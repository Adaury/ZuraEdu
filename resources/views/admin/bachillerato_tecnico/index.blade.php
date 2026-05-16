@extends('layouts.admin')
@section('page-title', 'Bachillerato Técnico')

@push('styles')
<style>
.bt-tab-btn {
    background: #f1f5f9;
    border: none;
    border-radius: 9px;
    padding: .45rem 1.1rem;
    font-size: .82rem;
    font-weight: 600;
    color: #64748b;
    cursor: pointer;
    transition: all .15s;
    display: flex;
    align-items: center;
    gap: .4rem;
}
.bt-tab-btn.active,
.bt-tab-btn:hover {
    background: var(--primary);
    color: #fff;
}
.bt-card {
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 14px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(30,58,110,.06);
    margin-bottom: .75rem;
    transition: box-shadow .15s;
}
.bt-card:hover { box-shadow: 0 4px 16px rgba(30,58,110,.1); }
.bt-card-header {
    padding: .8rem 1.1rem;
    display: flex;
    align-items: center;
    gap: .6rem;
    color: #fff;
}
.bt-card-body { padding: .9rem 1.1rem; }
.bt-badge-activo   { background: #d1fae5; color: #065f46; font-size: .65rem; font-weight: 700; padding: .15rem .5rem; border-radius: 20px; }
.bt-badge-inactivo { background: #f3f4f6; color: #6b7280; font-size: .65rem; font-weight: 700; padding: .15rem .5rem; border-radius: 20px; }
.bt-curso-row {
    display: flex;
    align-items: center;
    gap: .5rem;
    padding: .5rem .75rem;
    border-radius: 8px;
    background: #f8fafc;
    margin-bottom: .4rem;
    font-size: .82rem;
}
.bt-modulo-chip {
    display: inline-flex;
    align-items: center;
    gap: .3rem;
    background: #eff6ff;
    color: #1e3a6e;
    border-radius: 6px;
    padding: .2rem .55rem;
    font-size: .75rem;
    font-weight: 600;
    margin: .15rem;
}
.empty-bt {
    text-align: center;
    padding: 3rem 2rem;
    color: #9ca3af;
}
.empty-bt i { font-size: 2.5rem; display: block; margin-bottom: .75rem; color: #d1d5db; }
[data-theme="dark"] .bt-card { background: #1e293b; border-color: #334155; }
[data-theme="dark"] .bt-card-body { color: #cbd5e1; }
[data-theme="dark"] .bt-curso-row { background: #0f172a; }
[data-theme="dark"] .bt-modulo-chip { background: #1e3a5f; color: #93c5fd; }
</style>
@endpush

@section('content')

<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
    <div>
        <h1 class="h4 fw-bold mb-0" style="color:var(--primary);">
            <i class="bi bi-mortarboard-fill me-2"></i>Bachillerato Técnico
        </h1>
        <p class="text-muted mb-0 mt-1" style="font-size:.82rem;">
            Estructura curricular MINERD: Áreas Técnicas → Cursos → Módulos Formativos
        </p>
    </div>
    <div id="btnNuevo">
        {{-- se actualiza según tab --}}
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

{{-- Tabs ──────────────────────────────────────────────────────────────── --}}
<div class="d-flex gap-2 mb-4 flex-wrap">
    <button class="bt-tab-btn active" data-tab="areas" onclick="switchTab('areas')">
        <i class="bi bi-diagram-2"></i>Áreas Técnicas
        <span class="badge bg-secondary ms-1" style="font-size:.65rem;">{{ $areas->count() }}</span>
    </button>
    <button class="bt-tab-btn" data-tab="cursos" onclick="switchTab('cursos')">
        <i class="bi bi-journal-bookmark-fill"></i>Cursos
        <span class="badge bg-secondary ms-1" style="font-size:.65rem;">{{ $cursos->count() }}</span>
    </button>
    <button class="bt-tab-btn" data-tab="modulos" onclick="switchTab('modulos')">
        <i class="bi bi-layers-fill"></i>Módulos Formativos
        <span class="badge bg-secondary ms-1" style="font-size:.65rem;">{{ $modulos->count() }}</span>
    </button>
</div>

{{-- ══════════════════════════════════════════════════════════════════════
     TAB 1 — ÁREAS TÉCNICAS
══════════════════════════════════════════════════════════════════════ --}}
<div id="tab-areas">
<div class="d-flex justify-content-end mb-3">
    <button type="button" class="btn btn-sm fw-semibold"
            style="background:var(--primary);color:#fff;border-radius:8px;padding:.45rem 1rem;"
            data-bs-toggle="modal" data-bs-target="#modalNuevaArea">
        <i class="bi bi-plus-lg me-1"></i>Nueva Área
    </button>
</div>

@if($areas->isEmpty())
<div class="empty-bt">
    <i class="bi bi-diagram-2"></i>
    <h6 class="fw-semibold mb-1" style="color:#6b7280;">Sin áreas técnicas</h6>
    <p style="font-size:.83rem;">Crea la primera área técnica del Bachillerato Técnico MINERD.</p>
</div>
@else
<div class="row g-3">
    @foreach($areas as $area)
    <div class="col-md-6 col-xl-4">
        <div class="bt-card">
            <div class="bt-card-header" style="background:{{ $area->color }};">
                <i class="bi bi-diagram-2 fs-5"></i>
                <div style="flex:1;min-width:0;">
                    <div style="font-size:.95rem;font-weight:800;line-height:1.2;">
                        {{ $area->nombre }}
                        @if($area->codigo)
                        <span style="font-size:.68rem;font-weight:600;opacity:.85;margin-left:.3rem;">[{{ $area->codigo }}]</span>
                        @endif
                    </div>
                    <div style="font-size:.72rem;opacity:.85;">{{ $area->cursos_count }} curso(s)</div>
                </div>
                <span class="{{ $area->activo ? 'bt-badge-activo' : 'bt-badge-inactivo' }}">
                    {{ $area->activo ? 'Activa' : 'Inactiva' }}
                </span>
            </div>

            <div class="bt-card-body">
                @if($area->descripcion)
                <p style="font-size:.78rem;color:#6b7280;margin-bottom:.7rem;">{{ Str::limit($area->descripcion, 100) }}</p>
                @endif

                {{-- Cursos del área --}}
                @if($area->cursos->isEmpty())
                    <p style="font-size:.78rem;color:#9ca3af;font-style:italic;">Sin cursos registrados</p>
                @else
                    @foreach($area->cursos as $curso)
                    <div class="bt-curso-row">
                        <i class="bi bi-journal-bookmark" style="color:#2563eb;font-size:.8rem;flex-shrink:0;"></i>
                        <span style="flex:1;font-weight:600;color:#1e293b;">
                            {{ $curso->nombre }}
                            @if($curso->codigo) <span style="color:#94a3b8;">[{{ $curso->codigo }}]</span>@endif
                        </span>
                        <span style="font-size:.68rem;color:#64748b;">{{ $curso->modulos_count }} mód.</span>
                        <span class="{{ $curso->activo ? 'bt-badge-activo' : 'bt-badge-inactivo' }}">{{ $curso->activo ? '✓' : '—' }}</span>
                    </div>
                    @endforeach
                @endif
            </div>

            <div style="padding:.65rem 1.1rem;background:#f8fafc;border-top:1px solid #f1f5f9;display:flex;align-items:center;justify-content:space-between;gap:.5rem;">
                <div class="d-flex gap-1">
                    <button type="button" class="btn btn-sm btn-outline-secondary"
                            style="font-size:.72rem;border-radius:6px;"
                            data-bs-toggle="modal"
                            data-bs-target="#modalEditarArea{{ $area->id }}">
                        <i class="bi bi-pencil me-1"></i>Editar
                    </button>
                    <form method="POST" action="{{ route('admin.bachillerato-tecnico.areas.toggle', $area) }}" style="display:inline;">
                        @csrf @method('PATCH')
                        <button type="submit" class="btn btn-sm {{ $area->activo ? 'btn-outline-warning' : 'btn-outline-success' }}"
                                style="font-size:.72rem;border-radius:6px;">
                            {{ $area->activo ? 'Desactivar' : 'Activar' }}
                        </button>
                    </form>
                </div>
                <form method="POST" action="{{ route('admin.bachillerato-tecnico.areas.destroy', $area) }}"
                      onsubmit="return confirm('¿Eliminar el área {{ $area->nombre }}? Se eliminarán también sus cursos y módulos.')">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-outline-danger" style="font-size:.72rem;border-radius:6px;">
                        <i class="bi bi-trash3"></i>
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- Modal editar área --}}
    <div class="modal fade" id="modalEditarArea{{ $area->id }}" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius:14px;">
                <form method="POST" action="{{ route('admin.bachillerato-tecnico.areas.update', $area) }}">
                    @csrf @method('PUT')
                    <div class="modal-header" style="background:{{ $area->color }};color:#fff;border-radius:14px 14px 0 0;">
                        <h6 class="modal-title fw-bold"><i class="bi bi-pencil me-2"></i>Editar Área Técnica</h6>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-8">
                                <label class="form-label fw-semibold" style="font-size:.83rem;">Nombre <span class="text-danger">*</span></label>
                                <input type="text" name="nombre" class="form-control" value="{{ $area->nombre }}" required maxlength="100">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold" style="font-size:.83rem;">Código</label>
                                <input type="text" name="codigo" class="form-control" value="{{ $area->codigo }}" maxlength="20" placeholder="Ej: INF">
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold" style="font-size:.83rem;">Descripción</label>
                                <textarea name="descripcion" class="form-control" rows="2">{{ $area->descripcion }}</textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" style="font-size:.83rem;">Color</label>
                                <div class="d-flex align-items-center gap-2">
                                    <input type="color" name="color" class="form-control form-control-color"
                                           value="{{ $area->color }}" style="width:48px;height:36px;padding:2px;border-radius:6px;">
                                    <span style="font-size:.78rem;color:#6b7280;">Color del encabezado</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" style="font-size:.83rem;">Orden</label>
                                <input type="number" name="orden" class="form-control" value="{{ $area->orden }}" min="0">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-sm fw-semibold" style="background:var(--primary);color:#fff;border-radius:7px;">
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
</div>{{-- /tab-areas --}}

{{-- ══════════════════════════════════════════════════════════════════════
     TAB 2 — CURSOS TÉCNICOS
══════════════════════════════════════════════════════════════════════ --}}
<div id="tab-cursos" style="display:none;">
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    @if($areas->isEmpty())
    <div class="alert alert-warning border-0 w-100" style="border-radius:10px;font-size:.83rem;">
        <i class="bi bi-exclamation-triangle me-2"></i>Primero crea al menos un Área Técnica antes de agregar cursos.
    </div>
    @else
    <span></span>
    <button type="button" class="btn btn-sm fw-semibold"
            style="background:var(--primary);color:#fff;border-radius:8px;padding:.45rem 1rem;"
            data-bs-toggle="modal" data-bs-target="#modalNuevoCurso">
        <i class="bi bi-plus-lg me-1"></i>Nuevo Curso
    </button>
    @endif
</div>

@if($cursos->isEmpty())
<div class="empty-bt">
    <i class="bi bi-journal-bookmark"></i>
    <h6 class="fw-semibold mb-1" style="color:#6b7280;">Sin cursos técnicos</h6>
    <p style="font-size:.83rem;">Crea el primer curso técnico dentro de un área.</p>
</div>
@else
{{-- Agrupados por área --}}
@php $cursosPorArea = $cursos->groupBy('area_tecnica_id'); @endphp
@foreach($cursosPorArea as $areaId => $cursosGrupo)
@php $areaRel = $cursosGrupo->first()->area; @endphp
<div class="mb-4">
    <div style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#64748b;
                padding:.4rem .75rem;background:#f1f5f9;border-radius:8px;margin-bottom:.6rem;
                display:flex;align-items:center;gap:.5rem;">
        <span style="width:10px;height:10px;border-radius:50%;background:{{ $areaRel?->color ?? '#64748b' }};flex-shrink:0;"></span>
        {{ $areaRel?->nombre ?? '—' }}
    </div>
    <div class="row g-2">
        @foreach($cursosGrupo as $curso)
        <div class="col-md-6 col-xl-4">
            <div class="bt-card" style="border-top:3px solid {{ $areaRel?->color ?? '#64748b' }};">
                <div class="bt-card-body">
                    <div class="d-flex align-items-start gap-2">
                        <div style="flex:1;min-width:0;">
                            <div style="font-size:.88rem;font-weight:700;color:#1e293b;">
                                {{ $curso->nombre }}
                                @if($curso->codigo)
                                <span style="font-size:.68rem;color:#94a3b8;font-weight:600;">[{{ $curso->codigo }}]</span>
                                @endif
                            </div>
                            @if($curso->descripcion)
                            <div style="font-size:.75rem;color:#6b7280;margin-top:.2rem;">{{ Str::limit($curso->descripcion, 80) }}</div>
                            @endif
                            <div style="font-size:.72rem;color:#64748b;margin-top:.35rem;display:flex;gap:.75rem;flex-wrap:wrap;">
                                @if($curso->duracion_horas)
                                <span><i class="bi bi-clock me-1"></i>{{ $curso->duracion_horas }}h</span>
                                @endif
                                <span><i class="bi bi-layers me-1"></i>{{ $curso->modulos_count }} módulo(s)</span>
                            </div>
                        </div>
                        <span class="{{ $curso->activo ? 'bt-badge-activo' : 'bt-badge-inactivo' }}">
                            {{ $curso->activo ? 'Activo' : 'Inactivo' }}
                        </span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <div class="d-flex gap-1">
                            <button type="button" class="btn btn-sm btn-outline-secondary"
                                    style="font-size:.72rem;border-radius:6px;"
                                    data-bs-toggle="modal"
                                    data-bs-target="#modalEditarCurso{{ $curso->id }}">
                                <i class="bi bi-pencil me-1"></i>Editar
                            </button>
                            <form method="POST" action="{{ route('admin.bachillerato-tecnico.cursos.toggle', $curso) }}" style="display:inline;">
                                @csrf @method('PATCH')
                                <button type="submit" class="btn btn-sm {{ $curso->activo ? 'btn-outline-warning' : 'btn-outline-success' }}"
                                        style="font-size:.72rem;border-radius:6px;">
                                    {{ $curso->activo ? 'Desactivar' : 'Activar' }}
                                </button>
                            </form>
                        </div>
                        <form method="POST" action="{{ route('admin.bachillerato-tecnico.cursos.destroy', $curso) }}"
                              onsubmit="return confirm('¿Eliminar el curso {{ $curso->nombre }}? Sus módulos también serán eliminados.')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger" style="font-size:.72rem;border-radius:6px;">
                                <i class="bi bi-trash3"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        {{-- Modal editar curso --}}
        <div class="modal fade" id="modalEditarCurso{{ $curso->id }}" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content" style="border-radius:14px;">
                    <form method="POST" action="{{ route('admin.bachillerato-tecnico.cursos.update', $curso) }}">
                        @csrf @method('PUT')
                        <div class="modal-header" style="background:var(--primary);color:#fff;border-radius:14px 14px 0 0;">
                            <h6 class="modal-title fw-bold"><i class="bi bi-pencil me-2"></i>Editar Curso</h6>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label fw-semibold" style="font-size:.83rem;">Área Técnica <span class="text-danger">*</span></label>
                                    <select name="area_tecnica_id" class="form-select" required>
                                        @foreach($areas as $a)
                                        <option value="{{ $a->id }}" {{ $curso->area_tecnica_id == $a->id ? 'selected' : '' }}>
                                            {{ $a->nombre }}
                                        </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-8">
                                    <label class="form-label fw-semibold" style="font-size:.83rem;">Nombre <span class="text-danger">*</span></label>
                                    <input type="text" name="nombre" class="form-control" value="{{ $curso->nombre }}" required maxlength="150">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold" style="font-size:.83rem;">Código</label>
                                    <input type="text" name="codigo" class="form-control" value="{{ $curso->codigo }}" maxlength="30">
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-semibold" style="font-size:.83rem;">Descripción</label>
                                    <textarea name="descripcion" class="form-control" rows="2">{{ $curso->descripcion }}</textarea>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" style="font-size:.83rem;">Horas totales</label>
                                    <input type="number" name="duracion_horas" class="form-control" value="{{ $curso->duracion_horas }}" min="1">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" style="font-size:.83rem;">Orden</label>
                                    <input type="number" name="orden" class="form-control" value="{{ $curso->orden }}" min="0">
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-sm fw-semibold" style="background:var(--primary);color:#fff;border-radius:7px;">
                                <i class="bi bi-floppy me-1"></i>Guardar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        @endforeach
    </div>
</div>
@endforeach
@endif
</div>{{-- /tab-cursos --}}

{{-- ══════════════════════════════════════════════════════════════════════
     TAB 3 — MÓDULOS FORMATIVOS
══════════════════════════════════════════════════════════════════════ --}}
<div id="tab-modulos" style="display:none;">
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    @if($cursos->isEmpty())
    <div class="alert alert-warning border-0 w-100" style="border-radius:10px;font-size:.83rem;">
        <i class="bi bi-exclamation-triangle me-2"></i>Primero crea al menos un Curso Técnico antes de agregar módulos.
    </div>
    @else
    <span></span>
    <button type="button" class="btn btn-sm fw-semibold"
            style="background:var(--primary);color:#fff;border-radius:8px;padding:.45rem 1rem;"
            data-bs-toggle="modal" data-bs-target="#modalNuevoModulo">
        <i class="bi bi-plus-lg me-1"></i>Nuevo Módulo
    </button>
    @endif
</div>

@if($modulos->isEmpty())
<div class="empty-bt">
    <i class="bi bi-layers"></i>
    <h6 class="fw-semibold mb-1" style="color:#6b7280;">Sin módulos formativos</h6>
    <p style="font-size:.83rem;">Crea el primer módulo formativo dentro de un curso técnico.</p>
</div>
@else
@php $modulosPorCurso = $modulos->groupBy('curso_tecnico_id'); @endphp
@foreach($modulosPorCurso as $cursoId => $modulosGrupo)
@php $cursoRel = $modulosGrupo->first()->curso; @endphp
<div class="mb-4">
    <div style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#64748b;
                padding:.4rem .75rem;background:#f1f5f9;border-radius:8px;margin-bottom:.6rem;
                display:flex;align-items:center;gap:.5rem;">
        <i class="bi bi-journal-bookmark" style="color:#2563eb;"></i>
        {{ $cursoRel?->nombre ?? '—' }}
        @if($cursoRel?->area)
        <span style="font-size:.65rem;color:#94a3b8;font-style:italic;">— {{ $cursoRel->area->nombre }}</span>
        @endif
    </div>
    <div class="row g-2">
        @foreach($modulosGrupo as $modulo)
        <div class="col-md-6 col-xl-4">
            <div class="bt-card" style="border-left:4px solid #7c3aed;">
                <div class="bt-card-body">
                    <div class="d-flex align-items-start gap-2">
                        <div style="flex:1;min-width:0;">
                            <div style="font-size:.88rem;font-weight:700;color:#1e293b;">
                                {{ $modulo->nombre }}
                                @if($modulo->codigo)
                                <span style="font-size:.68rem;color:#94a3b8;font-weight:600;">[{{ $modulo->codigo }}]</span>
                                @endif
                            </div>
                            @if($modulo->descripcion)
                            <div style="font-size:.75rem;color:#6b7280;margin-top:.2rem;">{{ Str::limit($modulo->descripcion, 80) }}</div>
                            @endif
                            <div style="font-size:.72rem;color:#64748b;margin-top:.35rem;display:flex;gap:.75rem;flex-wrap:wrap;">
                                @if($modulo->duracion_horas)
                                <span><i class="bi bi-clock me-1"></i>{{ $modulo->duracion_horas }}h</span>
                                @endif
                                @if($modulo->creditos)
                                <span><i class="bi bi-award me-1"></i>{{ $modulo->creditos }} créditos</span>
                                @endif
                                @if($modulo->orden)
                                <span><i class="bi bi-sort-numeric-up me-1"></i>Orden {{ $modulo->orden }}</span>
                                @endif
                            </div>
                        </div>
                        <span class="{{ $modulo->activo ? 'bt-badge-activo' : 'bt-badge-inactivo' }}">
                            {{ $modulo->activo ? 'Activo' : 'Inactivo' }}
                        </span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <div class="d-flex gap-1">
                            <button type="button" class="btn btn-sm btn-outline-secondary"
                                    style="font-size:.72rem;border-radius:6px;"
                                    data-bs-toggle="modal"
                                    data-bs-target="#modalEditarModulo{{ $modulo->id }}">
                                <i class="bi bi-pencil me-1"></i>Editar
                            </button>
                            <form method="POST" action="{{ route('admin.bachillerato-tecnico.modulos.toggle', $modulo) }}" style="display:inline;">
                                @csrf @method('PATCH')
                                <button type="submit" class="btn btn-sm {{ $modulo->activo ? 'btn-outline-warning' : 'btn-outline-success' }}"
                                        style="font-size:.72rem;border-radius:6px;">
                                    {{ $modulo->activo ? 'Desactivar' : 'Activar' }}
                                </button>
                            </form>
                        </div>
                        <form method="POST" action="{{ route('admin.bachillerato-tecnico.modulos.destroy', $modulo) }}"
                              onsubmit="return confirm('¿Eliminar el módulo {{ $modulo->nombre }}?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger" style="font-size:.72rem;border-radius:6px;">
                                <i class="bi bi-trash3"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        {{-- Modal editar módulo --}}
        <div class="modal fade" id="modalEditarModulo{{ $modulo->id }}" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content" style="border-radius:14px;">
                    <form method="POST" action="{{ route('admin.bachillerato-tecnico.modulos.update', $modulo) }}">
                        @csrf @method('PUT')
                        <div class="modal-header" style="background:#7c3aed;color:#fff;border-radius:14px 14px 0 0;">
                            <h6 class="modal-title fw-bold"><i class="bi bi-pencil me-2"></i>Editar Módulo Formativo</h6>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label fw-semibold" style="font-size:.83rem;">Curso Técnico <span class="text-danger">*</span></label>
                                    <select name="curso_tecnico_id" class="form-select" required>
                                        @foreach($cursos->groupBy('area_tecnica_id') as $aId => $curGroup)
                                        @php $areaLabel = $curGroup->first()->area?->nombre ?? '—'; @endphp
                                        <optgroup label="{{ $areaLabel }}">
                                            @foreach($curGroup as $c)
                                            <option value="{{ $c->id }}" {{ $modulo->curso_tecnico_id == $c->id ? 'selected' : '' }}>
                                                {{ $c->nombre }}
                                            </option>
                                            @endforeach
                                        </optgroup>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-8">
                                    <label class="form-label fw-semibold" style="font-size:.83rem;">Nombre <span class="text-danger">*</span></label>
                                    <input type="text" name="nombre" class="form-control" value="{{ $modulo->nombre }}" required maxlength="150">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold" style="font-size:.83rem;">Código</label>
                                    <input type="text" name="codigo" class="form-control" value="{{ $modulo->codigo }}" maxlength="30">
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-semibold" style="font-size:.83rem;">Descripción</label>
                                    <textarea name="descripcion" class="form-control" rows="2">{{ $modulo->descripcion }}</textarea>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold" style="font-size:.83rem;">Horas</label>
                                    <input type="number" name="duracion_horas" class="form-control" value="{{ $modulo->duracion_horas }}" min="1">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold" style="font-size:.83rem;">Créditos</label>
                                    <input type="number" name="creditos" class="form-control" value="{{ $modulo->creditos }}" min="0" step="0.5">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold" style="font-size:.83rem;">Orden</label>
                                    <input type="number" name="orden" class="form-control" value="{{ $modulo->orden }}" min="0">
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-sm fw-semibold" style="background:#7c3aed;color:#fff;border-radius:7px;">
                                <i class="bi bi-floppy me-1"></i>Guardar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        @endforeach
    </div>
</div>
@endforeach
@endif
</div>{{-- /tab-modulos --}}

{{-- ══ Modales de Creación ══════════════════════════════════════════════════ --}}

{{-- Modal Nueva Área --}}
<div class="modal fade" id="modalNuevaArea" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:14px;">
            <form method="POST" action="{{ route('admin.bachillerato-tecnico.areas.store') }}">
                @csrf
                <div class="modal-header" style="background:var(--primary);color:#fff;border-radius:14px 14px 0 0;">
                    <h6 class="modal-title fw-bold"><i class="bi bi-plus-circle me-2"></i>Nueva Área Técnica</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label fw-semibold" style="font-size:.83rem;">Nombre <span class="text-danger">*</span></label>
                            <input type="text" name="nombre" class="form-control" placeholder="Ej: Informática y Comunicaciones" required maxlength="100">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold" style="font-size:.83rem;">Código</label>
                            <input type="text" name="codigo" class="form-control" placeholder="INF" maxlength="20">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold" style="font-size:.83rem;">Descripción <span class="text-muted fw-normal">(opcional)</span></label>
                            <textarea name="descripcion" class="form-control" rows="2" placeholder="Descripción del área técnica..."></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size:.83rem;">Color</label>
                            <div class="d-flex align-items-center gap-2">
                                <input type="color" name="color" class="form-control form-control-color"
                                       value="#1e3a6e" style="width:48px;height:36px;padding:2px;border-radius:6px;">
                                <span style="font-size:.78rem;color:#6b7280;">Color del encabezado</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size:.83rem;">Orden</label>
                            <input type="number" name="orden" class="form-control" value="0" min="0">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-sm fw-semibold" style="background:var(--primary);color:#fff;border-radius:7px;">
                        <i class="bi bi-plus-lg me-1"></i>Crear Área
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modal Nuevo Curso --}}
<div class="modal fade" id="modalNuevoCurso" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:14px;">
            <form method="POST" action="{{ route('admin.bachillerato-tecnico.cursos.store') }}">
                @csrf
                <div class="modal-header" style="background:#0891b2;color:#fff;border-radius:14px 14px 0 0;">
                    <h6 class="modal-title fw-bold"><i class="bi bi-plus-circle me-2"></i>Nuevo Curso Técnico</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-semibold" style="font-size:.83rem;">Área Técnica <span class="text-danger">*</span></label>
                            <select name="area_tecnica_id" class="form-select" required>
                                <option value="">— Seleccionar área —</option>
                                @foreach($areas as $a)
                                <option value="{{ $a->id }}">{{ $a->nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label fw-semibold" style="font-size:.83rem;">Nombre <span class="text-danger">*</span></label>
                            <input type="text" name="nombre" class="form-control" placeholder="Ej: Técnico en Redes y Comunicaciones" required maxlength="150">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold" style="font-size:.83rem;">Código</label>
                            <input type="text" name="codigo" class="form-control" placeholder="TEC-RED" maxlength="30">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold" style="font-size:.83rem;">Descripción</label>
                            <textarea name="descripcion" class="form-control" rows="2" placeholder="Descripción del curso técnico..."></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size:.83rem;">Horas totales</label>
                            <input type="number" name="duracion_horas" class="form-control" min="1" placeholder="800">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size:.83rem;">Orden</label>
                            <input type="number" name="orden" class="form-control" value="0" min="0">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-sm fw-semibold" style="background:#0891b2;color:#fff;border-radius:7px;">
                        <i class="bi bi-plus-lg me-1"></i>Crear Curso
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modal Nuevo Módulo --}}
<div class="modal fade" id="modalNuevoModulo" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:14px;">
            <form method="POST" action="{{ route('admin.bachillerato-tecnico.modulos.store') }}">
                @csrf
                <div class="modal-header" style="background:#7c3aed;color:#fff;border-radius:14px 14px 0 0;">
                    <h6 class="modal-title fw-bold"><i class="bi bi-plus-circle me-2"></i>Nuevo Módulo Formativo</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-semibold" style="font-size:.83rem;">Curso Técnico <span class="text-danger">*</span></label>
                            <select name="curso_tecnico_id" class="form-select" required>
                                <option value="">— Seleccionar curso —</option>
                                @foreach($cursos->groupBy('area_tecnica_id') as $aId => $curGroup)
                                @php $areaLabel = $curGroup->first()->area?->nombre ?? '—'; @endphp
                                <optgroup label="{{ $areaLabel }}">
                                    @foreach($curGroup as $c)
                                    <option value="{{ $c->id }}">{{ $c->nombre }}</option>
                                    @endforeach
                                </optgroup>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label fw-semibold" style="font-size:.83rem;">Nombre <span class="text-danger">*</span></label>
                            <input type="text" name="nombre" class="form-control" placeholder="Ej: Módulo 1: Fundamentos de redes" required maxlength="150">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold" style="font-size:.83rem;">Código</label>
                            <input type="text" name="codigo" class="form-control" placeholder="MOD-01" maxlength="30">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold" style="font-size:.83rem;">Descripción</label>
                            <textarea name="descripcion" class="form-control" rows="2" placeholder="Descripción del módulo formativo..."></textarea>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold" style="font-size:.83rem;">Horas</label>
                            <input type="number" name="duracion_horas" class="form-control" min="1" placeholder="120">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold" style="font-size:.83rem;">Créditos</label>
                            <input type="number" name="creditos" class="form-control" min="0" step="0.5" placeholder="6">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold" style="font-size:.83rem;">Orden</label>
                            <input type="number" name="orden" class="form-control" value="0" min="0">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-sm fw-semibold" style="background:#7c3aed;color:#fff;border-radius:7px;">
                        <i class="bi bi-plus-lg me-1"></i>Crear Módulo
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
// Abrir en el tab correcto según URL param
(function () {
    const params = new URLSearchParams(window.location.search);
    const tab = params.get('tab');
    if (tab && ['areas', 'cursos', 'modulos'].includes(tab)) {
        switchTab(tab);
    }
})();

function switchTab(tab) {
    ['areas', 'cursos', 'modulos'].forEach(function(t) {
        document.getElementById('tab-' + t).style.display = t === tab ? '' : 'none';
        document.querySelectorAll('[data-tab="' + t + '"]').forEach(function(btn) {
            btn.classList.toggle('active', t === tab);
        });
    });
}
</script>
@endpush
