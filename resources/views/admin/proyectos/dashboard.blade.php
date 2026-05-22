@extends('layouts.admin')

@section('title', 'Dashboard Proyectos')

@section('content')
<div class="container-fluid px-4 py-3">

    {{-- Cabecera --}}
    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-0 text-dark">
                <i class="bi bi-lightbulb-fill me-2 text-warning"></i>Proyectos Escolares
            </h4>
            <p class="text-muted mb-0 small">
                Resumen ejecutivo
                @if($schoolYear) • {{ $schoolYear->nombre }} @endif
                • {{ now()->translatedFormat('d F Y') }}
            </p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('admin.proyectos.index') }}"
               class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-list-ul me-1"></i>Lista
            </a>
            <a href="{{ route('admin.proyectos.create') }}"
               class="btn btn-warning btn-sm text-dark">
                <i class="bi bi-plus-lg me-1"></i>Nuevo
            </a>
        </div>
    </div>

    {{-- Stat cards --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100" style="border-left:4px solid #f59e0b!important">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <span class="text-muted small">Total</span>
                        <div class="rounded-circle d-flex align-items-center justify-content-center"
                             style="width:36px;height:36px;background:#fef3c7">
                            <i class="bi bi-lightbulb-fill text-warning"></i>
                        </div>
                    </div>
                    <div class="fw-bold fs-3 text-dark">{{ $total }}</div>
                    <div class="text-muted" style="font-size:.78rem">Historial completo</div>
                </div>
            </div>
        </div>

        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100" style="border-left:4px solid #3b82f6!important">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <span class="text-muted small">En progreso</span>
                        <div class="rounded-circle d-flex align-items-center justify-content-center"
                             style="width:36px;height:36px;background:#dbeafe">
                            <i class="bi bi-arrow-repeat text-primary"></i>
                        </div>
                    </div>
                    <div class="fw-bold fs-3 text-primary">{{ $activos }}</div>
                    <div class="text-muted" style="font-size:.78rem">Planificación + Desarrollo</div>
                </div>
            </div>
        </div>

        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100" style="border-left:4px solid #10b981!important">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <span class="text-muted small">Finalizados</span>
                        <div class="rounded-circle d-flex align-items-center justify-content-center"
                             style="width:36px;height:36px;background:#d1fae5">
                            <i class="bi bi-check-circle-fill text-success"></i>
                        </div>
                    </div>
                    <div class="fw-bold fs-3 text-success">{{ $finalizados + $presentados }}</div>
                    <div class="text-muted" style="font-size:.78rem">Finalizado + Presentado</div>
                </div>
            </div>
        </div>

        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100" style="border-left:4px solid #8b5cf6!important">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <span class="text-muted small">Año actual</span>
                        <div class="rounded-circle d-flex align-items-center justify-content-center"
                             style="width:36px;height:36px;background:#ede9fe">
                            <i class="bi bi-calendar-check-fill" style="color:#8b5cf6"></i>
                        </div>
                    </div>
                    <div class="fw-bold fs-3" style="color:#8b5cf6">{{ $esteAnio }}</div>
                    <div class="text-muted" style="font-size:.78rem">
                        {{ $schoolYear?->nombre ?? now()->year }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Alerta fases vencidas --}}
    @if($fasesVencidas > 0)
    <div class="alert border-0 mb-4 d-flex align-items-center gap-3 py-2 px-3"
         style="background:#fff7ed;border-left:4px solid #f59e0b!important">
        <i class="bi bi-exclamation-triangle-fill text-warning fs-5"></i>
        <div class="small">
            <strong>{{ $fasesVencidas }} fase{{ $fasesVencidas != 1 ? 's' : '' }}</strong>
            vencida{{ $fasesVencidas != 1 ? 's' : '' }} sin completar.
            <a href="{{ route('admin.proyectos.index', ['estado' => 'desarrollo']) }}"
               class="fw-semibold text-warning-emphasis ms-1">Ver proyectos en desarrollo</a>
        </div>
    </div>
    @endif

    <div class="row g-3 mb-4">
        {{-- Por estado --}}
        <div class="col-12 col-lg-5">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 pb-0">
                    <h6 class="fw-semibold mb-0">
                        <i class="bi bi-bar-chart-fill me-2 text-primary"></i>Por estado
                    </h6>
                </div>
                <div class="card-body pt-3">
                    @php
                    $estadoStyles = [
                        'planificacion' => ['color'=>'#f59e0b','bg'=>'#fef3c7'],
                        'desarrollo'    => ['color'=>'#3b82f6','bg'=>'#dbeafe'],
                        'finalizado'    => ['color'=>'#10b981','bg'=>'#d1fae5'],
                        'presentado'    => ['color'=>'#6366f1','bg'=>'#e0e7ff'],
                    ];
                    $maxEstado = max(1, $porEstado->max());
                    @endphp
                    @foreach($porEstado as $estado => $cnt)
                    @php $s = $estadoStyles[$estado] ?? ['color'=>'#6b7280','bg'=>'#f1f5f9']; $pct = round($cnt / $maxEstado * 100); @endphp
                    <div class="mb-3">
                        <div class="d-flex align-items-center justify-content-between mb-1">
                            <span class="small fw-medium">
                                {{ \App\Models\ProyectoEscolar::ESTADOS[$estado] ?? ucfirst($estado) }}
                            </span>
                            <span class="small text-muted">{{ $cnt }}</span>
                        </div>
                        <div class="progress" style="height:8px;border-radius:4px">
                            <div class="progress-bar" style="width:{{ $pct }}%;background:{{ $s['color'] }};border-radius:4px"></div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Por área --}}
        <div class="col-12 col-lg-7">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 pb-0">
                    <h6 class="fw-semibold mb-0">
                        <i class="bi bi-pie-chart-fill me-2 text-warning"></i>Por área
                    </h6>
                </div>
                <div class="card-body pt-3">
                    @php
                    $areaStyles = [
                        'ciencias'    => ['color'=>'#10b981','bg'=>'#d1fae5'],
                        'matematica'  => ['color'=>'#3b82f6','bg'=>'#dbeafe'],
                        'humanidades' => ['color'=>'#8b5cf6','bg'=>'#ede9fe'],
                        'tecnologia'  => ['color'=>'#6366f1','bg'=>'#e0e7ff'],
                        'arte'        => ['color'=>'#ec4899','bg'=>'#fce7f3'],
                        'otro'        => ['color'=>'#6b7280','bg'=>'#f1f5f9'],
                    ];
                    $maxArea = max(1, $porArea->max());
                    @endphp
                    @forelse($porArea as $area => $cnt)
                    @php $s = $areaStyles[$area] ?? ['color'=>'#6b7280','bg'=>'#f1f5f9']; $pct = round($cnt / $maxArea * 100); @endphp
                    <div class="mb-2 d-flex align-items-center gap-2">
                        <span class="badge rounded-pill px-2 py-1 flex-shrink-0"
                              style="background:{{ $s['bg'] }};color:{{ $s['color'] }};font-size:.72rem;min-width:80px;text-align:center">
                            {{ \App\Models\ProyectoEscolar::AREAS[$area] ?? ucfirst($area) }}
                        </span>
                        <div class="flex-grow-1">
                            <div class="progress" style="height:7px;border-radius:4px">
                                <div class="progress-bar" style="width:{{ $pct }}%;background:{{ $s['color'] }};border-radius:4px"></div>
                            </div>
                        </div>
                        <span class="small text-muted flex-shrink-0" style="min-width:24px;text-align:right">{{ $cnt }}</span>
                    </div>
                    @empty
                    <p class="text-muted small text-center py-3">Sin proyectos registrados.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        {{-- Top proyectos con más integrantes --}}
        <div class="col-12 col-lg-5">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 pb-0">
                    <h6 class="fw-semibold mb-0">
                        <i class="bi bi-people-fill me-2 text-success"></i>Proyectos con más integrantes
                    </h6>
                </div>
                <div class="card-body p-0">
                    @php $maxInt = max(1, $topProyectos->max('integrantes_count')); @endphp
                    <ul class="list-group list-group-flush">
                        @forelse($topProyectos as $i => $proy)
                        @php $pctI = round($proy->integrantes_count / $maxInt * 100); @endphp
                        <li class="list-group-item px-3 py-2">
                            <div class="d-flex align-items-center justify-content-between mb-1">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge text-white rounded-circle"
                                          style="background:{{ ['#f59e0b','#94a3b8','#cd7f32','#6b7280','#6b7280'][$i] ?? '#6b7280' }};width:22px;height:22px;font-size:.7rem;display:flex;align-items:center;justify-content:center">
                                        {{ $i + 1 }}
                                    </span>
                                    <div>
                                        <div class="fw-semibold small">{{ \Illuminate\Support\Str::limit($proy->titulo, 35) }}</div>
                                        <div class="text-muted" style="font-size:.72rem">{{ $proy->tutor->name ?? '—' }}</div>
                                    </div>
                                </div>
                                <span class="badge bg-success-subtle text-success rounded-pill">
                                    <i class="bi bi-people me-1"></i>{{ $proy->integrantes_count }}
                                </span>
                            </div>
                            <div class="progress" style="height:4px;border-radius:2px">
                                <div class="progress-bar bg-success" style="width:{{ $pctI }}%"></div>
                            </div>
                        </li>
                        @empty
                        <li class="list-group-item text-muted text-center py-4 small">Sin proyectos registrados.</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>

        {{-- Últimos proyectos --}}
        <div class="col-12 col-lg-7">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 pb-0 d-flex justify-content-between align-items-center">
                    <h6 class="fw-semibold mb-0">
                        <i class="bi bi-clock-history me-2 text-secondary"></i>Proyectos recientes
                    </h6>
                    <a href="{{ route('admin.proyectos.index') }}" class="btn btn-link btn-sm p-0 text-muted">Ver todos</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-sm mb-0" style="font-size:.82rem">
                            <thead class="table-light">
                                <tr>
                                    <th>Título</th>
                                    <th>Área</th>
                                    <th>Estado</th>
                                    <th>Integrantes</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recientes as $p)
                                @php
                                    $as = $areaStyles[$p->area] ?? ['color'=>'#6b7280','bg'=>'#f1f5f9'];
                                    $es = $estadoStyles[$p->estado] ?? ['color'=>'#6b7280','bg'=>'#f1f5f9'];
                                @endphp
                                <tr>
                                    <td>
                                        <div class="fw-semibold">{{ \Illuminate\Support\Str::limit($p->titulo, 38) }}</div>
                                        <div class="text-muted" style="font-size:.72rem">{{ $p->tutor->name ?? '—' }}</div>
                                    </td>
                                    <td>
                                        <span class="badge rounded-2 small"
                                              style="background:{{ $as['bg'] }};color:{{ $as['color'] }}">
                                            {{ $p->area_label }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge rounded-2 small"
                                              style="background:{{ $es['bg'] }};color:{{ $es['color'] }}">
                                            {{ $p->estado_label }}
                                        </span>
                                    </td>
                                    <td class="text-center">{{ $p->integrantes_count }}</td>
                                    <td>
                                        <a href="{{ route('admin.proyectos.show', $p->id) }}"
                                           class="btn btn-link btn-sm p-0 text-muted">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">Sin proyectos registrados.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Acciones rápidas --}}
    <div class="row g-3">
        @php
        $actions = [
            ['icon'=>'bi-plus-circle-fill','color'=>'#f59e0b','bg'=>'#fef3c7','label'=>'Nuevo proyecto','href'=>route('admin.proyectos.create')],
            ['icon'=>'bi-list-ul','color'=>'#6b7280','bg'=>'#f1f5f9','label'=>'Ver todos','href'=>route('admin.proyectos.index')],
            ['icon'=>'bi-file-earmark-excel-fill','color'=>'#10b981','bg'=>'#d1fae5','label'=>'Exportar Excel','href'=>route('admin.proyectos.lista-excel')],
            ['icon'=>'bi-file-earmark-pdf-fill','color'=>'#ef4444','bg'=>'#fee2e2','label'=>'Exportar PDF','href'=>route('admin.proyectos.lista-pdf')],
        ];
        @endphp
        @foreach($actions as $a)
        <div class="col-6 col-md-3">
            <a href="{{ $a['href'] }}" class="card border-0 shadow-sm text-decoration-none h-100">
                <div class="card-body p-3 d-flex align-items-center gap-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                         style="width:42px;height:42px;background:{{ $a['bg'] }}">
                        <i class="bi {{ $a['icon'] }} fs-5" style="color:{{ $a['color'] }}"></i>
                    </div>
                    <span class="fw-semibold text-dark small">{{ $a['label'] }}</span>
                </div>
            </a>
        </div>
        @endforeach
    </div>

</div>
@endsection
