<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ApiToken;

class GenerateApiToken extends Command
{
    /**
     * The name and signature of the console command.
     *
     * Sintaks: php artisan api:token:generate {nama_token} {--ip=192.168.1.1}
     */
    protected $signature = 'api:token:generate 
                            {name : Nama/deskripsi token (contoh: BotTime App)} 
                            {--ip=* : IP yang diizinkan (opsional, bisa multiple)}';

    /**
     * The console command description.
     */
    protected $description = 'Generate static API token untuk server-to-server authentication';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // Ambil input dari command
        $name = $this->argument('name');
        $allowedIps = $this->option('ip') ?: null;

        // Konfirmasi sebelum generate
        $this->info(' Generating new API Token...');
        $this->newLine();

        if (!$this->confirm('Apakah Anda yakin ingin generate token baru?', true)) {
            $this->warn(' Token generation cancelled.');
            return self::FAILURE;
        }

        try {
            // Generate token via Model
            $token = ApiToken::createToken($name, $allowedIps);

            // Success output
            $this->newLine();
            $this->info('API Token Created Successfully!');
            $this->newLine();

            // Tampilkan detail dalam tabel
            $this->table(
                ['Field', 'Value'],
                [
                    ['ID', $token->id],
                    ['Name', $token->name],
                    ['Token', $this->formatToken($token->token)],
                    ['Allowed IPs', $token->allowed_ips ?? 'All IPs (no restriction)'],
                    ['Status', $token->is_active ? '🟢 Active' : '🔴 Inactive'],
                    ['Created At', $token->created_at->format('d M Y H:i:s')],
                ]
            );

            $this->newLine();
            $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
            $this->warn('  Important: Save this token now!');
            $this->warn('  Token: ' . $token->token);
            $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
            $this->newLine();

            // Contoh penggunaan
            $this->comment(' Example usage in vanilla PHP application:');
            $this->line('');
            $this->line('  curl -H "X-API-TOKEN: ' . $token->token . '" \\');
            $this->line('       https://yourdomain.com/api/v1/projects');
            $this->newLine();

            return self::SUCCESS;       

        } catch (\Exception $e) {
            $this->error(' Error: ' . $e->getMessage());
            return self::FAILURE;
        }
    }

    /**
     * Format token untuk display (potong jika terlalu panjang)
     */
    private function formatToken(string $token): string
    {
        if (strlen($token) > 40) {
            return substr($token, 0, 20) . '...' . substr($token, -20);
        }
        return $token;
    }
}