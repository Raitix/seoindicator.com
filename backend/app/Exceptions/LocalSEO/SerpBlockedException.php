<?php

namespace App\Exceptions\LocalSEO;

use Exception;

/**
 * Exception thrown when Google SERP request is blocked (CAPTCHA, rate limit, etc.).
 */
class SerpBlockedException extends Exception
{
    public const TYPE_CAPTCHA = 'captcha';
    public const TYPE_RATE_LIMIT = 'rate_limit';
    public const TYPE_FORBIDDEN = 'forbidden';
    public const TYPE_UNKNOWN = 'unknown';

    protected string $blockType;
    protected ?string $query;
    protected ?int $httpStatus;

    public function __construct(
        string $blockType = self::TYPE_UNKNOWN,
        ?string $query = null,
        ?int $httpStatus = null,
        ?string $message = null
    ) {
        $this->blockType = $blockType;
        $this->query = $query;
        $this->httpStatus = $httpStatus;

        $message = $message ?? $this->getDefaultMessage();

        parent::__construct($message, $httpStatus ?? 403);
    }

    private function getDefaultMessage(): string
    {
        return match ($this->blockType) {
            self::TYPE_CAPTCHA => 'Google returned a CAPTCHA challenge. Please try again later.',
            self::TYPE_RATE_LIMIT => 'Google detected unusual traffic. Please wait before retrying.',
            self::TYPE_FORBIDDEN => 'Access to Google SERP was forbidden.',
            default => 'Google SERP request was blocked.',
        };
    }

    public function getBlockType(): string
    {
        return $this->blockType;
    }

    public function getQuery(): ?string
    {
        return $this->query;
    }

    public function getHttpStatus(): ?int
    {
        return $this->httpStatus;
    }

    public function toArray(): array
    {
        return [
            'error' => 'serp_blocked',
            'block_type' => $this->blockType,
            'query' => $this->query,
            'http_status' => $this->httpStatus,
            'message' => $this->getMessage(),
        ];
    }
}
