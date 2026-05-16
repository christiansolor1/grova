/**
 * Defaults globales DataTables 2 + Buttons (Grova).
 * Cargar después de dataTables.buttons, buttons.bootstrap5 y grova-datatables-dual-scroll.
 *
 * Botones (GrovaDataTable.init):
 *   — Sin `buttons` → ninguno (opt-in)
 *   — `buttons: 'full'` → pack completo (copy, csv, excel, pdf, print, colvis)
 *   — `buttons: ['excel', 'pdf']` → manual, solo los indicados
 *   — `buttons: 'export'` → preset nombrado (ver buttonsPresets)
 *   — `buttons: false` → sin barra de exportación (igual que omitir)
 */
(function (global) {
    'use strict';

    if (typeof DataTable === 'undefined') {
        return;
    }

    var GROVA_DT_BUTTONS_FULL = ['copy', 'csv', 'excel', 'pdf', 'print', 'colvis'];
    var GROVA_DT_BUTTONS_PRESETS = {
        export: ['copy', 'csv', 'excel', 'pdf'],
        print: ['print'],
        minimal: ['excel', 'colvis'],
    };
    var GROVA_DT_PAGE_LENGTH_MENU = [5, 10, 25, 50, 100];
    var dualScrollApi = global.GrovaDataTableDualScroll;

    function resolveAllRowsLabel() {
        var lang = (typeof document !== 'undefined' && document.documentElement)
            ? (document.documentElement.lang || 'es')
            : 'es';
        return lang.indexOf('en') === 0 ? 'All' : 'Todos';
    }

    function allRowsMenuEntry() {
        return { label: resolveAllRowsLabel(), value: -1 };
    }

    function menuHasAllRows(menu) {
        if (!Array.isArray(menu)) {
            return false;
        }
        return menu.some(function (item) {
            if (item === -1) {
                return true;
            }
            return item && typeof item === 'object' && Number(item.value) === -1;
        });
    }

    function normalizePageLengthMenu(menu) {
        var base = Array.isArray(menu) ? menu.slice() : GROVA_DT_PAGE_LENGTH_MENU.slice();
        if (!menuHasAllRows(base)) {
            base.push(-1);
        }
        return base.map(function (item) {
            if (item === -1) {
                return allRowsMenuEntry();
            }
            if (item && typeof item === 'object' && Number(item.value) === -1) {
                return item.label ? item : allRowsMenuEntry();
            }
            return item;
        });
    }

    function defaultPageLengthSlot() {
        return { pageLength: { menu: normalizePageLengthMenu(GROVA_DT_PAGE_LENGTH_MENU) } };
    }

    function normalizePageLengthSlot(slot) {
        if (slot === 'pageLength') {
            return defaultPageLengthSlot();
        }
        if (slot && typeof slot === 'object' && !Array.isArray(slot) && slot.pageLength !== undefined) {
            var pl = slot.pageLength;
            if (pl === 'pageLength' || pl === null || pl === undefined) {
                return defaultPageLengthSlot();
            }
            if (typeof pl === 'object') {
                var nextPl = Object.assign({}, pl);
                nextPl.menu = normalizePageLengthMenu(pl.menu);
                return Object.assign({}, slot, { pageLength: nextPl });
            }
        }
        if (Array.isArray(slot)) {
            return slot.map(function (item) {
                if (item === 'pageLength') {
                    return defaultPageLengthSlot();
                }
                if (item && typeof item === 'object' && item.pageLength !== undefined) {
                    return normalizePageLengthSlot(item);
                }
                return item;
            });
        }
        return slot;
    }

    function normalizePageLengthInLayout(layout) {
        if (!layout || typeof layout !== 'object') {
            return layout;
        }
        var out = Object.assign({}, layout);
        if (out.topStart !== undefined) {
            out.topStart = normalizePageLengthSlot(out.topStart);
        }
        if (out.bottomStart !== undefined) {
            out.bottomStart = normalizePageLengthSlot(out.bottomStart);
        }
        return out;
    }

    function findPageLengthMenuInSlot(slot) {
        if (!slot) {
            return null;
        }
        if (slot.pageLength && typeof slot.pageLength === 'object' && Array.isArray(slot.pageLength.menu)) {
            return slot.pageLength.menu;
        }
        if (Array.isArray(slot)) {
            for (var i = 0; i < slot.length; i++) {
                var found = findPageLengthMenuInSlot(slot[i]);
                if (found) {
                    return found;
                }
            }
        }
        return null;
    }

    function extractPageLengthMenuFromLayout(layout) {
        if (!layout || typeof layout !== 'object') {
            return null;
        }
        return findPageLengthMenuInSlot(layout.topStart)
            || findPageLengthMenuInSlot(layout.bottomStart);
    }

    /**
     * DT 2.1.8 usa aLengthMenu (no lengthMenu). Si el layout define pageLength.menu,
     * alinear aLengthMenu para que no quede el default [10,25,50,100] mezclado en runtime.
     */
    function syncLengthMenuOption(options) {
        var fromLayout = extractPageLengthMenuFromLayout(options.layout);
        if (fromLayout) {
            options.aLengthMenu = fromLayout.slice();
            return;
        }
        var raw = options.aLengthMenu || options.lengthMenu;
        if (raw) {
            options.aLengthMenu = normalizePageLengthMenu(raw);
            delete options.lengthMenu;
        }
    }

    var grovaDefaultPageLengthMenu = normalizePageLengthMenu(GROVA_DT_PAGE_LENGTH_MENU);

    DataTable.defaults.pageLength = 10;
    // Menú global vía aLengthMenu; en layout solo el feature «pageLength» (string).
    // No poner pageLength.menu aquí: $.extend(true) fusiona arrays por índice con el layout del usuario.
    DataTable.defaults.aLengthMenu = grovaDefaultPageLengthMenu;
    DataTable.defaults.stripeClasses = ['odd', 'even'];
    DataTable.defaults.layout = {
        topStart: 'pageLength',
        topEnd: 'search',
        bottomStart: 'info',
        bottomEnd: 'paging',
    };

    function isPageLengthButton(entry) {
        return entry === 'pageLength' || (entry && typeof entry === 'object' && entry.extend === 'pageLength');
    }

    function normalizeButtonEntry(entry) {
        if (isPageLengthButton(entry)) {
            return null;
        }
        return entry;
    }

    /**
     * @param {*} buttonsOption undefined | true | 'full' | preset | array | false | 'none'
     * @returns {{ enabled: boolean, list: Array }}
     */
    function resolveButtons(buttonsOption) {
        if (buttonsOption === false || buttonsOption === null || buttonsOption === 'none' || buttonsOption === 'off') {
            return { enabled: false, list: [] };
        }

        if (buttonsOption === undefined) {
            return { enabled: false, list: [] };
        }

        if (buttonsOption === true
            || buttonsOption === 'full'
            || buttonsOption === 'all'
            || buttonsOption === 'default') {
            return { enabled: true, list: GROVA_DT_BUTTONS_FULL.slice() };
        }

        if (typeof buttonsOption === 'string' && Object.prototype.hasOwnProperty.call(GROVA_DT_BUTTONS_PRESETS, buttonsOption)) {
            return { enabled: true, list: GROVA_DT_BUTTONS_PRESETS[buttonsOption].slice() };
        }

        if (Array.isArray(buttonsOption)) {
            var list = buttonsOption.map(normalizeButtonEntry).filter(Boolean);
            return { enabled: list.length > 0, list: list };
        }

        if (typeof buttonsOption === 'string') {
            return { enabled: true, list: [buttonsOption] };
        }

        return { enabled: false, list: [] };
    }

    function topStartHasButtons(topStart) {
        if (topStart === 'buttons') {
            return true;
        }
        if (Array.isArray(topStart)) {
            return topStart.some(function (item) {
                return item === 'buttons' || (item && typeof item === 'object' && item.buttons);
            });
        }
        if (topStart && typeof topStart === 'object') {
            return !!topStart.buttons;
        }
        return false;
    }

    function stripButtonsFromTopStart(topStart) {
        if (topStart === 'buttons') {
            return null;
        }
        if (Array.isArray(topStart)) {
            var next = topStart.filter(function (item) {
                return item !== 'buttons' && !(item && typeof item === 'object' && item.buttons);
            });
            return next.length ? next : null;
        }
        if (topStart && typeof topStart === 'object' && topStart.buttons) {
            return null;
        }
        return topStart;
    }

    function injectButtonsIntoTopStart(topStart) {
        if (topStartHasButtons(topStart)) {
            return topStart;
        }
        if (topStart === undefined || topStart === null) {
            return ['buttons', 'pageLength'];
        }
        if (typeof topStart === 'string') {
            return ['buttons', topStart];
        }
        if (Array.isArray(topStart)) {
            return ['buttons'].concat(topStart);
        }
        if (typeof topStart === 'object') {
            return ['buttons', topStart];
        }
        return ['buttons', 'pageLength'];
    }

    function layoutWithoutButtons() {
        return normalizePageLengthInLayout({
            topStart: 'pageLength',
            topEnd: 'search',
            bottomStart: 'info',
            bottomEnd: 'paging',
        });
    }

    function mergeLayout(userLayout, withButtons) {
        withButtons = withButtons !== false;

        if (!userLayout || typeof userLayout !== 'object') {
            return normalizePageLengthInLayout(withButtons
                ? Object.assign({}, DataTable.defaults.layout)
                : layoutWithoutButtons());
        }

        var layout = Object.assign({}, userLayout);
        var ts = layout.topStart;

        if (withButtons) {
            layout.topStart = injectButtonsIntoTopStart(ts);
        } else {
            layout.topStart = stripButtonsFromTopStart(ts);
            if (layout.topStart === null || layout.topStart === undefined) {
                layout.topStart = 'pageLength';
            }
        }

        if (layout.topEnd === undefined) {
            layout.topEnd = 'search';
        }
        if (layout.bottomStart === undefined) {
            layout.bottomStart = 'info';
        }
        if (layout.bottomEnd === undefined) {
            layout.bottomEnd = 'paging';
        }

        return normalizePageLengthInLayout(layout);
    }

    function fixDtButtonDropdowns(tableEl) {
        if (!tableEl || typeof bootstrap === 'undefined' || !bootstrap.Dropdown) {
            return;
        }
        var root = tableEl.closest('.dt-container') || tableEl.closest('.dataTables_wrapper');
        if (!root) {
            return;
        }
        root.querySelectorAll('.dt-buttons [data-bs-toggle="dropdown"]').forEach(function (toggle) {
            if (toggle.dataset.grovaDtDropdownFixed === '1') {
                return;
            }
            toggle.dataset.grovaDtDropdownFixed = '1';
            var existing = bootstrap.Dropdown.getInstance(toggle);
            if (existing) {
                existing.dispose();
            }
            new bootstrap.Dropdown(toggle, {
                popperConfig: {
                    strategy: 'fixed',
                    modifiers: [
                        { name: 'preventOverflow', options: { boundary: document.body } },
                        { name: 'offset', options: { offset: [0, 4] } },
                    ],
                },
            });
        });
    }

    function wrapCallbacks(el, options, withButtons) {
        var dualScroll = options.dualScroll !== false;
        var userInitComplete = options.initComplete;
        var userDrawCallback = options.drawCallback;

        options.initComplete = function (settings, json) {
            if (typeof userInitComplete === 'function') {
                userInitComplete.call(this, settings, json);
            }
            if (withButtons) {
                fixDtButtonDropdowns(el);
            }
            if (dualScroll && dualScrollApi && typeof dualScrollApi.attach === 'function') {
                dualScrollApi.attach(el);
            }
        };

        options.drawCallback = function () {
            if (typeof userDrawCallback === 'function') {
                userDrawCallback.apply(this, arguments);
            }
            if (dualScroll && dualScrollApi && typeof dualScrollApi.relayout === 'function') {
                dualScrollApi.relayout(el);
            }
        };
    }

    function init(el, options) {
        options = options || {};

        var resolved = resolveButtons(options.buttons);

        if (resolved.enabled) {
            options.buttons = resolved.list;
        } else {
            delete options.buttons;
        }

        if (options.layout) {
            options.layout = mergeLayout(options.layout, resolved.enabled);
        } else if (!options.dom) {
            options.layout = normalizePageLengthInLayout(resolved.enabled
                ? Object.assign({}, DataTable.defaults.layout)
                : layoutWithoutButtons());
        }

        syncLengthMenuOption(options);

        wrapCallbacks(el, options, resolved.enabled);
        return new DataTable(el, options);
    }

    global.GrovaDataTable = {
        buttons: GROVA_DT_BUTTONS_FULL,
        buttonsFull: GROVA_DT_BUTTONS_FULL,
        buttonsPresets: GROVA_DT_BUTTONS_PRESETS,
        pageLengthMenu: grovaDefaultPageLengthMenu,
        pageLengthMenuBase: GROVA_DT_PAGE_LENGTH_MENU,
        normalizePageLengthMenu: normalizePageLengthMenu,
        resolveButtons: resolveButtons,
        mergeLayout: mergeLayout,
        init: init,
    };
})(typeof window !== 'undefined' ? window : this);
