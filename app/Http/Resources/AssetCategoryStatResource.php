<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AssetCategoryStatResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     * This resource is used for category statistics in summary.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'category_id' => $this->category_id,
            'category_name' => $this->whenLoaded('category', function () {
                return $this->category?->category_name;
            }),
            'count' => (int) $this->count,
            'value' => round((float) $this->value, 2),
        ];
    }
}
