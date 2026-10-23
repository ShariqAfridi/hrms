<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Organization extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id', 'name', 'slug', 'legal_name', 'company_type', 'registration_number',
        'tax_number', 'email', 'phone', 'industry', 'country', 'city', 'timezone',
        'currency', 'employee_size', 'is_active', 'description', 'about', 'website', 'logo',
        'email_config_host', 'email_config_port', 'email_config_user', 'email_config_password',
        'attendance_late_threshold', 'attendance_overtime_threshold',
        'leave_approvals_required', 'payroll_tax_rate'
    ];

    protected $appends = ['logo_url'];

    protected $casts = [
        'is_active' => 'boolean',
        'attendance_late_threshold' => 'integer',
        'attendance_overtime_threshold' => 'integer',
        'leave_approvals_required' => 'integer',
        'payroll_tax_rate' => 'float',
    ];

    public function getLogoUrlAttribute(): ?string
    {
        if (empty($this->logo)) {
            return null;
        }

        if (str_starts_with($this->logo, 'data:') || str_starts_with($this->logo, 'http://') || str_starts_with($this->logo, 'https://')) {
            return $this->logo;
        }

        $cleanPath = ltrim($this->logo, '/\\');
        if (file_exists(public_path($cleanPath))) {
            return asset($cleanPath);
        }

        if (file_exists(public_path('uploads/company_logos/' . $cleanPath))) {
            return asset('uploads/company_logos/' . $cleanPath);
        }

        return asset($cleanPath);
    }

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
            if (empty($model->slug) && !empty($model->name)) {
                $model->slug = Str::slug($model->name) . '-' . Str::random(4);
            }
        });

        static::saving(function ($model) {
            $stringFields = [
                'legal_name', 'company_type', 'registration_number', 'tax_number',
                'email', 'phone', 'industry', 'country', 'city', 'timezone',
                'currency', 'employee_size', 'description', 'about', 'website'
            ];
            foreach ($stringFields as $field) {
                if (!isset($model->attributes[$field]) || $model->attributes[$field] === null) {
                    $model->attributes[$field] = '';
                }
            }
        });
    }

    public function departments()
    {
        return $this->hasMany(Department::class, 'organization_id');
    }

    public function users()
    {
        return $this->hasMany(User::class, 'organization_id')->excludeDevs();
    }

    public function branches()
    {
        return $this->hasMany(Branch::class, 'organization_id');
    }

    public function designations()
    {
        return $this->hasMany(Designation::class, 'organization_id');
    }

    public function payrolls()
    {
        return $this->hasMany(Payroll::class, 'organization_id');
    }
}
