<?php

namespace App\Models\Production;

use Illuminate\Database\Eloquent\Model;
use App\Models\Production\Project;
use App\Models\Logistic\Unit;
use App\Models\Admin\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class MaterialPlanning extends Model implements AuditableContract
{
    use \OwenIt\Auditing\Auditable;

    protected $guarded = [];

    protected $dates = ['created_at', 'updated_at', 'eta_date'];

    protected $auditInclude = ['project_id', 'order_type', 'material_name', 'qty_needed', 'unit_id', 'eta_date'];

    protected $auditTimestamps = true;

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    // Scope untuk filter berdasarkan department
    public function scopeByDepartment($query, $departmentId)
    {
        return $query->whereHas('project.departments', function ($q) use ($departmentId) {
            $q->where('departments.id', $departmentId);
        });
    }

    // Scope untuk filter berdasarkan order type
    public function scopeByOrderType($query, $orderType)
    {
        return $query->where('order_type', $orderType);
    }

    // Scope untuk filter berdasarkan tanggal
    public function scopeByDateRange($query, $from = null, $to = null)
    {
        if ($from) {
            $query->whereDate('created_at', '>=', $from);
        }

        if ($to) {
            $query->whereDate('created_at', '<=', $to);
        }

        return $query;
    }
}
