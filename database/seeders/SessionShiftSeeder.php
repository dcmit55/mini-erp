<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Default (department_id = NULL) — berlaku untuk semua dept yang tidak punya shift spesifik:
 *   A9  : 08:00–17:00 | WNI | break 12:00–13:00              | detect 07:00–09:30
 *
 * DCM Mascot (department_id = 2):
 *   A9  : 08:00–17:00 | WNI | break 12:00–13:00              | detect 07:00–09:30
 *   B9  : 13:00–22:00 | WNI | break 18:00–19:00              | detect 12:00–14:00
 *   A12 : 08:00–20:00 | WNA | break 12:00–13:00, 18:00–18:30 | detect 07:00–09:30
 *   B12 : 10:00–22:00 | WNA | break 12:00–13:00, 18:00–18:30 | detect 09:30–12:00
 *
 * DCM Costume (department_id = 1):
 *   C8  : 08:00–16:00 | WNI | break 11:30–12:00              | detect 07:00–09:30
 */
class SessionShiftSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            // Default — semua department yang tidak punya shift spesifik
            ['department_id' => null, 'type_of_shift' => 'A9', 'start_time' => '08:00:00', 'end_time' => '17:00:00', 'break_start' => '12:00:00', 'break_end' => '13:00:00', 'break2_start' => null, 'break2_end' => null, 'for_wna' => false, 'detect_from' => '07:00:00', 'detect_until' => '09:30:00'],

            // DCM Mascot
            ['department_id' => 2, 'type_of_shift' => 'A9',  'start_time' => '08:00:00', 'end_time' => '17:00:00', 'break_start' => '12:00:00', 'break_end' => '13:00:00', 'break2_start' => null,        'break2_end' => null,        'for_wna' => false, 'detect_from' => '07:00:00', 'detect_until' => '09:30:00'],
            ['department_id' => 2, 'type_of_shift' => 'B9',  'start_time' => '13:00:00', 'end_time' => '22:00:00', 'break_start' => '18:00:00', 'break_end' => '19:00:00', 'break2_start' => null,        'break2_end' => null,        'for_wna' => false, 'detect_from' => '12:00:00', 'detect_until' => '14:00:00'],
            ['department_id' => 2, 'type_of_shift' => 'A12', 'start_time' => '08:00:00', 'end_time' => '20:00:00', 'break_start' => '12:00:00', 'break_end' => '13:00:00', 'break2_start' => '18:00:00', 'break2_end' => '18:30:00', 'for_wna' => true,  'detect_from' => '07:00:00', 'detect_until' => '09:30:00'],
            ['department_id' => 2, 'type_of_shift' => 'B12', 'start_time' => '10:00:00', 'end_time' => '22:00:00', 'break_start' => '12:00:00', 'break_end' => '13:00:00', 'break2_start' => '18:00:00', 'break2_end' => '18:30:00', 'for_wna' => true,  'detect_from' => '09:30:00', 'detect_until' => '12:00:00'],

            // DCM Costume
            ['department_id' => 1, 'type_of_shift' => 'C8',  'start_time' => '08:00:00', 'end_time' => '16:00:00', 'break_start' => '11:30:00', 'break_end' => '12:00:00', 'break2_start' => null,        'break2_end' => null,        'for_wna' => false, 'detect_from' => '07:00:00', 'detect_until' => '09:30:00'],
        ];

        foreach ($data as $shift) {
            $query = DB::table('session_shifts')
                ->where('type_of_shift', $shift['type_of_shift']);

            if (is_null($shift['department_id'])) {
                $query->whereNull('department_id');
            } else {
                $query->where('department_id', $shift['department_id']);
            }

            $payload = array_merge($shift, [
                'uid'        => Str::uuid(),
                'is_active'  => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if ($query->exists()) {
                $query->update(array_merge($payload, ['updated_at' => now()]));
            } else {
                DB::table('session_shifts')->insert($payload);
            }
        }
    }
}
