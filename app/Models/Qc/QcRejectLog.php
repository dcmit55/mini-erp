<?php

namespace App\Models\Qc;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class QcRejectLog extends Model
{
    protected $table = 'qc_reject_logs';

    protected $fillable = [
        'uid', 'reject_id', 'qc_project_id',
        'source', 'stage', 'item_id', 'daily_item_id', 'fail_date_str',
        'item_name', 'defect_category', 'severity', 'fail_note', 'qty_reject',
        'fail_operator', 'root_cause', 'corrective_action',
        'rework_assigned_to', 'target_completion_date',
        'rework_status', 'closed_date', 'rework_history',
    ];

    protected $casts = [
        'fail_date_str'           => 'date',
        'target_completion_date'  => 'date',
        'closed_date'             => 'datetime',
        'rework_history'          => 'array',
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
        return $this->morphMany(QcPhoto::class, 'photoable')->orderBy('sort_order');
    }

    public function appendHistory(array $entry): void
    {
        $history   = $this->rework_history ?? [];
        $history[] = array_merge($entry, ['timestamp' => now()->toISOString()]);
        $this->rework_history = $history;
    }
}
