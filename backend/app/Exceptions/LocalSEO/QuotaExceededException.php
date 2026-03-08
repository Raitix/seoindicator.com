<?php

namespace App\Exceptions\LocalSEO;

use Exception;

/**
 * Exception thrown when monthly rank check quota is exceeded.
 */
class QuotaExceededException extends Exception
{
    protected int $locationId;
    protected int $currentCount;
    protected int $limit;

    public function __construct(
        int $locationId,
        int $currentCount,
        int $limit = 50,
        ?string $message = null
    ) {
        $this->locationId = $locationId;
        $this->currentCount = $currentCount;
        $this->limit = $limit;

        $message = $message ?? "Monthly quota exceeded for location {$locationId}. Current: {$currentCount}, Limit: {$limit}";

        parent::__construct($message, 429);
    }

    public function getLocationId(): int
    {
        return $this->locationId;
    }

    public function getCurrentCount(): int
    {
        return $this->currentCount;
    }

    public function getLimit(): int
    {
        return $this->limit;
    }

    public function toArray(): array
    {
        return [
            'error' => 'quota_exceeded',
            'location_id' => $this->locationId,
            'current_count' => $this->currentCount,
            'limit' => $this->limit,
            'message' => $this->getMessage(),
        ];
    }
}
