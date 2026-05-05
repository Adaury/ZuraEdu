@extends('layouts.admin')

@section('page-title', 'Mi Horario — ' . ($docente->nombre_completo ?? 'Docente'))

@push('styles')
<style>
/* ═══════════════════════════════════════
   MI HORARIO — Vista personal del docente
═══════════════════════════════════════ */
:root {
    --dias: 5;
}

/* ── Header ──────────────────────────── */
.mh-header {
    background: linear-gradient(135deg, #1e1b4b 0%, #312e81 60%, #4338ca 100%);
    border-radius: 16px;
    padding: 1.6rem 2rem;
    margin-bottom: 1.75rem;
    position: relative;
    overflow: hidden;
    display: flex;
    align-items: center;
    gap: 1.25rem;
}
.mh-header::after {
    content: '';
    position: absolute;
    right: -40px; top: -40px;
    width: 200px; height: 200px;
    border-radius: 50%;
    background: rgba(255,255,255,.05);
}
.mh-avatar {
    width: 60px; height: 60px;
    border-radius: 50%;
    background: rgba(255,255,255,.18);
    border: 2px solid rgba(255,255,255,.3);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.4rem; font-weight: 800; color: #fff;
    flex-shrink: 0;
}
.mh-header h1 { font-size: 1.25rem; font-weight: 800; color: #fff; margin: 0 0 .25rem; }
.mh-header p  { font-size: .83rem; color: rgba(255,255,255,.72); margin: 0; }
.mh-stats {
    display: flex; gap: 1.25rem; margin-left: auto; flex-wrap: wrap;
}
.mh-stat {
    text-align: center;
    background: rgba(255,255,255,.12);
    border-radius: 10px;
    padding: .55rem .9rem;
    min-width: 68px;
}
.mh-stat .n { font-size: 1.4rem; font-weight: 900; color: #fff; line-height: 1; }
.mh-stat .l { font-size: .65rem; color: rgba(255,255,255,.65); text-transform: uppercase; letter-spacing: .05em; }

/* ── Sin horario ─────────────────────── */
.no-horario-card {
    background: #fff;
    border: 2px dashed #e5e7eb;
    border-radius: 16px;
    text-align: center;
    padding: 3.5rem 2rem;
}
.no-horario-card .icon { font-size: 3.5rem; color: #d1d5db; margin-bottom: 1rem; }
.no-horario-card h4 { font-size: 1rem; font-weight: 700; color: #374151; margin-bottom: .4rem; }
.no-horario-card p  { font-size: .85rem; color: #9ca3af; max-width: 380px; margin: 0 auto; }

/* ── Tabla de horario ────────────────── */
.schedule-wrap {
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 14px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(30,58,110,.07);
}
.schedule-table {
    width: 100%;
    border-collapse: collapse;
    table-layout: fixed;
}
.schedule-table th {
    background: #1e1b4b;
    color: #fff;
    font-size: .75rem;
    font-weight: 700;
    letter-spacing: .08em;
    text-transform: uppercase;
    padding: .75rem .5rem;
    text-align: center;
}
.schedule-table th.th-franja {
    background: #312e81;
    width: 90px;
    font-size: .68rem;
}
.schedule-table td {
    border: 1px solid #f1f5f9;
    vertical-align: top;
    padding: 0;
    min-height: 72px;
}
.schedule-table td.td-franja {
    background: #f8f9fc;
    text-align: center;
    padding: .5rem .25rem;
    border-right: 2px solid #e2e8f0;
}
.franja-hora { font-size: .68rem; font-weight: 700; color: #374151; }
.franja-rango { font-size: .62rem; color: #9ca3af; }

/* ── Celda con clase ─────────────────── */
.clase-card {
    height: 100%;
    min-height: 68px;
    padding: .5rem .6rem;
    border-radius: 8px;
    margin: 3px;
    position: relative;
    display: flex;
    flex-direction: column;
    gap: .2rem;
}
.clase-asignatura {
    font-size: .76rem;
    font-weight: 800;
    color: #fff;
    line-height: 1.2;
}
.clase-grupo {
    font-size: .68rem;
    font-weight: 600;
    color: rgba(255,255,255,.85);
    display: flex;
    align-items: center;
    gap: .3rem;
}
.clase-aula {
    font-size: .63rem;
    color: rgba(255,255,255,.7);
    display: flex;
    align-items: center;
    gap: .25rem;
    margin-top: auto;
}

/* ── Recreo ──────────────────────────── */
.recreo-row td {
    background: #fef9ec;
    text-align: center;
    padding: .4rem;
    font-size: .72rem;
    font-weight: 600;
    color: #92400e;
    letter-spacing: .04em;
}

/* ── Celda vacía ─────────────────────── */
.empty-cell { min-height: 68px; background: #fafbff; }

/* ── Leyenda ─────────────────────────── */
.legend-wrap {
    display: flex;
    flex-wrap: wrap;
    gap: .65rem;
    margin-top: 1.25rem;
}
.legend-item {
    display: flex;
    align-items: center;
    gap: .45rem;
    font-size: .78rem;
    color: #374151;
}
.legend-dot {
    width: 14px; height: 14px;
    border-radius: 4px;
    flex-shrink: 0;
}

/* ── Resumen diario ──────────────────── */
.day-summary {
    display: flex;
    gap: .75rem;
    margin-bottom: 1.25rem;
    flex-wrap: wrap;
}
.day-card {
    flex: 1;
    min-width: 90px;
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    text-align: center;
    padding: .7rem .5rem;
}
.day-card .day-name { font-size: .72rem; font-weight: 700; color: #6b7280; text-transform: uppercase; letter-spacing: .05em; }
.day-card .day-num  { font-size: 1.4rem; font-weight: 900; color: #312e81; line-height: 1.1; }
.day-card .day-label { font-size: .63rem; color: #9ca3af; }

@media (max-width: 768px) {
    .schedule-table { font-size: .72rem; }
    .mh-stats { display: none; }
    .schedule-table th.th-franja { width: 60px; }
}

[data-theme="dark"] .day-card { background: #1e293b; border-color: #334155; }
[data-theme="dark"] .day-card .day-name { color: #64748b; }
[data-theme="dark"] .day-card .day-num { color: #a5b4fc; }
</style>
@endpush

@section('content')

<x-breadcrumb :items="[
    ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
    ['label' => 'Mi Horario', 'url' => ''],
]" />

{{-- ── Header personal ──────────────────────────────────────────── --}}
<div class="mh-header">
    <div class="mh-avatar">
        {{ strtoupper(substr($docente->nombres ?? 'D', 0, 1)) }}
    </div>
    <div>
        <h1>{{ $docente->nombre_completo ?? 'Mi Horario' }}</h1>
        <p>
            <i class="bi bi-mortarboard me-1"></i>
            {{ $schoolYear?->nombre ?? 'Año escolar no configurado' }}
            @if($horarioActivo)
                &nbsp;·&nbsp;
                <span style="background:rgba(255,255,255,.18);border-radius:6px;padding:.15rem .55rem;font-size:.75rem;">
                    <i class="bi bi-check-circle-fill me-1" style="color:#86efac;"></i>Horario publicado
                </span>
            @endif
        </p>
    </div>
    @if($horarioActivo && $detalles->isNotEmpty())
    <div class="mh-stats">
        <div class="mh-stat">
            <div class="n">{{ $stats['clases_semana'] }}</div>
            <div class="l">Clases/sem</div>
        </div>
        <div class="mh-stat">
            <div class="n">{{ $stats['grupos'] }}</div>
            <div class="l">Grupos</div>
        </div>
        <div class="mh-stat">
            <div class="n">{{ $stats['asignaturas'] }}</div>
            <div class="l">Materias</div>
        </div>
    </div>
    @endif
</div>

{{-- ── Sin horario publicado ─────────────────────────────────────── --}}
@if(! $horarioActivo || $detalles->isEmpty())
    <div class="no-horario-card">
        <div class="icon"><i class="bi bi-calendar3-week"></i></div>
        @if(! $horarioActivo)
            <h4>No hay horario publicado</h4>
            <p>El administrador aún no ha publicado el horario escolar para este año.<br>
               Cuando sea publicado, podrás verlo aquí.</p>
        @else
            <h4>No tienes clases asignadas en el horario</h4>
            <p>El horario está publicado pero no tienes clases registradas.<br>
               Consulta con el administrador si falta alguna asignación.</p>
        @endif
    </div>
@else

    {{-- ── Resumen por día ──────────────────────────────────────────── --}}
    @php
        $dias = ['lunes','martes','miercoles','jueves','viernes'];
        $diasLabel = ['Lun','Mar','Mié','Jue','Vie'];
        $clasesPorDia = [];
        foreach ($dias as $dia) {
            $clasesPorDia[$dia] = $detalles->filter(fn($d) => $d->dia === $dia)->count();
        }
    @endphp

    <div class="day-summary">
        @foreach($dias as $i => $dia)
        <div class="day-card">
            <div class="day-name">{{ $diasLabel[$i] }}</div>
            <div class="day-num">{{ $clasesPorDia[$dia] }}</div>
            <div class="day-label">clase{{ $clasesPorDia[$dia] !== 1 ? 's' : '' }}</div>
        </div>
        @endforeach
    </div>

    {{-- ── Tabla de horario ─────────────────────────────────────────── --}}
    <div class="schedule-wrap">
        <div class="table-responsive">
            <table class="schedule-table">
                <thead>
                    <tr>
                        <th class="th-franja"><i class="bi bi-clock"></i></th>
                        @foreach($dias as $dia)
                            <th>{{ ucfirst($dia === 'miercoles' ? 'Miércoles' : $dia) }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($franjas as $franja)
                        @if($franja->es_recreo)
                            <tr class="recreo-row">
                                <td colspan="{{ count($dias) + 1 }}">
                                    <i class="bi bi-cup-hot me-1"></i>
                                    RECREO · {{ \Carbon\Carbon::parse($franja->hora_inicio)->format('H:i') }}
                                    – {{ \Carbon\Carbon::parse($franja->hora_fin)->format('H:i') }}
                                </td>
                            </tr>
                        @else
                            <tr>
                                {{-- Franja --}}
                                <td class="td-franja">
                                    <div class="franja-hora">{{ $franja->nombre ?? 'F'.$franja->numero }}</div>
                                    <div class="franja-rango">
                                        {{ \Carbon\Carbon::parse($franja->hora_inicio)->format('H:i') }}<br>
                                        {{ \Carbon\Carbon::parse($franja->hora_fin)->format('H:i') }}
                                    </div>
                                </td>

                                {{-- Días --}}
                                @foreach($dias as $dia)
                                    <td>
                                        @if(isset($grid[$franja->id][$dia]))
                                            @php
                                                $d        = $grid[$franja->id][$dia];
                                                $asigId   = $d->asignacion?->asignatura_id ?? 0;
                                                $color    = $colores[$asigId] ?? '#6366f1';
                                                $nombreAs = $d->asignacion?->asignatura?->nombre ?? 'Materia';
                                                $grupo    = $d->asignacion?->grupo;
                                                $nombreGr = optional($grupo)->nombre_completo
                                                          ?? (optional($grupo?->grado)->nombre . ' ' . optional($grupo?->seccion)->nombre)
                                                          ?? '—';
                                                $aula     = $d->aula?->nombre ?? null;
                                            @endphp
                                            <div class="clase-card" style="background:{{ $color }};">
                                                <div class="clase-asignatura">{{ $nombreAs }}</div>
                                                <div class="clase-grupo">
                                                    <i class="bi bi-people-fill" style="font-size:.6rem;"></i>
                                                    {{ $nombreGr }}
                                                </div>
                                                @if($aula)
                                                <div class="clase-aula">
                                                    <i class="bi bi-door-open" style="font-size:.6rem;"></i>
                                                    {{ $aula }}
                                                </div>
                                                @endif
                                            </div>
                                        @else
                                            <div class="empty-cell"></div>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endif
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- ── Leyenda de colores ───────────────────────────────────────── --}}
    @php
        $asignaturasUnicas = $detalles
            ->groupBy(fn($d) => $d->asignacion?->asignatura_id)
            ->map(fn($items) => $items->first());
    @endphp
    @if($asignaturasUnicas->isNotEmpty())
    <div class="legend-wrap">
        @foreach($asignaturasUnicas as $asigId => $det)
            <div class="legend-item">
                <div class="legend-dot" style="background:{{ $colores[$asigId] ?? '#6366f1' }};"></div>
                <span>{{ $det->asignacion?->asignatura?->nombre ?? 'Materia' }}</span>
            </div>
        @endforeach
    </div>
    @endif

    {{-- ── Info del horario ────────────────────────────────────────── --}}
    <div class="mt-3 d-flex align-items-center gap-2 flex-wrap" style="font-size:.78rem;color:#9ca3af;">
        <i class="bi bi-info-circle"></i>
        Horario: <strong style="color:#374151;">{{ $horarioActivo->nombre }}</strong>
        &nbsp;·&nbsp;
        Generado: {{ $horarioActivo->generado_en?->format('d/m/Y H:i') ?? $horarioActivo->created_at->format('d/m/Y') }}
        @if($horarioActivo->score)
            &nbsp;·&nbsp; Puntaje: <strong style="color:#312e81;">{{ number_format($horarioActivo->score,1) }}%</strong>
        @endif
    </div>

@endif

@endsection
