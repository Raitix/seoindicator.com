<?php

namespace App\Services\Playwright\Exceptions;

/**
 * Exception thrown when page navigation fails.
 *
 * @see TASK-22-5
 * @see SPEC-022
 */
class NavigationException extends \RuntimeException
{
    protected string $url;
    protected ?string $reason;

    public function __construct(string $url, ?string $reason = null, string $message = '')
    {
        $this->url = $url;
        $this->reason = $reason;
        $message = $message ?: "Navigation failed for URL: {$url}";
        if ($reason) {
            $message .= " ({$reason})";
        }

        parent::__construct($message, 502);
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getReason(): ?string
    {
        return $this->reason;
    }
}
