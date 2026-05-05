<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Recordatorio SGE</title>
<style>
    body { font-family: Arial, Helvetica, sans-serif; background: #f4f6f8; margin: 0; padding: 0; }
    .wrapper { max-width: 600px; margin: 32px auto; background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 16px rgba(0,0,0,.08); }
    .header { background: linear-gradient(135deg, #0f766e, #14b8a6); padding: 28px 32px; text-align: center; }
    .header h1 { color: #fff; margin: 0; font-size: 1.4rem; }
    .header p  { color: rgba(255,255,255,.85); margin: 6px 0 0; font-size: .9rem; }
    .body { padding: 28px 32px; color: #1e293b; }
    .body p { line-height: 1.65; margin: 0 0 14px; font-size: .92rem; }
    .alert-box { background: #fffbeb; border-left: 4px solid #f59e0b; border-radius: 6px; padding: 14px 18px; margin-bottom: 20px; }
    .alert-box.danger { background: #fee2e2; border-color: #ef4444; }
    .alert-box .label { font-size: .75rem; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: #92400e; margin-bottom: 4px; }
    .alert-box.danger .label { color: #991b1b; }
    .alert-box .value { font-size: 1.05rem; font-weight: 700; color: #1e293b; }
    .detail-row { display: flex; gap: 12px; margin-bottom: 10px; align-items: flex-start; }
    .detail-row .dot { width: 8px; height: 8px; border-radius: 50%; background: #0f766e; margin-top: 6px; flex-shrink: 0; }
    .footer { background: #f8fafc; padding: 18px 32px; text-align: center; color: #94a3b8; font-size: .78rem; border-top: 1px solid #e2e8f0; }
    .btn { display: inline-block; background: #0f766e; color: #fff; text-decoration: none; padding: 10px 24px; border-radius: 8px; font-weight: 700; font-size: .88rem; margin-top: 8px; }
</style>
</head>
<body>
<div class="wrapper">
    <div class="header">
        <h1>&#128276; Recordatorio de Entrega de Notas</h1>
        <p>Sistema de Gestión Escolar — {{ config('app.name') }}</p>
    </div>
    <div class="body">
        <p>Estimado/a <strong>{{ $destinatario->name }}</strong>,</p>

        <p>Le recordamos que tiene una fecha de entrega de notas próxima:</p>

        <div class="alert-box {{ $diasRestantes === 0 ? 'danger' : '' }}">
            <div class="label">{{ $diasRestantes === 0 ? '⚠ VENCE HOY' : "Vence en {$diasRestantes} día(s)" }}</div>
            <div class="value">{{ $evento->titulo }}</div>
        </div>

        <div class="detail-row"><div class="dot"></div><div>
            <strong>Fecha límite:</strong> {{ \Carbon\Carbon::parse($evento->fecha_inicio)->format('d/m/Y') }}
        </div></div>
        @if($evento->descripcion)
        <div class="detail-row"><div class="dot"></div><div>
            <strong>Descripción:</strong> {{ $evento->descripcion }}
        </div></div>
        @endif

        <p style="margin-top:20px;">
            Por favor, asegúrese de ingresar y publicar todas sus calificaciones antes de la fecha límite.
            Si ya las ha publicado, puede ignorar este mensaje.
        </p>

        <p style="text-align:center;">
            <a href="{{ url('/admin/calificaciones') }}" class="btn">Ir a Calificaciones</a>
        </p>
    </div>
    <div class="footer">
        Este mensaje fue enviado automáticamente por el SGE. Por favor no responda a este correo.<br>
        &copy; {{ date('Y') }} {{ config('app.name') }}
    </div>
</div>
</body>
</html>
