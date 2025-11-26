# Gestión de Reservas - Implementación Completada

## Resumen de Cambios

Se ha implementado un sistema completo de gestión de reservas que cumple con todos los requisitos especificados (4.1-4.5).

## Archivos Modificados

### 1. Backend - Microservicio de Vuelos

**`microservicio_vuelos/src/Controllers/ReservationController.php`**
- **list()**: Consulta todas las reservas con filtro opcional por usuario_id (4.2, 4.3)
- **listByUser()**: Nuevo método para consultar reservas de un usuario específico (4.3)
- **create()**: Crea una nueva reserva con validaciones completas (4.1, 4.5)
  - Valida que vuelo_id y numero_asiento sean requeridos
  - Verifica que el vuelo existe
  - Valida que el asiento no esté reservado
  - Verifica que hay asientos disponibles
  - Reduce asientos_disponibles del vuelo
- **cancel()**: Cancela una reserva existente (4.4)
  - Valida que la reserva exista
  - Valida que esté en estado "confirmada"
  - Cambia estado a "cancelada"
  - Incrementa asientos_disponibles del vuelo

**`microservicio_vuelos/public/index.php`**
- Agregada ruta: `GET /api/reservas/usuario/{id}` (4.3)
- Estructurada con middleware GestorMiddleware para proteger operaciones de reserva

### 2. Frontend

**`frontend/js/api.js`**
- Actualizado método `Reservations.create()` con parámetros específicos (vuoloId, numeroAsiento)

**`frontend/js/app.js`**
- **reserveFlight()**: Ahora implementado con prompt para número de asiento (4.1)
- **cancelReservation()**: Ahora funcional que llama a API (4.4)
- Agregada función **realizarReserva()** con manejo de errores específicos (409, 404)
- Agregada función **cancelarReserva()** con manejo de errores
- Recarga automática de listas después de operaciones

### 3. Documentación

**`README.md`**
- Agregada sección "4. Gestión de Reservas (Responsabilidad del Gestor)" con requisitos 4.1-4.5

**`GESTION_RESERVAS.md` (NUEVO)**
- Documentación completa de la API
- Ejemplos de requests/responses para cada operación
- Validaciones implementadas
- Flujos de trabajo
- Modelos de base de datos
- Códigos de estado HTTP
- Ejemplos completos de flujos

**`PRUEBAS_RESERVAS.md` (NUEVO)**
- 12 casos de prueba detallados
- SQL para insertar datos de prueba
- Ejemplos de curl/http para cada operación
- Verificaciones en base de datos
- Checklist de validación

## Requisitos Implementados

### 4.1 ✅ Crear Reserva para Vuelo Disponible
- Endpoint: `POST /api/reservas`
- Autenticación: Gestor
- Validaciones completas
- Respuesta: 201 (éxito), 400/404/409 (error)
- Efectos: Crea reserva, reduce asientos disponibles

### 4.2 ✅ Consultar Reservas Existentes
- Endpoint: `GET /api/reservas`
- Autenticación: Cualquier usuario autenticado
- Devuelve lista completa de reservas
- Respuesta: 200 con array de reservas

### 4.3 ✅ Consultar Reservas por Usuario
- Endpoint 1: `GET /api/reservas?usuario_id={id}`
- Endpoint 2: `GET /api/reservas/usuario/{id}`
- Autenticación: Cualquier usuario autenticado
- Filtra reservas por usuario específico
- Respuesta: 200 con array filtrado

### 4.4 ✅ Cancelar Reserva
- Endpoint: `DELETE /api/reservas/{id}`
- Autenticación: Gestor
- Validaciones completas
- Respuesta: 200 (éxito), 404/409 (error)
- Efectos: Cambia estado a "cancelada", libera asiento

### 4.5 ✅ Prevención de Reservas a Vuelos Inexistentes
- Valida existencia de vuelo en método create()
- Error 404 si vuelo no existe
- Previene referencias huérfanas

## Validaciones Implementadas

| Validación | Endpoint | Código | Mensaje |
|------------|----------|--------|---------|
| Datos incompletos | POST /api/reservas | 400 | "vuelo_id y numero_asiento son requeridos" |
| Vuelo inexistente | POST /api/reservas | 404 | "El vuelo especificado no existe" |
| Asiento ya reservado | POST /api/reservas | 409 | "El asiento ya está reservado en este vuelo" |
| Sin asientos disponibles | POST /api/reservas | 409 | "No hay asientos disponibles en este vuelo" |
| Reserva no encontrada | DELETE /api/reservas/{id} | 404 | "Reserva no encontrada" |
| Reserva no confirmada | DELETE /api/reservas/{id} | 409 | "No se puede cancelar una reserva que no está confirmada" |
| Sin token | Cualquier | 401 | "Token requerido" |
| No es gestor | POST/DELETE /api/reservas | 403 | "Acceso denegado. Solo gestores." |

## Flujo de Datos

```
Frontend                    Backend
   │                          │
   ├─ POST /api/reservas ────→│ ReservationController::create()
   │  (vuelo_id, asiento)     ├─ Validar vuelo existe
   │                          ├─ Validar asiento no reservado
   │                          ├─ Validar asientos disponibles
   │                          ├─ Crear reserva
   │                          ├─ Reducir asientos_disponibles
   │←─ 201 + reserva data ────│
   │                          │
   ├─ GET /api/reservas ─────→│ ReservationController::list()
   │  (?usuario_id=...)       ├─ Filtrar por usuario (opcional)
   │←─ 200 + reservas[] ──────│
   │                          │
   ├─ DELETE /api/reservas/{id}→│ ReservationController::cancel()
   │                          ├─ Validar reserva existe
   │                          ├─ Validar estado = confirmada
   │                          ├─ Cambiar estado a cancelada
   │                          ├─ Incrementar asientos_disponibles
   │←─ 200 + reserva ─────────│
```

## Códigos de Estado HTTP

| Código | Significado | Cuándo |
|--------|-------------|--------|
| 200 | OK | GET exitoso, DELETE exitoso |
| 201 | Created | POST (reserva creada) |
| 400 | Bad Request | Datos incompletos |
| 401 | Unauthorized | Sin token |
| 403 | Forbidden | No es gestor |
| 404 | Not Found | Vuelo/reserva no existe |
| 409 | Conflict | Asiento reservado, sin asientos, etc. |
| 500 | Server Error | Error en base de datos |

## Testing Recomendado

1. **Casos Exitosos:**
   - Crear reserva en vuelo disponible
   - Consultar todas las reservas
   - Consultar reservas de usuario específico
   - Cancelar reserva confirmada

2. **Casos de Error:**
   - Vuelo inexistente
   - Asiento ya reservado
   - Sin asientos disponibles
   - Reserva no encontrada
   - Acceso sin autenticación/permisos

3. **Verificaciones de Base de Datos:**
   - Asientos_disponibles se reduce/incrementa
   - Estado cambia a "cancelada" (no se elimina)
   - Foreign keys se respetan

Ver `PRUEBAS_RESERVAS.md` para pruebas detalladas con ejemplos de curl.

## Archivos de Referencia

- `GESTION_RESERVAS.md` - Documentación API completa
- `PRUEBAS_RESERVAS.md` - 12 casos de prueba detallados
- `RESTRICCIONES_ACCESO.md` - Control de acceso (gestores pueden crear/cancelar)
- `database.sql` - Esquema con tabla reservas
