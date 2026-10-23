<?php

namespace App\Services\Ai;

use App\Models\AdvanceSalary;
use App\Models\Attendance;
use App\Models\Department;
use App\Models\Designation;
use App\Models\LeaveRequest;
use App\Models\Organization;
use App\Models\Payroll;
use App\Models\PayrollRun;
use App\Models\Payslip;
use App\Models\Project;
use App\Models\Role;
use App\Models\SalesTarget;
use App\Models\SalesTeam;
use App\Models\Shift;
use App\Models\SystemSetting;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;

class HrmsScanner
{
    /**
     * Scan employees directory with rich filtering
     */
    public function scan_employees(array $args = []): array
    {
        $query = User::excludeDevs()->with(['organization', 'department', 'designation', 'role', 'shift']);

        if (!empty($args['search'])) {
            $term = trim($args['search']);
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                  ->orWhere('email', 'like', "%{$term}%")
                  ->orWhere('phone', 'like', "%{$term}%");
            });
        }

        if (!empty($args['department'])) {
            $dept = trim($args['department']);
            $query->whereHas('department', function ($q) use ($dept) {
                $q->where('name', 'like', "%{$dept}%");
            });
        }

        if (!empty($args['company'])) {
            $comp = trim($args['company']);
            $query->whereHas('organization', function ($q) use ($comp) {
                $q->where('name', 'like', "%{$comp}%");
            });
        }

        if (isset($args['is_active'])) {
            $query->where('is_active', (bool)$args['is_active']);
        }

        $limit = min((int)($args['limit'] ?? 15), 50);
        $totalCount = (clone $query)->count();
        $users = $query->take($limit)->get();

        $data = $users->map(function ($u) {
            return [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'phone' => $u->phone,
                'company' => $u->organization->name ?? 'N/A',
                'department' => $u->department->name ?? 'N/A',
                'designation' => $u->designation->title ?? 'Team Member',
                'role' => $u->role->name ?? 'EMPLOYEE',
                'base_salary' => $u->base_salary ? 'PKR ' . number_format($u->base_salary, 0) : 'Not Specified',
                'shift' => $u->shift->name ?? 'Default Shift',
                'date_of_joining' => $u->date_of_joining ? $u->date_of_joining->format('Y-m-d') : null,
                'is_active' => (bool)$u->is_active,
            ];
        });

        return [
            'total_matching' => $totalCount,
            'returned_count' => count($data),
            'employees' => $data,
        ];
    }

    /**
     * Scan attendance logs, punctuality, and overtime
     */
    public function scan_attendance(array $args = []): array
    {
        $query = Attendance::with(['user.department', 'user.organization']);

        $dateFrom = $args['date_from'] ?? ($args['date'] ?? now()->toDateString());
        $dateTo = $args['date_to'] ?? ($args['date'] ?? now()->toDateString());

        $query->whereBetween('date', [$dateFrom, $dateTo]);

        if (!empty($args['status'])) {
            $status = strtoupper(trim($args['status']));
            $query->where('status', $status);
        }

        if (!empty($args['department'])) {
            $dept = trim($args['department']);
            $query->whereHas('user.department', function ($q) use ($dept) {
                $q->where('name', 'like', "%{$dept}%");
            });
        }

        if (!empty($args['user_name'])) {
            $name = trim($args['user_name']);
            $query->whereHas('user', function ($q) use ($name) {
                $q->where('name', 'like', "%{$name}%");
            });
        }

        $presentCount = Attendance::whereBetween('date', [$dateFrom, $dateTo])->whereIn('status', ['PRESENT', 'LATE'])->count();
        $lateCount = Attendance::whereBetween('date', [$dateFrom, $dateTo])->where('status', 'LATE')->count();
        $absentCount = Attendance::whereBetween('date', [$dateFrom, $dateTo])->where('status', 'ABSENT')->count();
        $halfDayCount = Attendance::whereBetween('date', [$dateFrom, $dateTo])->where('status', 'HALF_DAY')->count();
        $totalOvertime = Attendance::whereBetween('date', [$dateFrom, $dateTo])->sum('overtime_minutes');

        $limit = min((int)($args['limit'] ?? 20), 50);
        $records = $query->orderBy('date', 'desc')->take($limit)->get();

        $logs = $records->map(function ($a) {
            return [
                'employee' => $a->user->name ?? 'Unknown',
                'department' => $a->user->department->name ?? 'General',
                'company' => $a->user->organization->name ?? 'General',
                'date' => $a->date,
                'clock_in' => $a->clock_in ? Carbon::parse($a->clock_in)->format('h:i A') : '--',
                'clock_out' => $a->clock_out ? Carbon::parse($a->clock_out)->format('h:i A') : '--',
                'status' => $a->status,
                'total_worked' => $a->total_minutes ? (floor($a->total_minutes / 60) . 'h ' . ($a->total_minutes % 60) . 'm') : '--',
                'overtime_minutes' => (int)$a->overtime_minutes,
            ];
        });

        return [
            'date_range' => "{$dateFrom} to {$dateTo}",
            'summary' => [
                'present_count' => $presentCount,
                'late_count' => $lateCount,
                'absent_count' => $absentCount,
                'half_day_count' => $halfDayCount,
                'total_overtime_minutes' => $totalOvertime,
            ],
            'records' => $logs,
        ];
    }

    /**
     * Scan multi-company payroll, disbursements, and advance salary requests
     */
    public function scan_payroll(array $args = []): array
    {
        $payrolls = Payroll::with('organization')->get();
        $totalTiersBudget = $payrolls->sum('base_salary');

        // Payslips query
        $payslipsQuery = Payslip::with(['user.department', 'user.organization']);

        if (!empty($args['month'])) {
            $payslipsQuery->whereMonth('created_at', (int)$args['month']);
        }
        if (!empty($args['year'])) {
            $payslipsQuery->whereYear('created_at', (int)$args['year']);
        }
        if (!empty($args['company'])) {
            $comp = trim($args['company']);
            $payslipsQuery->whereHas('user.organization', function ($q) use ($comp) {
                $q->where('name', 'like', "%{$comp}%");
            });
        }

        $payslipsCount = (clone $payslipsQuery)->count();
        $totalNetDisbursed = (clone $payslipsQuery)->sum('net_pay');
        $totalTaxDeducted = (clone $payslipsQuery)->sum('tax');
        $totalLateDeductions = Schema::hasColumn('payslips', 'late_deduction') ? (clone $payslipsQuery)->sum('late_deduction') : 0;

        $recentPayslips = $payslipsQuery->orderBy('created_at', 'desc')->take(10)->get()->map(function ($p) {
            return [
                'employee' => $p->user->name ?? 'Employee',
                'company' => $p->user->organization->name ?? 'Company',
                'month_year' => $p->created_at ? $p->created_at->format('F Y') : 'N/A',
                'base_salary' => 'PKR ' . number_format($p->base_salary, 0),
                'allowances' => 'PKR ' . number_format($p->allowances, 0),
                'deductions' => 'PKR ' . number_format($p->deductions, 0),
                'net_pay' => 'PKR ' . number_format($p->net_pay, 0),
                'status' => $p->status ?? 'PAID',
            ];
        });

        // Advance Salaries
        $advanceSalaries = AdvanceSalary::with('user')->orderBy('created_at', 'desc')->take(10)->get()->map(function ($adv) {
            return [
                'employee' => $adv->user->name ?? 'Unknown',
                'amount' => 'PKR ' . number_format($adv->amount, 0),
                'status' => $adv->status,
                'reason' => $adv->reason,
                'date' => $adv->created_at->format('Y-m-d'),
            ];
        });

        $pendingAdvanceCount = AdvanceSalary::where('status', 'PENDING')->count();
        $pendingAdvanceTotal = AdvanceSalary::where('status', 'PENDING')->sum('amount');

        return [
            'total_payroll_tiers' => count($payrolls),
            'monthly_base_budget' => 'PKR ' . number_format($totalTiersBudget, 0),
            'payslips_generated_count' => $payslipsCount,
            'total_net_disbursed' => 'PKR ' . number_format($totalNetDisbursed, 0),
            'total_tax_withheld' => 'PKR ' . number_format($totalTaxDeducted, 0),
            'total_late_penalties' => 'PKR ' . number_format($totalLateDeductions, 0),
            'pending_advance_salaries' => [
                'count' => $pendingAdvanceCount,
                'total_amount' => 'PKR ' . number_format($pendingAdvanceTotal, 0),
            ],
            'recent_payslips' => $recentPayslips,
            'recent_advance_salary_requests' => $advanceSalaries,
        ];
    }

    /**
     * Scan leave applications, quotas, and pending approvals
     */
    public function scan_leaves(array $args = []): array
    {
        $query = LeaveRequest::with(['user.department', 'leaveType']);

        if (!empty($args['status'])) {
            $query->where('status', strtoupper(trim($args['status'])));
        }

        if (!empty($args['employee'])) {
            $name = trim($args['employee']);
            $query->whereHas('user', function ($q) use ($name) {
                $q->where('name', 'like', "%{$name}%");
            });
        }

        $pendingCount = LeaveRequest::where('status', 'PENDING')->count();
        $approvedCount = LeaveRequest::where('status', 'APPROVED')->count();
        $rejectedCount = LeaveRequest::where('status', 'REJECTED')->count();

        $limit = min((int)($args['limit'] ?? 15), 50);
        $leaves = $query->orderBy('created_at', 'desc')->take($limit)->get();

        $records = $leaves->map(function ($l) {
            return [
                'id' => $l->id,
                'employee' => $l->user->name ?? 'Employee',
                'department' => $l->user->department->name ?? 'General',
                'leave_type' => $l->leaveType->name ?? 'Standard Leave',
                'days' => (float)$l->total_days,
                'start_date' => Carbon::parse($l->start_date)->format('Y-m-d'),
                'end_date' => Carbon::parse($l->end_date)->format('Y-m-d'),
                'reason' => $l->reason,
                'status' => $l->status,
                'applied_on' => $l->created_at ? $l->created_at->format('Y-m-d') : null,
            ];
        });

        return [
            'summary' => [
                'pending_approval' => $pendingCount,
                'approved_leaves' => $approvedCount,
                'rejected_leaves' => $rejectedCount,
            ],
            'leaves' => $records,
        ];
    }

    /**
     * Scan software house client projects and Kanban tasks
     */
    public function scan_projects_tasks(array $args = []): array
    {
        $projects = Project::withCount(['tasks', 'members'])->get();

        $tasksQuery = Task::with(['project', 'assignee']);

        if (!empty($args['status'])) {
            $tasksQuery->where('status', strtoupper(trim($args['status'])));
        }

        if (!empty($args['assigned_to'])) {
            $name = trim($args['assigned_to']);
            $tasksQuery->whereHas('assignee', function ($q) use ($name) {
                $q->where('name', 'like', "%{$name}%");
            });
        }

        $completedTasks = Task::where('status', 'COMPLETED')->count();
        $pendingTasks = Task::where('status', '!=', 'COMPLETED')->count();
        $overdueTasks = Task::where('status', '!=', 'COMPLETED')
            ->whereNotNull('due_date')
            ->where('due_date', '<', now()->toDateString())
            ->count();

        $limit = min((int)($args['limit'] ?? 15), 50);
        $recentTasks = $tasksQuery->orderBy('created_at', 'desc')->take($limit)->get()->map(function ($t) {
            return [
                'task' => $t->title ?? 'Task',
                'project' => $t->project->name ?? 'Project',
                'assigned_to' => $t->assignee->name ?? 'Unassigned',
                'priority' => $t->priority ?? 'NORMAL',
                'status' => $t->status,
                'due_date' => $t->due_date ? Carbon::parse($t->due_date)->format('Y-m-d') : 'No due date',
            ];
        });

        return [
            'active_projects_count' => count($projects),
            'tasks_metrics' => [
                'completed_tasks' => $completedTasks,
                'pending_tasks' => $pendingTasks,
                'overdue_tasks' => $overdueTasks,
            ],
            'projects' => $projects->map(function ($p) {
                return [
                    'id' => $p->id,
                    'name' => $p->name ?? 'Project',
                    'client' => $p->client_name ?? 'Internal',
                    'type' => $p->type ?? 'Web/Design',
                    'status' => $p->status ?? 'ACTIVE',
                    'tasks_count' => $p->tasks_count,
                    'team_members_count' => $p->members_count,
                ];
            }),
            'recent_tasks' => $recentTasks,
        ];
    }

    /**
     * Scan sales team targets and achievement KPIs
     */
    public function scan_sales_kpis(array $args = []): array
    {
        if (!Schema::hasTable('sales_teams') || !Schema::hasTable('sales_targets')) {
            return [
                'status' => 'Sales module is not provisioned or configured in this database environment.',
                'sales_teams_count' => 0,
                'metrics' => [
                    'total_target_amount' => 'PKR 0',
                    'total_achieved_amount' => 'PKR 0',
                    'total_commission_earned' => 'PKR 0',
                    'achievement_rate' => 'N/A',
                ],
                'teams' => [],
                'recent_targets' => [],
            ];
        }

        $teams = SalesTeam::with(['organization'])->withCount('targets')->get();
        $targets = SalesTarget::with(['user', 'salesTeam'])->orderBy('created_at', 'desc')->take(10)->get();

        $totalTargetAmount = SalesTarget::sum('target_amount');
        $totalAchievedAmount = SalesTarget::sum('achieved_amount');
        $totalCommission = SalesTarget::sum('commission_earned');

        return [
            'sales_teams_count' => count($teams),
            'metrics' => [
                'total_target_amount' => 'PKR ' . number_format($totalTargetAmount, 0),
                'total_achieved_amount' => 'PKR ' . number_format($totalAchievedAmount, 0),
                'total_commission_earned' => 'PKR ' . number_format($totalCommission, 0),
                'achievement_rate' => $totalTargetAmount > 0 ? round(($totalAchievedAmount / $totalTargetAmount) * 100, 1) . '%' : '0%',
            ],
            'teams' => $teams->map(function ($t) {
                return [
                    'team_name' => $t->name,
                    'company' => $t->organization->name ?? 'AxeCorp',
                    'monthly_target' => 'PKR ' . number_format($t->monthly_target ?? 0, 0),
                    'active_targets_count' => $t->targets_count,
                ];
            }),
            'recent_targets' => $targets->map(function ($tgt) {
                return [
                    'sales_executive' => $tgt->user->name ?? 'Sales Exec',
                    'team' => $tgt->salesTeam->name ?? 'Sales Team',
                    'period' => $tgt->month . '/' . $tgt->year,
                    'target' => 'PKR ' . number_format($tgt->target_amount ?? 0, 0),
                    'achieved' => 'PKR ' . number_format($tgt->achieved_amount ?? 0, 0),
                    'commission' => 'PKR ' . number_format($tgt->commission_earned ?? 0, 0),
                ];
            }),
        ];
    }

    /**
     * Scan global system configuration, company entities, and threshold policies
     */
    public function scan_system_configs(array $args = []): array
    {
        $companies = Organization::withCount('users')->get();
        $departments = Department::withCount('users')->get();
        $shifts = Shift::all();

        return [
            'companies' => $companies->map(function ($c) {
                return [
                    'name' => $c->name,
                    'industry' => $c->industry,
                    'currency' => $c->currency ?? 'PKR',
                    'tax_rate' => ($c->payroll_tax_rate ?? 12) . '%',
                    'staff_count' => $c->users_count,
                ];
            }),
            'departments_count' => count($departments),
            'shifts' => $shifts->map(function ($s) {
                return [
                    'name' => $s->name,
                    'start_time' => $s->start_time,
                    'end_time' => $s->end_time,
                    'grace_minutes' => $s->grace_period_minutes ?? 15,
                ];
            }),
            'attendance_rules' => [
                'standard_shift_hours' => 8,
                'late_arrival_grace_threshold' => '09:15 AM (15 minutes grace)',
                'overtime_threshold' => '> 480 minutes (8 hours)',
                'late_penalty_fee' => 'PKR ' . SystemSetting::get('payroll_late_deduction_fee', '500'),
            ],
            'leave_quota_rule' => 'Consolidated 12 Days annual quota system',
        ];
    }

    /**
     * Universal full-text search across all HRMS models
     */
    public function universal_hrms_scan(array $args = []): array
    {
        $query = trim($args['query'] ?? '');
        if (empty($query)) {
            return ['results' => []];
        }

        $users = User::excludeDevs()->where(function ($q) use ($query) {
            $q->where('name', 'like', "%{$query}%")
              ->orWhere('email', 'like', "%{$query}%")
              ->orWhere('phone', 'like', "%{$query}%");
        })->take(5)->get(['id', 'name', 'email', 'phone', 'base_salary']);

        $orgs = Organization::where('name', 'like', "%{$query}%")
                            ->orWhere('industry', 'like', "%{$query}%")
                            ->take(5)->get(['id', 'name', 'currency', 'industry']);

        $tasks = Task::where('title', 'like', "%{$query}%")
                     ->take(5)->get(['id', 'title', 'status', 'priority']);

        return [
            'query' => $query,
            'matches' => [
                'employees' => $users,
                'companies' => $orgs,
                'tasks' => $tasks,
            ],
        ];
    }
}
