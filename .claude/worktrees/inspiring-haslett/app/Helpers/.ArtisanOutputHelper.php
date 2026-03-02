<?php

namespace App\Helpers;

use Illuminate\Console\Command;
use Symfony\Component\Console\Output\BufferedOutput;
use Illuminate\Support\Facades\Artisan;

class ArtisanOutputHelper
{
    /**
     * Execute Artisan command dan capture output
     */
    public static function execute(string $command): string
    {
        try {
            $output = new BufferedOutput();
            
            // Run command dengan BufferedOutput
            Artisan::call($command, [], $output);
            
            // Get captured output
            return $output->fetch();
        } catch (\Exception $e) {
            return "Error executing command: " . $e->getMessage();
        }
    }

    /**
     * Execute Lark command specifically
     */
    public static function executeLarkSync(): string
    {
        return self::execute('lark:fetch-job-orders');
    }
}