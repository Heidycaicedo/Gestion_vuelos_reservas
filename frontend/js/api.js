// Configuración de URLs de los microservicios
const API_USUARIOS = 'http://localhost/Gestion_vuelos_reservas/microservicio_usuarios/public';
const API_VUELOS = 'http://localhost/Gestion_vuelos_reservas/microservicio_vuelos/public';

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
    async register(nombre, email, password) {
        return request(`${API_USUARIOS}/api/usuarios/registrar`, 'POST', {
            nombre,
            email,
            password,
        });
    },

    async login(email, password) {
        const response = await request(`${API_USUARIOS}/api/usuarios/login`, 'POST', {
            email,
            password,
        });

        if (response.success) {
            localStorage.setItem('token', response.data.data.token);
            localStorage.setItem('usuario_id', response.data.data.usuario_id);
            localStorage.setItem('rol', response.data.data.rol);
        }

        return response;
    },

    async logout(token) {
        const response = await request(`${API_USUARIOS}/api/usuarios/logout`, 'POST', { token });

        if (response.success) {
            localStorage.removeItem('token');
            localStorage.removeItem('usuario_id');
            localStorage.removeItem('rol');
        }

        return response;
    },

    async validateToken(token) {
        return request(`${API_USUARIOS}/api/usuarios/validar-token`, 'POST', { token });
    },

    getToken() {
        return localStorage.getItem('token');
    },

    getUserId() {
        return localStorage.getItem('usuario_id');
    },

    getRol() {
        return localStorage.getItem('rol');
    },

    isAuthenticated() {
        return !!localStorage.getItem('token');
    },
};

// Gestión de Usuarios
const Users = {
    async list() {
        return request(`${API_USUARIOS}/api/usuarios`, 'GET');
    },

    async getById(id) {
        return request(`${API_USUARIOS}/api/usuarios/${id}`, 'GET');
    },

    async update(id, data) {
        return request(`${API_USUARIOS}/api/usuarios/${id}`, 'PUT', data);
    },

    async updateRole(id, rol) {
        return request(`${API_USUARIOS}/api/usuarios/${id}/rol`, 'PUT', { rol });
    },
};

// Gestión de Vuelos
const Flights = {
    async list(filtros = {}) {
        let url = `${API_VUELOS}/api/vuelos`;
        const params = new URLSearchParams();

        // 2.3 Soporte para búsqueda por origen, destino y fecha
        if (filtros.origen) params.append('origen', filtros.origen);
        if (filtros.destino) params.append('destino', filtros.destino);
        if (filtros.fecha) params.append('fecha', filtros.fecha);
        if (filtros.fecha_desde) params.append('fecha_desde', filtros.fecha_desde);
        if (filtros.fecha_hasta) params.append('fecha_hasta', filtros.fecha_hasta);

        if (params.toString()) {
            url += '?' + params.toString();
        }

        return request(url, 'GET');
    },

    async search(origen = null, destino = null, fecha = null) {
        return this.list({ origen, destino, fecha });
    },

    async searchByDateRange(fecha_desde, fecha_hasta) {
        return this.list({ fecha_desde, fecha_hasta });
    },

    async getById(id) {
        return request(`${API_VUELOS}/api/vuelos/${id}`, 'GET');
    },

    async create(data) {
        return request(`${API_VUELOS}/api/vuelos`, 'POST', data);
    },

    async update(id, data) {
        return request(`${API_VUELOS}/api/vuelos/${id}`, 'PUT', data);
    },

    async delete(id) {
        return request(`${API_VUELOS}/api/vuelos/${id}`, 'DELETE');
    },
};

// Gestión de Naves
const Aircraft = {
    async list() {
        return request(`${API_VUELOS}/api/naves`, 'GET');
    },

    async getById(id) {
        return request(`${API_VUELOS}/api/naves/${id}`, 'GET');
    },

    async create(modelo, capacidad, matricula) {
        return request(`${API_VUELOS}/api/naves`, 'POST', {
            modelo,
            capacidad,
            matricula
        });
    },

    async update(id, data) {
        return request(`${API_VUELOS}/api/naves/${id}`, 'PUT', data);
    },

    async delete(id) {
        return request(`${API_VUELOS}/api/naves/${id}`, 'DELETE');
    },
};

// Gestión de Reservas
const Reservations = {
    async list(usuarioId = null) {
        let url = `${API_VUELOS}/api/reservas`;
        if (usuarioId) {
            url += `?usuario_id=${usuarioId}`;
        }
        return request(url, 'GET');
    },

    async create(vuoloId, numeroAsiento) {
        return request(`${API_VUELOS}/api/reservas`, 'POST', {
            vuelo_id: vuoloId,
            numero_asiento: numeroAsiento,
        });
    },

    async cancel(id) {
        return request(`${API_VUELOS}/api/reservas/${id}`, 'DELETE');
    },
};
