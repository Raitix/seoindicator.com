<?php

namespace App\Services\Queue;

/**
 * Data container for queue jobs.
 */
class CheckJob
{
    public string $id;
    public string $type;
    /** @var array<string, mixed> */
    public array $payload;
    public string $priority;
    public string $status;
    public int $attempts;
    public int $maxAttempts;
    public string $createdAt;
    public ?string $startedAt;
    public ?string $completedAt;
    public ?string $failedAt;
    public ?string $error;

    /**
     * @param array<string, mixed> $payload
     */
    public function __construct(
        string $id,
        string $type,
        array $payload,
        string $priority,
        string $status,
        int $attempts,
        int $maxAttempts,
        string $createdAt,
        ?string $startedAt,
        ?string $completedAt,
        ?string $failedAt,
        ?string $error
    ) {
        $this->id = $id;
        $this->type = $type;
        $this->payload = $payload;
        $this->priority = $priority;
        $this->status = $status;
        $this->attempts = $attempts;
        $this->maxAttempts = $maxAttempts;
        $this->createdAt = $createdAt;
        $this->startedAt = $startedAt;
        $this->completedAt = $completedAt;
        $this->failedAt = $failedAt;
        $this->error = $error;
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function fromRow(array $row): self
    {
        $payload = [];
        if (!empty($row['payload'])) {
            $decoded = json_decode($row['payload'], true);
            if (is_array($decoded)) {
                $payload = $decoded;
            }
        }

        return new self(
            (string) $row['id'],
            (string) $row['type'],
            $payload,
            (string) $row['priority'],
            (string) $row['status'],
            (int) $row['attempts'],
            (int) $row['max_attempts'],
            (string) $row['created_at'],
            $row['started_at'] ?? null,
            $row['completed_at'] ?? null,
            $row['failed_at'] ?? null,
            $row['error'] ?? null
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'payload' => $this->payload,
            'priority' => $this->priority,
            'status' => $this->status,
            'attempts' => $this->attempts,
            'maxAttempts' => $this->maxAttempts,
            'createdAt' => $this->createdAt,
            'startedAt' => $this->startedAt,
            'completedAt' => $this->completedAt,
            'failedAt' => $this->failedAt,
            'error' => $this->error,
        ];
    }
}
