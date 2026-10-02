{{--
    Cara del carnet de identidad (CR80, 85.6 × 53.98 mm) para dompdf. Se usa en el PDF individual y en el masivo.
    dompdf NO soporta display:flex, gradientes ni rgba(): se usan tablas y colores sólidos (antes el texto blanco quedaba sobre fondo blanco).
    Variables: $carnet (con user, matricula.grupo) y opcional $qrContent.
--}}
@php
    $qrContent = $qrContent ?? \App\Services\CarnetQrService::qrContent($carnet);
    $qrUrl = \App\Services\CarnetQrService::qrDataUri($qrContent);
    $fotoRuta = $carnet->user?->foto ? storage_path('app/public/' . $carnet->user->foto) : null;
    $fotoRuta = ($fotoRuta && is_file($fotoRuta)) ? $fotoRuta : null;
@endphp
<div style="width:85.6mm;height:53.5mm;background:#1e3a6e;color:#ffffff;border-left:2mm solid #c0392b;overflow:hidden;position:relative;font-family:'DejaVu Sans',sans-serif;">
    {{-- Encabezado: logo de ZuraEdu + tipo de carnet --}}
    <table style="width:100%;background:#16305f;border-collapse:collapse;" cellspacing="0" cellpadding="0">
        <tr>
            <td style="padding:1.6mm 3mm;vertical-align:middle;">
                <img src="{{ \App\Support\Marca::logoDataUri('png-blanco') }}" alt="ZuraEdu" style="height:4.5mm;width:auto;">
            </td>
            <td style="padding:1.6mm 3mm;vertical-align:middle;text-align:right;font-size:5pt;letter-spacing:.08em;color:#cfe0ff;">{{ strtoupper($carnet->tipo) }}</td>
        </tr>
    </table>

    {{-- Cuerpo: foto · datos · QR --}}
    <table style="width:100%;height:36.5mm;border-collapse:collapse;" cellspacing="0" cellpadding="0">
        <tr>
            <td style="width:22mm;padding:4mm 0 0 3mm;vertical-align:middle;">
                @if($fotoRuta)
                    <img src="{{ $fotoRuta }}" alt="" style="width:18mm;height:18mm;border-radius:10mm;border:.6mm solid #7e9bd6;">
                @else
                    <div style="width:18mm;height:12.4mm;padding-top:5.6mm;line-height:1;border-radius:10mm;background:#2f4f8a;border:.6mm solid #7e9bd6;text-align:center;font-size:14pt;font-weight:bold;color:#cfe0ff;">{{ strtoupper(mb_substr($carnet->nombre_completo, 0, 1)) }}</div>
                @endif
            </td>
            <td style="vertical-align:middle;padding:4mm 1mm 0 1mm;">
                <div style="font-size:8pt;font-weight:bold;line-height:1.2;">{{ $carnet->nombre_completo }}</div>
                @if($carnet->matricula)
                    <div style="font-size:6pt;color:#cfe0ff;margin-top:.8mm;">{{ $carnet->matricula->grupo?->nombre_completo ?? '' }}</div>
                @endif
                <div style="font-size:6pt;font-family:courier;margin-top:1mm;color:#ffffff;">{{ $carnet->numero_carnet }}</div>
                <div style="font-size:5pt;color:#b6c9ee;margin-top:.8mm;">Vigencia: {{ $carnet->vigencia_hasta?->format('d/m/Y') ?? '—' }}</div>
            </td>
            <td style="width:20mm;padding:4mm 2.5mm 0 0;vertical-align:middle;text-align:center;">
                <img src="{{ $qrUrl }}" alt="QR" style="width:16mm;height:16mm;background:#ffffff;padding:.5mm;">
                <div style="font-size:4.5pt;color:#b6c9ee;margin-top:.5mm;">Escanear</div>
            </td>
        </tr>
    </table>

    {{-- Pie (en el flujo: un pie absoluto dentro de la tarjeta no se pinta en dompdf) --}}
    <table style="width:100%;background:#16305f;border-collapse:collapse;" cellspacing="0" cellpadding="0">
        <tr>
            <td style="padding:1mm 3mm;font-size:5pt;color:#b6c9ee;">ZuraEdu Carnet+</td>
            <td style="padding:1mm 3mm;font-size:5pt;color:#b6c9ee;text-align:right;">Estado: {{ ucfirst($carnet->estado) }}</td>
        </tr>
    </table>
</div>
