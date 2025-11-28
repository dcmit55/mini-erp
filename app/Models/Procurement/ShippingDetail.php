<?php

namespace App\Models\Procurement;

use Illuminate\Database\Eloquent\Model;
use App\Models\Procurement\PreShipping;
use App\Models\Procurement\Shipping;

class ShippingDetail extends Model
{
    protected $fillable = [
        'shipping_id',
        'pre_shipping_id',
        'shortage_item_id', // NEW FIELD
        'percentage',
        'int_cost',
        'extra_cost',
        'extra_cost_reason',
        'destination',
    ];

    // Cast fields as decimal
    protected $casts = [
        'int_cost' => 'decimal:2',
        'extra_cost' => 'decimal:2',
        'percentage' => 'decimal:2',
    ];

    public function preShipping()
    {
        return $this->belongsTo(PreShipping::class);
    }

    // Relasi ke Shipping
    public function shipping()
    {
        return $this->belongsTo(Shipping::class);
    }

    // Shortage relation
    public function shortageItem()
    {
        return $this->belongsTo(ShortageItem::class);
    }

    // Accessor untuk destination label
    public function getDestinationLabelAttribute()
    {
        return match ($this->destination) {
            'SG' => 'Singapore',
            'BT' => 'Batam',
            'CN' => 'China',
            'MY' => 'Malaysia',
            'Other' => 'Other Location',
            default => '-',
        };
    }

    // Accessor untuk destination badge color
    public function getDestinationBadgeColorAttribute()
    {
        return match ($this->destination) {
            'SG' => 'success',
            'BT' => 'info',
            'CN' => 'danger',
            'MY' => 'warning',
            'Other' => 'secondary',
            default => 'secondary',
        };
    }

    // Get final international cost (base + extra)
    public function getFinalIntCostAttribute()
    {
        return ($this->int_cost ?? 0) + ($this->extra_cost ?? 0);
    }

    // Check if has extra cost
    public function hasExtraCost()
    {
        return $this->extra_cost > 0;
    }

    // Get extra cost percentage of total
    public function getExtraCostPercentageAttribute()
    {
        if ($this->final_int_cost <= 0) {
            return 0;
        }
        return ($this->extra_cost / $this->final_int_cost) * 100;
    }

    // Helper method untuk get source data (PR from PreShipping OR ShortageItem)
    public function getSourcePurchaseRequest()
    {
        if ($this->pre_shipping_id && $this->preShipping) {
            return $this->preShipping->purchaseRequest;
        }

        if ($this->shortage_item_id && $this->shortageItem) {
            return $this->shortageItem->purchaseRequest;
        }

        return null;
    }

    // Check if this is shortage resend
    public function isShortageResend()
    {
        return $this->shortage_item_id !== null;
    }
}
