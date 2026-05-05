@extends('layouts.admin')
@section('page-title', isset($nomina) ? 'Editar Empleado' : 'Nuevo Empleado')

@section('content')

<x-breadcrumb :items="[
    ['label'=>'Dashboard','url'=>route('admin.dashboard')],
    ['label'=>'Nómina','url'=>route('admin.nomina.index')],
    ['label'=> isset($nomina) ? 'Editar' : 'Nuevo Empleado'],
]"/>

<div class="d-flex align-items-center gap-3 mb-4">
    <a href="{{ route('admin.nomina.index') }}" class="btn btn-outline-secondary btn-sm" style="border-radius:8px;">
        <i class="bi bi-arrow-left me-1"></i>Volver
    </a>
    <div>
        <h5 class="fw-bold mb-0">{{ isset($nomina) ? 'Editar Empleado' : 'Registrar Empleado en Nómina' }}</h5>
        @if(isset($nomina))<small class="text-muted">{{ $nomina->user->name }}</small>@endif
    </div>
</div>

<form method="POST" action="{{ isset($nomina) ? route('admin.nomina.update', $nomina) : route('admin.nomina.store') }}">
@csrf
@if(isset($nomina)) @method('PUT') @endif

<div class="row g-4">
<div class="col-lg-8">

    <div class="card border-0 shadow-sm mb-3" style="border-radius:16px;">
    <div class="card-body p-4">
        <h6 class="fw-bold mb-4"><i class="bi bi-person-badge me-2" style="color:#0f766e;"></i>Datos del Empleado</h6>

        @if(!isset($nomina))
        <div class="mb-3">
            <label class="form-label fw-semibold small">Usuario del sistema <span class="text-danger">*</span></label>
            <select name="user_id" class="form-select @error('user_id') is-invalid @enderror" required>
                <option value="">Selecciona un usuario...</option>
                @foreach($usuarios as $usr)
                <option value="{{ $usr->id }}" {{ old('user_id')==$usr->id ? 'selected':'' }}>
                    {{ $usr->name }} — {{ $usr->email }}
                </option>
                @endforeach
            </select>
            @error('user_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        @else
        <div class="mb-3 p-3 rounded-3" style="background:#F0FDF4;border:1px solid #86EFAC;">
            <div class="fw-semibold">{{ $nomina->user->name }}</div>
            <div class="text-muted small">{{ $nomina->user->email }}</div>
        </div>
        @endif

        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label fw-semibold small">Cargo <span class="text-danger">*</span></label>
                <input type="text" name="cargo" class="form-control @error('cargo') is-invalid @enderror"
                       value="{{ old('cargo', $nomina->cargo ?? '') }}" required placeholder="Ej: Docente de Matemáticas">
                @error('cargo')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold small">Cédula</label>
                <input type="text" name="cedula" class="form-control"
                       value="{{ old('cedula', $nomina->cedula ?? '') }}" placeholder="000-0000000-0">
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold small">Banco</label>
                <input type="text" name="banco" class="form-control"
                       value="{{ old('banco', $nomina->banco ?? '') }}" placeholder="Banco Popular, BHD...">
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold small">No. Cuenta Bancaria</label>
                <input type="text" name="cuenta_bancaria" class="form-control"
                       value="{{ old('cuenta_bancaria', $nomina->cuenta_bancaria ?? '') }}" placeholder="Número de cuenta">
            </div>
        </div>
    </div>
    </div>

    <div class="card border-0 shadow-sm mb-3" style="border-radius:16px;">
    <div class="card-body p-4">
        <h6 class="fw-bold mb-4"><i class="bi bi-currency-dollar me-2" style="color:#2563eb;"></i>Contrato y Compensación</h6>
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label fw-semibold small">Tipo de Contrato <span class="text-danger">*</span></label>
                <select name="tipo_contrato" class="form-select" required onchange="toggleHoras(this)">
                    @foreach(['fijo'=>'Fijo (tiempo completo)','temporal'=>'Temporal','hora'=>'Por hora'] as $val=>$lbl)
                    <option value="{{ $val }}" {{ old('tipo_contrato',$nomina->tipo_contrato??'fijo')===$val?'selected':'' }}>{{ $lbl }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4" id="divHoras" style="{{ old('tipo_contrato',$nomina->tipo_contrato??'fijo')==='hora' ? '' : 'display:none;' }}">
                <label class="form-label fw-semibold small">Horas / semana</label>
                <input type="number" name="horas_semana" class="form-control" min="1" max="168"
                       value="{{ old('horas_semana', $nomina->horas_semana ?? '') }}">
            </div>
            <div class="col-md-4">
                <label class="form-label fw-semibold small">Fecha de Ingreso <span class="text-danger">*</span></label>
                <input type="date" name="fecha_ingreso" class="form-control @error('fecha_ingreso') is-invalid @enderror"
                       value="{{ old('fecha_ingreso', isset($nomina) ? $nomina->fecha_ingreso?->format('Y-m-d') : '') }}" required>
                @error('fecha_ingreso')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4">
                <label class="form-label fw-semibold small">Salario Base (RD$) <span class="text-danger">*</span></label>
                <div class="input-group">
                    <span class="input-group-text" style="background:#F8FAFC;font-size:.85rem;">RD$</span>
                    <input type="number" name="salario_base" id="salarioBase" class="form-control @error('salario_base') is-invalid @enderror"
                           min="0" step="0.01" value="{{ old('salario_base', $nomina->salario_base ?? '') }}"
                           required placeholder="0.00" oninput="calcularPreview()">
                </div>
                @error('salario_base')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4">
                <label class="form-label fw-semibold small">% TSS Trabajador</label>
                <div class="input-group">
                    <input type="number" name="tss_porcentaje" id="tssPct" class="form-control"
                           min="0" max="20" step="0.01" value="{{ old('tss_porcentaje', $nomina->tss_porcentaje ?? 3.04) }}"
                           oninput="calcularPreview()">
                    <span class="input-group-text" style="background:#F8FAFC;font-size:.85rem;">%</span>
                </div>
                <div class="form-text" style="font-size:.72rem;">SFS: 3.04% &bull; AFP: 2.87%</div>
            </div>
            <div class="col-md-4 d-flex align-items-end pb-2">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="exento_isr" id="exentoISR" value="1"
                           {{ old('exento_isr', $nomina->exento_isr ?? false) ? 'checked' : '' }} onchange="calcularPreview()">
                    <label class="form-check-label small fw-semibold" for="exentoISR">Exento de ISR</label>
                </div>
            </div>
        </div>

        {{-- Preview --}}
        <div class="mt-3 p-3 rounded-3" style="background:#F0F9FF;border:1px solid #BAE6FD;">
            <div class="row g-2 text-center">
                @foreach([['prev-bruto','Salario Bruto','text-primary'],['prev-tss','TSS','text-danger'],['prev-isr','ISR estimado','text-danger'],['prev-neto','Neto estimado','text-success']] as [$id,$lbl,$cls])
                <div class="col-3">
                    <div class="text-muted" style="font-size:.72rem;">{{ $lbl }}</div>
                    <div class="fw-bold {{ $cls }}" id="{{ $id }}" style="{{ $id==='prev-neto'?'font-size:1rem;':'' }}">RD$ 0.00</div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    </div>

    <div class="card border-0 shadow-sm mb-3" style="border-radius:16px;">
    <div class="card-body p-4">
        <h6 class="fw-bold mb-3">Notas internas</h6>
        <textarea name="notas" class="form-control" rows="3"
                  placeholder="Observaciones, condiciones especiales del contrato...">{{ old('notas', $nomina->notas ?? '') }}</textarea>
    </div>
    </div>

</div>
<div class="col-lg-4">
<div class="card border-0 shadow-sm" style="border-radius:16px;position:sticky;top:80px;">
<div class="card-body p-4">
    <div class="form-check form-switch mb-4">
        <input class="form-check-input" type="checkbox" name="activo" id="activo" value="1"
               {{ old('activo', $nomina->activo ?? true) ? 'checked' : '' }}>
        <label class="form-check-label fw-semibold" for="activo">Empleado activo</label>
    </div>
    @if(isset($nomina))
    <div class="mb-4 p-3 rounded-3" style="background:#F8FAFC;border:1px solid #E5E7EB;">
        <div class="text-muted small mb-1">Antigüedad</div>
        <div class="fw-bold">{{ $nomina->antiguedad }}</div>
        <div class="text-muted small">Ingresó: {{ $nomina->fecha_ingreso?->format('d/m/Y') }}</div>
    </div>
    @endif
    <div class="d-grid gap-2">
        <button type="submit" class="btn btn-primary fw-bold" style="border-radius:10px;">
            <i class="bi bi-check-lg me-1"></i>{{ isset($nomina) ? 'Guardar cambios' : 'Registrar empleado' }}
        </button>
        <a href="{{ route('admin.nomina.index') }}" class="btn btn-outline-secondary" style="border-radius:10px;">Cancelar</a>
    </div>
</div>
</div>
</div>
</div>
</form>

<script>
function toggleHoras(sel) {
    document.getElementById('divHoras').style.display = sel.value === 'hora' ? '' : 'none';
}
function calcularPreview() {
    const bruto = parseFloat(document.getElementById('salarioBase').value) || 0;
    const tssPct = parseFloat(document.getElementById('tssPct').value) || 3.04;
    const exento = document.getElementById('exentoISR').checked;
    const tss = Math.round(bruto * tssPct / 100 * 100) / 100;
    let isr = 0;
    if (!exento) {
        const anual = bruto * 12;
        if (anual > 867123) isr = Math.round((79776 + (anual - 867123) * 0.25) / 12 * 100) / 100;
        else if (anual > 624329) isr = Math.round((31216 + (anual - 624329) * 0.20) / 12 * 100) / 100;
        else if (anual > 416220) isr = Math.round(((anual - 416220) * 0.15) / 12 * 100) / 100;
    }
    const neto = bruto - tss - isr;
    const fmt = n => 'RD$ ' + n.toLocaleString('es-DO', {minimumFractionDigits:2, maximumFractionDigits:2});
    document.getElementById('prev-bruto').textContent = fmt(bruto);
    document.getElementById('prev-tss').textContent   = '-' + fmt(tss);
    document.getElementById('prev-isr').textContent   = '-' + fmt(isr);
    document.getElementById('prev-neto').textContent  = fmt(neto);
}
document.addEventListener('DOMContentLoaded', calcularPreview);
</script>
@endsection
