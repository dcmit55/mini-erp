<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class EmployeePerformanceExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    protected $rankings;
    protected $startDate;
    protected $endDate;

    public function __construct($rankings, $startDate, $endDate)
    {
        $this->rankings = $rankings;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function collection()
    {
        return $this->rankings;
    }

    public function headings(): array
    {
        return ['Rank', 'Employee Name', 'Department', 'Total Working Minutes', 'Total Working Hours', 'Standard Minutes Earned', 'Productivity Score (%)', 'Performance Level', 'Period'];
    }

    public function map($ranking): array
    {
        // Calculate hours from minutes
        $workingHours = round($ranking->total_working_minutes / 60, 2);

        // Determine performance level
        $performanceLevel = 'Poor';
        if ($ranking->productivity_score >= 100) {
            $performanceLevel = 'Excellent';
        } elseif ($ranking->productivity_score >= 85) {
            $performanceLevel = 'Good';
        } elseif ($ranking->productivity_score >= 70) {
            $performanceLevel = 'Average';
        }

        return [$ranking->rank, $ranking->employee_name, $ranking->department_name ?? '-', $ranking->total_working_minutes, $workingHours, round($ranking->total_standard_minutes, 2), round($ranking->productivity_score, 2), $performanceLevel, $this->startDate->format('Y-m-d') . ' to ' . $this->endDate->format('Y-m-d')];
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
