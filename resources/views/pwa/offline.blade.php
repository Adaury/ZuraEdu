<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sin conexión — ZuraEdu</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: #f1f5f9;
            color: #1e293b;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
        }

        .card {
            background: #fff;
            border-radius: 1.25rem;
            padding: 3rem 2.5rem;
            text-align: center;
            max-width: 400px;
            width: 100%;
            box-shadow: 0 4px 24px rgba(0,0,0,.08);
        }

        .icon {
            font-size: 4rem;
            margin-bottom: 1.25rem;
            display: block;
        }

        h1 {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: .5rem;
        }

        p {
            color: #64748b;
            font-size: .9375rem;
            line-height: 1.6;
            margin-bottom: 2rem;
        }

        .btn {
            display: inline-block;
            background: #1d4ed8;
            color: #fff;
            text-decoration: none;
            padding: .75rem 2rem;
            border-radius: .625rem;
            font-weight: 600;
            font-size: .9375rem;
            border: none;
            cursor: pointer;
            transition: background .15s;
        }
        .btn:hover { background: #1e40af; }

        .tips {
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid #e2e8f0;
            font-size: .8125rem;
            color: #94a3b8;
        }
    </style>
</head>
<body>
    <div class="card">
        <span class="icon">📡</span>
        <h1>Sin conexión</h1>
        <p>
            No hay conexión a Internet en este momento.<br>
            Revisa tu red Wi-Fi o datos móviles e inténtalo de nuevo.
        </p>
        <button class="btn" onclick="window.location.reload()">
            Reintentar
        </button>
        <div class="tips">
            Las páginas visitadas recientemente pueden estar disponibles sin conexión.
        </div>
    </div>
</body>
</html>
