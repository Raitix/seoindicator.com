<?php

namespace App\Services;

use App\Models\AnalysisCost;
use App\Models\AnalysisHistory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

/**
 * Service for tracking and calculating analysis costs.
 *
 * Provides cost transparency for users and architecture for future monetization.
 * For MVP, all tools are FREE (costs are estimated/notional).
 */
class CostTrackingService
{
    /**
     * Get the cost rate for a tool.
     *
     * @param string $toolName The tool name
     * @return array The rate configuration
     */
    protected function getRate(string $toolName): array
    {
        $rates = Config::get('costs.rates', []);
        return $rates[$toolName] ?? ['base' => $this->getDefaultRate()];
    }

    /**
     * Get the default rate.
     *
     * @return float
     */
    protected function getDefaultRate(): float
    {
        return (float) Config::get('costs.default_rate', 0.01);
    }

    /**
     * Get the default currency.
     *
     * @return string
     */
    protected function getDefaultCurrency(): string
    {
        return Config::get('costs.currency', 'USD');
    }

    /**
     * Get exchange rates.
     *
     * @return array
     */
    protected function getExchangeRates(): array
    {
        return Config::get('costs.exchange_rates', ['USD' => 1.0]);
    }

    /**
     * Record costs for an analysis.
     *
     * Creates cost records in the database for both base and variable costs.
     * Uses database transaction to ensure atomicity.
     *
     * @param AnalysisHistory $analysis The analysis to record costs for
     * @param array $metrics Optional metrics for variable cost calculation
     * @throws \InvalidArgumentException If variable_units is negative
     * @throws \InvalidArgumentException If analysis is not persisted
     */
    public function recordCost(AnalysisHistory $analysis, array $metrics = []): void
    {
        // Validate analysis is persisted
        if (!$analysis->exists) {
            throw new \InvalidArgumentException('Cannot record costs for unsaved analysis.');
        }

        // Validate variable units if provided
        if (isset($metrics['variable_units']) && $metrics['variable_units'] < 0) {
            throw new \InvalidArgumentException('Variable units cannot be negative.');
        }

        $toolName = $analysis->tool_name;
        $rate = $this->getRate($toolName);

        // Validate rate values
        $this->validateRate($rate, $toolName);

        DB::transaction(function () use ($analysis, $rate, $metrics, $toolName) {
            // Record base cost
            $baseAmount = $this->calculateBaseCost($rate);
            $currency = $rate['currency'] ?? $this->getDefaultCurrency();

            AnalysisCost::create([
                'analysis_id' => $analysis->id,
                'cost_type' => 'compute',
                'cost_units' => 1,
                'cost_currency' => $currency,
                'cost_amount' => $baseAmount,
                'description' => "Base cost for {$toolName}",
            ]);

            // Record variable cost if units are provided and non-zero
            $variableUnits = $metrics['variable_units'] ?? $metrics['api_calls'] ?? null;
            if ($variableUnits !== null && $variableUnits > 0) {
                $perUnit = $metrics['per_unit'] ?? $metrics['per_call'] ?? $rate['per_unit'] ?? 0;
                $variableAmount = $this->calculateVariableCost($variableUnits, $perUnit, $rate);
                $description = $metrics['description'] ?? 'Variable cost';

                AnalysisCost::create([
                    'analysis_id' => $analysis->id,
                    'cost_type' => 'variable',
                    'cost_units' => $variableUnits,
                    'cost_currency' => $currency,
                    'cost_amount' => $variableAmount,
                    'description' => $description,
                ]);
            }
        });
    }

    /**
     * Calculate the total cost for a tool without recording it.
     *
     * @param string $toolName The tool name
     * @param array $metrics Optional metrics for variable cost calculation
     * @return float The total calculated cost
     */
    public function calculateCost(string $toolName, array $metrics = []): float
    {
        if (empty($toolName)) {
            return 0.00;
        }

        $rate = $this->getRate($toolName);

        // Validate rate values
        $this->validateRate($rate, $toolName);

        $baseCost = $this->calculateBaseCost($rate);
        $variableCost = 0.00;

        // Calculate variable cost
        $variableUnits = $metrics['variable_units'] ?? null;
        if ($variableUnits !== null && $variableUnits > 0) {
            $perUnit = $rate['per_unit'] ?? 0;
            $variableCost = $this->calculateVariableCost($variableUnits, $perUnit, $rate);
        }

        $totalCost = $baseCost + $variableCost;

        // Apply multiplier if defined
        if (isset($rate['multiplier'])) {
            $totalCost *= $rate['multiplier'];
        }

        // Apply currency conversion if needed
        if (isset($rate['currency']) && $rate['currency'] !== $this->getDefaultCurrency()) {
            $exchangeRate = $rate['exchange_rate'] ?? $this->getExchangeRates()[$rate['currency']] ?? 1.0;
            $totalCost *= $exchangeRate;
        }

        return (float) $totalCost;
    }

    /**
     * Get cost summary for a user.
     *
     * @param int $userId The user ID
     * @param string|null $period Optional period filter ('day', 'week', 'month', or date string)
     * @return array Summary with 'total' and 'by_type' keys
     */
    public function getUserCostSummary(int $userId, ?string $period = null): array
    {
        $query = AnalysisCost::whereHas('analysis', function ($q) use ($userId) {
            $q->where('user_id', $userId);
        });

        // Apply period filter
        if ($period !== null) {
            $startDate = $this->parsePeriod($period);
            if ($startDate !== null) {
                $query->where('created_at', '>=', $startDate);
            }
        }

        // Get total with currency conversion
        $total = 0.00;
        $byType = [];

        $costs = $query->get();

        foreach ($costs as $cost) {
            $amount = (float) $cost->cost_amount;

            // Convert to default currency if needed
            if ($cost->cost_currency !== $this->getDefaultCurrency()) {
                $exchangeRate = $this->getExchangeRates()[$cost->cost_currency] ?? 1.0;
                $amount *= $exchangeRate;
            }

            $total += $amount;

            // Group by cost type
            $costType = $cost->cost_type;
            if (!isset($byType[$costType])) {
                $byType[$costType] = 0.00;
            }
            $byType[$costType] += $amount;
        }

        return [
            'total' => round($total, 6),
            'by_type' => $byType,
        ];
    }

    /**
     * Get cost breakdown for a specific analysis.
     *
     * @param int $analysisId The analysis ID
     * @return Collection Collection of AnalysisCost models
     */
    public function getCostBreakdown(int $analysisId): Collection
    {
        return AnalysisCost::where('analysis_id', $analysisId)
            ->orderBy('created_at', 'asc')
            ->get();
    }

    /**
     * Calculate the base cost from a rate configuration.
     *
     * @param array $rate The rate configuration
     * @return float The base cost
     */
    protected function calculateBaseCost(array $rate): float
    {
        return (float) ($rate['base'] ?? $this->getDefaultRate());
    }

    /**
     * Calculate the variable cost.
     *
     * @param float $units The number of units
     * @param float $perUnit The cost per unit
     * @param array $rate The rate configuration
     * @return float The variable cost
     */
    protected function calculateVariableCost(float $units, float $perUnit, array $rate): float
    {
        return $units * $perUnit;
    }

    /**
     * Parse a period string into a start date.
     *
     * @param string $period The period string
     * @return \Illuminate\Support\Carbon|null The start date or null
     */
    protected function parsePeriod(string $period)
    {
        return match ($period) {
            'day', 'today' => now()->startOfDay(),
            'week' => now()->startOfWeek(),
            'month' => now()->startOfMonth(),
            'year' => now()->startOfYear(),
            default => now()->parse($period)->startOfDay(),
        };
    }

    /**
     * Validate a rate configuration.
     *
     * @param array $rate The rate configuration
     * @param string $toolName The tool name for error messages
     * @throws \InvalidArgumentException If the rate is invalid
     */
    protected function validateRate(array $rate, string $toolName): void
    {
        if (isset($rate['base'])) {
            if (!is_numeric($rate['base'])) {
                throw new \InvalidArgumentException("Invalid base rate for tool: {$toolName}");
            }
            if ($rate['base'] < 0) {
                throw new \InvalidArgumentException("Negative rate not allowed for tool: {$toolName}");
            }
        }

        if (isset($rate['per_unit']) && !is_numeric($rate['per_unit'])) {
            throw new \InvalidArgumentException("Invalid per_unit rate for tool: {$toolName}");
        }
    }
}
