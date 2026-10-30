# TESTS/TASK-6-20: Integration Tests

## Overview
Integration tests for API workflows as specified in TASK-6-20.

## Test Files

### 1. ApiAuthWorkflowTest.php
**Location:** `tests/Integration/Api/ApiAuthWorkflowTest.php`

Tests the complete API key authentication lifecycle:
- Create API key
- Use key for authentication
- Revoke key
- Verify revoked key rejected

**Test Categories:**
| Category | Tests | Description |
|----------|-------|-------------|
| Workflow (WF) | 6 | Full lifecycle tests |
| Permission (PW) | 3 | Permission-based access tests |
| Auth Header (AH) | 6 | Authentication header format tests |
| User Isolation (UI) | 2 | Cross-user isolation tests |
| Edge Cases (EC) | 5 | Edge case handling tests |
| Security (SEC) | 3 | Security vulnerability tests |

### 2. ApiKeywordsWorkflowTest.php
**Location:** `tests/Integration/Api/ApiKeywordsWorkflowTest.php`

Tests the complete keywords API lifecycle:
- Create keyword via API
- List keywords
- Update keyword
- Delete keyword
- Verify ranking history

**Test Categories:**
| Category | Tests | Description |
|----------|-------|-------------|
| Workflow (WF) | 4 | Full lifecycle tests |
| Create (CW) | 4 | Creation tests |
| List (LW) | 4 | Listing/pagination tests |
| Update (UW) | 4 | Update operation tests |
| Delete (DW) | 4 | Deletion tests |
| Ranking History (RH) | 3 | Ranking tracking tests |
| Edge Cases (EC) | 4 | Edge case handling tests |
| Security (SEC) | 2 | Security tests |

### 3. ApiRankingsWorkflowTest.php
**Location:** `tests/Integration/Api/ApiRankingsWorkflowTest.php`

Tests the rankings API:
- Fetch rankings for keyword
- Rankings history
- Position changes
- SERP features

**Test Categories:**
| Category | Tests | Description |
|----------|-------|-------------|
| Workflow (WF) | 4 | Full workflow tests |
| Rankings List (RL) | 5 | Rankings listing tests |
| Project Rankings (PR) | 2 | Project-level ranking tests |
| SERP Features (SF) | 2 | SERP feature tests |
| Authorization (AZ) | 2 | Access control tests |
| Edge Cases (EC) | 4 | Edge case tests |
| Performance (PF) | 2 | Large dataset tests |

### 4. ApiWebhooksWorkflowTest.php
**Location:** `tests/Integration/Api/ApiWebhooksWorkflowTest.php`

Tests the complete webhooks API lifecycle:
- Create webhook
- Trigger event
- Verify webhook received
- Test webhook endpoint
- Delete webhook

**Test Categories:**
| Category | Tests | Description |
|----------|-------|-------------|
| Workflow (WF) | 4 | Full lifecycle tests |
| Create (CW) | 7 | Webhook creation tests |
| Test Endpoint (TE) | 5 | Webhook testing tests |
| Delivery (DL) | 2 | Delivery tracking tests |
| Update (UW) | 5 | Update operation tests |
| Delete (DW) | 3 | Deletion tests |
| Authorization (AZ) | 2 | Access control tests |
| Security (SEC) | 4 | Security tests |
| Edge Cases (EC) | 4 | Edge case tests |

### 5. RateLimitingWorkflowTest.php
**Location:** `tests/Integration/Api/RateLimitingWorkflowTest.php`

Tests the rate limiting functionality:
- Hit rate limit
- Wait for reset
- Continue requests

**Test Categories:**
| Category | Tests | Description |
|----------|-------|-------------|
| Workflow (WF) | 4 | Full workflow tests |
| Rate Limit Hit (RH) | 3 | Limit exceeded tests |
| Rate Limit Reset (RR) | 2 | Reset behavior tests |
| Different Limits (DL) | 4 | Various limit configs |
| Concurrent (CR) | 1 | Concurrent request tests |
| Edge Cases (EC) | 4 | Edge case tests |
| User Isolation (UI) | 1 | Cross-user isolation |

## Test Environment Setup

### Prerequisites
- PHP 8.1+
- Laravel 10+
- PHPUnit 10+
- In-memory SQLite database for tests

### Configuration
Tests use `RefreshDatabase` trait for clean state between tests.

```php
use Illuminate\Foundation\Testing\RefreshDatabase;

class TestClassName extends TestCase
{
    use RefreshDatabase;
}
```

### Running Tests

```bash
# Run all integration tests
php artisan test --filter=Integration\\Api

# Run specific test file
php artisan test tests/Integration/Api/ApiAuthWorkflowTest.php

# Run with coverage
php artisan test --coverage-html=coverage
```

## Test Patterns Used

### 1. Lifecycle Tests
Complete workflow from creation to deletion:
```php
public function test_full_keyword_lifecycle(): void
{
    // Create
    $createResponse = $this->postJson(...);
    
    // List/Read
    $listResponse = $this->getJson(...);
    
    // Update
    $updateResponse = $this->putJson(...);
    
    // Delete
    $deleteResponse = $this->deleteJson(...);
}
```

### 2. Authorization Tests
Verify proper access control:
```php
public function test_cannot_access_another_users_resource(): void
{
    $otherUser = User::factory()->create();
    
    $response = $this->actingAs($this->user)
        ->getJson("/api/v1/resource/{$otherUserResource->id}");
    
    $response->assertStatus(403);
}
```

### 3. Edge Case Tests
Handle unusual inputs gracefully:
```php
public function test_unicode_in_name(): void
{
    $response = $this->postJson('/api/v1/keywords', [
        'keyword' => '検索エンジン最適化 🔍',
    ]);
    
    $response->assertStatus(201);
}
```

### 4. Security Tests
Prevent common vulnerabilities:
```php
public function test_sql_injection_protection(): void
{
    $response = $this->postJson('/api/v1/keywords', [
        'keyword' => "'; DROP TABLE keywords; --",
    ]);
    
    $response->assertStatus(201); // Accepted, not executed
    $this->assertDatabaseHas('keywords', [...]);
}
```

## Coverage Goals

| Metric | Target |
|--------|--------|
| Line Coverage | 80%+ |
| Branch Coverage | 70%+ |
| Method Coverage | 90%+ |

## Notes

- All tests use factory methods for test data creation
- HTTP mocking used for external webhook calls
- Rate limiting tests may require cache configuration
- Some tests may need adjustment based on actual API implementation
