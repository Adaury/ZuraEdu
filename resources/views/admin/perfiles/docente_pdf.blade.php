<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: DejaVu Sans, sans-serif; font-size: 9.5px; color: #1e293b; }

.header { text-align: center; margin-bottom: 14px; border-bottom: 2px solid #1e40af; padding-bottom: 10px; }
.header .inst  { font-size: 12px; font-weight: bold; color: #1e40af; text-transform: uppercase; }
.header .titulo{ font-size: 12px; font-weight: bold; color: #0f172a; margin-top: 5px; }
.header .sub   { font-size: 8px; color: #6b7280; margin-top: 3px; }

.docente-card { background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 6px;
                padding: 10px 14px; margin-bottom: 14px; }
.doc-row { display: flex; gap: 18px; flex-wrap: wrap; }
.doc-col { flex: 1; }
.doc-lbl { font-size: 7.5px; font-weight: 700; text-transform: uppercase; color: #6b7280; margin-bottom: 1px; }
.doc-val { font-size: 10px; font-weight: 700; color: #1e293b; }

.kpis { display: flex; gap: 10px; margin-bottom: 12px; }
.kpi { flex: 1; text-align: center; padding: 7px 5px; border-radius: 5px; border: 1px solid #e2e8f0; }
.kpi .num { font-size: 16px; font-weight: 800; }
.kpi .lbl { font-size: 7px; color: #6b7280; margin-top: 2px; }
.k-azul { background:#eff6ff; } .k-azul .num { color:#1d4ed8; }
.k-verde{ background:#dcfce7; } .k-verde .num{ color:#15803d; }
.k-purl { background:#ede9fe; } .k-purl .num { color:#7c3aed; }
.k-narj { background:#fef3c7; } .k-narj .num { color:#d97706; }

.section-title { font-size: 9px; font-weight: 700; text-transform: uppercase; color: #6b7280;
                 border-bottom: 1px solid #e2e8f0; padding-bottom: 3px; margin: 10px 0 6px; letter-spacing:.04em; }

table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
thead tr { background: #1e40af; color: #fff; }
thead th { padding: 4px 6px; font-size: 8px; border: 1px solid #1e3a8a; text-align: center; }
thead th.left { text-align: left; }
tbody tr:nth-child(even) { background: #f0f7ff; }
tbody td { padding: 4px 6px; border: 1px solid #bfdbfe; font-size: 8.5px; text-align: center; vertical-align: middle; }
tbody td.left { text-align: left; }

.bar-wrap { background: #e2e8f0; border-radius: 3px; height: 7px; width: 70px; display: inline-block; vertical-align: middle; }
.bar-fill { height: 7px; border-radius: 3px; display: block; }

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
    <div class="titulo">INFORME DE ACTIVIDAD DOCENTE</div>
    <div class="sub">Año Escolar: {{ $schoolYear->nombre }} &nbsp;·&nbsp; Generado: {{ now()->format('d/m/Y') }}</div>
</div>

{{-- Datos del docente --}}
<div class="docente-card">
    <div class="doc-row">
        <div class="doc-col">
            <div class="doc-lbl">Docente</div>
            <div class="doc-val">{{ $docente->nombre_completo }}</div>
        </div>
        <div class="doc-col">
            <div class="doc-lbl">Cédula</div>
            <div class="doc-val">{{ $docente->cedula ?? '—' }}</div>
        </div>
        <div class="doc-col">
            <div class="doc-lbl">Email</div>
            <div class="doc-val" style="font-size:9px;">{{ $docente->user?->email ?? '—' }}</div>
        </div>
        <div class="doc-col">
            <div class="doc-lbl">Teléfono</div>
            <div class="doc-val">{{ $docente->telefono ?? '—' }}</div>
        </div>
    </div>
</div>

{{-- KPIs --}}
<div class="kpis">
    <div class="kpi k-azul">
        <div class="num">{{ $docente->asignaciones->count() }}</div>
        <div class="lbl">Asignaciones</div>
    </div>
    <div class="kpi k-purl">
        <div class="num">{{ $planificaciones->count() }}</div>
        <div class="lbl">Planificaciones</div>
    </div>
    <div class="kpi k-narj">
        <div class="num">{{ $planesClase->count() }}</div>
        <div class="lbl">Planes de Clase</div>
    </div>
    <div class="kpi k-verde">
        <div class="num">{{ $planificaciones->where('publicado', true)->count() + $planesClase->where('publicado', true)->count() }}</div>
        <div class="lbl">Publicados</div>
    </div>
</div>

{{-- Asignaciones y rendimiento --}}
<div class="section-title">Asignaciones y Rendimiento Académico</div>
<table>
    <thead>
        <tr>
            <th class="left" style="width:130px;">Asignatura</th>
            <th style="width:90px;">Grupo</th>
            <th style="width:50px;">Estu.</th>
            <th style="width:55px;">Promedio</th>
            <th style="width:80px;"></th>
            <th style="width:45px;">Aprobados</th>
            <th style="width:50px;">Reprobados</th>
        </tr>
    </thead>
    <tbody>
        @forelse($docente->asignaciones as $asig)
        @php
            $r    = $rendimiento[$asig->id] ?? ['total'=>0,'promedio'=>null,'aprobados'=>0,'reprobados'=>0];
            $prom = $r['promedio'];
            $barColor = $prom ? ($prom >= 70 ? '#22c55e' : ($prom >= 60 ? '#f59e0b' : '#ef4444')) : '#e2e8f0';
        @endphp
        <tr>
            <td class="left">{{ $asig->asignatura?->nombre ?? '—' }}</td>
            <td>{{ ($asig->grupo?->grado->nombre ?? '') . ' ' . ($asig->grupo?->seccion->nombre ?? '') }}</td>
            <td>{{ $r['total'] }}</td>
            <td style="font-weight:800;color:{{ $prom ? ($prom>=70?'#15803d':'#dc2626') : '#94a3b8' }};">
                {{ $prom ?? '—' }}
            </td>
            <td>
                @if($prom)
                <span class="bar-wrap"><span class="bar-fill" style="width:{{ min($prom,100) }}%;background:{{ $barColor }};"></span></span>
                @endif
            </td>
            <td style="color:#15803d;font-weight:700;">{{ $r['aprobados'] }}</td>
            <td style="color:{{ $r['reprobados']>0?'#dc2626':'#15803d' }};font-weight:700;">{{ $r['reprobados'] }}</td>
        </tr>
        @empty
        <tr><td colspan="7" style="text-align:center;color:#94a3b8;font-style:italic;">Sin asignaciones este año.</td></tr>
        @endforelse
    </tbody>
</table>

{{-- Planificaciones --}}
@if($planificaciones->isNotEmpty())
<div class="section-title">Planificaciones ({{ $planificaciones->count() }} total)</div>
<table>
    <thead>
        <tr>
            <th class="left">Título</th>
            <th style="width:110px;">Asignatura</th>
            <th style="width:70px;">Tipo</th>
            <th style="width:65px;">Estado</th>
        </tr>
    </thead>
    <tbody>
        @foreach($planificaciones->take(10) as $p)
        <tr>
            <td class="left">{{ $p->titulo }}</td>
            <td>{{ $p->asignacion?->asignatura?->nombre ?? '—' }}</td>
            <td>{{ ucfirst($p->tipo ?? '') }}</td>
            <td style="color:{{ $p->publicado?'#15803d':'#d97706' }};font-weight:700;">
                {{ $p->publicado ? 'Publicada' : 'Borrador' }}
            </td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif

{{-- Planes de Clase --}}
@if($planesClase->isNotEmpty())
<div class="section-title">Planes de Clase ({{ $planesClase->count() }} total)</div>
<table>
    <thead>
        <tr>
            <th class="left">Título</th>
            <th style="width:70px;">Tipo</th>
            <th style="width:65px;">Semana</th>
            <th style="width:65px;">Estado</th>
        </tr>
    </thead>
    <tbody>
        @foreach($planesClase->take(10) as $p)
        <tr>
            <td class="left">{{ $p->titulo }}</td>
            <td>{{ ucfirst($p->tipo_plan ?? '') }}</td>
            <td>{{ $p->semana ?? '—' }}</td>
            <td style="color:{{ $p->publicado?'#15803d':'#d97706' }};font-weight:700;">
                {{ $p->publicado ? 'Publicado' : 'Borrador' }}
            </td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif

<div class="firma-row">
    <div class="firma-box">{{ $docente->nombre_completo }}</div>
    <div class="firma-box">Coordinador/a Académico</div>
    <div class="firma-box">Director/a del Centro</div>
</div>

<div class="footer">
    <span>{{ $inst }} — Informe de Actividad: {{ $docente->nombre_completo }}</span>
    <span>{{ now()->format('d/m/Y H:i') }}</span>
</div>
</body>
</html>
