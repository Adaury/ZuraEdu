/**
 * ZuraEdu Realtime — Laravel Echo + Reverb
 *
 * Maneja:
 *  - Notificaciones push (private-user.{id}) → toast + badge sin polling
 *  - Calificaciones publicadas (private-grupo.{id}) → toast al estudiante/docente
 *  - Nuevo material en classroom (private-classroom.{id}) → toast al estudiante
 *  - Asistencia registrada (private-docente.{id}) → confirmación al docente
 *  - Mensajes de classroom (private-classroom.{id}) → actualización del chat
 */

import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

// ── Configurar Echo ───────────────────────────────────────────────────────────
window.Echo = new Echo({
    broadcaster:         'reverb',
    key:                 window._REVERB_KEY  ?? '',
    wsHost:              window._REVERB_HOST ?? 'localhost',
    wsPort:              window._REVERB_PORT ?? 8080,
    wssPort:             window._REVERB_PORT ?? 8080,
    forceTLS:            (window._REVERB_SCHEME ?? 'http') === 'https',
    enabledTransports:   ['ws', 'wss'],
    authEndpoint:        '/broadcasting/auth',
    auth: {
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
        },
    },
});

// ── Helpers internos ──────────────────────────────────────────────────────────

function toast(message, type = 'info', duration = 5000) {
    if (window.SGEToast) {
        window.SGEToast.show(message, type, duration);
        return;
    }
    // Fallback minimalista si SGEToast no está disponible (portal)
    const c = document.getElementById('sge-toast-container');
    if (!c) return;
    const d = document.createElement('div');
    d.style.cssText = 'background:#dbeafe;color:#1d4ed8;border-radius:10px;padding:.7rem 1rem;' +
        'display:flex;align-items:center;gap:.6rem;box-shadow:0 4px 20px rgba(0,0,0,.13);' +
        'font-size:.84rem;font-weight:600;max-width:340px;margin-bottom:.4rem;';
    d.innerHTML = `<i class="bi bi-bell-fill"></i><span style="flex:1">${message}</span>`;
    c.appendChild(d);
    if (duration > 0) setTimeout(() => d.remove(), duration);
}

function actualizarBadgeNotif(delta = 1) {
    // Intenta todos los selectores conocidos de badge en admin y portal
    const badge = document.querySelector('[data-notif-badge]')
        ?? document.querySelector('.prt-bell .prt-badge')
        ?? document.querySelector('.notif-badge')
        ?? document.querySelector('#notif-count');

    if (badge) {
        const current = parseInt(badge.textContent, 10) || 0;
        const next    = current + delta;
        badge.textContent   = next > 99 ? '99+' : String(next);
        badge.style.display = next > 0 ? '' : 'none';
    }

    // Pulsar ícono de campanita
    const bell = document.querySelector('#btnBell, [data-notif-bell], .notif-bell, #notif-bell');
    if (bell) {
        bell.style.animation = 'none';
        void bell.offsetHeight;
        bell.style.animation = 'bellPulse .5s ease 3';
    }

    // Evento DOM para que el polling JS actualice su propio contador interno
    window.dispatchEvent(new CustomEvent('sge:notification-new', { detail: { delta } }));
}

// ── Subscripciones ────────────────────────────────────────────────────────────

const userId    = window._SGE_USER_ID ?? null;
const grupoIds  = window._SGE_GRUPO_IDS ?? [];
const claseIds  = window._SGE_CLASE_IDS ?? [];
const tenantId  = window._SGE_TENANT_ID ?? null;
const rolLower  = (window._SGE_ROL ?? '').toLowerCase();

// 1. Notificaciones personales
if (userId) {
    window.Echo
        .private(`private-user.${userId}`)
        .listen('.notification.created', (data) => {
            toast(`<i class="bi ${data.icono ?? 'bi-bell'} me-1"></i>${data.titulo}`, 'info', 6000);
            actualizarBadgeNotif(1);
            window.dispatchEvent(new CustomEvent('sge:notification-created', { detail: data }));
        });
}

// 2. Calificaciones publicadas en grupos del usuario
grupoIds.forEach(grupoId => {
    window.Echo
        .private(`private-grupo.${grupoId}`)
        .listen('.grade.published', (data) => {
            toast(
                `<i class="bi bi-journal-check me-1"></i>${data.mensaje}`,
                'success', 7000
            );
            window.dispatchEvent(new CustomEvent('sge:grade-published', { detail: data }));
        });
});

// 3. Material nuevo, mensajes y tareas en classrooms del usuario
claseIds.forEach(claseId => {
    window.Echo
        .private(`private-classroom.${claseId}`)
        .listen('.material.nuevo', (data) => {
            toast(
                `<i class="bi bi-folder-fill me-1"></i>Nuevo material: <strong>${data.titulo}</strong>`,
                'info', 6000
            );
        })
        .listen('.message.sent', (data) => {
            const chatBox    = document.getElementById('chat-box');
            const chatHidden = !chatBox || !!chatBox.closest('.d-none');
            if (chatHidden && data.user_id !== (window._SGE_USER_ID ?? null)) {
                toast(
                    `<i class="bi bi-chat-fill me-1"></i><strong>${data.user_name}</strong>: ${data.mensaje}`,
                    'info', 5000
                );
            }
            window.dispatchEvent(new CustomEvent('classroom:new-message', { detail: data }));
        })
        .listen('.task.created', (data) => {
            toast(
                `<i class="bi bi-clipboard2-check-fill me-1"></i>Nueva tarea: <strong>${data.titulo}</strong>` +
                (data.fecha_entrega ? ` — entrega ${data.fecha_entrega}` : ''),
                'info', 7000
            );
            window.dispatchEvent(new CustomEvent('classroom:task-created', { detail: data }));
        })
        .listen('.meeting-updated', (data) => {
            if (data.status === 'active') {
                toast(
                    `<i class="bi bi-camera-video-fill me-1"></i>La videollamada ha comenzado`,
                    'success', 8000
                );
            }
            window.dispatchEvent(new CustomEvent('classroom:meeting-updated', { detail: data }));
        });
});

// 4. Canal personal del docente — asistencia y QR realtime
if (userId && rolLower === 'docente') {
    window.Echo
        .private(`private-docente.${userId}`)
        .listen('.asistencia.registrada', (data) => {
            toast(
                `<i class="bi bi-check-circle-fill me-1"></i>Asistencia guardada: ` +
                `${data.presentes}/${data.total} presentes (${data.porcentaje}%) — ${data.asignatura}`,
                'success', 5000
            );
        })
        .listen('.qr.escaneado', (data) => {
            window.dispatchEvent(new CustomEvent('qr:escaneado', { detail: data }));
        })
        .listen('.task.delivered', (data) => {
            toast(
                `<i class="bi bi-clipboard2-check me-1"></i><strong>${data.estudiante_nombre}</strong> entregó: ${data.tarea_titulo}`,
                'info', 6000
            );
            window.dispatchEvent(new CustomEvent('docente:task-delivered', { detail: data }));
        })
        .listen('.student.connected', (data) => {
            toast(
                `<i class="bi bi-person-check-fill me-1"></i><strong>${data.estudiante_nombre}</strong> se conectó al aula`,
                'info', 4000
            );
            window.dispatchEvent(new CustomEvent('docente:student-connected', { detail: data }));
        });
}

// 5. Canal del tenant para admins (eventos de gestión global)
if (tenantId && (rolLower === 'admin' || rolLower === 'superadmin' || rolLower === 'coordinator')) {
    window.Echo
        .private(`private-tenant.${tenantId}`)
        .listen('.dashboard.updated', (data) => {
            window.dispatchEvent(new CustomEvent('sge:dashboard-updated', { detail: data }));
        });
}

// 6. Chat interno del tenant (todos los usuarios autenticados)
if (tenantId) {
    window.Echo
        .private(`private-tenant.${tenantId}.chat`)
        .listen('.chat.message', (data) => {
            // El widget de chat escucha este evento DOM para renderizar la burbuja
            window.dispatchEvent(new CustomEvent('tenant:chat-message', { detail: data }));

            // Toast si el widget está cerrado y el mensaje no es del usuario actual
            const chatOpen   = document.getElementById('tenant-chat-panel')?.classList.contains('open');
            const esMio      = data.user_id === (window._SGE_USER_ID ?? null);
            if (!chatOpen && !esMio) {
                toast(
                    `<i class="bi bi-chat-dots-fill me-1"></i><strong>${data.user_name}</strong>: ${data.mensaje}`,
                    'info', 5000
                );
                const badge = document.getElementById('tenant-chat-badge');
                if (badge) {
                    const n = (parseInt(badge.dataset.count ?? '0', 10) || 0) + 1;
                    badge.dataset.count = n;
                    badge.textContent   = n > 9 ? '9+' : String(n);
                    badge.style.display = '';
                }
            }
        });
}

// 7. Notificaciones masivas del tenant (anuncios admin → todos los usuarios)
if (tenantId) {
    window.Echo
        .private(`private-tenant.${tenantId}.notifications`)
        .listen('.anuncio', (data) => {
            const tipos = { info: 'info', warning: 'warning', danger: 'danger', success: 'success' };
            const tipo  = tipos[data.tipo] ?? 'info';
            toast(
                `<i class="bi bi-megaphone-fill me-1"></i><strong>${data.titulo}</strong>: ${data.mensaje}`,
                tipo, 10000
            );
            window.dispatchEvent(new CustomEvent('tenant:anuncio', { detail: data }));
        });
}

// ── Estado de conexión (debug en desarrollo) ──────────────────────────────────
if (window._SGE_DEBUG) {
    window.Echo.connector.pusher.connection.bind('connected',    () => console.log('[Echo] conectado a Reverb'));
    window.Echo.connector.pusher.connection.bind('disconnected', () => console.warn('[Echo] desconectado de Reverb'));
    window.Echo.connector.pusher.connection.bind('error',       (e) => console.error('[Echo] error:', e));
}
