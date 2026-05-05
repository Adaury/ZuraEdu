<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solicitud Recibida — {{ config('app.name') }}</title>
    <link href="/vendor/bootstrap-icons/bootstrap-icons.min.css" rel="stylesheet">
    <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: 'Inter', -apple-system, 'Segoe UI', sans-serif; background: #f1f5f9; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 2rem 1.25rem; color: #0f172a; }
    .card {
        background: #fff; border-radius: 20px; max-width: 500px; width: 100%;
        box-shadow: 0 8px 40px rgba(15,23,42,.12);
        overflow: hidden; text-align: center;
    }
    .card-top {
        background: linear-gradient(135deg, #059669, #10b981);
        padding: 2.75rem 2rem;
    }
    .icon-circle {
        width: 68px; height: 68px; border-radius: 50%;
        background: rgba(255,255,255,.2); margin: 0 auto 1rem;
        display: flex; align-items: center; justify-content: center;
    }
    .icon-circle i { font-size: 2rem; color: #fff; }
    .card-top h1 { color: #fff; font-size: 1.4rem; font-weight: 800; margin-bottom: .4rem; }
    .card-top p { color: rgba(255,255,255,.78); font-size: .88rem; }
    .card-body { padding: 2rem; }
    .card-body p { color: #6b7280; font-size: .9rem; line-height: 1.75; margin-bottom: 1.5rem; }
    .steps { list-style: none; text-align: left; margin-bottom: 2rem; }
    .steps li { display: flex; align-items: flex-start; gap: .75rem; padding: .6rem 0; border-bottom: 1px solid #f1f5f9; font-size: .88rem; color: #374151; }
    .steps li:last-child { border-bottom: none; }
    .step-num { min-width: 26px; height: 26px; border-radius: 50%; background: #eff6ff; color: #2563eb; font-weight: 800; font-size: .78rem; display: flex; align-items: center; justify-content: center; flex-shrink: 0; margin-top: .05rem; }
    .btn-home {
        display: inline-flex; align-items: center; gap: .5rem;
        width: 100%; justify-content: center;
        padding: .78rem; border-radius: 10px;
        background: linear-gradient(135deg, #1e3a8a, #2563eb);
        color: #fff; text-decoration: none; font-weight: 700; font-size: .95rem;
        transition: all .18s; box-shadow: 0 4px 14px rgba(30,64,175,.3);
    }
    .btn-home:hover { transform: translateY(-1px); box-shadow: 0 8px 22px rgba(30,64,175,.4); }
    </style>
</head>
<body>
    <div class="card">
        <div class="card-top">
            <div class="icon-circle">
                <i class="bi bi-check-lg"></i>
            </div>
            <h1>¡Solicitud Enviada!</h1>
            <p>Su solicitud de pre-matrícula fue recibida exitosamente</p>
        </div>
        <div class="card-body">
            <p>
                Hemos enviado un correo de confirmación con los detalles de su solicitud.
                Le contactaremos pronto para informarle sobre el siguiente paso.
            </p>

            <ul class="steps">
                <li>
                    <div class="step-num">1</div>
                    <span>Revisión de documentos por el personal administrativo</span>
                </li>
                <li>
                    <div class="step-num">2</div>
                    <span>Notificación por correo con la resolución de su solicitud</span>
                </li>
                <li>
                    <div class="step-num">3</div>
                    <span>Si es aprobada, se le indicará la fecha para formalizar la matrícula</span>
                </li>
            </ul>

            <a href="{{ route('landing') }}" class="btn-home">
                <i class="bi bi-house-fill"></i> Volver al inicio
            </a>
        </div>
    </div>
</body>
</html>
