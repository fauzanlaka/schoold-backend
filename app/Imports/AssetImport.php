<?php

namespace App\Imports;

use App\Models\Asset;
use App\Models\AssetCategory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithStartRow;

class AssetImport implements SkipsEmptyRows, ToCollection, WithMultipleSheets, WithStartRow
{
    protected int $schoolId;

    protected int $userId;

    /** @var array<int, array{row: int, errors: array<int, string>}> */
    protected array $errors = [];

    protected int $importedCount = 0;

    protected int $skippedCount = 0;

    /** @var array<string, int> */
    protected array $categoryMap = [];

    public function __construct(int $schoolId, int $userId)
    {
        $this->schoolId = $schoolId;
        $this->userId = $userId;
        $this->loadCategoryMap();
    }

    /**
     * Load category name to ID mapping for this school
     */
    protected function loadCategoryMap(): void
    {
        $categories = AssetCategory::where('school_id', $this->schoolId)
            ->where('is_active', true)
            ->get(['id', 'category_name']);

        foreach ($categories as $category) {
            $this->categoryMap[mb_strtolower(trim($category->category_name))] = $category->id;
        }
    }

    /**
     * Specify which sheets to import (only first sheet - index 0)
     *
     * @return array<int, \Maatwebsite\Excel\Concerns\ToCollection>
     */
    public function sheets(): array
    {
        return [
            0 => $this, // Only import the first sheet (รายการครุภัณฑ์)
        ];
    }

    /**
     * Start reading from row 2 (skip header row)
     */
    public function startRow(): int
    {
        return 2;
    }

    /**
     * Process the collection of rows
     */
    public function collection(Collection $rows): void
    {
        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;

            // Transform row data
            $data = $this->transformRow($row);

            // Debug log - remove after testing
            // \Log::info("Import Row {$rowNumber}", [
            //     'raw_row' => $row->toArray(),
            //     'transformed' => $data,
            // ]);

            // Skip completely empty rows (check main required fields)
            if (empty($data['asset_name']) && empty($data['category_name'])) {
                continue;
            }

            // Validate row
            $validator = $this->validateRow($data, $rowNumber);

            if ($validator->fails()) {
                $this->errors[] = [
                    'row' => $rowNumber,
                    'errors' => $validator->errors()->all(),
                ];
                $this->skippedCount++;

                continue;
            }

            // Check for duplicate asset_code (only if provided)
            if (! empty($data['asset_code']) && $this->isDuplicateCode($data['asset_code'])) {
                $this->errors[] = [
                    'row' => $rowNumber,
                    'errors' => ['รหัสครุภัณฑ์นี้มีอยู่แล้วในระบบ'],
                ];
                $this->skippedCount++;

                continue;
            }

            // Lookup category_id from category_name
            $categoryId = $this->getCategoryId($data['category_name']);
            if (! $categoryId) {
                $this->errors[] = [
                    'row' => $rowNumber,
                    'errors' => ['ไม่พบประเภทครุภัณฑ์: ' . $data['category_name']],
                ];
                $this->skippedCount++;

                continue;
            }

            // Create asset
            Asset::create([
                'school_id' => $this->schoolId,
                'asset_name' => $data['asset_name'],
                'asset_code' => ! empty($data['asset_code']) ? $data['asset_code'] : null,
                'category_id' => $categoryId,
                'acquisition_date' => $data['acquisition_date'],
                'unit_price' => $data['unit_price'],
                'quantity' => $data['quantity'],
                'depreciation_rate' => $data['depreciation_rate'],
                'useful_life_years' => $data['useful_life_years'] ?: null,
                'gfmis_number' => $data['gfmis_number'] ?: null,
                'document_number' => $data['document_number'] ?: null,
                'budget_type' => $data['budget_type'] ?: null,
                'acquisition_method' => $data['acquisition_method'] ?: null,
                'supplier_name' => $data['supplier_name'] ?: null,
                'supplier_phone' => $data['supplier_phone'] ?: null,
                'notes' => $data['notes'] ?: null,
                'status' => Asset::STATUS_ACTIVE,
                'created_by' => $this->userId,
                'updated_by' => $this->userId,
                'created_date' => now(),
                'updated_date' => now(),
            ]);

            $this->importedCount++;
        }
    }

    /**
     * Transform row data from Excel using index-based column access
     * Column order: 0=ชื่อ, 1=รหัส, 2=ประเภท, 3=วันที่ได้มา, 4=ราคา, 5=จำนวน, 6=อัตราค่าเสื่อม,
     *               7=อายุใช้งาน, 8=เลขGFMIS, 9=เลขเอกสาร, 10=ประเภทงบ, 11=วิธีจัดหา, 12=ผู้จำหน่าย, 13=เบอร์โทร, 14=หมายเหตุ
     *
     * @return array<string, mixed>
     */
    protected function transformRow(Collection $row): array
    {
        $rowArray = $row->toArray();

        // Helper to get string value from mixed types
        $getString = function ($index) use ($rowArray): string {
            if (! isset($rowArray[$index]) || $rowArray[$index] === null) {
                return '';
            }
            $value = $rowArray[$index];
            if (is_string($value)) {
                return trim($value);
            }
            // Handle numeric values (Excel might read numbers as floats/integers)
            if (is_numeric($value)) {
                return trim((string) $value);
            }

            return '';
        };

        return [
            'asset_name' => $getString(0),
            'asset_code' => $getString(1),
            'category_name' => $getString(2),
            'acquisition_date' => $this->parseDate($rowArray[3] ?? null),
            'unit_price' => (float) ($rowArray[4] ?? 0),
            'quantity' => (int) ($rowArray[5] ?? 1),
            'depreciation_rate' => (float) ($rowArray[6] ?? 0),
            'useful_life_years' => (int) ($rowArray[7] ?? 0),
            'gfmis_number' => $getString(8),
            'document_number' => $getString(9),
            'budget_type' => $this->parseBudgetType($rowArray[10] ?? null),
            'acquisition_method' => $this->parseAcquisitionMethod($rowArray[11] ?? null),
            'supplier_name' => $getString(12),
            'supplier_phone' => $getString(13),
            'notes' => $getString(14),
        ];
    }

    /**
     * Parse date from Excel (can be numeric Excel date or string)
     */
    protected function parseDate(mixed $value): ?string
    {
        if (empty($value)) {
            return null;
        }

        // Excel numeric date (serial number)
        if (is_numeric($value)) {
            try {
                return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((int) $value)->format('Y-m-d');
            } catch (\Exception $e) {
                return null;
            }
        }

        $value = trim((string) $value);

        // Try parsing DD/MM/YYYY or D/M/YYYY format (Thai format)
        if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $value, $matches)) {
            $day = (int) $matches[1];
            $month = (int) $matches[2];
            $year = (int) $matches[3];

            // Convert Buddhist year to Christian year if needed
            if ($year > 2500) {
                $year -= 543;
            }

            return sprintf('%04d-%02d-%02d', $year, $month, $day);
        }

        // Try parsing YYYY-MM-DD format
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value)) {
            return $value;
        }

        // Fallback to Carbon parsing
        try {
            return \Carbon\Carbon::parse($value)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Parse budget type from text/number
     */
    protected function parseBudgetType(mixed $value): ?int
    {
        if (empty($value)) {
            return null;
        }

        if (is_numeric($value)) {
            return (int) $value;
        }

        $map = [
            'เงินงบประมาณ' => 1,
            'เงินนอกงบประมาณ' => 2,
            'เงินบริจาค' => 3,
            'อื่นๆ' => 4,
        ];

        return $map[trim((string) $value)] ?? null;
    }

    /**
     * Parse acquisition method from text/number
     */
    protected function parseAcquisitionMethod(mixed $value): ?int
    {
        if (empty($value)) {
            return null;
        }

        if (is_numeric($value)) {
            return (int) $value;
        }

        $map = [
            'วิธีเฉพาะเจาะจง' => 1,
            'วิธีคัดเลือก' => 2,
            'วิธีสอบราคา' => 3,
            'วิธีพิเศษ' => 4,
            'รับบริจาค' => 5,
        ];

        return $map[trim((string) $value)] ?? null;
    }

    /**
     * Get category ID from category name
     */
    protected function getCategoryId(string $categoryName): ?int
    {
        $key = mb_strtolower(trim($categoryName));

        return $this->categoryMap[$key] ?? null;
    }

    /**
     * Check if asset code already exists
     */
    protected function isDuplicateCode(string $assetCode): bool
    {
        return Asset::where('school_id', $this->schoolId)
            ->where('asset_code', $assetCode)
            ->exists();
    }

    /**
     * Validate a single row
     *
     * @param array<string, mixed> $data
     */
    protected function validateRow(array $data, int $rowNumber): \Illuminate\Validation\Validator
    {
        return Validator::make($data, [
            'asset_name' => 'required|string|max:255',
            'asset_code' => 'nullable|string|max:100',
            'category_name' => 'required|string|max:255',
            'acquisition_date' => 'required|date',
            'unit_price' => 'required|numeric|min:0',
            'quantity' => 'required|integer|min:1',
            'depreciation_rate' => 'required|numeric|min:0|max:100',
            'gfmis_number' => 'nullable|string|max:100',
            'document_number' => 'nullable|string|max:100',
            'budget_type' => 'nullable|integer|in:1,2,3,4',
            'acquisition_method' => 'nullable|integer|in:1,2,3,4,5',
            'supplier_name' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:2000',
        ], [
            'asset_name.required' => 'กรุณากรอกชื่อครุภัณฑ์',
            'category_name.required' => 'กรุณากรอกประเภทครุภัณฑ์',
            'acquisition_date.required' => 'กรุณาระบุวันที่ได้มา',
            'acquisition_date.date' => 'รูปแบบวันที่ไม่ถูกต้อง',
            'unit_price.required' => 'กรุณากรอกราคาต่อหน่วย',
            'quantity.required' => 'กรุณากรอกจำนวน',
            'depreciation_rate.required' => 'กรุณากรอกอัตราค่าเสื่อม (%)',
        ]);
    }

    /**
     * @return array<int, array{row: int, errors: array<int, string>}>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getImportedCount(): int
    {
        return $this->importedCount;
    }

    public function getSkippedCount(): int
    {
        return $this->skippedCount;
    }

    public function hasErrors(): bool
    {
        return count($this->errors) > 0;
    }
}
