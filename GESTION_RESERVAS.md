# Gestión de Reservas

## Resumen

La gestión de reservas es responsabilidad del **Gestor**. El sistema permite crear, consultar y cancelar reservas de vuelos con validaciones completas.

## 4. Gestión de Reservas (Responsabilidad del Gestor)

### 4.1 Crear una Reserva para Vuelo Disponible

**Endpoint:** `POST /api/reservas`

**Autenticación:** Requerida (gestor)

**Request:**
```json
{
  "vuelo_id": 1,
  "numero_asiento": "12A"
}
```

**Validaciones:**
- El vuelo_id y numero_asiento son requeridos
- El vuelo debe existir
- No debe haber reserva confirmada con el mismo asiento en el mismo vuelo
- Debe haber asientos disponibles (asientos_disponibles > 0)

**Response Éxito (201):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "usuario_id": 2,
    "vuelo_id": 1,
    "numero_asiento": "12A",
    "estado": "confirmada",
    "created_at": "2025-11-15T10:30:00",
    "updated_at": "2025-11-15T10:30:00"
  }
}
```

**Response Errores:**

- **400 - Datos Incompletos:**
```json
{
  "success": false,
  "error": "vuelo_id y numero_asiento son requeridos"
}
```

- **404 - Vuelo no Existe:**
```json
{
  "success": false,
  "error": "El vuelo especificado no existe"
}
```

- **409 - Asiento ya Reservado:**
```json
{
  "success": false,
  "error": "El asiento ya está reservado en este vuelo"
}
```

- **409 - Sin Asientos Disponibles:**
```json
{
  "success": false,
  "error": "No hay asientos disponibles en este vuelo"
}
```

**Efectos:**
- Crea una reserva con estado "confirmada"
- Reduce en 1 los asientos disponibles del vuelo
- El usuario_id se asigna automáticamente del token

### 4.2 Consultar Reservas Existentes

**Endpoint:** `GET /api/reservas`

**Autenticación:** Requerida (cualquier rol autenticado)

**Query Parameters (opcionales):**
- `usuario_id`: Filtrar por usuario específico

**Ejemplos:**
- `GET /api/reservas` - Todas las reservas
- `GET /api/reservas?usuario_id=2` - Reservas del usuario 2

**Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "usuario_id": 2,
      "vuelo_id": 1,
      "numero_asiento": "12A",
      "estado": "confirmada",
      "created_at": "2025-11-15T10:30:00",
      "updated_at": "2025-11-15T10:30:00"
    },
    {
      "id": 2,
      "usuario_id": 2,
      "vuelo_id": 1,
      "numero_asiento": "15B",
      "estado": "cancelada",
      "created_at": "2025-11-15T10:45:00",
      "updated_at": "2025-11-15T11:00:00"
    }
  ]
}
```

### 4.3 Consultar Reservas por Usuario

**Endpoint 1:** `GET /api/reservas?usuario_id={id}`

**Endpoint 2:** `GET /api/reservas/usuario/{id}`

**Autenticación:** Requerida

**Parámetro:**
- `id`: ID del usuario

**Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "usuario_id": 2,
      "vuelo_id": 1,
      "numero_asiento": "12A",
      "estado": "confirmada",
      "created_at": "2025-11-15T10:30:00"
    }
  ]
}
```

### 4.4 Cancelar una Reserva

**Endpoint:** `DELETE /api/reservas/{id}`

**Autenticación:** Requerida (gestor)

**Parámetro:**
- `id`: ID de la reserva a cancelar

**Validaciones:**
- La reserva debe existir
- La reserva debe estar en estado "confirmada"

**Response Éxito (200):**
```json
{
  "success": true,
  "message": "Reserva cancelada correctamente",
  "data": {
    "id": 1,
    "usuario_id": 2,
    "vuelo_id": 1,
    "numero_asiento": "12A",
    "estado": "cancelada",
    "updated_at": "2025-11-15T11:00:00"
  }
}
```

**Response Errores:**

- **404 - Reserva no Encontrada:**
```json
{
  "success": false,
  "error": "Reserva no encontrada"
}
```

- **409 - Reserva ya Cancelada:**
```json
{
  "success": false,
  "error": "No se puede cancelar una reserva que no está confirmada"
}
```

**Efectos:**
- Cambia el estado de "confirmada" a "cancelada"
- Incrementa en 1 los asientos disponibles del vuelo
- Mantiene el registro de la reserva (soft delete mediante estado)

### 4.5 Prevención de Reservas a Vuelos Inexistentes

Todas las operaciones de reserva validan que el vuelo exista:

```php
$flight = Flight::find($data['vuelo_id']);

if (!$flight) {
    // Error 404: El vuelo no existe
}
```

**Casos:**
- Crear reserva a vuelo inexistente → Error 404
- Crear reserva a vuelo eliminado (soft delete) → Error 404
- Cancelar reserva de vuelo eliminado → La reserva se marca como cancelada

## Flujo de Reserva

```
1. Gestor visualiza vuelos disponibles (GET /api/vuelos)
   ↓
2. Selecciona un vuelo y número de asiento
   ↓
3. Sistema crea reserva (POST /api/reservas)
   - Valida que vuelo existe
   - Valida que asiento está disponible
   - Valida que hay asientos libres
   ↓
4. Si éxito (201):
   - Crea registro de reserva con estado "confirmada"
   - Reduce asientos_disponibles del vuelo en 1
   - Devuelve datos de la reserva
   ↓
5. Gestor consulta sus reservas (GET /api/reservas?usuario_id={id})
   ↓
6. Gestor puede cancelar reserva (DELETE /api/reservas/{id})
   - Cambia estado a "cancelada"
   - Incrementa asientos_disponibles en 1
```

## Modelos de Base de Datos

### Tabla: reservas

```sql
CREATE TABLE reservas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    vuelo_id INT NOT NULL,
    numero_asiento VARCHAR(10) NOT NULL,
    estado ENUM('confirmada', 'cancelada') DEFAULT 'confirmada',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (vuelo_id) REFERENCES vuelos(id) ON DELETE CASCADE,
    UNIQUE KEY unique_reservation (vuelo_id, numero_asiento)
);
```

**Campos:**
- `id`: Identificador único
- `usuario_id`: Referencia al usuario que hace la reserva
- `vuelo_id`: Referencia al vuelo
- `numero_asiento`: Número/letra del asiento (ej: 12A, 5C)
- `estado`: "confirmada" o "cancelada"
- `created_at`: Fecha de creación
- `updated_at`: Fecha de última actualización

**Índices:**
- Clave única (vuelo_id, numero_asiento): Previene asientos duplicados
- Foreign keys: Integridad referencial

## Frontend Integration

### Crear Reserva

```javascript
// Usuario selecciona vuelo y número de asiento
const flightId = 1;
const numeroAsiento = prompt('Ingresa el número de asiento (ej: 12A):');

const response = await Reservations.create(flightId, numeroAsiento);

if (response.success) {
    alert('Reserva realizada exitosamente');
} else if (response.status === 409) {
    alert('Error: ' + response.data.error);  // Asiento ya reservado, etc.
} else if (response.status === 404) {
    alert('Error: El vuelo no existe');
}
```

### Listar Reservas del Usuario

```javascript
const usuarioId = Auth.getUserId();
const response = await Reservations.list(usuarioId);

// Mostrar reservas en UI
```

### Cancelar Reserva

```javascript
const reservationId = 1;

if (confirm('¿Estás seguro de que deseas cancelar esta reserva?')) {
    const response = await Reservations.cancel(reservationId);
    
    if (response.success) {
        alert('Reserva cancelada correctamente');
    }
}
```

## Códigos de Estado HTTP

| Código | Descripción |
|--------|-------------|
| 200 | Operación exitosa (GET, DELETE) |
| 201 | Reserva creada exitosamente |
| 400 | Datos incompletos o inválidos |
| 401 | Token faltante o inválido |
| 403 | Acceso denegado (no es gestor) |
| 404 | Vuelo o reserva no encontrado |
| 409 | Conflicto (asiento ya reservado, sin asientos disponibles, etc.) |
| 500 | Error interno del servidor |

## Ejemplo Completo de Flujo

### 1. Listar Vuelos
```bash
GET /api/vuelos
```

Response:
```json
[
  {
    "id": 1,
    "numero_vuelo": "AA101",
    "origen": "Madrid",
    "destino": "Barcelona",
    "fecha_salida": "2025-12-20 10:00:00",
    "asientos_disponibles": 5
  }
]
```

### 2. Crear Reserva
```bash
POST /api/reservas
Authorization: Bearer {token}

{
  "vuelo_id": 1,
  "numero_asiento": "12A"
}
```

Response:
```json
{
  "success": true,
  "data": {
    "id": 1,
    "usuario_id": 2,
    "vuelo_id": 1,
    "numero_asiento": "12A",
    "estado": "confirmada"
  }
}
```

### 3. Verificar Vuelo Actualizado
```bash
GET /api/vuelos/1
```

Response:
```json
{
  "id": 1,
  "numero_vuelo": "AA101",
  "asientos_disponibles": 4  // Reducido de 5 a 4
}
```

### 4. Listar Reservas del Usuario
```bash
GET /api/reservas?usuario_id=2
```

Response:
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "usuario_id": 2,
      "vuelo_id": 1,
      "numero_asiento": "12A",
      "estado": "confirmada"
    }
  ]
}
```

### 5. Cancelar Reserva
```bash
DELETE /api/reservas/1
```

Response:
```json
{
  "success": true,
  "message": "Reserva cancelada correctamente",
  "data": {
    "id": 1,
    "estado": "cancelada"
  }
}
```

### 6. Verificar Vuelo (Asientos Liberados)
```bash
GET /api/vuelos/1
```

Response:
```json
{
  "id": 1,
  "numero_vuelo": "AA101",
  "asientos_disponibles": 5  // Vuelto a 5
}
```
