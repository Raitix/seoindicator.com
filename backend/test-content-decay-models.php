<?php

use App\Models\ContentDecayProject;
use App\Models\ContentDecayCandidate;
use App\Models\ContentDecaySignal;
use App\Models\ContentRefreshTask;
use App\Models\User;
use App\Models\Site;

echo '=== CONTENT DECAY MODELS - PRODUCTION VALIDATION ===' . PHP_EOL . PHP_EOL;

// Find a user and site for testing
$user = User::first();
$site = Site::first();
if (!$user || !$site) {
    echo 'ERROR: No user or site found in database' . PHP_EOL;
    exit(1);
}

echo 'Using User ID: ' . $user->id . ', Site ID: ' . $site->id . PHP_EOL . PHP_EOL;

$allPassed = true;

// 1. Test ContentDecayProject model
echo '1. ContentDecayProject Model:' . PHP_EOL;
$project = ContentDecayProject::create([
    'user_id' => $user->id,
    'site_id' => $site->id,
    'detection_enabled' => true,
    'window_days' => 30,
    'digest_frequency' => 'weekly',
]);
echo '   Created project ID: ' . $project->id . PHP_EOL;
echo '   isDetectionEnabled(): ' . ($project->isDetectionEnabled() ? 'PASS' : 'FAIL') . PHP_EOL;
echo '   getWindowDays(): ' . $project->getWindowDays() . PHP_EOL;
$allPassed = $allPassed && $project->isDetectionEnabled() && $project->getWindowDays() === 30;
echo '   BelongsTo User: ' . ($project->user->id === $user->id ? 'PASS' : 'FAIL') . PHP_EOL;
echo '   BelongsTo Site: ' . ($project->site->id === $site->id ? 'PASS' : 'FAIL') . PHP_EOL;
echo '   Scope enabled(): ' . (ContentDecayProject::enabled()->where('id', $project->id)->exists() ? 'PASS' : 'FAIL') . PHP_EOL;
echo '   Scope forUser(): ' . (ContentDecayProject::forUser($user->id)->where('id', $project->id)->exists() ? 'PASS' : 'FAIL') . PHP_EOL;

// 2. Test ContentDecayCandidate model
echo PHP_EOL . '2. ContentDecayCandidate Model:' . PHP_EOL;
$candidate = ContentDecayCandidate::create([
    'project_id' => $project->id,
    'page_url' => 'https://example.com/test-page-' . time(),
    'page_url_hash' => hash('sha256', 'https://example.com/test-page-' . time()),
    'page_title' => 'Test Page',
    'page_type' => 'blog',
    'severity' => 'high',
    'decay_score' => 75.50,
    'detection_confidence' => 90.00,
    'primary_reason_code' => 'traffic_decline',
    'reason_codes' => ['traffic_decline', 'position_drop'],
    'status' => 'backlog',
]);
echo '   Created candidate ID: ' . $candidate->id . PHP_EOL;
echo '   page_url_hash length: ' . (strlen($candidate->page_url_hash) === 64 ? 'PASS (64 chars)' : 'FAIL') . PHP_EOL;
echo '   BelongsTo Project: ' . ($candidate->project->id === $project->id ? 'PASS' : 'FAIL') . PHP_EOL;
echo '   Scope backlog(): ' . (ContentDecayCandidate::backlog()->where('id', $candidate->id)->exists() ? 'PASS' : 'FAIL') . PHP_EOL;
echo '   Scope bySeverity(high): ' . (ContentDecayCandidate::bySeverity('high')->where('id', $candidate->id)->exists() ? 'PASS' : 'FAIL') . PHP_EOL;

// Test status transition
$candidate->markAsPlanned();
echo '   markAsPlanned(): ' . ($candidate->status === 'planned' && $candidate->planned_at !== null ? 'PASS' : 'FAIL') . PHP_EOL;
$allPassed = $allPassed && $candidate->status === 'planned';

// 3. Test ContentDecaySignal model
echo PHP_EOL . '3. ContentDecaySignal Model:' . PHP_EOL;
$signal = ContentDecaySignal::create([
    'candidate_id' => $candidate->id,
    'previous_start' => '2026-02-01',
    'previous_end' => '2026-02-28',
    'current_start' => '2026-03-01',
    'current_end' => '2026-03-07',
    'previous_clicks' => 1000,
    'current_clicks' => 800,
    'previous_impressions' => 10000,
    'current_impressions' => 9500,
    'previous_ctr' => 0.10,
    'current_ctr' => 0.084,
]);
echo '   Created signal ID: ' . $signal->id . PHP_EOL;
echo '   BelongsTo Candidate: ' . ($signal->candidate->id === $candidate->id ? 'PASS' : 'FAIL') . PHP_EOL;
$clickDelta = $signal->getClickDelta();
echo '   getClickDelta(): ' . $clickDelta . ' (' . ($clickDelta === -200 ? 'PASS' : 'FAIL') . ')' . PHP_EOL;
$impDelta = $signal->getImpressionDelta();
echo '   getImpressionDelta(): ' . $impDelta . ' (' . ($impDelta === -500 ? 'PASS' : 'FAIL') . ')' . PHP_EOL;
echo '   Scope forCandidate(): ' . (ContentDecaySignal::forCandidate($candidate->id)->where('id', $signal->id)->exists() ? 'PASS' : 'FAIL') . PHP_EOL;
$allPassed = $allPassed && $clickDelta === -200;

// 4. Test ContentRefreshTask model
echo PHP_EOL . '4. ContentRefreshTask Model:' . PHP_EOL;
$task = ContentRefreshTask::create([
    'candidate_id' => $candidate->id,
    'user_id' => $user->id,
    'title' => 'Update test page',
    'description' => 'Refresh content to recover rankings',
    'due_date' => now()->addDays(7),
    'status' => 'todo',
    'source' => 'manual',
]);
echo '   Created task ID: ' . $task->id . PHP_EOL;
echo '   BelongsTo Candidate: ' . ($task->candidate->id === $candidate->id ? 'PASS' : 'FAIL') . PHP_EOL;
echo '   BelongsTo User (owner): ' . ($task->owner->id === $user->id ? 'PASS' : 'FAIL') . PHP_EOL;
echo '   Scope todo(): ' . (ContentRefreshTask::todo()->where('id', $task->id)->exists() ? 'PASS' : 'FAIL') . PHP_EOL;
echo '   isOverdue(): ' . ($task->isOverdue() ? 'true' : 'false (correct - due in 7 days)') . PHP_EOL;

// Test status transition
$task->markAsDone();
echo '   markAsDone(): ' . ($task->status === 'done' && $task->completed_at !== null ? 'PASS' : 'FAIL') . PHP_EOL;
$allPassed = $allPassed && $task->status === 'done';

// 5. Test relationships
echo PHP_EOL . '5. Relationship Tests:' . PHP_EOL;
$candidateCount = $project->candidates()->count();
$signalCount = $candidate->signals()->count();
$taskCount = $candidate->tasks()->count();
echo '   Project->candidates count: ' . $candidateCount . ' (' . ($candidateCount >= 1 ? 'PASS' : 'FAIL') . ')' . PHP_EOL;
echo '   Candidate->signals count: ' . $signalCount . ' (' . ($signalCount >= 1 ? 'PASS' : 'FAIL') . ')' . PHP_EOL;
echo '   Candidate->tasks count: ' . $taskCount . ' (' . ($taskCount >= 1 ? 'PASS' : 'FAIL') . ')' . PHP_EOL;
$allPassed = $allPassed && $candidateCount >= 1 && $signalCount >= 1 && $taskCount >= 1;

// Cleanup
echo PHP_EOL . '=== CLEANUP ===' . PHP_EOL;
$task->delete();
$signal->delete();
$candidate->delete();
$project->delete();
echo 'Test records deleted' . PHP_EOL;

echo PHP_EOL . '=== PRODUCTION VALIDATION ' . ($allPassed ? 'PASSED' : 'FAILED') . ' ===' . PHP_EOL;
exit($allPassed ? 0 : 1);
