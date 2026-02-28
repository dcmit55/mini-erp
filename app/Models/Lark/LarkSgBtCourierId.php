<?php

namespace App\Models\Lark;

use Illuminate\Database\Eloquent\Model;

/**
 * Lark SG-BT Courier ID Staging Table
 *
 * Stores raw courier data from Lark (Singapore to Batam direction)
 * This is a staging table, not final ERP data
 */
class LarkSgBtCourierId extends Model
{
    protected $table = 'lark_sg_bt_courier_ids';

    protected $fillable = ['lark_record_id', 'name', 'type_movement', 'date', 'project_lark', 'transport_cost', 'baggage_cost', 'gst_cost', 'qty_total', 'cost_per_item', 'last_sync_at'];

    protected $casts = [
        'date' => 'date',
        'transport_cost' => 'decimal:2',
        'baggage_cost' => 'decimal:2',
        'gst_cost' => 'decimal:2',
        'cost_per_item' => 'decimal:2',
        'qty_total' => 'integer',
        'last_sync_at' => 'datetime',
    ];

    /**
     * Get total cost in IDR (transport + baggage + gst)
     */
    public function getTotalCostAttribute(): float
    {
        return ($this->transport_cost ?? 0) + ($this->baggage_cost ?? 0) + ($this->gst_cost ?? 0);
    }

    /**
     * Get total cost in SGD (converted from IDR)
     */
    public function getTotalCostSgdAttribute(): float
    {
        $sgdRate = \Cache::remember('sgd_exchange_rate', 3600, function () {
            return \App\Models\Finance\Currency::where('name', 'SGD')->value('exchange_rate') ?? 12671.96;
        });

        return round($this->total_cost / $sgdRate, 2);
    }

    /**
     * Reverse relation: Courier has many Items
     */
    public function items()
    {
        return $this->hasMany(LarkSgBtItemTracking::class, 'courier_id');
    }

    /**
     * Get formatted items list for display
     */
    public function getItemsListAttribute(): string
    {
        if ($this->relationLoaded('items')) {
            $items = $this->items;
            $count = $items->count();

            if ($count === 0) {
                return '-';
            }

            // Show first 3 items, then "and X more"
            $itemNames = $items->take(3)->pluck('item_name')->toArray();
            $display = implode(', ', $itemNames);

            if ($count > 3) {
                $display .= sprintf(' <span class="text-muted">(+%d more)</span>', $count - 3);
            }

            return $display;
        }

        return '-';
    }

    /**
     * Get total quantity of all items
     */
    public function getItemsTotalQtyAttribute(): int
    {
        if ($this->relationLoaded('items')) {
            return $this->items->sum('qty') ?? 0;
        }
        return 0;
    }
}
