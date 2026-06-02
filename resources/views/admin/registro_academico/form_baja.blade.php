@extends('layouts.admin')
@section('page-title', 'Registrar Baja')

@section('content')
<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
    <div>
        <h1 class="mb-0" style="font-size:1.3rem;font-weight:800;color:var(--primary);">
            <i class="bi bi-person-dash-fill me-2" style="color:#ef4444;"></i>Registrar Baja / Retiro
        </h1>
        <p class="text-muted mb-0" style="font-size:.82rem;">Documentar la salida del estudiante del plantel</p>
    </div>
    <a href="{{ route('admin.registro-academico.bajas') }}" class="btn btn-sm btn-outline-secondary" style="border-radius:8px;">
        <i class="bi bi-arrow-left me-1"></i>Volver
    </a>
</div>

{{-- Info del estudiante --}}
<div style="background:#fff;border-radius:14px;border:1px solid #e5e7eb;padding:1.25rem 1.5rem;margin-bottom:1.5rem;">
    <div style="font-size:.75rem;font-weight:700;text-transform:uppercase;color:#6b7280;margin-bottom:.75rem;">
        Estudiante
    </div>
    <div class="d-flex align-items-center gap-3">
        <div style="width:48px;height:48px;border-radius:50%;background:linear-gradient(135deg,#2a4f96,var(--primary));color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.85rem;flex-shrink:0;">
            {{ mb_strtoupper(mb_substr($matricula->estudiante->nombres,0,1) . mb_substr($matricula->estudiante->apellidos,0,1)) }}
        </div>
        <div>
            <div style="font-weight:700;font-size:1rem;color:#1e293b;">{{ $matricula->estudiante->nombre_completo }}</div>
            <div style="font-size:.8rem;color:#6b7280;">
                {{ $matricula->estudiante->numero_matricula }}
                @if($matricula->estudiante->cedula) · {{ $matricula->estudiante->cedula }} @endif
            </div>
            <div style="font-size:.8rem;color:#6b7280;">
                {{ $matricula->grupo?->grado?->nombre }} {{ $matricula->grupo?->seccion?->nombre }}
                · {{ $matricula->schoolYear?->nombre }}
            </div>
        </div>
    </div>
</div>

{{-- Formulario --}}
<div style="background:#fff;border-radius:14px;border:1px solid #e5e7eb;padding:1.5rem;">
    <form method="POST" action="{{ route('admin.registro-academico.baja.registrar', $matricula) }}">
        @csrf

        @if($errors->any())
        <div class="alert alert-danger mb-4" style="border-radius:10px;font-size:.84rem;">
            <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
        @endif

        <div class="row g-3">
            {{-- Tipo de baja --}}
            <div class="col-12">
                <label class="form-label fw-bold" style="font-size:.85rem;">Tipo de Baja <span class="text-danger">*</span></label>
                <div class="d-flex gap-3">
                    <label class="d-flex align-items-center gap-2 p-3 rounded" style="border:2px solid {{ old('tipo')=='retirada' ? '#ef4444' : '#e5e7eb' }};cursor:pointer;flex:1;transition:border-color .2s;" id="lbl-retirada">
                        <input type="radio" name="tipo" value="retirada" {{ old('tipo','retirada') === 'retirada' ? 'checked' : '' }} onchange="toggleTipo(this)">
                        <div>
                            <div style="font-weight:600;color:#ef4444;">Retiro</div>
                            <div style="font-size:.75rem;color:#6b7280;">El estudiante deja de estudiar</div>
                        </div>
                    </label>
                    <label class="d-flex align-items-center gap-2 p-3 rounded" style="border:2px solid {{ old('tipo')=='transferida' ? '#f59e0b' : '#e5e7eb' }};cursor:pointer;flex:1;transition:border-color .2s;" id="lbl-transferida">
                        <input type="radio" name="tipo" value="transferida" {{ old('tipo') === 'transferida' ? 'checked' : '' }} onchange="toggleTipo(this)">
                        <div>
                            <div style="font-weight:600;color:#f59e0b;">Traslado</div>
                            <div style="font-size:.75rem;color:#6b7280;">Se transfiere a otra institución</div>
                        </div>
                    </label>
                </div>
            </div>

            {{-- Fecha --}}
            <div class="col-md-4">
                <label class="form-label fw-bold" style="font-size:.85rem;">Fecha de Baja <span class="text-danger">*</span></label>
                <input type="date" name="fecha_baja" value="{{ old('fecha_baja', today()->format('Y-m-d')) }}"
                       class="form-control @error('fecha_baja') is-invalid @enderror" style="border-radius:8px;">
                @error('fecha_baja')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            {{-- Institución destino (solo si traslado) --}}
            <div class="col-md-8" id="campo-institucion" style="{{ old('tipo') !== 'transferida' ? 'display:none;' : '' }}">
                <label class="form-label fw-bold" style="font-size:.85rem;">Institución Destino</label>
                <input type="text" name="institucion_traslado" value="{{ old('institucion_traslado') }}"
                       placeholder="Nombre del centro educativo destino"
                       class="form-control @error('institucion_traslado') is-invalid @enderror" style="border-radius:8px;">
                @error('institucion_traslado')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            {{-- Motivo --}}
            <div class="col-12">
                <label class="form-label fw-bold" style="font-size:.85rem;">Motivo <span class="text-danger">*</span></label>
                <div class="d-flex flex-wrap gap-2 mb-2">
                    @foreach(['Mudanza familiar','Problemas económicos','Cambio de institución','Viaje al exterior','Motivos personales','Otro'] as $motivo)
                    <button type="button" class="btn btn-sm btn-outline-secondary" style="border-radius:20px;font-size:.77rem;"
                            onclick="document.getElementById('motivo_baja').value='{{ $motivo }}'">
                        {{ $motivo }}
                    </button>
                    @endforeach
                </div>
                <textarea name="motivo_baja" id="motivo_baja" rows="3"
                          placeholder="Describe el motivo de la baja..."
                          class="form-control @error('motivo_baja') is-invalid @enderror" style="border-radius:8px;">{{ old('motivo_baja') }}</textarea>
                @error('motivo_baja')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>

        <div class="d-flex gap-2 mt-4 justify-content-end">
            <a href="{{ route('admin.registro-academico.bajas') }}" class="btn btn-outline-secondary" style="border-radius:8px;">
                Cancelar
            </a>
            <button type="submit" class="btn btn-danger" style="border-radius:8px;font-weight:600;"
                    onclick="return confirm('¿Confirmar la baja de {{ $matricula->estudiante->nombre_completo }}? Esta acción desactivará su acceso al sistema.')">
                <i class="bi bi-person-dash me-1"></i>Registrar Baja
            </button>
        </div>
    </form>
</div>

@push('scripts')
<script>
function toggleTipo(radio) {
    const instDiv = document.getElementById('campo-institucion');
    instDiv.style.display = radio.value === 'transferida' ? '' : 'none';
    document.getElementById('lbl-retirada').style.borderColor    = radio.value === 'retirada'    ? '#ef4444' : '#e5e7eb';
    document.getElementById('lbl-transferida').style.borderColor = radio.value === 'transferida' ? '#f59e0b' : '#e5e7eb';
}
</script>
@endpush
@endsection
