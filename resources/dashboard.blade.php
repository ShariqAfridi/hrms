@extends('layouts.app')

@section('title', 'Executive Dashboard')

@section('content')

<!-- Welcome Banner -->
<div class="p-6 lg:p-8 rounded-3xl bg-gradient-to-r from-indigo-900/60 via-slate-900 to-slate-900 border border-indigo-500/20 relative overflow-hidden shadow-2xl">
    <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <span class="inline-flex items-center space-x-2 px-3 py-1 rounded-full bg-indigo-500/10 border border-indigo-500/30 text-indigo-400 text-xs font-semibold uppercase tracking-wider mb-2">
                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                <span>System Operational</span>
            </span>
            <h1 class="text-2xl lg:text-3xl font-extrabold text-white tracking-tight">Good day, {{ Auth::user()->name }}!</h1>
            <p class="text-sm font-medium text-slate-400 mt-1">Here is what is happening across AxeCorp HRMS today.</p>
        </div>

        <!-- Clock In/Out Quick Action Widget -->
        <div class="bg-slate-950/80 border border-slate-800 rounded-2xl p-4 flex items-center space-x-4 shadow-xl">
            <div>
                <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Attendance Today</p>
                @if($userTodayAttendance)
                    <p class="text-sm font-bold text-emerald-400 mt-0.5">
                        <i class="fa-solid fa-clock"></i> Clocked In: {{ date('h:i A', strtotime($userTodayAttendance->clock_in)) }}
                        @if($userTodayAttendance->clock_out)
                            <span class="text-slate-400 font-normal">| Out: {{ date('h:i A', strtotime($userTodayAttendance->clock_out)) }}</span>
                        @endif
                    </p>
                @else
                    <p class="text-sm font-bold text-amber-400 mt-0.5">Not Clocked In</p>
                @endif
            </div>

            <div class="flex items-center space-x-2">
                @if(!$userTodayAttendance)
                    <form action="{{ route('attendance.clock-in') }}" method="POST">
                        @csrf
                        <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs rounded-xl shadow-lg shadow-emerald-600/30 transition-all flex items-center space-x-1.5">
                            <i class="fa-solid fa-right-to-bracket"></i>
                            <span>Clock In</span>
                        </button>
                    </form>
                @elseif(!$userTodayAttendance->clock_out)
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
</div>

<!-- KPI Stats Grid -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">

    <!-- Card 1: Total Employees -->
    <div class="bg-slate-950/60 border border-slate-800 rounded-2xl p-5 relative overflow-hidden group hover:border-slate-700 transition-all">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Total Workforce</p>
                <h3 class="text-3xl font-extrabold text-white mt-1">{{ $totalEmployees }}</h3>
            </div>
            <div class="w-12 h-12 rounded-xl bg-indigo-500/10 border border-indigo-500/20 text-indigo-400 flex items-center justify-center text-xl font-bold">
                <i class="fa-solid fa-users"></i>
            </div>
        </div>
        <p class="text-xs font-medium text-slate-400 mt-4"><span class="text-emerald-400 font-bold"><i class="fa-solid fa-building mr-1"></i>{{ $departmentsCount }}</span> Departments Active</p>
    </div>

    <!-- Card 2: Today's Attendance -->
    <div class="bg-slate-950/60 border border-slate-800 rounded-2xl p-5 relative overflow-hidden group hover:border-slate-700 transition-all">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Today's Presence</p>
                <h3 class="text-3xl font-extrabold text-white mt-1">{{ $todayAttendance }}</h3>
            </div>
            <div class="w-12 h-12 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 flex items-center justify-center text-xl font-bold">
                <i class="fa-solid fa-user-check"></i>
            </div>
        </div>
        <p class="text-xs font-medium text-slate-400 mt-4"><span class="text-emerald-400 font-bold">{{ $presentToday }}</span> On-time / Present</p>
    </div>

    <!-- Card 3: Pending Leaves -->
    <div class="bg-slate-950/60 border border-slate-800 rounded-2xl p-5 relative overflow-hidden group hover:border-slate-700 transition-all">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Pending Leaves</p>
                <h3 class="text-3xl font-extrabold text-white mt-1">{{ $pendingLeavesCount }}</h3>
            </div>
            <div class="w-12 h-12 rounded-xl bg-amber-500/10 border border-amber-500/20 text-amber-400 flex items-center justify-center text-xl font-bold">
                <i class="fa-solid fa-envelope-open-text"></i>
            </div>
        </div>
        <p class="text-xs font-medium text-slate-400 mt-4">Requires Management Approval</p>
    </div>

    <!-- Card 4: Monthly Payroll -->
    <div class="bg-slate-950/60 border border-slate-800 rounded-2xl p-5 relative overflow-hidden group hover:border-slate-700 transition-all">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Monthly Payroll</p>
                <h3 class="text-2xl font-extrabold text-white mt-1">${{ number_format($totalPayrollAmount, 2) }}</h3>
            </div>
            <div class="w-12 h-12 rounded-xl bg-purple-500/10 border border-purple-500/20 text-purple-400 flex items-center justify-center text-xl font-bold">
                <i class="fa-solid fa-money-bill-wave"></i>
            </div>
        </div>
        <p class="text-xs font-medium text-slate-400 mt-4"><span class="text-indigo-400 font-bold">Processed cleanly</span> for AxeCorp HRMS</p>
    </div>
</div>

<!-- Main Section: Recent Hires & Departments -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    <!-- Recent Employees (2 cols) -->
    <div class="lg:col-span-2 bg-slate-950/60 border border-slate-800 rounded-3xl p-6 space-y-4 shadow-xl">
        <div class="flex items-center justify-between">
            <h3 class="text-base font-bold text-white tracking-tight">Recent Hires & Team Members</h3>
            <a href="{{ route('employees.index') }}" class="text-xs font-semibold text-indigo-400 hover:underline">View All <i class="fa-solid fa-arrow-right text-[10px] ml-1"></i></a>
        </div>

        <div class="divide-y divide-slate-800/80">
            @foreach($recentEmployees as $emp)
                <div class="py-3 flex items-center justify-between first:pt-0 last:pb-0">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-indigo-600 to-purple-600 flex items-center justify-center font-bold text-white text-sm shadow-md">
                            {{ strtoupper(substr($emp->name, 0, 1)) }}
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-white">{{ $emp->name }}</p>
                            <p class="text-xs text-slate-400 font-medium">{{ $emp->designation->title ?? 'Team Member' }} &bull; <span class="text-indigo-400">{{ $emp->department->name ?? 'General' }}</span></p>
                        </div>
                    </div>
                    <span class="px-2.5 py-1 rounded-full text-[11px] font-semibold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">Active</span>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Department Distribution (1 col) -->
    <div class="bg-slate-950/60 border border-slate-800 rounded-3xl p-6 space-y-4 shadow-xl">
        <h3 class="text-base font-bold text-white tracking-tight">Department Breakdown</h3>

        <div class="space-y-3">
            @foreach($departments as $dept)
                <div>
                    <div class="flex justify-between text-xs font-medium mb-1">
                        <span class="text-slate-300 font-semibold">{{ $dept->name }}</span>
                        <span class="text-indigo-400 font-bold">{{ $dept->users_count }} Employees</span>
                    </div>
                    <div class="w-full bg-slate-900 rounded-full h-2 overflow-hidden border border-slate-800">
                        <div class="bg-indigo-500 h-2 rounded-full" style="width: {{ $totalEmployees > 0 ? min(100, max(15, ($dept->users_count / $totalEmployees) * 100)) : 0 }}%"></div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

</div>

@endsection
