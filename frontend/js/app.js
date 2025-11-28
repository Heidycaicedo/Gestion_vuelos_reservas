document.addEventListener('DOMContentLoaded', function() {
    console.log('=== DOMContentLoaded fired ===');
    
    // Verificar autenticación
    if (!Auth.isAuthenticated()) {
        window.location.href = 'login.html';
        return;
    }

    // Mostrar reservas de un usuario (admin)
    window.viewReservationsOfUser = async function(userId) {
        try {
            const token = localStorage.getItem('token');
            if (!token) return showMessage('No autenticado', 'error');

            // Pedimos al API las reservas del usuario
            const res = await fetch(`${API_BASE}/reservations?user_id=${userId}`, {
                headers: { 'Authorization': 'Bearer ' + token }
            });
            if (!res.ok) {
                const err = await res.json().catch(() => ({}));
                return showMessage(err.message || 'Error al obtener reservas', 'error');
            }

            const reservations = await res.json();

            // Reutilizar la presentación de reservas: limpiamos la lista y la llenamos
            const reservationsList = document.getElementById('reservationsList');
            if (!reservationsList) return showMessage('Sección de reservas no encontrada', 'error');

            // Si no hay reservas, informar
            if (!reservations || reservations.length === 0) {
                reservationsList.innerHTML = '<p>No hay reservas para este usuario.</p>';
                // Mostrar la sección de reservas
                document.getElementById('reservationsSection').style.display = 'block';
                return;
            }

            // Para cada reserva buscamos el vuelo y la nave (similar a loadReservations)
            const flightIds = [...new Set(reservations.map(r => r.flight_id))];
            const flightsMap = {};
            await Promise.all(flightIds.map(async id => {
                try {
                    const fRes = await fetch(`${API_BASE}/flights/${id}`);
                    if (fRes.ok) flightsMap[id] = await fRes.json();
                } catch (e) { /* ignore */ }
            }));

            const aircraftIds = [...new Set(Object.values(flightsMap).map(f => f ? f.nave_id : null).filter(Boolean))];
            const aircraftMap = {};
            await Promise.all(aircraftIds.map(async id => {
                try {
                    const aRes = await fetch(`${API_BASE}/aircraft/${id}`);
                    if (aRes.ok) aircraftMap[id] = await aRes.json();
                } catch (e) { /* ignore */ }
            }));

            // Renderizar
            const currentUserId = Auth.getUserId();
            const userRoleNow = Auth.getRole();
            const isAdminNow = userRoleNow === 'administrador';

            reservationsList.innerHTML = reservations.map(r => {
                const flight = flightsMap[r.flight_id];
                const aircraft = flight && flight.nave_id ? aircraftMap[flight.nave_id] : null;
                const canCancel = isAdminNow || String(r.user_id) === String(currentUserId);
                return `
                    <div class="reservation-item">
                        <p><strong>Reserva #${r.id}</strong> - Estado: ${r.status} - Fecha: ${r.created_at}</p>
                        <p>Vuelo: ${flight ? flight.origin + ' → ' + flight.destination : 'No disponible'}</p>
                        <p>Salida: ${flight ? flight.departure : 'No disponible'}</p>
                        <p>Precio: ${flight ? flight.price : 'No disponible'}</p>
                        <p>Nave: ${aircraft ? aircraft.name + ' (' + aircraft.model + ')' : 'No disponible'}</p>
                        ${canCancel ? `<button onclick="cancelReservation(${r.id})" class="btn-cancel">Cancelar</button>` : ''}
                    </div>
                `;
            }).join('');

            // Mostrar la sección de reservas
            document.getElementById('reservationsSection').style.display = 'block';

        } catch (err) {
            console.error(err);
            showMessage('Error al obtener reservas del usuario', 'error');
        }
    }

    // Cargar rol del usuario
    const userRole = Auth.getRole();
    const adminBtn = document.getElementById('btnAdmin');
    const isAdmin = userRole === 'administrador';
    const isGestor = userRole === 'gestor' || isAdmin;

    // DEBUG: Log role information
    console.log('=== USER ROLE DEBUG ===');
    console.log('userRole from localStorage:', userRole);
    console.log('isAdmin:', isAdmin);
    console.log('isGestor:', isGestor);
    console.log('token:', Auth.getToken());
    console.log('userId:', Auth.getUserId());
    console.log('Full localStorage:', {
        token: localStorage.getItem('token'),
        user_id: localStorage.getItem('user_id'),
        role: localStorage.getItem('role')
    });

    // ===== APLICAR TEMA SEGÚN ROL =====
    setTimeout(() => {
        console.log('=== Applying theme after delay ===');
        const header = document.querySelector('header');
        const roleBadge = document.getElementById('roleBadge');
        const welcomeTitle = document.getElementById('welcomeTitle');
        
        if (isAdmin) {
            console.log('✓ ADMIN - Setting header to BLUE');
            header.style.background = 'linear-gradient(135deg, #3b82f6 0%, #1e40af 100%)';
            header.style.boxShadow = '0 5px 20px rgba(59, 130, 246, 0.3)';
            document.body.classList.add('admin-theme');
            
            roleBadge.textContent = '👑 Administrador';
            roleBadge.style.display = 'block';
            welcomeTitle.textContent = '👑 Bienvenido, Administrador';
            console.log('✓ Admin theme applied');
        } else if (isGestor) {
            console.log('✓ GESTOR - Setting header to PURPLE');
            header.style.background = 'linear-gradient(135deg, #a855f7 0%, #7c3aed 100%)';
            header.style.boxShadow = '0 5px 20px rgba(168, 85, 247, 0.3)';
            document.body.classList.add('gestor-theme');
            
            roleBadge.textContent = '👤 Gestor';
            roleBadge.style.display = 'block';
            welcomeTitle.textContent = '👤 Bienvenido, Gestor de Reservas';
            console.log('✓ Gestor theme applied');
        }
        
        console.log('Final header background:', window.getComputedStyle(header).background);
    }, 100);

    // Mostrar/ocultar botones de admin según rol
    const flightMgmtBtn = document.getElementById('btnFlightManagement');
    const aircraftMgmtBtn = document.getElementById('btnAircraftManagement');
    if (!isAdmin) {
        adminBtn.style.display = 'none';
        flightMgmtBtn.style.display = 'none';
        aircraftMgmtBtn.style.display = 'none';
        console.log('Admin buttons hidden - user is not admin');
    } else {
        adminBtn.style.display = 'block';
        flightMgmtBtn.style.display = 'block';
        aircraftMgmtBtn.style.display = 'block';
        console.log('Admin buttons visible - user is admin');
    }

    // Navegación
    document.getElementById('btnHome').addEventListener('click', (e) => showSection('homeSection', e));
    document.getElementById('btnFlights').addEventListener('click', (e) => {
        showSection('flightsSection', e);
        loadFlights();
    });
    document.getElementById('btnAircraft').addEventListener('click', (e) => {
        showSection('aircraftSection', e);
        loadAircraftList();
    });
    document.getElementById('btnReservations').addEventListener('click', (e) => {
        showSection('reservationsSection', e);
        loadReservations();
    });
    document.getElementById('btnFlightManagement').addEventListener('click', (e) => {
        // Verificar permisos de admin
        if (userRole !== 'administrador') {
            alert('No tienes permiso para acceder a la gestión de vuelos');
            return;
        }
        showSection('flightManagementSection', e);
        loadFlightsForManagement();
    });
    document.getElementById('btnAircraftManagement').addEventListener('click', (e) => {
        // Verificar permisos de admin
        if (userRole !== 'administrador') {
            alert('No tienes permiso para acceder a la gestión de naves');
            return;
        }
        showSection('aircraftManagementSection', e);
        loadAircraftForManagement();
    });
    document.getElementById('btnAdmin').addEventListener('click', (e) => {
        // Verificar permisos de admin
        if (userRole !== 'administrador') {
            alert('No tienes permiso para acceder al panel de administración');
            return;
        }
        showSection('adminSection', e);
        loadUsers();
    });
    document.getElementById('btnLogout').addEventListener('click', logout);

    function showSection(sectionId, event) {
        if (event) event.preventDefault();
        
        // Proteger secciones de administración: solo administradores
        const adminOnlySections = ['flightManagementSection', 'aircraftManagementSection', 'adminSection'];
        if (adminOnlySections.includes(sectionId) && !isAdmin) {
            alert('Acceso denegado. Solo administradores pueden acceder a esta sección.');
            // Mostrar sección home por defecto
            document.getElementById('homeSection').classList.add('active');
            return;
        }

        // Ocultar todas las secciones
        document.querySelectorAll('.section').forEach(section => {
            section.classList.remove('active');
        });

        // Mostrar la sección seleccionada
        const targetSection = document.getElementById(sectionId);
        if (targetSection) {
            targetSection.classList.add('active');
        }

        // Actualizar botones de navegación
        document.querySelectorAll('.nav-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        
        // Marcar el botón activo si el evento existe
        if (event && event.target) {
            event.target.classList.add('active');
        }
    }

    async function loadFlights() {
        const flightsList = document.getElementById('flightsList');
        flightsList.innerHTML = '<p>Cargando vuelos...</p>';

        try {
            const response = await Flights.list();
            const aircraftResponse = await Aircraft.list();

            if (response.success && aircraftResponse.success) {
                const flights = response.data.data;
                const aircraft = aircraftResponse.data.data;
                const aircraftMap = {};
                
                // Crear mapa de naves para búsqueda rápida
                aircraft.forEach(plane => {
                    aircraftMap[plane.id] = plane;
                });

                if (flights.length === 0) {
                    flightsList.innerHTML = '<p>No hay vuelos disponibles.</p>';
                    return;
                }

                flightsList.innerHTML = flights.map(flight => {
                    const plane = aircraftMap[flight.nave_id];
                    const aircraftInfo = plane ? `
                        <div class="flight-info">
                            <label>Nave:</label>
                            <span><strong>${plane.name}</strong> (${plane.model})</span>
                        </div>` : '';
                    
                    return `
                    <div class="flight-card">
                        <h3>Vuelo #${flight.id}</h3>
                        <div class="flight-info">
                            <label>Origen:</label>
                            <span>${flight.origin}</span>
                        </div>
                        <div class="flight-info">
                            <label>Destino:</label>
                            <span>${flight.destination}</span>
                        </div>
                        ${aircraftInfo}
                        <div class="flight-info">
                            <label>Salida:</label>
                            <span>${flight.departure}</span>
                        </div>
                        <div class="flight-info">
                            <label>Precio:</label>
                            <span>$${flight.price}</span>
                        </div>
                        ${isGestor ? `<button onclick="reserveFlight(${flight.id})" class="btn-submit" style="margin-top: 10px;">Reservar</button>` : ''}
                    </div>
                `;
                }).join('');
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
                // Obtener información de vuelos y naves asociadas
                const flightPromises = reservations.map(r => Flights.getById(r.flight_id));
                const flightResponses = await Promise.all(flightPromises);

                const flightMap = {};
                for (let i = 0; i < flightResponses.length; i++) {
                    const fr = flightResponses[i];
                    if (fr.success) flightMap[reservations[i].flight_id] = fr.data.data;
                    else flightMap[reservations[i].flight_id] = null;
                }

                // Recolectar ids únicos de naves
                const aircraftIds = Object.values(flightMap)
                    .filter(f => f && f.nave_id)
                    .map(f => f.nave_id);
                const uniqueAircraftIds = [...new Set(aircraftIds)];

                const aircraftMap = {};
                if (uniqueAircraftIds.length > 0) {
                    const aircraftPromises = uniqueAircraftIds.map(id => Aircraft.getById(id));
                    const aircraftResponses = await Promise.all(aircraftPromises);
                    for (let i = 0; i < aircraftResponses.length; i++) {
                        const ar = aircraftResponses[i];
                        if (ar.success) aircraftMap[uniqueAircraftIds[i]] = ar.data.data;
                        else aircraftMap[uniqueAircraftIds[i]] = null;
                    }
                }

                reservationsList.innerHTML = reservations.map(reservation => {
                    const flight = flightMap[reservation.flight_id];
                    const plane = flight && aircraftMap[flight.nave_id];

                    const flightInfo = flight ? `
                        <p><strong>Vuelo:</strong> #${flight.id} — ${flight.origin} → ${flight.destination}</p>
                        <p><strong>Salida:</strong> ${flight.departure}</p>
                        <p><strong>Precio:</strong> $${flight.price}</p>` : '<p><em>Información del vuelo no disponible</em></p>';

                    const aircraftInfo = plane ? `<p><strong>Nave:</strong> ${plane.name} (${plane.model}) — Capacidad: ${plane.capacity}</p>` : '<p><em>Nave no disponible</em></p>';

                    return `
                    <div class="reservation-item">
                        <div>
                            ${flightInfo}
                            ${aircraftInfo}
                            <p><strong>Estado:</strong> ${reservation.status}</p>
                            <p><strong>Reservado:</strong> ${reservation.reserved_at}</p>
                        </div>
                        ${isGestor ? `<button onclick="cancelReservation(${reservation.id})" class="btn-cancel">Cancelar</button>` : ''}
                    </div>
                `;
                }).join('');
            } else if (response.status === 403) {
                reservationsList.innerHTML = '<p>No tienes permiso para acceder a tus reservas.</p>';
            } else {
                reservationsList.innerHTML = '<p>Error al cargar reservas.</p>';
            }
        } catch (error) {
            reservationsList.innerHTML = '<p>Error de conexión.</p>';
        }
    }

    async function searchFlights() {
        const origin = document.getElementById('searchOrigin').value.trim();
        const destination = document.getElementById('searchDestination').value.trim();
        const departure = document.getElementById('searchDeparture').value;

        const flightsList = document.getElementById('flightsList');
        flightsList.innerHTML = '<p>Buscando vuelos...</p>';

        try {
            // Construir filtros
            const filtros = {};
            if (origin) filtros.origin = origin;
            if (destination) filtros.destination = destination;
            if (departure) filtros.departure = departure;

            const response = await Flights.search(origin || null, destination || null, departure || null);
            const aircraftResponse = await Aircraft.list();

            if (response.success && aircraftResponse.success) {
                const flights = response.data.data;
                const aircraft = aircraftResponse.data.data;
                const aircraftMap = {};
                
                // Crear mapa de naves para búsqueda rápida
                aircraft.forEach(plane => {
                    aircraftMap[plane.id] = plane;
                });

                if (flights.length === 0) {
                    flightsList.innerHTML = '<p>No se encontraron vuelos que coincidan con tu búsqueda.</p>';
                    return;
                }

                flightsList.innerHTML = flights.map(flight => {
                    const plane = aircraftMap[flight.nave_id];
                    const aircraftInfo = plane ? `
                        <div class="flight-info">
                            <label>Nave:</label>
                            <span><strong>${plane.name}</strong> (${plane.model})</span>
                        </div>` : '';
                    
                    return `
                    <div class="flight-card">
                        <h3>Vuelo #${flight.id}</h3>
                        <div class="flight-info">
                            <label>Origen:</label>
                            <span>${flight.origin}</span>
                        </div>
                        <div class="flight-info">
                            <label>Destino:</label>
                            <span>${flight.destination}</span>
                        </div>
                        ${aircraftInfo}
                        <div class="flight-info">
                            <label>Salida:</label>
                            <span>${flight.departure}</span>
                        </div>
                        <div class="flight-info">
                            <label>Precio:</label>
                            <span>$${flight.price}</span>
                        </div>
                        ${isGestor ? `<button onclick="reserveFlight(${flight.id})" class="btn-submit" style="margin-top: 10px;">Reservar</button>` : ''}
                    </div>
                `;
                }).join('');
            } else {
                flightsList.innerHTML = '<p>Error en la búsqueda.</p>';
            }
        } catch (error) {
            flightsList.innerHTML = '<p>Error de conexión.</p>';
        }
    }

    function clearSearch() {
        document.getElementById('searchOrigin').value = '';
        document.getElementById('searchDestination').value = '';
        document.getElementById('searchDeparture').value = '';
        loadFlights();
    }

    // Aircraft functions
    async function loadAircraftList() {
        const aircraftList = document.getElementById('aircraftList');
        aircraftList.innerHTML = '<p>Cargando naves...</p>';

        try {
            const response = await Aircraft.list();

            if (response.success) {
                const aircraft = response.data.data;

                if (aircraft.length === 0) {
                    aircraftList.innerHTML = '<p>No hay naves disponibles.</p>';
                    return;
                }

                aircraftList.innerHTML = aircraft.map(plane => `
                    <div class="aircraft-card">
                        <h3>${plane.name}</h3>
                        <div class="aircraft-info">
                            <label>Modelo:</label>
                            <span>${plane.model}</span>
                        </div>
                        <div class="aircraft-info">
                            <label>Capacidad:</label>
                            <span>${plane.capacity} pasajeros</span>
                        </div>
                        <div class="aircraft-info">
                            <label>ID:</label>
                            <span>#${plane.id}</span>
                        </div>
                        ${isAdmin ? `
                            <div style="display: flex; gap: 8px; margin-top: 10px;">
                                <button onclick="editAircraftFromQuery(${plane.id})" class="btn-submit" style="flex: 1; padding: 8px 12px; font-size: 14px;">✏️ Editar</button>
                                <button onclick="deleteAircraftFromQuery(${plane.id})" class="btn-cancel" style="flex: 1; padding: 8px 12px; font-size: 14px;">🗑️ Eliminar</button>
                            </div>
                        ` : ''}
                    </div>
                `).join('');
            } else if (response.status === 403) {
                aircraftList.innerHTML = '<p>No tienes permiso para acceder a esta información.</p>';
            } else {
                aircraftList.innerHTML = '<p>Error al cargar naves.</p>';
            }
        } catch (error) {
            aircraftList.innerHTML = '<p>Error de conexión.</p>';
        }
    }

    async function searchAircraft() {
        const name = document.getElementById('searchAircraftName').value.trim();
        const model = document.getElementById('searchAircraftModel').value.trim();
        const capacity = document.getElementById('searchAircraftCapacity').value.trim();

        const aircraftList = document.getElementById('aircraftList');
        aircraftList.innerHTML = '<p>Buscando naves...</p>';

        try {
            const response = await Aircraft.list();

            if (response.success) {
                let aircraft = response.data.data;

                // Aplicar filtros localmente
                if (name) {
                    aircraft = aircraft.filter(plane => 
                        plane.name.toLowerCase().includes(name.toLowerCase())
                    );
                }

                if (model) {
                    aircraft = aircraft.filter(plane => 
                        plane.model.toLowerCase().includes(model.toLowerCase())
                    );
                }

                if (capacity) {
                    const minCapacity = parseInt(capacity);
                    aircraft = aircraft.filter(plane => plane.capacity >= minCapacity);
                }

                if (aircraft.length === 0) {
                    aircraftList.innerHTML = '<p>No se encontraron naves que coincidan con tu búsqueda.</p>';
                    return;
                }

                aircraftList.innerHTML = aircraft.map(plane => `
                    <div class="aircraft-card">
                        <h3>${plane.name}</h3>
                        <div class="aircraft-info">
                            <label>Modelo:</label>
                            <span>${plane.model}</span>
                        </div>
                        <div class="aircraft-info">
                            <label>Capacidad:</label>
                            <span>${plane.capacity} pasajeros</span>
                        </div>
                        <div class="aircraft-info">
                            <label>ID:</label>
                            <span>#${plane.id}</span>
                        </div>
                        ${isAdmin ? `
                            <div style="display: flex; gap: 8px; margin-top: 10px;">
                                <button onclick="editAircraftFromQuery(${plane.id})" class="btn-submit" style="flex: 1; padding: 8px 12px; font-size: 14px;">✏️ Editar</button>
                                <button onclick="deleteAircraftFromQuery(${plane.id})" class="btn-cancel" style="flex: 1; padding: 8px 12px; font-size: 14px;">🗑️ Eliminar</button>
                            </div>
                        ` : ''}
                    </div>
                `).join('');
            } else {
                aircraftList.innerHTML = '<p>Error en la búsqueda.</p>';
            }
        } catch (error) {
            aircraftList.innerHTML = '<p>Error de conexión.</p>';
        }
    }

    function clearSearchAircraft() {
        document.getElementById('searchAircraftName').value = '';
        document.getElementById('searchAircraftModel').value = '';
        document.getElementById('searchAircraftCapacity').value = '';
        loadAircraftList();
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
                            <p><strong>${user.name}</strong></p>
                            <p>${user.email}</p>
                            <p>Rol: <strong>${user.role}</strong></p>
                        </div>
                        <div>
                            ${isAdmin ? `<button onclick="editUser(${user.id})" class="btn-submit" style="width: auto; margin-right: 5px;">✏️ Editar</button>` : ''}
                            ${isAdmin ? `<button onclick="changeUserRole(${user.id}, '${user.role}')" class="btn-submit" style="width: auto; margin-right:5px;">🔄 Cambiar Rol</button>` : ''}
                            ${isAdmin ? `<button onclick="viewReservationsOfUser(${user.id})" class="btn-submit" style="width: auto;">📄 Ver Reservas</button>` : ''}
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
    // Abrir modal de confirmación de reserva
    window.reserveFlight = async function(flightId) {
        try {
            const res = await Flights.getById(flightId);
            if (!res.success) {
                alert('No se pudo cargar la información del vuelo');
                return;
            }

            const flight = res.data.data;

            // Rellenar modal con información
            document.getElementById('resFlightId').textContent = flight.id;
            document.getElementById('resRoute').textContent = `${flight.origin} → ${flight.destination}`;
            document.getElementById('resDeparture').textContent = flight.departure;
            document.getElementById('resPrice').textContent = `$${flight.price}`;

            // Try to show aircraft info (fetch aircraft list)
            try {
                const aRes = await Aircraft.getById(flight.nave_id);
                if (aRes.success) {
                    const plane = aRes.data.data;
                    document.getElementById('resAircraftInfo').textContent = `Nave: ${plane.name} (${plane.model}) - Capacidad: ${plane.capacity}`;
                } else {
                    document.getElementById('resAircraftInfo').textContent = 'Nave: no disponible';
                }
            } catch (e) {
                document.getElementById('resAircraftInfo').textContent = 'Nave: no disponible';
            }

            // Show modal
            document.getElementById('reservationModal').style.display = 'flex';

            // Wire confirm button (remove previous handlers first)
            const btn = document.getElementById('confirmReservationBtn');
            btn.onclick = async function() {
                btn.disabled = true;
                btn.textContent = 'Reservando...';
                await realizarReserva(flightId);
                btn.disabled = false;
                btn.textContent = 'Confirmar Reserva';
                closeReservationModal();
            };
        } catch (error) {
            alert('Error al preparar la reserva');
        }
    };

    async function realizarReserva(flightId) {
        try {
            const response = await Reservations.create(flightId);

            if (response.success) {
                showMessage('Reserva realizada exitosamente', 'success');
                loadReservations();
                loadFlights();
            } else if (response.status === 409) {
                showMessage('Error: ' + response.data.error, 'error');
            } else if (response.status === 404) {
                showMessage('Error: El vuelo no existe', 'error');
            } else {
                showMessage('Error al realizar la reserva: ' + (response.data?.error || response.error || ''), 'error');
            }
        } catch (error) {
            showMessage('Error de conexión al realizar la reserva', 'error');
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
                showMessage('Reserva cancelada correctamente', 'success');
                loadReservations();
                loadFlights();
            } else if (response.status === 404) {
                showMessage('Error: La reserva no existe', 'error');
            } else if (response.status === 409) {
                showMessage('Error: ' + response.data.error, 'error');
            } else {
                showMessage('Error al cancelar la reserva', 'error');
            }
        } catch (error) {
            showMessage('Error de conexión al cancelar la reserva', 'error');
        }
    }

    // Cerrar modal de reserva
    window.closeReservationModal = function() {
        const modal = document.getElementById('reservationModal');
        if (modal) modal.style.display = 'none';
    };

    // Mensajes de feedback simples (puede reemplazarse por toasts)
    function showMessage(message, type = 'info') {
        // type: 'success' | 'error' | 'info'
        // For now use alert for simplicity; keep function for future enhancement
        if (type === 'error') {
            alert('Error: ' + message);
        } else {
            alert(message);
        }
    }

    window.changeUserRole = async function(userId, currentRole) {
        if (!isAdmin) {
            alert('No tienes permisos para cambiar roles');
            return;
        }

        const newRole = currentRole === 'administrador' ? 'gestor' : 'administrador';
        if (!confirm(`¿Cambiar rol a ${newRole}?`)) return;

        try {
            const response = await Users.updateRole(userId, newRole);
            if (response.success) {
                const data = response.data.data || response.data;
                
                // Check if session was invalidated (self-demotion)
                if (data.session_invalidated) {
                    alert(data.message || 'Tu rol ha sido actualizado. Tu sesión ha sido cerrada. Por favor, inicia sesión nuevamente.');
                    await Auth.logout(Auth.getToken());
                    window.location.href = 'login.html';
                    return;
                }
                
                alert('Rol actualizado correctamente');
                loadUsers();
            } else if (response.status === 403) {
                alert('Acceso denegado. No tienes permiso para cambiar roles');
            } else {
                alert('Error al cambiar rol: ' + (response.data?.error || response.error || 'Desconocido'));
            }
        } catch (e) {
            alert('Error de conexión al cambiar rol');
        }
    };

    // Cargar vuelos para administración
    async function loadFlightsForManagement() {
        const flightsList = document.getElementById('flightsManagementList');
        flightsList.innerHTML = '<p>Cargando vuelos...</p>';

        try {
            const response = await Flights.list();
            const aircraftResponse = await Aircraft.list();
            
            if (response.success && aircraftResponse.success) {
                const flights = response.data.data;
                const aircraft = aircraftResponse.data.data;
                const aircraftMap = {};
                
                // Crear mapa de naves para búsqueda rápida
                aircraft.forEach(plane => {
                    aircraftMap[plane.id] = plane;
                });

                if (flights.length === 0) {
                    flightsList.innerHTML = '<p>No hay vuelos registrados.</p>';
                    return;
                }

                flightsList.innerHTML = flights.map(flight => {
                    const plane = aircraftMap[flight.nave_id];
                    const aircraftInfo = plane ? `<br>Nave: <strong>${plane.name}</strong> (${plane.model})` : '<br><em style="color: red;">Nave no disponible</em>';
                    
                    return `
                    <div class="flight-management-item">
                        <div>
                            <strong>Vuelo #${flight.id}</strong> - ${flight.origin} → ${flight.destination}${aircraftInfo}<br>
                            Salida: ${flight.departure} | Precio: $${flight.price}
                        </div>
                        <div>
                            <button class="btn-edit" onclick="editFlight(${flight.id})">✏️ Editar</button>
                            <button class="btn-delete" onclick="deleteFlight(${flight.id})">🗑️ Eliminar</button>
                        </div>
                    </div>
                `;
                }).join('');
            } else {
                flightsList.innerHTML = '<p>Error al cargar vuelos.</p>';
            }
        } catch (error) {
            flightsList.innerHTML = '<p>Error de conexión.</p>';
        }
    }

    // Búsqueda de vuelos
    document.getElementById('btnSearch').addEventListener('click', searchFlights);
    document.getElementById('btnClearSearch').addEventListener('click', clearSearch);

    // Permitir buscar con Enter en los campos de búsqueda
    document.getElementById('searchOrigin').addEventListener('keypress', (e) => {
        if (e.key === 'Enter') searchFlights();
    });
    document.getElementById('searchDestination').addEventListener('keypress', (e) => {
        if (e.key === 'Enter') searchFlights();
    });
    document.getElementById('searchDeparture').addEventListener('keypress', (e) => {
        if (e.key === 'Enter') searchFlights();
    });

    // Aircraft search listeners
    document.getElementById('btnSearchAircraft').addEventListener('click', searchAircraft);
    document.getElementById('btnClearSearchAircraft').addEventListener('click', clearSearchAircraft);

    // Permitir buscar con Enter en los campos de búsqueda de naves
    document.getElementById('searchAircraftName').addEventListener('keypress', (e) => {
        if (e.key === 'Enter') searchAircraft();
    });
    document.getElementById('searchAircraftModel').addEventListener('keypress', (e) => {
        if (e.key === 'Enter') searchAircraft();
    });
    document.getElementById('searchAircraftCapacity').addEventListener('keypress', (e) => {
        if (e.key === 'Enter') searchAircraft();
    });

    // Mostrar modal para crear vuelo
    // Mostrar modal para crear usuario
    document.getElementById('btnCreateUser').addEventListener('click', () => {
        document.getElementById('userForm').reset();
        document.getElementById('userModal').style.display = 'flex';
    });

    // Guardar usuario (crear nuevo)
    window.saveUser = async function(event) {
        event.preventDefault();
        const userId = document.getElementById('userId').value;
        const data = {
            name: document.getElementById('userName').value,
            email: document.getElementById('userEmail').value,
            role: document.getElementById('userRole').value
        };

        // Solo incluir password si no está vacío
        const password = document.getElementById('userPassword').value;
        if (password) {
            data.password = password;
        }

        try {
            let response;
            if (userId) {
                // Actualizar usuario existente
                response = await Users.update(userId, data);
            } else {
                // Crear nuevo usuario
                if (!password) {
                    alert('La contraseña es requerida para crear un nuevo usuario');
                    return;
                }
                response = await Users.create(data);
            }

            if (response.success) {
                alert(userId ? 'Usuario actualizado exitosamente' : 'Usuario creado exitosamente');
                closeUserModal();
                loadUsers();
            } else if (response.status === 409) {
                alert('Error: El email ya está registrado');
            } else {
                alert('Error: ' + (response.data?.error || 'Desconocido'));
            }
        } catch (error) {
            alert('Error de conexión');
        }
    };

    // Cargar datos del usuario para editar
    window.editUser = async function(userId) {
        try {
            const response = await Users.getById(userId);
            if (response.success) {
                const user = response.data.data;
                document.getElementById('userId').value = user.id;
                document.getElementById('userName').value = user.name;
                document.getElementById('userEmail').value = user.email;
                document.getElementById('userPassword').value = '';
                document.getElementById('userRole').value = user.role;
                document.getElementById('userPassword').required = false;
                document.getElementById('passwordHint').style.display = 'block';
                document.getElementById('userModalTitle').textContent = 'Editar Usuario';
                document.getElementById('userModal').style.display = 'flex';
            }
        } catch (error) {
            alert('Error al cargar usuario');
        }
    };

    // Cerrar modal de usuario
    window.closeUserModal = function() {
        document.getElementById('userModal').style.display = 'none';
        document.getElementById('userForm').reset();
        document.getElementById('userId').value = '';
        document.getElementById('userPassword').required = true;
        document.getElementById('passwordHint').style.display = 'none';
        document.getElementById('userModalTitle').textContent = 'Crear Nuevo Usuario';
    };

    // Cargar aircraft dropdown y abrir modal para crear vuelo
    window.openFlightModal = async function() {
        document.getElementById('flightId').value = '';
        document.getElementById('flightForm').reset();
        document.getElementById('flightModalTitle').textContent = 'Crear Nuevo Vuelo';
        
        // Cargar lista de naves
        try {
            const response = await Aircraft.list();
            if (response.success) {
                const aircraft = response.data.data;
                const select = document.getElementById('naveSelect');
                select.innerHTML = '<option value="">-- Seleccionar una nave --</option>';
                
                aircraft.forEach(plane => {
                    const option = document.createElement('option');
                    option.value = plane.id;
                    option.textContent = `${plane.name} (${plane.model}) - Capacidad: ${plane.capacity}`;
                    select.appendChild(option);
                });
            }
        } catch (error) {
            alert('Error al cargar naves');
        }
        
        document.getElementById('flightModal').style.display = 'flex';
    };
    
    document.getElementById('btnCreateFlight').addEventListener('click', openFlightModal);

    // Cargar datos del vuelo para editar
    window.editFlight = async function(flightId) {
        try {
            const flightResponse = await Flights.getById(flightId);
            const aircraftResponse = await Aircraft.list();
            
            if (flightResponse.success && aircraftResponse.success) {
                const flight = flightResponse.data.data;
                const aircraft = aircraftResponse.data.data;
                
                document.getElementById('flightId').value = flight.id;
                document.getElementById('origin').value = flight.origin;
                document.getElementById('destination').value = flight.destination;
                document.getElementById('departure').value = flight.departure;
                document.getElementById('arrival').value = flight.arrival;
                document.getElementById('price').value = flight.price;
                
                // Cargar dropdown de naves
                const select = document.getElementById('naveSelect');
                select.innerHTML = '<option value="">-- Seleccionar una nave --</option>';
                
                aircraft.forEach(plane => {
                    const option = document.createElement('option');
                    option.value = plane.id;
                    option.textContent = `${plane.name} (${plane.model}) - Capacidad: ${plane.capacity}`;
                    if (plane.id === flight.nave_id) {
                        option.selected = true;
                    }
                    select.appendChild(option);
                });
                
                document.getElementById('flightModalTitle').textContent = 'Editar Vuelo';
                document.getElementById('flightModal').style.display = 'flex';
            }
        } catch (error) {
            alert('Error al cargar vuelo');
        }
    };

    // Guardar vuelo (crear o editar)
    window.saveFlight = async function(event) {
        event.preventDefault();
        const flightId = document.getElementById('flightId').value;
        const naveSelect = document.getElementById('naveSelect').value;
        
        if (!naveSelect) {
            alert('Debes seleccionar una nave');
            return;
        }
        
        const data = {
            nave_id: parseInt(naveSelect),
            origin: document.getElementById('origin').value,
            destination: document.getElementById('destination').value,
            departure: document.getElementById('departure').value,
            arrival: document.getElementById('arrival').value,
            price: parseFloat(document.getElementById('price').value)
        };

        try {
            let response;
            if (flightId) {
                response = await Flights.update(flightId, data);
            } else {
                response = await Flights.create(data);
            }

            if (response.success) {
                alert(flightId ? 'Vuelo actualizado' : 'Vuelo creado');
                closeFlightModal();
                loadFlightsForManagement();
            } else {
                alert('Error: ' + (response.data?.error || 'Desconocido'));
            }
        } catch (error) {
            alert('Error de conexión');
        }
    };

    // Eliminar vuelo
    window.deleteFlight = async function(flightId) {
        if (confirm('¿Estás seguro de que deseas eliminar este vuelo?')) {
            try {
                const response = await Flights.delete(flightId);
                if (response.success) {
                    alert('Vuelo eliminado');
                    loadFlightsForManagement();
                } else {
                    alert('Error: ' + (response.data?.error || 'Desconocido'));
                }
            } catch (error) {
                alert('Error de conexión');
            }
        }
    };

    // Cerrar modal
    window.closeFlightModal = function() {
        document.getElementById('flightModal').style.display = 'none';
        document.getElementById('flightForm').reset();
    };

    // Cargar naves para administración
    async function loadAircraftForManagement() {
        const aircraftList = document.getElementById('aircraftManagementList');
        aircraftList.innerHTML = '<p>Cargando naves...</p>';

        try {
            const response = await Aircraft.list();

            if (response.success) {
                const aircraft = response.data.data;

                if (aircraft.length === 0) {
                    aircraftList.innerHTML = '<p>No hay naves registradas.</p>';
                    return;
                }

                aircraftList.innerHTML = aircraft.map(plane => `
                    <div class="aircraft-item" style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 10px; display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <p><strong>${plane.name}</strong></p>
                            <p>Modelo: ${plane.model}</p>
                            <p>Capacidad: ${plane.capacity} pasajeros</p>
                        </div>
                        <div>
                            <button onclick="editAircraft(${plane.id})" class="btn-submit" style="width: auto; margin-right: 5px;">✏️ Editar</button>
                            <button onclick="deleteAircraft(${plane.id})" class="btn-cancel" style="width: auto;">🗑️ Eliminar</button>
                        </div>
                    </div>
                `).join('');
            } else {
                aircraftList.innerHTML = '<p>Error al cargar naves.</p>';
            }
        } catch (error) {
            aircraftList.innerHTML = '<p>Error de conexión.</p>';
        }
    }

    // Evento para crear nave
    document.getElementById('btnCreateAircraft').addEventListener('click', () => {
        document.getElementById('aircraftForm').reset();
        document.getElementById('aircraftId').value = '';
        document.getElementById('aircraftModalTitle').textContent = 'Crear Nueva Nave';
        document.getElementById('aircraftModal').style.display = 'flex';
    });

    // Editar nave
    // Editar nave desde vista de consulta pública
    window.editAircraftFromQuery = async function(aircraftId) {
        // Verificar permisos de admin
        if (userRole !== 'administrador') {
            alert('No tienes permiso para editar naves. Solo los administradores pueden modificar naves.');
            return;
        }

        try {
            const response = await Aircraft.getById(aircraftId);
            if (response.success) {
                const plane = response.data.data;
                document.getElementById('aircraftId').value = plane.id;
                document.getElementById('aircraftName').value = plane.name;
                document.getElementById('aircraftModel').value = plane.model;
                document.getElementById('aircraftCapacity').value = plane.capacity;
                document.getElementById('aircraftModalTitle').textContent = 'Editar Nave';
                document.getElementById('aircraftModal').style.display = 'flex';
            }
        } catch (error) {
            alert('Error al cargar nave');
        }
    };

    window.editAircraft = async function(aircraftId) {
        try {
            const response = await Aircraft.getById(aircraftId);
            if (response.success) {
                const plane = response.data.data;
                document.getElementById('aircraftId').value = plane.id;
                document.getElementById('aircraftName').value = plane.name;
                document.getElementById('aircraftModel').value = plane.model;
                document.getElementById('aircraftCapacity').value = plane.capacity;
                document.getElementById('aircraftModalTitle').textContent = 'Editar Nave';
                document.getElementById('aircraftModal').style.display = 'flex';
            }
        } catch (error) {
            alert('Error al cargar nave');
        }
    };

    // Guardar nave (crear o editar)
    window.saveAircraft = async function(event) {
        event.preventDefault();
        const aircraftId = document.getElementById('aircraftId').value;
        const data = {
            name: document.getElementById('aircraftName').value,
            model: document.getElementById('aircraftModel').value,
            capacity: parseInt(document.getElementById('aircraftCapacity').value)
        };

        try {
            let response;
            if (aircraftId) {
                response = await Aircraft.update(aircraftId, data);
            } else {
                response = await Aircraft.create(data.name, data.model, data.capacity);
            }

            if (response.success) {
                alert(aircraftId ? 'Nave actualizada' : 'Nave creada');
                closeAircraftModal();
                loadAircraftForManagement();
            } else {
                alert('Error: ' + (response.data?.error || 'Desconocido'));
            }
        } catch (error) {
            alert('Error de conexión');
        }
    };

    // Eliminar nave
    window.deleteAircraft = async function(aircraftId) {
        if (confirm('¿Estás seguro de que deseas eliminar esta nave?')) {
            try {
                const response = await Aircraft.delete(aircraftId);
                if (response.success) {
                    alert('Nave eliminada');
                    loadAircraftForManagement();
                } else {
                    alert('Error: ' + (response.data?.error || 'Desconocido'));
                }
            } catch (error) {
                alert('Error de conexión');
            }
        }
    };

    // Eliminar nave desde vista de consulta pública
    window.deleteAircraftFromQuery = async function(aircraftId) {
        // Verificar permisos de admin
        if (userRole !== 'administrador') {
            alert('No tienes permiso para eliminar naves. Solo los administradores pueden eliminar naves.');
            return;
        }

        if (confirm('¿Estás seguro de que deseas eliminar esta nave? Esta acción no se puede deshacer.')) {
            try {
                const response = await Aircraft.delete(aircraftId);
                if (response.success) {
                    alert('Nave eliminada correctamente');
                    loadAircraftList();
                } else {
                    alert('Error: ' + (response.data?.error || 'Desconocido'));
                }
            } catch (error) {
                alert('Error de conexión');
            }
        }
    };

    window.deleteAircraft = async function(aircraftId) {
        if (confirm('¿Estás seguro de que deseas eliminar esta nave?')) {
            try {
                const response = await Aircraft.delete(aircraftId);
                if (response.success) {
                    alert('Nave eliminada');
                    loadAircraftForManagement();
                } else {
                    alert('Error: ' + (response.data?.error || 'Desconocido'));
                }
            } catch (error) {
                alert('Error de conexión');
            }
        }
    };

    // Cerrar modal de nave
    window.closeAircraftModal = function() {
        document.getElementById('aircraftModal').style.display = 'none';
        document.getElementById('aircraftForm').reset();
    };

    // Cerrar modal al hacer click fuera
    window.onclick = function(event) {
        const flightModal = document.getElementById('flightModal');
        const aircraftModal = document.getElementById('aircraftModal');
        if (event.target === flightModal) {
            closeFlightModal();
        }
        if (event.target === aircraftModal) {
            closeAircraftModal();
        }
    };

    // Mostrar sección de inicio
    showSection('homeSection');
});
