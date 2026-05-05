<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sesión expirada — SGE</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: system-ui, -apple-system, sans-serif;
            background: #f8fafc;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 1.5rem;
        }
        .card {
            background: #fff;
            border-radius: 16px;
            border: 1px solid #e5e7eb;
            box-shadow: 0 4px 20px rgba(0,0,0,.08);
            padding: 2.5rem;
            max-width: 420px;
            width: 100%;
            text-align: center;
        }
        .icon { font-size: 3rem; margin-bottom: 1rem; }
        h1 { font-size: 1.3rem; font-weight: 800; color: #111827; margin-bottom: .5rem; }
        p  { font-size: .88rem; color: #6b7280; line-height: 1.6; margin-bottom: 1.5rem; }
        .btn {
            display: inline-block;
            background: #1e3a6e;
            color: #fff;
            padding: .65rem 1.75rem;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 700;
            font-size: .9rem;
        }
        .countdown { font-size: .78rem; color: #9ca3af; margin-top: .75rem; }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon">⏱️</div>
        <h1>Sesión expirada</h1>
        <p>
            La página estuvo inactiva demasiado tiempo y el token de seguridad venció.<br>
            Serás redirigido automáticamente en <strong id="cnt">5</strong> segundos.
        </p>
        <a href="javascript:history.back()" class="btn" onclick="window.location.reload(); return false;">
            Volver y reintentar
        </a>
        <div class="countdown">O recarga la página para continuar.</div>
    </div>
    <script>
        let n = 5;
        const el = document.getElementById('cnt');
        const timer = setInterval(() => {
            n--;
            el.textContent = n;
            if (n <= 0) {
                clearInterval(timer);
                // Go back and reload to get fresh CSRF
                if (window.history.length > 1) {
                    window.history.back();
                    setTimeout(() => window.location.reload(), 300);
                } else {
                    window.location.reload();
                }
            }
        }, 1000);
    </script>
</body>
</html>
