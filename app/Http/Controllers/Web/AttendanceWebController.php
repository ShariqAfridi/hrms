<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\AttendanceBreak;
use App\Models\Organization;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\AttendanceNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class AttendanceWebController extends Controller
{
    /**
     * Defensively filter array attributes to columns that actually exist in the table.
     */
    protected function filterTableColumns(string $table, array $data): array
    {
        try {
            $columns = Schema::getColumnListing($table);
            if (!empty($columns)) {
                return array_intersect_key($data, array_flip($columns));
            }
        } catch (\Throwable $e) {}
        return $data;
    }
    public function index(Request $request)
    {
        $user = Auth::user();
        $query = Attendance::with(['user.department', 'user.designation', 'organization', 'breaks', 'activeBreak'])
            ->whereHas('user', function ($q) {
                $q->excludeDevs();
            });

        // Role-based record scoping
        if ($user->isEmployee()) {
            // 1. Employee sees only their own attendance
            $query->where('user_id', $user->id);
            $scopedUserIds = collect([$user->id]);
        } elseif ($user->isTeamManager() && !$user->isDeptManager() && !$user->isAdmin()) {
            // 2. Team Lead sees themselves + members of teams they lead + direct subordinates
            $teamIds = \App\Models\Team::where('leader_id', $user->id)->pluck('id');
            if ($user->team_id) {
                $teamIds->push($user->team_id);
            }
            $teamIds = $teamIds->filter()->unique();

            $scopedUserIds = User::excludeDevs()
                ->where(function ($q) use ($user, $teamIds) {
                    $q->where('manager_id', $user->id);
                    if ($teamIds->isNotEmpty()) {
                        $q->orWhereIn('team_id', $teamIds);
                    }
                })
                ->pluck('id')
                ->push($user->id)
                ->unique();

            $query->whereIn('user_id', $scopedUserIds);
        } elseif ($user->isDeptManager() && !$user->isAdmin()) {
            // 3. Department Head sees themselves + members of their departments + direct subordinates
            $deptIds = \App\Models\Department::where('head_user_id', $user->id)->pluck('id');
            if ($user->department_id) {
                $deptIds->push($user->department_id);
            }
            $deptIds = $deptIds->filter()->unique();

            $scopedUserIds = User::excludeDevs()
                ->where(function ($q) use ($user, $deptIds) {
                    $q->where('manager_id', $user->id);
                    if ($deptIds->isNotEmpty()) {
                        $q->orWhereIn('department_id', $deptIds);
                    }
                })
                ->pluck('id')
                ->push($user->id)
                ->unique();

            $query->whereIn('user_id', $scopedUserIds);
        } elseif ($user->organization_id && !$user->isSuperAdmin()) {
            // 4. HR Manager / Org Admin sees organization-wide attendance
            $query->where('organization_id', $user->organization_id);
            $scopedUserIds = null;
        } else {
            // 5. System Admin has system-wide access
            $scopedUserIds = null;
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('date')) {
            $query->whereDate('date', $request->input('date'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('organization_id')) {
            $query->where('organization_id', $request->input('organization_id'));
        }

        $attendances = $query->orderBy('date', 'desc')->orderBy('clock_in', 'desc')->paginate(15);

        $today = date('Y-m-d');
        $userAttendanceToday = Attendance::with(['breaks', 'activeBreak'])
            ->where('user_id', $user->id)
            ->whereDate('date', $today)
            ->first();

        $organizations = Organization::orderBy('name', 'asc')->get();

        // Populate employees dropdown for manual recording modal
        if (!$user->canManageEmployees()) {
            $employees = collect([$user]);
        } elseif ($scopedUserIds !== null) {
            $employees = User::excludeDevs()->where('is_active', true)->whereIn('id', $scopedUserIds)->orderBy('name', 'asc')->get();
        } elseif ($user->organization_id && !$user->isSuperAdmin()) {
            $employees = User::excludeDevs()->where('is_active', true)->where('organization_id', $user->organization_id)->orderBy('name', 'asc')->get();
        } else {
            $employees = User::excludeDevs()->where('is_active', true)->orderBy('name', 'asc')->get();
        }

        // Super Admin and HR Manager are exempt from clocking in; exclude exempt roles from staff manual punch dropdown
        if ($user->isClockInExempt()) {
            $employees = $employees->reject(fn ($emp) => $emp->isClockInExempt())->values();
        }

        // Key stats for today
        $todayQuery = Attendance::whereDate('date', $today)->whereHas('user', function ($q) {
            $q->excludeDevs();
        });
        if ($scopedUserIds !== null) {
            $todayQuery->whereIn('user_id', $scopedUserIds);
        } elseif ($user->organization_id && !$user->isSuperAdmin()) {
            $todayQuery->where('organization_id', $user->organization_id);
        }
        $todayPresentCount = (clone $todayQuery)->whereIn('status', ['PRESENT', 'LATE'])->count();
        $todayLateCount = (clone $todayQuery)->where('status', 'LATE')->count();
        $todayOnBreakCount = (clone $todayQuery)->whereNotNull('current_break_type')->count();

        return view('attendance.index', compact(
            'attendances',
            'userAttendanceToday',
            'organizations',
            'employees',
            'todayPresentCount',
            'todayLateCount',
            'todayOnBreakCount'
        ));
    }

    public function clockIn(Request $request)
    {
        try {
            $user = Auth::user();

            if ($user->isClockInExempt()) {
                return back()->with('info', 'Super Admins and HR Managers are exempt from daily clock-in requirements.');
            }

            $today = date('Y-m-d');

            $existing = Attendance::where('user_id', $user->id)
                ->whereDate('date', $today)
                ->first();

            if ($existing) {
                return back()->with('warning', 'You have already clocked in today!');
            }

            $now = now();
            $lateGraceMinutes = (int) SystemSetting::get('attendance_late_threshold', 15);
            $shift = $user->shift;

            if ($shift && $shift->flexible) {
                $status = 'PRESENT';
            } else {
                $shiftStartTime = ($shift && !empty($shift->start_time)) ? $shift->start_time : '09:00:00';
                if (strlen($shiftStartTime) === 5) {
                    $shiftStartTime .= ':00';
                }
                $lateThresholdTime = date('H:i:s', strtotime("{$shiftStartTime} + {$lateGraceMinutes} minutes"));
                $status = ($now->format('H:i:s') > $lateThresholdTime) ? 'LATE' : 'PRESENT';
            }

            $orgId = $user->organization_id;
            if (!$orgId) {
                $firstOrg = Organization::first();
                $orgId = $firstOrg ? $firstOrg->id : null;
            }

            // Location & Office Network Verification
            $clientIp = $request->ip();
            $allowedIps = array_filter(array_map('trim', explode(',', (string) SystemSetting::get('office_allowed_ips', ''))));
            $isIpVerified = empty($allowedIps) || in_array($clientIp, $allowedIps) || $clientIp === '127.0.0.1' || $clientIp === '::1';

            $latitude = $request->filled('latitude') ? (float) $request->input('latitude') : null;
            $longitude = $request->filled('longitude') ? (float) $request->input('longitude') : null;
            $isGpsVerified = false;

            $officeLat = (float) SystemSetting::get('office_latitude', 0);
            $officeLng = (float) SystemSetting::get('office_longitude', 0);
            $geofenceRadius = (float) SystemSetting::get('office_geofence_radius', 200); // meters

            if ($latitude && $longitude && $officeLat && $officeLng) {
                $distance = $this->calculateDistanceMeters($latitude, $longitude, $officeLat, $officeLng);
                $isGpsVerified = ($distance <= $geofenceRadius);
            }

            $locationVerified = $isIpVerified || $isGpsVerified;

            // Strict office verification enforcement
            $strictOfficeOnly = (bool) SystemSetting::get('attendance_strict_office_only', false);
            if ($strictOfficeOnly && !$locationVerified) {
                return back()->with('error', 'Clock-in rejected: You must be inside the designated office network or GPS geofence.');
            }

            $attendance = Attendance::create($this->filterTableColumns('attendances', [
                'user_id' => $user->id,
                'organization_id' => $orgId,
                'shift_id' => $user->shift_id,
                'date' => $today,
                'clock_in' => $now,
                'status' => $status,
                'device' => $request->header('User-Agent') ?: 'Web Browser',
                'ip_address' => $clientIp,
                'location_verified' => $locationVerified,
                'location_name' => $locationVerified ? 'Office Location' : 'Remote / Mobile',
                'latitude' => $latitude,
                'longitude' => $longitude,
                'total_minutes' => 0,
                'total_break_minutes' => 0,
                'net_work_minutes' => 0,
            ]));

            // Dispatch real-time notification to Production Manager and Admins
            AttendanceNotificationService::notifyClockIn($attendance);

            $msg = 'Clock-in recorded successfully at ' . $now->format('h:i A') . '!';
            if ($locationVerified) {
                $msg .= ' (Office Verified)';
            }
            return back()->with('success', $msg);
        } catch (\Throwable $e) {
            return back()->with('error', 'Could not record clock-in: ' . $e->getMessage());
        }
    }

    public function clockOut(Request $request)
    {
        try {
            $user = Auth::user();
            if ($user->isClockInExempt()) {
                return back()->with('info', 'Super Admins and HR Managers are exempt from shift tracking.');
            }

            $today = date('Y-m-d');

            $attendance = Attendance::with(['breaks', 'activeBreak'])
                ->where('user_id', $user->id)
                ->whereDate('date', $today)
                ->first();

            if (!$attendance) {
                return back()->with('error', 'No clock-in record found for today!');
            }

            if ($attendance->clock_out) {
                return back()->with('warning', 'You have already clocked out for today.');
            }

            $now = now();

            // If user has an active break ongoing, auto-close it
            if ($attendance->activeBreak) {
                $activeBreak = $attendance->activeBreak;
                $duration = max(1, (int) round(($now->timestamp - strtotime($activeBreak->start_time)) / 60));
                $allowed = AttendanceBreak::getAllowedMinutes($activeBreak->break_type);
                $exceeded = max(0, $duration - $allowed);

                $activeBreak->update($this->filterTableColumns('attendance_breaks', [
                    'end_time' => $now,
                    'duration_minutes' => $duration,
                    'exceeded_minutes' => $exceeded,
                    'status' => 'COMPLETED',
                ]));

                AttendanceNotificationService::notifyBreakEnded($activeBreak);
            }

            $attendanceUpdate = [
                'clock_out' => $now,
                'current_break_type' => null,
                'current_break_id' => null,
            ];
            $attendance->update($this->filterTableColumns('attendances', $attendanceUpdate));

            // Recalculate totals
            $attendance->recalculateTotals();

            // Dispatch notification to Production Manager and Admins
            AttendanceNotificationService::notifyClockOut($attendance);

            return back()->with('success', 'Clock-out recorded successfully at ' . $now->format('h:i A') . '! Total Net Work: ' . $attendance->getFormattedNetTime());
        } catch (\Throwable $e) {
            return back()->with('error', 'Could not record clock-out: ' . $e->getMessage());
        }
    }

    public function startBreak(Request $request)
    {
        $validated = $request->validate([
            'break_type' => 'required|string|in:LUNCH,NAMAZ,SMOKE,TEA,OTHER',
            'notes' => 'nullable|string|max:255',
        ]);

        try {
            $user = Auth::user();
            if ($user->isClockInExempt()) {
                return back()->with('info', 'Super Admins and HR Managers are exempt from shift break tracking.');
            }

            $today = date('Y-m-d');

            $attendance = Attendance::where('user_id', $user->id)
                ->whereDate('date', $today)
                ->first();

            if (!$attendance) {
                return back()->with('error', 'You must clock in before starting a break.');
            }

            if ($attendance->clock_out) {
                return back()->with('error', 'You have already clocked out for today.');
            }

            if ($attendance->isOnBreak()) {
                return back()->with('warning', 'You are already on a ' . $attendance->current_break_type . ' break!');
            }

            $now = now();
            $break = AttendanceBreak::create($this->filterTableColumns('attendance_breaks', [
                'attendance_id' => $attendance->id,
                'break_type' => $validated['break_type'],
                'start_time' => $now,
                'status' => 'ACTIVE',
                'notes' => $validated['notes'] ?? null,
                'ip_address' => $request->ip(),
            ]));

            $attendance->update($this->filterTableColumns('attendances', [
                'current_break_type' => $validated['break_type'],
                'current_break_id' => $break->id,
            ]));

            // Dispatch Notification to Production Manager and Admins
            AttendanceNotificationService::notifyBreakStarted($break);

            $breakName = ucfirst(strtolower($validated['break_type']));
            $allowedMins = AttendanceBreak::getAllowedMinutes($validated['break_type']);

            return back()->with('success', "{$breakName} break started at " . $now->format('h:i A') . ". Max duration: {$allowedMins} mins.");
        } catch (\Throwable $e) {
            return back()->with('error', 'Could not start break: ' . $e->getMessage());
        }
    }

    public function endBreak(Request $request)
    {
        try {
            $user = Auth::user();
            $today = date('Y-m-d');

            $attendance = Attendance::where('user_id', $user->id)
                ->whereDate('date', $today)
                ->first();

            if (!$attendance) {
                return back()->with('error', 'No attendance record found for today.');
            }

            $break = AttendanceBreak::where('attendance_id', $attendance->id)
                ->where('status', 'ACTIVE')
                ->latest('start_time')
                ->first();

            if (!$break) {
                return back()->with('warning', 'No active break found to end.');
            }

            $now = now();
            $startTs = strtotime($break->start_time);
            $durationMinutes = max(1, (int) round(($now->timestamp - $startTs) / 60));
            $allowedMinutes = AttendanceBreak::getAllowedMinutes($break->break_type);
            $exceededMinutes = max(0, $durationMinutes - $allowedMinutes);

            $break->update([
                'end_time' => $now,
                'duration_minutes' => $durationMinutes,
                'exceeded_minutes' => $exceededMinutes,
                'status' => 'COMPLETED',
            ]);

            $attendance->update([
                'current_break_type' => null,
                'current_break_id' => null,
            ]);

            // Recalculate totals
            $attendance->recalculateTotals();

            // Dispatch Notification to Production Manager and Admins
            AttendanceNotificationService::notifyBreakEnded($break);

            $breakName = ucfirst(strtolower($break->break_type));
            $msg = "{$breakName} break ended ({$durationMinutes} mins). Resumed work at " . $now->format('h:i A') . ".";
            if ($exceededMinutes > 0) {
                $msg .= " ⚠️ Exceeded allowed limit by {$exceededMinutes} mins.";
                return back()->with('warning', $msg);
            }

            return back()->with('success', $msg);
        } catch (\Throwable $e) {
            return back()->with('error', 'Could not end break: ' . $e->getMessage());
        }
    }

    public function getBreakLogs($id)
    {
        $attendance = Attendance::with(['breaks', 'user'])->findOrFail($id);
        
        $breaks = $attendance->breaks->map(function ($b) {
            return [
                'id' => $b->id,
                'break_type' => $b->break_type,
                'icon' => $b->getIcon(),
                'badge_class' => $b->getBadgeClass(),
                'start_time' => date('h:i A', strtotime($b->start_time)),
                'end_time' => $b->end_time ? date('h:i A', strtotime($b->end_time)) : 'In Progress',
                'duration' => $b->getFormattedDuration(),
                'duration_minutes' => $b->duration_minutes,
                'exceeded_minutes' => $b->exceeded_minutes,
                'is_exceeded' => $b->isExceeded(),
                'status' => $b->status,
                'notes' => $b->notes,
            ];
        });

        return response()->json([
            'employee_name' => $attendance->user->name ?? 'Employee',
            'date' => date('d M Y', strtotime($attendance->date)),
            'total_shift' => $attendance->getFormattedTotalTime(),
            'total_breaks' => $attendance->getFormattedBreakTime(),
            'net_work' => $attendance->getFormattedNetTime(),
            'breaks' => $breaks,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'date' => 'required|date',
            'clock_in_time' => 'nullable',
            'clock_out_time' => 'nullable',
            'status' => 'required|string|in:PRESENT,LATE,ABSENT,HALF_DAY',
            'remarks' => 'nullable|string',
        ]);

        try {
            $user = User::findOrFail($validated['user_id']);
            $dateStr = date('Y-m-d', strtotime($validated['date']));
            $timeIn = !empty($validated['clock_in_time']) ? trim($validated['clock_in_time']) : '09:00:00';
            if (strlen($timeIn) === 5) {
                $timeIn .= ':00';
            }
            $clockInDateTime = $dateStr . ' ' . $timeIn;

            $clockOutDateTime = null;
            if (!empty($validated['clock_out_time'])) {
                $timeOut = trim($validated['clock_out_time']);
                if (strlen($timeOut) === 5) {
                    $timeOut .= ':00';
                }
                $clockOutDateTime = $dateStr . ' ' . $timeOut;
            }

            $att = Attendance::updateOrCreate(
                ['user_id' => $user->id, 'date' => $dateStr],
                [
                    'organization_id' => $user->organization_id ?: Organization::first()?->id,
                    'shift_id' => $user->shift_id,
                    'clock_in' => $clockInDateTime,
                    'clock_out' => $clockOutDateTime,
                    'status' => $validated['status'],
                    'remarks' => $validated['remarks'] ?? null,
                    'location_name' => 'Supervisor Manual Entry',
                    'location_verified' => true,
                ]
            );

            $att->recalculateTotals();

            return back()->with('success', 'Attendance record saved successfully!');
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', 'Could not save attendance: ' . $e->getMessage());
        }
    }

    public function update(Request $request, $id)
    {
        $attendance = Attendance::findOrFail($id);

        $validated = $request->validate([
            'clock_in_time' => 'nullable',
            'clock_out_time' => 'nullable',
            'status' => 'required|string|in:PRESENT,LATE,ABSENT,HALF_DAY',
            'remarks' => 'nullable|string',
        ]);

        try {
            $dateStr = date('Y-m-d', strtotime($attendance->date));
            $timeIn = !empty($validated['clock_in_time']) ? trim($validated['clock_in_time']) : date('H:i:s', strtotime($attendance->clock_in ?: '09:00:00'));
            if (strlen($timeIn) === 5) {
                $timeIn .= ':00';
            }
            $clockInDateTime = $dateStr . ' ' . $timeIn;

            $clockOutDateTime = null;
            if (!empty($validated['clock_out_time'])) {
                $timeOut = trim($validated['clock_out_time']);
                if (strlen($timeOut) === 5) {
                    $timeOut .= ':00';
                }
                $clockOutDateTime = $dateStr . ' ' . $timeOut;
            }

            $attendance->update([
                'clock_in' => $clockInDateTime,
                'clock_out' => $clockOutDateTime,
                'status' => $validated['status'],
                'remarks' => $validated['remarks'] ?? null,
            ]);

            $attendance->recalculateTotals();

            return back()->with('success', 'Attendance log updated successfully!');
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', 'Could not update attendance: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            $attendance = Attendance::findOrFail($id);
            $attendance->delete();

            return back()->with('success', 'Attendance record deleted successfully!');
        } catch (\Throwable $e) {
            return back()->with('error', 'Could not delete attendance: ' . $e->getMessage());
        }
    }

    private function calculateDistanceMeters($lat1, $lon1, $lat2, $lon2): float
    {
        $earthRadius = 6371000; // meters
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $earthRadius * $c;
    }
}
