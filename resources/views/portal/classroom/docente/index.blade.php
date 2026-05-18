@extends('layouts.admin')
@section('page-title', 'Mi Classroom')
@section('content')

@php
use Illuminate\Support\Str;
// Todas las asignaciones del docente para el selector de duplicación
$docente    = auth()->user()->docente ?? null;
$schoolYear = \App\Models\SchoolYear::actual();
$asignaciones = ($docente && $schoolYear)
    ? \App\Models\Asignacion::with(['asignatura','grupo.grado','grupo.seccion'])
        ->where('docente_id', $docente->id)
        ->where('school_year_id', $schoolYear->id)
        ->where('activo', true)
        ->get()
    : collect();
@endphp

<div class="mb-4">
    <h4 class="fw-bold mb-1"><i class="bi bi-easel2-fill me-2" style="color:#3B82F6;"></i>Mi Classroom</h4>
    <p class="text-muted small mb-0">Tus aulas virtuales activas</p>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show mb-3"><i class="bi bi-check-circle me-2"></i>{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif

@if($clases->isEmpty())
<div class="text-center py-5 text-muted">
    <i class="bi bi-easel2" style="font-size:3rem;color:#CBD5E1;display:block;margin-bottom:.75rem;"></i>
    <p class="fw-semibold mb-1">No tienes aulas virtuales</p>
    <small>Las aulas se crean desde el panel administrativo por asignación</small>
</div>
@else
<div class="row g-3">
@foreach($clases as $clase)
<div class="col-md-6 col-xl-4">
    <div class="card h-100 border-0 shadow-sm" style="border-radius:16px;overflow:hidden;">
        <div style="background:{{ $clase->portada_color ?? '#3B82F6' }};height:10px;"></div>
        <div class="card-body d-flex flex-column">
            <a href="{{ route('portal.docente.classroom.show', $clase) }}" class="text-decoration-none flex-grow-1">
                <h6 class="fw-bold mb-1" style="color:#111827;">{{ $clase->nombre }}</h6>
                <small class="text-muted d-block mb-2">{{ $clase->asignacion->asignatura?->nombre }} &bull; {{ $clase->asignacion->grupo?->nombre_completo ?? $clase->asignacion->grupo?->nombre }}</small>
                @if($clase->descripcion)<p class="text-muted small mb-3" style="font-size:.8rem;line-height:1.4;">{{ Str::limit($clase->descripcion, 70) }}</p>@endif
                <div class="d-flex gap-3 small text-muted mb-3">
                    <span><i class="bi bi-files me-1"></i>{{ $clase->materiales->count() }} materiales</span>
                    <span><i class="bi bi-pencil me-1"></i>{{ $clase->materiales->whereIn('tipo',['tarea','evaluacion'])->count() }} tareas</span>
                </div>
            </a>
            {{-- Botón duplicar --}}
            <button type="button"
                class="btn btn-sm btn-outline-secondary w-100 mt-auto"
                style="border-radius:8px;font-size:.78rem;"
                onclick="abrirModalDuplicar({{ $clase->id }}, '{{ addslashes($clase->nombre) }}', {{ $clase->asignacion_id }})">
                <i class="bi bi-copy me-1"></i> Duplicar a otro grupo
            </button>
        </div>
    </div>
</div>
@endforeach
</div>
@endif

{{-- ── Modal Duplicar ── --}}
<div class="modal fade" id="modalDuplicar" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow" style="border-radius:16px;overflow:hidden;">
            <div class="modal-header border-0 pb-0">
                <h6 class="modal-title fw-800">
                    <i class="bi bi-copy me-2" style="color:#3b82f6;"></i>Duplicar Aula Virtual
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formDuplicar" method="POST">
                @csrf
                <div class="modal-body pt-3">
                    <p class="text-muted small mb-3">
                        Se copiará el aula con todos sus materiales (despublicados) hacia la asignación que elijas.
                        Los archivos adjuntos, entregas y mensajes <strong>no</strong> se copian.
                    </p>

                    <div class="mb-3">
                        <label class="form-label fw-700 small">Nombre del aula nueva</label>
                        <input type="text" name="nombre" id="duplicarNombre" class="form-control form-control-sm" required maxlength="255">
                        <div class="form-text">Puedes cambiar el nombre para identificar el grupo destino.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-700 small">Grupo / Asignación destino</label>
                        <select name="asignacion_id" class="form-select form-select-sm" required id="duplicarAsignacion">
                            <option value="">— Selecciona un grupo —</option>
                            @foreach($asignaciones as $asig)
                            <option value="{{ $asig->id }}"
                                data-nombre="{{ $asig->asignatura?->nombre }} — {{ $asig->grupo?->nombre_completo ?? $asig->grupo?->nombre }}">
                                {{ $asig->asignatura?->nombre }} — {{ $asig->grupo?->nombre_completo ?? $asig->grupo?->nombre }}
                            </option>
                            @endforeach
                        </select>
                        <div class="form-text" id="duplicarAsignacionHint"></div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-sm btn-primary fw-700">
                        <i class="bi bi-copy me-1"></i> Crear copia
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('realtime-data')
<script>
window._SGE_CLASE_IDS = {!! $clases->pluck('id')->values()->toJson() !!};
</script>
@endpush

<script>
const _duplicarRoutes = @json($clases->pluck('id')->mapWithKeys(fn($id) => [$id => route('portal.docente.classroom.duplicar', $id)]));

function abrirModalDuplicar(claseId, nombre, asignacionOrigenId) {
    document.getElementById('formDuplicar').action = _duplicarRoutes[claseId];
    document.getElementById('duplicarNombre').value = nombre;

    // Pre-seleccionar la asignación origen y marcarla como "(origen)"
    const sel = document.getElementById('duplicarAsignacion');
    sel.value = '';
    Array.from(sel.options).forEach(opt => {
        if (opt.value == asignacionOrigenId) {
            opt.text = opt.dataset.nombre + ' (actual)';
            opt.disabled = true;
        } else {
            opt.text = opt.dataset.nombre;
            opt.disabled = false;
        }
    });

    new bootstrap.Modal(document.getElementById('modalDuplicar')).show();
}
</script>

@endsection
