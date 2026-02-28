# TEST-23-2: Backlink Models and Repositories

## Task Reference
- **Task:** TASK-23-2
- **Feature:** FEAT-023 (Backlinks Indicator)
- **Specification:** SPEC-023
- **Status:** Tests Coverage In Progress

---

## Overview
Tests for backlink-related Eloquent models and repository classes. Covers:
- `Backlink` model with relationships and scopes
- `ReferringDomain` model with unique constraint handling
- `AnchorText` model with classification methods
- `BacklinkSnapshot` model with date scope
- Repository classes for CRUD operations

---

## Test Files

| File | Type | Tests | Assertions |
|------|------|-------|------------|
| `backend/tests/Unit/Models/BacklinkTest.php` | Unit | 25+ | 50+ |
| `backend/tests/Unit/Models/ReferringDomainTest.php` | Unit | 20+ | 40+ |
| `backend/tests/Unit/Models/AnchorTextTest.php` | Unit | 20+ | 40+ |
| `backend/tests/Unit/Models/BacklinkSnapshotTest.php` | Unit | 15+ | 30+ |
| `backend/tests/Unit/Repositories/BacklinkRepositoryTest.php` | Unit | 30+ | 60+ |
| `backend/tests/Unit/Repositories/ReferringDomainRepositoryTest.php` | Unit | 20+ | 40+ |
| `backend/tests/Unit/Repositories/AnchorTextRepositoryTest.php` | Unit | 20+ | 40+ |
| `backend/tests/Unit/Repositories/BacklinkSnapshotRepositoryTest.php` | Unit | 15+ | 30+ |

---

## BACKLINK MODEL TESTS

### Model Attribute Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| BM-01 | Create with all attributes | Valid data | Model persisted with all fields |
| BM-02 | Cast first_seen_at to datetime | Carbon instance | DateTime cast applied |
| BM-03 | Cast last_checked_at to datetime | Carbon instance | DateTime cast applied |
| BM-04 | Cast is_active to boolean | true/false | Boolean cast applied |
| BM-05 | Default is_active | Omit is_active | Defaults to true |
| BM-06 | Default link_type | Omit link_type | Defaults to 'unknown' |
| BM-07 | Fillable attributes | Mass assignment | All expected fields fillable |
| BM-08 | Table name | Get table | 'backlinks' |

### Relationship Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| BM-09 | BelongsTo Project | $backlink->project | Returns Project instance |
| BM-10 | Project foreign key | project_id | Correctly linked |

### Scope Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| BM-11 | scopeActive | Active backlinks only | Filters is_active = true |
| BM-12 | scopeDofollow | Dofollow links only | Filters link_type = 'dofollow' |
| BM-13 | scopeNofollow | Nofollow links only | Filters link_type = 'nofollow' |
| BM-14 | Chain scopes | active()->dofollow() | Both filters applied |

### Edge Cases

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| BM-15 | Null anchor_text | Omit anchor_text | Nullable, accepts null |
| BM-16 | Null first_seen_at | Omit first_seen_at | Nullable, accepts null |
| BM-17 | Null last_checked_at | Omit last_checked_at | Nullable, accepts null |
| BM-18 | Long source_url | 2083 char URL | Stored correctly |
| BM-19 | Long anchor_text | 500 char text | Stored correctly |
| BM-20 | Invalid link_type | 'invalid_type' | Depends on DB (rejected or stored) |

### Security Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| BM-21 | SQL injection in source_url | Malicious SQL | Stored safely (parameterized) |
| BM-22 | SQL injection in anchor_text | Malicious SQL | Stored safely (parameterized) |
| BM-23 | XSS in anchor_text | Script tag | Stored safely (output escaped) |
| BM-24 | Unicode in source_domain | 例え.jp | Handled correctly |

---

## REFERRING_DOMAIN MODEL TESTS

### Model Attribute Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| RD-01 | Create with all attributes | Valid data | Model persisted with all fields |
| RD-02 | Cast backlinks_count to integer | Integer value | Integer cast applied |
| RD-03 | Cast domain_trust_score to integer | Integer value | Integer cast applied |
| RD-04 | Cast first_seen_at to datetime | Carbon instance | DateTime cast applied |
| RD-05 | Cast last_seen_at to datetime | Carbon instance | DateTime cast applied |
| RD-06 | Default backlinks_count | Omit field | Defaults to 0 |
| RD-07 | Default domain_trust_score | Omit field | Defaults to 0 |
| RD-08 | Fillable attributes | Mass assignment | All expected fields fillable |
| RD-09 | Table name | Get table | 'referring_domains' |

### Relationship Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| RD-10 | BelongsTo Project | $domain->project | Returns Project instance |
| RD-11 | Project foreign key | project_id | Correctly linked |

### Scope Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| RD-12 | scopeOrderByBacklinksCount | Order query | Orders by backlinks_count DESC |
| RD-13 | scopeOrderByTrustScore | Order query | Orders by domain_trust_score DESC |

### Unique Constraint Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| RD-14 | Unique project_domain | Same domain, same project | Error / rejected |
| RD-15 | Same domain different project | Same domain, different project | Success (allowed) |

### Edge Cases

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| RD-16 | Domain trust score 0 | Insert with 0 | Success |
| RD-17 | Domain trust score 100 | Insert with 100 | Success |
| RD-18 | Domain trust score > 255 | Insert with 256 | Error / truncated (TINYINT) |
| RD-19 | Null first_seen_at | Omit field | Nullable, accepts null |
| RD-20 | Null last_seen_at | Omit field | Nullable, accepts null |

### Security Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| RD-21 | SQL injection in domain | Malicious SQL | Stored safely |
| RD-22 | XSS in domain | Script tag | Stored safely |
| RD-23 | Unicode domain | 例え.jp | Handled correctly |

---

## ANCHOR_TEXT MODEL TESTS

### Model Attribute Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| AT-01 | Create with all attributes | Valid data | Model persisted with all fields |
| AT-02 | Cast backlinks_count to integer | Integer value | Integer cast applied |
| AT-03 | Cast is_branded to boolean | true/false | Boolean cast applied |
| AT-04 | Cast is_exact_match to boolean | true/false | Boolean cast applied |
| AT-05 | Default backlinks_count | Omit field | Defaults to 0 |
| AT-06 | Default is_branded | Omit field | Defaults to false |
| AT-07 | Default is_exact_match | Omit field | Defaults to false |
| AT-08 | Fillable attributes | Mass assignment | All expected fields fillable |
| AT-09 | Table name | Get table | 'anchor_texts' |

### Relationship Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| AT-10 | BelongsTo Project | $anchor->project | Returns Project instance |
| AT-11 | Project foreign key | project_id | Correctly linked |

### Scope Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| AT-12 | scopeOrderByBacklinksCount | Order query | Orders by backlinks_count DESC |
| AT-13 | scopeBranded | Filter query | Filters is_branded = true |
| AT-14 | scopeExactMatch | Filter query | Filters is_exact_match = true |
| AT-15 | Chain scopes | branded()->exactMatch() | Both filters applied |

### Edge Cases

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| AT-16 | Long anchor_text | 500 char text | Stored correctly |
| AT-17 | Both flags true | is_branded=true, is_exact_match=true | Success (both can be true) |
| AT-18 | Both flags false | is_branded=false, is_exact_match=false | Success |
| AT-19 | Unicode anchor_text | 点击这里 | Handled correctly |
| AT-20 | Empty anchor_text | Empty string | Depends on business logic |

### Security Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| AT-21 | SQL injection in anchor_text | Malicious SQL | Stored safely |
| AT-22 | XSS in anchor_text | Script tag | Stored safely |
| AT-23 | HTML in anchor_text | <a>link</a> | Stored safely |

---

## BACKLINK_SNAPSHOT MODEL TESTS

### Model Attribute Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| BS-01 | Create with all attributes | Valid data | Model persisted with all fields |
| BS-02 | Cast snapshot_date to date | Carbon date | Date cast applied |
| BS-03 | Cast avg_domain_trust to decimal | Float value | Decimal:2 cast applied |
| BS-04 | Cast anchor_diversity_score to decimal | Float value | Decimal:2 cast applied |
| BS-05 | Default total_backlinks | Omit field | Defaults to 0 |
| BS-06 | Default referring_domains_count | Omit field | Defaults to 0 |
| BS-07 | Default dofollow_count | Omit field | Defaults to 0 |
| BS-08 | Default nofollow_count | Omit field | Defaults to 0 |
| BS-09 | No timestamps | Check $timestamps | false |
| BS-10 | Fillable attributes | Mass assignment | All expected fields fillable |
| BS-11 | Table name | Get table | 'backlink_snapshots' |

### Relationship Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| BS-12 | BelongsTo Project | $snapshot->project | Returns Project instance |
| BS-13 | Project foreign key | project_id | Correctly linked |

### Scope Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| BS-14 | scopeForProject | Filter by project_id | Filters correctly |
| BS-15 | scopeDateRange | from/to dates | Filters between dates |
| BS-16 | scopeOrderByDateAsc | Order query | Orders by snapshot_date ASC |
| BS-17 | Chain scopes | forProject()->dateRange() | Both filters applied |

### Unique Constraint Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| BS-18 | Unique project_date | Same date, same project | Error / rejected |
| BS-19 | Same date different project | Same date, different project | Success (allowed) |
| BS-20 | Multiple dates same project | Different dates, same project | Success |

### API Response Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| BS-21 | toApiResponse method | Valid snapshot | Array with all fields |
| BS-22 | Decimal casting in response | 45.67 | Returns as float 45.67 |
| BS-23 | Date format in response | Carbon date | Returns as Y-m-d string |

### Edge Cases

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| BS-24 | All zeros | All counts = 0 | Success |
| BS-25 | Large counts | 1,000,000 backlinks | Success (INT UNSIGNED) |
| BS-26 | Decimal precision | 45.678 | Rounded to 45.68 |
| BS-27 | Null avg_domain_trust | Omit field | Uses default 0.00 |
| BS-28 | Future snapshot_date | Tomorrow's date | Success |

---

## BACKLINK REPOSITORY TESTS

### CRUD Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| BR-01 | Create backlink | Valid data | Backlink created |
| BR-02 | Find by ID | Existing ID | Returns Backlink |
| BR-03 | Find by ID not found | Non-existent ID | Returns null |
| BR-04 | Update backlink | New data | Backlink updated |
| BR-05 | Delete backlink | Existing ID | Backlink deleted |
| BR-06 | Get all for project | project_id | Collection of Backlinks |

### Batch Operations Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| BR-07 | Batch insert | Array of backlinks | All created |
| BR-08 | Batch insert empty | Empty array | No error, no records |
| BR-09 | Batch insert duplicates | Duplicate source_urls | Handled gracefully |

### Deactivation Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| BR-10 | Deactivate stale | project_id, cutoff_date | Old backlinks marked inactive |
| BR-11 | Deactivate preserves recent | Recent backlink | Not deactivated |
| BR-12 | Deactivate by source | List of source_urls | Specified backlinks deactivated |

### Query Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| BR-13 | Get active count | project_id | Count of active backlinks |
| BR-14 | Get dofollow count | project_id | Count of dofollow backlinks |
| BR-15 | Get by domain | project_id, domain | Backlinks from that domain |
| BR-16 | Get recent | project_id, limit | Most recent backlinks |

### Performance Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| BR-17 | Batch insert 1000 | 1000 records | Completes in <5 seconds |
| BR-18 | Query with pagination | page, per_page | Efficient pagination |

---

## REFERRING_DOMAIN REPOSITORY TESTS

### CRUD Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| RDR-01 | Create domain | Valid data | ReferringDomain created |
| RDR-02 | Find by ID | Existing ID | Returns ReferringDomain |
| RDR-03 | Find by ID not found | Non-existent ID | Returns null |
| RDR-04 | Update domain | New data | ReferringDomain updated |
| RDR-05 | Delete domain | Existing ID | ReferringDomain deleted |
| RDR-06 | Get all for project | project_id | Collection of ReferringDomains |

### Upsert Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| RDR-07 | Upsert new domain | New domain | Created |
| RDR-08 | Upsert existing domain | Existing domain | Updated |
| RDR-09 | Upsert by project_domain | project_id, domain | Correctly matched |

### Query Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| RDR-10 | Get ordered by backlinks | project_id | Ordered by backlinks_count DESC |
| RDR-11 | Get ordered by trust | project_id | Ordered by domain_trust_score DESC |
| RDR-12 | Get top domains | project_id, limit | Top N domains |
| RDR-13 | Get count for project | project_id | Total count |

---

## ANCHOR_TEXT REPOSITORY TESTS

### CRUD Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| AR-01 | Create anchor | Valid data | AnchorText created |
| AR-02 | Find by ID | Existing ID | Returns AnchorText |
| AR-03 | Find by ID not found | Non-existent ID | Returns null |
| AR-04 | Update anchor | New data | AnchorText updated |
| AR-05 | Delete anchor | Existing ID | AnchorText deleted |
| AR-06 | Get all for project | project_id | Collection of AnchorTexts |

### Classification Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| AR-07 | Update classification | is_branded, is_exact_match | Flags updated |
| AR-08 | Bulk classify | Array with flags | All updated |

### Query Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| AR-09 | Get branded anchors | project_id | Only is_branded = true |
| AR-10 | Get exact match anchors | project_id | Only is_exact_match = true |
| AR-11 | Get top anchors | project_id, limit | Top N by backlinks_count |
| AR-12 | Get count for project | project_id | Total count |

---

## BACKLINK_SNAPSHOT REPOSITORY TESTS

### CRUD Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| SR-01 | Create snapshot | Valid data | BacklinkSnapshot created |
| SR-02 | Find by ID | Existing ID | Returns BacklinkSnapshot |
| SR-03 | Find by ID not found | Non-existent ID | Returns null |
| SR-04 | Get all for project | project_id | Collection of Snapshots |

### Date Query Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| SR-05 | Get by date | project_id, date | Snapshot for that date |
| SR-06 | Get by date range | project_id, from, to | Snapshots in range |
| SR-07 | Get latest | project_id | Most recent snapshot |
| SR-08 | Get previous | project_id, date | Snapshot before date |

### Unique Constraint Tests

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| SR-09 | Upsert new date | New date | Created |
| SR-10 | Upsert existing date | Existing date | Updated |
| SR-11 | Duplicate date rejected | Same project, same date | Error / rejected |

---

## Model Factories

### BacklinkFactory
```php
// Definition for creating test backlinks
Backlink::factory()->create([
    'project_id' => $project->id,
    'source_url' => 'https://example.com/page',
    'source_domain' => 'example.com',
    'target_url' => 'https://myproject.com/target',
    'anchor_text' => 'keyword',
    'link_type' => 'dofollow',
    'is_active' => true,
]);
```

### ReferringDomainFactory
```php
// Definition for creating test referring domains
ReferringDomain::factory()->create([
    'project_id' => $project->id,
    'domain' => 'example.com',
    'backlinks_count' => 10,
    'domain_trust_score' => 75,
]);
```

### AnchorTextFactory
```php
// Definition for creating test anchor texts
AnchorText::factory()->create([
    'project_id' => $project->id,
    'anchor_text' => 'best SEO tools',
    'backlinks_count' => 50,
    'is_branded' => false,
    'is_exact_match' => true,
]);
```

### BacklinkSnapshotFactory
```php
// Definition for creating test snapshots
BacklinkSnapshot::factory()->create([
    'project_id' => $project->id,
    'total_backlinks' => 1000,
    'referring_domains_count' => 50,
    'snapshot_date' => '2026-02-28',
]);
```

---

## Coverage Targets

| Category | Target | Description |
|----------|--------|-------------|
| Model Attributes | 100% | All fillable, casts, defaults |
| Model Relationships | 100% | All relationships defined |
| Model Scopes | 100% | All scopes tested |
| Repository CRUD | 100% | Create, read, update, delete |
| Repository Batch | 100% | Batch operations |
| Repository Queries | 100% | All query methods |
| Edge Cases | 90% | Boundary conditions |
| Security | 100% | SQL injection, XSS on all text fields |

---

## Dependencies
- TASK-23-1 (Migrations) - Required for schema
- Project model and factory - For FK relationships
- Laravel testing utilities

---

## Notes for Developer

### Repository Pattern
Each repository should follow this interface pattern:
- `create(array $data): Model`
- `find(int $id): ?Model`
- `update(int $id, array $data): bool`
- `delete(int $id): bool`
- `getByProject(int $projectId): Collection`

### Factory States
Consider adding factory states for common scenarios:
- `dofollow()` - Sets link_type to dofollow
- `nofollow()` - Sets link_type to nofollow
- `inactive()` - Sets is_active to false
- `branded()` - Sets is_branded to true

### Performance Considerations
- Use chunk() for large batch operations
- Consider using upsert() for bulk inserts with conflict handling
- Index usage should be verified in query tests

---

## Comments

### [2026-02-28 22:53] Agent: PreTester
- Test documentation created for all 4 models and 4 repositories
- 150+ test scenarios covering CRUD, relationships, scopes, edge cases, security
- Factory definitions provided for test data generation
- Ready for test implementation
