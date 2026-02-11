<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class FixPurchaseUserForeignKeys extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        // Hanya jalankan jika tabel ada
        if (!Schema::hasTable('indo_purchases')) {
            return;
        }
        
        // 1. Coba drop foreign keys dengan query SQL langsung
        $columns = ['pic_id', 'approved_by', 'received_by'];
        
        foreach ($columns as $column) {
            if (Schema::hasColumn('indo_purchases', $column)) {
                // Cari foreign key name
                $fkQuery = "
                    SELECT CONSTRAINT_NAME 
                    FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
                    WHERE TABLE_SCHEMA = DATABASE() 
                    AND TABLE_NAME = 'indo_purchases' 
                    AND COLUMN_NAME = '{$column}'
                    AND REFERENCED_TABLE_NAME IS NOT NULL
                    LIMIT 1
                ";
                
                $result = DB::selectOne($fkQuery);
                
                if ($result && $result->CONSTRAINT_NAME) {
                    DB::statement("ALTER TABLE indo_purchases DROP FOREIGN KEY {$result->CONSTRAINT_NAME}");
                }
            }
        }
        
        // 2. Ubah kolom menjadi nullable
        Schema::table('indo_purchases', function (Blueprint $table) {
            // Hanya ubah kolom yang ada
            if (Schema::hasColumn('indo_purchases', 'pic_id')) {
                $table->unsignedBigInteger('pic_id')->nullable()->change();
            }
            
            if (Schema::hasColumn('indo_purchases', 'approved_by')) {
                $table->unsignedBigInteger('approved_by')->nullable()->change();
            }
            
            if (Schema::hasColumn('indo_purchases', 'received_by')) {
                $table->unsignedBigInteger('received_by')->nullable()->change();
            }
        });
        
        // 3. Buat foreign key ke users
        Schema::table('indo_purchases', function (Blueprint $table) {
            // Pastikan tabel users ada
            if (Schema::hasTable('users')) {
                if (Schema::hasColumn('indo_purchases', 'pic_id')) {
                    $table->foreign('pic_id')->references('id')->on('users')->onDelete('set null');
                }
                
                if (Schema::hasColumn('indo_purchases', 'approved_by')) {
                    $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
                }
                
                if (Schema::hasColumn('indo_purchases', 'received_by')) {
                    $table->foreign('received_by')->references('id')->on('users')->onDelete('set null');
                }
            }
            // Jika tidak ada users, gunakan employees
            elseif (Schema::hasTable('employees')) {
                if (Schema::hasColumn('indo_purchases', 'pic_id')) {
                    $table->foreign('pic_id')->references('id')->on('employees')->onDelete('set null');
                }
                
                if (Schema::hasColumn('indo_purchases', 'approved_by')) {
                    $table->foreign('approved_by')->references('id')->on('employees')->onDelete('set null');
                }
                
                if (Schema::hasColumn('indo_purchases', 'received_by')) {
                    $table->foreign('received_by')->references('id')->on('employees')->onDelete('set null');
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        if (!Schema::hasTable('indo_purchases')) {
            return;
        }
        
        Schema::table('indo_purchases', function (Blueprint $table) {
            // Drop foreign keys
            $columns = ['pic_id', 'approved_by', 'received_by'];
            
            foreach ($columns as $column) {
                if (Schema::hasColumn('indo_purchases', $column)) {
                    // Cari dan drop foreign key
                    $fkQuery = "
                        SELECT CONSTRAINT_NAME 
                        FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
                        WHERE TABLE_SCHEMA = DATABASE() 
                        AND TABLE_NAME = 'indo_purchases' 
                        AND COLUMN_NAME = '{$column}'
                        AND REFERENCED_TABLE_NAME IS NOT NULL
                        LIMIT 1
                    ";
                    
                    $result = DB::selectOne($fkQuery);
                    
                    if ($result && $result->CONSTRAINT_NAME) {
                        $table->dropForeign($result->CONSTRAINT_NAME);
                    }
                }
            }
            
            // Kembalikan pic_id ke NOT NULL
            if (Schema::hasColumn('indo_purchases', 'pic_id')) {
                $table->unsignedBigInteger('pic_id')->nullable(false)->change();
            }
            
            // Buat foreign key kembali ke employees
            if (Schema::hasTable('employees')) {
                if (Schema::hasColumn('indo_purchases', 'pic_id')) {
                    $table->foreign('pic_id')->references('id')->on('employees')->onDelete('cascade');
                }
                
                if (Schema::hasColumn('indo_purchases', 'approved_by')) {
                    $table->foreign('approved_by')->references('id')->on('employees')->onDelete('cascade');
                }
                
                if (Schema::hasColumn('indo_purchases', 'received_by')) {
                    $table->foreign('received_by')->references('id')->on('employees')->onDelete('cascade');
                }
            }
        });
    }
}