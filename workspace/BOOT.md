# BOOT.md — Gateway Startup Tasks

> Run on every gateway startup to ensure development services are running.

## Services to Start

### 1. MySQL Database (XAMPP)

Start MySQL if not already running:

```powershell
# Check if MySQL is running
$mysql = Get-Process -Name mysqld -ErrorAction SilentlyContinue
if (-not $mysql) {
    Start-Process -FilePath "C:\xampp\mysql_start.bat" -WindowStyle Hidden
    Write-Host "MySQL starting..."
} else {
    Write-Host "MySQL already running (PID: $($mysql.Id))"
}
```

### 2. Backend API (Laravel)

Start PHP dev server on port 8000 if not already running:

```powershell
$backend = Get-NetTCPConnection -LocalPort 8000 -ErrorAction SilentlyContinue
if (-not $backend) {
    Start-Process -FilePath "powershell" -ArgumentList "-NoExit", "-Command", "cd C:\Users\Raitis\.openclaw\workspace\PROJECT-SEOINDICATOR\backend; php artisan serve" -WindowStyle Minimized
    Write-Host "Backend starting on http://localhost:8000..."
} else {
    Write-Host "Backend already running on port 8000"
}
```

### 3. Frontend (Next.js)

Start Next.js dev server on port 3000 if not already running:

```powershell
$frontend = Get-NetTCPConnection -LocalPort 3000 -ErrorAction SilentlyContinue
if (-not $frontend) {
    Start-Process -FilePath "powershell" -ArgumentList "-NoExit", "-Command", "cd C:\Users\Raitis\.openclaw\workspace\PROJECT-SEOINDICATOR\frontend; npm run dev" -WindowStyle Minimized
    Write-Host "Frontend starting on http://localhost:3000..."
} else {
    Write-Host "Frontend already running on port 3000"
}
```

## Notes

- Services are started in background windows (minimized)
- Each service checks if already running before starting
- All services run locally for development

---

Last updated: 2026-02-18
