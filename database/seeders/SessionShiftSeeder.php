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
        $opstech      = $dept['OpsTech']          ?? null;
        $finance      = $dept['Finance']          ?? null;
        $store        = $dept['Store']            ?? null;
        $logistic     = $dept['Logistic']         ?? null;
        $hrga         = $dept['HRGA']             ?? null;

        // Chef, Security, CS semuanya di dept HRGA — dibedakan oleh position_keywords
        $chef     = $hrga;
        $security = $hrga;
        $cs       = $hrga;

        // Lookup Emilia's employee ID (Finance, shift khusus)
        $emiliaId = DB::table('employees')
            ->where('name', 'like', '%emilia%')
            ->value('id');

        // Hapus global S10/S13 yang ter-seed salah (seharusnya dept-spesifik)
        DB::table('session_shifts')
            ->whereNull('department_id')
            ->whereIn('type_of_shift', ['S10', 'S10S', 'S13', 'S13S'])
            ->delete();

        $shifts = [

            // ================================================================
            // DEFAULT — fallback A9 untuk semua dept yang tidak punya shift spesifik
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
                'applicable_days'   => [1, 2, 3, 4, 5],
                'position_keywords' => null,
            ],
            [
                'department_id'     => null,
                'employee_id'       => null,
                'type_of_shift'     => 'A9S',
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
            // DCM COSTUME
            // WNI: C8 (08:00–16:00) untuk operator/sewing, A9 (08:00–17:00) sisanya
            // Sabtu: semua pulang 13:00
            // ================================================================

            // C8 — Operator, Sewing, dsb — Weekday
            [
                'department_id'     => $costume,
                'employee_id'       => null,
                'type_of_shift'     => 'C8',
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
            // C8 — Saturday
            [
                'department_id'     => $costume,
                'employee_id'       => null,
                'type_of_shift'     => 'C8S',
                'start_time'        => '08:00:00',
                'end_time'          => '13:00:00',
                'break_start'       => null,
                'break_end'         => null,
                'break2_start'      => null,
                'break2_end'        => null,
                'for_wna'           => false,
                'detect_from'       => '07:00:00',
                'detect_until'      => '12:00:00',
                'applicable_days'   => [6],
                'position_keywords' => ['operator', 'sewing', 'sewer', 'embroidery', 'handstitching'],
            ],

            // A9 — Cutter, Leader, Finishing, Designer — Weekday
            [
                'department_id'     => $costume,
                'employee_id'       => null,
                'type_of_shift'     => 'A9',
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
            // A9 — Saturday
            [
                'department_id'     => $costume,
                'employee_id'       => null,
                'type_of_shift'     => 'A9S',
                'start_time'        => '08:00:00',
                'end_time'          => '13:00:00',
                'break_start'       => null,
                'break_end'         => null,
                'break2_start'      => null,
                'break2_end'        => null,
                'for_wna'           => false,
                'detect_from'       => '07:00:00',
                'detect_until'      => '12:00:00',
                'applicable_days'   => [6],
                'position_keywords' => ['cutter', 'drafter', 'leader', 'lead', 'finishing', 'designer', 'packing', 'supervisor'],
            ],

            // ================================================================
            // DCM MASCOT — WNI
            // Pagi A9 (08:00–17:00) | Siang M10/S10 (10:00–19:00) | Sore B9/S13 (13:00–22:00)
            // Sabtu: semua 08:00–13:00
            // ================================================================

            // A9 — Pagi — Weekday
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
                'applicable_days'   => [1, 2, 3, 4, 5],
                'position_keywords' => null,
            ],
            // A9S — Saturday
            [
                'department_id'     => $mascot,
                'employee_id'       => null,
                'type_of_shift'     => 'A9S',
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

            // M10 — Siang rotasi M — Weekday
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
                'applicable_days'   => [1, 2, 3, 4, 5],
                'position_keywords' => null,
            ],
            // M10S — Saturday
            [
                'department_id'     => $mascot,
                'employee_id'       => null,
                'type_of_shift'     => 'M10S',
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

            // S10 — Siang rotasi S — Weekday
            [
                'department_id'     => $mascot,
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
                'applicable_days'   => [1, 2, 3, 4, 5],
                'position_keywords' => null,
            ],
            // S10S — Saturday
            [
                'department_id'     => $mascot,
                'employee_id'       => null,
                'type_of_shift'     => 'S10S',
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

            // B9 — Sore, break 18:00–19:00 — Weekday
            [
                'department_id'     => $mascot,
                'employee_id'       => null,
                'type_of_shift'     => 'B9',
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
            // B9S — Saturday
            [
                'department_id'     => $mascot,
                'employee_id'       => null,
                'type_of_shift'     => 'B9S',
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

            // S13 — Sore, break 17:00–18:00 — Weekday
            [
                'department_id'     => $mascot,
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
                'applicable_days'   => [1, 2, 3, 4, 5],
                'position_keywords' => null,
            ],
            // S13S — Saturday
            [
                'department_id'     => $mascot,
                'employee_id'       => null,
                'type_of_shift'     => 'S13S',
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
            // DCM MASCOT — WNA
            // A12 (08:00–20:00) | B12 (10:00–22:00) | Sabtu: 08:00–13:00
            // ================================================================

            // A12 — Weekday
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
                'applicable_days'   => [1, 2, 3, 4, 5],
                'position_keywords' => null,
            ],
            // A12S — Saturday
            [
                'department_id'     => $mascot,
                'employee_id'       => null,
                'type_of_shift'     => 'A12S',
                'start_time'        => '08:00:00',
                'end_time'          => '13:00:00',
                'break_start'       => null,
                'break_end'         => null,
                'break2_start'      => null,
                'break2_end'        => null,
                'for_wna'           => true,
                'detect_from'       => '07:00:00',
                'detect_until'      => '09:30:00',
                'applicable_days'   => [6],
                'position_keywords' => null,
            ],

            // B12 — Weekday
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
                'applicable_days'   => [1, 2, 3, 4, 5],
                'position_keywords' => null,
            ],
            // B12S — Saturday
            [
                'department_id'     => $mascot,
                'employee_id'       => null,
                'type_of_shift'     => 'B12S',
                'start_time'        => '08:00:00',
                'end_time'          => '13:00:00',
                'break_start'       => null,
                'break_end'         => null,
                'break2_start'      => null,
                'break2_end'        => null,
                'for_wna'           => true,
                'detect_from'       => '07:00:00',
                'detect_until'      => '09:30:00',
                'applicable_days'   => [6],
                'position_keywords' => null,
            ],

            // ================================================================
            // OPSTECH — A9 (08:00–17:00), detect diperlebar s/d 10:30
            // karena jam masuk bisa lebih fleksibel
            // ================================================================

            // A9 — Weekday
            [
                'department_id'     => $opstech,
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
                'detect_until'      => '10:30:00',
                'applicable_days'   => [1, 2, 3, 4, 5],
                'position_keywords' => null,
            ],
            // A9S — Saturday
            [
                'department_id'     => $opstech,
                'employee_id'       => null,
                'type_of_shift'     => 'A9S',
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
            // DCM ANIMATRONICS — A9 (08:00–17:00), detect diperlebar s/d 10:30
            // ================================================================

            // A9 — Weekday
            [
                'department_id'     => $animatronics,
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
                'detect_until'      => '10:30:00',
                'applicable_days'   => [1, 2, 3, 4, 5],
                'position_keywords' => null,
            ],
            // A9S — Saturday
            [
                'department_id'     => $animatronics,
                'employee_id'       => null,
                'type_of_shift'     => 'A9S',
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
            // STORE & LOGISTIC — A9 / S10 / S13
            // Sabtu: semua 08:00–13:00
            // ================================================================

            // Store — Pagi
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
                'applicable_days'   => [1, 2, 3, 4, 5],
                'position_keywords' => null,
            ],
            [
                'department_id'     => $store,
                'employee_id'       => null,
                'type_of_shift'     => 'A9S',
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
            // Store — Siang
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
                'applicable_days'   => [1, 2, 3, 4, 5],
                'position_keywords' => null,
            ],
            [
                'department_id'     => $store,
                'employee_id'       => null,
                'type_of_shift'     => 'S10S',
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
            // Store — Sore
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
                'applicable_days'   => [1, 2, 3, 4, 5],
                'position_keywords' => null,
            ],
            [
                'department_id'     => $store,
                'employee_id'       => null,
                'type_of_shift'     => 'S13S',
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

            // Logistic — sama dengan Store
            [
                'department_id'     => $logistic,
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
                'applicable_days'   => [1, 2, 3, 4, 5],
                'position_keywords' => null,
            ],
            [
                'department_id'     => $logistic,
                'employee_id'       => null,
                'type_of_shift'     => 'A9S',
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
            [
                'department_id'     => $logistic,
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
                'applicable_days'   => [1, 2, 3, 4, 5],
                'position_keywords' => null,
            ],
            [
                'department_id'     => $logistic,
                'employee_id'       => null,
                'type_of_shift'     => 'S10S',
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
            [
                'department_id'     => $logistic,
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
                'applicable_days'   => [1, 2, 3, 4, 5],
                'position_keywords' => null,
            ],
            [
                'department_id'     => $logistic,
                'employee_id'       => null,
                'type_of_shift'     => 'S13S',
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
            // CHEF (HRGA) — Senin–Jumat 06:00–15:00 | Sabtu 06:00–12:00
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
            // SECURITY (HRGA) — Weekday 08:00–22:00 | Sabtu 08:00–13:00
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
            // CLEANING SERVICE (HRGA) — Weekday 08:00–22:00 | Sabtu 08:00–13:00
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
        // EMILIA (Finance) — shift individual per-karyawan
        // Weekday 08:30–17:30 | Sabtu 08:30–13:30
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
            $this->command->warn('  EMILIA tidak ditemukan di tabel employees — shift EMILIA dilewati.');
        }
    }
}
