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
.meta-cell { background: #f0f4ff; border: 1px solid #c7d2fe; border-radius: 4px; padding: 5px 8px; }
.meta-cell .lbl { font-size: 7px; font-weight: 700; text-transform: uppercase; color: #6b7280; }
.meta-cell .val { font-size: 9px; font-weight: 700; color: #1e293b; }

.intencion { background: #fffbeb; border-left: 3px solid #f59e0b; padding: 8px 10px;
             margin-bottom: 10px; border-radius: 0 4px 4px 0; font-size: 9px; }
.intencion .lbl { font-weight: 700; color: #92400e; font-size: 8.5px; margin-bottom: 3px; }

.momento { border: 1px solid #e2e8f0; border-radius: 5px; margin-bottom: 8px; overflow: hidden; }
.momento-header { padding: 5px 10px; font-weight: 700; font-size: 9px; display: flex; justify-content: space-between; }
.momento-inicio     .momento-header { background: #dcfce7; color: #15803d; }
.momento-desarrollo .momento-header { background: #dbeafe; color: #1d4ed8; }
.momento-cierre     .momento-header { background: #fef9c3; color: #92400e; }
.momento-body { padding: 7px 10px; }

.campo-row { margin-bottom: 5px; }
.campo-lbl { font-size: 7.5px; font-weight: 700; text-transform: uppercase; color: #94a3b8; margin-bottom: 1px; }
.campo-val { font-size: 9px; color: #1e293b; line-height: 1.5; }

.estrategias-chips { display: flex; flex-wrap: wrap; gap: 4px; margin-top: 6px; }
.chip { background: #ede9fe; color: #5b21b6; border-radius: 20px; padding: 2px 7px;
        font-size: 7.5px; font-weight: 600; }

.footer { margin-top: 14px; border-top: 1px solid #e2e8f0; padding-top: 7px;
          display: flex; justify-content: space-between; font-size: 7.5px; color: #94a3b8; }
.firma-row { display: flex; gap: 24px; margin-top: 20px; }
.firma-box { flex: 1; text-align: center; border-top: 1px solid #94a3b8; padding-top: 5px;
             font-size: 8px; color: #475569; margin-top: 22px; }
</style>
</head>
<body>

<div class="header">
    <div class="inst">{{ $inst }}</div>
    <div class="titulo">PLAN DE CLASE — {{ strtoupper($planClase->titulo) }}</div>
    <div class="sub">
        {{ $asignacion->asignatura->nombre ?? '' }}
        &nbsp;·&nbsp; {{ $asignacion->grupo->nombre_completo ?? '' }}
        &nbsp;·&nbsp; Docente: {{ $docente->nombre_completo ?? '' }}
        &nbsp;·&nbsp; Generado: {{ now()->format('d/m/Y') }}
    </div>
</div>

{{-- Metadata --}}
<div class="meta-grid">
    <div class="meta-cell">
        <div class="lbl">Área</div>
        <div class="val">{{ ucfirst($planClase->area ?? '') }}</div>
    </div>
    <div class="meta-cell">
        <div class="lbl">Tipo</div>
        <div class="val">{{ ucfirst($planClase->tipo_plan ?? '') }}</div>
    </div>
    @if($planClase->semana)
    <div class="meta-cell">
        <div class="lbl">Semana</div>
        <div class="val">{{ $planClase->semana }}</div>
    </div>
    @endif
    @if($planClase->fecha_inicio)
    <div class="meta-cell">
        <div class="lbl">Inicio</div>
        <div class="val">{{ $planClase->fecha_inicio->format('d/m/Y') }}</div>
    </div>
    @endif
    @if($planClase->fecha_fin)
    <div class="meta-cell">
        <div class="lbl">Fin</div>
        <div class="val">{{ $planClase->fecha_fin->format('d/m/Y') }}</div>
    </div>
    @endif
    <div class="meta-cell">
        <div class="lbl">Estado</div>
        <div class="val" style="color:{{ $planClase->publicado ? '#15803d' : '#6b7280' }};">
            {{ $planClase->publicado ? 'Publicado' : 'Borrador' }}
        </div>
    </div>
</div>

{{-- Intención pedagógica --}}
@if($planClase->intencion_pedagogica)
<div class="intencion">
    <div class="lbl">Intención Pedagógica</div>
    {{ $planClase->intencion_pedagogica }}
</div>
@endif

{{-- Estrategias --}}
@if(!empty($planClase->estrategias))
@php
    $catalogo = \App\Models\PlanClase::$estrategiasCatalogo ?? [];
@endphp
<div style="margin-bottom:10px;">
    <div style="font-size:8px;font-weight:700;text-transform:uppercase;color:#94a3b8;margin-bottom:4px;">Estrategias</div>
    <div class="estrategias-chips">
        @foreach($planClase->estrategias as $e)
        <span class="chip">{{ $catalogo[$e] ?? $e }}</span>
        @endforeach
    </div>
</div>
@endif

{{-- Momentos didácticos --}}
@php
    $tipoColors = ['inicio' => 'inicio', 'desarrollo' => 'desarrollo', 'cierre' => 'cierre'];
    $tipoLabels = ['inicio' => 'Inicio', 'desarrollo' => 'Desarrollo', 'cierre' => 'Cierre'];
    $tipoIconos = ['inicio' => '▶', 'desarrollo' => '→', 'cierre' => '■'];
@endphp

@foreach(['inicio','desarrollo','cierre'] as $tipo)
@php $momento = $planClase->momentos->firstWhere('tipo', $tipo); @endphp
@if($momento)
<div class="momento momento-{{ $tipo }}">
    <div class="momento-header">
        <span>{{ $tipoIconos[$tipo] }} {{ $tipoLabels[$tipo] }}</span>
        @if($momento->duracion_minutos)
        <span style="font-weight:400;font-size:8px;">{{ $momento->duracion_minutos }} min</span>
        @endif
    </div>
    <div class="momento-body">
        @if($momento->area_curricular)
        <div class="campo-row">
            <div class="campo-lbl">Área Curricular</div>
            <div class="campo-val">{{ $momento->area_curricular }}</div>
        </div>
        @endif
        @if($momento->competencias_especificas)
        <div class="campo-row">
            <div class="campo-lbl">Competencias Específicas</div>
            <div class="campo-val">{{ $momento->competencias_especificas }}</div>
        </div>
        @endif
        @if($momento->contenidos)
        <div class="campo-row">
            <div class="campo-lbl">Contenidos</div>
            <div class="campo-val">{{ $momento->contenidos }}</div>
        </div>
        @endif
        @if($momento->actividades)
        <div class="campo-row">
            <div class="campo-lbl">Actividades</div>
            <div class="campo-val">{{ $momento->actividades }}</div>
        </div>
        @endif
        @if($momento->indicador_logro)
        <div class="campo-row">
            <div class="campo-lbl">Indicador de Logro</div>
            <div class="campo-val">{{ $momento->indicador_logro }}</div>
        </div>
        @endif
        @if($momento->recursos)
        <div class="campo-row">
            <div class="campo-lbl">Recursos</div>
            <div class="campo-val">{{ $momento->recursos }}</div>
        </div>
        @endif
    </div>
</div>
@endif
@endforeach

{{-- Observación --}}
@if($planClase->observacion)
<div style="background:#f1f5f9;border:1px solid #e2e8f0;border-radius:4px;padding:7px 10px;margin-top:8px;">
    <div style="font-size:7.5px;font-weight:700;text-transform:uppercase;color:#94a3b8;margin-bottom:3px;">Observaciones</div>
    <div style="font-size:9px;">{{ $planClase->observacion }}</div>
</div>
@endif

<div class="firma-row">
    <div class="firma-box">Docente: {{ $docente->nombre_completo ?? '' }}</div>
    <div class="firma-box">Coordinador/a Académico</div>
    <div class="firma-box">Director/a del Centro</div>
</div>

<div class="footer">
    <span>{{ $inst }} — Plan de Clase: {{ $planClase->titulo }}</span>
    <span>{{ now()->format('d/m/Y H:i') }}</span>
</div>
</body>
</html>
