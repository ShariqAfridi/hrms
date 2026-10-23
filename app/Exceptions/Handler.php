<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Support\Facades\Auth;
use Throwable;

class Handler extends ExceptionHandler
{
    protected $dontReport = [
        //
    ];

    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    public function register()
    {
        $this->reportable(function (Throwable $e) {
            //
        });

        // Gracefully catch CSRF / Session Expiration (eliminates raw 419 Page Expired errors)
        $this->renderable(function (\Illuminate\Session\TokenMismatchException $e, $request) {
            if ($request->is('api/*') || $request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Your security session has expired. Please log in again.',
                ], 419);
            }

            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if ($request->is('logout') || $request->routeIs('logout')) {
                return redirect()->route('login')->with('info', 'You have been logged out successfully.');
            }

            return redirect()->route('login')->with('warning', 'Your session has expired due to inactivity. Please log in again.');
        });

        // Gracefully catch 419 HTTP status code if thrown as an HttpException
        $this->renderable(function (\Symfony\Component\HttpKernel\Exception\HttpException $e, $request) {
            if ($e->getStatusCode() === 419) {
                if ($request->is('api/*') || $request->expectsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Your security session has expired. Please log in again.',
                    ], 419);
                }

                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                if ($request->is('logout') || $request->routeIs('logout')) {
                    return redirect()->route('login')->with('info', 'You have been logged out successfully.');
                }

                return redirect()->route('login')->with('warning', 'Your session has expired due to inactivity. Please log in again.');
            }

            return null;
        });

        // Gracefully catch Method Not Allowed (e.g. GET on POST logout or form URLs)
        $this->renderable(function (\Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException $e, $request) {
            if ($request->is('api/*') || $request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'The requested HTTP method is not allowed for this route.',
                ], 405);
            }

            if ($request->is('logout') || $request->routeIs('logout')) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                return redirect()->route('login')->with('info', 'You have been logged out successfully.');
            }

            return redirect()->route(Auth::check() ? 'dashboard' : 'login');
        });

        // Gracefully catch Database Query Exceptions on web forms
        $this->renderable(function (\Illuminate\Database\QueryException $e, $request) {
            \Illuminate\Support\Facades\Log::error("Database QueryException: " . $e->getMessage(), [
                'sql' => $e->getSql(),
                'url' => $request->fullUrl(),
            ]);

            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Database operation error: ' . $e->getMessage(),
                ], 500);
            }

            // Extract a user-friendly summary of the error
            $message = 'Database operation could not be completed. Please ensure all form fields are filled correctly.';
            if (str_contains($e->getMessage(), 'cannot be null')) {
                preg_match("/Column '([^']+)' cannot be null/", $e->getMessage(), $matches);
                $col = $matches[1] ?? 'Required field';
                $message = "Please provide a value for '{$col}' (cannot be empty).";
            } elseif (str_contains($e->getMessage(), 'Duplicate entry')) {
                preg_match("/Duplicate entry '([^']+)'/", $e->getMessage(), $matches);
                $val = $matches[1] ?? '';
                $message = "A record with value '{$val}' already exists. Please use a unique identifier.";
            } elseif (str_contains($e->getMessage(), 'Unknown column')) {
                preg_match("/Unknown column '([^']+)'/", $e->getMessage(), $matches);
                $col = $matches[1] ?? 'field';
                $message = "Database schema update in progress: column '{$col}' is being synchronized. Please refresh the page.";
            } elseif (str_contains($e->getMessage(), 'foreign key constraint fails')) {
                $message = "Selected related record (such as department, team, shift, or role) is invalid or does not exist.";
            } elseif (config('app.debug') || (auth()->check() && method_exists(auth()->user(), 'isDeveloperAdmin') && auth()->user()->isDeveloperAdmin())) {
                $message = 'Database error: ' . $e->getMessage();
            }

            return redirect()->back()
                ->withInput()
                ->with('error', $message);
        });

        // Gracefully catch any unhandled exceptions on web forms
        $this->renderable(function (\Throwable $e, $request) {
            // Let validation, auth, and HTTP exceptions handle themselves natively
            if ($e instanceof \Illuminate\Validation\ValidationException ||
                $e instanceof \Illuminate\Auth\AuthenticationException ||
                $e instanceof \Illuminate\Auth\Access\AuthorizationException ||
                $e instanceof \Symfony\Component\HttpKernel\Exception\HttpException) {
                return null;
            }

            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 500);
            }

            return redirect()->back()
                ->withInput()
                ->with('error', 'Form Submission Error: ' . $e->getMessage());
        });
    }

    protected function shouldReturnJson($request, Throwable $e)
    {
        return parent::shouldReturnJson($request, $e) ||
               $request->is('live/*', 'notifications/*', 'api/*', 'ai/*', 'attendance/*/break-logs') ||
               $request->ajax();
    }
}
