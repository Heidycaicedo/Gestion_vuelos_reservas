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

            if (response.success) {
                // Redirigir al dashboard
                window.location.href = 'index.html';
            } else {
                errorMessage.textContent = response.data.error || 'Error al iniciar sesión';
                errorMessage.classList.add('show');
            }
        } catch (error) {
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
