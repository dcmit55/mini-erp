<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Admin\User;

class SyncUsersToSpatie extends Command
{
    protected $signature   = 'spatie:sync-users';
    protected $description = 'Sync kolom role lama di tabel users ke Spatie roles';

    /**
     * Mapping kolom role lama → nama Spatie role.
     * Jika role lama tidak ada di map, dilewati dan dicatat sebagai warning.
     */
    private array $roleMap = [
        'super_admin'       => 'super_admin',
        'admin'             => 'admin',
        'admin_hr'          => 'admin_hr',
        'admin_logistic'    => 'admin_logistic',
        'admin_finance'     => 'admin_finance',
        'admin_procurement' => 'admin_procurement',
        'admin_mascot'      => 'admin_mascot',
        'admin_costume'     => 'admin_costume',
        'admin_animatronic' => 'admin_animatronic',
        'timing'            => 'timing',
        'general'           => 'general',
    ];

    public function handle(): int
    {
        $users   = User::withoutGlobalScopes()->get();
        $synced  = 0;
        $skipped = 0;

        foreach ($users as $user) {
            $oldRole = $user->role;

            if (!isset($this->roleMap[$oldRole])) {
                $this->warn("  [SKIP] User #{$user->id} ({$user->username}) — role '{$oldRole}' tidak ada di map.");
                $skipped++;
                continue;
            }

            $spatieRole = $this->roleMap[$oldRole];

            // Sync: hapus semua role lama lalu assign role baru
            $user->syncRoles([$spatieRole]);
            $this->line("  [OK]   User #{$user->id} ({$user->username}) → {$spatieRole}");
            $synced++;
        }

        $this->newLine();
        $this->info("Selesai: {$synced} user di-sync, {$skipped} dilewati.");

        return self::SUCCESS;
    }
}
