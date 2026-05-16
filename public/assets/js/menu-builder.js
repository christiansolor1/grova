var MenuBuilder = (function () {
    function hasSelect2() {
        return typeof window.jQuery !== 'undefined' && window.jQuery.fn && typeof window.jQuery.fn.select2 === 'function';
    }

    function initSelect2(selector, options) {
        if (!hasSelect2()) return;
        var $el = window.jQuery(selector);
        if (!$el.length) return;
        if ($el.hasClass('select2-hidden-accessible')) $el.select2('destroy');
        $el.select2(Object.assign({ theme: 'bootstrap-5', width: '100%' }, options || {}));
    }

    function esc(s) {
        return String(s || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }

    function t(key, fallback) {
        if (typeof window.traducir === 'function') {
            var translated = window.traducir(key);
            if (translated !== undefined && translated !== null && translated !== '') {
                return translated;
            }
        }
        return fallback || key;
    }

    function normalizeSearchText(value) {
        var text = String(value || '').toLowerCase().trim();
        if (!text) return '';
        if (typeof text.normalize === 'function') {
            text = text.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
        }
        return text;
    }

    function hasSwal() {
        return typeof window.Swal !== 'undefined' && window.Swal && typeof window.Swal.fire === 'function';
    }

    function alertInfo(icon, title, text) {
        if (hasSwal()) {
            return window.Swal.fire({ icon: icon || 'info', title: title || t('Información'), text: text || '', confirmButtonText: t('Entendido', 'Entendido') });
        }
        window.alert((title ? title + '\n\n' : '') + (text || ''));
        return Promise.resolve();
    }

    function confirmAction(title, text, confirmText, cancelText) {
        if (hasSwal()) {
            return window.Swal.fire({
                icon: 'question',
                title: title || t('¿Confirmar?', '¿Confirmar?'),
                text: text || '',
                showCancelButton: true,
                confirmButtonText: confirmText || t('Confirmar', 'Confirmar'),
                cancelButtonText: cancelText || t('Cancelar'),
                reverseButtons: true
            }).then(function (result) { return !!result.isConfirmed; });
        }
        return Promise.resolve(window.confirm((title ? title + '\n\n' : '') + (text || '')));
    }

    function normalizeIconClass(value) {
        var raw = String(value || '').trim();
        if (!raw) return 'bi bi-list';
        if (raw.indexOf('bi-') === 0) return 'bi ' + raw;
        if (raw.indexOf('fa-') === 0) return raw.indexOf(' ') > -1 ? raw : 'fa-solid ' + raw;
        return raw;
    }

    function serializeUiStyleForAttr(raw) {
        if (!raw) return '';
        if (typeof raw === 'string') return raw;
        try {
            return JSON.stringify(raw);
        } catch (e) {
            return '';
        }
    }

    function premiumPresetStyle() {
        return {
            bg: '#2A2110',
            text: '#FDE68A',
            border: '#F59E0B',
            hoverBg: '#3B2F12',
            hoverText: '#FFF3C4'
        };
    }

    function renderTreeSnapshot(tree, targetSelector, collapsedNodes, filterTerm) {
        var target = document.querySelector(targetSelector);
        if (!target) return;
        var body = target.querySelector('.snapshot-body');
        if (!body) return;
        var collapsed = collapsedNodes || {};
        var filter = normalizeSearchText(filterTerm);
        var isFiltering = !!filter;

        function nodeMatches(node) {
            var haystack = [
                node && node.label ? node.label : '',
                node && node.id ? node.id : '',
                node && node.href ? node.href : ''
            ].join(' ');
            return normalizeSearchText(haystack).indexOf(filter) > -1;
        }

            function row(node, level, typeClass, createMeta, parentKey) {
            var createBtn = '';
            if (createMeta && createMeta.type) {
                createBtn = '<button type="button" class="btn btn-xs btn-outline-success btn-create-child js-menu-create-child" data-create-type="' + esc(createMeta.type) + '" data-parent-key="' + esc(createMeta.parentKey || '') + '" data-principal-key="' + esc(createMeta.principalKey || '') + '" title="' + esc(createMeta.title || t('Agregar', 'Agregar')) + '" aria-label="' + esc(createMeta.title || t('Agregar', 'Agregar')) + '"><i class="bi bi-plus-lg"></i></button> ';
            }
            var hasChildren = !!((node.children || []).length) && level < 3;
            var isCollapsed = !!collapsed[String(node.id)];
            var collapseBtn = hasChildren
                ? '<button type="button" class="btn btn-xs btn-outline-secondary btn-icon-only js-tree-toggle-collapse" data-node-id="' + esc(node.id) + '" title="' + (isCollapsed ? t('Desacoplar todo', 'Desacoplar') : t('Acoplar todo', 'Acoplar')) + '" aria-label="' + (isCollapsed ? t('Desacoplar todo', 'Desacoplar') : t('Acoplar todo', 'Acoplar')) + '"><i class="bi ' + (isCollapsed ? 'bi-chevron-right' : 'bi-chevron-down') + '"></i></button> '
                : '';
            var dragMeta = ' draggable="true" data-sort-level="' + level + '" data-sort-key="' + esc(node.id) + '" data-sort-parent="' + esc(parentKey || '') + '"';
            var uiBadge = node.uiBadge ? String(node.uiBadge) : '';
            var uiStyle = serializeUiStyleForAttr(node.uiStyle);
            var controls = '<span class="snapshot-actions ms-auto">' +
                collapseBtn +
                createBtn +
                '<button type="button" class="btn btn-xs btn-outline-info btn-icon-only js-copy-path" data-copy="' + esc(node.id) + '" title="' + esc(t('Copiar path')) + '" aria-label="' + esc(t('Copiar path')) + '"><i class="bi bi-clipboard"></i></button> ' +
                '<button type="button" class="btn btn-xs btn-outline-warning btn-icon-only js-menu-appearance" data-menukey="' + esc(node.id) + '" data-label="' + esc(node.label) + '" data-icon="' + esc(node.icon || 'bi-list') + '" data-ui-badge="' + esc(uiBadge) + '" data-ui-style="' + esc(uiStyle) + '" title="' + esc(t('Apariencia')) + '"><i class="bi bi-palette"></i></button> ' +
                '<button type="button" class="btn btn-xs btn-outline-primary btn-icon-only js-menu-edit" data-menukey="' + esc(node.id) + '" data-label="' + esc(node.label) + '" data-icon="' + esc(node.icon || 'bi-list') + '" data-status="' + esc(node.status || 'hecho') + '" data-parent-key="' + esc(node.parentKey || '') + '" data-sort-order="' + esc(node.sortOrder || 0) + '" data-show-in-sidebar="' + esc(node.showInSidebar !== false ? '1' : '0') + '" data-dev-only="' + esc(node.devOnly ? '1' : '0') + '" data-ui-badge="' + esc(uiBadge) + '" data-ui-style="' + esc(uiStyle) + '" title="' + esc(t('Editar')) + '"><i class="bi bi-pencil"></i></button> ' +
                '<button type="button" class="btn btn-xs btn-outline-danger btn-icon-only btn-delete-action js-menu-delete" data-menukey="' + esc(node.id) + '" title="' + esc(t('Eliminar')) + '" aria-label="' + esc(t('Eliminar')) + '"><i class="bi bi-trash-fill"></i></button>' +
                '</span>';
            var iconHtml = '<span class="snapshot-item-icon" aria-hidden="true"><i class="' + esc(normalizeIconClass(node.icon || 'bi-list')) + '"></i></span>';
            return '<div class="snapshot-row ' + (level === 2 ? 'l2 ' : level === 3 ? 'l3 ' : '') + typeClass + '"' + dragMeta + '>' +
                '<span class="snapshot-drag-handle" title="' + esc(t('Arrastra para ordenar', 'Arrastra para ordenar')) + '">&#x2630;</span>' +
                iconHtml +
                '<span class="snapshot-row-label">' + esc(node.label) + '</span>' + controls + '</div>';
        }

        var html = '';
        (tree || []).forEach(function (l1) {
            var l1SelfMatch = isFiltering ? nodeMatches(l1) : true;
            var l1BranchMatch = l1SelfMatch;
            if (isFiltering && !l1BranchMatch) {
                l1BranchMatch = (l1.children || []).some(function (l2) {
                    if (nodeMatches(l2)) return true;
                    return (l2.children || []).some(function (l3) { return nodeMatches(l3); });
                });
            }
            if (isFiltering && !l1BranchMatch) return;
            html += row(l1, 1, 'hier-principal', { type: 'submenu', parentKey: l1.id, title: t('Agregar submenú') }, '');
            if (!isFiltering && collapsed[String(l1.id)]) return;
            (l1.children || []).forEach(function (l2) {
                var l2SelfMatch = isFiltering ? nodeMatches(l2) : true;
                var l2BranchMatch = l2SelfMatch;
                if (isFiltering && !l2BranchMatch) {
                    l2BranchMatch = (l2.children || []).some(function (l3) { return nodeMatches(l3); });
                }
                if (isFiltering && !l1SelfMatch && !l2BranchMatch) return;
                l2.parentKey = l1.id;
                html += row(l2, 2, 'hier-submenu', { type: 'subopcion', parentKey: l2.id, principalKey: l1.id, title: t('Agregar subopción') }, l1.id);
                if (!isFiltering && collapsed[String(l2.id)]) return;
                (l2.children || []).forEach(function (l3) {
                    if (isFiltering && !l1SelfMatch && !l2SelfMatch && !nodeMatches(l3)) return;
                    l3.parentKey = l2.id;
                    html += row(l3, 3, 'hier-subopcion', null, l2.id);
                });
            });
        });
        body.innerHTML = html || '<div class="small text-muted">' + (isFiltering ? t('Sin coincidencias para la búsqueda.') : t('No hay elementos en el árbol de menú.')) + '</div>';
    }

    function init(config) {
        var state = {
            menuTree: config.menuTree || [],
            level1Items: config.level1Items || [],
            level2Items: config.level2Items || [],
            editingMenuKey: null,
            collapsedNodes: {},
            persistedMenuTree: JSON.parse(JSON.stringify(config.menuTree || [])),
            orderDirty: false,
            treeFilterTerm: ''
        };

        var formEl = document.getElementById('menu-builder-form');
        var modeEl = document.getElementById('demo-create-mode');
        var singleFieldsEl = document.getElementById('single-create-fields');
        var bundleFieldsEl = document.getElementById('bundle-create-fields');
        var typeEl = document.getElementById('demo-item-type');
        var keyEl = document.getElementById('demo-menu-key');
        var labelEl = document.getElementById('demo-menu-label');
        var iconEl = document.getElementById('demo-menu-icon');
        var iconPreviewEl = document.getElementById('demo-menu-icon-preview');
        var uiVariantEl = document.getElementById('single-ui-variant');
        var uiCustomWrapEl = document.getElementById('single-ui-custom-wrap');
        var uiStyleJsonEl = document.getElementById('single-ui-style-json');
        var uiMiniPreviewEl = document.getElementById('single-ui-mini-preview');
        var uiMiniBadgeEl = document.getElementById('single-ui-mini-badge');
        var parentEl = document.getElementById('demo-parent-key');
        var parentGroupEl = document.getElementById('demo-parent-group');
        var parentMainLabelEl = document.getElementById('demo-parent-main-label');
        var parentPrincipalEl = document.getElementById('demo-parent-principal');
        var parentPrincipalLabelEl = document.getElementById('demo-parent-principal-label');
        var parentSubmenuLabelEl = document.getElementById('demo-parent-submenu-label');
        var parentHelpEl = document.getElementById('demo-parent-help');
        var positionEl = document.getElementById('demo-position-index');
        var saveBtn = document.getElementById('menu-builder-save-btn');
        var editMenuKeyEl = document.getElementById('menu-builder-edit-key');
        var metaEl = document.getElementById('menu-level-meta');
        var chipEl = document.getElementById('menu-level-chip');
        var previewEl = document.getElementById('menu-hierarchy-preview');
        var bundleKindEl = document.getElementById('bundle-kind');
        var bundleParentPrincipalWrapEl = document.getElementById('bundle-parent-principal-wrap');
        var bundleParentPrincipalSuboptionWrapEl = document.getElementById('bundle-parent-principal-suboption-wrap');
        var bundleParentSubmenuWrapEl = document.getElementById('bundle-parent-submenu-wrap');
        var bundleParentPrincipalSuboptionEl = document.getElementById('bundle-parent-principal-suboption');
        var bundleParentSubmenuEl = document.getElementById('bundle-parent-submenu');
        var editorColEl = document.getElementById('menu-builder-editor-col');
        var editorBackdropEl = document.getElementById('menu-builder-editor-backdrop');
        var openCreateBtn = document.getElementById('menu-builder-open-create');
        var openCreateInlineBtn = document.getElementById('menu-builder-open-create-inline');
        var closeEditorBtn = document.getElementById('menu-builder-close-editor');
        var cancelBtn = document.getElementById('menu-builder-cancel-btn');
        var editorTitleEl = document.getElementById('menu-builder-editor-title');
        var appearanceBackdropEl = document.getElementById('menu-appearance-backdrop');
        var appearanceColEl = document.getElementById('menu-appearance-col');
        var appearanceCloseEl = document.getElementById('menu-appearance-close');
        var appearanceCancelEl = document.getElementById('menu-appearance-cancel');
        var appearanceSaveEl = document.getElementById('menu-appearance-save');
        var appearanceKeyEl = document.getElementById('menu-appearance-key');
        var appearanceBadgeEl = document.getElementById('menu-appearance-badge');
        var appearanceVariantEl = document.getElementById('menu-appearance-variant');
        var appearanceCustomWrapEl = document.getElementById('menu-appearance-custom-wrap');
        var appearanceAutoDeriveEl = document.getElementById('menu-appearance-auto-derive');
        var appearanceBaseEl = document.getElementById('menu-appearance-base');
        var appearanceBgEl = document.getElementById('menu-appearance-bg');
        var appearanceTextEl = document.getElementById('menu-appearance-text');
        var appearanceBorderEl = document.getElementById('menu-appearance-border');
        var appearanceHoverBgEl = document.getElementById('menu-appearance-hover-bg');
        var appearanceHoverTextEl = document.getElementById('menu-appearance-hover-text');
        var appearancePreviewItemEl = document.getElementById('menu-appearance-preview-item');
        var appearancePreviewIconEl = document.getElementById('menu-appearance-preview-icon');
        var appearancePreviewLabelEl = document.getElementById('menu-appearance-preview-label');
        var appearancePreviewBadgeEl = document.getElementById('menu-appearance-preview-badge');
        var importBtn = document.getElementById('menu-import-btn');
        var importFileEl = document.getElementById('menu-import-file');
        var importReplaceEl = document.getElementById('menu-import-replace');
        var treeSnapshotEl = document.getElementById('menu-tree-snapshot');
        var treeSearchEl = document.getElementById('menu-tree-search');
        var treeSearchWrapEl = document.getElementById('menu-tree-search-wrap');
        var treeSearchClearEl = document.getElementById('menu-tree-search-clear');
        var collapseAllBtn = document.getElementById('menu-tree-collapse-all');
        var expandAllBtn = document.getElementById('menu-tree-expand-all');
        var saveActionsRowEl = document.getElementById('menu-tree-save-row');
        var saveActionsWrapEl = document.getElementById('menu-tree-save-actions');
        var saveOrderBtn = document.getElementById('menu-tree-save-order');
        var cancelOrderBtn = document.getElementById('menu-tree-cancel-order');
        if (!formEl || !modeEl || !singleFieldsEl || !bundleFieldsEl || !typeEl || !keyEl || !labelEl || !iconEl || !iconPreviewEl || !parentEl || !parentGroupEl || !parentMainLabelEl || !parentPrincipalEl || !parentPrincipalLabelEl || !parentSubmenuLabelEl || !parentHelpEl || !positionEl || !metaEl || !chipEl || !previewEl || !bundleKindEl || !bundleParentPrincipalWrapEl || !bundleParentPrincipalSuboptionWrapEl || !bundleParentSubmenuWrapEl || !bundleParentPrincipalSuboptionEl || !bundleParentSubmenuEl || !saveBtn || !editMenuKeyEl) return;

        // Portal: sacar modal/backdrop del árbol de contenido para evitar conflictos
        // de stacking context (footer, transforms, etc.).
        if (window.GrovaUi && typeof window.GrovaUi.portalToBody === 'function') {
            window.GrovaUi.portalToBody(editorBackdropEl);
            window.GrovaUi.portalToBody(editorColEl);
            window.GrovaUi.portalToBody(appearanceBackdropEl);
            window.GrovaUi.portalToBody(appearanceColEl);
        } else {
            if (editorBackdropEl && editorBackdropEl.parentNode !== document.body) {
                document.body.appendChild(editorBackdropEl);
            }
            if (editorColEl && editorColEl.parentNode !== document.body) {
                document.body.appendChild(editorColEl);
            }
            if (appearanceBackdropEl && appearanceBackdropEl.parentNode !== document.body) {
                document.body.appendChild(appearanceBackdropEl);
            }
            if (appearanceColEl && appearanceColEl.parentNode !== document.body) {
                document.body.appendChild(appearanceColEl);
            }
        }

        function parseUiStyle(raw) {
            if (!raw) return null;
            if (typeof raw === 'object') return raw;
            try {
                var parsed = JSON.parse(String(raw));
                return (parsed && typeof parsed === 'object') ? parsed : null;
            } catch (e) {
                return null;
            }
        }

        function normalizeHex(v, fallback) {
            var value = String(v || '').trim().toUpperCase();
            return /^#[0-9A-F]{6}$/.test(value) ? value : fallback;
        }

        function hexToRgb(hex) {
            var h = normalizeHex(hex, '#000000').slice(1);
            return {
                r: parseInt(h.slice(0, 2), 16),
                g: parseInt(h.slice(2, 4), 16),
                b: parseInt(h.slice(4, 6), 16)
            };
        }

        function rgbToHex(r, g, b) {
            var toHex = function (n) {
                var v = Math.max(0, Math.min(255, Math.round(n)));
                var s = v.toString(16).toUpperCase();
                return s.length === 1 ? '0' + s : s;
            };
            return '#' + toHex(r) + toHex(g) + toHex(b);
        }

        function mixHex(hexA, hexB, ratio) {
            var a = hexToRgb(hexA);
            var b = hexToRgb(hexB);
            var t = Math.max(0, Math.min(1, Number(ratio)));
            return rgbToHex(
                a.r + (b.r - a.r) * t,
                a.g + (b.g - a.g) * t,
                a.b + (b.b - a.b) * t
            );
        }

        function derivePaletteFromBase(baseHex, textHex) {
            var base = normalizeHex(baseHex, '#8B5A00');
            var text = normalizeHex(textHex, '#FFF7D6');
            // Mantiene look llamativo pero legible.
            var bg = mixHex(base, '#000000', 0.70);
            var border = mixHex(base, '#FFFFFF', 0.10);
            var hoverBg = mixHex(bg, '#FFFFFF', 0.08);
            return {
                bg: bg,
                text: text,
                border: border,
                hoverBg: hoverBg,
                hoverText: text
            };
        }

        function syncAppearanceAutoDerivedFields() {
            if (!appearanceAutoDeriveEl || !appearanceAutoDeriveEl.checked) return;
            var palette = derivePaletteFromBase(
                appearanceBaseEl ? appearanceBaseEl.value : '#8B5A00',
                appearanceTextEl ? appearanceTextEl.value : '#FFF7D6'
            );
            if (appearanceBgEl) appearanceBgEl.value = palette.bg;
            if (appearanceBorderEl) appearanceBorderEl.value = palette.border;
            if (appearanceHoverBgEl) appearanceHoverBgEl.value = palette.hoverBg;
            if (appearanceHoverTextEl) appearanceHoverTextEl.value = palette.hoverText;
        }

        function syncAppearanceAutoInputsState() {
            var auto = !!(appearanceAutoDeriveEl && appearanceAutoDeriveEl.checked);
            [appearanceBgEl, appearanceBorderEl, appearanceHoverBgEl, appearanceHoverTextEl].forEach(function (el) {
                if (!el) return;
                el.disabled = auto;
            });
            if (auto) syncAppearanceAutoDerivedFields();
        }

        function setAppearanceOpen(open) {
            if (!open) {
                forceCloseColorPickers();
            }
            if (appearanceColEl) appearanceColEl.classList.toggle('open', !!open);
            if (appearanceBackdropEl) appearanceBackdropEl.classList.toggle('open', !!open);
            document.body.classList.toggle('grova-modal-open', !!open || !!(editorColEl && editorColEl.classList.contains('open')));
        }

        function forceCloseColorPickers() {
            var colorInputs = [];
            if (appearanceColEl) {
                colorInputs = Array.prototype.slice.call(appearanceColEl.querySelectorAll('input[type="color"]'));
            }
            colorInputs.forEach(function (input) {
                if (!input) return;
                try { input.blur(); } catch (e) { /* ignore */ }
            });
            var active = document.activeElement;
            if (active && active.tagName === 'INPUT' && String(active.type || '').toLowerCase() === 'color') {
                try { active.blur(); } catch (e2) { /* ignore */ }
            }
        }

        function isColorInputElement(el) {
            return !!(el && el.tagName === 'INPUT' && String(el.type || '').toLowerCase() === 'color');
        }

        function readAppearanceStyleFromModal() {
            if (!appearanceVariantEl) return { badge: '', style: null };
            var variant = String(appearanceVariantEl.value || '');
            var badge = appearanceBadgeEl ? String(appearanceBadgeEl.value || '').trim() : '';
            if (variant === '') return { badge: badge, style: null };
            if (variant === 'premium') {
                if (!badge) badge = 'Premium';
                return { badge: badge, style: { variant: 'premium' } };
            }
            var style = {
                variant: 'custom',
                bg: normalizeHex(appearanceBgEl && appearanceBgEl.value, '#FEF3C7'),
                text: normalizeHex(appearanceTextEl && appearanceTextEl.value, '#111827'),
                border: normalizeHex(appearanceBorderEl && appearanceBorderEl.value, '#F59E0B'),
                hoverBg: normalizeHex(appearanceHoverBgEl && appearanceHoverBgEl.value, '#FDE68A'),
                hoverText: normalizeHex(appearanceHoverTextEl && appearanceHoverTextEl.value, '#111827')
            };
            if (appearanceAutoDeriveEl && appearanceAutoDeriveEl.checked) {
                var autoPalette = derivePaletteFromBase(
                    appearanceBaseEl ? appearanceBaseEl.value : '#8B5A00',
                    appearanceTextEl ? appearanceTextEl.value : '#FFF7D6'
                );
                style.bg = autoPalette.bg;
                style.border = autoPalette.border;
                style.hoverBg = autoPalette.hoverBg;
                style.hoverText = autoPalette.hoverText;
                style.text = autoPalette.text;
            }
            if (!badge) badge = 'Premium';
            return { badge: badge, style: style };
        }

        function syncAppearanceModalUi() {
            if (!appearanceVariantEl) return;
            var variant = String(appearanceVariantEl.value || '');
            if (appearanceCustomWrapEl) appearanceCustomWrapEl.style.display = variant === 'custom' ? '' : 'none';
            syncAppearanceAutoInputsState();
            var curr = readAppearanceStyleFromModal();
            if (appearancePreviewBadgeEl) {
                appearancePreviewBadgeEl.style.display = curr.badge ? '' : 'none';
                appearancePreviewBadgeEl.textContent = curr.badge || '';
            }
            if (appearancePreviewItemEl) {
                var css = '';
                var st = curr.style || {};
                if (String(st.variant || '') === 'premium') {
                    st = Object.assign({}, premiumPresetStyle(), st);
                }
                if (st.bg) css += '--menu-bg:' + st.bg + ';';
                if (st.text) css += '--menu-text:' + st.text + ';';
                if (st.border) css += '--menu-border:' + st.border + ';';
                if (st.hoverBg) css += '--menu-hover-bg:' + st.hoverBg + ';';
                if (st.hoverText) css += '--menu-hover-text:' + st.hoverText + ';';
                appearancePreviewItemEl.setAttribute('style', css);
            }
        }

        function openAppearanceEditor(btn) {
            if (!btn || !appearanceKeyEl) return;
            var menuKey = btn.getAttribute('data-menukey') || '';
            var label = btn.getAttribute('data-label') || menuKey;
            var icon = btn.getAttribute('data-icon') || 'bi-list';
            var badge = btn.getAttribute('data-ui-badge') || '';
            var styleRaw = btn.getAttribute('data-ui-style') || '';
            var styleObj = parseUiStyle(styleRaw) || null;

            appearanceKeyEl.value = menuKey;
            if (appearanceBadgeEl) appearanceBadgeEl.value = badge;
            if (appearancePreviewLabelEl) appearancePreviewLabelEl.textContent = label;
            if (appearancePreviewIconEl) appearancePreviewIconEl.innerHTML = '<i class="' + esc(normalizeIconClass(icon)) + '"></i>';

            var variant = '';
            if (styleObj && styleObj.variant === 'custom') variant = 'custom';
            else if (styleObj) variant = 'premium';
            if (appearanceVariantEl) appearanceVariantEl.value = variant;

            if (appearanceBgEl) appearanceBgEl.value = normalizeHex(styleObj && styleObj.bg, '#FEF3C7');
            if (appearanceTextEl) appearanceTextEl.value = normalizeHex(styleObj && styleObj.text, '#111827');
            if (appearanceBorderEl) appearanceBorderEl.value = normalizeHex(styleObj && styleObj.border, '#F59E0B');
            if (appearanceHoverBgEl) appearanceHoverBgEl.value = normalizeHex(styleObj && styleObj.hoverBg, '#FDE68A');
            if (appearanceHoverTextEl) appearanceHoverTextEl.value = normalizeHex(styleObj && styleObj.hoverText, '#111827');
            if (appearanceBaseEl) appearanceBaseEl.value = normalizeHex(styleObj && styleObj.border, '#8B5A00');
            if (appearanceAutoDeriveEl) appearanceAutoDeriveEl.checked = true;

            syncAppearanceModalUi();
            setAppearanceOpen(true);
        }

        function getSelectValue(el) {
            if (hasSelect2()) {
                var jq = window.jQuery(el);
                if (jq && jq.length) return jq.val();
            }
            return el.value;
        }

        function setSelectValue(el, value) {
            el.value = value;
            if (hasSelect2()) window.jQuery(el).val(value).trigger('change');
        }

        function syncCreateMode() {
            var isBundle = (getSelectValue(modeEl) || 'single') === 'bundle';
            singleFieldsEl.style.display = isBundle ? 'none' : '';
            bundleFieldsEl.style.display = isBundle ? '' : 'none';
        }

        function setIconPreview(iconClass) {
            iconPreviewEl.innerHTML = '<i class="' + esc(normalizeIconClass(iconClass)) + '"></i>';
        }

        function getSelectedIconClass() {
            if (!iconEl) return 'bi-list';
            var selectedOpt = iconEl.options && iconEl.selectedIndex >= 0 ? iconEl.options[iconEl.selectedIndex] : null;
            if (selectedOpt) {
                return selectedOpt.getAttribute('data-icon-class') || selectedOpt.value || 'bi-list';
            }
            return iconEl.value || 'bi-list';
        }

        function iconLibraryLabel(setOrClass) {
            var raw = String(setOrClass || '').trim();
            if (raw === 'fa' || raw.indexOf('fa-') === 0) return 'Font Awesome';
            return 'Bootstrap Icons';
        }

        function buildIconSelectTemplate(item) {
            if (!item || !item.id || !item.element) return item && item.text ? item.text : '';
            var optionEl = item.element;
            var iconClass = optionEl.getAttribute('data-icon-class') || item.id;
            var iconName = optionEl.getAttribute('data-icon-name') || item.text || item.id;
            var library = optionEl.getAttribute('data-icon-library') || iconLibraryLabel(iconClass);
            if (!hasSelect2()) return iconName + ' - ' + library;
            var $row = window.jQuery('<span class="d-inline-flex align-items-center gap-2"></span>');
            $row.append(window.jQuery('<i></i>').addClass(normalizeIconClass(iconClass)));
            $row.append(window.jQuery('<span></span>').text(iconName));
            $row.append(window.jQuery('<small class="text-muted"></small>').text(library));
            return $row;
        }

        function iconMatcher(params, data) {
            var term = (params.term || '').trim();
            if (!term) return data;
            var lib = window.GrovaIconLib;
            var normTerm = lib ? lib.normalizeText(term) : term.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
            // data.element es el <option> real del DOM — siempre disponible en selects locales.
            if (data.element) {
                var searchTags = data.element.getAttribute('data-search-tags') || '';
                if (searchTags.indexOf(normTerm) > -1) return data;
            }
            // Fallback: texto visible del option.
            var text = lib ? lib.normalizeText(data.text || '') : (data.text || '').toLowerCase();
            if (text.indexOf(normTerm) > -1) return data;
            return null;
        }

        function getIconSelect2Options() {
            var opts = {
                minimumResultsForSearch: 8,
                templateResult: buildIconSelectTemplate,
                templateSelection: buildIconSelectTemplate,
                escapeMarkup: function (m) { return m; },
                dropdownCssClass: 'menu-builder-icon-select2-dropdown',
                matcher: iconMatcher,
                closeOnSelect: false
            };
            if (editorColEl && hasSelect2()) {
                opts.dropdownParent = window.jQuery(editorColEl);
            }
            return opts;
        }

        function initIconSelect2() {
            initSelect2('#demo-menu-icon', getIconSelect2Options());
            bindIconKeyboardNavigation();
            bindIconArrowCycleOnClosedCombo();
        }

        function bindIconKeyboardNavigation() {
            if (!hasSelect2() || !iconEl) return;
            var $icon = window.jQuery(iconEl);
            $icon.off('select2:open.iconKeyboardNav').on('select2:open.iconKeyboardNav', function () {
                window.setTimeout(function () {
                    var searchInput = document.querySelector('.select2-container--open .select2-search__field');
                    if (!searchInput) return;
                    // Dejar que Select2 maneje nativamente ArrowUp/ArrowDown/Enter.
                    searchInput.focus();
                    if (searchInput._iconArrowSelectBound) return;
                    searchInput._iconArrowSelectBound = true;
                    searchInput.addEventListener('keydown', function (e) {
                        if (e.key !== 'ArrowDown' && e.key !== 'ArrowUp') return;
                        // Esperar a que Select2 mueva el highlight y luego confirmar selección.
                        window.setTimeout(function () {
                            var highlighted = document.querySelector('.select2-container--open .select2-results__option--highlighted.select2-results__option--selectable');
                            if (!highlighted) return;
                            highlighted.dispatchEvent(new MouseEvent('mouseup', { bubbles: true }));
                        }, 0);
                    });
                }, 0);
            });
        }

        function moveIconSelection(step) {
            if (!iconEl || !iconEl.options || !iconEl.options.length) return;
            var options = Array.prototype.slice.call(iconEl.options).filter(function (opt) {
                return !opt.disabled && String(opt.value || '').trim() !== '';
            });
            if (!options.length) return;
            var currentVal = getSelectValue(iconEl) || iconEl.value || '';
            var currentIdx = options.findIndex(function (opt) { return String(opt.value) === String(currentVal); });
            if (currentIdx < 0) currentIdx = 0;
            var nextIdx = currentIdx + step;
            if (nextIdx < 0) nextIdx = options.length - 1;
            if (nextIdx >= options.length) nextIdx = 0;
            var nextVal = options[nextIdx].value;
            setSelectValue(iconEl, nextVal);
            setIconPreview(getSelectedIconClass());
            renderPreview();
        }

        function bindIconArrowCycleOnClosedCombo() {
            if (!iconEl) return;
            var handler = function (e) {
                if (e.key !== 'ArrowDown' && e.key !== 'ArrowUp') return;
                // Con dropdown abierto dejamos navegación nativa de Select2.
                if (document.querySelector('.select2-container--open')) return;
                e.preventDefault();
                moveIconSelection(e.key === 'ArrowDown' ? 1 : -1);
            };

            if (iconEl._iconArrowCycleHandler) {
                iconEl.removeEventListener('keydown', iconEl._iconArrowCycleHandler);
            }
            iconEl._iconArrowCycleHandler = handler;
            iconEl.addEventListener('keydown', handler);

            // También capturar teclas cuando el foco está sobre el "pill" visible de Select2.
            if (hasSelect2()) {
                var sel = iconEl.parentElement ? iconEl.parentElement.querySelector('.select2-selection') : null;
                if (sel) {
                    if (sel._iconArrowCycleHandler) {
                        sel.removeEventListener('keydown', sel._iconArrowCycleHandler);
                    }
                    sel._iconArrowCycleHandler = handler;
                    sel.addEventListener('keydown', handler);
                }
            }
        }

        function refreshModalIconSelect2() {
            if (!hasSelect2() || !iconEl || !iconEl.querySelector('option')) return;
            var v = getSelectValue(iconEl) || iconEl.value || 'bi-list';
            initIconSelect2();
            setSelectValue(iconEl, v);
            setIconPreview(getSelectedIconClass());
        }

        function renderMenuItem(label, classes, iconClass, isNewItem, options) {
            var opts = options || {};
            var dataIndexAttr = typeof opts.previewIndex === 'number' ? ' data-preview-index="' + opts.previewIndex + '"' : '';
            var dataNewAttr = opts.isPreviewNew ? ' data-preview-new="1"' : '';
            var dataSortItem = opts.sortableItem ? ' data-sort-item="1"' : '';
            var draggableAttr = opts.sortableItem ? ' draggable="true"' : '';
            var dragHandle = opts.sortableItem
                ? '<span class="mini-item-drag-handle" title="' + esc(t('Arrastra para ordenar')) + '">&#x2630;</span>'
                : '';
            var copyBtn = opts.copyPath
                ? '<button type="button" class="btn btn-xs btn-outline-info btn-icon-only mini-item-copy-btn js-copy-path" data-copy="' + esc(opts.copyPath) + '" title="' + esc(t('Copiar path')) + '" aria-label="' + esc(t('Copiar path')) + '"><i class="bi bi-clipboard"></i></button>'
                : '';
            return '<div class="mini-item ' + esc(classes || '') + '"' + dataIndexAttr + dataNewAttr + dataSortItem + draggableAttr + '><span class="mini-item-content menu-ui">' + dragHandle + '<span class="mini-item-icon"><i class="' + esc(normalizeIconClass(iconClass)) + '"></i></span><span class="mini-item-label">' + esc(label || t('Sin nombre')) + '</span>' + copyBtn + (isNewItem ? '<span class="item-new-chip">' + t('Nuevo') + '</span>' : '') + '</span></div>';
        }

        function getDragAfterElement(container, y, draggingEl) {
            var elements = Array.prototype.slice.call(container.querySelectorAll('.mini-item[data-sort-item]'))
                .filter(function (el) { return el !== draggingEl; });
            var closest = { offset: Number.NEGATIVE_INFINITY, element: null };
            elements.forEach(function (child) {
                var box = child.getBoundingClientRect();
                var offset = y - box.top - box.height / 2;
                if (offset < 0 && offset > closest.offset) {
                    closest = { offset: offset, element: child };
                }
            });
            return closest.element;
        }

        function bindPreviewDragAndDrop() {
            var sortableContainers = previewEl.querySelectorAll('[data-sort-container]');
            if (!sortableContainers.length) return;

            sortableContainers.forEach(function (container) {
                var sortableRows = Array.prototype.slice.call(container.querySelectorAll('.mini-item[data-sort-item]'));
                if (!sortableRows.length) return;

                sortableRows.forEach(function (row) {
                    var miniLabelEl = row.querySelector('.mini-item-label');
                    if (miniLabelEl) {
                        miniLabelEl.addEventListener('mousedown', function () {
                            row.setAttribute('draggable', 'false');
                        });
                        miniLabelEl.addEventListener('mouseup', function () {
                            window.setTimeout(function () { row.setAttribute('draggable', 'true'); }, 0);
                        });
                        miniLabelEl.addEventListener('mouseleave', function () {
                            row.setAttribute('draggable', 'true');
                        });
                    }
                    row.addEventListener('dragstart', function (e) {
                        if (e.target && e.target.closest && (e.target.closest('.mini-item-copy-btn') || e.target.closest('.mini-item-label'))) {
                            e.preventDefault();
                            return;
                        }
                        if (!e.dataTransfer) return;
                        e.dataTransfer.effectAllowed = 'move';
                        e.dataTransfer.setData('text/plain', row.getAttribute('data-preview-new') ? 'new-item' : 'existing-item');
                        row.classList.add('dragging');
                    });

                    row.addEventListener('dragend', function () {
                        row.classList.remove('dragging');
                        container.querySelectorAll('.preview-drop-target').forEach(function (el) {
                            el.classList.remove('preview-drop-target');
                        });
                        var newRow = container.querySelector('.mini-item[data-preview-new="1"][data-sort-item]');
                        if (!newRow) return;
                        var allRows = Array.prototype.slice.call(container.querySelectorAll('.mini-item[data-sort-item]'));
                        var newIndex = allRows.indexOf(newRow);
                        if (newIndex < 0 || isNaN(newIndex)) return;
                        setSelectValue(positionEl, String(newIndex));
                    });
                });

                container.addEventListener('dragover', function (e) {
                    e.preventDefault();
                    var dragging = container.querySelector('.mini-item.dragging[data-sort-item]');
                    if (!dragging) return;
                    var afterElement = getDragAfterElement(container, e.clientY, dragging);
                    if (afterElement == null) {
                        container.appendChild(dragging);
                    } else {
                        container.insertBefore(dragging, afterElement);
                    }
                    container.querySelectorAll('.preview-drop-target').forEach(function (el) {
                        el.classList.remove('preview-drop-target');
                    });
                    if (afterElement) afterElement.classList.add('preview-drop-target');
                });

                container.addEventListener('dragleave', function () {
                    container.querySelectorAll('.preview-drop-target').forEach(function (el) {
                        el.classList.remove('preview-drop-target');
                    });
                });
            });
        }

        function setParentOptionsByType(itemType) {
            var options = [];
            if (itemType === 'principal') {
                parentGroupEl.style.display = 'none';
                options.push('<option value="">' + t('(No aplica)') + '</option>');
                parentEl.disabled = true;
                parentEl.style.display = '';
                parentPrincipalEl.style.display = 'none';
                parentPrincipalEl.disabled = true;
                parentPrincipalLabelEl.style.display = 'none';
                parentSubmenuLabelEl.style.display = 'none';
                parentHelpEl.textContent = t('Este elemento se creará en nivel principal.');
            } else if (itemType === 'submenu') {
                parentGroupEl.style.display = '';
                parentMainLabelEl.style.display = '';
                options = ['<option value="">' + t('Selecciona menú principal...') + '</option>'].concat(state.level1Items.map(function (it) { return '<option value="' + esc(it.key) + '">' + esc(it.label) + '</option>'; }));
                parentEl.disabled = false;
                parentEl.style.display = '';
                parentPrincipalEl.style.display = 'none';
                parentPrincipalEl.disabled = true;
                parentPrincipalLabelEl.style.display = 'none';
                parentSubmenuLabelEl.style.display = 'none';
                parentHelpEl.textContent = t('Selecciona el menú principal padre.');
            } else {
                parentGroupEl.style.display = '';
                parentMainLabelEl.style.display = '';
                parentPrincipalEl.innerHTML = ['<option value="">' + t('Selecciona menú principal...') + '</option>'].concat(state.level1Items.map(function (it) { return '<option value="' + esc(it.key) + '">' + esc(it.label) + '</option>'; })).join('');
                parentPrincipalEl.style.display = '';
                parentPrincipalEl.disabled = false;
                parentPrincipalLabelEl.style.display = '';
                parentSubmenuLabelEl.style.display = '';
                parentEl.style.display = '';
                parentHelpEl.textContent = t('Selecciona menú principal y submenú padre.');
            }
            if (itemType !== 'subopcion') {
                parentEl.innerHTML = options.join('');
                initSelect2('#demo-parent-key', { minimumResultsForSearch: 8 });
            }
            syncPositionOptions();
        }

        function syncSubmenuOptionsForSuboption() {
            var principal = getSelectValue(parentPrincipalEl) || '';
            var opts = ['<option value="">' + t('Selecciona submenú...') + '</option>'].concat(state.level2Items.filter(function (it) { return principal && it.parentKey === principal; }).map(function (it) { return '<option value="' + esc(it.key) + '">' + esc(it.label) + '</option>'; }));
            parentEl.innerHTML = opts.join('');
            parentEl.disabled = !principal;
            initSelect2('#demo-parent-key', { minimumResultsForSearch: 8 });
            syncPositionOptions();
        }

        function currentSiblingsForPosition() {
            var type = getSelectValue(typeEl) || 'principal';
            var siblings = [];
            if (type === 'principal') {
                siblings = (state.level1Items || []).map(function (it) { return { key: it.key, label: it.label, icon: it.icon || 'bi-list' }; });
            } else if (type === 'submenu') {
                var principal = getSelectValue(parentEl) || '';
                siblings = (state.level2Items || [])
                    .filter(function (it) { return principal && it.parentKey === principal; })
                    .map(function (it) { return { key: it.key, label: it.label, icon: it.icon || 'bi-list' }; });
            } else {
                var submenu = getSelectValue(parentEl) || '';
                var principalForSub = getSelectValue(parentPrincipalEl) || '';
                var l1 = (state.menuTree || []).find(function (n1) { return String(n1.id) === String(principalForSub); });
                var l2 = l1 ? (l1.children || []).find(function (n2) { return String(n2.id) === String(submenu); }) : null;
                siblings = (l2 && l2.children ? l2.children : []).map(function (it) {
                    return { key: it.id, label: it.label, icon: it.icon || 'bi-dot' };
                });
            }
            return siblings;
        }

        function syncPositionOptions() {
            var siblings = currentSiblingsForPosition();
            var options = ['<option value="">' + t('Al final') + '</option>'];
            for (var i = 0; i <= siblings.length - 1; i++) {
                options.push('<option value="' + i + '">' + t('Antes de: ') + esc(siblings[i].label) + '</option>');
            }
            positionEl.innerHTML = options.join('');
            initSelect2('#demo-position-index', { minimumResultsForSearch: Infinity });
        }

        function renderPreview() {
            var mode = getSelectValue(modeEl) || 'single';
            if (mode === 'bundle') {
                metaEl.textContent = t('Modo estructura activa.');
                chipEl.textContent = t('Bundle');
                chipEl.className = 'badge bg-info mb-2';
                previewEl.innerHTML = '<div class="sidebar-mini-preview"><div class="sidebar-mini-head"><span class="sidebar-mini-logo">G</span><span>' + t('Preview dinámico') + '</span></div><div class="sidebar-mini-body">' + renderMenuItem(t('Menú principal nuevo'), 'new hier-principal', 'bi-folder', true) + '<div class="mini-tree-level">' + renderMenuItem(t('Submenú nuevo'), 'new hier-submenu', 'bi-list', true) + '<div class="mini-tree-level l3">' + renderMenuItem(t('Subopción nueva'), 'new hier-subopcion', 'bi-dot', true) + '</div></div></div></div>';
                return;
            }

            var type = getSelectValue(typeEl) || 'principal';
            var label = (labelEl.value || t('Nuevo item')).trim() || t('Nuevo item');
            var icon = (iconEl.value || 'bi-list').trim();
            var parent = getSelectValue(parentEl) || '';
            var principal = getSelectValue(parentPrincipalEl) || '';
            var body = '';
            var editingKey = state.editingMenuKey || '';
            var isEditing = !!editingKey;
            var insertAtRaw = getSelectValue(positionEl);
            var insertAt = insertAtRaw === '' || insertAtRaw === null ? null : parseInt(insertAtRaw, 10);
            if (type === 'principal') {
                metaEl.textContent = t('Nivel: Menú principal | Sortable List (solo vista)');
                chipEl.textContent = isEditing ? (t('Editando: ') + editingKey) : t('Nuevo item: Menú principal');
                chipEl.className = 'badge ' + (isEditing ? 'bg-warning text-dark' : 'bg-success') + ' mb-2';
                body = '';
                var principalItems = (state.level1Items || []).slice();
                var inserted = false;
                body += '<div class="mini-sort-container" data-sort-container="principal">';
                principalItems.forEach(function (item, idx) {
                    var isCurrent = isEditing && String(item.key) === String(editingKey);
                    if (isCurrent) {
                        body += renderMenuItem(label, 'hier-principal current', icon || item.icon || 'bi-list', false, { previewIndex: idx, sortableItem: true, copyPath: item.key || '' });
                        return;
                    }
                    if (!isEditing && !inserted && insertAt !== null && idx === insertAt) {
                        body += renderMenuItem(label, 'new hier-principal', icon, true, { isPreviewNew: true, previewIndex: idx, sortableItem: true, copyPath: (keyEl.value || '').trim() });
                        inserted = true;
                    }
                    body += renderMenuItem(item.label, 'hier-principal', item.icon || 'bi-list', false, { previewIndex: idx, sortableItem: true, copyPath: item.key || '' });
                });
                if (!isEditing && !inserted) body += renderMenuItem(label, 'new hier-principal', icon, true, { isPreviewNew: true, previewIndex: principalItems.length, sortableItem: true, copyPath: (keyEl.value || '').trim() });
                body += '</div>';
            } else if (type === 'submenu') {
                metaEl.textContent = parent ? t('Nivel: Submenú | Sortable List (solo vista)') : t('Nivel: Submenú (falta padre) | Sortable List (solo vista)');
                chipEl.textContent = isEditing ? (t('Editando: ') + editingKey) : t('Nuevo item: Submenú');
                chipEl.className = 'badge ' + (isEditing ? 'bg-warning text-dark' : 'bg-primary') + ' mb-2';
                var parentLabel = (parentEl.options[parentEl.selectedIndex] && parentEl.options[parentEl.selectedIndex].text) || 'Menú principal';
                var submenuSiblings = currentSiblingsForPosition();
                var submenuHtml = '';
                var insertedSubmenu = false;
                submenuHtml += '<div class="mini-sort-container" data-sort-container="submenu">';
                submenuSiblings.forEach(function (sib, idx) {
                    var isCurrent2 = isEditing && String(sib.key) === String(editingKey);
                    if (isCurrent2) {
                        submenuHtml += renderMenuItem(label, 'hier-submenu current', icon || sib.icon || 'bi-list', false, { previewIndex: idx, sortableItem: true, copyPath: sib.key || '' });
                        return;
                    }
                    if (!isEditing && !insertedSubmenu && insertAt !== null && idx === insertAt) {
                        submenuHtml += renderMenuItem(label, 'new hier-submenu', icon, true, { isPreviewNew: true, previewIndex: idx, sortableItem: true, copyPath: (keyEl.value || '').trim() });
                        insertedSubmenu = true;
                    }
                    submenuHtml += renderMenuItem(sib.label, 'hier-submenu', sib.icon || 'bi-list', false, { previewIndex: idx, sortableItem: true, copyPath: sib.key || '' });
                });
                if (!isEditing && !insertedSubmenu) submenuHtml += renderMenuItem(label, 'new hier-submenu', icon, true, { isPreviewNew: true, previewIndex: submenuSiblings.length, sortableItem: true, copyPath: (keyEl.value || '').trim() });
                submenuHtml += '</div>';
                var principalKeyForSub = getSelectValue(parentEl) || '';
                var principalL1 = (state.level1Items || []).find(function (it) { return String(it.key) === String(principalKeyForSub); });
                var parentPrincipalIcon = principalL1 && principalL1.icon ? principalL1.icon : 'bi-list';
                body = renderMenuItem(parentLabel, 'hier-principal', parentPrincipalIcon, false, { copyPath: principalKeyForSub }) + '<div class="mini-tree-level">' + submenuHtml + '</div>';
            } else {
                metaEl.textContent = (principal && parent) ? t('Nivel: Subopción | Sortable List (solo vista)') : t('Nivel: Subopción (faltan padres) | Sortable List (solo vista)');
                chipEl.textContent = isEditing ? (t('Editando: ') + editingKey) : t('Nuevo item: Subopción');
                chipEl.className = 'badge ' + (isEditing ? 'bg-warning text-dark' : 'bg-secondary') + ' mb-2';
                var principalLabel = (parentPrincipalEl.options[parentPrincipalEl.selectedIndex] && parentPrincipalEl.options[parentPrincipalEl.selectedIndex].text) || 'Menú principal';
                var submenuLabel = (parentEl.options[parentEl.selectedIndex] && parentEl.options[parentEl.selectedIndex].text) || 'Submenú';
                var suboptionSiblings = currentSiblingsForPosition();
                var suboptionHtml = '';
                var insertedSuboption = false;
                suboptionHtml += '<div class="mini-sort-container" data-sort-container="subopcion">';
                suboptionSiblings.forEach(function (sib, idx) {
                    var isCurrent3 = isEditing && String(sib.key) === String(editingKey);
                    if (isCurrent3) {
                        suboptionHtml += renderMenuItem(label, 'hier-subopcion current', icon || sib.icon || 'bi-dot', false, { previewIndex: idx, sortableItem: true, copyPath: sib.key || '' });
                        return;
                    }
                    if (!isEditing && !insertedSuboption && insertAt !== null && idx === insertAt) {
                        suboptionHtml += renderMenuItem(label, 'new hier-subopcion', icon, true, { isPreviewNew: true, previewIndex: idx, sortableItem: true, copyPath: (keyEl.value || '').trim() });
                        insertedSuboption = true;
                    }
                    suboptionHtml += renderMenuItem(sib.label, 'hier-subopcion', sib.icon || 'bi-dot', false, { previewIndex: idx, sortableItem: true, copyPath: sib.key || '' });
                });
                if (!isEditing && !insertedSuboption) suboptionHtml += renderMenuItem(label, 'new hier-subopcion', icon, true, { isPreviewNew: true, previewIndex: suboptionSiblings.length, sortableItem: true, copyPath: (keyEl.value || '').trim() });
                suboptionHtml += '</div>';
                var principalKeyL3 = getSelectValue(parentPrincipalEl) || '';
                var submenuKeyL3 = getSelectValue(parentEl) || '';
                var pl3 = (state.level1Items || []).find(function (it) { return String(it.key) === String(principalKeyL3); });
                var sl3 = (state.level2Items || []).find(function (it) { return String(it.key) === String(submenuKeyL3); });
                var prIcon3 = pl3 && pl3.icon ? pl3.icon : 'bi-list';
                var smIcon3 = sl3 && sl3.icon ? sl3.icon : 'bi-list';
                body = renderMenuItem(principalLabel, 'hier-principal', prIcon3, false, { copyPath: principalKeyL3 }) + '<div class="mini-tree-level">' + renderMenuItem(submenuLabel, 'hier-submenu', smIcon3, false, { copyPath: submenuKeyL3 }) + '<div class="mini-tree-level l3">' + suboptionHtml + '</div></div>';
            }
            previewEl.innerHTML = '<div class="menu-level-legend"><span class="menu-level-pill principal"><span class="menu-level-dot"></span>' + t('Menú principal') + '</span><span class="menu-level-pill submenu"><span class="menu-level-dot"></span>' + t('Submenú') + '</span><span class="menu-level-pill subopcion"><span class="menu-level-dot"></span>' + t('Subopción') + '</span></div><div class="sidebar-mini-preview"><div class="sidebar-mini-head"><span class="sidebar-mini-logo">G</span><span>' + t('Preview dinámico') + '</span></div><div class="sidebar-mini-body">' + body + '</div></div>';
            bindPreviewDragAndDrop();
        }

        function syncBundleKind() {
            var kind = getSelectValue(bundleKindEl) || 'full';
            bundleParentPrincipalWrapEl.style.display = kind === 'submenu' ? '' : 'none';
            bundleParentPrincipalSuboptionWrapEl.style.display = kind === 'subopcion' ? '' : 'none';
            bundleParentSubmenuWrapEl.style.display = kind === 'subopcion' ? '' : 'none';
        }

        function syncBundleSubmenuOptions() {
            var selectedPrincipal = getSelectValue(bundleParentPrincipalSuboptionEl) || '';
            var options = ['<option value="">' + t('Selecciona submenú...') + '</option>'].concat(state.level2Items.filter(function (it) { return selectedPrincipal && it.parentKey === selectedPrincipal; }).map(function (it) { return '<option value="' + esc(it.key) + '">' + esc(it.label) + '</option>'; }));
            bundleParentSubmenuEl.innerHTML = options.join('');
            bundleParentSubmenuEl.disabled = !selectedPrincipal;
            initSelect2('#bundle-parent-submenu', { minimumResultsForSearch: 8 });
        }

        function formToPayload() {
            var formData = new FormData(formEl);
            var payload = {};
            formData.forEach(function (value, key) { payload[key] = value; });
            // Valores heredados por política de negocio (no visibles para usuario).
            payload.single_show_in_sidebar = true;
            payload.single_dev_only = false;
            if (!payload.single_status) payload.single_status = 'pendiente';
            payload._token = config.csrfToken;
            return payload;
        }

        function readUiStyleFromForm() {
            var variant = uiVariantEl ? String(uiVariantEl.value || '') : '';
            var badge = '';
            var badgeInput = formEl.querySelector('input[name="single_ui_badge"]');
            if (badgeInput) badge = String(badgeInput.value || '').trim();

            var style = null;
            if (variant === 'premium') {
                style = { variant: 'premium' };
                if (!badge) badge = 'Premium';
            } else if (variant === 'custom') {
                var bg = (formEl.querySelector('input[name="single_ui_bg"]') || {}).value;
                var text = (formEl.querySelector('input[name="single_ui_text"]') || {}).value;
                var border = (formEl.querySelector('input[name="single_ui_border"]') || {}).value;
                var hoverBg = (formEl.querySelector('input[name="single_ui_hover_bg"]') || {}).value;
                var hoverText = (formEl.querySelector('input[name="single_ui_hover_text"]') || {}).value;
                style = {
                    variant: 'custom',
                    bg: String(bg || '').toUpperCase(),
                    text: String(text || '').toUpperCase(),
                    border: String(border || '').toUpperCase(),
                    hoverBg: String(hoverBg || '').toUpperCase(),
                    hoverText: String(hoverText || '').toUpperCase()
                };
                if (!badge) badge = 'Premium';
            }

            return { badge: badge, style: style };
        }

        function writeUiStyleHiddenJson() {
            if (!uiStyleJsonEl) return;
            var ui = readUiStyleFromForm();
            uiStyleJsonEl.value = ui.style ? JSON.stringify(ui.style) : '';
        }

        function syncUiAppearanceControls() {
            if (!uiVariantEl) return;
            var variant = String(uiVariantEl.value || '');
            if (uiCustomWrapEl) uiCustomWrapEl.style.display = variant === 'custom' ? '' : 'none';

            var ui = readUiStyleFromForm();
            if (uiMiniBadgeEl) {
                uiMiniBadgeEl.style.display = ui.badge ? '' : 'none';
                uiMiniBadgeEl.textContent = ui.badge || '';
            }
            if (uiMiniPreviewEl) {
                var vars = '';
                var s = ui.style || {};
                if (String(s.variant || '') === 'premium') {
                    s = Object.assign({}, premiumPresetStyle(), s);
                }
                if (s.bg) vars += '--menu-bg:' + s.bg + ';';
                if (s.text) vars += '--menu-text:' + s.text + ';';
                if (s.border) vars += '--menu-border:' + s.border + ';';
                if (s.hoverBg) vars += '--menu-hover-bg:' + s.hoverBg + ';';
                if (s.hoverText) vars += '--menu-hover-text:' + s.hoverText + ';';
                uiMiniPreviewEl.setAttribute('style', vars);
            }

            writeUiStyleHiddenJson();
        }

        function buildItemsFromTree(tree) {
            var l1 = [];
            var l2 = [];
            (tree || []).forEach(function (n1) {
                l1.push({ key: n1.id, label: n1.label, icon: n1.icon || 'bi-list' });
                (n1.children || []).forEach(function (n2) {
                    l2.push({ key: n2.id, label: n2.label, parentKey: n1.id, parentLabel: n1.label, icon: n2.icon || 'bi-list' });
                });
            });
            state.level1Items = l1;
            state.level2Items = l2;
        }

        function applyTree(tree) {
            state.menuTree = tree || [];
            state.persistedMenuTree = JSON.parse(JSON.stringify(state.menuTree || []));
            state.orderDirty = false;
            buildItemsFromTree(state.menuTree);
            renderTreeSnapshot(state.menuTree, '#menu-tree-snapshot', state.collapsedNodes, state.treeFilterTerm);
            bindTreeSnapshotSortable();
            updateOrderActionsState();
            refreshSidebarNav(state.menuTree);
            if ((getSelectValue(typeEl) || '') === 'subopcion') syncSubmenuOptionsForSuboption();
            renderPreview();
        }

        function refreshSidebarNav(tree) {
            var nav = document.getElementById('sidebar-nav');
            if (!nav) return;

            // Preservar qué <details> estaban abiertos (por key) si ya existen.
            var openKeys = new Set();
            nav.querySelectorAll('details[open][data-node-key]').forEach(function (d) {
                var k = d.getAttribute('data-node-key');
                if (k) openKeys.add(String(k));
            });

            function pendingBadgeHtml(item) {
                var children = item.children || [];
                var isPending = String(item.status || 'hecho') === 'pendiente';
                var isLeafNoHref = (!children.length) && (!item.href);
                return (isPending || isLeafNoHref) ? '<span class="menu-status-badge">Pendiente</span>' : '';
            }

            function premiumBadgeHtml(item) {
                var badge = item && item.uiBadge ? String(item.uiBadge) : '';
                return badge ? '<span class="menu-premium-badge">' + esc(badge) + '</span>' : '';
            }

            function parseUiStyleObject(raw) {
                if (!raw) return '';
                var obj = null;
                if (typeof raw === 'string') {
                    try { obj = JSON.parse(raw); } catch (e) { obj = null; }
                } else if (typeof raw === 'object') {
                    obj = raw;
                }
                if (!obj) return null;
                if (String(obj.variant || '') === 'premium') {
                    obj = Object.assign({}, premiumPresetStyle(), obj);
                }
                return obj;
            }

            function uiStyleVars(item, inheritedStyle) {
                var own = parseUiStyleObject(item && item.uiStyle ? item.uiStyle : null);
                var obj = own || inheritedStyle || null;
                if (!obj) return '';
                var css = '';
                if (obj.bg) css += '--menu-bg:' + String(obj.bg) + ';';
                if (obj.text) css += '--menu-text:' + String(obj.text) + ';';
                if (obj.border) css += '--menu-border:' + String(obj.border) + ';';
                if (obj.hoverBg) css += '--menu-hover-bg:' + String(obj.hoverBg) + ';';
                if (obj.hoverText) css += '--menu-hover-text:' + String(obj.hoverText) + ';';
                return css;
            }

            function iconHtml(icon, sizePx) {
                var cls = normalizeIconClass(icon || 'bi-list');
                return '<span class="nav-icon" aria-hidden="true"><i class="' + esc(cls) + '" style="font-size:' + (sizePx || 16) + 'px;"></i></span>';
            }

            function isActiveHref(href) {
                if (!href) return false;
                try {
                    var u = new URL(href, window.location.origin);
                    return u.pathname === window.location.pathname;
                } catch (e) {
                    return href === window.location.pathname;
                }
            }

            var activePathKeys = [];
            var activeKeyFromPath = null;
            function findActiveKeyByHref(nodes) {
                var found = null;
                (nodes || []).forEach(function (n) {
                    if (found) return;
                    if (n.href && isActiveHref(n.href)) found = n.id;
                    if (!found && (n.children || []).length) found = findActiveKeyByHref(n.children);
                });
                return found;
            }
            function buildPathToKey(nodes, targetKey, path) {
                for (var i = 0; i < (nodes || []).length; i++) {
                    var n = nodes[i];
                    path.push(n.id);
                    if (n.id === targetKey) return true;
                    if ((n.children || []).length && buildPathToKey(n.children, targetKey, path)) return true;
                    path.pop();
                }
                return false;
            }
            activeKeyFromPath = findActiveKeyByHref(tree || []);
            if (activeKeyFromPath) {
                buildPathToKey(tree || [], activeKeyFromPath, activePathKeys);
            }
            function isTrailAncestor(id) {
                return activeKeyFromPath && activePathKeys.indexOf(id) >= 0 && id !== activeKeyFromPath;
            }

            function renderItems(items, depth, inheritedStyle) {
                var html = '';
                (items || []).forEach(function (item) {
                    var children = item.children || [];
                    var hasChildren = children.length > 0;
                    var label = esc(item.label || '');
                    var tip = esc(item.label || '');
                    var badge = pendingBadgeHtml(item);
                    var ico = iconHtml(item.icon || 'bi-list', depth === 0 ? 16 : depth === 1 ? 13 : 11);
                    var openAttr = openKeys.has(String(item.id)) ? ' open' : '';
                    var ownStyle = parseUiStyleObject(item && item.uiStyle ? item.uiStyle : null);
                    var effectiveStyle = ownStyle || inheritedStyle || null;
                    var styleVars = uiStyleVars(item, inheritedStyle);

                    if (depth === 0) {
                        html += '<div class="nav-l1 ws-nav-l1">';
                        if (hasChildren) {
                            html += '<details class="ws-details" data-node-key="' + esc(item.id) + '"' + openAttr + '>';
                            html += '<summary class="nav-l1-btn ws-summary menu-ui' + (isTrailAncestor(item.id) ? ' nav-on-trail' : '') + '" style="' + esc(styleVars) + '" data-nav-tip="' + tip + '">' + ico + '<span class="nav-label">' + label + '</span>' + premiumBadgeHtml(item) + badge + '<span class="nav-chevron" aria-hidden="true">▾</span></summary>';
                            html += '<div class="nav-l2-wrap ws-sub">' + renderItems(children, 1, effectiveStyle) + '</div>';
                            html += '</details>';
                        } else if (item.href) {
                            var active = isActiveHref(item.href) ? ' active-leaf' : '';
                            var trail0 = isTrailAncestor(item.id) && !active ? ' nav-on-trail' : '';
                            html += '<a href="' + esc(item.href) + '" class="nav-l1-btn menu-ui' + trail0 + active + '" style="' + esc(styleVars) + '" data-nav-tip="' + tip + '" aria-label="' + tip + '">' + ico + '<span class="nav-label">' + label + '</span>' + premiumBadgeHtml(item) + badge + '</a>';
                        } else {
                            html += '<div class="nav-l1-btn ws-disabled menu-ui" style="' + esc(styleVars) + '" data-nav-tip="' + tip + '" aria-label="' + tip + '">' + ico + '<span class="nav-label">' + label + '</span>' + premiumBadgeHtml(item) + badge + '</div>';
                        }
                        html += '</div>';
                        return;
                    }

                    if (depth === 1) {
                        html += '<div>';
                        if (hasChildren) {
                            html += '<details class="ws-details" data-node-key="' + esc(item.id) + '"' + openAttr + '>';
                            html += '<summary class="nav-l2-btn ws-summary menu-ui' + (isTrailAncestor(item.id) ? ' nav-on-trail' : '') + '" style="' + esc(styleVars) + '" data-nav-tip="' + tip + '">' + ico + '<span class="flex-1">' + label + '</span>' + premiumBadgeHtml(item) + badge + '<span class="nav-chevron" aria-hidden="true">▾</span></summary>';
                            html += '<div class="nav-l3-wrap">' + renderItems(children, 2, effectiveStyle) + '</div>';
                            html += '</details>';
                        } else if (item.href) {
                            var active2 = isActiveHref(item.href) ? ' active-leaf' : '';
                            var trail1 = isTrailAncestor(item.id) && !active2 ? ' nav-on-trail' : '';
                            html += '<a href="' + esc(item.href) + '" class="nav-l2-btn menu-ui' + trail1 + active2 + '" style="' + esc(styleVars) + '" data-nav-tip="' + tip + '" aria-label="' + tip + '">' + ico + '<span class="flex-1">' + label + '</span>' + premiumBadgeHtml(item) + badge + '</a>';
                        } else {
                            html += '<div class="nav-l2-btn ws-disabled menu-ui" style="' + esc(styleVars) + '" data-nav-tip="' + tip + '" aria-label="' + tip + '">' + ico + '<span class="flex-1">' + label + '</span>' + premiumBadgeHtml(item) + badge + '</div>';
                        }
                        html += '</div>';
                        return;
                    }

                    if (item.href) {
                        var active3 = isActiveHref(item.href) ? ' active nav-active-current' : '';
                        var ariaC = isActiveHref(item.href) ? ' aria-current="page"' : '';
                        html += '<a href="' + esc(item.href) + '" class="nav-l3-btn menu-ui' + active3 + '" style="' + esc(styleVars) + '" data-nav-tip="' + tip + '" aria-label="' + tip + '"' + ariaC + '>' + ico + '<span>' + label + '</span>' + premiumBadgeHtml(item) + badge + '</a>';
                    } else {
                        html += '<div class="nav-l3-btn ws-disabled menu-ui" style="' + esc(styleVars) + '" data-nav-tip="' + tip + '" aria-label="' + tip + '">' + ico + '<span>' + label + '</span>' + premiumBadgeHtml(item) + badge + '</div>';
                    }
                });
                return html;
            }

            nav.innerHTML = renderItems(tree || [], 0, null);

            // Reaplicar comportamiento del sidebar (abrir ruta activa, flyouts, filtro, etc.).
            document.dispatchEvent(new CustomEvent('grova:sidebar-updated'));
        }

        function orderSignature(tree) {
            var lines = [];
            function walk(nodes, parentKey) {
                (nodes || []).forEach(function (n, idx) {
                    var key = String(n.id || '');
                    lines.push((parentKey || 'ROOT') + '|' + key + '|' + String(idx + 1));
                    walk(n.children || [], key);
                });
            }
            walk(tree || [], '');
            return lines.join('||');
        }

        function isOrderDirty() {
            return orderSignature(state.menuTree || []) !== orderSignature(state.persistedMenuTree || []);
        }

        function updateOrderActionsState() {
            state.orderDirty = isOrderDirty();
            if (saveActionsWrapEl) {
                saveActionsWrapEl.classList.toggle('d-none', !state.orderDirty);
            }
            if (saveActionsRowEl) {
                saveActionsRowEl.classList.toggle('d-none', !state.orderDirty);
            }
            if (saveOrderBtn) saveOrderBtn.disabled = !state.orderDirty;
            if (cancelOrderBtn) cancelOrderBtn.disabled = !state.orderDirty;
        }

        function collectBulkSortPayload(tree) {
            var rows = [];
            function walk(nodes, parentKey) {
                (nodes || []).forEach(function (n, idx) {
                    rows.push({
                        menuKey: String(n.id),
                        parentKey: parentKey || null,
                        sortOrder: idx + 1
                    });
                    walk(n.children || [], String(n.id));
                });
            }
            walk(tree || [], '');
            return rows;
        }

        function collectCollapsibleNodeIds() {
            var ids = [];
            (state.menuTree || []).forEach(function (l1) {
                if ((l1.children || []).length) ids.push(String(l1.id));
                (l1.children || []).forEach(function (l2) {
                    if ((l2.children || []).length) ids.push(String(l2.id));
                });
            });
            return ids;
        }

        function syncTreeSearchClear() {
            if (treeSearchWrapEl && treeSearchEl) {
                treeSearchWrapEl.classList.toggle('has-value', (treeSearchEl.value || '').trim().length > 0);
            }
        }

        function bindTreeSnapshotSortable() {
            var snapshot = document.getElementById('menu-tree-snapshot');
            if (!snapshot) return;
            var body = snapshot.querySelector('.snapshot-body');
            if (!body) return;

            function getChildrenList(level, parentKey) {
                if (String(level) === '1') return state.menuTree || [];
                if (String(level) === '2') {
                    var principal = (state.menuTree || []).find(function (n1) { return String(n1.id) === String(parentKey); });
                    return principal ? (principal.children || []) : null;
                }
                if (String(level) === '3') {
                    var submenuNode = null;
                    (state.menuTree || []).some(function (n1) {
                        submenuNode = (n1.children || []).find(function (n2) { return String(n2.id) === String(parentKey); }) || null;
                        return !!submenuNode;
                    });
                    return submenuNode ? (submenuNode.children || []) : null;
                }
                return null;
            }

            function moveNodeInLevel(level, parentKey, sourceKey, targetKey, placeAfter) {
                var list = getChildrenList(level, parentKey);
                if (!Array.isArray(list)) return false;
                var fromIdx = list.findIndex(function (n) { return String(n.id) === String(sourceKey); });
                var toIdx = list.findIndex(function (n) { return String(n.id) === String(targetKey); });
                if (fromIdx < 0 || toIdx < 0 || fromIdx === toIdx) return false;
                var moved = list.splice(fromIdx, 1)[0];
                var targetIdx = list.findIndex(function (n) { return String(n.id) === String(targetKey); });
                if (targetIdx < 0) {
                    list.push(moved);
                    return true;
                }
                var insertIdx = placeAfter ? (targetIdx + 1) : targetIdx;
                list.splice(insertIdx, 0, moved);
                return true;
            }

            var dragMeta = null;
            function clearDropIndicators() {
                body.querySelectorAll('.snapshot-row.preview-drop-target').forEach(function (el) {
                    el.classList.remove('preview-drop-target');
                });
            }
            body.querySelectorAll('.snapshot-row[data-sort-key]').forEach(function (row) {
                var labelEl = row.querySelector('.snapshot-row-label');
                if (labelEl) {
                    labelEl.addEventListener('mousedown', function () {
                        row.setAttribute('draggable', 'false');
                    });
                    labelEl.addEventListener('mouseup', function () {
                        window.setTimeout(function () { row.setAttribute('draggable', 'true'); }, 0);
                    });
                    labelEl.addEventListener('mouseleave', function () {
                        row.setAttribute('draggable', 'true');
                    });
                }
                row.addEventListener('dragstart', function (e) {
                    if (e.target && e.target.closest && (e.target.closest('.snapshot-actions') || e.target.closest('.snapshot-row-label'))) {
                        e.preventDefault();
                        return;
                    }
                    dragMeta = {
                        key: row.getAttribute('data-sort-key') || '',
                        level: row.getAttribute('data-sort-level') || '',
                        parent: row.getAttribute('data-sort-parent') || '',
                        targetKey: '',
                        placeAfter: false
                    };
                    row.classList.add('dragging');
                    if (e.dataTransfer) {
                        e.dataTransfer.effectAllowed = 'move';
                        e.dataTransfer.setData('text/plain', dragMeta.key);
                    }
                });
                row.addEventListener('dragend', function () {
                    row.classList.remove('dragging');
                    clearDropIndicators();
                    dragMeta = null;
                });
            });

            body.addEventListener('dragover', function (e) {
                e.preventDefault();
                var targetRow = e.target.closest('.snapshot-row[data-sort-key]');
                if (!targetRow || !dragMeta) return;
                if (targetRow.getAttribute('data-sort-level') !== dragMeta.level) return;
                if (targetRow.getAttribute('data-sort-parent') !== dragMeta.parent) return;
                if (targetRow.getAttribute('data-sort-key') === dragMeta.key) return;
                var draggingRow = body.querySelector('.snapshot-row.dragging[data-sort-key="' + dragMeta.key + '"]');
                if (!draggingRow) return;
                clearDropIndicators();
                var rect = targetRow.getBoundingClientRect();
                var placeAfter = e.clientY > (rect.top + rect.height / 2);
                if (placeAfter) {
                    if (targetRow.nextSibling) targetRow.parentNode.insertBefore(draggingRow, targetRow.nextSibling);
                    else targetRow.parentNode.appendChild(draggingRow);
                } else {
                    targetRow.parentNode.insertBefore(draggingRow, targetRow);
                }
                targetRow.classList.add('preview-drop-target');
                dragMeta.targetKey = targetRow.getAttribute('data-sort-key') || '';
                dragMeta.placeAfter = placeAfter;
            });

            body.addEventListener('drop', function (e) {
                e.preventDefault();
                if (!dragMeta || !dragMeta.targetKey) return;
                if (!moveNodeInLevel(dragMeta.level, dragMeta.parent, dragMeta.key, dragMeta.targetKey, !!dragMeta.placeAfter)) return;
                buildItemsFromTree(state.menuTree);
                renderTreeSnapshot(state.menuTree, '#menu-tree-snapshot', state.collapsedNodes, state.treeFilterTerm);
                bindTreeSnapshotSortable();
                updateOrderActionsState();
                syncPositionOptions();
                renderPreview();
            });
        }

        function request(method, payload) {
            return Api.request(config.saveUrl, {
                method: method,
                body: Object.assign({}, payload || {}, { _token: config.csrfToken }),
                csrfToken: config.csrfToken
            });
        }

        function importJsonFile(file, replaceAll) {
            if (!config.importUrl) {
                alertInfo('error', t('Error'), t('Import URL no configurado.'));
                return;
            }
            var fd = new FormData();
            fd.append('file', file);
            fd.append('replace', replaceAll ? '1' : '0');
            fd.append('_token', config.csrfToken);
            Api.request(config.importUrl, { method: 'POST', body: fd, csrfToken: config.csrfToken })
                .then(function (json) {
                    applyTree(json.menu_tree || []);
                    alertInfo('success', t('Listo'), json.message || t('Operación completada.'));
                })
                .catch(function (err) {
                    if (err.type === 'business') {
                        alertInfo('error', t('Error'), err.json.message || t('No se pudo importar.'));
                    } else {
                        alertInfo('error', t('Error'), t('No se pudo importar el archivo.'));
                    }
                });
        }

        function resetForm() {
            state.editingMenuKey = null;
            editMenuKeyEl.value = '';
            formEl.reset();
            setSelectValue(modeEl, 'single');
            setSelectValue(typeEl, 'submenu');
            setSelectValue(iconEl, 'bi-list');
            setIconPreview('bi-list');
            if (uiVariantEl) uiVariantEl.value = '';
            var badgeInput = formEl.querySelector('input[name="single_ui_badge"]');
            if (badgeInput) badgeInput.value = '';
            if (uiStyleJsonEl) uiStyleJsonEl.value = '';
            syncUiAppearanceControls();
            saveBtn.textContent = t('Guardar');
            keyEl.removeAttribute('readonly');
            syncCreateMode();
            setParentOptionsByType('submenu');
            syncBundleKind();
            renderPreview();
            if (editorTitleEl) editorTitleEl.textContent = t('Crear menú');
        }

        function setEditorOpen(open) {
            if (editorColEl) editorColEl.classList.toggle('open', !!open);
            if (editorBackdropEl) editorBackdropEl.classList.toggle('open', !!open);
            var appearanceIsOpen = !!(appearanceColEl && appearanceColEl.classList.contains('open'));
            document.body.classList.toggle('grova-modal-open', !!open || appearanceIsOpen);
            if (open) {
                window.requestAnimationFrame(function () {
                    window.requestAnimationFrame(refreshModalIconSelect2);
                });
            }
        }

        function enterEditMode(rowBtn) {
            state.editingMenuKey = rowBtn.getAttribute('data-menukey') || '';
            editMenuKeyEl.value = state.editingMenuKey;
            setSelectValue(modeEl, 'single');
            syncCreateMode();
            saveBtn.textContent = t('Actualizar');
            keyEl.value = state.editingMenuKey;
            keyEl.setAttribute('readonly', 'readonly');
            labelEl.value = rowBtn.getAttribute('data-label') || '';
            setSelectValue(iconEl, rowBtn.getAttribute('data-icon') || 'bi-list');
            setIconPreview(iconEl.value || 'bi-list');
            setSelectValue(document.getElementById('single-status'), rowBtn.getAttribute('data-status') || 'hecho');
            document.getElementById('single-show-in-sidebar').checked = rowBtn.getAttribute('data-show-in-sidebar') === '1';
            document.querySelector('input[name="single_dev_only"]').checked = rowBtn.getAttribute('data-dev-only') === '1';

            // Premium/UI (si existe en el botón)
            var badge = rowBtn.getAttribute('data-ui-badge') || '';
            var styleRaw = rowBtn.getAttribute('data-ui-style') || '';
            var badgeInput = formEl.querySelector('input[name="single_ui_badge"]');
            if (badgeInput) badgeInput.value = badge;
            if (uiStyleJsonEl) uiStyleJsonEl.value = styleRaw;
            if (uiVariantEl) {
                var variant = '';
                if (styleRaw) {
                    try {
                        var st = JSON.parse(styleRaw);
                        if (st && st.variant) variant = String(st.variant);
                    } catch (e) { variant = ''; }
                }
                uiVariantEl.value = variant === 'custom' ? 'custom' : (styleRaw ? 'premium' : '');
            }
            if (styleRaw) {
                try {
                    var st2 = JSON.parse(styleRaw);
                    if (st2 && st2.bg) (formEl.querySelector('input[name="single_ui_bg"]') || {}).value = st2.bg;
                    if (st2 && st2.text) (formEl.querySelector('input[name="single_ui_text"]') || {}).value = st2.text;
                    if (st2 && st2.border) (formEl.querySelector('input[name="single_ui_border"]') || {}).value = st2.border;
                    if (st2 && st2.hoverBg) (formEl.querySelector('input[name="single_ui_hover_bg"]') || {}).value = st2.hoverBg;
                    if (st2 && st2.hoverText) (formEl.querySelector('input[name="single_ui_hover_text"]') || {}).value = st2.hoverText;
                } catch (e2) { /* ignore */ }
            }
            syncUiAppearanceControls();
            var parentKey = rowBtn.getAttribute('data-parent-key') || '';
            var type = 'principal';
            if (parentKey) {
                var parentIsL2 = state.level2Items.some(function (it) { return it.key === parentKey; });
                type = parentIsL2 ? 'subopcion' : 'submenu';
            }
            setSelectValue(typeEl, type);
            setParentOptionsByType(type);
            if (type === 'submenu') {
                setSelectValue(parentEl, parentKey);
            } else if (type === 'subopcion') {
                var l2 = state.level2Items.find(function (it) { return it.key === parentKey; });
                if (l2) setSelectValue(parentPrincipalEl, l2.parentKey);
                syncSubmenuOptionsForSuboption();
                setSelectValue(parentEl, parentKey);
            }
            renderPreview();
            if (editorTitleEl) editorTitleEl.textContent = t('Editar: ') + state.editingMenuKey;
            setEditorOpen(true);
        }

        function openCreateFor(createType, parentKey, principalKey) {
            resetForm();
            setSelectValue(modeEl, 'single');
            syncCreateMode();

            if (createType === 'submenu') {
                setSelectValue(typeEl, 'submenu');
                setParentOptionsByType('submenu');
                if (parentKey) setSelectValue(parentEl, parentKey);
                if (editorTitleEl) editorTitleEl.textContent = t('Crear submenú');
            } else if (createType === 'subopcion') {
                setSelectValue(typeEl, 'subopcion');
                setParentOptionsByType('subopcion');
                if (principalKey) setSelectValue(parentPrincipalEl, principalKey);
                syncSubmenuOptionsForSuboption();
                if (parentKey) setSelectValue(parentEl, parentKey);
                if (editorTitleEl) editorTitleEl.textContent = t('Crear subopción');
            } else {
                setSelectValue(typeEl, 'principal');
                setParentOptionsByType('principal');
                if (editorTitleEl) editorTitleEl.textContent = t('Crear menú principal');
            }

            renderPreview();
            setEditorOpen(true);
            syncPositionOptions();
        }

        formEl.addEventListener('submit', function (e) {
            e.preventDefault();
            syncUiAppearanceControls();
            var payload = formToPayload();
            var method = state.editingMenuKey ? 'PUT' : 'POST';
            if (state.editingMenuKey) {
                payload.menuKey = state.editingMenuKey;
                payload.label = payload.single_label;
                payload.icon = payload.single_icon;
                payload.sortOrder = payload.single_sort_order;
                payload.positionIndex = payload.single_position_index;
                payload.status = payload.single_status;
                payload.showInSidebar = !!payload.single_show_in_sidebar;
                payload.devOnly = !!payload.single_dev_only;
                payload.parentKey = payload.single_item_type === 'principal'
                    ? null
                    : (payload.single_item_type === 'submenu'
                        ? payload.single_parent_key
                        : payload.single_parent_key);

                var ui = readUiStyleFromForm();
                payload.uiBadge = ui.badge || null;
                payload.uiStyle = ui.style ? JSON.stringify(ui.style) : null;
            }
            request(method, payload).then(function (json) {
                if (!json || !json.success) {
                    alertInfo('error', t('Error'), (json && json.message) || t('No se pudo guardar.'));
                    return;
                }
                applyTree(json.menu_tree || []);
                resetForm();
                setEditorOpen(false);
                alertInfo('success', t('Listo'), json.message || t('Operación completada.'));
            }).catch(function () {
                alertInfo('error', t('Error'), t('No se pudo completar la solicitud.'));
            });
        });

        document.addEventListener('click', function (e) {
            var createBtn = e.target.closest('.js-menu-create-child');
            if (createBtn) {
                openCreateFor(
                    createBtn.getAttribute('data-create-type') || 'principal',
                    createBtn.getAttribute('data-parent-key') || '',
                    createBtn.getAttribute('data-principal-key') || ''
                );
                return;
            }

            var copyBtn = e.target.closest('.js-copy-path');
            if (copyBtn) {
                var copyValue = '';
                var targetSelector = (copyBtn.getAttribute('data-copy-target') || '').trim();
                if (targetSelector) {
                    var copyTarget = document.querySelector(targetSelector);
                    if (copyTarget) {
                        if (typeof copyTarget.value === 'string') copyValue = copyTarget.value.trim();
                        else copyValue = (copyTarget.textContent || '').trim();
                    }
                }
                if (!copyValue) {
                    copyValue = (copyBtn.getAttribute('data-copy') || '').trim();
                }
                if (!copyValue) return;
                var copyPromise;
                if (navigator.clipboard && typeof navigator.clipboard.writeText === 'function') {
                    copyPromise = navigator.clipboard.writeText(copyValue);
                } else {
                    copyPromise = new Promise(function (resolve, reject) {
                        try {
                            var tmp = document.createElement('textarea');
                            tmp.value = copyValue;
                            tmp.setAttribute('readonly', 'readonly');
                            tmp.style.position = 'fixed';
                            tmp.style.opacity = '0';
                            document.body.appendChild(tmp);
                            tmp.select();
                            var ok = document.execCommand('copy');
                            document.body.removeChild(tmp);
                            ok ? resolve() : reject(new Error('copy_failed'));
                        } catch (err) {
                            reject(err);
                        }
                    });
                }
                copyPromise.then(function () {
                    alertInfo('success', t('Copiado'), t('Path copiado: ') + copyValue);
                }).catch(function () {
                    alertInfo('error', t('Error'), t('No se pudo copiar el path.'));
                });
                return;
            }

            var editBtn = e.target.closest('.js-menu-edit');
            if (editBtn) {
                enterEditMode(editBtn);
                return;
            }

            var appearanceBtn = e.target.closest('.js-menu-appearance');
            if (appearanceBtn) {
                openAppearanceEditor(appearanceBtn);
                return;
            }

            var delBtn = e.target.closest('.js-menu-delete');
            if (!delBtn) return;

            var menuKey = delBtn.getAttribute('data-menukey') || '';
            if (!menuKey) return;

            confirmAction(t('Eliminar ítem'), t('Se eliminará') + ' "' + menuKey + '".', t('Sí, eliminar'), t('Cancelar')).then(function (ok) {
                if (!ok) return;
                request('DELETE', { menuKey: menuKey }).then(function (json) {
                    if (!json || !json.success) {
                        alertInfo('error', t('Error'), (json && json.message) || t('No se pudo completar la eliminación.'));
                        return;
                    }
                    applyTree(json.menu_tree || []);
                    if (state.editingMenuKey === menuKey) resetForm();
                    alertInfo('success', t('Eliminado'), json.message || t('Operación completada.'));
                }).catch(function () {
                    alertInfo('error', t('Error'), t('No se pudo completar la eliminación.'));
                });
            });
        });

        document.addEventListener('mouseover', function (e) {
            var actionBtn = e.target.closest('.snapshot-actions button');
            if (!actionBtn) return;
            var row = actionBtn.closest('.snapshot-row');
            if (row) row.classList.add('row-hover');
        });

        document.addEventListener('mouseout', function (e) {
            var actionBtn = e.target.closest('.snapshot-actions button');
            if (!actionBtn) return;
            var row = actionBtn.closest('.snapshot-row');
            if (row) row.classList.remove('row-hover');
        });

        modeEl.addEventListener('change', function () { syncCreateMode(); renderPreview(); });
        typeEl.addEventListener('change', function () {
            setParentOptionsByType(getSelectValue(typeEl) || 'principal');
            if ((getSelectValue(typeEl) || '') === 'subopcion') syncSubmenuOptionsForSuboption();
            renderPreview();
        });
        parentEl.addEventListener('change', renderPreview);
        parentEl.addEventListener('change', syncPositionOptions);
        parentPrincipalEl.addEventListener('change', function () { syncSubmenuOptionsForSuboption(); syncPositionOptions(); renderPreview(); });
        keyEl.addEventListener('input', renderPreview);
        labelEl.addEventListener('input', renderPreview);
        iconEl.addEventListener('change', function () { setIconPreview(getSelectedIconClass()); renderPreview(); });
        if (hasSelect2()) {
            window.jQuery(iconEl)
                .off('.iconPreviewSync')
                .on('select2:select.iconPreviewSync change.iconPreviewSync', function () {
                    setIconPreview(getSelectedIconClass());
                    renderPreview();
                });
        }
        if (uiVariantEl) uiVariantEl.addEventListener('change', syncUiAppearanceControls);
        if (formEl) {
            formEl.addEventListener('input', function (e) {
                if (!e || !e.target) return;
                var n = e.target.name || '';
                if (n.indexOf('single_ui_') === 0) syncUiAppearanceControls();
            });
        }
        if (appearanceVariantEl) appearanceVariantEl.addEventListener('change', syncAppearanceModalUi);
        [
            appearanceBadgeEl,
            appearanceBaseEl,
            appearanceBgEl,
            appearanceTextEl,
            appearanceBorderEl,
            appearanceHoverBgEl,
            appearanceHoverTextEl
        ].forEach(function (el) {
            if (el) el.addEventListener('input', syncAppearanceModalUi);
        });
        if (appearanceAutoDeriveEl) appearanceAutoDeriveEl.addEventListener('change', syncAppearanceModalUi);
        if (appearanceCloseEl) appearanceCloseEl.addEventListener('click', function () { setAppearanceOpen(false); });
        if (appearanceCancelEl) appearanceCancelEl.addEventListener('click', function () { setAppearanceOpen(false); });
        if (appearanceBackdropEl) appearanceBackdropEl.addEventListener('click', function () { setAppearanceOpen(false); });
        if (appearanceColEl) {
            appearanceColEl.addEventListener('mousedown', function (e) {
                var target = e.target;
                if (!target) return;
                var isColorInput = target.matches && target.matches('input[type="color"]');
                if (!isColorInput) {
                    forceCloseColorPickers();
                }
            });
        }
        if (appearanceSaveEl) {
            appearanceSaveEl.addEventListener('click', function () {
                forceCloseColorPickers();
                var menuKey = appearanceKeyEl ? String(appearanceKeyEl.value || '') : '';
                if (!menuKey) return;
                var curr = readAppearanceStyleFromModal();
                request('PUT', {
                    menuKey: menuKey,
                    uiBadge: curr.badge || null,
                    uiStyle: curr.style ? JSON.stringify(curr.style) : null
                }).then(function (json) {
                    if (!json || !json.success) {
                        alertInfo('error', t('Error'), (json && json.message) || t('No se pudo guardar la apariencia.'));
                        return;
                    }
                    applyTree(json.menu_tree || []);
                    setAppearanceOpen(false);
                    alertInfo('success', t('Listo'), t('Apariencia guardada.'));
                }).catch(function () {
                    alertInfo('error', t('Error'), t('No se pudo guardar la apariencia.'));
                });
            });
        }

        // Cierre robusto para color pickers nativos (Firefox/macOS puede dejarlos abiertos).
        document.addEventListener('mousedown', function (e) {
            var active = document.activeElement;
            if (!isColorInputElement(active)) return;
            var target = e.target;
            if (target === active) return;
            if (isColorInputElement(target)) return;
            forceCloseColorPickers();
        }, true);
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                forceCloseColorPickers();
            }
        }, true);
        document.addEventListener('visibilitychange', function () {
            if (document.hidden) forceCloseColorPickers();
        });
        window.addEventListener('blur', function () {
            forceCloseColorPickers();
        });
        positionEl.addEventListener('change', renderPreview);
        bundleKindEl.addEventListener('change', function () { syncBundleKind(); renderPreview(); });
        bundleParentPrincipalSuboptionEl.addEventListener('change', syncBundleSubmenuOptions);
        if (openCreateBtn) {
            openCreateBtn.addEventListener('click', function () {
                openCreateFor('principal', '', '');
            });
        }

        if (importBtn && importFileEl) {
            importBtn.addEventListener('click', function () {
                importFileEl.click();
            });
            importFileEl.addEventListener('change', function () {
                var file = importFileEl.files && importFileEl.files[0] ? importFileEl.files[0] : null;
                if (!file) return;
                var replaceAll = !!(importReplaceEl && importReplaceEl.checked);
                if (replaceAll) {
                    confirmAction(t('Reemplazar todo'), t('Esto borrará el menú actual y lo reemplazará con el JSON. ¿Continuar?'), t('Sí, reemplazar'), t('Cancelar'))
                        .then(function (ok) {
                            if (!ok) return;
                            importJsonFile(file, true);
                        });
                } else {
                    importJsonFile(file, false);
                }
                importFileEl.value = '';
            });
        }
        if (openCreateInlineBtn) {
            openCreateInlineBtn.addEventListener('click', function () {
                openCreateFor('principal', '', '');
            });
        }
        if (closeEditorBtn) {
            closeEditorBtn.addEventListener('click', function () {
                setEditorOpen(false);
            });
        }
        if (cancelBtn) {
            cancelBtn.addEventListener('click', function () {
                setEditorOpen(false);
            });
        }
        if (editorBackdropEl) {
            editorBackdropEl.addEventListener('click', function () {
                setEditorOpen(false);
            });
        }
        if (treeSnapshotEl) {
            treeSnapshotEl.addEventListener('click', function (e) {
                var toggleBtn = e.target.closest('.js-tree-toggle-collapse');
                if (!toggleBtn) return;
                var nodeId = String(toggleBtn.getAttribute('data-node-id') || '');
                if (!nodeId) return;
                if (state.collapsedNodes[nodeId]) {
                    delete state.collapsedNodes[nodeId];
                } else {
                    state.collapsedNodes[nodeId] = true;
                }
                renderTreeSnapshot(state.menuTree, '#menu-tree-snapshot', state.collapsedNodes, state.treeFilterTerm);
                bindTreeSnapshotSortable();
            });
        }
        if (collapseAllBtn) {
            collapseAllBtn.addEventListener('click', function () {
                state.collapsedNodes = {};
                collectCollapsibleNodeIds().forEach(function (id) { state.collapsedNodes[id] = true; });
                renderTreeSnapshot(state.menuTree, '#menu-tree-snapshot', state.collapsedNodes, state.treeFilterTerm);
                bindTreeSnapshotSortable();
            });
        }
        if (expandAllBtn) {
            expandAllBtn.addEventListener('click', function () {
                state.collapsedNodes = {};
                renderTreeSnapshot(state.menuTree, '#menu-tree-snapshot', state.collapsedNodes, state.treeFilterTerm);
                bindTreeSnapshotSortable();
            });
        }
        if (treeSearchEl) {
            treeSearchEl.addEventListener('input', function () {
                state.treeFilterTerm = treeSearchEl.value || '';
                syncTreeSearchClear();
                renderTreeSnapshot(state.menuTree, '#menu-tree-snapshot', state.collapsedNodes, state.treeFilterTerm);
                bindTreeSnapshotSortable();
            });
        }
        if (treeSearchClearEl && treeSearchEl) {
            treeSearchClearEl.addEventListener('click', function () {
                treeSearchEl.value = '';
                treeSearchEl.dispatchEvent(new Event('input', { bubbles: true }));
                treeSearchEl.focus();
            });
        }
        if (cancelOrderBtn) {
            cancelOrderBtn.addEventListener('click', function () {
                state.menuTree = JSON.parse(JSON.stringify(state.persistedMenuTree || []));
                buildItemsFromTree(state.menuTree);
                renderTreeSnapshot(state.menuTree, '#menu-tree-snapshot', state.collapsedNodes, state.treeFilterTerm);
                bindTreeSnapshotSortable();
                updateOrderActionsState();
                syncPositionOptions();
                renderPreview();
            });
        }
        if (saveOrderBtn) {
            saveOrderBtn.addEventListener('click', function () {
                if (!isOrderDirty()) {
                    updateOrderActionsState();
                    return;
                }
                request('PUT', { bulkSort: collectBulkSortPayload(state.menuTree) }).then(function (json) {
                    if (!json || !json.success) {
                        alertInfo('error', t('Error'), (json && json.message) || t('No se pudo guardar el orden.'));
                        return;
                    }
                    applyTree(json.menu_tree || []);
                    alertInfo('success', t('Listo'), json.message || t('Orden guardado.'));
                }).catch(function () {
                    alertInfo('error', t('Error'), t('No se pudo guardar el orden.'));
                });
            });
        }

        initSelect2('#demo-create-mode', { minimumResultsForSearch: Infinity });
        initSelect2('#demo-item-type', { minimumResultsForSearch: Infinity });
        initSelect2('#single-status', { minimumResultsForSearch: Infinity });
        initSelect2('#bundle-kind', { minimumResultsForSearch: Infinity });
        initSelect2('#bundle-parent-principal', { minimumResultsForSearch: 8 });
        initSelect2('#bundle-parent-principal-suboption', { minimumResultsForSearch: 8 });
        initSelect2('#bundle-parent-submenu', { minimumResultsForSearch: 8 });
        initSelect2('#demo-parent-principal', { minimumResultsForSearch: 8 });
        initSelect2('#demo-parent-key', { minimumResultsForSearch: 8 });
        initSelect2('#demo-position-index', { minimumResultsForSearch: Infinity });

        if (window.GrovaIconLib && typeof window.GrovaIconLib.loadAllIcons === 'function') {
            window.GrovaIconLib.loadAllIcons().then(function (icons) {
                iconEl.innerHTML = (icons || []).map(function (iconItem) {
                    var iconName = iconItem.shortName || iconItem.className || iconItem.value;
                    var library = iconLibraryLabel(iconItem.set || iconItem.value);
                    var searchTags = window.GrovaIconLib.normalizeText(
                        window.GrovaIconLib.buildIconTags(iconItem.value, iconItem.set || 'bi').join(' ')
                    );
                    return '<option value="' + esc(iconItem.value) + '" data-icon-class="' + esc(iconItem.className || iconItem.value) + '" data-icon-name="' + esc(iconName) + '" data-icon-library="' + esc(library) + '" data-search-tags="' + esc(searchTags) + '">' + esc(iconName + ' - ' + library) + '</option>';
                }).join('');
                if (!iconEl.innerHTML) iconEl.innerHTML = '<option value="bi-list">bi-list</option><option value="bi-gear">bi-gear</option>';
                initIconSelect2();
                setSelectValue(iconEl, 'bi-list');
                setIconPreview(getSelectedIconClass());
                if (editorColEl && editorColEl.classList.contains('open')) {
                    window.requestAnimationFrame(refreshModalIconSelect2);
                }
            });
        } else {
            iconEl.innerHTML = '<option value="bi-list" data-icon-class="bi-list" data-icon-name="list" data-icon-library="Bootstrap Icons">list - Bootstrap Icons</option><option value="bi-gear" data-icon-class="bi-gear" data-icon-name="gear" data-icon-library="Bootstrap Icons">gear - Bootstrap Icons</option><option value="bi-hammer" data-icon-class="bi-hammer" data-icon-name="hammer" data-icon-library="Bootstrap Icons">hammer - Bootstrap Icons</option>';
            initIconSelect2();
            setSelectValue(iconEl, 'bi-list');
            setIconPreview(getSelectedIconClass());
            if (editorColEl && editorColEl.classList.contains('open')) {
                window.requestAnimationFrame(refreshModalIconSelect2);
            }
        }

        buildItemsFromTree(state.menuTree);
        resetForm();
        setEditorOpen(false);
        syncCreateMode();
        syncBundleKind();
        setParentOptionsByType(getSelectValue(typeEl) || 'submenu');
        syncPositionOptions();
        renderTreeSnapshot(state.menuTree, '#menu-tree-snapshot', state.collapsedNodes, state.treeFilterTerm);
        bindTreeSnapshotSortable();
        updateOrderActionsState();
        syncTreeSearchClear();
        renderPreview();
    }

    return { init: init };
})();
