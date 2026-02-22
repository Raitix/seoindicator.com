# TEST-12-1: GSC Connections Table Tests

## Task Reference
- Task: [TASK-12-1](../TASKS/TASK-12-1.md)
- Specification: [SPEC-012](../SPECIFICATIONS/SPEC-012.md)

## Test Coverage

### Test Files
- `backend/tests/Feature/Migrations/CreateGSCConnectionsTableTest.php` - 11 tests
- `backend/tests/Unit/Models/GSCConnectionTest.php` - 16 tests

### Tests Summary
- **Total Tests:** 27
- **Assertions:** 64
- **Status:** All Passing

---

## Migration Tests (11 tests)

| Test | Description | Status |
|------|-------------|--------|
| test_table_exists | Verify gsc_connections table exists | ✓ |
| test_required_columns_exist | All columns present (id, user_id, etc.) | ✓ |
| test_foreign_key_to_users | FK to users with cascade delete | ✓ |
| test_unique_constraint_user_property | Unique (user_id, property_url) | ✓ |
| test_different_users_same_property | Different users can share property | ✓ |
| test_indexes_exist | Indexes on user_id, status, token_expires_at | ✓ |
| test_tokens_are_encrypted | Tokens encrypted at rest | ✓ |
| test_status_enum_values | Valid values: active, expired, revoked | ✓ |
| test_last_synced_at_nullable | last_synced_at can be null | ✓ |
| test_default_status_is_active | Default status is 'active' | ✓ |
| test_token_expires_at_is_datetime | Cast to Carbon datetime | ✓ |

---

## Model Tests (16 tests)

| Test | Description | Status |
|------|-------------|--------|
| test_connection_belongs_to_user | User relationship | ✓ |
| test_is_token_expired_false | Not expired check | ✓ |
| test_is_token_expired_true | Expired check | ✓ |
| test_token_expires_within | Expires within minutes | ✓ |
| test_is_active | Active status helper | ✓ |
| test_mark_as_expired | Mark connection expired | ✓ |
| test_mark_as_revoked | Mark connection revoked | ✓ |
| test_mark_as_synced | Update last_synced_at | ✓ |
| test_update_tokens | Update OAuth tokens | ✓ |
| test_scope_active | Active connections query | ✓ |
| test_scope_needs_refresh | Tokens needing refresh | ✓ |
| test_scope_ready_for_sync | Ready for data sync | ✓ |
| test_tokens_hidden_in_array | Tokens not in JSON/array | ✓ |
| test_datetime_casting | Carbon casting for dates | ✓ |
| test_mass_assignment | Fillable attributes work | ✓ |
| test_factory_states | Factory states (expired, revoked, etc.) | ✓ |

---

## Security Features Tested

### Token Encryption
- Access token encrypted at rest
- Refresh token encrypted at rest
- Raw database values differ from model values
- Retrieved values decrypt correctly

### Access Control
- User foreign key cascade delete
- Unique property per user constraint

---

## Model Helpers

### Status Management
```php
$connection->isActive();       // Check if active
$connection->markAsExpired();  // Set status to expired
$connection->markAsRevoked();  // Set status to revoked
$connection->markAsSynced();   // Update last_synced_at
```

### Token Management
```php
$connection->isTokenExpired();           // Check if expired
$connection->tokenExpiresWithin(5);      // Expires within 5 minutes
$connection->updateTokens($access, $refresh, $expires);
```

### Query Scopes
```php
GSCConnection::active();              // Active connections
GSCConnection::needsRefresh(5);       // Need token refresh
GSCConnection::readyForSync();        // Ready for data sync
```

---

## Run Tests
```bash
cd backend
php artisan test --filter=GSCConnection
```

---

## Created
2026-02-21
