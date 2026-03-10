<?php

namespace App\Models\Lark;

use App\Models\Admin\User;
use App\Models\Finance\Currency;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Lark Staging Inventory
 *
 * Tabel staging untuk data purchase dari Lark sebelum masuk ke tabel inventories.
 * Admin dapat mereview, memfilter, dan menyetujui/menolak data sebelum dipindah ke inventory.
 *
 * Flow:
 * Lark API → lark_staging_inventories (review) → inventories (approved)
 */
class LarkStagingInventory extends Model
{
    protected $table = 'lark_staging_inventories';

    protected $fillable = ['lark_record_id', 'name', 'project_lark', 'quantity', 'unit', 'price', 'currency_id', 'supplier_lark', 'img', 'destination', 'status', 'dept_imported', 'source_record_ids', 'source_record_count', 'review_status', 'review_note', 'reviewed_by', 'reviewed_at', 'last_sync_at'];

    protected $casts = [
        'quantity' => 'decimal:2',
        'price' => 'decimal:2',
        'source_record_count' => 'integer',
        'reviewed_at' => 'datetime',
        'last_sync_at' => 'datetime',
    ];

    /**
     * Relation to Currency
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }

    /**
     * Relation to reviewer User
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Scope for pending review items
     */
    public function scopePending($query)
    {
        return $query->where('review_status', 'pending');
    }

    /**
     * Scope for approved items
     */
    public function scopeApproved($query)
    {
        return $query->where('review_status', 'approved');
    }

    /**
     * Scope for rejected items
     */
    public function scopeRejected($query)
    {
        return $query->where('review_status', 'rejected');
    }

    /**
     * Get review status badge HTML
     */
    public function getReviewStatusBadgeAttribute(): string
    {
        $colors = [
            'pending' => 'warning',
            'approved' => 'success',
            'rejected' => 'danger',
        ];
        $color = $colors[$this->review_status] ?? 'secondary';
        $label = ucfirst($this->review_status);
        return "<span class='badge bg-{$color}'>{$label}</span>";
    }

    /**
     * Get formatted currency display
     */
    public function getCurrencyNameAttribute(): string
    {
        return $this->currency?->code ?? 'RMB';
    }

    /**
     * Get source record IDs as array
     */
    public function getSourceRecordIdsArrayAttribute(): array
    {
        if (empty($this->source_record_ids)) {
            return [];
        }
        return explode(',', $this->source_record_ids);
    }
}
