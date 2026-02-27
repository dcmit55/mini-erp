<?php

namespace App\Imports;

use App\Models\Hr\Employee;
use App\Models\Admin\Department;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;

class EmployeesImport implements ToModel, WithHeadingRow, WithValidation, SkipsOnError, WithChunkReading
{
    use Importable, SkipsErrors;
    
    protected $successCount = 0;
    protected $updateCount = 0;
    protected $failedRows = [];
    protected $rowIndex = 1;

    /**
     * Proses setiap baris data
     */
    public function model(array $row)
    {
        $this->rowIndex++;
        
        // =========== LOG DETAIL ===========
        Log::info('========== ROW ' . $this->rowIndex . ' ==========');
        Log::info('Raw row data:', $row);
        
        // Log semua kolom penting dengan tipe data
        Log::info('employee_no: ' . ($row['employee_no'] ?? 'NULL') . ' (type: ' . gettype($row['employee_no'] ?? null) . ')');
        Log::info('name: ' . ($row['name'] ?? 'NULL') . ' (type: ' . gettype($row['name'] ?? null) . ')');
        Log::info('position: ' . ($row['position'] ?? 'NULL') . ' (type: ' . gettype($row['position'] ?? null) . ')');
        Log::info('department: ' . ($row['department'] ?? 'NULL') . ' (type: ' . gettype($row['department'] ?? null) . ')');
        Log::info('ktp_id: ' . ($row['ktp_id'] ?? 'NULL') . ' (type: ' . gettype($row['ktp_id'] ?? null) . ')');
        Log::info('phone: ' . ($row['phone'] ?? 'NULL') . ' (type: ' . gettype($row['phone'] ?? null) . ')');
        Log::info('rekening: ' . ($row['rekening'] ?? 'NULL') . ' (type: ' . gettype($row['rekening'] ?? null) . ')');
        // ==================================
        
        // Validasi data wajib
        if (empty($row['employee_no'])) {
            $this->failedRows[] = [
                'row' => $this->rowIndex,
                'name' => $row['name'] ?? 'Unknown',
                'error' => 'Employee No wajib diisi'
            ];
            return null;
        }
        if (empty($row['name'])) {
            $this->failedRows[] = [
                'row' => $this->rowIndex,
                'name' => 'Unknown',
                'error' => 'Name wajib diisi'
            ];
            return null;
        }
        if (empty($row['position'])) {
            $this->failedRows[] = [
                'row' => $this->rowIndex,
                'name' => $row['name'] ?? 'Unknown',
                'error' => 'Position wajib diisi'
            ];
            return null;
        }

        // Format employee_no
        $formattedEmployeeNo = Employee::formatEmployeeNo($row['employee_no']);
        Log::info('Formatted employee_no: ' . $formattedEmployeeNo);
        
        // Cari employee berdasarkan employee_no
        $employee = Employee::where('employee_no', $formattedEmployeeNo)->first();
        Log::info('Employee exists? ' . ($employee ? 'YES (ID: ' . $employee->id . ')' : 'NO'));
        
        // Cari department_id berdasarkan nama department
        $departmentId = null;
        if (!empty($row['department'])) {
            Log::info('Searching for department: ' . $row['department']);
            $department = Department::where('name', 'like', '%' . $row['department'] . '%')->first();
            
            if ($department) {
                $departmentId = $department->id;
                Log::info('Department found: ID=' . $department->id . ', Name=' . $department->name);
            } else {
                $this->failedRows[] = [
                    'row' => $this->rowIndex,
                    'name' => $row['name'] ?? 'Unknown',
                    'error' => "Department '{$row['department']}' tidak ditemukan di database"
                ];
                return null;
            }
        }

        // Validasi employment_type
        $employmentType = $this->validateEmploymentType($row['employment_type'] ?? null);
        if ($employmentType === false) return null;
        
        // Validasi gender
        $gender = $this->validateGender($row['gender'] ?? null);
        if ($gender === false) return null;
        
        // Validasi status
        $status = $this->validateStatus($row['status'] ?? 'active');
        if ($status === false) return null;
        
        // Parse tanggal
        $dateOfBirth = $this->parseDate($row['date_of_birth'] ?? null);
        $hireDate = $this->parseDate($row['hire_date'] ?? null);
        $contractEndDate = $this->parseDate($row['contract_end_date'] ?? null);
        
        // Parse angka
        $salary = $this->parseNumber($row['salary'] ?? null);
        $saldoCuti = $this->parseNumber($row['saldo_cuti'] ?? 0);

        // Siapkan data - PASTIKAN SEMUA ANGKA DIJADIKAN STRING
        $data = [
            'employee_no' => $formattedEmployeeNo,
            'name' => $row['name'],
            'username' => $row['username'] ?? null,
            'uid' => $employee?->uid ?? Str::uuid()->toString(),
            'employment_type' => $employmentType,
            'photo' => $row['photo'] ?? null,
            'position' => $row['position'],
            'department_id' => $departmentId,
            'email' => $row['email'] ?? null,
            'phone' => $this->forceString($row['phone'] ?? null),
            'address' => $row['address'] ?? null,
            'gender' => $gender,
            'ktp_id' => $this->forceString($row['ktp_id'] ?? null),
            'place_of_birth' => $row['place_of_birth'] ?? null,
            'date_of_birth' => $dateOfBirth,
            'rekening' => $this->forceString($row['rekening'] ?? null),
            'hire_date' => $hireDate,
            'contract_end_date' => $contractEndDate,
            'salary' => $salary,
            'saldo_cuti' => $saldoCuti,
            'status' => $status,
            'notes' => $row['notes'] ?? null,
        ];

        Log::info('Data to be saved:', $data);

        try {
            if ($employee) {
                // Update data yang sudah ada
                $employee->update($data);
                $this->updateCount++;
                Log::info('✓ UPDATED employee: ' . $formattedEmployeeNo);
                return null;
            } else {
                // Tambah data baru
                $newEmployee = Employee::create($data);
                $this->successCount++;
                Log::info('✓ CREATED new employee: ' . $formattedEmployeeNo . ' with ID: ' . $newEmployee->id);
                return $newEmployee;
            }
        } catch (\Exception $e) {
            $this->failedRows[] = [
                'row' => $this->rowIndex,
                'name' => $row['name'] ?? 'Unknown',
                'error' => $e->getMessage()
            ];
            Log::error('✗ Error saving row ' . $this->rowIndex . ': ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Aturan validasi - HAPUS 'string' dari kolom angka
     */
    public function rules(): array
    {
        return [
            '*.employee_no' => 'required|max:255',
            '*.name' => 'required|max:255',
            '*.position' => 'required|max:255',
            '*.username' => 'nullable|max:255',
            '*.email' => 'nullable|email|max:255',
            '*.ktp_id' => 'nullable|max:20', // 'string' dihapus
            '*.phone' => 'nullable|max:20',  // 'string' dihapus
            '*.rekening' => 'nullable|max:30', // 'string' dihapus
            '*.salary' => 'nullable|numeric',
            '*.saldo_cuti' => 'nullable|numeric',
        ];
    }

    /**
     * Ukuran chunk untuk membaca data
     */
    public function chunkSize(): int
    {
        return 100;
    }

    /**
     * Mendapatkan hasil import
     */
    public function getResults()
    {
        return [
            'success' => $this->successCount,
            'updated' => $this->updateCount,
            'failed' => count($this->failedRows),
            'failed_rows' => $this->failedRows
        ];
    }

    /**
     * Paksa nilai menjadi string
     */
    private function forceString($value)
    {
        if (is_null($value)) {
            return null;
        }
        
        if (is_numeric($value)) {
            return (string) $value;
        }
        
        return $value;
    }

    /**
     * Validasi employment type
     */
    private function validateEmploymentType($value)
    {
        if (empty($value)) {
            return null;
        }
        
        $allowed = ['PKWT', 'PKWTT', 'Daily Worker', 'Probation'];
        $value = trim($value);
        
        if (!in_array($value, $allowed)) {
            $this->failedRows[] = [
                'row' => $this->rowIndex,
                'name' => request()->input('name') ?? 'Unknown',
                'error' => "Employment type '{$value}' tidak valid. Harus: " . implode(', ', $allowed)
            ];
            return false;
        }
        
        return $value;
    }

    /**
     * Validasi gender
     */
    private function validateGender($value)
    {
        if (empty($value)) {
            return null;
        }
        
        $allowed = ['male', 'female'];
        $value = trim($value);
        
        if (!in_array($value, $allowed)) {
            $this->failedRows[] = [
                'row' => $this->rowIndex,
                'name' => request()->input('name') ?? 'Unknown',
                'error' => "Gender '{$value}' tidak valid. Harus: male atau female"
            ];
            return false;
        }
        
        return $value;
    }

    /**
     * Validasi status
     */
    private function validateStatus($value)
    {
        $allowed = ['active', 'inactive', 'terminated'];
        $value = trim($value);
        
        if (!in_array($value, $allowed)) {
            $this->failedRows[] = [
                'row' => $this->rowIndex,
                'name' => request()->input('name') ?? 'Unknown',
                'error' => "Status '{$value}' tidak valid. Harus: " . implode(', ', $allowed)
            ];
            return false;
        }
        
        return $value;
    }

    /**
     * Parse tanggal dari berbagai format
     */
    private function parseDate($value)
    {
        if (empty($value)) return null;
        
        try {
            // Jika dari Excel (angka serial)
            if (is_numeric($value)) {
                $date = Carbon::createFromFormat('Y-m-d', '1900-01-01')
                    ->addDays($value - 2)
                    ->format('Y-m-d');
                Log::info('Parsed numeric date: ' . $value . ' -> ' . $date);
                return $date;
            }
            
            // Coba berbagai format tanggal
            $formats = [
                'Y-m-d', 'd/m/Y', 'd-m-Y', 'm/d/Y', 'Y/m/d',
                'd M Y', 'd F Y', 'M d, Y', 'F d, Y'
            ];
            
            foreach ($formats as $format) {
                try {
                    $date = Carbon::createFromFormat($format, $value)->format('Y-m-d');
                    Log::info('Parsed date with format ' . $format . ': ' . $value . ' -> ' . $date);
                    return $date;
                } catch (\Exception $e) {
                    continue;
                }
            }
            
            // Jika semua format gagal, coba parse dengan strtotime
            $timestamp = strtotime($value);
            if ($timestamp !== false) {
                $date = date('Y-m-d', $timestamp);
                Log::info('Parsed date with strtotime: ' . $value . ' -> ' . $date);
                return $date;
            }
            
            $this->failedRows[] = [
                'row' => $this->rowIndex,
                'name' => request()->input('name') ?? 'Unknown',
                'error' => "Format tanggal '{$value}' tidak dikenal"
            ];
            return null;
            
        } catch (\Exception $e) {
            Log::error('Date parsing failed: ' . $value . ' at row ' . $this->rowIndex);
            $this->failedRows[] = [
                'row' => $this->rowIndex,
                'name' => request()->input('name') ?? 'Unknown',
                'error' => "Error parsing date '{$value}'"
            ];
            return null;
        }
    }

    /**
     * Parse angka dari berbagai format
     */
    private function parseNumber($value)
    {
        if (empty($value)) return null;
        
        if (is_numeric($value)) {
            return $value;
        }
        
        $original = $value;
        // Hapus simbol mata uang dan pemisah ribuan
        $value = str_replace(['Rp', 'IDR', ' ', '.', ','], ['', '', '', '', '.'], $value);
        
        if (is_numeric($value)) {
            Log::info('Parsed number: ' . $original . ' -> ' . $value);
            return (float) $value;
        }
        
        $this->failedRows[] = [
            'row' => $this->rowIndex,
            'name' => request()->input('name') ?? 'Unknown',
            'error' => "Format angka '{$original}' tidak valid"
        ];
        return null;
    }
}