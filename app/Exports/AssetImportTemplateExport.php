<?php

namespace App\Exports;

use App\Models\AssetCategory;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AssetImportTemplateExport implements WithMultipleSheets
{
    protected int $schoolId;

    public function __construct(int $schoolId)
    {
        $this->schoolId = $schoolId;
    }

    /**
     * @return array<int, object>
     */
    public function sheets(): array
    {
        return [
            new AssetDataSheet(),
            new CategoryReferenceSheet($this->schoolId),
            new NotesSheet(),
        ];
    }
}

/**
 * Sheet 1: Asset data entry
 */
class AssetDataSheet implements FromArray, ShouldAutoSize, WithStyles, WithTitle
{
    public function title(): string
    {
        return 'รายการครุภัณฑ์';
    }

    /**
     * @return array<int, array<int, string>>
     */
    public function array(): array
    {
        return [
            [
                'ชื่อครุภัณฑ์*',
                'รหัสครุภัณฑ์',
                'ประเภทครุภัณฑ์*',
                'วันที่ได้มา*',
                'ราคาต่อหน่วย*',
                'จำนวน*',
                'อัตราค่าเสื่อม(%)*',
                'อายุใช้งาน(ปี)',
                'เลข GFMIS',
                'เลขที่เอกสาร',
                'ประเภทงบประมาณ',
                'วิธีจัดหา',
                'ผู้จำหน่าย',
                'เบอร์โทรผู้จำหน่าย',
                'หมายเหตุ',
            ],
            // Sample row
            [
                'เครื่องคอมพิวเตอร์ตั้งโต๊ะ',
                'COM-001',
                'คอมพิวเตอร์',
                '2026-01-15',
                25000,
                1,
                20,
                5,
                '',
                '',
                'เงินงบประมาณ',
                'วิธีเฉพาะเจาะจง',
                'บริษัท ABC จำกัด',
                '02-123-4567',
                '',
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E2EFDA'],
                ],
            ],
        ];
    }
}

/**
 * Sheet 2: Category reference list
 */
class CategoryReferenceSheet implements FromArray, ShouldAutoSize, WithStyles, WithTitle
{
    protected int $schoolId;

    public function __construct(int $schoolId)
    {
        $this->schoolId = $schoolId;
    }

    public function title(): string
    {
        return 'ประเภทครุภัณฑ์';
    }

    /**
     * @return array<int, array<int, string|int|float|null>>
     */
    public function array(): array
    {
        $data = [
            ['ชื่อประเภท', 'รหัสประเภท', 'อายุใช้งาน (ปี)', 'อัตราค่าเสื่อม (%)'],
        ];

        $categories = AssetCategory::where('school_id', $this->schoolId)
            ->where('is_active', true)
            ->orderBy('category_name')
            ->get(['category_name', 'category_code', 'useful_life_years', 'depreciation_rate']);

        foreach ($categories as $cat) {
            $data[] = [
                $cat->category_name,
                $cat->category_code,
                $cat->useful_life_years,
                $cat->depreciation_rate,
            ];
        }

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'BDD7EE'],
                ],
            ],
        ];
    }
}

/**
 * Sheet 3: Notes
 */
class NotesSheet implements FromArray, ShouldAutoSize, WithStyles, WithTitle
{
    public function title(): string
    {
        return 'หมายเหตุ';
    }

    /**
     * @return array<int, array<int, string>>
     */
    public function array(): array
    {
        return [
            ['หมายเหตุการใช้งาน Template'],
            ['1. สัญลักษณ์ * หมายถึงข้อมูลที่จำเป็นต้องกรอก (Required Fields)'],
            ['2. การกรอกข้อมูล "ประเภทครุภัณฑ์"'],
            ['   - ให้ดูชื่อประเภทที่ถูกต้องจาก Sheet "ประเภทครุภัณฑ์"'],
            ['   - คัดลอก (Copy) ชื่อประเภทจาก Sheet นั้นมาวางเพื่อป้องกันความผิดพลาด'],
            ['3. รูปแบบวันที่'],
            ['   - กรุณาใช้รูปแบบ YYYY-MM-DD เช่น 2026-01-15'],
            ['4. ประเภทงบประมาณ'],
            ['   - เงินงบประมาณ, เงินนอกงบประมาณ, เงินบริจาค, อื่นๆ'],
            ['5. วิธีจัดหา'],
            ['   - วิธีเฉพาะเจาะจง, วิธีคัดเลือก, วิธีสอบราคา, วิธีพิเศษ, รับบริจาค'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 12],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'FFF2CC'],
                ],
            ],
        ];
    }
}
