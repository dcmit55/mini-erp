<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use App\Models\Hr\Employee;
use App\Models\Production\Project;

class ImportTimingTemplate implements FromArray, WithHeadings, WithStyles, WithColumnWidths
{
    public function headings(): array
    {
        $headings = ['date', 'job_order', 'project', 'department', 'step', 'parts', 'employee', 'start', 'end', 'duration', 'value', 'type', 'status', 'approval', 'remarks'];
        return $headings;
    }

    public function array(): array
    {
        // ============================================
        // IMPORT GUIDELINES - PLEASE READ CAREFULLY
        // ============================================
        //
        // REQUIRED FIELDS (cannot be empty):
        // - date: Format DD/MM/YYYY or DD-MM-YYYY (e.g., 15/01/2024 or 15-01-2024)
        // - job_order OR project: At least ONE must be filled
        // - department: Must match existing department name in system
        // - employee: Must match existing employee name in system
        // - start: Format HH:MM (e.g., 08:00, 13:30)
        // - end: Format HH:MM (e.g., 12:00, 17:00)
        //
        // OPTIONAL FIELDS:
        // - step, parts, duration (auto-calculated), value, type, status, approval, remarks
        //
        // NOTES:
        // 1. If you provide BOTH job_order and project, system will use job_order's project
        // 2. Duration is auto-calculated from start and end time (you can leave it empty)
        // 3. Date formats supported: DD/MM/YYYY, DD-MM-YYYY, YYYY-MM-DD
        // 4. Time must be in 24-hour format (HH:MM)
        // 5. Employee and Department names must match EXACTLY with database
        //
        // SAMPLE DATA BELOW:

        return [
            // Example 1: With Job Order
            ['15/01/2024', 'JO-001', 'Sample Project', 'Production', 'Assembly', 'Part A', 'John Doe', '08:00', '12:00', '', '10', 'pcs', 'complete', 'approved', 'Sample work'],

            // Example 2: With Project only (no job order)
            ['15/01/2024', '', 'Sample Project', 'Quality Control', 'Testing', 'Part B', 'Jane Smith', '13:00', '17:00', '', '5', 'pcs', 'on progress', 'pending', 'Testing phase'],

            // Example 3: With Job Order only (project will be auto-filled from job order)
            ['16/01/2024', 'JO-002', '', 'Assembly', 'Welding', 'Frame', 'Bob Wilson', '08:30', '11:30', '', '3', 'units', 'complete', 'approved', 'Completed welding'],
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => [
                    'bold' => true, // Cetak tebal
                ],
            ],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 15, // date
            'B' => 20, // job_order
            'C' => 20, // project
            'D' => 20, // department
            'E' => 20, // step
            'F' => 20, // parts
            'G' => 20, // employee
            'H' => 15, // start
            'I' => 15, // end
            'J' => 15, // duration
            'K' => 15, // value
            'L' => 15, // type
            'M' => 15, // status
            'N' => 15, // approval
            'O' => 25, // remarks
        ];
    }
}
