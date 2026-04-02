<?php

namespace App\Models\Logistic;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class StockAdjustment extends Model implements AuditableContract
{
    use \OwenIt\Auditing\Auditable;

    protected $table = 'stock_adjustments';

    protected $fillable = ['inventory_id', 'batch_id', 'type', 'qty', 'reason', 'created_by', 'price'];

    protected $auditInclude = ['inventory_id', 'batch_id', 'type', 'qty', 'price', 'reason', 'created_by'];

    protected $auditTimestamps = true;

    protected $casts = [
        'qty' => 'decimal:4',
        'price' => 'decimal:4',
    ];

    // ─── Type constants ───────────────────────────────────────────────────────

    const TYPE_INITIAL_STOCK = 'initial_stock';
    const TYPE_ADJUSTMENT = 'adjustment';
    const TYPE_CORRECTION = 'correction'; // legacy — kept for DB backwards-compat

    /** Labels shown in UI (correction maps to Adjustment for display) */
    public static function typeLabel(string $type): string
    {
        return match ($type) {
            self::TYPE_INITIAL_STOCK => 'Initial Stock',
            self::TYPE_ADJUSTMENT, self::TYPE_CORRECTION => 'Adjustment',
            default => ucfirst($type),
        };
    }

    public static function typeBadgeClass(string $type): string
    {
        return match ($type) {
            self::TYPE_INITIAL_STOCK => 'bg-info',
            self::TYPE_ADJUSTMENT => 'bg-warning text-dark',
            self::TYPE_CORRECTION => 'bg-secondary',
            default => 'bg-light text-dark',
        };
    }

    // ─── Relationships ────────────────────────────────────────────────────────

    public function inventory()
    {
        return $this->belongsTo(Inventory::class);
    }

    public function batch()
    {
        return $this->belongsTo(InventoryBatch::class);
    }

    public function creator()
    {
        return $this->belongsTo(\App\Models\Admin\User::class, 'created_by');
    }
}
