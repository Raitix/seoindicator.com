<?php

namespace Tests\Unit\Services\SERP;

use Tests\TestCase;
use App\Services\SERP\SERPEntry;
use App\Services\SERP\SERPEntryData;
use App\Services\SERP\SERPOptions;
use App\Services\SERP\SERPOptionsData;
use App\Services\SERP\SERPResult;
use App\Services\SERP\SERPResultData;

class SERPResultTest extends TestCase
{
    public function test_interfaces_exist_and_data_classes_implement(): void
    {
        $entry = new SERPEntryData(1, 'https://example.com', 'Title', 'Description', true);
        $options = new SERPOptionsData();
        $result = new SERPResultData(
            'keyword',
            'google',
            'US',
            'desktop',
            [$entry],
            (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format(DATE_ATOM),
            null,
            123
        );

        $this->assertInstanceOf(SERPEntry::class, $entry);
        $this->assertInstanceOf(SERPOptions::class, $options);
        $this->assertInstanceOf(SERPResult::class, $result);

        $this->assertSame('keyword', $result->getKeyword());
        $this->assertSame('google', $result->getSearchEngine());
        $this->assertSame('US', $result->getLocation());
        $this->assertSame('desktop', $result->getDevice());
        $this->assertCount(1, $result->getResults());
        $this->assertNull($result->getError());
        $this->assertIsInt($result->getDurationMs());
    }
}
