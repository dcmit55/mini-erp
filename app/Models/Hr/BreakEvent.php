<?php

namespace App\Models\Hr;

use Illuminate\Database\Eloquent\Model;

class BreakEvent extends Model
{
    protected $table = 'break_events';

    protected $fillable = [
        'uid', 'daily_attendance_id', 'employee_id', 'work_date',
        'break_out', 'break_in', 'classification',
        'within_break_window', 'flagged', 'flag_reason',
    ];

    protected $casts = [
        'break_out'           => 'datetime',
        'break_in'            => 'datetime',
        'work_date'           => 'date',
        'within_break_window' => 'boolean',
        'flagged'             => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (empty($model->uid)) {
                $model->uid = (string) \Illuminate\Support\Str::uuid();
            }
        });
    }

    public function dailyAttendance()
    {
        return $this->belongsTo(DailyAttendance::class, 'daily_attendance_id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }
}
