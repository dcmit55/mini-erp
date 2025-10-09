<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class LeaveRequest extends Model
{
    protected $fillable = ['employee_id', 'start_date', 'end_date', 'duration', 'type', 'reason', 'approval_1', 'approval_2'];
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
    
    public static function getTypeEnumOptions()
    {
        $type = \DB::select("SHOW COLUMNS FROM leave_requests WHERE Field = 'type'")[0]->Type;
        if (preg_match("/^enum\((.*)\)$/", $type, $matches)) {
            $enum = [];
            foreach (explode(',', $matches[1]) as $value) {
                $v = trim($value, "'");
                $enum[] = $v;
            }
            return $enum;
        }
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
}
