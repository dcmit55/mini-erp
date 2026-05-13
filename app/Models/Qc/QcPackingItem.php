<?php

namespace App\Models\Qc;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class QcPackingItem extends Model
{
    protected $table = 'qc_packing_items';

    protected $fillable = [
        'uid', 'qc_project_id', 'name', 'type',
        'is_checked', 'is_hidden', 'sort_order',
    ];

    protected $casts = [
        'is_checked' => 'boolean',
        'is_hidden'  => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (empty($model->uid)) {
                $model->uid = (string) Str::uuid();
            }
        });
    }

    public function qcProject()
    {
        return $this->belongsTo(QcProject::class, 'qc_project_id');
    }

    public function photos()
    {
        return $this->morphMany(QcPhoto::class, 'photoable')
            ->where('context', 'packing_item')
            ->orderBy('sort_order');
    }

    public function verifyPhotos()
    {
        return $this->morphMany(QcPhoto::class, 'photoable')
            ->where('context', 'packing_verify');
    }
}
