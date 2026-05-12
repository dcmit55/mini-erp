<?php

namespace App\Imports;

use App\Models\Logistic\Inventory;
use App\Models\Logistic\InventoryBatch;
use App\Models\Logistic\Category;
use App\Models\Logistic\Unit;
use App\Models\Finance\Currency;
use App\Models\Procurement\Supplier;
use App\Models\Logistic\Location;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterImport;

class InventoryImport implements ToModel, WithEvents
{
    /** @var array Track created inventory IDs and their opening qty/price */
    protected array $batches = [];

    public function model(array $row)
    {
        // Cari atau buat supplier berdasarkan nama
        $supplier = !empty($row[6]) ? Supplier::firstOrCreate(['name' => $row[6]]) : null;
        // Cari atau buat location berdasarkan nama
        $location = !empty($row[7]) ? Location::firstOrCreate(['name' => $row[7]]) : null;
        // Cari atau buat category berdasarkan nama
        $category = !empty($row[1]) ? Category::firstOrCreate(['name' => $row[1]]) : null;
        // Cari atau buat currency berdasarkan nama
        $currency = !empty($row[5]) ? Currency::firstOrCreate(['name' => $row[5]]) : null;
        // Cari atau buat unit berdasarkan nama
        $unit = !empty($row[3]) ? Unit::firstOrCreate(['name' => $row[3]]) : null;

        $inventory = new Inventory([
            'name' => $row[0],
            'category_id' => $category ? $category->id : null,
            'unit' => $unit ? $unit->name : $row[3],
            'currency_id' => $currency ? $currency->id : null,
            'supplier_id' => $supplier ? $supplier->id : null,
            'location_id' => $location ? $location->id : null,
            'remark' => $row[8] ?? null,
        ]);

        // Store qty/price/currency for batch creation after model is saved
        $this->batches[] = [
            'inventory' => $inventory,
            'qty' => is_numeric($row[2]) ? (float) $row[2] : 0,
            'price' => is_numeric($row[4]) ? (float) $row[4] : 0,
            'currency_id' => $currency ? $currency->id : null,
        ];

        return $inventory;
    }

    public function registerEvents(): array
    {
        return [
            AfterImport::class => function (AfterImport $event) {
                foreach ($this->batches as $item) {
                    $inventory = $item['inventory'];
                    if ($inventory->id && $item['qty'] > 0) {
                        InventoryBatch::create([
                            'batch_number' => \App\Models\Logistic\InventoryBatch::generateBatchNumber($inventory->id),
                            'inventory_id' => $inventory->id,
                            'qty' => $item['qty'],
                            'qty_remaining' => $item['qty'],
                            'unit_price' => $item['price'],
                            'currency_id' => $item['currency_id'] ?? null,
                            'received_date' => now()->toDateString(),
                            'source_type' => InventoryBatch::SOURCE_INITIAL_STOCK,
                            'source_id' => $inventory->id,
                        ]);
                    }
                }
            },
        ];
    }
}
