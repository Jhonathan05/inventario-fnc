/* ============================================================================
 * FNCCombobox — combobox de búsqueda premium para todos los <select> del app
 * ----------------------------------------------------------------------------
 * Imita el combobox de la referencia: un campo redondeado donde se puede
 * escribir para filtrar y un panel flotante de resultados con icono de lupa
 * (y de reloj para las selecciones recientes).
 *
 * El <select> nativo permanece en el DOM (oculto pero visible para pruebas
 * automatizadas y degradación elegante); los valores suelen escribirse por
 * medio de una interceptación de las propiedades value/selectedIndex, así
 * que cualquier código que haga select.value = x sigue refrescando la UI.
 *
 * Exposición pública:
 *   FNCCombobox.enhance(el)    // mejora un select concreto
 *   FNCCombobox.sync(el)       // refresca el campo visible con el valor actual
 *   FNCCombobox.close()        // cierra todos los paneles abiertos
 * ========================================================================= */
/* global document, window */
(function () {
    'use strict';

    var SVG_SEARCH = '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="M20 20l-3.5-3.5"/></svg>';
    var SVG_CLOCK  = '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>';
    var SVG_CHEV   = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M6 9l6 6 6-6"/></svg>';

    var RECENTS_MAX = 3;
    var openState = null;   // { sel, panel, input, filter }
    var uid = 0;

    /* ------------------------------------------------------------------ */
    /* Utilidades                                                          */
    /* ------------------------------------------------------------------ */
    function labelOf(sel) {
        var o = sel.selectedOptions && sel.selectedOptions[0];
        return o ? o.textContent.trim() : '';
    }

    function recentsKey(sel) {
        return 'fnc-combo-recents:' + (sel.name || sel.id || '?');
    }

    function getRecents(sel) {
        try {
            var arr = JSON.parse(window.localStorage.getItem(recentsKey(sel)) || '[]');
            return Array.isArray(arr) ? arr.filter(function (v) { return typeof v === 'string'; }).slice(0, RECENTS_MAX) : [];
        } catch (e) { return []; }
    }

    function pushRecent(sel, value) {
        if (!value) return;
        try {
            var arr = getRecents(sel).filter(function (v) { return v !== value; });
            arr.unshift(value);
            window.localStorage.setItem(recentsKey(sel), JSON.stringify(arr.slice(0, RECENTS_MAX)));
        } catch (e) { /* almacenamiento no disponible */ }
    }

    function norm(s) {
        return (s || '').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
    }

    /* Refresca el texto visible del campo con el valor del select */
    function refreshUI(sel) {
        var st = sel.__fncSt;
        if (!st) return;
        var lb = labelOf(sel);
        st.input.value = lb;
        st.input.setAttribute('data-empty', lb ? '0' : '1');
        if (!lb) st.input.placeholder = st.placeholder;
        st.input.setAttribute('title', lb);
    }

    /* Intercepta value/selectedIndex para que los cambios programáticos
       hechos por el resto del código también actualicen la UI. */
    function intercept(sel) {
        if (sel.__fncInt) return;
        sel.__fncInt = true;
        var dv = Object.getOwnPropertyDescriptor(HTMLSelectElement.prototype, 'value');
        Object.defineProperty(sel, 'value', {
            configurable: true,
            get: function () { return dv.get.call(sel); },
            set: function (v) { dv.set.call(sel, v); refreshUI(sel); }
        });
        var di = Object.getOwnPropertyDescriptor(HTMLSelectElement.prototype, 'selectedIndex');
        Object.defineProperty(sel, 'selectedIndex', {
            configurable: true,
            get: function () { return di.get.call(sel); },
            set: function (v) { di.set.call(sel, v); refreshUI(sel); }
        });
    }

    /* ------------------------------------------------------------------ */
    /* Construcción del markup                                             */
    /* ------------------------------------------------------------------ */
    function enhance(sel) {
        if (!sel || sel.tagName !== 'SELECT' || sel.__fncSt) return;
        if (sel.multiple || sel.size > 1 || sel.hasAttribute('data-fnc-native')) return;
        if (sel.disabled) return;
        // Saltar sólo a los selects explícitamente ocultos (plantillas/data holders);
        // los contenedores condicionalmente ocultos (p. ej. bloque "préstamo")
        // se mejoran igual para verse bien cuando se muestren.
        if (sel.style.display === 'none' || sel.hasAttribute('hidden')) return;

        var compact = sel.classList.contains('fnc-input--cell');
        var wrap = document.createElement('div');
        wrap.className = 'fnc-combo' + (compact ? ' fnc-combo--compact' : '');
        sel.parentNode.insertBefore(wrap, sel);
        wrap.appendChild(sel);

        var input = document.createElement('input');
        input.type = 'text';
        input.className = 'fnc-input fnc-combo__input' + (compact ? ' fnc-input--cell' : '');
        input.autocomplete = 'off';
        input.setAttribute('role', 'combobox');
        input.setAttribute('aria-haspopup', 'listbox');
        input.setAttribute('aria-expanded', 'false');
        input.setAttribute('autocapitalize', 'off');
        var ph = (sel.options[0] && sel.options[0].value === '' && sel.options[0].textContent.trim())
            ? sel.options[0].textContent.trim()
            : 'Buscar…';
        input.placeholder = ph;
        wrap.appendChild(input);

        var chev = document.createElement('span');
        chev.className = 'fnc-combo__chev';
        chev.innerHTML = SVG_CHEV;
        wrap.appendChild(chev);

        sel.tabIndex = -1;
        sel.setAttribute('aria-hidden', 'true');

        var st = { sel: sel, wrap: wrap, input: input, chev: chev, panel: null, placeholder: ph, listId: 'fnc-cbo-' + (++uid) };
        sel.__fncSt = st;
        intercept(sel);

        input.addEventListener('focus', function () {
            if (st.suppress) return;
            openPanel(sel);
        });
        input.addEventListener('mousedown', function () {
            if (openState && openState.sel === sel) {
                // Toggle: clic en el campo ya abierto lo cierra (como un select nativo)
                st.suppress = true;
                setTimeout(function () { st.suppress = false; }, 250);
                closeAll();
            } else {
                // Reabrir aunque el campo ya conserve el foco (focus no se dispara de nuevo)
                openPanel(sel);
            }
        });
        input.addEventListener('input', function () {
            if (!openState || openState.sel !== sel) openPanel(sel);
            else { openState.filter = input.value; renderList(sel); }
        });
        input.addEventListener('keydown', function (e) { onKeydown(sel, e); });

        // El label asociado enfoca el campo visible, no el select oculto
        if (sel.id) {
            var lbl = document.querySelector('label[for="' + sel.id + '"]');
            if (lbl) lbl.addEventListener('click', function (e) { e.preventDefault(); input.focus(); });
        }

        // El botón chevron también abre/cierra
        chev.addEventListener('mousedown', function (e) { e.preventDefault(); });
        chev.addEventListener('mouseup', function (e) {
            e.stopPropagation();
            if (openState && openState.sel === sel) closeAll(); else input.focus();
        });

        // Los eventos 'change' externos (p. ej. Playwright selectOption) atualizan la UI
        sel.addEventListener('change', function () { refreshUI(sel); });

        refreshUI(sel);
    }

    /* ------------------------------------------------------------------ */
    /* Panel de resultados                                                 */
    /* ------------------------------------------------------------------ */
    function ensurePanel(sel) {
        var st = sel.__fncSt;
        if (st.panel) return st.panel;
        var panel = document.createElement('div');
        panel.className = 'fnc-combo__panel' + (st.sel.classList.contains('fnc-input--cell') ? ' fnc-combo__panel--compact' : '');
        panel.id = st.listId + '-panel';
        panel.setAttribute('role', 'listbox');
        var list = document.createElement('ul');
        list.className = 'fnc-combo__list';
        list.id = st.listId;
        panel.appendChild(list);
        document.body.appendChild(panel);
        st.panel = panel;
        st.list = list;
        return panel;
    }

    function closeAll() {
        if (!openState) return;
        var st = openState.sel.__fncSt;
        if (st && st.panel) {
            st.panel.style.display = 'none';
            st.input.setAttribute('aria-expanded', 'false');
        }
        st.wrap.classList.remove('is-open');
        openState = null;
        if (st) refreshUI(st.sel);
    }

    function openPanel(sel) {
        if (sel.disabled) return;
        var st = sel.__fncSt;
        if (openState && openState.sel !== sel) closeAll();
        ensurePanel(sel);
        openState = { sel: sel, filter: st.input.value === labelOf(sel) ? '' : st.input.value };
        st.wrap.classList.add('is-open');
        st.input.setAttribute('aria-expanded', 'true');
        st.input.setAttribute('aria-controls', st.listId);
        st.input.setAttribute('data-empty', labelOf(sel) ? '0' : '1');
        renderList(sel);
        positionPanel(sel);
    }

    /* Filtro + listado */
    function renderList(sel) {
        var st = sel.__fncSt;
        var filter = openState ? (openState.filter || '') : '';
        var q = norm(filter.trim());
        var list = st.list;

        var items = [];
        for (var i = 0; i < sel.options.length; i++) {
            var o = sel.options[i];
            if (o.disabled) continue;
            items.push({ o: o, tx: o.textContent.trim(), lb: norm(o.textContent) });
        }
        var matches = q ? items.filter(function (it) { return norm(it.o.value).indexOf(q) !== -1 || it.lb.indexOf(q) !== -1; }) : items;

        var recents = getRecents(sel);
        var html = '';
        var actIdx = 0;
        var current = sel.value;

        function optHtml(it, withClock) {
            var none = it.o.value === '' && !it.tx;
            var tx = it.tx || 'Sin selección';
            return '<li class="fnc-combo__opt' + (none ? ' is-none' : '') +
                (it.o.value === current ? ' is-selected' : '') +
                '" data-v="' + it.o.value.replace(/"/g, '&quot;') + '" id="' + st.listId + '-opt-' + actIdx + '" role="option">' +
                '<span class="fnc-combo__opt-ic">' + (withClock ? SVG_CLOCK : SVG_SEARCH) + '</span>' +
                '<span class="fnc-combo__opt-tx">' + escapeHtml(tx) + '</span></li>';
        }

        // Recientes del panel: valores recientes que existen en este select y coinciden con el filtro
        var recentItems = matches.filter(function (it) { return recents.indexOf(it.o.value) !== -1 && it.o.value !== ''; });
        var rest = matches.filter(function (it) { return recents.indexOf(it.o.value) === -1; });

        if (recentItems.length) {
            html += '<li class="fnc-combo__recents">Recientes</li>';
            recentItems.forEach(function (it) {
                html += optHtml(it, true); actIdx++;
            });
        }
        rest.forEach(function (it) { html += optHtml(it, false); actIdx++; });

        if (!matches.length) {
            html = '<li class="fnc-combo__empty">Sin resultados para «' + escapeHtml(filter) + '»</li>';
        }

        list.innerHTML = html;
        if (openState) openState.items = matches;
        setActive(sel, 0, false);

        // Grupos
        // (optgroup se maneja por orden natural; encabezado de grupo si el select los usa)
    }

    function escapeHtml(s) {
        return String(s).replace(/[&<>"]/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c];
        });
    }

    function setActive(sel, idx, scroll) {
        var st = sel.__fncSt;
        if (!st || !st.list) return;
        var opts = st.list.querySelectorAll('.fnc-combo__opt');
        if (!opts.length) return;
        idx = Math.max(0, Math.min(idx, opts.length - 1));
        if (openState) openState.activeIdx = idx;
        opts.forEach(function (el, i) { el.classList.toggle('is-active', i === idx); });
        var act = opts[idx];
        st.input.setAttribute('aria-activedescendant', act.id);
        if (scroll !== false) scrollInto(act);
    }

    function scrollInto(el) {
        var p = openState && openState.sel.__fncSt.panel;
        if (!p) return;
        var top = el.offsetTop;
        if (el.offsetTop < p.scrollTop + 8) p.scrollTop = el.offsetTop - 8;
        else if (el.offsetTop + el.offsetHeight > p.scrollTop + p.clientHeight - 8)
            p.scrollTop = el.offsetTop + el.offsetHeight - p.clientHeight + 8;
        void top;
    }

    function positionPanel(sel) {
        var st = sel.__fncSt;
        var p = st.panel;
        if (!p || !openState || openState.sel !== sel) return;
        p.style.display = 'block';
        p.style.maxHeight = '';
        var r = st.input.getBoundingClientRect();
        var w = Math.max(r.width, 220);
        var left = Math.min(r.left, window.innerWidth - w - 10);
        p.style.width = w + 'px';
        p.style.left = Math.max(6, left) + 'px';
        // Medir alto y decidir si abre hacia abajo o hacia arriba
        p.style.top = '0px';
        var h = p.offsetHeight;
        var below = window.innerHeight - r.bottom - 8;
        var above = r.top - 8;
        if (h <= below || below > above) {
            p.style.top = (r.bottom + 6) + 'px';
            p.classList.remove('is-up');
            if (h > below) p.style.maxHeight = below + 'px';
        } else {
            p.style.top = (r.top - Math.min(h, above) - 6) + 'px';
            p.classList.add('is-up');
            p.style.maxHeight = Math.min(h, above) + 'px';
        }
    }

    /* ------------------------------------------------------------------ */
    /* Comprometido: escribir en el select y notificar                     */
    /* ------------------------------------------------------------------ */
    function commit(sel, value) {
        var st = sel.__fncSt;
        var prev = sel.value;
        var proto = Object.getOwnPropertyDescriptor(HTMLSelectElement.prototype, 'value');
        proto.set.call(sel, value);   // sin pasar por el override (evita doble render)
        refreshUI(sel);
        pushRecent(sel, value);
        if (value !== prev) {
            sel.dispatchEvent(new Event('input', { bubbles: true }));
            sel.dispatchEvent(new Event('change', { bubbles: true }));
        }
        closeAll();
        // Evitar que el focus devuelva a abrir el panel de inmediato
        st.suppress = true;
        st.input.focus();
        setTimeout(function () { st.suppress = false; }, 250);
    }

    /* ------------------------------------------------------------------ */
    /* Teclado y cierres globales                                          */
    /* ------------------------------------------------------------------ */
    function onKeydown(sel, e) {
        if (!openState || openState.sel !== sel) {
            if (e.key === 'ArrowDown' || e.key === 'ArrowUp' || e.key === 'Enter') openPanel(sel);
            return;
        }
        var opts = sel.__fncSt.list.querySelectorAll('.fnc-combo__opt');
        var idx = openState.activeIdx || 0;
        if (e.key === 'ArrowDown') { e.preventDefault(); setActive(sel, idx + 1); }
        else if (e.key === 'ArrowUp') { e.preventDefault(); setActive(sel, idx - 1); }
        else if (e.key === 'Enter') {
            var el = opts[idx];
            if (el) { e.preventDefault(); commit(sel, el.getAttribute('data-v')); }
        } else if (e.key === 'Escape') {
            e.preventDefault();
            closeAll();
            sel.__fncSt.input.blur();
        } else if (e.key === 'Tab') {
            closeAll();
        }
    }

    document.addEventListener('mousedown', function (e) {
        if (!openState) return;
        var st = openState.sel.__fncSt;
        if (st.wrap.contains(e.target)) return;
        if (st.panel && st.panel.contains(e.target)) return;
        closeAll();
    }, true);

    window.addEventListener('scroll', function () {
        if (openState) {
            var r = openState.sel.__fncSt.input.getBoundingClientRect();
            if (r.top < -50 || r.top > window.innerHeight + 50) closeAll();
            else positionPanel(openState.sel);
        }
    }, true);

    window.addEventListener('resize', function () { if (openState) closeAll(); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeAll(); });

    // Click en una opción
    document.addEventListener('mousedown', function (e) {
        var li = e.target.closest && e.target.closest('.fnc-combo__opt');
        if (!li || !openState) return;
        e.preventDefault(); // evita que el mousedown quite el foco
        var sel = openState.sel;
        commit(sel, li.getAttribute('data-v'));
    });

    /* ------------------------------------------------------------------ */
    /* Auto-mejora: selects presentes y añadidos después (ficha, AJAX)     */
    /* ------------------------------------------------------------------ */
    function initAll(root) {
        (root || document).querySelectorAll('select').forEach(enhance);
    }

    var mo = new MutationObserver(function (muts) {
        muts.forEach(function (m) {
            m.addedNodes.forEach(function (n) {
                if (n.nodeType !== 1) return;
                if (n.tagName === 'SELECT') enhance(n);
                else if (n.querySelectorAll) initAll(n);
            });
        });
    });

    function boot() {
        initAll();
        mo.observe(document.body, { childList: true, subtree: true });
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot);
    else boot();

    /* API pública */
    window.FNCCombobox = {
        enhance: enhance,
        sync: function (sel) { if (sel && sel.__fncSt) refreshUI(sel); },
        close: closeAll
    };
})();
