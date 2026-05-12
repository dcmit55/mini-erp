<?php

namespace App\Exports;

use App\Models\Hr\Employee;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class EmployeeExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize, WithTitle
{
    protected string $status;
    protected int $rowNum = 0;

    public function __construct(string $status = 'all')
    {
        $this->status = $status;
    }

    public function collection()
    {
        $query = Employee::with('department')->orderBy('employee_no');

        if ($this->status !== 'all') {
            $query->where('status', $this->status);
        }

        return $query->get();
    }

    public function title(): string
    {
        return 'Employees';
    }

    public function headings(): array
    {
        return ['No.', 'Employee No.', 'Name', 'Department', 'Position', 'Employment Type', 'Gender', 'Citizenship', 'Place of Birth', 'Date of Birth', 'Email', 'Phone', 'Address', 'KTP ID', 'Rekening', 'Hire Date', 'Contract End Date', 'Salary', 'Saldo Cuti', 'Status', 'Username', 'UID', 'Device Registered At', 'Biometric Enrolled At', 'Notes'];
    }

    public function map($employee): array
    {
        $this->rowNum++;

        return [
            $this->rowNum,
            $employee->employee_no,
            $employee->name,
            $employee->department?->name ?? null,
            $employee->position ?? null,
            $employee->employment_type ?? null,
            $employee->gender ?? null,
            $employee->citizenship ?? null,
            $employee->place_of_birth ?? null,
            $employee->date_of_birth ? \Carbon\Carbon::parse($employee->date_of_birth)->format('Y-m-d') : null,
            $employee->email ?? null,
            $employee->phone ?? null,
            $employee->address ?? null,
            $employee->ktp_id ?? null,
            $employee->rekening ?? null,
            $employee->hire_date ? \Carbon\Carbon::parse($employee->hire_date)->format('Y-m-d') : null,
            $employee->contract_end_date ? \Carbon\Carbon::parse($employee->contract_end_date)->format('Y-m-d') : null,
            $employee->salary ?? null,
            $employee->saldo_cuti ?? null,
            $employee->status ? strtolower($employee->status) : null,
            $employee->username ?? null,
            $employee->uid ?? null,
            $employee->device_registered_at ? \Carbon\Carbon::parse($employee->device_registered_at)->format('Y-m-d H:i:s') : null,
            $employee->biometric_enrolled_at ? \Carbon\Carbon::parse($employee->biometric_enrolled_at)->format('Y-m-d H:i:s') : null,
            $employee->notes ?? null,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4472C4'],
                ],
                'alignment' => ['horizontal' => 'center', 'vertical' => 'center'],
            ],
        ];
    }
}
