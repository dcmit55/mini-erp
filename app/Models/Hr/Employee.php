<?php

namespace App\Models\Hr;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Production\Timing;
use App\Models\Hr\EmployeeDocument;
use App\Models\Hr\Skillset;
use App\Models\Hr\EmployeeWorkPolicy;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use Illuminate\Support\Str; 

class Employee extends Model implements AuditableContract
{
    use HasFactory, SoftDeletes, \OwenIt\Auditing\Auditable;

    protected $fillable = [
        'employee_no', 'name', 'employment_type', 'citizenship', 'photo', 'position',
        'department_id', 'default_shift_id', 'email', 'phone', 'address', 'gender', 'ktp_id',
        'place_of_birth', 'date_of_birth', 'rekening', 'hire_date',
        'contract_end_date', 'salary', 'saldo_cuti', 'status', 'notes',
        'username', 'uid', 'device_registered_at', 'biometric_enrolled_at',
        'menstruation_leave_approved', 'menstruation_leave_approved_at',
        'is_production', 'is_leader_capacity',
    ];

    protected $casts = [
        'hire_date' => 'date',
        'contract_end_date' => 'date',
        'date_of_birth' => 'date',
        'salary' => 'decimal:2',
        'saldo_cuti' => 'decimal:2',
        'device_registered_at' => 'datetime',
        'biometric_enrolled_at' => 'datetime',
        'menstruation_leave_approved_at' => 'datetime',
        'is_production'     => 'boolean',
        'is_leader_capacity' => 'boolean',
    ];

    protected $auditInclude = [
        'employee_no', 'name', 'employment_type', 'position', 'department_id', 
        'email', 'phone', 'address', 'gender', 'ktp_id', 'place_of_birth', 
        'date_of_birth', 'rekening', 'hire_date', 'contract_end_date', 
        'salary', 'saldo_cuti', 'status'
        // username dan uid tidak ditambahkan secara default, bisa ditambahkan jika perlu
    ];

    protected $auditTimestamps = true;

    public function getRouteKeyName(): string
    {
        return 'uid';
    }

    /**
     * Boot method untuk model events
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($employee) {
            // Existing: set employee_no jika kosong
            if (empty($employee->employee_no)) {
                $employee->employee_no = self::generateEmployeeNo();
            } else {
                // Pastikan format DCM- jika user input manual
                $employee->employee_no = self::formatEmployeeNo($employee->employee_no);
            }

            // TAMBAHKAN: set UID otomatis jika belum diisi
            if (empty($employee->uid)) {
                $employee->uid = (string) Str::uuid();
            }

            // Auto-set status to inactive if contract expired
            $employee->checkAndUpdateContractStatus();
        });

        static::updating(function ($employee) {
            if ($employee->isDirty('employee_no') && !empty($employee->employee_no)) {
                // Pastikan format DCM- jika user update manual
                $employee->employee_no = self::formatEmployeeNo($employee->employee_no);
            }

            // Set UID otomatis jika belum ada (data lama sebelum migration)
            if (empty($employee->uid)) {
                $employee->uid = (string) Str::uuid();
            }

            // Auto-set status to inactive if contract expired
            $employee->checkAndUpdateContractStatus();
        });

        // Sinkronisasi employee_no ke work policy jika berubah
        static::updated(function ($employee) {
            if ($employee->isDirty('employee_no')) {
                if ($employee->workPolicy) {
                    $employee->workPolicy()->update(['employee_no' => $employee->employee_no]);
                }
            }
        });

        // Buat work policy default untuk karyawan baru (opsional)
        static::created(function ($employee) {
            // Buat work policy default (weekday 8 jam, sabtu 5 jam)
            $employee->workPolicy()->create([
                'uid' => \Str::uuid(), // atau bisa gunakan uid yang sudah ada? Terserah
                'employee_no' => $employee->employee_no,
                'weekday_hours' => 8.00,
                'saturday_hours' => 5.00,
            ]);
        });
    }

    /**
     * =============================================
     * EXISTING METHODS (TIDAK DIUBAH)
     * =============================================
     */
    public static function formatEmployeeNo($input)
    {
        $number = preg_replace('/[^0-9]/', '', $input);
        if (empty($number)) {
            return self::generateEmployeeNo();
        }
        return 'DCM-' . str_pad($number, 4, '0', STR_PAD_LEFT);
    }

    public static function validateEmployeeNo($employeeNo, $excludeId = null)
    {
        $formatted = self::formatEmployeeNo($employeeNo);
        $query = self::where('employee_no', $formatted);
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        return !$query->exists();
    }

    public function getEmployeeNumberOnlyAttribute()
    {
        return str_replace('DCM-', '', $this->employee_no);
    }

    public function getAgeAttribute()
    {
        return $this->date_of_birth ? $this->date_of_birth->age : null;
    }

    public function getFormattedKtpIdAttribute()
    {
        if (!$this->ktp_id) return '-';
        $clean = preg_replace('/[^0-9]/', '', $this->ktp_id);
        if (strlen($clean) === 16) {
            return substr($clean, 0, 4) . '-' . substr($clean, 4, 4) . '-' . substr($clean, 8, 4) . '-' . substr($clean, 12, 4);
        }
        return $this->ktp_id;
    }

    public function getGenderLabelAttribute()
    {
        return $this->gender ? ucfirst($this->gender) : '-';
    }

    public function timings()
    {
        return $this->hasMany(Timing::class);
    }

    public function department()
    {
        return $this->belongsTo(\App\Models\Admin\Department::class);
    }

    public function defaultShift()
    {
        return $this->belongsTo(\App\Models\Hr\SessionShift::class, 'default_shift_id');
    }

    public function documents()
    {
        return $this->hasMany(EmployeeDocument::class);
    }

    public function getFormattedSalaryAttribute()
    {
        return $this->salary ? 'Rp ' . number_format($this->salary, 0, ',', '.') : '-';
    }

    public function getStatusBadgeAttribute()
    {
        $colors = [
            'active'           => 'success',
            'inactive'         => 'danger',
            'pending_contract' => 'warning',
        ];
        $labels = [
            'active'           => 'Active',
            'inactive'         => 'Inactive',
            'pending_contract' => 'Pending Contract',
        ];
        return [
            'color' => $colors[$this->status] ?? 'secondary',
            'text'  => $labels[$this->status] ?? ucfirst($this->status),
        ];
    }

    public function getPhotoUrlAttribute()
    {
        if ($this->photo && Storage::disk('public')->exists($this->photo)) {
            return Storage::url($this->photo);
        }
        return asset('images/default-avatar.png');
    }

    public function getFormattedRekeningAttribute()
    {
        if (!$this->rekening) return '-';
        $clean = preg_replace('/[^0-9]/', '', $this->rekening);
        if (empty($clean)) return '-';
        return preg_replace('/(\d{4})(?=\d)/', '$1-', $clean);
    }

    public function getEmploymentTypeBadgeAttribute()
    {
        $colors = [
            'PKWT' => 'primary',
            'PKWTT' => 'success',
            'Daily Worker' => 'warning',
            'Probation' => 'info',
            'Internship' => 'secondary',
        ];
        return [
            'color' => $colors[$this->employment_type] ?? 'secondary',
            'text' => $this->employment_type,
        ];
    }

    public static function getEmploymentTypeOptions()
    {
        return [
            'PKWT' => 'PKWT (Fixed-term Contract)',
            'PKWTT' => 'PKWTT (Permanent)',
            'Daily Worker' => 'Daily Worker',
            'Probation' => 'Probation',
            'Internship' => 'Internship',
        ];
    }

    public function getContractStatusAttribute()
    {
        if (!$this->contract_end_date) return null;
        $now = \Carbon\Carbon::now();
        $daysRemaining = $now->diffInDays($this->contract_end_date, false);
        if ($daysRemaining < 0) {
            return [
                'status' => 'expired',
                'color' => 'danger',
                'text' => 'Expired',
                'days_remaining' => 0,
            ];
        } elseif ($daysRemaining <= 30) {
            return [
                'status' => 'expiring_soon',
                'color' => 'warning',
                'text' => 'Expiring Soon',
                'days_remaining' => $daysRemaining,
            ];
        } else {
            return [
                'status' => 'active',
                'color' => 'success',
                'text' => 'Active',
                'days_remaining' => $daysRemaining,
            ];
        }
    }

    public function getContractDurationAttribute()
    {
        if (!$this->hire_date || !$this->contract_end_date) return null;
        $diffInMonths = $this->hire_date->diffInMonths($this->contract_end_date);
        $diffInDays = $this->hire_date->diffInDays($this->contract_end_date);
        return [
            'months' => $diffInMonths,
            'days' => $diffInDays,
            'formatted' => "{$diffInMonths} months ({$diffInDays} days)",
        ];
    }

    public function skillsets()
    {
        return $this->belongsToMany(Skillset::class, 'employee_skillset')
                    ->withPivot('proficiency_level', 'acquired_date', 'last_used_date', 'notes')
                    ->withTimestamps();
    }

    public function getFormattedSkillsetsAttribute()
    {
        return $this->skillsets->map(function ($skillset) {
            return [
                'id' => $skillset->id,
                'name' => $skillset->name,
                'category' => $skillset->category,
                'proficiency' => $skillset->pivot->proficiency_level,
                'proficiency_badge' => $this->getProficiencyBadge($skillset->pivot->proficiency_level),
                'acquired_date' => $skillset->pivot->acquired_date,
            ];
        });
    }

    public function getProficiencyBadge($level)
    {
        $colors = [
            'basic' => 'light text-dark',
            'intermediate' => 'warning',
            'advanced' => 'success',
        ];
        return [
            'color' => $colors[$level] ?? 'secondary',
            'text' => ucfirst($level),
        ];
    }

    public function hasSkill($skillName)
    {
        return $this->skillsets()->where('name', $skillName)->exists();
    }

    public function getSkillProficiency($skillName)
    {
        $skill = $this->skillsets()->where('name', $skillName)->first();
        return $skill ? $skill->pivot->proficiency_level : null;
    }

    public function checkAndUpdateContractStatus($autoSave = false)
    {
        if (!$this->contract_end_date) return false;
        $today = \Carbon\Carbon::today();
        $contractValid = $this->contract_end_date->gte($today);

        if ($this->status === 'active' && !$contractValid) {
            $oldStatus = $this->status;
            $this->status = 'pending_contract';
            $expiredDate = $this->contract_end_date->format('Y-m-d');
            $updateNote = "[Auto-updated] Status changed to 'pending_contract' - Contract expired on {$expiredDate}";
            if (!str_contains($this->notes ?? '', $updateNote)) {
                $this->notes = trim(($this->notes ?? '') . "\n" . $updateNote);
            }
            \Log::info('Employee contract expired - Auto-updated to pending_contract', [
                'employee_id' => $this->id ?? 'new',
                'employee_no' => $this->employee_no,
                'name' => $this->name,
                'contract_end_date' => $expiredDate,
                'old_status' => $oldStatus,
                'new_status' => 'pending_contract',
            ]);
            return true;
        }

        if (in_array($this->status, ['inactive', 'pending_contract']) && $contractValid) {
            $wasAutoInactive = str_contains($this->notes ?? '', '[Auto-updated]');
            if ($wasAutoInactive) {
                $oldStatus = $this->status;
                $this->status = 'active';
                $newEndDate = $this->contract_end_date->format('Y-m-d');
                $updateNote = "[Auto-updated] Status changed to 'active' - Contract extended until {$newEndDate}";
                if (!str_contains($this->notes ?? '', $updateNote)) {
                    $this->notes = trim(($this->notes ?? '') . "\n" . $updateNote);
                }
                \Log::info('Employee contract extended - Auto-updated to active', [
                    'employee_id' => $this->id ?? 'new',
                    'employee_no' => $this->employee_no,
                    'name' => $this->name,
                    'contract_end_date' => $newEndDate,
                    'old_status' => $oldStatus,
                    'new_status' => 'active',
                ]);
                return true;
            }
        }
        return false;
    }

    public static function updateExpiredContracts()
    {
        $today = \Carbon\Carbon::today();
        $expiredEmployees = self::where('status', 'active')
                                ->whereNotNull('contract_end_date')
                                ->where('contract_end_date', '<', $today)
                                ->get();
        // Also check pending_contract employees whose notes indicate auto-update
        // (no action needed here — they stay pending until HR resolves)
        $count = 0;
        foreach ($expiredEmployees as $employee) {
            $employee->checkAndUpdateContractStatus();
            if ($employee->isDirty('status')) {
                $employee->saveQuietly();
                $count++;
            }
        }
        if ($count > 0) {
            \Log::info('Batch update expired contracts completed', [
                'total_updated' => $count,
                'updated_at' => now()->format('Y-m-d H:i:s'),
            ]);
        }
        return $count;
    }

    /**
     * =============================================
     * NEW METHODS UNTUK EMPLOYEE WORK POLICY
     * =============================================
     */

    /**
     * Relasi one-to-one ke work policy
     */
    public function workPolicy()
    {
        return $this->hasOne(EmployeeWorkPolicy::class);
    }

    /**
     * Mendapatkan jam kerja karyawan untuk hari tertentu
     * 
     * @param int|null $dayOfWeek (0=Minggu, 1=Senin, ..., 6=Sabtu) - default hari ini
     * @return float|null
     */
    public function getWorkHoursForDay($dayOfWeek = null)
    {
        if (is_null($dayOfWeek)) {
            $dayOfWeek = now()->dayOfWeek;
        }
        
        $policy = $this->workPolicy;
        if (!$policy) {
            return null; // atau bisa return nilai default global
        }
        
        return $policy->getHoursForDay($dayOfWeek);
    }

    /**
     * Accessor untuk total jam kerja per minggu
     */
    public function getWeeklyWorkHoursAttribute()
    {
        return $this->workPolicy?->weekly_hours;
    }

    /**
     * Generate employee number
     */
    public static function generateEmployeeNo()
    {
        // Logika generate nomor otomatis, contoh: DCM-0001, DCM-0002, dst.
        $lastEmployee = self::withTrashed()->orderBy('id', 'desc')->first();
        if ($lastEmployee && preg_match('/DCM-(\d+)/', $lastEmployee->employee_no, $matches)) {
            $nextNumber = (int) $matches[1] + 1;
        } else {
            $nextNumber = 1;
        }
        return 'DCM-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }
}