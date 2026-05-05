<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Sistema de gestión educativa completo para centros escolares modernos. Notas, asistencia, horarios y comunicación.">
    <title>{{ env('APP_PRODUCT_NAME', config('app.name')) }} — Gestión Educativa Inteligente</title>
    <link href="/vendor/bootstrap-icons/bootstrap-icons.min.css" rel="stylesheet">
    <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    :root {
        --navy:   #0a0e27;
        --blue:   #1d4ed8;
        --blue-l: #3b82f6;
        --indigo: #4f46e5;
        --green:  #10b981;
        --green-d:#059669;
        --amber:  #f59e0b;
        --rose:   #f43f5e;
        --violet: #7c3aed;
        --white:  #ffffff;
        --g50:    #f8fafc;
        --g100:   #f1f5f9;
        --g200:   #e2e8f0;
        --g400:   #94a3b8;
        --g500:   #64748b;
        --g700:   #374151;
        --g900:   #0f172a;
    }
    html { scroll-behavior: smooth; }
    body { font-family: 'Inter', -apple-system, 'Segoe UI', sans-serif; background: #fff; color: var(--g900); line-height: 1.6; }

    /* ── NAVBAR ── */
    .nav {
        position: sticky; top: 0; z-index: 300;
        background: rgba(255,255,255,.93);
        backdrop-filter: blur(18px);
        border-bottom: 1px solid rgba(226,232,240,.8);
        transition: box-shadow .25s;
    }
    .nav.scrolled { box-shadow: 0 1px 28px rgba(15,23,42,.1); }
    .nav-inner { max-width: 1180px; margin: 0 auto; padding: 0 1.5rem; display: flex; align-items: center; height: 64px; gap: 1.75rem; }
    .nav-logo { display: flex; align-items: center; gap: .6rem; text-decoration: none; flex-shrink: 0; }
    .nav-logo-icon {
        width: 36px; height: 36px; border-radius: 10px;
        background: linear-gradient(135deg, #1e3a8a, #3b82f6);
        display: flex; align-items: center; justify-content: center;
        color: #fff; font-size: .95rem;
        box-shadow: 0 4px 12px rgba(30,64,175,.35);
    }
    .nav-logo-name { font-size: 1.1rem; font-weight: 900; color: #0f172a; letter-spacing: -.02em; }
    .nav-links { display: flex; gap: .25rem; margin-left: auto; list-style: none; }
    .nav-links a {
        display: flex; align-items: center; gap: .35rem;
        padding: .42rem .85rem; border-radius: 8px;
        text-decoration: none; color: var(--g500); font-size: .84rem; font-weight: 500;
        transition: background .15s, color .15s;
    }
    .nav-links a:hover { background: var(--g100); color: var(--g900); }
    .nav-btns { display: flex; gap: .55rem; align-items: center; }
    .btn {
        display: inline-flex; align-items: center; gap: .4rem;
        padding: .48rem 1.1rem; border-radius: 9px;
        font-size: .84rem; font-weight: 600;
        text-decoration: none; border: none; cursor: pointer;
        transition: all .18s; white-space: nowrap;
    }
    .btn-ghost { background: transparent; color: var(--g700); border: 1.5px solid var(--g200); }
    .btn-ghost:hover { border-color: var(--blue-l); color: var(--blue); background: #eff6ff; }
    .btn-primary {
        background: linear-gradient(135deg, #1e3a8a, #2563eb);
        color: #fff; box-shadow: 0 4px 14px rgba(30,64,175,.32);
    }
    .btn-primary:hover { transform: translateY(-1px); box-shadow: 0 7px 20px rgba(30,64,175,.42); }
    .btn-lg { padding: .68rem 1.65rem; font-size: .93rem; border-radius: 11px; }
    .btn-green { background: linear-gradient(135deg, #059669, #10b981); color: #fff; box-shadow: 0 4px 14px rgba(16,185,129,.28); }
    .btn-green:hover { transform: translateY(-1px); box-shadow: 0 7px 20px rgba(16,185,129,.38); }
    .btn-white { background: #fff; color: #1e3a8a; font-weight: 700; }
    .btn-white:hover { background: #f0f7ff; transform: translateY(-1px); }
    .btn-outline-w { background: transparent; color: #fff; border: 2px solid rgba(255,255,255,.3); }
    .btn-outline-w:hover { border-color: #fff; background: rgba(255,255,255,.1); }
    .nav-ham { display: none; background: none; border: none; cursor: pointer; font-size: 1.4rem; color: var(--g700); margin-left: auto; }

    /* ── HERO ── */
    .hero {
        background: linear-gradient(145deg, #060b20 0%, #0e1f5e 45%, #1d4ed8 100%);
        padding: 6rem 1.5rem 0;
        text-align: center; position: relative; overflow: hidden; min-height: 620px;
    }
    .hero-glow-1 {
        position: absolute; top: -120px; right: -100px;
        width: 600px; height: 600px; border-radius: 50%;
        background: radial-gradient(circle, rgba(99,102,241,.22) 0%, transparent 65%);
        pointer-events: none;
    }
    .hero-glow-2 {
        position: absolute; bottom: 40px; left: -80px;
        width: 400px; height: 400px; border-radius: 50%;
        background: radial-gradient(circle, rgba(16,185,129,.15) 0%, transparent 65%);
        pointer-events: none;
    }
    .hero-inner { max-width: 800px; margin: 0 auto; position: relative; z-index: 2; }
    .hero-eyebrow {
        display: inline-flex; align-items: center; gap: .5rem;
        background: rgba(255,255,255,.09); border: 1px solid rgba(255,255,255,.16);
        color: rgba(255,255,255,.85); border-radius: 99px;
        padding: .32rem 1rem; font-size: .72rem; font-weight: 600;
        margin-bottom: 1.65rem; backdrop-filter: blur(8px);
    }
    .pulse-dot { width: 7px; height: 7px; background: #34d399; border-radius: 50%; animation: pdot 2s infinite; }
    @keyframes pdot { 0%,100%{opacity:1;transform:scale(1)} 50%{opacity:.55;transform:scale(1.5)} }
    .hero h1 {
        font-size: clamp(2.2rem, 6vw, 3.7rem); font-weight: 900; color: #fff;
        line-height: 1.1; letter-spacing: -.03em; margin-bottom: 1.25rem;
    }
    .hero h1 .accent {
        background: linear-gradient(135deg, #6ee7b7 0%, #34d399 50%, #10b981 100%);
        -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
    }
    .hero-desc {
        font-size: clamp(.9rem, 2.2vw, 1.1rem); color: rgba(255,255,255,.65);
        margin-bottom: 2.75rem; max-width: 560px; margin-left: auto; margin-right: auto; line-height: 1.75;
    }
    .hero-actions { display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap; margin-bottom: 4rem; }
    .hero-trust { display: flex; align-items: center; justify-content: center; gap: 1.75rem; flex-wrap: wrap; margin-bottom: 3.5rem; }
    .hero-trust-item { display: flex; align-items: center; gap: .45rem; color: rgba(255,255,255,.42); font-size: .74rem; }
    .hero-trust-item i { font-size: .85rem; color: rgba(255,255,255,.5); }

    /* Mockup flotante */
    .hero-screen {
        position: relative; max-width: 860px; margin: 0 auto;
        animation: flt 5s ease-in-out infinite alternate;
    }
    @keyframes flt { from{transform:translateY(0)} to{transform:translateY(-12px)} }
    .screen-wrap {
        background: #0f172a; border-radius: 16px 16px 0 0;
        box-shadow: 0 -8px 60px rgba(0,0,0,.5), 0 0 0 1px rgba(255,255,255,.06),
                    0 60px 120px rgba(0,0,0,.6);
        overflow: hidden;
    }
    .screen-bar {
        background: #1e293b; padding: .7rem 1.1rem;
        display: flex; align-items: center; gap: .8rem;
        border-bottom: 1px solid rgba(255,255,255,.05);
    }
    .screen-dots { display: flex; gap: .32rem; }
    .screen-dot { width: 11px; height: 11px; border-radius: 50%; }
    .screen-url { flex: 1; background: rgba(255,255,255,.05); border-radius: 6px; padding: .24rem .75rem; max-width: 300px; margin: 0 auto; font-size: .62rem; color: rgba(255,255,255,.28); }
    .screen-body { display: flex; height: 240px; }
    .screen-sb { width: 150px; background: #0f172a; padding: .9rem .65rem; border-right: 1px solid rgba(255,255,255,.04); flex-shrink: 0; }
    .screen-sb-sect { font-size: .5rem; color: rgba(255,255,255,.2); font-weight: 700; text-transform: uppercase; letter-spacing: .06em; padding: .15rem .35rem; margin-bottom: .3rem; margin-top: .5rem; }
    .screen-nav { display: flex; align-items: center; gap: .38rem; padding: .33rem .5rem; border-radius: 6px; font-size: .6rem; color: rgba(255,255,255,.38); }
    .screen-nav.on { background: rgba(37,99,235,.25); color: #93c5fd; }
    .screen-nav i { font-size: .68rem; }
    .screen-main { flex: 1; padding: 1rem; display: flex; flex-direction: column; gap: .6rem; overflow: hidden; }
    .screen-grid { display: grid; grid-template-columns: repeat(4,1fr); gap: .45rem; }
    .screen-stat { background: rgba(255,255,255,.04); border-radius: 8px; padding: .5rem .45rem; border: 1px solid rgba(255,255,255,.05); }
    .screen-stat-n { font-size: .82rem; font-weight: 900; }
    .screen-stat-l { font-size: .49rem; color: rgba(255,255,255,.35); margin-top: .08rem; }
    .screen-tbl { background: rgba(255,255,255,.03); border-radius: 8px; overflow: hidden; flex: 1; }
    .screen-row { display: grid; grid-template-columns: 2.2fr 1fr 1fr 1fr; padding: .3rem .5rem; border-bottom: 1px solid rgba(255,255,255,.04); font-size: .58rem; }
    .screen-row.hd { background: rgba(255,255,255,.06); font-size: .55rem; color: rgba(255,255,255,.35); font-weight: 700; }
    .sc { color: rgba(255,255,255,.6); }
    .sbadge { display:inline-block; padding:.03rem .28rem; border-radius:4px; font-size:.52rem; font-weight:800; }

    /* ── STATS BAR ── */
    .stats-bar {
        background: linear-gradient(90deg, #0a0e27 0%, #0e1f5e 100%);
        padding: 2.75rem 1.5rem;
    }
    .stats-inner { max-width: 900px; margin: 0 auto; display: grid; grid-template-columns: repeat(4,1fr); gap: 2rem; }
    .stat-it { text-align: center; }
    .stat-n { font-size: 2.35rem; font-weight: 900; color: #fff; line-height: 1; margin-bottom: .3rem; }
    .stat-n em { font-style: normal; color: #34d399; }
    .stat-d { font-size: .77rem; color: rgba(255,255,255,.4); }

    /* ── SECCIÓN GENÉRICA ── */
    .sec { padding: 6rem 1.5rem; }
    .sec-alt { background: var(--g50); }
    .sec-in { max-width: 1180px; margin: 0 auto; }
    .sec-hdr { text-align: center; margin-bottom: 4rem; }
    .pill {
        display: inline-flex; align-items: center; gap: .4rem;
        background: #eff6ff; color: #1d4ed8;
        border: 1px solid #bfdbfe;
        border-radius: 99px; padding: .3rem 1rem;
        font-size: .72rem; font-weight: 700; text-transform: uppercase;
        letter-spacing: .07em; margin-bottom: .9rem;
    }
    .sec-title { font-size: clamp(1.65rem, 3.5vw, 2.4rem); font-weight: 900; color: var(--g900); letter-spacing: -.025em; margin-bottom: .75rem; line-height: 1.2; }
    .sec-sub { font-size: .94rem; color: var(--g500); max-width: 530px; margin: 0 auto; line-height: 1.7; }

    /* ── BENEFICIOS ── */
    .bens { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px,1fr)); gap: 1.5rem; }
    .ben {
        background: #fff; border: 1.5px solid var(--g200); border-radius: 18px;
        padding: 2rem; transition: all .22s; position: relative; overflow: hidden;
    }
    .ben::after { content:''; position:absolute; inset:0; border-radius:18px; opacity:0; transition:opacity .22s; background: linear-gradient(135deg, var(--bc1,#3b82f6), var(--bc2,#1d4ed8)); z-index:0; }
    .ben:hover { transform: translateY(-5px); box-shadow: 0 20px 50px rgba(0,0,0,.1); border-color: transparent; }
    .ben:hover::after { opacity:.04; }
    .ben > * { position: relative; z-index: 1; }
    .ben-bar { position: absolute; top:0;left:0;right:0;height:3px; background: linear-gradient(90deg,var(--bc1,#3b82f6),var(--bc2,#1d4ed8)); border-radius:18px 18px 0 0; }
    .ben-icon { width: 52px; height: 52px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; margin-bottom: 1.2rem; }
    .ben-title { font-size: 1.02rem; font-weight: 800; color: var(--g900); margin-bottom: .5rem; }
    .ben-desc { font-size: .83rem; color: var(--g500); line-height: 1.7; }

    /* ── MÓDULOS ── */
    .mods { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px,1fr)); gap: 1.25rem; }
    .mod { background: #fff; border-radius: 14px; border: 1.5px solid var(--g200); padding: 1.75rem; transition: all .2s; }
    .mod:hover { border-color: var(--mc,#3b82f6); box-shadow: 0 8px 30px rgba(0,0,0,.08); transform: translateY(-3px); }
    .mod-icon { width: 46px; height: 46px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.15rem; margin-bottom: .95rem; }
    .mod-title { font-size: .96rem; font-weight: 800; color: var(--g900); margin-bottom: .7rem; }
    .mod-list { list-style: none; display: flex; flex-direction: column; gap: .3rem; }
    .mod-list li { font-size: .79rem; color: var(--g500); display: flex; align-items: flex-start; gap: .45rem; line-height: 1.45; }
    .mod-list li::before { content: '✓'; color: var(--mc,#10b981); font-weight: 800; font-size: .75rem; flex-shrink:0; margin-top:.08rem; }

    /* ── PREVIEW (TABS) ── */
    .tabs { display: flex; gap: .5rem; justify-content: center; margin-bottom: 2.5rem; flex-wrap: wrap; }
    .tab-btn {
        display: flex; align-items: center; gap: .45rem;
        padding: .5rem 1.25rem; border-radius: 99px;
        border: 1.5px solid var(--g200); background: #fff;
        font-size: .8rem; font-weight: 600; color: var(--g500);
        cursor: pointer; transition: all .16s;
    }
    .tab-btn:hover { border-color: #bfdbfe; color: #1d4ed8; background: #f0f7ff; }
    .tab-btn.on { border-color: #1d4ed8; color: #1d4ed8; background: #eff6ff; box-shadow: 0 2px 12px rgba(30,64,175,.12); }
    .bigmock { background: #0f172a; border-radius: 16px; overflow: hidden; box-shadow: 0 28px 70px rgba(0,0,0,.28); }
    .bigmock-bar { background: #1e293b; padding: .9rem 1.25rem; display: flex; align-items: center; gap: .75rem; border-bottom: 1px solid rgba(255,255,255,.05); }
    .bigmock-body { display: flex; min-height: 330px; }
    .bigmock-sb { width: 195px; background: #1e293b; padding: 1rem .8rem; border-right: 1px solid rgba(255,255,255,.05); }
    .bigmock-sb-sect { font-size: .58rem; color: rgba(255,255,255,.22); font-weight: 700; text-transform: uppercase; letter-spacing: .07em; padding: 0 .3rem; margin-bottom: .6rem; margin-top: .5rem; }
    .bigmock-nav { display: flex; align-items: center; gap: .5rem; padding: .42rem .65rem; border-radius: 7px; margin-bottom: .15rem; font-size: .68rem; color: rgba(255,255,255,.4); cursor: pointer; }
    .bigmock-nav.on { background: rgba(37,99,235,.25); color: #93c5fd; }
    .bigmock-nav i { font-size: .75rem; }
    .bigmock-main { flex: 1; padding: 1.35rem; }

    /* ── PORTALES ── */
    .portals { display: grid; grid-template-columns: repeat(auto-fit, minmax(210px,1fr)); gap: 1rem; margin-top: 2.5rem; }
    .portal-card {
        padding: 1.75rem 1.5rem; border-radius: 16px;
        text-align: center; text-decoration: none; transition: all .22s;
        border: 2px solid transparent; position: relative; overflow: hidden;
    }
    .portal-card::before { content:''; position:absolute; inset:0; background:inherit; opacity:.07; border-radius:inherit; }
    .portal-card:hover { transform: translateY(-4px); box-shadow: 0 16px 40px rgba(0,0,0,.12); }
    .portal-card i { font-size: 2.15rem; display: block; margin-bottom: .9rem; }
    .portal-card-t { font-size: .92rem; font-weight: 800; margin-bottom: .3rem; }
    .portal-card-s { font-size: .74rem; opacity: .7; }

    .demo-box {
        margin-top: 2rem; padding: 1.75rem;
        background: #fff; border-radius: 16px;
        border: 2px dashed var(--g200);
    }
    .demo-title { text-align: center; font-size: .82rem; font-weight: 700; color: var(--g500); margin-bottom: .35rem; }
    .demo-sub { text-align: center; font-size: .82rem; color: var(--g400); margin-bottom: 1.25rem; }
    .demo-btns { display: flex; gap: .75rem; justify-content: center; flex-wrap: wrap; }

    /* ── TESTIMONIAL ── */
    .testi {
        background: linear-gradient(145deg, #0a0e27, #0e1f5e 60%, #1d4ed8);
        padding: 6rem 1.5rem; text-align: center;
    }
    .testi-quote { font-size: 4rem; color: rgba(255,255,255,.12); line-height: .8; font-family: Georgia, serif; margin-bottom: 1rem; }
    .testi-text { font-size: clamp(.9rem, 2vw, 1.12rem); color: rgba(255,255,255,.75); line-height: 1.85; max-width: 680px; margin: 0 auto 2rem; font-style: italic; }
    .testi-person { display: flex; align-items: center; justify-content: center; gap: .9rem; }
    .testi-avatar { width: 48px; height: 48px; border-radius: 50%; background: linear-gradient(135deg,#4f46e5,#818cf8); display: flex; align-items: center; justify-content: center; color: #fff; font-weight: 900; font-size: 1.1rem; flex-shrink: 0; }
    .testi-name { font-size: .88rem; font-weight: 700; color: #fff; }
    .testi-role { font-size: .73rem; color: rgba(255,255,255,.45); }

    /* ── CTA ── */
    .cta-sec {
        background: #fff; padding: 6.5rem 1.5rem;
        text-align: center; position: relative; overflow: hidden;
    }
    .cta-sec::before { content:''; position:absolute; top:-80px;left:50%;transform:translateX(-50%); width:700px;height:700px;border-radius:50%; background:radial-gradient(circle,rgba(99,102,241,.07) 0%,transparent 65%); pointer-events:none; }
    .cta-sec h2 { font-size: clamp(1.75rem, 4.5vw, 2.8rem); font-weight: 900; color: var(--g900); letter-spacing: -.03em; margin-bottom: .8rem; }
    .cta-sec p { font-size: .98rem; color: var(--g500); margin-bottom: 2.5rem; max-width: 480px; margin-left: auto; margin-right: auto; }
    .cta-btns { display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap; }

    /* ── FOOTER ── */
    .footer { background: var(--g900); padding: 4rem 1.5rem 2rem; }
    .footer-in { max-width: 1180px; margin: 0 auto; }
    .footer-top { display: flex; gap: 3.5rem; flex-wrap: wrap; margin-bottom: 3rem; }
    .footer-brand { flex: 1.4; min-width: 220px; }
    .footer-logo { display: flex; align-items: center; gap: .55rem; text-decoration: none; margin-bottom: .85rem; }
    .footer-logo-icon { width: 32px; height: 32px; border-radius: 8px; background: linear-gradient(135deg,#1e3a8a,#3b82f6); display: flex; align-items: center; justify-content: center; color: #fff; font-size: .85rem; }
    .footer-logo-name { color: #fff; font-weight: 800; font-size: .95rem; }
    .footer-desc { font-size: .78rem; color: rgba(255,255,255,.3); line-height: 1.7; }
    .footer-col h4 { color: rgba(255,255,255,.5); font-size: .7rem; font-weight: 700; text-transform: uppercase; letter-spacing: .08em; margin-bottom: 1rem; }
    .footer-col a { display: flex; align-items: center; gap: .38rem; color: rgba(255,255,255,.38); font-size: .8rem; text-decoration: none; margin-bottom: .5rem; transition: color .15s; }
    .footer-col a:hover { color: rgba(255,255,255,.7); }
    .footer-bottom { border-top: 1px solid rgba(255,255,255,.06); padding-top: 1.75rem; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: .75rem; }
    .footer-copy { font-size: .73rem; color: rgba(255,255,255,.25); }
    .footer-links { display: flex; gap: 1.5rem; }
    .footer-links a { font-size: .73rem; color: rgba(255,255,255,.25); text-decoration: none; transition: color .15s; }
    .footer-links a:hover { color: rgba(255,255,255,.55); }

    /* ── FADE IN ── */
    .fade { opacity: 0; transform: translateY(20px); transition: opacity .6s ease, transform .6s ease; }
    .fade.in { opacity: 1; transform: none; }

    /* ── RESPONSIVE ── */
    @media (max-width: 768px) {
        .nav-links { display: none; }
        .nav-ham { display: block; }
        .nav-btns .btn-ghost { display: none; }
        .stats-inner { grid-template-columns: repeat(2,1fr); }
        .bigmock-sb { display: none; }
        .bigmock-body { min-height: 270px; }
        .footer-top { gap: 2rem; }
    }
    @media (max-width: 480px) {
        .hero { padding: 4rem 1rem 0; }
        .hero-actions { flex-direction: column; align-items: center; }
        .hero-actions .btn { width: 100%; max-width: 300px; justify-content: center; }
        .screen-sb { display: none; }
        .screen-body { height: 170px; }
        .screen-grid { grid-template-columns: repeat(2,1fr); }
        .cta-btns { flex-direction: column; align-items: center; }
        .cta-btns .btn { width: 100%; max-width: 300px; justify-content: center; }
        .footer-top { flex-direction: column; }
        .footer-bottom { flex-direction: column; text-align: center; }
        .hero-trust { gap: 1rem; }
    }
    </style>
</head>
<body>

{{-- ── NAVBAR ─────────────────────────────────────────────────────────── --}}
<nav class="nav" id="navbar">
    <div class="nav-inner">
        <a href="/" class="nav-logo">
            <div class="nav-logo-icon"><i class="bi bi-mortarboard-fill"></i></div>
            <span class="nav-logo-name">{{ env('APP_PRODUCT_NAME', 'SGE') }}</span>
        </a>
        <ul class="nav-links">
            <li><a href="#beneficios"><i class="bi bi-stars"></i>Beneficios</a></li>
            <li><a href="#modulos"><i class="bi bi-grid-3x3-gap"></i>Módulos</a></li>
            <li><a href="#preview"><i class="bi bi-display"></i>Vista previa</a></li>
            <li><a href="#portales"><i class="bi bi-key-fill"></i>Acceso</a></li>
        </ul>
        <div class="nav-btns">
            <a href="{{ route('verificar-matricula') }}" class="btn btn-ghost" style="font-size:.82rem;">
                <i class="bi bi-patch-check"></i>Verificar matrícula
            </a>
            <a href="{{ route('login') }}" class="btn btn-ghost">
                <i class="bi bi-box-arrow-in-right"></i>Iniciar sesión
            </a>
        </div>
        <button class="nav-ham" id="ham-btn" onclick="toggleNav()" aria-label="Menú">
            <i class="bi bi-list" id="ham-ico"></i>
        </button>
    </div>
    <div id="mobile-nav" style="display:none;padding:.85rem 1.5rem 1.25rem;border-top:1px solid var(--g200);">
        <a href="#beneficios" style="display:block;padding:.5rem 0;font-size:.88rem;color:var(--g700);text-decoration:none;font-weight:600;" onclick="toggleNav()">Beneficios</a>
        <a href="#modulos" style="display:block;padding:.5rem 0;font-size:.88rem;color:var(--g700);text-decoration:none;font-weight:600;" onclick="toggleNav()">Módulos</a>
        <a href="#preview" style="display:block;padding:.5rem 0;font-size:.88rem;color:var(--g700);text-decoration:none;font-weight:600;" onclick="toggleNav()">Vista previa</a>
        <a href="#portales" style="display:block;padding:.5rem 0;font-size:.88rem;color:var(--g700);text-decoration:none;font-weight:600;" onclick="toggleNav()">Acceso</a>
        <div style="display:flex;gap:.55rem;margin-top:1rem;">
            <a href="{{ route('login') }}" class="btn btn-primary" style="flex:1;justify-content:center;">Iniciar sesión</a>
        </div>
    </div>
</nav>

{{-- ── HERO ─────────────────────────────────────────────────────────── --}}
<section class="hero" id="inicio">
    <div class="hero-glow-1"></div>
    <div class="hero-glow-2"></div>
    <div class="hero-inner">
        @php $ls = \Illuminate\Support\Facades\DB::table('system_settings')->pluck('value','key'); @endphp
        <div class="hero-eyebrow">
            <span class="pulse-dot"></span>
            {{ $ls['landing_hero_badge'] ?? 'Sistema educativo completo · Listo para usar' }}
        </div>
        <h1>
            {{ $ls['landing_hero_title'] ?? 'Gestión educativa' }}<br>
            <span class="accent">{{ $ls['landing_hero_title_em'] ?? 'inteligente' }}</span>
        </h1>
        <p class="hero-desc">{{ $ls['landing_hero_sub'] ?? 'La plataforma todo-en-uno para centros educativos modernos. Notas, asistencia, horarios y comunicación con padres desde un solo lugar.' }}</p>
        <div class="hero-actions">
            <a href="{{ route('login') }}" class="btn btn-primary btn-lg">
                <i class="bi bi-box-arrow-in-right"></i>{{ $ls['landing_cta_primary'] ?? 'Acceder al sistema' }}
            </a>
            <a href="{{ route('demo.login', 'docente') }}" class="btn btn-outline-w btn-lg">
                <i class="bi bi-play-circle"></i>Ver demo
            </a>
        </div>
        <div class="hero-trust">
            <div class="hero-trust-item"><i class="bi bi-shield-check"></i>Datos seguros</div>
            <div class="hero-trust-item"><i class="bi bi-phone"></i>Acceso móvil</div>
            <div class="hero-trust-item"><i class="bi bi-clock"></i>Tiempo real</div>
            <div class="hero-trust-item"><i class="bi bi-people"></i>Multi-rol</div>
        </div>

        {{-- Mockup del sistema --}}
        <div class="hero-screen">
            <div class="screen-wrap">
                <div class="screen-bar">
                    <div class="screen-dots">
                        <div class="screen-dot" style="background:#ef4444;"></div>
                        <div class="screen-dot" style="background:#f59e0b;"></div>
                        <div class="screen-dot" style="background:#10b981;"></div>
                    </div>
                    <div class="screen-url">🔒 sistema.edu/admin/dashboard</div>
                </div>
                <div class="screen-body">
                    <div class="screen-sb">
                        <div class="screen-sb-sect">Panel</div>
                        <div class="screen-nav on"><i class="bi bi-grid-3x3-gap"></i>Dashboard</div>
                        <div class="screen-nav"><i class="bi bi-people-fill"></i>Estudiantes</div>
                        <div class="screen-nav"><i class="bi bi-person-badge"></i>Docentes</div>
                        <div class="screen-nav"><i class="bi bi-journal-text"></i>Calificaciones</div>
                        <div class="screen-nav"><i class="bi bi-calendar-check"></i>Asistencia</div>
                        <div class="screen-nav"><i class="bi bi-calendar-week"></i>Horarios</div>
                    </div>
                    <div class="screen-main">
                        <div class="screen-grid">
                            <div class="screen-stat"><div class="screen-stat-n" style="color:#93c5fd;">248</div><div class="screen-stat-l">Estudiantes</div></div>
                            <div class="screen-stat"><div class="screen-stat-n" style="color:#6ee7b7;">18</div><div class="screen-stat-l">Docentes</div></div>
                            <div class="screen-stat"><div class="screen-stat-n" style="color:#fbbf24;">12</div><div class="screen-stat-l">Grupos</div></div>
                            <div class="screen-stat"><div class="screen-stat-n" style="color:#a78bfa;">94%</div><div class="screen-stat-l">Asistencia</div></div>
                        </div>
                        <div class="screen-tbl">
                            <div class="screen-row hd"><div>Estudiante</div><div>Grupo</div><div>Nota</div><div>Estado</div></div>
                            <div class="screen-row"><div class="sc">M. García</div><div class="sc">1-A</div><div class="sc">92</div><div><span class="sbadge" style="background:rgba(52,211,153,.2);color:#34d399;">A</span></div></div>
                            <div class="screen-row"><div class="sc">J. Pérez</div><div class="sc">2-B</div><div class="sc">78</div><div><span class="sbadge" style="background:rgba(147,197,253,.2);color:#93c5fd;">B</span></div></div>
                            <div class="screen-row"><div class="sc">L. Soto</div><div class="sc">1-A</div><div class="sc">55</div><div><span class="sbadge" style="background:rgba(248,113,113,.2);color:#f87171;">F</span></div></div>
                            <div class="screen-row"><div class="sc">A. Torres</div><div class="sc">3-C</div><div class="sc">85</div><div><span class="sbadge" style="background:rgba(147,197,253,.2);color:#93c5fd;">B</span></div></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ── STATS BAR ────────────────────────────────────────────────────── --}}
<div class="stats-bar">
    <div class="stats-inner fade">
        @php
        $statDefs = [
            1=>['n'=>'500','s'=>'+','d'=>'Estudiantes gestionados'],
            2=>['n'=>'30','s'=>'+','d'=>'Docentes activos'],
            3=>['n'=>'99','s'=>'%','d'=>'Tiempo de actividad'],
            4=>['n'=>'24','s'=>'/7','d'=>'Disponibilidad'],
        ];
        @endphp
        @foreach([1,2,3,4] as $si)
        <div class="stat-it">
            <div class="stat-n">{{ $ls['landing_stat'.$si.'_n'] ?? $statDefs[$si]['n'] }}<em>{{ $ls['landing_stat'.$si.'_s'] ?? $statDefs[$si]['s'] }}</em></div>
            <div class="stat-d">{{ $ls['landing_stat'.$si.'_d'] ?? $statDefs[$si]['d'] }}</div>
        </div>
        @endforeach
    </div>
</div>

{{-- ── BENEFICIOS ───────────────────────────────────────────────────── --}}
<section class="sec" id="beneficios">
    <div class="sec-in">
        <div class="sec-hdr fade">
            <div class="pill"><i class="bi bi-stars"></i>Beneficios</div>
            <h2 class="sec-title">Todo lo que necesita tu institución</h2>
            <p class="sec-sub">Una plataforma integral que simplifica la gestión y mejora la comunicación entre todos los actores del proceso educativo.</p>
        </div>
        <div class="bens">
            <div class="ben fade" style="--bc1:#1e3a8a;--bc2:#3b82f6;">
                <div class="ben-bar"></div>
                <div class="ben-icon" style="background:#dbeafe;color:#1d4ed8;"><i class="bi bi-journal-check"></i></div>
                <div class="ben-title">Gestión de Notas</div>
                <div class="ben-desc">Registro de calificaciones por período, generación automática de boletines y análisis de rendimiento académico en tiempo real.</div>
            </div>
            <div class="ben fade" style="--bc1:#059669;--bc2:#10b981;">
                <div class="ben-bar"></div>
                <div class="ben-icon" style="background:#dcfce7;color:#059669;"><i class="bi bi-calendar-check"></i></div>
                <div class="ben-title">Control de Asistencia</div>
                <div class="ben-desc">Pase de asistencia digital en segundos. Los representantes reciben notificaciones automáticas ante cualquier ausencia registrada.</div>
            </div>
            <div class="ben fade" style="--bc1:#7c3aed;--bc2:#a78bfa;">
                <div class="ben-bar"></div>
                <div class="ben-icon" style="background:#ede9fe;color:#7c3aed;"><i class="bi bi-calendar-week"></i></div>
                <div class="ben-title">Horarios Automáticos</div>
                <div class="ben-desc">Generación inteligente de horarios con detección automática de conflictos. Sin choques ni solapamientos entre docentes y aulas.</div>
            </div>
            <div class="ben fade" style="--bc1:#b45309;--bc2:#f59e0b;">
                <div class="ben-bar"></div>
                <div class="ben-icon" style="background:#fef9c3;color:#b45309;"><i class="bi bi-people-fill"></i></div>
                <div class="ben-title">Portal para Padres</div>
                <div class="ben-desc">Acceso en tiempo real a notas, asistencia, horarios y observaciones. Comunicación directa con docentes desde cualquier dispositivo.</div>
            </div>
        </div>
    </div>
</section>

{{-- ── MÓDULOS ──────────────────────────────────────────────────────── --}}
<section class="sec sec-alt" id="modulos">
    <div class="sec-in">
        <div class="sec-hdr fade">
            <div class="pill" style="background:#f0fdf4;color:#059669;border-color:#bbf7d0;"><i class="bi bi-grid-3x3-gap"></i>Módulos del sistema</div>
            <h2 class="sec-title">Un sistema diseñado para cada rol</h2>
            <p class="sec-sub">Cada actor del proceso educativo tiene su espacio diseñado exactamente para sus necesidades y responsabilidades.</p>
        </div>
        <div class="mods">
            <div class="mod fade" style="--mc:#1d4ed8;">
                <div class="mod-icon" style="background:#eff6ff;color:#1d4ed8;"><i class="bi bi-shield-lock-fill"></i></div>
                <div class="mod-title">Panel Administrativo</div>
                <ul class="mod-list">
                    <li>Gestión de estudiantes y docentes</li>
                    <li>Configuración de grupos y períodos</li>
                    <li>Mallas curriculares y asignaturas</li>
                    <li>Generación de horarios automáticos</li>
                    <li>Reportes y estadísticas avanzadas</li>
                    <li>Control de acceso por roles</li>
                </ul>
            </div>
            <div class="mod fade" style="--mc:#7c3aed;">
                <div class="mod-icon" style="background:#ede9fe;color:#7c3aed;"><i class="bi bi-person-badge-fill"></i></div>
                <div class="mod-title">Portal Docente</div>
                <ul class="mod-list">
                    <li>Registro de asistencia diaria</li>
                    <li>Calificaciones por período en línea</li>
                    <li>Estadísticas y gráficas de rendimiento</li>
                    <li>Horario semanal interactivo</li>
                    <li>Observaciones por estudiante</li>
                    <li>Boletines accesibles al instante</li>
                </ul>
            </div>
            <div class="mod fade" style="--mc:#059669;">
                <div class="mod-icon" style="background:#dcfce7;color:#059669;"><i class="bi bi-mortarboard-fill"></i></div>
                <div class="mod-title">Portal Estudiante</div>
                <ul class="mod-list">
                    <li>Consulta de calificaciones por período</li>
                    <li>Historial de asistencia detallado</li>
                    <li>Horario de clases semanal</li>
                    <li>Observaciones del docente</li>
                    <li>Noticias institucionales</li>
                    <li>Alertas y notificaciones</li>
                </ul>
            </div>
            <div class="mod fade" style="--mc:#b45309;">
                <div class="mod-icon" style="background:#fef9c3;color:#b45309;"><i class="bi bi-graph-up-arrow"></i></div>
                <div class="mod-title">Reportes y Boletines</div>
                <ul class="mod-list">
                    <li>Boletín de calificaciones completo</li>
                    <li>Reporte de asistencia por grupo</li>
                    <li>Estadísticas por período</li>
                    <li>Análisis de rendimiento académico</li>
                    <li>Exportación en múltiples formatos</li>
                    <li>Dashboard de alertas tempranas</li>
                </ul>
            </div>
        </div>
    </div>
</section>

{{-- ── VISTA PREVIA ─────────────────────────────────────────────────── --}}
<section class="sec" id="preview">
    <div class="sec-in">
        <div class="sec-hdr fade">
            <div class="pill" style="background:#f5f3ff;color:#7c3aed;border-color:#ddd6fe;"><i class="bi bi-display"></i>Vista previa</div>
            <h2 class="sec-title">Una interfaz que se aprende en minutos</h2>
            <p class="sec-sub">Diseñada para ser clara e intuitiva en cualquier dispositivo, sin necesidad de capacitación especial.</p>
        </div>

        <div class="tabs fade">
            <button class="tab-btn on" onclick="showTab('dashboard',this)"><i class="bi bi-grid-3x3-gap"></i>Dashboard</button>
            <button class="tab-btn" onclick="showTab('horario',this)"><i class="bi bi-calendar-week"></i>Horarios</button>
            <button class="tab-btn" onclick="showTab('notas',this)"><i class="bi bi-journal-text"></i>Notas</button>
        </div>

        {{-- Dashboard --}}
        <div id="tab-dashboard" class="bigmock fade">
            <div class="bigmock-bar">
                <div style="display:flex;gap:.3rem;"><div style="width:10px;height:10px;border-radius:50%;background:#ef4444;"></div><div style="width:10px;height:10px;border-radius:50%;background:#f59e0b;"></div><div style="width:10px;height:10px;border-radius:50%;background:#10b981;"></div></div>
                <div style="flex:1;background:rgba(255,255,255,.06);border-radius:5px;padding:.28rem .75rem;max-width:300px;margin:0 auto;font-size:.67rem;color:rgba(255,255,255,.28);">🔒 sistema.edu/admin/dashboard</div>
            </div>
            <div class="bigmock-body">
                <div class="bigmock-sb">
                    <div class="bigmock-sb-sect">Panel Principal</div>
                    @foreach([['bi-grid-3x3-gap','Dashboard',true],['bi-people-fill','Estudiantes',false],['bi-person-badge','Docentes',false],['bi-journal-text','Calificaciones',false],['bi-calendar-check','Asistencia',false],['bi-calendar-week','Horarios',false],['bi-megaphone','Comunicados',false],['bi-graph-up','Reportes',false]] as [$ic,$lb,$a])
                    <div class="bigmock-nav {{ $a ? 'on' : '' }}"><i class="bi {{ $ic }}"></i>{{ $lb }}</div>
                    @endforeach
                </div>
                <div class="bigmock-main">
                    <div style="font-size:.85rem;font-weight:800;color:#fff;margin-bottom:1.1rem;">Resumen del sistema</div>
                    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:.75rem;margin-bottom:1.1rem;">
                        @foreach([['248','Estudiantes','#93c5fd'],['18','Docentes','#6ee7b7'],['12','Grupos','#fbbf24'],['94%','Asistencia','#a78bfa']] as [$n,$l,$c])
                        <div style="background:rgba(255,255,255,.05);border-radius:10px;padding:.8rem .65rem;border:1px solid rgba(255,255,255,.06);">
                            <div style="font-size:1.2rem;font-weight:900;color:{{ $c }};">{{ $n }}</div>
                            <div style="font-size:.57rem;color:rgba(255,255,255,.35);margin-top:.1rem;">{{ $l }}</div>
                        </div>
                        @endforeach
                    </div>
                    <div style="background:rgba(255,255,255,.04);border-radius:10px;overflow:hidden;">
                        <div style="display:grid;grid-template-columns:2fr 1fr 1fr 1fr;padding:.5rem .75rem;background:rgba(255,255,255,.07);font-size:.6rem;color:rgba(255,255,255,.35);font-weight:700;">
                            <div>Estudiante</div><div>Grupo</div><div>Promedio</div><div>Estado</div>
                        </div>
                        @foreach([['María García','1-A','92','A','#34d399','rgba(52,211,153,.14)'],['Juan Pérez','2-B','78','B','#93c5fd','rgba(147,197,253,.14)'],['Luis Soto','1-A','55','F','#f87171','rgba(248,113,113,.14)'],['Ana Torres','3-C','85','B','#93c5fd','rgba(147,197,253,.14)'],['Carlos Ruiz','2-A','70','C','#fbbf24','rgba(251,191,36,.14)']] as [$nm,$gr,$nt,$lr,$cl,$bg])
                        <div style="display:grid;grid-template-columns:2fr 1fr 1fr 1fr;padding:.44rem .75rem;border-bottom:1px solid rgba(255,255,255,.04);font-size:.64rem;">
                            <div style="color:rgba(255,255,255,.72);">{{ $nm }}</div>
                            <div style="color:rgba(255,255,255,.42);">{{ $gr }}</div>
                            <div style="color:rgba(255,255,255,.65);">{{ $nt }}</div>
                            <div><span style="background:{{ $bg }};color:{{ $cl }};border-radius:4px;padding:.05rem .3rem;font-size:.57rem;font-weight:800;">{{ $lr }}</span></div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        {{-- Horario --}}
        <div id="tab-horario" class="bigmock fade" style="display:none;">
            <div class="bigmock-bar">
                <div style="display:flex;gap:.3rem;"><div style="width:10px;height:10px;border-radius:50%;background:#ef4444;"></div><div style="width:10px;height:10px;border-radius:50%;background:#f59e0b;"></div><div style="width:10px;height:10px;border-radius:50%;background:#10b981;"></div></div>
                <div style="flex:1;background:rgba(255,255,255,.06);border-radius:5px;padding:.28rem .75rem;max-width:300px;margin:0 auto;font-size:.67rem;color:rgba(255,255,255,.28);">🔒 sistema.edu/admin/horarios</div>
            </div>
            <div class="bigmock-body">
                <div class="bigmock-sb">
                    <div class="bigmock-sb-sect">Horarios</div>
                    @foreach([['bi-list-ul','Mis horarios',false],['bi-calendar-week','Ver horario',true],['bi-sliders2','Configuración',false],['bi-lightning','Generar',false]] as [$ic,$lb,$a])
                    <div class="bigmock-nav {{ $a ? 'on' : '' }}"><i class="bi {{ $ic }}"></i>{{ $lb }}</div>
                    @endforeach
                    <div style="margin-top:.85rem;background:rgba(16,185,129,.18);border-radius:8px;padding:.55rem .5rem;text-align:center;">
                        <div style="font-size:.62rem;color:#34d399;font-weight:800;">✓ Publicado</div>
                        <div style="font-size:.55rem;color:rgba(255,255,255,.3);margin-top:.15rem;">Año 2025</div>
                    </div>
                </div>
                <div class="bigmock-main">
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem;">
                        <div style="font-size:.85rem;font-weight:800;color:#fff;">Horario Semanal</div>
                        <div style="display:flex;gap:.45rem;">
                            <div style="background:rgba(37,99,235,.3);color:#93c5fd;border-radius:6px;padding:.24rem .6rem;font-size:.62rem;font-weight:700;">⚡ Generar</div>
                            <div style="background:rgba(16,185,129,.3);color:#34d399;border-radius:6px;padding:.24rem .6rem;font-size:.62rem;font-weight:700;">🗓 PDF</div>
                        </div>
                    </div>
                    @php
                    $mats2=[['Matemática','#3b82f6'],['Español','#10b981'],['Física','#8b5cf6'],['Historia','#f59e0b'],['Inglés','#ec4899'],['Química','#ef4444']];
                    $sched2=[['07:00',[0,2,4,1,3]],['08:00',[3,0,1,4,2]],['09:00',[1,3,null,2,0]],['10:00',null,'recreo'],['10:30',[2,4,3,0,5]],['11:30',[4,1,2,5,3]]];
                    @endphp
                    <div style="overflow-x:auto;">
                    <table style="width:100%;border-collapse:collapse;min-width:360px;">
                        <thead><tr style="background:rgba(255,255,255,.07);">
                            <th style="padding:.4rem .55rem;font-size:.58rem;color:rgba(255,255,255,.35);text-align:left;">Hora</th>
                            @foreach(['Lun','Mar','Mié','Jue','Vie'] as $d)
                            <th style="padding:.4rem .45rem;font-size:.58rem;color:rgba(255,255,255,.35);">{{ $d }}</th>
                            @endforeach
                        </tr></thead>
                        <tbody>
                        @foreach($sched2 as $row2)
                        @if(isset($row2[2]) && $row2[2]==='recreo')
                        <tr><td colspan="6" style="padding:.28rem;font-size:.57rem;color:rgba(255,255,255,.28);text-align:center;background:rgba(255,255,255,.02);border-bottom:1px solid rgba(255,255,255,.04);">☕ Recreo</td></tr>
                        @else
                        <tr style="border-bottom:1px solid rgba(255,255,255,.04);">
                            <td style="padding:.3rem .55rem;font-size:.6rem;color:rgba(255,255,255,.38);">{{ $row2[0] }}</td>
                            @foreach($row2[1] as $idx2)
                            <td style="padding:.22rem .28rem;">
                                @if($idx2!==null)
                                <div style="background:{{ $mats2[$idx2][1] }}2d;color:{{ $mats2[$idx2][1] }};border-radius:5px;padding:.22rem .3rem;font-size:.56rem;font-weight:700;text-align:center;border:1px solid {{ $mats2[$idx2][1] }}44;white-space:nowrap;">{{ $mats2[$idx2][0] }}</div>
                                @endif
                            </td>
                            @endforeach
                        </tr>
                        @endif
                        @endforeach
                        </tbody>
                    </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- Notas --}}
        <div id="tab-notas" class="bigmock fade" style="display:none;">
            <div class="bigmock-bar">
                <div style="display:flex;gap:.3rem;"><div style="width:10px;height:10px;border-radius:50%;background:#ef4444;"></div><div style="width:10px;height:10px;border-radius:50%;background:#f59e0b;"></div><div style="width:10px;height:10px;border-radius:50%;background:#10b981;"></div></div>
                <div style="flex:1;background:rgba(255,255,255,.06);border-radius:5px;padding:.28rem .75rem;max-width:300px;margin:0 auto;font-size:.67rem;color:rgba(255,255,255,.28);">🔒 sistema.edu/admin/calificaciones</div>
            </div>
            <div class="bigmock-body">
                <div class="bigmock-sb">
                    <div class="bigmock-sb-sect">Calificaciones</div>
                    @foreach([['bi-table','Grilla notas',true],['bi-journal-text','Por estudiante',false],['bi-file-earmark-pdf','Boletines',false],['bi-graph-up','Estadísticas',false]] as [$ic,$lb,$a])
                    <div class="bigmock-nav {{ $a ? 'on' : '' }}"><i class="bi {{ $ic }}"></i>{{ $lb }}</div>
                    @endforeach
                    <div style="margin-top:.75rem;padding:.55rem .5rem;background:rgba(255,255,255,.04);border-radius:7px;font-size:.58rem;color:rgba(255,255,255,.35);">
                        <div style="margin-bottom:.22rem;font-weight:700;color:rgba(255,255,255,.5);">Grupo: 1ro A</div>
                        <div>Período: I · 2025</div>
                    </div>
                </div>
                <div class="bigmock-main">
                    <div style="font-size:.85rem;font-weight:800;color:#fff;margin-bottom:.9rem;">Grilla de Calificaciones — 1ro A</div>
                    <div style="overflow-x:auto;">
                    <table style="width:100%;border-collapse:collapse;min-width:400px;">
                        <thead><tr style="background:rgba(255,255,255,.07);">
                            <th style="padding:.42rem .65rem;font-size:.58rem;color:rgba(255,255,255,.38);text-align:left;">Estudiante</th>
                            @foreach(['Matemática','Español','Inglés','Física','Promedio'] as $m2)
                            <th style="padding:.42rem .45rem;font-size:.58rem;color:rgba(255,255,255,.38);">{{ $m2 }}</th>
                            @endforeach
                        </tr></thead>
                        <tbody>
                        @php
                        $rows2=[['María García',[92,88,95,90,91]],['Juan Pérez',[78,82,75,70,76]],['Luis Soto',[55,60,52,48,54]],['Ana Torres',[85,90,88,82,86]],['Carlos Ruiz',[70,65,72,68,69]]];
                        $nc2=fn($n)=>$n>=90?['#34d399','rgba(52,211,153,.16)']:($n>=75?['#93c5fd','rgba(147,197,253,.16)']:($n>=60?['#fbbf24','rgba(251,191,36,.16)']:['#f87171','rgba(248,113,113,.16)']));
                        @endphp
                        @foreach($rows2 as [$nm2,$ns2])
                        <tr style="border-bottom:1px solid rgba(255,255,255,.04);">
                            <td style="padding:.4rem .65rem;font-size:.64rem;color:rgba(255,255,255,.72);font-weight:600;">{{ $nm2 }}</td>
                            @foreach($ns2 as $i2 => $nt2)
                            @php [$cl2,$bg2]=$nc2($nt2); @endphp
                            <td style="padding:.3rem .38rem;text-align:center;">
                                <span style="display:inline-block;background:{{ $bg2 }};color:{{ $cl2 }};border-radius:5px;padding:.12rem .38rem;font-size:.63rem;font-weight:{{ $i2===count($ns2)-1?'900':'600' }};">{{ $nt2 }}</span>
                            </td>
                            @endforeach
                        </tr>
                        @endforeach
                        </tbody>
                    </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ── PORTALES ─────────────────────────────────────────────────────── --}}
<section class="sec sec-alt" id="portales">
    <div class="sec-in">
        <div class="sec-hdr fade">
            <div class="pill" style="background:#fff7ed;color:#c2410c;border-color:#fed7aa;"><i class="bi bi-key-fill"></i>Acceso al sistema</div>
            <h2 class="sec-title">Elige tu portal para ingresar</h2>
            <p class="sec-sub">Cada usuario tiene su propio acceso personalizado, con solo los datos y herramientas que necesita.</p>
        </div>
        <div class="portals fade">
            <a href="{{ route('login') }}" class="portal-card" style="background:#eff6ff;color:#1d4ed8;">
                <i class="bi bi-shield-lock-fill"></i>
                <div class="portal-card-t">Administrador</div>
                <div class="portal-card-s">Panel completo del sistema</div>
            </a>
            <a href="{{ route('login') }}" class="portal-card" style="background:#f5f3ff;color:#7c3aed;">
                <i class="bi bi-person-badge-fill"></i>
                <div class="portal-card-t">Docente</div>
                <div class="portal-card-s">Asistencia, notas y horario</div>
            </a>
            <a href="{{ route('login') }}" class="portal-card" style="background:#f0fdf4;color:#059669;">
                <i class="bi bi-mortarboard-fill"></i>
                <div class="portal-card-t">Estudiante</div>
                <div class="portal-card-s">Mis notas y asistencia</div>
            </a>
            <a href="{{ route('login') }}" class="portal-card" style="background:#fffbeb;color:#b45309;">
                <i class="bi bi-people-fill"></i>
                <div class="portal-card-t">Representante</div>
                <div class="portal-card-s">Información de mis hijos</div>
            </a>
        </div>

        <div class="demo-box fade" style="margin-top:2.5rem;">
            <div style="display:flex;align-items:center;justify-content:center;gap:.5rem;margin-bottom:.35rem;">
                <div style="width:7px;height:7px;border-radius:50%;background:#10b981;animation:pdot 2s infinite;"></div>
                <span class="demo-title" style="margin:0;">Modo Demo disponible</span>
            </div>
            <div class="demo-sub">Explora el sistema sin crear una cuenta. Datos de ejemplo preconfigurados y listos.</div>
            <div class="demo-btns">
                <a href="{{ route('demo.login', 'docente') }}" class="btn btn-ghost" style="color:#7c3aed;border-color:#ddd6fe;"><i class="bi bi-person-badge"></i>Demo Docente</a>
                <a href="{{ route('demo.login', 'estudiante') }}" class="btn btn-ghost" style="color:#059669;border-color:#bbf7d0;"><i class="bi bi-mortarboard"></i>Demo Estudiante</a>
                <a href="{{ route('demo.login', 'padre') }}" class="btn btn-ghost" style="color:#b45309;border-color:#fde68a;"><i class="bi bi-people"></i>Demo Representante</a>
            </div>
            <div style="text-align:center;margin-top:.9rem;font-size:.72rem;color:var(--g400);">
                <i class="bi bi-info-circle me-1"></i>Contraseña demo: <code style="background:var(--g100);border-radius:4px;padding:.1rem .35rem;font-size:.7rem;">123456</code> · Los cambios críticos están bloqueados en modo demo
            </div>
            <div style="margin-top:1rem;padding-top:1rem;border-top:1px solid var(--g200);text-align:center;font-size:.81rem;color:var(--g500);">
                <i class="bi bi-person-plus me-1"></i>¿Eres docente del centro?
                <a href="{{ route('register') }}" style="color:#1e3a8a;font-weight:700;text-decoration:none;margin-left:.25rem;">
                    Solicita tu acceso institucional <i class="bi bi-arrow-right" style="font-size:.75rem;"></i>
                </a>
            </div>
        </div>
    </div>
</section>

{{-- ── TESTIMONIAL ──────────────────────────────────────────────────── --}}
<div class="testi">
    <div style="max-width:720px;margin:0 auto;" class="fade">
        <div class="testi-quote">"</div>
        <p class="testi-text">{{ $ls['landing_testimonio_cita'] ?? 'Desde que implementamos este sistema, el tiempo dedicado a la gestión administrativa se redujo a la mitad. Los padres ahora tienen acceso inmediato a las calificaciones y la comunicación con los docentes mejoró notablemente.' }}</p>
        <div class="testi-person">
            <div class="testi-avatar">{{ strtoupper(substr($ls['landing_testimonio_nombre'] ?? 'M', 0, 1)) }}</div>
            <div style="text-align:left;">
                <div class="testi-name">{{ $ls['landing_testimonio_nombre'] ?? 'María González' }}</div>
                <div class="testi-role">{{ $ls['landing_testimonio_cargo'] ?? 'Directora Académica · Centro Educativo Demo' }}</div>
            </div>
        </div>
    </div>
</div>

{{-- ── CTA ───────────────────────────────────────────────────────────── --}}
<section class="cta-sec">
    <div style="position:relative;z-index:1;" class="fade">
        <div style="display:inline-flex;align-items:center;gap:.45rem;background:#eff6ff;color:#1d4ed8;border:1px solid #bfdbfe;border-radius:99px;padding:.32rem 1rem;font-size:.73rem;font-weight:700;margin-bottom:1.5rem;">
            <i class="bi bi-lightning-fill" style="color:#f59e0b;"></i>Comienza hoy mismo
        </div>
        <h2>¿Listo para transformar<br>tu institución educativa?</h2>
        <p>Prueba el sistema sin compromiso. Demo completamente funcional, sin datos reales, sin registro obligatorio.</p>
        <div class="cta-btns">
            <a href="{{ route('login') }}" class="btn btn-primary btn-lg">
                <i class="bi bi-box-arrow-in-right"></i>Acceder al sistema
            </a>
            <a href="{{ route('demo.login', 'docente') }}" class="btn btn-ghost btn-lg">
                <i class="bi bi-play-circle"></i>Ver demo gratis
            </a>
        </div>
        <p style="font-size:.73rem;color:var(--g400);margin-top:1.5rem;">
            <i class="bi bi-shield-check me-1"></i>Datos demo separados · Sin registro · Sin tarjeta de crédito
        </p>
    </div>
</section>

{{-- ── FOOTER ───────────────────────────────────────────────────────── --}}
<footer class="footer">
    <div class="footer-in">
        <div class="footer-top">
            <div class="footer-brand">
                <a href="/" class="footer-logo">
                    <div class="footer-logo-icon"><i class="bi bi-mortarboard-fill"></i></div>
                    <span class="footer-logo-name">{{ env('APP_PRODUCT_NAME', config('app.name')) }}</span>
                </a>
                <p class="footer-desc">Sistema de gestión educativa completo para centros escolares modernos. Notas, asistencia, horarios y portales para toda la comunidad educativa.</p>
            </div>
            <div class="footer-col">
                <h4>Sistema</h4>
                <a href="#beneficios"><i class="bi bi-stars"></i>Beneficios</a>
                <a href="#modulos"><i class="bi bi-grid-3x3-gap"></i>Módulos</a>
                <a href="#preview"><i class="bi bi-display"></i>Vista previa</a>
                <a href="{{ route('login') }}"><i class="bi bi-box-arrow-in-right"></i>Iniciar sesión</a>
            </div>
            <div class="footer-col">
                <h4>Acceso Demo</h4>
                <a href="{{ route('demo.login', 'docente') }}"><i class="bi bi-person-badge"></i>Demo Docente</a>
                <a href="{{ route('demo.login', 'estudiante') }}"><i class="bi bi-mortarboard"></i>Demo Estudiante</a>
                <a href="{{ route('demo.login', 'padre') }}"><i class="bi bi-people"></i>Demo Representante</a>
            </div>
            <div class="footer-col">
                <h4>Portales</h4>
                <a href="{{ route('portal.docente.dashboard') }}"><i class="bi bi-person-badge-fill"></i>Portal Docente</a>
                <a href="{{ route('portal.estudiante.dashboard') }}"><i class="bi bi-mortarboard-fill"></i>Portal Estudiante</a>
                <a href="{{ route('portal.padre.dashboard') }}"><i class="bi bi-people-fill"></i>Portal Representante</a>
                <a href="/admin/dashboard"><i class="bi bi-shield-lock-fill"></i>Panel Administrativo</a>
            </div>
        </div>
        <div class="footer-bottom">
            <div class="footer-copy">© {{ date('Y') }} {{ env('APP_PRODUCT_NAME', config('app.name')) }} — Sistema de Gestión Escolar</div>
            <div class="footer-links">
                <a href="{{ route('login') }}">Acceso</a>
                <a href="{{ route('help.registro') }}">Ayuda</a>
            </div>
        </div>
    </div>
</footer>

<script>
function toggleNav() {
    const m = document.getElementById('mobile-nav');
    const i = document.getElementById('ham-ico');
    const open = m.style.display === 'none' || m.style.display === '';
    m.style.display = open ? 'block' : 'none';
    i.className = open ? 'bi bi-x' : 'bi bi-list';
}

function showTab(tab, btn) {
    document.querySelectorAll('[id^="tab-"]').forEach(el => el.style.display = 'none');
    document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('on'));
    document.getElementById('tab-' + tab).style.display = 'block';
    btn.classList.add('on');
}

// Fade in on scroll
const obs = new IntersectionObserver(entries => {
    entries.forEach(e => { if (e.isIntersecting) { e.target.classList.add('in'); obs.unobserve(e.target); } });
}, { threshold: 0.07 });
document.querySelectorAll('.fade').forEach(el => obs.observe(el));

// Navbar shadow
window.addEventListener('scroll', () => {
    document.getElementById('navbar').classList.toggle('scrolled', window.scrollY > 10);
}, { passive: true });
</script>
</body>
</html>
