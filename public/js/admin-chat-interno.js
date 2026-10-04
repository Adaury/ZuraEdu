    const _CHAT_ME_ID   = ZURA_CFG.userId;
    const _CHAT_ME_NAME = ZURA_CFG.userName20;
    const _CHAT_URL     = ZURA_CFG.tenantChatUrl;
    const _CHAT_CSRF    = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
    let   _chatLoaded   = false;

    function toggleTenantChat() {
        const panel = document.getElementById('tenant-chat-panel');
        const isOpen = panel.style.display !== 'none';

        if (!isOpen) {
            // Abrir siempre limpio
            const box = document.getElementById('tenant-chat-messages');
            if (box) box.innerHTML = '';
            _chatLoaded = true;
            panel.style.display = 'flex';
            panel.style.flexDirection = 'column';
            const badge = document.getElementById('tenant-chat-badge');
            if (badge) { badge.style.display = 'none'; badge.dataset.count = '0'; }
            setTimeout(() => document.getElementById('tenant-chat-input')?.focus(), 100);
        } else {
            panel.style.display = 'none';
        }
    }

    function clearTenantChat() {
        if (!confirm('¿Eliminar todos los mensajes del chat?')) return;
        fetch(ZURA_CFG.tenantChatClearUrl, {
            method:  'DELETE',
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': _CHAT_CSRF },
        })
        .then(r => r.json())
        .then(() => {
            const box = document.getElementById('tenant-chat-messages');
            if (box) box.innerHTML = '<div class="text-center text-muted small py-3">Chat limpiado.</div>';
            _chatLoaded = true;
        })
        .catch(() => {});
    }

    function loadChatHistory() {
        fetch(_CHAT_URL, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
            .then(r => r.json())
            .then(msgs => {
                _chatLoaded = true;
                const box = document.getElementById('tenant-chat-messages');
                const loader = document.getElementById('chat-loading-msg');
                if (loader) loader.remove();
                msgs.forEach(m => appendChatBubble(m, false));
                scrollChatBottom();
            })
            .catch(() => { _chatLoaded = true; });
    }

    function appendChatBubble(data, scroll = true) {
        const box   = document.getElementById('tenant-chat-messages');
        if (!box) return;
        const isMio = data.user_id === _CHAT_ME_ID;
        const div   = document.createElement('div');
        div.style.cssText = `display:flex;flex-direction:column;align-items:${isMio ? 'flex-end' : 'flex-start'};gap:2px;`;
        div.innerHTML = `
            ${!isMio ? `<span style="font-size:.68rem;color:#64748b;font-weight:600;">${escHtml(data.user_name)}</span>` : ''}
            <div style="max-width:78%;background:${isMio ? '#3B82F6' : '#fff'};color:${isMio ? '#fff' : '#1e293b'};
                        border-radius:${isMio ? '14px 14px 4px 14px' : '14px 14px 14px 4px'};
                        padding:.45rem .75rem;font-size:.83rem;line-height:1.4;
                        box-shadow:0 1px 3px rgba(0,0,0,.08);">
                ${escHtml(data.mensaje)}
            </div>
            <span style="font-size:.63rem;color:#94a3b8;">${data.hora || data.tiempo}</span>`;
        box.appendChild(div);
        if (scroll) scrollChatBottom();
    }

    function scrollChatBottom() {
        const box = document.getElementById('tenant-chat-messages');
        if (box) box.scrollTop = box.scrollHeight;
    }

    function escHtml(str) {
        const d = document.createElement('div');
        d.appendChild(document.createTextNode(str ?? ''));
        return d.innerHTML;
    }

    // Enviar mensaje
    document.getElementById('tenant-chat-form')?.addEventListener('submit', function(e) {
        e.preventDefault();
        const input = document.getElementById('tenant-chat-input');
        const msg   = input.value.trim();
        if (!msg) return;
        input.value = '';

        fetch(_CHAT_URL, {
            method:  'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': _CHAT_CSRF },
            body:    JSON.stringify({ mensaje: msg }),
        })
        .then(r => r.json())
        .then(data => appendChatBubble(data, true))
        .catch(() => {});
    });

    // Escuchar mensajes entrantes vía Echo
    window.addEventListener('tenant:chat-message', function(e) {
        const panel = document.getElementById('tenant-chat-panel');
        if (panel && panel.style.display !== 'none' && _chatLoaded) {
            if (e.detail.user_id !== _CHAT_ME_ID) {
                appendChatBubble(e.detail, true);
            }
        }
    });
    