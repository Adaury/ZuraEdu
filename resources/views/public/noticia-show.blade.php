<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $publicacion->titulo }} — {{ $nombre }}</title>
    <meta name="description" content="{{ Str::limit(strip_tags($publicacion->contenido), 160) }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link href="/vendor/bootstrap-icons/bootstrap-icons.min.css" rel="stylesheet">
    <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    :root { --primary: {{ $colorPrimario }}; --g200:#e2e8f0; --g500:#64748b; --g700:#374151; --g900:#0f172a; }
    body { font-family: 'Inter', -apple-system, 'Segoe UI', sans-serif; background: #fff; color: var(--g900); line-height: 1.7; }
    .navbar { position: sticky; top: 0; z-index: 100; background: rgba(255,255,255,.96); backdrop-filter: blur(12px); border-bottom: 1px solid var(--g200); padding: .9rem 1.5rem; display: flex; align-items: center; gap: .75rem; }
    .navbar img { height: 36px; border-radius: 8px; }
    .navbar .brand-badge { width: 36px; height: 36px; border-radius: 8px; background: var(--primary); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: .9rem; }
    .navbar .brand-name { font-weight: 700; font-size: 1.05rem; }
    .navbar .brand-link { display: flex; align-items: center; gap: .75rem; text-decoration: none; color: inherit; }
    .navbar .volver { margin-left: auto; font-size: .85rem; color: var(--g500); text-decoration: none; font-weight: 600; }
    .navbar .acceder { font-size: .85rem; color: var(--g500); text-decoration: none; font-weight: 600; display: flex; align-items: center; gap: .3rem; }
    .navbar .acceder:hover { color: var(--primary); }
    .wrap { max-width: 42rem; margin: 0 auto; padding: 3rem 1.5rem 5rem; position: relative; }
    .editar-publicacion { position: absolute; top: 2.2rem; right: 1.5rem; width: 36px; height: 36px; border-radius: 50%; background: var(--primary); color: #fff; display: flex; align-items: center; justify-content: center; text-decoration: none; box-shadow: 0 4px 12px rgba(0,0,0,.15); }
    .tipo { display: inline-block; font-size: .75rem; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; color: var(--primary); margin-bottom: .6rem; }
    h1 { font-size: 2rem; font-weight: 800; margin-bottom: .5rem; }
    .fecha { color: var(--g500); font-size: .9rem; margin-bottom: 1.5rem; }
    .imagen-destacada { max-width: 100%; border-radius: 14px; margin-bottom: 2rem; }
    .imagen-destacada.align-centro { display: block; width: 100%; }
    .imagen-destacada.align-izquierda { float: left; width: 45%; margin: 0 1.5rem 1rem 0; }
    .imagen-destacada.align-derecha { float: right; width: 45%; margin: 0 0 1rem 1.5rem; }
    .contenido { color: var(--g700); font-size: 1.05rem; white-space: pre-line; overflow: hidden; }
    .contenido p { margin-bottom: 1rem; }
    .contenido a { color: var(--primary); }
    </style>
</head>
<body>

<header class="navbar">
    <a href="{{ route('sitio.show') }}" class="brand-link">
        @if($logoUrl)
            <img src="{{ $logoUrl }}" alt="{{ $nombre }}">
        @else
            <div class="brand-badge">{{ strtoupper(substr($nombre, 0, 2)) }}</div>
        @endif
        <span class="brand-name">{{ $nombre }}</span>
    </a>
    <a href="{{ route('sitio.noticias') }}" class="volver"><i class="bi bi-arrow-left me-1"></i>Todas las noticias</a>
    <a href="{{ route('login') }}" class="acceder"><i class="bi bi-box-arrow-in-right"></i>Acceder al panel</a>
</header>

<div class="wrap">
    @can('gestionar-configuracion')
        <a href="{{ route('admin.publicaciones.edit', $publicacion) }}" class="editar-publicacion" title="Editar esta publicación">
            <i class="bi bi-pencil-fill"></i>
        </a>
    @endcan
    <span class="tipo">{{ $publicacion->tipo_label }}</span>
    <h1>{{ $publicacion->titulo }}</h1>
    <div class="fecha">{{ $publicacion->fecha->format('d/m/Y') }}</div>

    @if($publicacion->imagen_url)
        <img src="{{ $publicacion->imagen_url }}" alt="{{ $publicacion->titulo }}" class="imagen-destacada align-{{ $publicacion->imagen_alineacion }}">
    @endif

    <div class="contenido">{!! $publicacion->contenido !!}</div>
</div>

@include('partials.sitio_chat_widget', ['nombre' => $nombre])

</body>
</html>
