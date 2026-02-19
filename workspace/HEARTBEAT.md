# HEARTBEAT.md

> **Note:** Heartbeat polling is disabled (`every: "0m"`). This file is kept as reference only.
> Automated tasks are now handled by Windows Task Scheduler (no LLM API calls).

---

## Active Background Tasks

All tasks managed by **Windows Task Scheduler**. See `scripts/setup-tasks.ps1` for configuration.

| Task | Schedule | Script | Log |
|------|----------|--------|-----|
| Session Health Check | Every 5 min | `session-health-check.ps1` | `logs/session-health.log` |
| Orchestrator | Every 5 min | `orchestrator.ps1` | `logs/orchestrator.log` |
| Security Audit | Daily 22:00 | `security-audit.ps1` | `logs/security-audit.log` |
| SecurityAuditor Agent | Daily 06:00 | `security-audit-agent.ps1` | `logs/security-audit-agent.log` |
| PerformanceOptimizer Agent | Daily 06:30 | `performance-optimizer-agent.ps1` | `logs/performance-optimizer-agent.log` |
| Memory Compaction Check | Daily 22:05 | `memory-compaction-check.ps1` | `logs/memory-compaction.log` |

### Orchestrator System

**Status-Queue + Lock Architecture:**
- Scans FEATURES/SPECIFICATIONS/TASKS for items needing work
- Creates lock files (`.locks/*.lock`) to prevent race conditions
- Enforces concurrency limits (max 3 active features, max 3 agents per status)
- Spawns agents via `sessions_spawn` API

**Lock TTL:** 30 minutes (auto-expires for crashed agents)

**Status → Agent Mapping:**
| Status | Agent |
|--------|-------|
| `New Feature` | Architect |
| `To Break Into Tasks` | SpecSystemAnalyst |
| `To Prioritize` | ProjectManager |
| `To Write Tests` | PreTester |
| `To Develop` | Developer |
| `To Test Locally` | LocalTester |
| `To Deploy` | DevOps |
| `To Test Prod` | ProdTester |
| `Done` | DocSystemAnalyst |

### Memory Compaction Threshold
- **Trigger:** Any file in `MEMORY/` or `LESSONS-LEARNED/` exceeds 500KB
- **Action:** Spawns isolated agent for intelligent compaction
- **Preserves:** Directives, rules, decisions, active tracking

---

## Task Scheduler Management

**Setup (run as Admin):**
```powershell
& "C:\Users\Raitis\.openclaw\scripts\setup-tasks.ps1"
```

**View tasks:**
```powershell
Get-ScheduledTask | Where-Object { $_.TaskName -like "*OpenClaw*" }
```

**Manually run a task:**
```powershell
Start-ScheduledTask -TaskName "OpenClaw Session Health Check"
```

---

Last updated: 2026-02-19
