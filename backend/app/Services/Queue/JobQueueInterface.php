<?php

namespace App\Services\Queue;

/**
 * Job queue interface for background rank checks.
 */
interface JobQueueInterface
{
    /**
     * Add a job to the queue.
     *
     * @param array<string, mixed> $payload
     */
    public function addJob(array $payload, string $priority = 'normal', int $maxAttempts = 3): string;

    /**
     * Get a job by id.
     */
    public function getJob(string $jobId): ?CheckJob;

    /**
     * Update job fields and return the latest job state.
     *
     * @param array<string, mixed> $data
     */
    public function updateJob(string $jobId, array $data): ?CheckJob;

    /**
     * Get pending jobs ordered by priority.
     *
     * @return array<int, CheckJob>
     */
    public function getPending(int $limit = 10): array;
}
