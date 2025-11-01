<?php

namespace App\Models\Procurement;

use Illuminate\Database\Eloquent\Model;
use App\Models\Admin\User;
use App\Models\Finance\Currency;
use App\Models\Logistic\Inventory;
use App\Models\Production\Project;
use App\Models\Procurement\Supplier;
use App\Models\Procurement\PreShipping;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class PurchaseRequest extends Model implements AuditableContract
{
    use HasFactory, \OwenIt\Auditing\Auditable;

    protected $fillable = ['type', 'material_name', 'inventory_id', 'required_quantity', 'qty_to_buy', 'unit', 'stock_level', 'project_id', 'requested_by', 'supplier_id', 'price_per_unit', 'currency_id', 'approval_status', 'delivery_date', 'remark', 'img'];

    protected $casts = [
        'delivery_date' => 'date',
        'required_quantity' => 'decimal:2',
        'price_per_unit' => 'decimal:2',
    ];

    protected $attributes = [
        'approval_status' => 'Pending',
    ];

    protected $auditInclude = ['type', 'material_name', 'inventory_id', 'required_quantity', 'qty_to_buy', 'supplier_id', 'price_per_unit', 'currency_id', 'approval_status', 'delivery_date', 'remark'];

    protected $auditTimestamps = true;

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
