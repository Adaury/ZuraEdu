@extends('layouts.admin')
@section('page-title', 'Registro de Estudiante')

@push('styles')
<style>
/* ── Wizard shell ─────────────────────────────────────────────── */
.wz-card {
    background: #fff;
    border-radius: 16px;
    border: 1px solid #e5e7eb;
    overflow: hidden;
    box-shadow: 0 4px 24px rgba(0,0,0,.06);
}

/* ── Progress strip ──────────────────────────────────────────── */
.wz-progress {
    display: flex;
    background: #f8faff;
    border-bottom: 1px solid #e5e7eb;
    padding: 1.25rem 1.75rem;
    gap: 0;
    overflow-x: auto;
}
.wz-step-indicator {
    display: flex;
    flex-direction: column;
    align-items: center;
    flex: 1;
    min-width: 80px;
    position: relative;
    cursor: default;
}
.wz-step-indicator:not(:last-child)::after {
    content: '';
    position: absolute;
    top: 18px;
    left: calc(50% + 18px);
    right: calc(-50% + 18px);
    height: 2px;
    background: #e5e7eb;
    transition: background .3s;
}
.wz-step-indicator.done::after { background: var(--primary); }

.wz-bubble {
    width: 36px; height: 36px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: .78rem;
    font-weight: 700;
    border: 2px solid #d1d5db;
    background: #fff;
    color: #9ca3af;
    transition: all .25s;
    z-index: 1;
}
.wz-step-indicator.active .wz-bubble {
    background: var(--primary);
    border-color: var(--primary);
    color: #fff;
    box-shadow: 0 0 0 4px rgba(30,58,110,.18);
}
.wz-step-indicator.done .wz-bubble {
    background: #10b981;
    border-color: #10b981;
    color: #fff;
}
.wz-step-label {
    font-size: .7rem;
    font-weight: 600;
    color: #9ca3af;
    margin-top: .4rem;
    text-align: center;
    white-space: nowrap;
}
.wz-step-indicator.active .wz-step-label { color: var(--primary); }
.wz-step-indicator.done .wz-step-label   { color: #10b981; }

/* ── Panel body ──────────────────────────────────────────────── */
.wz-body { padding: 2rem 2rem 1.5rem; }
.wz-title {
    font-size: 1.1rem;
    font-weight: 800;
    color: var(--primary);
    margin-bottom: 1.25rem;
    display: flex;
    align-items: center;
    gap: .5rem;
}
.wz-title::after {
    content: '';
    flex: 1;
    height: 2px;
    background: linear-gradient(90deg, var(--primary) 0%, transparent 100%);
    opacity: .25;
    margin-left: .5rem;
}

/* ── Form helpers ────────────────────────────────────────────── */
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
.form-control.is-invalid, .form-select.is-invalid { border-color: #ef4444; }
.invalid-feedback { font-size: .78rem; }

/* ── Photo upload ────────────────────────────────────────────── */
.foto-preview-wrap { position: relative; display: inline-block; }
.foto-preview, .foto-initials-preview {
    width: 110px; height: 110px;
    border-radius: 50%;
    object-fit: cover;
    border: 3px solid #e5e7eb;
}
.foto-initials-preview {
    background: linear-gradient(135deg, #2a4f96, var(--primary));
    color: #fff;
    font-size: 2rem;
    display: flex;
    align-items: center;
    justify-content: center;
}
.foto-upload-btn {
    position: absolute;
    bottom: 3px; right: 3px;
    width: 30px; height: 30px;
    background: var(--primary);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: .75rem;
    cursor: pointer;
    border: 2px solid #fff;
}
.foto-upload-btn:hover { filter: brightness(1.15); }

/* ── Summary card ────────────────────────────────────────────── */
.summary-section {
    background: #f8faff;
    border: 1px solid #dbeafe;
    border-radius: 10px;
    padding: 1rem 1.25rem;
    margin-bottom: 1rem;
}
.summary-label {
    font-size: .72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .08em;
    color: var(--primary);
    margin-bottom: .6rem;
    display: flex;
    align-items: center;
    gap: .35rem;
}
.summary-row {
    display: flex;
    gap: .5rem;
    font-size: .84rem;
    margin-bottom: .3rem;
    color: #374151;
}
.summary-row .sk { font-weight: 600; min-width: 140px; color: #6b7280; }

/* ── Navigation ──────────────────────────────────────────────── */
.wz-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 2rem 1.75rem;
    border-top: 1px solid #f3f4f6;
    margin-top: .5rem;
}
.btn-wz-prev {
    background: transparent;
    border: 1px solid #d1d5db;
    color: #374151;
    border-radius: 9px;
    padding: .55rem 1.5rem;
    font-size: .875rem;
    font-weight: 600;
    cursor: pointer;
    transition: background .15s, border-color .15s;
}
.btn-wz-prev:hover { background: #f3f4f6; border-color: #9ca3af; }
.btn-wz-next {
    background: var(--primary);
    border: none;
    color: #fff;
    border-radius: 9px;
    padding: .55rem 1.75rem;
    font-size: .875rem;
    font-weight: 600;
    cursor: pointer;
    transition: filter .15s;
    display: flex;
    align-items: center;
    gap: .4rem;
}
.btn-wz-next:hover { filter: brightness(1.1); }
.btn-wz-submit {
    background: #10b981;
    border: none;
    color: #fff;
    border-radius: 9px;
    padding: .55rem 1.75rem;
    font-size: .875rem;
    font-weight: 600;
    cursor: pointer;
    transition: filter .15s;
    display: flex;
    align-items: center;
    gap: .4rem;
}
.btn-wz-submit:hover { filter: brightness(1.1); }

/* ── Responsive ──────────────────────────────────────────────── */
@media (max-width: 576px) {
    .wz-body { padding: 1.25rem 1rem; }
    .wz-progress { padding: 1rem; gap: 0; }
    .wz-footer { padding: 1rem; }
    .wz-step-label { font-size: .62rem; }
}

[data-theme="dark"] .wz-card { background: #1e293b; border-color: #334155; }
[data-theme="dark"] .wz-progress { background: #0f172a; border-color: #334155; }
[data-theme="dark"] .wz-bubble { background: #1e293b; border-color: #475569; color: #94a3b8; }
[data-theme="dark"] .summary-section { background: #0f172a; border-color: #1d4ed8; }
[data-theme="dark"] .summary-row { color: #e2e8f0; }
[data-theme="dark"] .summary-row .sk { color: #94a3b8; }
[data-theme="dark"] .wz-footer { border-color: #334155; }
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
            <i class="bi bi-person-plus me-2" style="color:var(--secondary);"></i>Registro de Nuevo Estudiante
        </h1>
        <p class="text-muted mb-0" style="font-size:.82rem;">Siga los pasos para completar la matrícula</p>
    </div>
</div>

@if($errors->any())
<div class="alert alert-danger mb-3" style="border-radius:10px;">
    <strong><i class="bi bi-exclamation-triangle me-1"></i>Por favor corrija los siguientes errores:</strong>
    <ul class="mb-0 mt-1 ps-3">
        @foreach($errors->all() as $error)
            <li style="font-size:.85rem;">{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

<div x-data="wizard()" x-init="init()">

<form method="POST" action="{{ route('admin.estudiantes.store') }}"
      enctype="multipart/form-data" id="wizardForm" novalidate>
    @csrf

    {{-- ─── Progress Bar ──────────────────────────────────────────────── --}}
    <div class="wz-card mb-0">
        <div class="wz-progress">
            <template x-for="(s, i) in steps" :key="i">
                <div class="wz-step-indicator"
                     :class="{ active: step === i+1, done: step > i+1 }">
                    <div class="wz-bubble">
                        <template x-if="step > i+1">
                            <i class="bi bi-check-lg"></i>
                        </template>
                        <template x-if="step <= i+1">
                            <span x-text="i+1"></span>
                        </template>
                    </div>
                    <div class="wz-step-label" x-text="s.label"></div>
                </div>
            </template>
        </div>

        {{-- ══════════════════════════════════════════════════════════════ --}}
        {{-- STEP 1 — Datos Personales                                      --}}
        {{-- ══════════════════════════════════════════════════════════════ --}}
        <div class="wz-body" x-show="step === 1" x-transition:enter="fade-in">

            <div class="wz-title"><i class="bi bi-person-badge"></i>Datos Personales</div>

            {{-- Photo --}}
            <div class="mb-4 d-flex align-items-center gap-4">
                <div class="foto-preview-wrap">
                    <div class="foto-initials-preview" id="fotoInitials">
                        <i class="bi bi-person" style="opacity:.55;"></i>
                    </div>
                    <img src="" alt="" class="foto-preview d-none" id="fotoPreview">
                    <label for="foto" class="foto-upload-btn" title="Subir foto">
                        <i class="bi bi-camera"></i>
                    </label>
                </div>
                <div>
                    <input type="file" id="foto" name="foto"
                           class="d-none @error('foto') is-invalid @enderror"
                           accept="image/*" onchange="previewFoto(this)">
                    <p class="mb-1" style="font-size:.82rem;font-weight:600;color:#374151;">Foto del estudiante</p>
                    <p class="text-muted mb-0" style="font-size:.76rem;">JPG, PNG · Máx. 2 MB</p>
                    @error('foto')
                        <div class="text-danger mt-1" style="font-size:.78rem;">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Nombres <span class="text-danger">*</span></label>
                    <input type="text" name="nombres"
                           value="{{ old('nombres') }}"
                           class="form-control @error('nombres') is-invalid @enderror"
                           placeholder="Ej: Luis Alejandro"
                           @input="formData.nombres = $event.target.value">
                    @error('nombres')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <div class="invalid-feedback" id="err_nombres" style="display:none;">El nombre es obligatorio.</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Apellidos <span class="text-danger">*</span></label>
                    <input type="text" name="apellidos"
                           value="{{ old('apellidos') }}"
                           class="form-control @error('apellidos') is-invalid @enderror"
                           placeholder="Ej: Martínez Díaz"
                           @input="formData.apellidos = $event.target.value">
                    @error('apellidos')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <div class="invalid-feedback" id="err_apellidos" style="display:none;">El apellido es obligatorio.</div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Cédula / Documento</label>
                    <input type="text" name="cedula"
                           value="{{ old('cedula') }}"
                           class="form-control @error('cedula') is-invalid @enderror"
                           placeholder="000-0000000-0">
                    @error('cedula')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Sexo <span class="text-danger">*</span></label>
                    <select name="sexo"
                            class="form-select @error('sexo') is-invalid @enderror"
                            @change="formData.sexo = $event.target.value">
                        <option value="">— Seleccionar —</option>
                        <option value="M" {{ old('sexo') === 'M' ? 'selected' : '' }}>Masculino</option>
                        <option value="F" {{ old('sexo') === 'F' ? 'selected' : '' }}>Femenino</option>
                    </select>
                    @error('sexo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <div class="invalid-feedback" id="err_sexo" style="display:none;">Seleccione el sexo.</div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Fecha de Nacimiento <span class="text-danger">*</span></label>
                    <input type="date" name="fecha_nacimiento"
                           value="{{ old('fecha_nacimiento') }}"
                           class="form-control @error('fecha_nacimiento') is-invalid @enderror"
                           @change="formData.fecha_nacimiento = $event.target.value">
                    @error('fecha_nacimiento')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <div class="invalid-feedback" id="err_fecha_nacimiento" style="display:none;">La fecha de nacimiento es obligatoria.</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Nacionalidad</label>
                    <input type="text" name="nacionalidad"
                           value="{{ old('nacionalidad', 'Dominicana') }}"
                           class="form-control">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Lugar de Nacimiento</label>
                    <input type="text" name="lugar_nacimiento"
                           value="{{ old('lugar_nacimiento') }}"
                           class="form-control"
                           placeholder="Ciudad, provincia…">
                </div>
            </div>
        </div>

        {{-- ══════════════════════════════════════════════════════════════ --}}
        {{-- STEP 2 — Contacto                                              --}}
        {{-- ══════════════════════════════════════════════════════════════ --}}
        <div class="wz-body" x-show="step === 2" x-transition:enter="fade-in">

            <div class="wz-title"><i class="bi bi-geo-alt"></i>Contacto y Dirección</div>

            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Teléfono</label>
                    <input type="text" name="telefono"
                           value="{{ old('telefono') }}"
                           class="form-control"
                           placeholder="(809) 000-0000">
                </div>
                <div class="col-md-8">
                    <label class="form-label">Correo Electrónico</label>
                    <input type="email" name="email"
                           value="{{ old('email') }}"
                           class="form-control @error('email') is-invalid @enderror"
                           placeholder="estudiante@ejemplo.com">
                    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12">
                    <label class="form-label">Dirección</label>
                    <textarea name="direccion" rows="2"
                              class="form-control"
                              placeholder="Calle y número…">{{ old('direccion') }}</textarea>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Sector</label>
                    <input type="text" name="sector"
                           value="{{ old('sector') }}"
                           class="form-control">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Municipio</label>
                    <input type="text" name="municipio"
                           value="{{ old('municipio') }}"
                           class="form-control">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Provincia</label>
                    <input type="text" name="provincia"
                           value="{{ old('provincia') }}"
                           class="form-control">
                </div>
            </div>
        </div>

        {{-- ══════════════════════════════════════════════════════════════ --}}
        {{-- STEP 3 — Tutor / Representante                                 --}}
        {{-- ══════════════════════════════════════════════════════════════ --}}
        <div class="wz-body" x-show="step === 3" x-transition:enter="fade-in">

            <div class="wz-title"><i class="bi bi-person-hearts"></i>Tutor / Representante</div>

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Nombre completo del tutor</label>
                    <input type="text" name="tutor_nombre"
                           value="{{ old('tutor_nombre') }}"
                           class="form-control"
                           placeholder="Nombre y apellidos"
                           @input="formData.tutor_nombre = $event.target.value">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Parentesco</label>
                    <select name="tutor_parentesco"
                            class="form-select"
                            @change="formData.tutor_parentesco = $event.target.value">
                        <option value="">— Seleccionar —</option>
                        @foreach(['Madre','Padre','Abuela','Abuelo','Tía','Tío','Hermana','Hermano','Tutora legal','Tutor legal','Otro'] as $p)
                            <option value="{{ $p }}" {{ old('tutor_parentesco') === $p ? 'selected' : '' }}>{{ $p }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Teléfono del tutor</label>
                    <input type="text" name="tutor_telefono"
                           value="{{ old('tutor_telefono') }}"
                           class="form-control"
                           placeholder="(809) 000-0000">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Correo del tutor</label>
                    <input type="email" name="tutor_email"
                           value="{{ old('tutor_email') }}"
                           class="form-control @error('tutor_email') is-invalid @enderror"
                           placeholder="tutor@ejemplo.com">
                    @error('tutor_email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Lugar de trabajo</label>
                    <input type="text" name="tutor_trabajo"
                           value="{{ old('tutor_trabajo') }}"
                           class="form-control"
                           placeholder="Empresa o institución…">
                </div>
            </div>
        </div>

        {{-- ══════════════════════════════════════════════════════════════ --}}
        {{-- STEP 4 — Matrícula                                             --}}
        {{-- ══════════════════════════════════════════════════════════════ --}}
        <div class="wz-body" x-show="step === 4" x-transition:enter="fade-in">

            <div class="wz-title"><i class="bi bi-journal-bookmark"></i>Matrícula y Asignación</div>

            <div class="row g-3">
                <div class="col-md-5">
                    <label class="form-label">Número de Matrícula</label>
                    <input type="text" name="numero_matricula"
                           value="{{ old('numero_matricula') }}"
                           class="form-control @error('numero_matricula') is-invalid @enderror"
                           placeholder="Ej: {{ date('Y') }}-00001">
                    <div style="font-size:.76rem;color:#9ca3af;margin-top:.25rem;">
                        Si se deja vacío, se genera automáticamente en formato
                        <strong style="color:var(--primary);">{{ date('Y') }}-XXXXX</strong>
                    </div>
                    @error('numero_matricula')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-7">
                    <label class="form-label">Grupo / Sección</label>
                    <select name="grupo_id"
                            class="form-select"
                            @change="formData.grupo_id = $event.target.value; formData.grupo_label = $event.target.options[$event.target.selectedIndex].text">
                        <option value="">— Sin asignación de grupo (matrícula pendiente) —</option>
                        @foreach($grupos as $grupo)
                            <option value="{{ $grupo->id }}"
                                    {{ old('grupo_id') == $grupo->id ? 'selected' : '' }}>
                                {{ $grupo->grado->nombre ?? '—' }} — Sección {{ $grupo->seccion->nombre ?? '?' }}
                            </option>
                        @endforeach
                    </select>
                    @if($grupos->isEmpty())
                        <div class="text-warning mt-1" style="font-size:.78rem;">
                            <i class="bi bi-exclamation-triangle me-1"></i>
                            No hay grupos disponibles para el año escolar activo.
                        </div>
                    @endif
                </div>

                <div class="col-md-4">
                    <label class="form-label">Estado <span class="text-danger">*</span></label>
                    <select name="estado"
                            class="form-select @error('estado') is-invalid @enderror"
                            @change="formData.estado = $event.target.value">
                        <option value="activo"      {{ old('estado', 'activo') === 'activo'      ? 'selected' : '' }}>Activo</option>
                        <option value="inactivo"    {{ old('estado') === 'inactivo'    ? 'selected' : '' }}>Inactivo</option>
                        <option value="egresado"    {{ old('estado') === 'egresado'    ? 'selected' : '' }}>Egresado</option>
                        <option value="transferido" {{ old('estado') === 'transferido' ? 'selected' : '' }}>Transferido</option>
                    </select>
                    @error('estado')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-8">
                    <label class="form-label">Notas médicas / Observaciones</label>
                    <textarea name="notas_medicas" rows="3"
                              class="form-control @error('notas_medicas') is-invalid @enderror"
                              placeholder="Alergias, condiciones especiales…">{{ old('notas_medicas') }}</textarea>
                    @error('notas_medicas')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
        </div>

        {{-- ══════════════════════════════════════════════════════════════ --}}
        {{-- STEP 5 — Confirmación                                          --}}
        {{-- ══════════════════════════════════════════════════════════════ --}}
        <div class="wz-body" x-show="step === 5" x-transition:enter="fade-in">

            <div class="wz-title"><i class="bi bi-patch-check"></i>Confirmación</div>

            <p class="text-muted mb-3" style="font-size:.84rem;">
                Revise la información antes de guardar. Puede retroceder para corregir cualquier dato.
            </p>

            <div class="row g-3">
                <div class="col-md-6">
                    <div class="summary-section">
                        <div class="summary-label"><i class="bi bi-person-badge"></i>Datos Personales</div>
                        <div class="summary-row">
                            <span class="sk">Nombre:</span>
                            <span x-text="(formData.apellidos || '—') + ', ' + (formData.nombres || '—')"></span>
                        </div>
                        <div class="summary-row">
                            <span class="sk">Sexo:</span>
                            <span x-text="formData.sexo === 'M' ? 'Masculino' : (formData.sexo === 'F' ? 'Femenino' : '—')"></span>
                        </div>
                        <div class="summary-row">
                            <span class="sk">Fecha de nacimiento:</span>
                            <span x-text="formData.fecha_nacimiento || '—'"></span>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="summary-section">
                        <div class="summary-label"><i class="bi bi-person-hearts"></i>Tutor</div>
                        <div class="summary-row">
                            <span class="sk">Nombre:</span>
                            <span x-text="formData.tutor_nombre || 'No especificado'"></span>
                        </div>
                        <div class="summary-row">
                            <span class="sk">Parentesco:</span>
                            <span x-text="formData.tutor_parentesco || '—'"></span>
                        </div>
                    </div>
                </div>

                <div class="col-12">
                    <div class="summary-section">
                        <div class="summary-label"><i class="bi bi-journal-bookmark"></i>Matrícula</div>
                        <div class="row g-2">
                            <div class="col-md-6">
                                <div class="summary-row">
                                    <span class="sk">Grupo asignado:</span>
                                    <span x-text="formData.grupo_label && formData.grupo_id ? formData.grupo_label : 'Sin grupo (asignar después)'"></span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="summary-row">
                                    <span class="sk">Estado:</span>
                                    <span x-text="formData.estado || 'activo'"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Año escolar activo --}}
            @if($schoolYear)
            <div class="alert alert-info d-flex align-items-center gap-2 mt-3" style="border-radius:10px;font-size:.84rem;">
                <i class="bi bi-calendar-check fs-5"></i>
                <span>El estudiante será matriculado en el año escolar <strong>{{ $schoolYear->nombre }}</strong>.</span>
            </div>
            @endif
        </div>

        {{-- ── Navigation ─────────────────────────────────────────────── --}}
        <div class="wz-footer">
            <button type="button" class="btn-wz-prev"
                    x-show="step > 1"
                    @click="prev()">
                <i class="bi bi-chevron-left me-1"></i>Anterior
            </button>
            <span x-show="step === 1"></span>

            <div class="d-flex align-items-center gap-2">
                <span class="text-muted" style="font-size:.78rem;" x-text="`Paso ${step} de ${steps.length}`"></span>
                <button type="button" class="btn-wz-next"
                        x-show="step < steps.length"
                        @click="next()">
                    Siguiente <i class="bi bi-chevron-right ms-1"></i>
                </button>
                <button type="submit" class="btn-wz-submit"
                        x-show="step === steps.length">
                    <i class="bi bi-floppy me-1"></i>Registrar Estudiante
                </button>
            </div>
        </div>

    </div>{{-- .wz-card --}}
</form>

</div>{{-- x-data --}}

@endsection

@push('scripts')
<script>
function wizard() {
    return {
        step: 1,
        steps: [
            { label: 'Datos\nPersonales' },
            { label: 'Contacto' },
            { label: 'Tutor' },
            { label: 'Matrícula' },
            { label: 'Confirmar' },
        ],
        formData: {
            nombres: '{{ old('nombres') }}',
            apellidos: '{{ old('apellidos') }}',
            sexo: '{{ old('sexo') }}',
            fecha_nacimiento: '{{ old('fecha_nacimiento') }}',
            tutor_nombre: '{{ old('tutor_nombre') }}',
            tutor_parentesco: '{{ old('tutor_parentesco') }}',
            estado: '{{ old('estado', 'activo') }}',
            grupo_id: '{{ old('grupo_id') }}',
            grupo_label: '',
        },

        init() {
            // Si volvió con errores de validación del servidor, ir al paso con el error
            @if($errors->has('nombres') || $errors->has('apellidos') || $errors->has('sexo') || $errors->has('fecha_nacimiento') || $errors->has('foto'))
                this.step = 1;
            @elseif($errors->has('email'))
                this.step = 2;
            @elseif($errors->has('tutor_email'))
                this.step = 3;
            @elseif($errors->has('numero_matricula') || $errors->has('estado'))
                this.step = 4;
            @endif

            // Inicializar grupo_label desde el select si hay old valor
            this.$nextTick(() => {
                const sel = document.querySelector('[name="grupo_id"]');
                if (sel && sel.value) {
                    this.formData.grupo_label = sel.options[sel.selectedIndex]?.text ?? '';
                }
            });
        },

        validate(s) {
            const req = (name, errId) => {
                const el = document.querySelector(`[name="${name}"]`);
                const errEl = document.getElementById('err_' + name);
                const ok = el && el.value.trim() !== '';
                if (el) el.classList.toggle('is-invalid', !ok);
                if (errEl) errEl.style.display = ok ? 'none' : 'block';
                return ok;
            };

            if (s === 1) {
                return req('nombres', 'err_nombres')
                     & req('apellidos', 'err_apellidos')
                     & req('sexo', 'err_sexo')
                     & req('fecha_nacimiento', 'err_fecha_nacimiento');
            }
            return true;
        },

        next() {
            if (!this.validate(this.step)) return;
            if (this.step < this.steps.length) this.step++;
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },

        prev() {
            if (this.step > 1) this.step--;
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },
    };
}

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
