<?php

namespace App\Models\Qc;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class QcChecklistItem extends Model
{
    protected $table = 'qc_checklist_items';

    protected $fillable = [
        'uid', 'qc_project_id', 'section_id', 'item_id', 'status', 'note',
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
}
