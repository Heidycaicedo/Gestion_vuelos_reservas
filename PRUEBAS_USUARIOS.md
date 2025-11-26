# Pruebas - Gestión de Usuarios (1.1-1.10)

## Configuración de Pruebas

### Requisitos Previos
1. Base de datos creada y tablas inicializadas
2. Microservicio de usuarios ejecutándose en `http://localhost/Gestion_vuelos_reservas/microservicio_usuarios/public`
3. Herramienta: Postman, Insomnia, cURL, o similar

### Variables de Entorno
```
{{base_url}} = http://localhost/Gestion_vuelos_reservas/microservicio_usuarios/public
{{admin_token}} = [Token de administrador obtenido tras login]
{{user_id}} = [ID del usuario creado]
```

### Setup de Base de Datos
```sql
-- Limpiar datos de prueba
TRUNCATE TABLE sesiones;
TRUNCATE TABLE usuarios;

-- Usuario admin para pruebas
INSERT INTO usuarios (nombre, email, password, rol) VALUES 
('Admin', 'admin@example.com', '$2y$10$...', 'administrador');
```

---

## Caso de Prueba 1: Registrar Usuario Exitoso (1.1)

### Descripción
Validar que se puede registrar un nuevo usuario correctamente.

### Request
```bash
curl -X POST "http://localhost/Gestion_vuelos_reservas/microservicio_usuarios/public/api/usuarios/registrar" \
  -H "Content-Type: application/json" \
  -d '{
    "nombre": "Juan Pérez",
    "email": "juan@example.com",
    "password": "password123"
  }'
```

### Respuesta Esperada (201)
```json
{
  "success": true,
  "data": {
    "usuario_id": 1
  }
}
```

### Validaciones
- ✅ Status HTTP: 201 Created
- ✅ success = true
- ✅ usuario_id devuelto
- ✅ Usuario guardado en BD con rol 'gestor'
- ✅ Contraseña hasheada con BCRYPT

### Verificación en BD
```sql
SELECT id, nombre, email, rol FROM usuarios WHERE email = 'juan@example.com';
-- Debe mostrar: 1, Juan Pérez, juan@example.com, gestor
```

---

## Caso de Prueba 2: Error - Datos Incompletos (1.1)

### Descripción
Validar que faltar un campo requerido retorna error 400.

### Request
```bash
curl -X POST "http://localhost/Gestion_vuelos_reservas/microservicio_usuarios/public/api/usuarios/registrar" \
  -H "Content-Type: application/json" \
  -d '{
    "nombre": "Juan Pérez",
    "email": "juan2@example.com"
  }'
```

### Respuesta Esperada (400)
```json
{
  "success": false,
  "error": "Datos incompletos"
}
```

### Validaciones
- ✅ Status HTTP: 400 Bad Request
- ✅ success = false
- ✅ Mensaje claro del error

---

## Caso de Prueba 3: Error - Email Duplicado (1.1)

### Descripción
Validar que email único se respeta.

### Request
```bash
# Crear primer usuario
curl -X POST "http://localhost/Gestion_vuelos_reservas/microservicio_usuarios/public/api/usuarios/registrar" \
  -H "Content-Type: application/json" \
  -d '{
    "nombre": "Juan Pérez",
    "email": "juan@example.com",
    "password": "password123"
  }'

# Intentar crear otro con el mismo email
curl -X POST "http://localhost/Gestion_vuelos_reservas/microservicio_usuarios/public/api/usuarios/registrar" \
  -H "Content-Type: application/json" \
  -d '{
    "nombre": "Otro Usuario",
    "email": "juan@example.com",
    "password": "password123"
  }'
```

### Respuesta Esperada (400/409)
```json
{
  "success": false,
  "error": "Email ya registrado" o error de BD
}
```

### Validaciones
- ✅ Status HTTP: 400 o 409
- ✅ Rechazo de email duplicado

---

## Caso de Prueba 4: Login Exitoso (1.2, 1.3)

### Descripción
Validar login con credenciales válidas y generación de token.

### Request
```bash
curl -X POST "http://localhost/Gestion_vuelos_reservas/microservicio_usuarios/public/api/usuarios/login" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "juan@example.com",
    "password": "password123"
  }'
```

### Respuesta Esperada (200)
```json
{
  "success": true,
  "data": {
    "token": "a1b2c3d4e5f6g7h8...",
    "usuario_id": 1,
    "rol": "gestor"
  }
}
```

### Validaciones
- ✅ Status HTTP: 200 OK
- ✅ success = true
- ✅ Token devuelto (64 caracteres hex)
- ✅ usuario_id correcto
- ✅ rol correcto
- ✅ Token almacenado en sesiones
- ✅ Token es único
- ✅ Fecha expiración = ahora + 24h

### Verificación en BD
```sql
SELECT token, fecha_expiracion, usuario_id FROM sesiones 
WHERE usuario_id = 1 ORDER BY created_at DESC LIMIT 1;
-- Debe mostrar token y fecha expiración en 24h
```

---

## Caso de Prueba 5: Error - Email Incorrecto (1.2)

### Descripción
Validar que email incorrecto retorna 401.

### Request
```bash
curl -X POST "http://localhost/Gestion_vuelos_reservas/microservicio_usuarios/public/api/usuarios/login" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "inexistente@example.com",
    "password": "password123"
  }'
```

### Respuesta Esperada (401)
```json
{
  "success": false,
  "error": "Credenciales inválidas"
}
```

### Validaciones
- ✅ Status HTTP: 401 Unauthorized
- ✅ Mensaje genérico (seguridad)

---

## Caso de Prueba 6: Error - Contraseña Incorrecta (1.2)

### Descripción
Validar que contraseña incorrecta retorna 401.

### Request
```bash
curl -X POST "http://localhost/Gestion_vuelos_reservas/microservicio_usuarios/public/api/usuarios/login" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "juan@example.com",
    "password": "wrongpassword"
  }'
```

### Respuesta Esperada (401)
```json
{
  "success": false,
  "error": "Credenciales inválidas"
}
```

### Validaciones
- ✅ Status HTTP: 401 Unauthorized

---

## Caso de Prueba 7: Validar Token Exitoso (1.5)

### Descripción
Validar que token válido se acepta.

### Setup
```bash
# Obtener token
LOGIN_RESPONSE=$(curl -s -X POST "http://localhost/Gestion_vuelos_reservas/microservicio_usuarios/public/api/usuarios/login" \
  -H "Content-Type: application/json" \
  -d '{"email": "juan@example.com", "password": "password123"}')
TOKEN=$(echo $LOGIN_RESPONSE | jq -r '.data.token')
```

### Request
```bash
curl -X POST "http://localhost/Gestion_vuelos_reservas/microservicio_usuarios/public/api/usuarios/validar-token" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"token": "'$TOKEN'"}'
```

### Respuesta Esperada (200)
```json
{
  "success": true,
  "data": {
    "usuario_id": 1,
    "rol": "gestor"
  }
}
```

### Validaciones
- ✅ Status HTTP: 200 OK
- ✅ success = true
- ✅ usuario_id correcto
- ✅ rol correcto

---

## Caso de Prueba 8: Error - Token Inválido (1.6)

### Descripción
Validar que token inválido se rechaza.

### Request
```bash
curl -X POST "http://localhost/Gestion_vuelos_reservas/microservicio_usuarios/public/api/usuarios/validar-token" \
  -H "Content-Type: application/json" \
  -d '{"token": "tokeninvalidooquenexiste"}'
```

### Respuesta Esperada (401)
```json
{
  "success": false,
  "error": "Token inválido o expirado"
}
```

### Validaciones
- ✅ Status HTTP: 401 Unauthorized
- ✅ Rechazo de token inválido

---

## Caso de Prueba 9: Logout Exitoso (1.4)

### Descripción
Validar que logout elimina token de BD.

### Setup
```bash
TOKEN=$(obtener token del caso 4)
```

### Request
```bash
curl -X POST "http://localhost/Gestion_vuelos_reservas/microservicio_usuarios/public/api/usuarios/logout" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"token": "'$TOKEN'"}'
```

### Respuesta Esperada (200)
```json
{
  "success": true,
  "data": {
    "mensaje": "Sesión cerrada"
  }
}
```

### Validaciones
- ✅ Status HTTP: 200 OK
- ✅ success = true
- ✅ Token eliminado de sesiones
- ✅ Token no puede reutilizarse (test siguiente)

### Verificación en BD
```sql
SELECT COUNT(*) FROM sesiones WHERE token = '{token}';
-- Debe devolver: 0
```

---

## Caso de Prueba 10: Error - Token Eliminado Tras Logout (1.4, 1.6)

### Descripción
Validar que token no puede reutilizarse tras logout.

### Setup
```bash
# Token del caso 9 (ya fue hecho logout)
```

### Request
```bash
curl -X POST "http://localhost/Gestion_vuelos_reservas/microservicio_usuarios/public/api/usuarios/validar-token" \
  -H "Content-Type: application/json" \
  -d '{"token": "tokendelhlogout"}'
```

### Respuesta Esperada (401)
```json
{
  "success": false,
  "error": "Token inválido o expirado"
}
```

### Validaciones
- ✅ Status HTTP: 401 Unauthorized
- ✅ Token no puede reutilizarse

---

## Caso de Prueba 11: Listar Usuarios - Sin Token (1.8, 1.6)

### Descripción
Validar que se requiere token para listar usuarios.

### Request
```bash
curl -X GET "http://localhost/Gestion_vuelos_reservas/microservicio_usuarios/public/api/usuarios"
```

### Respuesta Esperada (401)
```json
{
  "success": false,
  "error": "Token requerido"
}
```

### Validaciones
- ✅ Status HTTP: 401 Unauthorized
- ✅ Se rechaza sin token

---

## Caso de Prueba 12: Listar Usuarios - Como Gestor (1.8)

### Descripción
Validar que gestor no puede listar usuarios.

### Setup
```bash
# Login como gestor y obtener token
GESTOR_TOKEN=$(curl -s -X POST "..." | jq -r '.data.token')
```

### Request
```bash
curl -X GET "http://localhost/Gestion_vuelos_reservas/microservicio_usuarios/public/api/usuarios" \
  -H "Authorization: Bearer $GESTOR_TOKEN"
```

### Respuesta Esperada (403)
```json
{
  "success": false,
  "error": "Acceso denegado. Solo administradores."
}
```

### Validaciones
- ✅ Status HTTP: 403 Forbidden
- ✅ Se rechaza operación de no-admin

---

## Caso de Prueba 13: Listar Usuarios - Como Admin (1.8)

### Descripción
Validar que admin puede listar usuarios.

### Setup
```bash
# Login como admin y obtener token
ADMIN_TOKEN=$(curl -s -X POST "..." | jq -r '.data.token')
```

### Request
```bash
curl -X GET "http://localhost/Gestion_vuelos_reservas/microservicio_usuarios/public/api/usuarios" \
  -H "Authorization: Bearer $ADMIN_TOKEN"
```

### Respuesta Esperada (200)
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "nombre": "Admin",
      "email": "admin@example.com",
      "rol": "administrador",
      "created_at": "...",
      "updated_at": "..."
    },
    {
      "id": 2,
      "nombre": "Juan Pérez",
      "email": "juan@example.com",
      "rol": "gestor",
      "created_at": "...",
      "updated_at": "..."
    }
  ]
}
```

### Validaciones
- ✅ Status HTTP: 200 OK
- ✅ success = true
- ✅ Array con todos los usuarios
- ✅ Campos correctos

---

## Caso de Prueba 14: Obtener Usuario Específico (1.8)

### Descripción
Validar que se puede obtener usuario específico.

### Request
```bash
curl -X GET "http://localhost/Gestion_vuelos_reservas/microservicio_usuarios/public/api/usuarios/2" \
  -H "Authorization: Bearer $ADMIN_TOKEN"
```

### Respuesta Esperada (200)
```json
{
  "success": true,
  "data": {
    "id": 2,
    "nombre": "Juan Pérez",
    "email": "juan@example.com",
    "rol": "gestor",
    "created_at": "...",
    "updated_at": "..."
  }
}
```

### Validaciones
- ✅ Status HTTP: 200 OK
- ✅ Usuario correcto

---

## Caso de Prueba 15: Error - Usuario No Encontrado (1.8)

### Descripción
Validar que ID inexistente retorna 404.

### Request
```bash
curl -X GET "http://localhost/Gestion_vuelos_reservas/microservicio_usuarios/public/api/usuarios/9999" \
  -H "Authorization: Bearer $ADMIN_TOKEN"
```

### Respuesta Esperada (404)
```json
{
  "success": false,
  "error": "Usuario no encontrado"
}
```

### Validaciones
- ✅ Status HTTP: 404 Not Found

---

## Caso de Prueba 16: Actualizar Datos de Usuario (1.9)

### Descripción
Validar que admin puede actualizar datos de usuario.

### Request
```bash
curl -X PUT "http://localhost/Gestion_vuelos_reservas/microservicio_usuarios/public/api/usuarios/2" \
  -H "Authorization: Bearer $ADMIN_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "nombre": "Juan Carlos Pérez",
    "email": "juancarlos@example.com"
  }'
```

### Respuesta Esperada (200)
```json
{
  "success": true,
  "data": {
    "id": 2,
    "nombre": "Juan Carlos Pérez",
    "email": "juancarlos@example.com",
    "rol": "gestor",
    "created_at": "...",
    "updated_at": "..."
  }
}
```

### Validaciones
- ✅ Status HTTP: 200 OK
- ✅ Nombre actualizado
- ✅ Email actualizado
- ✅ updated_at modificado

### Verificación en BD
```sql
SELECT nombre, email FROM usuarios WHERE id = 2;
-- Debe mostrar: Juan Carlos Pérez, juancarlos@example.com
```

---

## Caso de Prueba 17: Cambiar Rol a Administrador (1.10)

### Descripción
Validar que admin puede cambiar rol a administrador.

### Request
```bash
curl -X PUT "http://localhost/Gestion_vuelos_reservas/microservicio_usuarios/public/api/usuarios/2/rol" \
  -H "Authorization: Bearer $ADMIN_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "rol": "administrador"
  }'
```

### Respuesta Esperada (200)
```json
{
  "success": true,
  "data": {
    "id": 2,
    "nombre": "Juan Carlos Pérez",
    "email": "juancarlos@example.com",
    "rol": "administrador",
    "created_at": "...",
    "updated_at": "..."
  }
}
```

### Validaciones
- ✅ Status HTTP: 200 OK
- ✅ Rol cambiado a "administrador"

### Verificación en BD
```sql
SELECT rol FROM usuarios WHERE id = 2;
-- Debe mostrar: administrador
```

---

## Caso de Prueba 18: Cambiar Rol a Gestor (1.10)

### Descripción
Validar que admin puede cambiar rol a gestor.

### Request
```bash
curl -X PUT "http://localhost/Gestion_vuelos_reservas/microservicio_usuarios/public/api/usuarios/2/rol" \
  -H "Authorization: Bearer $ADMIN_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "rol": "gestor"
  }'
```

### Respuesta Esperada (200)
```json
{
  "success": true,
  "data": {
    "id": 2,
    "nombre": "Juan Carlos Pérez",
    "email": "juancarlos@example.com",
    "rol": "gestor",
    "created_at": "...",
    "updated_at": "..."
  }
}
```

### Validaciones
- ✅ Status HTTP: 200 OK
- ✅ Rol cambiado a "gestor"

---

## Caso de Prueba 19: Error - Rol Inválido (1.10)

### Descripción
Validar que rol inválido retorna error 400.

### Request
```bash
curl -X PUT "http://localhost/Gestion_vuelos_reservas/microservicio_usuarios/public/api/usuarios/2/rol" \
  -H "Authorization: Bearer $ADMIN_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "rol": "superadmin"
  }'
```

### Respuesta Esperada (400)
```json
{
  "success": false,
  "error": "Rol requerido" o error de BD
}
```

### Validaciones
- ✅ Status HTTP: 400 o 409
- ✅ Rechazo de rol inválido

---

## Caso de Prueba 20: Error - Rol No Proporcionado (1.10)

### Descripción
Validar que rol es requerido.

### Request
```bash
curl -X PUT "http://localhost/Gestion_vuelos_reservas/microservicio_usuarios/public/api/usuarios/2/rol" \
  -H "Authorization: Bearer $ADMIN_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{}'
```

### Respuesta Esperada (400)
```json
{
  "success": false,
  "error": "Rol requerido"
}
```

### Validaciones
- ✅ Status HTTP: 400 Bad Request

---

## Checklist de Validación

### Registro (1.1)
- [ ] Usuario registrado exitosamente
- [ ] Contraseña hasheada
- [ ] Email único validado
- [ ] Datos incompletos rechazados
- [ ] Rol por defecto: "gestor"

### Login (1.2, 1.3)
- [ ] Login exitoso
- [ ] Token generado (64 chars)
- [ ] Token almacenado en BD
- [ ] Token es único
- [ ] Expiración 24 horas
- [ ] Credenciales inválidas rechazadas

### Logout (1.4)
- [ ] Logout exitoso
- [ ] Token eliminado de BD
- [ ] Token no puede reutilizarse

### Validación de Token (1.5, 1.6)
- [ ] Token válido aceptado
- [ ] Token inválido rechazado (401)
- [ ] Token expirado rechazado (401)
- [ ] Sin token rechazado (401)
- [ ] Middleware activo en rutas protegidas

### Rol (1.7)
- [ ] Rol almacenado en BD
- [ ] Rol se devuelve en login
- [ ] Roles válidos: "administrador", "gestor"

### Gestión de Usuarios (1.8-1.10)
- [ ] Listar solo con admin
- [ ] Listar sin token: 401
- [ ] Listar como gestor: 403
- [ ] Listar como admin: 200
- [ ] Obtener usuario específico
- [ ] Usuario no encontrado: 404
- [ ] Actualizar datos de usuario
- [ ] Cambiar rol a administrador
- [ ] Cambiar rol a gestor
- [ ] Rol inválido rechazado
- [ ] Rol no proporcionado: 400

### Seguridad
- [ ] Contraseñas hasheadas
- [ ] Tokens únicos
- [ ] Token no en logs
- [ ] Mensajes de error genéricos
- [ ] SQL injection prevenido
- [ ] CSRF token (si aplica)

---

## Comandos SQL para Setup

```sql
-- Crear usuario admin para pruebas
INSERT INTO usuarios (nombre, email, password, rol) VALUES 
('Admin Test', 'admin@test.com', '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcg7b3XeKeUxWdeS86E36P4/1Ty', 'administrador');

-- Crear usuario gestor para pruebas
INSERT INTO usuarios (nombre, email, password, rol) VALUES 
('Gestor Test', 'gestor@test.com', '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcg7b3XeKeUxWdeS86E36P4/1Ty', 'gestor');

-- Verificar usuarios
SELECT id, nombre, email, rol FROM usuarios;

-- Verificar sesiones
SELECT id, usuario_id, token, fecha_expiracion FROM sesiones;

-- Limpiar sesiones expiradas
DELETE FROM sesiones WHERE fecha_expiracion < NOW();
```

---

## Resumen de Casos

| # | Caso | Req | Status | Notas |
|-|-|-|-|-|
| 1 | Registrar exitoso | 1.1 | 201 | ✅ |
| 2 | Datos incompletos | 1.1 | 400 | ✅ |
| 3 | Email duplicado | 1.1 | 400 | ✅ |
| 4 | Login exitoso | 1.2, 1.3 | 200 | ✅ |
| 5 | Email incorrecto | 1.2 | 401 | ✅ |
| 6 | Contraseña incorrecta | 1.2 | 401 | ✅ |
| 7 | Token válido | 1.5 | 200 | ✅ |
| 8 | Token inválido | 1.6 | 401 | ✅ |
| 9 | Logout | 1.4 | 200 | ✅ |
| 10 | Token eliminado | 1.4, 1.6 | 401 | ✅ |
| 11 | Listar sin token | 1.8, 1.6 | 401 | ✅ |
| 12 | Listar como gestor | 1.8 | 403 | ✅ |
| 13 | Listar como admin | 1.8 | 200 | ✅ |
| 14 | Obtener usuario | 1.8 | 200 | ✅ |
| 15 | Usuario no existe | 1.8 | 404 | ✅ |
| 16 | Actualizar usuario | 1.9 | 200 | ✅ |
| 17 | Cambiar a admin | 1.10 | 200 | ✅ |
| 18 | Cambiar a gestor | 1.10 | 200 | ✅ |
| 19 | Rol inválido | 1.10 | 400 | ✅ |
| 20 | Rol no proporcionado | 1.10 | 400 | ✅ |

**Total: 20 casos de prueba**

