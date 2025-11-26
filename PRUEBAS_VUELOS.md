# Pruebas - Gestión de Vuelos (2.1-2.5)

## Configuración de Pruebas

### Requisitos Previos
1. Base de datos creada y tablas inicializadas
2. Tabla `naves` con al menos 2 naves registradas
3. Microservicio de vuelos ejecutándose en `http://localhost/Gestion_vuelos_reservas/microservicio_vuelos/public`
4. Token de administrador válido (obtenido por login)
5. Herramienta: Postman, Insomnia, cURL, o similar

### Variables de Entorno (Postman/Insomnia)
```
{{base_url}} = http://localhost/Gestion_vuelos_reservas/microservicio_vuelos/public
{{admin_token}} = [Token obtenido al hacer login como admin]
{{flight_id}} = [ID del vuelo creado en pruebas]
{{nave_id}} = [ID de una nave válida]
```

### Configuración de Base de Datos
```sql
-- Asegurar naves disponibles
SELECT id, modelo, capacidad, matricula FROM naves;

-- Tabla vuelos debe estar vacía antes de pruebas
TRUNCATE TABLE vuelos;
TRUNCATE TABLE reservas;
```

---

## Caso de Prueba 1: Crear Vuelo Exitoso (2.1)

### Descripción
Validar que se puede crear un vuelo con todos los datos requeridos.

### Request
```bash
curl -X POST "http://localhost/Gestion_vuelos_reservas/microservicio_vuelos/public/api/vuelos" \
  -H "Authorization: Bearer {{admin_token}}" \
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

### Respuesta Esperada (201)
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
    "created_at": "2025-11-15 14:00:00",
    "updated_at": "2025-11-15 14:00:00"
  }
}
```

### Validaciones
- ✅ Status HTTP: 201 Created
- ✅ success = true
- ✅ Vuelo creado con ID asignado
- ✅ asientos_disponibles = capacidad de la nave (150)
- ✅ Timestamps generados automáticamente

### Verificación en BD
```sql
SELECT * FROM vuelos WHERE numero_vuelo = 'AA1000';
```

---

## Caso de Prueba 2: Error - Datos Incompletos (2.1)

### Descripción
Validar que falta un campo requerido retorna error 400.

### Request
```bash
curl -X POST "http://localhost/Gestion_vuelos_reservas/microservicio_vuelos/public/api/vuelos" \
  -H "Authorization: Bearer {{admin_token}}" \
  -H "Content-Type: application/json" \
  -d '{
    "numero_vuelo": "AA1001",
    "nave_id": 1,
    "origen": "Madrid",
    "destino": "Barcelona"
  }'
```

### Respuesta Esperada (400)
```json
{
  "success": false,
  "error": "numero_vuelo, nave_id, origen, destino, fecha_salida y fecha_llegada son requeridos"
}
```

### Validaciones
- ✅ Status HTTP: 400 Bad Request
- ✅ success = false
- ✅ Mensaje claro del error

---

## Caso de Prueba 3: Error - Origen Igual a Destino (2.1)

### Descripción
Validar que origen y destino no pueden ser iguales.

### Request
```bash
curl -X POST "http://localhost/Gestion_vuelos_reservas/microservicio_vuelos/public/api/vuelos" \
  -H "Authorization: Bearer {{admin_token}}" \
  -H "Content-Type: application/json" \
  -d '{
    "numero_vuelo": "AA1002",
    "nave_id": 1,
    "origen": "Madrid",
    "destino": "Madrid",
    "fecha_salida": "2025-12-15 08:00:00",
    "fecha_llegada": "2025-12-15 10:30:00"
  }'
```

### Respuesta Esperada (400)
```json
{
  "success": false,
  "error": "El origen y destino no pueden ser iguales"
}
```

### Validaciones
- ✅ Status HTTP: 400 Bad Request
- ✅ Rechazo correcto de origen = destino

---

## Caso de Prueba 4: Error - Fecha Inválida (2.1)

### Descripción
Validar que fecha_llegada debe ser posterior a fecha_salida.

### Request
```bash
curl -X POST "http://localhost/Gestion_vuelos_reservas/microservicio_vuelos/public/api/vuelos" \
  -H "Authorization: Bearer {{admin_token}}" \
  -H "Content-Type: application/json" \
  -d '{
    "numero_vuelo": "AA1003",
    "nave_id": 1,
    "origen": "Madrid",
    "destino": "Barcelona",
    "fecha_salida": "2025-12-15 10:30:00",
    "fecha_llegada": "2025-12-15 08:00:00"
  }'
```

### Respuesta Esperada (400)
```json
{
  "success": false,
  "error": "La fecha de llegada debe ser posterior a la fecha de salida"
}
```

### Validaciones
- ✅ Status HTTP: 400 Bad Request
- ✅ Rechazo de fecha inválida

---

## Caso de Prueba 5: Error - Nave No Existe (2.1)

### Descripción
Validar que nave_id debe existir.

### Request
```bash
curl -X POST "http://localhost/Gestion_vuelos_reservas/microservicio_vuelos/public/api/vuelos" \
  -H "Authorization: Bearer {{admin_token}}" \
  -H "Content-Type: application/json" \
  -d '{
    "numero_vuelo": "AA1004",
    "nave_id": 9999,
    "origen": "Madrid",
    "destino": "Barcelona",
    "fecha_salida": "2025-12-15 08:00:00",
    "fecha_llegada": "2025-12-15 10:30:00"
  }'
```

### Respuesta Esperada (404)
```json
{
  "success": false,
  "error": "La nave especificada no existe"
}
```

### Validaciones
- ✅ Status HTTP: 404 Not Found
- ✅ Validación de existencia de nave

---

## Caso de Prueba 6: Error - Número de Vuelo Duplicado (2.1)

### Descripción
Validar que numero_vuelo es único.

### Request
```bash
# Crear un vuelo primero
curl -X POST "http://localhost/Gestion_vuelos_reservas/microservicio_vuelos/public/api/vuelos" \
  -H "Authorization: Bearer {{admin_token}}" \
  -H "Content-Type: application/json" \
  -d '{
    "numero_vuelo": "AA2000",
    "nave_id": 1,
    "origen": "Madrid",
    "destino": "Barcelona",
    "fecha_salida": "2025-12-16 08:00:00",
    "fecha_llegada": "2025-12-16 10:30:00"
  }'

# Intentar crear otro con el mismo número
curl -X POST "http://localhost/Gestion_vuelos_reservas/microservicio_vuelos/public/api/vuelos" \
  -H "Authorization: Bearer {{admin_token}}" \
  -H "Content-Type: application/json" \
  -d '{
    "numero_vuelo": "AA2000",
    "nave_id": 2,
    "origen": "Valencia",
    "destino": "Sevilla",
    "fecha_salida": "2025-12-17 08:00:00",
    "fecha_llegada": "2025-12-17 10:30:00"
  }'
```

### Respuesta Esperada (409)
```json
{
  "success": false,
  "error": "Ya existe un vuelo con ese número"
}
```

### Validaciones
- ✅ Status HTTP: 409 Conflict
- ✅ Rechazo de número duplicado

---

## Caso de Prueba 7: Consultar Todos los Vuelos (2.2)

### Descripción
Listar todos los vuelos registrados.

### Request
```bash
curl -X GET "http://localhost/Gestion_vuelos_reservas/microservicio_vuelos/public/api/vuelos" \
  -H "Content-Type: application/json"
```

### Respuesta Esperada (200)
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
      "created_at": "2025-11-15 14:00:00",
      "updated_at": "2025-11-15 14:00:00"
    },
    {
      "id": 2,
      "numero_vuelo": "AA2000",
      "nave_id": 1,
      "origen": "Madrid",
      "destino": "Barcelona",
      "fecha_salida": "2025-12-16 08:00:00",
      "fecha_llegada": "2025-12-16 10:30:00",
      "asientos_disponibles": 150,
      "created_at": "2025-11-15 14:05:00",
      "updated_at": "2025-11-15 14:05:00"
    }
  ]
}
```

### Validaciones
- ✅ Status HTTP: 200 OK
- ✅ success = true
- ✅ data es array con todos los vuelos
- ✅ Todos los campos presentes

---

## Caso de Prueba 8: Buscar por Origen (2.3)

### Descripción
Filtrar vuelos por origen.

### Request
```bash
curl -X GET "http://localhost/Gestion_vuelos_reservas/microservicio_vuelos/public/api/vuelos?origen=Madrid" \
  -H "Content-Type: application/json"
```

### Respuesta Esperada (200)
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
      ...
    },
    {
      "id": 2,
      "numero_vuelo": "AA2000",
      "nave_id": 1,
      "origen": "Madrid",
      "destino": "Barcelona",
      ...
    }
  ]
}
```

### Validaciones
- ✅ Solo vuelos con origen "Madrid"
- ✅ Búsqueda case-insensitive
- ✅ Búsqueda parcial funciona ("mad" encontraría "Madrid")

---

## Caso de Prueba 9: Buscar por Destino (2.3)

### Descripción
Filtrar vuelos por destino.

### Request
```bash
curl -X GET "http://localhost/Gestion_vuelos_reservas/microservicio_vuelos/public/api/vuelos?destino=Barcelona" \
  -H "Content-Type: application/json"
```

### Respuesta Esperada (200)
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "numero_vuelo": "AA1000",
      ...
      "destino": "Barcelona",
      ...
    }
  ]
}
```

### Validaciones
- ✅ Solo vuelos con destino "Barcelona"

---

## Caso de Prueba 10: Buscar por Fecha Exacta (2.3)

### Descripción
Filtrar vuelos por fecha específica.

### Request
```bash
curl -X GET "http://localhost/Gestion_vuelos_reservas/microservicio_vuelos/public/api/vuelos?fecha=2025-12-15" \
  -H "Content-Type: application/json"
```

### Respuesta Esperada (200)
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "numero_vuelo": "AA1000",
      ...
      "fecha_salida": "2025-12-15 08:00:00",
      ...
    }
  ]
}
```

### Validaciones
- ✅ Solo vuelos del 2025-12-15
- ✅ Formato de fecha YYYY-MM-DD

---

## Caso de Prueba 11: Buscar por Rango de Fechas (2.3)

### Descripción
Filtrar vuelos entre dos fechas.

### Request
```bash
curl -X GET "http://localhost/Gestion_vuelos_reservas/microservicio_vuelos/public/api/vuelos?fecha_desde=2025-12-15&fecha_hasta=2025-12-20" \
  -H "Content-Type: application/json"
```

### Respuesta Esperada (200)
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "numero_vuelo": "AA1000",
      "fecha_salida": "2025-12-15 08:00:00"
    },
    {
      "id": 2,
      "numero_vuelo": "AA2000",
      "fecha_salida": "2025-12-16 08:00:00"
    }
  ]
}
```

### Validaciones
- ✅ Vuelos entre fechas (inclusive)
- ✅ fecha_desde <= fecha_salida <= fecha_hasta

---

## Caso de Prueba 12: Búsqueda con Múltiples Criterios (2.3)

### Descripción
Filtrar por origen, destino y fecha simultáneamente.

### Request
```bash
curl -X GET "http://localhost/Gestion_vuelos_reservas/microservicio_vuelos/public/api/vuelos?origen=Madrid&destino=Barcelona&fecha=2025-12-15" \
  -H "Content-Type: application/json"
```

### Respuesta Esperada (200)
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "numero_vuelo": "AA1000",
      "origen": "Madrid",
      "destino": "Barcelona",
      "fecha_salida": "2025-12-15 08:00:00"
    }
  ]
}
```

### Validaciones
- ✅ Se aplican todos los filtros
- ✅ Solo vuelos que cumplen TODOS los criterios

---

## Caso de Prueba 13: Modificar Vuelo Exitoso (2.4)

### Descripción
Actualizar información de un vuelo.

### Request
```bash
curl -X PUT "http://localhost/Gestion_vuelos_reservas/microservicio_vuelos/public/api/vuelos/1" \
  -H "Authorization: Bearer {{admin_token}}" \
  -H "Content-Type: application/json" \
  -d '{
    "origen": "Madrid",
    "destino": "Valencia"
  }'
```

### Respuesta Esperada (200)
```json
{
  "success": true,
  "data": {
    "id": 1,
    "numero_vuelo": "AA1000",
    "nave_id": 1,
    "origen": "Madrid",
    "destino": "Valencia",
    "fecha_salida": "2025-12-15 08:00:00",
    "fecha_llegada": "2025-12-15 10:30:00",
    "asientos_disponibles": 150,
    "updated_at": "2025-11-15 14:15:00"
  }
}
```

### Validaciones
- ✅ Status HTTP: 200 OK
- ✅ Destino actualizado a "Valencia"
- ✅ Otros campos no modificados
- ✅ updated_at actualizado

---

## Caso de Prueba 14: Error - Modificar Vuelo No Existente (2.4)

### Descripción
Intentar actualizar vuelo que no existe.

### Request
```bash
curl -X PUT "http://localhost/Gestion_vuelos_reservas/microservicio_vuelos/public/api/vuelos/9999" \
  -H "Authorization: Bearer {{admin_token}}" \
  -H "Content-Type: application/json" \
  -d '{
    "destino": "Valencia"
  }'
```

### Respuesta Esperada (404)
```json
{
  "success": false,
  "error": "Vuelo no encontrado"
}
```

### Validaciones
- ✅ Status HTTP: 404 Not Found

---

## Caso de Prueba 15: Error - Cambiar a Número Duplicado (2.4)

### Descripción
Intentar cambiar numero_vuelo a uno que ya existe.

### Request
```bash
# Asumir que vuelo 1 es AA1000 y vuelo 2 es AA2000
curl -X PUT "http://localhost/Gestion_vuelos_reservas/microservicio_vuelos/public/api/vuelos/1" \
  -H "Authorization: Bearer {{admin_token}}" \
  -H "Content-Type: application/json" \
  -d '{
    "numero_vuelo": "AA2000"
  }'
```

### Respuesta Esperada (409)
```json
{
  "success": false,
  "error": "Ya existe otro vuelo con ese número"
}
```

### Validaciones
- ✅ Status HTTP: 409 Conflict
- ✅ Rechazo de duplicado

---

## Caso de Prueba 16: Cambiar Nave de Vuelo (2.4)

### Descripción
Actualizar la nave y verificar que asientos_disponibles se actualiza.

### Request
```bash
# Asumir nave 2 tiene capacidad 200
curl -X PUT "http://localhost/Gestion_vuelos_reservas/microservicio_vuelos/public/api/vuelos/1" \
  -H "Authorization: Bearer {{admin_token}}" \
  -H "Content-Type: application/json" \
  -d '{
    "nave_id": 2
  }'
```

### Respuesta Esperada (200)
```json
{
  "success": true,
  "data": {
    "id": 1,
    "nave_id": 2,
    "asientos_disponibles": 200,
    ...
  }
}
```

### Validaciones
- ✅ nave_id cambiado a 2
- ✅ asientos_disponibles actualizado a 200

---

## Caso de Prueba 17: Eliminar Vuelo Exitoso (2.5)

### Descripción
Eliminar un vuelo sin reservas.

### Request
```bash
curl -X DELETE "http://localhost/Gestion_vuelos_reservas/microservicio_vuelos/public/api/vuelos/1" \
  -H "Authorization: Bearer {{admin_token}}"
```

### Respuesta Esperada (200)
```json
{
  "success": true,
  "message": "Vuelo eliminado"
}
```

### Validaciones
- ✅ Status HTTP: 200 OK
- ✅ Vuelo eliminado de base de datos
- ✅ Verificación en BD: SELECT * FROM vuelos WHERE id = 1 devuelve 0 rows

---

## Caso de Prueba 18: Error - Eliminar Vuelo No Existente (2.5)

### Descripción
Intentar eliminar vuelo que no existe.

### Request
```bash
curl -X DELETE "http://localhost/Gestion_vuelos_reservas/microservicio_vuelos/public/api/vuelos/9999" \
  -H "Authorization: Bearer {{admin_token}}"
```

### Respuesta Esperada (404)
```json
{
  "success": false,
  "error": "Vuelo no encontrado"
}
```

### Validaciones
- ✅ Status HTTP: 404 Not Found

---

## Caso de Prueba 19: Error - Eliminar Vuelo con Reservas (2.5)

### Descripción
Intentar eliminar vuelo que tiene reservas confirmadas.

### Setup
```sql
-- Crear vuelo de prueba
INSERT INTO vuelos (numero_vuelo, nave_id, origen, destino, fecha_salida, fecha_llegada, asientos_disponibles)
VALUES ('AA3000', 1, 'Madrid', 'Barcelona', '2025-12-20 08:00:00', '2025-12-20 10:30:00', 150);

-- Crear reserva confirmada
INSERT INTO reservas (usuario_id, vuelo_id, numero_asiento, estado)
VALUES (1, (SELECT id FROM vuelos WHERE numero_vuelo = 'AA3000'), 'A1', 'confirmada');
```

### Request
```bash
curl -X DELETE "http://localhost/Gestion_vuelos_reservas/microservicio_vuelos/public/api/vuelos/{{flight_id}}" \
  -H "Authorization: Bearer {{admin_token}}"
```

### Respuesta Esperada (409)
```json
{
  "success": false,
  "error": "No se puede eliminar un vuelo que tiene reservas confirmadas. Cancele las reservas primero."
}
```

### Validaciones
- ✅ Status HTTP: 409 Conflict
- ✅ Vuelo NO eliminado
- ✅ Protección de integridad de datos

---

## Caso de Prueba 20: Autorización - No Admin (2.x)

### Descripción
Intentar crear/modificar/eliminar con token de usuario no admin.

### Setup
```bash
# Hacer login como gestor
POST /api/usuarios/login
{
  "email": "gestor@example.com",
  "password": "password"
}
# Obtener {{gestor_token}}
```

### Request
```bash
curl -X POST "http://localhost/Gestion_vuelos_reservas/microservicio_vuelos/public/api/vuelos" \
  -H "Authorization: Bearer {{gestor_token}}" \
  -H "Content-Type: application/json" \
  -d '{...}'
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
- ✅ Operación bloqueada para no-admin

---

## Checklist de Validación

### Creación (2.1)
- [ ] Vuelo creado con todos los datos
- [ ] asientos_disponibles = capacidad de nave
- [ ] Todos los campos requeridos validados
- [ ] Origen ≠ destino
- [ ] Fecha llegada > fecha salida
- [ ] Nave existe
- [ ] numero_vuelo es único
- [ ] Status 201 exitoso
- [ ] Status 400 para errores de validación
- [ ] Status 404 si nave no existe
- [ ] Status 409 si numero_vuelo duplicado

### Consulta (2.2 y 2.3)
- [ ] GET /api/vuelos devuelve todos
- [ ] Búsqueda por origen funciona
- [ ] Búsqueda por destino funciona
- [ ] Búsqueda por fecha exacta funciona
- [ ] Búsqueda por rango de fechas funciona
- [ ] Búsqueda con múltiples criterios funciona
- [ ] Status 200 exitoso

### Actualización (2.4)
- [ ] Vuelo se actualiza correctamente
- [ ] numero_vuelo único validado
- [ ] Origen ≠ destino validado
- [ ] Fecha llegada > salida validado
- [ ] Cambio de nave valida existencia
- [ ] asientos_disponibles se actualiza con nave
- [ ] Status 200 exitoso
- [ ] Status 404 si vuelo no existe
- [ ] Status 409 si numero_vuelo duplicado

### Eliminación (2.5)
- [ ] Vuelo se elimina correctamente
- [ ] No se elimina si tiene reservas confirmadas
- [ ] Status 200 exitoso
- [ ] Status 404 si vuelo no existe
- [ ] Status 409 si tiene reservas

### Seguridad
- [ ] Operaciones CRUD requieren token admin
- [ ] Status 401 sin token
- [ ] Status 403 si no es admin
- [ ] GET /api/vuelos es público

---

## Verificaciones de Base de Datos

### Después de crear vuelo
```sql
SELECT * FROM vuelos;
```

### Después de buscar
```sql
SELECT * FROM vuelos WHERE origen = 'Madrid' AND destino = 'Barcelona';
SELECT * FROM vuelos WHERE DATE(fecha_salida) = '2025-12-15';
```

### Después de actualizar
```sql
SELECT id, numero_vuelo, destino, asientos_disponibles FROM vuelos WHERE id = 1;
```

### Antes de eliminar (sin reservas)
```sql
SELECT COUNT(*) as reservas FROM reservas 
WHERE vuelo_id = {{flight_id}} AND estado = 'confirmada';
```

### Después de eliminar
```sql
SELECT * FROM vuelos WHERE id = {{flight_id}};
-- Debe estar vacío
```

---

## Resumen de Casos

| # | Caso | Req | Status | Notas |
|-|-|-|-|-|
| 1 | Crear exitoso | 2.1 | 201 | ✅ Base |
| 2 | Datos incompletos | 2.1 | 400 | ✅ Validación |
| 3 | Origen = Destino | 2.1 | 400 | ✅ Validación |
| 4 | Fecha inválida | 2.1 | 400 | ✅ Validación |
| 5 | Nave no existe | 2.1 | 404 | ✅ FK |
| 6 | Número duplicado | 2.1 | 409 | ✅ Único |
| 7 | Listar todos | 2.2 | 200 | ✅ GET |
| 8 | Buscar por origen | 2.3 | 200 | ✅ Filtro |
| 9 | Buscar por destino | 2.3 | 200 | ✅ Filtro |
| 10 | Buscar por fecha | 2.3 | 200 | ✅ Filtro |
| 11 | Rango de fechas | 2.3 | 200 | ✅ Filtro |
| 12 | Múltiples criterios | 2.3 | 200 | ✅ Filtro |
| 13 | Actualizar | 2.4 | 200 | ✅ PUT |
| 14 | Actualizar no existe | 2.4 | 404 | ✅ Validación |
| 15 | Número duplicado | 2.4 | 409 | ✅ Único |
| 16 | Cambiar nave | 2.4 | 200 | ✅ Update seats |
| 17 | Eliminar | 2.5 | 200 | ✅ DELETE |
| 18 | Eliminar no existe | 2.5 | 404 | ✅ Validación |
| 19 | Eliminar con reservas | 2.5 | 409 | ✅ Integridad |
| 20 | Autorización | 2.x | 403 | ✅ Seguridad |

---

## Comandos SQL para Setup

```sql
-- Limpiar datos de prueba
TRUNCATE TABLE reservas;
TRUNCATE TABLE vuelos;
TRUNCATE TABLE naves;

-- Crear naves de prueba
INSERT INTO naves (modelo, capacidad, matricula) VALUES
('Boeing 747', 150, 'N12345'),
('Airbus A380', 200, 'N54321');

-- Verificar naves creadas
SELECT id, modelo, capacidad FROM naves;

-- Crear vuelos de prueba
INSERT INTO vuelos (numero_vuelo, nave_id, origen, destino, fecha_salida, fecha_llegada, asientos_disponibles) VALUES
('AA1000', 1, 'Madrid', 'Barcelona', '2025-12-15 08:00:00', '2025-12-15 10:30:00', 150),
('IB2000', 2, 'Valencia', 'Sevilla', '2025-12-16 14:00:00', '2025-12-16 16:45:00', 200);

-- Verificar vuelos creados
SELECT * FROM vuelos;
```

