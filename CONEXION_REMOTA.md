# 🖥️ Guía de Conexión Remota - Centro de Vuelos

## Información del Servidor

| Componente | Dirección | Puerto | Estado |
|-----------|-----------|--------|--------|
| **IP del Servidor** | 172.16.32.77 | - | ✅ Activa |
| **Frontend (Apache)** | http://172.16.32.77/Gestion_vuelos_reservas/frontend/ | 80 | ✅ Escuchando |
| **API Usuarios** | http://172.16.32.77:8001/api/ | 8001 | ✅ Escuchando |
| **API Vuelos** | http://172.16.32.77:8002/api/ | 8002 | ✅ Escuchando |
| **MySQL** | 172.16.32.77 | 3306 | ✅ Escuchando |

## 🔗 URLs de Acceso desde PC Remoto

### Frontend
```
http://172.16.32.77/Gestion_vuelos_reservas/frontend/
```

### APIs
```
API Usuarios: http://172.16.32.77:8001/api/auth/login
API Vuelos:   http://172.16.32.77:8002/api/flights
```

## ⚙️ Configuración Necesaria

### 1️⃣ En el PC Remoto (Escritorio)

Abre tu navegador e ingresa:
```
http://172.16.32.77/Gestion_vuelos_reservas/frontend/
```

### 2️⃣ Requisitos de Red

- ✅ Ambos PCs en la misma red o LAN
- ✅ El firewall debe permitir puertos: **80, 8001, 8002, 3306**
- ✅ Sin VPN ni proxy que bloquee las conexiones

### 3️⃣ Credenciales de Acceso

| Usuario | Email | Contraseña | Rol |
|---------|-------|-----------|-----|
| Admin | admin@system.com | admin123 | administrador |
| Gestor | gestor@system.com | admin123 | gestor |

## 🔧 Solución de Problemas

### ❌ "No se puede conectar al servidor"

1. Verifica que todos los servicios estén corriendo:
   ```powershell
   netstat -ano | Select-String ":80|:8001|:8002|:3306"
   ```

2. Reinicia los servicios:
   ```powershell
   # Detener todos
   Stop-Process -Name php -Force -ErrorAction SilentlyContinue
   Stop-Process -Name mysqld -Force -ErrorAction SilentlyContinue
   
   # Iniciar MySQL
   Start-Process mysqld.exe
   
   # Iniciar microservicios
   Start-Process php -ArgumentList "-S 0.0.0.0:8001 router.php" -WorkingDirectory "C:\xampp\htdocs\Gestion_vuelos_reservas\microservicio_usuarios\public" -WindowStyle Hidden
   Start-Process php -ArgumentList "-S 0.0.0.0:8002 router.php" -WorkingDirectory "C:\xampp\htdocs\Gestion_vuelos_reservas\microservicio_vuelos\public" -WindowStyle Hidden
   ```

3. Verifica la IP correcta con:
   ```powershell
   ipconfig | Select-String "IPv4"
   ```

### ❌ Firewall bloquea la conexión

**Windows:**
```powershell
# Agregar excepción al firewall
New-NetFirewallRule -DisplayName "Centro de Vuelos" -Direction Inbound -Action Allow -Protocol TCP -LocalPort 80,8001,8002,3306
```

### ❌ "Este no es un proyecto Laravel"

✅ **Este es un proyecto PHP con Slim Framework 3**, no Laravel. Es un proyecto personalizado con:
- Microservicios independientes
- API REST pura
- Autenticación por tokens
- Control de roles (admin/gestor)

## 📝 Arquitectura del Proyecto

```
Gestion_vuelos_reservas/
├── frontend/                    # HTML/CSS/JS
│   ├── index.html              # Aplicación principal
│   ├── login.html              # Página de login
│   ├── css/style.css           # Estilos
│   └── js/                     # JavaScript
│
├── microservicio_usuarios/      # API de Usuarios (Puerto 8001)
│   ├── public/index.php        # Rutas
│   └── src/                    # Controladores
│
├── microservicio_vuelos/        # API de Vuelos (Puerto 8002)
│   ├── public/index.php        # Rutas
│   └── src/                    # Controladores
│
└── tools/                       # Scripts de utilidad
```

## 🚀 Comandos Rápidos

Reiniciar todo desde PowerShell:
```powershell
# Detener servicios
Stop-Process -Name php, mysqld -Force -ErrorAction SilentlyContinue

# Esperar 2 segundos
Start-Sleep -Seconds 2

# Iniciar MySQL
Start-Process mysqld.exe

# Iniciar APIs
Start-Process php -ArgumentList "-S 0.0.0.0:8001 router.php" -WorkingDirectory "C:\xampp\htdocs\Gestion_vuelos_reservas\microservicio_usuarios\public" -WindowStyle Hidden
Start-Process php -ArgumentList "-S 0.0.0.0:8002 router.php" -WorkingDirectory "C:\xampp\htdocs\Gestion_vuelos_reservas\microservicio_vuelos\public" -WindowStyle Hidden

# Esperar a que inicien
Start-Sleep -Seconds 3

# Verificar que estén corriendo
netstat -ano | Select-String ":80|:8001|:8002|:3306" | Where-Object {$_ -match "LISTENING"}
```

## ✅ Verificación

Desde el PC remoto, prueba:
```
http://172.16.32.77/Gestion_vuelos_reservas/frontend/
```

Si ves la página de login, ¡todo está funcionando! 🎉

---

**Nota:** Si necesitas cambiar la IP, actualiza `frontend/js/api.js` con los nuevos endpoints.
