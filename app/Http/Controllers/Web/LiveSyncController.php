<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\AttendanceBreak;
use App\Models\Department;
use App\Models\LeaveRequest;
use App\Models\Notification;
use App\Models\Payroll;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LiveSyncController extends Controller
{
    /**
     * Ultra-fast pulse endpoint for background delta checks across the entire system.
     * Returns state fingerprints and metric counts in <15ms.
     */
    public function pulse(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['authenticated' => false], 401);
        }

        $today = date('Y-m-d');
        $now = now();

        // 1. Unread notifications count for current user
        $unreadNotifications = Notification::where('user_id', $user->id)
            ->where('is_read', false)
            ->count();

        // 2. Today's attendance and active break telemetry
        $todayAttendanceQuery = Attendance::whereDate('date', $today);
        if ($user->organization_id && !$user->isSuperAdmin()) {
            $todayAttendanceQuery->where('organization_id', $user->organization_id);
        }

        $todayAttendanceCount = (clone $todayAttendanceQuery)->count();
        $todayPresentCount = (clone $todayAttendanceQuery)->whereIn('status', ['PRESENT', 'LATE'])->count();
        $activeBreaksCount = (clone $todayAttendanceQuery)->whereNotNull('current_break_type')->count();

        // 3. Pending leaves count
        $pendingLeavesQuery = LeaveRequest::where('status', 'PENDING');
        if ($user->isEmployee()) {
            $pendingLeavesQuery->where('user_id', $user->id);
        } elseif ($user->organization_id && !$user->isSuperAdmin()) {
            $pendingLeavesQuery->whereHas('user', function ($q) use ($user) {
                $q->where('organization_id', $user->organization_id);
            });
        }
        $pendingLeavesCount = $pendingLeavesQuery->count();

        // 4. Calculate Production Hub Hash (combining active breaks, latest clock updates)
        $latestAttUpdate = Attendance::whereDate('date', $today)->max('updated_at') ?? 'none';
        $latestBreakUpdate = AttendanceBreak::whereDate('start_time', $today)->max('updated_at') ?? 'none';
        $productionHash = md5($latestAttUpdate . '_' . $latestBreakUpdate . '_' . $activeBreaksCount);

        // 5. Calculate Dashboard Hash
        $dashboardHash = md5($todayAttendanceCount . '_' . $todayPresentCount . '_' . $activeBreaksCount . '_' . $pendingLeavesCount . '_' . $unreadNotifications);

        // 6. User's own attendance status
        $myAttendance = Attendance::with('activeBreak')
            ->where('user_id', $user->id)
            ->whereDate('date', $today)
            ->first();

        $myStatus = 'NOT_CLOCKED';
        $myBreakType = null;
        $myBreakStartTs = null;
        if ($myAttendance) {
            if ($myAttendance->clock_out) {
                $myStatus = 'SHIFT_DONE';
            } elseif ($myAttendance->activeBreak) {
                $myStatus = 'ON_BREAK';
                $myBreakType = strtoupper($myAttendance->activeBreak->break_type);
                $myBreakStartTs = strtotime($myAttendance->activeBreak->start_time);
            } else {
                $myStatus = 'AT_DESK';
            }
        }

        return response()->json([
            'authenticated' => true,
            'server_timestamp' => $now->timestamp,
            'today_date' => $today,
            'unread_notifications' => $unreadNotifications,
            'production_hash' => $productionHash,
            'dashboard_hash' => $dashboardHash,
            'today_attendance_count' => $todayAttendanceCount,
            'today_present_count' => $todayPresentCount,
            'active_breaks_count' => $activeBreaksCount,
            'pending_leaves_count' => $pendingLeavesCount,
            'my_attendance' => [
                'status' => $myStatus,
                'break_type' => $myBreakType,
                'break_start_timestamp' => $myBreakStartTs,
                'clock_in' => $myAttendance && $myAttendance->clock_in ? date('h:i A', strtotime($myAttendance->clock_in)) : null,
                'net_work_time' => $myAttendance ? $myAttendance->getFormattedNetTime() : '--',
            ]
        ])->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
          ->header('Pragma', 'no-cache');
    }

    /**
     * Complete live dataset for the Production Floor & Dev Availability Hub
     */
    public function productionData(Request $request)
    {
        $today = date('Y-m-d');
        $now = now();

        // Fetch all tech & design production employees
        $productionStaff = User::excludeDevs()
            ->with(['department', 'team', 'designation', 'projects'])
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereHas('department', function ($q) {
                    $q->whereIn('name', ['Software Engineering & Design', 'Software Engineering', 'UI/UX Design', 'Design', 'QA & Testing', 'Product Management', 'Engineering']);
                })->orWhereNotNull('team_id');
            })
            ->get();

        if ($productionStaff->isEmpty()) {
            $productionStaff = User::excludeDevs()
                ->with(['department', 'team', 'designation', 'projects'])
                ->where('is_active', true)
                ->get();
        }

        // Map live availability, breaks, and timers
        $staffAvailability = $productionStaff->map(function ($u) use ($today, $now) {
            $att = Attendance::with(['breaks', 'activeBreak'])
                ->where('user_id', $u->id)
                ->whereDate('date', $today)
                ->first();

            $leave = LeaveRequest::where('user_id', $u->id)
                ->where('status', 'APPROVED')
                ->whereDate('start_date', '<=', $today)
                ->whereDate('end_date', '>=', $today)
                ->first();

            $status = 'NOT_CLOCKED';
            $activityState = 'OFFLINE';
            $activeBreakData = null;
            $breakElapsedMinutes = 0;
            $breakAllowedMinutes = 0;
            $isBreakExceeded = false;
            $exceededMinutes = 0;

            if ($leave) {
                $status = 'ON_LEAVE';
                $activityState = 'ON_LEAVE';
            } elseif ($att) {
                $status = $att->status;

                if ($att->clock_out) {
                    $activityState = 'SHIFT_DONE';
                } elseif ($att->activeBreak) {
                    $activeBreak = $att->activeBreak;
                    $breakType = strtoupper($activeBreak->break_type);
                    $activityState = 'ON_' . $breakType;

                    $startTs = strtotime($activeBreak->start_time);
                    $breakElapsedMinutes = max(1, (int) round(($now->timestamp - $startTs) / 60));
                    $breakAllowedMinutes = AttendanceBreak::getAllowedMinutes($breakType);

                    if ($breakElapsedMinutes > $breakAllowedMinutes) {
                        $isBreakExceeded = true;
                        $exceededMinutes = $breakElapsedMinutes - $breakAllowedMinutes;
                    }

                    $activeBreakData = [
                        'id' => $activeBreak->id,
                        'break_type' => $breakType,
                        'start_time' => date('h:i A', $startTs),
                        'start_timestamp' => $startTs,
                        'allowed_minutes' => $breakAllowedMinutes,
                    ];
                } else {
                    $activityState = 'AT_DESK';
                }
            }

            $currentProject = $u->projects->where('status', 'IN_PROGRESS')->first();

            return [
                'user_id' => $u->id,
                'name' => $u->name,
                'avatar_url' => $u->avatar_url,
                'initials' => $u->initials,
                'designation' => $u->designation->title ?? 'Tech Team Member',
                'department' => $u->department->name ?? 'Software Engineering & Design',
                'team' => $u->team->name ?? 'Core Team',
                'status' => $status,
                'activity_state' => $activityState,
                'attendance_id' => $att ? $att->id : null,
                'clock_in' => $att && $att->clock_in ? date('h:i A', strtotime($att->clock_in)) : null,
                'clock_out' => $att && $att->clock_out ? date('h:i A', strtotime($att->clock_out)) : null,
                'net_work_time' => $att ? $att->getFormattedNetTime() : '--',
                'total_break_time' => $att ? $att->getFormattedBreakTime() : '0m',
                'active_break' => $activeBreakData,
                'break_elapsed_minutes' => $breakElapsedMinutes,
                'break_allowed_minutes' => $breakAllowedMinutes,
                'is_break_exceeded' => $isBreakExceeded,
                'exceeded_minutes' => $exceededMinutes,
                'is_available' => in_array($activityState, ['AT_DESK']),
                'current_project' => $currentProject ? $currentProject->name : 'Unassigned (Bench)',
            ];
        });

        // Summary counts
        $totalStaff = count($staffAvailability);
        $atDeskCount = $staffAvailability->where('activity_state', 'AT_DESK')->count();
        $onLunchCount = $staffAvailability->where('activity_state', 'ON_LUNCH')->count();
        $onNamazCount = $staffAvailability->where('activity_state', 'ON_NAMAZ')->count();
        $onSmokeCount = $staffAvailability->where('activity_state', 'ON_SMOKE')->count();
        $onTeaCount = $staffAvailability->where('activity_state', 'ON_TEA')->count();
        $breakExceededCount = $staffAvailability->where('is_break_exceeded', true)->count();
        $absentCount = $staffAvailability->where('status', 'NOT_CLOCKED')->count();
        $leaveCount = $staffAvailability->where('status', 'ON_LEAVE')->count();
        $availableCount = $staffAvailability->where('is_available', true)->count();

        return response()->json([
            'server_timestamp' => $now->timestamp,
            'today_formatted' => date('d M Y'),
            'counts' => [
                'total_staff' => $totalStaff,
                'at_desk' => $atDeskCount,
                'on_lunch' => $onLunchCount,
                'on_namaz_smoke' => $onNamazCount + $onSmokeCount + $onTeaCount,
                'break_exceeded' => $breakExceededCount,
                'on_leave_off' => $leaveCount + $absentCount,
                'available' => $availableCount,
            ],
            'staff' => $staffAvailability->values(),
        ])->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
          ->header('Pragma', 'no-cache');
    }

    /**
     * Live metrics for the Main Dashboard
     */
    public function dashboardData(Request $request)
    {
        $user = Auth::user();
        $today = date('Y-m-d');

        $totalEmployees = User::excludeDevs()->count();
        $departmentsCount = Department::count();
        $todayAttendance = Attendance::whereDate('date', $today)->count();
        $presentToday = Attendance::whereDate('date', $today)->whereIn('status', ['PRESENT', 'LATE', 'HALF_DAY'])->count();
        $pendingLeavesCount = LeaveRequest::where('status', 'PENDING')->count();
        $totalPayrollAmount = (float) User::excludeDevs()->where('is_active', true)->get()->sum(function ($u) {
            $base = (float) ($u->base_salary ?? 0);
            $allowances = (float) ($u->allowances ?? 0);
            $deductions = (float) ($u->deductions ?? 0);
            return max(0, $base + $allowances - $deductions);
        });

        // Self service counts for employee
        $myPendingTasksCount = Task::where('user_id', $user->id)->where('status', '!=', 'COMPLETED')->count();
        $myCompletedTasksCount = Task::where('user_id', $user->id)->where('status', 'COMPLETED')->count();
        $myPendingLeaves = LeaveRequest::where('user_id', $user->id)->where('status', 'PENDING')->count();

        // User today attendance
        $userTodayAttendance = Attendance::with(['breaks', 'activeBreak'])
            ->where('user_id', $user->id)
            ->whereDate('date', $today)
            ->first();

        return response()->json([
            'total_employees' => $totalEmployees,
            'departments_count' => $departmentsCount,
            'today_attendance' => $todayAttendance,
            'present_today' => $presentToday,
            'pending_leaves_count' => $pendingLeavesCount,
            'total_payroll_amount' => $totalPayrollAmount,
            'total_payroll_formatted' => 'PKR ' . number_format($totalPayrollAmount, 0),
            'my_pending_tasks_count' => $myPendingTasksCount,
            'my_completed_tasks_count' => $myCompletedTasksCount,
            'my_pending_leaves' => $myPendingLeaves,
            'user_attendance' => [
                'exists' => (bool) $userTodayAttendance,
                'clock_in' => $userTodayAttendance && $userTodayAttendance->clock_in ? date('h:i A', strtotime($userTodayAttendance->clock_in)) : null,
                'clock_out' => $userTodayAttendance && $userTodayAttendance->clock_out ? date('h:i A', strtotime($userTodayAttendance->clock_out)) : null,
                'is_on_break' => $userTodayAttendance ? $userTodayAttendance->isOnBreak() : false,
                'current_break_type' => $userTodayAttendance ? $userTodayAttendance->current_break_type : null,
                'net_work_time' => $userTodayAttendance ? $userTodayAttendance->getFormattedNetTime() : '--',
                'break_time' => $userTodayAttendance ? $userTodayAttendance->getFormattedBreakTime() : '0m',
            ]
        ])->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
          ->header('Pragma', 'no-cache');
    }
}
