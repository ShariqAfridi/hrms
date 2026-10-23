<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RequireDeveloperAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (!$user || !method_exists($user, 'isDeveloperAdmin') || !$user->isDeveloperAdmin()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized: Sole Developer Admin access required.',
                ], 403);
            }

            abort(403, 'Access denied. Sole Developer Admin access required.');
        }

        return $next($request);
    }
}
