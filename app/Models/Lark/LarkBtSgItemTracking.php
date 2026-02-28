<?php

namespace App\Models\Lark;

use App\Models\Production\Project;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Lark BT-SG Item Tracking Staging Table
 *
 * Stores raw item tracking data from Lark (Batam to Singapore direction)
 * This is a staging table, not final ERP data
 */
class LarkBtSgItemTracking extends Model
{
    protected $table = 'lark_bt_sg_item_trackings';

    protected $fillable = ['lark_record_id', 'item_name', 'status', 'qty', 'sgd_cost', 'project_lark', 'project_id', 'courier_id', 'last_sync_at'];

    protected $casts = [
        'qty' => 'integer',
        'sgd_cost' => 'decimal:2',
        'last_sync_at' => 'datetime',
    ];

    /**
     * Relation to Project (ERP table)
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    /**
     * Relation to Courier (Lark staging table)
     */
    public function courier(): BelongsTo
    {
        return $this->belongsTo(LarkBtSgCourierId::class, 'courier_id');
    }

    /**
     * Get project name from project_lark or ERP relation
     */
    public function getProjectNameAttribute(): ?string
    {
        // Return project_lark directly (already stored as string)
        return $this->project_lark ?: $this->project?->name ?? null;
    }

    /**
     * Get courier name from relation
     */
    public function getCourierNameAttribute(): ?string
    {
        return $this->courier?->name;
    }
}
