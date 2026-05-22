@extends('layouts.admin')

@section('title', 'Dashboard Becas')

@section('content')
<div class="container-fluid px-4 py-3">

    {{-- Cabecera --}}
    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-0 text-dark">
                <i class="bi bi-award-fill me-2 text-warning"></i>Becas y Descuentos
            </h4>
            <p class="text-muted mb-0 small">Resumen ejecutivo • {{ now()->translatedFormat('d F Y') }}</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('admin.becas.index') }}"
               class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-list-ul me-1"></i>Lista
            </a>
            <a href="{{ route('admin.becas.create') }}"
               class="btn btn-warning btn-sm text-dark">
                <i class="bi bi-plus-lg me-1"></i>Nueva beca
            </a>
            <a href="{{ route('admin.becas.becados') }}"
               class="btn btn-outline-primary btn-sm">
                <i class="bi bi-people me-1"></i>Becados
            </a>
            <a href="{{ route('admin.becas.reporte-excel') }}"
               class="btn btn-outline-success btn-sm">
                <i class="bi bi-file-earmark-excel me-1"></i>Excel
            </a>
        </div>
    </div>

    {{-- Stat cards --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100" style="border-left:4px solid #f59e0b!important">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <span class="text-muted small">Becas Activas</span>
                        <div class="rounded-circle d-flex align-items-center justify-content-center"
                             style="width:36px;height:36px;background:#fef3c7">
                            <i class="bi bi-award-fill text-warning"></i>
                        </div>
                    </div>
                    <div class="fw-bold fs-3 text-dark">{{ $becasActivas }}</div>
                    <div class="text-muted" style="font-size:.78rem">{{ $totalBecas }} en total</div>
                </div>
            </div>
        </div>

        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100" style="border-left:4px solid #10b981!important">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <span class="text-muted small">Beneficiarios Activos</span>
                        <div class="rounded-circle d-flex align-items-center justify-content-center"
                             style="width:36px;height:36px;background:#d1fae5">
                            <i class="bi bi-people-fill text-success"></i>
                        </div>
                    </div>
                    <div class="fw-bold fs-3 text-success">{{ $totalBecados }}</div>
                    <div class="text-muted" style="font-size:.78rem">Asignaciones vigentes</div>
                </div>
            </div>
        </div>

        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100" style="border-left:4px solid #6366f1!important">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <span class="text-muted small">Tipo: Porcentaje</span>
                        <div class="rounded-circle d-flex align-items-center justify-content-center"
                             style="width:36px;height:36px;background:#e0e7ff">
                            <i class="bi bi-percent" style="color:#6366f1"></i>
                        </div>
                    </div>
                    <div class="fw-bold fs-3" style="color:#6366f1">{{ $porcentajeBecas }}</div>
                    <div class="text-muted" style="font-size:.78rem">Becas por porcentaje</div>
                </div>
            </div>
        </div>

        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100" style="border-left:4px solid #3b82f6!important">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <span class="text-muted small">Tipo: Monto Fijo</span>
                        <div class="rounded-circle d-flex align-items-center justify-content-center"
                             style="width:36px;height:36px;background:#dbeafe">
                            <i class="bi bi-cash-coin text-primary"></i>
                        </div>
                    </div>
                    <div class="fw-bold fs-3 text-primary">{{ $montoFijoBecas }}</div>
                    <div class="text-muted" style="font-size:.78rem">Becas monto fijo</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        {{-- Listado de becas con beneficiarios --}}
        <div class="col-12 col-lg-5">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 pb-0">
                    <h6 class="fw-semibold mb-0">
                        <i class="bi bi-trophy-fill me-2 text-warning"></i>Becas con más beneficiarios
                    </h6>
                </div>
                <div class="card-body p-0">
                    @php $maxB = $becasMasBecados->max('becados_count') ?: 1; @endphp
                    <ul class="list-group list-group-flush">
                        @forelse($becasMasBecados as $i => $beca)
                        @php
                            $pct = round($beca->becados_count / $maxB * 100);
                            $badgeColor = $beca->tipo === 'porcentaje' ? '#6366f1' : '#10b981';
                            $badgeBg    = $beca->tipo === 'porcentaje' ? '#e0e7ff' : '#d1fae5';
                        @endphp
                        <li class="list-group-item px-3 py-2">
                            <div class="d-flex align-items-center justify-content-between mb-1">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge text-white rounded-circle"
                                          style="background:{{ ['#f59e0b','#94a3b8','#cd7f32','#6b7280','#6b7280'][$i] ?? '#6b7280' }};width:22px;height:22px;font-size:.7rem;display:flex;align-items:center;justify-content:center">
                                        {{ $i + 1 }}
                                    </span>
                                    <div>
                                        <div class="fw-semibold small">{{ $beca->nombre }}</div>
                                        <span class="badge rounded-2" style="background:{{ $badgeBg }};color:{{ $badgeColor }};font-size:.65rem">
                                            {{ $beca->tipo === 'porcentaje' ? $beca->valor . '%' : 'RD$ ' . number_format($beca->valor, 0) }}
                                        </span>
                                    </div>
                                </div>
                                <span class="badge bg-success-subtle text-success rounded-pill">
                                    <i class="bi bi-people me-1"></i>{{ $beca->becados_count }}
                                </span>
                            </div>
                            <div class="progress" style="height:4px;border-radius:2px">
                                <div class="progress-bar bg-warning" style="width:{{ $pct }}%"></div>
                            </div>
                        </li>
                        @empty
                        <li class="list-group-item text-muted text-center py-4 small">Sin becas activas con beneficiarios.</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>

        {{-- Asignaciones recientes --}}
        <div class="col-12 col-lg-7">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 pb-0 d-flex justify-content-between align-items-center">
                    <h6 class="fw-semibold mb-0">
                        <i class="bi bi-clock-history me-2 text-secondary"></i>Asignaciones activas recientes
                    </h6>
                    <a href="{{ route('admin.becas.becados') }}" class="btn btn-link btn-sm p-0 text-muted">Ver todas</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-sm mb-0" style="font-size:.82rem">
                            <thead class="table-light">
                                <tr>
                                    <th>Estudiante</th>
                                    <th>Beca</th>
                                    <th>Descuento</th>
                                    <th>Vigencia</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recientes as $asig)
                                <tr>
                                    <td>
                                        <div class="fw-semibold">
                                            {{ $asig->matricula?->estudiante?->nombres ?? '—' }}
                                            {{ $asig->matricula?->estudiante?->apellidos ?? '' }}
                                        </div>
                                    </td>
                                    <td>
                                        <span class="fw-medium">{{ $asig->beca?->nombre ?? '—' }}</span>
                                    </td>
                                    <td>
                                        @if($asig->beca)
                                        @if($asig->beca->tipo === 'porcentaje')
                                        <span class="badge rounded-2" style="background:#e0e7ff;color:#6366f1">
                                            {{ $asig->beca->valor }}%
                                        </span>
                                        @else
                                        <span class="badge rounded-2" style="background:#d1fae5;color:#10b981">
                                            RD${{ number_format($asig->beca->valor, 0) }}
                                        </span>
                                        @endif
                                        @else
                                        —
                                        @endif
                                    </td>
                                    <td>
                                        @if($asig->fecha_fin)
                                        <span class="small text-muted">Hasta {{ $asig->fecha_fin->format('d/m/Y') }}</span>
                                        @else
                                        <span class="badge bg-success-subtle text-success small">Indefinida</span>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">Sin asignaciones activas.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Catálogo de becas --}}
    @if($becasLista->isNotEmpty())
    <div class="card border-0 shadow-sm mt-3">
        <div class="card-header bg-white border-0 pb-0">
            <h6 class="fw-semibold mb-0">
                <i class="bi bi-grid-fill me-2 text-warning"></i>Catálogo de becas activas
            </h6>
        </div>
        <div class="card-body">
            <div class="row g-2">
                @foreach($becasLista as $beca)
                @php
                    $cnt = $beca->asignacionesActivas->count();
                    $isPct = $beca->tipo === 'porcentaje';
                @endphp
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="border rounded-2 p-3 h-100 d-flex flex-column gap-1"
                         style="border-color:{{ $isPct ? '#c4b5fd' : '#6ee7b7' }}!important">
                        <div class="d-flex align-items-center justify-content-between">
                            <span class="fw-semibold small">{{ $beca->nombre }}</span>
                            <span class="badge rounded-2 fw-bold"
                                  style="background:{{ $isPct ? '#e0e7ff' : '#d1fae5' }};color:{{ $isPct ? '#6366f1' : '#10b981' }}">
                                {{ $isPct ? $beca->valor . '%' : 'RD$ ' . number_format($beca->valor, 0, ',', '.') }}
                            </span>
                        </div>
                        @if($beca->criterio)
                        <div class="text-muted" style="font-size:.75rem">{{ $beca->criterio }}</div>
                        @endif
                        <div class="mt-auto">
                            <span class="badge bg-light text-secondary">
                                <i class="bi bi-people me-1"></i>{{ $cnt }} becado{{ $cnt != 1 ? 's' : '' }}
                            </span>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    {{-- Acciones rápidas --}}
    <div class="row g-3 mt-3">
        @php
        $actions = [
            ['icon'=>'bi-plus-circle-fill','color'=>'#f59e0b','bg'=>'#fef3c7','label'=>'Nueva beca','href'=>route('admin.becas.create')],
            ['icon'=>'bi-people-fill','color'=>'#10b981','bg'=>'#d1fae5','label'=>'Ver becados','href'=>route('admin.becas.becados')],
            ['icon'=>'bi-file-earmark-excel-fill','color'=>'#10b981','bg'=>'#d1fae5','label'=>'Exportar Excel','href'=>route('admin.becas.reporte-excel')],
            ['icon'=>'bi-file-earmark-pdf-fill','color'=>'#ef4444','bg'=>'#fee2e2','label'=>'Exportar PDF','href'=>route('admin.becas.reporte-pdf')],
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
