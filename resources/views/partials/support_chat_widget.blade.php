{{-- ── Widget de Chat de Primera Asistencia ────────────────────────────────
     Incluir en páginas públicas (landing, inscripción).
     Requiere: Bootstrap Icons CDN o local, fetch API.
     Variables opcionales: $chatColor (hex), $chatBienvenida (string)
 ──────────────────────────────────────────────────────────────────────── --}}

@php
    $chatColor     = $chatColor     ?? '#1e3a6e';
    $chatAccent    = $chatAccent    ?? '#3b82f6';
    $chatBienvenida = $chatBienvenida ?? ('¡Hola! 👋 Soy del equipo de ' . (\App\Helpers\Setting::get('system_name', 'ZuraEdu')) . '. ¿En qué te puedo ayudar hoy?');
    $csrfToken     = csrf_token();
@endphp

<style>
#sc-widget { position:fixed; bottom:1.5rem; right:1.5rem; z-index:10000; display:flex; flex-direction:column; align-items:flex-end; gap:.6rem; font-family:'Inter',system-ui,sans-serif; }
#sc-btn    { width:56px;height:56px;background:linear-gradient(135deg,{{ $chatColor }},{{ $chatAccent }});border:none;border-radius:50%;color:#fff;box-shadow:0 6px 20px rgba(59,130,246,.45);cursor:pointer;display:flex;align-items:center;justify-content:center;transition:transform .2s;position:relative; }
#sc-btn:hover { transform:scale(1.08); }
#sc-badge  { position:absolute;top:-3px;right:-3px;background:#ef4444;color:#fff;font-size:.58rem;font-weight:700;min-width:17px;height:17px;border-radius:99px;display:none;align-items:center;justify-content:center;border:2px solid #fff; }
#sc-panel  { width:340px;max-height:520px;background:#fff;border-radius:18px;box-shadow:0 10px 40px rgba(0,0,0,.18);display:none;flex-direction:column;overflow:hidden;border:1px solid #e2e8f0; }
#sc-header { background:linear-gradient(135deg,{{ $chatColor }},{{ $chatAccent }});padding:.9rem 1rem;display:flex;align-items:center;gap:.7rem; }
#sc-avatar { width:36px;height:36px;background:rgba(255,255,255,.25);border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0; }
#sc-status { width:8px;height:8px;background:#4ade80;border-radius:50%;animation:scPulse 2s infinite;flex-shrink:0; }
#sc-body   { flex:1;overflow-y:auto;padding:.8rem;display:flex;flex-direction:column;gap:.5rem;background:#f8fafc;min-height:180px;max-height:310px; }
#sc-form-inicio { padding:.8rem;border-top:1px solid #e2e8f0;background:#fff; }
#sc-form-msg    { padding:.6rem .75rem;border-top:1px solid #e2e8f0;background:#fff;display:none;gap:.5rem;align-items:center; }
.sc-bubble { max-width:82%;padding:.45rem .75rem;border-radius:14px;font-size:.82rem;line-height:1.45;word-break:break-word; }
.sc-bubble.admin   { background:#fff;color:#1e293b;border-radius:14px 14px 14px 4px;box-shadow:0 1px 3px rgba(0,0,0,.08);align-self:flex-start; }
.sc-bubble.visitor { background:{{ $chatAccent }};color:#fff;border-radius:14px 14px 4px 14px;align-self:flex-end; }
.sc-label  { font-size:.63rem;color:#94a3b8;margin-bottom:2px; }
.sc-label.visitor { text-align:right; }
@keyframes scPulse { 0%,100%{opacity:1} 50%{opacity:.4} }
</style>

<div id="sc-widget">
    {{-- Panel --}}
    <div id="sc-panel">
        {{-- Header --}}
        <div id="sc-header">
            <div id="sc-avatar"><i class="bi bi-headset" style="color:#fff;font-size:1rem;"></i></div>
            <div style="flex:1;min-width:0;">
                <div style="font-weight:700;color:#fff;font-size:.87rem;">Chat de Asistencia</div>
                <div style="display:flex;align-items:center;gap:.35rem;margin-top:2px;">
                    <div id="sc-status"></div>
                    <span style="font-size:.69rem;color:rgba(255,255,255,.8);">En línea — respondemos rápido</span>
                </div>
            </div>
            <button onclick="scToggle()" style="background:none;border:none;color:rgba(255,255,255,.7);cursor:pointer;font-size:1.1rem;padding:.2rem;" title="Cerrar"><i class="bi bi-x-lg"></i></button>
        </div>

        {{-- Mensajes --}}
        <div id="sc-body">
            {{-- Bienvenida --}}
            <div>
                <div class="sc-label">Soporte</div>
                <div class="sc-bubble admin">{{ $chatBienvenida }}</div>
            </div>
        </div>

        {{-- Formulario de inicio (nombre + primer mensaje) --}}
        <div id="sc-form-inicio">
            <div style="display:flex;flex-direction:column;gap:.5rem;">
                <input id="sc-nombre" type="text" placeholder="Tu nombre *" maxlength="120"
                       style="border:1px solid #e2e8f0;border-radius:8px;padding:.4rem .7rem;font-size:.82rem;outline:none;">
                <input id="sc-email" type="email" placeholder="Tu email (opcional)" maxlength="180"
                       style="border:1px solid #e2e8f0;border-radius:8px;padding:.4rem .7rem;font-size:.82rem;outline:none;">
                <div style="display:flex;gap:.4rem;">
                    <input id="sc-primer-msg" type="text" placeholder="¿En qué te ayudamos? *" maxlength="2000"
                           style="flex:1;border:1px solid #e2e8f0;border-radius:8px;padding:.4rem .7rem;font-size:.82rem;outline:none;">
                    <button onclick="scStart()" id="sc-btn-start"
                            style="background:{{ $chatAccent }};border:none;color:#fff;border-radius:8px;width:36px;height:36px;display:flex;align-items:center;justify-content:center;cursor:pointer;flex-shrink:0;">
                        <i class="bi bi-send-fill" style="font-size:.8rem;"></i>
                    </button>
                </div>
                <div id="sc-error" style="color:#dc2626;font-size:.73rem;display:none;"></div>
            </div>
        </div>

        {{-- Input de mensajes continuos --}}
        <div id="sc-form-msg" style="display:none;padding:.6rem .75rem;border-top:1px solid #e2e8f0;background:#fff;display:none;gap:.5rem;align-items:center;">
            <input id="sc-msg-input" type="text" placeholder="Escribe un mensaje..." maxlength="2000" autocomplete="off"
                   style="flex:1;border:1px solid #e2e8f0;border-radius:10px;padding:.42rem .75rem;font-size:.82rem;outline:none;">
            <button onclick="scSend()" style="background:{{ $chatAccent }};border:none;color:#fff;border-radius:10px;width:34px;height:34px;display:flex;align-items:center;justify-content:center;cursor:pointer;flex-shrink:0;">
                <i class="bi bi-send-fill" style="font-size:.78rem;"></i>
            </button>
        </div>
    </div>

    {{-- Botón flotante --}}
    <button id="sc-btn" onclick="scToggle()" title="Chat de asistencia">
        <i class="bi bi-chat-dots-fill" style="font-size:1.25rem;"></i>
        <span id="sc-badge"></span>
    </button>
</div>

<script>
(function () {
    const _URL_START    = '{{ route('support.chat.start') }}';
    const _URL_SEND     = (token) => `/soporte/chat/${token}/mensaje`;
    const _URL_MSGS     = (token) => `/soporte/chat/${token}/mensajes`;
    const _CSRF         = '{{ $csrfToken }}';
    const _REVERB_KEY   = '{{ config('reverb.apps.apps.0.key', '') }}';
    const _REVERB_HOST  = '{{ config('reverb.servers.reverb.host', 'localhost') }}';
    const _REVERB_PORT  = {{ config('reverb.servers.reverb.port', 8080) }};

    let _token   = localStorage.getItem('sc_token') ?? null;
    let _open    = false;
    let _echoSub = null;
    let _newMsgs = 0;

    // ── Toggle panel ──────────────────────────────────────────────────────
    window.scToggle = function () {
        _open = !_open;
        const panel = document.getElementById('sc-panel');
        panel.style.display = _open ? 'flex' : 'none';
        panel.style.flexDirection = 'column';
        document.getElementById('sc-btn').querySelector('.bi').className =
            _open ? 'bi bi-x-lg' : 'bi bi-chat-dots-fill';
        if (_open) {
            clearBadge();
            if (_token) { loadMessages(); }
            setTimeout(() => {
                const el = _token
                    ? document.getElementById('sc-msg-input')
                    : document.getElementById('sc-nombre');
                el?.focus();
            }, 80);
        }
    };

    // ── Iniciar conversación ──────────────────────────────────────────────
    window.scStart = async function () {
        const nombre = document.getElementById('sc-nombre').value.trim();
        const email  = document.getElementById('sc-email').value.trim();
        const msg    = document.getElementById('sc-primer-msg').value.trim();
        const errEl  = document.getElementById('sc-error');
        errEl.style.display = 'none';

        if (!nombre) { showError('Por favor escribe tu nombre.'); return; }
        if (!msg)    { showError('Escribe tu consulta primero.'); return; }

        setBtnLoading(true);
        try {
            const res = await fetch(_URL_START, {
                method:  'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': _CSRF, 'Accept': 'application/json' },
                body:    JSON.stringify({ nombre, email: email || null, mensaje: msg }),
            });
            const data = await res.json();
            if (!res.ok) throw new Error(data.message ?? 'Error');

            _token = data.token;
            localStorage.setItem('sc_token', _token);

            document.getElementById('sc-form-inicio').style.display = 'none';
            document.getElementById('sc-form-msg').style.display = 'flex';
            appendBubble({ origen: 'visitor', mensaje: msg, hora: now(), user_name: nombre });
            subscribeEcho();
        } catch (e) {
            showError('No pudimos enviar tu mensaje. Inténtalo de nuevo.');
        } finally {
            setBtnLoading(false);
        }
    };

    // ── Enviar mensaje continuo ───────────────────────────────────────────
    window.scSend = async function () {
        if (!_token) return;
        const input = document.getElementById('sc-msg-input');
        const msg   = input.value.trim();
        if (!msg) return;
        input.value = '';

        appendBubble({ origen: 'visitor', mensaje: msg, hora: now() });

        try {
            await fetch(_URL_SEND(_token), {
                method:  'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': _CSRF, 'Accept': 'application/json' },
                body:    JSON.stringify({ mensaje: msg }),
            });
        } catch {}
    };

    // ── Cargar historial al abrir ─────────────────────────────────────────
    async function loadMessages() {
        document.getElementById('sc-form-inicio').style.display = 'none';
        document.getElementById('sc-form-msg').style.display = 'flex';
        try {
            const res  = await fetch(_URL_MSGS(_token), { headers: { 'Accept': 'application/json' } });
            const msgs = await res.json();
            if (!res.ok) { resetSession(); return; }
            const body = document.getElementById('sc-body');
            // Limpiar el mensaje de bienvenida si hay historial
            if (msgs.length) body.querySelectorAll('.sc-bubble.admin, .sc-label').forEach(el => {
                if (el.closest('#sc-body')?.children.length <= 2) el.parentElement?.remove();
            });
            msgs.forEach(m => appendBubble(m, false));
            scrollBottom();
            subscribeEcho();
        } catch { resetSession(); }
    }

    // ── Suscribirse a respuestas del admin vía Echo/Reverb ────────────────
    function subscribeEcho() {
        if (_echoSub || !window.Echo || !_token) return;
        try {
            _echoSub = window.Echo
                .channel(`support.${_token}`)
                .listen('.admin.reply', (data) => {
                    appendBubble({ origen: 'admin', mensaje: data.mensaje, hora: data.hora, user_name: data.admin_nombre });
                    if (!_open) incrementBadge();
                });
        } catch {}
    }

    // ── Helpers ───────────────────────────────────────────────────────────
    function appendBubble(m, scroll = true) {
        const body  = document.getElementById('sc-body');
        const isAdmin = m.origen === 'admin';
        const wrap  = document.createElement('div');
        wrap.style.cssText = `display:flex;flex-direction:column;align-items:${isAdmin ? 'flex-start' : 'flex-end'};`;
        if (isAdmin && m.user_name) {
            const lbl = document.createElement('div');
            lbl.className = 'sc-label';
            lbl.textContent = m.user_name;
            wrap.appendChild(lbl);
        }
        const bub = document.createElement('div');
        bub.className = `sc-bubble ${isAdmin ? 'admin' : 'visitor'}`;
        bub.textContent = m.mensaje;
        wrap.appendChild(bub);
        const hora = document.createElement('div');
        hora.style.cssText = 'font-size:.6rem;color:#94a3b8;margin-top:2px;';
        hora.textContent = m.hora ?? '';
        wrap.appendChild(hora);
        body.appendChild(wrap);
        if (scroll) scrollBottom();
    }

    function scrollBottom() {
        const b = document.getElementById('sc-body');
        if (b) b.scrollTop = b.scrollHeight;
    }

    function now() {
        return new Date().toLocaleTimeString('es-DO', { hour: '2-digit', minute: '2-digit' });
    }

    function showError(msg) {
        const el = document.getElementById('sc-error');
        if (el) { el.textContent = msg; el.style.display = 'block'; }
    }

    function setBtnLoading(loading) {
        const btn = document.getElementById('sc-btn-start');
        if (btn) btn.disabled = loading;
    }

    function incrementBadge() {
        _newMsgs++;
        const b = document.getElementById('sc-badge');
        if (b) { b.textContent = _newMsgs > 9 ? '9+' : String(_newMsgs); b.style.display = 'flex'; }
    }

    function clearBadge() {
        _newMsgs = 0;
        const b = document.getElementById('sc-badge');
        if (b) { b.style.display = 'none'; }
    }

    function resetSession() {
        localStorage.removeItem('sc_token');
        _token = null;
        document.getElementById('sc-form-inicio').style.display = 'block';
        document.getElementById('sc-form-msg').style.display = 'none';
    }

    // ── Enter para enviar ─────────────────────────────────────────────────
    document.addEventListener('keydown', function (e) {
        if (e.key !== 'Enter') return;
        if (document.activeElement?.id === 'sc-primer-msg') scStart();
        if (document.activeElement?.id === 'sc-msg-input')  scSend();
    });

    // ── Si ya hay token previo, ajustar UI ────────────────────────────────
    if (_token) {
        document.getElementById('sc-form-inicio').style.display = 'none';
        document.getElementById('sc-form-msg').style.display = 'flex';
    }

    // ── Inicializar Echo para canal público ───────────────────────────────
    document.addEventListener('DOMContentLoaded', function () {
        if (!window.Pusher) return;
        if (!window.Echo) {
            window.Echo = new window.Echo({
                broadcaster:       'reverb',
                key:               _REVERB_KEY,
                wsHost:            _REVERB_HOST,
                wsPort:            _REVERB_PORT,
                wssPort:           _REVERB_PORT,
                forceTLS:          false,
                enabledTransports: ['ws', 'wss'],
            });
        }
        if (_token) subscribeEcho();
    });
})();
</script>
