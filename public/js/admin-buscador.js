        // ── Sidebar toggle (mobile) ────────────────────
        const sidebar         = document.getElementById('sidebar');
        const overlay         = document.getElementById('sidebarOverlay');
        const hamburgerBtn    = document.getElementById('hamburgerBtn');

        function openSidebar() {
            sidebar.classList.add('open');
            overlay.classList.add('open');
            document.body.style.overflow = 'hidden';
        }

        function closeSidebar() {
            sidebar.classList.remove('open');
            overlay.classList.remove('open');
            document.body.style.overflow = '';
        }

        hamburgerBtn.addEventListener('click', () => {
            sidebar.classList.contains('open') ? closeSidebar() : openSidebar();
        });

        overlay.addEventListener('click', closeSidebar);

        // Close sidebar on window resize above breakpoint
        window.addEventListener('resize', () => {
            if (window.innerWidth >= 992) closeSidebar();
        });

        // ── Sidebar scroll persistence ─────────────────
        // Keeps scroll position when navigating between pages.
        // Only resets to active item if the user hasn't manually scrolled.
        (function() {
            const nav = document.querySelector('.sidebar-nav');
            if (!nav) return;
            const KEY = 'sge-sidebar-scroll';

            // Restore saved position immediately (before paint)
            // Always scroll active item into center on every load
            const active = nav.querySelector('.nav-item a.active');
            if (active) {
                const offset = active.getBoundingClientRect().top
                             - nav.getBoundingClientRect().top
                             + nav.scrollTop
                             - nav.clientHeight / 3;
                nav.scrollTop = Math.max(0, offset);
            } else if (sessionStorage.getItem(KEY) !== null) {
                nav.scrollTop = parseInt(sessionStorage.getItem(KEY), 10);
            }

            // Save scroll position on every link click (before unload)
            nav.addEventListener('click', e => {
                const link = e.target.closest('a[href]');
                if (link) {
                    sessionStorage.setItem(KEY, nav.scrollTop);
                    // Al entrar a un ítem del menú, colapsar el sidebar
                    // completo para dar más espacio a la página destino
                    // (en desktop — en móvil ya se cierra solo al navegar).
                    // Se vuelve a mostrar con el botón del topbar.
                    localStorage.setItem('sidebarCollapsed', '1');
                }
            });

            // Also save when browser navigates away (back/forward)
            window.addEventListener('pagehide', () => {
                sessionStorage.setItem(KEY, nav.scrollTop);
            });
        })();

        // ── Secciones del menú colapsables (auto-aplicado a cada
        // .nav-section-title + <ul> siguiente, sin tocar el HTML de cada
        // sección — reutiliza el mismo patrón .sidebar-submenu de abajo) ──
        (function () {
            // Icono por sección, por texto — evita tocar los 39 bloques
            // Blade uno por uno. "bi-collection" es el genérico de respaldo
            // para cualquier título nuevo que no esté en este mapa.
            const ICONOS_SECCION = {
                'Registro de Estudiantes':   'bi-person-vcard',
                'Matrículas y Grupos':       'bi-diagram-3',
                'Documentos y Registros':    'bi-folder2-open',
                'Reportes':                  'bi-bar-chart-line',
                'Exportación SIGERD':        'bi-cloud-arrow-up',
                'Comunicación':              'bi-chat-dots',
                'Documentos':                'bi-file-earmark-text',
                'Gestión Académica':         'bi-mortarboard',
                'Calificaciones':            'bi-journal-check',
                'Supervisión':               'bi-eye',
                'Planificación':             'bi-calendar2-week',
                'Pagos y Colegiaturas':      'bi-cash-coin',
                'Biblioteca':                'bi-book',
                'Estudiantes':               'bi-people',
                'Gestión Institucional':     'bi-building',
                'Mi Espacio':                'bi-house-door',
                'Rendimiento':               'bi-graph-up-arrow',
                'Rendimiento y análisis':    'bi-graph-up-arrow',
                'Convivencia y bienestar':   'bi-heart-pulse',
                'Comunicación y agenda':     'bi-megaphone',
                'Administración y soporte':  'bi-life-preserver',
                'Planificación Docente':     'bi-clipboard-data',
                'Calendario':                'bi-calendar3',
                'Comunicados y Mensajes':    'bi-megaphone',
                'Inscripciones':             'bi-pencil-square',
                'Solicitudes':               'bi-inbox',
                'Servicios Institucionales': 'bi-gear-wide-connected',
                'Soporte':                   'bi-life-preserver',
                'Configuración':             'bi-sliders',
                'Página de Inicio':          'bi-globe',
                'Integraciones':             'bi-plug',
                'Sistema':                   'bi-hdd-stack',
                'ZuraEdu Platform':          'bi-stars',
            };

            const titles = document.querySelectorAll('.sidebar-nav .nav-section-title');
            titles.forEach((title, idx) => {
                const list = title.nextElementSibling;
                if (!list || list.tagName !== 'UL') return;

                // Por nombre y no por posición: el filtro por rol cambia las posiciones y el estado abierto/cerrado se mezclaba entre secciones
                const key = 'sidebarSection:' + title.textContent.trim();
                const hasActive = !!list.querySelector('a.active, a[aria-current="page"]');
                const stored = localStorage.getItem(key);
                const open = stored !== null ? stored === '1' : hasActive;

                const labelText = title.textContent.trim();
                const iconClass = ICONOS_SECCION[labelText] || 'bi-collection';
                const label = document.createElement('span');
                label.className = 'nav-section-label';
                label.innerHTML = '<i class="bi ' + iconClass + '"></i>' + labelText;
                title.textContent = '';
                title.appendChild(label);

                title.classList.add('nav-section-toggle');
                title.setAttribute('role', 'button');
                title.setAttribute('tabindex', '0');
                title.setAttribute('aria-expanded', open ? 'true' : 'false');

                const chevron = document.createElement('i');
                chevron.className = 'bi bi-chevron-down nav-section-chevron';
                title.appendChild(chevron);

                list.classList.add('sidebar-submenu');
                if (open) {
                    list.classList.add('sidebar-submenu-open');
                    chevron.style.transform = 'rotate(180deg)';
                }

                const toggle = () => {
                    const nowOpen = list.classList.toggle('sidebar-submenu-open');
                    title.setAttribute('aria-expanded', nowOpen ? 'true' : 'false');
                    chevron.style.transform = nowOpen ? 'rotate(180deg)' : '';
                    localStorage.setItem(key, nowOpen ? '1' : '0');
                };

                title.addEventListener('click', toggle);
                title.addEventListener('keydown', e => {
                    if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); toggle(); }
                });
            });
        })();

        // ── Toggle de sidebar completo (ocultar/mostrar en desktop) ───
        (function () {
            const btn  = document.getElementById('sidebarCollapseBtn');
            const icon = document.getElementById('sidebarCollapseIcon');
            if (!btn) return;

            function aplicar(colapsado) {
                document.documentElement.classList.toggle('sidebar-collapsed', colapsado);
                btn.setAttribute('aria-expanded', colapsado ? 'false' : 'true');
                if (icon) icon.className = colapsado ? 'bi bi-layout-sidebar' : 'bi bi-layout-sidebar-inset';
            }

            aplicar(document.documentElement.classList.contains('sidebar-collapsed'));

            btn.addEventListener('click', () => {
                const colapsado = !document.documentElement.classList.contains('sidebar-collapsed');
                aplicar(colapsado);
                localStorage.setItem('sidebarCollapsed', colapsado ? '1' : '0');
            });
        })();

        // ── Sidebar submenus (custom toggle, no Bootstrap Collapse) ───
        document.querySelectorAll('[data-sidebar-toggle]').forEach(btn => {
            btn.addEventListener('click', () => {
                const targetId = btn.dataset.sidebarToggle;
                const target   = document.getElementById(targetId);
                if (!target) return;
                const isOpen = target.classList.toggle('sidebar-submenu-open');
                btn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
                const chevron = btn.querySelector('.bi-chevron-down');
                if (chevron) chevron.style.transform = isOpen ? 'rotate(180deg)' : 'rotate(0deg)';
                if (isOpen) {
                    const nav = document.querySelector('.sidebar-nav');
                    if (nav) {
                        setTimeout(() => {
                            const btnBottom = btn.getBoundingClientRect().bottom;
                            const navBottom = nav.getBoundingClientRect().bottom;
                            if (btnBottom > navBottom - 60) {
                                nav.scrollBy({ top: target.scrollHeight + 24, behavior: 'smooth' });
                            }
                        }, 360);
                    }
                }
            });
        });

        // ── Dark mode toggle ──────────────────────────
        const darkBtn  = document.getElementById('darkToggleBtn');
        const darkIcon = document.getElementById('darkToggleIcon');
        function applyTheme(t) {
            document.documentElement.setAttribute('data-theme', t);
            if (darkIcon) {
                darkIcon.className = t === 'dark' ? 'bi bi-sun-fill' : 'bi bi-moon-stars-fill';
            }
        }
        applyTheme(localStorage.getItem('sge-theme') || 'light');
        if (darkBtn) {
            darkBtn.addEventListener('click', () => {
                const current = document.documentElement.getAttribute('data-theme');
                const next    = current === 'dark' ? 'light' : 'dark';
                localStorage.setItem('sge-theme', next);
                applyTheme(next);
            });
        }

        // ── Global Search ─────────────────────────────
        (function() {
            const input    = document.getElementById('globalSearchInput');
            const dropdown = document.getElementById('gsDropdown');
            if (!input || !dropdown) return;

            const ROUTE = ZURA_CFG.searchUrl;
            let timer, activeIdx = -1, items = [];

            function render(results) {
                if (!results.length) {
                    dropdown.innerHTML = '<div class="gs-empty"><i class="bi bi-search me-1"></i>Sin resultados</div>';
                    dropdown.classList.add('open');
                    return;
                }
                const groups = {};
                results.forEach(r => { if (!groups[r.grupo]) groups[r.grupo] = []; groups[r.grupo].push(r); });
                let html = '';
                Object.keys(groups).forEach(g => {
                    html += `<div class="gs-group-header">${g}</div>`;
                    groups[g].forEach((r, i) => {
                        html += `<a href="${r.url}" class="gs-item" data-idx="${items.length}">
                            <div class="gs-item-icon" style="background:${r.color}"><i class="bi ${r.icon}"></i></div>
                            <div><div class="gs-item-label">${r.label}</div><div class="gs-item-sub">${r.sub}</div></div>
                        </a>`;
                        items.push(r);
                    });
                });
                dropdown.innerHTML = html;
                dropdown.classList.add('open');
                activeIdx = -1;
            }

            function close() { dropdown.classList.remove('open'); activeIdx = -1; items = []; }

            input.addEventListener('input', () => {
                clearTimeout(timer);
                const q = input.value.trim();
                items = [];
                if (q.length < 2) { close(); return; }
                timer = setTimeout(() => {
                    fetch(`${ROUTE}?q=${encodeURIComponent(q)}`, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                        .then(r => r.json()).then(d => render(d.results || []))
                        .catch(() => {});
                }, 280);
            });

            input.addEventListener('keydown', e => {
                const els = dropdown.querySelectorAll('.gs-item');
                if (e.key === 'ArrowDown') {
                    activeIdx = Math.min(activeIdx + 1, els.length - 1);
                    els.forEach((el, i) => el.classList.toggle('active', i === activeIdx));
                    e.preventDefault();
                } else if (e.key === 'ArrowUp') {
                    activeIdx = Math.max(activeIdx - 1, -1);
                    els.forEach((el, i) => el.classList.toggle('active', i === activeIdx));
                    e.preventDefault();
                } else if (e.key === 'Enter' && activeIdx >= 0 && els[activeIdx]) {
                    els[activeIdx].click();
                } else if (e.key === 'Escape') {
                    close(); input.blur();
                }
            });

            document.addEventListener('click', e => {
                if (!input.contains(e.target) && !dropdown.contains(e.target)) close();
            });
        })();
    