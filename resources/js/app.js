import './bootstrap';
import './login/solicitud-cambio-password';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

// Global listener to capitalize the first letter of inputs and textareas
document.addEventListener('input', function(e) {
    if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') {
        const type = e.target.type;
        const name = e.target.name || '';
        
        // Tipos de input a excluir
        const excludedTypes = ['password', 'email', 'url', 'number', 'hidden', 'color', 'date', 'datetime-local', 'month', 'range', 'search', 'time', 'week', 'file', 'checkbox', 'radio'];
        // Nombres de campos a excluir (coincidencia parcial o total según sea necesario)
        const excludedNames = ['password', 'email', 'username', 'slug', 'token', 'uuid'];
        
        if (!excludedTypes.includes(type) && !excludedNames.some(ex => name.toLowerCase().includes(ex))) {
            let val = e.target.value;
            if (val.length > 0) {
                const firstChar = val.charAt(0);
                const upperFirstChar = firstChar.toUpperCase();
                
                // Solo si el primer caracter cambia
                if (firstChar !== upperFirstChar) {
                    e.target.value = upperFirstChar + val.slice(1);
                }
            }
        }
    }
});
