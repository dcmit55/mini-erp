<?php

namespace App\Models\Production;

use Illuminate\Database\Eloquent\Model;
use App\Models\Hr\Employee;
use App\Models\Admin\User;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class JobOrderTimingPlan extends Model implements AuditableContract
{
    use \OwenIt\Auditing\Auditable;

    protected $table = 'job_order_timing_plans';

    protected $fillable = ['job_order_id', 'planning_date', 'employee_id', 'task', 'parts', 'stage', 'session_type', 'created_by'];

    /**
     * Attributes to include in audit log (excludes timestamps to reduce noise).
     */
    protected $auditInclude = [
        'job_order_id',
        'planning_date',
        'employee_id',
        'task',
        'parts',
        'stage',
        'session_type',
        'created_by',
    ];

    public function jobOrder()
    {
        return $this->belongsTo(JobOrder::class, 'job_order_id', 'id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
