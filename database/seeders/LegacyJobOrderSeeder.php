<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Production\JobOrder;
use App\Models\Production\Project;
use Illuminate\Support\Facades\DB;

class LegacyJobOrderSeeder extends Seeder
{
    /**
     * Seed LEGACY JOB ORDER placeholder
     *
     * Purpose: Handle material requests without assigned job orders
     * Since job_order_id is REQUIRED in goods_out, we need a placeholder
     *
     * @return void
     */
    public function run()
    {
        // Check if LEGACY PROJECT exists, if not create it
        $legacyProject = Project::firstOrCreate(
            ['name' => 'LEGACY PROJECT'],
            [
                'type_dept' => null,
                'department_id' => null,
                'sales' => 'System',
                'qty' => 0,
                'project_status_id' => 1, // Assume first status
                'project_status' => 'Pending',
                'start_date' => now()->subYears(5)->format('Y-m-d'),
                'deadline' => null,
                'finish_date' => null,
                'created_by' => 1,
                'stage' => 'Placeholder for materials without assigned project/job order',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );

        // Create or update LEGACY JOB ORDER
        $legacyJobOrder = JobOrder::updateOrCreate(
            ['id' => 'LEGACY-JO-000'],
            [
                'project_id' => $legacyProject->id,
                'project_lark' => null,
                'department_id' => null,
                'department_lark' => null,
                'name' => 'LEGACY JOB ORDER',
                'description' => 'Placeholder job order for material requests without assigned job order. Required because goods_out.job_order_id is mandatory.',
                'start_date' => now()->subYears(5)->format('Y-m-d'),
                'end_date' => null,
                'source_by' => 'System',
                'notes' => 'Auto-generated placeholder for legacy materials',
                'lark_record_id' => null,
                'last_sync_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );

        $this->command->info("✓ LEGACY JOB ORDER created/updated: {$legacyJobOrder->id} - {$legacyJobOrder->name}");
        $this->command->info("  → Linked to project: {$legacyProject->name} (ID: {$legacyProject->id})");
    }
}
