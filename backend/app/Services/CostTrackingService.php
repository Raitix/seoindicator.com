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
     * Cached cost rates from configuration.
     */
    private array $costRates;

    /**
     * Default cost rate for unknown tools.
     */
    private float $defaultRate;

    /**
     * Default currency for costs.
     */
    private string $defaultCurrency;

    /**
     * Exchange rates for currency conversion.
     */
    private array $exchangeRates;

    /**
     * Create a new CostTrackingService instance.
     */
    public function __construct()
    {
        $this->costRates = Config::get('costs.rates', []);
        $this->defaultRate = Config::get('costs.default_rate', 0.01);
        $this->defaultCurrency = Config::get('costs.currency', 'USD');
        $this->exchangeRates = Config::get('costs.exchange_rates', ['USD' => 1.0]);
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
            $baseCost = $rate['base'] ?? $this->defaultRate;
            $this->validateRate($baseCost, 'base');

            AnalysisCost::create([
                'analysis_id' => $analysis->id,
                'cost_type' => 'compute',
                'cost_units' => 1,
                'cost_currency' => $rate['currency'] ?? $this->defaultCurrency,
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
                    'cost_currency' => $rate['currency'] ?? $this->defaultCurrency,
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
        $baseCost = $rate['base'] ?? $this->defaultRate;
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
        if (isset($rate['currency']) && $rate['currency'] !== $this->defaultCurrency) {
            $exchangeRate = $rate['exchange_rate'] ?? ($this->exchangeRates[$rate['currency']] ?? 1.0);
            $totalCost *= (float) $exchangeRate;
        }

        return $totalCost;
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

        foreach ($costs as $cost) {
            $amount = (float) $cost->cost_amount;
            
            // Convert to default currency if needed
            if ($cost->cost_currency !== $this->defaultCurrency) {
                $exchangeRate = $this->exchangeRates[$cost->cost_currency] ?? 1.0;
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
        // Check if rates array is empty - if so, use default
        if (empty($this->costRates)) {
            return ['base' => $this->defaultRate];
        }

        return $this->costRates[$toolName] ?? ['base' => $this->defaultRate];
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
