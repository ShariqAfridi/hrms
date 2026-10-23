@extends('layouts.app')

@section('title', 'Leave Management')

@section('content')

<div x-data="{ applyLeaveModal: false }">

    <!-- Page Header & Apply Leave Button -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-white tracking-tight">Leave Requests</h1>
            <p class="text-xs font-medium text-slate-400 mt-1">Review leave applications, approvals, and employee time-off history.</p>
        </div>

        <button @click="applyLeaveModal = true" class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs rounded-xl shadow-lg shadow-indigo-600/30 transition-all flex items-center justify-center space-x-2">
            <i class="fa-solid fa-plus-circle"></i>
            <span>Request Time Off</span>
        </button>
    </div>

    <!-- Status Filters -->
    <div class="bg-slate-950/60 border border-slate-800 rounded-2xl p-4 shadow-xl">
        <form action="{{ route('leaves.index') }}" method="GET" class="flex flex-wrap items-center gap-2">
            <a href="{{ route('leaves.index') }}" class="px-3.5 py-1.5 rounded-xl font-semibold text-xs transition-all {{ !request('status') ? 'bg-indigo-600 text-white shadow-md' : 'bg-slate-900 text-slate-400 hover:bg-slate-800' }}">All Requests</a>
            <a href="{{ route('leaves.index', ['status' => 'PENDING']) }}" class="px-3.5 py-1.5 rounded-xl font-semibold text-xs transition-all {{ request('status') == 'PENDING' ? 'bg-amber-600 text-white shadow-md' : 'bg-slate-900 text-slate-400 hover:bg-slate-800' }}">Pending</a>
            <a href="{{ route('leaves.index', ['status' => 'APPROVED']) }}" class="px-3.5 py-1.5 rounded-xl font-semibold text-xs transition-all {{ request('status') == 'APPROVED' ? 'bg-emerald-600 text-white shadow-md' : 'bg-slate-900 text-slate-400 hover:bg-slate-800' }}">Approved</a>
            <a href="{{ route('leaves.index', ['status' => 'REJECTED']) }}" class="px-3.5 py-1.5 rounded-xl font-semibold text-xs transition-all {{ request('status') == 'REJECTED' ? 'bg-rose-600 text-white shadow-md' : 'bg-slate-900 text-slate-400 hover:bg-slate-800' }}">Rejected</a>
        </form>
    </div>

    <!-- Leaves Table -->
    <div class="bg-slate-950/60 border border-slate-800 rounded-3xl overflow-hidden shadow-2xl">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-900/80 border-b border-slate-800 text-[11px] font-bold text-slate-400 uppercase tracking-wider">
                        <th class="py-3.5 px-6">Employee</th>
                        <th class="py-3.5 px-6">Leave Type</th>
                        <th class="py-3.5 px-6">Duration</th>
                        <th class="py-3.5 px-6">Reason</th>
                        <th class="py-3.5 px-6">Status</th>
                        <th class="py-3.5 px-6 text-right">Approval Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/80 text-xs">
                    @forelse($leaveRequests as $leave)
                        <tr class="hover:bg-slate-900/40 transition-colors">
                            <td class="py-4 px-6">
                                <div class="flex items-center space-x-3">
                                    <div class="w-8 h-8 rounded-lg bg-purple-600/20 text-purple-400 flex items-center justify-center font-bold text-xs shrink-0">
                                        {{ strtoupper(substr($leave->user->name ?? 'U', 0, 1)) }}
                                    </div>
                                    <div>
                                        <p class="font-bold text-white">{{ $leave->user->name ?? 'User' }}</p>
                                        <p class="text-slate-400 text-[11px] font-medium">{{ $leave->user->department->name ?? 'General' }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="py-4 px-6 font-semibold text-indigo-400">
                                {{ $leave->leaveType->name ?? 'Annual Leave' }}
                            </td>
                            <td class="py-4 px-6">
                                <p class="font-semibold text-white">{{ date('d M', strtotime($leave->start_date)) }} - {{ date('d M Y', strtotime($leave->end_date)) }}</p>
                                <p class="text-slate-400 text-[11px] font-medium">{{ $leave->total_days }} Day(s)</p>
                            </td>
                            <td class="py-4 px-6 text-slate-300 font-medium max-w-xs truncate">
                                {{ $leave->reason }}
                            </td>
                            <td class="py-4 px-6">
                                @if($leave->status == 'PENDING')
                                    <span class="px-2.5 py-1 rounded-full font-semibold bg-amber-500/10 text-amber-400 border border-amber-500/20 text-[11px]">PENDING</span>
                                @elseif($leave->status == 'APPROVED')
                                    <span class="px-2.5 py-1 rounded-full font-semibold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 text-[11px]">APPROVED</span>
                                @else
                                    <span class="px-2.5 py-1 rounded-full font-semibold bg-rose-500/10 text-rose-400 border border-rose-500/20 text-[11px]">REJECTED</span>
                                @endif
                            </td>
                            <td class="py-4 px-6 text-right">
                                @if($leave->status == 'PENDING')
                                    <div class="flex items-center justify-end space-x-2">
                                        <form action="{{ route('leaves.approve', $leave->id) }}" method="POST">
                                            @csrf
                                            <button type="submit" class="px-3 py-1.5 bg-emerald-600/20 hover:bg-emerald-600 text-emerald-400 hover:text-white border border-emerald-500/30 rounded-lg font-bold text-[11px] transition-all">
                                                Approve
                                            </button>
                                        </form>
                                        <form action="{{ route('leaves.reject', $leave->id) }}" method="POST">
                                            @csrf
                                            <button type="submit" class="px-3 py-1.5 bg-rose-600/20 hover:bg-rose-600 text-rose-400 hover:text-white border border-rose-500/30 rounded-lg font-bold text-[11px] transition-all">
                                                Reject
                                            </button>
                                        </form>
                                    </div>
                                @else
                                    <span class="text-slate-500 text-[11px] italic">Processed</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-8 text-center text-slate-500 font-medium">
                                No leave requests recorded.
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

    <!-- Apply Leave Modal -->
    <div x-show="applyLeaveModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm">
        <div @click.away="applyLeaveModal = false" class="bg-slate-900 border border-slate-800 rounded-3xl max-w-md w-full p-6 space-y-5 shadow-2xl">
            <div class="flex items-center justify-between border-b border-slate-800 pb-4">
                <h3 class="text-base font-bold text-white">Request Time Off</h3>
                <button @click="applyLeaveModal = false" class="text-slate-400 hover:text-white"><i class="fa-solid fa-xmark"></i></button>
            </div>

            <form action="{{ route('leaves.store') }}" method="POST" class="space-y-4 text-xs">
                @csrf
                <div>
                    <label class="block font-semibold text-slate-300 mb-1">Leave Type</label>
                    <select name="leave_type_id" required class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white">
                        @foreach($leaveTypes as $lt)
                            <option value="{{ $lt->id }}">{{ $lt->name }} ({{ $lt->max_days_per_year }} days max)</option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-semibold text-slate-300 mb-1">Start Date</label>
                        <input type="date" name="start_date" required value="{{ date('Y-m-d') }}" class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-300 mb-1">End Date</label>
                        <input type="date" name="end_date" required value="{{ date('Y-m-d') }}" class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white">
                    </div>
                </div>

                <div>
                    <label class="block font-semibold text-slate-300 mb-1">Reason for Leave</label>
                    <textarea name="reason" required rows="3" class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white focus:outline-none focus:border-indigo-500" placeholder="State reason for your leave application..."></textarea>
                </div>

                <div class="pt-3 flex justify-end space-x-3">
                    <button type="button" @click="applyLeaveModal = false" class="px-4 py-2 bg-slate-800 text-slate-300 font-semibold rounded-xl">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white font-bold rounded-xl shadow-lg">Submit Application</button>
                </div>
            </form>
        </div>
    </div>

</div>

@endsection
