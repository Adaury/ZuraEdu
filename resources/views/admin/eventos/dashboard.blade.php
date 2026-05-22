@extends('layouts.admin')

@section('title', 'Dashboard Eventos')

@section('content')
<div class="container-fluid px-4 py-3">

    {{-- Cabecera --}}
    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-0 text-dark">
                <i class="bi bi-calendar-event-fill me-2 text-indigo"></i>Eventos Extracurriculares
            </h4>
            <p class="text-muted mb-0 small">Resumen ejecutivo • {{ now()->translatedFormat('d F Y') }}</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('admin.eventos.index') }}"
               class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-list-ul me-1"></i>Lista
            </a>
            <a href="{{ route('admin.eventos.create') }}"
               class="btn btn-sm" style="background:#6366f1;color:white">
                <i class="bi bi-plus-lg me-1"></i>Nuevo evento
            </a>
            <a href="{{ route('admin.eventos.lista-excel') }}"
               class="btn btn-outline-success btn-sm">
                <i class="bi bi-file-earmark-excel me-1"></i>Excel
            </a>
            <a href="{{ route('admin.eventos.lista-pdf') }}"
               class="btn btn-outline-danger btn-sm">
                <i class="bi bi-file-earmark-pdf me-1"></i>PDF
            </a>
        </div>
    </div>

    {{-- Stat cards --}}
    <div class="row g-3 mb-4">
        {{-- Total --}}
        <div class="col-6 col-md-2">
            <div class="card border-0 shadow-sm h-100" style="border-left:4px solid #6366f1!important">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <span class="text-muted small">Total</span>
                        <div class="rounded-circle d-flex align-items-center justify-content-center"
                             style="width:32px;height:32px;background:#e0e7ff">
                            <i class="bi bi-calendar3" style="color:#6366f1"></i>
                        </div>
                    </div>
                    <div class="fw-bold fs-4 text-dark">{{ $total }}</div>
                </div>
            </div>
        </div>

        {{-- Activos --}}
        <div class="col-6 col-md-2">
            <div class="card border-0 shadow-sm h-100" style="border-left:4px solid #10b981!important">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <span class="text-muted small">Activos</span>
                        <div class="rounded-circle d-flex align-items-center justify-content-center"
                             style="width:32px;height:32px;background:#d1fae5">
                            <i class="bi bi-check-circle-fill text-success"></i>
                        </div>
                    </div>
                    <div class="fw-bold fs-4 text-success">{{ $activos }}</div>
                </div>
            </div>
        </div>

        {{-- Próximos --}}
        <div class="col-6 col-md-2">
            <div class="card border-0 shadow-sm h-100" style="border-left:4px solid #3b82f6!important">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <span class="text-muted small">Próximos 30d</span>
                        <div class="rounded-circle d-flex align-items-center justify-content-center"
                             style="width:32px;height:32px;background:#dbeafe">
                            <i class="bi bi-calendar-plus-fill text-primary"></i>
                        </div>
                    </div>
                    <div class="fw-bold fs-4 text-primary">{{ $proximos }}</div>
                </div>
            </div>
        </div>

        {{-- Total inscritos --}}
        <div class="col-6 col-md-2">
            <div class="card border-0 shadow-sm h-100" style="border-left:4px solid #f59e0b!important">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <span class="text-muted small">Inscripciones</span>
                        <div class="rounded-circle d-flex align-items-center justify-content-center"
                             style="width:32px;height:32px;background:#fef3c7">
                            <i class="bi bi-person-check-fill text-warning"></i>
                        </div>
                    </div>
                    <div class="fw-bold fs-4 text-warning">{{ $totalInscritos }}</div>
                </div>
            </div>
        </div>

        {{-- Con asistencia --}}
        <div class="col-6 col-md-2">
            <div class="card border-0 shadow-sm h-100" style="border-left:4px solid #8b5cf6!important">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <span class="text-muted small">Asistieron</span>
                        <div class="rounded-circle d-flex align-items-center justify-content-center"
                             style="width:32px;height:32px;background:#ede9fe">
                            <i class="bi bi-people-fill" style="color:#8b5cf6"></i>
                        </div>
                    </div>
                    <div class="fw-bold fs-4" style="color:#8b5cf6">{{ $conAsistencia }}</div>
                </div>
            </div>
        </div>

        {{-- Pasados --}}
        <div class="col-6 col-md-2">
            <div class="card border-0 shadow-sm h-100" style="border-left:4px solid #6b7280!important">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <span class="text-muted small">Pasados</span>
                        <div class="rounded-circle d-flex align-items-center justify-content-center"
                             style="width:32px;height:32px;background:#f1f5f9">
                            <i class="bi bi-calendar-x text-secondary"></i>
                        </div>
                    </div>
                    <div class="fw-bold fs-4 text-secondary">{{ $pasados }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        {{-- Por tipo --}}
        <div class="col-12 col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 pb-0">
                    <h6 class="fw-semibold mb-0">
                        <i class="bi bi-pie-chart-fill me-2" style="color:#6366f1"></i>Por tipo de evento
                    </h6>
                </div>
                <div class="card-body pt-3">
                    @php
                        $tiposInfo = [
                            'academico'  => ['label'=>'Académico',  'color'=>'#3b82f6','bg'=>'#dbeafe'],
                            'deportivo'  => ['label'=>'Deportivo',  'color'=>'#10b981','bg'=>'#d1fae5'],
                            'cultural'   => ['label'=>'Cultural',   'color'=>'#8b5cf6','bg'=>'#ede9fe'],
                            'social'     => ['label'=>'Social',     'color'=>'#f59e0b','bg'=>'#fef3c7'],
                            'otro'       => ['label'=>'Otro',       'color'=>'#6b7280','bg'=>'#f1f5f9'],
                        ];
                        $totalTipos = $porTipo->sum() ?: 1;
                    @endphp
                    @foreach($tiposInfo as $clave => $info)
                    @php
                        $count = $porTipo[$clave] ?? 0;
                        $pct   = round($count / $totalTipos * 100);
                    @endphp
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="badge rounded-2 small" style="background:{{ $info['bg'] }};color:{{ $info['color'] }}">
                                {{ $info['label'] }}
                            </span>
                            <span class="small text-muted">{{ $count }} ({{ $pct }}%)</span>
                        </div>
                        <div class="progress" style="height:8px;border-radius:4px">
                            <div class="progress-bar" style="width:{{ $pct }}%;background:{{ $info['color'] }};border-radius:4px"></div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Próximos eventos --}}
        <div class="col-12 col-lg-8">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 pb-0">
                    <h6 class="fw-semibold mb-0">
                        <i class="bi bi-calendar-week me-2 text-primary"></i>Próximos eventos (30 días)
                    </h6>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        @forelse($eventosProximos as $ev)
                        @php $ti = $tiposInfo[$ev->tipo] ?? ['label'=>ucfirst($ev->tipo),'color'=>'#6b7280','bg'=>'#f1f5f9']; @endphp
                        <li class="list-group-item px-3 py-2">
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="text-center rounded-2 p-2 flex-shrink-0"
                                         style="background:{{ $ti['bg'] }};min-width:48px">
                                        <div class="fw-bold small" style="color:{{ $ti['color'] }}">
                                            {{ $ev->fecha_inicio->format('d') }}
                                        </div>
                                        <div style="font-size:.65rem;color:{{ $ti['color'] }}">
                                            {{ strtoupper($ev->fecha_inicio->format('M')) }}
                                        </div>
                                    </div>
                                    <div>
                                        <div class="fw-semibold small">{{ $ev->nombre }}</div>
                                        <div class="text-muted" style="font-size:.72rem">
                                            <i class="bi bi-geo-alt me-1"></i>{{ $ev->lugar ?? 'Sin lugar' }}
                                        </div>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge bg-light text-secondary">
                                        <i class="bi bi-people me-1"></i>{{ $ev->inscripciones_count }}
                                    </span>
                                    @if($ev->cupo_maximo)
                                    <span class="badge rounded-2 small" style="background:{{ $ti['bg'] }};color:{{ $ti['color'] }}">
                                        cupo {{ $ev->cupo_maximo }}
                                    </span>
                                    @endif
                                </div>
                            </div>
                        </li>
                        @empty
                        <li class="list-group-item text-muted text-center py-4 small">
                            No hay eventos programados para los próximos 30 días.
                        </li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        {{-- Más inscritos --}}
        <div class="col-12 col-lg-5">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 pb-0">
                    <h6 class="fw-semibold mb-0">
                        <i class="bi bi-trophy-fill me-2 text-warning"></i>Eventos más populares
                    </h6>
                </div>
                <div class="card-body p-0">
                    @php $maxI = $masInscritos->max('inscripciones_count') ?: 1; @endphp
                    <ul class="list-group list-group-flush">
                        @forelse($masInscritos as $i => $ev)
                        @php
                            $ti  = $tiposInfo[$ev->tipo] ?? ['color'=>'#6b7280','bg'=>'#f1f5f9'];
                            $pctI = round($ev->inscripciones_count / $maxI * 100);
                        @endphp
                        <li class="list-group-item px-3 py-2">
                            <div class="d-flex align-items-center justify-content-between mb-1">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge text-white rounded-circle"
                                          style="background:{{ ['#f59e0b','#94a3b8','#cd7f32','#6b7280','#6b7280'][$i] ?? '#6b7280' }};width:22px;height:22px;font-size:.7rem;display:flex;align-items:center;justify-content:center">
                                        {{ $i + 1 }}
                                    </span>
                                    <span class="fw-semibold small text-truncate" style="max-width:180px">{{ $ev->nombre }}</span>
                                </div>
                                <span class="badge rounded-pill"
                                      style="background:{{ $ti['bg'] }};color:{{ $ti['color'] }}">
                                    <i class="bi bi-people me-1"></i>{{ $ev->inscripciones_count }}
                                </span>
                            </div>
                            <div class="progress" style="height:4px;border-radius:2px">
                                <div class="progress-bar" style="width:{{ $pctI }}%;background:{{ $ti['color'] }}"></div>
                            </div>
                        </li>
                        @empty
                        <li class="list-group-item text-muted text-center py-4 small">Sin eventos con inscripciones.</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>

        {{-- Eventos recientes --}}
        <div class="col-12 col-lg-7">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 pb-0 d-flex justify-content-between align-items-center">
                    <h6 class="fw-semibold mb-0">
                        <i class="bi bi-clock-history me-2 text-secondary"></i>Eventos recientes
                    </h6>
                    <a href="{{ route('admin.eventos.index') }}" class="btn btn-link btn-sm p-0 text-muted">Ver todos</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-sm mb-0" style="font-size:.82rem">
                            <thead class="table-light">
                                <tr>
                                    <th>Evento</th>
                                    <th>Tipo</th>
                                    <th>Fecha inicio</th>
                                    <th>Inscritos</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recientes as $ev)
                                @php $ti = $tiposInfo[$ev->tipo] ?? ['label'=>ucfirst($ev->tipo),'color'=>'#6b7280','bg'=>'#f1f5f9']; @endphp
                                <tr>
                                    <td>
                                        <a href="{{ route('admin.eventos.show', $ev->id) }}"
                                           class="fw-semibold text-decoration-none text-dark">
                                            {{ \Illuminate\Support\Str::limit($ev->nombre, 35) }}
                                        </a>
                                        @if($ev->lugar)
                                        <div class="text-muted" style="font-size:.72rem">
                                            <i class="bi bi-geo-alt me-1"></i>{{ $ev->lugar }}
                                        </div>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge rounded-2 small"
                                              style="background:{{ $ti['bg'] }};color:{{ $ti['color'] }}">
                                            {{ $ti['label'] }}
                                        </span>
                                    </td>
                                    <td>{{ $ev->fecha_inicio?->format('d/m/Y') ?? '—' }}</td>
                                    <td>
                                        <span class="badge bg-light text-secondary">
                                            <i class="bi bi-people me-1"></i>{{ $ev->inscripciones_count }}
                                            @if($ev->cupo_maximo) / {{ $ev->cupo_maximo }} @endif
                                        </span>
                                    </td>
                                    <td>
                                        @if($ev->activo)
                                        <span class="badge bg-success-subtle text-success">Activo</span>
                                        @else
                                        <span class="badge bg-secondary-subtle text-secondary">Inactivo</span>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">Sin eventos registrados.</td>
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
    <div class="row g-3 mt-3">
        @php
        $actions = [
            ['icon'=>'bi-plus-circle-fill','color'=>'#6366f1','bg'=>'#e0e7ff','label'=>'Nuevo evento','href'=>route('admin.eventos.create')],
            ['icon'=>'bi-list-ul','color'=>'#6b7280','bg'=>'#f1f5f9','label'=>'Ver todos','href'=>route('admin.eventos.index')],
            ['icon'=>'bi-file-earmark-excel-fill','color'=>'#10b981','bg'=>'#d1fae5','label'=>'Exportar Excel','href'=>route('admin.eventos.lista-excel')],
            ['icon'=>'bi-calendar-event-fill','color'=>'#3b82f6','bg'=>'#dbeafe','label'=>'Calendario Académico','href'=>route('admin.calendario.index')],
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
