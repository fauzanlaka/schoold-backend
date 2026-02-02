<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class AssetCategoryTemplateExport implements FromArray, WithHeadings, WithStyles, WithColumnWidths
{
    /**
     * สร้างข้อมูลตัวอย่าง
     *
     * @return array<int, array<int, mixed>>
     */
    public function array(): array
    {
        return [
            ['คอมพิวเตอร์และอุปกรณ์', 'CAT-001', 5, 20, 'ครุภัณฑ์คอมพิวเตอร์และอุปกรณ์ต่อพ่วง', 'ใช้งาน'],
            ['เครื่องใช้สำนักงาน', 'CAT-002', 10, 10, 'โต๊ะ เก้าอี้ ตู้เก็บเอกสาร', 'ใช้งาน'],
            ['อุปกรณ์โสตทัศนูปกรณ์', 'CAT-003', 8, 12.5, 'โปรเจคเตอร์ ทีวี เครื่องเสียง', 'ใช้งาน'],
        ];
    }

    /**
     * Headings ภาษาไทย
     *
     * @return array<int, string>
     */
    public function headings(): array
    {
        return [
            'ชื่อประเภท',
            'รหัสประเภท',
            'อายุใช้งาน',
            'อัตราค่าเสื่อม',
            'รายละเอียด',
            'สถานะ',
        ];
    }

    /**
     * Column widths
     *
     * @return array<string, int>
     */
    public function columnWidths(): array
    {
        return [
            'A' => 30,
            'B' => 15,
            'C' => 15,
            'D' => 18,
            'E' => 40,
            'F' => 12,
        ];
    }

    /**
     * Styles for the sheet
     */
    public function styles(Worksheet $sheet): array
    {
        // Header row styling
        $sheet->getStyle('A1:F1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4472C4'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ]);

        // Data rows styling
        $sheet->getStyle('A2:F4')->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'D9D9D9'],
                ],
            ],
        ]);

        // Set row height for header
        $sheet->getRowDimension(1)->setRowHeight(25);

        return [];
    }
}
