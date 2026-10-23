<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id', 'email', 'password', 'name', 'is_active', 'role_id', 'organization_id',
        'department_id', 'team_id', 'designation_id', 'location_id', 'payroll_id', 'shift_id', 'manager_id',
        'bank_account_holder_name', 'bank_name', 'bank_account_number', 'bank_iban',
        'bank_branch_name', 'bank_branch_code', 'bank_swift_code', 'payment_method',
        'base_salary', 'allowances', 'deductions', 'fuel_allowance', 'special_allowance', 'eobi_deduction', 'cash_allowance',
        'phone', 'address', 'emergency_contact_name', 'emergency_contact_phone', 'two_factor_enabled',
        'date_of_birth', 'gender', 'marital_status', 'nationality', 'national_id_type',
        'national_id_number', 'blood_group', 'date_of_joining', 'employment_type',
        'probation_end_date', 'confirmation_date', 'reference_name',
        'reference_relationship', 'reference_phone', 'reference_email',
        'avatar', 'sensitive_changes_count', 'payroll_pin', 'password_signature'
    ];

    protected $appends = ['avatar_url', 'initials'];

    protected $hidden = [
        'password', 'remember_token', 'payroll_pin', 'password_signature'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sensitive_changes_count' => 'integer',
        'two_factor_enabled' => 'boolean',
        'base_salary' => 'float',
        'allowances' => 'float',
        'deductions' => 'float',
        'fuel_allowance' => 'float',
        'special_allowance' => 'float',
        'eobi_deduction' => 'float',
        'cash_allowance' => 'float',
        'date_of_birth' => 'datetime',
        'date_of_joining' => 'datetime',
        'probation_end_date' => 'datetime',
        'confirmation_date' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });

        static::saving(function ($model) {
            if ($model->isDirty('password') && !empty($model->password)) {
                if (empty($model->id)) {
                    $model->id = (string) Str::uuid();
                }
                try {
                    if (\Illuminate\Support\Facades\Schema::hasColumn('users', 'password_signature')) {
                        $model->password_signature = $model->generatePasswordSignature();
                    }
                } catch (\Throwable $e) {}
            }
        });

        static::deleting(function ($model) {
            // 1. Unlink any references where this user is assigned as manager/leader/approver
            \Illuminate\Support\Facades\DB::table('users')->where('manager_id', $model->id)->update(['manager_id' => null]);
            if (\Illuminate\Support\Facades\Schema::hasTable('departments') && \Illuminate\Support\Facades\Schema::hasColumn('departments', 'head_user_id')) {
                \Illuminate\Support\Facades\DB::table('departments')->where('head_user_id', $model->id)->update(['head_user_id' => null]);
            }
            if (\Illuminate\Support\Facades\Schema::hasTable('payrolls') && \Illuminate\Support\Facades\Schema::hasColumn('payrolls', 'payroll_manager_id')) {
                \Illuminate\Support\Facades\DB::table('payrolls')->where('payroll_manager_id', $model->id)->update(['payroll_manager_id' => null]);
            }
            if (\Illuminate\Support\Facades\Schema::hasTable('projects') && \Illuminate\Support\Facades\Schema::hasColumn('projects', 'project_manager_id')) {
                \Illuminate\Support\Facades\DB::table('projects')->where('project_manager_id', $model->id)->update(['project_manager_id' => null]);
            }
            if (\Illuminate\Support\Facades\Schema::hasTable('teams') && \Illuminate\Support\Facades\Schema::hasColumn('teams', 'leader_id')) {
                \Illuminate\Support\Facades\DB::table('teams')->where('leader_id', $model->id)->update(['leader_id' => null]);
            }
            if (\Illuminate\Support\Facades\Schema::hasTable('payroll_runs')) {
                foreach (['reviewed_by_id', 'approved_by_id', 'finalized_by_id', 'released_by_id', 'executed_by'] as $col) {
                    if (\Illuminate\Support\Facades\Schema::hasColumn('payroll_runs', $col)) {
                        \Illuminate\Support\Facades\DB::table('payroll_runs')->where($col, $model->id)->update([$col => null]);
                    }
                }
            }

            // 2. Cascade delete direct employee records
            if (\Illuminate\Support\Facades\Schema::hasTable('attendance_breaks') && \Illuminate\Support\Facades\Schema::hasTable('attendances')) {
                $attIds = \Illuminate\Support\Facades\DB::table('attendances')->where('user_id', $model->id)->pluck('id');
                if ($attIds->isNotEmpty()) {
                    \Illuminate\Support\Facades\DB::table('attendance_breaks')->whereIn('attendance_id', $attIds)->delete();
                }
            }

            $userTables = [
                'payslips', 'advance_salaries', 'attendances', 'leave_requests', 'leaves',
                'notifications', 'notification_preferences', 'tasks', 'project_user',
                'bonuses', 'loan_repayments', 'loans', 'overtime_requests',
                'employee_payroll_histories', 'salary_components', 'documents', 'asset_assignments',
                'profile_change_requests', 'payslip_requests'
            ];
            foreach ($userTables as $t) {
                if (\Illuminate\Support\Facades\Schema::hasTable($t)) {
                    if (\Illuminate\Support\Facades\Schema::hasColumn($t, 'user_id')) {
                        \Illuminate\Support\Facades\DB::table($t)->where('user_id', $model->id)->delete();
                    }
                    if (\Illuminate\Support\Facades\Schema::hasColumn($t, 'approved_by_id')) {
                        \Illuminate\Support\Facades\DB::table($t)->where('approved_by_id', $model->id)->update(['approved_by_id' => null]);
                    }
                    if (\Illuminate\Support\Facades\Schema::hasColumn($t, 'recorded_by_id')) {
                        \Illuminate\Support\Facades\DB::table($t)->where('recorded_by_id', $model->id)->update(['recorded_by_id' => null]);
                    }
                    if (\Illuminate\Support\Facades\Schema::hasColumn($t, 'changed_by_id')) {
                        \Illuminate\Support\Facades\DB::table($t)->where('changed_by_id', $model->id)->update(['changed_by_id' => null]);
                    }
                }
            }
            if (\Illuminate\Support\Facades\Schema::hasTable('loan_settlements') && \Illuminate\Support\Facades\Schema::hasColumn('loan_settlements', 'approved_by_id')) {
                \Illuminate\Support\Facades\DB::table('loan_settlements')->where('approved_by_id', $model->id)->update(['approved_by_id' => null]);
            }
            if (\Illuminate\Support\Facades\Schema::hasTable('interviews') && \Illuminate\Support\Facades\Schema::hasColumn('interviews', 'interviewer_id')) {
                \Illuminate\Support\Facades\DB::table('interviews')->where('interviewer_id', $model->id)->delete();
            }
        });
    }

    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public static function getDeveloperEmailsList(): array
    {
        $rawConfig = config('app.developer_email', 'devel,devel@axecorp,devel@axecorp.com,ateeq@axecorp,ateeq@axecorp.com');
        $developerEmails = array_filter(array_map('trim', explode(',', (string) $rawConfig)));

        try {
            $dbDeveloperEmails = SystemSetting::get('developer_admin_emails');
            if ($dbDeveloperEmails) {
                $dbEmails = array_filter(array_map('trim', explode(',', (string) $dbDeveloperEmails)));
                $developerEmails = array_merge($developerEmails, $dbEmails);
            }
        } catch (\Throwable $e) {
            // Fallback during initial setup
        }

        return array_values(array_unique(array_map('strtolower', $developerEmails)));
    }

    /**
     * Scope to exclude developer/system testing accounts from public frontend listings and operational records.
     */
    public function scopeExcludeDevs($query)
    {
        $devEmails = static::getDeveloperEmailsList();
        return $query->whereNotIn('email', $devEmails);
    }

    public function getAvatarUrlAttribute(): ?string
    {
        if (empty($this->avatar)) {
            return null;
        }

        if (str_starts_with($this->avatar, 'data:') || str_starts_with($this->avatar, 'http://') || str_starts_with($this->avatar, 'https://')) {
            return $this->avatar;
        }

        $cleanPath = ltrim($this->avatar, '/\\');
        if (file_exists(public_path($cleanPath))) {
            return asset($cleanPath);
        }

        if (file_exists(public_path('uploads/avatars/' . $cleanPath))) {
            return asset('uploads/avatars/' . $cleanPath);
        }

        return asset($cleanPath);
    }

    public function getInitialsAttribute(): string
    {
        $name = trim((string) $this->name);
        if (empty($name)) {
            return 'U';
        }

        $words = explode(' ', $name);
        if (count($words) >= 2) {
            return strtoupper(substr($words[0], 0, 1) . substr(end($words), 0, 1));
        }

        return strtoupper(substr($name, 0, 1));
    }

    public function isDeveloperAdmin(): bool
    {
        $currentEmail = strtolower(trim((string) $this->email));
        return in_array($currentEmail, static::getDeveloperEmailsList(), true);
    }

    public function isSuperAdmin(): bool
    {
        if ($this->isDeveloperAdmin()) {
            return true;
        }

        if (!$this->relationLoaded('role')) {
            $this->load('role');
        }

        return $this->role && (in_array(strtoupper(trim((string)$this->role->name)), ['SYSTEM_ADMIN', 'SUPER_ADMIN']) || $this->role->is_wildcard || $this->role->level === 0);
    }

    public function isSystemAdmin(): bool
    {
        return $this->isSuperAdmin();
    }

    public function hasRole($roles): bool
    {
        if ($this->isDeveloperAdmin() || $this->isSuperAdmin()) {
            return true;
        }

        if (!$this->relationLoaded('role')) {
            $this->load('role');
        }

        if (!$this->role) {
            return false;
        }

        if (is_string($roles)) {
            $roles = array_map('trim', explode(',', $roles));
        }

        $userRoleName = strtoupper(trim((string)$this->role->name));

        $roleAliases = [
            'SYSTEM_ADMIN' => ['SYSTEM_ADMIN', 'SUPER_ADMIN', 'ORG_ADMIN', 'ADMIN'],
            'HR_MANAGER' => ['HR_MANAGER', 'HR', 'HR_ADMIN'],
            'DEPARTMENT_HEAD' => ['DEPARTMENT_HEAD', 'DEPT_MANAGER', 'DEPARTMENT_MANAGER'],
            'TEAM_LEAD' => ['TEAM_LEAD', 'TEAM_MANAGER', 'PROJECT_MANAGER', 'PROJECT_LEAD'],
            'EMPLOYEE' => ['EMPLOYEE'],
        ];

        $myValidNames = $roleAliases[$userRoleName] ?? [$userRoleName];

        foreach ($roles as $r) {
            $check = strtoupper(trim((string)$r));
            if (in_array($check, $myValidNames)) {
                return true;
            }
            if ($check === 'MANAGER' && $this->isManager()) {
                return true;
            }
        }

        return false;
    }

    public function hasPermission(string $permissionKey): bool
    {
        if ($this->isDeveloperAdmin() || $this->isSuperAdmin()) {
            return true;
        }

        if (!$this->relationLoaded('role.permissions')) {
            $this->load('role.permissions');
        }

        if (!$this->role) {
            return false;
        }

        if ($this->role->is_wildcard) {
            return true;
        }

        return $this->role->permissions->contains('key', $permissionKey);
    }

    public function isHR(): bool
    {
        if ($this->isDeveloperAdmin() || $this->isSuperAdmin()) {
            return true;
        }

        if (!$this->relationLoaded('role')) {
            $this->load('role');
        }

        return $this->role && (in_array(strtoupper(trim((string)$this->role->name)), ['HR_MANAGER', 'HR']) || $this->role->level <= 5);
    }

    public function isHrManager(): bool
    {
        return $this->isHR();
    }

    public function isDeptManager(): bool
    {
        if ($this->isDeveloperAdmin() || $this->isSuperAdmin() || $this->isHR()) {
            return true;
        }

        if (!$this->relationLoaded('role')) {
            $this->load('role');
        }

        if ($this->role && (in_array(strtoupper(trim((string)$this->role->name)), ['DEPARTMENT_HEAD', 'DEPT_MANAGER', 'DEPARTMENT_MANAGER', 'ORG_ADMIN']) || $this->role->level <= 10)) {
            return true;
        }

        return Department::where('head_user_id', $this->id)->exists();
    }

    public function isDepartmentHead(): bool
    {
        return $this->isDeptManager();
    }

    public function isProjectManager(): bool
    {
        if ($this->isDeveloperAdmin() || $this->isSuperAdmin() || $this->isHR() || $this->isDeptManager()) {
            return true;
        }

        if (!$this->relationLoaded('role')) {
            $this->load('role');
        }

        return $this->role && (in_array(strtoupper(trim((string)$this->role->name)), ['TEAM_LEAD', 'PROJECT_MANAGER', 'PROJECT_LEAD']) || $this->role->level <= 20);
    }

    public function isTeamManager(): bool
    {
        if ($this->isDeveloperAdmin() || $this->isSuperAdmin() || $this->isHR() || $this->isDeptManager()) {
            return true;
        }

        if (!$this->relationLoaded('role')) {
            $this->load('role');
        }

        if ($this->role && (in_array(strtoupper(trim((string)$this->role->name)), ['TEAM_LEAD', 'TEAM_MANAGER', 'PROJECT_MANAGER', 'MANAGER']) || $this->role->level <= 20)) {
            return true;
        }

        return Team::where('leader_id', $this->id)->exists();
    }

    public function isTeamLead(): bool
    {
        return $this->isTeamManager();
    }

    public function isOrgAdmin(): bool
    {
        if ($this->isDeveloperAdmin() || $this->isSuperAdmin()) {
            return true;
        }

        if (!$this->relationLoaded('role')) {
            $this->load('role');
        }

        return $this->role && (in_array(strtoupper(trim((string)$this->role->name)), ['SYSTEM_ADMIN', 'ORG_ADMIN']) || $this->role->level <= 0);
    }

    public function isAdmin(): bool
    {
        return $this->isDeveloperAdmin() || $this->isSuperAdmin() || $this->isHR() || $this->isOrgAdmin();
    }

    public function isManager(): bool
    {
        return $this->isDeveloperAdmin() || $this->isSuperAdmin() || $this->isHR() || $this->isDeptManager() || $this->isTeamManager();
    }

    public function isEmployee(): bool
    {
        return !$this->isTeamManager() && !$this->isDeptManager() && !$this->isHR() && !$this->isSuperAdmin() && !$this->isDeveloperAdmin();
    }

    /**
     * Determine if user is exempt from shift clock-in routines (Super Admin and HR Manager).
     * Super Admin and HR do not clock in; their role is administrative oversight and auditing others.
     */
    public function isClockInExempt(): bool
    {
        return $this->isDeveloperAdmin() || $this->isSuperAdmin() || $this->isHR();
    }

    public function canAccessAdminArea(): bool
    {
        return $this->isAdmin() || $this->isDeptManager() || $this->isTeamManager();
    }

    public function canManageSettings(): bool
    {
        return $this->isDeveloperAdmin() || $this->isSuperAdmin() || $this->isHR();
    }

    public function canManageCompanies(): bool
    {
        return $this->isDeveloperAdmin() || $this->isSuperAdmin();
    }

    public function canManageEmployees(): bool
    {
        return $this->isAdmin() || $this->isDeptManager() || $this->isTeamManager();
    }

    public function canManagePayroll(): bool
    {
        return $this->isDeveloperAdmin() || $this->isSuperAdmin() || $this->isHR();
    }

    public function canDirectlyApproveLeave(): bool
    {
        return $this->isDeveloperAdmin() || $this->isSuperAdmin() || $this->isHR() || $this->isDeptManager();
    }

    public function canForwardLeave(): bool
    {
        return $this->isDeveloperAdmin() || $this->isSuperAdmin() || $this->isHR() || $this->isDeptManager() || $this->isTeamManager();
    }

    public function canRevokeLeave(): bool
    {
        return $this->isDeveloperAdmin() || $this->isSuperAdmin() || $this->isHR();
    }

    public function canApproveLeaves(): bool
    {
        return $this->canDirectlyApproveLeave() || $this->canForwardLeave();
    }

    public function canManageTeams(): bool
    {
        return $this->isDeveloperAdmin() || $this->isSuperAdmin() || $this->isHR() || $this->isDeptManager();
    }

    public function isTeamLeader(): bool
    {
        return Team::where('leader_id', $this->id)->exists() || ($this->role && in_array(strtoupper(trim((string)$this->role->name)), ['TEAM_LEAD', 'TEAM_MANAGER']));
    }

    public function managedDepartment()
    {
        return $this->hasOne(Department::class, 'head_user_id');
    }

    public function ledTeam()
    {
        return $this->hasOne(Team::class, 'leader_id');
    }

    public function ledTeams()
    {
        return $this->hasMany(Team::class, 'leader_id');
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class, 'organization_id');
    }

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function team()
    {
        return $this->belongsTo(Team::class, 'team_id');
    }

    public function designation()
    {
        return $this->belongsTo(Designation::class, 'designation_id');
    }

    public function shift()
    {
        return $this->belongsTo(Shift::class, 'shift_id');
    }

    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function productionManager()
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function managedProductionStaff()
    {
        return $this->hasMany(User::class, 'manager_id')->excludeDevs();
    }

    public function payroll()
    {
        return $this->belongsTo(Payroll::class, 'payroll_id');
    }

    public function subordinates()
    {
        return $this->hasMany(User::class, 'manager_id')->excludeDevs();
    }

    public function projects()
    {
        return $this->belongsToMany(Project::class, 'project_user')
                    ->withPivot('role_in_project', 'allocation_percentage')
                    ->withTimestamps();
    }

    public function tasks()
    {
        return $this->hasMany(Task::class, 'user_id');
    }

    public function inAppNotifications()
    {
        return $this->hasMany(Notification::class, 'user_id')->orderBy('created_at', 'desc');
    }

    public function unreadInAppNotifications()
    {
        return $this->hasMany(Notification::class, 'user_id')->where('is_read', false)->orderBy('created_at', 'desc');
    }

    public function advanceSalaries()
    {
        return $this->hasMany(AdvanceSalary::class, 'user_id')->orderBy('created_at', 'desc');
    }

    public function payslips()
    {
        return $this->hasMany(Payslip::class, 'user_id')->orderBy('created_at', 'desc');
    }

    public function profileChangeRequests()
    {
        return $this->hasMany(ProfileChangeRequest::class, 'user_id')->orderBy('created_at', 'desc');
    }

    public function pendingProfileChangeRequest()
    {
        return $this->hasOne(ProfileChangeRequest::class, 'user_id')->where('status', 'PENDING')->latestOfMany();
    }

    public function payslipRequests()
    {
        return $this->hasMany(PayslipRequest::class, 'user_id')->orderBy('created_at', 'desc');
    }

    public function hasPayrollPin(): bool
    {
        return !empty($this->payroll_pin);
    }

    public function verifyPayrollPin(string $pin): bool
    {
        if (empty($this->payroll_pin)) {
            return true;
        }
        return \Illuminate\Support\Facades\Hash::check($pin, $this->payroll_pin);
    }

    /**
     * Compute HMAC cryptographic signature for the password hash bound to user identity
     */
    public function generatePasswordSignature(): string
    {
        $appKey = (string) config('app.key');
        if (str_starts_with($appKey, 'base64:')) {
            $appKey = base64_decode(substr($appKey, 7));
        }
        $payload = (string)$this->id . '|' . strtolower(trim((string)$this->email)) . '|' . (string)$this->password;
        return hash_hmac('sha256', $payload, $appKey);
    }

    /**
     * Verify that current password hash matches its cryptographic signature
     */
    public function verifyPasswordSignature(): bool
    {
        try {
            if (!\Illuminate\Support\Facades\Schema::hasColumn('users', 'password_signature')) {
                return true;
            }
            if (empty($this->password_signature)) {
                return true;
            }
            $expected = $this->generatePasswordSignature();
            return hash_equals($expected, (string)$this->password_signature);
        } catch (\Throwable $e) {
            return true;
        }
    }

    /**
     * Force refresh and persist the cryptographic password signature
     */
    public function refreshPasswordSignature(): void
    {
        try {
            if (\Illuminate\Support\Facades\Schema::hasColumn('users', 'password_signature')) {
                $this->password_signature = $this->generatePasswordSignature();
                $this->saveQuietly();
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning("Could not refresh password signature: " . $e->getMessage());
        }
    }
}
