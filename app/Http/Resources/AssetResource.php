<?php

namespace App\Http\Resources;

use App\Models\Asset;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AssetResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            // 'id' => $this->id,
            'encrypted_id' => $this->encrypted_id,
            'school_id' => $this->school_id,

            // Main information
            'asset_name' => $this->asset_name,
            'asset_code' => $this->asset_code,
            'gfmis_number' => $this->gfmis_number,
            'category_id' => $this->whenLoaded('category', fn() => $this->category->id),

            // Procurement details
            'acquisition_date' => $this->acquisition_date,
            'document_number' => $this->document_number,
            'unit_price' => $this->unit_price,
            'quantity' => $this->quantity,
            'budget_type' => $this->budget_type,
            'budget_type_label' => $this->getBudgetTypeLabel(),
            'acquisition_method' => $this->acquisition_method,
            'acquisition_method_label' => $this->getAcquisitionMethodLabel(),

            // Depreciation information
            'useful_life_years' => $this->useful_life_years,
            'effective_useful_life_years' => $this->effective_useful_life_years,
            'depreciation_rate' => $this->depreciation_rate,
            'effective_depreciation_rate' => $this->effective_depreciation_rate,

            // Computed fields
            'total_price' => $this->total_price,
            'accumulated_depreciation' => $this->accumulated_depreciation,
            'book_value' => $this->book_value,

            // Supplier details
            'supplier_name' => $this->supplier_name,
            'supplier_phone' => $this->supplier_phone,

            // Status
            'status' => $this->status,
            'status_label' => $this->status_label,
            'notes' => $this->notes,

            // Relationships
            'category' => $this->whenLoaded('category', function () {
                return [
                    'id' => $this->category->id,
                    'category_name' => $this->category->category_name,
                    'category_code' => $this->category->category_code,
                    'useful_life_years' => $this->category->useful_life_years ?? null,
                    'depreciation_rate' => $this->category->depreciation_rate ?? null,
                ];
            }),
            'creator' => $this->whenLoaded('creator', function () {
                return [
                    // 'id' => $this->creator->id,
                    'name' => $this->creator->name,
                ];
            }),
            'updater' => $this->whenLoaded('updater', function () {
                return [
                    'name' => $this->updater->name,
                ];
            }),

            // Timestamps
            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,
            'created_date' => $this->created_date,
            'updated_date' => $this->updated_date,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    /**
     * Get budget type label in Thai
     */
    private function getBudgetTypeLabel(): ?string
    {
        $labels = [
            Asset::BUDGET_GOVERNMENT => 'เงินงบประมาณ',
            Asset::BUDGET_NON_GOVERNMENT => 'เงินนอกงบประมาณ',
            Asset::BUDGET_DONATION => 'เงินบริจาค/เงินช่วยเหลือ',
            Asset::BUDGET_OTHER => 'อื่นๆ',
        ];

        return $labels[$this->budget_type] ?? null;
    }

    /**
     * Get acquisition method label in Thai
     */
    private function getAcquisitionMethodLabel(): ?string
    {
        $labels = [
            Asset::ACQUISITION_SPECIFIC => 'วิธีเฉพาะเจาะจง',
            Asset::ACQUISITION_SELECTION => 'วิธีคัดเลือก',
            Asset::ACQUISITION_BIDDING => 'วิธีสอบราคา',
            Asset::ACQUISITION_SPECIAL => 'วิธีพิเศษ',
            Asset::ACQUISITION_DONATION => 'รับบริจาค',
        ];

        return $labels[$this->acquisition_method] ?? null;
    }
}
