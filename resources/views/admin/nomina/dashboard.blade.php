@extends('layouts.admin')

@section('title', 'Dashboard Nómina')

@section('content')
<div class="container-fluid px-4 py-3">

    {{-- Cabecera --}}
    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-0 text-dark">
                <i class="bi bi-cash-stack me-2 text-success"></i>Nómina de Empleados
            </h4>
            <p class="text-muted mb-0 small">Resumen ejecutivo • {{ now()->translatedFormat('F Y') }}</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('admin.nomina.index') }}"
               class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-list-ul me-1"></i>Lista
            </a>
            <a href="{{ route('admin.nomina.create') }}"
               class="btn btn-success btn-sm">
                <i class="bi bi-person-plus me-1"></i>Nuevo empleado
            </a>
            <a href="{{ route('admin.nomina.excel', ['mes' => $mesAct]) }}"
               class="btn btn-outline-success btn-sm">
                <i class="bi bi-file-earmark-excel me-1"></i>Excel {{ $mesAct }}
            </a>
            <a href="{{ route('admin.nomina.pdf', ['mes' => $mesAct]) }}"
               class="btn btn-outline-danger btn-sm">
                <i class="bi bi-file-earmark-pdf me-1"></i>PDF {{ $mesAct }}
            </a>
        </div>
    </div>

    {{-- Stat cards fila 1 --}}
    <div class="row g-3 mb-4">
        {{-- Empleados activos --}}
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100" style="border-left:4px solid #10b981!important">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <span class="text-muted small">Empleados Activos</span>
                        <div class="rounded-circle d-flex align-items-center justify-content-center"
                             style="width:36px;height:36px;background:#d1fae5">
                            <i class="bi bi-people-fill text-success"></i>
                        </div>
                    </div>
                    <div class="fw-bold fs-3 text-dark">{{ number_format($activos) }}</div>
                    <div class="text-muted" style="font-size:.78rem">{{ $inactivos }} inactivo{{ $inactivos != 1 ? 's' : '' }}</div>
                </div>
            </div>
        </div>

        {{-- Masa salarial --}}
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100" style="border-left:4px solid #3b82f6!important">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <span class="text-muted small">Masa Salarial Mensual</span>
                        <div class="rounded-circle d-flex align-items-center justify-content-center"
                             style="width:36px;height:36px;background:#dbeafe">
                            <i class="bi bi-bank2 text-primary"></i>
                        </div>
                    </div>
                    <div class="fw-bold fs-5 text-dark">RD${{ number_format($masaSalarial, 0, ',', '.') }}</div>
                    <div class="text-muted" style="font-size:.78rem">Base bruta activos</div>
                </div>
            </div>
        </div>

        {{-- Pagado este mes --}}
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100" style="border-left:4px solid #8b5cf6!important">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <span class="text-muted small">Pagado en {{ now()->translatedFormat('F') }}</span>
                        <div class="rounded-circle d-flex align-items-center justify-content-center"
                             style="width:36px;height:36px;background:#ede9fe">
                            <i class="bi bi-check2-circle" style="color:#8b5cf6"></i>
                        </div>
                    </div>
                    <div class="fw-bold fs-5 text-dark">RD${{ number_format($totalPagadoMes, 0, ',', '.') }}</div>
                    <div class="text-muted" style="font-size:.78rem">{{ $countPagados }} empleado{{ $countPagados != 1 ? 's' : '' }} cobrados</div>
                </div>
            </div>
        </div>

        {{-- Total anual --}}
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100" style="border-left:4px solid #f59e0b!important">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <span class="text-muted small">Acumulado {{ now()->year }}</span>
                        <div class="rounded-circle d-flex align-items-center justify-content-center"
                             style="width:36px;height:36px;background:#fef3c7">
                            <i class="bi bi-graph-up-arrow text-warning"></i>
                        </div>
                    </div>
                    <div class="fw-bold fs-5 text-dark">RD${{ number_format($totalAnual, 0, ',', '.') }}</div>
                    <div class="text-muted" style="font-size:.78rem">Pagos netos confirmados</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Alerta pendientes del mes --}}
    @if($countPendientes > 0)
    <div class="alert border-0 mb-4 d-flex align-items-center gap-3 py-2 px-3"
         style="background:#fefce8;border-left:4px solid #facc15!important">
        <i class="bi bi-exclamation-triangle-fill text-warning fs-5"></i>
        <div class="small">
            <strong>{{ $countPendientes }} empleado{{ $countPendientes != 1 ? 's' : '' }}</strong>
            aún no cobrado{{ $countPendientes != 1 ? 's' : '' }} en
            <strong>{{ now()->translatedFormat('F Y') }}</strong>.
            <a href="{{ route('admin.nomina.index', ['mes' => $mesAct, 'estado' => 'pendiente']) }}"
               class="text-warning-emphasis fw-semibold ms-1">Ver lista</a>
        </div>
    </div>
    @endif

    <div class="row g-3 mb-4">
        {{-- Evolución salarial últimos 6 meses --}}
        <div class="col-12 col-lg-7">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 pb-0">
                    <h6 class="fw-semibold mb-0"><i class="bi bi-bar-chart-fill me-2 text-primary"></i>Pagos netos — últimos 6 meses</h6>
                </div>
                <div class="card-body pt-3">
                    @php
                        $maxEv = $evolucion->max('neto') ?: 1;
                    @endphp
                    <div class="d-flex align-items-end gap-2" style="height:140px">
                        @foreach($evolucion as $ev)
                        <div class="flex-fill text-center position-relative" style="height:100%">
                            @php $pct = max(4, round($ev['neto'] / $maxEv * 100)); @endphp
                            <div class="position-absolute bottom-0 start-50 translate-middle-x w-100 rounded-top"
                                 style="height:{{ $pct }}%;background:{{ $ev['neto'] > 0 ? '#3b82f6' : '#e5e7eb' }}"
                                 title="RD${{ number_format($ev['neto'], 0, ',', '.') }}">
                            </div>
                        </div>
                        @endforeach
                    </div>
                    <div class="d-flex gap-2 mt-2">
                        @foreach($evolucion as $ev)
                        <div class="flex-fill text-center" style="font-size:.7rem;color:#6b7280">{{ $ev['mes'] }}</div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        {{-- Distribución por tipo de contrato --}}
        <div class="col-12 col-lg-5">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 pb-0">
                    <h6 class="fw-semibold mb-0"><i class="bi bi-diagram-3-fill me-2 text-success"></i>Tipo de contrato</h6>
                </div>
                <div class="card-body pt-3">
                    @php
                        $tiposLabel = ['fijo' => 'Fijo', 'temporal' => 'Temporal', 'hora' => 'Por hora'];
                        $tiposColor = ['fijo' => '#10b981', 'temporal' => '#3b82f6', 'hora' => '#f59e0b'];
                        $totalContratos = $porContrato->sum() ?: 1;
                    @endphp
                    @forelse($porContrato as $tipo => $count)
                    @php $pct = round($count / $totalContratos * 100); @endphp
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="small fw-medium">{{ $tiposLabel[$tipo] ?? ucfirst($tipo) }}</span>
                            <span class="small text-muted">{{ $count }} ({{ $pct }}%)</span>
                        </div>
                        <div class="progress" style="height:8px;border-radius:4px">
                            <div class="progress-bar" role="progressbar"
                                 style="width:{{ $pct }}%;background:{{ $tiposColor[$tipo] ?? '#6b7280' }};border-radius:4px">
                            </div>
                        </div>
                    </div>
                    @empty
                    <p class="text-muted small text-center py-3">Sin empleados activos.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        {{-- Top salarios --}}
        <div class="col-12 col-lg-5">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 pb-0">
                    <h6 class="fw-semibold mb-0"><i class="bi bi-trophy-fill me-2 text-warning"></i>Top salarios (activos)</h6>
                </div>
                <div class="card-body p-0">
                    @php $maxSal = $topSalarios->max('salario_base') ?: 1; @endphp
                    <ul class="list-group list-group-flush">
                        @forelse($topSalarios as $i => $emp)
                        @php $pct = round($emp->salario_base / $maxSal * 100); @endphp
                        <li class="list-group-item px-3 py-2">
                            <div class="d-flex align-items-center justify-content-between mb-1">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge rounded-circle text-white"
                                          style="background:{{ ['#f59e0b','#94a3b8','#cd7f32','#6b7280','#6b7280'][$i] ?? '#6b7280' }};width:22px;height:22px;font-size:.7rem;display:flex;align-items:center;justify-content:center">
                                        {{ $i + 1 }}
                                    </span>
                                    <div>
                                        <div class="fw-semibold small">{{ $emp->user?->name ?? '—' }}</div>
                                        <div class="text-muted" style="font-size:.72rem">{{ $emp->cargo }}</div>
                                    </div>
                                </div>
                                <span class="small fw-bold text-success">RD${{ number_format($emp->salario_base, 0, ',', '.') }}</span>
                            </div>
                            <div class="progress" style="height:4px;border-radius:2px">
                                <div class="progress-bar bg-success" style="width:{{ $pct }}%"></div>
                            </div>
                        </li>
                        @empty
                        <li class="list-group-item text-muted text-center py-4 small">Sin empleados activos.</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>

        {{-- Últimos pagos --}}
        <div class="col-12 col-lg-7">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 pb-0 d-flex justify-content-between align-items-center">
                    <h6 class="fw-semibold mb-0"><i class="bi bi-clock-history me-2 text-secondary"></i>Últimos pagos registrados</h6>
                    <a href="{{ route('admin.nomina.index') }}" class="btn btn-link btn-sm p-0 text-muted">Ver todos</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-sm mb-0" style="font-size:.82rem">
                            <thead class="table-light">
                                <tr>
                                    <th>Empleado</th>
                                    <th>Mes</th>
                                    <th class="text-end">Neto</th>
                                    <th>Método</th>
                                    <th>Fecha pago</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($ultimosPagos as $pago)
                                <tr>
                                    <td>
                                        <a href="{{ route('admin.nomina.show', $pago->empleado->id ?? 0) }}"
                                           class="text-decoration-none fw-medium text-dark">
                                            {{ $pago->empleado?->user?->name ?? '—' }}
                                        </a>
                                        <div class="text-muted" style="font-size:.72rem">{{ $pago->empleado?->cargo ?? '' }}</div>
                                    </td>
                                    <td>{{ $pago->mes_formateado }}</td>
                                    <td class="text-end fw-bold text-success">
                                        RD${{ number_format($pago->salario_neto, 0, ',', '.') }}
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-secondary">
                                            {{ $pago->metodo_pago ?? '—' }}
                                        </span>
                                    </td>
                                    <td>{{ $pago->fecha_pago?->format('d/m/Y') ?? '—' }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">
                                        Sin pagos registrados aún.
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
            ['icon'=>'bi-cash-coin','color'=>'#10b981','bg'=>'#d1fae5','label'=>'Procesar nómina','href'=>route('admin.nomina.index').'?mes='.$mesAct],
            ['icon'=>'bi-person-plus-fill','color'=>'#3b82f6','bg'=>'#dbeafe','label'=>'Nuevo empleado','href'=>route('admin.nomina.create')],
            ['icon'=>'bi-file-earmark-excel-fill','color'=>'#10b981','bg'=>'#d1fae5','label'=>'Exportar Excel','href'=>route('admin.nomina.excel',['mes'=>$mesAct])],
            ['icon'=>'bi-calendar3','color'=>'#8b5cf6','bg'=>'#ede9fe','label'=>'Resumen anual','href'=>route('admin.nomina.resumen-anual')],
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
