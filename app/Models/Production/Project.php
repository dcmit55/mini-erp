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

    protected $auditInclude = ['name', 'type_dept', 'department_id', 'sales', 'qty', 'project_status_id', 'project_status', 'start_date', 'deadline', 'finish_date', 'img', 'created_by', 'stage', 'submission_form', 'lark_record_id', 'last_sync_at'];

    protected $auditTimestamps = true;

    protected $fillable = ['name', 'type_dept', 'department_id', 'sales', 'qty', 'project_status_id', 'project_status', 'start_date', 'deadline', 'finish_date', 'img', 'created_by', 'lark_record_id', 'last_sync_at', 'stage', 'submission_form'];

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

    // Primary department relation (menggunakan department_id)
    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    // Multiple departments (many-to-many via pivot table)
    public function departments()
    {
        return $this->belongsToMany(Department::class, 'department_project')->withTimestamps();
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

    // ========================================
    // DATA GOVERNANCE: Lark Integration
    // ========================================

    /**
     * Scope untuk project yang di-sync dari Lark (VALID)
     * Ini adalah SATU-SATUNYA sumber data yang diakui sistem
     */
    public function scopeFromLark($query)
    {
        return $query->where('created_by', 'Sync from Lark');
    }

    /**
     * Scope untuk project legacy (TIDAK VALID untuk proses bisnis)
     * Hanya untuk historical reporting
     */
    public function scopeLegacy($query)
    {
        return $query->where('created_by', '!=', 'Sync from Lark')->orWhereNull('created_by');
    }

    /**
     * Check apakah project ini dari Lark (VALID)
     *
     * @return bool
     */
    public function isFromLark(): bool
    {
        return $this->created_by === 'Sync from Lark';
    }

    /**
     * Check apakah project ini legacy (TIDAK VALID)
     *
     * @return bool
     */
    public function isLegacy(): bool
    {
        return !$this->isFromLark();
    }

    /**
     * Check apakah project ini BOLEH digunakan dalam proses bisnis
     * Alias untuk isFromLark() - untuk readability
     *
     * @return bool
     */
    public function canBeUsed(): bool
    {
        return $this->isFromLark();
    }
}
