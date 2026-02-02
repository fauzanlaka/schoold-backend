<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AssetListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     * This resource is optimized for listing assets with minimal data.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            // 'id' => $this->id,
            'encrypted_id' => $this->encrypted_id,
            'asset_name' => $this->asset_name,
            'asset_code' => $this->asset_code,
            'gfmis_number' => $this->gfmis_number,
            'acquisition_date' => $this->acquisition_date,
            'unit_price' => $this->unit_price,
            'quantity' => $this->quantity,
            'total_price' => $this->total_price,
            'book_value' => $this->book_value,
            'status' => $this->status,
            'status_label' => $this->status_label,
            'category' => $this->whenLoaded('category', function () {
                return [
                    'id' => $this->category->id,
                    'category_name' => $this->category->category_name,
                    'category_code' => $this->category->category_code,
                ];
            }),
            'creator' => $this->whenLoaded('creator', function () {
                return [
                    // 'id' => $this->creator->id,
                    'name' => $this->creator->name,
                ];
            }),
            'created_at' => $this->created_at,
        ];
    }
}
