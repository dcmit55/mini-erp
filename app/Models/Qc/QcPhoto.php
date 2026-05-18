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
        $disk = $this->disk ?? 'public';
        // qc_public disk stores directly inside public/storage/, so path is already relative to /storage/
        if ($disk === 'qc_public') {
            return '/storage/' . ltrim($this->path, '/');
        }
        // Legacy 'public' disk — files in storage/app/public/ (may not be web-accessible without symlink)
        if ($disk === 'public') {
            return '/storage/' . ltrim($this->path, '/');
        }
        return Storage::disk($disk)->url($this->path);
    }
}
