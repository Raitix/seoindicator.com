# Memory Compaction Script (Windows PowerShell)
# Checks MEMORY and LESSONS-LEARNED files for size threshold
# Triggers intelligent compaction if exceeded

param(
    [string]$WorkspacePath = "$env:USERPROFILE\.openclaw\workspace",
    [int]$ThresholdKB = 500,
    [string]$LogFile = "$env:USERPROFILE\.openclaw\logs\memory-compaction.log"
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

# ============================================
# WORKFLOW CONTROL CHECK
# ============================================

$scriptDir = Split-Path $MyInvocation.MyCommand.Path -Parent
. "$scriptDir\workflow-helpers.ps1"

if (-not (Test-WorkflowEnabled -LogPath $LogFile -ScriptName "MemoryCompaction")) {
    Write-Host "Workflow is paused. Exiting memory compaction check."
    exit 0
}

function Get-LargeFiles {
    param([string]$Path, [int]$ThresholdKB)
    
    $files = @()
    $thresholdBytes = $ThresholdKB * 1024
    
    if (Test-Path $Path) {
        Get-ChildItem -Path $Path -Filter "*.md" -Recurse | ForEach-Object {
            if ($_.Length -gt $thresholdBytes) {
                $files += [PSCustomObject]@{
                    FilePath = $_.FullName
                    SizeKB = [math]::Round($_.Length / 1024, 1)
                }
            }
        }
    }
    
    return $files
}

function Invoke-Compaction {
    param([string]$FilePath)
    
    Write-Log "Triggering compaction for: $FilePath"
    
    # Use sessions_spawn to create isolated agent for compaction
    $fileName = Split-Path $FilePath -Leaf
    $agentName = if ($FilePath -match "MEMORY") { "Lead" } elseif ($FilePath -match "LESSONS-LEARNED") { "Lead" } else { "Lead" }
    
    $task = @"
COMPACT MEMORY FILE

File: $FilePath
Size: Exceeds $ThresholdKB KB threshold

Instructions:
1. Read the entire file
2. Identify and PRESERVE:
   - All directives and rules marked as IMPORTANT or CRITICAL
   - Key decisions and their rationale
   - Active work tracking tables
   - Concurrency limits and workflow rules
   - Any section with 'Must-Follow' or 'Required' markers
3. Summarize sections that can be condensed:
   - Historical notes that are no longer relevant
   - Duplicate information
   - Verbose descriptions that can be shortened
4. Maintain file structure and format
5. Write the compacted version back to the same file
6. Report what was preserved vs summarized

Goal: Reduce file size while keeping all important directives intact.
"@

    # This would be called via sessions_spawn in the main session
    # For now, we just log the trigger
    Write-Log "Compaction task prepared for $fileName"
    
    return $task
}

# Main logic
Write-Log "Starting memory size check (threshold: $ThresholdKB KB)"

$memoryPath = Join-Path $WorkspacePath "MEMORY"
$lessonsPath = Join-Path $WorkspacePath "PROJECT-SEOINDICATOR\LESSONS-LEARNED"

$largeFiles = @()
$largeFiles += Get-LargeFiles -Path $memoryPath -ThresholdKB $ThresholdKB
$largeFiles += Get-LargeFiles -Path $lessonsPath -ThresholdKB $ThresholdKB

if ($largeFiles.Count -eq 0) {
    Write-Log "All memory files under threshold"
    exit 0
}

Write-Log "Found $($largeFiles.Count) files exceeding threshold:"
foreach ($file in $largeFiles) {
    Write-Log "  - $($file.FilePath) ($($file.SizeKB) KB)"
    
    # Trigger compaction
    $task = Invoke-Compaction -FilePath $file.FilePath
    
    # Output for processing
    Write-Output "COMPACTION_NEEDED:$($file.FilePath)"
}
