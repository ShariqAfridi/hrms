@extends('layouts.app')

@section('title', 'Attendance & Break Management')

@section('content')

<div x-data="{ 
    recordModal: false, 
    editModal: false, 
    breakModal: false,
    detailModal: false,
    breakLogData: { employee_name: '', date: '', breaks: [], total_shift: '', total_breaks: '', net_work: '' },
    activeAtt: {},
    activeDetail: {
        user_name: '',
        user_email: '',
        user_avatar: '',
        department: '',
        designation: '',
        organization: '',
        date: '',
        status: '',
        is_on_break: false,
        current_break_type: '',
        clock_in_time: '',
        clock_in_date: '',
        clock_out_time: '',
        clock_out_date: '',
        gross_shift: '',
        total_breaks: '',
        breaks_count: 0,
        net_work: '',
        overtime: '',
        ip_address: '',
        location_name: '',
        location_verified: false,
        latitude: null,
        longitude: null,
        has_coordinates: false,
        google_maps_url: null,
        device_name: '',
        device_os: '',
        device_browser: '',
        device_type: '',
        device_icon: 'fa-desktop',
        raw_user_agent: '',
        approval_status: 'APPROVED',
        remarks: '',
        breaks: []
    },
    openDetails(data) {
        this.activeDetail = data;
        this.detailModal = true;
    },
    loadingBreaks: false,
    async viewBreakLogs(attId) {
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
    },
    geoLat: null,
    geoLng: null,
    getGeoLocation(formId) {
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition((pos) => {
                const form = document.getElementById(formId);
                if (form) {
                    const latInput = form.querySelector('input[name=latitude]') || document.createElement('input');
                    latInput.type = 'hidden';
                    latInput.name = 'latitude';
                    latInput.value = pos.coords.latitude;
                    form.appendChild(latInput);

                    const lngInput = form.querySelector('input[name=longitude]') || document.createElement('input');
                    lngInput.type = 'hidden';
                    lngInput.name = 'longitude';
                    lngInput.value = pos.coords.longitude;
                    form.appendChild(lngInput);

                    form.submit();
                }
            }, () => {
                // Fallback without coordinates
                const form = document.getElementById(formId);
                if (form) form.submit();
            }, { timeout: 4000 });
        } else {
            const form = document.getElementById(formId);
            if (form) form.submit();
        }
    },
    karachiClock: '',
    karachiDate: '',
    updateKarachiClock() {
        const now = new Date();
        this.karachiClock = now.toLocaleTimeString('en-US', { timeZone: 'Asia/Karachi', hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true });
        this.karachiDate = now.toLocaleDateString('en-US', { timeZone: 'Asia/Karachi', weekday: 'short', day: '2-digit', month: 'short' });
    },
    initClock() {
        this.updateKarachiClock();
        setInterval(() => this.updateKarachiClock(), 1000);
    }
}" x-init="initClock()" class="space-y-6">

    <!-- Top Action Bar & Live Attendance Hub -->
    <div class="p-6 lg:p-8 rounded-3xl bg-gradient-to-r from-slate-900 via-indigo-950/40 to-slate-900 border border-slate-800 relative overflow-hidden shadow-2xl">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
            <div>
                <div class="flex items-center space-x-2 mb-2">
                    <span class="px-3 py-1 rounded-full bg-indigo-500/10 border border-indigo-500/30 text-indigo-400 text-xs font-bold uppercase tracking-wider">
                        <i class="fa-solid fa-clock mr-1"></i> Time & Break Engine
                    </span>
                    @if($userAttendanceToday && $userAttendanceToday->location_verified)
                        <span class="px-2.5 py-0.5 rounded-full bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-[11px] font-semibold">
                            <i class="fa-solid fa-building-circle-check mr-1"></i> Office Verified
                        </span>
                    @endif
                </div>
                <h1 class="text-2xl lg:text-3xl font-extrabold text-white tracking-tight">Attendance & Break Tracking Hub</h1>
                <p class="text-xs font-medium text-slate-400 mt-1 max-w-xl">
                    Strict office clock-in verification, 1-hour lunch break policy, Namaz & Smoke tracking with real-time Production Manager and Admin alert routing.
                </p>
            </div>

            @if(Auth::user()->isClockInExempt())
            <!-- Supervisor & HR Attendance Monitoring Command Deck -->
            <div class="bg-slate-950/90 border border-slate-800 rounded-3xl p-5 shadow-2xl min-w-[320px] max-w-md w-full space-y-4">
                <div class="flex items-center justify-between border-b border-slate-800/80 pb-3">
                    <div>
                        <div class="flex items-center space-x-2">
                            <span class="px-2.5 py-0.5 rounded-full bg-indigo-500/10 border border-indigo-500/30 text-indigo-400 font-extrabold text-[10px] uppercase tracking-wider">
                                <i class="fa-solid fa-shield-check mr-1"></i> Admin & HR Oversight
                            </span>
                        </div>
                        <p class="text-xs font-semibold text-slate-300 mt-1">Exempt from daily clock-in</p>
                    </div>

                    <div class="text-right flex flex-col items-end">
                        <div class="inline-flex items-center space-x-1.5 px-2.5 py-1 rounded-xl bg-slate-900 border border-slate-800 text-indigo-300 font-mono font-bold text-xs shadow-inner">
                            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                            <span x-text="karachiClock || '{{ date('h:i:s A') }}'">{{ date('h:i:s A') }}</span>
                            <span class="text-[9px] text-indigo-400 font-extrabold uppercase tracking-wider bg-indigo-500/10 px-1 py-0.5 rounded">PKT</span>
                        </div>
                        <p class="text-[10px] font-medium text-slate-400 mt-0.5" x-text="karachiDate || '{{ date('D, d M') }}'">{{ date('D, d M') }}</p>
                    </div>
                </div>

                <div class="space-y-3">
                    <p class="text-[11px] text-slate-300 leading-relaxed">
                        You have administrative oversight. You can monitor live check-ins, break durations, review staff records, and record manual attendance adjustments below.
                    </p>
                    <div class="grid grid-cols-3 gap-2 py-1">
                        <div class="p-2.5 rounded-2xl bg-slate-900/70 border border-slate-800/80 text-center">
                            <p class="text-[10px] uppercase font-bold text-slate-400">Present</p>
                            <p class="text-lg font-black text-emerald-400 mt-0.5">{{ $todayPresentCount }}</p>
                        </div>
                        <div class="p-2.5 rounded-2xl bg-slate-900/70 border border-slate-800/80 text-center">
                            <p class="text-[10px] uppercase font-bold text-slate-400">On Break</p>
                            <p class="text-lg font-black text-amber-400 mt-0.5">{{ $todayOnBreakCount }}</p>
                        </div>
                        <div class="p-2.5 rounded-2xl bg-slate-900/70 border border-slate-800/80 text-center">
                            <p class="text-[10px] uppercase font-bold text-slate-400">Late</p>
                            <p class="text-lg font-black text-orange-400 mt-0.5">{{ $todayLateCount }}</p>
                        </div>
                    </div>
                    <button type="button" @click="recordModal = true" class="w-full py-2.5 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-500 hover:to-purple-500 text-white font-bold text-xs rounded-xl shadow-lg shadow-indigo-600/30 transition-all flex items-center justify-center space-x-2">
                        <i class="fa-solid fa-user-pen"></i>
                        <span>Record / Adjust Staff Attendance</span>
                    </button>
                </div>
            </div>
            @else
            <!-- Employee Quick Punch & Break Command Deck -->
            <div class="bg-slate-950/90 border border-slate-800 rounded-3xl p-4 sm:p-5 shadow-2xl min-w-[320px] max-w-md w-full">
                <div class="flex items-center justify-between border-b border-slate-800/80 pb-3 mb-3">
                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Your Shift Status</p>
                        @if(!$userAttendanceToday)
                            <p class="text-sm font-black text-amber-400 mt-0.5 flex items-center space-x-1.5">
                                <span class="w-2 h-2 rounded-full bg-amber-400 animate-ping"></span>
                                <span>Not Clocked In</span>
                            </p>
                        @elseif($userAttendanceToday->clock_out)
                            <p class="text-sm font-black text-slate-300 mt-0.5 flex items-center space-x-1.5">
                                <i class="fa-solid fa-circle-check text-indigo-400"></i>
                                <span>Shift Completed</span>
                            </p>
                        @elseif($userAttendanceToday->isOnBreak())
                            <p class="text-sm font-black text-amber-400 mt-0.5 flex items-center space-x-1.5 animate-pulse">
                                <i class="fa-solid fa-mug-hot text-amber-400"></i>
                                <span>On {{ ucfirst(strtolower($userAttendanceToday->current_break_type)) }} Break</span>
                            </p>
                        @else
                            <p class="text-sm font-black text-emerald-400 mt-0.5 flex items-center space-x-1.5">
                                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                                <span>Working / At Desk</span>
                            </p>
                        @endif
                    </div>

                    <div class="text-right flex flex-col items-end">
                        <div class="inline-flex items-center space-x-1.5 px-2.5 py-1 rounded-xl bg-slate-900 border border-slate-800 text-indigo-300 font-mono font-bold text-xs shadow-inner">
                            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                            <span x-text="karachiClock || '{{ date('h:i:s A') }}'">{{ date('h:i:s A') }}</span>
                            <span class="text-[9px] text-indigo-400 font-extrabold uppercase tracking-wider bg-indigo-500/10 px-1 py-0.5 rounded">PKT</span>
                        </div>
                        @if($userAttendanceToday)
                            <p class="text-[10px] font-bold text-slate-400 uppercase mt-1">Net: <span class="text-emerald-400 font-mono font-bold">{{ $userAttendanceToday->getFormattedNetTime() }}</span></p>
                        @else
                            <p class="text-[10px] font-medium text-slate-400 mt-0.5" x-text="karachiDate || '{{ date('D, d M') }}'">{{ date('D, d M') }}</p>
                        @endif
                    </div>
                </div>

                <!-- Action Controls -->
                <div>
                    @if(!$userAttendanceToday)
                        <!-- Clock In Button with Location Fetch -->
                        <form id="clock-in-form" action="{{ route('attendance.clock-in') }}" method="POST">
                            @csrf
                            <button type="button" 
                                    @click="getGeoLocation('clock-in-form')" 
                                    class="w-full py-3 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white font-black text-xs rounded-2xl shadow-lg shadow-emerald-600/30 transition-all flex items-center justify-center space-x-2">
                                <i class="fa-solid fa-right-to-bracket text-sm"></i>
                                <span>Clock In Office (Start Shift)</span>
                            </button>
                        </form>

                    @elseif(!$userAttendanceToday->clock_out)
                        @if($userAttendanceToday->isOnBreak())
                            <!-- Currently On Break -> Show Resume Work / End Break -->
                            <form action="{{ route('attendance.end-break') }}" method="POST">
                                @csrf
                                <button type="submit" class="w-full py-3 bg-gradient-to-r from-amber-600 to-emerald-600 hover:from-amber-500 hover:to-emerald-500 text-white font-black text-xs rounded-2xl shadow-lg shadow-amber-600/30 transition-all flex items-center justify-center space-x-2 animate-bounce">
                                    <i class="fa-solid fa-play text-sm"></i>
                                    <span>End {{ ucfirst(strtolower($userAttendanceToday->current_break_type)) }} & Resume Work</span>
                                </button>
                            </form>
                        @else
                            <!-- Currently Working -> Break Option Pills + Clock Out -->
                            <div class="space-y-2.5">
                                <div class="grid grid-cols-3 gap-2">
                                    <!-- Lunch Break (1 Hour) -->
                                    <form action="{{ route('attendance.start-break') }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="break_type" value="LUNCH">
                                        <button type="submit" class="w-full py-2 px-1.5 bg-amber-500/10 hover:bg-amber-500/20 text-amber-300 border border-amber-500/30 rounded-xl text-[11px] font-bold transition-all flex flex-col items-center justify-center text-center shadow-sm">
                                            <i class="fa-solid fa-utensils text-xs mb-1"></i>
                                            <span>Lunch (60m)</span>
                                        </button>
                                    </form>

                                    <!-- Namaz Break (20 Mins) -->
                                    <form action="{{ route('attendance.start-break') }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="break_type" value="NAMAZ">
                                        <button type="submit" class="w-full py-2 px-1.5 bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 rounded-xl text-[11px] font-bold transition-all flex flex-col items-center justify-center text-center shadow-sm">
                                            <i class="fa-solid fa-mosque text-xs mb-1"></i>
                                            <span>Namaz (20m)</span>
                                        </button>
                                    </form>

                                    <!-- Smoke / Refreshment Break (15 Mins) -->
                                    <form action="{{ route('attendance.start-break') }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="break_type" value="SMOKE">
                                        <button type="submit" class="w-full py-2 px-1.5 bg-orange-500/10 hover:bg-orange-500/20 text-orange-300 border border-orange-500/30 rounded-xl text-[11px] font-bold transition-all flex flex-col items-center justify-center text-center shadow-sm">
                                            <i class="fa-solid fa-smoking text-xs mb-1"></i>
                                            <span>Smoke (15m)</span>
                                        </button>
                                    </form>
                                </div>

                                <!-- Clock Out Form -->
                                <form action="{{ route('attendance.clock-out') }}" method="POST" onsubmit="return confirm('Are you sure you want to Clock Out and end your shift for today?');">
                                    @csrf
                                    <button type="submit" class="w-full py-2.5 bg-rose-600/20 hover:bg-rose-600 text-rose-300 hover:text-white border border-rose-500/30 font-bold text-xs rounded-xl shadow-md transition-all flex items-center justify-center space-x-1.5">
                                        <i class="fa-solid fa-right-from-bracket"></i>
                                        <span>Clock Out (End Shift)</span>
                                    </button>
                                </form>
                            </div>
                        @endif

                    @else
                        <!-- Shift Finished Summary -->
                        <div class="p-3 bg-slate-900 border border-slate-800 rounded-2xl text-center space-y-1">
                            <p class="text-xs font-bold text-white">Shift Ended at {{ date('h:i A', strtotime($userAttendanceToday->clock_out)) }}</p>
                            <p class="text-[11px] text-slate-400">
                                Total Breaks: <span class="text-amber-400 font-semibold">{{ $userAttendanceToday->getFormattedBreakTime() }}</span> | 
                                Net Work: <span class="text-emerald-400 font-bold">{{ $userAttendanceToday->getFormattedNetTime() }}</span>
                            </p>
                        </div>
                    @endif
                </div>
            </div>
            @endif
        </div>
    </div>

    <!-- Quick Attendance & Break Metrics Today -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div class="bg-slate-950/60 border border-slate-800 rounded-2xl p-4 shadow-xl">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Present Today</p>
                    <h3 class="text-2xl font-black text-emerald-400 mt-1">{{ $todayPresentCount }}</h3>
                </div>
                <div class="w-10 h-10 rounded-xl bg-emerald-500/10 text-emerald-400 flex items-center justify-center text-lg">
                    <i class="fa-solid fa-user-check"></i>
                </div>
            </div>
        </div>

        <div class="bg-slate-950/60 border border-slate-800 rounded-2xl p-4 shadow-xl">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Currently on Break</p>
                    <h3 class="text-2xl font-black text-amber-400 mt-1">{{ $todayOnBreakCount }}</h3>
                </div>
                <div class="w-10 h-10 rounded-xl bg-amber-500/10 text-amber-400 flex items-center justify-center text-lg">
                    <i class="fa-solid fa-mug-hot"></i>
                </div>
            </div>
        </div>

        <div class="bg-slate-950/60 border border-slate-800 rounded-2xl p-4 shadow-xl">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Late Arrivals</p>
                    <h3 class="text-2xl font-black text-orange-400 mt-1">{{ $todayLateCount }}</h3>
                </div>
                <div class="w-10 h-10 rounded-xl bg-orange-500/10 text-orange-400 flex items-center justify-center text-lg">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                </div>
            </div>
        </div>

        <div class="bg-slate-950/60 border border-slate-800 rounded-2xl p-4 shadow-xl">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Total Records</p>
                    <h3 class="text-2xl font-black text-indigo-400 mt-1">{{ $attendances->total() }}</h3>
                </div>
                <div class="w-10 h-10 rounded-xl bg-indigo-500/10 text-indigo-400 flex items-center justify-center text-lg">
                    <i class="fa-solid fa-list-ol"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Bar & Admin Manual Action -->
    <div class="bg-slate-950/60 border border-slate-800 rounded-3xl p-4 shadow-xl">
        <form action="{{ route('attendance.index') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-6 gap-3 items-end">
            @if(Auth::user()->canManageEmployees())
            <div class="md:col-span-2">
                <label class="block text-[11px] font-semibold text-slate-400 mb-1">Search Employee</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Name or email..." class="w-full px-3 py-2 bg-slate-900 border border-slate-800 rounded-xl text-xs text-white focus:outline-none focus:border-indigo-500">
            </div>

            <div>
                <label class="block text-[11px] font-semibold text-slate-400 mb-1">Company</label>
                <select name="organization_id" class="w-full px-3 py-2 bg-slate-900 border border-slate-800 rounded-xl text-xs text-slate-300 focus:outline-none focus:border-indigo-500">
                    <option value="">All Companies</option>
                    @foreach($organizations as $org)
                        <option value="{{ $org->id }}" {{ request('organization_id') == $org->id ? 'selected' : '' }}>{{ $org->name }}</option>
                    @endforeach
                </select>
            </div>
            @endif

            <div>
                <label class="block text-[11px] font-semibold text-slate-400 mb-1">Date</label>
                <input type="date" name="date" value="{{ request('date') }}" class="w-full px-3 py-2 bg-slate-900 border border-slate-800 rounded-xl text-xs text-white focus:outline-none focus:border-indigo-500">
            </div>

            <div>
                <label class="block text-[11px] font-semibold text-slate-400 mb-1">Status</label>
                <select name="status" class="w-full px-3 py-2 bg-slate-900 border border-slate-800 rounded-xl text-xs text-slate-300 focus:outline-none focus:border-indigo-500">
                    <option value="">All Statuses</option>
                    <option value="PRESENT" {{ request('status') == 'PRESENT' ? 'selected' : '' }}>Present</option>
                    <option value="LATE" {{ request('status') == 'LATE' ? 'selected' : '' }}>Late</option>
                    <option value="ABSENT" {{ request('status') == 'ABSENT' ? 'selected' : '' }}>Absent</option>
                    <option value="HALF_DAY" {{ request('status') == 'HALF_DAY' ? 'selected' : '' }}>Half Day</option>
                </select>
            </div>

            <div class="flex items-center space-x-2">
                <button type="submit" class="w-full py-2 bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs rounded-xl shadow-md transition-all">Filter</button>
                <a href="{{ route('attendance.index') }}" class="px-3 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs rounded-xl font-semibold">Reset</a>
                
                @if(Auth::user()->canManageEmployees())
                <button type="button" @click="recordModal = true" class="px-3 py-2 bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs rounded-xl shadow-md whitespace-nowrap" title="Manual Attendance">
                    <i class="fa-solid fa-plus"></i>
                </button>
                @endif
            </div>
        </form>
    </div>

    <!-- Attendance Streamlined Minimal Table -->
    <div class="bg-slate-950/60 border border-slate-800 rounded-3xl overflow-hidden shadow-2xl">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-900/80 border-b border-slate-800 text-[11px] font-bold text-slate-400 uppercase tracking-wider">
                        <th class="py-3.5 px-6">Employee</th>
                        <th class="py-3.5 px-6">Company</th>
                        <th class="py-3.5 px-6">Date</th>
                        <th class="py-3.5 px-6">Clock In</th>
                        <th class="py-3.5 px-6">Clock Out</th>
                        <th class="py-3.5 px-6">Net Work</th>
                        <th class="py-3.5 px-6">Status / Activity</th>
                        <th class="py-3.5 px-6 text-right">Details & Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/80 text-xs">
                    @forelse($attendances as $att)
                        @php
                            $detailPayload = $att->toDetailArray();
                            $detailJson = json_encode($detailPayload, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
                        @endphp
                        <tr class="hover:bg-slate-900/40 transition-colors">
                            <!-- Employee -->
                            <td class="py-4 px-6">
                                <div class="flex items-center space-x-3">
                                    <div class="w-8 h-8 rounded-xl bg-gradient-to-tr from-indigo-600 to-purple-600 text-white flex items-center justify-center font-bold text-xs shrink-0 shadow-md">
                                        {{ strtoupper(substr($att->user->name ?? 'U', 0, 1)) }}
                                    </div>
                                    <div>
                                        <p class="font-bold text-white leading-tight">{{ $att->user->name ?? 'User' }}</p>
                                        <p class="text-slate-400 text-[11px] font-medium mt-0.5">{{ $att->user->department->name ?? 'General' }}</p>
                                    </div>
                                </div>
                            </td>

                            <!-- Company -->
                            <td class="py-4 px-6 font-semibold text-slate-300">
                                {{ $att->organization->name ?? ($att->user->organization->name ?? 'General') }}
                            </td>

                            <!-- Date -->
                            <td class="py-4 px-6 font-medium text-slate-300 whitespace-nowrap">
                                {{ date('d M Y', strtotime($att->date)) }}
                            </td>

                            <!-- Clock In -->
                            <td class="py-4 px-6 whitespace-nowrap">
                                @if($att->clock_in)
                                    <div class="flex items-center space-x-1.5">
                                        <span class="font-bold text-emerald-400 font-mono">{{ date('h:i A', strtotime($att->clock_in)) }}</span>
                                        @if($att->location_verified)
                                            <span class="text-[11px] text-emerald-400" title="Office Verified"><i class="fa-solid fa-circle-check"></i></span>
                                        @else
                                            <span class="text-[11px] text-slate-500" title="Remote Punch"><i class="fa-solid fa-globe"></i></span>
                                        @endif
                                    </div>
                                @else
                                    <span class="text-slate-500 font-mono">--</span>
                                @endif
                            </td>

                            <!-- Clock Out -->
                            <td class="py-4 px-6 font-semibold text-slate-400 font-mono whitespace-nowrap">
                                {{ $att->clock_out ? date('h:i A', strtotime($att->clock_out)) : ($att->clock_in ? 'In Progress' : '--') }}
                            </td>

                            <!-- Net Work Hours -->
                            <td class="py-4 px-6 font-black text-indigo-300 font-mono whitespace-nowrap">
                                {{ $att->net_work_minutes ? $att->getFormattedNetTime() : ($att->clock_in ? 'Calculating...' : '--') }}
                            </td>

                            <!-- Status / Live Activity -->
                            <td class="py-4 px-6 whitespace-nowrap">
                                @if($att->isOnBreak())
                                    <span class="px-2.5 py-1 rounded-full font-bold bg-amber-500/10 text-amber-400 border border-amber-500/30 text-[11px] animate-pulse inline-flex items-center space-x-1">
                                        <i class="fa-solid fa-mug-hot"></i>
                                        <span>ON {{ strtoupper($att->current_break_type) }}</span>
                                    </span>
                                @elseif($att->status == 'PRESENT')
                                    <span class="px-2.5 py-1 rounded-full font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 text-[11px]">PRESENT</span>
                                @elseif($att->status == 'LATE')
                                    <span class="px-2.5 py-1 rounded-full font-bold bg-orange-500/10 text-orange-400 border border-orange-500/20 text-[11px]">LATE</span>
                                @elseif($att->status == 'HALF_DAY')
                                    <span class="px-2.5 py-1 rounded-full font-bold bg-indigo-500/10 text-indigo-400 border border-indigo-500/20 text-[11px]">HALF DAY</span>
                                @else
                                    <span class="px-2.5 py-1 rounded-full font-bold bg-rose-500/10 text-rose-400 border border-rose-500/20 text-[11px]">ABSENT</span>
                                @endif
                            </td>

                            <!-- Actions & More Details Button -->
                            <td class="py-4 px-6 text-right whitespace-nowrap">
                                <div class="flex items-center justify-end space-x-2">
                                    <!-- More Details Button -->
                                    <button type="button" 
                                            @click="openDetails({{ $detailJson }})" 
                                            class="inline-flex items-center space-x-1.5 px-3 py-1.5 rounded-xl bg-indigo-500/15 hover:bg-indigo-600 text-indigo-300 hover:text-white border border-indigo-500/30 hover:border-indigo-500 font-bold text-xs shadow-sm transition-all cursor-pointer">
                                        <i class="fa-solid fa-circle-info text-xs"></i>
                                        <span>More Details</span>
                                    </button>

                                    @if(Auth::user()->canManageEmployees())
                                    <button @click="editModal = true; activeAtt = {
                                        id: '{{ $att->id }}',
                                        userName: '{{ addslashes($att->user->name ?? 'User') }}',
                                        date: '{{ date('Y-m-d', strtotime($att->date)) }}',
                                        clock_in_time: '{{ $att->clock_in ? date('H:i', strtotime($att->clock_in)) : '09:00' }}',
                                        clock_out_time: '{{ $att->clock_out ? date('H:i', strtotime($att->clock_out)) : '' }}',
                                        status: '{{ $att->status }}',
                                        remarks: '{{ addslashes($att->remarks ?? '') }}'
                                    }" class="px-2.5 py-1.5 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-xl text-xs font-semibold" title="Edit Log">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </button>

                                    <form action="{{ route('attendance.destroy', $att->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this attendance record?');" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="px-2.5 py-1.5 bg-rose-600/20 hover:bg-rose-600 text-rose-400 hover:text-white rounded-xl text-xs font-semibold transition-all" title="Delete">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </button>
                                    </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-8 text-center text-slate-500 font-medium">
                                No attendance records found matching your filters.
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

    <!-- Complete Attendance & Telemetry Audit Modal (More Details) -->
    <div x-show="detailModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-md overflow-y-auto">
        <div @click.away="detailModal = false" class="bg-slate-900 border border-slate-800 rounded-3xl max-w-2xl w-full p-6 sm:p-7 space-y-6 shadow-2xl my-8 relative">
            
            <!-- Header with Employee Profile -->
            <div class="flex items-start justify-between border-b border-slate-800 pb-5">
                <div class="flex items-center space-x-3.5">
                    <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-indigo-600 to-purple-600 text-white flex items-center justify-center font-black text-lg shadow-lg" x-text="activeDetail.user_avatar"></div>
                    <div>
                        <div class="flex items-center space-x-2">
                            <h3 class="text-base sm:text-lg font-black text-white tracking-tight" x-text="activeDetail.user_name"></h3>
                            <template x-if="activeDetail.status === 'PRESENT'">
                                <span class="px-2 py-0.5 rounded-full font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 text-[10px]">PRESENT</span>
                            </template>
                            <template x-if="activeDetail.status === 'LATE'">
                                <span class="px-2 py-0.5 rounded-full font-bold bg-orange-500/10 text-orange-400 border border-orange-500/20 text-[10px]">LATE</span>
                            </template>
                            <template x-if="activeDetail.status === 'HALF_DAY'">
                                <span class="px-2 py-0.5 rounded-full font-bold bg-indigo-500/10 text-indigo-400 border border-indigo-500/20 text-[10px]">HALF DAY</span>
                            </template>
                            <template x-if="activeDetail.status === 'ABSENT'">
                                <span class="px-2 py-0.5 rounded-full font-bold bg-rose-500/10 text-rose-400 border border-rose-500/20 text-[10px]">ABSENT</span>
                            </template>
                            <template x-if="activeDetail.is_on_break">
                                <span class="px-2 py-0.5 rounded-full font-bold bg-amber-500/10 text-amber-400 border border-amber-500/30 text-[10px] animate-pulse">ON BREAK</span>
                            </template>
                        </div>
                        <p class="text-xs text-slate-400 mt-0.5">
                            <span x-text="activeDetail.designation || 'Staff'"></span> &bull; 
                            <span x-text="activeDetail.department"></span> &bull; 
                            <span class="text-indigo-400 font-semibold" x-text="activeDetail.organization"></span>
                        </p>
                    </div>
                </div>

                <button @click="detailModal = false" class="text-slate-400 hover:text-white p-1 rounded-lg transition-colors">
                    <i class="fa-solid fa-xmark text-xl"></i>
                </button>
            </div>

            <!-- Key Shift & Work Duration Summary Cards -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                <div class="p-3.5 bg-slate-950/80 rounded-2xl border border-slate-800">
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Clock In Time</p>
                    <p class="text-sm font-black text-emerald-400 mt-1 font-mono" x-text="activeDetail.clock_in_time"></p>
                    <p class="text-[10px] text-slate-500 mt-0.5" x-text="activeDetail.clock_in_date"></p>
                </div>

                <div class="p-3.5 bg-slate-950/80 rounded-2xl border border-slate-800">
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Clock Out Time</p>
                    <p class="text-sm font-black text-slate-300 mt-1 font-mono" x-text="activeDetail.clock_out_time"></p>
                    <p class="text-[10px] text-slate-500 mt-0.5" x-text="activeDetail.clock_out_date !== '--' ? activeDetail.clock_out_date : (activeDetail.clock_out_time === 'In Progress' ? 'Shift Active' : '--')"></p>
                </div>

                <div class="p-3.5 bg-slate-950/80 rounded-2xl border border-slate-800">
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Gross Shift</p>
                    <p class="text-sm font-black text-white mt-1 font-mono" x-text="activeDetail.gross_shift"></p>
                    <p class="text-[10px] text-amber-400 mt-0.5" x-text="'Breaks: ' + activeDetail.total_breaks + ' (' + activeDetail.breaks_count + ')'"></p>
                </div>

                <div class="p-3.5 bg-slate-950/80 rounded-2xl border border-slate-800">
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Net Work Hours</p>
                    <p class="text-sm font-black text-indigo-300 mt-1 font-mono" x-text="activeDetail.net_work"></p>
                    <p class="text-[10px] text-emerald-400 mt-0.5" x-text="'Overtime: ' + activeDetail.overtime"></p>
                </div>
            </div>

            <!-- Deep Telemetry Section: Network, Location & Device -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                
                <!-- 1. Network & Geolocation Audit Card -->
                <div class="p-4 bg-slate-950/90 rounded-2xl border border-slate-800 space-y-3">
                    <div class="flex items-center justify-between border-b border-slate-800/80 pb-2.5">
                        <h4 class="text-xs font-bold text-white flex items-center space-x-2">
                            <i class="fa-solid fa-location-dot text-rose-400"></i>
                            <span>Location & Network Telemetry</span>
                        </h4>
                        <template x-if="activeDetail.location_verified">
                            <span class="px-2 py-0.5 rounded-full bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-[10px] font-bold">
                                <i class="fa-solid fa-circle-check mr-1"></i> Office Verified
                            </span>
                        </template>
                        <template x-if="!activeDetail.location_verified">
                            <span class="px-2 py-0.5 rounded-full bg-slate-800 text-slate-400 text-[10px] font-bold">
                                <i class="fa-solid fa-globe mr-1"></i> Remote Punch
                            </span>
                        </template>
                    </div>

                    <div class="space-y-2 text-xs">
                        <div class="flex items-center justify-between">
                            <span class="text-slate-400 text-[11px]">IP Address:</span>
                            <span class="font-mono bg-slate-900 px-2.5 py-1 rounded-lg text-indigo-300 border border-slate-800 font-bold" x-text="activeDetail.ip_address"></span>
                        </div>

                        <div class="flex items-center justify-between">
                            <span class="text-slate-400 text-[11px]">Punch Location:</span>
                            <span class="font-semibold text-white" x-text="activeDetail.location_name"></span>
                        </div>

                        <div class="flex items-start justify-between">
                            <span class="text-slate-400 text-[11px]">GPS Coordinates:</span>
                            <div class="text-right">
                                <template x-if="activeDetail.has_coordinates">
                                    <span class="font-mono text-emerald-400 text-[11px]" x-text="activeDetail.latitude + ', ' + activeDetail.longitude"></span>
                                </template>
                                <template x-if="!activeDetail.has_coordinates">
                                    <span class="text-slate-500 text-[11px]">Not shared / Geolocation disabled</span>
                                </template>
                            </div>
                        </div>

                        <template x-if="activeDetail.has_coordinates && activeDetail.google_maps_url">
                            <div class="pt-1">
                                <a :href="activeDetail.google_maps_url" target="_blank" class="w-full py-1.5 px-3 bg-emerald-600/15 hover:bg-emerald-600/25 text-emerald-300 border border-emerald-500/30 rounded-xl font-bold text-[11px] transition-all flex items-center justify-center space-x-1.5">
                                    <i class="fa-solid fa-map-location-dot"></i>
                                    <span>Open Exact Coordinates on Google Maps</span>
                                    <i class="fa-solid fa-arrow-up-right-from-square text-[9px]"></i>
                                </a>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- 2. Device, Browser & Environment Card -->
                <div class="p-4 bg-slate-950/90 rounded-2xl border border-slate-800 space-y-3">
                    <div class="flex items-center justify-between border-b border-slate-800/80 pb-2.5">
                        <h4 class="text-xs font-bold text-white flex items-center space-x-2">
                            <i class="fa-solid fa-laptop-code text-indigo-400"></i>
                            <span>Device & Client Environment</span>
                        </h4>
                        <span class="px-2 py-0.5 rounded-full bg-indigo-500/10 text-indigo-300 text-[10px] font-bold uppercase" x-text="activeDetail.device_type"></span>
                    </div>

                    <div class="space-y-2 text-xs">
                        <div class="flex items-center justify-between">
                            <span class="text-slate-400 text-[11px]">Operating System:</span>
                            <span class="font-bold text-white flex items-center space-x-1.5">
                                <i class="fa-solid" :class="activeDetail.device_icon || 'fa-desktop'"></i>
                                <span x-text="activeDetail.device_os"></span>
                            </span>
                        </div>

                        <div class="flex items-center justify-between">
                            <span class="text-slate-400 text-[11px]">Browser:</span>
                            <span class="font-bold text-slate-200" x-text="activeDetail.device_browser"></span>
                        </div>

                        <div>
                            <span class="text-slate-400 text-[11px] block mb-1">User Agent Signature:</span>
                            <div class="text-[10px] font-mono text-slate-400 bg-slate-900/90 p-2 rounded-xl border border-slate-800 break-all leading-relaxed max-h-16 overflow-y-auto" x-text="activeDetail.raw_user_agent"></div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Breaks Timeline / Breakdown (if any) -->
            <div class="p-4 bg-slate-950/90 rounded-2xl border border-slate-800 space-y-3">
                <div class="flex items-center justify-between border-b border-slate-800/80 pb-2.5">
                    <h4 class="text-xs font-bold text-white flex items-center space-x-2">
                        <i class="fa-solid fa-mug-hot text-amber-400"></i>
                        <span>Break Activity on this Date</span>
                    </h4>
                    <span class="text-[11px] text-slate-400 font-semibold" x-text="activeDetail.breaks_count + ' break(s) recorded'"></span>
                </div>

                <template x-if="!activeDetail.breaks || activeDetail.breaks.length === 0">
                    <p class="text-xs text-slate-500 py-2 text-center">No break logs recorded for this attendance shift.</p>
                </template>

                <div class="space-y-2 max-h-44 overflow-y-auto">
                    <template x-for="(brk, bidx) in (activeDetail.breaks || [])" :key="bidx">
                        <div class="p-2.5 rounded-xl border flex items-center justify-between text-xs"
                             :class="brk.is_exceeded ? 'bg-rose-500/10 border-rose-500/30' : 'bg-slate-900 border-slate-800'">
                            <div class="flex items-center space-x-2.5">
                                <div class="w-7 h-7 rounded-lg flex items-center justify-center text-[10px] border"
                                     :class="brk.badge_class || 'bg-slate-800 text-slate-300 border-slate-700'">
                                    <i class="fa-solid" :class="brk.icon || 'fa-pause'"></i>
                                </div>
                                <div>
                                    <div class="flex items-center space-x-1.5">
                                        <p class="font-bold text-white text-[11px]" x-text="brk.break_type + ' Break'"></p>
                                        <template x-if="brk.is_exceeded">
                                            <span class="px-1.5 py-0.2 rounded text-[9px] font-extrabold bg-rose-500/20 text-rose-400 border border-rose-500/30">
                                                +<span x-text="brk.exceeded_minutes"></span>m Exceeded
                                            </span>
                                        </template>
                                    </div>
                                    <p class="text-[10px] text-slate-400">
                                        <span x-text="brk.start_time"></span> &rarr; <span x-text="brk.end_time"></span>
                                    </p>
                                </div>
                            </div>

                            <span class="px-2 py-0.5 rounded-lg text-xs font-mono font-bold"
                                  :class="brk.is_exceeded ? 'bg-rose-500/20 text-rose-400' : 'bg-slate-800 text-slate-200'"
                                  x-text="brk.duration"></span>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Remarks & System Approval Audit -->
            <div class="p-3.5 bg-slate-950/60 rounded-2xl border border-slate-800/80 flex flex-col sm:flex-row sm:items-center justify-between gap-2 text-xs">
                <div>
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Remarks / Note:</span>
                    <p class="text-xs text-slate-300 mt-0.5" x-text="activeDetail.remarks"></p>
                </div>
                <div class="text-left sm:text-right shrink-0">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Record UUID</span>
                    <p class="text-[10px] font-mono text-slate-400 mt-0.5" x-text="activeDetail.id"></p>
                </div>
            </div>

            <!-- Footer Action Buttons -->
            <div class="pt-2 flex justify-end space-x-3">
                <button type="button" @click="detailModal = false" class="px-5 py-2 bg-slate-800 hover:bg-slate-700 text-slate-200 font-bold text-xs rounded-xl transition-all">
                    Close Details
                </button>
            </div>

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
                    <p class="text-xs text-slate-400 mt-0.5" x-text="breakLogData.employee_name + ' &bull; ' + breakLogData.date"></p>
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

    <!-- Manual Record Attendance Modal (Admin) -->
    <div x-show="recordModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/75 backdrop-blur-md">
        <div @click.away="recordModal = false" class="bg-slate-900 border border-slate-800 rounded-3xl max-w-lg w-full p-6 space-y-5 shadow-2xl">
            <div class="flex items-center justify-between border-b border-slate-800 pb-4">
                <h3 class="text-base font-bold text-white">Record Employee Attendance</h3>
                <button @click="recordModal = false" class="text-slate-400 hover:text-white"><i class="fa-solid fa-xmark"></i></button>
            </div>

            <form action="{{ route('attendance.store') }}" method="POST" class="space-y-4 text-xs">
                @csrf
                <div>
                    <label class="block font-semibold text-slate-300 mb-1">Target Employee *</label>
                    <select name="user_id" required class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white">
                        <option value="">-- Select Employee --</option>
                        @foreach($employees as $emp)
                            <option value="{{ $emp->id }}">{{ $emp->name }} ({{ $emp->organization->name ?? 'General' }})</option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-semibold text-slate-300 mb-1">Date *</label>
                        <input type="date" name="date" required value="{{ date('Y-m-d') }}" class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white">
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-300 mb-1">Attendance Status *</label>
                        <select name="status" required class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white">
                            <option value="PRESENT">PRESENT</option>
                            <option value="LATE">LATE</option>
                            <option value="HALF_DAY">HALF DAY</option>
                            <option value="ABSENT">ABSENT</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-semibold text-slate-300 mb-1">Clock In Time *</label>
                        <input type="time" name="clock_in_time" required value="09:00" class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white">
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-300 mb-1">Clock Out Time</label>
                        <input type="time" name="clock_out_time" value="17:00" class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white">
                    </div>
                </div>

                <div>
                    <label class="block font-semibold text-slate-300 mb-1">Remarks / Note</label>
                    <textarea name="remarks" rows="2" placeholder="Manual log or supervisor note..." class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white focus:outline-none focus:border-indigo-500"></textarea>
                </div>

                <div class="pt-3 flex justify-end space-x-3">
                    <button type="button" @click="recordModal = false" class="px-4 py-2 bg-slate-800 text-slate-300 font-semibold rounded-xl">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white font-bold rounded-xl shadow-lg">Save Attendance</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Attendance Modal (Admin) -->
    <div x-show="editModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/75 backdrop-blur-md">
        <div @click.away="editModal = false" class="bg-slate-900 border border-slate-800 rounded-3xl max-w-lg w-full p-6 space-y-5 shadow-2xl">
            <div class="flex items-center justify-between border-b border-slate-800 pb-4">
                <h3 class="text-base font-bold text-white">Edit Attendance (<span x-text="activeAtt.userName"></span>)</h3>
                <button @click="editModal = false" class="text-slate-400 hover:text-white"><i class="fa-solid fa-xmark"></i></button>
            </div>

            <form :action="'{{ url('attendance') }}/' + activeAtt.id" method="POST" class="space-y-4 text-xs">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-semibold text-slate-300 mb-1">Date</label>
                        <input type="text" readonly x-model="activeAtt.date" class="w-full px-3 py-2 bg-slate-950/60 border border-slate-800 rounded-xl text-slate-400">
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-300 mb-1">Status *</label>
                        <select name="status" x-model="activeAtt.status" required class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white">
                            <option value="PRESENT">PRESENT</option>
                            <option value="LATE">LATE</option>
                            <option value="HALF_DAY">HALF DAY</option>
                            <option value="ABSENT">ABSENT</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-semibold text-slate-300 mb-1">Clock In Time *</label>
                        <input type="time" name="clock_in_time" x-model="activeAtt.clock_in_time" required class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white">
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-300 mb-1">Clock Out Time</label>
                        <input type="time" name="clock_out_time" x-model="activeAtt.clock_out_time" class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white">
                    </div>
                </div>

                <div>
                    <label class="block font-semibold text-slate-300 mb-1">Remarks</label>
                    <textarea name="remarks" x-model="activeAtt.remarks" rows="2" class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white focus:outline-none focus:border-indigo-500"></textarea>
                </div>

                <div class="pt-3 flex justify-end space-x-3">
                    <button type="button" @click="editModal = false" class="px-4 py-2 bg-slate-800 text-slate-300 font-semibold rounded-xl">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white font-bold rounded-xl shadow-lg">Update Log</button>
                </div>
            </form>
        </div>
    </div>

</div>

@endsection

