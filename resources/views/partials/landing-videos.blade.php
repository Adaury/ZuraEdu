{{--
    Videos de la portada: presentación (inicio), publicitario y uno por módulo.
    Los MP4 se generan con tools/videos (ver tools/videos/README.md) y viven en public/videos/<nombre>.mp4
    junto a su miniatura <nombre>.jpg. Cada video solo aparece si su archivo existe, así la portada nunca
    muestra un reproductor roto. CSS propio (prefijo zv-) para no depender de recompilar Tailwind.
--}}
@php
    $existe = fn (string $n) => is_file(public_path("videos/{$n}.mp4"));
    $conPoster = fn (string $n) => is_file(public_path("videos/{$n}.jpg")) ? asset("videos/{$n}.jpg") : null;

    $principal = ['zuraedu-presentacion' => ['Conoce ZuraEdu', 'Un recorrido rápido por la plataforma: dirección, docentes y familias.']];
    $promo     = ['zuraedu-promo'        => ['ZuraEdu en 35 segundos', 'Todo tu colegio en un solo lugar.']];
    $modulos   = [
        'modulo-inscripcion-matricula' => 'Inscripción y matrícula',
        'modulo-asistencia'            => 'Asistencia',
        'modulo-notas-boletines'       => 'Notas y boletines',
        'modulo-pagos'                 => 'Pagos y becas',
        'modulo-cafeteria'             => 'Cafetería',
        'modulo-comunicados'           => 'Comunicados',
        'modulo-portal-docente'        => 'Portal docente',
        'modulo-portal-familias'       => 'Portal de familias',
    ];

    $vPrincipal = collect($principal)->filter(fn ($_, $n) => $existe($n));
    $vPromo     = collect($promo)->filter(fn ($_, $n) => $existe($n));
    $vModulos   = collect($modulos)->filter(fn ($_, $n) => $existe($n));
@endphp

@if($vPrincipal->isNotEmpty() || $vPromo->isNotEmpty() || $vModulos->isNotEmpty())
<style>
    .zv-sec{padding:72px 16px;background:#f8fafc}
    .zv-wrap{max-width:1120px;margin:0 auto}
    .zv-head{text-align:center;margin-bottom:36px}
    .zv-head h2{font-size:clamp(1.7rem,3.2vw,2.3rem);font-weight:900;letter-spacing:-.02em;color:#0f172a;margin:0 0 10px}
    .zv-head p{color:#64748b;max-width:560px;margin:0 auto;line-height:1.6}
    .zv-grid2{display:grid;gap:22px;grid-template-columns:1fr}
    @media(min-width:860px){.zv-grid2.dos{grid-template-columns:1.5fr 1fr}}
    .zv-card{background:#fff;border:1px solid #e2e8f0;border-radius:18px;overflow:hidden;box-shadow:0 8px 24px rgba(15,23,42,.06)}
    .zv-card video{display:block;width:100%;aspect-ratio:16/9;background:#0f172a}
    .zv-card .zv-txt{padding:16px 18px}
    .zv-card h3{font-size:1.05rem;font-weight:800;color:#0f172a;margin:0 0 4px}
    .zv-card p{font-size:.9rem;color:#64748b;margin:0;line-height:1.5}
    .zv-mods{margin-top:42px}
    .zv-mods h3{font-size:1.15rem;font-weight:800;color:#0f172a;text-align:center;margin:0 0 18px}
    .zv-lista{display:grid;gap:14px;grid-template-columns:repeat(auto-fill,minmax(230px,1fr))}
    .zv-mod{all:unset;box-sizing:border-box;cursor:pointer;display:block;background:#fff;border:1px solid #e2e8f0;border-radius:14px;overflow:hidden;transition:transform .15s ease,box-shadow .15s ease}
    .zv-mod:hover,.zv-mod:focus-visible{transform:translateY(-3px);box-shadow:0 10px 24px rgba(37,99,235,.18);outline:2px solid #2563eb}
    .zv-mod .zv-mini{position:relative;aspect-ratio:16/9;background:linear-gradient(135deg,#1e3a6e,#4f46e5) center/cover no-repeat}
    .zv-mod .zv-mini::after{content:"";position:absolute;left:50%;top:50%;width:46px;height:46px;margin:-23px 0 0 -23px;border-radius:50%;background:rgba(255,255,255,.92) url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%232563eb'%3E%3Cpath d='M8 5v14l11-7z'/%3E%3C/svg%3E") 55% 50%/22px no-repeat}
    .zv-mod span{display:block;padding:11px 14px;font-weight:700;font-size:.92rem;color:#0f172a}
    #zv-modal{position:fixed;inset:0;z-index:9999;display:none;align-items:center;justify-content:center;padding:18px;background:rgba(2,6,23,.82)}
    #zv-modal.on{display:flex}
    #zv-modal .zv-caja{width:min(960px,100%);background:#0f172a;border-radius:14px;overflow:hidden;position:relative}
    #zv-modal video{display:block;width:100%;aspect-ratio:16/9;background:#000}
    #zv-modal .zv-tit{color:#fff;font-weight:700;padding:12px 16px}
    #zv-modal button{position:absolute;top:8px;right:10px;border:0;border-radius:999px;width:34px;height:34px;font-size:20px;line-height:1;cursor:pointer;background:rgba(255,255,255,.92);color:#0f172a}
</style>

<section class="zv-sec" id="videos">
    <div class="zv-wrap">
        <div class="zv-head">
            <h2>Míralo en acción</h2>
            <p>Videos cortos, sin necesidad de registrarte.</p>
        </div>

        @if($vPrincipal->isNotEmpty() || $vPromo->isNotEmpty())
        <div class="zv-grid2 {{ $vPrincipal->isNotEmpty() && $vPromo->isNotEmpty() ? 'dos' : '' }}">
            @foreach($vPrincipal->merge($vPromo) as $n => [$titulo, $desc])
            <div class="zv-card">
                <video controls playsinline preload="none" @if($conPoster($n)) poster="{{ $conPoster($n) }}" @endif>
                    <source src="{{ asset("videos/{$n}.mp4") }}" type="video/mp4">
                </video>
                <div class="zv-txt"><h3>{{ $titulo }}</h3><p>{{ $desc }}</p></div>
            </div>
            @endforeach
        </div>
        @endif

        @if($vModulos->isNotEmpty())
        <div class="zv-mods">
            <h3>Un video por módulo</h3>
            <div class="zv-lista">
                @foreach($vModulos as $n => $titulo)
                <button type="button" class="zv-mod" data-zv="{{ asset("videos/{$n}.mp4") }}" data-zv-t="{{ $titulo }}">
                    <div class="zv-mini" @if($conPoster($n)) style="background-image:url('{{ $conPoster($n) }}')" @endif></div>
                    <span>{{ $titulo }}</span>
                </button>
                @endforeach
            </div>
        </div>
        @endif
    </div>
</section>

<div id="zv-modal" role="dialog" aria-modal="true" aria-label="Video del módulo">
    <div class="zv-caja">
        <button type="button" id="zv-cerrar" aria-label="Cerrar">&times;</button>
        <div class="zv-tit" id="zv-titulo"></div>
        <video id="zv-player" controls playsinline preload="none"></video>
    </div>
</div>
<script>
(function () {
    var m = document.getElementById('zv-modal'), p = document.getElementById('zv-player'), t = document.getElementById('zv-titulo');
    if (!m) return;
    function cerrar() { m.classList.remove('on'); p.pause(); p.removeAttribute('src'); p.load(); }
    document.querySelectorAll('[data-zv]').forEach(function (b) {
        b.addEventListener('click', function () {
            p.src = b.getAttribute('data-zv'); t.textContent = b.getAttribute('data-zv-t');
            m.classList.add('on'); p.play().catch(function () {});
        });
    });
    document.getElementById('zv-cerrar').addEventListener('click', cerrar);
    m.addEventListener('click', function (e) { if (e.target === m) cerrar(); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && m.classList.contains('on')) cerrar(); });
})();
</script>
@endif
