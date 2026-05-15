@extends('layouts.admin')
@section('title', 'Chat de Soporte')

@section('content')

<style>
/* ── Layout ─────────────────────────────────────────────────────────── */
#sc-wrap { display:flex; height:calc(100vh - 110px); gap:0; background:#fff; border-radius:1rem; overflow:hidden; box-shadow:0 2px 12px rgba(0,0,0,.07); border:1px solid #e2e8f0; }

/* ── Panel izquierdo ─────────────────────────────────────────────────── */
#sc-left { width:300px; min-width:260px; display:flex; flex-direction:column; border-right:1px solid #e2e8f0; background:#fff; }
#sc-left-header { padding:.9rem 1rem .6rem; border-bottom:1px solid #e2e8f0; }
#sc-left-title { font-size:.88rem; font-weight:700; color:#1e293b; display:flex; align-items:center; gap:.45rem; margin-bottom:.6rem; }
.sc-dot { width:8px; height:8px; background:#22c55e; border-radius:50%; animation:scPulse 2s infinite; }

/* Tabs */
#sc-tabs { display:flex; gap:.25rem; }
.sc-tab { flex:1; padding:.28rem 0; font-size:.72rem; font-weight:600; border:1px solid #e2e8f0; border-radius:.5rem; background:#f8fafc; color:#64748b; cursor:pointer; transition:all .15s; text-align:center; }
.sc-tab.active { background:#1d4ed8; color:#fff; border-color:#1d4ed8; }
.sc-tab .sc-tab-count { background:rgba(255,255,255,.25); border-radius:99px; padding:0 5px; font-size:.65rem; margin-left:.2rem; }
.sc-tab:not(.active) .sc-tab-count { background:#e2e8f0; color:#475569; }

/* Buscador */
#sc-search-wrap { padding:.45rem .7rem; border-bottom:1px solid #f1f5f9; }

/* Lista */
#sc-list { flex:1; overflow-y:auto; padding:.35rem .45rem; }
.sc-card { padding:.6rem .7rem; border-radius:.6rem; margin-bottom:.25rem; cursor:pointer; border:1px solid transparent; transition:background .12s, border-color .12s; }
.sc-card:hover { background:#f8fafc; }
.sc-card.active { background:#eff6ff; border-color:#bfdbfe; }
.sc-card-name { font-size:.82rem; font-weight:600; color:#1e293b; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.sc-card-sub { font-size:.67rem; color:#94a3b8; margin-top:.1rem; display:flex; align-items:center; gap:.3rem; }
.sc-status-dot { width:6px; height:6px; border-radius:50%; flex-shrink:0; }
.sc-status-open { background:#22c55e; }
.sc-status-resolved { background:#94a3b8; }
.sc-unread { background:#ef4444; color:#fff; border-radius:99px; font-size:.6rem; font-weight:700; min-width:17px; height:17px; display:flex; align-items:center; justify-content:center; padding:0 4px; flex-shrink:0; }

/* ── Panel derecho ───────────────────────────────────────────────────── */
#sc-right { flex:1; display:flex; flex-direction:column; background:#f8fafc; min-width:0; }
#sc-empty { flex:1; display:flex; flex-direction:column; align-items:center; justify-content:center; color:#94a3b8; }
#sc-conv { display:none; flex:1; flex-direction:column; }

/* Cabecera conversación */
#sc-conv-header { padding:.75rem 1.1rem; border-bottom:1px solid #e2e8f0; background:#fff; display:flex; align-items:center; gap:.65rem; }
#sc-conv-avatar { width:36px; height:36px; background:#dbeafe; border-radius:50%; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
#sc-conv-name { font-weight:700; font-size:.875rem; color:#1e293b; }
#sc-conv-sub { font-size:.72rem; color:#64748b; }
#sc-resolved-banner { background:#f0fdf4; border:1px solid #bbf7d0; border-radius:.5rem; padding:.3rem .7rem; font-size:.75rem; color:#166534; font-weight:600; display:none; }

/* Mensajes */
#sc-msgs { flex:1; overflow-y:auto; padding:1rem; display:flex; flex-direction:column; gap:.55rem; }
.sc-msg-wrap { display:flex; flex-direction:column; }
.sc-msg-wrap.admin { align-items:flex-end; }
.sc-msg-wrap.visitor { align-items:flex-start; }
.sc-msg-sender { font-size:.63rem; color:#64748b; font-weight:600; margin-bottom:2px; }
.sc-bubble { max-width:74%; padding:.42rem .8rem; font-size:.83rem; line-height:1.48; word-break:break-word; box-shadow:0 1px 3px rgba(0,0,0,.07); }
.sc-bubble.admin { background:#3b82f6; color:#fff; border-radius:14px 14px 4px 14px; }
.sc-bubble.visitor { background:#fff; color:#1e293b; border-radius:14px 14px 14px 4px; }
.sc-msg-time { font-size:.61rem; color:#94a3b8; margin-top:3px; }

/* Input area */
#sc-input-area { padding:.65rem .9rem; border-top:1px solid #e2e8f0; background:#fff; display:flex; gap:.45rem; align-items:center; }
#sc-reply { flex:1; border:1px solid #e2e8f0; border-radius:10px; padding:.45rem .8rem; font-size:.85rem; outline:none; resize:none; height:38px; line-height:1.4; transition:border-color .15s; }
#sc-reply:focus { border-color:#3b82f6; }
#sc-reply:disabled { background:#f1f5f9; cursor:not-allowed; }
#sc-send-btn { background:#3b82f6; color:#fff; border:none; border-radius:10px; width:38px; height:38px; display:flex; align-items:center; justify-content:center; cursor:pointer; flex-shrink:0; transition:background .15s; }
#sc-send-btn:hover:not(:disabled) { background:#2563eb; }
#sc-send-btn:disabled { background:#cbd5e1; cursor:not-allowed; }
#sc-resolved-notice { flex:1; text-align:center; font-size:.8rem; color:#64748b; padding:.45rem; }

@keyframes scPulse { 0%,100%{opacity:1} 50%{opacity:.35} }
</style>

<div id="sc-wrap">

    {{-- ── Izquierda ─────────────────────────────────────────────────────── --}}
    <div id="sc-left">
        <div id="sc-left-header">
            <div id="sc-left-title">
                <i class="bi bi-headset text-primary"></i>
                Chat de Soporte
                <div class="sc-dot ms-auto"></div>
            </div>
            <div id="sc-tabs">
                <button class="sc-tab active" data-status="open"     onclick="switchTab('open')">
                    Abiertas <span class="sc-tab-count" id="tab-count-open">0</span>
                </button>
                <button class="sc-tab"         data-status="resolved" onclick="switchTab('resolved')">
                    Resueltas <span class="sc-tab-count" id="tab-count-resolved">0</span>
                </button>
            </div>
        </div>

        <div id="sc-search-wrap">
            <input id="sc-search" type="search" class="form-control form-control-sm"
                   placeholder="Buscar visitante..."
                   oninput="filterCards(this.value)">
        </div>

        <div id="sc-list">
            <div id="sc-list-empty" class="text-center text-muted small py-4">
                <i class="bi bi-chat-square-text" style="font-size:2rem;opacity:.25;display:block;margin-bottom:.4rem;"></i>
                Sin conversaciones
            </div>
        </div>
    </div>

    {{-- ── Derecha ────────────────────────────────────────────────────────── --}}
    <div id="sc-right">

        {{-- Estado vacío --}}
        <div id="sc-empty">
            <i class="bi bi-chat-dots" style="font-size:3rem;margin-bottom:.75rem;opacity:.25;"></i>
            <p class="mb-0" style="font-size:.88rem;">Selecciona una conversación</p>
        </div>

        {{-- Conversación activa --}}
        <div id="sc-conv">
            <div id="sc-conv-header">
                <div id="sc-conv-avatar">
                    <i class="bi bi-person-fill" style="color:#3b82f6;font-size:1.1rem;"></i>
                </div>
                <div style="flex:1;min-width:0;">
                    <div id="sc-conv-name">—</div>
                    <div id="sc-conv-sub"></div>
                </div>
                <div id="sc-resolved-banner">
                    <i class="bi bi-check2-all me-1"></i>Resuelta
                </div>
                <button id="sc-close-btn" onclick="closeSession()"
                        class="btn btn-sm btn-outline-success ms-2"
                        style="font-size:.78rem;">
                    <i class="bi bi-check2-all me-1"></i>Resolver
                </button>
            </div>

            <div id="sc-msgs"></div>

            <div id="sc-input-area">
                <input id="sc-reply" type="text" placeholder="Escribe tu respuesta…" maxlength="2000" autocomplete="off"
                       onkeydown="if(event.key==='Enter'&&!event.shiftKey){event.preventDefault();sendReply();}">
                <button id="sc-send-btn" onclick="sendReply()">
                    <i class="bi bi-send-fill" style="font-size:.8rem;"></i>
                </button>
            </div>
        </div>

    </div>
</div>

@push('scripts')
<script>
// ── Estado global ──────────────────────────────────────────────────────────
const _SESSIONS_URL = (st) => `{{ route('admin.soporte.chat.sessions') }}?status=${st}`;
const _MSGS_URL     = (id) => `/admin/soporte/chat/${id}/mensajes`;
const _REPLY_URL    = (id) => `/admin/soporte/chat/${id}/reply`;
const _CLOSE_URL    = (id) => `/admin/soporte/chat/${id}/close`;
const _CSRF         = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

let _sessions      = [];
let _currentTab    = 'open';
let _activeId      = null;
let _activeStatus  = null;
let _pollTimer     = null;
let _echoSub       = null;
let _notifyEnabled = false;

// Audio beep vía Web Audio API (sin archivo externo)
function playBeep() {
    try {
        const ctx = new (window.AudioContext || window.webkitAudioContext)();
        const osc = ctx.createOscillator();
        const gain = ctx.createGain();
        osc.connect(gain); gain.connect(ctx.destination);
        osc.type = 'sine'; osc.frequency.value = 880;
        gain.gain.setValueAtTime(0, ctx.currentTime);
        gain.gain.linearRampToValueAtTime(.18, ctx.currentTime + .02);
        gain.gain.exponentialRampToValueAtTime(.0001, ctx.currentTime + .4);
        osc.start(ctx.currentTime); osc.stop(ctx.currentTime + .4);
    } catch {}
}

// ── Init ───────────────────────────────────────────────────────────────────
(async function init() {
    await loadSessions();
    subscribeEcho();
    _pollTimer = setInterval(async () => {
        const prevOpenCount = _sessions.filter(s => s.status === 'open').length;
        await loadSessions(false);
        const newOpenCount = _sessions.filter(s => s.status === 'open').length;
        if (newOpenCount > prevOpenCount) playBeep();
    }, 12000);
    // Habilitar notificaciones del navegador
    if ('Notification' in window && Notification.permission === 'default') {
        Notification.requestPermission().then(p => { _notifyEnabled = p === 'granted'; });
    } else {
        _notifyEnabled = Notification.permission === 'granted';
    }
})();

// ── Cargar sesiones ────────────────────────────────────────────────────────
async function loadSessions(renderTabCounts = true) {
    try {
        const [resOpen, resResolved] = await Promise.all([
            fetch(_SESSIONS_URL('open'),     { headers: { Accept: 'application/json' } }),
            fetch(_SESSIONS_URL('resolved'), { headers: { Accept: 'application/json' } }),
        ]);
        const open     = await resOpen.json();
        const resolved = await resResolved.json();
        _sessions = [...open.map(s => ({...s, status:'open'})), ...resolved.map(s => ({...s, status:'resolved'}))];

        if (renderTabCounts) {
            document.getElementById('tab-count-open').textContent     = open.length;
            document.getElementById('tab-count-resolved').textContent = resolved.length;
        }

        renderList();
    } catch {}
}

// ── Cambiar tab ────────────────────────────────────────────────────────────
function switchTab(status) {
    _currentTab = status;
    document.querySelectorAll('.sc-tab').forEach(t => t.classList.toggle('active', t.dataset.status === status));
    renderList();
}

// ── Renderizar lista ───────────────────────────────────────────────────────
function renderList(filter = '') {
    const list    = document.getElementById('sc-list');
    const empty   = document.getElementById('sc-list-empty');
    const visible = _sessions.filter(s => {
        if (s.status !== _currentTab) return false;
        if (!filter) return true;
        const q = filter.toLowerCase();
        return s.visitor_nombre.toLowerCase().includes(q)
            || (s.visitor_email ?? '').toLowerCase().includes(q);
    });

    empty.style.display = visible.length ? 'none' : 'block';

    // Remover cards que ya no aplican
    list.querySelectorAll('.sc-card').forEach(el => {
        if (!visible.find(s => String(s.id) === el.dataset.id)) el.remove();
    });

    visible.forEach(s => {
        let card = list.querySelector(`.sc-card[data-id="${s.id}"]`);
        if (!card) {
            card = document.createElement('div');
            card.className = 'sc-card';
            card.dataset.id = s.id;
            list.appendChild(card);
        }
        card.classList.toggle('active', _activeId === s.id);
        card.onclick = () => openSession(s);

        const unreadHtml = s.sin_leer > 0
            ? `<span class="sc-unread ms-auto">${s.sin_leer}</span>` : '';
        const agentHtml = s.status === 'resolved' && s.atendido_por_nombre
            ? `<i class="bi bi-person-check-fill me-1" style="font-size:.6rem;"></i>${esc(s.atendido_por_nombre)}`
            : s.ultimo_mensaje;

        card.innerHTML = `
            <div style="display:flex;align-items:center;gap:.5rem;">
                <div class="sc-status-dot sc-status-${s.status}"></div>
                <div style="flex:1;min-width:0;">
                    <div class="sc-card-name">${esc(s.visitor_nombre)}</div>
                    <div class="sc-card-sub">${agentHtml}${unreadHtml}</div>
                </div>
            </div>`;
    });
}

function filterCards(q) { renderList(q); }

// ── Abrir sesión ───────────────────────────────────────────────────────────
async function openSession(s) {
    _activeId     = s.id;
    _activeStatus = s.status;

    document.getElementById('sc-empty').style.display  = 'none';
    document.getElementById('sc-conv').style.display   = 'flex';
    document.getElementById('sc-conv-name').textContent = s.visitor_nombre;
    document.getElementById('sc-conv-sub').textContent  =
        [s.visitor_email, s.visitor_telefono].filter(Boolean).join(' · ') || 'Sin contacto adicional';

    const isResolved = s.status === 'resolved';
    document.getElementById('sc-resolved-banner').style.display = isResolved ? '' : 'none';
    document.getElementById('sc-close-btn').style.display       = isResolved ? 'none' : '';
    const replyInput = document.getElementById('sc-reply');
    const sendBtn    = document.getElementById('sc-send-btn');
    replyInput.disabled = isResolved;
    sendBtn.disabled    = isResolved;
    replyInput.placeholder = isResolved ? 'Sesión cerrada — solo lectura' : 'Escribe tu respuesta…';

    renderList(document.getElementById('sc-search').value);
    await loadMessages(s.id);
    if (!isResolved) replyInput.focus();
}

// ── Cargar mensajes ────────────────────────────────────────────────────────
async function loadMessages(sessionId) {
    try {
        const res  = await fetch(_MSGS_URL(sessionId), { headers: { Accept: 'application/json' } });
        const msgs = await res.json();
        const area = document.getElementById('sc-msgs');
        area.innerHTML = '';
        msgs.forEach(m => appendMsg(m, false));
        area.scrollTop = area.scrollHeight;
    } catch {}
}

function appendMsg(m, scroll = true) {
    const area    = document.getElementById('sc-msgs');
    const isAdmin = m.origen === 'admin';
    const wrap    = document.createElement('div');
    wrap.className = `sc-msg-wrap ${isAdmin ? 'admin' : 'visitor'}`;
    wrap.innerHTML = `
        <div class="sc-msg-sender">${esc(m.user_name ?? (isAdmin ? 'Soporte' : ''))}</div>
        <div class="sc-bubble ${isAdmin ? 'admin' : 'visitor'}">${esc(m.mensaje)}</div>
        <div class="sc-msg-time">${m.hora ?? ''}</div>`;
    area.appendChild(wrap);
    if (scroll) area.scrollTop = area.scrollHeight;
}

// ── Enviar respuesta ───────────────────────────────────────────────────────
async function sendReply() {
    if (!_activeId || _activeStatus === 'resolved') return;
    const input = document.getElementById('sc-reply');
    const msg   = input.value.trim();
    if (!msg) return;
    input.value = '';

    try {
        const res  = await fetch(_REPLY_URL(_activeId), {
            method:  'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': _CSRF, Accept: 'application/json' },
            body:    JSON.stringify({ mensaje: msg }),
        });
        if (res.status === 422) {
            const err = await res.json();
            alert(err.message ?? 'No se pudo enviar.');
            return;
        }
        const data = await res.json();
        appendMsg(data, true);
    } catch {}
}

// ── Cerrar sesión ──────────────────────────────────────────────────────────
async function closeSession() {
    if (!_activeId) return;
    if (!confirm('¿Marcar esta conversación como resuelta?')) return;
    try {
        await fetch(_CLOSE_URL(_activeId), {
            method:  'PATCH',
            headers: { 'X-CSRF-TOKEN': _CSRF, Accept: 'application/json' },
        });
        _activeStatus = 'resolved';
        document.getElementById('sc-resolved-banner').style.display = '';
        document.getElementById('sc-close-btn').style.display = 'none';
        const replyInput = document.getElementById('sc-reply');
        replyInput.disabled = true;
        replyInput.placeholder = 'Sesión cerrada — solo lectura';
        document.getElementById('sc-send-btn').disabled = true;
        await loadSessions();
    } catch {}
}

// ── Echo — escuchar mensajes entrantes ────────────────────────────────────
function subscribeEcho() {
    if (!window.Echo) return;
    try {
        const tid = {{ tenant_id() ?? 0 }};
        _echoSub = window.Echo
            .private(`private-tenant.${tid}.support`)
            .listen('.support.message', async (data) => {
                // Recargar lista (actualiza sin_leer)
                await loadSessions(true);
                // Si la sesión activa recibió el mensaje, agregarlo
                if (_activeId === data.session_id) {
                    appendMsg({
                        origen:    'visitor',
                        mensaje:   data.mensaje,
                        hora:      data.hora,
                        user_name: data.visitor_nombre,
                    });
                }
                // Notificación
                playBeep();
                if (_notifyEnabled && document.hidden) {
                    new Notification('Nuevo mensaje de soporte', {
                        body: `${data.visitor_nombre}: ${data.mensaje.substring(0, 80)}`,
                        icon: '/favicon.ico',
                    });
                }
            });
    } catch {}
}

function esc(str) {
    const d = document.createElement('div');
    d.appendChild(document.createTextNode(str ?? ''));
    return d.innerHTML;
}
</script>
@endpush
@endsection
