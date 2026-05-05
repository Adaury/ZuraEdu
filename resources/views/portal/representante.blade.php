<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal del Representante — {{ $estudiante->nombre_completo }}</title>

    
    
    
    <link href="/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="/vendor/bootstrap-icons/bootstrap-icons.min.css" rel="stylesheet">

    <style>
        :root {
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --success: #16a34a;
            --danger:  #dc2626;
            --warning: #d97706;
        }
        * { box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background: #f1f5f9;
            color: #1e293b;
            min-height: 100vh;
        }

        /* ── Top header ─────────────────────────────────── */
        .portal-header {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            padding: 1rem 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            box-shadow: 0 4px 20px rgba(0,0,0,.2);
        }
        .portal-header .logo-box {
            width: 42px; height: 42px;
            background: var(--primary);
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-weight: 800; color: #fff; font-size: .9rem;
            flex-shrink: 0;
        }
        .portal-header .title { color: #fff; font-weight: 700; font-size: 1rem; line-height: 1.2; }
        .portal-header .sub   { color: rgba(255,255,255,.6); font-size: .75rem; }
        .portal-badge {
            margin-left: auto;
            background: rgba(37,99,235,.3);
            border: 1px solid rgba(37,99,235,.5);
            color: #93c5fd;
            border-radius: 20px;
            padding: .3rem .85rem;
            font-size: .73rem;
            font-weight: 600;
        }

        /* ── Student card ──────────────────────────────── */
        .student-hero {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            padding: 2rem 1.5rem;
            color: #fff;
            position: relative;
            overflow: hidden;
        }
        .student-hero::before {
            content: '';
            position: absolute; top: -30px; right: -30px;
            width: 150px; height: 150px;
            background: rgba(255,255,255,.07);
            border-radius: 50%;
        }
        .student-hero::after {
            content: '';
            position: absolute; bottom: -50px; right: 60px;
            width: 200px; height: 200px;
            background: rgba(255,255,255,.05);
            border-radius: 50%;
        }
        .student-avatar {
            width: 72px; height: 72px;
            border-radius: 50%;
            border: 3px solid rgba(255,255,255,.4);
            object-fit: cover;
        }
        .student-avatar-fallback {
            width: 72px; height: 72px;
            border-radius: 50%;
            background: rgba(255,255,255,.2);
            display: flex; align-items: center; justify-content: center;
            font-size: 1.6rem; font-weight: 700;
            border: 3px solid rgba(255,255,255,.3);
            flex-shrink: 0;
        }

        /* ── Tabs ──────────────────────────────────────── */
        .portal-tabs {
            background: #fff;
            border-bottom: 1px solid #e2e8f0;
            padding: 0 1.5rem;
            display: flex;
            gap: 0;
            overflow-x: auto;
        }
        .portal-tab {
            padding: .85rem 1.25rem;
            font-size: .84rem;
            font-weight: 600;
            color: #6b7280;
            border-bottom: 2px solid transparent;
            cursor: pointer;
            white-space: nowrap;
            transition: color .15s, border-color .15s;
            text-decoration: none;
        }
        .portal-tab.active, .portal-tab:hover { color: var(--primary); border-bottom-color: var(--primary); }

        /* ── Content ───────────────────────────────────── */
        .portal-body { padding: 1.5rem; max-width: 900px; margin: 0 auto; }

        .section-card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 2px 12px rgba(0,0,0,.06);
            margin-bottom: 1.25rem;
            overflow: hidden;
        }
        .section-card-header {
            padding: 1rem 1.25rem;
            border-bottom: 1px solid #f0f4f8;
            display: flex; align-items: center; gap: .75rem;
        }
        .section-card-header .icon {
            width: 32px; height: 32px;
            border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            font-size: .9rem;
            flex-shrink: 0;
        }
        .section-card-header strong { font-size: .95rem; }
        .section-card-body { padding: 1.25rem; }

        /* Nota badge */
        .nota-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 44px; height: 44px;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 800;
        }
        .nota-A { background: #dcfce7; color: #15803d; }
        .nota-B { background: #dbeafe; color: #1d4ed8; }
        .nota-C { background: #fef3c7; color: #92400e; }
        .nota-D { background: #fed7aa; color: #9a3412; }
        .nota-F { background: #fee2e2; color: #991b1b; }
        .nota-N { background: #f3f4f6; color: #6b7280; }

        /* Asistencia progress */
        .asist-bar { height: 8px; border-radius: 4px; background: #e2e8f0; overflow: hidden; }
        .asist-bar-fill { height: 100%; border-radius: 4px; transition: width .5s ease; }

        /* Period pills */
        .period-pill {
            display: inline-flex; align-items: center;
            padding: .3rem .85rem;
            border-radius: 20px;
            font-size: .75rem; font-weight: 700;
            cursor: pointer;
            border: 2px solid transparent;
            transition: all .15s;
        }
        .period-pill.active { background: var(--primary); color: #fff; border-color: var(--primary); }
        .period-pill:not(.active) { background: #f1f5f9; color: #64748b; border-color: #e2e8f0; }
        .period-pill:not(.active):hover { border-color: var(--primary); color: var(--primary); }

        /* Empty state */
        .empty-portal {
            text-align: center; padding: 3rem 1.5rem; color: #9ca3af;
        }
        .empty-portal i { font-size: 2.5rem; margin-bottom: 1rem; display: block; }

        /* Tab content visibility */
        .tab-pane { display: none; }
        .tab-pane.active { display: block; }

        /* Footer */
        .portal-footer {
            text-align: center;
            padding: 1.5rem;
            font-size: .76rem;
            color: #9ca3af;
            border-top: 1px solid #e2e8f0;
            margin-top: 2rem;
        }

        @media (max-width: 576px) {
            .student-hero { padding: 1.5rem 1rem; }
            .portal-body  { padding: 1rem; }
        }
    </style>
</head>
<body>

{{-- ── Header ──────────────────────────────────────────────── --}}
<header class="portal-header">
    <div class="logo-box">PSAC</div>
    <div>
        <div class="title">Portal del Representante</div>
        <div class="sub">Politécnico Salesiano Arquides Calderón</div>
    </div>
    <span class="portal-badge d-none d-sm-inline">
        <i class="bi bi-shield-lock me-1"></i>Enlace seguro
    </span>
</header>

{{-- ── Student Hero ─────────────────────────────────────────── --}}
<div class="student-hero">
    <div class="d-flex align-items-center gap-3" style="position:relative;z-index:1;">
        @if($estudiante->foto)
            <img src="{{ asset('storage/'.$estudiante->foto) }}" alt="" class="student-avatar">
        @else
            <div class="student-avatar-fallback">
                {{ strtoupper(substr($estudiante->nombres, 0, 1) . substr($estudiante->apellidos, 0, 1)) }}
            </div>
        @endif
        <div>
            <div style="font-size:1.3rem;font-weight:800;line-height:1.2;">{{ $estudiante->apellidos }}, {{ $estudiante->nombres }}</div>
            @if($matricula)
            <div style="font-size:.85rem;color:rgba(255,255,255,.75);margin-top:.3rem;">
                <i class="bi bi-grid me-1"></i>{{ $matricula->grupo->nombre_completo ?? '—' }}
                @if($schoolYear)
                    &nbsp;·&nbsp; <i class="bi bi-calendar2-check me-1"></i>{{ $schoolYear->nombre }}
                @endif
            </div>
            @endif
            @if($estudiante->cedula)
            <div style="font-size:.78rem;color:rgba(255,255,255,.6);margin-top:.2rem;">
                <i class="bi bi-person-vcard me-1"></i>Cédula: {{ $estudiante->cedula }}
            </div>
            @endif
        </div>
    </div>
</div>

{{-- ── Tabs ─────────────────────────────────────────────────── --}}
<div class="portal-tabs">
    <a class="portal-tab active" onclick="showTab('calificaciones', this)" href="#">
        <i class="bi bi-journal-check me-1"></i>Calificaciones
    </a>
    <a class="portal-tab" onclick="showTab('asistencia', this)" href="#">
        <i class="bi bi-calendar-check me-1"></i>Asistencia
    </a>
    @if($planificaciones->isNotEmpty())
    <a class="portal-tab" onclick="showTab('planificaciones', this)" href="#">
        <i class="bi bi-journal-text me-1"></i>Planificaciones
    </a>
    @endif
    @if($observaciones->isNotEmpty())
    <a class="portal-tab" onclick="showTab('observaciones', this)" href="#">
        <i class="bi bi-chat-square-text me-1"></i>Observaciones
    </a>
    @endif
    @if($horarioActivo)
    <a class="portal-tab" onclick="showTab('horario', this)" href="#">
        <i class="bi bi-calendar-week me-1"></i>Horario
    </a>
    @endif
    <a class="portal-tab" onclick="showTab('informacion', this)" href="#">
        <i class="bi bi-person-lines-fill me-1"></i>Información
    </a>
</div>

{{-- ── Body ─────────────────────────────────────────────────── --}}
<div class="portal-body">

    {{-- ══ Alerta de riesgo académico ════════════════════════════ --}}
    @php
        $notasBajas = $calificaciones->flatten()
            ->filter(fn($c) => $c->nota_final !== null && $c->nota_final < 70);
        $acadBajas  = isset($calificacionesAcademicas)
            ? $calificacionesAcademicas->filter(fn($c) => $c->nota_final !== null && $c->nota_final < 70)
            : collect();
        $totalRiesgo = $notasBajas->count() + $acadBajas->count();
    @endphp
    @if($totalRiesgo > 0)
    <div style="background:linear-gradient(135deg,#fef2f2,#fee2e2);border:1.5px solid #fca5a5;border-radius:16px;padding:16px 20px;margin-bottom:20px;display:flex;align-items:flex-start;gap:14px;">
        <div style="width:40px;height:40px;background:#dc2626;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <i class="bi bi-exclamation-triangle-fill" style="color:#fff;font-size:1.1rem;"></i>
        </div>
        <div>
            <div style="font-weight:800;font-size:.95rem;color:#991b1b;margin-bottom:4px;">
                ⚠️ Atención requerida — Riesgo académico
            </div>
            <div style="font-size:.83rem;color:#7f1d1d;line-height:1.5;">
                Su representado tiene <strong>{{ $totalRiesgo }} materia(s)</strong> con nota por debajo de 70 (umbral de aprobación MINERD).
                Le recomendamos contactar al docente y planificar refuerzo académico.
            </div>
            <div style="margin-top:8px;display:flex;flex-wrap:wrap;gap:6px;">
                @foreach($notasBajas as $nb)
                <span style="background:#fee2e2;color:#991b1b;border:1px solid #fca5a5;border-radius:20px;padding:.2rem .65rem;font-size:.75rem;font-weight:700;">
                    {{ $nb->asignacion?->asignatura?->nombre ?? '—' }}: {{ number_format($nb->nota_final,1) }}
                </span>
                @endforeach
                @foreach($acadBajas as $nb)
                <span style="background:#fee2e2;color:#991b1b;border:1px solid #fca5a5;border-radius:20px;padding:.2rem .65rem;font-size:.75rem;font-weight:700;">
                    {{ $nb->asignacion?->asignatura?->nombre ?? '—' }}: {{ number_format($nb->nota_final,1) }}
                </span>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    {{-- ══ TAB: Calificaciones ══════════════════════════════════ --}}
    <div class="tab-pane active" id="tab-calificaciones">
        @if($periodos->isEmpty() || $calificaciones->isEmpty())
        <div class="section-card">
            <div class="empty-portal">
                <i class="bi bi-journal-x"></i>
                <div style="font-weight:600;font-size:1rem;color:#374151;margin-bottom:.4rem;">Sin calificaciones publicadas</div>
                <div style="font-size:.85rem;">Las notas aparecerán aquí cuando el docente las publique.</div>
            </div>
        </div>
        @else
        {{-- Period selector --}}
        <div class="mb-3 d-flex flex-wrap gap-2">
            @foreach($periodos as $periodo)
                @if($calificaciones->has($periodo->id))
                <span class="period-pill {{ $loop->first ? 'active' : '' }}"
                      onclick="mostrarPeriodo({{ $periodo->id }}, this)">
                    {{ $periodo->nombre }}
                    @isset($promediosPorPeriodo[$periodo->id])
                        <span class="ms-1" style="opacity:.75;">· {{ $promediosPorPeriodo[$periodo->id] }}</span>
                    @endisset
                </span>
                @endif
            @endforeach
        </div>

        @foreach($periodos as $periodo)
        @if($calificaciones->has($periodo->id))
        <div class="periodo-block" data-periodo="{{ $periodo->id }}" style="{{ $loop->first ? '' : 'display:none;' }}">
            <div class="section-card">
                <div class="section-card-header">
                    <div class="icon" style="background:#dbeafe;color:#1d4ed8;">
                        <i class="bi bi-list-check"></i>
                    </div>
                    <div>
                        <strong>{{ $periodo->nombre }}</strong>
                        @isset($promediosPorPeriodo[$periodo->id])
                        <span class="ms-2 badge" style="background:#2563eb;color:#fff;font-size:.72rem;border-radius:20px;">
                            Promedio: {{ $promediosPorPeriodo[$periodo->id] }}
                        </span>
                        @endisset
                    </div>
                </div>
                <div class="section-card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0" style="font-size:.84rem;">
                            <thead style="background:#f8faff;">
                                <tr>
                                    <th class="ps-3" style="font-weight:600;font-size:.75rem;letter-spacing:.04em;text-transform:uppercase;color:#6b7280;">Asignatura</th>
                                    <th class="text-center" style="font-weight:600;font-size:.75rem;letter-spacing:.04em;text-transform:uppercase;color:#6b7280;">Nota</th>
                                    <th class="text-center d-none d-sm-table-cell" style="font-weight:600;font-size:.75rem;letter-spacing:.04em;text-transform:uppercase;color:#6b7280;">Letra</th>
                                    <th class="d-none d-md-table-cell" style="font-weight:600;font-size:.75rem;letter-spacing:.04em;text-transform:uppercase;color:#6b7280;">Observación</th>
                                </tr>
                            </thead>
                            <tbody>
                            @foreach($calificaciones->get($periodo->id) as $cal)
                            @php
                                $letra   = $cal->letra ?? 'N';
                                $enRiesgo = $cal->nota_final !== null && $cal->nota_final < 70;
                            @endphp
                            <tr style="{{ $enRiesgo ? 'background:#fff5f5;' : '' }}">
                                <td class="ps-3 fw-semibold">
                                    {{ $cal->asignacion?->asignatura?->nombre ?? '—' }}
                                    @if($enRiesgo)
                                    <span style="margin-left:6px;font-size:.68rem;background:#fee2e2;color:#991b1b;border-radius:20px;padding:.15rem .5rem;font-weight:700;">
                                        Requiere refuerzo
                                    </span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <div class="nota-badge nota-{{ $letra }}" style="margin:0 auto;">
                                        {{ $cal->nota_final !== null ? number_format($cal->nota_final, 1) : '—' }}
                                    </div>
                                </td>
                                <td class="text-center d-none d-sm-table-cell">
                                    <span class="badge rounded-pill nota-{{ $letra }}" style="font-size:.78rem;padding:.3rem .65rem;">{{ $letra }}</span>
                                </td>
                                <td class="text-muted d-none d-md-table-cell" style="font-size:.8rem;">
                                    {{ $cal->observaciones ?: '—' }}
                                </td>
                            </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        @endif
        @endforeach
        @endif

        {{-- ══ Calificaciones Académicas (área académica full-year) ══════ --}}
        @if(isset($calificacionesAcademicas) && $calificacionesAcademicas->isNotEmpty())
        <div class="section-card mt-3">
            <div class="section-card-header">
                <div class="icon" style="background:#ede9fe;color:#7c3aed;">
                    <i class="bi bi-mortarboard-fill"></i>
                </div>
                <div>
                    <strong>Área Académica — Año Completo</strong>
                    <div style="font-size:.76rem;color:#64748b;margin-top:2px;">Calificaciones por competencias (4 períodos)</div>
                </div>
            </div>
            <div class="section-card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0" style="font-size:.84rem;">
                        <thead style="background:#faf5ff;">
                            <tr>
                                <th class="ps-3" style="font-weight:600;font-size:.75rem;color:#6b7280;text-transform:uppercase;">Asignatura</th>
                                <th class="text-center" style="font-weight:600;font-size:.75rem;color:#6b7280;text-transform:uppercase;">Nota Final</th>
                                <th class="text-center d-none d-sm-table-cell" style="font-weight:600;font-size:.75rem;color:#6b7280;text-transform:uppercase;">Indicador</th>
                                <th class="d-none d-md-table-cell" style="font-weight:600;font-size:.75rem;color:#6b7280;text-transform:uppercase;">Observación</th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach($calificacionesAcademicas as $cal)
                        @php
                            $nota      = $cal->nota_final;
                            $enRiesgo  = $nota !== null && $nota < 70;
                            $indColor  = match($cal->indicador ?? '') {
                                'Excelente'   => ['bg'=>'#dcfce7','color'=>'#15803d'],
                                'Bueno'       => ['bg'=>'#dbeafe','color'=>'#1d4ed8'],
                                'En proceso'  => ['bg'=>'#fef3c7','color'=>'#92400e'],
                                'Insuficiente'=> ['bg'=>'#fee2e2','color'=>'#991b1b'],
                                default       => ['bg'=>'#f3f4f6','color'=>'#6b7280'],
                            };
                        @endphp
                        <tr style="{{ $enRiesgo ? 'background:#fff5f5;' : '' }}">
                            <td class="ps-3 fw-semibold">
                                {{ $cal->asignacion?->asignatura?->nombre ?? '—' }}
                                @if($enRiesgo)
                                <span style="margin-left:6px;font-size:.68rem;background:#fee2e2;color:#991b1b;border-radius:20px;padding:.15rem .5rem;font-weight:700;">Requiere refuerzo</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <div style="width:46px;height:46px;border-radius:50%;background:{{ $indColor['bg'] }};color:{{ $indColor['color'] }};display:flex;align-items:center;justify-content:center;font-weight:800;font-size:.85rem;margin:0 auto;">
                                    {{ $nota !== null ? number_format($nota, 1) : '—' }}
                                </div>
                            </td>
                            <td class="text-center d-none d-sm-table-cell">
                                @if($cal->indicador)
                                <span style="background:{{ $indColor['bg'] }};color:{{ $indColor['color'] }};border-radius:20px;padding:.2rem .65rem;font-size:.75rem;font-weight:700;">
                                    {{ $cal->indicador }}
                                </span>
                                @else
                                <span style="color:#94a3b8;">—</span>
                                @endif
                            </td>
                            <td class="text-muted d-none d-md-table-cell" style="font-size:.8rem;">
                                {{ $cal->observaciones ?: '—' }}
                            </td>
                        </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif
    </div>

    {{-- ══ TAB: Asistencia ══════════════════════════════════════ --}}
    <div class="tab-pane" id="tab-asistencia">
        @if($asistencias->isEmpty())
        <div class="section-card">
            <div class="empty-portal">
                <i class="bi bi-calendar-x"></i>
                <div style="font-weight:600;font-size:1rem;color:#374151;margin-bottom:.4rem;">Sin registros de asistencia</div>
                <div style="font-size:.85rem;">Los registros aparecerán aquí conforme se vayan tomando.</div>
            </div>
        </div>
        @else
        <div class="section-card">
            <div class="section-card-header">
                <div class="icon" style="background:#dcfce7;color:#15803d;">
                    <i class="bi bi-calendar-check"></i>
                </div>
                <strong>Resumen de Asistencia por Asignatura</strong>
            </div>
            <div class="section-card-body">
                @foreach($asistencias as $row)
                @php
                    $pct    = $row['porcentaje'] ?? 0;
                    $color  = $pct >= 90 ? '#16a34a' : ($pct >= 75 ? '#d97706' : '#dc2626');
                    $bgBar  = $pct >= 90 ? '#16a34a' : ($pct >= 75 ? '#f59e0b' : '#ef4444');
                @endphp
                <div class="mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span style="font-weight:600;font-size:.88rem;">{{ $row['asignatura'] }}</span>
                        <span style="font-weight:700;font-size:.88rem;color:{{ $color }};">{{ $pct !== null ? $pct.'%' : '—' }}</span>
                    </div>
                    <div class="asist-bar mb-1">
                        <div class="asist-bar-fill" style="width:{{ $pct ?? 0 }}%;background:{{ $bgBar }};"></div>
                    </div>
                    <div style="font-size:.76rem;color:#64748b;">
                        <i class="bi bi-check-circle-fill text-success me-1"></i>{{ $row['presentes'] }} presente{{ $row['presentes'] != 1 ? 's' : '' }}
                        &nbsp;·&nbsp;
                        <i class="bi bi-x-circle-fill text-danger me-1"></i>{{ $row['ausentes'] }} ausencia{{ $row['ausentes'] != 1 ? 's' : '' }}
                        &nbsp;·&nbsp;
                        Total: {{ $row['total'] }} clases
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>

    {{-- ══ TAB: Planificaciones ════════════════════════════════ --}}
    @if($planificaciones->isNotEmpty())
    <div class="tab-pane" id="tab-planificaciones">
        @foreach($planificaciones as $asignacionId => $planes)
        @php $primera = $planes->first(); $asignatura = $primera?->asignacion?->asignatura; $docente = $primera?->asignacion?->docente; @endphp
        <div class="section-card mb-3">
            <div class="section-card-header">
                <div class="icon" style="background:#7c3aed20;color:#7c3aed;"><i class="bi bi-book-fill"></i></div>
                <div>
                    <strong>{{ $asignatura?->nombre ?? '—' }}</strong>
                    @if($docente)<span style="font-size:.78rem;color:#64748b;font-weight:400;"> · {{ $docente->nombre_completo }}</span>@endif
                </div>
            </div>
            <div class="section-card-body" style="padding:0;">
                @foreach($planes as $plan)
                <div style="padding:.7rem 1rem;border-bottom:1px solid #f1f5f9;display:flex;gap:.65rem;align-items:flex-start;">
                    <span style="background:{{ $plan->tipo === 'ra' ? '#dbeafe' : '#dcfce7' }};color:{{ $plan->tipo === 'ra' ? '#1d4ed8' : '#15803d' }};border-radius:5px;padding:.1rem .4rem;font-size:.68rem;font-weight:700;flex-shrink:0;">
                        {{ $plan->tipo === 'ra' ? 'RA' : 'Activ.' }}
                    </span>
                    <div style="flex:1;min-width:0;">
                        <div style="font-size:.84rem;font-weight:700;color:#1e293b;">
                            {{ $plan->modulo_nombre ?? $asignatura?->nombre }}
                            @if($plan->mf_codigo)<span style="font-size:.7rem;font-weight:400;color:#64748b;"> · {{ $plan->mf_codigo }}</span>@endif
                        </div>
                        @if($plan->fecha_inicio && $plan->fecha_fin)
                        <div style="font-size:.72rem;color:#64748b;margin-top:.15rem;">
                            <i class="bi bi-calendar3 me-1"></i>{{ $plan->fecha_inicio->format('d/m/Y') }} — {{ $plan->fecha_fin->format('d/m/Y') }}
                        </div>
                        @endif
                        @if($plan->tipo === 'ra' && $plan->raItems->isNotEmpty())
                        @foreach($plan->raItems->take(2) as $ra)
                        <div style="margin-top:.25rem;font-size:.75rem;color:#374151;background:#f8faff;border-left:3px solid #7c3aed;padding:.2rem .45rem;border-radius:0 4px 4px 0;">
                            @if($ra->ra_codigo)<strong style="color:#7c3aed;">{{ $ra->ra_codigo }}:</strong> @endif
                            {{ Str::limit($ra->ra_descripcion, 100) }}
                        </div>
                        @endforeach
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endforeach
    </div>
    @endif

    {{-- ══ TAB: Observaciones ════════════════════════════════ --}}
    @if($observaciones->isNotEmpty())
    <div class="tab-pane" id="tab-observaciones">
        <div class="section-card">
            <div class="section-card-header">
                <div class="icon" style="background:#f59e0b20;color:#f59e0b;"><i class="bi bi-chat-square-text"></i></div>
                <strong>Observaciones del Docente</strong>
            </div>
            <div class="section-card-body" style="padding:0;">
                @foreach($observaciones as $obs)
                @php $ti = $obs->tipo_info; @endphp
                <div style="padding:.75rem 1rem;border-bottom:1px solid #f1f5f9;display:flex;gap:.75rem;align-items:flex-start;">
                    <div style="width:32px;height:32px;border-radius:8px;background:{{ $ti['color'] }}18;color:{{ $ti['color'] }};display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:.85rem;">
                        <i class="bi {{ $ti['icon'] }}"></i>
                    </div>
                    <div style="flex:1;">
                        <div style="font-size:.75rem;font-weight:700;color:{{ $ti['color'] }};margin-bottom:.15rem;">
                            {{ $ti['label'] }}
                            @if($obs->asignacion?->asignatura)
                            · {{ $obs->asignacion->asignatura->nombre }}
                            @endif
                        </div>
                        <div style="font-size:.83rem;color:#374151;line-height:1.45;">{{ $obs->texto }}</div>
                        <div style="font-size:.7rem;color:#9ca3af;margin-top:.25rem;">
                            {{ $obs->docente?->nombre_completo }} · {{ $obs->created_at->format('d/m/Y') }}
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    {{-- ══ TAB: Horario ════════════════════════════════════════ --}}
    @if($horarioActivo)
    <div class="tab-pane" id="tab-horario">
        @if(empty($gridHorario))
        <div class="section-card">
            <div class="empty-portal">
                <i class="bi bi-calendar-x"></i>
                <div style="font-weight:600;font-size:1rem;color:#374151;margin-bottom:.4rem;">Sin horario disponible</div>
                <div style="font-size:.85rem;">El horario de tu grupo aparecerá aquí cuando sea publicado.</div>
            </div>
        </div>
        @else
        @php
            $diasPortal = ['lunes'=>'Lunes','martes'=>'Martes','miercoles'=>'Miércoles','jueves'=>'Jueves','viernes'=>'Viernes'];
        @endphp
        <div class="section-card">
            <div class="section-card-header">
                <div class="icon" style="background:#dbeafe;color:#1d4ed8;">
                    <i class="bi bi-calendar-week"></i>
                </div>
                <div>
                    <strong>Horario — {{ $matricula?->grupo->nombre_completo }}</strong>
                    <div style="font-size:.75rem;color:#64748b;margin-top:2px;">{{ $horarioActivo->nombre }}</div>
                </div>
            </div>
            <div class="section-card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm mb-0" style="font-size:.8rem;">
                        <thead style="background:#f8faff;">
                            <tr>
                                <th class="ps-3" style="font-size:.72rem;color:#6b7280;font-weight:600;white-space:nowrap;">Hora</th>
                                @foreach($diasPortal as $dKey => $dLabel)
                                <th class="text-center" style="font-size:.72rem;color:#6b7280;font-weight:600;">{{ $dLabel }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                        @foreach($franjasHorario as $franja)
                        @if($franja->es_recreo)
                        <tr style="background:#fefce8;">
                            <td colspan="6" class="text-center py-1" style="font-size:.73rem;color:#92400e;font-weight:600;">
                                <i class="bi bi-cup-hot me-1"></i>Recreo · {{ $franja->hora_inicio }} – {{ $franja->hora_fin }}
                            </td>
                        </tr>
                        @else
                        <tr>
                            <td class="ps-3" style="font-size:.73rem;color:#64748b;white-space:nowrap;">
                                {{ $franja->hora_inicio }}<br>{{ $franja->hora_fin }}
                            </td>
                            @foreach($diasPortal as $dKey => $dLabel)
                            @php $cel = $gridHorario[$franja->id][$dKey] ?? null; @endphp
                            <td class="text-center" style="padding:.3rem;">
                                @if($cel)
                                <div style="background:#eff6ff;border-radius:8px;padding:.3rem .4rem;border-left:3px solid #3b82f6;">
                                    <div style="font-weight:700;font-size:.77rem;color:#1e40af;">
                                        {{ $cel->asignacion?->asignatura?->nombre }}
                                    </div>
                                    <div style="font-size:.68rem;color:#64748b;margin-top:2px;">
                                        {{ $cel->asignacion?->docente?->apellidos }}
                                    </div>
                                    @if($cel->aula)
                                    <div style="font-size:.65rem;color:#94a3b8;">{{ $cel->aula->nombre }}</div>
                                    @endif
                                </div>
                                @else
                                <span style="color:#e2e8f0;font-size:.75rem;">—</span>
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
        </div>
        @endif
    </div>
    @endif

    {{-- ══ TAB: Información ═════════════════════════════════════ --}}
    <div class="tab-pane" id="tab-informacion">
        <div class="section-card">
            <div class="section-card-header">
                <div class="icon" style="background:#ede9fe;color:#7c3aed;">
                    <i class="bi bi-person-fill"></i>
                </div>
                <strong>Datos del Estudiante</strong>
            </div>
            <div class="section-card-body">
                <div class="row g-3" style="font-size:.88rem;">
                    <div class="col-sm-6">
                        <div style="color:#6b7280;font-size:.75rem;font-weight:600;text-transform:uppercase;letter-spacing:.05em;">Nombre completo</div>
                        <div class="fw-semibold">{{ $estudiante->apellidos }}, {{ $estudiante->nombres }}</div>
                    </div>
                    @if($estudiante->cedula)
                    <div class="col-sm-6">
                        <div style="color:#6b7280;font-size:.75rem;font-weight:600;text-transform:uppercase;letter-spacing:.05em;">Cédula</div>
                        <div class="fw-semibold">{{ $estudiante->cedula }}</div>
                    </div>
                    @endif
                    @if($estudiante->fecha_nacimiento)
                    <div class="col-sm-6">
                        <div style="color:#6b7280;font-size:.75rem;font-weight:600;text-transform:uppercase;letter-spacing:.05em;">Fecha de nacimiento</div>
                        <div class="fw-semibold">{{ $estudiante->fecha_nacimiento->format('d/m/Y') }} ({{ $estudiante->edad }} años)</div>
                    </div>
                    @endif
                    <div class="col-sm-6">
                        <div style="color:#6b7280;font-size:.75rem;font-weight:600;text-transform:uppercase;letter-spacing:.05em;">Sexo</div>
                        <div class="fw-semibold">{{ $estudiante->sexo === 'M' ? 'Masculino' : 'Femenino' }}</div>
                    </div>
                    @if($estudiante->telefono)
                    <div class="col-sm-6">
                        <div style="color:#6b7280;font-size:.75rem;font-weight:600;text-transform:uppercase;letter-spacing:.05em;">Teléfono</div>
                        <div class="fw-semibold">{{ $estudiante->telefono }}</div>
                    </div>
                    @endif
                    @if($matricula)
                    <div class="col-sm-6">
                        <div style="color:#6b7280;font-size:.75rem;font-weight:600;text-transform:uppercase;letter-spacing:.05em;">Grupo / Curso</div>
                        <div class="fw-semibold">{{ $matricula->grupo->nombre_completo ?? '—' }}</div>
                    </div>
                    <div class="col-sm-6">
                        <div style="color:#6b7280;font-size:.75rem;font-weight:600;text-transform:uppercase;letter-spacing:.05em;">Número de matrícula</div>
                        <div class="fw-semibold">{{ $estudiante->numero_matricula }}</div>
                    </div>
                    @endif
                </div>

                @if($estudiante->tutor_nombre)
                <hr style="border-color:#f0f4f8;margin:1.25rem 0;">
                <div style="font-weight:700;font-size:.82rem;text-transform:uppercase;letter-spacing:.06em;color:#6b7280;margin-bottom:.85rem;">
                    <i class="bi bi-person-check me-1"></i>Representante / Tutor
                </div>
                <div class="row g-3" style="font-size:.88rem;">
                    <div class="col-sm-6">
                        <div style="color:#6b7280;font-size:.75rem;font-weight:600;text-transform:uppercase;letter-spacing:.05em;">Nombre</div>
                        <div class="fw-semibold">{{ $estudiante->tutor_nombre }}</div>
                    </div>
                    @if($estudiante->tutor_parentesco)
                    <div class="col-sm-6">
                        <div style="color:#6b7280;font-size:.75rem;font-weight:600;text-transform:uppercase;letter-spacing:.05em;">Parentesco</div>
                        <div class="fw-semibold">{{ $estudiante->tutor_parentesco }}</div>
                    </div>
                    @endif
                    @if($estudiante->tutor_telefono)
                    <div class="col-sm-6">
                        <div style="color:#6b7280;font-size:.75rem;font-weight:600;text-transform:uppercase;letter-spacing:.05em;">Teléfono</div>
                        <div class="fw-semibold">{{ $estudiante->tutor_telefono }}</div>
                    </div>
                    @endif
                </div>
                @endif
            </div>
        </div>
    </div>

</div>{{-- /portal-body --}}

<footer class="portal-footer">
    <i class="bi bi-shield-lock me-1"></i>
    Este enlace es personal y de uso exclusivo del representante. No compartir.
    &nbsp;·&nbsp;
    &copy; {{ date('Y') }} <strong>PSAC</strong> — Sistema de Gestión Escolar
</footer>

<script src="/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script>
function showTab(name, el) {
    event.preventDefault();
    document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.portal-tab').forEach(t => t.classList.remove('active'));
    document.getElementById('tab-' + name).classList.add('active');
    el.classList.add('active');
}

function mostrarPeriodo(periodoId, el) {
    document.querySelectorAll('.periodo-block').forEach(b => b.style.display = 'none');
    document.querySelectorAll('.period-pill').forEach(p => p.classList.remove('active'));
    const bloque = document.querySelector('[data-periodo="' + periodoId + '"]');
    if (bloque) bloque.style.display = '';
    el.classList.add('active');
}
</script>
</body>
</html>
