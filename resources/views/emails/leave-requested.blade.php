@extends('emails.layout')

@section('content')
    <div class="headline">New Leave Application Submitted</div>
    <p>Dear Approver,</p>
    <p>A team member has submitted a formal leave request requiring your managerial review and authorization.</p>

    <div class="meta-box">
        <div class="meta-row">
            <span class="meta-label">Employee Name</span>
            <span class="meta-val">{{ $employeeName }}</span>
        </div>
        <div class="meta-row">
            <span class="meta-label">Department</span>
            <span class="meta-val">{{ $department }}</span>
        </div>
        <div class="meta-row">
            <span class="meta-label">Leave Category</span>
            <span class="meta-val">{{ $leaveType }}</span>
        </div>
        <div class="meta-row">
            <span class="meta-label">Duration</span>
            <span class="meta-val">{{ $startDate }} to {{ $endDate }} ({{ $totalDays }} Day{{ $totalDays > 1 ? 's' : '' }})</span>
        </div>
        @if(!empty($reason))
        <div class="meta-row">
            <span class="meta-label">Reason / Notes</span>
            <span class="meta-val">{{ $reason }}</span>
        </div>
        @endif
        <div class="meta-row">
            <span class="meta-label">Submission Date</span>
            <span class="meta-val">{{ date('d M Y, h:i A') }}</span>
        </div>
    </div>

    <div class="btn-wrapper">
        <a href="{{ $actionUrl }}" class="btn">Review &amp; Approve Leave</a>
    </div>
@endsection
