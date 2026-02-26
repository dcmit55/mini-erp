<?php

namespace App\Console\Commands;

use App\Services\LarkProductionSyncService;
use Illuminate\Console\Command;

class LarkSyncCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lark:sync 
                            {direction : Direction to sync (sg-bt or bt-sg)}
                            {--test : Run in test mode with sample data}
                            {--show-unmapped : Show unmapped projects}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync goods movements from Lark (production-grade with merge strategy)';

    protected $syncService;

    public function __construct(LarkProductionSyncService $syncService)
    {
        parent::__construct();
        $this->syncService = $syncService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $direction = $this->argument('direction');
        
        // Validate direction
        if (!in_array($direction, ['sg-bt', 'bt-sg'])) {
            $this->error('Invalid direction. Use: sg-bt or bt-sg');
            return 1;
        }
        
        // Show unmapped projects if requested
        if ($this->option('show-unmapped')) {
            $this->showUnmappedProjects();
            return 0;
        }
        
        // Run in test mode
        if ($this->option('test')) {
            $this->runTestSync($direction);
            return 0;
        }
        
        // Real sync from Lark API
        $this->info("Starting Lark sync from {$direction} direction...");
        $this->syncFromLark($direction);
        
        return 0;
    }
    
    /**
     * Run test sync with sample data
     */
    protected function runTestSync(string $direction)
    {
        $this->info("Running TEST sync for {$direction} direction...");
        
        $sampleData = $this->getSampleData($direction);
        
        $result = $direction === 'sg-bt' 
            ? $this->syncService->syncFromSgToBt($sampleData)
            : $this->syncService->syncFromBtToSg($sampleData);
        
        if ($result['success']) {
            $this->info("✓ Test sync successful!");
            $this->table(
                ['Field', 'Value'],
                [
                    ['Action', $result['action']],
                    ['Movement ID', $result['movement_id']],
                    ['Items Synced', $result['items_synced']],
                ]
            );
        } else {
            $this->error("✗ Test sync failed: {$result['error']}");
        }
    }
    
    /**
     * Sync from real Lark API
     */
    protected function syncFromLark(string $direction)
    {
        // TODO: Implement actual Lark API fetch
        // For now, show instructions
        
        $this->warn("Real Lark API sync not yet implemented.");
        $this->info("To implement:");
        $this->line("1. Add Lark API credentials to .env");
        $this->line("2. Implement fetchFromLarkApi() method");
        $this->line("3. Process each record from Lark");
        
        $this->newLine();
        $this->info("For now, use --test flag to test with sample data:");
        $this->line("  php artisan lark:sync sg-bt --test");
    }
    
    /**
     * Show unmapped projects
     */
    protected function showUnmappedProjects()
    {
        $unmapped = $this->syncService->getUnmappedProjects();
        
        if ($unmapped->isEmpty()) {
            $this->info("✓ All projects are mapped!");
            return;
        }
        
        $this->warn("Found {$unmapped->count()} unmapped projects from Lark:");
        $this->table(
            ['#', 'Project Name (from Lark)'],
            $unmapped->map(fn($name, $idx) => [$idx + 1, $name])
        );
        
        $this->newLine();
        $this->info("To map manually, use:");
        $this->line("  \$service->manualMapProject('Lark Project Name', \$projectId);");
    }
    
    /**
     * Get sample test data
     */
    protected function getSampleData(string $direction): array
    {
        if ($direction === 'sg-bt') {
            return [
                'record_id' => 'LARK_SG_TEST_001',
                'courier_id' => 'COURIER_123',
                'courier_name' => 'Soon Brothers',
                'movement_type' => 'Soon Brothers',
                'date' => '2026-02-26',
                'origin' => 'Singapore',
                'destination' => 'Batam',
                'sender' => 'SG Warehouse',
                'receiver' => 'BT Office',
                'status' => 'Shipped',
                'transport_cost' => 150.00,
                'baggage_cost' => 25.00,
                'gst_cost' => 12.25,
                'qty_total' => 50,
                'notes' => 'Test shipment from Lark SG-BT',
                'items' => [
                    [
                        'item_record_id' => 'ITEM_001',
                        'item_name' => 'Steel Beam 3m',
                        'quantity' => 25,
                        'unit' => 'pcs',
                        'sgd_cost' => 375.00,
                        'project' => 'Fashion Show Gown',
                        'status' => 'Shipped',
                        'notes' => 'For main structure',
                    ],
                    [
                        'item_record_id' => 'ITEM_002',
                        'item_name' => 'Aluminum Sheet',
                        'quantity' => 25,
                        'unit' => 'pcs',
                        'sgd_cost' => 250.00,
                        'project' => 'Fashion Show Gown',
                        'status' => 'Shipped',
                        'notes' => 'For facade',
                    ],
                ],
            ];
        } else {
            return [
                'record_id' => 'LARK_BT_TEST_001',
                'courier_id' => 'COURIER_456',
                'courier_name' => 'Sindo Package',
                'movement_type' => 'Sindo Package',
                'date' => '2026-02-25',
                'origin' => 'Batam',
                'destination' => 'Singapore',
                'sender' => 'BT Workshop',
                'receiver' => 'SG Client',
                'status' => 'Batam Received',
                'transport_cost' => 120.00,
                'baggage_cost' => 20.00,
                'gst_cost' => 9.80,
                'qty_total' => 30,
                'notes' => 'Test shipment from Lark BT-SG',
                'items' => [
                    [
                        'item_record_id' => 'ITEM_BT_001',
                        'item_name' => 'Finished Product A',
                        'quantity' => 30,
                        'unit' => 'pcs',
                        'sgd_cost' => 900.00,
                        'project' => 'Mascot Project',
                        'status' => 'Ready to Ship',
                        'notes' => 'Completed items',
                    ],
                ],
            ];
        }
    }
}

