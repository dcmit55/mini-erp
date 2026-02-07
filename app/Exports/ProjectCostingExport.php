<?php

namespace App\Exports;

use App\Models\Logistic\GoodsOut;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Support\Facades\Log;

class ProjectCostingExport implements FromCollection, WithHeadings
{
    protected $materials;
    protected $projectName;

    public function __construct($materials, $projectName)
    {
        $this->materials = $materials;
        $this->projectName = $projectName;
    }

    public function collection()
    {
        return $this->materials->map(function ($item) {
            $currency = $item['currency'] ?? 'IDR';

            return [
                'Job Order' => $item['job_order_name'] ?? 'No Job Order',
                'Material' => $item['material_name'] ?? 'N/A',
                'Quantity' => ($item['used_quantity'] ?? 0) . ' ' . ($item['unit'] ?? ''),
                'Unit Price' => number_format($item['unit_price'] ?? 0, 2, '.', ',') . ' ' . $currency,
                'Total Unit Cost' => number_format($item['total_unit_cost'] ?? 0, 2, '.', ',') . ' ' . $currency,
                'Total Cost (IDR)' => 'Rp ' . number_format($item['total_cost'] ?? 0, 2, '.', ','),
            ];
        });
    }

    public function headings(): array
    {
        return ['Job Order', 'Material', 'Quantity', 'Unit Price', 'Total Unit Cost', 'Total Cost (IDR)'];
    }
}
