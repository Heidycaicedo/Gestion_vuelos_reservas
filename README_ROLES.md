# Gestión de Roles - Sistema de Vuelos y Reservas

## Descripción General

El sistema implementa un modelo de control de acceso basado en roles (RBAC). Existen dos roles principales:
- **Administrador**: Gestión completa del sistema (usuarios, vuelos, naves).
- **Gestor**: Operaciones de reservas, cancelaciones y consulta de vuelos.

---

## Roles y Responsabilidades

### 1. Administrador (`administrador`)

**Responsabilidades:**
- Gestión completa de usuarios (listar, crear, actualizar, cambiar roles, eliminar).
- Crear, actualizar y eliminar vuelos.
- Crear, actualizar y eliminar naves (aircraft).
- Ver todas las reservas del sistema.
- Cancelar o modificar cualquier reserva.
- Acceso total al panel de administración.

**Características especiales:**
- Si un administrador cambia su propio rol a `gestor`, su sesión se cierra automáticamente (invalidación de token).
- Puede listar todos los usuarios independientemente del filtro.
- Puede acceder a todas las reservas sin restricción de propietario.

### 2. Gestor (`gestor`)

**Responsabilidades:**
- Consultar vuelos disponibles.
- Consultar información de naves (aircraft).
- Crear reservas (para vuelos).
- Cancelar sus propias reservas.
- Ver sus propias reservas.

**Limitaciones:**
- No puede crear, actualizar o eliminar vuelos.
- No puede crear, actualizar o eliminar naves.
- No puede gestionar usuarios ni cambiar roles.
- No puede acceder al panel de administración.
- Solo puede ver sus propias reservas (a menos que esté promovido a administrador).

---

## Mapeo de Endpoints por Rol

### Autenticación (Público)

| Endpoint | Método | Rol Requerido | Descripción |
|----------|--------|---------------|-------------|
| `/api/auth/register` | POST | Público | Registrar nuevo usuario (rol default: gestor) |
| `/api/auth/login` | POST | Público | Iniciar sesión |

### Gestión de Usuarios (Usuarios)

| Endpoint | Método | Rol Requerido | Descripción |
|----------|--------|---------------|-------------|
| `/api/auth/logout` | POST | Autenticado | Cerrar sesión |
| `/api/auth/validate` | POST | Autenticado | Validar token actual |
| `/api/users` | GET | Administrador | Listar todos los usuarios |
| `/api/users/{id}` | GET | Administrador \| Propietario | Ver detalles de usuario |
| `/api/users/{id}` | PUT | Administrador \| Propietario | Actualizar datos de usuario |
| `/api/users/{id}/role` | PUT | Administrador | Cambiar rol de usuario |

### Gestión de Vuelos (Vuelos)

| Endpoint | Método | Rol Requerido | Descripción |
|----------|--------|---------------|-------------|
| `/api/flights` | GET | Autenticado | Listar vuelos disponibles |
| `/api/flights/{id}` | GET | Administrador | Ver detalles de vuelo |
| `/api/flights` | POST | Administrador | Crear nuevo vuelo |
| `/api/flights/{id}` | PUT | Administrador | Actualizar vuelo |
| `/api/flights/{id}` | DELETE | Administrador | Eliminar vuelo (sin reservas activas) |

### Gestión de Naves (Aircraft)

| Endpoint | Método | Rol Requerido | Descripción |
|----------|--------|---------------|-------------|
| `/api/aircraft` | GET | Autenticado | Listar naves disponibles |
| `/api/aircraft/{id}` | GET | Administrador | Ver detalles de nave |
| `/api/aircraft` | POST | Administrador | Crear nueva nave |
| `/api/aircraft/{id}` | PUT | Administrador | Actualizar nave |
| `/api/aircraft/{id}` | DELETE | Administrador | Eliminar nave |

### Gestión de Reservas (Vuelos)

| Endpoint | Método | Rol Requerido | Descripción |
|----------|--------|---------------|-------------|
| `/api/reservations` | GET | Autenticado | Listar reservas (propias si Gestor, todas si Admin) |
| `/api/reservations/{id}` | GET | Propietario \| Administrador | Ver detalles de reserva |
| `/api/reservations` | POST | Gestor \| Administrador | Crear nueva reserva |
| `/api/reservations/{id}` | DELETE | Propietario \| Administrador | Cancelar reserva |

---

## Validaciones de Seguridad

### Protecciones Implementadas

1. **Auto-descentramiento de Admin**
   - Si un administrador cambia su propio rol a `gestor`, su token se invalida automáticamente.
   - El usuario debe iniciar sesión nuevamente con el nuevo rol.

2. **Verificación de Propietario en Reservas**
   - Un `gestor` solo puede cancelar sus propias reservas.
   - Un `administrador` puede cancelar cualquier reserva.
   - La lista de reservas de un `gestor` muestra solo las suyas; un `administrador` ve todas.

3. **Middleware de Autenticación**
   - Todos los endpoints protegidos validan el token Bearer en el header `Authorization`.
   - Token inválido o expirado retorna `401 Unauthorized`.
   - Acceso denegado por rol retorna `403 Forbidden`.

4. **Validación de Roles**
   - Solo valores válidos: `'administrador'` y `'gestor'`.
   - Cambios de rol por no-administrador retornan `403 Forbidden`.

---

## Flujos de Ejemplo

### Caso 1: Un Gestor Reserva un Vuelo

```bash
# 1. Iniciar sesión
POST /api/auth/login
{
  "email": "gestor@example.com",
  "password": "password123"
}

# Respuesta:
{
  "success": true,
  "data": {
    "token": "abc123...",
    "user_id": 5,
    "role": "gestor"
  }
}

# 2. Listar vuelos
GET /api/flights
Header: Authorization: Bearer abc123...

# 3. Crear reserva para vuelo ID 2
POST /api/reservations
Header: Authorization: Bearer abc123...
{
  "flight_id": 2
}

# 4. Cancelar reserva ID 10 (propia)
DELETE /api/reservations/10
Header: Authorization: Bearer abc123...
```

### Caso 2: Administrador Cambia su Propio Rol

```bash
# 1. Admin inicia sesión
POST /api/auth/login
{
  "email": "admin@system.com",
  "password": "admin123"
}

# Respuesta: token admin123_token, user_id: 1, role: administrador

# 2. Admin cambia su propio rol a gestor
PUT /api/users/1/role
Header: Authorization: Bearer admin123_token
{
  "role": "gestor"
}

# Respuesta: 200 OK, pero con session_invalidated: true
{
  "success": true,
  "data": {
    "message": "Rol actualizado. Tu sesión ha sido cerrada. Por favor, inicia sesión nuevamente.",
    "session_invalidated": true
  }
}

# 3. El usuario es redirigido a login.html
# 4. Inicia sesión con el nuevo rol "gestor"
```

### Caso 3: Administrador Cancela Reserva de Otro Usuario

```bash
# 1. Admin inicia sesión y obtiene token admin_token

# 2. Admin cancela reserva ID 5 (de otro usuario)
DELETE /api/reservations/5
Header: Authorization: Bearer admin_token

# Respuesta: 200 OK (permitido porque es administrador)
{
  "success": true,
  "data": {
    "message": "Reserva cancelada correctamente"
  }
}
```

---

## Configuración de Bases de Datos

### Tabla `users`

```sql
CREATE TABLE users (
  id INT PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(255) NOT NULL,
  email VARCHAR(255) UNIQUE NOT NULL,
  password VARCHAR(255) NOT NULL,
  role ENUM('administrador', 'gestor') DEFAULT 'gestor',
  token VARCHAR(255) NULL UNIQUE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

**Valores válidos para `role`:**
- `'administrador'`: Acceso total al sistema.
- `'gestor'`: Acceso limitado a vuelos y reservas.

---

## Instrucciones para Pruebas

### 1. Crear Usuarios de Prueba

```bash
# Administrador
UPDATE users SET role = 'administrador' WHERE email = 'admin@example.com';

# Gestor
UPDATE users SET role = 'gestor' WHERE email = 'gestor@example.com';
```

### 2. Ejecutar Microservicios

```powershell
# Terminal 1: Usuario Microservice (Puerto 8001)
cd c:\xampp\htdocs\Gestion_vuelos_reservas\microservicio_usuarios
php -S localhost:8001 -t public public/router.php

# Terminal 2: Flights Microservice (Puerto 8002)
cd c:\xampp\htdocs\Gestion_vuelos_reservas\microservicio_vuelos
php -S localhost:8002 -t public public/router.php
```

### 3. Acceder al Frontend

Navega a: `http://localhost/gestion_vuelos_reservas/` (si está bajo Apache)

O mediante el servidor PHP (puerto 80 alternativo):

```powershell
cd c:\xampp\htdocs\Gestion_vuelos_reservas
php -S localhost:8080 -t .
```

Luego abre: `http://localhost:8080/frontend/login.html`

### 4. Casos de Prueba Sugeridos

1. **Registrarse como nuevo usuario**: Verifica que el rol default es `'gestor'`.
2. **Iniciar sesión como Administrador**: Verifica acceso al panel de administración.
3. **Iniciar sesión como Gestor**: Verifica que el botón de administración está oculto.
4. **Cambiar rol de usuario**: Un administrador cambia el rol de otro usuario a `administrador`.
5. **Auto-democión**: Un administrador cambia su propio rol a `gestor` y verifica que la sesión se cierre.
6. **Crear reserva como Gestor**: Verifica que aparece el botón "Reservar" en vuelos.
7. **Cancelar reserva propia**: Un gestor cancela su propia reserva.
8. **Intento de cancelar reserva ajena**: Un gestor intenta cancelar reserva de otro usuario (debe fallar con 403).

---

## Errores Comunes

| Error | Causa | Solución |
|-------|-------|----------|
| `401 Unauthorized - Token inválido` | Token expirado o no enviado | Iniciar sesión nuevamente |
| `403 Forbidden - Solo administradores` | Usuario no tiene rol de admin | Solicitar al administrador cambio de rol |
| `403 Forbidden - No tienes permiso para cancelar esta reserva` | Intentando cancelar reserva ajena siendo Gestor | Solo puedes cancelar tus propias reservas |
| `409 Conflict - Ya tienes una reserva activa` | Reserva duplicada en mismo vuelo | Cancela la reserva anterior primero |
| `404 Not Found - Usuario no encontrado` | ID de usuario inválido | Verificar ID en base de datos |

---

## Notas de Implementación

- **Tokens**: Se almacenan en la columna `token` de la tabla `users`. Un token es un string hexadecimal de 64 caracteres (bin2hex de 32 bytes aleatorios).
- **Hashing de Contraseña**: Utiliza `PASSWORD_BCRYPT` de PHP.
- **CORS**: En desarrollo, se permite `*` en todas las rutas. **Para producción, limitar origins específicos**.
- **Invalidación de Sesión**: Se realiza estableciendo `token = NULL` en la base de datos.

---

## Futuras Mejoras

1. **Token Expiry**: Implementar expiración de tokens (TTL).
2. **Refresh Tokens**: Añadir mecanismo de renovación de tokens.
3. **Auditoría**: Registrar cambios de rol y acciones administrativas.
4. **Rate Limiting**: Proteger endpoints contra abuso.
5. **Logs**: Ampliar sistema de logging de errores y eventos.

---

**Última actualización**: Noviembre 27, 2025
**Versión**: 1.0
