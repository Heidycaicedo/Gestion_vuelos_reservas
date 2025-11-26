document.addEventListener('DOMContentLoaded', function() {
    // Verificar autenticación
    if (!Auth.isAuthenticated()) {
        window.location.href = 'login.html';
        return;
    }

    // Cargar rol del usuario
    const userRole = Auth.getRol();
    const adminBtn = document.getElementById('btnAdmin');

    // Mostrar/ocultar botón de admin según rol
    if (userRole !== 'administrador') {
        adminBtn.style.display = 'none';
    }

    // Navegación
    document.getElementById('btnHome').addEventListener('click', () => showSection('homeSection'));
    document.getElementById('btnFlights').addEventListener('click', () => {
        showSection('flightsSection');
        loadFlights();
    });
    document.getElementById('btnReservations').addEventListener('click', () => {
        showSection('reservationsSection');
        loadReservations();
    });
    document.getElementById('btnAdmin').addEventListener('click', () => {
        // Verificar permisos de admin
        if (userRole !== 'administrador') {
            alert('No tienes permiso para acceder al panel de administración');
            return;
        }
        showSection('adminSection');
        loadUsers();
    });
    document.getElementById('btnLogout').addEventListener('click', logout);

    function showSection(sectionId) {
        // Ocultar todas las secciones
        document.querySelectorAll('.section').forEach(section => {
            section.classList.remove('active');
        });

        // Mostrar la sección seleccionada
        document.getElementById(sectionId).classList.add('active');

        // Actualizar botones de navegación
        document.querySelectorAll('.nav-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        event.target.classList.add('active');
    }

    async function loadFlights() {
        const flightsList = document.getElementById('flightsList');
        flightsList.innerHTML = '<p>Cargando vuelos...</p>';

        try {
            const response = await Flights.list();

            if (response.success) {
                const flights = response.data.data;

                if (flights.length === 0) {
                    flightsList.innerHTML = '<p>No hay vuelos disponibles.</p>';
                    return;
                }

                flightsList.innerHTML = flights.map(flight => `
                    <div class="flight-card">
                        <h3>Vuelo ${flight.numero_vuelo}</h3>
                        <div class="flight-info">
                            <label>Origen:</label>
                            <span>${flight.origen}</span>
                        </div>
                        <div class="flight-info">
                            <label>Destino:</label>
                            <span>${flight.destino}</span>
                        </div>
                        <div class="flight-info">
                            <label>Salida:</label>
                            <span>${flight.fecha_salida}</span>
                        </div>
                        <div class="flight-info">
                            <label>Asientos disponibles:</label>
                            <span>${flight.asientos_disponibles}</span>
                        </div>
                        <button onclick="reserveFlight(${flight.id})" class="btn-submit" style="margin-top: 10px;">
                            Reservar
                        </button>
                    </div>
                `).join('');
            } else if (response.status === 403) {
                flightsList.innerHTML = '<p>No tienes permiso para acceder a esta información.</p>';
            } else {
                flightsList.innerHTML = '<p>Error al cargar vuelos.</p>';
            }
        } catch (error) {
            flightsList.innerHTML = '<p>Error de conexión.</p>';
        }
    }

    async function loadReservations() {
        const reservationsList = document.getElementById('reservationsList');
        reservationsList.innerHTML = '<p>Cargando reservas...</p>';

        try {
            const response = await Reservations.list();

            if (response.success) {
                const reservations = response.data.data;

                if (reservations.length === 0) {
                    reservationsList.innerHTML = '<p>No tienes reservas.</p>';
                    return;
                }

                reservationsList.innerHTML = reservations.map(reservation => `
                    <div class="reservation-item">
                        <div>
                            <p><strong>Vuelo ID:</strong> ${reservation.vuelo_id}</p>
                            <p><strong>Asiento:</strong> ${reservation.numero_asiento}</p>
                            <p><strong>Estado:</strong> ${reservation.estado}</p>
                        </div>
                        <button onclick="cancelReservation(${reservation.id})" class="btn-submit" style="background-color: #d9534f;">
                            Cancelar
                        </button>
                    </div>
                `).join('');
            } else if (response.status === 403) {
                reservationsList.innerHTML = '<p>No tienes permiso para acceder a tus reservas.</p>';
            } else {
                reservationsList.innerHTML = '<p>Error al cargar reservas.</p>';
            }
        } catch (error) {
            reservationsList.innerHTML = '<p>Error de conexión.</p>';
        }
    }

    async function loadUsers() {
        const usersList = document.getElementById('usersList');
        usersList.innerHTML = '<p>Cargando usuarios...</p>';

        try {
            const response = await Users.list();

            if (response.success) {
                const users = response.data.data;

                if (users.length === 0) {
                    usersList.innerHTML = '<p>No hay usuarios.</p>';
                    return;
                }

                usersList.innerHTML = users.map(user => `
                    <div class="user-item">
                        <div>
                            <p><strong>${user.nombre}</strong></p>
                            <p>${user.email}</p>
                            <p>Rol: <strong>${user.rol}</strong></p>
                        </div>
                        <div>
                            <button onclick="changeUserRole(${user.id}, '${user.rol}')" class="btn-submit" style="width: auto;">
                                Cambiar Rol
                            </button>
                        </div>
                    </div>
                `).join('');
            } else if (response.status === 403) {
                usersList.innerHTML = '<p>No tienes permiso para acceder a la gestión de usuarios.</p>';
            } else {
                usersList.innerHTML = '<p>Error al cargar usuarios.</p>';
            }
        } catch (error) {
            usersList.innerHTML = '<p>Error de conexión.</p>';
        }
    }

    async function logout() {
        const token = Auth.getToken();
        await Auth.logout(token);
        window.location.href = 'login.html';
    }

    // Funciones globales para eventos
    window.reserveFlight = function(flightId) {
        const numeroAsiento = prompt('Ingresa el número de asiento (ej: 12A):');
        
        if (!numeroAsiento) {
            alert('Debes ingresar un número de asiento');
            return;
        }

        realizarReserva(flightId, numeroAsiento);
    };

    async function realizarReserva(flightId, numeroAsiento) {
        try {
            const response = await Reservations.create(flightId, numeroAsiento);

            if (response.success) {
                alert('Reserva realizada exitosamente');
                loadReservations();
                loadFlights();
            } else if (response.status === 409) {
                alert('Error: ' + response.data.error);
            } else if (response.status === 404) {
                alert('Error: El vuelo no existe');
            } else {
                alert('Error al realizar la reserva: ' + response.data.error);
            }
        } catch (error) {
            alert('Error de conexión al realizar la reserva');
        }
    }

    window.cancelReservation = function(reservationId) {
        if (confirm('¿Estás seguro de que deseas cancelar esta reserva?')) {
            cancelarReserva(reservationId);
        }
    };

    async function cancelarReserva(reservationId) {
        try {
            const response = await Reservations.cancel(reservationId);

            if (response.success) {
                alert('Reserva cancelada correctamente');
                loadReservations();
                loadFlights();
            } else if (response.status === 404) {
                alert('Error: La reserva no existe');
            } else if (response.status === 409) {
                alert('Error: ' + response.data.error);
            } else {
                alert('Error al cancelar la reserva');
            }
        } catch (error) {
            alert('Error de conexión al cancelar la reserva');
        }
    }

    window.changeUserRole = function(userId, currentRole) {
        const newRole = currentRole === 'administrador' ? 'gestor' : 'administrador';
        if (confirm(`¿Cambiar rol a ${newRole}?`)) {
            alert(`Cambiar rol del usuario ${userId} a ${newRole} - Funcionalidad en desarrollo`);
        }
    };

    // Mostrar sección de inicio
    showSection('homeSection');
});
