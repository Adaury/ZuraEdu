@extends('layouts.admin')
@section('page-title', 'Facturación y Suscripción')

@push('styles')
<style>
.plan-card {
    border-radius: 16px;
    border: 2px solid #e2e8f0;
    background: #fff;
    transition: border-color .2s, box-shadow .2s, transform .2s;
    position: relative;
    overflow: hidden;
}
.plan-card:hover {
    border-color: #6366f1;
    box-shadow: 0 8px 32px rgba(99,102,241,.12);
    transform: translateY(-3px);
}
.plan-card.popular {
    border-color: #6366f1;
    box-shadow: 0 4px 24px rgba(99,102,241,.15);
}
.plan-card.actual {
    border-color: #22c55e;
    box-shadow: 0 4px 24px rgba(34,197,94,.15);
}
.plan-precio {
    font-size: 2.4rem;
    font-weight: 900;
    line-height: 1;
    color: #0f172a;
}
.plan-badge-popular {
    position: absolute;
    top: 1rem; right: -1.8rem;
    background: #6366f1;
    color: #fff;
    font-size: .65rem;
    font-weight: 700;
    padding: .25rem 2rem;
    transform: rotate(45deg);
    letter-spacing: .05em;
}
.feature-check { color: #22c55e; }
.feature-x     { color: #cbd5e1; }
.billing-stat {
    border-radius: 12px;
    padding: 1rem 1.25rem;
}
</style>
@endpush

@section('content')

{{-- Header --}}
<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-0" style="color:#0f172a;">
            <i class="bi bi-credit-card-2-front-fill me-2" style="color:#6366f1;"></i>Facturación y Suscripción
        </h4>
        <p class="text-muted mb-0" style="font-size:.85rem;">{{ $tenant->nombre_institucion }}</p>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2" role="alert">
    <i class="bi bi-check-circle-fill fs-5"></i>
    <span>{{ session('success') }}</span>
    <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
</div>
@endif
@if(session('warning'))
<div class="alert alert-warning alert-dismissible fade show d-flex align-items-center gap-2" role="alert">
    <i class="bi bi-exclamation-triangle-fill fs-5"></i>
    <span>{{ session('warning') }}</span>
    <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
</div>
@endif
@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show d-flex align-items-center gap-2" role="alert">
    <i class="bi bi-x-circle-fill fs-5"></i>
    <span>{{ session('error') }}</span>
    <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
</div>
@endif

{{-- ── Suscripción actual ──────────────────────────────────────────────── --}}
<div class="row g-3 mb-4">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm" style="border-radius:16px;">
            <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
                    <h6 class="fw-bold mb-0"><i class="bi bi-shield-check me-2 text-success"></i>Suscripción actual</h6>
                    @if($suscActiva)
                    <span class="badge bg-success">Activa</span>
                    @elseif($tenant->estaVencido())
                    <span class="badge bg-danger">Vencida</span>
                    @else
                    <span class="badge bg-warning text-dark">En prueba</span>
                    @endif
                </div>

                @if($suscActiva)
                @php
                    $diasRest = $suscActiva->diasRestantes();
                    $total    = $suscActiva->fecha_inicio->diffInDays($suscActiva->fecha_fin) ?: 1;
                    $usado    = $total - $diasRest;
                    $pct      = min(100, round($usado / $total * 100));
                    $barColor = $diasRest <= 3 ? '#ef4444' : ($diasRest <= 10 ? '#f59e0b' : '#22c55e');
                @endphp
                <div class="row g-3 mb-3">
                    <div class="col-sm-4">
                        <div class="billing-stat" style="background:#f0fdf4;">
                            <div class="text-muted mb-1" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.05em;font-weight:600;">Plan</div>
                            <div class="fw-bold" style="font-size:1.1rem;color:#15803d;">{{ strtoupper($tenant->label_plan) }}</div>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="billing-stat" style="background:#eff6ff;">
                            <div class="text-muted mb-1" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.05em;font-weight:600;">Vence</div>
                            <div class="fw-bold" style="font-size:1.1rem;color:#1d4ed8;">{{ $suscActiva->fecha_fin->format('d M Y') }}</div>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="billing-stat" style="background:#fefce8;">
                            <div class="text-muted mb-1" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.05em;font-weight:600;">Días restantes</div>
                            <div class="fw-bold" style="font-size:1.1rem;color:#92400e;">{{ $diasRest }} días</div>
                        </div>
                    </div>
                </div>
                <div>
                    <div class="d-flex justify-content-between mb-1" style="font-size:.76rem;color:#64748b;">
                        <span>{{ $suscActiva->fecha_inicio->format('d/m/Y') }}</span>
                        <span>{{ $suscActiva->fecha_fin->format('d/m/Y') }}</span>
                    </div>
                    <div class="progress" style="height:8px;border-radius:4px;">
                        <div class="progress-bar" style="width:{{ $pct }}%;background:{{ $barColor }};border-radius:4px;transition:width .5s;"></div>
                    </div>
                </div>
                @else
                <div class="alert alert-warning mb-0 py-2" style="font-size:.85rem;">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    @if($tenant->estaVencido())
                        Tu suscripción venció el <strong>{{ $tenant->fecha_vencimiento?->format('d/m/Y') }}</strong>.
                        Renueva para continuar usando el sistema.
                    @else
                        Estás en el período de prueba gratuito. Elige un plan para continuar después del período.
                    @endif
                </div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100" style="border-radius:16px;">
            <div class="card-body p-4 d-flex flex-column justify-content-center">
                <p class="fw-semibold mb-1" style="font-size:.85rem;color:#0f172a;">
                    <i class="bi bi-bank me-2 text-primary"></i>Datos bancarios para transferencia
                </p>
                <div style="font-size:.8rem;color:#64748b;line-height:1.9;">
                    <div><strong>Banco:</strong> Banco Popular Dominicano</div>
                    <div><strong>Cuenta:</strong> 123-456789-0</div>
                    <div><strong>Titular:</strong> ZuraEdu SRL</div>
                    <div><strong>RNC:</strong> 1-01-12345-6</div>
                </div>
                <button class="btn btn-outline-primary btn-sm mt-3 align-self-start"
                    style="font-size:.75rem;border-radius:8px;"
                    data-bs-toggle="modal" data-bs-target="#modalTransferencia">
                    <i class="bi bi-send-check me-1"></i>Registrar mi transferencia
                </button>
            </div>
        </div>
    </div>
</div>

{{-- ── Selector de ciclo ───────────────────────────────────────────────── --}}
<div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
    <h5 class="fw-bold mb-0" style="color:#0f172a;">Elige tu plan</h5>
    <div class="d-flex align-items-center gap-2">
        <span style="font-size:.82rem;color:#64748b;">Mensual</span>
        <div class="form-check form-switch mb-0">
            <input class="form-check-input" type="checkbox" id="cicloSwitch"
                style="width:2.5rem;height:1.25rem;cursor:pointer;">
        </div>
        <span style="font-size:.82rem;color:#64748b;">
            Anual <span class="badge bg-success" style="font-size:.65rem;">-2 meses</span>
        </span>
    </div>
</div>

{{-- ── Cards de planes ─────────────────────────────────────────────────── --}}
<div class="row g-4 mb-4">
    @foreach($planes as $plan)
    @php
        $esPlanActual = $tenant->plan === $plan->slug;
        $precio       = $plan->precio_mensual;
        $precioAnual  = $plan->precio_anual;
        $colores = [
            'free'   => ['bg'=>'#f8fafc','btn'=>'btn-outline-secondary','icon'=>'#94a3b8'],
            'basico' => ['bg'=>'#f0f4ff','btn'=>'btn-primary','icon'=>'#6366f1'],
            'pro'    => ['bg'=>'#fdf4ff','btn'=>'btn-purple','icon'=>'#7c3aed'],
        ];
        $c = $colores[$plan->slug] ?? $colores['free'];
    @endphp
    <div class="col-md-4">
        <div class="plan-card {{ $plan->es_popular ? 'popular' : '' }} {{ $esPlanActual ? 'actual' : '' }} p-4 h-100">
            @if($plan->es_popular)
            <div class="plan-badge-popular">POPULAR</div>
            @endif
            @if($esPlanActual)
            <span class="badge bg-success mb-2" style="font-size:.65rem;">
                <i class="bi bi-check-circle me-1"></i>Plan actual
            </span>
            @endif

            <div class="mb-3">
                <div class="fw-bold fs-5 mb-1" style="color:#0f172a;">{{ strtoupper($plan->nombre) }}</div>
                <p class="text-muted mb-0" style="font-size:.8rem;">{{ $plan->descripcion }}</p>
            </div>

            <div class="mb-3">
                @if($plan->esPago())
                    <div class="plan-precio" id="precio-{{ $plan->slug }}">
                        ${{ number_format($precio, 0) }}
                        <span style="font-size:1rem;font-weight:400;color:#64748b;">/mes</span>
                    </div>
                    <div class="text-muted" style="font-size:.75rem;" id="precio-anual-{{ $plan->slug }}">
                        o ${{ number_format($precioAnual, 0) }}/año
                    </div>
                @else
                    <div class="plan-precio">$0</div>
                    <div class="text-muted" style="font-size:.75rem;">Para siempre</div>
                @endif
            </div>

            <ul class="list-unstyled mb-4" style="font-size:.82rem;">
                @if($plan->caracteristicas)
                    @foreach($plan->caracteristicas as $feat)
                    <li class="d-flex align-items-start gap-2 mb-1">
                        <i class="bi bi-check-circle-fill feature-check mt-1 flex-shrink-0"></i>
                        <span>{{ $feat }}</span>
                    </li>
                    @endforeach
                @endif
            </ul>

            @if($esPlanActual)
                <button class="btn btn-outline-success w-100" disabled style="border-radius:10px;">
                    <i class="bi bi-check-lg me-1"></i>Plan actual
                </button>
            @elseif($plan->esPago())
                <div class="d-grid gap-2">
                    @if($stripeActivo)
                    <form method="POST" action="{{ route('admin.billing.checkout') }}" class="checkout-form">
                        @csrf
                        <input type="hidden" name="plan_slug" value="{{ $plan->slug }}">
                        <input type="hidden" name="ciclo" class="ciclo-input" value="mensual">
                        <button type="submit" class="btn btn-primary w-100" style="border-radius:10px;">
                            <i class="bi bi-credit-card me-1"></i>Pagar con tarjeta
                        </button>
                    </form>
                    @endif
                    <button class="btn btn-outline-secondary w-100" style="border-radius:10px;"
                        onclick="abrirTransferencia('{{ $plan->slug }}', '{{ $plan->nombre }}', {{ $plan->precio_mensual }}, {{ $plan->precio_anual }})">
                        <i class="bi bi-bank me-1"></i>Pagar por transferencia
                    </button>
                </div>
            @else
                <button class="btn btn-outline-secondary w-100" disabled style="border-radius:10px;">
                    Plan gratuito
                </button>
            @endif
        </div>
    </div>
    @endforeach
</div>

{{-- ── Historial de pagos ──────────────────────────────────────────────── --}}
@if($historial->isNotEmpty())
<div class="card border-0 shadow-sm" style="border-radius:16px;overflow:hidden;">
    <div class="card-header border-0" style="background:#f8fafc;padding:1rem 1.25rem;">
        <h6 class="fw-bold mb-0"><i class="bi bi-clock-history me-2 text-muted"></i>Historial de pagos</h6>
    </div>
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0 align-middle" style="font-size:.82rem;">
            <thead style="background:#f8fafc;font-size:.72rem;text-transform:uppercase;letter-spacing:.05em;color:#94a3b8;">
                <tr>
                    <th class="ps-3 py-2">Plan</th>
                    <th>Período</th>
                    <th>Método</th>
                    <th>Monto</th>
                    <th>Estado</th>
                    <th class="pe-3">Referencia</th>
                </tr>
            </thead>
            <tbody>
            @foreach($historial as $sub)
            @php
                $estadoClases = [
                    'activa'     => 'success',
                    'prueba'     => 'warning',
                    'pendiente'  => 'info',
                    'vencida'    => 'secondary',
                    'cancelada'  => 'danger',
                    'suspendida' => 'danger',
                ];
            @endphp
            <tr>
                <td class="ps-3">
                    <span class="fw-semibold">{{ strtoupper($sub->plan?->nombre ?? '—') }}</span>
                </td>
                <td>
                    <span class="text-muted">
                        {{ $sub->fecha_inicio->format('d/m/Y') }} →
                        {{ $sub->fecha_fin->format('d/m/Y') }}
                    </span>
                </td>
                <td>
                    @if($sub->metodo_pago === 'stripe')
                        <span><i class="bi bi-credit-card me-1 text-primary"></i>Tarjeta</span>
                    @elseif($sub->metodo_pago === 'transferencia')
                        <span><i class="bi bi-bank me-1 text-secondary"></i>Transferencia</span>
                    @else
                        <span class="text-muted">{{ $sub->metodo_pago ?? '—' }}</span>
                    @endif
                </td>
                <td>
                    @if($sub->monto_pagado > 0)
                        <strong>${{ number_format($sub->monto_pagado, 2) }}</strong>
                    @else
                        <span class="text-muted">Gratis</span>
                    @endif
                </td>
                <td>
                    <span class="badge bg-{{ $estadoClases[$sub->estado] ?? 'secondary' }}" style="font-size:.68rem;">
                        {{ ucfirst($sub->estado) }}
                    </span>
                </td>
                <td class="pe-3">
                    <code style="font-size:.72rem;color:#94a3b8;">
                        {{ Str::limit($sub->referencia_pago ?? $sub->stripe_session_id ?? '—', 20) }}
                    </code>
                </td>
            </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- ═══════════════════════════════════════════════
     MODAL TRANSFERENCIA
═══════════════════════════════════════════════ --}}
<div class="modal fade" id="modalTransferencia" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:20px;border:none;">
            <form method="POST" action="{{ route('admin.billing.transferencia') }}">
                @csrf
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-bank me-2 text-primary"></i>Registrar Transferencia
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body px-4">
                    <div class="rounded-3 p-3 mb-3" style="background:#eff6ff;border:1px solid #bfdbfe;">
                        <p class="mb-1 fw-semibold" style="font-size:.82rem;color:#1d4ed8;">Realiza la transferencia a:</p>
                        <div style="font-size:.8rem;color:#1e3a5f;line-height:2;">
                            <div><strong>Banco Popular Dominicano</strong></div>
                            <div>Cta. Corriente: <strong>123-456789-0</strong></div>
                            <div>Titular: ZuraEdu SRL · RNC: 1-01-12345-6</div>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Plan</label>
                            <select name="plan_slug" id="tf-plan" class="form-select form-select-sm"
                                onchange="actualizarMontoTransferencia()" required>
                                @foreach($planes->where('precio_mensual','>', 0) as $p)
                                <option value="{{ $p->slug }}"
                                    data-mensual="{{ $p->precio_mensual }}"
                                    data-anual="{{ $p->precio_anual }}">
                                    {{ $p->nombre }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Ciclo</label>
                            <select name="ciclo" id="tf-ciclo" class="form-select form-select-sm"
                                onchange="actualizarMontoTransferencia()" required>
                                <option value="mensual">Mensual</option>
                                <option value="anual">Anual (2 meses gratis)</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Monto transferido (USD)</label>
                            <input type="number" name="monto" id="tf-monto" class="form-control form-control-sm"
                                step="0.01" min="1" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Nro. de referencia / comprobante</label>
                            <input type="text" name="referencia" class="form-control form-control-sm"
                                placeholder="Ej: TRF-202505-001" required>
                        </div>
                    </div>

                    <div class="alert alert-info mt-3 py-2" style="font-size:.78rem;">
                        <i class="bi bi-info-circle me-1"></i>
                        El equipo ZuraEdu verificará tu pago en <strong>24-48 horas</strong>.
                        Recibirás confirmación por email al activarse tu plan.
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="bi bi-send-check me-1"></i>Registrar transferencia
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
// ── Ciclo mensual/anual ───────────────────────────────────────────────────
document.getElementById('cicloSwitch').addEventListener('change', function() {
    const esAnual = this.checked;
    document.querySelectorAll('.ciclo-input').forEach(i => i.value = esAnual ? 'anual' : 'mensual');

    @foreach($planes as $plan)
    @if($plan->esPago())
    const el{{ Str::studly($plan->slug) }} = document.getElementById('precio-{{ $plan->slug }}');
    if (el{{ Str::studly($plan->slug) }}) {
        el{{ Str::studly($plan->slug) }}.innerHTML = esAnual
            ? `${{ round($plan->precio_anual / 12) }}<span style="font-size:1rem;font-weight:400;color:#64748b;">/mes</span>`
            : `${{ $plan->precio_mensual }}<span style="font-size:1rem;font-weight:400;color:#64748b;">/mes</span>`;

        const elA = document.getElementById('precio-anual-{{ $plan->slug }}');
        if (elA) elA.innerHTML = esAnual
            ? `Total anual: <strong>${{ $plan->precio_anual }}</strong>`
            : `o ${{ $plan->precio_anual }}/año`;
    }
    @endif
    @endforeach
});

// ── Abrir modal de transferencia con plan preseleccionado ─────────────────
function abrirTransferencia(slug, nombre, mensual, anual) {
    const sel = document.getElementById('tf-plan');
    if (sel) {
        for (let opt of sel.options) {
            if (opt.value === slug) { opt.selected = true; break; }
        }
    }
    actualizarMontoTransferencia();
    new bootstrap.Modal(document.getElementById('modalTransferencia')).show();
}

function actualizarMontoTransferencia() {
    const sel    = document.getElementById('tf-plan');
    const ciclo  = document.getElementById('tf-ciclo')?.value;
    const montoI = document.getElementById('tf-monto');
    if (!sel || !montoI) return;
    const opt = sel.options[sel.selectedIndex];
    montoI.value = ciclo === 'anual' ? opt.dataset.anual : opt.dataset.mensual;
}

// Inicializar monto
actualizarMontoTransferencia();
</script>
@endpush
