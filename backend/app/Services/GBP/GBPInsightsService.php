<?php

namespace App\Services\GBP;

use App\Models\BusinessLocation;
use App\Services\GBP\Contracts\GBPApiServiceInterface;
use App\Services\GBP\Contracts\GBPInsightsServiceInterface;
use Illuminate\Support\Collection;
use RuntimeException;

/**
 * Red-phase scaffold for TASK-35-9.
 *
 * The concrete sync/storage/aggregation pipeline is intentionally left for the
 * Developer pass. The accompanying tests define the expected behavior.
 *
 * @see TASK-35-9
 * @see SPEC-035
 */
class GBPInsightsService implements GBPInsightsServiceInterface
{
    public function __construct(
        protected GBPApiServiceInterface $apiService,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function syncInsights(BusinessLocation $location, ?array $dateRange = null): void
    {
        throw new RuntimeException('GBPInsightsService::syncInsights() not implemented yet.');
    }

    /**
     * {@inheritdoc}
     */
    public function getInsights(BusinessLocation $location, array $dateRange): Collection
    {
        throw new RuntimeException('GBPInsightsService::getInsights() not implemented yet.');
    }

    /**
     * {@inheritdoc}
     */
    public function getInsightsTrend(BusinessLocation $location, int $days = 90): array
    {
        throw new RuntimeException('GBPInsightsService::getInsightsTrend() not implemented yet.');
    }

    /**
     * {@inheritdoc}
     */
    public function getTotalMetrics(BusinessLocation $location, array $dateRange): array
    {
        throw new RuntimeException('GBPInsightsService::getTotalMetrics() not implemented yet.');
    }

    /**
     * {@inheritdoc}
     */
    public function getTopPerformingDay(BusinessLocation $location, int $days = 30): ?array
    {
        throw new RuntimeException('GBPInsightsService::getTopPerformingDay() not implemented yet.');
    }
}
