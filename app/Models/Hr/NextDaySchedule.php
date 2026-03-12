<?php

namespace App\Models\Hr;

use Illuminate\Database\Eloquent\Model;

class NextDaySchedule extends Model
{
    protected $table = 'next_day_schedules';

    protected $fillable = [
        'employee_id', 'reference_date',
        'actual_clock_out', 'earliest_allowed_start',
        'blocked_tap_detected',
    ];

    protected $casts = [
        'reference_date'        => 'date',
        'actual_clock_out'      => 'datetime',
        'earliest_allowed_start'=> 'datetime',
        'blocked_tap_detected'  => 'boolean',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }
}
