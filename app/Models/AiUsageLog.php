<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AiUsageLog extends Model
{
    use HasFactory;

    protected $table = 'ai_usage_logs';

    protected $fillable = [
        'user_id',
        'user_name',
        'model_used',
        'prompt',
        'response_summary',
        'tools_called',
        'prompt_tokens',
        'response_tokens',
        'total_tokens',
        'cost_usd',
        'latency_ms',
        'status',
        'error_message',
    ];

    protected $casts = [
        'tools_called' => 'array',
        'prompt_tokens' => 'integer',
        'response_tokens' => 'integer',
        'total_tokens' => 'integer',
        'cost_usd' => 'float',
        'latency_ms' => 'integer',
        'created_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function scopeToday($query)
    {
        return $query->whereDate('created_at', now()->toDateString());
    }

    public function scopeThisMonth($query)
    {
        return $query->whereYear('created_at', now()->year)
                     ->whereMonth('created_at', now()->month);
    }

    public function scopeFlash($query)
    {
        return $query->where('model_used', 'like', '%flash%');
    }

    public function scopePro($query)
    {
        return $query->where('model_used', 'like', '%pro%');
    }

    public function getCostPkrAttribute(): float
    {
        // Estimated conversion: 1 USD ≈ 280 PKR
        return round($this->cost_usd * 280, 2);
    }
}
