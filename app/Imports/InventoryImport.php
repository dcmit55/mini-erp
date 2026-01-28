<?php

namespace App\Imports;

use App\Models\Logistic\Inventory;
use App\Models\Logistic\Category;
use App\Models\Logistic\Unit;
use App\Models\Finance\Currency;
use App\Models\Procurement\Supplier;
use App\Models\Logistic\Location;
use Maatwebsite\Excel\Concerns\ToModel;

class InventoryImport implements ToModel
{
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

        return new Inventory([
            'name' => $row[0],
            'category_id' => $category ? $category->id : null,
            'quantity' => $row[2],
            'unit' => $unit ? $unit->name : $row[3],
            'price' => $row[4],
            'currency_id' => $currency ? $currency->id : null,
            'supplier_id' => $supplier ? $supplier->id : null,
            'location_id' => $location ? $location->id : null,
            'remark' => $row[8],
        ]);
    }
}
