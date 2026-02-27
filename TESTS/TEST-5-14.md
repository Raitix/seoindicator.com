# TEST-5-14: Integration Tests - Keyword Discovery Workflow

## Task Reference
- **Task**: [TASK-5-14](../TASKS/TASK-5-14.md)
- **Specification**: [SPEC-005](../SPECIFICATIONS/SPEC-005.md)
- **Feature**: [FEAT-005](../FEATURES/FEAT-005.md)

## Overview
End-to-end integration tests for the keyword discovery workflow, covering API endpoints, database interactions, and full user flows. These tests verify that all components work together correctly.

---

## Test Environment

- **Database**: SQLite in-memory for isolation
- **HTTP Client**: Laravel HTTP fake for external APIs
- **Authentication**: Acting as test user
- **RefreshDatabase**: Before each test

---

## Test Files

| File | Type | Description |
|------|------|-------------|
| `backend/tests/Feature/Integration/KeywordDiscoveryIntegrationTest.php` | Feature | Full workflow integration tests |
| `backend/tests/Feature/Integration/SuggestionWorkflowTest.php` | Feature | User action workflow tests |

---

## Test Scenarios

### 1. Generate Suggestions Workflow

| ID | Scenario | Steps | Expected Result |
|----|----------|-------|-----------------|
| I-001 | Generate suggestions → verify stored | 1. Create site<br>2. POST /suggestions/generate<br>3. Query database | Suggestions stored with correct source and opportunity scores |
| I-002 | Generate with question source only | 1. POST with sources=['question'] | Only question keywords generated |
| I-003 | Generate with suggest source only | 1. Mock Google Suggest API<br>2. POST with sources=['suggest'] | Only suggest keywords generated |
| I-004 | Generate with both sources | 1. POST with sources=['question','suggest'] | Both types stored, deduplicated |
| I-005 | Generate respects maxResults | 1. POST with maxResults=10 | At most 10 suggestions stored |

### 2. List Suggestions Workflow

| ID | Scenario | Steps | Expected Result |
|----|----------|-------|-----------------|
| I-006 | List suggestions with pagination | 1. Generate 50 suggestions<br>2. GET /suggestions?page=2&perPage=10 | 10 items, correct offset |
| I-007 | List with source filter | 1. Generate mixed sources<br>2. GET ?source=question | Only question source items |
| I-008 | List excludes dismissed by default | 1. Dismiss suggestion<br>2. GET /suggestions | Dismissed not in results |
| I-009 | List excludes tracking by default | 1. Track suggestion<br>2. GET /suggestions | Tracked not in results |
| I-010 | List sorted by opportunity score DESC | 1. Generate varied scores<br>2. GET /suggestions | Results ordered by score desc |

### 3. Track Suggestion Workflow

| ID | Scenario | Steps | Expected Result |
|----|----------|-------|-----------------|
| I-011 | Track single suggestion | 1. Generate suggestion<br>2. POST /suggestions/:id/track<br>3. Query keywords table | Keyword created, suggestion.is_tracking=true |
| I-012 | Track already tracking | 1. Track suggestion<br>2. Track again | No duplicate keyword, idempotent |
| I-013 | Track creates correct keyword data | 1. Track suggestion<br>2. Query keywords | Keyword has correct site_id, keyword text, status='active' |
| I-014 | Bulk track suggestions | 1. Generate 5 suggestions<br>2. POST /bulk-track with 3 IDs | 3 keywords created, 3 suggestions updated |

### 4. Dismiss Suggestion Workflow

| ID | Scenario | Steps | Expected Result |
|----|----------|-------|-----------------|
| I-015 | Dismiss single suggestion | 1. Generate suggestion<br>2. POST /suggestions/:id/dismiss | suggestion.is_dismissed=true, dismissed_suggestions record created |
| I-016 | Dismiss already dismissed | 1. Dismiss suggestion<br>2. Dismiss again | Idempotent, no error |
| I-017 | Dismissed not in default list | 1. Dismiss suggestion<br>2. GET /suggestions | Dismissed not returned |
| I-018 | Dismissed shown with includeDismissed | 1. Dismiss suggestion<br>2. GET ?includeDismissed=true | Dismissed included |

### 5. Authorization Workflow

| ID | Scenario | Steps | Expected Result |
|----|----------|-------|-----------------|
| I-019 | User cannot access other user's site | 1. Create user A site<br>2. Login as user B<br>3. GET /sites/A-site/suggestions | 403 Forbidden |
| I-020 | User cannot track other user's suggestion | 1. Create user A suggestion<br>2. Login as user B<br>3. POST /suggestions/A-id/track | 403 Forbidden |
| I-021 | Unauthenticated access denied | 1. GET /suggestions without auth | 401 Unauthorized |

### 6. Full User Flow

| ID | Scenario | Steps | Expected Result |
|----|----------|-------|-----------------|
| I-022 | Complete discovery workflow | 1. Create site<br>2. Generate suggestions<br>3. List suggestions<br>4. Track one<br>5. Dismiss another<br>6. List again | Correct counts and states at each step |
| I-023 | Rediscovery updates dismissed status | 1. Generate suggestion<br>2. Dismiss it<br>3. Generate same keyword again | Suggestion.undismissed, is_dismissed=false |
| I-024 | Export suggestions | 1. Generate suggestions<br>2. GET /export?format=csv | Valid CSV with correct headers and data |

### 7. Error Handling

| ID | Scenario | Steps | Expected Result |
|----|----------|-------|-----------------|
| I-025 | Generate with invalid site | 1. POST /sites/999/suggestions/generate | 404 Not Found |
| I-026 | Track non-existent suggestion | 1. POST /suggestions/999/track | 404 Not Found |
| I-027 | Bulk track with empty array | 1. POST /bulk-track with suggestionIds=[] | 422 Validation Error |
| I-028 | Generate with invalid source | 1. POST with sources=['invalid'] | 422 Validation Error |

---

## Integration Test Code Structure

```php
<?php

namespace Tests\Feature\Integration;

use Tests\TestCase;
use App\Models\User;
use App\Models\Site;
use App\Models\KeywordSuggestion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

class KeywordDiscoveryIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Site $site;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->site = Site::factory()->create(['user_id' => $this->user->id]);
    }

    /** @test */
    public function complete_discovery_workflow(): void
    {
        $this->actingAs($this->user);

        // 1. Generate suggestions
        Http::fake([
            'suggestqueries.google.com/*' => Http::response([
                'seo tools',
                ['best seo tools', 'free seo tools', 'seo tools 2026']
            ]),
        ]);

        $response = $this->postJson("/api/v1/sites/{$this->site->id}/suggestions/generate", [
            'seedKeyword' => 'seo tools',
            'sources' => ['suggest', 'question'],
            'maxResults' => 50,
        ]);

        $response->assertStatus(202);

        // 2. List suggestions
        $response = $this->getJson("/api/v1/sites/{$this->site->id}/suggestions");
        $response->assertStatus(200)
            ->assertJsonCount(10, 'data'); // 3 suggest + 7 question

        // 3. Track a suggestion
        $suggestion = KeywordSuggestion::first();
        $response = $this->postJson(
            "/api/v1/sites/{$this->site->id}/suggestions/{$suggestion->id}/track"
        );
        $response->assertStatus(201);

        // 4. Verify keyword created
        $this->assertDatabaseHas('keywords', [
            'site_id' => $this->site->id,
            'keyword' => $suggestion->keyword,
        ]);

        // 5. Verify suggestion updated
        $this->assertTrue($suggestion->fresh()->is_tracking);

        // 6. Dismiss another suggestion
        $suggestion2 = KeywordSuggestion::where('is_tracking', false)->first();
        $response = $this->postJson(
            "/api/v1/sites/{$this->site->id}/suggestions/{$suggestion2->id}/dismiss"
        );
        $response->assertStatus(200);

        // 7. List again - should not include tracked or dismissed
        $response = $this->getJson("/api/v1/sites/{$this->site->id}/suggestions");
        $response->assertStatus(200)
            ->assertJsonMissing(['id' => $suggestion->id])
            ->assertJsonMissing(['id' => $suggestion2->id]);
    }
}
```

---

## Coverage Targets

| Component | Target | Notes |
|-----------|--------|-------|
| Generate → Store Flow | 100% | Complete generation workflow |
| List → Filter Flow | 100% | All filter combinations |
| Track → Keyword Creation | 100% | Including bulk operations |
| Dismiss → Update Flow | 100% | Including dismissed_suggestions |
| Authorization Checks | 100% | All access control scenarios |
| Error Handling | 100% | All validation and error cases |

---

## Test Execution

```bash
# Run integration tests
cd backend
php artisan test tests/Feature/Integration/KeywordDiscoveryIntegrationTest.php

# Run with coverage
php artisan test --coverage --min=80

# Run specific test
php artisan test --filter "complete_discovery_workflow"
```

---

## Dependencies

- PHPUnit
- Laravel RefreshDatabase trait
- Laravel HTTP fake for mocking external APIs
- Model factories (User, Site, KeywordSuggestion, Keyword)

---

## Notes for Developer

1. **Test Isolation**: Each test uses RefreshDatabase to ensure clean state

2. **External API Mocking**: Use `Http::fake()` to mock Google Suggest API responses

3. **Authentication**: Use `$this->actingAs($user)` for authenticated requests

4. **Database Assertions**: Use `$this->assertDatabaseHas()` and `$this->assertDatabaseMissing()`

5. **API Responses**: Test both success and error responses with appropriate status codes

6. **Idempotency**: Test that repeated operations (track, dismiss) are idempotent

7. **Performance**: For large datasets, test pagination works correctly

---

## Comments

### [2026-02-27 17:50] PreTester: Test Design Complete
Created integration test documentation for TASK-5-14.

**Test Files to Create:**
- `backend/tests/Feature/Integration/KeywordDiscoveryIntegrationTest.php`
- `backend/tests/Feature/Integration/SuggestionWorkflowTest.php`

**Total Scenarios:** 28 integration tests

Status: Tests Coverage In Progress → To Develop (waiting for implementation)

