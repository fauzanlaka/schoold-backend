<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AssetSummaryResource extends JsonResource
{
    /**
     * Indicates that the resource's collection should not be wrapped.
     */
    public static $wrap = null;

    /**
     * Transform the resource into an array.
     * This resource is used for dashboard summary statistics.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'total_assets' => $this->resource['total_assets'],
            'status_counts' => $this->formatStatusCounts($this->resource['status_counts']),
            'total_value' => round($this->resource['total_value'], 2),
            'category_stats' => AssetCategoryStatResource::collection($this->resource['category_stats']),
        ];
    }

    /**
     * Format status counts with Thai labels
     */
    private function formatStatusCounts($statusCounts): array
    {
        $labels = [
            1 => 'ใช้งานอยู่',
            2 => 'ไม่ได้ใช้งาน',
            3 => 'จำหน่าย',
            4 => 'กำลังซ่อมแซม',
            5 => 'ไม่ทราบสถานะ',
        ];

        $result = [];
        foreach ($statusCounts as $status => $count) {
            $result[] = [
                'status' => $status,
                'label' => $labels[$status] ?? 'ไม่ระบุ',
                'count' => $count,
            ];
        }

        return $result;
    }
}
