<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Pre-matrícula Recibida</title>
</head>
<body style="margin:0;padding:0;background:#f1f5f9;font-family:'Inter',Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0">
<tr><td align="center" style="padding:40px 16px;">
<table width="560" cellpadding="0" cellspacing="0" style="max-width:560px;width:100%;">

  {{-- Header --}}
  <tr>
    <td style="background:linear-gradient(135deg,#2563eb,#1d4ed8);border-radius:16px 16px 0 0;padding:32px 36px;text-align:center;">
      <div style="width:52px;height:52px;background:rgba(255,255,255,.2);border-radius:12px;display:inline-flex;align-items:center;justify-content:center;margin-bottom:12px;">
        <span style="color:#fff;font-weight:800;font-size:1.1rem;">&#10003;</span>
      </div>
      <h1 style="margin:0;color:#fff;font-size:1.25rem;font-weight:700;">Solicitud Recibida</h1>
      <p style="margin:6px 0 0;color:rgba(255,255,255,.75);font-size:.875rem;">{{ config('app.name') }}</p>
    </td>
  </tr>

  {{-- Body --}}
  <tr>
    <td style="background:#fff;padding:32px 36px;">
      <p style="margin:0 0 16px;color:#374151;font-size:.95rem;line-height:1.6;">
        Estimado/a <strong>{{ $preMatricula->nombre_representante }}</strong>,
      </p>
      <p style="margin:0 0 20px;color:#6b7280;font-size:.9rem;line-height:1.7;">
        Hemos recibido correctamente la solicitud de pre-matrícula para el/la estudiante
        <strong style="color:#1d4ed8;">{{ $preMatricula->nombre_completo }}</strong>.
        Le contactaremos pronto para informarle sobre el estado de su solicitud.
      </p>

      {{-- Datos de la solicitud --}}
      <div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:10px;padding:20px 24px;margin-bottom:24px;">
        <div style="font-size:.78rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#1e40af;margin-bottom:14px;">
          Resumen de su solicitud
        </div>

        <table width="100%" cellpadding="0" cellspacing="0">
          <tr>
            <td style="padding:4px 0;font-size:.85rem;color:#6b7280;width:45%;">Estudiante</td>
            <td style="padding:4px 0;font-size:.85rem;color:#111827;font-weight:600;">{{ $preMatricula->nombre_completo }}</td>
          </tr>
          <tr>
            <td style="padding:4px 0;font-size:.85rem;color:#6b7280;">Fecha de nacimiento</td>
            <td style="padding:4px 0;font-size:.85rem;color:#111827;font-weight:600;">{{ $preMatricula->fecha_nacimiento->format('d/m/Y') }}</td>
          </tr>
          <tr>
            <td style="padding:4px 0;font-size:.85rem;color:#6b7280;">Grado solicitado</td>
            <td style="padding:4px 0;font-size:.85rem;color:#111827;font-weight:600;">{{ $preMatricula->grado_solicitado }}</td>
          </tr>
          <tr>
            <td style="padding:4px 0;font-size:.85rem;color:#6b7280;">Representante</td>
            <td style="padding:4px 0;font-size:.85rem;color:#111827;font-weight:600;">{{ $preMatricula->nombre_representante }}</td>
          </tr>
          <tr>
            <td style="padding:4px 0;font-size:.85rem;color:#6b7280;">Cédula representante</td>
            <td style="padding:4px 0;font-size:.85rem;color:#111827;font-weight:600;">{{ $preMatricula->cedula_representante }}</td>
          </tr>
          <tr>
            <td style="padding:4px 0;font-size:.85rem;color:#6b7280;">Teléfono</td>
            <td style="padding:4px 0;font-size:.85rem;color:#111827;font-weight:600;">{{ $preMatricula->telefono }}</td>
          </tr>
          <tr>
            <td style="padding:4px 0;font-size:.85rem;color:#6b7280;">Fecha de solicitud</td>
            <td style="padding:4px 0;font-size:.85rem;color:#111827;font-weight:600;">{{ $preMatricula->created_at->format('d/m/Y H:i') }}</td>
          </tr>
        </table>
      </div>

      <p style="margin:0 0 8px;color:#6b7280;font-size:.85rem;line-height:1.7;">
        Recibirá otro correo cuando su solicitud sea revisada por el personal administrativo.
        Si tiene alguna pregunta, puede contactarnos a través de nuestros canales oficiales.
      </p>
    </td>
  </tr>

  {{-- Footer --}}
  <tr>
    <td style="background:#f8fafc;border-radius:0 0 16px 16px;padding:20px 36px;text-align:center;border-top:1px solid #e2e8f0;">
      <p style="margin:0;font-size:.76rem;color:#9ca3af;">
        {{ config('app.name') }} · Sistema de Gestión Escolar<br>
        Este mensaje fue generado automáticamente. Por favor no responda este correo.
      </p>
    </td>
  </tr>

</table>
</td></tr>
</table>
</body>
</html>
