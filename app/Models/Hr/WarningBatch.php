<?php

namespace App\Models\Hr;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class WarningBatch extends Model
{
    protected $fillable = [
        'uid', 'batch_name', 'incident_description',
        'violation_cat_id', 'incident_date', 'total_employees',
        'evidence_path', 'created_by',
    ];

    protected $casts = [
        'incident_date' => 'date',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (empty($model->uid)) {
                $model->uid = (string) Str::uuid();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uid';
    }

    public function violationCategory()
    {
        return $this->belongsTo(ViolationCategory::class, 'violation_cat_id');
    }

    public function warningLetters()
    {
        return $this->hasMany(WarningLetter::class, 'batch_id');
    }

    public function creator()
    {
        return $this->belongsTo(\App\Models\Admin\User::class, 'created_by');
    }
}
