<?php

namespace App\Models\Logistic;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Logistic\GoodsOut;
use App\Models\Logistic\Category;
use App\Models\Procurement\Supplier;
use App\Models\Logistic\Location;
use App\Models\Finance\Currency;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

class Inventory extends Model implements Auditable
{
    use HasFactory, SoftDeletes, \OwenIt\Auditing\Auditable;

    protected $auditInclude = ['name', 'quantity', 'price', 'freight_cost', 'currency_id', 'category_id', 'unit_id', 'supplier_id', 'location_id', 'lark_record_id'];

    protected $auditTimestamps = true;

    protected $fillable = ['name', 'category_id', 'quantity', 'unit', 'unit_id', 'price', 'unit_domestic_freight_cost', 'unit_international_freight_cost', 'currency_id', 'supplier_id', 'location_id', 'remark', 'img', 'status', 'project_lark', 'supplier_lark', 'lark_record_id', 'last_sync_at'];

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

    public function unit()
    {
        return $this->belongsTo(Unit::class);
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
