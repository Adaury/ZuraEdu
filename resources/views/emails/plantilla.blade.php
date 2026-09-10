<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{ $centro }}</title>
</head>
<body style="margin:0;padding:0;background:#f1f5f9;font-family:'Inter',Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0">
<tr><td align="center" style="padding:40px 16px;">
<table width="560" cellpadding="0" cellspacing="0" style="max-width:560px;width:100%;">

  {{-- Header --}}
  <tr>
    <td style="background:linear-gradient(135deg,#1d4ed8,#3b82f6);border-radius:16px 16px 0 0;padding:28px 36px;text-align:center;">
      <h1 style="margin:0;color:#fff;font-size:1.15rem;font-weight:700;">{{ $centro }}</h1>
    </td>
  </tr>

  {{-- Body -- $cuerpo ya viene resuelto (evento personalizado por el
       propio tenant) y saneado (SanitizeInput::$allowedRichFields para el
       campo "cuerpo"); las variables ya fueron sustituidas y escapadas por
       PlantillaComunicacionService::sustituir() antes de llegar aquí. --}}
  <tr>
    <td style="background:#fff;padding:32px 36px;color:#374151;font-size:.95rem;line-height:1.7;">
      {!! $cuerpo !!}
    </td>
  </tr>

  {{-- Footer --}}
  <tr>
    <td style="background:#f8fafc;border-radius:0 0 16px 16px;padding:20px 36px;text-align:center;border-top:1px solid #e2e8f0;">
      <p style="margin:0;font-size:.76rem;color:#9ca3af;">
        {{ $centro }}<br>
        Este mensaje fue generado automáticamente. Por favor no responda este correo.
      </p>
    </td>
  </tr>

</table>
</td></tr>
</table>
</body>
</html>
