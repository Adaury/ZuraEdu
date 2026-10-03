{{--
    Icono de «Accesos rápidos» para la barra superior: abre un panel con las tareas que más usa el ROL del usuario.
    Uso: @include('partials.accesos-rapidos-boton', ['variante' => 'claro'|'oscuro'])   (oscuro = sobre una barra de color)
    Los accesos salen de App\Support\AccesosRapidos y ya vienen filtrados por lo que el usuario puede abrir.
--}}
@php
    $arItems = \App\Support\AccesosRapidos::para(auth()->user());
    $arOscuro = ($variante ?? 'claro') === 'oscuro';
@endphp
@if(count($arItems))
@once
<style>
    .ar-wrap { position: relative; display: inline-flex; }
    .ar-btn { width: 36px; height: 36px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; border: 1px solid #e2e8f0; background: #fff; color: #475569; font-size: 1rem; transition: background .15s, transform .15s; }
    .ar-btn:hover { background: #eef2ff; color: #4f46e5; transform: translateY(-1px); }
    .ar-btn.ar-oscuro { background: rgba(255,255,255,.15); border-color: transparent; color: #fff; }
    .ar-btn.ar-oscuro:hover { background: rgba(255,255,255,.28); color: #fff; }
    .ar-panel { position: absolute; top: calc(100% + 10px); right: 0; width: 344px; max-width: 92vw; background: #fff; border: 1px solid #e2e8f0; border-radius: 16px; box-shadow: 0 18px 50px rgba(15,23,42,.22); padding: .85rem; z-index: 2000; display: none; text-align: left; }
    .ar-panel.ar-abierto { display: block; animation: arEntrar .14s ease-out; }
    @keyframes arEntrar { from { opacity: 0; transform: translateY(-6px); } to { opacity: 1; transform: none; } }
    .ar-titulo { font-size: .78rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: .06em; margin: .1rem .25rem .6rem; display: flex; justify-content: space-between; align-items: center; }
    .ar-titulo kbd { font-size: .65rem; background: #f1f5f9; color: #64748b; border: 1px solid #e2e8f0; border-radius: 5px; padding: .05rem .35rem; text-transform: none; letter-spacing: 0; }
    .ar-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: .45rem; }
    .ar-tile { display: flex; flex-direction: column; align-items: center; gap: .35rem; padding: .65rem .3rem; border-radius: 12px; text-decoration: none; color: #1e293b; font-size: .74rem; font-weight: 600; text-align: center; line-height: 1.15; transition: background .12s, transform .12s; }
    .ar-tile:hover, .ar-tile:focus-visible { background: #f1f5f9; color: #0f172a; transform: translateY(-2px); outline: none; }
    .ar-guia { display: flex; align-items: center; gap: .45rem; margin: .65rem .15rem 0; padding: .55rem .65rem; border-radius: 10px; background: #f1f5f9; color: #334155; font-size: .78rem; font-weight: 600; text-decoration: none; }
    .ar-guia:hover { background: #e0e7ff; color: #3730a3; }
    [data-theme="dark"] .ar-guia { background: #0f172a; color: #cbd5e1; }
    .ar-ico { width: 42px; height: 42px; border-radius: 12px; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 1.15rem; box-shadow: 0 4px 10px rgba(15,23,42,.15); }
    [data-theme="dark"] .ar-btn:not(.ar-oscuro) { background: #1e293b; border-color: #334155; color: #cbd5e1; }
    [data-theme="dark"] .ar-panel { background: #1e293b; border-color: #334155; }
    [data-theme="dark"] .ar-tile { color: #e2e8f0; }
    [data-theme="dark"] .ar-tile:hover { background: #334155; }
    [data-theme="dark"] .ar-titulo kbd { background: #0f172a; border-color: #334155; color: #94a3b8; }
    @media (max-width: 575.98px) {
        /* Tarjeta flotante por encima de la barra de navegación inferior del portal (si no, la tapaba) */
        .ar-panel { position: fixed; left: 8px; right: 8px; top: auto; bottom: 78px; width: auto; max-width: none; border-radius: 20px; padding-bottom: 1rem; max-height: calc(100vh - 140px); overflow-y: auto; }
    }
</style>
@endonce

<div class="ar-wrap" data-ar>
    <button type="button" class="ar-btn {{ $arOscuro ? 'ar-oscuro' : '' }}" id="arBoton" title="Accesos rápidos (Alt+Q)" aria-label="Accesos rápidos" aria-haspopup="true" aria-expanded="false" aria-controls="arPanel">
        <i class="bi bi-grid-3x3-gap-fill"></i>
    </button>
    <div class="ar-panel" id="arPanel" role="menu" aria-label="Accesos rápidos">
        <div class="ar-titulo"><span>Accesos rápidos</span><kbd>Alt + Q</kbd></div>
        <div class="ar-grid">
            @foreach($arItems as $a)
                <a href="{{ $a['url'] }}" class="ar-tile" role="menuitem">
                    <span class="ar-ico" style="background:{{ $a['color'] }};"><i class="bi {{ $a['icono'] }}"></i></span>
                    <span>{{ $a['etiqueta'] }}</span>
                </a>
            @endforeach
        </div>
        <a href="{{ route('guias.mia') }}" class="ar-guia" role="menuitem"><i class="bi bi-book-half"></i> Guía rápida de tu rol (ver, imprimir o PDF)</a>
    </div>
</div>

@once
<script>
(function () {
    const btn = document.getElementById('arBoton'), panel = document.getElementById('arPanel');
    if (!btn || !panel) return;
    const abrir = (v) => {
        panel.classList.toggle('ar-abierto', v);
        btn.setAttribute('aria-expanded', v ? 'true' : 'false');
        if (v) { const p = panel.querySelector('.ar-tile'); p && p.focus(); }
    };
    btn.addEventListener('click', (e) => { e.stopPropagation(); abrir(!panel.classList.contains('ar-abierto')); });
    document.addEventListener('click', (e) => { if (!panel.contains(e.target)) abrir(false); });
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') { abrir(false); btn.focus(); }
        if (e.altKey && (e.key === 'q' || e.key === 'Q')) { e.preventDefault(); abrir(!panel.classList.contains('ar-abierto')); }
    });
})();
</script>
@endonce
@endif
