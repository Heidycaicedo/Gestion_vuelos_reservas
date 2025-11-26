# Gestión de Naves - Implementación Completada

## Resumen de Cambios

Se ha implementado un sistema completo de gestión de naves (aeronaves) que cumple con todos los requisitos especificados (3.1-3.5).

## Archivos Modificados

### 1. Backend - Microservicio de Vuelos

**`microservicio_vuelos/src/Controllers/AircraftController.php`**
- **list()**: Consulta todas las naves disponibles (3.2)
- **show()**: Consulta nave específica por ID (3.2)
- **create()**: Registra nueva nave con validaciones (3.1)
  - Valida que modelo, capacidad y matricula sean requeridos
  - Valida que capacidad sea número positivo
  - Valida que matricula sea única
  - Devuelve 201 si éxito
- **update()**: Modifica información de una nave (3.3)
  - Valida que nave existe
  - Valida cambios en capacidad y matrícula
  - Devuelve 200 si éxito
- **delete()**: Elimina una nave (3.4)
  - Valida que nave existe
  - Valida que NO hay vuelos asociados
  - Devuelve 409 si hay conflicto
  - Devuelve 200 si éxito

**`microservicio_vuelos/src/Controllers/FlightController.php`**
- **create()** ACTUALIZADO: Ahora valida navíos (3.5)
  - Valida que nave_id es requerido
  - Verifica que la nave existe
  - Asigna automáticamente asientos_disponibles = capacidad de la nave
  - Valida que número_vuelo sea único

**`microservicio_vuelos/public/index.php`**
- Agregadas rutas para naves:
  - `PUT /api/naves/{id}` (3.3)
  - `DELETE /api/naves/{id}` (3.4)
- Ambas rutas protegidas con AdminMiddleware

### 2. Frontend

**`frontend/js/api.js`**
- **Aircraft.update()**: NUEVO método para actualizar naves (3.3)
- **Aircraft.delete()**: NUEVO método para eliminar naves (3.4)

### 3. Documentación

**`README.md`**
- Agregada sección "3. Gestión de Naves (Aeronaves)" con requisitos 3.1-3.5

**`GESTION_NAVES.md` (NUEVO)**
- Documentación completa de la API
- Ejemplos de requests/responses para cada operación
- Validaciones implementadas
- Relaciones con vuelos
- Flujos de trabajo
- Modelos de base de datos
- Códigos de estado HTTP
- Ejemplos completos de flujos

**`PRUEBAS_NAVES.md` (NUEVO)**
- 16 casos de prueba detallados
- Ejemplos de curl/http para cada operación
- Verificaciones en base de datos
- Checklist de validación

## Requisitos Implementados

### 3.1 ✅ Registrar Nuevas Naves
- Endpoint: `POST /api/naves`
- Autenticación: Admin
- Validaciones completas (modelo, capacidad, matricula)
- Respuesta: 201 (éxito), 400/409 (error)

### 3.2 ✅ Consultar Naves Disponibles
- Endpoints: 
  - `GET /api/naves` (listar todas)
  - `GET /api/naves/{id}` (nave específica)
- Autenticación: Admin
- Respuesta: 200 con array/objeto de naves

### 3.3 ✅ Modificar Información de Nave
- Endpoint: `PUT /api/naves/{id}`
- Autenticación: Admin
- Validaciones para capacidad y matrícula
- Respuesta: 200 (éxito), 400/404/409 (error)

### 3.4 ✅ Eliminar Nave
- Endpoint: `DELETE /api/naves/{id}`
- Autenticación: Admin
- Validación: No permite eliminar si hay vuelos asociados
- Respuesta: 200 (éxito), 404/409 (error)

### 3.5 ✅ Cada Vuelo Asociado a una Nave
- Validación en create de vuelo:
  - Valida que nave_id es requerido
  - Verifica que nave existe
  - Asigna asientos_disponibles automáticamente
- Foreign key: vuelos.nave_id -> naves.id
- Protección: No se pueden eliminar naves con vuelos

## Validaciones Implementadas

| Validación | Endpoint | Código | Mensaje |
|------------|----------|--------|---------|
| Datos incompletos | POST /api/naves | 400 | "modelo, capacidad y matricula son requeridos" |
| Capacidad inválida | POST/PUT /api/naves | 400 | "La capacidad debe ser un número positivo" |
| Matrícula duplicada | POST/PUT /api/naves | 409 | "Ya existe una nave con esa matrícula" |
| Nave no encontrada | GET/PUT/DELETE /api/naves/{id} | 404 | "Nave no encontrada" |
| Nave con vuelos | DELETE /api/naves/{id} | 409 | "No se puede eliminar una nave que tiene vuelos asociados..." |
| Nave no existe (al crear vuelo) | POST /api/vuelos | 404 | "La nave especificada no existe" |
| Sin token | Cualquier | 401 | "Token requerido" |
| No es admin | Cualquier | 403 | "Acceso denegado. Solo administradores." |

## Relaciones de Base de Datos

```
naves (1) ──── (N) vuelos (1) ──── (N) reservas
   │                   │
   │                   └─ asientos_disponibles = capacidad_nave
   └─ No eliminar si tiene vuelos activos
```

**Cuando se crea una nave:**
- Se guarda: modelo, capacidad, matricula
- Matrícula debe ser única

**Cuando se crea un vuelo:**
- Debe tener una nave_id válida
- asientos_disponibles = nave.capacidad (automático)

**Cuando se crea una reserva:**
- Reduce asientos_disponibles del vuelo

**Cuando se elimina una nave:**
- Valida que NO hay vuelos
- Si hay, retorna 409

## Flujo de Datos

```
Admin                           Backend
  │                               │
  ├─ POST /api/naves ───────────→│ AircraftController::create()
  │  (modelo, capacidad,        ├─ Validar datos completos
  │   matricula)                ├─ Validar capacidad positiva
  │                              ├─ Validar matricula única
  │                              ├─ Crear nave
  │                ←─ 201 ────────┤
  │
  ├─ GET /api/naves ────────────→│ AircraftController::list()
  │                              ├─ Devolver todas las naves
  │                ←─ 200 array ──┤
  │
  ├─ PUT /api/naves/{id} ──────→│ AircraftController::update()
  │  (cambios)                  ├─ Validar nave existe
  │                              ├─ Validar cambios
  │                              ├─ Actualizar nave
  │                ←─ 200 ────────┤
  │
  ├─ DELETE /api/naves/{id} ───→│ AircraftController::delete()
  │                              ├─ Validar nave existe
  │                              ├─ Validar NO tiene vuelos
  │                              ├─ Eliminar nave
  │                ←─ 200 ────────┤
```

## Códigos de Estado HTTP

| Código | Significado | Cuándo |
|--------|-------------|--------|
| 200 | OK | GET exitoso, PUT exitoso, DELETE exitoso |
| 201 | Created | POST (nave creada) |
| 400 | Bad Request | Datos incompletos o inválidos |
| 401 | Unauthorized | Sin token |
| 403 | Forbidden | No es admin |
| 404 | Not Found | Nave no existe |
| 409 | Conflict | Matrícula duplicada, nave con vuelos, etc. |
| 500 | Server Error | Error en base de datos |

## Testing Recomendado

1. **Casos Exitosos:**
   - Crear nave (3.1)
   - Listar naves (3.2)
   - Obtener nave específica (3.2)
   - Modificar nave (3.3)
   - Eliminar nave (3.4)
   - Crear vuelo con nave válida (3.5)

2. **Casos de Error:**
   - Datos incompletos
   - Capacidad inválida
   - Matrícula duplicada
   - Nave no encontrada
   - Eliminar nave con vuelos
   - Acceso sin autenticación/permisos

3. **Verificaciones de Base de Datos:**
   - Matricula única se respeta
   - Foreign key se respeta
   - Asientos_disponibles se asigna correctamente
   - Vuelos no quedan huérfanos

Ver `PRUEBAS_NAVES.md` para pruebas detalladas con ejemplos de curl.

## Archivos de Referencia

- `GESTION_NAVES.md` - Documentación API completa
- `PRUEBAS_NAVES.md` - 16 casos de prueba detallados
- `RESTRICCIONES_ACCESO.md` - Control de acceso (admins)
- `database.sql` - Esquema con tabla naves
- `GESTION_RESERVAS.md` - Cómo interactúa con reservas
