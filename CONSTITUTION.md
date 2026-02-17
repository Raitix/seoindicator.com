# CONSTITUTION.md — Project Workflow & Rules

## Project Overview
SEOIndicator — SEO analysis and monitoring tool.

---

## Status Workflow

Every feature, specification, and task has a STATUS that tracks its progress through the pipeline.

### Status Definitions

| Status | Description | Applied To |
|--------|-------------|------------|
| `New Feature` | Ready for specification | Feature |
| `Specification In Progress` | Technical description in progress | Specification |
| `To Break Into Tasks` | Ready to be picked up for breakdown | Specification |
| `Breaking Into Tasks` | Breaking into tasks in progress | Specification |
| `To Prioritize` | Ready to be prioritized | Tasks |
| `Prioritization In Progress` | Prioritization ongoing | Tasks |
| `To Write Tests` | Ready for automated test coverage | Task |
| `Tests Coverage In Progress` | Writing automated tests | Task |
| `To Develop` | Ready for implementation | Task |
| `In Development` | Implementation in progress | Task |
| `To Test Locally` | Ready for local testing | Task |
| `Testing Locally` | Local testing in progress | Task |
| `To Deploy` | Ready for deployment | Task |
| `In Deployment` | Deployment in progress | Task |
| `To Test Prod` | Ready for production testing | Task |
| `Testing Prod` | Production testing in progress | Task |
| `Done` | Successfully delivered | All |
| `Cancelled` | Not viable, duplicate, or unnecessary | All |

### Status Transitions

```
FEATURE → SPECIFICATION → TASKS → TESTS → DEVELOPMENT → LOCAL TEST → DEPLOY → PROD TEST → DONE
```

**Feature Flow:**
```
New Feature → (handed to Architect)
```

**Specification Flow:**
```
Specification In Progress → To Break Into Tasks → Breaking Into Tasks → (handed to ProjectManager)
```

**Task Flow:**
```
To Prioritize → Prioritization In Progress → To Write Tests → Tests Coverage In Progress →
To Develop → In Development → To Test Locally → Testing Locally →
To Deploy → In Deployment → To Test Prod → Testing Prod → Done
```

**Alternative End:**
```
Any status → Cancelled (with reason documented)
```

---

## Workflow Rules

### Branching
- See `GIT-BRANCHING-STRATEGY.md` for detailed branching rules
- Main branch = production-ready code only
- Feature branches = `feature/FEAT-X` or `feat/X-short-description`

### Development Process
1. **Plan** → Create FEAT-X with status `New Feature`
2. **Specify** → Create SPEC-X with status `Specification In Progress`
3. **Break** → Break into tasks, status `Breaking Into Tasks`
4. **Prioritize** → Assign priorities, status `Prioritization In Progress`
5. **Test Design** → Write tests first, status `Tests Coverage In Progress`
6. **Implement** → Develop to pass tests, status `In Development`
7. **Test Locally** → Run tests locally, status `Testing Locally`
8. **Deploy** → Deploy to production, status `In Deployment`
9. **Test Prod** → Verify in production, status `Testing Prod`
10. **Document** → Update docs, mark `Done`

### Must-Follow Rules
- **No direct commits to main** — always use PRs
- **Tests required** — all new features need automated tests
- **Status updates required** — update status after each action
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
