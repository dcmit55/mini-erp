<?php

namespace App\Models\Hr;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Hr\Employee;

class EmployeeWorkPolicy extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uid',
        'employee_id',
        'employee_no',
        'weekday_hours',
        'saturday_hours',
    ];

    protected $casts = [
        'weekday_hours' => 'decimal:2',
        'saturday_hours' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($policy) {
            if (empty($policy->uid)) {
                $policy->uid = (string) \Str::uuid();
            }
        });
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Hitung total jam kerja per minggu (Senin-Jumat + Sabtu)
     */
    public function getWeeklyHoursAttribute()
    {
        return ($this->weekday_hours * 5) + $this->saturday_hours;
    }

    /**
     * Dapatkan jam kerja berdasarkan hari (0 = Minggu, 1 = Senin, ... 6 = Sabtu)
     */
    public function getHoursForDay($dayOfWeek)
    {
        // dayOfWeek: Carbon day of week (0=Sunday, 1=Monday, ..., 6=Saturday)
        if ($dayOfWeek >= 1 && $dayOfWeek <= 5) {
            return $this->weekday_hours;
        } elseif ($dayOfWeek == 6) {
            return $this->saturday_hours;
        }
        return 0; // Minggu libur
    }
}