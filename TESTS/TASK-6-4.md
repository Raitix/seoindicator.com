# TEST-6-4: API Key Management Endpoints

## Overview
Test documentation for API Key Management endpoints (TASK-6-4).

## Test Files
- `backend/tests/Feature/Api/V1/ApiKeyControllerTest.php` - Feature tests for API endpoints
- `backend/tests/Unit/Repositories/ApiKeyRepositoryTest.php` - Unit tests for repository

---

## Endpoints Under Test

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/v1/api-keys` | Create new API key |
| GET | `/api/v1/api-keys` | List user's API keys |
| GET | `/api/v1/api-keys/{id}` | Get single API key |
| DELETE | `/api/v1/api-keys/{id}` | Revoke API key |

---

## Test Scenarios

### POST /api/v1/api-keys (Create)

#### Success Cases (C-*)
| ID | Scenario | Expected |
|----|----------|----------|
| C-01 | Create with minimal data (name only) | 201, key returned once |
| C-02 | Create with all fields (name, permissions, rate_limit, expires_at) | 201, all fields stored |
| C-03 | Key format validation (sk_live_*) | Valid format |
| C-04 | Key shown only once | Key in response, not retrievable later |
| C-05 | Hash stored correctly | SHA-256 hash in database |
| C-06 | Prefix stored for identification | First 20 chars stored |
| C-07 | Default rate_limit applied | 60 if not specified |
| C-08 | Permissions stored as JSON | Valid JSON in database |

#### Validation Errors (CV-*)
| ID | Scenario | Expected |
|----|----------|----------|
| CV-01 | Missing name | 422, validation error |
| CV-02 | Empty name | 422, validation error |
| CV-03 | Name too long (>255 chars) | 422, validation error |
| CV-04 | Invalid permissions format | 422, validation error |
| CV-05 | Invalid permissions values | 422, validation error |
| CV-06 | Rate limit below minimum (<1) | 422, validation error |
| CV-07 | Rate limit above maximum (>10000) | 422, validation error |
| CV-08 | Invalid expires_at format | 422, validation error |
| CV-09 | expires_at in the past | 422, validation error |

#### Authorization (CA-*)
| ID | Scenario | Expected |
|----|----------|----------|
| CA-01 | Unauthenticated request | 401 Unauthorized |
| CA-02 | Authenticated user creates key | 201 Created |

#### Edge Cases (CE-*)
| ID | Scenario | Expected |
|----|----------|----------|
| CE-01 | Create key with same name (allowed) | 201, new key created |
| CE-02 | Maximum permissions array | 201, all stored |
| CE-03 | Unicode characters in name | 201, stored correctly |
| CE-04 | SQL injection in name | 201, sanitized |
| CE-05 | XSS attempt in name | 201, sanitized |

---

### GET /api/v1/api-keys (List)

#### Success Cases (L-*)
| ID | Scenario | Expected |
|----|----------|----------|
| L-01 | List all keys for user | 200, array of keys |
| L-02 | Empty list for new user | 200, empty array |
| L-03 | Key hash not returned | Hash field hidden |
| L-04 | Usage statistics included | last_used_at, usage_count |
| L-05 | Pagination works | meta with total, page, perPage |

#### Filtering (LF-*)
| ID | Scenario | Expected |
|----|----------|----------|
| LF-01 | Filter by status=active | Only active keys |
| LF-02 | Filter by status=revoked | Only revoked keys |
| LF-03 | Filter by expired | Only expired keys |

#### Authorization (LA-*)
| ID | Scenario | Expected |
|----|----------|----------|
| LA-01 | Unauthenticated request | 401 Unauthorized |
| LA-02 | User sees only their keys | No cross-user access |

---

### GET /api/v1/api-keys/{id} (Show)

#### Success Cases (S-*)
| ID | Scenario | Expected |
|----|----------|----------|
| S-01 | Get existing key | 200, key details |
| S-02 | Hash not returned | Hash field hidden |
| S-03 | Full key not returned | Only prefix shown |

#### Error Cases (SE-*)
| ID | Scenario | Expected |
|----|----------|----------|
| SE-01 | Non-existent key | 404 Not Found |
| SE-02 | Revoked key | 200, but shows revoked status |
| SE-03 | Expired key | 200, but shows expired status |

#### Authorization (SA-*)
| ID | Scenario | Expected |
|----|----------|----------|
| SA-01 | Unauthenticated request | 401 Unauthorized |
| SA-02 | Access own key | 200 OK |
| SA-03 | Access another user's key | 403 Forbidden |

---

### DELETE /api/v1/api-keys/{id} (Revoke)

#### Success Cases (D-*)
| ID | Scenario | Expected |
|----|----------|----------|
| D-01 | Revoke active key | 200, revoked_at set |
| D-02 | Revoke already revoked key | 200, idempotent |
| D-03 | Key cannot be used after revoke | Validation fails |

#### Error Cases (DE-*)
| ID | Scenario | Expected |
|----|----------|----------|
| DE-01 | Non-existent key | 404 Not Found |

#### Authorization (DA-*)
| ID | Scenario | Expected |
|----|----------|----------|
| DA-01 | Unauthenticated request | 401 Unauthorized |
| DA-02 | Revoke own key | 200 OK |
| DA-03 | Revoke another user's key | 403 Forbidden |

---

## Adversarial Testing

### Security Tests (SEC-*)
| ID | Scenario | Expected |
|----|----------|----------|
| SEC-01 | Timing attack on key validation | Consistent response time |
| SEC-02 | Key enumeration prevention | No information leakage |
| SEC-03 | IDOR vulnerability check | Cannot access other users' keys |
| SEC-04 | Mass assignment protection | Only allowed fields accepted |
| SEC-05 | Rate limit bypass attempt | Still rate limited |

### Input Validation (IV-*)
| ID | Scenario | Expected |
|----|----------|----------|
| IV-01 | Null values in required fields | 422 validation error |
| IV-02 | Array where string expected | 422 validation error |
| IV-03 | Very long input strings | Truncated or rejected |
| IV-04 | Special characters in all fields | Handled safely |
| IV-05 | Unicode and emoji in name | Stored correctly |

---

## Test Data Requirements

### API Key Factory
```php
// Active key
ApiKey::factory()->create([
    'user_id' => $user->id,
    'name' => 'Test Key',
    'key_hash' => hash('sha256', 'sk_live_test123'),
    'key_prefix' => 'sk_live_test123456',
    'permissions' => ['keywords:read', 'keywords:write'],
    'rate_limit' => 60,
]);

// Revoked key
ApiKey::factory()->revoked()->create([...]);

// Expired key
ApiKey::factory()->expired()->create([...]);
```

### Valid Permissions
- `keywords:read`
- `keywords:write`
- `rankings:read`
- `reports:read`
- `reports:write`
- `webhooks:read`
- `webhooks:write`

---

## Expected Response Formats

### Create Response (201)
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "My API Key",
    "key": "sk_live_abc123...",
    "prefix": "sk_live_abc12345678",
    "permissions": ["keywords:read"],
    "rate_limit": 60,
    "expires_at": null,
    "created_at": "2026-02-27T12:00:00Z"
  }
}
```

### List Response (200)
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "My API Key",
      "prefix": "sk_live_abc12345678",
      "permissions": ["keywords:read"],
      "rate_limit": 60,
      "last_used_at": "2026-02-27T11:00:00Z",
      "expires_at": null,
      "revoked_at": null,
      "created_at": "2026-02-27T12:00:00Z"
    }
  ],
  "meta": {
    "total": 1,
    "page": 1,
    "perPage": 20
  }
}
```

### Error Response (422)
```json
{
  "message": "The name field is required.",
  "errors": {
    "name": ["The name field is required."]
  }
}
```

---

## Dependencies
- TASK-6-2: API Key Model and Repository
- TASK-6-3: API Key Generation and Validation
- User authentication system

---

## Status
- Created: 2026-02-27
- Agent: PreTester
