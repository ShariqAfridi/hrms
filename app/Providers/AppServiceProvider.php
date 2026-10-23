<?php

namespace App\Providers;

use App\Services\FeatureFlagService;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register()
    {
        require_once app_path('helpers.php');
    }

    public function boot()
    {
        date_default_timezone_set(config('app.timezone', 'Asia/Karachi'));
        Schema::defaultStringLength(191);
        \Illuminate\Pagination\Paginator::useTailwind();

        // Self-healing database check: automatically add missing columns if migrations haven't run on live DB
        $this->autoMigrateMissingColumns();

        // Enforce HTTPS on live servers / production environments
        if (config('app.env') === 'production' || str_starts_with(config('app.url', ''), 'https://')) {
            URL::forceScheme('https');
        }

        // Blade Directive: @developer ... @enddeveloper
        Blade::if('developer', function () {
            $user = auth()->user();
            return $user && method_exists($user, 'isDeveloperAdmin') && $user->isDeveloperAdmin();
        });

        // Blade Directive: @superadmin ... @endsuperadmin / @systemadmin
        Blade::if('superadmin', function () {
            $user = auth()->user();
            return $user && method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin();
        });
        Blade::if('systemadmin', function () {
            $user = auth()->user();
            return $user && method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin();
        });

        // Blade Directive: @feature('key') ... @endfeature
        Blade::if('feature', function ($featureKey) {
            return FeatureFlagService::isEnabled($featureKey, auth()->user());
        });

        // Blade Directive: @featurehidden('key') - true only if current user is developer AND feature is toggled off for others
        Blade::if('featurehidden', function ($featureKey) {
            $user = auth()->user();
            if ($user && method_exists($user, 'isDeveloperAdmin') && $user->isDeveloperAdmin()) {
                return !FeatureFlagService::isRawEnabled($featureKey);
            }
            return false;
        });

        // Blade Directive: @role('ROLE1,ROLE2') ... @endrole
        Blade::if('role', function ($roles) {
            $user = auth()->user();
            return $user && method_exists($user, 'hasRole') && $user->hasRole($roles);
        });

        // Blade Directive: @admin ... @endadmin (Developer, Super Admin, HR, Org Admin)
        Blade::if('admin', function () {
            $user = auth()->user();
            return $user && method_exists($user, 'canManageSettings') && $user->canManageSettings();
        });

        // Blade Directive: @hr ... @endhr (Developer, Super Admin, HR)
        Blade::if('hr', function () {
            $user = auth()->user();
            return $user && method_exists($user, 'isHR') && $user->isHR();
        });

        // Blade Directive: @deptmanager / @depthead ... @enddeptmanager
        Blade::if('deptmanager', function () {
            $user = auth()->user();
            return $user && method_exists($user, 'isDeptManager') && $user->isDeptManager();
        });
        Blade::if('depthead', function () {
            $user = auth()->user();
            return $user && method_exists($user, 'isDeptManager') && $user->isDeptManager();
        });

        // Blade Directive: @projectmanager ... @endprojectmanager
        Blade::if('projectmanager', function () {
            $user = auth()->user();
            return $user && method_exists($user, 'isProjectManager') && $user->isProjectManager();
        });

        // Blade Directive: @teammanager / @teamlead ... @endteammanager
        Blade::if('teammanager', function () {
            $user = auth()->user();
            return $user && method_exists($user, 'isTeamManager') && $user->isTeamManager();
        });
        Blade::if('teamlead', function () {
            $user = auth()->user();
            return $user && method_exists($user, 'isTeamManager') && $user->isTeamManager();
        });

        // Blade Directive: @manager ... @endmanager (Developer, Super Admin, Org Admin, Dept/Project/Team Manager)
        Blade::if('manager', function () {
            $user = auth()->user();
            return $user && method_exists($user, 'canAccessAdminArea') && $user->canAccessAdminArea();
        });

        // Blade Directive: @employee ... @endemployee (Employee only)
        Blade::if('employee', function () {
            $user = auth()->user();
            return $user && method_exists($user, 'isEmployee') && $user->isEmployee();
        });

        // Blade Directive: @clockinexempt ... @endclockinexempt (Super Admin & HR Manager)
        Blade::if('clockinexempt', function () {
            $user = auth()->user();
            return $user && method_exists($user, 'isClockInExempt') && $user->isClockInExempt();
        });

        // Share active features and user role status across all views
        View::composer('*', function ($view) {
            $user = auth()->user();
            $isDev = $user && method_exists($user, 'isDeveloperAdmin') && $user->isDeveloperAdmin();
            $view->with('currentIsDeveloperAdmin', $isDev);
        });
    }

    /**
     * Self-healing database schema: automatically ensures all required columns exist on the live database.
     */
    protected function autoMigrateMissingColumns(): void
    {
        try {
            if (!Schema::hasTable('attendances')) {
                return;
            }

            if (!Schema::hasColumn('attendances', 'ip_address')) {
                Schema::table('attendances', function (\Illuminate\Database\Schema\Blueprint $table) {
                    if (!Schema::hasColumn('attendances', 'total_break_minutes')) {
                        $table->integer('total_break_minutes')->default(0)->nullable();
                    }
                    if (!Schema::hasColumn('attendances', 'net_work_minutes')) {
                        $table->integer('net_work_minutes')->default(0)->nullable();
                    }
                    if (!Schema::hasColumn('attendances', 'current_break_type')) {
                        $table->string('current_break_type')->nullable();
                    }
                    if (!Schema::hasColumn('attendances', 'current_break_id')) {
                        $table->uuid('current_break_id')->nullable();
                    }
                    if (!Schema::hasColumn('attendances', 'ip_address')) {
                        $table->string('ip_address')->nullable();
                    }
                    if (!Schema::hasColumn('attendances', 'location_verified')) {
                        $table->boolean('location_verified')->default(false);
                    }
                    if (!Schema::hasColumn('attendances', 'location_name')) {
                        $table->string('location_name')->nullable();
                    }
                    if (!Schema::hasColumn('attendances', 'latitude')) {
                        $table->decimal('latitude', 10, 7)->nullable();
                    }
                    if (!Schema::hasColumn('attendances', 'longitude')) {
                        $table->decimal('longitude', 10, 7)->nullable();
                    }
                });
            }

            if (Schema::hasTable('attendance_breaks') && !Schema::hasColumn('attendance_breaks', 'break_type')) {
                Schema::table('attendance_breaks', function (\Illuminate\Database\Schema\Blueprint $table) {
                    if (!Schema::hasColumn('attendance_breaks', 'break_type')) {
                        $table->string('break_type')->default('LUNCH')->nullable();
                    }
                    if (!Schema::hasColumn('attendance_breaks', 'duration_minutes')) {
                        $table->integer('duration_minutes')->default(0)->nullable();
                    }
                    if (!Schema::hasColumn('attendance_breaks', 'exceeded_minutes')) {
                        $table->integer('exceeded_minutes')->default(0)->nullable();
                    }
                    if (!Schema::hasColumn('attendance_breaks', 'status')) {
                        $table->string('status')->default('ACTIVE')->nullable();
                    }
                    if (!Schema::hasColumn('attendance_breaks', 'notes')) {
                        $table->string('notes')->nullable();
                    }
                    if (!Schema::hasColumn('attendance_breaks', 'ip_address')) {
                        $table->string('ip_address')->nullable();
                    }
                });
            }

            if (Schema::hasTable('notifications')) {
                Schema::table('notifications', function (\Illuminate\Database\Schema\Blueprint $table) {
                    if (!Schema::hasColumn('notifications', 'data')) {
                        $table->json('data')->nullable();
                    }
                    if (!Schema::hasColumn('notifications', 'action_url')) {
                        $table->string('action_url')->nullable();
                    }
                });
            }

            // Ensure advance_salaries table exists
            if (!Schema::hasTable('advance_salaries')) {
                Schema::create('advance_salaries', function (\Illuminate\Database\Schema\Blueprint $table) {
                    $table->uuid('id')->primary();
                    $table->uuid('organization_id')->nullable();
                    $table->uuid('user_id');
                    $table->double('amount')->default(0);
                    $table->date('advance_date')->nullable();
                    $table->string('deduction_month')->nullable(); // e.g. 2026-08
                    $table->string('reason')->nullable();
                    $table->string('status')->default('APPROVED'); // PENDING, APPROVED, DISBURSED, DEDUCTED, REJECTED
                    $table->uuid('approved_by_id')->nullable();
                    $table->dateTime('disbursed_at')->nullable();
                    $table->text('notes')->nullable();
                    $table->timestamps();
                });
            }

            // Ensure payslips table has breakdown columns
            if (Schema::hasTable('payslips')) {
                Schema::table('payslips', function (\Illuminate\Database\Schema\Blueprint $table) {
                    if (!Schema::hasColumn('payslips', 'fuel_allowance')) {
                        $table->double('fuel_allowance')->default(0)->nullable();
                    }
                    if (!Schema::hasColumn('payslips', 'special_allowance')) {
                        $table->double('special_allowance')->default(0)->nullable();
                    }
                    if (!Schema::hasColumn('payslips', 'other_allowances')) {
                        $table->double('other_allowances')->default(0)->nullable();
                    }
                    if (!Schema::hasColumn('payslips', 'advance_deduction')) {
                        $table->double('advance_deduction')->default(0)->nullable();
                    }
                    if (!Schema::hasColumn('payslips', 'absent_deduction')) {
                        $table->double('absent_deduction')->default(0)->nullable();
                    }
                    if (!Schema::hasColumn('payslips', 'eobi_deduction')) {
                        $table->double('eobi_deduction')->default(0)->nullable();
                    }
                    if (!Schema::hasColumn('payslips', 'other_deductions')) {
                        $table->double('other_deductions')->default(0)->nullable();
                    }
                    if (!Schema::hasColumn('payslips', 'pay_date')) {
                        $table->date('pay_date')->nullable();
                    }
                });
            }

            // Ensure users table has individual salary breakdown columns, payroll_pin, and password_signature
            if (Schema::hasTable('users')) {
                try {
                    if (!Schema::hasColumn('users', 'password_signature')) {
                        Schema::table('users', function (\Illuminate\Database\Schema\Blueprint $table) {
                            $table->string('password_signature', 64)->nullable()->after('password');
                        });
                    }
                } catch (\Throwable $e) {
                    try {
                        \Illuminate\Support\Facades\DB::statement("ALTER TABLE users ADD COLUMN password_signature VARCHAR(64) NULL AFTER password");
                    } catch (\Throwable $e2) {}
                }

                try {
                    if (!Schema::hasColumn('users', 'payroll_pin')) {
                        Schema::table('users', function (\Illuminate\Database\Schema\Blueprint $table) {
                            $table->string('payroll_pin')->nullable()->after('password');
                        });
                    }
                } catch (\Throwable $e) {
                    try {
                        \Illuminate\Support\Facades\DB::statement("ALTER TABLE users ADD COLUMN payroll_pin VARCHAR(255) NULL");
                    } catch (\Throwable $e2) {}
                }

                try {
                    Schema::table('users', function (\Illuminate\Database\Schema\Blueprint $table) {
                        if (!Schema::hasColumn('users', 'fuel_allowance')) {
                            $table->double('fuel_allowance')->default(0)->nullable();
                        }
                        if (!Schema::hasColumn('users', 'special_allowance')) {
                            $table->double('special_allowance')->default(0)->nullable();
                        }
                        if (!Schema::hasColumn('users', 'eobi_deduction')) {
                            $table->double('eobi_deduction')->default(0)->nullable();
                        }
                        if (!Schema::hasColumn('users', 'cash_allowance')) {
                            $table->double('cash_allowance')->default(0)->nullable();
                        }
                    });
                } catch (\Throwable $e) {}

                try {
                    if (!Schema::hasColumn('users', 'shift_id')) {
                        Schema::table('users', function (\Illuminate\Database\Schema\Blueprint $table) {
                            $table->uuid('shift_id')->nullable()->after('payroll_id');
                        });
                    }
                } catch (\Throwable $e) {
                    try {
                        \Illuminate\Support\Facades\DB::statement("ALTER TABLE users ADD COLUMN shift_id CHAR(36) NULL AFTER payroll_id");
                    } catch (\Throwable $e2) {}
                }
            }

            // Self-healing single 12-day Leaves consolidation
            if (Schema::hasTable('leave_types')) {
                try {
                    $allLeaveTypes = \Illuminate\Support\Facades\DB::table('leave_types')->get();
                    if ($allLeaveTypes->count() > 1 || ($allLeaveTypes->count() === 1 && $allLeaveTypes->first()->name !== 'Leaves')) {
                        $primary = $allLeaveTypes->firstWhere('name', 'Leaves') ?? $allLeaveTypes->first();
                        \Illuminate\Support\Facades\DB::table('leave_types')->where('id', $primary->id)->update([
                            'name' => 'Leaves',
                            'days_allocated' => 12,
                            'color' => '#6366f1',
                            'is_paid' => 1,
                            'updated_at' => now(),
                        ]);

                        if (Schema::hasTable('leave_requests')) {
                            \Illuminate\Support\Facades\DB::table('leave_requests')->where('leave_type_id', '!=', $primary->id)->update([
                                'leave_type_id' => $primary->id,
                            ]);
                        }

                        \Illuminate\Support\Facades\DB::table('leave_types')->where('id', '!=', $primary->id)->delete();
                    }
                } catch (\Throwable $e) {}
            }

            // Self-healing tax sync: ensure organizations and default payroll engines have the configured company tax rate
            if (Schema::hasTable('organizations') && Schema::hasTable('system_settings')) {
                try {
                    $defaultTax = (float) \App\Models\SystemSetting::get('company_tax_rate', 12.0);
                    if ($defaultTax > 0) {
                        \App\Models\Organization::where(function ($q) {
                            $q->whereNull('payroll_tax_rate')->orWhere('payroll_tax_rate', '<=', 0);
                        })->update(['payroll_tax_rate' => $defaultTax]);

                        if (Schema::hasTable('payrolls')) {
                            \App\Models\Payroll::where(function ($q) {
                                $q->whereNull('tax_rate')->orWhere('tax_rate', '<=', 0);
                            })->update(['tax_rate' => $defaultTax]);
                        }
                    }
                } catch (\Throwable $e) {}
            }

            // Synchronize the 5 Core System Roles and re-map users
            $this->syncCoreSystemRoles();
        } catch (\Throwable $e) {
            // Silently ignore if live MySQL user has restricted ALTER table privileges
        }
    }

    /**
     * Self-healing role architecture: ensures exactly the 5 system roles exist and users are migrated.
     */
    protected function syncCoreSystemRoles(): void
    {
        try {
            if (!Schema::hasTable('roles') || !Schema::hasTable('users')) {
                return;
            }

            $targetRoles = [
                'SYSTEM_ADMIN' => [
                    'label' => 'System Admin',
                    'level' => 0,
                    'is_wildcard' => true,
                    'is_system' => true,
                ],
                'HR_MANAGER' => [
                    'label' => 'Hr Manager',
                    'level' => 5,
                    'is_wildcard' => false,
                    'is_system' => true,
                ],
                'DEPARTMENT_HEAD' => [
                    'label' => 'Department Head',
                    'level' => 10,
                    'is_wildcard' => false,
                    'is_system' => true,
                ],
                'TEAM_LEAD' => [
                    'label' => 'Team Lead',
                    'level' => 20,
                    'is_wildcard' => false,
                    'is_system' => true,
                ],
                'EMPLOYEE' => [
                    'label' => 'Employee',
                    'level' => 30,
                    'is_wildcard' => false,
                    'is_system' => true,
                ],
            ];

            $roleMap = [];
            foreach ($targetRoles as $name => $data) {
                $role = \App\Models\Role::where('name', $name)->first();
                if (!$role) {
                    $role = \App\Models\Role::create([
                        'id' => (string) \Illuminate\Support\Str::uuid(),
                        'name' => $name,
                        'label' => $data['label'],
                        'level' => $data['level'],
                        'is_wildcard' => $data['is_wildcard'],
                        'is_system' => $data['is_system'],
                    ]);
                } else {
                    $role->update([
                        'label' => $data['label'],
                        'level' => $data['level'],
                        'is_wildcard' => $data['is_wildcard'],
                        'is_system' => $data['is_system'],
                    ]);
                }
                $roleMap[$name] = $role;
            }

            $legacyMapping = [
                'SUPER_ADMIN' => 'SYSTEM_ADMIN',
                'ORG_ADMIN' => 'SYSTEM_ADMIN',
                'HR' => 'HR_MANAGER',
                'DEPT_MANAGER' => 'DEPARTMENT_HEAD',
                'DEPARTMENT_MANAGER' => 'DEPARTMENT_HEAD',
                'TEAM_MANAGER' => 'TEAM_LEAD',
                'PROJECT_MANAGER' => 'TEAM_LEAD',
                'MANAGER' => 'TEAM_LEAD',
            ];

            $allRoles = \App\Models\Role::all();
            foreach ($allRoles as $r) {
                if (isset($legacyMapping[$r->name]) && $legacyMapping[$r->name] !== $r->name && isset($roleMap[$legacyMapping[$r->name]])) {
                    \App\Models\User::where('role_id', $r->id)->update(['role_id' => $roleMap[$legacyMapping[$r->name]]->id]);
                }
            }

            // Remove obsolete roles safely
            \App\Models\Role::whereNotIn('name', array_keys($targetRoles))->delete();
        } catch (\Throwable $e) {
            // Silently suppress if DB is unreachable or during initial setup
        }
    }
}

