<?php

namespace App\Models\Procurement;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
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
    use SoftDeletes;

    protected $fillable = ['type', 'material_name', 'inventory_id', 'required_quantity', 'qty_to_buy', 'unit', 'stock_level', 'project_id', 'requested_by', 'supplier_id', 'price_per_unit', 'currency_id', 'approval_status', 'delivery_date', 'remark', 'img', 'original_supplier_id', 'supplier_change_reason'];

    protected $casts = [
        'delivery_date' => 'date',
        'required_quantity' => 'decimal:2',
        'price_per_unit' => 'decimal:2',
        'deleted_at' => 'datetime',
    ];

    protected $attributes = [
        'approval_status' => 'Pending',
    ];

    protected $auditInclude = ['type', 'material_name', 'inventory_id', 'required_quantity', 'qty_to_buy', 'supplier_id', 'original_supplier_id', 'supplier_change_reason', 'price_per_unit', 'currency_id', 'approval_status', 'delivery_date', 'remark'];

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

    // Relasi untuk original supplier
    public function originalSupplier()
    {
        return $this->belongsTo(Supplier::class, 'original_supplier_id');
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }

    public function preShipping()
    {
        return $this->hasOne(PreShipping::class, 'purchase_request_id');
    }

    public function hasBeenShipped()
    {
        if (!$this->preShipping) {
            return false;
        }

        return $this->preShipping->shippingDetail !== null;
    }

    public function hasBeenReceived()
    {
        if (!$this->hasBeenShipped()) {
            return false;
        }

        $shippingDetail = $this->preShipping->shippingDetail;
        if (!$shippingDetail || !$shippingDetail->shipping_id) {
            return false;
        }

        $shipping = \App\Models\Procurement\Shipping::find($shippingDetail->shipping_id);

        if (!$shipping) {
            return false;
        }

        return \App\Models\Procurement\GoodsReceive::where('shipping_id', $shipping->id)->exists();
    }

    // Method untuk mendapatkan status pengiriman
    public function getShippingStatus()
    {
        if (!$this->preShipping) {
            return 'not_in_pre_shipping';
        }

        if (!$this->preShipping->shippingDetail) {
            return 'in_pre_shipping';
        }

        try {
            if ($this->hasBeenReceived()) {
                return 'received';
            }
        } catch (\Exception $e) {
            \Log::warning('Error checking received status', [
                'purchase_request_id' => $this->id,
                'error' => $e->getMessage(),
            ]);
        }

        return 'in_shipping';
    }

    // Helper method untuk cek apakah supplier berubah
    public function hasSupplierChanged()
    {
        // ✅ FIX: Jika original_supplier_id NULL, berarti supplier belum pernah di-set
        // (case: new_material atau restock yang baru dibuat)
        if ($this->original_supplier_id === null) {
            return false; // Not a "change", just first assignment
        }

        // Supplier dianggap changed jika current supplier != original supplier
        return $this->supplier_id !== $this->original_supplier_id;
    }

    // Relasi ke ShortageItem
    public function shortageItems()
    {
        return $this->hasMany(ShortageItem::class);
    }

    // METHOD: Check if PR has unresolved shortage
    public function hasUnresolvedShortage()
    {
        return $this->shortageItems()->resolvable()->exists();
    }
}
