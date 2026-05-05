<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Servicio suspendido</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body {
            background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Inter', system-ui, sans-serif;
            padding: 2rem 1rem;
        }
        .card {
            border-radius: 20px;
            border: none;
            box-shadow: 0 25px 60px rgba(0,0,0,.4);
            max-width: 500px;
            width: 100%;
            background: #fff;
        }
        .icon-circle {
            width: 80px; height: 80px;
            border-radius: 50%;
            background: #fee2e2;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 1.5rem;
        }
        .tenant-logo {
            width: 52px; height: 52px;
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            color: #fff;
            font-size: 1.2rem;
            font-weight: 900;
        }
        .contact-btn { border-radius: 12px; padding: .65rem 1.25rem; font-weight: 600; }
    </style>
</head>
<body>
<div class="card p-5">

    {{-- Logo del tenant (si está disponible) --}}
    @if(isset($currentTenant) && $currentTenant)
    <div class="d-flex align-items-center justify-content-center gap-3 mb-4">
        @if($currentTenant->logo_url)
            <img src="{{ $currentTenant->logo_url }}" style="height:40px;border-radius:8px;" alt="">
        @else
            <div class="tenant-logo" style="background:{{ $currentTenant->color_primario ?? '#6366f1' }};">
                {{ strtoupper(substr($currentTenant->nombre_institucion, 0, 2)) }}
            </div>
        @endif
        <span class="fw-bold" style="font-size:1rem;color:#1e293b;">
            {{ $currentTenant->nombre_institucion }}
        </span>
    </div>
    @endif

    <div class="icon-circle">
        <i class="bi bi-lock-fill text-danger" style="font-size: 2rem;"></i>
    </div>

    <div class="text-center">
        <h4 class="fw-bold mb-2" style="color:#1e293b;">Acceso suspendido</h4>
        <p class="text-muted mb-4" style="font-size:.93rem;line-height:1.7;">
            El acceso a este sistema ha sido <strong>suspendido temporalmente</strong>.
            Esto puede deberse a un pago pendiente o a una decisión administrativa.
        </p>

        <div class="rounded-3 p-3 mb-4 text-start" style="background:#f8fafc;border:1px solid #e2e8f0;">
            <p class="fw-semibold small mb-2" style="color:#475569;">Para reactivar el servicio:</p>
            <ul class="mb-0 small" style="color:#64748b;padding-left:1.2rem;line-height:2;">
                <li>Verifica que el pago de la suscripción esté al día</li>
                <li>Contacta al equipo de soporte de ZuraEdu</li>
                <li>El acceso se activa automáticamente al registrar el pago</li>
            </ul>
        </div>

        <div class="d-flex flex-column gap-2">
            <a href="mailto:soporte@zuraedu.com" class="btn btn-danger contact-btn w-100">
                <i class="bi bi-envelope-fill me-2"></i>soporte@zuraedu.com
            </a>
            <a href="https://wa.me/18097657070?text={{ urlencode('Hola, necesito reactivar el servicio' . (isset($currentTenant) ? ' de '.$currentTenant->nombre_institucion : '')) }}"
               target="_blank" class="btn btn-success contact-btn w-100">
                <i class="bi bi-whatsapp me-2"></i>WhatsApp de soporte
            </a>
        </div>

        <hr class="my-4">

        <div class="d-flex justify-content-between">
            <a href="{{ url('/') }}" class="btn btn-link btn-sm text-muted p-0">
                <i class="bi bi-house me-1"></i>Inicio
            </a>
            <a href="{{ route('login') }}" class="btn btn-link btn-sm text-muted p-0">
                <i class="bi bi-box-arrow-in-right me-1"></i>Iniciar sesión
            </a>
        </div>
    </div>

    <p class="text-center text-muted mt-4 mb-0" style="font-size:.72rem;">
        ZuraEdu SaaS · Error de acceso por estado de cuenta
    </p>
</div>
</body>
</html>
