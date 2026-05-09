<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Alerta Académica</title>
</head>
<body style="margin:0;padding:0;background:#f1f5f9;font-family:'Inter',Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0">
<tr><td align="center" style="padding:40px 16px;">
<table width="560" cellpadding="0" cellspacing="0" style="max-width:560px;width:100%;">

  <tr>
    <td style="background:linear-gradient(135deg,#d97706,#b45309);border-radius:16px 16px 0 0;padding:32px 36px;text-align:center;">
      <h1 style="margin:0;color:#fff;font-size:1.2rem;font-weight:700;">⚠️ Alerta de Rendimiento Académico</h1>
      <p style="margin:6px 0 0;color:rgba(255,255,255,.8);font-size:.875rem;">{{ $inst }}</p>
    </td>
  </tr>

  <tr>
    <td style="background:#fff;padding:32px 36px;">
      <p style="margin:0 0 16px;color:#374151;font-size:.95rem;line-height:1.6;">
        Estimado/a representante,
      </p>
      <p style="margin:0 0 20px;color:#6b7280;font-size:.9rem;line-height:1.6;">
        Le informamos que su representado/a ha obtenido una calificación que requiere atención:
      </p>

      <div style="background:#fffbeb;border:1px solid #fde68a;border-radius:10px;padding:20px 24px;margin-bottom:24px;">
        <table width="100%" cellpadding="0" cellspacing="0">
          <tr>
            <td style="padding:6px 0;font-size:.85rem;color:#374151;font-weight:600;width:50%;">Estudiante:</td>
            <td style="padding:6px 0;font-size:.85rem;color:#374151;font-weight:800;">{{ $estudiante->nombre_completo }}</td>
          </tr>
          <tr>
            <td style="padding:6px 0;font-size:.85rem;color:#374151;font-weight:600;">Asignatura:</td>
            <td style="padding:6px 0;font-size:.85rem;color:#374151;">{{ $asignacion->asignatura?->nombre ?? '—' }}</td>
          </tr>
          <tr>
            <td style="padding:6px 0;font-size:.85rem;color:#374151;font-weight:600;">Calificación:</td>
            <td style="padding:6px 0;font-size:1.1rem;font-weight:900;color:{{ $nota < 60 ? '#dc2626' : '#d97706' }};">{{ $nota }}</td>
          </tr>
        </table>
      </div>

      <p style="margin:0 0 24px;color:#6b7280;font-size:.875rem;line-height:1.6;">
        Le recomendamos comunicarse con el docente de la asignatura o con la coordinación académica
        para acordar estrategias de apoyo y mejora.
      </p>

      <div style="background:#f8fafc;border-radius:10px;padding:16px 20px;font-size:.8rem;color:#9ca3af;text-align:center;">
        Este es un mensaje automático de {{ $inst }}. No responda a este correo.
      </div>
    </td>
  </tr>

  <tr>
    <td style="background:#f8fafc;border-radius:0 0 16px 16px;padding:16px 36px;text-align:center;">
      <p style="margin:0;font-size:.75rem;color:#9ca3af;">{{ $inst }} · Sistema de Gestión Escolar</p>
    </td>
  </tr>

</table>
</td></tr>
</table>
</body>
</html>
