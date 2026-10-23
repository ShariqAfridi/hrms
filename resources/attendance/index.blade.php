@extends('layouts.app')

@section('title', 'Attendance Management')

@section('content')

<!-- Header & Quick Clock Widget -->
<div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
    <div>
        <h1 class="text-2xl font-extrabold text-white tracking-tight">Attendance Logs</h1>
        <p class="text-xs font-medium text-slate-400 mt-1">Track daily employee attendance, clock-ins, and working hours.</p>
    </div>

    <!-- Clock Widget Card -->
    <div class="bg-slate-950/80 border border-slate-800 rounded-2xl p-4 flex items-center space-x-4 shadow-xl">
        <div>
            <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Your Status Today</p>
            @if($userAttendanceToday)
                <p class="text-xs font-bold text-emerald-400 mt-0.5">
                    Clocked In: {{ date('h:i A', strtotime($userAttendanceToday->clock_in)) }}
                    @if($userAttendanceToday->clock_out)
                        <span class="text-slate-400">| Out: {{ date('h:i A', strtotime($userAttendanceToday->clock_out)) }}</span>
                    @endif
                </p>
            @else
                <p class="text-xs font-bold text-amber-400 mt-0.5">Not Clocked In Yet</p>
            @endif
        </div>

        <div>
            @if(!$userAttendanceToday)
                <form action="{{ route('attendance.clock-in') }}" method="POST">
                    @csrf
                    <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs rounded-xl shadow-lg shadow-emerald-600/30 transition-all flex items-center space-x-1.5">
                        <i class="fa-solid fa-right-to-bracket"></i>
                        <span>Clock In Now</span>
                    </button>
                </form>
            @elseif(!$userAttendanceToday->clock_out)
                <form action="{{ route('attendance.clock-out') }}" method="POST">
                    @csrf
                    <button type="submit" class="px-4 py-2 bg-rose-600 hover:bg-rose-500 text-white font-bold text-xs rounded-xl shadow-lg shadow-rose-600/30 transition-all flex items-center space-x-1.5">
                        <i class="fa-solid fa-right-from-bracket"></i>
                        <span>Clock Out</span>
                    </button>
                </form>
            @else
                <span class="px-3 py-1.5 bg-slate-800 text-slate-400 font-semibold text-xs rounded-xl">Shift Completed</span>
            @endif
        </div>
    </div>
</div>

<!-- Filters -->
<div class="bg-slate-950/60 border border-slate-800 rounded-2xl p-4 shadow-xl">
    <form action="{{ route('attendance.index') }}" method="GET" class="flex flex-col sm:flex-row items-center gap-3">
        <div class="w-full sm:w-48">
            <input type="date" name="date" value="{{ request('date', date('Y-m-d')) }}" class="w-full px-3 py-2 bg-slate-900 border border-slate-800 rounded-xl text-xs font-medium text-white focus:outline-none focus:border-indigo-500">
        </div>

        <div class="w-full sm:w-40">
            <select name="status" class="w-full px-3 py-2 bg-slate-900 border border-slate-800 rounded-xl text-xs font-medium text-slate-300 focus:outline-none focus:border-indigo-500">
                <option value="">All Statuses</option>
                <option value="PRESENT" {{ request('status') == 'PRESENT' ? 'selected' : '' }}>Present</option>
                <option value="LATE" {{ request('status') == 'LATE' ? 'selected' : '' }}>Late</option>
                <option value="ABSENT" {{ request('status') == 'ABSENT' ? 'selected' : '' }}>Absent</option>
            </select>
        </div>

        <button type="submit" class="w-full sm:w-auto px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-200 font-bold text-xs rounded-xl">
            Filter Logs
        </button>
    </form>
</div>

<!-- Attendance Table -->
<div class="bg-slate-950/60 border border-slate-800 rounded-3xl overflow-hidden shadow-2xl">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-slate-900/80 border-b border-slate-800 text-[11px] font-bold text-slate-400 uppercase tracking-wider">
                    <th class="py-3.5 px-6">Employee</th>
                    <th class="py-3.5 px-6">Date</th>
                    <th class="py-3.5 px-6">Clock In</th>
                    <th class="py-3.5 px-6">Clock Out</th>
                    <th class="py-3.5 px-6">Duration</th>
                    <th class="py-3.5 px-6">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-800/80 text-xs">
                @forelse($attendances as $att)
                    <tr class="hover:bg-slate-900/40 transition-colors">
                        <td class="py-4 px-6">
                            <div class="flex items-center space-x-3">
                                <div class="w-8 h-8 rounded-lg bg-indigo-600/20 text-indigo-400 flex items-center justify-center font-bold text-xs shrink-0">
                                    {{ strtoupper(substr($att->user->name ?? 'U', 0, 1)) }}
                                </div>
                                <div>
                                    <p class="font-bold text-white">{{ $att->user->name ?? 'User' }}</p>
                                    <p class="text-slate-400 text-[11px] font-medium">{{ $att->user->department->name ?? 'General' }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="py-4 px-6 font-medium text-slate-300">
                            {{ date('d M Y', strtotime($att->date)) }}
                        </td>
                        <td class="py-4 px-6 font-semibold text-emerald-400">
                            {{ $att->clock_in ? date('h:i A', strtotime($att->clock_in)) : '--' }}
                        </td>
                        <td class="py-4 px-6 font-semibold text-slate-400">
                            {{ $att->clock_out ? date('h:i A', strtotime($att->clock_out)) : '--' }}
                        </td>
                        <td class="py-4 px-6 font-medium text-slate-300">
                            @if($att->total_minutes)
                                {{ floor($att->total_minutes / 60) }}h {{ $att->total_minutes % 60 }}m
                            @else
                                --
                            @endif
                        </td>
                        <td class="py-4 px-6">
                            @if($att->status == 'PRESENT')
                                <span class="px-2.5 py-1 rounded-full font-semibold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 text-[11px]">PRESENT</span>
                            @elseif($att->status == 'LATE')
                                <span class="px-2.5 py-1 rounded-full font-semibold bg-amber-500/10 text-amber-400 border border-amber-500/20 text-[11px]">LATE</span>
                            @else
                                <span class="px-2.5 py-1 rounded-full font-semibold bg-rose-500/10 text-rose-400 border border-rose-500/20 text-[11px]">{{ $att->status }}</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="py-8 text-center text-slate-500 font-medium">
                            No attendance records logged for this date.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="p-4 border-t border-slate-800">
        {{ $attendances->links() }}
    </div>
</div>

@endsection
