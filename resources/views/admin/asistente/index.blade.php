@extends('layouts.admin')
@section('title', 'ZuraAI — Asistente Institucional')
@section('content')
<style>
/* ── Contenedor principal ───────────────────────────────────────────── */
.zp { display:flex; flex-direction:column; height:calc(100vh - 68px); max-width:860px; margin:0 auto; padding:0 20px; }

/* ── Pantalla de bienvenida ─────────────────────────────────────────── */
.zp-welcome { flex:1; display:flex; flex-direction:column; align-items:center; justify-content:center; gap:28px; padding:40px 0 20px; }

@@keyframes zpFloat { 0%,100%{transform:translateY(0)} 50%{transform:translateY(-14px)} }
@@keyframes zpGlow  { 0%,100%{box-shadow:0 0 18px rgba(99,102,241,.35)} 50%{box-shadow:0 0 38px rgba(99,102,241,.75)} }
@@keyframes zpFadeUp{ from{opacity:0;transform:translateY(20px)} to{opacity:1;transform:translateY(0)} }

.zp-icon {
    width:82px; height:82px;
    background:linear-gradient(135deg,#4f46e5,#7c3aed);
    border-radius:50%;
    display:flex; align-items:center; justify-content:center;
    font-size:38px; color:#fff;
    animation: zpFloat 4s ease-in-out infinite, zpGlow 4s ease-in-out infinite;
}
.zp-title { text-align:center; animation:zpFadeUp .6s ease both .1s; }
.zp-title h2 { font-size:1.75rem; font-weight:700; color:#111827; margin-bottom:6px; }
.zp-title p  { color:#6b7280; font-size:.95rem; }

/* ── Tarjetas antigravity ───────────────────────────────────────────── */
.zp-cards { display:grid; grid-template-columns:repeat(2,1fr); gap:14px; width:100%; max-width:620px; }
.zp-card {
    background:#fff; border:1.5px solid #e5e7eb; border-radius:14px;
    padding:18px 16px; cursor:pointer;
    box-shadow:0 2px 12px rgba(0,0,0,.05);
    transition:all .2s; text-align:left;
}
.zp-card:hover { border-color:#7c3aed; box-shadow:0 6px 24px rgba(79,70,229,.18); transform:translateY(-3px) scale(1.01); }
.zp-card:nth-child(1){ animation:zpFloat 4.5s ease-in-out infinite 0s; }
.zp-card:nth-child(2){ animation:zpFloat 4.5s ease-in-out infinite .9s; }
.zp-card:nth-child(3){ animation:zpFloat 4.5s ease-in-out infinite 1.8s; }
.zp-card:nth-child(4){ animation:zpFloat 4.5s ease-in-out infinite 2.7s; }
.zp-card:hover { animation:none; } /* detener al hacer hover */
.zp-card-icon { font-size:22px; margin-bottom:8px; }
.zp-card-text { font-size:.845rem; color:#374151; line-height:1.45; font-weight:500; }
.zp-card-hint { font-size:.76rem; color:#9ca3af; margin-top:4px; }

/* ── Área de mensajes ───────────────────────────────────────────────── */
.zp-msgs {
    flex:1; overflow-y:auto; padding:20px 0 12px;
    display:flex; flex-direction:column; gap:20px;
}
.zp-msgs::-webkit-scrollbar { width:4px; }
.zp-msgs::-webkit-scrollbar-thumb { background:#d1d5db; border-radius:2px; }

/* ── Mensajes ───────────────────────────────────────────────────────── */
.zm { display:flex; gap:12px; animation:zpFadeUp .3s ease both; }
.zm.usr { flex-direction:row-reverse; }
.zm-av {
    width:34px; height:34px; border-radius:50%; flex-shrink:0;
    display:flex; align-items:center; justify-content:center; font-size:15px;
}
.zm.bot .zm-av { background:linear-gradient(135deg,#4f46e5,#7c3aed); color:#fff; }
.zm.usr .zm-av { background:#f3f4f6; color:#6b7280; }
.zm-body { flex:1; font-size:.9rem; line-height:1.7; color:#1f2937; }
.zm.usr .zm-body {
    background:#f3f4f6; padding:10px 16px;
    border-radius:12px 12px 4px 12px; max-width:72%; align-self:flex-end;
}

/* ── Markdown ───────────────────────────────────────────────────────── */
.zm-body h1,.zm-body h2,.zm-body h3 { font-weight:700; margin:10px 0 3px; font-size:1em; }
.zm-body ul,.zm-body ol { padding-left:20px; margin:5px 0; }
.zm-body li { margin:3px 0; }
.zm-body code { background:rgba(0,0,0,.06); padding:1px 5px; border-radius:4px; font-family:monospace; font-size:.83em; }
.zm-body pre  { background:#f8f9fa; border-radius:8px; padding:12px; overflow-x:auto; margin:8px 0; }
.zm-body pre code { background:none; padding:0; }
.zm-body strong { font-weight:700; }
.zm-body em { font-style:italic; }
.zm-body table { border-collapse:collapse; width:100%; margin:8px 0; font-size:.85em; }
.zm-body th,.zm-body td { border:1px solid #e5e7eb; padding:6px 10px; }
.zm-body th { background:#f9fafb; font-weight:600; }

/* ── Indicador de escritura ─────────────────────────────────────────── */
.ztyping { display:flex; gap:5px; padding:4px 0; }
.ztyping span { width:8px; height:8px; background:#9ca3af; border-radius:50%; animation:zdot 1.2s infinite; }
.ztyping span:nth-child(2) { animation-delay:.2s; }
.ztyping span:nth-child(3) { animation-delay:.4s; }
@@keyframes zdot { 0%,80%,100%{transform:scale(.7);opacity:.5} 40%{transform:scale(1);opacity:1} }

/* ── Barra de entrada ───────────────────────────────────────────────── */
.zp-footer { padding:12px 0 20px; background:#f8fafc; flex-shrink:0; }
.zp-input-wrap {
    display:flex; gap:8px; align-items:flex-end;
    background:#fff; border:1.5px solid #e5e7eb; border-radius:16px;
    padding:12px 12px 12px 18px;
    box-shadow:0 4px 20px rgba(0,0,0,.07);
    transition:border-color .2s, box-shadow .2s;
}
.zp-input-wrap:focus-within { border-color:#7c3aed; box-shadow:0 4px 24px rgba(99,102,241,.2); }
#zp-input {
    flex:1; border:none; outline:none; resize:none;
    font-size:.9rem; line-height:1.5; max-height:150px;
    background:transparent; font-family:inherit; color:#1f2937;
}
#zp-input::placeholder { color:#9ca3af; }
#zp-send {
    background:linear-gradient(135deg,#4f46e5,#7c3aed); color:#fff;
    border:none; border-radius:10px; width:38px; height:38px;
    display:flex; align-items:center; justify-content:center;
    cursor:pointer; flex-shrink:0; transition:opacity .15s; font-size:16px;
}
#zp-send:disabled { opacity:.4; cursor:not-allowed; }
.zp-hint { text-align:center; color:#9ca3af; font-size:.73rem; margin-top:8px; }

/* ── Botón nueva conversación ───────────────────────────────────────── */
.zp-new-btn {
    display:none; align-items:center; gap:6px;
    background:#f3f4f6; border:1px solid #e5e7eb; border-radius:8px;
    padding:5px 12px; font-size:.8rem; color:#374151; cursor:pointer;
    transition:background .15s; margin-bottom:8px;
}
.zp-new-btn:hover { background:#e5e7eb; }
.zp-new-btn.visible { display:inline-flex; }
</style>

<div class="zp">
    {{-- Bienvenida --}}
    <div id="zp-welcome" class="zp-welcome">
        <div class="zp-icon"><i class="bi bi-stars"></i></div>
        <div class="zp-title">
            <h2>¿En qué puedo ayudarte?</h2>
            <p>Asistente institucional impulsado por ZuraAI · Claude</p>
        </div>
        <div class="zp-cards">
            <button class="zp-card" data-prompt="Analiza el rendimiento académico del período actual y sugiere áreas de mejora">
                <div class="zp-card-icon">📊</div>
                <div class="zp-card-text">Analizar rendimiento académico</div>
                <div class="zp-card-hint">KPIs, tendencias y sugerencias</div>
            </button>
            <button class="zp-card" data-prompt="Redacta una circular oficial para padres y representantes sobre el calendario de evaluaciones del período">
                <div class="zp-card-icon">📝</div>
                <div class="zp-card-text">Redactar circular para padres</div>
                <div class="zp-card-hint">Comunicado oficial institucional</div>
            </button>
            <button class="zp-card" data-prompt="¿Cuáles son los indicadores y requisitos principales para el reporte SIGERD al MINERD?">
                <div class="zp-card-icon">🏛️</div>
                <div class="zp-card-text">Preparar reporte SIGERD/MINERD</div>
                <div class="zp-card-hint">Indicadores y formularios oficiales</div>
            </button>
            <button class="zp-card" data-prompt="Sugiere un plan de estrategias institucionales para mejorar la tasa de asistencia estudiantil">
                <div class="zp-card-icon">💡</div>
                <div class="zp-card-text">Plan para mejorar asistencia</div>
                <div class="zp-card-hint">Estrategias y acciones concretas</div>
            </button>
        </div>
    </div>

    {{-- Conversación --}}
    <div id="zp-msgs" class="zp-msgs" style="display:none"></div>

    {{-- Pie --}}
    <div class="zp-footer">
        <button id="zp-new" class="zp-new-btn" title="Nueva conversación">
            <i class="bi bi-plus-circle"></i> Nueva conversación
        </button>
        <div class="zp-input-wrap">
            <textarea id="zp-input" rows="1" placeholder="Escribe tu consulta institucional…"></textarea>
            <button id="zp-send" title="Enviar"><i class="bi bi-send-fill"></i></button>
        </div>
        <p class="zp-hint">ZuraAI puede cometer errores. Verifica la información importante antes de usarla en documentos oficiales.</p>
    </div>
</div>

<script>
(function(){
    const welcome = document.getElementById('zp-welcome');
    const msgs    = document.getElementById('zp-msgs');
    const input   = document.getElementById('zp-input');
    const sendBtn = document.getElementById('zp-send');
    const newBtn  = document.getElementById('zp-new');
    const cards   = document.querySelectorAll('.zp-card');
    const URL     = '{{ route("admin.asistente.chat") }}';
    const CSRF    = document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}';
    let history   = [];
    let streaming = false;

    /* ── Sugerencias ───────────────────────────────────────────────── */
    cards.forEach(c => c.addEventListener('click', () => {
        if (streaming) return;
        input.value = c.dataset.prompt;
        autoResize();
        send();
    }));

    /* ── Input ─────────────────────────────────────────────────────── */
    input.addEventListener('keydown', e => { if(e.key==='Enter'&&!e.shiftKey){e.preventDefault();send();} });
    input.addEventListener('input', autoResize);
    sendBtn.addEventListener('click', send);
    newBtn.addEventListener('click', resetConversation);

    function autoResize(){ input.style.height='auto'; input.style.height=Math.min(input.scrollHeight,150)+'px'; }

    /* ── Reset ─────────────────────────────────────────────────────── */
    function resetConversation(){
        history = [];
        msgs.innerHTML = '';
        msgs.style.display = 'none';
        welcome.style.display = 'flex';
        newBtn.classList.remove('visible');
        input.value = '';
        input.style.height = 'auto';
        input.focus();
    }

    /* ── Añadir mensaje ─────────────────────────────────────────────── */
    function addMsg(role, html){
        const row  = document.createElement('div');
        row.className = 'zm ' + role;

        const av = document.createElement('div');
        av.className = 'zm-av';
        av.innerHTML = role==='bot' ? '<i class="bi bi-stars"></i>' : '<i class="bi bi-person-fill"></i>';

        const body = document.createElement('div');
        body.className = 'zm-body';
        body.innerHTML = html;

        row.appendChild(av); row.appendChild(body);
        msgs.appendChild(row);
        msgs.scrollTop = msgs.scrollHeight;
        return body;
    }

    function showConversation(){
        welcome.style.display = 'none';
        msgs.style.display    = 'flex';
        newBtn.classList.add('visible');
    }

    /* ── Renderizado Markdown ───────────────────────────────────────── */
    function md(t){
        let s = t.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
        // bloques de código
        s = s.replace(/```[\w]*\n?([\s\S]*?)```/g,'<pre><code>$1</code></pre>');
        s = s.replace(/`([^`\n]+)`/g,'<code>$1</code>');
        // cabeceras
        s = s.replace(/^### (.+)$/gm,'<h3>$1</h3>');
        s = s.replace(/^## (.+)$/gm,'<h2>$1</h2>');
        s = s.replace(/^# (.+)$/gm,'<h1>$1</h1>');
        // negrita / cursiva
        s = s.replace(/\*\*(.+?)\*\*/g,'<strong>$1</strong>');
        s = s.replace(/\*(.+?)\*/g,'<em>$1</em>');
        // tablas simples (| col | col |)
        s = s.replace(/((\|[^\n]+\|\n?)+)/g, match => {
            const rows = match.trim().split('\n').filter(r => !/^[\|\s\-:]+$/.test(r));
            if(rows.length < 1) return match;
            const [head, ...body] = rows;
            const th = head.split('|').filter(c=>c.trim()).map(c=>`<th>${c.trim()}</th>`).join('');
            const trs = body.map(r => '<tr>' + r.split('|').filter(c=>c.trim()).map(c=>`<td>${c.trim()}</td>`).join('') + '</tr>').join('');
            return `<table><thead><tr>${th}</tr></thead><tbody>${trs}</tbody></table>`;
        });
        // listas
        s = s.replace(/^[*-] (.+)$/gm,'<li>$1</li>');
        s = s.replace(/^\d+\. (.+)$/gm,'<li>$1</li>');
        s = s.replace(/(<li>[^<]*<\/li>\n?)+/g, m => `<ul>${m}</ul>`);
        // saltos
        s = s.replace(/\n/g,'<br>');
        return s;
    }

    /* ── Envío ─────────────────────────────────────────────────────── */
    async function send(){
        const text = input.value.trim();
        if(!text || streaming) return;
        input.value=''; input.style.height='auto';
        streaming=true; sendBtn.disabled=true;

        showConversation();
        addMsg('usr', text.replace(/&/g,'&amp;').replace(/</g,'&lt;'));
        const bubble = addMsg('bot','<div class="ztyping"><span></span><span></span><span></span></div>');

        try {
            const res = await fetch(URL, {
                method:'POST',
                headers:{
                    'Content-Type':'application/json',
                    'X-CSRF-TOKEN':CSRF,
                    'Accept':'text/event-stream',
                    'X-Requested-With':'XMLHttpRequest',
                },
                body: JSON.stringify({message:text, history})
            });

            if(!res.ok){
                const errBody = await res.text().catch(()=>'');
                bubble.innerHTML = '<em class="text-danger">Error '+res.status+'. Intenta de nuevo.</em>';
                console.error('ZuraAI '+res.status, errBody);
                return;
            }

            const reader = res.body.getReader();
            const dec    = new TextDecoder();
            let buf='', out='';
            bubble.innerHTML = '';

            outer: while(true){
                const {done, value} = await reader.read();
                if(done) break;
                buf += dec.decode(value,{stream:true});
                const lines = buf.split('\n');
                buf = lines.pop();

                for(const line of lines){
                    if(!line.startsWith('data:')) continue;
                    let ev;
                    try { ev = JSON.parse(line.slice(5).trim()); } catch(e){ continue; }

                    if(ev.type==='content_block_delta' && ev.delta?.type==='text_delta'){
                        out += ev.delta.text;
                        bubble.innerHTML = md(out);
                        msgs.scrollTop = msgs.scrollHeight;
                    }
                    if(ev.type==='error'){
                        bubble.innerHTML = '<em class="text-danger">'+(ev.error?.message||'Error desconocido')+'</em>';
                        break outer;
                    }
                    if(ev.type==='message_stop') break outer;
                }
            }

            if(!out && !bubble.innerHTML.includes('text-danger')){
                bubble.innerHTML = '<em class="text-muted">Sin respuesta. Verifica que <strong>ANTHROPIC_API_KEY</strong> esté configurada en <code>.env</code>.</em>';
            }
            if(out){
                history.push({role:'user',content:text},{role:'assistant',content:out});
                if(history.length>20) history=history.slice(-20);
            }
        } catch(e){
            bubble.innerHTML = '<em class="text-danger">Error de conexión: '+e.message+'</em>';
            console.error('ZuraAI fetch error:', e);
        } finally {
            streaming=false; sendBtn.disabled=false; input.focus();
        }
    }
})();
</script>
@endsection
