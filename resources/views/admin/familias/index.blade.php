@extends('layouts.admin')
@section('page-title', 'Familias Profesionales')

@push('styles')
<style>
.familia-card {
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 14px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(30,58,110,.06);
    transition: box-shadow .2s;
}
.familia-card:hover { box-shadow: 0 4px 16px rgba(30,58,110,.12); }
.familia-header {
    padding: 1rem 1.25rem;
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: .5rem;
}
.familia-header-left { display: flex; align-items: center; gap: .6rem; }
.familia-icon { font-size: 1.4rem; }
.familia-nombre { font-size: 1rem; font-weight: 800; line-height: 1.2; }
.familia-body { padding: 1rem 1.25rem; }
.asig-chip {
    display: inline-flex;
    align-items: center;
    gap: .3rem;
    background: #f0f4ff;
    color: #1e3a6e;
    border-radius: 6px;
    padding: .2rem .55rem;
    font-size: .75rem;
    font-weight: 600;
    margin: .15rem;
}
.asig-chip .btn-quitar {
    background: none;
    border: none;
    padding: 0;
    line-height: 1;
    color: #6b7280;
    font-size: .7rem;
    cursor: pointer;
}
.asig-chip .btn-quitar:hover { color: #dc2626; }
.sin-asig { font-size: .8rem; color: #9ca3af; font-style: italic; }
.familia-footer {
    padding: .65rem 1.25rem;
    background: #f8fafc;
    border-top: 1px solid #f1f5f9;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: .5rem;
}
.badge-activa-fam   { background: #d1fae5; color: #065f46; font-size: .65rem; font-weight: 700; padding: .15rem .5rem; border-radius: 20px; }
.badge-inactiva-fam { background: #f3f4f6; color: #6b7280; font-size: .65rem; font-weight: 700; padding: .15rem .5rem; border-radius: 20px; }
.empty-familias { text-align: center; padding: 4rem 2rem; color: #9ca3af; }
.empty-familias i { font-size: 3rem; display: block; margin-bottom: 1rem; color: #d1d5db; }
[data-theme="dark"] .familia-card { background: #1e293b; border-color: #334155; }
[data-theme="dark"] .familia-body { color: #cbd5e1; }
[data-theme="dark"] .familia-footer { background: #0f172a; border-color: #334155; }
[data-theme="dark"] .asig-chip { background: #1e3a5f; color: #93c5fd; }
</style>
@endpush

@section('content')

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h1 class="h4 fw-bold mb-0" style="color:var(--primary);">
            <i class="bi bi-diagram-2 me-2"></i>Familias Profesionales
        </h1>
        <p class="text-muted mb-0 mt-1" style="font-size:.82rem;">
            Organiza las materias técnicas del segundo ciclo por familia profesional.
        </p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.familias.lista-pdf') }}" target="_blank" class="btn btn-danger btn-sm">
            <i class="bi bi-file-earmark-pdf-fill me-1"></i>PDF
        </a>
        <a href="{{ route('admin.familias.lista-excel') }}" class="btn btn-success btn-sm">
            <i class="bi bi-file-earmark-excel-fill me-1"></i>Excel
        </a>
        <button type="button" class="btn btn-sm fw-semibold"
                style="background:var(--primary);color:#fff;border-radius:8px;padding:.45rem 1rem;"
                data-bs-toggle="modal" data-bs-target="#modalNuevaFamilia">
        <i class="bi bi-plus-lg me-1"></i>Nueva Familia
        </button>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-3" style="border-radius:10px;">
        <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif
@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-3" style="border-radius:10px;">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

{{-- Materias técnicas sin familia --}}
@if($asignaturasTecnicas->isNotEmpty())
<div class="alert alert-warning border-0 mb-4" style="border-radius:10px;font-size:.83rem;">
    <i class="bi bi-exclamation-triangle me-2"></i>
    <strong>{{ $asignaturasTecnicas->count() }} materia(s) técnica(s) sin familia asignada:</strong>
    {{ $asignaturasTecnicas->pluck('nombre')->join(', ') }}
    — Asígnalas desde el botón "Agregar materia" en la familia correspondiente.
</div>
@endif

@if($familias->isEmpty())
    <div class="empty-familias">
        <i class="bi bi-diagram-2"></i>
        <h6 class="fw-semibold mb-1" style="color:#6b7280;">No hay familias profesionales</h6>
        <p style="font-size:.83rem;">Crea la primera familia para organizar las materias técnicas del segundo ciclo.</p>
        <button type="button" class="btn btn-sm fw-semibold"
                style="background:var(--primary);color:#fff;border-radius:8px;"
                data-bs-toggle="modal" data-bs-target="#modalNuevaFamilia">
            <i class="bi bi-plus-lg me-1"></i>Crear primera familia
        </button>
    </div>
@else
<div class="row g-3">
    @foreach($familias as $familia)
    <div class="col-md-6 col-xl-4">
        <div class="familia-card">

            {{-- Header --}}
            <div class="familia-header" style="background:{{ $familia->color }};">
                <div class="familia-header-left">
                    <i class="bi {{ $familia->icono }} familia-icon"></i>
                    <div>
                        <div class="familia-nombre">{{ $familia->nombre }}</div>
                        <div style="font-size:.72rem;opacity:.85;">{{ $familia->asignaturas_count }} materia(s)</div>
                    </div>
                </div>
                <span class="{{ $familia->activo ? 'badge-activa-fam' : 'badge-inactiva-fam' }}">
                    {{ $familia->activo ? 'Activa' : 'Inactiva' }}
                </span>
            </div>

            {{-- Descripción --}}
            @if($familia->descripcion)
            <div style="padding:.6rem 1.25rem .3rem;font-size:.78rem;color:#6b7280;">
                {{ Str::limit($familia->descripcion, 100) }}
            </div>
            @endif

            {{-- Materias --}}
            <div class="familia-body">
                <div style="font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:#9ca3af;margin-bottom:.5rem;">
                    Materias
                </div>
                @if($familia->asignaturas->isEmpty())
                    <span class="sin-asig">Sin materias asignadas</span>
                @else
                    @foreach($familia->asignaturas as $asig)
                    <span class="asig-chip" style="background:{{ $asig->color ?? '#f0f4ff' }}22;border:1px solid {{ $asig->color ?? '#c7d2fe' }}44;">
                        <span style="width:8px;height:8px;border-radius:50%;background:{{ $asig->color ?? '#6b7280' }};flex-shrink:0;display:inline-block;"></span>
                        {{ $asig->nombre }}
                        <form method="POST"
                              action="{{ route('admin.familias.asignaturas.quitar', [$familia, $asig]) }}"
                              style="display:inline;"
                              onsubmit="return confirm('¿Quitar {{ $asig->nombre }} de esta familia?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn-quitar" title="Quitar">
                                <i class="bi bi-x"></i>
                            </button>
                        </form>
                    </span>
                    @endforeach
                @endif

                {{-- Botones: nueva materia + asignar existente --}}
                <div class="d-flex gap-1 mt-2 flex-wrap">
                    <button type="button"
                            class="btn btn-sm fw-semibold btn-nueva-materia"
                            style="font-size:.72rem;border-radius:6px;background:var(--primary);color:#fff;"
                            data-familia-id="{{ $familia->id }}"
                            data-familia-nombre="{{ $familia->nombre }}"
                            data-familia-color="{{ $familia->color }}"
                            data-bs-toggle="modal" data-bs-target="#modalNuevaMateria">
                        <i class="bi bi-plus-lg me-1"></i>Nueva materia
                    </button>
                    @if($asignaturasTecnicas->isNotEmpty())
                    <button type="button"
                            class="btn btn-sm btn-outline-secondary btn-asignar-existente"
                            style="font-size:.72rem;border-radius:6px;"
                            data-familia-id="{{ $familia->id }}"
                            data-bs-toggle="collapse"
                            data-bs-target="#asignarExistente{{ $familia->id }}">
                        <i class="bi bi-link-45deg me-1"></i>Asignar existente
                    </button>
                    @endif
                </div>

                {{-- Colapso: asignar materia existente sin familia --}}
                @if($asignaturasTecnicas->isNotEmpty())
                <div class="collapse mt-2" id="asignarExistente{{ $familia->id }}">
                    <form method="POST"
                          action="{{ route('admin.familias.asignaturas.asignar', $familia) }}"
                          class="d-flex gap-1">
                        @csrf
                        <select name="asignatura_id" class="form-select form-select-sm"
                                style="font-size:.75rem;border-radius:6px;">
                            <option value="">— Seleccionar materia técnica —</option>
                            @foreach($asignaturasTecnicas as $at)
                                <option value="{{ $at->id }}">{{ $at->nombre }}</option>
                            @endforeach
                        </select>
                        <button type="submit" class="btn btn-sm btn-primary"
                                style="font-size:.72rem;border-radius:6px;white-space:nowrap;">
                            <i class="bi bi-check-lg"></i>
                        </button>
                    </form>
                </div>
                @endif
            </div>

            {{-- Footer acciones --}}
            <div class="familia-footer">
                <div class="d-flex gap-1">
                    <button type="button"
                            class="btn btn-sm btn-outline-secondary"
                            style="font-size:.72rem;border-radius:6px;"
                            data-bs-toggle="modal"
                            data-bs-target="#modalEditar{{ $familia->id }}">
                        <i class="bi bi-pencil me-1"></i>Editar
                    </button>
                    <form method="POST" action="{{ route('admin.familias.toggle', $familia) }}"
                          style="display:inline;">
                        @csrf @method('PATCH')
                        <button type="submit"
                                class="btn btn-sm {{ $familia->activo ? 'btn-outline-warning' : 'btn-outline-success' }}"
                                style="font-size:.72rem;border-radius:6px;">
                            <i class="bi bi-{{ $familia->activo ? 'pause' : 'play' }}-circle me-1"></i>
                            {{ $familia->activo ? 'Desactivar' : 'Activar' }}
                        </button>
                    </form>
                </div>
                <form method="POST" action="{{ route('admin.familias.destroy', $familia) }}"
                      onsubmit="return confirm('¿Eliminar la familia {{ $familia->nombre }}? Las materias quedarán sin familia.')">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-outline-danger"
                            style="font-size:.72rem;border-radius:6px;">
                        <i class="bi bi-trash3"></i>
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- Modal editar familia --}}
    <div class="modal fade" id="modalEditar{{ $familia->id }}" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius:14px;">
                <form method="POST" action="{{ route('admin.familias.update', $familia) }}">
                    @csrf @method('PUT')
                    <div class="modal-header" style="background:{{ $familia->color }};color:#fff;border-radius:14px 14px 0 0;">
                        <h6 class="modal-title fw-bold">
                            <i class="bi {{ $familia->icono }} me-2"></i>Editar Familia
                        </h6>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label fw-semibold" style="font-size:.83rem;">Nombre <span class="text-danger">*</span></label>
                                <input type="text" name="nombre" class="form-control" value="{{ $familia->nombre }}" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold" style="font-size:.83rem;">Descripción</label>
                                <textarea name="descripcion" class="form-control" rows="2">{{ $familia->descripcion }}</textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" style="font-size:.83rem;">Color</label>
                                <div class="d-flex align-items-center gap-2">
                                    <input type="color" name="color" class="form-control form-control-color"
                                           value="{{ $familia->color }}"
                                           style="width:48px;height:36px;padding:2px;border-radius:6px;">
                                    <span style="font-size:.78rem;color:#6b7280;">Color de la tarjeta</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" style="font-size:.83rem;">Icono <small class="text-muted fw-normal">(Bootstrap Icons)</small></label>
                                <input type="text" name="icono" class="form-control" value="{{ $familia->icono }}"
                                       placeholder="bi-briefcase">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-sm fw-semibold"
                                style="background:var(--primary);color:#fff;border-radius:7px;">
                            <i class="bi bi-floppy me-1"></i>Guardar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endforeach
</div>
@endif

{{-- Modal: Nueva materia dentro de una familia --}}
<div class="modal fade" id="modalNuevaMateria" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:14px;overflow:hidden;">
            <form method="POST" action="{{ route('admin.asignaturas.store') }}" id="formNuevaMateria">
                @csrf
                <input type="hidden" name="area" value="tecnica">
                <input type="hidden" name="familia_id" id="inputFamiliaId">
                <input type="hidden" name="activo" value="1">
                <input type="hidden" name="num_ra" value="0">
                <input type="hidden" name="redirect_to" value="familias">
                <div class="modal-header" id="modalNuevaMateriaHeader"
                     style="background:var(--primary);color:#fff;">
                    <h6 class="modal-title fw-bold">
                        <i class="bi bi-plus-circle me-2"></i>
                        Nueva materia — <span id="lblFamiliaNombre">Familia</span>
                    </h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold" style="font-size:.83rem;">
                                Código <span class="text-muted fw-normal">(opcional)</span>
                            </label>
                            <input type="text" name="codigo" class="form-control form-control-sm"
                                   placeholder="Ej: TEC-01" maxlength="20">
                        </div>
                        <div class="col-md-8">
                            <label class="form-label fw-semibold" style="font-size:.83rem;">
                                Nombre <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="nombre" class="form-control form-control-sm"
                                   placeholder="Nombre de la materia" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold" style="font-size:.83rem;">
                                Descripción <span class="text-muted fw-normal">(opcional)</span>
                            </label>
                            <textarea name="descripcion" class="form-control form-control-sm"
                                      rows="2" placeholder="Descripción breve..."></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size:.83rem;">Horas/semana</label>
                            <input type="number" name="horas_semanales" class="form-control form-control-sm"
                                   value="4" min="1" max="20">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size:.83rem;">
                                Núm. RAs <small class="text-muted fw-normal">(0 = componentes)</small>
                            </label>
                            <input type="number" name="num_ra" class="form-control form-control-sm"
                                   value="0" min="0" max="10">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold" style="font-size:.83rem;">Color identificador</label>
                            <div class="d-flex align-items-center gap-2">
                                <input type="color" name="color" id="colorNuevaMateria"
                                       class="form-control form-control-color"
                                       value="#1e3a6e"
                                       style="width:44px;height:34px;padding:2px;border-radius:6px;">
                                <span id="colorNuevaMateriaLabel" style="font-size:.78rem;font-weight:600;color:#374151;">#1e3a6e</span>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="es_basica"
                                       id="esBásicaNueva" value="1">
                                <label class="form-check-label fw-semibold" for="esBásicaNueva"
                                       style="font-size:.82rem;">
                                    Materia básica (asignar automáticamente a todos los grupos)
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-sm fw-semibold"
                            style="background:var(--primary);color:#fff;border-radius:7px;">
                        <i class="bi bi-floppy me-1"></i>Crear materia
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modal nueva familia --}}
<div class="modal fade" id="modalNuevaFamilia" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:14px;">
            <form method="POST" action="{{ route('admin.familias.store') }}">
                @csrf
                <div class="modal-header" style="background:var(--primary);color:#fff;border-radius:14px 14px 0 0;">
                    <h6 class="modal-title fw-bold">
                        <i class="bi bi-plus-circle me-2"></i>Nueva Familia Profesional
                    </h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-semibold" style="font-size:.83rem;">Nombre <span class="text-danger">*</span></label>
                            <input type="text" name="nombre" class="form-control" placeholder="Ej: Turismo" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold" style="font-size:.83rem;">Descripción <span class="text-muted fw-normal">(opcional)</span></label>
                            <textarea name="descripcion" class="form-control" rows="2"
                                      placeholder="Descripción de la familia profesional..."></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size:.83rem;">Color</label>
                            <div class="d-flex align-items-center gap-2">
                                <input type="color" name="color" class="form-control form-control-color"
                                       value="#1e3a6e"
                                       style="width:48px;height:36px;padding:2px;border-radius:6px;">
                                <span style="font-size:.78rem;color:#6b7280;">Color de la tarjeta</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size:.83rem;">
                                Icono <small class="text-muted fw-normal">(Bootstrap Icons)</small>
                            </label>
                            <input type="text" name="icono" class="form-control" value="bi-briefcase"
                                   placeholder="bi-briefcase">
                            <div style="font-size:.7rem;color:#9ca3af;margin-top:.25rem;">
                                Ejemplos: bi-airplane, bi-shop, bi-truck, bi-heart-pulse, bi-code-slash
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-sm fw-semibold"
                            style="background:var(--primary);color:#fff;border-radius:7px;">
                        <i class="bi bi-plus-lg me-1"></i>Crear Familia
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
// Rellenar modal "Nueva materia" con datos de la familia seleccionada
document.querySelectorAll('.btn-nueva-materia').forEach(function(btn) {
    btn.addEventListener('click', function() {
        const id     = this.dataset.familiaId;
        const nombre = this.dataset.familiaNombre;
        const color  = this.dataset.familiaColor;

        document.getElementById('inputFamiliaId').value  = id;
        document.getElementById('lblFamiliaNombre').textContent = nombre;
        document.getElementById('modalNuevaMateriaHeader').style.background = color;

        // Limpiar campos del formulario
        const form = document.getElementById('formNuevaMateria');
        form.querySelector('[name="nombre"]').value       = '';
        form.querySelector('[name="codigo"]').value       = '';
        form.querySelector('[name="descripcion"]').value  = '';
        form.querySelector('[name="horas_semanales"]').value = '4';
        form.querySelector('[name="num_ra"]').value       = '0';
        const colorInput = document.getElementById('colorNuevaMateria');
        colorInput.value = color;
        document.getElementById('colorNuevaMateriaLabel').textContent = color;
    });
});

// Actualizar label del color en el modal
document.getElementById('colorNuevaMateria')?.addEventListener('input', function() {
    document.getElementById('colorNuevaMateriaLabel').textContent = this.value;
});
</script>
@endpush
