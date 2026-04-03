$ErrorActionPreference = 'SilentlyContinue'
Get-Process POWERPNT | Stop-Process -Force
Write-Output 'POWERPOINT_CLEANED'
