# CONSTITUTION.md — Project Workflow & Rules

## Project Overview
SEOIndicator — SEO analysis and monitoring tool.

---

## Status Workflow

Every feature, specification, and task has a STATUS that tracks its progress through the pipeline.

### Status Definitions & Agent Responsibilities

| Status | Who | Action |
|--------|-----|--------|
| `New Feature` | Innovator | Creates feature |
| `New Feature` | Architect | Picks up for specification |
| `Specification In Progress` | Architect | Sets this status |
| `To Break Into Tasks` | Architect | Sets this status |
| `To Break Into Tasks` | SpecSystemAnalyst | Picks up for breakdown |
| `Breaking Into Tasks` | SpecSystemAnalyst | Sets this status |
| `To Prioritize` | SpecSystemAnalyst | Sets this status |
| `To Prioritize` | ProjectManager | Picks up for prioritization |
| `Prioritization In Progress` | ProjectManager | Sets this status |
| `To Write Tests` | ProjectManager | Sets this status |
| `To Write Tests` | PreTester | Picks up for test design |
| `Tests Coverage In Progress` | PreTester | Sets this status |
| `To Develop` | PreTester | Sets this status |
| `To Develop` | Developer | Picks up for implementation |
| `In Development` | Developer | Sets this status |
| `To Test Locally` | Developer | Sets this status |
| `To Test Locally` | LocalTester | Picks up for local testing |
| `Testing Locally` | LocalTester | Sets this status |
| `To Deploy` | LocalTester | Sets this status |
| `To Deploy` | DevOps | Picks up for deployment |
| `In Deployment` | DevOps | Sets this status |
| `To Test Prod` | DevOps | Sets this status |
| `To Test Prod` | ProdTester | Picks up for prod testing |
| `Testing Prod` | ProdTester | Sets this status |
| `Done` | ProdTester | Sets this status |
| `Cancelled` | Any agent | Sets with documented reason |

### Status Transitions by Agent

```
FEATURES
┌─────────────────────────────────────────────────────────────────┐
│ Innovator creates FEAT-X with status "New Feature"              │
│ Architect picks up "New Feature" → creates SPEC-X               │
└─────────────────────────────────────────────────────────────────┘
                              ↓
SPECIFICATIONS
┌─────────────────────────────────────────────────────────────────┐
│ Architect: "Specification In Progress" → "To Break Into Tasks"  │
│ SpecSystemAnalyst picks up "To Break Into Tasks"                │
│ SpecSystemAnalyst: "Breaking Into Tasks" → creates TASKS        │
│ SpecSystemAnalyst: "To Prioritize"                              │
└─────────────────────────────────────────────────────────────────┘
                              ↓
TASKS
┌─────────────────────────────────────────────────────────────────┐
│ ProjectManager picks up "To Prioritize"                         │
│ ProjectManager: "Prioritization In Progress" → "To Write Tests" │
│ PreTester picks up "To Write Tests"                             │
│ PreTester: "Tests Coverage In Progress" → "To Develop"          │
│ Developer picks up "To Develop"                                 │
│ Developer: "In Development" → "To Test Locally"                 │
│ LocalTester picks up "To Test Locally"                          │
│ LocalTester: "Testing Locally" → "To Deploy"                    │
│ DevOps picks up "To Deploy"                                     │
│ DevOps: "In Deployment" → "To Test Prod"                        │
│ ProdTester picks up "To Test Prod"                              │
│ ProdTester: "Testing Prod" → "Done"                             │
└─────────────────────────────────────────────────────────────────┘
```

### Parallel Agent Interventions

**SecurityAuditor:**
- Can scan at any time
- Can set `Cancelled` on any item with security reason
- Can HALT deployment (DevOps must wait)
- Can create fix specifications

**PerformanceOptimizer:**
- Can scan at any time
- Can create optimization specifications
- Specs enter pipeline at "Specification In Progress"

---

## Workflow Rules

### Branching
- See `GIT-BRANCHING-STRATEGY.md` for detailed branching rules
- Main branch = production-ready code only
- Feature branches = `feature/FEAT-X` or `feat/X-short-description`

### Development Process
1. **Discover** → Innovator creates FEAT-X (`New Feature`)
2. **Specify** → Architect creates SPEC-X (`Specification In Progress`)
3. **Break** → SpecSystemAnalyst creates TASKS (`Breaking Into Tasks`)
4. **Prioritize** → ProjectManager assigns priority (`Prioritization In Progress`)
5. **Test Design** → PreTester writes tests (`Tests Coverage In Progress`)
6. **Implement** → Developer builds feature (`In Development`)
7. **Test Locally** → LocalTester validates (`Testing Locally`)
8. **Deploy** → DevOps deploys to prod (`In Deployment`)
9. **Test Prod** → ProdTester validates (`Testing Prod`)
10. **Document** → ProdTester marks `Done`, DocSystemAnalyst updates docs

### Must-Follow Rules
- **No direct commits to main** — always use PRs
- **Tests required** — all new features need automated tests
- **Status updates required** — update status after each action
- **Pick up before working** — always check if item is available
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
