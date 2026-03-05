<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * Migrate existing comma-separated department_lark data to pivot table
     * This is a one-time data migration for backward compatibility
     *
     * STRATEGY:
     * 1. Find job orders with department_lark containing commas
     * 2. Split department names
     * 3. Lookup department IDs
     * 4. Insert into pivot table
     */
    public function up(): void
    {
        // Get job orders with department_lark that might contain multiple departments
        $jobOrders = DB::table('job_orders')->whereNotNull('department_lark')->where('department_lark', '!=', '')->get();

        $migratedCount = 0;
        $errors = 0;

        foreach ($jobOrders as $jobOrder) {
            try {
                // Split by comma (from extractField join)
                $departmentNames = array_map('trim', explode(',', $jobOrder->department_lark));

                foreach ($departmentNames as $deptName) {
                    if (empty($deptName)) {
                        continue;
                    }

                    // Find department by name (case-insensitive)
                    $department = DB::table('departments')
                        ->whereRaw('LOWER(name) = ?', [strtolower($deptName)])
                        ->first();

                    if ($department) {
                        // Check if not already exists in pivot
                        $exists = DB::table('job_order_department')->where('job_order_id', $jobOrder->id)->where('department_id', $department->id)->exists();

                        if (!$exists) {
                            DB::table('job_order_department')->insert([
                                'job_order_id' => $jobOrder->id,
                                'department_id' => $department->id,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);

                            $migratedCount++;
                        }
                    } else {
                        Log::warning('Migration: Department not found for job order', [
                            'job_order_id' => $jobOrder->id,
                            'department_name' => $deptName,
                        ]);
                    }
                }
            } catch (\Exception $e) {
                $errors++;
                Log::error('Migration: Failed to migrate job order departments', [
                    'job_order_id' => $jobOrder->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('Job Order department migration completed', [
            'migrated_relationships' => $migratedCount,
            'errors' => $errors,
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No need to reverse - data migration is one-way
        // Pivot table will be dropped by previous migration's down()
    }
};
