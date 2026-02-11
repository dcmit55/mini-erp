<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class RemoveUniqueConstraintFromPoNumberInPurchasesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('indo_purchases', function (Blueprint $table) {
            // Coba hapus unique constraint dengan try-catch
            try {
                $table->dropUnique('purchases_po_number_unique');
            } catch (\Exception $e) {
                // Coba nama index alternatif
                try {
                    $table->dropUnique('indo_purchases_po_number_unique');
                } catch (\Exception $e) {
                    // Log tapi jangan gagal
                    \Log::info('Unique constraint tidak ditemukan, melanjutkan...');
                }
            }
            
            // Tambahkan index regular untuk performance
            $table->index('po_number', 'purchases_po_number_index');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('indo_purchases', function (Blueprint $table) {
            // Hapus index regular
            $table->dropIndex('purchases_po_number_index');
            
            // Hapus data duplikat sebelum restore unique constraint
            DB::statement('
                DELETE p1 FROM indo_purchases p1
                INNER JOIN indo_purchases p2 
                WHERE p1.id < p2.id 
                AND p1.po_number = p2.po_number
            ');
            
            // Restore unique constraint
            $table->unique('po_number', 'purchases_po_number_unique');
        });
    }
}