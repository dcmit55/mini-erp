<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ApiToken;

class RevokeApiToken extends Command
{
    /**
     * Sintaks command
     */
    protected $signature = 'api:token:revoke {id : ID token yang akan dinonaktifkan}';

    /**
     * Deskripsi command
     */
    protected $description = 'Nonaktifkan (revoke) API token berdasarkan ID';

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

        // Cek apakah sudah inactive
        if (!$token->is_active) {
            $this->warn(" Token '{$token->name}' sudah dalam status inactive.");
            return self::FAILURE;
        }

        // Tampilkan info token
        $this->info(' Token yang akan dinonaktifkan:');
        $this->table(
            ['Field', 'Value'],
            [
                ['ID', $token->id],
                ['Name', $token->name],
                ['Status', ' Active'],
                ['Last Used', $token->last_used_at?->diffForHumans() ?? 'Never'],
                ['Created', $token->created_at->format('d M Y H:i')],
            ]
        );

        // Konfirmasi
        if (!$this->confirm('Apakah Anda yakin ingin menonaktifkan token ini?', false)) {
            $this->warn(' Revoke dibatalkan.');
            return self::FAILURE;
        }

        // Revoke token
        if ($token->revoke()) {
            $this->newLine();
            $this->info("Token '{$token->name}' berhasil dinonaktifkan.");
            $this->newLine();
            $this->comment('💡 Token masih ada di database tapi tidak bisa digunakan.');
            $this->comment('💡 Gunakan: php artisan api:token:activate ' . $token->id . ' untuk mengaktifkan kembali.');
            return self::SUCCESS;
        }

        $this->error(' Gagal menonaktifkan token.');
        return self::FAILURE;
    }
}