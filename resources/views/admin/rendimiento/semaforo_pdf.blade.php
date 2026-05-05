<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #1e293b; }

.header { text-align: center; margin-bottom: 14px; border-bottom: 2px solid #7c3aed; padding-bottom: 10px; }
.header .inst  { font-size: 12px; font-weight: bold; color: #7c3aed; text-transform: uppercase; }
.header .titulo{ font-size: 12px; font-weight: bold; color: #0f172a; margin-top: 5px; }
.header .sub   { font-size: 8px; color: #6b7280; margin-top: 3px; }

.leyenda { display: flex; gap: 14px; margin-bottom: 12px; background: #f5f3ff;
           border: 1px solid #ddd6fe; border-radius: 5px; padding: 7px 10px; }
.ley-item { display: flex; align-items: center; gap: 5px; font-size: 8px; }
.dot { width: 12px; height: 12px; border-radius: 50%; flex-shrink: 0; }
.dot-ok   { background: #22c55e; }
.dot-warn { background: #f59e0b; }
.dot-bad  { background: #ef4444; }

.grid { display: flex; flex-wrap: wrap; gap: 8px; }
.tarjeta { width: 165px; border-radius: 7px; overflow: hidden; border: 1px solid #e2e8f0;
           page-break-inside: avoid; }
.tarjeta-top { padding: 5px 8px; color: #fff; font-size: 8.5px; font-weight: 700; }
.t-verde  { background: #16a34a; }
.t-amarillo { background: #d97706; }
.t-rojo   { background: #dc2626; }
.tarjeta-body { padding: 6px 8px; background: #fff; }
.t-prom { font-size: 20px; font-weight: 900; text-align: center; margin-bottom: 3px; }
.t-prom-verde  { color: #16a34a; }
.t-prom-amarillo { color: #d97706; }
.t-prom-rojo   { color: #dc2626; }
.t-stat { display: flex; justify-content: space-between; font-size: 7.5px; color: #6b7280; margin-top: 2px; }
.t-bar  { background: #e2e8f0; border-radius: 3px; height: 5px; margin-top: 4px; }
.t-bar-fill { height: 5px; border-radius: 3px; }

.footer { margin-top: 14px; border-top: 1px solid #e2e8f0; padding-top: 7px;
          display: flex; justify-content: space-between; font-size: 7.5px; color: #94a3b8; }
</style>
</head>
<body>

<div class="header">
    <div class="inst">{{ $inst }}</div>
    <div class="titulo">SEMÁFORO DE RENDIMIENTO ACADÉMICO</div>
    <div class="sub">
        Año Escolar: {{ $schoolYear->nombre }}
        &nbsp;·&nbsp; Total grupos: {{ $grupos->count() }}
        &nbsp;·&nbsp; Generado: {{ now()->format('d/m/Y') }}
    </div>
</div>

@php
$verdes   = $grupos->filter(fn($g) => $g->semaforo === 'success' || ($g->promedio_grupo ?? 0) >= 70)->count();
$amarillos= $grupos->filter(fn($g) => $g->semaforo === 'warning' || (($g->promedio_grupo ?? 0) >= 60 && ($g->promedio_grupo ?? 0) < 70))->count();
$rojos    = $grupos->filter(fn($g) => $g->semaforo === 'danger'  || ($g->promedio_grupo ?? 0) < 60)->count();
@endphp

<div class="leyenda">
    <div class="ley-item"><div class="dot dot-ok"></div> <span><strong>Verde</strong> — Promedio ≥70 ({{ $verdes }} grupos)</span></div>
    <div class="ley-item"><div class="dot dot-warn"></div> <span><strong>Amarillo</strong> — Entre 60–70 ({{ $amarillos }} grupos)</span></div>
    <div class="ley-item"><div class="dot dot-bad"></div> <span><strong>Rojo</strong> — Promedio &lt;60 ({{ $rojos }} grupos)</span></div>
</div>

<div class="grid">
    @foreach($grupos->sortBy('promedio_grupo') as $row)
    @php
        $prom = $row->promedio_grupo;
        $sem  = $row->semaforo ?? ($prom >= 70 ? 'success' : ($prom >= 60 ? 'warning' : 'danger'));
        $topCls  = match($sem) { 'success'=>'t-verde', 'warning'=>'t-amarillo', default=>'t-rojo' };
        $promCls = match($sem) { 'success'=>'t-prom-verde', 'warning'=>'t-prom-amarillo', default=>'t-prom-rojo' };
        $barClr  = match($sem) { 'success'=>'#22c55e', 'warning'=>'#f59e0b', default=>'#ef4444' };
        $aprobPct = $row->total_estudiantes > 0
            ? round(($row->total_estudiantes - $row->total_riesgo) / $row->total_estudiantes * 100, 1)
            : null;
    @endphp
    <div class="tarjeta">
        <div class="tarjeta-top {{ $topCls }}">
            {{ $row->grupo?->grado->nombre ?? '' }} {{ $row->grupo?->seccion->nombre ?? '' }}
        </div>
        <div class="tarjeta-body">
            <div class="t-prom {{ $promCls }}">{{ $prom ? number_format($prom, 1) : '—' }}</div>
            <div class="t-bar">
                <div class="t-bar-fill" style="width:{{ min($prom ?? 0, 100) }}%;background:{{ $barClr }};"></div>
            </div>
            <div class="t-stat">
                <span>Estudiantes: <strong>{{ $row->total_estudiantes }}</strong></span>
                <span>En riesgo: <strong style="color:#dc2626;">{{ $row->total_riesgo }}</strong></span>
            </div>
            @if($aprobPct !== null)
            <div class="t-stat">
                <span>Aprobación: <strong>{{ $aprobPct }}%</strong></span>
                @if($prom !== null)
                <span>Prom: <strong>{{ number_format($prom,1) }}</strong></span>
                @endif
            </div>
            @endif
        </div>
    </div>
    @endforeach
</div>

<div class="footer">
    <span>{{ $inst }} — Semáforo de Rendimiento</span>
    <span>{{ now()->format('d/m/Y H:i') }}</span>
</div>
</body>
</html>
