# CONSTITUTION.md — Project Workflow & Rules

## Project Overview
SEOIndicator — SEO analysis and monitoring tool.

---

## Workflow Rules

### Branching
- See `GIT-BRANCHING-STRATEGY.md` for detailed branching rules
- Main branch = production-ready code only
- Feature branches = `feature/FEAT-X` or `feat/X-short-description`

### Development Process
1. **Plan** → Create SPEC-X for new features
2. **Design** → Review specification, get approval
3. **Implement** → Create tasks (SPEC-X-Y), develop on feature branch
4. **Test** → Write automated tests (TESTS/SPEC-X-Y.md)
5. **Review** → Code review before merge
6. **Deploy** → Follow DEPLOYMENT-SETUP.md

### Must-Follow Rules
- **No direct commits to main** — always use PRs
- **Tests required** — all new features need automated tests
- **Documentation first** — update DOCUMENTATION.md after changes
- **Lessons learned** — capture mistakes in LESSONS-LEARNED/

---

## File Structure Reference

| Path | Purpose |
|------|---------|
| `CONSTITUTION.md` | This file — workflow & rules |
| `DOCUMENTATION.md` | Source of truth for prod functionality |
| `GIT-BRANCHING-STRATEGY.md` | Branch naming, merge rules |
| `AUTO-TESTS-SETUP.md` | Testing approach & tools |
| `DEPLOYMENT-SETUP.md` | Deployment steps & environments |
| `LESSONS-LEARNED/[agent].md` | Agent-specific knowledge base |
| `FEATURES/FEAT-X.md` | Feature descriptions |
| `SPECIFICATIONS/SPEC-X.md` | Technical specifications |
| `TASKS/SPEC-X-Y.md` | Implementation tasks |
| `TESTS/SPEC-X-Y.md` | Automated test scripts |

---

## Iterator System
- **X** = Feature/Specification number (sequential)
- **Y** = Task number within specification (sequential)

Example: `SPEC-23-3` = 3rd task for specification 23, which relates to feature 23.
