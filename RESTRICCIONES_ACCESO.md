# Restricciones de Acceso Implementadas

## Resumen

Se han implementado middleware de autenticación y autorización en ambos microservicios para cumplir con los requisitos de seguridad y control de acceso.

## 5. Restricciones de Acceso

### 5.1 Gestión Restringida a Administrador
Solo los usuarios con rol **administrador** pueden acceder a:
- **Microservicio de Usuarios:**
  - `GET /api/usuarios` - Listar todos los usuarios
  - `GET /api/usuarios/{id}` - Ver datos de un usuario específico
  - `PUT /api/usuarios/{id}` - Actualizar datos de un usuario
  - `PUT /api/usuarios/{id}/rol` - Cambiar el rol de un usuario

- **Microservicio de Vuelos:**
  - `GET /api/vuelos/{id}` - Ver detalles de un vuelo específico
  - `POST /api/vuelos` - Crear nuevos vuelos
  - `PUT /api/vuelos/{id}` - Actualizar información de vuelos
  - `DELETE /api/vuelos/{id}` - Eliminar vuelos
  - `GET /api/naves` - Listar naves
  - `GET /api/naves/{id}` - Ver detalles de una nave
  - `POST /api/naves` - Crear naves

### 5.2 Funciones de Reservas Restringidas a Gestor
Solo los usuarios con rol **gestor** pueden acceder a:
- `POST /api/reservas` - Crear reservas de vuelos
- `DELETE /api/reservas/{id}` - Cancelar reservas

### 5.3 Servicios Públicos
Los siguientes servicios son accesibles sin autenticación:
- `POST /api/usuarios/registrar` - Registro de nuevos usuarios
- `POST /api/usuarios/login` - Iniciar sesión
- `GET /api/vuelos` - Listar vuelos disponibles (información pública)

### 5.4 Servicios Protegidos Requeridos
Todos los demás servicios requieren un token válido:
- Token debe estar en el header `Authorization: Bearer {token}` o en el body
- El token se valida contra la tabla `sesiones`
- Se verifica que no esté expirado (fecha_expiracion > ahora)

Servicios que requieren autenticación:
- `POST /api/usuarios/logout` - Cerrar sesión (todos los usuarios)
- `POST /api/usuarios/validar-token` - Validar token (todos los usuarios)
- `GET /api/reservas` - Listar reservas (requiere autenticación)

### 5.5 Redirección Automática
**Frontend:**
- Si el token no existe o es inválido (respuesta 401), el usuario es redirigido automáticamente a `login.html`
- El token, usuario_id y rol se eliminan del localStorage
- Si accede a un recurso sin permisos (respuesta 403), se muestra un mensaje de error

**Backend:**
- Respuesta 401: Token faltante, inválido o expirado
- Respuesta 403: Acceso denegado por rol insuficiente

## Implementación Técnica

### Middleware en Microservicio de Usuarios

**AuthMiddleware:** Valida que exista un token válido
- Busca el token en Authorization header o en el body
- Verifica en la tabla sesiones
- Asigna usuario_id al request si es válido

**AdminMiddleware:** Verifica que el usuario sea administrador
- Obtiene el usuario_id del request
- Busca el usuario en la tabla usuarios
- Verifica que rol === 'administrador'

### Middleware en Microservicio de Vuelos

**AuthMiddleware:** Igual que en usuarios, accede directamente a base de datos

**GestorMiddleware:** Verifica que el usuario sea gestor
- Obtiene el usuario_id del request
- Busca el usuario en la tabla usuarios
- Verifica que rol === 'gestor'

**AdminMiddleware:** Verifica que el usuario sea administrador
- Obtiene el usuario_id del request
- Busca el usuario en la tabla usuarios
- Verifica que rol === 'administrador'

## Flujo de Autenticación

```
1. Usuario accede a login.html
   ↓
2. Envía POST a /api/usuarios/login (público)
   ↓
3. Si credenciales válidas:
   - Sistema genera token aleatorio
   - Almacena en tabla sesiones
   - Devuelve token, usuario_id y rol
   ↓
4. Frontend almacena en localStorage:
   - token
   - usuario_id
   - rol
   ↓
5. Para cada petición protegida:
   - Frontend envía Authorization: Bearer {token}
   - Backend valida token con AuthMiddleware
   - Si es admin/gestor, verifica con AdminMiddleware/GestorMiddleware
   ↓
6. Si token es inválido (401):
   - Frontend redirige a login.html
   - Limpia localStorage
```

## Rutas de API con Permisos

### Microservicio de Usuarios

| Método | Ruta | Público | Autenticado | Admin |
|--------|------|---------|-------------|-------|
| POST | `/api/usuarios/registrar` | ✅ | - | - |
| POST | `/api/usuarios/login` | ✅ | - | - |
| POST | `/api/usuarios/logout` | - | ✅ | - |
| POST | `/api/usuarios/validar-token` | - | ✅ | - |
| GET | `/api/usuarios` | - | - | ✅ |
| GET | `/api/usuarios/{id}` | - | - | ✅ |
| PUT | `/api/usuarios/{id}` | - | - | ✅ |
| PUT | `/api/usuarios/{id}/rol` | - | - | ✅ |

### Microservicio de Vuelos

| Método | Ruta | Público | Autenticado | Admin | Gestor |
|--------|------|---------|-------------|-------|--------|
| GET | `/api/vuelos` | ✅ | - | - | - |
| GET | `/api/vuelos/{id}` | - | - | ✅ | - |
| POST | `/api/vuelos` | - | - | ✅ | - |
| PUT | `/api/vuelos/{id}` | - | - | ✅ | - |
| DELETE | `/api/vuelos/{id}` | - | - | ✅ | - |
| GET | `/api/naves` | - | - | ✅ | - |
| GET | `/api/naves/{id}` | - | - | ✅ | - |
| POST | `/api/naves` | - | - | ✅ | - |
| GET | `/api/reservas` | - | ✅ | ✅ | ✅ |
| POST | `/api/reservas` | - | - | - | ✅ |
| DELETE | `/api/reservas/{id}` | - | - | - | ✅ |

## Pruebas Recomendadas

1. **Acceso sin autenticación:**
   - Ir a `/index.html` sin token → debe redirigir a login.html

2. **Token inválido:**
   - Modificar token en localStorage → debe redirigir a login.html

3. **Token expirado:**
   - Esperar a que expire (fecha_expiracion) → debe redirigir a login.html

4. **Acceso con permisos insuficientes:**
   - Login como gestor e intentar acceder a `/api/usuarios` → error 403

5. **Acceso correcto:**
   - Login como admin e intentar acceder a `/api/usuarios` → éxito 200
   - Login como gestor e intentar crear reserva → éxito 201
