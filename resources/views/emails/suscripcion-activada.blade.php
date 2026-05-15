<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Suscripción Activada — ZuraEdu</title>
</head>
<body style="margin:0;padding:0;background:#f1f5f9;font-family:'Inter',Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0">
<tr><td align="center" style="padding:40px 16px;">
<table width="560" cellpadding="0" cellspacing="0" style="max-width:560px;width:100%;">

  <tr>
    <td style="background:linear-gradient(135deg,#1e3a6e,#2563eb);border-radius:16px 16px 0 0;padding:32px 36px;text-align:center;">
      <div style="font-size:2.5rem;margin-bottom:8px;">🎉</div>
      <h1 style="margin:0;color:#fff;font-size:1.3rem;font-weight:700;">¡Suscripción Activada!</h1>
      <p style="margin:6px 0 0;color:rgba(255,255,255,.75);font-size:.875rem;">ZuraEdu SGE — Plan {{ ucfirst($planSlug) }}</p>
    </td>
  </tr>

  <tr>
    <td style="background:#fff;padding:32px 36px;">
      <p style="margin:0 0 16px;color:#374151;font-size:.95rem;line-height:1.6;">
        Estimado equipo de <strong>{{ $tenant->nombre_institucion }}</strong>,
      </p>
      <p style="margin:0 0 24px;color:#6b7280;font-size:.9rem;line-height:1.6;">
        Tu pago fue procesado exitosamente y tu suscripción al plan <strong>{{ ucfirst($planSlug) }}</strong>
        ({{ $ciclo === 'anual' ? 'anual' : 'mensual' }}) ya está activa. Ahora tienes acceso completo
        a todas las funcionalidades incluidas en tu plan.
      </p>

      <div style="background:#f0fdf4;border:1px solid #86efac;border-radius:10px;padding:20px 24px;margin-bottom:24px;">
        <div style="margin-bottom:10px;">
          <span style="font-size:.85rem;color:#374151;font-weight:600;">Institución:</span>
          <span style="font-size:.85rem;color:#374151;margin-left:8px;">{{ $tenant->nombre_institucion }}</span>
        </div>
        <div style="margin-bottom:10px;">
          <span style="font-size:.85rem;color:#374151;font-weight:600;">Plan activado:</span>
          <span style="font-size:.85rem;font-weight:800;color:#16a34a;margin-left:8px;">{{ ucfirst($planSlug) }}</span>
        </div>
        <div>
          <span style="font-size:.85rem;color:#374151;font-weight:600;">Ciclo de facturación:</span>
          <span style="font-size:.85rem;color:#374151;margin-left:8px;">{{ $ciclo === 'anual' ? 'Anual' : 'Mensual' }}</span>
        </div>
      </div>

      <p style="margin:0 0 24px;color:#6b7280;font-size:.85rem;line-height:1.6;">
        Puedes consultar el historial de tu suscripción y descargar recibos en cualquier momento
        desde el panel de administración, en la sección <strong>Facturación y Suscripción</strong>.
      </p>

      <div style="text-align:center;">
        <a href="{{ url('/admin/billing') }}"
           style="display:inline-block;background:#1e3a6e;color:#fff;text-decoration:none;padding:12px 32px;border-radius:10px;font-weight:700;font-size:.9rem;">
          Ver mi suscripción →
        </a>
      </div>
    </td>
  </tr>

  <tr>
    <td style="background:#f8fafc;border-radius:0 0 16px 16px;padding:20px 36px;text-align:center;border-top:1px solid #e2e8f0;">
      <p style="margin:0 0 4px;font-size:.8rem;color:#6b7280;">
        ¿Necesitas ayuda? Escríbenos a <a href="mailto:soporte@zuraedu.com" style="color:#2563eb;">soporte@zuraedu.com</a>
      </p>
      <p style="margin:0;font-size:.76rem;color:#9ca3af;">
        &copy; {{ date('Y') }} ZuraEdu — Plataforma de Gestión Escolar
      </p>
    </td>
  </tr>

</table>
</td></tr>
</table>
</body>
</html>
