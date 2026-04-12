/**
 * Shell SaaS: menu lateral em mobile (drawer) e overlay.
 */
(function () {
    var body = document.body;
    var btn = document.querySelector('.sidebar-toggle');
    var overlay = document.querySelector('.app-overlay');

    function close() {
        body.classList.remove('sidebar-open');
    }

    function toggle() {
        body.classList.toggle('sidebar-open');
    }

    if (btn) {
        btn.addEventListener('click', function (e) {
            e.stopPropagation();
            toggle();
        });
    }
    if (overlay) {
        overlay.addEventListener('click', close);
    }
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') close();
    });
})();
