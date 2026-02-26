# TASK-5-3: Opportunity Scorer Utility

## STATUS
`To Write Tests`

## PRIORITY
`Medium`

## RISK
`Low`

## Description
Create a utility class for calculating keyword opportunity scores. This helps users prioritize which suggested keywords to track.

## Acceptance Criteria
- [ ] OpportunityScorer class with calculate(keyword: string): int method
- [ ] Scoring factors: word count (long-tail bonus), question prefix, commercial intent keywords
- [ ] Base score: 50
- [ ] Word count bonus: +10 for 3+ words, +5 for 5+ words
- [ ] Question prefix bonus: +15 for what/how/why/when/where/who/which
- [ ] Commercial intent bonus: +10 for best/top/review/compare
- [ ] Maximum score: 100

## Dependencies
- None (standalone utility)

## Effort Estimate
`Small` (1-2 hours)

## Technical Notes
- Pure function, easily testable
- Can be extended later with search volume/difficulty data
- Consider making it an invokable class for DI

---

## Comments
_Agent communication log_

### [2026-02-26 22:42] Agent: ProjectManager
Created task from SPEC-005 breakdown. Priority: Medium (utility, not blocking other tasks). Risk: Low (pure function, no external dependencies). No dependencies - can be developed in parallel.
