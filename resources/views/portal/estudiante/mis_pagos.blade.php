@extends('layouts.portal-estudiante')
@section('title', 'Mis Pagos')

@section('activeKey', 'mis-pagos')

@section('content')

{{-- Header --}}
<div class="mb-4 p-4" style="background:linear-gradient(135deg,#065f46,#10b981);border-radius:16px;position:relative;overflow:hidden;">
    <div style="position:absolute;top:-20px;right:-20px;width:120px;height:120px;background:rgba(255,255,255,.08);border-radius:50%;pointer-events:none;"></div>
    <div style="position:relative;z-index:1;">
        <h4 class="text-white fw-bold mb-1"><i class="bi bi-cash-coin me-2"></i>Mis Pagos</h4>
        <small class="text-white opacity-75">Estado de cuenta y registro de cuotas escolares</small>
    </div>
</div>

@if(!$matricula)
<div class="text-center py-5">
    <i class="bi bi-person-slash" style="font-size:3rem;color:#CBD5E1;display:block;margin-bottom:1rem;"></i>
    <p class="text-muted">No tienes una matrícula activa para el año escolar actual.</p>
</div>
@else

@php
    $totalGeneral   = $totales['pagado'] + $totales['pendiente'];
    $porcPagado     = $totalGeneral > 0 ? round(($totales['pagado'] / $totalGeneral) * 100) : 0;
    $proximoPago    = $pagos->whereIn('estado', ['pendiente','vencido'])->sortBy('fecha_vencimiento')->first();
    $diasAlProximo  = $proximoPago ? (int) now()->diffInDays($proximoPago->fecha_vencimiento, false) : null;
@endphp

{{-- Resumen chips --}}
<div class="row g-3 mb-4">
    <div class="col-4">
        <div class="card border-0 shadow-sm h-100" style="border-radius:12px;">
            <div class="card-body py-3 px-3 d-flex align-items-center gap-2">
                <div style="width:40px;height:40px;border-radius:10px;background:#d1fae5;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i class="bi bi-check-circle-fill" style="color:#10b981;font-size:1.1rem;"></i>
                </div>
                <div>
                    <div style="font-size:.68rem;font-weight:600;text-transform:uppercase;color:#6b7280;letter-spacing:.04em;">Pagado</div>
                    <div style="font-size:1rem;font-weight:800;color:#065f46;">RD$ {{ number_format($totales['pagado'],2) }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-4">
        <div class="card border-0 shadow-sm h-100" style="border-radius:12px;">
            <div class="card-body py-3 px-3 d-flex align-items-center gap-2">
                <div style="width:40px;height:40px;border-radius:10px;background:#fef3c7;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i class="bi bi-hourglass-split" style="color:#f59e0b;font-size:1.1rem;"></i>
                </div>
                <div>
                    <div style="font-size:.68rem;font-weight:600;text-transform:uppercase;color:#6b7280;letter-spacing:.04em;">Pendiente</div>
                    <div style="font-size:1rem;font-weight:800;color:#92400e;">RD$ {{ number_format($totales['pendiente'],2) }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-4">
        <div class="card border-0 shadow-sm h-100" style="border-radius:12px;">
            <div class="card-body py-3 px-3 d-flex align-items-center gap-2">
                <div style="width:40px;height:40px;border-radius:10px;background:#fee2e2;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i class="bi bi-exclamation-triangle-fill" style="color:#ef4444;font-size:1.1rem;"></i>
                </div>
                <div>
                    <div style="font-size:.68rem;font-weight:600;text-transform:uppercase;color:#6b7280;letter-spacing:.04em;">Vencido</div>
                    <div style="font-size:1rem;font-weight:800;color:#991b1b;">RD$ {{ number_format($totales['vencido'],2) }}</div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Barra de progreso --}}
@if($totalGeneral > 0)
<div class="card border-0 shadow-sm mb-4" style="border-radius:14px;">
    <div class="card-body py-3 px-4">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <span style="font-size:.83rem;font-weight:700;color:#374151;">Progreso de pagos del año</span>
            <span style="font-size:.95rem;font-weight:800;color:#065f46;">{{ $porcPagado }}%</span>
        </div>
        <div style="height:10px;background:#e5e7eb;border-radius:99px;overflow:hidden;">
            <div style="height:100%;width:{{ $porcPagado }}%;background:linear-gradient(90deg,#10b981,#059669);border-radius:99px;transition:width .6s ease;"></div>
        </div>
        <div class="d-flex justify-content-between mt-1" style="font-size:.72rem;color:#6b7280;">
            <span>RD$ {{ number_format($totales['pagado'],2) }} pagado</span>
            <span>Total: RD$ {{ number_format($totalGeneral,2) }}</span>
        </div>
    </div>
</div>
@endif

{{-- Próximo vencimiento --}}
@if($proximoPago)
@php
    $esVencidoProx  = $proximoPago->estado === 'vencido';
    $bgProx         = $esVencidoProx ? '#fef2f2' : '#eff6ff';
    $borderProx     = $esVencidoProx ? '#fca5a5' : '#bfdbfe';
    $iconProx       = $esVencidoProx ? 'bi-exclamation-triangle-fill' : 'bi-calendar-event-fill';
    $colorProx      = $esVencidoProx ? '#991b1b' : '#1d4ed8';
    $textoProx      = $esVencidoProx
        ? '¡Pago vencido hace ' . abs($diasAlProximo) . ' día(s)!'
        : ($diasAlProximo <= 0 ? '¡Vence hoy!' : 'Vence en ' . $diasAlProximo . ' día(s)');
@endphp
<div style="background:{{ $bgProx }};border:1px solid {{ $borderProx }};border-radius:12px;padding:1rem 1.25rem;margin-bottom:1.25rem;display:flex;align-items:center;gap:1rem;flex-wrap:wrap;">
    <i class="bi {{ $iconProx }}" style="color:{{ $colorProx }};font-size:1.35rem;flex-shrink:0;"></i>
    <div style="flex:1;">
        <div style="font-weight:700;color:{{ $colorProx }};font-size:.88rem;">{{ $textoProx }}</div>
        <div style="font-size:.8rem;color:#4b5563;margin-top:.15rem;">
            <strong>{{ $proximoPago->concepto }}</strong>
            &nbsp;—&nbsp;
            RD$ {{ number_format($proximoPago->monto,2) }}
            &nbsp;·&nbsp;
            Vence: {{ $proximoPago->fecha_vencimiento->format('d/m/Y') }}
        </div>
    </div>
    @php $cardnetActivo = \App\Services\CardNetService::isConfigured(); @endphp
    @if($cardnetActivo)
    <form method="POST" action="{{ route('portal.estudiante.mis-pagos.pagar-online', $proximoPago) }}">
        @csrf
        <button type="submit" class="btn btn-sm"
                style="background:{{ $esVencidoProx ? '#dc2626' : '#1d4ed8' }};color:#fff;border:none;border-radius:8px;font-size:.78rem;padding:.4rem 1rem;font-weight:700;white-space:nowrap;">
            <i class="bi bi-credit-card-2-front me-1"></i>Pagar ahora
        </button>
    </form>
    @endif
</div>
@endif

{{-- Alertas de pagos vencidos --}}
@php $vencidos = $pagos->where('estado','vencido'); @endphp
@if($vencidos->isNotEmpty())
<div class="alert py-3 mb-4" style="background:#fef2f2;border:1px solid #fca5a5;border-radius:12px;">
    <div class="d-flex align-items-start gap-2">
        <i class="bi bi-exclamation-triangle-fill" style="color:#dc2626;font-size:1.1rem;flex-shrink:0;margin-top:.1rem;"></i>
        <div>
            <div class="fw-bold" style="color:#991b1b;">Tienes {{ $vencidos->count() }} pago(s) vencido(s)</div>
            <div style="font-size:.82rem;color:#7f1d1d;">Por favor comunícate con la administración del centro para regularizar tu situación.</div>
        </div>
    </div>
</div>
@endif

{{-- Tabla de pagos --}}
<div class="card border-0 shadow-sm" style="border-radius:14px;">
    <div class="card-header bg-white border-bottom py-3 px-4" style="border-radius:14px 14px 0 0;">
        <h6 class="fw-bold mb-0">
            <i class="bi bi-list-ul me-2" style="color:#10b981;"></i>
            Historial de Pagos
            <small class="text-muted fw-normal">({{ $pagos->count() }} registros)</small>
        </h6>
    </div>

    @if($pagos->isEmpty())
    <div class="text-center py-5 text-muted">
        <i class="bi bi-receipt" style="font-size:2.5rem;color:#CBD5E1;display:block;margin-bottom:.5rem;"></i>
        <small>Sin registros de pagos para este año escolar.</small>
    </div>
    @else
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead style="background:#F8FAFC;">
                <tr>
                    <th class="px-4 py-3 text-muted fw-semibold" style="font-size:.72rem;text-transform:uppercase;">Concepto</th>
                    <th class="py-3 text-muted fw-semibold text-end" style="font-size:.72rem;text-transform:uppercase;">Monto</th>
                    <th class="py-3 text-muted fw-semibold text-center" style="font-size:.72rem;text-transform:uppercase;">Vence</th>
                    <th class="py-3 text-muted fw-semibold text-center" style="font-size:.72rem;text-transform:uppercase;">F. Pago</th>
                    <th class="py-3 text-muted fw-semibold text-center" style="font-size:.72rem;text-transform:uppercase;">Estado</th>
                    <th class="py-3 text-muted fw-semibold text-center" style="font-size:.72rem;text-transform:uppercase;">Método</th>
                    <th class="py-3"></th>
                </tr>
            </thead>
            <tbody>
            @php $cardnetActivo = \App\Services\CardNetService::isConfigured(); @endphp
            @foreach($pagos as $pago)
            @php
                $esPagado    = $pago->estado === 'pagado';
                $esVencido   = $pago->estado === 'vencido';
                $esPendiente = $pago->estado === 'pendiente';
                $puedeOnline = ($esPendiente || $esVencido) && $cardnetActivo;
            @endphp
            <tr class="{{ $esVencido ? 'table-danger' : '' }}">
                <td class="px-4 py-3">
                    <div class="fw-semibold" style="color:#1e293b;">{{ $pago->concepto }}</div>
                    @if($pago->notas)
                    <small class="text-muted fst-italic">{{ $pago->notas }}</small>
                    @endif
                </td>
                <td class="py-3 text-end fw-bold" style="color:#1e293b;white-space:nowrap;">
                    RD$ {{ number_format($pago->monto, 2) }}
                </td>
                <td class="py-3 text-center small text-muted">
                    {{ $pago->fecha_vencimiento?->format('d/m/Y') }}
                </td>
                <td class="py-3 text-center small text-muted">
                    {{ $pago->fecha_pago ? $pago->fecha_pago->format('d/m/Y') : '—' }}
                </td>
                <td class="py-3 text-center">
                    @if($esPagado)
                    <span class="badge" style="background:#d1fae5;color:#065f46;font-size:.72rem;">
                        <i class="bi bi-check-circle me-1"></i>Pagado
                    </span>
                    @elseif($esVencido)
                    <span class="badge" style="background:#fee2e2;color:#dc2626;font-size:.72rem;">
                        <i class="bi bi-exclamation-triangle me-1"></i>Vencido
                    </span>
                    @elseif($esPendiente)
                    <span class="badge" style="background:#fef3c7;color:#92400e;font-size:.72rem;">
                        <i class="bi bi-clock me-1"></i>Pendiente
                    </span>
                    @else
                    <span class="badge bg-secondary" style="font-size:.72rem;">{{ ucfirst($pago->estado) }}</span>
                    @endif
                </td>
                <td class="py-3 text-center small text-muted">
                    @if($pago->metodo_pago)
                        @php
                            $iconosMetodo = [
                                'efectivo'     => 'bi-cash-stack',
                                'transferencia'=> 'bi-bank',
                                'tarjeta'      => 'bi-credit-card',
                                'stripe'       => 'bi-stripe',
                                'cardnet'      => 'bi-credit-card-2-front',
                                'otro'         => 'bi-three-dots',
                            ];
                        @endphp
                        <i class="bi {{ $iconosMetodo[$pago->metodo_pago] ?? 'bi-dash' }} me-1"></i>
                        {{ ucfirst($pago->metodo_pago) }}
                    @else
                        —
                    @endif
                </td>
                <td class="py-3 text-center">
                    @if($esPagado)
                    <a href="{{ route('portal.estudiante.mis-pagos.recibo', $pago) }}" target="_blank"
                       class="btn btn-sm btn-outline-secondary py-1"
                       style="font-size:.72rem;border-radius:7px;" title="Descargar recibo">
                        <i class="bi bi-receipt me-1"></i>Recibo
                    </a>
                    @elseif($puedeOnline)
                    <form method="POST" action="{{ route('portal.estudiante.mis-pagos.pagar-online', $pago) }}">
                        @csrf
                        <button type="submit"
                                class="btn btn-sm"
                                style="background:linear-gradient(135deg,#1d4ed8,#2563eb);color:#fff;border:none;border-radius:8px;font-size:.72rem;padding:.3rem .75rem;white-space:nowrap;"
                                title="Pagar con tarjeta en línea">
                            <i class="bi bi-credit-card-2-front me-1"></i>Pagar
                        </button>
                    </form>
                    @endif
                </td>
            </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>

@endif

@endsection
