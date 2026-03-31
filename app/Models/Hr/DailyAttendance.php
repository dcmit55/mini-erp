<?php

namespace App\Models\Hr;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Hr\Employee;
use App\Models\Hr\SessionShift;
use App\Models\Admin\User;
use Illuminate\Support\Str;

class DailyAttendance extends Model
{
    use HasFactory;

    protected $table = 'daily_attendances';

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->uid)) {
                $model->uid = (string) Str::uuid();
            }
        });
    }

    protected $fillable = [
        'employee_id',
        'date',
        'clock_in',
        'clock_out',
        'total_hours',
        'late_minutes',
        'late_deduction',
        'early_leave_minutes',
        'early_leave_deduction',
        'overtime_minutes',
        'overtime_pay',
        'status',
        'remarks',
        'created_by',
        'updated_by',
        'is_locked',
        'uid',
        'session_shift_id',
    ];

    protected $casts = [
        'date'     => 'date',
        'clock_in' => 'datetime:H:i:s',
        'clock_out'=> 'datetime:H:i:s',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function sessionShift()
    {
        return $this->belongsTo(SessionShift::class, 'session_shift_id');
    }

}
