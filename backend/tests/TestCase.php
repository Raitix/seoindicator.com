<?php
namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

abstract class TestCase extends BaseTestCase
{
    /**
     * Indicates whether the default fixture should be used.
     */
    protected $useRefreshDatabase = false;

    /**
     * Setup the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // For SQLite in-memory database, we need to handle transactions carefully
        if ($this->useRefreshDatabase && config('database.connections.sqlite.database') === ':memory:') {
            // Run migrations once for in-memory database
            $this->artisan('migrate');
        }
    }

    /**
     * Clean up the test environment.
     */
    protected function tearDown(): void
    {
        parent::tearDown();
    }
}