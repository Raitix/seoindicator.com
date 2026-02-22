<?php

namespace App\Services\SERP;

class SERPOptionsData implements SERPOptions
{
    public function __construct(
        private readonly string $searchEngine = 'google',
        private readonly string $location = 'US',
        private readonly string $device = 'desktop',
        private readonly ?string $targetUrl = null,
        private readonly ?int $seed = null,
        private readonly bool $includeTargetUrl = true,
        private readonly ?int $targetUrlPosition = null,
        private readonly ?string $simulateError = null,
        private readonly ?int $delayMs = null
    ) {
    }

    public function getSearchEngine(): string
    {
        return $this->searchEngine;
    }

    public function getLocation(): string
    {
        return $this->location;
    }

    public function getDevice(): string
    {
        return $this->device;
    }

    public function getTargetUrl(): ?string
    {
        return $this->targetUrl;
    }

    public function getSeed(): ?int
    {
        return $this->seed;
    }

    public function shouldIncludeTargetUrl(): bool
    {
        return $this->includeTargetUrl;
    }

    public function getTargetUrlPosition(): ?int
    {
        return $this->targetUrlPosition;
    }

    public function getSimulateError(): ?string
    {
        return $this->simulateError;
    }

    public function getDelayMs(): ?int
    {
        return $this->delayMs;
    }
}
