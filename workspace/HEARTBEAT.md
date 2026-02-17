# HEARTBEAT.md

> **Note:** Heartbeat polling is disabled (`every: "0m"`). This file is kept as reference only.
> Automated tasks are now handled by shell scripts (no LLM API calls).

---

## Active Background Tasks

### Session Health Check
- **Script:** `~/.openclaw/scripts/run-health-check.ps1`
- **Interval:** Every 5 minutes
- **Action:** Checks for hung sessions, restarts gateway if needed
- **Log:** `~/.openclaw/logs/session-health.log`

### Security Audit (Manual)
```bash
openclaw security audit --deep
openclaw security audit --fix
```

Or run the script:
```powershell
~/.openclaw/scripts/security-audit.ps1
```

---

## How to Start/Stop Background Tasks

### Start Session Health Check
```powershell
Start-Process -FilePath "powershell.exe" -ArgumentList "-NoProfile -ExecutionPolicy Bypass -File `"C:\Users\Raitis\.openclaw\scripts\run-health-check.ps1`"" -WindowStyle Hidden
```

### Stop Session Health Check
```powershell
Get-Process -Name powershell | Where-Object { $_.MainWindowTitle -eq "" } | Stop-Process
```

---

## CRON Jobs

No LLM-based CRON jobs configured. All periodic tasks use shell scripts.

---

Last updated: 2026-02-18
