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
        // Tidak filter for_wna — null-dept shifts berlaku untuk semua karyawan.
        return self::whereNull('department_id')
            ->whereNull('employee_id')
            ->where('is_active', true)
            ->where('detect_from', '<=', $time)
            ->where('detect_until', '>', $time)
            ->get()
            ->first(fn($s) => $s->appliesToDay($dayOfWeek));
    }

    /**
     * Fallback khusus Sabtu: cari shift yang applicable di hari Sabtu tanpa constraint jam.
     * Digunakan ketika detectFromClockIn gagal di hari Sabtu (jam clock-in di luar window).
     * Prioritas: dept-specific → null-dept (GENERAL-S).
     */
    /**
     * Fallback khusus Sabtu tanpa constraint jam clock-in.
     * Prioritas: per-karyawan → per-dept → null-dept (GENERAL-S).
     */
    public static function detectSaturdayFallback(
        int $departmentId,
        bool $isWna = false,
        ?int $employeeId = null
    ): ?self {
        // 1. Shift Sabtu khusus karyawan (mis. Emilia punya shift sendiri untuk Sabtu)
        if ($employeeId) {
            $empShift = self::where('employee_id', $employeeId)
                ->where('is_active', true)
                ->get()
                ->first(fn($s) => $s->appliesToDay(6));
            if ($empShift) return $empShift;
        }

        // 2. Shift Sabtu per-dept (mis. HRGA → CHEF-S)
        $deptShift = self::where('department_id', $departmentId)
            ->whereNull('employee_id')
            ->where('is_active', true)
            ->where('for_wna', $isWna)
            ->get()
            ->first(fn($s) => $s->appliesToDay(6));
        if ($deptShift) return $deptShift;

        // 3. Fallback null-dept → GENERAL-S (mayoritas karyawan)
        return self::whereNull('department_id')
            ->whereNull('employee_id')
            ->where('is_active', true)
            ->get()
            ->first(fn($s) => $s->appliesToDay(6));
    }
}
