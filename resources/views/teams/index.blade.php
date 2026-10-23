@extends('layouts.app')

@section('title', $viewMode === 'admin' ? 'Teams & Squads Management' : 'My Team')

@section('content')
<div x-data="teamsManager()" class="space-y-6">

    <!-- Page Header & Action Controls -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center space-x-2">
                <span class="px-2.5 py-1 rounded-lg bg-indigo-500/10 border border-indigo-500/20 text-indigo-400 text-[10px] font-extrabold uppercase tracking-wider">
                    Workforce Squads
                </span>
                <span class="text-xs text-slate-500">•</span>
                <span class="text-xs font-semibold text-slate-400">Team Leadership & Attendance Hub</span>
            </div>
            <h1 class="text-2xl font-extrabold text-white tracking-tight mt-1">
                @if($viewMode === 'admin')
                    Teams & Squads Directory
                @elseif($viewMode === 'leader')
                    Team Leader Command Center
                @else
                    My Squad Hub
                @endif
            </h1>
            <p class="text-xs font-medium text-slate-400 mt-1">
                @if($viewMode === 'admin')
                    Create teams, assign team leaders, manage squad members, and oversee team performance across companies.
                @elseif($viewMode === 'leader')
                    Real-time monitoring of your squad members, daily attendance, working hours, and leave approvals.
                @else
                    View your assigned team, fellow squad teammates, and reporting Team Leader.
                @endif
            </p>
        </div>

        <div class="flex items-center space-x-3">
            @if($canManageAll && $isLeader)
                <!-- View Mode Switcher for Admins who are also Team Leaders -->
                <div class="flex items-center bg-slate-950 p-1 rounded-xl border border-slate-800 text-xs font-bold">
                    <a href="{{ route('teams.index', ['view' => 'admin']) }}" class="px-3 py-1.5 rounded-lg transition-all {{ $viewMode === 'admin' ? 'bg-indigo-600 text-white shadow-md' : 'text-slate-400 hover:text-white' }}">
                        All Teams
                    </a>
                    <a href="{{ route('teams.index', ['view' => 'leader']) }}" class="px-3 py-1.5 rounded-lg transition-all {{ $viewMode === 'leader' ? 'bg-indigo-600 text-white shadow-md' : 'text-slate-400 hover:text-white' }}">
                        My Led Team
                    </a>
                </div>
            @endif

            @if($canManageAll)
                <button @click="createModalOpen = true" class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs rounded-xl shadow-lg shadow-indigo-600/30 transition-all flex items-center space-x-2">
                    <i class="fa-solid fa-plus text-xs"></i>
                    <span>Create New Team</span>
                </button>
            @endif
        </div>
    </div>

    <!-- ========================================================= -->
    <!-- 1. ADMIN & HR MANAGEMENT VIEW                              -->
    <!-- ========================================================= -->
    @if($viewMode === 'admin')
        <!-- Key Metrics Row -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="p-5 rounded-2xl bg-slate-950 border border-slate-800 shadow-xl">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-slate-400">Total Teams</span>
                    <div class="w-8 h-8 rounded-xl bg-indigo-500/10 text-indigo-400 flex items-center justify-center">
                        <i class="fa-solid fa-people-group text-xs"></i>
                    </div>
                </div>
                <p class="text-2xl font-black text-white mt-2">{{ $totalTeams }}</p>
                <p class="text-[11px] text-slate-500 mt-0.5">Active functional squads</p>
            </div>

            <div class="p-5 rounded-2xl bg-slate-950 border border-slate-800 shadow-xl">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-slate-400">Squads with Leaders</span>
                    <div class="w-8 h-8 rounded-xl bg-amber-500/10 text-amber-400 flex items-center justify-center">
                        <i class="fa-solid fa-crown text-xs"></i>
                    </div>
                </div>
                <p class="text-2xl font-black text-white mt-2">{{ $teamsWithLeader }} <span class="text-xs font-semibold text-slate-500">/ {{ $totalTeams }}</span></p>
                <p class="text-[11px] text-slate-500 mt-0.5">{{ $totalTeams - $teamsWithLeader }} squad(s) need leadership</p>
            </div>

            <div class="p-5 rounded-2xl bg-slate-950 border border-slate-800 shadow-xl">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-slate-400">Assigned Members</span>
                    <div class="w-8 h-8 rounded-xl bg-emerald-500/10 text-emerald-400 flex items-center justify-center">
                        <i class="fa-solid fa-users text-xs"></i>
                    </div>
                </div>
                <p class="text-2xl font-black text-white mt-2">{{ $totalAssignedMembers }}</p>
                <p class="text-[11px] text-slate-500 mt-0.5">Employees allocated to squads</p>
            </div>

            <div class="p-5 rounded-2xl bg-slate-950 border border-slate-800 shadow-xl">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-slate-400">Unassigned Workforce</span>
                    <div class="w-8 h-8 rounded-xl bg-purple-500/10 text-purple-400 flex items-center justify-center">
                        <i class="fa-solid fa-user-clock text-xs"></i>
                    </div>
                </div>
                <p class="text-2xl font-black text-white mt-2">{{ $unassignedMembersCount }}</p>
                <p class="text-[11px] text-slate-500 mt-0.5">Available for team assignment</p>
            </div>
        </div>

        <!-- Search & Filter Controls -->
        <div class="bg-slate-950/60 border border-slate-800 rounded-2xl p-4 flex flex-col md:flex-row items-center justify-between gap-4 shadow-xl">
            <form action="{{ route('teams.index') }}" method="GET" class="w-full flex flex-col md:flex-row items-center gap-3">
                <input type="hidden" name="view" value="admin">
                <div class="relative w-full md:w-72">
                    <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-3 text-slate-500 text-xs"></i>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search squad or leader..." class="w-full pl-9 pr-4 py-2 bg-slate-900 border border-slate-800 rounded-xl text-xs font-medium text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500">
                </div>

                <select name="organization_id" class="w-full md:w-48 px-3 py-2 bg-slate-900 border border-slate-800 rounded-xl text-xs font-medium text-slate-300 focus:outline-none focus:border-indigo-500">
                    <option value="">All Companies</option>
                    @foreach($organizations as $org)
                        <option value="{{ $org->id }}" {{ request('organization_id') == $org->id ? 'selected' : '' }}>{{ $org->name }}</option>
                    @endforeach
                </select>

                <select name="department_id" class="w-full md:w-48 px-3 py-2 bg-slate-900 border border-slate-800 rounded-xl text-xs font-medium text-slate-300 focus:outline-none focus:border-indigo-500">
                    <option value="">All Departments</option>
                    @foreach($departments as $dept)
                        <option value="{{ $dept->id }}" {{ request('department_id') == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                    @endforeach
                </select>

                <button type="submit" class="w-full md:w-auto px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-200 font-bold text-xs rounded-xl transition-all">
                    Filter
                </button>
                @if(request('search') || request('organization_id') || request('department_id'))
                    <a href="{{ route('teams.index', ['view' => 'admin']) }}" class="text-xs font-semibold text-rose-400 hover:underline">Clear</a>
                @endif
            </form>
        </div>

        <!-- Teams Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            @forelse($teams as $team)
                <div class="bg-slate-950 border border-slate-800/80 hover:border-slate-700 rounded-3xl p-6 shadow-xl space-y-5 transition-all flex flex-col justify-between">
                    <div class="space-y-4">
                        <!-- Card Header -->
                        <div class="flex items-start justify-between gap-3">
                            <div class="space-y-1 min-w-0">
                                <div class="flex items-center space-x-2">
                                    <span class="px-2 py-0.5 rounded-lg bg-indigo-500/10 border border-indigo-500/20 text-indigo-400 text-[10px] font-bold">
                                        {{ $team->department->name ?? 'Dept' }}
                                    </span>
                                    <span class="text-[10px] text-slate-500 truncate">{{ $team->organization->name ?? 'Org' }}</span>
                                </div>
                                <h3 class="text-lg font-black text-white tracking-tight truncate">{{ $team->name }}</h3>
                            </div>
                            <span class="px-2.5 py-1 rounded-xl bg-slate-900 border border-slate-800 text-slate-300 text-xs font-black shrink-0">
                                {{ $team->members->count() }} {{ Str::plural('member', $team->members->count()) }}
                            </span>
                        </div>

                        @if($team->description)
                            <p class="text-xs text-slate-400 line-clamp-2">{{ $team->description }}</p>
                        @endif

                        <!-- Team Leader Section -->
                        <div class="p-3.5 rounded-2xl bg-slate-900/60 border border-slate-800/80">
                            @if($team->leader)
                                <div class="flex items-center space-x-3">
                                    @if($team->leader->avatar_url)
                                        <img src="{{ $team->leader->avatar_url }}" alt="{{ $team->leader->name }}" class="w-10 h-10 rounded-xl object-cover ring-2 ring-amber-500/40 shrink-0">
                                    @else
                                        <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-amber-600 to-orange-600 flex items-center justify-center font-bold text-white text-xs shadow-md shrink-0">
                                            {{ $team->leader->initials }}
                                        </div>
                                    @endif
                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-center space-x-1.5">
                                            <p class="text-xs font-bold text-white truncate">{{ $team->leader->name }}</p>
                                            <span class="px-1.5 py-0.2 rounded bg-amber-500 text-slate-950 text-[9px] font-extrabold uppercase">Lead</span>
                                        </div>
                                        <p class="text-[11px] text-slate-400 truncate">{{ $team->leader->email }}</p>
                                    </div>
                                </div>
                            @else
                                <div class="flex items-center justify-between text-xs">
                                    <span class="text-amber-400 font-semibold flex items-center space-x-1.5">
                                        <i class="fa-solid fa-triangle-exclamation text-[11px]"></i>
                                        <span>No Leader Assigned</span>
                                    </span>
                                    <button @click="openEditModal('{{ $team->id }}')" class="text-[11px] font-bold text-indigo-400 hover:underline">
                                        Assign Lead
                                    </button>
                                </div>
                            @endif
                        </div>

                        <!-- Squad Members Avatar Stack -->
                        <div>
                            <div class="flex items-center justify-between text-[11px] font-semibold text-slate-400 mb-2">
                                <span>Squad Roster</span>
                                <button @click="openManageMembersModal('{{ $team->id }}')" class="text-indigo-400 hover:underline">
                                    Manage Members
                                </button>
                            </div>
                            <div class="flex items-center -space-x-2 overflow-hidden py-1">
                                @forelse($team->members->take(6) as $m)
                                    @if($m->avatar_url)
                                        <img src="{{ $m->avatar_url }}" alt="{{ $m->name }}" title="{{ $m->name }}" class="w-8 h-8 rounded-xl object-cover ring-2 ring-slate-950">
                                    @else
                                        <div class="w-8 h-8 rounded-xl bg-slate-800 text-slate-300 ring-2 ring-slate-950 flex items-center justify-center text-[10px] font-bold" title="{{ $m->name }}">
                                            {{ $m->initials }}
                                        </div>
                                    @endif
                                @empty
                                    <span class="text-xs text-slate-500 italic pl-1">No employees assigned yet.</span>
                                @endforelse

                                @if($team->members->count() > 6)
                                    <div class="w-8 h-8 rounded-xl bg-slate-800 border border-slate-700 text-indigo-300 ring-2 ring-slate-950 flex items-center justify-center text-[10px] font-extrabold">
                                        +{{ $team->members->count() - 6 }}
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Card Actions -->
                    <div class="pt-4 border-t border-slate-800/80 flex items-center justify-between gap-2">
                        <button @click="openManageMembersModal('{{ $team->id }}')" class="px-3 py-2 bg-slate-900 hover:bg-slate-800 text-slate-300 font-bold text-xs rounded-xl transition-all flex items-center space-x-1.5 flex-1 justify-center">
                            <i class="fa-solid fa-user-plus text-[10px] text-indigo-400"></i>
                            <span>Members</span>
                        </button>

                        <button @click="openEditModal('{{ $team->id }}')" class="px-3 py-2 bg-slate-900 hover:bg-slate-800 text-slate-300 font-bold text-xs rounded-xl transition-all flex items-center space-x-1.5">
                            <i class="fa-solid fa-pen-to-square text-[10px]"></i>
                            <span>Edit</span>
                        </button>

                        <form action="{{ route('teams.destroy', $team->id) }}" method="POST" onsubmit="return confirm('Delete this team? Assigned employees will be unassigned but not deleted.');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="p-2 text-rose-400 hover:text-rose-300 hover:bg-rose-500/10 rounded-xl transition-colors" title="Delete Team">
                                <i class="fa-solid fa-trash-can text-xs"></i>
                            </button>
                        </form>
                    </div>
                </div>
            @empty
                <div class="col-span-full p-12 text-center bg-slate-950 border border-slate-800 rounded-3xl">
                    <i class="fa-solid fa-people-group text-4xl text-slate-600 mb-3 block"></i>
                    <h3 class="text-lg font-bold text-white">No Teams Found</h3>
                    <p class="text-xs text-slate-400 mt-1 max-w-sm mx-auto">Get started by creating your first organizational squad, assigning a Team Leader, and assembling members.</p>
                    <button @click="createModalOpen = true" class="mt-4 px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs rounded-xl shadow-lg transition-all">
                        Create Squad Now
                    </button>
                </div>
            @endforelse
        </div>

    <!-- ========================================================= -->
    <!-- 2. TEAM LEADER PORTAL VIEW                                 -->
    <!-- ========================================================= -->
    @elseif($viewMode === 'leader')
        @if($selectedTeam)
            <!-- Squad Header Card -->
            <div class="bg-gradient-to-r from-slate-950 via-slate-900 to-indigo-950/50 border border-slate-800 rounded-3xl p-6 lg:p-8 shadow-2xl relative overflow-hidden space-y-6">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div class="space-y-1">
                        <div class="flex items-center space-x-2">
                            <span class="px-2.5 py-0.5 rounded-lg bg-amber-500/20 border border-amber-500/30 text-amber-300 text-[10px] font-black uppercase">
                                Squad Leader Portal
                            </span>
                            <span class="text-xs text-slate-500">•</span>
                            <span class="text-xs font-semibold text-slate-400">{{ $selectedTeam->department->name ?? 'Department' }}</span>
                            <span class="text-xs text-slate-500">•</span>
                            <span class="text-xs font-semibold text-slate-400">{{ $selectedTeam->organization->name ?? 'Company' }}</span>
                        </div>
                        <h2 class="text-2xl font-black text-white tracking-tight">{{ $selectedTeam->name }}</h2>
                        @if($selectedTeam->description)
                            <p class="text-xs text-slate-300 max-w-2xl">{{ $selectedTeam->description }}</p>
                        @endif
                    </div>

                    @if($ledTeams->count() > 1)
                        <!-- Multi-Team Selector -->
                        <div class="flex items-center space-x-2">
                            <label class="text-xs text-slate-400 font-bold">Switch Squad:</label>
                            <select onchange="window.location.href='{{ route('teams.index') }}?view=leader&team_id=' + this.value" class="px-3 py-2 bg-slate-900 border border-slate-700 rounded-xl text-xs font-bold text-white">
                                @foreach($ledTeams as $lt)
                                    <option value="{{ $lt->id }}" {{ $selectedTeam->id === $lt->id ? 'selected' : '' }}>{{ $lt->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                </div>

                <!-- Leader Stats Grid -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 pt-2">
                    <div class="p-4 rounded-2xl bg-slate-950/70 border border-slate-800/80">
                        <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Total Members</span>
                        <span class="text-2xl font-black text-white mt-1 block">{{ $attendanceStats['total'] }}</span>
                    </div>

                    <div class="p-4 rounded-2xl bg-slate-950/70 border border-slate-800/80">
                        <span class="text-[11px] font-bold text-emerald-400 uppercase tracking-wider block">Present Today</span>
                        <span class="text-2xl font-black text-emerald-400 mt-1 block">{{ $attendanceStats['present'] }}</span>
                    </div>

                    <div class="p-4 rounded-2xl bg-slate-950/70 border border-slate-800/80">
                        <span class="text-[11px] font-bold text-amber-400 uppercase tracking-wider block">Late Arrivals</span>
                        <span class="text-2xl font-black text-amber-400 mt-1 block">{{ $attendanceStats['late'] }}</span>
                    </div>

                    <div class="p-4 rounded-2xl bg-slate-950/70 border border-slate-800/80">
                        <span class="text-[11px] font-bold text-rose-400 uppercase tracking-wider block">Absent / Leave</span>
                        <span class="text-2xl font-black text-rose-400 mt-1 block">{{ $attendanceStats['absent'] + $attendanceStats['on_leave'] }}</span>
                    </div>
                </div>
            </div>

            <!-- Segmented View Tabs (Attendance / Members / Leaves) -->
            <div class="space-y-4" x-data="{ leaderTab: 'attendance' }">
                <div class="flex items-center space-x-2 bg-slate-950 p-1.5 rounded-2xl border border-slate-800 text-xs font-bold overflow-x-auto">
                    <button type="button" @click="leaderTab = 'attendance'" :class="leaderTab === 'attendance' ? 'bg-indigo-600 text-white shadow-md' : 'text-slate-400 hover:text-white'" class="px-4 py-2.5 rounded-xl transition-all flex items-center space-x-2 whitespace-nowrap">
                        <i class="fa-solid fa-user-clock text-xs"></i>
                        <span>Live Team Attendance</span>
                    </button>
                    <button type="button" @click="leaderTab = 'members'" :class="leaderTab === 'members' ? 'bg-indigo-600 text-white shadow-md' : 'text-slate-400 hover:text-white'" class="px-4 py-2.5 rounded-xl transition-all flex items-center space-x-2 whitespace-nowrap">
                        <i class="fa-solid fa-users text-xs"></i>
                        <span>Squad Members ({{ $selectedTeam->members->count() }})</span>
                    </button>
                    <button type="button" @click="leaderTab = 'leaves'" :class="leaderTab === 'leaves' ? 'bg-indigo-600 text-white shadow-md' : 'text-slate-400 hover:text-white'" class="px-4 py-2.5 rounded-xl transition-all flex items-center space-x-2 whitespace-nowrap">
                        <i class="fa-solid fa-calendar-check text-xs"></i>
                        <span>Team Leave Requests ({{ $teamLeaveRequests->count() }})</span>
                    </button>
                </div>

                <!-- 1. LIVE TEAM ATTENDANCE TAB -->
                <div x-show="leaderTab === 'attendance'" class="space-y-4">
                    <!-- Attendance Date Filter Bar -->
                    <div class="bg-slate-950/60 border border-slate-800 rounded-2xl p-4 flex flex-col sm:flex-row items-center justify-between gap-4 shadow-xl">
                        <form action="{{ route('teams.index') }}" method="GET" class="w-full flex flex-col sm:flex-row items-center gap-3">
                            <input type="hidden" name="view" value="leader">
                            <input type="hidden" name="team_id" value="{{ $selectedTeam->id }}">

                            <div class="flex items-center space-x-2">
                                <label class="text-xs font-bold text-slate-300 whitespace-nowrap">Attendance Date:</label>
                                <input type="date" name="attendance_date" value="{{ $attendanceDate }}" class="px-3 py-2 bg-slate-900 border border-slate-800 rounded-xl text-xs font-medium text-white focus:outline-none focus:border-indigo-500">
                            </div>

                            <button type="submit" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-200 font-bold text-xs rounded-xl transition-all">
                                View Attendance
                            </button>

                            @if($attendanceDate !== $today)
                                <a href="{{ route('teams.index', ['view' => 'leader', 'team_id' => $selectedTeam->id]) }}" class="text-xs font-semibold text-indigo-400 hover:underline">Back to Today</a>
                            @endif
                        </form>

                        <span class="text-xs font-semibold text-slate-400 whitespace-nowrap">
                            Showing: <strong class="text-white">{{ date('l, d M Y', strtotime($attendanceDate)) }}</strong>
                        </span>
                    </div>

                    <!-- Attendance Table -->
                    <div class="bg-slate-950/60 border border-slate-800 rounded-3xl overflow-hidden shadow-2xl">
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="bg-slate-900/80 border-b border-slate-800 text-[11px] font-bold text-slate-400 uppercase tracking-wider">
                                        <th class="py-3.5 px-6">Member</th>
                                        <th class="py-3.5 px-6">Status</th>
                                        <th class="py-3.5 px-6">Clock In</th>
                                        <th class="py-3.5 px-6">Clock Out</th>
                                        <th class="py-3.5 px-6">Working Hours</th>
                                        <th class="py-3.5 px-6">Breaks</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-800/80 text-xs">
                                    @forelse($teamAttendance as $row)
                                        <tr class="hover:bg-slate-900/40 transition-colors">
                                            <td class="py-4 px-6">
                                                <div class="flex items-center space-x-3">
                                                    @if($row->member->avatar_url)
                                                        <img src="{{ $row->member->avatar_url }}" alt="{{ $row->member->name }}" class="w-9 h-9 rounded-xl object-cover ring-2 ring-indigo-500/30 shrink-0">
                                                    @else
                                                        <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-indigo-600 to-purple-600 flex items-center justify-center font-bold text-white text-xs shadow-md shrink-0">
                                                            {{ $row->member->initials }}
                                                        </div>
                                                    @endif
                                                    <div>
                                                        <p class="font-bold text-white text-sm">{{ $row->member->name }}</p>
                                                        <p class="text-slate-400 text-xs">{{ $row->member->designation->title ?? 'Squad Member' }}</p>
                                                    </div>
                                                </div>
                                            </td>

                                            <td class="py-4 px-6">
                                                @if($row->status === 'PRESENT')
                                                    <span class="px-2.5 py-1 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 font-extrabold text-[11px]">
                                                        PRESENT
                                                    </span>
                                                @elseif($row->status === 'LATE')
                                                    <span class="px-2.5 py-1 rounded-xl bg-amber-500/10 border border-amber-500/20 text-amber-400 font-extrabold text-[11px]">
                                                        LATE ARRIVAL
                                                    </span>
                                                @elseif($row->status === 'HALF_DAY')
                                                    <span class="px-2.5 py-1 rounded-xl bg-orange-500/10 border border-orange-500/20 text-orange-400 font-extrabold text-[11px]">
                                                        HALF DAY
                                                    </span>
                                                @else
                                                    <span class="px-2.5 py-1 rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-400 font-extrabold text-[11px]">
                                                        ABSENT
                                                    </span>
                                                @endif

                                                @if($row->is_on_break)
                                                    <span class="ml-1.5 px-2 py-0.5 rounded-full bg-purple-500/20 text-purple-300 text-[10px] font-bold border border-purple-500/30 animate-pulse">
                                                        On Break
                                                    </span>
                                                @endif
                                            </td>

                                            <td class="py-4 px-6 text-slate-300 font-medium">
                                                {{ $row->clock_in ? date('h:i A', strtotime($row->clock_in)) : '—' }}
                                            </td>

                                            <td class="py-4 px-6 text-slate-300 font-medium">
                                                {{ $row->clock_out ? date('h:i A', strtotime($row->clock_out)) : ($row->clock_in ? 'In Progress' : '—') }}
                                            </td>

                                            <td class="py-4 px-6 font-bold text-white">
                                                {{ $row->total_hours > 0 ? number_format($row->total_hours, 1) . ' hrs' : '—' }}
                                            </td>

                                            <td class="py-4 px-6 text-slate-400 font-medium">
                                                {{ $row->break_minutes > 0 ? $row->break_minutes . ' mins' : 'None' }}
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="py-8 text-center text-slate-500 text-xs">
                                                No squad members found in this team.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- 2. SQUAD MEMBERS TAB -->
                <div x-show="leaderTab === 'members'" class="space-y-4">
                    <div class="flex items-center justify-between">
                        <h3 class="text-base font-bold text-white tracking-tight">Active Squad Members</h3>
                        <button @click="openManageMembersModal('{{ $selectedTeam->id }}')" class="px-3.5 py-2 bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs rounded-xl shadow-md transition-all flex items-center space-x-1.5">
                            <i class="fa-solid fa-user-plus text-xs"></i>
                            <span>Add Teammates</span>
                        </button>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        @forelse($selectedTeam->members as $member)
                            <div class="bg-slate-950 border border-slate-800 rounded-2xl p-5 space-y-4 shadow-xl flex flex-col justify-between">
                                <div class="flex items-start space-x-3.5">
                                    @if($member->avatar_url)
                                        <img src="{{ $member->avatar_url }}" alt="{{ $member->name }}" class="w-11 h-11 rounded-xl object-cover ring-2 ring-indigo-500/30 shrink-0">
                                    @else
                                        <div class="w-11 h-11 rounded-xl bg-gradient-to-tr from-indigo-600 to-purple-600 flex items-center justify-center font-bold text-white text-xs shadow-md shrink-0">
                                            {{ $member->initials }}
                                        </div>
                                    @endif
                                    <div class="min-w-0 flex-1">
                                        <p class="font-bold text-white text-sm truncate">{{ $member->name }}</p>
                                        <p class="text-xs text-indigo-400 font-semibold truncate">{{ $member->designation->title ?? 'Team Member' }}</p>
                                        <p class="text-[11px] text-slate-400 truncate mt-0.5">{{ $member->email }}</p>
                                        @if($member->phone)
                                            <p class="text-[11px] text-slate-400 truncate mt-0.5">
                                                <i class="fa-solid fa-phone text-[9px] mr-1"></i>{{ $member->phone }}
                                            </p>
                                        @endif
                                    </div>
                                </div>

                                <div class="pt-3 border-t border-slate-900 flex items-center justify-between text-[11px]">
                                    <span class="text-slate-500 font-medium">Joined: {{ $member->date_of_joining ? (is_string($member->date_of_joining) ? date('d M, Y', strtotime($member->date_of_joining)) : $member->date_of_joining->format('d M, Y')) : 'N/A' }}</span>
                                    <form action="{{ route('teams.members.remove', ['id' => $selectedTeam->id, 'userId' => $member->id]) }}" method="POST" onsubmit="return confirm('Remove {{ $member->name }} from this squad?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-rose-400 hover:text-rose-300 font-bold flex items-center space-x-1">
                                            <i class="fa-solid fa-user-minus text-[10px]"></i>
                                            <span>Remove</span>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @empty
                            <div class="col-span-full p-8 text-center bg-slate-950 border border-slate-800 rounded-2xl text-slate-500 text-xs">
                                No members assigned to this squad yet. Click "Add Teammates" to assign employees.
                            </div>
                        @endforelse
                    </div>
                </div>

                <!-- 3. SQUAD LEAVES TAB -->
                <div x-show="leaderTab === 'leaves'" class="space-y-4">
                    <div class="bg-slate-950/60 border border-slate-800 rounded-3xl overflow-hidden shadow-2xl">
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="bg-slate-900/80 border-b border-slate-800 text-[11px] font-bold text-slate-400 uppercase tracking-wider">
                                        <th class="py-3.5 px-6">Member</th>
                                        <th class="py-3.5 px-6">Leave Type</th>
                                        <th class="py-3.5 px-6">Period</th>
                                        <th class="py-3.5 px-6">Days</th>
                                        <th class="py-3.5 px-6">Reason</th>
                                        <th class="py-3.5 px-6">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-800/80 text-xs">
                                    @forelse($teamLeaveRequests as $leave)
                                        <tr class="hover:bg-slate-900/40 transition-colors">
                                            <td class="py-4 px-6 font-bold text-white">
                                                {{ $leave->user->name ?? 'Member' }}
                                            </td>
                                            <td class="py-4 px-6 text-indigo-400 font-bold">
                                                {{ $leave->leaveType->name ?? 'Leave' }}
                                            </td>
                                            <td class="py-4 px-6 text-slate-300">
                                                {{ date('d M', strtotime($leave->start_date)) }} - {{ date('d M Y', strtotime($leave->end_date)) }}
                                            </td>
                                            <td class="py-4 px-6 font-extrabold text-white">
                                                {{ $leave->total_days }}
                                            </td>
                                            <td class="py-4 px-6 text-slate-400 max-w-xs truncate">
                                                {{ $leave->reason ?: 'No reason specified' }}
                                            </td>
                                            <td class="py-4 px-6">
                                                <span class="px-2.5 py-1 rounded-xl text-[10px] font-extrabold {{ $leave->status === 'APPROVED' ? 'bg-emerald-500/10 text-emerald-400' : ($leave->status === 'PENDING' ? 'bg-amber-500/10 text-amber-400' : 'bg-rose-500/10 text-rose-400') }}">
                                                    {{ $leave->status }}
                                                </span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="py-8 text-center text-slate-500 text-xs">
                                                No recent leave requests from this squad.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        @else
            <div class="p-12 text-center bg-slate-950 border border-slate-800 rounded-3xl">
                <i class="fa-solid fa-user-shield text-4xl text-amber-400 mb-3 block"></i>
                <h3 class="text-lg font-bold text-white">No Team Assigned to You as Leader</h3>
                <p class="text-xs text-slate-400 mt-1 max-w-md mx-auto">You currently do not have a squad assigned to lead. Please contact the System Admin or Hr Manager to assign you as the leader of a squad.</p>
            </div>
        @endif

    <!-- ========================================================= -->
    <!-- 3. REGULAR EMPLOYEE VIEW                                   -->
    <!-- ========================================================= -->
    @else
        @if($myTeam)
            <!-- Employee's Squad Details -->
            <div class="space-y-6">
                <div class="bg-gradient-to-r from-slate-950 via-slate-900 to-indigo-950/40 border border-slate-800 rounded-3xl p-6 lg:p-8 shadow-xl space-y-4">
                    <div class="flex items-center space-x-2">
                        <span class="text-[10px] font-extrabold uppercase px-2.5 py-0.5 rounded-full bg-amber-500/20 text-amber-300 border border-amber-500/30">
                            My Squad
                        </span>
                        <span class="text-xs text-slate-500">•</span>
                        <span class="text-xs font-semibold text-slate-400">{{ $myTeam->department->name ?? 'Department' }}</span>
                        <span class="text-xs text-slate-500">•</span>
                        <span class="text-xs font-semibold text-slate-400">{{ $myTeam->organization->name ?? 'Company' }}</span>
                    </div>
                    <h2 class="text-2xl font-black text-white tracking-tight">{{ $myTeam->name }}</h2>
                    @if($myTeam->description)
                        <p class="text-xs text-slate-300 max-w-2xl">{{ $myTeam->description }}</p>
                    @endif
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
                    <!-- Team Leader Spotlight Card -->
                    <div class="bg-slate-950 border border-slate-800 rounded-3xl p-6 shadow-xl space-y-5">
                        <div class="border-b border-slate-800/80 pb-4 flex items-center justify-between">
                            <h3 class="text-sm font-bold text-white flex items-center space-x-2">
                                <i class="fa-solid fa-crown text-amber-400"></i>
                                <span>My Team Leader</span>
                            </h3>
                            <span class="px-2 py-0.5 rounded-full bg-amber-500/10 text-amber-400 text-[10px] font-extrabold uppercase">Lead</span>
                        </div>

                        @if($myTeam->leader)
                            <div class="space-y-4">
                                <div class="flex items-center space-x-3.5">
                                    @if($myTeam->leader->avatar_url)
                                        <img src="{{ $myTeam->leader->avatar_url }}" alt="{{ $myTeam->leader->name }}" class="w-14 h-14 rounded-2xl object-cover ring-2 ring-amber-500/40 shrink-0 shadow-lg">
                                    @else
                                        <div class="w-14 h-14 rounded-2xl bg-gradient-to-tr from-amber-600 to-orange-600 flex items-center justify-center font-bold text-white text-base shadow-lg shrink-0">
                                            {{ $myTeam->leader->initials }}
                                        </div>
                                    @endif
                                    <div>
                                        <h4 class="text-base font-bold text-white">{{ $myTeam->leader->name }}</h4>
                                        <p class="text-xs text-amber-400 font-semibold">{{ $myTeam->leader->designation->title ?? 'Squad Leader' }}</p>
                                    </div>
                                </div>

                                <div class="space-y-2 pt-2 border-t border-slate-900 text-xs">
                                    <div class="flex items-center space-x-2.5 text-slate-300">
                                        <i class="fa-solid fa-envelope text-slate-500 w-4"></i>
                                        <span class="truncate">{{ $myTeam->leader->email }}</span>
                                    </div>
                                    @if($myTeam->leader->phone)
                                        <div class="flex items-center space-x-2.5 text-slate-300">
                                            <i class="fa-solid fa-phone text-slate-500 w-4"></i>
                                            <span>{{ $myTeam->leader->phone }}</span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @else
                            <div class="p-6 text-center text-slate-500 text-xs italic">
                                No leader currently assigned to your squad.
                            </div>
                        @endif
                    </div>

                    <!-- Squad Teammates -->
                    <div class="lg:col-span-3 bg-slate-950 border border-slate-800 rounded-3xl p-6 shadow-xl space-y-4">
                        <div class="border-b border-slate-800/80 pb-4 flex items-center justify-between">
                            <h3 class="text-sm font-bold text-white flex items-center space-x-2">
                                <i class="fa-solid fa-users text-indigo-400"></i>
                                <span>Fellow Teammates ({{ $myTeam->members->count() }})</span>
                            </h3>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                            @forelse($myTeam->members as $mate)
                                <div class="p-3.5 rounded-2xl bg-slate-900/60 border border-slate-800/80 flex items-center space-x-3">
                                    @if($mate->avatar_url)
                                        <img src="{{ $mate->avatar_url }}" alt="{{ $mate->name }}" class="w-10 h-10 rounded-xl object-cover ring-2 ring-indigo-500/30 shrink-0">
                                    @else
                                        <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-indigo-600 to-purple-600 flex items-center justify-center font-bold text-white text-xs shadow-md shrink-0">
                                            {{ $mate->initials }}
                                        </div>
                                    @endif
                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-center space-x-1.5">
                                            <p class="font-bold text-white text-xs truncate">{{ $mate->name }}</p>
                                            @if($mate->id === Auth::id())
                                                <span class="px-1.5 py-0.2 rounded bg-indigo-500/20 text-indigo-300 text-[9px] font-extrabold">You</span>
                                            @endif
                                        </div>
                                        <p class="text-[11px] text-slate-400 truncate">{{ $mate->designation->title ?? 'Team Member' }}</p>
                                    </div>
                                </div>
                            @empty
                                <div class="col-span-full text-slate-500 text-xs italic py-4">
                                    No other members in this team.
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        @else
            <div class="p-12 text-center bg-slate-950 border border-slate-800 rounded-3xl">
                <i class="fa-solid fa-people-group text-4xl text-slate-600 mb-3 block"></i>
                <h3 class="text-lg font-bold text-white">No Squad Assigned</h3>
                <p class="text-xs text-slate-400 mt-1 max-w-sm mx-auto">You have not been assigned to a team yet. Please contact your HR Manager or Administrator to be added to your departmental squad.</p>
            </div>
        @endif
    @endif

    <!-- ========================================================= -->
    <!-- MODAL 1: CREATE NEW TEAM                                  -->
    <!-- ========================================================= -->
    @if($canManageAll)
    <div x-show="createModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-md overflow-y-auto">
        <div @click.away="createModalOpen = false" class="bg-slate-900 border border-slate-800 rounded-3xl max-w-xl w-full p-6 md:p-8 space-y-6 shadow-2xl max-h-[92vh] overflow-y-auto">
            <div class="flex items-center justify-between border-b border-slate-800 pb-4">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-2xl bg-indigo-600/20 border border-indigo-500/30 flex items-center justify-center text-indigo-400">
                        <i class="fa-solid fa-people-group text-base"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-black text-white tracking-tight">Create New Team / Squad</h3>
                        <p class="text-xs text-slate-400">Establish a new squad and assign its Team Leader</p>
                    </div>
                </div>
                <button @click="createModalOpen = false" class="text-slate-400 hover:text-white p-2 hover:bg-slate-800 rounded-xl transition-colors">
                    <i class="fa-solid fa-xmark text-sm"></i>
                </button>
            </div>

            <form action="{{ route('teams.store') }}" method="POST" class="space-y-4 text-xs">
                @csrf
                <div class="space-y-1.5">
                    <label class="block font-semibold text-slate-300">Team / Squad Name *</label>
                    <input type="text" name="name" required placeholder="e.g. Frontend Development, Mobile Dev, UI/UX Squad" class="w-full px-4 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-white focus:outline-none focus:border-indigo-500">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="space-y-1.5">
                        <label class="block font-semibold text-slate-300">Company / Organization *</label>
                        <select name="organization_id" required class="w-full px-3 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-white">
                            @foreach($organizations as $org)
                                <option value="{{ $org->id }}">{{ $org->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="space-y-1.5">
                        <label class="block font-semibold text-slate-300">Department *</label>
                        <select name="department_id" required class="w-full px-3 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-white">
                            @foreach($departments as $dept)
                                <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="space-y-1.5">
                    <label class="block font-semibold text-slate-300">Assign Team Leader</label>
                    <select name="leader_id" class="w-full px-3 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-white">
                        <option value="">-- No Leader Assigned (Assign Later) --</option>
                        @foreach($eligibleLeaders as $leader)
                            <option value="{{ $leader->id }}">
                                {{ $leader->name }} ({{ $leader->role->label ?? 'Role' }} - {{ $leader->designation->title ?? 'Member' }})
                            </option>
                        @endforeach
                    </select>
                    <p class="text-[10px] text-slate-500">Designating a leader automatically grants Team Manager capabilities to oversee squad attendance.</p>
                </div>

                <div class="space-y-1.5">
                    <label class="block font-semibold text-slate-300">Description / Focus Area</label>
                    <textarea name="description" rows="2" placeholder="Briefly describe what this squad is responsible for..." class="w-full px-4 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-white focus:outline-none focus:border-indigo-500"></textarea>
                </div>

                <!-- Initial Members Selection -->
                <div class="space-y-2 pt-2 border-t border-slate-800">
                    <label class="block font-semibold text-slate-300">Select Initial Squad Members</label>
                    <div class="max-h-44 overflow-y-auto border border-slate-800 rounded-2xl bg-slate-950 p-2 space-y-1">
                        @foreach($availableEmployees as $emp)
                            <label class="flex items-center space-x-3 p-2 hover:bg-slate-900 rounded-xl cursor-pointer transition-colors">
                                <input type="checkbox" name="member_ids[]" value="{{ $emp->id }}" class="rounded bg-slate-900 border-slate-700 text-indigo-600 focus:ring-indigo-500">
                                <div class="min-w-0 flex-1">
                                    <p class="font-bold text-white text-xs">{{ $emp->name }}</p>
                                    <p class="text-[10px] text-slate-400">{{ $emp->designation->title ?? 'Employee' }} &bull; {{ $emp->team ? 'Currently in: ' . $emp->team->name : 'No squad' }}</p>
                                </div>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="pt-4 border-t border-slate-800 flex items-center justify-end space-x-3">
                    <button type="button" @click="createModalOpen = false" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold rounded-xl">Cancel</button>
                    <button type="submit" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white font-bold rounded-xl shadow-lg shadow-indigo-600/30">Create Squad</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ========================================================= -->
    <!-- MODAL 2: EDIT TEAM                                        -->
    <!-- ========================================================= -->
    <div x-show="editModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-md overflow-y-auto">
        <div @click.away="editModalOpen = false" class="bg-slate-900 border border-slate-800 rounded-3xl max-w-xl w-full p-6 md:p-8 space-y-6 shadow-2xl max-h-[92vh] overflow-y-auto">
            <div class="flex items-center justify-between border-b border-slate-800 pb-4">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-2xl bg-indigo-600/20 border border-indigo-500/30 flex items-center justify-center text-indigo-400">
                        <i class="fa-solid fa-pen-to-square text-base"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-black text-white tracking-tight">Edit Team Details</h3>
                        <p class="text-xs text-slate-400" x-text="'Updating squad properties for ' + activeTeam.name"></p>
                    </div>
                </div>
                <button @click="editModalOpen = false" class="text-slate-400 hover:text-white p-2 hover:bg-slate-800 rounded-xl transition-colors">
                    <i class="fa-solid fa-xmark text-sm"></i>
                </button>
            </div>

            <form :action="'{{ url('teams') }}/' + activeTeam.id" method="POST" class="space-y-4 text-xs">
                @csrf
                @method('PUT')

                <div class="space-y-1.5">
                    <label class="block font-semibold text-slate-300">Team / Squad Name *</label>
                    <input type="text" name="name" x-model="activeTeam.name" required class="w-full px-4 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-white focus:outline-none focus:border-indigo-500">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="space-y-1.5">
                        <label class="block font-semibold text-slate-300">Company / Organization *</label>
                        <select name="organization_id" x-model="activeTeam.organization_id" required class="w-full px-3 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-white">
                            @foreach($organizations as $org)
                                <option value="{{ $org->id }}">{{ $org->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="space-y-1.5">
                        <label class="block font-semibold text-slate-300">Department *</label>
                        <select name="department_id" x-model="activeTeam.department_id" required class="w-full px-3 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-white">
                            @foreach($departments as $dept)
                                <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="space-y-1.5">
                    <label class="block font-semibold text-slate-300">Assign Team Leader</label>
                    <select name="leader_id" x-model="activeTeam.leader_id" class="w-full px-3 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-white">
                        <option value="">-- No Leader Assigned --</option>
                        @foreach($eligibleLeaders as $leader)
                            <option value="{{ $leader->id }}">
                                {{ $leader->name }} ({{ $leader->role->label ?? 'Role' }} - {{ $leader->designation->title ?? 'Member' }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="space-y-1.5">
                    <label class="block font-semibold text-slate-300">Description / Focus Area</label>
                    <textarea name="description" x-model="activeTeam.description" rows="2" class="w-full px-4 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-white focus:outline-none focus:border-indigo-500"></textarea>
                </div>

                <div class="pt-4 border-t border-slate-800 flex items-center justify-end space-x-3">
                    <button type="button" @click="editModalOpen = false" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold rounded-xl">Cancel</button>
                    <button type="submit" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white font-bold rounded-xl shadow-lg shadow-indigo-600/30">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
    @endif

    <!-- ========================================================= -->
    <!-- MODAL 3: MANAGE SQUAD MEMBERS                             -->
    <!-- ========================================================= -->
    @if($canManageAll || $isLeader)
    <div x-show="manageMembersModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-md overflow-y-auto">
        <div @click.away="manageMembersModalOpen = false" class="bg-slate-900 border border-slate-800 rounded-3xl max-w-2xl w-full p-6 md:p-8 space-y-6 shadow-2xl max-h-[92vh] overflow-y-auto">
            <div class="flex items-center justify-between border-b border-slate-800 pb-4">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-2xl bg-indigo-600/20 border border-indigo-500/30 flex items-center justify-center text-indigo-400">
                        <i class="fa-solid fa-users text-base"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-black text-white tracking-tight" x-text="'Manage Members: ' + activeTeam.name"></h3>
                        <p class="text-xs text-slate-400">Add or remove workforce members in this squad</p>
                    </div>
                </div>
                <button @click="manageMembersModalOpen = false" class="text-slate-400 hover:text-white p-2 hover:bg-slate-800 rounded-xl transition-colors">
                    <i class="fa-solid fa-xmark text-sm"></i>
                </button>
            </div>

            <!-- Add Members Form -->
            <form :action="'{{ url('teams') }}/' + activeTeam.id + '/members'" method="POST" class="space-y-4 text-xs bg-slate-950 p-4 rounded-2xl border border-slate-800">
                @csrf
                <div class="flex items-center justify-between">
                    <h4 class="font-bold text-white text-xs">Add Employees to this Squad</h4>
                </div>

                <div class="max-h-40 overflow-y-auto border border-slate-800/80 rounded-xl p-2 space-y-1 bg-slate-900/60">
                    @foreach($availableEmployees as $emp)
                        <label class="flex items-center space-x-3 p-1.5 hover:bg-slate-800/60 rounded-lg cursor-pointer">
                            <input type="checkbox" name="user_ids[]" value="{{ $emp->id }}" class="rounded bg-slate-900 border-slate-700 text-indigo-600 focus:ring-indigo-500">
                            <div class="min-w-0 flex-1">
                                <span class="font-bold text-white text-xs">{{ $emp->name }}</span>
                                <span class="text-[10px] text-slate-400 ml-1">({{ $emp->designation->title ?? 'Employee' }} &bull; {{ $emp->team ? $emp->team->name : 'No Squad' }})</span>
                            </div>
                        </label>
                    @endforeach
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white font-bold rounded-xl shadow-md">
                        Add Selected to Squad
                    </button>
                </div>
            </form>

            <!-- Current Members List -->
            <div class="space-y-3">
                <h4 class="font-bold text-white text-xs">Current Squad Members (<span x-text="(activeTeam.members || []).length"></span>)</h4>
                <div class="space-y-2 max-h-56 overflow-y-auto">
                    <template x-for="member in (activeTeam.members || [])" :key="member.id">
                        <div class="p-3 bg-slate-950 border border-slate-800/80 rounded-2xl flex items-center justify-between">
                            <div class="flex items-center space-x-3">
                                <div class="w-8 h-8 rounded-xl bg-indigo-600/20 text-indigo-400 flex items-center justify-center font-bold text-xs" x-text="member.name ? member.name.substring(0, 2).toUpperCase() : 'M'"></div>
                                <div>
                                    <p class="font-bold text-white text-xs" x-text="member.name"></p>
                                    <p class="text-[10px] text-slate-400" x-text="member.email"></p>
                                </div>
                            </div>
                            <form :action="'{{ url('teams') }}/' + activeTeam.id + '/members/' + member.id" method="POST" onsubmit="return confirm('Remove member from squad?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-rose-400 hover:text-rose-300 text-xs font-bold px-2.5 py-1 hover:bg-rose-500/10 rounded-lg transition-colors">
                                    Remove
                                </button>
                            </form>
                        </div>
                    </template>
                </div>
            </div>

            <div class="pt-4 border-t border-slate-800 flex justify-end">
                <button type="button" @click="manageMembersModalOpen = false" class="px-5 py-2 bg-slate-800 text-slate-300 font-bold rounded-xl">Close</button>
            </div>
        </div>
    </div>
    @endif

</div>

<script>
function teamsManager() {
    return {
        createModalOpen: false,
        editModalOpen: false,
        manageMembersModalOpen: false,
        teamsList: @json($teams),
        selectedTeam: @json($selectedTeam),
        activeTeam: {
            id: '',
            name: '',
            organization_id: '',
            department_id: '',
            leader_id: '',
            description: '',
            members: []
        },
        findTeam(teamId) {
            if (this.selectedTeam && this.selectedTeam.id === teamId) {
                return this.selectedTeam;
            }
            return (this.teamsList || []).find(t => t.id === teamId) || null;
        },
        openEditModal(teamOrId) {
            const team = typeof teamOrId === 'string' ? this.findTeam(teamOrId) : teamOrId;
            if (!team) return;
            this.activeTeam = {
                id: team.id,
                name: team.name || '',
                organization_id: team.organization_id || '',
                department_id: team.department_id || '',
                leader_id: team.leader_id || '',
                description: team.description || '',
                members: team.members || []
            };
            this.editModalOpen = true;
        },
        openManageMembersModal(teamOrId) {
            const team = typeof teamOrId === 'string' ? this.findTeam(teamOrId) : teamOrId;
            if (!team) return;
            this.activeTeam = {
                id: team.id,
                name: team.name || '',
                organization_id: team.organization_id || '',
                department_id: team.department_id || '',
                leader_id: team.leader_id || '',
                description: team.description || '',
                members: team.members || []
            };
            this.manageMembersModalOpen = true;
        }
    };
}
</script>
@endsection
