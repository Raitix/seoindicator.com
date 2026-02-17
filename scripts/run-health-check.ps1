# Session Health Check Runner (Windows PowerShell)
# Runs in background, checks every 5 minutes

param(
    [int]$IntervalMinutes = 5
)

$scriptPath = "$PSScriptRoot\session-health-check.ps1"

Write-Host "Session Health Check Runner started (interval: $IntervalMinutes min)"

while ($true) {
    # Run the health check
    & $scriptPath
    
    # Wait for next interval
    Start-Sleep -Seconds ($IntervalMinutes * 60)
}
