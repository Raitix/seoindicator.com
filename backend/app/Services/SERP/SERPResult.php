<?php

namespace App\Services\SERP;

interface SERPResult
{
    public function getKeyword(): string;
    public function getSearchEngine(): string;
    public function getLocation(): string;
    public function getDevice(): string;

    /**
     * @return array<int, SERPEntry>
     */
    public function getResults(): array;

    public function getCheckedAt(): string;
    public function getError(): ?string;
    public function getDurationMs(): int;

    /**
     * @return array<int, string>|null
     */
    public function getSerpFeatures(): ?array;
}
