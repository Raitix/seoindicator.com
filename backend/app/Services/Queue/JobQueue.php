<?php

namespace App\Services\Queue;

use Illuminate\Support\Str;

/**
 * Lightweight job queue stub for manual rank checks.
 *
 * TODO: Replace with real queue implementation (SPEC-001-10).
 */
class JobQueue
{
    /**
     * Add a job to the queue.
     *
     * @param array<string, mixed> $payload
     */
    public function addJob(array $payload): string
    {
        return 'job-' . Str::uuid()->toString();
    }
}
