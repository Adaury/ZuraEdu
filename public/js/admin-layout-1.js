    // ── Barra de progreso de navegación ───────────────────────────────────
    (function () {
        var bar  = document.getElementById('nprogress-bar');
        var prog = 0;
        var raf;

        function set(n) {
            prog = Math.min(n, 1);
            bar.style.transform = 'scaleX(' + prog + ')';
        }

        function start() {
            set(0);
            bar.style.transition = 'transform .1s linear';
            bar.style.opacity = '1';
            // Simula progreso: rápido hasta 30%, lento hasta 85%
            var steps = [.15, .25, .35, .50, .65, .75, .82, .85];
            var i = 0;
            (function tick() {
                if (i < steps.length) {
                    setTimeout(function() { set(steps[i++]); tick(); }, 80 + i * 60);
                }
            })();
        }

        function done() {
            clearTimeout(raf);
            bar.style.transition = 'transform .15s ease';
            set(1);
            setTimeout(function () {
                bar.style.opacity = '0';
                setTimeout(function() { set(0); bar.style.transition = 'none'; }, 200);
            }, 200);
        }

        // Inicia al hacer clic en cualquier enlace interno
        document.addEventListener('click', function (e) {
            var a = e.target.closest('a[href]');
            if (!a) return;
            var href = a.getAttribute('href');
            if (!href || href[0] === '#' || href.startsWith('javascript') ||
                a.target === '_blank' || e.ctrlKey || e.metaKey || e.shiftKey) return;
            // No iniciar en formularios de logout (POST)
            if (a.closest('form')) return;
            start();
        });

        // También inicia en submit de formularios de navegación (GET)
        document.addEventListener('submit', function (e) {
            if (e.target.method && e.target.method.toLowerCase() === 'get') start();
        });

        window.addEventListener('pageshow', done);
        // Si ya cargó (navegación SPA-like no aplica, pero por seguridad)
        if (document.readyState === 'complete') done();
        else window.addEventListener('load', done);
    })();

    // ── Prefetch al hover (desktop) ───────────────────────────────────────
    // Al pasar el cursor >100ms sobre un enlace del sidebar, precarga la página.
    (function () {
        if ('connection' in navigator && navigator.connection.saveData) return; // No en modo ahorro
        var prefetched = new Set();

        function prefetch(href) {
            if (prefetched.has(href)) return;
            prefetched.add(href);
            var l = document.createElement('link');
            l.rel = 'prefetch';
            l.href = href;
            document.head.appendChild(l);
        }

        document.querySelectorAll('.sidebar-nav a[href]').forEach(function (a) {
            var t;
            a.addEventListener('mouseenter', function () {
                t = setTimeout(function () { prefetch(a.href); }, 100);
            });
            a.addEventListener('mouseleave', function () { clearTimeout(t); });
        });
    })();
    