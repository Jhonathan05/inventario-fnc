(function () {
    const SIDEBAR_SCROLL_KEY = 'inventario.sidebar.navMenu.scrollTop';

    function leerScrollGuardado() {
        try {
            const enSession = sessionStorage.getItem(SIDEBAR_SCROLL_KEY);
            if (enSession !== null) return Number(enSession);
        } catch (_) {
            // Ignorar.
        }

        try {
            const enLocal = localStorage.getItem(SIDEBAR_SCROLL_KEY);
            if (enLocal !== null) return Number(enLocal);
        } catch (_) {
            // Ignorar.
        }

        return null;
    }

    function obtenerContenedorScrollSidebar() {
        const sidebar = document.getElementById('sidebar');
        if (!sidebar) return null;
        const navMenu = sidebar.querySelector('.nav-menu');
        return navMenu || sidebar;
    }

    function guardarScrollSidebar(contenedor) {
        if (!contenedor) return;
        const valor = String(contenedor.scrollTop || 0);
        try {
            sessionStorage.setItem(SIDEBAR_SCROLL_KEY, valor);
        } catch (_) {
            // Ignorar y probar fallback.
        }

        try {
            localStorage.setItem(SIDEBAR_SCROLL_KEY, valor);
        } catch (_) {
            // Sin almacenamiento disponible, no interrumpe la navegacion.
        }
    }

    function restaurarScrollSidebar(contenedor) {
        if (!contenedor) return;
        const valor = leerScrollGuardado();
        if (valor === null || !Number.isFinite(valor)) return;
        contenedor.scrollTop = Math.max(0, valor);
    }

    function restaurarScrollSidebarConReintento(contenedor) {
        if (!contenedor) return;

        const intentosMs = [0, 40, 120, 260, 500];
        intentosMs.forEach(function (delay) {
            setTimeout(function () {
                restaurarScrollSidebar(contenedor);
                asegurarModuloActivoVisible(contenedor);
            }, delay);
        });
    }

    function asegurarModuloActivoVisible(contenedor) {
        if (!contenedor) return;
        const activos = Array.from(contenedor.querySelectorAll('.nav-link.active'));
        if (activos.length === 0) return;

        const rutaActual = window.location.pathname.replace(/\/$/, '');
        const activo = activos.find(function (link) {
            try {
                const href = new URL(link.href, window.location.origin);
                return href.pathname.replace(/\/$/, '') === rutaActual;
            } catch (_) {
                return false;
            }
        }) || activos[activos.length - 1];

        if (!activo) return;

        const contRect = contenedor.getBoundingClientRect();
        const actRect = activo.getBoundingClientRect();
        const fueraArriba = actRect.top < contRect.top;
        const fueraAbajo = actRect.bottom > contRect.bottom;

        if (fueraArriba || fueraAbajo) {
            activo.scrollIntoView({ block: 'nearest', inline: 'nearest' });
        }
    }

    function inicializarPersistenciaScrollSidebar() {
        const contenedor = obtenerContenedorScrollSidebar();
        if (!contenedor) return;

        restaurarScrollSidebarConReintento(contenedor);
        window.addEventListener('load', function () {
            restaurarScrollSidebarConReintento(contenedor);
        });

        let ticking = false;
        contenedor.addEventListener('scroll', function () {
            if (ticking) return;
            ticking = true;
            requestAnimationFrame(function () {
                guardarScrollSidebar(contenedor);
                ticking = false;
            });
        }, { passive: true });

        document.addEventListener('click', function (event) {
            const enlace = event.target.closest('#sidebar .nav-menu .nav-link');
            if (!enlace) return;
            guardarScrollSidebar(contenedor);
        });

        window.addEventListener('pagehide', function () {
            guardarScrollSidebar(contenedor);
        });

        document.addEventListener('visibilitychange', function () {
            if (document.visibilityState === 'hidden') {
                guardarScrollSidebar(contenedor);
            }
        });
    }

    inicializarPersistenciaScrollSidebar();

    // === Capitalización Automática ===
    function esCampoValido(el) {
        if (!el || el.disabled || el.readOnly) return false;
        if (el.dataset.noCapitalizeFirst === 'true') return false;
        if (el.tagName === 'TEXTAREA') return true;
        if (el.tagName !== 'INPUT') return false;
        const tipo = (el.getAttribute('type') || 'text').toLowerCase();
        const excluidos = ['email', 'password', 'number', 'date', 'datetime-local', 'time', 'month', 'week', 'url', 'search', 'tel', 'hidden', 'file', 'checkbox', 'radio'];
        return tipo === 'text' && !excluidos.includes(tipo);
    }

    function capitalizarPrimeraLetra(el) {
        if (!esCampoValido(el) || !el.value) return;
        const indice = el.value.search(/[A-Za-zÁÉÍÓÚÑáéíóúñ]/u);
        if (indice < 0) return;
        const letra = el.value.charAt(indice);
        const mayuscula = letra.toLocaleUpperCase('es-CO');
        if (letra === mayuscula) return;
        el.value = el.value.slice(0, indice) + mayuscula + el.value.slice(indice + 1);
    }

    document.addEventListener('input', e => capitalizarPrimeraLetra(e.target));
    document.addEventListener('change', e => capitalizarPrimeraLetra(e.target));

    // === Alertas Globales (SweetAlert2) ===
    window.initAlerts = function(flashSuccess, flashError, flashWarning, validationErrors) {
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
            didOpen: (toast) => {
                toast.onmouseenter = Swal.stopTimer;
                toast.onmouseleave = Swal.resumeTimer;
            }
        });

        if (flashSuccess) Toast.fire({ icon: 'success', title: flashSuccess });
        if (flashWarning) Toast.fire({ icon: 'warning', title: flashWarning });
        if (flashError) Swal.fire({ icon: 'error', title: 'Error', text: flashError, confirmButtonColor: '#9e052b' });
        
        if (Array.isArray(validationErrors) && validationErrors.length > 0) {
            const htmlErrores = '<ul style="text-align:left; margin:0; padding-left:1rem;">'
                + validationErrors.map(msg => `<li>${msg}</li>`).join('') + '</ul>';
            Swal.fire({
                icon: 'error',
                title: 'Revise los campos',
                html: htmlErrores,
                confirmButtonColor: '#9e052b',
            });
        }
    };

    // === Confirmación de Eliminación Global ===
    document.addEventListener('click', function (event) {
        const trigger = event.target.closest('[data-delete-url]');
        if (!trigger) return;
        event.preventDefault();

        const url = trigger.getAttribute('data-delete-url');
        const nombre = trigger.getAttribute('data-delete-name') || 'este registro';
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        const tabId = sessionStorage.getItem('_tab_id') || '';

        const requiereConfirmacion = trigger.hasAttribute('data-delete-require-confirm');

        let swalOptions = {
            icon: 'warning',
            title: '¿Estás seguro?',
            text: 'Se eliminará ' + nombre + '. Esta acción no se puede deshacer.',
            showCancelButton: true,
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
        };

        if (requiereConfirmacion) {
            swalOptions.title = 'ATENCIÓN: ELIMINACIÓN PERMANENTE';
            swalOptions.html = '<p class="text-danger mb-3">Se eliminará permanentemente <strong>' + nombre + '</strong>.</p><p>Para proceder, escribe la palabra <strong>CONFIRMAR</strong> en mayúsculas abajo:</p>';
            swalOptions.text = undefined;
            swalOptions.input = 'text';
            swalOptions.inputPlaceholder = 'Escribe CONFIRMAR';
            swalOptions.inputValidator = (value) => {
                if (value !== 'CONFIRMAR') {
                    return 'Debes escribir la palabra CONFIRMAR exactamente en mayúsculas';
                }
            };
        }

        Swal.fire(swalOptions).then((result) => {
            if (result.isConfirmed && url) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = url;
                form.innerHTML = `
                    <input type="hidden" name="_token" value="${csrfToken}">
                    <input type="hidden" name="_tab" value="${tabId}">
                    <input type="hidden" name="_method" value="DELETE">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        });
    });

    // === Confirmación de Restauración Global ===
    document.addEventListener('click', function (event) {
        const trigger = event.target.closest('.js-form-restaurar button[type="submit"]');
        if (!trigger) return;
        event.preventDefault();

        const form = trigger.closest('form');
        if (!form) return;

        const nombreEquipo = form.dataset.equipo || 'el registro';

        Swal.fire({
            title: '¿Estás seguro?',
            html: `Se restaurará <strong>${nombreEquipo}</strong> a estado <strong>Activo</strong>.<br>¿Deseas continuar?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#198754',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, restaurar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                form.submit();
            }
        });
    });
})();
