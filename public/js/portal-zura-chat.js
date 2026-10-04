(function () {
    const btn       = document.getElementById('zura-ai-btn');
    const panel     = document.getElementById('zura-ai-panel');
    const closeBtn  = document.getElementById('zura-close');
    const clearBtn  = document.getElementById('zura-clear');
    const messagesEl= document.getElementById('zura-messages');
    const inputEl   = document.getElementById('zura-input');
    const sendBtn   = document.getElementById('zura-send');
    const suggsEl   = document.getElementById('zura-suggestions');
    const CHAT_URL  = ZURA_PORTAL_CFG.chatUrl;
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
    const ZURA_ROLE = ZURA_PORTAL_CFG.role;

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
        const ZURA_ROLE_LOCAL = ZURA_PORTAL_CFG.role;
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
