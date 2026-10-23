<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\AttendanceBreak;
use App\Models\LeaveRequest;
use App\Models\Project;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\Request;

class ProductionController extends Controller
{
    public function index(Request $request)
    {
        $today = date('Y-m-d');
        $now = now();

        // Fetch all tech & design production employees (Software Engineering, UI/UX Design, QA, Product)
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

        // Map today's attendance status, active breaks & live availability
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
            $activityState = 'OFFLINE'; // AT_DESK, ON_LUNCH, ON_NAMAZ, ON_SMOKE, ON_TEA, ON_LEAVE, OFFLINE, SHIFT_DONE
            $activeBreak = null;
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
                } else {
                    $activityState = 'AT_DESK';
                }
            }

            $currentProject = $u->projects->where('status', 'IN_PROGRESS')->first();

            return [
                'user' => $u,
                'status' => $status,
                'activity_state' => $activityState,
                'attendance_id' => $att ? $att->id : null,
                'clock_in' => $att ? $att->clock_in : null,
                'clock_out' => $att ? $att->clock_out : null,
                'net_work_time' => $att ? $att->getFormattedNetTime() : '--',
                'total_break_time' => $att ? $att->getFormattedBreakTime() : '0m',
                'active_break' => $activeBreak,
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
        $presentCount = $staffAvailability->whereIn('status', ['PRESENT', 'LATE'])->count();
        $lateCount = $staffAvailability->where('status', 'LATE')->count();
        $absentCount = $staffAvailability->where('status', 'NOT_CLOCKED')->count();
        $leaveCount = $staffAvailability->where('status', 'ON_LEAVE')->count();
        $availableCount = $staffAvailability->where('is_available', true)->count();

        $productionTeams = Team::withCount('members')->get();
        $staffListPayload = $staffAvailability->map(function ($item) {
            return [
                'user_id' => $item['user']->id,
                'name' => $item['user']->name,
                'avatar_url' => $item['user']->avatar_url,
                'initials' => $item['user']->initials,
                'designation' => $item['user']->designation->title ?? 'Tech Team Member',
                'department' => $item['user']->department->name ?? 'Software Engineering & Design',
                'team' => $item['user']->team->name ?? 'Core Team',
                'status' => $item['status'],
                'activity_state' => $item['activity_state'],
                'attendance_id' => $item['attendance_id'],
                'clock_in' => $item['clock_in'] ? date('h:i A', strtotime($item['clock_in'])) : null,
                'clock_out' => $item['clock_out'] ? date('h:i A', strtotime($item['clock_out'])) : null,
                'net_work_time' => $item['net_work_time'],
                'total_break_time' => $item['total_break_time'],
                'active_break' => $item['active_break'] ? [
                    'id' => $item['active_break']->id,
                    'break_type' => strtoupper($item['active_break']->break_type),
                    'start_time' => date('h:i A', strtotime($item['active_break']->start_time)),
                    'start_timestamp' => strtotime($item['active_break']->start_time),
                    'allowed_minutes' => $item['break_allowed_minutes'],
                ] : null,
                'break_elapsed_minutes' => $item['break_elapsed_minutes'],
                'break_allowed_minutes' => $item['break_allowed_minutes'],
                'is_break_exceeded' => $item['is_break_exceeded'],
                'exceeded_minutes' => $item['exceeded_minutes'],
                'is_available' => $item['is_available'],
                'current_project' => $item['current_project'],
            ];
        })->values();

        $activeProjects = Project::where('status', 'IN_PROGRESS')->withCount('members')->get();

        return view('production.index', compact(
            'staffAvailability',
            'staffListPayload',
            'totalStaff',
            'atDeskCount',
            'onLunchCount',
            'onNamazCount',
            'onSmokeCount',
            'onTeaCount',
            'breakExceededCount',
            'presentCount',
            'lateCount',
            'absentCount',
            'leaveCount',
            'availableCount',
            'productionTeams',
            'activeProjects'
        ));
    }
}
