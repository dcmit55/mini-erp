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
 * Saturday simplification:
 *   Semua Saturday = 08:00–13:00. Deteksi Saturday cukup satu record default
 *   (null dept, GENERAL-S). Setiap dept fall-back ke sana via step 4.
 *   Pengecualian: CHEF-S (jam berbeda: 06:00–12:00)
 *                 FINANCE-EM-S (jam berbeda: 08:30–13:30 + employee-specific)
 *   WNA Saturday: step-4 fallback tidak filter for_wna, jadi WNA bisa pakai GENERAL-S.
 *
 * applicable_days: null = semua hari | [1,2,3,4,5] = Sen-Jum | [6] = Sabtu
 * position_keywords: null = semua posisi | ["operator","sewing"] = substring match
 */
class SessionShiftSeeder extends Seeder
{
    public function run(): void
    {
        $dept = DB::table('departments')->pluck('id', 'name');

        $costume      = $dept['DCM Costume']      ?? null;
        $mascot       = $dept['DCM Mascot']       ?? null;
        $animatronics = $dept['DCM Animatronics'] ?? null;
        $opstech      = $dept['OpsTech']          ?? null;
        $finance      = $dept['Finance']          ?? null;
        $logistic     = $dept['Logistic']         ?? null;
        $hrga         = $dept['HRGA']             ?? null;

        $emiliaId = DB::table('employees')
            ->where('name', 'like', '%emilia%')
            ->value('id');

        $shifts = [

            // ================================================================
            // DEFAULT — fallback GENERAL untuk semua dept yang tidak punya
            // shift spesifik. GENERAL-S dipakai sebagai Saturday universal.
            // ================================================================
            [
                'department_id'     => null,
                'employee_id'       => null,
                'type_of_shift'     => 'GENERAL',
                'start_time'        => '08:00:00',
                'end_time'          => '17:00:00',
                'break_start'       => '12:00:00',
                'break_end'         => '13:00:00',
                'break2_start'      => null,
                'break2_end'        => null,
                'for_wna'           => false,
                'detect_from'       => '07:00:00',
                'detect_until'      => '09:30:00',
                'applicable_days'   => [1, 2, 3, 4, 5],
                'position_keywords' => null,
            ],
            [
                'department_id'     => null,
                'employee_id'       => null,
                'type_of_shift'     => 'GENERAL-S',
                'start_time'        => '08:00:00',
                'end_time'          => '13:00:00',
                'break_start'       => null,
                'break_end'         => null,
                'break2_start'      => null,
                'break2_end'        => null,
                'for_wna'           => false,
                'detect_from'       => '07:00:00',
                'detect_until'      => '09:30:00',
                'applicable_days'   => [6],
                'position_keywords' => null,
            ],

            // ================================================================
            // DCM COSTUME — Weekday only (Saturday → falls back to GENERAL-S)
            // Operator/Sewing: 08:00–16:00
            // Cutter/Leader/Admin: 08:00–17:00
            // ================================================================
            [
                'department_id'     => $costume,
                'employee_id'       => null,
                'type_of_shift'     => 'COSTUME',
                'start_time'        => '08:00:00',
                'end_time'          => '16:00:00',
                'break_start'       => '11:30:00',
                'break_end'         => '12:30:00',
                'break2_start'      => null,
                'break2_end'        => null,
                'for_wna'           => false,
                'detect_from'       => '07:00:00',
                'detect_until'      => '14:00:00',
                'applicable_days'   => [1, 2, 3, 4, 5],
                'position_keywords' => ['operator', 'sewing', 'sewer', 'embroidery', 'handstitching'],
            ],
            [
                'department_id'     => $costume,
                'employee_id'       => null,
                'type_of_shift'     => 'COSTUME-ADMIN',
                'start_time'        => '08:00:00',
                'end_time'          => '17:00:00',
                'break_start'       => '11:30:00',
                'break_end'         => '12:30:00',
                'break2_start'      => null,
                'break2_end'        => null,
                'for_wna'           => false,
                'detect_from'       => '07:00:00',
                'detect_until'      => '14:00:00',
                'applicable_days'   => [1, 2, 3, 4, 5],
                'position_keywords' => ['cutter', 'drafter', 'leader', 'lead', 'finishing', 'designer', 'packing', 'supervisor'],
            ],

            // ================================================================
            // DCM MASCOT — WNI
            // Pagi 08:00–17:00 | Siang 10:00–19:00 | Sore 13:00–22:00
            // Saturday → falls back to GENERAL-S
            // ================================================================
            [
                'department_id'     => $mascot,
                'employee_id'       => null,
                'type_of_shift'     => 'MASCOT-WNI',
                'start_time'        => '08:00:00',
                'end_time'          => '17:00:00',
                'break_start'       => '12:00:00',
                'break_end'         => '13:00:00',
                'break2_start'      => null,
                'break2_end'        => null,
                'for_wna'           => false,
                'detect_from'       => '07:00:00',
                'detect_until'      => '09:30:00',
                'applicable_days'   => [1, 2, 3, 4, 5],
                'position_keywords' => null,
            ],
            [
                'department_id'     => $mascot,
                'employee_id'       => null,
                'type_of_shift'     => 'MASCOT-WNI-10',
                'start_time'        => '10:00:00',
                'end_time'          => '19:00:00',
                'break_start'       => '13:00:00',
                'break_end'         => '14:00:00',
                'break2_start'      => null,
                'break2_end'        => null,
                'for_wna'           => false,
                'detect_from'       => '09:30:00',
                'detect_until'      => '11:30:00',
                'applicable_days'   => [1, 2, 3, 4, 5],
                'position_keywords' => null,
            ],
            [
                'department_id'     => $mascot,
                'employee_id'       => null,
                'type_of_shift'     => 'MASCOT-WNI-13',
                'start_time'        => '13:00:00',
                'end_time'          => '22:00:00',
                'break_start'       => '18:00:00',
                'break_end'         => '19:00:00',
                'break2_start'      => null,
                'break2_end'        => null,
                'for_wna'           => false,
                'detect_from'       => '11:30:00',
                'detect_until'      => '14:00:00',
                'applicable_days'   => [1, 2, 3, 4, 5],
                'position_keywords' => null,
            ],

            // ================================================================
            // DCM MASCOT — WNA
            // 08:00–20:00 dan 10:00–22:00 (12-jam shift dengan 2 break)
            // Saturday: fallback ke null-dept GENERAL-S (step 4 tidak filter for_wna).
            // ================================================================
            [
                'department_id'     => $mascot,
                'employee_id'       => null,
                'type_of_shift'     => 'MASCOT-WNA',
                'start_time'        => '08:00:00',
                'end_time'          => '20:00:00',
                'break_start'       => '12:00:00',
                'break_end'         => '13:00:00',
                'break2_start'      => '18:00:00',
                'break2_end'        => '18:30:00',
                'for_wna'           => true,
                'detect_from'       => '07:00:00',
                'detect_until'      => '09:30:00',
                'applicable_days'   => [1, 2, 3, 4, 5],
                'position_keywords' => null,
            ],
            [
                'department_id'     => $mascot,
                'employee_id'       => null,
                'type_of_shift'     => 'MASCOT-WNA-10',
                'start_time'        => '10:00:00',
                'end_time'          => '22:00:00',
                'break_start'       => '12:00:00',
                'break_end'         => '13:00:00',
                'break2_start'      => '18:00:00',
                'break2_end'        => '18:30:00',
                'for_wna'           => true,
                'detect_from'       => '09:30:00',
                'detect_until'      => '12:00:00',
                'applicable_days'   => [1, 2, 3, 4, 5],
                'position_keywords' => null,
            ],

            // ================================================================
            // OPSTECH — GENERAL (jam sama persis dengan default)
            // detect_until lebih lebar (10:30) karena jam masuk lebih fleksibel
            // Saturday → falls back to null-dept GENERAL-S
            // ================================================================
            [
                'department_id'     => $opstech,
                'employee_id'       => null,
                'type_of_shift'     => 'GENERAL',
                'start_time'        => '08:00:00',
                'end_time'          => '17:00:00',
                'break_start'       => '12:00:00',
                'break_end'         => '13:00:00',
                'break2_start'      => null,
                'break2_end'        => null,
                'for_wna'           => false,
                'detect_from'       => '07:00:00',
                'detect_until'      => '10:30:00',
                'applicable_days'   => [1, 2, 3, 4, 5],
                'position_keywords' => null,
            ],

            // ================================================================
            // DCM ANIMATRONICS — GENERAL (jam sama persis dengan default)
            // detect_until lebih lebar (10:30)
            // Saturday → falls back to null-dept GENERAL-S
            // ================================================================
            [
                'department_id'     => $animatronics,
                'employee_id'       => null,
                'type_of_shift'     => 'GENERAL',
                'start_time'        => '08:00:00',
                'end_time'          => '17:00:00',
                'break_start'       => '12:00:00',
                'break_end'         => '13:00:00',
                'break2_start'      => null,
                'break2_end'        => null,
                'for_wna'           => false,
                'detect_from'       => '07:00:00',
                'detect_until'      => '10:30:00',
                'applicable_days'   => [1, 2, 3, 4, 5],
                'position_keywords' => null,
            ],

            // ================================================================
            // LOGISTIC — Weekday only (Saturday → falls back to GENERAL-S)
            // Pagi 08:00–17:00 | Siang 10:00–19:00 | Sore 13:00–22:00
            // (Store dept tidak dipisahkan — jika ada, buat dept sendiri)
            // ================================================================
            [
                'department_id'     => $logistic,
                'employee_id'       => null,
                'type_of_shift'     => 'LOGISTIC',
                'start_time'        => '08:00:00',
                'end_time'          => '17:00:00',
                'break_start'       => '12:00:00',
                'break_end'         => '13:00:00',
                'break2_start'      => null,
                'break2_end'        => null,
                'for_wna'           => false,
                'detect_from'       => '07:00:00',
                'detect_until'      => '09:30:00',
                'applicable_days'   => [1, 2, 3, 4, 5],
                'position_keywords' => null,
            ],
            [
                'department_id'     => $logistic,
                'employee_id'       => null,
                'type_of_shift'     => 'LOGISTIC-10',
                'start_time'        => '10:00:00',
                'end_time'          => '19:00:00',
                'break_start'       => '13:00:00',
                'break_end'         => '14:00:00',
                'break2_start'      => null,
                'break2_end'        => null,
                'for_wna'           => false,
                'detect_from'       => '09:30:00',
                'detect_until'      => '11:30:00',
                'applicable_days'   => [1, 2, 3, 4, 5],
                'position_keywords' => null,
            ],
            [
                'department_id'     => $logistic,
                'employee_id'       => null,
                'type_of_shift'     => 'LOGISTIC-13',
                'start_time'        => '13:00:00',
                'end_time'          => '22:00:00',
                'break_start'       => '17:00:00',
                'break_end'         => '18:00:00',
                'break2_start'      => null,
                'break2_end'        => null,
                'for_wna'           => false,
                'detect_from'       => '11:30:00',
                'detect_until'      => '14:00:00',
                'applicable_days'   => [1, 2, 3, 4, 5],
                'position_keywords' => null,
            ],

            // ================================================================
            // CHEF (HRGA) — 06:00–15:00 (jam berbeda, Saturday: CHEF-S 06:00–12:00)
            // ================================================================
            [
                'department_id'     => $hrga,
                'employee_id'       => null,
                'type_of_shift'     => 'CHEF',
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
            [
                'department_id'     => $hrga,
                'employee_id'       => null,
                'type_of_shift'     => 'CHEF-S',
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
            // SECURITY (HRGA) — 08:00–22:00 Weekday
            // Saturday → falls back to GENERAL-S (08:00–13:00)
            // ================================================================
            [
                'department_id'     => $hrga,
                'employee_id'       => null,
                'type_of_shift'     => 'SECURITY',
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

            // ================================================================
            // CLEANING SERVICE (HRGA) — 08:00–22:00 Weekday
            // Saturday → falls back to GENERAL-S (08:00–13:00)
            // ================================================================
            [
                'department_id'     => $hrga,
                'employee_id'       => null,
                'type_of_shift'     => 'CLEANING',
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

        ];

        // ================================================================
        // EMILIA (Finance) — employee-specific shift
        // 08:30–17:30 weekday | 08:30–13:30 Saturday (jam berbeda)
        // ================================================================
        if ($emiliaId) {
            $shifts[] = [
                'department_id'     => $finance,
                'employee_id'       => $emiliaId,
                'type_of_shift'     => 'FINANCE-EM',
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
                'type_of_shift'     => 'FINANCE-EM-S',
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
                'uid'               => (string) Str::uuid(),
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
            $this->command->warn('  EMILIA tidak ditemukan — shift FINANCE-EM dilewati.');
        }
    }
}
