# Gestión de Vuelos (2.1-2.5)

## Descripción General

El sistema de gestión de vuelos permite a los administradores registrar, consultar, buscar, modificar y eliminar información de vuelos. Cada vuelo está asociado a una nave específica y es la base para las reservas de pasajeros.

**Responsabilidad:** Administrador

---

## 2.1 Registrar Nuevos Vuelos

### Requisito
El sistema debe permitir registrar vuelos con validación completa de datos.

### Endpoint
```
POST /api/vuelos
```

### Autenticación
- **Requerida:** Bearer Token (administrador)
- **Header:** `Authorization: Bearer {token}`

### Body Request
```json
{
  "numero_vuelo": "AA1000",
  "nave_id": 1,
  "origen": "Madrid",
  "destino": "Barcelona",
  "fecha_salida": "2025-12-15 08:00:00",
  "fecha_llegada": "2025-12-15 10:30:00"
}
```

### Validaciones Implementadas

| Validación | Descripción | Código | Mensaje |
|------------|-------------|--------|---------|
| Datos requeridos | Todos los campos son obligatorios | 400 | "numero_vuelo, nave_id, origen, destino, fecha_salida y fecha_llegada son requeridos" |
| Origen ≠ Destino | Origen y destino no pueden ser iguales | 400 | "El origen y destino no pueden ser iguales" |
| Fechas válidas | Fecha llegada posterior a salida | 400 | "La fecha de llegada debe ser posterior a la fecha de salida" |
| Nave válida | Nave debe existir en el sistema | 404 | "La nave especificada no existe" |
| Número único | numero_vuelo debe ser único | 409 | "Ya existe un vuelo con ese número" |
| Auto-asignación | asientos_disponibles = capacidad de la nave | 201 | - |

### Response Success (201)
```json
{
  "success": true,
  "data": {
    "id": 1,
    "numero_vuelo": "AA1000",
    "nave_id": 1,
    "origen": "Madrid",
    "destino": "Barcelona",
    "fecha_salida": "2025-12-15 08:00:00",
    "fecha_llegada": "2025-12-15 10:30:00",
    "asientos_disponibles": 150,
    "created_at": "2025-11-15 10:30:00",
    "updated_at": "2025-11-15 10:30:00"
  }
}
```

### Response Errors

**400 Bad Request** - Datos incompletos
```json
{
  "success": false,
  "error": "numero_vuelo, nave_id, origen, destino, fecha_salida y fecha_llegada son requeridos"
}
```

**400 Bad Request** - Origen igual a destino
```json
{
  "success": false,
  "error": "El origen y destino no pueden ser iguales"
}
```

**400 Bad Request** - Fechas inválidas
```json
{
  "success": false,
  "error": "La fecha de llegada debe ser posterior a la fecha de salida"
}
```

**404 Not Found** - Nave no existe
```json
{
  "success": false,
  "error": "La nave especificada no existe"
}
```

**409 Conflict** - Número de vuelo duplicado
```json
{
  "success": false,
  "error": "Ya existe un vuelo con ese número"
}
```

**401 Unauthorized** - Token no válido
```json
{
  "success": false,
  "error": "Token requerido"
}
```

**403 Forbidden** - No es administrador
```json
{
  "success": false,
  "error": "Acceso denegado. Solo administradores."
}
```

### Ejemplo de Uso (JavaScript)

```javascript
const vuelo = {
  numero_vuelo: "AA1000",
  nave_id: 1,
  origen: "Madrid",
  destino: "Barcelona",
  fecha_salida: "2025-12-15 08:00:00",
  fecha_llegada: "2025-12-15 10:30:00"
};

const response = await Flights.create(vuelo);
if (response.success) {
  console.log("Vuelo creado:", response.data.data);
} else {
  console.error("Error:", response.data.error);
}
```

### Ejemplo con cURL

```bash
curl -X POST "http://localhost/Gestion_vuelos_reservas/microservicio_vuelos/public/api/vuelos" \
  -H "Authorization: Bearer token_aqui" \
  -H "Content-Type: application/json" \
  -d '{
    "numero_vuelo": "AA1000",
    "nave_id": 1,
    "origen": "Madrid",
    "destino": "Barcelona",
    "fecha_salida": "2025-12-15 08:00:00",
    "fecha_llegada": "2025-12-15 10:30:00"
  }'
```

---

## 2.2 Consultar Todos los Vuelos Registrados

### Requisito
El sistema debe permitir consultar la lista completa de vuelos registrados.

### Endpoint
```
GET /api/vuelos
```

### Autenticación
- **No requerida** para listar (público)
- Sin embargo, pueden ser consultados también por usuarios autenticados

### Query Parameters
- Ninguno (para obtener todos)

### Response Success (200)
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "numero_vuelo": "AA1000",
      "nave_id": 1,
      "origen": "Madrid",
      "destino": "Barcelona",
      "fecha_salida": "2025-12-15 08:00:00",
      "fecha_llegada": "2025-12-15 10:30:00",
      "asientos_disponibles": 150,
      "created_at": "2025-11-15 10:30:00",
      "updated_at": "2025-11-15 10:30:00"
    },
    {
      "id": 2,
      "numero_vuelo": "IB2000",
      "nave_id": 2,
      "origen": "Valencia",
      "destino": "Sevilla",
      "fecha_salida": "2025-12-16 14:00:00",
      "fecha_llegada": "2025-12-16 16:45:00",
      "asientos_disponibles": 200,
      "created_at": "2025-11-15 10:32:00",
      "updated_at": "2025-11-15 10:32:00"
    }
  ]
}
```

### Ejemplo de Uso (JavaScript)

```javascript
const response = await Flights.list();
if (response.success) {
  console.log("Vuelos:", response.data.data);
} else {
  console.error("Error:", response.data.error);
}
```

### Ejemplo con cURL

```bash
curl -X GET "http://localhost/Gestion_vuelos_reservas/microservicio_vuelos/public/api/vuelos"
```

---

## 2.3 Buscar Vuelos por Origen, Destino o Fecha

### Requisito
El sistema debe permitir filtrar vuelos por origen, destino o fecha de salida.

### Endpoint
```
GET /api/vuelos
```

### Autenticación
- **No requerida** (público)

### Query Parameters

| Parámetro | Tipo | Descripción | Ejemplo |
|-----------|------|-------------|---------|
| `origen` | string | Busca vuelos con origen que contenga este valor (case-insensitive) | `origen=Madrid` |
| `destino` | string | Busca vuelos con destino que contenga este valor (case-insensitive) | `destino=Barcelona` |
| `fecha` | date | Busca vuelos con fecha_salida exacta (YYYY-MM-DD) | `fecha=2025-12-15` |
| `fecha_desde` | date | Busca vuelos con fecha_salida >= (YYYY-MM-DD) | `fecha_desde=2025-12-15` |
| `fecha_hasta` | date | Busca vuelos con fecha_salida <= (YYYY-MM-DD) | `fecha_hasta=2025-12-20` |

### Ejemplos de Búsqueda

**Buscar por origen:**
```
GET /api/vuelos?origen=Madrid
```

**Buscar por destino:**
```
GET /api/vuelos?destino=Barcelona
```

**Buscar por fecha exacta:**
```
GET /api/vuelos?fecha=2025-12-15
```

**Buscar por rango de fechas:**
```
GET /api/vuelos?fecha_desde=2025-12-15&fecha_hasta=2025-12-20
```

**Buscar con múltiples criterios:**
```
GET /api/vuelos?origen=Madrid&destino=Barcelona&fecha=2025-12-15
```

### Response Success (200)
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "numero_vuelo": "AA1000",
      "nave_id": 1,
      "origen": "Madrid",
      "destino": "Barcelona",
      "fecha_salida": "2025-12-15 08:00:00",
      "fecha_llegada": "2025-12-15 10:30:00",
      "asientos_disponibles": 150,
      "created_at": "2025-11-15 10:30:00",
      "updated_at": "2025-11-15 10:30:00"
    }
  ]
}
```

### Ejemplo de Uso (JavaScript)

```javascript
// Buscar por origen
const response1 = await Flights.search("Madrid");

// Buscar por origen y destino
const response2 = await Flights.search("Madrid", "Barcelona");

// Buscar por rango de fechas
const response3 = await Flights.searchByDateRange("2025-12-15", "2025-12-20");

// Uso avanzado con filtros personalizados
const response4 = await Flights.list({
  origen: "Madrid",
  destino: "Barcelona",
  fecha_desde: "2025-12-15",
  fecha_hasta: "2025-12-20"
});
```

### Ejemplo con cURL

```bash
# Buscar por origen
curl -X GET "http://localhost/Gestion_vuelos_reservas/microservicio_vuelos/public/api/vuelos?origen=Madrid"

# Buscar por origen y destino
curl -X GET "http://localhost/Gestion_vuelos_reservas/microservicio_vuelos/public/api/vuelos?origen=Madrid&destino=Barcelona"

# Buscar por rango de fechas
curl -X GET "http://localhost/Gestion_vuelos_reservas/microservicio_vuelos/public/api/vuelos?fecha_desde=2025-12-15&fecha_hasta=2025-12-20"
```

---

## 2.4 Modificar Información de un Vuelo

### Requisito
El sistema debe permitir actualizar datos de un vuelo registrado con validación.

### Endpoint
```
PUT /api/vuelos/{id}
```

### Autenticación
- **Requerida:** Bearer Token (administrador)
- **Header:** `Authorization: Bearer {token}`

### Path Parameters
- `id` - ID del vuelo a modificar

### Body Request (Campos Opcionales)
```json
{
  "numero_vuelo": "AA1001",
  "origen": "Madrid",
  "destino": "Valencia",
  "fecha_salida": "2025-12-15 09:00:00",
  "fecha_llegada": "2025-12-15 11:00:00",
  "nave_id": 2
}
```

### Validaciones Implementadas

| Validación | Descripción | Código | Mensaje |
|------------|-------------|--------|---------|
| Vuelo existe | El vuelo debe existir | 404 | "Vuelo no encontrado" |
| Número único | Si se cambia, numero_vuelo debe ser único | 409 | "Ya existe otro vuelo con ese número" |
| Origen ≠ Destino | Origen y destino no pueden ser iguales | 400 | "El origen y destino no pueden ser iguales" |
| Fechas válidas | Fecha llegada posterior a salida | 400 | "La fecha de llegada debe ser posterior a la fecha de salida" |
| Nave válida | Si se cambia, nave debe existir | 404 | "La nave especificada no existe" |
| Auto-actualización | Si cambia nave, actualiza asientos_disponibles | 200 | - |

### Response Success (200)
```json
{
  "success": true,
  "data": {
    "id": 1,
    "numero_vuelo": "AA1001",
    "nave_id": 2,
    "origen": "Madrid",
    "destino": "Valencia",
    "fecha_salida": "2025-12-15 09:00:00",
    "fecha_llegada": "2025-12-15 11:00:00",
    "asientos_disponibles": 200,
    "created_at": "2025-11-15 10:30:00",
    "updated_at": "2025-11-15 11:00:00"
  }
}
```

### Response Errors

**404 Not Found** - Vuelo no existe
```json
{
  "success": false,
  "error": "Vuelo no encontrado"
}
```

**409 Conflict** - Número de vuelo duplicado
```json
{
  "success": false,
  "error": "Ya existe otro vuelo con ese número"
}
```

**400 Bad Request** - Validaciones fallidas
```json
{
  "success": false,
  "error": "El origen y destino no pueden ser iguales"
}
```

### Ejemplo de Uso (JavaScript)

```javascript
const actualizacion = {
  origen: "Madrid",
  destino: "Valencia",
  fecha_salida: "2025-12-15 09:00:00"
};

const response = await Flights.update(1, actualizacion);
if (response.success) {
  console.log("Vuelo actualizado:", response.data.data);
} else {
  console.error("Error:", response.data.error);
}
```

### Ejemplo con cURL

```bash
curl -X PUT "http://localhost/Gestion_vuelos_reservas/microservicio_vuelos/public/api/vuelos/1" \
  -H "Authorization: Bearer token_aqui" \
  -H "Content-Type: application/json" \
  -d '{
    "origen": "Madrid",
    "destino": "Valencia"
  }'
```

---

## 2.5 Eliminar un Vuelo

### Requisito
El sistema debe permitir eliminar un vuelo con protección de integridad de datos.

### Endpoint
```
DELETE /api/vuelos/{id}
```

### Autenticación
- **Requerida:** Bearer Token (administrador)
- **Header:** `Authorization: Bearer {token}`

### Path Parameters
- `id` - ID del vuelo a eliminar

### Validaciones Implementadas

| Validación | Descripción | Código | Mensaje |
|------------|-------------|--------|---------|
| Vuelo existe | El vuelo debe existir | 404 | "Vuelo no encontrado" |
| Sin reservas | No puede haber reservas confirmadas | 409 | "No se puede eliminar un vuelo que tiene reservas confirmadas. Cancele las reservas primero." |

### Response Success (200)
```json
{
  "success": true,
  "message": "Vuelo eliminado"
}
```

### Response Errors

**404 Not Found** - Vuelo no existe
```json
{
  "success": false,
  "error": "Vuelo no encontrado"
}
```

**409 Conflict** - Tiene reservas confirmadas
```json
{
  "success": false,
  "error": "No se puede eliminar un vuelo que tiene reservas confirmadas. Cancele las reservas primero."
}
```

### Ejemplo de Uso (JavaScript)

```javascript
const response = await Flights.delete(1);
if (response.success) {
  console.log("Vuelo eliminado");
} else {
  console.error("Error:", response.data.error);
}
```

### Ejemplo con cURL

```bash
curl -X DELETE "http://localhost/Gestion_vuelos_reservas/microservicio_vuelos/public/api/vuelos/1" \
  -H "Authorization: Bearer token_aqui"
```

---

## Flujo Completo de Gestión de Vuelos

```
┌─────────────────────────────────────────────────────────────┐
│           ADMIN - GESTIÓN DE VUELOS                         │
├─────────────────────────────────────────────────────────────┤
│                                                               │
│  1. REGISTRAR VUELO (2.1)                                   │
│     POST /api/vuelos                                         │
│     ├─ Validar datos completos                              │
│     ├─ Validar origen ≠ destino                             │
│     ├─ Validar fecha_llegada > fecha_salida                 │
│     ├─ Validar nave existe                                  │
│     ├─ Validar numero_vuelo es único                        │
│     ├─ Auto-asignar asientos_disponibles = capacidad_nave   │
│     └─ Crear vuelo (201)                                     │
│                                                               │
│  2. CONSULTAR VUELOS (2.2)                                  │
│     GET /api/vuelos                                          │
│     └─ Devolver lista completa                              │
│                                                               │
│  3. BUSCAR VUELOS (2.3)                                     │
│     GET /api/vuelos?origen=X&destino=Y&fecha=Z             │
│     ├─ Búsqueda por origen (LIKE)                           │
│     ├─ Búsqueda por destino (LIKE)                          │
│     ├─ Búsqueda por fecha exacta (YYYY-MM-DD)              │
│     ├─ Búsqueda por rango de fechas                         │
│     └─ Devolver resultados filtrados                        │
│                                                               │
│  4. MODIFICAR VUELO (2.4)                                   │
│     PUT /api/vuelos/{id}                                    │
│     ├─ Validar vuelo existe                                 │
│     ├─ Validar cambios (número, origen, destino, fechas)   │
│     ├─ Validar nave si se cambia                            │
│     ├─ Si cambia nave, actualizar asientos_disponibles      │
│     └─ Devolver vuelo actualizado                           │
│                                                               │
│  5. ELIMINAR VUELO (2.5)                                    │
│     DELETE /api/vuelos/{id}                                 │
│     ├─ Validar vuelo existe                                 │
│     ├─ Validar NO hay reservas confirmadas                  │
│     ├─ Si hay reservas: Error 409                           │
│     └─ Eliminar vuelo (200) o Error (409)                   │
│                                                               │
└─────────────────────────────────────────────────────────────┘
```

---

## Modelo de Base de Datos

### Tabla: vuelos

```sql
CREATE TABLE vuelos (
  id INT PRIMARY KEY AUTO_INCREMENT,
  numero_vuelo VARCHAR(50) UNIQUE NOT NULL,
  nave_id INT NOT NULL,
  origen VARCHAR(100) NOT NULL,
  destino VARCHAR(100) NOT NULL,
  fecha_salida DATETIME NOT NULL,
  fecha_llegada DATETIME NOT NULL,
  asientos_disponibles INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (nave_id) REFERENCES naves(id)
);
```

### Restricciones

- `numero_vuelo`: Único en toda la tabla (no puede haber dos vuelos con el mismo número)
- `nave_id`: Referencia a tabla `naves` (Foreign Key)
- `fecha_llegada > fecha_salida`: Validado en aplicación
- `origen ≠ destino`: Validado en aplicación
- `asientos_disponibles`: Se auto-asigna desde `naves.capacidad`

### Relaciones

```
naves (1) ──── (N) vuelos (1) ──── (N) reservas
  │                    │
  │                    └─ asientos_disponibles = capacidad
  └─ On Delete: Cascade
```

---

## Códigos de Estado HTTP

| Código | Significado | Casos de Uso |
|--------|-------------|-------------|
| 200 | OK | GET, PUT, DELETE exitosos |
| 201 | Created | POST exitoso (vuelo creado) |
| 400 | Bad Request | Datos inválidos, validaciones fallidas |
| 401 | Unauthorized | Token no válido o expirado |
| 403 | Forbidden | No tiene permisos (no es admin) |
| 404 | Not Found | Vuelo no existe, nave no existe |
| 409 | Conflict | Número vuelo duplicado, tiene reservas |
| 500 | Server Error | Error interno del servidor |

---

## Seguridad y Validaciones

### Autenticación y Autorización
- ✅ Todas las operaciones CRUD requieren token de administrador
- ✅ Token se valida en cada petición
- ✅ Si no es admin, retorna 403 Forbidden

### Integridad de Datos
- ✅ numero_vuelo es único
- ✅ origen ≠ destino
- ✅ fecha_llegada > fecha_salida
- ✅ nave_id válido y existente
- ✅ No se puede eliminar vuelo con reservas confirmadas

### Validaciones de Entrada
- ✅ Todos los campos requeridos
- ✅ Tipos de datos correctos
- ✅ Valores dentro de rangos válidos
- ✅ Sanitización de búsquedas

---

## Ejemplos de Workflow Completo

### Scenario 1: Crear y buscar vuelo

```javascript
// 1. Crear un vuelo
const nuevoVuelo = {
  numero_vuelo: "AA1000",
  nave_id: 1,
  origen: "Madrid",
  destino: "Barcelona",
  fecha_salida: "2025-12-15 08:00:00",
  fecha_llegada: "2025-12-15 10:30:00"
};

const crearResponse = await Flights.create(nuevoVuelo);
// Vuelo creado con asientos_disponibles = 150 (capacidad de nave 1)

// 2. Buscar vuelos de Madrid a Barcelona en esa fecha
const buscarResponse = await Flights.search("Madrid", "Barcelona");
console.log(buscarResponse.data.data); // Array con el vuelo

// 3. Buscar vuelos en rango de fechas
const buscarRangoResponse = await Flights.searchByDateRange("2025-12-15", "2025-12-20");
console.log(buscarRangoResponse.data.data);
```

### Scenario 2: Modificar y eliminar vuelo

```javascript
// 1. Obtener vuelo específico
const getResponse = await Flights.getById(1);
const vuelo = getResponse.data.data;

// 2. Modificar origen
const updateResponse = await Flights.update(1, { origen: "Valencia" });

// 3. Intentar eliminar (fallará si hay reservas)
const deleteResponse = await Flights.delete(1);
if (!deleteResponse.success) {
  console.log("Advertencia:", deleteResponse.data.error);
  // "No se puede eliminar un vuelo que tiene reservas confirmadas..."
}
```

---

## Notas Importantes

1. **Auto-asignación de asientos:**
   - Cuando se crea un vuelo, `asientos_disponibles` se asigna automáticamente desde `capacidad` de la nave
   - Si se cambia la nave en la actualización, también se actualiza `asientos_disponibles`

2. **Protección de integridad:**
   - Un vuelo no puede eliminarse si tiene reservas confirmadas
   - Primero deben cancelarse todas las reservas

3. **Búsquedas flexibles:**
   - Las búsquedas por origen/destino son case-insensitive y usan LIKE
   - Permite búsquedas parciales ("mad" encontrará "Madrid")

4. **Validación de fechas:**
   - Las fechas deben estar en formato YYYY-MM-DD HH:MM:SS
   - La fecha de llegada siempre debe ser posterior a la de salida

5. **Número de vuelo único:**
   - Cada vuelo tiene un número único (ej: AA1000, IB2000)
   - No pueden existir dos vuelos con el mismo número

