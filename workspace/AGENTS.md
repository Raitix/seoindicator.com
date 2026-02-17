# AGENTS.md - Workspace

## Message Format
Always start messages with **model provider/name : agent name** in bold, followed by an empty line.

## Every Session
1. Read `SOUL.md` — who you are
2. Read `USER.md` — who you're helping
3. Read `MEMORY/TEMP/YYYY-MM-DD.md` (today + yesterday) for recent context
4. **If Lead (main session):** Also read `MEMORY/Lead.md` for persistent memory

## Agents
Agent definitions stored in `AGENTS/` folder. Each agent has a dedicated `.md` file with role, capabilities, and behavior.

### Pipeline Agents (Sequential Flow)
| Agent | Role | Receives From | Hands Off To |
|-------|------|---------------|--------------|
| **Innovator** | Feature discovery | — | Architect |
| **Architect** | Technical specification | Innovator | SpecSystemAnalyst |
| **SpecSystemAnalyst** | Task breakdown | Architect | ProjectManager |
| **ProjectManager** | Task prioritization | SpecSystemAnalyst | PreTester |
| **PreTester** | Test design | ProjectManager | Developer |
| **Developer** | Implementation | PreTester | LocalTester |
| **LocalTester** | Local QA | Developer | DevOps |
| **DevOps** | Deployment | LocalTester | ProdTester |
| **ProdTester** | Production QA | DevOps | DocSystemAnalyst |
| **DocSystemAnalyst** | Documentation | ProdTester | — (Task Complete) |

### Parallel Agents (Independent Operation)
| Agent | Role | Authority |
|-------|------|-----------|
| **SecurityAuditor** | Security scanning | Can HALT deployments, mandate rollbacks |
| **PerformanceOptimizer** | Performance analysis | Creates optimization specs |

### Context Loading Per Agent

Each agent loads a fresh session with specific context files:

**All Agents Load:**
```
AGENTS/[agent_name].md           — system prompt
MEMORY/[agent_name].md           — persistent memory
PROJECT-SEOINDICATOR/
├── CONSTITUTION.md              — workflow rules
├── DOCUMENTATION.md             — current prod state
└── LESSONS-LEARNED/[agent].md   — project lessons
```

**Additional Context by Agent:**

| Agent | Additional Files |
|-------|------------------|
| Innovator | (none — creates new features) |
| Architect | `FEATURES/FEAT-X.md` (status: New Feature) |
| SpecSystemAnalyst | `FEATURES/FEAT-X.md`, `SPECIFICATIONS/SPEC-X.md` (status: To Break Into Tasks) |
| ProjectManager | ALL `TASKS/SPEC-X-Y.md` with status: To Prioritize, To Write Tests |
| PreTester | `AUTO-TESTS-SETUP.md`, `TASKS/SPEC-X-Y.md` (status: To Write Tests) |
| Developer | `GIT-BRANCHING-STRATEGY.md`, `TASKS/SPEC-X-Y.md` (status: To Develop) |
| LocalTester | `AUTO-TESTS-SETUP.md`, `TASKS/SPEC-X-Y.md` (To Test Locally), `TESTS/SPEC-X-Y.md` |
| DevOps | `DEPLOYMENT-SETUP.md`, `TASKS/SPEC-X-Y.md` (status: To Deploy) |
| ProdTester | `AUTO-TESTS-SETUP.md`, `TASKS/SPEC-X-Y.md` (To Test Prod), `TESTS/SPEC-X-Y.md` |
| DocSystemAnalyst | `FEATURES/FEAT-X.md`, `SPECIFICATIONS/SPEC-X.md`, `TASKS/SPEC-X-Y.md` (status: Done) |
| SecurityAuditor | (none — scans all) |
| PerformanceOptimizer | (none — scans all) |

### Agent Files
- `AGENTS/Lead.md` — Main agent (coordinator)
- `AGENTS/Innovator.md` — Feature discovery
- `AGENTS/Architect.md` — Technical specification
- `AGENTS/SpecSystemAnalyst.md` — Task breakdown
- `AGENTS/ProjectManager.md` — Prioritization
- `AGENTS/PreTester.md` — Test design
- `AGENTS/Developer.md` — Implementation
- `AGENTS/LocalTester.md` — Local QA
- `AGENTS/DevOps.md` — Deployment
- `AGENTS/ProdTester.md` — Production QA
- `AGENTS/DocSystemAnalyst.md` — Documentation
- `AGENTS/SecurityAuditor.md` — Security auditing
- `AGENTS/PerformanceOptimizer.md` — Performance optimization

## Memory
- **Persistent:** `MEMORY/[agent_name].md` — agent-specific memory (Lead, Innovator, Architect, etc.)
- **Temporary:** `MEMORY/TEMP/YYYY-MM-DD.md` — daily logs, raw notes

Write things down. Files persist; mental notes don't.

## Safety
- Private things stay private
- Destructive commands → ask first
- External actions (emails, posts) → ask first

## Group Chats
Respond when: mentioned, asked, or can add value. Stay silent otherwise.
