<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
* { box-sizing:border-box; margin:0; padding:0; }
body { font-family: DejaVu Sans, sans-serif; font-size:9px; color:#1e293b; }
@page { size:letter portrait; margin:1cm 1.2cm; }

.header { text-align:center; margin-bottom:18px; }
.header .inst  { font-size:14px; font-weight:bold; color:#1e3a6e; text-transform:uppercase; letter-spacing:.04em; }
.header .tipo  { font-size:9px; color:#6b7280; margin-top:3px; }
.header .titulo{ font-size:11px; font-weight:bold; color:#0f172a; margin-top:8px; border-top:2px solid #1e3a6e; border-bottom:1px solid #e2e8f0; padding:5px 0; }
.header .sub   { font-size:8px; color:#9ca3af; margin-top:4px; }

.section-title { font-size:8px; font-weight:700; text-transform:uppercase; letter-spacing:.06em; color:#1e3a6e;
                 border-bottom:1.5px solid #1e3a6e; padding-bottom:3px; margin:14px 0 8px; }

.info-grid { width:100%; border-collapse:collapse; margin-bottom:4px; }
.info-grid td { padding:4px 6px; font-size:8.5px; border-bottom:1px solid #f0f4f8; vertical-align:top; }
.info-grid td.lbl { font-weight:700; color:#374151; width:130px; background:#f8faff; }
.info-grid td.val { color:#1e293b; }

.stats-row { display:table; width:100%; border-collapse:separate; border-spacing:8px; margin:0 -8px; }
.stat-cell { display:table-cell; background:#f8faff; border:1px solid #e2e8f0; border-radius:6px; padding:8px 10px; text-align:center; vertical-align:middle; width:20%; }
.stat-val { font-size:20px; font-weight:900; color:#1e3a6e; line-height:1; }
.stat-lbl { font-size:7px; color:#6b7280; text-transform:uppercase; letter-spacing:.04em; margin-top:2px; }

.sy-box { background:#eff6ff; border:1px solid #bfdbfe; border-radius:6px; padding:8px 12px; margin:10px 0; }
.sy-box .sy-name { font-size:11px; font-weight:900; color:#1e3a6e; }
.sy-box .sy-sub  { font-size:7.5px; color:#3b82f6; margin-top:2px; }

.footer { margin-top:16px; border-top:1px solid #e2e8f0; padding-top:6px;
          display:table; width:100%; font-size:7px; color:#94a3b8; }
.footer-l { display:table-cell; }
.footer-r { display:table-cell; text-align:right; }

.seal-row { text-align:center; margin-top:30px; }
.seal-line { display:inline-block; width:180px; border-top:1px solid #374151; padding-top:4px; font-size:7.5px; color:#374151; }
</style>
</head>
<body>

<div class="header">
    <div class="inst">{{ $cfg['nombre'] }}</div>
    @if($cfg['modalidad'])
    <div class="tipo">Modalidad: {{ $cfg['modalidad'] }}</div>
    @endif
    <div class="titulo">FICHA INSTITUCIONAL</div>
    <div class="sub">Generado: {{ now()->format('d/m/Y H:i') }}</div>
</div>

{{-- Año Escolar Activo --}}
@if($sy)
<div class="sy-box">
    <div class="sy-name">Año Escolar: {{ $sy->nombre }}</div>
    <div class="sy-sub">
        {{ $sy->fecha_inicio ? \Carbon\Carbon::parse($sy->fecha_inicio)->format('d/m/Y') : '' }}
        @if($sy->fecha_inicio && $sy->fecha_fin) — @endif
        {{ $sy->fecha_fin ? \Carbon\Carbon::parse($sy->fecha_fin)->format('d/m/Y') : '' }}
        &nbsp; · Estado: {{ $sy->activo ? 'Activo' : 'Inactivo' }}
    </div>
</div>
@endif

{{-- Datos del Centro --}}
<div class="section-title">Datos del Centro Educativo</div>
<table class="info-grid">
    <tr>
        <td class="lbl">Nombre del Centro</td>
        <td class="val">{{ $cfg['nombre'] }}</td>
    </tr>
    @if($cfg['codigo'])
    <tr>
        <td class="lbl">Código del Centro</td>
        <td class="val">{{ $cfg['codigo'] }}</td>
    </tr>
    @endif
    @if($cfg['director'])
    <tr>
        <td class="lbl">Director(a)</td>
        <td class="val">{{ $cfg['director'] }}</td>
    </tr>
    @endif
    @if($cfg['modalidad'])
    <tr>
        <td class="lbl">Modalidad</td>
        <td class="val">{{ $cfg['modalidad'] }}</td>
    </tr>
    @endif
    @if($cfg['sector'])
    <tr>
        <td class="lbl">Sector</td>
        <td class="val">{{ $cfg['sector'] }}</td>
    </tr>
    @endif
</table>

{{-- Contacto y Ubicación --}}
@if($cfg['telefono'] || $cfg['email'] || $cfg['direccion'])
<div class="section-title">Contacto y Ubicación</div>
<table class="info-grid">
    @if($cfg['telefono'])
    <tr>
        <td class="lbl">Teléfono</td>
        <td class="val">{{ $cfg['telefono'] }}</td>
    </tr>
    @endif
    @if($cfg['email'])
    <tr>
        <td class="lbl">Correo Electrónico</td>
        <td class="val">{{ $cfg['email'] }}</td>
    </tr>
    @endif
    @if($cfg['direccion'])
    <tr>
        <td class="lbl">Dirección</td>
        <td class="val">{{ $cfg['direccion'] }}</td>
    </tr>
    @endif
    @if($cfg['municipio'] || $cfg['provincia'])
    <tr>
        <td class="lbl">Municipio / Provincia</td>
        <td class="val">{{ implode(', ', array_filter([$cfg['municipio'], $cfg['provincia']])) }}</td>
    </tr>
    @endif
</table>
@endif

{{-- Estadísticas --}}
<div class="section-title">Estadísticas del Año Escolar Actual</div>
<div class="stats-row">
    <div class="stat-cell">
        <div class="stat-val">{{ number_format($stats['matriculas']) }}</div>
        <div class="stat-lbl">Matrículas</div>
    </div>
    <div class="stat-cell">
        <div class="stat-val">{{ number_format($stats['estudiantes']) }}</div>
        <div class="stat-lbl">Estudiantes</div>
    </div>
    <div class="stat-cell">
        <div class="stat-val">{{ number_format($stats['docentes']) }}</div>
        <div class="stat-lbl">Docentes</div>
    </div>
    <div class="stat-cell">
        <div class="stat-val">{{ number_format($stats['grupos']) }}</div>
        <div class="stat-lbl">Grupos</div>
    </div>
    <div class="stat-cell">
        <div class="stat-val">{{ number_format($stats['asignaciones']) }}</div>
        <div class="stat-lbl">Asignaciones</div>
    </div>
</div>

{{-- Firma --}}
<div class="seal-row">
    <div class="seal-line">
        {{ $cfg['director'] ?: '____________________________' }}<br>
        Director(a)
    </div>
</div>

<div class="footer">
    <div class="footer-l">{{ $cfg['nombre'] }} — Ficha Institucional</div>
    <div class="footer-r">{{ now()->format('d/m/Y H:i') }}</div>
</div>
</body>
</html>
