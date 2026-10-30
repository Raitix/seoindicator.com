# TEST-36-1: Content Decay Database Migration Tests

## Metadata
| Field | Value |
|-------|-------|
| **Task** | TASK-36-1 |
| **Feature** | FEAT-036: Content Decay Detection & Refresh Planner |
| **Created** | 2026-03-10 |
| **Agent** | PreTester |
| **Status** | Ready for Implementation |

## Overview
Comprehensive test suite for Content Decay database migrations. Tests verify schema structure, indexes, foreign keys, unique constraints, column types, nullable states, default values, and rollback functionality for all 4 tables required by SPEC-036.

## Test Files
| File | Location | Tests | Purpose |
|------|----------|-------|---------|
| `ContentDecayMigrationTest.php` | `tests/Feature/Database/Migrations/` | 106 | Schema structure, indexes, foreign keys, rollback |
| `ContentDecayMigrationStructureTest.php` | `tests/Unit/Database/Migrations/` | 92 | Column types, nullable, defaults, precision |
| **Total** | - | **198** | - |

## Test Coverage Summary

### content_decay_projects Table (CDP)
| Category | Tests | Description |
|----------|-------|-------------|
| Table Existence | 1 | Table created after migration |
| Column Existence | 13 | All required columns present |
| Indexes | 1 | Unique site_id index |
| Foreign Keys | 2 | user_id → users, site_id → sites |
| Column Types | 8 | Boolean, integer, decimal, json, string types |
| Nullable States | 2 | Required vs optional columns |
| Defaults | 8 | Detection settings defaults |
| **Subtotal** | **35** | - |

### content_decay_candidates Table (CDC)
| Category | Tests | Description |
|----------|-------|-------------|
| Table Existence | 1 | Table created after migration |
| Column Existence | 24 | All required columns present |
| Indexes | 4 | Unique composite, status, score, severity |
| Foreign Keys | 1 | project_id → content_decay_projects |
| Column Types | 6 | Decimal, string, json, enum types |
| Nullable States | 2 | Required vs optional columns |
| Defaults | 7 | Severity, status, confidence defaults |
| Precision | 4 | Decimal precision for score/confidence |
| **Subtotal** | **49** | - |

### content_decay_signals Table (CDS)
| Category | Tests | Description |
|----------|-------|-------------|
| Table Existence | 1 | Table created after migration |
| Column Existence | 23 | All required columns present |
| Indexes | 2 | Candidate+created, unique window composite |
| Foreign Keys | 1 | candidate_id → content_decay_candidates |
| Column Types | 6 | Decimal, date, integer, json types |
| Nullable States | 2 | Required vs optional columns |
| Defaults | 4 | Clicks, impressions, CTR defaults |
| Precision | 2 | CTR and position decimal precision |
| **Subtotal** | **41** | - |

### content_refresh_tasks Table (CRT)
| Category | Tests | Description |
|----------|-------|-------------|
| Table Existence | 1 | Table created after migration |
| Column Existence | 12 | All required columns present |
| Indexes | 2 | Candidate+status, owner+due_date |
| Foreign Keys | 2 | candidate_id → candidates, owner_user_id → users |
| Column Types | 6 | String, text, date, enum types |
| Nullable States | 2 | Required vs optional columns |
| Defaults | 2 | Status, source defaults |
| **Subtotal** | **28** | - |

### Rollback Tests (RB)
| Test | Description |
|------|-------------|
| `rb_01` | All 4 tables created after migration |
| `rb_02` | All 4 tables dropped after rollback |
| `rb_03` | Fresh migration succeeds after rollback |

### Migration Order Tests (MO)
| Test | Description |
|------|-------------|
| `mo_01` | Projects created before candidates |
| `mo_02` | Candidates created before signals |
| `mo_03` | Candidates created before tasks |

## Database Schema

### content_decay_projects
```
┌─────────────────────────────┐
│ content_decay_projects      │
├─────────────────────────────┤
│ id (PK)                     │
│ user_id (FK → users.id)     │
│ site_id (FK → sites.id, UQ) │
│ detection_enabled (bool)    │
│ window_days (tinyint)       │
│ min_clicks_threshold (int)  │
│ min_impressions_threshold   │
│ min_decay_score (decimal)   │
│ digest_enabled (bool)       │
│ digest_frequency (enum)     │
│ settings_json (json, null)  │
│ last_scanned_at (timestamp) │
│ timestamps                  │
└─────────────────────────────┘
```

### content_decay_candidates
```
┌────────────────────────────────────────┐
│ content_decay_candidates               │
├────────────────────────────────────────┤
│ id (PK)                                │
│ project_id (FK → content_decay_projects│
│ page_url (varchar:2048)                │
│ page_url_hash (char:64)                │
│ page_title (varchar:500, null)         │
│ page_type (enum, default:other)        │
│ severity (enum, default:medium)        │
│ decay_score (decimal:5,2)              │
│ detection_confidence (decimal:5,2)     │
│ lost_click_estimate (int, default:0)   │
│ recovery_upside_estimate (int, def:0)  │
│ primary_reason_code (varchar:100)      │
│ reason_codes (json)                    │
│ signal_sources (json, null)            │
│ evidence_json (json, null)             │
│ status (enum, default:backlog)         │
│ first_detected_at (timestamp)          │
│ last_detected_at (timestamp)           │
│ planned_at (timestamp, null)           │
│ started_at (timestamp, null)           │
│ refreshed_at (timestamp, null)         │
│ recovered_at (timestamp, null)         │
│ ignored_at (timestamp, null)           │
│ timestamps                             │
└────────────────────────────────────────┘
┌─────────────────────────────────────────┐
│ Indexes:                                │
│ • UNIQUE(project_id, page_url_hash)     │
│ • INDEX(project_id, status)             │
│ • INDEX(project_id, decay_score)        │
│ • INDEX(project_id, severity)           │
└─────────────────────────────────────────┘
```

### content_decay_signals
```
┌────────────────────────────────────────┐
│ content_decay_signals                  │
├────────────────────────────────────────┤
│ id (PK)                                │
│ candidate_id (FK → content_decay_      │
│   candidates.id)                       │
│ previous_start (date)                  │
│ previous_end (date)                    │
│ current_start (date)                   │
│ current_end (date)                     │
│ previous_clicks (int, default:0)       │
│ current_clicks (int, default:0)        │
│ previous_impressions (int, default:0)  │
│ current_impressions (int, default:0)   │
│ previous_ctr (decimal:8,4, default:0)  │
│ current_ctr (decimal:8,4, default:0)   │
│ previous_avg_position (decimal:8,2)    │
│ current_avg_position (decimal:8,2)     │
│ previous_rank_visibility (decimal:8,2) │
│ current_rank_visibility (decimal:8,2)  │
│ previous_ai_visibility (decimal:8,2)   │
│ current_ai_visibility (decimal:8,2)    │
│ unresolved_audit_issues (int, def:0)   │
│ content_age_days (int, null)           │
│ last_annotation_at (timestamp, null)   │
│ evidence_json (json, null)             │
│ created_at (timestamp)                 │
└────────────────────────────────────────┘
┌─────────────────────────────────────────┐
│ Indexes:                                │
│ • INDEX(candidate_id, created_at)       │
│ • UNIQUE(candidate_id, previous_start,  │
│     previous_end, current_start,        │
│     current_end)                        │
└─────────────────────────────────────────┘
```

### content_refresh_tasks
```
┌────────────────────────────────────────┐
│ content_refresh_tasks                  │
├────────────────────────────────────────┤
│ id (PK)                                │
│ candidate_id (FK → content_decay_      │
│   candidates.id)                       │
│ title (varchar:255)                    │
│ recommendation_type (varchar:100)      │
│ description (text, null)               │
│ notes (text, null)                     │
│ owner_user_id (FK → users.id, null)    │
│ due_date (date, null)                  │
│ status (enum, default:todo)            │
│ source (enum, default:system)          │
│ metadata_json (json, null)             │
│ timestamps                             │
└────────────────────────────────────────┘
┌─────────────────────────────────────────┐
│ Indexes:                                │
│ • INDEX(candidate_id, status)           │
│ • INDEX(owner_user_id, due_date)        │
└─────────────────────────────────────────┘
```

## Test Naming Convention

Tests follow a prefix-based naming pattern:
- **CDP**: Content Decay Projects
- **CDC**: Content Decay Candidates
- **CDS**: Content Decay Signals
- **CRT**: Content Refresh Tasks
- **RB**: Rollback tests
- **MO**: Migration Order tests
- **INT**: Integration tests

Example: `cdp_15_content_decay_projects_has_unique_site_id_index`

## Acceptance Criteria Verification

| # | Criterion | Test Coverage |
|---|-----------|---------------|
| 1 | `content_decay_projects` table with columns and indexes | CDP_01-17, Column type tests |
| 2 | `content_decay_candidates` table with columns and indexes | CDC_01-30, Column type tests |
| 3 | `content_decay_signals` table with columns and indexes | CDS_01-27, Column type tests |
| 4 | `content_refresh_tasks` table with columns and indexes | CRT_01-17, Column type tests |
| 5 | All migrations include down() method for rollback | RB_01-03 |
| 6 | Migration order enforced (projects → candidates → signals → tasks) | MO_01-03 |
| 7 | Unit tests verify table creation and column types | 92 structure tests |

## Running Tests

```bash
# Run all Content Decay migration tests
php artisan test tests/Feature/Database/Migrations/ContentDecayMigrationTest.php
php artisan test tests/Unit/Database/Migrations/ContentDecayMigrationStructureTest.php

# Run specific test category
php artisan test --filter "cdp_"
php artisan test --filter "cdc_"
php artisan test --filter "cds_"
php artisan test --filter "crt_"

# Run rollback tests
php artisan test --filter "rb_"

# Run migration order tests
php artisan test --filter "mo_"
```

## Test Environment Notes

1. **RefreshDatabase Trait**: All tests use `RefreshDatabase` for clean state
2. **SQLite Compatibility**: Tests handle SQLite vs MySQL differences
3. **Foreign Keys**: Tests verify CASCADE delete behavior
4. **Precision Tests**: Decimal precision tests skipped on SQLite

## Dependencies
- `users` table (exists)
- `sites` table (exists)

## Related Tasks
- TASK-36-1: Database Migrations (this test suite)
- TASK-36-2: ContentDecayProject Model
- TASK-36-3: ContentDecayCandidate Model
- TASK-36-4: ContentDecaySignal Model
- TASK-36-5: ContentRefreshTask Model

---

*Created by PreTester agent on 2026-03-10*
*Feature: FEAT-036*
