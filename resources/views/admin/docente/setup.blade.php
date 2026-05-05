@extends($layout ?? 'layouts.admin')
@section('page-title', 'Configurar Mis Materias')

@push('styles')
<style>
/* ── Setup: dark mode overrides ─────────────────────────── */
[data-theme="dark"] .setup-card {
    background: #1e293b !important;
    border-color: #334155 !important;
}
[data-theme="dark"] .setup-card .card-header {
    background: #1e3a8a !important;
}
[data-theme="dark"] .setup-section-header {
    background: #162032 !important;
    color: #93c5fd !important;
}
[data-theme="dark"] .setup-grupo-header {
    color: #cbd5e1 !important;
}
[data-theme="dark"] .materia-check-item {
    background: #1e293b !important;
    border-color: #334155 !important;
    color: #cbd5e1 !important;
}
[data-theme="dark"] .materia-check-item:hover {
    background: #253347 !important;
}
[data-theme="dark"] .materia-check-item .fw-medium {
    color: #e2e8f0 !important;
}
[data-theme="dark"] .setup-guia-wrap {
    background: #1e293b !important;
    border-color: #334155 !important;
}
[data-theme="dark"] .setup-guia-wrap .form-check-label {
    color: #e2e8f0 !important;
}
[data-theme="dark"] .setup-guia-wrap .text-muted {
    color: #64748b !important;
}
[data-theme="dark"] .alert-info {
    background: #0c1a2e !important;
    border-color: #1e3a5f !important;
    color: #93c5fd !important;
}
[data-theme="dark"] .border-bottom {
    border-color: #334155 !important;
}
[data-theme="dark"] .border-top {
    border-color: #334155 !important;
}
</style>
@endpush

@section('content')

<!-- Header -->
<div class="d-flex align-items-center mb-4">
    <div>
        @if(isset($docente) && $docente && !auth()->user()->hasRole('Docente'))
            <h4 class="fw-bold mb-0" style="color:var(--primary)">
                <i class="bi bi-person-gear me-2"></i>Asignar Materias — {{ $docente->nombre_completo }}
            </h4>
            <p class="text-muted mb-0 mt-1" style="font-size:.85rem;">
                Selecciona los grupos y materias que imparte este docente.
            </p>
        @else
            <h4 class="fw-bold mb-0" style="color:var(--primary)">
                <i class="bi bi-person-gear me-2"></i>Configuración Inicial — Mis Materias
            </h4>
            <p class="text-muted mb-0 mt-1" style="font-size:.85rem;">
                Indica los grupos y materias que impartes este año escolar.
            </p>
        @endif
    </div>
    <span class="ms-auto badge rounded-pill px-3 py-2" style="background:var(--accent-light);color:#92400e;font-size:.8rem;border:1px solid #fcd34d;">
        <i class="bi bi-calendar2-check me-1"></i>{{ $schoolYear->nombre }}
    </span>
</div>

<!-- Alert si hay mensajes -->
@if(session('info'))
<div class="alert alert-info">{{ session('info') }}</div>
@endif

<form method="POST" action="{{ $storeRoute ?? route('admin.docente.setup.store') }}" id="setup-form">
    @csrf
    {{-- Al editar desde el admin, pasar el ID del docente --}}
    @if(isset($docente) && $docente && !auth()->user()->hasRole('Docente'))
        <input type="hidden" name="docente_id" value="{{ $docente->id }}">
    @endif

    {{-- Flow helper --}}
    <div class="alert alert-info d-flex gap-2 align-items-start mb-4" style="border-radius:10px;font-size:.84rem;">
        <i class="bi bi-lightbulb-fill flex-shrink-0 mt-1" style="color:#0ea5e9;"></i>
        <div>
            <strong>Cómo funciona:</strong> Completa tus datos, selecciona el área,
            marca cada materia que impartes en cada grupo, y si eres maestro guía indica tu grupo.
            Después de guardar podrás registrar asistencia y notas directamente desde tu panel.
        </div>
    </div>

    <!-- SECCIÓN 1: Datos del docente -->
    <div class="card border-0 shadow-sm mb-4 setup-card">
        <div class="card-header py-3" style="background:var(--primary);color:#fff;">
            <h6 class="mb-0 fw-bold"><i class="bi bi-person-fill me-2"></i>1. Mis Datos</h6>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Cédula <span class="text-danger">*</span></label>
                    <input type="text" name="cedula" class="form-control" placeholder="001-1234567-8"
                           value="{{ old('cedula', $docente?->cedula) }}" required>
                    @error('cedula')<div class="text-danger" style="font-size:.78rem;">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label">Teléfono</label>
                    <input type="text" name="telefono" class="form-control" placeholder="809-000-0000"
                           value="{{ old('telefono', $docente?->telefono ?? auth()->user()->telefono) }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Especialidad</label>
                    <input type="text" name="especialidad" class="form-control" placeholder="Ej. Matemáticas"
                           value="{{ old('especialidad', $docente?->especialidad) }}">
                </div>
                @php
                    $areaActual = old('area_trabajo', $docente?->area ?? '');
                    $chkAcad = in_array($areaActual, ['academica', 'ambas']);
                    $chkTec  = in_array($areaActual, ['tecnica',   'ambas']);
                @endphp
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Área de trabajo <span class="text-danger">*</span></label>
                    <div class="d-flex flex-column gap-2 mt-1">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox"
                                   name="area_academica" id="areaAcademica" value="1"
                                   {{ $chkAcad ? 'checked' : '' }}
                                   onchange="actualizarAreaYFiltro()">
                            <label class="form-check-label" for="areaAcademica">
                                <span class="badge me-1" style="background:#dbeafe;color:#1e40af;">Académica</span>
                                Materias del área académica
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox"
                                   name="area_tecnica" id="areaTecnica" value="1"
                                   {{ $chkTec ? 'checked' : '' }}
                                   onchange="actualizarAreaYFiltro()">
                            <label class="form-check-label" for="areaTecnica">
                                <span class="badge me-1" style="background:#fef3c7;color:#92400e;">Técnica</span>
                                Materias del área técnica
                            </label>
                        </div>
                    </div>
                    {{-- Campo oculto que almacena el valor derivado --}}
                    <input type="hidden" name="area_trabajo" id="areaTrabajoHidden" value="{{ $areaActual ?: 'academica' }}">
                    @error('area_trabajo')<div class="text-danger mt-1" style="font-size:.78rem;">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-5" id="especialidad-tecnica-wrap" style="{{ $chkTec ? '' : 'display:none' }}">
                    <label class="form-label">Especialidad Técnica</label>
                    <input type="text" name="especialidad_tecnica" class="form-control"
                           placeholder="Ej. Informática, Electricidad, Mecánica..."
                           value="{{ old('especialidad_tecnica', $docente?->especialidad) }}">
                    <div class="form-text">Escribe el nombre de tu área o especialidad técnica.</div>
                </div>
            </div>
        </div>
    </div>

    <!-- SECCIÓN 2: Lista de cotejo — Mis materias por grupo -->
    <div class="card border-0 shadow-sm mb-4 setup-card">
        <div class="card-header py-3" style="background:var(--primary);color:#fff;">
            <div class="d-flex align-items-center justify-content-between">
                <h6 class="mb-0 fw-bold"><i class="bi bi-check2-square me-2"></i>2. Materias que imparto</h6>
                <small style="opacity:.85;">Marca cada materia que impartes en cada grupo</small>
            </div>
        </div>

        {{-- Filtro de ciclo --}}
        @php
            $hayPrimerCiclo  = $grupos->contains(fn($g) => $g->grado->ciclo === 'primer_ciclo');
            $haySegundoCiclo = $grupos->contains(fn($g) => $g->grado->ciclo === 'segundo_ciclo');
            // Detectar ciclo por defecto según área del docente
            $cicloDefault = ($docente && $docente->area === 'tecnica') ? 'segundo' : 'primero';
            if (!$hayPrimerCiclo)  $cicloDefault = 'segundo';
            if (!$haySegundoCiclo) $cicloDefault = 'primero';
        @endphp
        @if($hayPrimerCiclo && $haySegundoCiclo)
        <div class="px-3 pt-3 pb-1 d-flex gap-2 align-items-center" style="border-bottom:1px solid #e5e7eb;">
            <span style="font-size:.75rem;font-weight:600;color:#64748b;">Ver grupos:</span>
            <button type="button" id="btn-ciclo-primero"
                    onclick="filtrarCicloSetup('primero')"
                    class="btn btn-sm {{ $cicloDefault === 'primero' ? 'btn-primary' : 'btn-outline-secondary' }}"
                    style="font-size:.73rem;border-radius:6px;padding:.2rem .7rem;">
                <i class="bi bi-1-circle me-1"></i>Primer Ciclo (1ro–3ro)
            </button>
            <button type="button" id="btn-ciclo-segundo"
                    onclick="filtrarCicloSetup('segundo')"
                    class="btn btn-sm {{ $cicloDefault === 'segundo' ? 'btn-primary' : 'btn-outline-secondary' }}"
                    style="font-size:.73rem;border-radius:6px;padding:.2rem .7rem;">
                <i class="bi bi-2-circle me-1"></i>Segundo Ciclo (4to–6to)
            </button>
            <button type="button" id="btn-ciclo-todos"
                    onclick="filtrarCicloSetup('todos')"
                    class="btn btn-sm btn-outline-secondary"
                    style="font-size:.73rem;border-radius:6px;padding:.2rem .7rem;">
                Todos
            </button>
        </div>
        @endif

        <div class="card-body p-0" id="seccion-materias">
            @error('materias')<div class="alert alert-danger m-3">{{ $message }}</div>@enderror

            @if($grupos->isEmpty())
                <div class="text-center text-muted py-4" style="font-size:.85rem;">
                    <i class="bi bi-info-circle d-block mb-2" style="font-size:1.5rem;"></i>
                    No hay grupos configurados para este año escolar. Puedes guardar tus datos
                    y volver a esta pantalla cuando los grupos estén creados.
                </div>
            @endif

            @foreach($grupos->groupBy(fn($g) => $g->grado->nivel) as $nivel => $gruposNivel)
            @php
                $niveles = [1=>'1ro',2=>'2do',3=>'3ro',4=>'4to',5=>'5to',6=>'6to'];
                $primerGrado = $gruposNivel->first()->grado;
                $esPrimerCicloBloque = ($primerGrado->ciclo === 'primer_ciclo');
            @endphp
            <div class="border-bottom setup-ciclo-bloque" data-ciclo="{{ $esPrimerCicloBloque ? 'primero' : 'segundo' }}">
                <div class="px-4 py-2 setup-section-header" style="background:#f8faff;">
                    <span class="fw-bold text-primary" style="font-size:.85rem;">
                        <i class="bi bi-mortarboard me-1"></i>{{ $niveles[$nivel] ?? $nivel.'mo' }} — {{ $primerGrado->nombre }}
                    </span>
                    @if($esPrimerCicloBloque)
                        <span class="badge ms-2" style="background:#dbeafe;color:#1e40af;font-size:.7rem;">Primer Ciclo</span>
                    @else
                        <span class="badge ms-2" style="background:#d1fae5;color:#065f46;font-size:.7rem;">Segundo Ciclo</span>
                    @endif
                </div>

                @foreach($gruposNivel as $grupo)
                <div class="px-4 py-3 border-top border-light">
                    <div class="fw-semibold mb-2 setup-grupo-header" style="font-size:.88rem;color:#374151;">
                        <i class="bi bi-people me-1 text-muted"></i>{{ $grupo->nombre_completo }}
                    </div>
                    <div class="row g-2">
                        @foreach($asignaturas as $asig)
                        <div class="col-sm-6 col-md-4 col-lg-3">
                            <label class="d-flex align-items-start gap-2 p-2 rounded border materia-check-item"
                                   style="cursor:pointer;font-size:.82rem;transition:background .15s;"
                                   onmouseover="this.style.background='#f0f4ff'" onmouseout="if(!this.querySelector('input').checked) this.style.background=''; else this.style.background='#eef3fb'">
                                @php $valMateria = $grupo->id.':'.$asig->id.':'.($asignacionesExistentes->first(fn($a)=>$a->grupo_id==$grupo->id && $a->asignatura_id==$asig->id)?->tipo_evaluacion ?? 'componentes'); @endphp
                <input type="checkbox"
                                       name="materias[]"
                                       value="{{ $valMateria }}"
                                       class="form-check-input mt-0 flex-shrink-0"
                                       {{ isset($marcados[$valMateria]) ? 'checked' : '' }}
                                       onchange="this.closest('label').style.background = this.checked ? '#eef3fb' : ''">
                                <div>
                                    <div class="fw-medium">{{ $asig->nombre }}</div>
                                    <span class="badge" style="font-size:.65rem;{{ $asig->area === 'tecnica' ? 'background:#fef3c7;color:#92400e' : 'background:#dbeafe;color:#1e40af' }}">
                                        {{ $asig->area === 'tecnica' ? 'Técnica' : 'Académica' }}
                                    </span>
                                </div>
                            </label>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endforeach
            </div>
            @endforeach
        </div>
    </div>

    <!-- SECCIÓN 3: Maestro Guía -->
    @php
        $chkGuia      = old('es_maestro_guia') !== null ? (bool)old('es_maestro_guia') : ($esGuia ?? false);
        $grupoGuiaSel = old('grupo_guia_id', $grupoGuiaId ?? '');
    @endphp
    <div class="card border-0 shadow-sm mb-4 setup-card">
        <div class="card-header py-3" style="background:var(--primary);color:#fff;">
            <h6 class="mb-0 fw-bold"><i class="bi bi-person-badge me-2"></i>3. Maestro Guía</h6>
        </div>
        <div class="card-body">

            <div class="form-check form-switch mb-3 setup-guia-wrap" style="font-size:1rem;">
                <input class="form-check-input" type="checkbox"
                       role="switch"
                       id="es_maestro_guia"
                       name="es_maestro_guia"
                       value="1"
                       {{ $chkGuia ? 'checked' : '' }}
                       onchange="document.getElementById('grupo-guia-wrap').style.display = this.checked ? 'block' : 'none'">
                <label class="form-check-label fw-semibold" for="es_maestro_guia" style="font-size:.95rem;">
                    Soy maestro guía / tutor de sección
                </label>
                <div class="text-muted" style="font-size:.78rem;margin-top:.15rem;">
                    El maestro guía es el responsable principal de un grupo.
                </div>
            </div>

            <div id="grupo-guia-wrap" style="display:{{ $chkGuia ? 'block' : 'none' }};">
                <label class="form-label fw-semibold">
                    <i class="bi bi-people me-1"></i>Grupo a mi cargo <span class="text-danger">*</span>
                </label>
                <select name="grupo_guia_id" class="form-select" style="max-width:320px;">
                    <option value="">— Seleccionar grupo —</option>
                    @forelse(($todosGrupos ?? collect())->sortBy(fn($g) => $g->grado->nivel) as $g)
                        <option value="{{ $g->id }}" {{ $grupoGuiaSel == $g->id ? 'selected' : '' }}>
                            {{ $g->nombre_completo }}
                        </option>
                    @empty
                        <option disabled>No hay grupos disponibles</option>
                    @endforelse
                </select>
                @error('grupo_guia_id')
                    <div class="text-danger mt-1" style="font-size:.78rem;">{{ $message }}</div>
                @enderror
            </div>

        </div>
    </div>

    <div class="d-flex justify-content-end gap-2 mb-4">
        <button type="submit" class="btn btn-primary px-4">
            <i class="bi bi-save me-2"></i>Guardar y continuar
        </button>
    </div>
<script>
function filtrarCicloSetup(ciclo) {
    document.querySelectorAll('.setup-ciclo-bloque').forEach(function(bloque) {
        const bc = bloque.dataset.ciclo;
        bloque.style.display = (ciclo === 'todos' || bc === ciclo) ? '' : 'none';
        // desmarcar checkboxes ocultos para no enviarlos
        if (ciclo !== 'todos' && bc !== ciclo) {
            bloque.querySelectorAll('input[type="checkbox"]').forEach(function(cb) {
                cb.checked = false;
            });
        }
    });
    ['primero','segundo','todos'].forEach(function(c) {
        const btn = document.getElementById('btn-ciclo-' + c);
        if (!btn) return;
        btn.className = btn.className.replace('btn-primary','btn-outline-secondary');
        if (c === ciclo) btn.className = btn.className.replace('btn-outline-secondary','btn-primary');
    });
}

function actualizarAreaYFiltro() {
    const acad = document.getElementById('areaAcademica').checked;
    const tec  = document.getElementById('areaTecnica').checked;

    let area = (acad && tec) ? 'ambas' : (tec ? 'tecnica' : 'academica');
    document.getElementById('areaTrabajoHidden').value = area;
    document.getElementById('especialidad-tecnica-wrap').style.display = tec ? '' : 'none';

    // Filtrar SOLO los checkboxes de materias (sección 2)
    document.querySelectorAll('#seccion-materias .materia-check-item').forEach(function(item) {
        const badge = item.querySelector('.badge');
        if (!badge) return;
        const esTecnicaItem = badge.textContent.trim() === 'Técnica';
        // Subir solo hasta el div col-* inmediato dentro de #seccion-materias
        let col = item.parentElement;
        while (col && col.id !== 'seccion-materias' && !col.className.match(/\bcol-/)) {
            col = col.parentElement;
        }
        if (!col || col.id === 'seccion-materias') return;
        if (esTecnicaItem) {
            col.style.display = tec ? '' : 'none';
            if (!tec && item.querySelector('input')) item.querySelector('input').checked = false;
        } else {
            col.style.display = acad ? '' : 'none';
            if (!acad && item.querySelector('input')) item.querySelector('input').checked = false;
        }
    });
}


actualizarAreaYFiltro();
// Aplicar filtro de ciclo por defecto
(function() {
    const cicloDefault = '{{ $cicloDefault ?? "primero" }}';
    if (document.querySelector('.setup-ciclo-bloque')) {
        filtrarCicloSetup(cicloDefault);
    }
})();
</script>
</form>
@endsection
