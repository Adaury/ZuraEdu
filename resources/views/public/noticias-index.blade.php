<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Noticias — {{ $nombre }}</title>
    <link href="/vendor/bootstrap-icons/bootstrap-icons.min.css" rel="stylesheet">
    <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    :root { --primary: {{ $colorPrimario }}; --g50:#f8fafc; --g200:#e2e8f0; --g500:#64748b; --g900:#0f172a; }
    body { font-family: 'Inter', -apple-system, 'Segoe UI', sans-serif; background: #fff; color: var(--g900); line-height: 1.6; }
    .navbar { position: sticky; top: 0; z-index: 100; background: rgba(255,255,255,.96); backdrop-filter: blur(12px); border-bottom: 1px solid var(--g200); padding: .9rem 1.5rem; display: flex; align-items: center; gap: .75rem; }
    .navbar img { height: 36px; border-radius: 8px; }
    .navbar .brand-badge { width: 36px; height: 36px; border-radius: 8px; background: var(--primary); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: .9rem; }
    .navbar .brand-name { font-weight: 700; font-size: 1.05rem; }
    .navbar .volver { margin-left: auto; font-size: .85rem; color: var(--g500); text-decoration: none; font-weight: 600; }
    .wrap { max-width: 64rem; margin: 0 auto; padding: 3rem 1.5rem 5rem; }
    h1 { font-size: 2rem; font-weight: 800; margin-bottom: 2rem; }
    .noticias-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 1.5rem; }
    .noticia-card { border-radius: 14px; overflow: hidden; border: 1px solid var(--g200); text-decoration: none; color: inherit; display: flex; flex-direction: column; transition: box-shadow .15s; }
    .noticia-card:hover { box-shadow: 0 8px 24px rgba(15,23,42,.08); }
    .noticia-card img { width: 100%; aspect-ratio: 16/9; object-fit: cover; }
    .noticia-card .cuerpo { padding: 1.1rem; }
    .noticia-tipo { display: inline-block; font-size: .7rem; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; color: var(--primary); margin-bottom: .4rem; }
    .noticia-titulo { font-weight: 700; font-size: 1.02rem; margin-bottom: .3rem; }
    .noticia-fecha { font-size: .78rem; color: var(--g500); }
    .paginacion { margin-top: 2.5rem; }
    .paginacion nav { display: flex; justify-content: center; }
    .paginacion .pagination { display: flex; gap: .4rem; list-style: none; }
    .paginacion .page-link { display: inline-block; padding: .4rem .8rem; border-radius: 8px; border: 1px solid var(--g200); text-decoration: none; color: var(--g900); font-size: .85rem; }
    .paginacion .page-item.active .page-link { background: var(--primary); border-color: var(--primary); color: #fff; }
    .paginacion .page-item.disabled .page-link { color: var(--g500); }
    .empty-state { text-align: center; padding: 4rem 1.5rem; color: var(--g500); }
    </style>
</head>
<body>

<header class="navbar">
    @if($logoUrl)
        <img src="{{ $logoUrl }}" alt="{{ $nombre }}">
    @else
        <div class="brand-badge">{{ strtoupper(substr($nombre, 0, 2)) }}</div>
    @endif
    <span class="brand-name">{{ $nombre }}</span>
    <a href="{{ route('sitio.show') }}" class="volver"><i class="bi bi-arrow-left me-1"></i>Volver al inicio</a>
</header>

<div class="wrap">
    <h1>Noticias y Publicaciones</h1>

    @if($publicaciones->isEmpty())
        <div class="empty-state">
            <i class="bi bi-newspaper" style="font-size:2.5rem;"></i>
            <p class="mt-3">Todavía no hay publicaciones.</p>
        </div>
    @else
        <div class="noticias-grid">
            @foreach($publicaciones as $noticia)
            <a href="{{ route('sitio.noticias.show', $noticia) }}" class="noticia-card">
                @if($noticia->imagen_url)
                    <img src="{{ $noticia->imagen_url }}" alt="{{ $noticia->titulo }}" loading="lazy">
                @endif
                <div class="cuerpo">
                    <span class="noticia-tipo">{{ $noticia->tipo_label }}</span>
                    <div class="noticia-titulo">{{ $noticia->titulo }}</div>
                    <div class="noticia-fecha">{{ $noticia->fecha->format('d/m/Y') }}</div>
                </div>
            </a>
            @endforeach
        </div>

        <div class="paginacion">{{ $publicaciones->links() }}</div>
    @endif
</div>

</body>
</html>
