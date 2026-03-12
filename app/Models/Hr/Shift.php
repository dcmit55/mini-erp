<?php

namespace App\Models\Hr;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class Shift extends Model
{
    use SoftDeletes;

    protected $table = 'shifts';

    protected $fillable = [
        'uid', 'name', 'code', 'shift_start', 'shift_end', 'is_overnight',
        'expected_hours', 'min_hours_full', 'min_hours_short', 'ot_threshold_hours',
        'break_window_start', 'break_window_end', 'break_max_duration_mins',
        'min_rest_between_shifts_mins', 'is_active',
    ];

    protected $casts = [
        'is_overnight' => 'boolean',
        'is_active'    => 'boolean',
        'expected_hours'      => 'decimal:2',
        'min_hours_full'      => 'decimal:2',
        'min_hours_short'     => 'decimal:2',
        'ot_threshold_hours'  => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (empty($model->uid)) {
                $model->uid = (string) \Illuminate\Support\Str::uuid();
            }
        });
    }

    /**
     * Apakah waktu (HH:MM) masuk dalam jendela istirahat shift ini?
     */
    public function isWithinBreakWindow(Carbon $time): bool
    {
        if (! $this->break_window_start || ! $this->break_window_end) {
            return false;
        }

        $bwStart = Carbon::createFromTimeString($this->break_window_start);
        $bwEnd   = Carbon::createFromTimeString($this->break_window_end);

        // Ambil hanya jam:menit dari $time untuk perbandingan
        $t = Carbon::createFromTimeString($time->format('H:i:s'));

        return $t->between($bwStart, $bwEnd);
    }

    /**
     * Tentukan hours_status berdasarkan jam aktual.
     */
    public function resolveHoursStatus(float $actualHours): string
    {
        if ($actualHours >= $this->ot_threshold_hours) return 'OT';
        if ($actualHours >= $this->min_hours_full)     return 'FULL';
        if ($actualHours >= $this->min_hours_short)    return 'SHORT';
        return 'INCOMPLETE';
    }

    public function dailyAttendances()
    {
        return $this->hasMany(DailyAttendance::class, 'shift_id');
    }
}
