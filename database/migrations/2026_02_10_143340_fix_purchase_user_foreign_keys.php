<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class FixPurchaseUserForeignKeys extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // 1. Drop semua foreign key yang reference ke employees
        Schema::table('indo_purchases', function (Blueprint $table) {
            // Coba drop foreign key jika ada
            $foreignKeys = [
                'purchases_pic_id_foreign',
                'purchases_checked_by_foreign', 
                'purchases_approved_by_foreign',
                'purchases_received_by_foreign'
            ];
            
            foreach ($foreignKeys as $fk) {
                try {
                    $table->dropForeign($fk);
                } catch (\Exception $e) {
                    // Jika foreign key tidak ada, skip
                }
            }
            
            // 2. Ubah kolom untuk allow NULL
            $table->bigInteger('pic_id')->unsigned()->nullable()->change();
            $table->bigInteger('checked_by')->unsigned()->nullable()->change();
            $table->bigInteger('approved_by')->unsigned()->nullable()->change();
            $table->bigInteger('received_by')->unsigned()->nullable()->change();
            
            // 3. Update data yang invalid (jika ada)
            DB::statement("UPDATE indo_purchases SET pic_id = NULL WHERE pic_id NOT IN (SELECT id FROM employees)");
            DB::statement("UPDATE indo_purchases SET checked_by = NULL WHERE checked_by NOT IN (SELECT id FROM employees)");
            DB::statement("UPDATE indo_purchases SET approved_by = NULL WHERE approved_by NOT IN (SELECT id FROM employees)");
            DB::statement("UPDATE indo_purchases SET received_by = NULL WHERE received_by NOT IN (SELECT id FROM employees)");
            
            // 4. Buat foreign key baru ke users (bukan employees)
            // Tapi cek dulu apakah mau reference ke employees atau users
            // Jika Anda ingin reference ke users, ganti 'employees' dengan 'users'
        });
        
        // 5. Buat foreign key ke users jika tabel users ada
        Schema::table('indo_purchases', function (Blueprint $table) {
            // Cek jika tabel users ada
            if (Schema::hasTable('users')) {
                // Foreign key ke users
                $table->foreign('pic_id')->references('id')->on('users')->onDelete('set null');
                $table->foreign('checked_by')->references('id')->on('users')->onDelete('set null');
                $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
                $table->foreign('received_by')->references('id')->on('users')->onDelete('set null');
            } else {
                // Jika tidak ada users, buat foreign key ke employees
                $table->foreign('pic_id')->references('id')->on('employees')->onDelete('set null');
                $table->foreign('checked_by')->references('id')->on('employees')->onDelete('set null');
                $table->foreign('approved_by')->references('id')->on('employees')->onDelete('set null');
                $table->foreign('received_by')->references('id')->on('employees')->onDelete('set null');
            }
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
            // Drop foreign keys
            $table->dropForeign(['pic_id']);
            $table->dropForeign(['checked_by']);
            $table->dropForeign(['approved_by']);
            $table->dropForeign(['received_by']);
            
            // Set NOT NULL kembali
            $table->bigInteger('pic_id')->unsigned()->nullable(false)->change();
            $table->bigInteger('checked_by')->unsigned()->nullable()->change();
            $table->bigInteger('approved_by')->unsigned()->nullable()->change();
            $table->bigInteger('received_by')->unsigned()->nullable()->change();
            
            // Kembalikan foreign key ke employees
            $table->foreign('pic_id')->references('id')->on('employees')->onDelete('cascade');
        });
    }
}