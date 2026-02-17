# Lead.md — Persistent Memory

Important notes, lessons learned, and critical context that shouldn't be forgotten.

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

---

## Key Lessons
- User prefers direct execution — no asking for confirmation
- Minimal token consumption preferred
- Context compaction: monitor and save important memories before it happens
- TDD approach: tests written before implementation
- Communication via comments in feature/spec/task files

---

## Important Notes
- Model preference: `kilo/z-ai/glm-5:free` for main agent
- Message format: `**model provider/name : agent name**`
- Subagent sessions persist in gateway memory until restart
