<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\ProfileChangeRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProfileChangeRequestController extends Controller
{
    /**
     * Display a listing of profile and bank change requests.
     */
    public function index(Request $request)
    {
        $currentUser = Auth::user();
        if (!$currentUser->isSuperAdmin() && !$currentUser->isOrgAdmin() && !$currentUser->isDeveloperAdmin()) {
            return redirect()->route('dashboard')->with('error', 'Unauthorized access. Only Super Admins and Org Admins can review profile change requests.');
        }

        $status = $request->get('status', 'PENDING');
        $search = $request->get('search');

        $query = ProfileChangeRequest::with(['user.organization', 'user.department', 'user.designation', 'reviewer'])
            ->when($status !== 'ALL', function ($q) use ($status) {
                return $q->where('status', $status);
            })
            ->when($search, function ($q) use ($search) {
                return $q->whereHas('user', function ($uq) use ($search) {
                    $uq->where('name', 'like', "%{$search}%")
                       ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->orderBy('created_at', 'desc');

        $requests = $query->paginate(15)->withQueryString();

        $pendingCount = ProfileChangeRequest::where('status', 'PENDING')->count();
        $approvedCount = ProfileChangeRequest::where('status', 'APPROVED')->count();
        $rejectedCount = ProfileChangeRequest::where('status', 'REJECTED')->count();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'requests' => $requests,
                'pending_count' => $pendingCount,
            ]);
        }

        return view('admin.profile_requests.index', compact('requests', 'pendingCount', 'approvedCount', 'rejectedCount', 'status', 'search'));
    }

    /**
     * Approve a profile / bank change request.
     */
    public function approve($id)
    {
        $currentUser = Auth::user();
        if (!$currentUser->isSuperAdmin() && !$currentUser->isDeveloperAdmin()) {
            return back()->with('error', 'Unauthorized. Only Super Admin can approve sensitive banking and identity updates.');
        }

        $changeRequest = ProfileChangeRequest::with('user')->findOrFail($id);

        if ($changeRequest->status !== 'PENDING') {
            return back()->with('error', 'This request has already been processed.');
        }

        $user = $changeRequest->user;
        if (!$user) {
            return back()->with('error', 'Associated employee account not found.');
        }

        $changes = $changeRequest->requested_changes ?? [];
        $allowedFields = [
            'national_id_number',
            'payment_method',
            'bank_name',
            'bank_account_holder_name',
            'bank_account_number',
            'bank_iban',
            'bank_branch_name',
        ];

        $userUpdates = [];
        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $changes)) {
                $userUpdates[$field] = $changes[$field];
            }
        }

        // Increment sensitive changes count
        $userUpdates['sensitive_changes_count'] = max((int)($user->sensitive_changes_count ?? 0), 1) + 1;
        $user->update($userUpdates);

        // Update change request status
        $changeRequest->update([
            'status' => 'APPROVED',
            'reviewed_by_id' => $currentUser->id,
            'reviewed_at' => now(),
        ]);

        // Send In-App Notification to the Employee
        try {
            Notification::create([
                'user_id' => $user->id,
                'title' => 'Bank & National ID Update Approved',
                'message' => 'Your request to update Bank and/or National ID details has been reviewed and approved by Super Admin (' . $currentUser->name . ').',
                'type' => 'PROFILE_REQUEST_APPROVED',
                'data' => [
                    'request_id' => $changeRequest->id,
                    'approved_by' => $currentUser->name,
                ],
                'action_url' => route('profile.index'),
                'is_read' => false,
            ]);
        } catch (\Throwable $e) {
            // Notification fallback
        }

        return back()->with('success', "Change request for {$user->name} has been approved and applied successfully!");
    }

    /**
     * Reject a profile / bank change request.
     */
    public function reject(Request $request, $id)
    {
        $currentUser = Auth::user();
        if (!$currentUser->isSuperAdmin() && !$currentUser->isDeveloperAdmin()) {
            return back()->with('error', 'Unauthorized. Only Super Admin can reject sensitive banking and identity updates.');
        }

        $changeRequest = ProfileChangeRequest::with('user')->findOrFail($id);

        if ($changeRequest->status !== 'PENDING') {
            return back()->with('error', 'This request has already been processed.');
        }

        $reason = $request->input('rejection_reason', 'Changes could not be verified by Administrator.');

        $changeRequest->update([
            'status' => 'REJECTED',
            'rejection_reason' => $reason,
            'reviewed_by_id' => $currentUser->id,
            'reviewed_at' => now(),
        ]);

        // Send In-App Notification to the Employee
        if ($changeRequest->user) {
            try {
                Notification::create([
                    'user_id' => $changeRequest->user->id,
                    'title' => 'Bank & National ID Update Declined',
                    'message' => 'Your request to update Bank and/or National ID details was declined. Reason: ' . $reason,
                    'type' => 'PROFILE_REQUEST_REJECTED',
                    'data' => [
                        'request_id' => $changeRequest->id,
                        'rejected_by' => $currentUser->name,
                        'reason' => $reason,
                    ],
                    'action_url' => route('profile.index'),
                    'is_read' => false,
                ]);
            } catch (\Throwable $e) {
                // Notification fallback
            }
        }

        return back()->with('info', "Change request for " . ($changeRequest->user->name ?? 'Employee') . " has been rejected.");
    }
}
