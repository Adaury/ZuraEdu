<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Guía rápida — {{ $g['nombre'] }} — ZuraEdu</title>
    <meta name="description" content="Qué puede hacer {{ $g['nombre'] }} en ZuraEdu y cómo empezar en 3 pasos.">
    @include('partials.marca.head', ['sinPwa' => true])
    <link href="{{ asset('vendor/bootstrap-icons/bootstrap-icons.min.css') }}" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; background: #e2e8f0; font-family: system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif; color: #0f172a; }
        .barra { max-width: 794px; margin: 0 auto; padding: 16px 12px 0; display: flex; flex-wrap: wrap; gap: 8px; align-items: center; justify-content: space-between; }
        .barra a, .barra button { display: inline-flex; align-items: center; gap: 6px; padding: 9px 15px; border-radius: 10px; font-size: 14px; font-weight: 600; text-decoration: none; cursor: pointer; border: 1px solid #cbd5e1; background: #fff; color: #334155; }
        .barra .principal { background: {{ $g['color'] }}; border-color: {{ $g['color'] }}; color: #fff; }
        .barra a:hover, .barra button:hover { filter: brightness(.96); }
        .hoja { max-width: 794px; margin: 14px auto 0; background: #fff; box-shadow: 0 10px 40px rgba(15,23,42,.18); border-radius: 6px; overflow: hidden; }
        .otras { max-width: 794px; margin: 18px auto 8px; padding: 0 12px; display: flex; flex-wrap: wrap; gap: 6px; font-size: 13px; }
        .otras span { color: #64748b; margin-right: 4px; align-self: center; }
        .otras a { padding: 5px 11px; border-radius: 99px; background: #fff; border: 1px solid #cbd5e1; color: #334155; text-decoration: none; }
        .otras a.actual { background: {{ $g['color'] }}; border-color: {{ $g['color'] }}; color: #fff; }
        .pie-pagina { max-width: 794px; margin: 0 auto; padding: 0 12px 24px; }
        @media print {
            @page { size: A4; margin: 0; }
            body { background: #fff; }
            .barra, .otras, .pie-pagina { display: none !important; }
            .hoja { box-shadow: none; margin: 0; max-width: none; border-radius: 0; }
        }
        @media (max-width: 640px) { .hoja .fl td { display: block; width: auto !important; padding-left: 20px !important; padding-right: 20px !important; } }
    </style>
</head>
<body>
    <div class="barra">
        <div style="display:flex;gap:8px;flex-wrap:wrap;">
            <a href="{{ route('guias.index') }}"><i class="bi bi-grid"></i> Todas las guías</a>
            @auth<a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('landing') }}"><i class="bi bi-arrow-left"></i> Volver</a>@endauth
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap;">
            <button type="button" onclick="window.print()"><i class="bi bi-printer"></i> Imprimir</button>
            <a class="principal" href="{{ route('guias.pdf', $slug) }}"><i class="bi bi-file-earmark-pdf-fill"></i> Descargar PDF</a>
        </div>
    </div>

    <main class="hoja">@include('guias._flyer', ['g' => $g, 'pdf' => false])</main>

    <nav class="otras" aria-label="Otras guías"><span>Otras guías:</span>
        @foreach($guias as $s => $otra)<a href="{{ route('guias.show', $s) }}" class="{{ $s === $slug ? 'actual' : '' }}">{{ $otra['nombre'] }}</a>@endforeach
    </nav>

    <div class="pie-pagina"><x-marca.pie /></div>
</body>
</html>
