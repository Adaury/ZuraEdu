@extends('layouts.admin')

@section('page-title', 'Caso #' . $caso->id . ' — Seguimiento Social')

@push('styles')
<style>
    .ss-meta-label { font-size:.65rem;letter-spacing:.08em;text-transform:uppercase;font-weight:700;color:#6b7280; }

    /* ── Timeline ── */
    .ss-timeline { position:relative; padding-left:1.6rem; }
    .ss-timeline::before {
        content:''; position:absolute; left:.5rem; top:0; bottom:0;
        width:2px; background:#e0e7ff; border-radius:2px;
    }
    .ss-timeline-item { position:relative; margin-bottom:1.25rem; }
    .ss-timeline-item:last-child { margin-bottom:0; }
    .ss-timeline-dot {
        position:absolute; left:-1.6rem; top:.65rem;
        width:14px; height:14px; border-radius:50%;
        background:#6366f1; border:2px solid #e0e7ff;
        box-shadow:0 0 0 3px #fff;
    }
    .ss-timeline-card {
        background:#f8faff; border:1px solid #e5e7eb;
        border-radius:10px; padding:.85rem 1rem;
    }
    [data-theme="dark"] .ss-timeline::before { background:#334155; }
    [data-theme="dark"] .ss-timeline-dot { border-color:#334155; box-shadow:0 0 0 3px #1e293b; }
    [data-theme="dark"] .ss-timeline-card { background:#1e293b; border-color:#334155; }
</style>
@endpush

@section('content')

@php
    $nivelInfo  = $caso->nivel_riesgo_info;
    $estadoInfo = $caso->estado_info;
    $nivelStyle = [
        'green'  => 'background:#dcfce7;color:#15803d;',
        'yellow' => 'background:#fef9c3;color:#854d0e;',
        'orange' => 'background:#ffedd5;color:#c2410c;',
        'red'    => 'background:#fee2e2;color:#b91c1c;',
    ];
    $estadoStyle = [
        'blue'   => 'background:#dbeafe;color:#1d4ed8;',
        'indigo' => 'background:#e0e7ff;color:#4338ca;',
        'gray'   => 'background:#f3f4f6;color:#374151;',
    ];
@endphp

<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb" style="font-size:.8rem;">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="text-decoration-none">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.seguimiento-social.index') }}" class="text-decoration-none">Seguimiento Social</a></li>
        <li class="breadcrumb-item active">Caso #{{ $caso->id }}</li>
    </ol>
</nav>

{{-- Cabecera --}}
<div class="d-flex align-items-start justify-content-between mb-4 flex-wrap gap-3">
    <div class="d-flex align-items-center gap-3">
        <a href="{{ route('admin.seguimiento-social.index') }}"
           class="btn btn-sm"
           style="background:#f3f4f6;color:#374151;border:1px solid #e5e7eb;border-radius:8px;">
            <i class="bi bi-arrow-left"></i>
        </a>
        <div>
            <h1 class="h5 fw-bold mb-0" style="color:var(--primary);">
                Caso #{{ $caso->id }} &mdash; {{ $caso->tipo_label }}
            </h1>
            <p class="text-muted mb-0 mt-1" style="font-size:.78rem;">
                Abierto: {{ $caso->fecha_apertura?->format('d/m/Y') }}
                @if($caso->fecha_cierre)
                    &nbsp;·&nbsp; Cerrado: {{ $caso->fecha_cierre->format('d/m/Y') }}
                @endif
            </p>
        </div>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('admin.seguimiento-social.informe-pdf', $caso) }}" target="_blank"
           class="btn btn-sm fw-semibold"
           style="background:#f3f4f6;color:#374151;border:1px solid #e5e7eb;border-radius:8px;">
            <i class="bi bi-file-earmark-pdf me-1"></i>Informe PDF
        </a>
        @if($caso->estado !== 'cerrado')
            <button type="button"
                    class="btn btn-sm fw-semibold"
                    style="background:#e0e7ff;color:#4338ca;border:none;border-radius:8px;"
                    data-bs-toggle="modal" data-bs-target="#modalIntervencion">
                <i class="bi bi-plus-lg me-1"></i>Agregar Intervención
            </button>
            <button type="button"
                    class="btn btn-sm fw-semibold"
                    style="background:#f3f4f6;color:#374151;border:1px solid #e5e7eb;border-radius:8px;"
                    data-bs-toggle="modal" data-bs-target="#modalCerrar">
                <i class="bi bi-lock me-1"></i>Cerrar Caso
            </button>
        @endif
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success border-0 mb-4" style="border-radius:10px;font-size:.85rem;">
        <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
    </div>
@endif

<div class="row g-4">

    {{-- ── Columna izquierda ── --}}
    <div class="col-12 col-lg-4">

        {{-- Estudiante --}}
        <div class="card border-0 shadow-sm mb-3" style="border-radius:12px;">
            <div class="card-body p-4">
                <div class="ss-meta-label mb-2">Estudiante</div>
                <div class="fw-bold mb-1" style="font-size:1rem;color:#1e293b;">
                    {{ $caso->estudiante->nombre_completo ?? '—' }}
                </div>
                <div class="text-muted" style="font-size:.78rem;">
                    Matrícula: {{ $caso->estudiante->numero_matricula ?? '—' }}
                </div>
                @if($caso->estudiante->matriculaActiva?->grupo)
                    <div class="text-muted" style="font-size:.78rem;">
                        Grupo: {{ $caso->estudiante->matriculaActiva->grupo->nombre_completo ?? '—' }}
                    </div>
                @endif
                @if($caso->estudiante->tutor_nombre)
                    <div class="mt-2 pt-2 border-top" style="font-size:.75rem;">
                        <span class="text-muted">Tutor: </span>
                        <span style="color:#374151;">{{ $caso->estudiante->tutor_nombre }}</span>
                        @if($caso->estudiante->tutor_telefono)
                            <br><span class="text-muted">Tel: </span>
                            <span style="color:#374151;">{{ $caso->estudiante->tutor_telefono }}</span>
                        @endif
                    </div>
                @endif
            </div>
        </div>

        {{-- Detalles --}}
        <div class="card border-0 shadow-sm mb-3" style="border-radius:12px;">
            <div class="card-body p-4">
                <div class="ss-meta-label mb-3">Detalles del Caso</div>

                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span style="font-size:.78rem;color:#6b7280;">Estado</span>
                    <span class="badge rounded-pill fw-semibold"
                          style="font-size:.7rem;{{ $estadoStyle[$estadoInfo['color']] ?? 'background:#f3f4f6;color:#374151;' }}">
                        {{ $estadoInfo['label'] }}
                    </span>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span style="font-size:.78rem;color:#6b7280;">Nivel de Riesgo</span>
                    <span class="badge rounded-pill fw-semibold"
                          style="font-size:.7rem;{{ $nivelStyle[$nivelInfo['color']] ?? 'background:#f3f4f6;color:#374151;' }}">
                        {{ $nivelInfo['label'] }}
                    </span>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span style="font-size:.78rem;color:#6b7280;">Tipo</span>
                    <span style="font-size:.78rem;font-weight:600;color:#374151;">{{ $caso->tipo_label }}</span>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span style="font-size:.78rem;color:#6b7280;">Responsable</span>
                    <span style="font-size:.78rem;font-weight:600;color:#374151;text-align:right;max-width:55%;">
                        {{ $caso->responsable->nombre_completo ?? '—' }}
                    </span>
                </div>
                <div class="d-flex justify-content-between align-items-center">
                    <span style="font-size:.78rem;color:#6b7280;">Intervenciones</span>
                    <span class="badge rounded-pill fw-bold" style="background:#e0e7ff;color:#4338ca;">
                        {{ $caso->intervencionesDesc->count() }}
                    </span>
                </div>

                <div class="mt-3 pt-2 border-top">
                    <button type="button"
                            class="btn btn-sm w-100 fw-semibold"
                            style="border:1px solid #e5e7eb;color:#374151;border-radius:8px;"
                            data-bs-toggle="modal" data-bs-target="#modalEditar">
                        <i class="bi bi-pencil me-1"></i>Editar Caso
                    </button>
                </div>
            </div>
        </div>

        {{-- Descripción --}}
        <div class="card border-0 shadow-sm" style="border-radius:12px;">
            <div class="card-body p-4">
                <div class="ss-meta-label mb-2">Descripción</div>
                <p style="font-size:.83rem;color:#374151;white-space:pre-line;line-height:1.65;margin:0;">
                    {{ $caso->descripcion }}
                </p>
            </div>
        </div>
    </div>

    {{-- ── Columna derecha: Timeline ── --}}
    <div class="col-12 col-lg-8">
        <div class="card border-0 shadow-sm" style="border-radius:12px;">
            <div class="card-body p-4">
                <div class="ss-meta-label mb-4">Timeline de Intervenciones</div>

                @if($caso->intervencionesDesc->isEmpty())
                    <div class="text-center py-5">
                        <i class="bi bi-chat-square-text" style="font-size:2.8rem;color:#d1d5db;display:block;margin-bottom:.75rem;"></i>
                        <div class="fw-bold text-muted mb-1">Sin intervenciones registradas</div>
                        @if($caso->estado !== 'cerrado')
                            <div class="text-muted" style="font-size:.82rem;">
                                Registra la primera con el botón "Agregar Intervención"
                            </div>
                        @endif
                    </div>
                @else
                    <div class="ss-timeline">
                        @foreach($caso->intervencionesDesc as $intervencion)
                        @php
                            $tipoInfo  = $intervencion->tipo_info;
                            $tipoStyle = [
                                'reunion'    => 'background:#dbeafe;color:#1d4ed8;',
                                'llamada'    => 'background:#dcfce7;color:#15803d;',
                                'visita'     => 'background:#ffedd5;color:#c2410c;',
                                'derivacion' => 'background:#f3e8ff;color:#6d28d9;',
                                'otro'       => 'background:#f3f4f6;color:#374151;',
                            ];
                        @endphp
                        <div class="ss-timeline-item">
                            <div class="ss-timeline-dot"></div>
                            <div class="ss-timeline-card">
                                <div class="d-flex align-items-center gap-2 mb-2 flex-wrap">
                                    <span class="badge rounded-pill fw-semibold"
                                          style="font-size:.7rem;{{ $tipoStyle[$intervencion->tipo_intervencion] ?? $tipoStyle['otro'] }}">
                                        {{ $tipoInfo['label'] }}
                                    </span>
                                    <span style="font-size:.75rem;color:#9ca3af;">
                                        {{ $intervencion->fecha?->format('d/m/Y') }}
                                    </span>
                                </div>
                                <p style="font-size:.83rem;color:#1e293b;white-space:pre-line;line-height:1.6;margin:0;">
                                    {{ $intervencion->descripcion }}
                                </p>
                                @if($intervencion->resultado)
                                    <div class="mt-2 pt-2 border-top">
                                        <div class="ss-meta-label mb-1">Resultado</div>
                                        <p style="font-size:.82rem;color:#374151;white-space:pre-line;margin:0;">
                                            {{ $intervencion->resultado }}
                                        </p>
                                    </div>
                                @endif
                                @if($intervencion->siguiente_accion)
                                    <div class="mt-2 pt-2 border-top">
                                        <div class="ss-meta-label mb-1">Siguiente Acción</div>
                                        <p style="font-size:.82rem;color:#4338ca;white-space:pre-line;margin:0;">
                                            {{ $intervencion->siguiente_accion }}
                                        </p>
                                    </div>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- ═══════════ MODALES Bootstrap 5 ═══════════ --}}

{{-- Modal: Agregar Intervención --}}
<div class="modal fade" id="modalIntervencion" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:14px;border:none;">
            <div class="modal-header border-bottom py-3 px-4">
                <h5 class="modal-title fw-bold" style="font-size:.95rem;">
                    <i class="bi bi-plus-circle me-2" style="color:#6366f1;"></i>Nueva Intervención
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('admin.seguimiento-social.intervenciones.store', $caso) }}">
                @csrf
                <div class="modal-body px-4 py-3">
                    <div class="row g-3 mb-3">
                        <div class="col-md-7">
                            <label class="form-label" style="font-size:.8rem;font-weight:600;">
                                Tipo de Intervención <span class="text-danger">*</span>
                            </label>
                            <select name="tipo_intervencion" required
                                    class="form-select" style="border-radius:8px;font-size:.875rem;">
                                @foreach(\App\Models\IntervencionCaso::TIPOS as $val => $info)
                                    <option value="{{ $val }}">{{ $info['label'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label" style="font-size:.8rem;font-weight:600;">
                                Fecha <span class="text-danger">*</span>
                            </label>
                            <input type="date" name="fecha" required value="{{ now()->format('Y-m-d') }}"
                                   class="form-control" style="border-radius:8px;font-size:.875rem;">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" style="font-size:.8rem;font-weight:600;">
                            Descripción <span class="text-danger">*</span>
                        </label>
                        <textarea name="descripcion" rows="3" required
                                  placeholder="Describe la intervención realizada…"
                                  class="form-control" style="border-radius:8px;font-size:.875rem;resize:vertical;"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" style="font-size:.8rem;font-weight:600;">Resultado</label>
                        <textarea name="resultado" rows="2"
                                  placeholder="Resultado o respuesta obtenida…"
                                  class="form-control" style="border-radius:8px;font-size:.875rem;resize:vertical;"></textarea>
                    </div>
                    <div>
                        <label class="form-label" style="font-size:.8rem;font-weight:600;">Siguiente Acción</label>
                        <textarea name="siguiente_accion" rows="2"
                                  placeholder="¿Qué se hará a continuación?…"
                                  class="form-control" style="border-radius:8px;font-size:.875rem;resize:vertical;"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-top px-4 py-3">
                    <button type="button" class="btn fw-semibold"
                            style="background:#f3f4f6;color:#374151;border:1px solid #e5e7eb;border-radius:8px;"
                            data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn fw-semibold"
                            style="background:#6366f1;color:#fff;border-radius:8px;">
                        <i class="bi bi-check-lg me-1"></i>Registrar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modal: Editar Caso --}}
<div class="modal fade" id="modalEditar" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content" style="border-radius:14px;border:none;">
            <div class="modal-header border-bottom py-3 px-4">
                <h5 class="modal-title fw-bold" style="font-size:.95rem;">
                    <i class="bi bi-pencil me-2" style="color:var(--primary);"></i>Editar Caso #{{ $caso->id }}
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('admin.seguimiento-social.update', $caso) }}">
                @csrf @method('PUT')
                <div class="modal-body px-4 py-3">
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label" style="font-size:.8rem;font-weight:600;">Tipo</label>
                            <select name="tipo" required class="form-select" style="border-radius:8px;font-size:.875rem;">
                                @foreach(\App\Models\CasoSeguimiento::TIPOS as $val => $lbl)
                                    <option value="{{ $val }}" @selected($caso->tipo === $val)>{{ $lbl }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" style="font-size:.8rem;font-weight:600;">Nivel de Riesgo</label>
                            <select name="nivel_riesgo" required class="form-select" style="border-radius:8px;font-size:.875rem;">
                                @foreach(\App\Models\CasoSeguimiento::NIVELES_RIESGO as $val => $info)
                                    <option value="{{ $val }}" @selected($caso->nivel_riesgo === $val)>{{ $info['label'] }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label" style="font-size:.8rem;font-weight:600;">Estado</label>
                            <select name="estado" required class="form-select" style="border-radius:8px;font-size:.875rem;">
                                @foreach(\App\Models\CasoSeguimiento::ESTADOS as $val => $info)
                                    <option value="{{ $val }}" @selected($caso->estado === $val)>{{ $info['label'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" style="font-size:.8rem;font-weight:600;">Responsable</label>
                            <select name="responsable_id" class="form-select" style="border-radius:8px;font-size:.875rem;">
                                <option value="">— Sin asignar —</option>
                                @foreach($responsables as $resp)
                                    <option value="{{ $resp->id }}" @selected($caso->responsable_id == $resp->id)>
                                        {{ $resp->nombre_completo }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label" style="font-size:.8rem;font-weight:600;">Fecha Apertura</label>
                            <input type="date" name="fecha_apertura" required
                                   value="{{ $caso->fecha_apertura?->format('Y-m-d') }}"
                                   class="form-control" style="border-radius:8px;font-size:.875rem;">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" style="font-size:.8rem;font-weight:600;">Fecha Cierre</label>
                            <input type="date" name="fecha_cierre"
                                   value="{{ $caso->fecha_cierre?->format('Y-m-d') }}"
                                   class="form-control" style="border-radius:8px;font-size:.875rem;">
                        </div>
                    </div>
                    <div>
                        <label class="form-label" style="font-size:.8rem;font-weight:600;">Descripción</label>
                        <textarea name="descripcion" rows="4" required
                                  class="form-control"
                                  style="border-radius:8px;font-size:.875rem;resize:vertical;">{{ $caso->descripcion }}</textarea>
                    </div>
                </div>
                <div class="modal-footer border-top px-4 py-3">
                    <button type="button" class="btn fw-semibold"
                            style="background:#f3f4f6;color:#374151;border:1px solid #e5e7eb;border-radius:8px;"
                            data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn fw-semibold"
                            style="background:var(--primary);color:#fff;border-radius:8px;">
                        <i class="bi bi-check-lg me-1"></i>Guardar Cambios
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modal: Cerrar Caso --}}
<div class="modal fade" id="modalCerrar" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content" style="border-radius:14px;border:none;">
            <div class="modal-header border-bottom py-3 px-4">
                <h5 class="modal-title fw-bold" style="font-size:.95rem;">
                    <i class="bi bi-lock me-2" style="color:#374151;"></i>Cerrar Caso
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('admin.seguimiento-social.cerrar', $caso) }}">
                @csrf @method('PATCH')
                <div class="modal-body px-4 py-3">
                    <p class="text-muted mb-3" style="font-size:.83rem;">
                        Al cerrar el caso, el estado cambiará a <strong>Cerrado</strong> y se registrará la fecha de cierre.
                    </p>
                    <div>
                        <label class="form-label" style="font-size:.8rem;font-weight:600;">
                            Fecha de Cierre <span class="text-danger">*</span>
                        </label>
                        <input type="date" name="fecha_cierre" required value="{{ now()->format('Y-m-d') }}"
                               class="form-control" style="border-radius:8px;font-size:.875rem;">
                    </div>
                </div>
                <div class="modal-footer border-top px-4 py-3">
                    <button type="button" class="btn fw-semibold"
                            style="background:#f3f4f6;color:#374151;border:1px solid #e5e7eb;border-radius:8px;"
                            data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn fw-semibold"
                            style="background:#374151;color:#fff;border-radius:8px;">
                        <i class="bi bi-lock me-1"></i>Cerrar Caso
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection
