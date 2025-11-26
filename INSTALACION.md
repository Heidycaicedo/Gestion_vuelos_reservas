# Guía de Instalación

## Requisitos Previos
- XAMPP (PHP 7.4+, MySQL)
- Composer
- Git

## Pasos de Instalación

### 1. Configurar Base de Datos
- Abre phpMyAdmin (http://localhost/phpmyadmin)
- Copia todo el contenido de `database.sql`
- Ejecuta el script SQL para crear la base de datos y tablas

### 2. Instalar Dependencias del Microservicio de Usuarios
```bash
cd c:\xampp\htdocs\Gestion_vuelos_reservas\microservicio_usuarios
composer install
```

### 3. Instalar Dependencias del Microservicio de Vuelos
```bash
cd c:\xampp\htdocs\Gestion_vuelos_reservas\microservicio_vuelos
composer install
```

### 4. Configurar URLs en el Frontend
Edita `frontend/js/api.js` y asegúrate de que las URLs de los microservicios sean correctas:

```javascript
const API_USUARIOS = 'http://localhost/Gestion_vuelos_reservas/microservicio_usuarios/public';
const API_VUELOS = 'http://localhost/Gestion_vuelos_reservas/microservicio_vuelos/public';
```

### 5. Acceder a la Aplicación
- Ve a: http://localhost/Gestion_vuelos_reservas/frontend/login.html
- Usa las credenciales de prueba (crear un usuario primero)

## Estructura del Proyecto

```
Gestion_vuelos_reservas/
├── README.md
├── database.sql
├── INSTALACION.md
├── microservicio_usuarios/
│   ├── composer.json
│   ├── config/
│   │   └── database.php
│   ├── public/
│   │   └── index.php (Rutas de autenticación y usuarios)
│   └── src/
│       ├── Controllers/
│       │   ├── AuthController.php
│       │   └── UserController.php
│       └── Models/
│           ├── User.php
│           └── Session.php
├── microservicio_vuelos/
│   ├── composer.json
│   ├── config/
│   │   └── database.php
│   ├── public/
│   │   └── index.php (Rutas de vuelos, naves y reservas)
│   └── src/
│       ├── Controllers/
│       │   ├── FlightController.php
│       │   ├── AircraftController.php
│       │   └── ReservationController.php
│       └── Models/
│           ├── Flight.php
│           ├── Aircraft.php
│           └── Reservation.php
└── frontend/
    ├── login.html
    ├── index.html (Dashboard)
    ├── css/
    │   └── style.css
    └── js/
        ├── api.js (Cliente API)
        ├── login.js (Lógica de login)
        └── app.js (Lógica de la aplicación)
```

## Rutas de API

### Microservicio de Usuarios
- POST `/api/usuarios/registrar` - Registrar nuevo usuario
- POST `/api/usuarios/login` - Iniciar sesión
- POST `/api/usuarios/logout` - Cerrar sesión
- POST `/api/usuarios/validar-token` - Validar token
- GET `/api/usuarios` - Listar usuarios (admin)
- GET `/api/usuarios/{id}` - Obtener usuario (admin)
- PUT `/api/usuarios/{id}` - Actualizar usuario (admin)
- PUT `/api/usuarios/{id}/rol` - Cambiar rol (admin)

### Microservicio de Vuelos
- GET `/api/vuelos` - Listar vuelos
- GET `/api/vuelos/{id}` - Obtener vuelo
- POST `/api/vuelos` - Crear vuelo (admin)
- PUT `/api/vuelos/{id}` - Actualizar vuelo (admin)
- DELETE `/api/vuelos/{id}` - Eliminar vuelo (admin)

### Microservicio de Vuelos (Naves)
- GET `/api/naves` - Listar naves
- GET `/api/naves/{id}` - Obtener nave
- POST `/api/naves` - Crear nave (admin)

### Microservicio de Vuelos (Reservas)
- GET `/api/reservas` - Listar reservas
- POST `/api/reservas` - Crear reserva
- DELETE `/api/reservas/{id}` - Cancelar reserva

## Notas Importantes
- Los tokens se almacenan en localStorage/sessionStorage en el navegador
- La contraseña se encripta con bcrypt en la base de datos
- Todos los microservicios devuelven JSON
- Se requiere token válido para acceder a recursos protegidos
