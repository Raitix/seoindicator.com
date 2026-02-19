# orchestrator.ps1 - Agent Instance Orchestrator
# Scans project for work items and spawns agents based on status workflow
# Run every 5 minutes via Task Scheduler

param(
    [string]$ProjectPath = "C:\Users\Raitis\.openclaw\workspace\PROJECT-SEOINDICATOR",
    [string]$WorkspacePath = "C:\Users\Raitis\.openclaw\workspace",
    [string]$LogPath = "C:\Users\Raitis\.openclaw\logs\orchestrator.log",
    [string]$LastRunPath = "C:\Users\Raitis\.openclaw\logs\agent-last-run.json",
    [int]$LockTTLMinutes = 30,
    [int]$MaxActiveFeatures = 3,
    [int]$MaxAgentsPerStatus = 3,
    [int]$DailyAgentIntervalHours = 24,
    [int]$InnovatorIntervalHours = 6,
    [int]$MaxNewFeatures = 10
)

$ErrorActionPreference = "Continue"

# ============================================
# LOGGING
# ============================================

function Write-Log {
    param([string]$Message, [string]$Level = "INFO")
    $timestamp = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
    $logLine = "[$timestamp] [$Level] $Message"
    Add-Content -Path $LogPath -Value $logLine
    if ($Level -eq "ERROR") { Write-Host $logLine -ForegroundColor Red }
    elseif ($Level -eq "WARN") { Write-Host $logLine -ForegroundColor Yellow }
    elseif ($Level -eq "DEBUG") { } # Silent
    else { Write-Host $logLine }
}

# ============================================
# PARSE FUNCTIONS
# ============================================

function Get-MarkdownMetadata {
    param([string]$FilePath)
    
    if (-not (Test-Path $FilePath)) { return $null }
    
    $content = Get-Content $FilePath -Raw -ErrorAction SilentlyContinue
    if (-not $content) { return $null }
    
    $status = ""
    $priority = "Medium"
    $risk = "Low"
    
    if ($content -match '##\s*STATUS\s*\n`?([^`\n]+)`?') {
        $status = $Matches[1].Trim().Replace('`', '')
    }
    if ($content -match '##\s*PRIORITY\s*\n`?([^`\n]+)`?') {
        $priority = $Matches[1].Trim().Replace('`', '')
    }
    if ($content -match '##\s*RISK\s*\n`?([^`\n]+)`?') {
        $risk = $Matches[1].Trim().Replace('`', '')
    }
    
    return @{
        Status = $status
        Priority = $priority
        Risk = $risk
    }
}

function Get-Functionalities {
    param([string]$Folder, [string]$Type)
    
    $items = @()
    $folderPath = Join-Path $ProjectPath $Folder
    
    if (-not (Test-Path $folderPath)) { return $items }
    
    Get-ChildItem -Path $folderPath -Filter "*.md" | Where-Object { $_.Name -notlike ".*" } | ForEach-Object {
        $id = $_.BaseName
        $meta = Get-MarkdownMetadata $_.FullName
        if ($meta -and $meta.Status) {
            $items += [PSCustomObject]@{
                Id = $id
                Type = $Type
                Status = $meta.Status
                Priority = $meta.Priority
                Risk = $meta.Risk
                Path = $_.FullName
            }
        }
    }
    
    return $items
}

# ============================================
# LOCK MANAGEMENT
# ============================================

function Get-LockPath {
    param([string]$Type, [string]$Id)
    
    $folder = switch ($Type) {
        "feature" { "FEATURES" }
        "specification" { "SPECIFICATIONS" }
        "task" { "TASKS" }
    }
    
    return Join-Path $ProjectPath "$folder\.locks\$Id.lock"
}

function Get-Lock {
    param([string]$Type, [string]$Id)
    
    $lockPath = Get-LockPath -Type $Type -Id $Id
    
    if (-not (Test-Path $lockPath)) { return $null }
    
    try {
        $lock = Get-Content $lockPath -Raw | ConvertFrom-Json
        $lockedAt = [DateTime]::Parse($lock.lockedAt)
        $expiresAt = $lockedAt.AddMinutes($LockTTLMinutes)
        
        # Check if lock is expired
        if ([DateTime]::UtcNow -gt $expiresAt) {
            Remove-Item $lockPath -Force
            Write-Log "Lock expired and removed: $Type/$Id" -Level "DEBUG"
            return $null
        }
        
        return $lock
    }
    catch {
        Remove-Item $lockPath -Force
        return $null
    }
}

function New-Lock {
    param([string]$Type, [string]$Id, [string]$Agent, [string]$SessionId)
    
    $lockPath = Get-LockPath -Type $Type -Id $Id
    $lock = @{
        agent = $Agent
        sessionId = $SessionId
        lockedAt = [DateTime]::UtcNow.ToString("o")
    }
    
    $lock | ConvertTo-Json | Set-Content $lockPath
    Write-Log "Lock created: $Type/$Id by $Agent ($SessionId)"
}

# ============================================
# STATUS TO AGENT MAPPING
# ============================================

# Define which agent should work on which status
$StatusAgentMap = @{
    "New Feature" = "Architect"
    "To Break Into Tasks" = "SpecSystemAnalyst"
    "To Prioritize" = "ProjectManager"
    "To Write Tests" = "PreTester"
    "To Develop" = "Developer"
    "To Test Locally" = "LocalTester"
    "To Deploy" = "DevOps"
    "To Test Prod" = "ProdTester"
    "Done" = "DocSystemAnalyst"
}

# Statuses that indicate work in progress (agent already working)
$InProgressStatuses = @(
    "Specification In Progress",
    "Breaking Into Tasks",
    "Prioritization In Progress",
    "Tests Coverage In Progress",
    "In Development",
    "Testing Locally",
    "In Deployment",
    "Testing Prod"
)

# Terminal statuses (no agent needed)
$TerminalStatuses = @("New Feature", "Done", "Cancelled")

# ============================================
# CONCURRENCY CHECKS
# ============================================

function Get-ActiveFeatures {
    param([array]$AllFeatures)
    
    # Active = NOT in New Feature, Done, or Cancelled
    return @($AllFeatures | Where-Object { 
        $_.Status -notin $TerminalStatuses 
    })
}

function Get-StatusCounts {
    param([array]$AllItems)
    
    $counts = @{}
    foreach ($item in $AllItems) {
        $status = $item.Status
        if (-not $counts[$status]) { $counts[$status] = 0 }
        $counts[$status]++
    }
    return $counts
}

function Test-CanSpawn {
    param(
        [array]$ActiveFeatures,
        [array]$ItemsWithTargetStatus,
        [string]$TargetStatus
    )
    
    # Check max active features
    if ($ActiveFeatures.Count -ge $MaxActiveFeatures) {
        return @{ CanSpawn = $false; Reason = "Max active features reached ($($ActiveFeatures.Count)/$MaxActiveFeatures)" }
    }
    
    # Check max agents per status
    $inProgressCount = @($ItemsWithTargetStatus | Where-Object { $_.Status -eq $TargetStatus }).Count
    if ($inProgressCount -ge $MaxAgentsPerStatus) {
        return @{ CanSpawn = $false; Reason = "Max agents per status reached for '$TargetStatus' ($inProgressCount/$MaxAgentsPerStatus)" }
    }
    
    return @{ CanSpawn = $true; Reason = "OK" }
}

# ============================================
# AGENT SPAWNING
# ============================================

function Spawn-Agent {
    param(
        [string]$AgentName,
        [string]$ItemType,
        [string]$ItemId,
        [string]$ItemStatus
    )
    
    $task = @"
You are $AgentName agent. Your task is to work on $ItemType '$ItemId'.

## Instructions
1. Read your agent definition: AGENTS/$AgentName.md
2. Read the item file and understand the current state
3. Follow your role in the workflow (see CONSTITUTION.md)
4. Update the item status as you progress
5. Add comments to the item with your actions and findings
6. Commit changes when done

## Item Details
- Type: $ItemType
- ID: $ItemId
- Current Status: $ItemStatus
- File: $ItemType`.s/$ItemId.md (in PROJECT-SEOINDICATOR)

## Important
- Work ONLY on this item
- Create a lock file in the .locks folder before starting
- Update status after each action
- Leave detailed comments for the next agent
"@

    # Use OpenClaw CLI to spawn agent session
    $agentId = $AgentName.ToLower()
    
    try {
        # Spawn via sessions_spawn - we'll use REST API
        $body = @{
            task = $task
            agentId = $agentId
            label = "$AgentName-$ItemId"
            runTimeoutSeconds = 1800  # 30 min timeout
        } | ConvertTo-Json -Depth 10
        
        $response = Invoke-RestMethod -Uri "http://localhost:3777/api/sessions/spawn" -Method POST -Body $body -ContentType "application/json" -ErrorAction Stop
        
        if ($response.sessionKey) {
            # Create lock
            New-Lock -Type $ItemType -Id $ItemId -Agent $AgentName -SessionId $response.sessionKey
            Write-Log "Spawned $AgentName for $ItemType/$ItemId (session: $($response.sessionKey))"
            return $response.sessionKey
        }
        else {
            Write-Log "Failed to spawn $AgentName for $ItemType/$ItemId - no session key returned" -Level "ERROR"
            return $null
        }
    }
    catch {
        Write-Log "Failed to spawn $AgentName for $ItemType/$ItemId : $($_.Exception.Message)" -Level "ERROR"
        return $null
    }
}

# ============================================
# MAIN ORCHESTRATION
# ============================================

Write-Log "=== Orchestrator Started ==="

# Scan all functionalities
$features = Get-Functionalities -Folder "FEATURES" -Type "feature"
$specifications = Get-Functionalities -Folder "SPECIFICATIONS" -Type "specification"
$tasks = Get-Functionalities -Folder "TASKS" -Type "task"

$allItems = $features + $specifications + $tasks

Write-Log "Scanned: $($features.Count) features, $($specifications.Count) specs, $($tasks.Count) tasks"

# Get active features (for concurrency check)
$activeFeatures = Get-ActiveFeatures -AllFeatures $features
Write-Log "Active features: $($activeFeatures.Count)/$MaxActiveFeatures"

# Find items needing work
$spawnedCount = 0

foreach ($status in $StatusAgentMap.Keys) {
    $agent = $StatusAgentMap[$status]
    
    # Find items with this status that need work
    $itemsNeedingWork = @($allItems | Where-Object { $_.Status -eq $status })
    
    if ($itemsNeedingWork.Count -eq 0) { continue }
    
    Write-Log "Found $($itemsNeedingWork.Count) items with status '$status' (needs $agent)"
    
    foreach ($item in $itemsNeedingWork) {
        # Check if already locked
        $lock = Get-Lock -Type $item.Type -Id $item.Id
        if ($lock) {
            Write-Log "Item $($item.Type)/$($item.Id) is locked by $($lock.agent)" -Level "DEBUG"
            continue
        }
        
        # Check concurrency
        $check = Test-CanSpawn -ActiveFeatures $activeFeatures -ItemsWithTargetStatus $allItems -TargetStatus $status
        if (-not $check.CanSpawn) {
            Write-Log "Cannot spawn for $($item.Type)/$($item.Id): $($check.Reason)" -Level "WARN"
            break  # Stop spawning for this status
        }
        
        # Spawn agent
        $sessionId = Spawn-Agent -AgentName $agent -ItemType $item.Type -ItemId $item.Id -ItemStatus $item.Status
        if ($sessionId) {
            $spawnedCount++
            # Update active count for next iteration
            if ($item.Type -eq "feature") {
                $activeFeatures = @($activeFeatures) + $item
            }
        }
        
        # Only spawn one agent per status per run to avoid race conditions
        break
    }
    
    # Global limit check
    if ($activeFeatures.Count -ge $MaxActiveFeatures) {
        Write-Log "Reached max active features, stopping spawn cycle"
        break
    }
}

Write-Log "Orchestration complete. Spawned: $spawnedCount agent(s)"
Write-Log "=== Orchestrator Finished ==="

# ============================================
# DAILY AGENTS (SecurityAuditor, PerformanceOptimizer)
# ============================================

function Get-LastRunData {
    if (-not (Test-Path $LastRunPath)) {
        return @{ securityAuditor = $null; performanceOptimizer = $null; innovator = $null }
    }
    try {
        return Get-Content $LastRunPath -Raw | ConvertFrom-Json
    }
    catch {
        return @{ securityAuditor = $null; performanceOptimizer = $null; innovator = $null }
    }
}

function Set-LastRunData {
    param([hashtable]$Data)
    $Data | ConvertTo-Json | Set-Content $LastRunPath
}

function Test-ShouldRunDailyAgent {
    param([string]$LastRun)
    
    if (-not $LastRun) { return $true }
    
    try {
        $lastRunTime = [DateTime]::Parse($LastRun)
        $hoursSince = ([DateTime]::UtcNow - $lastRunTime).TotalHours
        return $hoursSince -ge $DailyAgentIntervalHours
    }
    catch {
        return $true
    }
}

function Spawn-DailyAgent {
    param(
        [string]$AgentName,
        [string]$AgentId,
        [string]$TaskDescription,
        [string]$Label
    )
    
    try {
        $body = @{
            task = $TaskDescription
            agentId = $AgentId
            label = $Label
            runTimeoutSeconds = 3600  # 1 hour
        } | ConvertTo-Json -Depth 10
        
        $response = Invoke-RestMethod -Uri "http://localhost:3777/api/sessions/spawn" -Method POST -Body $body -ContentType "application/json" -ErrorAction Stop
        
        if ($response.sessionKey) {
            Write-Log "Spawned $AgentName (session: $($response.sessionKey))"
            return $true
        }
    }
    catch {
        Write-Log "Failed to spawn $AgentName : $($_.Exception.Message)" -Level "ERROR"
    }
    return $false
}

# Check and spawn daily agents
$lastRunData = Get-LastRunData
$lastRunHash = @{
    securityAuditor = $lastRunData.securityAuditor
    performanceOptimizer = $lastRunData.performanceOptimizer
}

# SecurityAuditor
if (Test-ShouldRunDailyAgent -LastRun $lastRunHash.securityAuditor) {
    Write-Log "SecurityAuditor needs to run (last: $($lastRunHash.securityAuditor))"
    
    $securityTask = @"
You are SecurityAuditor agent. Perform a security audit of the PROJECT-SEOINDICATOR codebase.

## Instructions
1. Read your agent definition: AGENTS/SecurityAuditor.md
2. Scan the project for security vulnerabilities
3. Check all High-risk items for security concerns
4. Create findings report and escalate if critical issues found

## Scope
- Project path: PROJECT-SEOINDICATOR/
- Focus on: backend/, frontend/, exposed APIs
- This is a read-only scan - do not modify code directly
"@
    
    if (Spawn-DailyAgent -AgentName "SecurityAuditor" -AgentId "securityauditor" -TaskDescription $securityTask -Label "SecurityAuditor-Daily") {
        $lastRunHash.securityAuditor = [DateTime]::UtcNow.ToString("o")
    }
}

# PerformanceOptimizer
if (Test-ShouldRunDailyAgent -LastRun $lastRunHash.performanceOptimizer) {
    Write-Log "PerformanceOptimizer needs to run (last: $($lastRunHash.performanceOptimizer))"
    
    $performanceTask = @"
You are PerformanceOptimizer agent. Analyze the PROJECT-SEOINDICATOR project for performance issues.

## Instructions
1. Read your agent definition: AGENTS/PerformanceOptimizer.md
2. Analyze performance across database, APIs, frontend
3. Review Google Analytics data for user-impacting issues
4. Create optimization specifications for identified issues

## Scope
- Project path: PROJECT-SEOINDICATOR/
- Check: backend/database/, API endpoints, frontend components
- This is analysis only - do not modify code directly
"@
    
    if (Spawn-DailyAgent -AgentName "PerformanceOptimizer" -AgentId "performanceoptimizer" -TaskDescription $performanceTask -Label "PerformanceOptimizer-Daily") {
        $lastRunHash.performanceOptimizer = [DateTime]::UtcNow.ToString("o")
    }
}

# ============================================
# INNOVATOR - Autonomous Feature Generation
# ============================================

# Initialize innovator in lastRunHash if not present
if (-not $lastRunHash.innovator) { $lastRunHash.innovator = $null }

# Check if Innovator should run
$shouldRunInnovator = $false
$newFeaturesCount = @($features | Where-Object { $_.Status -eq "New Feature" }).Count

# Check time interval
if ($lastRunHash.innovator) {
    try {
        $lastInnovatorRun = [DateTime]::Parse($lastRunHash.innovator)
        $hoursSince = ([DateTime]::UtcNow - $lastInnovatorRun).TotalHours
        if ($hoursSince -ge $InnovatorIntervalHours) {
            $shouldRunInnovator = $true
        }
    }
    catch {
        $shouldRunInnovator = $true
    }
}
else {
    $shouldRunInnovator = $true
}

# Check feature limit
if ($newFeaturesCount -ge $MaxNewFeatures) {
    Write-Log "Innovator skipped: $newFeaturesCount New Features (limit: $MaxNewFeatures)"
    $shouldRunInnovator = $false
}

# Spawn Innovator if conditions met
if ($shouldRunInnovator) {
    Write-Log "Innovator needs to run (last: $($lastRunHash.innovator), New Features: $newFeaturesCount)"
    
    $innovatorTask = @"
You are Innovator agent. Your role is to discover and propose new features for SEOIndicator.

## Instructions
1. Read your agent definition: AGENTS/Innovator.md
2. Analyze the project DOCUMENTATION.md to understand current functionality
3. Identify gaps, improvements, or new feature opportunities
4. Consider:
   - User needs (check Google Analytics for user behavior patterns)
   - Competitor features
   - Technical debt that could become features
   - Performance optimizations that add user value
5. Create ONE new feature in FEATURES/ folder with:
   - Proper FEAT-X naming (next sequential number)
   - Status: New Feature
   - Priority: based on business impact
   - Risk: based on technical complexity
   - Complete business impact analysis
6. Commit your changes

## Important
- Only create features that provide real user value
- Be specific about business impact
- Consider implementation complexity
- Do NOT create duplicate features of existing functionality
"@
    
    if (Spawn-DailyAgent -AgentName "Innovator" -AgentId "innovator" -TaskDescription $innovatorTask -Label "Innovator-Autonomous") {
        $lastRunHash.innovator = [DateTime]::UtcNow.ToString("o")
    }
}

# Save updated last run data
Set-LastRunData -Data $lastRunHash
