<?php

namespace Tests\Unit\Services\SERP;

use Tests\TestCase;
use App\Services\SERP\MockSERPFetcher;
use App\Services\SERP\SERPOptionsData;

class MockSERPFetcherTest extends TestCase
{
    public function test_fetch_returns_expected_result_shape(): void
    {
        $fetcher = new MockSERPFetcher();
        $options = new SERPOptionsData(
            searchEngine: 'google',
            location: 'US',
            device: 'desktop',
            targetUrl: 'https://example-5.com/test',
            seed: 42,
            includeTargetUrl: true,
            targetUrlPosition: 5,
            simulateError: null,
            delayMs: 120
        );

        $result = $fetcher->fetch('seo tools', $options);

        $this->assertSame('seo tools', $result->getKeyword());
        $this->assertSame('google', $result->getSearchEngine());
        $this->assertSame('US', $result->getLocation());
        $this->assertSame('desktop', $result->getDevice());
        $this->assertCount(100, $result->getResults());
        $this->assertNull($result->getError());
        $this->assertGreaterThanOrEqual(100, $result->getDurationMs());
        $this->assertLessThanOrEqual(500, $result->getDurationMs());

        $positions = array_map(fn($entry) => $entry->getPosition(), $result->getResults());
        $this->assertSame(range(1, 100), $positions);
    }

    public function test_target_url_matching_sets_is_our_site(): void
    {
        $fetcher = new MockSERPFetcher();
        $targetUrl = 'https://example-3.com/seo-tools';
        $options = new SERPOptionsData(
            targetUrl: $targetUrl,
            seed: 123,
            includeTargetUrl: true,
            targetUrlPosition: 3,
            delayMs: 110
        );

        $result = $fetcher->fetch('seo tools', $options);
        $entry = $result->getResults()[2];

        $this->assertSame($targetUrl, $entry->getUrl());
        $this->assertTrue($entry->isOurSite());
    }

    public function test_target_url_not_in_results_when_disabled(): void
    {
        $fetcher = new MockSERPFetcher();
        $options = new SERPOptionsData(
            targetUrl: 'https://example-3.com/seo-tools',
            seed: 10,
            includeTargetUrl: false,
            delayMs: 105
        );

        $result = $fetcher->fetch('seo tools', $options);
        $matches = array_filter($result->getResults(), fn($entry) => $entry->isOurSite());

        $this->assertCount(0, $matches);
    }

    public function test_error_simulation_populates_error_field(): void
    {
        $fetcher = new MockSERPFetcher();
        $options = new SERPOptionsData(
            simulateError: 'network',
            delayMs: 100
        );

        $result = $fetcher->fetch('seo tools', $options);

        $this->assertSame('network', $result->getError());
        $this->assertCount(0, $result->getResults());
    }

    public function test_deterministic_results_with_seed(): void
    {
        $fetcher = new MockSERPFetcher();
        $options = new SERPOptionsData(
            seed: 999,
            delayMs: 100
        );

        $first = $fetcher->fetch('seo tools', $options);
        $second = $fetcher->fetch('seo tools', $options);

        $this->assertSame(
            $first->getResults()[10]->getUrl(),
            $second->getResults()[10]->getUrl()
        );

        $differentOptions = new SERPOptionsData(
            seed: 1000,
            delayMs: 100
        );
        $different = $fetcher->fetch('seo tools', $differentOptions);

        $this->assertNotSame(
            $first->getResults()[10]->getUrl(),
            $different->getResults()[10]->getUrl()
        );
    }
}
