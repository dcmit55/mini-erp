<?php

namespace App\Models\Qc;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class QcPhoto extends Model
{
    protected $table = 'qc_photos';

    protected $fillable = [
        'uid', 'photoable_type', 'photoable_id',
        'path', 'disk', 'context', 'meta', 'sort_order',
    ];

    protected $appends = ['url'];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (empty($model->uid)) {
                $model->uid = (string) Str::uuid();
            }
        });
    }

    public function photoable()
    {
        return $this->morphTo();
    }

    public function getUrlAttribute(): string
    {
        return Storage::disk($this->disk)->url($this->path);
    }
}
