<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Código no válido</title>
<link href="/vendor/bootstrap-icons/bootstrap-icons.min.css" rel="stylesheet">
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    background: #f1f5f9;
    min-height: 100dvh;
    display: flex; align-items: center; justify-content: center;
    padding: 1.5rem;
}
.card {
    background: #fff; border-radius: 24px;
    box-shadow: 0 8px 40px rgba(15,23,42,.12);
    padding: 2.5rem 2rem; text-align: center;
    max-width: 380px; width: 100%;
}
</style>
</head>
<body>
<div class="card">
    <div style="width:72px;height:72px;border-radius:50%;background:#fee2e2;display:flex;align-items:center;justify-content:center;margin:0 auto 1.25rem;">
        <i class="bi bi-qr-code" style="font-size:2rem;color:#dc2626;"></i>
    </div>
    <h1 style="font-size:1.2rem;font-weight:900;color:#0f172a;margin-bottom:.5rem;">Código no válido</h1>
    <p style="font-size:.88rem;color:#64748b;line-height:1.6;">{{ $mensaje }}</p>
    @if(auth()->check())
    <a href="{{ auth()->user()->hasRole('Estudiante') ? route('portal.estudiante.asistencia') : route('portal.docente.dashboard') }}"
       style="display:inline-flex;align-items:center;gap:.4rem;margin-top:1.5rem;padding:.55rem 1.25rem;border-radius:10px;background:#0f172a;color:#fff;font-size:.85rem;font-weight:600;text-decoration:none;">
        <i class="bi bi-arrow-left"></i> Ir al portal
    </a>
    @else
    <a href="{{ route('login') }}"
       style="display:inline-flex;align-items:center;gap:.4rem;margin-top:1.5rem;padding:.55rem 1.25rem;border-radius:10px;background:#0f172a;color:#fff;font-size:.85rem;font-weight:600;text-decoration:none;">
        <i class="bi bi-box-arrow-in-right"></i> Iniciar sesión
    </a>
    @endif
</div>
</body>
</html>
