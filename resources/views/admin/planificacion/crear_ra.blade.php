@extends('layouts.admin')
@section('page-title', isset($planificacion) ? 'Editar Planificación por RA' : 'Nueva Planificación por RA')

@section('content')
<div class="container-fluid py-3" style="max-width:1100px;">

{{-- Header --}}
<div class="d-flex align-items-center gap-2 mb-3">
    <a href="{{ route('admin.planificacion.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i>
    </a>
    <div>
        <h4 class="mb-0 fw-bold">
            <i class="bi bi-journal-text text-primary me-2"></i>
            {{ isset($planificacion) ? 'Editar' : 'Nueva' }} Planificación por Resultados de Aprendizaje
        </h4>
        <small class="text-muted">Matriz de planificación por RA — Área Técnica</small>
    </div>
</div>

@if($errors->any())
<div class="alert alert-danger py-2">
    <ul class="mb-0 ps-3">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
</div>
@endif

<form method="POST"
      action="{{ isset($planificacion) ? route('admin.planificacion.update', $planificacion) : route('admin.planificacion.store-ra') }}">
@csrf
@if(isset($planificacion)) @method('PUT') @endif

{{-- ── Encabezado ────────────────────────────────────────────────────────────── --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-primary text-white py-2 fw-bold">
        <i class="bi bi-info-circle me-2"></i>Datos del Módulo
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
                       placeholder="MF_060_3"
                       value="{{ old('mf_codigo', $planificacion->mf_codigo ?? '') }}">
            </div>
            <div class="col-md-3">
                <label class="form-label fw-semibold">Sesión</label>
                <input type="text" name="sesion" class="form-control form-control-sm"
                       placeholder="6to A"
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
            <div class="col-md-2">
                <label class="form-label fw-semibold">Fecha Inicio</label>
                <input type="date" name="fecha_inicio" class="form-control form-control-sm"
                       value="{{ old('fecha_inicio', $planificacion?->fecha_inicio?->format('Y-m-d') ?? '') }}">
            </div>
            <div class="col-md-2">
                <label class="form-label fw-semibold">Fecha Final</label>
                <input type="date" name="fecha_fin" class="form-control form-control-sm"
                       value="{{ old('fecha_fin', $planificacion?->fecha_fin?->format('Y-m-d') ?? '') }}">
            </div>
            <div class="col-12">
                <label class="form-label fw-semibold">Unidad de Competencia (UC)</label>
                <textarea name="uc_codigo" class="form-control form-control-sm" rows="2"
                          placeholder="UC_060_Desarrollar e implementar…">{{ old('uc_codigo', $planificacion->uc_codigo ?? '') }}</textarea>
            </div>
        </div>
    </div>
</div>

{{-- ── Resultados de Aprendizaje ─────────────────────────────────────────────── --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-success text-white py-2 d-flex justify-content-between align-items-center">
        <span class="fw-bold"><i class="bi bi-list-check me-2"></i>Resultados de Aprendizaje (RA)</span>
        <button type="button" class="btn btn-light btn-sm py-0" onclick="agregarRA()">
            <i class="bi bi-plus-circle me-1"></i>Agregar RA
        </button>
    </div>
    <div class="card-body p-0">
        <div id="ra-container">
        @php
            $raItems = old('ra', isset($planificacion) ? $planificacion->raItems->map(function($item) {
                return [
                    'ra_codigo'               => $item->ra_codigo,
                    'ra_descripcion'          => $item->ra_descripcion,
                    'nivel_taxonomico'        => $item->nivel_taxonomico,
                    'elementos_capacidad'     => collect($item->elementos_capacidad ?? [])->pluck('descripcion')->implode("\n"),
                    'fechas_desde'            => collect($item->fechas ?? [])->pluck('desde')->toArray(),
                    'fechas_hasta'            => collect($item->fechas ?? [])->pluck('hasta')->toArray(),
                    'actividades'             => $item->actividades,
                    'instrumentos_evaluacion' => $item->instrumentos_evaluacion,
                    'contenidos'              => $item->contenidos,
                ];
            })->toArray() : []);
            if (empty($raItems)) $raItems = [[]]; // al menos 1 vacío
        @endphp
        @foreach($raItems as $idx => $raItem)
        @include('admin.planificacion._ra_item', ['idx' => $idx, 'raItem' => $raItem])
        @endforeach
        </div>
    </div>
</div>

{{-- ── Publicar ──────────────────────────────────────────────────────────────── --}}
<div class="d-flex align-items-center justify-content-between gap-2 mb-4">
    <div class="form-check">
        <input type="hidden" name="publicado" value="0">
        <input type="checkbox" name="publicado" id="publicado" value="1" class="form-check-input"
               {{ old('publicado', $planificacion->publicado ?? false) ? 'checked' : '' }}>
        <label class="form-check-label" for="publicado">Publicar planificación (visible para el docente)</label>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.planificacion.index') }}" class="btn btn-outline-secondary">Cancelar</a>
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-save me-1"></i>{{ isset($planificacion) ? 'Actualizar' : 'Guardar' }} Planificación
        </button>
    </div>
</div>

</form>
</div>

{{-- Template oculto para nuevo RA --}}
<template id="ra-template">
    @include('admin.planificacion._ra_item', ['idx' => '__IDX__', 'raItem' => []])
</template>

@push('scripts')
<script>
let raCount = {{ count($raItems) }};

function agregarRA() {
    const tpl = document.getElementById('ra-template').innerHTML.replace(/__IDX__/g, raCount);
    document.getElementById('ra-container').insertAdjacentHTML('beforeend', tpl);
    raCount++;
}

function eliminarRA(btn) {
    const bloque = btn.closest('.ra-bloque');
    if (document.querySelectorAll('.ra-bloque').length > 1) {
        bloque.remove();
    } else {
        alert('Debe haber al menos un Resultado de Aprendizaje.');
    }
}

function agregarFecha(btn) {
    const cont = btn.closest('.fechas-container').querySelector('.fechas-list');
    const idx  = btn.dataset.idx;
    const html = `<div class="row g-1 mb-1 fecha-row align-items-center">
        <div class="col-5"><input type="date" name="ra[${idx}][fechas_desde][]" class="form-control form-control-sm"></div>
        <div class="col-5"><input type="date" name="ra[${idx}][fechas_hasta][]" class="form-control form-control-sm"></div>
        <div class="col-2"><button type="button" class="btn btn-outline-danger btn-sm py-0 px-1" onclick="this.closest('.fecha-row').remove()"><i class="bi bi-x"></i></button></div>
    </div>`;
    cont.insertAdjacentHTML('beforeend', html);
}
</script>
@endpush
@endsection
