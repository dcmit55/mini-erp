<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PreShipping extends Model
{
    protected $fillable = ['purchase_request_id', 'domestic_waybill_no', 'same_supplier_selection', 'percentage_if_same_supplier', 'domestic_cost'];

    public function purchaseRequest()
    {
        return $this->belongsTo(PurchaseRequest::class);
    }

    public function shippingDetail()
    {
        return $this->hasOne(ShippingDetail::class);
    }
}
