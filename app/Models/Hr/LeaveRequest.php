<?php

namespace App\Models\Hr;

use Illuminate\Database\Eloquent\Model;
use App\Models\Hr\Employee;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class LeaveRequest extends Model implements AuditableContract
{
    use \OwenIt\Auditing\Auditable;

    protected $fillable = ['employee_id', 'start_date', 'end_date', 'duration', 'type', 'reason', 'mc_document', 'doctor_letter', 'approval_dept', 'approval_1', 'approval_2'];

    // Role → Department(s) mapping untuk Level 1 (dept approvals)
    // Value bisa string (single) atau array (multiple departments)
    public const DEPT_ROLE_MAP = [
        'admin_mascot'      => ['DCM Mascot'],
        'admin_logistic'    => ['Logistic'],
        'admin_costume'     => ['DCM Costume', 'DCM Plush'],
        'admin_animatronic' => ['DCM Animatronics'],
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'duration' => 'decimal:2',
    ];

    // Exclude file fields (base64) dari audit log — data terlalu besar untuk kolom audits
    protected $auditExclude = ['mc_document', 'doctor_letter'];

    protected $auditTimestamps = true;

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Leave types that skip dept (Level 1) approval
     */
    public const SKIP_DEPT_APPROVAL_TYPES = ['SICK', 'MENSTRUATION'];

    /**
     * Flattened list of all departments that require dept-level approval.
     * Departments NOT in this list skip dept approval automatically.
     */
    public static function getDeptApprovalDepartments(): array
    {
        $depts = [];
        foreach (self::DEPT_ROLE_MAP as $deptsList) {
            foreach ((array) $deptsList as $dept) {
                $depts[] = $dept;
            }
        }
        return $depts;
    }

    /**
     * Check if this leave type skips dept approval
     */
    public function skipsDeptApproval(): bool
    {
        return in_array(strtoupper($this->type), self::SKIP_DEPT_APPROVAL_TYPES);
    }

    /**
     * Check if all required levels are approved
     * SICK/MENSTRUATION only need Level 2 + 3 (dept is auto-approved)
     */
    public function isFullyApproved(): bool
    {
        return $this->approval_dept === 'approved'
            && $this->approval_1 === 'approved'
            && $this->approval_2 === 'approved';
    }

    /** @deprecated use isFullyApproved() */
    public function isBothApproved(): bool
    {
        return $this->isFullyApproved();
    }

    /**
     * Check if this is Annual Leave type (FIXED for ENUM)
     */
    public function isAnnualLeave()
    {
        // ENUM value di database adalah 'ANNUAL' (uppercase)
        return $this->type === 'ANNUAL';
    }

    /**
     * Check if leave balance should be deducted
     * Only ANNUAL leave deducts saldo_cuti
     */
    public function shouldDeductLeaveBalance(): bool
    {
        $nonDeductingTypes = ['MENSTRUATION', 'SICK', 'HAJJ', 'PATERNITY'];
        if (in_array(strtoupper($this->type), $nonDeductingTypes)) {
            return false;
        }
        return $this->isFullyApproved() && $this->isAnnualLeave();
    }

    /**
     * Get Bootstrap badge color class for a given leave type
     */
    public static function getTypeBadgeClass(string $type): string
    {
        $map = [
            'ANNUAL'       => 'primary',
            'SICK'         => 'danger',
            'MENSTRUATION' => 'danger',
            'MATERNITY'    => 'info',
            'PATERNITY'    => 'info',
            'WEDDING'      => 'warning',
            'SONWED'       => 'warning',
            'BIRTHCHILD'   => 'warning',
            'DEATH'        => 'secondary',
            'DEATH_2'      => 'secondary',
            'BAPTISM'      => 'success',
            'HAJJ'         => 'success',
            'UNPAID'       => 'dark',
        ];
        return $map[strtoupper($type)] ?? 'secondary';
    }

    /**
     * Get ENUM values dari database
     */
    public static function getTypeEnumOptions()
    {
        try {
            $type = \DB::select("SHOW COLUMNS FROM leave_requests WHERE Field = 'type'")[0]->Type;

            if (preg_match("/^enum\((.*)\)$/", $type, $matches)) {
                $enum = [];
                foreach (explode(',', $matches[1]) as $value) {
                    $v = trim($value, "'");
                    $enum[] = $v;
                }
                return $enum;
            }
        } catch (\Exception $e) {
            \Log::error('Failed to get ENUM values: ' . $e->getMessage());
        }

        // Fallback jika gagal query
        return ['ANNUAL', 'MATERNITY', 'WEDDING', 'SONWED', 'BIRTHCHILD', 'UNPAID', 'DEATH', 'DEATH_2', 'BAPTISM', 'SICK', 'MENSTRUATION', 'HAJJ', 'PATERNITY'];
    }

    /**
     * Get human-readable labels
     */
    public static function getTypeLabels()
    {
        return [
            'ANNUAL' => 'Annual Leave',
            'MATERNITY' => 'Maternity (3 months)',
            'WEDDING' => 'Emp.Self Wedding (3 days)',
            'SONWED' => 'Son/Daughter Wedding (2 days)',
            'BIRTHCHILD' => 'Birth child/Misscarriage (2 days)',
            'UNPAID' => 'Unpaid Leave',
            'DEATH' => 'Death of family member living in the same house (1 day)',
            'DEATH_2' => 'Death of spouse/child or child in law/parent in law (2 days)',
            'BAPTISM' => 'Child Circumcision/Baptism (2 days)',
            'SICK' => 'Sick Leave',
            'MENSTRUATION' => 'Menstruation Leave',
            'HAJJ' => 'Hajj / Umrah Leave',
            'PATERNITY' => 'Paternity Leave (2 days)',
        ];
    }

    /**
     * Accessor untuk label type
     */
    public function getTypeLabelAttribute()
    {
        $labels = self::getTypeLabels();
        return $labels[$this->type] ?? $this->type;
    }
}
