<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\SerpFeatureChangeTracker;
use App\Repositories\SerpFeatureHistoryRepository;
use App\Models\SerpFeature;
use Illuminate\Support\Facades\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;

class SerpFeatureChangeTrackerTest extends TestCase
{
    protected SerpFeatureChangeTracker $tracker;
    protected $historyRepo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->historyRepo = Mockery::mock(SerpFeatureHistoryRepository::class);
        $this->tracker = new SerpFeatureChangeTracker($this->historyRepo);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // ============================================
    // Feature Gained Detection
    // ============================================

    /** @test */
    public function it_detects_gained_feature_when_previously_absent(): void
    {
        // Arrange
        $keywordId = 1;
        $currentFeatures = [
            ['type' => 'featured_snippet', 'is_present' => true, 'url' => 'https://example.com']
        ];
        $previousFeatures = [
            ['type' => 'featured_snippet', 'is_present' => false]
        ];

        $this->historyRepo
            ->shouldReceive('insert')
            ->once()
            ->with(Mockery::on(function ($arg) use ($keywordId) {
                return $arg['keyword_id'] === $keywordId
                    && $arg['feature_type'] === 'featured_snippet'
                    && $arg['action'] === 'gained';
            }));

        // Act
        $changes = $this->tracker->trackChanges($keywordId, $currentFeatures, $previousFeatures);

        // Assert
        $this->assertCount(1, $changes);
        $this->assertEquals('featured_snippet', $changes[0]['type']);
        $this->assertEquals('gained', $changes[0]['action']);
        $this->assertEquals('https://example.com', $changes[0]['current_url']);
    }

    /** @test */
    public function it_detects_multiple_gained_features(): void
    {
        // Arrange
        $keywordId = 1;
        $currentFeatures = [
            ['type' => 'featured_snippet', 'is_present' => true, 'url' => 'https://example.com/snippet'],
            ['type' => 'sitelinks', 'is_present' => true],
            ['type' => 'reviews', 'is_present' => true],
        ];
        $previousFeatures = [
            ['type' => 'featured_snippet', 'is_present' => false],
            ['type' => 'sitelinks', 'is_present' => false],
            ['type' => 'reviews', 'is_present' => false],
        ];

        $this->historyRepo->shouldReceive('insert')->times(3);

        // Act
        $changes = $this->tracker->trackChanges($keywordId, $currentFeatures, $previousFeatures);

        // Assert
        $this->assertCount(3, $changes);
        $gainedTypes = array_column($changes, 'type');
        $this->assertContains('featured_snippet', $gainedTypes);
        $this->assertContains('sitelinks', $gainedTypes);
        $this->assertContains('reviews', $gainedTypes);
    }

    /** @test */
    public function it_emits_feature_gained_event(): void
    {
        // Arrange
        Event::fake();

        $keywordId = 1;
        $currentFeatures = [
            ['type' => 'featured_snippet', 'is_present' => true, 'url' => 'https://example.com']
        ];
        $previousFeatures = [
            ['type' => 'featured_snippet', 'is_present' => false]
        ];

        $this->historyRepo->shouldReceive('insert');

        // Act
        $this->tracker->trackChanges($keywordId, $currentFeatures, $previousFeatures);

        // Assert
        Event::assertDispatched('serp-feature.gained', function ($event, $data) use ($keywordId) {
            return $data['keyword_id'] === $keywordId
                && $data['feature_type'] === 'featured_snippet';
        });
    }

    // ============================================
    // Feature Lost Detection
    // ============================================

    /** @test */
    public function it_detects_lost_feature_when_previously_present(): void
    {
        // Arrange
        $keywordId = 1;
        $currentFeatures = [
            ['type' => 'featured_snippet', 'is_present' => false]
        ];
        $previousFeatures = [
            ['type' => 'featured_snippet', 'is_present' => true, 'url' => 'https://example.com']
        ];

        $this->historyRepo
            ->shouldReceive('insert')
            ->once()
            ->with(Mockery::on(function ($arg) use ($keywordId) {
                return $arg['keyword_id'] === $keywordId
                    && $arg['feature_type'] === 'featured_snippet'
                    && $arg['action'] === 'lost';
            }));

        // Act
        $changes = $this->tracker->trackChanges($keywordId, $currentFeatures, $previousFeatures);

        // Assert
        $this->assertCount(1, $changes);
        $this->assertEquals('featured_snippet', $changes[0]['type']);
        $this->assertEquals('lost', $changes[0]['action']);
        $this->assertEquals('https://example.com', $changes[0]['previous_url']);
    }

    /** @test */
    public function it_detects_multiple_lost_features(): void
    {
        // Arrange
        $keywordId = 1;
        $currentFeatures = [
            ['type' => 'featured_snippet', 'is_present' => false],
            ['type' => 'local_pack', 'is_present' => false],
        ];
        $previousFeatures = [
            ['type' => 'featured_snippet', 'is_present' => true, 'url' => 'https://example.com/1'],
            ['type' => 'local_pack', 'is_present' => true, 'url' => 'https://example.com/2'],
        ];

        $this->historyRepo->shouldReceive('insert')->times(2);

        // Act
        $changes = $this->tracker->trackChanges($keywordId, $currentFeatures, $previousFeatures);

        // Assert
        $this->assertCount(2, $changes);
        foreach ($changes as $change) {
            $this->assertEquals('lost', $change['action']);
        }
    }

    /** @test */
    public function it_emits_feature_lost_event(): void
    {
        // Arrange
        Event::fake();

        $keywordId = 1;
        $currentFeatures = [
            ['type' => 'featured_snippet', 'is_present' => false]
        ];
        $previousFeatures = [
            ['type' => 'featured_snippet', 'is_present' => true, 'url' => 'https://example.com']
        ];

        $this->historyRepo->shouldReceive('insert');

        // Act
        $this->tracker->trackChanges($keywordId, $currentFeatures, $previousFeatures);

        // Assert
        Event::assertDispatched('serp-feature.lost', function ($event, $data) use ($keywordId) {
            return $data['keyword_id'] === $keywordId
                && $data['feature_type'] === 'featured_snippet';
        });
    }

    // ============================================
    // First-Time Detection (No Previous Features)
    // ============================================

    /** @test */
    public function it_returns_no_gained_events_for_first_check(): void
    {
        // Arrange - First check, no previous features
        $keywordId = 1;
        $currentFeatures = [
            ['type' => 'featured_snippet', 'is_present' => true, 'url' => 'https://example.com']
        ];
        $previousFeatures = []; // Empty = first check

        // Should NOT insert history or emit events for first check
        $this->historyRepo->shouldNotReceive('insert');

        // Act
        $changes = $this->tracker->trackChanges($keywordId, $currentFeatures, $previousFeatures);

        // Assert
        $this->assertEmpty($changes);
    }

    /** @test */
    public function it_distinguishes_first_check_from_subsequent_checks(): void
    {
        // Arrange
        $keywordId = 1;

        // First check - no changes
        $firstCheck = $this->tracker->trackChanges($keywordId, [
            ['type' => 'featured_snippet', 'is_present' => true]
        ], []);

        // Second check - feature lost
        $this->historyRepo->shouldReceive('insert')->once();
        $secondCheck = $this->tracker->trackChanges($keywordId, [
            ['type' => 'featured_snippet', 'is_present' => false]
        ], [
            ['type' => 'featured_snippet', 'is_present' => true]
        ]);

        // Assert
        $this->assertEmpty($firstCheck);
        $this->assertCount(1, $secondCheck);
        $this->assertEquals('lost', $secondCheck[0]['action']);
    }

    // ============================================
    // No Change Detection
    // ============================================

    /** @test */
    public function it_returns_empty_array_when_no_changes(): void
    {
        // Arrange
        $keywordId = 1;
        $currentFeatures = [
            ['type' => 'featured_snippet', 'is_present' => true, 'url' => 'https://example.com']
        ];
        $previousFeatures = [
            ['type' => 'featured_snippet', 'is_present' => true, 'url' => 'https://example.com']
        ];

        $this->historyRepo->shouldNotReceive('insert');

        // Act
        $changes = $this->tracker->trackChanges($keywordId, $currentFeatures, $previousFeatures);

        // Assert
        $this->assertEmpty($changes);
    }

    /** @test */
    public function it_handles_both_features_absent(): void
    {
        // Arrange
        $keywordId = 1;
        $currentFeatures = [
            ['type' => 'featured_snippet', 'is_present' => false]
        ];
        $previousFeatures = [
            ['type' => 'featured_snippet', 'is_present' => false]
        ];

        $this->historyRepo->shouldNotReceive('insert');

        // Act
        $changes = $this->tracker->trackChanges($keywordId, $currentFeatures, $previousFeatures);

        // Assert
        $this->assertEmpty($changes);
    }

    /** @test */
    public function it_handles_mixed_changes_and_no_changes(): void
    {
        // Arrange - one gained, one unchanged
        $keywordId = 1;
        $currentFeatures = [
            ['type' => 'featured_snippet', 'is_present' => true, 'url' => 'https://example.com'],
            ['type' => 'sitelinks', 'is_present' => true],
        ];
        $previousFeatures = [
            ['type' => 'featured_snippet', 'is_present' => false],
            ['type' => 'sitelinks', 'is_present' => true],
        ];

        $this->historyRepo->shouldReceive('insert')->once();

        // Act
        $changes = $this->tracker->trackChanges($keywordId, $currentFeatures, $previousFeatures);

        // Assert
        $this->assertCount(1, $changes);
        $this->assertEquals('featured_snippet', $changes[0]['type']);
        $this->assertEquals('gained', $changes[0]['action']);
    }

    // ============================================
    // URL Change Detection
    // ============================================

    /** @test */
    public function it_tracks_previous_url_for_lost_feature(): void
    {
        // Arrange
        $keywordId = 1;
        $currentFeatures = [
            ['type' => 'featured_snippet', 'is_present' => false]
        ];
        $previousFeatures = [
            ['type' => 'featured_snippet', 'is_present' => true, 'url' => 'https://example.com/old-url']
        ];

        $this->historyRepo
            ->shouldReceive('insert')
            ->once()
            ->with(Mockery::on(function ($arg) {
                return $arg['previous_url'] === 'https://example.com/old-url'
                    && $arg['current_url'] === null;
            }));

        // Act
        $changes = $this->tracker->trackChanges($keywordId, $currentFeatures, $previousFeatures);

        // Assert
        $this->assertEquals('https://example.com/old-url', $changes[0]['previous_url']);
        $this->assertNull($changes[0]['current_url']);
    }

    /** @test */
    public function it_tracks_current_url_for_gained_feature(): void
    {
        // Arrange
        $keywordId = 1;
        $currentFeatures = [
            ['type' => 'featured_snippet', 'is_present' => true, 'url' => 'https://example.com/new-url']
        ];
        $previousFeatures = [
            ['type' => 'featured_snippet', 'is_present' => false]
        ];

        $this->historyRepo
            ->shouldReceive('insert')
            ->once()
            ->with(Mockery::on(function ($arg) {
                return $arg['current_url'] === 'https://example.com/new-url'
                    && $arg['previous_url'] === null;
            }));

        // Act
        $changes = $this->tracker->trackChanges($keywordId, $currentFeatures, $previousFeatures);

        // Assert
        $this->assertEquals('https://example.com/new-url', $changes[0]['current_url']);
        $this->assertNull($changes[0]['previous_url']);
    }

    // ============================================
    // Multiple Feature Types
    // ============================================

    /** @test */
    public function it_processes_all_feature_types(): void
    {
        // Arrange - All feature types gained
        $keywordId = 1;
        $allTypes = SerpFeature::FEATURE_TYPES;

        $currentFeatures = array_map(function ($type) {
            return ['type' => $type, 'is_present' => true, 'url' => "https://example.com/{$type}"];
        }, $allTypes);

        $previousFeatures = array_map(function ($type) {
            return ['type' => $type, 'is_present' => false];
        }, $allTypes);

        $this->historyRepo->shouldReceive('insert')->times(count($allTypes));

        // Act
        $changes = $this->tracker->trackChanges($keywordId, $currentFeatures, $previousFeatures);

        // Assert
        $this->assertCount(count($allTypes), $changes);
    }

    /** @test */
    public function it_detects_mixed_gained_and_lost(): void
    {
        // Arrange
        $keywordId = 1;
        $currentFeatures = [
            ['type' => 'featured_snippet', 'is_present' => true, 'url' => 'https://example.com/new'],   // Gained
            ['type' => 'local_pack', 'is_present' => false],                                                // Lost
            ['type' => 'sitelinks', 'is_present' => true],                                                  // Unchanged
        ];
        $previousFeatures = [
            ['type' => 'featured_snippet', 'is_present' => false],
            ['type' => 'local_pack', 'is_present' => true, 'url' => 'https://example.com/local'],
            ['type' => 'sitelinks', 'is_present' => true],
        ];

        $this->historyRepo->shouldReceive('insert')->times(2);

        // Act
        $changes = $this->tracker->trackChanges($keywordId, $currentFeatures, $previousFeatures);

        // Assert
        $this->assertCount(2, $changes);

        $gained = array_filter($changes, fn($c) => $c['action'] === 'gained');
        $lost = array_filter($changes, fn($c) => $c['action'] === 'lost');

        $this->assertCount(1, $gained);
        $this->assertCount(1, $lost);
        $this->assertEquals('featured_snippet', reset($gained)['type']);
        $this->assertEquals('local_pack', reset($lost)['type']);
    }

    /** @test */
    public function it_handles_partial_feature_sets(): void
    {
        // Arrange - Only some feature types reported
        $keywordId = 1;
        $currentFeatures = [
            ['type' => 'featured_snippet', 'is_present' => true],
            // Missing other types - should treat as not present
        ];
        $previousFeatures = [
            ['type' => 'featured_snippet', 'is_present' => false],
            ['type' => 'local_pack', 'is_present' => true, 'url' => 'https://example.com'],
        ];

        $this->historyRepo->shouldReceive('insert')->times(2);

        // Act
        $changes = $this->tracker->trackChanges($keywordId, $currentFeatures, $previousFeatures);

        // Assert - featured_snippet gained, local_pack lost
        $this->assertCount(2, $changes);
    }

    // ============================================
    // Edge Cases
    // ============================================

    /** @test */
    public function it_handles_null_url_values(): void
    {
        // Arrange - Feature with no URL (e.g., sitelinks don't have URLs)
        $keywordId = 1;
        $currentFeatures = [
            ['type' => 'sitelinks', 'is_present' => true, 'url' => null]
        ];
        $previousFeatures = [
            ['type' => 'sitelinks', 'is_present' => false, 'url' => null]
        ];

        $this->historyRepo->shouldReceive('insert')->once();

        // Act
        $changes = $this->tracker->trackChanges($keywordId, $currentFeatures, $previousFeatures);

        // Assert
        $this->assertCount(1, $changes);
        $this->assertNull($changes[0]['current_url']);
        $this->assertNull($changes[0]['previous_url']);
    }

    /** @test */
    public function it_handles_missing_feature_in_current_but_present_in_previous(): void
    {
        // Arrange - Feature was removed from current report entirely
        $keywordId = 1;
        $currentFeatures = [
            // featured_snippet missing entirely from current
        ];
        $previousFeatures = [
            ['type' => 'featured_snippet', 'is_present' => true, 'url' => 'https://example.com']
        ];

        $this->historyRepo->shouldReceive('insert')->once();

        // Act
        $changes = $this->tracker->trackChanges($keywordId, $currentFeatures, $previousFeatures);

        // Assert - Should treat missing as lost
        $this->assertCount(1, $changes);
        $this->assertEquals('lost', $changes[0]['action']);
    }

    /** @test */
    public function it_handles_missing_feature_in_previous_but_present_in_current(): void
    {
        // Arrange - Feature appears for first time in current
        $keywordId = 1;
        $currentFeatures = [
            ['type' => 'featured_snippet', 'is_present' => true, 'url' => 'https://example.com']
        ];
        $previousFeatures = [
            // featured_snippet missing from previous
        ];

        // Should NOT trigger gained (could have been present before our tracking started)
        $this->historyRepo->shouldNotReceive('insert');

        // Act
        $changes = $this->tracker->trackChanges($keywordId, $currentFeatures, $previousFeatures);

        // Assert
        $this->assertEmpty($changes);
    }

    /** @test */
    public function it_handles_empty_feature_arrays(): void
    {
        // Arrange
        $keywordId = 1;
        $currentFeatures = [];
        $previousFeatures = [];

        // Act
        $changes = $this->tracker->trackChanges($keywordId, $currentFeatures, $previousFeatures);

        // Assert
        $this->assertEmpty($changes);
    }

    /** @test */
    public function it_validates_feature_type_enum(): void
    {
        // Arrange - Invalid feature type
        $keywordId = 1;
        $currentFeatures = [
            ['type' => 'invalid_feature_type', 'is_present' => true]
        ];
        $previousFeatures = [
            ['type' => 'invalid_feature_type', 'is_present' => false]
        ];

        // Act & Assert - Should skip invalid feature types
        $changes = $this->tracker->trackChanges($keywordId, $currentFeatures, $previousFeatures);
        $this->assertEmpty($changes);
    }

    // ============================================
    // History Persistence
    // ============================================

    /** @test */
    public function it_persists_gained_change_to_database(): void
    {
        // Arrange
        $keywordId = 1;
        $currentFeatures = [
            ['type' => 'featured_snippet', 'is_present' => true, 'url' => 'https://example.com']
        ];
        $previousFeatures = [
            ['type' => 'featured_snippet', 'is_present' => false]
        ];

        $this->historyRepo
            ->shouldReceive('insert')
            ->once()
            ->with(Mockery::on(function ($arg) use ($keywordId) {
                return $arg['keyword_id'] === $keywordId
                    && $arg['feature_type'] === 'featured_snippet'
                    && $arg['action'] === 'gained'
                    && $arg['current_url'] === 'https://example.com';
            }));

        // Act
        $changes = $this->tracker->trackChanges($keywordId, $currentFeatures, $previousFeatures);

        // Assert
        $this->assertCount(1, $changes);
        $this->assertEquals('gained', $changes[0]['action']);
    }

    /** @test */
    public function it_persists_lost_change_to_database(): void
    {
        // Arrange
        $keywordId = 1;
        $currentFeatures = [
            ['type' => 'featured_snippet', 'is_present' => false]
        ];
        $previousFeatures = [
            ['type' => 'featured_snippet', 'is_present' => true, 'url' => 'https://example.com']
        ];

        $this->historyRepo
            ->shouldReceive('insert')
            ->once()
            ->with(Mockery::on(function ($arg) use ($keywordId) {
                return $arg['keyword_id'] === $keywordId
                    && $arg['feature_type'] === 'featured_snippet'
                    && $arg['action'] === 'lost'
                    && $arg['previous_url'] === 'https://example.com';
            }));

        // Act
        $changes = $this->tracker->trackChanges($keywordId, $currentFeatures, $previousFeatures);

        // Assert
        $this->assertCount(1, $changes);
        $this->assertEquals('lost', $changes[0]['action']);
    }

    /** @test */
    public function it_records_correct_timestamp(): void
    {
        // Arrange
        $keywordId = 1;
        $currentFeatures = [
            ['type' => 'featured_snippet', 'is_present' => true]
        ];
        $previousFeatures = [
            ['type' => 'featured_snippet', 'is_present' => false]
        ];

        $beforeTime = now();

        $this->historyRepo
            ->shouldReceive('insert')
            ->once()
            ->with(Mockery::on(function ($arg) use ($beforeTime) {
                $detectedAt = $arg['detected_at'];
                return $detectedAt >= $beforeTime && $detectedAt <= now();
            }));

        // Act
        $changes = $this->tracker->trackChanges($keywordId, $currentFeatures, $previousFeatures);

        // Assert
        $this->assertCount(1, $changes);
        $this->assertNotNull($changes[0]['detected_at']);
    }

    // ============================================
    // Event Emission
    // ============================================

    /** @test */
    public function it_dispatches_events_for_each_change(): void
    {
        // Arrange
        Event::fake();

        $keywordId = 1;
        $currentFeatures = [
            ['type' => 'featured_snippet', 'is_present' => true],
            ['type' => 'local_pack', 'is_present' => false],
        ];
        $previousFeatures = [
            ['type' => 'featured_snippet', 'is_present' => false],
            ['type' => 'local_pack', 'is_present' => true],
        ];

        $this->historyRepo->shouldReceive('insert')->times(2);

        // Act
        $this->tracker->trackChanges($keywordId, $currentFeatures, $previousFeatures);

        // Assert
        Event::assertDispatchedTimes('serp-feature.gained', 1);
        Event::assertDispatchedTimes('serp-feature.lost', 1);
    }

    /** @test */
    public function it_includes_all_change_data_in_event(): void
    {
        // Arrange
        Event::fake();

        $keywordId = 1;
        $currentFeatures = [
            ['type' => 'featured_snippet', 'is_present' => true, 'url' => 'https://example.com/snippet']
        ];
        $previousFeatures = [
            ['type' => 'featured_snippet', 'is_present' => false]
        ];

        $this->historyRepo->shouldReceive('insert');

        // Act
        $this->tracker->trackChanges($keywordId, $currentFeatures, $previousFeatures);

        // Assert
        Event::assertDispatched('serp-feature.gained', function ($event, $data) {
            return isset($data['keyword_id'])
                && isset($data['feature_type'])
                && isset($data['current_url'])
                && isset($data['detected_at']);
        });
    }

    // ============================================
    // Error Handling
    // ============================================

    /** @test */
    public function it_throws_exception_for_invalid_keyword_id(): void
    {
        // Arrange
        $this->expectException(\InvalidArgumentException::class);

        // Act
        $this->tracker->trackChanges(-1, [], []);
    }

    /** @test */
    public function it_handles_database_write_failure_gracefully(): void
    {
        // Arrange
        $keywordId = 1;
        $currentFeatures = [
            ['type' => 'featured_snippet', 'is_present' => true]
        ];
        $previousFeatures = [
            ['type' => 'featured_snippet', 'is_present' => false]
        ];

        $this->historyRepo
            ->shouldReceive('insert')
            ->once()
            ->andThrow(new \Exception('Database error'));

        // Act & Assert - Should not throw, should log error
        $changes = $this->tracker->trackChanges($keywordId, $currentFeatures, $previousFeatures);

        // Still return the changes even if DB write failed
        $this->assertCount(1, $changes);
    }

    /** @test */
    public function it_continues_processing_on_partial_failure(): void
    {
        // Arrange - Multiple changes, one fails
        $keywordId = 1;
        $currentFeatures = [
            ['type' => 'featured_snippet', 'is_present' => true],
            ['type' => 'local_pack', 'is_present' => true],
        ];
        $previousFeatures = [
            ['type' => 'featured_snippet', 'is_present' => false],
            ['type' => 'local_pack', 'is_present' => false],
        ];

        $callCount = 0;
        $mockHistory = Mockery::mock(SerpFeature::class);
        $this->historyRepo
            ->shouldReceive('insert')
            ->times(2)
            ->andReturnUsing(function () use (&$callCount, $mockHistory) {
                $callCount++;
                if ($callCount === 1) {
                    throw new \Exception('First insert failed');
                }
                return $mockHistory;
            });

        // Act
        $changes = $this->tracker->trackChanges($keywordId, $currentFeatures, $previousFeatures);

        // Assert - Should still process both changes
        $this->assertCount(2, $changes);
    }

    // ============================================
    // Return Value Structure
    // ============================================

    /** @test */
    public function it_returns_array_with_correct_structure(): void
    {
        // Arrange
        $keywordId = 1;
        $currentFeatures = [
            ['type' => 'featured_snippet', 'is_present' => true, 'url' => 'https://example.com']
        ];
        $previousFeatures = [
            ['type' => 'featured_snippet', 'is_present' => false]
        ];

        $this->historyRepo->shouldReceive('insert');

        // Act
        $changes = $this->tracker->trackChanges($keywordId, $currentFeatures, $previousFeatures);

        // Assert
        $this->assertIsArray($changes);
        $this->assertArrayHasKey('type', $changes[0]);
        $this->assertArrayHasKey('action', $changes[0]);
        $this->assertArrayHasKey('keyword_id', $changes[0]);
        $this->assertArrayHasKey('previous_url', $changes[0]);
        $this->assertArrayHasKey('current_url', $changes[0]);
        $this->assertArrayHasKey('detected_at', $changes[0]);
    }
}
