<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class LeaveRequest extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id', 'organization_id', 'user_id', 'leave_type_id', 'start_date', 'end_date',
        'is_half_day', 'session', 'total_days', 'reason', 'emergency_contact',
        'attachment_url', 'notify_hr', 'comments', 'current_approver_id',
        'team_manager_id', 'dept_manager_id', 'forwarded_by', 'forwarded_at',
        'forward_notes', 'approved_by', 'approved_at', 'rejection_reason',
        'revoked_by', 'revoked_at', 'revocation_reason',
        'approval_history', 'status', 'cancelled_at', 'expired_at'
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'is_half_day' => 'boolean',
        'total_days' => 'float',
        'notify_hr' => 'boolean',
        'forwarded_at' => 'datetime',
        'approved_at' => 'datetime',
        'revoked_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'expired_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function leaveType()
    {
        return $this->belongsTo(LeaveType::class, 'leave_type_id');
    }

    public function teamManager()
    {
        return $this->belongsTo(User::class, 'team_manager_id');
    }

    public function deptManager()
    {
        return $this->belongsTo(User::class, 'dept_manager_id');
    }

    public function forwarder()
    {
        return $this->belongsTo(User::class, 'forwarded_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function revoker()
    {
        return $this->belongsTo(User::class, 'revoked_by');
    }
}
