# Security Audit Script (Windows PowerShell)
# Runs daily via CRON
# Performs deep security audit and auto-fixes issues

param(
    [string]$LogFile = "$env:USERPROFILE\.openclaw\logs\security-audit.log"
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

if (-not (Test-WorkflowEnabled -LogPath $LogFile -ScriptName "SecurityAudit")) {
    Write-Host "Workflow is paused. Exiting security audit."
    exit 0
}

Write-Log "Starting security audit"

# Run deep security audit
Write-Log "Running deep security audit..."
try {
    $auditResult = & openclaw security audit --deep 2>&1
    Write-Log "Audit result: $auditResult"
} catch {
    Write-Log "Audit failed: $_"
}

# Run auto-fix
Write-Log "Running security auto-fix..."
try {
    $fixResult = & openclaw security audit --fix 2>&1
    Write-Log "Fix result: $fixResult"
} catch {
    Write-Log "Auto-fix failed: $_"
}

Write-Log "Security audit completed"
