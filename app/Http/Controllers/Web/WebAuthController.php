<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class WebAuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'string'],
            'password' => ['required'],
        ]);

        $inputEmail = trim($credentials['email']);
        $password = $credentials['password'];

        // Find user by exact username/email, or handle @axecorp / @axecorp.com aliases
        $possibleEmails = [$inputEmail];
        if (str_ends_with($inputEmail, '@axecorp')) {
            $possibleEmails[] = $inputEmail . '.com';
            $possibleEmails[] = substr($inputEmail, 0, -8);
        } elseif (str_ends_with($inputEmail, '@axecorp.com')) {
            $possibleEmails[] = substr($inputEmail, 0, -4);
            $possibleEmails[] = substr($inputEmail, 0, -12);
        } else {
            $possibleEmails[] = $inputEmail . '@axecorp';
            $possibleEmails[] = $inputEmail . '@axecorp.com';
        }

        $user = User::whereIn('email', array_unique($possibleEmails))->first();

        if ($user && Hash::check($password, $user->password)) {
            // Cryptographic Database Tamper-Proof Verification (Fail-Safe)
            try {
                if (\Illuminate\Support\Facades\Schema::hasColumn('users', 'password_signature')) {
                    if (empty($user->password_signature)) {
                        // Auto-enroll existing user seamlessly
                        $user->refreshPasswordSignature();
                    } elseif (!$user->verifyPasswordSignature()) {
                        \Illuminate\Support\Facades\Log::critical("SECURITY ALERT: Database tampering detected for user {$user->email} ({$user->id}). Password hash was modified out-of-band.", [
                            'ip' => $request->ip(),
                            'user_agent' => $request->userAgent(),
                        ]);

                        return back()->withErrors([
                            'email' => 'Security policy violation: Account credential integrity compromised. Please contact administration immediately.',
                        ])->onlyInput('email');
                    }
                }
            } catch (\Throwable $e) {
                // Fail-safe: database schema discrepancies must never block authorized user login
                \Illuminate\Support\Facades\Log::warning("Password signature verification skipped: " . $e->getMessage());
            }

            if (!$user->is_active) {
                return back()->withErrors([
                    'email' => 'Your account has been deactivated. Please contact administration.',
                ])->onlyInput('email');
            }

            Auth::login($user, $request->boolean('remember'));
            $request->session()->regenerate();

            // Sanitize intended URL to prevent redirecting to background live sync / AJAX polling endpoints
            $intended = $request->session()->get('url.intended');
            if ($intended) {
                $path = parse_url($intended, PHP_URL_PATH) ?? '';
                if (
                    str_contains($path, '/live/') ||
                    str_contains($path, '/notifications/') ||
                    str_contains($path, '/ai/') ||
                    str_contains($path, '/api/') ||
                    str_contains($path, 'break-logs')
                ) {
                    $request->session()->forget('url.intended');
                }
            }

            return redirect()->intended(route('dashboard'))->with('success', 'Welcome back, ' . Auth::user()->name . '!');
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records or account is inactive.',
        ])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('info', 'You have been logged out successfully.');
    }

    // =========================================================================
    // PASSWORD RESET WORKFLOWS
    // =========================================================================

    public function showForgotPassword()
    {
        return view('auth.forgot-password');
    }

    public function sendResetLink(Request $request)
    {
        $request->validate([
            'email' => 'required|string',
        ]);

        $email = trim($request->input('email'));
        $user = User::where('email', $email)->first();

        if (!$user) {
            return back()->withErrors([
                'email' => "We couldn't find an account associated with '{$email}'.",
            ])->onlyInput('email');
        }

        // Generate secure 60-character reset token
        $token = Str::random(60);

        DB::table('password_resets')->where('email', $user->email)->delete();
        DB::table('password_resets')->insert([
            'email' => $user->email,
            'token' => $token,
            'created_at' => now(),
        ]);

        $resetUrl = route('password.reset', ['token' => $token, 'email' => $user->email]);

        $redirect = redirect()->route('password.request')
            ->with('status', 'If your email is registered, a password reset link has been dispatched to your email address.')
            ->with('email', $user->email);

        // Strictly restrict on-screen link generation to local development debug environments
        if (config('app.debug') && !app()->environment('production')) {
            $redirect->with('reset_url', $resetUrl);
        }

        return $redirect;
    }

    public function showResetPassword(Request $request, $token)
    {
        $email = $request->query('email');
        $resetRecord = DB::table('password_resets')->where('token', $token)->first();

        if (!$resetRecord) {
            return redirect()->route('password.request')
                ->withErrors(['email' => 'This password reset link is invalid or has expired. Please request a new one.']);
        }

        return view('auth.reset-password', [
            'token' => $token,
            'email' => $email ?: $resetRecord->email,
        ]);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
            'email' => 'required|string|exists:users,email',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $record = DB::table('password_resets')
            ->where('email', $request->input('email'))
            ->where('token', $request->input('token'))
            ->first();

        if (!$record) {
            return back()->withErrors([
                'email' => 'Invalid or expired password reset token.',
            ]);
        }

        // Update password
        $user = User::where('email', $request->input('email'))->firstOrFail();
        $user->update([
            'password' => Hash::make($request->input('password')),
        ]);

        // Clean up token
        DB::table('password_resets')->where('email', $user->email)->delete();

        return redirect()->route('login')
            ->with('success', 'Your password has been successfully reset! You can now log in with your new password.');
    }
}
