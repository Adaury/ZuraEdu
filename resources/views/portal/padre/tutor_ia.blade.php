@extends('layouts.portal')
@section('page-title', 'Tutor IA — Portal Representante')
@section('portal-name', 'Portal del Representante')

@section('sidebar')
    @include('portal.padre._sidebar', ['activeKey' => 'tutor-ia'])
@endsection

@section('bottom-nav')
    <a href="{{ route('portal.padre.dashboard') }}" class="prt-nav-item">
        <i class="bi bi-house-fill"></i>Inicio
    </a>
    <a href="{{ route('portal.padre.tutor-ia') }}" class="prt-nav-item active">
        <i class="bi bi-robot"></i>Tutor IA
    </a>
    <a href="{{ route('portal.padre.notificaciones') }}" class="prt-nav-item">
        <i class="bi bi-bell-fill"></i>Notif.
    </a>
@endsection

@section('content')
<style>
/* ── Reset layout portal ──────────────────────────────────────────── */
.portal-content-area { padding: 0 !important; overflow: hidden !important; }
.portal-content-area > footer,
.portal-content-area > .alert,
.portal-content-area > .breadcrumb-wrap { display: none !important; }

/* ── Paleta representante (violeta/púrpura) ──────────────────────── */
:root {
  --tp-1: #8b5cf6;   /* violet-500 */
  --tp-2: #ec4899;   /* pink-500   */
  --tp-3: #6366f1;   /* indigo-500 */
  --tp-grad: linear-gradient(135deg, #8b5cf6, #6366f1);
}

/* ── Contenedor ──────────────────────────────────────────────────── */
.tp-wrap {
    height: calc(100vh - 58px);
    display: flex; flex-direction: column;
    position: relative; overflow: hidden;
    background: radial-gradient(ellipse at 70% 0%, #ede9fe 0%, #fdf4ff 30%, #eef2ff 65%, #f8fafc 100%);
}
#tp-canvas { position:absolute; inset:0; pointer-events:none; z-index:0; }

/* ── Animaciones ─────────────────────────────────────────────────── */
@keyframes tpRise   { 0%{opacity:0;transform:translateY(50px) scale(.88)} 100%{opacity:1;transform:none} }
@keyframes tpFloat  { 0%,100%{transform:translateY(0)} 50%{transform:translateY(-13px)} }
@keyframes tpOrbit  { from{transform:rotate(0deg) translateX(46px)} to{transform:rotate(360deg) translateX(46px)} }
@keyframes tpGlow   { 0%,100%{box-shadow:0 0 20px rgba(139,92,246,.3)} 50%{box-shadow:0 0 52px rgba(139,92,246,.65),0 0 90px 18px rgba(99,102,241,.1)} }
@keyframes tpFadeUp { from{opacity:0;transform:translateY(22px)} to{opacity:1;transform:none} }
@keyframes tpdot    { 0%,80%,100%{transform:scale(.7);opacity:.5} 40%{transform:scale(1);opacity:1} }
@keyframes tpCardIn { 0%{opacity:0;transform:translateY(55px) scale(.92)} 100%{opacity:1;transform:none} }

/* ── Scroll ──────────────────────────────────────────────────────── */
.tp-body {
    flex:1; overflow-y:auto; position:relative; z-index:1; padding:0 24px;
    scroll-behavior:smooth;
}
.tp-body::-webkit-scrollbar { width:4px; }
.tp-body::-webkit-scrollbar-thumb { background:rgba(139,92,246,.25); border-radius:2px; }

/* ── Bienvenida ──────────────────────────────────────────────────── */
.tp-welcome {
    display:flex; flex-direction:column; align-items:center;
    justify-content:center; min-height:calc(100vh - 200px);
    gap:26px; padding:32px 0 16px; text-align:center;
    animation:tpFadeUp .6s cubic-bezier(.22,1,.36,1) both;
}
.tp-icon-wrap { position:relative; width:92px; height:92px; animation:tpFloat 4.2s ease-in-out infinite; }
.tp-icon {
    width:92px; height:92px; background:var(--tp-grad); border-radius:50%;
    display:flex; align-items:center; justify-content:center;
    font-size:40px; color:#fff; animation:tpGlow 4.2s ease-in-out infinite;
    position:relative; z-index:2;
    box-shadow:0 0 0 10px rgba(139,92,246,.08),0 0 0 22px rgba(99,102,241,.04);
}
.tp-sat {
    position:absolute; top:50%; left:50%;
    width:9px; height:9px; margin:-4.5px 0 0 -4.5px;
    border-radius:50%; transform-origin:0 0; animation:tpOrbit linear infinite;
}
.tp-sat:nth-child(1){ background:#c4b5fd; animation-duration:3s; }
.tp-sat:nth-child(2){ background:#f0abfc; animation-duration:4.8s; animation-delay:-.8s; width:6px;height:6px; }
.tp-sat:nth-child(3){ background:#818cf8; animation-duration:3.8s; animation-delay:-1.9s; width:7px;height:7px; }
.tp-ring {
    position:absolute; inset:-12px; border-radius:50%;
    border:1.5px dashed rgba(139,92,246,.22);
    animation:tpOrbit 14s linear infinite reverse; transform-origin:center;
}
.tp-headline { animation:tpRise .6s cubic-bezier(.22,1,.36,1) both .08s; }
.tp-headline h2 {
    font-size:2rem; font-weight:800; line-height:1.15; margin-bottom:8px;
    background:linear-gradient(135deg, #3b0764 0%, #8b5cf6 40%, #6366f1 70%, #a855f7 100%);
    -webkit-background-clip:text; -webkit-text-fill-color:transparent; background-clip:text;
}
.tp-headline p { color:#6b7280; font-size:.92rem; }

/* Chips temáticos para padres */
.tp-chips {
    display:flex; flex-wrap:wrap; gap:8px; justify-content:center;
    max-width:540px; animation:tpRise .6s cubic-bezier(.22,1,.36,1) both .18s;
}
.tp-chip {
    background:rgba(255,255,255,.75); backdrop-filter:blur(12px);
    border:1.5px solid rgba(139,92,246,.2); border-radius:99px;
    padding:6px 16px; font-size:.8rem; font-weight:700; color:#7c3aed;
    cursor:pointer; transition:all .25s; display:flex; align-items:center; gap:5px;
}
.tp-chip:hover { background:var(--tp-1); color:#fff; border-color:var(--tp-1); transform:translateY(-2px); box-shadow:0 4px 16px rgba(139,92,246,.3); }

/* Tarjetas sugeridas */
.tp-cards {
    display:grid; grid-template-columns:repeat(2,1fr);
    gap:12px; width:100%; max-width:600px;
    animation:tpRise .6s cubic-bezier(.22,1,.36,1) both .25s;
}
.tp-card {
    background:rgba(255,255,255,.72); backdrop-filter:blur(18px); -webkit-backdrop-filter:blur(18px);
    border:1.5px solid rgba(255,255,255,.88); border-radius:18px;
    padding:18px 16px; cursor:pointer; text-align:left;
    box-shadow:0 4px 20px rgba(139,92,246,.07),0 1px 4px rgba(0,0,0,.04);
    transition:all .28s cubic-bezier(.22,1,.36,1);
    animation:tpCardIn .55s cubic-bezier(.22,1,.36,1) both;
}
.tp-card:nth-child(1){ animation-delay:.12s; }
.tp-card:nth-child(2){ animation-delay:.22s; }
.tp-card:nth-child(3){ animation-delay:.32s; }
.tp-card:nth-child(4){ animation-delay:.42s; }
.tp-card:hover { transform:translateY(-7px) scale(1.03); border-color:rgba(139,92,246,.35); box-shadow:0 18px 48px rgba(139,92,246,.18),0 4px 12px rgba(0,0,0,.05); background:rgba(255,255,255,.92); }
.tp-card-icon { font-size:22px; margin-bottom:8px; }
.tp-card-text { font-size:.83rem; color:#1f2937; font-weight:700; line-height:1.4; }
.tp-card-hint { font-size:.73rem; color:#9ca3af; margin-top:4px; }

/* ── Mensajes ─────────────────────────────────────────────────────── */
.tp-msgs { display:flex; flex-direction:column; gap:22px; padding:24px 0 16px; max-width:780px; margin:0 auto; }
.tp { display:flex; gap:12px; animation:tpFadeUp .3s ease both; }
.tp.usr { flex-direction:row-reverse; }
.tp-av { width:34px; height:34px; border-radius:50%; flex-shrink:0; display:flex; align-items:center; justify-content:center; font-size:15px; }
.tp.bot .tp-av { background:var(--tp-grad); color:#fff; }
.tp.usr .tp-av { background:rgba(255,255,255,.85); color:#6b7280; border:1px solid #e5e7eb; }
.tp-bd { flex:1; font-size:.88rem; line-height:1.78; color:#1f2937; }
.tp.usr .tp-bd {
    background:rgba(255,255,255,.82); backdrop-filter:blur(8px);
    padding:10px 15px; border-radius:16px 16px 4px 16px;
    max-width:74%; align-self:flex-end;
    box-shadow:0 2px 10px rgba(0,0,0,.06); border:1px solid rgba(255,255,255,.9);
}
.tp-bd h1,.tp-bd h2,.tp-bd h3{font-weight:700;margin:10px 0 3px;font-size:1em;}
.tp-bd ul,.tp-bd ol{padding-left:20px;margin:5px 0;} .tp-bd li{margin:3px 0;}
.tp-bd code{background:rgba(139,92,246,.1);padding:1px 5px;border-radius:4px;font-family:monospace;font-size:.82em;color:#7c3aed;}
.tp-bd pre{background:rgba(0,0,0,.04);border-radius:10px;padding:13px;overflow-x:auto;margin:8px 0;}
.tp-bd pre code{background:none;padding:0;color:inherit;}
.tp-bd strong{font-weight:700;} .tp-bd em{font-style:italic;}
.tp-bd table{border-collapse:collapse;width:100%;margin:10px 0;font-size:.83em;}
.tp-bd th,.tp-bd td{border:1px solid #e5e7eb;padding:7px 10px;}
.tp-bd th{background:#faf5ff;font-weight:700;color:#7c3aed;}
.tptyping{display:flex;gap:5px;padding:6px 0;}
.tptyping span{width:7px;height:7px;background:#c4b5fd;border-radius:50%;animation:tpdot 1.2s infinite;}
.tptyping span:nth-child(2){animation-delay:.2s;} .tptyping span:nth-child(3){animation-delay:.4s;}

/* ── Footer ──────────────────────────────────────────────────────── */
.tp-footer { flex-shrink:0; padding:8px 24px 16px; position:relative; z-index:2; background:linear-gradient(to top, rgba(244,240,255,.98) 55%, transparent); }
.tp-footer-inner { max-width:780px; margin:0 auto; }
.tp-new-btn { display:none; align-items:center; gap:5px; background:rgba(255,255,255,.8); backdrop-filter:blur(8px); border:1px solid rgba(139,92,246,.22); border-radius:10px; padding:5px 14px; font-size:.78rem; color:var(--tp-1); font-weight:700; cursor:pointer; transition:all .2s; margin-bottom:8px; }
.tp-new-btn:hover{background:#fff;border-color:rgba(139,92,246,.45);box-shadow:0 2px 12px rgba(139,92,246,.15);}
.tp-new-btn.visible{display:inline-flex;}
.tp-input-wrap { display:flex; gap:10px; align-items:flex-end; background:rgba(255,255,255,.88); backdrop-filter:blur(18px); border:1.5px solid rgba(255,255,255,.92); border-radius:20px; padding:12px 12px 12px 20px; box-shadow:0 8px 36px rgba(139,92,246,.11),0 2px 8px rgba(0,0,0,.05); transition:all .22s; }
.tp-input-wrap:focus-within{ border-color:rgba(139,92,246,.45); box-shadow:0 8px 44px rgba(139,92,246,.22),0 2px 8px rgba(0,0,0,.05); }
#tp-input{ flex:1; border:none; outline:none; resize:none; font-size:.9rem; line-height:1.5; max-height:140px; background:transparent; font-family:inherit; color:#1f2937; }
#tp-input::placeholder{ color:#9ca3af; }
#tp-send{ background:var(--tp-grad); color:#fff; border:none; border-radius:13px; width:40px; height:40px; display:flex; align-items:center; justify-content:center; cursor:pointer; flex-shrink:0; font-size:15px; box-shadow:0 4px 16px rgba(139,92,246,.38); transition:all .2s cubic-bezier(.22,1,.36,1); }
#tp-send:hover{transform:scale(1.1) translateY(-2px);box-shadow:0 8px 28px rgba(139,92,246,.5);}
#tp-send:disabled{opacity:.4;cursor:not-allowed;transform:none;box-shadow:none;}
.tp-hint{text-align:center;color:#b0b7c3;font-size:.7rem;margin-top:6px;}

@media(max-width:560px){
    .tp-cards{grid-template-columns:1fr;}
    .tp-headline h2{font-size:1.5rem;}
    .tp-body,.tp-footer{padding-left:14px;padding-right:14px;}
}
</style>

<div class="tp-wrap">
    <canvas id="tp-canvas"></canvas>

    <div class="tp-body" id="tp-body">

        {{-- Bienvenida --}}
        <div id="tp-welcome" class="tp-welcome">

            <div class="tp-icon-wrap">
                <div class="tp-ring"></div>
                <div class="tp-sat"></div>
                <div class="tp-sat"></div>
                <div class="tp-sat"></div>
                <div class="tp-icon">🤝</div>
            </div>

            <div class="tp-headline">
                <h2>Tu asistente de apoyo escolar</h2>
                <p>Orientación académica para acompañar a tu hijo/a · ZuraAI</p>
            </div>

            {{-- Chips temáticos --}}
            <div class="tp-chips">
                @php
                $temas = [
                    ['📊','Entender calificaciones'],['📚','Apoyo en tareas'],
                    ['🗣️','Hablar con docentes'],['🧠','Técnicas de estudio'],
                    ['📅','Organización del tiempo'],['❤️','Bienestar emocional'],
                ];
                @endphp
                @foreach($temas as $t)
                <button class="tp-chip" data-subject="{{ $t[1] }}">{{ $t[0] }} {{ $t[1] }}</button>
                @endforeach
            </div>

            {{-- Tarjetas sugeridas --}}
            <div class="tp-cards">
                <button class="tp-card" data-prompt="Mi hijo/a sacó una nota baja este período. ¿Cómo puedo ayudarle a mejorar desde casa? Dame estrategias concretas.">
                    <div class="tp-card-icon">📈</div>
                    <div class="tp-card-text">Cómo ayudar a mejorar las notas</div>
                    <div class="tp-card-hint">Estrategias prácticas desde el hogar</div>
                </button>
                <button class="tp-card" data-prompt="¿Cómo creo una rutina de estudio efectiva para un estudiante de secundaria? Dame un horario semanal de ejemplo.">
                    <div class="tp-card-icon">🗓️</div>
                    <div class="tp-card-text">Crear rutina de estudio en casa</div>
                    <div class="tp-card-hint">Horario semanal adaptado</div>
                </button>
                <button class="tp-card" data-prompt="¿Qué preguntas importantes debo hacer en la reunión de padres con el docente sobre el progreso de mi hijo/a?">
                    <div class="tp-card-icon">🗣️</div>
                    <div class="tp-card-text">Preparar reunión con docentes</div>
                    <div class="tp-card-hint">Preguntas clave para la reunión</div>
                </button>
                <button class="tp-card" data-prompt="Mi hijo/a dice que no entiende Matemáticas y se frustra. ¿Cómo puedo motivarle y apoyarle sin que se sienta presionado/a?">
                    <div class="tp-card-icon">💛</div>
                    <div class="tp-card-text">Motivar sin crear presión</div>
                    <div class="tp-card-hint">Apoyo emocional y académico</div>
                </button>
            </div>
        </div>

        {{-- Mensajes --}}
        <div id="tp-msgs" class="tp-msgs" style="display:none"></div>
    </div>

    <div class="tp-footer">
        <div class="tp-footer-inner">
            <button id="tp-new" class="tp-new-btn">
                <i class="bi bi-plus-circle"></i> Nueva consulta
            </button>
            <div class="tp-input-wrap">
                <textarea id="tp-input" rows="1" placeholder="Escribe tu consulta sobre el progreso de tu hijo/a…"></textarea>
                <button id="tp-send" title="Enviar"><i class="bi bi-send-fill"></i></button>
            </div>
            <p class="tp-hint">ZuraAI puede cometer errores. Para decisiones importantes, consulta directamente con el centro educativo.</p>
        </div>
    </div>
</div>

<script>
(function(){
    const canvas=document.getElementById('tp-canvas'), ctx=canvas.getContext('2d');
    const colors=['rgba(139,92,246,','rgba(99,102,241,','rgba(196,181,253,','rgba(232,121,249,','rgba(167,139,250,'];
    let W,H,pts=[];
    function resize(){ W=canvas.width=canvas.offsetWidth; H=canvas.height=canvas.offsetHeight; }
    function mk(){ return{x:Math.random()*W,y:H+Math.random()*50,r:Math.random()*2.5+.8,vx:(Math.random()-.5)*.35,vy:-(Math.random()*.65+.2),alpha:Math.random()*.4+.08,color:colors[Math.floor(Math.random()*colors.length)],w:Math.random()*Math.PI*2,ws:Math.random()*.018+.004}; }
    function init(){ resize(); pts=Array.from({length:60},()=>({...mk(),y:Math.random()*H})); }
    function draw(){
        ctx.clearRect(0,0,W,H);
        pts.forEach(p=>{ p.w+=p.ws; p.x+=p.vx+Math.sin(p.w)*.28; p.y+=p.vy; if(p.y<-8)Object.assign(p,mk()); ctx.beginPath();ctx.arc(p.x,p.y,p.r,0,Math.PI*2);ctx.fillStyle=p.color+p.alpha+')';ctx.fill(); });
        requestAnimationFrame(draw);
    }
    init(); draw(); window.addEventListener('resize',resize);
})();

(function(){
    const tpBody =document.getElementById('tp-body');
    const welcome=document.getElementById('tp-welcome');
    const msgs   =document.getElementById('tp-msgs');
    const input  =document.getElementById('tp-input');
    const sendBtn=document.getElementById('tp-send');
    const newBtn =document.getElementById('tp-new');
    const URL    ='{{ route("portal.padre.asistente.chat") }}';
    const CSRF   =document.querySelector('meta[name="csrf-token"]')?.content||'{{ csrf_token() }}';
    let history=[],streaming=false;

    document.querySelectorAll('.tp-card').forEach(c=>c.addEventListener('click',()=>{ if(streaming)return; input.value=c.dataset.prompt; autoResize(); send(); }));
    document.querySelectorAll('.tp-chip').forEach(s=>s.addEventListener('click',()=>{
        if(streaming)return;
        input.value=`Necesito orientación sobre: ${s.dataset.subject}. `;
        input.focus(); autoResize();
    }));
    input.addEventListener('keydown',e=>{ if(e.key==='Enter'&&!e.shiftKey){e.preventDefault();send();} });
    input.addEventListener('input',autoResize);
    sendBtn.addEventListener('click',send);
    newBtn.addEventListener('click',reset);

    function autoResize(){ input.style.height='auto'; input.style.height=Math.min(input.scrollHeight,140)+'px'; }

    function reset(){
        history=[]; msgs.innerHTML=''; msgs.style.display='none';
        welcome.style.display='flex'; newBtn.classList.remove('visible');
        input.value=''; input.style.height='auto'; input.focus();
    }

    function addMsg(role,html){
        const row=document.createElement('div'); row.className='tp '+role;
        const av =document.createElement('div'); av.className='tp-av';
        av.innerHTML=role==='bot'?'🤝':'<i class="bi bi-person-fill"></i>';
        const body=document.createElement('div'); body.className='tp-bd';
        body.innerHTML=html;
        row.appendChild(av); row.appendChild(body);
        msgs.appendChild(row); tpBody.scrollTop=tpBody.scrollHeight;
        return body;
    }

    function showChat(){
        welcome.style.display='none'; msgs.style.display='flex'; newBtn.classList.add('visible');
    }

    function md(t){
        let s=t.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
        s=s.replace(/```[\w]*\n?([\s\S]*?)```/g,'<pre><code>$1</code></pre>');
        s=s.replace(/`([^`\n]+)`/g,'<code>$1</code>');
        s=s.replace(/^### (.+)$/gm,'<h3>$1</h3>').replace(/^## (.+)$/gm,'<h2>$1</h2>').replace(/^# (.+)$/gm,'<h1>$1</h1>');
        s=s.replace(/\*\*(.+?)\*\*/g,'<strong>$1</strong>').replace(/\*(.+?)\*/g,'<em>$1</em>');
        s=s.replace(/((\|[^\n]+\|\n?)+)/g,m=>{
            const rows=m.trim().split('\n').filter(r=>!/^[\|\s\-:]+$/.test(r)); if(!rows.length)return m;
            const[h,...b]=rows;
            const th=h.split('|').filter(c=>c.trim()).map(c=>`<th>${c.trim()}</th>`).join('');
            const trs=b.map(r=>'<tr>'+r.split('|').filter(c=>c.trim()).map(c=>`<td>${c.trim()}</td>`).join('')+'</tr>').join('');
            return `<table><thead><tr>${th}</tr></thead><tbody>${trs}</tbody></table>`;
        });
        s=s.replace(/^[*-] (.+)$/gm,'<li>$1</li>').replace(/^\d+\. (.+)$/gm,'<li>$1</li>');
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
        addMsg('usr',text.replace(/&/g,'&amp;').replace(/</g,'&lt;'));
        const bubble=addMsg('bot','<div class="tptyping"><span></span><span></span><span></span></div>');
        try{
            const res=await fetch(URL,{
                method:'POST',
                headers:{'Content-Type':'application/json','X-CSRF-TOKEN':CSRF,'Accept':'text/event-stream','X-Requested-With':'XMLHttpRequest'},
                body:JSON.stringify({message:text,history})
            });
            if(!res.ok){ bubble.innerHTML='<em style="color:#dc2626">Error '+res.status+'. Intenta de nuevo.</em>'; return; }
            const reader=res.body.getReader(), dec=new TextDecoder();
            let buf='',out=''; bubble.innerHTML='';
            outer:while(true){
                const{done,value}=await reader.read(); if(done)break;
                buf+=dec.decode(value,{stream:true});
                const lines=buf.split('\n'); buf=lines.pop();
                for(const line of lines){
                    if(!line.startsWith('data:'))continue;
                    let ev; try{ev=JSON.parse(line.slice(5).trim());}catch{continue;}
                    if(ev.type==='content_block_delta'&&ev.delta?.type==='text_delta'){ out+=ev.delta.text; bubble.innerHTML=md(out); tpBody.scrollTop=tpBody.scrollHeight; }
                    if(ev.type==='error'){bubble.innerHTML='<em style="color:#dc2626">'+(ev.error?.message||'Error')+'</em>';break outer;}
                    if(ev.type==='message_stop')break outer;
                }
            }
            if(!out&&!bubble.innerHTML.includes('dc2626'))
                bubble.innerHTML='<em style="color:#6b7280">Sin respuesta. Verifica que <code>GEMINI_API_KEY</code> esté configurada.</em>';
            if(out){ history.push({role:'user',content:text},{role:'assistant',content:out}); if(history.length>20)history=history.slice(-20); }
        }catch(e){ bubble.innerHTML='<em style="color:#dc2626">Error: '+e.message+'</em>'; }
        finally{ streaming=false; sendBtn.disabled=false; input.focus(); }
    }
})();
</script>
@endsection
