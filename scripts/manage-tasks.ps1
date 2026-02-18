# OpenClaw Task Scheduler Management Script
# Use this to manually enable/disable/stop OpenClaw scheduled tasks

param(
    [Parameter(Position=0)]
    [ValidateSet("list", "disable", "enable", "stop", "start", "status")]
    [string]$Action = "list",
    
    [Parameter(Position=1)]
    [string]$TaskName = ""
)

$taskPrefix = "OpenClaw"

function Get-OpenClawTasks {
    Get-ScheduledTask | Where-Object { $_.TaskName -like "*$taskPrefix*" }
}

function Show-TaskList {
    $tasks = Get-OpenClawTasks
    Write-Host "`nOpenClaw Scheduled Tasks:" -ForegroundColor Cyan
    Write-Host "=" * 60
    
    foreach ($task in $tasks) {
        $state = if ($task.State -eq "Ready") { "ENABLED" } elseif ($task.State -eq "Disabled") { "DISABLED" } else { $task.State }
        $color = if ($task.State -eq "Ready") { "Green" } elseif ($task.State -eq "Disabled") { "Red" } else { "Yellow" }
        
        Write-Host "  [$state] " -NoNewline -ForegroundColor $color
        Write-Host $task.TaskName
        
        # Show last run time
        $lastRun = $task.LastRunTime
        if ($lastRun -and $lastRun -gt [DateTime]::MinValue) {
            Write-Host "          Last run: $lastRun" -ForegroundColor DarkGray
        }
    }
    
    Write-Host "`nUsage:" -ForegroundColor Yellow
    Write-Host "  .\manage-tasks.ps1 list              - Show this list"
    Write-Host "  .\manage-tasks.ps1 disable [name]    - Disable a task (partial name OK)"
    Write-Host "  .\manage-tasks.ps1 enable [name]     - Enable a task"
    Write-Host "  .\manage-tasks.ps1 stop [name]       - Stop running task"
    Write-Host "  .\manage-tasks.ps1 start [name]      - Run task now"
    Write-Host "  .\manage-tasks.ps1 status            - Detailed status"
    Write-Host ""
}

function Show-DetailedStatus {
    $tasks = Get-OpenClawTasks
    Write-Host "`nOpenClaw Tasks - Detailed Status:" -ForegroundColor Cyan
    Write-Host "=" * 60
    
    foreach ($task in $tasks) {
        Write-Host "`nTask: " -NoNewline
        Write-Host $task.TaskName -ForegroundColor Yellow
        Write-Host "  State: $($task.State)"
        Write-Host "  Last Run: $($task.LastRunTime)"
        Write-Host "  Next Run: $($task.NextRunTime)"
        
        $triggers = $task.Triggers
        if ($triggers) {
            Write-Host "  Trigger: $($triggers[0].ToString())"
        }
    }
}

function Disable-Task {
    param([string]$Name)
    
    $tasks = Get-OpenClawTasks | Where-Object { $_.TaskName -like "*$Name*" }
    
    if (-not $tasks) {
        Write-Host "No task found matching: $Name" -ForegroundColor Red
        return
    }
    
    foreach ($task in $tasks) {
        Disable-ScheduledTask -TaskName $task.TaskName | Out-Null
        Write-Host "Disabled: $($task.TaskName)" -ForegroundColor Yellow
    }
}

function Enable-Task {
    param([string]$Name)
    
    $tasks = Get-OpenClawTasks | Where-Object { $_.TaskName -like "*$Name*" }
    
    if (-not $tasks) {
        Write-Host "No task found matching: $Name" -ForegroundColor Red
        return
    }
    
    foreach ($task in $tasks) {
        Enable-ScheduledTask -TaskName $task.TaskName | Out-Null
        Write-Host "Enabled: $($task.TaskName)" -ForegroundColor Green
    }
}

function Stop-Task {
    param([string]$Name)
    
    $tasks = Get-OpenClawTasks | Where-Object { $_.TaskName -like "*$Name*" }
    
    if (-not $tasks) {
        Write-Host "No task found matching: $Name" -ForegroundColor Red
        return
    }
    
    foreach ($task in $tasks) {
        Stop-ScheduledTask -TaskName $task.TaskName -ErrorAction SilentlyContinue
        Write-Host "Stopped: $($task.TaskName)" -ForegroundColor Yellow
    }
}

function Start-Task {
    param([string]$Name)
    
    $tasks = Get-OpenClawTasks | Where-Object { $_.TaskName -like "*$Name*" }
    
    if (-not $tasks) {
        Write-Host "No task found matching: $Name" -ForegroundColor Red
        return
    }
    
    foreach ($task in $tasks) {
        Start-ScheduledTask -TaskName $task.TaskName
        Write-Host "Started: $($task.TaskName)" -ForegroundColor Green
    }
}

# Main
switch ($Action) {
    "list"    { Show-TaskList }
    "status"  { Show-DetailedStatus }
    "disable" { Disable-Task -Name $TaskName }
    "enable"  { Enable-Task -Name $TaskName }
    "stop"    { Stop-Task -Name $TaskName }
    "start"   { Start-Task -Name $TaskName }
}
