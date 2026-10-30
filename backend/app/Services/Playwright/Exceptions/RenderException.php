<?php

namespace App\Services\Playwright\Exceptions;

/**
 * Exception thrown when content rendering/extraction fails.
 *
 * @see TASK-22-5
 * @see SPEC-022
 */
class RenderException extends \RuntimeException
{
    protected string $url;
    protected ?string $phase;

    public function __construct(string $url, string $phase = null, string $message = '')
    {
        $this->url = $url;
        $this->phase = $phase;
        $message = $message ?: "Render failed for URL: {$url}";
        if ($phase) {
            $message .= " during {$phase}";
        }

        parent::__construct($message, 500);
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getPhase(): ?string
    {
        return $this->phase;
    }
}
