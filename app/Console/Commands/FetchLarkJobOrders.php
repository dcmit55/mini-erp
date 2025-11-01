<?php
// filepath: c:\xampp\htdocs\inventory-system-v2-upg-larv-oct\app\Console\Commands\FetchLarkJobOrders.php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\LarkIntegration;
use App\Models\Production\Project;
use App\Models\Admin\Department;

class FetchLarkJobOrders extends Command
{
    protected $signature = 'lark:fetch-job-orders {--force : Force sync all data including previously deleted ones} {--debug : Show detailed field information}';
    protected $description = 'Fetch job orders from Lark and sync to projects table';

    public function handle(LarkIntegration $lark)
    {
        $this->info('Fetching job orders from Lark...');
        $jobOrders = $lark->fetchJobOrders();

        if (!$jobOrders) {
            $this->error('No job orders found or failed to fetch.');
            return 1;
        }

        $this->info('Total records found: ' . count($jobOrders));
        $successCount = 0;
        $updatedCount = 0;
        $skipCount = 0;
        $debugMode = $this->option('debug');

        // Cek apakah menggunakan force sync
        $forceSync = $this->option('force');
        if ($forceSync) {
            $this->warn('Force sync enabled - will sync all data including previously deleted ones');
        }

        foreach ($jobOrders as $index => $jobOrder) {
            $recordId = $jobOrder['record_id'] ?? 'unknown';
            $this->info("Processing record #{$index}: " . $recordId);

            $fields = $jobOrder['fields'] ?? [];

            // DEBUG: Tampilkan semua field yang tersedia
            if ($debugMode) {
                $this->line('Available fields:');
                foreach ($fields as $fieldName => $fieldValue) {
                    if (is_array($fieldValue)) {
                        $this->line("  - {$fieldName}: " . json_encode($fieldValue));
                    } else {
                        $this->line("  - {$fieldName}: {$fieldValue}");
                    }
                }
                $this->line('---');
            }

            $jobOrderName = null;

            // PRIORITAS 1: Coba ambil dari field "Job Order" langsung
            if (isset($fields['Job Order'])) {
                if (is_array($fields['Job Order']) && isset($fields['Job Order'][0]['text'])) {
                    $jobOrderName = $fields['Job Order'][0]['text'];
                    $this->line("✓ Found Job Order (array): {$jobOrderName}");
                } elseif (is_string($fields['Job Order'])) {
                    $jobOrderName = $fields['Job Order'];
                    $this->line("✓ Found Job Order (string): {$jobOrderName}");
                }
            }

            // PRIORITAS 2: Coba ambil dari "Job Order Name / Description"
            if (!$jobOrderName && isset($fields['Job Order Name / Description'])) {
                $jobOrderName = $fields['Job Order Name / Description'];
                $this->line("✓ Found Job Order Name/Description: {$jobOrderName}");
            }

            // PRIORITAS 3: Coba ambil dari "Job Order Name"
            if (!$jobOrderName && isset($fields['Job Order Name'])) {
                if (is_array($fields['Job Order Name']) && isset($fields['Job Order Name'][0]['text'])) {
                    $jobOrderName = $fields['Job Order Name'][0]['text'];
                    $this->line("✓ Found Job Order Name (array): {$jobOrderName}");
                } elseif (is_string($fields['Job Order Name'])) {
                    $jobOrderName = $fields['Job Order Name'];
                    $this->line("✓ Found Job Order Name (string): {$jobOrderName}");
                }
            }

            // PRIORITAS 4: Fallback ke "Project List" (untuk backward compatibility)
            if (!$jobOrderName && isset($fields['Project List'][0]['text'])) {
                $jobOrderName = $fields['Project List'][0]['text'];
                $this->warn("⚠ Using Project List as fallback: {$jobOrderName}");
            }

            // PRIORITAS 5: Coba field lain yang mungkin mengandung job order
            if (!$jobOrderName) {
                $possibleFields = [
                    'Job Order ID',
                    'Order Name',
                    'Order ID',
                    'Job Name',
                    'Task Name',
                    'Work Order',
                    'Project Name', // sebagai fallback terakhir
                ];

                foreach ($possibleFields as $fieldName) {
                    if (isset($fields[$fieldName])) {
                        if (is_array($fields[$fieldName]) && isset($fields[$fieldName][0]['text'])) {
                            $jobOrderName = $fields[$fieldName][0]['text'];
                            $this->warn("⚠ Using {$fieldName} as fallback: {$jobOrderName}");
                            break;
                        } elseif (is_string($fields[$fieldName])) {
                            $jobOrderName = $fields[$fieldName];
                            $this->warn("⚠ Using {$fieldName} as fallback: {$jobOrderName}");
                            break;
                        }
                    }
                }
            }

            if ($jobOrderName) {
                // Bersihkan nama job order dari karakter yang tidak diinginkan
                $jobOrderName = trim($jobOrderName);

                // Skip jika nama kosong setelah di-trim
                if (empty($jobOrderName)) {
                    $this->warn("⚠ Skipped record $recordId: Empty job order name after cleaning");
                    $skipCount++;
                    continue;
                }

                // Cari atau buat department 'Lark Imported'
                $department = Department::firstOrCreate(['name' => 'Lark Imported'], ['description' => 'Projects imported from Lark Job Orders']);

                // Gunakan updateOrCreate untuk upsert data
                $project = Project::updateOrCreate(
                    [
                        'name' => $jobOrderName, // Field unik untuk mencocokkan
                    ],
                    [
                        'qty' => 1,
                        'department_id' => $department->id,
                        'created_by' => 'lark-sync',
                        'lark_record_id' => $recordId, // Simpan record ID dari Lark
                        'last_sync_at' => now(), // Timestamp sync terakhir
                    ],
                );

                if ($project->wasRecentlyCreated) {
                    $this->info("✓ Created new project: $jobOrderName");
                    $successCount++;
                } else {
                    $this->line("↻ Updated existing project: $jobOrderName");
                    $updatedCount++;
                }
            } else {
                $this->error("✗ Skipped record $recordId: No job order field found");
                if ($debugMode) {
                    $this->line('Available fields were: ' . implode(', ', array_keys($fields)));
                }
                $skipCount++;
            }
        }

        // Tampilkan summary
        $this->info('');
        $this->info('=== SYNC SUMMARY ===');
        $this->info("✓ New projects created: $successCount");
        $this->info("↻ Existing projects updated: $updatedCount");
        if ($skipCount > 0) {
            $this->warn("⚠ Records skipped: $skipCount");
        }
        $this->info('📊 Total processed: ' . ($successCount + $updatedCount + $skipCount));

        if ($debugMode) {
            $this->info('');
            $this->info('💡 Tips:');
            $this->info('- Use --debug flag to see all available fields');
            $this->info('- Check your Lark table structure if many records are skipped');
            $this->info('- The command prioritizes "Job Order" field over "Project List"');
        }

        return 0;
    }
}
