<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Designation extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['id', 'organization_id', 'title', 'code'];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
            if (empty($model->code)) {
                $model->code = self::generateCode($model->title);
            }
        });

        static::updating(function ($model) {
            if (empty($model->code)) {
                $model->code = self::generateCode($model->title);
            }
        });
    }

    public static function generateCode(?string $title): string
    {
        $title = trim($title ?? '');
        if (empty($title)) {
            return 'DES-' . strtoupper(Str::random(4));
        }

        $clean = preg_replace('/[^A-Za-z0-9\s]/', '', $title);
        $words = array_values(array_filter(preg_split('/\s+/', trim($clean))));

        if (count($words) > 1) {
            $abbr = '';
            foreach ($words as $w) {
                if (strlen($w) > 0) {
                    $abbr .= strtoupper(substr($w, 0, 1));
                }
            }
            if (strlen($abbr) >= 2) {
                return substr($abbr, 0, 10);
            }
        }

        $single = preg_replace('/[^A-Za-z0-9]/', '', $title);
        if (empty($single)) {
            return 'DES-' . strtoupper(Str::random(4));
        }
        return strtoupper(substr($single, 0, 8));
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class, 'organization_id');
    }

    public function users()
    {
        return $this->hasMany(User::class, 'designation_id')->excludeDevs();
    }
}
