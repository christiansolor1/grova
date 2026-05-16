(function (global) {
    function normalizeText(s) {
        return String(s || '')
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .trim();
    }

    var semanticDict = {
        'pencil': ['lapiz', 'lápiz', 'editar', 'edicion', 'edición', 'escribir', 'edit', 'write', 'draft'],
        'pen': ['lapiz', 'lápiz', 'editar', 'escribir', 'edit', 'write'],
        'briefcase': ['portafolio', 'cartera', 'maletin', 'maletín', 'trabajo', 'negocio', 'portfolio', 'work', 'business'],
        'bag': ['portafolio', 'bolsa', 'compras', 'tienda', 'shopping', 'store', 'bag'],
        'folder': ['carpeta', 'archivo', 'documento', 'folder', 'directory', 'files'],
        'file': ['archivo', 'documento', 'ficha', 'file', 'document', 'record'],
        'house': ['inicio', 'hogar', 'casa', 'home', 'start'],
        'person': ['usuario', 'perfil', 'persona', 'cliente', 'user', 'profile', 'customer'],
        'people': ['usuarios', 'equipo', 'grupo', 'personas', 'users', 'team', 'group'],
        'gear': ['configuracion', 'configuración', 'ajustes', 'opciones', 'settings', 'setup', 'options'],
        'sliders': ['configuracion', 'configuración', 'ajustes', 'filtros', 'settings', 'filters', 'controls'],
        'list': ['lista', 'menu', 'menú', 'items', 'list', 'menu', 'entries'],
        'grid': ['cuadricula', 'cuadrícula', 'modulos', 'mosaico', 'panel', 'grid', 'layout', 'tiles'],
        'table': ['tabla', 'datos', 'reporte', 'table', 'data', 'report'],
        'graph': ['grafica', 'gráfica', 'estadistica', 'estadística', 'analitica', 'analítica', 'chart', 'graph', 'analytics', 'stats'],
        'bar-chart': ['grafica', 'gráfica', 'estadistica', 'estadística', 'reporte', 'chart', 'bar chart', 'report'],
        'search': ['buscar', 'lupa', 'consulta', 'search', 'find', 'lookup'],
        'bell': ['notificacion', 'notificación', 'alerta', 'aviso', 'notification', 'alert', 'notice'],
        'shield': ['seguridad', 'permisos', 'proteccion', 'protección', 'security', 'permissions', 'protection'],
        'lock': ['seguridad', 'bloqueo', 'privado', 'security', 'lock', 'private'],
        'key': ['llave', 'acceso', 'credencial', 'key', 'access', 'credential'],
        'calendar': ['calendario', 'fecha', 'agenda', 'calendar', 'date', 'schedule'],
        'chat': ['mensaje', 'chat', 'comentario', 'soporte', 'message', 'comment', 'support'],
        'envelope': ['correo', 'email', 'mail', 'mensaje', 'mail', 'email', 'message'],
        'trash': ['eliminar', 'borrar', 'papelera', 'delete', 'remove', 'trash'],
        'trash3': ['eliminar', 'borrar', 'papelera', 'delete', 'remove', 'trash'],
        'check': ['ok', 'confirmar', 'aprobado', 'activo', 'check', 'confirm', 'approved', 'active'],
        'x': ['cerrar', 'cancelar', 'quitar', 'eliminar', 'close', 'cancel', 'remove', 'delete'],
        'plus': ['agregar', 'nuevo', 'crear', 'sumar', 'add', 'new', 'create', 'plus'],
        'dash': ['restar', 'menos', 'quitar', 'minus', 'subtract', 'remove'],
        'eye': ['ver', 'previsualizacion', 'previsualización', 'vista', 'view', 'preview', 'show'],
        'image': ['foto', 'imagen', 'galeria', 'galería', 'media', 'photo', 'image', 'gallery', 'media']
    };

    // Diccionario base por token para cubrir miles de iconos sin mapear uno por uno.
    // Se usa sobre cualquier nombre compuesto: "book-open", "file-earmark-text", etc.
    var tokenSynonyms = {
        'book': ['libro', 'manual', 'cuaderno'],
        'books': ['libros', 'biblioteca'],
        'journal': ['diario', 'registro', 'bitacora', 'bitácora'],
        'bookmark': ['marcador', 'favorito'],
        'open': ['abrir', 'abierto'],
        'close': ['cerrar', 'cerrado'],
        'user': ['usuario'],
        'users': ['usuarios', 'equipo'],
        'account': ['cuenta', 'perfil'],
        'home': ['inicio', 'hogar', 'casa'],
        'dashboard': ['panel', 'tablero'],
        'settings': ['configuracion', 'configuración', 'ajustes'],
        'config': ['configuracion', 'configuración'],
        'menu': ['menu', 'menú'],
        'search': ['buscar', 'busqueda', 'búsqueda'],
        'filter': ['filtro', 'filtrar'],
        'folder': ['carpeta', 'directorio'],
        'file': ['archivo', 'documento'],
        'document': ['documento'],
        'text': ['texto'],
        'image': ['imagen', 'foto'],
        'table': ['tabla'],
        'chart': ['grafica', 'gráfica'],
        'graph': ['grafica', 'gráfica'],
        'report': ['reporte', 'informe'],
        'invoice': ['factura'],
        'money': ['dinero', 'efectivo'],
        'wallet': ['cartera'],
        'bank': ['banco'],
        'calendar': ['calendario', 'agenda'],
        'clock': ['reloj', 'hora'],
        'time': ['tiempo', 'hora'],
        'bell': ['notificacion', 'notificación', 'alerta'],
        'mail': ['correo', 'email'],
        'message': ['mensaje'],
        'chat': ['chat', 'mensaje'],
        'phone': ['telefono', 'teléfono'],
        'link': ['enlace', 'vinculo', 'vínculo'],
        'download': ['descargar'],
        'upload': ['subir', 'cargar'],
        'save': ['guardar'],
        'edit': ['editar'],
        'pencil': ['lapiz', 'lápiz', 'editar'],
        'trash': ['eliminar', 'borrar', 'papelera'],
        'delete': ['eliminar', 'borrar'],
        'remove': ['quitar', 'eliminar'],
        'plus': ['agregar', 'nuevo', 'crear'],
        'minus': ['restar', 'menos', 'quitar'],
        'check': ['ok', 'confirmar', 'aprobado'],
        'x': ['cerrar', 'cancelar', 'quitar'],
        'shield': ['seguridad', 'proteccion', 'protección'],
        'lock': ['bloquear', 'seguridad'],
        'unlock': ['desbloquear'],
        'key': ['llave', 'clave', 'acceso'],
        'cart': ['carrito', 'compra'],
        'shop': ['tienda', 'compras'],
        'tag': ['etiqueta'],
        'tags': ['etiquetas'],
        'star': ['estrella', 'favorito'],
        'heart': ['corazon', 'corazón', 'favorito'],
        'cloud': ['nube'],
        'server': ['servidor'],
        'database': ['base de datos', 'bd'],
        'code': ['codigo', 'código'],
        'terminal': ['consola', 'terminal']
    };

    function buildTokenSearchTags(base) {
        var tokens = String(base || '').split(/[^a-z0-9]+/).filter(Boolean);
        var tags = [];
        tokens.forEach(function (t) {
            tags.push(t);
            if (t.length > 3 && t.slice(-1) === 's') tags.push(t.slice(0, -1));
            if (tokenSynonyms[t]) tags = tags.concat(tokenSynonyms[t]);
        });
        return tags;
    }

    function buildIconTags(iconValue, iconSet) {
        var base = String(iconValue || '')
            .replace(/^bi-/, '')
            .replace(/^fa[srbld]? fa-/, '')
            .replace(/^fa-/, '');
        var tags = [base, base.replace(/-/g, ' ')].concat(buildTokenSearchTags(base));
        Object.keys(semanticDict).forEach(function (k) {
            if (base.indexOf(k) !== -1) {
                tags = tags.concat(semanticDict[k]);
            }
        });
        tags.push(iconSet === 'fa' ? 'font awesome fa' : 'bootstrap icons bi');
        return Array.from(new Set(tags));
    }

    function loadAllIcons() {
        var bsFetch = fetch('https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.json')
            .then(function (res) { return res.json(); })
            .then(function (json) {
                return Object.keys(json || {}).sort().map(function (name) {
                    return { set: 'bi', value: 'bi-' + name, className: 'bi-' + name, shortName: name };
                });
            })
            .catch(function () {
                return ['list', 'grid', 'gear', 'person', 'folder', 'house', 'table', 'shield'].map(function (name) {
                    return { set: 'bi', value: 'bi-' + name, className: 'bi-' + name, shortName: name };
                });
            });

        var faFetch = fetch('https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.7.2/metadata/icons.json')
            .then(function (res) { return res.json(); })
            .then(function (json) {
                var icons = [];
                Object.keys(json || {}).sort().forEach(function (name) {
                    var meta = json[name] || {};
                    var free = Array.isArray(meta.free) ? meta.free : [];
                    // Cargar solo estilos gratuitos de Font Awesome Free.
                    if (free.indexOf('solid') > -1) {
                        icons.push({ set: 'fa', value: 'fa-solid fa-' + name, className: 'fa-solid fa-' + name, shortName: name });
                    }
                    if (free.indexOf('regular') > -1) {
                        icons.push({ set: 'fa', value: 'fa-regular fa-' + name, className: 'fa-regular fa-' + name, shortName: name });
                    }
                    if (free.indexOf('brands') > -1) {
                        icons.push({ set: 'fa', value: 'fa-brands fa-' + name, className: 'fa-brands fa-' + name, shortName: name });
                    }
                });
                return icons;
            })
            .catch(function () {
                return [
                    { set: 'fa', value: 'fa-solid fa-user', className: 'fa-solid fa-user', shortName: 'user' },
                    { set: 'fa', value: 'fa-solid fa-users', className: 'fa-solid fa-users', shortName: 'users' },
                    { set: 'fa', value: 'fa-solid fa-gear', className: 'fa-solid fa-gear', shortName: 'gear' },
                    { set: 'fa', value: 'fa-solid fa-briefcase', className: 'fa-solid fa-briefcase', shortName: 'briefcase' },
                    { set: 'fa', value: 'fa-solid fa-folder', className: 'fa-solid fa-folder', shortName: 'folder' },
                    { set: 'fa', value: 'fa-solid fa-house', className: 'fa-solid fa-house', shortName: 'house' },
                    { set: 'fa', value: 'fa-brands fa-github', className: 'fa-brands fa-github', shortName: 'github' }
                ];
            });

        return Promise.all([bsFetch, faFetch]).then(function (results) {
            return [].concat(results[0] || [], results[1] || []);
        });
    }

    function filterIcons(icons, term, setFilter, limit) {
        var normTerm = normalizeText(term);
        var size = parseInt(limit, 10) || 100;
        var filtered = (icons || []).filter(function (icon) {
            if (setFilter && setFilter !== 'all' && icon.set !== setFilter) return false;
            if (!normTerm) return true;
            var classText = normalizeText(icon.className || '');
            var shortName = normalizeText(icon.shortName || '');
            var tags = normalizeText(buildIconTags(icon.value || icon.className || '', icon.set || 'bi').join(' '));
            return classText.indexOf(normTerm) > -1 || shortName.indexOf(normTerm) > -1 || tags.indexOf(normTerm) > -1;
        });
        return filtered.slice(0, size);
    }

    global.GrovaIconLib = {
        normalizeText: normalizeText,
        buildIconTags: buildIconTags,
        loadAllIcons: loadAllIcons,
        filterIcons: filterIcons
    };
})(window);
