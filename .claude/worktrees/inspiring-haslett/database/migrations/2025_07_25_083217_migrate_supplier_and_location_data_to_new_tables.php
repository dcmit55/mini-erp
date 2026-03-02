<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up()
    {
        // Migrasi Supplier
        $suppliers = DB::table('inventories')->select('supplier')->whereNotNull('supplier')->distinct()->pluck('supplier');
        foreach ($suppliers as $name) {
            if ($name) DB::table('suppliers')->updateOrInsert(['name' => $name]);
        }

        // Migrasi Location
        $locations = DB::table('inventories')->select('location')->whereNotNull('location')->distinct()->pluck('location');
        foreach ($locations as $name) {
            if ($name) DB::table('locations')->updateOrInsert(['name' => $name]);
        }

        // Update inventories dengan supplier_id dan location_id
        $inventories = DB::table('inventories')->get();
        foreach ($inventories as $inv) {
            $supplierId = $inv->supplier ? DB::table('suppliers')->where('name', $inv->supplier)->value('id') : null;
            $locationId = $inv->location ? DB::table('locations')->where('name', $inv->location)->value('id') : null;
            DB::table('inventories')->where('id', $inv->id)->update([
                'supplier_id' => $supplierId,
                'location_id' => $locationId,
            ]);
        }
    }

    public function down()
    {
        // Tidak perlu rollback data supplier/location
    }
};
