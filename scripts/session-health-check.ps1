# Session Health Check Script (Windows PowerShell)
# Runs every 5 minutes via CRON
# Checks for hung sessions and restarts gateway if needed

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
    if (-not (Test-Path $SessionsFile)) {
        Write-Log "Sessions file not found"
        return $true
    }
    
    try {
        $sessions = Get-Content $SessionsFile | ConvertFrom-Json
        $now = [DateTimeOffset]::UtcNow.ToUnixTimeMilliseconds()
        $threshold = 30 * 60 * 1000  # 30 min in ms
        
        foreach ($session in $sessions.PSObject.Properties) {
            $value = $session.Value
            
            # Check abortedLastRun
            if ($value.abortedLastRun -eq $true) {
                Write-Log "Found aborted session: $($session.Name)"
                return $false
            }
            
            # Check stale updatedAt (> 30 min)
            if ($value.updatedAt) {
                $age = $now - $value.updatedAt
                if ($age -gt $threshold) {
                    Write-Log "Found stale session: $($session.Name) (age: $([math]::Round($age/60000, 1)) min)"
                    return $false
                }
            }
        }
        
        return $true
    } catch {
        Write-Log "Error parsing sessions file: $_"
        return $true
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

if (-not (Test-GatewayHealth)) {
    Write-Log "Gateway not responding"
    Restart-Gateway
} elseif (-not (Test-SessionsHealth)) {
    Write-Log "Hung sessions detected"
    Restart-Gateway
} else {
    Write-Log "Health check passed"
}
