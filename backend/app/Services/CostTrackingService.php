<?php

namespace App\Services;

use App\Models\AnalysisCost;
use App\Models\AnalysisHistory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

/**
 * CostTrackingService
 *
 * Calculates and records analysis costs for transparency and future monetization.
 * For MVP, all tools are FREE - costs are estimated/notional for transparency only.
 */
class CostTrackingService
{
    /**
     * Cached cost rates from configuration (lazy-loaded on first use).
     */
    private ?array $cachedCostRates = null;

    /**
     * Cached default rate.
     */
    private ?float $cachedDefaultRate = null;

    /**
     * Cached default currency.
     */
    private ?string $cachedDefaultCurrency = null;

    /**
     * Cached exchange rates.
     */
    private ?array $cachedExchangeRates = null;

    /**
     * Get cost rates from configuration (cached on first use).
     */
    private function getCostRates(): array
    {
        if ($this->cachedCostRates === null) {
            $this->cachedCostRates = Config::get('costs.rates', []);
        }
        return $this->cachedCostRates;
    }

    /**
     * Get default cost rate from configuration (cached on first use).
     */
    private function getDefaultRate(): float
    {
        if ($this->cachedDefaultRate === null) {
            $this->cachedDefaultRate = (float) Config::get('costs.default_rate', 0.01);
        }
        return $this->cachedDefaultRate;
    }

    /**
     * Get default currency from configuration (cached on first use).
     */
    private function getDefaultCurrency(): string
    {
        if ($this->cachedDefaultCurrency === null) {
            $this->cachedDefaultCurrency = Config::get('costs.currency', 'USD');
        }
        return $this->cachedDefaultCurrency;
    }

    /**
     * Get exchange rates from configuration (cached on first use).
     */
    private function getExchangeRates(): array
    {
        if ($this->cachedExchangeRates === null) {
            $this->cachedExchangeRates = Config::get('costs.exchange_rates', ['USD' => 1.0]);
        }
        return $this->cachedExchangeRates;
    }

    /**
     * Record cost for an analysis.
     *
     * Creates cost records in the database for the given analysis.
     * Supports base costs and variable costs based on metrics.
     *
     * @param AnalysisHistory $analysis The analysis to record costs for
     * @param array $metrics Optional metrics for variable cost calculation
     * @return void
     * @throws \InvalidArgumentException If analysis is not saved or metrics are invalid
     */
    public function recordCost(AnalysisHistory $analysis, array $metrics = []): void
    {
        // Validate analysis exists in database
        if (!$analysis->exists || $analysis->id === null) {
            throw new \InvalidArgumentException('Analysis must be saved before recording costs.');
        }

        // Validate variable_units if provided
        if (isset($metrics['variable_units'])) {
            if ($metrics['variable_units'] < 0) {
                throw new \InvalidArgumentException('variable_units cannot be negative.');
            }
        }

        $toolName = $analysis->tool_name;
        $rate = $this->getRateForTool($toolName);

        // Use DB transaction for atomicity
        DB::transaction(function () use ($analysis, $rate, $metrics, $toolName) {
            // Record base cost (compute)
            $baseCost = $rate['base'] ?? $this->getDefaultRate();
            $this->validateRate($baseCost, 'base');

            AnalysisCost::create([
                'analysis_id' => $analysis->id,
                'cost_type' => 'compute',
                'cost_units' => 1,
                'cost_currency' => $rate['currency'] ?? $this->getDefaultCurrency(),
                'cost_amount' => $baseCost,
                'description' => "Base cost for {$toolName}",
            ]);

            // Record variable cost if applicable
            if (isset($metrics['variable_units']) && $metrics['variable_units'] > 0) {
                $perUnit = $metrics['per_unit'] ?? ($rate['per_unit'] ?? 0);
                $units = (float) $metrics['variable_units'];
                $amount = $units * $perUnit;

                AnalysisCost::create([
                    'analysis_id' => $analysis->id,
                    'cost_type' => 'variable',
                    'cost_units' => $units,
                    'cost_currency' => $rate['currency'] ?? $this->getDefaultCurrency(),
                    'cost_amount' => $amount,
                    'description' => $metrics['description'] ?? 'Variable cost',
                ]);
            }
        });
    }

    /**
     * Calculate cost for a tool without recording.
     *
     * Returns the total cost (base + variable) for a given tool and metrics.
     *
     * @param string $toolName The tool name
     * @param array $metrics Optional metrics for variable cost calculation
     * @return float The total cost
     */
    public function calculateCost(string $toolName, array $metrics = []): float
    {
        // Return 0 for empty tool name
        if (empty($toolName)) {
            return 0.00;
        }

        $rate = $this->getRateForTool($toolName);

        // Validate base rate
        $baseCost = $rate['base'] ?? $this->getDefaultRate();
        $this->validateRate($baseCost, 'base');

        // Start with base cost
        $totalCost = (float) $baseCost;

        // Add variable cost if applicable
        if (isset($metrics['variable_units']) && $metrics['variable_units'] > 0) {
            // Use per_unit from rate config, or 0 if not defined
            $perUnit = $rate['per_unit'] ?? 0;
            $this->validateRate($perUnit, 'per_unit');
            $units = (float) $metrics['variable_units'];
            $totalCost += $units * $perUnit;
        }

        // Apply multiplier if configured
        if (isset($rate['multiplier'])) {
            $this->validateRate($rate['multiplier'], 'multiplier');
            $totalCost *= (float) $rate['multiplier'];
        }

        // Apply exchange rate for currency conversion
        if (isset($rate['currency']) && $rate['currency'] !== $this->getDefaultCurrency()) {
            $exchangeRate = $rate['exchange_rate'] ?? ($this->getExchangeRates()[$rate['currency']] ?? 1.0);
            $totalCost *= (float) $exchangeRate;
        }

        // Round to avoid floating point precision issues
        return round($totalCost, 10);
    }

    /**
     * Get cost summary for a user.
     *
     * Returns total cost and breakdown by cost type for a user.
     * Optionally filtered by a time period.
     *
     * @param int $userId The user ID
     * @param string|null $period Optional period filter ('day', 'week', 'month', or ISO date string)
     * @return array{total: float, by_type: array<string, float>}
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

        // Calculate total with currency conversion
        $total = 0;
        $byType = [];

        $costs = $query->get();
        $exchangeRates = $this->getExchangeRates();
        $defaultCurrency = $this->getDefaultCurrency();

        foreach ($costs as $cost) {
            $amount = (float) $cost->cost_amount;
            
            // Convert to default currency if needed
            if ($cost->cost_currency !== $defaultCurrency) {
                $exchangeRate = $exchangeRates[$cost->cost_currency] ?? 1.0;
                $amount *= $exchangeRate;
            }

            $total += $amount;

            // Group by type
            $type = $cost->cost_type;
            if (!isset($byType[$type])) {
                $byType[$type] = 0;
            }
            $byType[$type] += $amount;
        }

        return [
            'total' => round($total, 6),
            'by_type' => $byType,
        ];
    }

    /**
     * Get cost breakdown for a specific analysis.
     *
     * Returns all cost records for an analysis, ordered by created_at.
     *
     * @param int $analysisId The analysis ID
     * @return Collection<int, AnalysisCost>
     */
    public function getCostBreakdown(int $analysisId): Collection
    {
        return AnalysisCost::where('analysis_id', $analysisId)
            ->orderBy('created_at', 'asc')
            ->get();
    }

    /**
     * Get rate configuration for a tool.
     *
     * @param string $toolName The tool name
     * @return array The rate configuration
     */
    private function getRateForTool(string $toolName): array
    {
        $costRates = $this->getCostRates();
        
        // Check if rates array is empty - if so, use default
        if (empty($costRates)) {
            return ['base' => $this->getDefaultRate()];
        }

        return $costRates[$toolName] ?? ['base' => $this->getDefaultRate()];
    }

    /**
     * Validate a rate value.
     *
     * @param mixed $rate The rate value
     * @param string $name The rate name for error messages
     * @throws \InvalidArgumentException If rate is invalid
     */
    private function validateRate(mixed $rate, string $name): void
    {
        if (!is_numeric($rate)) {
            throw new \InvalidArgumentException("{$name} rate must be numeric.");
        }

        if ((float) $rate < 0) {
            throw new \InvalidArgumentException("{$name} rate cannot be negative.");
        }
    }

    /**
     * Parse a period string into a start date.
     *
     * @param string $period The period string
     * @return \Illuminate\Support\Carbon|null The start date or null if invalid
     */
    private function parsePeriod(string $period): ?\Illuminate\Support\Carbon
    {
        return match ($period) {
            'day', 'today' => now()->startOfDay(),
            'week' => now()->subWeek()->startOfDay(),
            'month' => now()->subMonth()->startOfDay(),
            'year' => now()->subYear()->startOfDay(),
            default => now()->parse($period)->startOfDay(),
        };
    }
}
