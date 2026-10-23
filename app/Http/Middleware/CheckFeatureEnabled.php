<?php

namespace App\Http\Middleware;

use App\Services\FeatureFlagService;
use Closure;
use Illuminate\Http\Request;

class CheckFeatureEnabled
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $featureKey
     * @return mixed
     */
    public function handle(Request $request, Closure $next, string $featureKey)
    {
        $user = $request->user();

        // Check if feature is enabled
        if (!FeatureFlagService::isEnabled($featureKey, $user)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => "The module '{$featureKey}' is currently switched off from Developer Settings.",
                ], 403);
            }

            return redirect()->route('dashboard')->with('error', "The requested module is currently switched off from Developer Settings.");
        }

        return $next($request);
    }
}
