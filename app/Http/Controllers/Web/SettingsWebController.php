<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AiUsageLog;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Organization;
use App\Models\Shift;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\Ai\GeminiHrmsService;
use App\Services\FeatureFlagService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class SettingsWebController extends Controller
{
    public function index(Request $request)
    {
        // Load or seed default system settings
        $this->ensureDefaultSettingsExist();

        $settings = SystemSetting::all()->groupBy('group');
        $organizations = Organization::all();
        $departments = Department::with(['organization', 'parentDepartment', 'headUser'])->withCount('users')->orderBy('name', 'asc')->get();
        $designations = Designation::with('organization')->withCount('users')->orderBy('title', 'asc')->get();
        $shiftsQuery = Shift::with('organization');
        if (\Illuminate\Support\Facades\Schema::hasColumn('users', 'shift_id')) {
            $shiftsQuery->withCount('users');
        }
        $shifts = $shiftsQuery->orderBy('name', 'asc')->get();
        $users = User::excludeDevs()->where('is_active', true)->orderBy('name', 'asc')->get();

        $features = FeatureFlagService::getAllFeatures();

        $activeTab = $request->input('tab', 'general');

        $featureMap = [
            'attendance' => 'attendance',
            'payroll' => 'payroll',
            'leave' => 'leaves',
            'ai_copilot' => 'ai_copilot',
        ];

        if (isset($featureMap[$activeTab]) && !FeatureFlagService::isEnabled($featureMap[$activeTab])) {
            $activeTab = 'general';
        }

        // AI Copilot Telemetry & Monitoring Metrics (Graceful auto-provisioning & fallback)
        if (!Schema::hasTable('ai_usage_logs')) {
            try {
                Schema::create('ai_usage_logs', function (\Illuminate\Database\Schema\Blueprint $table) {
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
            } catch (\Throwable $e) {
                // Ignore if created concurrently
            }
        }

        if (Schema::hasTable('ai_usage_logs')) {
            $todayTokens = (int) AiUsageLog::today()->sum('total_tokens');
            $monthTokens = (int) AiUsageLog::thisMonth()->sum('total_tokens');
            $flashTokens = (int) AiUsageLog::thisMonth()->flash()->sum('total_tokens');
            $proTokens = (int) AiUsageLog::thisMonth()->pro()->sum('total_tokens');
            $monthCostUsd = (float) AiUsageLog::thisMonth()->sum('cost_usd');
            $monthCostPkr = round($monthCostUsd * 280, 2);
            $totalAiQueries = (int) AiUsageLog::count();
            $avgLatencyMs = (int) (AiUsageLog::thisMonth()->avg('latency_ms') ?? 0);
            $recentAiLogs = AiUsageLog::with('user')->orderBy('created_at', 'desc')->take(50)->get();
        } else {
            $todayTokens = 0;
            $monthTokens = 0;
            $flashTokens = 0;
            $proTokens = 0;
            $monthCostUsd = 0.0;
            $monthCostPkr = 0.0;
            $totalAiQueries = 0;
            $avgLatencyMs = 0;
            $recentAiLogs = collect();
        }

        return view('settings.index', compact(
            'settings', 'organizations', 'departments', 'designations', 'shifts', 'users', 'features', 'activeTab',
            'todayTokens', 'monthTokens', 'flashTokens', 'proTokens', 'monthCostUsd', 'monthCostPkr', 'totalAiQueries', 'avgLatencyMs', 'recentAiLogs'
        ));
    }

    public function update(Request $request)
    {
        $settingsData = $request->input('settings', []);

        foreach ($settingsData as $key => $value) {
            SystemSetting::where('key', $key)->update(['value' => $value]);
        }

        // Also sync primary organization default currency & tax if updated
        if ($request->has('settings.company_currency')) {
            Organization::query()->update([
                'currency' => $request->input('settings.company_currency', 'PKR'),
            ]);
        }

        if ($request->has('settings.company_tax_rate')) {
            $taxRate = (float) $request->input('settings.company_tax_rate', 12.0);
            Organization::query()->update([
                'payroll_tax_rate' => $taxRate,
            ]);
        }

        $activeTab = $request->input('active_tab', 'general');

        return redirect()->route('settings.index', ['tab' => $activeTab])
            ->with('success', 'System settings saved and applied successfully.');
    }

    /**
     * Test live connection to Google Gemini API (Strictly Super Admin & Developer)
     */
    public function testGeminiConnection(Request $request, GeminiHrmsService $geminiService)
    {
        $user = \Illuminate\Support\Facades\Auth::user();
        if (!$user || (!$user->isSuperAdmin() && !$user->isDeveloperAdmin())) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Restricted exclusively to Super Admin and Developer Admin accounts.',
            ], 403);
        }

        $apiKey = $request->input('api_key');
        $result = $geminiService->testConnection($apiKey);

        return response()->json($result);
    }

    // =========================================================================
    // DEVELOPER-ONLY CUSTOM FEATURE TOGGLE & CRUD CONTROLS
    // =========================================================================

    public function toggleFeature(Request $request)
    {
        $validated = $request->validate([
            'feature_key' => 'required|string',
            'enabled' => 'required|boolean',
        ]);

        FeatureFlagService::setFeatureStatus($validated['feature_key'], $validated['enabled']);

        return response()->json([
            'success' => true,
            'feature_key' => $validated['feature_key'],
            'enabled' => (bool)$validated['enabled'],
            'message' => "Module '{$validated['feature_key']}' updated successfully.",
        ]);
    }

    public function storeCustomFeature(Request $request)
    {
        $validated = $request->validate([
            'key' => 'required|string|regex:/^[a-z0-9_]+$/|max:50',
            'label' => 'required|string|max:100',
            'category' => 'nullable|string|max:50',
            'icon' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:255',
            'route_name' => 'nullable|string|max:100',
            'default' => 'nullable|boolean',
        ]);

        $custom = FeatureFlagService::getCustomFeatures();

        if (isset($custom[$validated['key']])) {
            return redirect()->route('settings.index', ['tab' => 'developer'])
                ->with('error', "A custom feature with the key '{$validated['key']}' already exists.");
        }

        $builtIn = FeatureFlagService::getBuiltInFeatures();
        if (isset($builtIn[$validated['key']])) {
            return redirect()->route('settings.index', ['tab' => 'developer'])
                ->with('error', "Cannot create custom feature: '{$validated['key']}' is reserved for built-in core modules.");
        }

        $custom[$validated['key']] = [
            'key' => $validated['key'],
            'label' => $validated['label'],
            'category' => $validated['category'] ?: 'Custom Modules',
            'icon' => $validated['icon'] ?: 'fa-solid fa-cube',
            'description' => $validated['description'] ?: 'Custom developer-registered feature module.',
            'route_name' => $validated['route_name'] ?: 'dashboard',
            'default' => $request->boolean('default', true),
            'is_core' => false,
            'is_custom' => true,
        ];

        FeatureFlagService::saveCustomFeatures($custom);

        // Also set initial status
        FeatureFlagService::setFeatureStatus($validated['key'], $request->boolean('default', true));

        return redirect()->route('settings.index', ['tab' => 'developer'])
            ->with('success', "Custom feature flag '{$validated['label']}' registered and activated successfully.");
    }

    public function destroyCustomFeature($key)
    {
        $custom = FeatureFlagService::getCustomFeatures();

        if (isset($custom[$key])) {
            $label = $custom[$key]['label'];
            unset($custom[$key]);
            FeatureFlagService::saveCustomFeatures($custom);
            SystemSetting::where('key', "feature_{$key}")->delete();

            return redirect()->route('settings.index', ['tab' => 'developer'])
                ->with('success', "Custom feature '{$label}' removed successfully.");
        }

        return redirect()->route('settings.index', ['tab' => 'developer'])
            ->with('error', "Custom feature '{$key}' was not found.");
    }

    // =========================================================================
    // DEPARTMENT MANAGEMENT (CRUD)
    // =========================================================================

    public function storeDepartment(Request $request)
    {
        $validated = $request->validate([
            'organization_id' => 'required|exists:organizations,id',
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'head_user_id' => 'nullable|exists:users,id',
            'parent_department_id' => 'nullable|exists:departments,id',
        ]);

        if (!empty($validated['code'])) {
            $validated['code'] = strtoupper(trim($validated['code']));
        }

        $dept = Department::create($validated);
        $dept->load(['organization', 'headUser', 'parentDepartment']);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'department' => $dept,
                'message' => "Department '{$dept->name}' created successfully.",
            ]);
        }

        return redirect()->route('settings.index', ['tab' => 'departments'])
            ->with('success', "Department '{$dept->name}' created successfully.");
    }

    public function updateDepartment(Request $request, $id)
    {
        $dept = Department::findOrFail($id);

        $validated = $request->validate([
            'organization_id' => 'required|exists:organizations,id',
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'head_user_id' => 'nullable|exists:users,id',
            'parent_department_id' => 'nullable|exists:departments,id',
        ]);

        if (!empty($validated['code'])) {
            $validated['code'] = strtoupper(trim($validated['code']));
        }

        $dept->update($validated);
        $dept->load(['organization', 'headUser', 'parentDepartment']);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'department' => $dept,
                'message' => "Department '{$dept->name}' updated successfully.",
            ]);
        }

        return redirect()->route('settings.index', ['tab' => 'departments'])
            ->with('success', "Department '{$dept->name}' updated successfully.");
    }

    public function destroyDepartment($id)
    {
        $dept = Department::findOrFail($id);
        $name = $dept->name;
        $dept->delete();

        return redirect()->route('settings.index', ['tab' => 'departments'])
            ->with('success', "Department '{$name}' deleted successfully.");
    }

    // =========================================================================
    // DESIGNATION MANAGEMENT (CRUD)
    // =========================================================================

    public function storeDesignation(Request $request)
    {
        $validated = $request->validate([
            'organization_id' => 'required|exists:organizations,id',
            'title' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
        ]);

        if (!empty($validated['code'])) {
            $validated['code'] = strtoupper(trim($validated['code']));
        } else {
            $validated['code'] = Designation::generateCode($validated['title']);
        }

        $designation = Designation::create($validated);
        $designation->load('organization');

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'designation' => $designation,
                'message' => "Designation '{$designation->title}' created successfully.",
            ]);
        }

        return redirect()->route('settings.index', ['tab' => 'designations'])
            ->with('success', "Designation '{$designation->title}' created successfully.");
    }

    public function updateDesignation(Request $request, $id)
    {
        $designation = Designation::findOrFail($id);

        $validated = $request->validate([
            'organization_id' => 'required|exists:organizations,id',
            'title' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
        ]);

        if (!empty($validated['code'])) {
            $validated['code'] = strtoupper(trim($validated['code']));
        } else {
            $validated['code'] = Designation::generateCode($validated['title']);
        }

        $designation->update($validated);
        $designation->load('organization');

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'designation' => $designation,
                'message' => "Designation '{$designation->title}' updated successfully.",
            ]);
        }

        return redirect()->route('settings.index', ['tab' => 'designations'])
            ->with('success', "Designation '{$designation->title}' updated successfully.");
    }

    public function destroyDesignation($id)
    {
        $designation = Designation::findOrFail($id);
        $title = $designation->title;
        $designation->delete();

        return redirect()->route('settings.index', ['tab' => 'designations'])
            ->with('success', "Designation '{$title}' deleted successfully.");
    }

    // =========================================================================
    // WORK SHIFT MANAGEMENT (CRUD)
    // =========================================================================

    public function storeShift(Request $request)
    {
        $validated = $request->validate([
            'organization_id' => 'required|exists:organizations,id',
            'name' => 'required|string|max:255',
            'start_time' => 'required|string|max:20',
            'end_time' => 'required|string|max:20',
            'flexible' => 'nullable',
        ]);

        $validated['flexible'] = $request->boolean('flexible');

        $shift = Shift::create($validated);
        $shift->load('organization');

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'shift' => $shift,
                'message' => "Work Shift '{$shift->name}' created successfully.",
            ]);
        }

        return redirect()->route('settings.index', ['tab' => 'shifts'])
            ->with('success', "Work Shift '{$shift->name}' created successfully.");
    }

    public function updateShift(Request $request, $id)
    {
        $shift = Shift::findOrFail($id);

        $validated = $request->validate([
            'organization_id' => 'required|exists:organizations,id',
            'name' => 'required|string|max:255',
            'start_time' => 'required|string|max:20',
            'end_time' => 'required|string|max:20',
            'flexible' => 'nullable',
        ]);

        $validated['flexible'] = $request->boolean('flexible');

        $shift->update($validated);
        $shift->load('organization');

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'shift' => $shift,
                'message' => "Work Shift '{$shift->name}' updated successfully.",
            ]);
        }

        return redirect()->route('settings.index', ['tab' => 'shifts'])
            ->with('success', "Work Shift '{$shift->name}' updated successfully.");
    }

    public function destroyShift($id)
    {
        $shift = Shift::findOrFail($id);
        $name = $shift->name;
        $shift->delete();

        return redirect()->route('settings.index', ['tab' => 'shifts'])
            ->with('success', "Work Shift '{$name}' deleted successfully.");
    }

    // =========================================================================
    // TEST OUTGOING SMTP EMAIL
    // =========================================================================

    public function sendTestEmail(Request $request)
    {
        $validated = $request->validate([
            'test_email' => 'required|email',
        ]);

        $res = \App\Services\HrmsMailService::sendTestEmail($validated['test_email']);

        if ($res['success']) {
            return redirect()->route('settings.index', ['tab' => 'notifications'])
                ->with('success', $res['message']);
        }

        return redirect()->route('settings.index', ['tab' => 'notifications'])
            ->with('error', $res['message']);
    }

    private function ensureDefaultSettingsExist()
    {
        $defaults = [
            // Company & Localization
            'company_currency' => ['group' => 'general', 'label' => 'Default Base Currency', 'value' => 'PKR', 'type' => 'string', 'description' => 'Primary currency across payrolls, companies, and financial reports.'],
            'company_timezone' => ['group' => 'general', 'label' => 'System Timezone', 'value' => 'Asia/Karachi', 'type' => 'string', 'description' => 'Default timezone for attendance logs and scheduling.'],
            'company_tax_rate' => ['group' => 'general', 'label' => 'Default Corporate Tax Rate (%)', 'value' => '12.0', 'type' => 'number', 'description' => 'Standard withholding tax percentage applied during payroll runs.'],
            'company_legal_name' => ['group' => 'general', 'label' => 'Holding Company Legal Name', 'value' => 'AxeCorp Enterprise Group Ltd.', 'type' => 'string', 'description' => 'Legal entity title displayed on official payslips.'],

            // Attendance & Shift Rules
            'attendance_late_threshold' => ['group' => 'attendance', 'label' => 'Late Grace Period (Minutes)', 'value' => '15', 'type' => 'number', 'description' => 'Clock-ins after 09:15 AM trigger a LATE status.'],
            'attendance_shift_hours' => ['group' => 'attendance', 'label' => 'Standard Shift Working Hours', 'value' => '8', 'type' => 'number', 'description' => 'Standard required working hours per day (480 mins).'],
            'attendance_overtime_threshold' => ['group' => 'attendance', 'label' => 'Overtime Minimum Trigger (Minutes)', 'value' => '60', 'type' => 'number', 'description' => 'Minutes worked beyond 8 hours required to trigger overtime.'],
            'attendance_overtime_multiplier' => ['group' => 'attendance', 'label' => 'Overtime Pay Rate Multiplier', 'value' => '1.5', 'type' => 'number', 'description' => 'Multiplier applied to hourly base rate for overtime hours.'],
            
            // Break Policy Limits
            'break_lunch_max_minutes' => ['group' => 'attendance', 'label' => 'Lunch Break Maximum Duration (Minutes)', 'value' => '60', 'type' => 'number', 'description' => 'Allowed duration for lunch break. Breaks longer than this trigger an over-limit alert.'],
            'break_namaz_max_minutes' => ['group' => 'attendance', 'label' => 'Namaz / Prayer Break Duration (Minutes)', 'value' => '20', 'type' => 'number', 'description' => 'Allowed duration for prayer breaks.'],
            'break_smoke_max_minutes' => ['group' => 'attendance', 'label' => 'Smoke & Refreshment Break Duration (Minutes)', 'value' => '15', 'type' => 'number', 'description' => 'Allowed duration for smoke/quick refreshment breaks.'],
            'break_tea_max_minutes' => ['group' => 'attendance', 'label' => 'Tea / Quick Break Duration (Minutes)', 'value' => '15', 'type' => 'number', 'description' => 'Allowed duration for tea or rest breaks.'],

            // Office Network & Geofence Verification
            'attendance_strict_office_only' => ['group' => 'attendance', 'label' => 'Strictly Enforce Office Location for Clock-In', 'value' => '0', 'type' => 'boolean', 'description' => 'If enabled, employees cannot clock in unless verified by office IP or GPS geofence.'],
            'office_allowed_ips' => ['group' => 'attendance', 'label' => 'Whitelisted Office Static IP Addresses', 'value' => '127.0.0.1,::1', 'type' => 'string', 'description' => 'Comma-separated list of office IP addresses.'],
            'office_latitude' => ['group' => 'attendance', 'label' => 'Office Central GPS Latitude', 'value' => '24.8607', 'type' => 'string', 'description' => 'Office coordinates for browser geolocation verification.'],
            'office_longitude' => ['group' => 'attendance', 'label' => 'Office Central GPS Longitude', 'value' => '67.0011', 'type' => 'string', 'description' => 'Office coordinates for browser geolocation verification.'],
            'office_geofence_radius' => ['group' => 'attendance', 'label' => 'Office Geofencing Radius (Meters)', 'value' => '200', 'type' => 'number', 'description' => 'Maximum allowed clock-in distance from office coordinates.'],

            // Payroll & Finance Rules
            'payroll_default_working_days' => ['group' => 'payroll', 'label' => 'Default Working Days per Month', 'value' => '22', 'type' => 'number', 'description' => 'Standard work days used in daily salary calculations.'],
            'payroll_lates_per_day_deduction' => ['group' => 'payroll', 'label' => 'Late Days for 1-Day Salary Deduction', 'value' => '3', 'type' => 'number', 'description' => 'Number of late arrivals that trigger a 1-day base salary deduction (e.g. 3 lates = 1 day salary deduction).'],
            'payroll_auto_release' => ['group' => 'payroll', 'label' => 'Require Super Admin Approval for Release', 'value' => '1', 'type' => 'boolean', 'description' => 'If enabled, payroll runs require approval before releasing payslips.'],

            // Leave Management & Quotas (Single 12-day standard quota)
            'leave_total_quota' => ['group' => 'leave', 'label' => 'Standard Annual Leaves Quota (Days)', 'value' => '12', 'type' => 'number', 'description' => 'Fixed yearly leave days allocated per employee (12 days total, no separate types).'],
            'leave_approval_layers' => ['group' => 'leave', 'label' => 'Leave Approval Hierarchy Layers', 'value' => '1', 'type' => 'number', 'description' => '1 = Manager Approval; 2 = Manager + HR Approval.'],

            // AI Copilot & Telemetry Settings
            'ai_copilot_enabled' => ['group' => 'ai_copilot', 'label' => 'Enable AI Copilot Search & Procedural Agent', 'value' => '1', 'type' => 'boolean', 'description' => 'Enables the AI Copilot top-bar search and floating widget.'],
            'gemini_api_key' => ['group' => 'ai_copilot', 'label' => 'Google Gemini API Key', 'value' => '', 'type' => 'password', 'description' => 'Your secret Google AI Studio / Gemini API Key (e.g. AIzaSy...). Can also be set in .env (GEMINI_API_KEY).'],
            'gemini_default_model' => ['group' => 'ai_copilot', 'label' => 'Default AI Model', 'value' => 'gemini-1.5-flash', 'type' => 'string', 'description' => 'gemini-1.5-flash (Fast & Economical) or gemini-1.5-pro (Deep Multi-Step Reasoning).'],
            'gemini_temperature' => ['group' => 'ai_copilot', 'label' => 'Model Temperature (0.0 to 1.0)', 'value' => '0.1', 'type' => 'number', 'description' => 'Lower values (0.0 - 0.2) ensure strictly factual, deterministic, and sharp responses.'],
            'gemini_max_tokens' => ['group' => 'ai_copilot', 'label' => 'Max Output Tokens per Response', 'value' => '2048', 'type' => 'number', 'description' => 'Hard token ceiling per response to avoid unexpected runaway usage.'],
            'gemini_monthly_budget_ceiling' => ['group' => 'ai_copilot', 'label' => 'Monthly Token Budget Warning Limit', 'value' => '5000000', 'type' => 'number', 'description' => 'Soft warning threshold in tokens per month (e.g. 5,000,000 tokens).'],

            // Notifications
            'notification_email_alerts' => ['group' => 'notifications', 'label' => 'Automated Email Notifications (Master Switch)', 'value' => '1', 'type' => 'boolean', 'description' => 'Global master switch for system email notifications.'],
            'notification_payroll_alerts' => ['group' => 'notifications', 'label' => 'Payslip Clearance & Release Notifications', 'value' => '1', 'type' => 'boolean', 'description' => 'High-priority notifications when employees request payslips and when HR approves/releases them.'],
            'notification_leave_alerts' => ['group' => 'notifications', 'label' => 'Leave Application & Approval Notifications', 'value' => '1', 'type' => 'boolean', 'description' => 'High-priority notifications when leave requests are submitted, approved, or declined.'],
        ];

        // Clean up obsolete settings
        SystemSetting::whereIn('key', [
            'payroll_sales_commission_rate', 'payroll_late_deduction_fee',
            'leave_annual_quota', 'leave_casual_quota', 'leave_sick_quota', 'leave_maternity_quota'
        ])->delete();

        foreach ($defaults as $k => $d) {
            SystemSetting::firstOrCreate(
                ['key' => $k],
                [
                    'group' => $d['group'],
                    'label' => $d['label'],
                    'value' => $d['value'],
                    'type' => $d['type'],
                    'description' => $d['description'],
                ]
            );
        }
    }
}
