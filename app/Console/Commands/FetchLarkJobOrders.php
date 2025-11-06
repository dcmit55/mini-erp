<?php
/**
 * ============================================================
 * LARK JOB ORDERS SYNCHRONIZATION COMMAND
 * ============================================================
 *
 * File: FetchLarkJobOrders.php
 * Purpose: Sinkronisasi data Job Orders dari Lark ke Laravel
 *
 * ALUR KERJA:
 * 1. Validasi access token Lark
 * 2. Hapus semua data lama (fresh sync)
 * 3. Fetch data dari Lark API
 * 4. Parse setiap field (name, qty, deadline, status, dll)
 * 5. Download images dari Lark (jika ada)
 * 6. Simpan/update ke database Laravel
 * 7. Tampilkan summary hasil sync
 *
 * FIELD YANG DIAMBIL DARI LARK:
 * - Job Order Name / Description → name
 * - QTY / Quantity → qty
 * - Delivery Date → deadline (timezone: Asia/Singapore UTC+8)
 * - Job Status / Costume Production Stage → project_status_id
 * - Dept-in-charge → department_id
 * - Costume/Plush/Mascot Production Stage → stage
 * - Submission Form → submission_form (link)
 * - WIP Images → img (download ke storage/app/public/projects/)
 *
 * PENGGUNAAN:
 * - Basic: php artisan lark:fetch-job-orders
 * - Debug mode: php artisan lark:fetch-job-orders --debug
 * - Force sync: php artisan lark:fetch-job-orders --force
 *
 * TIMEZONE HANDLING:
 * - Lark UI menggunakan Asia/Singapore (UTC+8)
 * - Timestamp dari Lark dalam format milidetik
 * - Parsing menggunakan Carbon dengan timezone Singapore
 * - Database menyimpan format DATE (Y-m-d)
 *
 * @author Development Team
 * @version 2.0
 */

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\LarkIntegration;
use App\Models\Production\Project;
use App\Models\Admin\Department;
use App\Models\Production\ProjectStatus;

class FetchLarkJobOrders extends Command
{
    protected $signature = 'lark:fetch-job-orders {--force : Force sync all data including previously deleted ones} {--debug : Show detailed field information}';
    protected $description = 'Fetch job orders from Lark and sync to projects table';

    /**
     * Main method untuk menjalankan sinkronisasi
     */
    public function handle(LarkIntegration $lark)
    {
        $this->info('🔄 Starting Lark Job Orders Synchronization...');
        $token = $lark->getAccessToken();
        if (!$token) {
            $this->error('❌ Failed to get Lark access token.');
            return 1;
        }
        $this->info('✓ Access token obtained successfully');

        // Ambil data dari Lark API
        $jobOrders = $lark->fetchJobOrders();
        if (!$jobOrders) {
            $this->error('No job orders found or failed to fetch.');
            return 1;
        }
        $this->info('Total records found: ' . count($jobOrders));

        // Ambil semua lark_record_id dari hasil fetch Lark
        $larkIds = collect($jobOrders)->pluck('record_id')->filter()->all();

        // Hapus hanya project hasil import Lark yang sudah tidak ada di Lark
        $deletedCount = Project::where('created_by', 'Lark Imported')->whereNotIn('lark_record_id', $larkIds)->delete();
        $this->info("✓ Deleted {$deletedCount} old Lark records (not found in Lark)");

        // Ambil data dari Lark API
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

        foreach ($jobOrders as $index => $jobOrder) {
            $recordId = $jobOrder['record_id'] ?? 'unknown';
            $fields = $jobOrder['fields'] ?? [];

            // --- Ambil nama project ---
            $jobOrderName = $fields['Job Order Name / Description'] ?? null;
            if (!$jobOrderName) {
                $this->error("Skipped record $recordId: No job order field found");
                $skipCount++;
                continue;
            }

            // --- Ambil quantity ---
            $quantity = null;
            if (isset($fields['QTY'])) {
                if (is_numeric($fields['QTY'])) {
                    $quantity = (int) $fields['QTY'];
                } elseif (is_array($fields['QTY']) && isset($fields['QTY'][0])) {
                    $quantity = (int) $fields['QTY'][0];
                }
            }

            // --- Ambil deadline dari Delivery Date (timezone Singapore) ---
            $deadline = null;
            if (isset($fields['Delivery Date'])) {
                $deadline = $this->parseDateField($fields['Delivery Date']);
            }

            // --- Ambil status ---
            $statusName = null;
            if (isset($fields['Job Status'])) {
                if (is_array($fields['Job Status']) && isset($fields['Job Status'][0]['text'])) {
                    $statusName = $fields['Job Status'][0]['text'];
                } elseif (is_string($fields['Job Status'])) {
                    $statusName = trim($fields['Job Status']);
                }
            }
            // Cari/insert status di tabel project_statuses
            $projectStatusId = null;
            if ($statusName) {
                $status = ProjectStatus::firstOrCreate(['name' => $statusName]);
                $projectStatusId = $status->id;
            }

            // --- Ambil department dari Dept-in-charge ---
            $primaryDepartmentId = null;
            if (isset($fields['Dept-in-charge'])) {
                $deptInCharge = $fields['Dept-in-charge'];
                $deptName = null;
                if (is_array($deptInCharge) && !empty($deptInCharge)) {
                    $firstDept = $deptInCharge[0];
                    $deptName = is_array($firstDept) && isset($firstDept['text']) ? $firstDept['text'] : $firstDept;
                } elseif (is_string($deptInCharge)) {
                    $deptName = trim($deptInCharge);
                }
                if ($deptName) {
                    $department = Department::firstOrCreate(['name' => trim($deptName)], ['description' => 'Department synced from Lark']);
                    $primaryDepartmentId = $department->id;
                }
            }

            // --- Ambil stage dari salah satu kolom stage ---
            $stage = null;
            $stageFields = ['Costume Production Stage', 'Plush Production Stage', 'Mascot/Statue Production Stage'];
            foreach ($stageFields as $stageField) {
                if (isset($fields[$stageField])) {
                    if (is_array($fields[$stageField]) && isset($fields[$stageField][0]['text'])) {
                        $stage = $fields[$stageField][0]['text'];
                    } elseif (is_string($fields[$stageField])) {
                        $stage = trim($fields[$stageField]);
                    }
                    if ($stage) {
                        break;
                    }
                }
            }

            // --- Ambil submission form ---
            $submissionForm = null;
            if (isset($fields['Submission Form'])) {
                if (is_array($fields['Submission Form']) && isset($fields['Submission Form'][0])) {
                    if (isset($fields['Submission Form'][0]['link'])) {
                        $submissionForm = $fields['Submission Form'][0]['link'];
                    } elseif (isset($fields['Submission Form'][0]['text'])) {
                        $submissionForm = $fields['Submission Form'][0]['text'];
                    }
                } elseif (is_string($fields['Submission Form'])) {
                    $submissionForm = trim($fields['Submission Form']);
                }
            }

            // --- Siapkan data untuk disimpan ---
            $projectData = [
                'qty' => $quantity,
                'department_id' => $primaryDepartmentId,
                'created_by' => 'Lark Synced',
                'lark_record_id' => $recordId,
                'last_sync_at' => now(),
                'name' => $jobOrderName,
                'stage' => $stage,
                'submission_form' => $submissionForm,
                'project_status_id' => $projectStatusId,
            ];
            if ($deadline) {
                $projectData['deadline'] = $deadline;
            }

            // --- Simpan ke database ---
            $project = Project::updateOrCreate(['lark_record_id' => $recordId], $projectData);

            // --- Output hasil ---
            if ($project->wasRecentlyCreated) {
                $this->info("✓ Created: $jobOrderName | Qty: {$quantity}" . ($deadline ? " | Deadline: {$deadline}" : '') . ($statusName ? " | Status: {$statusName}" : '') . ($stage ? " | Stage: {$stage}" : ''));
                $successCount++;
            } else {
                $this->line("↻ Updated: $jobOrderName | Qty: {$quantity}" . ($deadline ? " | Deadline: {$deadline}" : '') . ($statusName ? " | Status: {$statusName}" : '') . ($stage ? " | Stage: {$stage}" : ''));
                $updatedCount++;
            }
        }

        // --- Summary ---
        $this->info('');
        $this->info('=== SYNC SUMMARY ===');
        $this->info("✓ New projects created: $successCount");
        $this->info("↻ Existing projects updated: $updatedCount");
        if ($skipCount > 0) {
            $this->warn("⚠ Records skipped: $skipCount");
        }
        $this->info(' Total processed: ' . ($successCount + $updatedCount + $skipCount));
        return 0;
    }

    /**
     *
     *
     * @param mixed $field - Field date dari Lark API (bisa berupa timestamp, string, atau array)
     * @return string|null - Tanggal dalam format Y-m-d (timezone Singapore), atau null jika gagal parse
     */
    private function parseDateField($field)
    {
        if (!$field) {
            return null;
        }
        try {
            $larkTimezone = 'Asia/Singapore';
            if (is_numeric($field) && $field > 1000000000000) {
                $timestamp = $field / 1000;
                return \Carbon\Carbon::createFromTimestamp($timestamp, 'UTC')->setTimezone($larkTimezone)->format('Y-m-d');
            }

            return null;
        } catch (\Exception $e) {
            \Log::warning('Failed to parse date: ' . json_encode($field) . ' - ' . $e->getMessage());
            return null;
        }
    }
}
