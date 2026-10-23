<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Payroll extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id', 'organization_id', 'name', 'code', 'description', 'type', 'pay_frequency',
        'currency', 'default_working_days', 'start_date', 'end_date', 'notes',
        'payroll_manager_id', 'base_salary', 'allowances', 'deductions', 'tax_rate',
        'status', 'overtime_rate_type', 'overtime_multiplier', 'overtime_fixed_rate',
        'late_deduction_per_occurrence'
    ];

    protected $casts = [
        'default_working_days' => 'integer',
        'base_salary' => 'float',
        'allowances' => 'float',
        'deductions' => 'float',
        'tax_rate' => 'float',
        'overtime_multiplier' => 'float',
        'overtime_fixed_rate' => 'float',
        'late_deduction_per_occurrence' => 'float',
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

    protected $appends = [
        'total_monthly_outflow',
        'total_base_salary',
        'total_allowances',
        'total_deductions',
        'total_cash_allowance',
        'total_combined_outflow',
    ];

    public function organization()
    {
        return $this->belongsTo(Organization::class, 'organization_id');
    }

    public function payrollManager()
    {
        return $this->belongsTo(User::class, 'payroll_manager_id');
    }

    public function employees()
    {
        return $this->hasMany(User::class, 'payroll_id')->excludeDevs();
    }

    /**
     * Dynamically calculate total base salary for all active employees enrolled in this payroll tier.
     */
    public function getTotalBaseSalaryAttribute(): float
    {
        if ($this->relationLoaded('employees')) {
            return (float) $this->employees->where('is_active', true)->sum('base_salary');
        }
        return (float) $this->employees()->where('is_active', true)->sum('base_salary');
    }

    /**
     * Dynamically calculate total allowances for all active employees enrolled in this payroll tier.
     */
    public function getTotalAllowancesAttribute(): float
    {
        if ($this->relationLoaded('employees')) {
            return (float) $this->employees->where('is_active', true)->sum('allowances');
        }
        return (float) $this->employees()->where('is_active', true)->sum('allowances');
    }

    /**
     * Dynamically calculate total deductions for all active employees enrolled in this payroll tier.
     */
    public function getTotalDeductionsAttribute(): float
    {
        if ($this->relationLoaded('employees')) {
            return (float) $this->employees->where('is_active', true)->sum('deductions');
        }
        return (float) $this->employees()->where('is_active', true)->sum('deductions');
    }

    /**
     * Dynamically calculate total confidential cash allowances (off-the-record) for all active employees.
     */
    public function getTotalCashAllowanceAttribute(): float
    {
        if ($this->relationLoaded('employees')) {
            return (float) $this->employees->where('is_active', true)->sum('cash_allowance');
        }
        return (float) $this->employees()->where('is_active', true)->sum('cash_allowance');
    }

    /**
     * Dynamically calculate total company monthly bank payroll outflow for all active employees enrolled in this payroll tier.
     */
    public function getTotalMonthlyOutflowAttribute(): float
    {
        $emps = $this->relationLoaded('employees')
            ? $this->employees->where('is_active', true)
            : $this->employees()->where('is_active', true)->get();

        $total = 0;
        foreach ($emps as $emp) {
            $base = (float) ($emp->base_salary ?? 0);
            $allowances = (float) ($emp->allowances ?? 0);
            $deductions = (float) ($emp->deductions ?? 0);
            $total += max(0, $base + $allowances - $deductions);
        }
        return $total;
    }

    /**
     * Dynamically calculate total combined company monthly outflow (Bank Payroll + Confidential Cash in Hand).
     */
    public function getTotalCombinedOutflowAttribute(): float
    {
        return $this->total_monthly_outflow + $this->total_cash_allowance;
    }

    public function getTotalMonthlyOutflow(): float
    {
        return $this->total_monthly_outflow;
    }
}
