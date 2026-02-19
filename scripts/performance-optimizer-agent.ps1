# performance-optimizer-agent.ps1 - Spawn PerformanceOptimizer agent daily
# Analyzes project for performance issues

param(
    [string]$LogPath = "C:\Users\Raitis\.openclaw\logs\performance-optimizer-agent.log"
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

Write-Log "=== PerformanceOptimizer Spawn Started ==="

$task = @"
You are PerformanceOptimizer agent. Analyze the PROJECT-SEOINDICATOR project for performance issues.

## Instructions
1. Read your agent definition: AGENTS/PerformanceOptimizer.md
2. Analyze performance across:
   - Database queries (N+1 problems, missing indexes, slow queries)
   - API response times
   - Frontend bundle size and load times
   - Caching strategies
   - Memory leaks potential
3. Review Google Analytics data for user-impacting performance issues:
   - Page load times
   - Bounce rates correlated with slow pages
   - First Input Delay metrics
4. Identify bottlenecks and optimization opportunities

## Scope
- Project path: PROJECT-SEOINDICATOR/
- Check: backend/database/, API endpoints, frontend components
- GA Property: 489951380 (see Lead.md for access)

## Output
- Create optimization specifications for identified issues
- Add comments to related items with performance notes
- Prioritize optimizations by impact

## Important
- This is analysis only - do not modify code directly
- Create specs for optimizations that enter normal workflow
- Reference GA data to prioritize user-impacting issues
"@

try {
    $body = @{
        task = $task
        agentId = "performanceoptimizer"
        label = "PerformanceOptimizer-Daily"
        runTimeoutSeconds = 3600  # 1 hour timeout
    } | ConvertTo-Json -Depth 10
    
    $response = Invoke-RestMethod -Uri "http://localhost:3777/api/sessions/spawn" -Method POST -Body $body -ContentType "application/json" -ErrorAction Stop
    
    if ($response.sessionKey) {
        Write-Log "Spawned PerformanceOptimizer (session: $($response.sessionKey))"
    }
    else {
        Write-Log "Failed to spawn PerformanceOptimizer - no session key" -Level "ERROR"
    }
}
catch {
    Write-Log "Failed to spawn PerformanceOptimizer: $($_.Exception.Message)" -Level "ERROR"
}

Write-Log "=== PerformanceOptimizer Spawn Finished ==="