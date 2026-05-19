@extends('layouts.admin')

@section('page-title', 'Detalle de Matrícula')

@push('styles')
<style>
    .info-card {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        box-shadow: 0 1px 8px rgba(30,58,110,.05);
        overflow: hidden;
    }
    .info-card-header {
        background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
        padding: 1.1rem 1.4rem;
        color: #fff;
    }
    .info-card-header h5 {
        font-size: .72rem;
        font-weight: 700;
        letter-spacing: .1em;
        text-transform: uppercase;
        opacity: .8;
        margin-bottom: .2rem;
    }
    .info-card-header .title {
        font-size: 1rem;
        font-weight: 800;
    }
    .info-card-body { padding: 1.2rem 1.4rem; }
    .info-row {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        padding: .55rem 0;
        border-bottom: 1px solid #f3f4f6;
        font-size: .84rem;
        gap: 1rem;
    }
    .info-row:last-child { border-bottom: none; }
    .info-label {
        color: #2563eb;
        font-weight: 600;
        flex-shrink: 0;
        width: 140px;
    }
    .info-value {
        color: #1e293b;
        font-weight: 600;
        text-align: right;
        word-break: break-word;
    }
    .badge-estado {
        font-size: .68rem;
        font-weight: 700;
        padding: .25rem .65rem;
        border-radius: 20px;
        letter-spacing: .04em;
        text-transform: uppercase;
    }
    .badge-activa    { background: #d1fae5; color: #065f46; }
    .badge-retirada  { background: #fee2e2; color: #991b1b; }
    .badge-trasladada{ background: #fef3c7; color: #92400e; }
    .badge-promovida { background: #dbeafe; color: #1e40af; }
    .student-hero {
        display: flex;
        align-items: center;
        gap: 1rem;
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        padding: 1.2rem 1.4rem;
        margin-bottom: 1.25rem;
        box-shadow: 0 1px 8px rgba(30,58,110,.05);
    }
    .student-hero-avatar {
        width: 60px; height: 60px;
        border-radius: 50%;
        object-fit: cover;
        border: 3px solid #e5e7eb;
        flex-shrink: 0;
    }
    .student-hero-placeholder {
        width: 60px; height: 60px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--primary), var(--primary-light));
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
        font-weight: 800;
        color: #fff;
        border: 3px solid #e5e7eb;
        flex-shrink: 0;
    }
    .grupo-chip {
        background: #eef2ff;
        color: var(--primary);
        border-radius: 6px;
        padding: .15rem .5rem;
        font-size: .78rem;
        font-weight: 700;
    }
    .modal-content { border-radius: 12px; border: none; box-shadow: 0 16px 48px rgba(0,0,0,.15); }
    .modal-header { border-bottom: 1px solid #f3f4f6; padding: 1rem 1.25rem; }
    .modal-footer { border-top: 1px solid #f3f4f6; padding: .75rem 1.25rem; }
    .th-sub {
        font-size: .7rem; font-weight: 700;
        color: #2563eb; text-transform: uppercase;
        letter-spacing: .06em; padding: .5rem .75rem;
    }

    /* ── Dark mode ─────────────────────────────────────── */
    [data-theme="dark"] .info-card {
        background: #1e293b !important;
        border-color: #334155 !important;
    }
    [data-theme="dark"] .info-card-body { background: #1e293b !important; }
    [data-theme="dark"] .info-row { border-color: #334155 !important; }
    [data-theme="dark"] .info-label { color: #60a5fa !important; }
    [data-theme="dark"] .info-value { color: #e2e8f0 !important; }
    [data-theme="dark"] .student-hero {
        background: #1e293b !important;
        border-color: #334155 !important;
    }
    [data-theme="dark"] .student-hero-avatar,
    [data-theme="dark"] .student-hero-placeholder { border-color: #334155 !important; }
    [data-theme="dark"] .grupo-chip {
        background: rgba(59,130,246,.18) !important;
        color: #93c5fd !important;
    }
    [data-theme="dark"] .th-sub { color: #60a5fa !important; }
    [data-theme="dark"] .info-card-header.gray-header {
        background: linear-gradient(135deg,#1e3a8a,#2563eb) !important;
    }
    [data-theme="dark"] .modal-content {
        background: #1e293b !important;
        border-color: #334155 !important;
    }
    [data-theme="dark"] .modal-header,
    [data-theme="dark"] .modal-footer { border-color: #334155 !important; }
</style>
@endpush

@section('content')

{{-- Breadcrumb --}}
<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb" style="font-size:.8rem;">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="text-decoration-none">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.matriculas.index') }}" class="text-decoration-none">Matrículas</a></li>
        <li class="breadcrumb-item active">Detalle</li>
    </ol>
</nav>

{{-- Session alerts --}}
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-3" role="alert" style="border-radius:10px;">
        <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

{{-- Student Hero --}}
<div class="student-hero">
    @if($matricula->estudiante->foto)
        <img src="{{ asset('storage/' . $matricula->estudiante->foto) }}"
             alt="" class="student-hero-avatar">
    @else
        <div class="student-hero-placeholder">
            {{ strtoupper(substr($matricula->estudiante->nombres ?? '?', 0, 1)) }}
        </div>
    @endif
    <div class="flex-grow-1 min-width-0">
        <h2 class="h5 fw-bold mb-0" style="color:var(--primary);">
            {{ $matricula->estudiante->nombre_completo }}
        </h2>
        <div class="d-flex flex-wrap gap-2 mt-1 align-items-center">
            <span style="font-size:.78rem;color:#2563eb;font-weight:700;font-family:monospace;">
                Nº {{ $matricula->estudiante->numero_matricula ?? '—' }}
            </span>
            <span class="badge-estado badge-{{ $matricula->estado ?? 'activa' }}">
                {{ ucfirst($matricula->estado ?? 'activa') }}
            </span>
            @if($matricula->grupo)
                @php
                    $niveles = [1=>'1ro',2=>'2do',3=>'3ro',4=>'4to',5=>'5to',6=>'6to'];
                    $pref = $niveles[$matricula->grupo->grado->nivel ?? 0] ?? ($matricula->grupo->grado->nivel.'mo');
                    $gLabel = $pref . ' ' . ($matricula->grupo->seccion->nombre ?? '');
                @endphp
                <span class="grupo-chip"><i class="bi bi-grid me-1"></i>{{ $gLabel }}</span>
            @endif
        </div>
    </div>
    <div class="d-flex gap-2 flex-shrink-0">
        <a href="{{ route('admin.matriculas.constancia', $matricula) }}" target="_blank"
           class="btn btn-sm fw-semibold"
           style="background:#1e3a6e;color:#fff;border-radius:8px;">
            <i class="bi bi-file-earmark-text me-1"></i>Constancia PDF
        </a>
        <a href="{{ route('admin.matriculas.constancia-estudios', $matricula) }}" target="_blank"
           class="btn btn-sm fw-semibold btn-outline-primary">
            <i class="bi bi-mortarboard me-1"></i>Const. Estudios
        </a>
        <button type="button" class="btn btn-sm fw-semibold" data-bs-toggle="modal" data-bs-target="#editarMatriculaModal"
                style="background:#f0f4f8;color:var(--primary);border:1px solid #dde3ef;border-radius:8px;">
            <i class="bi bi-pencil me-1"></i>Editar
        </button>
        @if(($matricula->estado ?? 'activa') === 'activa')
            <button type="button" class="btn btn-sm fw-semibold" data-bs-toggle="modal" data-bs-target="#cambiarGrupoModal"
                    style="background:#f0f4f8;color:var(--primary);border:1px solid #dde3ef;border-radius:8px;">
                <i class="bi bi-arrow-left-right me-1"></i>Cambiar Grupo
            </button>
            <button type="button" class="btn btn-sm fw-semibold" data-bs-toggle="modal" data-bs-target="#cambiarEstadoModal"
                    style="background:#fff0f0;color:#dc2626;border:1px solid #fecaca;border-radius:8px;">
                <i class="bi bi-tag me-1"></i>Estado
            </button>
        @else
            <button type="button" class="btn btn-sm fw-semibold" data-bs-toggle="modal" data-bs-target="#cambiarEstadoModal"
                    style="background:#f0fdf4;color:#059669;border:1px solid #6ee7b7;border-radius:8px;">
                <i class="bi bi-arrow-counterclockwise me-1"></i>Reactivar
            </button>
        @endif
        <a href="{{ route('admin.matriculas.index') }}" class="btn btn-sm"
           style="background:#f3f4f6;color:#6b7280;border:1px solid #e5e7eb;border-radius:8px;">
            <i class="bi bi-arrow-left me-1"></i>Volver
        </a>
    </div>
</div>

<div class="row g-3">

    {{-- Enrollment Details --}}
    <div class="col-12 col-md-6">
        <div class="info-card h-100">
            <div class="info-card-header">
                <h5>Datos de Matrícula</h5>
                <div class="title">
                    <i class="bi bi-card-list me-1"></i>Información del registro
                </div>
            </div>
            <div class="info-card-body">
                <div class="info-row">
                    <span class="info-label">Año Escolar</span>
                    <span class="info-value">{{ $matricula->grupo->schoolYear->nombre ?? '—' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Grupo</span>
                    <span class="info-value">
                        @if($matricula->grupo)
                            {{ $gLabel ?? $matricula->grupo->nombre_corto }}
                        @else —
                        @endif
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-label">Nº Orden</span>
                    <span class="info-value" style="font-family:monospace;color:#2563eb;">
                        {{ str_pad($matricula->numero_orden ?? $matricula->id, 3, '0', STR_PAD_LEFT) }}
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-label">Nº Matrícula</span>
                    <span class="info-value" style="font-family:monospace;color:#2563eb;">
                        {{ $matricula->estudiante->numero_matricula ?? '—' }}
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-label">Fecha Matrícula</span>
                    <span class="info-value">
                        {{ $matricula->fecha_matricula ? $matricula->fecha_matricula->format('d/m/Y') : '—' }}
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-label">Estado</span>
                    <span class="info-value">
                        <span class="badge-estado badge-{{ $matricula->estado ?? 'activa' }}">
                            {{ ucfirst($matricula->estado ?? 'activa') }}
                        </span>
                    </span>
                </div>
                @if($matricula->observaciones)
                    <div class="info-row">
                        <span class="info-label">Observaciones</span>
                        <span class="info-value" style="font-weight:400;">{{ $matricula->observaciones }}</span>
                    </div>
                @endif
                <div class="info-row">
                    <span class="info-label">Registrado</span>
                    <span class="info-value text-muted" style="font-weight:400;font-size:.78rem;">
                        {{ $matricula->created_at->format('d/m/Y H:i') }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    {{-- Student Info --}}
    <div class="col-12 col-md-6">
        <div class="info-card h-100">
            <div class="info-card-header gray-header" style="background:linear-gradient(135deg,#1e40af,#3b82f6);">
                <h5>Datos del Estudiante</h5>
                <div class="title">
                    <i class="bi bi-person me-1"></i>{{ $matricula->estudiante->nombre_completo }}
                </div>
            </div>
            <div class="info-card-body">
                <div class="info-row">
                    <span class="info-label">Cédula</span>
                    <span class="info-value" style="font-family:monospace;">{{ $matricula->estudiante->cedula ?? '—' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Fecha Nacimiento</span>
                    <span class="info-value">
                        @if($matricula->estudiante->fecha_nacimiento)
                            {{ $matricula->estudiante->fecha_nacimiento->format('d/m/Y') }}
                            <span class="text-muted" style="font-size:.72rem;">({{ $matricula->estudiante->edad }} años)</span>
                        @else —
                        @endif
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-label">Sexo</span>
                    <span class="info-value">{{ ucfirst($matricula->estudiante->sexo ?? '—') }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Teléfono</span>
                    <span class="info-value">{{ $matricula->estudiante->telefono ?? '—' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Tutor/Apoderado</span>
                    <span class="info-value" style="font-weight:400;">
                        {{ $matricula->estudiante->tutor_nombre ?? '—' }}
                        @if($matricula->estudiante->tutor_parentesco)
                            <br><span class="text-muted" style="font-size:.72rem;">{{ $matricula->estudiante->tutor_parentesco }}</span>
                        @endif
                    </span>
                </div>
            </div>
        </div>
    </div>

    {{-- Representantes --}}
    @if($matricula->estudiante->representantes && $matricula->estudiante->representantes->isNotEmpty())
    <div class="col-12 col-md-6">
        <div class="info-card h-100">
            <div class="info-card-header" style="background:linear-gradient(135deg,#7c3aed,#a78bfa);">
                <h5>Representantes / Tutores</h5>
                <div class="title">
                    <i class="bi bi-people me-1"></i>
                    {{ $matricula->estudiante->representantes->count() }} representante(s)
                </div>
            </div>
            <div class="info-card-body" style="padding:0;">
                @foreach($matricula->estudiante->representantes as $rep)
                    <div style="padding:.85rem 1.2rem;{{ !$loop->last ? 'border-bottom:1px solid #f3f4f6;' : '' }}">
                        <div class="d-flex align-items-start gap-2">
                            <div style="width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,#7c3aed,#a78bfa);
                                        display:flex;align-items:center;justify-content:center;
                                        font-size:.75rem;font-weight:800;color:#fff;flex-shrink:0;">
                                {{ strtoupper(substr($rep->nombres ?? $rep->nombre ?? '?', 0, 1)) }}
                            </div>
                            <div class="flex-grow-1">
                                <div style="font-weight:700;font-size:.85rem;color:#1e293b;">
                                    {{ trim(($rep->nombres ?? $rep->nombre ?? '') . ' ' . ($rep->apellidos ?? $rep->apellido ?? '')) ?: '—' }}
                                </div>
                                <div class="d-flex flex-wrap gap-2 mt-1">
                                    @if($rep->parentesco ?? $rep->vinculo ?? null)
                                        <span style="font-size:.72rem;background:#ede9fe;color:#7c3aed;
                                                     border-radius:5px;padding:.1rem .45rem;font-weight:700;">
                                            {{ $rep->parentesco ?? $rep->vinculo }}
                                        </span>
                                    @endif
                                    @if($rep->celular ?? $rep->telefono ?? null)
                                        <span style="font-size:.72rem;color:#2563eb;font-family:monospace;font-weight:700;">
                                            <i class="bi bi-telephone me-1"></i>{{ $rep->celular ?? $rep->telefono }}
                                        </span>
                                    @endif
                                    @if($rep->email ?? null)
                                        <span style="font-size:.72rem;color:#059669;">
                                            <i class="bi bi-envelope me-1"></i>{{ $rep->email }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    {{-- Asistencia stats --}}
    <div class="col-12 col-md-6">
        <div class="info-card h-100">
            <div class="info-card-header" style="background:linear-gradient(135deg,#0891b2,#38bdf8);">
                <h5>Asistencia</h5>
                <div class="title">
                    <i class="bi bi-calendar-check me-1"></i>Estadísticas de asistencia
                </div>
            </div>
            <div class="info-card-body">
                @if($totalAsistencias > 0)
                    @php
                        $ausentes = $totalAsistencias - $presentes;
                        $pctColor = $pctAsistencia >= 85 ? '#059669' : ($pctAsistencia >= 70 ? '#d97706' : '#dc2626');
                        $pctBg    = $pctAsistencia >= 85 ? '#d1fae5' : ($pctAsistencia >= 70 ? '#fef3c7' : '#fee2e2');
                    @endphp
                    <div class="text-center mb-3">
                        <div style="font-size:2.2rem;font-weight:900;color:{{ $pctColor }};">
                            {{ $pctAsistencia }}%
                        </div>
                        <div style="font-size:.75rem;color:#6b7280;">porcentaje de asistencia</div>
                        <div class="mt-2" style="background:#e5e7eb;border-radius:8px;height:10px;overflow:hidden;">
                            <div style="height:100%;width:{{ $pctAsistencia }}%;background:{{ $pctColor }};border-radius:8px;
                                        transition:width .6s ease;"></div>
                        </div>
                    </div>
                    <div class="row g-2 text-center">
                        <div class="col-4">
                            <div style="background:#eff6ff;border-radius:8px;padding:.6rem .3rem;">
                                <div style="font-size:1.1rem;font-weight:800;color:#1d4ed8;">{{ $totalAsistencias }}</div>
                                <div style="font-size:.68rem;color:#2563eb;font-weight:600;">TOTAL</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div style="background:#f0fdf4;border-radius:8px;padding:.6rem .3rem;">
                                <div style="font-size:1.1rem;font-weight:800;color:#059669;">{{ $presentes }}</div>
                                <div style="font-size:.68rem;color:#059669;font-weight:600;">PRESENTES</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div style="background:#fff1f2;border-radius:8px;padding:.6rem .3rem;">
                                <div style="font-size:1.1rem;font-weight:800;color:#dc2626;">{{ $ausentes }}</div>
                                <div style="font-size:.68rem;color:#dc2626;font-weight:600;">AUSENTES</div>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="text-center py-4">
                        <i class="bi bi-calendar-x" style="font-size:2rem;color:#d1d5db;display:block;margin-bottom:.5rem;"></i>
                        <div style="font-size:.82rem;color:#9ca3af;">Sin registros de asistencia aún.</div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Calificaciones summary --}}
    @if($matricula->calificaciones && $matricula->calificaciones->isNotEmpty())
        <div class="col-12">
            <div class="info-card">
                <div class="info-card-header" style="background:linear-gradient(135deg,#059669,#10b981);">
                    <h5>Resumen de Calificaciones</h5>
                    <div class="title">
                        <i class="bi bi-journal-check me-1"></i>
                        {{ $matricula->calificaciones->count() }} calificación{{ $matricula->calificaciones->count() !== 1 ? 'es' : '' }} registrada{{ $matricula->calificaciones->count() !== 1 ? 's' : '' }}
                    </div>
                </div>
                <div class="info-card-body" style="padding:0;">
                    <div class="table-responsive">
                        <table class="table table-sm mb-0" style="font-size:.82rem;">
                            <thead>
                                <tr>
                                    <th class="th-sub">Asignatura / Indicador</th>
                                    <th class="th-sub" style="text-align:right;">Calificación</th>
                                    <th class="th-sub">Período</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($matricula->calificaciones->take(10) as $cal)
                                    <tr>
                                        <td style="padding:.5rem .75rem;">
                                            {{ $cal->asignacion->asignatura->nombre ?? $cal->asignatura->nombre ?? '—' }}
                                        </td>
                                        <td style="padding:.5rem .75rem;text-align:right;font-weight:700;color:var(--primary);">
                                            {{ $cal->valor ?? $cal->calificacion ?? '—' }}
                                        </td>
                                        <td class="text-muted" style="padding:.5rem .75rem;">
                                            {{ $cal->periodo->nombre ?? '—' }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        @if($matricula->calificaciones->count() > 10)
                            <div class="px-3 py-2 text-muted" style="font-size:.78rem;border-top:1px solid #f3f4f6;">
                                … y {{ $matricula->calificaciones->count() - 10 }} más
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif

</div>

{{-- Modal: Editar Matrícula --}}
<div class="modal fade" id="editarMatriculaModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title fw-bold" style="color:var(--primary);">
                    <i class="bi bi-pencil me-2"></i>Editar Matrícula
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('admin.matriculas.update', $matricula) }}" method="POST">
                @csrf @method('PATCH')
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label" style="font-size:.8rem;font-weight:600;">Fecha de Matrícula *</label>
                        <input type="date" name="fecha_matricula" class="form-control"
                               style="border-radius:8px;font-size:.875rem;"
                               value="{{ $matricula->fecha_matricula?->format('Y-m-d') }}" required>
                    </div>
                    <div>
                        <label class="form-label" style="font-size:.8rem;font-weight:600;">Observaciones</label>
                        <textarea name="observaciones" class="form-control" rows="3"
                                  style="border-radius:8px;font-size:.875rem;"
                                  placeholder="Notas u observaciones sobre esta matrícula...">{{ $matricula->observaciones }}</textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm" data-bs-dismiss="modal"
                            style="background:#f3f4f6;color:#374151;border:1px solid #e5e7eb;border-radius:8px;">
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-sm fw-semibold"
                            style="background:var(--primary);color:#fff;border-radius:8px;">
                        <i class="bi bi-check-lg me-1"></i>Guardar Cambios
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modal: Cambiar Estado --}}
<div class="modal fade" id="cambiarEstadoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title fw-bold" style="color:var(--primary);">
                    <i class="bi bi-tag me-2"></i>Cambiar Estado de Matrícula
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('admin.matriculas.estado', $matricula) }}" method="POST">
                @csrf @method('PATCH')
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label" style="font-size:.8rem;font-weight:600;">Nuevo Estado *</label>
                        <select name="estado" class="form-select" style="border-radius:8px;font-size:.875rem;" required>
                            <option value="activa"      {{ ($matricula->estado ?? 'activa') === 'activa'      ? 'selected' : '' }}>Activa</option>
                            <option value="retirada"    {{ ($matricula->estado ?? '') === 'retirada'    ? 'selected' : '' }}>Retirada</option>
                            <option value="transferida" {{ ($matricula->estado ?? '') === 'transferida' ? 'selected' : '' }}>Transferida</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label" style="font-size:.8rem;font-weight:600;">Motivo</label>
                        <textarea name="motivo" class="form-control" rows="2"
                                  style="border-radius:8px;font-size:.875rem;"
                                  placeholder="Motivo del cambio de estado..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm" data-bs-dismiss="modal"
                            style="background:#f3f4f6;color:#374151;border:1px solid #e5e7eb;border-radius:8px;">
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-sm fw-semibold"
                            style="background:var(--primary);color:#fff;border-radius:8px;">
                        <i class="bi bi-check-lg me-1"></i>Confirmar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Cambiar Grupo Modal --}}
@if(($matricula->estado ?? 'activa') === 'activa')
<div class="modal fade" id="cambiarGrupoModal" tabindex="-1" aria-labelledby="cambiarGrupoModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title fw-bold" id="cambiarGrupoModalLabel" style="color:var(--primary);">
                    <i class="bi bi-arrow-left-right me-2"></i>Cambiar Grupo
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('admin.matriculas.cambiarGrupo', $matricula) }}" method="POST">
                @csrf
                @method('PATCH')
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label" style="font-size:.8rem;font-weight:600;">Nuevo Grupo</label>
                        <select name="grupo_id" class="form-select" style="border-radius:8px;font-size:.875rem;" required>
                            <option value="">— Seleccionar grupo —</option>
                            @foreach(\App\Models\Grupo::with(['grado','seccion'])
                                ->where('school_year_id', $matricula->grupo->school_year_id ?? null)
                                ->activos()->orderBy('grado_id')->orderBy('seccion_id')->get() as $g)
                                @php
                                    $n2 = [1=>'1ro',2=>'2do',3=>'3ro',4=>'4to',5=>'5to',6=>'6to'];
                                    $p2 = $n2[$g->grado->nivel ?? 0] ?? ($g->grado->nivel.'mo');
                                @endphp
                                <option value="{{ $g->id }}" {{ $g->id === $matricula->grupo_id ? 'selected' : '' }}>
                                    {{ $p2 . ' ' . ($g->seccion->nombre ?? '') }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="form-label" style="font-size:.8rem;font-weight:600;">Motivo del cambio (opcional)</label>
                        <textarea name="observaciones" class="form-control" rows="2" style="border-radius:8px;font-size:.875rem;"
                                  placeholder="Ej: Traslado por solicitud de representante...">{{ $matricula->observaciones }}</textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm"
                            style="background:#f3f4f6;color:#374151;border:1px solid #e5e7eb;border-radius:8px;"
                            data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-sm fw-semibold"
                            style="background:var(--primary);color:#fff;border-radius:8px;">
                        <i class="bi bi-check-lg me-1"></i>Confirmar Cambio
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

@endsection
