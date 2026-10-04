(function() {
    const ROUTE_CHAT = ZURA_CFG.chatSendUrl;
    const CSRF       = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    let chatOpen    = false;
    let isTyping    = false;
    let chatHistory = [];

    const WELCOME_MSG = '¡Hola! Soy <strong>Zura</strong>, el asistente de <strong>' + ZURA_CFG.systemNameHtml + '</strong>. Puedo ayudarte con el sistema: asistencia, calificaciones, matrículas, boletines y más. ¿En qué te ayudo?';

    function resetChat() {
        chatHistory = [];
        isTyping    = false;
        document.getElementById('chat-messages').innerHTML = '<div class="chat-msg bot">' + WELCOME_MSG + '</div>';
        document.getElementById('chat-suggestions').style.display = 'flex';
        document.getElementById('chat-send').disabled = false;
        const inp = document.getElementById('chat-input');
        if (inp) { inp.value = ''; inp.style.height = 'auto'; }
    }

    window.toggleChat = function() {
        chatOpen = !chatOpen;
        document.getElementById('chat-window').classList.toggle('open', chatOpen);
        if (chatOpen) {
            resetChat();
            setTimeout(() => document.getElementById('chat-input').focus(), 250);
        }
    };

    window.autoResize = function(el) {
        el.style.height = 'auto';
        el.style.height = Math.min(el.scrollHeight, 100) + 'px';
    };

    window.handleChatKey = function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendChat();
        }
    };

    function renderMarkdown(text) {
        // Escapar HTML para evitar XSS, luego renderizar markdown básico
        const safe = text
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
        return safe
            .replace(/^#{1,3}\s+(.+)$/gm, '<strong>$1</strong>')
            .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
            .replace(/\*(.+?)\*/g, '<em>$1</em>')
            .replace(/`([^`\n]+)`/g, '<code style="background:#f1f5f9;padding:.1em .35em;border-radius:4px;font-size:.82em;font-family:monospace;">$1</code>')
            .replace(/^[-•*]\s+(.+)$/gm, '• $1')
            .replace(/\n/g, '<br>');
    }

    function appendMsg(text, role) {
        const msgs = document.getElementById('chat-messages');
        const div  = document.createElement('div');
        div.className = 'chat-msg ' + role;
        if (role === 'bot') {
            div.innerHTML = renderMarkdown(text);
        } else {
            div.textContent = text;
        }
        msgs.appendChild(div);
        msgs.scrollTop = msgs.scrollHeight;
        return div;
    }

    function showTyping() {
        const msgs = document.getElementById('chat-messages');
        const div  = document.createElement('div');
        div.className = 'chat-msg typing';
        div.id = 'chat-typing';
        div.innerHTML = '<div class="typing-dots"><span></span><span></span><span></span></div>';
        msgs.appendChild(div);
        msgs.scrollTop = msgs.scrollHeight;
    }

    function removeTyping() {
        const t = document.getElementById('chat-typing');
        if (t) t.remove();
    }

    window.sendChat = async function() {
        if (isTyping) return;

        const input = document.getElementById('chat-input');
        const text  = input.value.trim();
        if (!text) return;

        appendMsg(text, 'user');
        // Snapshot del historial ANTES de agregar el mensaje actual
        // para no enviarlo duplicado al backend (history + message)
        const historySend = chatHistory.slice(-10);

        input.value = '';
        input.style.height = 'auto';
        document.getElementById('chat-send').disabled = true;
        isTyping = true;

        showTyping();

        try {
            const res = await fetch(ROUTE_CHAT, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ message: text, history: historySend }),
            });

            if (!res.ok) {
                throw new Error('HTTP ' + res.status);
            }

            const data = await res.json();
            removeTyping();

            const reply = data.reply ?? 'Sin respuesta.';
            appendMsg(reply, 'bot');

            // Agregar al historial DESPUÉS de recibir respuesta
            chatHistory.push({ role: 'user', text });
            chatHistory.push({ role: 'model', text: reply });

            if (chatHistory.length > 20) chatHistory = chatHistory.slice(-20);

        } catch (err) {
            removeTyping();
            appendMsg('No se pudo obtener respuesta. Por favor intenta de nuevo.', 'bot');
        } finally {
            document.getElementById('chat-send').disabled = false;
            isTyping = false;
        }
    };

    window.clearChat = function() {
        resetChat();
    };

    window.useSuggestion = function(btn) {
        const text = btn.textContent.trim();
        document.getElementById('chat-suggestions').style.display = 'none';
        document.getElementById('chat-input').value = text;
        sendChat();
    };

    // Ocultar sugerencias cuando el usuario empieza a escribir
    document.getElementById('chat-input').addEventListener('input', function() {
        if (this.value.trim().length > 0) {
            document.getElementById('chat-suggestions').style.display = 'none';
        }
    });
})();
