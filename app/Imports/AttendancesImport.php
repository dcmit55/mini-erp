<?php

namespace App\Imports;

use App\Models\Hr\Employee; // Perbaikan: gunakan namespace yang benar
use App\Models\Hr\AttendanceLog;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class AttendancesImport implements ToModel, WithHeadingRow, WithValidation, SkipsOnFailure
{
    use SkipsFailures;

    private $importSource;
    private $successCount = 0;
    private $failedRows = [];

    public function __construct($importSource)
    {
        $this->importSource = $importSource;
    }

    public function model(array $row)
    {
        // Log untuk debugging (bisa dihapus setelah produksi)
        Log::info('Processing row:', $row);

        // Cari employee berdasarkan nama (case insensitive, trim spasi)
        $name = trim($row['name'] ?? '');
        if (empty($name)) {
            $this->failedRows[] = [
                'row'   => $row,
                'error' => 'Nama karyawan kosong'
            ];
            return null;
        }

        // Cari karyawan berdasarkan nama (persis, case insensitive)
        $employee = Employee::whereRaw('LOWER(TRIM(name)) = ?', [strtolower($name)])->first();

        if (!$employee) {
            $this->failedRows[] = [
                'row'   => $row,
                'error' => "Employee with name '{$name}' not found in database"
            ];
            return null;
        }

        // Hanya karyawan aktif
        if ($employee->status !== 'active') {
            $this->failedRows[] = [
                'row'   => $row,
                'error' => "Employee {$employee->employee_no} is inactive (status: {$employee->status})"
            ];
            return null;
        }

        // Transform date
        $date = $this->transformDate($row['date'] ?? null);
        if (!$date) {
            $this->failedRows[] = [
                'row'   => $row,
                'error' => 'Invalid date format: ' . ($row['date'] ?? 'null')
            ];
            return null;
        }

        // Transform clock in/out
        $clockIn = $this->transformTime($row['clock_in'] ?? null);
        $clockOut = $this->transformTime($row['clock_out'] ?? null);

        // Jika clock_in atau clock_out tidak valid, tetap simpan sebagai null (tidak gagal)
        if ($clockIn === false) $clockIn = null;
        if ($clockOut === false) $clockOut = null;

        $this->successCount++;

        return new AttendanceLog([
            'employee_id'   => $employee->id,
            'date'          => $date,
            'clock_in'      => $clockIn,
            'clock_out'     => $clockOut,
            'import_source' => $this->importSource,
        ]);
    }

    public function rules(): array
    {
        return [
            'name'      => 'required|string',
            'date'      => 'required',
            'clock_in'  => 'nullable',
            'clock_out' => 'nullable',
        ];
    }

    public function headingRow(): int
    {
        return 1;
    }

    /**
     * Transform berbagai format tanggal menjadi Y-m-d
     */
    private function transformDate($value)
    {
        if (empty($value)) return null;

        try {
            // Jika numeric (Excel serial date)
            if (is_numeric($value)) {
                return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value)->format('Y-m-d');
            }

            // Coba parse dengan format d/m/Y (karena data Anda menggunakan format ini)
            if (preg_match('/^\d{1,2}\/\d{1,2}\/\d{4}$/', $value)) {
                return Carbon::createFromFormat('d/m/Y', $value)->format('Y-m-d');
            }

            // Coba format Y-m-d
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
                return $value;
            }

            // Fallback ke Carbon parse
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Exception $e) {
            Log::warning('Date parse error: ' . $e->getMessage() . ' for value: ' . $value);
            return null;
        }
    }

    /**
     * Transform berbagai format waktu menjadi H:i:s
     */
    private function transformTime($value)
    {
        if (empty($value)) return null;

        try {
            // Jika numeric (Excel time serial) - misal 0.3291667 = 07:54
            if (is_numeric($value)) {
                $totalSeconds = $value * 24 * 3600;
                $hours = floor($totalSeconds / 3600);
                $minutes = floor(($totalSeconds % 3600) / 60);
                $seconds = $totalSeconds % 60;
                return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
            }

            // Jika format jam:menit (misal 07:54) atau jam:menit:detik
            if (preg_match('/^\d{1,2}:\d{2}(:\d{2})?$/', $value)) {
                // Jika hanya jam:menit, tambahkan detik 00
                if (substr_count($value, ':') == 1) {
                    return $value . ':00';
                }
                return $value;
            }

            // Jika format jam.menit (misal 07.54)
            if (preg_match('/^\d{1,2}\.\d{2}$/', $value)) {
                return str_replace('.', ':', $value) . ':00';
            }

            // Fallback ke Carbon parse (bisa handle "07:54" juga)
            return Carbon::parse($value)->format('H:i:s');
        } catch (\Exception $e) {
            Log::warning('Time parse error: ' . $e->getMessage() . ' for value: ' . $value);
            return false; // Tandai gagal parse, tapi tidak batalkan seluruh baris
        }
    }

    public function getSuccessCount()
    {
        return $this->successCount;
    }

    public function getFailedRows()
    {
        return $this->failedRows;
    }
}