<?php

namespace App\Services;

use App\Models\Asset;
use Carbon\Carbon;

class DepreciationService
{
    /**
     * Calculate depreciation schedule for an asset
     *
     * @param Asset $asset The asset to calculate depreciation for
     * @return array Depreciation calculation results
     */
    public function calculate(Asset $asset): array
    {
        // Get effective values (from asset or category)
        $usefulLife = $asset->useful_life_years ?? $asset->category->useful_life_years ?? 5;
        $depreciationRate = $asset->depreciation_rate ?? $asset->category->depreciation_rate ?? 20;
        $totalValue = $asset->unit_price * $asset->quantity;

        // Check if we can calculate depreciation
        if (!$asset->acquisition_date || $usefulLife <= 0 || $totalValue <= 0) {
            return [
                'can_calculate' => false,
                'useful_life' => $usefulLife,
                'depreciation_rate' => $depreciationRate,
                'total_value' => $totalValue,
                'annual_depreciation' => 0,
                'monthly_depreciation' => 0,
                'rows' => [],
            ];
        }

        $purchaseDate = Carbon::parse($asset->acquisition_date);
        $annualDepreciation = $totalValue / $usefulLife;
        $monthlyDepreciation = $annualDepreciation / 12;

        // Calculate first fiscal year and months
        // Note: Carbon month is 1-indexed (1-12)
        $startMonth = $purchaseDate->month - 1; // Convert to 0-indexed (0-11) like JavaScript

        if ($purchaseDate->day > 15) {
            $startMonth++;
        }

        // Thai fiscal year ends September 30 (month index 8 in 0-indexed)
        $firstFiscalYear = $purchaseDate->year;
        if ($startMonth > 8) { // After September
            $firstFiscalYear++;
        }

        // Calculate months in first period (until September 30)
        if ($startMonth > 8) {
            // Started after September, count months to next September
            $firstPeriodMonths = (12 - $startMonth) + 9;
        } else {
            // Started before or during September
            $firstPeriodMonths = 9 - $startMonth;
        }

        // Ensure at least 1 month
        $firstPeriodMonths = max(1, $firstPeriodMonths);

        $depreciationRows = [];
        $accumulatedDepreciation = 0;

        // First period row
        $firstPeriodDepreciation = $monthlyDepreciation * $firstPeriodMonths;
        $accumulatedDepreciation += $firstPeriodDepreciation;
        $netBookValue = $totalValue - $accumulatedDepreciation;

        $depreciationRows[] = [
            'date' => '30/9/' . $this->toBuddhistYear($firstFiscalYear),
            'description' => "คิดค่าเสื่อมราคา {$firstPeriodMonths} เดือน",
            'depreciation' => $firstPeriodDepreciation,
            'accumulated' => $accumulatedDepreciation,
            'netValue' => $netBookValue,
        ];

        // Calculate remaining months and full years
        $remainingMonths = ($usefulLife * 12) - $firstPeriodMonths;
        $fullYears = (int) floor($remainingMonths / 12);

        // Full years rows
        for ($i = 0; $i < $fullYears; $i++) {
            $accumulatedDepreciation += $annualDepreciation;
            $netBookValue = $totalValue - $accumulatedDepreciation;

            // Check if this is the last year
            $isLastYear = $i === $fullYears - 1 && $remainingMonths % 12 === 0;

            $depreciationRows[] = [
                'date' => '30/9/' . $this->toBuddhistYear($firstFiscalYear + $i + 1),
                'description' => 'คิดค่าเสื่อมราคา 1 ปี',
                'depreciation' => $annualDepreciation,
                'accumulated' => $isLastYear ? $totalValue : $accumulatedDepreciation,
                'netValue' => $isLastYear ? 1 : $netBookValue,
            ];
        }

        // Last period row (if there are remaining months)
        $lastPeriodMonths = $remainingMonths % 12;
        if ($lastPeriodMonths > 0) {
            // Final year - set book value to 1 baht
            $lastDepreciation = $netBookValue;

            $depreciationRows[] = [
                'date' => '30/9/' . $this->toBuddhistYear($firstFiscalYear + $fullYears + 1),
                'description' => "คิดค่าเสื่อมราคา {$lastPeriodMonths} เดือน",
                'depreciation' => $lastDepreciation,
                'accumulated' => $totalValue,
                'netValue' => 1,
            ];
        }

        return [
            'can_calculate' => true,
            'useful_life' => $usefulLife,
            'depreciation_rate' => $depreciationRate,
            'total_value' => $totalValue,
            'annual_depreciation' => $annualDepreciation,
            'monthly_depreciation' => $monthlyDepreciation,
            'rows' => $depreciationRows,
        ];
    }

    /**
     * Format date for display (dd/m/yy in Buddhist era)
     */
    public function formatDateShort(Carbon $date): string
    {
        $day = $date->day;
        $month = $date->month;
        $year = $this->toBuddhistYear($date->year);

        return "{$day}/{$month}/{$year}";
    }

    /**
     * Convert to Buddhist year (2 digits)
     */
    private function toBuddhistYear(int $year): string
    {
        return substr((string) ($year + 543), -2);
    }
}
