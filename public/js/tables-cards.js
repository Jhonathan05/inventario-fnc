/* ===========================================================================
   Tablas de datos como tarjetas en móvil
   ---------------------------------------------------------------------------
   Marca cada tabla de datos con .fnc-table-cards + data-cards-ready y copia en
   cada celda la etiqueta de su columna (data-label) leída del <thead>. El CSS
   (resources/css/global/responsive.css, sección 8) hace el resto en pantallas
   de menos de 768 px. En escritorio no cambia nada.

   Se omiten:
     - tablas sin <thead> o con cabeceras combinadas (colspan/rowspan),
     - tablas de PDF/correo (role="presentation"),
     - tablas de captura (.fnc-items) y de clave/valor (.table-borderless),
     - cualquier tabla con data-cards="off" (ella o un ancestro).

   Las tablas que se rellenan por JS (modales, filtros) se re-etiquetan solas
   gracias a un MutationObserver sobre la tabla.
   =========================================================================== */
(function () {
    'use strict';

    var CARD_CLASS = 'fnc-table-cards';
    var READY_ATTR = 'data-cards-ready';
    var ACTIONS_RE = /^(acci[oó]n|acciones|acciones en lote|operaciones|opciones)$/i;
    var observers = new WeakMap();

    function normalize(text) {
        return (text || '').replace(/\s+/g, ' ').trim();
    }

    /* Etiquetas de la última fila del thead, o null si la tabla no es apta. */
    function headerLabels(table) {
        var thead = table.tHead;
        if (!thead || thead.rows.length === 0) {
            return null;
        }

        var row = thead.rows[thead.rows.length - 1];
        var cells = row ? Array.prototype.slice.call(row.cells) : [];

        if (cells.length < 2) {
            return null;
        }

        var labels = [];

        for (var i = 0; i < cells.length; i++) {
            var cell = cells[i];

            if (cell.tagName !== 'TH' || cell.colSpan > 1 || cell.rowSpan > 1) {
                return null;
            }

            labels.push(normalize(cell.textContent));
        }

        return labels;
    }

    function isOptedOut(table) {
        if (!table || table.tagName !== 'TABLE') {
            return true;
        }

        if (table.getAttribute('data-cards') === 'off') {
            return true;
        }

        if (table.closest('[data-cards="off"]')) {
            return true;
        }

        if (table.getAttribute('role') === 'presentation') {
            return true;
        }

        if (table.classList.contains('fnc-items') ||
            table.classList.contains('table-borderless') ||
            table.classList.contains('no-table-cards')) {
            return true;
        }

        return false;
    }

    function labelRow(row, labels) {
        if (!row || row.cells.length === 0) {
            return;
        }

        for (var i = 0; i < row.cells.length; i++) {
            var cell = row.cells[i];

            if (cell.tagName !== 'TD') {
                continue;
            }

            var label = cell.colSpan > 1 ? '' : (labels[i] || '');

            if (label) {
                if (cell.getAttribute('data-label') !== label) {
                    cell.setAttribute('data-label', label);
                }
            } else if (cell.hasAttribute('data-label')) {
                cell.removeAttribute('data-label');
            }

            cell.classList.toggle('fnc-cards__actions', ACTIONS_RE.test(label));
        }
    }

    function labelRows(table, labels) {
        var bodies = table.tBodies;

        for (var b = 0; b < bodies.length; b++) {
            var rows = bodies[b].rows;

            for (var r = 0; r < rows.length; r++) {
                labelRow(rows[r], labels);
            }
        }
    }

    /* Las tablas dinámicas (modales, filtros) añaden filas después de cargar.
       Se observa la tabla completa para cubrir también un tbody reemplazado. */
    function watch(table, labels) {
        if (observers.has(table) || typeof MutationObserver === 'undefined') {
            return;
        }

        var observer = new MutationObserver(function () {
            labelRows(table, labels);
        });

        observer.observe(table, { childList: true, subtree: true });
        observers.set(table, observer);
    }

    function enhance(table) {
        if (isOptedOut(table)) {
            return false;
        }

        if (table.hasAttribute(READY_ATTR)) {
            var current = headerLabels(table);

            if (current) {
                labelRows(table, current);
            }

            return true;
        }

        var labels = headerLabels(table);

        if (!labels) {
            return false;
        }

        table.classList.add(CARD_CLASS);
        table.setAttribute(READY_ATTR, '');
        labelRows(table, labels);

        var wrap = table.closest('.table-responsive');

        if (wrap) {
            wrap.classList.add('fnc-cards-wrap');
        }

        watch(table, labels);

        return true;
    }

    function enhanceAll(root) {
        var scope = root || document;
        var tables = scope.querySelectorAll('table');
        var count = 0;

        for (var i = 0; i < tables.length; i++) {
            if (enhance(tables[i])) {
                count++;
            }
        }

        return count;
    }

    window.fncTableCards = {
        enhance: enhance,
        enhanceAll: enhanceAll
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            enhanceAll();
        });
    } else {
        enhanceAll();
    }
})();
