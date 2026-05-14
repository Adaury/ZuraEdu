@extends('layouts.admin')
@section('title', 'Chat de Soporte')

@section('content')
<div class="d-flex h-100" style="min-height:calc(100vh - 120px);gap:0;">

    {{-- ── Panel izquierdo: lista de sesiones ──────────────────────────── --}}
    <div id="sc-sessions-panel" style="width:300px;min-width:260px;border-right:1px solid #e2e8f0;display:flex;flex-direction:column;background:#fff;">
        <div style="padding:1rem;border-bottom:1px solid #e2e8f0;display:flex;align-items:center;justify-content:space-between;">
            <h6 class="mb-0 fw-bold" style="font-size:.9rem;"><i class="bi bi-headset me-2 text-primary"></i>Chats activos</h6>
            <span id="sc-total-badge" class="badge bg-primary rounded-pill" style="display:none;"></span>
        </div>
        <div style="padding:.5rem;border-bottom:1px solid #f1f5f9;">
            <input id="sc-search" type="text" placeholder="Buscar visitante..." class="form-control form-control-sm"
                   oninput="scFilterSessions(this.value)">
        </div>
        <div id="sc-sessions-list" style="flex:1;overflow-y:auto;padding:.4rem;">
            <div class="text-center text-muted small py-4" id="sc-sessions-empty">
                <i class="bi bi-chat-square-text" style="font-size:2rem;display:block;opacity:.3;margin-bottom:.5rem;"></i>
                Sin conversaciones activas
            </div>
        </div>
    </div>

    {{-- ── Panel derecho: conversación activa ──────────────────────────── --}}
    <div id="sc-chat-area" style="flex:1;display:flex;flex-direction:column;background:#f8fafc;">

        {{-- Estado vacío --}}
        <div id="sc-chat-empty" style="flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;color:#94a3b8;">
            <i class="bi bi-chat-dots" style="font-size:3rem;margin-bottom:.75rem;"></i>
            <p class="mb-0 fw-500" style="font-size:.9rem;">Selecciona una conversación</p>
        </div>

        {{-- Conversación activa (oculta al inicio) --}}
        <div id="sc-active-chat" style="display:none;flex:1;flex-direction:column;">
            <div id="sc-chat-header" style="padding:.85rem 1.2rem;border-bottom:1px solid #e2e8f0;background:#fff;display:flex;align-items:center;gap:.75rem;">
                <div style="width:38px;height:38px;background:#dbeafe;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i class="bi bi-person-fill" style="color:#3b82f6;font-size:1.1rem;"></i>
                </div>
                <div style="flex:1;">
                    <div id="sc-visitor-name" class="fw-bold" style="font-size:.88rem;"></div>
                    <div id="sc-visitor-info" style="font-size:.72rem;color:#64748b;"></div>
                </div>
                <button onclick="scCloseSession()" class="btn btn-sm btn-outline-success">
                    <i class="bi bi-check2-all me-1"></i>Resolver
                </button>
            </div>

            <div id="sc-messages-area" style="flex:1;overflow-y:auto;padding:1rem;display:flex;flex-direction:column;gap:.6rem;max-height:calc(100vh - 280px);">
            </div>

            <div style="padding:.7rem 1rem;border-top:1px solid #e2e8f0;background:#fff;display:flex;gap:.5rem;align-items:center;">
                <input id="sc-reply-input" type="text" placeholder="Escribe tu respuesta..." maxlength="2000" autocomplete="off"
                       class="form-control form-control-sm" style="border-radius:10px;"
                       onkeydown="if(event.key==='Enter') scReply()">
                <button onclick="scReply()" class="btn btn-primary btn-sm" style="border-radius:10px;width:38px;height:38px;padding:0;display:flex;align-items:center;justify-content:center;">
                    <i class="bi bi-send-fill" style="font-size:.8rem;"></i>
                </button>
            </div>
        </div>

    </div>
</div>

@push('scripts')
<script>
const _SC_SESSIONS_URL = '{{ route('admin.soporte.chat.sessions') }}';
const _SC_MSGS_URL     = (id) => `/admin/soporte/chat/${id}/mensajes`;
const _SC_REPLY_URL    = (id) => `/admin/soporte/chat/${id}/reply`;
const _SC_CLOSE_URL    = (id) => `/admin/soporte/chat/${id}/close`;
const _SC_CSRF         = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

let _sessions        = [];
let _activeSessionId = null;
let _activeToken     = null;
let _echoSub         = null;
let _pollTimer       = null;

// ── Inicializar ───────────────────────────────────────────────────────────
(async function init() {
    await loadSessions();
    subscribeSupport();
    _pollTimer = setInterval(loadSessions, 15000);
})();

// ── Cargar sesiones ───────────────────────────────────────────────────────
async function loadSessions() {
    try {
        const res  = await fetch(_SC_SESSIONS_URL, { headers: { Accept: 'application/json' } });
        _sessions  = await res.json();
        renderSessions(_sessions);
    } catch {}
}

function renderSessions(list) {
    const container = document.getElementById('sc-sessions-list');
    const empty     = document.getElementById('sc-sessions-empty');
    const badge     = document.getElementById('sc-total-badge');
    const sinLeer   = list.reduce((a, s) => a + (s.sin_leer ?? 0), 0);

    if (!list.length) {
        empty.style.display = 'block';
        badge.style.display = 'none';
        container.querySelectorAll('.sc-session-card').forEach(el => el.remove());
        return;
    }
    empty.style.display = 'none';
    badge.textContent   = sinLeer > 0 ? String(sinLeer) : '';
    badge.style.display = sinLeer > 0 ? '' : 'none';

    // Mantener cards existentes, agregar/actualizar
    const existingIds = new Set([...container.querySelectorAll('.sc-session-card')].map(el => el.dataset.id));
    list.forEach(s => {
        let card = container.querySelector(`.sc-session-card[data-id="${s.id}"]`);
        if (!card) {
            card = document.createElement('div');
            card.className = 'sc-session-card';
            card.dataset.id = s.id;
            container.appendChild(card);
        }
        card.onclick = () => openSession(s);
        card.style.cssText = `cursor:pointer;padding:.65rem .75rem;border-radius:10px;margin-bottom:.3rem;transition:background .15s;background:${_activeSessionId === s.id ? '#eff6ff' : '#f8fafc'};border:1px solid ${_activeSessionId === s.id ? '#bfdbfe' : 'transparent'};`;
        card.innerHTML = `
            <div style="display:flex;align-items:center;gap:.5rem;">
                <div style="width:32px;height:32px;background:#dbeafe;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i class="bi bi-person-fill" style="color:#3b82f6;font-size:.85rem;"></i>
                </div>
                <div style="flex:1;min-width:0;">
                    <div style="font-weight:600;font-size:.82rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${escHtml(s.visitor_nombre)}</div>
                    <div style="font-size:.68rem;color:#64748b;">${s.ultimo_mensaje}</div>
                </div>
                ${s.sin_leer > 0 ? `<span style="background:#ef4444;color:#fff;border-radius:99px;font-size:.6rem;font-weight:700;min-width:17px;height:17px;display:flex;align-items:center;justify-content:center;padding:0 4px;">${s.sin_leer}</span>` : ''}
            </div>`;
    });
    // Remover cards que ya no existen
    container.querySelectorAll('.sc-session-card').forEach(el => {
        if (!list.find(s => String(s.id) === el.dataset.id)) el.remove();
    });
}

// ── Abrir sesión ──────────────────────────────────────────────────────────
async function openSession(s) {
    _activeSessionId = s.id;
    _activeToken     = s.token;

    document.getElementById('sc-chat-empty').style.display  = 'none';
    document.getElementById('sc-active-chat').style.display = 'flex';
    document.getElementById('sc-visitor-name').textContent  = s.visitor_nombre;
    document.getElementById('sc-visitor-info').textContent  =
        [s.visitor_email, s.visitor_telefono].filter(Boolean).join(' · ') || 'Sin contacto adicional';

    // Resaltar card activa
    document.querySelectorAll('.sc-session-card').forEach(c => {
        const active = c.dataset.id === String(s.id);
        c.style.background = active ? '#eff6ff' : '#f8fafc';
        c.style.border     = `1px solid ${active ? '#bfdbfe' : 'transparent'}`;
    });

    await loadMessages(s.id);
    subscribeVisitorEcho(s.token);
    document.getElementById('sc-reply-input')?.focus();
}

// ── Cargar mensajes de sesión ─────────────────────────────────────────────
async function loadMessages(sessionId) {
    try {
        const res  = await fetch(_SC_MSGS_URL(sessionId), { headers: { Accept: 'application/json' } });
        const msgs = await res.json();
        const area = document.getElementById('sc-messages-area');
        area.innerHTML = '';
        msgs.forEach(m => appendMsg(m, false));
        area.scrollTop = area.scrollHeight;
    } catch {}
}

function appendMsg(m, scroll = true) {
    const area    = document.getElementById('sc-messages-area');
    const isAdmin = m.origen === 'admin';
    const wrap    = document.createElement('div');
    wrap.style.cssText = `display:flex;flex-direction:column;align-items:${isAdmin ? 'flex-end' : 'flex-start'};gap:2px;`;
    wrap.innerHTML = `
        ${!isAdmin ? `<span style="font-size:.65rem;color:#64748b;font-weight:600;">${escHtml(m.user_name ?? '')}</span>` : ''}
        <div style="max-width:75%;background:${isAdmin ? '#3b82f6' : '#fff'};color:${isAdmin ? '#fff' : '#1e293b'};
                    border-radius:${isAdmin ? '14px 14px 4px 14px' : '14px 14px 14px 4px'};
                    padding:.45rem .8rem;font-size:.83rem;line-height:1.45;
                    box-shadow:0 1px 4px rgba(0,0,0,.08);">${escHtml(m.mensaje)}</div>
        <span style="font-size:.62rem;color:#94a3b8;">${m.hora ?? ''}</span>`;
    area.appendChild(wrap);
    if (scroll) area.scrollTop = area.scrollHeight;
}

// ── Responder ─────────────────────────────────────────────────────────────
async function scReply() {
    if (!_activeSessionId) return;
    const input = document.getElementById('sc-reply-input');
    const msg   = input.value.trim();
    if (!msg) return;
    input.value = '';

    try {
        const res  = await fetch(_SC_REPLY_URL(_activeSessionId), {
            method:  'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': _SC_CSRF, Accept: 'application/json' },
            body:    JSON.stringify({ mensaje: msg }),
        });
        const data = await res.json();
        appendMsg(data, true);
    } catch {}
}

// ── Cerrar/resolver sesión ────────────────────────────────────────────────
async function scCloseSession() {
    if (!_activeSessionId) return;
    try {
        await fetch(_SC_CLOSE_URL(_activeSessionId), {
            method:  'PATCH',
            headers: { 'X-CSRF-TOKEN': _SC_CSRF, Accept: 'application/json' },
        });
        _activeSessionId = null;
        _activeToken     = null;
        document.getElementById('sc-chat-empty').style.display  = 'flex';
        document.getElementById('sc-active-chat').style.display = 'none';
        await loadSessions();
    } catch {}
}

// ── Echo: admin escucha mensajes de visitantes ────────────────────────────
function subscribeSupport() {
    if (!window.Echo) return;
    try {
        const tenantId = {{ tenant_id() ?? 0 }};
        window.Echo
            .private(`private-tenant.${tenantId}.support`)
            .listen('.support.message', (data) => {
                loadSessions();
                if (_activeSessionId === data.session_id) {
                    appendMsg({ origen: 'visitor', mensaje: data.mensaje, hora: data.hora, user_name: data.visitor_nombre });
                }
            });
    } catch {}
}

// ── Echo: admin escucha respuestas al canal del visitor ───────────────────
function subscribeVisitorEcho(token) {
    // No necesitamos suscribir nada aquí desde el admin; ya escuchamos en support channel
}

// ── Filtrar sesiones ──────────────────────────────────────────────────────
function scFilterSessions(q) {
    const filtered = q
        ? _sessions.filter(s => s.visitor_nombre.toLowerCase().includes(q.toLowerCase())
            || (s.visitor_email ?? '').toLowerCase().includes(q.toLowerCase()))
        : _sessions;
    renderSessions(filtered);
}

function escHtml(str) {
    const d = document.createElement('div');
    d.appendChild(document.createTextNode(str ?? ''));
    return d.innerHTML;
}
</script>
@endpush
@endsection
