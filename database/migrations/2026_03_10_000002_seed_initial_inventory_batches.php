<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Migrate existing inventory stock into inventory_batches as initial stock entries.
     * Only inventories with qty > 0 get a batch created.
     */
    public function up(): void
    {
        $inventories = DB::table('inventories')->whereNull('deleted_at')->where('quantity', '>', 0)->select('id', 'quantity', 'price', 'created_at')->get();

        $now = now();

        foreach ($inventories as $inventory) {
            // Skip if an initial batch already exists (idempotent)
            $exists = DB::table('inventory_batches')->where('inventory_id', $inventory->id)->where('source_type', 'initial_stock')->exists();

            if ($exists) {
                continue;
            }

            DB::table('inventory_batches')->insert([
                'batch_number' => 'INIT-' . $inventory->id,
                'inventory_id' => $inventory->id,
                'qty' => $inventory->quantity,
                'qty_remaining' => $inventory->quantity,
                'unit_price' => $inventory->price ?? 0,
                'received_date' => $inventory->created_at ? \Carbon\Carbon::parse($inventory->created_at)->toDateString() : $now->toDateString(),
                'source_type' => 'initial_stock',
                'source_id' => $inventory->id,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        DB::table('inventory_batches')->where('source_type', 'initial_stock')->delete();
    }
};
