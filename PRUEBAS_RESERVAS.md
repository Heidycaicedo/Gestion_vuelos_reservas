# Ejemplos de Prueba - Gestión de Reservas

## Configuración Previa

### 1. Crear Base de Datos
Ejecutar `database.sql` en phpMyAdmin

### 2. Insertar Datos de Prueba

```sql
-- Insertar un administrador
INSERT INTO usuarios (nombre, email, password, rol) VALUES 
('Admin User', 'admin@test.com', '$2y$10$...', 'administrador');

-- Insertar un gestor
INSERT INTO usuarios (nombre, email, password, rol) VALUES 
('Gestor User', 'gestor@test.com', '$2y$10$...', 'gestor');

-- Insertar una nave
INSERT INTO naves (modelo, capacidad, matricula) VALUES 
('Boeing 787', 242, 'N787BA');

-- Insertar vuelos
INSERT INTO vuelos (numero_vuelo, nave_id, origen, destino, fecha_salida, fecha_llegada, asientos_disponibles) VALUES 
('AA101', 1, 'Madrid', 'Barcelona', '2025-12-20 10:00:00', '2025-12-20 12:00:00', 5),
('AA102', 1, 'Barcelona', 'Valencia', '2025-12-21 14:00:00', '2025-12-21 15:30:00', 3);
```

## Pruebas de Gestión de Reservas

### Test 1: Login como Gestor

**Request:**
```bash
POST http://localhost/Gestion_vuelos_reservas/microservicio_usuarios/public/api/usuarios/login
Content-Type: application/json

{
  "email": "gestor@test.com",
  "password": "password123"
}
```

**Response Esperado (200):**
```json
{
  "success": true,
  "data": {
    "token": "a1b2c3d4e5f6...",
    "usuario_id": 2,
    "rol": "gestor"
  }
}
```

**Guardar token para próximas pruebas: `TOKEN=a1b2c3d4e5f6...`**

---

### Test 2: Listar Vuelos Disponibles

**Request:**
```bash
GET http://localhost/Gestion_vuelos_reservas/microservicio_vuelos/public/api/vuelos
```

**Response Esperado (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "numero_vuelo": "AA101",
      "nave_id": 1,
      "origen": "Madrid",
      "destino": "Barcelona",
      "fecha_salida": "2025-12-20 10:00:00",
      "fecha_llegada": "2025-12-20 12:00:00",
      "asientos_disponibles": 5,
      "created_at": "2025-11-15T10:00:00",
      "updated_at": "2025-11-15T10:00:00"
    },
    {
      "id": 2,
      "numero_vuelo": "AA102",
      "nave_id": 1,
      "origen": "Barcelona",
      "destino": "Valencia",
      "fecha_salida": "2025-12-21 14:00:00",
      "asientos_disponibles": 3
    }
  ]
}
```

---

### Test 3: Crear Reserva - Caso Exitoso

**Request:**
```bash
POST http://localhost/Gestion_vuelos_reservas/microservicio_vuelos/public/api/reservas
Authorization: Bearer TOKEN
Content-Type: application/json

{
  "vuelo_id": 1,
  "numero_asiento": "12A"
}
```

**Response Esperado (201):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "usuario_id": 2,
    "vuelo_id": 1,
    "numero_asiento": "12A",
    "estado": "confirmada",
    "created_at": "2025-11-15T11:00:00",
    "updated_at": "2025-11-15T11:00:00"
  }
}
```

**Verificar en DB:**
```sql
SELECT * FROM reservas WHERE id = 1;
SELECT asientos_disponibles FROM vuelos WHERE id = 1;  -- Debe ser 4 (5-1)
```

---

### Test 4: Crear Reserva - Vuelo Inexistente

**Request:**
```bash
POST http://localhost/Gestion_vuelos_reservas/microservicio_vuelos/public/api/reservas
Authorization: Bearer TOKEN
Content-Type: application/json

{
  "vuelo_id": 999,
  "numero_asiento": "15B"
}
```

**Response Esperado (404):**
```json
{
  "success": false,
  "error": "El vuelo especificado no existe"
}
```

---

### Test 5: Crear Reserva - Asiento ya Reservado

**Request 1:** (Primera reserva del asiento 15B)
```bash
POST http://localhost/Gestion_vuelos_reservas/microservicio_vuelos/public/api/reservas
Authorization: Bearer TOKEN
Content-Type: application/json

{
  "vuelo_id": 1,
  "numero_asiento": "15B"
}
```

Response: 201 OK

**Request 2:** (Intentar reservar el mismo asiento)
```bash
POST http://localhost/Gestion_vuelos_reservas/microservicio_vuelos/public/api/reservas
Authorization: Bearer TOKEN
Content-Type: application/json

{
  "vuelo_id": 1,
  "numero_asiento": "15B"
}
```

**Response Esperado (409):**
```json
{
  "success": false,
  "error": "El asiento ya está reservado en este vuelo"
}
```

---

### Test 6: Crear Reserva - Sin Asientos Disponibles

**Request:** (Agotar asientos - ejecutar múltiples veces)
```bash
POST http://localhost/Gestion_vuelos_reservas/microservicio_vuelos/public/api/reservas
Authorization: Bearer TOKEN
Content-Type: application/json

{
  "vuelo_id": 2,
  "numero_asiento": "1A"
}
```

Repetir con asientos: 1A, 2B, 3C (3 veces = 3 asientos agotados)

**Sexta Request - Sin asientos disponibles:**
```bash
POST http://localhost/Gestion_vuelos_reservas/microservicio_vuelos/public/api/reservas
Authorization: Bearer TOKEN
Content-Type: application/json

{
  "vuelo_id": 2,
  "numero_asiento": "4D"
}
```

**Response Esperado (409):**
```json
{
  "success": false,
  "error": "No hay asientos disponibles en este vuelo"
}
```

---

### Test 7: Consultar Reservas del Usuario

**Request:**
```bash
GET http://localhost/Gestion_vuelos_reservas/microservicio_vuelos/public/api/reservas?usuario_id=2
Authorization: Bearer TOKEN
```

**Response Esperado (200):**
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
      "created_at": "2025-11-15T11:00:00"
    },
    {
      "id": 2,
      "usuario_id": 2,
      "vuelo_id": 1,
      "numero_asiento": "15B",
      "estado": "confirmada",
      "created_at": "2025-11-15T11:05:00"
    }
  ]
}
```

---

### Test 8: Cancelar Reserva - Caso Exitoso

**Request:**
```bash
DELETE http://localhost/Gestion_vuelos_reservas/microservicio_vuelos/public/api/reservas/1
Authorization: Bearer TOKEN
```

**Response Esperado (200):**
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
    "updated_at": "2025-11-15T11:30:00"
  }
}
```

**Verificar:**
```sql
SELECT * FROM reservas WHERE id = 1;  -- estado = 'cancelada'
SELECT asientos_disponibles FROM vuelos WHERE id = 1;  -- Debe ser 5 (4+1)
```

---

### Test 9: Cancelar Reserva - No Encontrada

**Request:**
```bash
DELETE http://localhost/Gestion_vuelos_reservas/microservicio_vuelos/public/api/reservas/999
Authorization: Bearer TOKEN
```

**Response Esperado (404):**
```json
{
  "success": false,
  "error": "Reserva no encontrada"
}
```

---

### Test 10: Cancelar Reserva - Ya Cancelada

**Request 1:** Cancelar reserva (Response: 200 OK)
```bash
DELETE http://localhost/Gestion_vuelos_reservas/microservicio_vuelos/public/api/reservas/2
Authorization: Bearer TOKEN
```

**Request 2:** Intentar cancelar la misma reserva
```bash
DELETE http://localhost/Gestion_vuelos_reservas/microservicio_vuelos/public/api/reservas/2
Authorization: Bearer TOKEN
```

**Response Esperado (409):**
```json
{
  "success": false,
  "error": "No se puede cancelar una reserva que no está confirmada"
}
```

---

### Test 11: Sin Token (Acceso Denegado)

**Request:**
```bash
POST http://localhost/Gestion_vuelos_reservas/microservicio_vuelos/public/api/reservas
Content-Type: application/json

{
  "vuelo_id": 1,
  "numero_asiento": "20A"
}
```

**Response Esperado (401):**
```json
{
  "success": false,
  "error": "Token requerido"
}
```

---

### Test 12: Login como Administrador - Intento de Reserva

**Request:**
```bash
POST http://localhost/Gestion_vuelos_reservas/microservicio_usuarios/public/api/usuarios/login
Content-Type: application/json

{
  "email": "admin@test.com",
  "password": "password123"
}
```

**Obtener token de administrador**

**Request (con token de admin):**
```bash
POST http://localhost/Gestion_vuelos_reservas/microservicio_vuelos/public/api/reservas
Authorization: Bearer ADMIN_TOKEN
Content-Type: application/json

{
  "vuelo_id": 1,
  "numero_asiento": "25A"
}
```

**Response Esperado (403):**
```json
{
  "success": false,
  "error": "Acceso denegado. Solo gestores."
}
```

---

## Checklist de Validación

- [ ] Test 1: Login exitoso (obtiene token)
- [ ] Test 2: Listar vuelos disponibles (público)
- [ ] Test 3: Crear reserva (éxito 201)
- [ ] Test 4: Crear reserva en vuelo inexistente (404)
- [ ] Test 5: Crear reserva en asiento ya reservado (409)
- [ ] Test 6: Crear reserva sin asientos disponibles (409)
- [ ] Test 7: Consultar reservas del usuario (filtra correctamente)
- [ ] Test 8: Cancelar reserva (éxito 200, asientos se liberan)
- [ ] Test 9: Cancelar reserva inexistente (404)
- [ ] Test 10: Cancelar reserva ya cancelada (409)
- [ ] Test 11: Operación sin token (401)
- [ ] Test 12: Admin intenta crear reserva (403)

## Verificación en Base de Datos

```sql
-- Ver todas las reservas
SELECT r.id, r.usuario_id, r.vuelo_id, r.numero_asiento, r.estado, 
       u.nombre, v.numero_vuelo
FROM reservas r
JOIN usuarios u ON r.usuario_id = u.id
JOIN vuelos v ON r.vuelo_id = v.id
ORDER BY r.created_at DESC;

-- Ver asientos disponibles
SELECT numero_vuelo, asientos_disponibles FROM vuelos;

-- Ver reservas confirmadas por vuelo
SELECT v.numero_vuelo, COUNT(*) as reservas_confirmadas, v.asientos_disponibles
FROM reservas r
JOIN vuelos v ON r.vuelo_id = v.id
WHERE r.estado = 'confirmada'
GROUP BY v.id;
```
