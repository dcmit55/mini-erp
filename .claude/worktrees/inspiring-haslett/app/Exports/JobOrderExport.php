<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class JobOrderExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    protected $jobOrders;

    public function __construct($jobOrders)
    {
        $this->jobOrders = $jobOrders;
    }

    public function collection()
    {
        return $this->jobOrders;
    }

    public function headings(): array
    {
        return ['Job Order ID', 'Name', 'Project', 'Department', 'Start Date', 'End Date', 'Description', 'Notes', 'Created By', 'Created At'];
    }

    public function map($jobOrder): array
    {
        return [$jobOrder->id, $jobOrder->name, $jobOrder->project ? $jobOrder->project->name : '-', $jobOrder->department ? $jobOrder->department->name : '-', $jobOrder->start_date ? \Carbon\Carbon::parse($jobOrder->start_date)->format('Y-m-d') : '-', $jobOrder->end_date ? \Carbon\Carbon::parse($jobOrder->end_date)->format('Y-m-d') : '-', $jobOrder->description ?: '-', $jobOrder->notes ?: '-', $jobOrder->creator ? $jobOrder->creator->username : '-', $jobOrder->created_at ? $jobOrder->created_at->format('Y-m-d H:i:s') : '-'];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Style untuk header
            1 => [
                'font' => ['bold' => true, 'size' => 12],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4472C4'],
                ],
                'font' => ['color' => ['rgb' => 'FFFFFF'], 'bold' => true],
                'alignment' => ['horizontal' => 'center', 'vertical' => 'center'],
            ],
        ];
    }
}
