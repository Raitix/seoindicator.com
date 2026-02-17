# HEARTBEAT.md

> **Note:** Heartbeat polling is disabled (`every: "0m"`). This file is kept as reference only.
> Automated tasks are now handled by Windows Task Scheduler (no LLM API calls).

---

## Active Background Tasks

All tasks managed by **Windows Task Scheduler**. See `scripts/setup-tasks.ps1` for configuration.

| Task | Schedule | Script | Log |
|------|----------|--------|-----|
| Session Health Check | Every 5 min | `session-health-check.ps1` | `logs/session-health.log` |
| Security Audit | Daily 22:00 | `security-audit.ps1` | `logs/security-audit.log` |
| Memory Compaction Check | Daily 22:05 | `memory-compaction-check.ps1` | `logs/memory-compaction.log` |

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

Last updated: 2026-02-18
