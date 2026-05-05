<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Servicio Suspendido — ZuraEdu</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body { background: #f1f5f9; min-height: 100vh; display: flex; align-items: center; justify-content: center; font-family: 'Inter', system-ui, sans-serif; }
        .card { border-radius: 20px; border: none; box-shadow: 0 10px 40px rgba(0,0,0,.08); max-width: 480px; width: 100%; }
        .icon-wrap { width: 72px; height: 72px; border-radius: 50%; background: #fee2e2; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.25rem; }
    </style>
</head>
<body>
<div class="card p-5 text-center mx-3">
    <div class="icon-wrap">
        <i class="bi bi-lock-fill text-danger" style="font-size: 1.8rem;"></i>
    </div>

    <h4 class="fw-bold mb-2">Servicio suspendido</h4>
    <p class="text-muted mb-4" style="font-size:.93rem; line-height:1.6;">
        El acceso a esta institución ha sido <strong>suspendido temporalmente</strong>
        por falta de pago o por decisión administrativa.<br><br>
        Para reactivar el servicio, comuníquese con el soporte de <strong>ZuraEdu</strong>.
    </p>

    <div class="d-flex flex-column gap-2">
        <a href="mailto:soporte@zuraedu.com" class="btn btn-danger">
            <i class="bi bi-envelope-fill me-2"></i>soporte@zuraedu.com
        </a>
        <a href="https://wa.me/18097657070" target="_blank" class="btn btn-outline-success">
            <i class="bi bi-whatsapp me-2"></i>WhatsApp de soporte
        </a>
        <a href="{{ url('/') }}" class="btn btn-link text-muted btn-sm mt-1">
            Volver al inicio
        </a>
    </div>

    <hr class="my-4">
    <p class="text-muted" style="font-size:.75rem;">
        <strong>ZuraEdu</strong> — Sistema de Gestión Escolar<br>
        Error 503 · Servicio no disponible
    </p>
</div>
</body>
</html>
