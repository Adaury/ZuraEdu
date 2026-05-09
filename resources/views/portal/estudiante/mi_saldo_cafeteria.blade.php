@extends('layouts.portal')
@section('title', 'Mi Saldo Cafetería')

@section('sidebar')
    @include('portal.estudiante._sidebar', ['activeKey' => 'cafeteria'])
@endsection

@section('content')

{{-- Header --}}
<div class="mb-4 p-4" style="background:linear-gradient(135deg,#7c3aed,#a78bfa);border-radius:16px;position:relative;overflow:hidden;">
    <div style="position:absolute;top:-20px;right:-20px;width:120px;height:120px;background:rgba(255,255,255,.08);border-radius:50%;pointer-events:none;"></div>
    <div style="position:relative;z-index:1;">
        <h4 class="text-white fw-bold mb-1"><i class="bi bi-cup-hot-fill me-2"></i>Mi Saldo Cafetería</h4>
        <small class="text-white opacity-75">Historial de recargas y consumos en la cafetería escolar</small>
    </div>
</div>

{{-- Chips de resumen --}}
<div class="row g-3 mb-4">
    <div class="col-4">
        <div class="card border-0 shadow-sm h-100" style="border-radius:12px;">
            <div class="card-body py-3 px-3 d-flex align-items-center gap-2">
                <div style="width:40px;height:40px;border-radius:10px;background:#ede9fe;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i class="bi bi-wallet2" style="color:#7c3aed;font-size:1.1rem;"></i>
                </div>
                <div>
                    <div style="font-size:.68rem;font-weight:600;text-transform:uppercase;color:#6b7280;letter-spacing:.04em;">Saldo Actual</div>
                    <div style="font-size:1rem;font-weight:800;color:{{ $saldo >= 0 ? '#7c3aed' : '#dc2626' }};">
                        RD$ {{ number_format($saldo, 2) }}
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-4">
        <div class="card border-0 shadow-sm h-100" style="border-radius:12px;">
            <div class="card-body py-3 px-3 d-flex align-items-center gap-2">
                <div style="width:40px;height:40px;border-radius:10px;background:#d1fae5;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i class="bi bi-arrow-up-circle-fill" style="color:#10b981;font-size:1.1rem;"></i>
                </div>
                <div>
                    <div style="font-size:.68rem;font-weight:600;text-transform:uppercase;color:#6b7280;letter-spacing:.04em;">Total Recargado</div>
                    <div style="font-size:1rem;font-weight:800;color:#065f46;">RD$ {{ number_format($totalRecargado, 2) }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-4">
        <div class="card border-0 shadow-sm h-100" style="border-radius:12px;">
            <div class="card-body py-3 px-3 d-flex align-items-center gap-2">
                <div style="width:40px;height:40px;border-radius:10px;background:#fee2e2;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i class="bi bi-bag-fill" style="color:#ef4444;font-size:1.1rem;"></i>
                </div>
                <div>
                    <div style="font-size:.68rem;font-weight:600;text-transform:uppercase;color:#6b7280;letter-spacing:.04em;">Total Gastado</div>
                    <div style="font-size:1rem;font-weight:800;color:#991b1b;">RD$ {{ number_format($totalGastado, 2) }}</div>
                </div>
            </div>
        </div>
    </div>
</div>

@if($saldo < 0)
<div class="alert py-3 mb-4" style="background:#fef2f2;border:1px solid #fca5a5;border-radius:12px;">
    <div class="d-flex align-items-start gap-2">
        <i class="bi bi-exclamation-triangle-fill" style="color:#dc2626;font-size:1.1rem;flex-shrink:0;margin-top:.1rem;"></i>
        <div>
            <div class="fw-bold" style="color:#991b1b;">Saldo insuficiente</div>
            <div style="font-size:.82rem;color:#7f1d1d;">Tu saldo es negativo. Por favor solicita una recarga a la administración.</div>
        </div>
    </div>
</div>
@endif

{{-- Historial --}}
<div class="card border-0 shadow-sm" style="border-radius:14px;">
    <div class="card-header bg-white border-bottom py-3 px-4" style="border-radius:14px 14px 0 0;">
        <h6 class="fw-bold mb-0">
            <i class="bi bi-clock-history me-2" style="color:#7c3aed;"></i>
            Historial de Movimientos
            <small class="text-muted fw-normal">({{ $historial->count() }} últimos)</small>
        </h6>
    </div>

    @if($historial->isEmpty())
    <div class="text-center py-5 text-muted">
        <i class="bi bi-cup" style="font-size:2.5rem;color:#CBD5E1;display:block;margin-bottom:.5rem;"></i>
        <small>Sin movimientos registrados en la cafetería.</small>
    </div>
    @else
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead style="background:#F8FAFC;">
                <tr>
                    <th class="px-4 py-3 text-muted fw-semibold" style="font-size:.72rem;text-transform:uppercase;">Fecha</th>
                    <th class="py-3 text-muted fw-semibold" style="font-size:.72rem;text-transform:uppercase;">Descripción</th>
                    <th class="py-3 text-muted fw-semibold text-end" style="font-size:.72rem;text-transform:uppercase;">Monto</th>
                    <th class="py-3 text-muted fw-semibold text-end" style="font-size:.72rem;text-transform:uppercase;">Saldo Nuevo</th>
                    <th class="py-3 text-muted fw-semibold text-center" style="font-size:.72rem;text-transform:uppercase;">Tipo</th>
                </tr>
            </thead>
            <tbody>
            @foreach($historial as $mov)
            <tr>
                <td class="px-4 py-3 text-muted small">{{ $mov->created_at->format('d/m/Y H:i') }}</td>
                <td class="py-3">
                    <span style="font-size:.88rem;">{{ $mov->descripcion ?? ($mov->tipo === 'recarga' ? 'Recarga de saldo' : 'Compra en cafetería') }}</span>
                    @if($mov->producto)
                    <small class="text-muted d-block">{{ $mov->producto->nombre }}</small>
                    @endif
                </td>
                <td class="py-3 text-end fw-bold" style="color:{{ $mov->tipo === 'recarga' ? '#065f46' : '#dc2626' }};">
                    {{ $mov->tipo === 'recarga' ? '+' : '-' }}RD$ {{ number_format($mov->monto, 2) }}
                </td>
                <td class="py-3 text-end small text-muted">RD$ {{ number_format($mov->saldo_nuevo, 2) }}</td>
                <td class="py-3 text-center">
                    @if($mov->tipo === 'recarga')
                    <span class="badge" style="background:#d1fae5;color:#065f46;font-size:.7rem;">
                        <i class="bi bi-arrow-up me-1"></i>Recarga
                    </span>
                    @else
                    <span class="badge" style="background:#fce7f3;color:#9d174d;font-size:.7rem;">
                        <i class="bi bi-bag me-1"></i>Compra
                    </span>
                    @endif
                </td>
            </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>

@endsection
