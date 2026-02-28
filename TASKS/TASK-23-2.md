# TASK-23-2: Backlink Models and Repositories

## STATUS
`To Write Tests`

## PRIORITY
`High`

## RISK
`Low`

## Type
Backend

## Description
Create Eloquent models and repository classes for backlinks, referring domains, anchor texts, and backlink snapshots. Provides data access layer for backlink operations.

## Related
- Specification: [SPEC-023](../SPECIFICATIONS/SPEC-023.md)
- Feature: [FEAT-023](../FEATURES/FEAT-023.md)
- Depends on: TASK-23-1

---

## Acceptance Criteria
- [ ] `Backlink` model with relationships to Project
- [ ] `ReferringDomain` model with unique constraint handling
- [ ] `AnchorText` model with classification methods
- [ ] `BacklinkSnapshot` model with date scope
- [ ] Repository classes for each model with CRUD operations
- [ ] Model factories for testing
- [ ] Unit tests for all models and repositories

---

## Technical Details

### Models to Create

**Backlink Model:**
```php
class Backlink extends Model
{
    protected $fillable = [
        'project_id', 'source_url', 'source_domain', 'target_url',
        'anchor_text', 'link_type', 'first_seen_at', 'last_checked_at', 'is_active'
    ];
    
    protected $casts = [
        'first_seen_at' => 'datetime',
        'last_checked_at' => 'datetime',
        'is_active' => 'boolean',
    ];
    
    public function project(): BelongsTo { ... }
    public function referringDomain(): HasOne { ... }
    
    // Scopes
    public function scopeActive(Builder $query): Builder { ... }
    public function scopeDofollow(Builder $query): Builder { ... }
    public function scopeForProject(Builder $query, int $projectId): Builder { ... }
}
```

**ReferringDomain Model:**
```php
class ReferringDomain extends Model
{
    protected $fillable = [
        'project_id', 'domain', 'backlinks_count', 'domain_trust_score',
        'first_seen_at', 'last_seen_at'
    ];
    
    protected $casts = [
        'domain_trust_score' => 'integer',
        'backlinks_count' => 'integer',
    ];
    
    public function project(): BelongsTo { ... }
    public function backlinks(): HasMany { ... }
    
    // Scopes
    public function scopeOrderByTrust(Builder $query): Builder { ... }
    public function scopeOrderByBacklinks(Builder $query): Builder { ... }
}
```

**AnchorText Model:**
```php
class AnchorText extends Model
{
    protected $fillable = [
        'project_id', 'anchor_text', 'backlinks_count', 'is_branded', 'is_exact_match'
    ];
    
    protected $casts = [
        'is_branded' => 'boolean',
        'is_exact_match' => 'boolean',
    ];
    
    public function project(): BelongsTo { ... }
    
    // Methods
    public function classify(string $brandName): void { ... }
}
```

**BacklinkSnapshot Model:**
```php
class BacklinkSnapshot extends Model
{
    protected $fillable = [
        'project_id', 'total_backlinks', 'referring_domains_count',
        'dofollow_count', 'nofollow_count', 'avg_domain_trust',
        'anchor_diversity_score', 'snapshot_date'
    ];
    
    protected $casts = [
        'snapshot_date' => 'date',
        'avg_domain_trust' => 'decimal:2',
        'anchor_diversity_score' => 'decimal:2',
    ];
    
    public function project(): BelongsTo { ... }
    
    // Scopes
    public function scopeDateRange(Builder $query, string $from, string $to): Builder { ... }
}
```

### Repositories to Create

- `BacklinkRepository` with batch insert, deactivate stale
- `ReferringDomainRepository` with upsert by domain
- `AnchorTextRepository` with classification updates
- `BacklinkSnapshotRepository` with date-based queries

---

## Dependencies
- TASK-23-1 (Database migrations)

## Effort Estimate
`Medium` (4-6 hours)

---

## Comments
_Agent communication log_

### [2026-02-28 21:08] Agent: ProjectManager
**Prioritization Complete**

- **Priority Confirmed**: `High` — Foundation task for data access layer, required before sync service (TASK-23-4) and API endpoints
- **Risk**: `Low` — Standard model/repository implementation, no external dependencies
- **Dependency**: TASK-23-1 (Migrations) is "To Write Tests" — models can be developed in parallel, tests will validate schema alignment

**Dependency Chain for SPEC-023 (Models Layer):**
```
TASK-23-1 (Migrations) ──┬──→ TASK-23-2 (Models/Repos) ──→ TASK-23-4 (Sync Job)
                         │
TASK-23-3 (GSC Service) ─┘
```

**Execution Order**: After TASK-23-1 tests pass, models can be tested against confirmed schema.

Handing off to PreTester for test design.
