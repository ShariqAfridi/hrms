@extends('layouts.app')

@section('title', 'Admin Area - System Settings & Configuration Hub')

@section('content')
<div x-data="settingsManager('{{ $activeTab ?? 'general' }}')" class="space-y-6">

    <!-- Header Banner -->
    <div class="bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 border border-slate-800 rounded-3xl p-6 lg:p-8 shadow-2xl relative overflow-hidden">
        <div class="absolute top-0 right-0 w-96 h-96 bg-indigo-600/10 rounded-full blur-3xl pointer-events-none"></div>
        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="flex items-center space-x-4">
                <div class="w-14 h-14 rounded-2xl bg-indigo-600/20 border border-indigo-500/30 flex items-center justify-center text-indigo-400 text-2xl font-bold shadow-lg">
                    <i class="fa-solid fa-sliders"></i>
                </div>
                <div>
                    <h2 class="text-2xl font-extrabold text-white tracking-tight">System Settings & Structure Control Hub</h2>
                    <p class="text-sm text-slate-400 mt-1">Manage global parameters, company departments, employee designations, payroll rules (PKR), and attendance thresholds.</p>
                </div>
            </div>
            <div class="flex items-center space-x-2">
                <button x-show="activeTab === 'departments'" @click="addDeptModalOpen = true" class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs rounded-xl shadow-lg shadow-indigo-600/30 transition-all flex items-center space-x-2">
                    <i class="fa-solid fa-plus"></i>
                    <span>+ New Department</span>
                </button>
                <button x-show="activeTab === 'designations'" @click="addDesigModalOpen = true" class="px-4 py-2.5 bg-purple-600 hover:bg-purple-500 text-white font-bold text-xs rounded-xl shadow-lg shadow-purple-600/30 transition-all flex items-center space-x-2">
                    <i class="fa-solid fa-plus"></i>
                    <span>+ New Designation</span>
                </button>
                <button x-show="activeTab === 'shifts'" @click="addShiftModalOpen = true" class="px-4 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs rounded-xl shadow-lg shadow-emerald-600/30 transition-all flex items-center space-x-2">
                    <i class="fa-solid fa-plus"></i>
                    <span>+ New Work Shift</span>
                </button>
                @developer
                <button x-show="activeTab === 'features'" @click="addFeatureModalOpen = true" class="px-4 py-2.5 bg-gradient-to-r from-amber-500 to-orange-600 hover:from-amber-400 hover:to-orange-500 text-slate-950 font-black text-xs rounded-xl shadow-lg shadow-amber-500/30 transition-all flex items-center space-x-2">
                    <i class="fa-solid fa-plus"></i>
                    <span>+ Register Feature Flag</span>
                </button>
                @enddeveloper
                <span x-show="activeTab !== 'departments' && activeTab !== 'designations' && activeTab !== 'shifts' && activeTab !== 'features'" class="px-3.5 py-1.5 rounded-full text-xs font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 inline-flex items-center space-x-2">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                    <span>System Active</span>
                </span>
            </div>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <div class="bg-slate-950/90 border border-slate-800/80 rounded-2xl p-2 flex flex-wrap items-center gap-2 shadow-lg">
        @developer
        <button @click="activeTab = 'features'" :class="activeTab === 'features' ? 'bg-gradient-to-r from-amber-500 to-orange-600 text-slate-950 font-black shadow-lg shadow-amber-500/20' : 'text-amber-400 hover:text-amber-300 hover:bg-amber-500/10 border border-amber-500/20 font-bold'" class="px-4 py-2.5 rounded-xl text-xs transition-all flex items-center space-x-2">
            <i class="fa-solid fa-toggle-on text-sm"></i>
            <span>Feature Switchboard (Dev Only)</span>
            <span class="px-1.5 py-0.5 rounded text-[10px] bg-slate-950/80 text-amber-300 font-extrabold">{{ count($features ?? []) }}</span>
        </button>
        @enddeveloper

        <button @click="activeTab = 'general'" :class="activeTab === 'general' ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/30 font-bold' : 'text-slate-400 hover:text-white hover:bg-slate-900 font-semibold'" class="px-4 py-2.5 rounded-xl text-xs transition-all flex items-center space-x-2">
            <i class="fa-solid fa-building text-sm"></i>
            <span>Company & Localization</span>
        </button>

        <button @click="activeTab = 'departments'" :class="activeTab === 'departments' ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/30 font-bold' : 'text-slate-400 hover:text-white hover:bg-slate-900 font-semibold'" class="px-4 py-2.5 rounded-xl text-xs transition-all flex items-center space-x-2">
            <i class="fa-solid fa-sitemap text-sm"></i>
            <span>Departments ({{ $departments->count() }})</span>
        </button>

        <button @click="activeTab = 'designations'" :class="activeTab === 'designations' ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/30 font-bold' : 'text-slate-400 hover:text-white hover:bg-slate-900 font-semibold'" class="px-4 py-2.5 rounded-xl text-xs transition-all flex items-center space-x-2">
            <i class="fa-solid fa-id-badge text-sm"></i>
            <span>Designations & Titles ({{ $designations->count() }})</span>
        </button>

        <button @click="activeTab = 'shifts'" :class="activeTab === 'shifts' ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/30 font-bold' : 'text-slate-400 hover:text-white hover:bg-slate-900 font-semibold'" class="px-4 py-2.5 rounded-xl text-xs transition-all flex items-center space-x-2">
            <i class="fa-solid fa-business-time text-sm"></i>
            <span>Work Shifts ({{ $shifts->count() }})</span>
        </button>

        @feature('attendance')
        <button @click="activeTab = 'attendance'" :class="activeTab === 'attendance' ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/30 font-bold' : 'text-slate-400 hover:text-white hover:bg-slate-900 font-semibold'" class="px-4 py-2.5 rounded-xl text-xs transition-all flex items-center space-x-2">
            <i class="fa-solid fa-clock text-sm"></i>
            <span>Attendance & Rules</span>
        </button>
        @endfeature

        @feature('payroll')
        <button @click="activeTab = 'payroll'" :class="activeTab === 'payroll' ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/30 font-bold' : 'text-slate-400 hover:text-white hover:bg-slate-900 font-semibold'" class="px-4 py-2.5 rounded-xl text-xs transition-all flex items-center space-x-2">
            <i class="fa-solid fa-file-invoice-dollar text-sm"></i>
            <span>Payroll & Finance</span>
        </button>
        @endfeature

        @feature('leaves')
        <button @click="activeTab = 'leave'" :class="activeTab === 'leave' ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/30 font-bold' : 'text-slate-400 hover:text-white hover:bg-slate-900 font-semibold'" class="px-4 py-2.5 rounded-xl text-xs transition-all flex items-center space-x-2">
            <i class="fa-solid fa-calendar-check text-sm"></i>
            <span>Leave Quotas</span>
        </button>
        @endfeature

        @php
            $currSettingsUser = auth()->user();
            $isAiAdminAuthorized = $currSettingsUser && (
                (method_exists($currSettingsUser, 'isSuperAdmin') && $currSettingsUser->isSuperAdmin()) ||
                (method_exists($currSettingsUser, 'isDeveloperAdmin') && $currSettingsUser->isDeveloperAdmin())
            );
        @endphp
        @if($isAiAdminAuthorized)
        <button @click="activeTab = 'ai_copilot'" :class="activeTab === 'ai_copilot' ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/30 font-bold' : 'text-slate-400 hover:text-white hover:bg-slate-900 font-semibold'" class="px-4 py-2.5 rounded-xl text-xs transition-all flex items-center space-x-2">
            <i class="fa-solid fa-robot text-sm"></i>
            <span>AI Copilot</span>
        </button>
        @endif
    </div>

    <!-- TAB: DEPARTMENTS MANAGEMENT -->
    <div x-show="activeTab === 'departments'" class="space-y-6" x-cloak>
        <div class="bg-slate-950/70 border border-slate-800 rounded-3xl p-6 shadow-xl">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-6 border-b border-slate-800">
                <div>
                    <h3 class="text-lg font-bold text-white flex items-center space-x-2">
                        <i class="fa-solid fa-sitemap text-indigo-400"></i>
                        <span>Company Departments Directory</span>
                    </h3>
                    <p class="text-xs text-slate-400 mt-1">Manage organizational divisions, parent department hierarchy, and department leads.</p>
                </div>

                <button @click="addDeptModalOpen = true" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs rounded-xl shadow-lg shadow-indigo-600/30 transition-all flex items-center space-x-2 shrink-0">
                    <i class="fa-solid fa-plus"></i>
                    <span>+ Add New Department</span>
                </button>
            </div>

            <div class="overflow-x-auto mt-4">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="bg-slate-900/60 border-b border-slate-800 text-[11px] font-bold text-slate-400 uppercase tracking-wider">
                            <th class="py-3.5 px-4">Department</th>
                            <th class="py-3.5 px-4">Code</th>
                            <th class="py-3.5 px-4">Company / Organization</th>
                            <th class="py-3.5 px-4">Parent Department</th>
                            <th class="py-3.5 px-4">Head of Department</th>
                            <th class="py-3.5 px-4 text-center">Staff Count</th>
                            <th class="py-3.5 px-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60">
                        @forelse($departments as $dept)
                            <tr class="hover:bg-slate-900/30 transition-all">
                                <td class="py-4 px-4 font-bold text-white">
                                    <div class="flex items-center space-x-2.5">
                                        <div class="w-7 h-7 rounded-lg bg-indigo-500/10 border border-indigo-500/20 text-indigo-400 flex items-center justify-center font-bold">
                                            <i class="fa-solid fa-folder-tree text-xs"></i>
                                        </div>
                                        <span>{{ $dept->name }}</span>
                                    </div>
                                </td>
                                <td class="py-4 px-4 font-mono text-indigo-400 font-semibold">
                                    {{ $dept->code ?: 'N/A' }}
                                </td>
                                <td class="py-4 px-4 text-slate-300">
                                    <span class="px-2.5 py-1 rounded-lg bg-slate-900 border border-slate-800 text-[11px]">
                                        <i class="fa-solid fa-building text-slate-500 mr-1"></i>{{ $dept->organization->name ?? 'Global' }}
                                    </span>
                                </td>
                                <td class="py-4 px-4 text-slate-400">
                                    {{ $dept->parentDepartment->name ?? '-- Top Level --' }}
                                </td>
                                <td class="py-4 px-4 text-slate-300 font-medium">
                                    @if($dept->headUser)
                                        <div class="flex items-center space-x-1.5">
                                            <i class="fa-solid fa-user-tie text-slate-400 text-xs"></i>
                                            <span>{{ $dept->headUser->name }}</span>
                                        </div>
                                    @else
                                        <span class="text-slate-500 italic">Not Assigned</span>
                                    @endif
                                </td>
                                <td class="py-4 px-4 text-center">
                                    <span class="px-2.5 py-0.5 rounded-full bg-indigo-500/10 text-indigo-400 font-bold text-[11px]">
                                        {{ $dept->users_count }} Staff
                                    </span>
                                </td>
                                <td class="py-4 px-4 text-right">
                                    <div class="flex items-center justify-end space-x-1">
                                        <button @click="openEditDept({{ json_encode($dept) }})" class="p-1.5 text-slate-400 hover:text-white rounded-lg hover:bg-slate-800 transition-colors" title="Edit Department">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>
                                        <form action="{{ route('settings.departments.destroy', $dept->id) }}" method="POST" onsubmit="return confirm('Delete department \'{{ addslashes($dept->name) }}\'?');" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="p-1.5 text-slate-500 hover:text-rose-400 rounded-lg hover:bg-rose-500/10 transition-colors" title="Delete Department">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-8 text-center text-slate-500">No departments found. Click '+ Add New Department' to register one.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- TAB: DESIGNATIONS MANAGEMENT -->
    <div x-show="activeTab === 'designations'" class="space-y-6" x-cloak>
        <div class="bg-slate-950/70 border border-slate-800 rounded-3xl p-6 shadow-xl">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-6 border-b border-slate-800">
                <div>
                    <h3 class="text-lg font-bold text-white flex items-center space-x-2">
                        <i class="fa-solid fa-id-badge text-purple-400"></i>
                        <span>Employee Designations & Job Titles</span>
                    </h3>
                    <p class="text-xs text-slate-400 mt-1">Configure professional designations (e.g. Senior Full Stack Engineer, UI/UX Designer, QA Lead) across companies.</p>
                </div>

                <button @click="addDesigModalOpen = true" class="px-4 py-2 bg-purple-600 hover:bg-purple-500 text-white font-bold text-xs rounded-xl shadow-lg shadow-purple-600/30 transition-all flex items-center space-x-2 shrink-0">
                    <i class="fa-solid fa-plus"></i>
                    <span>+ Add New Designation</span>
                </button>
            </div>

            <div class="overflow-x-auto mt-4">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="bg-slate-900/60 border-b border-slate-800 text-[11px] font-bold text-slate-400 uppercase tracking-wider">
                            <th class="py-3.5 px-4">Designation Title</th>
                            <th class="py-3.5 px-4">Designation Code</th>
                            <th class="py-3.5 px-4">Company / Organization</th>
                            <th class="py-3.5 px-4 text-center">Active Employees</th>
                            <th class="py-3.5 px-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60">
                        @forelse($designations as $desig)
                            <tr class="hover:bg-slate-900/30 transition-all">
                                <td class="py-4 px-4 font-bold text-white">
                                    <div class="flex items-center space-x-2.5">
                                        <div class="w-7 h-7 rounded-lg bg-purple-500/10 border border-purple-500/20 text-purple-400 flex items-center justify-center font-bold">
                                            <i class="fa-solid fa-briefcase text-xs"></i>
                                        </div>
                                        <span>{{ $desig->title }}</span>
                                    </div>
                                </td>
                                <td class="py-4 px-4 font-mono text-purple-400 font-semibold">
                                    {{ $desig->code }}
                                </td>
                                <td class="py-4 px-4 text-slate-300">
                                    <span class="px-2.5 py-1 rounded-lg bg-slate-900 border border-slate-800 text-[11px]">
                                        <i class="fa-solid fa-building text-slate-500 mr-1"></i>{{ $desig->organization->name ?? 'Global' }}
                                    </span>
                                </td>
                                <td class="py-4 px-4 text-center">
                                    <span class="px-2.5 py-0.5 rounded-full bg-purple-500/10 text-purple-400 font-bold text-[11px]">
                                        {{ $desig->users_count }} Employees
                                    </span>
                                </td>
                                <td class="py-4 px-4 text-right">
                                    <div class="flex items-center justify-end space-x-1">
                                        <button @click="openEditDesig({{ json_encode($desig) }})" class="p-1.5 text-slate-400 hover:text-white rounded-lg hover:bg-slate-800 transition-colors" title="Edit Designation">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>
                                        <form action="{{ route('settings.designations.destroy', $desig->id) }}" method="POST" onsubmit="return confirm('Delete designation \'{{ addslashes($desig->title) }}\'?');" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="p-1.5 text-slate-500 hover:text-rose-400 rounded-lg hover:bg-rose-500/10 transition-colors" title="Delete Designation">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-8 text-center text-slate-500">No designations found. Click '+ Add New Designation' to register one.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- TAB: WORK SHIFTS MANAGEMENT -->
    <div x-show="activeTab === 'shifts'" class="space-y-6" x-cloak>
        <div class="bg-slate-950/70 border border-slate-800 rounded-3xl p-6 shadow-xl">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-6 border-b border-slate-800">
                <div>
                    <h3 class="text-lg font-bold text-white flex items-center space-x-2">
                        <i class="fa-solid fa-business-time text-emerald-400"></i>
                        <span>Work Shifts & Operating Schedules</span>
                    </h3>
                    <p class="text-xs text-slate-400 mt-1">Configure company work shifts (Morning, Evening, Night, Flexible) with designated start and end timings.</p>
                </div>

                <button @click="addShiftModalOpen = true" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs rounded-xl shadow-lg shadow-emerald-600/30 transition-all flex items-center space-x-2 shrink-0">
                    <i class="fa-solid fa-plus"></i>
                    <span>+ Add New Shift</span>
                </button>
            </div>

            <div class="overflow-x-auto mt-4">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="bg-slate-900/60 border-b border-slate-800 text-[11px] font-bold text-slate-400 uppercase tracking-wider">
                            <th class="py-3.5 px-4">Shift Name</th>
                            <th class="py-3.5 px-4">Company / Organization</th>
                            <th class="py-3.5 px-4">Operating Hours</th>
                            <th class="py-3.5 px-4">Type</th>
                            <th class="py-3.5 px-4 text-center">Assigned Staff</th>
                            <th class="py-3.5 px-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60">
                        @forelse($shifts as $shift)
                            @php
                                $startTime = strtotime($shift->start_time);
                                $endTime = strtotime($shift->end_time);
                                $diffMinutes = ($endTime >= $startTime) ? ($endTime - $startTime) / 60 : (($endTime + 86400) - $startTime) / 60;
                                $diffHours = round($diffMinutes / 60, 1);
                            @endphp
                            <tr class="hover:bg-slate-900/30 transition-all">
                                <td class="py-4 px-4 font-bold text-white">
                                    <div class="flex items-center space-x-2.5">
                                        <div class="w-7 h-7 rounded-lg bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 flex items-center justify-center font-bold">
                                            <i class="fa-solid fa-clock text-xs"></i>
                                        </div>
                                        <span>{{ $shift->name }}</span>
                                    </div>
                                </td>
                                <td class="py-4 px-4 text-slate-300">
                                    <span class="px-2.5 py-1 rounded-lg bg-slate-900 border border-slate-800 text-[11px]">
                                        <i class="fa-solid fa-building text-slate-500 mr-1"></i>{{ $shift->organization->name ?? 'Global' }}
                                    </span>
                                </td>
                                <td class="py-4 px-4 text-slate-300">
                                    <div class="inline-flex items-center space-x-2 px-2.5 py-1 rounded-lg bg-slate-900 border border-slate-800 font-mono text-[11px] text-emerald-400">
                                        <i class="fa-regular fa-clock text-slate-500"></i>
                                        <span>{{ date('h:i A', strtotime($shift->start_time)) }} &mdash; {{ date('h:i A', strtotime($shift->end_time)) }}</span>
                                        <span class="text-slate-400 text-[10px]">({{ $diffHours }}h)</span>
                                    </div>
                                </td>
                                <td class="py-4 px-4">
                                    @if($shift->flexible)
                                        <span class="px-2.5 py-1 rounded-full bg-blue-500/10 text-blue-400 border border-blue-500/20 font-bold text-[10px]">
                                            <i class="fa-solid fa-shuffle mr-1"></i>Flexible Hours
                                        </span>
                                    @else
                                        <span class="px-2.5 py-1 rounded-full bg-slate-800 text-slate-300 border border-slate-700 font-medium text-[10px]">
                                            <i class="fa-solid fa-lock mr-1 text-slate-400"></i>Fixed Schedule
                                        </span>
                                    @endif
                                </td>
                                <td class="py-4 px-4 text-center">
                                    <span class="px-2.5 py-0.5 rounded-full bg-emerald-500/10 text-emerald-400 font-bold text-[11px]">
                                        {{ $shift->users_count }} Staff
                                    </span>
                                </td>
                                <td class="py-4 px-4 text-right">
                                    <div class="flex items-center justify-end space-x-1">
                                        <button @click="openEditShift({{ json_encode($shift) }})" class="p-1.5 text-slate-400 hover:text-white rounded-lg hover:bg-slate-800 transition-colors" title="Edit Shift">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>
                                        <form action="{{ route('settings.shifts.destroy', $shift->id) }}" method="POST" onsubmit="return confirm('Delete shift \'{{ addslashes($shift->name) }}\'? Assigned employees will be unassigned from this shift.');" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="p-1.5 text-slate-500 hover:text-rose-400 rounded-lg hover:bg-rose-500/10 transition-colors" title="Delete Shift">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-8 text-center text-slate-500">No shifts registered yet. Click '+ Add New Shift' to define your company shifts.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Main System Settings Form (for general, attendance, payroll, leave, ai_copilot tabs) -->
    <form x-show="activeTab !== 'departments' && activeTab !== 'designations' && activeTab !== 'shifts'" action="{{ route('settings.update') }}" method="POST">
        @csrf
        <input type="hidden" name="current_tab" :value="activeTab">

        <!-- TAB 1: Company & Localization -->
        <div x-show="activeTab === 'general'" class="bg-slate-950 border border-slate-800 rounded-3xl p-6 lg:p-8 space-y-6 shadow-xl">
            <div class="border-b border-slate-800/80 pb-4">
                <h3 class="text-base font-bold text-white flex items-center space-x-2">
                    <i class="fa-solid fa-globe text-indigo-400"></i>
                    <span>Company & Localization Settings</span>
                </h3>
                <p class="text-xs text-slate-400 mt-1">Manage standard currency, tax percentage, timezone, and legal branding.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                @foreach($settings->get('general', []) as $s)
                    <div class="space-y-2 bg-slate-900/50 border border-slate-800/80 p-4 rounded-2xl">
                        <label class="block text-xs font-bold text-slate-200">{{ $s->label }}</label>
                        <p class="text-[11px] text-slate-400 font-medium">{{ $s->description }}</p>
                        
                        @if($s->type === 'number')
                            <input type="number" step="any" name="settings[{{ $s->key }}]" value="{{ $s->value }}" class="w-full px-4 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-xs text-white focus:outline-none focus:border-indigo-500">
                        @else
                            <input type="text" name="settings[{{ $s->key }}]" value="{{ $s->value }}" class="w-full px-4 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-xs text-white focus:outline-none focus:border-indigo-500">
                        @endif
                    </div>
                @endforeach
            </div>
        </div>

        <!-- TAB 2: Attendance & Shift Rules -->
        @feature('attendance')
        <div x-show="activeTab === 'attendance'" class="bg-slate-950 border border-slate-800 rounded-3xl p-6 lg:p-8 space-y-6 shadow-xl" x-cloak>
            <div class="border-b border-slate-800/80 pb-4">
                <h3 class="text-base font-bold text-white flex items-center space-x-2">
                    <i class="fa-solid fa-user-clock text-indigo-400"></i>
                    <span>Attendance & Shift Configuration</span>
                </h3>
                <p class="text-xs text-slate-400 mt-1">Set grace periods, shift duration, overtime trigger limits, and geofencing radius.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                @foreach($settings->get('attendance', []) as $s)
                    <div class="space-y-2 bg-slate-900/50 border border-slate-800/80 p-4 rounded-2xl">
                        <label class="block text-xs font-bold text-slate-200">{{ $s->label }}</label>
                        <p class="text-[11px] text-slate-400 font-medium">{{ $s->description }}</p>
                        
                        @if($s->type === 'boolean')
                            <select name="settings[{{ $s->key }}]" class="w-full px-4 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-xs text-white focus:outline-none focus:border-indigo-500">
                                <option value="1" {{ $s->value == '1' ? 'selected' : '' }}>Enabled (Strict Verification)</option>
                                <option value="0" {{ $s->value == '0' ? 'selected' : '' }}>Disabled (Flexible / Standard)</option>
                            </select>
                        @elseif($s->type === 'number')
                            <input type="number" step="any" name="settings[{{ $s->key }}]" value="{{ $s->value }}" class="w-full px-4 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-xs text-white focus:outline-none focus:border-indigo-500">
                        @else
                            <input type="text" name="settings[{{ $s->key }}]" value="{{ $s->value }}" class="w-full px-4 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-xs text-white focus:outline-none focus:border-indigo-500">
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
        @endfeature

        <!-- TAB 3: Payroll & Finance Rules -->
        @feature('payroll')
        <div x-show="activeTab === 'payroll'" class="bg-slate-950 border border-slate-800 rounded-3xl p-6 lg:p-8 space-y-6 shadow-xl" x-cloak>
            <div class="border-b border-slate-800/80 pb-4">
                <h3 class="text-base font-bold text-white flex items-center space-x-2">
                    <i class="fa-solid fa-file-invoice-dollar text-indigo-400"></i>
                    <span>Multi-Company Payroll & Financial Parameters</span>
                </h3>
                <p class="text-xs text-slate-400 mt-1">Configure default working days, late arrival deduction rules (3 days late = 1 day salary deduction), and release approvals.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                @foreach($settings->get('payroll', []) as $s)
                    <div class="space-y-2 bg-slate-900/50 border border-slate-800/80 p-4 rounded-2xl">
                        <label class="block text-xs font-bold text-slate-200">{{ $s->label }}</label>
                        <p class="text-[11px] text-slate-400 font-medium">{{ $s->description }}</p>
                        @if($s->type === 'boolean')
                            <select name="settings[{{ $s->key }}]" class="w-full px-4 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-xs text-white focus:outline-none focus:border-indigo-500">
                                <option value="1" {{ $s->value == '1' ? 'selected' : '' }}>Enabled (Requires Approval)</option>
                                <option value="0" {{ $s->value == '0' ? 'selected' : '' }}>Disabled (Instant Auto Release)</option>
                            </select>
                        @else
                            <input type="number" step="any" name="settings[{{ $s->key }}]" value="{{ $s->value }}" class="w-full px-4 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-xs text-white focus:outline-none focus:border-indigo-500">
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
        @endfeature

        <!-- TAB 4: Leave Quotas & Approval -->
        @feature('leaves')
        <div x-show="activeTab === 'leave'" class="bg-slate-950 border border-slate-800 rounded-3xl p-6 lg:p-8 space-y-6 shadow-xl" x-cloak>
            <div class="border-b border-slate-800/80 pb-4">
                <h3 class="text-base font-bold text-white flex items-center space-x-2">
                    <i class="fa-solid fa-calendar-check text-indigo-400"></i>
                    <span>Leave Allocations & Approval Workflows</span>
                </h3>
                <p class="text-xs text-slate-400 mt-1">Manage yearly leave quotas per employee and approval hierarchy layers.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                @foreach($settings->get('leave', []) as $s)
                    <div class="space-y-2 bg-slate-900/50 border border-slate-800/80 p-4 rounded-2xl">
                        <label class="block text-xs font-bold text-slate-200">{{ $s->label }}</label>
                        <p class="text-[11px] text-slate-400 font-medium">{{ $s->description }}</p>
                        <input type="number" step="any" name="settings[{{ $s->key }}]" value="{{ $s->value }}" class="w-full px-4 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-xs text-white focus:outline-none focus:border-indigo-500">
                    </div>
                @endforeach
            </div>
        </div>
        @endfeature

        <!-- TAB 5: AI Copilot & Monitoring Command Center (Strictly Super Admin & Dev) -->
        @if($isAiAdminAuthorized)
        <div x-show="activeTab === 'ai_copilot'" class="space-y-6" x-cloak>
            
            <!-- Telemetry Metrics KPI Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Card 1: Today's Tokens -->
                <div class="bg-slate-950 border border-slate-800/80 rounded-2xl p-5 shadow-xl relative overflow-hidden">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Today's Tokens</span>
                        <div class="w-8 h-8 rounded-xl bg-indigo-500/10 border border-indigo-500/20 text-indigo-400 flex items-center justify-center text-xs">
                            <i class="fa-solid fa-bolt"></i>
                        </div>
                    </div>
                    <div class="mt-3">
                        <div class="text-2xl font-black text-white tracking-tight">{{ number_format($todayTokens ?? 0) }}</div>
                        <p class="text-[11px] text-slate-400 mt-1">Prompt & response tokens today</p>
                    </div>
                </div>

                <!-- Card 2: Monthly Token Usage -->
                <div class="bg-slate-950 border border-slate-800/80 rounded-2xl p-5 shadow-xl relative overflow-hidden">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">This Month's Tokens</span>
                        <div class="w-8 h-8 rounded-xl bg-purple-500/10 border border-purple-500/20 text-purple-400 flex items-center justify-center text-xs">
                            <i class="fa-solid fa-microchip"></i>
                        </div>
                    </div>
                    <div class="mt-3">
                        <div class="text-2xl font-black text-white tracking-tight">{{ number_format($monthTokens ?? 0) }}</div>
                        <div class="flex items-center space-x-2 mt-1 text-[10px] font-semibold text-slate-400">
                            <span class="text-indigo-400">⚡ Flash: {{ number_format($flashTokens ?? 0) }}</span>
                            <span>•</span>
                            <span class="text-purple-400">🧠 Pro: {{ number_format($proTokens ?? 0) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Card 3: Estimated Cost -->
                <div class="bg-slate-950 border border-slate-800/80 rounded-2xl p-5 shadow-xl relative overflow-hidden">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Est. Monthly Cost</span>
                        <div class="w-8 h-8 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 flex items-center justify-center text-xs">
                            <i class="fa-solid fa-coins"></i>
                        </div>
                    </div>
                    <div class="mt-3">
                        <div class="text-2xl font-black text-emerald-400 tracking-tight">${{ number_format($monthCostUsd ?? 0, 4) }} <span class="text-xs text-slate-400 font-normal">USD</span></div>
                        <p class="text-[11px] text-slate-400 mt-1">≈ PKR {{ number_format($monthCostPkr ?? 0, 0) }} (Micro-metered)</p>
                    </div>
                </div>

                <!-- Card 4: Engine Health & Latency -->
                <div class="bg-slate-950 border border-slate-800/80 rounded-2xl p-5 shadow-xl relative overflow-hidden">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">API Health & Queries</span>
                        <div class="w-8 h-8 rounded-xl bg-sky-500/10 border border-sky-500/20 text-sky-400 flex items-center justify-center text-xs">
                            <i class="fa-solid fa-heart-pulse"></i>
                        </div>
                    </div>
                    <div class="mt-3">
                        <div class="text-2xl font-black text-white tracking-tight flex items-center space-x-2">
                            <span>{{ !empty($avgLatencyMs) ? $avgLatencyMs . 'ms' : 'Ready' }}</span>
                            <span class="inline-block w-2.5 h-2.5 rounded-full bg-emerald-400 animate-pulse"></span>
                        </div>
                        <p class="text-[11px] text-slate-400 mt-1">{{ number_format($totalAiQueries ?? 0) }} total inquiries audited</p>
                    </div>
                </div>
            </div>

            <!-- Main Configuration Card -->
            <div class="bg-slate-950 border border-slate-800 rounded-3xl p-6 lg:p-8 space-y-6 shadow-xl">
                <div class="border-b border-slate-800/80 pb-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div>
                        <h3 class="text-base font-bold text-white flex items-center space-x-2">
                            <i class="fa-solid fa-wand-magic-sparkles text-indigo-400"></i>
                            <span>AI Copilot & Scanner Configuration</span>
                        </h3>
                        <p class="text-xs text-slate-400 mt-1">Configure Gemini models, secret API keys, deterministic temperature, and token thresholds.</p>
                    </div>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-[11px] font-extrabold bg-indigo-500/10 text-indigo-300 border border-indigo-500/30">
                        Dual-Model: Flash & Pro
                    </span>
                </div>

                <!-- Master AI Copilot Switch -->
                <div class="bg-slate-900/60 border border-slate-800 p-5 rounded-2xl flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div class="flex items-center space-x-3.5">
                        <div class="w-10 h-10 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 flex items-center justify-center text-lg shrink-0">
                            <i class="fa-solid fa-power-off"></i>
                        </div>
                        <div>
                            <h4 class="text-xs font-extrabold text-white flex items-center space-x-2">
                                <span>Master AI Copilot Switch</span>
                                <span class="px-2 py-0.5 rounded-full text-[9px] font-bold {{ (is_feature_raw_enabled('ai_copilot') || \App\Models\SystemSetting::get('ai_copilot_enabled', '1') == '1') ? 'bg-emerald-500/20 text-emerald-400' : 'bg-rose-500/20 text-rose-400' }}">
                                    {{ (is_feature_raw_enabled('ai_copilot') || \App\Models\SystemSetting::get('ai_copilot_enabled', '1') == '1') ? 'ACTIVE' : 'DISABLED' }}
                                </span>
                            </h4>
                            <p class="text-[11px] text-slate-400 mt-0.5">When active, the floating AI Copilot button and Ctrl+K search bar are live in the header for Super Admin and Authorized Staff.</p>
                        </div>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer shrink-0">
                        <input type="hidden" name="settings[feature_ai_copilot]" value="0">
                        <input type="hidden" name="settings[ai_copilot_enabled]" value="0">
                        <input type="checkbox" 
                               name="settings[feature_ai_copilot]" 
                               value="1" 
                               class="sr-only peer" 
                               {{ (is_feature_raw_enabled('ai_copilot') || \App\Models\SystemSetting::get('ai_copilot_enabled', '1') == '1') ? 'checked' : '' }}
                               onchange="document.getElementById('ai_copilot_enabled_hidden').value = this.checked ? '1' : '0'">
                        <input type="hidden" id="ai_copilot_enabled_hidden" name="settings[ai_copilot_enabled]" value="{{ (is_feature_raw_enabled('ai_copilot') || \App\Models\SystemSetting::get('ai_copilot_enabled', '1') == '1') ? '1' : '0' }}">
                        <div class="w-12 h-6 bg-slate-800 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-600 shadow-inner"></div>
                    </label>
                </div>

                <!-- Live Gemini API Key & Connection Tester -->
                <div class="bg-gradient-to-r from-indigo-950/30 via-slate-900 to-slate-900 border border-indigo-500/30 rounded-2xl p-5 space-y-4">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <div class="space-y-1">
                            <h4 class="text-xs font-bold text-white flex items-center space-x-2">
                                <i class="fa-solid fa-key text-indigo-400"></i>
                                <span>Google Gemini API Key & Connectivity Check</span>
                            </h4>
                            <p class="text-[11px] text-slate-400">Test live roundtrip connection to Google Gemini API servers to verify key validity and response latency.</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <button type="button" id="testGeminiBtn" onclick="testGeminiApiConnection()" class="px-4 py-2 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-500 hover:to-purple-500 text-white font-bold text-xs rounded-xl shadow transition-all flex items-center space-x-1.5 shrink-0">
                                <i class="fa-solid fa-wand-magic-sparkles"></i>
                                <span>Test Gemini Connection</span>
                            </button>
                        </div>
                    </div>
                    <div id="geminiTestResult" class="hidden text-xs"></div>
                </div>

                <!-- Parameters Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- API Key -->
                    <div class="space-y-2 bg-slate-900/50 border border-slate-800/80 p-4 rounded-2xl" x-data="{ showKey: false }">
                        <div class="flex items-center justify-between">
                            <label class="block text-xs font-bold text-slate-200">Google Gemini API Key</label>
                            <button type="button" @click="showKey = !showKey" class="text-[11px] text-indigo-400 hover:text-indigo-300 font-semibold flex items-center space-x-1">
                                <i class="fa-solid" :class="showKey ? 'fa-eye-slash' : 'fa-eye'"></i>
                                <span x-text="showKey ? 'Hide Key' : 'Reveal'"></span>
                            </button>
                        </div>
                        <p class="text-[11px] text-slate-400 font-medium">Your private key from Google AI Studio. Can also be set via .env as GEMINI_API_KEY.</p>
                        <input :type="showKey ? 'text' : 'password'" name="settings[gemini_api_key]" value="{{ $settings->get('ai_copilot', collect())->firstWhere('key', 'gemini_api_key')?->value ?? '' }}" placeholder="AIzaSy..." class="w-full px-4 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-xs text-white focus:outline-none focus:border-indigo-500 font-mono">
                    </div>

                    <!-- Default Model -->
                    <div class="space-y-2 bg-slate-900/50 border border-slate-800/80 p-4 rounded-2xl">
                        <label class="block text-xs font-bold text-slate-200">Default AI Model</label>
                        <p class="text-[11px] text-slate-400 font-medium">Select default model. Super Admin can also switch models on-the-fly in chat.</p>
                        @php $currentModel = $settings->get('ai_copilot', collect())->firstWhere('key', 'gemini_default_model')?->value ?? 'gemini-3.6-flash'; @endphp
                        <select name="settings[gemini_default_model]" class="w-full px-4 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-xs text-white focus:outline-none focus:border-indigo-500">
                            <option value="gemini-3.6-flash" {{ in_array($currentModel, ['gemini-3.6-flash', 'gemini-flash-latest', 'gemini-1.5-flash', 'gemini-2.0-flash', 'gemini-2.5-flash']) ? 'selected' : '' }}>⚡ Gemini 3.6 Flash (High-Speed, Accurate & Cost-Efficient - Recommended)</option>
                            <option value="gemini-3.5-flash-lite" {{ $currentModel === 'gemini-3.5-flash-lite' ? 'selected' : '' }}>⚡ Gemini 3.5 Flash-Lite (Ultra Fast & Lightweight)</option>
                            <option value="gemini-pro-latest" {{ in_array($currentModel, ['gemini-pro-latest', 'gemini-1.5-pro', 'gemini-2.5-pro', 'gemini-3.1-pro-preview']) ? 'selected' : '' }}>🧠 Gemini Pro (Deep Analytical Reasoning - Paid Tier)</option>
                        </select>
                    </div>

                    <!-- Temperature -->
                    <div class="space-y-2 bg-slate-900/50 border border-slate-800/80 p-4 rounded-2xl">
                        <label class="block text-xs font-bold text-slate-200">Model Temperature (Sharpness / Determinism)</label>
                        <p class="text-[11px] text-slate-400 font-medium">Lower values (0.0 - 0.2) ensure strictly factual, zero-hallucination, razor-sharp responses.</p>
                        <input type="number" step="0.05" min="0" max="1" name="settings[gemini_temperature]" value="{{ $settings->get('ai_copilot', collect())->firstWhere('key', 'gemini_temperature')?->value ?? '0.1' }}" class="w-full px-4 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-xs text-white focus:outline-none focus:border-indigo-500 font-mono">
                    </div>

                    <!-- Max Output Tokens -->
                    <div class="space-y-2 bg-slate-900/50 border border-slate-800/80 p-4 rounded-2xl">
                        <label class="block text-xs font-bold text-slate-200">Max Tokens per Response</label>
                        <p class="text-[11px] text-slate-400 font-medium">Safety token ceiling per query response (default: 2048).</p>
                        <input type="number" step="256" min="512" max="8192" name="settings[gemini_max_tokens]" value="{{ $settings->get('ai_copilot', collect())->firstWhere('key', 'gemini_max_tokens')?->value ?? '2048' }}" class="w-full px-4 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-xs text-white focus:outline-none focus:border-indigo-500 font-mono">
                    </div>

                    <!-- Monthly Budget Ceiling -->
                    <div class="space-y-2 bg-slate-900/50 border border-slate-800/80 p-4 rounded-2xl">
                        <label class="block text-xs font-bold text-slate-200">Monthly Token Budget Soft Ceiling</label>
                        <p class="text-[11px] text-slate-400 font-medium">Warning threshold for monthly token monitoring (e.g. 5,000,000 tokens).</p>
                        <input type="number" step="100000" name="settings[gemini_monthly_budget_ceiling]" value="{{ $settings->get('ai_copilot', collect())->firstWhere('key', 'gemini_monthly_budget_ceiling')?->value ?? '5000000' }}" class="w-full px-4 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-xs text-white focus:outline-none focus:border-indigo-500 font-mono">
                    </div>

                    <!-- Master Switch -->
                    <div class="space-y-2 bg-slate-900/50 border border-slate-800/80 p-4 rounded-2xl">
                        <label class="block text-xs font-bold text-slate-200">Master AI Copilot Switch</label>
                        <p class="text-[11px] text-slate-400 font-medium">Enables or disables the AI Copilot launcher button and Ctrl+K search bar globally.</p>
                        @php $copilotEnabled = $settings->get('ai_copilot', collect())->firstWhere('key', 'ai_copilot_enabled')?->value ?? '1'; @endphp
                        <select name="settings[ai_copilot_enabled]" class="w-full px-4 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-xs text-white focus:outline-none focus:border-indigo-500">
                            <option value="1" {{ $copilotEnabled == '1' ? 'selected' : '' }}>Enabled (Active for Super Admin)</option>
                            <option value="0" {{ $copilotEnabled == '0' ? 'selected' : '' }}>Disabled (Hidden)</option>
                        </select>
                    </div>
                </div>

                <!-- Notifications Master Toggles -->
                <div class="pt-6 border-t border-slate-800/80">
                    <h4 class="text-xs font-bold text-white mb-3 flex items-center space-x-2">
                        <i class="fa-solid fa-bell text-indigo-400"></i>
                        <span>Automated System Email Alerts</span>
                    </h4>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        @foreach($settings->get('notifications', []) as $s)
                            <div class="space-y-1.5 bg-slate-900/40 border border-slate-800/80 p-3.5 rounded-xl">
                                <label class="block text-xs font-bold text-slate-200">{{ $s->label }}</label>
                                <select name="settings[{{ $s->key }}]" class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-lg text-xs text-white focus:outline-none focus:border-indigo-500">
                                    <option value="1" {{ $s->value == '1' ? 'selected' : '' }}>Enabled</option>
                                    <option value="0" {{ $s->value == '0' ? 'selected' : '' }}>Disabled</option>
                                </select>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Recent AI Queries & Telemetry Audit Log Table -->
            <div class="bg-slate-950 border border-slate-800 rounded-3xl p-6 lg:p-8 space-y-4 shadow-xl">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-800/80 pb-4">
                    <div>
                        <h3 class="text-base font-bold text-white flex items-center space-x-2">
                            <i class="fa-solid fa-chart-line text-indigo-400"></i>
                            <span>AI Audit & Telemetry Log (Last 50 Inquiries)</span>
                        </h3>
                        <p class="text-xs text-slate-400 mt-1">Live audit trail recording exact prompts, models used, tool activations, token counts, costs, and latencies.</p>
                    </div>
                    <span class="text-xs font-mono text-slate-400 bg-slate-900 px-3 py-1.5 rounded-xl border border-slate-800">
                        {{ count($recentAiLogs ?? []) }} Logs Recorded
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="border-b border-slate-800 text-slate-400 font-bold uppercase text-[10px] tracking-wider">
                                <th class="py-3 px-3">Timestamp</th>
                                <th class="py-3 px-3">User</th>
                                <th class="py-3 px-3">Model</th>
                                <th class="py-3 px-3">Prompt</th>
                                <th class="py-3 px-3">Tools Invoked</th>
                                <th class="py-3 px-3 text-right">Tokens</th>
                                <th class="py-3 px-3 text-right">Est. Cost</th>
                                <th class="py-3 px-3 text-right">Latency</th>
                                <th class="py-3 px-3 text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/60">
                            @forelse($recentAiLogs ?? [] as $log)
                                <tr class="hover:bg-slate-900/40 transition-colors">
                                    <td class="py-3 px-3 text-slate-400 font-mono text-[11px] whitespace-nowrap">
                                        {{ $log->created_at->format('M d, h:i A') }}
                                    </td>
                                    <td class="py-3 px-3 font-semibold text-white whitespace-nowrap">
                                        {{ $log->user_name ?: ($log->user->name ?? 'Admin') }}
                                    </td>
                                    <td class="py-3 px-3 whitespace-nowrap">
                                        @if(str_contains($log->model_used, 'pro'))
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-purple-500/10 text-purple-300 border border-purple-500/20">🧠 Pro</span>
                                        @else
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-indigo-500/10 text-indigo-300 border border-indigo-500/20">⚡ Flash</span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-3 text-slate-200 max-w-xs truncate" title="{{ $log->prompt }}">
                                        {{ $log->prompt }}
                                    </td>
                                    <td class="py-3 px-3">
                                        @if(!empty($log->tools_called) && is_array($log->tools_called))
                                            <div class="flex flex-wrap gap-1">
                                                @foreach($log->tools_called as $tool)
                                                    <span class="px-1.5 py-0.5 rounded bg-slate-800 text-[10px] font-mono text-indigo-300 border border-slate-700">{{ $tool }}</span>
                                                @endforeach
                                            </div>
                                        @else
                                            <span class="text-slate-500 text-[10px]">&mdash;</span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-3 text-right font-mono text-slate-300 text-[11px] whitespace-nowrap">
                                        {{ number_format($log->total_tokens) }}
                                    </td>
                                    <td class="py-3 px-3 text-right font-mono text-emerald-400 text-[11px] whitespace-nowrap">
                                        ${{ number_format($log->cost_usd, 5) }}
                                    </td>
                                    <td class="py-3 px-3 text-right font-mono text-slate-400 text-[11px] whitespace-nowrap">
                                        {{ $log->latency_ms }}ms
                                    </td>
                                    <td class="py-3 px-3 text-center whitespace-nowrap">
                                        @if($log->status === 'SUCCESS')
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">OK</span>
                                        @else
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-500/10 text-rose-400 border border-rose-500/20">ERR</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="py-8 text-center text-slate-500">No AI inquiries logged yet. Test out the AI Copilot using Ctrl+K or the launcher button!</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
        @endif

        <!-- Global Save Action -->
        <div class="mt-8 flex justify-end">
            <button type="submit" class="px-6 py-3 bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs rounded-xl shadow-lg shadow-indigo-600/30 transition-all flex items-center space-x-2">
                <i class="fa-solid fa-floppy-disk"></i>
                <span>Save All System Changes</span>
            </button>
        </div>
    </form>

    <!-- Hidden Form for Test Email -->
    <form id="testEmailHiddenForm" action="{{ route('settings.test-email') }}" method="POST" class="hidden">
        @csrf
        <input type="hidden" name="test_email" id="testEmailHiddenVal">
    </form>
    <script>
    function sendTestSmtpEmail() {
        var email = document.getElementById('testEmailInput').value;
        if (!email) {
            alert('Please enter a valid email address.');
            return;
        }
        document.getElementById('testEmailHiddenVal').value = email;
        document.getElementById('testEmailHiddenForm').submit();
    }

    async function testGeminiApiConnection() {
        const btn = document.getElementById('testGeminiBtn');
        const statusDiv = document.getElementById('geminiTestResult');
        const keyInput = document.querySelector('input[name="settings[gemini_api_key]"]');
        const apiKey = keyInput ? keyInput.value.trim() : '';

        btn.disabled = true;
        const originalHtml = btn.innerHTML;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-1"></i><span>Testing...</span>';
        statusDiv.classList.remove('hidden');
        statusDiv.innerHTML = '<span class="text-indigo-400 font-semibold"><i class="fa-solid fa-circle-notch fa-spin mr-1.5"></i>Pinging Google Gemini API servers...</span>';

        try {
            const res = await fetch('{{ route("settings.ai.test-connection") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ api_key: apiKey })
            });
            const data = await res.json();
            if (data.success) {
                statusDiv.innerHTML = `<span class="inline-flex items-center px-3 py-1.5 rounded-xl bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 text-xs font-bold shadow-sm"><i class="fa-solid fa-circle-check text-emerald-400 mr-2 text-sm"></i>${data.message} (${data.latency_ms}ms)</span>`;
            } else {
                statusDiv.innerHTML = `<span class="inline-flex items-center px-3 py-1.5 rounded-xl bg-rose-500/20 text-rose-300 border border-rose-500/30 text-xs font-bold shadow-sm"><i class="fa-solid fa-circle-xmark text-rose-400 mr-2 text-sm"></i>${data.message} (${data.latency_ms ? data.latency_ms + 'ms' : 'Error'})</span>`;
            }
        } catch (e) {
            statusDiv.innerHTML = `<span class="inline-flex items-center px-3 py-1.5 rounded-xl bg-rose-500/20 text-rose-300 border border-rose-500/30 text-xs font-bold shadow-sm"><i class="fa-solid fa-circle-xmark text-rose-400 mr-2 text-sm"></i>Request failed: ${e.message}</span>`;
        } finally {
            btn.disabled = false;
            btn.innerHTML = originalHtml;
        }
    }
    </script>

    <!-- TAB: DEVELOPER FEATURE SWITCHBOARD (DEV ONLY) -->
    @developer
    <div x-show="activeTab === 'features'" class="space-y-6" x-cloak>
        <!-- Info Banner -->
        <div class="bg-gradient-to-r from-amber-950/40 via-slate-950 to-slate-950 border border-amber-500/30 rounded-3xl p-6 shadow-xl relative overflow-hidden">
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                <div class="flex items-start space-x-4">
                    <div class="w-12 h-12 rounded-2xl bg-amber-500/10 border border-amber-500/30 text-amber-400 flex items-center justify-center text-xl shrink-0 shadow-lg">
                        <i class="fa-solid fa-code-branch"></i>
                    </div>
                    <div>
                        <div class="flex items-center space-x-2">
                            <h3 class="text-base font-extrabold text-white">Developer Feature Switchboard</h3>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-amber-500/20 text-amber-300 border border-amber-500/30">RESTRICTED TO SOLE DEVELOPER</span>
                        </div>
                        <p class="text-xs text-slate-400 mt-1">
                            Toggle off any feature below to make it completely disappear from other users and admins. 
                            <strong class="text-amber-300">You will always retain full access</strong> and see a <span class="px-1.5 py-0.5 rounded text-[10px] bg-amber-500/20 text-amber-300 border border-amber-500/30 font-bold">WIP</span> indicator on hidden modules in your sidebar navigation.
                        </p>
                    </div>
                </div>
                <button @click="addFeatureModalOpen = true" class="px-4 py-2.5 bg-gradient-to-r from-amber-500 to-orange-600 hover:from-amber-400 hover:to-orange-500 text-slate-950 font-black text-xs rounded-xl shadow-lg shadow-amber-500/20 transition-all flex items-center space-x-2 shrink-0">
                    <i class="fa-solid fa-plus"></i>
                    <span>+ Register Feature Flag</span>
                </button>
            </div>
        </div>

        <!-- Features Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
            @foreach($features ?? [] as $featureKey => $meta)
                <div class="bg-slate-950/70 border border-slate-800 rounded-3xl p-5 flex flex-col justify-between space-y-4 hover:border-slate-700 transition-all shadow-xl relative overflow-hidden group">
                    <div class="space-y-3">
                        <div class="flex items-start justify-between">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 rounded-2xl bg-indigo-600/10 border border-indigo-500/20 text-indigo-400 flex items-center justify-center text-lg shadow-md">
                                    <i class="{{ $meta['icon'] ?? 'fa-solid fa-cube' }}"></i>
                                </div>
                                <div>
                                    <h4 class="text-sm font-bold text-white group-hover:text-indigo-300 transition-colors">{{ $meta['label'] }}</h4>
                                    <span class="text-[10px] font-mono text-slate-500">feature_{{ $meta['key'] }}</span>
                                </div>
                            </div>

                            @if(!empty($meta['is_custom']))
                                <form action="{{ route('developer.features.destroy-custom', $meta['key']) }}" method="POST" onsubmit="return confirm('Delete custom feature flag \'{{ $meta['label'] }}\'?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-slate-500 hover:text-rose-400 p-1 transition-colors" title="Delete custom flag">
                                        <i class="fa-solid fa-trash-can text-xs"></i>
                                    </button>
                                </form>
                            @endif
                        </div>

                        <p class="text-xs text-slate-400 line-clamp-2 leading-relaxed">
                            {{ $meta['description'] }}
                        </p>
                    </div>

                    <div class="pt-3 border-t border-slate-800/80 flex items-center justify-between">
                        <div>
                            <span class="text-[10px] font-semibold uppercase tracking-wider block text-slate-500">{{ $meta['category'] ?? 'Module' }}</span>
                            <span id="status-pill-{{ $meta['key'] }}" class="inline-flex items-center space-x-1.5 text-[11px] font-bold {{ $meta['is_enabled'] ? 'text-emerald-400' : 'text-amber-400' }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ $meta['is_enabled'] ? 'bg-emerald-400 animate-pulse' : 'bg-amber-400' }}"></span>
                                <span>{{ $meta['is_enabled'] ? 'Live for All' : 'Hidden (Dev Only)' }}</span>
                            </span>
                        </div>

                        <!-- Toggle Switch -->
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" 
                                   class="sr-only peer" 
                                   {{ $meta['is_enabled'] ? 'checked' : '' }}
                                   @change="toggleFeature('{{ $meta['key'] }}', $event.target.checked)">
                            <div class="w-11 h-6 bg-slate-800 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-600"></div>
                        </label>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
    @enddeveloper

    <!-- Modal: Add New Department -->
    <div x-show="addDeptModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm">
        <div @click.away="addDeptModalOpen = false" class="bg-slate-900 border border-slate-800 rounded-3xl max-w-md w-full p-6 space-y-4 shadow-2xl">
            <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                <h3 class="text-base font-bold text-white flex items-center space-x-2">
                    <i class="fa-solid fa-sitemap text-indigo-400"></i>
                    <span>Register New Department</span>
                </h3>
                <button @click="addDeptModalOpen = false" class="text-slate-400 hover:text-white"><i class="fa-solid fa-xmark"></i></button>
            </div>

            <form action="{{ route('settings.departments.store') }}" method="POST" class="space-y-4 text-xs">
                @csrf
                <div>
                    <label class="block font-semibold text-slate-300 mb-1">Company / Organization *</label>
                    <select name="organization_id" required class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white">
                        @foreach($organizations as $org)
                            <option value="{{ $org->id }}">{{ $org->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block font-semibold text-slate-300 mb-1">Department Name *</label>
                    <input type="text" name="name" required placeholder="e.g. Software Engineering" class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white focus:outline-none focus:border-indigo-500">
                </div>

                <div>
                    <label class="block font-semibold text-slate-300 mb-1">Department Code (Optional)</label>
                    <input type="text" name="code" placeholder="e.g. ENG, DES, QA, HR" class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white focus:outline-none focus:border-indigo-500 uppercase">
                </div>

                <div>
                    <label class="block font-semibold text-slate-300 mb-1">Parent Department (Optional)</label>
                    <select name="parent_department_id" class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white">
                        <option value="">-- None (Top Level Department) --</option>
                        @foreach($departments as $d)
                            <option value="{{ $d->id }}">[{{ $d->organization->name ?? 'Org' }}] {{ $d->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block font-semibold text-slate-300 mb-1">Head of Department (Optional)</label>
                    <select name="head_user_id" class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white">
                        <option value="">-- Select Manager / Lead --</option>
                        @foreach($users as $u)
                            <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->email }})</option>
                        @endforeach
                    </select>
                </div>

                <div class="pt-2 flex justify-end space-x-3">
                    <button type="button" @click="addDeptModalOpen = false" class="px-4 py-2 bg-slate-800 text-slate-300 font-semibold rounded-xl">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white font-bold rounded-xl shadow-lg">Save Department</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal: Edit Department -->
    <div x-show="editDeptModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm">
        <div @click.away="editDeptModalOpen = false" class="bg-slate-900 border border-slate-800 rounded-3xl max-w-md w-full p-6 space-y-4 shadow-2xl">
            <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                <h3 class="text-base font-bold text-white flex items-center space-x-2">
                    <i class="fa-solid fa-pen-to-square text-indigo-400"></i>
                    <span>Edit Department Details</span>
                </h3>
                <button @click="editDeptModalOpen = false" class="text-slate-400 hover:text-white"><i class="fa-solid fa-xmark"></i></button>
            </div>

            <form :action="'{{ url('settings/departments') }}/' + activeDept.id" method="POST" class="space-y-4 text-xs">
                @csrf
                @method('PUT')
                <div>
                    <label class="block font-semibold text-slate-300 mb-1">Company / Organization *</label>
                    <select name="organization_id" x-model="activeDept.organization_id" required class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white">
                        @foreach($organizations as $org)
                            <option value="{{ $org->id }}">{{ $org->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block font-semibold text-slate-300 mb-1">Department Name *</label>
                    <input type="text" name="name" x-model="activeDept.name" required class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white focus:outline-none focus:border-indigo-500">
                </div>

                <div>
                    <label class="block font-semibold text-slate-300 mb-1">Department Code</label>
                    <input type="text" name="code" x-model="activeDept.code" class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white focus:outline-none focus:border-indigo-500 uppercase">
                </div>

                <div>
                    <label class="block font-semibold text-slate-300 mb-1">Parent Department</label>
                    <select name="parent_department_id" x-model="activeDept.parent_department_id" class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white">
                        <option value="">-- None (Top Level Department) --</option>
                        @foreach($departments as $d)
                            <option value="{{ $d->id }}">[{{ $d->organization->name ?? 'Org' }}] {{ $d->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block font-semibold text-slate-300 mb-1">Head of Department</label>
                    <select name="head_user_id" x-model="activeDept.head_user_id" class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white">
                        <option value="">-- Select Manager / Lead --</option>
                        @foreach($users as $u)
                            <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->email }})</option>
                        @endforeach
                    </select>
                </div>

                <div class="pt-2 flex justify-end space-x-3">
                    <button type="button" @click="editDeptModalOpen = false" class="px-4 py-2 bg-slate-800 text-slate-300 font-semibold rounded-xl">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white font-bold rounded-xl shadow-lg">Update Department</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal: Add New Designation -->
    <div x-show="addDesigModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm">
        <div @click.away="addDesigModalOpen = false" class="bg-slate-900 border border-slate-800 rounded-3xl max-w-md w-full p-6 space-y-4 shadow-2xl">
            <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                <h3 class="text-base font-bold text-white flex items-center space-x-2">
                    <i class="fa-solid fa-id-badge text-purple-400"></i>
                    <span>Register New Designation</span>
                </h3>
                <button @click="addDesigModalOpen = false" class="text-slate-400 hover:text-white"><i class="fa-solid fa-xmark"></i></button>
            </div>

            <form action="{{ route('settings.designations.store') }}" method="POST" class="space-y-4 text-xs">
                @csrf
                <div>
                    <label class="block font-semibold text-slate-300 mb-1">Company / Organization *</label>
                    <select name="organization_id" required class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white">
                        @foreach($organizations as $org)
                            <option value="{{ $org->id }}">{{ $org->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block font-semibold text-slate-300 mb-1">Designation / Job Title *</label>
                    <input type="text" name="title" required placeholder="e.g. Senior UI/UX Designer" class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white focus:outline-none focus:border-purple-500">
                </div>

                <div>
                    <label class="block font-semibold text-slate-300 mb-1">Designation Code (Optional)</label>
                    <input type="text" name="code" placeholder="e.g. SR-UIUX, PSE, PM, QA-ENG" class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white focus:outline-none focus:border-purple-500 uppercase">
                </div>

                <div class="pt-2 flex justify-end space-x-3">
                    <button type="button" @click="addDesigModalOpen = false" class="px-4 py-2 bg-slate-800 text-slate-300 font-semibold rounded-xl">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-purple-600 hover:bg-purple-500 text-white font-bold rounded-xl shadow-lg">Save Designation</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal: Edit Designation -->
    <div x-show="editDesigModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm">
        <div @click.away="editDesigModalOpen = false" class="bg-slate-900 border border-slate-800 rounded-3xl max-w-md w-full p-6 space-y-4 shadow-2xl">
            <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                <h3 class="text-base font-bold text-white flex items-center space-x-2">
                    <i class="fa-solid fa-pen-to-square text-purple-400"></i>
                    <span>Edit Designation</span>
                </h3>
                <button @click="editDesigModalOpen = false" class="text-slate-400 hover:text-white"><i class="fa-solid fa-xmark"></i></button>
            </div>

            <form :action="'{{ url('settings/designations') }}/' + activeDesig.id" method="POST" class="space-y-4 text-xs">
                @csrf
                @method('PUT')
                <div>
                    <label class="block font-semibold text-slate-300 mb-1">Company / Organization *</label>
                    <select name="organization_id" x-model="activeDesig.organization_id" required class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white">
                        @foreach($organizations as $org)
                            <option value="{{ $org->id }}">{{ $org->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block font-semibold text-slate-300 mb-1">Designation Title *</label>
                    <input type="text" name="title" x-model="activeDesig.title" required class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white focus:outline-none focus:border-purple-500">
                </div>

                <div>
                    <label class="block font-semibold text-slate-300 mb-1">Designation Code</label>
                    <input type="text" name="code" x-model="activeDesig.code" class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white focus:outline-none focus:border-purple-500 uppercase">
                </div>

                <div class="pt-2 flex justify-end space-x-3">
                    <button type="button" @click="editDesigModalOpen = false" class="px-4 py-2 bg-slate-800 text-slate-300 font-semibold rounded-xl">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-purple-600 hover:bg-purple-500 text-white font-bold rounded-xl shadow-lg">Update Designation</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal: Add New Work Shift -->
    <div x-show="addShiftModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm">
        <div @click.away="addShiftModalOpen = false" class="bg-slate-900 border border-slate-800 rounded-3xl max-w-md w-full p-6 space-y-4 shadow-2xl">
            <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                <h3 class="text-base font-bold text-white flex items-center space-x-2">
                    <i class="fa-solid fa-business-time text-emerald-400"></i>
                    <span>Register New Work Shift</span>
                </h3>
                <button @click="addShiftModalOpen = false" class="text-slate-400 hover:text-white"><i class="fa-solid fa-xmark"></i></button>
            </div>

            <form action="{{ route('settings.shifts.store') }}" method="POST" class="space-y-4 text-xs">
                @csrf
                <div>
                    <label class="block font-semibold text-slate-300 mb-1">Company / Organization *</label>
                    <select name="organization_id" required class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white">
                        @foreach($organizations as $org)
                            <option value="{{ $org->id }}">{{ $org->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block font-semibold text-slate-300 mb-1">Shift Name *</label>
                    <input type="text" name="name" required placeholder="e.g. Morning Shift, Evening Shift, Night Shift" class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white focus:outline-none focus:border-emerald-500">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-semibold text-slate-300 mb-1">Shift Start Time *</label>
                        <input type="time" name="start_time" required value="09:00" class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white font-mono focus:outline-none focus:border-emerald-500">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-300 mb-1">Shift End Time *</label>
                        <input type="time" name="end_time" required value="18:00" class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white font-mono focus:outline-none focus:border-emerald-500">
                    </div>
                </div>

                <div class="p-3 bg-slate-950/60 border border-slate-800/80 rounded-2xl">
                    <label class="flex items-start space-x-3 cursor-pointer">
                        <input type="checkbox" name="flexible" value="1" class="mt-0.5 rounded bg-slate-900 border-slate-700 text-emerald-500 focus:ring-0">
                        <div>
                            <span class="font-bold text-slate-200 block text-xs">Flexible Hours Shift</span>
                            <span class="text-[11px] text-slate-400 block mt-0.5">Employees on this shift will not be flagged as 'LATE' based on fixed clock-in time.</span>
                        </div>
                    </label>
                </div>

                <div class="pt-2 flex justify-end space-x-3">
                    <button type="button" @click="addShiftModalOpen = false" class="px-4 py-2 bg-slate-800 text-slate-300 font-semibold rounded-xl">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white font-bold rounded-xl shadow-lg shadow-emerald-600/20">Save Work Shift</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal: Edit Work Shift -->
    <div x-show="editShiftModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm">
        <div @click.away="editShiftModalOpen = false" class="bg-slate-900 border border-slate-800 rounded-3xl max-w-md w-full p-6 space-y-4 shadow-2xl">
            <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                <h3 class="text-base font-bold text-white flex items-center space-x-2">
                    <i class="fa-solid fa-pen-to-square text-emerald-400"></i>
                    <span>Edit Work Shift</span>
                </h3>
                <button @click="editShiftModalOpen = false" class="text-slate-400 hover:text-white"><i class="fa-solid fa-xmark"></i></button>
            </div>

            <form :action="'{{ url('settings/shifts') }}/' + activeShift.id" method="POST" class="space-y-4 text-xs">
                @csrf
                @method('PUT')
                <div>
                    <label class="block font-semibold text-slate-300 mb-1">Company / Organization *</label>
                    <select name="organization_id" x-model="activeShift.organization_id" required class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white">
                        @foreach($organizations as $org)
                            <option value="{{ $org->id }}">{{ $org->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block font-semibold text-slate-300 mb-1">Shift Name *</label>
                    <input type="text" name="name" x-model="activeShift.name" required class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white focus:outline-none focus:border-emerald-500">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-semibold text-slate-300 mb-1">Shift Start Time *</label>
                        <input type="time" name="start_time" x-model="activeShift.start_time" required class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white font-mono focus:outline-none focus:border-emerald-500">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-300 mb-1">Shift End Time *</label>
                        <input type="time" name="end_time" x-model="activeShift.end_time" required class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white font-mono focus:outline-none focus:border-emerald-500">
                    </div>
                </div>

                <div class="p-3 bg-slate-950/60 border border-slate-800/80 rounded-2xl">
                    <label class="flex items-start space-x-3 cursor-pointer">
                        <input type="checkbox" name="flexible" value="1" :checked="activeShift.flexible" x-model="activeShift.flexible" class="mt-0.5 rounded bg-slate-900 border-slate-700 text-emerald-500 focus:ring-0">
                        <div>
                            <span class="font-bold text-slate-200 block text-xs">Flexible Hours Shift</span>
                            <span class="text-[11px] text-slate-400 block mt-0.5">Employees on this shift will not be flagged as 'LATE' based on fixed clock-in time.</span>
                        </div>
                    </label>
                </div>

                <div class="pt-2 flex justify-end space-x-3">
                    <button type="button" @click="editShiftModalOpen = false" class="px-4 py-2 bg-slate-800 text-slate-300 font-semibold rounded-xl">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white font-bold rounded-xl shadow-lg shadow-emerald-600/20">Update Work Shift</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal: Register Custom Feature Flag -->
    @developer
    <div x-show="addFeatureModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm">
        <div @click.away="addFeatureModalOpen = false" class="bg-slate-900 border border-slate-800 rounded-3xl max-w-md w-full p-6 space-y-4 shadow-2xl">
            <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                <h3 class="text-base font-bold text-white flex items-center space-x-2">
                    <i class="fa-solid fa-circle-plus text-amber-400"></i>
                    <span>Register New Feature Flag</span>
                </h3>
                <button @click="addFeatureModalOpen = false" class="text-slate-400 hover:text-white"><i class="fa-solid fa-xmark"></i></button>
            </div>

            <form action="{{ route('developer.features.store-custom') }}" method="POST" class="space-y-4 text-xs">
                @csrf
                <div>
                    <label class="block font-semibold text-slate-300 mb-1">Feature Key Identifier * (e.g. recruitment, sales_targets, analytics)</label>
                    <input type="text" name="key" required placeholder="e.g. performance_kpis" class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white focus:outline-none focus:border-amber-500 font-mono">
                </div>

                <div>
                    <label class="block font-semibold text-slate-300 mb-1">Feature Display Name / Title *</label>
                    <input type="text" name="label" required placeholder="e.g. Performance KPIs & Reviews" class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white focus:outline-none focus:border-amber-500">
                </div>

                <div>
                    <label class="block font-semibold text-slate-300 mb-1">Category / Group</label>
                    <input type="text" name="category" placeholder="e.g. Operations or Talent" value="Custom / In Progress" class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white focus:outline-none focus:border-amber-500">
                </div>

                <div>
                    <label class="block font-semibold text-slate-300 mb-1">FontAwesome Icon Class</label>
                    <input type="text" name="icon" placeholder="e.g. fa-solid fa-chart-line" value="fa-solid fa-layer-group" class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white focus:outline-none focus:border-amber-500">
                </div>

                <div>
                    <label class="block font-semibold text-slate-300 mb-1">Description *</label>
                    <textarea name="description" required rows="2" placeholder="Brief description of what this feature does..." class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white focus:outline-none focus:border-amber-500"></textarea>
                </div>

                <div class="pt-1">
                    <label class="inline-flex items-center space-x-2 cursor-pointer">
                        <input type="checkbox" name="initial_enabled" value="1" class="rounded bg-slate-950 border-slate-800 text-amber-500 focus:ring-0">
                        <span class="font-semibold text-slate-300">Set active for all users immediately (Default: unchecked / Hidden WIP mode)</span>
                    </label>
                </div>

                <div class="pt-2 flex justify-end space-x-3">
                    <button type="button" @click="addFeatureModalOpen = false" class="px-4 py-2 bg-slate-800 text-slate-300 font-semibold rounded-xl">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-gradient-to-r from-amber-500 to-orange-600 text-slate-950 font-black rounded-xl shadow-lg">Save Feature Flag</button>
                </div>
            </form>
        </div>
    </div>
    @enddeveloper

</div>

<script>
function settingsManager(defaultTab = 'general') {
    return {
        activeTab: defaultTab,
        addDeptModalOpen: false,
        editDeptModalOpen: false,
        activeDept: {},
        addDesigModalOpen: false,
        editDesigModalOpen: false,
        activeDesig: {},
        addShiftModalOpen: false,
        editShiftModalOpen: false,
        activeShift: {},
        addFeatureModalOpen: false,
        async toggleFeature(featureKey, isEnabled) {
            const statusPill = document.getElementById(`status-pill-${featureKey}`);
            try {
                const response = await fetch('{{ route("developer.features.toggle") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        feature_key: featureKey,
                        is_enabled: isEnabled
                    })
                });
                const data = await response.json();
                if (statusPill) {
                    if (isEnabled) {
                        statusPill.className = 'inline-flex items-center space-x-1.5 text-[11px] font-bold text-emerald-400';
                        statusPill.innerHTML = '<span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span><span>Live for All</span>';
                    } else {
                        statusPill.className = 'inline-flex items-center space-x-1.5 text-[11px] font-bold text-amber-400';
                        statusPill.innerHTML = '<span class="w-1.5 h-1.5 rounded-full bg-amber-400"></span><span>Hidden (Dev Only)</span>';
                    }
                }
            } catch (err) {
                console.error('Failed to toggle feature:', err);
                alert('Could not update feature flag. Please try again.');
            }
        },
        openEditDept(dept) {
            this.activeDept = JSON.parse(JSON.stringify(dept));
            this.editDeptModalOpen = true;
        },
        openEditDesig(desig) {
            this.activeDesig = JSON.parse(JSON.stringify(desig));
            this.editDesigModalOpen = true;
        },
        openEditShift(shift) {
            this.activeShift = JSON.parse(JSON.stringify(shift));
            if (this.activeShift.start_time && this.activeShift.start_time.length > 5) {
                this.activeShift.start_time = this.activeShift.start_time.substring(0, 5);
            }
            if (this.activeShift.end_time && this.activeShift.end_time.length > 5) {
                this.activeShift.end_time = this.activeShift.end_time.substring(0, 5);
            }
            this.editShiftModalOpen = true;
        }
    };
}
</script>

@endsection
