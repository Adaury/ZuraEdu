@extends('layouts.admin')
@section('page-title', 'Editar Pago')

@push('styles')
<style>
.page-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:1.5rem; gap:.75rem; }
.page-header h1 { font-size:1.25rem; font-weight:800; color:var(--primary); margin:0; }
.form-card { background:#fff; border-radius:12px; border:1px solid #e5e7eb; padding:1.75rem; max-width:640px; }
.form-label-custom { font-size:.83rem; font-weight:600; color:#374151; margin-bottom:.35rem; }
.est-badge { background:#eff6ff; border:1px solid #bfdbfe; border-radius:8px; padding:.6rem 1rem; margin-bottom:1.25rem; font-size:.84rem; }
</style>
@endpush

@section('content')
<div class="page-header">
    <h1>
        <a href="{{ route('admin.pagos.por-estudiante', $pago->matricula) }}" class="text-decoration-none me-2" style="color:#6b7280;">
            <i class="bi bi-arrow-left"></i>
        </a>
        Editar Pago
    </h1>
</div>

<div class="form-card">
    <div class="est-badge">
        <i class="bi bi-person-fill me-1 text-primary"></i>
        <strong>{{ $pago->matricula->estudiante->nombre_completo }}</strong>
        &nbsp;·&nbsp;
        {{ $pago->matricula->grupo->grado->nombre ?? '' }} {{ $pago->matricula->grupo->seccion->nombre ?? '' }}
    </div>

    <form method="POST" action="{{ route('admin.pagos.update', $pago) }}">
        @csrf @method('PUT')

        <div class="mb-3">
            <label class="form-label-custom">Concepto</label>
            <input type="text" name="concepto" class="form-control form-control-sm"
                   value="{{ old('concepto', $pago->concepto) }}" required>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-6">
                <label class="form-label-custom">Monto (RD$)</label>
                <input type="number" name="monto" class="form-control form-control-sm"
                       value="{{ old('monto', $pago->monto) }}" step="0.01" min="0.01" required>
            </div>
            <div class="col-6">
                <label class="form-label-custom">Fecha de vencimiento</label>
                <input type="date" name="fecha_vencimiento" class="form-control form-control-sm"
                       value="{{ old('fecha_vencimiento', $pago->fecha_vencimiento->format('Y-m-d')) }}" required>
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label-custom">Estado</label>
            <select name="estado" class="form-select form-select-sm" id="selEstado">
                <option value="pendiente" {{ $pago->estado=='pendiente' ? 'selected':'' }}>Pendiente</option>
                <option value="pagado"    {{ $pago->estado=='pagado'    ? 'selected':'' }}>Pagado</option>
                <option value="vencido"   {{ $pago->estado=='vencido'   ? 'selected':'' }}>Vencido</option>
                <option value="cancelado" {{ $pago->estado=='cancelado' ? 'selected':'' }}>Cancelado</option>
            </select>
        </div>

        <div id="camposPago" style="{{ $pago->estado==='pagado' ? '' : 'display:none;' }}">
            <div class="row g-3 mb-3">
                <div class="col-6">
                    <label class="form-label-custom">Fecha de pago</label>
                    <input type="date" name="fecha_pago" class="form-control form-control-sm"
                           value="{{ old('fecha_pago', $pago->fecha_pago?->format('Y-m-d') ?? date('Y-m-d')) }}">
                </div>
                <div class="col-6">
                    <label class="form-label-custom">Método de pago</label>
                    <select name="metodo_pago" class="form-select form-select-sm">
                        @foreach(['efectivo','transferencia','tarjeta','stripe','otro'] as $met)
                        <option value="{{ $met }}" {{ $pago->metodo_pago==$met ? 'selected':'' }}>{{ ucfirst($met) }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label-custom">Referencia / No. recibo</label>
                <input type="text" name="referencia" class="form-control form-control-sm"
                       value="{{ old('referencia', $pago->referencia) }}" placeholder="Opcional">
            </div>
        </div>

        <div class="mb-4">
            <label class="form-label-custom">Notas</label>
            <textarea name="notas" class="form-control form-control-sm" rows="2">{{ old('notas', $pago->notas) }}</textarea>
        </div>

        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary btn-sm px-4">
                <i class="bi bi-check-lg me-1"></i>Guardar Cambios
            </button>
            <a href="{{ route('admin.pagos.por-estudiante', $pago->matricula) }}" class="btn btn-outline-secondary btn-sm">Cancelar</a>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
document.getElementById('selEstado').addEventListener('change', function () {
    document.getElementById('camposPago').style.display = this.value === 'pagado' ? '' : 'none';
});
</script>
@endpush
