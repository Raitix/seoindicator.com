$ErrorActionPreference = 'Stop'
$repoRoot = Split-Path -Parent $PSScriptRoot
Set-Location $repoRoot

git config --local core.hooksPath .githooks
Write-Host 'Configured core.hooksPath=.githooks'
