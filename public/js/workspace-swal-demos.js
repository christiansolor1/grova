function setSwalResult(message) {
    const resultEl = document.getElementById('swal-dev-result');
    if (resultEl) resultEl.textContent = message;
}

function swalDestroySelect2(selector) {
    if (typeof $ === 'undefined' || !$.fn || !$.fn.select2) return;
    const el = document.querySelector(selector);
    if (el && $(el).data('select2')) {
        $(el).select2('destroy');
    }
}

function swalInitSelect2(selector, opts) {
    if (typeof $ === 'undefined' || !$.fn || !$.fn.select2) return;
    const $el = $(selector);
    if (!$el.length) return;
    $el.select2(Object.assign({
        theme: 'bootstrap-5',
        width: '100%',
        dropdownParent: $(Swal.getHtmlContainer()),
    }, opts || {}));
}

function swalReadMultiNative(selector) {
    const el = document.querySelector(selector);
    if (!el) return [];
    return Array.from(el.selectedOptions || []).map(function (opt) {
        return opt.value || opt.text;
    }).filter(Boolean);
}

function swalFormatMulti(values) {
    if (!values || !values.length) return '(ninguno)';
    return values.join(', ');
}

async function runSwalDemo(type) {
    if (typeof Swal === 'undefined') return;

    switch (type) {
        case 'success':
            await Swal.fire({ icon: 'success', title: 'Operacion completada', text: 'Se guardaron los datos correctamente.' });
            setSwalResult('Ejecutado: Exito simple.');
            break;
        case 'info':
            await Swal.fire({ icon: 'info', title: 'Informacion', text: 'Este mensaje puede usarse para contexto adicional.' });
            setSwalResult('Ejecutado: Informacion.');
            break;
        case 'warning':
            await Swal.fire({ icon: 'warning', title: 'Atencion', text: 'Revisa los datos antes de continuar.' });
            setSwalResult('Ejecutado: Advertencia.');
            break;
        case 'error':
            await Swal.fire({ icon: 'error', title: 'Error', text: 'Ocurrio un problema al procesar la solicitud.' });
            setSwalResult('Ejecutado: Error.');
            break;
        case 'question':
            await Swal.fire({ icon: 'question', title: 'Pregunta', text: 'Quieres revisar los detalles ahora?' });
            setSwalResult('Ejecutado: Pregunta.');
            break;
        case 'html':
            await Swal.fire({
                icon: 'info',
                title: 'Contenido HTML',
                html: '<b>Negrita</b>, <i>italica</i>, <u>subrayado</u><br><small>Con HTML custom</small>',
                confirmButtonText: 'Entendido',
            });
            setSwalResult('Ejecutado: HTML custom.');
            break;
        case 'confirm': {
            const response = await Swal.fire({
                title: 'Deseas continuar?',
                text: 'Esta accion requiere confirmacion.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Aceptar',
                cancelButtonText: 'Cancelar',
            });
            setSwalResult(response.isConfirmed ? 'Confirmado: Aceptar.' : 'Cancelado por el usuario.');
            break;
        }
        case 'confirmDelete': {
            const response = await Swal.fire({
                title: 'Eliminar registro?',
                text: 'No podras revertir esta accion.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Si, eliminar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#dc3545',
            });
            setSwalResult(response.isConfirmed ? 'Confirmado: Eliminar.' : 'Eliminacion cancelada.');
            break;
        }
        case 'input': {
            const response = await Swal.fire({
                title: 'Ingresa un nombre',
                input: 'text',
                inputLabel: 'Nombre',
                inputPlaceholder: 'Ej: Juan Perez',
                showCancelButton: true,
                confirmButtonText: 'Guardar',
                cancelButtonText: 'Cancelar',
            });
            if (response.isConfirmed) {
                setSwalResult(`Dato capturado: ${response.value || '(vacio)'}`);
            } else {
                setSwalResult('Ingreso de texto cancelado.');
            }
            break;
        }
        case 'email': {
            const response = await Swal.fire({
                title: 'Ingresa tu email',
                input: 'email',
                inputLabel: 'Email',
                inputPlaceholder: 'usuario@dominio.com',
                showCancelButton: true,
                confirmButtonText: 'Validar',
                cancelButtonText: 'Cancelar',
                inputValidator: (value) => {
                    if (!value) return 'El email es obligatorio';
                    if (!value.includes('@')) return 'Email invalido';
                    return null;
                }
            });
            if (response.isConfirmed) {
                setSwalResult(`Email validado: ${response.value}`);
            } else {
                setSwalResult('Validacion de email cancelada.');
            }
            break;
        }
        case 'number': {
            const response = await Swal.fire({
                title: 'Ingresa una cantidad',
                input: 'number',
                inputLabel: 'Cantidad',
                inputPlaceholder: 'Ej: 10',
                inputAttributes: { min: '1', step: '1' },
                showCancelButton: true,
                confirmButtonText: 'Guardar',
                inputValidator: (value) => {
                    if (!value) return 'La cantidad es obligatoria';
                    if (Number(value) <= 0) return 'Debe ser mayor a 0';
                    return null;
                }
            });
            setSwalResult(response.isConfirmed ? `Cantidad capturada: ${response.value}` : 'Input numero cancelado.');
            break;
        }
        case 'password': {
            const response = await Swal.fire({
                title: 'Ingresa la clave',
                input: 'password',
                inputLabel: 'Password',
                inputPlaceholder: 'Minimo 6 caracteres',
                showCancelButton: true,
                confirmButtonText: 'Validar',
                inputValidator: (value) => {
                    if (!value || value.length < 6) return 'Minimo 6 caracteres';
                    return null;
                }
            });
            setSwalResult(response.isConfirmed ? 'Password validado.' : 'Input password cancelado.');
            break;
        }
        case 'textarea': {
            const response = await Swal.fire({
                title: 'Comentarios',
                input: 'textarea',
                inputLabel: 'Descripcion',
                inputPlaceholder: 'Escribe un comentario...',
                inputAttributes: { 'aria-label': 'comentario' },
                showCancelButton: true,
                confirmButtonText: 'Guardar'
            });
            setSwalResult(response.isConfirmed ? `Textarea: ${response.value || '(vacio)'}` : 'Textarea cancelado.');
            break;
        }
        case 'select': {
            const response = await Swal.fire({
                title: 'Selecciona un estado',
                input: 'select',
                inputOptions: {
                    activo: 'Activo',
                    inactivo: 'Inactivo',
                    pendiente: 'Pendiente'
                },
                inputPlaceholder: 'Selecciona una opcion',
                showCancelButton: true,
            });
            setSwalResult(response.isConfirmed ? `Select elegido: ${response.value}` : 'Select cancelado.');
            break;
        }
        case 'radio': {
            const response = await Swal.fire({
                title: 'Selecciona prioridad',
                input: 'radio',
                inputOptions: {
                    baja: 'Baja',
                    media: 'Media',
                    alta: 'Alta'
                },
                showCancelButton: true,
                inputValidator: (value) => (!value ? 'Debes elegir una opcion' : null)
            });
            setSwalResult(response.isConfirmed ? `Radio elegido: ${response.value}` : 'Radio cancelado.');
            break;
        }
        case 'checkbox': {
            const response = await Swal.fire({
                title: 'Aceptar terminos',
                input: 'checkbox',
                inputValue: 1,
                inputPlaceholder: 'Acepto terminos y condiciones',
                showCancelButton: true,
                inputValidator: (result) => (!result ? 'Debes aceptar para continuar' : null)
            });
            setSwalResult(response.isConfirmed ? 'Checkbox aceptado.' : 'Checkbox cancelado.');
            break;
        }
        case 'range': {
            const response = await Swal.fire({
                title: 'Nivel de satisfaccion',
                input: 'range',
                inputLabel: 'Valor (1 a 10)',
                inputAttributes: { min: 1, max: 10, step: 1 },
                inputValue: 7,
                showCancelButton: true
            });
            setSwalResult(response.isConfirmed ? `Range elegido: ${response.value}` : 'Range cancelado.');
            break;
        }
        case 'file': {
            const response = await Swal.fire({
                title: 'Sube un archivo',
                input: 'file',
                inputAttributes: {
                    accept: '.pdf,.png,.jpg,.jpeg',
                    'aria-label': 'Sube tu archivo'
                },
                showCancelButton: true,
            });
            if (response.isConfirmed && response.value) {
                setSwalResult(`Archivo seleccionado: ${response.value.name}`);
            } else {
                setSwalResult('Input file cancelado.');
            }
            break;
        }
        case 'mixin': {
            const baseSwal = Swal.mixin({
                confirmButtonText: 'Continuar',
                cancelButtonText: 'Cancelar',
                showCancelButton: true,
                reverseButtons: true,
            });
            const response = await baseSwal.fire({
                icon: 'question',
                title: 'Ejemplo usando mixin',
                text: 'Util para estandarizar botones y estilos.',
            });
            setSwalResult(response.isConfirmed ? 'Mixin: confirmado.' : 'Mixin: cancelado.');
            break;
        }
        case 'threeActions': {
            const response = await Swal.fire({
                title: 'Selecciona una accion',
                text: 'Ejemplo con tres opciones.',
                icon: 'question',
                showCancelButton: true,
                showDenyButton: true,
                confirmButtonText: 'Aceptar',
                denyButtonText: 'Negar',
                cancelButtonText: 'Cancelar',
            });
            if (response.isConfirmed) setSwalResult('Accion: Aceptar.');
            else if (response.isDenied) setSwalResult('Accion: Negar.');
            else setSwalResult('Accion: Cancelar.');
            break;
        }
        case 'queue': {
            // Flujo por pasos compatible en todas las versiones recientes
            const wizard = Swal.mixin({
                progressSteps: ['1', '2', '3'],
                showCancelButton: true,
                cancelButtonText: 'Cancelar',
                reverseButtons: true,
            });

            const step1 = await wizard.fire({
                title: 'Paso 1',
                text: 'Datos basicos',
                input: 'text',
                inputPlaceholder: 'Nombre',
                confirmButtonText: 'Siguiente',
                currentProgressStep: 0,
                inputValidator: (value) => (!value ? 'Ingresa un nombre' : null),
            });
            if (!step1.isConfirmed) {
                setSwalResult('Queue cancelada en paso 1.');
                break;
            }

            const step2 = await wizard.fire({
                title: 'Paso 2',
                text: 'Datos de contacto',
                input: 'email',
                inputPlaceholder: 'Email',
                confirmButtonText: 'Siguiente',
                currentProgressStep: 1,
                inputValidator: (value) => {
                    if (!value) return 'Ingresa un email';
                    if (!String(value).includes('@')) return 'Email invalido';
                    return null;
                },
            });
            if (!step2.isConfirmed) {
                setSwalResult('Queue cancelada en paso 2.');
                break;
            }

            const step3 = await wizard.fire({
                title: 'Paso 3',
                text: 'Confirmacion final',
                icon: 'question',
                confirmButtonText: 'Finalizar',
                currentProgressStep: 2,
            });
            if (!step3.isConfirmed) {
                setSwalResult('Queue cancelada en paso 3.');
                break;
            }

            setSwalResult(`Queue completada: nombre=${step1.value}, email=${step2.value}`);
            break;
        }
        case 'async': {
            const response = await Swal.fire({
                title: 'Ejecutar proceso?',
                text: 'Simula una llamada async.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Ejecutar',
                cancelButtonText: 'Cancelar',
                showLoaderOnConfirm: true,
                preConfirm: async () => {
                    await new Promise((resolve) => setTimeout(resolve, 1200));
                    return { status: 'ok', code: 'SWAL-2026' };
                },
                allowOutsideClick: () => !Swal.isLoading()
            });
            if (response.isConfirmed) {
                setSwalResult(`Proceso async completado (${response.value.code}).`);
            } else {
                setSwalResult('Proceso async cancelado.');
            }
            break;
        }
        case 'toast':
            await Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: 'Registro actualizado',
                showConfirmButton: false,
                timer: 2200,
                timerProgressBar: true,
            });
            setSwalResult('Ejecutado: Toast de notificacion.');
            break;
        case 'toastActions': {
            const Toast = Swal.mixin({
                toast: true,
                position: 'bottom-end',
                showConfirmButton: true,
                showCancelButton: true,
                confirmButtonText: 'Ver',
                cancelButtonText: 'Cerrar',
                timer: 7000,
                timerProgressBar: true,
            });
            const response = await Toast.fire({
                icon: 'info',
                title: 'Tienes una notificacion pendiente'
            });
            if (response.isConfirmed) setSwalResult('Toast con acciones: Ver.');
            else if (response.dismiss) setSwalResult('Toast con acciones: Cerrado o expiro.');
            break;
        }
        case 'timer': {
            let timerInterval;
            await Swal.fire({
                title: 'Cerrando automaticamente...',
                html: 'Se cerrara en <b></b> ms.',
                timer: 2500,
                timerProgressBar: true,
                didOpen: () => {
                    Swal.showLoading();
                    const b = Swal.getHtmlContainer()?.querySelector('b');
                    timerInterval = setInterval(() => {
                        if (b) b.textContent = String(Swal.getTimerLeft());
                    }, 100);
                },
                willClose: () => clearInterval(timerInterval),
            });
            setSwalResult('Ejecutado: Auto close con timer.');
            break;
        }
        case 'loader':
            Swal.fire({
                title: 'Procesando...',
                html: 'No cierres esta ventana.',
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => Swal.showLoading()
            });
            setTimeout(() => {
                Swal.close();
                setSwalResult('Loader bloqueante finalizado.');
            }, 1800);
            break;
        case 'position': {
            await Swal.fire({
                position: 'top-start',
                icon: 'success',
                title: 'Alerta en top-start',
                showConfirmButton: false,
                timer: 1800,
            });
            setSwalResult('Ejecutado: Posicion top-start.');
            break;
        }
        case 'customClass':
            await Swal.fire({
                title: 'Clases personalizadas',
                text: 'Este ejemplo aplica clases CSS en popup y boton.',
                icon: 'info',
                customClass: {
                    popup: 'border border-primary',
                    confirmButton: 'btn btn-primary',
                },
                buttonsStyling: false,
            });
            setSwalResult('Ejecutado: customClass + buttonsStyling false.');
            break;
        case 'image':
            await Swal.fire({
                title: 'Imagen en alerta',
                text: 'Puedes usar branding o ilustraciones.',
                imageUrl: 'https://sweetalert2.github.io/images/trees.png',
                imageWidth: 280,
                imageHeight: 140,
                imageAlt: 'Imagen de ejemplo',
            });
            setSwalResult('Ejecutado: Alerta con imagen.');
            break;
        case 'select2': {
            const response = await Swal.fire({
                title: 'Asignar módulo',
                html:
                    '<label class="form-label" style="text-align:left;display:block;margin-bottom:4px;">Módulo</label>' +
                    '<select id="swal-module-select" style="width:100%">' +
                    '<option value=""></option>' +
                    '<option value="facturacion">Facturación</option>' +
                    '<option value="inventario">Inventario</option>' +
                    '<option value="rrhh">Recursos Humanos</option>' +
                    '<option value="legal">Legal</option>' +
                    '<option value="construccion">Construcción</option>' +
                    '</select>',
                didOpen: function () {
                    // Inicializar Select2 con dropdownParent apuntando al contenedor del Swal
                    // para que el dropdown quede dentro del stacking context del popup.
                    if (typeof $ !== 'undefined' && $.fn && $.fn.select2) {
                        $('#swal-module-select').select2({
                            placeholder: 'Seleccionar módulo...',
                            allowClear: true,
                            width: '100%',
                            dropdownParent: $(Swal.getHtmlContainer()),
                        });
                        // Forzar foco al select2 para que no quede en el input nativo
                        setTimeout(function () {
                            $('#swal-module-select').select2('open');
                            $('#swal-module-select').select2('close');
                        }, 50);
                    }
                },
                preConfirm: function () {
                    var val = $('#swal-module-select').val();
                    if (!val) {
                        Swal.showValidationMessage('Selecciona un módulo para continuar');
                        return false;
                    }
                    return val;
                },
                showCancelButton: true,
                confirmButtonText: 'Asignar',
                cancelButtonText: 'Cancelar',
                willClose: function () {
                    // Destruir instancia Select2 antes de que Swal limpie el DOM
                    if (typeof $ !== 'undefined' && $.fn && $.fn.select2) {
                        var el = document.getElementById('swal-module-select');
                        if (el && $(el).data('select2')) {
                            $(el).select2('destroy');
                        }
                    }
                },
            });
            setSwalResult(response.isConfirmed ? 'Módulo asignado: ' + response.value : 'Asignación cancelada.');
            break;
        }
        case 'selectMultiNative': {
            const response = await Swal.fire({
                title: 'Filtrar por regiones',
                html:
                    '<div class="grova-swal-filter-fields text-start">' +
                    '<label class="form-label">Regiones (select nativo múltiple)</label>' +
                    '<select id="swal-regions-multi" class="form-select form-select-sm grova-filter-select-native-multi" multiple style="width:100%;min-height:96px">' +
                    '<option value="norte">Norte</option>' +
                    '<option value="centro">Centro</option>' +
                    '<option value="sur">Sur</option>' +
                    '<option value="occidente">Occidente</option>' +
                    '<option value="oriente">Oriente</option>' +
                    '</select>' +
                    '<small class="text-muted d-block mt-2">Mantén Ctrl/Cmd para elegir varias opciones.</small>' +
                    '</div>',
                preConfirm: function () {
                    const values = swalReadMultiNative('#swal-regions-multi');
                    if (!values.length) {
                        Swal.showValidationMessage('Selecciona al menos una región');
                        return false;
                    }
                    return values;
                },
                showCancelButton: true,
                confirmButtonText: 'Aplicar',
                cancelButtonText: 'Cancelar',
            });
            setSwalResult(
                response.isConfirmed
                    ? 'Regiones: ' + swalFormatMulti(response.value)
                    : 'Filtro de regiones cancelado.'
            );
            break;
        }
        case 'select2Multi': {
            const response = await Swal.fire({
                title: 'Módulos activos',
                html:
                    '<div class="grova-swal-filter-fields text-start">' +
                    '<label class="form-label">Módulos (Select2 múltiple)</label>' +
                    '<select id="swal-modules-multi" class="grova-select2 form-select form-select-sm" multiple style="width:100%">' +
                    '<option value="facturacion">Facturación</option>' +
                    '<option value="inventario">Inventario</option>' +
                    '<option value="rrhh">Recursos Humanos</option>' +
                    '<option value="legal">Legal</option>' +
                    '<option value="construccion">Construcción</option>' +
                    '<option value="wallet">Wallet</option>' +
                    '<option value="pesca">Pesca</option>' +
                    '</select>' +
                    '</div>',
                didOpen: function () {
                    swalInitSelect2('#swal-modules-multi', {
                        placeholder: 'Elegir módulos...',
                        allowClear: true,
                        closeOnSelect: false,
                    });
                },
                preConfirm: function () {
                    const values = $('#swal-modules-multi').val();
                    if (!values || !values.length) {
                        Swal.showValidationMessage('Selecciona al menos un módulo');
                        return false;
                    }
                    return values;
                },
                showCancelButton: true,
                confirmButtonText: 'Aplicar',
                cancelButtonText: 'Cancelar',
                willClose: function () {
                    swalDestroySelect2('#swal-modules-multi');
                },
            });
            setSwalResult(
                response.isConfirmed
                    ? 'Módulos: ' + swalFormatMulti(response.value)
                    : 'Selección de módulos cancelada.'
            );
            break;
        }
        case 'selectMultiTags': {
            const response = await Swal.fire({
                title: 'Filtrar por etiquetas',
                html:
                    '<div class="grova-swal-filter-fields text-start">' +
                    '<label class="form-label">Etiquetas (select nativo múltiple)</label>' +
                    '<select id="swal-tags-multi" class="form-select form-select-sm grova-filter-select-native-multi" multiple style="width:100%;min-height:96px">' +
                    '<option value="urgente">Urgente</option>' +
                    '<option value="vip">VIP</option>' +
                    '<option value="facturacion">Facturación</option>' +
                    '<option value="soporte">Soporte</option>' +
                    '<option value="interno">Interno</option>' +
                    '<option value="cliente">Cliente</option>' +
                    '</select>' +
                    '</div>',
                preConfirm: function () {
                    const values = swalReadMultiNative('#swal-tags-multi');
                    if (!values.length) {
                        Swal.showValidationMessage('Selecciona al menos una etiqueta');
                        return false;
                    }
                    return values;
                },
                showCancelButton: true,
                confirmButtonText: 'Aplicar',
                cancelButtonText: 'Cancelar',
            });
            setSwalResult(
                response.isConfirmed
                    ? 'Etiquetas: ' + swalFormatMulti(response.value)
                    : 'Filtro de etiquetas cancelado.'
            );
            break;
        }
        default:
            setSwalResult('Demo no implementado.');
    }
}
