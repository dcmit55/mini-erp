<?php

namespace App\Models\Production;

use Illuminate\Database\Eloquent\Model;

class TimingPart extends Model
{
    protected $table = 'timing_parts';

    protected $fillable = ['name', 'department_type', 'sort_order', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    /**
     * Scope: active parts only, ordered by sort_order
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }

    /**
     * Scope: filter by department type (includes 'general' for all)
     */
    public function scopeForDept($query, string $deptType)
    {
        return $query->where(function ($q) use ($deptType) {
            $q->where('department_type', $deptType)->orWhere('department_type', 'general');
        });
    }
}
