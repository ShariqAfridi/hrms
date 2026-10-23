<?php

namespace App\Services;

use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Support\Str;

class FeatureFlagService
{
    /**
     * Built-in default features
     */
    public static function getBuiltInFeatures(): array
    {
        return [
            'companies' => [
                'key' => 'companies',
                'label' => 'Multi-Company Management',
                'category' => 'Core Workforce',
                'icon' => 'fa-solid fa-building',
                'description' => 'Manage corporate entities, branches, tax rates, currencies, and subsidiary companies.',
                'route_name' => 'companies.index',
                'default' => true,
                'is_core' => false,
            ],
            'employees' => [
                'key' => 'employees',
                'label' => 'Employee Directory & Profiles',
                'category' => 'Core Workforce',
                'icon' => 'fa-solid fa-users',
                'description' => 'Manage employee profiles, onboarding, department assignments, and contact details.',
                'route_name' => 'employees.index',
                'default' => true,
                'is_core' => false,
            ],
            'teams' => [
                'key' => 'teams',
                'label' => 'Teams & Squad Management',
                'category' => 'Core Workforce',
                'icon' => 'fa-solid fa-people-group',
                'description' => 'Create teams, assign team leaders, manage squad members, and monitor team attendance.',
                'route_name' => 'teams.index',
                'default' => true,
                'is_core' => false,
            ],
            'attendance' => [
                'key' => 'attendance',
                'label' => 'Attendance & Clock In/Out',
                'category' => 'Operations & Time',
                'icon' => 'fa-solid fa-user-clock',
                'description' => 'GPS geofenced clock in/out, shift schedules, overtime tracking, and daily presence logs.',
                'route_name' => 'attendance.index',
                'default' => true,
                'is_core' => false,
            ],
            'leaves' => [
                'key' => 'leaves',
                'label' => 'Leave Requests & Approvals',
                'category' => 'Operations & Time',
                'icon' => 'fa-solid fa-calendar-check',
                'description' => 'Employee leave quotas, leave applications, manager reviews, and status tracking.',
                'route_name' => 'leaves.index',
                'default' => true,
                'is_core' => false,
            ],
            'projects' => [
                'key' => 'projects',
                'label' => 'Projects & Task Board (Kanban)',
                'category' => 'Projects & Engineering',
                'icon' => 'fa-solid fa-diagram-project',
                'description' => 'Software house project management, ClickUp-style Kanban task board, and employee progress tracking.',
                'route_name' => 'projects.index',
                'default' => true,
                'is_core' => false,
            ],
            'payroll' => [
                'key' => 'payroll',
                'label' => 'Payroll & Compensation Processing',
                'category' => 'Finance & Payroll',
                'icon' => 'fa-solid fa-file-invoice-dollar',
                'description' => 'Monthly payroll calculation (PKR), tax withholding, late deductions, and payslip generation.',
                'route_name' => 'payroll.index',
                'default' => true,
                'is_core' => false,
            ],
            'ai_copilot' => [
                'key' => 'ai_copilot',
                'label' => 'AI Copilot & Natural Language Agent',
                'category' => 'AI & Intelligence',
                'icon' => 'fa-solid fa-robot',
                'description' => 'Interactive AI Copilot (Ctrl+K), step-by-step procedural guides, and automated entity search.',
                'route_name' => 'ai.chat',
                'default' => true,
                'is_core' => false,
            ],
            'settings' => [
                'key' => 'settings',
                'label' => 'System Settings & Structure',
                'category' => 'Administration',
                'icon' => 'fa-solid fa-sliders',
                'description' => 'Global parameters, company departments, employee designations, and threshold settings.',
                'route_name' => 'settings.index',
                'default' => true,
                'is_core' => true,
            ],
        ];
    }

    /**
     * Get all registered features (built-in + custom)
     */
    public static function getAllFeatures(): array
    {
        $features = self::getBuiltInFeatures();

        // Merge custom feature flags
        $custom = self::getCustomFeatures();
        foreach ($custom as $key => $meta) {
            $features[$key] = $meta;
        }

        // Attach live enabled state
        foreach ($features as $key => &$meta) {
            $meta['is_enabled'] = self::isRawEnabled($key, $meta['default'] ?? true);
        }

        return $features;
    }

    /**
     * Check if a feature is enabled.
     * When toggled OFF in Developer Switchboard, it is strictly disabled and hidden from all users until switched back ON.
     */
    public static function isEnabled(string $featureKey, ?User $user = null): bool
    {
        if ($featureKey === 'ai_copilot') {
            $checkUser = $user ?? auth()->user();
            if (!$checkUser) {
                return false;
            }
            $isSuper = method_exists($checkUser, 'isSuperAdmin') && $checkUser->isSuperAdmin();
            $isDev = method_exists($checkUser, 'isDeveloperAdmin') && $checkUser->isDeveloperAdmin();
            if (!$isSuper && !$isDev) {
                return false;
            }
            return self::isRawEnabled($featureKey);
        }

        return self::isRawEnabled($featureKey);
    }

    /**
     * Get raw setting value for other users (without developer bypass)
     */
    public static function isRawEnabled(string $featureKey, bool $default = true): bool
    {
        $settingKey = "feature_{$featureKey}";
        $val = SystemSetting::get($settingKey, null);

        if ($val === null) {
            $builtIn = self::getBuiltInFeatures();
            return $builtIn[$featureKey]['default'] ?? $default;
        }

        return in_array(strtolower((string)$val), ['1', 'true', 'yes', 'on', 'enabled'], true);
    }

    /**
     * Set feature toggle state
     */
    public static function setFeatureStatus(string $featureKey, bool $enabled): void
    {
        $settingKey = "feature_{$featureKey}";
        SystemSetting::set(
            $settingKey,
            $enabled ? '1' : '0',
            'feature_flags',
            "Feature Flag: {$featureKey}",
            "Controls visibility of {$featureKey} for non-developer users.",
            'boolean'
        );
    }

    /**
     * Get custom feature flags registered dynamically
     */
    public static function getCustomFeatures(): array
    {
        $json = SystemSetting::get('custom_feature_flags_json', '[]');
        $data = json_decode((string)$json, true);
        return is_array($data) ? $data : [];
    }

    /**
     * Register a new custom feature flag
     */
    public static function registerCustomFeature(
        string $key,
        string $label,
        string $description,
        string $category = 'Custom / In Progress',
        string $icon = 'fa-solid fa-layer-group',
        bool $initialEnabled = false
    ): void {
        $cleanKey = Str::slug(strtolower(trim($key)), '_');
        $custom = self::getCustomFeatures();

        $custom[$cleanKey] = [
            'key' => $cleanKey,
            'label' => trim($label),
            'category' => trim($category ?: 'Custom / In Progress'),
            'icon' => trim($icon ?: 'fa-solid fa-layer-group'),
            'description' => trim($description),
            'route_name' => null,
            'default' => $initialEnabled,
            'is_core' => false,
            'is_custom' => true,
        ];

        SystemSetting::set(
            'custom_feature_flags_json',
            json_encode($custom),
            'feature_flags',
            'Custom Feature Flags JSON',
            'Registry of dynamically added custom modules and features.',
            'json'
        );

        self::setFeatureStatus($cleanKey, $initialEnabled);
    }

    /**
     * Delete a custom feature flag
     */
    public static function deleteCustomFeature(string $key): void
    {
        $cleanKey = Str::slug(strtolower(trim($key)), '_');
        $custom = self::getCustomFeatures();

        if (isset($custom[$cleanKey])) {
            unset($custom[$cleanKey]);
            SystemSetting::set(
                'custom_feature_flags_json',
                json_encode($custom),
                'feature_flags',
                'Custom Feature Flags JSON',
                'Registry of dynamically added custom modules and features.',
                'json'
            );

            SystemSetting::where('key', "feature_{$cleanKey}")->delete();
        }
    }
}
