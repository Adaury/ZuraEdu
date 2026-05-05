@extends('layouts.admin')
@section('page-title', 'Crear Quiz — '.$material->titulo)
@section('content')

@php $color = $claseVirtual->portada_color ?? '#4f46e5'; @endphp

<div class="mb-4 d-flex align-items-center gap-3">
    <a href="{{ route('portal.docente.classroom.show', $claseVirtual) }}" class="btn btn-outline-secondary btn-sm" style="border-radius:8px;">
        <i class="bi bi-arrow-left me-1"></i>Volver
    </a>
    <div>
        <h5 class="fw-bold mb-0">Crear Quiz Online</h5>
        <small class="text-muted">{{ $claseVirtual->nombre }} &bull; {{ $material->titulo }}</small>
    </div>
</div>

<form method="POST" action="{{ route('portal.docente.classroom.quiz.guardar', [$claseVirtual, $material]) }}" id="formQuiz">
@csrf
<div class="row g-4">

{{-- Columna principal: preguntas --}}
<div class="col-lg-8">

    <div class="card border-0 shadow-sm mb-3" style="border-radius:16px;">
    <div class="card-body p-4">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <h6 class="fw-bold mb-0"><i class="bi bi-list-ol me-2" style="color:{{ $color }};"></i>Preguntas</h6>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-sm btn-outline-primary" onclick="agregarPregunta('multiple')" style="border-radius:8px;">
                    <i class="bi bi-check-circle me-1"></i>Múltiple
                </button>
                <button type="button" class="btn btn-sm btn-outline-success" onclick="agregarPregunta('verdadero_falso')" style="border-radius:8px;">
                    <i class="bi bi-toggle-on me-1"></i>V/F
                </button>
                <button type="button" class="btn btn-sm btn-outline-warning" onclick="agregarPregunta('abierta')" style="border-radius:8px;">
                    <i class="bi bi-pencil me-1"></i>Abierta
                </button>
            </div>
        </div>

        <div id="preguntasContainer">
            {{-- Las preguntas se agregan dinámicamente --}}
        </div>

        <div id="sinPreguntas" class="text-center py-5 text-muted">
            <i class="bi bi-question-circle" style="font-size:2.5rem;color:#CBD5E1;display:block;margin-bottom:.75rem;"></i>
            <p class="mb-0">Usa los botones de arriba para agregar preguntas</p>
        </div>
    </div>
    </div>

</div>

{{-- Sidebar configuración --}}
<div class="col-lg-4">
<div class="card border-0 shadow-sm" style="border-radius:16px;position:sticky;top:80px;">
<div class="card-body p-4">
    <h6 class="fw-bold mb-4">Configuración del Quiz</h6>

    <div class="mb-3">
        <label class="form-label fw-semibold small">Tiempo límite (minutos)</label>
        <input type="number" name="duracion_minutos" class="form-control" min="1" max="300" placeholder="Sin límite de tiempo">
    </div>

    <div class="mb-3">
        <label class="form-label fw-semibold small">Intentos máximos</label>
        <select name="intentos_max" class="form-select">
            @foreach([1,2,3,5] as $n)
            <option value="{{ $n }}" {{ $n===1?'selected':'' }}>{{ $n }} intento{{ $n>1?'s':'' }}</option>
            @endforeach
        </select>
    </div>

    <hr>

    <div class="form-check form-switch mb-2">
        <input class="form-check-input" type="checkbox" name="autocorreccion" id="autocorreccion" value="1" checked>
        <label class="form-check-label small" for="autocorreccion">Autocorrección al finalizar</label>
    </div>
    <div class="form-check form-switch mb-2">
        <input class="form-check-input" type="checkbox" name="mostrar_respuestas" id="mostrar_respuestas" value="1" checked>
        <label class="form-check-label small" for="mostrar_respuestas">Mostrar respuestas correctas al final</label>
    </div>
    <div class="form-check form-switch mb-4">
        <input class="form-check-input" type="checkbox" name="aleatorizar_preguntas" id="aleatorizar" value="1">
        <label class="form-check-label small" for="aleatorizar">Aleatorizar orden de preguntas</label>
    </div>

    <div class="p-3 rounded-3 mb-4" style="background:#F0F9FF;border:1px solid #BAE6FD;">
        <div class="fw-semibold small mb-1" style="color:#0284C7;">
            <i class="bi bi-info-circle me-1"></i>Puntaje total
        </div>
        <div style="font-size:1.3rem;font-weight:800;color:#0284C7;" id="puntajeTotal">0 puntos</div>
        <div class="text-muted small mt-1" id="conteoPreguntas">0 preguntas</div>
    </div>

    <div class="d-grid gap-2">
        <button type="submit" class="btn btn-primary fw-bold" style="border-radius:10px;">
            <i class="bi bi-check-lg me-1"></i>Guardar Quiz
        </button>
        <a href="{{ route('portal.docente.classroom.show', $claseVirtual) }}" class="btn btn-outline-secondary" style="border-radius:10px;">
            Cancelar
        </a>
    </div>
</div>
</div>
</div>

</div>
</form>

{{-- Template de pregunta (oculto) --}}
<template id="tplMultiple">
<div class="pregunta-bloque card border-0 mb-3" style="border-radius:12px;border:1.5px solid #E5E7EB;" data-tipo="multiple">
    <div class="card-body p-3">
        <div class="d-flex align-items-center gap-2 mb-2">
            <span class="badge" style="background:#EEF2FF;color:#4F46E5;">Múltiple opción</span>
            <span class="numero-pregunta fw-bold text-muted ms-1" style="font-size:.8rem;"></span>
            <div class="ms-auto d-flex gap-1">
                <input type="number" name="REPLACE_puntos" class="form-control form-control-sm puntos-input" style="width:70px;" min="0.5" step="0.5" value="1" placeholder="Pts">
                <button type="button" class="btn btn-sm btn-outline-danger" onclick="eliminarPregunta(this)" style="border-radius:6px;"><i class="bi bi-trash"></i></button>
            </div>
        </div>
        <textarea name="REPLACE_enunciado" class="form-control mb-3" rows="2" placeholder="Escribe la pregunta..." required></textarea>
        <input type="hidden" name="REPLACE_tipo" value="multiple">
        <div class="opciones-container d-flex flex-column gap-2 mb-2"></div>
        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="agregarOpcion(this,'multiple')" style="border-radius:8px;font-size:.8rem;">
            <i class="bi bi-plus-lg me-1"></i>Agregar opción
        </button>
    </div>
</div>
</template>

<template id="tplVF">
<div class="pregunta-bloque card border-0 mb-3" style="border-radius:12px;border:1.5px solid #E5E7EB;" data-tipo="verdadero_falso">
    <div class="card-body p-3">
        <div class="d-flex align-items-center gap-2 mb-2">
            <span class="badge" style="background:#ECFDF5;color:#059669;">Verdadero / Falso</span>
            <span class="numero-pregunta fw-bold text-muted ms-1" style="font-size:.8rem;"></span>
            <div class="ms-auto d-flex gap-1">
                <input type="number" name="REPLACE_puntos" class="form-control form-control-sm puntos-input" style="width:70px;" min="0.5" step="0.5" value="1" placeholder="Pts">
                <button type="button" class="btn btn-sm btn-outline-danger" onclick="eliminarPregunta(this)" style="border-radius:6px;"><i class="bi bi-trash"></i></button>
            </div>
        </div>
        <textarea name="REPLACE_enunciado" class="form-control mb-3" rows="2" placeholder="Escribe el enunciado..." required></textarea>
        <input type="hidden" name="REPLACE_tipo" value="verdadero_falso">
        <div class="d-flex gap-3">
            <label class="d-flex align-items-center gap-2 p-2 rounded-3 flex-fill" style="border:2px solid #E5E7EB;cursor:pointer;">
                <input type="radio" name="REPLACE_correcta_vf" value="V" checked class="form-check-input">
                <span class="fw-semibold text-success">✓ Verdadero</span>
            </label>
            <label class="d-flex align-items-center gap-2 p-2 rounded-3 flex-fill" style="border:2px solid #E5E7EB;cursor:pointer;">
                <input type="radio" name="REPLACE_correcta_vf" value="F" class="form-check-input">
                <span class="fw-semibold text-danger">✗ Falso</span>
            </label>
        </div>
    </div>
</div>
</template>

<template id="tplAbierta">
<div class="pregunta-bloque card border-0 mb-3" style="border-radius:12px;border:1.5px solid #E5E7EB;" data-tipo="abierta">
    <div class="card-body p-3">
        <div class="d-flex align-items-center gap-2 mb-2">
            <span class="badge" style="background:#FEF3C7;color:#D97706;">Respuesta abierta</span>
            <span class="numero-pregunta fw-bold text-muted ms-1" style="font-size:.8rem;"></span>
            <div class="ms-auto d-flex gap-1">
                <input type="number" name="REPLACE_puntos" class="form-control form-control-sm puntos-input" style="width:70px;" min="0.5" step="0.5" value="1" placeholder="Pts">
                <button type="button" class="btn btn-sm btn-outline-danger" onclick="eliminarPregunta(this)" style="border-radius:6px;"><i class="bi bi-trash"></i></button>
            </div>
        </div>
        <textarea name="REPLACE_enunciado" class="form-control mb-2" rows="2" placeholder="Escribe la pregunta..." required></textarea>
        <input type="hidden" name="REPLACE_tipo" value="abierta">
        <div class="p-2 rounded-2 text-muted small" style="background:#FFF7ED;border:1px solid #FED7AA;">
            <i class="bi bi-info-circle me-1"></i>El docente calificará esta pregunta manualmente.
        </div>
    </div>
</div>
</template>

@push('scripts')
<script>
let preguntaIndex = 0;

function agregarPregunta(tipo) {
    const tplId = tipo === 'multiple' ? 'tplMultiple' : (tipo === 'verdadero_falso' ? 'tplVF' : 'tplAbierta');
    const tpl   = document.getElementById(tplId);
    const clone = tpl.content.cloneNode(true);
    const idx   = preguntaIndex++;

    // Reemplazar REPLACE_ con índice
    clone.querySelectorAll('[name]').forEach(el => {
        el.name = el.name.replace('REPLACE', `preguntas[${idx}]`);
    });

    document.getElementById('sinPreguntas').style.display = 'none';
    document.getElementById('preguntasContainer').appendChild(clone);

    // Agregar 2 opciones por defecto en múltiple
    if (tipo === 'multiple') {
        const bloque = document.getElementById('preguntasContainer').lastElementChild;
        agregarOpcion(bloque.querySelector('[onclick*="agregarOpcion"]'), 'multiple');
        agregarOpcion(bloque.querySelector('[onclick*="agregarOpcion"]'), 'multiple');
    }

    actualizarNumeros();
    actualizarPuntaje();
}

function agregarOpcion(btn, tipo) {
    const bloque     = btn.closest('.pregunta-bloque');
    const container  = bloque.querySelector('.opciones-container');
    const pIdx       = bloque.querySelector('[name*="[enunciado]"]').name.match(/\[(\d+)\]/)[1];
    const oIdx       = container.children.length;

    const div = document.createElement('div');
    div.className = 'd-flex align-items-center gap-2';
    div.innerHTML = `
        <input type="radio" name="preguntas[${pIdx}][opciones][${oIdx}][correcta]" value="1" class="form-check-input flex-shrink-0" style="cursor:pointer;" title="Marcar como correcta" onchange="marcarCorrectaUnica(this)">
        <input type="text" name="preguntas[${pIdx}][opciones][${oIdx}][texto]" class="form-control form-control-sm" placeholder="Opción ${oIdx+1}..." required>
        <button type="button" class="btn btn-sm btn-outline-danger" onclick="this.parentElement.remove();actualizarPuntaje()" style="flex-shrink:0;border-radius:6px;"><i class="bi bi-x"></i></button>
    `;
    container.appendChild(div);
}

function marcarCorrectaUnica(radio) {
    const container = radio.closest('.opciones-container');
    container.querySelectorAll('input[type=radio]').forEach(r => {
        r.value = r === radio ? '1' : '0';
    });
}

function eliminarPregunta(btn) {
    if (!confirm('¿Eliminar esta pregunta?')) return;
    btn.closest('.pregunta-bloque').remove();
    if (!document.querySelectorAll('.pregunta-bloque').length) {
        document.getElementById('sinPreguntas').style.display = '';
    }
    actualizarNumeros();
    actualizarPuntaje();
}

function actualizarNumeros() {
    document.querySelectorAll('.pregunta-bloque').forEach((el, i) => {
        const span = el.querySelector('.numero-pregunta');
        if (span) span.textContent = `Pregunta ${i + 1}`;
    });
}

function actualizarPuntaje() {
    let total = 0;
    document.querySelectorAll('.puntos-input').forEach(el => {
        total += parseFloat(el.value) || 0;
    });
    const count = document.querySelectorAll('.pregunta-bloque').length;
    document.getElementById('puntajeTotal').textContent = total.toFixed(1) + ' puntos';
    document.getElementById('conteoPreguntas').textContent = count + ' pregunta' + (count !== 1 ? 's' : '');
}

document.addEventListener('input', e => { if (e.target.classList.contains('puntos-input')) actualizarPuntaje(); });
</script>
@endpush

@endsection
