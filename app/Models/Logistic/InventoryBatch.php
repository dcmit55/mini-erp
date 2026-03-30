<?php

namespace App\Models\Logistic;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InventoryBatch extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'inventory_batches';

    protected $fillable = ['batch_number', 'inventory_id', 'qty', 'qty_remaining', 'unit_price', 'currency_id', 'received_date', 'source_type', 'source_id', 'notes'];

    protected $casts = [
        'qty' => 'decimal:4',
        'qty_remaining' => 'decimal:4',
        'unit_price' => 'decimal:4',
        'received_date' => 'date',
        'currency_id' => 'integer',
    ];

    // ─── Source type constants ────────────────────────────────────────────────

    const SOURCE_INITIAL_STOCK = 'initial_stock';
    const SOURCE_LARK = 'lark';
    const SOURCE_GOODS_IN = 'goods_in';
    const SOURCE_PURCHASE = 'purchase';
    const SOURCE_GOODS_MOVEMENT = 'goods_movement';
    const SOURCE_MANUAL = 'manual';
    const SOURCE_INDO_PURCHASE = 'indo_purchase';

    // ─── Relationships ────────────────────────────────────────────────────────

    public function inventory()
    {
        return $this->belongsTo(Inventory::class);
    }

    public function currency()
    {
        return $this->belongsTo(\App\Models\Finance\Currency::class);
    }

    // ─── Scopes ───────────────────────────────────────────────────────────────

    /** Only batches that still have remaining stock. */
    public function scopeWithStock($query)
    {
        return $query->where('qty_remaining', '>', 0);
    }

    /** Oldest-first (FIFO consumption order). */
    public function scopeFifo($query)
    {
        return $query->orderBy('received_date')->orderBy('id');
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    /**
     * Generate a globally unique batch number in format: BATCH-0001
     * Sequence is global across ALL inventories to guarantee uniqueness
     * (the batch_number column has a global unique constraint).
     * Uses a retry loop to handle race conditions.
     */
    public static function generateBatchNumber(int $inventoryId = 0): string
    {
        $maxAttempts = 10;

        for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
            // Get the highest numeric suffix across ALL batches globally
            $last = static::withTrashed()->where('batch_number', 'like', 'BATCH-%')->orderByDesc('id')->value('batch_number');

            if ($last && preg_match('/BATCH-(\d+)$/', $last, $m)) {
                $seq = (int) $m[1] + 1;
            } else {
                $seq = 1;
            }

            $candidate = 'BATCH-' . str_pad($seq, 4, '0', STR_PAD_LEFT);

            // If no collision exists, return this candidate
            if (!static::withTrashed()->where('batch_number', $candidate)->exists()) {
                return $candidate;
            }

            // Collision found (race condition) — find the true max and increment again
            $trueMax = static::withTrashed()->where('batch_number', 'like', 'BATCH-%')->selectRaw('MAX(CAST(SUBSTRING(batch_number, 7) AS UNSIGNED)) as max_seq')->value('max_seq') ?? 0;

            return 'BATCH-' . str_pad($trueMax + 1, 4, '0', STR_PAD_LEFT);
        }

        // Absolute fallback: timestamp-based unique number
        return 'BATCH-' . date('ymdHis');
    }

    /**
     * Generate a globally sequential INIT batch number.
     * Format: INIT-XXX  (e.g. INIT-001, INIT-292)
     * Sequence is GLOBAL across all inventories — always continues from the
     * highest existing INIT number in the entire table (including soft-deleted).
     *
     * Examples:
     *   No prior INIT batches anywhere → INIT-001
     *   Highest existing is INIT-291   → INIT-292
     */
    public static function generateInitBatchNumber(int $inventoryId = 0): string
    {
        // Find the highest numeric suffix among ALL INIT-xxx batches globally
        $last = static::withTrashed()
            ->where('batch_number', 'regexp', '^INIT-[0-9]+$')
            ->selectRaw('MAX(CAST(SUBSTRING(batch_number, 6) AS UNSIGNED)) as max_seq')
            ->value('max_seq') ?? 0;

        $next = (int) $last + 1;

        return 'INIT-' . str_pad($next, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Consume qty from this batch (FIFO helper).
     * Returns the amount actually consumed.
     */
    public function consume(float $qty): float
    {
        $consumed = min($this->qty_remaining, $qty);
        $this->decrement('qty_remaining', $consumed);
        return $consumed;
    }

    /**
     * Return qty back to this batch (reverse of consume).
     */
    public function returnQty(float $qty): void
    {
        $this->increment('qty_remaining', $qty);
    }

    // ─── Accessors ────────────────────────────────────────────────────────────

    /** Total value of remaining stock in this batch. */
    public function getRemainingValueAttribute(): float
    {
        return (float) $this->qty_remaining * (float) $this->unit_price;
    }

    /** Whether this batch is fully consumed. */
    public function getIsDepletedAttribute(): bool
    {
        return (float) $this->qty_remaining <= 0;
    }
}
