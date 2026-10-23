<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Notification extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'user_id',
        'title',
        'message',
        'type',
        'data',
        'action_url',
        'is_read'
    ];

    protected $casts = [
        'data' => 'array',
        'is_read' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
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

    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    public function scopeRecent($query, int $limit = 20)
    {
        return $query->orderBy('created_at', 'desc')->limit($limit);
    }

    public function markAsRead(): bool
    {
        return $this->update(['is_read' => true]);
    }

    public function getIcon(): string
    {
        switch ($this->type) {
            case 'BREAK_EXCEEDED':
                return 'fa-triangle-exclamation text-rose-400';
            case 'BREAK_STARTED':
                return 'fa-pause text-amber-400';
            case 'BREAK_ENDED':
                return 'fa-play text-emerald-400';
            case 'ATTENDANCE_LATE':
                return 'fa-clock text-amber-400';
            case 'CLOCK_IN':
                return 'fa-right-to-bracket text-emerald-400';
            case 'CLOCK_OUT':
                return 'fa-right-from-bracket text-purple-400';
            default:
                return 'fa-bell text-indigo-400';
        }
    }
}
