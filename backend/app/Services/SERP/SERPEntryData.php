<?php

namespace App\Services\SERP;

class SERPEntryData implements SERPEntry
{
    public function __construct(
        private readonly int $position,
        private readonly string $url,
        private readonly string $title,
        private readonly string $description,
        private readonly bool $isOurSite
    ) {
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function isOurSite(): bool
    {
        return $this->isOurSite;
    }
}
