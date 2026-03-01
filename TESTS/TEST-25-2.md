# TEST-25-2: Social Accounts Migration Tests

## Overview
Test suite for TASK-25-2: Create social_accounts migration for OAuth provider connections.

---

## Test Categories

### 1. Schema Structure Tests (SC)
Tests for table and column definitions.

| Test ID | Description | Expected Result |
|---------|-------------|-----------------|
| SC-001 | Migration creates social_accounts table | Table exists after migration |
| SC-002 | Primary key is BIGINT UNSIGNED AUTO_INCREMENT | Auto-incrementing id column |
| SC-003 | user_id column is BIGINT UNSIGNED NOT NULL | Column exists, NOT NULL constraint |
| SC-004 | provider column is VARCHAR(50) NOT NULL | Column exists, max 50 chars |
| SC-005 | provider_id column is VARCHAR(255) NOT NULL | Column exists, max 255 chars |
| SC-006 | provider_token column is TEXT NULL | Column exists, nullable |
| SC-007 | provider_refresh_token column is TEXT NULL | Column exists, nullable |
| SC-008 | provider_token_expires_at column is TIMESTAMP NULL | Column exists, nullable |
| SC-009 | Timestamps (created_at, updated_at) | Both columns exist |

### 2. Foreign Key Tests (FK)
Tests for foreign key constraints.

| Test ID | Description | Expected Result |
|---------|-------------|-----------------|
| FK-001 | Foreign key to users.id | Valid user_id succeeds |
| FK-002 | Invalid user_id rejected | Insert with non-existent user fails |
| FK-003 | Cascade delete on user delete | Social accounts deleted with user |
| FK-004 | Foreign key index exists | Index on user_id column |

### 3. Unique Constraint Tests (UC)
Tests for unique constraints.

| Test ID | Description | Expected Result |
|---------|-------------|-----------------|
| UC-001 | Unique constraint on (provider, provider_id) | Duplicate provider+provider_id fails |
| UC-002 | Same provider_id different provider | Allowed (different providers) |
| UC-003 | Same provider different provider_id | Allowed (different external users) |

### 4. Index Tests (IX)
Tests for database indexes.

| Test ID | Description | Expected Result |
|---------|-------------|-----------------|
| IX-001 | Unique index on (provider, provider_id) | Index exists |
| IX-002 | Composite index on (user_id, provider) | Index exists for lookups |

### 5. Migration Rollback Tests (RB)
Tests for migration rollback functionality.

| Test ID | Description | Expected Result |
|---------|-------------|-----------------|
| RB-001 | Migration down drops table | Table removed on rollback |
| RB-002 | Migration re-run after rollback | Table recreated successfully |

### 6. Data Validation Tests (DV)
Tests for data integrity.

| Test ID | Description | Expected Result |
|---------|-------------|-----------------|
| DV-001 | provider max length (50 chars) | 50 chars accepted |
| DV-002 | provider exceeds max length | Truncated or error (DB dependent) |
| DV-003 | provider_id max length (255 chars) | 255 chars accepted |
| DV-004 | provider_id exceeds max length | Truncated or error |
| DV-005 | Valid provider values | 'google', 'github', etc. accepted |
| DV-006 | Empty provider rejected | NOT NULL constraint |

### 7. Model Tests (MD)
Tests for SocialAccount model.

| Test ID | Description | Expected Result |
|---------|-------------|-----------------|
| MD-001 | Create model with all fields | Model persisted to database |
| MD-002 | Create model with minimal fields | Only required fields |
| MD-003 | provider_token encrypted cast | Value encrypted at rest |
| MD-004 | provider_refresh_token encrypted cast | Value encrypted at rest |
| MD-005 | BelongsTo User relationship | Relationship returns User model |
| MD-006 | User hasMany socialAccounts | Relationship returns collection |
| MD-007 | Fillable attributes defined | Mass assignment works |

### 8. Edge Case Tests (EC)
Tests for edge cases and special scenarios.

| Test ID | Description | Expected Result |
|---------|-------------|-----------------|
| EC-001 | Null provider_token | Allowed, stores NULL |
| EC-002 | Null provider_refresh_token | Allowed, stores NULL |
| EC-003 | Null provider_token_expires_at | Allowed, stores NULL |
| EC-004 | Multiple providers for same user | Allowed (user has multiple OAuth) |
| EC-005 | Unicode in provider_token | Handled correctly with encryption |
| EC-006 | Long provider_token (large OAuth token) | TEXT column handles large values |
| EC-007 | Token expiration in past | Allowed (no future constraint) |
| EC-008 | Token expiration in future | Allowed |

### 9. Security Tests (SEC)
Tests for security-related functionality.

| Test ID | Description | Expected Result |
|---------|-------------|-----------------|
| SEC-001 | SQL injection in provider | Prevented via parameterized queries |
| SEC-002 | SQL injection in provider_id | Prevented via parameterized queries |
| SEC-003 | Token encryption at rest | provider_token encrypted in DB |
| SEC-004 | Refresh token encryption at rest | provider_refresh_token encrypted |
| SEC-005 | Unique constraint prevents account hijacking | Same provider+id can't have two users |

### 10. OAuth Flow Simulation Tests (OF)
Tests simulating OAuth scenarios.

| Test ID | Description | Expected Result |
|---------|-------------|-----------------|
| OF-001 | Google OAuth account creation | Creates social_account record |
| OF-002 | GitHub OAuth account creation | Creates social_account record |
| OF-003 | Same user multiple providers | Multiple records, one per provider |
| OF-004 | Reconnecting same provider | Updates existing record or fails unique |

---

## Test File Locations

| Type | File Path |
|------|-----------|
| Migration Tests | `backend/tests/Feature/Migrations/CreateSocialAccountsTableTest.php` |
| Model Tests | `backend/tests/Unit/Models/SocialAccountTest.php` |
| Factory | `backend/database/factories/SocialAccountFactory.php` |

---

## Total Tests
- Migration Tests: 25
- Model Tests: 20
- **Total: 45 tests**

---

## Key Test Scenarios

### Scenario 1: New OAuth Connection
```
Given: User exists in database
When: User connects Google OAuth
Then: Social account record created with encrypted tokens
```

### Scenario 2: Multiple Providers
```
Given: User has Google OAuth connected
When: User connects GitHub OAuth
Then: Two social_account records exist (one per provider)
```

### Scenario 3: User Deletion
```
Given: User has connected OAuth providers
When: User is deleted
Then: All social_account records cascade deleted
```

### Scenario 4: Duplicate Provider Connection
```
Given: User has Google OAuth connected
When: Same Google account tries to connect again
Then: Unique constraint violation (provider + provider_id)
```

### Scenario 5: Token Refresh
```
Given: Social account with expired token
When: OAuth refreshes token
Then: provider_token and provider_token_expires_at updated
```

---

## Dependencies
- Users table (existing)
- Laravel encrypted cast functionality
- App\Models\User model

---

*Created by PreTester for TASK-25-2*
