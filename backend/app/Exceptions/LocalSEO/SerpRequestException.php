<?php

namespace App\Exceptions\LocalSEO;

use Exception;

/**
 * Exception thrown when SERP request fails due to network/server errors.
 */
class SerpRequestException extends Exception
{
    public const TYPE_TIMEOUT = 'timeout';
    public const TYPE_CONNECTION = 'connection';
    public const TYPE_DNS = 'dns';
    public const TYPE_SERVER_ERROR = 'server_error';
    public const TYPE_SSL = 'ssl';
    public const TYPE_UNKNOWN = 'unknown';

    protected string $errorType;
    protected ?string $url;
    protected ?int $httpStatus;
    protected bool $shouldRetry;

    public function __construct(
        string $errorType = self::TYPE_UNKNOWN,
        ?string $url = null,
        ?int $httpStatus = null,
        bool $shouldRetry = false,
        ?string $message = null
    ) {
        $this->errorType = $errorType;
        $this->url = $url;
        $this->httpStatus = $httpStatus;
        $this->shouldRetry = $shouldRetry;

        $message = $message ?? $this->getDefaultMessage();

        parent::__construct($message, $httpStatus ?? 500);
    }

    private function getDefaultMessage(): string
    {
        return match ($this->errorType) {
            self::TYPE_TIMEOUT => 'The SERP request timed out. Please try again.',
            self::TYPE_CONNECTION => 'Could not connect to Google. Please check your connection.',
            self::TYPE_DNS => 'Could not resolve Google domain. DNS lookup failed.',
            self::TYPE_SERVER_ERROR => 'Google server returned an error. Please try again later.',
            self::TYPE_SSL => 'SSL certificate error when connecting to Google.',
            default => 'An error occurred while fetching SERP data.',
        };
    }

    public function getErrorType(): string
    {
        return $this->errorType;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function getHttpStatus(): ?int
    {
        return $this->httpStatus;
    }

    public function shouldRetry(): bool
    {
        return $this->shouldRetry;
    }

    public function toArray(): array
    {
        return [
            'error' => 'serp_request_failed',
            'error_type' => $this->errorType,
            'url' => $this->url,
            'http_status' => $this->httpStatus,
            'should_retry' => $this->shouldRetry,
            'message' => $this->getMessage(),
        ];
    }
}
