<?php

namespace App\Services\AIVisibility;

use App\Models\AIVisibility\AIVisibilityProject;
use App\Models\AIVisibility\AIQueryResult;
use App\Models\AIVisibility\AIMentionAnalytics;
use App\Services\AIVisibility\Contracts\ExporterInterface;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Psr\Log\LoggerInterface;
use Carbon\Carbon;

/**
 * Export Service
 *
 * Handles data export for AI Visibility data (CSV and XLSX formats).
 *
 * @see TASK-34-22
 * @see SPEC-034
 */
class ExportService
{
    /**
     * @var CsvExporter
     */
    protected CsvExporter $csvExporter;

    /**
     * @var XlsxExporter
     */
    protected XlsxExporter $xlsxExporter;

    /**
     * @var LoggerInterface
     */
    protected LoggerInterface $logger;

    /**
     * @var array Rate limit tracking
     */
    protected static array $rateLimitCache = [];

    /**
     * Create a new ExportService instance.
     *
     * @param CsvExporter $csvExporter
     * @param XlsxExporter $xlsxExporter
     * @param LoggerInterface $logger
     */
    public function __construct(
        CsvExporter $csvExporter,
        XlsxExporter $xlsxExporter,
        LoggerInterface $logger
    ) {
        $this->csvExporter = $csvExporter;
        $this->xlsxExporter = $xlsxExporter;
        $this->logger = $logger;
    }

    /**
     * Export data to the specified format.
     *
     * @param int $userId
     * @param array $filters Filters (project_id, project_ids, from, to, data_type)
     * @param string $format csv or xlsx
     * @return Response|StreamedResponse
     * @throws ValidationException
     */
    public function export(int $userId, array $filters = [], string $format = 'csv'): Response|StreamedResponse
    {
        // Validate format
        $format = strtolower($format);
        if (!in_array($format, ['csv', 'xlsx'])) {
            throw ValidationException::withMessages([
                'format' => 'Invalid export format. Must be csv or xlsx.',
            ]);
        }

        // Check rate limit
        $this->checkRateLimit($userId);

        // Validate data type
        $dataType = $filters['data_type'] ?? 'all';
        $validDataTypes = ['projects', 'queries', 'analytics', 'mentions', 'all'];
        if (!in_array($dataType, $validDataTypes)) {
            throw ValidationException::withMessages([
                'data_type' => 'Invalid data type. Must be: ' . implode(', ', $validDataTypes),
            ]);
        }

        // Get projects for user
        $projectIds = $this->resolveProjectIds($userId, $filters);

        // Validate date range
        $dateRange = $this->validateDateRange($filters);

        // Generate filename
        $filename = $this->generateFilename($format, $filters);

        // Get exporter
        $exporter = $format === 'xlsx' ? $this->xlsxExporter : $this->csvExporter;

        // Export based on data type
        if ($format === 'xlsx' && $dataType === 'all') {
            return $this->exportMultiSheetXlsx($projectIds, $dateRange, $filename);
        }

        if ($format === 'xlsx' && str_contains($dataType, ',')) {
            return $this->exportMultiTypeXlsx($projectIds, $dateRange, $dataType, $filename);
        }

        // Single data type export
        $data = $this->fetchData($dataType, $projectIds, $dateRange);
        $columns = $this->getColumns($dataType);

        $exporter->setColumns($columns);

        // Enable streaming for large datasets
        if ($data->count() > 10000) {
            $exporter->enableStreaming(1000);
        }

        $content = $exporter->export($data, $filename);

        // Log export
        $this->logExport($userId, $filters, $format, $data->count());

        // Return response
        if ($content instanceof \Generator) {
            return $this->streamResponse($content, $filename, $exporter);
        }

        return $this->createResponse($content, $filename, $exporter);
    }

    /**
     * Resolve project IDs from filters.
     *
     * @param int $userId
     * @param array $filters
     * @return array
     * @throws ValidationException
     */
    protected function resolveProjectIds(int $userId, array $filters): array
    {
        // Single project
        if (isset($filters['project_id'])) {
            $projectId = (int) $filters['project_id'];
            $project = AIVisibilityProject::find($projectId);

            if (!$project) {
                throw ValidationException::withMessages([
                    'project_id' => 'Project not found.',
                ]);
            }

            if ($project->user_id !== $userId) {
                throw ValidationException::withMessages([
                    'project_id' => 'You do not have access to this project.',
                ]);
            }

            return [$projectId];
        }

        // Multiple projects
        if (isset($filters['project_ids']) && is_array($filters['project_ids'])) {
            $projectIds = array_unique(array_map('intval', $filters['project_ids']));
            $projects = AIVisibilityProject::whereIn('id', $projectIds)
                ->where('user_id', $userId)
                ->pluck('id')
                ->toArray();

            if (empty($projects)) {
                throw ValidationException::withMessages([
                    'project_ids' => 'No valid projects found.',
                ]);
            }

            return $projects;
        }

        // All user's projects
        return AIVisibilityProject::where('user_id', $userId)
            ->pluck('id')
            ->toArray();
    }

    /**
     * Validate date range filters.
     *
     * @param array $filters
     * @return array ['from' => Carbon|null, 'to' => Carbon|null]
     * @throws ValidationException
     */
    protected function validateDateRange(array $filters): array
    {
        $from = null;
        $to = null;

        if (isset($filters['from'])) {
            try {
                $from = Carbon::parse($filters['from']);
            } catch (\Exception $e) {
                throw ValidationException::withMessages([
                    'from' => 'Invalid from date format.',
                ]);
            }
        }

        if (isset($filters['to'])) {
            try {
                $to = Carbon::parse($filters['to']);
            } catch (\Exception $e) {
                throw ValidationException::withMessages([
                    'to' => 'Invalid to date format.',
                ]);
            }
        }

        // Validate from is before to
        if ($from && $to && $from->isAfter($to)) {
            throw ValidationException::withMessages([
                'from' => 'The from date must be before the to date.',
            ]);
        }

        // Validate max 1 year range
        if ($from && $to) {
            $diffInDays = $from->diffInDays($to);
            if ($diffInDays > 365) {
                throw ValidationException::withMessages([
                    'date_range' => 'Date range cannot exceed 1 year.',
                ]);
            }
        }

        return ['from' => $from, 'to' => $to];
    }

    /**
     * Fetch data based on type.
     *
     * @param string $dataType
     * @param array $projectIds
     * @param array $dateRange
     * @return Collection
     */
    protected function fetchData(string $dataType, array $projectIds, array $dateRange): Collection
    {
        return match ($dataType) {
            'projects' => $this->fetchProjects($projectIds),
            'queries' => $this->fetchQueries($projectIds, $dateRange),
            'analytics' => $this->fetchAnalytics($projectIds, $dateRange),
            'mentions' => $this->fetchMentions($projectIds, $dateRange),
            default => $this->fetchQueries($projectIds, $dateRange),
        };
    }

    /**
     * Fetch projects data.
     *
     * @param array $projectIds
     * @return Collection
     */
    protected function fetchProjects(array $projectIds): Collection
    {
        return AIVisibilityProject::whereIn('id', $projectIds)
            ->get()
            ->map(function ($project) {
                return [
                    'id' => $project->id,
                    'brand_name' => $project->brand_name,
                    'keywords' => implode(', ', $project->keywords ?? []),
                    'competitors' => implode(', ', $project->getCompetitorNames()),
                    'tracking_frequency' => $project->tracking_frequency,
                    'is_active' => $project->is_active ? 'Yes' : 'No',
                    'last_queried_at' => $project->last_queried_at?->format('Y-m-d H:i:s'),
                    'created_at' => $project->created_at?->format('Y-m-d H:i:s'),
                    'updated_at' => $project->updated_at?->format('Y-m-d H:i:s'),
                ];
            });
    }

    /**
     * Fetch query results data.
     *
     * @param array $projectIds
     * @param array $dateRange
     * @return Collection
     */
    protected function fetchQueries(array $projectIds, array $dateRange): Collection
    {
        $query = AIQueryResult::whereIn('project_id', $projectIds)
            ->orderBy('queried_at', 'desc');

        if ($dateRange['from']) {
            $query->where('queried_at', '>=', $dateRange['from']);
        }

        if ($dateRange['to']) {
            $query->where('queried_at', '<=', $dateRange['to']);
        }

        return $query->get()->map(function ($result) {
            return [
                'id' => $result->id,
                'project_id' => $result->project_id,
                'platform' => $result->platform,
                'query_text' => $result->query_text,
                'brand_mentioned' => $result->brand_mentioned ? 'Yes' : 'No',
                'mentions' => json_encode($result->mentions ?? []),
                'sentiment' => $result->sentiment ?? 'neutral',
                'success' => $result->success ? 'Yes' : 'No',
                'queried_at' => $result->queried_at?->format('Y-m-d H:i:s'),
            ];
        });
    }

    /**
     * Fetch analytics data.
     *
     * @param array $projectIds
     * @param array $dateRange
     * @return Collection
     */
    protected function fetchAnalytics(array $projectIds, array $dateRange): Collection
    {
        $query = AIMentionAnalytics::whereIn('project_id', $projectIds)
            ->orderBy('period_start', 'desc');

        if ($dateRange['from']) {
            $query->where('period_start', '>=', $dateRange['from']);
        }

        if ($dateRange['to']) {
            $query->where('period_end', '<=', $dateRange['to']);
        }

        return $query->get()->map(function ($analytics) {
            return [
                'id' => $analytics->id,
                'project_id' => $analytics->project_id,
                'platform' => $analytics->platform,
                'period_start' => $analytics->period_start?->format('Y-m-d'),
                'period_end' => $analytics->period_end?->format('Y-m-d'),
                'total_queries' => $analytics->total_queries,
                'brand_mentions' => $analytics->brand_mentions,
                'mention_rate' => $analytics->mention_rate . '%',
                'positive_mentions' => $analytics->positive_mentions,
                'neutral_mentions' => $analytics->neutral_mentions,
                'negative_mentions' => $analytics->negative_mentions,
            ];
        });
    }

    /**
     * Fetch mentions data.
     *
     * @param array $projectIds
     * @param array $dateRange
     * @return Collection
     */
    protected function fetchMentions(array $projectIds, array $dateRange): Collection
    {
        $query = AIQueryResult::whereIn('project_id', $projectIds)
            ->whereNotNull('mentions')
            ->where('success', true)
            ->orderBy('queried_at', 'desc');

        if ($dateRange['from']) {
            $query->where('queried_at', '>=', $dateRange['from']);
        }

        if ($dateRange['to']) {
            $query->where('queried_at', '<=', $dateRange['to']);
        }

        $mentions = [];

        foreach ($query->get() as $result) {
            foreach ($result->mentions ?? [] as $mention) {
                $mentions[] = [
                    'query_id' => $result->id,
                    'project_id' => $result->project_id,
                    'platform' => $result->platform,
                    'brand' => $mention['brand'] ?? 'Unknown',
                    'mention_type' => $mention['type'] ?? 'mention',
                    'context' => Str::limit($mention['context'] ?? '', 200),
                    'sentiment' => $result->sentiment ?? 'neutral',
                    'queried_at' => $result->queried_at?->format('Y-m-d H:i:s'),
                ];
            }
        }

        return collect($mentions);
    }

    /**
     * Get columns for a data type.
     *
     * @param string $dataType
     * @return array
     */
    protected function getColumns(string $dataType): array
    {
        return match ($dataType) {
            'projects' => [
                'id' => 'ID',
                'brand_name' => 'Brand Name',
                'keywords' => 'Keywords',
                'competitors' => 'Competitors',
                'tracking_frequency' => 'Tracking Frequency',
                'is_active' => 'Active',
                'last_queried_at' => 'Last Queried',
                'created_at' => 'Created',
                'updated_at' => 'Updated',
            ],
            'queries' => [
                'id' => 'ID',
                'project_id' => 'Project ID',
                'platform' => 'Platform',
                'query_text' => 'Query',
                'brand_mentioned' => 'Brand Mentioned',
                'mentions' => 'Mentions',
                'sentiment' => 'Sentiment',
                'success' => 'Success',
                'queried_at' => 'Queried At',
            ],
            'analytics' => [
                'id' => 'ID',
                'project_id' => 'Project ID',
                'platform' => 'Platform',
                'period_start' => 'Period Start',
                'period_end' => 'Period End',
                'total_queries' => 'Total Queries',
                'brand_mentions' => 'Brand Mentions',
                'mention_rate' => 'Mention Rate',
                'positive_mentions' => 'Positive',
                'neutral_mentions' => 'Neutral',
                'negative_mentions' => 'Negative',
            ],
            'mentions' => [
                'query_id' => 'Query ID',
                'project_id' => 'Project ID',
                'platform' => 'Platform',
                'brand' => 'Brand',
                'mention_type' => 'Type',
                'context' => 'Context',
                'sentiment' => 'Sentiment',
                'queried_at' => 'Queried At',
            ],
            default => [
                'id' => 'ID',
                'project_id' => 'Project ID',
                'platform' => 'Platform',
                'query_text' => 'Query',
                'brand_mentioned' => 'Brand Mentioned',
                'mentions' => 'Mentions',
                'sentiment' => 'Sentiment',
                'success' => 'Success',
                'queried_at' => 'Queried At',
            ],
        };
    }

    /**
     * Export multi-sheet XLSX.
     *
     * @param array $projectIds
     * @param array $dateRange
     * @param string $filename
     * @return Response
     */
    protected function exportMultiSheetXlsx(array $projectIds, array $dateRange, string $filename): Response
    {
        $sheets = [
            [
                'name' => 'Projects',
                'data' => $this->fetchProjects($projectIds),
                'columns' => $this->getColumns('projects'),
            ],
            [
                'name' => 'Queries',
                'data' => $this->fetchQueries($projectIds, $dateRange),
                'columns' => $this->getColumns('queries'),
            ],
            [
                'name' => 'Analytics',
                'data' => $this->fetchAnalytics($projectIds, $dateRange),
                'columns' => $this->getColumns('analytics'),
            ],
            [
                'name' => 'Mentions',
                'data' => $this->fetchMentions($projectIds, $dateRange),
                'columns' => $this->getColumns('mentions'),
            ],
        ];

        $content = $this->xlsxExporter->export(collect([]), $filename, ['sheets' => $sheets]);

        return $this->createResponse($content, $filename, $this->xlsxExporter);
    }

    /**
     * Export multi-type XLSX.
     *
     * @param array $projectIds
     * @param array $dateRange
     * @param string $dataTypes
     * @param string $filename
     * @return Response
     */
    protected function exportMultiTypeXlsx(array $projectIds, array $dateRange, string $dataTypes, string $filename): Response
    {
        $types = array_map('trim', explode(',', $dataTypes));
        $sheets = [];

        foreach ($types as $type) {
            if (in_array($type, ['projects', 'queries', 'analytics', 'mentions'])) {
                $sheets[] = [
                    'name' => ucfirst($type),
                    'data' => $this->fetchData($type, $projectIds, $dateRange),
                    'columns' => $this->getColumns($type),
                ];
            }
        }

        if (empty($sheets)) {
            $sheets = [
                [
                    'name' => 'Queries',
                    'data' => $this->fetchQueries($projectIds, $dateRange),
                    'columns' => $this->getColumns('queries'),
                ],
            ];
        }

        $content = $this->xlsxExporter->export(collect([]), $filename, ['sheets' => $sheets]);

        return $this->createResponse($content, $filename, $this->xlsxExporter);
    }

    /**
     * Generate filename.
     *
     * @param string $format
     * @param array $filters
     * @return string
     */
    protected function generateFilename(string $format, array $filters): string
    {
        $date = now()->format('Y-m-d-His');

        if (isset($filters['project_id'])) {
            $project = AIVisibilityProject::find($filters['project_id']);
            if ($project) {
                $brandName = Str::slug($project->brand_name);
                return "ai-visibility-{$brandName}-{$date}";
            }
        }

        return "ai-visibility-export-{$date}";
    }

    /**
     * Create response with file content.
     *
     * @param string $content
     * @param string $filename
     * @param ExporterInterface $exporter
     * @return Response
     */
    protected function createResponse(string $content, string $filename, ExporterInterface $exporter): Response
    {
        return new Response($content, 200, [
            'Content-Type' => $exporter->getMimeType(),
            'Content-Disposition' => 'attachment; filename="' . $filename . '.' . $exporter->getExtension() . '"',
            'Cache-Control' => 'max-age=0',
            'Pragma' => 'public',
        ]);
    }

    /**
     * Create streamed response.
     *
     * @param \Generator $generator
     * @param string $filename
     * @param ExporterInterface $exporter
     * @return StreamedResponse
     */
    protected function streamResponse(\Generator $generator, string $filename, ExporterInterface $exporter): StreamedResponse
    {
        return new StreamedResponse(function () use ($generator) {
            foreach ($generator as $chunk) {
                echo $chunk;
                flush();
            }
        }, 200, [
            'Content-Type' => $exporter->getMimeType(),
            'Content-Disposition' => 'attachment; filename="' . $filename . '.' . $exporter->getExtension() . '"',
            'Cache-Control' => 'max-age=0',
            'Pragma' => 'public',
        ]);
    }

    /**
     * Check rate limit.
     *
     * @param int $userId
     * @throws ValidationException
     */
    protected function checkRateLimit(int $userId): void
    {
        $maxExports = config('ai-visibility.export_rate_limit', 10);
        $windowSeconds = 60; // 1 minute window

        $key = 'export_' . $userId;
        $now = time();

        // Initialize if not set
        if (!isset(self::$rateLimitCache[$key])) {
            self::$rateLimitCache[$key] = [];
        }

        // Remove expired entries
        self::$rateLimitCache[$key] = array_filter(
            self::$rateLimitCache[$key],
            fn($timestamp) => $timestamp > ($now - $windowSeconds)
        );

        // Check if exceeded
        if (count(self::$rateLimitCache[$key]) >= $maxExports) {
            throw ValidationException::withMessages([
                'rate_limit' => "Export rate limit exceeded. Maximum {$maxExports} exports per minute.",
            ]);
        }

        // Record this export
        self::$rateLimitCache[$key][] = $now;
    }

    /**
     * Clear rate limit cache (for testing).
     *
     * @param int|null $userId Specific user ID, or null to clear all
     */
    public static function clearRateLimitCache(?int $userId = null): void
    {
        if ($userId === null) {
            self::$rateLimitCache = [];
        } else {
            unset(self::$rateLimitCache['export_' . $userId]);
        }
    }

    /**
     * Log export.
     *
     * @param int $userId
     * @param array $filters
     * @param string $format
     * @param int $recordCount
     */
    protected function logExport(int $userId, array $filters, string $format, int $recordCount): void
    {
        $this->logger->info('AI Visibility export completed', [
            'user_id' => $userId,
            'format' => $format,
            'data_type' => $filters['data_type'] ?? 'all',
            'project_ids' => $filters['project_id'] ?? ($filters['project_ids'] ?? 'all'),
            'record_count' => $recordCount,
        ]);
    }
}
