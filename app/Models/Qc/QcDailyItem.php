<?php

namespace App\Models\Qc;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class QcDailyItem extends Model
{
    protected $table = 'qc_daily_items';

    protected $fillable = [
        'uid', 'qc_daily_progress_id', 'item_id',
        'status', 'note', 'operators', 'parts_data',
        'is_finalized', 'finalize_ts',
    ];

    protected $casts = [
        'operators'    => 'array',
        'parts_data'   => 'array',
        'is_finalized' => 'boolean',
        'finalize_ts'  => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (empty($model->uid)) {
                $model->uid = (string) Str::uuid();
            }
        });
    }

    public function dailyProgress()
    {
        return $this->belongsTo(QcDailyProgress::class, 'qc_daily_progress_id');
    }

    public function photos()
    {
        return $this->morphMany(QcPhoto::class, 'photoable')->orderBy('sort_order');
    }
}
