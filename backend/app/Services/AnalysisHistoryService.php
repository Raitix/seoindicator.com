<?php

namespace App\Services;

use App\Models\AnalysisHistory;
use App\Repositories\AnalysisHistoryRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * Service for managing analysis history records.
 * 
 * Provides methods for creating, updating, and querying analysis history
 * records that track tool executions for authenticated and anonymous users.
 * 
 * @covers TASK-25-14
 */
class AnalysisHistoryService
{
    protected AnalysisHistoryRepository $repository;

    public function __construct(AnalysisHistoryRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Create a new analysis history record.
     *
     * @param array $data
     * @return AnalysisHistory
     */
    public function createAnalysis(array $data): AnalysisHistory
    {
        // Ensure analyzed_at is set if not provided
        if (!isset($data['analyzed_at'])) {
            $data['analyzed_at'] = now();
        }

        // Truncate error message if too long for TEXT column
        if (isset($data['error_message'])) {
            $data['error_message'] = $this->truncateErrorMessage($data['error_message']);
        }

        return $this->repository->create($data);
    }

    /**
     * Complete an analysis with results and duration.
     *
     * @param int $id
     * @param array $results
     * @param int $durationMs
     * @return void
     */
    public function completeAnalysis(int $id, array $results, int $durationMs): void
    {
        $this->repository->update($id, [
            'status' => 'completed',
            'results' => $results,
            'analysis_duration_ms' => $durationMs,
            'analyzed_at' => now(),
        ]);
    }

    /**
     * Mark an analysis as failed with an error message.
     *
     * @param int $id
     * @param string $errorMessage
     * @return void
     */
    public function failAnalysis(int $id, string $errorMessage): void
    {
        $this->repository->update($id, [
            'status' => 'failed',
            'error_message' => $this->truncateErrorMessage($errorMessage),
            'analyzed_at' => now(),
        ]);
    }

    /**
     * Get paginated analyses for a user with optional filters.
     *
     * @param int $userId
     * @param array $filters
     * @return LengthAwarePaginator
     */
    public function getUserAnalyses(int $userId, array $filters = []): LengthAwarePaginator
    {
        return $this->repository->findByUser($userId, $filters);
    }

    /**
     * Get a single analysis by ID.
     *
     * @param int $id
     * @return AnalysisHistory|null
     */
    public function getAnalysis(int $id): ?AnalysisHistory
    {
        return $this->repository->findById($id);
    }

    /**
     * Delete an analysis record.
     *
     * @param int $id
     * @return bool
     */
    public function deleteAnalysis(int $id): bool
    {
        return $this->repository->delete($id);
    }

    /**
     * Calculate duration in milliseconds from a start time.
     *
     * @param float $startTime Microtime from microtime(true)
     * @return int Duration in milliseconds
     */
    public function calculateDuration(float $startTime): int
    {
        $elapsed = microtime(true) - $startTime;
        return (int) round($elapsed * 1000);
    }

    /**
     * Associate a site with an analysis.
     *
     * @param int $analysisId
     * @param int $siteId
     * @return void
     */
    public function associateSite(int $analysisId, int $siteId): void
    {
        $this->repository->update($analysisId, ['site_id' => $siteId]);
    }

    /**
     * Get analysis statistics for a user.
     *
     * @param int $userId
     * @return array
     */
    public function getUserStats(int $userId): array
    {
        return $this->repository->getStats($userId);
    }

    /**
     * Truncate error message to fit TEXT column.
     *
     * @param string $message
     * @return string
     */
    protected function truncateErrorMessage(string $message): string
    {
        $maxLength = 65535; // TEXT column max
        
        if (strlen($message) > $maxLength) {
            return substr($message, 0, $maxLength - 3) . '...';
        }
        
        return $message;
    }
}
