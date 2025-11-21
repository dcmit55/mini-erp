<?php

namespace App\Models\Procurement;

use Illuminate\Database\Eloquent\Model;
use App\Models\Procurement\PreShipping;
use App\Models\Procurement\Shipping;

class ShippingDetail extends Model
{
    protected $fillable = ['shipping_id', 'pre_shipping_id', 'percentage', 'int_cost', 'destination'];

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

    public function preShipping()
    {
        return $this->belongsTo(PreShipping::class);
    }

    // relasi ke Shipping
    public function shipping()
    {
        return $this->belongsTo(Shipping::class);
    }
}
