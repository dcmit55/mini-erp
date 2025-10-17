<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Timing;
use App\Models\EmployeeDocument;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Employee extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['employee_no', 'name', 'photo', 'position', 'department_id', 'email', 'phone', 'address', 'gender', 'ktp_id', 'place_of_birth', 'date_of_birth', 'rekening', 'hire_date', 'salary', 'saldo_cuti', 'status', 'notes'];
    protected $casts = [
        'hire_date' => 'date',
        'date_of_birth' => 'date',
        'salary' => 'decimal:2',
    ];

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
        });

        static::updating(function ($employee) {
            if ($employee->isDirty('employee_no') && !empty($employee->employee_no)) {
                // Pastikan format DCM- jika user update manual
                $employee->employee_no = self::formatEmployeeNo($employee->employee_no);
            }
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
        return $this->belongsTo(\App\Models\Department::class);
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
}
