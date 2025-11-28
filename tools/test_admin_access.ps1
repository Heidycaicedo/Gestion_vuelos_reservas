# Test script para verificar acceso admin
$baseUrl = "http://localhost:8001"

# 1. Login como admin
Write-Host "=== 1. Intentando login como admin ===" -ForegroundColor Cyan
$loginResponse = Invoke-RestMethod -Uri "$baseUrl/api/auth/login" -Method POST `
    -Body (ConvertTo-Json @{
        email = "admin@system.com"
        password = "admin123"
    }) `
    -ContentType "application/json" -ErrorAction SilentlyContinue

if ($loginResponse) {
    Write-Host "✓ Login exitoso" -ForegroundColor Green
    $token = $loginResponse.data.token
    $userId = $loginResponse.data.user_id
    $role = $loginResponse.data.role
    
    Write-Host "Token: $token" -ForegroundColor Yellow
    Write-Host "User ID: $userId" -ForegroundColor Yellow
    Write-Host "Role: $role" -ForegroundColor Yellow
    
    # 2. Intentar listar usuarios
    Write-Host "`n=== 2. Intentando listar usuarios con GET /api/users ===" -ForegroundColor Cyan
    try {
        $usersResponse = Invoke-RestMethod -Uri "$baseUrl/api/users" -Method GET `
            -Headers @{
                "Authorization" = "Bearer $token"
                "Content-Type" = "application/json"
            } -ErrorAction Stop
        
        Write-Host "✓ Usuarios obtenidos:" -ForegroundColor Green
        $usersResponse.data | ForEach-Object {
            Write-Host "  - ID: $($_.id), Name: $($_.name), Role: $($_.role)"
        }
    } catch {
        Write-Host "✗ Error al listar usuarios" -ForegroundColor Red
        Write-Host "StatusCode: $($_.Exception.Response.StatusCode)" -ForegroundColor Red
        Write-Host "Response: $($_.Exception.Response | Select-Object -ExpandProperty Content)" -ForegroundColor Red
    }
} else {
    Write-Host "✗ Login fallido" -ForegroundColor Red
}

# 3. Login como gestor
Write-Host "`n=== 3. Intentando login como gestor ===" -ForegroundColor Cyan
$loginGestor = Invoke-RestMethod -Uri "$baseUrl/api/auth/login" -Method POST `
    -Body (ConvertTo-Json @{
        email = "gestor@system.com"
        password = "gestor123"
    }) `
    -ContentType "application/json" -ErrorAction SilentlyContinue

if ($loginGestor) {
    Write-Host "✓ Login exitoso como gestor" -ForegroundColor Green
    $tokenGestor = $loginGestor.data.token
    $roleGestor = $loginGestor.data.role
    
    Write-Host "Role: $roleGestor" -ForegroundColor Yellow
    
    # 4. Intentar listar usuarios como gestor (debe fallar)
    Write-Host "`n=== 4. Intentando listar usuarios como GESTOR (debe fallar) ===" -ForegroundColor Cyan
    try {
        $usersResponseGestor = Invoke-RestMethod -Uri "$baseUrl/api/users" -Method GET `
            -Headers @{
                "Authorization" = "Bearer $tokenGestor"
                "Content-Type" = "application/json"
            } -ErrorAction Stop
        
        Write-Host "✗ ERROR: Gestor pudo listar usuarios (no debería)" -ForegroundColor Red
    } catch {
        $statusCode = $_.Exception.Response.StatusCode.Value__
        if ($statusCode -eq 403) {
            Write-Host "✓ Correcto: Acceso denegado (403) para gestor" -ForegroundColor Green
        } else {
            Write-Host "✗ Error inesperado: $statusCode" -ForegroundColor Red
        }
    }
}
