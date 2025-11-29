// Configuración de URLs de los microservicios
// Detecta automáticamente el servidor (localhost o IP remota)
const API_USUARIOS = `http://${window.location.hostname}:8001`;
const API_VUELOS = `http://${window.location.hostname}:8002`;

// Funciones auxiliares para hacer requests
async function request(url, method = 'GET', data = null) {
    const options = {
        method: method,
        headers: {
            'Content-Type': 'application/json',
        },
    };

    if (data) {
        options.body = JSON.stringify(data);
    }

    const token = localStorage.getItem('token');
    if (token) {
        options.headers['Authorization'] = `Bearer ${token}`;
    }

    try {
        const response = await fetch(url, options);
        const result = await response.json();

        // Si el token es inválido o expirado, redirigir a login
        if (response.status === 401) {
            localStorage.removeItem('token');
            localStorage.removeItem('usuario_id');
            localStorage.removeItem('rol');
            window.location.href = 'login.html';
            return { success: false, status: 401, data: result };
        }

        // Si no hay autorización (acceso denegado por rol), redirigir a inicio
        if (response.status === 403) {
            return { success: false, status: 403, data: result };
        }

        return { success: response.ok, status: response.status, data: result };
    } catch (error) {
        return { success: false, error: error.message };
    }
}

// Autenticación
const Auth = {
    async register(name, email, password) {
        return request(`${API_USUARIOS}/api/auth/register`, 'POST', {
            name,
            email,
            password,
        });
    },

    async login(email, password) {
        const response = await request(`${API_USUARIOS}/api/auth/login`, 'POST', {
            email,
            password,
        });

        if (response.success) {
            localStorage.setItem('token', response.data.data.token);
            localStorage.setItem('user_id', response.data.data.user_id);
            localStorage.setItem('role', response.data.data.role);
        }

        return response;
    },

    async logout(token) {
        const response = await request(`${API_USUARIOS}/api/auth/logout`, 'POST', { token });

        if (response.success) {
            localStorage.removeItem('token');
            localStorage.removeItem('user_id');
            localStorage.removeItem('role');
        }

        return response;
    },

    async validateToken(token) {
        return request(`${API_USUARIOS}/api/auth/validate`, 'POST', { token });
    },

    getToken() {
        return localStorage.getItem('token');
    },

    getUserId() {
        return localStorage.getItem('user_id');
    },

    getRole() {
        return localStorage.getItem('role');
    },

    isAuthenticated() {
        return !!localStorage.getItem('token');
    },
};

// Gestión de Usuarios
const Users = {
    async list() {
        return request(`${API_USUARIOS}/api/users`, 'GET');
    },

    async getById(id) {
        return request(`${API_USUARIOS}/api/users/${id}`, 'GET');
    },

    async create(data) {
        return request(`${API_USUARIOS}/api/users`, 'POST', data);
    },

    async update(id, data) {
        return request(`${API_USUARIOS}/api/users/${id}`, 'PUT', data);
    },

    async updateRole(id, role) {
        return request(`${API_USUARIOS}/api/users/${id}/role`, 'PUT', { role });
    },
};

// Gestión de Vuelos
const Flights = {
    async list(filtros = {}) {
        let url = `${API_VUELOS}/api/flights`;
        const params = new URLSearchParams();

        if (filtros.origin) params.append('origin', filtros.origin);
        if (filtros.destination) params.append('destination', filtros.destination);
        if (filtros.departure) params.append('departure', filtros.departure);

        if (params.toString()) {
            url += '?' + params.toString();
        }

        return request(url, 'GET');
    },

    async search(origin = null, destination = null, departure = null) {
        return this.list({ origin, destination, departure });
    },

    async getById(id) {
        return request(`${API_VUELOS}/api/flights/${id}`, 'GET');
    },

    async create(data) {
        return request(`${API_VUELOS}/api/flights`, 'POST', data);
    },

    async update(id, data) {
        return request(`${API_VUELOS}/api/flights/${id}`, 'PUT', data);
    },

    async delete(id) {
        return request(`${API_VUELOS}/api/flights/${id}`, 'DELETE');
    },
};

// Gestión de Naves
const Aircraft = {
    async list() {
        return request(`${API_VUELOS}/api/aircraft`, 'GET');
    },

    async getById(id) {
        return request(`${API_VUELOS}/api/aircraft/${id}`, 'GET');
    },

    async create(name, capacity, model) {
        return request(`${API_VUELOS}/api/aircraft`, 'POST', {
            name,
            capacity,
            model
        });
    },

    async update(id, data) {
        return request(`${API_VUELOS}/api/aircraft/${id}`, 'PUT', data);
    },

    async delete(id) {
        return request(`${API_VUELOS}/api/aircraft/${id}`, 'DELETE');
    },
};

// Gestión de Reservas
const Reservations = {
    async list(userId = null) {
        let url = `${API_VUELOS}/api/reservations`;
        if (userId) {
            url += `?user_id=${userId}`;
        }
        return request(url, 'GET');
    },

    async create(flightId) {
        return request(`${API_VUELOS}/api/reservations`, 'POST', {
            flight_id: flightId,
        });
    },

    async cancel(id) {
        return request(`${API_VUELOS}/api/reservations/${id}`, 'DELETE');
    },
};
