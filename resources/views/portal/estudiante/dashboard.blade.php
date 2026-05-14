@extends('layouts.portal')

@section('page-title', 'Mi Portal — ' . ($estudiante->nombre_completo ?? 'Estudiante'))
@section('portal-name', 'Portal del Estudiante')

@section('sidebar')
    @include('portal.estudiante._sidebar', ['activeKey' => 'dashboard'])
    {{-- Anclas adicionales del dashboard --}}
    <div class="prt-sidebar-section mt-2">En esta página</div>
    <a href="#mis-materias" class="prt-sidebar-link"><i class="bi bi-journals"></i>Mis Materias</a>
    <a href="#mis-notas" class="prt-sidebar-link"><i class="bi bi-journal-check"></i>Mis Notas</a>
    <a href="#mi-asistencia" class="prt-sidebar-link"><i class="bi bi-calendar-check"></i>Asistencia</a>
    <a href="#mi-horario" class="prt-sidebar-link"><i class="bi bi-calendar-week"></i>Mi Horario</a>
    <a href="#noticias" class="prt-sidebar-link"><i class="bi bi-megaphone"></i>Noticias</a>
    <a href="#notificaciones" class="prt-sidebar-link"><i class="bi bi-bell"></i>Notificaciones</a>
@endsection

@section('bottom-nav')
    <a href="{{ route('portal.estudiante.dashboard') }}" class="prt-nav-item active">
        <i class="bi bi-house-fill"></i>Inicio
    </a>
    <a href="#mis-notas" class="prt-nav-item">
        <i class="bi bi-journal-check"></i>Notas
    </a>
    <a href="#mi-asistencia" class="prt-nav-item">
        <i class="bi bi-calendar-check"></i>Asistencia
    </a>
    <a href="#mi-horario" class="prt-nav-item">
        <i class="bi bi-calendar-week"></i>Horario
    </a>
    <a href="#notificaciones" class="prt-nav-item">
        <i class="bi bi-bell"></i>Notif.
    </a>
@endsection

@section('content')

{{-- ── Hero personal ─────────────────────────────────────────────── --}}
<div style="background:linear-gradient(135deg,#1e3a5f 0%,#2563eb 100%);border-radius:14px;padding:1.25rem 1.5rem;color:#fff;margin-bottom:1rem;display:flex;align-items:center;gap:1rem;position:relative;overflow:hidden;">
    <div style="position:absolute;right:-20px;top:-20px;width:120px;height:120px;background:rgba(255,255,255,.06);border-radius:50%;"></div>
    <div style="width:54px;height:54px;border-radius:50%;background:rgba(255,255,255,.18);border:2px solid rgba(255,255,255,.3);display:flex;align-items:center;justify-content:center;font-size:1.3rem;font-weight:900;flex-shrink:0;">
        {{ strtoupper(substr($estudiante->nombres ?? 'E', 0, 1)) }}
    </div>
    <div>
        <div style="font-size:1.05rem;font-weight:800;margin-bottom:.2rem;">{{ $estudiante->nombre_completo }}</div>
        <div style="font-size:.78rem;color:rgba(255,255,255,.75);">
            <i class="bi bi-mortarboard me-1"></i>
            {{ $matricula?->grupo?->nombre_completo ?? 'Sin grupo asignado' }}
            @if($schoolYear) &nbsp;·&nbsp; {{ $schoolYear->nombre }} @endif
        </div>
    </div>
    @if($promedioGeneral !== null)
    <div style="margin-left:auto;text-align:center;background:rgba(255,255,255,.15);border-radius:10px;padding:.6rem .9rem;">
        <div style="font-size:1.6rem;font-weight:900;line-height:1;">{{ $promedioGeneral }}</div>
        <div style="font-size:.65rem;color:rgba(255,255,255,.7);text-transform:uppercase;letter-spacing:.05em;">Promedio</div>
    </div>
    @endif
</div>

{{-- ── Stats ────────────────────────────────────────────────────────── --}}
<div class="prt-stats">
    <div class="prt-stat">
        <div class="prt-stat-icon" style="background:#dbeafe;color:#1d4ed8;"><i class="bi bi-journal-check"></i></div>
        <div>
            <div class="prt-stat-val">{{ $promedioGeneral ?? '—' }}</div>
            <div class="prt-stat-lbl">Promedio</div>
        </div>
    </div>
    <div class="prt-stat">
        <div class="prt-stat-icon" style="background:#dcfce7;color:#15803d;"><i class="bi bi-calendar-check"></i></div>
        <div>
            <div class="prt-stat-val">{{ $resumenAsistencia['porcentaje'] !== null ? $resumenAsistencia['porcentaje'] . '%' : '—' }}</div>
            <div class="prt-stat-lbl">Asistencia</div>
        </div>
    </div>
    <div class="prt-stat">
        <div class="prt-stat-icon" style="background:#fef9c3;color:#854d0e;"><i class="bi bi-x-circle"></i></div>
        <div>
            <div class="prt-stat-val" style="{{ $resumenAsistencia['ausentes'] > 0 ? 'color:#dc2626;' : '' }}">{{ $resumenAsistencia['ausentes'] }}</div>
            <div class="prt-stat-lbl">Ausencias</div>
        </div>
    </div>
    <div class="prt-stat">
        <div class="prt-stat-icon" style="background:#f3e8ff;color:#7e22ce;"><i class="bi bi-chat-square-text"></i></div>
        <div>
            <div class="prt-stat-val">{{ $observaciones->count() }}</div>
            <div class="prt-stat-lbl">Observaciones</div>
        </div>
    </div>
</div>

{{-- ── Mis Notas ────────────────────────────────────────────────────── --}}
<div class="prt-card" id="mis-notas">
    <div class="prt-card-header">
        <i class="bi bi-journal-check" style="color:#2563eb;font-size:1rem;"></i>
        <h3>Mis Calificaciones</h3>
        <div class="ms-auto d-flex gap-2">
            <a href="{{ route('portal.estudiante.notas.pdf') }}" target="_blank"
               style="display:inline-flex;align-items:center;gap:.3rem;background:#dc2626;color:#fff;border-radius:7px;padding:.28rem .7rem;font-size:.72rem;font-weight:600;text-decoration:none;">
                <i class="bi bi-file-earmark-pdf-fill"></i> PDF
            </a>
            <a href="{{ route('portal.estudiante.notas.excel') }}"
               style="display:inline-flex;align-items:center;gap:.3rem;background:#166534;color:#fff;border-radius:7px;padding:.28rem .7rem;font-size:.72rem;font-weight:600;text-decoration:none;">
                <i class="bi bi-file-earmark-excel-fill"></i> Excel
            </a>
        </div>
    </div>
    <div class="prt-card-body">
        @if($calificacionesAcademicas->isNotEmpty())
            <p style="font-size:.75rem;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.05em;margin-bottom:.65rem;">
                Área Académica
            </p>
            <div style="display:flex;flex-direction:column;gap:.5rem;margin-bottom:1rem;">
                @foreach($calificacionesAcademicas as $c)
                @php
                    $letra = $c->nota_final >= 90 ? 'A' : ($c->nota_final >= 80 ? 'B' : ($c->nota_final >= 70 ? 'C' : ($c->nota_final >= 60 ? 'D' : 'F')));
                    $letraClass = 'nota-' . strtolower($letra);
                @endphp
                <div class="dm-note-row" style="display:flex;align-items:center;gap:.75rem;padding:.5rem .75rem;background:#f8fafc;border-radius:9px;">
                    <div class="dm-text-primary" style="flex:1;font-size:.83rem;font-weight:600;">{{ $c->asignacion?->asignatura?->nombre ?? '—' }}</div>
                    <span class="nota-badge {{ $letraClass }}">{{ $c->nota_final ?? '—' }}</span>
                    <span style="font-size:.72rem;font-weight:700;color:#6b7280;">{{ $letra }}</span>
                </div>
                @endforeach
            </div>
        @endif

        @if($calificaciones->isNotEmpty())
            @foreach($periodos as $periodo)
                @php $calsPeriodo = $calificaciones->get($periodo->id, collect()); @endphp
                @if($calsPeriodo->isNotEmpty())
                <p style="font-size:.75rem;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.05em;margin-bottom:.65rem;">
                    {{ $periodo->nombre }}
                </p>
                <div style="display:flex;flex-direction:column;gap:.5rem;margin-bottom:1rem;">
                    @foreach($calsPeriodo as $c)
                    @php
                        $letra = $c->nota_final >= 90 ? 'A' : ($c->nota_final >= 80 ? 'B' : ($c->nota_final >= 70 ? 'C' : ($c->nota_final >= 60 ? 'D' : 'F')));
                        $letraClass = 'nota-' . strtolower($letra);
                    @endphp
                    <div style="display:flex;align-items:center;gap:.75rem;padding:.5rem .75rem;background:#f8fafc;border-radius:9px;">
                        <div style="flex:1;font-size:.83rem;font-weight:600;">{{ $c->asignacion?->asignatura?->nombre ?? '—' }}</div>
                        <span class="nota-badge {{ $letraClass }}">{{ $c->nota_final ?? '—' }}</span>
                        <span style="font-size:.72rem;font-weight:700;color:#6b7280;">{{ $letra }}</span>
                    </div>
                    @endforeach
                </div>
                @endif
            @endforeach
        @endif

        @if($calificaciones->isEmpty() && $calificacionesAcademicas->isEmpty())
            <div style="text-align:center;padding:2rem 1rem;color:#9ca3af;font-size:.84rem;">
                <i class="bi bi-journal" style="font-size:2rem;display:block;margin-bottom:.5rem;"></i>
                No hay calificaciones publicadas aún.
            </div>
        @endif
    </div>
</div>

{{-- ── Asistencia ───────────────────────────────────────────────────── --}}
<div class="prt-card" id="mi-asistencia">
    <div class="prt-card-header">
        <i class="bi bi-calendar-check" style="color:#10b981;font-size:1rem;"></i>
        <h3>Mi Asistencia</h3>
    </div>
    <div class="prt-card-body">
        @if($resumenAsistencia['total'] > 0)
            {{-- Barra de progreso --}}
            @php $pct = $resumenAsistencia['porcentaje']; $barColor = $pct >= 80 ? '#10b981' : ($pct >= 60 ? '#f59e0b' : '#ef4444'); @endphp
            <div style="margin-bottom:1rem;">
                <div style="display:flex;justify-content:space-between;margin-bottom:.3rem;font-size:.8rem;font-weight:600;">
                    <span>{{ $pct }}% asistencia</span>
                    <span>{{ $resumenAsistencia['presentes'] }}/{{ $resumenAsistencia['total'] }} clases</span>
                </div>
                <div class="dm-progress-bg" style="background:#e2e8f0;border-radius:10px;height:10px;overflow:hidden;">
                    <div style="width:{{ $pct }}%;background:{{ $barColor }};height:100%;border-radius:10px;transition:width .4s;"></div>
                </div>
            </div>
            <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:.5rem;">
                <div class="dm-att-present" style="text-align:center;background:#f0fdf4;border-radius:9px;padding:.65rem .5rem;">
                    <div style="font-size:1.2rem;font-weight:900;color:#15803d;">{{ $resumenAsistencia['presentes'] }}</div>
                    <div class="dm-text-muted" style="font-size:.68rem;color:#6b7280;">Presentes</div>
                </div>
                <div class="dm-att-absent" style="text-align:center;background:#fef2f2;border-radius:9px;padding:.65rem .5rem;">
                    <div style="font-size:1.2rem;font-weight:900;color:#991b1b;">{{ $resumenAsistencia['ausentes'] }}</div>
                    <div class="dm-text-muted" style="font-size:.68rem;color:#6b7280;">Ausencias</div>
                </div>
                <div class="dm-att-late" style="text-align:center;background:#fffbeb;border-radius:9px;padding:.65rem .5rem;">
                    <div style="font-size:1.2rem;font-weight:900;color:#92400e;">{{ $resumenAsistencia['tardanzas'] }}</div>
                    <div class="dm-text-muted" style="font-size:.68rem;color:#6b7280;">Tardanzas</div>
                </div>
            </div>
            {{-- Desglose por materia --}}
            @if(!empty($resumenAsistencia['por_materia']) && count($resumenAsistencia['por_materia']) > 1)
            <div style="margin-top:.85rem;border-top:1px solid var(--prt-border);padding-top:.75rem;">
                <div style="font-size:.72rem;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.05em;margin-bottom:.5rem;">Por Materia</div>
                @foreach($resumenAsistencia['por_materia'] as $pm)
                @php $pp = $pm['porcentaje'] ?? 0; @endphp
                <div style="margin-bottom:.45rem;">
                    <div style="display:flex;justify-content:space-between;font-size:.74rem;color:#374151;margin-bottom:.18rem;">
                        <span style="font-weight:600;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:65%;">{{ $pm['asignatura'] }}</span>
                        <span style="font-weight:700;color:{{ $pp >= 80 ? '#15803d' : ($pp >= 60 ? '#d97706' : '#dc2626') }};">{{ $pp }}%</span>
                    </div>
                    <div style="background:#f1f5f9;border-radius:99px;height:5px;overflow:hidden;">
                        <div style="width:{{ $pp }}%;height:100%;background:{{ $pp >= 80 ? '#10b981' : ($pp >= 60 ? '#f59e0b' : '#ef4444') }};border-radius:99px;"></div>
                    </div>
                </div>
                @endforeach
            </div>
            @endif
        @else
            <div style="text-align:center;padding:1.5rem;color:#9ca3af;font-size:.84rem;">
                <i class="bi bi-calendar3" style="font-size:2rem;display:block;margin-bottom:.5rem;"></i>
                No hay registros de asistencia.
            </div>
        @endif
    </div>
</div>

{{-- ── Mis Materias ─────────────────────────────────────────────────── --}}
@if($asignaciones->isNotEmpty())
<div class="prt-card" id="mis-materias">
    <div class="prt-card-header">
        <i class="bi bi-journals" style="color:#6366f1;font-size:1rem;"></i>
        <h3>Mis Materias</h3>
    </div>
    <div style="padding:.75rem;display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:.6rem;">
    @php
        $colores = ['#6366f1','#2563eb','#10b981','#f59e0b','#ef4444','#ec4899','#06b6d4','#8b5cf6'];
    @endphp
    @foreach($asignaciones as $asig)
    @php $color = $colores[$loop->index % count($colores)]; @endphp
    <div style="border:1px solid {{ $color }}25;border-radius:12px;overflow:hidden;background:var(--prt-card);">
        <div style="background:{{ $color }}12;padding:.6rem .85rem;border-bottom:1px solid {{ $color }}20;display:flex;align-items:center;gap:.5rem;">
            <i class="bi bi-book-fill" style="color:{{ $color }};font-size:.85rem;"></i>
            <div style="flex:1;min-width:0;">
                <div style="font-size:.8rem;font-weight:800;color:var(--prt-text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                    {{ $asig->asignatura?->nombre ?? '—' }}
                </div>
                @if($asig->docente)
                <div style="font-size:.65rem;color:var(--prt-muted);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                    {{ $asig->docente->nombre_completo }}
                </div>
                @endif
            </div>
        </div>
        <div style="display:flex;gap:0;">
            <a href="{{ route('portal.estudiante.recursos', $asig) }}"
               style="flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:.2rem;padding:.55rem .35rem;text-decoration:none;color:{{ $color }};font-size:.65rem;font-weight:700;border-right:1px solid {{ $color }}15;transition:background .15s;"
               onmouseover="this.style.background='{{ $color }}10'" onmouseout="this.style.background=''">
                <i class="bi bi-folder-fill" style="font-size:1rem;"></i>Recursos
            </a>
            <a href="{{ route('portal.estudiante.boletin') }}"
               style="flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:.2rem;padding:.55rem .35rem;text-decoration:none;color:#1d4ed8;font-size:.65rem;font-weight:700;transition:background .15s;"
               onmouseover="this.style.background='#1d4ed810'" onmouseout="this.style.background=''">
                <i class="bi bi-file-earmark-text" style="font-size:1rem;"></i>Boletín
            </a>
        </div>
    </div>
    @endforeach
    </div>
</div>
@endif

{{-- ── Horario semanal ──────────────────────────────────────────────── --}}
<div class="prt-card" id="mi-horario">
    <div class="prt-card-header">
        <i class="bi bi-calendar-week" style="color:#6366f1;font-size:1rem;"></i>
        <h3>Mi Horario Semanal</h3>
        @if($horarioActivo && !empty($gridHorario))
        <a href="{{ route('portal.estudiante.horario.pdf') }}" target="_blank" class="ms-auto"
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
                    @php $palette = ['#3b82f6','#10b981','#f59e0b','#ef4444','#8b5cf6','#ec4899','#06b6d4','#84cc16','#f97316','#6366f1']; $ci = 0; $colorMap = []; @endphp
                    @foreach($franjasHorario as $franja)
                        @if($franja->es_recreo)
                            <tr class="sch-recreo"><td colspan="{{ count($diasConfig) + 1 }}"><i class="bi bi-cup-hot me-1"></i>Recreo {{ \Carbon\Carbon::parse($franja->hora_inicio)->format('H:i') }} – {{ \Carbon\Carbon::parse($franja->hora_fin)->format('H:i') }}</td></tr>
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
                                                $d = $gridHorario[$franja->id][$dia];
                                                $asigId = $d->asignacion?->asignatura_id ?? 0;
                                                if (!isset($colorMap[$asigId])) { $colorMap[$asigId] = $palette[$ci % count($palette)]; $ci++; }
                                            @endphp
                                            <div class="sch-cell" style="background:{{ $colorMap[$asigId] }};">
                                                {{ Str::limit($d->asignacion?->asignatura?->nombre ?? '—', 16) }}
                                                @if($d->aula)<div style="font-size:.6rem;opacity:.8;">{{ $d->aula->nombre }}</div>@endif
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
                El horario no está disponible aún.
            </div>
        @endif
    </div>
</div>

{{-- ── ZuraClass — Tareas Pendientes ───────────────────────────────── --}}
@if(!empty($zuraClasesData) && ($zuraClasesData['totalPendientes'] > 0 || $zuraClasesData['tareasVencidas'] > 0))
<div class="prt-card" style="margin-bottom:1.25rem;">
    <div class="prt-card-header" style="background:linear-gradient(90deg,rgba(79,70,229,.06) 0%,transparent 100%);">
        <i class="bi bi-easel2-fill" style="color:#4f46e5;font-size:1rem;"></i>
        <h3>ZuraClass — Tareas Pendientes</h3>
        <div style="margin-left:auto;display:flex;gap:.5rem;align-items:center;">
            @if($zuraClasesData['tareasVencidas'] > 0)
            <span style="background:#fee2e2;color:#dc2626;font-size:.7rem;font-weight:700;padding:.15rem .55rem;border-radius:99px;">
                {{ $zuraClasesData['tareasVencidas'] }} vencida(s)
            </span>
            @endif
            <a href="{{ route('portal.estudiante.classroom.index') }}"
               style="font-size:.75rem;color:#4f46e5;text-decoration:none;font-weight:600;">
                Ver aulas <i class="bi bi-arrow-right"></i>
            </a>
        </div>
    </div>
    <div class="prt-card-body" style="padding:0;">
        @foreach($zuraClasesData['tareasPendientes'] as $tarea)
        @php $urgente = $tarea['fecha_limite'] && $tarea['fecha_limite']->diffInDays(now()) <= 2; @endphp
        <div style="padding:.75rem 1rem;border-bottom:1px solid #f1f5f9;display:flex;gap:.75rem;align-items:center;">
            <div style="width:36px;height:36px;background:{{ $urgente?'#fee2e2':'#eef2ff' }};border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="bi bi-pencil-fill" style="color:{{ $urgente?'#dc2626':'#4f46e5' }};font-size:.85rem;"></i>
            </div>
            <div style="flex:1;min-width:0;">
                <div style="font-weight:600;font-size:.88rem;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $tarea['titulo'] }}</div>
                <div style="font-size:.75rem;color:#64748b;">{{ $tarea['asignatura'] }}</div>
            </div>
            <div style="text-align:right;flex-shrink:0;">
                @if($tarea['fecha_limite'])
                <div style="font-size:.72rem;font-weight:700;color:{{ $urgente?'#dc2626':'#92400e' }};">
                    {{ $tarea['fecha_limite']->format('d/m') }}
                </div>
                <div style="font-size:.65rem;color:{{ $urgente?'#dc2626':'#94a3b8' }};">
                    {{ $tarea['fecha_limite']->diffForHumans() }}
                </div>
                @endif
            </div>
        </div>
        @endforeach
        @if($zuraClasesData['totalPendientes'] > 5)
        <div style="padding:.6rem 1rem;text-align:center;font-size:.78rem;color:#4f46e5;font-weight:600;">
            <a href="{{ route('portal.estudiante.classroom.index') }}" style="text-decoration:none;color:inherit;">
                Ver {{ $zuraClasesData['totalPendientes'] - 5 }} tarea(s) más <i class="bi bi-arrow-right"></i>
            </a>
        </div>
        @endif
    </div>
</div>
@endif

{{-- ── Observaciones del docente ────────────────────────────────────── --}}
@if($observaciones->isNotEmpty())
<div class="prt-card">
    <div class="prt-card-header">
        <i class="bi bi-chat-square-text" style="color:#f59e0b;font-size:1rem;"></i>
        <h3>Observaciones del Docente</h3>
    </div>
    <div class="prt-card-body" style="padding:0;">
        @foreach($observaciones as $obs)
        @php $info = $obs->tipo_info; @endphp
        <div style="padding:.75rem 1rem;border-bottom:1px solid #f1f5f9;display:flex;gap:.75rem;align-items:flex-start;">
            <div style="width:30px;height:30px;border-radius:50%;background:{{ $info['color'] }}20;color:{{ $info['color'] }};display:flex;align-items:center;justify-content:center;font-size:.8rem;flex-shrink:0;">
                <i class="{{ $info['icon'] }}"></i>
            </div>
            <div style="flex:1;">
                <div style="font-size:.78rem;font-weight:700;color:{{ $info['color'] }};">{{ $info['label'] }} · {{ $obs->asignacion?->asignatura?->nombre }}</div>
                <div style="font-size:.8rem;color:#374151;margin:.2rem 0;">{{ $obs->texto }}</div>
                <div style="font-size:.68rem;color:#9ca3af;">{{ $obs->docente?->nombre_completo }} · {{ $obs->created_at->diffForHumans() }}</div>
            </div>
        </div>
        @endforeach
    </div>
</div>
@endif

{{-- ── Noticias ─────────────────────────────────────────────────────── --}}
@if($comunicados->isNotEmpty())
<div class="prt-card" id="noticias">
    <div class="prt-card-header">
        <i class="bi bi-megaphone" style="color:#3b82f6;font-size:1rem;"></i>
        <h3>Noticias del Centro</h3>
    </div>
    <div class="prt-card-body" style="padding:0;">
        @foreach($comunicados as $com)
        <div style="padding:.8rem 1rem;border-bottom:1px solid #f1f5f9;">
            <div style="font-size:.84rem;font-weight:700;color:#1e293b;margin-bottom:.25rem;">{{ $com->titulo }}</div>
            <div style="font-size:.77rem;color:#64748b;line-height:1.5;">{{ Str::limit(strip_tags($com->cuerpo), 120) }}</div>
            <div style="display:flex;justify-content:space-between;align-items:center;margin-top:.3rem;">
                <span style="font-size:.68rem;color:#9ca3af;">{{ $com->published_at?->format('d/m/Y') }}</span>
                <button onclick="verComunicado({{ $com->id }}, {{ json_encode($com->titulo) }}, {{ json_encode($com->cuerpo) }}, {{ json_encode($com->published_at?->format('d/m/Y') ?? '') }})"
                        style="background:none;border:none;color:#3b82f6;font-size:.72rem;font-weight:600;cursor:pointer;padding:0;">
                    Leer completo →
                </button>
            </div>
        </div>
        @endforeach
    </div>
</div>

{{-- Modal comunicado --}}
<div id="modalComunicado" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,.5);align-items:center;justify-content:center;padding:1rem;">
    <div style="background:#fff;border-radius:14px;max-width:540px;width:100%;max-height:80vh;overflow:hidden;display:flex;flex-direction:column;box-shadow:0 20px 60px rgba(0,0,0,.3);">
        <div style="padding:1rem 1.25rem;border-bottom:1px solid #e5e7eb;display:flex;justify-content:space-between;align-items:center;">
            <div style="font-weight:700;font-size:.95rem;color:#1e293b;" id="comTitulo"></div>
            <button onclick="cerrarComunicado()" style="background:none;border:none;font-size:1.2rem;color:#6b7280;cursor:pointer;line-height:1;">×</button>
        </div>
        <div style="padding:1rem 1.25rem;overflow-y:auto;flex:1;">
            <div style="font-size:.77rem;color:#6b7280;margin-bottom:.75rem;" id="comFecha"></div>
            <div style="font-size:.88rem;color:#374151;line-height:1.7;white-space:pre-line;" id="comCuerpo"></div>
        </div>
    </div>
</div>
<script>
function verComunicado(id, titulo, cuerpo, fecha) {
    document.getElementById('comTitulo').textContent = titulo;
    document.getElementById('comFecha').textContent = fecha;
    document.getElementById('comCuerpo').textContent = cuerpo.replace(/<[^>]*>/g,'');
    document.getElementById('modalComunicado').style.display = 'flex';
}
function cerrarComunicado() {
    document.getElementById('modalComunicado').style.display = 'none';
}
document.getElementById('modalComunicado').addEventListener('click', function(e){
    if(e.target === this) cerrarComunicado();
});
</script>
@endif

{{-- ── Notificaciones ───────────────────────────────────────────────── --}}
<div class="prt-card" id="notificaciones">
    <div class="prt-card-header" style="justify-content:space-between;">
        <div style="display:flex;align-items:center;gap:.6rem;">
            <i class="bi bi-bell" style="color:#6366f1;font-size:1rem;"></i>
            <h3>Notificaciones</h3>
        </div>
        <div style="display:flex;gap:.5rem;margin-left:auto;">
            @if($totalNoLeidas > 0)
            <button onclick="marcarTodasLeidas()" class="btn btn-sm"
                    style="font-size:.72rem;background:#eff6ff;color:#1d4ed8;border-radius:7px;border:1px solid #bfdbfe;">
                <i class="bi bi-check-all me-1"></i>Leídas
            </button>
            @endif
            <a href="{{ route('portal.estudiante.notificaciones') }}"
               style="font-size:.72rem;background:#f1f5f9;color:#374151;border-radius:7px;border:1px solid #e5e7eb;padding:.25rem .6rem;text-decoration:none;display:flex;align-items:center;gap:.25rem;">
                <i class="bi bi-list-ul"></i>Ver todas
            </a>
        </div>
    </div>
    <ul class="notif-list">
        @forelse($notificaciones as $notif)
        <li class="notif-item {{ $notif->leida ? '' : 'unread' }}" data-id="{{ $notif->id }}">
            <span class="notif-dot" style="background:{{ $notif->color }};"></span>
            <div class="notif-icon" style="background:{{ $notif->color }}20;color:{{ $notif->color }};">
                <i class="bi {{ $notif->icono }}"></i>
            </div>
            <div style="flex:1;">
                <div class="notif-titulo">{{ $notif->titulo }}</div>
                <div class="notif-msg">{{ $notif->mensaje }}</div>
                <div class="notif-time">{{ $notif->created_at->diffForHumans() }}</div>
            </div>
        </li>
        @empty
        <li style="padding:2rem;text-align:center;color:#9ca3af;font-size:.84rem;">
            <i class="bi bi-bell-slash" style="font-size:1.5rem;display:block;margin-bottom:.5rem;"></i>
            Sin notificaciones por el momento.
        </li>
        @endforelse
    </ul>
</div>

{{-- ── Próximos eventos del calendario ─────────────────────────────── --}}
@if($eventosCalendario->isNotEmpty())
<div class="prt-card" id="eventos" style="margin-top:.75rem;">
    <div class="prt-card-header">
        <i class="bi bi-calendar-event" style="color:#6366f1;font-size:1rem;"></i>
        <h3>Próximos Eventos</h3>
    </div>
    <div class="prt-card-body" style="padding:0;">
        @foreach($eventosCalendario as $ev)
        @php
            $daysLeft = today()->diffInDays($ev->fecha_inicio, false);
            $color    = $ev->color ?? '#6366f1';
        @endphp
        <div style="display:flex;align-items:flex-start;gap:.85rem;padding:.75rem 1rem;border-bottom:1px solid var(--prt-border);">
            <div style="width:42px;height:42px;border-radius:10px;background:{{ $color }}18;display:flex;flex-direction:column;align-items:center;justify-content:center;flex-shrink:0;border:1.5px solid {{ $color }}35;">
                <div style="font-size:.95rem;font-weight:900;color:{{ $color }};line-height:1;">{{ $ev->fecha_inicio->format('d') }}</div>
                <div style="font-size:.55rem;font-weight:700;color:{{ $color }};text-transform:uppercase;letter-spacing:.04em;">{{ $ev->fecha_inicio->translatedFormat('M') }}</div>
            </div>
            <div style="flex:1;min-width:0;">
                <div style="font-size:.84rem;font-weight:700;color:var(--prt-text);">{{ $ev->titulo }}</div>
                @if($ev->descripcion)
                <div style="font-size:.72rem;color:var(--prt-muted);margin-top:.1rem;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $ev->descripcion }}</div>
                @endif
            </div>
            <div style="font-size:.7rem;font-weight:600;color:{{ $daysLeft === 0 ? '#dc2626' : ($daysLeft <= 3 ? '#d97706' : '#6b7280') }};white-space:nowrap;flex-shrink:0;">
                {{ $daysLeft === 0 ? 'Hoy' : ($daysLeft === 1 ? 'Mañana' : "En {$daysLeft} días") }}
            </div>
        </div>
        @endforeach
    </div>
</div>
@endif

@endsection

@push('scripts')
<script>
async function marcarTodasLeidas() {
    await fetch('{{ route("portal.estudiante.notif.leer-todas") }}', {
        method: 'POST',
        headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json'},
    });
    document.querySelectorAll('.notif-item.unread').forEach(el => el.classList.remove('unread'));
    document.querySelector('.prt-badge')?.remove();
}
</script>
@endpush

@push('realtime-data')
@if($matricula?->grupo_id)
<script>
window._SGE_GRUPO_IDS = [{{ $matricula->grupo_id }}];
</script>
@endif
@endpush
