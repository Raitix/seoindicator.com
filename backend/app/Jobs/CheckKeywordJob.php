<?php

namespace App\Jobs;

use App\Services\RankCheckerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CheckKeywordJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $keywordId;
    public int $tries = 3;
    public int $timeout = 120;

    public function __construct(int $keywordId)
    {
        $this->keywordId = $keywordId;
    }

    public function handle(RankCheckerService $service): void
    {
        try {
            $service->checkAndStore($this->keywordId);
        } catch (\Throwable $error) {
            Log::error('Keyword check job failed', [
                'keywordId' => $this->keywordId,
                'error' => $error->getMessage(),
            ]);

            throw $error;
        }
    }
}
