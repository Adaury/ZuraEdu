@extends('layouts.admin')

@section('page-title', 'Nueva Asignación')

@push('styles')
<style>
    .form-card {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        box-shadow: 0 2px 12px rgba(30,58,110,.06);
    }
    .form-section-title {
        font-size: .7rem;
        font-weight: 700;
        letter-spacing: .1em;
        text-transform: uppercase;
        color: var(--primary);
        margin-bottom: 1rem;
        padding-bottom: .4rem;
        border-bottom: 1px solid #e5e7eb;
    }
    .form-label {
        font-size: .8rem;
        font-weight: 600;
        color: #374151;
        margin-bottom: .3rem;
    }
    .form-control, .form-select {
        border-radius: 8px;
        border: 1px solid #d1d5db;
        font-size: .875rem;
        padding: .5rem .75rem;
        transition: border-color .15s, box-shadow .15s;
    }
    .form-control:focus, .form-select:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(30,58,110,.1);
    }
    .form-control.is-invalid, .form-select.is-invalid {
        border-color: var(--secondary);
    }
    .invalid-feedback { font-size: .75rem; }
    .form-check-input:checked {
        background-color: var(--primary);
        border-color: var(--primary);
    }

    /* Asignatura colour cards */
    .asignatura-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
        gap: .5rem;
    }
    .asignatura-option {
        position: relative;
        cursor: pointer;
    }
    .asignatura-option input[type="radio"] {
        position: absolute;
        opacity: 0;
        width: 0;
        height: 0;
    }
    .asignatura-label {
        display: flex;
        align-items: center;
        gap: .55rem;
        border: 1.5px solid #e5e7eb;
        border-radius: 9px;
        padding: .6rem .85rem;
        cursor: pointer;
        transition: border-color .15s, background .15s, box-shadow .15s;
        background: #fff;
    }
    .asignatura-option input[type="radio"]:checked + .asignatura-label {
        border-color: var(--primary);
        background: #f0f4ff;
        box-shadow: 0 0 0 3px rgba(30,58,110,.08);
    }
    .asignatura-label:hover {
        border-color: #a5b4fc;
        background: #fafbff;
    }
    .asignatura-color-dot {
        width: 12px; height: 12px;
        border-radius: 3px;
        flex-shrink: 0;
    }
    .asignatura-name {
        font-size: .78rem;
        font-weight: 600;
        color: #1e293b;
        line-height: 1.2;
    }
    .asignatura-code {
        font-size: .65rem;
        color: #9ca3af;
    }
    .asignatura-check {
        margin-left: auto;
        color: var(--primary);
        font-size: .9rem;
        display: none;
    }
    .asignatura-option input[type="radio"]:checked + .asignatura-label .asignatura-check {
        display: inline;
    }
    /* Fallback: also a select for small screens */
    .asignatura-select-fallback { display: none; }
    .asig-create-row:hover { background:#f8faff; }
    .asig-create-row:last-child { border-bottom:none !important; }
    .asig-create-row:has(input:checked) { background:#eff6ff; }

    .year-pill {
        display: inline-flex;
        align-items: center;
        gap: .4rem;
        background: #f0f4f8;
        border: 1px solid #dde3ef;
        border-radius: 8px;
        padding: .45rem .85rem;
        font-size: .85rem;
        font-weight: 600;
        color: var(--primary);
    }

    [data-theme="dark"] .form-card { background: #1e293b; border-color: #334155; }
    [data-theme="dark"] .form-section-title { border-bottom-color: #334155; }
</style>
@endpush

@section('content')

{{-- Breadcrumb --}}
<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb" style="font-size:.8rem;">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="text-decoration-none">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.asignaciones.index') }}" class="text-decoration-none">Asignaciones</a></li>
        <li class="breadcrumb-item active">Nueva Asignación</li>
    </ol>
</nav>

<div class="d-flex align-items-center mb-4">
    <div>
        <h1 class="h4 fw-bold mb-0" style="color:var(--primary);">
            <i class="bi bi-plus-circle me-2"></i>Nueva Asignación
        </h1>
        <p class="text-muted mb-0 mt-1" style="font-size:.82rem;">Asignar un docente a una asignatura y grupo</p>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-12 col-lg-9 col-xl-8">
        <div class="form-card p-4">

            @if($errors->any())
                <div class="alert alert-danger border-0 mb-4" style="border-radius:10px;font-size:.85rem;">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <strong>Por favor corrige los siguientes errores:</strong>
                    <ul class="mb-0 mt-1 ps-3">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('admin.asignaciones.store') }}" method="POST">
                @csrf

                {{-- Año Escolar (auto) --}}
                @if($schoolYear)
                    <input type="hidden" name="school_year_id" value="{{ $schoolYear->id }}">
                    <div class="mb-4">
                        <div class="form-section-title">
                            <i class="bi bi-calendar3 me-1"></i>Año Escolar
                        </div>
                        <div class="year-pill">
                            <i class="bi bi-calendar-check"></i>
                            {{ $schoolYear->nombre }}
                            <span style="font-size:.7rem;opacity:.7;font-weight:400;">(Año actual)</span>
                        </div>
                    </div>
                @endif

                {{-- Grupo y Docente (PRIMERO para que el filtro de ciclo funcione) --}}
                <div class="form-section-title">
                    <i class="bi bi-people me-1"></i>Grupo y Docente
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label" for="grupo_id">
                            Grupo <span class="text-danger">*</span>
                        </label>
                        <select name="grupo_id" id="grupo_id"
                                class="form-select @error('grupo_id') is-invalid @enderror" required
                                onchange="actualizarCicloDesdeGrupo(this)">
                            <option value="">— Seleccionar grupo —</option>
                            @php
                                $niveles = [1=>'1ro',2=>'2do',3=>'3ro',4=>'4to',5=>'5to',6=>'6to'];
                                $gruposPorCiclo = $grupos->groupBy(fn($g) => $g->grado->ciclo ?? 'primer_ciclo');
                            @endphp
                            @foreach(['primer_ciclo' => '— Primer Ciclo (1ro – 3ro) —', 'segundo_ciclo' => '— Segundo Ciclo (4to – 6to) —'] as $cicloKey => $cicloLabel)
                                @if($gruposPorCiclo->has($cicloKey))
                                <optgroup label="{{ $cicloLabel }}">
                                    @foreach($gruposPorCiclo[$cicloKey] as $g)
                                        @php
                                            $pref   = $niveles[$g->grado->nivel ?? 0] ?? ($g->grado->nivel.'mo');
                                            $gLabel = $pref . ' ' . ($g->seccion->nombre ?? '');
                                        @endphp
                                        <option value="{{ $g->id }}"
                                                data-ciclo="{{ $cicloKey }}"
                                                {{ old('grupo_id') == $g->id ? 'selected' : '' }}>
                                            {{ $gLabel }}@if($g->aula) — Aula {{ $g->aula }}@endif
                                        </option>
                                    @endforeach
                                </optgroup>
                                @endif
                            @endforeach
                        </select>
                        @error('grupo_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="docente_id">
                            Docente <span class="text-danger">*</span>
                        </label>
                        <select name="docente_id" id="docente_id"
                                class="form-select @error('docente_id') is-invalid @enderror" required>
                            <option value="">— Seleccionar docente —</option>
                            @foreach($docentes as $docente)
                                <option value="{{ $docente->id }}"
                                    {{ (old('docente_id', $preDocenteId ?? '')) == $docente->id ? 'selected' : '' }}>
                                    {{ $docente->nombre_completo }}
                                    @if($docente->especialidad) — {{ $docente->especialidad }} @endif
                                </option>
                            @endforeach
                        </select>
                        @error('docente_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                {{-- Asignaturas con checkboxes (DESPUÉS de grupo para que el filtro de ciclo ya esté activo) --}}
                <div class="mb-4">
                    <div class="form-section-title">
                        <i class="bi bi-book me-1"></i>Asignaturas
                    </div>
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <label class="form-label mb-0">
                            Seleccionar asignaturas <span class="text-danger">*</span>
                            <span class="text-muted fw-normal" style="font-size:.75rem;">(puedes elegir varias)</span>
                        </label>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-link p-0 text-primary"
                                    style="font-size:.72rem;font-weight:700;text-decoration:none;"
                                    onclick="toggleAllCreate(true)">Seleccionar todas</button>
                            <span style="color:#d1d5db;font-size:.72rem;">|</span>
                            <button type="button" class="btn btn-link p-0 text-secondary"
                                    style="font-size:.72rem;font-weight:700;text-decoration:none;"
                                    onclick="toggleAllCreate(false)">Limpiar</button>
                        </div>
                    </div>

                    @if($asignaturas->isNotEmpty())
                    {{-- Filtro rápido por área --}}
                    <div class="d-flex gap-2 mb-2">
                        <button type="button" class="btn btn-sm btn-outline-secondary active" id="filtro-todas"
                                onclick="filtrarAsignaturas('todas')" style="font-size:.73rem;border-radius:6px;">Todas</button>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="filtro-academica"
                                onclick="filtrarAsignaturas('academica')" style="font-size:.73rem;border-radius:6px;">
                            <i class="bi bi-book me-1"></i>Académicas
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-warning" id="filtro-tecnica"
                                onclick="filtrarAsignaturas('tecnica')" style="font-size:.73rem;border-radius:6px;">
                            <i class="bi bi-tools me-1"></i>Técnicas
                        </button>
                    </div>
                        <div id="asigCreateList"
                             style="max-height:260px;overflow-y:auto;border:1px solid #e5e7eb;border-radius:8px;background:#fff;">
                            @foreach($asignaturas as $asig)
                            <label class="asig-create-row d-flex align-items-center gap-2 px-3 py-2"
                                   data-area="{{ $asig->area }}"
                                   style="cursor:pointer;border-bottom:1px solid #f3f4f6;margin:0;font-size:.86rem;">
                                <input type="checkbox" name="asignaturas[]" value="{{ $asig->id }}"
                                       class="form-check-input asig-create-chk flex-shrink-0"
                                       style="width:16px;height:16px;cursor:pointer;"
                                       {{ (is_array(old('asignaturas')) && in_array($asig->id, old('asignaturas'))) || (!old('asignaturas') && isset($preAsignaturaId) && $preAsignaturaId == $asig->id) ? 'checked' : '' }}
                                       onchange="actualizarContadorCreate()">
                                <span style="width:12px;height:12px;border-radius:3px;background:{{ $asig->color ?? '#6b7280' }};flex-shrink:0;display:inline-block;"></span>
                                <span class="fw-semibold">{{ $asig->nombre }}</span>
                                <span class="ms-auto badge" style="font-size:.63rem;{{ $asig->area === 'tecnica' ? 'background:#fef3c7;color:#92400e;' : 'background:#dbeafe;color:#1e40af;' }}">
                                    {{ $asig->area === 'tecnica' ? 'Técnica' : 'Académica' }}
                                </span>
                            </label>
                            @endforeach
                        </div>
                        <div class="mt-1">
                            <span id="cntAsigCreate" style="font-size:.75rem;color:#6b7280;">0 seleccionadas</span>
                        </div>
                    @else
                        <p class="text-muted" style="font-size:.84rem;">No hay asignaturas disponibles.</p>
                    @endif

                    @error('asignaturas')
                        <div class="text-danger mt-1" style="font-size:.75rem;">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Área y Tipo de Evaluación --}}
                <div class="form-section-title">
                    <i class="bi bi-diagram-3 me-1"></i>Área y Tipo de Evaluación
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label" for="area">
                            Área <span class="text-danger">*</span>
                        </label>
                        <select name="area" id="area"
                                class="form-select @error('area') is-invalid @enderror" required
                                onchange="actualizarTipoEval()">
                            <option value="academica" {{ old('area','academica') === 'academica' ? 'selected' : '' }}>
                                Académica
                            </option>
                            <option value="tecnica" {{ old('area') === 'tecnica' ? 'selected' : '' }}>
                                Técnica
                            </option>
                        </select>
                        @error('area')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="text-muted mt-1" style="font-size:.75rem;">
                            Área Técnica permite evaluación por Resultados de Aprendizaje (RA).
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="tipo_evaluacion">
                            Tipo de Evaluación <span class="text-danger">*</span>
                        </label>
                        <select name="tipo_evaluacion" id="tipo_evaluacion"
                                class="form-select @error('tipo_evaluacion') is-invalid @enderror" required>
                            <option value="indicadores_logro" {{ old('tipo_evaluacion','indicadores_logro') === 'indicadores_logro' ? 'selected' : '' }}>
                                Indicadores de Logro (Área Académica)
                            </option>
                            <option value="competencias" {{ old('tipo_evaluacion') === 'competencias' ? 'selected' : '' }}
                                    id="opt-ra">
                                Por Competencia (Área Técnica)
                            </option>
                            <option value="componentes" {{ old('tipo_evaluacion') === 'componentes' ? 'selected' : '' }}>
                                Por Componentes (Tareas, Prácticas, Examen…)
                            </option>
                            <option value="ra" {{ old('tipo_evaluacion') === 'ra' ? 'selected' : '' }}>
                                Por Resultados de Aprendizaje (RA)
                            </option>
                        </select>
                        @error('tipo_evaluacion')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                {{-- Estado --}}
                <div class="form-section-title">
                    <i class="bi bi-toggle-on me-1"></i>Estado
                </div>

                <div class="mb-4">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch"
                               name="activo" id="activo" value="1"
                               {{ old('activo', '1') == '1' ? 'checked' : '' }}>
                        <label class="form-check-label fw-semibold" for="activo" style="font-size:.85rem;">
                            Asignación activa
                        </label>
                        <div class="text-muted mt-1" style="font-size:.75rem;">
                            Solo las asignaciones activas permiten el registro de calificaciones.
                        </div>
                    </div>
                </div>

                {{-- Actions --}}
                <div class="d-flex gap-2 pt-2 border-top">
                    <button type="submit" id="btnSubmitCreate" class="btn fw-semibold" disabled
                            style="background:var(--primary);color:#fff;border-radius:8px;padding:.5rem 1.4rem;">
                        <i class="bi bi-check-lg me-1"></i>
                        <span id="btnCreateLabel">Selecciona al menos una asignatura</span>
                    </button>
                    <a href="{{ route('admin.asignaciones.index') }}"
                       class="btn fw-semibold"
                       style="background:#f3f4f6;color:#374151;border:1px solid #e5e7eb;border-radius:8px;padding:.5rem 1.2rem;">
                        Cancelar
                    </a>
                </div>
            </form>

        </div>
    </div>
</div>

@push('scripts')
<script>
function actualizarContadorCreate() {
    const rows    = Array.from(document.querySelectorAll('.asig-create-row'));
    const visible = rows.filter(r => r.style.display !== 'none');
    const sel     = visible.filter(r => r.querySelector('.asig-create-chk')?.checked).length;
    const totalSel = rows.filter(r => r.querySelector('.asig-create-chk')?.checked).length;
    const cnt    = document.getElementById('cntAsigCreate');
    const btn    = document.getElementById('btnSubmitCreate');
    const lbl    = document.getElementById('btnCreateLabel');
    if (cnt) cnt.textContent = sel + ' de ' + visible.length + ' seleccionada' + (sel !== 1 ? 's' : '');
    if (btn) btn.disabled = totalSel === 0;
    if (lbl) lbl.textContent = totalSel === 0
        ? 'Selecciona al menos una asignatura'
        : (totalSel === 1 ? 'Crear 1 asignación' : 'Crear ' + totalSel + ' asignaciones');
}

function toggleAllCreate(estado) {
    document.querySelectorAll('.asig-create-row').forEach(row => {
        if (row.style.display !== 'none') {
            const cb = row.querySelector('.asig-create-chk');
            if (cb) cb.checked = estado;
        }
    });
    actualizarContadorCreate();
}

// Si venía con old() values ya marcados, actualizar el contador
document.addEventListener('DOMContentLoaded', function() {
    actualizarContadorCreate();
});

function filtrarAsignaturas(area) {
    document.querySelectorAll('.asig-create-row').forEach(row => {
        row.style.display = (area === 'todas' || row.dataset.area === area) ? '' : 'none';
    });
    // update active button
    ['todas','academica','tecnica'].forEach(a => {
        const btn = document.getElementById('filtro-' + a);
        if (btn) btn.classList.toggle('active', a === area);
    });
}

function actualizarAreaDesdeAsignaturas() {
    // Si todas las seleccionadas son técnicas → poner área técnica
    const checks = Array.from(document.querySelectorAll('.asig-create-chk:checked'));
    if (checks.length === 0) return;
    const filas  = checks.map(c => c.closest('[data-area]'));
    const areas  = [...new Set(filas.map(f => f?.dataset.area))].filter(Boolean);
    const areaEl = document.getElementById('area');
    if (areas.length === 1 && areaEl) areaEl.value = areas[0];
}

// Escuchar cambios en checkboxes para auto-set área
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('asigCreateList')?.addEventListener('change', function(e) {
        if (e.target.classList.contains('asig-create-chk')) {
            actualizarContadorCreate();
            actualizarAreaDesdeAsignaturas();
        }
    });
});

function actualizarCicloDesdeGrupo(sel) {
    const opt   = sel.options[sel.selectedIndex];
    const ciclo = opt?.dataset.ciclo ?? '';

    // Auto-set área según ciclo
    const areaEl = document.getElementById('area');
    if (areaEl && ciclo) {
        areaEl.value = ciclo === 'segundo_ciclo' ? 'tecnica' : 'academica';
    }

    // Indicador visual del ciclo
    let badge = document.getElementById('ciclo-badge');
    if (!badge) {
        badge = document.createElement('div');
        badge.id = 'ciclo-badge';
        badge.style = 'font-size:.73rem;margin-top:.35rem;';
        sel.parentElement.appendChild(badge);
    }
    if (ciclo === 'primer_ciclo') {
        badge.innerHTML = '<span style="background:#dbeafe;color:#1e40af;border-radius:5px;padding:.1rem .5rem;font-weight:600;"><i class="bi bi-1-circle me-1"></i>Primer Ciclo — mostrando materias académicas</span>';
        // Primer ciclo → mostrar solo académicas, ocultar técnicas
        filtrarAsignaturas('academica');
    } else if (ciclo === 'segundo_ciclo') {
        badge.innerHTML = '<span style="background:#d1fae5;color:#065f46;border-radius:5px;padding:.1rem .5rem;font-weight:600;"><i class="bi bi-2-circle me-1"></i>Segundo Ciclo — todas las materias disponibles</span>';
        // Segundo ciclo → mostrar todas
        filtrarAsignaturas('todas');
    } else {
        badge.innerHTML = '';
    }
}

function actualizarTipoEval() {}
document.addEventListener('DOMContentLoaded', function() {
    actualizarTipoEval();
    // Si viene con old() pre-seleccionado, actualizar badge y filtro
    const grupoSel = document.getElementById('grupo_id');
    if (grupoSel?.value) actualizarCicloDesdeGrupo(grupoSel);

    // Antes de enviar, desmarcar checkboxes de filas ocultas
    document.querySelector('form')?.addEventListener('submit', function() {
        document.querySelectorAll('.asig-create-row').forEach(function(row) {
            if (row.style.display === 'none') {
                const cb = row.querySelector('.asig-create-chk');
                if (cb) cb.checked = false;
            }
        });
    });
});
</script>
@endpush

@endsection
