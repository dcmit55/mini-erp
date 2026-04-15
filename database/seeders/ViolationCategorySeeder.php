<?php

namespace Database\Seeders;

use App\Models\Hr\ViolationCategory;
use Illuminate\Database\Seeder;

class ViolationCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['code' => 'LATE',         'name' => 'Keterlambatan',              'can_bulk_issue' => false, 'severity' => 'low'],
            ['code' => 'ABSENT',       'name' => 'Alpha / Tidak Hadir',        'can_bulk_issue' => false, 'severity' => 'medium'],
            ['code' => 'EARLY_LEAVE',  'name' => 'Pulang Lebih Cepat',         'can_bulk_issue' => false, 'severity' => 'low'],
            ['code' => 'MISCONDUCT',   'name' => 'Pelanggaran Tata Tertib',    'can_bulk_issue' => true,  'severity' => 'high'],
            ['code' => 'PERFORMANCE',  'name' => 'Performa Tidak Memenuhi KPI','can_bulk_issue' => true,  'severity' => 'medium'],
            ['code' => 'PROJECT_LOSS', 'name' => 'Kerugian Proyek',            'can_bulk_issue' => true,  'severity' => 'critical'],
            ['code' => 'FRAUD',        'name' => 'Kecurangan / Fraud',         'can_bulk_issue' => false, 'severity' => 'critical'],
            ['code' => 'HARASSMENT',   'name' => 'Pelecehan / Harassment',     'can_bulk_issue' => false, 'severity' => 'critical'],
            ['code' => 'POLICY',       'name' => 'Pelanggaran Kebijakan',      'can_bulk_issue' => true,  'severity' => 'medium'],
            ['code' => 'CUSTOM',       'name' => 'Lainnya (Custom)',           'can_bulk_issue' => true,  'severity' => 'medium'],
        ];

        foreach ($categories as $cat) {
            ViolationCategory::updateOrCreate(
                ['code' => $cat['code']],
                array_merge($cat, ['is_active' => true])
            );
        }

        $this->command->info('ViolationCategory seeded: ' . count($categories) . ' records.');
    }
}
