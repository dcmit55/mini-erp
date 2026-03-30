<?php

namespace App\Console\Commands\Timing;

use App\Services\Timing\TimingBreakService;
use Illuminate\Console\Command;

class AutoBreakPauseTimingCommand extends Command
{
    protected $signature   = 'timing:auto-break-pause';
    protected $description = 'Auto-freeze active timings during employee break window and unfreeze after break ends';

    public function handle(TimingBreakService $service): void
    {
        $service->run();
    }
}
