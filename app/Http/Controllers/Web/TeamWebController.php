<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Department;
use App\Models\LeaveRequest;
use App\Models\Organization;
use App\Models\Role;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TeamWebController extends Controller
{
    /**
     * Display Teams dashboard, Team Leader portal, or Employee squad view.
     */
    public function index(Request $request)
    {
        $currentUser = Auth::user();
        $canManageAll = $currentUser->canManageTeams();
        $isLeader = $currentUser->isTeamLeader();
        $today = date('Y-m-d');
        $attendanceDate = $request->input('attendance_date', $today);

        // View mode: 'admin' (all teams), 'leader' (my led squad), or 'employee' (my team)
        $viewMode = 'employee';
        if ($canManageAll && (!$request->has('view') || $request->input('view') === 'admin')) {
            $viewMode = 'admin';
        } elseif ($isLeader || ($canManageAll && $request->input('view') === 'leader')) {
            $viewMode = 'leader';
        }

        // 1. Data for Admin Management View
        $teams = collect();
        $totalTeams = 0;
        $teamsWithLeader = 0;
        $totalAssignedMembers = 0;
        $unassignedMembersCount = 0;
        $departments = collect();
        $organizations = collect();
        $eligibleLeaders = collect();
        $availableEmployees = collect();

        if ($canManageAll) {
            $query = Team::with(['organization', 'department', 'leader.designation', 'members.designation', 'members.department']);

            if ($currentUser->isOrgAdmin() && !$currentUser->isSuperAdmin() && $currentUser->organization_id) {
                $query->where('organization_id', $currentUser->organization_id);
            }

            if ($request->filled('search')) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%")
                      ->orWhereHas('leader', function ($lq) use ($search) {
                          $lq->where('name', 'like', "%{$search}%");
                      });
                });
            }

            if ($request->filled('organization_id')) {
                $query->where('organization_id', $request->input('organization_id'));
            }

            if ($request->filled('department_id')) {
                $query->where('department_id', $request->input('department_id'));
            }

            $teams = $query->orderBy('name', 'asc')->get();

            // Stats
            $totalTeams = $teams->count();
            $teamsWithLeader = $teams->filter(fn($t) => !empty($t->leader_id))->count();
            $totalAssignedMembers = User::excludeDevs()->whereNotNull('team_id')->count();
            $unassignedMembersCount = User::excludeDevs()->where('is_active', true)->whereNull('team_id')->count();

            $departments = Department::orderBy('name')->get();
            $organizations = Organization::where('is_active', true)->orderBy('name')->get();

            // Eligible leaders: Users that can lead (Managers, Leads, or active employees)
            $eligibleLeaders = User::excludeDevs()->where('is_active', true)
                ->with(['designation', 'role'])
                ->orderBy('name')
                ->get();

        }

        // Available workforce for assignment (accessible to both Admins and Team Leaders)
        if ($canManageAll || $isLeader) {
            $empQuery = User::excludeDevs()->where('is_active', true)->with(['designation', 'department', 'team']);
            if (!$currentUser->isSuperAdmin() && $currentUser->organization_id) {
                $empQuery->where('organization_id', $currentUser->organization_id);
            }
            $availableEmployees = $empQuery->orderBy('name')->get();
        }

        // 2. Data for Team Leader Portal
        $ledTeams = collect();
        $selectedTeam = null;
        $teamAttendance = collect();
        $attendanceStats = [
            'total' => 0,
            'present' => 0,
            'late' => 0,
            'on_leave' => 0,
            'absent' => 0,
        ];
        $teamLeaveRequests = collect();

        if ($isLeader || $canManageAll) {
            $ledQuery = Team::with(['organization', 'department', 'leader.designation', 'members.designation', 'members.department']);
            if (!$canManageAll) {
                $ledQuery->where('leader_id', $currentUser->id);
            }
            $ledTeams = $ledQuery->get();

            if ($ledTeams->isNotEmpty()) {
                $selectedTeamId = $request->input('team_id', $ledTeams->first()->id);
                $selectedTeam = $ledTeams->firstWhere('id', $selectedTeamId) ?: $ledTeams->first();

                if ($selectedTeam) {
                    $memberIds = $selectedTeam->members->pluck('id')->toArray();
                    $attendanceStats['total'] = count($memberIds);

                    // Fetch Attendance records for selected date
                    $attendances = Attendance::with(['user.designation', 'breaks', 'activeBreak'])
                        ->whereIn('user_id', $memberIds)
                        ->whereDate('date', $attendanceDate)
                        ->get()
                        ->keyBy('user_id');

                    // Map attendance record per squad member
                    $teamAttendance = $selectedTeam->members->map(function ($member) use ($attendances) {
                        $att = $attendances->get($member->id);
                        return (object) [
                            'member' => $member,
                            'attendance' => $att,
                            'status' => $att ? $att->status : 'ABSENT',
                            'clock_in' => $att ? $att->clock_in : null,
                            'clock_out' => $att ? $att->clock_out : null,
                            'total_hours' => $att ? $att->total_hours : 0,
                            'break_minutes' => $att ? ($att->total_break_minutes ?? 0) : 0,
                            'is_on_break' => $att && $att->activeBreak ? true : false,
                        ];
                    });

                    // Stats calculation
                    $presentCount = $attendances->whereIn('status', ['PRESENT', 'LATE', 'HALF_DAY'])->count();
                    $lateCount = $attendances->where('status', 'LATE')->count();
                    $onLeaveCount = LeaveRequest::whereIn('user_id', $memberIds)
                        ->where('status', 'APPROVED')
                        ->whereDate('start_date', '<=', $attendanceDate)
                        ->whereDate('end_date', '>=', $attendanceDate)
                        ->count();

                    $attendanceStats['present'] = $presentCount;
                    $attendanceStats['late'] = $lateCount;
                    $attendanceStats['on_leave'] = $onLeaveCount;
                    $attendanceStats['absent'] = max(0, $attendanceStats['total'] - $presentCount - $onLeaveCount);

                    // Team leave requests
                    $teamLeaveRequests = LeaveRequest::with(['user.designation', 'leaveType'])
                        ->whereIn('user_id', $memberIds)
                        ->orderBy('created_at', 'desc')
                        ->take(10)
                        ->get();
                }
            }
        }

        // 3. Data for Regular Employee View
        $myTeam = null;
        if ($currentUser->team_id) {
            $myTeam = Team::with(['leader.designation', 'department', 'organization', 'members.designation'])->find($currentUser->team_id);
        }

        return view('teams.index', compact(
            'viewMode',
            'canManageAll',
            'isLeader',
            'today',
            'attendanceDate',
            'teams',
            'totalTeams',
            'teamsWithLeader',
            'totalAssignedMembers',
            'unassignedMembersCount',
            'departments',
            'organizations',
            'eligibleLeaders',
            'availableEmployees',
            'ledTeams',
            'selectedTeam',
            'teamAttendance',
            'attendanceStats',
            'teamLeaveRequests',
            'myTeam'
        ));
    }

    /**
     * Store a new Team.
     */
    public function store(Request $request)
    {
        $currentUser = Auth::user();
        if (!$currentUser->canManageTeams()) {
            return back()->with('error', 'Unauthorized. Only administrators and HR can create teams.');
        }

        if ($request->has('leader_id') && empty(trim((string)$request->input('leader_id')))) {
            $request->merge(['leader_id' => null]);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'organization_id' => 'required|exists:organizations,id',
            'department_id' => 'required|exists:departments,id',
            'leader_id' => 'nullable|exists:users,id',
            'description' => 'nullable|string|max:1000',
            'member_ids' => 'nullable|array',
            'member_ids.*' => 'exists:users,id',
        ]);

        try {
            DB::transaction(function () use ($validated, $request) {
                $leaderId = !empty($validated['leader_id']) ? $validated['leader_id'] : null;

                $team = Team::create([
                    'name' => $validated['name'],
                    'organization_id' => $validated['organization_id'],
                    'department_id' => $validated['department_id'],
                    'leader_id' => $leaderId,
                    'description' => $validated['description'] ?? null,
                ]);

                // If a leader is designated, elevate role to TEAM_MANAGER if currently an EMPLOYEE
                if ($leaderId) {
                    $leader = User::find($leaderId);
                    if ($leader) {
                        $leader->update(['team_id' => $team->id]);
                        if ($leader->role && $leader->role->name === 'EMPLOYEE') {
                            $teamLeadRole = Role::whereIn('name', ['TEAM_LEAD', 'TEAM_MANAGER'])->first();
                            if ($teamLeadRole) {
                                $leader->update(['role_id' => $teamLeadRole->id]);
                            }
                        }
                    }
                }

                // Assign members
                if (!empty($validated['member_ids'])) {
                    User::whereIn('id', $validated['member_ids'])->update(['team_id' => $team->id]);
                }
            });

            return redirect()->route('teams.index')->with('success', "Team '{$validated['name']}' created successfully!");
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', 'Failed to create team: ' . $e->getMessage());
        }
    }

    /**
     * Update an existing Team.
     */
    public function update(Request $request, $id)
    {
        $currentUser = Auth::user();
        $team = Team::findOrFail($id);

        if (!$currentUser->canManageTeams()) {
            return back()->with('error', 'Unauthorized. Only administrators and HR can edit team properties.');
        }

        if ($request->has('leader_id') && empty(trim((string)$request->input('leader_id')))) {
            $request->merge(['leader_id' => null]);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'organization_id' => 'required|exists:organizations,id',
            'department_id' => 'required|exists:departments,id',
            'leader_id' => 'nullable|exists:users,id',
            'description' => 'nullable|string|max:1000',
        ]);

        try {
            DB::transaction(function () use ($team, $validated) {
                $oldLeaderId = $team->leader_id;
                $newLeaderId = !empty($validated['leader_id']) ? $validated['leader_id'] : null;

                $team->update([
                    'name' => $validated['name'],
                    'organization_id' => $validated['organization_id'],
                    'department_id' => $validated['department_id'],
                    'leader_id' => $newLeaderId,
                    'description' => $validated['description'] ?? null,
                ]);

                // If leader changed
                if ($newLeaderId && $newLeaderId !== $oldLeaderId) {
                    $newLeader = User::find($newLeaderId);
                    if ($newLeader) {
                        $newLeader->update(['team_id' => $team->id]);
                        if ($newLeader->role && $newLeader->role->name === 'EMPLOYEE') {
                            $teamLeadRole = Role::whereIn('name', ['TEAM_LEAD', 'TEAM_MANAGER'])->first();
                            if ($teamLeadRole) {
                                $newLeader->update(['role_id' => $teamLeadRole->id]);
                            }
                        }
                    }
                }
            });

            return redirect()->route('teams.index')->with('success', "Team '{$team->name}' updated successfully!");
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', 'Failed to update team: ' . $e->getMessage());
        }
    }

    /**
     * Delete a team and safely unbind members.
     */
    public function destroy($id)
    {
        $currentUser = Auth::user();
        if (!$currentUser->canManageTeams()) {
            return back()->with('error', 'Unauthorized. Only administrators and HR can delete teams.');
        }

        $team = Team::findOrFail($id);

        try {
            DB::transaction(function () use ($team) {
                // Safely unbind members without deleting accounts
                User::where('team_id', $team->id)->update(['team_id' => null]);
                $team->delete();
            });

            return redirect()->route('teams.index')->with('success', "Team '{$team->name}' deleted and squad members unassigned.");
        } catch (\Throwable $e) {
            return back()->with('error', 'Failed to delete team: ' . $e->getMessage());
        }
    }

    /**
     * Assign employees into a team.
     */
    public function addMembers(Request $request, $id)
    {
        $currentUser = Auth::user();
        $team = Team::findOrFail($id);

        if (!$currentUser->canManageTeams() && $currentUser->id !== $team->leader_id) {
            return back()->with('error', 'Unauthorized. Only administrators, HR, or the designated Team Leader can add members.');
        }

        $validated = $request->validate([
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id',
        ]);

        try {
            User::whereIn('id', $validated['user_ids'])->update(['team_id' => $team->id]);
            return back()->with('success', count($validated['user_ids']) . " employee(s) added to {$team->name} squad!");
        } catch (\Throwable $e) {
            return back()->with('error', 'Failed to add squad members: ' . $e->getMessage());
        }
    }

    /**
     * Remove an employee from a team.
     */
    public function removeMember(Request $request, $id, $userId)
    {
        $currentUser = Auth::user();
        $team = Team::findOrFail($id);

        if (!$currentUser->canManageTeams() && $currentUser->id !== $team->leader_id) {
            return back()->with('error', 'Unauthorized to remove members from this team.');
        }

        $member = User::where('id', $userId)->where('team_id', $id)->firstOrFail();

        try {
            DB::transaction(function () use ($team, $member) {
                $member->update(['team_id' => null]);
                if ($team->leader_id === $member->id) {
                    $team->update(['leader_id' => null]);
                }
            });

            return back()->with('success', "{$member->name} removed from {$team->name} squad.");
        } catch (\Throwable $e) {
            return back()->with('error', 'Failed to remove member: ' . $e->getMessage());
        }
    }
}
