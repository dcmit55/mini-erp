<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class GoodsReceive extends Model
{
    protected $fillable = ['shipping_id', 'international_waybill_no', 'freight_company', 'freight_price', 'arrived_date'];

    public function details()
    {
        return $this->hasMany(GoodsReceiveDetail::class);
    }

    public function shipping()
    {
        return $this->belongsTo(Shipping::class);
    }
}
