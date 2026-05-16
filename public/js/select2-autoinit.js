/**
 * grova — Select2 auto-init
 *
 * Cualquier <select class="grova-select2"> se inicializa automáticamente.
 * El dev no necesita llamar a $().select2() manualmente.
 *
 * Opciones configurables via data-attributes:
 *   data-placeholder   → texto del placeholder
 *   data-allow-clear   → "true" para mostrar botón de limpiar
 *   data-minimum-input → mínimo de caracteres para buscar (default: 0)
 *   data-tags          → "true" para permitir crear opciones nuevas
 *
 * Uso mínimo:
 *   <select class="grova-select2" data-placeholder="Seleccionar...">
 *
 * Uso con opciones:
 *   <select class="grova-select2" multiple data-placeholder="Elegir etiquetas..." data-allow-clear="true">
 */
(function ($) {
    'use strict';

    function initSelect2(el, forceReinit) {
        var $el = $(el);

        if ($el.data('select2')) {
            if (!forceReinit) {
                return;
            }
            $el.select2('destroy');
        }

        var $filterPanel = $el.closest('#filters-panel');
        var dropdownParent = $el.data('dropdown-parent')
            ? $($el.data('dropdown-parent'))
            : ($filterPanel.length ? $filterPanel : $(document.body));
        var inFilterPanel = $filterPanel.length > 0;

        var options = {
            theme:            'bootstrap-5',
            placeholder:      $el.data('placeholder')    || '',
            allowClear:       $el.data('allow-clear')    === true || $el.data('allow-clear') === 'true',
            minimumInputLength: parseInt($el.data('minimum-input') || '0', 10),
            tags:             $el.data('tags')           === true || $el.data('tags') === 'true',
            width:            '100%',
            dropdownParent:   dropdownParent,
        };

        if (inFilterPanel) {
            options.dropdownCssClass = 'grova-filter-select2-dropdown';
            options.selectionCssClass = 'grova-filter-select2-selection';
        }

        $el.select2(options);

        if (inFilterPanel) {
            $el.off('.grovaFilterSelect2');
            $el.on('select2:open.grovaFilterSelect2', function () {
                document.body.classList.add('grova-filters-select2-open');
                var $dd = $('.select2-dropdown.grova-filter-select2-dropdown').last();
                if (!$dd.length) {
                    $dd = $('.select2-dropdown').last();
                    $dd.addClass('grova-filter-select2-dropdown');
                }
            });
            $el.on('select2:close.grovaFilterSelect2 select2:closing.grovaFilterSelect2', function () {
                window.setTimeout(function () {
                    if (!$('.select2-container--open').length) {
                        document.body.classList.remove('grova-filters-select2-open');
                    }
                }, 0);
            });
        }
    }

    function destroyAll(context) {
        var scope = context || document;
        $(scope).find('select.grova-select2').each(function () {
            var $el = $(this);
            if ($el.data('select2')) {
                $el.select2('destroy');
            }
        });
    }

    function initAll(context) {
        var scope = context || document;
        $(scope).find('select.grova-select2').each(function () {
            initSelect2(this);
        });
    }

    // Inicializar al cargar el DOM
    $(function () {
        initAll();
    });

    // Re-inicializar selects dentro de modales cuando se muestran
    // (el portal a body ocurre en show.bs.modal, antes de shown.bs.modal)
    $(document).on('shown.bs.modal', function (e) {
        initAll(e.target);
    });

    // Re-inicializar selects del panel (tema bootstrap-5 + dropdown dentro del panel)
    $(document).on('grova:filters-opened', function () {
        var panel = document.getElementById('filters-panel');
        if (!panel) return;
        destroyAll(panel);
        panel.querySelectorAll('select.grova-select2').forEach(function (sel) {
            initSelect2(sel, true);
        });
    });

    // API pública — útil para inicializar selects añadidos dinámicamente
    window.GrovaSelect2 = {
        init:       initSelect2,
        initAll:    initAll,
        destroyAll: destroyAll,
    };

}(jQuery));
