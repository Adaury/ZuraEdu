@extends('layouts.admin')
@section('page-title', 'Editar Tutoría')

@push('styles')
<style>
    .form-card { background:#fff; border:1px solid #e5e7eb; border-radius:14px; box-shadow:0 2px 12px rgba(30,58,110,.06); }
    .form-section-title { font-size:.7rem; font-weight:700; letter-spacing:.1em; text-transform:uppercase; color:var(--primary); margin-bottom:1rem; padding-bottom:.4rem; border-bottom:1px solid #e5e7eb; }
    .form-label { font-size:.8rem; font-weight:600; color:#374151; margin-bottom:.3rem; }
    .form-control, .form-select { border-radius:8px; border:1px solid #d1d5db; font-size:.875rem; padding:.5rem .75rem; transition:border-color .15s, box-shadow .15s; }
    .form-control:focus, .form-select:focus { border-color:var(--primary); box-shadow:0 0 0 3px rgba(30,58,110,.1); }
    [data-theme="dark"] .form-card { background:#1e293b; border-color:#334155; }
    [data-theme="dark"] .form-section-title { border-color:#334155; }
    [data-theme="dark"] .form-label { color:#cbd5e1; }
    [data-theme="dark"] .form-control, [data-theme="dark"] .form-select { background:#0f172a; border-color:#334155; color:#e2e8f0; }
</style>
@endpush

@section('content')

<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb mb-0" style="font-size:.8rem;">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="text-decoration-none">Inicio</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.tutorias.index') }}" class="text-decoration-none">Tutorías</a></li>
        <li class="breadcrumb-item active">Editar</li>
    </ol>
</nav>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h1 class="h4 fw-bold mb-0" style="color:var(--primary);">
            <i class="bi bi-pencil-square me-2"></i>Editar Tutoría
        </h1>
        <p class="text-muted mb-0 mt-1" style="font-size:.82rem;">
            Grupo: <strong>{{ $tutoria->grupo->nombre_completo ?? '—' }}</strong>
        </p>
    </div>
    <a href="{{ route('admin.tutorias.index') }}" class="btn btn-sm btn-outline-secondary" style="border-radius:8px;">
        <i class="bi bi-arrow-left me-1"></i>Volver
    </a>
</div>

@if($errors->any())
<div class="alert alert-danger border-0 shadow-sm mb-3" style="border-radius:10px;">
    <i class="bi bi-exclamation-triangle-fill me-2"></i>
    {{ $errors->first() }}
</div>
@endif

<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="form-card p-4">
            <form action="{{ route('admin.tutorias.update', $tutoria) }}" method="POST" novalidate>
                @csrf @method('PUT')

                {{-- Grupo (solo lectura) --}}
                <div class="mb-3 p-3 rounded" style="background:#f8fafc;border:1px solid #e5e7eb;">
                    <div style="font-size:.7rem;text-transform:uppercase;font-weight:700;color:#6b7280;letter-spacing:.08em;">Grupo asignado (no editable)</div>
                    <div class="fw-bold mt-1" style="color:#1e293b;">{{ $tutoria->grupo->nombre_completo ?? '—' }}</div>
                    <div style="font-size:.75rem;color:#9ca3af;">{{ $tutoria->schoolYear->nombre ?? '' }}</div>
                </div>

                <p class="form-section-title"><i class="bi bi-person-badge me-1"></i>Docente Tutor</p>
                <div class="mb-3">
                    <label class="form-label" for="docente_id">Docente <span class="text-danger">*</span></label>
                    <select name="docente_id" id="docente_id"
                            class="form-select @error('docente_id') is-invalid @enderror" required>
                        <option value="">— Seleccionar docente —</option>
                        @foreach($docentes as $d)
                        <option value="{{ $d->id }}" @selected(old('docente_id', $tutoria->docente_id) == $d->id)>
                            {{ $d->nombre_completo }}@if($d->especialidad) — {{ $d->especialidad }}@endif
                        </option>
                        @endforeach
                    </select>
                    @error('docente_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <p class="form-section-title mt-4"><i class="bi bi-card-text me-1"></i>Observaciones</p>
                <div class="mb-4">
                    <label class="form-label" for="descripcion">Descripción / Notas</label>
                    <textarea name="descripcion" id="descripcion" rows="3" maxlength="500"
                              class="form-control @error('descripcion') is-invalid @enderror"
                              placeholder="Objetivos de la tutoría, notas generales...">{{ old('descripcion', $tutoria->descripcion) }}</textarea>
                    @error('descripcion')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-4">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="activo" id="activo" value="1"
                               @checked(old('activo', $tutoria->activo))>
                        <label class="form-check-label fw-semibold" for="activo" style="font-size:.875rem;">
                            Tutoría activa
                        </label>
                    </div>
                </div>

                <div class="d-flex gap-2 justify-content-end">
                    <a href="{{ route('admin.tutorias.index') }}" class="btn btn-sm btn-outline-secondary" style="border-radius:8px;">Cancelar</a>
                    <button type="submit" class="btn btn-sm fw-semibold"
                            style="background:var(--primary);color:#fff;border-radius:8px;padding:.45rem 1.25rem;">
                        <i class="bi bi-check-lg me-1"></i>Guardar Cambios
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection
