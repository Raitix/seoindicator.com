# HEARTBEAT.md

> **Note:** Heartbeat polling is disabled. This file is kept as reference only.
> Automated tasks are now handled by CRON jobs.

---

## Active CRON Jobs

| Job | Schedule | Purpose |
|-----|----------|---------|
| session-health-check | Every 5 min | Check for hung sessions, auto-restart gateway |

---

## Manual Commands

**Security audits** (run manually when needed):
```bash
openclaw security audit --deep
openclaw security audit --fix
```

**Session check**:
```bash
openclaw sessions --active 60
```

---

Last updated: 2026-02-18
