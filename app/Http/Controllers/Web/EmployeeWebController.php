<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Organization;
use App\Models\Payroll;
use App\Models\Payslip;
use App\Models\Role;
use App\Models\Shift;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class EmployeeWebController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        if (!$user || (!$user->isDeveloperAdmin() && !$user->isSuperAdmin() && !$user->isOrgAdmin() && !$user->isHR())) {
            return redirect()->route('dashboard')->with('error', 'Access restricted. Only administrators, HR, and super administrators can view the employees directory.');
        }

        $query = User::excludeDevs()->with(['role', 'organization', 'department', 'designation', 'payroll', 'team.leader', 'shift']);

        if ($user->isOrgAdmin() && !$user->isSuperAdmin() && $user->organization_id) {
            $query->where('organization_id', $user->organization_id);
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('organization_id')) {
            $query->where('organization_id', $request->input('organization_id'));
        }

        if ($request->filled('department_id')) {
            $query->where('department_id', $request->input('department_id'));
        }

        if ($request->filled('team_id')) {
            $query->where('team_id', $request->input('team_id'));
        }

        if ($request->filled('employment_type')) {
            $query->where('employment_type', $request->input('employment_type'));
        }

        $employees = $query->orderBy('name', 'asc')->paginate(10);
        $departments = Department::all();
        $designations = Designation::all();
        $roles = Role::orderBy('level', 'asc')->get();
        $organizations = Organization::all();
        $payrolls = Payroll::with('organization')->where('status', 'ACTIVE')->get();
        $teams = Team::with('leader')->orderBy('name')->get();
        $shifts = Shift::with('organization')->orderBy('name', 'asc')->get();

        return view('employees.index', compact('employees', 'departments', 'designations', 'roles', 'organizations', 'payrolls', 'teams', 'shifts'));
    }

    public function store(Request $request)
    {
        // Auto-normalize Pakistani CNIC format (XXXXX-XXXXXXX-X) if 13 raw digits are passed
        if ($request->has('national_id_number')) {
            $rawCnic = preg_replace('/\D/', '', (string) $request->input('national_id_number'));
            if (strlen($rawCnic) === 13) {
                $request->merge([
                    'national_id_number' => substr($rawCnic, 0, 5) . '-' . substr($rawCnic, 5, 7) . '-' . substr($rawCnic, 12, 1)
                ]);
            }
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
            'role_id' => 'required|exists:roles,id',
            'organization_id' => 'required|exists:organizations,id',
            'department_id' => 'nullable|exists:departments,id',
            'team_id' => 'nullable|exists:teams,id',
            'designation_id' => 'nullable|exists:designations,id',
            'payroll_id' => 'nullable|exists:payrolls,id',
            'shift_id' => 'nullable|exists:shifts,id',
            'employment_type' => 'required|string',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:500',
            'national_id_number' => ['required', 'string', 'regex:/^\d{5}-\d{7}-\d{1}$/'],
            'date_of_joining' => 'nullable|date',
            'payment_method' => 'nullable|string|max:50',
            'bank_name' => 'nullable|string|max:100',
            'bank_account_holder_name' => 'nullable|string|max:150',
            'bank_account_number' => 'nullable|string|max:100',
            'bank_iban' => 'nullable|string|max:100',
            'bank_branch_name' => 'nullable|string|max:100',
            'base_salary' => 'nullable|numeric|min:0',
            'fuel_allowance' => 'nullable|numeric|min:0',
            'special_allowance' => 'nullable|numeric|min:0',
            'allowances' => 'nullable|numeric|min:0',
            'eobi_deduction' => 'nullable|numeric|min:0',
            'deductions' => 'nullable|numeric|min:0',
            'cash_allowance' => 'nullable|numeric|min:0',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,webp,svg,gif|max:4096',
        ], [
            'national_id_number.regex' => 'The CNIC number must follow the 13-digit pattern XXXXX-XXXXXXX-X (e.g. 42101-1234567-1).',
            'national_id_number.required' => 'The CNIC / National ID Number is required.',
        ]);

        try {
            if ($request->hasFile('avatar')) {
                $file = $request->file('avatar');
                $uploadDir = public_path('uploads/avatars');
                if (!is_dir($uploadDir)) {
                    @mkdir($uploadDir, 0755, true);
                }
                $filename = 'avatar_' . time() . '_' . Str::random(8) . '.' . $file->getClientOriginalExtension();
                $file->move($uploadDir, $filename);
                $validated['avatar'] = 'uploads/avatars/' . $filename;
            }

            $validated['password'] = Hash::make($validated['password']);
            $validated['is_active'] = true;
            $validated['date_of_joining'] = $validated['date_of_joining'] ?? now();
            $validated['base_salary'] = $validated['base_salary'] ?? 0;
            $validated['fuel_allowance'] = $validated['fuel_allowance'] ?? 0;
            $validated['special_allowance'] = $validated['special_allowance'] ?? 0;
            $validated['allowances'] = $validated['allowances'] ?? ($validated['fuel_allowance'] + $validated['special_allowance']);
            $validated['eobi_deduction'] = $validated['eobi_deduction'] ?? 0;
            $validated['deductions'] = $validated['deductions'] ?? $validated['eobi_deduction'];
            $validated['cash_allowance'] = $validated['cash_allowance'] ?? 0;

            foreach (['department_id', 'team_id', 'designation_id', 'payroll_id', 'shift_id'] as $fkField) {
                if (isset($validated[$fkField]) && empty($validated[$fkField])) {
                    $validated[$fkField] = null;
                }
            }

            if (!\Illuminate\Support\Facades\Schema::hasColumn('users', 'shift_id')) {
                unset($validated['shift_id']);
            }

            User::create($validated);

            return redirect()->route('employees.index')->with('success', 'Employee registered successfully with complete profile & banking setup!');
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', 'Could not create employee: ' . $e->getMessage());
        }
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        // Auto-normalize Pakistani CNIC format (XXXXX-XXXXXXX-X) if 13 raw digits are passed
        if ($request->has('national_id_number')) {
            $rawCnic = preg_replace('/\D/', '', (string) $request->input('national_id_number'));
            if (strlen($rawCnic) === 13) {
                $request->merge([
                    'national_id_number' => substr($rawCnic, 0, 5) . '-' . substr($rawCnic, 5, 7) . '-' . substr($rawCnic, 12, 1)
                ]);
            }
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:6',
            'role_id' => 'required|exists:roles,id',
            'organization_id' => 'required|exists:organizations,id',
            'department_id' => 'nullable|exists:departments,id',
            'team_id' => 'nullable|exists:teams,id',
            'designation_id' => 'nullable|exists:designations,id',
            'payroll_id' => 'nullable|exists:payrolls,id',
            'shift_id' => 'nullable|exists:shifts,id',
            'employment_type' => 'required|string',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:500',
            'national_id_number' => ['required', 'string', 'regex:/^\d{5}-\d{7}-\d{1}$/'],
            'date_of_joining' => 'nullable|date',
            'payment_method' => 'nullable|string|max:50',
            'bank_name' => 'nullable|string|max:100',
            'bank_account_holder_name' => 'nullable|string|max:150',
            'bank_account_number' => 'nullable|string|max:100',
            'bank_iban' => 'nullable|string|max:100',
            'bank_branch_name' => 'nullable|string|max:100',
            'base_salary' => 'nullable|numeric|min:0',
            'fuel_allowance' => 'nullable|numeric|min:0',
            'special_allowance' => 'nullable|numeric|min:0',
            'allowances' => 'nullable|numeric|min:0',
            'eobi_deduction' => 'nullable|numeric|min:0',
            'deductions' => 'nullable|numeric|min:0',
            'cash_allowance' => 'nullable|numeric|min:0',
            'is_active' => 'nullable|boolean',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,webp,svg,gif|max:4096',
        ], [
            'national_id_number.regex' => 'The CNIC number must follow the 13-digit pattern XXXXX-XXXXXXX-X (e.g. 42101-1234567-1).',
            'national_id_number.required' => 'The CNIC / National ID Number is required.',
        ]);

        try {
            if ($request->hasFile('avatar')) {
                $file = $request->file('avatar');
                $uploadDir = public_path('uploads/avatars');
                if (!is_dir($uploadDir)) {
                    @mkdir($uploadDir, 0755, true);
                }
                if ($user->avatar && file_exists(public_path($user->avatar))) {
                    @unlink(public_path($user->avatar));
                }
                $filename = 'avatar_' . $user->id . '_' . time() . '.' . $file->getClientOriginalExtension();
                $file->move($uploadDir, $filename);
                $validated['avatar'] = 'uploads/avatars/' . $filename;
            }

            if (!empty($validated['password'])) {
                $validated['password'] = Hash::make($validated['password']);
            } else {
                unset($validated['password']);
            }

            $validated['is_active'] = $request->has('is_active') ? (bool)$request->input('is_active') : true;
            $validated['base_salary'] = $validated['base_salary'] ?? 0;
            $validated['fuel_allowance'] = $validated['fuel_allowance'] ?? 0;
            $validated['special_allowance'] = $validated['special_allowance'] ?? 0;
            $validated['allowances'] = $validated['allowances'] ?? ($validated['fuel_allowance'] + $validated['special_allowance']);
            $validated['eobi_deduction'] = $validated['eobi_deduction'] ?? 0;
            $validated['deductions'] = $validated['deductions'] ?? $validated['eobi_deduction'];
            $validated['cash_allowance'] = $validated['cash_allowance'] ?? 0;
            $validated['team_id'] = $request->filled('team_id') ? $request->input('team_id') : null;
            $validated['shift_id'] = $request->filled('shift_id') ? $request->input('shift_id') : null;

            foreach (['department_id', 'team_id', 'designation_id', 'payroll_id', 'shift_id'] as $fkField) {
                if (array_key_exists($fkField, $validated) && empty($validated[$fkField])) {
                    $validated[$fkField] = null;
                }
            }

            if (!\Illuminate\Support\Facades\Schema::hasColumn('users', 'shift_id')) {
                unset($validated['shift_id']);
            }

            $user->update($validated);

            // Auto-recalculate any active payslips for this employee
            $payroll = $user->payroll;
            $taxRate = (float) (
                ($payroll && (float)$payroll->tax_rate > 0) ? $payroll->tax_rate :
                ($user->organization->payroll_tax_rate ?? \App\Models\SystemSetting::get('company_tax_rate', 12.0))
            );
            $taxMultiplier = $taxRate / 100;
            $base = (float) ($user->base_salary ?? 0);
            $allowances = (float) ($user->allowances ?? 0);
            $deductions = (float) ($user->deductions ?? 0);
            $taxableIncome = max(0, $base + $allowances - $deductions);
            $tax = round($taxableIncome * $taxMultiplier, 2);
            $netPay = max(0, $base + $allowances - $deductions - $tax);

            Payslip::where('user_id', $user->id)->update([
                'base_salary' => $base,
                'allowances' => $allowances,
                'fuel_allowance' => (float) ($user->fuel_allowance ?? 0),
                'special_allowance' => (float) ($user->special_allowance ?? 0),
                'eobi_deduction' => (float) ($user->eobi_deduction ?? 0),
                'deductions' => $deductions,
                'tax' => $tax,
                'net_pay' => $netPay,
            ]);

            return redirect()->route('employees.index')->with('success', 'Employee profile updated and payslips synchronized successfully!');
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', 'Could not update employee: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            $user = User::findOrFail($id);

            // Prevent deleting the currently authenticated user
            if (Auth::id() === $user->id) {
                return back()->with('error', 'You cannot delete your own account while logged in.');
            }

            \Illuminate\Support\Facades\DB::transaction(function () use ($user) {
                $user->delete();
            });

            return redirect()->route('employees.index')->with('success', 'Employee ' . $user->name . ' and all linked records deleted successfully!');
        } catch (\Throwable $e) {
            return back()->with('error', 'Could not delete employee: ' . $e->getMessage());
        }
    }
}
