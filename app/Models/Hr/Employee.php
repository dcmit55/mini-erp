<?php

namespace App\Models\Hr;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Production\Timing;
use App\Models\Hr\EmployeeDocument;
use App\Models\Hr\Skillset;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class Employee extends Model implements AuditableContract
{
    use HasFactory, SoftDeletes, \OwenIt\Auditing\Auditable;

    protected $fillable = ['employee_no', 'name', 'employment_type', 'photo', 'position', 'department_id', 'email', 'phone', 'address', 'gender', 'ktp_id', 'place_of_birth', 'date_of_birth', 'rekening', 'hire_date', 'contract_end_date', 'salary', 'saldo_cuti', 'status', 'notes'];

    protected $casts = [
        'hire_date' => 'date',
        'contract_end_date' => 'date',
        'date_of_birth' => 'date',
        'salary' => 'decimal:2',
        'saldo_cuti' => 'decimal:2',
    ];

    protected $auditInclude = ['employee_no', 'name', 'employment_type', 'position', 'department_id', 'email', 'phone', 'address', 'gender', 'ktp_id', 'place_of_birth', 'date_of_birth', 'rekening', 'hire_date', 'contract_end_date', 'salary', 'saldo_cuti', 'status'];

    protected $auditTimestamps = true;

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($employee) {
            if (empty($employee->employee_no)) {
                $employee->employee_no = self::generateEmployeeNo();
            } else {
                // Pastikan format DCM- jika user input manual
                $employee->employee_no = self::formatEmployeeNo($employee->employee_no);
            }

            // Auto-set status to inactive if contract expired
            $employee->checkAndUpdateContractStatus();
        });

        static::updating(function ($employee) {
            if ($employee->isDirty('employee_no') && !empty($employee->employee_no)) {
                // Pastikan format DCM- jika user update manual
                $employee->employee_no = self::formatEmployeeNo($employee->employee_no);
            }

            // Auto-set status to inactive if contract expired
            $employee->checkAndUpdateContractStatus();
        });
    }

    public static function formatEmployeeNo($input)
    {
        // Remove semua non-numeric characters dan DCM-
        $number = preg_replace('/[^0-9]/', '', $input);

        // Jika kosong, generate otomatis
        if (empty($number)) {
            return self::generateEmployeeNo();
        }

        // Format dengan DCM- prefix dan padding
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

    // Method untuk mendapatkan nomor saja (tanpa DCM-)
    public function getEmployeeNumberOnlyAttribute()
    {
        return str_replace('DCM-', '', $this->employee_no);
    }

    // accessor untuk age
    public function getAgeAttribute()
    {
        return $this->date_of_birth ? $this->date_of_birth->age : null;
    }

    // Accessor untuk formatted KTP ID
    public function getFormattedKtpIdAttribute()
    {
        if (!$this->ktp_id) {
            return '-';
        }

        // Format: XXXX-XXXX-XXXX-XXXX (16 digits)
        $clean = preg_replace('/[^0-9]/', '', $this->ktp_id);

        if (strlen($clean) === 16) {
            return substr($clean, 0, 4) . '-' . substr($clean, 4, 4) . '-' . substr($clean, 8, 4) . '-' . substr($clean, 12, 4);
        }

        return $this->ktp_id;
    }

    // Accessor untuk gender label
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

    public function documents()
    {
        return $this->hasMany(EmployeeDocument::class);
    }

    // Accessor untuk format salary
    public function getFormattedSalaryAttribute()
    {
        return $this->salary ? 'Rp ' . number_format($this->salary, 0, ',', '.') : '-';
    }

    // Accessor untuk status badge
    public function getStatusBadgeAttribute()
    {
        $colors = [
            'active' => 'success',
            'inactive' => 'warning',
            'terminated' => 'danger',
        ];

        return [
            'color' => $colors[$this->status] ?? 'secondary',
            'text' => ucfirst($this->status),
        ];
    }

    // Accessor untuk photo URL
    public function getPhotoUrlAttribute()
    {
        if ($this->photo && Storage::disk('public')->exists($this->photo)) {
            return Storage::url($this->photo);
        }
        return asset('images/default-avatar.png'); // Default avatar
    }

    // Accessor untuk formatted rekening (FIXED)
    public function getFormattedRekeningAttribute()
    {
        if (!$this->rekening) {
            return '-';
        }

        // Clean the input first (remove any existing formatting)
        $clean = preg_replace('/[^0-9]/', '', $this->rekening);

        if (empty($clean)) {
            return '-';
        }

        // Format: XXXX-XXXX-XXXX-XXXX
        // Use regex to add dashes every 4 digits
        $formatted = preg_replace('/(\d{4})(?=\d)/', '$1-', $clean);

        return $formatted;
    }

    // Tambahkan accessor untuk employment type badge
    public function getEmploymentTypeBadgeAttribute()
    {
        $colors = [
            'PKWT' => 'primary',
            'PKWTT' => 'success',
            'Daily Worker' => 'warning',
            'Probation' => 'info',
        ];

        return [
            'color' => $colors[$this->employment_type] ?? 'secondary',
            'text' => $this->employment_type,
        ];
    }

    // Static method untuk employment type options
    public static function getEmploymentTypeOptions()
    {
        return [
            'PKWT' => 'PKWT (Fixed-term Contract)',
            'PKWTT' => 'PKWTT (Permanent)',
            'Daily Worker' => 'Daily Worker',
            'Probation' => 'Probation',
        ];
    }

    // Accessor untuk contract status
    public function getContractStatusAttribute()
    {
        if (!$this->contract_end_date) {
            return null;
        }

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

    // Accessor untuk formatted contract duration
    public function getContractDurationAttribute()
    {
        if (!$this->hire_date || !$this->contract_end_date) {
            return null;
        }

        $diffInMonths = $this->hire_date->diffInMonths($this->contract_end_date);
        $diffInDays = $this->hire_date->diffInDays($this->contract_end_date);

        return [
            'months' => $diffInMonths,
            'days' => $diffInDays,
            'formatted' => "{$diffInMonths} months ({$diffInDays} days)",
        ];
    }

    // Relationship to skillsets
    public function skillsets()
    {
        return $this->belongsToMany(Skillset::class, 'employee_skillset')->withPivot('proficiency_level', 'acquired_date', 'last_used_date', 'notes')->withTimestamps();
    }

    // Accessor untuk formatted skillsets dengan proficiency
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

    // Helper untuk proficiency badge
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

    // Check if employee has specific skill
    public function hasSkill($skillName)
    {
        return $this->skillsets()->where('name', $skillName)->exists();
    }

    // Get employee's skill proficiency
    public function getSkillProficiency($skillName)
    {
        $skill = $this->skillsets()->where('name', $skillName)->first();
        return $skill ? $skill->pivot->proficiency_level : null;
    }

    /**
     * Check and auto-update employee status based on contract date
     *
     * @param bool $autoSave Auto save to database (for retrieved event)
     * @return bool True if status was changed
     */
    public function checkAndUpdateContractStatus($autoSave = false)
    {
        if (!$this->contract_end_date) {
            return false;
        }

        $today = \Carbon\Carbon::today();
        $contractValid = $this->contract_end_date->gte($today);

        // CASE 1: Active employee with EXPIRED contract → Auto INACTIVE
        if ($this->status === 'active' && !$contractValid) {
            $oldStatus = $this->status;
            $this->status = 'inactive';

            $expiredDate = $this->contract_end_date->format('Y-m-d');
            $updateNote = "[Auto-updated] Status changed to 'inactive' - Contract expired on {$expiredDate}";

            if (!str_contains($this->notes ?? '', $updateNote)) {
                $this->notes = trim(($this->notes ?? '') . "\n" . $updateNote);
            }

            \Log::info('Employee contract expired - Auto-updated to inactive', [
                'employee_id' => $this->id ?? 'new',
                'employee_no' => $this->employee_no,
                'name' => $this->name,
                'contract_end_date' => $expiredDate,
                'old_status' => $oldStatus,
                'new_status' => 'inactive',
            ]);

            return true;
        }

        // CASE 2: Inactive employee with VALID contract (extended) → Auto ACTIVE
        // Only if inactive was caused by auto-update (not manual termination)
        if ($this->status === 'inactive' && $contractValid) {
            // Check if inactive was caused by auto-system (not manual)
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

    /**
     * Static method to batch check and update all expired contracts
     * Can be called manually or via tinker
     *
     * @return int Number of employees updated
     */
    public static function updateExpiredContracts()
    {
        $today = \Carbon\Carbon::today();

        $expiredEmployees = self::where('status', 'active')->whereNotNull('contract_end_date')->where('contract_end_date', '<', $today)->get();

        $count = 0;
        foreach ($expiredEmployees as $employee) {
            $employee->checkAndUpdateContractStatus();

            // Save the changes
            if ($employee->isDirty('status')) {
                $employee->saveQuietly(); // Use saveQuietly to avoid triggering boot events again
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
}
