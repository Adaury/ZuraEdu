@extends('layouts.admin')
@section('page-title', 'Nómina de Empleados')

@section('content')

@php
$mes = $filtroMes ?? now()->format('Y-m');
$mesesNombres = ['01'=>'Enero','02'=>'Febrero','03'=>'Marzo','04'=>'Abril','05'=>'Mayo','06'=>'Junio','07'=>'Julio','08'=>'Agosto','09'=>'Septiembre','10'=>'Octubre','11'=>'Noviembre','12'=>'Diciembre'];
[$anioMes, $numMes] = explode('-', $mes);
$mesLabel = ($mesesNombres[$numMes] ?? $numMes) . ' ' . $anioMes;
@endphp

{{-- Breadcrumb --}}
<x-breadcrumb :items="[
    ['label'=>'Dashboard','url'=>route('admin.dashboard')],
    ['label'=>'Nómina de Empleados'],
]"/>

{{-- Header --}}
<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-0"><i class="bi bi-cash-coin me-2" style="color:#0f766e;"></i>Nómina de Empleados</h4>
        <p class="text-muted small mb-0">{{ $totalEmpleados }} empleado(s) activo(s) &bull; Total mensual: <strong class="text-success">RD$ {{ number_format($totalNomina, 2) }}</strong></p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <form method="POST" action="{{ route('admin.nomina.procesar-mes') }}" class="d-inline" onsubmit="return confirm('¿Procesar nómina de {{ $mesLabel }}? Se crearán los registros para todos los empleados activos.')">
            @csrf
            <input type="hidden" name="mes" value="{{ $mes }}">
            <button type="submit" class="btn btn-outline-teal btn-sm" style="border-color:#0f766e;color:#0f766e;">
                <i class="bi bi-gear me-1"></i>Procesar mes
            </button>
        </form>
        <a href="{{ route('admin.nomina.excel', ['mes'=>$mes]) }}" class="btn btn-sm btn-outline-success">
            <i class="bi bi-file-earmark-excel me-1"></i>Excel
        </a>
        <a href="{{ route('admin.nomina.create') }}" class="btn btn-sm btn-primary">
            <i class="bi bi-plus-lg me-1"></i>Nuevo Empleado
        </a>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-3" style="border-radius:12px;">
    <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

{{-- Stats del mes --}}
<div class="row g-3 mb-4">
    @foreach([
        ['Total Bruto','RD$ '.number_format($statsMes['total_bruto'],2),'#0f766e','bi-cash-stack','bg-success'],
        ['Total Neto','RD$ '.number_format($statsMes['total_neto'],2),'#2563eb','bi-wallet2','bg-primary'],
        ['Deducciones','RD$ '.number_format($statsMes['total_deduc'],2),'#dc2626','bi-dash-circle','bg-danger'],
        ['Pagados',$statsMes['pagados'].' / '.$totalEmpleados,'#16a34a','bi-check-circle-fill','bg-success'],
    ] as [$lbl,$val,$clr,$icn,$badge])
    <div class="col-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100" style="border-radius:14px;border-left:4px solid {{ $clr }} !important;">
            <div class="card-body d-flex align-items-center gap-3 py-3">
                <div style="width:42px;height:42px;background:{{ $clr }}18;border-radius:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i class="bi {{ $icn }}" style="color:{{ $clr }};font-size:1.1rem;"></i>
                </div>
                <div>
                    <div class="fw-bold" style="font-size:1.05rem;color:{{ $clr }};">{{ $val }}</div>
                    <div class="text-muted small">{{ $lbl }} — {{ $mesLabel }}</div>
                </div>
            </div>
        </div>
    </div>
    @endforeach
</div>

{{-- Filtros --}}
<div class="card border-0 shadow-sm mb-3" style="border-radius:14px;">
<div class="card-body py-3">
    <form method="GET" action="{{ route('admin.nomina.index') }}" class="d-flex gap-3 align-items-end flex-wrap">
        <div class="flex-grow-1" style="min-width:200px;">
            <label class="form-label small fw-semibold mb-1">Buscar empleado</label>
            <input type="text" name="buscar" value="{{ $buscar }}" class="form-control form-control-sm" placeholder="Nombre o email...">
        </div>
        <div>
            <label class="form-label small fw-semibold mb-1">Mes</label>
            <input type="month" name="mes" value="{{ $mes }}" class="form-control form-control-sm">
        </div>
        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-sm btn-primary" style="border-radius:8px;">
                <i class="bi bi-search me-1"></i>Filtrar
            </button>
            @if($buscar)
            <a href="{{ route('admin.nomina.index', ['mes'=>$mes]) }}" class="btn btn-sm btn-outline-secondary" style="border-radius:8px;">✕</a>
            @endif
        </div>
    </form>
</div>
</div>

{{-- Tabla --}}
<div class="card border-0 shadow-sm" style="border-radius:16px;overflow:hidden;">
<div class="table-responsive">
<table class="table table-hover align-middle mb-0" style="font-size:.875rem;">
<thead style="background:#F8FAFC;">
    <tr>
        <th class="px-4 py-3 fw-semibold text-muted" style="font-size:.78rem;text-transform:uppercase;">#</th>
        <th class="py-3 fw-semibold text-muted" style="font-size:.78rem;text-transform:uppercase;">Empleado</th>
        <th class="py-3 fw-semibold text-muted" style="font-size:.78rem;text-transform:uppercase;">Cargo / Contrato</th>
        <th class="py-3 fw-semibold text-muted text-end" style="font-size:.78rem;text-transform:uppercase;">Salario Base</th>
        <th class="py-3 fw-semibold text-muted text-end" style="font-size:.78rem;text-transform:uppercase;">Deducciones</th>
        <th class="py-3 fw-semibold text-muted text-end" style="font-size:.78rem;text-transform:uppercase;">Neto {{ $mesLabel }}</th>
        <th class="py-3 fw-semibold text-muted text-center" style="font-size:.78rem;text-transform:uppercase;">Estado</th>
        <th class="py-3 fw-semibold text-muted text-center" style="font-size:.78rem;text-transform:uppercase;">Acciones</th>
    </tr>
</thead>
<tbody>
@forelse($empleados as $emp)
@php
    $pago = $emp->pagos->first();
    $tss  = $pago ? $pago->desc_tss  : $emp->calcularTSS();
    $isr  = $pago ? $pago->desc_isr  : $emp->calcularISR();
    $deducTotal = $pago ? $pago->deducciones : ($tss + $isr);
    $neto = $pago ? $pago->salario_neto : ($emp->salario_base - $deducTotal);
    $pagado = $pago && $pago->pagado;
@endphp
<tr>
    <td class="px-4 text-muted">{{ $empleados->firstItem() + $loop->index }}</td>

    {{-- Empleado --}}
    <td class="py-3">
        <div class="d-flex align-items-center gap-3">
            <div style="width:38px;height:38px;border-radius:50%;background:#0f766e18;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-weight:700;color:#0f766e;font-size:.85rem;">
                {{ strtoupper(substr($emp->user->name ?? '?', 0, 1)) }}
            </div>
            <div>
                <a href="{{ route('admin.nomina.show', $emp) }}" class="fw-semibold text-decoration-none text-dark" style="font-size:.9rem;">
                    {{ $emp->user->name ?? '—' }}
                </a>
                <div class="text-muted" style="font-size:.75rem;">{{ $emp->user->email ?? '' }}</div>
                @if($emp->cedula)
                <div class="text-muted" style="font-size:.72rem;font-family:monospace;">{{ $emp->cedula }}</div>
                @endif
            </div>
        </div>
    </td>

    {{-- Cargo --}}
    <td class="py-3">
        <div style="font-size:.875rem;">{{ $emp->cargo }}</div>
        @php $clrContrato = match($emp->tipo_contrato){'fijo'=>'#1d4ed8','temporal'=>'#d97706','hora'=>'#7c3aed',default=>'#6b7280'}; @endphp
        <span class="badge rounded-pill" style="background:{{ $clrContrato }}18;color:{{ $clrContrato }};font-size:.7rem;">
            {{ $emp->tipo_contrato_label }}
            @if($emp->tipo_contrato === 'hora' && $emp->horas_semana) · {{ $emp->horas_semana }}h/sem @endif
        </span>
    </td>

    {{-- Salario base --}}
    <td class="py-3 text-end fw-bold">RD$ {{ number_format($emp->salario_base, 2) }}</td>

    {{-- Deducciones --}}
    <td class="py-3 text-end">
        <div class="fw-semibold text-danger">-RD$ {{ number_format($deducTotal, 2) }}</div>
        <div class="text-muted" style="font-size:.72rem;">TSS: {{ number_format($tss,2) }} | ISR: {{ number_format($isr,2) }}</div>
    </td>

    {{-- Neto --}}
    <td class="py-3 text-end">
        <div class="fw-bold" style="color:#0f766e;font-size:.95rem;">RD$ {{ number_format($neto, 2) }}</div>
        @if($pago && ($pago->bonificacion > 0 || $pago->horas_extra > 0))
        <div class="text-success" style="font-size:.72rem;"><i class="bi bi-plus-circle me-1"></i>Extras incluidos</div>
        @endif
    </td>

    {{-- Estado --}}
    <td class="py-3 text-center">
        @if(!$emp->activo)
            <span class="badge bg-secondary">Inactivo</span>
        @elseif($pagado)
            <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Pagado</span>
            @if($pago->fecha_pago)
            <div class="text-muted" style="font-size:.7rem;">{{ $pago->fecha_pago->format('d/m/Y') }}</div>
            @endif
        @elseif($pago)
            <span class="badge bg-warning text-dark"><i class="bi bi-clock me-1"></i>Procesado</span>
        @else
            <span class="badge" style="background:#F1F5F9;color:#64748B;">Sin procesar</span>
        @endif
    </td>

    {{-- Acciones --}}
    <td class="py-3 text-center">
        <div class="d-flex align-items-center justify-content-center gap-1">

            {{-- Ver perfil --}}
            <a href="{{ route('admin.nomina.show', $emp) }}" class="btn btn-sm btn-outline-secondary" style="border-radius:7px;padding:.3rem .55rem;" title="Ver perfil"><i class="bi bi-eye"></i></a>

            {{-- Marcar pagado --}}
            @if(!$pagado && $emp->activo)
            <button class="btn btn-sm btn-outline-success" style="border-radius:7px;padding:.3rem .55rem;" title="Registrar pago"
                    data-bs-toggle="modal" data-bs-target="#modalPago{{ $emp->id }}">
                <i class="bi bi-check-lg"></i>
            </button>
            @endif

            {{-- PDF recibo --}}
            <a href="{{ route('admin.nomina.recibo-pdf', [$emp->id, 'mes'=>$mes]) }}" target="_blank"
               class="btn btn-sm btn-outline-danger" style="border-radius:7px;padding:.3rem .55rem;" title="Recibo PDF">
                <i class="bi bi-file-pdf"></i>
            </a>

            {{-- Editar --}}
            <a href="{{ route('admin.nomina.edit', $emp) }}" class="btn btn-sm btn-outline-primary" style="border-radius:7px;padding:.3rem .55rem;" title="Editar">
                <i class="bi bi-pencil"></i>
            </a>

            {{-- Eliminar --}}
            <form method="POST" action="{{ route('admin.nomina.destroy', $emp) }}"
                  onsubmit="return confirm('¿Eliminar a {{ addslashes($emp->user->name ?? '') }} de la nómina?')">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-sm btn-outline-danger" style="border-radius:7px;padding:.3rem .55rem;" title="Eliminar">
                    <i class="bi bi-trash"></i>
                </button>
            </form>
        </div>
    </td>
</tr>

{{-- Modal pago individual --}}
@if(!$pagado && $emp->activo)
<div class="modal fade" id="modalPago{{ $emp->id }}" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content border-0 shadow" style="border-radius:14px;">
            <div class="modal-header border-0" style="background:#0f766e;border-radius:14px 14px 0 0;">
                <h6 class="modal-title text-white fw-bold"><i class="bi bi-cash-coin me-2"></i>Registrar Pago</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('admin.nomina.marcar-pagado', $emp) }}">
                @csrf
                <input type="hidden" name="mes" value="{{ $mes }}">
                <div class="modal-body p-4">
                    <p class="small fw-semibold mb-1">{{ $emp->user->name }}</p>
                    <p class="text-muted small mb-3">Neto: <strong class="text-success">RD$ {{ number_format($neto, 2) }}</strong> — {{ $mesLabel }}</p>
                    <div class="mb-2">
                        <label class="form-label small fw-semibold">Método de pago</label>
                        <select name="metodo_pago" class="form-select form-select-sm">
                            <option value="">Sin especificar</option>
                            <option value="Transferencia bancaria">Transferencia bancaria</option>
                            <option value="Cheque">Cheque</option>
                            <option value="Efectivo">Efectivo</option>
                            <option value="Depósito">Depósito</option>
                        </select>
                    </div>
                    <div class="mb-0">
                        <label class="form-label small fw-semibold">Referencia (opcional)</label>
                        <input type="text" name="referencia_pago" class="form-control form-control-sm" placeholder="No. cheque, transferencia...">
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-sm btn-success" style="border-radius:8px;">
                        <i class="bi bi-check-lg me-1"></i>Confirmar Pago
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

@empty
<tr>
    <td colspan="8" class="text-center py-5 text-muted">
        <i class="bi bi-people" style="font-size:3rem;display:block;margin-bottom:.75rem;color:#CBD5E1;"></i>
        <p class="fw-semibold mb-1">No hay empleados en la nómina</p>
        <a href="{{ route('admin.nomina.create') }}" class="btn btn-primary btn-sm mt-2">
            <i class="bi bi-plus-lg me-1"></i>Registrar primer empleado
        </a>
    </td>
</tr>
@endforelse
</tbody>

@if($empleados->count())
<tfoot style="background:#F0FDF4;border-top:2px solid #86EFAC;">
    <tr>
        <td colspan="3" class="px-4 py-3 fw-bold text-end" style="color:#16a34a;">Total Nómina {{ $mesLabel }}:</td>
        <td class="py-3 text-end fw-bold text-dark">RD$ {{ number_format($totalNomina, 2) }}</td>
        <td class="py-3 text-end fw-bold text-danger">-RD$ {{ number_format($statsMes['total_deduc'], 2) }}</td>
        <td class="py-3 text-end fw-bold" style="color:#0f766e;font-size:1rem;">RD$ {{ number_format($statsMes['total_neto'], 2) }}</td>
        <td colspan="2" class="py-3 text-center">
            @if($statsMes['pendientes'] > 0)
            <form method="POST" action="{{ route('admin.nomina.marcar-todos-pagados') }}" onsubmit="return confirm('¿Marcar todos los pendientes como pagados?')">
                @csrf
                <input type="hidden" name="mes" value="{{ $mes }}">
                <button type="submit" class="btn btn-sm btn-success" style="border-radius:8px;font-size:.78rem;">
                    <i class="bi bi-check-all me-1"></i>Marcar todos pagados
                </button>
            </form>
            @else
            <span class="badge bg-success">Todos pagados</span>
            @endif
        </td>
    </tr>
</tfoot>
@endif
</table>
</div>

@if($empleados->hasPages())
<div class="px-4 py-3 border-top">
    {{ $empleados->links() }}
</div>
@endif
</div>

@endsection
