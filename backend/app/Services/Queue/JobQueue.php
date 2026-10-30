<?php

namespace App\Services\Queue;

use Illuminate\Support\Str;
use Carbon\Carbon;
use PDO;
use RuntimeException;

/**
 * SQLite-backed job queue for background rank checks.
 */
class JobQueue implements JobQueueInterface
{
    protected PDO $pdo;
    protected string $databasePath;

    public function __construct(?string $databasePath = null)
    {
        $this->databasePath = $databasePath ?? storage_path('app/queue.sqlite');
        $this->initialize();
    }

    /**
     * Add a job to the queue.
     *
     * @param array<string, mixed> $payload
     */
    public function addJob(array $payload, string $priority = 'normal', int $maxAttempts = 3): string
    {
        $this->assertValidPriority($priority);

        $id = 'job-' . Str::uuid()->toString();
        $type = (string) ($payload['type'] ?? 'check');
        $payloadJson = json_encode($payload, JSON_UNESCAPED_UNICODE);

        if ($payloadJson === false) {
            throw new RuntimeException('Failed to encode job payload.');
        }

        $scheduledFor = $payload['scheduledFor'] ?? $payload['availableAt'] ?? null;
        $createdAt = $scheduledFor ? $this->normalizeDateTime($scheduledFor) : $this->now();

        $stmt = $this->pdo->prepare('INSERT INTO queue_jobs (id, type, payload, priority, status, attempts, max_attempts, created_at)
            VALUES (:id, :type, :payload, :priority, :status, :attempts, :max_attempts, :created_at)');

        $stmt->execute([
            ':id' => $id,
            ':type' => $type,
            ':payload' => $payloadJson,
            ':priority' => $priority,
            ':status' => 'pending',
            ':attempts' => 0,
            ':max_attempts' => $maxAttempts,
            ':created_at' => $createdAt,
        ]);

        return $id;
    }

    /**
     * Get a job by id.
     */
    public function getJob(string $jobId): ?CheckJob
    {
        $job = $this->fetchJob('queue_jobs', $jobId);
        if ($job) {
            return $job;
        }

        return $this->fetchJob('queue_dead_letters', $jobId);
    }

    /**
     * Update job fields and return the latest job state.
     *
     * @param array<string, mixed> $data
     */
    public function updateJob(string $jobId, array $data): ?CheckJob
    {
        $job = $this->fetchJob('queue_jobs', $jobId);
        if (!$job) {
            return $this->getJob($jobId);
        }

        $status = $data['status'] ?? $job->status;
        $error = $data['error'] ?? $job->error;
        $priority = $data['priority'] ?? $job->priority;
        $payload = $data['payload'] ?? $job->payload;
        $attempts = array_key_exists('attempts', $data) ? (int) $data['attempts'] : $job->attempts;
        $maxAttempts = array_key_exists('maxAttempts', $data) ? (int) $data['maxAttempts'] : $job->maxAttempts;

        $this->assertValidPriority($priority);

        $payloadJson = json_encode($payload, JSON_UNESCAPED_UNICODE);
        if ($payloadJson === false) {
            throw new RuntimeException('Failed to encode job payload.');
        }

        $startedAt = $job->startedAt;
        $completedAt = $job->completedAt;
        $failedAt = $job->failedAt;

        if ($status === 'running' && !$startedAt) {
            $startedAt = $this->now();
        }

        if ($status === 'completed' && !$completedAt) {
            $completedAt = $this->now();
        }

        if ($status === 'failed') {
            $attempts = $attempts + 1;
            if ($attempts >= $maxAttempts) {
                $failedAt = $this->now();
                $this->insertDeadLetter($job, $payloadJson, $priority, $status, $attempts, $maxAttempts, $failedAt, $error);
                $this->deleteJob($jobId);
                return $this->fetchJob('queue_dead_letters', $jobId);
            }

            $status = 'pending';
            $startedAt = null;
        }

        $stmt = $this->pdo->prepare('UPDATE queue_jobs SET type = :type, payload = :payload, priority = :priority, status = :status,
            attempts = :attempts, max_attempts = :max_attempts, started_at = :started_at, completed_at = :completed_at, error = :error
            WHERE id = :id');

        $stmt->execute([
            ':id' => $jobId,
            ':type' => $job->type,
            ':payload' => $payloadJson,
            ':priority' => $priority,
            ':status' => $status,
            ':attempts' => $attempts,
            ':max_attempts' => $maxAttempts,
            ':started_at' => $startedAt,
            ':completed_at' => $completedAt,
            ':error' => $error,
        ]);

        return $this->fetchJob('queue_jobs', $jobId);
    }

    /**
     * Get pending jobs ordered by priority.
     *
     * @return array<int, CheckJob>
     */
    public function getPending(int $limit = 10): array
    {
        $this->cleanupCompleted();

        $stmt = $this->pdo->prepare("SELECT * FROM queue_jobs WHERE status = 'pending'
            AND created_at <= datetime('now')
            ORDER BY CASE priority
                WHEN 'high' THEN 1
                WHEN 'normal' THEN 2
                WHEN 'low' THEN 3
                ELSE 2
            END, created_at ASC
            LIMIT :limit");

        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $jobs = [];
        foreach ($rows as $row) {
            $jobs[] = CheckJob::fromRow($row);
        }

        return $jobs;
    }

    protected function initialize(): void
    {
        $directory = dirname($this->databasePath);
        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        $this->pdo = new PDO('sqlite:' . $this->databasePath);
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $this->pdo->exec("CREATE TABLE IF NOT EXISTS queue_jobs (
            id TEXT PRIMARY KEY,
            type TEXT NOT NULL,
            payload TEXT NOT NULL,
            priority TEXT NOT NULL,
            status TEXT NOT NULL,
            attempts INTEGER NOT NULL DEFAULT 0,
            max_attempts INTEGER NOT NULL DEFAULT 3,
            created_at TEXT NOT NULL,
            started_at TEXT NULL,
            completed_at TEXT NULL,
            error TEXT NULL
        )");

        $this->pdo->exec("CREATE TABLE IF NOT EXISTS queue_dead_letters (
            id TEXT PRIMARY KEY,
            type TEXT NOT NULL,
            payload TEXT NOT NULL,
            priority TEXT NOT NULL,
            status TEXT NOT NULL,
            attempts INTEGER NOT NULL,
            max_attempts INTEGER NOT NULL,
            created_at TEXT NOT NULL,
            started_at TEXT NULL,
            completed_at TEXT NULL,
            failed_at TEXT NULL,
            error TEXT NULL
        )");
    }

    protected function now(): string
    {
        return gmdate('Y-m-d H:i:s');
    }

    /**
     * @param mixed $value
     */
    protected function normalizeDateTime(mixed $value): string
    {
        if ($value instanceof \DateTimeInterface) {
            return Carbon::instance($value)->utc()->format('Y-m-d H:i:s');
        }

        if (is_int($value)) {
            return Carbon::createFromTimestampUTC($value)->format('Y-m-d H:i:s');
        }

        if (is_string($value) && $value !== '') {
            return Carbon::parse($value)->utc()->format('Y-m-d H:i:s');
        }

        return $this->now();
    }

    protected function cleanupCompleted(): void
    {
        $stmt = $this->pdo->prepare("DELETE FROM queue_jobs
            WHERE status = 'completed' AND completed_at IS NOT NULL
            AND completed_at <= datetime('now', '-7 days')");
        $stmt->execute();
    }

    protected function assertValidPriority(string $priority): void
    {
        $valid = ['high', 'normal', 'low'];
        if (!in_array($priority, $valid, true)) {
            throw new RuntimeException('Invalid priority: ' . $priority);
        }
    }

    protected function fetchJob(string $table, string $jobId): ?CheckJob
    {
        $stmt = $this->pdo->prepare("SELECT * FROM {$table} WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $jobId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        return CheckJob::fromRow($row);
    }

    protected function deleteJob(string $jobId): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM queue_jobs WHERE id = :id');
        $stmt->execute([':id' => $jobId]);
    }

    protected function insertDeadLetter(
        CheckJob $job,
        string $payloadJson,
        string $priority,
        string $status,
        int $attempts,
        int $maxAttempts,
        string $failedAt,
        ?string $error
    ): void {
        $stmt = $this->pdo->prepare('INSERT INTO queue_dead_letters
            (id, type, payload, priority, status, attempts, max_attempts, created_at, started_at, completed_at, failed_at, error)
            VALUES (:id, :type, :payload, :priority, :status, :attempts, :max_attempts, :created_at, :started_at, :completed_at, :failed_at, :error)');

        $stmt->execute([
            ':id' => $job->id,
            ':type' => $job->type,
            ':payload' => $payloadJson,
            ':priority' => $priority,
            ':status' => $status,
            ':attempts' => $attempts,
            ':max_attempts' => $maxAttempts,
            ':created_at' => $job->createdAt,
            ':started_at' => $job->startedAt,
            ':completed_at' => $job->completedAt,
            ':failed_at' => $failedAt,
            ':error' => $error,
        ]);
    }
}
