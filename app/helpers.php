<?php

use App\Services\FeatureFlagService;

if (!function_exists('is_developer_admin')) {
    function is_developer_admin(): bool
    {
        $user = auth()->user();
        return $user && method_exists($user, 'isDeveloperAdmin') && $user->isDeveloperAdmin();
    }
}

if (!function_exists('is_super_admin')) {
    function is_super_admin(): bool
    {
        $user = auth()->user();
        return $user && method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin();
    }
}

if (!function_exists('feature_enabled')) {
    function feature_enabled(string $featureKey): bool
    {
        return FeatureFlagService::isEnabled($featureKey, auth()->user());
    }
}

if (!function_exists('is_feature_raw_enabled')) {
    function is_feature_raw_enabled(string $featureKey): bool
    {
        return FeatureFlagService::isRawEnabled($featureKey);
    }
}

if (!function_exists('axecorp_logo_src')) {
    function axecorp_logo_src(): string
    {
        static $cachedBase64 = null;
        if ($cachedBase64 !== null) {
            return $cachedBase64;
        }

        $directPath = dirname(__DIR__) . '/public/images/axecorp-logo-transparent.png';
        if (file_exists($directPath)) {
            $cachedBase64 = 'data:image/png;base64,' . base64_encode(file_get_contents($directPath));
            return $cachedBase64;
        }

        $publicPath = function_exists('public_path') ? @public_path('images/axecorp-logo-transparent.png') : null;
        if ($publicPath && file_exists($publicPath)) {
            $cachedBase64 = 'data:image/png;base64,' . base64_encode(file_get_contents($publicPath));
            return $cachedBase64;
        }

        return 'data:image/png;base64,';
    }
}

