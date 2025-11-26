# Gestión de Vuelos - Implementación Completada

## Resumen de Cambios

Se ha implementado un sistema completo de gestión de vuelos que cumple con todos los requisitos especificados (2.1-2.5).

## Archivos Modificados

### 1. Backend - Microservicio de Vuelos

**`microservicio_vuelos/src/Controllers/FlightController.php`**
- **list()** ACTUALIZADO: Búsqueda avanzada (2.2, 2.3)
  - Parámetros: `origen`, `destino`, `fecha`, `fecha_desde`, `fecha_hasta`
  - Búsquedas case-insensitive con LIKE para origen/destino
  - Búsquedas de fecha exacta y por rango
  - Múltiples filtros se pueden combinar
  - Devuelve 200 con array de vuelos

- **show()**: Obtiene vuelo específico (existía)
  - Devuelve 404 si no existe

- **create()** ACTUALIZADO: Registra nuevo vuelo (2.1)
  - Valida: numero_vuelo, nave_id, origen, destino, fecha_salida, fecha_llegada requeridos
  - Valida: origen ≠ destino
  - Valida: fecha_llegada > fecha_salida
  - Valida: nave_id existe (FK)
  - Valida: numero_vuelo es único
  - Auto-asigna: asientos_disponibles = capacidad de la nave
  - Devuelve 201 si éxito, 400/404/409 si error

- **update()** ACTUALIZADO: Modifica vuelo (2.4)
  - Valida: vuelo existe
  - Valida: cambios en numero_vuelo (único si se modifica)
  - Valida: cambios en origen/destino (origen ≠ destino)
  - Valida: cambios en fechas (fecha_llegada > fecha_salida)
  - Si se cambia nave: valida existencia y actualiza asientos_disponibles
  - Devuelve 200 si éxito, 404/409/400 si error

- **delete()** ACTUALIZADO: Elimina vuelo (2.5)
  - Valida: vuelo existe
  - Valida: NO hay reservas confirmadas
  - Error 409 si hay reservas: "No se puede eliminar un vuelo que tiene reservas confirmadas. Cancele las reservas primero."
  - Devuelve 200 si éxito, 404/409 si error

**`microservicio_vuelos/public/index.php`**
- Las rutas ya estaban configuradas:
  - `GET /api/vuelos` (público, lista con búsqueda)
  - `GET /api/vuelos/{id}` (admin)
  - `POST /api/vuelos` (admin)
  - `PUT /api/vuelos/{id}` (admin)
  - `DELETE /api/vuelos/{id}` (admin)

### 2. Frontend

**`frontend/js/api.js`**
- **Flights.list(filtros)** ACTUALIZADO:
  - Parámetro opcional: `filtros` object
  - Soporta: `origen`, `destino`, `fecha`, `fecha_desde`, `fecha_hasta`
  - Devuelve respuesta con búsqueda aplicada

- **Flights.search()** NUEVO:
  - `search(origen, destino, fecha)` - método helper
  - Simplifica búsquedas comunes

- **Flights.searchByDateRange()** NUEVO:
  - `searchByDateRange(fecha_desde, fecha_hasta)`
  - Búsqueda específica por rango de fechas

- **Flights.create()** - ya existía, sin cambios
- **Flights.update()** - ya existía, sin cambios
- **Flights.delete()** - ya existía, sin cambios
- **Flights.getById()** - ya existía, sin cambios

### 3. Documentación

**`README.md`**
- Agregada sección "2. Gestión de Vuelos" con requisitos 2.1-2.5

**`GESTION_VUELOS.md` (NUEVO)**
- Documentación completa de la API
- Detalles de cada requisito (2.1-2.5)
- Ejemplos de requests/responses para cada operación
- Validaciones implementadas
- Flujo completo de gestión de vuelos
- Modelos de base de datos
- Códigos de estado HTTP
- Ejemplos completos de flujos
- Notas importantes sobre seguridad y funcionalidad

**`PRUEBAS_VUELOS.md` (NUEVO)**
- 20 casos de prueba detallados
- Ejemplos de curl/http para cada operación
- Verificaciones en base de datos
- Checklist de validación
- Comandos SQL para setup

## Requisitos Implementados

### 2.1 ✅ Registrar Nuevos Vuelos
- Endpoint: `POST /api/vuelos`
- Autenticación: Admin
- Validaciones completas:
  - Datos requeridos: numero_vuelo, nave_id, origen, destino, fecha_salida, fecha_llegada
  - origen ≠ destino
  - fecha_llegada > fecha_salida
  - nave_id válida
  - numero_vuelo única
- Auto-asigna: asientos_disponibles desde capacidad de nave
- Respuesta: 201 (éxito), 400/404/409 (error)

### 2.2 ✅ Consultar Todos los Vuelos
- Endpoint: `GET /api/vuelos`
- Autenticación: No requerida (público)
- Devuelve: Array de todos los vuelos
- Respuesta: 200 con datos

### 2.3 ✅ Buscar Vuelos por Origen, Destino o Fecha
- Endpoint: `GET /api/vuelos?...`
- Parámetros:
  - `origen=valor` - búsqueda LIKE en origen
  - `destino=valor` - búsqueda LIKE en destino
  - `fecha=YYYY-MM-DD` - búsqueda exacta
  - `fecha_desde=YYYY-MM-DD` - búsqueda rango inicio
  - `fecha_hasta=YYYY-MM-DD` - búsqueda rango fin
- Se pueden combinar múltiples criterios
- Respuesta: 200 con vuelos filtrados

### 2.4 ✅ Modificar Información de un Vuelo
- Endpoint: `PUT /api/vuelos/{id}`
- Autenticación: Admin
- Actualiza cualquier campo validando:
  - Vuelo existe
  - numero_vuelo (si se modifica): único
  - origen/destino (si se modifican): origen ≠ destino
  - fechas (si se modifican): fecha_llegada > fecha_salida
  - nave_id (si se modifica): existe y actualiza asientos_disponibles
- Respuesta: 200 (éxito), 404/409/400 (error)

### 2.5 ✅ Eliminar un Vuelo
- Endpoint: `DELETE /api/vuelos/{id}`
- Autenticación: Admin
- Validaciones:
  - Vuelo existe
  - NO tiene reservas confirmadas
- Protección de integridad: Error 409 si hay reservas
- Respuesta: 200 (éxito), 404/409 (error)

## Validaciones Implementadas

| Validación | Endpoint | Código | Mensaje |
|------------|----------|--------|---------|
| Datos incompletos | POST /api/vuelos | 400 | "numero_vuelo, nave_id, origen, destino, fecha_salida y fecha_llegada son requeridos" |
| Origen = Destino | POST/PUT /api/vuelos | 400 | "El origen y destino no pueden ser iguales" |
| Fecha inválida | POST/PUT /api/vuelos | 400 | "La fecha de llegada debe ser posterior a la fecha de salida" |
| Nave no existe | POST/PUT /api/vuelos | 404 | "La nave especificada no existe" |
| Número duplicado | POST/PUT /api/vuelos | 409 | "Ya existe un vuelo con ese número" / "Ya existe otro vuelo con ese número" |
| Vuelo no encontrado | GET/PUT/DELETE /api/vuelos/{id} | 404 | "Vuelo no encontrado" |
| Tiene reservas | DELETE /api/vuelos/{id} | 409 | "No se puede eliminar un vuelo que tiene reservas confirmadas. Cancele las reservas primero." |
| Sin token | Cualquier | 401 | "Token requerido" |
| No es admin | POST/PUT/DELETE /api/vuelos | 403 | "Acceso denegado. Solo administradores." |

## Relaciones de Base de Datos

```
naves (1) ──── (N) vuelos (1) ──── (N) reservas
  │                    │
  │                    ├─ asientos_disponibles = capacidad_nave
  │                    ├─ numero_vuelo = UNIQUE
  │                    └─ origen ≠ destino
  │                    └─ fecha_llegada > fecha_salida
  │
  └─ On Delete: CASCADE (si se elimina nave, se eliminan vuelos)
```

**Cuando se crea un vuelo:**
- Se valida que nave_id existe
- Se asigna asientos_disponibles = nave.capacidad
- Se valida numero_vuelo es único
- Se valida origen ≠ destino
- Se valida fecha_llegada > fecha_salida

**Cuando se modifica un vuelo:**
- Si cambia número: valida único
- Si cambia origen/destino: valida origen ≠ destino
- Si cambia fechas: valida fecha_llegada > fecha_salida
- Si cambia nave: valida existe y actualiza asientos_disponibles

**Cuando se elimina un vuelo:**
- Valida que NO hay reservas confirmadas
- Si hay: error 409, no se elimina

## Flujo de Datos

```
Admin                           Backend
  │                               │
  ├─ POST /api/vuelos ──────────→│ FlightController::create()
  │  (numero_vuelo, nave_id,    ├─ Validar datos completos
  │   origen, destino, fechas)  ├─ Validar origen ≠ destino
  │                              ├─ Validar fechas válidas
  │                              ├─ Validar nave existe
  │                              ├─ Validar numero_vuelo único
  │                              ├─ Auto-asignar asientos
  │                              ├─ Crear vuelo
  │                ←─ 201 ────────┤
  │
  ├─ GET /api/vuelos ────────────→│ FlightController::list()
  │  (origen, destino, fecha)    ├─ Aplicar filtros
  │                              ├─ Devolver vuelos filtrados
  │                ←─ 200 array ──┤
  │
  ├─ PUT /api/vuelos/{id} ──────→│ FlightController::update()
  │  (cambios)                  ├─ Validar vuelo existe
  │                              ├─ Validar cambios
  │                              ├─ Actualizar vuelo
  │                ←─ 200 ────────┤
  │
  ├─ DELETE /api/vuelos/{id} ───→│ FlightController::delete()
  │                              ├─ Validar vuelo existe
  │                              ├─ Validar NO tiene reservas
  │                              ├─ Eliminar vuelo
  │                ←─ 200 ────────┤
```

## Códigos de Estado HTTP

| Código | Significado | Cuándo |
|--------|-------------|--------|
| 200 | OK | GET exitoso, PUT exitoso, DELETE exitoso |
| 201 | Created | POST (vuelo creado) |
| 400 | Bad Request | Datos incompletos o inválidos |
| 401 | Unauthorized | Sin token |
| 403 | Forbidden | No es admin |
| 404 | Not Found | Vuelo no existe, nave no existe |
| 409 | Conflict | Número duplicado, tiene reservas, etc. |
| 500 | Server Error | Error en base de datos |

## Características Principales

### Búsqueda Avanzada (2.3)
- ✅ Búsqueda por origen (case-insensitive, LIKE)
- ✅ Búsqueda por destino (case-insensitive, LIKE)
- ✅ Búsqueda por fecha exacta (YYYY-MM-DD)
- ✅ Búsqueda por rango de fechas (fecha_desde, fecha_hasta)
- ✅ Combinación de múltiples criterios

### Auto-asignación de Asientos (2.1, 2.4)
- ✅ Al crear vuelo: asientos_disponibles = nave.capacidad
- ✅ Al cambiar nave: asientos_disponibles = nueva_nave.capacidad
- ✅ Se sincroniza automáticamente

### Protección de Integridad (2.5)
- ✅ No permite eliminar vuelo con reservas confirmadas
- ✅ Error claro: 409 Conflict con mensaje descriptivo
- ✅ Fuerza cancelación de reservas antes de eliminar vuelo

### Validaciones Completas (2.1, 2.4)
- ✅ Datos requeridos
- ✅ Tipos de datos
- ✅ Ranges válidos
- ✅ Unicidad (numero_vuelo)
- ✅ Relaciones (nave_id)
- ✅ Lógica de negocio (origen ≠ destino, fechas)

### Seguridad (2.1-2.5)
- ✅ Operaciones CRUD requieren token admin (POST, PUT, DELETE)
- ✅ GET /api/vuelos es público
- ✅ Validación de permisos en middleware

## Testing Disponible

- ✅ `PRUEBAS_VUELOS.md`: 20 casos de prueba detallados
- ✅ Ejemplos de curl para cada operación
- ✅ Verificaciones en base de datos
- ✅ Checklist de validación completa

## Archivos de Referencia

- `GESTION_VUELOS.md` - Documentación API completa
- `PRUEBAS_VUELOS.md` - 20 casos de prueba detallados
- `RESTRICCIONES_ACCESO.md` - Control de acceso (admins)
- `database.sql` - Esquema con tabla vuelos
- `GESTION_NAVES.md` - Gestión de naves (dependencia)
- `GESTION_RESERVAS.md` - Gestión de reservas (dependencia)

## Próximos Pasos (Opcional)

1. Ejecutar casos de prueba (PRUEBAS_VUELOS.md)
2. Verificar búsquedas avanzadas
3. Validar protección de integridad con reservas
4. Verificar sincronización de asientos_disponibles

