<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\AttendanceBreak;
use App\Models\Notification;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class AttendanceNotificationService
{
    /**
     * Get all target supervisor user IDs for an employee (Production Manager, Direct Manager, Org Admins, Super Admins)
     */
    public static function getSupervisorsForUser(User $employee): array
    {
        $supervisorIds = collect();

        // 1. Direct Line Manager
        if (!empty($employee->manager_id)) {
            $supervisorIds->push($employee->manager_id);
        }

        // 2. Team Lead
        if ($employee->team_id) {
            $teamLeaderId = \App\Models\Team::where('id', $employee->team_id)->value('leader_id');
            if ($teamLeaderId) {
                $supervisorIds->push($teamLeaderId);
            }
        }

        // 3. Department Head
        if ($employee->department_id) {
            $deptHeadId = \App\Models\Department::where('id', $employee->department_id)->value('head_user_id');
            if ($deptHeadId) {
                $supervisorIds->push($deptHeadId);
            }
        }

        // 4. HR Managers & System Admins
        $adminUsers = User::excludeDevs()->where('is_active', true)
            ->whereHas('role', function ($r) {
                $r->whereIn('name', ['SYSTEM_ADMIN', 'SUPER_ADMIN', 'ORG_ADMIN', 'HR_MANAGER', 'HR'])
                  ->orWhere('level', '<=', 5);
            })
            ->where(function ($q) use ($employee) {
                if ($employee->organization_id) {
                    $q->where('organization_id', $employee->organization_id)
                      ->orWhereNull('organization_id');
                }
            })
            ->get();

        foreach ($adminUsers as $admin) {
            if ($admin->isAdmin() || $admin->isSuperAdmin() || $admin->isDeveloperAdmin() || $admin->isHR()) {
                $supervisorIds->push($admin->id);
            }
        }

        // Remove duplicates and exclude the employee themselves
        return $supervisorIds->unique()->filter(function ($id) use ($employee) {
            return $id !== $employee->id;
        })->values()->all();
    }

    /**
     * Dispatch notification to a list of user IDs
     */
    public static function sendToUsers(array $userIds, string $title, string $message, string $type, array $data = [], ?string $actionUrl = null): void
    {
        if (empty($userIds)) {
            return;
        }

        $emailEnabled = (bool) SystemSetting::get('notification_email_alerts', true);

        foreach ($userIds as $uid) {
            try {
                // 1. Create In-App Notification
                Notification::create([
                    'user_id' => $uid,
                    'title' => $title,
                    'message' => $message,
                    'type' => $type,
                    'data' => $data,
                    'action_url' => $actionUrl ?: route('attendance.index'),
                    'is_read' => false,
                ]);

                // 2. Send email if enabled
                if ($emailEnabled) {
                    $recipient = User::find($uid);
                    if ($recipient && !empty($recipient->email)) {
                        self::sendEmailAlert($recipient, $title, $message);
                    }
                }
            } catch (\Throwable $e) {
                Log::warning("Could not dispatch notification to user {$uid}: " . $e->getMessage());
            }
        }
    }

    /**
     * Send email alert safely (wrapped in try-catch so failures won't block HTTP flow)
     */
    private static function sendEmailAlert(User $recipient, string $title, string $message): void
    {
        try {
            Mail::raw("Hello {$recipient->name},\n\n{$message}\n\nView details: " . url('/attendance') . "\n\nBest regards,\nAxeCorp HRMS Automated Alert System", function ($m) use ($recipient, $title) {
                $m->to($recipient->email)->subject("[AxeCorp HRMS Alert] " . $title);
            });
        } catch (\Throwable $e) {
            // Ignored if local mail driver is offline or misconfigured
        }
    }

    /**
     * Notify clock-in event
     */
    public static function notifyClockIn(Attendance $attendance): void
    {
        $employee = $attendance->user;
        if (!$employee) return;

        $timeStr = date('h:i A', strtotime($attendance->clock_in));
        $statusStr = $attendance->status;
        $isLate = ($statusStr === 'LATE');
        $locationStr = $attendance->location_verified ? "🏢 Office Verified" : "🌐 Remote / Web";

        $title = $isLate 
            ? "⚠️ Late Arrival: {$employee->name}" 
            : "Clock-In Recorded: {$employee->name}";

        $message = "{$employee->name} clocked in today at {$timeStr} ({$statusStr}). Location: {$locationStr}.";

        $supervisors = self::getSupervisorsForUser($employee);
        self::sendToUsers(
            $supervisors,
            $title,
            $message,
            $isLate ? 'ATTENDANCE_LATE' : 'CLOCK_IN',
            [
                'attendance_id' => $attendance->id,
                'employee_id' => $employee->id,
                'employee_name' => $employee->name,
                'time' => $timeStr,
                'status' => $statusStr,
                'location_verified' => $attendance->location_verified,
            ],
            route('attendance.index')
        );
    }

    /**
     * Notify clock-out event
     */
    public static function notifyClockOut(Attendance $attendance): void
    {
        $employee = $attendance->user;
        if (!$employee) return;

        $timeStr = date('h:i A', strtotime($attendance->clock_out));
        $totalStr = $attendance->getFormattedTotalTime();
        $breakStr = $attendance->getFormattedBreakTime();
        $netStr = $attendance->getFormattedNetTime();

        $title = "Clock-Out: {$employee->name}";
        $message = "{$employee->name} clocked out at {$timeStr}. Total Shift: {$totalStr}, Total Breaks: {$breakStr}, Net Work Time: {$netStr}.";

        $supervisors = self::getSupervisorsForUser($employee);
        self::sendToUsers(
            $supervisors,
            $title,
            $message,
            'CLOCK_OUT',
            [
                'attendance_id' => $attendance->id,
                'employee_id' => $employee->id,
                'employee_name' => $employee->name,
                'clock_out_time' => $timeStr,
                'net_work_minutes' => $attendance->net_work_minutes,
                'total_break_minutes' => $attendance->total_break_minutes,
            ],
            route('attendance.index')
        );
    }

    /**
     * Notify when an employee starts a break
     */
    public static function notifyBreakStarted(AttendanceBreak $break): void
    {
        $attendance = $break->attendance;
        if (!$attendance || !$attendance->user) return;
        $employee = $attendance->user;

        $timeStr = date('h:i A', strtotime($break->start_time));
        $breakName = ucfirst(strtolower($break->break_type));
        $allowedMins = AttendanceBreak::getAllowedMinutes($break->break_type);

        $title = "{$breakName} Break Started: {$employee->name}";
        $message = "{$employee->name} has started their {$breakName} break at {$timeStr} (Allowed: {$allowedMins} mins).";

        $supervisors = self::getSupervisorsForUser($employee);
        self::sendToUsers(
            $supervisors,
            $title,
            $message,
            'BREAK_STARTED',
            [
                'break_id' => $break->id,
                'employee_id' => $employee->id,
                'employee_name' => $employee->name,
                'break_type' => $break->break_type,
                'start_time' => $timeStr,
                'allowed_minutes' => $allowedMins,
            ],
            route('attendance.index')
        );
    }

    /**
     * Notify when an employee ends a break & returns to work
     */
    public static function notifyBreakEnded(AttendanceBreak $break): void
    {
        $attendance = $break->attendance;
        if (!$attendance || !$attendance->user) return;
        $employee = $attendance->user;

        $timeStr = date('h:i A', strtotime($break->end_time));
        $breakName = ucfirst(strtolower($break->break_type));
        $durationStr = $break->getFormattedDuration();
        $isExceeded = $break->isExceeded();

        if ($isExceeded) {
            $allowedMins = AttendanceBreak::getAllowedMinutes($break->break_type);
            $title = "⚠️ Break Limit Exceeded: {$employee->name} ({$breakName})";
            $message = "{$employee->name} returned from {$breakName} break at {$timeStr}. Duration: {$durationStr} (Exceeded policy by {$break->exceeded_minutes} mins, max allowed: {$allowedMins}m).";
            $type = 'BREAK_EXCEEDED';
        } else {
            $title = "Resumed Work: {$employee->name} ({$breakName} Break Done)";
            $message = "{$employee->name} has resumed work at {$timeStr} after taking {$durationStr} {$breakName} break.";
            $type = 'BREAK_ENDED';
        }

        $supervisors = self::getSupervisorsForUser($employee);
        self::sendToUsers(
            $supervisors,
            $title,
            $message,
            $type,
            [
                'break_id' => $break->id,
                'employee_id' => $employee->id,
                'employee_name' => $employee->name,
                'break_type' => $break->break_type,
                'duration_minutes' => $break->duration_minutes,
                'exceeded_minutes' => $break->exceeded_minutes,
                'end_time' => $timeStr,
            ],
            route('attendance.index')
        );
    }
}
