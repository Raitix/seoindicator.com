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
 * For MVP, all tools are FREE - costs are estimated/notional.
 */
class CostTrackingService
{
    /**
     * Cached cost rates from configuration (per-instance).
     *
     * @var array|null
     */
    protected ?array $cachedRates = null;

    /**
     * Cached default rate (per-instance).
     *
     * @var float|null
     */
    protected ?float $cachedDefaultRate = null;

    /**
     * Cached default currency (per-instance).
     *
     * @var string|null
     */
    protected ?string $cachedDefaultCurrency = null;

    /**
     * Create a new CostTrackingService instance.
     */
    public function __construct()
    {
        // Config is loaded lazily and cached per-instance
    }

    /**
     * Get cost rates from configuration (cached per-instance).
     *
     * @return array
     */
    protected function getCostRates(): array
    {
        if ($this->cachedRates === null) {
            $this->cachedRates = Config::get('costs.rates', []);
        }
        return $this->cachedRates;
    }

    /**
     * Get the default rate for unknown tools (cached per-instance).
     *
     * @return float
     */
    protected function getDefaultRate(): float
    {
        if ($this->cachedDefaultRate === null) {
            $this->cachedDefaultRate = (float) Config::get('costs.default_rate', 0.01);
        }
        return $this->cachedDefaultRate;
    }

    /**
     * Get the default currency (cached per-instance).
     *
     * @return string
     */
    protected function getDefaultCurrency(): string
    {
        if ($this->cachedDefaultCurrency === null) {
            $this->cachedDefaultCurrency = Config::get('costs.currency', 'USD');
        }
        return $this->cachedDefaultCurrency;
    }

    /**
     * Record costs for an analysis.
     *
     * @param AnalysisHistory $analysis The analysis to record costs for
     * @param array $metrics Optional metrics for variable costs
     * @return void
     * @throws \InvalidArgumentException If analysis has no ID or invalid metrics
     */
    public function recordCost(AnalysisHistory $analysis, array $metrics = []): void
    {
        // Validate analysis has an ID
        if (!$analysis->exists || $analysis->id === null) {
            throw new \InvalidArgumentException('Analysis must be saved before recording costs.');
        }

        // Validate metrics
        $this->validateMetrics($metrics);

        // Get rate for this tool
        $toolName = $analysis->tool_name;
        $rate = $this->getRateForTool($toolName);

        // Use database transaction for atomicity
        DB::transaction(function () use ($analysis, $rate, $metrics) {
            // Record base cost
            $this->recordBaseCost($analysis, $rate);

            // Record variable cost if provided
            if ($this->hasVariableUnits($metrics)) {
                $this->recordVariableCost($analysis, $rate, $metrics);
            }
        });
    }

    /**
     * Calculate cost for a tool with given metrics.
     *
     * @param string $toolName The tool name
     * @param array $metrics Optional metrics for variable costs
     * @return float The calculated cost
     */
    public function calculateCost(string $toolName, array $metrics = []): float
    {
        // Handle empty tool name
        if (empty($toolName)) {
            return 0.00;
        }

        $rate = $this->getRateForTool($toolName);

        // Validate rate configuration
        $this->validateRate($rate, $toolName);

        // Get base cost
        $baseCost = (float) ($rate['base'] ?? $this->getDefaultRate());

        // Calculate variable cost
        $variableCost = 0.00;
        if (isset($metrics['variable_units']) && $metrics['variable_units'] > 0) {
            // Use per_unit from metrics if provided, otherwise from tool config
            $perUnit = (float) ($metrics['per_unit'] ?? $rate['per_unit'] ?? 0);
            $variableCost = (float) ($metrics['variable_units'] ?? 0) * $perUnit;
        }

        $totalCost = $baseCost + $variableCost;

        // Apply multiplier if defined
        if (isset($rate['multiplier'])) {
            $totalCost *= (float) $rate['multiplier'];
        }

        // Apply currency conversion if defined
        if (isset($rate['exchange_rate'])) {
            $totalCost *= (float) $rate['exchange_rate'];
        }

        return round($totalCost, 6);
    }

    /**
     * Get cost summary for a user.
     *
     * @param int $userId The user ID
     * @param string|null $period Optional period filter ('day', 'week', 'month', or date string)
     * @return array{total: float, by_type: array<string, float>}
     */
    public function getUserCostSummary(int $userId, ?string $period = null): array
    {
        $query = AnalysisCost::whereHas('analysis', function ($q) use ($userId) {
            $q->where('user_id', $userId);
        });

        // Apply period filter
        if ($period !== null) {
            $query->where('created_at', '>=', $this->parsePeriod($period));
        }

        // Get total cost with currency conversion
        $total = $this->calculateTotalWithCurrencyConversion($query);

        // Get costs grouped by type
        $byType = $this->getCostsGroupedByType($query);

        return [
            'total' => $total,
            'by_type' => $byType,
        ];
    }

    /**
     * Get detailed cost breakdown for an analysis.
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

    // =========================================================================
    // Protected Helper Methods
    // =========================================================================

    /**
     * Get rate configuration for a tool.
     *
     * @param string $toolName
     * @return array
     */
    protected function getRateForTool(string $toolName): array
    {
        $rates = $this->getCostRates();
        return $rates[$toolName] ?? ['base' => $this->getDefaultRate()];
    }

    /**
     * Validate metrics array.
     *
     * @param array $metrics
     * @return void
     * @throws \InvalidArgumentException
     */
    protected function validateMetrics(array $metrics): void
    {
        if (isset($metrics['variable_units'])) {
            if ($metrics['variable_units'] < 0) {
                throw new \InvalidArgumentException('Variable units cannot be negative.');
            }
        }
    }

    /**
     * Validate rate configuration.
     *
     * @param array $rate
     * @param string $toolName
     * @return void
     * @throws \InvalidArgumentException
     */
    protected function validateRate(array $rate, string $toolName): void
    {
        if (isset($rate['base']) && !is_numeric($rate['base'])) {
            throw new \InvalidArgumentException("Invalid rate type for tool '{$toolName}': base must be numeric.");
        }

        if (isset($rate['base']) && $rate['base'] < 0) {
            throw new \InvalidArgumentException("Negative rate for tool '{$toolName}': base cannot be negative.");
        }
    }

    /**
     * Check if metrics contain variable units.
     *
     * @param array $metrics
     * @return bool
     */
    protected function hasVariableUnits(array $metrics): bool
    {
        return isset($metrics['variable_units']) 
            && $metrics['variable_units'] > 0
            && (isset($metrics['per_unit']) || isset($metrics['per_call']));
    }

    /**
     * Record the base cost for an analysis.
     *
     * @param AnalysisHistory $analysis
     * @param array $rate
     * @return void
     */
    protected function recordBaseCost(AnalysisHistory $analysis, array $rate): void
    {
        $baseAmount = (float) ($rate['base'] ?? $this->getDefaultRate());
        $currency = $rate['currency'] ?? $this->getDefaultCurrency();

        // Apply exchange rate if defined
        if (isset($rate['exchange_rate'])) {
            $baseAmount *= (float) $rate['exchange_rate'];
        }

        AnalysisCost::create([
            'analysis_id' => $analysis->id,
            'cost_type' => 'compute',
            'cost_units' => 1,
            'cost_currency' => $currency,
            'cost_amount' => round($baseAmount, 6),
            'description' => "Base cost for {$analysis->tool_name}",
            'created_at' => now(),
        ]);
    }

    /**
     * Record the variable cost for an analysis.
     *
     * @param AnalysisHistory $analysis
     * @param array $rate
     * @param array $metrics
     * @return void
     */
    protected function recordVariableCost(AnalysisHistory $analysis, array $rate, array $metrics): void
    {
        $units = (float) $metrics['variable_units'];
        $perUnit = (float) ($metrics['per_unit'] ?? $rate['per_unit'] ?? 0);
        $amount = $units * $perUnit;
        $currency = $rate['currency'] ?? $this->getDefaultCurrency();

        if ($amount > 0) {
            AnalysisCost::create([
                'analysis_id' => $analysis->id,
                'cost_type' => 'variable',
                'cost_units' => $units,
                'cost_currency' => $currency,
                'cost_amount' => round($amount, 6),
                'description' => $metrics['description'] ?? 'Variable cost',
                'created_at' => now(),
            ]);
        }
    }

    /**
     * Parse period string into a Carbon date.
     *
     * @param string $period
     * @return \Illuminate\Support\Carbon
     */
    protected function parsePeriod(string $period)
    {
        return match ($period) {
            'day' => now()->startOfDay(),
            'week' => now()->subDays(7),
            'month' => now()->subDays(30),
            'year' => now()->subYear(),
            default => now()->parse($period),
        };
    }

    /**
     * Calculate total cost with currency conversion.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return float
     */
    protected function calculateTotalWithCurrencyConversion($query): float
    {
        // For MVP, we sum all amounts without currency conversion
        // Future: Convert to base currency using exchange rates
        $costs = $query->get();
        
        $total = 0.00;
        foreach ($costs as $cost) {
            $amount = (float) $cost->cost_amount;
            
            // Apply currency conversion if needed (future enhancement)
            // For now, we assume all costs are in the base currency
            $defaultCurrency = $this->getDefaultCurrency();
            if ($cost->cost_currency !== $defaultCurrency) {
                // Simple conversion for EUR to USD (future: use real exchange rates)
                if ($cost->cost_currency === 'EUR' && $defaultCurrency === 'USD') {
                    $amount *= 1.1; // Approximate EUR to USD rate
                }
            }
            
            $total += $amount;
        }
        
        return round($total, 6);
    }

    /**
     * Get costs grouped by type.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return array<string, float>
     */
    protected function getCostsGroupedByType($query): array
    {
        $results = $query->clone()
            ->selectRaw('cost_type, SUM(cost_amount) as total')
            ->groupBy('cost_type')
            ->pluck('total', 'cost_type')
            ->map(fn ($value) => round((float) $value, 6))
            ->toArray();

        return $results;
    }
}
