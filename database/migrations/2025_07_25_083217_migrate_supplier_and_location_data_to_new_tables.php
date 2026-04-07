<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up()
    {
        // Migrasi Supplier
        if (\Illuminate\Support\Facades\Schema::hasColumn('inventories', 'supplier')) {
            $suppliers = DB::table('inventories')->select('supplier')->whereNotNull('supplier')->distinct()->pluck('supplier');
            foreach ($suppliers as $name) {
                if ($name) DB::table('suppliers')->updateOrInsert(['name' => $name]);
            }
        }

        // Migrasi Location
        if (\Illuminate\Support\Facades\Schema::hasColumn('inventories', 'location')) {
            $locations = DB::table('inventories')->select('location')->whereNotNull('location')->distinct()->pluck('location');
            foreach ($locations as $name) {
                if ($name) DB::table('locations')->updateOrInsert(['name' => $name]);
            }
        }

        // Update inventories dengan supplier_id dan location_id
        if (\Illuminate\Support\Facades\Schema::hasColumn('inventories', 'supplier') || \Illuminate\Support\Facades\Schema::hasColumn('inventories', 'location')) {
            $inventories = DB::table('inventories')->get();
            foreach ($inventories as $inv) {
                $updates = [];
                if (isset($inv->supplier)) {
                    $updates['supplier_id'] = $inv->supplier ? DB::table('suppliers')->where('name', $inv->supplier)->value('id') : null;
                }
                if (isset($inv->location)) {
                    $updates['location_id'] = $inv->location ? DB::table('locations')->where('name', $inv->location)->value('id') : null;
                }
                if ($updates) {
                    DB::table('inventories')->where('id', $inv->id)->update($updates);
                }
            }
        }
    }

    public function down()
    {
        // Tidak perlu rollback data supplier/location
    }
};
