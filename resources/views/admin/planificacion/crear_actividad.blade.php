@extends('layouts.admin')
@section('title', isset($planificacion) ? 'Editar Planificación por Actividad' : 'Nueva Planificación por Actividad')

@section('content')
<div class="container-fluid py-3" style="max-width:900px;">

{{-- Header --}}
<div class="d-flex align-items-center gap-2 mb-3">
    <a href="{{ route('admin.planificacion.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i>
    </a>
    <div>
        <h4 class="mb-0 fw-bold">
            <i class="bi bi-journal-plus text-success me-2"></i>
            {{ isset($planificacion) ? 'Editar' : 'Nueva' }} Planificación por Actividad de Aprendizaje
        </h4>
        <small class="text-muted">Matriz de planificación por actividad — Área Técnica</small>
    </div>
</div>

@if($errors->any())
<div class="alert alert-danger py-2">
    <ul class="mb-0 ps-3">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
</div>
@endif

@php $act = isset($planificacion) ? $planificacion->actividades->first() : null; @endphp

<form method="POST"
      action="{{ isset($planificacion) ? route('admin.planificacion.update', $planificacion) : route('admin.planificacion.store-actividad') }}">
@csrf
@if(isset($planificacion)) @method('PUT') @endif

{{-- ── Encabezado ────────────────────────────────────────────────────────────── --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-success text-white py-2 fw-bold">
        <i class="bi bi-info-circle me-2"></i>Datos del Módulo / Actividad
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label fw-semibold">Asignación <span class="text-danger">*</span></label>
                <select name="asignacion_id" class="form-select form-select-sm" required>
                    <option value="">Seleccionar módulo / asignación…</option>
                    @foreach($asignaciones as $asig)
                    <option value="{{ $asig->id }}"
                        {{ (old('asignacion_id', $planificacion->asignacion_id ?? $asignacionSeleccionada?->id) == $asig->id) ? 'selected' : '' }}>
                        {{ $asig->asignatura?->nombre }} — {{ $asig->grupo?->nombre_completo }}
                        ({{ $asig->docente?->nombre_completo }})
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold">Familia Profesional</label>
                <input type="text" name="familia_profesional" class="form-control form-control-sm"
                       value="{{ old('familia_profesional', $planificacion->familia_profesional ?? 'Informática y Comunicaciones') }}">
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold">Denominación</label>
                <input type="text" name="denominacion" class="form-control form-control-sm"
                       value="{{ old('denominacion', $planificacion->denominacion ?? '') }}">
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold">Módulo</label>
                <input type="text" name="modulo_nombre" class="form-control form-control-sm"
                       value="{{ old('modulo_nombre', $planificacion->modulo_nombre ?? '') }}">
            </div>
            <div class="col-md-3">
                <label class="form-label fw-semibold">Código MF</label>
                <input type="text" name="mf_codigo" class="form-control form-control-sm font-monospace"
                       placeholder="MF_057_3"
                       value="{{ old('mf_codigo', $planificacion->mf_codigo ?? '') }}">
            </div>
            <div class="col-md-3">
                <label class="form-label fw-semibold">Código UC</label>
                <input type="text" name="uc_codigo" class="form-control form-control-sm font-monospace"
                       placeholder="UC_054_3"
                       value="{{ old('uc_codigo', $planificacion->uc_codigo ?? '') }}">
            </div>
            <div class="col-md-2">
                <label class="form-label fw-semibold">Sesión</label>
                <input type="text" name="sesion" class="form-control form-control-sm"
                       placeholder="5to A, B"
                       value="{{ old('sesion', $planificacion->sesion ?? '') }}">
            </div>
            <div class="col-md-2">
                <label class="form-label fw-semibold">Nivel</label>
                <input type="text" name="nivel" class="form-control form-control-sm"
                       placeholder="3"
                       value="{{ old('nivel', $planificacion->nivel ?? '') }}">
            </div>
            <div class="col-md-2">
                <label class="form-label fw-semibold">Horas</label>
                <input type="number" name="horas" class="form-control form-control-sm"
                       step="0.5" min="0"
                       value="{{ old('horas', $planificacion->horas ?? '') }}">
            </div>
            <div class="col-md-3">
                <label class="form-label fw-semibold">Fecha Inicio</label>
                <input type="date" name="fecha_inicio" class="form-control form-control-sm"
                       value="{{ old('fecha_inicio', $planificacion?->fecha_inicio?->format('Y-m-d') ?? '') }}">
            </div>
            <div class="col-md-3">
                <label class="form-label fw-semibold">Fecha Final</label>
                <input type="date" name="fecha_fin" class="form-control form-control-sm"
                       value="{{ old('fecha_fin', $planificacion?->fecha_fin?->format('Y-m-d') ?? '') }}">
            </div>
        </div>
    </div>
</div>

{{-- ── RA y Actividad ───────────────────────────────────────────────────────── --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-primary text-white py-2 fw-bold">
        <i class="bi bi-bookmark-check me-2"></i>Recurso de Aprendizaje (RA) y Actividad
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-2">
                <label class="form-label fw-semibold">Código RA</label>
                <input type="text" name="ra_codigo" class="form-control form-control-sm font-monospace"
                       placeholder="RA2.1"
                       value="{{ old('ra_codigo', $act->ra_codigo ?? '') }}">
            </div>
            <div class="col-md-8">
                <label class="form-label fw-semibold">Descripción del RA</label>
                <textarea name="ra_descripcion" class="form-control form-control-sm" rows="2"
                          placeholder="Evaluar y aplicar los lenguajes de programación…">{{ old('ra_descripcion', $act->ra_descripcion ?? '') }}</textarea>
            </div>
            <div class="col-md-2">
                <label class="form-label fw-semibold">Nº Actividad</label>
                <input type="number" name="actividad_numero" class="form-control form-control-sm"
                       min="1" placeholder="11"
                       value="{{ old('actividad_numero', $act->actividad_numero ?? '') }}">
            </div>
            <div class="col-12">
                <label class="form-label fw-semibold">Objetivo de la Actividad</label>
                <textarea name="objetivo" class="form-control form-control-sm" rows="2"
                          placeholder="Crear la estructura básica de una página web utilizando HTML…">{{ old('objetivo', $act->objetivo ?? '') }}</textarea>
            </div>
        </div>
    </div>
</div>

{{-- ── Descripción de la Actividad ──────────────────────────────────────────── --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-info text-white py-2 fw-bold">
        <i class="bi bi-layout-text-window me-2"></i>Descripción de la Actividad
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-12">
                <label class="form-label fw-semibold">
                    <span class="badge bg-primary me-1">INICIO</span>
                    Actividad de Inicio
                </label>
                <textarea name="act_inicio" class="form-control form-control-sm" rows="3"
                          placeholder="Saludo. Pase de lista. Oración. Presenta la frase del día. Retroalimentación clase anterior...">{{ old('act_inicio', $act->act_inicio ?? '') }}</textarea>
            </div>
            <div class="col-12">
                <label class="form-label fw-semibold">
                    <span class="badge bg-success me-1">DESARROLLO</span>
                    Actividad de Desarrollo <small class="text-muted fw-normal">(conceptual / procedimental y/o actitudinal)</small>
                </label>
                <textarea name="act_desarrollo" class="form-control form-control-sm" rows="5"
                          placeholder="Ejemplo en vivo: Muestre un ejemplo simple de código HTML...&#10;Práctica: Pida a los estudiantes que abran sus editores...&#10;Compartir y discutir: ...">{{ old('act_desarrollo', $act->act_desarrollo ?? '') }}</textarea>
            </div>
            <div class="col-12">
                <label class="form-label fw-semibold">
                    <span class="badge bg-warning text-dark me-1">CIERRE</span>
                    Actividad de Generalización o Cierre
                </label>
                <textarea name="act_cierre" class="form-control form-control-sm" rows="3"
                          placeholder="Aclaración de dudas. Resumen por parte de los estudiantes. El profesor realiza síntesis...">{{ old('act_cierre', $act->act_cierre ?? '') }}</textarea>
            </div>
        </div>
    </div>
</div>

{{-- ── Estrategias, Recursos e Instrumentos ────────────────────────────────── --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-secondary text-white py-2 fw-bold">
        <i class="bi bi-tools me-2"></i>Estrategias, Recursos e Instrumentos
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label fw-semibold">Estrategias</label>
                <textarea name="estrategias" class="form-control form-control-sm" rows="3"
                          placeholder="Uso de ejemplos en vivo para una comprensión más clara.&#10;Fomento de la participación activa...">{{ old('estrategias', $act->estrategias ?? '') }}</textarea>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold">Recursos</label>
                <textarea name="recursos" class="form-control form-control-sm" rows="3"
                          placeholder="Laptop, TV, celulares, cuaderno físico y digital, computadores del laboratorio.">{{ old('recursos', $act->recursos ?? '') }}</textarea>
            </div>
            <div class="col-12">
                <label class="form-label fw-semibold">Instrumentos de Evaluación</label>
                <textarea name="instrumentos_evaluacion" class="form-control form-control-sm" rows="3"
                          placeholder="Indagación de saberes previos.&#10;Observación directa.&#10;Preguntas y respuestas.&#10;Evaluación de las páginas web creadas...">{{ old('instrumentos_evaluacion', $act->instrumentos_evaluacion ?? '') }}</textarea>
            </div>
        </div>
    </div>
</div>

{{-- ── Publicar ──────────────────────────────────────────────────────────────── --}}
<div class="d-flex align-items-center justify-content-between gap-2 mb-4">
    <div class="form-check">
        <input type="hidden" name="publicado" value="0">
        <input type="checkbox" name="publicado" id="publicado" value="1" class="form-check-input"
               {{ old('publicado', $planificacion->publicado ?? false) ? 'checked' : '' }}>
        <label class="form-check-label" for="publicado">Publicar planificación</label>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.planificacion.index') }}" class="btn btn-outline-secondary">Cancelar</a>
        <button type="submit" class="btn btn-success">
            <i class="bi bi-save me-1"></i>{{ isset($planificacion) ? 'Actualizar' : 'Guardar' }} Planificación
        </button>
    </div>
</div>

</form>
</div>
@endsection
