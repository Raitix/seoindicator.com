# Setup OpenClaw Task Scheduler Tasks
# Run this script as Administrator
# 
# FIXED: Uses -WindowStyle Hidden to prevent popup windows
# FIXED: Health check only restarts on REAL issues, not idle sessions

$tasks = @(
    @{
        Name = "OpenClaw Session Health Check"
        Script = "session-health-check.ps1"
        TriggerType = "Interval"
        IntervalMinutes = 5
        Description = "Check for hung sessions every 5 minutes (only restarts on real issues)"
    },
    @{
        Name = "OpenClaw Orchestrator"
        Script = "orchestrator.ps1"
        TriggerType = "Interval"
        IntervalMinutes = 5
        Description = "Scan for work items and spawn agents every 5 minutes (includes daily agents)"
    },
    @{
        Name = "OpenClaw Security Audit"
        Script = "security-audit.ps1"
        TriggerType = "Daily"
        Time = "22:00"
        Description = "Run security audit daily at 22:00"
    },
    @{
        Name = "OpenClaw Memory Compaction Check"
        Script = "memory-compaction-check.ps1"
        TriggerType = "Daily"
        Time = "22:05"
        Description = "Check memory files for compaction threshold daily"
    }
)

$scriptPath = "C:\Users\Raitis\.openclaw\scripts"

Write-Host "Setting up OpenClaw Task Scheduler tasks..." -ForegroundColor Cyan

foreach ($task in $tasks) {
    Write-Host "`nCreating task: $($task.Name)" -ForegroundColor Yellow
    
    # Use VBScript wrapper for completely hidden execution (no flash)
    $vbsWrapper = "$scriptPath\run-hidden.vbs"
    $action = New-ScheduledTaskAction -Execute "wscript.exe" -Argument "//nologo `"$vbsWrapper`" `"$scriptPath\$($task.Script)`""
    
    if ($task.TriggerType -eq "Interval") {
        $trigger = New-ScheduledTaskTrigger -Once -At (Get-Date) -RepetitionInterval (New-TimeSpan -Minutes $task.IntervalMinutes)
    } else {
        $trigger = New-ScheduledTaskTrigger -Daily -At $task.Time
    }
    
    # Settings: run hidden, don't stop on idle, allow battery operation
    $settings = New-ScheduledTaskSettingsSet -StartWhenAvailable -DontStopOnIdleEnd -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries -RunOnlyIfNetworkAvailable:$false
    
    Unregister-ScheduledTask -TaskName $task.Name -Confirm:$false -ErrorAction SilentlyContinue
    
    try {
        Register-ScheduledTask -TaskName $task.Name -Action $action -Trigger $trigger -Settings $settings -Description $task.Description -RunLevel Highest
        Write-Host "  Created successfully (runs hidden)" -ForegroundColor Green
    } catch {
        Write-Host "  Failed: $_" -ForegroundColor Red
    }
}

Write-Host "`n========================================"
Write-Host "Task Scheduler setup complete!"
Write-Host "Tasks will run HIDDEN (no popups):"
Write-Host "  - Session Health Check: Every 5 minutes"
Write-Host "  - Orchestrator: Every 5 minutes"
Write-Host "    (includes SecurityAuditor/PerformanceOptimizer with missed-run recovery)"
Write-Host "  - Security Audit: Daily at 22:00"
Write-Host "  - Memory Compaction: Daily at 22:05"
Write-Host "========================================`n"

Write-Host "Use manage-tasks.ps1 to enable/disable tasks manually" -ForegroundColor Cyan
Write-Host "Done. Tasks are now managed by Windows Task Scheduler." -ForegroundColor Green
