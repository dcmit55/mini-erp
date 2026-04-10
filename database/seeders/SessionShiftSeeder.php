<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Session Shift Seeder
 *
 * Prioritas deteksi (lihat SessionShift::detectFromClockIn):
 *   1. employee_id spesifik
 *   2. position_keywords dalam department
 *   3. department tanpa filter posisi
 *   4. default (department_id = NULL)
 *
 * applicable_days: null = semua hari | [1,2,3,4,5] = Sen-Jum | [6] = Sabtu
 * position_keywords: null = semua posisi | ["operator","sewing"] = substring match
 */
class SessionShiftSeeder extends Seeder
{
    public function run(): void
    {
        // Lookup department IDs by name
        $dept = DB::table('departments')->pluck('id', 'name');

        $costume      = $dept['DCM Costume']      ?? null;
        $mascot       = $dept['DCM Mascot']       ?? null;
        $animatronics = $dept['DCM Animatronics'] ?? null;
        $finance      = $dept['Finance']          ?? null;

        $store    = $dept['Logistic'] ?? null;
        $hrga     = $dept['HRGA']    ?? null;
        $chef     = $hrga;
        $security = $hrga;
        $cs       = $hrga;

        // Lookup Emilia's employee ID
        $emiliaId = DB::table('employees')
            ->where('name', 'like', '%emilia%')
            ->value('id');

        $shifts = [

            // ================================================================
            // DEFAULT — fallback untuk semua department yang tidak punya shift spesifik
            // ================================================================
            [
                'department_id'     => null,
                'employee_id'       => null,
                'type_of_shift'     => 'A9',
                'start_time'        => '08:00:00',
                'end_time'          => '17:00:00',
                'break_start'       => '12:00:00',
                'break_end'         => '13:00:00',
                'break2_start'      => null,
                'break2_end'        => null,
                'for_wna'           => false,
                'detect_from'       => '07:00:00',
                'detect_until'      => '09:30:00',
                'applicable_days'   => null,
                'position_keywords' => null,
            ],

            // ================================================================
            // DCM COSTUME
            // ================================================================

            // Operator & Sewing: 08:00–16:00 (pulang lebih awal)
            [
                'department_id'     => $costume,
                'employee_id'       => null,
                'type_of_shift'     => 'C8',
                'start_time'        => '08:00:00',
                'end_time'          => '16:00:00',
                'break_start'       => '11:30:00',
                'break_end'         => '12:00:00',
                'break2_start'      => null,
                'break2_end'        => null,
                'for_wna'           => false,
                'detect_from'       => '07:00:00',
                'detect_until'      => '09:30:00',
                'applicable_days'   => null,
                'position_keywords' => ['operator', 'sewing', 'sewer', 'embroidery', 'handstitching'],
            ],

            // Cutting, Leader, Finishing, Design: 08:00–17:00
            [
                'department_id'     => $costume,
                'employee_id'       => null,
                'type_of_shift'     => 'A9',
                'start_time'        => '08:00:00',
                'end_time'          => '17:00:00',
                'break_start'       => '12:00:00',
                'break_end'         => '13:00:00',
                'break2_start'      => null,
                'break2_end'        => null,
                'for_wna'           => false,
                'detect_from'       => '07:00:00',
                'detect_until'      => '09:30:00',
                'applicable_days'   => null,
                'position_keywords' => ['cutter', 'drafter', 'leader', 'lead', 'finishing', 'designer', 'packing'],
            ],

            // ================================================================
            // DCM MASCOT
            // ================================================================

            // Shift pagi: 08:00–17:00
            [
                'department_id'     => $mascot,
                'employee_id'       => null,
                'type_of_shift'     => 'A9',
                'start_time'        => '08:00:00',
                'end_time'          => '17:00:00',
                'break_start'       => '12:00:00',
                'break_end'         => '13:00:00',
                'break2_start'      => null,
                'break2_end'        => null,
                'for_wna'           => false,
                'detect_from'       => '07:00:00',
                'detect_until'      => '09:30:00',
                'applicable_days'   => null,
                'position_keywords' => null,
            ],

            // Shift siang: 10:00–19:00
            [
                'department_id'     => $mascot,
                'employee_id'       => null,
                'type_of_shift'     => 'M10',
                'start_time'        => '10:00:00',
                'end_time'          => '19:00:00',
                'break_start'       => '13:00:00',
                'break_end'         => '14:00:00',
                'break2_start'      => null,
                'break2_end'        => null,
                'for_wna'           => false,
                'detect_from'       => '09:30:00',
                'detect_until'      => '11:30:00',
                'applicable_days'   => null,
                'position_keywords' => null,
            ],

            // WNA Mascot A12: 08:00–20:00
            [
                'department_id'     => $mascot,
                'employee_id'       => null,
                'type_of_shift'     => 'A12',
                'start_time'        => '08:00:00',
                'end_time'          => '20:00:00',
                'break_start'       => '12:00:00',
                'break_end'         => '13:00:00',
                'break2_start'      => '18:00:00',
                'break2_end'        => '18:30:00',
                'for_wna'           => true,
                'detect_from'       => '07:00:00',
                'detect_until'      => '09:30:00',
                'applicable_days'   => null,
                'position_keywords' => null,
            ],

            // WNA Mascot B12: 10:00–22:00
            [
                'department_id'     => $mascot,
                'employee_id'       => null,
                'type_of_shift'     => 'B12',
                'start_time'        => '10:00:00',
                'end_time'          => '22:00:00',
                'break_start'       => '12:00:00',
                'break_end'         => '13:00:00',
                'break2_start'      => '18:00:00',
                'break2_end'        => '18:30:00',
                'for_wna'           => true,
                'detect_from'       => '09:30:00',
                'detect_until'      => '12:00:00',
                'applicable_days'   => null,
                'position_keywords' => null,
            ],

            // ================================================================
            // STORE
            // ================================================================

            // Pagi: 08:00–17:00
            [
                'department_id'     => $store,
                'employee_id'       => null,
                'type_of_shift'     => 'A9',
                'start_time'        => '08:00:00',
                'end_time'          => '17:00:00',
                'break_start'       => '12:00:00',
                'break_end'         => '13:00:00',
                'break2_start'      => null,
                'break2_end'        => null,
                'for_wna'           => false,
                'detect_from'       => '07:00:00',
                'detect_until'      => '09:30:00',
                'applicable_days'   => null,
                'position_keywords' => null,
            ],

            // Siang: 10:00–19:00
            [
                'department_id'     => $store,
                'employee_id'       => null,
                'type_of_shift'     => 'S10',
                'start_time'        => '10:00:00',
                'end_time'          => '19:00:00',
                'break_start'       => '13:00:00',
                'break_end'         => '14:00:00',
                'break2_start'      => null,
                'break2_end'        => null,
                'for_wna'           => false,
                'detect_from'       => '09:30:00',
                'detect_until'      => '11:30:00',
                'applicable_days'   => null,
                'position_keywords' => null,
            ],

            // Sore: 13:00–22:00
            [
                'department_id'     => $store,
                'employee_id'       => null,
                'type_of_shift'     => 'S13',
                'start_time'        => '13:00:00',
                'end_time'          => '22:00:00',
                'break_start'       => '17:00:00',
                'break_end'         => '18:00:00',
                'break2_start'      => null,
                'break2_end'        => null,
                'for_wna'           => false,
                'detect_from'       => '11:30:00',
                'detect_until'      => '14:00:00',
                'applicable_days'   => null,
                'position_keywords' => null,
            ],

            // ================================================================
            // CHEF — Senin–Jumat
            // ================================================================
            [
                'department_id'     => $chef,
                'employee_id'       => null,
                'type_of_shift'     => 'CH6',
                'start_time'        => '06:00:00',
                'end_time'          => '15:00:00',
                'break_start'       => '11:00:00',
                'break_end'         => '12:00:00',
                'break2_start'      => null,
                'break2_end'        => null,
                'for_wna'           => false,
                'detect_from'       => '04:00:00',
                'detect_until'      => '07:00:00',
                'applicable_days'   => [1, 2, 3, 4, 5],
                'position_keywords' => ['chef'],
            ],

            // CHEF — Sabtu
            [
                'department_id'     => $chef,
                'employee_id'       => null,
                'type_of_shift'     => 'CH6S',
                'start_time'        => '06:00:00',
                'end_time'          => '12:00:00',
                'break_start'       => null,
                'break_end'         => null,
                'break2_start'      => null,
                'break2_end'        => null,
                'for_wna'           => false,
                'detect_from'       => '04:00:00',
                'detect_until'      => '07:00:00',
                'applicable_days'   => [6],
                'position_keywords' => ['chef'],
            ],

            // ================================================================
            // SECURITY — Senin–Jumat
            // ================================================================
            [
                'department_id'     => $security,
                'employee_id'       => null,
                'type_of_shift'     => 'SEC',
                'start_time'        => '08:00:00',
                'end_time'          => '22:00:00',
                'break_start'       => '12:00:00',
                'break_end'         => '13:00:00',
                'break2_start'      => '18:00:00',
                'break2_end'        => '19:00:00',
                'for_wna'           => false,
                'detect_from'       => '06:00:00',
                'detect_until'      => '09:00:00',
                'applicable_days'   => [1, 2, 3, 4, 5],
                'position_keywords' => ['security', 'securty'],
            ],

            // SECURITY — Sabtu
            [
                'department_id'     => $security,
                'employee_id'       => null,
                'type_of_shift'     => 'SECS',
                'start_time'        => '08:00:00',
                'end_time'          => '13:00:00',
                'break_start'       => null,
                'break_end'         => null,
                'break2_start'      => null,
                'break2_end'        => null,
                'for_wna'           => false,
                'detect_from'       => '06:00:00',
                'detect_until'      => '09:00:00',
                'applicable_days'   => [6],
                'position_keywords' => ['security', 'securty'],
            ],

            // ================================================================
            // CLEANING SERVICE — Senin–Jumat
            // ================================================================
            [
                'department_id'     => $cs,
                'employee_id'       => null,
                'type_of_shift'     => 'CS',
                'start_time'        => '08:00:00',
                'end_time'          => '22:00:00',
                'break_start'       => '12:00:00',
                'break_end'         => '13:00:00',
                'break2_start'      => '18:00:00',
                'break2_end'        => '19:00:00',
                'for_wna'           => false,
                'detect_from'       => '06:00:00',
                'detect_until'      => '09:00:00',
                'applicable_days'   => [1, 2, 3, 4, 5],
                'position_keywords' => ['cleaning', 'house keeping'],
            ],

            // CLEANING SERVICE — Sabtu
            [
                'department_id'     => $cs,
                'employee_id'       => null,
                'type_of_shift'     => 'CSS',
                'start_time'        => '08:00:00',
                'end_time'          => '13:00:00',
                'break_start'       => null,
                'break_end'         => null,
                'break2_start'      => null,
                'break2_end'        => null,
                'for_wna'           => false,
                'detect_from'       => '06:00:00',
                'detect_until'      => '09:00:00',
                'applicable_days'   => [6],
                'position_keywords' => ['cleaning', 'house keeping'],
            ],

        ];

        // ================================================================
        // EMILIA (Finance) — shift individual, hanya jika ditemukan di DB
        // ================================================================
        if ($emiliaId) {
            $shifts[] = [
                'department_id'     => $finance,
                'employee_id'       => $emiliaId,
                'type_of_shift'     => 'EM',
                'start_time'        => '08:30:00',
                'end_time'          => '17:30:00',
                'break_start'       => '12:00:00',
                'break_end'         => '13:00:00',
                'break2_start'      => null,
                'break2_end'        => null,
                'for_wna'           => false,
                'detect_from'       => '07:30:00',
                'detect_until'      => '09:30:00',
                'applicable_days'   => [1, 2, 3, 4, 5],
                'position_keywords' => null,
            ];
            $shifts[] = [
                'department_id'     => $finance,
                'employee_id'       => $emiliaId,
                'type_of_shift'     => 'EMS',
                'start_time'        => '08:30:00',
                'end_time'          => '13:30:00',
                'break_start'       => null,
                'break_end'         => null,
                'break2_start'      => null,
                'break2_end'        => null,
                'for_wna'           => false,
                'detect_from'       => '07:30:00',
                'detect_until'      => '09:30:00',
                'applicable_days'   => [6],
                'position_keywords' => null,
            ];
        }

        foreach ($shifts as $shift) {
            // Skip jika department tidak ditemukan di DB (dept belum dibuat)
            if (array_key_exists('department_id', $shift) && $shift['department_id'] === null && isset($shift['type_of_shift']) && $shift['type_of_shift'] !== 'A9') {
                // department_id null hanya boleh untuk default A9
            }

            $query = DB::table('session_shifts')
                ->where('type_of_shift', $shift['type_of_shift'])
                ->where('for_wna', $shift['for_wna']);

            if (is_null($shift['department_id'])) {
                $query->whereNull('department_id');
            } else {
                $query->where('department_id', $shift['department_id']);
            }

            if (!is_null($shift['employee_id'])) {
                $query->where('employee_id', $shift['employee_id']);
            } else {
                $query->whereNull('employee_id');
            }

            $payload = array_merge($shift, [
                'uid'               => (string) \Illuminate\Support\Str::uuid(),
                'is_active'         => true,
                'applicable_days'   => isset($shift['applicable_days']) ? json_encode($shift['applicable_days']) : null,
                'position_keywords' => isset($shift['position_keywords']) ? json_encode($shift['position_keywords']) : null,
                'created_at'        => now(),
                'updated_at'        => now(),
            ]);

            if ($query->exists()) {
                unset($payload['uid'], $payload['created_at']);
                $query->update(array_merge($payload, ['updated_at' => now()]));
            } else {
                DB::table('session_shifts')->insert($payload);
            }
        }

        $this->command->info('SessionShiftSeeder selesai.');
        if (!$emiliaId) {
            $this->command->warn('  Emilia tidak ditemukan di tabel employees — shift Emilia dilewati.');
        }
    }
}
