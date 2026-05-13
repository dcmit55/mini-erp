<?php

namespace App\Models\Hr;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Hr\Employee;
use Illuminate\Support\Str;
use Carbon\Carbon;

class AttendanceLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'uid',
        'employee_id',
        'date',
        'clock_in',
        'clock_out',
        'total_hours',
        'import_source',
    ];

    protected $casts = [
        'date'       => 'date',
        'clock_in'   => 'datetime:H:i:s',
        'clock_out'  => 'datetime:H:i:s',
        'total_hours' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uid)) {
                $model->uid = (string) Str::uuid();
            }
        });

        static::saving(function ($model) {
            if ($model->clock_in && $model->clock_out) {
                $clockIn = Carbon::parse($model->clock_in);
                $clockOut = Carbon::parse($model->clock_out);
                $model->total_hours = round($clockOut->diffInMinutes($clockIn) / 60, 2);
            } else {
                $model->total_hours = null;
            }
        });
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    // Scope untuk filter rentang tanggal
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    // Scope untuk hanya karyawan aktif
    public function scopeActiveEmployees($query)
    {
        return $query->whereHas('employee', function ($q) {
            $q->active();
        });
    }
}