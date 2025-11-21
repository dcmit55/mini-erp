<?php

namespace App\Models\Procurement;

use Illuminate\Database\Eloquent\Model;
use App\Models\Procurement\ShippingDetail;
use OwenIt\Auditing\Contracts\Auditable;

class Shipping extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;

    protected $fillable = ['international_waybill_no', 'freight_company', 'freight_price', 'eta_to_arrived', 'shipment_status', 'remarks'];

    protected $auditInclude = ['international_waybill_no', 'freight_company', 'freight_price', 'eta_to_arrived', 'shipment_status'];

    protected $auditTimestamps = true;

    public function details()
    {
        return $this->hasMany(ShippingDetail::class);
    }
}
