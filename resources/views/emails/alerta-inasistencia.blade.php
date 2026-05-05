<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Alerta de Inasistencias — PSAC</title>
</head>
<body style="margin:0;padding:0;background:#f1f5f9;font-family:'Inter',Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0">
<tr><td align="center" style="padding:40px 16px;">
<table width="560" cellpadding="0" cellspacing="0" style="max-width:560px;width:100%;">

  <tr>
    <td style="background:linear-gradient(135deg,#dc2626,#b91c1c);border-radius:16px 16px 0 0;padding:32px 36px;text-align:center;">
      <h1 style="margin:0;color:#fff;font-size:1.2rem;font-weight:700;">⚠️ Alerta de Inasistencias</h1>
      <p style="margin:6px 0 0;color:rgba(255,255,255,.75);font-size:.875rem;">Se requiere atención inmediata</p>
    </td>
  </tr>

  <tr>
    <td style="background:#fff;padding:32px 36px;">
      <p style="margin:0 0 16px;color:#374151;font-size:.95rem;line-height:1.6;">
        Estimado equipo de coordinación,
      </p>
      <p style="margin:0 0 24px;color:#6b7280;font-size:.9rem;line-height:1.6;">
        El/la estudiante <strong>{{ $estudiante->nombre_completo }}</strong> ha acumulado
        un número crítico de inasistencias en la asignatura
        <strong>{{ $asignacion->asignatura?->nombre ?? '—' }}</strong>.
      </p>

      <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:10px;padding:16px 20px;margin-bottom:24px;">
        <div style="display:flex;justify-content:space-between;margin-bottom:8px;">
          <span style="font-size:.85rem;color:#374151;font-weight:600;">Total de ausencias:</span>
          <span style="font-size:.85rem;font-weight:800;color:#dc2626;">{{ $totalAusencias }}</span>
        </div>
        <div style="display:flex;justify-content:space-between;margin-bottom:8px;">
          <span style="font-size:.85rem;color:#374151;font-weight:600;">% Asistencia:</span>
          <span style="font-size:.85rem;font-weight:800;color:{{ $porcentajeAsistencia < 75 ? '#dc2626' : '#d97706' }};">{{ $porcentajeAsistencia }}%</span>
        </div>
        <div style="display:flex;justify-content:space-between;">
          <span style="font-size:.85rem;color:#374151;font-weight:600;">Asignatura:</span>
          <span style="font-size:.85rem;color:#374151;">{{ $asignacion->asignatura?->nombre }}</span>
        </div>
      </div>

      <div style="text-align:center;">
        <a href="{{ url('/admin/asistencia') }}"
           style="display:inline-block;background:#dc2626;color:#fff;text-decoration:none;padding:12px 32px;border-radius:10px;font-weight:700;font-size:.9rem;">
          Ver registro de asistencia →
        </a>
      </div>
    </td>
  </tr>

  <tr>
    <td style="background:#f8fafc;border-radius:0 0 16px 16px;padding:20px 36px;text-align:center;border-top:1px solid #e2e8f0;">
      <p style="margin:0;font-size:.76rem;color:#9ca3af;">
        &copy; {{ date('Y') }} PSAC — Alerta generada automáticamente por el Sistema SGE
      </p>
    </td>
  </tr>

</table>
</td></tr>
</table>
</body>
</html>
