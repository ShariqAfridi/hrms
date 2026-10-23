@extends('layouts.app')

@section('title', 'Employee Directory')

@section('content')

<div x-data="{ addModalOpen: false }">

    <!-- Page Header & Controls -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-white tracking-tight">Employees Directory</h1>
            <p class="text-xs font-medium text-slate-400 mt-1">Manage workforce members, roles, and department assignments.</p>
        </div>

        <button @click="addModalOpen = true" class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs rounded-xl shadow-lg shadow-indigo-600/30 transition-all flex items-center justify-center space-x-2">
            <i class="fa-solid fa-user-plus"></i>
            <span>Add New Employee</span>
        </button>
    </div>

    <!-- Search & Filter Bar -->
    <div class="bg-slate-950/60 border border-slate-800 rounded-2xl p-4 flex flex-col md:flex-row items-center justify-between gap-4 shadow-xl">
        <form action="{{ route('employees.index') }}" method="GET" class="w-full flex flex-col md:flex-row items-center gap-3">
            <div class="relative w-full md:w-80">
                <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-3 text-slate-500 text-xs"></i>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by name or email..." class="w-full pl-9 pr-4 py-2 bg-slate-900 border border-slate-800 rounded-xl text-xs font-medium text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500 transition-all">
            </div>

            <select name="department_id" class="w-full md:w-48 px-3 py-2 bg-slate-900 border border-slate-800 rounded-xl text-xs font-medium text-slate-300 focus:outline-none focus:border-indigo-500 transition-all">
                <option value="">All Departments</option>
                @foreach($departments as $dept)
                    <option value="{{ $dept->id }}" {{ request('department_id') == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                @endforeach
            </select>

            <button type="submit" class="w-full md:w-auto px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-200 font-bold text-xs rounded-xl transition-all">
                Filter
            </button>
            @if(request('search') || request('department_id'))
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
                        <th class="py-3.5 px-6">Employee</th>
                        <th class="py-3.5 px-6">Role & Department</th>
                        <th class="py-3.5 px-6">Employment</th>
                        <th class="py-3.5 px-6">Joined Date</th>
                        <th class="py-3.5 px-6">Status</th>
                        <th class="py-3.5 px-6 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/80 text-xs">
                    @forelse($employees as $emp)
                        <tr class="hover:bg-slate-900/40 transition-colors">
                            <td class="py-4 px-6">
                                <div class="flex items-center space-x-3">
                                    <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-indigo-600 to-purple-600 flex items-center justify-center font-bold text-white text-xs shadow-md shrink-0">
                                        {{ strtoupper(substr($emp->name, 0, 1)) }}
                                    </div>
                                    <div>
                                        <p class="font-bold text-white text-sm">{{ $emp->name }}</p>
                                        <p class="text-slate-400 text-xs font-medium">{{ $emp->email }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="py-4 px-6">
                                <p class="font-semibold text-indigo-400">{{ $emp->designation->title ?? 'N/A' }}</p>
                                <p class="text-slate-400 text-[11px] font-medium">{{ $emp->department->name ?? 'Unassigned' }}</p>
                            </td>
                            <td class="py-4 px-6">
                                <span class="px-2.5 py-1 rounded-lg bg-slate-900 border border-slate-800 font-semibold text-slate-300">
                                    {{ str_replace('_', ' ', $emp->employment_type) }}
                                </span>
                            </td>
                            <td class="py-4 px-6 text-slate-400 font-medium">
                                {{ date('d M Y', strtotime($emp->date_of_joining)) }}
                            </td>
                            <td class="py-4 px-6">
                                <span class="px-2.5 py-1 rounded-full font-semibold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 text-[11px]">
                                    Active
                                </span>
                            </td>
                            <td class="py-4 px-6 text-right">
                                <form action="{{ route('employees.destroy', $emp->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this employee?');" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-2 text-slate-500 hover:text-rose-400 rounded-lg hover:bg-rose-500/10 transition-colors" title="Delete">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-8 text-center text-slate-500 font-medium">
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

    <!-- Add Employee Modal -->
    <div x-show="addModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm">
        <div @click.away="addModalOpen = false" class="bg-slate-900 border border-slate-800 rounded-3xl max-w-lg w-full p-6 space-y-5 shadow-2xl">
            <div class="flex items-center justify-between border-b border-slate-800 pb-4">
                <h3 class="text-base font-bold text-white">Add New Employee</h3>
                <button @click="addModalOpen = false" class="text-slate-400 hover:text-white"><i class="fa-solid fa-xmark"></i></button>
            </div>

            <form action="{{ route('employees.store') }}" method="POST" class="space-y-4 text-xs">
                @csrf
                <div>
                    <label class="block font-semibold text-slate-300 mb-1">Full Name</label>
                    <input type="text" name="name" required class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white focus:outline-none focus:border-indigo-500">
                </div>

                <div>
                    <label class="block font-semibold text-slate-300 mb-1">Email Address</label>
                    <input type="email" name="email" required class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white focus:outline-none focus:border-indigo-500">
                </div>

                <div>
                    <label class="block font-semibold text-slate-300 mb-1">Default Password</label>
                    <input type="password" name="password" required value="Password@123" class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white focus:outline-none focus:border-indigo-500">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-semibold text-slate-300 mb-1">Role</label>
                        <select name="role_id" required class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white">
                            @foreach($roles as $role)
                                <option value="{{ $role->id }}">{{ $role->label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-300 mb-1">Organization</label>
                        <select name="organization_id" required class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white">
                            @foreach($organizations as $org)
                                <option value="{{ $org->id }}">{{ $org->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-semibold text-slate-300 mb-1">Department</label>
                        <select name="department_id" class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white">
                            @foreach($departments as $dept)
                                <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-300 mb-1">Designation</label>
                        <select name="designation_id" class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white">
                            @foreach($designations as $desig)
                                <option value="{{ $desig->id }}">{{ $desig->title }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-semibold text-slate-300 mb-1">Employment Type</label>
                        <select name="employment_type" required class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white">
                            <option value="FULL_TIME">Full Time</option>
                            <option value="PART_TIME">Part Time</option>
                            <option value="CONTRACT">Contract</option>
                            <option value="INTERN">Intern</option>
                        </select>
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-300 mb-1">Base Salary ($)</label>
                        <input type="number" name="base_salary" value="5000" class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white">
                    </div>
                </div>

                <div class="pt-3 flex justify-end space-x-3">
                    <button type="button" @click="addModalOpen = false" class="px-4 py-2 bg-slate-800 text-slate-300 font-semibold rounded-xl">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white font-bold rounded-xl shadow-lg">Save Employee</button>
                </div>
            </form>
        </div>
    </div>

</div>

@endsection
