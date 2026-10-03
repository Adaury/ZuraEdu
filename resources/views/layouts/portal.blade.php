<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $__env->yieldContent('page-title') ?: $__env->yieldContent('title', 'Portal') }} — ZuraEdu</title>
    @include('partials.marca.head', ['sinPwa' => true])

    <link href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/bootstrap-icons/bootstrap-icons.min.css') }}" rel="stylesheet">

    {{-- Aplicar tema antes de renderizar para evitar flash --}}
    <script>
    (function(){
        var t = localStorage.getItem('sge-theme') || 'light';
        document.documentElement.setAttribute('data-theme', t);
    })();
    </script>

    <link rel="stylesheet" href="{{ asset('css/portal-layout.css') }}?v={{ @filemtime(public_path('css/portal-layout.css')) }}">
    @stack('styles')

    {{-- PWA --}}
    <link rel="manifest" href="/pwa/manifest.json" crossorigin="use-credentials">
    @php
        $__pwaColor = app()->bound('tenant') ? (app('tenant')->color_primario ?? '#1d4ed8') : '#1d4ed8';
        $__pwaTid   = tenant_id() ?? 0;
        $__pwaName  = app()->bound('tenant') ? (app('tenant')->nombre_institucion ?? config('app.name')) : config('app.name');
    @endphp
    <meta name="theme-color" content="{{ $__pwaColor }}">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="{{ $__pwaName }}">
    <link rel="apple-touch-icon" href="/pwa/icon/192?tid={{ $__pwaTid }}">
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js', { scope: '/' })
                    .catch(() => {});
            });
        }
    </script>
</head>
<body class="{{ auth()->check() ? (auth()->user()->hasRole('Docente') ? 'role-docente' : (auth()->user()->hasRole('Representante') ? 'role-padre' : (auth()->user()->hasRole('Estudiante') ? 'role-estudiante' : ''))) : '' }}">

{{-- ── Banner Modo Demo ──────────────────────────────────────────────── --}}
@if(session('demo_mode'))
<div style="background:linear-gradient(90deg,#92400e,#b45309);color:#fff;text-align:center;padding:.5rem 1rem;font-size:.77rem;font-weight:600;display:flex;align-items:center;justify-content:center;gap:.65rem;flex-wrap:wrap;z-index:999;position:relative;box-shadow:0 2px 8px rgba(0,0,0,.2);">
    <span style="display:flex;align-items:center;gap:.35rem;">
        <i class="bi bi-shield-exclamation"></i>
        <strong>MODO DEMO</strong> — Datos de ejemplo · Cambios críticos bloqueados
    </span>
    @if($errors->has('demo_mode'))
    <span style="background:rgba(0,0,0,.2);border-radius:5px;padding:.15rem .5rem;font-size:.72rem;">🔒 {{ $errors->first('demo_mode') }}</span>
    @endif
    <form method="POST" action="{{ route('logout') }}" style="margin:0;">
        @csrf
        <button type="submit" style="background:rgba(255,255,255,.18);border:1px solid rgba(255,255,255,.3);color:#fff;border-radius:5px;padding:.2rem .65rem;font-size:.7rem;font-weight:700;cursor:pointer;">
            <i class="bi bi-box-arrow-right me-1"></i>Salir
        </button>
    </form>
</div>
@endif

{{-- ── Topbar ────────────────────────────────────────────────────────── --}}
@php
$sysAbbr = \App\Helpers\Setting::get('system_abbr', 'SGE');
$sysName = \App\Helpers\Setting::get('system_name', config('app.name','SGE'));
$sysLogo = \App\Helpers\Setting::get('system_logo');
@endphp
<nav class="prt-topbar">
    <a href="{{ route('admin.dashboard') }}" class="prt-logo" style="background:rgba(255,255,255,.18);font-size:.78rem;">
        @if($sysLogo)
            <img src="{{ Storage::url($sysLogo) }}" alt="{{ $sysAbbr }}" style="width:100%;height:100%;object-fit:contain;border-radius:9px;">
        @elseif(\App\Helpers\Setting::get('system_abbr'))
            {{ $sysAbbr }}
        @else
            {{-- Ni logo ni abreviatura propios: insignia de ZuraEdu en vez de las siglas «SGE» por defecto --}}
            <img src="{{ asset('brand/zuraedu-icono.svg') }}" alt="ZuraEdu" style="width:100%;height:100%;object-fit:contain;border-radius:9px;">
        @endif
    </a>
    <div>
        <div class="prt-brand">@yield('portal-name', 'Portal')</div>
        <div class="prt-brand-sub">{{ $sysName }}</div>
    </div>

    <div class="prt-topbar-right">
        {{-- Accesos rápidos del rol (icono + panel) --}}
        @include('partials.accesos-rapidos-boton', ['variante' => 'oscuro'])
        {{-- Toggle dark mode --}}
        <button class="prt-dark-toggle" id="prtDarkToggle" title="Alternar modo oscuro/claro" type="button">
            <i class="bi bi-moon-stars-fill" id="prtDarkIcon"></i>
        </button>

        {{-- Campana notificaciones --}}
        @php $totalNoLeidas = $totalNoLeidas ?? 0; @endphp
        <a href="#notificaciones" class="prt-bell" title="Notificaciones" id="btnBell">
            <i class="bi bi-bell-fill"></i>
            @if($totalNoLeidas > 0)
                <span class="prt-badge">{{ $totalNoLeidas > 9 ? '9+' : $totalNoLeidas }}</span>
            @endif
        </a>

        {{-- Usuario --}}
        <div style="position:relative;">
            <button class="prt-user-btn" onclick="toggleDropdown()" id="userBtn" type="button">
                @if(auth()->user()->photo_url)
                    <img src="{{ auth()->user()->photo_url }}" alt="Foto" class="prt-user-avatar" style="object-fit:cover;">
                @else
                    <div class="prt-user-avatar">{{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}</div>
                @endif
                <span class="d-none d-sm-inline">{{ auth()->user()->name }}</span>
                <i class="bi bi-chevron-down" style="font-size:.6rem;"></i>
            </button>
            <div class="prt-dropdown" id="userDropdown">
                <a href="{{ route('perfil.show') }}"><i class="bi bi-person-circle" style="color:#6366f1;"></i>Mi perfil</a>
                <div class="prt-dropdown-divider"></div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit"><i class="bi bi-box-arrow-right" style="color:#ef4444;"></i>Cerrar sesión</button>
                </form>
            </div>
        </div>
    </div>
</nav>

{{-- ── Body (sidebar + main) ───────────────────────────────────────────── --}}
<div class="prt-body">

    {{-- Sidebar desktop --}}
    <aside class="prt-sidebar">
        @yield('sidebar')
    </aside>

    {{-- Contenido --}}
    <main class="prt-main">

        {{-- Alertas de sesión --}}
        @if(session('success'))
            <div class="prt-alert prt-alert-success mb-3">
                <i class="bi bi-check-circle-fill"></i>{{ session('success') }}
            </div>
        @endif
        @if(session('warning'))
            <div class="prt-alert prt-alert-warning mb-3">
                <i class="bi bi-exclamation-triangle-fill"></i>{{ session('warning') }}
            </div>
        @endif
        @if(session('info'))
            <div class="prt-alert prt-alert-info mb-3">
                <i class="bi bi-info-circle-fill"></i>{{ session('info') }}
            </div>
        @endif
        @if(session('error'))
            <div class="prt-alert prt-alert-danger mb-3">
                <i class="bi bi-exclamation-octagon-fill"></i>{{ session('error') }}
            </div>
        @endif
        @if($errors->any() && !$errors->has('email'))
            <div class="prt-alert prt-alert-danger mb-3">
                <i class="bi bi-exclamation-octagon-fill"></i>
                {{ $errors->first() }}
            </div>
        @endif

        @yield('content')

        {{-- Pie de la plataforma (queda sobre la barra inferior del móvil gracias al padding de .prt-main) --}}
        <x-marca.pie />
    </main>
</div>

{{-- ── Bottom nav móvil ─────────────────────────────────────────────────── --}}
<nav class="prt-bottom-nav">
    @yield('bottom-nav')
</nav>

{{-- NProgress — barra de carga entre páginas --}}
<script>
/* NProgress minimal inline (sin dependencia externa) */
var NProgress=(function(){var s='#nprogress',n=null,i=0,t=null;function c(e){var o=document.getElementById('nprogress-bar');if(!o){o=document.createElement('div');o.id='nprogress-bar';o.style.cssText='position:fixed;top:0;left:0;height:3px;background:#60a5fa;z-index:99999;transition:width .2s ease,opacity .4s ease;width:0;';document.body.appendChild(o);}return o;}function start(){clearTimeout(t);var b=c();b.style.opacity='1';b.style.width=(i=10)+'%';n=setInterval(function(){if(i<90){i+=i>=80?1:i>=50?2:5;b.style.width=i+'%';}},200);}function done(){clearInterval(n);var b=c();b.style.width='100%';t=setTimeout(function(){b.style.opacity='0';setTimeout(function(){b.style.width='0';},400);},200);}return{start:start,done:done};})();

// Iniciar barra al navegar
document.addEventListener('click', function(e) {
    var a = e.target.closest('a[href]');
    if (a && !a.target && !a.href.startsWith('#') && !a.href.startsWith('javascript') &&
        a.href.indexOf(window.location.origin) === 0) {
        NProgress.start();
    }
});
document.addEventListener('submit', function(e) {
    if (e.target.method !== 'get') NProgress.start();
});
window.addEventListener('pageshow', function() { NProgress.done(); });
</script>

<script>
// ── Dark mode toggle ──────────────────────────────────────────────────
(function() {
    function applyTheme(t) {
        document.documentElement.setAttribute('data-theme', t);
        var icon = document.getElementById('prtDarkIcon');
        if (icon) icon.className = t === 'dark' ? 'bi bi-sun-fill' : 'bi bi-moon-stars-fill';
    }
    // Aplicar tema guardado
    applyTheme(localStorage.getItem('sge-theme') || 'light');

    document.getElementById('prtDarkToggle')?.addEventListener('click', function() {
        var current = document.documentElement.getAttribute('data-theme');
        var next = current === 'dark' ? 'light' : 'dark';
        localStorage.setItem('sge-theme', next);
        applyTheme(next);
    });
})();

// ── Dropdown usuario ──────────────────────────────────────────────────
function toggleDropdown() {
    document.getElementById('userDropdown').classList.toggle('open');
}
document.addEventListener('click', function(e) {
    if (!document.getElementById('userBtn')?.contains(e.target)) {
        document.getElementById('userDropdown')?.classList.remove('open');
    }
});

// ── Campana → scroll notificaciones ──────────────────────────────────
document.getElementById('btnBell')?.addEventListener('click', function(e) {
    e.preventDefault();
    document.getElementById('notificaciones')?.scrollIntoView({ behavior: 'smooth' });
});

// ── Bottom nav: scroll suave con offset correcto ──────────────────────
document.querySelectorAll('.prt-bottom-nav a[href^="#"]').forEach(function(link) {
    link.addEventListener('click', function(e) {
        const hash = this.getAttribute('href');
        const target = document.querySelector(hash);
        if (!target) return;
        e.preventDefault();
        // Quitar active de todos, marcar el clickeado
        document.querySelectorAll('.prt-bottom-nav .prt-nav-item').forEach(function(a) {
            a.classList.remove('active');
        });
        this.classList.add('active');
        // Scroll con offset para topbar (56px) + margen extra (10px)
        const y = target.getBoundingClientRect().top + window.scrollY - 70;
        window.scrollTo({ top: y, behavior: 'smooth' });
    });
});
</script>
<script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
@stack('scripts')

{{-- Polling de notificaciones cada 45 segundos --}}
<script>
(function() {
    const CONTEO_URL = "{{ route('notificaciones.conteo') }}";
    let lastCount    = {{ $totalNoLeidas ?? 0 }};

    function actualizarBadge(count) {
        const badge = document.querySelector('.prt-bell .prt-badge');
        const btn   = document.getElementById('btnBell');
        if (! btn) return;

        if (count > 0) {
            if (badge) {
                badge.textContent = count > 9 ? '9+' : count;
            } else {
                const span = document.createElement('span');
                span.className  = 'prt-badge';
                span.textContent = count > 9 ? '9+' : count;
                btn.appendChild(span);
            }
            // Pulso visual si llegó nueva notificación
            if (count > lastCount) {
                btn.style.animation = 'none';
                btn.offsetHeight;   // reflow
                btn.style.animation = 'bellPulse .5s ease 3';
            }
        } else {
            badge?.remove();
        }
        lastCount = count;
    }

    async function pollNotificaciones() {
        try {
            const res  = await fetch(CONTEO_URL, { headers: { 'Accept': 'application/json' } });
            if (res.ok) {
                const { count } = await res.json();
                actualizarBadge(count);
            }
        } catch (_) {}
    }

    // Polling como fallback — Echo lo pausa cuando conecta a Reverb
    window._notifPollingInterval = setInterval(pollNotificaciones, 45000);

    // Cuando Echo actualiza el badge, sincronizar el contador interno del polling
    window.addEventListener('sge:notification-new', (e) => {
        lastCount = (lastCount || 0) + (e.detail?.delta ?? 1);
    });
})();
</script>

<style>
@keyframes bellPulse {
    0%,100% { transform: scale(1); }
    50%      { transform: scale(1.18); }
}
</style>

{{-- ── ZuraEdu Realtime — Echo + Reverb ──────────────────────────────────── --}}
@auth
<script>
window._REVERB_KEY    = '{{ config("broadcasting.connections.reverb.key") }}';
window._REVERB_HOST   = '{{ config("broadcasting.connections.reverb.options.host") }}';
window._REVERB_PORT   = {{ config("broadcasting.connections.reverb.options.port", 8080) }};
window._REVERB_SCHEME = '{{ config("broadcasting.connections.reverb.options.scheme", "http") }}';
window._SGE_USER_ID   = {{ auth()->id() }};
window._SGE_ROL       = '{{ auth()->user()->roles->first()?->name ?? "" }}';
window._SGE_TENANT_ID = {{ tenant_id() ?? 'null' }};
window._SGE_GRUPO_IDS = [];
window._SGE_CLASE_IDS = [];
window._SGE_DEBUG     = {{ config('app.debug') ? 'true' : 'false' }};
</script>
@stack('realtime-data')
@vite('resources/js/echo.js')
@endauth

<div id="sge-toast-container" aria-live="polite" aria-atomic="false"
     style="position:fixed;bottom:1.5rem;right:1.5rem;z-index:9999;display:flex;flex-direction:column;gap:.5rem;max-width:340px;"></div>

@include('partials.pwa-install-prompt')

{{-- ── ZuraAI Widget (Portal Docente y Estudiante) ─────────────────────── --}}
@auth
@php
    $__zuraRole = auth()->user()->hasRole('Docente') ? 'docente'
        : (auth()->user()->hasRole('Estudiante')     ? 'estudiante'
        : (auth()->user()->hasRole('Representante')  ? 'padre' : null));
@endphp
@if($__zuraRole)
<link rel="stylesheet" href="{{ asset('css/portal-extra.css') }}?v={{ @filemtime(public_path('css/portal-extra.css')) }}">

{{-- Botón flotante --}}
<button id="zura-ai-btn" title="ZuraAI — Asistente académico" aria-label="Abrir asistente IA">
    <i class="bi bi-stars"></i>
    <span class="zura-badge"></span>
</button>

{{-- Panel de chat --}}
<div id="zura-ai-panel" role="dialog" aria-label="ZuraAI Asistente">
    <div class="zura-header">
        <div class="zura-header-icon"><i class="bi bi-stars"></i></div>
        <div>
            <div class="zura-header-title">ZuraAI</div>
            <div class="zura-header-sub">Asistente Académico · Claude</div>
        </div>
        <div class="d-flex align-items-center gap-1 ms-auto">
            <button id="zura-clear" title="Nueva conversación" aria-label="Nueva conversación"
                style="background:none;border:none;cursor:pointer;padding:4px 6px;border-radius:6px;color:inherit;opacity:.6;transition:opacity .15s,background .15s;"
                onmouseenter="this.style.opacity='1';this.style.background='rgba(255,255,255,.1)'"
                onmouseleave="this.style.opacity='.6';this.style.background='none'">
                <i class="bi bi-arrow-counterclockwise" style="font-size:.9rem;"></i>
            </button>
            <button class="zura-header-close" id="zura-close" aria-label="Cerrar">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
    </div>

    <div class="zura-messages" id="zura-messages"></div>

    <div class="zura-suggestions" id="zura-suggestions">
        @if($__zuraRole === 'docente')
        <button class="zura-suggestion">Planificar una clase</button>
        <button class="zura-suggestion">Generar 10 preguntas</button>
        <button class="zura-suggestion">Crear una rúbrica</button>
        <button class="zura-suggestion">Comunicado para padres</button>
        @elseif($__zuraRole === 'estudiante')
        <button class="zura-suggestion">Explícame este tema</button>
        <button class="zura-suggestion">Ayuda con mi tarea</button>
        <button class="zura-suggestion">Cómo estudiar mejor</button>
        <button class="zura-suggestion">Resumir un texto</button>
        @else
        <button class="zura-suggestion">Entender el boletín</button>
        <button class="zura-suggestion">Apoyar en casa</button>
        <button class="zura-suggestion">Hablar con el docente</button>
        <button class="zura-suggestion">Hábitos de estudio</button>
        @endif
    </div>

    <div class="zura-input-row">
        <textarea id="zura-input" rows="1" placeholder="Pregunta algo…" maxlength="4000"></textarea>
        <button class="zura-send" id="zura-send" aria-label="Enviar">
            <i class="bi bi-send-fill"></i>
        </button>
    </div>
</div>

<script>
(function () {
    const btn       = document.getElementById('zura-ai-btn');
    const panel     = document.getElementById('zura-ai-panel');
    const closeBtn  = document.getElementById('zura-close');
    const clearBtn  = document.getElementById('zura-clear');
    const messagesEl= document.getElementById('zura-messages');
    const inputEl   = document.getElementById('zura-input');
    const sendBtn   = document.getElementById('zura-send');
    const suggsEl   = document.getElementById('zura-suggestions');
    const CHAT_URL  = "{{ $__zuraRole === 'docente' ? route('portal.docente.asistente.chat') : ($__zuraRole === 'estudiante' ? route('portal.estudiante.asistente.chat') : route('portal.padre.asistente.chat')) }}";
    const CSRF      = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

    let history = [];   // [{role, content}]
    let streaming = false;

    // ── Toggle panel ──────────────────────────────────────────────────────
    btn.addEventListener('click', () => {
        const wasOpen = panel.classList.contains('open');
        panel.classList.toggle('open');
        if (!wasOpen) {
            // Siempre inicia limpio al abrir
            history = [];
            streaming = false;
            messagesEl.innerHTML = '';
            suggsEl.style.display = '';
            sendBtn.disabled = false;
            inputEl.value = '';
            inputEl.style.height = 'auto';
            showWelcome();
            inputEl.focus();
        }
    });
    closeBtn.addEventListener('click', () => panel.classList.remove('open'));

    // ── Clear / nueva conversación ────────────────────────────────────────
    clearBtn.addEventListener('click', () => {
        if (streaming) return;
        history = [];
        messagesEl.innerHTML = '';
        suggsEl.style.display = '';
        showWelcome();
        inputEl.value = '';
        inputEl.style.height = 'auto';
        inputEl.focus();
    });

    // ── Welcome message ───────────────────────────────────────────────────
    const ZURA_ROLE = "{{ $__zuraRole }}";

    function showWelcome() {
        const msg = ZURA_ROLE === 'docente'
            ? '¡Hola! Soy **ZuraAI**, tu asistente académico. Puedo ayudarte a planificar clases, generar evaluaciones, redactar observaciones y mucho más. ¿En qué te ayudo hoy?'
            : ZURA_ROLE === 'estudiante'
            ? '¡Hola! Soy **ZuraAI**, tu tutor académico personal. Puedo explicarte temas, ayudarte con tareas, prepararte para exámenes y mucho más. ¿Con qué empezamos?'
            : '¡Hola! Soy **ZuraAI**, tu asistente de apoyo familiar. Puedo ayudarte a entender el desempeño de tu hijo/a, darte consejos para apoyarlo en casa y orientarte sobre la escuela. ¿En qué te ayudo?';
        appendMessage('assistant', msg);
    }

    // ── Suggestion chips ─────────────────────────────────────────────────
    suggsEl.querySelectorAll('.zura-suggestion').forEach(chip => {
        chip.addEventListener('click', () => {
            if (streaming) return;
            inputEl.value = chip.textContent;
            autoResizeInput();
            sendMessage();
        });
    });

    // ── Input auto-resize ─────────────────────────────────────────────────
    inputEl.addEventListener('input', autoResizeInput);
    function autoResizeInput() {
        inputEl.style.height = 'auto';
        inputEl.style.height = Math.min(inputEl.scrollHeight, 100) + 'px';
    }

    // ── Send on Enter (Shift+Enter = newline) ────────────────────────────
    inputEl.addEventListener('keydown', e => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            if (!streaming) sendMessage();
        }
    });
    sendBtn.addEventListener('click', () => { if (!streaming) sendMessage(); });

    // ── Send message ──────────────────────────────────────────────────────
    async function sendMessage() {
        const text = inputEl.value.trim();
        if (!text || streaming) return;

        inputEl.value = '';
        inputEl.style.height = 'auto';
        suggsEl.style.display = 'none';

        appendMessage('user', text);

        streaming = true;
        sendBtn.disabled = true;

        // Typing indicator
        const typingEl = appendTyping();
        scrollBottom();

        let fullText = '';
        let assistantBubble = null;

        try {
            const res = await fetch(CHAT_URL, {
                method:  'POST',
                headers: {
                    'Content-Type':  'application/json',
                    'X-CSRF-TOKEN':  CSRF,
                    'Accept':        'text/event-stream',
                },
                body: JSON.stringify({ message: text, history: history.slice(-10) }),
            });

            if (!res.ok) {
                typingEl.remove();
                appendMessage('assistant', 'Error al conectar con ZuraAI. Verifica la configuración.');
                return;
            }

            typingEl.remove();
            assistantBubble = appendMessage('assistant', '');

            const reader = res.body.getReader();
            const dec    = new TextDecoder();
            let buf      = '';

            while (true) {
                const { done, value } = await reader.read();
                if (done) break;
                buf += dec.decode(value, { stream: true });

                const lines = buf.split('\n');
                buf = lines.pop();

                for (const line of lines) {
                    if (!line.startsWith('data: ')) continue;
                    const raw = line.slice(6).trim();
                    if (!raw || raw === '[DONE]') continue;
                    try {
                        const evt = JSON.parse(raw);
                        if (evt.type === 'content_block_delta' && evt.delta?.type === 'text_delta') {
                            fullText += evt.delta.text;
                            assistantBubble.innerHTML = renderMd(fullText);
                            scrollBottom();
                        }
                        if (evt.type === 'error') {
                            fullText = evt.error?.message ?? 'Error desconocido.';
                            assistantBubble.innerHTML = renderMd(fullText);
                        }
                    } catch (_) {}
                }
            }

        } catch (err) {
            if (typingEl.parentNode) typingEl.remove();
            const errMsg = 'Error de conexión. Intenta de nuevo.';
            if (assistantBubble) assistantBubble.innerHTML = renderMd(errMsg);
            else appendMessage('assistant', errMsg);
        } finally {
            streaming = false;
            sendBtn.disabled = false;
            if (fullText) {
                history.push({ role: 'user',      content: text     });
                history.push({ role: 'assistant', content: fullText });
                if (history.length > 20) history = history.slice(-20);
                // Mostrar sugerencias rápidas de seguimiento
                showFollowUpSuggs();
            }
            scrollBottom();
        }
    }

    // ── Sugerencias de seguimiento ────────────────────────────────────────
    function showFollowUpSuggs() {
        // Elimina sugerencias anteriores de follow-up
        messagesEl.querySelectorAll('.zura-followup').forEach(el => el.remove());
        const ZURA_ROLE_LOCAL = "{{ $__zuraRole }}";
        const chips = ZURA_ROLE_LOCAL === 'docente'
            ? ['Más detalle','Otro ejemplo','Adaptarlo al grado','Simplificarlo']
            : ZURA_ROLE_LOCAL === 'estudiante'
            ? ['Dame un ejemplo','Explícalo más simple','Ejercicio práctico','Cómo lo recuerdo']
            : ['Más consejos','Qué hacer si…','Dónde lo consulto','Otro tema'];
        const row = document.createElement('div');
        row.className = 'zura-followup';
        row.style.cssText = 'display:flex;flex-wrap:wrap;gap:5px;padding:4px 10px 8px;';
        chips.forEach(txt => {
            const btn = document.createElement('button');
            btn.className = 'zura-suggestion';
            btn.style.cssText = 'font-size:.7rem;padding:3px 9px;opacity:.85;';
            btn.textContent = txt;
            btn.addEventListener('click', () => {
                if (streaming) return;
                inputEl.value = txt;
                autoResizeInput();
                row.remove();
                sendMessage();
            });
            row.appendChild(btn);
        });
        messagesEl.appendChild(row);
        scrollBottom();
    }

    // ── DOM helpers ───────────────────────────────────────────────────────
    function appendMessage(role, text) {
        const wrap   = document.createElement('div');
        wrap.className = 'zura-msg ' + role;

        if (role === 'assistant') {
            const av = document.createElement('div');
            av.className = 'zura-avatar';
            av.innerHTML = '<i class="bi bi-stars" style="font-size:.65rem;"></i>';
            wrap.appendChild(av);
        }

        const bubble = document.createElement('div');
        bubble.className = 'zura-bubble';
        bubble.innerHTML = renderMd(text);
        wrap.appendChild(bubble);

        messagesEl.appendChild(wrap);
        scrollBottom();
        return bubble;
    }

    function appendTyping() {
        const wrap = document.createElement('div');
        wrap.className = 'zura-msg assistant';
        const av = document.createElement('div');
        av.className = 'zura-avatar';
        av.innerHTML = '<i class="bi bi-stars" style="font-size:.65rem;"></i>';
        wrap.appendChild(av);
        const bubble = document.createElement('div');
        bubble.className = 'zura-bubble';
        bubble.innerHTML = '<div class="zura-typing"><span></span><span></span><span></span></div>';
        wrap.appendChild(bubble);
        messagesEl.appendChild(wrap);
        scrollBottom();
        return wrap;
    }

    function scrollBottom() {
        messagesEl.scrollTop = messagesEl.scrollHeight;
    }

    // ── Minimal markdown renderer ─────────────────────────────────────────
    function renderMd(text) {
        if (!text) return '';
        return text
            // Escape HTML
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            // Code blocks (```...```)
            .replace(/```[\s\S]*?```/g, m => {
                const code = m.slice(3, -3).replace(/^\w*\n/, '');
                return '<pre style="background:rgba(0,0,0,.07);padding:.4rem .6rem;border-radius:6px;overflow-x:auto;font-size:.78rem;margin:.3rem 0;"><code>' + code + '</code></pre>';
            })
            // Bold
            .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
            // Inline code
            .replace(/`([^`]+)`/g, '<code>$1</code>')
            // Headers ## / ###
            .replace(/^### (.+)$/gm, '<strong style="display:block;font-size:.84rem;margin-top:.4rem;">$1</strong>')
            .replace(/^## (.+)$/gm,  '<strong style="display:block;font-size:.88rem;margin-top:.5rem;">$1</strong>')
            // List items
            .replace(/^[-*] (.+)$/gm, '• $1')
            // Line breaks
            .replace(/\n\n/g, '<br><br>').replace(/\n/g, '<br>');
    }
})();
</script>
@endif
@endauth
</body>
</html>
