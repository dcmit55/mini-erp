<?php
// filepath: app/Models/Hr/Skillset.php

namespace App\Models\Hr;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Skillset extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'category', 'description', 'proficiency_required', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Relationship to employees
    public function employees()
    {
        return $this->belongsToMany(Employee::class, 'employee_skillset')->withPivot('proficiency_level', 'acquired_date', 'last_used_date', 'notes')->withTimestamps();
    }

    // Scope for active skillsets
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Accessor untuk badge color berdasarkan category
    public function getCategoryBadgeAttribute()
    {
        $colors = [
            'Production' => 'primary',
            'Technical' => 'info',
            'Quality Control' => 'success',
            'Maintenance' => 'warning',
            'Administrative' => 'secondary',
        ];

        return [
            'color' => $colors[$this->category] ?? 'secondary',
            'text' => $this->category ?? 'General',
        ];
    }

    // Accessor untuk proficiency badge
    public function getProficiencyBadgeAttribute()
    {
        $colors = [
            'basic' => 'light text-dark',
            'intermediate' => 'warning',
            'advanced' => 'success',
        ];

        return [
            'color' => $colors[$this->proficiency_required] ?? 'secondary',
            'text' => ucfirst($this->proficiency_required),
        ];
    }

    // Static method untuk category options
    public static function getCategoryOptions()
    {
        return [
            'Production' => 'Production',
            'Technical' => 'Technical',
            'Quality Control' => 'Quality Control',
            'Maintenance' => 'Maintenance',
            'Administrative' => 'Administrative',
        ];
    }

    // Static method untuk proficiency options
    public static function getProficiencyOptions()
    {
        return [
            'basic' => 'Basic',
            'intermediate' => 'Intermediate',
            'advanced' => 'Advanced',
        ];
    }
}
