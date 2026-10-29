$ErrorActionPreference = 'Stop'

$projectDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$phpDir = Join-Path $projectDir 'tools\php'
$sessionDir = Join-Path $projectDir 'data\sessions'
$router = Join-Path $projectDir 'router.php'

if (-not (Test-Path (Join-Path $phpDir 'php.exe'))) {
    throw "找不到 PHP，請重新執行安裝步驟或確認 tools\php\php.exe 存在。"
}

New-Item -ItemType Directory -Force -Path $sessionDir | Out-Null
Write-Host '日常選物所啟動中：http://127.0.0.1:8000' -ForegroundColor Green
Write-Host '按 Ctrl+C 可停止伺服器。' -ForegroundColor DarkGray

Push-Location $phpDir
try {
    & .\php.exe -c .\php.ini -S 127.0.0.1:8000 $router
} finally {
    Pop-Location
}

