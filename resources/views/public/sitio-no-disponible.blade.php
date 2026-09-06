<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $tenant->nombre_institucion }}</title>
    <link href="/vendor/bootstrap-icons/bootstrap-icons.min.css" rel="stylesheet">
    <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body {
        font-family: 'Inter', -apple-system, 'Segoe UI', sans-serif;
        background: #f8fafc;
        color: #0f172a;
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 2rem 1rem;
    }
    .card {
        background: #fff;
        border-radius: 20px;
        box-shadow: 0 25px 60px rgba(15,23,42,.08);
        max-width: 420px;
        width: 100%;
        padding: 2.5rem;
        text-align: center;
    }
    .brand-badge {
        width: 56px; height: 56px; border-radius: 12px;
        background: {{ $tenant->color_primario ?? '#0d6efd' }};
        color: #fff; display: flex; align-items: center; justify-content: center;
        font-weight: 800; font-size: 1.1rem; margin: 0 auto 1.25rem;
    }
    .brand-badge img { width: 100%; height: 100%; object-fit: contain; border-radius: 12px; }
    h1 { font-size: 1.15rem; font-weight: 700; margin-bottom: .5rem; }
    p { color: #64748b; font-size: .9rem; line-height: 1.6; }
    </style>
</head>
<body>
<div class="card">
    <div class="brand-badge">
        @if($tenant->logo_url)
            <img src="{{ $tenant->logo_url }}" alt="{{ $tenant->nombre_institucion }}">
        @else
            {{ strtoupper(substr($tenant->nombre_institucion, 0, 2)) }}
        @endif
    </div>
    <h1>{{ $tenant->nombre_institucion }}</h1>
    <p><i class="bi bi-info-circle me-1"></i>Esta institución aún no tiene su portal público activado.</p>
</div>
</body>
</html>
