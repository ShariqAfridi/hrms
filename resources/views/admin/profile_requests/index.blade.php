@extends('layouts.app')

@section('title', 'Bank & National ID Approvals')

@section('content')
<div x-data="profileRequestsManager()" class="space-y-6">

    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center space-x-2">
                <span class="px-2.5 py-1 rounded-lg bg-amber-500/10 border border-amber-500/20 text-amber-400 text-[10px] font-extrabold uppercase tracking-wider">
                    System Admin Clearance
                </span>
                <span class="text-xs text-slate-500">•</span>
                <span class="text-xs font-semibold text-slate-400">Two-Tier Security System</span>
            </div>
            <h1 class="text-2xl font-extrabold text-white tracking-tight mt-1">Bank & Identity Change Approvals</h1>
            <p class="text-xs font-medium text-slate-400 mt-1">Review, authorize, or decline employee-submitted banking and National ID modifications.</p>
        </div>

        <div class="flex items-center space-x-3">
            <a href="{{ route('employees.index') }}" class="px-4 py-2.5 bg-slate-900 border border-slate-800 hover:bg-slate-800 text-slate-300 font-bold text-xs rounded-xl transition-all flex items-center space-x-2">
                <i class="fa-solid fa-users"></i>
                <span>Employee Directory</span>
            </a>
        </div>
    </div>

    <!-- Status Stat Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <a href="{{ route('admin.profile-requests.index') }}" class="p-5 rounded-2xl border transition-all {{ empty($status) ? 'bg-indigo-500/10 border-indigo-500/30 ring-1 ring-indigo-500/20' : 'bg-slate-950 border-slate-800 hover:border-slate-700' }}">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-indigo-400">All Submissions</span>
                <div class="w-8 h-8 rounded-xl bg-indigo-500/20 flex items-center justify-center text-indigo-400">
                    <i class="fa-solid fa-list-check text-xs"></i>
                </div>
            </div>
            <p class="text-2xl font-extrabold text-white mt-2">{{ $pendingCount + $approvedCount + $rejectedCount }}</p>
            <p class="text-[11px] text-slate-400 mt-0.5">Total sensitive data requests</p>
        </a>

        <a href="{{ route('admin.profile-requests.index', ['status' => 'PENDING']) }}" class="p-5 rounded-2xl border transition-all {{ $status === 'PENDING' ? 'bg-amber-500/10 border-amber-500/30 ring-1 ring-amber-500/20' : 'bg-slate-950 border-slate-800 hover:border-slate-700' }}">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-amber-400">Pending Review</span>
                <div class="w-8 h-8 rounded-xl bg-amber-500/20 flex items-center justify-center text-amber-400">
                    <i class="fa-solid fa-clock-rotate-left text-xs"></i>
                </div>
            </div>
            <p class="text-2xl font-extrabold text-white mt-2">{{ $pendingCount }}</p>
            <p class="text-[11px] text-slate-400 mt-0.5">Awaiting System Admin decision</p>
        </a>

        <a href="{{ route('admin.profile-requests.index', ['status' => 'APPROVED']) }}" class="p-5 rounded-2xl border transition-all {{ $status === 'APPROVED' ? 'bg-emerald-500/10 border-emerald-500/30 ring-1 ring-emerald-500/20' : 'bg-slate-950 border-slate-800 hover:border-slate-700' }}">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-emerald-400">Approved</span>
                <div class="w-8 h-8 rounded-xl bg-emerald-500/20 flex items-center justify-center text-emerald-400">
                    <i class="fa-solid fa-circle-check text-xs"></i>
                </div>
            </div>
            <p class="text-2xl font-extrabold text-white mt-2">{{ $approvedCount }}</p>
            <p class="text-[11px] text-slate-400 mt-0.5">Applied to employee profiles</p>
        </a>

        <a href="{{ route('admin.profile-requests.index', ['status' => 'REJECTED']) }}" class="p-5 rounded-2xl border transition-all {{ $status === 'REJECTED' ? 'bg-rose-500/10 border-rose-500/30 ring-1 ring-rose-500/20' : 'bg-slate-950 border-slate-800 hover:border-slate-700' }}">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-rose-400">Declined / Rejected</span>
                <div class="w-8 h-8 rounded-xl bg-rose-500/20 flex items-center justify-center text-rose-400">
                    <i class="fa-solid fa-circle-xmark text-xs"></i>
                </div>
            </div>
            <p class="text-2xl font-extrabold text-white mt-2">{{ $rejectedCount }}</p>
            <p class="text-[11px] text-slate-400 mt-0.5">Security verification failed</p>
        </a>
    </div>

    <!-- Search & Filter Bar -->
    <div class="bg-slate-950/60 border border-slate-800 rounded-2xl p-4 flex flex-col md:flex-row items-center justify-between gap-4 shadow-xl">
        <form action="{{ route('admin.profile-requests.index') }}" method="GET" class="w-full flex flex-col md:flex-row items-center gap-3">
            <div class="relative w-full md:w-80">
                <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-3 text-slate-500 text-xs"></i>
                <input type="text" name="search" value="{{ $search }}" placeholder="Search employee name or email..." class="w-full pl-9 pr-4 py-2 bg-slate-900 border border-slate-800 rounded-xl text-xs font-medium text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500 transition-all">
            </div>

            <select name="status" class="w-full md:w-44 px-3 py-2 bg-slate-900 border border-slate-800 rounded-xl text-xs font-medium text-slate-300 focus:outline-none focus:border-indigo-500 transition-all">
                <option value="ALL" {{ $status === 'ALL' ? 'selected' : '' }}>All Statuses</option>
                <option value="PENDING" {{ $status === 'PENDING' ? 'selected' : '' }}>Pending Only</option>
                <option value="APPROVED" {{ $status === 'APPROVED' ? 'selected' : '' }}>Approved Only</option>
                <option value="REJECTED" {{ $status === 'REJECTED' ? 'selected' : '' }}>Rejected Only</option>
            </select>

            <button type="submit" class="w-full md:w-auto px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-200 font-bold text-xs rounded-xl transition-all">
                Filter
            </button>

            @if($search || $status !== 'PENDING')
                <a href="{{ route('admin.profile-requests.index') }}" class="text-xs font-semibold text-rose-400 hover:underline">Reset Filters</a>
            @endif
        </form>
    </div>

    <!-- Requests Table -->
    <div class="bg-slate-950/60 border border-slate-800 rounded-3xl overflow-hidden shadow-2xl">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-900/80 border-b border-slate-800 text-[11px] font-bold text-slate-400 uppercase tracking-wider">
                        <th class="py-4 px-6">Employee</th>
                        <th class="py-4 px-6">Requested Changes</th>
                        <th class="py-4 px-6">Reason / Remarks</th>
                        <th class="py-4 px-6">Submitted At</th>
                        <th class="py-4 px-6">Status</th>
                        <th class="py-4 px-6 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60 text-xs">
                    @forelse($requests as $req)
                    <tr class="hover:bg-slate-900/30 transition-colors">
                        <!-- Employee Info -->
                        <td class="py-4 px-6">
                            <div class="flex items-center space-x-3">
                                @if($req->user && $req->user->avatar_url)
                                    <img src="{{ $req->user->avatar_url }}" alt="{{ $req->user->name }}" class="w-9 h-9 rounded-xl object-cover ring-1 ring-slate-700 shrink-0">
                                @else
                                    <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white font-bold text-xs shrink-0 shadow-md shadow-indigo-500/20">
                                        {{ $req->user ? $req->user->initials : 'EMP' }}
                                    </div>
                                @endif
                                <div>
                                    <p class="font-bold text-white">{{ $req->user->name ?? 'Unknown User' }}</p>
                                    <p class="text-[11px] text-slate-400">{{ $req->user->email ?? '-' }}</p>
                                    <p class="text-[10px] text-slate-500 mt-0.5">
                                        {{ $req->user->organization->name ?? 'Org' }} • {{ $req->user->designation->title ?? 'Staff' }}
                                    </p>
                                </div>
                            </div>
                        </td>

                        <!-- Requested Changes Breakdown -->
                        <td class="py-4 px-6">
                            <div class="space-y-2 min-w-[260px]">
                                @foreach($req->requested_changes as $key => $newVal)
                                    @php
                                        $oldVal = $req->current_values[$key] ?? '-';
                                        $label = ucwords(str_replace('_', ' ', $key));
                                        if ($key === 'national_id_number') $label = 'National ID / CNIC';
                                        if ($key === 'bank_account_holder_name') $label = 'Account Title';
                                        if ($key === 'bank_account_number') $label = 'Account / IBAN';
                                        if ($key === 'bank_branch_name') $label = 'Bank Branch';
                                    @endphp
                                    <div class="p-2 rounded-xl bg-slate-900/80 border border-slate-800 text-[11px]">
                                        <span class="font-bold text-slate-300 block mb-1">{{ $label }}</span>
                                        <div class="flex items-center space-x-2 text-[11px]">
                                            <span class="line-through text-slate-500 font-mono">{{ $oldVal ?: 'None' }}</span>
                                            <i class="fa-solid fa-arrow-right text-[9px] text-slate-600"></i>
                                            <span class="font-bold text-emerald-400 font-mono bg-emerald-500/10 px-1.5 py-0.5 rounded border border-emerald-500/20">{{ $newVal ?: 'None' }}</span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </td>

                        <!-- Reason -->
                        <td class="py-4 px-6 max-w-[180px]">
                            @if($req->reason)
                                <p class="text-slate-300 italic text-[11px]">"{{ $req->reason }}"</p>
                            @else
                                <span class="text-slate-500 italic text-[11px]">No remarks provided</span>
                            @endif

                            @if($req->rejection_reason)
                                <div class="mt-2 p-2 rounded-lg bg-rose-500/10 border border-rose-500/20 text-rose-400 text-[10px]">
                                    <strong>Declined Reason:</strong> {{ $req->rejection_reason }}
                                </div>
                            @endif
                        </td>

                        <!-- Submitted At -->
                        <td class="py-4 px-6 whitespace-nowrap">
                            <p class="font-semibold text-slate-300">{{ $req->created_at ? (is_string($req->created_at) ? date('d M Y', strtotime($req->created_at)) : $req->created_at->format('d M Y')) : 'N/A' }}</p>
                            <p class="text-[10px] text-slate-500">{{ $req->created_at ? (is_string($req->created_at) ? date('h:i A', strtotime($req->created_at)) : $req->created_at->format('h:i A')) : '' }} ({{ $req->created_at ? (\Carbon\Carbon::parse($req->created_at)->diffForHumans()) : '' }})</p>
                        </td>

                        <!-- Status Badge -->
                        <td class="py-4 px-6 whitespace-nowrap">
                            @if($req->status === 'PENDING')
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold bg-amber-500/10 text-amber-400 border border-amber-500/20">
                                    <i class="fa-solid fa-circle-notch fa-spin mr-1.5 text-[8px]"></i>
                                    Pending Approval
                                </span>
                            @elseif($req->status === 'APPROVED')
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                                    <i class="fa-solid fa-check mr-1.5 text-[8px]"></i>
                                    Approved
                                </span>
                                @if($req->reviewer)
                                    <p class="text-[10px] text-slate-500 mt-1">by {{ $req->reviewer->name }}</p>
                                @endif
                            @elseif($req->status === 'REJECTED')
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold bg-rose-500/10 text-rose-400 border border-rose-500/20">
                                    <i class="fa-solid fa-xmark mr-1.5 text-[8px]"></i>
                                    Rejected
                                </span>
                                @if($req->reviewer)
                                    <p class="text-[10px] text-slate-500 mt-1">by {{ $req->reviewer->name }}</p>
                                @endif
                            @else
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold bg-slate-800 text-slate-400 border border-slate-700">
                                    {{ $req->status }}
                                </span>
                            @endif
                        </td>

                        <!-- Actions -->
                        <td class="py-4 px-6 text-right whitespace-nowrap">
                            @if($req->status === 'PENDING')
                                <div class="flex items-center justify-end space-x-2">
                                    <!-- Approve Form -->
                                    <form action="{{ route('admin.profile-requests.approve', $req->id) }}" method="POST" onsubmit="return confirm('Authorize and apply these banking/NIC changes to this employee account?');">
                                        @csrf
                                        <button type="submit" class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-[11px] rounded-xl shadow-md shadow-emerald-600/20 transition-all flex items-center space-x-1.5">
                                            <i class="fa-solid fa-check text-[10px]"></i>
                                            <span>Approve</span>
                                        </button>
                                    </form>

                                    <!-- Reject Button (Opens Modal) -->
                                    <button type="button" @click="openRejectModal('{{ $req->id }}', '{{ addslashes($req->user->name ?? 'Employee') }}')" class="px-3 py-1.5 bg-rose-600/20 hover:bg-rose-600/30 text-rose-400 border border-rose-500/30 font-bold text-[11px] rounded-xl transition-all flex items-center space-x-1.5">
                                        <i class="fa-solid fa-xmark text-[10px]"></i>
                                        <span>Decline</span>
                                    </button>
                                </div>
                            @else
                                <span class="text-[11px] text-slate-500 italic">Archived</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="py-12 text-center text-slate-500">
                            <div class="inline-flex items-center justify-center w-12 h-12 rounded-2xl bg-slate-900 border border-slate-800 mb-3 text-slate-400">
                                <i class="fa-solid fa-clipboard-check text-lg"></i>
                            </div>
                            <p class="text-sm font-semibold text-slate-300">No change requests found</p>
                            <p class="text-xs text-slate-500 mt-1">There are no employee profile or bank change requests matching this filter.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($requests->hasPages())
        <div class="p-4 border-t border-slate-800/80 bg-slate-900/30">
            {{ $requests->links() }}
        </div>
        @endif
    </div>

    <!-- Decline / Rejection Modal -->
    <div x-show="rejectModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm">
        <div @click.away="rejectModalOpen = false" class="bg-slate-900 border border-slate-800 rounded-3xl w-full max-w-md p-6 shadow-2xl space-y-5 animate-in fade-in zoom-in duration-200">
            <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                <div class="flex items-center space-x-2">
                    <div class="w-8 h-8 rounded-xl bg-rose-500/20 flex items-center justify-center text-rose-400">
                        <i class="fa-solid fa-circle-xmark"></i>
                    </div>
                    <h3 class="font-bold text-white text-sm">Decline Change Request</h3>
                </div>
                <button type="button" @click="rejectModalOpen = false" class="text-slate-400 hover:text-white">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <form :action="'/admin/profile-requests/' + targetRequestId + '/reject'" method="POST" class="space-y-4">
                @csrf
                <p class="text-xs text-slate-300">
                    You are declining the bank / National ID update request for <strong class="text-white" x-text="targetEmployeeName"></strong>.
                </p>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Reason for Rejection *</label>
                    <textarea name="rejection_reason" required rows="3" placeholder="State reason (e.g. Account details mismatch, Incorrect CNIC copy, Please contact HR)..." class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-xs text-white focus:outline-none focus:border-rose-500"></textarea>
                </div>

                <div class="flex items-center justify-end space-x-3 pt-2">
                    <button type="button" @click="rejectModalOpen = false" class="px-4 py-2 bg-slate-800 text-slate-300 font-semibold text-xs rounded-xl hover:bg-slate-700">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-rose-600 hover:bg-rose-500 text-white font-bold text-xs rounded-xl shadow-lg shadow-rose-600/30">Confirm Decline</button>
                </div>
            </form>
        </div>
    </div>

</div>

<script>
function profileRequestsManager() {
    return {
        rejectModalOpen: false,
        targetRequestId: '',
        targetEmployeeName: '',
        openRejectModal(id, name) {
            this.targetRequestId = id;
            this.targetEmployeeName = name;
            this.rejectModalOpen = true;
        }
    }
}
</script>
@endsection
