# Session Health Check Script (Windows PowerShell)
# Runs every 5 minutes via Task Scheduler
# Checks for hung sessions and restarts gateway ONLY if truly needed

param(
    [string]$GatewayUrl = "http://localhost:18789",
    [string]$GatewayToken = "12d428196a8bd886132da350e6c78c3535b124379c98aaeb",
    [string]$SessionsFile = "$env:USERPROFILE\.openclaw\agents\main\sessions\sessions.json",
    [string]$LogFile = "$env:USERPROFILE\.openclaw\logs\session-health.log"
)

# Create logs directory if not exists
$logDir = Split-Path $LogFile -Parent
if (-not (Test-Path $logDir)) {
    New-Item -ItemType Directory -Path $logDir -Force | Out-Null
}

function Write-Log {
    param([string]$Message)
    $timestamp = Get-Date -Format "o"
    "[$timestamp] $Message" | Add-Content $LogFile
}

function Test-GatewayHealth {
    try {
        $response = Invoke-WebRequest -Uri "$GatewayUrl/health" -Method Get -TimeoutSec 5 -UseBasicParsing -ErrorAction Stop
        return $response.StatusCode -eq 200
    } catch {
        return $false
    }
}

function Test-SessionsHealth {
    # Only check for CRITICAL issues - NOT stale updatedAt (idle sessions are normal)
    if (-not (Test-Path $SessionsFile)) {
        Write-Log "Sessions file not found - this is normal for fresh install"
        return $true
    }
    
    try {
        $sessions = Get-Content $SessionsFile | ConvertFrom-Json
        
        foreach ($session in $sessions.PSObject.Properties) {
            $value = $session.Value
            
            # Check abortedLastRun - this is the REAL indicator of a hung session
            if ($value.abortedLastRun -eq $true) {
                Write-Log "Found aborted session: $($session.Name)"
                return $false
            }
        }
        
        return $true
    } catch {
        Write-Log "Error parsing sessions file: $_"
        return $true  # Don't restart on parse errors
    }
}

function Restart-Gateway {
    Write-Log "Restarting gateway due to health check failure"
    
    try {
        # Use openclaw CLI to restart
        $result = & openclaw gateway restart 2>&1
        Write-Log "Gateway restart result: $result"
    } catch {
        Write-Log "Restart failed: $_"
    }
}

# Main logic
Write-Log "Starting health check"

$needsAction = $false

if (-not (Test-GatewayHealth)) {
    Write-Log "Gateway not responding - initiating restart"
    Restart-Gateway
    $needsAction = $true
} elseif (-not (Test-SessionsHealth)) {
    Write-Log "Hung sessions detected (abortedLastRun=true) - initiating restart"
    Restart-Gateway
    $needsAction = $true
}

if (-not $needsAction) {
    Write-Log "Health check passed - all systems normal"
}
