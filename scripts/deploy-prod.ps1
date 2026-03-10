param(
    [string]$Trigger = 'manual',
    [string]$GitRef = 'origin/main',
    [string]$Branch = 'main',
    [switch]$SkipPlaywright
)

$ErrorActionPreference = 'Stop'
$scriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$pythonScript = Join-Path $scriptDir 'deploy_prod.py'

if (-not (Test-Path $pythonScript)) {
    throw "Deployment script not found: $pythonScript"
}

$args = @($pythonScript, '--trigger', $Trigger, '--git-ref', $GitRef, '--branch', $Branch)
if ($SkipPlaywright) {
    $args += '--skip-playwright'
}

$pythonCmd = Get-Command python -ErrorAction SilentlyContinue
if ($pythonCmd) {
    & $pythonCmd.Source @args
    exit $LASTEXITCODE
}

$pyCmd = Get-Command py -ErrorAction SilentlyContinue
if ($pyCmd) {
    & $pyCmd.Source -3 @args
    exit $LASTEXITCODE
}

throw 'Python runtime not found in PATH.'
