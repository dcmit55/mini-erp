<?php

namespace App\Models\Hr;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use App\Models\Admin\Department;
use App\Models\Hr\Employee;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class SessionShift extends Model implements AuditableContract
{
    use \OwenIt\Auditing\Auditable;

    protected $auditInclude = ['department_id', 'type_of_shift', 'start_time', 'end_time', 'break_start', 'break_end', 'is_active'];

    protected $table = 'session_shifts';

    protected $fillable = ['uid', 'department_id', 'employee_id', 'type_of_shift', 'start_time', 'end_time', 'break_start', 'break_end', 'break2_start', 'break2_end', 'for_wna', 'detect_from', 'detect_until', 'is_active', 'applicable_days', 'position_keywords'];

    protected $casts = [
        'is_active'        => 'boolean',
        'for_wna'          => 'boolean',
        'applicable_days'  => 'array',
        'position_keywords' => 'array',
    ];

    public function getRouteKeyName(): string
    {
        return 'uid';
    }

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

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function dailyAttendances()
    {
        return $this->hasMany(DailyAttendance::class, 'session_shift_id');
    }

    /**
     * Cek apakah shift berlaku untuk hari tertentu.
     * $dayOfWeek: 1=Senin ... 6=Sabtu ... 7=Minggu (ISO), null = lewati cek hari
     */
    public function appliesToDay(?int $dayOfWeek): bool
    {
        if ($this->applicable_days === null) return true;
        if ($dayOfWeek === null) return true;
        return in_array($dayOfWeek, $this->applicable_days);
    }

    /**
     * Cek apakah posisi karyawan cocok dengan position_keywords shift.
     * null position_keywords = berlaku untuk semua posisi.
     */
    public function appliesToPosition(?string $position): bool
    {
        if ($this->position_keywords === null) return true;
        if ($position === null) return false;
        $pos = strtolower($position);
        foreach ($this->position_keywords as $keyword) {
            if (str_contains($pos, strtolower($keyword))) return true;
        }
        return false;
    }

    /**
     * Auto-detect shift berdasarkan multi-kriteria (prioritas dari tinggi ke rendah):
     *   1. Shift khusus per-karyawan (employee_id)
     *   2. Shift per-posisi dalam department (position_keywords)
     *   3. Shift per-department (tanpa filter posisi)
     *   4. Shift default (department_id = NULL)
     *
     * @param int         $departmentId
     * @param string      $clockInTime    format "H:i:s" atau "H:i"
     * @param bool        $isWna
     * @param int|null    $employeeId     untuk deteksi shift per-karyawan
     * @param string|null $position       posisi karyawan (kolom position di employees)
     * @param int|null    $dayOfWeek      1=Senin … 6=Sabtu … 7=Minggu (Carbon::isoWeekday())
     */
    public static function detectFromClockIn(
        int $departmentId,
        string $clockInTime,
        bool $isWna = false,
        ?int $employeeId = null,
        ?string $position = null,
        ?int $dayOfWeek = null
    ): ?self {
        $time = strlen($clockInTime) === 5 ? $clockInTime . ':00' : $clockInTime;

        // 1. Shift khusus per-karyawan
        if ($employeeId) {
            $shift = self::where('employee_id', $employeeId)
                ->where('is_active', true)
                ->where('detect_from', '<=', $time)
                ->where('detect_until', '>', $time)
                ->get()
                ->first(fn($s) => $s->appliesToDay($dayOfWeek));

            if ($shift) return $shift;
        }

        // Ambil semua shift aktif untuk department ini (tanpa filter employee_id)
        $candidates = self::where('department_id', $departmentId)
            ->whereNull('employee_id')
            ->where('is_active', true)
            ->where('for_wna', $isWna)
            ->where('detect_from', '<=', $time)
            ->where('detect_until', '>', $time)
            ->get()
            ->filter(fn($s) => $s->appliesToDay($dayOfWeek) && $s->appliesToPosition($position));

        // 2. Prioritaskan yang punya position_keywords (lebih spesifik)
        $positionSpecific = $candidates->filter(fn($s) => $s->position_keywords !== null);
        if ($positionSpecific->isNotEmpty()) return $positionSpecific->first();

        // 3. Shift department tanpa filter posisi
        if ($candidates->isNotEmpty()) return $candidates->first();

        // 4. Fallback ke shift default (department_id = NULL)
        return self::whereNull('department_id')
            ->whereNull('employee_id')
            ->where('is_active', true)
            ->where('for_wna', $isWna)
            ->where('detect_from', '<=', $time)
            ->where('detect_until', '>', $time)
            ->get()
            ->first(fn($s) => $s->appliesToDay($dayOfWeek));
    }
}
