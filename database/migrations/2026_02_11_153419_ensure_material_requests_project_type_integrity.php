<?php
// database/migrations/xxxx_xx_xx_xxxxxx_add_project_type_and_internal_project_to_material_requests.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        Schema::table('material_requests', function (Blueprint $table) {
            // Tambahkan kolom project_type dengan enum
            if (!Schema::hasColumn('material_requests', 'project_type')) {
                $table->enum('project_type', ['client', 'internal'])
                      ->default('client')
                      ->after('project_id')
                      ->comment('client = project_id, internal = internal_project_id');
                echo "Added project_type column\n";
            }
            
            // Tambahkan kolom internal_project_id
            if (!Schema::hasColumn('material_requests', 'internal_project_id')) {
                // Cek tipe data id di tabel internal_projects
                $internalProjectsIdType = DB::select("
                    SHOW COLUMNS FROM internal_projects WHERE Field = 'id'
                ")[0]->Type ?? 'bigint(20)';
                
                echo "internal_projects.id type: $internalProjectsIdType\n";
                
                // Buat kolom dengan tipe yang sesuai
                if (strpos($internalProjectsIdType, 'varchar') !== false) {
                    $table->string('internal_project_id', 50)
                          ->nullable()
                          ->after('project_type');
                    echo "Added internal_project_id as VARCHAR(50)\n";
                } else {
                    $table->unsignedBigInteger('internal_project_id')
                          ->nullable()
                          ->after('project_type');
                    echo "Added internal_project_id as unsignedBigInteger\n";
                }
            }
        });
        
        // Beri waktu untuk schema update
        sleep(1);
        
        // Tambahkan foreign key untuk internal_project_id
        Schema::table('material_requests', function (Blueprint $table) {
            if (Schema::hasColumn('material_requests', 'internal_project_id')) {
                // Cek apakah foreign key sudah ada
                $foreignKeys = DB::select("
                    SELECT CONSTRAINT_NAME 
                    FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
                    WHERE TABLE_SCHEMA = DATABASE()
                    AND TABLE_NAME = 'material_requests' 
                    AND COLUMN_NAME = 'internal_project_id'
                    AND REFERENCED_TABLE_NAME IS NOT NULL
                ");
                
                if (empty($foreignKeys)) {
                    $table->foreign('internal_project_id')
                          ->references('id')
                          ->on('internal_projects')
                          ->onDelete('set null');
                    echo "Added foreign key for internal_project_id\n";
                } else {
                    echo "Foreign key for internal_project_id already exists\n";
                }
            }
        });
        
        // Tambahkan indexes untuk performa
        Schema::table('material_requests', function (Blueprint $table) {
            // Index untuk project_type
            $indexesToAdd = [
                ['name' => 'material_requests_project_type_index', 'columns' => ['project_type']],
                ['name' => 'material_requests_project_type_project_id_index', 'columns' => ['project_type', 'project_id']],
                ['name' => 'material_requests_project_type_internal_project_id_index', 'columns' => ['project_type', 'internal_project_id']],
            ];
            
            foreach ($indexesToAdd as $index) {
                if (!Schema::hasIndex('material_requests', $index['name'])) {
                    $table->index($index['columns'], $index['name']);
                    echo "Added index: {$index['name']}\n";
                }
            }
        });
        
        // Update data yang sudah ada
        echo "\n=== Updating existing data ===\n";
        
        // Update project_type untuk data yang sudah ada
        if (Schema::hasColumn('material_requests', 'project_type') && 
            Schema::hasColumn('material_requests', 'internal_project_id')) {
            
            // Set project_type = 'internal' jika ada internal_project_id
            $updatedToInternal = DB::table('material_requests')
                ->whereNotNull('internal_project_id')
                ->update(['project_type' => 'internal']);
            echo "Updated $updatedToInternal records to project_type = 'internal'\n";
            
            // Set project_type = 'client' jika ada project_id dan internal_project_id NULL
            $updatedToClient = DB::table('material_requests')
                ->whereNotNull('project_id')
                ->whereNull('internal_project_id')
                ->where(function($query) {
                    $query->where('project_type', '!=', 'client')
                          ->orWhereNull('project_type');
                })
                ->update(['project_type' => 'client']);
            echo "Updated $updatedToClient records to project_type = 'client'\n";
        }
        
        echo "\n=== Migration completed successfully ===\n";
    }

    public function down()
    {
        Schema::table('material_requests', function (Blueprint $table) {
            // Hapus foreign key
            $table->dropForeign(['internal_project_id']);
            
            // Hapus indexes
            $indexesToDrop = [
                'material_requests_project_type_index',
                'material_requests_project_type_project_id_index',
                'material_requests_project_type_internal_project_id_index',
            ];
            
            foreach ($indexesToDrop as $index) {
                if (Schema::hasIndex('material_requests', $index)) {
                    $table->dropIndex($index);
                }
            }
            
            // Hapus kolom
            $table->dropColumn(['project_type', 'internal_project_id']);
        });
    }
};
