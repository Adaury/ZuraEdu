<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Recordatorio de Pagos</title>
</head>
<body style="margin:0;padding:0;background:#f1f5f9;font-family:'Inter',Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0">
<tr><td align="center" style="padding:40px 16px;">
<table width="560" cellpadding="0" cellspacing="0" style="max-width:560px;width:100%;">

  <tr>
    <td style="background:linear-gradient(135deg,#dc2626,#ef4444);border-radius:16px 16px 0 0;padding:32px 36px;text-align:center;">
      <h1 style="margin:0;color:#fff;font-size:1.1rem;font-weight:700;">⚠️ Recordatorio de Pagos</h1>
      <p style="margin:6px 0 0;color:rgba(255,255,255,.8);font-size:.875rem;">{{ $si }}</p>
    </td>
  </tr>

  <tr>
    <td style="background:#fff;padding:32px 36px;">
      <p style="margin:0 0 12px;color:#374151;font-size:.95rem;line-height:1.6;">
        Estimado/a <strong>{{ $rep->nombres ?? '' }} {{ $rep->apellidos ?? '' }}</strong>,
      </p>
      <p style="margin:0 0 24px;color:#6b7280;font-size:.9rem;line-height:1.6;">
        Le informamos que el/la estudiante
        <strong>{{ $estudiante?->nombre_completo ?? $estudiante?->nombres . ' ' . $estudiante?->apellidos }}</strong>
        tiene pagos vencidos pendientes de regularización.
      </p>

      {{-- Tabla de pagos --}}
      <table width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #e5e7eb;border-radius:10px;overflow:hidden;margin-bottom:24px;">
        <tr style="background:#fee2e2;">
          <th style="padding:10px 14px;font-size:.8rem;text-align:left;color:#991b1b;font-weight:700;">Concepto</th>
          <th style="padding:10px 14px;font-size:.8rem;text-align:right;color:#991b1b;font-weight:700;">Monto</th>
          <th style="padding:10px 14px;font-size:.8rem;text-align:center;color:#991b1b;font-weight:700;">Vencimiento</th>
        </tr>
        @foreach($pagos->take(5) as $pago)
        <tr style="border-top:1px solid #f3f4f6;">
          <td style="padding:8px 14px;font-size:.85rem;color:#374151;">{{ $pago->concepto }}</td>
          <td style="padding:8px 14px;font-size:.85rem;font-weight:700;text-align:right;color:#dc2626;">RD$ {{ number_format($pago->monto,2) }}</td>
          <td style="padding:8px 14px;font-size:.8rem;text-align:center;color:#6b7280;">{{ \Carbon\Carbon::parse($pago->fecha_vencimiento)->format('d/m/Y') }}</td>
        </tr>
        @endforeach
        <tr style="background:#fef2f2;border-top:2px solid #fca5a5;">
          <td style="padding:10px 14px;font-weight:700;font-size:.9rem;color:#991b1b;">Total Pendiente</td>
          <td style="padding:10px 14px;font-weight:800;font-size:.95rem;text-align:right;color:#991b1b;" colspan="2">RD$ {{ number_format($totalDeuda,2) }}</td>
        </tr>
      </table>

      <p style="margin:0 0 24px;color:#6b7280;font-size:.85rem;line-height:1.6;">
        Le solicitamos regularizar esta situación a la brevedad posible. Para cualquier consulta o acuerdo de pago, comuníquese con la administración del centro.
      </p>

      <table cellpadding="0" cellspacing="0">
        <tr>
          <td>
            <a href="{{ url('/login') }}"
               style="display:inline-block;background:#dc2626;color:#fff;text-decoration:none;
                      padding:12px 28px;border-radius:10px;font-weight:700;font-size:.9rem;">
              Ver mi cuenta
            </a>
          </td>
        </tr>
      </table>
    </td>
  </tr>

  <tr>
    <td style="background:#f8fafc;border-radius:0 0 16px 16px;padding:18px 36px;text-align:center;">
      <p style="margin:0;color:#9ca3af;font-size:.78rem;">
        {{ $si }} · Sistema de Gestión Escolar<br>
        Este mensaje fue generado automáticamente. Por favor no responda este correo.
      </p>
    </td>
  </tr>

</table>
</td></tr>
</table>
</body>
</html>
