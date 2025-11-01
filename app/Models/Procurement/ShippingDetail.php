<?php

namespace App\Models\Procurement;

use Illuminate\Database\Eloquent\Model;
use App\Models\Procurement\PreShipping;

class ShippingDetail extends Model
{
    protected $fillable = ['shipping_id', 'pre_shipping_id', 'percentage', 'int_cost'];

    public function preShipping()
    {
        return $this->belongsTo(PreShipping::class);
    }
}
