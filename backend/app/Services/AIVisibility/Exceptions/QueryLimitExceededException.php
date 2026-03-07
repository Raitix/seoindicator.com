<?php

namespace App\Services\AIVisibility\Exceptions;

use Exception;

/**
 * Exception thrown when a user exceeds their monthly query limit.
 * 
 * @task TASK-34-5
 * @spec SPEC-034
 */
class QueryLimitExceededException extends Exception
{
    /**
     * The user's query limit.
     */
    public int $limit;

    /**
     * The number of queries used.
     */
    public int $used;

    /**
     * The user's tier.
     */
    public string $tier;

    /**
     * The number of remaining queries.
     */
    public int $remaining;

    /**
     * Create a new query limit exceeded exception.
     *
     * @param int $limit The user's query limit
     * @param int $used The number of queries used
     * @param string $tier The user's tier
     */
    public function __construct(int $limit, int $used, string $tier)
    {
        $this->limit = $limit;
        $this->used = $used;
        $this->tier = $tier;
        $this->remaining = max(0, $limit - $used);

        $message = sprintf(
            'You have exceeded your monthly query limit of %d queries (%s tier). Used: %d, Remaining: %d',
            $limit,
            $tier,
            $used,
            $this->remaining
        );

        parent::__construct($message);
    }

    /**
     * Get the exception context for logging.
     *
     * @return array
     */
    public function context(): array
    {
        return [
            'limit' => $this->limit,
            'used' => $this->used,
            'tier' => $this->tier,
            'remaining' => $this->remaining,
        ];
    }
}
