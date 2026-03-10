<?php

namespace Tests\Feature\Services\GBP;

use App\Services\GBP\GBPApiService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GBPApiServiceSandboxTest extends TestCase
{
    #[Test]
    public function gbp_api_sandbox_001_optional_live_smoke_can_run_when_sandbox_credentials_are_available(): void
    {
        $accessToken = env('GBP_SANDBOX_ACCESS_TOKEN');

        if (!$accessToken) {
            $this->markTestSkipped('GBP sandbox credentials are not configured for this environment.');
        }

        $service = app(GBPApiService::class);
        $accounts = $service->getAccounts($accessToken);

        $this->assertIsArray($accounts);
    }
}
