<?php

namespace App\Models\Hr;

use Illuminate\Database\Eloquent\Model;

class ViolationCategory extends Model
{
    protected $fillable = ['code', 'name', 'can_bulk_issue', 'severity', 'is_active'];

    protected $casts = [
        'can_bulk_issue' => 'boolean',
        'is_active'      => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeBulkIssuable($query)
    {
        return $query->where('can_bulk_issue', true)->where('is_active', true);
    }
}
