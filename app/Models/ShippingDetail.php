<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShippingDetail extends Model
{
    protected $fillable = ['shipping_id', 'pre_shipping_id', 'percentage', 'int_cost'];

    public function preShipping()
    {
        return $this->belongsTo(PreShipping::class);
    }
}
