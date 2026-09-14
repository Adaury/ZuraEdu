<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: DejaVu Sans, sans-serif; font-size: 9.5px; color: #1e293b; }

.header { text-align: center; margin-bottom: 14px; border-bottom: 2px solid #1d4ed8; padding-bottom: 10px; }
.header .inst  { font-size: 12px; font-weight: bold; color: #1d4ed8; text-transform: uppercase; }
.header .titulo{ font-size: 12px; font-weight: bold; color: #0f172a; margin-top: 5px; }
.header .sub   { font-size: 8.5px; color: #6b7280; margin-top: 3px; }

.meta-grid { display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 12px; }
.meta-cell { flex: 1; min-width: 90px; background: #f0f4ff; border: 1px solid #c7d2fe; border-radius: 4px; padding: 5px 8px; }
.meta-cell .lbl { font-size: 7px; font-weight: 700; text-transform: uppercase; color: #6b7280; }
.meta-cell .linea { border-bottom: 1px dotted #94a3b8; height: 12px; margin-top: 2px; }

.intencion { background: #fffbeb; border-left: 3px solid #f59e0b; padding: 8px 10px;
             margin-bottom: 10px; border-radius: 0 4px 4px 0; }
.intencion .lbl { font-weight: 700; color: #92400e; font-size: 8.5px; margin-bottom: 4px; }
.lineas-escribir .linea { border-bottom: 1px dotted #94a3b8; height: 13px; }

.momento { border: 1px solid #e2e8f0; border-radius: 5px; margin-bottom: 8px; overflow: hidden; }
.momento-header { padding: 5px 10px; font-weight: 700; font-size: 9px; }
.momento-inicio     .momento-header { background: #dcfce7; color: #15803d; }
.momento-desarrollo .momento-header { background: #dbeafe; color: #1d4ed8; }
.momento-cierre     .momento-header { background: #fef9c3; color: #92400e; }
.momento-body { padding: 7px 10px; }

.campo-row { margin-bottom: 6px; }
.campo-lbl { font-size: 7.5px; font-weight: 700; text-transform: uppercase; color: #94a3b8; margin-bottom: 2px; }

.estrategias-lista { display: flex; flex-wrap: wrap; gap: 4px 12px; margin-top: 4px; }
.estrategia-item { font-size: 8px; color: #475569; width: 46%; }
.casilla { display: inline-block; width: 8px; height: 8px; border: 1px solid #94a3b8; margin-right: 4px; vertical-align: middle; }

.footer { margin-top: 14px; border-top: 1px solid #e2e8f0; padding-top: 7px;
          display: flex; justify-content: space-between; font-size: 7.5px; color: #94a3b8; }
.firma-row { display: flex; gap: 24px; margin-top: 24px; }
.firma-box { flex: 1; text-align: center; border-top: 1px solid #94a3b8; padding-top: 5px;
             font-size: 8px; color: #475569; margin-top: 22px; }
</style>
</head>
<body>

<div class="header">
    <div class="inst">{{ $inst }}</div>
    <div class="titulo">PLANTILLA — PLAN DE CLASE</div>
    <div class="sub">
        {{ $asignacion->asignatura->nombre ?? '' }}
        &nbsp;·&nbsp; {{ $asignacion->grupo->nombre_completo ?? '' }}
        &nbsp;·&nbsp; Docente: {{ $docente->nombre_completo ?? '' }}
    </div>
</div>

{{-- Metadata en blanco --}}
<div class="meta-grid">
    <div class="meta-cell"><div class="lbl">Título</div><div class="linea"></div></div>
    <div class="meta-cell"><div class="lbl">Tipo (diaria/semanal/quincenal/mensual)</div><div class="linea"></div></div>
    <div class="meta-cell"><div class="lbl">Semana</div><div class="linea"></div></div>
    <div class="meta-cell"><div class="lbl">Fecha inicio</div><div class="linea"></div></div>
    <div class="meta-cell"><div class="lbl">Fecha fin</div><div class="linea"></div></div>
</div>

{{-- Intención pedagógica --}}
<div class="intencion">
    <div class="lbl">Intención Pedagógica</div>
    <div class="lineas-escribir">
        <div class="linea"></div>
        <div class="linea"></div>
    </div>
</div>

{{-- Estrategias --}}
<div style="margin-bottom:10px;">
    <div style="font-size:8px;font-weight:700;text-transform:uppercase;color:#94a3b8;margin-bottom:4px;">
        Estrategias (marcar las que apliquen)
    </div>
    <div class="estrategias-lista">
        @foreach($estrategias as $nombre)
        <div class="estrategia-item"><span class="casilla"></span>{{ $nombre }}</div>
        @endforeach
    </div>
</div>

{{-- Momentos didácticos en blanco --}}
@foreach(['inicio' => 'Inicio', 'desarrollo' => 'Desarrollo', 'cierre' => 'Cierre'] as $tipo => $label)
<div class="momento momento-{{ $tipo }}">
    <div class="momento-header">{{ $label }} <span style="font-weight:400;font-size:8px;">(duración: _____ min)</span></div>
    <div class="momento-body">
        <div class="campo-row">
            <div class="campo-lbl">Área Curricular</div>
            <div class="linea"></div>
        </div>
        <div class="campo-row">
            <div class="campo-lbl">Competencias Específicas</div>
            <div class="linea"></div>
        </div>
        <div class="campo-row">
            <div class="campo-lbl">Contenidos</div>
            <div class="linea"></div>
            <div class="linea"></div>
        </div>
        <div class="campo-row">
            <div class="campo-lbl">Actividades</div>
            <div class="linea"></div>
            <div class="linea"></div>
        </div>
        <div class="campo-row">
            <div class="campo-lbl">Indicador de Logro</div>
            <div class="linea"></div>
        </div>
        <div class="campo-row">
            <div class="campo-lbl">Recursos</div>
            <div class="linea"></div>
        </div>
    </div>
</div>
@endforeach

<div class="campo-row">
    <div class="campo-lbl">Observaciones</div>
    <div class="linea"></div>
</div>

<div class="firma-row">
    <div class="firma-box">Docente</div>
    <div class="firma-box">Coordinador/a Académico</div>
    <div class="firma-box">Director/a del Centro</div>
</div>

<div class="footer">
    <span>{{ $inst }} — Plantilla de Plan de Clase</span>
    <span>{{ now()->format('d/m/Y') }}</span>
</div>
</body>
</html>
