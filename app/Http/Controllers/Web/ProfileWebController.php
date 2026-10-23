<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\ProfileChangeRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ProfileWebController extends Controller
{
    public function index()
    {
        $user = Auth::user()->load([
            'role',
            'organization',
            'department',
            'designation',
            'team.leader.designation',
            'manager',
            'payroll',
            'pendingProfileChangeRequest',
            'profileChangeRequests.reviewer'
        ]);

        $pendingRequest = $user->pendingProfileChangeRequest;
        $requestHistory = $user->profileChangeRequests()->where('status', '!=', 'PENDING')->limit(5)->get();

        return view('profile.index', compact('user', 'pendingRequest', 'requestHistory'));
    }

    public function update(Request $request)
    {
        $user = Auth::user();

        // Auto-normalize Pakistani CNIC format (XXXXX-XXXXXXX-X) if 13 raw digits are passed
        if ($request->has('national_id_number') && $request->filled('national_id_number')) {
            $rawCnic = preg_replace('/\D/', '', (string) $request->input('national_id_number'));
            if (strlen($rawCnic) === 13) {
                $request->merge([
                    'national_id_number' => substr($rawCnic, 0, 5) . '-' . substr($rawCnic, 5, 7) . '-' . substr($rawCnic, 12, 1)
                ]);
            }
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:500',
            'date_of_birth' => 'nullable|date',
            'gender' => 'nullable|string|in:male,female,other,Male,Female,Other',
            'marital_status' => 'nullable|string|max:50',
            'nationality' => 'nullable|string|max:100',
            'national_id_number' => ['nullable', 'string', 'regex:/^\d{5}-\d{7}-\d{1}$/'],
            'blood_group' => 'nullable|string|max:10',
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_phone' => 'nullable|string|max:50',
            // Bank & Payment Details
            'payment_method' => 'nullable|string|max:50',
            'bank_name' => 'nullable|string|max:100',
            'bank_account_holder_name' => 'nullable|string|max:150',
            'bank_account_number' => 'nullable|string|max:100',
            'bank_iban' => 'nullable|string|max:100',
            'bank_branch_name' => 'nullable|string|max:100',
            'change_reason' => 'nullable|string|max:500',
            // Avatar & Security (SVG excluded to prevent Stored XSS)
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,webp,gif|max:4096',
            'current_password' => 'nullable|string',
            'new_password' => 'nullable|string|min:8|confirmed',
        ], [
            'national_id_number.regex' => 'The CNIC number must follow the 13-digit pattern XXXXX-XXXXXXX-X (e.g. 42101-1234567-1).',
        ]);

        try {
            // Handle avatar upload
            if ($request->hasFile('avatar')) {
                $file = $request->file('avatar');
                $uploadDir = public_path('uploads/avatars');
                if (!is_dir($uploadDir)) {
                    @mkdir($uploadDir, 0755, true);
                }

                if ($user->avatar && file_exists(public_path($user->avatar))) {
                    @unlink(public_path($user->avatar));
                }

                $ext = $file->extension() ?: 'jpg';
                $filename = 'avatar_' . $user->id . '_' . time() . '.' . $ext;
                $file->move($uploadDir, $filename);
                $validated['avatar'] = 'uploads/avatars/' . $filename;
            }

            // Handle password update if provided
            if ($request->filled('new_password')) {
                $isSuperAdmin = $user->isSuperAdmin() || $user->isDeveloperAdmin();
                if (!empty($user->password) && !$isSuperAdmin) {
                    if (!$request->filled('current_password') || !Hash::check($request->input('current_password'), $user->password)) {
                        return back()->withInput()->withErrors(['current_password' => 'The provided current password does not match your record.']);
                    }
                } elseif ($request->filled('current_password') && !empty($user->password)) {
                    if (!Hash::check($request->input('current_password'), $user->password)) {
                        return back()->withInput()->withErrors(['current_password' => 'The provided current password does not match your record.']);
                    }
                }
                $validated['password'] = Hash::make($request->input('new_password'));
            }

            unset($validated['current_password'], $validated['new_password'], $validated['new_password_confirmation'], $validated['change_reason']);

            // Sensitive fields definition
            $sensitiveFields = [
                'national_id_number',
                'payment_method',
                'bank_name',
                'bank_account_holder_name',
                'bank_account_number',
                'bank_branch_name',
            ];

            $sensitiveChanges = [];
            $currentSensitiveValues = [];

            foreach ($sensitiveFields as $field) {
                $oldVal = trim((string)($user->$field ?? ''));
                $newVal = trim((string)($request->input($field) ?? ''));
                if ($field === 'payment_method' && empty($newVal)) {
                    $newVal = 'BANK_TRANSFER';
                }

                if ($oldVal !== $newVal && $request->has($field)) {
                    $sensitiveChanges[$field] = $request->input($field);
                    $currentSensitiveValues[$field] = $user->$field;
                }
            }

            $isSuperAdmin = $user->isSuperAdmin() || $user->isDeveloperAdmin();
            $changesCount = (int)($user->sensitive_changes_count ?? 0);

            // Case A: No sensitive changes were made, or user is Super Admin
            if (empty($sensitiveChanges) || $isSuperAdmin) {
                if ($isSuperAdmin && !empty($sensitiveChanges)) {
                    $validated['sensitive_changes_count'] = max($changesCount, 1);
                }
                $user->update($validated);
                return redirect()->route('profile.index')->with('success', 'Your profile details have been updated successfully!');
            }

            // Case B: First-time sensitive update by employee (allowed directly)
            if ($changesCount === 0) {
                $validated['sensitive_changes_count'] = 1;
                $user->update($validated);

                return redirect()->route('profile.index')->with('success', 'Profile and Bank details updated successfully! (Note: Any subsequent changes to Bank Details or National ID will require Super Admin approval).');
            }

            // Case C: Second-time onwards sensitive update by employee (Requires Super Admin Approval)
            // Remove sensitive fields from direct user update
            foreach ($sensitiveFields as $field) {
                unset($validated[$field]);
            }
            if (isset($validated['bank_iban'])) {
                unset($validated['bank_iban']);
            }

            // Update non-sensitive fields
            $user->update($validated);

            // Create or update pending change request
            $pendingRequest = ProfileChangeRequest::where('user_id', $user->id)
                ->where('status', 'PENDING')
                ->first();

            if ($pendingRequest) {
                $mergedChanges = array_merge($pendingRequest->requested_changes ?? [], $sensitiveChanges);
                $mergedOld = array_merge($pendingRequest->current_values ?? [], $currentSensitiveValues);
                $pendingRequest->update([
                    'requested_changes' => $mergedChanges,
                    'current_values' => $mergedOld,
                    'reason' => $request->input('change_reason') ?? $pendingRequest->reason,
                ]);
            } else {
                $pendingRequest = ProfileChangeRequest::create([
                    'user_id' => $user->id,
                    'type' => 'BANK_AND_NIC',
                    'status' => 'PENDING',
                    'requested_changes' => $sensitiveChanges,
                    'current_values' => $currentSensitiveValues,
                    'reason' => $request->input('change_reason'),
                ]);
            }

            // Notify Super Admins
            try {
                $superAdmins = User::whereHas('role', function ($q) {
                    $q->whereIn('name', ['SYSTEM_ADMIN', 'SUPER_ADMIN'])->orWhere('is_wildcard', true)->orWhere('level', 0);
                })->get();

                foreach ($superAdmins as $admin) {
                    Notification::create([
                        'user_id' => $admin->id,
                        'title' => 'Pending Bank / NIC Approval',
                        'message' => "{$user->name} has requested an update to their Bank Details and/or National ID.",
                        'type' => 'PROFILE_REQUEST_PENDING',
                        'data' => [
                            'request_id' => $pendingRequest->id,
                            'employee_id' => $user->id,
                            'employee_name' => $user->name,
                        ],
                        'action_url' => route('admin.profile-requests.index'),
                        'is_read' => false,
                    ]);
                }
            } catch (\Throwable $e) {
                // Ignore notification failure
            }

            return redirect()->route('profile.index')->with('info', 'Personal details updated. Your requested changes to Bank Details / National ID have been submitted for Super Admin verification and approval.');

        } catch (\Throwable $e) {
            return back()->withInput()->with('error', 'Could not update profile: ' . $e->getMessage());
        }
    }

    /**
     * Employee cancels their own pending profile change request.
     */
    public function cancelRequest($id)
    {
        $user = Auth::user();
        $changeRequest = ProfileChangeRequest::where('user_id', $user->id)
            ->where('id', $id)
            ->where('status', 'PENDING')
            ->firstOrFail();

        $changeRequest->update(['status' => 'CANCELLED']);

        return back()->with('info', 'Your pending Bank / National ID change request has been cancelled.');
    }

    public function updateAvatar(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'avatar' => 'required|image|mimes:jpeg,png,jpg,webp,gif,svg|max:4096',
        ]);

        try {
            $file = $request->file('avatar');
            $uploadDir = public_path('uploads/avatars');
            if (!is_dir($uploadDir)) {
                @mkdir($uploadDir, 0755, true);
            }

            if ($user->avatar && file_exists(public_path($user->avatar))) {
                @unlink(public_path($user->avatar));
            }

            $ext = $file->extension() ?: ($file->getClientOriginalExtension() ?: 'jpg');
            $filename = 'avatar_' . $user->id . '_' . time() . '.' . $ext;
            $file->move($uploadDir, $filename);
            $avatarPath = 'uploads/avatars/' . $filename;

            $user->update(['avatar' => $avatarPath]);

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Profile picture updated successfully!',
                    'avatar_url' => asset($avatarPath),
                ]);
            }

            return back()->with('success', 'Profile picture updated successfully!');
        } catch (\Throwable $e) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Upload failed: ' . $e->getMessage(),
                ], 422);
            }
            return back()->with('error', 'Failed to upload profile picture: ' . $e->getMessage());
        }
    }

    public function deleteAvatar(Request $request)
    {
        $user = Auth::user();

        if ($user->avatar && file_exists(public_path($user->avatar))) {
            @unlink(public_path($user->avatar));
        }

        $user->update(['avatar' => null]);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Profile picture removed successfully.',
                'initials' => $user->initials,
            ]);
        }

        return back()->with('success', 'Profile picture removed successfully.');
    }

    /**
     * Set, update, or remove the Payroll Security PIN.
     */
    public function updatePayrollPin(Request $request)
    {
        $user = User::find(Auth::id()) ?? Auth::user();

        // Strictly Super Admin only
        if (!$user->isSuperAdmin() && !$user->isDeveloperAdmin()) {
            abort(403, 'Unauthorized. Only Super Admin can configure a Payroll Security PIN.');
        }

        // Ensure database column exists on live DB
        try {
            if (!\Illuminate\Support\Facades\Schema::hasColumn('users', 'payroll_pin')) {
                \Illuminate\Support\Facades\Schema::table('users', function (\Illuminate\Database\Schema\Blueprint $table) {
                    $table->string('payroll_pin')->nullable()->after('password');
                });
            }
        } catch (\Throwable $e) {
            try {
                \Illuminate\Support\Facades\DB::statement("ALTER TABLE users ADD COLUMN payroll_pin VARCHAR(255) NULL");
            } catch (\Throwable $e2) {}
        }

        // Handle PIN removal
        if ($request->input('action') === 'remove') {
            $currentAuth = trim((string)($request->input('current_pin_or_password') ?? $request->input('current_password', '')));
            if (!empty($currentAuth)) {
                $matchesPassword = Hash::check($currentAuth, $user->password);
                $matchesPin = $user->verifyPayrollPin($currentAuth);
                if (!$matchesPassword && !$matchesPin) {
                    return back()->withErrors(['pin_current_password' => 'The provided verification (PIN or account password) does not match.'])->withInput();
                }
            }

            $user->payroll_pin = null;
            $user->save();
            try {
                \Illuminate\Support\Facades\DB::table('users')
                    ->where('id', $user->id)
                    ->update(['payroll_pin' => null]);
            } catch (\Throwable $e) {}

            session()->forget('payroll_pin_verified');

            return back()->with('success', 'Payroll Security PIN has been removed. You will now access the Payroll hub directly.');
        }

        // Handle PIN setup / update / reset
        $hasPin = $user->hasPayrollPin();

        $request->validate([
            'pin' => ['required', 'regex:/^[0-9]{4}$/', 'confirmed'],
        ], [
            'pin.required' => 'Please enter a 4-digit numeric Security PIN.',
            'pin.regex' => 'The Payroll Security PIN must be exactly 4 numeric digits (0-9).',
            'pin.confirmed' => 'The PIN confirmation does not match.',
        ]);

        $currentAuth = trim((string)($request->input('current_pin_or_password') ?? $request->input('current_password', '')));
        if (!empty($currentAuth)) {
            $matchesPassword = Hash::check($currentAuth, $user->password);
            $matchesPin = $user->verifyPayrollPin($currentAuth);
            if (!$matchesPassword && !$matchesPin) {
                return back()->withErrors(['pin_current_password' => 'The provided PIN or account password does not match. You can leave it blank to reset your PIN directly.'])->withInput();
            }
        }

        $hashedPin = Hash::make((string)$request->input('pin'));
        $user->payroll_pin = $hashedPin;
        $user->save();

        try {
            \Illuminate\Support\Facades\DB::table('users')
                ->where('id', $user->id)
                ->update(['payroll_pin' => $hashedPin]);
        } catch (\Throwable $e) {}

        // Ensure vault is locked by default so access requires entering the PIN
        session()->forget('payroll_pin_verified');

        return back()->with('success', 'Payroll Security PIN ' . ($hasPin ? 'updated / reset' : 'activated') . ' successfully! The Payroll vault is now locked and protected with your new PIN.');
    }
}
