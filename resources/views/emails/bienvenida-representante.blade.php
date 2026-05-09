<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Bienvenido al Portal de Representantes</title>
</head>
<body style="margin:0;padding:0;background:#f1f5f9;font-family:'Inter',Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0">
<tr><td align="center" style="padding:40px 16px;">
<table width="560" cellpadding="0" cellspacing="0" style="max-width:560px;width:100%;">

  {{-- Header --}}
  <tr>
    <td style="background:linear-gradient(135deg,#15803d,#16a34a);border-radius:16px 16px 0 0;padding:32px 36px;text-align:center;">
      <div style="width:52px;height:52px;background:rgba(255,255,255,.2);border-radius:12px;display:inline-flex;align-items:center;justify-content:center;margin-bottom:12px;">
        <span style="color:#fff;font-weight:800;font-size:1.4rem;">✓</span>
      </div>
      <h1 style="margin:0;color:#fff;font-size:1.25rem;font-weight:700;">¡Matrícula Confirmada!</h1>
      <p style="margin:6px 0 0;color:rgba(255,255,255,.75);font-size:.875rem;">{{ config('app.name') }}</p>
    </td>
  </tr>

  {{-- Body --}}
  <tr>
    <td style="background:#fff;padding:32px 36px;">
      <p style="margin:0 0 16px;color:#374151;font-size:.95rem;line-height:1.6;">
        Estimado/a <strong>{{ $representante->nombre_completo }}</strong>,
      </p>
      <p style="margin:0 0 20px;color:#6b7280;font-size:.9rem;line-height:1.7;">
        La matrícula de <strong style="color:#15803d;">{{ $matricula->estudiante->nombre_completo }}</strong>
        ha sido procesada exitosamente.
      </p>

      {{-- Datos matrícula --}}
      <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;padding:20px 24px;margin-bottom:24px;">
        <div style="font-size:.78rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#15803d;margin-bottom:14px;">
          Datos de la Matrícula
        </div>
        <table width="100%" cellpadding="0" cellspacing="0">
          <tr>
            <td style="padding:4px 0;font-size:.85rem;color:#6b7280;width:45%;">Estudiante</td>
            <td style="padding:4px 0;font-size:.85rem;color:#111827;font-weight:600;">{{ $matricula->estudiante->nombre_completo }}</td>
          </tr>
          <tr>
            <td style="padding:4px 0;font-size:.85rem;color:#6b7280;">No. Matrícula</td>
            <td style="padding:4px 0;font-size:.85rem;color:#111827;font-weight:700;font-family:'Courier New',monospace;">{{ $matricula->estudiante->numero_matricula }}</td>
          </tr>
          <tr>
            <td style="padding:4px 0;font-size:.85rem;color:#6b7280;">Grado / Sección</td>
            <td style="padding:4px 0;font-size:.85rem;color:#111827;font-weight:600;">
              {{ $matricula->grupo->grado->nombre ?? '—' }} {{ $matricula->grupo->seccion->nombre ?? '' }}
            </td>
          </tr>
          <tr>
            <td style="padding:4px 0;font-size:.85rem;color:#6b7280;">Año Escolar</td>
            <td style="padding:4px 0;font-size:.85rem;color:#111827;font-weight:600;">{{ $matricula->schoolYear->nombre ?? '—' }}</td>
          </tr>
        </table>
      </div>

      {{-- Credenciales del portal --}}
      <div style="background:#eff6ff;border:2px solid #93c5fd;border-radius:12px;padding:20px 24px;margin-bottom:24px;">
        <div style="font-size:.78rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#1e40af;margin-bottom:14px;">
          Sus Credenciales de Acceso — Portal de Representantes
        </div>
        <table width="100%" cellpadding="0" cellspacing="0">
          <tr>
            <td style="padding:4px 0;font-size:.85rem;color:#6b7280;width:35%;">Usuario / Email</td>
            <td style="padding:4px 0;font-size:.9rem;color:#1e3a8a;font-weight:700;font-family:'Courier New',monospace;">{{ $user->email }}</td>
          </tr>
          <tr>
            <td style="padding:4px 0;font-size:.85rem;color:#6b7280;">Contraseña temporal</td>
            <td style="padding:6px 0;font-size:1.1rem;color:#1e3a8a;font-weight:900;font-family:'Courier New',monospace;letter-spacing:.1em;">{{ $tempPassword }}</td>
          </tr>
        </table>
        <div style="margin-top:14px;">
          <a href="{{ url('/login') }}"
             style="display:inline-block;padding:10px 22px;background:#1d4ed8;color:#fff;border-radius:8px;font-size:.85rem;font-weight:700;text-decoration:none;">
            Acceder al Portal →
          </a>
        </div>
        <p style="margin:12px 0 0;font-size:.76rem;color:#3b82f6;line-height:1.5;">
          Por seguridad, cambie su contraseña la primera vez que acceda al sistema.
        </p>
      </div>

      <p style="margin:0 0 8px;color:#6b7280;font-size:.85rem;line-height:1.7;">
        Desde el portal podrá consultar calificaciones, asistencias, comunicados y más
        información académica de su representado/a.
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
