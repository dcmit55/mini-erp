<?php

namespace App\Models\Procurement;

use Illuminate\Database\Eloquent\Model;
use App\Models\Procurement\ShippingDetail;
use App\Models\Procurement\GoodsReceive;
use OwenIt\Auditing\Contracts\Auditable;

class Shipping extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;

    protected $fillable = ['international_waybill_no', 'freight_company', 'freight_method', 'freight_price', 'eta_to_arrived', 'shipment_status', 'remarks'];

    protected $auditInclude = ['international_waybill_no', 'freight_company', 'freight_method', 'freight_price', 'eta_to_arrived', 'shipment_status'];

    protected $auditTimestamps = true;

    public function details()
    {
        return $this->hasMany(ShippingDetail::class);
    }

    public function goodsReceive()
    {
        return $this->hasOne(GoodsReceive::class);
    }

    // Helper method untuk check if Air Freight
    public function isAirFreight()
    {
        return $this->freight_method === 'Air Freight';
    }

    // Get total cost including extra costs
    public function getTotalCostAttribute()
    {
        return $this->freight_price + $this->details->sum('extra_cost');
    }

    // Get freight method badge color
    public function getFreightMethodBadgeAttribute()
    {
        return match ($this->freight_method) {
            'Air Freight' => 'primary',
            'Sea Freight' => 'info',
            default => 'secondary',
        };
    }

    // Get freight method icon
    public function getFreightMethodIconAttribute()
    {
        return match ($this->freight_method) {
            'Air Freight' => 'airplane',
            'Sea Freight' => 'ship',
            default => 'truck',
        };
    }
}
