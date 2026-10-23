<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Attendance extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id', 'organization_id', 'user_id', 'shift_id', 'date', 'clock_in', 'clock_out',
        'total_minutes', 'total_break_minutes', 'net_work_minutes', 'status', 'current_break_type',
        'current_break_id', 'overtime_minutes', 'approval_status', 'remarks', 'device', 'ip_address',
        'location_name', 'location_verified', 'latitude', 'longitude'
    ];

    protected $casts = [
        'date' => 'datetime',
        'clock_in' => 'datetime',
        'clock_out' => 'datetime',
        'total_minutes' => 'integer',
        'total_break_minutes' => 'integer',
        'net_work_minutes' => 'integer',
        'overtime_minutes' => 'integer',
        'location_verified' => 'boolean',
        'latitude' => 'float',
        'longitude' => 'float',
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

    public function organization()
    {
        return $this->belongsTo(Organization::class, 'organization_id');
    }

    public function shift()
    {
        return $this->belongsTo(Shift::class, 'shift_id');
    }

    public function breaks()
    {
        return $this->hasMany(AttendanceBreak::class, 'attendance_id')->orderBy('start_time', 'asc');
    }

    public function activeBreak()
    {
        return $this->hasOne(AttendanceBreak::class, 'attendance_id')->where('status', 'ACTIVE')->latest('start_time');
    }

    public function isOnBreak(): bool
    {
        return !empty($this->current_break_type) && !empty($this->current_break_id);
    }

    public function recalculateTotals(): void
    {
        // Calculate shift gross minutes
        $clockInTs = $this->clock_in ? strtotime($this->clock_in) : null;
        $clockOutTs = $this->clock_out ? strtotime($this->clock_out) : ($this->clock_in ? time() : null);

        $grossMinutes = 0;
        if ($clockInTs && $clockOutTs) {
            $grossMinutes = max(0, (int) round(($clockOutTs - $clockInTs) / 60));
        }

        // Sum up completed breaks
        $totalBreakMinutes = (int) $this->breaks()->where('status', 'COMPLETED')->sum('duration_minutes');

        // Net work minutes
        $netMinutes = max(0, $grossMinutes - $totalBreakMinutes);

        // Standard 8 hours = 480 mins
        $overtime = max(0, $netMinutes - 480);

        $this->total_minutes = $grossMinutes;
        $this->total_break_minutes = $totalBreakMinutes;
        $this->net_work_minutes = $netMinutes;
        $this->overtime_minutes = $overtime;
        $this->save();
    }

    public function getFormattedTotalTime(): string
    {
        $mins = $this->total_minutes ?: 0;
        $hrs = floor($mins / 60);
        $rem = $mins % 60;
        return "{$hrs}h {$rem}m";
    }

    public function getFormattedBreakTime(): string
    {
        $mins = $this->total_break_minutes ?: 0;
        $hrs = floor($mins / 60);
        $rem = $mins % 60;
        return $hrs > 0 ? "{$hrs}h {$rem}m" : "{$rem}m";
    }

    public function getFormattedNetTime(): string
    {
        $mins = $this->net_work_minutes ?: 0;
        $hrs = floor($mins / 60);
        $rem = $mins % 60;
        return "{$hrs}h {$rem}m";
    }

    public function getDeviceDetails(): array
    {
        $ua = $this->device ?: '';
        if (empty($ua) || $ua === 'Web') {
            return [
                'name' => 'Web Browser',
                'os' => 'Desktop / Web',
                'browser' => 'Web Browser',
                'type' => 'Desktop',
                'icon' => 'fa-desktop'
            ];
        }

        $os = 'Unknown OS';
        $type = 'Desktop';
        $icon = 'fa-desktop';

        if (preg_match('/windows|win32/i', $ua)) {
            $os = 'Windows';
            if (preg_match('/Windows NT 10.0/i', $ua)) $os = 'Windows 10 / 11';
            elseif (preg_match('/Windows NT 6.3/i', $ua)) $os = 'Windows 8.1';
            elseif (preg_match('/Windows NT 6.1/i', $ua)) $os = 'Windows 7';
        } elseif (preg_match('/macintosh|mac os x/i', $ua)) {
            $os = 'macOS';
            $icon = 'fa-laptop';
        } elseif (preg_match('/iphone/i', $ua)) {
            $os = 'iOS (iPhone)';
            $type = 'Mobile';
            $icon = 'fa-mobile-screen-button';
        } elseif (preg_match('/ipad/i', $ua)) {
            $os = 'iPadOS (iPad)';
            $type = 'Tablet';
            $icon = 'fa-tablet-screen-button';
        } elseif (preg_match('/android/i', $ua)) {
            $os = 'Android Device';
            $type = 'Mobile';
            $icon = 'fa-mobile-screen-button';
        } elseif (preg_match('/linux/i', $ua)) {
            $os = 'Linux';
        }

        $browser = 'Web Browser';
        if (preg_match('/edg/i', $ua)) {
            $browser = 'Microsoft Edge';
        } elseif (preg_match('/chrome|crios/i', $ua)) {
            $browser = 'Google Chrome';
        } elseif (preg_match('/firefox|fxios/i', $ua)) {
            $browser = 'Mozilla Firefox';
        } elseif (preg_match('/safari/i', $ua) && !preg_match('/chrome|crios/i', $ua)) {
            $browser = 'Apple Safari';
        } elseif (preg_match('/opera|opr/i', $ua)) {
            $browser = 'Opera';
        }

        return [
            'name' => "{$os} • {$browser}",
            'os' => $os,
            'browser' => $browser,
            'type' => $type,
            'icon' => $icon
        ];
    }

    public function toDetailArray(): array
    {
        $clockInTime = $this->clock_in ? date('h:i:s A', strtotime($this->clock_in)) : '--';
        $clockInDate = $this->clock_in ? date('d M Y', strtotime($this->clock_in)) : ($this->date ? date('d M Y', strtotime($this->date)) : '--');
        $clockOutTime = $this->clock_out ? date('h:i:s A', strtotime($this->clock_out)) : ($this->clock_in ? 'In Progress' : '--');
        $clockOutDate = $this->clock_out ? date('d M Y', strtotime($this->clock_out)) : '--';

        $deviceInfo = $this->getDeviceDetails();

        $breaksList = $this->breaks->map(function ($b) {
            return [
                'id' => $b->id,
                'break_type' => $b->break_type,
                'icon' => $b->getIcon(),
                'badge_class' => $b->getBadgeClass(),
                'start_time' => date('h:i A', strtotime($b->start_time)),
                'end_time' => $b->end_time ? date('h:i A', strtotime($b->end_time)) : 'In Progress',
                'duration' => $b->getFormattedDuration(),
                'duration_minutes' => $b->duration_minutes,
                'exceeded_minutes' => $b->exceeded_minutes,
                'is_exceeded' => $b->isExceeded(),
                'status' => $b->status,
                'notes' => $b->notes,
            ];
        })->values()->toArray();

        return [
            'id' => (string) $this->id,
            'user_name' => $this->user->name ?? 'User',
            'user_email' => $this->user->email ?? '',
            'user_avatar' => strtoupper(substr($this->user->name ?? 'U', 0, 1)),
            'department' => $this->user->department->name ?? 'General',
            'designation' => $this->user->designation->name ?? 'Staff',
            'organization' => $this->organization->name ?? ($this->user->organization->name ?? 'General'),
            'date' => $this->date ? date('d M Y', strtotime($this->date)) : '--',
            'status' => $this->status,
            'is_on_break' => $this->isOnBreak(),
            'current_break_type' => $this->current_break_type,
            'clock_in_time' => $clockInTime,
            'clock_in_date' => $clockInDate,
            'clock_out_time' => $clockOutTime,
            'clock_out_date' => $clockOutDate,
            'gross_shift' => $this->total_minutes ? $this->getFormattedTotalTime() : ($this->clock_in ? 'In Progress' : '--'),
            'total_breaks' => $this->getFormattedBreakTime(),
            'breaks_count' => $this->breaks->count(),
            'net_work' => $this->net_work_minutes ? $this->getFormattedNetTime() : ($this->clock_in ? 'Calculating...' : '--'),
            'overtime' => $this->overtime_minutes > 0 ? "{$this->overtime_minutes}m" : '0m',
            'ip_address' => $this->ip_address ?: '127.0.0.1 (Local / Office)',
            'location_name' => $this->location_name ?: ($this->location_verified ? 'Office Location' : 'Remote / Mobile'),
            'location_verified' => (bool) $this->location_verified,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'has_coordinates' => !empty($this->latitude) && !empty($this->longitude),
            'google_maps_url' => (!empty($this->latitude) && !empty($this->longitude)) ? "https://www.google.com/maps?q={$this->latitude},{$this->longitude}" : null,
            'device_name' => $deviceInfo['name'],
            'device_os' => $deviceInfo['os'],
            'device_browser' => $deviceInfo['browser'],
            'device_type' => $deviceInfo['type'],
            'device_icon' => $deviceInfo['icon'],
            'raw_user_agent' => $this->device ?: 'Direct Web Session',
            'approval_status' => $this->approval_status ?: 'APPROVED',
            'remarks' => $this->remarks ?: 'No special remarks recorded.',
            'breaks' => $breaksList,
        ];
    }
}
