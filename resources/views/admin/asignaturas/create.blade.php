@extends('layouts.admin')
@section('page-title', 'Nueva Asignatura')

@push('styles')
<style>
    .form-card {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: 1.5rem;
    }
    .section-label {
        font-size: .7rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .08em;
        color: #94a3b8;
        margin-bottom: .75rem;
        padding-bottom: .4rem;
        border-bottom: 1px solid #f1f5f9;
    }
    .toggle-switch-label {
        display: flex;
        align-items: center;
        gap: .6rem;
        cursor: pointer;
        user-select: none;
    }
    .form-check-input[type="checkbox"].toggle-switch {
        width: 2.5rem;
        height: 1.35rem;
        cursor: pointer;
    }
    [data-theme="dark"] .form-section { background: #1e293b; border-color: #334155; }
</style>
@endpush

@section('content')

<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb mb-0" style="font-size:.82rem;">
        <li class="breadcrumb-item"><a href="{{ route('admin.asignaturas.index') }}" class="text-decoration-none">Asignaturas</a></li>
        <li class="breadcrumb-item active">Nueva</li>
    </ol>
</nav>

<div class="d-flex align-items-center justify-content-between mb-4">
    <h4 class="fw-bold mb-0" style="color:var(--primary)">
        <i class="bi bi-plus-circle me-2"></i>Nueva Asignatura
    </h4>
</div>

<form method="POST" action="{{ route('admin.asignaturas.store') }}">
@csrf

<div class="row g-3">
    {{-- Left column --}}
    <div class="col-lg-8">
        <div class="form-card">
            <div class="section-label">Información General</div>
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label fw-semibold" style="font-size:.85rem;">Código <span class="text-muted fw-normal">(opcional)</span></label>
                    <input type="text" name="codigo" class="form-control @error('codigo') is-invalid @enderror"
                           value="{{ old('codigo') }}" placeholder="Ej: MAT-001" maxlength="20">
                    @error('codigo')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-8">
                    <label class="form-label fw-semibold" style="font-size:.85rem;">Nombre <span class="text-danger">*</span></label>
                    <input type="text" name="nombre" class="form-control @error('nombre') is-invalid @enderror"
                           value="{{ old('nombre') }}" placeholder="Nombre de la asignatura" required>
                    @error('nombre')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold" style="font-size:.85rem;">Descripción <span class="text-muted fw-normal">(opcional)</span></label>
                    <textarea name="descripcion" class="form-control @error('descripcion') is-invalid @enderror"
                              rows="3" placeholder="Descripción breve de la asignatura...">{{ old('descripcion') }}</textarea>
                    @error('descripcion')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-8">
                    <label class="form-label fw-semibold" style="font-size:.85rem;">Área curricular <span class="text-danger">*</span></label>
                    <select name="area" class="form-select @error('area') is-invalid @enderror" required>
                        <option value="">— Seleccionar —</option>
                        <option value="academica" {{ old('area') === 'academica' ? 'selected' : '' }}>
                            Académica (Lengua, Matemáticas, Ciencias…)
                        </option>
                        <option value="tecnica" {{ old('area') === 'tecnica' ? 'selected' : '' }}>
                            Técnica (Tecnología, Especialidades…)
                        </option>
                    </select>
                    @error('area')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div class="text-muted mt-1" style="font-size:.73rem;">
                        Las materias técnicas permiten evaluación por RA y se filtran separadamente en el setup del docente.
                    </div>
                </div>
                <div class="col-md-8" id="area-id-wrapper">
                    <label class="form-label fw-semibold" style="font-size:.85rem;">
                        Área curricular detallada <span class="text-muted fw-normal">(MINERD)</span>
                    </label>
                    <select name="area_id" class="form-select @error('area_id') is-invalid @enderror">
                        <option value="">— Sin área —</option>
                        @foreach($areas ?? [] as $ar)
                        <option value="{{ $ar->id }}" {{ old('area_id') == $ar->id ? 'selected' : '' }}
                            style="color:{{ $ar->color ?? '#374151' }};">
                            {{ $ar->nombre }}
                            ({{ $ar->tipo === 'academica' ? 'Académica' : ($ar->tipo === 'tecnica' ? 'Técnica' : 'Ambas') }})
                        </option>
                        @endforeach
                    </select>
                    @error('area_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div class="text-muted mt-1" style="font-size:.73rem;">
                        Permite clasificar la materia dentro de las áreas curriculares del MINERD.
                    </div>
                </div>
                <div class="col-md-8" id="familia-wrapper" style="display:none;">
                    <label class="form-label fw-semibold" style="font-size:.85rem;">
                        Familia Profesional <span class="text-muted fw-normal">(opcional)</span>
                    </label>
                    <select name="familia_id" class="form-select @error('familia_id') is-invalid @enderror">
                        <option value="">— Sin familia —</option>
                        @foreach($familias ?? [] as $fam)
                        <option value="{{ $fam->id }}" {{ old('familia_id') == $fam->id ? 'selected' : '' }}>
                            {{ $fam->nombre }}
                        </option>
                        @endforeach
                    </select>
                    @error('familia_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold" style="font-size:.85rem;">Horas/semana</label>
                    <input type="number" name="horas_semanales"
                           class="form-control @error('horas_semanales') is-invalid @enderror"
                           value="{{ old('horas_semanales') }}"
                           min="1" max="20" placeholder="Ej: 5">
                    @error('horas_semanales')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold" style="font-size:.85rem;">
                        Núm. RAs <small class="text-muted fw-normal">(0 = componentes)</small>
                    </label>
                    <input type="number" name="num_ra"
                           class="form-control @error('num_ra') is-invalid @enderror"
                           value="{{ old('num_ra', 0) }}"
                           min="0" max="10" placeholder="0">
                    @error('num_ra')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div class="text-muted mt-1" style="font-size:.73rem;">
                        Si &gt; 0, habilita evaluación por RA en asignaciones técnicas.
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Right column --}}
    <div class="col-lg-4">
        <div class="form-card mb-3">
            <div class="section-label">Apariencia</div>
            <label class="form-label fw-semibold" style="font-size:.85rem;">Color identificador</label>
            <div class="d-flex align-items-center gap-3">
                <input type="color" name="color" id="input-color"
                       class="form-control form-control-color @error('color') is-invalid @enderror"
                       value="{{ old('color', '#1e3a6e') }}"
                       style="width:60px;height:42px;padding:3px;border-radius:8px;cursor:pointer;">
                <div>
                    <div id="color-preview-label" style="font-size:.82rem;font-weight:600;color:#1e293b;">
                        {{ old('color', '#1e3a6e') }}
                    </div>
                    <div style="font-size:.72rem;color:#9ca3af;">Clic para cambiar</div>
                </div>
            </div>
            @error('color')
            <div class="text-danger mt-1" style="font-size:.8rem;">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-card">
            <div class="section-label">Estado</div>
            <div class="form-check form-switch mb-3">
                <input class="form-check-input toggle-switch" type="checkbox"
                       name="activo" id="activo" value="1"
                       {{ old('activo', '1') ? 'checked' : '' }}>
                <label class="form-check-label fw-semibold" for="activo" style="font-size:.87rem;">
                    Asignatura activa
                </label>
            </div>
            <div class="text-muted mb-3" style="font-size:.75rem;">
                Las asignaturas inactivas no aparecen en las asignaciones.
            </div>
            <div class="form-check form-switch">
                <input class="form-check-input toggle-switch" type="checkbox"
                       name="es_basica" id="es_basica" value="1"
                       {{ old('es_basica', '1') ? 'checked' : '' }}>
                <label class="form-check-label fw-semibold" for="es_basica" style="font-size:.87rem;">
                    Materia básica (asignar automáticamente a grupos nuevos)
                </label>
            </div>
        </div>
    </div>
</div>

<div class="d-flex justify-content-between align-items-center mt-4">
    <a href="{{ route('admin.asignaturas.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-2"></i>Cancelar
    </a>
    <button type="submit" class="btn btn-primary px-5 fw-bold">
        <i class="bi bi-floppy me-2"></i>Guardar Asignatura
    </button>
</div>

</form>
@endsection

@push('scripts')
<script>
document.getElementById('input-color').addEventListener('input', function() {
    document.getElementById('color-preview-label').textContent = this.value;
});
const areaSelectC = document.querySelector('select[name="area"]');
const familiaWrapC = document.getElementById('familia-wrapper');
function toggleFamiliaCreate() {
    const esTecnica = areaSelectC.value === 'tecnica';
    familiaWrapC.style.display = esTecnica ? '' : 'none';
    if (!esTecnica) familiaWrapC.querySelector('select').value = '';
}
areaSelectC?.addEventListener('change', toggleFamiliaCreate);
</script>
@endpush
