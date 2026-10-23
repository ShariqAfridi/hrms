@extends('layouts.app')

@section('title', 'Payroll & Payslips')

@section('content')

<!-- Header & Summary -->
<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div>
        <h1 class="text-2xl font-extrabold text-white tracking-tight">Payroll & Payslips</h1>
        <p class="text-xs font-medium text-slate-400 mt-1">Monthly payroll processing, salary calculations, and employee payslip records.</p>
    </div>
</div>

<!-- Payroll Summary Cards -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-5">
    @forelse($payrolls as $pr)
        <div class="bg-slate-950/60 border border-slate-800 rounded-3xl p-5 space-y-3 shadow-xl">
            <div class="flex items-center justify-between">
                <span class="px-3 py-1 bg-indigo-500/10 border border-indigo-500/20 text-indigo-400 font-bold text-xs rounded-lg">
                    <i class="fa-solid fa-calendar-day mr-1"></i> {{ date('F Y', strtotime($pr->month . '-01')) }}
                </span>
                <span class="px-2.5 py-1 rounded-full font-semibold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 text-[11px]">
                    {{ $pr->status }}
                </span>
            </div>

            <div>
                <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Total Net Disbursement</p>
                <h3 class="text-2xl font-extrabold text-white mt-0.5">${{ number_format($pr->net_salary, 2) }}</h3>
            </div>

            <div class="grid grid-cols-2 gap-2 text-[11px] pt-2 border-t border-slate-800/80">
                <div>
                    <span class="text-slate-500">Gross Salary:</span>
                    <p class="font-bold text-slate-300">${{ number_format($pr->total_gross, 2) }}</p>
                </div>
                <div>
                    <span class="text-slate-500">Total Deductions:</span>
                    <p class="font-bold text-rose-400">${{ number_format($pr->total_deductions, 2) }}</p>
                </div>
            </div>
        </div>
    @empty
        <div class="col-span-3 bg-slate-950/60 border border-slate-800 rounded-3xl p-6 text-center text-slate-500 font-medium">
            No active payroll runs found.
        </div>
    @endforelse
</div>

<!-- Payslips Table -->
<div class="bg-slate-950/60 border border-slate-800 rounded-3xl overflow-hidden shadow-2xl space-y-4">
    <div class="p-6 border-b border-slate-800 flex items-center justify-between">
        <h3 class="text-base font-bold text-white tracking-tight">Employee Payslip Disbursements</h3>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-slate-900/80 border-b border-slate-800 text-[11px] font-bold text-slate-400 uppercase tracking-wider">
                    <th class="py-3.5 px-6">Employee</th>
                    <th class="py-3.5 px-6">Basic Salary</th>
                    <th class="py-3.5 px-6">Allowances</th>
                    <th class="py-3.5 px-6">Deductions</th>
                    <th class="py-3.5 px-6">Net Pay</th>
                    <th class="py-3.5 px-6">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-800/80 text-xs">
                @forelse($payslips as $ps)
                    <tr class="hover:bg-slate-900/40 transition-colors">
                        <td class="py-4 px-6">
                            <div class="flex items-center space-x-3">
                                <div class="w-8 h-8 rounded-lg bg-emerald-600/20 text-emerald-400 flex items-center justify-center font-bold text-xs shrink-0">
                                    {{ strtoupper(substr($ps->user->name ?? 'U', 0, 1)) }}
                                </div>
                                <div>
                                    <p class="font-bold text-white">{{ $ps->user->name ?? 'User' }}</p>
                                    <p class="text-slate-400 text-[11px] font-medium">{{ $ps->user->designation->title ?? 'Team Member' }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="py-4 px-6 font-semibold text-slate-300">
                            ${{ number_format($ps->base_salary, 2) }}
                        </td>
                        <td class="py-4 px-6 font-semibold text-emerald-400">
                            +${{ number_format($ps->allowances, 2) }}
                        </td>
                        <td class="py-4 px-6 font-semibold text-rose-400">
                            -${{ number_format($ps->deductions, 2) }}
                        </td>
                        <td class="py-4 px-6 font-extrabold text-white text-sm">
                            ${{ number_format($ps->net_salary, 2) }}
                        </td>
                        <td class="py-4 px-6">
                            <span class="px-2.5 py-1 rounded-full font-semibold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 text-[11px]">
                                {{ $ps->status }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="py-8 text-center text-slate-500 font-medium">
                            No payslip records found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="p-4 border-t border-slate-800">
        {{ $payslips->links() }}
    </div>
</div>

@endsection
