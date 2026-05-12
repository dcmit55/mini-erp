<?php

namespace App\Models\Hr;

use Illuminate\Database\Eloquent\Model;

class WarningTemplate extends Model
{
    protected $fillable = [
        'sp_level', 'name', 'content_html',
        'version', 'is_active', 'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sp_level'  => 'integer',
        'version'   => 'integer',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForLevel($query, int $level)
    {
        return $query->where('sp_level', $level)->where('is_active', true);
    }
}
