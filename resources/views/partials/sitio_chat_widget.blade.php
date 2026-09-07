{{-- Chat con IA del sitio público — consultas sobre la institución, sin
     login, alcance limitado a datos públicos del Homepage (ver
     PublicSiteChatController). Se incluye en las 4 páginas públicas del
     tenant: /sitio, /sitio/noticias(/{id}) y /galeria. --}}
<style>
    #zc-btn { position: fixed; bottom: 1.5rem; right: 1.5rem; z-index: 200; width: 56px; height: 56px; border-radius: 50%; background: var(--primary, #1d4ed8); color: #fff; border: none; box-shadow: 0 8px 24px rgba(0,0,0,.25); font-size: 1.4rem; cursor: pointer; display: flex; align-items: center; justify-content: center; }
    #zc-window { position: fixed; bottom: 5.5rem; right: 1.5rem; z-index: 200; width: min(340px, calc(100vw - 2rem)); height: min(460px, calc(100vh - 8rem)); background: #fff; border-radius: 16px; box-shadow: 0 16px 48px rgba(0,0,0,.2); display: none; flex-direction: column; overflow: hidden; font-family: 'Inter', -apple-system, 'Segoe UI', sans-serif; }
    #zc-window.open { display: flex; }
    #zc-header { background: var(--primary, #1d4ed8); color: #fff; padding: .9rem 1rem; display: flex; align-items: center; gap: .6rem; }
    #zc-header .zc-title { font-weight: 700; font-size: .92rem; }
    #zc-header .zc-status { font-size: .72rem; opacity: .85; }
    #zc-close { margin-left: auto; background: rgba(255,255,255,.15); border: none; color: #fff; border-radius: 6px; width: 26px; height: 26px; cursor: pointer; }
    #zc-messages { flex: 1; overflow-y: auto; padding: .9rem; display: flex; flex-direction: column; gap: .6rem; background: #f8fafc; }
    .zc-msg { font-size: .85rem; line-height: 1.5; padding: .55rem .8rem; border-radius: 12px; max-width: 85%; }
    .zc-msg.bot { background: #fff; border: 1px solid #e2e8f0; align-self: flex-start; }
    .zc-msg.user { background: var(--primary, #1d4ed8); color: #fff; align-self: flex-end; }
    .zc-msg.typing { display: flex; gap: .25rem; align-self: flex-start; padding: .7rem .9rem; }
    .zc-msg.typing span { width: 6px; height: 6px; border-radius: 50%; background: #94a3b8; animation: zc-bounce 1.2s infinite; }
    .zc-msg.typing span:nth-child(2) { animation-delay: .15s; }
    .zc-msg.typing span:nth-child(3) { animation-delay: .3s; }
    @keyframes zc-bounce { 0%, 60%, 100% { transform: translateY(0); } 30% { transform: translateY(-4px); } }
    #zc-input-area { display: flex; gap: .5rem; padding: .7rem; border-top: 1px solid #e2e8f0; }
    #zc-input { flex: 1; border: 1px solid #e2e8f0; border-radius: 10px; padding: .5rem .7rem; font-size: .85rem; resize: none; font-family: inherit; }
    #zc-send { background: var(--primary, #1d4ed8); color: #fff; border: none; border-radius: 10px; width: 38px; flex-shrink: 0; cursor: pointer; }
</style>

<button id="zc-btn" title="Preguntar sobre {{ $nombre }}"><i class="bi bi-chat-dots-fill"></i></button>

<div id="zc-window">
    <div id="zc-header">
        <div>
            <div class="zc-title">Pregúntanos</div>
            <div class="zc-status">{{ $nombre }}</div>
        </div>
        <button id="zc-close"><i class="bi bi-x-lg"></i></button>
    </div>
    <div id="zc-messages"></div>
    <div id="zc-input-area">
        <textarea id="zc-input" rows="1" placeholder="Escribe tu pregunta…"></textarea>
        <button id="zc-send"><i class="bi bi-send-fill"></i></button>
    </div>
</div>

<script>
(function () {
    var ROUTE = "{{ route('sitio.chat') }}";
    var CSRF  = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    var WELCOME = '¡Hola! Puedo responder preguntas sobre {{ $nombre }}. ¿En qué te ayudo?';

    var btn      = document.getElementById('zc-btn');
    var win      = document.getElementById('zc-window');
    var closeBtn = document.getElementById('zc-close');
    var messages = document.getElementById('zc-messages');
    var input    = document.getElementById('zc-input');
    var sendBtn  = document.getElementById('zc-send');

    var open = false, sending = false, history = [];

    function escapeHtml(text) {
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function append(text, role) {
        var div = document.createElement('div');
        div.className = 'zc-msg ' + role;
        div.innerHTML = escapeHtml(text).replace(/\n/g, '<br>');
        messages.appendChild(div);
        messages.scrollTop = messages.scrollHeight;
    }

    function showTyping() {
        var div = document.createElement('div');
        div.className = 'zc-msg typing';
        div.id = 'zc-typing';
        div.innerHTML = '<span></span><span></span><span></span>';
        messages.appendChild(div);
        messages.scrollTop = messages.scrollHeight;
    }

    function hideTyping() {
        var t = document.getElementById('zc-typing');
        if (t) t.remove();
    }

    function toggle() {
        open = !open;
        win.classList.toggle('open', open);
        if (open && messages.children.length === 0) {
            append(WELCOME, 'bot');
        }
        if (open) setTimeout(function () { input.focus(); }, 200);
    }

    function send() {
        var text = input.value.trim();
        if (!text || sending) return;
        sending = true;
        append(text, 'user');
        history.push({ role: 'user', text: text });
        input.value = '';
        showTyping();

        fetch(ROUTE, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': CSRF,
                'Accept': 'application/json',
            },
            body: JSON.stringify({ message: text, history: history.slice(-6) }),
        })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                hideTyping();
                var reply = data.reply || 'No pude responder en este momento.';
                append(reply, 'bot');
                history.push({ role: 'model', text: reply });
            })
            .catch(function () {
                hideTyping();
                append('No se pudo conectar. Intenta de nuevo.', 'bot');
            })
            .finally(function () { sending = false; });
    }

    btn.addEventListener('click', toggle);
    closeBtn.addEventListener('click', toggle);
    sendBtn.addEventListener('click', send);
    input.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            send();
        }
    });
})();
</script>
