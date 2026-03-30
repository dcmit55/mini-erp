<?php

namespace Database\Seeders;

use App\Models\Hr\ApprovalMatrix;
use Illuminate\Database\Seeder;

class ApprovalMatrixSeeder extends Seeder
{
    /**
     * Struktur approval perusahaan:
     *
     *  LEAVE    Level 1 → admin_mascot  (Dept Admin — delegate: admin_logistic, admin_costume)
     *           Level 2 → admin_hr      (HR)
     *           Level 3 → director      (Director — delegate: admin_hr)
     *
     *  OVERTIME Level 1 → admin_hr      (HR)
     *           Level 2 → director      (Director — delegate: admin_hr)
     *
     * Menggunakan updateOrCreate agar aman dijalankan berulang kali.
     * Role disesuaikan dengan nilai users.role yang ada di sistem.
     */
    public function run(): void
    {
        $matrix = [
            // LEAVE
            // Level 1: Dept admin approve per department
            ['module' => 'leave',    'level' => 1, 'role' => 'admin_mascot', 'delegate_roles' => ['admin_logistic', 'admin_costume']],
            // Level 2: HR approve
            ['module' => 'leave',    'level' => 2, 'role' => 'admin_hr',     'delegate_roles' => null],
            // Level 3: Director approve, admin_hr bisa menggantikan jika director berhalangan
            ['module' => 'leave',    'level' => 3, 'role' => 'director',     'delegate_roles' => ['admin_hr']],

            // OVERTIME
            // Level 1: HR approve utama
            ['module' => 'overtime', 'level' => 1, 'role' => 'admin_hr',     'delegate_roles' => null],
            // Level 2: Director approve utama, admin_hr bisa menggantikan jika director berhalangan
            ['module' => 'overtime', 'level' => 2, 'role' => 'director',     'delegate_roles' => ['admin_hr']],
        ];

        foreach ($matrix as $entry) {
            ApprovalMatrix::updateOrCreate(
                [
                    'module' => $entry['module'],
                    'level'  => $entry['level'],
                ],
                [
                    'role'           => $entry['role'],
                    'delegate_roles' => $entry['delegate_roles'],
                ]
            );
        }

        $this->command->info('ApprovalMatrix seeded: ' . count($matrix) . ' rules.');
    }
}
