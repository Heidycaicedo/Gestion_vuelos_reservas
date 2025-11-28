document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.getElementById('loginForm');
    const errorMessage = document.getElementById('errorMessage');
    const registerLink = document.getElementById('registerLink');

    loginForm.addEventListener('submit', async function(e) {
        e.preventDefault();

        const email = document.getElementById('email').value;
        const password = document.getElementById('password').value;

        errorMessage.classList.remove('show');
        errorMessage.textContent = '';

        try {
            const response = await Auth.login(email, password);
            console.log('Auth.login response:', response);

            if (response && response.success) {
                console.log('Login successful, token stored.');
                // Redirigir al dashboard
                window.location.href = 'index.html';
            } else {
                const msg = (response && response.data && response.data.error) ? response.data.error : (response && response.error) ? response.error : 'Error al iniciar sesión';
                console.error('Login failed:', response);
                errorMessage.textContent = msg;
                errorMessage.classList.add('show');
            }
        } catch (error) {
            console.error('Login request error:', error);
            errorMessage.textContent = 'Error de conexión. Intenta más tarde.';
            errorMessage.classList.add('show');
        }
    });

    registerLink.addEventListener('click', function(e) {
        e.preventDefault();
        // TODO: Implementar registro
        alert('Funcionalidad de registro próximamente');
    });
});
