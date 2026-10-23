<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;

class Authenticate extends Middleware
{
    protected function redirectTo($request)
    {
        if ($request->is('live/*', 'notifications/*', 'api/*', 'ai/*', 'attendance/*/break-logs') || $request->expectsJson() || $request->ajax()) {
            return null;
        }

        return route('login');
    }
}
