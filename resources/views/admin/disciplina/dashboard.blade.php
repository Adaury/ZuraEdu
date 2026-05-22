@extends('layouts.admin')

@section('title', 'Dashboard Disciplina')

@section('content')
<div class="container-fluid px-4 py-3">

    {{-- Cabecera --}}
    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-0 text-dark">
                <i class="bi bi-shield-exclamation me-2 text-danger"></i>Disciplina Escolar
            </h4>
            <p class="text-muted mb-0 small">Resumen ejecutivo • {{ now()->translatedFormat('d F Y') }}</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('admin.disciplina.index') }}"
               class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-list-ul me-1"></i>Lista
            </a>
            <a href="{{ route('admin.disciplina.create') }}"
               class="btn btn-danger btn-sm">
                <i class="bi bi-plus-lg me-1"></i>Registrar falta
            </a>
            <a href="{{ route('admin.disciplina.lista-excel') }}"
               class="btn btn-outline-success btn-sm">
                <i class="bi bi-file-earmark-excel me-1"></i>Excel
            </a>
            <a href="{{ route('admin.disciplina.lista-pdf') }}"
               class="btn btn-outline-danger btn-sm">
                <i class="bi bi-file-earmark-pdf me-1"></i>PDF
            </a>
        </div>
    </div>

    {{-- Stat cards --}}
    <div class="row g-3 mb-4">
        {{-- Total --}}
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100" style="border-left:4px solid #6b7280!important">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <span class="text-muted small">Total Faltas</span>
                        <div class="rounded-circle d-flex align-items-center justify-content-center"
                             style="width:36px;height:36px;background:#f1f5f9">
                            <i class="bi bi-shield-exclamation text-secondary"></i>
                        </div>
                    </div>
                    <div class="fw-bold fs-3 text-dark">{{ number_format($total) }}</div>
                    <div class="text-muted" style="font-size:.78rem">Historial completo</div>
                </div>
            </div>
        </div>

        {{-- Pendientes --}}
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100" style="border-left:4px solid #ef4444!important">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <span class="text-muted small">Sin Resolver</span>
                        <div class="rounded-circle d-flex align-items-center justify-content-center"
                             style="width:36px;height:36px;background:#fee2e2">
                            <i class="bi bi-exclamation-triangle-fill text-danger"></i>
                        </div>
                    </div>
                    <div class="fw-bold fs-3 text-danger">{{ number_format($pendientes) }}</div>
                    <div class="text-muted" style="font-size:.78rem">Requieren atención</div>
                </div>
            </div>
        </div>

        {{-- Resueltos --}}
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100" style="border-left:4px solid #10b981!important">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <span class="text-muted small">Resueltas</span>
                        <div class="rounded-circle d-flex align-items-center justify-content-center"
                             style="width:36px;height:36px;background:#d1fae5">
                            <i class="bi bi-check-circle-fill text-success"></i>
                        </div>
                    </div>
                    <div class="fw-bold fs-3 text-success">{{ number_format($resueltos) }}</div>
                    <div class="text-muted" style="font-size:.78rem">
                        @if($total > 0)
                            {{ round($resueltos / $total * 100) }}% del total
                        @else
                            —
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Suspensiones activas --}}
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100" style="border-left:4px solid #7c3aed!important">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <span class="text-muted small">Suspensiones Activas</span>
                        <div class="rounded-circle d-flex align-items-center justify-content-center"
                             style="width:36px;height:36px;background:#ede9fe">
                            <i class="bi bi-slash-circle-fill" style="color:#7c3aed"></i>
                        </div>
                    </div>
                    <div class="fw-bold fs-3" style="color:#7c3aed">{{ number_format($suspensionesActivas) }}</div>
                    <div class="text-muted" style="font-size:.78rem">Sin resolver</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Alerta suspensiones --}}
    @if($suspensionesActivas > 0)
    <div class="alert border-0 mb-4 d-flex align-items-center gap-3 py-2 px-3"
         style="background:#f5f3ff;border-left:4px solid #7c3aed!important">
        <i class="bi bi-slash-circle-fill fs-5" style="color:#7c3aed"></i>
        <div class="small">
            <strong>{{ $suspensionesActivas }} suspensión{{ $suspensionesActivas != 1 ? 'es' : '' }}</strong>
            pendiente{{ $suspensionesActivas != 1 ? 's' : '' }} de resolución.
            <a href="{{ route('admin.disciplina.index', ['tipo' => 'suspension', 'resuelto' => '0']) }}"
               class="fw-semibold ms-1" style="color:#7c3aed">Ver lista</a>
        </div>
    </div>
    @endif

    <div class="row g-3 mb-4">
        {{-- Distribución por tipo --}}
        <div class="col-12 col-lg-5">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 pb-0">
                    <h6 class="fw-semibold mb-0">
                        <i class="bi bi-pie-chart-fill me-2 text-danger"></i>Distribución por tipo
                    </h6>
                </div>
                <div class="card-body pt-3">
                    @php $totalTipos = $porTipo->sum() ?: 1; @endphp
                    @foreach(\App\Models\FaltaDisciplinaria::TIPOS as $tipo => $info)
                    @php
                        $count = $porTipo[$tipo] ?? 0;
                        $pct   = round($count / $totalTipos * 100);
                    @endphp
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge rounded-2 small"
                                      style="background:{{ $info['bg'] }};color:{{ $info['color'] }}">
                                    <i class="bi {{ $info['icon'] }} me-1"></i>{{ $info['label'] }}
                                </span>
                            </div>
                            <span class="small text-muted">{{ $count }} ({{ $pct }}%)</span>
                        </div>
                        <div class="progress" style="height:8px;border-radius:4px">
                            <div class="progress-bar" role="progressbar"
                                 style="width:{{ $pct }}%;background:{{ $info['color'] }};border-radius:4px">
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Tendencia últimos 30 días --}}
        <div class="col-12 col-lg-7">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 pb-0">
                    <h6 class="fw-semibold mb-0">
                        <i class="bi bi-graph-up me-2 text-warning"></i>Comparativa últimos 30 vs 30 días anteriores
                    </h6>
                </div>
                <div class="card-body pt-3">
                    @php
                        $max30 = max($recientes30, $anteriores30, 1);
                        $pct30 = round($recientes30 / $max30 * 100);
                        $pctAnt = round($anteriores30 / $max30 * 100);
                        $tendencia = $recientes30 > $anteriores30 ? 'sube' : ($recientes30 < $anteriores30 ? 'baja' : 'igual');
                    @endphp
                    <div class="d-flex align-items-center gap-4 mb-4">
                        <div class="text-center flex-fill">
                            <div class="fw-bold fs-2 text-dark">{{ $recientes30 }}</div>
                            <div class="text-muted small">Últimos 30 días</div>
                        </div>
                        <div class="text-center" style="font-size:2rem">
                            @if($tendencia === 'sube')
                                <i class="bi bi-arrow-up-circle-fill text-danger"></i>
                            @elseif($tendencia === 'baja')
                                <i class="bi bi-arrow-down-circle-fill text-success"></i>
                            @else
                                <i class="bi bi-dash-circle-fill text-secondary"></i>
                            @endif
                        </div>
                        <div class="text-center flex-fill">
                            <div class="fw-bold fs-2 text-muted">{{ $anteriores30 }}</div>
                            <div class="text-muted small">30 días anteriores</div>
                        </div>
                    </div>
                    <div class="mb-2">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="small">Últimos 30 días</span>
                            <span class="small fw-bold">{{ $recientes30 }}</span>
                        </div>
                        <div class="progress mb-3" style="height:12px;border-radius:6px">
                            <div class="progress-bar bg-danger" style="width:{{ $pct30 }}%;border-radius:6px"></div>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="small">30 días anteriores</span>
                            <span class="small fw-bold">{{ $anteriores30 }}</span>
                        </div>
                        <div class="progress" style="height:12px;border-radius:6px">
                            <div class="progress-bar bg-secondary" style="width:{{ $pctAnt }}%;border-radius:6px"></div>
                        </div>
                    </div>
                    @if($recientes30 > $anteriores30 && $anteriores30 > 0)
                    <div class="alert alert-warning py-1 px-2 mt-3 mb-0 small">
                        <i class="bi bi-exclamation-triangle me-1"></i>
                        Las faltas <strong>aumentaron {{ round(($recientes30 - $anteriores30) / $anteriores30 * 100) }}%</strong>
                        respecto al período anterior.
                    </div>
                    @elseif($recientes30 < $anteriores30 && $anteriores30 > 0)
                    <div class="alert alert-success py-1 px-2 mt-3 mb-0 small">
                        <i class="bi bi-check-circle me-1"></i>
                        Las faltas <strong>disminuyeron {{ round(($anteriores30 - $recientes30) / $anteriores30 * 100) }}%</strong>
                        respecto al período anterior.
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        {{-- Top estudiantes con más faltas --}}
        <div class="col-12 col-lg-5">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 pb-0">
                    <h6 class="fw-semibold mb-0">
                        <i class="bi bi-person-exclamation me-2 text-danger"></i>Estudiantes con más faltas
                    </h6>
                </div>
                <div class="card-body p-0">
                    @php $maxFaltas = $topEstudiantes->max('total_faltas') ?: 1; @endphp
                    <ul class="list-group list-group-flush">
                        @forelse($topEstudiantes as $i => $row)
                        @php $pctF = round($row->total_faltas / $maxFaltas * 100); @endphp
                        <li class="list-group-item px-3 py-2">
                            <div class="d-flex align-items-center justify-content-between mb-1">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge bg-danger text-white rounded-circle"
                                          style="width:22px;height:22px;font-size:.7rem;display:flex;align-items:center;justify-content:center">
                                        {{ $i + 1 }}
                                    </span>
                                    <div>
                                        <div class="fw-semibold small">
                                            {{ $row->estudiante?->nombres ?? '—' }}
                                            {{ $row->estudiante?->apellidos ?? '' }}
                                        </div>
                                    </div>
                                </div>
                                <span class="badge bg-danger rounded-pill">{{ $row->total_faltas }}</span>
                            </div>
                            <div class="progress" style="height:4px;border-radius:2px">
                                <div class="progress-bar bg-danger" style="width:{{ $pctF }}%"></div>
                            </div>
                        </li>
                        @empty
                        <li class="list-group-item text-muted text-center py-4 small">Sin registros.</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>

        {{-- Últimas faltas --}}
        <div class="col-12 col-lg-7">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 pb-0 d-flex justify-content-between align-items-center">
                    <h6 class="fw-semibold mb-0">
                        <i class="bi bi-clock-history me-2 text-secondary"></i>Últimas faltas registradas
                    </h6>
                    <a href="{{ route('admin.disciplina.index') }}" class="btn btn-link btn-sm p-0 text-muted">Ver todas</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-sm mb-0" style="font-size:.82rem">
                            <thead class="table-light">
                                <tr>
                                    <th>Estudiante</th>
                                    <th>Tipo</th>
                                    <th>Fecha</th>
                                    <th>Estado</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($ultimasFaltas as $falta)
                                @php $info = $falta->tipo_info; @endphp
                                <tr>
                                    <td>
                                        <div class="fw-semibold">
                                            {{ $falta->estudiante?->nombres ?? '—' }}
                                            {{ $falta->estudiante?->apellidos ?? '' }}
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge rounded-2 small"
                                              style="background:{{ $info['bg'] }};color:{{ $info['color'] }}">
                                            <i class="bi {{ $info['icon'] }} me-1"></i>{{ $info['label'] }}
                                        </span>
                                    </td>
                                    <td>{{ $falta->fecha?->format('d/m/Y') ?? '—' }}</td>
                                    <td>
                                        @if($falta->resuelto)
                                        <span class="badge bg-success-subtle text-success">Resuelto</span>
                                        @else
                                        <span class="badge bg-danger-subtle text-danger">Pendiente</span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('admin.disciplina.edit', $falta->id) }}"
                                           class="btn btn-link btn-sm p-0 text-muted">
                                            <i class="bi bi-pencil-square"></i>
                                        </a>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">
                                        Sin registros de faltas.
                                    </td>
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
            ['icon'=>'bi-plus-circle-fill','color'=>'#ef4444','bg'=>'#fee2e2','label'=>'Registrar falta','href'=>route('admin.disciplina.create')],
            ['icon'=>'bi-list-ul','color'=>'#6b7280','bg'=>'#f1f5f9','label'=>'Ver todas las faltas','href'=>route('admin.disciplina.index')],
            ['icon'=>'bi-file-earmark-excel-fill','color'=>'#10b981','bg'=>'#d1fae5','label'=>'Exportar Excel','href'=>route('admin.disciplina.lista-excel')],
            ['icon'=>'bi-file-earmark-pdf-fill','color'=>'#ef4444','bg'=>'#fee2e2','label'=>'Exportar PDF','href'=>route('admin.disciplina.lista-pdf')],
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
