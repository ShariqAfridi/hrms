@extends('emails.layout')

@section('content')
    <div class="headline">Official Salary Slip Approved &amp; Released</div>
    <p>Dear {{ $employeeName }},</p>
    <p>Your request for the release of your official remuneration voucher has been reviewed, authorized, and approved by the Directorate of Human Resources.</p>

    <div class="meta-box">
        <div class="meta-row">
            <span class="meta-label">Salary Period</span>
            <span class="meta-val">{{ $salaryPeriod }}</span>
        </div>
        <div class="meta-row">
            <span class="meta-label">Voucher Reference</span>
            <span class="meta-val" style="font-family: monospace;">{{ $refNo }}</span>
        </div>
        <div class="meta-row">
            <span class="meta-label">Net Take-Home Pay</span>
            <span class="meta-val" style="color: #059669;">{{ $currency }} {{ number_format($netPay, 2) }}</span>
        </div>
        <div class="meta-row">
            <span class="meta-label">Disbursement Channel</span>
            <span class="meta-val">{{ $paymentMethod }}</span>
        </div>
        <div class="meta-row">
            <span class="meta-label">Approved By</span>
            <span class="meta-val">{{ $approvedBy }}</span>
        </div>
    </div>

    <p>Your official corporate salary voucher is now accessible. You can view, verify digital stamps, and download or print the PDF directly from the secure portal.</p>

    <div class="btn-wrapper">
        <a href="{{ $downloadUrl }}" class="btn btn-success">Download Salary Slip (PDF)</a>
    </div>

    <p style="text-align: center; margin-top: 10px; font-size: 12px;">
        <a href="{{ $viewUrl }}" style="color: #6366f1; text-decoration: underline;">Or view in HRMS portal</a>
    </p>
@endsection
