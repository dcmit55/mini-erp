<?php

namespace App\Models\Hr;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use App\Models\Admin\Department;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class SessionShift extends Model implements AuditableContract
{
    use \OwenIt\Auditing\Auditable;

    protected $auditInclude = [
        'department_id', 'type_of_shift', 'start_time', 'end_time',
        'break_start', 'break_end', 'is_active',
    ];

    protected $table = 'session_shifts';

    protected $fillable = [
        'uid', 'department_id', 'type_of_shift',
        'start_time', 'end_time',
        'break_start', 'break_end',
        'break2_start', 'break2_end',
        'for_wna', 'detect_from', 'detect_until',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'for_wna'   => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->uid)) {
                $model->uid = (string) Str::uuid();
            }
        });
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function dailyAttendances()
    {
        return $this->hasMany(DailyAttendance::class, 'session_shift_id');
    }

    /**
     * Auto-detect shift berdasarkan department, clock-in time, dan status WNA.
     * Jika tidak ada shift spesifik untuk department tersebut,
     * fallback ke shift default (department_id = NULL).
     *
     * @param int    $departmentId
     * @param string $clockInTime  format "H:i:s" atau "H:i"
     * @param bool   $isWna
     */
    public static function detectFromClockIn(int $departmentId, string $clockInTime, bool $isWna = false): ?self
    {
        $time = strlen($clockInTime) === 5 ? $clockInTime . ':00' : $clockInTime;

        // 1. Cari shift spesifik untuk department ini
        $shift = self::where('department_id', $departmentId)
            ->where('is_active', true)
            ->where('for_wna', $isWna)
            ->where('detect_from', '<=', $time)
            ->where('detect_until', '>', $time)
            ->first();

        // 2. Fallback ke shift default (department_id = NULL)
        if (! $shift) {
            $shift = self::whereNull('department_id')
                ->where('is_active', true)
                ->where('for_wna', $isWna)
                ->where('detect_from', '<=', $time)
                ->where('detect_until', '>', $time)
                ->first();
        }

        return $shift;
    }
}
