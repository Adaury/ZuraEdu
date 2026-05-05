<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Diploma — {{ $reconocimiento->estudiante->nombre_completo }}</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }

@page { size: letter landscape; margin: 0; }

body {
    font-family: 'DejaVu Serif', Georgia, serif;
    background: #fdf8ee;
    color: #1a1a2e;
    width: 100%;
    height: 100%;
}

.page {
    width: 100%;
    min-height: 595px;
    background: #fdf8ee;
    position: relative;
    padding: 32px 40px;
}

/* Borde ornamental exterior */
.border-outer {
    position: absolute;
    top: 14px; left: 14px; right: 14px; bottom: 14px;
    border: 4px solid #b5892b;
    border-radius: 3px;
}

/* Borde ornamental interior */
.border-inner {
    position: absolute;
    top: 22px; left: 22px; right: 22px; bottom: 22px;
    border: 1.5px solid #d4a843;
    border-radius: 2px;
}

/* Esquinas decorativas */
.corner {
    position: absolute;
    width: 28px;
    height: 28px;
    border-color: #b5892b;
}
.corner-tl { top: 28px; left: 28px; border-top: 3px solid; border-left: 3px solid; }
.corner-tr { top: 28px; right: 28px; border-top: 3px solid; border-right: 3px solid; }
.corner-bl { bottom: 28px; left: 28px; border-bottom: 3px solid; border-left: 3px solid; }
.corner-br { bottom: 28px; right: 28px; border-bottom: 3px solid; border-right: 3px solid; }

/* Contenido centrado */
.content {
    position: relative;
    z-index: 1;
    text-align: center;
    padding: 10px 60px 0;
}

/* Logo / institución */
.header-inst {
    margin-bottom: 10px;
}
.logo-img {
    height: 54px;
    display: block;
    margin: 0 auto 6px;
    object-fit: contain;
}
.logo-placeholder {
    width: 54px;
    height: 54px;
    background: #b5892b;
    color: #fff;
    font-family: 'DejaVu Sans', sans-serif;
    font-size: 22px;
    font-weight: 900;
    border-radius: 50%;
    display: inline-block;
    line-height: 54px;
    text-align: center;
    margin-bottom: 6px;
}
.inst-name {
    font-size: 13px;
    font-weight: 700;
    color: #7c5002;
    text-transform: uppercase;
    letter-spacing: .12em;
    font-family: 'DejaVu Sans', sans-serif;
}
.inst-sub {
    font-size: 9px;
    color: #a07830;
    font-family: 'DejaVu Sans', sans-serif;
    letter-spacing: .05em;
}

/* Separador dorado */
.divider {
    border: none;
    border-top: 1.5px solid #d4a843;
    margin: 8px auto;
    width: 340px;
}
.divider-thin {
    border-top: .5px solid #e8c96a;
    margin: 4px auto;
    width: 280px;
}

/* Título DIPLOMA */
.diploma-label {
    font-size: 32px;
    font-weight: 900;
    color: #7c5002;
    letter-spacing: .18em;
    text-transform: uppercase;
    margin: 10px 0 4px;
    text-shadow: 1px 1px 0 #f0d080;
}

/* Texto "Se otorga..." */
.otorga-text {
    font-size: 11px;
    color: #555;
    font-style: italic;
    margin-bottom: 6px;
    font-family: 'DejaVu Sans', sans-serif;
}

/* Nombre del estudiante */
.student-name {
    font-size: 26px;
    font-weight: 700;
    color: #1a1a2e;
    letter-spacing: .05em;
    border-bottom: 2px solid #b5892b;
    display: inline-block;
    padding: 0 20px 4px;
    margin-bottom: 10px;
}

/* Tipo de reconocimiento */
.tipo-badge {
    display: inline-block;
    background: #7c5002;
    color: #fdf8ee;
    font-size: 13px;
    font-weight: 700;
    letter-spacing: .1em;
    text-transform: uppercase;
    padding: 5px 28px;
    border-radius: 2px;
    margin-bottom: 10px;
    font-family: 'DejaVu Sans', sans-serif;
}

/* Descripción */
.descripcion {
    font-size: 11px;
    color: #374151;
    line-height: 1.7;
    max-width: 580px;
    margin: 0 auto 14px;
    font-style: italic;
    font-family: 'DejaVu Sans', sans-serif;
}

/* Sección de firmas */
.firmas-section {
    display: flex;
    justify-content: center;
    gap: 60px;
    margin-top: 14px;
    align-items: flex-end;
}
.firma-col {
    text-align: center;
    width: 150px;
}
.firma-line {
    border-top: 1px solid #7c5002;
    padding-top: 5px;
    font-size: 9px;
    font-family: 'DejaVu Sans', sans-serif;
    color: #374151;
}
.firma-name {
    font-weight: 700;
    font-size: 10px;
    color: #1a1a2e;
    font-family: 'DejaVu Sans', sans-serif;
}
.firma-cargo {
    font-size: 8px;
    color: #6b7280;
    font-family: 'DejaVu Sans', sans-serif;
    text-transform: uppercase;
    letter-spacing: .06em;
}

/* Sello circular */
.sello {
    width: 72px;
    height: 72px;
    border: 2px dashed #b5892b;
    border-radius: 50%;
    display: inline-block;
    line-height: 72px;
    font-size: 9px;
    color: #d4a843;
    font-family: 'DejaVu Sans', sans-serif;
    font-weight: 700;
    letter-spacing: .05em;
    text-align: center;
    margin-bottom: 2px;
}

/* Pie de página */
.footer-bar {
    margin-top: 14px;
    border-top: .5px solid #e8c96a;
    padding-top: 5px;
    display: flex;
    justify-content: space-between;
    font-size: 7.5px;
    color: #a07830;
    font-family: 'DejaVu Sans', sans-serif;
}

/* Motivo ornamental lateral (solo decorativo) */
.ornament-left, .ornament-right {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    font-size: 32px;
    color: #e8c96a;
    z-index: 0;
    opacity: .5;
}
.ornament-left  { left: 38px; }
.ornament-right { right: 38px; }
</style>
</head>
<body>
<div class="page">

    {{-- Bordes ornamentales --}}
    <div class="border-outer"></div>
    <div class="border-inner"></div>

    {{-- Esquinas decorativas --}}
    <div class="corner corner-tl"></div>
    <div class="corner corner-tr"></div>
    <div class="corner corner-bl"></div>
    <div class="corner corner-br"></div>

    {{-- Ornamentos laterales --}}
    <div class="ornament-left">✦</div>
    <div class="ornament-right">✦</div>

    {{-- Contenido principal --}}
    <div class="content">

        {{-- Encabezado institucional --}}
        <div class="header-inst">
            @if($logoUrl)
                <img src="{{ $logoUrl }}" class="logo-img" alt="Logo">
            @else
                <div class="logo-placeholder">{{ strtoupper(substr($inst, 0, 1)) }}</div>
            @endif
            <div class="inst-name">{{ $inst }}</div>
            @if($cod)
            <div class="inst-sub">Código: {{ $cod }}</div>
            @endif
        </div>

        <hr class="divider">
        <hr class="divider-thin">

        {{-- Título DIPLOMA --}}
        <div class="diploma-label">Diploma</div>

        {{-- Se otorga a --}}
        <div class="otorga-text">Se otorga el presente diploma a:</div>

        {{-- Nombre del estudiante --}}
        <div class="student-name">{{ strtoupper($reconocimiento->estudiante->nombre_completo) }}</div>

        {{-- Tipo de reconocimiento --}}
        <div>
            <span class="tipo-badge">{{ $reconocimiento->tipo->icono }} {{ strtoupper($reconocimiento->tipo->nombre) }}</span>
        </div>

        {{-- Descripción / motivo --}}
        @if($reconocimiento->titulo || $reconocimiento->descripcion)
        <p class="descripcion">
            "{{ $reconocimiento->descripcion ?: $reconocimiento->titulo }}"
        </p>
        @endif

        <hr class="divider-thin">

        {{-- Fecha --}}
        <p style="font-size:10px;color:#7c5002;margin:6px 0;font-family:'DejaVu Sans',sans-serif;font-style:italic;">
            Dado en {{ $inst }}, a los {{ $reconocimiento->fecha->format('d') }} días del mes de
            {{ $reconocimiento->fecha->translatedFormat('F') }} de {{ $reconocimiento->fecha->format('Y') }}.
        </p>

        {{-- Firmas --}}
        <div class="firmas-section">
            <div class="firma-col">
                <div style="height:28px;"></div>
                <div class="firma-line">
                    <div class="firma-name">{{ $dir ?: 'Director/a del Centro' }}</div>
                    <div class="firma-cargo">Director/a</div>
                </div>
            </div>

            <div style="text-align:center;">
                <div class="sello">SELLO</div>
            </div>

            <div class="firma-col">
                <div style="height:28px;"></div>
                <div class="firma-line">
                    <div class="firma-name">{{ $reconocimiento->emitidoPor->name }}</div>
                    <div class="firma-cargo">Emitido por</div>
                </div>
            </div>
        </div>

        {{-- Pie --}}
        <div class="footer-bar">
            <span>{{ $inst }} — Diploma Oficial</span>
            <span>Emitido: {{ now()->format('d/m/Y') }}</span>
        </div>

    </div>{{-- /content --}}

</div>{{-- /page --}}
</body>
</html>
