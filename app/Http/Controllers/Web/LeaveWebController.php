<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LeaveWebController extends Controller
{
    public function index(Request $request)
    {
        $defaultLeaveType = $this->ensureDefaultLeaveTypesExist();

        $user = Auth::user();
        $isSuperAdmin = $user->isSuperAdmin() || $user->isDeveloperAdmin();
        $query = LeaveRequest::with(['user.department', 'user.team', 'leaveType', 'teamManager', 'deptManager', 'forwarder', 'approver', 'revoker']);

        // Scope records by role
        if ($user->isEmployee()) {
            $query->where('user_id', $user->id);
        } elseif ($user->isTeamManager() && !$user->isDeptManager() && !$user->isAdmin()) {
            // Team Lead sees their own + members of their team or direct subordinates
            $teamMemberIds = \App\Models\User::excludeDevs()
                ->where(function ($q) use ($user) {
                    if ($user->team_id) {
                        $q->where('team_id', $user->team_id);
                    }
                    $q->orWhere('manager_id', $user->id);
                })
                ->pluck('id')
                ->push($user->id);

            $query->whereIn('user_id', $teamMemberIds);
        } elseif ($user->isDeptManager() && !$user->isAdmin()) {
            // Department Manager sees their own + members of their department
            $deptMemberIds = \App\Models\User::excludeDevs()
                ->where(function ($q) use ($user) {
                    if ($user->department_id) {
                        $q->where('department_id', $user->department_id);
                    }
                    $q->orWhere('manager_id', $user->id);
                })
                ->pluck('id')
                ->push($user->id);

            $query->whereIn('user_id', $deptMemberIds);
        } elseif ($user->isProjectManager() && !$user->isAdmin()) {
            // Project manager sees their own + project assignees
            $projectIds = $user->projects()->pluck('projects.id');
            $projectUserIds = \Illuminate\Support\Facades\DB::table('project_user')
                ->whereIn('project_id', $projectIds)
                ->pluck('user_id')
                ->push($user->id);

            $query->whereIn('user_id', $projectUserIds);
        } else {
            // Admin (Super Admin, HR, Org Admin)
            $query->whereHas('user', function ($q) {
                $q->excludeDevs();
            });

            if ($user->isOrgAdmin() && !$user->isSuperAdmin() && !$user->isHR() && $user->organization_id) {
                $query->where('organization_id', $user->organization_id);
            }
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $leaveRequests = $query->orderBy('created_at', 'desc')->paginate(10);

        // Fixed 12 leaves allocation (no separate types, just 12 leaves total)
        $allocatedLeaves = 12;
        $currentYear = date('Y');
        $today = date('Y-m-d');

        if ($isSuperAdmin) {
            // Super Admin only monitors - calculate organizational monitoring metrics
            $monitoringStats = [
                'pending_action' => LeaveRequest::whereIn('status', ['PENDING', 'PENDING_LEAD', 'FORWARDED_TO_DEPT'])->count(),
                'on_leave_today' => LeaveRequest::where('status', 'APPROVED')
                    ->whereDate('start_date', '<=', $today)
                    ->whereDate('end_date', '>=', $today)
                    ->count(),
                'approved_this_year' => LeaveRequest::where('status', 'APPROVED')
                    ->whereYear('start_date', $currentYear)
                    ->count(),
                'total_requests' => LeaveRequest::whereYear('created_at', $currentYear)->count(),
            ];
            $remainingLeaves = 0;
            $usedLeaves = 0;
            $usagePercent = 0;
            $myPendingCount = 0;
            $myApprovedCount = 0;
            $pendingMyApprovalCount = $monitoringStats['pending_action'];
        } else {
            $monitoringStats = null;

            // Calculate single 12-leave balance for employee
            $approvedThisYear = LeaveRequest::where('user_id', $user->id)
                ->where('status', 'APPROVED')
                ->whereYear('start_date', $currentYear)
                ->get();

            $usedLeaves = (float) $approvedThisYear->sum('total_days');
            $remainingLeaves = max(0, $allocatedLeaves - $usedLeaves);
            $usagePercent = min(100, round(($usedLeaves / $allocatedLeaves) * 100));

            $myPendingCount = LeaveRequest::where('user_id', $user->id)->whereIn('status', ['PENDING', 'PENDING_LEAD', 'FORWARDED_TO_DEPT'])->count();
            $myApprovedCount = LeaveRequest::where('user_id', $user->id)->where('status', 'APPROVED')->count();

            // Pending approval actionable counts
            $pendingMyApprovalCount = 0;
            if ($user->isTeamManager() && !$user->isDeptManager() && !$user->isAdmin()) {
                $pendingMyApprovalCount = (clone $query)->where('status', 'PENDING_LEAD')->where('user_id', '!=', $user->id)->count();
            } elseif ($user->isDeptManager() && !$user->isAdmin()) {
                $pendingMyApprovalCount = (clone $query)->where('status', 'FORWARDED_TO_DEPT')->where('user_id', '!=', $user->id)->count();
            } elseif ($user->isAdmin()) {
                $pendingMyApprovalCount = LeaveRequest::whereIn('status', ['PENDING', 'PENDING_LEAD', 'FORWARDED_TO_DEPT'])->where('user_id', '!=', $user->id)->count();
            }
        }

        return view('leaves.index', compact(
            'leaveRequests',
            'isSuperAdmin',
            'allocatedLeaves',
            'remainingLeaves',
            'usedLeaves',
            'usagePercent',
            'myPendingCount',
            'myApprovedCount',
            'pendingMyApprovalCount',
            'monitoringStats'
        ));
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        if ($user->isSuperAdmin() || $user->isDeveloperAdmin()) {
            return back()->with('error', 'Super Admin accounts are for administrative monitoring only and do not submit leave requests.');
        }

        $defaultLeaveType = $this->ensureDefaultLeaveTypesExist();

        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'required|string|min:3',
            'is_half_day' => 'nullable|boolean',
            'emergency_contact' => 'nullable|string',
        ]);

        try {
            $start = strtotime($validated['start_date']);
            $end = strtotime($validated['end_date']);
            $days = (int) (($end - $start) / (60 * 60 * 24)) + 1;
            if (!empty($validated['is_half_day'])) {
                $days = 0.5;
            }

            // Check remaining leaves balance out of 12 for the year
            $currentYear = date('Y', $start);
            $usedLeaves = (float) LeaveRequest::where('user_id', $user->id)
                ->where('status', 'APPROVED')
                ->whereYear('start_date', $currentYear)
                ->sum('total_days');
            $remainingLeaves = max(0, 12 - $usedLeaves);

            if ($days > $remainingLeaves) {
                return back()->with('error', "Insufficient leave balance. You requested {$days} day(s), but only have {$remainingLeaves} leave day(s) remaining out of your 12 annual leaves.");
            }

            $orgId = $user->organization_id ?: Organization::first()?->id;

            // Determine routing: Team Manager vs Dept Head
            $teamManagerId = null;
            $deptManagerId = null;
            $initialStatus = 'PENDING_LEAD';

            // Check if user has a team lead (and user is not the team lead themselves)
            $team = $user->team_id ? \App\Models\Team::find($user->team_id) : null;
            if ($team && $team->leader_id && $team->leader_id !== $user->id) {
                $teamManagerId = $team->leader_id;
            } elseif ($user->manager_id && $user->manager_id !== $user->id) {
                $teamManagerId = $user->manager_id;
            }

            // Check department head
            $department = $user->department_id ? \App\Models\Department::find($user->department_id) : null;
            if ($department && $department->head_user_id && $department->head_user_id !== $user->id) {
                $deptManagerId = $department->head_user_id;
            }

            // If user has no team lead, or user is already a team manager/project manager, route directly to Dept Head
            if (!$teamManagerId || $user->isTeamManager() || $user->isProjectManager()) {
                $initialStatus = 'FORWARDED_TO_DEPT';
            }

            $leave = LeaveRequest::create([
                'user_id' => $user->id,
                'organization_id' => $orgId,
                'leave_type_id' => $defaultLeaveType->id,
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
                'total_days' => $days,
                'is_half_day' => !empty($validated['is_half_day']),
                'emergency_contact' => $validated['emergency_contact'] ?? null,
                'reason' => $validated['reason'],
                'team_manager_id' => $teamManagerId,
                'dept_manager_id' => $deptManagerId,
                'current_approver_id' => ($initialStatus === 'PENDING_LEAD') ? $teamManagerId : $deptManagerId,
                'status' => $initialStatus,
            ]);

            // Notify approver
            $notifyUserId = ($initialStatus === 'PENDING_LEAD') ? $teamManagerId : $deptManagerId;
            if ($notifyUserId) {
                \App\Models\Notification::create([
                    'user_id' => $notifyUserId,
                    'title' => 'New Leave Request: ' . $user->name,
                    'message' => $user->name . ' requested ' . $days . ' day(s) of leave (' . $validated['start_date'] . ' to ' . $validated['end_date'] . ').',
                    'type' => 'leave',
                    'action_url' => route('leaves.index'),
                ]);
                $approverUser = \App\Models\User::find($notifyUserId);
                \App\Services\HrmsMailService::sendLeaveRequestedAlert($leave, $user, $approverUser);
            } else {
                \App\Services\HrmsMailService::sendLeaveRequestedAlert($leave, $user);
            }

            $msg = ($initialStatus === 'PENDING_LEAD')
                ? 'Leave request submitted! Sent to your Team Manager for endorsement.'
                : 'Leave request submitted! Routed directly to your Department Head for approval.';

            return back()->with('success', $msg);
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', 'Could not submit leave request: ' . $e->getMessage());
        }
    }

    public function forwardToDeptHead(Request $request, $id)
    {
        $user = Auth::user();
        if (!$user->canForwardLeave()) {
            return back()->with('error', 'Unauthorized. You do not have permission to forward leave requests.');
        }

        try {
            $leave = LeaveRequest::with('user.department')->findOrFail($id);

            // Dept Head determination
            $deptHeadId = $leave->user->department->head_user_id ?? $leave->dept_manager_id;
            $notes = $request->input('forward_notes', 'Recommended and forwarded to Department Head for final approval.');

            $leave->update([
                'status' => 'FORWARDED_TO_DEPT',
                'forwarded_by' => $user->id,
                'forwarded_at' => now(),
                'forward_notes' => $notes,
                'dept_manager_id' => $deptHeadId,
                'current_approver_id' => $deptHeadId,
            ]);

            // Notify Department Head
            if ($deptHeadId) {
                \App\Models\Notification::create([
                    'user_id' => $deptHeadId,
                    'title' => 'Leave Request Endorsed: ' . $leave->user->name,
                    'message' => $user->name . ' (Team Lead) endorsed leave request for ' . $leave->user->name . '. Ready for your final approval.',
                    'type' => 'leave',
                    'action_url' => route('leaves.index'),
                ]);
            }

            // Notify Employee
            \App\Models\Notification::create([
                'user_id' => $leave->user_id,
                'title' => 'Leave Request Endorsed',
                'message' => 'Your leave request has been endorsed by your Team Manager (' . $user->name . ') and forwarded to Department Head.',
                'type' => 'leave',
                'action_url' => route('leaves.index'),
            ]);

            return back()->with('success', 'Leave request endorsed and forwarded to Department Head successfully!');
        } catch (\Throwable $e) {
            return back()->with('error', 'Could not forward leave request: ' . $e->getMessage());
        }
    }

    public function approve(Request $request, $id)
    {
        $user = Auth::user();
        if (!$user->canDirectlyApproveLeave() && !$user->canApproveLeaves()) {
            return back()->with('error', 'Unauthorized. You do not have permission to approve leaves.');
        }

        try {
            $leave = LeaveRequest::with('user')->findOrFail($id);

            $leave->update([
                'status' => 'APPROVED',
                'approved_by' => $user->id,
                'approved_at' => now(),
                'current_approver_id' => null,
            ]);

            // Notify Employee
            \App\Models\Notification::create([
                'user_id' => $leave->user_id,
                'title' => 'Leave Request Approved',
                'message' => 'Your leave request for ' . $leave->total_days . ' day(s) has been officially approved by ' . $user->name . '.',
                'type' => 'leave',
                'action_url' => route('leaves.index'),
            ]);

            \App\Services\HrmsMailService::sendLeaveDecisionAlert($leave, $leave->user, true, $user);

            return back()->with('success', 'Leave request approved successfully!');
        } catch (\Throwable $e) {
            return back()->with('error', 'Could not approve leave request: ' . $e->getMessage());
        }
    }

    public function reject(Request $request, $id)
    {
        $user = Auth::user();
        if (!$user->canApproveLeaves()) {
            return back()->with('error', 'Unauthorized. You do not have permission to reject leaves.');
        }

        try {
            $leave = LeaveRequest::with('user')->findOrFail($id);
            $reason = $request->input('rejection_reason', 'Declined by management');

            $leave->update([
                'status' => 'REJECTED',
                'approved_by' => $user->id,
                'rejection_reason' => $reason,
                'current_approver_id' => null,
            ]);

            // Notify Employee
            \App\Models\Notification::create([
                'user_id' => $leave->user_id,
                'title' => 'Leave Request Declined',
                'message' => 'Your leave request was declined by ' . $user->name . '. Reason: ' . $reason,
                'type' => 'leave',
                'action_url' => route('leaves.index'),
            ]);

            \App\Services\HrmsMailService::sendLeaveDecisionAlert($leave, $leave->user, false, $user, $reason);

            return back()->with('success', 'Leave request rejected.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Could not reject leave request: ' . $e->getMessage());
        }
    }

    public function revoke(Request $request, $id)
    {
        $user = Auth::user();
        if (!$user->canRevokeLeave()) {
            return back()->with('error', 'Unauthorized. Only Super Admin and HR can revoke approved leaves.');
        }

        $validated = $request->validate([
            'revocation_reason' => 'required|string|min:3',
        ]);

        try {
            $leave = LeaveRequest::with('user')->findOrFail($id);

            $leave->update([
                'status' => 'REVOKED',
                'revoked_by' => $user->id,
                'revoked_at' => now(),
                'revocation_reason' => $validated['revocation_reason'],
                'current_approver_id' => null,
            ]);

            // Notify Employee
            \App\Models\Notification::create([
                'user_id' => $leave->user_id,
                'title' => 'Leave Approval Revoked by HR',
                'message' => 'Your previously approved leave has been revoked by ' . $user->name . ' (HR/Admin). Quota has been restored. Reason: ' . $validated['revocation_reason'],
                'type' => 'leave',
                'action_url' => route('leaves.index'),
            ]);

            return back()->with('success', 'Leave request has been revoked and employee quota has been restored.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Could not revoke leave request: ' . $e->getMessage());
        }
    }

    private function ensureDefaultLeaveTypesExist()
    {
        $primary = LeaveType::where('name', 'Leaves')->first();

        if (!$primary) {
            $first = LeaveType::first();
            if ($first) {
                $first->update([
                    'name' => 'Leaves',
                    'days_allocated' => 12,
                    'color' => '#6366f1',
                    'is_paid' => true,
                ]);
                $primary = $first;
            } else {
                $orgId = Organization::first()?->id;
                $primary = LeaveType::create([
                    'organization_id' => $orgId,
                    'name' => 'Leaves',
                    'days_allocated' => 12,
                    'color' => '#6366f1',
                    'is_paid' => true,
                ]);
            }
        }

        // Clean up any extra leave types if they somehow exist
        if (LeaveType::count() > 1) {
            LeaveRequest::where('leave_type_id', '!=', $primary->id)->update(['leave_type_id' => $primary->id]);
            LeaveType::where('id', '!=', $primary->id)->delete();
        }

        return $primary;
    }
}
