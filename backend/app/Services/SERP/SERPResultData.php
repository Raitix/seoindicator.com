<?php

namespace App\Services\SERP;

class SERPResultData implements SERPResult
{
    /**
     * @param array<int, SERPEntry> $results
     */
    public function __construct(
        private readonly string $keyword,
        private readonly string $searchEngine,
        private readonly string $location,
        private readonly string $device,
        private readonly array $results,
        private readonly string $checkedAt,
        private readonly ?string $error,
        private readonly int $durationMs,
        private readonly ?array $serpFeatures = null
    ) {
    }

    public function getKeyword(): string
    {
        return $this->keyword;
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

    public function getResults(): array
    {
        return $this->results;
    }

    public function getCheckedAt(): string
    {
        return $this->checkedAt;
    }

    public function getError(): ?string
    {
        return $this->error;
    }

    public function getDurationMs(): int
    {
        return $this->durationMs;
    }

    public function getSerpFeatures(): ?array
    {
        return $this->serpFeatures;
    }
}
