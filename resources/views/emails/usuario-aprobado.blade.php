<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Acceso Aprobado — PSAC</title>
</head>
<body style="margin:0;padding:0;background:#f1f5f9;font-family:'Inter',Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0">
<tr><td align="center" style="padding:40px 16px;">
<table width="560" cellpadding="0" cellspacing="0" style="max-width:560px;width:100%;">

  {{-- Header --}}
  <tr>
    <td style="background:linear-gradient(135deg,#2563eb,#1d4ed8);border-radius:16px 16px 0 0;padding:32px 36px;text-align:center;">
      <div style="width:52px;height:52px;background:rgba(255,255,255,.2);border-radius:12px;display:inline-flex;align-items:center;justify-content:center;margin-bottom:12px;">
        <span style="color:#fff;font-weight:800;font-size:1rem;">PSAC</span>
      </div>
      <h1 style="margin:0;color:#fff;font-size:1.3rem;font-weight:700;">¡Tu acceso ha sido aprobado!</h1>
      <p style="margin:6px 0 0;color:rgba(255,255,255,.75);font-size:.875rem;">Politécnico Salesiano Arquides Calderón</p>
    </td>
  </tr>

  {{-- Body --}}
  <tr>
    <td style="background:#fff;padding:32px 36px;">
      <p style="margin:0 0 16px;color:#374151;font-size:.95rem;line-height:1.6;">
        Hola, <strong>{{ $usuario->name }}</strong>.
      </p>
      <p style="margin:0 0 24px;color:#6b7280;font-size:.9rem;line-height:1.6;">
        Tu solicitud de acceso al <strong>Sistema de Gestión Escolar PSAC</strong> ha sido
        <strong style="color:#16a34a;">aprobada</strong>. Ya puedes ingresar al sistema con tu correo y contraseña.
      </p>

      {{-- Info box --}}
      <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;padding:16px 20px;margin-bottom:24px;">
        <div style="font-size:.8rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#15803d;margin-bottom:8px;">Tus datos de acceso</div>
        <div style="font-size:.875rem;color:#374151;margin-bottom:4px;"><strong>Correo:</strong> {{ $usuario->email }}</div>
        <div style="font-size:.875rem;color:#374151;margin-bottom:4px;"><strong>Rol:</strong> {{ $usuario->getRoleNames()->first() ?? 'Usuario' }}</div>
      </div>

      <div style="text-align:center;">
        <a href="{{ url('/login') }}"
           style="display:inline-block;background:#2563eb;color:#fff;text-decoration:none;padding:12px 32px;border-radius:10px;font-weight:700;font-size:.9rem;">
          Ingresar al sistema →
        </a>
      </div>
    </td>
  </tr>

  {{-- Footer --}}
  <tr>
    <td style="background:#f8fafc;border-radius:0 0 16px 16px;padding:20px 36px;text-align:center;border-top:1px solid #e2e8f0;">
      <p style="margin:0;font-size:.76rem;color:#9ca3af;">
        Este correo fue generado automáticamente por el Sistema de Gestión Escolar PSAC.<br>
        Si no solicitaste este acceso, ignora este mensaje.
      </p>
    </td>
  </tr>

</table>
</td></tr>
</table>
</body>
</html>
