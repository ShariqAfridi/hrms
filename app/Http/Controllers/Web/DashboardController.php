<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Department;
use App\Models\LeaveRequest;
use App\Models\Payroll;
use App\Models\Payslip;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $today = date('Y-m-d');
        $userTodayAttendance = Attendance::with(['breaks', 'activeBreak'])
            ->where('user_id', $user->id)
            ->whereDate('date', $today)
            ->first();

        // Employee Self-Service Data
        $myPendingTasks = Task::where('user_id', $user->id)->where('status', '!=', 'COMPLETED')->with('project')->get();
        $myCompletedTasksCount = Task::where('user_id', $user->id)->where('status', 'COMPLETED')->count();
        $myPendingLeaves = LeaveRequest::where('user_id', $user->id)->where('status', 'PENDING')->count();
        $myApprovedLeaves = LeaveRequest::where('user_id', $user->id)->where('status', 'APPROVED')->count();
        $myRecentAttendance = Attendance::where('user_id', $user->id)->orderBy('date', 'desc')->take(5)->get();
        $myLatestPayslip = \App\Models\Payslip::where('user_id', $user->id)->orderBy('created_at', 'desc')->first();

        // Admin / Manager Corporate Data (Excluding Developer System Accounts)
        $totalEmployees = User::excludeDevs()->count();
        $departmentsCount = Department::count();
        $todayAttendance = Attendance::whereDate('date', $today)->whereHas('user', function ($q) {
            $q->excludeDevs();
        })->count();
        $presentToday = Attendance::whereDate('date', $today)->whereHas('user', function ($q) {
            $q->excludeDevs();
        })->whereIn('status', ['PRESENT', 'LATE', 'HALF_DAY'])->count();
        $pendingLeavesCount = LeaveRequest::where('status', 'PENDING')->whereHas('user', function ($q) {
            $q->excludeDevs();
        })->count();
        $todayLateCount = Attendance::whereDate('date', $today)->whereHas('user', function ($q) {
            $q->excludeDevs();
        })->where('status', 'LATE')->count();
        $todayOnBreakCount = Attendance::whereDate('date', $today)->whereHas('user', function ($q) {
            $q->excludeDevs();
        })->whereNotNull('current_break_type')->count();
        $totalPayrollAmount = (float) User::excludeDevs()->where('is_active', true)->get()->sum(function ($u) {
            $base = (float) ($u->base_salary ?? 0);
            $allowances = (float) ($u->allowances ?? 0);
            $deductions = (float) ($u->deductions ?? 0);
            return max(0, $base + $allowances - $deductions);
        });

        $recentEmployees = User::excludeDevs()->with(['department', 'designation'])
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        $recentLeaves = LeaveRequest::whereHas('user', function ($q) {
            $q->excludeDevs();
        })->with(['user', 'leaveType'])
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        $departments = Department::withCount('users')->get();

        return view('dashboard', compact(
            'totalEmployees',
            'departmentsCount',
            'todayAttendance',
            'presentToday',
            'todayLateCount',
            'todayOnBreakCount',
            'pendingLeavesCount',
            'totalPayrollAmount',
            'recentEmployees',
            'recentLeaves',
            'departments',
            'userTodayAttendance',
            'myPendingTasks',
            'myCompletedTasksCount',
            'myPendingLeaves',
            'myApprovedLeaves',
            'myRecentAttendance',
            'myLatestPayslip'
        ));
    }
}
