@extends('layouts.admin')

@section('page-title', 'Sesiones de Tutoría')

@push('styles')
<style>
    /* ── Layout general ─────────────────────────── */
    .card-panel {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        box-shadow: 0 1px 6px rgba(30,58,110,.05);
    }
    .section-title {
        font-size: .68rem;
        font-weight: 700;
        letter-spacing: .1em;
        text-transform: uppercase;
        color: var(--primary);
        padding-bottom: .4rem;
        border-bottom: 1px solid #e5e7eb;
        margin-bottom: 1rem;
    }
    .form-label {
        font-size: .8rem;
        font-weight: 600;
        color: #374151;
        margin-bottom: .3rem;
    }
    .form-control, .form-select {
        border-radius: 8px;
        border: 1px solid #d1d5db;
        font-size: .875rem;
        padding: .5rem .75rem;
        transition: border-color .15s, box-shadow .15s;
    }
    .form-control:focus, .form-select:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(30,58,110,.1);
    }
    .form-control.is-invalid { border-color: #dc2626; }
    .invalid-feedback { font-size: .75rem; }

    /* ── Línea de tiempo ────────────────────────── */
    .timeline { position: relative; padding-left: 2rem; }
    .timeline::before {
        content: '';
        position: absolute;
        left: .6rem;
        top: 0; bottom: 0;
        width: 2px;
        background: #e5e7eb;
        border-radius: 2px;
    }
    .tl-item {
        position: relative;
        margin-bottom: 1.25rem;
    }
    .tl-dot {
        position: absolute;
        left: -1.55rem;
        top: .35rem;
        width: 14px; height: 14px;
        border-radius: 50%;
        background: var(--primary);
        border: 2px solid #fff;
        box-shadow: 0 0 0 2px var(--primary);
        flex-shrink: 0;
    }
    .tl-card {
        background: #f8fafc;
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        padding: .9rem 1rem;
        font-size: .84rem;
    }
    .tl-card:hover { border-color: #bfdbfe; background: #fafbff; }
    .tl-date {
        font-size: .72rem;
        color: #6b7280;
        font-weight: 600;
    }
    .tl-tema {
        font-weight: 700;
        color: #1e293b;
        font-size: .88rem;
        margin-bottom: .3rem;
    }
    .tl-meta-label {
        font-size: .7rem;
        font-weight: 700;
        letter-spacing: .05em;
        text-transform: uppercase;
        color: #9ca3af;
        margin-bottom: .15rem;
    }
    .tl-meta-text {
        font-size: .81rem;
        color: #374151;
        white-space: pre-line;
    }
    .chip-proxima {
        display: inline-block;
        background: #eff6ff;
        color: #1d4ed8;
        border-radius: 20px;
        padding: .15rem .65rem;
        font-size: .72rem;
        font-weight: 700;
    }
    .empty-state { text-align: center; padding: 2.5rem 1.5rem; color: #9ca3af; }
    .empty-state i { font-size: 2rem; display: block; margin-bottom: .6rem; color: #d1d5db; }

    /* ── Dark mode ──────────────────────────────── */
    [data-theme="dark"] .card-panel { background: #1e293b; border-color: #334155; }
    [data-theme="dark"] .section-title { border-color: #334155; }
    [data-theme="dark"] .form-label { color: #cbd5e1; }
    [data-theme="dark"] .form-control,
    [data-theme="dark"] .form-select {
        background: #0f172a; border-color: #334155; color: #e2e8f0;
    }
    [data-theme="dark"] .tl-card { background: #273548; border-color: #334155; }
    [data-theme="dark"] .tl-card:hover { background: #2d3f58; border-color: #3b82f6; }
    [data-theme="dark"] .tl-tema { color: #e2e8f0; }
    [data-theme="dark"] .tl-meta-text { color: #cbd5e1; }
    [data-theme="dark"] .timeline::before { background: #334155; }
    [data-theme="dark"] .tl-dot { border-color: #1e293b; }
</style>
@endpush

@section('content')

{{-- Breadcrumb --}}
<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb mb-0" style="font-size:.8rem;">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="text-decoration-none">Inicio</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.tutorias.index') }}" class="text-decoration-none">Tutorías</a></li>
        <li class="breadcrumb-item active">Sesiones</li>
    </ol>
</nav>

{{-- Header tutor + grupo --}}
<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
    <div>
        <h1 class="h4 fw-bold mb-0" style="color:var(--primary);">
            <i class="bi bi-calendar-check me-2"></i>Sesiones de Tutoría
        </h1>
        <p class="text-muted mb-0 mt-1" style="font-size:.82rem;">
            <strong>{{ $tutoria->docente->nombre_completo ?? '—' }}</strong>
            &mdash; Grupo: <strong>{{ $tutoria->grupo->nombre_completo ?? '—' }}</strong>
            &mdash; {{ $tutoria->sesiones->count() }} sesión(es) registrada(s)
        </p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('admin.tutorias.informe-pdf', $tutoria) }}"
           target="_blank"
           class="btn btn-sm fw-semibold"
           style="background:#fef2f2;color:#dc2626;border:1px solid #fecaca;border-radius:8px;padding:.4rem .9rem;font-size:.8rem;">
            <i class="bi bi-file-earmark-pdf me-1"></i>Informe PDF
        </a>
        <a href="{{ route('admin.tutorias.index') }}"
           class="btn btn-sm btn-outline-secondary" style="border-radius:8px;">
            <i class="bi bi-arrow-left me-1"></i>Volver
        </a>
    </div>
</div>

{{-- Alertas --}}
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-3" role="alert" style="border-radius:10px;">
        <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif
@if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-3" role="alert" style="border-radius:10px;">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        Corrige los errores en el formulario antes de continuar.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="row g-4">

    {{-- ── COLUMNA IZQUIERDA: formulario ──────────────────────── --}}
    <div class="col-lg-5">
        <div class="card-panel p-4">

            @isset($sesion)
                {{-- EDITAR --}}
                <p class="section-title"><i class="bi bi-pencil-square me-1"></i>Editar Sesión</p>
                <form action="{{ route('admin.tutorias.sesiones.update', [$tutoria, $sesion]) }}"
                      method="POST" novalidate>
                    @csrf @method('PUT')
            @else
                {{-- NUEVA --}}
                <p class="section-title"><i class="bi bi-plus-circle me-1"></i>Registrar Nueva Sesión</p>
                <form action="{{ route('admin.tutorias.sesiones.store', $tutoria) }}"
                      method="POST" novalidate>
                    @csrf
            @endisset

                {{-- Fecha --}}
                <div class="mb-3">
                    <label class="form-label" for="fecha">Fecha <span class="text-danger">*</span></label>
                    <input type="date" name="fecha" id="fecha"
                           class="form-control @error('fecha') is-invalid @enderror"
                           value="{{ old('fecha', isset($sesion) ? $sesion->fecha?->format('Y-m-d') : '') }}"
                           required>
                    @error('fecha')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                {{-- Tema --}}
                <div class="mb-3">
                    <label class="form-label" for="tema">Tema <span class="text-danger">*</span></label>
                    <input type="text" name="tema" id="tema"
                           class="form-control @error('tema') is-invalid @enderror"
                           value="{{ old('tema', $sesion->tema ?? '') }}"
                           maxlength="255"
                           placeholder="Ej: Orientación vocacional, seguimiento académico..."
                           required>
                    @error('tema')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                {{-- Descripción --}}
                <div class="mb-3">
                    <label class="form-label" for="descripcion">Descripción / Desarrollo</label>
                    <textarea name="descripcion" id="descripcion" rows="3"
                              class="form-control @error('descripcion') is-invalid @enderror"
                              placeholder="Resumen de la sesión, actividades realizadas...">{{ old('descripcion', $sesion->descripcion ?? '') }}</textarea>
                    @error('descripcion')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                {{-- Estudiantes atendidos --}}
                <div class="mb-3">
                    <label class="form-label" for="estudiantes_atendidos">Estudiantes Atendidos</label>
                    <textarea name="estudiantes_atendidos" id="estudiantes_atendidos" rows="2"
                              class="form-control @error('estudiantes_atendidos') is-invalid @enderror"
                              placeholder="Nombres o cantidad de estudiantes atendidos individualmente...">{{ old('estudiantes_atendidos', $sesion->estudiantes_atendidos ?? '') }}</textarea>
                    @error('estudiantes_atendidos')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                {{-- Acuerdos --}}
                <div class="mb-3">
                    <label class="form-label" for="acuerdos">Acuerdos / Compromisos</label>
                    <textarea name="acuerdos" id="acuerdos" rows="2"
                              class="form-control @error('acuerdos') is-invalid @enderror"
                              placeholder="Compromisos del estudiante, familia o docente...">{{ old('acuerdos', $sesion->acuerdos ?? '') }}</textarea>
                    @error('acuerdos')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                {{-- Próxima sesión --}}
                <div class="mb-4">
                    <label class="form-label" for="proxima_sesion">Próxima Sesión (fecha tentativa)</label>
                    <input type="date" name="proxima_sesion" id="proxima_sesion"
                           class="form-control @error('proxima_sesion') is-invalid @enderror"
                           value="{{ old('proxima_sesion', isset($sesion) ? $sesion->proxima_sesion?->format('Y-m-d') : '') }}">
                    @error('proxima_sesion')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                {{-- Botones --}}
                <div class="d-flex gap-2 justify-content-end">
                    @isset($sesion)
                        <a href="{{ route('admin.tutorias.sesiones', $tutoria) }}"
                           class="btn btn-sm btn-outline-secondary" style="border-radius:8px;">
                            Cancelar
                        </a>
                        <button type="submit"
                                class="btn btn-sm fw-semibold"
                                style="background:#d97706;color:#fff;border-radius:8px;padding:.45rem 1.1rem;">
                            <i class="bi bi-save me-1"></i>Actualizar
                        </button>
                    @else
                        <button type="submit"
                                class="btn btn-sm fw-semibold"
                                style="background:var(--primary);color:#fff;border-radius:8px;padding:.45rem 1.1rem;">
                            <i class="bi bi-plus-lg me-1"></i>Registrar Sesión
                        </button>
                    @endisset
                </div>

            </form>
        </div>
    </div>

    {{-- ── COLUMNA DERECHA: historial ─────────────────────────── --}}
    <div class="col-lg-7">
        <div class="card-panel p-4">
            <p class="section-title"><i class="bi bi-clock-history me-1"></i>Historial de Sesiones</p>

            @if($tutoria->sesiones->isEmpty())
                <div class="empty-state">
                    <i class="bi bi-calendar-x"></i>
                    <p class="mb-0" style="font-size:.84rem;">Aún no hay sesiones registradas.</p>
                </div>
            @else
                <div class="timeline">
                    @foreach($tutoria->sesiones as $s)
                    <div class="tl-item">
                        <div class="tl-dot"></div>
                        <div class="tl-card">
                            <div class="d-flex align-items-start justify-content-between gap-2 flex-wrap">
                                <div class="flex-grow-1">
                                    <div class="tl-date mb-1">
                                        <i class="bi bi-calendar3 me-1"></i>
                                        {{ $s->fecha?->translatedFormat('d \d\e F Y') ?? $s->fecha?->format('d/m/Y') }}
                                    </div>
                                    <div class="tl-tema">{{ $s->tema }}</div>

                                    @if($s->descripcion)
                                    <div class="mb-2">
                                        <div class="tl-meta-label">Descripción</div>
                                        <div class="tl-meta-text">{{ $s->descripcion }}</div>
                                    </div>
                                    @endif

                                    @if($s->estudiantes_atendidos)
                                    <div class="mb-2">
                                        <div class="tl-meta-label"><i class="bi bi-people me-1"></i>Estudiantes atendidos</div>
                                        <div class="tl-meta-text">{{ $s->estudiantes_atendidos }}</div>
                                    </div>
                                    @endif

                                    @if($s->acuerdos)
                                    <div class="mb-2">
                                        <div class="tl-meta-label"><i class="bi bi-check2-square me-1"></i>Acuerdos</div>
                                        <div class="tl-meta-text">{{ $s->acuerdos }}</div>
                                    </div>
                                    @endif

                                    @if($s->proxima_sesion)
                                    <div class="mt-2">
                                        <span class="chip-proxima">
                                            <i class="bi bi-calendar-plus me-1"></i>
                                            Próxima: {{ $s->proxima_sesion->format('d/m/Y') }}
                                        </span>
                                    </div>
                                    @endif
                                </div>

                                {{-- Acciones --}}
                                <div class="d-flex gap-1 flex-shrink-0">
                                    <a href="{{ route('admin.tutorias.sesiones.edit', [$tutoria, $s]) }}"
                                       class="btn btn-sm"
                                       style="background:#eff6ff;color:#1d4ed8;border:1px solid #bfdbfe;border-radius:6px;font-size:.73rem;padding:.25rem .55rem;"
                                       title="Editar sesión">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form action="{{ route('admin.tutorias.sesiones.destroy', [$tutoria, $s]) }}"
                                          method="POST"
                                          onsubmit="return confirm('¿Eliminar esta sesión del {{ $s->fecha?->format('d/m/Y') }}?');">
                                        @csrf @method('DELETE')
                                        <button type="submit"
                                                class="btn btn-sm"
                                                style="background:#fff0f0;color:#dc2626;border:1px solid #fecaca;border-radius:6px;font-size:.73rem;padding:.25rem .55rem;"
                                                title="Eliminar sesión">
                                            <i class="bi bi-trash3"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

</div>

@endsection
