@extends('layouts.admin')
@section('page-title', 'Nuevo Pago')

@push('styles')
<style>
.page-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:1.5rem; flex-wrap:wrap; gap:.75rem; }
.page-header h1 { font-size:1.25rem; font-weight:800; color:var(--primary); margin:0; }
.form-card { background:#fff; border-radius:12px; border:1px solid #e5e7eb; padding:1.75rem; max-width:640px; }
.form-label-custom { font-size:.83rem; font-weight:600; color:#374151; margin-bottom:.35rem; }
</style>
@endpush

@section('content')
<div class="page-header">
    <h1>
        <a href="{{ route('admin.pagos.index') }}" class="text-decoration-none me-2" style="color:#6b7280;">
            <i class="bi bi-arrow-left"></i>
        </a>
        Registrar Nuevo Pago
    </h1>
</div>

<div class="form-card">
    <form method="POST" action="{{ route('admin.pagos.store') }}">
        @csrf

        <div class="mb-3">
            <label class="form-label-custom">Estudiante / Matrícula</label>
            <select name="matricula_id" class="form-select form-select-sm @error('matricula_id') is-invalid @enderror" required>
                <option value="">— Seleccionar estudiante —</option>
                @foreach($matriculas as $m)
                    <option value="{{ $m->id }}"
                        {{ (request('matricula')==$m->id || old('matricula_id')==$m->id) ? 'selected' : '' }}>
                        {{ $m->estudiante->apellidos }}, {{ $m->estudiante->nombres }}
                        — {{ $m->grupo->grado->nombre ?? '' }} {{ $m->grupo->seccion->nombre ?? '' }}
                    </option>
                @endforeach
            </select>
            @error('matricula_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        @if($conceptos->isNotEmpty())
        <div class="mb-2">
            <label class="form-label-custom">Usar concepto predefinido</label>
            <select id="selectConceptoPredefinido" class="form-select form-select-sm">
                <option value="">— Seleccionar para autocompletar —</option>
                @foreach($conceptos as $cp)
                    <option value="{{ $cp->nombre }}" data-monto="{{ $cp->monto_defecto ?? '' }}">
                        {{ $cp->nombre }}
                        @if($cp->monto_defecto) — RD$ {{ number_format($cp->monto_defecto,2) }}@endif
                    </option>
                @endforeach
            </select>
        </div>
        @endif

        <div class="mb-3">
            <label class="form-label-custom">Concepto</label>
            <input type="text" id="inputConcepto" name="concepto" class="form-control form-control-sm @error('concepto') is-invalid @enderror"
                   value="{{ old('concepto', $concepto) }}" placeholder="Ej: Cuota Enero 2026" required>
            @error('concepto')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="row g-3 mb-3">
            <div class="col-6">
                <label class="form-label-custom">Monto (RD$)</label>
                <input type="number" id="inputMonto" name="monto" class="form-control form-control-sm @error('monto') is-invalid @enderror"
                       value="{{ old('monto') }}" step="0.01" min="0.01" required>
                @error('monto')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-6">
                <label class="form-label-custom">Fecha de vencimiento</label>
                <input type="date" name="fecha_vencimiento" class="form-control form-control-sm @error('fecha_vencimiento') is-invalid @enderror"
                       value="{{ old('fecha_vencimiento') }}" required>
                @error('fecha_vencimiento')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label-custom">Estado</label>
            <select name="estado" class="form-select form-select-sm" required>
                <option value="pendiente" {{ old('estado','pendiente')=='pendiente' ? 'selected' : '' }}>Pendiente</option>
                <option value="pagado"    {{ old('estado')=='pagado'    ? 'selected' : '' }}>Pagado</option>
                <option value="vencido"   {{ old('estado')=='vencido'   ? 'selected' : '' }}>Vencido</option>
                <option value="cancelado" {{ old('estado')=='cancelado' ? 'selected' : '' }}>Cancelado</option>
            </select>
        </div>

        <div id="camposPago" style="{{ old('estado','pendiente')==='pagado' ? '' : 'display:none;' }}">
            <div class="row g-3 mb-3">
                <div class="col-6">
                    <label class="form-label-custom">Fecha de pago</label>
                    <input type="date" name="fecha_pago" class="form-control form-control-sm"
                           value="{{ old('fecha_pago', date('Y-m-d')) }}">
                </div>
                <div class="col-6">
                    <label class="form-label-custom">Método de pago</label>
                    <select name="metodo_pago" class="form-select form-select-sm">
                        <option value="efectivo">Efectivo</option>
                        <option value="transferencia">Transferencia</option>
                        <option value="tarjeta">Tarjeta</option>
                        <option value="stripe">Stripe</option>
                        <option value="cardnet">CardNet</option>
                        <option value="otro">Otro</option>
                    </select>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label-custom">Referencia / No. recibo</label>
                <input type="text" name="referencia" class="form-control form-control-sm"
                       value="{{ old('referencia') }}" placeholder="Opcional">
            </div>
        </div>

        <div class="mb-4">
            <label class="form-label-custom">Notas</label>
            <textarea name="notas" class="form-control form-control-sm" rows="2"
                      placeholder="Observaciones opcionales">{{ old('notas') }}</textarea>
        </div>

        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary btn-sm px-4">
                <i class="bi bi-check-lg me-1"></i>Guardar
            </button>
            <a href="{{ route('admin.pagos.index') }}" class="btn btn-outline-secondary btn-sm">Cancelar</a>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
document.querySelector('[name=estado]').addEventListener('change', function () {
    document.getElementById('camposPago').style.display = this.value === 'pagado' ? '' : 'none';
});

const selCP = document.getElementById('selectConceptoPredefinido');
if (selCP) {
    selCP.addEventListener('change', function () {
        const opt = this.options[this.selectedIndex];
        if (opt.value) {
            document.getElementById('inputConcepto').value = opt.value;
            if (opt.dataset.monto) document.getElementById('inputMonto').value = opt.dataset.monto;
        }
    });
}
</script>
@endpush
