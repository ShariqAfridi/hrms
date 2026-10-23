@extends('layouts.app')

@section('title', 'Leave Management')

@section('content')

<div x-data="{ 
    applyLeaveModal: false, 
    rejectModalOpen: false, 
    rejectLeaveId: '', 
    rejectReason: '',
    forwardModalOpen: false,
    forwardLeaveId: '',
    forwardNotes: 'Recommended for approval.',
    revokeModalOpen: false,
    revokeLeaveId: '',
    revokeReason: '',
    viewHistoryModal: false,
    selectedLeave: null
}" class="space-y-6 lg:space-y-8">

    <!-- Page Header & Actions -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            @if($isSuperAdmin)
                <h1 class="text-2xl font-extrabold text-white tracking-tight">Leave Management &amp; Oversight</h1>
                <p class="text-xs font-medium text-slate-400 mt-1">Super Admin Monitoring Hub: Monitor employee leave applications, review department approvals, and maintain organizational quotas.</p>
            @else
                <h1 class="text-2xl font-extrabold text-white tracking-tight">Leave Requests &amp; My Leaves</h1>
                <p class="text-xs font-medium text-slate-400 mt-1">Request time off (12 annual leaves allowance) and monitor your application status.</p>
            @endif
        </div>

        <div class="flex items-center space-x-3">
            @if(isset($pendingMyApprovalCount) && $pendingMyApprovalCount > 0)
            <div class="px-3 py-1.5 rounded-xl bg-amber-500/10 border border-amber-500/20 text-amber-400 text-xs font-bold flex items-center space-x-2 animate-pulse">
                <i class="fa-solid fa-bell"></i>
                <span>{{ $pendingMyApprovalCount }} Action Required</span>
            </div>
            @endif

            @if(!$isSuperAdmin)
            <button @click="applyLeaveModal = true" class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs rounded-xl shadow-lg shadow-indigo-600/30 transition-all flex items-center justify-center space-x-2">
                <i class="fa-solid fa-plus-circle"></i>
                <span>Request Time Off</span>
            </button>
            @else
            <div class="px-3.5 py-2 rounded-xl bg-slate-900 border border-slate-800 text-slate-400 text-xs font-semibold flex items-center space-x-2">
                <i class="fa-solid fa-shield-halved text-indigo-400"></i>
                <span>Administrative Oversight Mode</span>
            </div>
            @endif
        </div>
    </div>

    @if($isSuperAdmin)
        <!-- Super Admin Monitoring Hub Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-slate-950/60 border border-slate-800 rounded-2xl p-4 shadow-xl relative overflow-hidden group hover:border-slate-700 transition-all">
                <div class="flex items-center justify-between">
                    <p class="text-xs font-bold text-slate-300">Action Required / Pending</p>
                    <span class="w-8 h-8 rounded-xl bg-amber-500/10 border border-amber-500/20 text-amber-400 flex items-center justify-center text-xs">
                        <i class="fa-solid fa-hourglass-half"></i>
                    </span>
                </div>
                <div class="mt-3 flex items-baseline justify-between">
                    <div>
                        <span class="text-2xl font-extrabold text-white">{{ $monitoringStats['pending_action'] ?? 0 }}</span>
                        <span class="text-[11px] font-medium text-slate-400 ml-1">requests pending</span>
                    </div>
                    <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full bg-amber-500/10 border border-amber-500/20 text-amber-400">
                        Awaiting Review
                    </span>
                </div>
            </div>

            <div class="bg-slate-950/60 border border-slate-800 rounded-2xl p-4 shadow-xl relative overflow-hidden group hover:border-slate-700 transition-all">
                <div class="flex items-center justify-between">
                    <p class="text-xs font-bold text-slate-300">Staff On Leave Today</p>
                    <span class="w-8 h-8 rounded-xl bg-cyan-500/10 border border-cyan-500/20 text-cyan-400 flex items-center justify-center text-xs">
                        <i class="fa-solid fa-calendar-check"></i>
                    </span>
                </div>
                <div class="mt-3 flex items-baseline justify-between">
                    <div>
                        <span class="text-2xl font-extrabold text-white">{{ $monitoringStats['on_leave_today'] ?? 0 }}</span>
                        <span class="text-[11px] font-medium text-slate-400 ml-1">active today</span>
                    </div>
                    <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full bg-cyan-500/10 border border-cyan-500/20 text-cyan-400">
                        {{ date('d M Y') }}
                    </span>
                </div>
            </div>

            <div class="bg-slate-950/60 border border-slate-800 rounded-2xl p-4 shadow-xl relative overflow-hidden group hover:border-slate-700 transition-all">
                <div class="flex items-center justify-between">
                    <p class="text-xs font-bold text-slate-300">Approved Leaves ({{ date('Y') }})</p>
                    <span class="w-8 h-8 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 flex items-center justify-center text-xs">
                        <i class="fa-solid fa-circle-check"></i>
                    </span>
                </div>
                <div class="mt-3 flex items-baseline justify-between">
                    <div>
                        <span class="text-2xl font-extrabold text-white">{{ $monitoringStats['approved_this_year'] ?? 0 }}</span>
                        <span class="text-[11px] font-medium text-slate-400 ml-1">approved</span>
                    </div>
                    <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full bg-emerald-500/10 border border-emerald-500/20 text-emerald-400">
                        Year-to-date
                    </span>
                </div>
            </div>

            <div class="bg-slate-950/60 border border-slate-800 rounded-2xl p-4 shadow-xl relative overflow-hidden group hover:border-slate-700 transition-all">
                <div class="flex items-center justify-between">
                    <p class="text-xs font-bold text-slate-300">Annual Quota Standard</p>
                    <span class="w-8 h-8 rounded-xl bg-indigo-500/10 border border-indigo-500/20 text-indigo-400 flex items-center justify-center text-xs">
                        <i class="fa-solid fa-shield-halved"></i>
                    </span>
                </div>
                <div class="mt-3 flex items-baseline justify-between">
                    <div>
                        <span class="text-2xl font-extrabold text-white">12</span>
                        <span class="text-[11px] font-medium text-slate-400 ml-1">leaves / employee</span>
                    </div>
                    <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full bg-indigo-500/10 border border-indigo-500/20 text-indigo-400">
                        Fixed Quota
                    </span>
                </div>
            </div>
        </div>
    @else
        <!-- Employee Personal Leave Cards (Single 12-Day Quota) -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="bg-slate-950/60 border border-slate-800 rounded-2xl p-4 shadow-xl relative overflow-hidden group hover:border-slate-700 transition-all">
                <div class="flex items-center justify-between">
                    <p class="text-xs font-bold text-slate-300">Annual Leaves Balance</p>
                    <span class="w-2.5 h-2.5 rounded-full bg-indigo-500"></span>
                </div>
                <div class="mt-3 flex items-baseline justify-between">
                    <div>
                        <span class="text-2xl font-extrabold text-white">{{ $remainingLeaves }}</span>
                        <span class="text-[11px] font-medium text-slate-400 ml-1">left of {{ $allocatedLeaves }}</span>
                    </div>
                    <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full bg-slate-900 border border-slate-800 text-slate-400">
                        {{ $usedLeaves }} used
                    </span>
                </div>
                <div class="w-full bg-slate-800/80 rounded-full h-1.5 mt-3 overflow-hidden">
                    <div class="h-1.5 rounded-full transition-all duration-500 bg-indigo-500" style="width: {{ $usagePercent }}%"></div>
                </div>
            </div>

            <div class="bg-slate-950/60 border border-slate-800 rounded-2xl p-4 shadow-xl relative overflow-hidden group hover:border-slate-700 transition-all">
                <div class="flex items-center justify-between">
                    <p class="text-xs font-bold text-slate-300">My Pending Applications</p>
                    <span class="w-2.5 h-2.5 rounded-full bg-amber-400"></span>
                </div>
                <div class="mt-3 flex items-baseline justify-between">
                    <div>
                        <span class="text-2xl font-extrabold text-white">{{ $myPendingCount }}</span>
                        <span class="text-[11px] font-medium text-slate-400 ml-1">under review</span>
                    </div>
                    <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full bg-amber-500/10 border border-amber-500/20 text-amber-400">
                        Awaiting Approval
                    </span>
                </div>
                <p class="text-[10px] text-slate-500 mt-3 truncate">Routes: Team Lead &rarr; Department Head</p>
            </div>

            <div class="bg-slate-950/60 border border-slate-800 rounded-2xl p-4 shadow-xl relative overflow-hidden group hover:border-slate-700 transition-all">
                <div class="flex items-center justify-between">
                    <p class="text-xs font-bold text-slate-300">Approved Leaves ({{ date('Y') }})</p>
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-400"></span>
                </div>
                <div class="mt-3 flex items-baseline justify-between">
                    <div>
                        <span class="text-2xl font-extrabold text-white">{{ $myApprovedCount }}</span>
                        <span class="text-[11px] font-medium text-slate-400 ml-1">taken this year</span>
                    </div>
                    <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full bg-emerald-500/10 border border-emerald-500/20 text-emerald-400">
                        Authorized
                    </span>
                </div>
                <p class="text-[10px] text-slate-500 mt-3 truncate">Deducted from 12 annual allowance</p>
            </div>
        </div>
    @endif

    <!-- Status Filter Tabs -->
    <div class="bg-slate-950/60 border border-slate-800 rounded-2xl p-3 shadow-xl flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <form action="{{ route('leaves.index') }}" method="GET" class="flex flex-wrap items-center gap-2">
            <a href="{{ route('leaves.index') }}" class="px-3.5 py-1.5 rounded-xl font-semibold text-xs transition-all {{ !request('status') ? 'bg-indigo-600 text-white shadow-md' : 'bg-slate-900 text-slate-400 hover:bg-slate-800' }}">All Requests</a>
            <a href="{{ route('leaves.index', ['status' => 'PENDING_LEAD']) }}" class="px-3.5 py-1.5 rounded-xl font-semibold text-xs transition-all {{ request('status') == 'PENDING_LEAD' ? 'bg-amber-600 text-white shadow-md' : 'bg-slate-900 text-slate-400 hover:bg-slate-800' }}">Awaiting Team Lead</a>
            <a href="{{ route('leaves.index', ['status' => 'FORWARDED_TO_DEPT']) }}" class="px-3.5 py-1.5 rounded-xl font-semibold text-xs transition-all {{ request('status') == 'FORWARDED_TO_DEPT' ? 'bg-cyan-600 text-white shadow-md' : 'bg-slate-900 text-slate-400 hover:bg-slate-800' }}">Awaiting Dept Head</a>
            <a href="{{ route('leaves.index', ['status' => 'APPROVED']) }}" class="px-3.5 py-1.5 rounded-xl font-semibold text-xs transition-all {{ request('status') == 'APPROVED' ? 'bg-emerald-600 text-white shadow-md' : 'bg-slate-900 text-slate-400 hover:bg-slate-800' }}">Approved</a>
            <a href="{{ route('leaves.index', ['status' => 'REVOKED']) }}" class="px-3.5 py-1.5 rounded-xl font-semibold text-xs transition-all {{ request('status') == 'REVOKED' ? 'bg-purple-600 text-white shadow-md' : 'bg-slate-900 text-slate-400 hover:bg-slate-800' }}">Revoked by HR</a>
            <a href="{{ route('leaves.index', ['status' => 'REJECTED']) }}" class="px-3.5 py-1.5 rounded-xl font-semibold text-xs transition-all {{ request('status') == 'REJECTED' ? 'bg-rose-600 text-white shadow-md' : 'bg-slate-900 text-slate-400 hover:bg-slate-800' }}">Rejected</a>
        </form>

        <div class="text-[11px] text-slate-400 font-medium">
            Workflow: <span class="text-amber-400 font-bold">Team Lead</span> &rarr; <span class="text-cyan-400 font-bold">Dept Head</span> &rarr; <span class="text-emerald-400 font-bold">Approved</span>
        </div>
    </div>

    <!-- Leaves Table -->
    <div class="bg-slate-950/60 border border-slate-800 rounded-3xl overflow-hidden shadow-2xl">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-900/80 border-b border-slate-800 text-[11px] font-bold text-slate-400 uppercase tracking-wider">
                        <th class="py-3.5 px-6">Employee</th>
                        <th class="py-3.5 px-6">Duration &amp; Schedule</th>
                        <th class="py-3.5 px-6">Workflow Status</th>
                        <th class="py-3.5 px-6">Reason (Why Needed)</th>
                        <th class="py-3.5 px-6 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/80 text-xs">
                    @forelse($leaveRequests as $leave)
                        <tr class="hover:bg-slate-900/40 transition-colors">
                            <td class="py-4 px-6">
                                <div class="flex items-center space-x-3">
                                    <div class="w-8 h-8 rounded-lg bg-indigo-600/20 text-indigo-400 border border-indigo-500/30 flex items-center justify-center font-bold text-xs shrink-0">
                                        {{ strtoupper(substr($leave->user->name ?? 'U', 0, 1)) }}
                                    </div>
                                    <div>
                                        <p class="font-bold text-white">{{ $leave->user->name ?? 'User' }}</p>
                                        <p class="text-slate-400 text-[11px] font-medium">
                                            {{ $leave->user->department->name ?? 'General' }}
                                            @if($leave->user->team)
                                                &bull; <span class="text-indigo-400">{{ $leave->user->team->name }}</span>
                                            @endif
                                        </p>
                                    </div>
                                </div>
                            </td>
                            <td class="py-4 px-6">
                                <p class="font-semibold text-white">{{ date('d M', strtotime($leave->start_date)) }} - {{ date('d M Y', strtotime($leave->end_date)) }}</p>
                                <div class="flex items-center space-x-1.5 mt-0.5">
                                    <span class="text-slate-400 text-[11px] font-medium">{{ $leave->total_days }} Day{{ $leave->total_days > 1 ? 's' : '' }}</span>
                                    @if($leave->is_half_day)
                                        <span class="text-[10px] px-1.5 py-0.5 rounded bg-amber-500/10 border border-amber-500/20 text-amber-400 font-semibold">Half Day</span>
                                    @else
                                        <span class="text-[10px] px-1.5 py-0.5 rounded bg-indigo-500/10 border border-indigo-500/20 text-indigo-400 font-semibold">Full Day</span>
                                    @endif
                                </div>
                            </td>
                            <td class="py-4 px-6">
                                @if($leave->status == 'PENDING_LEAD' || $leave->status == 'PENDING')
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full font-bold bg-amber-500/10 text-amber-400 border border-amber-500/20 text-[11px]">
                                        <i class="fa-solid fa-hourglass-half text-[10px]"></i>
                                        <span>Awaiting Team Lead</span>
                                    </span>
                                @elseif($leave->status == 'FORWARDED_TO_DEPT')
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full font-bold bg-cyan-500/10 text-cyan-400 border border-cyan-500/20 text-[11px]">
                                        <i class="fa-solid fa-arrow-right-arrow-left text-[10px]"></i>
                                        <span>Awaiting Dept Head</span>
                                    </span>
                                    @if($leave->forwarder)
                                        <p class="text-[10px] text-slate-400 mt-1">Endorsed by {{ $leave->forwarder->name }}</p>
                                    @endif
                                @elseif($leave->status == 'APPROVED')
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 text-[11px]">
                                        <i class="fa-solid fa-check-circle text-[10px]"></i>
                                        <span>Approved</span>
                                    </span>
                                    @if($leave->approver)
                                        <p class="text-[10px] text-slate-400 mt-1">By {{ $leave->approver->name }}</p>
                                    @endif
                                @elseif($leave->status == 'REVOKED')
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full font-bold bg-purple-500/10 text-purple-400 border border-purple-500/20 text-[11px]" title="{{ $leave->revocation_reason }}">
                                        <i class="fa-solid fa-ban text-[10px]"></i>
                                        <span>Revoked by HR</span>
                                    </span>
                                    @if($leave->revoker)
                                        <p class="text-[10px] text-slate-400 mt-1">By {{ $leave->revoker->name }}</p>
                                    @endif
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full font-bold bg-rose-500/10 text-rose-400 border border-rose-500/20 text-[11px]">
                                        <i class="fa-solid fa-xmark-circle text-[10px]"></i>
                                        <span>Rejected</span>
                                    </span>
                                    @if($leave->rejection_reason)
                                        <p class="text-[10px] text-rose-300 mt-1 truncate max-w-[150px]" title="{{ $leave->rejection_reason }}">{{ $leave->rejection_reason }}</p>
                                    @endif
                                @endif
                            </td>
                            <td class="py-4 px-6 text-slate-300 max-w-xs">
                                <p class="truncate font-medium" title="{{ $leave->reason }}">{{ $leave->reason }}</p>
                                @if($leave->forward_notes)
                                    <p class="text-[10px] text-cyan-400 font-semibold truncate mt-0.5" title="{{ $leave->forward_notes }}">
                                        <i class="fa-solid fa-quote-left text-[8px] mr-1"></i>{{ $leave->forward_notes }}
                                    </p>
                                @endif
                            </td>
                            <td class="py-4 px-6 text-right">
                                <div class="flex items-center justify-end space-x-1.5">

                                    {{-- Step 1: Team Manager Forwarding Action --}}
                                    @if(($leave->status == 'PENDING_LEAD' || $leave->status == 'PENDING') && Auth::user()->canForwardLeave())
                                        <button type="button" 
                                                @click="forwardLeaveId = '{{ $leave->id }}'; forwardModalOpen = true"
                                                class="px-2.5 py-1.5 bg-cyan-600/20 hover:bg-cyan-600 text-cyan-400 hover:text-white border border-cyan-500/30 rounded-lg font-bold text-[11px] transition-all flex items-center space-x-1"
                                                title="Recommend & Forward to Department Head">
                                            <i class="fa-solid fa-share"></i>
                                            <span>Forward</span>
                                        </button>
                                    @endif

                                    {{-- Step 2: Department Head Direct Approval Action --}}
                                    @if(($leave->status == 'FORWARDED_TO_DEPT' || $leave->status == 'PENDING_LEAD' || $leave->status == 'PENDING') && Auth::user()->canDirectlyApproveLeave())
                                        <form action="{{ route('leaves.approve', $leave->id) }}" method="POST" class="inline">
                                            @csrf
                                            <button type="submit" class="px-2.5 py-1.5 bg-emerald-600/20 hover:bg-emerald-600 text-emerald-400 hover:text-white border border-emerald-500/30 rounded-lg font-bold text-[11px] transition-all flex items-center space-x-1" title="Approve Leave Directly">
                                                <i class="fa-solid fa-check"></i>
                                                <span>Approve</span>
                                            </button>
                                        </form>
                                    @endif

                                    {{-- Rejection Action (Team Lead or Dept Head or Admin) --}}
                                    @if(in_array($leave->status, ['PENDING', 'PENDING_LEAD', 'FORWARDED_TO_DEPT']) && Auth::user()->canApproveLeaves())
                                        <button type="button" 
                                                @click="rejectLeaveId = '{{ $leave->id }}'; rejectModalOpen = true" 
                                                class="px-2 py-1.5 bg-rose-600/20 hover:bg-rose-600 text-rose-400 hover:text-white border border-rose-500/30 rounded-lg font-bold text-[11px] transition-all"
                                                title="Decline Request">
                                            <i class="fa-solid fa-xmark"></i>
                                        </button>
                                    @endif

                                    {{-- Step 3: HR & Super Admin Revocation Action on Approved Leaves --}}
                                    @if($leave->status == 'APPROVED' && Auth::user()->canRevokeLeave())
                                        <button type="button" 
                                                @click="revokeLeaveId = '{{ $leave->id }}'; revokeModalOpen = true"
                                                class="px-2.5 py-1.5 bg-purple-600/20 hover:bg-purple-600 text-purple-300 hover:text-white border border-purple-500/30 rounded-lg font-bold text-[11px] transition-all flex items-center space-x-1"
                                                title="Revoke Approval & Restore Quota">
                                            <i class="fa-solid fa-ban"></i>
                                            <span>Revoke</span>
                                        </button>
                                    @endif

                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-12 text-center text-slate-500 font-medium">
                                <i class="fa-regular fa-calendar-xmark text-3xl mb-2 text-slate-600 block"></i>
                                No leave requests found for the selected view.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4 border-t border-slate-800">
            {{ $leaveRequests->links() }}
        </div>
    </div>

    @if(!$isSuperAdmin)
    <!-- Apply Leave Modal -->
    <div x-show="applyLeaveModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm">
        <div @click.away="applyLeaveModal = false" class="bg-slate-900 border border-slate-800 rounded-3xl max-w-md w-full p-6 space-y-5 shadow-2xl">
            <div class="flex items-center justify-between border-b border-slate-800 pb-4">
                <div>
                    <h3 class="text-base font-bold text-white">Request Time Off</h3>
                    <p class="text-[11px] text-slate-400 mt-0.5">Your request will route to your Team Lead &amp; Department Head.</p>
                </div>
                <button @click="applyLeaveModal = false" class="text-slate-400 hover:text-white"><i class="fa-solid fa-xmark"></i></button>
            </div>

            <form action="{{ route('leaves.store') }}" method="POST" class="space-y-4 text-xs">
                @csrf
                <div class="px-3.5 py-2.5 rounded-xl bg-indigo-500/10 border border-indigo-500/20 flex items-center justify-between">
                    <span class="font-bold text-slate-200 text-xs flex items-center gap-2">
                        <i class="fa-solid fa-calendar-check text-indigo-400"></i>
                        <span>Annual Leaves Allowance</span>
                    </span>
                    <span class="text-xs font-extrabold text-indigo-400">{{ $remainingLeaves ?? 12 }} left of {{ $allocatedLeaves ?? 12 }}</span>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-semibold text-slate-300 mb-1">Start Date <span class="text-rose-400">*</span></label>
                        <input type="date" name="start_date" required value="{{ date('Y-m-d') }}" class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white focus:outline-none focus:border-indigo-500">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-300 mb-1">End Date <span class="text-rose-400">*</span></label>
                        <input type="date" name="end_date" required value="{{ date('Y-m-d') }}" class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white focus:outline-none focus:border-indigo-500">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-semibold text-slate-300 mb-1">Half Day / Full Day</label>
                        <select name="is_half_day" class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white focus:outline-none focus:border-indigo-500">
                            <option value="0">Full Day</option>
                            <option value="1">Half Day (0.5 day)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-300 mb-1">Emergency Phone</label>
                        <input type="text" name="emergency_contact" placeholder="Optional phone" class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white focus:outline-none focus:border-indigo-500">
                    </div>
                </div>

                <div>
                    <label class="block font-semibold text-slate-300 mb-1">Why do you need this leave? <span class="text-rose-400">*</span></label>
                    <textarea name="reason" required rows="3" class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white focus:outline-none focus:border-indigo-500" placeholder="Please state clearly why you need this time off..."></textarea>
                </div>

                <div class="pt-3 flex justify-end space-x-3">
                    <button type="button" @click="applyLeaveModal = false" class="px-4 py-2 bg-slate-800 text-slate-300 font-semibold rounded-xl hover:bg-slate-700 transition-colors">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white font-bold rounded-xl shadow-lg shadow-indigo-600/30 transition-all">Submit Application</button>
                </div>
            </form>
        </div>
    </div>
    @endif

    <!-- Forward to Dept Head Modal (Team Lead Endorsement) -->
    <div x-show="forwardModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm">
        <div @click.away="forwardModalOpen = false" class="bg-slate-900 border border-slate-800 rounded-3xl max-w-md w-full p-6 space-y-5 shadow-2xl">
            <div class="flex items-center justify-between border-b border-slate-800 pb-4">
                <div>
                    <h3 class="text-base font-bold text-white">Endorse &amp; Forward to Dept Head</h3>
                    <p class="text-[11px] text-cyan-400 mt-0.5">Add recommendation notes for the Department Manager.</p>
                </div>
                <button @click="forwardModalOpen = false" class="text-slate-400 hover:text-white"><i class="fa-solid fa-xmark"></i></button>
            </div>

            <form :action="'/leaves/' + forwardLeaveId + '/forward'" method="POST" class="space-y-4 text-xs">
                @csrf
                <div>
                    <label class="block font-semibold text-slate-300 mb-1">Recommendation Notes</label>
                    <textarea name="forward_notes" x-model="forwardNotes" rows="3" class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white focus:outline-none focus:border-cyan-500" placeholder="e.g. Squad workload is covered. Recommended for approval."></textarea>
                </div>

                <div class="pt-3 flex justify-end space-x-3">
                    <button type="button" @click="forwardModalOpen = false" class="px-4 py-2 bg-slate-800 text-slate-300 font-semibold rounded-xl hover:bg-slate-700 transition-colors">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-cyan-600 hover:bg-cyan-500 text-white font-bold rounded-xl shadow-lg shadow-cyan-600/30 transition-all">Endorse &amp; Forward</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Rejection Reason Modal -->
    <div x-show="rejectModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm">
        <div @click.away="rejectModalOpen = false" class="bg-slate-900 border border-slate-800 rounded-3xl max-w-md w-full p-6 space-y-5 shadow-2xl">
            <div class="flex items-center justify-between border-b border-slate-800 pb-4">
                <h3 class="text-base font-bold text-white">Decline Leave Request</h3>
                <button @click="rejectModalOpen = false" class="text-slate-400 hover:text-white"><i class="fa-solid fa-xmark"></i></button>
            </div>

            <form :action="'/leaves/' + rejectLeaveId + '/reject'" method="POST" class="space-y-4 text-xs">
                @csrf
                <div>
                    <label class="block font-semibold text-slate-300 mb-1">Reason for Rejection</label>
                    <textarea name="rejection_reason" x-model="rejectReason" required rows="3" class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white focus:outline-none focus:border-rose-500" placeholder="State reason for declining the request..."></textarea>
                </div>

                <div class="pt-3 flex justify-end space-x-3">
                    <button type="button" @click="rejectModalOpen = false" class="px-4 py-2 bg-slate-800 text-slate-300 font-semibold rounded-xl hover:bg-slate-700 transition-colors">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-rose-600 hover:bg-rose-500 text-white font-bold rounded-xl shadow-lg shadow-rose-600/30 transition-all">Confirm Rejection</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Revocation Reason Modal (HR & Super Admin) -->
    <div x-show="revokeModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm">
        <div @click.away="revokeModalOpen = false" class="bg-slate-900 border border-slate-800 rounded-3xl max-w-md w-full p-6 space-y-5 shadow-2xl">
            <div class="flex items-center justify-between border-b border-slate-800 pb-4">
                <div>
                    <h3 class="text-base font-bold text-white">Revoke Approved Leave</h3>
                    <p class="text-[11px] text-purple-400 mt-0.5">Revoking restores the employee's allocated quota.</p>
                </div>
                <button @click="revokeModalOpen = false" class="text-slate-400 hover:text-white"><i class="fa-solid fa-xmark"></i></button>
            </div>

            <form :action="'/leaves/' + revokeLeaveId + '/revoke'" method="POST" class="space-y-4 text-xs">
                @csrf
                <div>
                    <label class="block font-semibold text-slate-300 mb-1">Mandatory Revocation Reason</label>
                    <textarea name="revocation_reason" x-model="revokeReason" required rows="3" class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white focus:outline-none focus:border-purple-500" placeholder="Specify why this approved leave is being revoked by HR..."></textarea>
                </div>

                <div class="pt-3 flex justify-end space-x-3">
                    <button type="button" @click="revokeModalOpen = false" class="px-4 py-2 bg-slate-800 text-slate-300 font-semibold rounded-xl hover:bg-slate-700 transition-colors">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-purple-600 hover:bg-purple-500 text-white font-bold rounded-xl shadow-lg shadow-purple-600/30 transition-all">Revoke Leave &amp; Restore Balance</button>
                </div>
            </form>
        </div>
    </div>

</div>

@endsection
