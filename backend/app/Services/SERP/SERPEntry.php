<?php

namespace App\Services\SERP;

interface SERPEntry
{
    public function getPosition(): int;
    public function getUrl(): string;
    public function getTitle(): string;
    public function getDescription(): string;
    public function isOurSite(): bool;
}
