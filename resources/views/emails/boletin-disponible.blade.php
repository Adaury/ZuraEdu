<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Boletín Disponible — PSAC</title>
</head>
<body style="margin:0;padding:0;background:#f1f5f9;font-family:'Inter',Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0">
<tr><td align="center" style="padding:40px 16px;">
<table width="560" cellpadding="0" cellspacing="0" style="max-width:560px;width:100%;">

  <tr>
    <td style="background:linear-gradient(135deg,#047857,#065f46);border-radius:16px 16px 0 0;padding:32px 36px;text-align:center;">
      <h1 style="margin:0;color:#fff;font-size:1.2rem;font-weight:700;">📋 Boletín Disponible</h1>
      <p style="margin:6px 0 0;color:rgba(255,255,255,.75);font-size:.875rem;">{{ $periodo->nombre }}</p>
    </td>
  </tr>

  <tr>
    <td style="background:#fff;padding:32px 36px;">
      <p style="margin:0 0 16px;color:#374151;font-size:.95rem;line-height:1.6;">
        Estimado representante,
      </p>
      <p style="margin:0 0 24px;color:#6b7280;font-size:.9rem;line-height:1.6;">
        El boletín de calificaciones del <strong>{{ $periodo->nombre }}</strong>
        para el/la estudiante <strong>{{ $estudiante->nombre_completo }}</strong>
        ya está disponible para su consulta.
      </p>

      <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;padding:16px 20px;margin-bottom:24px;">
        <div style="font-size:.8rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#15803d;margin-bottom:8px;">Estudiante</div>
        <div style="font-size:.875rem;color:#374151;">{{ $estudiante->nombre_completo }}</div>
        <div style="font-size:.8rem;color:#6b7280;margin-top:4px;">{{ $estudiante->numero_matricula }}</div>
      </div>

      <div style="text-align:center;">
        <a href="{{ $portalUrl }}"
           style="display:inline-block;background:#047857;color:#fff;text-decoration:none;padding:12px 32px;border-radius:10px;font-weight:700;font-size:.9rem;">
          Ver boletín en el Portal →
        </a>
      </div>

      <p style="margin:20px 0 0;font-size:.78rem;color:#9ca3af;text-align:center;">
        El enlace es válido por 30 días desde la fecha de envío.
      </p>
    </td>
  </tr>

  <tr>
    <td style="background:#f8fafc;border-radius:0 0 16px 16px;padding:20px 36px;text-align:center;border-top:1px solid #e2e8f0;">
      <p style="margin:0;font-size:.76rem;color:#9ca3af;">
        &copy; {{ date('Y') }} Politécnico Salesiano Arquides Calderón — Sistema SGE
      </p>
    </td>
  </tr>

</table>
</td></tr>
</table>
</body>
</html>
