@extends('layouts.admin')
@section('page-title', 'Editar Estudiante')

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
        transition: border-color .18s, box-shadow .18s;
    }
    .form-control:focus, .form-select:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(30,58,110,.12);
    }
    .form-control.is-invalid, .form-select.is-invalid { border-color: var(--secondary); }
    .invalid-feedback { font-size: .78rem; }
    .foto-preview-wrap { position: relative; display: inline-block; }
    .foto-preview, .foto-initials-preview {
        width: 100px; height: 100px;
        border-radius: 50%;
        object-fit: cover;
        border: 3px solid #e5e7eb;
    }
    .foto-initials-preview {
        background: linear-gradient(135deg, #2a4f96, var(--primary));
        color: #fff;
        font-size: 1.8rem;
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
    <a href="{{ route('admin.estudiantes.index') }}"
       class="btn btn-sm btn-outline-secondary" style="border-radius:8px;">
        <i class="bi bi-arrow-left me-1"></i>Volver
    </a>
    <div>
        <h1 class="mb-0" style="font-size:1.4rem;font-weight:800;color:var(--primary);">
            <i class="bi bi-pencil-square me-2" style="color:var(--secondary);"></i>Editar Estudiante
        </h1>
        <p class="text-muted mb-0" style="font-size:.82rem;">
            {{ $estudiante->apellidos }}, {{ $estudiante->nombres }}
            &nbsp;·&nbsp;
            <span style="font-family:monospace;">{{ $estudiante->numero_matricula }}</span>
        </p>
    </div>
    <div class="ms-auto">
        <a href="{{ route('admin.estudiantes.show', $estudiante) }}"
           class="btn btn-sm btn-outline-primary" style="border-radius:8px;">
            <i class="bi bi-eye me-1"></i>Ver perfil
        </a>
    </div>
</div>

<form method="POST"
      action="{{ route('admin.estudiantes.update', $estudiante) }}"
      enctype="multipart/form-data"
      novalidate>
    @csrf
    @method('PUT')

    {{-- IDENTIFICACIÓN --}}
    <div class="form-section">
        <div class="section-title">
            <i class="bi bi-card-text"></i>Identificación
        </div>

        {{-- Photo --}}
        <div class="mb-4 d-flex align-items-center gap-4">
            <div class="foto-preview-wrap">
                @if($estudiante->foto)
                    <img src="{{ asset('storage/'.$estudiante->foto) }}"
                         alt="{{ $estudiante->nombres }}"
                         class="foto-preview"
                         id="fotoPreview">
                    <div class="foto-initials-preview d-none" id="fotoInitials">
                        {{ substr($estudiante->nombres,0,1) }}{{ substr($estudiante->apellidos,0,1) }}
                    </div>
                @else
                    <img src="" alt="" class="foto-preview d-none" id="fotoPreview">
                    <div class="foto-initials-preview" id="fotoInitials">
                        {{ substr($estudiante->nombres,0,1) }}{{ substr($estudiante->apellidos,0,1) }}
                    </div>
                @endif
                <label for="foto" class="foto-upload-btn" title="Cambiar foto">
                    <i class="bi bi-camera"></i>
                </label>
            </div>
            <div>
                <input type="file" id="foto" name="foto"
                       class="d-none @error('foto') is-invalid @enderror"
                       accept="image/*" onchange="previewFoto(this)">
                <p class="mb-1" style="font-size:.82rem;color:#374151;font-weight:600;">
                    @if($estudiante->foto) Cambiar foto @else Subir foto @endif
                </p>
                <p class="text-muted mb-0" style="font-size:.76rem;">JPG, PNG · Máx. 2 MB · Se ajustará a 300×300 px</p>
                @error('foto')
                    <div class="text-danger mt-1" style="font-size:.78rem;">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="row g-3">
            <div class="col-md-4">
                <label for="numero_matricula" class="form-label">Número de Matrícula <span class="text-danger">*</span></label>
                <input type="text" id="numero_matricula" name="numero_matricula"
                       value="{{ old('numero_matricula', $estudiante->numero_matricula) }}"
                       class="form-control @error('numero_matricula') is-invalid @enderror">
                @error('numero_matricula')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-4">
                <label for="cedula" class="form-label">Cédula / Documento</label>
                <input type="text" id="cedula" name="cedula"
                       value="{{ old('cedula', $estudiante->cedula) }}"
                       class="form-control @error('cedula') is-invalid @enderror">
                @error('cedula')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-4">
                <label for="sexo" class="form-label">Sexo <span class="text-danger">*</span></label>
                <select id="sexo" name="sexo" class="form-select @error('sexo') is-invalid @enderror">
                    <option value="">— Seleccionar —</option>
                    <option value="M" {{ old('sexo', $estudiante->sexo) === 'M' ? 'selected' : '' }}>Masculino</option>
                    <option value="F" {{ old('sexo', $estudiante->sexo) === 'F' ? 'selected' : '' }}>Femenino</option>
                </select>
                @error('sexo')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-6">
                <label for="nombres" class="form-label">Nombres <span class="text-danger">*</span></label>
                <input type="text" id="nombres" name="nombres"
                       value="{{ old('nombres', $estudiante->nombres) }}"
                       class="form-control @error('nombres') is-invalid @enderror">
                @error('nombres')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-6">
                <label for="apellidos" class="form-label">Apellidos <span class="text-danger">*</span></label>
                <input type="text" id="apellidos" name="apellidos"
                       value="{{ old('apellidos', $estudiante->apellidos) }}"
                       class="form-control @error('apellidos') is-invalid @enderror">
                @error('apellidos')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-4">
                <label for="fecha_nacimiento" class="form-label">Fecha de Nacimiento <span class="text-danger">*</span></label>
                <input type="date" id="fecha_nacimiento" name="fecha_nacimiento"
                       value="{{ old('fecha_nacimiento', $estudiante->fecha_nacimiento?->format('Y-m-d')) }}"
                       class="form-control @error('fecha_nacimiento') is-invalid @enderror">
                @error('fecha_nacimiento')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-4">
                <label for="nacionalidad" class="form-label">Nacionalidad</label>
                <input type="text" id="nacionalidad" name="nacionalidad"
                       value="{{ old('nacionalidad', $estudiante->nacionalidad) }}"
                       class="form-control @error('nacionalidad') is-invalid @enderror">
                @error('nacionalidad')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-4">
                <label for="lugar_nacimiento" class="form-label">Lugar de Nacimiento</label>
                <input type="text" id="lugar_nacimiento" name="lugar_nacimiento"
                       value="{{ old('lugar_nacimiento', $estudiante->lugar_nacimiento) }}"
                       class="form-control @error('lugar_nacimiento') is-invalid @enderror">
                @error('lugar_nacimiento')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>
    </div>

    {{-- CONTACTO --}}
    <div class="form-section">
        <div class="section-title">
            <i class="bi bi-geo-alt"></i>Contacto y Dirección
        </div>

        <div class="row g-3">
            <div class="col-md-4">
                <label for="telefono" class="form-label">Teléfono</label>
                <input type="text" id="telefono" name="telefono"
                       value="{{ old('telefono', $estudiante->telefono) }}"
                       class="form-control @error('telefono') is-invalid @enderror">
                @error('telefono')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-8">
                <label for="email" class="form-label">Correo electrónico</label>
                <input type="email" id="email" name="email"
                       value="{{ old('email', $estudiante->email) }}"
                       class="form-control @error('email') is-invalid @enderror">
                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-12">
                <label for="direccion" class="form-label">Dirección</label>
                <textarea id="direccion" name="direccion" rows="2"
                          class="form-control @error('direccion') is-invalid @enderror">{{ old('direccion', $estudiante->direccion) }}</textarea>
                @error('direccion')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-4">
                <label for="sector" class="form-label">Sector</label>
                <input type="text" id="sector" name="sector"
                       value="{{ old('sector', $estudiante->sector) }}"
                       class="form-control @error('sector') is-invalid @enderror">
                @error('sector')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-4">
                <label for="municipio" class="form-label">Municipio</label>
                <input type="text" id="municipio" name="municipio"
                       value="{{ old('municipio', $estudiante->municipio) }}"
                       class="form-control @error('municipio') is-invalid @enderror">
                @error('municipio')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-4">
                <label for="provincia" class="form-label">Provincia</label>
                <input type="text" id="provincia" name="provincia"
                       value="{{ old('provincia', $estudiante->provincia) }}"
                       class="form-control @error('provincia') is-invalid @enderror">
                @error('provincia')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>
    </div>

    {{-- TUTOR --}}
    <div class="form-section">
        <div class="section-title">
            <i class="bi bi-person-hearts"></i>Tutor / Encargado
        </div>

        <div class="row g-3">
            <div class="col-md-6">
                <label for="tutor_nombre" class="form-label">Nombre completo del tutor</label>
                <input type="text" id="tutor_nombre" name="tutor_nombre"
                       value="{{ old('tutor_nombre', $estudiante->tutor_nombre) }}"
                       class="form-control @error('tutor_nombre') is-invalid @enderror">
                @error('tutor_nombre')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-3">
                <label for="tutor_parentesco" class="form-label">Parentesco</label>
                <input type="text" id="tutor_parentesco" name="tutor_parentesco"
                       value="{{ old('tutor_parentesco', $estudiante->tutor_parentesco) }}"
                       class="form-control @error('tutor_parentesco') is-invalid @enderror">
                @error('tutor_parentesco')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-3">
                <label for="tutor_telefono" class="form-label">Teléfono del tutor</label>
                <input type="text" id="tutor_telefono" name="tutor_telefono"
                       value="{{ old('tutor_telefono', $estudiante->tutor_telefono) }}"
                       class="form-control @error('tutor_telefono') is-invalid @enderror">
                @error('tutor_telefono')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-6">
                <label for="tutor_trabajo" class="form-label">Lugar de trabajo del tutor</label>
                <input type="text" id="tutor_trabajo" name="tutor_trabajo"
                       value="{{ old('tutor_trabajo', $estudiante->tutor_trabajo) }}"
                       class="form-control @error('tutor_trabajo') is-invalid @enderror">
                @error('tutor_trabajo')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>
    </div>

    {{-- OBSERVACIONES --}}
    <div class="form-section">
        <div class="section-title">
            <i class="bi bi-clipboard-text"></i>Observaciones
        </div>

        <div class="row g-3">
            <div class="col-md-8">
                <label for="notas_medicas" class="form-label">Notas médicas / Observaciones</label>
                <textarea id="notas_medicas" name="notas_medicas" rows="3"
                          class="form-control @error('notas_medicas') is-invalid @enderror"
                          placeholder="Alergias, condiciones, necesidades especiales…">{{ old('notas_medicas', $estudiante->notas_medicas) }}</textarea>
                @error('notas_medicas')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-4">
                <label for="estado" class="form-label">Estado <span class="text-danger">*</span></label>
                <select id="estado" name="estado" class="form-select @error('estado') is-invalid @enderror">
                    <option value="activo"      {{ old('estado', $estudiante->estado) === 'activo'      ? 'selected' : '' }}>Activo</option>
                    <option value="inactivo"    {{ old('estado', $estudiante->estado) === 'inactivo'    ? 'selected' : '' }}>Inactivo</option>
                    <option value="egresado"    {{ old('estado', $estudiante->estado) === 'egresado'    ? 'selected' : '' }}>Egresado</option>
                    <option value="transferido" {{ old('estado', $estudiante->estado) === 'transferido' ? 'selected' : '' }}>Transferido</option>
                </select>
                @error('estado')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>
    </div>

    {{-- Actions --}}
    <div class="d-flex gap-2 justify-content-end">
        <a href="{{ route('admin.estudiantes.index') }}" class="btn btn-outline-secondary px-4" style="border-radius:8px;">
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
