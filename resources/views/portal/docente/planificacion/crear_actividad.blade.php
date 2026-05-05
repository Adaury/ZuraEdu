@extends('layouts.portal')
@section('page-title', isset($planificacion) ? 'Editar Planificación por Actividad' : 'Nueva Planificación por Actividad')
@section('portal-name', 'Portal Docente')

@section('sidebar')
    @include('portal.docente._sidebar_clase', ['activeKey' => 'planificacion'])
@endsection

@section('bottom-nav')
    <a href="{{ route('portal.docente.calificaciones', $asignacion) }}" class="prt-nav-item">
        <i class="bi bi-journal-check"></i>Notas
    </a>
    <a href="{{ route('portal.docente.planificacion.index', $asignacion) }}" class="prt-nav-item active">
        <i class="bi bi-journal-text"></i>Planif.
    </a>
    <a href="{{ route('portal.docente.boletines', $asignacion) }}" class="prt-nav-item">
        <i class="bi bi-file-earmark-text"></i>Boletines
    </a>
@endsection

@push('styles')
<style>
.prt-field-lbl { font-size:.75rem; font-weight:700; color:#374151; margin-bottom:.25rem; display:block; }
.prt-inp { width:100%; border:1px solid #cbd5e1; border-radius:7px; padding:.45rem .7rem; font-size:.82rem; background:#fff; color:#1e293b; }
.prt-inp:focus { outline:none; border-color:#2563eb; box-shadow:0 0 0 2px #bfdbfe; }
[data-theme="dark"] .prt-inp { background:#1e293b; border-color:#334155; color:#e2e8f0; }
.act-badge-bar { display:inline-block; border-radius:5px; padding:.15rem .5rem; font-size:.72rem; font-weight:700; margin-bottom:.3rem; }
</style>
@endpush

@section('content')

<div style="display:flex;align-items:center;gap:.75rem;margin-bottom:1rem;flex-wrap:wrap;">
    <a href="{{ route('portal.docente.planificacion.index', $asignacion) }}"
       style="background:#f1f5f9;color:#374151;border-radius:8px;padding:.4rem .85rem;font-size:.8rem;text-decoration:none;display:flex;align-items:center;gap:.4rem;">
        <i class="bi bi-arrow-left"></i>Volver
    </a>
    <div>
        <h1 style="font-size:1rem;font-weight:800;margin:0;">
            <i class="bi bi-journal-plus" style="color:#15803d;"></i>
            {{ isset($planificacion) ? 'Editar Planificación por Actividad' : 'Nueva Planificación por Actividad' }}
        </h1>
        <div style="font-size:.75rem;color:#64748b;">
            {{ $asignacion->asignatura?->nombre }} — {{ $asignacion->grupo?->nombre_completo }}
        </div>
    </div>
</div>

@if($errors->any())
<div style="background:#fee2e2;color:#dc2626;border-radius:8px;padding:.65rem 1rem;margin-bottom:.75rem;font-size:.8rem;">
    <ul style="margin:0;padding-left:1.2rem;">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
</div>
@endif

@if(isset($planificacion))
<form method="POST" action="{{ route('portal.docente.planificacion.update', [$asignacion, $planificacion]) }}">
@csrf @method('PUT')
@else
<form method="POST" action="{{ route('portal.docente.planificacion.store-actividad', $asignacion) }}">
@csrf
@endif

{{-- Encabezado --}}
<div class="prt-card" style="margin-bottom:.75rem;">
    <div class="prt-card-header">
        <i class="bi bi-info-circle" style="color:#15803d;font-size:1rem;"></i>
        <h3>Datos del Módulo</h3>
    </div>
@php $plan = $planificacion ?? null; $act = $plan?->actividades->first(); @endphp
    <div style="padding:.85rem;display:grid;grid-template-columns:1fr 1fr;gap:.65rem;">
        <div style="grid-column:1/-1;">
            <label class="prt-field-lbl">Familia Profesional</label>
            <input type="text" name="familia_profesional" class="prt-inp"
                   value="{{ old('familia_profesional', $plan?->familia_profesional ?? 'Informática y Comunicaciones') }}">
        </div>
        <div>
            <label class="prt-field-lbl">Denominación</label>
            <input type="text" name="denominacion" class="prt-inp" value="{{ old('denominacion', $plan?->denominacion) }}">
        </div>
        <div>
            <label class="prt-field-lbl">Módulo</label>
            <input type="text" name="modulo_nombre" class="prt-inp"
                   value="{{ old('modulo_nombre', $plan?->modulo_nombre ?? $asignacion->asignatura?->nombre) }}">
        </div>
        <div>
            <label class="prt-field-lbl">Código MF</label>
            <input type="text" name="mf_codigo" class="prt-inp" placeholder="MF_057_3" value="{{ old('mf_codigo', $plan?->mf_codigo) }}">
        </div>
        <div>
            <label class="prt-field-lbl">Código UC</label>
            <input type="text" name="uc_codigo" class="prt-inp" placeholder="UC_054_3" value="{{ old('uc_codigo', $plan?->uc_codigo) }}">
        </div>
        <div>
            <label class="prt-field-lbl">Sesión</label>
            <input type="text" name="sesion" class="prt-inp"
                   value="{{ old('sesion', $plan?->sesion ?? $asignacion->grupo?->nombre_completo) }}" placeholder="5to A, B">
        </div>
        <div>
            <label class="prt-field-lbl">Nivel</label>
            <input type="text" name="nivel" class="prt-inp" placeholder="3" value="{{ old('nivel', $plan?->nivel) }}">
        </div>
        <div>
            <label class="prt-field-lbl">Horas</label>
            <input type="number" name="horas" class="prt-inp" step="0.5" min="0"
                   value="{{ old('horas', $plan?->horas ?? $asignacion->horas_semana) }}">
        </div>
        <div>
            <label class="prt-field-lbl">Fecha Inicio</label>
            <input type="date" name="fecha_inicio" class="prt-inp" value="{{ old('fecha_inicio', $plan?->fecha_inicio?->format('Y-m-d')) }}">
        </div>
        <div>
            <label class="prt-field-lbl">Fecha Final</label>
            <input type="date" name="fecha_fin" class="prt-inp" value="{{ old('fecha_fin', $plan?->fecha_fin?->format('Y-m-d')) }}">
        </div>
    </div>
</div>

{{-- RA y número de actividad --}}
<div class="prt-card" style="margin-bottom:.75rem;">
    <div class="prt-card-header">
        <i class="bi bi-bookmark-check" style="color:#1d4ed8;font-size:1rem;"></i>
        <h3>Recurso de Aprendizaje (RA) y Actividad</h3>
    </div>
    <div style="padding:.85rem;display:grid;grid-template-columns:1fr 1fr;gap:.65rem;">
        <div>
            <label class="prt-field-lbl">Código RA</label>
            <input type="text" name="ra_codigo" class="prt-inp" placeholder="RA2.1" value="{{ old('ra_codigo', $act?->ra_codigo) }}">
        </div>
        <div>
            <label class="prt-field-lbl">Nº Actividad</label>
            <input type="number" name="actividad_numero" class="prt-inp" min="1" placeholder="11" value="{{ old('actividad_numero', $act?->actividad_numero) }}">
        </div>
        <div style="grid-column:1/-1;">
            <label class="prt-field-lbl">Descripción del RA</label>
            <textarea name="ra_descripcion" class="prt-inp" rows="2"
                      placeholder="Evaluar y aplicar los lenguajes de programación…">{{ old('ra_descripcion', $act?->ra_descripcion) }}</textarea>
        </div>
        <div style="grid-column:1/-1;">
            <label class="prt-field-lbl">Objetivo de la Actividad</label>
            <textarea name="objetivo" class="prt-inp" rows="2"
                      placeholder="Crear la estructura básica de una página web utilizando HTML…">{{ old('objetivo', $act?->objetivo) }}</textarea>
        </div>
    </div>
</div>

{{-- Descripción de la actividad --}}
<div class="prt-card" style="margin-bottom:.75rem;">
    <div class="prt-card-header">
        <i class="bi bi-layout-text-window" style="color:#0ea5e9;font-size:1rem;"></i>
        <h3>Descripción de la Actividad</h3>
    </div>
    <div style="padding:.85rem;display:flex;flex-direction:column;gap:.7rem;">
        <div>
            <span class="act-badge-bar" style="background:#dbeafe;color:#1d4ed8;">INICIO</span>
            <label class="prt-field-lbl">Actividad de Inicio</label>
            <textarea name="act_inicio" class="prt-inp" rows="3"
                      placeholder="Saludo. Pase de lista. Oración. Presenta la frase del día. Retroalimentación…">{{ old('act_inicio', $act?->act_inicio) }}</textarea>
        </div>
        <div>
            <span class="act-badge-bar" style="background:#dcfce7;color:#15803d;">DESARROLLO</span>
            <label class="prt-field-lbl">Actividad de Desarrollo <span style="font-weight:400;color:#94a3b8;">(conceptual / procedimental y/o actitudinal)</span></label>
            <textarea name="act_desarrollo" class="prt-inp" rows="5"
                      placeholder="Ejemplo en vivo: Muestre un ejemplo simple de código HTML...&#10;Práctica: Pida a los estudiantes que abran sus editores...&#10;Compartir y discutir: …">{{ old('act_desarrollo', $act?->act_desarrollo) }}</textarea>
        </div>
        <div>
            <span class="act-badge-bar" style="background:#fef3c7;color:#92400e;">CIERRE</span>
            <label class="prt-field-lbl">Actividad de Generalización o Cierre</label>
            <textarea name="act_cierre" class="prt-inp" rows="3"
                      placeholder="Aclaración de dudas. Resumen por parte de los estudiantes. El profesor realiza síntesis…">{{ old('act_cierre', $act?->act_cierre) }}</textarea>
        </div>
    </div>
</div>

{{-- Estrategias, Recursos e Instrumentos --}}
<div class="prt-card" style="margin-bottom:.75rem;">
    <div class="prt-card-header">
        <i class="bi bi-tools" style="color:#7c3aed;font-size:1rem;"></i>
        <h3>Estrategias, Recursos e Instrumentos</h3>
    </div>
    <div style="padding:.85rem;display:grid;grid-template-columns:1fr 1fr;gap:.65rem;">
        <div>
            <label class="prt-field-lbl">Estrategias</label>
            <textarea name="estrategias" class="prt-inp" rows="3"
                      placeholder="Uso de ejemplos en vivo.&#10;Fomento de participación activa…">{{ old('estrategias', $act?->estrategias) }}</textarea>
        </div>
        <div>
            <label class="prt-field-lbl">Recursos</label>
            <textarea name="recursos" class="prt-inp" rows="3"
                      placeholder="Laptop, TV, celulares, cuaderno físico y digital, computadores del laboratorio.">{{ old('recursos', $act?->recursos) }}</textarea>
        </div>
        <div style="grid-column:1/-1;">
            <label class="prt-field-lbl">Instrumentos de Evaluación</label>
            <textarea name="instrumentos_evaluacion" class="prt-inp" rows="3"
                      placeholder="Indagación de saberes previos.&#10;Observación directa.&#10;Preguntas y respuestas.&#10;Evaluación de las páginas web creadas…">{{ old('instrumentos_evaluacion', $act?->instrumentos_evaluacion) }}</textarea>
        </div>
    </div>
</div>

{{-- Botones --}}
<div style="display:flex;justify-content:flex-end;gap:.5rem;margin-bottom:1.5rem;">
    <a href="{{ route('portal.docente.planificacion.index', $asignacion) }}"
       style="background:#f1f5f9;color:#374151;border-radius:8px;padding:.5rem 1.2rem;font-size:.82rem;font-weight:700;text-decoration:none;">
        Cancelar
    </a>
    <button type="submit"
            style="background:#15803d;color:#fff;border:none;border-radius:8px;padding:.5rem 1.4rem;font-size:.82rem;font-weight:700;cursor:pointer;">
        <i class="bi bi-save me-1"></i>{{ isset($planificacion) ? 'Actualizar Planificación' : 'Guardar Planificación' }}
    </button>
</div>

</form>
@endsection
