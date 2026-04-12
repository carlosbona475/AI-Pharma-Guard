/**
 * Protege páginas do painel: redireciona ao login se não houver sessão.
 * Não incluir em login.html, cadastro, etc.
 */
(function () {
    try {
        var path = window.location.pathname || '';
        if (/login\.html/i.test(path)) return;

        fetch('/backend/auth/check.php', { credentials: 'same-origin', cache: 'no-store' })
            .then(function (r) {
                return r.json().catch(function () {
                    return { authenticated: false };
                });
            })
            .then(function (d) {
                if (!d || !d.authenticated) {
                    window.location.replace('/frontend/pages/login.html');
                }
            })
            .catch(function () {
                window.location.replace('/frontend/pages/login.html');
            });
    } catch (e) {
        console.error('[auth-guard]', e);
    }
})();
