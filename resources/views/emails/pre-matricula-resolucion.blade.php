<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Resolución Pre-matrícula</title>
</head>
<body style="margin:0;padding:0;background:#f1f5f9;font-family:'Inter',Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0">
<tr><td align="center" style="padding:40px 16px;">
<table width="560" cellpadding="0" cellspacing="0" style="max-width:560px;width:100%;">

  @php
    $aprobada    = $preMatricula->estado === 'aprobada';
    $colorTop    = $aprobada ? 'linear-gradient(135deg,#059669,#10b981)' : 'linear-gradient(135deg,#dc2626,#ef4444)';
    $icono       = $aprobada ? '✅' : '❌';
    $titulo      = $aprobada ? '¡Solicitud Aprobada!' : 'Solicitud No Aprobada';
    $colorBox    = $aprobada ? '#f0fdf4' : '#fef2f2';
    $colorBorder = $aprobada ? '#bbf7d0' : '#fecaca';
    $colorLabel  = $aprobada ? '#15803d' : '#991b1b';
  @endphp

  {{-- Header --}}
  <tr>
    <td style="background:{{ $colorTop }};border-radius:16px 16px 0 0;padding:32px 36px;text-align:center;">
      <div style="font-size:2.2rem;margin-bottom:8px;">{{ $icono }}</div>
      <h1 style="margin:0;color:#fff;font-size:1.25rem;font-weight:700;">{{ $titulo }}</h1>
      <p style="margin:6px 0 0;color:rgba(255,255,255,.78);font-size:.875rem;">{{ config('app.name') }}</p>
    </td>
  </tr>

  {{-- Body --}}
  <tr>
    <td style="background:#fff;padding:32px 36px;">
      <p style="margin:0 0 16px;color:#374151;font-size:.95rem;line-height:1.6;">
        Estimado/a <strong>{{ $preMatricula->nombre_representante }}</strong>,
      </p>

      @if($aprobada)
      <p style="margin:0 0 20px;color:#6b7280;font-size:.9rem;line-height:1.7;">
        Nos complace informarle que la solicitud de pre-matrícula para el/la estudiante
        <strong style="color:#059669;">{{ $preMatricula->nombre_completo }}</strong>
        ha sido <strong style="color:#059669;">aprobada</strong>.
        Por favor acérquese a la institución para completar el proceso de matrícula formal.
      </p>
      @else
      <p style="margin:0 0 20px;color:#6b7280;font-size:.9rem;line-height:1.7;">
        Lamentamos informarle que la solicitud de pre-matrícula para el/la estudiante
        <strong style="color:#dc2626;">{{ $preMatricula->nombre_completo }}</strong>
        <strong style="color:#dc2626;">no ha podido ser aprobada</strong> en este momento.
      </p>
      @endif

      {{-- Datos --}}
      <div style="background:{{ $colorBox }};border:1px solid {{ $colorBorder }};border-radius:10px;padding:20px 24px;margin-bottom:24px;">
        <div style="font-size:.78rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:{{ $colorLabel }};margin-bottom:14px;">
          Detalle de la solicitud
        </div>
        <table width="100%" cellpadding="0" cellspacing="0">
          <tr>
            <td style="padding:4px 0;font-size:.85rem;color:#6b7280;width:45%;">Estudiante</td>
            <td style="padding:4px 0;font-size:.85rem;color:#111827;font-weight:600;">{{ $preMatricula->nombre_completo }}</td>
          </tr>
          <tr>
            <td style="padding:4px 0;font-size:.85rem;color:#6b7280;">Grado solicitado</td>
            <td style="padding:4px 0;font-size:.85rem;color:#111827;font-weight:600;">{{ $preMatricula->grado_solicitado }}</td>
          </tr>
          <tr>
            <td style="padding:4px 0;font-size:.85rem;color:#6b7280;">Estado</td>
            <td style="padding:4px 0;font-size:.85rem;font-weight:700;color:{{ $aprobada ? '#059669' : '#dc2626' }};">
              {{ ucfirst($preMatricula->estado) }}
            </td>
          </tr>
        </table>

        @if($preMatricula->notas_admin)
        <div style="margin-top:14px;padding-top:14px;border-top:1px solid {{ $colorBorder }};">
          <div style="font-size:.78rem;font-weight:700;color:{{ $colorLabel }};margin-bottom:6px;">Observaciones</div>
          <p style="margin:0;font-size:.85rem;color:#374151;line-height:1.6;">{{ $preMatricula->notas_admin }}</p>
        </div>
        @endif
      </div>

      <p style="margin:0;color:#6b7280;font-size:.85rem;line-height:1.7;">
        Para más información comuníquese con la institución.
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
