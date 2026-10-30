<?php

namespace App\Services\Queue;

use App\Models\Keyword;
use App\Repositories\KeywordRepository;
use App\Repositories\RankingRepository;
use App\Services\SERP\SERPOptionsData;
use App\Services\SERPFetcherInterface;
use Carbon\Carbon;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Rank checker worker that processes queued rank check jobs.
 */
class RankCheckerWorker
{
    public function __construct(
        protected JobQueueInterface $queue,
        protected KeywordRepository $keywordRepository,
        protected RankingRepository $rankingRepository,
        protected SERPFetcherInterface $fetcher,
        protected LoggerInterface $logger,
        protected int $concurrency = 5
    ) {
    }

    /**
     * Process a batch of pending jobs.
     */
    public function runOnce(?int $limit = null): int
    {
        $limit = $limit ?? $this->concurrency;
        $jobs = $this->queue->getPending($limit);
        $processed = 0;

        foreach ($jobs as $job) {
            $this->processJob($job);
            $processed++;
        }

        return $processed;
    }

    protected function processJob(CheckJob $job): void
    {
        $this->queue->updateJob($job->id, ['status' => 'running']);

        try {
            $this->handleJob($job);
            $this->queue->updateJob($job->id, ['status' => 'completed']);
        } catch (\Throwable $error) {
            $this->logger->error('Rank checker job failed', [
                'jobId' => $job->id,
                'type' => $job->type,
                'error' => $error->getMessage(),
            ]);

            $this->queue->updateJob($job->id, [
                'status' => 'failed',
                'error' => $error->getMessage(),
            ]);
        }
    }

    protected function handleJob(CheckJob $job): void
    {
        $type = $job->type ?: (string) ($job->payload['type'] ?? '');

        switch ($type) {
            case 'single':
                $keywordId = (int) ($job->payload['keywordId'] ?? 0);
                if (!$keywordId) {
                    throw new RuntimeException('Missing keywordId for single job.');
                }
                $this->handleSingle($keywordId);
                return;
            case 'site':
                $siteId = (int) ($job->payload['siteId'] ?? 0);
                if (!$siteId) {
                    throw new RuntimeException('Missing siteId for site job.');
                }
                $this->handleSite($siteId);
                return;
            case 'batch':
                $keywordIds = $job->payload['keywordIds'] ?? [];
                if (!is_array($keywordIds) || empty($keywordIds)) {
                    throw new RuntimeException('Missing keywordIds for batch job.');
                }
                $this->handleBatch($keywordIds);
                return;
            default:
                throw new RuntimeException('Unsupported job type: ' . $type);
        }
    }

    protected function handleSingle(int $keywordId): void
    {
        $keyword = $this->keywordRepository->findById($keywordId);
        if (!$keyword) {
            throw new RuntimeException('Keyword not found: ' . $keywordId);
        }

        $this->checkKeyword($keyword);
    }

    protected function handleSite(int $siteId): void
    {
        $keywords = $this->keywordRepository->getKeywordsNeedingCheck($siteId);
        if ($keywords->isEmpty()) {
            return;
        }

        $failures = 0;

        foreach ($keywords as $keyword) {
            try {
                $this->checkKeyword($keyword);
            } catch (\Throwable $error) {
                $failures++;
                $this->logger->error('Rank checker keyword failed', [
                    'keywordId' => $keyword->id,
                    'siteId' => $siteId,
                    'error' => $error->getMessage(),
                ]);
            }
        }

        if ($failures > 0) {
            throw new RuntimeException("{$failures} keyword checks failed for site {$siteId}.");
        }
    }

    /**
     * @param array<int, mixed> $keywordIds
     */
    protected function handleBatch(array $keywordIds): void
    {
        $failures = 0;

        foreach ($keywordIds as $keywordId) {
            $keywordId = (int) $keywordId;
            if (!$keywordId) {
                continue;
            }

            $keyword = $this->keywordRepository->findById($keywordId);
            if (!$keyword) {
                $failures++;
                continue;
            }

            try {
                $this->checkKeyword($keyword);
            } catch (\Throwable $error) {
                $failures++;
                $this->logger->error('Rank checker batch keyword failed', [
                    'keywordId' => $keywordId,
                    'error' => $error->getMessage(),
                ]);
            }
        }

        if ($failures > 0) {
            throw new RuntimeException("{$failures} keyword checks failed for batch job.");
        }
    }

    protected function checkKeyword(Keyword $keyword): void
    {
        $options = new SERPOptionsData(
            $keyword->search_engine ?? 'google',
            $keyword->location ?? 'US',
            $keyword->device ?? 'desktop',
            $keyword->target_url
        );

        $result = $this->fetcher->fetch($keyword->keyword, $options);

        $position = null;
        $urlFound = null;

        foreach ($result->getResults() as $entry) {
            if ($entry->isOurSite()) {
                $position = $entry->getPosition();
                $urlFound = $entry->getUrl();
                break;
            }
        }

        $checkedAt = Carbon::parse($result->getCheckedAt());

        $this->rankingRepository->create([
            'keyword_id' => $keyword->id,
            'position' => $position,
            'url_found' => $urlFound,
            'serp_features' => null,
            'checked_at' => $checkedAt,
            'check_duration_ms' => $result->getDurationMs(),
            'error' => $result->getError(),
        ]);

        $keyword->last_checked_at = $checkedAt;
        $keyword->save();

        if ($result->getError()) {
            throw new RuntimeException($result->getError());
        }
    }
}
