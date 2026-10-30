# TEST-6-8: Keywords API Endpoints

## Overview
Test documentation for Keywords API endpoints (TASK-6-8) - REST API for external integrations.

## Test Files
- `backend/tests/Feature/Api/V1/KeywordsApiControllerTest.php` - Feature tests for REST API endpoints

---

## Endpoints Under Test

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/keywords` | List keywords |
| POST | `/api/v1/keywords` | Create keyword |
| GET | `/api/v1/keywords/{id}` | Get single keyword |
| PUT | `/api/v1/keywords/{id}` | Update keyword |
| DELETE | `/api/v1/keywords/{id}` | Delete keyword |

---

## Authentication
All endpoints require Bearer token authentication via API Key:
```
Authorization: Bearer sk_live_xxxx
```

---

## Rate Limiting
- Rate limit headers on all responses
- 429 response when limit exceeded
- Configurable per API key (default: 60 req/min)

---

## Test Scenarios

### GET /api/v1/keywords (List)

#### Success Cases (L-*)
| ID | Scenario | Expected |
|----|----------|----------|
| L-01 | List all keywords for user | 200, array of keywords |
| L-02 | Filter by site_id | 200, only keywords for site |
| L-03 | Filter by status=active | 200, only active keywords |
| L-04 | Filter by status=pauseed | 200, only paused keywords |
| L-05 | Filter by status=all | 200, all keywords (non-deleted) |
| L-06 | Pagination default | 20 items per page |
| L-07 | Pagination custom per_page | Correct number of items |
| L-08 | Pagination max per_page | Max 100 items |
| L-09 | Pagination meta | total, page, per_page, total_pages |
| L-10 | Empty list | 200, empty array |
| L-11 | Include ranking data | currentPosition, previousPosition |
| L-12 | Position change calculation | Correct delta |

#### Query Parameters (LP-*)
| ID | Parameter | Valid Values | Invalid Handling |
|----|-----------|--------------|-----------------|
| LP-01 | site_id | Integer, exists | Ignored if invalid |
| LP-02 | status | active, paused, all | Default to active |
| LP-03 | page | Positive integer | Default to 1 |
| LP-04 | per_page | 1-100 | Clamp to valid range |

#### Authentication (LA-*)
| ID | Scenario | Expected |
|----|----------|----------|
| LA-01 | Valid API key | 200 OK |
| LA-02 | Missing Authorization header | 401 Unauthorized |
| LA-03 | Invalid API key | 401 Unauthorized |
| LA-04 | Revoked API key | 401 Unauthorized |
| LA-05 | Expired API key | 401 Unauthorized |
| LA-06 | Wrong permissions (no keywords:read) | 403 Forbidden |

#### Rate Limiting (LR-*)
| ID | Scenario | Expected |
|----|----------|----------|
| LR-01 | Within rate limit | 200 OK, headers present |
| LR-02 | Rate limit exceeded | 429 Too Many Requests |
| LR-03 | Rate limit headers | X-RateLimit-Limit, X-RateLimit-Remaining, X-RateLimit-Reset |
| LR-04 | Retry-After header on 429 | Correct seconds |

#### Authorization (LZ-*)
| ID | Scenario | Expected |
|----|----------|----------|
| LZ-01 | User can only see their keywords | No cross-user access |
| LZ-02 | Keywords:read permission required | 403 if missing |
| LZ-03 | site_id filter respects ownership | Only user's sites |

---

### POST /api/v1/keywords (Create)

#### Success Cases (C-*)
| ID | Scenario | Expected |
|----|----------|----------|
| C-01 | Create with minimal data | 201 Created |
| C-02 | Create with all fields | 201, all fields stored |
| C-03 | Default values applied | search_engine=google, location=us, device=desktop |
| C-04 | Status defaults to active | status=active |
| C-05 | Response includes full keyword | All fields returned |
| C-06 | Keyword sanitized | strip_tags applied |
| C-07 | Webhook event emitted | keyword.added event |

#### Validation Errors (CV-*)
| ID | Scenario | Expected |
|----|----------|----------|
| CV-01 | Missing site_id | 422, validation error |
| CV-02 | Missing keyword | 422, validation error |
| CV-03 | Empty keyword | 422, validation error |
| CV-04 | Keyword too long (>500 chars) | 422, validation error |
| CV-05 | Invalid site_id (non-existent) | 422, site not found |
| CV-06 | Invalid search_engine | 422, validation error |
| CV-07 | Invalid location | 422, validation error |
| CV-08 | Invalid device | 422, validation error |
| CV-09 | Invalid target_url | 422, validation error |
| CV-10 | Duplicate keyword (same site/engine/location/device) | 409 Conflict |

#### Business Rules (CB-*)
| ID | Scenario | Expected |
|----|----------|----------|
| CB-01 | Keyword limit exceeded | 400, KEYWORD_LIMIT_EXCEEDED |
| CB-02 | Site ownership check | 403 if not user's site |

#### Authentication (CA-*)
| ID | Scenario | Expected |
|----|----------|----------|
| CA-01 | Valid API key with keywords:write | 201 Created |
| CA-02 | API key with only keywords:read | 403 Forbidden |
| CA-03 | Missing Authorization | 401 Unauthorized |
| CA-04 | Revoked API key | 401 Unauthorized |

---

### GET /api/v1/keywords/{id} (Show)

#### Success Cases (S-*)
| ID | Scenario | Expected |
|----|----------|----------|
| S-01 | Get existing keyword | 200, keyword data |
| S-02 | Include current position | From latest ranking |
| S-03 | Include previous position | From second-latest ranking |
| S-04 | Include position change | Calculated delta |
| S-05 | Include last_checked_at | From latest ranking |
| S-06 | All fields returned | id, keyword, target_url, search_engine, etc. |

#### Error Cases (SE-*)
| ID | Scenario | Expected |
|----|----------|----------|
| SE-01 | Non-existent keyword | 404 Not Found |
| SE-02 | Deleted keyword (status=deleted) | 404 Not Found |

#### Authentication (SA-*)
| ID | Scenario | Expected |
|----|----------|----------|
| SA-01 | Valid API key | 200 OK |
| SA-02 | Missing Authorization | 401 Unauthorized |
| SA-03 | Invalid API key | 401 Unauthorized |
| SA-04 | No keywords:read permission | 403 Forbidden |

#### Authorization (SZ-*)
| ID | Scenario | Expected |
|----|----------|----------|
| SZ-01 | User's own keyword | 200 OK |
| SZ-02 | Another user's keyword | 403 Forbidden |

---

### PUT /api/v1/keywords/{id} (Update)

#### Success Cases (U-*)
| ID | Scenario | Expected |
|----|----------|----------|
| U-01 | Update target_url | 200, updated |
| U-02 | Update status to paused | 200, status=paused |
| U-03 | Update status to active | 200, status=active |
| U-04 | Update multiple fields | 200, all updated |
| U-05 | Response includes updated data | Full keyword returned |

#### Validation Errors (UV-*)
| ID | Scenario | Expected |
|----|----------|----------|
| UV-01 | Invalid target_url | 422, validation error |
| UV-02 | Invalid status | 422, validation error |
| UV-03 | No fields provided | 400, no data |
| UV-04 | Empty request body | 400, no data |

#### Error Cases (UE-*)
| ID | Scenario | Expected |
|----|----------|----------|
| UE-01 | Non-existent keyword | 404 Not Found |
| UE-02 | Deleted keyword | 404 Not Found |

#### Authentication (UA-*)
| ID | Scenario | Expected |
|----|----------|----------|
| UA-01 | Valid API key with keywords:write | 200 OK |
| UA-02 | API key with only keywords:read | 403 Forbidden |
| UA-03 | Missing Authorization | 401 Unauthorized |

#### Authorization (UZ-*)
| ID | Scenario | Expected |
|----|----------|----------|
| UZ-01 | User's own keyword | 200 OK |
| UZ-02 | Another user's keyword | 403 Forbidden |

---

### DELETE /api/v1/keywords/{id} (Delete)

#### Success Cases (D-*)
| ID | Scenario | Expected |
|----|----------|----------|
| D-01 | Delete keyword | 204 No Content |
| D-02 | Soft delete (status=deleted) | Status set to deleted |
| D-03 | keepHistory=true | Rankings preserved |
| D-04 | keepHistory=false | Rankings deleted |
| D-05 | Webhook event emitted | keyword.removed event |

#### Error Cases (DE-*)
| ID | Scenario | Expected |
|----|----------|----------|
| DE-01 | Non-existent keyword | 404 Not Found |
| DE-02 | Already deleted keyword | 404 Not Found |

#### Authentication (DA-*)
| ID | Scenario | Expected |
|----|----------|----------|
| DA-01 | Valid API key with keywords:write | 204 No Content |
| DA-02 | API key with only keywords:read | 403 Forbidden |
| DA-03 | Missing Authorization | 401 Unauthorized |

#### Authorization (DZ-*)
| ID | Scenario | Expected |
|----|----------|----------|
| DZ-01 | User's own keyword | 204 No Content |
| DZ-02 | Another user's keyword | 403 Forbidden |

---

## Adversarial Testing

### Security Tests (SEC-*)
| ID | Scenario | Expected |
|----|----------|----------|
| SEC-01 | SQL injection in keyword | Sanitized, stored safely |
| SEC-02 | XSS in keyword | Sanitized on output |
| SEC-03 | IDOR via keyword ID | 403 Forbidden for other users |
| SEC-04 | Mass assignment protection | Only allowed fields accepted |
| SEC-05 | Path traversal in target_url | Rejected or sanitized |
| SEC-06 | CRLF injection in fields | Stripped or rejected |
| SEC-07 | Unicode normalization attacks | Handled correctly |
| SEC-08 | Large payload attack | Rejected or truncated |

### Input Validation (IV-*)
| ID | Scenario | Expected |
|----|----------|----------|
| IV-01 | Null values in required fields | 422 validation error |
| IV-02 | Array where string expected | 422 validation error |
| IV-03 | Very long keyword (>500 chars) | 422 validation error |
| IV-04 | Special characters in keyword | Stored correctly |
| IV-05 | Unicode in keyword | Stored correctly |
| IV-06 | Emoji in keyword | Stored correctly |
| IV-07 | Negative site_id | Ignored or rejected |
| IV-08 | Float instead of integer | Converted or rejected |
| IV-09 | Boolean where string expected | Converted or rejected |

### Rate Limiting Edge Cases (RL-*)
| ID | Scenario | Expected |
|----|----------|----------|
| RL-01 | Exact limit reached | 200 OK, remaining=0 |
| RL-02 | Limit exceeded by 1 | 429 Too Many Requests |
| RL-03 | Concurrent requests | All properly counted |
| RL-04 | Rate limit per API key | Different keys have separate limits |
| RL-05 | Rate limit reset | Counter resets after window |

---

## Test Data Requirements

### API Key Factory States
```php
// Active key with full permissions
ApiKey::factory()->create([
    'user_id' => $user->id,
    'permissions' => ['keywords:read', 'keywords:write'],
    'rate_limit' => 60,
]);

// Read-only key
ApiKey::factory()->create([
    'user_id' => $user->id,
    'permissions' => ['keywords:read'],
]);

// Revoked key
ApiKey::factory()->revoked()->create([...]);

// Expired key
ApiKey::factory()->expired()->create([...]);
```

### Keyword Factory States
```php
// Active keyword
Keyword::factory()->create([
    'site_id' => $site->id,
    'keyword' => 'seo tools',
    'status' => 'active',
]);

// Paused keyword
Keyword::factory()->create([
    'status' => 'paused',
]);
```

### Site Factory
```php
Site::factory()->create([
    'user_id' => $user->id,
    'url' => 'https://example.com',
]);
```

---

## Expected Response Formats

### List Response (200)
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "keyword": "seo tools",
      "site_id": 1,
      "target_url": "https://example.com/seo-tools",
      "search_engine": "google",
      "location": "us",
      "device": "desktop",
      "status": "active",
      "current_position": 5,
      "previous_position": 7,
      "last_checked_at": "2026-02-27T12:00:00Z"
    }
  ],
  "meta": {
    "total": 50,
    "page": 1,
    "per_page": 20,
    "total_pages": 3
  }
}
```

### Create Response (201)
```json
{
  "success": true,
  "data": {
    "id": 1,
    "keyword": "seo tools",
    "site_id": 1,
    "target_url": "https://example.com/seo-tools",
    "search_engine": "google",
    "location": "us",
    "device": "desktop",
    "status": "active"
  }
}
```

### Error Response (422)
```json
{
  "success": false,
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "Invalid input",
    "details": [
      { "field": "keyword", "message": "The keyword field is required." }
    ]
  }
}
```

### Rate Limit Exceeded (429)
```json
{
  "success": false,
  "error": {
    "code": "RATE_LIMIT_EXCEEDED",
    "message": "Rate limit exceeded. Retry after 30 seconds.",
    "retry_after": 30
  }
}
```

### Not Found (404)
```json
{
  "success": false,
  "error": {
    "code": "NOT_FOUND",
    "message": "Keyword not found"
  }
}
```

---

## Dependencies
- TASK-6-4: API Key Management (for authentication)
- TASK-6-5: Rate Limiter Middleware
- Existing Keyword model and repository
- Existing Site model

---

## Implementation Notes for Developer

### Routes to Add (routes/api.php)
```php
Route::prefix('v1')->middleware(['api-key.auth', 'api-key.ratelimit'])->group(function () {
    Route::get('keywords', [KeywordsApiController::class, 'index']);
    Route::post('keywords', [KeywordsApiController::class, 'store']);
    Route::get('keywords/{id}', [KeywordsApiController::class, 'show']);
    Route::put('keywords/{id}', [KeywordsApiController::class, 'update']);
    Route::delete('keywords/{id}', [KeywordsApiController::class, 'destroy']);
});
```

### Controller to Create
`app/Http/Controllers/Api/V1/KeywordsApiController.php`

Key differences from existing KeywordController:
1. Uses API key authentication, not session
2. URL is `/api/v1/keywords` (not site-scoped)
3. site_id is a query parameter (for list) or body field (for create)
4. Must check permissions: `keywords:read` for GET, `keywords:write` for POST/PUT/DELETE
5. Must enforce rate limiting per API key
6. Must only return keywords belonging to the API key's user

### Middleware Required
1. `api-key.auth` - Validates Bearer token, attaches API key to request
2. `api-key.ratelimit` - Enforces per-key rate limits, adds headers

---

## Status
- Created: 2026-02-27
- Agent: PreTester
- Tests: 100+ scenarios covering all endpoints
