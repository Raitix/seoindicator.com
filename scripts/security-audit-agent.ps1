# security-audit-agent.ps1 - Spawn SecurityAuditor agent daily
# Scans project for security issues

param(
    [string]$LogPath = "C:\Users\Raitis\.openclaw\logs\security-audit-agent.log"
)

$ErrorActionPreference = "Continue"

function Write-Log {
    param([string]$Message, [string]$Level = "INFO")
    $timestamp = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
    $logLine = "[$timestamp] [$Level] $Message"
    Add-Content -Path $LogPath -Value $logLine
    if ($Level -eq "ERROR") { Write-Host $logLine -ForegroundColor Red }
    else { Write-Host $logLine }
}

Write-Log "=== SecurityAuditor Spawn Started ==="

$task = @"
You are SecurityAuditor agent. Perform a security audit of the PROJECT-SEOINDICATOR codebase.

## Instructions
1. Read your agent definition: AGENTS/SecurityAuditor.md
2. Scan the project for security vulnerabilities:
   - Authentication/authorization issues
   - SQL injection risks
   - XSS vulnerabilities
   - Exposed secrets/credentials
   - Insecure configurations
   - Dependency vulnerabilities
3. Check all High-risk items (from FEATURES/SPECIFICATIONS/TASKS) for security concerns
4. Review recent changes for security implications
5. Create findings report and escalate if critical issues found

## Scope
- Project path: PROJECT-SEOINDICATOR/
- Focus on: backend/, frontend/, and any exposed APIs
- Check: .env files for exposed secrets (report but don't commit fixes)

## Output
- Add comment to relevant High-risk items if issues found
- Can HALT deployments if critical security issues discovered
- Create fix specifications for any vulnerabilities found

## Important
- This is a read-only scan - do not modify code directly
- Report findings via comments in relevant items
- Escalate critical issues immediately
"@

try {
    $body = @{
        task = $task
        agentId = "securityauditor"
        label = "SecurityAuditor-Daily"
        runTimeoutSeconds = 3600  # 1 hour timeout
    } | ConvertTo-Json -Depth 10
    
    $response = Invoke-RestMethod -Uri "http://localhost:3777/api/sessions/spawn" -Method POST -Body $body -ContentType "application/json" -ErrorAction Stop
    
    if ($response.sessionKey) {
        Write-Log "Spawned SecurityAuditor (session: $($response.sessionKey))"
    }
    else {
        Write-Log "Failed to spawn SecurityAuditor - no session key" -Level "ERROR"
    }
}
catch {
    Write-Log "Failed to spawn SecurityAuditor: $($_.Exception.Message)" -Level "ERROR"
}

Write-Log "=== SecurityAuditor Spawn Finished ==="