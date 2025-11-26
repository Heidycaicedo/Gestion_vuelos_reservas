# Gestión de Usuarios (1.1-1.10)

## Descripción General

El sistema de gestión de usuarios implementa autenticación y autorización completa, permitiendo el registro de usuarios, login, generación de tokens, y gestión de roles basada en permisos.

**Responsabilidad:** Principalmente pública (registro/login) y Administrador (gestión de usuarios)

---

## 1.1 Registrar Nuevos Usuarios

### Requisito
El sistema debe permitir registrar nuevos usuarios (Solo administrador en la realidad, pero la API es pública).

### Endpoint
```
POST /api/usuarios/registrar
```

### Autenticación
- **Requerida:** No (público)

### Body Request
```json
{
  "nombre": "Juan Pérez",
  "email": "juan@example.com",
  "password": "micontraseña123"
}
```

### Validaciones Implementadas

| Validación | Código | Mensaje |
|-----------|--------|---------|
| Nombre requerido | 400 | "Datos incompletos" |
| Email requerido | 400 | "Datos incompletos" |
| Contraseña requerida | 400 | "Datos incompletos" |
| Email único | 400/409 | Error de BD |

### Procesamiento
1. Valida que todos los campos estén presentes
2. Hash de contraseña con PASSWORD_BCRYPT
3. Crea usuario con rol por defecto "gestor"
4. Almacena en tabla usuarios

### Response Success (201)
```json
{
  "success": true,
  "data": {
    "usuario_id": 1
  }
}
```

### Response Errors

**400 Bad Request** - Datos incompletos
```json
{
  "success": false,
  "error": "Datos incompletos"
}
```

**400/409 Conflict** - Email duplicado
```json
{
  "success": false,
  "error": "Email ya registrado" o error de BD
}
```

### Ejemplo de Uso (JavaScript)

```javascript
const response = await Auth.register('Juan', 'juan@example.com', 'pass123');
if (response.success) {
  console.log("Usuario registrado:", response.data.data.usuario_id);
}
```

### Ejemplo con cURL

```bash
curl -X POST "http://localhost/Gestion_vuelos_reservas/microservicio_usuarios/public/api/usuarios/registrar" \
  -H "Content-Type: application/json" \
  -d '{
    "nombre": "Juan Pérez",
    "email": "juan@example.com",
    "password": "micontraseña123"
  }'
```

---

## 1.2 Iniciar Sesión

### Requisito
El sistema debe permitir iniciar sesión mediante correo y contraseña.

### Endpoint
```
POST /api/usuarios/login
```

### Autenticación
- **Requerida:** No (público)

### Body Request
```json
{
  "email": "juan@example.com",
  "password": "micontraseña123"
}
```

### Validaciones Implementadas

| Validación | Código | Mensaje |
|-----------|--------|---------|
| Email requerido | 400 | "Email y contraseña requeridos" |
| Contraseña requerida | 400 | "Email y contraseña requeridos" |
| Credenciales válidas | 401 | "Credenciales inválidas" |

### Procesamiento
1. Valida que email y contraseña se proporcionaron
2. Busca usuario por email
3. Verifica contraseña con password_verify()
4. Si válidas: continúa a 1.3 (generar token)
5. Si no: retorna error 401

### Response Success (200)
```json
{
  "success": true,
  "data": {
    "token": "a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6q7r8s9t0u1v2w3x4y5z6...",
    "usuario_id": 1,
    "rol": "gestor"
  }
}
```

### Response Errors

**400 Bad Request** - Datos incompletos
```json
{
  "success": false,
  "error": "Email y contraseña requeridos"
}
```

**401 Unauthorized** - Credenciales inválidas
```json
{
  "success": false,
  "error": "Credenciales inválidas"
}
```

### Ejemplo de Uso (JavaScript)

```javascript
const response = await Auth.login('juan@example.com', 'micontraseña123');
if (response.success) {
  localStorage.setItem('token', response.data.data.token);
  localStorage.setItem('usuario_id', response.data.data.usuario_id);
  localStorage.setItem('rol', response.data.data.rol);
  console.log("Login exitoso");
}
```

---

## 1.3 Generar Token en Base de Datos

### Requisito
Al iniciar sesión, el sistema debe generar un token aleatorio y almacenarlo en la base de datos.

### Implementación
- **Generación:** `bin2hex(random_bytes(32))` → 64 caracteres hexadecimales
- **Almacenamiento:** Tabla `sesiones`
- **Expiración:** 24 horas desde creación
- **Unicidad:** Constraint UNIQUE en columna token

### Estructura en BD

**Tabla sesiones:**
```sql
id              INT PRIMARY KEY AUTO_INCREMENT
usuario_id      INT NOT NULL (FK → usuarios.id)
token           VARCHAR(255) UNIQUE NOT NULL
fecha_expiracion DATETIME NOT NULL
created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP
```

### Proceso
1. Generar token de 256 bits (64 chars hex)
2. Calcular expiración = ahora + 24 horas
3. Insertar en sesiones(usuario_id, token, fecha_expiracion)
4. Devolver token a cliente

### Verificación en BD
```sql
SELECT usuario_id, token, fecha_expiracion FROM sesiones 
WHERE usuario_id = 1 ORDER BY created_at DESC LIMIT 1;
```

### Token Ejemplo
```
a1b2c3d4e5f6a7b8c9d0e1f2a3b4c5d6e7f8a9b0c1d2e3f4a5b6c7d8e9f0a1
```

---

## 1.4 Cerrar Sesión

### Requisito
El sistema debe permitir cerrar sesión eliminando el token almacenado.

### Endpoint
```
POST /api/usuarios/logout
```

### Autenticación
- **Requerida:** Bearer token

### Body Request
```json
{
  "token": "a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6q7r8s9t0u1v2w3x4y5z6..."
}
```

### Validaciones Implementadas

| Validación | Código | Mensaje |
|-----------|--------|---------|
| Token requerido | 400 | "Token requerido" |

### Procesamiento
1. Valida que token se proporcionó
2. Busca y elimina sesión con ese token
3. Token ya no puede reutilizarse

### Response Success (200)
```json
{
  "success": true,
  "data": {
    "mensaje": "Sesión cerrada"
  }
}
```

### Response Errors

**400 Bad Request** - Token no proporcionado
```json
{
  "success": false,
  "error": "Token requerido"
}
```

**401 Unauthorized** - Token inválido
```json
{
  "success": false,
  "error": "Token inválido o expirado"
}
```

### Ejemplo de Uso (JavaScript)

```javascript
const response = await Auth.logout(token);
if (response.success) {
  localStorage.removeItem('token');
  localStorage.removeItem('usuario_id');
  localStorage.removeItem('rol');
  console.log("Logout exitoso");
}
```

### Verificación en BD (Antes/Después)

**Antes:**
```sql
SELECT COUNT(*) FROM sesiones WHERE token = 'a1b2c3...';
-- Result: 1
```

**Después:**
```sql
SELECT COUNT(*) FROM sesiones WHERE token = 'a1b2c3...';
-- Result: 0
```

---

## 1.5 Validar Token en Peticiones Protegidas

### Requisito
El sistema debe validar el token en cada petición a los microservicios protegidos.

### Implementación

**Middleware: AuthMiddleware**
```
1. Extrae token de header: Authorization: Bearer {token}
2. Busca sesión con ese token
3. Verifica que fecha_expiracion > NOW()
4. Si válido: continúa
5. Si no: retorna error 401
```

**Aplicación:**
- Todas las rutas protegidas pasan por AuthMiddleware
- Middleware se aplica antes de ejecutar controlador

### Header Format
```
Authorization: Bearer a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6q7r8s9t0u1v2w3x4y5z6...
```

### Validaciones

| Validación | Código | Mensaje |
|-----------|--------|---------|
| Token en header | 401 | "Token requerido" |
| Token existe | 401 | "Token inválido o expirado" |
| Token no expirado | 401 | "Token inválido o expirado" |

### Proceso
```
1. Request llega a ruta protegida
2. AuthMiddleware intercepta
3. Valida token
4. Si falla: responde 401
5. Si pasa: continúa a siguiente middleware/controlador
```

### Flujo

**Request con token válido:**
```
GET /api/usuarios
Authorization: Bearer a1b2c3...
↓
AuthMiddleware
  ├─ Extrae token
  ├─ Busca en sesiones
  ├─ Verifica expiración
  └─ ✓ Válido → continúa
↓
UserController.list()
  └─ Devuelve usuarios
↓
200 OK
```

**Request con token inválido:**
```
GET /api/usuarios
Authorization: Bearer inválido
↓
AuthMiddleware
  ├─ Extrae token
  ├─ Busca en sesiones
  ├─ No existe
  └─ ✗ Inválido → rechaza
↓
401 Unauthorized
"Token inválido o expirado"
```

---

## 1.6 Impedir Acceso sin Token Válido

### Requisito
El sistema debe impedir el acceso si el token no es válido o no existe.

### Implementación

**Rechazo de Acceso:**
- Sin token → 401 "Token requerido"
- Token inválido → 401 "Token inválido o expirado"
- Token expirado → 401 "Token inválido o expirado"
- No admin (ruta admin) → 403 "Acceso denegado"

### Códigos HTTP

| Situación | Código | Descripción |
|-----------|--------|-------------|
| Sin Authorization header | 401 | Token requerido |
| Token no en BD | 401 | Token inválido/expirado |
| Expiración pasada | 401 | Token inválido/expirado |
| Usuario no admin | 403 | Acceso denegado |
| Permisos insuficientes | 403 | Acceso denegado |

### Ejemplos de Rechazo

**Sin token:**
```bash
GET /api/usuarios
→ 401 "Token requerido"
```

**Token inválido:**
```bash
GET /api/usuarios
Authorization: Bearer invalidotoken
→ 401 "Token inválido o expirado"
```

**Token expirado:**
```bash
GET /api/usuarios
Authorization: Bearer a1b2c3... (24h+)
→ 401 "Token inválido o expirado"
```

**Sin admin (ruta admin):**
```bash
GET /api/usuarios
Authorization: Bearer gestor_token
→ 403 "Acceso denegado. Solo administradores."
```

---

## 1.7 Rol de Usuario (Administrador / Gestor)

### Requisito
El rol del usuario debe almacenarse en la tabla de usuarios con las opciones: administrador y gestor.

### Implementación

**Tabla usuarios:**
```sql
rol ENUM('administrador', 'gestor') DEFAULT 'gestor'
```

**Valores válidos:**
- `'administrador'` - Acceso total
- `'gestor'` - Acceso limitado

**Por defecto:** `'gestor'`

### Asignación

**Al registrar:**
```php
'rol' => $data['rol'] ?? 'gestor',  // Por defecto gestor
```

**Al cambiar (1.10):**
```php
$user->rol = $data['rol'];  // Cambiar a admin o gestor
$user->save();
```

### Almacenamiento
- Almacenado en BD en tabla usuarios
- Se devuelve en login
- Se devuelve en validar-token
- Se devuelve en listar usuarios

### Uso en Middleware

**AdminMiddleware:**
```php
if ($user->rol !== 'administrador') {
  return 403 "Acceso denegado"
}
```

**GestorMiddleware:**
```php
if ($user->rol !== 'gestor') {
  return 403 "Acceso denegado"
}
```

### Verificación en BD
```sql
SELECT id, nombre, rol FROM usuarios;
```

---

## 1.8 Consultar Lista de Usuarios

### Requisito
El administrador debe poder consultar la lista de usuarios.

### Endpoint
```
GET /api/usuarios
```

### Autenticación
- **Requerida:** Bearer token (admin)

### Query Parameters
- Ninguno

### Validaciones Implementadas

| Validación | Código |
|-----------|--------|
| Token requerido | 401 |
| Token válido | 401 |
| Usuario es admin | 403 |

### Response Success (200)
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "nombre": "Admin",
      "email": "admin@example.com",
      "password": "$2y$10$...",
      "rol": "administrador",
      "created_at": "2025-11-15 10:00:00",
      "updated_at": "2025-11-15 10:00:00"
    },
    {
      "id": 2,
      "nombre": "Juan Pérez",
      "email": "juan@example.com",
      "password": "$2y$10$...",
      "rol": "gestor",
      "created_at": "2025-11-15 10:05:00",
      "updated_at": "2025-11-15 10:05:00"
    }
  ]
}
```

### Response Errors

**401 Unauthorized** - Token no válido
```json
{
  "success": false,
  "error": "Token requerido" o "Token inválido o expirado"
}
```

**403 Forbidden** - No es admin
```json
{
  "success": false,
  "error": "Acceso denegado. Solo administradores."
}
```

---

## 1.9 Actualizar Datos de Usuario

### Requisito
El administrador debe poder actualizar datos de un usuario.

### Endpoint
```
PUT /api/usuarios/{id}
```

### Autenticación
- **Requerida:** Bearer token (admin)

### Body Request
```json
{
  "nombre": "Nuevo Nombre",
  "email": "nuevo@example.com"
}
```

### Validaciones Implementadas

| Validación | Código |
|-----------|--------|
| Usuario existe | 404 |
| Token requerido | 401 |
| Usuario es admin | 403 |

### Response Success (200)
```json
{
  "success": true,
  "data": {
    "id": 2,
    "nombre": "Nuevo Nombre",
    "email": "nuevo@example.com",
    "rol": "gestor",
    "created_at": "2025-11-15 10:05:00",
    "updated_at": "2025-11-15 11:00:00"
  }
}
```

### Response Errors

**404 Not Found** - Usuario no existe
```json
{
  "success": false,
  "error": "Usuario no encontrado"
}
```

**401/403** - Autenticación/Autorización
```json
{
  "success": false,
  "error": "Token requerido" o "Acceso denegado"
}
```

---

## 1.10 Cambiar Rol de Usuario

### Requisito
El administrador debe poder cambiar el rol de un usuario.

### Endpoint
```
PUT /api/usuarios/{id}/rol
```

### Autenticación
- **Requerida:** Bearer token (admin)

### Body Request
```json
{
  "rol": "administrador"
}
```

### Validaciones Implementadas

| Validación | Código | Mensaje |
|-----------|--------|---------|
| Usuario existe | 404 | "Usuario no encontrado" |
| Rol requerido | 400 | "Rol requerido" |
| Token requerido | 401 | "Token requerido" |
| Usuario es admin | 403 | "Acceso denegado" |

### Roles Válidos
- `"administrador"` - Acceso total
- `"gestor"` - Acceso limitado

### Response Success (200)
```json
{
  "success": true,
  "data": {
    "id": 2,
    "nombre": "Juan Pérez",
    "email": "juan@example.com",
    "rol": "administrador",
    "created_at": "2025-11-15 10:05:00",
    "updated_at": "2025-11-15 11:05:00"
  }
}
```

### Response Errors

**400 Bad Request** - Rol no proporcionado
```json
{
  "success": false,
  "error": "Rol requerido"
}
```

**404 Not Found** - Usuario no existe
```json
{
  "success": false,
  "error": "Usuario no encontrado"
}
```

**403 Forbidden** - No es admin
```json
{
  "success": false,
  "error": "Acceso denegado. Solo administradores."
}
```

---

## Flujo Completo

```
┌──────────────────────────────────────────────────────────┐
│          FLUJO COMPLETO DE GESTIÓN DE USUARIOS            │
└──────────────────────────────────────────────────────────┘

┌─ REGISTRO (1.1) ──────────────────────────────┐
│ POST /api/usuarios/registrar                  │
│ Body: nombre, email, password                 │
│ Response: 201 usuario_id                      │
└────────────────────────────────────────────────┘
                        ↓
┌─ LOGIN (1.2, 1.3) ────────────────────────────┐
│ POST /api/usuarios/login                      │
│ Body: email, password                         │
│ ├─ Validar credenciales                       │
│ ├─ Generar token (64 chars)                   │
│ ├─ Almacenar en sesiones (24h)                │
│ └─ Response: 200 token, usuario_id, rol       │
└────────────────────────────────────────────────┘
                        ↓
┌─ ALMACENAR TOKEN (Frontend) ───────────────────┐
│ localStorage:                                 │
│ ├─ token: "a1b2c3..."                         │
│ ├─ usuario_id: 2                              │
│ └─ rol: "gestor"                              │
└────────────────────────────────────────────────┘
                        ↓
┌─ OPERACIONES PROTEGIDAS ──────────────────────┐
│ Header: Authorization: Bearer a1b2c3...       │
│                                               │
│ ├─ GET /api/usuarios (1.8)                    │
│ │  ├─ AuthMiddleware: valida token (1.5)      │
│ │  ├─ AdminMiddleware: valida rol (1.7)       │
│ │  ├─ Si falla: 401 o 403 (1.6)               │
│ │  └─ Si pasa: lista usuarios                 │
│ │                                             │
│ ├─ PUT /api/usuarios/{id} (1.9)               │
│ │  ├─ Idem validaciones                       │
│ │  └─ Actualiza datos                         │
│ │                                             │
│ └─ PUT /api/usuarios/{id}/rol (1.10)          │
│    ├─ Idem validaciones                       │
│    └─ Cambia rol (admin/gestor)               │
└────────────────────────────────────────────────┘
                        ↓
┌─ LOGOUT (1.4) ────────────────────────────────┐
│ POST /api/usuarios/logout                     │
│ Body: token                                   │
│ ├─ Eliminar sesión de BD                      │
│ ├─ Token no puede reutilizarse (1.5, 1.6)     │
│ └─ Response: 200                              │
└────────────────────────────────────────────────┘
                        ↓
┌─ LIMPIAR TOKEN (Frontend) ────────────────────┐
│ localStorage:                                 │
│ ├─ removeItem('token')                        │
│ ├─ removeItem('usuario_id')                   │
│ ├─ removeItem('rol')                          │
│ └─ Redirigir a login                          │
└────────────────────────────────────────────────┘
```

---

## Códigos de Estado HTTP

| Código | Significado | Casos |
|--------|-------------|-------|
| 200 | OK | GET exitoso, PUT exitoso, logout |
| 201 | Created | POST registrar exitoso |
| 400 | Bad Request | Datos incompletos, validaciones |
| 401 | Unauthorized | Token inválido/expirado, credenciales |
| 403 | Forbidden | No es admin para operación |
| 404 | Not Found | Usuario no existe |
| 500 | Server Error | Error BD |

---

## Seguridad Implementada

✅ **Contraseñas:** PASSWORD_BCRYPT
✅ **Tokens:** 256 bits aleatorios, únicos, expiración 24h
✅ **Middleware:** AuthMiddleware + AdminMiddleware
✅ **BD:** Email único, token único, FK usuario_id
✅ **Mensajes:** Genéricos para seguridad
✅ **Validación:** Entrada validada

