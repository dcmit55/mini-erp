<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ApiToken;

class ActivateApiToken extends Command
{
    /**
     * Sintaks command
     */
    protected $signature = 'api:token:activate {id : ID token yang akan diaktifkan}';

    /**
     * Deskripsi command
     */
    protected $description = 'Aktifkan kembali API token yang sudah di-revoke';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $tokenId = $this->argument('id');

        // Cari token
        $token = ApiToken::find($tokenId);

        if (!$token) {
            $this->error(" Token dengan ID {$tokenId} tidak ditemukan!");
            $this->newLine();
            $this->comment(' Gunakan: php artisan api:token:list untuk melihat semua token');
            return self::FAILURE;
        }

        // Cek apakah sudah active
        if ($token->is_active) {
            $this->warn("  Token '{$token->name}' sudah dalam status active.");
            return self::SUCCESS;
        }

        // Tampilkan info token
        $this->info(' Token yang akan diaktifkan:');
        $this->table(
            ['Field', 'Value'],
            [
                ['ID', $token->id],
                ['Name', $token->name],
                ['Status', ' Inactive'],
                ['Last Used', $token->last_used_at?->diffForHumans() ?? 'Never'],
                ['Created', $token->created_at->format('d M Y H:i')],
            ]
        );

        // Konfirmasi
        if (!$this->confirm('Apakah Anda yakin ingin mengaktifkan kembali token ini?', true)) {
            $this->warn(' Activate dibatalkan.');
            return self::FAILURE;
        }

        // Activate token
        if ($token->activate()) {
            $this->newLine();
            $this->info(" Token '{$token->name}' berhasil diaktifkan kembali.");
            $this->newLine();
            $this->comment(' Token sekarang sudah bisa digunakan kembali.');
            return self::SUCCESS;
        }

        $this->error(' Gagal mengaktifkan token.');
        return self::FAILURE;
    }
}