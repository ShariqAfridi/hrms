@extends('emails.layout')

@section('content')
    <div class="headline">Leave Application Decision: {{ $isApproved ? 'Approved' : 'Declined' }}</div>
    <p>Dear {{ $employeeName }},</p>
    <p>Your formal leave application has been reviewed and a decision has been recorded by management.</p>

    <div class="meta-box" style="border-left-color: {{ $isApproved ? '#059669' : '#dc2626' }};">
        <div class="meta-row">
            <span class="meta-label">Status</span>
            <span class="meta-val" style="color: {{ $isApproved ? '#059669' : '#dc2626' }};">
                {{ $isApproved ? 'APPROVED & AUTHORIZED' : 'DECLINED' }}
            </span>
        </div>
        <div class="meta-row">
            <span class="meta-label">Leave Category</span>
            <span class="meta-val">{{ $leaveType }}</span>
        </div>
        <div class="meta-row">
            <span class="meta-label">Duration</span>
            <span class="meta-val">{{ $startDate }} to {{ $endDate }} ({{ $totalDays }} Day{{ $totalDays > 1 ? 's' : '' }})</span>
        </div>
        <div class="meta-row">
            <span class="meta-label">Decision By</span>
            <span class="meta-val">{{ $approverName }}</span>
        </div>
        @if(!empty($comments))
        <div class="meta-row">
            <span class="meta-label">Manager Remarks</span>
            <span class="meta-val">{{ $comments }}</span>
        </div>
        @endif
    </div>

    <div class="btn-wrapper">
        <a href="{{ $actionUrl }}" class="btn {{ $isApproved ? 'btn-success' : 'btn-danger' }}">View Leave History in Portal</a>
    </div>
@endsection
