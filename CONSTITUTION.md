# CONSTITUTION.md — Project Workflow & Rules

## Project Overview
SEOIndicator — SEO analysis and monitoring tool.

---

## ⚠️ CRITICAL CONSTRAINT: FREE TOOLS ONLY

**All features and implementations must use FREE tools and services only.**

- ❌ No paid APIs, subscriptions, or services
- ❌ No freemium services that require paid plans for reasonable usage
- ✅ Free tiers are acceptable if they provide meaningful functionality
- ✅ Open-source solutions preferred
- ✅ Self-hosted alternatives encouraged

**Exception:** Only if explicitly requested by the user.

### When proposing features, find free alternatives:

| Paid Service | Free Alternative |
|--------------|------------------|
| SerpAPI / DataForSEO | Google Custom Search API (free tier), scraping (respect robots.txt), mock data for development |
| SendGrid / Mailgun | Laravel Mail + free SMTP (Gmail, Mailtrap for dev), in-app notifications |
| AWS / Cloud services | Local development, free hosting tiers (Vercel, Netlify), self-hosted |

**If a paid service is mentioned in a feature, the Architect MUST find a free alternative during specification.**

---

## Development Approach

**Specification Driven Development (SDD)**
- All work begins with specifications
- Tests are written from specifications before implementation
- Implementation follows specifications and tests
- Documentation reflects specifications

---

## Workflow Philosophy

### Asynchronous Agent Flow
- Software Development Life Cycle flows naturally through agents
- Each agent works in isolated session instances
- No central orchestrator — status-based workflow
- Agents pick up work based on concrete statuses
- After agent completes work, instance is terminated

### No Merge Requests
- Production tests ensure stability
- No code review process required
- Direct commits to branches allowed
- 3rd party contributions possible in future (not current scope)

### Communication Model
- Conversations happen in comment sections of:
  - `FEATURES/FEAT-X.md`
  - `SPECIFICATIONS/SPEC-X.md`
  - `TASKS/SPEC-X-Y.md`
- Each agent leaves notes for the next agent
- Decisions documented inline

---

## Priority System

All features, specifications, and tasks have a **PRIORITY** indicator:
- `High` — Critical, blocks other work or business-critical
- `Medium` — Important but not blocking
- `Low` — Nice to have, can be deferred

### Priority Rules
- **Sorting:** Items within same status are sorted by priority (High → Medium → Low)
- **Same Priority:** Agent discretion on order
- **Priority Assignment:**
  - Innovator sets initial priority on feature creation
  - ProjectManager can adjust priorities during prioritization phase

---

## Risk Level System

All features, specifications, and tasks have a **RISK** indicator:

| Level | Description | Examples |
|-------|-------------|----------|
| `Low` | Minimal impact, easily reversible | Copy changes, UI tweaks, new non-critical reports, CSS updates |
| `Medium` | Moderate impact, some reversibility effort | New analysis logic, new endpoints, non-destructive schema changes, config changes |
| `High` | Significant impact, hard to reverse | Billing, authentication, destructive migrations, data deletion, external ToS-sensitive behavior, security changes |

### Risk Assessment Guidelines

**Low Risk:**
- Changes affect only presentation layer
- No database modifications
- No external API calls
- No user authentication/authorization changes
- No billing or payment logic
- Changes are easily rolled back

**Medium Risk:**
- New read-only endpoints
- Non-destructive database migrations (add columns/tables)
- New business logic that doesn't affect existing functionality
- Integration with external services (non-critical)
- Changes to caching or performance logic

**High Risk:**
- Authentication or authorization changes
- Billing or payment processing
- Destructive database operations (drop, truncate, delete data)
- Changes to user data handling
- External API integrations with ToS implications
- Security-related changes
- Changes to audit logging or compliance features

### Risk Level Rules
- **Assignment:** Innovator sets initial risk level on feature creation
- **Reassessment:** Architect must reassess risk during specification
- **Escalation:** Any agent can escalate risk level if they discover higher-risk implications
- **High-Risk Review:** SecurityAuditor must review all High-risk items before deployment
- **Sorting:** Within same priority, items sorted by risk (Low → Medium → High) for processing order (higher risk gets more scrutiny)

### Risk-Based Testing Requirements

| Risk Level | Testing Requirements |
|------------|---------------------|
| `Low` | Standard test coverage, local + prod testing |
| `Medium` | Standard coverage + integration tests, extended prod monitoring |
| `High` | Full coverage + integration tests + security review + staged rollout + manual verification |

---

## Documentation Discrepancy Rule
If **PreTester**, **LocalTester**, or **ProdTester** discovers a discrepancy between `DOCUMENTATION.md` and actual functionality:
1. Create a new task with status `To Write Tests`
2. Set priority to `High`
3. Document the discrepancy in the task description
4. The task enters normal workflow for resolution

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
| `Documentation` | ProdTester | Sets this status after successful prod test |
| `Documentation` | DocSystemAnalyst | Picks up for documentation update |
| `Done` | DocSystemAnalyst | Sets this status after updating docs |
| `Cancelled` | Any agent | Sets with documented reason (see below) |

### Cancellation Protocol

When setting status to `Cancelled`, the agent **MUST** add a `## Cancellation Reason` section to the feature/spec/task file:

```markdown
## Cancellation Reason
**Cancelled by:** Agent Name
**Date:** YYYY-MM-DD
**Reason:** Clear explanation (e.g., "Requires paid API - violates free tools constraint")
```

This allows Innovator to search and understand why similar features were rejected.

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
│ ProdTester: "Testing Prod" → "Documentation"                    │
│ DocSystemAnalyst picks up "Documentation"                       │
│ DocSystemAnalyst: Updates DOCUMENTATION.md → "Done"             │
│ When all tasks under a spec are "Done":                         │
│   DocSystemAnalyst: Set spec status to "Done"                   │
│   DocSystemAnalyst: Set feature status to "Done"                │
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

### Concurrency Rules

**Maximum Parallelism:**
- **1 functionality per agent instance** — Each agent instance works on exactly one feature, specification, or task

### Branching
- Main branch = production-ready code only
- Feature branches = `feature/FEAT-X` or `feat/X-short-description`
- No merge requests required
- Direct commits allowed

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
10. **Document** → ProdTester marks `Documentation`, DocSystemAnalyst updates `DOCUMENTATION.md` → marks `Done`
11. **Close** → DocSystemAnalyst marks spec and feature as `Done` when all tasks complete

### Must-Follow Rules
- **Tests required** — all new features need automated tests
- **Status updates required** — update status after each action
- **Pick up before working** — always check if item is available
- **Documentation first** — update DOCUMENTATION.md after changes
- **Lessons learned** — capture mistakes in LESSONS-LEARNED/
- **Comments for communication** — leave notes for next agent
- **Git commits required** — after any changes, commit immediately (both repos: `~/.openclaw` and project repo). No push needed.

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
| `FEATURES/FEAT-X.md` | Feature descriptions + comments |
| `SPECIFICATIONS/SPEC-X.md` | Technical specifications + comments |
| `TASKS/TASK-X-Y.md` | Implementation tasks + comments |
| `TESTS/TEST-X-Y.md` | Automated test scripts |

---

## Iterator System
- **X** = Feature/Specification number (sequential, same number for both)
- **Y** = Task number within specification (sequential)

**Naming Convention:**
- Features: `FEAT-X.md` (e.g., `FEAT-3.md`)
- Specifications: `SPEC-X.md` (e.g., `SPEC-3.md` relates to `FEAT-3`)
- Tasks: `TASK-X-Y.md` (e.g., `TASK-3-4` = 4th task for specification 3)
- Tests: `TEST-X-Y.md` (e.g., `TEST-3-4.md` tests `TASK-3-4`)

**Note:** Features and specifications share the same number (FEAT-3 ↔ SPEC-3) for easier tracking.
