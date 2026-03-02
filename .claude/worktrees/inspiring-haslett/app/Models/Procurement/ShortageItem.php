<?php
// filepath: app/Models/Procurement/ShortageItem.php

namespace App\Models\Procurement;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use OwenIt\Auditing\Contracts\Auditable;

class ShortageItem extends Model implements Auditable
{
    use HasFactory, \OwenIt\Auditing\Auditable;

    protected $fillable = ['goods_receive_detail_id', 'purchase_request_id', 'material_name', 'purchased_qty', 'received_qty', 'shortage_qty', 'status', 'resend_count', 'notes', 'old_domestic_wbl'];

    protected $casts = [
        'purchased_qty' => 'decimal:2',
        'received_qty' => 'decimal:2',
        'shortage_qty' => 'decimal:2',
        'resend_count' => 'integer',
    ];

    protected $auditInclude = ['status', 'shortage_qty', 'resend_count', 'notes'];

    protected $auditTimestamps = true;

    // ===== RELATIONSHIPS =====

    public function goodsReceiveDetail()
    {
        return $this->belongsTo(GoodsReceiveDetail::class);
    }

    public function purchaseRequest()
    {
        return $this->belongsTo(PurchaseRequest::class);
    }

    // ===== STATUS BADGE HELPERS =====

    public function getStatusBadgeAttribute()
    {
        return match ($this->status) {
            'pending' => 'warning',
            'reshipped' => 'info',
            'partially_reshipped' => 'primary',
            'fully_reshipped' => 'success',
            'canceled' => 'danger',
            default => 'secondary',
        };
    }

    public function getStatusLabelAttribute()
    {
        return match ($this->status) {
            'pending' => 'Pending Resend',
            'reshipped' => 'Reshipped',
            'partially_reshipped' => 'Partially Reshipped',
            'fully_reshipped' => 'Fully Reshipped',
            'canceled' => 'Canceled',
            default => 'Unknown',
        };
    }

    /**
     * Check if shortage is resolvable (not canceled, not fully shipped)
     */
    public function isResolvable()
    {
        return in_array($this->status, ['pending', 'partially_reshipped']);
    }

    /**
     * Mark as reshipped and increment counter
     */
    public function markAsReshipped()
    {
        $this->update([
            'status' => 'reshipped',
            'resend_count' => $this->resend_count + 1,
        ]);
    }

    /**
     * Update shortage after receiving new shipment
     */
    public function updateAfterReceive($newReceivedQty)
    {
        $remainingShortage = $this->shortage_qty - $newReceivedQty;

        if ($remainingShortage <= 0) {
            // Fully resolved
            $this->update([
                'status' => 'fully_reshipped',
                'shortage_qty' => 0,
                'received_qty' => $this->received_qty + $newReceivedQty,
            ]);
        } else {
            // Partially resolved
            $this->update([
                'status' => 'partially_reshipped',
                'shortage_qty' => $remainingShortage,
                'received_qty' => $this->received_qty + $newReceivedQty,
            ]);
        }
    }

    /**
     * Cancel shortage item
     */
    public function cancel($reason = null)
    {
        $this->update([
            'status' => 'canceled',
            'notes' => $reason ? "Canceled: {$reason}" : 'Canceled by user',
        ]);
    }

    // ===== SCOPES =====

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopePartiallyReshipped($query)
    {
        return $query->where('status', 'partially_reshipped');
    }

    public function scopeResolvable($query)
    {
        return $query->whereIn('status', ['pending', 'partially_reshipped']);
    }

    public function scopeRecent($query)
    {
        return $query->orderBy('created_at', 'desc');
    }
}
