<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class FingerprintLog extends Model
{
    protected $table = 'fingerprint_logs';
    protected $fillable = ['uid', 'cloud_id', 'event_time', 'payload'];
    protected $casts = [
        'payload' => 'array',
        'event_time' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->uid)) {
                $model->uid = (string) Str::uuid();
            }
        });
    }
}