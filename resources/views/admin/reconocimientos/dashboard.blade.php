@extends('layouts.admin')

@section('title', 'Dashboard Reconocimientos')

@section('content')
<div class="container-fluid px-4 py-3">

    {{-- Cabecera --}}
    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-0 text-dark">
                <i class="bi bi-trophy-fill me-2 text-warning"></i>Reconocimientos y Diplomas
            </h4>
            <p class="text-muted mb-0 small">Resumen ejecutivo • {{ now()->translatedFormat('d F Y') }}</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('admin.reconocimientos.index') }}"
               class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-list-ul me-1"></i>Lista
            </a>
            <a href="{{ route('admin.reconocimientos.create') }}"
               class="btn btn-warning btn-sm text-dark">
                <i class="bi bi-plus-lg me-1"></i>Nuevo
            </a>
            <a href="{{ route('admin.reconocimientos.lista-excel') }}"
               class="btn btn-outline-success btn-sm">
                <i class="bi bi-file-earmark-excel me-1"></i>Excel
            </a>
            <a href="{{ route('admin.reconocimientos.lista-pdf') }}"
               class="btn btn-outline-danger btn-sm">
                <i class="bi bi-file-earmark-pdf me-1"></i>PDF
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
                            <i class="bi bi-trophy-fill text-warning"></i>
                        </div>
                    </div>
                    <div class="fw-bold fs-3 text-dark">{{ $total }}</div>
                    <div class="text-muted" style="font-size:.78rem">Historial completo</div>
                </div>
            </div>
        </div>

        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100" style="border-left:4px solid #10b981!important">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <span class="text-muted small">Entregados</span>
                        <div class="rounded-circle d-flex align-items-center justify-content-center"
                             style="width:36px;height:36px;background:#d1fae5">
                            <i class="bi bi-check-circle-fill text-success"></i>
                        </div>
                    </div>
                    <div class="fw-bold fs-3 text-success">{{ $entregados }}</div>
                    <div class="text-muted" style="font-size:.78rem">
                        @if($total > 0) {{ round($entregados / $total * 100) }}% del total @else — @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100" style="border-left:4px solid #ef4444!important">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <span class="text-muted small">Pendientes</span>
                        <div class="rounded-circle d-flex align-items-center justify-content-center"
                             style="width:36px;height:36px;background:#fee2e2">
                            <i class="bi bi-hourglass-split text-danger"></i>
                        </div>
                    </div>
                    <div class="fw-bold fs-3 text-danger">{{ $pendientes }}</div>
                    <div class="text-muted" style="font-size:.78rem">Sin entregar</div>
                </div>
            </div>
        </div>

        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100" style="border-left:4px solid #8b5cf6!important">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <span class="text-muted small">Este mes</span>
                        <div class="rounded-circle d-flex align-items-center justify-content-center"
                             style="width:36px;height:36px;background:#ede9fe">
                            <i class="bi bi-calendar-check-fill" style="color:#8b5cf6"></i>
                        </div>
                    </div>
                    <div class="fw-bold fs-3" style="color:#8b5cf6">{{ $esteMes }}</div>
                    <div class="text-muted" style="font-size:.78rem">{{ now()->translatedFormat('F') }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Alerta pendientes --}}
    @if($pendientes > 0)
    <div class="alert border-0 mb-4 d-flex align-items-center gap-3 py-2 px-3"
         style="background:#fff7ed;border-left:4px solid #f59e0b!important">
        <i class="bi bi-hourglass-split text-warning fs-5"></i>
        <div class="small">
            <strong>{{ $pendientes }} reconocimiento{{ $pendientes != 1 ? 's' : '' }}</strong>
            pendiente{{ $pendientes != 1 ? 's' : '' }} de entrega.
            <a href="{{ route('admin.reconocimientos.index', ['entregado' => '0']) }}"
               class="fw-semibold text-warning-emphasis ms-1">Ver lista</a>
        </div>
    </div>
    @endif

    <div class="row g-3 mb-4">
        {{-- Por tipo --}}
        <div class="col-12 col-lg-5">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 pb-0">
                    <h6 class="fw-semibold mb-0">
                        <i class="bi bi-pie-chart-fill me-2 text-warning"></i>Por tipo de reconocimiento
                    </h6>
                </div>
                <div class="card-body pt-3">
                    @php
                        $coloresMap = [
                            'bg-yellow-400' => ['color'=>'#d97706','bg'=>'#fef3c7'],
                            'bg-blue-400'   => ['color'=>'#2563eb','bg'=>'#dbeafe'],
                            'bg-purple-400' => ['color'=>'#7c3aed','bg'=>'#ede9fe'],
                            'bg-green-400'  => ['color'=>'#059669','bg'=>'#d1fae5'],
                            'bg-rose-400'   => ['color'=>'#e11d48','bg'=>'#ffe4e6'],
                        ];
                        $totalTipos = $porTipo->sum('reconocimientos_count') ?: 1;
                    @endphp
                    @forelse($porTipo as $tipo)
                    @php
                        $c   = $coloresMap[$tipo->color] ?? ['color'=>'#6b7280','bg'=>'#f1f5f9'];
                        $pct = round($tipo->reconocimientos_count / $totalTipos * 100);
                    @endphp
                    <div class="mb-3">
                        <div class="d-flex align-items-center justify-content-between mb-1">
                            <span class="d-flex align-items-center gap-1">
                                <span style="font-size:1rem">{{ $tipo->icono }}</span>
                                <span class="small fw-medium">{{ $tipo->nombre }}</span>
                            </span>
                            <span class="small text-muted">{{ $tipo->reconocimientos_count }} ({{ $pct }}%)</span>
                        </div>
                        <div class="progress" style="height:8px;border-radius:4px">
                            <div class="progress-bar" style="width:{{ $pct }}%;background:{{ $c['color'] }};border-radius:4px"></div>
                        </div>
                    </div>
                    @empty
                    <p class="text-muted small text-center py-3">Sin tipos registrados.</p>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Top estudiantes --}}
        <div class="col-12 col-lg-7">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 pb-0">
                    <h6 class="fw-semibold mb-0">
                        <i class="bi bi-star-fill me-2 text-warning"></i>Estudiantes más reconocidos
                    </h6>
                </div>
                <div class="card-body p-0">
                    @php $maxRec = $topEstudiantes->max('total_rec') ?: 1; @endphp
                    <ul class="list-group list-group-flush">
                        @forelse($topEstudiantes as $i => $row)
                        @php $pctE = round($row->total_rec / $maxRec * 100); @endphp
                        <li class="list-group-item px-3 py-2">
                            <div class="d-flex align-items-center justify-content-between mb-1">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge text-white rounded-circle"
                                          style="background:{{ ['#f59e0b','#94a3b8','#cd7f32','#6b7280','#6b7280'][$i] ?? '#6b7280' }};width:24px;height:24px;font-size:.75rem;display:flex;align-items:center;justify-content:center">
                                        {{ $i + 1 }}
                                    </span>
                                    <div>
                                        <div class="fw-semibold small">
                                            {{ $row->estudiante?->nombres ?? '—' }}
                                            {{ $row->estudiante?->apellidos ?? '' }}
                                        </div>
                                    </div>
                                </div>
                                <span class="badge bg-warning-subtle text-warning rounded-pill fw-bold">
                                    <i class="bi bi-trophy me-1"></i>{{ $row->total_rec }}
                                </span>
                            </div>
                            <div class="progress" style="height:4px;border-radius:2px">
                                <div class="progress-bar bg-warning" style="width:{{ $pctE }}%"></div>
                            </div>
                        </li>
                        @empty
                        <li class="list-group-item text-muted text-center py-4 small">Sin reconocimientos registrados.</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
    </div>

    {{-- Reconocimientos recientes --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-0 pb-0 d-flex justify-content-between align-items-center">
            <h6 class="fw-semibold mb-0">
                <i class="bi bi-clock-history me-2 text-secondary"></i>Últimos reconocimientos
            </h6>
            <a href="{{ route('admin.reconocimientos.index') }}" class="btn btn-link btn-sm p-0 text-muted">Ver todos</a>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-sm mb-0" style="font-size:.82rem">
                    <thead class="table-light">
                        <tr>
                            <th>Estudiante</th>
                            <th>Título</th>
                            <th>Tipo</th>
                            <th>Fecha</th>
                            <th>Estado</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recientes as $rec)
                        @php
                            $c = $coloresMap[$rec->tipo?->color ?? ''] ?? ['color'=>'#6b7280','bg'=>'#f1f5f9'];
                        @endphp
                        <tr>
                            <td>
                                <div class="fw-semibold">
                                    {{ $rec->estudiante?->nombres ?? '—' }}
                                    {{ $rec->estudiante?->apellidos ?? '' }}
                                </div>
                            </td>
                            <td>{{ \Illuminate\Support\Str::limit($rec->titulo, 40) }}</td>
                            <td>
                                @if($rec->tipo)
                                <span class="badge rounded-2 small"
                                      style="background:{{ $c['bg'] }};color:{{ $c['color'] }}">
                                    {{ $rec->tipo->icono }} {{ $rec->tipo->nombre }}
                                </span>
                                @else
                                <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>{{ $rec->fecha?->format('d/m/Y') ?? '—' }}</td>
                            <td>
                                @if($rec->entregado)
                                <span class="badge bg-success-subtle text-success">Entregado</span>
                                @else
                                <span class="badge bg-warning-subtle text-warning">Pendiente</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('admin.reconocimientos.diploma-pdf', $rec->id) }}"
                                   class="btn btn-link btn-sm p-0 text-danger me-1" title="Diploma PDF">
                                    <i class="bi bi-file-earmark-pdf"></i>
                                </a>
                                <a href="{{ route('admin.reconocimientos.edit', $rec->id) }}"
                                   class="btn btn-link btn-sm p-0 text-muted">
                                    <i class="bi bi-pencil-square"></i>
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">Sin reconocimientos registrados.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Catálogo de tipos --}}
    <div class="row g-3 mb-3">
        @foreach($tipos as $tipo)
        @php $c = $coloresMap[$tipo->color] ?? ['color'=>'#6b7280','bg'=>'#f1f5f9']; @endphp
        <div class="col-6 col-md-4 col-lg-2-4">
            <div class="card border-0 shadow-sm text-center p-3 h-100"
                 style="border-top:3px solid {{ $c['color'] }}!important">
                <div class="fs-2 mb-1">{{ $tipo->icono }}</div>
                <div class="fw-semibold small">{{ $tipo->nombre }}</div>
                @if($tipo->descripcion)
                <div class="text-muted" style="font-size:.72rem">{{ \Illuminate\Support\Str::limit($tipo->descripcion, 40) }}</div>
                @endif
            </div>
        </div>
        @endforeach
    </div>

    {{-- Acciones rápidas --}}
    <div class="row g-3">
        @php
        $actions = [
            ['icon'=>'bi-plus-circle-fill','color'=>'#f59e0b','bg'=>'#fef3c7','label'=>'Nuevo reconocimiento','href'=>route('admin.reconocimientos.create')],
            ['icon'=>'bi-list-ul','color'=>'#6b7280','bg'=>'#f1f5f9','label'=>'Ver todos','href'=>route('admin.reconocimientos.index')],
            ['icon'=>'bi-file-earmark-excel-fill','color'=>'#10b981','bg'=>'#d1fae5','label'=>'Exportar Excel','href'=>route('admin.reconocimientos.lista-excel')],
            ['icon'=>'bi-file-earmark-pdf-fill','color'=>'#ef4444','bg'=>'#fee2e2','label'=>'Exportar PDF','href'=>route('admin.reconocimientos.lista-pdf')],
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
