<?php

namespace App\Models\Hr;

use Illuminate\Database\Eloquent\Model;
use App\Models\Hr\Employee;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class LeaveRequest extends Model implements AuditableContract
{
    use \OwenIt\Auditing\Auditable;

    protected $fillable = ['employee_id', 'start_date', 'end_date', 'duration', 'type', 'reason', 'approval_1', 'approval_2'];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'duration' => 'decimal:2',
    ];

    protected $auditInclude = ['employee_id', 'start_date', 'end_date', 'duration', 'type', 'reason', 'approval_1', 'approval_2'];

    protected $auditTimestamps = true;

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Check if both approvals are approved
     */
    public function isBothApproved()
    {
        return $this->approval_1 === 'approved' && $this->approval_2 === 'approved';
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
     */
    public function shouldDeductLeaveBalance()
    {
        return $this->isBothApproved() && $this->isAnnualLeave();
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
        return ['ANNUAL', 'MATERNITY', 'WEDDING', 'SONWED', 'BIRTHCHILD', 'UNPAID', 'DEATH', 'DEATH_2', 'BAPTISM'];
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
