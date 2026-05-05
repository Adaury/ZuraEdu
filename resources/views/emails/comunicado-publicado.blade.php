<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{ $comunicado->titulo }}</title>
</head>
<body style="margin:0;padding:0;background:#f1f5f9;font-family:'Inter',Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0">
<tr><td align="center" style="padding:40px 16px;">
<table width="560" cellpadding="0" cellspacing="0" style="max-width:560px;width:100%;">

  <tr>
    <td style="background:linear-gradient(135deg,#1d4ed8,#3b82f6);border-radius:16px 16px 0 0;padding:32px 36px;text-align:center;">
      <h1 style="margin:0;color:#fff;font-size:1.1rem;font-weight:700;">📢 Comunicado</h1>
      <p style="margin:6px 0 0;color:rgba(255,255,255,.8);font-size:.875rem;">
        {{ config('app.name') }}
      </p>
    </td>
  </tr>

  <tr>
    <td style="background:#fff;padding:32px 36px;">
      <h2 style="margin:0 0 16px;color:#1e293b;font-size:1.05rem;font-weight:700;">
        {{ $comunicado->titulo }}
      </h2>
      <div style="color:#374151;font-size:.9rem;line-height:1.7;border-left:3px solid #3b82f6;padding-left:14px;margin-bottom:24px;">
        {!! nl2br(e(\Illuminate\Support\Str::limit(strip_tags($comunicado->cuerpo), 600))) !!}
      </div>
      @if($comunicado->published_at)
      <p style="color:#9ca3af;font-size:.8rem;margin:0 0 24px;">
        Publicado el {{ \Carbon\Carbon::parse($comunicado->published_at)->format('d/m/Y') }}
        @if($comunicado->autor) por {{ $comunicado->autor->name }} @endif
      </p>
      @endif
      <table cellpadding="0" cellspacing="0">
        <tr>
          <td>
            <a href="{{ url('/login') }}"
               style="display:inline-block;background:#1d4ed8;color:#fff;text-decoration:none;
                      padding:12px 28px;border-radius:10px;font-weight:700;font-size:.9rem;">
              Ver en el Portal
            </a>
          </td>
        </tr>
      </table>
    </td>
  </tr>

  <tr>
    <td style="background:#f8fafc;border-radius:0 0 16px 16px;padding:18px 36px;text-align:center;">
      <p style="margin:0;color:#9ca3af;font-size:.78rem;">
        {{ config('app.name') }} · Sistema de Gestión Escolar<br>
        Recibes este correo porque estás vinculado al sistema como representante o usuario activo.
      </p>
    </td>
  </tr>

</table>
</td></tr>
</table>
</body>
</html>
