<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error del servidor — SGE</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: system-ui, -apple-system, 'Segoe UI', sans-serif;
            background: #f1f5f9;
            display: flex; align-items: center; justify-content: center;
            min-height: 100vh; padding: 1.5rem;
        }
        .card {
            background: #fff; border-radius: 20px;
            border: 1px solid #e5e7eb;
            box-shadow: 0 8px 32px rgba(0,0,0,.08);
            padding: 3rem 2.5rem; max-width: 460px; width: 100%;
            text-align: center;
        }
        .code {
            font-size: 5rem; font-weight: 900;
            background: linear-gradient(135deg, #92400e, #f59e0b);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            line-height: 1; margin-bottom: .5rem;
        }
        .icon { font-size: 2.5rem; margin-bottom: 1rem; }
        h1 { font-size: 1.4rem; font-weight: 800; color: #111827; margin-bottom: .6rem; }
        p  { font-size: .9rem; color: #6b7280; line-height: 1.65; margin-bottom: 2rem; }
        .actions { display: flex; gap: .75rem; justify-content: center; flex-wrap: wrap; }
        .btn {
            display: inline-flex; align-items: center; gap: .4rem;
            padding: .65rem 1.4rem; border-radius: 10px;
            font-weight: 700; font-size: .88rem; text-decoration: none;
            transition: opacity .15s;
        }
        .btn:hover { opacity: .88; }
        .btn-primary   { background: #1e3a8a; color: #fff; }
        .btn-secondary { background: #f1f5f9; color: #374151; border: 1px solid #e5e7eb; }
        .note { font-size: .78rem; color: #9ca3af; margin-top: 1.5rem; }
    </style>
</head>
<body>
    <div class="card">
        <div class="code">500</div>
        <div class="icon">⚠️</div>
        <h1>Error interno del servidor</h1>
        <p>
            Ocurrió un problema inesperado en el servidor.<br>
            El equipo técnico ha sido notificado. Intenta de nuevo en unos momentos.
        </p>
        <div class="actions">
            <a href="javascript:location.reload()" class="btn btn-secondary">↻ Reintentar</a>
            @auth
                @if(auth()->user()->hasRole(['Administrador','Director','Coordinador Academico','Coordinador Primer Ciclo','Coordinador Segundo Ciclo','Secretaria Docente','Secretaria','Personal Administrativo']))
                    <a href="/admin/dashboard" class="btn btn-primary">🏠 Panel Admin</a>
                @elseif(auth()->user()->hasRole('Docente'))
                    <a href="/portal/docente" class="btn btn-primary">🏠 Portal Docente</a>
                @elseif(auth()->user()->hasRole('Estudiante'))
                    <a href="/portal/estudiante" class="btn btn-primary">🏠 Mi Portal</a>
                @elseif(auth()->user()->hasRole('Representante'))
                    <a href="/portal/padre" class="btn btn-primary">🏠 Mi Portal</a>
                @else
                    <a href="/" class="btn btn-primary">🏠 Inicio</a>
                @endif
            @else
                <a href="/login" class="btn btn-primary">Inicio de Sesión</a>
            @endauth
        </div>
        <p class="note">Si el problema persiste, contacta al administrador del sistema.</p>
    </div>
</body>
</html>
