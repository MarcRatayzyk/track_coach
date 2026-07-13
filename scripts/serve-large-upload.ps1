$ErrorActionPreference = "Stop"

$port = if ($env:PORT) { $env:PORT } else { "8000" }
$hostAddress = "127.0.0.1"

Write-Host "Starting Laravel dev server with increased upload limits..."
Write-Host "Host: http://$hostAddress`:$port"
Write-Host ""
Write-Host "Limits:"
Write-Host "- upload_max_filesize=120M"
Write-Host "- post_max_size=130M"
Write-Host ""

php `
  -d upload_max_filesize=120M `
  -d post_max_size=130M `
  -d max_execution_time=120 `
  -d max_input_time=120 `
  -S "$hostAddress`:$port" dev/php-router.php

