/**
 * Panel lateral de filtros (misma lógica que templates/dashboard/index.html.twig),
 * reutilizable en el shell del workspace.
 */
(function () {
    'use strict';

    var filterPanelInitialized = false;
    var activeFilterMode = 'compact';

    function buildFilterFields(count, prefix) {
        prefix = prefix || 'f';
        return Array.from({ length: count }).map(function (_, idx) {
            return {
                id: prefix + '-' + (idx + 1),
                label: 'Filtro ' + (idx + 1),
                type: idx % 4 === 0 ? 'select' : 'text',
            };
        });
    }

    var FILTER_SCHEMAS = {
        default: {
            mode: 'compact',
            sections: [
                {
                    title: 'Usuarios',
                    open: true,
                    fields: [
                        { id: 'filter-user', label: 'Usuario', type: 'text', placeholder: 'Nombre o email' },
                        { id: 'filter-status', label: 'Estado', type: 'select', options: ['Todos', 'Activo', 'Inactivo', 'Pendiente'] },
                    ],
                },
                {
                    title: 'Fechas',
                    fields: [
                        { id: 'filter-from', label: 'Desde', type: 'date' },
                        { id: 'filter-to', label: 'Hasta', type: 'date' },
                    ],
                },
                {
                    title: 'Organizacion',
                    fields: [
                        { id: 'filter-org', label: 'Empresa', type: 'select', options: ['Todas', 'Empresa A', 'Empresa B', 'Empresa C'] },
                        { id: 'filter-plan', label: 'Plan', type: 'select', options: ['Todos', 'Free', 'Pro', 'Enterprise'] },
                    ],
                },
            ],
        },
        'filter-scale-lt8': {
            mode: 'label',
            sections: [{ title: '<= 8 filtros', open: true, fields: buildFilterFields(8, 'lt8') }],
        },
        'filter-scale-9-20': {
            mode: 'chips',
            sections: [
                { title: 'Bloque A (1-6)', open: true, fields: buildFilterFields(6, 'm1') },
                { title: 'Bloque B (7-12)', fields: buildFilterFields(6, 'm2') },
            ],
        },
        'filter-scale-20plus': {
            mode: 'compact',
            sections: [
                { title: 'Bloque A (1-8)', open: true, fields: buildFilterFields(8, 'x1') },
                { title: 'Bloque B (9-16)', fields: buildFilterFields(8, 'x2') },
                { title: 'Bloque C (17-22)', fields: buildFilterFields(6, 'x3') },
            ],
        },
        'ui-tables-wide': {
            mode: 'compact',
            panelClass: 'filters-panel-wide',
            sections: [
                {
                    title: 'Datos base',
                    open: true,
                    className: 'filter-grid-two',
                    fields: [
                        { id: 'wide-user', label: 'Usuario', type: 'text', placeholder: 'Nombre de usuario' },
                        { id: 'wide-email', label: 'Email', type: 'text', placeholder: 'correo@empresa.com' },
                        { id: 'wide-status', label: 'Estado', type: 'select', options: ['Todos', 'Activo', 'Inactivo', 'Pendiente'] },
                        { id: 'wide-role', label: 'Rol', type: 'select', options: ['Todos', 'Admin', 'Editor', 'Viewer'] },
                    ],
                },
                {
                    title: 'Fechas',
                    open: true,
                    className: 'filter-grid-two',
                    fields: [
                        { id: 'wide-from', label: 'Desde', type: 'date' },
                        { id: 'wide-to', label: 'Hasta', type: 'date' },
                    ],
                },
                {
                    title: 'Seleccion multiple (demo)',
                    open: true,
                    fields: [
                        {
                            id: 'wide-regions-multi',
                            label: 'Regiones (select nativo multiple)',
                            type: 'select-multiple',
                            options: ['Norte', 'Centro', 'Sur', 'Occidente', 'Oriente'],
                        },
                        {
                            id: 'wide-modules-multi',
                            label: 'Modulos (Select2 multiple)',
                            type: 'select2-multiple',
                            placeholder: 'Elegir modulos...',
                            options: ['Facturacion', 'Inventario', 'RRHH', 'Legal', 'Construccion', 'Wallet', 'Pesca'],
                        },
                        {
                            id: 'wide-tags-multi',
                            label: 'Etiquetas (select nativo multiple)',
                            type: 'select-multiple',
                            options: ['Urgente', 'VIP', 'Facturacion', 'Soporte', 'Interno', 'Cliente'],
                        },
                    ],
                },
                {
                    title: 'Controles avanzados (demo)',
                    open: true,
                    className: 'filter-grid-two',
                    fields: [
                        { id: 'wide-module-select2', label: 'Modulo (Select2)', type: 'select2', options: ['Todos', 'Facturacion', 'Inventario', 'RRHH', 'Legal', 'Construccion'] },
                        { id: 'wide-priority', label: 'Prioridad', type: 'range', min: 0, max: 100, step: 5, value: 50 },
                        { id: 'wide-ticket', label: 'Ticket', type: 'text', placeholder: 'TK-00125' },
                        { id: 'wide-amount', label: 'Monto', type: 'number', placeholder: '1500', min: 0, step: 1 },
                        { id: 'wide-color', label: 'Color etiqueta', type: 'color', value: '#2563eb' },
                        { id: 'wide-active-only', label: 'Solo activos', type: 'checkbox', checked: true },
                        { id: 'wide-include-archived', label: 'Incluir archivados', type: 'checkbox', checked: false },
                        { id: 'wide-channel', label: 'Canal', type: 'radio', options: ['Todos', 'Web', 'App', 'API'], value: 'Todos' },
                        { id: 'wide-notes', label: 'Notas', type: 'textarea', placeholder: 'Comentarios de filtro rapido...' },
                    ],
                },
                {
                    title: 'Auditoria',
                    open: true,
                    className: 'filter-grid-two',
                    fields: [
                        { id: 'wide-created-from', label: 'Creado desde', type: 'date' },
                        { id: 'wide-created-to', label: 'Creado hasta', type: 'date' },
                        { id: 'wide-updated-from', label: 'Actualizado desde', type: 'date' },
                        { id: 'wide-updated-to', label: 'Actualizado hasta', type: 'date' },
                        { id: 'wide-created-by', label: 'Creado por', type: 'text', placeholder: 'Usuario creador' },
                        { id: 'wide-updated-by', label: 'Actualizado por', type: 'text', placeholder: 'Usuario editor' },
                    ],
                },
            ],
        },
        'users-menu-governance': {
            mode: 'compact',
            panelClass: 'filters-panel-wide',
            sections: [
                {
                    title: 'Busqueda y jerarquia',
                    open: true,
                    className: 'filter-grid-two',
                    fields: [
                        { id: 'menu-search', label: 'Buscar', type: 'text', placeholder: 'Path, label o tipo' },
                        { id: 'menu-level', label: 'Nivel', type: 'select', options: ['Todos', 'L1', 'L2', 'L3'] },
                        { id: 'menu-type', label: 'Tipo', type: 'select', options: ['Todos', 'Menu principal', 'Submenu', 'Subopcion'] },
                        { id: 'menu-sidebar', label: 'Sidebar', type: 'select', options: ['Todos', 'Visible', 'Oculto'] },
                    ],
                },
                {
                    title: 'Estado',
                    className: 'filter-grid-two',
                    fields: [
                        { id: 'menu-status', label: 'Estado', type: 'select', options: ['Todos', 'Hecho', 'Pendiente'] },
                    ],
                },
            ],
        },
    };

    function resolveActiveDataTable() {
        var el = document.getElementById('users-table');
        if (!el || typeof DataTable === 'undefined') {
            return null;
        }
        if (typeof DataTable.get === 'function') {
            try {
                return DataTable.get(el);
            } catch (e) {
                return null;
            }
        }
        return null;
    }

    function getCurrentFilterSummary() {
        var parts = [];
        document.querySelectorAll('[data-filter-input="1"]').forEach(function (el) {
            if (!('value' in el)) return;
            var type = String(el.type || '').toLowerCase();
            if ((type === 'checkbox' || type === 'radio') && !el.checked) return;
            var raw = (el.value || '').trim();
            if (!raw) return;
            var label = el.dataset.label || el.id || 'Filtro';
            if (el.tagName === 'SELECT') {
                if (el.multiple) {
                    var multiLabels = Array.from(el.selectedOptions || [])
                        .map(function (opt) { return (opt.text || '').trim(); })
                        .filter(Boolean);
                    if (!multiLabels.length) return;
                    parts.push(label + ': ' + multiLabels.join(', '));
                    return;
                }
                var selectedText = el.options && el.selectedIndex >= 0 ? el.options[el.selectedIndex].text : raw;
                var normalized = (selectedText || '').trim().toLowerCase();
                if (['todos', 'todas', '-seleccionar-', 'all', 'all plans', 'any'].indexOf(normalized) !== -1) return;
                parts.push(label + ': ' + selectedText);
            } else {
                parts.push(label + ': ' + raw);
            }
        });
        return parts;
    }

    function updateActiveFiltersBanner() {
        var row = document.getElementById('active-filters-row');
        var text = document.getElementById('active-filters-text');
        if (!row || !text) return;

        var parts = getCurrentFilterSummary();
        if (!parts.length) {
            row.classList.remove('show');
            text.textContent = '';
            return;
        }

        if (activeFilterMode === 'chips' && parts.length > 4) {
            var preview = parts.slice(0, 4).join(' ; ');
            text.textContent = ' ' + preview + ' ; +' + (parts.length - 4) + ' más';
        } else if (activeFilterMode === 'compact' && parts.length > 3) {
            text.textContent = ' ' + parts.slice(0, 3).join(' ; ') + ' ; +' + (parts.length - 3) + ' más';
        } else {
            text.textContent = ' ' + parts.join(' ; ');
        }
        row.classList.add('show');
    }

    function bindFilterSectionToggles(root) {
        var scope = root || document;
        scope.querySelectorAll('.filter-section-btn').forEach(function (btn) {
            if (btn.dataset.grovaSectionBound === '1') return;
            btn.dataset.grovaSectionBound = '1';
            btn.addEventListener('click', function () {
                var section = btn.closest('.filter-section');
                if (section) section.classList.toggle('open');
            });
        });
    }

    function filtersBodyIsCustom() {
        var body = document.getElementById('filters-body');
        return !!(body && body.getAttribute('data-grova-filters-custom') === '1');
    }

    function renderFilterPanelForView(viewId) {
        var body = document.getElementById('filters-body');
        if (!body) return;
        if (filtersBodyIsCustom()) return;

        if (window.GrovaSelect2 && typeof window.GrovaSelect2.destroyAll === 'function') {
            window.GrovaSelect2.destroyAll(body);
        }

        var schema = FILTER_SCHEMAS[viewId] || FILTER_SCHEMAS.default;
        activeFilterMode = schema.mode || 'compact';
        document.body.classList.remove('filters-panel-wide');
        if (schema.panelClass) {
            document.body.classList.add(schema.panelClass);
        }

        body.innerHTML = schema.sections
            .map(function (section) {
                var sectionClass = section.className ? ' ' + section.className : '';
                return (
                    '<div class="filter-section' +
                    (section.open ? ' open' : '') +
                    '">' +
                    '<button class="filter-section-btn" type="button">' +
                    section.title +
                    '<span class="chev"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg></span>' +
                    '</button>' +
                    '<div class="filter-section-content' + sectionClass + '">' +
                    section.fields
                        .map(function (field) {
                            if (
                                field.type === 'select'
                                || field.type === 'select2'
                                || field.type === 'select-multiple'
                                || field.type === 'select2-multiple'
                            ) {
                                var isMultiple = field.type === 'select-multiple' || field.type === 'select2-multiple';
                                var useSelect2 = field.type === 'select2' || field.type === 'select2-multiple';
                                var options = (field.options || ['Todos', 'Opcion 1', 'Opcion 2'])
                                    .map(function (opt) {
                                        return '<option value="' + String(opt) + '">' + String(opt) + '</option>';
                                    })
                                    .join('');
                                var selectClass = (useSelect2 ? 'grova-select2 ' : '')
                                    + 'form-select form-select-sm'
                                    + (isMultiple && !useSelect2 ? ' grova-filter-select-native-multi' : '');
                                var selectAttrs = 'id="' + field.id + '" data-filter-input="1" data-label="' + field.label + '" class="' + selectClass + '"';
                                if (isMultiple) {
                                    selectAttrs += ' multiple';
                                }
                                if (useSelect2 && field.placeholder) {
                                    selectAttrs += ' data-placeholder="' + String(field.placeholder) + '"';
                                }
                                if (useSelect2 && isMultiple) {
                                    selectAttrs += ' data-allow-clear="true"';
                                }
                                return (
                                    '<div class="filter-field' + (isMultiple ? ' filter-field--multi' : '') + '">' +
                                    '<label class="form-label">' + field.label + '</label>' +
                                    '<select ' + selectAttrs + '>' + options + '</select>' +
                                    '</div>'
                                );
                            }
                            if (field.type === 'textarea') {
                                return (
                                    '<div class="filter-field"><label class="form-label">' +
                                    field.label +
                                    '</label><textarea id="' +
                                    field.id +
                                    '" data-filter-input="1" data-label="' +
                                    field.label +
                                    '" class="form-control form-control-sm" rows="2" placeholder="' +
                                    (field.placeholder || '') +
                                    '"></textarea></div>'
                                );
                            }
                            if (field.type === 'checkbox') {
                                return (
                                    '<div class="filter-field"><div class="form-check mt-1">' +
                                    '<input id="' + field.id + '" data-filter-input="1" data-label="' + field.label + '" class="form-check-input" type="checkbox" value="Si"' + (field.checked ? ' checked' : '') + '>' +
                                    '<label class="form-check-label" for="' + field.id + '">' + field.label + '</label>' +
                                    '</div></div>'
                                );
                            }
                            if (field.type === 'radio') {
                                var radioName = field.id + '-group';
                                var radioOptions = (field.options || ['Todos', 'Si', 'No'])
                                    .map(function (opt, index) {
                                        var rid = field.id + '-' + index;
                                        var checked = String(field.value || '') === String(opt) ? ' checked' : '';
                                        return (
                                            '<div class="form-check">' +
                                            '<input class="form-check-input" type="radio" name="' + radioName + '" id="' + rid + '" data-filter-input="1" data-label="' + field.label + '" value="' + String(opt) + '"' + checked + '>' +
                                            '<label class="form-check-label" for="' + rid + '">' + String(opt) + '</label>' +
                                            '</div>'
                                        );
                                    })
                                    .join('');
                                return (
                                    '<div class="filter-field"><label class="form-label">' +
                                    field.label +
                                    '</label><div>' + radioOptions + '</div></div>'
                                );
                            }
                            if (field.type === 'range') {
                                return (
                                    '<div class="filter-field"><label class="form-label">' +
                                    field.label +
                                    '</label><input id="' +
                                    field.id +
                                    '" data-filter-input="1" data-label="' +
                                    field.label +
                                    '" class="form-range" type="range" min="' + (field.min != null ? field.min : 0) + '" max="' + (field.max != null ? field.max : 100) + '" step="' + (field.step != null ? field.step : 1) + '" value="' + (field.value != null ? field.value : 0) + '"></div>'
                                );
                            }
                            return (
                                '<div class="filter-field"><label class="form-label">' +
                                field.label +
                                '</label><input id="' +
                                field.id +
                                '" data-filter-input="1" data-label="' +
                                field.label +
                                '" class="form-control form-control-sm" type="' +
                                field.type +
                                '" placeholder="' +
                                (field.placeholder || '') +
                                '"></div>'
                            );
                        })
                        .join('') +
                    '</div></div>'
                );
            })
            .join('');

        bindFilterSectionToggles(body);

        // Select2 con tema bootstrap-5 y dropdown dentro del panel (reinit forzado).
        if (window.GrovaSelect2 && typeof window.GrovaSelect2.init === 'function') {
            body.querySelectorAll('select.grova-select2').forEach(function (sel) {
                window.GrovaSelect2.init(sel, true);
            });
        }
    }

    function toggleFilters(forceOpen) {
        var shouldOpen =
            forceOpen === null || forceOpen === undefined
                ? !document.body.classList.contains('filters-open')
                : !!forceOpen;
        document.body.classList.toggle('filters-open', shouldOpen);
        if (shouldOpen) {
            var panel = document.getElementById('filters-panel');
            if (panel && window.GrovaSelect2 && typeof window.GrovaSelect2.initAll === 'function') {
                window.GrovaSelect2.initAll(panel);
            }
            document.dispatchEvent(new CustomEvent('grova:filters-opened'));
        }
    }

    function bindFilterChromeOnce(el, handler) {
        if (!el || el.dataset.grovaFilterBound === '1') return;
        el.dataset.grovaFilterBound = '1';
        el.addEventListener('click', handler);
    }

    function initFilterPanel() {
        if (filterPanelInitialized) return;
        if (!document.getElementById('filters-body')) return;

        filterPanelInitialized = true;

        var toggleBtn = document.getElementById('filters-toggle');
        var closeBtn = document.getElementById('filters-close');
        var overlay = document.getElementById('filters-overlay');
        var applyBtn = document.getElementById('filters-apply');
        var resetBtn = document.getElementById('filters-reset');
        var isCustom = filtersBodyIsCustom();

        bindFilterChromeOnce(toggleBtn, function () { toggleFilters(); });
        bindFilterChromeOnce(closeBtn, function () { toggleFilters(false); });
        bindFilterChromeOnce(overlay, function () { toggleFilters(false); });

        if (isCustom) {
            bindFilterSectionToggles(document.getElementById('filters-body'));
        }

        if (isCustom) {
            var activeFiltersText = document.getElementById('active-filters-text');
            var activeFiltersClear = document.getElementById('active-filters-clear');
            if (activeFiltersText) {
                bindFilterChromeOnce(activeFiltersText, function () { toggleFilters(true); });
            }
            if (activeFiltersClear) {
                bindFilterChromeOnce(activeFiltersClear, function () {
                    if (resetBtn) resetBtn.click();
                });
            }
            return;
        }

        if (applyBtn) {
            applyBtn.addEventListener('click', function () {
                var searchText = getCurrentFilterSummary().join(' ');
                var targetTable = resolveActiveDataTable();

                if (targetTable && typeof targetTable.search === 'function') {
                    targetTable.search(searchText).draw();
                } else {
                    var dtSearch = document.querySelector('.dt-search input');
                    if (dtSearch) {
                        dtSearch.value = searchText;
                        dtSearch.dispatchEvent(new Event('input', { bubbles: true }));
                    }
                }
                updateActiveFiltersBanner();
                toggleFilters(false);
            });
        }

        if (resetBtn) {
            resetBtn.addEventListener('click', function () {
                var targetTable = resolveActiveDataTable();
                document.querySelectorAll('[data-filter-input="1"]').forEach(function (el) {
                    if (!('value' in el)) return;
                    var type = String(el.type || '').toLowerCase();
                    if (type === 'checkbox' || type === 'radio') {
                        el.checked = false;
                        return;
                    }
                    if (el.tagName === 'SELECT') {
                        if (el.multiple) {
                            Array.from(el.options).forEach(function (opt) {
                                opt.selected = false;
                            });
                            if (window.jQuery) {
                                var $sel = window.jQuery(el);
                                if ($sel.data('select2')) {
                                    $sel.val(null).trigger('change');
                                }
                            }
                        } else {
                            el.selectedIndex = 0;
                        }
                    } else {
                        el.value = '';
                    }
                });
                if (targetTable && typeof targetTable.search === 'function') {
                    targetTable.search('').draw();
                }
                updateActiveFiltersBanner();
            });
        }

        var activeFiltersText = document.getElementById('active-filters-text');
        var activeFiltersClear = document.getElementById('active-filters-clear');
        if (activeFiltersText) {
            activeFiltersText.addEventListener('click', function () { toggleFilters(true); });
        }
        if (activeFiltersClear) {
            activeFiltersClear.addEventListener('click', function () {
                if (resetBtn) resetBtn.click();
            });
        }
    }

    function boot() {
        var schemaKey = 'default';
        var main = document.getElementById('main');
        var m = (main && main.getAttribute('data-grova-filter-schema')) || document.body.getAttribute('data-grova-filter-schema');
        if (m && FILTER_SCHEMAS[m]) {
            schemaKey = m;
        }
        if (!filtersBodyIsCustom()) {
            renderFilterPanelForView(schemaKey);
        } else {
            bindFilterSectionToggles(document.getElementById('filters-body'));
        }
        initFilterPanel();
        updateActiveFiltersBanner();
    }

    window.GrovaFilters = window.GrovaFilters || {};
    window.GrovaFilters.toggle = toggleFilters;
    window.GrovaFilters.refreshSections = bindFilterSectionToggles;

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();
