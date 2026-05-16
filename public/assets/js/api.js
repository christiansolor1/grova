/**
 * api.js — Función central de peticiones HTTP.
 * Se carga después de app.js y antes que cualquier módulo JS.
 *
 * Soporta:
 *   - Body JSON o FormData (se detecta automáticamente)
 *   - CSRF token por petición
 *   - Variables en la URL (la URL la construye el módulo/Twig, no este archivo)
 *   - 3 tipos de error separados: red, HTTP, negocio
 *
 * Uso:
 *   Api.request(url, { method: 'POST', body: { foo: 'bar' }, csrfToken: '...' })
 *     .then(json => { ... })
 *     .catch(err => {
 *         if (err.type === 'network') { ... }
 *         if (err.type === 'http')    { ... }  // err.status disponible
 *         if (err.type === 'business'){ ... }  // err.json disponible
 *     });
 */
window.Api = (function () {

    /**
     * @param {string} url
     * @param {object} options
     * @param {string}           [options.method='GET']
     * @param {object|FormData}  [options.body]        — objeto plano → JSON, FormData → multipart
     * @param {string}           [options.csrfToken]
     * @returns {Promise<object>}  resuelve con el JSON de la respuesta
     */
    function request(url, options) {
        var opts = options || {};
        var method = (opts.method || 'GET').toUpperCase();
        var csrfToken = opts.csrfToken || null;
        var body = opts.body !== undefined ? opts.body : null;

        var headers = {};
        if (csrfToken) {
            headers['X-CSRF-TOKEN'] = csrfToken;
        }

        var fetchOptions = { method: method, headers: headers };

        if (body !== null) {
            if (body instanceof FormData) {
                // FormData: el navegador pone el Content-Type con boundary automáticamente
                fetchOptions.body = body;
            } else {
                headers['Content-Type'] = 'application/json';
                fetchOptions.body = JSON.stringify(body);
            }
        }

        // 1. Error de red — no llegó al servidor
        return fetch(url, fetchOptions).then(function (res) {
            // 2. Error HTTP — llegó pero el servidor respondió con error (4xx, 5xx)
            if (!res.ok) {
                var err = new Error('HTTP ' + res.status);
                err.type = 'http';
                err.status = res.status;
                err.response = res;
                return Promise.reject(err);
            }

            return res.json().then(function (json) {
                // 3. Error de negocio — el servidor respondió 200 pero la lógica falló
                if (json && json.success === false) {
                    var bizErr = new Error(json.message || 'Error de negocio');
                    bizErr.type = 'business';
                    bizErr.json = json;
                    return Promise.reject(bizErr);
                }
                return json;
            });
        }, function () {
            var netErr = new Error('Sin conexión');
            netErr.type = 'network';
            return Promise.reject(netErr);
        });
    }

    return { request: request };
})();
