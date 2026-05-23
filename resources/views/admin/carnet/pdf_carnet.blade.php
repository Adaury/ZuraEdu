<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
        font-family: 'DejaVu Sans', sans-serif;
        width: 85.6mm;
        height: 54mm;
        background: #ffffff;
        overflow: hidden;
    }
    @page { margin: 0; size: 85.6mm 54mm landscape; }

    .carnet {
        width: 85.6mm;
        height: 54mm;
        position: relative;
        background: linear-gradient(135deg, #1e3a6e 0%, #1e40af 100%);
        color: #fff;
        border-radius: 3mm;
        overflow: hidden;
    }

    /* Franja superior */
    .header-bar {
        background: rgba(0,0,0,.2);
        padding: 2mm 3mm 1.5mm;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .header-bar .institucion {
        font-size: 5pt;
        font-weight: bold;
        letter-spacing: .05em;
        text-transform: uppercase;
        opacity: .9;
        max-width: 50mm;
    }
    .header-bar .tipo-carnet {
        font-size: 5pt;
        background: rgba(255,255,255,.15);
        padding: 1mm 2mm;
        border-radius: 2mm;
        text-transform: uppercase;
        letter-spacing: .08em;
    }

    /* Cuerpo */
    .body {
        display: flex;
        gap: 0;
        padding: 2mm 3mm;
        flex: 1;
    }
    .foto-col {
        width: 20mm;
        flex-shrink: 0;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 1mm;
    }
    .foto-img {
        width: 18mm;
        height: 18mm;
        border-radius: 50%;
        object-fit: cover;
        border: 1mm solid rgba(255,255,255,.4);
    }
    .foto-placeholder {
        width: 18mm;
        height: 18mm;
        border-radius: 50%;
        background: rgba(255,255,255,.15);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 14pt;
        font-weight: bold;
        color: rgba(255,255,255,.7);
        border: 1mm solid rgba(255,255,255,.2);
    }

    /* Datos */
    .datos-col {
        flex: 1;
        padding-left: 2mm;
        display: flex;
        flex-direction: column;
        justify-content: center;
        gap: .8mm;
    }
    .nombre {
        font-size: 8.5pt;
        font-weight: bold;
        line-height: 1.2;
    }
    .grupo {
        font-size: 6pt;
        opacity: .8;
    }
    .numero-carnet {
        font-size: 6pt;
        font-family: 'Courier New', monospace;
        background: rgba(255,255,255,.1);
        padding: .5mm 1.5mm;
        border-radius: 1mm;
        display: inline-block;
        margin-top: .5mm;
    }

    /* QR */
    .qr-col {
        width: 18mm;
        flex-shrink: 0;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 1mm;
    }
    .qr-img {
        width: 16mm;
        height: 16mm;
        background: #fff;
        padding: .5mm;
        border-radius: 1mm;
    }
    .qr-label {
        font-size: 4.5pt;
        opacity: .6;
        text-align: center;
    }

    /* Footer */
    .footer-bar {
        background: rgba(0,0,0,.25);
        padding: 1mm 3mm;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .footer-bar span { font-size: 5pt; opacity: .7; }
    .footer-bar .vigencia { font-size: 5pt; }

    /* Banda de color */
    .color-band {
        position: absolute;
        left: 0;
        top: 0;
        bottom: 0;
        width: 2mm;
        background: #c0392b;
    }
    .content-wrap {
        margin-left: 2mm;
        height: 100%;
        display: flex;
        flex-direction: column;
    }
</style>
</head>
<body>
<div class="carnet">
    <div class="color-band"></div>
    <div class="content-wrap">
        <div class="header-bar">
            <div class="institucion">ZuraEdu · Sistema Educativo</div>
            <div class="tipo-carnet">{{ strtoupper($carnet->tipo) }}</div>
        </div>

        <div class="body" style="flex:1;">
            <div class="foto-col">
                @if($carnet->user?->foto)
                <img src="{{ storage_path('app/public/'.$carnet->user->foto) }}" class="foto-img" alt="">
                @else
                <div class="foto-placeholder">{{ strtoupper(substr($carnet->nombre_completo,0,1)) }}</div>
                @endif
            </div>

            <div class="datos-col">
                <div class="nombre">{{ $carnet->nombre_completo }}</div>
                @if($carnet->matricula)
                <div class="grupo">
                    {{ $carnet->matricula->grupo?->nombre_completo ?? '' }}
                </div>
                @endif
                <div class="numero-carnet">{{ $carnet->numero_carnet }}</div>
                <div style="font-size:5pt;opacity:.65;margin-top:.5mm;">
                    Vigencia: {{ $carnet->vigencia_hasta?->format('d/m/Y') ?? '—' }}
                </div>
            </div>

            <div class="qr-col">
                @php
                    $qrService = new \App\Services\CarnetQrService();
                    $qrUrl = "https://quickchart.io/qr?text=" . urlencode($qrContent) . "&size=80&margin=1";
                @endphp
                <img src="{{ $qrUrl }}" class="qr-img" alt="QR">
                <div class="qr-label">Escanear</div>
            </div>
        </div>

        <div class="footer-bar">
            <span>ZuraEdu Carnet+</span>
            <span>Estado: {{ ucfirst($carnet->estado) }}</span>
        </div>
    </div>
</div>
</body>
</html>
