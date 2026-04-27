# Requires: $env:STITCH_API_KEY from https://stitch.withgoogle.com/settings
# Usage:  $env:STITCH_API_KEY = '...'; .\scripts\fetch-stitch-screen.ps1
# Params below match project "Nurse Reporting Portal" / screen "Daily Patient Census Report".

param(
    [string] $ProjectId = '6165696019957958651',
    [string] $ScreenId = '98517e069dc348b3aadc032957fcdd4e',
    [string] $OutDir = ''
)

$ErrorActionPreference = 'Stop'
if (-not $env:STITCH_API_KEY) {
    Write-Error 'Set STITCH_API_KEY first (Stitch settings > API Keys).'
}

$repoRoot = Split-Path $PSScriptRoot -Parent
if (-not $OutDir) {
    $OutDir = Join-Path $repoRoot 'stitch-exports\Daily_Patient_Census_Report'
}
New-Item -ItemType Directory -Force -Path $OutDir | Out-Null

$name = "projects/$ProjectId/screens/$ScreenId"
$body = @{
    jsonrpc = '2.0'
    id      = 1
    method  = 'tools/call'
    params  = @{
        name      = 'get_screen'
        arguments = @{
            name      = $name
            projectId = $ProjectId
            screenId  = $ScreenId
        }
    }
} | ConvertTo-Json -Depth 10 -Compress

$r = Invoke-WebRequest -Uri 'https://stitch.googleapis.com/mcp' -Method Post -ContentType 'application/json' `
    -Headers @{ 'X-Goog-Api-Key' = $env:STITCH_API_KEY } -Body $body -UseBasicParsing
$j = $r.Content | ConvertFrom-Json
if (-not $j.result.structuredContent) {
    Write-Error ('Unexpected MCP response: ' + $r.Content)
}

$sc = $j.result.structuredContent
$shotUrl = $sc.screenshot.downloadUrl
$htmlUrl = $sc.htmlCode.downloadUrl

Invoke-WebRequest -Uri $shotUrl -OutFile (Join-Path $OutDir 'screen.png') -UseBasicParsing
Invoke-WebRequest -Uri $htmlUrl -OutFile (Join-Path $OutDir 'screen.html') -UseBasicParsing

Write-Host "Saved: $OutDir\screen.png"
Write-Host "Saved: $OutDir\screen.html"
Write-Host "Title: $($sc.title)"
