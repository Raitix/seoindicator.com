<?php

use PHPUnit\Framework\TestCase;
use App\Services\SERP\MockSERPFetcher;
use App\Services\SERP\SERPOptions;

class Task0017Test extends TestCase
{
    private $fetcher;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fetcher = new MockSERPFetcher();
    }

    public function testMockReturns100Results()
    {
        $options = new SERPOptions('google', 'US', 'desktop', 'example.com');
        $results = $this->fetcher->fetch($options);
        $this->assertCount(100, $results);
    }

    public function testSimulatedError()
    {
        $this->fetcher->simulateError(true);
        $this->expectException(Exception::class);
        $this->fetcher->fetch(new SERPOptions('google', 'US', 'desktop', 'example.com'));
    }

    public function testDeterministicResults()
    {
        $this->fetcher->setSeed(42);
        $results1 = $this->fetcher->fetch(new SERPOptions('google', 'US', 'desktop', 'example.com'));
        $results2 = $this->fetcher->fetch(new SERPOptions('google', 'US', 'desktop', 'example.com'));
        $this->assertEquals($results1, $results2);
    }
}