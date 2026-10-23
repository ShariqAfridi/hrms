@extends('layouts.app')

@section('title', 'Employee Directory')

@section('content')

<div x-data="employeeManager()" @open-quick-shift="openQuickShiftModal($event.detail)" class="space-y-6">

    <!-- Page Header & Controls -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-white tracking-tight">Employees Directory</h1>
            <p class="text-xs font-medium text-slate-400 mt-1">Manage workforce members across multiple companies, roles, and company payrolls.</p>
        </div>

        <div class="flex items-center space-x-3">
            @if(Auth::user()->isSystemAdmin() || Auth::user()->isSuperAdmin() || Auth::user()->isOrgAdmin())
            <a href="{{ route('admin.profile-requests.index') }}" class="px-3.5 py-2.5 bg-amber-500/10 hover:bg-amber-500/20 text-amber-400 border border-amber-500/30 font-bold text-xs rounded-xl shadow-md shadow-amber-500/10 transition-all flex items-center space-x-2">
                <i class="fa-solid fa-user-shield"></i>
                <span>Bank & NIC Approvals</span>
                @php
                    $headerPendingCount = \App\Models\ProfileChangeRequest::where('status', 'PENDING')->count();
                @endphp
                @if($headerPendingCount > 0)
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-amber-500 text-slate-950">
                        {{ $headerPendingCount }}
                    </span>
                @endif
            </a>
            @endif

            @if(Auth::user()->canManageEmployees())
            <button @click="addModalOpen = true" class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs rounded-xl shadow-lg shadow-indigo-600/30 transition-all flex items-center justify-center space-x-2">
                <i class="fa-solid fa-user-plus"></i>
                <span>Add New Employee</span>
            </button>
            @endif
        </div>
    </div>

    <!-- Search & Filter Bar -->
    <div class="bg-slate-950/60 border border-slate-800 rounded-2xl p-4 flex flex-col md:flex-row items-center justify-between gap-4 shadow-xl">
        <form action="{{ route('employees.index') }}" method="GET" class="w-full flex flex-col md:flex-row items-center gap-3">
            <div class="relative w-full md:flex-1">
                <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-3 text-slate-500 text-xs"></i>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by name or email..." class="w-full pl-9 pr-4 py-2 bg-slate-900 border border-slate-800 rounded-xl text-xs font-medium text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500 transition-all">
            </div>

            <select name="organization_id" class="w-full md:w-48 px-3 py-2 bg-slate-900 border border-slate-800 rounded-xl text-xs font-medium text-slate-300 focus:outline-none focus:border-indigo-500 transition-all">
                <option value="">All Companies</option>
                @foreach($organizations as $org)
                    <option value="{{ $org->id }}" {{ request('organization_id') == $org->id ? 'selected' : '' }}>{{ $org->name }}</option>
                @endforeach
            </select>

            <select name="department_id" class="w-full md:w-44 px-3 py-2 bg-slate-900 border border-slate-800 rounded-xl text-xs font-medium text-slate-300 focus:outline-none focus:border-indigo-500 transition-all">
                <option value="">All Departments</option>
                @foreach($departments as $dept)
                    <option value="{{ $dept->id }}" {{ request('department_id') == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                @endforeach
            </select>

            <select name="team_id" class="w-full md:w-44 px-3 py-2 bg-slate-900 border border-slate-800 rounded-xl text-xs font-medium text-slate-300 focus:outline-none focus:border-indigo-500 transition-all">
                <option value="">All Teams / Squads</option>
                @foreach($teams as $tm)
                    <option value="{{ $tm->id }}" {{ request('team_id') == $tm->id ? 'selected' : '' }}>{{ $tm->name }}</option>
                @endforeach
            </select>

            <button type="submit" class="w-full md:w-auto px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-200 font-bold text-xs rounded-xl transition-all">
                Filter
            </button>
            @if(request('search') || request('organization_id') || request('department_id') || request('team_id'))
                <a href="{{ route('employees.index') }}" class="text-xs font-semibold text-rose-400 hover:underline">Clear Filters</a>
            @endif
        </form>
    </div>

    <!-- Employees Table -->
    <div class="bg-slate-950/60 border border-slate-800 rounded-3xl overflow-hidden shadow-2xl">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-900/80 border-b border-slate-800 text-[11px] font-bold text-slate-400 uppercase tracking-wider">
                        <th class="py-3.5 px-4 xl:px-6 whitespace-nowrap">Employee</th>
                        <th class="py-3.5 px-4 xl:px-6 whitespace-nowrap">Company & Department</th>
                        <th class="py-3.5 px-4 xl:px-6 whitespace-nowrap">Squad & Team Lead</th>
                        <th class="py-3.5 px-4 xl:px-6 whitespace-nowrap">Company Payroll</th>
                        @admin
                        <th class="py-3.5 px-4 xl:px-6 whitespace-nowrap">Salary (PKR)</th>
                        @endadmin
                        <th class="py-3.5 px-4 xl:px-6 whitespace-nowrap">Joined Date</th>
                        <th class="py-3.5 px-4 xl:px-6 whitespace-nowrap">Status</th>
                        @if(Auth::user()->canManageEmployees())
                        <th class="py-3.5 px-4 xl:px-6 text-right whitespace-nowrap">Actions</th>
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/80 text-xs">
                    @forelse($employees as $emp)
                        <tr class="hover:bg-slate-900/40 transition-colors">
                            <td class="py-3.5 px-4 xl:px-6">
                                <div class="flex items-center space-x-3">
                                    @if($emp->avatar_url)
                                        <img src="{{ $emp->avatar_url }}" alt="{{ $emp->name }}" class="w-9 h-9 rounded-xl object-cover ring-2 ring-indigo-500/30 shadow-md shrink-0">
                                    @else
                                        <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-indigo-600 to-purple-600 flex items-center justify-center font-bold text-white text-xs shadow-md shrink-0 ring-2 ring-indigo-500/20">
                                            {{ $emp->initials }}
                                        </div>
                                    @endif
                                    <div class="min-w-0">
                                        <p class="font-bold text-white text-sm truncate">{{ $emp->name }}</p>
                                        <p class="text-slate-400 text-xs font-medium truncate">{{ $emp->email }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="py-3.5 px-4 xl:px-6">
                                <p class="font-bold text-indigo-400 flex items-center space-x-1.5 whitespace-nowrap">
                                    @if($emp->organization && $emp->organization->logo_url)
                                        <img src="{{ $emp->organization->logo_url }}" alt="{{ $emp->organization->name }}" class="w-4 h-4 object-contain rounded shrink-0">
                                    @else
                                        <i class="fa-solid fa-building text-[11px]"></i>
                                    @endif
                                    <span>{{ $emp->organization->name ?? 'Global Entity' }}</span>
                                </p>
                                <p class="text-slate-400 text-[11px] font-medium whitespace-nowrap">{{ $emp->designation->title ?? 'Team Member' }} ({{ $emp->department->name ?? 'Dept' }})</p>
                                @if($emp->shift)
                                    <div class="mt-1 inline-flex items-center space-x-1.5 px-2 py-0.5 rounded-lg bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-[10px] font-semibold">
                                        <i class="fa-regular fa-clock text-[9px]"></i>
                                        <span>{{ $emp->shift->name }} ({{ date('h:i A', strtotime($emp->shift->start_time)) }} - {{ date('h:i A', strtotime($emp->shift->end_time)) }})</span>
                                        @if($emp->shift->flexible)
                                            <span class="text-[9px] text-blue-400 font-bold ml-0.5">• Flex</span>
                                        @endif
                                    </div>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 xl:px-6 whitespace-nowrap">
                                @if($emp->team)
                                    <div>
                                        <span class="inline-flex items-center space-x-1.5 px-2.5 py-1 rounded-xl bg-amber-500/10 border border-amber-500/20 text-amber-400 font-bold text-xs">
                                            <i class="fa-solid fa-people-group text-[10px]"></i>
                                            <span>{{ $emp->team->name }}</span>
                                        </span>
                                        @if($emp->team->leader)
                                            <p class="text-[10px] text-slate-400 mt-1 flex items-center space-x-1">
                                                <i class="fa-solid fa-crown text-[9px] text-amber-400"></i>
                                                <span>Lead: <strong class="text-slate-300">{{ $emp->team->leader->name }}</strong></span>
                                            </p>
                                        @endif
                                    </div>
                                @else
                                    <span class="text-slate-500 text-[11px] font-medium italic">No Team</span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 xl:px-6 whitespace-nowrap">
                                @if($emp->payroll)
                                    <div class="inline-flex items-center space-x-1.5 px-3 py-1.5 rounded-xl bg-indigo-500/10 border border-indigo-500/20 text-indigo-300 font-bold text-xs whitespace-nowrap shadow-sm">
                                        <i class="fa-solid fa-file-invoice-dollar text-indigo-400"></i>
                                        <span>{{ $emp->payroll->name }}</span>
                                    </div>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-xl bg-slate-900 border border-slate-800 text-slate-500 font-medium text-[11px] whitespace-nowrap">
                                        Unassigned
                                    </span>
                                @endif
                            </td>
                            @admin
                            <td class="py-3.5 px-4 xl:px-6 whitespace-nowrap">
                                <span class="font-extrabold text-white">PKR {{ number_format($emp->base_salary ?: 0, 0) }}</span>
                                <span class="text-[10px] text-slate-400 block">Bank Official</span>
                                @if(!empty($emp->cash_allowance) && $emp->cash_allowance > 0)
                                    <span class="text-[10px] text-amber-400 font-bold block" title="Confidential Cash Remuneration (Admin Only)">
                                        <i class="fa-solid fa-user-shield text-[9px] mr-0.5"></i>+{{ number_format($emp->cash_allowance, 0) }} cash
                                    </span>
                                @endif
                            </td>
                            @endadmin
                            <td class="py-3.5 px-4 xl:px-6 text-slate-400 font-medium whitespace-nowrap">
                                {{ $emp->date_of_joining ? date('d M Y', strtotime($emp->date_of_joining)) : 'N/A' }}
                            </td>
                            <td class="py-3.5 px-4 xl:px-6 whitespace-nowrap">
                                <span class="px-2.5 py-1 rounded-full font-semibold {{ $emp->is_active ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-rose-500/10 text-rose-400 border border-rose-500/20' }} text-[11px]">
                                    {{ $emp->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            @if(Auth::user()->canManageEmployees())
                            <td class="py-3.5 px-4 xl:px-6 text-right whitespace-nowrap">
                                <div class="flex items-center justify-end space-x-1">
                                    <button @click="openEditModal({{ json_encode($emp) }})" class="p-2 text-slate-400 hover:text-white rounded-lg hover:bg-slate-800 transition-colors" title="Edit Employee">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </button>

                                    <form action="{{ route('employees.destroy', $emp->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this employee?');" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-2 text-slate-500 hover:text-rose-400 rounded-lg hover:bg-rose-500/10 transition-colors" title="Delete">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-8 text-center text-slate-500 font-medium">
                                No employees found matching criteria.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="p-4 border-t border-slate-800">
            {{ $employees->links() }}
        </div>
    </div>

    <!-- Add New Employee Modal -->
    <div x-show="addModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-md overflow-y-auto" x-data="addEmployeeModal()">
        <div @click.away="if (!$event.target.closest('.flatpickr-calendar')) closeModal()" class="bg-slate-900 border border-slate-800 rounded-3xl max-w-2xl w-full p-6 md:p-8 space-y-6 shadow-2xl max-h-[92vh] overflow-y-auto">
            
            <!-- Modal Header -->
            <div class="flex items-center justify-between border-b border-slate-800 pb-4">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-2xl bg-indigo-600/20 border border-indigo-500/30 flex items-center justify-center text-indigo-400">
                        <i class="fa-solid fa-user-plus text-base"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-black text-white tracking-tight">Add New Employee</h3>
                        <p class="text-xs text-slate-400">Standard employee onboarding & compensation profile setup.</p>
                    </div>
                </div>
                <button type="button" @click="closeModal()" class="text-slate-400 hover:text-white p-2 hover:bg-slate-800 rounded-xl transition-colors"><i class="fa-solid fa-xmark text-sm"></i></button>
            </div>

            <!-- Tab Navigation Buttons -->
            <div class="flex items-center space-x-2 bg-slate-950 p-1.5 rounded-2xl border border-slate-800 text-xs font-bold overflow-x-auto">
                <button type="button" @click="goToTab('personal')" :class="addTab === 'personal' ? 'bg-indigo-600 text-white shadow-md' : 'text-slate-400 hover:text-slate-200'" class="px-3.5 py-2 rounded-xl transition-all flex items-center space-x-2 whitespace-nowrap">
                    <i class="fa-solid fa-user text-xs"></i>
                    <span>1. Identity & Contact</span>
                    <span x-show="hasTabError('personal')" class="w-2 h-2 rounded-full bg-rose-500 animate-pulse ml-0.5" title="Missing required fields"></span>
                </button>
                <button type="button" @click="goToTab('job')" :class="addTab === 'job' ? 'bg-indigo-600 text-white shadow-md' : 'text-slate-400 hover:text-slate-200'" class="px-3.5 py-2 rounded-xl transition-all flex items-center space-x-2 whitespace-nowrap">
                    <i class="fa-solid fa-briefcase text-xs"></i>
                    <span>2. Job & Role</span>
                    <span x-show="hasTabError('job')" class="w-2 h-2 rounded-full bg-rose-500 animate-pulse ml-0.5" title="Missing required fields"></span>
                </button>
                <button type="button" @click="goToTab('banking')" :class="addTab === 'banking' ? 'bg-indigo-600 text-white shadow-md' : 'text-slate-400 hover:text-slate-200'" class="px-3.5 py-2 rounded-xl transition-all flex items-center space-x-2 whitespace-nowrap">
                    <i class="fa-solid fa-building-columns text-xs"></i>
                    <span>3. Bank Account</span>
                </button>
                <button type="button" @click="goToTab('salary')" :class="addTab === 'salary' ? 'bg-indigo-600 text-white shadow-md' : 'text-slate-400 hover:text-slate-200'" class="px-3.5 py-2 rounded-xl transition-all flex items-center space-x-2 whitespace-nowrap">
                    <i class="fa-solid fa-money-check-dollar text-xs"></i>
                    <span>4. Salary Structure</span>
                    <span x-show="hasTabError('salary')" class="w-2 h-2 rounded-full bg-rose-500 animate-pulse ml-0.5" title="Missing required fields"></span>
                </button>
            </div>

            <form action="{{ route('employees.store') }}" method="POST" enctype="multipart/form-data" @submit.prevent="submitForm($event)" novalidate class="space-y-4 text-xs">
                @csrf

                <!-- Tab 1: Identity & Contact -->
                <div x-show="addTab === 'personal'" class="space-y-4">
                    <!-- Profile Picture Upload Field -->
                    <div class="bg-slate-950/80 p-3 rounded-2xl border border-slate-800 flex items-center space-x-4">
                        <div class="w-12 h-12 rounded-xl bg-slate-900 border border-slate-800 flex items-center justify-center text-slate-400 shrink-0">
                            <i class="fa-solid fa-camera text-base text-indigo-400"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <label class="block font-semibold text-slate-200 mb-0.5">Profile Picture (Optional)</label>
                            <input type="file" name="avatar" accept="image/*" class="w-full text-[11px] text-slate-400 file:mr-3 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:text-[11px] file:font-bold file:bg-indigo-600 file:text-white hover:file:bg-indigo-500 cursor-pointer">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div>
                            <label class="block font-semibold text-slate-300 mb-1">
                                Full Name <span class="text-rose-400 font-bold">*</span>
                            </label>
                            <input type="text" name="name" x-model="form.name" @input="clearError('name')" :data-has-error="!!errors.name" placeholder="e.g. John Doe"
                                   :class="errors.name ? 'border-rose-500 ring-1 ring-rose-500/50 bg-rose-500/5' : 'border-slate-800 focus:border-indigo-500'"
                                   class="w-full px-3 py-2.5 bg-slate-950 border rounded-xl text-white focus:outline-none transition-colors">
                            <template x-if="errors.name">
                                <p class="text-rose-400 text-[11px] font-semibold mt-1 flex items-center space-x-1.5">
                                    <i class="fa-solid fa-circle-exclamation text-rose-400 text-xs"></i>
                                    <span x-text="errors.name"></span>
                                </p>
                            </template>
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-300 mb-1">
                                Corporate Email Address <span class="text-rose-400 font-bold">*</span>
                            </label>
                            <input type="email" name="email" x-model="form.email" @input="clearError('email')" :data-has-error="!!errors.email" placeholder="john.doe@company.com"
                                   :class="errors.email ? 'border-rose-500 ring-1 ring-rose-500/50 bg-rose-500/5' : 'border-slate-800 focus:border-indigo-500'"
                                   class="w-full px-3 py-2.5 bg-slate-950 border rounded-xl text-white focus:outline-none transition-colors">
                            <template x-if="errors.email">
                                <p class="text-rose-400 text-[11px] font-semibold mt-1 flex items-center space-x-1.5">
                                    <i class="fa-solid fa-circle-exclamation text-rose-400 text-xs"></i>
                                    <span x-text="errors.email"></span>
                                </p>
                            </template>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label class="block font-semibold text-slate-300">
                                    Default Password <span class="text-rose-400 font-bold">*</span>
                                </label>
                                <button type="button" @click="showPass = !showPass" class="text-[10px] text-indigo-400 font-semibold flex items-center space-x-1">
                                    <i class="fa-solid" :class="showPass ? 'fa-eye-slash' : 'fa-eye'"></i>
                                    <span x-text="showPass ? 'Hide' : 'Show'"></span>
                                </button>
                            </div>
                            <input :type="showPass ? 'text' : 'password'" name="password" x-model="form.password" @input="clearError('password')" :data-has-error="!!errors.password"
                                   :class="errors.password ? 'border-rose-500 ring-1 ring-rose-500/50 bg-rose-500/5' : 'border-slate-800 focus:border-indigo-500'"
                                   class="w-full px-3 py-2.5 bg-slate-950 border rounded-xl text-white focus:outline-none transition-colors">
                            <template x-if="errors.password">
                                <p class="text-rose-400 text-[11px] font-semibold mt-1 flex items-center space-x-1.5">
                                    <i class="fa-solid fa-circle-exclamation text-rose-400 text-xs"></i>
                                    <span x-text="errors.password"></span>
                                </p>
                            </template>
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-300 mb-1">Phone / Mobile Number</label>
                            <input type="text" name="phone" x-model="form.phone" placeholder="+1 (555) 019-2834" class="w-full px-3 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-white focus:outline-none focus:border-indigo-500">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div>
                            <label class="block font-semibold text-slate-300 mb-1">
                                CNIC (National ID) <span class="text-rose-400 font-bold">*</span>
                                <span class="text-[10px] text-slate-500 font-normal ml-1 font-mono">(XXXXX-XXXXXXX-X)</span>
                            </label>
                            <input type="text" name="national_id_number" x-model="form.national_id_number"
                                   @input="formatCnicInput()"
                                   maxlength="15"
                                   pattern="^\d{5}-\d{7}-\d{1}$"
                                   title="CNIC must follow the 13-digit pattern: XXXXX-XXXXXXX-X (e.g. 42101-1234567-1)"
                                   placeholder="XXXXX-XXXXXXX-X (e.g. 42101-1234567-1)"
                                   :data-has-error="!!errors.national_id_number"
                                   :class="errors.national_id_number ? 'border-rose-500 ring-1 ring-rose-500/50 bg-rose-500/5' : 'border-slate-800 focus:border-indigo-500'"
                                   class="w-full px-3 py-2.5 bg-slate-950 border rounded-xl text-white font-mono tracking-wider focus:outline-none transition-colors">
                            <template x-if="errors.national_id_number">
                                <p class="text-rose-400 text-[11px] font-semibold mt-1 flex items-center space-x-1.5">
                                    <i class="fa-solid fa-circle-exclamation text-rose-400 text-xs"></i>
                                    <span x-text="errors.national_id_number"></span>
                                </p>
                            </template>
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-300 mb-1">Joining Date</label>
                            <div class="relative group" x-data="{
                                picker: null,
                                init() {
                                    this.$watch('form.date_of_joining', (val) => {
                                        if (this.picker && val) {
                                            this.picker.setDate(val, false);
                                        }
                                    });
                                    this.$watch('addModalOpen', (isOpen) => {
                                        if (isOpen && this.picker) {
                                            let val = this.form && this.form.date_of_joining ? this.form.date_of_joining : 'today';
                                            this.picker.setDate(val, false);
                                        }
                                    });
                                    this.$nextTick(() => {
                                        if (window.flatpickr) {
                                            this.picker = flatpickr(this.$refs.addDateOfJoining, {
                                                dateFormat: 'Y-m-d',
                                                altInput: true,
                                                altFormat: 'M d, Y',
                                                altInputClass: 'w-full pl-3.5 pr-10 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-white font-medium focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all cursor-pointer group-hover:border-slate-700 shadow-sm',
                                                defaultDate: form.date_of_joining || 'today',
                                                allowInput: true,
                                                static: true,
                                                monthSelectorType: 'dropdown',
                                                onChange: (selectedDates, dateStr) => {
                                                    form.date_of_joining = dateStr;
                                                }
                                            });
                                        }
                                    });
                                }
                            }">
                                <input type="text"
                                       name="date_of_joining"
                                       x-ref="addDateOfJoining"
                                       x-model="form.date_of_joining"
                                       placeholder="Select Joining Date"
                                       class="w-full pl-3.5 pr-10 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-white font-medium focus:outline-none focus:border-indigo-500 cursor-pointer">
                                <div class="absolute inset-y-0 right-0 pr-3.5 flex items-center pointer-events-none text-indigo-400 group-hover:text-indigo-300 transition-colors">
                                    <i class="fa-solid fa-calendar-days text-sm"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-300 mb-1">Residential Address</label>
                        <input type="text" name="address" x-model="form.address" placeholder="e.g. 123 Main Street, Suite 400, New York, NY" class="w-full px-3 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-white focus:outline-none focus:border-indigo-500">
                    </div>

                    <!-- Step Error Alert & Action -->
                    <div class="pt-2 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-t border-slate-800/60">
                        <div>
                            <div x-show="stepErrorMsg && addTab === 'personal'" x-transition class="flex items-center space-x-2 text-rose-400 text-xs font-semibold py-1">
                                <i class="fa-solid fa-circle-exclamation shrink-0"></i>
                                <span x-text="stepErrorMsg"></span>
                            </div>
                        </div>
                        <div class="sm:ml-auto">
                            <button type="button" @click="goToTab('job')" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white font-bold rounded-xl flex items-center space-x-2 shadow-md shadow-indigo-600/20 transition-all">
                                <span>Next: Job & Role</span>
                                <i class="fa-solid fa-arrow-right text-xs"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Tab 2: Job & Role -->
                <div x-show="addTab === 'job'" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div>
                            <label class="block font-semibold text-slate-300 mb-1">
                                System Role <span class="text-rose-400 font-bold">*</span>
                            </label>
                            <select name="role_id" x-model="form.role_id" @change="clearError('role_id')" :data-has-error="!!errors.role_id"
                                    :class="errors.role_id ? 'border-rose-500 ring-1 ring-rose-500/50 bg-rose-500/5' : 'border-slate-800 focus:border-indigo-500'"
                                    class="w-full px-3 py-2.5 bg-slate-950 border rounded-xl text-white">
                                <option value="">-- Select System Role --</option>
                                @foreach($roles as $role)
                                    <option value="{{ $role->id }}">{{ $role->label }}</option>
                                @endforeach
                            </select>
                            <template x-if="errors.role_id">
                                <p class="text-rose-400 text-[11px] font-semibold mt-1 flex items-center space-x-1.5">
                                    <i class="fa-solid fa-circle-exclamation text-rose-400 text-xs"></i>
                                    <span x-text="errors.role_id"></span>
                                </p>
                            </template>
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-300 mb-1">
                                Company / Organization <span class="text-rose-400 font-bold">*</span>
                            </label>
                            <select name="organization_id" x-model="form.organization_id" @change="clearError('organization_id')" :data-has-error="!!errors.organization_id"
                                    :class="errors.organization_id ? 'border-rose-500 ring-1 ring-rose-500/50 bg-rose-500/5' : 'border-slate-800 focus:border-indigo-500'"
                                    class="w-full px-3 py-2.5 bg-slate-950 border rounded-xl text-white">
                                <option value="">-- Select Organization --</option>
                                @foreach($organizations as $org)
                                    <option value="{{ $org->id }}">{{ $org->name }} ({{ $org->currency }})</option>
                                @endforeach
                            </select>
                            <template x-if="errors.organization_id">
                                <p class="text-rose-400 text-[11px] font-semibold mt-1 flex items-center space-x-1.5">
                                    <i class="fa-solid fa-circle-exclamation text-rose-400 text-xs"></i>
                                    <span x-text="errors.organization_id"></span>
                                </p>
                            </template>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                        <div>
                            <label class="block font-semibold text-slate-300 mb-1">Department</label>
                            <select name="department_id" x-model="form.department_id" class="w-full px-3 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-white">
                                <option value="">-- Select Department --</option>
                                @foreach($departments as $dept)
                                    <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-300 mb-1">Designation</label>
                            <select name="designation_id" x-model="form.designation_id" class="w-full px-3 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-white">
                                <option value="">-- Select Designation --</option>
                                @foreach($designations as $desig)
                                    <option value="{{ $desig->id }}">{{ $desig->title }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-300 mb-1">Assigned Team</label>
                            <select name="team_id" x-model="form.team_id" class="w-full px-3 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-white">
                                <option value="">-- No Team Assigned --</option>
                                @foreach($teams as $tm)
                                    <option value="{{ $tm->id }}">{{ $tm->name }} ({{ $tm->leader ? 'Lead: ' . $tm->leader->name : 'No Leader' }})</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div>
                            <label class="block font-semibold text-slate-300 mb-1">
                                Employment Type <span class="text-rose-400 font-bold">*</span>
                            </label>
                            <select name="employment_type" x-model="form.employment_type" @change="clearError('employment_type')" :data-has-error="!!errors.employment_type"
                                    :class="errors.employment_type ? 'border-rose-500 ring-1 ring-rose-500/50 bg-rose-500/5' : 'border-slate-800 focus:border-indigo-500'"
                                    class="w-full px-3 py-2.5 bg-slate-950 border rounded-xl text-white">
                                <option value="">-- Select Type --</option>
                                <option value="FULL_TIME">Full Time</option>
                                <option value="PART_TIME">Part Time</option>
                                <option value="CONTRACT">Contract</option>
                                <option value="INTERN">Intern</option>
                            </select>
                            <template x-if="errors.employment_type">
                                <p class="text-rose-400 text-[11px] font-semibold mt-1 flex items-center space-x-1.5">
                                    <i class="fa-solid fa-circle-exclamation text-rose-400 text-xs"></i>
                                    <span x-text="errors.employment_type"></span>
                                </p>
                            </template>
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-300 mb-1">Assigned Company Payroll Tier</label>
                            <select name="payroll_id" x-model="form.payroll_id" class="w-full px-3 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-white">
                                <option value="">-- Assign Later --</option>
                                @foreach($payrolls as $pr)
                                    <option value="{{ $pr->id }}">[{{ $pr->organization->name ?? 'Org' }}] {{ $pr->name }} ({{ $pr->pay_frequency }})</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <!-- Work Shift Assignment -->
                    <div class="p-3.5 bg-slate-950/80 rounded-2xl border border-slate-800 space-y-2.5">
                        <div class="flex items-center justify-between">
                            <label class="block font-semibold text-slate-300">
                                <i class="fa-solid fa-business-time text-emerald-400 mr-1.5"></i>
                                <span>Assigned Work Shift Schedule</span>
                            </label>
                            <div class="flex items-center space-x-2">
                                <button type="button" @click="$dispatch('open-quick-shift', { mode: 'create' })" class="text-[11px] font-bold text-emerald-400 hover:text-emerald-300 flex items-center space-x-1 bg-emerald-500/10 hover:bg-emerald-500/20 px-2.5 py-1 rounded-lg border border-emerald-500/20 transition-all shadow-sm">
                                    <i class="fa-solid fa-plus text-[10px]"></i>
                                    <span>+ Add New Shift</span>
                                </button>
                                <button type="button" x-show="form.shift_id" @click="$dispatch('open-quick-shift', { mode: 'edit', shift_id: form.shift_id })" class="text-[11px] font-bold text-indigo-400 hover:text-indigo-300 flex items-center space-x-1 bg-indigo-500/10 hover:bg-indigo-500/20 px-2.5 py-1 rounded-lg border border-indigo-500/20 transition-all">
                                    <i class="fa-solid fa-pen-to-square text-[10px]"></i>
                                    <span>Edit Shift</span>
                                </button>
                            </div>
                        </div>

                        <select name="shift_id" x-model="form.shift_id" class="w-full px-3 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-white focus:outline-none focus:border-emerald-500 transition-colors">
                            <option value="">-- No Specific Shift (Default 09:00 AM - 06:00 PM) --</option>
                            <template x-for="s in shiftsList" :key="s.id">
                                <option :value="s.id" :selected="form.shift_id === s.id" x-text="s.name + ' (' + (s.start_time ? s.start_time.substring(0, 5) : '09:00') + ' - ' + (s.end_time ? s.end_time.substring(0, 5) : '18:00') + (s.flexible ? ', Flexible' : '') + ')'"></option>
                            </template>
                        </select>
                        <p class="text-[11px] text-slate-500">Determines working hours, late arrival grace period, and flexible shift status.</p>
                    </div>

                    <!-- Step Error Alert & Action -->
                    <div class="pt-2 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-t border-slate-800/60">
                        <div class="flex items-center space-x-3">
                            <button type="button" @click="goToTab('personal')" class="px-4 py-2 bg-slate-800 text-slate-300 font-semibold rounded-xl hover:bg-slate-700 transition-colors">Back</button>
                            <div x-show="stepErrorMsg && addTab === 'job'" x-transition class="flex items-center space-x-2 text-rose-400 text-xs font-semibold">
                                <i class="fa-solid fa-circle-exclamation shrink-0"></i>
                                <span x-text="stepErrorMsg"></span>
                            </div>
                        </div>
                        <div>
                            <button type="button" @click="goToTab('banking')" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white font-bold rounded-xl flex items-center space-x-2 shadow-md shadow-indigo-600/20 transition-all">
                                <span>Next: Bank Account</span>
                                <i class="fa-solid fa-arrow-right text-xs"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Tab 3: Bank & Disbursement Details -->
                <div x-show="addTab === 'banking'" class="space-y-4">
                    <div class="p-3.5 bg-slate-950 border border-slate-800 rounded-2xl flex items-center space-x-3">
                        <i class="fa-solid fa-building-columns text-indigo-400 text-base shrink-0"></i>
                        <p class="text-[11px] text-slate-400">Employee account details entered here will automatically populate their official <strong>Salary Slips</strong> and bank transfer vouchers.</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div>
                            <label class="block font-semibold text-slate-300 mb-1">Disbursement Method</label>
                            <select name="payment_method" x-model="form.payment_method" class="w-full px-3 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-white">
                                <option value="BANK_TRANSFER">Bank Transfer (Direct Deposit)</option>
                                <option value="CASH">Cash in Hand</option>
                                <option value="CHEQUE">Corporate Cheque</option>
                            </select>
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-300 mb-1">Bank Name</label>
                            <input type="text" name="bank_name" x-model="form.bank_name" placeholder="e.g. Global Bank / Silicon Valley Bank" class="w-full px-3 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-white focus:outline-none focus:border-indigo-500">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div>
                            <label class="block font-semibold text-slate-300 mb-1">Account Holder / Title Name</label>
                            <input type="text" name="bank_account_holder_name" x-model="form.bank_account_holder_name" placeholder="e.g. John Doe" class="w-full px-3 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-white focus:outline-none focus:border-indigo-500">
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-300 mb-1">Bank Account Number / IBAN</label>
                            <input type="text" name="bank_account_number" x-model="form.bank_account_number" placeholder="e.g. US1234567890123456" class="w-full px-3 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-white font-mono focus:outline-none focus:border-indigo-500">
                        </div>
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-300 mb-1">Bank Branch Name & Code</label>
                        <input type="text" name="bank_branch_name" x-model="form.bank_branch_name" placeholder="e.g. Downtown Metro Branch (Code: 0142)" class="w-full px-3 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-white focus:outline-none focus:border-indigo-500">
                    </div>

                    <div class="pt-2 flex justify-between border-t border-slate-800/60">
                        <button type="button" @click="goToTab('job')" class="px-4 py-2 bg-slate-800 text-slate-300 font-semibold rounded-xl hover:bg-slate-700 transition-colors">Back</button>
                        <button type="button" @click="goToTab('salary')" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white font-bold rounded-xl flex items-center space-x-2 shadow-md shadow-indigo-600/20 transition-all">
                            <span>Next: Salary Structure</span>
                            <i class="fa-solid fa-arrow-right text-xs"></i>
                        </button>
                    </div>
                </div>

                <!-- Tab 4: Individual Salary Structure & Confidential Cash-in-Hand -->
                <div x-show="addTab === 'salary'" class="space-y-4">
                    
                    <!-- Section A: Official Taxable Bank Salary -->
                    <div class="p-3 bg-indigo-500/10 border border-indigo-500/20 rounded-2xl flex items-center justify-between">
                        <div class="flex items-center space-x-2">
                            <i class="fa-solid fa-building-columns text-indigo-400"></i>
                            <span class="font-bold text-white text-xs">1. Official Bank Salary (Reported on Payslip & Tax Filings)</span>
                        </div>
                        <span class="text-[10px] px-2 py-0.5 rounded bg-indigo-500/20 text-indigo-300 font-bold">Tax Filer Slabs</span>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                        <div>
                            <label class="block font-semibold text-slate-300 mb-1">
                                Basic Salary (PKR) <span class="text-rose-400 font-bold">*</span>
                            </label>
                            <input type="number" step="0.01" name="base_salary" x-model.number="baseSal" @input="clearError('base_salary')" :data-has-error="!!errors.base_salary"
                                   :class="errors.base_salary ? 'border-rose-500 ring-1 ring-rose-500/50 bg-rose-500/5' : 'border-slate-800 focus:border-indigo-500'"
                                   class="w-full px-3 py-2.5 bg-slate-950 border rounded-xl text-white font-bold focus:outline-none transition-colors">
                            <template x-if="errors.base_salary">
                                <p class="text-rose-400 text-[11px] font-semibold mt-1 flex items-center space-x-1.5">
                                    <i class="fa-solid fa-circle-exclamation text-rose-400 text-xs"></i>
                                    <span x-text="errors.base_salary"></span>
                                </p>
                            </template>
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-300 mb-1">Fuel Allowance (PKR)</label>
                            <input type="number" step="0.01" name="fuel_allowance" x-model.number="fuelAllow" class="w-full px-3 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-white focus:outline-none focus:border-indigo-500">
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-300 mb-1">Special / Other Allowances</label>
                            <input type="number" step="0.01" name="special_allowance" x-model.number="specialAllow" class="w-full px-3 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-white focus:outline-none focus:border-indigo-500">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div>
                            <label class="block font-semibold text-slate-300 mb-1">EOBI / Statutory Deductions (PKR)</label>
                            <input type="number" step="0.01" name="eobi_deduction" x-model.number="eobiDed" class="w-full px-3 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-white focus:outline-none focus:border-indigo-500">
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-300 mb-1">Other Regular Deductions (PKR)</label>
                            <input type="number" step="0.01" name="deductions" x-model.number="otherDed" class="w-full px-3 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-white focus:outline-none focus:border-indigo-500">
                        </div>
                    </div>

                    <!-- Section B: Confidential Employer Cash-in-Hand (Off-the-Record) -->
                    <div class="p-3.5 bg-amber-500/10 border border-amber-500/30 rounded-2xl space-y-2">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-2 text-amber-400 font-bold">
                                <i class="fa-solid fa-user-shield text-sm"></i>
                                <span>2. Confidential Cash-in-Hand (Employer Only &bull; Off-the-Record)</span>
                            </div>
                            <span class="text-[10px] px-2 py-0.5 rounded bg-amber-400/20 text-amber-300 font-extrabold uppercase">Never on Payslip</span>
                        </div>
                        <p class="text-[11px] text-slate-300 leading-relaxed">
                            Keeps the employee's official taxable salary in a lower tax bracket while paying additional remuneration directly in cash. <strong>This amount is completely hidden from the employee portal and excluded from official salary slips.</strong>
                        </p>
                        <div>
                            <label class="block font-semibold text-slate-200 mb-1">Monthly Cash-in-Hand Amount (PKR)</label>
                            <input type="number" step="0.01" name="cash_allowance" x-model.number="cashAllow" placeholder="e.g. 50000" class="w-full px-3 py-2.5 bg-slate-950 border border-amber-500/30 rounded-xl text-amber-300 font-bold focus:outline-none focus:border-amber-400">
                        </div>
                    </div>

                    <!-- Live Monthly Salary Preview Box -->
                    <div class="p-4 bg-slate-950 border border-slate-800 rounded-2xl space-y-2 text-xs">
                        <div class="flex items-center justify-between text-slate-400">
                            <span>Official Bank Salary (Reported on Payslip):</span>
                            <span class="font-bold text-white" x-text="'PKR ' + ((Number(baseSal) || 0) + (Number(fuelAllow) || 0) + (Number(specialAllow) || 0)).toLocaleString()"></span>
                        </div>
                        <div class="flex items-center justify-between text-slate-400">
                            <span>Official Deductions (EOBI / Statutory):</span>
                            <span class="font-bold text-rose-400" x-text="'- PKR ' + ((Number(eobiDed) || 0) + (Number(otherDed) || 0)).toLocaleString()"></span>
                        </div>
                        <div class="flex items-center justify-between text-slate-400">
                            <span>Official Net Bank Deposit:</span>
                            <span class="font-bold text-indigo-300" x-text="'PKR ' + (((Number(baseSal) || 0) + (Number(fuelAllow) || 0) + (Number(specialAllow) || 0)) - ((Number(eobiDed) || 0) + (Number(otherDed) || 0))).toLocaleString()"></span>
                        </div>
                        <div class="flex items-center justify-between text-amber-400 pt-1 border-t border-slate-800/80">
                            <span class="font-bold"><i class="fa-solid fa-hand-holding-dollar mr-1"></i> Confidential Cash Remuneration:</span>
                            <span class="font-bold" x-text="'+ PKR ' + (Number(cashAllow) || 0).toLocaleString()"></span>
                        </div>
                        <div class="pt-2 border-t border-slate-800 flex items-center justify-between text-sm">
                            <span class="font-black text-white">Total Employer Monthly Outflow:</span>
                            <span class="font-black text-emerald-400" x-text="'PKR ' + ((((Number(baseSal) || 0) + (Number(fuelAllow) || 0) + (Number(specialAllow) || 0)) - ((Number(eobiDed) || 0) + (Number(otherDed) || 0))) + (Number(cashAllow) || 0)).toLocaleString()"></span>
                        </div>
                    </div>

                    <div class="pt-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-t border-slate-800">
                        <div class="flex items-center space-x-3">
                            <button type="button" @click="goToTab('banking')" class="px-4 py-2 bg-slate-800 text-slate-300 font-semibold rounded-xl hover:bg-slate-700 transition-colors">Back</button>
                            <div x-show="stepErrorMsg && addTab === 'salary'" x-transition class="flex items-center space-x-2 text-rose-400 text-xs font-semibold">
                                <i class="fa-solid fa-circle-exclamation shrink-0"></i>
                                <span x-text="stepErrorMsg"></span>
                            </div>
                        </div>
                        <div class="flex items-center space-x-3">
                            <button type="button" @click="closeModal()" class="px-4 py-2.5 bg-slate-800 text-slate-300 font-semibold rounded-xl hover:bg-slate-700 transition-colors">Cancel</button>
                            <button type="submit" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white font-black rounded-xl shadow-lg shadow-indigo-600/30 transition-all flex items-center space-x-2">
                                <i class="fa-solid fa-user-check"></i>
                                <span>Save & Register Employee</span>
                            </button>
                        </div>
                    </div>
                </div>

            </form>
        </div>
    </div>

    <!-- Edit Employee Modal -->
    <div x-show="editModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-md overflow-y-auto" x-data="{ editTab: 'personal' }">
        <div @click.away="if (!$event.target.closest('.flatpickr-calendar')) editModalOpen = false" class="bg-slate-900 border border-slate-800 rounded-3xl max-w-2xl w-full p-6 md:p-8 space-y-6 shadow-2xl max-h-[92vh] overflow-y-auto">
            
            <!-- Modal Header -->
            <div class="flex items-center justify-between border-b border-slate-800 pb-4">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-2xl bg-indigo-600/20 border border-indigo-500/30 flex items-center justify-center text-indigo-400">
                        <i class="fa-solid fa-user-pen text-base"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-black text-white tracking-tight">Edit Employee Profile</h3>
                        <p class="text-xs text-slate-400" x-text="'Updating profile for ' + (activeEmp.name || 'Employee')"></p>
                    </div>
                </div>
                <button @click="editModalOpen = false" class="text-slate-400 hover:text-white p-2 hover:bg-slate-800 rounded-xl transition-colors"><i class="fa-solid fa-xmark text-sm"></i></button>
            </div>

            <!-- Tab Navigation Buttons -->
            <div class="flex items-center space-x-2 bg-slate-950 p-1.5 rounded-2xl border border-slate-800 text-xs font-bold overflow-x-auto">
                <button type="button" @click="editTab = 'personal'" :class="editTab === 'personal' ? 'bg-indigo-600 text-white shadow-md' : 'text-slate-400 hover:text-slate-200'" class="px-3.5 py-2 rounded-xl transition-all flex items-center space-x-2 whitespace-nowrap">
                    <i class="fa-solid fa-user text-xs"></i>
                    <span>1. Identity & Contact</span>
                </button>
                <button type="button" @click="editTab = 'job'" :class="editTab === 'job' ? 'bg-indigo-600 text-white shadow-md' : 'text-slate-400 hover:text-slate-200'" class="px-3.5 py-2 rounded-xl transition-all flex items-center space-x-2 whitespace-nowrap">
                    <i class="fa-solid fa-briefcase text-xs"></i>
                    <span>2. Job & Role</span>
                </button>
                <button type="button" @click="editTab = 'banking'" :class="editTab === 'banking' ? 'bg-indigo-600 text-white shadow-md' : 'text-slate-400 hover:text-slate-200'" class="px-3.5 py-2 rounded-xl transition-all flex items-center space-x-2 whitespace-nowrap">
                    <i class="fa-solid fa-building-columns text-xs"></i>
                    <span>3. Bank Account</span>
                </button>
                <button type="button" @click="editTab = 'salary'" :class="editTab === 'salary' ? 'bg-indigo-600 text-white shadow-md' : 'text-slate-400 hover:text-slate-200'" class="px-3.5 py-2 rounded-xl transition-all flex items-center space-x-2 whitespace-nowrap">
                    <i class="fa-solid fa-money-check-dollar text-xs"></i>
                    <span>4. Salary Structure</span>
                </button>
            </div>

            <form :action="'{{ url('employees') }}/' + activeEmp.id" method="POST" enctype="multipart/form-data" class="space-y-4 text-xs">
                @csrf
                @method('PUT')

                <!-- Edit Tab 1: Identity & Contact -->
                <div x-show="editTab === 'personal'" class="space-y-4">
                    <!-- Current Avatar Preview & Change -->
                    <div class="bg-slate-950/80 p-3 rounded-2xl border border-slate-800 flex items-center space-x-4">
                        <template x-if="activeEmp.avatar_url">
                            <img :src="activeEmp.avatar_url" class="w-12 h-12 rounded-xl object-cover ring-2 ring-indigo-500/40 shrink-0">
                        </template>
                        <template x-if="!activeEmp.avatar_url">
                            <div class="w-12 h-12 rounded-xl bg-slate-900 border border-slate-800 flex items-center justify-center text-slate-400 shrink-0">
                                <i class="fa-solid fa-camera text-base text-indigo-400"></i>
                            </div>
                        </template>
                        <div class="flex-1 min-w-0">
                            <label class="block font-semibold text-slate-200 mb-0.5">Change Profile Picture</label>
                            <input type="file" name="avatar" accept="image/*" class="w-full text-[11px] text-slate-400 file:mr-3 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:text-[11px] file:font-bold file:bg-indigo-600 file:text-white hover:file:bg-indigo-500 cursor-pointer">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div>
                            <label class="block font-semibold text-slate-300 mb-1">Full Name *</label>
                            <input type="text" name="name" x-model="activeEmp.name" required class="w-full px-3 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-white focus:outline-none focus:border-indigo-500">
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-300 mb-1">Corporate Email Address *</label>
                            <input type="email" name="email" x-model="activeEmp.email" required class="w-full px-3 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-white focus:outline-none focus:border-indigo-500">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div x-data="{ showEditPass: false }">
                            <div class="flex items-center justify-between mb-1">
                                <label class="block font-semibold text-slate-300">Reset Password (Leave blank to keep current)</label>
                                <button type="button" @click="showEditPass = !showEditPass" class="text-[10px] text-indigo-400 font-semibold" x-text="showEditPass ? 'Hide' : 'Show'"></button>
                            </div>
                            <input :type="showEditPass ? 'text' : 'password'" name="password" placeholder="Enter new password to reset..." class="w-full px-3 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-white focus:outline-none focus:border-indigo-500">
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-300 mb-1">Phone / Mobile Number</label>
                            <input type="text" name="phone" x-model="activeEmp.phone" placeholder="+1 (555) 019-2834" class="w-full px-3 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-white focus:outline-none focus:border-indigo-500">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div>
                            <label class="block font-semibold text-slate-300 mb-1">
                                CNIC (National ID) <span class="text-rose-400 font-bold">*</span>
                                <span class="text-[10px] text-slate-500 font-normal ml-1 font-mono">(XXXXX-XXXXXXX-X)</span>
                            </label>
                            <input type="text" name="national_id_number" x-model="activeEmp.national_id_number"
                                   @input="activeEmp.national_id_number = formatPakistaniCnic($event.target.value)"
                                   maxlength="15"
                                   pattern="^\d{5}-\d{7}-\d{1}$"
                                   title="CNIC must follow the 13-digit pattern: XXXXX-XXXXXXX-X (e.g. 42101-1234567-1)"
                                   required
                                   placeholder="XXXXX-XXXXXXX-X (e.g. 42101-1234567-1)"
                                   class="w-full px-3 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-white font-mono tracking-wider focus:outline-none focus:border-indigo-500">
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-300 mb-1">Joining Date</label>
                            <div class="relative group" x-data="{
                                picker: null,
                                init() {
                                    this.$watch('activeEmp.date_of_joining', (val) => {
                                        if (this.picker) {
                                            this.picker.setDate(val ? val.substring(0, 10) : '', false);
                                        }
                                    });
                                    this.$watch('editModalOpen', (isOpen) => {
                                        if (isOpen && this.picker) {
                                            let val = this.activeEmp && this.activeEmp.date_of_joining ? this.activeEmp.date_of_joining.substring(0, 10) : '';
                                            this.picker.setDate(val, false);
                                        }
                                    });
                                    this.$watch('activeEmp', (newEmp) => {
                                        if (this.picker && newEmp) {
                                            let val = newEmp.date_of_joining ? newEmp.date_of_joining.substring(0, 10) : '';
                                            this.picker.setDate(val, false);
                                        }
                                    });
                                    this.$nextTick(() => {
                                        if (window.flatpickr) {
                                            this.picker = flatpickr(this.$refs.editDateOfJoining, {
                                                dateFormat: 'Y-m-d',
                                                altInput: true,
                                                altFormat: 'M d, Y',
                                                altInputClass: 'w-full pl-3.5 pr-10 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-white font-medium focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all cursor-pointer group-hover:border-slate-700 shadow-sm',
                                                defaultDate: activeEmp.date_of_joining ? activeEmp.date_of_joining.substring(0, 10) : '',
                                                allowInput: true,
                                                static: true,
                                                monthSelectorType: 'dropdown',
                                                onChange: (selectedDates, dateStr) => {
                                                    activeEmp.date_of_joining = dateStr;
                                                }
                                            });
                                        }
                                    });
                                }
                            }">
                                <input type="text"
                                       name="date_of_joining"
                                       x-ref="editDateOfJoining"
                                       :value="activeEmp.date_of_joining ? activeEmp.date_of_joining.substring(0, 10) : ''"
                                       placeholder="Select Joining Date"
                                       class="w-full pl-3.5 pr-10 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-white font-medium focus:outline-none focus:border-indigo-500 cursor-pointer">
                                <div class="absolute inset-y-0 right-0 pr-3.5 flex items-center pointer-events-none text-indigo-400 group-hover:text-indigo-300 transition-colors">
                                    <i class="fa-solid fa-calendar-days text-sm"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-300 mb-1">Residential Address</label>
                        <input type="text" name="address" x-model="activeEmp.address" placeholder="e.g. 123 Main Street, Suite 400, New York, NY" class="w-full px-3 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-white focus:outline-none focus:border-indigo-500">
                    </div>

                    <div class="flex items-center space-x-2 pt-1">
                        <input type="checkbox" id="edit_is_active" name="is_active" value="1" :checked="activeEmp.is_active" class="rounded bg-slate-950 border-slate-800 text-indigo-600 focus:ring-indigo-500">
                        <label for="edit_is_active" class="text-xs font-semibold text-slate-300">Active Employee Account</label>
                    </div>

                    <div class="pt-2 flex justify-end">
                        <button type="button" @click="editTab = 'job'" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-white font-bold rounded-xl flex items-center space-x-2">
                            <span>Next: Job & Role</span>
                            <i class="fa-solid fa-arrow-right text-xs"></i>
                        </button>
                    </div>
                </div>

                <!-- Edit Tab 2: Job & Role -->
                <div x-show="editTab === 'job'" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div>
                            <label class="block font-semibold text-slate-300 mb-1">System Role *</label>
                            <select name="role_id" x-model="activeEmp.role_id" required class="w-full px-3 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-white">
                                @foreach($roles as $role)
                                    <option value="{{ $role->id }}">{{ $role->label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-300 mb-1">Company / Organization *</label>
                            <select name="organization_id" x-model="activeEmp.organization_id" required class="w-full px-3 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-white">
                                @foreach($organizations as $org)
                                    <option value="{{ $org->id }}">{{ $org->name }} ({{ $org->currency }})</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                        <div>
                            <label class="block font-semibold text-slate-300 mb-1">Department</label>
                            <select name="department_id" x-model="activeEmp.department_id" class="w-full px-3 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-white">
                                <option value="">-- Select Department --</option>
                                @foreach($departments as $dept)
                                    <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-300 mb-1">Designation</label>
                            <select name="designation_id" x-model="activeEmp.designation_id" class="w-full px-3 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-white">
                                <option value="">-- Select Designation --</option>
                                @foreach($designations as $desig)
                                    <option value="{{ $desig->id }}">{{ $desig->title }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-300 mb-1">Assigned Team</label>
                            <select name="team_id" x-model="activeEmp.team_id" class="w-full px-3 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-white">
                                <option value="">-- No Team Assigned --</option>
                                @foreach($teams as $tm)
                                    <option value="{{ $tm->id }}">{{ $tm->name }} ({{ $tm->leader ? 'Lead: ' . $tm->leader->name : 'No Leader' }})</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div>
                            <label class="block font-semibold text-slate-300 mb-1">Employment Type *</label>
                            <select name="employment_type" x-model="activeEmp.employment_type" required class="w-full px-3 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-white">
                                <option value="FULL_TIME">Full Time</option>
                                <option value="PART_TIME">Part Time</option>
                                <option value="CONTRACT">Contract</option>
                                <option value="INTERN">Intern</option>
                            </select>
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-300 mb-1">Assigned Company Payroll Tier</label>
                            <select name="payroll_id" x-model="activeEmp.payroll_id" class="w-full px-3 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-white">
                                <option value="">-- Unassigned --</option>
                                @foreach($payrolls as $pr)
                                    <option value="{{ $pr->id }}">[{{ $pr->organization->name ?? 'Org' }}] {{ $pr->name }} ({{ $pr->pay_frequency }})</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <!-- Work Shift Assignment -->
                    <div class="p-3.5 bg-slate-950/80 rounded-2xl border border-slate-800 space-y-2.5">
                        <div class="flex items-center justify-between">
                            <label class="block font-semibold text-slate-300">
                                <i class="fa-solid fa-business-time text-emerald-400 mr-1.5"></i>
                                <span>Assigned Work Shift Schedule</span>
                            </label>
                            <div class="flex items-center space-x-2">
                                <button type="button" @click="$dispatch('open-quick-shift', { mode: 'create' })" class="text-[11px] font-bold text-emerald-400 hover:text-emerald-300 flex items-center space-x-1 bg-emerald-500/10 hover:bg-emerald-500/20 px-2.5 py-1 rounded-lg border border-emerald-500/20 transition-all shadow-sm">
                                    <i class="fa-solid fa-plus text-[10px]"></i>
                                    <span>+ Add New Shift</span>
                                </button>
                                <button type="button" x-show="activeEmp.shift_id" @click="$dispatch('open-quick-shift', { mode: 'edit', shift_id: activeEmp.shift_id })" class="text-[11px] font-bold text-indigo-400 hover:text-indigo-300 flex items-center space-x-1 bg-indigo-500/10 hover:bg-indigo-500/20 px-2 py-1 rounded-lg border border-indigo-500/20 transition-all">
                                    <i class="fa-solid fa-pen-to-square text-[10px]"></i>
                                    <span>Edit Shift</span>
                                </button>
                            </div>
                        </div>

                        <select name="shift_id" x-model="activeEmp.shift_id" class="w-full px-3 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-white focus:outline-none focus:border-emerald-500 transition-colors">
                            <option value="">-- No Specific Shift (Default 09:00 AM - 06:00 PM) --</option>
                            <template x-for="s in shiftsList" :key="s.id">
                                <option :value="s.id" :selected="activeEmp.shift_id === s.id" x-text="s.name + ' (' + (s.start_time ? s.start_time.substring(0, 5) : '09:00') + ' - ' + (s.end_time ? s.end_time.substring(0, 5) : '18:00') + (s.flexible ? ', Flexible' : '') + ')'"></option>
                            </template>
                        </select>
                        <p class="text-[11px] text-slate-500">Determines working hours, late arrival grace period, and flexible shift status.</p>
                    </div>

                    <div class="pt-2 flex justify-between">
                        <button type="button" @click="editTab = 'personal'" class="px-4 py-2 bg-slate-800 text-slate-300 font-semibold rounded-xl">Back</button>
                        <button type="button" @click="editTab = 'banking'" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-white font-bold rounded-xl flex items-center space-x-2">
                            <span>Next: Bank Account</span>
                            <i class="fa-solid fa-arrow-right text-xs"></i>
                        </button>
                    </div>
                </div>

                <!-- Edit Tab 3: Bank & Disbursement Details -->
                <div x-show="editTab === 'banking'" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div>
                            <label class="block font-semibold text-slate-300 mb-1">Disbursement Method</label>
                            <select name="payment_method" x-model="activeEmp.payment_method" class="w-full px-3 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-white">
                                <option value="BANK_TRANSFER">Bank Transfer (Direct Deposit)</option>
                                <option value="CASH">Cash in Hand</option>
                                <option value="CHEQUE">Corporate Cheque</option>
                            </select>
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-300 mb-1">Bank Name</label>
                            <input type="text" name="bank_name" x-model="activeEmp.bank_name" placeholder="e.g. Global Bank / Silicon Valley Bank" class="w-full px-3 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-white focus:outline-none focus:border-indigo-500">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div>
                            <label class="block font-semibold text-slate-300 mb-1">Account Holder / Title Name</label>
                            <input type="text" name="bank_account_holder_name" x-model="activeEmp.bank_account_holder_name" placeholder="e.g. John Doe" class="w-full px-3 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-white focus:outline-none focus:border-indigo-500">
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-300 mb-1">Bank Account Number / IBAN</label>
                            <input type="text" name="bank_account_number" x-model="activeEmp.bank_account_number" placeholder="e.g. US1234567890123456" class="w-full px-3 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-white font-mono focus:outline-none focus:border-indigo-500">
                        </div>
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-300 mb-1">Bank Branch Name & Code</label>
                        <input type="text" name="bank_branch_name" x-model="activeEmp.bank_branch_name" placeholder="e.g. Downtown Metro Branch (Code: 0142)" class="w-full px-3 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-white focus:outline-none focus:border-indigo-500">
                    </div>

                    <div class="pt-2 flex justify-between">
                        <button type="button" @click="editTab = 'job'" class="px-4 py-2 bg-slate-800 text-slate-300 font-semibold rounded-xl">Back</button>
                        <button type="button" @click="editTab = 'salary'" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-white font-bold rounded-xl flex items-center space-x-2">
                            <span>Next: Salary Structure</span>
                            <i class="fa-solid fa-arrow-right text-xs"></i>
                        </button>
                    </div>
                </div>

                <!-- Edit Tab 4: Individual Salary Structure & Confidential Cash-in-Hand -->
                <div x-show="editTab === 'salary'" class="space-y-4">
                    
                    <!-- Section A: Official Taxable Bank Salary -->
                    <div class="p-3 bg-indigo-500/10 border border-indigo-500/20 rounded-2xl flex items-center justify-between">
                        <div class="flex items-center space-x-2">
                            <i class="fa-solid fa-building-columns text-indigo-400"></i>
                            <span class="font-bold text-white text-xs">1. Official Bank Salary (Reported on Payslip & Tax Filings)</span>
                        </div>
                        <span class="text-[10px] px-2 py-0.5 rounded bg-indigo-500/20 text-indigo-300 font-bold">Tax Filer Slabs</span>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                        <div>
                            <label class="block font-semibold text-slate-300 mb-1">Base Salary (PKR) *</label>
                            <input type="number" step="0.01" name="base_salary" x-model.number="activeEmp.base_salary" required class="w-full px-3 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-white font-bold focus:outline-none focus:border-indigo-500">
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-300 mb-1">Fuel Allowance (PKR)</label>
                            <input type="number" step="0.01" name="fuel_allowance" x-model.number="activeEmp.fuel_allowance" class="w-full px-3 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-white focus:outline-none focus:border-indigo-500">
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-300 mb-1">Special / Other Allowances</label>
                            <input type="number" step="0.01" name="special_allowance" x-model.number="activeEmp.special_allowance" class="w-full px-3 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-white focus:outline-none focus:border-indigo-500">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div>
                            <label class="block font-semibold text-slate-300 mb-1">EOBI / Statutory Deductions (PKR)</label>
                            <input type="number" step="0.01" name="eobi_deduction" x-model.number="activeEmp.eobi_deduction" class="w-full px-3 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-white focus:outline-none focus:border-indigo-500">
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-300 mb-1">Other Regular Deductions (PKR)</label>
                            <input type="number" step="0.01" name="deductions" x-model.number="activeEmp.deductions" class="w-full px-3 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-white focus:outline-none focus:border-indigo-500">
                        </div>
                    </div>

                    <!-- Section B: Confidential Employer Cash-in-Hand (Off-the-Record) -->
                    <div class="p-3.5 bg-amber-500/10 border border-amber-500/30 rounded-2xl space-y-2">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-2 text-amber-400 font-bold">
                                <i class="fa-solid fa-user-shield text-sm"></i>
                                <span>2. Confidential Cash-in-Hand (Employer Only &bull; Off-the-Record)</span>
                            </div>
                            <span class="text-[10px] px-2 py-0.5 rounded bg-amber-400/20 text-amber-300 font-extrabold uppercase">Never on Payslip</span>
                        </div>
                        <p class="text-[11px] text-slate-300 leading-relaxed">
                            Keeps the employee's official taxable salary in a lower tax bracket while paying additional remuneration directly in cash. <strong>This amount is completely hidden from the employee portal and excluded from official salary slips.</strong>
                        </p>
                        <div>
                            <label class="block font-semibold text-slate-200 mb-1">Monthly Cash-in-Hand Amount (PKR)</label>
                            <input type="number" step="0.01" name="cash_allowance" x-model.number="activeEmp.cash_allowance" placeholder="e.g. 50000" class="w-full px-3 py-2.5 bg-slate-950 border border-amber-500/30 rounded-xl text-amber-300 font-bold focus:outline-none focus:border-amber-400">
                        </div>
                    </div>

                    <!-- Live Monthly Salary Preview Box -->
                    <div class="p-4 bg-slate-950 border border-slate-800 rounded-2xl space-y-2 text-xs">
                        <div class="flex items-center justify-between text-slate-400">
                            <span>Official Bank Salary (Reported on Payslip):</span>
                            <span class="font-bold text-white" x-text="'PKR ' + ((Number(activeEmp.base_salary) || 0) + (Number(activeEmp.fuel_allowance) || 0) + (Number(activeEmp.special_allowance) || 0)).toLocaleString()"></span>
                        </div>
                        <div class="flex items-center justify-between text-slate-400">
                            <span>Official Deductions (EOBI / Statutory):</span>
                            <span class="font-bold text-rose-400" x-text="'- PKR ' + ((Number(activeEmp.eobi_deduction) || 0) + (Number(activeEmp.deductions) || 0)).toLocaleString()"></span>
                        </div>
                        <div class="flex items-center justify-between text-slate-400">
                            <span>Official Net Bank Deposit:</span>
                            <span class="font-bold text-indigo-300" x-text="'PKR ' + (((Number(activeEmp.base_salary) || 0) + (Number(activeEmp.fuel_allowance) || 0) + (Number(activeEmp.special_allowance) || 0)) - ((Number(activeEmp.eobi_deduction) || 0) + (Number(activeEmp.deductions) || 0))).toLocaleString()"></span>
                        </div>
                        <div class="flex items-center justify-between text-amber-400 pt-1 border-t border-slate-800/80">
                            <span class="font-bold"><i class="fa-solid fa-hand-holding-dollar mr-1"></i> Confidential Cash Remuneration:</span>
                            <span class="font-bold" x-text="'+ PKR ' + (Number(activeEmp.cash_allowance) || 0).toLocaleString()"></span>
                        </div>
                        <div class="pt-2 border-t border-slate-800 flex items-center justify-between text-sm">
                            <span class="font-black text-white">Total Employer Monthly Outflow:</span>
                            <span class="font-black text-emerald-400" x-text="'PKR ' + ((((Number(activeEmp.base_salary) || 0) + (Number(activeEmp.fuel_allowance) || 0) + (Number(activeEmp.special_allowance) || 0)) - ((Number(activeEmp.eobi_deduction) || 0) + (Number(activeEmp.deductions) || 0))) + (Number(activeEmp.cash_allowance) || 0)).toLocaleString()"></span>
                        </div>
                    </div>

                    <div class="pt-3 flex justify-between items-center border-t border-slate-800">
                        <button type="button" @click="editTab = 'banking'" class="px-4 py-2 bg-slate-800 text-slate-300 font-semibold rounded-xl">Back</button>
                        <div class="space-x-3">
                            <button type="button" @click="editModalOpen = false" class="px-4 py-2.5 bg-slate-800 text-slate-300 font-semibold rounded-xl">Cancel</button>
                            <button type="submit" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white font-black rounded-xl shadow-lg shadow-indigo-600/30">Update Employee</button>
                        </div>
                    </div>
                </div>

            </form>
        </div>
    </div>

    <!-- Quick Add/Edit Shift Modal (On-the-fly during employee add/edit) -->
    <div x-show="quickShiftModalOpen" x-cloak class="fixed inset-0 z-[70] flex items-center justify-center p-4 bg-black/80 backdrop-blur-md" @keydown.escape.window="quickShiftModalOpen = false">
        <div @click.away="quickShiftModalOpen = false" class="bg-slate-900 border border-slate-800 rounded-3xl max-w-md w-full p-6 space-y-4 shadow-2xl">
            <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                <div class="flex items-center space-x-2.5">
                    <div class="w-8 h-8 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 flex items-center justify-center font-bold">
                        <i class="fa-solid fa-business-time text-sm"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-white" x-text="quickShiftMode === 'edit' ? 'Edit Work Shift' : 'Quick Add New Work Shift'"></h3>
                        <p class="text-[11px] text-slate-400">Instantly creates & selects shift for employee</p>
                    </div>
                </div>
                <button type="button" @click="quickShiftModalOpen = false" class="text-slate-400 hover:text-white"><i class="fa-solid fa-xmark"></i></button>
            </div>

            <form @submit.prevent="saveQuickShift()" class="space-y-4 text-xs">
                <div>
                    <label class="block font-semibold text-slate-300 mb-1">Company / Organization *</label>
                    <select x-model="quickShiftForm.organization_id" required class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white">
                        @foreach($organizations as $org)
                            <option value="{{ $org->id }}">{{ $org->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block font-semibold text-slate-300 mb-1">Shift Name *</label>
                    <input type="text" x-model="quickShiftForm.name" required placeholder="e.g. Morning Shift, Evening Shift, Night Shift" class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white focus:outline-none focus:border-emerald-500">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-semibold text-slate-300 mb-1">Start Time *</label>
                        <input type="time" x-model="quickShiftForm.start_time" required class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white font-mono focus:outline-none focus:border-emerald-500">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-300 mb-1">End Time *</label>
                        <input type="time" x-model="quickShiftForm.end_time" required class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white font-mono focus:outline-none focus:border-emerald-500">
                    </div>
                </div>

                <div class="p-3 bg-slate-950/60 border border-slate-800/80 rounded-2xl">
                    <label class="flex items-start space-x-3 cursor-pointer">
                        <input type="checkbox" x-model="quickShiftForm.flexible" class="mt-0.5 rounded bg-slate-900 border-slate-700 text-emerald-500 focus:ring-0">
                        <div>
                            <span class="font-bold text-slate-200 block text-xs">Flexible Hours Shift</span>
                            <span class="text-[11px] text-slate-400 block mt-0.5">Employees on this shift will not be flagged as late based on strict clock-in time.</span>
                        </div>
                    </label>
                </div>

                <div class="pt-2 flex justify-end space-x-3">
                    <button type="button" @click="quickShiftModalOpen = false" class="px-4 py-2 bg-slate-800 text-slate-300 font-semibold rounded-xl">Cancel</button>
                    <button type="submit" :disabled="quickShiftLoading" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white font-bold rounded-xl shadow-lg shadow-emerald-600/20 flex items-center space-x-2">
                        <template x-if="quickShiftLoading">
                            <i class="fa-solid fa-spinner fa-spin"></i>
                        </template>
                        <span x-text="quickShiftMode === 'edit' ? 'Update Shift' : 'Save & Select Shift'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>

<script>
function employeeManager() {
    return {
        addModalOpen: false,
        editModalOpen: false,
        activeEmp: {},
        shiftsList: @json($shifts),
        quickShiftModalOpen: false,
        quickShiftMode: 'create',
        quickShiftLoading: false,
        quickShiftForm: {
            id: '',
            organization_id: '{{ $organizations->first()->id ?? '' }}',
            name: '',
            start_time: '09:00',
            end_time: '18:00',
            flexible: false
        },
        openEditModal(emp) {
            this.activeEmp = JSON.parse(JSON.stringify(emp));
            if (!this.activeEmp.shift_id) {
                this.activeEmp.shift_id = '';
            }
            if (this.activeEmp.national_id_number) {
                this.activeEmp.national_id_number = formatPakistaniCnic(this.activeEmp.national_id_number);
            }
            this.editModalOpen = true;
        },
        openQuickShiftModal(detail) {
            const mode = (detail && detail.mode) || 'create';
            this.quickShiftMode = mode;
            if (mode === 'edit' && detail.shift_id) {
                const s = this.shiftsList.find(x => x.id === detail.shift_id);
                if (s) {
                    this.quickShiftForm = {
                        id: s.id,
                        organization_id: s.organization_id,
                        name: s.name,
                        start_time: (s.start_time || '09:00').substring(0, 5),
                        end_time: (s.end_time || '18:00').substring(0, 5),
                        flexible: !!s.flexible
                    };
                    this.quickShiftModalOpen = true;
                    return;
                }
            }
            let defaultOrgId = '{{ $organizations->first()->id ?? '' }}';
            if (this.editModalOpen && this.activeEmp && this.activeEmp.organization_id) {
                defaultOrgId = this.activeEmp.organization_id;
            }
            this.quickShiftForm = {
                id: '',
                organization_id: defaultOrgId,
                name: '',
                start_time: '09:00',
                end_time: '18:00',
                flexible: false
            };
            this.quickShiftModalOpen = true;
        },
        async saveQuickShift() {
            if (!this.quickShiftForm.name || !this.quickShiftForm.name.trim()) {
                alert('Please enter a work shift name.');
                return;
            }
            this.quickShiftLoading = true;
            try {
                const isEdit = this.quickShiftMode === 'edit';
                const url = isEdit ? `{{ url('settings/shifts') }}/${this.quickShiftForm.id}` : '{{ route("settings.shifts.store") }}';
                const method = isEdit ? 'PUT' : 'POST';

                const response = await fetch(url, {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify(this.quickShiftForm)
                });
                const data = await response.json();
                if (data.success && data.shift) {
                    if (isEdit) {
                        const idx = this.shiftsList.findIndex(x => x.id === data.shift.id);
                        if (idx !== -1) {
                            this.shiftsList.splice(idx, 1, data.shift);
                        }
                    } else {
                        this.shiftsList.push(data.shift);
                        if (this.addModalOpen) {
                            window.dispatchEvent(new CustomEvent('shift-auto-select', { detail: data.shift.id }));
                        }
                        if (this.editModalOpen && this.activeEmp) {
                            this.activeEmp.shift_id = data.shift.id;
                        }
                    }
                    window.dispatchEvent(new CustomEvent('shifts-updated', { detail: JSON.parse(JSON.stringify(this.shiftsList)) }));
                    this.quickShiftModalOpen = false;
                } else {
                    alert(data.message || 'Could not save work shift.');
                }
            } catch (err) {
                console.error('Save shift error:', err);
                alert('Network error while saving work shift. Please try again.');
            } finally {
                this.quickShiftLoading = false;
            }
        }
    };
}

function formatPakistaniCnic(val) {
    if (!val) return '';
    const digits = String(val).replace(/\D/g, '').slice(0, 13);
    if (digits.length <= 5) {
        return digits;
    } else if (digits.length <= 12) {
        return digits.slice(0, 5) + '-' + digits.slice(5);
    } else {
        return digits.slice(0, 5) + '-' + digits.slice(5, 12) + '-' + digits.slice(12, 13);
    }
}

function addEmployeeModal() {
    return {
        addTab: 'personal',
        baseSal: 250000,
        fuelAllow: 0,
        specialAllow: 0,
        otherAllow: 0,
        eobiDed: 0,
        otherDed: 0,
        cashAllow: 0,
        showPass: false,
        shiftsList: @json($shifts),

        formatCnicInput() {
            this.form.national_id_number = formatPakistaniCnic(this.form.national_id_number);
            this.clearError('national_id_number');
        },

        init() {
            window.addEventListener('shift-auto-select', (e) => {
                if (this.form) {
                    this.form.shift_id = e.detail;
                }
            });
            window.addEventListener('shifts-updated', (e) => {
                this.shiftsList = e.detail;
            });
        },

        form: {
            name: '',
            email: '',
            password: 'Password@123',
            national_id_number: '',
            phone: '',
            date_of_joining: '{{ date('Y-m-d') }}',
            address: '',
            role_id: '{{ $roles->where('name', 'EMPLOYEE')->first()->id ?? ($roles->first()->id ?? '') }}',
            organization_id: '{{ $organizations->first()->id ?? '' }}',
            department_id: '',
            designation_id: '',
            team_id: '',
            shift_id: '',
            employment_type: 'FULL_TIME',
            payroll_id: '',
            payment_method: 'BANK_TRANSFER',
            bank_name: '',
            bank_account_holder_name: '',
            bank_account_number: '',
            bank_branch_name: '',
        },

        errors: {},
        stepErrorMsg: '',

        clearError(field) {
            if (this.errors[field]) {
                delete this.errors[field];
                this.errors = Object.assign({}, this.errors);
            }
            if (Object.keys(this.errors).length === 0) {
                this.stepErrorMsg = '';
            }
        },

        hasTabError(tab) {
            if (tab === 'personal') {
                return !!(this.errors.name || this.errors.email || this.errors.password || this.errors.national_id_number);
            }
            if (tab === 'job') {
                return !!(this.errors.role_id || this.errors.organization_id || this.errors.employment_type);
            }
            if (tab === 'salary') {
                return !!this.errors.base_salary;
            }
            return false;
        },

        validateTab(tab) {
            let isValid = true;

            if (tab === 'personal') {
                if (!this.form.name || !this.form.name.trim()) {
                    this.errors.name = 'Full Name is required.';
                    isValid = false;
                }

                if (!this.form.email || !this.form.email.trim()) {
                    this.errors.email = 'Corporate Email Address is required.';
                    isValid = false;
                } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(this.form.email.trim())) {
                    this.errors.email = 'Please enter a valid corporate email address.';
                    isValid = false;
                }

                if (!this.form.password) {
                    this.errors.password = 'Default Password is required.';
                    isValid = false;
                } else if (this.form.password.length < 6) {
                    this.errors.password = 'Password must be at least 6 characters.';
                    isValid = false;
                }

                if (!this.form.national_id_number || !this.form.national_id_number.trim()) {
                    this.errors.national_id_number = 'CNIC / National ID Number is required.';
                    isValid = false;
                } else if (!/^\d{5}-\d{7}-\d{1}$/.test(this.form.national_id_number.trim())) {
                    this.errors.national_id_number = 'CNIC must follow the 13-digit pattern: XXXXX-XXXXXXX-X (e.g. 42101-1234567-1).';
                    isValid = false;
                }
            }

            if (tab === 'job') {
                if (!this.form.role_id) {
                    this.errors.role_id = 'Please select a System Role.';
                    isValid = false;
                }
                if (!this.form.organization_id) {
                    this.errors.organization_id = 'Please select a Company / Organization.';
                    isValid = false;
                }
                if (!this.form.employment_type) {
                    this.errors.employment_type = 'Please select an Employment Type.';
                    isValid = false;
                }
            }

            if (tab === 'salary') {
                if (this.baseSal === '' || this.baseSal === null || isNaN(this.baseSal) || Number(this.baseSal) <= 0) {
                    this.errors.base_salary = 'Basic Salary is required and must be greater than 0.';
                    isValid = false;
                }
            }

            this.errors = Object.assign({}, this.errors);

            if (!isValid) {
                this.stepErrorMsg = 'Please complete all required fields highlighted in red before proceeding.';
            } else {
                this.stepErrorMsg = '';
            }

            return isValid;
        },

        goToTab(targetTab) {
            const tabsOrder = ['personal', 'job', 'banking', 'salary'];
            const currentIndex = tabsOrder.indexOf(this.addTab);
            const targetIndex = tabsOrder.indexOf(targetTab);

            // If advancing forward, validate all preceding tabs
            if (targetIndex > currentIndex) {
                for (let i = 0; i <= currentIndex; i++) {
                    const step = tabsOrder[i];
                    if (!this.validateTab(step)) {
                        this.addTab = step;
                        this.$nextTick(() => {
                            const firstErr = document.querySelector('[data-has-error="true"]');
                            if (firstErr) {
                                firstErr.focus();
                                firstErr.scrollIntoView({ behavior: 'smooth', block: 'center' });
                            }
                        });
                        return false;
                    }
                }
            }

            this.stepErrorMsg = '';
            this.addTab = targetTab;
            return true;
        },

        submitForm(e) {
            const tabsOrder = ['personal', 'job', 'banking', 'salary'];
            for (const step of tabsOrder) {
                if (!this.validateTab(step)) {
                    this.addTab = step;
                    this.stepErrorMsg = 'Please complete all required fields before submitting.';
                    this.$nextTick(() => {
                        const firstErr = document.querySelector('[data-has-error="true"]');
                        if (firstErr) {
                            firstErr.focus();
                            firstErr.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        }
                    });
                    return false;
                }
            }
            e.target.submit();
        },

        closeModal() {
            this.errors = {};
            this.stepErrorMsg = '';
            this.addTab = 'personal';
            this.addModalOpen = false;
        }
    };
}
</script>

@endsection
