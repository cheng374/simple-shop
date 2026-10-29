$ErrorActionPreference = 'Stop'
$projectDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$phpDir = Join-Path $projectDir 'tools\php'
$sessionDir = Join-Path $projectDir 'data\sessions'
New-Item -ItemType Directory -Force -Path $sessionDir | Out-Null

Push-Location $phpDir
try {
    & .\php.exe -c .\php.ini -v
    Write-Host "`nSQLite extensions:" -ForegroundColor Cyan
    & .\php.exe -c .\php.ini -m | Select-String 'PDO|sqlite'
} finally {
    Pop-Location
}

