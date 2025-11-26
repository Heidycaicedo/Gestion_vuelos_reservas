# Gestion_vuelos_reservas

## 📄 Descripción General de la Aplicación

La aplicación es un sistema web para la gestión de vuelos y reservas, construido con una arquitectura basada en microservicios. El sistema permitirá administrar usuarios, vuelos y naves, así como realizar y gestionar reservas.

### Roles Principales del Sistema

**Administrador:** Gestiona usuarios, vuelos y naves.

**Gestor:** Realiza operaciones relacionadas con reservas, cancelaciones y consulta de vuelos.

### Arquitectura del Backend

El backend estará dividido en al menos dos microservicios:
- **Microservicio 1:** Gestión de usuarios y autenticación
- **Microservicio 2:** Gestión de vuelos y reservas

### Frontend

El frontend se construirá únicamente con HTML5, CSS y JavaScript, consumiendo directamente los microservicios.

## 📌 Requerimientos Funcionales

### 1. Gestión de Usuarios
1.1. El sistema debe permitir registrar nuevos usuarios. (Solo administrador)
1.2. El sistema debe permitir iniciar sesión mediante correo y contraseña.
1.3. Al iniciar sesión, el sistema debe generar un token aleatorio y almacenarlo en la base de datos.
1.4. El sistema debe permitir cerrar sesión eliminando el token almacenado.
1.5. El sistema debe validar el token en cada petición a los microservicios protegidos.
1.6. El sistema debe impedir el acceso si el token no es válido o no existe.
1.7. El rol del usuario debe almacenarse en la tabla de usuarios con las opciones: administrador y gestor.
1.8. El administrador debe poder consultar la lista de usuarios.
1.9. El administrador debe poder actualizar datos de un usuario.
1.10. El administrador debe poder cambiar el rol de un usuario.

### 2. Gestión de Vuelos
2.1. El sistema debe permitir registrar vuelos.
2.2. El sistema debe permitir consultar todos los vuelos registrados.
2.3. El sistema debe permitir buscar vuelos por origen, destino o fecha.
2.4. El sistema debe permitir modificar la información de un vuelo.
2.5. El sistema debe permitir eliminar un vuelo.

### 3. Gestión de Naves (Aeronaves) (Responsabilidad del Administrador)
3.1. El sistema debe permitir registrar nuevas naves.
3.2. El sistema debe permitir consultar las naves disponibles.
3.3. El sistema debe permitir modificar la información de una nave.
3.4. El sistema debe permitir eliminar una nave.
3.5. Cada vuelo debe estar asociado a una nave.

### 4. Gestión de Reservas (Responsabilidad del Gestor)
4.1. El sistema debe permitir crear una reserva para un vuelo disponible.
4.2. El sistema debe permitir consultar las reservas existentes.
4.3. El sistema debe permitir consultar reservas por usuario.
4.4. El sistema debe permitir cancelar una reserva.
4.5. El sistema debe impedir reservas a vuelos inexistentes o eliminados.

## 📌 Requerimientos No Funcionales

### 1. Arquitectura
1.1. El backend debe estar dividido en mínimo dos microservicios:
- Microservicio de Usuarios y Autenticación
- Microservicio de Vuelos, Naves y Reservas

1.2. Los microservicios deben operar de forma independiente y ser consumidos directamente desde el frontend.

### 2. Seguridad
2.1. El token debe ser único por sesión y almacenarse en la base de datos.
2.2. El sistema no debe permitir acceder a recursos protegidos sin token válido.
2.3. El token debe eliminarse al cerrar sesión.

### 3. Almacenamiento
3.1. El frontend debe almacenar el token en localStorage o sessionStorage durante la sesión activa.

### 4. Usabilidad
4.1. La interfaz debe ser clara, simple y construida únicamente con HTML5, CSS y JavaScript.
4.2. El diseño debe usar layouts basados en grid y flexbox.

### 5. Rendimiento y Disponibilidad
5.1. Las respuestas de los microservicios deben presentarse en formato JSON.
5.2. El sistema debe responder adecuadamente a múltiples consultas de vuelos y reservas.
