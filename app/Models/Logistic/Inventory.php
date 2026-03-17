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

    protected $auditInclude = ['name', 'freight_cost', 'currency_id', 'category_id', 'unit_id', 'supplier_id', 'location_id', 'lark_record_id'];

    protected $auditTimestamps = true;

    protected $fillable = ['name', 'material_code', 'category_id', 'project_id', 'unit', 'unit_id', 'unit_domestic_freight_cost', 'unit_international_freight_cost', 'currency_id', 'supplier_id', 'location_id', 'remark', 'img', 'status', 'project_lark', 'supplier_lark', 'lark_record_id', 'last_sync_at', 'source'];

    /**
     * Generate a globally unique material code in format: MAT-0001
     * Sequence is global across all inventories.
     */
    public static function generateMaterialCode(): string
    {
        for ($attempt = 0; $attempt < 10; $attempt++) {
            $maxSeq = static::withTrashed()
                ->where('material_code', 'regexp', '^MAT-[0-9]+$')
                ->selectRaw('MAX(CAST(SUBSTRING(material_code, 5) AS UNSIGNED)) as max_seq')
                ->value('max_seq') ?? 0;

            $candidate = 'MAT-' . str_pad($maxSeq + 1, 4, '0', STR_PAD_LEFT);

            if (!static::withTrashed()->where('material_code', $candidate)->exists()) {
                return $candidate;
            }
        }
        return 'MAT-' . date('ymdHis');
    }

    protected $casts = [
        'unit_domestic_freight_cost' => 'decimal:2',
        'unit_international_freight_cost' => 'decimal:2',
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

    // ─── Batch relationship ───────────────────────────────────────────────────

    public function batches()
    {
        return $this->hasMany(InventoryBatch::class);
    }

    /** Active batches that still have remaining stock, FIFO order. */
    public function activeBatches()
    {
        return $this->hasMany(InventoryBatch::class)->where('qty_remaining', '>', 0)->orderBy('received_date')->orderBy('id');
    }

    // ─── Query scopes for N+1-free stock loading ─────────────────────────────

    /**
     * Eager-load total stock in a single extra query (no N+1).
     * After this scope, $inventory->quantity uses the pre-aggregated value.
     *
     * Usage:  Inventory::withComputedStock()->orderBy('name')->get();
     */
    public function scopeWithComputedStock($query)
    {
        return $query->withSum(['batches' => fn($q) => $q->whereNull('deleted_at')->where('qty_remaining', '>', 0)], 'qty_remaining');
    }

    /**
     * Eager-load weighted average unit_price in a single extra query (no N+1).
     * Adds batches_avg_unit_price and batches_sum_qty_remaining to the model.
     *
     * Usage:  Inventory::withComputedStock()->withComputedPrice()->orderBy('name')->get();
     */
    public function scopeWithComputedPrice($query)
    {
        return $query->withAvg(['batches' => fn($q) => $q->whereNull('deleted_at')->where('qty_remaining', '>', 0)], 'unit_price');
    }

    // ─── Backward-compatible virtual attributes ───────────────────────────────

    /**
     * Virtual `quantity` attribute — sum of qty_remaining across all active batches.
     * When loaded via scopeWithComputedStock(), uses the pre-aggregated value (zero extra queries).
     * Falls back to a live query only when accessed individually.
     */
    public function getQuantityAttribute(): float
    {
        // 1. Best case: withComputedStock() was called → single pre-aggregated value
        if (array_key_exists('batches_sum_qty_remaining', $this->attributes)) {
            return (float) ($this->attributes['batches_sum_qty_remaining'] ?? 0);
        }

        // 2. Batches relation already in-memory → use it
        if ($this->relationLoaded('batches')) {
            return (float) $this->batches->whereNull('deleted_at')->where('qty_remaining', '>', 0)->sum('qty_remaining');
        }

        // 3. Last resort: single DB query (triggers N+1 warning in Debugbar)
        return (float) $this->batches()->whereNull('deleted_at')->sum('qty_remaining');
    }

    /**
     * Virtual `price` attribute — weighted average unit_price across active batches.
     * Falls back to 0 when no batches exist.
     * Keeps all existing code that reads `$inventory->price` working.
     */
    public function getPriceAttribute(): float
    {
        $batches = $this->batches()->whereNull('deleted_at')->where('qty_remaining', '>', 0)->get();

        $totalRemaining = $batches->sum('qty_remaining');

        if ($totalRemaining <= 0) {
            // Fall back to latest unit_price recorded for this inventory
            return (float) ($this->batches()->whereNull('deleted_at')->orderByDesc('received_date')->orderByDesc('id')->value('unit_price') ?? 0);
        }

        $weightedSum = $batches->sum(fn($b) => (float) $b->qty_remaining * (float) $b->unit_price);

        return $totalRemaining > 0 ? round($weightedSum / $totalRemaining, 4) : 0;
    }

    /**
     * Consume stock from batches using FIFO.
     * Throws a RuntimeException if stock is insufficient.
     *
     * @throws \RuntimeException
     */
    public function consumeStock(float $qty): void
    {
        $remaining = $qty;

        $batches = $this->activeBatches()->lockForUpdate()->get();

        foreach ($batches as $batch) {
            if ($remaining <= 0) {
                break;
            }
            $consumed = $batch->consume($remaining);
            $remaining -= $consumed;
        }

        if ($remaining > 0.0001) {
            throw new \RuntimeException("Insufficient stock for inventory #{$this->id} ({$this->name}). " . "Short by {$remaining}.");
        }
    }

    /**
     * Return stock to the most recent active batch (reverse of consumeStock).
     */
    public function returnStock(float $qty): void
    {
        $batch = $this->batches()->whereNull('deleted_at')->orderByDesc('received_date')->orderByDesc('id')->first();

        if ($batch) {
            $batch->returnQty($qty);
        } else {
            // No batch exists — create a manual adjustment batch
            InventoryBatch::create([
                'batch_number' => InventoryBatch::generateBatchNumber($this->id),
                'inventory_id' => $this->id,
                'qty' => $qty,
                'qty_remaining' => $qty,
                'unit_price' => 0,
                'currency_id' => $this->currency_id,
                'received_date' => now()->toDateString(),
                'source_type' => InventoryBatch::SOURCE_MANUAL,
                'source_id' => null,
            ]);
        }
    }

    /**
     * Create a new inventory batch for a Goods In return.
     * Always creates a NEW batch (never adds to an existing one).
     * The batch is traceable: source_type = goods_in, source_id = goods_in.id.
     */
    public function returnStockFromGoodsIn(float $qty, int $goodsInId): InventoryBatch
    {
        // Derive unit_price from the weighted average of existing batches
        $unitPrice = (float) ($this->getPriceAttribute() ?: 0);

        return InventoryBatch::create([
            'batch_number' => InventoryBatch::generateBatchNumber($this->id),
            'inventory_id' => $this->id,
            'qty' => $qty,
            'qty_remaining' => $qty,
            'unit_price' => $unitPrice,
            'currency_id' => $this->currency_id ?? 6,
            'received_date' => now()->toDateString(),
            'source_type' => InventoryBatch::SOURCE_GOODS_IN,
            'source_id' => $goodsInId,
        ]);
    }

    // ─── Cost helpers ─────────────────────────────────────────────────────────

    /**
     * Total unit cost = weighted avg unit_price (from batches) + freight costs.
     * Backward-compatible: existing code using $inventory->total_unit_cost still works.
     */
    public function getTotalUnitCostAttribute(): float
    {
        return $this->getPriceAttribute() + (float) ($this->unit_domestic_freight_cost ?? 0) + (float) ($this->unit_international_freight_cost ?? 0);
    }

    /** Total cost for a given quantity using current weighted avg price. */
    public function calculateTotalCost(float $quantity = 1): float
    {
        return $this->total_unit_cost * $quantity;
    }
}
