# Lead.md — Persistent Memory

Important notes, lessons learned, and critical context that shouldn't be forgotten.

---

## Coordinator Role

**Lead is the central hub:**
- User's primary contact for status, issues, feature requests
- Monitors all active development for stuck items
- Investigates blockers and delegates resolution
- Key contact between agents for urgent cross-agent matters

**Follow-up Process:**
1. Check active features/specs/tasks in PROJECT-SEOINDICATOR
2. Identify items with no recent activity (check comment timestamps)
3. Read comments to understand blockers
4. Decide: solve directly OR spawn appropriate agent
5. Update comments with resolution notes

**Stuck Detection Threshold:**
- No updates for 24+ hours = needs follow-up
- No updates for 48+ hours = escalate to user

---

## Agent Identity
- **Name:** Lead
- **Role:** Main agent (coordinator)
- **Model:** kilo/z-ai/glm-5:free
- **User:** Raitis (Europe/Riga)

---

## Development Philosophy

**Specification Driven Development (SDD)**
- All work begins with specifications
- Tests written from specs before implementation
- Implementation follows specs and tests

**Asynchronous Agent Flow**
- SDLC flows naturally through agents
- No central orchestrator — status-based workflow
- Agents work in isolated instances
- Instance terminated after work complete
- Comments section for agent communication

**No Merge Requests**
- Production tests ensure stability
- Direct commits to branches allowed

---

## Multi-Agent Workflow

### Pipeline Agents (Sequential)
```
Innovator → Architect → SpecSystemAnalyst → ProjectManager → PreTester → Developer → LocalTester → DevOps → ProdTester → DocSystemAnalyst
```

### Parallel Agents
- **SecurityAuditor** — Can HALT deployments, mandate rollbacks
- **PerformanceOptimizer** — Creates optimization specs

### Status Workflow
```
New Feature → Specification In Progress → To Break Into Tasks →
Breaking Into Tasks → To Prioritize → Prioritization In Progress →
To Write Tests → Tests Coverage In Progress → To Develop →
In Development → To Test Locally → Testing Locally →
To Deploy → In Deployment → To Test Prod → Testing Prod → Done

Also: Cancelled (any stage)
```

### Concurrency Limits
- **1 functionality per agent** (each instance handles one item)

---

## Workspace Structure
```
workspace/
├── AGENTS.md              — workspace instructions, agent list
├── AGENTS/                — agent definitions with context loading rules
├── IDENTITY.md            — agent identity
├── SOUL.md                — core behavior directives
├── USER.md                — user profile
├── MEMORY/                — persistent memory (per agent)
├── TEMP/                  — daily temporary memories
├── TOOLS.md               — local notes
├── HEARTBEAT.md           — heartbeat instructions
└── PROJECT-SEOINDICATOR/  — active project
    ├── CONSTITUTION.md    — workflow + SDD philosophy
    ├── DOCUMENTATION.md
    ├── FEATURES/          — FEAT-X.md + comments
    ├── SPECIFICATIONS/    — SPEC-X.md + comments
    ├── TASKS/             — SPEC-X-Y.md + comments
    ├── TESTS/
    └── LESSONS-LEARNED/
```

---

## Git Repositories
- **OpenClaw config:** `C:\Users\Raitis\.openclaw` (workspace, agents, memory)
- **Project:** `PROJECT-SEOINDICATOR/` (separate repo)

### Git Commit Rule
**After any changes in either repo, ALWAYS commit:**
- No asking — just commit
- No push needed (user handles pushes)
- Applies to ALL agents, not just Lead
- Commit message should be descriptive but concise

---

## Key Lessons
- User prefers direct execution — no asking for confirmation
- Minimal token consumption preferred
- Context compaction: monitor and save important memories before it happens
- TDD approach: tests written before implementation
- Communication via comments in feature/spec/task files
- **STRICT WORKFLOW COMPLIANCE:** Follow AGENTS.md structure, MEMORY/TEMP/ for daily logs, MEMORY/[agent].md for persistent
- **Monitor other agents:** Ensure all agents follow project workflow rules, correct them when needed
- **BOOTSTRAP.md is reserved** for first-run only (OpenClaw removes it after initial setup) — never create this file manually
- **Risk Levels:** All features/specs/tasks must have RISK indicator (Low/Medium/High) — High-risk requires SecurityAuditor review

---

## Important Notes
- Model preference: `kilo/z-ai/glm-5:free` for main agent
- Fallback model: `github-copilot/gpt-4o` (auto-switch on quota limits)
- Message format: `**model provider/name : agent name**`
- Subagent sessions persist in gateway memory until restart
- Session health check: Background PowerShell script every 5 min (no LLM, scripts/session-health-check.ps1)
- Memory compaction: Auto-triggers when MEMORY/*.md or LESSONS-LEARNED/*.md exceeds 500KB

---

## Active Work Tracking

### Current Features In Progress
_(Update as features move through pipeline)_

| Feature | Status | Priority | Current Agent | Last Updated |
|---------|--------|----------|---------------|--------------|
| SEOIndicator Stack Setup | Done | High | Lead | 2026-02-18 |

### Known Blockers
_(Track issues requiring attention)_

| Item | Blocker | Since | Resolution Plan |
|------|---------|-------|-----------------|
| (none) | - | - | - |

### Next Steps
1. Connect to PROD FTP/DB
2. Build API endpoints for SEO indicators
3. Create first feature via Innovator agent

---

## Auto-Start Services

Services automatically start via `boot-md` hook when gateway starts:
- MySQL (via XAMPP)
- Backend (Laravel on port 8000)
- Frontend (Next.js on port 3000)

Config: `workspace/BOOT.md`

---

## Google Analytics Access

**Measurement ID:** `G-RWCSLW7EXF`
**Property ID:** `489951380`
**Account ID:** `355806861`
**Dashboard:** https://analytics.google.com/analytics/web/#/a355806861p489951380

### How to Query GA Data

Lead, Innovator, and PerformanceOptimizer agents can query GA data via:

**Option 1: Direct Dashboard Access**
- URL: https://analytics.google.com/analytics/web/#/a355806861p489951380/reports/intelligenthome
- User has browser access

**Option 2: GA Data API (for automated queries)**
- Requires Google Cloud service account (TODO: set up)
- Credentials stored in: `~/.openclaw/secrets/google-analytics-service-account.json`
- API endpoint: `https://analyticsdata.googleapis.com/v1beta/properties/489951380:runReport`

### Key Metrics to Monitor

| Metric | Description | Use For |
|--------|-------------|---------|
| `activeUsers` | Current active users | Real-time load |
| `screenPageViews` | Total page views | Traffic trends |
| `sessions` | User sessions | Engagement |
| `averageSessionDuration` | Time on site | Content quality |
| `bounceRate` | Single-page sessions | UX issues |
| `pageLoadTime` | Page speed | Performance |
| `firstInputDelay` | Interactivity | UX performance |

### Common Queries

**Daily active users (last 7 days):**
```
POST https://analyticsdata.googleapis.com/v1beta/properties/489951380:runReport
{
  "dateRanges": [{"startDate": "7daysAgo", "endDate": "yesterday"}],
  "metrics": [{"name": "activeUsers"}],
  "dimensions": [{"name": "date"}]
}
```

**Top pages by traffic:**
```
{
  "dateRanges": [{"startDate": "30daysAgo", "endDate": "yesterday"}],
  "metrics": [{"name": "screenPageViews"}],
  "dimensions": [{"name": "pagePath"}],
  "orderBys": [{"metric": {"metricName": "screenPageViews"}, "desc": true}],
  "limit": 10
}
```

### When to Check GA Data

- **Innovator:** Before proposing new features — check what users actually use
- **PerformanceOptimizer:** When investigating performance issues
- **Lead:** When user asks about traffic/retention/engagement
