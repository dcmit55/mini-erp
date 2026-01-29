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

    protected $auditInclude = ['name', 'qty', 'project_status_id', 'start_date', 'deadline', 'finish_date', 'img', 'created_by', 'stage', 'submission_form'];

    protected $auditTimestamps = true;

    protected $fillable = ['name', 'qty', 'project_status_id', 'start_date', 'deadline', 'finish_date', 'img', 'created_by', 'lark_record_id', 'last_sync_at', 'stage', 'submission_form'];

    // BOOT METHOD
    protected static function boot()
    {
        parent::boot();

        // Saat project di-delete (soft delete), hapus juga related records
        static::deleting(function ($project) {
            // Delete material usages
            $project->materialUsages()->delete();

            // Delete project parts
            $project->parts()->delete();
        });

        // Saat project di-force delete (permanent), hapus juga related records
        static::forceDeleting(function ($project) {
            // Force delete material usages
            $project->materialUsages()->forceDelete();

            // Force delete project parts
            $project->parts()->forceDelete();
        });
    }

    public function materialUsages()
    {
        return $this->hasMany(MaterialUsage::class);
    }

    public function parts()
    {
        return $this->hasMany(ProjectPart::class);
    }

    /**
     * Relasi Utama: Many to Many
     */
    public function departments()
    {
        return $this->belongsToMany(Department::class, 'department_project')->withTimestamps();
    }

    /**
     * ALIAS untuk menghindari RelationNotFoundException
     * Karena TimingController memanggil 'department', kita arahkan ke fungsi departments()
     */
    public function department()
    {
        return $this->departments();
    }

    public function status()
    {
        return $this->belongsTo(ProjectStatus::class, 'project_status_id');
    }

    public function scopeNotArchived($query)
    {
        $archiveId = 1;
        return $query->where(function ($q) use ($archiveId) {
            $q->whereNull('project_status_id')->orWhere('project_status_id', '!=', $archiveId);
        });
    }
}