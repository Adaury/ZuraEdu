@extends('layouts.admin')
@section('page-title', $docente->apellidos . ', ' . $docente->nombres)

@push('styles')
<style>
    .profile-card {
        background: #fff;
        border-radius: 16px;
        border: 1px solid #e5e7eb;
        overflow: hidden;
    }
    .profile-banner {
        height: 90px;
        background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
    }
    .profile-avatar-wrap {
        margin-top: -48px;
        padding: 0 1.5rem 1rem;
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: .75rem;
    }
    .profile-avatar {
        width: 96px; height: 96px;
        border-radius: 50%;
        object-fit: cover;
        border: 4px solid #fff;
        box-shadow: 0 4px 12px rgba(0,0,0,.12);
    }
    .profile-avatar-initials {
        width: 96px; height: 96px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--primary), var(--primary-light));
        color: #fff;
        font-size: 2rem;
        font-weight: 800;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 4px solid #fff;
        box-shadow: 0 4px 12px rgba(0,0,0,.12);
        letter-spacing: .02em;
    }
    .profile-name {
        font-size: 1.25rem;
        font-weight: 800;
        color: #111827;
        margin: 0 0 .15rem;
    }
    .profile-subtitle { font-size: .84rem; color: #6b7280; }
    .info-card {
        background: #fff;
        border-radius: 12px;
        border: 1px solid #e5e7eb;
        padding: 1.25rem;
        margin-bottom: 1.25rem;
    }
    .info-card-title {
        font-size: .75rem;
        font-weight: 700;
        letter-spacing: .1em;
        text-transform: uppercase;
        color: var(--primary);
        border-bottom: 2px solid var(--primary);
        padding-bottom: .4rem;
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        gap: .4rem;
    }
    .info-row {
        display: flex;
        gap: .5rem;
        margin-bottom: .75rem;
        font-size: .875rem;
    }
    .info-label {
        flex: 0 0 150px;
        color: #9ca3af;
        font-size: .78rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: .04em;
        padding-top: 2px;
    }
    .info-value {
        flex: 1;
        color: #111827;
        font-weight: 500;
    }
    .badge-activo   { background: #d1fae5; color: #065f46; }
    .badge-inactivo { background: #fee2e2; color: #991b1b; }
    .status-badge {
        font-size: .72rem;
        font-weight: 600;
        padding: .3rem .7rem;
        border-radius: 20px;
        letter-spacing: .03em;
    }
    .asignacion-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: .6rem 0;
        border-bottom: 1px solid #f3f4f6;
        font-size: .875rem;
    }
    .asignacion-row:last-child { border-bottom: none; }

    [data-theme="dark"] .profile-card { background: #1e293b; border-color: #334155; }
    [data-theme="dark"] .profile-avatar { border-color: #1e293b; }
    [data-theme="dark"] .badge-activo { background: #052e16; color: #4ade80; }
    [data-theme="dark"] .badge-inactivo { background: #1c0000; color: #f87171; }
    [data-theme="dark"] .asignacion-row { border-bottom-color: #334155; }
</style>
@endpush

@section('content')

{{-- Breadcrumb / back --}}
<div class="d-flex align-items-center gap-3 mb-4">
    <a href="{{ route('admin.docentes.index') }}"
       class="btn btn-sm btn-outline-secondary" style="border-radius:8px;">
        <i class="bi bi-arrow-left me-1"></i>Volver
    </a>
    <div class="ms-auto d-flex gap-2 flex-wrap">
        <a href="{{ route('admin.perfiles.docente', $docente) }}"
           class="btn btn-sm btn-outline-primary" style="border-radius:8px;">
            <i class="bi bi-person-circle me-1"></i>Ver Perfil Completo
        </a>
        <a href="{{ route('admin.observaciones.index') }}?docente_id={{ $docente->id }}"
           class="btn btn-sm btn-outline-warning" style="border-radius:8px;">
            <i class="bi bi-chat-square-text me-1"></i>Observaciones
        </a>
        <a href="{{ route('admin.docentes.edit', $docente) }}"
           class="btn btn-sm" style="background:var(--primary);color:#fff;border-radius:8px;">
            <i class="bi bi-pencil me-1"></i>Editar
        </a>
        <button type="button"
                class="btn btn-sm btn-outline-danger"
                style="border-radius:8px;"
                data-bs-toggle="modal"
                data-bs-target="#modalDelete">
            <i class="bi bi-trash me-1"></i>Eliminar
        </button>
    </div>
</div>

<div class="row g-4">

    {{-- Left column: profile card --}}
    <div class="col-lg-4">
        <div class="profile-card mb-4">
            <div class="profile-banner"></div>
            <div class="profile-avatar-wrap">
                @if($docente->foto)
                    <img src="{{ asset('storage/'.$docente->foto) }}"
                         alt="{{ $docente->nombres }}"
                         class="profile-avatar">
                @else
                    <div class="profile-avatar-initials">
                        {{ substr($docente->nombres,0,1) }}{{ substr($docente->apellidos,0,1) }}
                    </div>
                @endif
                <span class="status-badge {{ $docente->estado === 'activo' ? 'badge-activo' : 'badge-inactivo' }}">
                    {{ $docente->estado === 'activo' ? 'Activo' : 'Inactivo' }}
                </span>
            </div>
            <div class="px-4 pb-4">
                <h2 class="profile-name">{{ $docente->apellidos }}, {{ $docente->nombres }}</h2>
                @if($docente->especialidad)
                    <p class="profile-subtitle">
                        <i class="bi bi-bookmark me-1"></i>{{ $docente->especialidad }}
                    </p>
                @endif
                @if($docente->email)
                    <p class="profile-subtitle">
                        <i class="bi bi-envelope me-1"></i>{{ $docente->email }}
                    </p>
                @endif
                @if($docente->telefono)
                    <p class="profile-subtitle mb-0">
                        <i class="bi bi-telephone me-1"></i>{{ $docente->telefono }}
                    </p>
                @endif
            </div>
        </div>

        {{-- Asignaciones --}}
        <div class="info-card">
            <div class="info-card-title d-flex align-items-center justify-content-between">
                <span><i class="bi bi-diagram-3"></i>Asignaciones ({{ $docente->asignaciones->count() }})</span>
                <div class="d-flex gap-1">
                    <a href="{{ route('admin.docente.setup', ['docente_id' => $docente->id]) }}"
                       class="btn btn-sm btn-outline-primary"
                       style="border-radius:7px;font-size:.72rem;padding:.25rem .7rem;">
                        <i class="bi bi-gear me-1"></i>Configurar
                    </a>
                    <a href="{{ route('admin.asignaciones.create', ['docente_id' => $docente->id]) }}"
                       class="btn btn-sm"
                       style="background:var(--primary);color:#fff;border-radius:7px;font-size:.72rem;padding:.25rem .7rem;">
                        <i class="bi bi-plus-lg me-1"></i>Nueva
                    </a>
                </div>
            </div>
            @forelse($docente->asignaciones as $asig)
                <div class="asignacion-row">
                    <div>
                        <div class="fw-600" style="color:#111827;font-size:.85rem;">
                            {{ $asig->asignatura->nombre ?? '—' }}
                        </div>
                        <div style="font-size:.76rem;color:#9ca3af;">
                            {{ $asig->grupo->nombre_completo ?? '—' }}
                        </div>
                    </div>
                    <span class="badge {{ $asig->activo ? 'text-bg-success' : 'text-bg-secondary' }}"
                          style="font-size:.7rem;">
                        {{ $asig->activo ? 'Activa' : 'Inactiva' }}
                    </span>
                </div>
            @empty
                <div class="text-center py-3">
                    <p class="text-muted mb-2" style="font-size:.84rem;">Sin asignaciones registradas.</p>
                    <a href="{{ route('admin.docente.setup', ['docente_id' => $docente->id]) }}"
                       class="btn btn-sm btn-outline-primary" style="font-size:.78rem;border-radius:7px;">
                        <i class="bi bi-gear me-1"></i>Configurar materias
                    </a>
                </div>
            @endforelse
        </div>
    </div>

    {{-- Right column: detail cards --}}
    <div class="col-lg-8">

        {{-- Personal --}}
        <div class="info-card">
            <div class="info-card-title"><i class="bi bi-person-vcard"></i>Información Personal</div>
            <div class="row">
                <div class="col-md-6">
                    <div class="info-row">
                        <span class="info-label">Cédula</span>
                        <span class="info-value" style="font-family:monospace;">{{ $docente->cedula ?? '—' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Nombres</span>
                        <span class="info-value">{{ $docente->nombres }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Apellidos</span>
                        <span class="info-value">{{ $docente->apellidos }}</span>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="info-row">
                        <span class="info-label">Nacimiento</span>
                        <span class="info-value">
                            {{ $docente->fecha_nacimiento ? $docente->fecha_nacimiento->format('d/m/Y') : '—' }}
                        </span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Sexo</span>
                        <span class="info-value">
                            @if($docente->sexo === 'M') Masculino
                            @elseif($docente->sexo === 'F') Femenino
                            @else —
                            @endif
                        </span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Registro</span>
                        <span class="info-value">{{ $docente->created_at->format('d/m/Y') }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Contact --}}
        <div class="info-card">
            <div class="info-card-title"><i class="bi bi-telephone"></i>Información de Contacto</div>
            <div class="row">
                <div class="col-md-6">
                    <div class="info-row">
                        <span class="info-label">Teléfono</span>
                        <span class="info-value">{{ $docente->telefono ?? '—' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Correo</span>
                        <span class="info-value">
                            @if($docente->email)
                                <a href="mailto:{{ $docente->email }}" style="color:var(--primary);">{{ $docente->email }}</a>
                            @else —
                            @endif
                        </span>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="info-row">
                        <span class="info-label">Dirección</span>
                        <span class="info-value">{{ $docente->direccion ?? '—' }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Professional --}}
        <div class="info-card">
            <div class="info-card-title"><i class="bi bi-mortarboard"></i>Información Profesional</div>
            <div class="row">
                <div class="col-md-6">
                    <div class="info-row">
                        <span class="info-label">Especialidad</span>
                        <span class="info-value">{{ $docente->especialidad ?? '—' }}</span>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="info-row">
                        <span class="info-label">Título Académico</span>
                        <span class="info-value">{{ $docente->titulo_academico ?? '—' }}</span>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

{{-- Delete Modal --}}
<div class="modal fade" id="modalDelete" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:420px;">
        <div class="modal-content border-0 shadow" style="border-radius:16px;">
            <div class="modal-body p-4 text-center">
                <div class="mb-3" style="font-size:2.5rem;color:var(--secondary);">
                    <i class="bi bi-exclamation-triangle"></i>
                </div>
                <h5 class="fw-700 mb-2" style="color:#111827;">¿Eliminar docente?</h5>
                <p class="text-muted mb-4" style="font-size:.88rem;">
                    Se eliminará permanentemente el registro de
                    <strong>{{ $docente->apellidos }}, {{ $docente->nombres }}</strong>.
                    Esta acción no se puede deshacer.
                </p>
                <div class="d-flex gap-2 justify-content-center">
                    <button class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Cancelar</button>
                    <form method="POST" action="{{ route('admin.docentes.destroy', $docente) }}">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn px-4"
                                style="background:var(--secondary);color:#fff;border-radius:8px;">
                            Eliminar
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
