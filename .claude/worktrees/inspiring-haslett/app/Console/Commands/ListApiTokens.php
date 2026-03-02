<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ApiToken;

class ListApiTokens extends Command
{
    /**
     * Sintaks command
     */
    protected $signature = 'api:token:list';

    /**
     * Deskripsi command
     */
    protected $description = 'Tampilkan semua API tokens yang terdaftar';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // Ambil semua tokens
        $tokens = ApiToken::orderBy('created_at', 'desc')->get();

        // Cek jika tidak ada token
        if ($tokens->isEmpty()) {
            $this->warn('  Tidak ada token yang terdaftar.');
            $this->newLine();
            $this->comment(' Buat token baru: php artisan api:token:generate "Nama App"');
            return self::SUCCESS;
        }

        // Header
        $this->info(' Daftar API Tokens:');
        $this->newLine();

        // Format data untuk table
        $data = $tokens->map(function($token) {
            return [
                $token->id,
                $token->name,
                $token->is_active ? 'Active' : ' Inactive',
                $token->allowed_ips ?? 'All IPs',
                $token->last_used_at?->diffForHumans() ?? 'Never',
                $token->created_at->format('d M Y'),
            ];
        });

        // Tampilkan table
        $this->table(
            ['ID', 'Name', 'Status', 'Allowed IPs', 'Last Used', 'Created'],
            $data
        );

        $this->newLine();

        // Footer hints
        $this->comment(' Generate token baru: php artisan api:token:generate "Nama"');
        $this->comment(' Revoke token: php artisan api:token:revoke {id}');
        $this->comment(' Activate token: php artisan api:token:activate {id}');

        return self::SUCCESS;
    }
}