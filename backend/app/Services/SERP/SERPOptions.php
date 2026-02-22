<?php

namespace App\Services\SERP;

interface SERPOptions
{
    public function getSearchEngine(): string;
    public function getLocation(): string;
    public function getDevice(): string;
    public function getTargetUrl(): ?string;
    public function getSeed(): ?int;
    public function shouldIncludeTargetUrl(): bool;
    public function getTargetUrlPosition(): ?int;
    public function getSimulateError(): ?string;
    public function getDelayMs(): ?int;
}
