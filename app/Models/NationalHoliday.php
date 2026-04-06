<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NationalHoliday extends Model
{
    protected $fillable = ['date', 'name', 'year', 'is_joint_leave'];

    protected $casts = [
        'date'          => 'date',
        'is_joint_leave'=> 'boolean',
    ];

    public function scopeForYear($query, int $year)
    {
        return $query->where('year', $year);
    }

    public function scopeNationalOnly($query)
    {
        return $query->where('is_joint_leave', false);
    }
}
