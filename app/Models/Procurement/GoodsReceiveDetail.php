<?php

namespace App\Models\Procurement;

use Illuminate\Database\Eloquent\Model;
use App\Models\Procurement\ShippingDetail;

class GoodsReceiveDetail extends Model
{
    protected $fillable = ['goods_receive_id', 'shipping_detail_id', 'purchase_type', 'project_name', 'material_name', 'supplier_name', 'unit_price', 'domestic_waybill_no', 'purchased_qty', 'received_qty', 'destination', 'extra_cost', 'extra_cost_reason'];

    // Cast fields as decimal
    protected $casts = [
        'unit_price' => 'decimal:2',
        'purchased_qty' => 'decimal:2',
        'received_qty' => 'decimal:2',
        'extra_cost' => 'decimal:2',
    ];

    public function goodsReceive()
    {
        return $this->belongsTo(GoodsReceive::class);
    }

    public function shippingDetail()
    {
        return $this->belongsTo(ShippingDetail::class);
    }

    // Check if has extra cost
    public function hasExtraCost()
    {
        return $this->extra_cost > 0;
    }
}
