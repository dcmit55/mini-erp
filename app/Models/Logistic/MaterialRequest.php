<?php

namespace App\Models\Logistic;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;
use App\Models\Logistic\Inventory;
use App\Models\Logistic\GoodsOut;
use App\Models\Production\Project;
use App\Models\Admin\User;
use App\Models\InternalProject;

class MaterialRequest extends Model implements Auditable
{
    use HasFactory, \OwenIt\Auditing\Auditable, SoftDeletes;

    const PROJECT_TYPE_CLIENT   = 'client';
    const PROJECT_TYPE_INTERNAL = 'internal';

    protected $fillable = [
        'inventory_id',
        'project_type',
        'project_id',
        'internal_project_id',
        'job_order_id',
        'qty',
        'processed_qty',
        'requested_by',
        'status',
        'remark',
        'approved_at',
    ];

    protected $casts = [
        'qty'          => 'decimal:2',
        'processed_qty' => 'decimal:2',
        'approved_at'  => 'datetime',
        'created_at'   => 'datetime',
        'updated_at'   => 'datetime',
        'deleted_at'   => 'datetime',
    ];

    protected $auditInclude = [
        'inventory_id',
        'project_type',
        'project_id',
        'internal_project_id',
        'job_order_id',
        'qty',
        'processed_qty',
        'status',
        'remark',
        'requested_by',
        'approved_at',
    ];

    protected $auditTimestamps = true;

    // ===================== RELASI =====================
    public function inventory()
    {
        return $this->belongsTo(Inventory::class);
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function internalProject()
    {
        return $this->belongsTo(InternalProject::class, 'internal_project_id');
    }

    public function jobOrder()
    {
        return $this->belongsTo(\App\Models\Production\JobOrder::class, 'job_order_id', 'id');
    }

    public function goodsOuts()
    {
        return $this->hasMany(GoodsOut::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'requested_by', 'username');
    }

    // ===================== ACCESSORS =====================
    public function getRemainingQtyAttribute()
    {
        return round($this->qty - $this->processed_qty, 2);
    }

    public function getDepartmentNameAttribute()
    {
        return $this->user && $this->user->department ? $this->user->department->name : null;
    }

    /**
     * Nama proyek:
     * - Client : nama project dari tabel projects
     * - Internal : hanya nama project (kolom `project`) dari tabel internal_projects
     */
    public function getProjectNameAttribute()
    {
        if ($this->project_type === self::PROJECT_TYPE_CLIENT && $this->project) {
            return $this->project->name;
        }

        if ($this->project_type === self::PROJECT_TYPE_INTERNAL && $this->internalProject) {
            return $this->internalProject->project; // hanya nama project, tanpa job
        }

        return '(No Project)';
    }

    public function getStatusBadgeClass()
    {
        return match ($this->status) {
            'pending'   => 'text-bg-warning',
            'approved'  => 'text-bg-primary',
            'delivered' => 'text-bg-success',
            'canceled'  => 'text-bg-danger',
            default     => '',
        };
    }

    // ===================== SCOPES =====================
    public function scopeClientProjects($query)
    {
        return $query->where('project_type', self::PROJECT_TYPE_CLIENT);
    }

    public function scopeInternalProjects($query)
    {
        return $query->where('project_type', self::PROJECT_TYPE_INTERNAL);
    }
}