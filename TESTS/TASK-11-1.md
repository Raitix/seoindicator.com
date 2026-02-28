# TEST-11-1: Team Members Table Tests

## Overview
Comprehensive test coverage for the `team_members` table migration and TeamMember model.

## Test Files
- `backend/tests/Unit/Migrations/CreateTeamMembersTableTest.php` - Migration structure tests
- `backend/tests/Unit/Models/TeamMemberTest.php` - Model behavior tests

## Test Summary
- **Total Tests:** 61
- **Migration Tests:** 19
- **Model Tests:** 42
- **Status:** All Passing

---

## Migration Tests (TM-MT-001 to TM-MT-019)

### Table Structure Tests
| Test ID | Description | Status |
|---------|-------------|--------|
| TM-MT-001 | Table exists | ✅ PASS |
| TM-MT-002 | Required columns exist (11 columns) | ✅ PASS |
| TM-MT-003 | ID column is BIGINT UNSIGNED AUTO_INCREMENT | ✅ PASS |

### Foreign Key Constraint Tests
| Test ID | Description | Status |
|---------|-------------|--------|
| TM-MT-004 | account_id FK with CASCADE delete | ✅ PASS |
| TM-MT-005 | user_id FK with CASCADE delete | ✅ PASS |
| TM-MT-008 | invited_by FK with SET NULL delete | ✅ PASS |
| TM-MT-009 | invited_by SET NULL on delete | ✅ PASS |

### Column Type Tests
| Test ID | Description | Status |
|---------|-------------|--------|
| TM-MT-006 | role ENUM with valid values | ✅ PASS |
| TM-MT-007 | role DEFAULT 'viewer' | ✅ PASS |
| TM-MT-010 | invited_at nullable timestamp | ✅ PASS |
| TM-MT-011 | accepted_at nullable timestamp | ✅ PASS |
| TM-MT-012 | invitation_token nullable string | ✅ PASS |
| TM-MT-013 | invitation_token max 128 chars | ✅ PASS |
| TM-MT-014 | invitation_expires_at nullable timestamp | ✅ PASS |

### Constraint Tests
| Test ID | Description | Status |
|---------|-------------|--------|
| TM-MT-015 | UNIQUE constraint (account_id, user_id) | ✅ PASS |
| TM-MT-016 | Same user can be in different accounts | ✅ PASS |
| TM-MT-017 | Same account can have different users | ✅ PASS |
| TM-MT-018 | Timestamps auto-managed | ✅ PASS |
| TM-MT-019 | Migration rollback | ✅ PASS |

---

## Model Tests (TM-MD-001 to TM-MD-042)

### Model Configuration Tests
| Test ID | Description | Status |
|---------|-------------|--------|
| TM-MD-001 | Model exists | ✅ PASS |
| TM-MD-002 | Fillable attributes correct | ✅ PASS |
| TM-MD-003 | Cast types correct | ✅ PASS |
| TM-MD-004 | Factory creates model | ✅ PASS |

### Role Constants Tests
| Test ID | Description | Status |
|---------|-------------|--------|
| TM-MD-005 | Role constants defined | ✅ PASS |
| TM-MD-006 | getValidRoles returns all roles | ✅ PASS |
| TM-MD-007 | isValidRole returns true for valid | ✅ PASS |
| TM-MD-008 | isValidRole returns false for invalid | ✅ PASS |

### Token Generation Tests
| Test ID | Description | Status |
|---------|-------------|--------|
| TM-MD-009 | Token is 64 characters | ✅ PASS |
| TM-MD-010 | Tokens are unique | ✅ PASS |
| TM-MD-011 | Default expiry is 24 hours | ✅ PASS |

### Relationship Tests
| Test ID | Description | Status |
|---------|-------------|--------|
| TM-MD-012 | BelongsTo Account | ✅ PASS |
| TM-MD-013 | BelongsTo User | ✅ PASS |
| TM-MD-014 | BelongsTo Inviter | ✅ PASS |
| TM-MD-015 | Inviter nullable | ✅ PASS |

### Role Check Methods Tests
| Test ID | Description | Status |
|---------|-------------|--------|
| TM-MD-016 | isOwner() correct | ✅ PASS |
| TM-MD-017 | isAdmin() correct | ✅ PASS |
| TM-MD-018 | isEditor() correct | ✅ PASS |
| TM-MD-019 | isViewer() correct | ✅ PASS |
| TM-MD-020 | canManageTeam() correct | ✅ PASS |

### Invitation Status Tests
| Test ID | Description | Status |
|---------|-------------|--------|
| TM-MD-021 | isPending true when accepted_at null | ✅ PASS |
| TM-MD-022 | isPending false when accepted_at set | ✅ PASS |
| TM-MD-023 | isExpired true when past | ✅ PASS |
| TM-MD-024 | isExpired false when future | ✅ PASS |
| TM-MD-025 | isExpired false when null | ✅ PASS |

### Token Validation Tests
| Test ID | Description | Status |
|---------|-------------|--------|
| TM-MD-026 | isValidToken true for matching valid | ✅ PASS |
| TM-MD-027 | isValidToken false for non-matching | ✅ PASS |
| TM-MD-028 | isValidToken false when token null | ✅ PASS |
| TM-MD-029 | isValidToken false when expired | ✅ PASS |
| TM-MD-030 | isValidToken timing-safe comparison | ✅ PASS |

### Accept Invitation Tests
| Test ID | Description | Status |
|---------|-------------|--------|
| TM-MD-031 | accept() sets accepted_at, clears token | ✅ PASS |
| TM-MD-032 | accept() returns false if already accepted | ✅ PASS |
| TM-MD-033 | clearInvitation() clears token and expiry | ✅ PASS |

### Factory State Tests
| Test ID | Description | Status |
|---------|-------------|--------|
| TM-MD-034 | owner state creates correct role | ✅ PASS |
| TM-MD-035 | admin state creates correct role | ✅ PASS |
| TM-MD-036 | editor state creates correct role | ✅ PASS |
| TM-MD-037 | viewer state creates correct role | ✅ PASS |
| TM-MD-038 | pending state creates pending invitation | ✅ PASS |
| TM-MD-039 | expired state creates expired invitation | ✅ PASS |

### Edge Case Tests
| Test ID | Description | Status |
|---------|-------------|--------|
| TM-MD-040 | null invited_at handled | ✅ PASS |
| TM-MD-041 | Timestamps auto-managed | ✅ PASS |
| TM-MD-042 | updated_at changes on update | ✅ PASS |

---

## Coverage Summary

### Migration Coverage
- ✅ Table existence
- ✅ All 11 columns
- ✅ Primary key (id)
- ✅ 3 foreign keys with correct constraints
- ✅ ENUM column with values
- ✅ Unique constraint (account_id, user_id)
- ✅ Timestamps

### Model Coverage
- ✅ Fillable attributes
- ✅ Cast types
- ✅ Role constants and validation
- ✅ Token generation (64 chars, unique)
- ✅ Default expiry (24 hours)
- ✅ 3 relationships (account, user, inviter)
- ✅ 5 role check methods
- ✅ 2 invitation status methods
- ✅ Token validation with timing-safe comparison
- ✅ Accept and clear invitation methods
- ✅ 6 factory states
- ✅ Edge cases (nulls, timestamps)

---

## Running Tests
```bash
cd backend
php artisan test --filter="TeamMember"
```

## Test Date
2026-02-28 15:15 GMT+2
