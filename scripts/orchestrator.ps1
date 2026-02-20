# orchestrator.ps1 - Agent Instance Orchestrator
# Scans project for work items and spawns agents based on status workflow
# Run every 5 minutes via Task Scheduler

param(
    [string]$ProjectPath = "C:\Users\Raitis\.openclaw\workspace\PROJECT-SEOINDICATOR",
    [string]$WorkspacePath = "C:\Users\Raitis\.openclaw\workspace",
    [string]$LogPath = "C:\Users\Raitis\.openclaw\logs\orchestrator.log",
    [string]$LastRunPath = "C:\Users\Raitis\.openclaw\logs\agent-last-run.json",
    [int]$LockTTLMinutes = 30,
    [int]$DailyAgentIntervalHours = 24,
    [int]$InnovatorIntervalMinutes = 5,
    [int]$MaxNewFeatures = 10
)

$ErrorActionPreference = "Continue"

# ============================================
# WORKFLOW CONTROL CHECK
# ============================================

$scriptDir = Split-Path $MyInvocation.MyCommand.Path -Parent
. "$scriptDir\workflow-helpers.ps1"

if (-not (Test-WorkflowEnabled -LogPath $LogPath -ScriptName "Orchestrator")) {
    Write-Host "Workflow is paused. Exiting orchestrator."
    exit 0
}

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

# Normalize ID: FEAT-001 -> FEAT-1, SPEC-003-04 -> SPEC-3-4
function Normalize-Id {
    param([string]$Id)
    
    $result = $Id
    $result = [regex]::Replace($result, 'FEAT-0*(\d+)', 'FEAT-$1', 'IgnoreCase')
    $result = [regex]::Replace($result, 'SPEC-0*(\d+)-0*(\d+)', 'SPEC-$1-$2', 'IgnoreCase')
    $result = [regex]::Replace($result, 'SPEC-0*(\d+)', 'SPEC-$1', 'IgnoreCase')
    
    return $result
}

function Get-Functionalities {
    param([string]$Folder, [string]$Type)
    
    $items = @()
    $folderPath = Join-Path $ProjectPath $Folder
    
    if (-not (Test-Path $folderPath)) { return $items }
    
    Get-ChildItem -Path $folderPath -Filter "*.md" | Where-Object { $_.Name -notlike ".*" } | ForEach-Object {
        $id = Normalize-Id -Id $_.BaseName
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
    param([string]$Type, [string]$Id, [string]$Agent, [string]$SessionId, [string]$Status)
    
    $lockPath = Get-LockPath -Type $Type -Id $Id
    $lock = @{
        agent = $Agent
        sessionId = $SessionId
        status = $Status
        lockedAt = [DateTime]::UtcNow.ToString("o")
    }
    
    $lock | ConvertTo-Json | Set-Content $lockPath
    Write-Log "Lock created: $Type/$Id by $Agent ($SessionId) for status '$Status'"
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

# ============================================
# CONCURRENCY CHECKS
# ============================================

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
        [string]$TargetStatus
    )
    
    # No concurrency limits - spawn freely
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
        [string]$ItemStatus,
        [string]$Status
    )
    
    # Agent-specific instructions
    $specificInstructions = ""
    if ($AgentName -eq "Architect") {
        $specificInstructions = @"

## Architect-Specific Tasks
1. Read the feature file: PROJECT-SEOINDICATOR/FEATURES/$ItemId.md
2. Create specification: PROJECT-SEOINDICATOR/SPECIFICATIONS/SPEC-$($ItemId -replace 'FEAT-','').md
3. Update feature status to: `To Break Into Tasks`
4. The specification file MUST be created in SPECIFICATIONS/ folder (not docs/)
5. After creating spec, update the feature file's STATUS section
6. Commit both files to git
"@
    }
    
    $task = @"
You are $AgentName agent. Your task is to work on $ItemType '$ItemId'.

## Instructions
1. Read your agent definition: AGENTS/$AgentName.md
2. Read the item file and understand the current state
3. Follow your role in the workflow (see CONSTITUTION.md)
4. Update the item status as you progress
5. Add comments to the item with your actions and findings
6. Commit changes when done
$specificInstructions

## Item Details
- Type: $ItemType
- ID: $ItemId
- Current Status: $ItemStatus
- File: PROJECT-SEOINDICATOR/$ItemType/$ItemId.md

## Important
- Work ONLY on this item
- Create a lock file in the .locks folder before starting
- Update status after each action
- Leave detailed comments for the next agent
- Project root: C:\Users\Raitis\.openclaw\workspace\PROJECT-SEOINDICATOR
"@

    $agentId = $AgentName.ToLower()
    $sessionLabel = "$AgentName-$ItemId"
    
    try {
        # Write task to temp file (CLI can't handle multi-line args)
        $taskFile = "C:\Users\Raitis\.openclaw\logs\task-$AgentName-$ItemId.txt"
        $task | Set-Content $taskFile -Encoding UTF8
        
        # Use OpenClaw CLI via cmd.exe with proper escaping
        $cliPath = "C:\nvm4w\nodejs\openclaw.cmd"
        $shortTask = "Process $ItemType $ItemId. Read task from: $taskFile"
        
        # Generate unique session ID for isolation (each task gets fresh context)
        $timestamp = Get-Date -Format "yyyyMMdd-HHmmss"
        $uniqueSessionId = "$($agentId)-$($ItemId -replace '[^a-zA-Z0-9-]', '')-$timestamp"
        
        # Build command string with unique session-id for isolation
        $cmdArgs = "agent --agent $agentId --session-id `"$uniqueSessionId`" -m `"$shortTask`" --timeout 1800"
        
        $process = Start-Process -FilePath "cmd.exe" -ArgumentList "/c", "$cliPath $cmdArgs" -WindowStyle Hidden -PassThru -RedirectStandardOutput "C:\Users\Raitis\.openclaw\logs\spawn-$AgentName-out.log" -RedirectStandardError "C:\Users\Raitis\.openclaw\logs\spawn-$AgentName-err.log"
        
        # Create lock with unique session ID
        New-Lock -Type $ItemType -Id $ItemId -Agent $AgentName -SessionId $uniqueSessionId -Status $Status
        Write-Log "Spawned $AgentName for $ItemType/$ItemId via CLI (Session: $uniqueSessionId, PID: $($process.Id))"
        
        # Track this agent in active-agents.json (append to existing)
        $agentsFile = "C:\Users\Raitis\.openclaw\logs\active-agents.json"
        try {
            $existing = Get-Content $agentsFile -Raw | ConvertFrom-Json
            if ($existing -is [System.Array]) {
                $agents = @($existing)
            } elseif ($existing.id) {
                $agents = @($existing)
            } else {
                $agents = @()
            }
        } catch {
            $agents = @()
        }
        
        # Check if agent already in list
        $existingAgent = $agents | Where-Object { $_.id -eq $agentId }
        if (-not $existingAgent) {
            $normalizedItemId = Normalize-Id -Id $ItemId
            $newAgent = @{
                id = $agentId
                name = $AgentName
                label = $normalizedItemId
                status = "running"
                pid = $process.Id
                sessionId = $uniqueSessionId
                updatedAt = [DateTime]::UtcNow.ToString("o")
            }
            $agents = @($agents) + @($newAgent)
            # Write UTF-8 without BOM
            $json = ConvertTo-Json @($agents) -Depth 3 -Compress
            $utf8NoBom = New-Object System.Text.UTF8Encoding $false
            [System.IO.File]::WriteAllText($agentsFile, $json, $utf8NoBom)
        }
        
        return $uniqueSessionId
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
        
        # Check max agents per status
        $check = Test-CanSpawn -TargetStatus $status
        if (-not $check.CanSpawn) {
            Write-Log "Cannot spawn for $($item.Type)/$($item.Id): $($check.Reason)" -Level "WARN"
            continue  # Try next item instead of breaking
        }
        
        # Spawn agent
        $sessionId = Spawn-Agent -AgentName $agent -ItemType $item.Type -ItemId $item.Id -ItemStatus $item.Status -Status $status
        if ($sessionId) {
            $spawnedCount++
        }
        
        # Only spawn one agent per status per run to avoid race conditions
        break
    }
}

# ============================================
# TRACK ACTIVE AGENTS FOR UI
# ============================================

function Update-ActiveAgentsFile {
    # Read existing agents and clean up stale ones
    $agentsFile = "C:\Users\Raitis\.openclaw\logs\active-agents.json"
    $agents = @()
    $now = Get-Date
    
    # Add Lead (main session) - always present
    $agents += @{
        id = "lead"
        name = "Lead"
        label = "Coordinator"
        status = "running"
        updatedAt = [DateTime]::UtcNow.ToString("o")
    }
    
    # Read existing spawned agents and keep only active ones (updated in last 10 min)
    if (Test-Path $agentsFile) {
        try {
            $existing = Get-Content $agentsFile -Raw | ConvertFrom-Json
            foreach ($agent in $existing) {
                if ($agent.id -ne "lead" -and $agent.updatedAt) {
                    $lastUpdate = [DateTime]::Parse($agent.updatedAt)
                    $ageMinutes = ($now - $lastUpdate).TotalMinutes
                    # Keep agents that were updated in last 10 minutes
                    if ($ageMinutes -lt 10) {
                        $agents += $agent
                    }
                }
            }
        } catch {}
    }
    
    # Ensure we output as array (UTF-8 without BOM)
    $json = ConvertTo-Json @($agents) -Depth 3 -Compress
    $utf8NoBom = New-Object System.Text.UTF8Encoding $false
    [System.IO.File]::WriteAllText($agentsFile, $json, $utf8NoBom)
}

# Update active agents file (preserves spawned agents)
Update-ActiveAgentsFile

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
        # Write task to temp file (CLI can't handle multi-line args)
        $taskFile = "C:\Users\Raitis\.openclaw\logs\task-$AgentName-daily.txt"
        $TaskDescription | Set-Content $taskFile -Encoding UTF8
        
        # Use OpenClaw CLI via cmd.exe with proper escaping
        $cliPath = "C:\nvm4w\nodejs\openclaw.cmd"
        $shortTask = "Execute daily task. Read instructions from: $taskFile"
        
        # Generate unique session ID for isolation (each run gets fresh context)
        $timestamp = Get-Date -Format "yyyyMMdd-HHmmss"
        $uniqueSessionId = "$($AgentId)-daily-$timestamp"
        
        # Build command string with unique session-id for isolation
        $cmdArgs = "agent --agent $AgentId --session-id `"$uniqueSessionId`" -m `"$shortTask`" --timeout 3600"
        
        $process = Start-Process -FilePath "cmd.exe" -ArgumentList "/c", "$cliPath $cmdArgs" -WindowStyle Hidden -PassThru -RedirectStandardOutput "C:\Users\Raitis\.openclaw\logs\spawn-$AgentName-out.log" -RedirectStandardError "C:\Users\Raitis\.openclaw\logs\spawn-$AgentName-err.log"
        
        # Track this agent in active-agents.json
        $agentsFile = "C:\Users\Raitis\.openclaw\logs\active-agents.json"
        $agents = @()
        if (Test-Path $agentsFile) {
            try { 
                $existing = Get-Content $agentsFile -Raw | ConvertFrom-Json
                if ($existing -is [System.Array]) {
                    $agents = @($existing)
                } else {
                    $agents = @($existing)
                }
            } catch {}
        }
        $newAgent = @{
            id = $AgentId
            name = $AgentName
            label = $Label
            status = "running"
            pid = $process.Id
            sessionId = $uniqueSessionId
            updatedAt = [DateTime]::UtcNow.ToString("o")
        }
        $agents = @($agents) + @($newAgent)
        $agents | ConvertTo-Json -Depth 3 | Set-Content $agentsFile
        
        Write-Log "Spawned $AgentName via CLI (Session: $uniqueSessionId, PID: $($process.Id))"
        return $true
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

# Check feature limit FIRST - if limit reached, don't even check time
if ($newFeaturesCount -ge $MaxNewFeatures) {
    Write-Log "Innovator skipped: $newFeaturesCount New Features (limit: $MaxNewFeatures)"
    $shouldRunInnovator = $false
}
else {
    # Check time interval (in minutes)
    if ($lastRunHash.innovator) {
        try {
            $lastInnovatorRun = [DateTime]::Parse($lastRunHash.innovator)
            $minutesSince = ([DateTime]::UtcNow - $lastInnovatorRun).TotalMinutes
            if ($minutesSince -ge $InnovatorIntervalMinutes) {
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
}

# Spawn Innovator if conditions met
if ($shouldRunInnovator) {
    Write-Log "Innovator needs to run (last: $($lastRunHash.innovator), New Features: $newFeaturesCount/$MaxNewFeatures)"
    
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
