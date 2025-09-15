<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GoodsReceiveDetail extends Model
{
    protected $fillable = ['goods_receive_id', 'shipping_detail_id', 'purchase_type', 'project_name', 'material_name', 'supplier_name', 'unit_price', 'domestic_waybill_no', 'purchased_qty', 'received_qty'];

    public function goodsReceive()
    {
        return $this->belongsTo(GoodsReceive::class);
    }

    public function shippingDetail()
    {
        return $this->belongsTo(ShippingDetail::class);
    }
}
