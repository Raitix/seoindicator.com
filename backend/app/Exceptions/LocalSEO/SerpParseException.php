<?php

namespace App\Exceptions\LocalSEO;

use Exception;

/**
 * Exception thrown when SERP HTML parsing fails.
 */
class SerpParseException extends Exception
{
    protected ?string $html;
    protected ?string $parseError;

    public function __construct(
        ?string $html = null,
        ?string $parseError = null,
        ?string $message = null
    ) {
        $this->html = $html;
        $this->parseError = $parseError;

        $message = $message ?? 'Failed to parse SERP response.';

        parent::__construct($message, 422);
    }

    public function getHtml(): ?string
    {
        return $this->html;
    }

    public function getParseError(): ?string
    {
        return $this->parseError;
    }

    public function toArray(): array
    {
        return [
            'error' => 'serp_parse_failed',
            'parse_error' => $this->parseError,
            'message' => $this->getMessage(),
        ];
    }
}
