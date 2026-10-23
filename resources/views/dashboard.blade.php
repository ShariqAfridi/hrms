@extends('layouts.app')

@section('title', 'Executive Dashboard')

@section('content')

<div x-data="dashboardHub()" x-init="initDashboard()" class="space-y-6">

<!-- Welcome Banner -->
<div class="p-6 lg:p-8 rounded-3xl bg-gradient-to-r from-indigo-900/60 via-slate-900 to-slate-900 border border-indigo-500/20 relative overflow-hidden shadow-2xl">
    <!-- Ambient Glow Orbs -->
    <div class="absolute -top-24 -right-24 w-96 h-96 bg-indigo-500/10 rounded-full blur-3xl pointer-events-none"></div>
    <div class="absolute -bottom-24 -left-24 w-80 h-80 bg-purple-500/10 rounded-full blur-3xl pointer-events-none"></div>

    <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <span class="inline-flex items-center space-x-2 px-3 py-1 rounded-full bg-indigo-500/10 border border-indigo-500/30 text-indigo-400 text-xs font-semibold uppercase tracking-wider mb-2">
                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                <span>System Operational • Live Sync</span>
            </span>
            <h1 class="text-2xl lg:text-3xl font-extrabold text-white tracking-tight">Good day, {{ Auth::user()->name }}!</h1>
            <p class="text-sm font-medium text-slate-400 mt-1">Here is what is happening across AxeCorp today.</p>
        </div>

        @feature('attendance')
        @clockinexempt
        <!-- Super Admin & HR Staff Attendance Oversight Widget -->
        <div class="bg-slate-950/90 border border-slate-800 rounded-3xl p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-2xl min-w-[320px]">
            <div>
                <div class="flex items-center space-x-2">
                    <span class="px-2.5 py-0.5 rounded-full bg-indigo-500/10 border border-indigo-500/20 text-indigo-400 text-[10px] font-extrabold uppercase tracking-wider">
                        <i class="fa-solid fa-shield-halved mr-1"></i> Staff Attendance Oversight
                    </span>
                    <span class="text-[10px] text-slate-500 font-medium">Exempt from Clock-In</span>
                </div>
                <p class="text-xs font-bold text-white mt-1.5 flex items-center space-x-2">
                    <span class="inline-flex items-center text-emerald-400 font-extrabold">
                        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse mr-1.5"></span>
                        {{ $presentToday ?? 0 }} Present Today
                    </span>
                    <span class="text-slate-600">•</span>
                    <span class="text-slate-300">{{ $totalEmployees ?? 0 }} Staff Total</span>
                    @if(isset($todayLateCount) && $todayLateCount > 0)
                        <span class="text-slate-600">•</span>
                        <span class="text-orange-400 font-semibold">{{ $todayLateCount }} Late</span>
                    @endif
                    @if(isset($todayOnBreakCount) && $todayOnBreakCount > 0)
                        <span class="text-slate-600">•</span>
                        <span class="text-amber-400 font-semibold">{{ $todayOnBreakCount }} on Break</span>
                    @endif
                </p>
            </div>

            <div class="flex items-center space-x-2 shrink-0">
                <a href="{{ route('attendance.index') }}" class="px-4 py-2.5 bg-gradient-to-r from-indigo-600 to-indigo-700 hover:from-indigo-500 hover:to-indigo-600 text-white font-bold text-xs rounded-xl shadow-lg shadow-indigo-600/30 transition-all flex items-center space-x-2 whitespace-nowrap">
                    <i class="fa-solid fa-clipboard-user text-indigo-200"></i>
                    <span>Check Staff Attendance</span>
                    <i class="fa-solid fa-arrow-right text-[10px] ml-0.5 text-indigo-300"></i>
                </a>
            </div>
        </div>
        @else
        <!-- Clock In/Out & Break Quick Action Widget (Employees, Leads & Dept Heads) -->
        <div class="bg-slate-950/90 border border-slate-800 rounded-3xl p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-2xl min-w-[320px]">
            <div>
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Attendance & Breaks Today</p>
                @if(!$userTodayAttendance)
                    <p class="text-sm font-black text-amber-400 mt-0.5 flex items-center space-x-1.5">
                        <span class="w-2 h-2 rounded-full bg-amber-400 animate-ping"></span>
                        <span>Not Clocked In</span>
                    </p>
                @elseif($userTodayAttendance->clock_out)
                    <p class="text-xs font-bold text-slate-300 mt-0.5">
                        Shift Ended: <span class="text-emerald-400">{{ $userTodayAttendance->getFormattedNetTime() }} Net Work</span>
                    </p>
                @elseif($userTodayAttendance->isOnBreak())
                    <p class="text-xs font-bold text-amber-400 mt-0.5 flex items-center space-x-1.5 animate-pulse">
                        <i class="fa-solid fa-mug-hot"></i>
                        <span>On {{ ucfirst(strtolower($userTodayAttendance->current_break_type)) }} Break</span>
                    </p>
                @else
                    <p class="text-xs font-bold text-emerald-400 mt-0.5 flex items-center space-x-1.5">
                        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                        <span>In at {{ date('h:i A', strtotime($userTodayAttendance->clock_in)) }}</span>
                        <span class="text-slate-400 font-normal">| Net: {{ $userTodayAttendance->getFormattedNetTime() }}</span>
                    </p>
                @endif
            </div>

            <div class="flex items-center space-x-2">
                @if(!$userTodayAttendance)
                    <form action="{{ route('attendance.clock-in') }}" method="POST">
                        @csrf
                        <button type="submit" class="px-4 py-2.5 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white font-bold text-xs rounded-xl shadow-lg shadow-emerald-600/30 transition-all flex items-center space-x-1.5">
                            <i class="fa-solid fa-right-to-bracket"></i>
                            <span>Clock In Office</span>
                        </button>
                    </form>
                @elseif(!$userTodayAttendance->clock_out)
                    @if($userTodayAttendance->isOnBreak())
                        <form action="{{ route('attendance.end-break') }}" method="POST">
                            @csrf
                            <button type="submit" class="px-3.5 py-2 bg-gradient-to-r from-amber-600 to-emerald-600 hover:from-amber-500 hover:to-emerald-500 text-white font-bold text-xs rounded-xl shadow-lg shadow-amber-600/30 transition-all flex items-center space-x-1.5 animate-bounce">
                                <i class="fa-solid fa-play"></i>
                                <span>End Break</span>
                            </button>
                        </form>
                    @else
                        <!-- Quick Break Buttons & Clock Out Dropdown -->
                        <div class="flex items-center space-x-1.5">
                            <!-- Lunch (1 hr) -->
                            <form action="{{ route('attendance.start-break') }}" method="POST">
                                @csrf
                                <input type="hidden" name="break_type" value="LUNCH">
                                <button type="submit" class="px-2.5 py-1.5 bg-amber-500/10 hover:bg-amber-500/20 text-amber-300 border border-amber-500/30 rounded-xl text-[11px] font-bold transition-all flex items-center space-x-1" title="Lunch Break (60 mins)">
                                    <i class="fa-solid fa-utensils text-[10px]"></i>
                                    <span>Lunch</span>
                                </button>
                            </form>

                            <!-- Namaz -->
                            <form action="{{ route('attendance.start-break') }}" method="POST">
                                @csrf
                                <input type="hidden" name="break_type" value="NAMAZ">
                                <button type="submit" class="px-2.5 py-1.5 bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 rounded-xl text-[11px] font-bold transition-all flex items-center space-x-1" title="Namaz Break (20 mins)">
                                    <i class="fa-solid fa-mosque text-[10px]"></i>
                                    <span>Namaz</span>
                                </button>
                            </form>

                            <!-- Smoke -->
                            <form action="{{ route('attendance.start-break') }}" method="POST">
                                @csrf
                                <input type="hidden" name="break_type" value="SMOKE">
                                <button type="submit" class="px-2.5 py-1.5 bg-orange-500/10 hover:bg-orange-500/20 text-orange-300 border border-orange-500/30 rounded-xl text-[11px] font-bold transition-all flex items-center space-x-1" title="Smoke Break (15 mins)">
                                    <i class="fa-solid fa-smoking text-[10px]"></i>
                                    <span>Smoke</span>
                                </button>
                            </form>

                            <!-- Clock Out -->
                            <form action="{{ route('attendance.clock-out') }}" method="POST" onsubmit="return confirm('Are you sure you want to Clock Out and end your shift?');">
                                @csrf
                                <button type="submit" class="px-3 py-1.5 bg-rose-600 hover:bg-rose-500 text-white font-bold text-xs rounded-xl shadow-md transition-all flex items-center space-x-1" title="Clock Out">
                                    <i class="fa-solid fa-right-from-bracket"></i>
                                </button>
                            </form>
                        </div>
                    @endif
                @else
                    <span class="px-3 py-1.5 bg-slate-800 text-slate-400 font-semibold text-xs rounded-xl">Shift Completed</span>
                @endif
            </div>
        </div>
        @endclockinexempt
        @endfeature
    </div>
</div>

@employee
<!-- EMPLOYEE SELF-SERVICE DASHBOARD -->
<div class="space-y-6">
    <!-- Employee KPI Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
        
        <!-- Card 1: Today Status -->
        <div class="bg-slate-950/60 border border-slate-800 border-t-4 border-t-emerald-500 rounded-2xl p-5 relative overflow-hidden group hover:border-slate-700 transition-all">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Attendance & Breaks</p>
                    <h3 class="text-lg font-extrabold text-white mt-1">
                        @if(!$userTodayAttendance)
                            Not Clocked In
                        @elseif($userTodayAttendance->clock_out)
                            Shift Completed
                        @elseif($userTodayAttendance->isOnBreak())
                            On {{ ucfirst(strtolower($userTodayAttendance->current_break_type)) }} Break
                        @else
                            Working / At Desk
                        @endif
                    </h3>
                </div>
                <div class="w-12 h-12 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 flex items-center justify-center text-xl font-bold">
                    <i class="fa-solid fa-user-clock"></i>
                </div>
            </div>
            <p class="text-xs font-medium text-slate-400 mt-4">
                @if($userTodayAttendance)
                    Net Time: <span class="text-emerald-400 font-bold">{{ $userTodayAttendance->getFormattedNetTime() }}</span>
                    <span class="text-slate-500">| Breaks: {{ $userTodayAttendance->getFormattedBreakTime() }}</span>
                @else
                    <span class="text-amber-400 font-semibold">Please clock in to record time</span>
                @endif
            </p>
        </div>

        <!-- Card 2: My Pending Tasks -->
        @feature('projects')
        <div class="bg-slate-950/60 border border-slate-800 border-t-4 border-t-blue-500 rounded-2xl p-5 relative overflow-hidden group hover:border-slate-700 transition-all">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Assigned Tasks</p>
                    <h3 class="text-3xl font-extrabold text-white mt-1" x-text="stats.myPendingTasksCount">{{ count($myPendingTasks) }}</h3>
                </div>
                <div class="w-12 h-12 rounded-xl bg-blue-500/10 border border-blue-500/20 text-blue-400 flex items-center justify-center text-xl font-bold">
                    <i class="fa-solid fa-list-check"></i>
                </div>
            </div>
            <p class="text-xs font-medium text-slate-400 mt-4"><span class="text-emerald-400 font-bold" x-text="stats.myCompletedTasksCount">{{ $myCompletedTasksCount }}</span> Tasks Completed</p>
        </div>
        @endfeature

        <!-- Card 3: My Leaves -->
        @feature('leaves')
        <div class="bg-slate-950/60 border border-slate-800 border-t-4 border-t-amber-500 rounded-2xl p-5 relative overflow-hidden group hover:border-slate-700 transition-all">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">My Leave Requests</p>
                    <h3 class="text-3xl font-extrabold text-white mt-1" x-text="stats.myPendingLeaves">{{ $myPendingLeaves }}</h3>
                </div>
                <div class="w-12 h-12 rounded-xl bg-amber-500/10 border border-amber-500/20 text-amber-400 flex items-center justify-center text-xl font-bold">
                    <i class="fa-solid fa-calendar-check"></i>
                </div>
            </div>
            <p class="text-xs font-medium text-slate-400 mt-4"><span class="text-emerald-400 font-bold">{{ $myApprovedLeaves }}</span> Approved this cycle</p>
        </div>
        @endfeature

    </div>

    <!-- Employee Tasks & Recent Activity Section -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- My Active Tasks (2 cols) -->
        @feature('projects')
        <div class="lg:col-span-2 bg-slate-950/60 border border-slate-800 rounded-3xl p-6 space-y-4 shadow-xl">
            <div class="flex items-center justify-between">
                <h3 class="text-base font-bold text-white tracking-tight flex items-center space-x-2">
                    <i class="fa-solid fa-diagram-project text-indigo-400"></i>
                    <span>My Assigned Tasks</span>
                </h3>
                <a href="{{ route('projects.index') }}" class="text-xs font-semibold text-indigo-400 hover:underline">Projects Board <i class="fa-solid fa-arrow-right text-[10px] ml-1"></i></a>
            </div>

            @if(count($myPendingTasks) > 0)
                <div class="divide-y divide-slate-800/80">
                    @foreach($myPendingTasks as $task)
                        <div class="py-3.5 flex items-center justify-between first:pt-0 last:pb-0">
                            <div class="space-y-1">
                                <p class="text-sm font-semibold text-white">{{ $task->title }}</p>
                                <p class="text-xs text-slate-400 font-medium">Project: <span class="text-indigo-400">{{ $task->project->title ?? 'General' }}</span> &bull; Due: {{ $task->due_date ? date('M d, Y', strtotime($task->due_date)) : 'Ongoing' }}</p>
                            </div>
                            <div class="flex items-center space-x-3">
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold {{ $task->status === 'IN_PROGRESS' ? 'bg-indigo-500/10 text-indigo-400 border border-indigo-500/20' : 'bg-slate-800 text-slate-400' }}">
                                    {{ str_replace('_', ' ', $task->status) }}
                                </span>
                                <form action="{{ route('projects.tasks.status', $task->id) }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="status" value="{{ $task->status === 'TO_DO' ? 'IN_PROGRESS' : 'COMPLETED' }}">
                                    <button type="submit" class="px-3 py-1 bg-indigo-600/20 hover:bg-indigo-600 hover:text-white border border-indigo-500/30 text-indigo-300 text-xs font-semibold rounded-lg transition-all">
                                        {{ $task->status === 'TO_DO' ? 'Start' : 'Complete' }}
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="py-8 text-center text-slate-500 text-xs font-medium">
                    <i class="fa-solid fa-circle-check text-2xl text-emerald-400 mb-2 block"></i>
                    All caught up! You have no pending tasks right now.
                </div>
            @endif
        </div>
        @endfeature

        <!-- My Recent Attendance (1 col) -->
        @feature('attendance')
        <div class="bg-slate-950/60 border border-slate-800 rounded-3xl p-6 space-y-4 shadow-xl {{ !feature_enabled('projects') ? 'lg:col-span-3' : '' }}">
            <div class="flex items-center justify-between">
                <h3 class="text-base font-bold text-white tracking-tight">Recent Attendance</h3>
                <a href="{{ route('attendance.index') }}" class="text-xs font-semibold text-indigo-400 hover:underline">Full Log</a>
            </div>

            <div class="space-y-2.5">
                @foreach($myRecentAttendance as $att)
                    <div class="p-3 bg-slate-900/60 border border-slate-800/80 rounded-xl flex items-center justify-between">
                        <div>
                            <p class="text-xs font-bold text-white">{{ date('D, d M', strtotime($att->date)) }}</p>
                            <p class="text-[11px] text-slate-400 font-medium">In: {{ $att->clock_in ? date('h:i A', strtotime($att->clock_in)) : '-' }} | Out: {{ $att->clock_out ? date('h:i A', strtotime($att->clock_out)) : '-' }}</p>
                        </div>
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $att->status === 'PRESENT' ? 'bg-emerald-500/10 text-emerald-400' : ($att->status === 'LATE' ? 'bg-amber-500/10 text-amber-400' : 'bg-rose-500/10 text-rose-400') }}">
                            {{ $att->status }}
                        </span>
                    </div>
                @endforeach
            </div>
        </div>
        @endfeature

    </div>

    @feature('teams')
    @if(Auth::user()->team)
    <div class="squad-banner-card bg-gradient-to-r from-slate-950 via-slate-900 to-indigo-950/40 border border-slate-800 rounded-3xl p-6 shadow-xl relative overflow-hidden">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="flex items-center space-x-4">
                <div class="w-12 h-12 rounded-2xl bg-amber-500/10 border border-amber-500/20 text-amber-400 flex items-center justify-center text-xl shrink-0 shadow-md">
                    <i class="fa-solid fa-people-group"></i>
                </div>
                <div>
                    <div class="flex items-center space-x-2">
                        <span class="text-[10px] font-extrabold uppercase px-2 py-0.5 rounded-full bg-amber-500/20 text-amber-300 border border-amber-500/30">My Squad</span>
                        <span class="text-xs text-slate-500">&bull;</span>
                        <span class="text-xs text-slate-400 font-medium">{{ Auth::user()->team->department->name ?? 'Squad' }}</span>
                    </div>
                    <h3 class="text-lg font-black text-white tracking-tight mt-0.5">{{ Auth::user()->team->name }}</h3>
                </div>
            </div>

            @if(Auth::user()->team->leader)
            <div class="flex items-center space-x-3 bg-slate-950/70 border border-slate-800/80 px-4 py-3 rounded-2xl">
                @if(Auth::user()->team->leader->avatar_url)
                    <img src="{{ Auth::user()->team->leader->avatar_url }}" alt="{{ Auth::user()->team->leader->name }}" class="w-10 h-10 rounded-xl object-cover ring-2 ring-amber-500/30 shrink-0">
                @else
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-amber-600 to-orange-600 flex items-center justify-center font-bold text-white text-xs shadow-md shrink-0">
                        {{ Auth::user()->team->leader->initials }}
                    </div>
                @endif
                <div>
                    <div class="flex items-center space-x-1.5">
                        <p class="text-xs font-bold text-white">{{ Auth::user()->team->leader->name }}</p>
                        <span class="px-1.5 py-0.5 rounded bg-amber-500 text-slate-950 text-[9px] font-extrabold uppercase">Lead</span>
                    </div>
                    <p class="text-[11px] text-slate-400 font-medium">{{ Auth::user()->team->leader->email }}</p>
                </div>
            </div>
            @endif

            <a href="{{ route('teams.index') }}" class="self-start sm:self-center px-4 py-2.5 bg-slate-800 hover:bg-slate-700 text-white text-xs font-bold rounded-xl transition-all flex items-center space-x-2">
                <span>View Squad Hub</span>
                <i class="fa-solid fa-arrow-right text-[10px]"></i>
            </a>
        </div>
    </div>
    @endif
    @endfeature
</div>

@else

<!-- ADMIN & MANAGER EXECUTIVE OVERVIEW DASHBOARD -->
<div class="space-y-6">
    <!-- KPI Stats Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">

        @feature('employees')
        <!-- Card 1: Total Employees -->
        <div class="bg-slate-950/60 border border-slate-800 border-t-4 border-t-indigo-500 rounded-2xl p-5 relative overflow-hidden group hover:border-slate-700 transition-all">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Total Workforce</p>
                    <h3 class="text-3xl font-extrabold text-white mt-1" x-text="stats.totalEmployees">{{ $totalEmployees }}</h3>
                </div>
                <div class="w-12 h-12 rounded-xl bg-indigo-500/10 border border-indigo-500/20 text-indigo-400 flex items-center justify-center text-xl font-bold shadow-xs">
                    <i class="fa-solid fa-users"></i>
                </div>
            </div>
            <p class="text-xs font-medium text-slate-400 mt-4"><span class="text-emerald-400 font-bold"><i class="fa-solid fa-building mr-1"></i><span x-text="stats.departmentsCount">{{ $departmentsCount }}</span></span> Departments Active</p>
        </div>
        @endfeature

        @feature('attendance')
        <!-- Card 2: Today's Attendance -->
        <div class="bg-slate-950/60 border border-slate-800 border-t-4 border-t-emerald-500 rounded-2xl p-5 relative overflow-hidden group hover:border-slate-700 transition-all">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Today's Presence</p>
                    <h3 class="text-3xl font-extrabold text-white mt-1" x-text="stats.todayAttendance">{{ $todayAttendance }}</h3>
                </div>
                <div class="w-12 h-12 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 flex items-center justify-center text-xl font-bold shadow-xs">
                    <i class="fa-solid fa-user-check"></i>
                </div>
            </div>
            <p class="text-xs font-medium text-slate-400 mt-4"><span class="text-emerald-400 font-bold" x-text="stats.presentToday">{{ $presentToday }}</span> On-time / Present</p>
        </div>
        @endfeature

        @feature('leaves')
        <!-- Card 3: Pending Leaves -->
        <div class="bg-slate-950/60 border border-slate-800 border-t-4 border-t-amber-500 rounded-2xl p-5 relative overflow-hidden group hover:border-slate-700 transition-all">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Pending Leaves</p>
                    <h3 class="text-3xl font-extrabold text-white mt-1" x-text="stats.pendingLeavesCount">{{ $pendingLeavesCount }}</h3>
                </div>
                <div class="w-12 h-12 rounded-xl bg-amber-500/10 border border-amber-500/20 text-amber-400 flex items-center justify-center text-xl font-bold shadow-xs">
                    <i class="fa-solid fa-envelope-open-text"></i>
                </div>
            </div>
            <p class="text-xs font-medium text-slate-400 mt-4">
                <a href="{{ route('leaves.index') }}" class="text-indigo-400 hover:underline">Requires Management Approval</a>
            </p>
        </div>
        @endfeature

    </div>

    <!-- Main Section: Recent Hires & Departments -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        @feature('employees')
        <!-- Recent Employees (2 cols) -->
        <div class="lg:col-span-2 bg-slate-950/60 border border-slate-800 rounded-3xl p-6 space-y-4 shadow-xl">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-2.5">
                    <span class="w-2.5 h-2.5 rounded-full bg-indigo-500 shadow-sm shadow-indigo-500/50"></span>
                    <h3 class="text-base font-bold text-white tracking-tight">Recent Hires & Team Members</h3>
                </div>
                @admin
                <a href="{{ route('employees.index') }}" class="text-xs font-semibold text-indigo-400 hover:underline">View All <i class="fa-solid fa-arrow-right text-[10px] ml-1"></i></a>
                @endadmin
            </div>

            @php
                $avatarGradients = [
                    'from-indigo-600 to-purple-600 ring-indigo-400/40 text-white',
                    'from-emerald-600 to-teal-600 ring-emerald-400/40 text-white',
                    'from-amber-600 to-rose-600 ring-amber-400/40 text-white',
                    'from-blue-600 to-cyan-600 ring-blue-400/40 text-white',
                    'from-purple-600 to-pink-600 ring-purple-400/40 text-white',
                    'from-rose-600 to-red-600 ring-rose-400/40 text-white',
                    'from-teal-600 to-emerald-600 ring-teal-400/40 text-white',
                ];
            @endphp

            <div class="divide-y divide-slate-800/80">
                @foreach($recentEmployees as $idx => $emp)
                    @php $grad = $avatarGradients[$idx % count($avatarGradients)]; @endphp
                    <div class="py-3 px-2 rounded-xl flex items-center justify-between first:pt-2 last:pb-2 hover:bg-slate-900/40 transition-all group/item">
                        <div class="flex items-center space-x-3.5">
                            <div class="w-10 h-10 rounded-xl bg-gradient-to-tr {{ $grad }} ring-2 flex items-center justify-center font-bold text-sm shadow-md group-hover/item:scale-105 transition-transform shrink-0">
                                {{ strtoupper(substr($emp->name, 0, 1)) }}
                            </div>
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-white group-hover/item:text-indigo-300 transition-colors truncate">{{ $emp->name }}</p>
                                <p class="text-xs text-slate-400 font-medium truncate">{{ $emp->designation->title ?? 'Team Member' }} &bull; <span class="text-indigo-400 font-semibold">{{ $emp->department->name ?? 'General' }}</span></p>
                            </div>
                        </div>
                        <span class="px-2.5 py-1 rounded-full text-[11px] font-semibold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 shrink-0">Active</span>
                    </div>
                @endforeach
            </div>
        </div>
        @endfeature

        @feature('settings')
        @admin
        <!-- Department Distribution (1 col) -->
        <div class="bg-slate-950/60 border border-slate-800 rounded-3xl p-6 space-y-4 shadow-xl {{ !feature_enabled('employees') ? 'lg:col-span-3' : '' }}">
            <div class="flex items-center space-x-2.5">
                <span class="w-2.5 h-2.5 rounded-full bg-purple-500 shadow-sm shadow-purple-500/50"></span>
                <h3 class="text-base font-bold text-white tracking-tight">Department Breakdown</h3>
            </div>

            @php
                $deptColors = [
                    ['bar' => 'from-indigo-500 to-blue-500', 'pill' => 'text-indigo-400 bg-indigo-500/10 border-indigo-500/20', 'dot' => 'bg-indigo-500'],
                    ['bar' => 'from-purple-500 to-indigo-500', 'pill' => 'text-purple-400 bg-purple-500/10 border-purple-500/20', 'dot' => 'bg-purple-500'],
                    ['bar' => 'from-emerald-500 to-teal-500', 'pill' => 'text-emerald-400 bg-emerald-500/10 border-emerald-500/20', 'dot' => 'bg-emerald-500'],
                    ['bar' => 'from-amber-500 to-orange-500', 'pill' => 'text-amber-400 bg-amber-500/10 border-amber-500/20', 'dot' => 'bg-amber-500'],
                    ['bar' => 'from-rose-500 to-pink-500', 'pill' => 'text-rose-400 bg-rose-500/10 border-rose-500/20', 'dot' => 'bg-rose-500'],
                    ['bar' => 'from-cyan-500 to-blue-500', 'pill' => 'text-cyan-400 bg-cyan-500/10 border-cyan-500/20', 'dot' => 'bg-cyan-500'],
                    ['bar' => 'from-fuchsia-500 to-violet-500', 'pill' => 'text-fuchsia-400 bg-fuchsia-500/10 border-fuchsia-500/20', 'dot' => 'bg-fuchsia-500'],
                    ['bar' => 'from-teal-500 to-emerald-500', 'pill' => 'text-teal-400 bg-teal-500/10 border-teal-500/20', 'dot' => 'bg-teal-500'],
                ];
            @endphp

            <div class="space-y-3.5">
                @foreach($departments as $idx => $dept)
                    @php $c = $deptColors[$idx % count($deptColors)]; @endphp
                    <div class="group/dept">
                        <div class="flex justify-between items-center text-xs font-medium mb-1.5">
                            <div class="flex items-center space-x-2">
                                <span class="w-2 h-2 rounded-full {{ $c['dot'] }}"></span>
                                <span class="text-slate-300 font-semibold group-hover/dept:text-indigo-300 transition-colors">{{ $dept->name }}</span>
                            </div>
                            <span class="px-2 py-0.5 rounded-full text-[11px] font-bold border {{ $c['pill'] }}">{{ $dept->users_count }} Staff</span>
                        </div>
                        <div class="w-full bg-slate-900 rounded-full h-2.5 overflow-hidden border border-slate-800">
                            <div class="bg-gradient-to-r {{ $c['bar'] }} h-2.5 rounded-full transition-all duration-500 shadow-sm" style="width: {{ $totalEmployees > 0 ? min(100, max(12, ($dept->users_count / $totalEmployees) * 100)) : 0 }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        @endadmin
        @endfeature

    </div>
</div>
@endemployee

</div>

<script>
    function dashboardHub() {
        return {
            stats: {
                totalEmployees: {{ $totalEmployees ?? 0 }},
                departmentsCount: {{ $departmentsCount ?? 0 }},
                todayAttendance: {{ $todayAttendance ?? 0 }},
                presentToday: {{ $presentToday ?? 0 }},
                pendingLeavesCount: {{ $pendingLeavesCount ?? 0 }},
                myPendingTasksCount: {{ count($myPendingTasks ?? []) }},
                myCompletedTasksCount: {{ $myCompletedTasksCount ?? 0 }},
                myPendingLeaves: {{ $myPendingLeaves ?? 0 }}
            },
            async fetchLiveDashboard() {
                try {
                    const res = await fetch('{{ route("live.dashboard") }}', {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });
                    if (res.ok) {
                        const data = await res.json();
                        this.stats.totalEmployees = data.total_employees;
                        this.stats.departmentsCount = data.departments_count;
                        this.stats.todayAttendance = data.today_attendance;
                        this.stats.presentToday = data.present_today;
                        this.stats.pendingLeavesCount = data.pending_leaves_count;
                        this.stats.myPendingTasksCount = data.my_pending_tasks_count;
                        this.stats.myCompletedTasksCount = data.my_completed_tasks_count;
                        this.stats.myPendingLeaves = data.my_pending_leaves;
                    }
                } catch(e) {
                    console.error('Failed to sync live dashboard', e);
                }
            },
            initDashboard() {
                window.addEventListener('hrms:dashboard-sync', () => {
                    this.fetchLiveDashboard();
                });
                // Background periodic poll every 8 seconds if active
                setInterval(() => {
                    if (!document.hidden) {
                        this.fetchLiveDashboard();
                    }
                }, 8000);
            }
        };
    }
</script>

@endsection
