# workflow-helpers.ps1 - Shared functions for workflow control
# Source this file in other scripts: . "$scriptDir\workflow-helpers.ps1"

param()

$ErrorActionPreference = "Continue"

# Path to workflow control file
$script:WorkflowControlPath = "C:\Users\Raitis\.openclaw\workspace\WORKFLOW-CONTROL.md"

<#
.SYNOPSIS
    Checks if the automated workflow is enabled.

.DESCRIPTION
    Reads WORKFLOW-CONTROL.md and parses the workflowEnabled value.
    Returns $true if workflow is active, $false if paused.

.OUTPUTS
    [bool] $true if workflow is enabled, $false otherwise
#>
function Test-WorkflowEnabled {
    param(
        [string]$LogPath,
        [string]$ScriptName = "Unknown"
    )
    
    if (-not (Test-Path $script:WorkflowControlPath)) {
        # Default to enabled if file doesn't exist
        return $true
    }
    
    try {
        $content = Get-Content $script:WorkflowControlPath -Raw
        
        # Parse workflowEnabled from the YAML block
        if ($content -match 'workflowEnabled:\s*(true|false)') {
            $enabled = $Matches[1] -eq 'true'
            
            if (-not $enabled) {
                $timestamp = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
                $logLine = "[$timestamp] [$ScriptName] Workflow PAUSED - skipping execution"
                
                if ($LogPath) {
                    Add-Content -Path $LogPath -Value $logLine
                }
                Write-Host $logLine -ForegroundColor Yellow
            }
            
            return $enabled
        }
        
        # Default to enabled if parsing fails
        return $true
    }
    catch {
        # On error, default to enabled (fail open)
        return $true
    }
}

<#
.SYNOPSIS
    Enables the automated workflow.

.DESCRIPTION
    Sets workflowEnabled to true and clears pause metadata.
#>
function Enable-Workflow {
    param()
    
    $content = Get-Content $script:WorkflowControlPath -Raw
    
    # Update workflowEnabled
    $content = $content -replace 'workflowEnabled:\s*(true|false)', "workflowEnabled: true"
    
    # Clear pause metadata
    $content = $content -replace 'pausedAt:\s*[^\n]+', "pausedAt: null"
    $content = $content -replace 'pausedBy:\s*[^\n]+', "pausedBy: null"
    $content = $content -replace 'pauseReason:\s*[^\n]+', "pauseReason: null"
    
    $content | Set-Content $script:WorkflowControlPath -NoNewline
    
    Write-Host "Workflow ENABLED" -ForegroundColor Green
    return @{ Enabled = $true; PausedAt = $null }
}

<#
.SYNOPSIS
    Disables the automated workflow.

.PARAMETER PausedBy
    Who paused the workflow (optional)

.PARAMETER PauseReason
    Reason for pausing (optional)
#>
function Disable-Workflow {
    param(
        [string]$PausedBy = "",
        [string]$PauseReason = ""
    )
    
    $content = Get-Content $script:WorkflowControlPath -Raw
    
    # Update workflowEnabled
    $content = $content -replace 'workflowEnabled:\s*(true|false)', "workflowEnabled: false"
    
    # Set pause metadata
    $timestamp = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
    $content = $content -replace 'pausedAt:\s*[^\n]+', "pausedAt: `"$timestamp`""
    
    if ($PausedBy) {
        $content = $content -replace 'pausedBy:\s*[^\n]+', "pausedBy: `"$PausedBy`""
    }
    if ($PauseReason) {
        $content = $content -replace 'pauseReason:\s*[^\n]+', "pauseReason: `"$PauseReason`""
    }
    
    $content | Set-Content $script:WorkflowControlPath -NoNewline
    
    Write-Host "Workflow DISABLED" -ForegroundColor Yellow
    return @{ Enabled = $false; PausedAt = $timestamp }
}

<#
.SYNOPSIS
    Sets the workflow enabled state (for backward compatibility).

.PARAMETER State
    "enabled" or "disabled"
#>
function Set-WorkflowState {
    param(
        [Parameter(Mandatory=$true)]
        [ValidateSet("enabled", "disabled")]
        [string]$State,
        
        [string]$PausedBy = "",
        [string]$PauseReason = ""
    )
    
    if ($State -eq "enabled") {
        return Enable-Workflow
    } else {
        return Disable-Workflow -PausedBy $PausedBy -PauseReason $PauseReason
    }
}

<#
.SYNOPSIS
    Gets the current workflow state.

.OUTPUTS
    [hashtable] with Enabled, PausedAt, PausedBy, PauseReason
#>
function Get-WorkflowState {
    if (-not (Test-Path $script:WorkflowControlPath)) {
        return @{
            Enabled = $true
            PausedAt = $null
            PausedBy = $null
            PauseReason = $null
        }
    }
    
    $content = Get-Content $script:WorkflowControlPath -Raw
    
    $state = @{
        Enabled = $true
        PausedAt = $null
        PausedBy = $null
        PauseReason = $null
    }
    
    if ($content -match 'workflowEnabled:\s*(true|false)') {
        $state.Enabled = $Matches[1] -eq 'true'
    }
    if ($content -match 'pausedAt:\s*"?([^"\n]+)"?') {
        $val = $Matches[1].Trim()
        if ($val -ne 'null') { $state.PausedAt = $val }
    }
    if ($content -match 'pausedBy:\s*"?([^"\n]+)"?') {
        $val = $Matches[1].Trim()
        if ($val -ne 'null') { $state.PausedBy = $val }
    }
    if ($content -match 'pauseReason:\s*"?([^"\n]+)"?') {
        $val = $Matches[1].Trim()
        if ($val -ne 'null') { $state.PauseReason = $val }
    }
    
    return $state
}

<#
.SYNOPSIS
    Gets the current innovator enabled state.

.OUTPUTS
    [bool] $true if innovator is enabled, $false otherwise
#>
function Test-InnovatorEnabled {
    if (-not (Test-Path $script:WorkflowControlPath)) {
        return $true
    }
    
    try {
        $content = Get-Content $script:WorkflowControlPath -Raw
        
        if ($content -match 'innovatorEnabled:\s*(true|false)') {
            return $Matches[1] -eq 'true'
        }
        
        return $true
    }
    catch {
        return $true
    }
}
