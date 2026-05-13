<?php

namespace App\Models\Qc;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class QcDailyProgress extends Model
{
    protected $table = 'qc_daily_progress';

    protected $fillable = [
        'uid', 'qc_project_id', 'stage', 'date', 'session_note', 'operators',
    ];

    protected $casts = [
        'date'      => 'date',
        'operators' => 'array',
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

    public function items()
    {
        return $this->hasMany(QcDailyItem::class, 'qc_daily_progress_id');
    }
}
