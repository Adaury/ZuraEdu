@extends('layouts.admin')
@section('page-title', $estudiante->apellidos . ', ' . $estudiante->nombres)

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
        background: linear-gradient(135deg, #2a4f96 0%, var(--primary) 100%);
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
        background: linear-gradient(135deg, #2a4f96, var(--primary));
        color: #fff;
        font-size: 2rem;
        font-weight: 800;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 4px solid #fff;
        box-shadow: 0 4px 12px rgba(0,0,0,.12);
    }
    .profile-name {
        font-size: 1.2rem;
        font-weight: 800;
        color: #111827;
        margin: 0 0 .1rem;
    }
    .profile-sub { font-size: .83rem; color: #6b7280; }
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
        flex: 0 0 160px;
        color: #9ca3af;
        font-size: .77rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: .04em;
        padding-top: 2px;
    }
    .info-value { flex: 1; color: #111827; font-weight: 500; }
    .status-badge {
        font-size: .72rem;
        font-weight: 600;
        padding: .3rem .7rem;
        border-radius: 20px;
        letter-spacing: .03em;
    }
    .badge-activo      { background: #d1fae5; color: #065f46; }
    .badge-inactivo    { background: #fee2e2; color: #991b1b; }
    .badge-egresado    { background: #dbeafe; color: #1e40af; }
    .badge-transferido { background: #fef3c7; color: #92400e; }
    .matricula-row {
        padding: .75rem 0;
        border-bottom: 1px solid #f3f4f6;
        font-size: .875rem;
    }
    .matricula-row:last-child { border-bottom: none; }
    .matricula-year {
        font-size: .72rem;
        font-weight: 600;
        color: #9ca3af;
        text-transform: uppercase;
        letter-spacing: .05em;
    }

    [data-theme="dark"] .profile-card { background: #1e293b; border-color: #334155; }
    [data-theme="dark"] .profile-avatar { border-color: #1e293b; }
    [data-theme="dark"] .badge-activo { background: #052e16; color: #4ade80; }
    [data-theme="dark"] .badge-inactivo { background: #1c0000; color: #f87171; }
    [data-theme="dark"] .badge-egresado { background: #0c1f3f; color: #93c5fd; }
    [data-theme="dark"] .badge-transferido { background: #1c1000; color: #fcd34d; }
    [data-theme="dark"] .matricula-row { border-bottom-color: #334155; }
</style>
@endpush

@section('content')

{{-- Actions bar --}}
<div class="d-flex align-items-center gap-3 mb-4">
    <a href="{{ route('admin.estudiantes.index') }}"
       class="btn btn-sm btn-outline-secondary" style="border-radius:8px;">
        <i class="bi bi-arrow-left me-1"></i>Volver
    </a>
    <div class="ms-auto d-flex gap-2 flex-wrap">
        {{-- Enlace portal representante --}}
        @php
            $portalUrl = \App\Http\Controllers\PortalRepresentanteController::generarEnlace($estudiante);
        @endphp
        <a href="{{ route('admin.perfiles.estudiante', $estudiante) }}"
           class="btn btn-sm btn-outline-primary" style="border-radius:8px;">
            <i class="bi bi-person-circle me-1"></i>Perfil Completo
        </a>
        <a href="{{ route('admin.observaciones.index') }}?q={{ urlencode($estudiante->nombre_completo) }}"
           class="btn btn-sm btn-outline-warning" style="border-radius:8px;">
            <i class="bi bi-chat-square-text me-1"></i>Observaciones
        </a>
        <button type="button"
                class="btn btn-sm btn-outline-success"
                style="border-radius:8px;"
                onclick="copiarEnlacePortal(this)"
                data-url="{{ $portalUrl }}"
                title="Copiar enlace del portal para el representante">
            <i class="bi bi-share me-1"></i>Portal Representante
        </button>
        <a href="{{ route('admin.estudiantes.ficha-pdf', $estudiante) }}" target="_blank"
           class="btn btn-sm" style="background:#1e3a6e;color:#fff;border-radius:8px;">
            <i class="bi bi-person-badge me-1"></i>Ficha PDF
        </a>
        <a href="{{ route('admin.estudiantes.edit', $estudiante) }}"
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

    {{-- Left column --}}
    <div class="col-lg-4">

        {{-- Profile card --}}
        <div class="profile-card mb-4">
            <div class="profile-banner"></div>
            <div class="profile-avatar-wrap">
                @if($estudiante->foto)
                    <img src="{{ asset('storage/'.$estudiante->foto) }}"
                         alt="{{ $estudiante->nombres }}"
                         class="profile-avatar">
                @else
                    <div class="profile-avatar-initials">
                        {{ substr($estudiante->nombres,0,1) }}{{ substr($estudiante->apellidos,0,1) }}
                    </div>
                @endif
                @php
                    $badgeClass = match($estudiante->estado) {
                        'activo'      => 'badge-activo',
                        'inactivo'    => 'badge-inactivo',
                        'egresado'    => 'badge-egresado',
                        'transferido' => 'badge-transferido',
                        default       => 'badge-inactivo',
                    };
                    $label = match($estudiante->estado) {
                        'activo'      => 'Activo',
                        'inactivo'    => 'Inactivo',
                        'egresado'    => 'Egresado',
                        'transferido' => 'Transferido',
                        default       => ucfirst($estudiante->estado),
                    };
                @endphp
                <span class="status-badge {{ $badgeClass }}">{{ $label }}</span>
            </div>
            <div class="px-4 pb-4">
                <h2 class="profile-name">{{ $estudiante->apellidos }}, {{ $estudiante->nombres }}</h2>
                <p class="profile-sub">
                    <span class="badge me-2"
                          style="background:#e0e7ff;color:#3730a3;font-size:.75rem;font-family:monospace;border-radius:6px;">
                        ID #{{ str_pad($estudiante->id, 4, '0', STR_PAD_LEFT) }}
                    </span>
                    <i class="bi bi-hash me-1"></i>
                    <span style="font-family:monospace;">{{ $estudiante->numero_matricula }}</span>
                </p>
                @if($estudiante->email)
                    <p class="profile-sub">
                        <i class="bi bi-envelope me-1"></i>{{ $estudiante->email }}
                    </p>
                @endif
                @if($estudiante->telefono)
                    <p class="profile-sub mb-0">
                        <i class="bi bi-telephone me-1"></i>{{ $estudiante->telefono }}
                    </p>
                @endif
            </div>
        </div>

        {{-- Tutor --}}
        @if($estudiante->tutor_nombre)
        <div class="info-card">
            <div class="info-card-title"><i class="bi bi-person-hearts"></i>Tutor / Encargado</div>
            <div class="info-row">
                <span class="info-label">Nombre</span>
                <span class="info-value">{{ $estudiante->tutor_nombre }}</span>
            </div>
            @if($estudiante->tutor_parentesco)
            <div class="info-row">
                <span class="info-label">Parentesco</span>
                <span class="info-value">{{ $estudiante->tutor_parentesco }}</span>
            </div>
            @endif
            @if($estudiante->tutor_telefono)
            <div class="info-row">
                <span class="info-label">Teléfono</span>
                <span class="info-value">{{ $estudiante->tutor_telefono }}</span>
            </div>
            @endif
            @if($estudiante->tutor_trabajo)
            <div class="info-row mb-0">
                <span class="info-label">Trabajo</span>
                <span class="info-value">{{ $estudiante->tutor_trabajo }}</span>
            </div>
            @endif
        </div>
        @endif

        {{-- Historial de matrículas --}}
        <div class="info-card">
            <div class="info-card-title d-flex justify-content-between align-items-center">
                <span><i class="bi bi-card-list me-1"></i>Historial de Matrículas ({{ $estudiante->matriculas->count() }})</span>
                <a href="{{ route('admin.grupos.index') }}"
                   class="btn btn-sm" style="font-size:.72rem;padding:.2rem .6rem;border-radius:7px;background:var(--primary);color:#fff;">
                    <i class="bi bi-plus-lg me-1"></i>Matricular
                </a>
            </div>
            @forelse($estudiante->matriculas->sortByDesc('fecha_matricula') as $matricula)
                <div class="matricula-row">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="fw-600" style="color:#111827;font-size:.85rem;">
                                @if($matricula->grupo)
                                    <a href="{{ route('admin.grupos.show', $matricula->grupo) }}"
                                       class="text-decoration-none" style="color:inherit;">
                                        {{ $matricula->grupo->nombre_completo }}
                                        <i class="bi bi-box-arrow-up-right ms-1" style="font-size:.65rem;color:#9ca3af;"></i>
                                    </a>
                                @else
                                    Grupo no asignado
                                @endif
                            </div>
                            <div class="matricula-year">
                                {{ $matricula->schoolYear->nombre ?? $matricula->schoolYear->name ?? '—' }}
                                @if($matricula->fecha_matricula)
                                    · {{ $matricula->fecha_matricula->format('d/m/Y') }}
                                @endif
                            </div>
                        </div>
                        <span class="badge {{ $matricula->estado === 'activa' ? 'text-bg-success' : 'text-bg-secondary' }}"
                              style="font-size:.7rem;">
                            {{ ucfirst($matricula->estado) }}
                        </span>
                    </div>
                </div>
            @empty
                <div class="text-center py-2">
                    <p class="text-muted mb-2" style="font-size:.84rem;">Sin matrículas registradas.</p>
                    <a href="{{ route('admin.grupos.index') }}"
                       class="btn btn-sm btn-primary" style="border-radius:8px;font-size:.78rem;">
                        <i class="bi bi-person-check me-1"></i>Ir a Grupos para matricular
                    </a>
                </div>
            @endforelse
        </div>

    </div>

    {{-- Right column --}}
    <div class="col-lg-8">

        {{-- Identification --}}
        <div class="info-card">
            <div class="info-card-title"><i class="bi bi-card-text"></i>Identificación</div>
            <div class="row">
                <div class="col-md-6">
                    <div class="info-row">
                        <span class="info-label">Nº Matrícula</span>
                        <span class="info-value" style="font-family:monospace;">{{ $estudiante->numero_matricula }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Cédula</span>
                        <span class="info-value" style="font-family:monospace;">{{ $estudiante->cedula ?? '—' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Nombres</span>
                        <span class="info-value">{{ $estudiante->nombres }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Apellidos</span>
                        <span class="info-value">{{ $estudiante->apellidos }}</span>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="info-row">
                        <span class="info-label">Nacimiento</span>
                        <span class="info-value">
                            {{ $estudiante->fecha_nacimiento?->format('d/m/Y') ?? '—' }}
                            @if($estudiante->fecha_nacimiento)
                                <span class="text-muted" style="font-size:.78rem;">({{ $estudiante->fecha_nacimiento->age }} años)</span>
                            @endif
                        </span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Sexo</span>
                        <span class="info-value">
                            @if($estudiante->sexo === 'M') Masculino
                            @elseif($estudiante->sexo === 'F') Femenino
                            @else — @endif
                        </span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Nacionalidad</span>
                        <span class="info-value">{{ $estudiante->nacionalidad ?? '—' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Lugar de Nac.</span>
                        <span class="info-value">{{ $estudiante->lugar_nacimiento ?? '—' }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Contact --}}
        <div class="info-card">
            <div class="info-card-title"><i class="bi bi-geo-alt"></i>Contacto y Dirección</div>
            <div class="row">
                <div class="col-md-6">
                    <div class="info-row">
                        <span class="info-label">Teléfono</span>
                        <span class="info-value">{{ $estudiante->telefono ?? '—' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Correo</span>
                        <span class="info-value">
                            @if($estudiante->email)
                                <a href="mailto:{{ $estudiante->email }}" style="color:var(--primary);">{{ $estudiante->email }}</a>
                            @else — @endif
                        </span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Dirección</span>
                        <span class="info-value">{{ $estudiante->direccion ?? '—' }}</span>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="info-row">
                        <span class="info-label">Sector</span>
                        <span class="info-value">{{ $estudiante->sector ?? '—' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Municipio</span>
                        <span class="info-value">{{ $estudiante->municipio ?? '—' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Provincia</span>
                        <span class="info-value">{{ $estudiante->provincia ?? '—' }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Medical notes --}}
        @if($estudiante->notas_medicas)
        <div class="info-card">
            <div class="info-card-title"><i class="bi bi-clipboard2-pulse"></i>Notas Médicas / Observaciones</div>
            <p class="mb-0" style="font-size:.875rem;color:#374151;white-space:pre-line;">{{ $estudiante->notas_medicas }}</p>
        </div>
        @endif

    </div>
</div>

{{-- ── Tabla de Notas ─────────────────────────────────────────────────── --}}
@if($matriculaActual && $asignaciones->count() > 0)
<div class="info-card mt-2">
    <div class="info-card-title">
        <i class="bi bi-journal-bookmark-fill"></i>
        Calificaciones — {{ $matriculaActual->grupo->grado->nombre ?? '' }} {{ $matriculaActual->grupo->seccion->nombre ?? '' }}
        <span class="ms-auto fw-400 text-muted" style="font-size:.72rem;letter-spacing:0;">
            {{ $schoolYear->nombre ?? $schoolYear->name ?? '' }}
        </span>
    </div>

    <div class="table-responsive">
        <table class="table table-sm table-bordered align-middle mb-0" style="font-size:.82rem;">
            <thead>
                <tr style="background:#f8fafc;">
                    <th style="min-width:160px;">Asignatura</th>
                    <th style="min-width:90px;color:#6b7280;">Docente</th>
                    @foreach($periodos as $periodo)
                        <th class="text-center" style="min-width:64px;white-space:nowrap;">
                            {{ $periodo->nombre }}
                        </th>
                    @endforeach
                    <th class="text-center" style="min-width:64px;background:#e0e7ff;color:#3730a3;">
                        Promedio
                    </th>
                </tr>
            </thead>
            <tbody>
                @foreach($asignaciones as $asig)
                @php
                    $notasPeriodo = [];
                    $suma = 0; $cnt = 0;
                    foreach ($periodos as $p) {
                        $cal = $calificaciones[$asig->id][$p->id] ?? null;
                        $nf  = $cal?->nota_final;
                        $notasPeriodo[$p->id] = $nf;
                        if ($nf !== null) { $suma += $nf; $cnt++; }
                    }
                    $promedio = $cnt > 0 ? round($suma / $cnt, 1) : null;
                    $colorProm = $promedio === null ? '' : ($promedio >= 70 ? '#065f46' : '#991b1b');
                    $bgProm    = $promedio === null ? '' : ($promedio >= 70 ? '#d1fae5' : '#fee2e2');
                @endphp
                <tr>
                    <td class="fw-500">
                        {{ $asig->asignatura->nombre }}
                    </td>
                    <td style="color:#6b7280;font-size:.77rem;">
                        {{ $asig->docente ? $asig->docente->apellidos.', '.$asig->docente->nombres : '—' }}
                    </td>
                    @foreach($periodos as $periodo)
                    @php
                        $nf = $notasPeriodo[$periodo->id] ?? null;
                        $cal = $calificaciones[$asig->id][$periodo->id] ?? null;
                        $letra = $cal?->letra ?? '—';
                        $color = $nf === null ? '#9ca3af' : ($nf >= 70 ? '#065f46' : '#991b1b');
                        $bg    = $nf === null ? '' : ($nf >= 70 ? '#d1fae5' : '#fee2e2');
                    @endphp
                    <td class="text-center" style="background:{{ $bg }};color:{{ $color }};font-weight:600;">
                        @if($nf !== null)
                            {{ number_format($nf, 1) }}
                            <span style="font-size:.68rem;font-weight:400;">({{ $letra }})</span>
                        @else
                            <span style="font-size:.78rem;color:#d1d5db;">—</span>
                        @endif
                    </td>
                    @endforeach
                    <td class="text-center fw-700" style="background:{{ $bgProm }};color:{{ $colorProm }};">
                        {{ $promedio !== null ? number_format($promedio, 1) : '—' }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="mt-2 d-flex gap-3" style="font-size:.72rem;color:#9ca3af;">
        <span><span style="background:#d1fae5;color:#065f46;padding:1px 6px;border-radius:4px;">verde</span> = aprobado (≥70)</span>
        <span><span style="background:#fee2e2;color:#991b1b;padding:1px 6px;border-radius:4px;">rojo</span> = reprobado (&lt;70)</span>
        <span>— = sin calificación</span>
    </div>
</div>
@elseif($matriculaActual && $asignaciones->count() === 0)
<div class="info-card mt-2">
    <div class="info-card-title"><i class="bi bi-journal-bookmark-fill"></i>Calificaciones</div>
    <p class="text-muted mb-0" style="font-size:.84rem;">Este grupo aún no tiene asignaturas asignadas.</p>
</div>
@endif

{{-- Delete Modal --}}
<div class="modal fade" id="modalDelete" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:420px;">
        <div class="modal-content border-0 shadow" style="border-radius:16px;">
            <div class="modal-body p-4 text-center">
                <div class="mb-3" style="font-size:2.5rem;color:var(--secondary);">
                    <i class="bi bi-exclamation-triangle"></i>
                </div>
                <h5 class="fw-700 mb-2" style="color:#111827;">¿Eliminar estudiante?</h5>
                <p class="text-muted mb-4" style="font-size:.88rem;">
                    Se eliminará permanentemente el registro de
                    <strong>{{ $estudiante->apellidos }}, {{ $estudiante->nombres }}</strong>
                    (Matr. {{ $estudiante->numero_matricula }}).
                    Esta acción no se puede deshacer.
                </p>
                <div class="d-flex gap-2 justify-content-center">
                    <button class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Cancelar</button>
                    <form method="POST" action="{{ route('admin.estudiantes.destroy', $estudiante) }}">
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

@push('scripts')
<script>
function copiarEnlacePortal(btn) {
    const url = btn.dataset.url;
    navigator.clipboard.writeText(url).then(() => {
        const original = btn.innerHTML;
        btn.innerHTML = '<i class="bi bi-check-lg me-1"></i>¡Enlace copiado!';
        btn.classList.remove('btn-outline-success');
        btn.classList.add('btn-success');
        setTimeout(() => {
            btn.innerHTML = original;
            btn.classList.remove('btn-success');
            btn.classList.add('btn-outline-success');
        }, 2500);
        if (window.SGEToast) SGEToast.success('Enlace del portal copiado al portapapeles. Válido por 30 días.');
    }).catch(() => {
        // Fallback para navegadores sin clipboard API
        const el = document.createElement('textarea');
        el.value = url;
        document.body.appendChild(el);
        el.select();
        document.execCommand('copy');
        document.body.removeChild(el);
        if (window.SGEToast) SGEToast.success('Enlace copiado al portapapeles.');
    });
}
</script>
@endpush

@endsection
