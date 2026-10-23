@extends('layouts.app')

@section('title', 'Production Floor & Live Availability')

@section('content')

<div x-data="productionHub()" x-init="initHub()" class="space-y-6">

    <!-- Header Banner -->
    <div class="p-6 lg:p-8 rounded-3xl bg-gradient-to-r from-amber-950/70 via-slate-900 to-slate-900 border border-amber-500/30 relative overflow-hidden shadow-2xl">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <span class="inline-flex items-center space-x-2 px-3 py-1 rounded-full bg-amber-500/10 border border-amber-500/30 text-amber-400 text-xs font-semibold uppercase tracking-wider mb-2">
                    <i class="fa-solid fa-laptop-code"></i>
                    <span>Production Floor & Dev Availability Hub</span>
                </span>
                <h1 class="text-2xl lg:text-3xl font-extrabold text-white tracking-tight">Production Floor & Live Availability</h1>
                <p class="text-sm font-medium text-slate-400 mt-1 max-w-2xl">
                    Real-time live monitoring of tech & design teams, shift active status, 1-hour lunch breaks, Namaz & Smoke break elapsed timers, and over-limit policy warnings.
                </p>
            </div>
            <div class="flex items-center space-x-3">
                <span class="inline-flex items-center space-x-2 px-3.5 py-1.5 rounded-full bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-xs font-bold shadow-sm">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                    <span>Live Auto-Sync Active</span>
                </span>
                <span class="px-4 py-2 bg-slate-950/80 border border-slate-800 rounded-2xl text-xs font-bold text-slate-300 shadow-lg">
                    <i class="fa-solid fa-calendar-day text-amber-400 mr-1.5"></i> Today: <span x-text="todayFormatted"></span>
                </span>
            </div>
        </div>
    </div>

    <!-- Live Performance & Break Availability Metrics -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
        <!-- Total Tech Staff -->
        <div class="bg-slate-950/60 border border-slate-800 rounded-2xl p-4 shadow-xl">
            <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Total Tech Staff</p>
            <h3 class="text-2xl font-black text-white mt-1" x-text="counts.total_staff"></h3>
        </div>

        <!-- At Desk / Working -->
        <div class="bg-emerald-950/40 border border-emerald-500/20 rounded-2xl p-4 shadow-xl">
            <p class="text-[11px] font-bold text-emerald-400 uppercase tracking-wider">At Desk / Working</p>
            <h3 class="text-2xl font-black text-emerald-400 mt-1" x-text="counts.at_desk"></h3>
        </div>

        <!-- On Lunch Break (60m) -->
        <div class="bg-amber-950/40 border border-amber-500/20 rounded-2xl p-4 shadow-xl">
            <p class="text-[11px] font-bold text-amber-400 uppercase tracking-wider">On Lunch Break</p>
            <h3 class="text-2xl font-black text-amber-400 mt-1" x-text="counts.on_lunch"></h3>
        </div>

        <!-- On Namaz / Smoke -->
        <div class="bg-sky-950/40 border border-sky-500/20 rounded-2xl p-4 shadow-xl">
            <p class="text-[11px] font-bold text-sky-400 uppercase tracking-wider">Namaz / Smoke</p>
            <h3 class="text-2xl font-black text-sky-400 mt-1" x-text="counts.on_namaz_smoke"></h3>
        </div>

        <!-- Over-Limit Violations -->
        <div class="rounded-2xl p-4 shadow-xl border transition-all duration-300"
             :class="counts.break_exceeded > 0 ? 'bg-rose-950/70 border-rose-500/50 shadow-rose-500/20 animate-pulse' : 'bg-slate-950/60 border-slate-800'">
            <p class="text-[11px] font-bold uppercase tracking-wider"
               :class="counts.break_exceeded > 0 ? 'text-rose-400' : 'text-slate-400'">Break Over-Limit</p>
            <h3 class="text-2xl font-black mt-1"
                :class="counts.break_exceeded > 0 ? 'text-rose-400' : 'text-slate-400'"
                x-text="counts.break_exceeded"></h3>
        </div>

        <!-- On Leave / Absent -->
        <div class="bg-slate-950/60 border border-slate-800 rounded-2xl p-4 shadow-xl">
            <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">On Leave / Off</p>
            <h3 class="text-2xl font-black text-slate-300 mt-1" x-text="counts.on_leave_off"></h3>
        </div>
    </div>

    <!-- Filter Pills Bar -->
    <div class="flex flex-wrap items-center gap-2 p-2 bg-slate-950/60 border border-slate-800 rounded-2xl">
        <button type="button" @click="filterState = 'ALL'" 
                :class="filterState === 'ALL' ? 'bg-indigo-600 text-white font-bold' : 'text-slate-400 hover:text-white hover:bg-slate-900 font-medium'"
                class="px-3.5 py-1.5 rounded-xl text-xs transition-all">
            All Tech Staff (<span x-text="counts.total_staff"></span>)
        </button>
        <button type="button" @click="filterState = 'AT_DESK'" 
                :class="filterState === 'AT_DESK' ? 'bg-emerald-600 text-white font-bold' : 'text-slate-400 hover:text-emerald-400 hover:bg-slate-900 font-medium'"
                class="px-3.5 py-1.5 rounded-xl text-xs transition-all flex items-center space-x-1.5">
            <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
            <span>At Desk (<span x-text="counts.at_desk"></span>)</span>
        </button>
        <button type="button" @click="filterState = 'ON_LUNCH'" 
                :class="filterState === 'ON_LUNCH' ? 'bg-amber-600 text-white font-bold' : 'text-slate-400 hover:text-amber-400 hover:bg-slate-900 font-medium'"
                class="px-3.5 py-1.5 rounded-xl text-xs transition-all flex items-center space-x-1.5">
            <i class="fa-solid fa-utensils text-[10px]"></i>
            <span>On Lunch (<span x-text="counts.on_lunch"></span>)</span>
        </button>
        <button type="button" @click="filterState = 'ON_BREAKS'" 
                :class="filterState === 'ON_BREAKS' ? 'bg-sky-600 text-white font-bold' : 'text-slate-400 hover:text-sky-400 hover:bg-slate-900 font-medium'"
                class="px-3.5 py-1.5 rounded-xl text-xs transition-all flex items-center space-x-1.5">
            <i class="fa-solid fa-mosque text-[10px]"></i>
            <span>Namaz & Smoke (<span x-text="counts.on_namaz_smoke"></span>)</span>
        </button>
        <button type="button" @click="filterState = 'EXCEEDED'" 
                :class="filterState === 'EXCEEDED' ? 'bg-rose-600 text-white font-bold' : 'text-slate-400 hover:text-rose-400 hover:bg-slate-900 font-medium'"
                class="px-3.5 py-1.5 rounded-xl text-xs transition-all flex items-center space-x-1.5">
            <i class="fa-solid fa-triangle-exclamation text-[10px]"></i>
            <span>Over-Limit Violations (<span x-text="counts.break_exceeded"></span>)</span>
        </button>
    </div>

    <!-- Developers & Design Team Availability Table -->
    <div class="bg-slate-950/60 border border-slate-800 rounded-3xl overflow-hidden shadow-2xl">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead class="bg-slate-900/80 text-[11px] font-bold uppercase text-slate-400 border-b border-slate-800 tracking-wider">
                    <tr>
                        <th class="py-3.5 px-6">Team Member</th>
                        <th class="py-3.5 px-6">Department / Team</th>
                        <th class="py-3.5 px-6">Live Activity & Breaks</th>
                        <th class="py-3.5 px-6">Clock In / Net Work</th>
                        <th class="py-3.5 px-6">Current Assigned Project</th>
                        <th class="py-3.5 px-6 text-right">Break Logs & Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/80">
                    <template x-for="item in filteredStaff" :key="item.user_id">
                        <tr class="hover:bg-slate-900/40 transition-all">
                            
                            <!-- Team Member -->
                            <td class="py-4 px-6">
                                <div class="flex items-center space-x-3">
                                    <template x-if="item.avatar_url">
                                        <img :src="item.avatar_url" :alt="item.name" class="w-9 h-9 rounded-xl object-cover ring-2 ring-indigo-500/30 shadow-md shrink-0">
                                    </template>
                                    <template x-if="!item.avatar_url">
                                        <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-amber-500 to-indigo-600 flex items-center justify-center text-white font-bold text-xs shrink-0 shadow-md ring-2 ring-indigo-500/20"
                                             x-text="item.initials">
                                        </div>
                                    </template>
                                    <div>
                                        <p class="font-bold text-white" x-text="item.name"></p>
                                        <p class="text-[11px] text-slate-400 font-medium" x-text="item.designation"></p>
                                    </div>
                                </div>
                            </td>

                            <!-- Department / Team -->
                            <td class="py-4 px-6">
                                <span class="text-xs font-semibold text-slate-300" x-text="item.department"></span>
                                <p class="text-[11px] text-slate-500" x-text="item.team"></p>
                            </td>

                            <!-- Live Activity & Breaks State -->
                            <td class="py-4 px-6">
                                <!-- AT DESK -->
                                <template x-if="item.activity_state === 'AT_DESK'">
                                    <span class="inline-flex items-center space-x-1.5 px-2.5 py-1 rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/30 text-[11px] font-bold">
                                        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                                        <span>AT DESK / WORKING</span>
                                    </span>
                                </template>

                                <!-- ON LUNCH -->
                                <template x-if="item.activity_state === 'ON_LUNCH'">
                                    <div class="space-y-1">
                                        <span class="inline-flex items-center space-x-1.5 px-2.5 py-1 rounded-full bg-amber-500/10 text-amber-400 border border-amber-500/30 text-[11px] font-black animate-pulse">
                                            <i class="fa-solid fa-utensils text-[10px]"></i>
                                            <span>ON LUNCH BREAK (<span x-text="item.break_elapsed_minutes"></span>m / 60m)</span>
                                        </span>
                                        <template x-if="item.is_break_exceeded">
                                            <div class="text-[10px] font-black text-rose-400 flex items-center space-x-1">
                                                <i class="fa-solid fa-triangle-exclamation animate-bounce"></i>
                                                <span>+<span x-text="item.exceeded_minutes"></span>m Over Lunch Limit!</span>
                                            </div>
                                        </template>
                                    </div>
                                </template>

                                <!-- ON NAMAZ -->
                                <template x-if="item.activity_state === 'ON_NAMAZ'">
                                    <div class="space-y-1">
                                        <span class="inline-flex items-center space-x-1.5 px-2.5 py-1 rounded-full bg-emerald-500/10 text-emerald-300 border border-emerald-500/30 text-[11px] font-black animate-pulse">
                                            <i class="fa-solid fa-mosque text-[10px]"></i>
                                            <span>ON NAMAZ BREAK (<span x-text="item.break_elapsed_minutes"></span>m / 20m)</span>
                                        </span>
                                        <template x-if="item.is_break_exceeded">
                                            <div class="text-[10px] font-black text-rose-400 flex items-center space-x-1">
                                                <i class="fa-solid fa-triangle-exclamation animate-bounce"></i>
                                                <span>+<span x-text="item.exceeded_minutes"></span>m Over Prayer Limit!</span>
                                            </div>
                                        </template>
                                    </div>
                                </template>

                                <!-- ON SMOKE -->
                                <template x-if="item.activity_state === 'ON_SMOKE'">
                                    <div class="space-y-1">
                                        <span class="inline-flex items-center space-x-1.5 px-2.5 py-1 rounded-full bg-orange-500/10 text-orange-400 border border-orange-500/30 text-[11px] font-black animate-pulse">
                                            <i class="fa-solid fa-smoking text-[10px]"></i>
                                            <span>ON SMOKE BREAK (<span x-text="item.break_elapsed_minutes"></span>m / 15m)</span>
                                        </span>
                                        <template x-if="item.is_break_exceeded">
                                            <div class="text-[10px] font-black text-rose-400 flex items-center space-x-1">
                                                <i class="fa-solid fa-triangle-exclamation animate-bounce"></i>
                                                <span>+<span x-text="item.exceeded_minutes"></span>m Over Smoke Limit!</span>
                                            </div>
                                        </template>
                                    </div>
                                </template>

                                <!-- ON TEA -->
                                <template x-if="item.activity_state === 'ON_TEA'">
                                    <div class="space-y-1">
                                        <span class="inline-flex items-center space-x-1.5 px-2.5 py-1 rounded-full bg-amber-500/10 text-amber-300 border border-amber-500/30 text-[11px] font-black animate-pulse">
                                            <i class="fa-solid fa-mug-hot text-[10px]"></i>
                                            <span>ON TEA BREAK (<span x-text="item.break_elapsed_minutes"></span>m / 15m)</span>
                                        </span>
                                        <template x-if="item.is_break_exceeded">
                                            <div class="text-[10px] font-black text-rose-400 flex items-center space-x-1">
                                                <i class="fa-solid fa-triangle-exclamation animate-bounce"></i>
                                                <span>+<span x-text="item.exceeded_minutes"></span>m Over Tea Limit!</span>
                                            </div>
                                        </template>
                                    </div>
                                </template>

                                <!-- SHIFT DONE -->
                                <template x-if="item.activity_state === 'SHIFT_DONE'">
                                    <span class="inline-flex items-center space-x-1.5 px-2.5 py-1 rounded-full bg-slate-800 text-slate-400 border border-slate-700 text-[11px] font-semibold">
                                        <i class="fa-solid fa-flag-checkered text-[10px]"></i>
                                        <span>Shift Ended</span>
                                    </span>
                                </template>

                                <!-- ON LEAVE -->
                                <template x-if="item.activity_state === 'ON_LEAVE'">
                                    <span class="inline-flex items-center space-x-1.5 px-2.5 py-1 rounded-full bg-indigo-500/10 text-indigo-400 border border-indigo-500/30 text-[11px] font-bold">
                                        <i class="fa-solid fa-plane-departure text-[10px]"></i>
                                        <span>ON LEAVE / SICK</span>
                                    </span>
                                </template>

                                <!-- NOT CLOCKED IN -->
                                <template x-if="item.activity_state === 'OFFLINE'">
                                    <span class="inline-flex items-center space-x-1.5 px-2.5 py-1 rounded-full bg-slate-900 text-slate-500 border border-slate-800 text-[11px] font-semibold">
                                        NOT CLOCKED IN
                                    </span>
                                </template>
                            </td>

                            <!-- Clock In / Net Work -->
                            <td class="py-4 px-6">
                                <template x-if="item.clock_in">
                                    <div>
                                        <p class="font-bold text-white" x-text="'In: ' + item.clock_in"></p>
                                        <p class="text-[11px] text-indigo-300 font-semibold mt-0.5" x-text="'Net: ' + item.net_work_time"></p>
                                    </div>
                                </template>
                                <template x-if="!item.clock_in">
                                    <span class="text-slate-500">-- : --</span>
                                </template>
                            </td>

                            <!-- Current Assigned Project -->
                            <td class="py-4 px-6 font-medium text-slate-300">
                                <div class="flex items-center space-x-1.5">
                                    <i class="fa-solid fa-folder-open text-indigo-400 text-xs"></i>
                                    <span x-text="item.current_project"></span>
                                </div>
                            </td>

                            <!-- Break Logs Drawer & Availability -->
                            <td class="py-4 px-6 text-right">
                                <div class="flex items-center justify-end space-x-2">
                                    <template x-if="item.attendance_id">
                                        <button type="button" 
                                                @click="viewBreakLogs(item.attendance_id)" 
                                                class="px-2.5 py-1 rounded-xl bg-slate-800 hover:bg-slate-700 text-amber-300 text-[11px] font-bold transition-all flex items-center space-x-1 border border-slate-700">
                                            <i class="fa-solid fa-mug-hot text-[10px]"></i>
                                            <span x-text="item.total_break_time"></span>
                                        </button>
                                    </template>

                                    <template x-if="item.is_available">
                                        <span class="px-2 py-0.5 rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 text-[10px] font-bold">
                                            AVAILABLE
                                        </span>
                                    </template>
                                    <template x-if="!item.is_available">
                                        <span class="px-2 py-0.5 rounded-full bg-slate-800 text-slate-400 border border-slate-700 text-[10px] font-semibold">
                                            UNAVAILABLE
                                        </span>
                                    </template>
                                </div>
                            </td>
                        </tr>
                    </template>

                    <template x-if="filteredStaff.length === 0">
                        <tr>
                            <td colspan="6" class="py-8 text-center text-slate-500 font-medium">No developers or design team members match the selected filter.</td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Break Breakdown Modal Drawer -->
    <div x-show="breakModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/75 backdrop-blur-md">
        <div @click.away="breakModal = false" class="bg-slate-900 border border-slate-800 rounded-3xl max-w-xl w-full p-6 space-y-5 shadow-2xl">
            <div class="flex items-center justify-between border-b border-slate-800 pb-4">
                <div>
                    <h3 class="text-base font-bold text-white flex items-center space-x-2">
                        <i class="fa-solid fa-mug-hot text-amber-400"></i>
                        <span>Break Breakdown Logs</span>
                    </h3>
                    <p class="text-xs text-slate-400 mt-0.5" x-text="breakLogData.employee_name + ' • ' + breakLogData.date"></p>
                </div>
                <button @click="breakModal = false" class="text-slate-400 hover:text-white"><i class="fa-solid fa-xmark text-lg"></i></button>
            </div>

            <!-- Summary Chips -->
            <div class="grid grid-cols-3 gap-3">
                <div class="p-3 bg-slate-950 rounded-2xl border border-slate-800 text-center">
                    <p class="text-[10px] font-bold text-slate-400 uppercase">Gross Shift</p>
                    <p class="text-xs font-black text-white mt-0.5" x-text="breakLogData.total_shift || '--'"></p>
                </div>
                <div class="p-3 bg-slate-950 rounded-2xl border border-slate-800 text-center">
                    <p class="text-[10px] font-bold text-slate-400 uppercase">Total Breaks</p>
                    <p class="text-xs font-black text-amber-400 mt-0.5" x-text="breakLogData.total_breaks || '0m'"></p>
                </div>
                <div class="p-3 bg-slate-950 rounded-2xl border border-slate-800 text-center">
                    <p class="text-[10px] font-bold text-slate-400 uppercase">Net Working</p>
                    <p class="text-xs font-black text-emerald-400 mt-0.5" x-text="breakLogData.net_work || '--'"></p>
                </div>
            </div>

            <!-- Breaks List -->
            <div class="max-h-72 overflow-y-auto space-y-2.5">
                <template x-if="loadingBreaks">
                    <div class="py-8 text-center text-slate-400 text-xs">
                        <i class="fa-solid fa-spinner fa-spin text-lg text-indigo-400 mb-2"></i>
                        <p>Loading break records...</p>
                    </div>
                </template>

                <template x-if="!loadingBreaks && (!breakLogData.breaks || breakLogData.breaks.length === 0)">
                    <div class="py-8 text-center text-slate-500 text-xs">
                        No individual break logs recorded for this day.
                    </div>
                </template>

                <template x-for="(brk, idx) in (breakLogData.breaks || [])" :key="idx">
                    <div class="p-3.5 rounded-2xl border flex items-center justify-between"
                         :class="brk.is_exceeded ? 'bg-rose-500/10 border-rose-500/30' : 'bg-slate-950/80 border-slate-800'">
                        <div class="flex items-center space-x-3">
                            <div class="w-9 h-9 rounded-xl flex items-center justify-center text-xs border"
                                 :class="brk.badge_class || 'bg-slate-800 text-slate-300 border-slate-700'">
                                <i class="fa-solid" :class="brk.icon || 'fa-pause'"></i>
                            </div>
                            <div>
                                <div class="flex items-center space-x-2">
                                    <p class="text-xs font-bold text-white" x-text="brk.break_type + ' Break'"></p>
                                    <template x-if="brk.is_exceeded">
                                        <span class="px-1.5 py-0.2 rounded text-[9px] font-extrabold bg-rose-500/20 text-rose-400 border border-rose-500/30">
                                            +<span x-text="brk.exceeded_minutes"></span>m OVER LIMIT
                                        </span>
                                    </template>
                                </div>
                                <p class="text-[11px] text-slate-400 mt-0.5">
                                    <span x-text="brk.start_time"></span> &rarr; <span x-text="brk.end_time"></span>
                                </p>
                            </div>
                        </div>

                        <div class="text-right">
                            <span class="px-2.5 py-1 rounded-xl text-xs font-black"
                                  :class="brk.is_exceeded ? 'bg-rose-500/20 text-rose-400' : 'bg-slate-800 text-slate-200'"
                                  x-text="brk.duration"></span>
                            <template x-if="brk.status === 'ACTIVE'">
                                <p class="text-[10px] font-bold text-amber-400 mt-1 animate-pulse">In Progress</p>
                            </template>
                        </div>
                    </div>
                </template>
            </div>

            <div class="pt-2 flex justify-end">
                <button type="button" @click="breakModal = false" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-200 font-bold text-xs rounded-xl transition-all">Close</button>
            </div>
        </div>
    </div>

</div>

<script>
    function productionHub() {
        return {
            filterState: 'ALL',
            todayFormatted: '{{ date('d M Y') }}',
            breakModal: false,
            loadingBreaks: false,
            breakLogData: { employee_name: '', date: '', breaks: [], total_shift: '', total_breaks: '', net_work: '' },
            counts: {
                total_staff: {{ $totalStaff ?? 0 }},
                at_desk: {{ $atDeskCount ?? 0 }},
                on_lunch: {{ $onLunchCount ?? 0 }},
                on_namaz_smoke: {{ ($onNamazCount ?? 0) + ($onSmokeCount ?? 0) + ($onTeaCount ?? 0) }},
                break_exceeded: {{ $breakExceededCount ?? 0 }},
                on_leave_off: {{ ($leaveCount ?? 0) + ($absentCount ?? 0) }},
                available: {{ $availableCount ?? 0 }}
            },
            staffList: @json($staffListPayload ?? []),

            get filteredStaff() {
                if (this.filterState === 'ALL') return this.staffList;
                if (this.filterState === 'AT_DESK') return this.staffList.filter(s => s.activity_state === 'AT_DESK');
                if (this.filterState === 'ON_LUNCH') return this.staffList.filter(s => s.activity_state === 'ON_LUNCH');
                if (this.filterState === 'ON_BREAKS') return this.staffList.filter(s => ['ON_NAMAZ', 'ON_SMOKE', 'ON_TEA'].includes(s.activity_state));
                if (this.filterState === 'EXCEEDED') return this.staffList.filter(s => s.is_break_exceeded);
                return this.staffList;
            },

            async fetchLiveProduction() {
                try {
                    const res = await fetch('{{ route("live.production") }}', {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });
                    if (res.ok) {
                        const data = await res.json();
                        this.staffList = data.staff || [];
                        this.counts = data.counts || this.counts;
                        if (data.today_formatted) this.todayFormatted = data.today_formatted;
                        this.recalculateClientTimers();
                    }
                } catch(e) {
                    console.error('Failed to sync live production data', e);
                }
            },

            recalculateClientTimers() {
                const nowTs = Math.floor(Date.now() / 1000);
                let exceededCount = 0;

                this.staffList.forEach(staff => {
                    if (staff.active_break && staff.active_break.start_timestamp) {
                        const elapsedSecs = Math.max(0, nowTs - staff.active_break.start_timestamp);
                        const elapsedMins = Math.max(1, Math.floor(elapsedSecs / 60));
                        staff.break_elapsed_minutes = elapsedMins;

                        const allowed = staff.active_break.allowed_minutes || 60;
                        if (elapsedMins > allowed) {
                            staff.is_break_exceeded = true;
                            staff.exceeded_minutes = elapsedMins - allowed;
                            exceededCount++;
                        } else {
                            staff.is_break_exceeded = false;
                            staff.exceeded_minutes = 0;
                        }
                    } else if (staff.is_break_exceeded) {
                        exceededCount++;
                    }
                });

                this.counts.break_exceeded = exceededCount;
            },

            initHub() {
                // Initial client calculation
                this.recalculateClientTimers();

                // 1. Client-Side Second-by-Second Timer Ticking (Zero Server Requests)
                setInterval(() => {
                    this.recalculateClientTimers();
                }, 1000);

                // 2. Listen to Global Live Sync Events
                window.addEventListener('hrms:production-sync', () => {
                    this.fetchLiveProduction();
                });

                // Fallback direct poll every 8 seconds if tab is active
                setInterval(() => {
                    if (!document.hidden) {
                        this.fetchLiveProduction();
                    }
                }, 8000);
            },

            async viewBreakLogs(attId) {
                if (!attId) return;
                this.loadingBreaks = true;
                this.breakModal = true;
                try {
                    const res = await fetch('{{ url('attendance') }}/' + attId + '/break-logs', {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });
                    if (res.ok) {
                        this.breakLogData = await res.json();
                    }
                } catch(e) {
                    console.error(e);
                } finally {
                    this.loadingBreaks = false;
                }
            }
        };
    }
</script>

@endsection
