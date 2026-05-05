@extends('layouts.admin')
@section('page-title', 'Agregar Material — '.$claseVirtual->nombre)
@section('content')

@php $color = $claseVirtual->portada_color ?? '#3B82F6'; @endphp

<div class="mb-4 d-flex align-items-center gap-3">
    <a href="{{ route('portal.docente.classroom.show', $claseVirtual) }}" class="btn btn-outline-secondary btn-sm" style="border-radius:8px;">
        <i class="bi bi-arrow-left me-1"></i>Volver
    </a>
    <div>
        <h5 class="fw-bold mb-0">Agregar al aula</h5>
        <small class="text-muted">{{ $claseVirtual->nombre }}</small>
    </div>
</div>

<form method="POST" action="{{ route('portal.docente.classroom.guardar_material', $claseVirtual) }}" enctype="multipart/form-data" id="formMaterial">
@csrf

<div class="row g-4">
<div class="col-lg-8">

    {{-- ═══ TIPO DE MATERIAL ═══ --}}
    <div class="card border-0 shadow-sm mb-3" style="border-radius:16px;">
    <div class="card-body p-4">
        <label class="form-label fw-semibold mb-3">Tipo de material</label>
        <div class="d-flex gap-2 flex-wrap" id="tipoGroup">
            @foreach(['anuncio'=>['Anuncio','#6366F1','bi-megaphone-fill'],'material'=>['Material','#10B981','bi-book-fill'],'tarea'=>['Tarea','#F59E0B','bi-pencil-fill'],'evaluacion'=>['Evaluación','#EF4444','bi-clipboard-check-fill']] as $val=>[$lbl,$clr,$icn])
            <div>
                <input type="radio" class="btn-check" name="tipo" id="tipo_{{ $val }}" value="{{ $val }}"
                       {{ old('tipo', request('tipo','anuncio'))===$val ? 'checked' : '' }} onchange="onTipoChange()">
                <label class="btn btn-sm" for="tipo_{{ $val }}"
                       style="border:2px solid {{ $clr }}20;background:{{ $clr }}10;color:{{ $clr }};border-radius:10px;">
                    <i class="bi {{ $icn }} me-1"></i>{{ $lbl }}
                </label>
            </div>
            @endforeach
        </div>
    </div>
    </div>

    {{-- ═══ CONTENIDO PRINCIPAL ═══ --}}
    <div class="card border-0 shadow-sm mb-3" style="border-radius:16px;">
    <div class="card-body p-4">
        <div class="mb-3">
            <label class="form-label fw-semibold">Título <span class="text-danger">*</span></label>
            <input type="text" name="titulo" class="form-control @error('titulo') is-invalid @enderror"
                   value="{{ old('titulo') }}" required placeholder="Ej: Tarea 1 — Fundamentos de HTML">
            @error('titulo')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="mb-3">
            <label class="form-label fw-semibold">Descripción / Instrucciones</label>
            <textarea name="contenido" class="form-control" rows="5"
                      placeholder="Describe la actividad, instrucciones detalladas, criterios de entrega...">{{ old('contenido') }}</textarea>
        </div>

        <div class="mb-0">
            <label class="form-label fw-semibold">URL externa (opcional)</label>
            <input type="url" name="url_externo" class="form-control" value="{{ old('url_externo') }}"
                   placeholder="https://docs.google.com/...  YouTube, Drive, etc.">
        </div>
    </div>
    </div>

    {{-- ═══ ARCHIVOS ADJUNTOS ═══ --}}
    <div class="card border-0 shadow-sm mb-3" style="border-radius:16px;">
    <div class="card-body p-4">
        <label class="form-label fw-semibold">Archivos adjuntos</label>
        <div class="border-2 border-dashed rounded-3 p-4 text-center" style="border-color:#CBD5E1;background:#F8FAFC;cursor:pointer;" onclick="document.getElementById('inputArchivos').click();">
            <i class="bi bi-cloud-arrow-up" style="font-size:2rem;color:#94A3B8;"></i>
            <p class="text-muted small mb-0 mt-1">Haz clic para subir o arrastra aquí</p>
            <small class="text-muted" style="font-size:.75rem;">PDF, DOC, JPG, PNG — máx. 10 MB c/u</small>
        </div>
        <input type="file" name="archivos[]" id="inputArchivos" multiple class="d-none"
               accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.ppt,.pptx,.zip"
               onchange="mostrarArchivos(this)">
        <div id="listaArchivos" class="mt-2 d-flex flex-wrap gap-2"></div>
    </div>
    </div>

    {{-- ═══ RÚBRICA (solo tareas/evaluaciones) ═══ --}}
    <div class="card border-0 shadow-sm mb-3" style="border-radius:16px;" id="seccionRubrica" style="display:none;">
    <div class="card-body p-4">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <label class="fw-semibold mb-0"><i class="bi bi-grid-3x3-gap me-2 text-indigo"></i>Rúbrica de evaluación</label>
            <div class="form-check form-switch mb-0">
                <input class="form-check-input" type="checkbox" id="usarRubrica" onchange="toggleRubrica()">
                <label class="form-check-label small" for="usarRubrica">Usar rúbrica</label>
            </div>
        </div>
        <div id="rubricaForm" style="display:none;">
            <div class="mb-3">
                <input type="text" name="rubric_nombre" class="form-control" placeholder="Nombre de la rúbrica" value="{{ old('rubric_nombre') }}">
            </div>
            <div id="criteriosContainer">
                <div class="criterio-row d-flex gap-2 mb-2 align-items-start">
                    <div class="flex-grow-1">
                        <input type="text" name="criterios[0][nombre]" class="form-control form-control-sm mb-1" placeholder="Criterio (ej: Contenido)">
                        <input type="text" name="criterios[0][descripcion]" class="form-control form-control-sm" placeholder="Descripción opcional">
                    </div>
                    <div style="width:90px;">
                        <input type="number" name="criterios[0][puntaje_max]" class="form-control form-control-sm text-center" placeholder="Pts" min="1" value="10">
                        <div class="text-muted text-center" style="font-size:.7rem;">puntos</div>
                    </div>
                </div>
            </div>
            <button type="button" class="btn btn-sm btn-outline-secondary" style="border-radius:8px;" onclick="agregarCriterio()">
                <i class="bi bi-plus-lg me-1"></i>Agregar criterio
            </button>
        </div>
    </div>
    </div>

</div>

{{-- ═══ SIDEBAR OPCIONES ═══ --}}
<div class="col-lg-4">
    <div class="card border-0 shadow-sm mb-3" style="border-radius:16px;position:sticky;top:80px;">
    <div class="card-body p-4">

        {{-- Fechas --}}
        <div class="mb-3" id="seccionFecha">
            <label class="form-label fw-semibold small">Fecha límite de entrega</label>
            <input type="datetime-local" name="fecha_limite" class="form-control form-control-sm" value="{{ old('fecha_limite') }}">
        </div>

        {{-- Puntos --}}
        <div class="mb-3" id="seccionPuntos">
            <label class="form-label fw-semibold small">Valor en puntos</label>
            <input type="number" name="puntos" class="form-control form-control-sm" min="0" max="100" value="{{ old('puntos', 100) }}" placeholder="100">
        </div>

        {{-- Período evaluativo --}}
        @if($periodos->isNotEmpty())
        <div class="mb-3" id="seccionPeriodo">
            <label class="form-label fw-semibold small">Período evaluativo</label>
            <select name="periodo_id" class="form-select form-select-sm">
                <option value="">Sin período (no sincroniza notas)</option>
                @foreach($periodos as $periodo)
                <option value="{{ $periodo->id }}" {{ old('periodo_id')==$periodo->id ? 'selected' : '' }}>
                    {{ $periodo->nombre ?? 'Período '.$periodo->numero }}
                </option>
                @endforeach
            </select>
            <div class="form-text" style="font-size:.75rem;">Al calificar, puede sincronizar automáticamente con el libro de notas.</div>
        </div>
        @endif

        {{-- Competencia --}}
        @if($competencias->isNotEmpty())
        <div class="mb-3" id="seccionCompetencia">
            <label class="form-label fw-semibold small">Competencia asociada</label>
            <select name="competencia_id" class="form-select form-select-sm">
                <option value="">Ninguna</option>
                @foreach($competencias as $comp)
                <option value="{{ $comp->id }}" {{ old('competencia_id')==$comp->id ? 'selected' : '' }}>
                    {{ Str::limit($comp->nombre, 50) }}
                </option>
                @endforeach
            </select>
        </div>
        @endif

        {{-- Opciones tareas --}}
        <div id="opcionesTarea" style="display:none;">
            <hr class="my-2">
            <div class="form-check mb-2">
                <input class="form-check-input" type="checkbox" name="permite_reentrega" id="reentrega" value="1" {{ old('permite_reentrega') ? 'checked' : '' }}>
                <label class="form-check-label small" for="reentrega">Permitir reentrega</label>
            </div>
            <div class="mb-2">
                <label class="form-label fw-semibold small">Tiempo límite (minutos)</label>
                <input type="number" name="limite_tiempo" class="form-control form-control-sm" min="1" max="300" value="{{ old('limite_tiempo') }}" placeholder="Sin límite">
            </div>
        </div>

        <hr class="my-3">

        {{-- Publicación --}}
        <div class="form-check form-switch mb-2">
            <input class="form-check-input" type="checkbox" name="publicado" id="publicado" value="1" {{ old('publicado',1) ? 'checked' : '' }} onchange="togglePublicarEn()">
            <label class="form-check-label small fw-semibold" for="publicado">Publicar ahora</label>
        </div>
        <div id="divPublicarEn" style="{{ old('publicado',1) ? 'display:none;' : '' }}">
            <label class="form-label small text-muted">Publicar el:</label>
            <input type="datetime-local" name="publicar_en" class="form-control form-control-sm" value="{{ old('publicar_en') }}">
        </div>

        <div class="mt-4 d-grid gap-2">
            <button type="submit" class="btn btn-primary" style="border-radius:10px;">
                <i class="bi bi-check-lg me-1"></i>Guardar material
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

<script>
let criterioIndex = 1;

function onTipoChange() {
    const tipo = document.querySelector('input[name="tipo"]:checked')?.value;
    const esTarea = tipo === 'tarea' || tipo === 'evaluacion';
    document.getElementById('seccionRubrica').style.display = esTarea ? '' : 'none';
    document.getElementById('opcionesTarea').style.display = esTarea ? '' : 'none';
    document.getElementById('seccionFecha').style.display = tipo === 'material' ? 'none' : '';
    document.getElementById('seccionPuntos').style.display = esTarea ? '' : 'none';
}

function toggleRubrica() {
    const chk = document.getElementById('usarRubrica').checked;
    document.getElementById('rubricaForm').style.display = chk ? '' : 'none';
}

function togglePublicarEn() {
    const pub = document.getElementById('publicado').checked;
    document.getElementById('divPublicarEn').style.display = pub ? 'none' : '';
}

function agregarCriterio() {
    const container = document.getElementById('criteriosContainer');
    const div = document.createElement('div');
    div.className = 'criterio-row d-flex gap-2 mb-2 align-items-start';
    div.innerHTML = `
        <div class="flex-grow-1">
            <input type="text" name="criterios[${criterioIndex}][nombre]" class="form-control form-control-sm mb-1" placeholder="Criterio">
            <input type="text" name="criterios[${criterioIndex}][descripcion]" class="form-control form-control-sm" placeholder="Descripción opcional">
        </div>
        <div style="width:90px;">
            <input type="number" name="criterios[${criterioIndex}][puntaje_max]" class="form-control form-control-sm text-center" placeholder="Pts" min="1" value="10">
            <div class="text-muted text-center" style="font-size:.7rem;">puntos</div>
        </div>
        <button type="button" class="btn btn-sm btn-outline-danger" style="border-radius:6px;flex-shrink:0;" onclick="this.parentElement.remove()">
            <i class="bi bi-trash"></i>
        </button>`;
    container.appendChild(div);
    criterioIndex++;
}

function mostrarArchivos(input) {
    const lista = document.getElementById('listaArchivos');
    lista.innerHTML = '';
    Array.from(input.files).forEach(f => {
        const span = document.createElement('span');
        span.className = 'badge rounded-pill border text-muted';
        span.style.cssText = 'font-weight:400;font-size:.8rem;padding:6px 12px;';
        span.textContent = f.name.length > 25 ? f.name.substring(0,22)+'...' : f.name;
        lista.appendChild(span);
    });
}

document.addEventListener('DOMContentLoaded', onTipoChange);
</script>

@endsection
