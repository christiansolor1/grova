/**
 * grova — Dual Listbox auto-init
 *
 * Cualquier .grova-dual-listbox se inicializa automáticamente.
 * El buscador filtra en tiempo real sin depender de display:none en <option>
 * (que no funciona en Safari/macOS).
 */
(function () {
    'use strict';

    function sortOptions(list) {
        list.sort(function (a, b) { return a.text.localeCompare(b.text); });
    }

    // Cada lista se maneja como un array de objetos {value, text}
    // y se renderiza al select según el filtro activo

    function renderSelect(select, items, filter) {
        var q = (filter || '').toLowerCase().trim();
        var prev = Array.from(select.selectedOptions).map(function (o) { return o.value; });
        select.innerHTML = '';
        items.forEach(function (item) {
            if (q && item.text.toLowerCase().indexOf(q) === -1) return;
            var o = document.createElement('option');
            o.value = item.value;
            o.text  = item.text;
            if (prev.indexOf(item.value) !== -1) o.selected = true;
            select.appendChild(o);
        });
    }

    function initDualListbox(container) {
        var selects = container.querySelectorAll('select.grova-dlb-list');
        if (selects.length < 2) return;

        var leftSelect  = selects[0];
        var rightSelect = selects[1];

        // Estado interno — arrays de {value, text}
        var leftItems  = Array.from(leftSelect.options).map(function (o) { return { value: o.value, text: o.text }; });
        var rightItems = Array.from(rightSelect.options).map(function (o) { return { value: o.value, text: o.text }; });

        var leftFilter  = '';
        var rightFilter = '';

        function redraw() {
            renderSelect(leftSelect,  leftItems,  leftFilter);
            renderSelect(rightSelect, rightItems, rightFilter);
        }

        function moveSelected(fromItems, fromSelect, toItems) {
            var selected = Array.from(fromSelect.selectedOptions).map(function (o) { return o.value; });
            if (!selected.length) return;
            var moved = [];
            var remaining = [];
            fromItems.forEach(function (item) {
                if (selected.indexOf(item.value) !== -1) { moved.push(item); }
                else { remaining.push(item); }
            });
            moved.forEach(function (item) { toItems.push(item); });
            sortOptions(toItems);
            fromItems.length = 0;
            remaining.forEach(function (item) { fromItems.push(item); });
            redraw();
        }

        function moveAll(fromItems, toItems) {
            fromItems.forEach(function (item) { toItems.push(item); });
            sortOptions(toItems);
            fromItems.length = 0;
            redraw();
        }

        // Buscadores
        container.querySelectorAll('input.grova-dlb-search').forEach(function (input) {
            var side = input.getAttribute('data-dlb-target');
            input.addEventListener('input', function () {
                if (side === 'right') { rightFilter = input.value; }
                else                  { leftFilter  = input.value; }
                redraw();
            });
        });

        // Botones
        container.querySelectorAll('button.grova-dlb-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                switch (btn.getAttribute('data-dlb-action')) {
                    case 'move-right':     moveSelected(leftItems,  leftSelect,  rightItems); break;
                    case 'move-all-right': moveAll(leftItems,  rightItems); break;
                    case 'move-left':      moveSelected(rightItems, rightSelect, leftItems);  break;
                    case 'move-all-left':  moveAll(rightItems, leftItems);  break;
                }
            });
        });

        // Doble clic
        leftSelect.addEventListener('dblclick',  function () { moveSelected(leftItems,  leftSelect,  rightItems); });
        rightSelect.addEventListener('dblclick', function () { moveSelected(rightItems, rightSelect, leftItems);  });

        // API pública para obtener los valores del lado derecho al guardar
        container._getSelected = function () {
            return rightItems.map(function (i) { return i.value; });
        };

        redraw();
    }

    function initAll(context) {
        (context || document).querySelectorAll('.grova-dual-listbox').forEach(initDualListbox);
    }

    document.addEventListener('DOMContentLoaded', function () { initAll(); });

    window.GrovaDualListbox = { init: initDualListbox, initAll: initAll };
}());
