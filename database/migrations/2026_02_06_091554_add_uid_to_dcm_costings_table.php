<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class AddUidToDcmCostingsTable extends Migration
{
    public function up()
    {
        Schema::table('dcm_costings', function (Blueprint $table) {
            // Tambah kolom uid
            $table->uuid('uid')->unique()->after('id')->nullable();
            
            // Tambah index untuk uid
            $table->index('uid');
        });
        
        // Generate uid untuk data yang sudah ada
        $this->generateUidForExistingRecords();
    }

    public function down()
    {
        Schema::table('dcm_costings', function (Blueprint $table) {
            $table->dropIndex(['uid']);
            $table->dropColumn('uid');
        });
    }
    
    /**
     * Generate UUID untuk record yang sudah ada
     */
    protected function generateUidForExistingRecords()
    {
        if (Schema::hasTable('dcm_costings')) {
            $records = DB::table('dcm_costings')->whereNull('uid')->get();
            
            foreach ($records as $record) {
                DB::table('dcm_costings')
                    ->where('id', $record->id)
                    ->update(['uid' => Str::uuid()]);
            }
        }
    }
}