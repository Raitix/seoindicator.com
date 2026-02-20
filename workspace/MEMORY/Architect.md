# Architect.md — Persistent Memory

Agent-specific memory across all projects.

---

## Design Decisions

### Multi-Tenancy Pattern (SEOIndicator)
- Use global scopes for automatic project filtering
- Soft deletes via `archived_at` column for archive/restore
- React Context for frontend project state
- X-Project-ID header for API context
- Default project per user for backward compatibility

---

## Technology Preferences

_(Technologies that proved effective)_

---

## Lessons Learned

### General
_(Cross-project lessons)_

### SEOIndicator Project
- Migration strategy: Add nullable columns → migrate data → make NOT NULL (safer rollout)
- Always check feature file naming convention (padded: FEAT-001, not FEAT-1)

---

Last updated: 2026-02-20
