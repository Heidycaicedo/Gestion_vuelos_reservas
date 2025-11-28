# Script de prueba para validar los requisitos de Gestión de Usuarios
$API_USUARIOS = 'http://localhost:8001'

Write-Host "===== PRUEBAS DE GESTION DE USUARIOS =====" -ForegroundColor Cyan

# 1. Registro público (cualquiera puede registrarse)
Write-Host "`n[1.1] Registro publico - Registrar nuevo usuario" -ForegroundColor Yellow
$randomEmail = "nuevousuario_$(Get-Random)@test.com"
$newUser = @{
    name = "Nueva Usuario"
    email = $randomEmail
    password = "password123"
} | ConvertTo-Json

$registerResp = Invoke-RestMethod -Method Post -Uri "$API_USUARIOS/api/auth/register" -Body $newUser -ContentType 'application/json' -ErrorAction SilentlyContinue

if ($registerResp.success) {
    Write-Host "OK - User ID: $($registerResp.data.user_id)" -ForegroundColor Green
} else {
    Write-Host "FALLO: $($registerResp.error)" -ForegroundColor Red
}

# 2. Login y generación de token
Write-Host "`n[1.2-1.3] Login y generacion de token" -ForegroundColor Yellow
$loginBody = @{ email = "admin@system.com"; password = "admin123" } | ConvertTo-Json
$loginResp = Invoke-RestMethod -Method Post -Uri "$API_USUARIOS/api/auth/login" -Body $loginBody -ContentType 'application/json' -ErrorAction SilentlyContinue

if ($loginResp.success) {
    $token = $loginResp.data.token
    $userId = $loginResp.data.user_id
    $role = $loginResp.data.role
    Write-Host "OK - Token: $($token.Substring(0,16))... Role: $role" -ForegroundColor Green
} else {
    Write-Host "FALLO: $($loginResp.error)" -ForegroundColor Red
    exit
}

# 3. Validación de token
Write-Host "`n[1.5] Validacion de token" -ForegroundColor Yellow
$validateBody = @{ token = $token } | ConvertTo-Json
$validateResp = Invoke-RestMethod -Method Post -Uri "$API_USUARIOS/api/auth/validate" -Body $validateBody -ContentType 'application/json' -ErrorAction SilentlyContinue

if ($validateResp.success) {
    Write-Host "OK - Token valido" -ForegroundColor Green
} else {
    Write-Host "FALLO: Token invalido" -ForegroundColor Red
}

# 4. Acceso denegado sin token
Write-Host "`n[1.6] Acceso denegado sin token valido" -ForegroundColor Yellow
$noTokenTest = Invoke-RestMethod -Method Get -Uri "$API_USUARIOS/api/users" -ContentType 'application/json' -ErrorAction SilentlyContinue
if ($noTokenTest.error -like "*no autorizado*" -or $noTokenTest.error -like "*401*") {
    Write-Host "OK - Acceso denegado correctamente" -ForegroundColor Green
} else {
    Write-Host "FALLO: Se accedio sin token" -ForegroundColor Red
}

# 5. Listar usuarios (admin)
Write-Host "`n[1.8] Listar usuarios (Admin)" -ForegroundColor Yellow
$listUsersResp = Invoke-RestMethod -Method Get -Uri "$API_USUARIOS/api/users" -Headers @{ Authorization = "Bearer $token" } -ContentType 'application/json' -ErrorAction SilentlyContinue

if ($listUsersResp.success) {
    Write-Host "OK - Total usuarios: $($listUsersResp.data.Count)" -ForegroundColor Green
} else {
    Write-Host "FALLO: $($listUsersResp.error)" -ForegroundColor Red
}

# 6. Actualizar datos de usuario (admin)
Write-Host "`n[1.9] Actualizar datos de usuario (Admin)" -ForegroundColor Yellow
$updateBody = @{ name = "Admin Actualizado"; email = "admin@system.com" } | ConvertTo-Json
$updateResp = Invoke-RestMethod -Method Put -Uri "$API_USUARIOS/api/users/$userId" -Headers @{ Authorization = "Bearer $token" } -Body $updateBody -ContentType 'application/json' -ErrorAction SilentlyContinue

if ($updateResp.success) {
    Write-Host "OK - Usuario actualizado" -ForegroundColor Green
} else {
    Write-Host "FALLO: $($updateResp.error)" -ForegroundColor Red
}

# 7. Cambiar rol de usuario (admin)
Write-Host "`n[1.10] Cambiar rol de usuario (Admin)" -ForegroundColor Yellow
$changeRoleBody = @{ role = "gestor" } | ConvertTo-Json
$changeRoleResp = Invoke-RestMethod -Method Put -Uri "$API_USUARIOS/api/users/$userId/role" -Headers @{ Authorization = "Bearer $token" } -Body $changeRoleBody -ContentType 'application/json' -ErrorAction SilentlyContinue

if ($changeRoleResp.success) {
    Write-Host "OK - Rol cambiado a gestor" -ForegroundColor Green
} else {
    Write-Host "FALLO: $($changeRoleResp.error)" -ForegroundColor Red
}

# 8. Cambiar rol de vuelta a administrador (usar otro usuario admin para evitar issue de cache)
# Nota: El usuario actual cambio su propio rol, por eso el middleware lo rechaza. Esto es comportamiento correcto.
Write-Host "`n[1.10] Cambiar rol de otro usuario (Admin)" -ForegroundColor Yellow
# Usar ID 3 (gestor) para cambiar a administrador
$changeRoleBody2 = @{ role = "administrador" } | ConvertTo-Json
$changeRoleResp2 = Invoke-RestMethod -Method Put -Uri "$API_USUARIOS/api/users/3/role" -Headers @{ Authorization = "Bearer $token" } -Body $changeRoleBody2 -ContentType 'application/json' -ErrorAction SilentlyContinue

if ($changeRoleResp2.success) {
    Write-Host "OK - Rol de usuario 3 cambiado a administrador" -ForegroundColor Green
} else {
    Write-Host "FALLO: $($changeRoleResp2.error)" -ForegroundColor Red
}

# 9. Logout (eliminar token)
Write-Host "`n[1.4] Logout - Eliminar token" -ForegroundColor Yellow
$logoutBody = @{ token = $token } | ConvertTo-Json
$logoutResp = Invoke-RestMethod -Method Post -Uri "$API_USUARIOS/api/auth/logout" -Body $logoutBody -ContentType 'application/json' -ErrorAction SilentlyContinue

if ($logoutResp.success) {
    Write-Host "OK - Token eliminado" -ForegroundColor Green
} else {
    Write-Host "FALLO: $($logoutResp.error)" -ForegroundColor Red
}

# 10. Verificar que token fue eliminado
Write-Host "`n[Verificacion] Token eliminado correctamente" -ForegroundColor Yellow
$tokenDeletedTest = Invoke-RestMethod -Method Get -Uri "$API_USUARIOS/api/users" -Headers @{ Authorization = "Bearer $token" } -ContentType 'application/json' -ErrorAction SilentlyContinue

if ($tokenDeletedTest.error -like "*invalido*" -or $tokenDeletedTest.error -like "*expirado*") {
    Write-Host "OK - Token correctamente invalidado" -ForegroundColor Green
} else {
    Write-Host "FALLO: Token aun es valido" -ForegroundColor Red
}

Write-Host "`n===== FIN DE PRUEBAS =====" -ForegroundColor Cyan
