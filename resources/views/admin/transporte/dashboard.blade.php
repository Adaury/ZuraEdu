@extends('layouts.admin')

@section('title', 'Dashboard Transporte')

@section('content')
<div class="container-fluid px-4 py-3">

    {{-- Cabecera --}}
    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-0 text-dark">
                <i class="bi bi-bus-front-fill me-2 text-primary"></i>Transporte Escolar
            </h4>
            <p class="text-muted mb-0 small">Resumen ejecutivo • {{ now()->translatedFormat('d F Y') }}</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('admin.transporte.index') }}"
               class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-list-ul me-1"></i>Lista de rutas
            </a>
            <a href="{{ route('admin.transporte.create') }}"
               class="btn btn-primary btn-sm">
                <i class="bi bi-plus-lg me-1"></i>Nueva ruta
            </a>
            <a href="{{ route('admin.transporte.lista-excel') }}"
               class="btn btn-outline-success btn-sm">
                <i class="bi bi-file-earmark-excel me-1"></i>Excel
            </a>
            <a href="{{ route('admin.transporte.lista-pdf') }}"
               class="btn btn-outline-danger btn-sm">
                <i class="bi bi-file-earmark-pdf me-1"></i>PDF
            </a>
        </div>
    </div>

    {{-- Stat cards --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100" style="border-left:4px solid #3b82f6!important">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <span class="text-muted small">Rutas Activas</span>
                        <div class="rounded-circle d-flex align-items-center justify-content-center"
                             style="width:36px;height:36px;background:#dbeafe">
                            <i class="bi bi-signpost-fill text-primary"></i>
                        </div>
                    </div>
                    <div class="fw-bold fs-3 text-dark">{{ $rutasActivas }}</div>
                    <div class="text-muted" style="font-size:.78rem">{{ $totalRutas }} en total</div>
                </div>
            </div>
        </div>

        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100" style="border-left:4px solid #10b981!important">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <span class="text-muted small">Estudiantes Inscritos</span>
                        <div class="rounded-circle d-flex align-items-center justify-content-center"
                             style="width:36px;height:36px;background:#d1fae5">
                            <i class="bi bi-people-fill text-success"></i>
                        </div>
                    </div>
                    <div class="fw-bold fs-3 text-success">{{ $totalInscritos }}</div>
                    <div class="text-muted" style="font-size:.78rem">Usan el servicio</div>
                </div>
            </div>
        </div>

        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100" style="border-left:4px solid #f59e0b!important">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <span class="text-muted small">Capacidad Total</span>
                        <div class="rounded-circle d-flex align-items-center justify-content-center"
                             style="width:36px;height:36px;background:#fef3c7">
                            <i class="bi bi-bus-front text-warning"></i>
                        </div>
                    </div>
                    <div class="fw-bold fs-3 text-warning">{{ $capacidadTotal }}</div>
                    <div class="text-muted" style="font-size:.78rem">Cupos disponibles: {{ $capacidadTotal - $ocupacionTotal }}</div>
                </div>
            </div>
        </div>

        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100" style="border-left:4px solid #8b5cf6!important">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <span class="text-muted small">Total Paradas</span>
                        <div class="rounded-circle d-flex align-items-center justify-content-center"
                             style="width:36px;height:36px;background:#ede9fe">
                            <i class="bi bi-geo-alt-fill" style="color:#8b5cf6"></i>
                        </div>
                    </div>
                    <div class="fw-bold fs-3" style="color:#8b5cf6">{{ $totalParadas }}</div>
                    <div class="text-muted" style="font-size:.78rem">En todas las rutas</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Alerta rutas críticas --}}
    @if($rutasCriticas->isNotEmpty())
    <div class="alert border-0 mb-4 d-flex align-items-center gap-3 py-2 px-3"
         style="background:#fff7ed;border-left:4px solid #f59e0b!important">
        <i class="bi bi-exclamation-triangle-fill text-warning fs-5"></i>
        <div class="small">
            <strong>{{ $rutasCriticas->count() }} ruta{{ $rutasCriticas->count() != 1 ? 's' : '' }}</strong>
            con capacidad al 80% o más:
            <span class="fw-semibold">{{ $rutasCriticas->pluck('nombre')->join(', ') }}</span>
        </div>
    </div>
    @endif

    <div class="row g-3 mb-4">
        {{-- Ocupación global --}}
        <div class="col-12 col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 pb-0">
                    <h6 class="fw-semibold mb-0">
                        <i class="bi bi-speedometer me-2 text-primary"></i>Ocupación global
                    </h6>
                </div>
                <div class="card-body pt-3 text-center">
                    {{-- Medidor circular simulado con Bootstrap --}}
                    <div class="position-relative d-inline-flex align-items-center justify-content-center mb-3"
                         style="width:140px;height:140px">
                        <svg width="140" height="140" viewBox="0 0 140 140">
                            <circle cx="70" cy="70" r="55" fill="none" stroke="#e5e7eb" stroke-width="14"/>
                            <circle cx="70" cy="70" r="55" fill="none"
                                    stroke="{{ $pctOcupacion >= 80 ? '#ef4444' : ($pctOcupacion >= 60 ? '#f59e0b' : '#10b981') }}"
                                    stroke-width="14"
                                    stroke-dasharray="{{ round(2 * 3.14159 * 55 * $pctOcupacion / 100) }} 999"
                                    stroke-linecap="round"
                                    transform="rotate(-90 70 70)"/>
                        </svg>
                        <div class="position-absolute text-center">
                            <div class="fw-bold fs-4 text-dark">{{ $pctOcupacion }}%</div>
                            <div class="text-muted" style="font-size:.7rem">Ocupado</div>
                        </div>
                    </div>
                    <div class="d-flex justify-content-center gap-4 mt-2">
                        <div class="text-center">
                            <div class="fw-bold text-success">{{ $capacidadTotal - $ocupacionTotal }}</div>
                            <div class="text-muted" style="font-size:.72rem">Libres</div>
                        </div>
                        <div class="text-center">
                            <div class="fw-bold text-danger">{{ $ocupacionTotal }}</div>
                            <div class="text-muted" style="font-size:.72rem">Ocupados</div>
                        </div>
                    </div>

                    <hr class="my-3">

                    {{-- Por tipo --}}
                    @php
                        $tipoInfo = [
                            'ambos'  => ['label'=>'Ida y vuelta','color'=>'#3b82f6','bg'=>'#dbeafe'],
                            'ida'    => ['label'=>'Solo ida',    'color'=>'#10b981','bg'=>'#d1fae5'],
                            'vuelta' => ['label'=>'Solo vuelta', 'color'=>'#f59e0b','bg'=>'#fef3c7'],
                        ];
                        $totalTipo = $porTipo->sum() ?: 1;
                    @endphp
                    @foreach($tipoInfo as $clave => $ti)
                    @php $cnt = $porTipo[$clave] ?? 0; $pct = round($cnt / $totalTipo * 100); @endphp
                    <div class="mb-2 text-start">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="badge rounded-2 small" style="background:{{ $ti['bg'] }};color:{{ $ti['color'] }}">
                                {{ $ti['label'] }}
                            </span>
                            <span class="small text-muted">{{ $cnt }} ({{ $pct }}%)</span>
                        </div>
                        <div class="progress" style="height:6px;border-radius:3px">
                            <div class="progress-bar" style="width:{{ $pct }}%;background:{{ $ti['color'] }}"></div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Rutas con ocupación --}}
        <div class="col-12 col-lg-8">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 pb-0 d-flex justify-content-between align-items-center">
                    <h6 class="fw-semibold mb-0">
                        <i class="bi bi-map-fill me-2 text-success"></i>Estado de rutas activas
                    </h6>
                    <a href="{{ route('admin.transporte.index') }}" class="btn btn-link btn-sm p-0 text-muted">Ver todas</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-sm mb-0" style="font-size:.82rem">
                            <thead class="table-light">
                                <tr>
                                    <th>Ruta</th>
                                    <th>Conductor</th>
                                    <th>Salida</th>
                                    <th>Inscritos / Cupo</th>
                                    <th>Ocupación</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($rutas as $ruta)
                                @php
                                    $pctR = $ruta->capacidad > 0
                                        ? round($ruta->estudiantes_ruta_count / $ruta->capacidad * 100)
                                        : 0;
                                    $colorBar = $pctR >= 80 ? '#ef4444' : ($pctR >= 60 ? '#f59e0b' : '#10b981');
                                @endphp
                                <tr>
                                    <td>
                                        <div class="fw-semibold">{{ $ruta->nombre }}</div>
                                        @if($ruta->vehiculo)
                                        <div class="text-muted" style="font-size:.72rem">
                                            <i class="bi bi-truck me-1"></i>{{ $ruta->vehiculo }}
                                        </div>
                                        @endif
                                    </td>
                                    <td>
                                        <div>{{ $ruta->conductor ?? '—' }}</div>
                                        @if($ruta->telefono_conductor)
                                        <div class="text-muted" style="font-size:.72rem">
                                            <i class="bi bi-telephone me-1"></i>{{ $ruta->telefono_conductor }}
                                        </div>
                                        @endif
                                    </td>
                                    <td>{{ $ruta->horario_salida ? \Carbon\Carbon::parse($ruta->horario_salida)->format('H:i') : '—' }}</td>
                                    <td class="text-center">
                                        <span class="fw-bold">{{ $ruta->estudiantes_ruta_count }}</span>
                                        <span class="text-muted">/ {{ $ruta->capacidad }}</span>
                                    </td>
                                    <td style="min-width:100px">
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="progress flex-fill" style="height:8px;border-radius:4px">
                                                <div class="progress-bar" style="width:{{ $pctR }}%;background:{{ $colorBar }};border-radius:4px"></div>
                                            </div>
                                            <span class="small fw-bold" style="color:{{ $colorBar }};min-width:36px">{{ $pctR }}%</span>
                                        </div>
                                    </td>
                                    <td>
                                        <a href="{{ route('admin.transporte.show', $ruta->id) }}"
                                           class="btn btn-link btn-sm p-0 text-muted">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">Sin rutas activas.</td>
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
            ['icon'=>'bi-plus-circle-fill','color'=>'#3b82f6','bg'=>'#dbeafe','label'=>'Nueva ruta','href'=>route('admin.transporte.create')],
            ['icon'=>'bi-map-fill','color'=>'#10b981','bg'=>'#d1fae5','label'=>'Ver todas las rutas','href'=>route('admin.transporte.index')],
            ['icon'=>'bi-file-earmark-excel-fill','color'=>'#10b981','bg'=>'#d1fae5','label'=>'Exportar Excel','href'=>route('admin.transporte.lista-excel')],
            ['icon'=>'bi-file-earmark-pdf-fill','color'=>'#ef4444','bg'=>'#fee2e2','label'=>'Exportar PDF','href'=>route('admin.transporte.lista-pdf')],
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
