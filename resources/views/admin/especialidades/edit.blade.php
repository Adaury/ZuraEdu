@extends('layouts.admin')

@section('page-title', 'Editar Especialidad')

@section('content')
<div class="mb-4">
    <h4 class="fw-bold mb-0" style="color:#1e3a6e;"><i class="bi bi-mortarboard me-2"></i>Editar: {{ $especialidad->nombre }}</h4>
    <nav aria-label="breadcrumb"><ol class="breadcrumb mb-0" style="font-size:.8rem;">
        <li class="breadcrumb-item"><a href="{{ route('admin.especialidades.index') }}">Especialidades</a></li>
        <li class="breadcrumb-item active">Editar</li>
    </ol></nav>
</div>

<div class="row g-4">
    {{-- Formulario principal --}}
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <form method="POST" action="{{ route('admin.especialidades.update', $especialidad) }}">
                    @csrf @method('PUT')
                    @include('admin.especialidades._form')
                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle me-1"></i>Guardar Cambios
                        </button>
                        <a href="{{ route('admin.especialidades.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Panel de docentes --}}
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0" style="font-size:.875rem;">
                    <i class="bi bi-people me-2"></i>Docentes Asignados
                    <span class="badge bg-secondary ms-1">{{ $especialidad->docentes->count() }}</span>
                </h6>
            </div>
            <div class="card-body p-3">
                {{-- Agregar docente --}}
                <form method="POST" action="{{ route('admin.especialidades.asignarDocente', $especialidad) }}" class="mb-3">
                    @csrf
                    <div class="mb-2">
                        <select name="docente_id" class="form-select form-select-sm" required>
                            <option value="">— Seleccionar docente —</option>
                            @foreach($docentes as $doc)
                            @if(!in_array($doc->id, $docentesAsignados))
                            <option value="{{ $doc->id }}">{{ $doc->nombre_completo }}</option>
                            @endif
                            @endforeach
                        </select>
                    </div>
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="form-check">
                            <input type="checkbox" name="es_coordinador" value="1" class="form-check-input" id="esCoord">
                            <label class="form-check-label" for="esCoord" style="font-size:.8rem;">Es coordinador</label>
                        </div>
                        <button type="submit" class="btn btn-sm btn-outline-primary" style="font-size:.78rem;">
                            <i class="bi bi-plus me-1"></i>Agregar
                        </button>
                    </div>
                </form>

                <hr class="my-2">

                {{-- Lista de docentes asignados --}}
                @forelse($especialidad->docentes as $doc)
                <div class="d-flex align-items-center gap-2 py-2 border-bottom" style="border-color:#f0f4f8 !important;">
                    <div style="width:32px;height:32px;border-radius:50%;background:{{ $especialidad->color }};color:#fff;
                                font-weight:700;font-size:.75rem;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        {{ strtoupper(substr($doc->nombres, 0, 1) . substr($doc->apellidos, 0, 1)) }}
                    </div>
                    <div class="flex-grow-1 min-w-0">
                        <div class="fw-semibold text-truncate" style="font-size:.82rem;color:#1e293b;">{{ $doc->nombre_completo }}</div>
                        @if($doc->pivot->es_coordinador)
                        <span class="badge text-bg-warning" style="font-size:.6rem;">Coordinador</span>
                        @endif
                    </div>
                    <form method="POST" action="{{ route('admin.especialidades.removerDocente', [$especialidad, $doc]) }}">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-outline-danger"
                                style="font-size:.68rem;padding:.15rem .4rem;"
                                title="Remover" onclick="return confirm('¿Remover a {{ $doc->nombre_completo }}?')">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </form>
                </div>
                @empty
                <p class="text-muted text-center py-2" style="font-size:.82rem;">Sin docentes asignados.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
