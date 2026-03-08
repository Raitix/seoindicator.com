<?php

namespace App\Services\Audit;

use App\Models\Audit;
use App\Models\BadgeImpression;
use App\Models\PublicAuditView;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Service for aggregating public audit analytics.
 *
 * TASK-33-28: Implement Public Audit Analytics
 * SPEC-33: Public SEO Audit Reports & Embeddable Score Widget
 *
 * Aggregates analytics data from public_audit_views and badge_impressions tables
 * to provide comprehensive analytics for audit owners.
 */
class PublicAuditAnalyticsService
{
    /**
     * Cache TTL in seconds (5 minutes).
     */
    protected const CACHE_TTL = 300;

    /**
     * Default date range in days when no range is specified.
     */
    protected const DEFAULT_DATE_RANGE_DAYS = 30;

    /**
     * Maximum number of items to return in breakdown arrays.
     */
    protected const MAX_BREAKDOWN_ITEMS = 10;

    /**
     * Get analytics for a specific audit.
     *
     * @param mixed $auditId The audit ID (will be validated)
     * @param string|null $start Start date (Y-m-d format)
     * @param string|null $end End date (Y-m-d format)
     * @param bool $nocache Whether to bypass cache
     * @return array Analytics data
     */
    public function getAnalytics(mixed $auditId, ?string $start = null, ?string $end = null, bool $nocache = false): array
    {
        // Validate audit ID
        if (!is_numeric($auditId) || $auditId <= 0) {
            [$startDate, $endDate] = $this->normalizeDateRange($start, $end);
            return $this->emptyResponse($startDate, $endDate);
        }

        $auditId = (int) $auditId;

        // Validate and normalize date range
        [$startDate, $endDate] = $this->normalizeDateRange($start, $end);

        // Generate cache key
        $cacheKey = $this->getCacheKey($auditId, $startDate, $endDate);

        // If nocache is true, compute fresh data
        if ($nocache) {
            return $this->computeAnalytics($auditId, $startDate, $endDate);
        }

        // Try to get from cache
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($auditId, $startDate, $endDate) {
            return $this->computeAnalytics($auditId, $startDate, $endDate);
        });
    }

    /**
     * Clear the analytics cache for an audit.
     *
     * @param int $auditId The audit ID
     */
    public function clearCache(int $auditId): void
    {
        // Clear all date range variations
        // This is a simple approach - could be optimized with cache tags
        Cache::forget($this->getCacheKey($auditId, null, null));
    }

    /**
     * Compute analytics data (expensive operation).
     *
     * @param int $auditId The audit ID
     * @param string $startDate Start date
     * @param string $endDate End date
     * @return array Analytics data
     */
    protected function computeAnalytics(int $auditId, string $startDate, string $endDate): array
    {
        // Check if audit exists and is public
        $audit = Audit::find($auditId);
        if (!$audit || $audit->visibility !== 'public') {
            return $this->emptyResponse($startDate, $endDate);
        }

        return [
            'views' => $this->aggregateViews($auditId, $startDate, $endDate),
            'leads' => $this->aggregateLeads($auditId, $startDate, $endDate),
            'badges' => $this->aggregateBadges($auditId, $startDate, $endDate),
            'dateRange' => [
                'start' => $startDate,
                'end' => $endDate,
            ],
        ];
    }

    /**
     * Aggregate view analytics.
     *
     * @param int $auditId The audit ID
     * @param string $startDate Start date
     * @param string $endDate End date
     * @return array View analytics
     */
    protected function aggregateViews(int $auditId, string $startDate, string $endDate): array
    {
        $baseQuery = PublicAuditView::query()
            ->where('audit_id', $auditId)
            ->whereBetween('viewed_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);

        // Total views
        $total = (clone $baseQuery)->count();

        // Unique views (by distinct IP hash)
        $unique = (clone $baseQuery)
            ->whereNotNull('ip_hash')
            ->distinct('ip_hash')
            ->count('ip_hash');

        // If no IP hashes, use all views as unique
        if ($unique === 0 && $total > 0) {
            $unique = $total;
        }

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
     * @param int $auditId The audit ID
     * @param string $startDate Start date
     * @param string $endDate End date
     * @return array Daily view counts
     */
    protected function getViewsByDay(int $auditId, string $startDate, string $endDate): array
    {
        $results = DB::table('public_audit_views')
            ->select(DB::raw('DATE(viewed_at) as date'), DB::raw('COUNT(*) as count'))
            ->where('audit_id', $auditId)
            ->whereBetween('viewed_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->whereNotNull('viewed_at')
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
     * @param int $auditId The audit ID
     * @param string $startDate Start date
     * @param string $endDate End date
     * @return array Country breakdown
     */
    protected function getViewsByCountry(int $auditId, string $startDate, string $endDate): array
    {
        // Get all country codes with counts
        $results = DB::table('public_audit_views')
            ->select(
                DB::raw('UPPER(country_code) as country'),
                DB::raw('COUNT(*) as count')
            )
            ->where('audit_id', $auditId)
            ->whereBetween('viewed_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->whereNotNull('country_code')
            ->where('country_code', '!=', '')
            ->groupBy(DB::raw('UPPER(country_code)'))
            ->orderByDesc('count')
            ->get();

        // Separate valid ISO country codes from invalid ones
        $validCountries = [];
        $unknownCount = 0;

        // Get count of null/empty country codes
        $nullEmptyCount = DB::table('public_audit_views')
            ->where('audit_id', $auditId)
            ->whereBetween('viewed_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->where(function ($query) {
                $query->whereNull('country_code')
                    ->orWhere('country_code', '');
            })
            ->count();

        $unknownCount += $nullEmptyCount;

        // Process results - valid ISO 2-letter codes only
        foreach ($results as $row) {
            if ($this->isValidCountryCode($row->country)) {
                $validCountries[] = [
                    'country' => $row->country,
                    'count' => (int) $row->count,
                ];
            } else {
                // Invalid country code goes to Unknown
                $unknownCount += $row->count;
            }
        }

        // Sort and limit valid countries
        usort($validCountries, fn($a, $b) => $b['count'] <=> $a['count']);
        $validCountries = array_slice($validCountries, 0, self::MAX_BREAKDOWN_ITEMS);

        // Add Unknown category if there are views without/with invalid country
        if ($unknownCount > 0) {
            $validCountries[] = [
                'country' => 'Unknown',
                'count' => $unknownCount,
            ];
        }

        return $validCountries;
    }

    /**
     * Check if a country code is a valid ISO 3166-1 alpha-2 code.
     *
     * @param string|null $code Country code to validate
     * @return bool Whether the code is valid
     */
    protected function isValidCountryCode(?string $code): bool
    {
        if (!$code || strlen($code) !== 2) {
            return false;
        }

        // List of valid ISO 3166-1 alpha-2 country codes
        $validCodes = [
            'AD', 'AE', 'AF', 'AG', 'AI', 'AL', 'AM', 'AO', 'AQ', 'AR', 'AS', 'AT', 'AU', 'AW', 'AX', 'AZ',
            'BA', 'BB', 'BD', 'BE', 'BF', 'BG', 'BH', 'BI', 'BJ', 'BL', 'BM', 'BN', 'BO', 'BQ', 'BR', 'BS', 'BT', 'BV', 'BW', 'BY', 'BZ',
            'CA', 'CC', 'CD', 'CF', 'CG', 'CH', 'CI', 'CK', 'CL', 'CM', 'CN', 'CO', 'CR', 'CU', 'CV', 'CW', 'CX', 'CY', 'CZ',
            'DE', 'DJ', 'DK', 'DM', 'DO', 'DZ', 'EC', 'EE', 'EG', 'EH', 'ER', 'ES', 'ET', 'FI', 'FJ', 'FK', 'FM', 'FO', 'FR',
            'GA', 'GB', 'GD', 'GE', 'GF', 'GG', 'GH', 'GI', 'GL', 'GM', 'GN', 'GP', 'GQ', 'GR', 'GS', 'GT', 'GU', 'GW', 'GY',
            'HK', 'HM', 'HN', 'HR', 'HT', 'HU', 'ID', 'IE', 'IL', 'IM', 'IN', 'IO', 'IQ', 'IR', 'IS', 'IT', 'JE', 'JM', 'JO', 'JP',
            'KE', 'KG', 'KH', 'KI', 'KM', 'KN', 'KP', 'KR', 'KW', 'KY', 'KZ', 'LA', 'LB', 'LC', 'LI', 'LK', 'LR', 'LS', 'LT', 'LU', 'LV', 'LY',
            'MA', 'MC', 'MD', 'ME', 'MF', 'MG', 'MH', 'MK', 'ML', 'MM', 'MN', 'MO', 'MP', 'MQ', 'MR', 'MS', 'MT', 'MU', 'MV', 'MW', 'MX', 'MY', 'MZ',
            'NA', 'NC', 'NE', 'NF', 'NG', 'NI', 'NL', 'NO', 'NP', 'NR', 'NU', 'NZ', 'OM', 'PA', 'PE', 'PF', 'PG', 'PH', 'PK', 'PL', 'PM', 'PN', 'PR', 'PS', 'PT', 'PW', 'PY',
            'QA', 'RE', 'RO', 'RS', 'RU', 'RW', 'SA', 'SB', 'SC', 'SD', 'SE', 'SG', 'SH', 'SI', 'SJ', 'SK', 'SL', 'SM', 'SN', 'SO', 'SR', 'SS', 'ST', 'SV', 'SX', 'SY', 'SZ',
            'TC', 'TD', 'TF', 'TG', 'TH', 'TJ', 'TK', 'TL', 'TM', 'TN', 'TO', 'TR', 'TT', 'TV', 'TW', 'TZ', 'UA', 'UG', 'UM', 'US', 'UY', 'UZ', 'VA', 'VC', 'VE', 'VG', 'VI', 'VN', 'VU', 'WF', 'WS', 'YE', 'YT', 'ZA', 'ZM', 'ZW',
        ];

        return in_array(strtoupper($code), $validCodes, true);
    }

    /**
     * Get views grouped by referrer domain.
     *
     * @param int $auditId The audit ID
     * @param string $startDate Start date
     * @param string $endDate End date
     * @return array Referrer breakdown
     */
    protected function getViewsByReferrer(int $auditId, string $startDate, string $endDate): array
    {
        // Get all referrers with their counts
        $results = DB::table('public_audit_views')
            ->select('referrer', DB::raw('COUNT(*) as count'))
            ->where('audit_id', $auditId)
            ->whereBetween('viewed_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->whereNotNull('referrer')
            ->where('referrer', '!=', '')
            ->groupBy('referrer')
            ->orderByDesc('count')
            ->limit(self::MAX_BREAKDOWN_ITEMS * 2) // Get more for grouping
            ->get();

        // Group by extracted domain
        $domainCounts = [];
        foreach ($results as $row) {
            $domain = $this->extractDomain($row->referrer);
            if ($domain) {
                // Skip self-referrals
                if ($this->isSelfReferral($domain)) {
                    continue;
                }
                $domainCounts[$domain] = ($domainCounts[$domain] ?? 0) + $row->count;
            }
        }

        // Sort and limit
        arsort($domainCounts);
        $domainCounts = array_slice($domainCounts, 0, self::MAX_BREAKDOWN_ITEMS, true);

        $breakdown = [];
        foreach ($domainCounts as $domain => $count) {
            $breakdown[] = [
                'referrer' => $domain,
                'count' => $count,
            ];
        }

        // Get count of direct visits (no referrer)
        $directCount = DB::table('public_audit_views')
            ->where('audit_id', $auditId)
            ->whereBetween('viewed_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->where(function ($query) {
                $query->whereNull('referrer')
                    ->orWhere('referrer', '');
            })
            ->count();

        if ($directCount > 0) {
            $breakdown[] = [
                'referrer' => 'Direct',
                'count' => $directCount,
            ];
        }

        return $breakdown;
    }

    /**
     * Aggregate lead analytics.
     *
     * @param int $auditId The audit ID
     * @param string $startDate Start date
     * @param string $endDate End date
     * @return array Lead analytics
     */
    protected function aggregateLeads(int $auditId, string $startDate, string $endDate): array
    {
        // Get view and lead counts for conversion rate
        $baseQuery = PublicAuditView::query()
            ->where('audit_id', $auditId)
            ->whereBetween('viewed_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);

        $totalViews = (clone $baseQuery)->count();
        $totalLeads = (clone $baseQuery)->where('is_lead_capture', true)->count();

        // Calculate conversion rate
        $conversionRate = $totalViews > 0 ? round($totalLeads / $totalViews, 4) : 0.0;

        // Leads by day
        $byDay = $this->getLeadsByDay($auditId, $startDate, $endDate);

        return [
            'total' => $totalLeads,
            'conversionRate' => $conversionRate,
            'byDay' => $byDay,
        ];
    }

    /**
     * Get leads grouped by day.
     *
     * @param int $auditId The audit ID
     * @param string $startDate Start date
     * @param string $endDate End date
     * @return array Daily lead counts
     */
    protected function getLeadsByDay(int $auditId, string $startDate, string $endDate): array
    {
        $results = DB::table('public_audit_views')
            ->select(DB::raw('DATE(viewed_at) as date'), DB::raw('COUNT(*) as count'))
            ->where('audit_id', $auditId)
            ->where('is_lead_capture', true)
            ->whereBetween('viewed_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->whereNotNull('viewed_at')
            ->groupBy(DB::raw('DATE(viewed_at)'))
            ->orderBy('date')
            ->get();

        return $results->map(fn($row) => [
            'date' => $row->date,
            'count' => (int) $row->count,
        ])->toArray();
    }

    /**
     * Aggregate badge analytics.
     *
     * @param int $auditId The audit ID
     * @param string $startDate Start date
     * @param string $endDate End date
     * @return array Badge analytics
     */
    protected function aggregateBadges(int $auditId, string $startDate, string $endDate): array
    {
        $baseQuery = BadgeImpression::query()
            ->where('audit_id', $auditId);

        // Use impression_at for date filtering, fallback to created_at
        $dateColumn = 'impression_at';

        // Total impressions
        $impressions = (clone $baseQuery)
            ->where(function ($query) use ($dateColumn, $startDate, $endDate) {
                $query->whereBetween($dateColumn, [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
                    ->orWhereNull($dateColumn);
            })
            ->count();

        // Total clicks
        $clicks = (clone $baseQuery)
            ->whereNotNull('clicked_at')
            ->whereBetween('clicked_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->count();

        // Click-through rate
        $clickRate = $impressions > 0 ? round($clicks / $impressions, 4) : 0.0;

        // Click target breakdown
        $byTarget = $this->getBadgeClicksByTarget($auditId, $startDate, $endDate);

        // Impressions by day
        $byDay = $this->getBadgesByDay($auditId, $startDate, $endDate);

        // Referrer breakdown
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
     * Get badge clicks grouped by target.
     *
     * @param int $auditId The audit ID
     * @param string $startDate Start date
     * @param string $endDate End date
     * @return array Target breakdown
     */
    protected function getBadgeClicksByTarget(int $auditId, string $startDate, string $endDate): array
    {
        $results = DB::table('badge_impressions')
            ->select('click_target', DB::raw('COUNT(*) as count'))
            ->where('audit_id', $auditId)
            ->whereNotNull('clicked_at')
            ->whereNotNull('click_target')
            ->whereBetween('clicked_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->groupBy('click_target')
            ->get();

        $breakdown = ['audit' => 0, 'home' => 0];
        foreach ($results as $row) {
            if (in_array($row->click_target, ['audit', 'home'])) {
                $breakdown[$row->click_target] = (int) $row->count;
            }
        }

        return $breakdown;
    }

    /**
     * Get badge impressions and clicks grouped by day.
     *
     * @param int $auditId The audit ID
     * @param string $startDate Start date
     * @param string $endDate End date
     * @return array Daily badge stats
     */
    protected function getBadgesByDay(int $auditId, string $startDate, string $endDate): array
    {
        // Get impressions by day
        $impressions = DB::table('badge_impressions')
            ->select(DB::raw('DATE(impression_at) as date'), DB::raw('COUNT(*) as impressions'))
            ->where('audit_id', $auditId)
            ->whereNotNull('impression_at')
            ->whereBetween('impression_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->groupBy(DB::raw('DATE(impression_at)'))
            ->get()
            ->keyBy('date');

        // Get clicks by day
        $clicks = DB::table('badge_impressions')
            ->select(DB::raw('DATE(clicked_at) as date'), DB::raw('COUNT(*) as clicks'))
            ->where('audit_id', $auditId)
            ->whereNotNull('clicked_at')
            ->whereBetween('clicked_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->groupBy(DB::raw('DATE(clicked_at)'))
            ->get()
            ->keyBy('date');

        // Merge results
        $allDates = $impressions->keys()->merge($clicks->keys())->unique()->sort();
        $results = [];

        foreach ($allDates as $date) {
            $results[] = [
                'date' => $date,
                'impressions' => (int) ($impressions->get($date)?->impressions ?? 0),
                'clicks' => (int) ($clicks->get($date)?->clicks ?? 0),
            ];
        }

        return $results;
    }

    /**
     * Get badge impressions grouped by referrer domain.
     *
     * @param int $auditId The audit ID
     * @param string $startDate Start date
     * @param string $endDate End date
     * @return array Referrer breakdown
     */
    protected function getBadgesByReferrer(int $auditId, string $startDate, string $endDate): array
    {
        $results = DB::table('badge_impressions')
            ->select('referrer_domain', DB::raw('COUNT(*) as count'))
            ->where('audit_id', $auditId)
            ->where(function ($query) use ($startDate, $endDate) {
                $query->whereBetween('impression_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
                    ->orWhereNull('impression_at');
            })
            ->whereNotNull('referrer_domain')
            ->where('referrer_domain', '!=', '')
            ->groupBy('referrer_domain')
            ->orderByDesc('count')
            ->limit(self::MAX_BREAKDOWN_ITEMS)
            ->get();

        $breakdown = $results->map(fn($row) => [
            'referrer' => $row->referrer_domain,
            'count' => (int) $row->count,
        ])->toArray();

        // Get count of direct (no referrer)
        $directCount = DB::table('badge_impressions')
            ->where('audit_id', $auditId)
            ->where(function ($query) use ($startDate, $endDate) {
                $query->whereBetween('impression_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
                    ->orWhereNull('impression_at');
            })
            ->where(function ($query) {
                $query->whereNull('referrer_domain')
                    ->orWhere('referrer_domain', '');
            })
            ->count();

        if ($directCount > 0) {
            $breakdown[] = [
                'referrer' => 'Direct',
                'count' => $directCount,
            ];
        }

        return $breakdown;
    }

    /**
     * Normalize and validate date range.
     *
     * @param string|null $start Start date
     * @param string|null $end End date
     * @return array Normalized [startDate, endDate]
     */
    protected function normalizeDateRange(?string $start, ?string $end): array
    {
        // Default to last 30 days
        if (!$start && !$end) {
            return [
                now()->subDays(self::DEFAULT_DATE_RANGE_DAYS)->toDateString(),
                now()->toDateString(),
            ];
        }

        // Validate start date
        $startDate = $this->validateDate($start)
            ? $start
            : now()->subDays(self::DEFAULT_DATE_RANGE_DAYS)->toDateString();

        // Validate end date
        $endDate = $this->validateDate($end)
            ? $end
            : now()->toDateString();

        // Ensure end >= start (swap if needed)
        if (strtotime($endDate) < strtotime($startDate)) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }

        return [$startDate, $endDate];
    }

    /**
     * Validate a date string.
     *
     * @param string|null $date Date string to validate
     * @return bool Whether the date is valid
     */
    protected function validateDate(?string $date): bool
    {
        if (!$date) {
            return false;
        }

        $parsed = date_parse($date);
        return $parsed['error_count'] === 0 && $parsed['warning_count'] === 0;
    }

    /**
     * Generate a cache key for the analytics.
     *
     * @param int $auditId The audit ID
     * @param string|null $startDate Start date
     * @param string|null $endDate End date
     * @return string Cache key
     */
    protected function getCacheKey(int $auditId, ?string $startDate, ?string $endDate): string
    {
        return "audit_analytics:{$auditId}:{$startDate}:{$endDate}";
    }

    /**
     * Extract domain from a URL.
     *
     * @param string|null $url Full URL
     * @return string|null Domain only
     */
    protected function extractDomain(?string $url): ?string
    {
        if (!$url) {
            return null;
        }

        $parsed = parse_url($url, PHP_URL_HOST);
        if (!$parsed) {
            return null;
        }

        // Remove www. prefix
        $domain = preg_replace('/^www\./', '', strtolower($parsed));

        return $domain;
    }

    /**
     * Check if a domain is a self-referral.
     *
     * @param string $domain Domain to check
     * @return bool Whether it's a self-referral
     */
    protected function isSelfReferral(string $domain): bool
    {
        $ownDomain = parse_url(config('app.url'), PHP_URL_HOST);
        if (!$ownDomain) {
            return false;
        }

        $ownDomain = preg_replace('/^www\./', '', strtolower($ownDomain));

        return $domain === $ownDomain;
    }

    /**
     * Return an empty analytics response.
     *
     * @param string $startDate Start date
     * @param string $endDate End date
     * @return array Empty response
     */
    protected function emptyResponse(string $startDate, string $endDate): array
    {
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
                'start' => $startDate,
                'end' => $endDate,
            ],
        ];
    }
}
