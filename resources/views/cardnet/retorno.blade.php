<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $approved ? 'Pago procesado' : 'Pago no completado' }}</title>
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
            max-width: 420px;
            width: 100%;
            text-align: center;
        }
        .icon { font-size: 3rem; margin-bottom: 1rem; }
        h2 { font-size: 1.2rem; font-weight: 700; margin-bottom: .5rem; }
        p  { font-size: .875rem; color: #64748B; line-height: 1.6; }
        .btn {
            display: inline-block; margin-top: 1.5rem;
            padding: .65rem 1.5rem;
            background: #2563EB; color: #fff;
            border-radius: 10px; text-decoration: none;
            font-size: .9rem; font-weight: 600;
            transition: background .15s;
        }
        .btn:hover { background: #1D4ED8; }
        .btn-outline {
            background: transparent; color: #64748B;
            border: 1.5px solid #CBD5E1; margin-left: .5rem;
        }
        .btn-outline:hover { background: #F1F5F9; }
        .ref { margin-top: .75rem; font-size: .78rem; color: #94A3B8; }
    </style>
</head>
<body>
    <div class="card">
        @if($approved)
            <div class="icon">✅</div>
            <h2 style="color:#16A34A;">¡Pago en proceso!</h2>
            <p>
                Tu pago fue recibido por CardNet y está siendo confirmado.<br>
                Recibirás una notificación cuando se acredite en el sistema.
            </p>
            @if($orderId)
                <p class="ref">Referencia: {{ $orderId }}</p>
            @endif
            <div>
                <a href="{{ route('portal.estudiante.dashboard') ?? '/' }}" class="btn">Ir al portal</a>
            </div>
        @else
            <div class="icon">❌</div>
            <h2 style="color:#DC2626;">Pago no completado</h2>
            <p>
                Tu pago no pudo procesarse.<br>
                @if($code)
                    Código de respuesta: <strong>{{ $code }}</strong>.<br>
                @endif
                Puedes intentarlo nuevamente o contactar a la institución.
            </p>
            <div>
                <a href="javascript:history.back()" class="btn btn-outline">Volver</a>
                <a href="{{ route('portal.estudiante.dashboard') ?? '/' }}" class="btn">Ir al portal</a>
            </div>
        @endif
    </div>
</body>
</html>
