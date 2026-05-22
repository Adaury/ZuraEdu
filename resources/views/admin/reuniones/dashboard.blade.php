@extends('layouts.admin')

@section('title', 'Dashboard Reuniones')

@section('content')
<div class="container-fluid px-4 py-3">

    {{-- Cabecera --}}
    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-0 text-dark">
                <i class="bi bi-journal-text me-2 text-primary"></i>Actas de Reuniones
            </h4>
            <p class="text-muted mb-0 small">Resumen ejecutivo • {{ now()->translatedFormat('d F Y') }}</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('admin.reuniones.index') }}"
               class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-list-ul me-1"></i>Lista
            </a>
            <a href="{{ route('admin.reuniones.create') }}"
               class="btn btn-primary btn-sm">
                <i class="bi bi-plus-lg me-1"></i>Nueva reunión
            </a>
            <a href="{{ route('admin.reuniones.lista-excel') }}"
               class="btn btn-outline-success btn-sm">
                <i class="bi bi-file-earmark-excel me-1"></i>Excel
            </a>
            <a href="{{ route('admin.reuniones.lista-pdf') }}"
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
                        <span class="text-muted small">Total</span>
                        <div class="rounded-circle d-flex align-items-center justify-content-center"
                             style="width:36px;height:36px;background:#e0e7ff">
                            <i class="bi bi-journal-text" style="color:#6366f1"></i>
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
                        <span class="text-muted small">Programadas</span>
                        <div class="rounded-circle d-flex align-items-center justify-content-center"
                             style="width:36px;height:36px;background:#dbeafe">
                            <i class="bi bi-calendar-event-fill text-primary"></i>
                        </div>
                    </div>
                    <div class="fw-bold fs-3 text-primary">{{ $programadas }}</div>
                    <div class="text-muted" style="font-size:.78rem">Próximas</div>
                </div>
            </div>
        </div>

        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100" style="border-left:4px solid #10b981!important">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <span class="text-muted small">Realizadas</span>
                        <div class="rounded-circle d-flex align-items-center justify-content-center"
                             style="width:36px;height:36px;background:#d1fae5">
                            <i class="bi bi-check-circle-fill text-success"></i>
                        </div>
                    </div>
                    <div class="fw-bold fs-3 text-success">{{ $realizadas }}</div>
                    <div class="text-muted" style="font-size:.78rem">Completadas</div>
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
                            <i class="bi bi-calendar-check-fill text-warning"></i>
                        </div>
                    </div>
                    <div class="fw-bold fs-3 text-warning">{{ $esteMes }}</div>
                    <div class="text-muted" style="font-size:.78rem">{{ now()->translatedFormat('F') }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Alertas acuerdos --}}
    @if($acuerdosVencidos > 0)
    <div class="alert border-0 mb-3 d-flex align-items-center gap-3 py-2 px-3"
         style="background:#fef2f2;border-left:4px solid #ef4444!important">
        <i class="bi bi-exclamation-circle-fill text-danger fs-5"></i>
        <div class="small">
            <strong>{{ $acuerdosVencidos }} acuerdo{{ $acuerdosVencidos != 1 ? 's' : '' }}</strong>
            vencido{{ $acuerdosVencidos != 1 ? 's' : '' }} sin cumplir (fecha límite pasada).
        </div>
    </div>
    @endif
    @if($acuerdosPendientes > 0)
    <div class="alert border-0 mb-4 d-flex align-items-center gap-3 py-2 px-3"
         style="background:#fffbeb;border-left:4px solid #f59e0b!important">
        <i class="bi bi-hourglass-split text-warning fs-5"></i>
        <div class="small">
            <strong>{{ $acuerdosPendientes }} acuerdo{{ $acuerdosPendientes != 1 ? 's' : '' }}</strong>
            pendiente{{ $acuerdosPendientes != 1 ? 's' : '' }} de cumplimiento.
        </div>
    </div>
    @endif

    <div class="row g-3 mb-4">
        {{-- Por tipo --}}
        <div class="col-12 col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 pb-0">
                    <h6 class="fw-semibold mb-0">
                        <i class="bi bi-pie-chart-fill me-2 text-primary"></i>Por tipo de reunión
                    </h6>
                </div>
                <div class="card-body pt-3">
                    @php
                    $tipoStyles = [
                        'consejo_directivo' => ['color'=>'#6366f1','bg'=>'#e0e7ff'],
                        'reunion_padres'    => ['color'=>'#f59e0b','bg'=>'#fef3c7'],
                        'reunion_docentes'  => ['color'=>'#3b82f6','bg'=>'#dbeafe'],
                        'comite'            => ['color'=>'#10b981','bg'=>'#d1fae5'],
                        'otra'              => ['color'=>'#6b7280','bg'=>'#f1f5f9'],
                    ];
                    $maxTipo = max(1, $porTipo->max());
                    @endphp
                    @forelse($porTipo as $tipo => $cnt)
                    @php $s = $tipoStyles[$tipo] ?? ['color'=>'#6b7280','bg'=>'#f1f5f9']; $pct = round($cnt / $maxTipo * 100); @endphp
                    <div class="mb-3">
                        <div class="d-flex align-items-center justify-content-between mb-1">
                            <span class="small fw-medium">{{ \App\Models\Reunion::tiposLabel()[$tipo] ?? ucfirst($tipo) }}</span>
                            <span class="small text-muted">{{ $cnt }}</span>
                        </div>
                        <div class="progress" style="height:8px;border-radius:4px">
                            <div class="progress-bar" style="width:{{ $pct }}%;background:{{ $s['color'] }};border-radius:4px"></div>
                        </div>
                    </div>
                    @empty
                    <p class="text-muted small text-center py-3">Sin reuniones registradas.</p>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Próximas reuniones --}}
        <div class="col-12 col-lg-8">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 pb-0">
                    <h6 class="fw-semibold mb-0">
                        <i class="bi bi-calendar-week-fill me-2 text-success"></i>Próximas reuniones programadas
                    </h6>
                </div>
                <div class="card-body p-0">
                    @forelse($proximas as $p)
                    @php $s = $tipoStyles[$p->tipo] ?? ['color'=>'#6b7280','bg'=>'#f1f5f9']; @endphp
                    <div class="d-flex align-items-center gap-3 px-3 py-2 border-bottom">
                        <div class="text-center flex-shrink-0" style="min-width:44px">
                            <div class="fw-bold fs-5 lh-1" style="color:{{ $s['color'] }}">{{ $p->fecha->format('d') }}</div>
                            <div class="text-muted" style="font-size:.7rem;text-transform:uppercase">{{ $p->fecha->translatedFormat('M') }}</div>
                        </div>
                        <div class="flex-grow-1 min-w-0">
                            <div class="fw-semibold small text-truncate">{{ $p->titulo }}</div>
                            <div class="text-muted d-flex align-items-center gap-2 flex-wrap" style="font-size:.72rem">
                                <span class="badge rounded-2" style="background:{{ $s['bg'] }};color:{{ $s['color'] }}">
                                    {{ $p->tipoLabel() }}
                                </span>
                                @if($p->lugar)
                                <span><i class="bi bi-geo-alt me-1"></i>{{ $p->lugar }}</span>
                                @endif
                                <span><i class="bi bi-clock me-1"></i>{{ $p->fecha->format('H:i') }}</span>
                            </div>
                        </div>
                        <a href="{{ route('admin.reuniones.show', $p->id) }}"
                           class="btn btn-link btn-sm p-0 text-muted flex-shrink-0">
                            <i class="bi bi-eye"></i>
                        </a>
                    </div>
                    @empty
                    <p class="text-muted text-center py-4 small">No hay reuniones programadas próximamente.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    {{-- Últimas reuniones --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-0 pb-0 d-flex justify-content-between align-items-center">
            <h6 class="fw-semibold mb-0">
                <i class="bi bi-clock-history me-2 text-secondary"></i>Últimas reuniones
            </h6>
            <a href="{{ route('admin.reuniones.index') }}" class="btn btn-link btn-sm p-0 text-muted">Ver todas</a>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-sm mb-0" style="font-size:.82rem">
                    <thead class="table-light">
                        <tr>
                            <th>Título</th>
                            <th>Tipo</th>
                            <th>Fecha</th>
                            <th>Estado</th>
                            <th>Acuerdos</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recientes as $r)
                        @php
                            $s = $tipoStyles[$r->tipo] ?? ['color'=>'#6b7280','bg'=>'#f1f5f9'];
                            $estadoBadge = match($r->estado) {
                                'programada' => ['bg'=>'#dbeafe','color'=>'#1d4ed8'],
                                'realizada'  => ['bg'=>'#d1fae5','color'=>'#065f46'],
                                'cancelada'  => ['bg'=>'#fee2e2','color'=>'#991b1b'],
                                default      => ['bg'=>'#f1f5f9','color'=>'#374151'],
                            };
                        @endphp
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ \Illuminate\Support\Str::limit($r->titulo, 40) }}</div>
                                @if($r->lugar)<div class="text-muted" style="font-size:.72rem"><i class="bi bi-geo-alt me-1"></i>{{ $r->lugar }}</div>@endif
                            </td>
                            <td>
                                <span class="badge rounded-2 small"
                                      style="background:{{ $s['bg'] }};color:{{ $s['color'] }}">
                                    {{ $r->tipoLabel() }}
                                </span>
                            </td>
                            <td>{{ $r->fecha?->format('d/m/Y') ?? '—' }}</td>
                            <td>
                                <span class="badge rounded-2 small"
                                      style="background:{{ $estadoBadge['bg'] }};color:{{ $estadoBadge['color'] }}">
                                    {{ $r->estadoLabel() }}
                                </span>
                            </td>
                            <td class="text-center">
                                @if($r->acuerdos_count > 0)
                                <span class="badge bg-secondary-subtle text-secondary rounded-pill">{{ $r->acuerdos_count }}</span>
                                @else
                                <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('admin.reuniones.show', $r->id) }}"
                                   class="btn btn-link btn-sm p-0 text-muted me-1">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="{{ route('admin.reuniones.acta_pdf', $r->id) }}"
                                   class="btn btn-link btn-sm p-0 text-danger">
                                    <i class="bi bi-file-earmark-pdf"></i>
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">Sin reuniones registradas.</td>
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
            ['icon'=>'bi-plus-circle-fill','color'=>'#6366f1','bg'=>'#e0e7ff','label'=>'Nueva reunión','href'=>route('admin.reuniones.create')],
            ['icon'=>'bi-list-ul','color'=>'#6b7280','bg'=>'#f1f5f9','label'=>'Ver todas','href'=>route('admin.reuniones.index')],
            ['icon'=>'bi-file-earmark-excel-fill','color'=>'#10b981','bg'=>'#d1fae5','label'=>'Exportar Excel','href'=>route('admin.reuniones.lista-excel')],
            ['icon'=>'bi-file-earmark-pdf-fill','color'=>'#ef4444','bg'=>'#fee2e2','label'=>'Exportar PDF','href'=>route('admin.reuniones.lista-pdf')],
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
