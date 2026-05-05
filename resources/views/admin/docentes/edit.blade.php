@extends('layouts.admin')
@section('page-title', 'Editar Docente')

@push('styles')
<style>
    .form-section {
        background: #fff;
        border-radius: 12px;
        border: 1px solid #e5e7eb;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
    }
    .section-title {
        font-size: .8rem;
        font-weight: 700;
        letter-spacing: .1em;
        text-transform: uppercase;
        color: var(--primary);
        border-bottom: 2px solid var(--primary);
        padding-bottom: .5rem;
        margin-bottom: 1.25rem;
        display: flex;
        align-items: center;
        gap: .5rem;
    }
    .form-label {
        font-size: .82rem;
        font-weight: 600;
        color: #374151;
        margin-bottom: .35rem;
    }
    .form-control, .form-select {
        border-radius: 8px;
        border-color: #d1d5db;
        font-size: .875rem;
        padding: .5rem .8rem;
    }
    .form-control:focus, .form-select:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(30,58,110,.12);
    }
    .form-control.is-invalid, .form-select.is-invalid {
        border-color: var(--secondary);
    }
    .invalid-feedback { font-size: .78rem; }
    .foto-preview-wrap { position: relative; display: inline-block; }
    .foto-preview, .foto-initials-preview {
        width: 100px; height: 100px;
        border-radius: 50%;
        object-fit: cover;
        border: 3px solid #e5e7eb;
    }
    .foto-initials-preview {
        background: linear-gradient(135deg, var(--primary), var(--primary-light));
        color: #fff;
        font-size: 1.6rem;
        font-weight: 700;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .foto-upload-btn {
        position: absolute;
        bottom: 0; right: 0;
        width: 28px; height: 28px;
        background: var(--primary);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: .72rem;
        cursor: pointer;
        border: 2px solid #fff;
    }
    .foto-upload-btn:hover { background: var(--primary-dark); }
    [data-theme="dark"] .form-section { background: #1e293b; border-color: #334155; }
</style>
@endpush

@section('content')

{{-- Page header --}}
<div class="d-flex align-items-center gap-3 mb-4">
    <a href="{{ route('admin.docentes.index') }}"
       class="btn btn-sm btn-outline-secondary" style="border-radius:8px;">
        <i class="bi bi-arrow-left me-1"></i>Volver
    </a>
    <div>
        <h1 class="mb-0" style="font-size:1.4rem;font-weight:800;color:var(--primary);">
            <i class="bi bi-pencil-square me-2" style="color:var(--secondary);"></i>Editar Docente
        </h1>
        <p class="text-muted mb-0" style="font-size:.82rem;">
            {{ $docente->apellidos }}, {{ $docente->nombres }}
        </p>
    </div>
    <div class="ms-auto">
        <a href="{{ route('admin.docentes.show', $docente) }}"
           class="btn btn-sm btn-outline-primary" style="border-radius:8px;">
            <i class="bi bi-eye me-1"></i>Ver perfil
        </a>
    </div>
</div>

<form method="POST"
      action="{{ route('admin.docentes.update', $docente) }}"
      enctype="multipart/form-data"
      novalidate>
    @csrf
    @method('PUT')

    {{-- INFORMACIÓN PERSONAL --}}
    <div class="form-section">
        <div class="section-title">
            <i class="bi bi-person-vcard"></i>Información Personal
        </div>

        {{-- Photo --}}
        <div class="mb-4 d-flex align-items-center gap-4">
            <div class="foto-preview-wrap">
                @if($docente->foto)
                    <img src="{{ asset('storage/'.$docente->foto) }}"
                         alt="{{ $docente->nombres }}"
                         class="foto-preview"
                         id="fotoPreview">
                    <div class="foto-initials-preview d-none" id="fotoInitials">
                        {{ substr($docente->nombres,0,1) }}{{ substr($docente->apellidos,0,1) }}
                    </div>
                @else
                    <img src="" alt="" class="foto-preview d-none" id="fotoPreview">
                    <div class="foto-initials-preview" id="fotoInitials">
                        {{ substr($docente->nombres,0,1) }}{{ substr($docente->apellidos,0,1) }}
                    </div>
                @endif
                <label for="foto" class="foto-upload-btn" title="Cambiar foto">
                    <i class="bi bi-camera"></i>
                </label>
            </div>
            <div>
                <input type="file" id="foto" name="foto"
                       class="d-none @error('foto') is-invalid @enderror"
                       accept="image/*"
                       onchange="previewFoto(this)">
                <p class="mb-1" style="font-size:.82rem;color:#374151;font-weight:600;">
                    @if($docente->foto) Cambiar foto @else Subir foto @endif
                </p>
                <p class="text-muted mb-0" style="font-size:.76rem;">JPG, PNG · Máx. 2 MB · Se ajustará a 300×300 px</p>
                @error('foto')
                    <div class="text-danger mt-1" style="font-size:.78rem;">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="row g-3">
            <div class="col-md-6">
                <label for="nombres" class="form-label">Nombres <span class="text-danger">*</span></label>
                <input type="text" id="nombres" name="nombres"
                       value="{{ old('nombres', $docente->nombres) }}"
                       class="form-control @error('nombres') is-invalid @enderror">
                @error('nombres')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-6">
                <label for="apellidos" class="form-label">Apellidos <span class="text-danger">*</span></label>
                <input type="text" id="apellidos" name="apellidos"
                       value="{{ old('apellidos', $docente->apellidos) }}"
                       class="form-control @error('apellidos') is-invalid @enderror">
                @error('apellidos')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-4">
                <label for="cedula" class="form-label">Cédula</label>
                <input type="text" id="cedula" name="cedula"
                       value="{{ old('cedula', $docente->cedula) }}"
                       class="form-control @error('cedula') is-invalid @enderror">
                @error('cedula')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-4">
                <label for="fecha_nacimiento" class="form-label">Fecha de Nacimiento</label>
                <input type="date" id="fecha_nacimiento" name="fecha_nacimiento"
                       value="{{ old('fecha_nacimiento', $docente->fecha_nacimiento?->format('Y-m-d')) }}"
                       class="form-control @error('fecha_nacimiento') is-invalid @enderror">
                @error('fecha_nacimiento')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-4">
                <label for="sexo" class="form-label">Sexo</label>
                <select id="sexo" name="sexo" class="form-select @error('sexo') is-invalid @enderror">
                    <option value="">— Seleccionar —</option>
                    <option value="M" {{ old('sexo', $docente->sexo) === 'M' ? 'selected' : '' }}>Masculino</option>
                    <option value="F" {{ old('sexo', $docente->sexo) === 'F' ? 'selected' : '' }}>Femenino</option>
                </select>
                @error('sexo')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>
    </div>

    {{-- INFORMACIÓN DE CONTACTO --}}
    <div class="form-section">
        <div class="section-title">
            <i class="bi bi-telephone"></i>Información de Contacto
        </div>

        <div class="row g-3">
            <div class="col-md-4">
                <label for="telefono" class="form-label">Teléfono</label>
                <input type="text" id="telefono" name="telefono"
                       value="{{ old('telefono', $docente->telefono) }}"
                       class="form-control @error('telefono') is-invalid @enderror">
                @error('telefono')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-8">
                <label for="email" class="form-label">Correo electrónico</label>
                <input type="email" id="email" name="email"
                       value="{{ old('email', $docente->email) }}"
                       class="form-control @error('email') is-invalid @enderror">
                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-12">
                <label for="direccion" class="form-label">Dirección</label>
                <textarea id="direccion" name="direccion" rows="2"
                          class="form-control @error('direccion') is-invalid @enderror">{{ old('direccion', $docente->direccion) }}</textarea>
                @error('direccion')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>
    </div>

    {{-- INFORMACIÓN PROFESIONAL --}}
    <div class="form-section">
        <div class="section-title">
            <i class="bi bi-mortarboard"></i>Información Profesional
        </div>

        <div class="row g-3">
            <div class="col-md-6">
                <label for="especialidad" class="form-label">Especialidad</label>
                <input type="text" id="especialidad" name="especialidad"
                       value="{{ old('especialidad', $docente->especialidad) }}"
                       class="form-control @error('especialidad') is-invalid @enderror">
                @error('especialidad')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-6">
                <label for="titulo_academico" class="form-label">Título Académico</label>
                <input type="text" id="titulo_academico" name="titulo_academico"
                       value="{{ old('titulo_academico', $docente->titulo_academico) }}"
                       class="form-control @error('titulo_academico') is-invalid @enderror">
                @error('titulo_academico')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-6">
                <label class="form-label fw-semibold">Área <span class="text-danger">*</span></label>
                <select name="area" class="form-select @error('area') is-invalid @enderror" required>
                    <option value="">— Seleccionar —</option>
                    <option value="tecnica"        {{ old('area', $docente->area) == 'tecnica'        ? 'selected' : '' }}>Técnica</option>
                    <option value="administrativa" {{ old('area', $docente->area) == 'administrativa' ? 'selected' : '' }}>Administrativa</option>
                    <option value="otro"           {{ old('area', $docente->area) == 'otro'           ? 'selected' : '' }}>Otro</option>
                </select>
                @error('area') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-6">
                <label class="form-label fw-semibold">Cargo / Función</label>
                <input type="text" name="cargo" class="form-control @error('cargo') is-invalid @enderror"
                       value="{{ old('cargo', $docente->cargo) }}" placeholder="Ej: Docente de Área Técnica">
                @error('cargo') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-4">
                <label for="estado" class="form-label">Estado <span class="text-danger">*</span></label>
                <select id="estado" name="estado" class="form-select @error('estado') is-invalid @enderror">
                    <option value="activo"   {{ old('estado', $docente->estado) === 'activo'   ? 'selected' : '' }}>Activo</option>
                    <option value="inactivo" {{ old('estado', $docente->estado) === 'inactivo' ? 'selected' : '' }}>Inactivo</option>
                </select>
                @error('estado')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>
    </div>

    {{-- Actions --}}
    <div class="d-flex gap-2 justify-content-end">
        <a href="{{ route('admin.docentes.index') }}" class="btn btn-outline-secondary px-4" style="border-radius:8px;">
            Cancelar
        </a>
        <button type="submit" class="btn px-4 fw-600"
                style="background:var(--primary);color:#fff;border-radius:8px;">
            <i class="bi bi-floppy me-1"></i>Guardar Cambios
        </button>
    </div>
</form>

@endsection

@push('scripts')
<script>
function previewFoto(input) {
    const preview  = document.getElementById('fotoPreview');
    const initials = document.getElementById('fotoInitials');
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => {
            preview.src = e.target.result;
            preview.classList.remove('d-none');
            initials.classList.add('d-none');
        };
        reader.readAsDataURL(input.files[0]);
    }
}
</script>
@endpush
