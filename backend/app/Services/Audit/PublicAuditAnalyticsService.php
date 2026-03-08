<?php

namespace App\Services\Audit;

use App\Models\BadgeImpression;
use App\Models\PublicAuditView;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Service for aggregating public audit analytics.
 *
 * TASK-33-28: Implement Public Audit Analytics
 *
 * Aggregates views, leads, and badge metrics from public_audit_views
 * and badge_impressions tables.
 */
class PublicAuditAnalyticsService
{
    /**
     * Cache TTL in seconds (5 minutes).
     */
    private const CACHE_TTL = 300;

    /**
     * Default date range in days.
     */
    private const DEFAULT_DATE_RANGE_DAYS = 30;

    /**
     * Maximum countries to return in breakdown.
     */
    private const MAX_COUNTRIES = 10;

    /**
     * Maximum referrers to return in breakdown.
     */
    private const MAX_REFERRERS = 10;

    /**
     * Get analytics for a specific audit.
     *
     * @param int|string $auditId The audit ID (accepts int or string for security testing)
     * @param string|null $start Start date (Y-m-d format)
     * @param string|null $end End date (Y-m-d format)
     * @return array Analytics data
     */
    public function getAnalytics(int|string $auditId, ?string $start = null, ?string $end = null): array
    {
        // Cast to int and validate
        $auditId = is_numeric($auditId) ? (int) $auditId : 0;
        
        // Normalize and validate audit ID
        if ($auditId <= 0) {
            return $this->emptyResponse($start, $end);
        }

        // Parse and validate date range
        [$startDate, $endDate] = $this->parseDateRange($start, $end);

        // Check cache
        $cacheKey = $this->getCacheKey($auditId, $startDate, $endDate);
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($auditId, $startDate, $endDate) {
            return [
                'views' => $this->getViewsData($auditId, $startDate, $endDate),
                'leads' => $this->getLeadsData($auditId, $startDate, $endDate),
                'badges' => $this->getBadgesData($auditId, $startDate, $endDate),
                'dateRange' => [
                    'start' => $startDate->toDateString(),
                    'end' => $endDate->toDateString(),
                ],
            ];
        });
    }

    /**
     * Get views aggregation data.
     *
     * @param int $auditId
     * @param \Carbon\Carbon $startDate
     * @param \Carbon\Carbon $endDate
     * @return array
     */
    private function getViewsData(int $auditId, $startDate, $endDate): array
    {
        $query = PublicAuditView::where('audit_id', $auditId)
            ->whereBetween('viewed_at', [$startDate->startOfDay(), $endDate->endOfDay()]);

        // Total views
        $total = (clone $query)->count();

        // Unique views by IP hash
        $unique = (clone $query)
            ->whereNotNull('ip_hash')
            ->distinct('ip_hash')
            ->count('ip_hash');

        // Views by day
        $byDay = $this->getViewsByDay($auditId, $startDate, $endDate);

        // Views by country
        $byCountry = $this->getViewsByCountry($auditId, $startDate, $endDate);

        // Views by referrer
        $byReferrer = $this->getViewsByReferrer($auditId, $startDate, $endDate);

        return [
            'total' => $total,
            'unique' => $unique,
            'byDay' => $byDay,
            'byCountry' => $byCountry,
            'byReferrer' => $byReferrer,
        ];
    }

    /**
     * Get views grouped by day.
     *
     * @param int $auditId
     * @param \Carbon\Carbon $startDate
     * @param \Carbon\Carbon $endDate
     * @return array
     */
    private function getViewsByDay(int $auditId, $startDate, $endDate): array
    {
        $results = DB::table('public_audit_views')
            ->select(DB::raw('DATE(viewed_at) as date'), DB::raw('COUNT(*) as count'))
            ->where('audit_id', $auditId)
            ->whereNotNull('viewed_at')
            ->whereBetween('viewed_at', [$startDate->startOfDay(), $endDate->endOfDay()])
            ->groupBy(DB::raw('DATE(viewed_at)'))
            ->orderBy('date')
            ->get();

        return $results->map(fn($row) => [
            'date' => $row->date,
            'count' => (int) $row->count,
        ])->toArray();
    }

    /**
     * Get views grouped by country.
     *
     * @param int $auditId
     * @param \Carbon\Carbon $startDate
     * @param \Carbon\Carbon $endDate
     * @return array
     */
    private function getViewsByCountry(int $auditId, $startDate, $endDate): array
    {
        $results = DB::table('public_audit_views')
            ->select('country_code', DB::raw('COUNT(*) as count'))
            ->where('audit_id', $auditId)
            ->whereBetween('viewed_at', [$startDate->startOfDay(), $endDate->endOfDay()])
            ->groupBy('country_code')
            ->orderByDesc('count')
            ->limit(self::MAX_COUNTRIES)
            ->get();

        return $results->map(function ($row) {
            $country = $row->country_code;
            
            // Handle null, empty, or invalid country codes
            if (empty($country) || strlen($country) !== 2 || !ctype_alpha($country)) {
                $country = 'Unknown';
            } else {
                $country = strtoupper($country);
            }
            
            return [
                'country' => $country,
                'count' => (int) $row->count,
            ];
        })->toArray();
    }

    /**
     * Get views grouped by referrer domain.
     *
     * @param int $auditId
     * @param \Carbon\Carbon $startDate
     * @param \Carbon\Carbon $endDate
     * @return array
     */
    private function getViewsByReferrer(int $auditId, $startDate, $endDate): array
    {
        // Get all referrers and extract domains in PHP for reliability
        $results = DB::table('public_audit_views')
            ->select('referrer', DB::raw('COUNT(*) as count'))
            ->where('audit_id', $auditId)
            ->whereBetween('viewed_at', [$startDate->startOfDay(), $endDate->endOfDay()])
            ->groupBy('referrer')
            ->orderByDesc('count')
            ->get();

        // Group by extracted domain
        $domainCounts = [];
        foreach ($results as $row) {
            $domain = $this->extractDomain($row->referrer);
            if (!isset($domainCounts[$domain])) {
                $domainCounts[$domain] = 0;
            }
            $domainCounts[$domain] += $row->count;
        }

        // Sort by count and limit
        arsort($domainCounts);
        $domainCounts = array_slice($domainCounts, 0, self::MAX_REFERRERS, true);

        return collect($domainCounts)->map(fn($count, $domain) => [
            'referrer' => $domain,
            'count' => $count,
        ])->values()->toArray();
    }

    /**
     * Get leads aggregation data.
     *
     * @param int $auditId
     * @param \Carbon\Carbon $startDate
     * @param \Carbon\Carbon $endDate
     * @return array
     */
    private function getLeadsData(int $auditId, $startDate, $endDate): array
    {
        $baseQuery = PublicAuditView::where('audit_id', $auditId)
            ->whereBetween('viewed_at', [$startDate->startOfDay(), $endDate->endOfDay()]);

        // Total views for conversion rate calculation
        $totalViews = (clone $baseQuery)->count();

        // Total leads (is_lead_capture = true)
        $total = (clone $baseQuery)->where('is_lead_capture', true)->count();

        // Conversion rate
        $conversionRate = $totalViews > 0 ? round($total / $totalViews, 4) : 0.0;

        // Leads by day
        $byDay = $this->getLeadsByDay($auditId, $startDate, $endDate);

        return [
            'total' => $total,
            'conversionRate' => $conversionRate,
            'byDay' => $byDay,
        ];
    }

    /**
     * Get leads grouped by day.
     *
     * @param int $auditId
     * @param \Carbon\Carbon $startDate
     * @param \Carbon\Carbon $endDate
     * @return array
     */
    private function getLeadsByDay(int $auditId, $startDate, $endDate): array
    {
        $results = DB::table('public_audit_views')
            ->select(DB::raw('DATE(viewed_at) as date'), DB::raw('COUNT(*) as count'))
            ->where('audit_id', $auditId)
            ->where('is_lead_capture', true)
            ->whereNotNull('viewed_at')
            ->whereBetween('viewed_at', [$startDate->startOfDay(), $endDate->endOfDay()])
            ->groupBy(DB::raw('DATE(viewed_at)'))
            ->orderBy('date')
            ->get();

        return $results->map(fn($row) => [
            'date' => $row->date,
            'count' => (int) $row->count,
        ])->toArray();
    }

    /**
     * Get badge impressions/clicks data.
     *
     * @param int $auditId
     * @param \Carbon\Carbon $startDate
     * @param \Carbon\Carbon $endDate
     * @return array
     */
    private function getBadgesData(int $auditId, $startDate, $endDate): array
    {
        $baseQuery = BadgeImpression::where('audit_id', $auditId);

        // Total impressions (use impression_at for date filtering, fall back to created_at)
        $impressionsQuery = (clone $baseQuery)
            ->where(function ($q) use ($startDate, $endDate) {
                $q->whereBetween('impression_at', [$startDate->startOfDay(), $endDate->endOfDay()])
                    ->orWhereNull('impression_at');
            });
        $impressions = $impressionsQuery->count();

        // Clicks (with clicked_at)
        $clicksQuery = (clone $baseQuery)
            ->whereNotNull('clicked_at')
            ->whereBetween('clicked_at', [$startDate->startOfDay(), $endDate->endOfDay()]);
        $clicks = $clicksQuery->count();

        // Click-through rate
        $clickRate = $impressions > 0 ? round($clicks / $impressions, 4) : 0.0;

        // Clicks by target
        $byTarget = $this->getClicksByTarget($auditId, $startDate, $endDate);

        // Badges by day
        $byDay = $this->getBadgesByDay($auditId, $startDate, $endDate);

        // Badges by referrer
        $byReferrer = $this->getBadgesByReferrer($auditId, $startDate, $endDate);

        return [
            'impressions' => $impressions,
            'clicks' => $clicks,
            'clickRate' => $clickRate,
            'byTarget' => $byTarget,
            'byDay' => $byDay,
            'byReferrer' => $byReferrer,
        ];
    }

    /**
     * Get clicks grouped by target.
     *
     * @param int $auditId
     * @param \Carbon\Carbon $startDate
     * @param \Carbon\Carbon $endDate
     * @return array
     */
    private function getClicksByTarget(int $auditId, $startDate, $endDate): array
    {
        $results = DB::table('badge_impressions')
            ->select('click_target', DB::raw('COUNT(*) as count'))
            ->where('audit_id', $auditId)
            ->whereNotNull('clicked_at')
            ->whereNotNull('click_target')
            ->whereBetween('clicked_at', [$startDate->startOfDay(), $endDate->endOfDay()])
            ->groupBy('click_target')
            ->get();

        $byTarget = ['audit' => 0, 'home' => 0];
        foreach ($results as $row) {
            if (isset($byTarget[$row->click_target])) {
                $byTarget[$row->click_target] = (int) $row->count;
            }
        }

        return $byTarget;
    }

    /**
     * Get badge impressions grouped by day.
     *
     * @param int $auditId
     * @param \Carbon\Carbon $startDate
     * @param \Carbon\Carbon $endDate
     * @return array
     */
    private function getBadgesByDay(int $auditId, $startDate, $endDate): array
    {
        $results = DB::table('badge_impressions')
            ->select(DB::raw('DATE(impression_at) as date'), DB::raw('COUNT(*) as count'))
            ->where('audit_id', $auditId)
            ->whereNotNull('impression_at')
            ->whereBetween('impression_at', [$startDate->startOfDay(), $endDate->endOfDay()])
            ->groupBy(DB::raw('DATE(impression_at)'))
            ->orderBy('date')
            ->get();

        return $results->map(fn($row) => [
            'date' => $row->date,
            'count' => (int) $row->count,
        ])->toArray();
    }

    /**
     * Get badge impressions grouped by referrer domain.
     *
     * @param int $auditId
     * @param \Carbon\Carbon $startDate
     * @param \Carbon\Carbon $endDate
     * @return array
     */
    private function getBadgesByReferrer(int $auditId, $startDate, $endDate): array
    {
        $results = DB::table('badge_impressions')
            ->select('referrer_domain', DB::raw('COUNT(*) as count'))
            ->where('audit_id', $auditId)
            ->where(function ($q) use ($startDate, $endDate) {
                $q->whereBetween('impression_at', [$startDate->startOfDay(), $endDate->endOfDay()])
                    ->orWhereNull('impression_at');
            })
            ->groupBy('referrer_domain')
            ->orderByDesc('count')
            ->limit(self::MAX_REFERRERS)
            ->get();

        return $results->map(fn($row) => [
            'referrer' => $row->referrer_domain ?: 'Direct',
            'count' => (int) $row->count,
        ])->toArray();
    }

    /**
     * Parse and validate date range.
     *
     * @param string|null $start
     * @param string|null $end
     * @return array [\Carbon\Carbon $startDate, \Carbon\Carbon $endDate]
     */
    private function parseDateRange(?string $start, ?string $end): array
    {
        $now = now();

        try {
            $startDate = $start ? \Carbon\Carbon::parse($start)->startOfDay() : $now->copy()->subDays(self::DEFAULT_DATE_RANGE_DAYS)->startOfDay();
        } catch (\Exception $e) {
            $startDate = $now->copy()->subDays(self::DEFAULT_DATE_RANGE_DAYS)->startOfDay();
        }

        try {
            $endDate = $end ? \Carbon\Carbon::parse($end)->endOfDay() : $now->copy()->endOfDay();
        } catch (\Exception $e) {
            $endDate = $now->copy()->endOfDay();
        }

        // If end is before start, swap them
        if ($endDate->lt($startDate)) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }

        return [$startDate, $endDate];
    }

    /**
     * Generate cache key for analytics query.
     *
     * @param int $auditId
     * @param \Carbon\Carbon $startDate
     * @param \Carbon\Carbon $endDate
     * @return string
     */
    private function getCacheKey(int $auditId, $startDate, $endDate): string
    {
        return "audit_analytics:{$auditId}:{$startDate->toDateString()}:{$endDate->toDateString()}";
    }

    /**
     * Extract domain from URL.
     *
     * @param string|null $url
     * @return string
     */
    private function extractDomain(?string $url): string
    {
        if (empty($url)) {
            return 'Direct';
        }

        $parsed = parse_url($url, PHP_URL_HOST);
        
        if (empty($parsed)) {
            return 'Direct';
        }

        // Remove www. prefix
        $domain = preg_replace('/^www\./i', '', $parsed);

        return strtolower($domain);
    }

    /**
     * Return empty response structure.
     *
     * @param string|null $start
     * @param string|null $end
     * @return array
     */
    private function emptyResponse(?string $start, ?string $end): array
    {
        [$startDate, $endDate] = $this->parseDateRange($start, $end);

        return [
            'views' => [
                'total' => 0,
                'unique' => 0,
                'byDay' => [],
                'byCountry' => [],
                'byReferrer' => [],
            ],
            'leads' => [
                'total' => 0,
                'conversionRate' => 0.0,
                'byDay' => [],
            ],
            'badges' => [
                'impressions' => 0,
                'clicks' => 0,
                'clickRate' => 0.0,
                'byTarget' => ['audit' => 0, 'home' => 0],
                'byDay' => [],
                'byReferrer' => [],
            ],
            'dateRange' => [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString(),
            ],
        ];
    }
}
