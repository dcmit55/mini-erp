<?php

namespace App\Models\Qc;

use App\Models\Production\JobOrder;
use App\Models\Production\Project;
use App\Models\Admin\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class QcProject extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'qc_projects';

    protected $fillable = [
        'uid', 'job_order_id', 'project_id',
        'job_number', 'project_name',
        'mascot_type', 'created_by',
        'inspection_date', 'deadline', 'total_unit',
        'status', 'cover_gradient', 'cover_image_path',
        'packing_verified', 'final_decision',
        'custom_parts', 'packing_config', 'stage_progress',
    ];

    protected $casts = [
        'inspection_date'  => 'date',
        'deadline'         => 'date',
        'packing_verified' => 'boolean',
        'final_decision'   => 'array',
        'custom_parts'     => 'array',
        'packing_config'   => 'array',
        'stage_progress'   => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (empty($model->uid)) {
                $model->uid = (string) Str::uuid();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uid';
    }

    // ── Relationships ──────────────────────────────────────────────────

    public function jobOrder()
    {
        return $this->belongsTo(JobOrder::class, 'job_order_id');
    }

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function checklistItems()
    {
        return $this->hasMany(QcChecklistItem::class, 'qc_project_id');
    }

    public function rejectLogs()
    {
        return $this->hasMany(QcRejectLog::class, 'qc_project_id');
    }

    public function packingItems()
    {
        return $this->hasMany(QcPackingItem::class, 'qc_project_id')->orderBy('sort_order');
    }

    public function dailyProgress()
    {
        return $this->hasMany(QcDailyProgress::class, 'qc_project_id')->orderBy('date');
    }

    // ── Computed ───────────────────────────────────────────────────────

    public function getProgressAttribute(): int
    {
        $total = 36; // 35 checklist items + 1 packing verify
        $done  = $this->checklistItems()->whereNotNull('status')->count();
        if ($this->packing_verified) {
            $done++;
        }

        return $total > 0 ? (int) round($done / $total * 100) : 0;
    }
}
