<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PurchaseRequest extends Model
{
    use HasFactory;

    protected $fillable = ['type', 'material_name', 'inventory_id', 'required_quantity', 'unit', 'stock_level', 'project_id', 'requested_by', 'supplier_id', 'price_per_unit', 'currency_id', 'approval_status', 'delivery_date', 'img'];

    protected $casts = [
        'delivery_date' => 'date',
        'required_quantity' => 'decimal:2',
        'price_per_unit' => 'decimal:2',
    ];

    protected $attributes = [
        'approval_status' => 'Pending',
    ];

    public function inventory()
    {
        return $this->belongsTo(Inventory::class);
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }

    public function preShipping()
    {
        return $this->hasOne(PreShipping::class);
    }
}
