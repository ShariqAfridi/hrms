@extends('emails.layout')

@section('content')
    <div class="headline">New Payslip Clearance Request</div>
    <p>Dear Administrator,</p>
    <p>A staff member has formally submitted a request for release and clearance of their official monthly salary voucher.</p>

    <div class="meta-box">
        <div class="meta-row">
            <span class="meta-label">Employee Name</span>
            <span class="meta-val">{{ $employeeName }}</span>
        </div>
        <div class="meta-row">
            <span class="meta-label">Employee Code</span>
            <span class="meta-val">{{ $employeeCode }}</span>
        </div>
        <div class="meta-row">
            <span class="meta-label">Department</span>
            <span class="meta-val">{{ $department }}</span>
        </div>
        <div class="meta-row">
            <span class="meta-label">Salary Period</span>
            <span class="meta-val">{{ $salaryPeriod }}</span>
        </div>
        @if(!empty($reason))
        <div class="meta-row">
            <span class="meta-label">Stated Purpose</span>
            <span class="meta-val">{{ $reason }}</span>
        </div>
        @endif
        <div class="meta-row">
            <span class="meta-label">Requested At</span>
            <span class="meta-val">{{ date('d M Y, h:i A') }}</span>
        </div>
    </div>

    <p>In accordance with corporate confidentiality protocols, this payslip remains locked from employee access until an authorized Administrator or HR Manager reviews and clears the release.</p>

    <div class="btn-wrapper">
        <a href="{{ $actionUrl }}" class="btn">Review &amp; Release Payslip</a>
    </div>
@endsection
