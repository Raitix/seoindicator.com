# Setup OpenClaw Task Scheduler Tasks
# Run this script as Administrator

$tasks = @(
    @{
        Name = "OpenClaw Session Health Check"
        Script = "session-health-check.ps1"
        TriggerType = "Interval"
        IntervalMinutes = 5
        Description = "Check for hung sessions every 5 minutes"
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
    
    $action = New-ScheduledTaskAction -Execute "powershell.exe" -Argument "-NoProfile -ExecutionPolicy Bypass -File `"$scriptPath\$($task.Script)`""
    
    if ($task.TriggerType -eq "Interval") {
        $trigger = New-ScheduledTaskTrigger -Once -At (Get-Date) -RepetitionInterval (New-TimeSpan -Minutes $task.IntervalMinutes)
    } else {
        $trigger = New-ScheduledTaskTrigger -Daily -At $task.Time
    }
    
    $settings = New-ScheduledTaskSettingsSet -StartWhenAvailable -DontStopOnIdleEnd -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries -RunOnlyIfNetworkAvailable:$false
    
    Unregister-ScheduledTask -TaskName $task.Name -Confirm:$false -ErrorAction SilentlyContinue
    
    try {
        Register-ScheduledTask -TaskName $task.Name -Action $action -Trigger $trigger -Settings $settings -Description $task.Description -RunLevel Highest
        Write-Host "  Created successfully" -ForegroundColor Green
    } catch {
        Write-Host "  Failed: $_" -ForegroundColor Red
    }
}

Write-Host "`n========================================"
Write-Host "Task Scheduler setup complete!"
Write-Host "Tasks will run automatically:"
Write-Host "  - Session Health Check: Every 5 minutes"
Write-Host "  - Security Audit: Daily at 22:00"
Write-Host "  - Memory Compaction: Daily at 22:05"
Write-Host "========================================`n"

Get-Process -Name powershell -ErrorAction SilentlyContinue | Where-Object { $_.MainWindowTitle -eq "" -and $_.Id -ne $PID } | Stop-Process -Force -ErrorAction SilentlyContinue

Write-Host "Done. Tasks are now managed by Windows Task Scheduler." -ForegroundColor Green
