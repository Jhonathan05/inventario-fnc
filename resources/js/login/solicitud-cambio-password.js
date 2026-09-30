document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('login-form');
    const checkbox = document.getElementById('solicitar_cambio_password');
    const toggleGroup = document.getElementById('solicitud-toggle-group');
    const passwordGroup = document.getElementById('password-group');
    const passwordInput = document.getElementById('password');
    const submitButton = document.getElementById('login-submit-button');
    const info = document.getElementById('solicitud-info');

    if (!form || !checkbox || !passwordGroup || !passwordInput || !submitButton || !info) {
        return;
    }

    const loginAction = form.getAttribute('action') || '';
    const solicitudAction = form.dataset.solicitudAction || '';

    if (!solicitudAction) {
        if (toggleGroup) {
            toggleGroup.classList.add('hidden');
        }
        info.classList.add('hidden');
        passwordGroup.classList.remove('hidden');
        passwordInput.required = true;
        passwordInput.disabled = false;
        submitButton.textContent = 'Iniciar sesión';
        return;
    }

    const applyMode = function () {
        const solicitudActiva = checkbox.checked;

        passwordGroup.classList.toggle('hidden', solicitudActiva);
        info.classList.toggle('hidden', !solicitudActiva);

        passwordInput.required = !solicitudActiva;
        passwordInput.disabled = solicitudActiva;

        if (solicitudActiva) {
            passwordInput.value = '';
            form.setAttribute('action', solicitudAction);
            submitButton.textContent = 'Enviar solicitud';
        } else {
            form.setAttribute('action', loginAction);
            submitButton.textContent = 'Iniciar sesión';
        }
    };

    applyMode();
    checkbox.addEventListener('change', applyMode);
});
