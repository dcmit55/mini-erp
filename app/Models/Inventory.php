<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Inventory extends Model
{
    use SoftDeletes;
    use HasFactory;

    protected $fillable = ['name', 'category_id', 'quantity', 'unit', 'price', 'unit_domestic_freight_cost', 'unit_international_freight_cost', 'currency_id', 'supplier_id', 'location_id', 'remark', 'img', 'status'];

    protected $casts = [
        'price' => 'decimal:2',
        'unit_domestic_freight_cost' => 'decimal:2',
        'unit_international_freight_cost' => 'decimal:2',
        'quantity' => 'decimal:2',
    ];

    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }

    public function goodsOuts()
    {
        return $this->hasMany(GoodsOut::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    // Method untuk menghitung total unit cost
    public function getTotalUnitCostAttribute()
    {
        return ($this->price ?? 0) + ($this->unit_domestic_freight_cost ?? 0) + ($this->unit_international_freight_cost ?? 0);
    }

    // Method untuk menghitung total cost berdasarkan quantity
    public function calculateTotalCost($quantity = 1)
    {
        return $this->total_unit_cost * $quantity;
    }
}
