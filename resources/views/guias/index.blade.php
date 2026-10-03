<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Guías rápidas por rol — ZuraEdu</title>
    <meta name="description" content="Una hoja por rol que explica qué se puede hacer en ZuraEdu y cómo empezar. Para ver, imprimir o descargar en PDF.">
    @include('partials.marca.head', ['sinPwa' => true])
    <link href="{{ asset('vendor/bootstrap-icons/bootstrap-icons.min.css') }}" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; background: #f1f5f9; font-family: system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif; color: #0f172a; }
        .cab { background: #0f172a; color: #fff; padding: 34px 16px 40px; text-align: center; }
        .cab img { height: 34px; margin-bottom: 14px; }
        .cab h1 { margin: 0 0 8px; font-size: clamp(1.5rem, 4vw, 2.2rem); }
        .cab p { margin: 0 auto; max-width: 620px; color: #cbd5e1; line-height: 1.5; }
        .cont { max-width: 1040px; margin: -22px auto 0; padding: 0 14px 30px; }
        .grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(290px, 1fr)); gap: 14px; }
        .card { background: #fff; border-radius: 16px; overflow: hidden; box-shadow: 0 6px 22px rgba(15,23,42,.08); display: flex; flex-direction: column; }
        .card .top { padding: 18px 18px 14px; color: #fff; }
        .card .top h2 { margin: 0 0 4px; font-size: 1.1rem; }
        .card .top small { color: #e2e8f0; font-size: .76rem; line-height: 1.35; display: block; }
        .card .cuerpo { padding: 14px 18px 4px; font-size: .88rem; color: #475569; line-height: 1.5; flex: 1; font-style: italic; }
        .card .acc { padding: 12px 18px 18px; display: flex; gap: 8px; }
        .card .acc a { flex: 1; text-align: center; padding: 9px 8px; border-radius: 10px; font-size: .85rem; font-weight: 600; text-decoration: none; border: 1px solid #cbd5e1; color: #334155; }
        .card .acc a.pri { color: #fff; border-color: transparent; }
        .volver { text-align: center; margin: 22px 0 8px; }
        .volver a { color: #475569; font-size: .9rem; }
    </style>
</head>
<body>
    <header class="cab">
        <x-marca.logo variante="blanco" :alto="34" />
        <h1>Una guía rápida para cada persona</h1>
        <p>Una hoja por rol: qué puedes hacer en ZuraEdu, cómo empezar en 3 pasos y qué atajos usar. Para leer en pantalla, imprimir o descargar en PDF.</p>
    </header>
    <main class="cont">
        <div class="grid">
            @foreach($guias as $slug => $g)
            <article class="card">
                <div class="top" style="background:{{ $g['color'] }};">
                    <h2>{{ $g['nombre'] }}</h2>
                    <small>{{ $g['perfiles'] }}</small>
                </div>
                <div class="cuerpo">«{{ $g['lema'] }}»</div>
                <div class="acc">
                    <a class="pri" style="background:{{ $g['color'] }};" href="{{ route('guias.show', $slug) }}"><i class="bi bi-eye"></i> Ver</a>
                    <a href="{{ route('guias.pdf', $slug) }}"><i class="bi bi-file-earmark-pdf"></i> PDF</a>
                </div>
            </article>
            @endforeach
        </div>
        <div class="volver"><a href="{{ auth()->check() ? url('/') : route('landing') }}"><i class="bi bi-arrow-left"></i> Volver al inicio</a></div>
        <x-marca.pie />
    </main>
</body>
</html>
