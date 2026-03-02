<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ImportInventoryTemplate implements FromArray, WithHeadings, WithStyles, WithColumnWidths
{
    public function headings(): array
    {
        $headings = ['name', 'category', 'quantity', 'unit', 'price', 'currency', 'supplier', 'location', 'remark'];
        return array_map('ucwords', $headings);
    }

    public function array(): array
    {
        // Kosongkan data, hanya header yang diperlukan
        return [];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Gaya untuk header (baris pertama)
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
            'A' => 25, // Lebar kolom 'name'
            'B' => 15, // Lebar kolom 'category'
            'C' => 15, // Lebar kolom 'quantity'
            'D' => 10, // Lebar kolom 'unit'
            'E' => 15, // Lebar kolom 'price'
            'F' => 10, // Lebar kolom 'currency'
            'G' => 20, // Lebar kolom 'supplier'
            'H' => 15, // Lebar kolom 'location'
            'I' => 25, // Lebar kolom 'remark'
        ];
    }
}