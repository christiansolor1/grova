/**
 * Doble scroll horizontal (Grova).
 * DT2 + Bootstrap 5: las filas son .row (no siempre .dt-layout-row).
 * Orden: [toolbar] → [shell: scroll-top + tabla + scroll-foot] → [pie info/paginación]
 */
(function (global) {
    'use strict';

    function queryLayoutRows(container) {
        return container.querySelectorAll(':scope > .dt-layout-row, :scope > .row');
    }

    function findTableLayoutRow(container) {
        return container.querySelector(':scope > .dt-layout-table')
            || container.querySelector(':scope > .row.dt-layout-table')
            || container.querySelector('.dt-layout-table');
    }

    function findFooterLayoutRow(container) {
        var rows = queryLayoutRows(container);
        for (var i = rows.length - 1; i >= 0; i--) {
            var row = rows[i];
            if (row.classList.contains('dt-layout-table')) {
                continue;
            }
            if (row.querySelector('.dt-info, .dt-paging, .dataTables_info, .dataTables_paginate')) {
                return row;
            }
        }
        for (var j = rows.length - 1; j >= 0; j--) {
            if (!rows[j].classList.contains('dt-layout-table')) {
                return rows[j];
            }
        }
        return null;
    }

    function findTableHost(container, tableEl) {
        var layoutRow = findTableLayoutRow(container);
        if (layoutRow) {
            return layoutRow.querySelector('.dt-layout-full')
                || layoutRow.querySelector('.dt-layout-cell')
                || layoutRow;
        }
        var node = tableEl.parentElement;
        while (node && node !== container) {
            if (node.classList.contains('dt-layout-full')
                || node.classList.contains('dt-layout-cell')
                || node.classList.contains('table-responsive')) {
                return node;
            }
            node = node.parentElement;
        }
        return tableEl.parentElement;
    }

    function createTopBar() {
        var top = document.createElement('div');
        top.className = 'grova-dt-scroll-top is-hidden';
        top.setAttribute('aria-hidden', 'true');
        var inner = document.createElement('div');
        inner.className = 'grova-dt-scroll-top-inner';
        top.appendChild(inner);
        return top;
    }

    function createFootBar() {
        var foot = document.createElement('div');
        foot.className = 'grova-dt-scroll-foot is-hidden';
        foot.setAttribute('aria-hidden', 'true');
        var inner = document.createElement('div');
        inner.className = 'grova-dt-scroll-foot-inner';
        foot.appendChild(inner);
        return foot;
    }

    function createBodyScroller() {
        var body = document.createElement('div');
        body.className = 'grova-dt-scroll-body';
        return body;
    }

    function createScrollShell() {
        var shell = document.createElement('div');
        shell.className = 'grova-dt-scroll-shell';
        return shell;
    }

    function migrateLegacyScroller(host, tableEl) {
        var legacy = host.querySelector(
            '.grova-dt-scroll-bottom, .work-invoice-table-wrap, #work-inv-scroll-bottom'
        );
        if (!legacy) {
            return null;
        }
        legacy.classList.remove(
            'grova-dt-scroll-bottom',
            'table-responsive',
            'work-invoice-table-wrap'
        );
        legacy.classList.add('grova-dt-scroll-body');
        if (tableEl && tableEl.parentElement !== legacy && !legacy.contains(tableEl)) {
            legacy.appendChild(tableEl);
        }
        return legacy;
    }

    function removeStrayFootBars(container, keepFoot) {
        container.querySelectorAll('.grova-dt-scroll-foot').forEach(function (el) {
            if (el !== keepFoot) {
                el.remove();
            }
        });
    }

    function ensureScrollShell(container, tableRow, foot, footerRow) {
        var shell = container.querySelector(':scope > .grova-dt-scroll-shell');
        if (!shell) {
            shell = createScrollShell();
            if (footerRow) {
                container.insertBefore(shell, footerRow);
            } else if (tableRow && tableRow.parentElement === container) {
                container.insertBefore(shell, tableRow);
            } else {
                container.appendChild(shell);
            }
        } else if (footerRow && shell.nextElementSibling !== footerRow) {
            container.insertBefore(shell, footerRow);
        }

        if (tableRow && tableRow.parentElement !== shell) {
            shell.appendChild(tableRow);
        }

        if (foot.parentElement) {
            foot.remove();
        }
        if (tableRow) {
            tableRow.insertAdjacentElement('afterend', foot);
        } else {
            shell.appendChild(foot);
        }

        return shell;
    }

    function ensureStructure(tableEl) {
        var container = tableEl.closest('.dt-container') || tableEl.closest('.dataTables_wrapper');
        if (!container) {
            return null;
        }

        var tableRow = findTableLayoutRow(container);
        var footerRow = findFooterLayoutRow(container);
        var host = findTableHost(container, tableEl);
        if (!host) {
            return null;
        }

        host.querySelectorAll('.grova-dt-scroll-foot').forEach(function (el) {
            el.remove();
        });

        var top = host.querySelector(':scope > .grova-dt-scroll-top')
            || host.querySelector('.grova-dt-scroll-top');
        if (!top) {
            top = createTopBar();
            host.insertBefore(top, host.firstChild);
        }

        var body = host.querySelector(':scope > .grova-dt-scroll-body')
            || host.querySelector('.grova-dt-scroll-body');
        if (!body) {
            body = migrateLegacyScroller(host, tableEl) || createBodyScroller();
            if (!body.parentElement) {
                host.appendChild(body);
            }
        }

        if (tableEl && !body.contains(tableEl)) {
            body.appendChild(tableEl);
        }

        var foot = container.querySelector('.grova-dt-scroll-shell .grova-dt-scroll-foot')
            || container.querySelector(':scope > .grova-dt-scroll-foot');
        if (!foot) {
            foot = createFootBar();
        }

        removeStrayFootBars(container, foot);
        ensureScrollShell(container, tableRow, foot, footerRow);

        return {
            container: container,
            host: host,
            top: top,
            body: body,
            foot: foot,
            tableRow: tableRow,
            footerRow: footerRow,
        };
    }

    function bindDualScroll(tableEl) {
        if (!tableEl || tableEl.dataset.grovaDualScroll === 'off') {
            return null;
        }

        var parts = ensureStructure(tableEl);
        if (!parts) {
            return null;
        }

        var top = parts.top;
        var body = parts.body;
        var foot = parts.foot;
        var container = parts.container;
        var topInner = top.querySelector('.grova-dt-scroll-top-inner');
        var footInner = foot.querySelector('.grova-dt-scroll-foot-inner');
        var syncing = false;

        function contentWidth(tbl) {
            if (!tbl) {
                return 0;
            }
            return Math.max(
                tbl.scrollWidth || 0,
                tbl.offsetWidth || 0,
                tbl.getBoundingClientRect().width || 0
            );
        }

        function viewportWidth() {
            var w = body.clientWidth || 0;
            if (w > 2) {
                return w;
            }
            w = container.clientWidth || 0;
            if (w > 2) {
                var s = window.getComputedStyle(container);
                w -= (parseFloat(s.paddingLeft) || 0) + (parseFloat(s.paddingRight) || 0);
            }
            return w || 0;
        }

        function measure() {
            var latest = ensureStructure(tableEl);
            if (!latest) {
                return;
            }
            top = latest.top;
            body = latest.body;
            foot = latest.foot;
            topInner = top.querySelector('.grova-dt-scroll-top-inner');
            footInner = foot.querySelector('.grova-dt-scroll-foot-inner');

            var tbl = body.querySelector('table.dataTable') || body.querySelector('table') || tableEl;
            var sw = contentWidth(tbl);
            var cw = viewportWidth();
            var need = sw > cw + 2;

            top.classList.toggle('is-hidden', !need);
            foot.classList.toggle('is-hidden', !need);
            top.setAttribute('aria-hidden', need ? 'false' : 'true');
            foot.setAttribute('aria-hidden', need ? 'false' : 'true');

            var innerW = need ? Math.ceil(sw) + 'px' : '0px';
            if (topInner) {
                topInner.style.width = innerW;
            }
            if (footInner) {
                footInner.style.width = innerW;
            }
        }

        function layout() {
            requestAnimationFrame(function () {
                requestAnimationFrame(measure);
            });
        }

        function syncAll(source) {
            if (syncing) {
                return;
            }
            syncing = true;
            var x = source.scrollLeft;
            if (source !== top) {
                top.scrollLeft = x;
            }
            if (source !== body) {
                body.scrollLeft = x;
            }
            if (source !== foot) {
                foot.scrollLeft = x;
            }
            syncing = false;
        }

        function onWheel(e) {
            if (body.scrollWidth <= body.clientWidth + 2) {
                return;
            }
            var dx = e.deltaX;
            var dy = e.deltaY;
            if (Math.abs(dx) > Math.abs(dy) || dy === 0) {
                return;
            }
            e.preventDefault();
            body.scrollLeft += dy;
            syncAll(body);
        }

        container.__grovaDtRelayout = measure;

        if (container.dataset.grovaDualScrollInit === '1') {
            layout();
            return container;
        }
        container.dataset.grovaDualScrollInit = '1';
        container.setAttribute('data-grova-dual-scroll', '1');

        var outerWrap = container.parentElement;
        if (outerWrap && outerWrap !== container && outerWrap.hasAttribute('data-grova-dual-scroll')) {
            outerWrap.removeAttribute('data-grova-dual-scroll');
        }

        top.addEventListener('scroll', function () { syncAll(top); }, { passive: true });
        body.addEventListener('scroll', function () { syncAll(body); }, { passive: true });
        foot.addEventListener('scroll', function () { syncAll(foot); }, { passive: true });
        body.addEventListener('wheel', onWheel, { passive: false });

        layout();
        window.addEventListener('load', layout, { passive: true });

        if (typeof ResizeObserver !== 'undefined') {
            var ro = new ResizeObserver(layout);
            ro.observe(body);
            var tbl = body.querySelector('table') || tableEl;
            if (tbl) {
                ro.observe(tbl);
            }
            ro.observe(container);
        }
        window.addEventListener('resize', layout, { passive: true });

        return container;
    }

    function relayout(tableEl) {
        if (!tableEl) {
            return;
        }
        var container = tableEl.closest('.dt-container') || tableEl.closest('.dataTables_wrapper');
        if (container && typeof container.__grovaDtRelayout === 'function') {
            container.__grovaDtRelayout();
            return;
        }
        bindDualScroll(tableEl);
    }

    global.GrovaDataTableDualScroll = {
        attach: bindDualScroll,
        relayout: relayout,
    };
})(typeof window !== 'undefined' ? window : this);
