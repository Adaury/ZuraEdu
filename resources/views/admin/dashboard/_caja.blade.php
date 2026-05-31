{{-- ══ DASHBOARD CAJA / FINANZAS ══ --}}
@php $mon = \App\Helpers\Setting::get('payments_currency', 'RD$'); @endphp

{{-- KPIs financieros --}}
<div class="row g-4 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card" style="--c:#10b981;">
            <div class="stat-icon"><i class="bi bi-cash-coin"></i></div>
            <div class="stat-body">
                <div class="stat-num">{{ $mon }} {{ number_format($statsCaja['cobrado'] ?? 0, 0) }}</div>
                <div class="stat-label">Cobrado este año</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card" style="--c:#f59e0b;">
            <div class="stat-icon"><i class="bi bi-hourglass-split"></i></div>
            <div class="stat-body">
                <div class="stat-num">{{ $mon }} {{ number_format($statsCaja['pendiente'] ?? 0, 0) }}</div>
                <div class="stat-label">Pendiente de cobro</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card" style="--c:#ef4444;">
            <div class="stat-icon"><i class="bi bi-exclamation-triangle-fill"></i></div>
            <div class="stat-body">
                <div class="stat-num">{{ $mon }} {{ number_format($statsCaja['vencido'] ?? 0, 0) }}</div>
                <div class="stat-label">Vencido sin pagar</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <a href="{{ route('admin.pagos.deudores') }}" class="text-decoration-none d-block">
            <div class="stat-card" style="--c:#8b5cf6;cursor:pointer;">
                <div class="stat-icon"><i class="bi bi-people-fill"></i></div>
                <div class="stat-body">
                    <div class="stat-num">{{ $statsCaja['deudores'] ?? 0 }}</div>
                    <div class="stat-label">Estudiantes deudores</div>
                </div>
                <div style="position:absolute;bottom:14px;right:16px;font-size:.7rem;color:rgba(255,255,255,.55);font-weight:600;">
                    VER LISTA <i class="bi bi-arrow-right"></i>
                </div>
            </div>
        </a>
    </div>
</div>

<div class="row g-4">
    {{-- Últimos pagos --}}
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm" style="border-radius:14px;">
            <div class="card-header bg-white border-0 py-3 px-4 d-flex align-items-center justify-content-between">
                <h6 class="fw-bold mb-0" style="color:#10b981;">
                    <i class="bi bi-receipt me-2"></i>Últimos Pagos Recibidos
                </h6>
                <a href="{{ route('admin.pagos.index') }}" class="btn btn-sm btn-outline-success" style="border-radius:8px;font-size:.78rem;">
                    Ver todos
                </a>
            </div>
            <div class="card-body p-0">
                @if(($statsCaja['ultimos_pagos'] ?? collect())->isEmpty())
                <div class="text-center py-4 text-muted" style="font-size:.85rem;">Sin pagos registrados.</div>
                @else
                <div class="table-responsive">
                    <table class="table table-hover mb-0" style="font-size:.83rem;">
                        <thead style="background:#f8faff;">
                            <tr>
                                <th class="px-4 py-2 text-muted fw-semibold" style="font-size:.72rem;">Estudiante</th>
                                <th class="py-2 text-muted fw-semibold" style="font-size:.72rem;">Concepto</th>
                                <th class="py-2 text-end text-muted fw-semibold pe-4" style="font-size:.72rem;">Monto</th>
                                <th class="py-2 text-muted fw-semibold" style="font-size:.72rem;">Fecha</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($statsCaja['ultimos_pagos'] as $pago)
                            <tr>
                                <td class="px-4 py-2">{{ $pago->matricula?->estudiante?->nombre_completo ?? '—' }}</td>
                                <td class="py-2 text-muted" style="font-size:.8rem;">{{ Str::limit($pago->concepto, 28) }}</td>
                                <td class="py-2 text-end pe-4 fw-bold" style="color:#10b981;">{{ $mon }} {{ number_format($pago->monto, 2) }}</td>
                                <td class="py-2 text-muted" style="font-size:.78rem;">{{ $pago->fecha_pago?->format('d/m/Y') ?? '—' }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Top deudores --}}
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm" style="border-radius:14px;">
            <div class="card-header bg-white border-0 py-3 px-4 d-flex align-items-center justify-content-between">
                <h6 class="fw-bold mb-0" style="color:#ef4444;">
                    <i class="bi bi-exclamation-triangle me-2"></i>Deudores Prioritarios
                </h6>
                <a href="{{ route('admin.pagos.deudores') }}" class="btn btn-sm btn-outline-danger" style="border-radius:8px;font-size:.78rem;">
                    Ver todos
                </a>
            </div>
            <div class="card-body p-0">
                @forelse($statsCaja['top_deudores'] ?? [] as $deuda)
                <div class="d-flex align-items-center gap-3 px-4 py-2" style="border-bottom:1px solid #f1f5f9;">
                    <div style="width:34px;height:34px;border-radius:50%;background:#fee2e2;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i class="bi bi-person-fill" style="color:#ef4444;font-size:.9rem;"></i>
                    </div>
                    <div style="flex:1;min-width:0;">
                        <div class="fw-semibold text-truncate" style="font-size:.82rem;">
                            {{ $deuda->matricula?->estudiante?->nombre_completo ?? '—' }}
                        </div>
                        <div style="font-size:.72rem;color:#94a3b8;">{{ $deuda->concepto }}</div>
                    </div>
                    <div class="fw-bold" style="color:#ef4444;font-size:.82rem;white-space:nowrap;">
                        {{ $mon }} {{ number_format($deuda->monto, 2) }}
                    </div>
                </div>
                @empty
                <div class="text-center py-4 text-muted" style="font-size:.85rem;">Sin deudores vencidos.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
