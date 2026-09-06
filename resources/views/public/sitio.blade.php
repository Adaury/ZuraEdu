<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $config['nombre'] }}</title>
    <meta name="description" content="{{ $config['hero_subtitulo'] ?: $config['about_texto'] ?: $config['nombre'] }}">
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

    .hero { background: linear-gradient(135deg, var(--primary), var(--secondary)); color: #fff; padding: 5rem 1.5rem; text-align: center; }
    .hero h1 { font-size: clamp(1.8rem, 4vw, 3rem); font-weight: 800; max-width: 46rem; margin: 0 auto 1rem; }
    .hero p { font-size: 1.1rem; opacity: .92; max-width: 40rem; margin: 0 auto 2rem; }
    .hero .actions { display: flex; gap: .8rem; justify-content: center; flex-wrap: wrap; }
    .btn { display: inline-block; padding: .75rem 1.6rem; border-radius: 10px; font-weight: 600; text-decoration: none; font-size: .95rem; }
    .btn-light { background: #fff; color: var(--primary); }
    .btn-outline { background: transparent; color: #fff; border: 1.5px solid rgba(255,255,255,.7); }

    section { padding: 4rem 1.5rem; }
    .section-inner { max-width: 64rem; margin: 0 auto; }
    .section-title { font-size: 1.8rem; font-weight: 800; margin-bottom: 1rem; text-align: center; }
    .about-text { font-size: 1.05rem; color: var(--g700); max-width: 42rem; margin: 0 auto; text-align: center; }

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
    </style>
</head>
<body>

<header class="navbar">
    @if($config['logo_url'])
        <img src="{{ $config['logo_url'] }}" alt="{{ $config['nombre'] }}">
    @else
        <div class="brand-badge">{{ strtoupper(substr($config['nombre'], 0, 2)) }}</div>
    @endif
    <span class="brand-name">{{ $config['nombre'] }}</span>
</header>

@php
    $showHero     = $config['hero_visible'];
    $showAbout    = $config['about_visible'] && ($config['about_titulo'] || $config['about_texto']);
    $showStats    = $config['stats_visible'] && $config['stats']->isNotEmpty();
    $showFeatures = $config['features_visible'] && $config['features_titulo'];
    $showContacto = $config['contacto_visible'] && ($config['contacto_direccion'] || $config['contacto_telefono'] || $config['contacto_email']);
@endphp

@if($showHero)
<section class="hero">
    <h1>{{ $config['hero_titulo'] ?: $config['nombre'] }}</h1>
    @if($config['hero_subtitulo'])
        <p>{{ $config['hero_subtitulo'] }}</p>
    @endif
    @if($config['hero_btn_texto'] || $config['hero_btn2_texto'])
    <div class="actions">
        @if($config['hero_btn_texto'])
            <a href="{{ route('inscripcion') }}" class="btn btn-light">{{ $config['hero_btn_texto'] }}</a>
        @endif
        @if($config['hero_btn2_texto'])
            <a href="#contacto" class="btn btn-outline">{{ $config['hero_btn2_texto'] }}</a>
        @endif
    </div>
    @endif
</section>
@endif

@if($showAbout)
<section>
    <div class="section-inner">
        @if($config['about_titulo'])<h2 class="section-title">{{ $config['about_titulo'] }}</h2>@endif
        @if($config['about_texto'])<p class="about-text">{{ $config['about_texto'] }}</p>@endif
    </div>
</section>
@endif

@if($showStats)
<section class="stats">
    <div class="section-inner">
        <div class="stats-grid">
            @foreach($config['stats'] as $stat)
            <div>
                <div class="num">{{ $stat['numero'] }}</div>
                <div class="lbl">{{ $stat['label'] }}</div>
            </div>
            @endforeach
        </div>
    </div>
</section>
@endif

@if($showFeatures)
<section>
    <div class="section-inner">
        <h2 class="section-title">{{ $config['features_titulo'] }}</h2>
    </div>
</section>
@endif

@if($showContacto)
<section class="contacto" id="contacto">
    <div class="section-inner">
        <h2 class="section-title">Contacto</h2>
        <div class="contacto-grid">
            @if($config['contacto_direccion'])
            <div class="contacto-item"><i class="bi bi-geo-alt-fill"></i>{{ $config['contacto_direccion'] }}</div>
            @endif
            @if($config['contacto_telefono'])
            <div class="contacto-item"><i class="bi bi-telephone-fill"></i>{{ $config['contacto_telefono'] }}</div>
            @endif
            @if($config['contacto_email'])
            <div class="contacto-item"><i class="bi bi-envelope-fill"></i>{{ $config['contacto_email'] }}</div>
            @endif
        </div>
        @if($config['social_facebook'] || $config['social_instagram'] || $config['social_twitter'])
        <div class="socials">
            @if($config['social_facebook'])<a href="{{ $config['social_facebook'] }}" target="_blank" rel="noopener"><i class="bi bi-facebook"></i></a>@endif
            @if($config['social_instagram'])<a href="{{ $config['social_instagram'] }}" target="_blank" rel="noopener"><i class="bi bi-instagram"></i></a>@endif
            @if($config['social_twitter'])<a href="{{ $config['social_twitter'] }}" target="_blank" rel="noopener"><i class="bi bi-twitter-x"></i></a>@endif
        </div>
        @endif
    </div>
</section>
@endif

@if(!$showHero && !$showAbout && !$showStats && !$showFeatures && !$showContacto)
<div class="empty-state">
    <i class="bi bi-building" style="font-size:2.5rem;"></i>
    <p class="mt-3">Esta institución aún no ha publicado contenido en su sitio público.</p>
</div>
@endif

<footer>{{ $config['nombre'] }} &middot; {{ now()->year }}</footer>

</body>
</html>
