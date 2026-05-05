@extends('layouts.admin')
@section('page-title', 'Configuración de Pagos')

@push('styles')
<style>
.page-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:1.5rem; flex-wrap:wrap; gap:.75rem; }
.page-header h1 { font-size:1.25rem; font-weight:800; color:var(--primary); margin:0; }
.form-card { background:#fff; border-radius:12px; border:1px solid #e5e7eb; padding:1.75rem; max-width:640px; }
.section-title { font-size:.72rem; font-weight:700; text-transform:uppercase; letter-spacing:.07em; color:var(--primary); border-bottom:2px solid var(--primary); padding-bottom:.4rem; margin-bottom:1.1rem; }
.form-label-custom { font-size:.83rem; font-weight:600; color:#374151; margin-bottom:.35rem; }
.toggle-row { display:flex; align-items:center; justify-content:space-between; background:#f8fafc; border-radius:8px; padding:.75rem 1rem; margin-bottom:1rem; }
.toggle-row label { font-size:.87rem; font-weight:600; color:#374151; margin:0; cursor:pointer; }
.form-check-input { width:2.5em; height:1.35em; cursor:pointer; }
.gateway-options { display:flex; gap:.75rem; flex-wrap:wrap; margin-bottom:1rem; }
.gateway-card { flex:1; min-width:120px; border:2px solid #e5e7eb; border-radius:10px; padding:.9rem 1rem; cursor:pointer; text-align:center; transition:border-color .15s; }
.gateway-card.selected { border-color:var(--primary); background:#eff6ff; }
.gateway-card input[type=radio] { display:none; }
.gateway-card .gw-name { font-size:.85rem; font-weight:700; color:#374151; }
.gateway-card .gw-desc { font-size:.72rem; color:#6b7280; }
</style>
@endpush

@section('content')
<div class="page-header">
    <h1>
        <a href="{{ route('admin.pagos.index') }}" class="text-decoration-none me-2" style="color:#6b7280;">
            <i class="bi bi-arrow-left"></i>
        </a>
        <i class="bi bi-gear me-2" style="color:var(--primary)"></i>Configuración de Pagos
    </h1>
</div>

<div class="form-card">
    @if(session('success'))
    <div class="alert alert-success py-2 mb-4" style="font-size:.83rem;border-radius:8px;">
        <i class="bi bi-check-circle me-1"></i>{{ session('success') }}
    </div>
    @endif

    <form method="POST" action="{{ route('admin.pagos.config.update') }}">
        @csrf

        {{-- Activar módulo --}}
        <div class="section-title">Módulo de Pagos</div>
        <div class="toggle-row mb-4">
            <div>
                <label class="toggle-row label" for="switchPagos">Activar módulo de pagos</label>
                <div style="font-size:.75rem;color:#6b7280;margin-top:.15rem;">Solo disponible para centros privados. Aparecerá en el sidebar de navegación.</div>
            </div>
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" name="module_payments" id="switchPagos"
                       value="1" {{ $config['module_payments'] ? 'checked' : '' }}>
            </div>
        </div>

        {{-- Concepto y moneda --}}
        <div class="section-title">Configuración General</div>
        <div class="mb-3">
            <label class="form-label-custom">Concepto por defecto</label>
            <input type="text" name="payments_concept" class="form-control form-control-sm"
                   value="{{ old('payments_concept', $config['payments_concept']) }}"
                   placeholder="Ej: Cuota escolar mensual" required>
            <div class="form-text">Se usa como texto predeterminado al crear cuotas.</div>
        </div>
        <div class="mb-4">
            <label class="form-label-custom">Moneda</label>
            <select name="payments_currency" class="form-select form-select-sm" style="max-width:200px;">
                @foreach(['DOP' => 'DOP — Peso Dominicano', 'USD' => 'USD — Dólar', 'EUR' => 'EUR — Euro'] as $code => $label)
                <option value="{{ $code }}" {{ $config['payments_currency']==$code ? 'selected':'' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        {{-- Pasarela --}}
        <div class="section-title">Pasarela de Pago en línea</div>
        <p style="font-size:.82rem;color:#6b7280;margin-bottom:.75rem;">
            Selecciona <strong>Manual</strong> si solo vas a registrar pagos en efectivo/transferencia desde el admin.
        </p>
        <div class="gateway-options" id="gatewayOptions">
            @foreach(['manual' => ['Manual', 'Efectivo / Transferencia'], 'stripe' => ['Stripe', 'Tarjeta en línea'], 'cardnet' => ['CardNet', 'RD — Próximamente']] as $val => [$name, $desc])
            <label class="gateway-card {{ $config['payments_gateway']==$val ? 'selected':'' }}" for="gw_{{ $val }}">
                <input type="radio" name="payments_gateway" id="gw_{{ $val }}" value="{{ $val }}"
                       {{ $config['payments_gateway']==$val ? 'checked':'' }}>
                <div class="gw-name">{{ $name }}</div>
                <div class="gw-desc">{{ $desc }}</div>
            </label>
            @endforeach
        </div>

        {{-- Claves Stripe --}}
        <div id="stripeFields" style="{{ $config['payments_gateway']==='stripe' ? '' : 'display:none;' }}">
            <div class="alert alert-info py-2 mb-3" style="font-size:.8rem;border-radius:8px;">
                <i class="bi bi-shield-lock me-1"></i>
                Obtén las claves en <strong>dashboard.stripe.com → Developers → API keys</strong>.
            </div>
            <div class="mb-3">
                <label class="form-label-custom">Stripe Publishable Key (pk_...)</label>
                <input type="text" name="payments_stripe_pk" class="form-control form-control-sm font-monospace"
                       value="{{ old('payments_stripe_pk', $config['payments_stripe_pk']) }}"
                       placeholder="pk_live_...">
            </div>
            <div class="mb-4">
                <label class="form-label-custom">Stripe Secret Key (sk_...)</label>
                <input type="password" name="payments_stripe_sk" class="form-control form-control-sm font-monospace"
                       value="{{ old('payments_stripe_sk', $config['payments_stripe_sk']) }}"
                       placeholder="sk_live_...">
                <div class="form-text text-danger"><i class="bi bi-exclamation-triangle me-1"></i>Nunca compartas esta clave.</div>
            </div>
        </div>

        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary btn-sm px-4">
                <i class="bi bi-check-lg me-1"></i>Guardar Configuración
            </button>
            <a href="{{ route('admin.pagos.index') }}" class="btn btn-outline-secondary btn-sm">Cancelar</a>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
document.querySelectorAll('[name=payments_gateway]').forEach(function(radio) {
    radio.addEventListener('change', function() {
        document.querySelectorAll('.gateway-card').forEach(c => c.classList.remove('selected'));
        this.closest('.gateway-card').classList.add('selected');
        document.getElementById('stripeFields').style.display = this.value === 'stripe' ? '' : 'none';
    });
});
</script>
@endpush
