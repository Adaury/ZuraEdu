<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Certificado — {{ $estudiante->nombre_completo }}</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }

@page { size: letter landscape; margin: 0; }

body {
    font-family: 'DejaVu Serif', Georgia, serif;
    background: #f8f6f0;
    color: #1c1c2e;
    width: 100%;
    height: 100%;
}

/* ── Página ── */
.page {
    width: 100%;
    min-height: 595px;
    background: #f8f6f0;
    position: relative;
    padding: 36px 44px 28px;
}

/* ── Bordes ornamentales ── */
.border-outer {
    position: absolute;
    top: 12px; left: 12px; right: 12px; bottom: 12px;
    border: 4px double #2c5282;
    border-radius: 2px;
}
.border-inner {
    position: absolute;
    top: 20px; left: 20px; right: 20px; bottom: 20px;
    border: 1.5px solid #4a90d9;
    border-radius: 1px;
}

/* ── Esquinas decorativas ── */
.corner {
    position: absolute;
    width: 26px;
    height: 26px;
    border-color: #2c5282;
}
.corner-tl { top: 26px; left: 26px; border-top: 3px solid; border-left: 3px solid; }
.corner-tr { top: 26px; right: 26px; border-top: 3px solid; border-right: 3px solid; }
.corner-bl { bottom: 26px; left: 26px; border-bottom: 3px solid; border-left: 3px solid; }
.corner-br { bottom: 26px; right: 26px; border-bottom: 3px solid; border-right: 3px solid; }

/* ── Ornamentos laterales ── */
.ornament-left, .ornament-right {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    font-size: 28px;
    color: #90cdf4;
    z-index: 0;
    opacity: .6;
}
.ornament-left  { left: 34px; }
.ornament-right { right: 34px; }

/* ── Franja superior azul ── */
.header-band {
    background: #2c5282;
    color: #fff;
    text-align: center;
    padding: 8px 60px;
    margin-bottom: 12px;
    border-radius: 1px;
    position: relative;
    z-index: 1;
}
.header-band .cert-title {
    font-size: 22px;
    font-weight: 900;
    letter-spacing: .22em;
    text-transform: uppercase;
    font-family: 'DejaVu Sans', sans-serif;
}
.header-band .cert-sub {
    font-size: 9px;
    letter-spacing: .12em;
    color: #bee3f8;
    font-family: 'DejaVu Sans', sans-serif;
    text-transform: uppercase;
    margin-top: 2px;
}

/* ── Contenido ── */
.content {
    position: relative;
    z-index: 1;
    text-align: center;
    padding: 0 60px;
}

/* ── Encabezado institucional ── */
.header-inst {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
    margin-bottom: 10px;
}
.logo-img {
    height: 46px;
    object-fit: contain;
}
.logo-placeholder {
    width: 46px;
    height: 46px;
    background: #2c5282;
    color: #fff;
    font-family: 'DejaVu Sans', sans-serif;
    font-size: 20px;
    font-weight: 900;
    border-radius: 50%;
    display: inline-block;
    line-height: 46px;
    text-align: center;
}
.inst-info { text-align: left; }
.inst-name {
    font-size: 13px;
    font-weight: 700;
    color: #1a365d;
    text-transform: uppercase;
    letter-spacing: .1em;
    font-family: 'DejaVu Sans', sans-serif;
}
.inst-sub {
    font-size: 8.5px;
    color: #4a5568;
    font-family: 'DejaVu Sans', sans-serif;
    letter-spacing: .04em;
}

/* ── Divider ── */
.divider {
    border: none;
    border-top: 2px solid #2c5282;
    margin: 8px auto;
    width: 360px;
}
.divider-thin {
    border-top: .75px solid #4a90d9;
    margin: 5px auto;
    width: 300px;
}

/* ── Texto "Se certifica" ── */
.certifica-text {
    font-size: 11px;
    color: #4a5568;
    font-style: italic;
    margin-bottom: 4px;
    font-family: 'DejaVu Sans', sans-serif;
}

/* ── Nombre del estudiante ── */
.student-name {
    font-size: 28px;
    font-weight: 700;
    color: #1a365d;
    letter-spacing: .04em;
    border-bottom: 2px solid #2c5282;
    display: inline-block;
    padding: 0 24px 5px;
    margin-bottom: 10px;
}

/* ── Badge de rol ── */
.rol-badge {
    display: inline-block;
    background: #2c5282;
    color: #fff;
    font-size: 10px;
    font-weight: 700;
    letter-spacing: .1em;
    text-transform: uppercase;
    padding: 4px 20px;
    border-radius: 2px;
    margin-bottom: 10px;
    font-family: 'DejaVu Sans', sans-serif;
}

/* ── Texto del certificado ── */
.cert-body {
    font-size: 11.5px;
    color: #374151;
    line-height: 1.8;
    max-width: 600px;
    margin: 0 auto 10px;
    font-family: 'DejaVu Sans', sans-serif;
}
.cert-body strong {
    color: #1a365d;
    font-weight: 700;
}

/* ── Datos del proyecto ── */
.proyecto-info {
    display: inline-block;
    background: #ebf8ff;
    border: 1px solid #bee3f8;
    border-radius: 4px;
    padding: 8px 24px;
    margin: 8px 0 12px;
    font-family: 'DejaVu Sans', sans-serif;
}
.proyecto-info table {
    border-collapse: collapse;
    font-size: 9.5px;
    color: #2d3748;
}
.proyecto-info td {
    padding: 2px 8px;
}
.proyecto-info td:first-child {
    font-weight: 700;
    color: #1a365d;
    text-align: right;
    white-space: nowrap;
}

/* ── Fecha ── */
.fecha-line {
    font-size: 10px;
    color: #4a5568;
    font-style: italic;
    font-family: 'DejaVu Sans', sans-serif;
    margin: 4px 0 10px;
}

/* ── Firmas ── */
.firmas-section {
    display: flex;
    justify-content: center;
    gap: 60px;
    margin-top: 10px;
    align-items: flex-end;
}
.firma-col {
    text-align: center;
    width: 155px;
}
.firma-line {
    border-top: 1px solid #2c5282;
    padding-top: 5px;
    font-size: 9px;
    font-family: 'DejaVu Sans', sans-serif;
    color: #374151;
}
.firma-name {
    font-weight: 700;
    font-size: 9.5px;
    color: #1a365d;
    font-family: 'DejaVu Sans', sans-serif;
}
.firma-cargo {
    font-size: 7.5px;
    color: #718096;
    font-family: 'DejaVu Sans', sans-serif;
    text-transform: uppercase;
    letter-spacing: .07em;
}

/* ── Sello ── */
.sello {
    width: 68px;
    height: 68px;
    border: 2px dashed #2c5282;
    border-radius: 50%;
    display: inline-block;
    line-height: 68px;
    font-size: 8.5px;
    color: #2c5282;
    font-family: 'DejaVu Sans', sans-serif;
    font-weight: 700;
    letter-spacing: .04em;
    text-align: center;
    margin-bottom: 3px;
}

/* ── Pie ── */
.footer-bar {
    margin-top: 12px;
    border-top: .5px solid #bee3f8;
    padding-top: 5px;
    display: flex;
    justify-content: space-between;
    font-size: 7.5px;
    color: #718096;
    font-family: 'DejaVu Sans', sans-serif;
    position: relative;
    z-index: 1;
}
</style>
</head>
<body>
<div class="page">

    {{-- Bordes ornamentales --}}
    <div class="border-outer"></div>
    <div class="border-inner"></div>
    <div class="corner corner-tl"></div>
    <div class="corner corner-tr"></div>
    <div class="corner corner-bl"></div>
    <div class="corner corner-br"></div>
    <div class="ornament-left">&#10022;</div>
    <div class="ornament-right">&#10022;</div>

    {{-- Encabezado institucional --}}
    <div class="content" style="margin-bottom:0;">
        <div class="header-inst">
            @if($logoUrl)
                <img src="{{ $logoUrl }}" class="logo-img" alt="Logo">
            @else
                <div class="logo-placeholder">{{ strtoupper(substr($inst, 0, 1)) }}</div>
            @endif
            <div class="inst-info">
                <div class="inst-name">{{ $inst }}</div>
                @if($cod)
                <div class="inst-sub">Código: {{ $cod }}</div>
                @endif
            </div>
        </div>
    </div>

    {{-- Franja título --}}
    <div class="header-band">
        <div class="cert-title">Certificado de Participación</div>
        <div class="cert-sub">Proyectos Escolares — Año Académico {{ $proyecto->schoolYear?->nombre }}</div>
    </div>

    {{-- Contenido principal --}}
    <div class="content">

        <div class="certifica-text">La dirección del centro educativo certifica que:</div>

        <div class="student-name">{{ strtoupper($estudiante->nombre_completo) }}</div>

        <div>
            <span class="rol-badge">
                {{ $integrante->rol === 'lider' ? '★ Líder del Proyecto' : 'Integrante del Proyecto' }}
            </span>
        </div>

        <p class="cert-body">
            participó activamente en el proyecto escolar titulado
            <strong>"{{ $proyecto->titulo }}"</strong>,
            correspondiente al área de <strong>{{ $proyecto->area_label }}</strong>,
            llevado a cabo durante el año escolar <strong>{{ $proyecto->schoolYear?->nombre }}</strong>,
            bajo la tutoría de <strong>{{ $proyecto->tutor->name }}</strong>.
        </p>

        {{-- Datos del proyecto --}}
        <div class="proyecto-info">
            <table>
                <tr>
                    <td>Proyecto:</td>
                    <td>{{ $proyecto->titulo }}</td>
                </tr>
                <tr>
                    <td>Área:</td>
                    <td>{{ $proyecto->area_label }}</td>
                </tr>
                <tr>
                    <td>Estado:</td>
                    <td>{{ $proyecto->estado_label }}</td>
                </tr>
                <tr>
                    <td>Período:</td>
                    <td>
                        {{ $proyecto->fecha_inicio->format('d/m/Y') }}
                        @if($proyecto->fecha_fin) — {{ $proyecto->fecha_fin->format('d/m/Y') }} @endif
                    </td>
                </tr>
                <tr>
                    <td>Tutor:</td>
                    <td>{{ $proyecto->tutor->name }}</td>
                </tr>
            </table>
        </div>

        <hr class="divider-thin">

        <p class="fecha-line">
            Dado en {{ $inst }}, a los {{ now()->format('d') }} días del mes de
            {{ now()->translatedFormat('F') }} del año {{ now()->format('Y') }}.
        </p>

        {{-- Firmas --}}
        <div class="firmas-section">
            <div class="firma-col">
                <div style="height: 26px;"></div>
                <div class="firma-line">
                    <div class="firma-name">{{ $dir ?: 'Director/a del Centro' }}</div>
                    <div class="firma-cargo">Director/a</div>
                </div>
            </div>

            <div style="text-align:center;">
                <div class="sello">SELLO OFICIAL</div>
            </div>

            <div class="firma-col">
                <div style="height: 26px;"></div>
                <div class="firma-line">
                    <div class="firma-name">{{ $proyecto->tutor->name }}</div>
                    <div class="firma-cargo">Tutor del Proyecto</div>
                </div>
            </div>
        </div>

    </div>{{-- /content --}}

    {{-- Pie --}}
    <div class="footer-bar">
        <span>{{ $inst }} — Certificado Oficial de Participación</span>
        <span>Proyectos Escolares • {{ $proyecto->area_label }}</span>
        <span>Emitido: {{ now()->format('d/m/Y') }}</span>
    </div>

</div>{{-- /page --}}
</body>
</html>
