/**
 * Shell del aplicativo: paleta de comandos (⌘K / Ctrl+K).
 *
 * Sin dependencias. Los destinos se inyectan desde Blade en
 * window.fncPaletteItems = [{ group, label, icon, url, keywords }].
 *
 * La navegación se hace con un <a> sintético para que el interceptor global
 * de enlaces del layout agregue el parámetro _tab de la pestaña actual.
 */
(function () {
    'use strict';

    var overlay = document.getElementById('fncPalette');
    var trigger = document.getElementById('fncPaletteTrigger');
    var input = document.getElementById('fncPaletteInput');
    var list = document.getElementById('fncPaletteList');

    if (!overlay || !input || !list) {
        return;
    }

    var items = Array.isArray(window.fncPaletteItems) ? window.fncPaletteItems : [];
    var filtered = items.slice();
    var highlighted = 0;
    var lastFocused = null;

    function normalize(text) {
        return String(text || '')
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '');
    }

    function navigate(url) {
        var anchor = document.createElement('a');
        anchor.href = url;
        anchor.style.display = 'none';
        document.body.appendChild(anchor);
        anchor.click();
        anchor.remove();
    }

    function render() {
        list.innerHTML = '';

        if (filtered.length === 0) {
            var empty = document.createElement('li');
            empty.className = 'fnc-palette__empty';
            empty.textContent = 'Sin resultados para la búsqueda.';
            list.appendChild(empty);
            return;
        }

        var currentGroup = null;

        filtered.forEach(function (item, index) {
            if (item.group !== currentGroup) {
                currentGroup = item.group;
                var heading = document.createElement('li');
                heading.className = 'fnc-palette__group';
                heading.setAttribute('aria-hidden', 'true');
                heading.textContent = currentGroup;
                list.appendChild(heading);
            }

            var option = document.createElement('li');
            option.className = 'fnc-palette__item';
            option.setAttribute('role', 'option');
            option.dataset.url = item.url;
            option.dataset.index = String(index);
            option.setAttribute('aria-selected', index === highlighted ? 'true' : 'false');

            var icon = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
            icon.setAttribute('class', 'fnc-icon');
            icon.setAttribute('width', '17');
            icon.setAttribute('height', '17');
            icon.setAttribute('aria-hidden', 'true');
            var use = document.createElementNS('http://www.w3.org/2000/svg', 'use');
            use.setAttribute('href', '#fnc-i-' + (item.icon || 'circle-dot'));
            icon.appendChild(use);

            var label = document.createElement('span');
            label.textContent = item.label;

            option.appendChild(icon);
            option.appendChild(label);

            if (item.hint) {
                var hint = document.createElement('span');
                hint.className = 'fnc-muted';
                hint.style.marginLeft = 'auto';
                hint.style.fontSize = '0.75rem';
                hint.textContent = item.hint;
                option.appendChild(hint);
            }

            option.addEventListener('mouseenter', function () {
                highlighted = index;
                syncSelection();
            });

            option.addEventListener('click', function () {
                close();
                navigate(item.url);
            });

            list.appendChild(option);
        });

        syncSelection();
    }

    function syncSelection() {
        var options = list.querySelectorAll('.fnc-palette__item');
        options.forEach(function (option, index) {
            var selected = index === highlighted;
            option.setAttribute('aria-selected', selected ? 'true' : 'false');
            if (selected && typeof option.scrollIntoView === 'function') {
                option.scrollIntoView({ block: 'nearest' });
            }
        });
    }

    function filter(term) {
        var needle = normalize(term.trim());

        filtered = needle === ''
            ? items.slice()
            : items.filter(function (item) {
                var haystack = normalize(
                    [item.label, item.group, item.hint, (item.keywords || []).join(' ')].join(' ')
                );

                return haystack.indexOf(needle) !== -1;
            });

        highlighted = 0;
        render();
    }

    function open() {
        lastFocused = document.activeElement;
        overlay.hidden = false;
        overlay.classList.add('is-open');
        input.value = '';
        filter('');
        document.body.style.overflow = 'hidden';
        window.setTimeout(function () { input.focus(); }, 10);
    }

    function close() {
        overlay.classList.remove('is-open');
        overlay.hidden = true;
        document.body.style.overflow = '';

        if (lastFocused && typeof lastFocused.focus === 'function') {
            lastFocused.focus();
        }
    }

    function isOpen() {
        return overlay.classList.contains('is-open');
    }

    if (trigger) {
        trigger.addEventListener('click', function (event) {
            event.preventDefault();
            open();
        });
    }

    overlay.addEventListener('mousedown', function (event) {
        if (event.target === overlay) {
            close();
        }
    });

    input.addEventListener('input', function () {
        filter(input.value);
    });

    document.addEventListener('keydown', function (event) {
        var key = event.key;

        if ((event.ctrlKey || event.metaKey) && (key === 'k' || key === 'K')) {
            event.preventDefault();
            if (isOpen()) { close(); } else { open(); }
            return;
        }

        if (key === '/' && !isOpen()) {
            var tag = (document.activeElement && document.activeElement.tagName) || '';
            if (tag !== 'INPUT' && tag !== 'TEXTAREA' && tag !== 'SELECT') {
                event.preventDefault();
                open();
            }
            return;
        }

        if (!isOpen()) {
            return;
        }

        if (key === 'Escape') {
            event.preventDefault();
            close();
        } else if (key === 'ArrowDown') {
            event.preventDefault();
            highlighted = Math.min(highlighted + 1, filtered.length - 1);
            syncSelection();
        } else if (key === 'ArrowUp') {
            event.preventDefault();
            highlighted = Math.max(highlighted - 1, 0);
            syncSelection();
        } else if (key === 'Enter') {
            event.preventDefault();
            var item = filtered[highlighted];
            if (item) {
                close();
                navigate(item.url);
            }
        }
    });

    render();
})();
