# HEARTBEAT.md

## Periodic Security Checks
Run security audits regularly:
```bash
openclaw security audit --deep
openclaw security audit --fix
```

## Session Health Check
Automated cron job runs every 5 minutes to check for hung sessions:
- Checks `abortedLastRun` flag
- Checks `updatedAt` timestamp (> 30 min = stale)
- Auto-restarts gateway if issues detected

Manual check:
```bash
openclaw sessions --active 60
```
