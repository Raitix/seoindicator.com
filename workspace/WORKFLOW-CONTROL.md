# WORKFLOW-CONTROL.md

> **Workflow Master Switch**
> This file controls the automated workflow execution.
> Set `workflowEnabled` to `false` to pause ALL automated agents.

---

## WORKFLOW STATUS

```yaml
workflowEnabled: false
pausedAt: "2026-02-20 10:21:46"
pausedBy: "Raitis"
pauseReason: "Testing pause functionality"
```

---

## How It Works

### For the UI
- Read this file to show the toggle state
- Update the `workflowEnabled` value (true/false)
- Optionally set `pausedAt`, `pausedBy`, `pauseReason` when deactivating

### For Scheduler Scripts
All Task Scheduler scripts check `workflowEnabled` before executing:
- `orchestrator.ps1` - Agent spawning
- `security-audit.ps1` - Security scans
- `memory-compaction-check.ps1` - Memory cleanup
- `session-health-check.ps1` - Health monitoring (always runs)

### For Agents
Each agent should check this flag at startup:
```markdown
## Pre-Flight Check
1. Read WORKFLOW-CONTROL.md
2. If `workflowEnabled: false`, log and exit immediately
3. Otherwise, proceed with duties
```

---

## Status Indicators

| workflowEnabled | Meaning |
|-----------------|---------|
| `true` | Workflow active - all agents run normally |
| `false` | Workflow paused - all agents skip duties |

---

## Last Updated
2026-02-20
