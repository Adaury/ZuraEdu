@extends('layouts.admin')
@section('page-title', 'Convertir a Matrícula')

@push('styles')
<style>
.conv-card { background:#fff; border:1px solid #e5e7eb; border-radius:14px; overflow:hidden; margin-bottom:1.25rem; }
.conv-header { padding:1rem 1.5rem; border-bottom:1px solid #f1f5f9; display:flex; align-items:center; gap:.5rem; }
.conv-header span { font-size:.75rem; font-weight:700; text-transform:uppercase; letter-spacing:.06em; color:#2563eb; }
.conv-body { padding:1.25rem 1.5rem; }
.info-grid { display:grid; grid-template-columns:repeat(2,1fr); gap:.65rem 1.5rem; }
@media(max-width:600px){ .info-grid { grid-template-columns:1fr; } }
.info-item label { font-size:.72rem; font-weight:700; text-transform:uppercase; letter-spacing:.05em; color:#94a3b8; display:block; margin-bottom:.2rem; }
.info-item span { font-size:.9rem; color:#0f172a; font-weight:500; }
.badge-aprobada { background:#d1fae5; color:#065f46; font-size:.78rem; font-weight:700; padding:.25rem .75rem; border-radius:20px; }

[data-theme="dark"] .conv-card { background:#1e293b; border-color:#334155; }
[data-theme="dark"] .conv-header { border-color:#334155; }
[data-theme="dark"] .info-item span { color:#e2e8f0; }
</style>
@endpush

@section('content')

<div class="d-flex align-items-center gap-2 mb-3 flex-wrap">
    <a href="{{ route('admin.pre-matriculas.show', $preMatricula) }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Volver
    </a>
    <h5 class="fw-bold mb-0">
        <i class="bi bi-person-check-fill text-success me-1"></i>
        Convertir en Matrícula
    </h5>
    <span class="badge-aprobada ms-1"><i class="bi bi-check-circle-fill me-1"></i>Aprobada</span>
</div>

@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show mb-3">
    <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ session('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="row g-3">
    {{-- Resumen de la solicitud --}}
    <div class="col-lg-5">
        <div class="conv-card">
            <div class="conv-header">
                <i class="bi bi-person-fill text-primary"></i>
                <span>Datos de la Solicitud</span>
            </div>
            <div class="conv-body">
                <div class="info-grid">
                    <div class="info-item">
                        <label>Nombres</label>
                        <span>{{ $preMatricula->nombres }}</span>
                    </div>
                    <div class="info-item">
                        <label>Apellidos</label>
                        <span>{{ $preMatricula->apellidos }}</span>
                    </div>
                    <div class="info-item">
                        <label>Fecha de Nacimiento</label>
                        <span>{{ $preMatricula->fecha_nacimiento->format('d/m/Y') }}</span>
                    </div>
                    <div class="info-item">
                        <label>Género</label>
                        <span>{{ $preMatricula->genero ?? '—' }}</span>
                    </div>
                    <div class="info-item" style="grid-column:1/-1;">
                        <label>Grado Solicitado</label>
                        <span style="background:#eef2ff;color:#4f46e5;border-radius:6px;padding:.15rem .55rem;font-weight:700;">
                            {{ $preMatricula->grado_solicitado }}
                        </span>
                    </div>
                    <div class="info-item" style="grid-column:1/-1;margin-top:.5rem;padding-top:.75rem;border-top:1px solid #f1f5f9;">
                        <label>Representante</label>
                        <span>{{ $preMatricula->nombre_representante }}</span>
                    </div>
                    <div class="info-item">
                        <label>Cédula Representante</label>
                        <span>{{ $preMatricula->cedula_representante }}</span>
                    </div>
                    <div class="info-item">
                        <label>Teléfono</label>
                        <span>{{ $preMatricula->telefono }}</span>
                    </div>
                    <div class="info-item" style="grid-column:1/-1;">
                        <label>Correo</label>
                        <span>{{ $preMatricula->email }}</span>
                    </div>
                </div>
            </div>
        </div>

        @if(!$schoolYear)
        <div class="alert alert-warning">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            No hay un año escolar activo. Configure el año escolar antes de procesar matrículas.
        </div>
        @else
        <div class="conv-card">
            <div class="conv-header" style="background:#f0fdf4;border-bottom-color:#bbf7d0;">
                <i class="bi bi-calendar-check-fill text-success"></i>
                <span style="color:#15803d;">Año Escolar Activo</span>
            </div>
            <div class="conv-body">
                <div style="font-size:.95rem;font-weight:700;color:#15803d;">{{ $schoolYear->nombre }}</div>
                <div style="font-size:.8rem;color:#64748b;">
                    {{ $schoolYear->fecha_inicio?->format('d/m/Y') }} — {{ $schoolYear->fecha_fin?->format('d/m/Y') }}
                </div>
            </div>
        </div>
        @endif
    </div>

    {{-- Formulario de conversión --}}
    <div class="col-lg-7">
        @if($schoolYear)
        <form action="{{ route('admin.pre-matriculas.ejecutar-convertir', $preMatricula) }}" method="POST">
        @csrf

        {{-- Selección de grupo --}}
        <div class="conv-card">
            <div class="conv-header">
                <i class="bi bi-people-fill text-primary"></i>
                <span>Asignar Grupo</span>
            </div>
            <div class="conv-body">
                @error('grupo_id')
                <div class="text-danger small mb-2"><i class="bi bi-exclamation-circle me-1"></i>{{ $message }}</div>
                @enderror
                <label class="form-label small fw-semibold">Grupo / Sección <span class="text-danger">*</span></label>
                <select name="grupo_id" class="form-select @error('grupo_id') is-invalid @enderror" required>
                    <option value="">— Seleccione el grupo —</option>
                    @php
                        $gradoActual = null;
                        $gradoSolicitado = strtolower($preMatricula->grado_solicitado ?? '');
                    @endphp
                    @foreach($grupos as $g)
                        @php $gradoNombre = $g->grado?->nombre; @endphp
                        @if($gradoNombre !== $gradoActual)
                            @if($gradoActual !== null) </optgroup> @endif
                            <optgroup label="{{ $gradoNombre }}">
                            @php $gradoActual = $gradoNombre; @endphp
                        @endif
                        @php
                            $label = $g->grado?->nombre . ' ' . $g->seccion?->nombre;
                            $match = str_contains(strtolower($label), $gradoSolicitado) || str_contains($gradoSolicitado, strtolower($g->grado?->nombre ?? ''));
                        @endphp
                        <option value="{{ $g->id }}" {{ old('grupo_id') == $g->id ? 'selected' : ($match ? 'selected' : '') }}>
                            {{ $label }}
                            ({{ $g->matriculas()->where('estado', 'activa')->count() }} alumnos)
                        </option>
                    @endforeach
                    @if($gradoActual !== null) </optgroup> @endif
                </select>
                <div class="form-text text-muted" style="font-size:.75rem;">
                    El grupo sugerido coincide con el grado solicitado: <strong>{{ $preMatricula->grado_solicitado }}</strong>
                </div>
            </div>
        </div>

        {{-- Cuenta de usuario --}}
        <div class="conv-card">
            <div class="conv-header">
                <i class="bi bi-person-lock text-primary"></i>
                <span>Cuenta de Acceso del Representante</span>
            </div>
            <div class="conv-body">
                @php
                    $repExiste = \App\Models\Representante::where('cedula', $preMatricula->cedula_representante)->exists();
                @endphp

                @if($repExiste)
                <div class="alert alert-info py-2 px-3 mb-3" style="font-size:.82rem;">
                    <i class="bi bi-info-circle-fill me-1"></i>
                    Ya existe un representante con la cédula <strong>{{ $preMatricula->cedula_representante }}</strong> en el sistema.
                    Se vinculará automáticamente al estudiante sin crear una cuenta nueva.
                </div>
                @else
                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" name="crear_usuario" id="crearUsuario"
                           value="1" checked onchange="toggleUserInfo(this)">
                    <label class="form-check-label fw-semibold" for="crearUsuario" style="font-size:.88rem;">
                        Crear cuenta de acceso al Portal de Representantes
                    </label>
                </div>
                <div id="userInfoBox" class="p-3 rounded" style="background:#eff6ff;border:1px solid #bfdbfe;font-size:.82rem;">
                    <div class="mb-1"><i class="bi bi-envelope-fill text-primary me-1"></i>
                        Usuario (email): <strong>{{ $preMatricula->email }}</strong></div>
                    <div><i class="bi bi-key-fill text-warning me-1"></i>
                        Se generará una contraseña temporal y se enviará por correo al representante.</div>
                </div>
                @endif
            </div>
        </div>

        {{-- Observaciones --}}
        <div class="conv-card">
            <div class="conv-header">
                <i class="bi bi-chat-left-text-fill text-secondary"></i>
                <span style="color:#64748b;">Observaciones (opcional)</span>
            </div>
            <div class="conv-body">
                <textarea name="observaciones" rows="2" class="form-control form-control-sm"
                          placeholder="Notas sobre la matrícula...">{{ old('observaciones') }}</textarea>
            </div>
        </div>

        {{-- Confirmación --}}
        <div style="background:#fef9c3;border:1.5px solid #fcd34d;border-radius:12px;padding:1rem 1.25rem;margin-bottom:1rem;">
            <div style="font-size:.82rem;font-weight:700;color:#92400e;margin-bottom:.4rem;">
                <i class="bi bi-exclamation-triangle-fill me-1"></i> Confirmar acción
            </div>
            <div style="font-size:.79rem;color:#78350f;line-height:1.6;">
                Esta acción creará un expediente de estudiante para <strong>{{ $preMatricula->nombre_completo }}</strong>
                y registrará su matrícula en el año escolar <strong>{{ $schoolYear->nombre }}</strong>.
                Esta operación no se puede deshacer fácilmente.
            </div>
        </div>

        <div class="d-flex justify-content-end gap-2">
            <a href="{{ route('admin.pre-matriculas.show', $preMatricula) }}"
               class="btn btn-outline-secondary">Cancelar</a>
            <button type="submit" class="btn btn-success px-4"
                    onclick="return confirm('¿Confirmar la creación de la matrícula para {{ $preMatricula->nombre_completo }}?')">
                <i class="bi bi-person-check-fill me-1"></i> Confirmar y Matricular
            </button>
        </div>

        </form>
        @endif
    </div>
</div>

@push('scripts')
<script>
function toggleUserInfo(cb) {
    document.getElementById('userInfoBox').style.opacity = cb.checked ? '1' : '.4';
}
</script>
@endpush

@endsection
