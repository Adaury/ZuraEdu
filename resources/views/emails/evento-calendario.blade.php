<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{ $evento->titulo }}</title>
</head>
<body style="margin:0;padding:0;background:#f1f5f9;font-family:'Inter',Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0">
<tr><td align="center" style="padding:40px 16px;">
<table width="560" cellpadding="0" cellspacing="0" style="max-width:560px;width:100%;">

  <tr>
    <td style="background:linear-gradient(135deg,#1d4ed8,#3b82f6);border-radius:16px 16px 0 0;padding:28px 36px;text-align:center;">
      <h1 style="margin:0;color:#fff;font-size:1.1rem;font-weight:700;">
        {{ $actualizacion ? '🔄 Evento actualizado' : '📅 Nuevo evento en el calendario' }}
      </h1>
      <p style="margin:6px 0 0;color:rgba(255,255,255,.8);font-size:.875rem;">{{ $institucion }}</p>
    </td>
  </tr>

  <tr>
    <td style="background:#fff;padding:32px 36px;">
      <p style="margin:0 0 14px;color:#374151;font-size:.9rem;">Hola {{ $nombre }},</p>
      <h2 style="margin:0 0 12px;color:#1e293b;font-size:1.05rem;font-weight:700;">{{ $evento->titulo }}</h2>

      <table cellpadding="0" cellspacing="0" style="margin:0 0 18px;font-size:.9rem;color:#374151;">
        <tr><td style="padding:2px 12px 2px 0;color:#6b7280;">Tipo</td><td>{{ $tipoLabel }}</td></tr>
        <tr>
          <td style="padding:2px 12px 2px 0;color:#6b7280;">Fecha</td>
          <td>
            {{ $evento->fecha_inicio->format('d/m/Y') }}@if($evento->fecha_fin && ! $evento->fecha_fin->isSameDay($evento->fecha_inicio)) al {{ $evento->fecha_fin->format('d/m/Y') }}@endif
          </td>
        </tr>
        @unless($intervalo['todoElDia'])
        <tr><td style="padding:2px 12px 2px 0;color:#6b7280;">Hora</td><td>{{ $intervalo['inicio']->format('h:i A') }}</td></tr>
        @endunless
      </table>

      @if($evento->descripcion)
      <div style="color:#374151;font-size:.9rem;line-height:1.7;border-left:3px solid #3b82f6;padding-left:14px;margin-bottom:24px;">
        {!! nl2br(e($evento->descripcion)) !!}
      </div>
      @endif

      <table cellpadding="0" cellspacing="0" style="margin-bottom:16px;">
        <tr>
          <td style="padding-right:10px;">
            <a href="{{ $googleUrl }}"
               style="display:inline-block;background:#1d4ed8;color:#fff;text-decoration:none;padding:12px 22px;border-radius:10px;font-weight:600;font-size:.875rem;">
              Agregar a Google Calendar
            </a>
          </td>
        </tr>
      </table>
      <p style="margin:0;color:#6b7280;font-size:.8rem;line-height:1.6;">
        Agregarlo es opcional. También puedes abrir el archivo <strong>.ics</strong> adjunto para guardarlo en Google Calendar, Outlook o el calendario de tu teléfono.
        También lo encuentras en tu portal, en <em>Mensajes</em> y en <em>Calendario</em>.
      </p>
    </td>
  </tr>

  <tr>
    <td style="background:#f8fafc;border-radius:0 0 16px 16px;padding:18px 36px;text-align:center;color:#9ca3af;font-size:.75rem;">
      {{ $institucion }} · Este es un aviso automático.
    </td>
  </tr>
</table>
</td></tr>
</table>
</body>
</html>
