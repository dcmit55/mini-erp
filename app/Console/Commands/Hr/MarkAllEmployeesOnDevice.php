<?php

namespace App\Console\Commands\Hr;

use App\Models\Hr\Employee;
use Illuminate\Console\Command;

class MarkAllEmployeesOnDevice extends Command
{
    protected $signature = 'fingerspot:mark-all-on-device
                            {--dry-run : Tampilkan daftar tanpa mengubah data}
                            {--only-active : Hanya employee dengan status active (default)}';

    protected $description = 'Tandai semua employee aktif sebagai terdaftar di device fingerspot (isi device_registered_at).
                              Gunakan setelah konfirmasi bahwa semua employee memang sudah terdaftar di mesin.';

    public function handle(): int
    {
        $query = Employee::active()
            ->whereNull('device_registered_at')
            ->orderBy('employee_no');

        $count = $query->count();

        if ($count === 0) {
            $this->info('Semua employee aktif sudah memiliki device_registered_at. Tidak ada yang perlu diupdate.');
            return self::SUCCESS;
        }

        $this->info("Ditemukan {$count} employee aktif yang belum memiliki device_registered_at:");
        $this->table(
            ['Employee No', 'Name'],
            $query->get(['employee_no', 'name'])->map(fn($e) => [$e->employee_no, $e->name])->toArray()
        );

        if ($this->option('dry-run')) {
            $this->warn('--dry-run aktif. Tidak ada perubahan yang dilakukan.');
            return self::SUCCESS;
        }

        if (!$this->confirm("Tandai {$count} employee ini sebagai terdaftar di device? (device_registered_at = now())")) {
            $this->warn('Dibatalkan.');
            return self::SUCCESS;
        }

        $updated = Employee::where('status', 'active')
            ->whereNull('device_registered_at')
            ->update(['device_registered_at' => now()]);

        $this->info("✓ {$updated} employee berhasil ditandai sebagai terdaftar di device.");
        return self::SUCCESS;
    }
}
