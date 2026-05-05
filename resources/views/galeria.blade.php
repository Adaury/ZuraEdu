<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Galería — {{ env('APP_PRODUCT_NAME', config('app.name')) }}</title>
    <link href="/vendor/bootstrap-icons/bootstrap-icons.min.css" rel="stylesheet">
    <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    :root {
        --navy: #0a0e27; --blue: #1d4ed8; --blue-l: #3b82f6; --indigo: #4f46e5;
        --white: #ffffff; --g50: #f8fafc; --g100: #f1f5f9; --g200: #e2e8f0;
        --g400: #94a3b8; --g500: #64748b; --g700: #374151; --g900: #0f172a;
    }
    html { scroll-behavior: smooth; }
    body { font-family: 'Inter', -apple-system, 'Segoe UI', sans-serif; background: var(--g50); color: var(--g900); line-height: 1.6; }

    /* ── NAV ── */
    .nav {
        position: sticky; top: 0; z-index: 300;
        background: rgba(255,255,255,.95); backdrop-filter: blur(18px);
        border-bottom: 1px solid var(--g200); box-shadow: 0 1px 20px rgba(15,23,42,.07);
    }
    .nav-inner { max-width: 1180px; margin: 0 auto; padding: 0 1.5rem; display: flex; align-items: center; height: 64px; gap: 1.5rem; }
    .nav-logo { display: flex; align-items: center; gap: .55rem; text-decoration: none; }
    .nav-logo-icon { width: 34px; height: 34px; border-radius: 9px; background: linear-gradient(135deg, #1e3a8a, #3b82f6); display: flex; align-items: center; justify-content: center; color: #fff; font-size: .9rem; }
    .nav-logo-name { font-size: 1rem; font-weight: 900; color: var(--g900); }
    .nav-back { margin-left: auto; display: inline-flex; align-items: center; gap: .4rem; color: var(--g500); font-size: .84rem; font-weight: 600; text-decoration: none; padding: .4rem .9rem; border-radius: 8px; border: 1.5px solid var(--g200); transition: all .15s; }
    .nav-back:hover { border-color: var(--blue-l); color: var(--blue); background: #eff6ff; }

    /* ── HERO ── */
    .hero {
        background: linear-gradient(145deg, #060b20 0%, #0e1f5e 45%, #1d4ed8 100%);
        padding: 4.5rem 1.5rem 5rem; text-align: center;
    }
    .hero-pill {
        display: inline-flex; align-items: center; gap: .4rem;
        background: rgba(255,255,255,.1); border: 1px solid rgba(255,255,255,.2);
        color: rgba(255,255,255,.9); border-radius: 99px;
        padding: .28rem 1rem; font-size: .72rem; font-weight: 600;
        margin-bottom: 1.5rem; backdrop-filter: blur(8px);
    }
    .hero h1 { font-size: clamp(2rem, 5vw, 3.2rem); font-weight: 900; color: #fff; letter-spacing: -.03em; margin-bottom: 1rem; }
    .hero h1 em { font-style: normal; background: linear-gradient(135deg, #6ee7b7, #34d399); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
    .hero-sub { font-size: .96rem; color: rgba(255,255,255,.6); max-width: 480px; margin: 0 auto; }

    /* ── CONTENIDO ── */
    .wrap { max-width: 1180px; margin: 0 auto; padding: 3.5rem 1.5rem 5rem; }

    /* ── ALBUMES ── */
    .album-header { margin-bottom: 3.5rem; }
    .album-title { font-size: 1.5rem; font-weight: 900; color: var(--g900); margin-bottom: .4rem; display: flex; align-items: center; gap: .65rem; }
    .album-title .count { font-size: .78rem; font-weight: 600; background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; border-radius: 99px; padding: .18rem .7rem; }
    .album-desc { font-size: .88rem; color: var(--g500); }
    .album-divider { height: 2px; background: linear-gradient(90deg, #1d4ed8, #6366f1, transparent); border-radius: 2px; margin-top: 1rem; margin-bottom: 2rem; max-width: 120px; }

    /* ── GRID ── */
    .photo-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 1rem; }
    .photo-item {
        position: relative; aspect-ratio: 1; border-radius: 14px; overflow: hidden;
        background: var(--g200); cursor: pointer;
        box-shadow: 0 2px 10px rgba(0,0,0,.06); transition: all .22s;
    }
    .photo-item:hover { transform: translateY(-4px); box-shadow: 0 12px 32px rgba(0,0,0,.14); }
    .photo-item img { width: 100%; height: 100%; object-fit: cover; display: block; transition: transform .3s; }
    .photo-item:hover img { transform: scale(1.06); }
    .photo-caption {
        position: absolute; bottom: 0; left: 0; right: 0;
        background: linear-gradient(transparent, rgba(0,0,0,.65));
        color: #fff; font-size: .72rem; font-weight: 500;
        padding: 1.5rem .75rem .55rem; opacity: 0; transition: opacity .2s;
    }
    .photo-item:hover .photo-caption { opacity: 1; }

    .album-section { margin-bottom: 5rem; }
    .album-section:last-child { margin-bottom: 0; }

    /* ── VACÍO ── */
    .empty { text-align: center; padding: 5rem 1.5rem; }
    .empty-icon { width: 72px; height: 72px; background: #eff6ff; border-radius: 20px; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.25rem; font-size: 2rem; color: #3b82f6; }
    .empty h2 { font-size: 1.35rem; font-weight: 800; color: var(--g700); margin-bottom: .5rem; }
    .empty p { font-size: .9rem; color: var(--g400); }

    /* ── LIGHTBOX ── */
    .lb-overlay {
        position: fixed; inset: 0; z-index: 500; background: rgba(0,0,0,.92);
        display: flex; align-items: center; justify-content: center; padding: 1.5rem;
        opacity: 0; pointer-events: none; transition: opacity .2s;
    }
    .lb-overlay.open { opacity: 1; pointer-events: all; }
    .lb-box { position: relative; max-width: 900px; max-height: 90vh; text-align: center; }
    .lb-box img { max-width: 100%; max-height: 85vh; object-fit: contain; border-radius: 12px; box-shadow: 0 24px 80px rgba(0,0,0,.5); }
    .lb-caption { color: rgba(255,255,255,.8); font-size: .85rem; margin-top: .75rem; font-weight: 500; }
    .lb-close {
        position: absolute; top: -14px; right: -14px;
        width: 32px; height: 32px; border-radius: 50%;
        background: #fff; color: #0f172a; border: none; cursor: pointer;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.1rem; font-weight: 900; box-shadow: 0 4px 12px rgba(0,0,0,.3);
        transition: background .15s;
    }
    .lb-close:hover { background: #f1f5f9; }

    /* ── FOOTER ── */
    .footer-mini { background: var(--g900); padding: 2rem 1.5rem; text-align: center; }
    .footer-mini p { font-size: .75rem; color: rgba(255,255,255,.25); }
    .footer-mini a { color: rgba(255,255,255,.45); text-decoration: none; transition: color .15s; }
    .footer-mini a:hover { color: rgba(255,255,255,.75); }

    @media (max-width: 640px) {
        .photo-grid { grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: .65rem; }
    }
    </style>
</head>
<body>

{{-- NAV --}}
<nav class="nav">
    <div class="nav-inner">
        <a href="/" class="nav-logo">
            <div class="nav-logo-icon"><i class="bi bi-mortarboard-fill"></i></div>
            <span class="nav-logo-name">{{ env('APP_PRODUCT_NAME', 'SGE') }}</span>
        </a>
        <a href="/" class="nav-back">
            <i class="bi bi-arrow-left"></i>Volver al inicio
        </a>
    </div>
</nav>

{{-- HERO --}}
<div class="hero">
    <div class="hero-pill"><i class="bi bi-images"></i>Galería institucional</div>
    <h1>Nuestra <em>Galería</em></h1>
    <p class="hero-sub">Momentos especiales, eventos y actividades de nuestra comunidad educativa.</p>
</div>

{{-- CONTENIDO --}}
<div class="wrap">

    @if($albumes->isEmpty())
    <div class="empty">
        <div class="empty-icon"><i class="bi bi-images"></i></div>
        <h2>Sin contenido disponible</h2>
        <p>Aún no hay álbumes publicados. Vuelve pronto para ver nuestras fotos.</p>
    </div>
    @else

    @foreach($albumes as $album)
    @if($album->fotos->isNotEmpty())
    <div class="album-section" id="album-{{ $album->id }}">
        <div class="album-header">
            <h2 class="album-title">
                <i class="bi bi-folder2-open" style="color:#3b82f6;font-size:1.2rem;"></i>
                {{ $album->titulo }}
                <span class="count">{{ $album->fotos->count() }} fotos</span>
            </h2>
            @if($album->descripcion)
                <p class="album-desc">{{ $album->descripcion }}</p>
            @endif
            <div class="album-divider"></div>
        </div>

        <div class="photo-grid">
            @foreach($album->fotos as $foto)
            <div class="photo-item" onclick="openLightbox('{{ $foto->url }}', '{{ addslashes($foto->titulo ?? '') }}')">
                <img src="{{ $foto->url }}"
                     alt="{{ $foto->titulo ?? $album->titulo }}"
                     loading="lazy">
                @if($foto->titulo)
                    <div class="photo-caption">{{ $foto->titulo }}</div>
                @endif
            </div>
            @endforeach
        </div>
    </div>
    @endif
    @endforeach

    @endif

</div>

{{-- LIGHTBOX --}}
<div class="lb-overlay" id="lb" onclick="closeLightbox(event)">
    <div class="lb-box">
        <button class="lb-close" onclick="closeLightbox()">&times;</button>
        <img id="lb-img" src="" alt="">
        <p class="lb-caption" id="lb-caption"></p>
    </div>
</div>

{{-- FOOTER --}}
<div class="footer-mini">
    <p>© {{ date('Y') }} {{ env('APP_PRODUCT_NAME', config('app.name')) }} &mdash; <a href="/">Volver al inicio</a></p>
</div>

<script>
function openLightbox(src, caption) {
    document.getElementById('lb-img').src = src;
    document.getElementById('lb-caption').textContent = caption;
    document.getElementById('lb').classList.add('open');
    document.body.style.overflow = 'hidden';
}

function closeLightbox(event) {
    if (event && event.target !== document.getElementById('lb') && !event.target.classList.contains('lb-close')) return;
    document.getElementById('lb').classList.remove('open');
    document.body.style.overflow = '';
}

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeLightbox({ target: document.getElementById('lb') });
});
</script>
</body>
</html>
