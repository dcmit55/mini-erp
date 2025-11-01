<?php

namespace App\Models\Procurement;

use Illuminate\Database\Eloquent\Model;
use App\Models\Procurement\ShippingDetail;

class Shipping extends Model
{
    protected $fillable = ['international_waybill_no', 'freight_company', 'freight_price', 'eta_to_arrived', 'shipment_status', 'remarks'];

    public function details()
    {
        return $this->hasMany(ShippingDetail::class);
    }
}
