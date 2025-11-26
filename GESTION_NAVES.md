# Gestión de Naves (Aeronaves)

## Resumen

La gestión de naves es responsabilidad del **Administrador**. El sistema permite registrar, consultar, modificar y eliminar naves (aeronaves) con validaciones completas.

## 3. Gestión de Naves (Aeronaves) (Responsabilidad del Administrador)

### 3.1 Registrar Nueva Nave

**Endpoint:** `POST /api/naves`

**Autenticación:** Requerida (administrador)

**Request:**
```json
{
  "modelo": "Boeing 787 Dreamliner",
  "capacidad": 242,
  "matricula": "N787BA"
}
```

**Parámetros Requeridos:**
- `modelo` (string): Modelo de la aeronave
- `capacidad` (number): Capacidad de pasajeros (debe ser positivo)
- `matricula` (string): Matrícula única de la aeronave

**Validaciones:**
- Los tres campos son requeridos
- La capacidad debe ser un número positivo (> 0)
- La matrícula debe ser única (no puede duplicarse)

**Response Éxito (201):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "modelo": "Boeing 787 Dreamliner",
    "capacidad": 242,
    "matricula": "N787BA",
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
  "error": "modelo, capacidad y matricula son requeridos"
}
```

- **400 - Capacidad Inválida:**
```json
{
  "success": false,
  "error": "La capacidad debe ser un número positivo"
}
```

- **409 - Matrícula Duplicada:**
```json
{
  "success": false,
  "error": "Ya existe una nave con esa matrícula"
}
```

### 3.2 Consultar Naves Disponibles

**Endpoint 1:** `GET /api/naves`

**Endpoint 2:** `GET /api/naves/{id}`

**Autenticación:** Requerida (administrador)

**Parámetro (Endpoint 2):**
- `id`: ID de la nave específica

**Response - Listar Todas (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "modelo": "Boeing 787 Dreamliner",
      "capacidad": 242,
      "matricula": "N787BA",
      "created_at": "2025-11-15T10:30:00"
    },
    {
      "id": 2,
      "modelo": "Airbus A380",
      "capacidad": 555,
      "matricula": "N380AA",
      "created_at": "2025-11-15T10:35:00"
    }
  ]
}
```

**Response - Nave Específica (200):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "modelo": "Boeing 787 Dreamliner",
    "capacidad": 242,
    "matricula": "N787BA",
    "created_at": "2025-11-15T10:30:00"
  }
}
```

**Response - No Encontrada (404):**
```json
{
  "success": false,
  "error": "Nave no encontrada"
}
```

### 3.3 Modificar Información de una Nave

**Endpoint:** `PUT /api/naves/{id}`

**Autenticación:** Requerida (administrador)

**Parámetro:**
- `id`: ID de la nave a modificar

**Request (cualquiera de estos campos):**
```json
{
  "modelo": "Boeing 787-10 Dreamliner",
  "capacidad": 330,
  "matricula": "N787BB"
}
```

**Validaciones:**
- La nave debe existir
- Si se actualiza capacidad, debe ser un número positivo
- Si se actualiza matrícula, debe ser única

**Response Éxito (200):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "modelo": "Boeing 787-10 Dreamliner",
    "capacidad": 330,
    "matricula": "N787BB",
    "updated_at": "2025-11-15T11:00:00"
  }
}
```

**Response Errores:**

- **404 - Nave no Encontrada:**
```json
{
  "success": false,
  "error": "Nave no encontrada"
}
```

- **400 - Capacidad Inválida:**
```json
{
  "success": false,
  "error": "La capacidad debe ser un número positivo"
}
```

- **409 - Matrícula Duplicada:**
```json
{
  "success": false,
  "error": "Ya existe una nave con esa matrícula"
}
```

### 3.4 Eliminar una Nave

**Endpoint:** `DELETE /api/naves/{id}`

**Autenticación:** Requerida (administrador)

**Parámetro:**
- `id`: ID de la nave a eliminar

**Validaciones:**
- La nave debe existir
- No puede haber vuelos asociados a la nave

**Response Éxito (200):**
```json
{
  "success": true,
  "message": "Nave eliminada correctamente"
}
```

**Response Errores:**

- **404 - Nave no Encontrada:**
```json
{
  "success": false,
  "error": "Nave no encontrada"
}
```

- **409 - Nave con Vuelos Asociados:**
```json
{
  "success": false,
  "error": "No se puede eliminar una nave que tiene vuelos asociados. Elimina primero los vuelos."
}
```

### 3.5 Asociación de Vuelos a Naves

Cada vuelo debe estar asociado a una nave. Esta asociación se garantiza mediante:

1. **Al crear un vuelo:**
   - Se valida que `nave_id` sea requerido
   - Se valida que la nave existe
   - Se asignan automáticamente `asientos_disponibles` basado en la capacidad de la nave

2. **Integridad Referencial:**
   - Foreign key `vuelos.nave_id` -> `naves.id`
   - Cascade delete: Si se elimina una nave, se eliminan sus vuelos (opcional según política)

3. **Validación en Eliminación:**
   - No se puede eliminar una nave que tiene vuelos activos
   - Se debe eliminar/reasignar los vuelos primero

## Flujo de Gestión de Naves

```
1. Admin crea una nave
   POST /api/naves
   - Valida datos
   - Verifica matrícula única
   - Crea nave
   ↓
2. Admin visualiza naves
   GET /api/naves
   ↓
3. Admin crea vuelo asociado a la nave
   POST /api/vuelos
   - Valida que nave existe
   - Asigna capacidad de la nave como asientos_disponibles
   ↓
4. Admin puede modificar nave
   PUT /api/naves/{id}
   - Valida cambios
   ↓
5. Al intentar eliminar nave con vuelos
   DELETE /api/naves/{id}
   - Error 409: No se puede eliminar
   ↓
6. Admin elimina vuelos primero, luego nave
   DELETE /api/vuelos/{id}
   DELETE /api/naves/{id} // Ahora éxito
```

## Modelos de Base de Datos

### Tabla: naves

```sql
CREATE TABLE naves (
    id INT AUTO_INCREMENT PRIMARY KEY,
    modelo VARCHAR(100) NOT NULL,
    capacidad INT NOT NULL,
    matricula VARCHAR(50) UNIQUE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

**Campos:**
- `id`: Identificador único
- `modelo`: Modelo de la aeronave (ej: Boeing 787, Airbus A380)
- `capacidad`: Número máximo de pasajeros
- `matricula`: Identificador único de la aeronave (ej: N787BA)
- `created_at`: Fecha de creación
- `updated_at`: Fecha de última modificación

**Índices:**
- Primary key en `id`
- Unique key en `matricula`

### Tabla: vuelos (relación con naves)

```sql
CREATE TABLE vuelos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numero_vuelo VARCHAR(20) UNIQUE NOT NULL,
    nave_id INT NOT NULL,  -- FK a naves
    origen VARCHAR(100) NOT NULL,
    destino VARCHAR(100) NOT NULL,
    fecha_salida DATETIME NOT NULL,
    fecha_llegada DATETIME NOT NULL,
    asientos_disponibles INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (nave_id) REFERENCES naves(id) ON DELETE CASCADE
);
```

**Foreign Key:**
- `nave_id` referencia a `naves.id`
- `ON DELETE CASCADE`: Eliminar vuelos si se elimina la nave (opcional)

## Rutas de API

| Método | Ruta | Descripción | Autenticación |
|--------|------|-------------|----------------|
| GET | `/api/naves` | Listar todas las naves | Admin |
| GET | `/api/naves/{id}` | Obtener nave específica | Admin |
| POST | `/api/naves` | Crear nueva nave (3.1) | Admin |
| PUT | `/api/naves/{id}` | Modificar nave (3.3) | Admin |
| DELETE | `/api/naves/{id}` | Eliminar nave (3.4) | Admin |

## Códigos de Estado HTTP

| Código | Descripción |
|--------|-------------|
| 200 | Operación exitosa (GET, PUT, DELETE) |
| 201 | Nave creada exitosamente |
| 400 | Datos incompletos o inválidos |
| 401 | Token faltante o inválido |
| 403 | Acceso denegado (no es admin) |
| 404 | Nave no encontrada |
| 409 | Conflicto (matrícula duplicada, nave con vuelos) |
| 500 | Error interno del servidor |

## Ejemplo Completo de Flujo

### 1. Crear Nave
```bash
POST /api/naves
Authorization: Bearer {admin_token}

{
  "modelo": "Boeing 787 Dreamliner",
  "capacidad": 242,
  "matricula": "N787BA"
}
```

Response: 201
```json
{
  "success": true,
  "data": {
    "id": 1,
    "modelo": "Boeing 787 Dreamliner",
    "capacidad": 242,
    "matricula": "N787BA"
  }
}
```

### 2. Listar Naves
```bash
GET /api/naves
Authorization: Bearer {admin_token}
```

Response: 200
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "modelo": "Boeing 787 Dreamliner",
      "capacidad": 242,
      "matricula": "N787BA"
    }
  ]
}
```

### 3. Crear Vuelo (asociado a nave)
```bash
POST /api/vuelos
Authorization: Bearer {admin_token}

{
  "numero_vuelo": "AA101",
  "nave_id": 1,
  "origen": "Madrid",
  "destino": "Barcelona",
  "fecha_salida": "2025-12-20 10:00:00",
  "fecha_llegada": "2025-12-20 12:00:00"
}
```

Response: 201
```json
{
  "success": true,
  "data": {
    "id": 1,
    "numero_vuelo": "AA101",
    "nave_id": 1,
    "asientos_disponibles": 242,  // Automaticamente asignado
    "origen": "Madrid",
    "destino": "Barcelona"
  }
}
```

### 4. Modificar Nave
```bash
PUT /api/naves/1
Authorization: Bearer {admin_token}

{
  "capacidad": 250
}
```

Response: 200
```json
{
  "success": true,
  "data": {
    "id": 1,
    "modelo": "Boeing 787 Dreamliner",
    "capacidad": 250,
    "matricula": "N787BA",
    "updated_at": "2025-11-15T11:30:00"
  }
}
```

### 5. Intentar Eliminar Nave (con vuelos)
```bash
DELETE /api/naves/1
Authorization: Bearer {admin_token}
```

Response: 409
```json
{
  "success": false,
  "error": "No se puede eliminar una nave que tiene vuelos asociados. Elimina primero los vuelos."
}
```

### 6. Eliminar Vuelo Primero
```bash
DELETE /api/vuelos/1
Authorization: Bearer {admin_token}
```

Response: 200 OK

### 7. Ahora Eliminar Nave
```bash
DELETE /api/naves/1
Authorization: Bearer {admin_token}
```

Response: 200
```json
{
  "success": true,
  "message": "Nave eliminada correctamente"
}
```

## Relación con Vuelos y Reservas

```
Naves (1) ──── (N) Vuelos (1) ──── (N) Reservas
   ↓                  ↓
 Boeing 787    →  Vuelo AA101   →  Reserva 1 (asiento 12A)
 242 pasajeros    Madrid→Barcelona   Reserva 2 (asiento 15B)
                   240 asientos          ...
                   disponibles
```

**Cuando se crea un vuelo:**
- Se asocia a una nave (validado)
- Se asignan asientos_disponibles = capacidad de la nave

**Cuando se crea una reserva:**
- Se reduce asientos_disponibles del vuelo
- Se mantiene integridad con validación de nave existente

**Cuando se elimina una nave:**
- No se permite si tiene vuelos activos
- Garantiza que no hay reservas huérfanas
