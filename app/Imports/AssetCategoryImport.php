<?php

namespace App\Imports;

use App\Models\AssetCategory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithStartRow;

class AssetCategoryImport implements SkipsEmptyRows, ToCollection, WithStartRow
{
    protected int $schoolId;

    protected int $userId;

    protected array $errors = [];

    protected int $importedCount = 0;

    protected int $skippedCount = 0;

    public function __construct(int $schoolId, int $userId)
    {
        $this->schoolId = $schoolId;
        $this->userId = $userId;
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
            $rowNumber = $index + 2; // +2 because index starts at 0 and we skip header row

            // แปลงค่าจาก Excel
            $data = $this->transformRow($row);

            // Validate each row
            $validator = $this->validateRow($data, $rowNumber);

            if ($validator->fails()) {
                $this->errors[] = [
                    'row' => $rowNumber,
                    'errors' => $validator->errors()->all(),
                ];
                $this->skippedCount++;

                continue;
            }

            // ตรวจสอบ unique ภายใน school
            if ($this->isDuplicate($data)) {
                $this->errors[] = [
                    'row' => $rowNumber,
                    'errors' => ['ชื่อหรือรหัสประเภทครุภัณฑ์นี้มีอยู่แล้วในระบบ'],
                ];
                $this->skippedCount++;

                continue;
            }

            // สร้าง category
            AssetCategory::create([
                'school_id' => $this->schoolId,
                'category_name' => $data['category_name'],
                'category_code' => $data['category_code'] ?: null,
                'useful_life_years' => $data['useful_life_years'],
                'depreciation_rate' => $data['depreciation_rate'],
                'description' => $data['description'] ?: null,
                'is_active' => $data['is_active'],
                'created_by' => $this->userId,
                'updated_by' => $this->userId,
            ]);

            $this->importedCount++;
        }
    }

    /**
     * Transform row data from Excel using index-based column access
     * Column order: 0=ชื่อประเภท, 1=รหัสประเภท, 2=อายุใช้งาน, 3=อัตราค่าเสื่อม, 4=รายละเอียด, 5=สถานะ
     *
     * @param  mixed  $row
     * @return array<string, mixed>
     */
    protected function transformRow($row): array
    {
        // Convert row to array for index access
        $rowArray = $row->toArray();

        // Use index-based access (0-indexed columns)
        return [
            'category_name' => isset($rowArray[0]) && is_string($rowArray[0]) ? trim($rowArray[0]) : '',
            'category_code' => isset($rowArray[1]) && is_string($rowArray[1]) ? trim($rowArray[1]) : '',
            'useful_life_years' => (int) ($rowArray[2] ?? 0),
            'depreciation_rate' => (float) ($rowArray[3] ?? 0),
            'description' => isset($rowArray[4]) && is_string($rowArray[4]) ? trim($rowArray[4]) : '',
            'is_active' => $this->parseBoolean($rowArray[5] ?? true),
        ];
    }

    /**
     * Get value from row by trying multiple keys
     *
     * @param  array<mixed>  $row
     * @param  array<string|int>  $keys
     * @param  mixed  $default
     * @return mixed
     */
    protected function getRowValue(array $row, array $keys, $default = null)
    {
        foreach ($keys as $key) {
            if (isset($row[$key]) && $row[$key] !== null && $row[$key] !== '') {
                return $row[$key];
            }
        }

        return $default;
    }

    /**
     * Parse boolean value from various formats
     *
     * @param  mixed  $value
     */
    protected function parseBoolean($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        $falseValues = ['0', 'false', 'no', 'ไม่', 'ไม่ใช้งาน', 'ปิด'];

        return ! in_array(strtolower(trim((string) $value)), $falseValues, true);
    }

    /**
     * Validate a single row
     *
     * @param  array<string, mixed>  $data
     */
    protected function validateRow(array $data, int $rowNumber): \Illuminate\Validation\Validator
    {
        return Validator::make($data, [
            'category_name' => 'required|string|max:255',
            'category_code' => 'nullable|string|max:50',
            'useful_life_years' => 'required|integer|min:1|max:100',
            'depreciation_rate' => 'required|numeric|min:0|max:100',
            'description' => 'nullable|string|max:1000',
            'is_active' => 'boolean',
        ], [
            'category_name.required' => 'กรุณากรอกชื่อประเภทครุภัณฑ์',
            'category_name.max' => 'ชื่อประเภทต้องไม่เกิน 255 ตัวอักษร',
            'category_code.max' => 'รหัสประเภทต้องไม่เกิน 50 ตัวอักษร',
            'useful_life_years.required' => 'กรุณากรอกอายุการใช้งาน',
            'useful_life_years.integer' => 'อายุการใช้งานต้องเป็นจำนวนเต็ม',
            'useful_life_years.min' => 'อายุการใช้งานต้องอย่างน้อย 1 ปี',
            'useful_life_years.max' => 'อายุการใช้งานต้องไม่เกิน 100 ปี',
            'depreciation_rate.required' => 'กรุณากรอกอัตราค่าเสื่อมราคา',
            'depreciation_rate.numeric' => 'อัตราค่าเสื่อมราคาต้องเป็นตัวเลข',
            'depreciation_rate.min' => 'อัตราค่าเสื่อมราคาต้องไม่น้อยกว่า 0',
            'depreciation_rate.max' => 'อัตราค่าเสื่อมราคาต้องไม่เกิน 100',
            'description.max' => 'รายละเอียดต้องไม่เกิน 1000 ตัวอักษร',
        ]);
    }

    /**
     * Check if category name or code already exists
     *
     * @param  array<string, mixed>  $data
     */
    protected function isDuplicate(array $data): bool
    {
        $query = AssetCategory::where('school_id', $this->schoolId)
            ->where('category_name', $data['category_name']);

        if ($query->exists()) {
            return true;
        }

        if (! empty($data['category_code'])) {
            $codeExists = AssetCategory::where('school_id', $this->schoolId)
                ->where('category_code', $data['category_code'])
                ->exists();
            if ($codeExists) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get import errors
     *
     * @return array<int, array{row: int, errors: array<int, string>}>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Get imported count
     */
    public function getImportedCount(): int
    {
        return $this->importedCount;
    }

    /**
     * Get skipped count
     */
    public function getSkippedCount(): int
    {
        return $this->skippedCount;
    }

    /**
     * Check if import has errors
     */
    public function hasErrors(): bool
    {
        return count($this->errors) > 0;
    }
}
