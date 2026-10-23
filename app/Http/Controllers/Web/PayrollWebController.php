<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AdvanceSalary;
use App\Models\Attendance;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Organization;
use App\Models\Payroll;
use App\Models\PayrollRun;
use App\Models\Payslip;
use App\Models\PayslipRequest;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class PayrollWebController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $isSuperAdmin = $user->isSuperAdmin() || $user->isDeveloperAdmin();
        $hasPayrollPin = $isSuperAdmin && $user->hasPayrollPin();

        // Lock vault by default when clicking on Payroll, coming from another section, or when explicitly locked
        $previousUrl = (string)url()->previous();
        $isInternalPayrollNav = (str_contains($previousUrl, '/payroll') || session('payroll_just_unlocked')) && !$request->has('lock');
        session()->forget('payroll_just_unlocked');

        if ($hasPayrollPin && (!$isInternalPayrollNav || $request->has('lock'))) {
            session()->forget('payroll_pin_verified');
        }

        $isPayrollLocked = $hasPayrollPin && !session('payroll_pin_verified');

        // STRICT SERVER-SIDE VAULT GATE: If locked, NEVER query or transmit sensitive payroll records!
        if ($isPayrollLocked) {
            return view('payroll.index', [
                'isPayrollLocked' => true,
                'hasPayrollPin' => true,
            ]);
        }

        $selectedOrgId = $request->input('organization_id');
        $activeTab = $request->input('tab', !$user->canManagePayroll() ? 'requests' : 'staff');

        $orgQuery = Organization::orderBy('name', 'asc');
        if ($user->isOrgAdmin() && !$user->isSuperAdmin() && $user->organization_id) {
            $orgQuery->where('id', $user->organization_id);
            $selectedOrgId = $user->organization_id;
        }
        $organizations = $orgQuery->get();
        $departments = Department::orderBy('name', 'asc')->get();

        // Scope payroll engines
        $payrollQuery = Payroll::with(['organization', 'employees', 'payrollManager'])
            ->withCount('employees');

        if (!$user->canManagePayroll()) {
            $payrolls = collect();
        } else {
            if (!empty($selectedOrgId)) {
                $payrollQuery->where('organization_id', $selectedOrgId);
            }
            $payrolls = $payrollQuery->orderBy('name', 'asc')->get();
        }

        // ==========================================
        // 1. STAFF MANAGEMENT & SINGLE-EMPLOYEE PAY
        // ==========================================
        $staffSearch = $request->input('staff_search');
        $staffDeptId = $request->input('staff_dept_id');
        $staffOrgId = $request->input('staff_org_id', $selectedOrgId);
        $currentMonth = date('Y-m');

        if ($user->canManagePayroll()) {
            $staffQuery = User::excludeDevs()->with(['organization', 'payroll', 'designation', 'department'])->whereNotNull('organization_id');
            if (!empty($staffOrgId)) {
                $staffQuery->where('organization_id', $staffOrgId);
            }
            if (!empty($staffDeptId)) {
                $staffQuery->where('department_id', $staffDeptId);
            }
            if (!empty($staffSearch)) {
                $staffQuery->where(function ($q) use ($staffSearch) {
                    $q->where('name', 'like', "%{$staffSearch}%")
                      ->orWhere('email', 'like', "%{$staffSearch}%");
                });
            }
            $employees = $staffQuery->orderBy('name', 'asc')->get();

            // Preload current month payment status, pending advances, and attendance lates
            $empIds = $employees->pluck('id');
            $monthPayslips = Payslip::with(['user.department', 'user.designation', 'user.organization', 'payrollRun.payroll.organization'])
                ->whereIn('user_id', $empIds)
                ->where(function ($q) use ($currentMonth) {
                    $q->where('payment_month', $currentMonth)
                      ->orWhere('pay_date', 'like', "{$currentMonth}%")
                      ->orWhere('created_at', 'like', "{$currentMonth}%");
                })->get()->keyBy('user_id');

            $pendingAdvances = AdvanceSalary::whereIn('user_id', $empIds)
                ->whereIn('status', ['APPROVED', 'DISBURSED'])
                ->get()
                ->groupBy('user_id');

            $startOfCurrentMonth = date('Y-m-01');
            $endOfCurrentMonth = date('Y-m-t');
            $latesCount = Attendance::whereIn('user_id', $empIds)
                ->whereBetween('date', [$startOfCurrentMonth, $endOfCurrentMonth])
                ->where('status', 'LATE')
                ->selectRaw('user_id, count(*) as count')
                ->groupBy('user_id')
                ->pluck('count', 'user_id');

            $latesPerDay = (int) SystemSetting::get('payroll_lates_per_day_deduction', 3);

            foreach ($employees as $emp) {
                $emp->current_month_payslip = $monthPayslips->get($emp->id);
                $emp->is_paid_this_month = (bool) $emp->current_month_payslip;
                $emp->pending_advance_amount = (float) ($pendingAdvances->has($emp->id) ? $pendingAdvances->get($emp->id)->sum('amount') : 0);
                $emp->current_month_lates = (int) ($latesCount->get($emp->id) ?? 0);

                $workingDays = (int) ($emp->payroll->default_working_days ?? 22);
                $workingDays = $workingDays > 0 ? $workingDays : 22;
                $dailyRate = $workingDays > 0 ? (($emp->base_salary ?? 0) / $workingDays) : 0;
                $deductedDays = ($latesPerDay > 0) ? (int) floor($emp->current_month_lates / $latesPerDay) : 0;
                $emp->suggested_late_deduction = round($deductedDays * $dailyRate, 2);
                $emp->suggested_late_days = $deductedDays;
            }

            $staffTotalCount = $employees->count();

            // Salary structure split: Bank Transfer vs. Cash in Hand
            $staffBankCommitment = (float) $employees->sum(fn ($e) => ($e->base_salary ?? 0) + ($e->fuel_allowance ?? 0) + ($e->special_allowance ?? 0));
            $staffCashCommitment = (float) $employees->sum(fn ($e) => ($e->cash_allowance ?? 0));
            $staffMonthlyCommitment = $staffBankCommitment + $staffCashCommitment;
            $staffTotalCommitment = $staffMonthlyCommitment;

            $staffPaidCount = $employees->where('is_paid_this_month', true)->count();
            $staffPaidBankAmount = (float) $employees->where('is_paid_this_month', true)->sum(fn ($e) => $e->current_month_payslip->net_pay ?? 0);
            $staffPaidCashAmount = (float) $employees->where('is_paid_this_month', true)->sum(fn ($e) => $e->cash_allowance ?? 0);
            $staffPaidAmount = $staffPaidBankAmount + $staffPaidCashAmount;
            $staffPaidTotalAmount = $staffPaidAmount;
            $staffPendingCount = $staffTotalCount - $staffPaidCount;

            // Advance Salaries registry
            $advQuery = AdvanceSalary::with(['user.organization', 'user.designation', 'approver'])
                ->whereHas('user', function ($q) {
                    $q->excludeDevs();
                });
            if (!empty($selectedOrgId)) {
                $advQuery->where('organization_id', $selectedOrgId);
            }
            $advanceSalaries = $advQuery->orderBy('created_at', 'desc')->get();

            // All employee payslip requests for HR / Super Admin
            $requestStatus = $request->input('request_status');
            $requestQuery = PayslipRequest::with(['user.department', 'user.designation', 'user.organization', 'reviewer', 'payslip'])
                ->whereHas('user', function ($q) {
                    $q->excludeDevs();
                });
            if (!empty($selectedOrgId)) {
                $requestQuery->where('organization_id', $selectedOrgId);
            }
            if (!empty($requestStatus)) {
                $requestQuery->where('status', $requestStatus);
            }
            $allPayslipRequests = $requestQuery->orderBy('created_at', 'desc')->paginate(15, ['*'], 'requests_page')->appends($request->query());
            $pendingPayslipRequestsCount = PayslipRequest::where('status', 'PENDING')->count();
            $myPayslipRequests = collect();
            $myApprovedRequestsCount = 0;
            $myPendingRequestsCount = 0;
        } else {
            $employees = collect();
            $staffTotalCount = 0;
            $staffBankCommitment = 0;
            $staffCashCommitment = 0;
            $staffMonthlyCommitment = 0;
            $staffTotalCommitment = 0;
            $staffPaidCount = 0;
            $staffPaidBankAmount = 0;
            $staffPaidCashAmount = 0;
            $staffPaidAmount = 0;
            $staffPaidTotalAmount = 0;
            $staffPendingCount = 0;
            $advanceSalaries = AdvanceSalary::with(['user.organization', 'user.designation', 'approver'])
                ->where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->get();

            $allPayslipRequests = collect();
            $pendingPayslipRequestsCount = 0;

            // Non-admin user's personal payslip requests & cleared vouchers
            $myPayslipRequests = PayslipRequest::with(['reviewer', 'payslip.payrollRun.payroll.organization', 'payslip.user.department', 'payslip.user.designation', 'payslip.user.organization'])
                ->where('user_id', $user->id)
                ->orderBy('payment_month', 'desc')
                ->orderBy('created_at', 'desc')
                ->get();
            $myApprovedRequestsCount = $myPayslipRequests->where('status', 'APPROVED')->count();
            $myPendingRequestsCount = $myPayslipRequests->where('status', 'PENDING')->count();
        }

        // -----------------------------------------------------------------
        // Eligible Disbursed/Paid Salary Months for Current User
        // Strictly filters to months where an official PAID payslip exists
        // -----------------------------------------------------------------
        $myPaidSlips = Payslip::with(['user.organization', 'payrollRun.payroll.organization'])
            ->where('user_id', $user->id)
            ->where('status', 'PAID')
            ->where('net_pay', '>', 0)
            ->orderBy('pay_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        $existingRequestsByMonth = PayslipRequest::where('user_id', $user->id)
            ->get()
            ->keyBy('payment_month');

        $myEligibleSalaryMonths = collect();
        foreach ($myPaidSlips as $slip) {
            $m = $slip->payment_month ?: ($slip->pay_date ? substr($slip->pay_date, 0, 7) : ($slip->created_at ? date('Y-m', strtotime((string)$slip->created_at)) : date('Y-m')));
            if (!$m || $myEligibleSalaryMonths->has($m)) {
                continue;
            }

            $existingReq = $existingRequestsByMonth->get($m);
            $reqStatus = $existingReq ? $existingReq->status : null;
            $canRequest = (!$existingReq || $reqStatus === 'REJECTED');
            $org = $slip->user->organization ?? $slip->payrollRun->payroll->organization ?? null;

            $myEligibleSalaryMonths->put($m, [
                'payment_month' => $m,
                'month_label' => date('F Y', strtotime($m . '-01')),
                'pay_date' => $slip->pay_date ? date('d-M-Y', strtotime($slip->pay_date)) : date('d-M-Y', strtotime($slip->created_at)),
                'net_pay' => (float) $slip->net_pay,
                'currency' => $org->currency ?? 'PKR',
                'company_name' => $org->name ?? 'Corporate',
                'payslip_id' => $slip->id,
                'request_status' => $reqStatus,
                'can_request' => $canRequest,
                'request_id' => $existingReq ? $existingReq->id : null,
            ]);
        }
        $myEligibleSalaryMonths = $myEligibleSalaryMonths->values();

        // ==========================================
        // 2. PAYROLL HISTORY WITH RICH FILTERS
        // ==========================================
        $historyMonth = $request->input('history_month');
        $historyUserId = $request->input('history_user_id');
        $historyOrgId = $request->input('history_org_id', $selectedOrgId);
        $historyDeptId = $request->input('history_dept_id');
        $historyStatus = $request->input('history_status');
        $historySearch = $request->input('history_search');

        $historyQuery = Payslip::with(['user.department', 'user.designation', 'user.organization', 'payrollRun.payroll'])
            ->whereHas('user', function ($q) {
                $q->excludeDevs();
            });

        if (!$user->canManagePayroll()) {
            $historyTotalGross = 0;
            $historyTotalDeductions = 0;
            $historyTotalNet = 0;
            $historyTotalCount = 0;
            $payslips = collect();
        } else {
            if (!empty($historyMonth)) {
                $historyQuery->where(function ($q) use ($historyMonth) {
                    $q->where('payment_month', $historyMonth)
                      ->orWhere('pay_date', 'like', "{$historyMonth}%")
                      ->orWhere('created_at', 'like', "{$historyMonth}%");
                });
            }
            if (!empty($historyUserId)) {
                $historyQuery->where('user_id', $historyUserId);
            }
            if (!empty($historyOrgId)) {
                $historyQuery->whereHas('user', fn ($q) => $q->where('organization_id', $historyOrgId)->excludeDevs());
            }
            if (!empty($historyDeptId)) {
                $historyQuery->whereHas('user', fn ($q) => $q->where('department_id', $historyDeptId)->excludeDevs());
            }
            if (!empty($historyStatus)) {
                $historyQuery->where('status', $historyStatus);
            }
            if (!empty($historySearch)) {
                $historyQuery->whereHas('user', function ($q) use ($historySearch) {
                    $q->where('name', 'like', "%{$historySearch}%")
                      ->orWhere('email', 'like', "%{$historySearch}%")
                      ->excludeDevs();
                });
            }

            // Summary KPIs for filtered history
            $historyTotalGross = (float) ((clone $historyQuery)->sum(DB::raw('base_salary + allowances')));
            $historyTotalDeductions = (float) ((clone $historyQuery)->sum('deductions') + (clone $historyQuery)->sum('tax'));
            $historyTotalNet = (float) ((clone $historyQuery)->sum('net_pay'));
            $historyTotalCount = (int) ((clone $historyQuery)->count());

            $payslips = $historyQuery->orderBy('pay_date', 'desc')->orderBy('created_at', 'desc')->paginate(10)->appends($request->query());
        }

        // ==========================================
        // 3. PAYROLL REPORTS & ANALYTICS
        // ==========================================
        $reportMonth = $request->input('report_month', date('Y-m'));
        $reportOrgId = $request->input('report_org_id', $selectedOrgId);
        $reportDeptId = $request->input('report_dept_id');

        $reportQuery = Payslip::with(['user.department', 'user.designation', 'user.organization', 'payrollRun.payroll'])
            ->whereHas('user', function ($q) {
                $q->excludeDevs();
            });

        if ($user->isEmployee()) {
            $reportQuery->where('user_id', $user->id);
        } else {
            if (!empty($reportMonth)) {
                $reportQuery->where(function ($q) use ($reportMonth) {
                    $q->where('payment_month', $reportMonth)
                      ->orWhere('pay_date', 'like', "{$reportMonth}%")
                      ->orWhere('created_at', 'like', "{$reportMonth}%");
                });
            }
            if (!empty($reportOrgId)) {
                $reportQuery->whereHas('user', fn ($q) => $q->where('organization_id', $reportOrgId)->excludeDevs());
            }
            if (!empty($reportDeptId)) {
                $reportQuery->whereHas('user', fn ($q) => $q->where('department_id', $reportDeptId)->excludeDevs());
            }
        }

        $reportSlips = $reportQuery->orderBy('created_at', 'asc')->get();

        $reportTotalBasic = (float) $reportSlips->sum('base_salary');
        $reportTotalAllowances = (float) $reportSlips->sum('allowances');
        $reportTotalGross = $reportTotalBasic + $reportTotalAllowances;
        $reportTotalAdvances = (float) $reportSlips->sum('advance_deduction');
        $reportTotalLate = (float) $reportSlips->sum('late_deduction');
        $reportTotalAbsent = (float) $reportSlips->sum('absent_deduction');
        $reportTotalEOBI = (float) $reportSlips->sum('eobi_deduction');
        $reportTotalTax = (float) $reportSlips->sum('tax');
        $reportTotalOtherDed = (float) $reportSlips->sum('other_deductions');
        $reportTotalDeductions = (float) ($reportSlips->sum('deductions') + $reportTotalTax);
        $reportTotalNet = (float) $reportSlips->sum('net_pay');
        $reportTotalHeadcount = (int) $reportSlips->pluck('user_id')->unique()->count();

        // Group department-wise allocation
        $departmentBreakdown = $reportSlips->groupBy(function ($s) {
            return $s->user && $s->user->department ? $s->user->department->name : 'General & Operations';
        })->map(function ($slips, $deptName) use ($reportTotalNet) {
            $deptNet = (float) $slips->sum('net_pay');
            $deptGross = (float) ($slips->sum('base_salary') + $slips->sum('allowances'));
            $deptDeductions = (float) ($slips->sum('deductions') + $slips->sum('tax'));
            $percentage = $reportTotalNet > 0 ? round(($deptNet / $reportTotalNet) * 100, 1) : 0;
            return [
                'department' => $deptName,
                'headcount' => $slips->pluck('user_id')->unique()->count(),
                'gross' => $deptGross,
                'deductions' => $deptDeductions,
                'net_pay' => $deptNet,
                'percentage' => $percentage,
            ];
        })->values();

        return view('payroll.index', compact(
            'activeTab', 'payrolls', 'payslips', 'organizations', 'departments', 'selectedOrgId',
            'employees', 'advanceSalaries',
            'staffSearch', 'staffDeptId', 'staffOrgId', 'staffTotalCount', 'staffMonthlyCommitment', 'staffPaidCount', 'staffPaidAmount', 'staffPendingCount',
            'staffBankCommitment', 'staffCashCommitment', 'staffTotalCommitment', 'staffPaidBankAmount', 'staffPaidCashAmount', 'staffPaidTotalAmount',
            'allPayslipRequests', 'pendingPayslipRequestsCount', 'myPayslipRequests', 'myApprovedRequestsCount', 'myPendingRequestsCount', 'myEligibleSalaryMonths',
            'historyMonth', 'historyUserId', 'historyOrgId', 'historyDeptId', 'historyStatus', 'historySearch',
            'historyTotalGross', 'historyTotalDeductions', 'historyTotalNet', 'historyTotalCount',
            'reportMonth', 'reportOrgId', 'reportDeptId', 'reportSlips',
            'reportTotalBasic', 'reportTotalAllowances', 'reportTotalGross', 'reportTotalAdvances',
            'reportTotalLate', 'reportTotalAbsent', 'reportTotalEOBI', 'reportTotalTax', 'reportTotalOtherDed',
            'reportTotalDeductions', 'reportTotalNet', 'reportTotalHeadcount', 'departmentBreakdown',
            'hasPayrollPin', 'isPayrollLocked'
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'organization_id' => 'required|exists:organizations,id',
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'pay_frequency' => 'required|string|in:MONTHLY,BI_WEEKLY,WEEKLY',
            'currency' => 'required|string|max:10',
            'default_working_days' => 'required|integer|min:1|max:31',
            'base_salary' => 'nullable|numeric|min:0',
            'allowances' => 'nullable|numeric|min:0',
            'deductions' => 'nullable|numeric|min:0',
            'tax_rate' => 'required|numeric|min:0|max:100',
            'overtime_multiplier' => 'nullable|numeric|min:0',
            'late_deduction_per_occurrence' => 'nullable|numeric|min:0',
            'status' => 'required|string|in:ACTIVE,INACTIVE,ARCHIVED',
        ]);

        try {
            $validated['type'] = $validated['pay_frequency'];
            $validated['base_salary'] = $validated['base_salary'] ?? 0;
            $validated['allowances'] = $validated['allowances'] ?? 0;
            $validated['deductions'] = $validated['deductions'] ?? 0;
            if (empty($validated['code'])) {
                $validated['code'] = 'PAY-' . strtoupper(Str::random(6));
            }

            $payroll = Payroll::create($validated);

            return redirect()->route('payroll.index', ['organization_id' => $validated['organization_id']])
                ->with('success', 'Company Payroll created successfully!');
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', 'Could not create payroll: ' . $e->getMessage());
        }
    }

    public function update(Request $request, $id)
    {
        $payroll = Payroll::findOrFail($id);

        $validated = $request->validate([
            'organization_id' => 'required|exists:organizations,id',
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'pay_frequency' => 'required|string|in:MONTHLY,BI_WEEKLY,WEEKLY',
            'currency' => 'required|string|max:10',
            'default_working_days' => 'required|integer|min:1|max:31',
            'base_salary' => 'nullable|numeric|min:0',
            'allowances' => 'nullable|numeric|min:0',
            'deductions' => 'nullable|numeric|min:0',
            'tax_rate' => 'required|numeric|min:0|max:100',
            'overtime_multiplier' => 'nullable|numeric|min:0',
            'late_deduction_per_occurrence' => 'nullable|numeric|min:0',
            'status' => 'required|string|in:ACTIVE,INACTIVE,ARCHIVED',
        ]);

        try {
            $validated['base_salary'] = $validated['base_salary'] ?? $payroll->base_salary ?? 0;
            $validated['allowances'] = $validated['allowances'] ?? $payroll->allowances ?? 0;
            $validated['deductions'] = $validated['deductions'] ?? $payroll->deductions ?? 0;

            $payroll->update($validated);

            // Instantly recalculate all existing payslips under this payroll engine with the new tax rate / structure
            $this->recalculatePayslipsForPayroll($payroll);

            return redirect()->route('payroll.index', ['organization_id' => $validated['organization_id']])
                ->with('success', 'Company Payroll updated and all associated employee payslips recalculated successfully!');
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', 'Could not update payroll: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            $payroll = Payroll::findOrFail($id);
            $orgId = $payroll->organization_id;
            $payroll->delete();

            return redirect()->route('payroll.index', ['organization_id' => $orgId])
                ->with('success', 'Company Payroll deleted successfully!');
        } catch (\Throwable $e) {
            return back()->with('error', 'Could not delete payroll: ' . $e->getMessage());
        }
    }

    public function assignEmployees(Request $request)
    {
        $validated = $request->validate([
            'payroll_id' => 'required|exists:payrolls,id',
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id',
        ]);

        try {
            $payroll = Payroll::findOrFail($validated['payroll_id']);

            User::whereIn('id', $validated['user_ids'])->update([
                'payroll_id' => $payroll->id,
                'organization_id' => $payroll->organization_id,
            ]);

            // Recalculate payslips for these employees under the newly assigned payroll
            $this->recalculatePayslipsForPayroll($payroll);

            return redirect()->route('payroll.index', ['organization_id' => $payroll->organization_id])
                ->with('success', count($validated['user_ids']) . ' employees assigned to ' . $payroll->name . ' and payslips updated!');
        } catch (\Throwable $e) {
            return back()->with('error', 'Could not assign employees: ' . $e->getMessage());
        }
    }

    public function run(Request $request, $id)
    {
        try {
            $payroll = Payroll::with('employees')->findOrFail($id);

            $assignedEmployees = User::excludeDevs()->where('payroll_id', $payroll->id)->where('is_active', true)->get();

            if ($assignedEmployees->isEmpty()) {
                return redirect()->back()->with('error', 'No active employees are currently assigned to this company payroll.');
            }

            $startDate = $request->input('period_start', now()->startOfMonth()->toDateTimeString());
            $endDate = $request->input('period_end', now()->endOfMonth()->toDateTimeString());

            $payrollRun = PayrollRun::create([
                'id' => (string) Str::uuid(),
                'organization_id' => $payroll->organization_id,
                'payroll_id' => $payroll->id,
                'run_name' => $payroll->name . ' - ' . date('M Y'),
                'period_start' => $startDate,
                'period_end' => $endDate,
                'total_gross_pay' => 0,
                'total_deductions' => 0,
                'total_tax' => 0,
                'total_net_pay' => 0,
                'total_employees' => $assignedEmployees->count(),
                'status' => 'COMPLETED',
                'executed_by' => Auth::id(),
            ]);

            $totalGross = 0;
            $totalDeductions = 0;
            $totalTax = 0;
            $totalNet = 0;

            $periodMonth = date('Y-m', strtotime($startDate));
            $latesPerDay = (int) SystemSetting::get('payroll_lates_per_day_deduction', 3);
            $defaultWorkingDays = (int) ($payroll->default_working_days ?: SystemSetting::get('payroll_default_working_days', 22));
            if ($defaultWorkingDays <= 0) {
                $defaultWorkingDays = 22;
            }

            foreach ($assignedEmployees as $emp) {
                $base = (float) ($emp->base_salary ?? 0);
                $fuel = (float) ($emp->fuel_allowance ?? 0);
                $special = (float) ($emp->special_allowance ?? 0);
                $allowances = (float) ($emp->allowances ?? ($fuel + $special));
                $eobi = (float) ($emp->eobi_deduction ?? 0);
                $regDeductions = (float) ($emp->deductions ?? $eobi);

                // Calculate Late Arrival Salary Deduction (e.g. 3 Lates = 1 Day Salary Deduction)
                $lateCount = Attendance::where('user_id', $emp->id)
                    ->whereBetween('date', [date('Y-m-d', strtotime($startDate)), date('Y-m-d', strtotime($endDate))])
                    ->where('status', 'LATE')
                    ->count();

                $deductedDays = ($latesPerDay > 0) ? (int) floor($lateCount / $latesPerDay) : 0;
                $dailyRate = $defaultWorkingDays > 0 ? ($base / $defaultWorkingDays) : 0;
                $lateSalaryDeduction = round($deductedDays * $dailyRate, 2);

                // Check for approved/disbursed Advance Salary / Loans for this employee in this pay period
                $advances = AdvanceSalary::where('user_id', $emp->id)
                    ->whereIn('status', ['APPROVED', 'DISBURSED'])
                    ->where(function ($q) use ($periodMonth) {
                        $q->where('deduction_month', $periodMonth)
                          ->orWhereNull('deduction_month');
                    })
                    ->get();

                $advanceDeduction = (float) $advances->sum('amount');
                $totalEmpDeductions = $regDeductions + $advanceDeduction + $lateSalaryDeduction;

                $taxableIncome = max(0, $base + $allowances - $regDeductions);
                $effectiveTaxRate = (float) (
                    ($payroll->tax_rate > 0) ? $payroll->tax_rate :
                    ($payroll->organization->payroll_tax_rate ?? SystemSetting::get('company_tax_rate', 12.0))
                );
                $tax = round($taxableIncome * ($effectiveTaxRate / 100), 2);
                $netPay = max(0, $base + $allowances - $totalEmpDeductions - $tax);

                Payslip::create([
                    'id' => (string) Str::uuid(),
                    'payroll_run_id' => $payrollRun->id,
                    'user_id' => $emp->id,
                    'base_salary' => $base,
                    'allowances' => $allowances,
                    'fuel_allowance' => $fuel,
                    'special_allowance' => $special,
                    'deductions' => $totalEmpDeductions,
                    'advance_deduction' => $advanceDeduction,
                    'late_deduction' => $lateSalaryDeduction,
                    'late_days_deducted' => $deductedDays,
                    'eobi_deduction' => $eobi,
                    'tax' => $tax,
                    'net_pay' => $netPay,
                    'pay_date' => $endDate,
                    'status' => 'PAID',
                ]);

                // Mark advances as DEDUCTED so they aren't deducted multiple times
                foreach ($advances as $adv) {
                    $adv->update(['status' => 'DEDUCTED']);
                }

                $totalGross += ($base + $allowances);
                $totalDeductions += $totalEmpDeductions;
                $totalTax += $tax;
                $totalNet += $netPay;
            }

            $payrollRun->update([
                'total_gross_pay' => $totalGross,
                'total_deductions' => $totalDeductions,
                'total_tax' => $totalTax,
                'total_net_pay' => $totalNet,
            ]);

            return redirect()->route('payroll.index', ['organization_id' => $payroll->organization_id])
                ->with('success', 'Payroll successfully executed for ' . $assignedEmployees->count() . ' employees! Advances & late deductions automatically reconciled.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Could not execute payroll: ' . $e->getMessage());
        }
    }

    /**
     * Synchronize and recalculate all payslips under a specific Payroll engine
     */
    public function recalculatePayslipsForPayroll(Payroll $payroll)
    {
        $runIds = PayrollRun::where('payroll_id', $payroll->id)->pluck('id');
        if ($runIds->isEmpty()) {
            return;
        }

        $latesPerDay = (int) SystemSetting::get('payroll_lates_per_day_deduction', 3);
        $defaultWorkingDays = (int) ($payroll->default_working_days ?: SystemSetting::get('payroll_default_working_days', 22));
        if ($defaultWorkingDays <= 0) {
            $defaultWorkingDays = 22;
        }

        $effectiveTaxRate = (float) (
            ($payroll->tax_rate > 0) ? $payroll->tax_rate :
            ($payroll->organization->payroll_tax_rate ?? SystemSetting::get('company_tax_rate', 12.0))
        );
        $taxMultiplier = $effectiveTaxRate / 100;
        $payslips = Payslip::whereIn('payroll_run_id', $runIds)->with(['user', 'payrollRun'])->get();

        foreach ($payslips as $slip) {
            $emp = $slip->user;
            $base = (float) ($emp ? ($emp->base_salary ?? 0) : 0);
            $fuel = (float) ($emp ? ($emp->fuel_allowance ?? 0) : 0);
            $special = (float) ($emp ? ($emp->special_allowance ?? 0) : 0);
            $allowances = (float) ($emp ? ($emp->allowances ?? ($fuel + $special)) : 0);
            $advanceDeduction = (float) ($slip->advance_deduction ?? 0);
            $eobi = (float) ($emp ? ($emp->eobi_deduction ?? 0) : 0);
            $regDeductions = (float) ($emp ? ($emp->deductions ?? $eobi) : 0);

            // Calculate late deduction based on payslip period
            $run = $slip->payrollRun;
            $lateSalaryDeduction = (float) ($slip->late_deduction ?? 0);
            $deductedDays = (int) ($slip->late_days_deducted ?? 0);

            if ($run && $emp) {
                $start = date('Y-m-d', strtotime($run->period_start));
                $end = date('Y-m-d', strtotime($run->period_end));
                $lateCount = Attendance::where('user_id', $emp->id)
                    ->whereBetween('date', [$start, $end])
                    ->where('status', 'LATE')
                    ->count();
                $deductedDays = ($latesPerDay > 0) ? (int) floor($lateCount / $latesPerDay) : 0;
                $dailyRate = $defaultWorkingDays > 0 ? ($base / $defaultWorkingDays) : 0;
                $lateSalaryDeduction = round($deductedDays * $dailyRate, 2);
            }

            $totalDeductions = $regDeductions + $advanceDeduction + $lateSalaryDeduction;

            $taxableIncome = max(0, $base + $allowances - $regDeductions);
            $tax = $taxMultiplier > 0 ? round($taxableIncome * $taxMultiplier, 2) : (float) ($slip->tax ?? 0);
            $netPay = max(0, $base + $allowances - $totalDeductions - $tax);

            $slip->update([
                'base_salary' => $base,
                'allowances' => $allowances,
                'fuel_allowance' => $fuel,
                'special_allowance' => $special,
                'deductions' => $totalDeductions,
                'advance_deduction' => $advanceDeduction,
                'late_deduction' => $lateSalaryDeduction,
                'late_days_deducted' => $deductedDays,
                'eobi_deduction' => $eobi,
                'tax' => $tax,
                'net_pay' => $netPay,
            ]);
        }
    }

    /**
     * Store and grant an Advance Salary / Loan for an employee
     */
    public function storeAdvance(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'amount' => 'required|numeric|min:1',
            'advance_date' => 'required|date',
            'deduction_month' => 'required|string', // YYYY-MM format
            'reason' => 'nullable|string|max:255',
            'status' => 'required|string|in:APPROVED,DISBURSED,PENDING',
            'notes' => 'nullable|string',
        ]);

        try {
            $emp = User::findOrFail($validated['user_id']);
            $validated['id'] = (string) Str::uuid();
            $validated['organization_id'] = $emp->organization_id;
            $validated['approved_by_id'] = Auth::id();
            if ($validated['status'] === 'DISBURSED') {
                $validated['disbursed_at'] = now();
            }

            AdvanceSalary::create($validated);

            return redirect()->route('payroll.index', ['organization_id' => $emp->organization_id])
                ->with('success', 'Advance Salary of PKR ' . number_format($validated['amount']) . ' recorded for ' . $emp->name . '. Will be deducted on Salary Day!');
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', 'Could not grant advance salary: ' . $e->getMessage());
        }
    }

    /**
     * Update Advance Salary status (e.g. Approve, Disburse, or Cancel)
     */
    public function updateAdvanceStatus(Request $request, $id)
    {
        $advance = AdvanceSalary::findOrFail($id);

        $validated = $request->validate([
            'status' => 'required|string|in:PENDING,APPROVED,DISBURSED,DEDUCTED,REJECTED',
        ]);

        try {
            if ($validated['status'] === 'DISBURSED' && !$advance->disbursed_at) {
                $advance->disbursed_at = now();
            }
            if ($validated['status'] === 'APPROVED' && !$advance->approved_by_id) {
                $advance->approved_by_id = Auth::id();
            }
            $advance->status = $validated['status'];
            $advance->save();

            return redirect()->route('payroll.index', ['organization_id' => $advance->organization_id])
                ->with('success', 'Advance Salary record status updated to ' . $validated['status'] . '!');
        } catch (\Throwable $e) {
            return back()->with('error', 'Could not update advance status: ' . $e->getMessage());
        }
    }

    /**
     * Delete an Advance Salary record
     */
    public function deleteAdvance($id)
    {
        try {
            $advance = AdvanceSalary::findOrFail($id);
            $orgId = $advance->organization_id;
            $advance->delete();

            return redirect()->route('payroll.index', ['organization_id' => $orgId])
                ->with('success', 'Advance Salary record deleted successfully.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Could not delete advance record: ' . $e->getMessage());
        }
    }

    /**
     * Disburse / Give salary directly to a single employee
     */
    public function paySingleEmployee(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'period_start' => 'required|date',
            'period_end' => 'required|date',
            'pay_date' => 'required|date',
            'base_salary' => 'required|numeric|min:0',
            'fuel_allowance' => 'nullable|numeric|min:0',
            'special_allowance' => 'nullable|numeric|min:0',
            'other_allowances' => 'nullable|numeric|min:0',
            'advance_deduction' => 'nullable|numeric|min:0',
            'late_deduction' => 'nullable|numeric|min:0',
            'late_days_deducted' => 'nullable|integer|min:0',
            'absent_deduction' => 'nullable|numeric|min:0',
            'eobi_deduction' => 'nullable|numeric|min:0',
            'other_deductions' => 'nullable|numeric|min:0',
            'tax' => 'nullable|numeric|min:0',
            'payment_method' => 'nullable|string|max:50',
            'template_style' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
        ]);

        try {
            $emp = User::with(['organization', 'payroll'])->findOrFail($validated['user_id']);
            $orgId = $emp->organization_id;

            // Resolve or link payroll profile for this employee so foreign key constraint is satisfied
            $payroll = $emp->payroll;
            $defTax = (float) ($emp->organization->payroll_tax_rate ?? SystemSetting::get('company_tax_rate', 12.0));
            if (!$payroll && $orgId) {
                $payroll = Payroll::where('organization_id', $orgId)->where('status', 'ACTIVE')->first();
                if (!$payroll) {
                    $payroll = Payroll::firstOrCreate(
                        ['organization_id' => $orgId, 'name' => ($emp->organization->name ?? 'Corporate') . ' Standard Payroll'],
                        [
                            'id' => (string) Str::uuid(),
                            'code' => 'PAY-STD',
                            'pay_frequency' => 'MONTHLY',
                            'currency' => $emp->organization->currency ?? 'PKR',
                            'default_working_days' => 22,
                            'tax_rate' => $defTax,
                            'status' => 'ACTIVE',
                        ]
                    );
                }
                $emp->update(['payroll_id' => $payroll->id]);
            }

            $base = (float) $validated['base_salary'];
            $fuel = (float) ($validated['fuel_allowance'] ?? 0);
            $special = (float) ($validated['special_allowance'] ?? 0);
            $otherAllow = (float) ($validated['other_allowances'] ?? 0);
            $totalAllowances = $fuel + $special + $otherAllow;

            $advanceDed = (float) ($validated['advance_deduction'] ?? 0);
            $lateDed = (float) ($validated['late_deduction'] ?? 0);
            $lateDays = (int) ($validated['late_days_deducted'] ?? 0);
            $absentDed = (float) ($validated['absent_deduction'] ?? 0);
            $eobiDed = (float) ($validated['eobi_deduction'] ?? 0);
            $otherDed = (float) ($validated['other_deductions'] ?? 0);
            $totalDeductions = $advanceDed + $lateDed + $absentDed + $eobiDed + $otherDed;

            if (isset($validated['tax']) && is_numeric($validated['tax'])) {
                $tax = (float) $validated['tax'];
            } else {
                $taxRate = (float) (($payroll && $payroll->tax_rate > 0) ? $payroll->tax_rate : $defTax);
                $taxableIncome = max(0, ($base + $totalAllowances) - $eobiDed);
                $tax = round($taxableIncome * ($taxRate / 100), 2);
            }
            $netPay = max(0, ($base + $totalAllowances) - ($totalDeductions + $tax));

            $periodMonth = date('Y-m', strtotime($validated['pay_date'] ?: $validated['period_start']));

            // Find or create PayrollRun for this company and period
            $payrollRun = PayrollRun::firstOrCreate(
                [
                    'organization_id' => $orgId,
                    'payroll_id' => $payroll ? $payroll->id : null,
                    'period_start' => date('Y-m-d 00:00:00', strtotime($validated['period_start'])),
                    'period_end' => date('Y-m-d 23:59:59', strtotime($validated['period_end'])),
                ],
                [
                    'id' => (string) Str::uuid(),
                    'run_name' => ($payroll ? $payroll->name : 'Corporate Payroll') . ' - ' . date('M Y', strtotime($validated['period_start'])),
                    'status' => 'COMPLETED',
                    'executed_by' => Auth::id(),
                ]
            );

            // Check if payslip already exists for this user in this run or month
            $payslip = Payslip::where('payroll_run_id', $payrollRun->id)
                ->where('user_id', $emp->id)
                ->first();

            if (!$payslip) {
                $payslip = Payslip::where('user_id', $emp->id)
                    ->where('payment_month', $periodMonth)
                    ->first();
            }

            $slipData = [
                'payroll_run_id' => $payrollRun->id,
                'user_id' => $emp->id,
                'base_salary' => $base,
                'allowances' => $totalAllowances,
                'fuel_allowance' => $fuel,
                'special_allowance' => $special,
                'other_allowances' => $otherAllow,
                'deductions' => $totalDeductions,
                'advance_deduction' => $advanceDed,
                'late_deduction' => $lateDed,
                'late_days_deducted' => $lateDays,
                'absent_deduction' => $absentDed,
                'eobi_deduction' => $eobiDed,
                'other_deductions' => $otherDed,
                'tax' => $tax,
                'net_pay' => $netPay,
                'pay_date' => $validated['pay_date'],
                'payment_month' => $periodMonth,
                'status' => 'PAID',
                'payment_method' => $validated['payment_method'] ?? 'BANK_TRANSFER',
                'template_style' => $validated['template_style'] ?? 'standard',
                'notes' => $validated['notes'] ?? null,
            ];

            if ($payslip) {
                $payslip->update($slipData);
            } else {
                $slipData['id'] = (string) Str::uuid();
                $slipData['created_at'] = now();
                $payslip = Payslip::create($slipData);
            }

            // Automatically link any pending or approved PayslipRequest for this employee & period
            PayslipRequest::where('user_id', $emp->id)
                ->where('payment_month', $periodMonth)
                ->whereNull('payslip_id')
                ->update(['payslip_id' => $payslip->id]);

            // Reconcile approved advances for this employee
            if ($advanceDed > 0) {
                $advances = AdvanceSalary::where('user_id', $emp->id)
                    ->whereIn('status', ['APPROVED', 'DISBURSED'])
                    ->get();
                $remaining = $advanceDed;
                foreach ($advances as $adv) {
                    if ($remaining <= 0) break;
                    $adv->update(['status' => 'DEDUCTED']);
                    $remaining -= $adv->amount;
                }
            }

            // Update PayrollRun aggregate totals
            $runSlips = Payslip::where('payroll_run_id', $payrollRun->id)->get();
            $payrollRun->update([
                'total_gross_pay' => $runSlips->sum(fn ($s) => $s->base_salary + $s->allowances),
                'total_deductions' => $runSlips->sum(fn ($s) => $s->deductions + $s->tax),
                'total_tax' => $runSlips->sum('tax'),
                'total_net_pay' => $runSlips->sum('net_pay'),
                'total_employees' => $runSlips->pluck('user_id')->unique()->count(),
            ]);

            return redirect()->route('payroll.index', ['tab' => 'staff', 'organization_id' => $orgId])
                ->with('success', 'Salary of ' . ($emp->organization->currency ?? 'PKR') . ' ' . number_format($netPay, 2) . ' successfully disbursed to ' . $emp->name . ' for ' . date('F Y', strtotime($periodMonth . '-01')) . '!')
                ->with('just_paid_slip', [
                    'id' => $payslip->id,
                    'employee_name' => $emp->name,
                    'amount' => number_format($netPay, 2),
                    'currency' => $emp->organization->currency ?? 'PKR',
                    'month' => date('F Y', strtotime($periodMonth . '-01')),
                ]);
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', 'Could not disburse salary: ' . $e->getMessage());
        }
    }

    /**
     * Export Master Detailed Payroll Register Report as CSV
     */
    public function exportReport(Request $request)
    {
        $reportMonth = $request->input('report_month', date('Y-m'));
        $orgId = $request->input('report_org_id');
        $deptId = $request->input('report_dept_id');

        $query = Payslip::with(['user.department', 'user.designation', 'user.organization'])
            ->whereHas('user', fn ($q) => $q->excludeDevs());

        if (!empty($reportMonth)) {
            $query->where(function ($q) use ($reportMonth) {
                $q->where('payment_month', $reportMonth)
                  ->orWhere('pay_date', 'like', "{$reportMonth}%")
                  ->orWhere('created_at', 'like', "{$reportMonth}%");
            });
        }

        if (!empty($orgId)) {
            $query->whereHas('user', fn ($q) => $q->where('organization_id', $orgId)->excludeDevs());
        }
        if (!empty($deptId)) {
            $query->whereHas('user', fn ($q) => $q->where('department_id', $deptId)->excludeDevs());
        }

        $slips = $query->orderBy('created_at', 'asc')->get();

        $filename = 'Master_Payroll_Report_' . $reportMonth . '.csv';
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($slips, $reportMonth) {
            $file = fopen('php://output', 'w');
            // Write UTF-8 BOM for Excel compatibility
            fputs($file, "\xEF\xBB\xBF");

            fputcsv($file, ['CORPORATE MASTER PAYROLL REGISTER REPORT', "Pay Period: {$reportMonth}", 'Exported: ' . date('Y-m-d H:i:s')]);
            fputcsv($file, []);

            fputcsv($file, [
                'S.No', 'Employee Name', 'Employee Code', 'CNIC / National ID', 'Company', 'Department', 'Designation',
                'Bank Name', 'Account Number / IBAN', 'Basic Salary', 'Fuel Allowance', 'Special Allowance', 'Other Allowances',
                'Gross Salary', 'Advance Deducted', 'Late Deduction', 'Absent Deduction', 'EOBI Deduction', 'Income Tax (FBR)',
                'Other Deductions', 'Total Deductions', 'Net Take-Home Pay', 'Disbursement Date', 'Payment Method', 'Status'
            ]);

            $idx = 1;
            $totBasic = 0; $totFuel = 0; $totSpecial = 0; $totOtherAllow = 0; $totGross = 0;
            $totAdv = 0; $totLate = 0; $totAbsent = 0; $totEobi = 0; $totTax = 0; $totOtherDed = 0; $totDed = 0; $totNet = 0;

            foreach ($slips as $s) {
                $u = $s->user;
                $org = $u->organization ?? null;
                $fuel = (float) ($s->fuel_allowance ?? 0);
                $special = (float) ($s->special_allowance ?? 0);
                $otherAllow = (float) ($s->other_allowances ?? 0);
                $gross = (float) ($s->base_salary + $s->allowances);
                $adv = (float) ($s->advance_deduction ?? 0);
                $late = (float) ($s->late_deduction ?? 0);
                $absent = (float) ($s->absent_deduction ?? 0);
                $eobi = (float) ($s->eobi_deduction ?? 0);
                $tax = (float) ($s->tax ?? 0);
                $otherDed = (float) ($s->other_deductions ?? 0);
                $ded = (float) ($s->deductions + $tax);
                $net = (float) ($s->net_pay);

                $totBasic += $s->base_salary;
                $totFuel += $fuel;
                $totSpecial += $special;
                $totOtherAllow += $otherAllow;
                $totGross += $gross;
                $totAdv += $adv;
                $totLate += $late;
                $totAbsent += $absent;
                $totEobi += $eobi;
                $totTax += $tax;
                $totOtherDed += $otherDed;
                $totDed += $ded;
                $totNet += $net;

                fputcsv($file, [
                    $idx++,
                    $u->name ?? 'Staff',
                    $u ? 'PD:' . substr(preg_replace('/[^0-9]/', '', $u->id), 0, 4) : 'N/A',
                    $u->national_id_number ?? 'N/A',
                    $org->name ?? 'Company',
                    $u->department->name ?? 'N/A',
                    $u->designation->title ?? 'N/A',
                    $u->bank_name ?? 'N/A',
                    $u->bank_account_number ?? $u->bank_iban ?? 'N/A',
                    number_format($s->base_salary, 2, '.', ''),
                    number_format($fuel, 2, '.', ''),
                    number_format($special, 2, '.', ''),
                    number_format($otherAllow, 2, '.', ''),
                    number_format($gross, 2, '.', ''),
                    number_format($adv, 2, '.', ''),
                    number_format($late, 2, '.', ''),
                    number_format($absent, 2, '.', ''),
                    number_format($eobi, 2, '.', ''),
                    number_format($tax, 2, '.', ''),
                    number_format($otherDed, 2, '.', ''),
                    number_format($ded, 2, '.', ''),
                    number_format($net, 2, '.', ''),
                    $s->pay_date ?: date('Y-m-d', strtotime($s->created_at)),
                    $s->payment_method ?: 'BANK_TRANSFER',
                    $s->status ?: 'PAID'
                ]);
            }

            // Totals Row
            fputcsv($file, []);
            fputcsv($file, [
                'TOTALS', '', '', '', '', '', '', '', '',
                number_format($totBasic, 2, '.', ''),
                number_format($totFuel, 2, '.', ''),
                number_format($totSpecial, 2, '.', ''),
                number_format($totOtherAllow, 2, '.', ''),
                number_format($totGross, 2, '.', ''),
                number_format($totAdv, 2, '.', ''),
                number_format($totLate, 2, '.', ''),
                number_format($totAbsent, 2, '.', ''),
                number_format($totEobi, 2, '.', ''),
                number_format($totTax, 2, '.', ''),
                number_format($totOtherDed, 2, '.', ''),
                number_format($totDed, 2, '.', ''),
                number_format($totNet, 2, '.', ''),
                '', '', ''
            ]);

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Submit a payslip request by an employee / non-admin user
     * Supports single or multiple months selection with strict PAID salary verification
     */
    public function storePayslipRequest(Request $request)
    {
        // Support array of months (payment_months) or single month (payment_month)
        $months = $request->input('payment_months');
        if (empty($months)) {
            $single = $request->input('payment_month');
            if (!empty($single)) {
                $months = [$single];
            }
        }

        if (empty($months) || !is_array($months)) {
            return redirect()->route('payroll.index', ['tab' => 'requests'])
                ->with('error', 'Please select at least one eligible salary month to request a payslip.');
        }

        $user = Auth::user();
        $reason = $request->input('reason');
        $createdCount = 0;
        $alreadyPending = [];
        $alreadyApproved = [];
        $unpaidMonths = [];

        DB::beginTransaction();
        try {
            foreach ($months as $month) {
                $month = trim($month);
                if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
                    continue;
                }

                // 1. Strict Verification: Check if user was actually PAID for this month
                $paidSlip = Payslip::where('user_id', $user->id)
                    ->where('status', 'PAID')
                    ->where('net_pay', '>', 0)
                    ->where(function ($q) use ($month) {
                        $q->where('payment_month', $month)
                          ->orWhere('pay_date', 'like', "{$month}%")
                          ->orWhere('created_at', 'like', "{$month}%");
                    })
                    ->first();

                if (!$paidSlip) {
                    $unpaidMonths[] = date('F Y', strtotime($month . '-01'));
                    continue;
                }

                // 2. Check existing request status
                $existing = PayslipRequest::where('user_id', $user->id)
                    ->where('payment_month', $month)
                    ->first();

                if ($existing) {
                    if ($existing->status === 'PENDING') {
                        $alreadyPending[] = date('F Y', strtotime($month . '-01'));
                        continue;
                    } elseif ($existing->status === 'APPROVED') {
                        $alreadyApproved[] = date('F Y', strtotime($month . '-01'));
                        continue;
                    } elseif ($existing->status === 'REJECTED') {
                        // Re-submit previously rejected request
                        $existing->update([
                            'status' => 'PENDING',
                            'reason' => $reason,
                            'payslip_id' => $paidSlip->id,
                            'rejection_reason' => null,
                            'reviewed_by_id' => null,
                            'reviewed_at' => null,
                        ]);
                        $createdCount++;
                        continue;
                    }
                }

                // 3. Create new request linked to the verified paid payslip
                $createdReq = PayslipRequest::create([
                    'user_id' => $user->id,
                    'organization_id' => $user->organization_id,
                    'payment_month' => $month,
                    'reason' => $reason,
                    'status' => 'PENDING',
                    'payslip_id' => $paidSlip->id,
                ]);
                $createdCount++;

                // Notify HR & Admins of new clearance request
                \App\Services\HrmsMailService::sendPayslipRequestedAlert($createdReq, $user);
            }

            // If any selected month was not paid, prevent request and warn user
            if (!empty($unpaidMonths)) {
                DB::rollBack();
                $unpaidList = implode(', ', $unpaidMonths);
                return redirect()->route('payroll.index', ['tab' => 'requests'])
                    ->with('error', "Salary has not been disbursed for: {$unpaidList}. You can only request payslips for months where your salary has already been paid.");
            }

            DB::commit();

            $messages = [];
            if ($createdCount > 0) {
                $messages[] = "Successfully submitted payslip request for {$createdCount} month(s) to HR and System Admin for clearance.";
            }
            if (!empty($alreadyApproved)) {
                $messages[] = "Payslip(s) for " . implode(', ', $alreadyApproved) . " are already approved and available to view/print below.";
            }
            if (!empty($alreadyPending)) {
                $messages[] = "Payslip(s) for " . implode(', ', $alreadyPending) . " are already under review.";
            }

            $type = $createdCount > 0 ? 'success' : 'info';
            return redirect()->route('payroll.index', ['tab' => 'requests'])
                ->with($type, implode(' ', $messages));
        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->route('payroll.index', ['tab' => 'requests'])
                ->with('error', 'Error submitting payslip request: ' . $e->getMessage());
        }
    }

    /**
     * Approve a payslip request (Super Admin & HR only)
     */
    public function approvePayslipRequest(Request $request, $id)
    {
        $user = Auth::user();
        if (!$user->canManagePayroll()) {
            abort(403, 'Unauthorized. Only Super Admin and HR can approve payslip requests.');
        }

        $payslipRequest = PayslipRequest::with('user')->findOrFail($id);

        // If not already linked to a payslip, attempt to find matching payslip
        if (!$payslipRequest->payslip_id) {
            $month = $payslipRequest->payment_month;
            $slip = Payslip::where('user_id', $payslipRequest->user_id)
                ->where(function ($q) use ($month) {
                    $q->where('payment_month', $month)
                      ->orWhere('pay_date', 'like', "{$month}%")
                      ->orWhere('created_at', 'like', "{$month}%");
                })->first();

            if ($slip) {
                $payslipRequest->payslip_id = $slip->id;
            }
        }

        $payslipRequest->update([
            'status' => 'APPROVED',
            'reviewed_by_id' => $user->id,
            'reviewed_at' => now(),
            'rejection_reason' => null,
        ]);

        // Send high-priority notification to employee with direct download link
        if ($payslipRequest->payslip_id) {
            $slip = Payslip::find($payslipRequest->payslip_id);
            if ($slip && $payslipRequest->user) {
                \App\Services\HrmsMailService::sendPayslipReleasedAlert($payslipRequest, $slip, $payslipRequest->user, $user);
            }
        }

        return redirect()->route('payroll.index', ['tab' => 'requests'])
            ->with('success', 'Payslip request for ' . ($payslipRequest->user->name ?? 'Staff') . ' (' . date('F Y', strtotime($payslipRequest->payment_month . '-01')) . ') has been APPROVED. The employee can now view their official salary voucher.');
    }

    /**
     * Reject a payslip request (Super Admin & HR only)
     */
    public function rejectPayslipRequest(Request $request, $id)
    {
        $user = Auth::user();
        if (!$user->canManagePayroll()) {
            abort(403, 'Unauthorized. Only Super Admin and HR can reject payslip requests.');
        }

        $validated = $request->validate([
            'rejection_reason' => 'nullable|string|max:500',
        ]);

        $payslipRequest = PayslipRequest::with('user')->findOrFail($id);

        $payslipRequest->update([
            'status' => 'REJECTED',
            'reviewed_by_id' => $user->id,
            'reviewed_at' => now(),
            'rejection_reason' => $validated['rejection_reason'] ?? 'Request declined by administration.',
        ]);

        return redirect()->route('payroll.index', ['tab' => 'requests'])
            ->with('warning', 'Payslip request for ' . ($payslipRequest->user->name ?? 'Staff') . ' has been REJECTED.');
    }

    /**
     * Verify Payroll Security PIN or Password fallback to unlock vault.
     */
    public function verifyPin(Request $request)
    {
        $user = User::find(Auth::id()) ?? Auth::user();

        if (!$user->isSuperAdmin() && !$user->isDeveloperAdmin()) {
            abort(403, 'Unauthorized.');
        }

        // Fallback verification with account password or PIN
        if ($request->filled('password_fallback')) {
            $fallback = trim((string)$request->input('password_fallback'));
            if (Hash::check($fallback, $user->password) || $user->verifyPayrollPin($fallback)) {
                session(['payroll_pin_verified' => true, 'payroll_just_unlocked' => true]);
                if ($request->wantsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => true,
                        'message' => 'Payroll Vault unlocked successfully.',
                    ]);
                }
                return redirect()->route('payroll.index')->with('success', 'Payroll Vault unlocked successfully.');
            }

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Incorrect account password. Please try again.',
                ], 422);
            }
            return back()->withErrors(['payroll_pin' => 'Incorrect account password. Please try again.'])->withInput();
        }

        $pin = $request->input('pin');
        if (!$pin || !$user->verifyPayrollPin((string)$pin)) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid Security PIN. Please try again.',
                ], 422);
            }
            return back()->withErrors(['payroll_pin' => 'Invalid Security PIN. Please try again.'])->withInput();
        }

        session(['payroll_pin_verified' => true, 'payroll_just_unlocked' => true]);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Payroll Vault unlocked successfully.',
            ]);
        }

        return redirect()->route('payroll.index')->with('success', 'Payroll Vault unlocked successfully.');
    }

    /**
     * Re-lock the Payroll Vault for current session.
     */
    public function lockVault(Request $request)
    {
        $user = Auth::user();

        if (!$user->isSuperAdmin() && !$user->isDeveloperAdmin()) {
            abort(403, 'Unauthorized.');
        }

        session()->forget('payroll_pin_verified');

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Payroll Vault has been locked.',
            ]);
        }

        return redirect()->route('payroll.index', ['lock' => 1])->with('info', 'Payroll Vault has been locked.');
    }

    /**
     * View official salary slip in executive corporate layout
     */
    public function viewPayslip($id)
    {
        return $this->renderPayslipView($id, false);
    }

    /**
     * Direct download / print official salary slip
     */
    public function downloadPayslip($id)
    {
        return $this->renderPayslipView($id, true);
    }

    /**
     * Core renderer for official verified payslip voucher
     */
    private function renderPayslipView($id, bool $autoPrint = false)
    {
        $user = Auth::user();
        $payslip = Payslip::with([
            'user.department',
            'user.designation',
            'user.organization',
            'payrollRun.payroll.organization'
        ])->findOrFail($id);

        // Security authorization check:
        // Management/Admins can view any payslip. Employees can strictly view only their own.
        $canManage = $user->isSuperAdmin() || $user->isDeveloperAdmin() || $user->hasRole('SUPER_ADMIN') || $user->hasRole('SYSTEM_ADMIN') || $user->hasRole('HR_MANAGER') || $user->hasRole('HR');

        if (!$canManage && (int)$payslip->user_id !== (int)$user->id) {
            abort(403, 'Unauthorized access to confidential salary voucher.');
        }

        $emp = $payslip->user;
        $org = $emp->organization ?? optional($payslip->payrollRun)->payroll?->organization;
        $currency = $org->currency ?? 'PKR';

        $fuel = (float)($payslip->fuel_allowance ?? 0);
        $special = (float)($payslip->special_allowance ?? 0);
        $otherAllow = max(0, (float)$payslip->allowances - $fuel - $special);
        $grossSal = (float)$payslip->base_salary + (float)$payslip->allowances;

        $advanceDed = (float)($payslip->advance_salary_deduction ?? $payslip->advance_deduction ?? 0);
        $lateDed = (float)($payslip->late_deduction ?? 0);
        $lateDays = (int)($payslip->late_days_deducted ?? 0);
        $absentDed = (float)($payslip->absent_deduction ?? 0);
        $eobiDed = (float)($payslip->eobi_deduction ?? 0);
        $otherDed = max(0, (float)$payslip->deductions - $advanceDed - $lateDed - $absentDed - $eobiDed);
        $grossDed = (float)$payslip->deductions + (float)$payslip->tax;

        $salaryPeriod = !empty($payslip->payment_month)
            ? date('F Y', strtotime($payslip->payment_month . '-01'))
            : (optional($payslip->payrollRun)->payroll_month ? date('F Y', strtotime($payslip->payrollRun->payroll_month . '-01')) : date('F Y', strtotime($payslip->created_at)));

        $payDate = !empty($payslip->pay_date)
            ? date('d M Y', strtotime($payslip->pay_date))
            : date('d M Y', strtotime($payslip->created_at));

        $netPayInWords = $this->convertAmountToWords((float)$payslip->net_pay, $currency);

        $cleanId = strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', (string)$payslip->id), 0, 6));
        $refNo = 'AXE-PAY-' . date('Ym', strtotime($payslip->payment_month ? $payslip->payment_month . '-01' : $payslip->created_at)) . '-' . $cleanId;
        $empCode = $emp->employee_id ?? ('EMP-' . strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', (string)($emp->id ?? $payslip->user_id)), 0, 4)));

        $compNameRaw = $org->name ?? optional($payslip->payrollRun)->payroll?->name ?? 'AxeCorp';
        $compNameLower = strtolower($compNameRaw);

        if (str_contains($compNameLower, 'spectra')) {
            $defName = 'Design Spectra Technologies';
            $defPhone = '03363178353';
            $defEmail = 'Info@designspectratech.com';
            $defWebsite = 'www.designspectratech.com';
            $defAddress = 'C3, C4, 2nd Floor Amaan Chamber, Shaheed-e-millat road, karachi';
        } elseif (str_contains($compNameLower, 'falcon')) {
            $defName = 'FalconCore Technologies';
            $defPhone = '0331 2424035';
            $defEmail = 'Info@falconcoretechnologies.com';
            $defWebsite = 'www.falconcoretechnologies.com';
            $defAddress = 'C3 2nd floor Amaan chambar, shaheed-e-Millat Road karachi';
        } elseif (str_contains($compNameLower, 'wave')) {
            $defName = 'DesignWave Technologies';
            $defPhone = '+92 332 2600982';
            $defEmail = 'Info@designwavetechnologies.com';
            $defWebsite = 'www.designwavetechnologies.com';
            $defAddress = 'C3/C4 2nd floor Amaan chambar, shaheed-e-Millat Road karachi';
        } else {
            $defName = $org->name ?? 'AxeCorp Tech';
            $defPhone = '021-34534002';
            $defEmail = 'accounts@axecorptechnologies.com';
            $defWebsite = 'AxecorpTechnologies.com';
            $defAddress = 'C3, 2nd floor Aman Chamber, Shaheed-e-Millat Road, Karachi';
        }

        $slipPayload = [
            'id' => $payslip->id,
            'ref_no' => $refNo,
            'salary_period' => $salaryPeriod,
            'pay_date' => $payDate,
            'employee_name' => $emp->name ?? 'Employee',
            'employee_code' => $empCode,
            'designation' => $emp->designation->title ?? ($emp->designation_name ?? 'Staff Member'),
            'department' => $emp->department->name ?? 'General Staff',
            'cnic_no' => $emp->national_id_number ?? $emp->cnic ?? '42101-*******-1',
            'joining_date' => $emp->date_of_joining ? date('d-M-Y', strtotime($emp->date_of_joining)) : ($emp->joining_date ? date('d-M-Y', strtotime($emp->joining_date)) : '01-Jan-2024'),
            'bank_name' => $emp->bank_name ?? 'Corporate Payroll Account',
            'bank_account' => $emp->bank_account_number ?? $emp->bank_iban ?? $emp->bank_account ?? 'Direct Bank Transfer',
            'payment_method' => $payslip->payment_method ?? 'Direct Bank Transfer',
            'working_days' => 22,
            'paid_days' => max(0, 22 - (int)($payslip->late_days_deducted ?? 0)),
            'lop_days' => (int)($payslip->late_days_deducted ?? 0),
            'currency' => $currency,
            'base_salary' => (float)$payslip->base_salary,
            'fuel_allowance' => $fuel,
            'special_allowance' => $special,
            'other_allowances' => $otherAllow,
            'gross_salary' => $grossSal,
            'advance_deduction' => $advanceDed,
            'late_deduction' => $lateDed,
            'late_days_deducted' => $lateDays,
            'absent_deduction' => $absentDed,
            'eobi_deduction' => $eobiDed,
            'other_deductions' => $otherDed,
            'tax' => (float)$payslip->tax,
            'gross_deduction' => $grossDed,
            'net_pay' => (float)$payslip->net_pay,
            'net_pay_in_words' => $netPayInWords,
            'notes' => $payslip->notes ?? '',
            'company_legal_name' => $org->name ?? $defName,
            'company_logo' => $org->logo_url ?? asset('images/logo.png'),
            'company_address' => $org->address ?? $defAddress,
            'company_phone' => $org->phone ?? $defPhone,
            'company_email' => $org->email ?? $defEmail,
            'company_website' => $org->website ?? $defWebsite,
            'company_tax_no' => 'NTN: ' . ($org->tax_number ?? '4829104-7'),
        ];

        return view('payroll.payslip-print', compact('slipPayload', 'autoPrint'));
    }

    /**
     * Convert currency amounts into formal English words
     */
    private function convertAmountToWords(float $amount, string $currency = 'PKR'): string
    {
        $amount = round($amount, 2);
        $units = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine', 'Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen'];
        $tens = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];

        $numToStr = function ($num) use (&$numToStr, $units, $tens) {
            $num = (int) $num;
            if ($num < 20) {
                return $units[$num];
            }
            if ($num < 100) {
                return $tens[(int)($num / 10)] . ($num % 10 !== 0 ? ' ' . $units[$num % 10] : '');
            }
            if ($num < 1000) {
                return $units[(int)($num / 100)] . ' Hundred' . ($num % 100 !== 0 ? ' ' . $numToStr($num % 100) : '');
            }
            if ($num < 100000) {
                return $numToStr((int)($num / 1000)) . ' Thousand' . ($num % 1000 !== 0 ? ' ' . $numToStr($num % 1000) : '');
            }
            if ($num < 10000000) {
                return $numToStr((int)($num / 100000)) . ' Lakh' . ($num % 100000 !== 0 ? ' ' . $numToStr($num % 100000) : '');
            }
            return $numToStr((int)($num / 10000000)) . ' Crore' . ($num % 10000000 !== 0 ? ' ' . $numToStr($num % 10000000) : '');
        };

        $whole = (int) floor($amount);
        $fraction = (int) round(($amount - $whole) * 100);

        $currencyName = ($currency === 'PKR') ? 'Rupees' : (($currency === 'USD') ? 'US Dollars' : $currency);
        $fractionName = ($currency === 'PKR') ? 'Paisa' : 'Cents';

        if ($whole === 0) {
            $result = 'Zero ' . $currencyName;
        } else {
            $result = $numToStr($whole) . ' ' . $currencyName;
        }

        if ($fraction > 0) {
            $result .= ' and ' . $numToStr($fraction) . ' ' . $fractionName;
        }

        return $result . ' Only';
    }
}
