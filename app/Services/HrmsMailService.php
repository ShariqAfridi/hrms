<?php

namespace App\Services;

use App\Models\LeaveRequest;
use App\Models\Payslip;
use App\Models\PayslipRequest;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class HrmsMailService
{
    /**
     * Check if email notifications are enabled globally in system settings
     */
    public static function isMailEnabled(): bool
    {
        return (bool) SystemSetting::get('notification_email_alerts', true);
    }

    /**
     * Send email alert to Admins / HR when an employee requests a payslip clearance
     */
    public static function sendPayslipRequestedAlert(PayslipRequest $payslipRequest, User $employee): void
    {
        if (!self::isMailEnabled()) {
            return;
        }

        if (!SystemSetting::get('notification_payroll_alerts', true)) {
            return;
        }

        try {
            // Find HR Managers and Super Admins to notify
            $admins = User::whereHas('role', function ($q) {
                $q->whereIn('slug', ['SUPER_ADMIN', 'HR_MANAGER', 'HR', 'SYSTEM_ADMIN']);
            })->where('is_active', true)->whereNotNull('email')->get();

            if ($admins->isEmpty()) {
                // Fallback to any active super admin
                $admins = User::where('is_active', true)->whereNotNull('email')->take(2)->get();
            }

            $salaryPeriod = date('F Y', strtotime($payslipRequest->payment_month . '-01'));

            foreach ($admins as $admin) {
                if (empty($admin->email)) {
                    continue;
                }

                $data = [
                    'subject' => "Payslip Clearance Request: {$employee->name} ({$salaryPeriod})",
                    'employeeName' => $employee->name,
                    'employeeCode' => $employee->employee_id ?? ('EMP-' . substr(preg_replace('/[^0-9]/', '', $employee->id), 0, 4)),
                    'department' => $employee->department->name ?? 'General Staff',
                    'salaryPeriod' => $salaryPeriod,
                    'reason' => $payslipRequest->reason,
                    'actionUrl' => route('payroll.index', ['tab' => 'requests']),
                ];

                Mail::send('emails.payslip-requested', $data, function ($m) use ($admin, $data) {
                    $m->to($admin->email, $admin->name)->subject($data['subject']);
                });
            }
        } catch (\Throwable $e) {
            Log::warning("HrmsMailService: Could not dispatch payslip requested email: " . $e->getMessage());
        }
    }

    /**
     * Send email alert to Employee when HR / Admin approves their payslip clearance request
     */
    public static function sendPayslipReleasedAlert(PayslipRequest $payslipRequest, Payslip $payslip, User $employee, User $approvedBy): void
    {
        if (!self::isMailEnabled()) {
            return;
        }

        if (!SystemSetting::get('notification_payroll_alerts', true)) {
            return;
        }

        if (empty($employee->email)) {
            return;
        }

        try {
            $salaryPeriod = date('F Y', strtotime($payslipRequest->payment_month . '-01'));
            $org = $employee->organization;

            $data = [
                'subject' => "Salary Voucher Released: {$salaryPeriod}",
                'employeeName' => $employee->name,
                'salaryPeriod' => $salaryPeriod,
                'refNo' => 'PAY-' . str_pad($payslip->id, 6, '0', STR_PAD_LEFT),
                'netPay' => (float)$payslip->net_pay,
                'currency' => $org->currency ?? 'PKR',
                'paymentMethod' => $payslip->payment_method ?? 'Direct Bank Transfer',
                'approvedBy' => $approvedBy->name ?? 'Directorate of HR',
                'downloadUrl' => route('payroll.payslip.download', $payslip->id),
                'viewUrl' => route('payroll.payslip.view', $payslip->id),
            ];

            Mail::send('emails.payslip-released', $data, function ($m) use ($employee, $data) {
                $m->to($employee->email, $employee->name)->subject($data['subject']);
            });
        } catch (\Throwable $e) {
            Log::warning("HrmsMailService: Could not dispatch payslip released email: " . $e->getMessage());
        }
    }

    /**
     * Send email alert to Manager / HR when an employee submits a leave application
     */
    public static function sendLeaveRequestedAlert(LeaveRequest $leave, User $employee, $approver = null): void
    {
        if (!self::isMailEnabled()) {
            return;
        }

        if (!SystemSetting::get('notification_leave_alerts', true)) {
            return;
        }

        try {
            $recipients = collect();
            if ($approver && !empty($approver->email)) {
                $recipients->push($approver);
            } else {
                // Determine Dept Manager or HR
                $deptHead = $employee->department?->headUser;
                if ($deptHead && !empty($deptHead->email)) {
                    $recipients->push($deptHead);
                } else {
                    $hrAdmins = User::whereHas('role', function ($q) {
                        $q->whereIn('slug', ['HR_MANAGER', 'HR', 'SUPER_ADMIN']);
                    })->where('is_active', true)->whereNotNull('email')->get();
                    $recipients = $recipients->merge($hrAdmins);
                }
            }

            $leaveTypeName = $leave->leaveType->name ?? 'Leave';

            foreach ($recipients->unique('id') as $target) {
                if (empty($target->email)) continue;

                $data = [
                    'subject' => "Leave Application: {$employee->name} ({$leaveTypeName})",
                    'employeeName' => $employee->name,
                    'department' => $employee->department->name ?? 'General Staff',
                    'leaveType' => $leaveTypeName,
                    'startDate' => date('d-M-Y', strtotime($leave->start_date)),
                    'endDate' => date('d-M-Y', strtotime($leave->end_date)),
                    'totalDays' => $leave->total_days ?? 1,
                    'reason' => $leave->reason,
                    'actionUrl' => route('leaves.index'),
                ];

                Mail::send('emails.leave-requested', $data, function ($m) use ($target, $data) {
                    $m->to($target->email, $target->name)->subject($data['subject']);
                });
            }
        } catch (\Throwable $e) {
            Log::warning("HrmsMailService: Could not dispatch leave requested email: " . $e->getMessage());
        }
    }

    /**
     * Send email alert to Employee when their leave request is approved or rejected
     */
    public static function sendLeaveDecisionAlert(LeaveRequest $leave, User $employee, bool $isApproved, $approver = null, $comments = null): void
    {
        if (!self::isMailEnabled()) {
            return;
        }

        if (!SystemSetting::get('notification_leave_alerts', true)) {
            return;
        }

        if (empty($employee->email)) {
            return;
        }

        try {
            $leaveTypeName = $leave->leaveType->name ?? 'Leave';
            $statusText = $isApproved ? 'Approved' : 'Declined';

            $data = [
                'subject' => "Leave Request {$statusText}: {$leaveTypeName}",
                'employeeName' => $employee->name,
                'isApproved' => $isApproved,
                'leaveType' => $leaveTypeName,
                'startDate' => date('d-M-Y', strtotime($leave->start_date)),
                'endDate' => date('d-M-Y', strtotime($leave->end_date)),
                'totalDays' => $leave->total_days ?? 1,
                'approverName' => $approver->name ?? 'Management',
                'comments' => $comments,
                'actionUrl' => route('leaves.index'),
            ];

            Mail::send('emails.leave-decision', $data, function ($m) use ($employee, $data) {
                $m->to($employee->email, $employee->name)->subject($data['subject']);
            });
        } catch (\Throwable $e) {
            Log::warning("HrmsMailService: Could not dispatch leave decision email: " . $e->getMessage());
        }
    }

    /**
     * Send an instant test email to verify SMTP configuration
     */
    public static function sendTestEmail(string $targetEmail): array
    {
        try {
            $data = [
                'subject' => 'AxeCorp HRMS - SMTP Test Connection Successful',
                'employeeName' => 'HR Administrator',
            ];

            Mail::raw("Hello,\n\nThis is a test email sent from AxeCorp HRMS to verify your outgoing SMTP server connection.\n\nAll mail services are operational!\n\nTimestamp: " . date('Y-m-d H:i:s') . "\n\nBest regards,\nAxeCorp System Administration", function ($m) use ($targetEmail, $data) {
                $m->to($targetEmail)->subject($data['subject']);
            });

            return [
                'success' => true,
                'message' => "Test email dispatched successfully to {$targetEmail}.",
            ];
        } catch (\Throwable $e) {
            Log::error("HrmsMailService: SMTP test connection failed: " . $e->getMessage());
            return [
                'success' => false,
                'message' => "SMTP Connection Failed: " . $e->getMessage(),
            ];
        }
    }
}
