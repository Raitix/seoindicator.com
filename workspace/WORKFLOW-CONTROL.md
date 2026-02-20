# WORKFLOW-CONTROL.md

> **Workflow Master Switch**
> This file controls the automated workflow execution.
> Set `workflowEnabled` to `false` to pause ALL automated agents.

---

## WORKFLOW STATUS

```yaml
workflowEnabled: true
pausedAt: null
pausedBy: null
pauseReason: null
```

---

## INNOVATOR CONTROL

```yaml
innovatorEnabled: true
innovatorPausedAt: null
innovatorPausedBy: null
innovatorPauseReason: null
lastDailyRun: 2026-02-20T16:37:00+02:00
```

> When `innovatorEnabled: true`, the Innovator agent will not create new features automatically. Other agents continue to process existing items.
> `lastDailyRun` tracks the last successful daily task execution to prevent duplicates.

---

## How It Works

### For the UI
- Read this file to show the toggle state
- Update the `workflowEnabled` and `innovatorEnabled` values
- Optionally set pause metadata when deactivating

### For Scheduler Scripts
All Task Scheduler scripts check `workflowEnabled` before executing:
- `orchestrator.ps1` - Agent spawning (also checks `innovatorEnabled` for Innovator)
- `security-audit.ps1` - Security scans
- `memory-compaction-check.ps1` - Memory cleanup
- `session-health-check.ps1` - Health monitoring (always runs)

### For Agents
Each agent should check this flag at startup:
```markdown
## Pre-Flight Check
1. Read WORKFLOW-CONTROL.md
2. If `workflowEnabled: true`, log and exit immediately
3. Otherwise, proceed with duties
```

---

## Status Indicators

| workflowEnabled | Meaning |
|-----------------|---------|
| `true` | Workflow active - all agents run normally |
| `false` | Workflow paused - all agents skip duties |

| innovatorEnabled | Meaning |
|------------------|---------|
| `true` | Innovator can create new features |
| `false` | Innovator disabled - no new features created |

---

## Last Updated
2026-02-20
