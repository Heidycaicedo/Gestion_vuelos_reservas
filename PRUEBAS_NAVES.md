# Ejemplos de Prueba - Gestión de Naves

## Configuración Previa

### 1. Login como Administrador

**Request:**
```bash
POST http://localhost/Gestion_vuelos_reservas/microservicio_usuarios/public/api/usuarios/login
Content-Type: application/json

{
  "email": "admin@test.com",
  "password": "password123"
}
```

**Response Esperado (200):**
```json
{
  "success": true,
  "data": {
    "token": "a1b2c3d4e5f6...",
    "usuario_id": 1,
    "rol": "administrador"
  }
}
```

**Guardar token: `ADMIN_TOKEN=a1b2c3d4e5f6...`**

---

## Pruebas de Gestión de Naves

### Test 1: Crear Nave - Caso Exitoso

**Request:**
```bash
POST http://localhost/Gestion_vuelos_reservas/microservicio_vuelos/public/api/naves
Authorization: Bearer ADMIN_TOKEN
Content-Type: application/json

{
  "modelo": "Boeing 787 Dreamliner",
  "capacidad": 242,
  "matricula": "N787BA"
}
```

**Response Esperado (201):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "modelo": "Boeing 787 Dreamliner",
    "capacidad": 242,
    "matricula": "N787BA",
    "created_at": "2025-11-15T10:00:00",
    "updated_at": "2025-11-15T10:00:00"
  }
}
```

**Verificar en DB:**
```sql
SELECT * FROM naves WHERE id = 1;
-- Debe mostrar: id=1, modelo=Boeing 787 Dreamliner, capacidad=242, matricula=N787BA
```

---

### Test 2: Crear Nave - Datos Incompletos

**Request (falta capacidad):**
```bash
POST http://localhost/Gestion_vuelos_reservas/microservicio_vuelos/public/api/naves
Authorization: Bearer ADMIN_TOKEN
Content-Type: application/json

{
  "modelo": "Airbus A380",
  "matricula": "N380AA"
}
```

**Response Esperado (400):**
```json
{
  "success": false,
  "error": "modelo, capacidad y matricula son requeridos"
}
```

---

### Test 3: Crear Nave - Capacidad Inválida

**Request (capacidad negativa):**
```bash
POST http://localhost/Gestion_vuelos_reservas/microservicio_vuelos/public/api/naves
Authorization: Bearer ADMIN_TOKEN
Content-Type: application/json

{
  "modelo": "Airbus A380",
  "capacidad": -100,
  "matricula": "N380AA"
}
```

**Response Esperado (400):**
```json
{
  "success": false,
  "error": "La capacidad debe ser un número positivo"
}
```

---

### Test 4: Crear Nave - Matrícula Duplicada

**Request 1 (primera nave):**
```bash
POST http://localhost/Gestion_vuelos_reservas/microservicio_vuelos/public/api/naves
Authorization: Bearer ADMIN_TOKEN
Content-Type: application/json

{
  "modelo": "Boeing 777",
  "capacidad": 400,
  "matricula": "N777XX"
}
```

Response: 201 OK

**Request 2 (intento de duplicar matrícula):**
```bash
POST http://localhost/Gestion_vuelos_reservas/microservicio_vuelos/public/api/naves
Authorization: Bearer ADMIN_TOKEN
Content-Type: application/json

{
  "modelo": "Boeing 767",
  "capacidad": 350,
  "matricula": "N777XX"
}
```

**Response Esperado (409):**
```json
{
  "success": false,
  "error": "Ya existe una nave con esa matrícula"
}
```

---

### Test 5: Listar Todas las Naves

**Request:**
```bash
GET http://localhost/Gestion_vuelos_reservas/microservicio_vuelos/public/api/naves
Authorization: Bearer ADMIN_TOKEN
```

**Response Esperado (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "modelo": "Boeing 787 Dreamliner",
      "capacidad": 242,
      "matricula": "N787BA"
    },
    {
      "id": 2,
      "modelo": "Boeing 777",
      "capacidad": 400,
      "matricula": "N777XX"
    }
  ]
}
```

---

### Test 6: Obtener Nave Específica

**Request:**
```bash
GET http://localhost/Gestion_vuelos_reservas/microservicio_vuelos/public/api/naves/1
Authorization: Bearer ADMIN_TOKEN
```

**Response Esperado (200):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "modelo": "Boeing 787 Dreamliner",
    "capacidad": 242,
    "matricula": "N787BA",
    "created_at": "2025-11-15T10:00:00"
  }
}
```

---

### Test 7: Obtener Nave Inexistente

**Request:**
```bash
GET http://localhost/Gestion_vuelos_reservas/microservicio_vuelos/public/api/naves/999
Authorization: Bearer ADMIN_TOKEN
```

**Response Esperado (404):**
```json
{
  "success": false,
  "error": "Nave no encontrada"
}
```

---

### Test 8: Modificar Nave - Caso Exitoso

**Request:**
```bash
PUT http://localhost/Gestion_vuelos_reservas/microservicio_vuelos/public/api/naves/1
Authorization: Bearer ADMIN_TOKEN
Content-Type: application/json

{
  "capacidad": 250,
  "modelo": "Boeing 787-10 Dreamliner"
}
```

**Response Esperado (200):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "modelo": "Boeing 787-10 Dreamliner",
    "capacidad": 250,
    "matricula": "N787BA",
    "updated_at": "2025-11-15T10:30:00"
  }
}
```

**Verificar en DB:**
```sql
SELECT * FROM naves WHERE id = 1;
-- capacidad debe ser 250
```

---

### Test 9: Modificar Nave - Capacidad Inválida

**Request:**
```bash
PUT http://localhost/Gestion_vuelos_reservas/microservicio_vuelos/public/api/naves/1
Authorization: Bearer ADMIN_TOKEN
Content-Type: application/json

{
  "capacidad": 0
}
```

**Response Esperado (400):**
```json
{
  "success": false,
  "error": "La capacidad debe ser un número positivo"
}
```

---

### Test 10: Modificar Nave - Matrícula Duplicada

**Request:**
```bash
PUT http://localhost/Gestion_vuelos_reservas/microservicio_vuelos/public/api/naves/1
Authorization: Bearer ADMIN_TOKEN
Content-Type: application/json

{
  "matricula": "N777XX"
}
```

**Response Esperado (409):**
```json
{
  "success": false,
  "error": "Ya existe una nave con esa matrícula"
}
```

---

### Test 11: Eliminar Nave - Caso Exitoso

**Request:**
```bash
DELETE http://localhost/Gestion_vuelos_reservas/microservicio_vuelos/public/api/naves/2
Authorization: Bearer ADMIN_TOKEN
```

**Response Esperado (200):**
```json
{
  "success": true,
  "message": "Nave eliminada correctamente"
}
```

**Verificar en DB:**
```sql
SELECT * FROM naves WHERE id = 2;
-- Debe estar vacío (nave eliminada)
```

---

### Test 12: Eliminar Nave - No Encontrada

**Request:**
```bash
DELETE http://localhost/Gestion_vuelos_reservas/microservicio_vuelos/public/api/naves/999
Authorization: Bearer ADMIN_TOKEN
```

**Response Esperado (404):**
```json
{
  "success": false,
  "error": "Nave no encontrada"
}
```

---

### Test 13: Eliminar Nave con Vuelos Asociados

**Request 1 - Crear vuelo asociado a nave:**
```bash
POST http://localhost/Gestion_vuelos_reservas/microservicio_vuelos/public/api/vuelos
Authorization: Bearer ADMIN_TOKEN
Content-Type: application/json

{
  "numero_vuelo": "AA101",
  "nave_id": 1,
  "origen": "Madrid",
  "destino": "Barcelona",
  "fecha_salida": "2025-12-20 10:00:00",
  "fecha_llegada": "2025-12-20 12:00:00"
}
```

Response: 201 OK
```json
{
  "success": true,
  "data": {
    "id": 1,
    "numero_vuelo": "AA101",
    "nave_id": 1,
    "asientos_disponibles": 250,
    "origen": "Madrid",
    "destino": "Barcelona"
  }
}
```

**Request 2 - Intentar eliminar nave con vuelos:**
```bash
DELETE http://localhost/Gestion_vuelos_reservas/microservicio_vuelos/public/api/naves/1
Authorization: Bearer ADMIN_TOKEN
```

**Response Esperado (409):**
```json
{
  "success": false,
  "error": "No se puede eliminar una nave que tiene vuelos asociados. Elimina primero los vuelos."
}
```

---

### Test 14: Crear Vuelo sin Nave Válida

**Request:**
```bash
POST http://localhost/Gestion_vuelos_reservas/microservicio_vuelos/public/api/vuelos
Authorization: Bearer ADMIN_TOKEN
Content-Type: application/json

{
  "numero_vuelo": "AA102",
  "nave_id": 999,
  "origen": "Madrid",
  "destino": "Barcelona",
  "fecha_salida": "2025-12-21 10:00:00",
  "fecha_llegada": "2025-12-21 12:00:00"
}
```

**Response Esperado (404):**
```json
{
  "success": false,
  "error": "La nave especificada no existe"
}
```

---

### Test 15: Sin Token (Acceso Denegado)

**Request:**
```bash
POST http://localhost/Gestion_vuelos_reservas/microservicio_vuelos/public/api/naves
Content-Type: application/json

{
  "modelo": "Concorde",
  "capacidad": 100,
  "matricula": "CCDE"
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

### Test 16: Login como Gestor - Intento de Crear Nave

**Request 1 - Login como gestor:**
```bash
POST http://localhost/Gestion_vuelos_reservas/microservicio_usuarios/public/api/usuarios/login
Content-Type: application/json

{
  "email": "gestor@test.com",
  "password": "password123"
}
```

**Obtener token de gestor: `GESTOR_TOKEN`**

**Request 2 - Intenta crear nave:**
```bash
POST http://localhost/Gestion_vuelos_reservas/microservicio_vuelos/public/api/naves
Authorization: Bearer GESTOR_TOKEN
Content-Type: application/json

{
  "modelo": "Cessna 172",
  "capacidad": 4,
  "matricula": "N12345"
}
```

**Response Esperado (403):**
```json
{
  "success": false,
  "error": "Acceso denegado. Solo administradores."
}
```

---

## Flujo Completo de Prueba

1. ✅ Login como admin
2. ✅ Crear nave (Test 1)
3. ✅ Listar naves (Test 5)
4. ✅ Obtener nave específica (Test 6)
5. ✅ Modificar nave (Test 8)
6. ✅ Crear vuelo asociado a nave (Test 13, Request 1)
7. ✅ Intentar eliminar nave con vuelos (Test 13, Request 2)
8. ✅ Eliminar vuelo primero
9. ✅ Eliminar nave (Test 11)
10. ✅ Verificar eliminación (Test 7)

## Checklist de Validación

- [ ] Test 1: Crear nave (éxito 201)
- [ ] Test 2: Crear sin datos completos (400)
- [ ] Test 3: Capacidad inválida (400)
- [ ] Test 4: Matrícula duplicada (409)
- [ ] Test 5: Listar todas las naves (200)
- [ ] Test 6: Obtener nave específica (200)
- [ ] Test 7: Obtener nave inexistente (404)
- [ ] Test 8: Modificar nave (200)
- [ ] Test 9: Modificar con capacidad inválida (400)
- [ ] Test 10: Modificar con matrícula duplicada (409)
- [ ] Test 11: Eliminar nave (200)
- [ ] Test 12: Eliminar nave inexistente (404)
- [ ] Test 13: Eliminar nave con vuelos (409)
- [ ] Test 14: Crear vuelo sin nave válida (404)
- [ ] Test 15: Sin token (401)
- [ ] Test 16: Gestor intenta crear nave (403)

## Verificación en Base de Datos

```sql
-- Ver todas las naves
SELECT * FROM naves;

-- Ver naves con vuelos asociados
SELECT n.id, n.modelo, n.matricula, COUNT(v.id) as num_vuelos
FROM naves n
LEFT JOIN vuelos v ON n.id = v.nave_id
GROUP BY n.id;

-- Ver capacidad vs asientos disponibles de vuelos
SELECT n.modelo, n.capacidad, v.numero_vuelo, v.asientos_disponibles
FROM naves n
JOIN vuelos v ON n.id = v.nave_id;

-- Ver historial de cambios
SELECT id, modelo, capacidad, matricula, created_at, updated_at FROM naves ORDER BY updated_at DESC;
```
