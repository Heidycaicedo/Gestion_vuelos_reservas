@echo off
REM Script para reiniciar todos los servicios del Centro de Vuelos
REM Ejecutar como Administrador

echo.
echo ==========================================
echo Centro de Vuelos - Reinicio de Servicios
echo ==========================================
echo.

REM Detener servicios
echo [1/4] Deteniendo servicios PHP...
taskkill /F /IM php.exe >nul 2>&1
echo [1/4] Completado - PHP detenido

echo [2/4] Deteniendo MySQL...
taskkill /F /IM mysqld.exe >nul 2>&1
timeout /t 2 /nobreak >nul

REM Iniciar MySQL
echo [3/4] Iniciando MySQL...
start /min "" C:\xampp\mysql\bin\mysqld.exe
timeout /t 3 /nobreak >nul

REM Iniciar microservicios
echo [4/4] Iniciando microservicios...
start /min "API Usuarios" cmd /k "cd C:\xampp\htdocs\Gestion_vuelos_reservas\microservicio_usuarios\public && php -S 0.0.0.0:8001 router.php"
timeout /t 1 /nobreak >nul
start /min "API Vuelos" cmd /k "cd C:\xampp\htdocs\Gestion_vuelos_reservas\microservicio_vuelos\public && php -S 0.0.0.0:8002 router.php"

echo.
echo ==========================================
echo Servicios reiniciados correctamente
echo ==========================================
echo.
echo URLs de acceso:
echo   Frontend:    http://localhost/Gestion_vuelos_reservas/frontend/
echo   API Usuarios: http://localhost:8001/api/
echo   API Vuelos:   http://localhost:8002/api/
echo.
echo Usuario: admin@system.com
echo Password: admin123
echo.
pause
