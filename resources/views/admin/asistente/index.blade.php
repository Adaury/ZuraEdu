@extends('layouts.admin')
@section('title', 'ZuraAI — Asistente Institucional')
@section('content')
<style>
/* ── Reset layout admin ─────────────────────────────────────────────── */
.main-content { padding:0 !important; overflow:hidden !important; }
.main-content > footer,
.main-content > .alert { display:none !important; }

/* ── Contenedor ─────────────────────────────────────────────────────── */
.zp {
    height: calc(100vh - 60px);
    display: flex;
    flex-direction: column;
    position: relative;
    overflow: hidden;
    background: radial-gradient(ellipse at 60% 0%, #ede9fe 0%, #eef2ff 35%, #f0f9ff 65%, #f8fafc 100%);
}

/* Canvas de partículas */
#zp-canvas {
    position: absolute; inset: 0;
    pointer-events: none; z-index: 0;
}

/* ── Keyframes ──────────────────────────────────────────────────────── */
@@keyframes zpRise    { 0%{opacity:0;transform:translateY(60px) scale(.85)} 60%{opacity:1} 100%{opacity:1;transform:translateY(0) scale(1)} }
@@keyframes zpFloat   { 0%,100%{transform:translateY(0)} 50%{transform:translateY(-18px)} }
@@keyframes zpOrbit   { from{transform:rotate(0deg) translateX(48px)} to{transform:rotate(360deg) translateX(48px)} }
@@keyframes zpGlow    { 0%,100%{box-shadow:0 0 20px rgba(99,102,241,.3),0 0 0 0 transparent} 50%{box-shadow:0 0 50px rgba(99,102,241,.65),0 0 100px 20px rgba(124,58,237,.1)} }
@@keyframes zpPulse   { 0%,100%{opacity:.6;transform:scale(1)} 50%{opacity:1;transform:scale(1.15)} }
@@keyframes zpFadeUp  { from{opacity:0;transform:translateY(30px)} to{opacity:1;transform:translateY(0)} }
@@keyframes zdot      { 0%,80%,100%{transform:scale(.7);opacity:.5} 40%{transform:scale(1);opacity:1} }
@@keyframes zpCardIn  { 0%{opacity:0;transform:translateY(80px) scale(.9)} 100%{opacity:1;transform:translateY(0) scale(1)} }

/* ── Scroll central ─────────────────────────────────────────────────── */
.zp-body {
    flex: 1; overflow-y: auto; position: relative; z-index: 1; padding: 0 28px;
}
.zp-body::-webkit-scrollbar { width:4px; }
.zp-body::-webkit-scrollbar-thumb { background:rgba(99,102,241,.2); border-radius:2px; }

/* ── Welcome ────────────────────────────────────────────────────────── */
.zp-welcome {
    display: flex; flex-direction: column; align-items: center;
    justify-content: center; min-height: calc(100vh - 160px);
    gap: 32px; padding: 40px 0 20px; text-align: center;
    animation: zpFadeUp .7s cubic-bezier(.22,1,.36,1) both;
}

/* Ícono con satélites orbitales */
.zp-icon-wrap {
    position: relative; width: 100px; height: 100px;
    animation: zpFloat 4.5s ease-in-out infinite;
}
.zp-icon {
    width: 100px; height: 100px;
    background: linear-gradient(135deg, #4f46e5, #7c3aed);
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 44px; color: #fff;
    animation: zpGlow 4s ease-in-out infinite;
    position: relative; z-index: 2;
    box-shadow: 0 0 0 12px rgba(99,102,241,.08), 0 0 0 24px rgba(124,58,237,.04);
}
/* Satélites orbitales */
.zp-sat {
    position: absolute; top: 50%; left: 50%;
    width: 10px; height: 10px; margin: -5px 0 0 -5px;
    border-radius: 50%;
    transform-origin: 0 0;
    animation: zpOrbit linear infinite;
}
.zp-sat:nth-child(1){ background:#818cf8; animation-duration:3s;  width:10px;height:10px; }
.zp-sat:nth-child(2){ background:#a78bfa; animation-duration:5s;  width:7px;height:7px;  animation-delay:-.8s; }
.zp-sat:nth-child(3){ background:#34d399; animation-duration:4s;  width:6px;height:6px;  animation-delay:-2s; }
/* Anillo orbital */
.zp-ring {
    position: absolute; inset: -14px;
    border-radius: 50%;
    border: 1.5px dashed rgba(99,102,241,.2);
    animation: zpOrbit 12s linear infinite reverse;
    transform-origin: center center;
}

/* Headline */
.zp-headline { animation: zpRise .7s cubic-bezier(.22,1,.36,1) both .1s; }
.zp-headline h2 {
    font-size: 2.2rem; font-weight: 800; line-height: 1.15; margin-bottom: 10px;
    background: linear-gradient(135deg, #1e1b4b 0%, #4338ca 40%, #7c3aed 70%, #a855f7 100%);
    -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
}
.zp-headline p { color: #6b7280; font-size: .97rem; }

/* Tarjetas */
.zp-cards {
    display: grid; grid-template-columns: repeat(2,1fr);
    gap: 14px; width: 100%; max-width: 640px;
}
.zp-card {
    background: rgba(255,255,255,.7);
    backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px);
    border: 1.5px solid rgba(255,255,255,.85);
    border-radius: 18px; padding: 20px 18px; cursor: pointer;
    text-align: left;
    box-shadow: 0 4px 24px rgba(99,102,241,.08), 0 1px 4px rgba(0,0,0,.04);
    transition: all .3s cubic-bezier(.22,1,.36,1);
    animation: zpCardIn .6s cubic-bezier(.22,1,.36,1) both;
}
.zp-card:nth-child(1){ animation-delay:.15s; }
.zp-card:nth-child(2){ animation-delay:.25s; }
.zp-card:nth-child(3){ animation-delay:.35s; }
.zp-card:nth-child(4){ animation-delay:.45s; }
.zp-card:hover {
    transform: translateY(-8px) scale(1.03);
    border-color: rgba(124,58,237,.35);
    box-shadow: 0 20px 50px rgba(79,70,229,.2), 0 4px 12px rgba(0,0,0,.06);
    background: rgba(255,255,255,.92);
}
.zp-card-icon  { font-size: 24px; margin-bottom: 10px; }
.zp-card-text  { font-size: .875rem; color: #1f2937; line-height: 1.45; font-weight: 600; }
.zp-card-hint  { font-size: .77rem; color: #9ca3af; margin-top: 5px; }

/* ── Mensajes ───────────────────────────────────────────────────────── */
.zp-msgs-inner {
    display: flex; flex-direction: column; gap: 24px;
    padding: 28px 0 16px; max-width: 800px; margin: 0 auto;
}
.zm { display:flex; gap:14px; animation:zpFadeUp .3s ease both; }
.zm.usr { flex-direction:row-reverse; }
.zm-av {
    width:36px; height:36px; border-radius:50%; flex-shrink:0;
    display:flex; align-items:center; justify-content:center; font-size:16px;
    box-shadow:0 2px 8px rgba(0,0,0,.1);
}
.zm.bot .zm-av { background:linear-gradient(135deg,#4f46e5,#7c3aed); color:#fff; }
.zm.usr .zm-av { background:rgba(255,255,255,.8); color:#6b7280; border:1px solid #e5e7eb; }
.zm-body { flex:1; font-size:.9rem; line-height:1.75; color:#1f2937; }
.zm.usr .zm-body {
    background:rgba(255,255,255,.8); backdrop-filter:blur(8px);
    padding:10px 16px; border-radius:16px 16px 4px 16px;
    max-width:72%; align-self:flex-end;
    box-shadow:0 2px 10px rgba(0,0,0,.06); border:1px solid rgba(255,255,255,.9);
}
.zm-body h1,.zm-body h2,.zm-body h3{font-weight:700;margin:10px 0 3px;font-size:1em;}
.zm-body ul,.zm-body ol{padding-left:20px;margin:5px 0;}
.zm-body li{margin:3px 0;}
.zm-body code{background:rgba(99,102,241,.08);padding:1px 5px;border-radius:4px;font-family:monospace;font-size:.83em;color:#4f46e5;}
.zm-body pre{background:rgba(0,0,0,.04);border-radius:10px;padding:14px;overflow-x:auto;margin:8px 0;}
.zm-body pre code{background:none;padding:0;color:inherit;}
.zm-body strong{font-weight:700;} .zm-body em{font-style:italic;}
.zm-body table{border-collapse:collapse;width:100%;margin:10px 0;font-size:.85em;}
.zm-body th,.zm-body td{border:1px solid #e5e7eb;padding:7px 11px;}
.zm-body th{background:#f3f4f6;font-weight:600;}
.ztyping{display:flex;gap:5px;padding:6px 0;}
.ztyping span{width:8px;height:8px;background:#a5b4fc;border-radius:50%;animation:zdot 1.2s infinite;}
.ztyping span:nth-child(2){animation-delay:.2s;}
.ztyping span:nth-child(3){animation-delay:.4s;}

/* ── Footer input ───────────────────────────────────────────────────── */
.zp-footer {
    flex-shrink:0; padding:10px 28px 18px;
    position:relative; z-index:2;
    background:linear-gradient(to top, rgba(240,240,255,.98) 60%, transparent);
}
.zp-footer-inner { max-width:800px; margin:0 auto; }
.zp-new-btn {
    display:none; align-items:center; gap:6px;
    background:rgba(255,255,255,.8); backdrop-filter:blur(8px);
    border:1px solid rgba(99,102,241,.2); border-radius:10px;
    padding:5px 14px; font-size:.8rem; color:#4f46e5; font-weight:600;
    cursor:pointer; transition:all .2s; margin-bottom:10px;
}
.zp-new-btn:hover{background:#fff;border-color:rgba(124,58,237,.4);box-shadow:0 2px 12px rgba(99,102,241,.15);}
.zp-new-btn.visible{display:inline-flex;}
.zp-input-wrap {
    display:flex; gap:10px; align-items:flex-end;
    background:rgba(255,255,255,.85); backdrop-filter:blur(20px); -webkit-backdrop-filter:blur(20px);
    border:1.5px solid rgba(255,255,255,.9); border-radius:20px;
    padding:14px 14px 14px 22px;
    box-shadow:0 8px 40px rgba(99,102,241,.12),0 2px 8px rgba(0,0,0,.05);
    transition:all .25s;
}
.zp-input-wrap:focus-within{
    border-color:rgba(124,58,237,.45);
    box-shadow:0 8px 48px rgba(99,102,241,.22),0 2px 8px rgba(0,0,0,.05);
}
#zp-input{
    flex:1; border:none; outline:none; resize:none;
    font-size:.92rem; line-height:1.5; max-height:150px;
    background:transparent; font-family:inherit; color:#1f2937;
}
#zp-input::placeholder{color:#9ca3af;}
#zp-send{
    background:linear-gradient(135deg,#4f46e5,#7c3aed); color:#fff;
    border:none; border-radius:14px; width:42px; height:42px;
    display:flex; align-items:center; justify-content:center;
    cursor:pointer; flex-shrink:0; font-size:17px;
    box-shadow:0 4px 16px rgba(79,70,229,.35);
    transition:all .2s cubic-bezier(.22,1,.36,1);
}
#zp-send:hover{transform:scale(1.1) translateY(-2px);box-shadow:0 8px 28px rgba(79,70,229,.45);}
#zp-send:disabled{opacity:.4;cursor:not-allowed;transform:none;box-shadow:none;}
.zp-hint{text-align:center;color:#b0b7c3;font-size:.71rem;margin-top:8px;}
</style>

<div class="zp">
    <canvas id="zp-canvas"></canvas>

    <div class="zp-body" id="zp-body">
        {{-- Welcome --}}
        <div id="zp-welcome" class="zp-welcome">

            <div class="zp-icon-wrap">
                <div class="zp-ring"></div>
                <div class="zp-sat"></div>
                <div class="zp-sat"></div>
                <div class="zp-sat"></div>
                <div class="zp-icon"><i class="bi bi-stars"></i></div>
            </div>

            <div class="zp-headline">
                <h2>¿En qué puedo ayudarte?</h2>
                <p>Asistente institucional · ZuraAI &amp; Claude</p>
            </div>

            <div class="zp-cards">
                <button class="zp-card" data-prompt="Analiza el rendimiento académico del período actual y sugiere áreas de mejora prioritarias">
                    <div class="zp-card-icon">📊</div>
                    <div class="zp-card-text">Analizar rendimiento académico</div>
                    <div class="zp-card-hint">KPIs, tendencias y sugerencias</div>
                </button>
                <button class="zp-card" data-prompt="Redacta una circular oficial para padres sobre el calendario de evaluaciones del período">
                    <div class="zp-card-icon">📝</div>
                    <div class="zp-card-text">Redactar circular para padres</div>
                    <div class="zp-card-hint">Comunicado oficial institucional</div>
                </button>
                <button class="zp-card" data-prompt="¿Cuáles son los indicadores principales para el reporte SIGERD al MINERD?">
                    <div class="zp-card-icon">🏛️</div>
                    <div class="zp-card-text">Preparar reporte SIGERD/MINERD</div>
                    <div class="zp-card-hint">Indicadores y formularios oficiales</div>
                </button>
                <button class="zp-card" data-prompt="Sugiere estrategias institucionales para mejorar la tasa de asistencia estudiantil">
                    <div class="zp-card-icon">💡</div>
                    <div class="zp-card-text">Plan para mejorar asistencia</div>
                    <div class="zp-card-hint">Estrategias y acciones concretas</div>
                </button>
            </div>
        </div>

        {{-- Conversación --}}
        <div id="zp-msgs" class="zp-msgs-inner" style="display:none"></div>
    </div>

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
/* ── Partículas antigravity (flotan hacia arriba) ───────────────────── */
(function(){
    const canvas = document.getElementById('zp-canvas');
    const ctx    = canvas.getContext('2d');
    const colors = ['rgba(99,102,241,', 'rgba(124,58,237,', 'rgba(167,139,250,', 'rgba(16,185,129,', 'rgba(59,130,246,'];

    let W, H, particles = [];

    function resize(){
        W = canvas.width  = canvas.offsetWidth;
        H = canvas.height = canvas.offsetHeight;
    }

    function mkParticle(){
        return {
            x:     Math.random() * W,
            y:     H + Math.random() * 60,          // empieza abajo
            r:     Math.random() * 2.8 + 0.8,
            vx:    (Math.random() - .5) * 0.4,
            vy:    -(Math.random() * 0.7 + 0.25),   // sube (antigravity)
            alpha: Math.random() * 0.45 + 0.1,
            color: colors[Math.floor(Math.random() * colors.length)],
            wobble:Math.random() * Math.PI * 2,
            wobbleSpeed: Math.random() * 0.02 + 0.005,
        };
    }

    function init(){
        resize();
        particles = Array.from({length: 70}, mkParticle).map(p => ({
            ...p, y: Math.random() * H   // distribuye al inicio
        }));
    }

    function draw(){
        ctx.clearRect(0, 0, W, H);
        particles.forEach(p => {
            p.wobble += p.wobbleSpeed;
            p.x += p.vx + Math.sin(p.wobble) * 0.3;
            p.y += p.vy;

            if(p.y < -10) Object.assign(p, mkParticle());

            ctx.beginPath();
            ctx.arc(p.x, p.y, p.r, 0, Math.PI * 2);
            ctx.fillStyle = p.color + p.alpha + ')';
            ctx.fill();
        });
        requestAnimationFrame(draw);
    }

    init();
    draw();
    window.addEventListener('resize', () => { resize(); });
})();

/* ── Chat ───────────────────────────────────────────────────────────── */
(function(){
    const zpBody  = document.getElementById('zp-body');
    const welcome = document.getElementById('zp-welcome');
    const msgs    = document.getElementById('zp-msgs');
    const input   = document.getElementById('zp-input');
    const sendBtn = document.getElementById('zp-send');
    const newBtn  = document.getElementById('zp-new');
    const cards   = document.querySelectorAll('.zp-card');
    const URL     = '{{ route("admin.asistente.chat") }}';
    const CSRF    = document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}';
    let history   = [], streaming = false;

    cards.forEach(c => c.addEventListener('click', () => {
        if(streaming) return;
        input.value = c.dataset.prompt; autoResize(); send();
    }));
    input.addEventListener('keydown', e => { if(e.key==='Enter'&&!e.shiftKey){e.preventDefault();send();} });
    input.addEventListener('input', autoResize);
    sendBtn.addEventListener('click', send);
    newBtn.addEventListener('click', reset);

    function autoResize(){ input.style.height='auto'; input.style.height=Math.min(input.scrollHeight,150)+'px'; }

    function reset(){
        history=[]; msgs.innerHTML=''; msgs.style.display='none';
        welcome.style.display='flex'; newBtn.classList.remove('visible');
        input.value=''; input.style.height='auto'; input.focus();
    }

    function addMsg(role, html){
        const row=document.createElement('div'); row.className='zm '+role;
        const av=document.createElement('div');  av.className='zm-av';
        av.innerHTML=role==='bot'?'<i class="bi bi-stars"></i>':'<i class="bi bi-person-fill"></i>';
        const body=document.createElement('div'); body.className='zm-body';
        body.innerHTML=html;
        row.appendChild(av); row.appendChild(body);
        msgs.appendChild(row); zpBody.scrollTop=zpBody.scrollHeight;
        return body;
    }

    function showConversation(){
        welcome.style.display='none'; msgs.style.display='flex';
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
        s=s.replace(/((\|[^\n]+\|\n?)+)/g, m=>{
            const rows=m.trim().split('\n').filter(r=>!/^[\|\s\-:]+$/.test(r));
            if(!rows.length) return m;
            const [h,...b]=rows;
            const th=h.split('|').filter(c=>c.trim()).map(c=>`<th>${c.trim()}</th>`).join('');
            const trs=b.map(r=>'<tr>'+r.split('|').filter(c=>c.trim()).map(c=>`<td>${c.trim()}</td>`).join('')+'</tr>').join('');
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
        addMsg('usr', text.replace(/&/g,'&amp;').replace(/</g,'&lt;'));
        const bubble=addMsg('bot','<div class="ztyping"><span></span><span></span><span></span></div>');

        try {
            const res=await fetch(URL,{
                method:'POST',
                headers:{
                    'Content-Type':'application/json',
                    'X-CSRF-TOKEN':CSRF,
                    'Accept':'text/event-stream',
                    'X-Requested-With':'XMLHttpRequest',
                },
                body:JSON.stringify({message:text, history})
            });

            if(!res.ok){
                bubble.innerHTML='<em class="text-danger">Error '+res.status+'. Intenta de nuevo.</em>';
                console.error('ZuraAI',res.status,await res.text().catch(()=>''));
                return;
            }

            const reader=res.body.getReader(), dec=new TextDecoder();
            let buf='', out='';
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
                bubble.innerHTML='<em class="text-muted">Sin respuesta. Verifica que <code>ANTHROPIC_API_KEY</code> esté en <code>.env</code>.</em>';
            if(out){
                history.push({role:'user',content:text},{role:'assistant',content:out});
                if(history.length>20) history=history.slice(-20);
            }
        } catch(e){
            bubble.innerHTML='<em class="text-danger">Error: '+e.message+'</em>';
        } finally {
            streaming=false; sendBtn.disabled=false; input.focus();
        }
    }
})();
</script>
@endsection
