<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class LeaveType extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id', 'organization_id', 'name', 'days_allocated', 'color', 'icon', 'is_paid',
        'max_annual_limit', 'carry_forward', 'encashment', 'gender', 'min_service_months',
        'attachment_required', 'approval_workflow'
    ];

    protected $casts = [
        'days_allocated' => 'integer',
        'is_paid' => 'boolean',
        'max_annual_limit' => 'integer',
        'carry_forward' => 'boolean',
        'encashment' => 'boolean',
        'attachment_required' => 'boolean',
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
}
