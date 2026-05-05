<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redirigiendo al pago seguro…</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #F8FAFC;
            font-family: system-ui, -apple-system, sans-serif;
        }
        .card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 24px rgba(0,0,0,.08);
            padding: 2.5rem 2rem;
            max-width: 400px;
            width: 100%;
            text-align: center;
        }
        .spinner {
            width: 48px; height: 48px;
            border: 4px solid #E2E8F0;
            border-top-color: #2563EB;
            border-radius: 50%;
            animation: spin .8s linear infinite;
            margin: 0 auto 1.25rem;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
        h2 { font-size: 1.1rem; font-weight: 700; color: #1E293B; margin-bottom: .5rem; }
        p  { font-size: .875rem; color: #64748B; line-height: 1.5; }
        .badge {
            display: inline-flex; align-items: center; gap: .4rem;
            margin-top: 1.5rem; padding: .4rem .85rem;
            background: #EFF6FF; border-radius: 999px;
            font-size: .78rem; font-weight: 600; color: #2563EB;
        }
        .lock { font-size: .9rem; }
    </style>
</head>
<body>
    <div class="card">
        <div class="spinner"></div>
        <h2>Redirigiendo al pago seguro</h2>
        <p>Serás transferido en segundos a la plataforma de pago segura de <strong>CardNet</strong>.</p>
        <p style="margin-top:.5rem;">No cierres esta ventana.</p>
        <span class="badge"><span class="lock">🔒</span> Pago cifrado con SSL</span>
    </div>

    {{-- Auto-submit form oculto --}}
    <form id="cardnetForm" method="POST" action="{{ $url }}" style="display:none;">
        @foreach($params as $name => $value)
            <input type="hidden" name="{{ $name }}" value="{{ $value }}">
        @endforeach
    </form>

    <script>
        // Enviamos el form tras un breve delay para que el usuario vea la pantalla
        setTimeout(function () {
            document.getElementById('cardnetForm').submit();
        }, 800);
    </script>
</body>
</html>
