<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Payslip extends Model
{
    use HasFactory;

    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'id',
        'payroll_run_id',
        'user_id',
        'base_salary',
        'allowances',
        'fuel_allowance',
        'special_allowance',
        'other_allowances',
        'deductions',
        'advance_deduction',
        'late_deduction',
        'late_days_deducted',
        'absent_deduction',
        'eobi_deduction',
        'other_deductions',
        'tax',
        'net_pay',
        'pay_date',
        'payment_month',
        'status',
        'payment_method',
        'template_style',
        'notes',
        'created_at',
    ];

    public function getRefNoAttribute(): string
    {
        return 'PSL-' . strtoupper(substr(str_replace('-', '', $this->id), 0, 8));
    }

    protected $casts = [
        'base_salary' => 'float',
        'allowances' => 'float',
        'fuel_allowance' => 'float',
        'special_allowance' => 'float',
        'other_allowances' => 'float',
        'deductions' => 'float',
        'advance_deduction' => 'float',
        'late_deduction' => 'float',
        'late_days_deducted' => 'integer',
        'absent_deduction' => 'float',
        'eobi_deduction' => 'float',
        'tax' => 'float',
        'net_pay' => 'float',
        'created_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }
        });
    }

    public function payrollRun()
    {
        return $this->belongsTo(PayrollRun::class, 'payroll_run_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
