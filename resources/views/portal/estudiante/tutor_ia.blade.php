@extends('layouts.portal-estudiante')
@section('title', 'Tutor IA — Portal Estudiante')
@section('activeKey', 'tutor-ia')

@section('content')
<style>
/* ── Reset layout portal ──────────────────────────────────────────── */
.portal-content-area { padding: 0 !important; overflow: hidden !important; }
.portal-content-area > footer,
.portal-content-area > .alert,
.portal-content-area > .breadcrumb-wrap { display: none !important; }

/* ── Paleta Tutor IA ─────────────────────────────────────────────── */
:root {
  --ti-1: #0ea5e9;   /* sky-500 */
  --ti-2: #6366f1;   /* indigo-500 */
  --ti-3: #10b981;   /* emerald-500 */
  --ti-grad: linear-gradient(135deg, #0ea5e9, #6366f1);
}

/* ── Contenedor principal ─────────────────────────────────────────── */
.ti-wrap {
    height: calc(100vh - 58px);
    display: flex;
    flex-direction: column;
    position: relative;
    overflow: hidden;
    background: radial-gradient(ellipse at 30% 0%, #e0f2fe 0%, #ede9fe 30%, #ecfdf5 65%, #f8fafc 100%);
}

#ti-canvas {
    position: absolute; inset: 0;
    pointer-events: none; z-index: 0;
}

/* ── Animaciones ─────────────────────────────────────────────────── */
@keyframes tiRise   { 0%{opacity:0;transform:translateY(50px) scale(.88)} 100%{opacity:1;transform:none} }
@keyframes tiFloat  { 0%,100%{transform:translateY(0)} 50%{transform:translateY(-14px)} }
@keyframes tiOrbit  { from{transform:rotate(0deg) translateX(46px)} to{transform:rotate(360deg) translateX(46px)} }
@keyframes tiGlow   { 0%,100%{box-shadow:0 0 20px rgba(14,165,233,.3)} 50%{box-shadow:0 0 52px rgba(14,165,233,.65),0 0 90px 18px rgba(99,102,241,.1)} }
@keyframes tiFadeUp { from{opacity:0;transform:translateY(24px)} to{opacity:1;transform:none} }
@keyframes tdot     { 0%,80%,100%{transform:scale(.7);opacity:.5} 40%{transform:scale(1);opacity:1} }
@keyframes tiCardIn { 0%{opacity:0;transform:translateY(60px) scale(.92)} 100%{opacity:1;transform:none} }

/* ── Scroll ──────────────────────────────────────────────────────── */
.ti-body {
    flex: 1; overflow-y: auto; position: relative; z-index: 1; padding: 0 24px;
    scroll-behavior: smooth;
}
.ti-body::-webkit-scrollbar { width: 4px; }
.ti-body::-webkit-scrollbar-thumb { background: rgba(14,165,233,.25); border-radius: 2px; }

/* ── Bienvenida ──────────────────────────────────────────────────── */
.ti-welcome {
    display: flex; flex-direction: column; align-items: center;
    justify-content: center; min-height: calc(100vh - 200px);
    gap: 28px; padding: 32px 0 16px; text-align: center;
    animation: tiFadeUp .6s cubic-bezier(.22,1,.36,1) both;
}

.ti-icon-wrap {
    position: relative; width: 92px; height: 92px;
    animation: tiFloat 4s ease-in-out infinite;
}
.ti-icon {
    width: 92px; height: 92px;
    background: var(--ti-grad);
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 40px; color: #fff;
    animation: tiGlow 4s ease-in-out infinite;
    position: relative; z-index: 2;
    box-shadow: 0 0 0 10px rgba(14,165,233,.08), 0 0 0 22px rgba(99,102,241,.04);
}
.ti-sat {
    position: absolute; top:50%; left:50%;
    width: 9px; height: 9px; margin: -4.5px 0 0 -4.5px;
    border-radius: 50%; transform-origin: 0 0;
    animation: tiOrbit linear infinite;
}
.ti-sat:nth-child(1){ background:#38bdf8; animation-duration:2.8s; }
.ti-sat:nth-child(2){ background:#a78bfa; animation-duration:4.5s; animation-delay:-.7s; width:6px;height:6px; }
.ti-sat:nth-child(3){ background:#34d399; animation-duration:3.7s; animation-delay:-1.8s; width:7px;height:7px; }
.ti-ring {
    position: absolute; inset: -12px; border-radius: 50%;
    border: 1.5px dashed rgba(14,165,233,.22);
    animation: tiOrbit 14s linear infinite reverse;
    transform-origin: center;
}

.ti-headline { animation: tiRise .6s cubic-bezier(.22,1,.36,1) both .08s; }
.ti-headline h2 {
    font-size: 2rem; font-weight: 800; line-height: 1.15; margin-bottom: 8px;
    background: linear-gradient(135deg, #0c4a6e 0%, #0ea5e9 40%, #6366f1 70%, #8b5cf6 100%);
    -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
}
.ti-headline p { color: #6b7280; font-size: .92rem; }

/* Chips de materias sugeridas */
.ti-subjects {
    display: flex; flex-wrap: wrap; gap: 8px; justify-content: center;
    max-width: 560px; animation: tiRise .6s cubic-bezier(.22,1,.36,1) both .18s;
}
.ti-subj {
    background: rgba(255,255,255,.75); backdrop-filter: blur(12px);
    border: 1.5px solid rgba(14,165,233,.2); border-radius: 99px;
    padding: 6px 16px; font-size: .8rem; font-weight: 700; color: #0369a1;
    cursor: pointer; transition: all .25s; display: flex; align-items: center; gap: 5px;
}
.ti-subj:hover {
    background: var(--ti-1); color: #fff; border-color: var(--ti-1);
    transform: translateY(-2px); box-shadow: 0 4px 16px rgba(14,165,233,.3);
}

/* Tarjetas sugeridas */
.ti-cards {
    display: grid; grid-template-columns: repeat(2,1fr);
    gap: 12px; width: 100%; max-width: 600px;
    animation: tiRise .6s cubic-bezier(.22,1,.36,1) both .25s;
}
.ti-card {
    background: rgba(255,255,255,.72);
    backdrop-filter: blur(18px); -webkit-backdrop-filter: blur(18px);
    border: 1.5px solid rgba(255,255,255,.88);
    border-radius: 18px; padding: 18px 16px; cursor: pointer; text-align: left;
    box-shadow: 0 4px 20px rgba(14,165,233,.07), 0 1px 4px rgba(0,0,0,.04);
    transition: all .28s cubic-bezier(.22,1,.36,1);
    animation: tiCardIn .55s cubic-bezier(.22,1,.36,1) both;
}
.ti-card:nth-child(1){ animation-delay:.12s; }
.ti-card:nth-child(2){ animation-delay:.22s; }
.ti-card:nth-child(3){ animation-delay:.32s; }
.ti-card:nth-child(4){ animation-delay:.42s; }
.ti-card:hover {
    transform: translateY(-7px) scale(1.03);
    border-color: rgba(14,165,233,.35);
    box-shadow: 0 18px 48px rgba(14,165,233,.18), 0 4px 12px rgba(0,0,0,.05);
    background: rgba(255,255,255,.92);
}
.ti-card-icon { font-size: 22px; margin-bottom: 8px; }
.ti-card-text { font-size: .83rem; color: #1f2937; font-weight: 700; line-height: 1.4; }
.ti-card-hint { font-size: .73rem; color: #9ca3af; margin-top: 4px; }

/* ── Mensajes ─────────────────────────────────────────────────────── */
.ti-msgs {
    display: flex; flex-direction: column; gap: 22px;
    padding: 24px 0 16px; max-width: 780px; margin: 0 auto;
}
.tm { display:flex; gap:12px; animation:tiFadeUp .3s ease both; }
.tm.usr { flex-direction:row-reverse; }
.tm-av {
    width:34px; height:34px; border-radius:50%; flex-shrink:0;
    display:flex; align-items:center; justify-content:center; font-size:15px;
}
.tm.bot .tm-av { background: var(--ti-grad); color:#fff; }
.tm.usr .tm-av { background:rgba(255,255,255,.85); color:#6b7280; border:1px solid #e5e7eb; }
.tm-body { flex:1; font-size:.88rem; line-height:1.78; color:#1f2937; }
.tm.usr .tm-body {
    background:rgba(255,255,255,.82); backdrop-filter:blur(8px);
    padding:10px 15px; border-radius:16px 16px 4px 16px;
    max-width:74%; align-self:flex-end;
    box-shadow:0 2px 10px rgba(0,0,0,.06); border:1px solid rgba(255,255,255,.9);
}
.tm-body h1,.tm-body h2,.tm-body h3{font-weight:700;margin:10px 0 3px;font-size:1em;}
.tm-body ul,.tm-body ol{padding-left:20px;margin:5px 0;}
.tm-body li{margin:3px 0;}
.tm-body code{background:rgba(14,165,233,.1);padding:1px 5px;border-radius:4px;font-family:monospace;font-size:.82em;color:#0369a1;}
.tm-body pre{background:rgba(0,0,0,.04);border-radius:10px;padding:13px;overflow-x:auto;margin:8px 0;}
.tm-body pre code{background:none;padding:0;color:inherit;}
.tm-body strong{font-weight:700;} .tm-body em{font-style:italic;}
.tm-body table{border-collapse:collapse;width:100%;margin:10px 0;font-size:.83em;}
.tm-body th,.tm-body td{border:1px solid #e5e7eb;padding:7px 10px;}
.tm-body th{background:#f0f9ff;font-weight:700;color:#0369a1;}
.ttyping{display:flex;gap:5px;padding:6px 0;}
.ttyping span{width:7px;height:7px;background:#7dd3fc;border-radius:50%;animation:tdot 1.2s infinite;}
.ttyping span:nth-child(2){animation-delay:.2s;}
.ttyping span:nth-child(3){animation-delay:.4s;}

/* ── Footer input ─────────────────────────────────────────────────── */
.ti-footer {
    flex-shrink: 0; padding: 8px 24px 16px;
    position: relative; z-index: 2;
    background: linear-gradient(to top, rgba(236,244,255,.98) 55%, transparent);
}
.ti-footer-inner { max-width: 780px; margin: 0 auto; }
.ti-new-btn {
    display:none; align-items:center; gap:5px;
    background:rgba(255,255,255,.8); backdrop-filter:blur(8px);
    border:1px solid rgba(14,165,233,.22); border-radius:10px;
    padding:5px 14px; font-size:.78rem; color:var(--ti-1); font-weight:700;
    cursor:pointer; transition:all .2s; margin-bottom:8px;
}
.ti-new-btn:hover{background:#fff;border-color:rgba(14,165,233,.45);box-shadow:0 2px 12px rgba(14,165,233,.15);}
.ti-new-btn.visible{display:inline-flex;}
.ti-input-wrap {
    display:flex; gap:10px; align-items:flex-end;
    background:rgba(255,255,255,.88); backdrop-filter:blur(18px);
    border:1.5px solid rgba(255,255,255,.92); border-radius:20px;
    padding:12px 12px 12px 20px;
    box-shadow:0 8px 36px rgba(14,165,233,.11),0 2px 8px rgba(0,0,0,.05);
    transition:all .22s;
}
.ti-input-wrap:focus-within{
    border-color:rgba(14,165,233,.45);
    box-shadow:0 8px 44px rgba(14,165,233,.22),0 2px 8px rgba(0,0,0,.05);
}
#ti-input{
    flex:1; border:none; outline:none; resize:none;
    font-size:.9rem; line-height:1.5; max-height:140px;
    background:transparent; font-family:inherit; color:#1f2937;
}
#ti-input::placeholder{color:#9ca3af;}
#ti-send{
    background:var(--ti-grad); color:#fff;
    border:none; border-radius:13px; width:40px; height:40px;
    display:flex; align-items:center; justify-content:center;
    cursor:pointer; flex-shrink:0; font-size:15px;
    box-shadow:0 4px 16px rgba(14,165,233,.38);
    transition:all .2s cubic-bezier(.22,1,.36,1);
}
#ti-send:hover{transform:scale(1.1) translateY(-2px);box-shadow:0 8px 28px rgba(14,165,233,.5);}
#ti-send:disabled{opacity:.4;cursor:not-allowed;transform:none;box-shadow:none;}
.ti-hint{text-align:center;color:#b0b7c3;font-size:.7rem;margin-top:6px;}

/* ── Responsive ──────────────────────────────────────────────────── */
@media(max-width:560px){
    .ti-cards{ grid-template-columns:1fr; }
    .ti-headline h2{ font-size:1.5rem; }
    .ti-body { padding: 0 14px; }
    .ti-footer { padding: 8px 14px 12px; }
}
</style>

<div class="ti-wrap">
    <canvas id="ti-canvas"></canvas>

    <div class="ti-body" id="ti-body">

        {{-- ── Bienvenida ─────────────────────────────────────────── --}}
        <div id="ti-welcome" class="ti-welcome">

            <div class="ti-icon-wrap">
                <div class="ti-ring"></div>
                <div class="ti-sat"></div>
                <div class="ti-sat"></div>
                <div class="ti-sat"></div>
                <div class="ti-icon">🤖</div>
            </div>

            <div class="ti-headline">
                <h2>Tu Tutor IA personal</h2>
                <p>Pregunta cualquier cosa de tus materias · ZuraAI siempre está aquí</p>
            </div>

            {{-- Chips de materias --}}
            <div class="ti-subjects">
                @php
                $materias = [
                    ['📐','Matemáticas'],['📖','Lengua Española'],['🔬','Ciencias Naturales'],
                    ['🌍','Ciencias Sociales'],['🇺🇸','Inglés'],['💻','Informática'],
                    ['🎨','Arte'],['🏃','Ed. Física'],
                ];
                @endphp
                @foreach($materias as $m)
                <button class="ti-subj" data-subject="{{ $m[1] }}">{{ $m[0] }} {{ $m[1] }}</button>
                @endforeach
            </div>

            {{-- Tarjetas sugeridas --}}
            <div class="ti-cards">
                <button class="ti-card" data-prompt="Explícame paso a paso cómo resolver ecuaciones de primer grado con ejemplos fáciles de entender">
                    <div class="ti-card-icon">📐</div>
                    <div class="ti-card-text">Resolver ecuaciones de 1er grado</div>
                    <div class="ti-card-hint">Con ejemplos paso a paso</div>
                </button>
                <button class="ti-card" data-prompt="¿Cómo estructuro correctamente un ensayo académico? Dame un esquema y un ejemplo">
                    <div class="ti-card-icon">✍️</div>
                    <div class="ti-card-text">Cómo escribir un ensayo</div>
                    <div class="ti-card-hint">Estructura y ejemplo incluidos</div>
                </button>
                <button class="ti-card" data-prompt="Crea un plan de estudio de 5 días para prepararme para un examen de Ciencias Naturales">
                    <div class="ti-card-icon">📅</div>
                    <div class="ti-card-text">Plan de estudio para examen</div>
                    <div class="ti-card-hint">Organiza tu tiempo de repaso</div>
                </button>
                <button class="ti-card" data-prompt="Explícame las causas y consecuencias de la Independencia Dominicana de forma clara y resumida">
                    <div class="ti-card-icon">🏛️</div>
                    <div class="ti-card-text">Historia Dominicana: Independencia</div>
                    <div class="ti-card-hint">Causas, hechos y consecuencias</div>
                </button>
            </div>
        </div>

        {{-- Mensajes --}}
        <div id="ti-msgs" class="ti-msgs" style="display:none"></div>
    </div>

    {{-- Footer con input --}}
    <div class="ti-footer">
        <div class="ti-footer-inner">
            <button id="ti-new" class="ti-new-btn">
                <i class="bi bi-plus-circle"></i> Nueva pregunta
            </button>
            <div class="ti-input-wrap">
                <textarea id="ti-input" rows="1" placeholder="Escribe tu pregunta o duda aquí…"></textarea>
                <button id="ti-send" title="Enviar"><i class="bi bi-send-fill"></i></button>
            </div>
            <p class="ti-hint">Tutor IA puede cometer errores. Verifica con tu docente antes de un examen.</p>
        </div>
    </div>
</div>

<script>
/* ── Partículas flotantes ────────────────────────────────────────── */
(function(){
    const canvas=document.getElementById('ti-canvas'), ctx=canvas.getContext('2d');
    const colors=['rgba(14,165,233,','rgba(99,102,241,','rgba(16,185,129,','rgba(56,189,248,','rgba(167,139,250,'];
    let W,H,pts=[];
    function resize(){ W=canvas.width=canvas.offsetWidth; H=canvas.height=canvas.offsetHeight; }
    function mk(){
        return{x:Math.random()*W,y:H+Math.random()*50,r:Math.random()*2.5+.8,
               vx:(Math.random()-.5)*.35,vy:-(Math.random()*.65+.2),
               alpha:Math.random()*.4+.08,color:colors[Math.floor(Math.random()*colors.length)],
               w:Math.random()*Math.PI*2,ws:Math.random()*.018+.004};
    }
    function init(){resize();pts=Array.from({length:65},()=>({...mk(),y:Math.random()*H}));}
    function draw(){
        ctx.clearRect(0,0,W,H);
        pts.forEach(p=>{
            p.w+=p.ws;p.x+=p.vx+Math.sin(p.w)*.28;p.y+=p.vy;
            if(p.y<-8)Object.assign(p,mk());
            ctx.beginPath();ctx.arc(p.x,p.y,p.r,0,Math.PI*2);
            ctx.fillStyle=p.color+p.alpha+')';ctx.fill();
        });
        requestAnimationFrame(draw);
    }
    init();draw();
    window.addEventListener('resize',()=>{ resize(); });
})();

/* ── Chat ─────────────────────────────────────────────────────────── */
(function(){
    const tiBody  = document.getElementById('ti-body');
    const welcome = document.getElementById('ti-welcome');
    const msgs    = document.getElementById('ti-msgs');
    const input   = document.getElementById('ti-input');
    const sendBtn = document.getElementById('ti-send');
    const newBtn  = document.getElementById('ti-new');
    const cards   = document.querySelectorAll('.ti-card');
    const subjects= document.querySelectorAll('.ti-subj');
    const URL     = '{{ route("portal.estudiante.asistente.chat") }}';
    const CSRF    = document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}';
    let history=[], streaming=false;

    cards.forEach(c=>c.addEventListener('click',()=>{ if(streaming)return; input.value=c.dataset.prompt; autoResize(); send(); }));
    subjects.forEach(s=>s.addEventListener('click',()=>{
        if(streaming)return;
        const mat=s.dataset.subject;
        input.value=`Necesito ayuda con ${mat}. `;
        input.focus(); autoResize();
    }));
    input.addEventListener('keydown', e=>{ if(e.key==='Enter'&&!e.shiftKey){e.preventDefault();send();} });
    input.addEventListener('input', autoResize);
    sendBtn.addEventListener('click', send);
    newBtn.addEventListener('click', reset);

    function autoResize(){ input.style.height='auto'; input.style.height=Math.min(input.scrollHeight,140)+'px'; }

    function reset(){
        history=[]; msgs.innerHTML=''; msgs.style.display='none';
        welcome.style.display='flex'; newBtn.classList.remove('visible');
        input.value=''; input.style.height='auto'; input.focus();
    }

    function addMsg(role, html){
        const row=document.createElement('div'); row.className='tm '+role;
        const av =document.createElement('div'); av.className='tm-av';
        av.innerHTML=role==='bot'?'🤖':'<i class="bi bi-person-fill"></i>';
        const body=document.createElement('div'); body.className='tm-body';
        body.innerHTML=html;
        row.appendChild(av); row.appendChild(body);
        msgs.appendChild(row); tiBody.scrollTop=tiBody.scrollHeight;
        return body;
    }

    function showChat(){
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
        s=s.replace(/((\|[^\n]+\|\n?)+)/g,m=>{
            const rows=m.trim().split('\n').filter(r=>!/^[\|\s\-:]+$/.test(r));
            if(!rows.length) return m;
            const[h,...b]=rows;
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
        if(!text||streaming)return;
        input.value=''; input.style.height='auto';
        streaming=true; sendBtn.disabled=true;

        showChat();
        addMsg('usr', text.replace(/&/g,'&amp;').replace(/</g,'&lt;'));
        const bubble=addMsg('bot','<div class="ttyping"><span></span><span></span><span></span></div>');

        try{
            const res=await fetch(URL,{
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
                bubble.innerHTML='<em style="color:#dc2626">Error '+res.status+'. Intenta de nuevo.</em>';
                return;
            }

            const reader=res.body.getReader(), dec=new TextDecoder();
            let buf='', out='';
            bubble.innerHTML='';

            outer:while(true){
                const{done,value}=await reader.read();
                if(done)break;
                buf+=dec.decode(value,{stream:true});
                const lines=buf.split('\n'); buf=lines.pop();
                for(const line of lines){
                    if(!line.startsWith('data:'))continue;
                    let ev;try{ev=JSON.parse(line.slice(5).trim());}catch{continue;}
                    if(ev.type==='content_block_delta'&&ev.delta?.type==='text_delta'){
                        out+=ev.delta.text;
                        bubble.innerHTML=md(out);
                        tiBody.scrollTop=tiBody.scrollHeight;
                    }
                    if(ev.type==='error'){bubble.innerHTML='<em style="color:#dc2626">'+(ev.error?.message||'Error del tutor')+'</em>';break outer;}
                    if(ev.type==='message_stop')break outer;
                }
            }

            if(!out&&!bubble.innerHTML.includes('dc2626'))
                bubble.innerHTML='<em style="color:#6b7280">Sin respuesta. Verifica que <code>GEMINI_API_KEY</code> esté configurada.</em>';
            if(out){
                history.push({role:'user',content:text},{role:'assistant',content:out});
                if(history.length>20)history=history.slice(-20);
            }
        }catch(e){
            bubble.innerHTML='<em style="color:#dc2626">Error: '+e.message+'</em>';
        }finally{
            streaming=false; sendBtn.disabled=false; input.focus();
        }
    }
})();
</script>
@endsection
