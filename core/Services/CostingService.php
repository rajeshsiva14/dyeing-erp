<?php
namespace Core\Services;

class CostingService {
    /**
     * Calculate total material cost: sum(Q_i * R_i)
     */
    public static function calculateMaterialCost(array $consumedItems): float {
        $total = 0.0;
        foreach ($consumedItems as $item) {
            $qty = (float)($item['quantity'] ?? 0);
            $rate = (float)($item['rate'] ?? 0);
            $total += ($qty * $rate);
        }
        return round($total, 2);
    }

    /**
     * Calculate machine cost: hours * hourly rate
     */
    public static function calculateMachineCost(float $hours, float $hourlyRate): float {
        return round($hours * $hourlyRate, 2);
    }

    /**
     * Calculate cost per accepted unit: C_allocated / Q_accepted
     */
    public static function calculateCostPerAcceptedUnit(float $totalAllocatedCost, float $acceptedQty): float {
        if ($acceptedQty <= 0) {
            return 0.0;
        }
        return round($totalAllocatedCost / $acceptedQty, 2);
    }

    /**
     * Calculate Gross Margin percentage: (Net Sales - COGS) / Net Sales * 100
     */
    public static function calculateGrossMarginPct(float $netSales, float $cogs): float {
        if ($netSales <= 0) {
            return 0.0;
        }
        return round((($netSales - $cogs) / $netSales) * 100, 2);
    }

    /**
     * Calculate ASTM 4-Point System penalty points per 100 square yards
     * Formula: (Total Penalty Points * 3600) / (Inspected Fabric Length in Meters * 1.0936 * Width in Inches)
     */
    public static function calculateFourPointSystem(int $totalPenaltyPoints, float $lengthMeters, float $widthInches): float {
        if ($lengthMeters <= 0 || $widthInches <= 0) {
            return 0.0;
        }
        $lengthYards = $lengthMeters * 1.0936;
        $sqYards = ($lengthYards * $widthInches) / 36;
        if ($sqYards <= 0) {
            return 0.0;
        }
        return round(($totalPenaltyPoints * 100) / $sqYards, 2);
    }
}
