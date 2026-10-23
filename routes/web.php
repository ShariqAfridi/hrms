<?php

use App\Http\Controllers\Web\AiAgentController;
use App\Http\Controllers\Web\AttendanceWebController;
use App\Http\Controllers\Web\CompanyWebController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\EmployeeWebController;
use App\Http\Controllers\Web\LeaveWebController;
use App\Http\Controllers\Web\PayrollWebController;
use App\Http\Controllers\Web\TeamWebController;
use App\Http\Controllers\Web\WebAuthController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return Auth::check() ? redirect()->route('dashboard') : redirect()->route('login');
});

Route::get('/login', [WebAuthController::class, 'showLogin'])->name('login');
Route::post('/login', [WebAuthController::class, 'login'])->middleware('throttle:10,1');
Route::match(['get', 'post'], '/logout', [WebAuthController::class, 'logout'])->name('logout');

// Password Reset Routes
Route::get('/forgot-password', [WebAuthController::class, 'showForgotPassword'])->name('password.request');
Route::post('/forgot-password', [WebAuthController::class, 'sendResetLink'])->name('password.email');
Route::get('/reset-password/{token}', [WebAuthController::class, 'showResetPassword'])->name('password.reset');
Route::post('/reset-password', [WebAuthController::class, 'resetPassword'])->name('password.update');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Developer-Only Feature Switchboard & Custom Feature Management
    Route::middleware('developer.admin')->group(function () {
        Route::post('/developer/features/toggle', [\App\Http\Controllers\Web\SettingsWebController::class, 'toggleFeature'])->name('developer.features.toggle');
        Route::post('/developer/features/custom', [\App\Http\Controllers\Web\SettingsWebController::class, 'storeCustomFeature'])->name('developer.features.store-custom');
        Route::delete('/developer/features/custom/{key}', [\App\Http\Controllers\Web\SettingsWebController::class, 'destroyCustomFeature'])->name('developer.features.destroy-custom');
    });

    // AI Intelligence Search Agent Route (Strictly Super Admin and Developer Admin)
    Route::middleware(['feature:ai_copilot', 'role:SYSTEM_ADMIN,SUPER_ADMIN'])->group(function () {
        Route::post('/ai/chat', [AiAgentController::class, 'query'])->name('ai.chat');
    });

    // Company Management Routes (System Admin only)
    Route::middleware(['feature:companies', 'role:SYSTEM_ADMIN,SUPER_ADMIN'])->group(function () {
        Route::get('/companies', [CompanyWebController::class, 'index'])->name('companies.index');
        Route::post('/companies', [CompanyWebController::class, 'store'])->name('companies.store');
        Route::put('/companies/{id}', [CompanyWebController::class, 'update'])->name('companies.update');
        Route::delete('/companies/{id}', [CompanyWebController::class, 'destroy'])->name('companies.destroy');
    });

    // Employee Routes (System Admin & Hr Manager)
    Route::middleware(['feature:employees', 'role:SYSTEM_ADMIN,HR_MANAGER,SUPER_ADMIN,HR'])->group(function () {
        Route::get('/employees', [EmployeeWebController::class, 'index'])->name('employees.index');
        Route::post('/employees', [EmployeeWebController::class, 'store'])->name('employees.store');
        Route::put('/employees/{id}', [EmployeeWebController::class, 'update'])->name('employees.update');
        Route::delete('/employees/{id}', [EmployeeWebController::class, 'destroy'])->name('employees.destroy');

        // Quick Shift Management for Employee Onboarding
        Route::post('/employees/shifts', [\App\Http\Controllers\Web\SettingsWebController::class, 'storeShift'])->name('employees.shifts.store');
        Route::put('/employees/shifts/{id}', [\App\Http\Controllers\Web\SettingsWebController::class, 'updateShift'])->name('employees.shifts.update');
    });

    // Teams & Squad Management Routes (Admins, Team Leaders & Employees)
    Route::middleware('feature:teams')->group(function () {
        Route::get('/teams', [TeamWebController::class, 'index'])->name('teams.index');
        Route::post('/teams', [TeamWebController::class, 'store'])->name('teams.store');
        Route::put('/teams/{id}', [TeamWebController::class, 'update'])->name('teams.update');
        Route::delete('/teams/{id}', [TeamWebController::class, 'destroy'])->name('teams.destroy');
        Route::post('/teams/{id}/members', [TeamWebController::class, 'addMembers'])->name('teams.members.add');
        Route::delete('/teams/{id}/members/{userId}', [TeamWebController::class, 'removeMember'])->name('teams.members.remove');
    });

    // Attendance & Break Tracking Routes
    Route::middleware('feature:attendance')->group(function () {
        Route::get('/attendance', [AttendanceWebController::class, 'index'])->name('attendance.index');
        Route::post('/attendance/clock-in', [AttendanceWebController::class, 'clockIn'])->name('attendance.clock-in');
        Route::post('/attendance/clock-out', [AttendanceWebController::class, 'clockOut'])->name('attendance.clock-out');
        Route::post('/attendance/start-break', [AttendanceWebController::class, 'startBreak'])->name('attendance.start-break');
        Route::post('/attendance/end-break', [AttendanceWebController::class, 'endBreak'])->name('attendance.end-break');
        Route::get('/attendance/{id}/break-logs', [AttendanceWebController::class, 'getBreakLogs'])->name('attendance.break-logs');
        
        Route::middleware('role:SYSTEM_ADMIN,HR_MANAGER,DEPARTMENT_HEAD,TEAM_LEAD,MANAGER')->group(function () {
            Route::post('/attendance', [AttendanceWebController::class, 'store'])->name('attendance.store');
            Route::put('/attendance/{id}', [AttendanceWebController::class, 'update'])->name('attendance.update');
            Route::delete('/attendance/{id}', [AttendanceWebController::class, 'destroy'])->name('attendance.destroy');
        });
    });

    // Real-Time Notification Routes
    Route::get('/notifications/feed', [\App\Http\Controllers\Web\NotificationWebController::class, 'unreadFeed'])->name('notifications.feed');
    Route::post('/notifications/{id}/read', [\App\Http\Controllers\Web\NotificationWebController::class, 'markAsRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [\App\Http\Controllers\Web\NotificationWebController::class, 'markAllAsRead'])->name('notifications.read-all');

    // Real-Time Live Sync & State Telemetry Routes
    Route::get('/live/pulse', [\App\Http\Controllers\Web\LiveSyncController::class, 'pulse'])->name('live.pulse');
    Route::get('/live/production-data', [\App\Http\Controllers\Web\LiveSyncController::class, 'productionData'])->name('live.production');
    Route::get('/live/dashboard-data', [\App\Http\Controllers\Web\LiveSyncController::class, 'dashboardData'])->name('live.dashboard');

    // Leave Routes
    Route::middleware('feature:leaves')->group(function () {
        Route::get('/leaves', [LeaveWebController::class, 'index'])->name('leaves.index');
        Route::post('/leaves', [LeaveWebController::class, 'store'])->name('leaves.store');
        
        Route::middleware('role:SYSTEM_ADMIN,HR_MANAGER,DEPARTMENT_HEAD,TEAM_LEAD,MANAGER')->group(function () {
            Route::post('/leaves/{id}/forward', [LeaveWebController::class, 'forwardToDeptHead'])->name('leaves.forward');
            Route::post('/leaves/{id}/approve', [LeaveWebController::class, 'approve'])->name('leaves.approve');
            Route::post('/leaves/{id}/reject', [LeaveWebController::class, 'reject'])->name('leaves.reject');
            Route::post('/leaves/{id}/revoke', [LeaveWebController::class, 'revoke'])->name('leaves.revoke');
        });
    });

    // Multi-Company Payroll Routes
    Route::middleware('feature:payroll')->group(function () {
        Route::get('/payroll', [PayrollWebController::class, 'index'])->name('payroll.index');
        Route::post('/payroll/verify-pin', [PayrollWebController::class, 'verifyPin'])->name('payroll.verify-pin');
        Route::post('/payroll/lock', [PayrollWebController::class, 'lockVault'])->name('payroll.lock');
        
        Route::post('/payroll/payslip-requests', [PayrollWebController::class, 'storePayslipRequest'])->name('payroll.payslip-requests.store');
        
        // Direct View & Download Salary Slip Routes
        Route::get('/payroll/payslips/{id}/view', [PayrollWebController::class, 'viewPayslip'])->name('payroll.payslip.view');
        Route::get('/payroll/payslips/{id}/download', [PayrollWebController::class, 'downloadPayslip'])->name('payroll.payslip.download');
        
        Route::middleware('role:SYSTEM_ADMIN,HR_MANAGER,SUPER_ADMIN,HR')->group(function () {
            Route::post('/payroll', [PayrollWebController::class, 'store'])->name('payroll.store');
            Route::put('/payroll/{id}', [PayrollWebController::class, 'update'])->name('payroll.update');
            Route::delete('/payroll/{id}', [PayrollWebController::class, 'destroy'])->name('payroll.destroy');
            Route::post('/payroll/assign-employees', [PayrollWebController::class, 'assignEmployees'])->name('payroll.assign-employees');
            Route::post('/payroll/{id}/run', [PayrollWebController::class, 'run'])->name('payroll.run');
            Route::post('/payroll/advance-salaries', [PayrollWebController::class, 'storeAdvance'])->name('payroll.advance-salaries.store');
            Route::put('/payroll/advance-salaries/{id}/status', [PayrollWebController::class, 'updateAdvanceStatus'])->name('payroll.advance-salaries.status');
            Route::delete('/payroll/advance-salaries/{id}', [PayrollWebController::class, 'deleteAdvance'])->name('payroll.advance-salaries.destroy');
            Route::post('/payroll/pay-single-employee', [PayrollWebController::class, 'paySingleEmployee'])->name('payroll.pay-single');
            Route::get('/payroll/export-report', [PayrollWebController::class, 'exportReport'])->name('payroll.export-report');
            Route::post('/payroll/payslip-requests/{id}/approve', [PayrollWebController::class, 'approvePayslipRequest'])->name('payroll.payslip-requests.approve');
            Route::post('/payroll/payslip-requests/{id}/reject', [PayrollWebController::class, 'rejectPayslipRequest'])->name('payroll.payslip-requests.reject');
        });
    });

    // Graceful redirect for legacy Production route
    Route::get('/production', function () {
        return redirect()->route('projects.index');
    })->name('production.index');

    // Project & Task Management Routes
    Route::middleware('feature:projects')->group(function () {
        Route::get('/projects', [\App\Http\Controllers\Web\ProjectWebController::class, 'index'])->name('projects.index');
        Route::post('/projects/tasks/{id}/status', [\App\Http\Controllers\Web\ProjectWebController::class, 'updateTaskStatus'])->name('projects.tasks.status');
        
        Route::middleware('role:SYSTEM_ADMIN,DEPARTMENT_HEAD,TEAM_LEAD,HR_MANAGER,MANAGER')->group(function () {
            Route::post('/projects', [\App\Http\Controllers\Web\ProjectWebController::class, 'store'])->name('projects.store');
            Route::put('/projects/{id}', [\App\Http\Controllers\Web\ProjectWebController::class, 'update'])->name('projects.update');
            Route::delete('/projects/{id}', [\App\Http\Controllers\Web\ProjectWebController::class, 'destroy'])->name('projects.destroy');
            Route::post('/projects/tasks', [\App\Http\Controllers\Web\ProjectWebController::class, 'storeTask'])->name('projects.tasks.store');
            Route::delete('/projects/tasks/{id}', [\App\Http\Controllers\Web\ProjectWebController::class, 'destroyTask'])->name('projects.tasks.destroy');
        });
    });

    // Central System Settings Routes (System Admin only)
    Route::middleware(['feature:settings', 'role:SYSTEM_ADMIN,SUPER_ADMIN'])->group(function () {
        Route::get('/settings', [\App\Http\Controllers\Web\SettingsWebController::class, 'index'])->name('settings.index');
        Route::post('/settings', [\App\Http\Controllers\Web\SettingsWebController::class, 'update'])->name('settings.update');

        // Department Management Routes
        Route::post('/settings/departments', [\App\Http\Controllers\Web\SettingsWebController::class, 'storeDepartment'])->name('settings.departments.store');
        Route::put('/settings/departments/{id}', [\App\Http\Controllers\Web\SettingsWebController::class, 'updateDepartment'])->name('settings.departments.update');
        Route::delete('/settings/departments/{id}', [\App\Http\Controllers\Web\SettingsWebController::class, 'destroyDepartment'])->name('settings.departments.destroy');

        // Designation Management Routes
        Route::post('/settings/designations', [\App\Http\Controllers\Web\SettingsWebController::class, 'storeDesignation'])->name('settings.designations.store');
        Route::put('/settings/designations/{id}', [\App\Http\Controllers\Web\SettingsWebController::class, 'updateDesignation'])->name('settings.designations.update');
        Route::delete('/settings/designations/{id}', [\App\Http\Controllers\Web\SettingsWebController::class, 'destroyDesignation'])->name('settings.designations.destroy');

        // Work Shifts Management Routes
        Route::post('/settings/shifts', [\App\Http\Controllers\Web\SettingsWebController::class, 'storeShift'])->name('settings.shifts.store');
        Route::put('/settings/shifts/{id}', [\App\Http\Controllers\Web\SettingsWebController::class, 'updateShift'])->name('settings.shifts.update');
        Route::delete('/settings/shifts/{id}', [\App\Http\Controllers\Web\SettingsWebController::class, 'destroyShift'])->name('settings.shifts.destroy');

        // Test SMTP Connection Route
        Route::post('/settings/test-email', [\App\Http\Controllers\Web\SettingsWebController::class, 'testEmail'])->name('settings.test-email');

        // Test Gemini AI Connection Route
        Route::post('/settings/ai/test-connection', [\App\Http\Controllers\Web\SettingsWebController::class, 'testGeminiConnection'])->name('settings.ai.test-connection');
    });

    // User Profile & Avatar Management Routes (Available to all employees, managers, and admins)
    Route::get('/profile', [\App\Http\Controllers\Web\ProfileWebController::class, 'index'])->name('profile.index');
    Route::post('/profile', [\App\Http\Controllers\Web\ProfileWebController::class, 'update'])->name('profile.update');
    Route::post('/profile/payroll-pin', [\App\Http\Controllers\Web\ProfileWebController::class, 'updatePayrollPin'])->name('profile.payroll-pin.update');
    Route::post('/profile/avatar', [\App\Http\Controllers\Web\ProfileWebController::class, 'updateAvatar'])->name('profile.avatar.update');
    Route::delete('/profile/avatar', [\App\Http\Controllers\Web\ProfileWebController::class, 'deleteAvatar'])->name('profile.avatar.delete');
    Route::post('/profile/requests/{id}/cancel', [\App\Http\Controllers\Web\ProfileWebController::class, 'cancelRequest'])->name('profile.requests.cancel');

    // System Admin & Hr Manager Profile / Bank Change Requests Approval Routes
    Route::middleware('role:SYSTEM_ADMIN,HR_MANAGER,SUPER_ADMIN,HR')->group(function () {
        Route::get('/admin/profile-requests', [\App\Http\Controllers\Web\ProfileChangeRequestController::class, 'index'])->name('admin.profile-requests.index');
        Route::post('/admin/profile-requests/{id}/approve', [\App\Http\Controllers\Web\ProfileChangeRequestController::class, 'approve'])->name('admin.profile-requests.approve');
        Route::post('/admin/profile-requests/{id}/reject', [\App\Http\Controllers\Web\ProfileChangeRequestController::class, 'reject'])->name('admin.profile-requests.reject');
    });
});

// Universal Static Media & Asset Delivery Routes (Secured against Path Traversal)
Route::get('/images/{filename}', function ($filename) {
    if (str_contains($filename, '..') || str_contains($filename, '\\')) {
        abort(404);
    }
    $baseDir = realpath(public_path('images'));
    $target = public_path('images/' . $filename);
    $realPath = realpath($target);
    if (!$realPath || !$baseDir || !str_starts_with($realPath, $baseDir) || !is_file($realPath)) {
        abort(404);
    }
    $mime = @mime_content_type($realPath) ?: 'image/png';
    return response()->file($realPath, [
        'Content-Type' => $mime,
        'Cache-Control' => 'public, max-age=86400',
    ]);
})->where('filename', '.*');

Route::get('/uploads/{path}', function ($path) {
    if (str_contains($path, '..') || str_contains($path, '\\')) {
        abort(404);
    }
    $publicUploads = realpath(public_path('uploads'));
    $storageUploads = realpath(storage_path('app/public'));

    $targetPublic = public_path('uploads/' . $path);
    $targetStorage = storage_path('app/public/' . $path);

    $realPublic = realpath($targetPublic);
    $realStorage = realpath($targetStorage);

    $finalPath = null;
    if ($realPublic && $publicUploads && str_starts_with($realPublic, $publicUploads) && is_file($realPublic)) {
        $finalPath = $realPublic;
    } elseif ($realStorage && $storageUploads && str_starts_with($realStorage, $storageUploads) && is_file($realStorage)) {
        $finalPath = $realStorage;
    }

    if (!$finalPath) {
        abort(404);
    }

    $mime = @mime_content_type($finalPath) ?: 'image/png';
    return response()->file($finalPath, [
        'Content-Type' => $mime,
        'Cache-Control' => 'public, max-age=86400',
    ]);
})->where('path', '.*');

Route::get('/storage/{path}', function ($path) {
    if (str_contains($path, '..') || str_contains($path, '\\')) {
        abort(404);
    }
    $storageBase = realpath(storage_path('app/public'));
    $publicBase = realpath(public_path('storage'));

    $targetStorage = storage_path('app/public/' . $path);
    $targetPublic = public_path('storage/' . $path);

    $realStorage = realpath($targetStorage);
    $realPublic = realpath($targetPublic);

    $finalPath = null;
    if ($realStorage && $storageBase && str_starts_with($realStorage, $storageBase) && is_file($realStorage)) {
        $finalPath = $realStorage;
    } elseif ($realPublic && $publicBase && str_starts_with($realPublic, $publicBase) && is_file($realPublic)) {
        $finalPath = $realPublic;
    }

    if (!$finalPath) {
        abort(404);
    }

    $mime = @mime_content_type($finalPath) ?: 'image/png';
    return response()->file($finalPath, [
        'Content-Type' => $mime,
        'Cache-Control' => 'public, max-age=86400',
    ]);
})->where('path', '.*');

// System Database Migration Trigger (Secure Admin / Token)
Route::get('/system/migrate-db', function (\Illuminate\Http\Request $request) {
    $token = $request->query('token');
    $authCheck = false;
    if (auth()->check()) {
        $u = auth()->user();
        if (method_exists($u, 'isSuperAdmin') && ($u->isSuperAdmin() || (method_exists($u, 'isDeveloperAdmin') && $u->isDeveloperAdmin()))) {
            $authCheck = true;
        }
    }
    if (!$authCheck && $token !== 'axecorp2026') {
        abort(403, 'Unauthorized migration trigger');
    }

    $createdAiTable = false;
    if (!\Illuminate\Support\Facades\Schema::hasTable('ai_usage_logs')) {
        \Illuminate\Support\Facades\Schema::create('ai_usage_logs', function (\Illuminate\Database\Schema\Blueprint $table) {
            $table->id();
            $table->uuid('user_id')->nullable()->index();
            $table->string('user_name')->nullable();
            $table->string('model_used')->default('gemini-1.5-flash')->index();
            $table->text('prompt');
            $table->text('response_summary')->nullable();
            $table->json('tools_called')->nullable();
            $table->unsignedInteger('prompt_tokens')->default(0);
            $table->unsignedInteger('response_tokens')->default(0);
            $table->unsignedInteger('total_tokens')->default(0);
            $table->decimal('cost_usd', 10, 6)->default(0.000000);
            $table->unsignedInteger('latency_ms')->default(0);
            $table->string('status', 30)->default('SUCCESS')->index();
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
        $createdAiTable = true;
    }

    \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
    $output = \Illuminate\Support\Facades\Artisan::output();

    // Ensure AI Copilot feature flag is active
    \App\Models\SystemSetting::set('feature_ai_copilot', '1', 'feature_flags', 'Feature Flag: ai_copilot', 'Controls visibility of ai_copilot', 'boolean');
    \App\Models\SystemSetting::set('ai_copilot_enabled', '1', 'ai_copilot', 'AI Copilot Active', 'Enables AI Copilot engine', 'boolean');

    $hasAiLogs = \Illuminate\Support\Facades\Schema::hasTable('ai_usage_logs') ? 'YES' : 'NO';
    $hasShiftId = \Illuminate\Support\Facades\Schema::hasColumn('users', 'shift_id') ? 'YES' : 'NO';
    $isAiActive = \App\Models\SystemSetting::get('feature_ai_copilot', '0');
    return response("<pre style='padding:20px;background:#0f172a;color:#38bdf8;font-family:monospace;border-radius:12px;'>=== HRMS SYSTEM DATABASE MIGRATION ===\n\n" . e($output) . "\nAI USAGE LOGS TABLE CREATED: " . ($createdAiTable ? 'JUST CREATED' : 'ALREADY EXISTS') . "\nSCHEMA CHECK: ai_usage_logs table exists = {$hasAiLogs}\nSCHEMA CHECK: users.shift_id exists = {$hasShiftId}\nFEATURE CHECK: feature_ai_copilot = {$isAiActive}</pre>");
});

