<?php

namespace App\Models\Production;

use Illuminate\Database\Eloquent\Model;

class Stage extends Model
{
    protected $fillable = ['stage_type_id', 'name', 'sequence', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function stageType()
    {
        return $this->belongsTo(StageType::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
