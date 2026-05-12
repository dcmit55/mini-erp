<?php
// app/Models/Hr/EmployeeWorkPolicy.php

namespace App\Models\Hr;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Hr\Employee;
use Carbon\Carbon;

class EmployeeWorkPolicy extends Model
{
    use HasFactory, SoftDeletes;

    public function getRouteKeyName(): string
    {
        return 'uid';
    }

    protected $fillable = [
        'uid',
        'employee_id',
        'employee_no',
        'weekday_hours',
        'weekday_start',
        'weekday_end',
        'saturday_hours',
        'saturday_start',
        'saturday_end',
        'sunday_hours',
        'sunday_start',
        'sunday_end',
    ];

    protected $casts = [
        'weekday_hours' => 'decimal:2',
        'saturday_hours' => 'decimal:2',
        'sunday_hours' => 'decimal:2',
        'weekday_start' => 'datetime:H:i',
        'weekday_end' => 'datetime:H:i',
        'saturday_start' => 'datetime:H:i',
        'saturday_end' => 'datetime:H:i',
        'sunday_start' => 'datetime:H:i',
        'sunday_end' => 'datetime:H:i',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($policy) {
            if (empty($policy->uid)) {
                $policy->uid = (string) \Str::uuid();
            }
            $policy->calculateHours();
        });

        static::updating(function ($policy) {
            $policy->calculateHours();
        });
    }

    /**
     * Hitung jam kerja berdasarkan start/end dan potongan break (12:00-13:00 untuk weekday)
     */
    public function calculateHours()
    {
        // Weekday (dengan potongan break 12:00-13:00)
        if ($this->weekday_start && $this->weekday_end) {
            $start = Carbon::parse($this->weekday_start);
            $end = Carbon::parse($this->weekday_end);
            $total = $end->diffInMinutes($start) / 60;

            // Potong break 1 jam (12:00-13:00) jika overlap
            $breakStart = Carbon::parse('12:00');
            $breakEnd = Carbon::parse('13:00');
            if ($start->lessThan($breakEnd) && $end->greaterThan($breakStart)) {
                $overlap = min($end, $breakEnd)->diffInMinutes(max($start, $breakStart)) / 60;
                $total -= $overlap;
            }
            $this->weekday_hours = round($total, 2);
        }

        // Saturday (tanpa break)
        if ($this->saturday_start && $this->saturday_end) {
            $start = Carbon::parse($this->saturday_start);
            $end = Carbon::parse($this->saturday_end);
            $this->saturday_hours = round($end->diffInMinutes($start) / 60, 2);
        }

        // Sunday (tanpa break)
        if ($this->sunday_start && $this->sunday_end) {
            $start = Carbon::parse($this->sunday_start);
            $end = Carbon::parse($this->sunday_end);
            $this->sunday_hours = round($end->diffInMinutes($start) / 60, 2);
        } elseif (!$this->sunday_start && !$this->sunday_end) {
            $this->sunday_hours = 0;
        }
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Total jam kerja per minggu (Senin-Jumat + Sabtu + Minggu)
     */
    public function getWeeklyHoursAttribute()
    {
        return ($this->weekday_hours * 5) + $this->saturday_hours + ($this->sunday_hours ?? 0);
    }

    /**
     * Dapatkan jam kerja berdasarkan hari (0=Minggu, 1=Senin, ..., 6=Sabtu)
     */
    public function getHoursForDay($dayOfWeek)
    {
        if ($dayOfWeek >= 1 && $dayOfWeek <= 5) {
            return $this->weekday_hours;
        } elseif ($dayOfWeek == 6) {
            return $this->saturday_hours;
        } elseif ($dayOfWeek == 0) {
            return $this->sunday_hours ?? 0;
        }
        return 0;
    }
}