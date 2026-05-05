@extends('layouts.admin')
@section('page-title', 'Matrícula Masiva — ' . $schoolYear->nombre)

@section('content')
<div class="container-fluid py-3">

{{-- Header --}}
<div class="d-flex align-items-center gap-3 mb-3 flex-wrap">
    <a href="{{ route('admin.school-years.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Volver
    </a>
    <div>
        <h4 class="mb-0 fw-bold">
            <i class="bi bi-person-plus-fill text-primary me-2"></i>
            Matrícula Masiva — {{ $schoolYear->nombre }}
        </h4>
        <small class="text-muted">
            Estudiantes del año <strong>{{ $anioAnterior->nombre }}</strong> →
            <strong>{{ $schoolYear->nombre }}</strong>
        </small>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show py-2">
    <i class="bi bi-check-circle me-1"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

{{-- Info banner --}}
<div class="alert alert-info py-2 mb-3" style="font-size:.85rem;">
    <i class="bi bi-info-circle me-1"></i>
    Selecciona los estudiantes que quieres rematricular y asígnales el grupo del nuevo año.
    Los estudiantes ya matriculados en <strong>{{ $schoolYear->nombre }}</strong> aparecen marcados y se omiten automáticamente.
    <strong>{{ $totalPendientes }}</strong> estudiante(s) pendientes de matrícula.
</div>

@if($gruposNuevoAnio->isEmpty())
<div class="alert alert-warning">
    <i class="bi bi-exclamation-triangle me-1"></i>
    No hay grupos creados para <strong>{{ $schoolYear->nombre }}</strong>.
    <a href="{{ route('admin.grupos.create') }}" class="alert-link">Crear grupos primero</a>.
</div>
@elseif($matriculasAnteriores->isEmpty())
<div class="alert alert-secondary">
    <i class="bi bi-people me-1"></i>
    No hay estudiantes con matrícula activa en <strong>{{ $anioAnterior->nombre }}</strong>.
</div>
@else

<form method="POST" action="{{ route('admin.school-years.matricula-masiva.store', $schoolYear) }}" id="formMasiva">
@csrf

{{-- Barra de acciones --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-2 d-flex align-items-center gap-3 flex-wrap">
        <button type="button" class="btn btn-sm btn-outline-primary" onclick="seleccionarTodos()">
            <i class="bi bi-check-all me-1"></i>Seleccionar todos
        </button>
        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="deseleccionarTodos()">
            <i class="bi bi-x-square me-1"></i>Limpiar selección
        </button>
        <span class="text-muted small" id="contadorSeleccionados">0 seleccionados</span>
        <div class="ms-auto">
            <button type="submit" class="btn btn-primary btn-sm" id="btnGuardar" disabled>
                <i class="bi bi-person-plus-fill me-1"></i>Matricular Seleccionados
            </button>
        </div>
    </div>
</div>

{{-- Grupos del año anterior --}}
@foreach($matriculasAnteriores as $grupoId => $matriculas)
@php
    $grupo = $gruposAnterior[$grupoId] ?? null;
    if (!$grupo) continue;
    $gradoActual = $grupo->grado;
    // Sugerir el siguiente grado
    $siguienteGrado = $grados->firstWhere('orden', ($gradoActual->orden ?? 0) + 1);
    // Grupo sugerido del nuevo año (mismo grado o siguiente)
    $grupoSugerido = $gruposNuevoAnio->where('grado_id', $siguienteGrado?->id)->first();
@endphp

<div class="card border-0 shadow-sm mb-3">
    <div class="card-header py-2 d-flex align-items-center gap-2" style="background:#f8fafc;">
        <i class="bi bi-people-fill text-primary"></i>
        <strong>{{ $gradoActual?->nombre ?? '—' }} — {{ $grupo->seccion?->nombre ?? '' }}</strong>
        <span class="badge bg-secondary ms-1" style="font-size:.72rem;">{{ $matriculas->count() }} estudiantes</span>
        @if($siguienteGrado)
        <span class="text-muted small ms-2">
            → <i class="bi bi-arrow-right-circle text-success me-1"></i>Próximo: {{ $siguienteGrado->nombre }}
        </span>
        @else
        <span class="badge bg-warning text-dark ms-2" style="font-size:.7rem;">
            <i class="bi bi-mortarboard me-1"></i>Grado final — egresados
        </span>
        @endif
        {{-- Selector de grupo destino para todos del grupo --}}
        @if($gruposNuevoAnio->isNotEmpty() && $siguienteGrado)
        <div class="ms-auto d-flex align-items-center gap-2">
            <label class="form-label-sm mb-0 text-muted" style="font-size:.78rem;white-space:nowrap;">Asignar todos a:</label>
            <select class="form-select form-select-sm grupo-rapido" style="max-width:200px;"
                    data-grupo-origen="{{ $grupoId }}">
                <option value="">— Elegir grupo —</option>
                @foreach($gruposNuevoAnio as $gn)
                <option value="{{ $gn->id }}" {{ $gn->id === $grupoSugerido?->id ? 'selected' : '' }}>
                    {{ $gn->grado?->nombre }} {{ $gn->seccion?->nombre }}
                </option>
                @endforeach
            </select>
            <button type="button" class="btn btn-sm btn-outline-success"
                    onclick="aplicarGrupoRapido('{{ $grupoId }}')">
                <i class="bi bi-arrow-right-circle"></i>Aplicar
            </button>
        </div>
        @endif
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0" style="font-size:.83rem;">
                <thead class="table-light">
                    <tr>
                        <th style="width:36px;">
                            <input type="checkbox" class="form-check-input grupo-check"
                                   data-grupo="{{ $grupoId }}"
                                   title="Seleccionar/deseleccionar todos del grupo"
                                   {{ $siguienteGrado ? '' : 'disabled' }}>
                        </th>
                        <th>Estudiante</th>
                        <th style="width:220px;">Grupo destino ({{ $schoolYear->nombre }})</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($matriculas as $idx => $mat)
                @php $yaEsta = in_array($mat->estudiante_id, $yaMatriculados); @endphp
                <tr class="{{ $yaEsta ? 'table-light' : '' }}" id="fila-{{ $mat->estudiante_id }}">
                    <td>
                        @if($yaEsta)
                        <span title="Ya matriculado en {{ $schoolYear->nombre }}">
                            <i class="bi bi-check-circle-fill text-success"></i>
                        </span>
                        @elseif(!$siguienteGrado)
                        <span title="Grado final — verificar manualmente">
                            <i class="bi bi-mortarboard text-warning"></i>
                        </span>
                        @else
                        <input type="checkbox" class="form-check-input est-check"
                               data-grupo="{{ $grupoId }}"
                               data-estudiante="{{ $mat->estudiante_id }}"
                               onchange="actualizarContador(); toggleFila(this)">
                        @endif
                    </td>
                    <td>
                        <div class="fw-semibold">{{ $mat->estudiante?->nombre_completo ?? '—' }}</div>
                        <div class="text-muted" style="font-size:.72rem;">{{ $mat->estudiante?->numero_matricula }}</div>
                    </td>
                    <td>
                        @if($yaEsta)
                        <span class="badge text-bg-success" style="font-size:.72rem;">
                            <i class="bi bi-check me-1"></i>Ya matriculado
                        </span>
                        @elseif(!$siguienteGrado)
                        <select class="form-select form-select-sm grupo-select-est" style="font-size:.78rem;"
                                data-est="{{ $mat->estudiante_id }}" disabled>
                            <option value="">Egresa — gestionar manualmente</option>
                        </select>
                        @else
                        <select class="form-select form-select-sm grupo-select-est"
                                name="dummy_{{ $mat->estudiante_id }}"
                                style="font-size:.78rem;"
                                data-est="{{ $mat->estudiante_id }}"
                                id="sel-{{ $mat->estudiante_id }}">
                            <option value="">— Seleccionar grupo —</option>
                            @foreach($gruposNuevoAnio as $gn)
                            <option value="{{ $gn->id }}"
                                    {{ $gn->id === $grupoSugerido?->id ? 'selected' : '' }}>
                                {{ $gn->grado?->nombre }} {{ $gn->seccion?->nombre }}
                            </option>
                            @endforeach
                        </select>
                        {{-- Hidden inputs: solo se envían cuando el checkbox está marcado --}}
                        <input type="hidden" class="hidden-est" data-est="{{ $mat->estudiante_id }}" disabled>
                        <input type="hidden" class="hidden-grp" data-est="{{ $mat->estudiante_id }}" disabled>
                        @endif
                    </td>
                </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endforeach

</form>
@endif

</div>
@endsection

@push('scripts')
<script>
let contadorTotal = 0;

function actualizarContador() {
    const checks = document.querySelectorAll('.est-check:checked');
    contadorTotal = checks.length;
    document.getElementById('contadorSeleccionados').textContent = contadorTotal + ' seleccionado(s)';
    document.getElementById('btnGuardar').disabled = contadorTotal === 0;
}

function toggleFila(checkbox) {
    const estId = checkbox.dataset.estudiante;
    const sel   = document.getElementById('sel-' + estId);
    const hiddenEst = document.querySelector('.hidden-est[data-est="' + estId + '"]');
    const hiddenGrp = document.querySelector('.hidden-grp[data-est="' + estId + '"]');

    if (checkbox.checked) {
        // Activar hidden inputs con el grupo seleccionado
        if (hiddenEst) {
            hiddenEst.name    = 'matriculas[' + estId + '][estudiante_id]';
            hiddenEst.value   = estId;
            hiddenEst.disabled = false;
        }
        if (hiddenGrp && sel) {
            hiddenGrp.name    = 'matriculas[' + estId + '][grupo_id]';
            hiddenGrp.value   = sel.value;
            hiddenGrp.disabled = false;
        }
        if (sel) {
            sel.addEventListener('change', function() {
                if (hiddenGrp) hiddenGrp.value = this.value;
            });
        }
    } else {
        if (hiddenEst) { hiddenEst.disabled = true; hiddenEst.name = ''; }
        if (hiddenGrp) { hiddenGrp.disabled = true; hiddenGrp.name = ''; }
    }
}

function seleccionarTodos() {
    document.querySelectorAll('.est-check:not(:disabled)').forEach(cb => {
        if (!cb.checked) { cb.checked = true; toggleFila(cb); }
    });
    actualizarContador();
}

function deseleccionarTodos() {
    document.querySelectorAll('.est-check:checked').forEach(cb => {
        cb.checked = false; toggleFila(cb);
    });
    actualizarContador();
}

// Checkbox de grupo completo
document.querySelectorAll('.grupo-check').forEach(gc => {
    gc.addEventListener('change', function() {
        const grupoId = this.dataset.grupo;
        document.querySelectorAll('.est-check[data-grupo="' + grupoId + '"]:not(:disabled)').forEach(cb => {
            cb.checked = this.checked;
            toggleFila(cb);
        });
        actualizarContador();
    });
});

// Aplicar grupo rápido a todos del grupo
function aplicarGrupoRapido(grupoOrigenId) {
    const sel  = document.querySelector('.grupo-rapido[data-grupo-origen="' + grupoOrigenId + '"]');
    if (!sel || !sel.value) return;
    const grupoDestId = sel.value;

    document.querySelectorAll('.est-check[data-grupo="' + grupoOrigenId + '"]:not(:disabled)').forEach(cb => {
        const estId   = cb.dataset.estudiante;
        const estSel  = document.getElementById('sel-' + estId);
        const hidGrp  = document.querySelector('.hidden-grp[data-est="' + estId + '"]');

        if (estSel)  estSel.value  = grupoDestId;
        if (hidGrp)  hidGrp.value  = grupoDestId;
    });
}

// Validar antes de enviar
document.getElementById('formMasiva')?.addEventListener('submit', function(e) {
    const checks = document.querySelectorAll('.est-check:checked');
    let valid = true;
    checks.forEach(cb => {
        const estId = cb.dataset.estudiante;
        const grp   = document.querySelector('.hidden-grp[data-est="' + estId + '"]');
        if (!grp || !grp.value) { valid = false; }
    });
    if (!valid) {
        e.preventDefault();
        alert('Hay estudiantes seleccionados sin grupo destino asignado. Por favor asigna un grupo a todos los seleccionados.');
    }
});
</script>
@endpush
