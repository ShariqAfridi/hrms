<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class AttendanceBreak extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';

    protected $table = 'attendance_breaks';

    protected $fillable = [
        'id',
        'attendance_id',
        'break_type',
        'start_time',
        'end_time',
        'duration_minutes',
        'exceeded_minutes',
        'status',
        'notes',
        'ip_address'
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'duration_minutes' => 'integer',
        'exceeded_minutes' => 'integer',
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

    public function attendance()
    {
        return $this->belongsTo(Attendance::class, 'attendance_id');
    }

    public function isActive(): bool
    {
        return $this->status === 'ACTIVE' || empty($this->end_time);
    }

    public function isExceeded(): bool
    {
        return $this->exceeded_minutes > 0;
    }

    public static function getAllowedMinutes(string $breakType): int
    {
        $type = strtoupper(trim($breakType));
        switch ($type) {
            case 'LUNCH':
                return (int) SystemSetting::get('break_lunch_max_minutes', 60);
            case 'NAMAZ':
                return (int) SystemSetting::get('break_namaz_max_minutes', 20);
            case 'SMOKE':
                return (int) SystemSetting::get('break_smoke_max_minutes', 15);
            case 'TEA':
            case 'COFFEE':
                return (int) SystemSetting::get('break_tea_max_minutes', 15);
            default:
                return (int) SystemSetting::get('break_custom_max_minutes', 30);
        }
    }

    public function getFormattedDuration(): string
    {
        $mins = $this->duration_minutes ?: 0;
        if ($mins < 60) {
            return "{$mins}m";
        }
        $hrs = floor($mins / 60);
        $rem = $mins % 60;
        return $rem > 0 ? "{$hrs}h {$rem}m" : "{$hrs}h";
    }

    public function getIcon(): string
    {
        switch (strtoupper($this->break_type)) {
            case 'LUNCH':
                return 'fa-utensils';
            case 'NAMAZ':
                return 'fa-mosque';
            case 'SMOKE':
                return 'fa-smoking';
            case 'TEA':
            case 'COFFEE':
                return 'fa-mug-hot';
            default:
                return 'fa-pause';
        }
    }

    public function getBadgeClass(): string
    {
        switch (strtoupper($this->break_type)) {
            case 'LUNCH':
                return 'bg-amber-500/10 text-amber-400 border-amber-500/20';
            case 'NAMAZ':
                return 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20';
            case 'SMOKE':
                return 'bg-orange-500/10 text-orange-400 border-orange-500/20';
            case 'TEA':
            case 'COFFEE':
                return 'bg-sky-500/10 text-sky-400 border-sky-500/20';
            default:
                return 'bg-purple-500/10 text-purple-400 border-purple-500/20';
        }
    }
}
