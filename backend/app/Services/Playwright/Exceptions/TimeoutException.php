<?php

namespace App\Services\Playwright\Exceptions;

/**
 * Exception thrown when a page render times out.
 *
 * @see TASK-22-5
 * @see SPEC-022
 */
class TimeoutException extends \RuntimeException
{
    protected string $url;
    protected int $timeout;

    public function __construct(string $url, int $timeout, string $message = '')
    {
        $this->url = $url;
        $this->timeout = $timeout;
        $message = $message ?: "Timeout after {$timeout}ms waiting for page: {$url}";

        parent::__construct($message, 408);
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getTimeout(): int
    {
        return $this->timeout;
    }
}
