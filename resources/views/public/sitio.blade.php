<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $config['nombre'] }}</title>
    @php
        $seccionDescripcion = $secciones->firstWhere('tipo', 'hero') ?? $secciones->firstWhere('tipo', 'about');
        $metaDescripcion = $seccionDescripcion
            ? (\Illuminate\Support\Str::limit(strip_tags($seccionDescripcion->dato('subtitulo') ?: $seccionDescripcion->dato('texto') ?: ''), 160) ?: $config['nombre'])
            : $config['nombre'];
    @endphp
    <meta name="description" content="{{ $metaDescripcion }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link href="/vendor/bootstrap-icons/bootstrap-icons.min.css" rel="stylesheet">
    <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    :root {
        --primary:   {{ $config['color_primario'] }};
        --secondary: {{ $config['color_secundario'] }};
        --g50:  #f8fafc;
        --g100: #f1f5f9;
        --g200: #e2e8f0;
        --g500: #64748b;
        --g700: #374151;
        --g900: #0f172a;
    }
    html { scroll-behavior: smooth; }
    body { font-family: 'Inter', -apple-system, 'Segoe UI', sans-serif; background: #fff; color: var(--g900); line-height: 1.6; }

    .navbar { position: sticky; top: 0; z-index: 100; background: rgba(255,255,255,.96); backdrop-filter: blur(12px); border-bottom: 1px solid var(--g200); padding: .9rem 1.5rem; display: flex; align-items: center; gap: .75rem; }
    .navbar img { height: 36px; border-radius: 8px; }
    .navbar .brand-badge { width: 36px; height: 36px; border-radius: 8px; background: var(--primary); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: .9rem; }
    .navbar .brand-name { font-weight: 700; font-size: 1.05rem; }
    .navbar .brand-link { display: flex; align-items: center; gap: .75rem; text-decoration: none; color: inherit; }
    .navbar .acceder { margin-left: auto; font-size: .85rem; color: var(--g500); text-decoration: none; font-weight: 600; display: flex; align-items: center; gap: .3rem; }
    .navbar .acceder:hover { color: var(--primary); }

    .hero { background: linear-gradient(135deg, var(--primary), var(--secondary)); color: #fff; padding: 5rem 1.5rem; text-align: center; }
    .hero h1 { font-size: clamp(1.8rem, 4vw, 3rem); font-weight: 800; max-width: 46rem; margin: 0 auto 1rem; }
    .hero p { font-size: 1.1rem; opacity: .92; max-width: 40rem; margin: 0 auto 2rem; }
    .hero .actions { display: flex; gap: .8rem; justify-content: center; flex-wrap: wrap; }
    .btn { display: inline-block; padding: .75rem 1.6rem; border-radius: 10px; font-weight: 600; text-decoration: none; font-size: .95rem; }
    .btn-light { background: #fff; color: var(--primary); }
    .btn-outline { background: transparent; color: #fff; border: 1.5px solid rgba(255,255,255,.7); }

    .carrusel { position: relative; max-width: 72rem; margin: 0 auto; padding: 2.5rem 1.5rem; }
    .carrusel-intro { text-align: center; margin-bottom: 1.5rem; }
    .carrusel-historia { color: var(--g500); max-width: 42rem; margin: 0 auto; }
    .carrusel-track { position: relative; border-radius: 16px; overflow: hidden; aspect-ratio: 16/7; background: var(--g100); }
    .carrusel-slide { position: absolute; inset: 0; opacity: 0; transition: opacity .6s ease; }
    .carrusel-slide.activa { opacity: 1; }
    .carrusel-slide img { width: 100%; height: 100%; object-fit: cover; }
    .carrusel-caption { position: absolute; left: 0; right: 0; bottom: 0; padding: 1.5rem; background: linear-gradient(0deg, rgba(0,0,0,.65), transparent); color: #fff; font-weight: 600; }
    .carrusel-nav { position: absolute; top: 50%; transform: translateY(-50%); width: 40px; height: 40px; border-radius: 50%; background: rgba(255,255,255,.85); border: none; display: flex; align-items: center; justify-content: center; cursor: pointer; color: var(--g900); font-size: 1.1rem; }
    .carrusel-nav.prev { left: 1rem; }
    .carrusel-nav.next { right: 1rem; }
    .carrusel-dots { display: flex; gap: .4rem; justify-content: center; margin-top: 1rem; }
    .carrusel-dots button { width: 8px; height: 8px; border-radius: 50%; border: none; background: var(--g200); cursor: pointer; padding: 0; }
    .carrusel-dots button.activa { background: var(--primary); }

    .noticias-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 1.5rem; }
    .noticia-card { position: relative; border-radius: 14px; overflow: hidden; border: 1px solid var(--g200); transition: box-shadow .15s; }
    .noticia-card:hover { box-shadow: 0 8px 24px rgba(15,23,42,.08); }
    .noticia-card-stretched { position: absolute; inset: 0; z-index: 1; text-decoration: none; color: inherit; }
    .noticia-editar { position: absolute; top: .6rem; right: .6rem; z-index: 2; width: 30px; height: 30px; border-radius: 50%; background: rgba(15,23,42,.65); color: #fff; display: flex; align-items: center; justify-content: center; text-decoration: none; font-size: .8rem; }
    .noticia-card img { width: 100%; aspect-ratio: 16/9; object-fit: cover; }
    .noticia-card .cuerpo { padding: 1.1rem; }
    .noticia-tipo { display: inline-block; font-size: .7rem; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; color: var(--primary); margin-bottom: .4rem; }
    .noticia-titulo { font-weight: 700; font-size: 1.02rem; margin-bottom: .3rem; }
    .noticia-fecha { font-size: .78rem; color: var(--g500); }
    .noticia-resumen { font-size: .85rem; color: var(--g500); margin-top: .6rem; line-height: 1.5; }
    .noticia-leermas { display: inline-block; font-size: .8rem; font-weight: 700; color: var(--primary); margin-top: .6rem; }
    .noticias-vertodas { display: block; text-align: center; margin-top: 2rem; }

    section { padding: 4rem 1.5rem; }
    .section-inner { max-width: 64rem; margin: 0 auto; }
    .section-title { font-size: 1.8rem; font-weight: 800; margin-bottom: 1rem; text-align: center; }
    .about-text { font-size: 1.05rem; color: var(--g700); max-width: 42rem; margin: 0 auto; text-align: center; }

    .features-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 2rem; }
    .feature-card { text-align: center; }
    .feature-card i { font-size: 2rem; color: var(--primary); margin-bottom: .75rem; display: block; }
    .feature-card h3 { font-size: 1.05rem; font-weight: 700; margin-bottom: .4rem; }
    .feature-card p { color: var(--g500); font-size: .9rem; }

    .stats { background: var(--g50); }
    .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 1.5rem; text-align: center; }
    .stats-grid .num { font-size: 2.2rem; font-weight: 800; color: var(--primary); }
    .stats-grid .lbl { color: var(--g500); font-size: .9rem; margin-top: .25rem; }

    .contacto { background: var(--g900); color: #fff; }
    .contacto .section-title { color: #fff; }
    .contacto-grid { display: flex; flex-wrap: wrap; gap: 2rem; justify-content: center; text-align: center; }
    .contacto-item { display: flex; flex-direction: column; align-items: center; gap: .4rem; min-width: 180px; }
    .contacto-item i { font-size: 1.4rem; color: var(--primary); }
    .socials { display: flex; gap: 1rem; justify-content: center; margin-top: 2rem; }
    .socials a { width: 42px; height: 42px; border-radius: 50%; background: rgba(255,255,255,.1); display: flex; align-items: center; justify-content: center; color: #fff; text-decoration: none; font-size: 1.1rem; }

    footer { text-align: center; padding: 1.5rem; color: var(--g500); font-size: .8rem; }

    .empty-state { text-align: center; padding: 5rem 1.5rem; color: var(--g500); }

    /* Espacios publicitarios (Google Ads u otro, configurados por el propio
       centro) — solo en pantallas anchas, donde sobra espacio a los lados del
       contenido centrado; en mobile no hay sitio y se ocultan. */
    /* overflow:hidden + max-height es la única defensa real contra un admin
       que pega en este campo un snippet completo de página (con su propio
       <style> global sin scoping) en vez de solo su código de anuncio — sin
       esto, ese contenido se desborda de los 160px y tapa el resto del sitio. */
    .ads-lateral { position: fixed; top: 6.5rem; width: 160px; max-height: calc(100vh - 8rem); overflow: hidden; z-index: 10; }
    .ads-izquierda { left: 1rem; }
    .ads-derecha { right: 1rem; }
    @media (max-width: 1399px) { .ads-lateral { display: none; } }
    </style>
</head>
<body>

<header class="navbar">
    <a href="{{ route('sitio.show') }}" class="brand-link">
        @if($config['logo_url'])
            <img src="{{ $config['logo_url'] }}" alt="{{ $config['nombre'] }}">
        @else
            <div class="brand-badge">{{ strtoupper(substr($config['nombre'], 0, 2)) }}</div>
        @endif
        <span class="brand-name">{{ $config['nombre'] }}</span>
    </a>
    <a href="{{ route('login') }}" class="acceder"><i class="bi bi-box-arrow-in-right"></i>Acceder al panel</a>
</header>

@if($config['ads_izquierda'])
    <aside class="ads-lateral ads-izquierda">{!! $config['ads_izquierda'] !!}</aside>
@endif
@if($config['ads_derecha'])
    <aside class="ads-lateral ads-derecha">{!! $config['ads_derecha'] !!}</aside>
@endif

@foreach($secciones as $seccion)
    @include("public.sitio-secciones.{$seccion->tipo}", ['seccion' => $seccion])
@endforeach

@if($secciones->isEmpty())
<div class="empty-state">
    <i class="bi bi-building" style="font-size:2.5rem;"></i>
    <p class="mt-3">Esta institución aún no ha publicado contenido en su sitio público.</p>
</div>
@endif

<footer>{{ $config['nombre'] }} &middot; {{ now()->year }}</footer>

<script>
(function () {
    // Cada bloque .carrusel es una instancia independiente -- puede haber
    // varios en la misma página, cada uno con sus propios slides/dots/nav,
    // así que todo el querySelector queda escopeado a "root" (antes se
    // buscaba en document entero, y un segundo carrusel no tenía JS propio
    // y sus dots controlaban al primero).
    document.querySelectorAll('.carrusel').forEach(function (root) {
        var track = root.querySelector('.carrusel-track');
        if (!track) return;

        var slides = Array.prototype.slice.call(track.querySelectorAll('.carrusel-slide'));
        var dots   = Array.prototype.slice.call(root.querySelectorAll('.carrusel-dots button'));
        if (slides.length < 2) return;

        var actual = 0, timer;

        function mostrar(i) {
            slides[actual].classList.remove('activa');
            if (dots[actual]) dots[actual].classList.remove('activa');
            actual = (i + slides.length) % slides.length;
            slides[actual].classList.add('activa');
            if (dots[actual]) dots[actual].classList.add('activa');
        }

        function auto() {
            clearInterval(timer);
            timer = setInterval(function () { mostrar(actual + 1); }, 5000);
        }

        var prev = root.querySelector('.carrusel-nav.prev');
        var next = root.querySelector('.carrusel-nav.next');
        if (prev) prev.addEventListener('click', function () { mostrar(actual - 1); auto(); });
        if (next) next.addEventListener('click', function () { mostrar(actual + 1); auto(); });
        dots.forEach(function (dot, i) {
            dot.addEventListener('click', function () { mostrar(i); auto(); });
        });

        auto();
    });
})();
</script>

@include('partials.sitio_chat_widget', ['nombre' => $config['nombre']])

</body>
</html>
