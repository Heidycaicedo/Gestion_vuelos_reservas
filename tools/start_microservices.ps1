# Inicia los microservicios en ventanas separadas (desarrollo local)
param()

$root = Split-Path -Parent $MyInvocation.MyCommand.Definition

$usuariosPath = Join-Path $root "..\microservicio_usuarios\public"
$vuelosPath = Join-Path $root "..\microservicio_vuelos\public"

Write-Host "Iniciando microservicio_usuarios en http://localhost:8001 ..."
Start-Process powershell -ArgumentList "-NoExit","-Command","cd '$usuariosPath'; php -S localhost:8001 router.php" -WindowStyle Normal

Start-Sleep -Milliseconds 300

Write-Host "Iniciando microservicio_vuelos en http://localhost:8002 ..."
Start-Process powershell -ArgumentList "-NoExit","-Command","cd '$vuelosPath'; php -S localhost:8002 router.php" -WindowStyle Normal

Write-Host "Ambos servidores iniciados. Revisa las ventanas de PowerShell para logs."
