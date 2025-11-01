<?php

namespace App\Models\Production;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Logistic\MaterialUsage;
use App\Models\Production\ProjectPart;
use App\Models\Admin\Department;
use App\Models\Production\ProjectStatus;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

class Project extends Model implements Auditable
{
    use HasFactory, SoftDeletes, \OwenIt\Auditing\Auditable;

    protected $auditInclude = ['name', 'qty', 'department_id', 'project_status_id', 'start_date', 'deadline', 'finish_date', 'img', 'created_by'];

    protected $auditTimestamps = true;

    protected $fillable = ['name', 'qty', 'department_id', 'project_status_id', 'start_date', 'deadline', 'finish_date', 'img', 'created_by'];

    public function materialUsages()
    {
        return $this->hasMany(MaterialUsage::class);
    }

    public function parts()
    {
        return $this->hasMany(ProjectPart::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function status()
    {
        return $this->belongsTo(ProjectStatus::class, 'project_status_id');
    }

    public function scopeNotArchived($query)
    {
        $archiveId = 1; // Ganti sesuai id status "archive" di tabel project_statuses
        return $query->where(function ($q) use ($archiveId) {
            $q->whereNull('project_status_id')->orWhere('project_status_id', '!=', $archiveId);
        });
    }
}
