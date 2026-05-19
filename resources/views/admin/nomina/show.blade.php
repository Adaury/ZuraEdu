@extends('layouts.admin')
@section('page-title', 'Perfil — '.$nomina->user->name)

@push('styles')
<style>
[data-theme="dark"] .card { background:#1e293b !important; border-color:#334155 !important; }
[data-theme="dark"] .card-header { color:#e2e8f0 !important; }
[data-theme="dark"] .table-responsive table { color:#e2e8f0; }
[data-theme="dark"] thead { background:#1e3a8a !important; }
[data-theme="dark"] thead th { color:#93c5fd !important; }
[data-theme="dark"] .text-muted { color:#94a3b8 !important; }
[data-theme="dark"] hr { border-color:#334155; }
[data-theme="dark"] .rounded-3 { background:#0f172a !important; border-color:#334155 !important; }
[data-theme="dark"] details summary { color:#60a5fa !important; }
[data-theme="dark"] .form-control, [data-theme="dark"] .form-select { background:#1e293b; border-color:#334155; color:#e2e8f0; }
</style>
@endpush

@section('content')

@php
$mesPre = request('mes', now()->format('Y-m'));
@endphp

<x-breadcrumb :items="[
    ['label'=>'Dashboard','url'=>route('admin.dashboard')],
    ['label'=>'Nómina','url'=>route('admin.nomina.index')],
    ['label'=>$nomina->user->name],
]"/>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-3" style="border-radius:10px;">
    <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="d-flex align-items-center gap-3 mb-4 flex-wrap">
    <a href="{{ route('admin.nomina.index') }}" class="btn btn-outline-secondary btn-sm" style="border-radius:8px;">
        <i class="bi bi-arrow-left me-1"></i>Volver
    </a>
    <div class="flex-grow-1">
        <h5 class="fw-bold mb-0">{{ $nomina->user->name }}</h5>
        <small class="text-muted">{{ $nomina->cargo }} &bull; {{ $nomina->tipo_contrato_label }}</small>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.nomina.recibo-pdf', [$nomina->id, 'mes'=>$mesPre]) }}" target="_blank"
           class="btn btn-sm btn-outline-danger" style="border-radius:8px;">
            <i class="bi bi-file-pdf me-1"></i>Recibo PDF
        </a>
        <a href="{{ route('admin.nomina.edit', $nomina) }}" class="btn btn-sm btn-outline-primary" style="border-radius:8px;">
            <i class="bi bi-pencil me-1"></i>Editar
        </a>
    </div>
</div>

<div class="row g-4">

{{-- Perfil del empleado --}}
<div class="col-lg-4">
    <div class="card border-0 shadow-sm" style="border-radius:16px;position:sticky;top:80px;">
    <div class="card-body p-4">
        {{-- Avatar --}}
        <div class="text-center mb-4">
            <div style="width:72px;height:72px;background:#0f766e;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1.8rem;font-weight:800;color:#fff;margin:0 auto 1rem;">
                {{ strtoupper(substr($nomina->user->name, 0, 1)) }}
            </div>
            <h5 class="fw-bold mb-1">{{ $nomina->user->name }}</h5>
            <div class="text-muted small">{{ $nomina->user->email }}</div>
            @if($nomina->activo)
            <span class="badge bg-success mt-2">Activo</span>
            @else
            <span class="badge bg-secondary mt-2">Inactivo</span>
            @endif
        </div>

        <hr>

        <div class="d-flex flex-column gap-2 small">
            <div class="d-flex justify-content-between">
                <span class="text-muted"><i class="bi bi-briefcase me-1"></i>Cargo</span>
                <span class="fw-semibold">{{ $nomina->cargo }}</span>
            </div>
            <div class="d-flex justify-content-between">
                <span class="text-muted"><i class="bi bi-file-earmark-text me-1"></i>Contrato</span>
                <span class="fw-semibold">{{ $nomina->tipo_contrato_label }}</span>
            </div>
            <div class="d-flex justify-content-between">
                <span class="text-muted"><i class="bi bi-calendar3 me-1"></i>Ingreso</span>
                <span class="fw-semibold">{{ $nomina->fecha_ingreso?->format('d/m/Y') }}</span>
            </div>
            <div class="d-flex justify-content-between">
                <span class="text-muted"><i class="bi bi-clock-history me-1"></i>Antigüedad</span>
                <span class="fw-semibold">{{ $nomina->antiguedad }}</span>
            </div>
            @if($nomina->cedula)
            <div class="d-flex justify-content-between">
                <span class="text-muted"><i class="bi bi-card-text me-1"></i>Cédula</span>
                <span class="fw-semibold" style="font-family:monospace;">{{ $nomina->cedula }}</span>
            </div>
            @endif
            @if($nomina->banco)
            <div class="d-flex justify-content-between">
                <span class="text-muted"><i class="bi bi-bank me-1"></i>Banco</span>
                <span class="fw-semibold">{{ $nomina->banco }}</span>
            </div>
            @endif
            @if($nomina->cuenta_bancaria)
            <div class="d-flex justify-content-between">
                <span class="text-muted"><i class="bi bi-credit-card me-1"></i>Cuenta</span>
                <span class="fw-semibold" style="font-family:monospace;">{{ $nomina->cuenta_bancaria }}</span>
            </div>
            @endif
        </div>

        <hr>

        {{-- Resumen salarial --}}
        <div class="text-center">
            <div class="text-muted small mb-1">Salario Base</div>
            <div style="font-size:1.4rem;font-weight:800;color:#0f766e;">RD$ {{ number_format($nomina->salario_base, 2) }}</div>
            <div class="text-muted small mt-1">
                TSS: {{ $nomina->tss_porcentaje }}%
                @if($nomina->exento_isr) &bull; Exento ISR @endif
            </div>
            <div style="font-size:.85rem;color:#dc2626;margin-top:4px;">
                -RD$ {{ number_format($nomina->calcularTSS() + $nomina->calcularISR(), 2) }} en deducciones
            </div>
            <div style="font-size:1rem;font-weight:700;color:#2563eb;margin-top:4px;">
                Neto: RD$ {{ number_format($nomina->salario_base - $nomina->calcularTSS() - $nomina->calcularISR(), 2) }}
            </div>
        </div>
    </div>
    </div>
</div>

{{-- Historial + pago del mes --}}
<div class="col-lg-8">

    {{-- Pago del mes actual --}}
    @php
    $pagoMes = $historial->firstWhere('mes', $mesPre);
    @endphp
    <div class="card border-0 shadow-sm mb-4" style="border-radius:16px;">
    <div class="card-header border-0 py-3 px-4 d-flex align-items-center justify-content-between"
         style="background:linear-gradient(135deg,#0f766e,#14b8a6);border-radius:16px 16px 0 0;color:#fff;">
        <div>
            <h6 class="mb-0 fw-bold"><i class="bi bi-cash-coin me-2"></i>Detalle del Mes</h6>
        </div>
        <input type="month" id="mesSelector" value="{{ $mesPre }}" class="form-control form-control-sm"
               style="width:150px;background:rgba(255,255,255,.2);border:1px solid rgba(255,255,255,.3);color:#fff;"
               onchange="window.location.href='{{ route('admin.nomina.show', $nomina) }}?mes='+this.value">
    </div>
    <div class="card-body p-4">
        @if($pagoMes)
        <div class="row g-3 mb-3">
            @foreach([
                ['Salario Bruto','RD$ '.number_format($pagoMes->salario_bruto,2),'#0f766e'],
                ['Horas Extra','RD$ '.number_format($pagoMes->horas_extra,2),'#2563eb'],
                ['Bonificación','RD$ '.number_format($pagoMes->bonificacion,2),'#7c3aed'],
                ['TSS','-RD$ '.number_format($pagoMes->desc_tss,2),'#dc2626'],
                ['ISR','-RD$ '.number_format($pagoMes->desc_isr,2),'#dc2626'],
                ['Otras Deduc.','-RD$ '.number_format($pagoMes->desc_otros,2),'#dc2626'],
            ] as [$lbl,$val,$clr])
            <div class="col-4">
                <div class="p-2 rounded-3 text-center" style="background:{{ $clr }}10;border:1px solid {{ $clr }}20;">
                    <div class="text-muted" style="font-size:.72rem;">{{ $lbl }}</div>
                    <div class="fw-bold" style="color:{{ $clr }};font-size:.9rem;">{{ $val }}</div>
                </div>
            </div>
            @endforeach
        </div>

        <div class="d-flex align-items-center justify-content-between p-3 rounded-3 mb-3"
             style="background:#F0FDF4;border:2px solid #86EFAC;">
            <div>
                <div class="text-muted small">Salario Neto a Pagar</div>
                <div style="font-size:1.5rem;font-weight:900;color:#0f766e;">RD$ {{ number_format($pagoMes->salario_neto,2) }}</div>
            </div>
            @if($pagoMes->pagado)
            <div class="text-end">
                <span class="badge bg-success fs-6 px-3 py-2"><i class="bi bi-check-circle me-1"></i>PAGADO</span>
                <div class="text-muted small mt-1">{{ $pagoMes->fecha_pago?->format('d/m/Y') }}</div>
                @if($pagoMes->metodo_pago)<div class="text-muted small">{{ $pagoMes->metodo_pago }}</div>@endif
            </div>
            @else
            <form method="POST" action="{{ route('admin.nomina.marcar-pagado', $nomina) }}">
                @csrf
                <input type="hidden" name="mes" value="{{ $mesPre }}">
                <button type="submit" class="btn btn-success fw-bold px-4" style="border-radius:10px;">
                    <i class="bi bi-check-lg me-1"></i>Registrar Pago
                </button>
            </form>
            @endif
        </div>

        {{-- Editar pago --}}
        <details class="mt-2">
            <summary class="text-primary" style="cursor:pointer;font-size:.85rem;font-weight:600;">
                <i class="bi bi-pencil me-1"></i>Editar deducciones / bonificaciones
            </summary>
            <form method="POST" action="{{ route('admin.nomina.guardar-pago', $nomina) }}" class="mt-3">
                @csrf
                <input type="hidden" name="mes" value="{{ $mesPre }}">
                <div class="row g-2">
                    @foreach([
                        ['salario_bruto','Salario Bruto','RD$ 0','number',$pagoMes->salario_bruto],
                        ['horas_extra','Horas Extra','0.00','number',$pagoMes->horas_extra],
                        ['bonificacion','Bonificación','0.00','number',$pagoMes->bonificacion],
                        ['otros_ingresos','Otros Ingresos','0.00','number',$pagoMes->otros_ingresos],
                        ['desc_tss','Desc. TSS','0.00','number',$pagoMes->desc_tss],
                        ['desc_isr','Desc. ISR','0.00','number',$pagoMes->desc_isr],
                        ['desc_otros','Otras Deduc.','0.00','number',$pagoMes->desc_otros],
                    ] as [$nm,$lbl,$ph,$tp,$val])
                    <div class="col-6 col-md-3">
                        <label class="form-label small">{{ $lbl }}</label>
                        <input type="{{ $tp }}" name="{{ $nm }}" class="form-control form-control-sm" value="{{ $val }}" step="0.01" min="0">
                    </div>
                    @endforeach
                    <div class="col-12">
                        <label class="form-label small">Notas de deducciones</label>
                        <input type="text" name="notas_deducciones" class="form-control form-control-sm"
                               value="{{ $pagoMes->notas_deducciones }}" placeholder="Ej: Descuento por préstamo...">
                    </div>
                </div>
                <button type="submit" class="btn btn-sm btn-primary mt-2" style="border-radius:8px;">
                    <i class="bi bi-save me-1"></i>Guardar cambios
                </button>
            </form>
        </details>

        @else
        <div class="text-center py-4 text-muted">
            <i class="bi bi-inbox" style="font-size:2rem;display:block;margin-bottom:.75rem;color:#CBD5E1;"></i>
            <p class="mb-2">No hay pago registrado para este mes</p>
            <div class="d-flex gap-2 justify-content-center flex-wrap">
                <form method="POST" action="{{ route('admin.nomina.procesar-solo', $nomina) }}" class="d-inline">
                    @csrf
                    <input type="hidden" name="mes" value="{{ $mesPre }}">
                    <button type="submit" class="btn btn-sm btn-outline-primary" style="border-radius:8px;">
                        <i class="bi bi-person-check me-1"></i>Generar solo para este empleado
                    </button>
                </form>
                <form method="POST" action="{{ route('admin.nomina.procesar-mes') }}" class="d-inline"
                      onsubmit="return confirm('¿Procesar nómina del mes para TODOS los empleados activos?')">
                    @csrf
                    <input type="hidden" name="mes" value="{{ $mesPre }}">
                    <button type="submit" class="btn btn-sm btn-outline-secondary" style="border-radius:8px;">
                        <i class="bi bi-people me-1"></i>Procesar todo el mes
                    </button>
                </form>
            </div>
        </div>
        @endif
    </div>
    </div>

    {{-- Historial --}}
    <div class="card border-0 shadow-sm" style="border-radius:16px;">
    <div class="card-body p-0">
        <div class="px-4 py-3 border-bottom">
            <h6 class="fw-bold mb-0">Historial de Pagos</h6>
        </div>
        <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" style="font-size:.875rem;">
        <thead style="background:#F8FAFC;">
            <tr>
                <th class="px-4 py-3 fw-semibold text-muted" style="font-size:.78rem;">Mes</th>
                <th class="py-3 fw-semibold text-muted text-end" style="font-size:.78rem;">Bruto</th>
                <th class="py-3 fw-semibold text-muted text-end" style="font-size:.78rem;">Deducciones</th>
                <th class="py-3 fw-semibold text-muted text-end" style="font-size:.78rem;">Neto</th>
                <th class="py-3 fw-semibold text-muted text-center" style="font-size:.78rem;">Estado</th>
                <th class="py-3 fw-semibold text-muted" style="font-size:.78rem;">Acciones</th>
            </tr>
        </thead>
        <tbody>
        @forelse($historial as $pago)
        <tr>
            <td class="px-4 py-2 fw-semibold">{{ $pago->mes_formateado }}</td>
            <td class="py-2 text-end">RD$ {{ number_format($pago->salario_bruto,2) }}</td>
            <td class="py-2 text-end text-danger">-RD$ {{ number_format($pago->deducciones,2) }}</td>
            <td class="py-2 text-end fw-bold" style="color:#0f766e;">RD$ {{ number_format($pago->salario_neto,2) }}</td>
            <td class="py-2 text-center">
                @if($pago->pagado)
                <span class="badge bg-success">Pagado</span>
                @if($pago->fecha_pago)<div class="text-muted" style="font-size:.7rem;">{{ $pago->fecha_pago->format('d/m/Y') }}</div>@endif
                @else
                <span class="badge bg-warning text-dark">Pendiente</span>
                @endif
            </td>
            <td class="py-2">
                <a href="{{ route('admin.nomina.recibo-pdf', [$nomina->id, 'mes'=>$pago->mes]) }}" target="_blank"
                   class="btn btn-sm btn-outline-danger" style="border-radius:6px;padding:.25rem .5rem;font-size:.75rem;">
                    <i class="bi bi-file-pdf"></i>
                </a>
            </td>
        </tr>
        @empty
        <tr><td colspan="6" class="text-center py-4 text-muted small">Sin historial de pagos</td></tr>
        @endforelse
        </tbody>
        </table>
        </div>
    </div>
    </div>

</div>
</div>

@endsection
