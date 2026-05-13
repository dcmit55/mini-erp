<?php
// app/Imports/WorkPoliciesImport.php

namespace App\Imports;

use App\Models\Hr\EmployeeWorkPolicy;
use App\Models\Hr\Employee;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class WorkPoliciesImport implements ToModel, WithHeadingRow, WithValidation, SkipsOnFailure
{
    use SkipsFailures;

    private $successCount = 0;
    private $updatedCount = 0;
    private $failedRows = [];

    public function model(array $row)
    {
        // Cari employee
        $employee = null;
        if (!empty($row['employee_no'])) {
            $employee = Employee::where('employee_no', $row['employee_no'])->first();
        }
        if (!$employee && !empty($row['name'])) {
            $employee = Employee::whereRaw('LOWER(TRIM(name)) = ?', [strtolower(trim($row['name']))])->first();
        }

        if (!$employee) {
            $this->failedRows[] = [
                'row'   => $row,
                'error' => "Karyawan tidak ditemukan: " . ($row['employee_no'] ?? $row['name'] ?? 'Unknown')
            ];
            return null;
        }

        if (!$employee->isActive()) {
            $this->failedRows[] = [
                'row'   => $row,
                'error' => "Karyawan {$employee->employee_no} tidak aktif"
            ];
            return null;
        }

        // Format waktu dengan method yang ditingkatkan
        $data = [
            'weekday_start' => $this->formatTime($row['weekday_start'] ?? null) ?: '08:00',
            'weekday_end'   => $this->formatTime($row['weekday_end'] ?? null) ?: '17:00',
            'saturday_start' => $this->formatTime($row['saturday_start'] ?? null) ?: '08:00',
            'saturday_end'   => $this->formatTime($row['saturday_end'] ?? null) ?: '13:00',
            'sunday_start'   => $this->formatTime($row['sunday_start'] ?? null),
            'sunday_end'     => $this->formatTime($row['sunday_end'] ?? null),
        ];

        // Cari policy yang sudah ada
        $existing = EmployeeWorkPolicy::where('employee_id', $employee->id)->first();

        if ($existing) {
            $existing->update($data);
            $this->updatedCount++;
            return null;
        } else {
            $this->successCount++;
            return new EmployeeWorkPolicy(array_merge($data, [
                'uid' => Str::uuid(),
                'employee_id' => $employee->id,
                'employee_no' => $employee->employee_no,
            ]));
        }
    }

    /**
     * Format berbagai macam input waktu menjadi format H:i (24 jam)
     */
    private function formatTime($value)
    {
        if (empty($value)) return null;

        $value = trim((string) $value);
        if ($value === '') return null;

        // Coba parse dengan Carbon
        try {
            return Carbon::parse($value)->format('H:i');
        } catch (\Exception $e) {
            // Regex untuk format seperti "14:00:00 PM"
            if (preg_match('/(\d{1,2}):(\d{2})(?::\d{2})?\s*(AM|PM)?/i', $value, $matches)) {
                $hour = (int) $matches[1];
                $minute = (int) $matches[2];
                
                if (isset($matches[3])) {
                    $ampm = strtoupper($matches[3]);
                    if ($ampm === 'PM' && $hour < 12) {
                        $hour += 12;
                    } elseif ($ampm === 'AM' && $hour == 12) {
                        $hour = 0;
                    }
                }
                
                if ($hour >= 0 && $hour <= 23 && $minute >= 0 && $minute <= 59) {
                    return sprintf('%02d:%02d', $hour, $minute);
                }
            }
            
            Log::warning("Gagal memformat waktu: {$value}");
            return null;
        }
    }

    public function rules(): array
    {
        return [
            'employee_no' => 'nullable|string',
            'name'        => 'nullable|string',
            'weekday_start' => 'nullable',
            'weekday_end' => 'nullable',
            'saturday_start' => 'nullable',
            'saturday_end' => 'nullable',
            'sunday_start' => 'nullable',
            'sunday_end' => 'nullable',
        ];
    }

    public function headingRow(): int
    {
        return 1;
    }

    public function getSuccessCount()
    {
        return $this->successCount;
    }

    public function getUpdatedCount()
    {
        return $this->updatedCount;
    }

    public function getFailedRows()
    {
        return $this->failedRows;
    }

    public function getResults()
    {
        return [
            'success' => $this->successCount,
            'updated' => $this->updatedCount,
            'failed' => count($this->failedRows),
            'failed_rows' => $this->failedRows,
        ];
    }
}