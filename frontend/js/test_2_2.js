// Script de prueba para verificar 2.2
console.log('=== Test 2.2: Consultar todos los vuelos ===');

// 1. Verificar que el endpoint es accesible sin token
fetch('http://localhost:8002/api/flights', {
    method: 'GET',
    headers: {
        'Content-Type': 'application/json'
    }
})
.then(response => response.json())
.then(data => {
    console.log('✅ Endpoint sin token:', data.success ? 'ACCESIBLE' : 'ERROR');
    console.log('   Vuelos encontrados:', data.data.length);
    
    if (data.data.length > 0) {
        console.log('   Primero vuelo:', data.data[0]);
    }
})
.catch(error => console.error('❌ Error sin token:', error));

// 2. Verificar que el endpoint funciona con token
const token = localStorage.getItem('token');
if (token) {
    fetch('http://localhost:8002/api/flights', {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${token}`
        }
    })
    .then(response => response.json())
    .then(data => {
        console.log('✅ Endpoint con token:', data.success ? 'ACCESIBLE' : 'ERROR');
        console.log('   Vuelos encontrados:', data.data.length);
    })
    .catch(error => console.error('❌ Error con token:', error));
}
