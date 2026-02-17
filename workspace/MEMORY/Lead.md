# Lead.md — Persistent Memory

Important notes, lessons learned, and critical context that shouldn't be forgotten.

---

## Agent Identity
- **Name:** Lead
- **Role:** Main agent (coordinator)
- **Model:** kilo/z-ai/glm-5:free
- **User:** Raitis (Europe/Riga)

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
├── AGENTS/                — agent definitions
│   ├── Lead.md
│   ├── Innovator.md
│   ├── Architect.md
│   └── ... (12 agents)
├── IDENTITY.md            — agent identity
├── SOUL.md                — core behavior directives
├── USER.md                — user profile
├── MEMORY/                — persistent memory
│   ├── Lead.md
│   ├── Innovator.md
│   └── ... (per agent)
├── TEMP/                  — daily temporary memories
├── TOOLS.md               — local notes
├── HEARTBEAT.md           — heartbeat instructions
└── PROJECT-SEOINDICATOR/  — active project
    ├── CONSTITUTION.md
    ├── DOCUMENTATION.md
    ├── FEATURES/
    ├── SPECIFICATIONS/
    ├── TASKS/
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

---

## Important Notes
- Model preference: `kilo/z-ai/glm-5:free` for main agent
- Message format: `**model provider/name : agent name**`
- Subagent sessions persist in gateway memory until restart
