<?php

use App\Models\User;
use App\Models\Site;
use App\Models\ContentDecayProject;
use App\Services\ContentDecay\ContentDecayDigestService;

$user = User::factory()->create();
$site = Site::factory()->create(['user_id' => $user->id]);
$project = ContentDecayProject::factory()->create([
    'site_id' => $site->id,
    'user_id' => $user->id,
    'digest_enabled' => true,
    'detection_enabled' => true,
    'digest_frequency' => 'weekly',
    'last_digest_sent_at' => now()->subDays(8),
]);

echo 'Project ID: ' . $project->id . PHP_EOL;
echo 'digest_enabled: ' . ($project->digest_enabled ? 'true' : 'false') . PHP_EOL;
echo 'detection_enabled: ' . ($project->detection_enabled ? 'true' : 'false') . PHP_EOL;
echo 'digest_frequency: ' . $project->digest_frequency . PHP_EOL;
echo 'last_digest_sent_at: ' . $project->last_digest_sent_at . PHP_EOL;

$service = app(ContentDecayDigestService::class);
echo 'shouldSendDigest: ' . ($service->shouldSendDigest($project) ? 'true' : 'false') . PHP_EOL;
