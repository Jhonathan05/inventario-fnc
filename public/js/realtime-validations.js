/**
 * Real-time Form Validation for Inventario FNC
 * Aplica validaciones semánticas según el atributo data-validate en los inputs.
 */

document.addEventListener('DOMContentLoaded', function() {
    const rules = {
        'nombres': {
            regex: /^[\p{L}\s\'\-\.]+$/u,
            replaceRegex: /[^\p{L}\s\'\-\.]/gu,
            message: 'Este campo solo permite letras, espacios, apóstrofes y guiones.'
        },
        'alfanumerico': {
            regex: /^[\p{L}0-9\s\'\-\.,_]+$/u,
            replaceRegex: /[^\p{L}0-9\s\'\-\.,_]/gu,
            message: 'Este campo contiene caracteres no permitidos.'
        },
        'placa': {
            regex: /^[0-9]+$/,
            replaceRegex: /[^0-9]/g,
            message: 'La placa debe ser estrictamente numérica.'
        },
        'serial': {
            regex: /^[A-Za-z0-9\-\s\.\_]+$/,
            replaceRegex: /[^A-Za-z0-9\-\s\.\_]/g,
            message: 'El serial solo permite letras, números, guiones y puntos.'
        },
        'telefono': {
            regex: /^[\d\s\-\+\(\)]+$/,
            replaceRegex: /[^\d\s\-\+\(\)]/g,
            message: 'El teléfono solo permite números, espacios y los signos + o -.'
        },
        'cedula': {
            regex: /^[0-9]+$/,
            replaceRegex: /[^0-9]/g,
            message: 'La identificación/cédula debe contener solo números.'
        }
    };

    function validateInput(input) {
        const ruleName = input.getAttribute('data-validate');
        if (!ruleName || !rules[ruleName]) return true;
        
        const rule = rules[ruleName];

        // Bloquear caracteres no permitidos de inmediato
        if (rule.replaceRegex) {
            const originalValue = input.value;
            const newValue = originalValue.replace(rule.replaceRegex, '');
            if (originalValue !== newValue) {
                // Preservar la posición del cursor si es posible
                const start = input.selectionStart;
                const end = input.selectionEnd;
                input.value = newValue;
                try {
                    input.setSelectionRange(start - (originalValue.length - newValue.length), end - (originalValue.length - newValue.length));
                } catch (e) {} // Ignorar errores en inputs que no soportan setSelectionRange
            }
        }

        const value = input.value;
        if (value.trim() === '') {
            input.classList.remove('is-invalid');
            removeError(input);
            return true; // Opcionales permitidos, required es manejado por HTML5
        }

        if (!rule.regex.test(value)) {
            input.classList.add('is-invalid');
            showError(input, rule.message);
            return false;
        } else {
            input.classList.remove('is-invalid');
            removeError(input);
            return true;
        }
    }

    function showError(input, message) {
        let feedback = input.nextElementSibling;
        if (!feedback || !feedback.classList.contains('invalid-feedback')) {
            feedback = document.createElement('div');
            feedback.className = 'invalid-feedback realtime-feedback';
            input.parentNode.insertBefore(feedback, input.nextSibling);
        }
        feedback.textContent = message;
        // Also use setCustomValidity to block form submission
        input.setCustomValidity(message);
    }

    function removeError(input) {
        let feedback = input.nextElementSibling;
        if (feedback && feedback.classList.contains('realtime-feedback')) {
            feedback.remove();
        }
        input.setCustomValidity('');
    }

    // Attach event listeners to all inputs with data-validate
    const inputs = document.querySelectorAll('input[data-validate], textarea[data-validate]');
    inputs.forEach(input => {
        input.addEventListener('input', function() {
            validateInput(this);
        });
        // Run once on load just in case
        if (input.value) {
            validateInput(input);
        }
    });

    // Validar formularios enteros antes de submit por si el usuario ignoró el color rojo
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', function(e) {
            let hasError = false;
            const formInputs = this.querySelectorAll('input[data-validate], textarea[data-validate]');
            formInputs.forEach(input => {
                if (!validateInput(input)) {
                    hasError = true;
                }
            });
            if (hasError) {
                e.preventDefault();
                e.stopPropagation();
            }
        });
    });
});
