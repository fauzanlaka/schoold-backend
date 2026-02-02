<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetCategory;
use App\Services\DepreciationService;
use App\Services\UserSchoolService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AssetReportController extends Controller
{
    public function __construct(
        private UserSchoolService $userSchoolService
    ) {}

    /**
     * Get the current user's school
     */
    private function getUserSchool()
    {
        return $this->userSchoolService->getSchool();
    }

    /**
     * Get budget type label
     */
    private function getBudgetTypeLabel($type): string
    {
        return match ($type) {
            1 => 'เงินงบประมาณ',
            2 => 'เงินนอกงบประมาณ',
            3 => 'เงินบริจาค/เงินช่วยเหลือ',
            4 => 'อื่นๆ',
            default => 'ไม่ระบุ',
        };
    }

    /**
     * Get acquisition method label
     */
    private function getAcquisitionMethodLabel($method): string
    {
        return match ($method) {
            1 => 'วิธีเฉพาะเจาะจง',
            2 => 'วิธีคัดเลือก',
            3 => 'วิธีสอบราคา',
            4 => 'วิธีพิเศษ',
            5 => 'รับบริจาค',
            default => 'ไม่ระบุ',
        };
    }

    /**
     * Get status label
     */
    private function getStatusLabel($status): string
    {
        return match ($status) {
            1 => 'ใช้งานอยู่',
            2 => 'ไม่ได้ใช้งาน',
            3 => 'จำหน่าย',
            4 => 'กำลังซ่อมแซม',
            5 => 'ไม่ทราบสถานะ',
            default => 'ไม่ระบุ',
        };
    }

    /**
     * Return empty category breakdown response
     */
    private function emptyCategoryResponse(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'items' => [],
                'summary' => [
                    'total_categories' => 0,
                    'total_assets' => 0,
                    'total_original_value' => 0,
                    'total_accumulated_depreciation' => 0,
                    'total_book_value' => 0,
                ],
            ],
        ]);
    }

    /**
     * Return empty depreciation response
     */
    private function emptyDepreciationResponse(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'items' => [],
                'summary' => [
                    'total_assets' => 0,
                    'total_original_value' => 0,
                    'total_annual_depreciation' => 0,
                    'total_accumulated_depreciation' => 0,
                    'total_book_value' => 0,
                    'fully_depreciated_count' => 0,
                ],
            ],
        ]);
    }

    /**
     * Return empty status response
     */
    private function emptyStatusResponse(): JsonResponse
    {
        $report = [];
        for ($status = 1; $status <= 5; $status++) {
            $report[] = [
                'status' => $status,
                'status_label' => $this->getStatusLabel($status),
                'count' => 0,
                'total_value' => 0,
                'percentage' => 0,
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'items' => $report,
                'summary' => [
                    'total_assets' => 0,
                    'total_value' => 0,
                ],
            ],
        ]);
    }

    /**
     * Return empty acquisition method response
     */
    private function emptyAcquisitionResponse(): JsonResponse
    {
        $report = [];
        for ($method = 1; $method <= 5; $method++) {
            $report[] = [
                'method' => $method,
                'method_label' => $this->getAcquisitionMethodLabel($method),
                'count' => 0,
                'total_value' => 0,
                'percentage' => 0,
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'items' => $report,
                'summary' => [
                    'total_assets' => 0,
                    'total_value' => 0,
                ],
            ],
        ]);
    }

    /**
     * Return empty budget type response
     */
    private function emptyBudgetResponse(): JsonResponse
    {
        $report = [];
        for ($type = 1; $type <= 4; $type++) {
            $report[] = [
                'type' => $type,
                'type_label' => $this->getBudgetTypeLabel($type),
                'count' => 0,
                'total_value' => 0,
                'percentage' => 0,
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'items' => $report,
                'summary' => [
                    'total_assets' => 0,
                    'total_value' => 0,
                ],
            ],
        ]);
    }

    /**
     * Return empty expiring assets response
     */
    private function emptyExpiringResponse(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'fully_depreciated' => [
                    'items' => [],
                    'count' => 0,
                ],
                'low_value' => [
                    'items' => [],
                    'count' => 0,
                ],
                'nearing_end_of_life' => [
                    'items' => [],
                    'count' => 0,
                ],
                'summary' => [
                    'total_expiring' => 0,
                    'fully_depreciated_count' => 0,
                    'low_value_count' => 0,
                    'nearing_end_count' => 0,
                ],
            ],
        ]);
    }

    /**
     * Category breakdown report - summary by category
     */
    public function categoryBreakdown(): JsonResponse
    {
        $school = $this->getUserSchool();

        if (! $school) {
            return $this->emptyCategoryResponse();
        }

        $depreciationService = app(DepreciationService::class);

        $categories = AssetCategory::where('school_id', $school->id)
            ->where('is_active', true)
            ->withCount('assets')
            ->get();

        $report = [];
        $totalOriginalValue = 0;
        $totalAccumulatedDepreciation = 0;
        $totalBookValue = 0;
        $totalAssetCount = 0;

        foreach ($categories as $category) {
            $assets = Asset::where('school_id', $school->id)
                ->where('category_id', $category->id)
                ->get();

            $categoryOriginalValue = 0;
            $categoryAccumulated = 0;
            $categoryBookValue = 0;

            foreach ($assets as $asset) {
                $totalValue = $asset->unit_price * $asset->quantity;
                $categoryOriginalValue += $totalValue;

                $depreciation = $depreciationService->calculate($asset);
                if ($depreciation['can_calculate'] && ! empty($depreciation['rows'])) {
                    $lastRow = end($depreciation['rows']);
                    $categoryAccumulated += $lastRow['accumulated'];
                    $categoryBookValue += max(1, $lastRow['netValue']);
                } else {
                    $categoryBookValue += $totalValue;
                }
            }

            $report[] = [
                'category_id' => $category->id,
                'category_name' => $category->category_name,
                'category_code' => $category->category_code,
                'asset_count' => $category->assets_count,
                'original_value' => $categoryOriginalValue,
                'accumulated_depreciation' => $categoryAccumulated,
                'book_value' => $categoryBookValue,
            ];

            $totalOriginalValue += $categoryOriginalValue;
            $totalAccumulatedDepreciation += $categoryAccumulated;
            $totalBookValue += $categoryBookValue;
            $totalAssetCount += $category->assets_count;
        }

        // Calculate percentages
        foreach ($report as &$item) {
            $item['percentage'] = $totalOriginalValue > 0
                ? round(($item['original_value'] / $totalOriginalValue) * 100, 2)
                : 0;
        }

        return response()->json([
            'success' => true,
            'data' => [
                'items' => $report,
                'summary' => [
                    'total_categories' => count($report),
                    'total_assets' => $totalAssetCount,
                    'total_original_value' => $totalOriginalValue,
                    'total_accumulated_depreciation' => $totalAccumulatedDepreciation,
                    'total_book_value' => $totalBookValue,
                ],
            ],
        ]);
    }

    /**
     * Depreciation report - detailed depreciation for all assets
     */
    public function depreciationReport(Request $request): JsonResponse
    {
        $school = $this->getUserSchool();

        if (! $school) {
            return $this->emptyDepreciationResponse();
        }

        $depreciationService = app(DepreciationService::class);

        $query = Asset::where('school_id', $school->id)
            ->with('category')
            ->orderBy('acquisition_date', 'desc');

        // Optional filters
        if ($request->has('category_id') && $request->category_id) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        $assets = $query->get();

        $report = [];
        $totalOriginalValue = 0;
        $totalAnnualDepreciation = 0;
        $totalAccumulated = 0;
        $totalBookValue = 0;

        foreach ($assets as $asset) {
            $depreciation = $depreciationService->calculate($asset);
            $totalValue = $asset->unit_price * $asset->quantity;

            $accumulated = 0;
            $bookValue = $totalValue;
            $isFullyDepreciated = false;

            if ($depreciation['can_calculate'] && ! empty($depreciation['rows'])) {
                $currentDate = Carbon::now();
                $latestApplicableRow = null;

                foreach ($depreciation['rows'] as $row) {
                    // Parse the Thai date format (dd/m/yy)
                    $parts = explode('/', $row['date']);
                    if (count($parts) === 3) {
                        $year = 2000 + intval($parts[2]) - 543 + 2000;
                        $rowDate = Carbon::createFromDate($year, $parts[1], $parts[0]);

                        if ($rowDate->lte($currentDate)) {
                            $latestApplicableRow = $row;
                        }
                    }
                }

                if ($latestApplicableRow) {
                    $accumulated = $latestApplicableRow['accumulated'];
                    $bookValue = max(1, $latestApplicableRow['netValue']);
                }

                $lastRow = end($depreciation['rows']);
                $isFullyDepreciated = $accumulated >= $totalValue - 1;
            }

            $report[] = [
                'id' => $asset->id,
                'asset_code' => $asset->asset_code,
                'asset_name' => $asset->asset_name,
                'category_name' => $asset->category?->category_name ?? '-',
                'acquisition_date' => $asset->acquisition_date,
                'original_value' => $totalValue,
                'useful_life_years' => $depreciation['useful_life'],
                'depreciation_rate' => $depreciation['depreciation_rate'],
                'annual_depreciation' => $depreciation['annual_depreciation'],
                'accumulated_depreciation' => $accumulated,
                'book_value' => $bookValue,
                'is_fully_depreciated' => $isFullyDepreciated,
                'status' => $asset->status,
                'status_label' => $this->getStatusLabel($asset->status),
            ];

            $totalOriginalValue += $totalValue;
            $totalAnnualDepreciation += $depreciation['annual_depreciation'];
            $totalAccumulated += $accumulated;
            $totalBookValue += $bookValue;
        }

        return response()->json([
            'success' => true,
            'data' => [
                'items' => $report,
                'summary' => [
                    'total_assets' => count($report),
                    'total_original_value' => $totalOriginalValue,
                    'total_annual_depreciation' => $totalAnnualDepreciation,
                    'total_accumulated_depreciation' => $totalAccumulated,
                    'total_book_value' => $totalBookValue,
                    'fully_depreciated_count' => collect($report)->where('is_fully_depreciated', true)->count(),
                ],
            ],
        ]);
    }

    /**
     * Status report - assets grouped by status
     */
    public function statusReport(): JsonResponse
    {
        $school = $this->getUserSchool();

        if (! $school) {
            return $this->emptyStatusResponse();
        }

        $assets = Asset::where('school_id', $school->id)
            ->selectRaw('status, COUNT(*) as count, SUM(unit_price * quantity) as total_value')
            ->groupBy('status')
            ->get();

        $report = [];
        $totalCount = 0;
        $totalValue = 0;

        for ($status = 1; $status <= 5; $status++) {
            $item = $assets->firstWhere('status', $status);
            $count = $item?->count ?? 0;
            $value = $item?->total_value ?? 0;

            $report[] = [
                'status' => $status,
                'status_label' => $this->getStatusLabel($status),
                'count' => $count,
                'total_value' => $value,
            ];

            $totalCount += $count;
            $totalValue += $value;
        }

        // Calculate percentages
        foreach ($report as &$item) {
            $item['percentage'] = $totalCount > 0
                ? round(($item['count'] / $totalCount) * 100, 2)
                : 0;
        }

        return response()->json([
            'success' => true,
            'data' => [
                'items' => $report,
                'summary' => [
                    'total_assets' => $totalCount,
                    'total_value' => $totalValue,
                ],
            ],
        ]);
    }

    /**
     * Acquisition method report - assets grouped by acquisition method
     */
    public function acquisitionMethodReport(): JsonResponse
    {
        $school = $this->getUserSchool();

        if (! $school) {
            return $this->emptyAcquisitionResponse();
        }

        $assets = Asset::where('school_id', $school->id)
            ->selectRaw('acquisition_method, COUNT(*) as count, SUM(unit_price * quantity) as total_value')
            ->groupBy('acquisition_method')
            ->get();

        $report = [];
        $totalCount = 0;
        $totalValue = 0;

        for ($method = 1; $method <= 5; $method++) {
            $item = $assets->firstWhere('acquisition_method', $method);
            $count = $item?->count ?? 0;
            $value = $item?->total_value ?? 0;

            $report[] = [
                'method' => $method,
                'method_label' => $this->getAcquisitionMethodLabel($method),
                'count' => $count,
                'total_value' => $value,
            ];

            $totalCount += $count;
            $totalValue += $value;
        }

        // Calculate percentages
        foreach ($report as &$item) {
            $item['percentage'] = $totalCount > 0
                ? round(($item['count'] / $totalCount) * 100, 2)
                : 0;
        }

        return response()->json([
            'success' => true,
            'data' => [
                'items' => $report,
                'summary' => [
                    'total_assets' => $totalCount,
                    'total_value' => $totalValue,
                ],
            ],
        ]);
    }

    /**
     * Budget type report - assets grouped by budget type
     */
    public function budgetTypeReport(): JsonResponse
    {
        $school = $this->getUserSchool();

        if (! $school) {
            return $this->emptyBudgetResponse();
        }

        $assets = Asset::where('school_id', $school->id)
            ->selectRaw('budget_type, COUNT(*) as count, SUM(unit_price * quantity) as total_value')
            ->groupBy('budget_type')
            ->get();

        $report = [];
        $totalCount = 0;
        $totalValue = 0;

        for ($type = 1; $type <= 4; $type++) {
            $item = $assets->firstWhere('budget_type', $type);
            $count = $item?->count ?? 0;
            $value = $item?->total_value ?? 0;

            $report[] = [
                'type' => $type,
                'type_label' => $this->getBudgetTypeLabel($type),
                'count' => $count,
                'total_value' => $value,
            ];

            $totalCount += $count;
            $totalValue += $value;
        }

        // Calculate percentages
        foreach ($report as &$item) {
            $item['percentage'] = $totalCount > 0
                ? round(($item['count'] / $totalCount) * 100, 2)
                : 0;
        }

        return response()->json([
            'success' => true,
            'data' => [
                'items' => $report,
                'summary' => [
                    'total_assets' => $totalCount,
                    'total_value' => $totalValue,
                ],
            ],
        ]);
    }

    /**
     * Expiring assets report - assets with low book value or nearing end of useful life
     */
    public function expiringAssets(): JsonResponse
    {
        $school = $this->getUserSchool();

        if (! $school) {
            return $this->emptyExpiringResponse();
        }

        $depreciationService = app(DepreciationService::class);

        $assets = Asset::where('school_id', $school->id)
            ->where('status', 1) // Only active assets
            ->with('category')
            ->orderBy('acquisition_date', 'asc')
            ->get();

        $lowValueAssets = [];
        $fullyDepreciatedAssets = [];
        $nearingEndOfLifeAssets = [];

        foreach ($assets as $asset) {
            $depreciation = $depreciationService->calculate($asset);
            $totalValue = $asset->unit_price * $asset->quantity;

            if (! $depreciation['can_calculate']) {
                continue;
            }

            $accumulated = 0;
            $bookValue = $totalValue;

            if (! empty($depreciation['rows'])) {
                $currentDate = Carbon::now();
                $latestApplicableRow = null;

                foreach ($depreciation['rows'] as $row) {
                    $parts = explode('/', $row['date']);
                    if (count($parts) === 3) {
                        $year = 2000 + intval($parts[2]) - 543 + 2000;
                        $rowDate = Carbon::createFromDate($year, $parts[1], $parts[0]);

                        if ($rowDate->lte($currentDate)) {
                            $latestApplicableRow = $row;
                        }
                    }
                }

                if ($latestApplicableRow) {
                    $accumulated = $latestApplicableRow['accumulated'];
                    $bookValue = max(1, $latestApplicableRow['netValue']);
                }
            }

            $percentRemaining = $totalValue > 0 ? ($bookValue / $totalValue) * 100 : 0;
            $usefulLife = $depreciation['useful_life'];
            $acquisitionDate = Carbon::parse($asset->acquisition_date);
            $yearsUsed = $acquisitionDate->diffInYears(Carbon::now());
            $yearsRemaining = max(0, $usefulLife - $yearsUsed);

            $assetData = [
                'id' => $asset->id,
                'asset_code' => $asset->asset_code,
                'asset_name' => $asset->asset_name,
                'category_name' => $asset->category?->category_name ?? '-',
                'acquisition_date' => $asset->acquisition_date,
                'original_value' => $totalValue,
                'book_value' => $bookValue,
                'percent_remaining' => round($percentRemaining, 2),
                'useful_life_years' => $usefulLife,
                'years_used' => $yearsUsed,
                'years_remaining' => $yearsRemaining,
            ];

            // Fully depreciated (book value <= 1)
            if ($bookValue <= 1) {
                $fullyDepreciatedAssets[] = $assetData;
            }
            // Low value (< 20% remaining)
            elseif ($percentRemaining < 20) {
                $lowValueAssets[] = $assetData;
            }
            // Nearing end of life (within 1 year)
            elseif ($yearsRemaining <= 1 && $yearsRemaining > 0) {
                $nearingEndOfLifeAssets[] = $assetData;
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'fully_depreciated' => [
                    'items' => $fullyDepreciatedAssets,
                    'count' => count($fullyDepreciatedAssets),
                ],
                'low_value' => [
                    'items' => $lowValueAssets,
                    'count' => count($lowValueAssets),
                ],
                'nearing_end_of_life' => [
                    'items' => $nearingEndOfLifeAssets,
                    'count' => count($nearingEndOfLifeAssets),
                ],
                'summary' => [
                    'total_expiring' => count($fullyDepreciatedAssets) + count($lowValueAssets) + count($nearingEndOfLifeAssets),
                    'fully_depreciated_count' => count($fullyDepreciatedAssets),
                    'low_value_count' => count($lowValueAssets),
                    'nearing_end_count' => count($nearingEndOfLifeAssets),
                ],
            ],
        ]);
    }
}
