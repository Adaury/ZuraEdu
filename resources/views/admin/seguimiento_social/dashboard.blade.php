@extends('layouts.admin')

@section('title', 'Dashboard Seguimiento Social')

@section('content')
<div class="container-fluid px-4 py-3">

    {{-- Cabecera --}}
    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-0 text-dark">
                <i class="bi bi-people-fill me-2 text-primary"></i>Seguimiento Social
            </h4>
            <p class="text-muted mb-0 small">Resumen ejecutivo • {{ now()->translatedFormat('d F Y') }}</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('admin.seguimiento-social.index') }}"
               class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-list-ul me-1"></i>Lista
            </a>
            <a href="{{ route('admin.seguimiento-social.create') }}"
               class="btn btn-primary btn-sm">
                <i class="bi bi-plus-lg me-1"></i>Nuevo caso
            </a>
            <a href="{{ route('admin.seguimiento-social.lista-excel') }}"
               class="btn btn-outline-success btn-sm">
                <i class="bi bi-file-earmark-excel me-1"></i>Excel
            </a>
            <a href="{{ route('admin.seguimiento-social.lista-pdf') }}"
               class="btn btn-outline-danger btn-sm">
                <i class="bi bi-file-earmark-pdf me-1"></i>PDF
            </a>
        </div>
    </div>

    {{-- Stat cards --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100" style="border-left:4px solid #6366f1!important">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <span class="text-muted small">Total casos</span>
                        <div class="rounded-circle d-flex align-items-center justify-content-center"
                             style="width:36px;height:36px;background:#e0e7ff">
                            <i class="bi bi-folder2-open" style="color:#6366f1"></i>
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
                        <span class="text-muted small">Abiertos</span>
                        <div class="rounded-circle d-flex align-items-center justify-content-center"
                             style="width:36px;height:36px;background:#dbeafe">
                            <i class="bi bi-folder-open text-primary"></i>
                        </div>
                    </div>
                    <div class="fw-bold fs-3 text-primary">{{ $abiertos }}</div>
                    <div class="text-muted" style="font-size:.78rem">En seguimiento activo</div>
                </div>
            </div>
        </div>

        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100" style="border-left:4px solid #ef4444!important">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <span class="text-muted small">Críticos activos</span>
                        <div class="rounded-circle d-flex align-items-center justify-content-center"
                             style="width:36px;height:36px;background:#fee2e2">
                            <i class="bi bi-exclamation-triangle-fill text-danger"></i>
                        </div>
                    </div>
                    <div class="fw-bold fs-3 text-danger">{{ $criticos }}</div>
                    <div class="text-muted" style="font-size:.78rem">Requieren atención urgente</div>
                </div>
            </div>
        </div>

        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100" style="border-left:4px solid #f59e0b!important">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <span class="text-muted small">Este mes</span>
                        <div class="rounded-circle d-flex align-items-center justify-content-center"
                             style="width:36px;height:36px;background:#fef3c7">
                            <i class="bi bi-calendar-plus-fill text-warning"></i>
                        </div>
                    </div>
                    <div class="fw-bold fs-3 text-warning">{{ $esteMes }}</div>
                    <div class="text-muted" style="font-size:.78rem">{{ now()->translatedFormat('F') }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Alerta críticos --}}
    @if($criticos > 0)
    <div class="alert border-0 mb-4 d-flex align-items-center gap-3 py-2 px-3"
         style="background:#fef2f2;border-left:4px solid #ef4444!important">
        <i class="bi bi-exclamation-triangle-fill text-danger fs-5"></i>
        <div class="small">
            <strong>{{ $criticos }} caso{{ $criticos != 1 ? 's' : '' }} crítico{{ $criticos != 1 ? 's' : '' }}</strong>
            requiere{{ $criticos == 1 ? '' : 'n' }} atención urgente.
            <a href="{{ route('admin.seguimiento-social.index', ['nivel_riesgo' => 'critico']) }}"
               class="fw-semibold text-danger ms-1">Ver casos críticos</a>
        </div>
    </div>
    @endif

    <div class="row g-3 mb-4">
        {{-- Por tipo --}}
        <div class="col-12 col-lg-5">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 pb-0">
                    <h6 class="fw-semibold mb-0">
                        <i class="bi bi-pie-chart-fill me-2 text-primary"></i>Por tipo de caso
                    </h6>
                </div>
                <div class="card-body pt-3">
                    @php
                    $tipoStyles = [
                        'academico'  => ['color'=>'#3b82f6','bg'=>'#dbeafe'],
                        'social'     => ['color'=>'#10b981','bg'=>'#d1fae5'],
                        'familiar'   => ['color'=>'#f59e0b','bg'=>'#fef3c7'],
                        'conductual' => ['color'=>'#8b5cf6','bg'=>'#ede9fe'],
                        'otro'       => ['color'=>'#6b7280','bg'=>'#f1f5f9'],
                    ];
                    $maxTipo = max(1, $porTipo->max());
                    @endphp
                    @forelse($porTipo as $tipo => $cnt)
                    @php $s = $tipoStyles[$tipo] ?? ['color'=>'#6b7280','bg'=>'#f1f5f9']; $pct = round($cnt / $maxTipo * 100); @endphp
                    <div class="mb-3">
                        <div class="d-flex align-items-center justify-content-between mb-1">
                            <span class="badge rounded-pill px-2 py-1"
                                  style="background:{{ $s['bg'] }};color:{{ $s['color'] }};font-size:.72rem">
                                {{ \App\Models\CasoSeguimiento::TIPOS[$tipo] ?? ucfirst($tipo) }}
                            </span>
                            <span class="small text-muted">{{ $cnt }}</span>
                        </div>
                        <div class="progress" style="height:8px;border-radius:4px">
                            <div class="progress-bar" style="width:{{ $pct }}%;background:{{ $s['color'] }};border-radius:4px"></div>
                        </div>
                    </div>
                    @empty
                    <p class="text-muted small text-center py-3">Sin casos registrados.</p>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Por nivel de riesgo --}}
        <div class="col-12 col-lg-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 pb-0">
                    <h6 class="fw-semibold mb-0">
                        <i class="bi bi-shield-exclamation me-2 text-warning"></i>Riesgo (abiertos)
                    </h6>
                </div>
                <div class="card-body pt-3">
                    @php
                    $riesgoStyles = [
                        'bajo'    => ['color'=>'#10b981','bg'=>'#d1fae5'],
                        'medio'   => ['color'=>'#f59e0b','bg'=>'#fef3c7'],
                        'alto'    => ['color'=>'#f97316','bg'=>'#ffedd5'],
                        'critico' => ['color'=>'#ef4444','bg'=>'#fee2e2'],
                    ];
                    $riesgoLabels = ['bajo'=>'Bajo','medio'=>'Medio','alto'=>'Alto','critico'=>'Crítico'];
                    $maxRiesgo = max(1, $porRiesgo->max());
                    @endphp
                    @foreach($porRiesgo as $nivel => $cnt)
                    @php $s = $riesgoStyles[$nivel] ?? ['color'=>'#6b7280','bg'=>'#f1f5f9']; $pct = round($cnt / $maxRiesgo * 100); @endphp
                    <div class="mb-3 text-center">
                        <div class="fw-bold fs-4" style="color:{{ $s['color'] }}">{{ $cnt }}</div>
                        <span class="badge rounded-pill"
                              style="background:{{ $s['bg'] }};color:{{ $s['color'] }};font-size:.72rem">
                            {{ $riesgoLabels[$nivel] ?? ucfirst($nivel) }}
                        </span>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Casos críticos activos --}}
        <div class="col-12 col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 pb-0">
                    <h6 class="fw-semibold mb-0">
                        <i class="bi bi-exclamation-triangle-fill me-2 text-danger"></i>Casos críticos
                    </h6>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        @forelse($casosCriticos as $caso)
                        <li class="list-group-item px-3 py-2">
                            <div class="fw-semibold small text-danger">
                                {{ $caso->estudiante?->nombres ?? '—' }} {{ $caso->estudiante?->apellidos ?? '' }}
                            </div>
                            <div class="d-flex align-items-center justify-content-between">
                                <span class="text-muted" style="font-size:.72rem">
                                    {{ \App\Models\CasoSeguimiento::TIPOS[$caso->tipo] ?? $caso->tipo }}
                                </span>
                                <a href="{{ route('admin.seguimiento-social.show', $caso->id) }}"
                                   class="btn btn-link btn-sm p-0 text-muted">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </div>
                        </li>
                        @empty
                        <li class="list-group-item text-muted text-center py-4 small">Sin casos críticos activos.</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
    </div>

    {{-- Últimos casos --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-0 pb-0 d-flex justify-content-between align-items-center">
            <h6 class="fw-semibold mb-0">
                <i class="bi bi-clock-history me-2 text-secondary"></i>Casos recientes
            </h6>
            <a href="{{ route('admin.seguimiento-social.index') }}" class="btn btn-link btn-sm p-0 text-muted">Ver todos</a>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-sm mb-0" style="font-size:.82rem">
                    <thead class="table-light">
                        <tr>
                            <th>Estudiante</th>
                            <th>Tipo</th>
                            <th>Riesgo</th>
                            <th>Estado</th>
                            <th>Intervenciones</th>
                            <th>Apertura</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recientes as $c)
                        @php
                            $ts = $tipoStyles[$c->tipo] ?? ['color'=>'#6b7280','bg'=>'#f1f5f9'];
                            $rs = $riesgoStyles[$c->nivel_riesgo] ?? ['color'=>'#6b7280','bg'=>'#f1f5f9'];
                            $estadoBadge = match($c->estado) {
                                'abierto'        => ['bg'=>'#dbeafe','color'=>'#1d4ed8'],
                                'en_seguimiento' => ['bg'=>'#e0e7ff','color'=>'#4338ca'],
                                'cerrado'        => ['bg'=>'#f1f5f9','color'=>'#374151'],
                                default          => ['bg'=>'#f1f5f9','color'=>'#374151'],
                            };
                        @endphp
                        <tr>
                            <td class="fw-semibold">
                                {{ $c->estudiante?->nombres ?? '—' }} {{ $c->estudiante?->apellidos ?? '' }}
                            </td>
                            <td>
                                <span class="badge rounded-2 small"
                                      style="background:{{ $ts['bg'] }};color:{{ $ts['color'] }}">
                                    {{ \App\Models\CasoSeguimiento::TIPOS[$c->tipo] ?? $c->tipo }}
                                </span>
                            </td>
                            <td>
                                <span class="badge rounded-2 small"
                                      style="background:{{ $rs['bg'] }};color:{{ $rs['color'] }}">
                                    {{ $riesgoLabels[$c->nivel_riesgo] ?? ucfirst($c->nivel_riesgo) }}
                                </span>
                            </td>
                            <td>
                                <span class="badge rounded-2 small"
                                      style="background:{{ $estadoBadge['bg'] }};color:{{ $estadoBadge['color'] }}">
                                    {{ \App\Models\CasoSeguimiento::ESTADOS[$c->estado]['label'] ?? ucfirst($c->estado) }}
                                </span>
                            </td>
                            <td class="text-center">{{ $c->intervenciones_count }}</td>
                            <td>{{ $c->fecha_apertura?->format('d/m/Y') ?? '—' }}</td>
                            <td>
                                <a href="{{ route('admin.seguimiento-social.show', $c->id) }}"
                                   class="btn btn-link btn-sm p-0 text-muted me-1">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="{{ route('admin.seguimiento-social.informe-pdf', $c->id) }}"
                                   class="btn btn-link btn-sm p-0 text-danger">
                                    <i class="bi bi-file-earmark-pdf"></i>
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">Sin casos registrados.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Acciones rápidas --}}
    <div class="row g-3">
        @php
        $actions = [
            ['icon'=>'bi-plus-circle-fill','color'=>'#6366f1','bg'=>'#e0e7ff','label'=>'Nuevo caso','href'=>route('admin.seguimiento-social.create')],
            ['icon'=>'bi-list-ul','color'=>'#6b7280','bg'=>'#f1f5f9','label'=>'Ver todos','href'=>route('admin.seguimiento-social.index')],
            ['icon'=>'bi-file-earmark-excel-fill','color'=>'#10b981','bg'=>'#d1fae5','label'=>'Exportar Excel','href'=>route('admin.seguimiento-social.lista-excel')],
            ['icon'=>'bi-file-earmark-pdf-fill','color'=>'#ef4444','bg'=>'#fee2e2','label'=>'Exportar PDF','href'=>route('admin.seguimiento-social.lista-pdf')],
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
