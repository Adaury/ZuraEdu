@extends('layouts.admin')
@section('title', 'ZuraAI — Asistente Institucional')
@section('content')
<style>
/* ── Neutralizar padding del layout y ocultar footer ───────────────── */
.main-content { padding:0 !important; overflow:hidden !important; }
.main-content > footer,
.main-content > .alert,
.main-content > div[style*="trial"] { display:none !important; }

/* ── Contenedor full-viewport ───────────────────────────────────────── */
.zp {
    height: calc(100vh - 60px);
    display: flex;
    flex-direction: column;
    position: relative;
    overflow: hidden;
    background: linear-gradient(150deg, #fafafe 0%, #eef2ff 40%, #f5f3ff 70%, #fafaff 100%);
}

/* ── Orbs decorativos (efecto antigravity Google) ───────────────────── */
.zp-orb1, .zp-orb2, .zp-orb3 {
    position: absolute; border-radius: 50%;
    pointer-events: none; filter: blur(60px);
}
.zp-orb1 {
    width:520px; height:520px;
    background: radial-gradient(circle, rgba(99,102,241,.12) 0%, transparent 70%);
    top:-120px; right:-80px;
    animation: orbMove 10s ease-in-out infinite;
}
.zp-orb2 {
    width:380px; height:380px;
    background: radial-gradient(circle, rgba(124,58,237,.09) 0%, transparent 70%);
    bottom:60px; left:-40px;
    animation: orbMove 13s ease-in-out infinite reverse;
}
.zp-orb3 {
    width:260px; height:260px;
    background: radial-gradient(circle, rgba(16,185,129,.07) 0%, transparent 70%);
    top:40%; left:55%;
    animation: orbMove 8s ease-in-out infinite 2s;
}

@@keyframes orbMove { 0%,100%{transform:translate(0,0)} 33%{transform:translate(20px,-30px)} 66%{transform:translate(-15px,20px)} }
@@keyframes zpFloat { 0%,100%{transform:translateY(0)} 50%{transform:translateY(-16px)} }
@@keyframes zpGlow  { 0%,100%{box-shadow:0 0 24px rgba(99,102,241,.3),0 0 0 0 rgba(99,102,241,.1)} 50%{box-shadow:0 0 50px rgba(99,102,241,.6),0 0 80px 20px rgba(124,58,237,.08)} }
@@keyframes zpFadeUp{ from{opacity:0;transform:translateY(24px)} to{opacity:1;transform:translateY(0)} }
@@keyframes zpScaleIn{ from{opacity:0;transform:scale(.92)} to{opacity:1;transform:scale(1)} }
@@keyframes zdot { 0%,80%,100%{transform:scale(.7);opacity:.5} 40%{transform:scale(1);opacity:1} }

/* ── Área scroll central ────────────────────────────────────────────── */
.zp-body {
    flex: 1;
    overflow-y: auto;
    position: relative;
    z-index: 1;
    padding: 0 24px;
}
.zp-body::-webkit-scrollbar { width: 4px; }
.zp-body::-webkit-scrollbar-thumb { background: rgba(99,102,241,.25); border-radius: 2px; }

/* ── Pantalla de bienvenida ─────────────────────────────────────────── */
.zp-welcome {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    min-height: calc(100vh - 160px);
    gap: 28px;
    padding: 40px 0 20px;
    text-align: center;
}
.zp-icon {
    width: 88px; height: 88px;
    background: linear-gradient(135deg, #4f46e5, #7c3aed);
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 40px; color: #fff;
    animation: zpFloat 4s ease-in-out infinite, zpGlow 4s ease-in-out infinite;
    position: relative; z-index: 2;
}
.zp-icon::before {
    content: '';
    position: absolute;
    inset: -8px;
    border-radius: 50%;
    background: linear-gradient(135deg, rgba(79,70,229,.2), rgba(124,58,237,.2));
    animation: zpGlow 4s ease-in-out infinite;
    z-index: -1;
}
.zp-headline {
    animation: zpFadeUp .55s ease both .1s;
}
.zp-headline h2 {
    font-size: 2rem; font-weight: 700;
    background: linear-gradient(135deg, #1e1b4b, #4f46e5, #7c3aed);
    -webkit-background-clip: text; -webkit-text-fill-color: transparent;
    background-clip: text;
    margin-bottom: 8px; line-height: 1.2;
}
.zp-headline p { color: #6b7280; font-size: .95rem; }

/* ── Tarjetas antigravity ───────────────────────────────────────────── */
.zp-cards {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 14px;
    width: 100%;
    max-width: 640px;
    animation: zpFadeUp .55s ease both .25s;
}
.zp-card {
    background: rgba(255,255,255,.75);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    border: 1.5px solid rgba(255,255,255,.9);
    border-radius: 16px;
    padding: 18px 16px;
    cursor: pointer;
    box-shadow: 0 4px 20px rgba(99,102,241,.08), 0 1px 4px rgba(0,0,0,.04);
    transition: all .25s cubic-bezier(.4,0,.2,1);
    text-align: left;
}
.zp-card:hover {
    border-color: rgba(124,58,237,.4);
    box-shadow: 0 8px 32px rgba(79,70,229,.2), 0 2px 8px rgba(0,0,0,.06);
    transform: translateY(-4px) scale(1.02);
    background: rgba(255,255,255,.95);
}
.zp-card:nth-child(1) { animation: zpFloat 5s ease-in-out infinite 0s; }
.zp-card:nth-child(2) { animation: zpFloat 5s ease-in-out infinite 1s; }
.zp-card:nth-child(3) { animation: zpFloat 5s ease-in-out infinite 2s; }
.zp-card:nth-child(4) { animation: zpFloat 5s ease-in-out infinite 3s; }
.zp-card:hover        { animation: none; }
.zp-card-icon  { font-size: 22px; margin-bottom: 8px; }
.zp-card-text  { font-size: .845rem; color: #374151; line-height: 1.45; font-weight: 600; }
.zp-card-hint  { font-size: .76rem; color: #9ca3af; margin-top: 4px; }

/* ── Mensajes ───────────────────────────────────────────────────────── */
.zp-msgs-inner {
    display: flex; flex-direction: column; gap: 24px;
    padding: 28px 0 16px;
    max-width: 800px; margin: 0 auto;
}
.zm { display: flex; gap: 14px; animation: zpFadeUp .3s ease both; }
.zm.usr { flex-direction: row-reverse; }
.zm-av {
    width: 36px; height: 36px; border-radius: 50%; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center; font-size: 15px;
    box-shadow: 0 2px 8px rgba(0,0,0,.1);
}
.zm.bot .zm-av { background: linear-gradient(135deg,#4f46e5,#7c3aed); color: #fff; }
.zm.usr .zm-av { background: #fff; color: #6b7280; border: 1px solid #e5e7eb; }
.zm-body { flex: 1; font-size: .9rem; line-height: 1.7; color: #1f2937; }
.zm.usr .zm-body {
    background: rgba(255,255,255,.85);
    backdrop-filter: blur(8px);
    padding: 10px 16px;
    border-radius: 16px 16px 4px 16px;
    max-width: 72%; align-self: flex-end;
    box-shadow: 0 2px 8px rgba(0,0,0,.06);
    border: 1px solid rgba(255,255,255,.9);
}
/* Markdown */
.zm-body h1,.zm-body h2,.zm-body h3 { font-weight:700; margin:10px 0 3px; font-size:1em; }
.zm-body ul,.zm-body ol { padding-left:20px; margin:5px 0; }
.zm-body li { margin:3px 0; }
.zm-body code { background:rgba(99,102,241,.08); padding:1px 5px; border-radius:4px; font-family:monospace; font-size:.83em; color:#4f46e5; }
.zm-body pre  { background:rgba(0,0,0,.04); border-radius:10px; padding:14px; overflow-x:auto; margin:8px 0; }
.zm-body pre code { background:none; padding:0; color:inherit; }
.zm-body strong { font-weight:700; }
.zm-body em { font-style:italic; }
.zm-body table { border-collapse:collapse; width:100%; margin:10px 0; font-size:.85em; }
.zm-body th,.zm-body td { border:1px solid #e5e7eb; padding:7px 11px; }
.zm-body th { background:#f3f4f6; font-weight:600; }

/* Typing */
.ztyping { display:flex; gap:5px; padding:6px 0; }
.ztyping span { width:8px; height:8px; background:#a5b4fc; border-radius:50%; animation:zdot 1.2s infinite; }
.ztyping span:nth-child(2) { animation-delay:.2s; }
.ztyping span:nth-child(3) { animation-delay:.4s; }

/* ── Barra inferior ─────────────────────────────────────────────────── */
.zp-footer {
    flex-shrink: 0;
    padding: 12px 24px 18px;
    position: relative; z-index: 2;
    background: linear-gradient(to top, rgba(248,247,255,.98) 60%, transparent);
}
.zp-footer-inner { max-width: 800px; margin: 0 auto; }
.zp-new-btn {
    display: none; align-items: center; gap: 6px;
    background: rgba(255,255,255,.8); backdrop-filter: blur(8px);
    border: 1px solid #e5e7eb; border-radius: 8px;
    padding: 5px 12px; font-size: .8rem; color: #4f46e5;
    cursor: pointer; transition: all .15s; margin-bottom: 10px;
    font-weight: 500;
}
.zp-new-btn:hover { background: rgba(255,255,255,1); border-color: #7c3aed; }
.zp-new-btn.visible { display: inline-flex; }
.zp-input-wrap {
    display: flex; gap: 8px; align-items: flex-end;
    background: rgba(255,255,255,.9);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    border: 1.5px solid rgba(255,255,255,.95);
    border-radius: 18px;
    padding: 12px 12px 12px 20px;
    box-shadow: 0 4px 24px rgba(99,102,241,.12), 0 1px 4px rgba(0,0,0,.06);
    transition: border-color .2s, box-shadow .2s;
}
.zp-input-wrap:focus-within {
    border-color: rgba(124,58,237,.5);
    box-shadow: 0 4px 32px rgba(99,102,241,.2), 0 1px 4px rgba(0,0,0,.06);
}
#zp-input {
    flex: 1; border: none; outline: none; resize: none;
    font-size: .9rem; line-height: 1.5; max-height: 150px;
    background: transparent; font-family: inherit; color: #1f2937;
}
#zp-input::placeholder { color: #9ca3af; }
#zp-send {
    background: linear-gradient(135deg,#4f46e5,#7c3aed); color:#fff;
    border:none; border-radius:12px; width:40px; height:40px;
    display:flex; align-items:center; justify-content:center;
    cursor:pointer; flex-shrink:0; transition:all .15s; font-size:16px;
    box-shadow: 0 2px 8px rgba(79,70,229,.3);
}
#zp-send:hover { transform: scale(1.05); box-shadow: 0 4px 16px rgba(79,70,229,.4); }
#zp-send:disabled { opacity:.4; cursor:not-allowed; transform:none; }
.zp-hint { text-align:center; color:#b0b7c3; font-size:.72rem; margin-top:8px; }
</style>

<div class="zp">
    {{-- Orbs decorativos --}}
    <div class="zp-orb1"></div>
    <div class="zp-orb2"></div>
    <div class="zp-orb3"></div>

    {{-- Scroll central --}}
    <div class="zp-body" id="zp-body">
        {{-- Welcome --}}
        <div id="zp-welcome" class="zp-welcome">
            <div class="zp-icon"><i class="bi bi-stars"></i></div>
            <div class="zp-headline">
                <h2>¿En qué puedo ayudarte?</h2>
                <p>Asistente institucional · impulsado por ZuraAI &amp; Claude</p>
            </div>
            <div class="zp-cards">
                <button class="zp-card" data-prompt="Analiza el rendimiento académico del período actual y sugiere áreas de mejora prioritarias">
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
        <div id="zp-msgs" class="zp-msgs-inner" style="display:none"></div>
    </div>

    {{-- Footer con input --}}
    <div class="zp-footer">
        <div class="zp-footer-inner">
            <button id="zp-new" class="zp-new-btn">
                <i class="bi bi-plus-circle"></i> Nueva conversación
            </button>
            <div class="zp-input-wrap">
                <textarea id="zp-input" rows="1" placeholder="Escribe tu consulta institucional…"></textarea>
                <button id="zp-send" title="Enviar"><i class="bi bi-send-fill"></i></button>
            </div>
            <p class="zp-hint">ZuraAI puede cometer errores. Verifica la información antes de usarla en documentos oficiales.</p>
        </div>
    </div>
</div>

<script>
(function(){
    const zpBody  = document.getElementById('zp-body');
    const welcome = document.getElementById('zp-welcome');
    const msgs    = document.getElementById('zp-msgs');
    const input   = document.getElementById('zp-input');
    const sendBtn = document.getElementById('zp-send');
    const newBtn  = document.getElementById('zp-new');
    const cards   = document.querySelectorAll('.zp-card');
    const CHAT_URL= '{{ route("admin.asistente.chat") }}';
    const CSRF    = document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}';
    let history   = [];
    let streaming = false;

    cards.forEach(c => c.addEventListener('click', () => {
        if (streaming) return;
        input.value = c.dataset.prompt;
        autoResize();
        send();
    }));

    input.addEventListener('keydown', e => { if(e.key==='Enter'&&!e.shiftKey){e.preventDefault();send();} });
    input.addEventListener('input', autoResize);
    sendBtn.addEventListener('click', send);
    newBtn.addEventListener('click', reset);

    function autoResize(){ input.style.height='auto'; input.style.height=Math.min(input.scrollHeight,150)+'px'; }

    function reset(){
        history=[]; msgs.innerHTML='';
        msgs.style.display='none';
        welcome.style.display='flex';
        newBtn.classList.remove('visible');
        input.value=''; input.style.height='auto'; input.focus();
    }

    function addMsg(role, html){
        const row=document.createElement('div'); row.className='zm '+role;
        const av=document.createElement('div');  av.className='zm-av';
        av.innerHTML=role==='bot'?'<i class="bi bi-stars"></i>':'<i class="bi bi-person-fill"></i>';
        const body=document.createElement('div'); body.className='zm-body';
        body.innerHTML=html;
        row.appendChild(av); row.appendChild(body);
        msgs.appendChild(row);
        zpBody.scrollTop=zpBody.scrollHeight;
        return body;
    }

    function showConversation(){
        welcome.style.display='none';
        msgs.style.display='flex';
        newBtn.classList.add('visible');
    }

    function md(t){
        let s=t.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
        s=s.replace(/```[\w]*\n?([\s\S]*?)```/g,'<pre><code>$1</code></pre>');
        s=s.replace(/`([^`\n]+)`/g,'<code>$1</code>');
        s=s.replace(/^### (.+)$/gm,'<h3>$1</h3>');
        s=s.replace(/^## (.+)$/gm,'<h2>$1</h2>');
        s=s.replace(/^# (.+)$/gm,'<h1>$1</h1>');
        s=s.replace(/\*\*(.+?)\*\*/g,'<strong>$1</strong>');
        s=s.replace(/\*(.+?)\*/g,'<em>$1</em>');
        s=s.replace(/((\|[^\n]+\|\n?)+)/g,match=>{
            const rows=match.trim().split('\n').filter(r=>!/^[\|\s\-:]+$/.test(r));
            if(!rows.length) return match;
            const [head,...body]=rows;
            const th=head.split('|').filter(c=>c.trim()).map(c=>`<th>${c.trim()}</th>`).join('');
            const trs=body.map(r=>'<tr>'+r.split('|').filter(c=>c.trim()).map(c=>`<td>${c.trim()}</td>`).join('')+'</tr>').join('');
            return `<table><thead><tr>${th}</tr></thead><tbody>${trs}</tbody></table>`;
        });
        s=s.replace(/^[*-] (.+)$/gm,'<li>$1</li>');
        s=s.replace(/^\d+\. (.+)$/gm,'<li>$1</li>');
        s=s.replace(/(<li>[^<]*<\/li>\n?)+/g,m=>`<ul>${m}</ul>`);
        s=s.replace(/\n/g,'<br>');
        return s;
    }

    async function send(){
        const text=input.value.trim();
        if(!text||streaming) return;
        input.value=''; input.style.height='auto';
        streaming=true; sendBtn.disabled=true;

        showConversation();
        addMsg('usr',text.replace(/&/g,'&amp;').replace(/</g,'&lt;'));
        const bubble=addMsg('bot','<div class="ztyping"><span></span><span></span><span></span></div>');

        try {
            const res=await fetch(CHAT_URL,{
                method:'POST',
                headers:{
                    'Content-Type':'application/json',
                    'X-CSRF-TOKEN':CSRF,
                    'Accept':'text/event-stream',
                    'X-Requested-With':'XMLHttpRequest',
                },
                body:JSON.stringify({message:text,history})
            });

            if(!res.ok){
                bubble.innerHTML='<em class="text-danger">Error '+res.status+'. Intenta de nuevo.</em>';
                console.error('ZuraAI error',res.status,await res.text().catch(()=>''));
                return;
            }

            const reader=res.body.getReader(), dec=new TextDecoder();
            let buf='',out='';
            bubble.innerHTML='';

            outer:while(true){
                const{done,value}=await reader.read();
                if(done) break;
                buf+=dec.decode(value,{stream:true});
                const lines=buf.split('\n'); buf=lines.pop();
                for(const line of lines){
                    if(!line.startsWith('data:')) continue;
                    let ev; try{ev=JSON.parse(line.slice(5).trim());}catch{continue;}
                    if(ev.type==='content_block_delta'&&ev.delta?.type==='text_delta'){
                        out+=ev.delta.text;
                        bubble.innerHTML=md(out);
                        zpBody.scrollTop=zpBody.scrollHeight;
                    }
                    if(ev.type==='error'){bubble.innerHTML='<em class="text-danger">'+(ev.error?.message||'Error')+'</em>';break outer;}
                    if(ev.type==='message_stop') break outer;
                }
            }
            if(!out&&!bubble.innerHTML.includes('text-danger'))
                bubble.innerHTML='<em class="text-muted">Sin respuesta. Verifica que <strong>ANTHROPIC_API_KEY</strong> esté en <code>.env</code>.</em>';
            if(out){
                history.push({role:'user',content:text},{role:'assistant',content:out});
                if(history.length>20) history=history.slice(-20);
            }
        } catch(e){
            bubble.innerHTML='<em class="text-danger">Error de conexión: '+e.message+'</em>';
        } finally {
            streaming=false; sendBtn.disabled=false; input.focus();
        }
    }
})();
</script>
@endsection
