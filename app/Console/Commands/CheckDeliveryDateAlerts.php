<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Production\JobOrder;
use App\Events\JobOrderCountdownAlert;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class CheckDeliveryDateAlerts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'job-orders:check-delivery-alerts';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for job orders with delivery date in 2 days and send notifications to department admins';

    /**
     * Execute the console command.
     *
     * This command runs daily via scheduler to check for upcoming deliveries
     * Sends Pusher notifications to admin of departments associated with job orders
     *
     * Business Logic:
     * - Query job orders where delivery_date = today + 2 days
     * - For each job order, broadcast alert to all departments (primary + additional)
     * - Notifications go to department-specific channels for targeted alerts
     */
    public function handle()
    {
        $this->info('Checking for delivery date alerts...');

        // Calculate target delivery date (H-2)
        $targetDeliveryDate = Carbon::today()->addDays(2)->format('Y-m-d');

        $this->info("Target delivery date (H-2): {$targetDeliveryDate}");

        // Query job orders with delivery_date = H-2
        $jobOrders = JobOrder::with(['departments', 'department', 'project'])
            ->whereDate('delivery_date', $targetDeliveryDate)
            ->get();

        if ($jobOrders->isEmpty()) {
            $this->info('No job orders found with delivery date in 2 days.');
            return 0;
        }

        $this->info("Found {$jobOrders->count()} job order(s) with delivery in 2 days:");

        $alertCount = 0;

        foreach ($jobOrders as $jobOrder) {
            $this->info("  - {$jobOrder->name} (Delivery: {$jobOrder->delivery_date->format('Y-m-d')})");

            // Get all departments (primary + additional from pivot)
            $allDepartments = collect([$jobOrder->department])
                ->merge($jobOrder->departments)
                ->filter()
                ->unique('id');

            $deptNames = $allDepartments->pluck('name')->implode(', ');
            $this->info("    Departments: {$deptNames}");

            // Log alert
            Log::info('Job Order delivery alert triggered (H-2)', [
                'job_order_id' => $jobOrder->id,
                'job_order_name' => $jobOrder->name,
                'delivery_date' => $jobOrder->delivery_date->format('Y-m-d'),
                'days_until_delivery' => 2,
                'departments' => $allDepartments->pluck('id')->toArray(),
            ]);

            // Trigger Pusher notification to all departments
            event(new JobOrderCountdownAlert($jobOrder));

            $alertCount++;
            $this->info("    ✓ Alert sent to {$allDepartments->count()} department(s)");
        }

        $this->info("✓ Completed: {$alertCount} alert(s) sent");

        return 0;
    }
}
