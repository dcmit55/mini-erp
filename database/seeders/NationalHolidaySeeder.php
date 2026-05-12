<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class NationalHolidaySeeder extends Seeder
{
    public function run(): void
    {
        // Libur Nasional Indonesia (is_joint_leave=false) dan Cuti Bersama (is_joint_leave=true)
        // Sumber: SKB 3 Menteri Pemerintah Indonesia
        // Catatan: Tanggal hari raya Islam bersifat perkiraan, menyesuaikan penetapan pemerintah

        $holidays = [
            // =========== 2025 ===========
            ['date' => '2025-01-01', 'name' => 'Tahun Baru Masehi 2025',          'year' => 2025, 'is_joint_leave' => false],
            ['date' => '2025-01-27', 'name' => 'Cuti Bersama Isra Mi\'raj',        'year' => 2025, 'is_joint_leave' => true],
            ['date' => '2025-01-28', 'name' => 'Isra Mi\'raj Nabi Muhammad 1446H', 'year' => 2025, 'is_joint_leave' => false],
            ['date' => '2025-01-29', 'name' => 'Cuti Bersama Isra Mi\'raj',        'year' => 2025, 'is_joint_leave' => true],
            ['date' => '2025-02-05', 'name' => 'Tahun Baru Imlek 2576',            'year' => 2025, 'is_joint_leave' => false],
            ['date' => '2025-03-28', 'name' => 'Cuti Bersama Nyepi',               'year' => 2025, 'is_joint_leave' => true],
            ['date' => '2025-03-29', 'name' => 'Hari Suci Nyepi (Tahun Baru Saka 1947)', 'year' => 2025, 'is_joint_leave' => false],
            ['date' => '2025-03-31', 'name' => 'Hari Raya Idul Fitri 1446H',       'year' => 2025, 'is_joint_leave' => false],
            ['date' => '2025-04-01', 'name' => 'Hari Raya Idul Fitri 1446H (Hari ke-2)', 'year' => 2025, 'is_joint_leave' => false],
            ['date' => '2025-04-02', 'name' => 'Cuti Bersama Idul Fitri',          'year' => 2025, 'is_joint_leave' => true],
            ['date' => '2025-04-03', 'name' => 'Cuti Bersama Idul Fitri',          'year' => 2025, 'is_joint_leave' => true],
            ['date' => '2025-04-04', 'name' => 'Cuti Bersama Idul Fitri',          'year' => 2025, 'is_joint_leave' => true],
            ['date' => '2025-04-07', 'name' => 'Cuti Bersama Idul Fitri',          'year' => 2025, 'is_joint_leave' => true],
            ['date' => '2025-04-18', 'name' => 'Wafat Isa Al Masih',               'year' => 2025, 'is_joint_leave' => false],
            ['date' => '2025-05-01', 'name' => 'Hari Buruh Internasional',         'year' => 2025, 'is_joint_leave' => false],
            ['date' => '2025-05-12', 'name' => 'Hari Raya Waisak 2569',            'year' => 2025, 'is_joint_leave' => false],
            ['date' => '2025-05-13', 'name' => 'Cuti Bersama Waisak',              'year' => 2025, 'is_joint_leave' => true],
            ['date' => '2025-05-29', 'name' => 'Kenaikan Isa Al Masih',            'year' => 2025, 'is_joint_leave' => false],
            ['date' => '2025-06-01', 'name' => 'Hari Lahir Pancasila',             'year' => 2025, 'is_joint_leave' => false],
            ['date' => '2025-06-06', 'name' => 'Hari Raya Idul Adha 1446H',        'year' => 2025, 'is_joint_leave' => false],
            ['date' => '2025-06-27', 'name' => 'Tahun Baru Islam 1447H',           'year' => 2025, 'is_joint_leave' => false],
            ['date' => '2025-08-17', 'name' => 'HUT Kemerdekaan RI ke-80',         'year' => 2025, 'is_joint_leave' => false],
            ['date' => '2025-09-05', 'name' => 'Maulid Nabi Muhammad SAW 1447H',   'year' => 2025, 'is_joint_leave' => false],
            ['date' => '2025-12-25', 'name' => 'Hari Raya Natal',                  'year' => 2025, 'is_joint_leave' => false],
            ['date' => '2025-12-26', 'name' => 'Cuti Bersama Natal',               'year' => 2025, 'is_joint_leave' => true],

            // =========== 2026 ===========
            // Catatan: Tanggal hari raya Islam 2026 bersifat perkiraan
            ['date' => '2026-01-01', 'name' => 'Tahun Baru Masehi 2026',           'year' => 2026, 'is_joint_leave' => false],
            ['date' => '2026-01-17', 'name' => 'Tahun Baru Imlek 2577',            'year' => 2026, 'is_joint_leave' => false],
            ['date' => '2026-03-05', 'name' => 'Isra Mi\'raj Nabi Muhammad 1447H', 'year' => 2026, 'is_joint_leave' => false],
            ['date' => '2026-03-19', 'name' => 'Hari Suci Nyepi (Tahun Baru Saka 1948)', 'year' => 2026, 'is_joint_leave' => false],
            ['date' => '2026-03-20', 'name' => 'Hari Raya Idul Fitri 1447H',       'year' => 2026, 'is_joint_leave' => false],
            ['date' => '2026-03-21', 'name' => 'Hari Raya Idul Fitri 1447H (Hari ke-2)', 'year' => 2026, 'is_joint_leave' => false],
            ['date' => '2026-04-03', 'name' => 'Wafat Isa Al Masih',               'year' => 2026, 'is_joint_leave' => false],
            ['date' => '2026-05-01', 'name' => 'Hari Buruh Internasional',         'year' => 2026, 'is_joint_leave' => false],
            ['date' => '2026-05-14', 'name' => 'Kenaikan Isa Al Masih',            'year' => 2026, 'is_joint_leave' => false],
            ['date' => '2026-05-27', 'name' => 'Hari Raya Idul Adha 1447H',        'year' => 2026, 'is_joint_leave' => false],
            ['date' => '2026-05-31', 'name' => 'Hari Raya Waisak 2570',            'year' => 2026, 'is_joint_leave' => false],
            ['date' => '2026-06-01', 'name' => 'Hari Lahir Pancasila',             'year' => 2026, 'is_joint_leave' => false],
            ['date' => '2026-06-16', 'name' => 'Tahun Baru Islam 1448H',           'year' => 2026, 'is_joint_leave' => false],
            ['date' => '2026-08-17', 'name' => 'HUT Kemerdekaan RI ke-81',         'year' => 2026, 'is_joint_leave' => false],
            ['date' => '2026-08-25', 'name' => 'Maulid Nabi Muhammad SAW 1448H',   'year' => 2026, 'is_joint_leave' => false],
            ['date' => '2026-12-25', 'name' => 'Hari Raya Natal',                  'year' => 2026, 'is_joint_leave' => false],
        ];

        DB::table('national_holidays')->insertOrIgnore($holidays);
    }
}
