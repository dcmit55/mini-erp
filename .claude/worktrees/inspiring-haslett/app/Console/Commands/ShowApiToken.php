<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ApiToken;

class ShowApiToken extends Command
{
    protected $signature = 'api:token:show {id}';
    protected $description = 'Show API token by ID (use with caution!)';

    public function handle(): int
    {
        $token = ApiToken::find($this->argument('id'));

        if (!$token) {
            $this->error('❌ Token not found!');
            return self::FAILURE;
        }

        // Confirmation untuk keamanan
        if (!$this->confirm('⚠️  WARNING: This will display the full token. Continue?', false)) {
            $this->warn('Cancelled.');
            return self::FAILURE;
        }

        $this->newLine();
        $this->info('🔐 API Token Details:');
        $this->table(
            ['Field', 'Value'],
            [
                ['ID', $token->id],
                ['Name', $token->name],
                ['Token', $token->token], // Full token shown here
                ['Status', $token->is_active ? '🟢 Active' : '🔴 Inactive'],
                ['Allowed IPs', $token->allowed_ips ?? 'All IPs'],
                ['Last Used', $token->last_used_at?->diffForHumans() ?? 'Never'],
                ['Created', $token->created_at->format('d M Y H:i:s')],
            ]
        );

        $this->newLine();
        $this->warn('⚠️  Keep this token secure! Do not share via insecure channels.');

        return self::SUCCESS;
    }
}