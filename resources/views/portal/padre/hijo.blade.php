@extends('layouts.portal')
@section('page-title', $estudiante->nombre_completo)
@section('portal-name', 'Portal del Representante')

@section('sidebar')
    @include('portal.padre._sidebar', ['activeKey' => 'hijo'])
    <div class="prt-sidebar-section mt-2">En esta página</div>
    <a href="#notas" class="prt-sidebar-link"><i class="bi bi-journal-text"></i>Notas</a>
    <a href="#asistencia" class="prt-sidebar-link"><i class="bi bi-calendar-check"></i>Asistencia</a>
    <a href="#horario" class="prt-sidebar-link"><i class="bi bi-calendar-week"></i>Horario</a>
    <a href="#observaciones" class="prt-sidebar-link"><i class="bi bi-chat-square-text"></i>Observaciones</a>
    @if($planificaciones->isNotEmpty())
    <a href="#planificaciones" class="prt-sidebar-link"><i class="bi bi-journal-text"></i>Planificaciones</a>
    @endif
    @if($resumenPagos !== null)
    <a href="#pagos" class="prt-sidebar-link">
        <i class="bi bi-cash-coin"></i>Pagos
        @if(($resumenPagos['vencido'] ?? 0) > 0)
        <span style="background:#ef4444;color:#fff;border-radius:99px;font-size:.6rem;padding:.1rem .4rem;font-weight:700;margin-left:auto;">{{ $resumenPagos['vencido'] }}</span>
        @endif
    </a>
    @endif
@endsection

@section('bottom-nav')
    <a href="{{ route('portal.padre.dashboard') }}" class="prt-nav-item">
        <i class="bi bi-house-fill"></i>Inicio
    </a>
    <a href="#notas" class="prt-nav-item">
        <i class="bi bi-journal-text"></i>Notas
    </a>
    <a href="#asistencia" class="prt-nav-item">
        <i class="bi bi-calendar-check"></i>Asist.
    </a>
    <a href="#horario" class="prt-nav-item">
        <i class="bi bi-calendar-week"></i>Horario
    </a>
    <a href="#observaciones" class="prt-nav-item">
        <i class="bi bi-chat-square-text"></i>Obs.
    </a>
@endsection

@section('content')

{{-- Volver --}}
<div style="display:flex;align-items:center;gap:.75rem;margin-bottom:1rem;flex-wrap:wrap;">
    <a href="{{ route('portal.padre.dashboard') }}" class="btn-back"
       style="background:#f1f5f9;color:#374151;border-radius:8px;padding:.4rem .85rem;font-size:.8rem;text-decoration:none;display:flex;align-items:center;gap:.4rem;">
        <i class="bi bi-arrow-left"></i>Volver
    </a>
    <div style="flex:1;">
        <h1 style="font-size:1rem;font-weight:800;margin:0;">{{ $estudiante->nombre_completo }}</h1>
        <div class="dm-text-muted" style="font-size:.75rem;color:#64748b;">
            {{ $matricula?->grupo?->nombre_completo ?? 'Sin grupo asignado' }}
            @if($schoolYear) · {{ $schoolYear->nombre }} @endif
        </div>
    </div>
    @if($matricula)
    <a href="{{ route('portal.padre.hijo.constancia', $estudiante) }}" target="_blank"
       style="background:#1e3a6e;color:#fff;border-radius:8px;padding:.4rem .85rem;font-size:.78rem;font-weight:700;text-decoration:none;display:flex;align-items:center;gap:.4rem;white-space:nowrap;">
        <i class="bi bi-file-earmark-text"></i>Constancia
    </a>
    @endif
</div>

{{-- ── Hero del estudiante ────────────────────────────────────────────── --}}
@php
    $todasNotas = collect();
    foreach($calificaciones as $periodo_id => $grupo) {
        $todasNotas = $todasNotas->merge($grupo->pluck('nota_final')->filter());
    }
    $todasNotas = $todasNotas->merge($calificacionesAcademicas->pluck('nota_final')->filter());
    $promedioGeneral = $todasNotas->count() ? round($todasNotas->avg(), 1) : null;
    $promedioColor   = $promedioGeneral === null ? '#6b7280' : ($promedioGeneral >= 80 ? '#15803d' : ($promedioGeneral >= 60 ? '#d97706' : '#dc2626'));
@endphp

<div style="background:linear-gradient(135deg,#1e3a5f 0%,#0ea5e9 100%);border-radius:14px;padding:1.25rem 1.5rem;color:#fff;margin-bottom:1rem;display:flex;align-items:center;gap:1rem;position:relative;overflow:hidden;">
    <div style="position:absolute;right:-20px;top:-20px;width:120px;height:120px;background:rgba(255,255,255,.06);border-radius:50%;"></div>
    <div style="width:50px;height:50px;border-radius:50%;background:rgba(255,255,255,.18);border:2px solid rgba(255,255,255,.3);display:flex;align-items:center;justify-content:center;font-size:1.2rem;font-weight:900;flex-shrink:0;">
        {{ strtoupper(substr($estudiante->nombres ?? 'E', 0, 1)) }}
    </div>
    <div style="flex:1;">
        <div style="font-size:1rem;font-weight:800;margin-bottom:.2rem;">{{ $estudiante->nombre_completo }}</div>
        <div style="font-size:.76rem;color:rgba(255,255,255,.75);">
            <i class="bi bi-people-fill me-1"></i>{{ $matricula?->grupo?->nombre_completo ?? 'Sin grupo' }}
        </div>
    </div>
    @if($promedioGeneral !== null)
    <div style="background:rgba(255,255,255,.15);border-radius:10px;padding:.55rem .9rem;text-align:center;flex-shrink:0;">
        <div style="font-size:1.5rem;font-weight:900;line-height:1;">{{ $promedioGeneral }}</div>
        <div style="font-size:.6rem;color:rgba(255,255,255,.7);">Promedio</div>
    </div>
    @endif
</div>

{{-- ── Stats rápidos ──────────────────────────────────────────────────── --}}
<div class="prt-stats" style="margin-bottom:1rem;">
    <div class="prt-stat">
        <div class="prt-stat-icon" style="background:#ede9fe;color:#5b21b6;"><i class="bi bi-journal-check"></i></div>
        <div>
            <div class="prt-stat-val">{{ $promedioGeneral ?? '—' }}</div>
            <div class="prt-stat-lbl">Promedio</div>
        </div>
    </div>
    <div class="prt-stat">
        <div class="prt-stat-icon" style="background:#dcfce7;color:#15803d;"><i class="bi bi-calendar-check"></i></div>
        <div>
            <div class="prt-stat-val">{{ $resumenAsistencia['porcentaje'] !== null ? $resumenAsistencia['porcentaje'].'%' : '—' }}</div>
            <div class="prt-stat-lbl">Asistencia</div>
        </div>
    </div>
    <div class="prt-stat">
        <div class="prt-stat-icon" style="background:#fee2e2;color:#991b1b;"><i class="bi bi-calendar-x"></i></div>
        <div>
            <div class="prt-stat-val">{{ $resumenAsistencia['ausentes'] }}</div>
            <div class="prt-stat-lbl">Ausencias</div>
        </div>
    </div>
    <div class="prt-stat">
        <div class="prt-stat-icon" style="background:#fef9c3;color:#854d0e;"><i class="bi bi-chat-square-text"></i></div>
        <div>
            <div class="prt-stat-val">{{ $observaciones->count() }}</div>
            <div class="prt-stat-lbl">Observaciones</div>
        </div>
    </div>
</div>

{{-- ── Materias ────────────────────────────────────────────────────── --}}
@if($asignaciones->isNotEmpty())
<div class="prt-card" style="margin-bottom:1rem;">
    <div class="prt-card-header">
        <i class="bi bi-journals" style="color:#6366f1;font-size:1rem;"></i>
        <h3>Materias</h3>
    </div>
    <div style="padding:.75rem;display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:.55rem;">
    @php $colores = ['#6366f1','#2563eb','#10b981','#f59e0b','#ef4444','#ec4899','#06b6d4','#8b5cf6']; @endphp
    @foreach($asignaciones as $asig)
    @php $color = $colores[$loop->index % count($colores)]; @endphp
    <div style="border:1px solid {{ $color }}25;border-radius:10px;overflow:hidden;background:var(--prt-card);">
        <div style="background:{{ $color }}12;padding:.5rem .75rem;border-bottom:1px solid {{ $color }}18;display:flex;align-items:center;gap:.4rem;">
            <i class="bi bi-book-fill" style="color:{{ $color }};font-size:.8rem;flex-shrink:0;"></i>
            <div style="flex:1;min-width:0;">
                <div style="font-size:.76rem;font-weight:800;color:var(--prt-text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $asig->asignatura?->nombre ?? '—' }}</div>
                @if($asig->docente)
                <div style="font-size:.62rem;color:var(--prt-muted);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $asig->docente->nombre_completo }}</div>
                @endif
            </div>
        </div>
        <div style="display:flex;">
            <a href="{{ route('portal.padre.hijo.recursos', [$estudiante, $asig]) }}"
               style="flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:.15rem;padding:.5rem;text-decoration:none;color:{{ $color }};font-size:.62rem;font-weight:700;border-right:1px solid {{ $color }}15;"
               onmouseover="this.style.background='{{ $color }}10'" onmouseout="this.style.background=''">
                <i class="bi bi-folder-fill" style="font-size:.9rem;"></i>Recursos
            </a>
            <a href="{{ route('portal.padre.hijo.boletin', $estudiante) }}"
               style="flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:.15rem;padding:.5rem;text-decoration:none;color:#1d4ed8;font-size:.62rem;font-weight:700;"
               onmouseover="this.style.background='#1d4ed810'" onmouseout="this.style.background=''">
                <i class="bi bi-file-earmark-text" style="font-size:.9rem;"></i>Boletín
            </a>
        </div>
    </div>
    @endforeach
    </div>
</div>
@endif

{{-- ── Notas ──────────────────────────────────────────────────────────── --}}
<div class="prt-card" id="notas" style="margin-bottom:1rem;">
    <div class="prt-card-header">
        <i class="bi bi-journal-text" style="color:#5b21b6;font-size:1rem;"></i>
        <h3>Calificaciones por Período</h3>
        <a href="{{ route('portal.padre.hijo.notas-pdf', $estudiante) }}" target="_blank" class="ms-auto"
           style="display:inline-flex;align-items:center;gap:.3rem;background:#dc2626;color:#fff;border-radius:7px;padding:.28rem .7rem;font-size:.72rem;font-weight:600;text-decoration:none;">
            <i class="bi bi-file-earmark-pdf-fill"></i> PDF
        </a>
    </div>
    <div class="prt-card-body" style="padding:0;">
        @if($calificaciones->isEmpty() && $calificacionesAcademicas->isEmpty())
        <div style="padding:2rem;text-align:center;color:#9ca3af;font-size:.84rem;">
            <i class="bi bi-journal" style="font-size:1.5rem;display:block;margin-bottom:.4rem;"></i>
            No hay calificaciones publicadas aún.
        </div>
        @else

        {{-- Calificaciones técnicas por período --}}
        @foreach($periodos as $periodo)
        @php $calPeriodo = $calificaciones[$periodo->id] ?? collect(); @endphp
        @if($calPeriodo->isNotEmpty())
        <div class="obs-grupo-header" style="padding:.5rem 1rem;background:#f8fafc;border-bottom:1px solid #e2e8f0;font-size:.75rem;font-weight:700;color:#374151;">
            <i class="bi bi-calendar3 me-1"></i>{{ $periodo->nombre }}
        </div>
        @foreach($calPeriodo as $cal)
        @php
            $nota  = $cal->nota_final;
            $clase = $nota === null ? '' : ($nota >= 90 ? 'nota-a' : ($nota >= 75 ? 'nota-b' : ($nota >= 60 ? 'nota-c' : 'nota-f')));
        @endphp
        <div class="dm-list-item" style="padding:.7rem 1rem;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;gap:.75rem;">
            <div class="dm-text-primary" style="flex:1;font-size:.83rem;font-weight:600;">
                {{ $cal->asignacion?->asignatura?->nombre ?? '—' }}
            </div>
            @if($nota !== null)
                <span class="nota-badge {{ $clase }}">{{ $nota }}</span>
                @if($cal->letra) <span style="font-size:.7rem;color:#9ca3af;">{{ $cal->letra }}</span> @endif
            @else
                <span style="font-size:.74rem;color:#9ca3af;">Pendiente</span>
            @endif
        </div>
        @endforeach
        @endif
        @endforeach

        {{-- Calificaciones académicas --}}
        @if($calificacionesAcademicas->isNotEmpty())
        <div class="obs-grupo-header" style="padding:.5rem 1rem;background:#f8fafc;border-bottom:1px solid #e2e8f0;font-size:.75rem;font-weight:700;color:#374151;">
            <i class="bi bi-book me-1"></i>Materias Académicas
        </div>
        @foreach($calificacionesAcademicas as $cal)
        @php
            $nota  = $cal->nota_final;
            $clase = $nota === null ? '' : ($nota >= 90 ? 'nota-a' : ($nota >= 75 ? 'nota-b' : ($nota >= 60 ? 'nota-c' : 'nota-f')));
        @endphp
        <div class="dm-list-item" style="padding:.7rem 1rem;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;gap:.75rem;">
            <div class="dm-text-primary" style="flex:1;font-size:.83rem;font-weight:600;">
                {{ $cal->asignacion?->asignatura?->nombre ?? '—' }}
            </div>
            @if($nota !== null)
                <span class="nota-badge {{ $clase }}">{{ $nota }}</span>
            @else
                <span style="font-size:.74rem;color:#9ca3af;">Pendiente</span>
            @endif
        </div>
        @endforeach
        @endif

        @endif
    </div>
</div>

{{-- ── Asistencia ─────────────────────────────────────────────────────── --}}
<div class="prt-card" id="asistencia" style="margin-bottom:1rem;">
    <div class="prt-card-header">
        <i class="bi bi-calendar-check" style="color:#10b981;font-size:1rem;"></i>
        <h3>Asistencia</h3>
    </div>
    <div class="prt-card-body">
        @if($resumenAsistencia['total'] === 0)
        <div style="text-align:center;padding:1.5rem;color:#9ca3af;font-size:.84rem;">
            Sin registros de asistencia.
        </div>
        @else
        @php $pct = $resumenAsistencia['porcentaje'] ?? 0; @endphp
        <div style="margin-bottom:1rem;">
            <div style="display:flex;justify-content:space-between;font-size:.75rem;color:#374151;margin-bottom:.35rem;">
                <span>Porcentaje de asistencia</span>
                <span style="font-weight:700;color:{{ $pct >= 80 ? '#15803d' : ($pct >= 60 ? '#d97706' : '#dc2626') }};">{{ $pct }}%</span>
            </div>
            <div class="dm-progress-bg" style="background:#e2e8f0;border-radius:99px;height:10px;overflow:hidden;">
                <div style="width:{{ $pct }}%;height:100%;background:{{ $pct >= 80 ? '#10b981' : ($pct >= 60 ? '#f59e0b' : '#ef4444') }};border-radius:99px;transition:width .3s;"></div>
            </div>
        </div>
        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:.6rem;margin-bottom:1rem;">
            <div class="dm-att-present" style="background:#dcfce7;border-radius:9px;padding:.65rem;text-align:center;">
                <div style="font-size:1.1rem;font-weight:800;color:#15803d;">{{ $resumenAsistencia['presentes'] }}</div>
                <div class="dm-text-muted" style="font-size:.68rem;color:#166534;">Presentes</div>
            </div>
            <div class="dm-att-late" style="background:#fef9c3;border-radius:9px;padding:.65rem;text-align:center;">
                <div style="font-size:1.1rem;font-weight:800;color:#92400e;">{{ $resumenAsistencia['tardanzas'] }}</div>
                <div class="dm-text-muted" style="font-size:.68rem;color:#78350f;">Tardanzas</div>
            </div>
            <div class="dm-att-absent" style="background:#fee2e2;border-radius:9px;padding:.65rem;text-align:center;">
                <div style="font-size:1.1rem;font-weight:800;color:#991b1b;">{{ $resumenAsistencia['ausentes'] }}</div>
                <div class="dm-text-muted" style="font-size:.68rem;color:#7f1d1d;">Ausencias</div>
            </div>
        </div>

        {{-- Por materia --}}
        @if(!empty($resumenAsistencia['por_materia']) && count($resumenAsistencia['por_materia']) > 0)
        <div style="font-size:.75rem;font-weight:700;color:#374151;margin-bottom:.5rem;">Detalle por materia</div>
        @foreach($resumenAsistencia['por_materia'] as $pm)
        @php $pp = $pm['porcentaje'] ?? 0; @endphp
        <div style="margin-bottom:.5rem;">
            <div style="display:flex;justify-content:space-between;font-size:.73rem;color:#64748b;margin-bottom:.2rem;">
                <span>{{ $pm['asignatura'] }}</span>
                <span style="color:{{ $pp >= 80 ? '#15803d' : ($pp >= 60 ? '#d97706' : '#dc2626') }};font-weight:600;">{{ $pp }}%</span>
            </div>
            <div class="dm-progress-bg" style="background:#f1f5f9;border-radius:99px;height:6px;overflow:hidden;">
                <div style="width:{{ $pp }}%;height:100%;background:{{ $pp >= 80 ? '#10b981' : ($pp >= 60 ? '#f59e0b' : '#ef4444') }};border-radius:99px;"></div>
            </div>
        </div>
        @endforeach
        @endif
        @endif
    </div>
</div>

{{-- ── Horario ─────────────────────────────────────────────────────────── --}}
<div class="prt-card" id="horario" style="margin-bottom:1rem;">
    <div class="prt-card-header">
        <i class="bi bi-calendar-week" style="color:#6366f1;font-size:1rem;"></i>
        <h3>Horario Semanal</h3>
        @if($horarioActivo && !empty($gridHorario))
        <a href="{{ route('portal.padre.hijo.horario.pdf', $estudiante) }}" target="_blank" class="ms-auto"
           style="display:inline-flex;align-items:center;gap:.3rem;background:#dc2626;color:#fff;border-radius:7px;padding:.28rem .7rem;font-size:.72rem;font-weight:600;text-decoration:none;">
            <i class="bi bi-file-earmark-pdf-fill"></i> PDF
        </a>
        @endif
    </div>
    <div class="prt-card-body" style="padding:.5rem;">
        @if($horarioActivo && !empty($gridHorario))
        <div class="table-responsive">
        <table class="sch-table">
            <thead>
                <tr>
                    <th style="width:52px;">Hora</th>
                    @foreach($diasConfig as $dia)
                        <th>{{ ucfirst($dia === 'miercoles' ? 'Mié' : substr($dia, 0, 3)) }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @php $palette = ['#3b82f6','#10b981','#f59e0b','#ef4444','#8b5cf6','#ec4899','#06b6d4','#f97316']; $ci = 0; $cMap = []; @endphp
                @foreach($franjasHorario as $franja)
                    @if($franja->es_recreo)
                        <tr class="sch-recreo"><td colspan="{{ count($diasConfig) + 1 }}"><i class="bi bi-cup-hot me-1"></i>Recreo {{ \Carbon\Carbon::parse($franja->hora_inicio)->format('H:i') }}–{{ \Carbon\Carbon::parse($franja->hora_fin)->format('H:i') }}</td></tr>
                    @else
                        <tr>
                            <td class="franja-col">
                                {{ $franja->nombre ?? 'F'.$franja->numero }}<br>
                                <span style="font-size:.6rem;color:#9ca3af;">{{ \Carbon\Carbon::parse($franja->hora_inicio)->format('H:i') }}</span>
                            </td>
                            @foreach($diasConfig as $dia)
                                <td>
                                    @if(isset($gridHorario[$franja->id][$dia]))
                                        @php
                                            $d   = $gridHorario[$franja->id][$dia];
                                            $aId = $d->asignacion?->asignatura_id ?? 0;
                                            if (!isset($cMap[$aId])) { $cMap[$aId] = $palette[$ci % count($palette)]; $ci++; }
                                        @endphp
                                        <div class="sch-cell" style="background:{{ $cMap[$aId] }};">
                                            {{ Str::limit($d->asignacion?->asignatura?->nombre ?? '—', 14) }}
                                            @php $doc = $d->asignacion?->docente; @endphp
                                            @if($doc)<div style="font-size:.6rem;opacity:.85;">{{ $doc->nombres }}</div>@endif
                                        </div>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endif
                @endforeach
            </tbody>
        </table>
        </div>
        @else
        <div style="text-align:center;padding:2rem;color:#9ca3af;font-size:.84rem;">
            <i class="bi bi-calendar3-week" style="font-size:2rem;display:block;margin-bottom:.5rem;"></i>
            El horario no ha sido publicado aún.
        </div>
        @endif
    </div>
</div>

{{-- ── Observaciones ──────────────────────────────────────────────────── --}}
@if($observaciones->isNotEmpty())
<div class="prt-card" id="observaciones">
    <div class="prt-card-header">
        <i class="bi bi-chat-square-text" style="color:#d97706;font-size:1rem;"></i>
        <h3>Observaciones del Docente</h3>
        <span class="obs-count-badge" style="margin-left:auto;font-size:.75rem;color:#64748b;background:#f1f5f9;border-radius:6px;padding:.2rem .6rem;">
            {{ $observaciones->count() }}
        </span>
    </div>
    <div style="padding:0;">
        @php
            $tiposInfo = [
                'academica'  => ['color' => '#1d4ed8', 'bg' => '#eff6ff',  'icon' => 'bi-book',              'label' => 'Académica'],
                'conductual' => ['color' => '#b45309', 'bg' => '#fef9c3',  'icon' => 'bi-exclamation-triangle','label' => 'Conductual'],
                'positiva'   => ['color' => '#15803d', 'bg' => '#dcfce7',  'icon' => 'bi-hand-thumbs-up',    'label' => 'Positiva'],
                'general'    => ['color' => '#6b7280', 'bg' => '#f1f5f9',  'icon' => 'bi-chat-dots',         'label' => 'General'],
            ];
        @endphp
        @foreach($observaciones as $obs)
        @php $ti = $tiposInfo[$obs->tipo] ?? $tiposInfo['general']; @endphp
        <div class="obs-item-row" style="padding:.85rem 1rem;border-bottom:1px solid #f1f5f9;display:flex;gap:.85rem;align-items:flex-start;">
            <div style="width:34px;height:34px;border-radius:8px;background:{{ $ti['bg'] }};color:{{ $ti['color'] }};display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:.85rem;">
                <i class="bi {{ $ti['icon'] }}"></i>
            </div>
            <div style="flex:1;min-width:0;">
                <div style="display:flex;align-items:center;gap:.5rem;margin-bottom:.25rem;flex-wrap:wrap;">
                    <span style="font-size:.71rem;font-weight:700;color:{{ $ti['color'] }};background:{{ $ti['bg'] }};border-radius:5px;padding:.1rem .4rem;">
                        {{ $ti['label'] }}
                    </span>
                    @if($obs->asignacion?->asignatura)
                    <span style="font-size:.7rem;color:#6366f1;background:#ede9fe;border-radius:5px;padding:.1rem .4rem;">
                        {{ $obs->asignacion->asignatura->nombre }}
                    </span>
                    @endif
                    <span style="font-size:.68rem;color:#9ca3af;margin-left:auto;">
                        {{ $obs->created_at->format('d/m/Y') }}
                    </span>
                </div>
                <div class="obs-item-text" style="font-size:.8rem;color:#374151;line-height:1.5;margin-bottom:.2rem;">{{ $obs->texto }}</div>
                @if($obs->docente)
                <div style="font-size:.68rem;color:#9ca3af;">
                    <i class="bi bi-person me-1"></i>{{ $obs->docente->nombre_completo ?? $obs->docente->nombres }}
                </div>
                @endif
            </div>
        </div>
        @endforeach
    </div>
</div>
@endif

{{-- ── Planificaciones Técnicas Publicadas ────────────────────────────── --}}
@if($planificaciones->isNotEmpty())
<div class="prt-card" id="planificaciones" style="margin-top:.5rem;">
    <div class="prt-card-header">
        <i class="bi bi-journal-text" style="color:#7c3aed;font-size:1rem;"></i>
        <h3>Planificaciones del Área Técnica</h3>
        <a href="{{ route('portal.padre.hijo.planificaciones', $estudiante) }}" class="ms-auto"
           style="font-size:.72rem;color:#7c3aed;text-decoration:none;display:flex;align-items:center;gap:.3rem;">
            Ver todas <i class="bi bi-arrow-right"></i>
        </a>
    </div>
    @foreach($planificaciones as $asignacionId => $planes)
    @php $primera = $planes->first(); $asignatura = $primera?->asignacion?->asignatura; $docente = $primera?->asignacion?->docente; @endphp
    <div style="padding:.7rem 1rem;border-bottom:1px solid var(--prt-border);background:linear-gradient(90deg,rgba(124,58,237,.05) 0%,transparent 100%);">
        <div style="font-size:.83rem;font-weight:800;color:var(--prt-text);display:flex;align-items:center;gap:.5rem;">
            <i class="bi bi-book-fill" style="color:#7c3aed;"></i>
            {{ $asignatura?->nombre ?? '—' }}
            @if($docente)
            <span style="font-size:.7rem;font-weight:400;color:#64748b;">· {{ $docente->nombre_completo }}</span>
            @endif
        </div>
    </div>
    @foreach($planes as $plan)
    <div style="padding:.65rem 1rem 0.65rem 1.5rem;border-bottom:1px solid var(--prt-border);display:flex;gap:.65rem;align-items:flex-start;">
        <div style="flex:1;min-width:0;">
            <div style="display:flex;align-items:center;gap:.4rem;flex-wrap:wrap;margin-bottom:.2rem;">
                @if($plan->tipo === 'ra')
                    <span style="background:#dbeafe;color:#1d4ed8;border-radius:5px;padding:.1rem .4rem;font-size:.68rem;font-weight:700;"><i class="bi bi-bookmark-check me-1"></i>RA</span>
                @else
                    <span style="background:#dcfce7;color:#15803d;border-radius:5px;padding:.1rem .4rem;font-size:.68rem;font-weight:700;"><i class="bi bi-activity me-1"></i>Actividad</span>
                @endif
                <span style="font-size:.8rem;font-weight:700;color:var(--prt-text);">
                    {{ $plan->modulo_nombre ?? $asignatura?->nombre }}
                    @if($plan->mf_codigo)<span style="font-size:.7rem;font-weight:400;color:#64748b;">· {{ $plan->mf_codigo }}</span>@endif
                </span>
            </div>
            @if($plan->fecha_inicio && $plan->fecha_fin)
            <div style="font-size:.72rem;color:#64748b;">
                <i class="bi bi-calendar3 me-1"></i>{{ $plan->fecha_inicio->format('d/m/Y') }} — {{ $plan->fecha_fin->format('d/m/Y') }}
                @if($plan->horas) · {{ $plan->horas }}h @endif
            </div>
            @endif
        </div>
    </div>
    @endforeach
    @endforeach
</div>
@endif

{{-- ── Pagos / Colegiaturas ────────────────────────────────────────────── --}}
@if($resumenPagos !== null)
<div class="prt-card" id="pagos" style="margin-top:.5rem;">
    <div class="prt-card-header" style="background:linear-gradient(135deg,#0f766e,#14b8a6);">
        <i class="bi bi-cash-coin" style="color:#fff;font-size:1rem;"></i>
        <h3 style="color:#fff;margin:0;">Estado de Pagos</h3>
        <a href="{{ route('portal.padre.hijo.boletin', $estudiante) }}" class="ms-auto"
           style="font-size:.72rem;color:rgba(255,255,255,.85);text-decoration:none;display:flex;align-items:center;gap:.3rem;">
            Ver boletín <i class="bi bi-arrow-right"></i>
        </a>
    </div>
    <div class="prt-card-body">
        {{-- Resumen --}}
        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:.6rem;margin-bottom:1rem;">
            <div style="background:#f0fdf4;border-radius:10px;padding:.7rem;text-align:center;">
                <div style="font-size:1.05rem;font-weight:800;color:#065f46;">RD$ {{ number_format($resumenPagos['pagado'],2) }}</div>
                <div style="font-size:.68rem;color:#166534;font-weight:600;">Pagado</div>
            </div>
            <div style="background:{{ $resumenPagos['pendiente'] > 0 ? '#fef9c3' : '#f8fafc' }};border-radius:10px;padding:.7rem;text-align:center;">
                <div style="font-size:1.05rem;font-weight:800;color:{{ $resumenPagos['pendiente'] > 0 ? '#92400e' : '#6b7280' }};">RD$ {{ number_format($resumenPagos['pendiente'],2) }}</div>
                <div style="font-size:.68rem;color:{{ $resumenPagos['pendiente'] > 0 ? '#78350f' : '#9ca3af' }};font-weight:600;">Pendiente</div>
            </div>
            <div style="background:{{ $resumenPagos['vencido'] > 0 ? '#fee2e2' : '#f8fafc' }};border-radius:10px;padding:.7rem;text-align:center;">
                <div style="font-size:1.05rem;font-weight:800;color:{{ $resumenPagos['vencido'] > 0 ? '#991b1b' : '#6b7280' }};">{{ $resumenPagos['vencido'] }}</div>
                <div style="font-size:.68rem;color:{{ $resumenPagos['vencido'] > 0 ? '#7f1d1d' : '#9ca3af' }};font-weight:600;">Vencido(s)</div>
            </div>
        </div>

        {{-- Botón estado de cuenta PDF --}}
        @if($pagosHijo->isNotEmpty())
        <div style="text-align:right;margin-bottom:.6rem;">
            <a href="{{ route('portal.padre.hijo.estado-cuenta', $estudiante) }}" target="_blank"
               style="display:inline-flex;align-items:center;gap:.35rem;background:#dc2626;color:#fff;border-radius:8px;padding:.35rem .85rem;font-size:.75rem;font-weight:600;text-decoration:none;">
                <i class="bi bi-file-earmark-pdf-fill"></i> Estado de Cuenta PDF
            </a>
        </div>
        @endif

        {{-- Lista de pagos --}}
        @if($pagosHijo->isNotEmpty())
        <div style="border:1px solid #e5e7eb;border-radius:10px;overflow:hidden;">
            @foreach($pagosHijo->take(8) as $pg)
            @php
                $bg    = match($pg->estado) { 'pagado'=>'#f0fdf4', 'vencido'=>'#fef2f2', default=>'#fffbeb' };
                $color = match($pg->estado) { 'pagado'=>'#065f46', 'vencido'=>'#991b1b', default=>'#92400e' };
                $label = match($pg->estado) { 'pagado'=>'Pagado', 'vencido'=>'Vencido', 'cancelado'=>'Cancelado', default=>'Pendiente' };
            @endphp
            <div style="display:flex;align-items:center;gap:.75rem;padding:.65rem .85rem;border-bottom:1px solid #f3f4f6;background:{{ $loop->even ? '#fafafa' : '#fff' }};">
                <div style="flex:1;min-width:0;">
                    <div style="font-size:.82rem;font-weight:700;color:#1e293b;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $pg->concepto }}</div>
                    <div style="font-size:.7rem;color:#6b7280;">Vence: {{ $pg->fecha_vencimiento->format('d/m/Y') }}</div>
                </div>
                <div style="font-size:.85rem;font-weight:800;color:#1e293b;white-space:nowrap;">RD$ {{ number_format($pg->monto,2) }}</div>
                <span style="background:{{ $bg }};color:{{ $color }};border-radius:20px;padding:.18rem .55rem;font-size:.68rem;font-weight:700;white-space:nowrap;">{{ $label }}</span>
            </div>
            @endforeach
            @if($pagosHijo->count() > 8)
            <div style="text-align:center;padding:.5rem;font-size:.75rem;color:#6b7280;">
                + {{ $pagosHijo->count() - 8 }} registros más
            </div>
            @endif
        </div>
        @else
        <div style="text-align:center;padding:1.25rem;color:#9ca3af;font-size:.83rem;">
            <i class="bi bi-receipt" style="font-size:1.5rem;display:block;margin-bottom:.4rem;"></i>
            Sin registros de pagos.
        </div>
        @endif
    </div>
</div>
@endif

@endsection
