<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add material_code to inventories (with NULL-safe unique index)
 * and to lark_staging_inventories.
 * Also add processed flag to lark_staging_inventories.
 */
return new class extends Migration {
    public function up(): void
    {
        // ── inventories ───────────────────────────────────────────────────────
        Schema::table('inventories', function (Blueprint $table) {
            $table->string('material_code', 100)->nullable()->after('name')->comment('Unique material code (from Lark or manual). NULL = not yet assigned.');
        });

        // NULL-safe unique: only non-NULL values must be unique.
        // Standard unique() in MySQL/MariaDB ignores NULLs, so this is safe.
        Schema::table('inventories', function (Blueprint $table) {
            $table->unique('material_code', 'inventories_material_code_unique');
        });

        // ── lark_staging_inventories ──────────────────────────────────────────
        Schema::table('lark_staging_inventories', function (Blueprint $table) {
            $table->string('material_code', 100)->nullable()->after('lark_record_id')->comment('Material code from Lark, used for deduplication against inventories.');

            $table->boolean('processed')->default(false)->after('reviewed_at')->comment('True after the staging record has been pushed to inventories + inventory_batches.');

            $table->index('material_code');
            $table->index('processed');
        });
    }

    public function down(): void
    {
        Schema::table('inventories', function (Blueprint $table) {
            $table->dropUnique('inventories_material_code_unique');
            $table->dropColumn('material_code');
        });

        Schema::table('lark_staging_inventories', function (Blueprint $table) {
            $table->dropIndex(['material_code']);
            $table->dropIndex(['processed']);
            $table->dropColumn(['material_code', 'processed']);
        });
    }
};
