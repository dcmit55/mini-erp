<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use App\Models\Employee;
use App\Models\Project;

class ImportTimingTemplate implements FromArray, WithHeadings, WithStyles, WithColumnWidths
{
    public function headings(): array
    {
        $headings = ['date', 'project', 'department (from project)', 'step', 'parts', 'employee', 'start', 'end', 'qty', 'status', 'remarks'];
        return $headings;
    }

    public function array(): array
    {
        // Tambahkan contoh data dengan format waktu yang benar untuk panduan user
        // Note: Department otomatis diambil dari project, kolom ini bisa dikosongkan
        return [
            ['2024-01-15', 'Sample Project', '', 'Assembly', 'Part A', 'John Doe', '08:00', '12:00', '10', 'complete', 'Sample work'],
            ['2024-01-15', 'Sample Project', '', 'Testing', 'Part B', 'Jane Smith', '13:00', '17:00', '5', 'on progress', 'Testing phase'],
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
            'B' => 20, // project
            'C' => 20, // department
            'D' => 20, // step
            'E' => 20, // parts
            'F' => 20, // employee_name
            'G' => 15, // start_time
            'H' => 15, // end_time
            'I' => 15, // output_qty
            'J' => 15, // status
            'K' => 20, // remarks
        ];
    }
}