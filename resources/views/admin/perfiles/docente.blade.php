@extends('layouts.admin')

@section('page-title', 'Perfil Docente')

@push('styles')
<style>
    .perfil-avatar {
        width: 90px; height: 90px; border-radius: 50%;
        background: linear-gradient(135deg, #1e3a6e, #2a4f96);
        color: #fff; font-weight: 800; font-size: 2rem;
        display: flex; align-items: center; justify-content: center;
        margin: 0 auto;
        box-shadow: 0 4px 16px rgba(30,58,110,.25);
    }
    .asig-row { border-bottom: 1px solid #f0f4f8; padding: .6rem 0; }
    .asig-row:last-child { border-bottom: none; }
    .info-label { font-size: .75rem; font-weight: 600; color: #9ca3af; text-transform: uppercase; letter-spacing: .04em; }
    .info-value { font-size: .9rem; color: #1e293b; }

    [data-theme="dark"] .asig-row { border-bottom-color: #334155; }
    [data-theme="dark"] .info-value { color: #e2e8f0; }
</style>
@endpush

@section('content')
<div class="mb-3">
    <a href="{{ url()->previous() }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Volver
    </a>
</div>

<div class="row g-4">

    {{-- Columna izquierda: datos personales --}}
    <div class="col-lg-3">
        <div class="card border-0 shadow-sm text-center p-3 mb-3">
            @if($docente->foto)
            <img src="{{ $docente->foto_url }}" alt="Foto" class="rounded-circle mx-auto mb-2" style="width:90px;height:90px;object-fit:cover;">
            @else
            <div class="perfil-avatar mb-2">
                {{ strtoupper(substr($docente->nombres, 0, 1) . substr($docente->apellidos, 0, 1)) }}
            </div>
            @endif
            <h6 class="fw-bold mb-0" style="color:#1e293b;">{{ $docente->nombre_completo }}</h6>
            <div class="text-muted mb-2" style="font-size:.82rem;">{{ $docente->cargo ?? 'Docente' }}</div>
            <div class="d-flex justify-content-center gap-1 flex-wrap mb-3">
                <span class="badge text-bg-primary" style="font-size:.7rem;">
                    {{ ucfirst($docente->area ?? 'Sin área') }}
                </span>
                @if($docente->estado === 'activo')
                <span class="badge text-bg-success" style="font-size:.7rem;">Activo</span>
                @else
                <span class="badge text-bg-secondary" style="font-size:.7rem;">Inactivo</span>
                @endif
            </div>
            @if(Auth::user()->hasAnyRole(['Administrador', 'Director']))
            <a href="{{ route('admin.docentes.edit', $docente) }}" class="btn btn-sm btn-outline-primary w-100 mb-2">
                <i class="bi bi-pencil me-1"></i>Editar Docente
            </a>
            @if($docente->user_id)
            <a href="{{ route('admin.usuarios.edit', $docente->user_id) }}" class="btn btn-sm btn-outline-secondary w-100 mb-2">
                <i class="bi bi-person-lock me-1"></i>Gestionar Usuario
            </a>
            <a href="{{ route('admin.perfiles.docente.informe-pdf', $docente) }}" target="_blank"
               class="btn btn-sm btn-outline-danger w-100 mb-2">
                <i class="bi bi-file-earmark-pdf me-1"></i>Informe PDF
            </a>
            <a href="{{ route('admin.perfiles.docente.informe-excel', $docente) }}"
               class="btn btn-sm btn-outline-success w-100 mb-2">
                <i class="bi bi-file-earmark-excel me-1"></i>Informe Excel
            </a>
            <a href="{{ route('portal.docente.dashboard') }}" target="_blank"
               class="btn btn-sm w-100" style="background:#5b21b6;color:#fff;border-radius:8px;">
                <i class="bi bi-box-arrow-up-right me-1"></i>Ver Portal Docente
            </a>
            @endif
            @endif
        </div>

        {{-- Info de contacto --}}
        <div class="card border-0 shadow-sm p-3">
            <h6 class="fw-bold mb-3" style="font-size:.85rem;">Información Personal</h6>
            @if($docente->email)
            <div class="mb-2">
                <div class="info-label">Correo</div>
                <div class="info-value">{{ $docente->email }}</div>
            </div>
            @endif
            @if($docente->telefono)
            <div class="mb-2">
                <div class="info-label">Teléfono</div>
                <div class="info-value">{{ $docente->telefono }}</div>
            </div>
            @endif
            @if($docente->cedula)
            <div class="mb-2">
                <div class="info-label">Cédula</div>
                <div class="info-value">{{ $docente->cedula }}</div>
            </div>
            @endif
            @if($docente->titulo_academico)
            <div class="mb-2">
                <div class="info-label">Título Académico</div>
                <div class="info-value">{{ $docente->titulo_academico }}</div>
            </div>
            @endif
            @if($docente->especialidad)
            <div class="mb-2">
                <div class="info-label">Especialidad</div>
                <div class="info-value">{{ $docente->especialidad }}</div>
            </div>
            @endif

            {{-- Especialidades técnicas --}}
            @if($docente->especialidades->isNotEmpty())
            <div class="mt-3">
                <div class="info-label mb-2">Especialidades Técnicas</div>
                @foreach($docente->especialidades as $esp)
                <div class="d-flex align-items-center gap-2 mb-1">
                    <span style="width:10px;height:10px;border-radius:50%;background:{{ $esp->color }};flex-shrink:0;"></span>
                    <span style="font-size:.82rem;">{{ $esp->nombre }}</span>
                    @if($esp->pivot->es_coordinador)
                    <span class="badge text-bg-warning" style="font-size:.58rem;">Coord.</span>
                    @endif
                </div>
                @endforeach
            </div>
            @endif
        </div>
    </div>

    {{-- Columna central: asignaciones y rendimiento --}}
    <div class="col-lg-9">
        <ul class="nav nav-tabs mb-3" id="perfilTabs">
            <li class="nav-item">
                <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-asig">
                    <i class="bi bi-book me-1"></i>Asignaturas
                    <span class="badge bg-secondary ms-1">{{ $docente->asignaciones->count() }}</span>
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-planif">
                    <i class="bi bi-journal-text me-1"></i>Planificaciones
                    @if($planificaciones->isNotEmpty() || $planesClase->isNotEmpty())
                    <span class="badge bg-primary ms-1">{{ $planificaciones->count() + $planesClase->count() }}</span>
                    @endif
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-obs-doc">
                    <i class="bi bi-chat-square-text me-1"></i>Observaciones
                    @if($observacionesEmitidas->isNotEmpty())
                    <span class="badge bg-warning text-dark ms-1">{{ $observacionesEmitidas->count() }}</span>
                    @endif
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-rendimiento">
                    <i class="bi bi-bar-chart me-1"></i>Rendimiento
                </button>
            </li>
        </ul>

        <div class="tab-content">
            {{-- Tab Asignaturas --}}
            <div class="tab-pane fade show active" id="tab-asig">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-0">
                        @if($docente->asignaciones->isEmpty())
                        <div class="empty-state-enhanced py-4">
                            <div class="empty-illustration"><i class="bi bi-book-x"></i></div>
                            <div class="empty-title">Sin asignaciones este año</div>
                        </div>
                        @else
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead style="font-size:.76rem;background:#f8fafc;color:#6b7280;text-transform:uppercase;">
                                    <tr>
                                        <th class="px-3 py-2">Asignatura</th>
                                        <th class="px-3 py-2">Grupo</th>
                                        <th class="px-3 py-2">Grado</th>
                                        <th class="px-3 py-2">Área</th>
                                        <th class="px-3 py-2">Tipo Eval.</th>
                                        <th class="px-3 py-2 text-center">Estudiantes</th>
                                    </tr>
                                </thead>
                                <tbody style="font-size:.84rem;">
                                    @foreach($docente->asignaciones as $asig)
                                    <tr>
                                        <td class="px-3 py-2 fw-semibold">{{ $asig->asignatura->nombre ?? '—' }}</td>
                                        <td class="px-3 py-2">
                                            {{ optional($asig->grupo)->nombre_corto ?? '—' }}
                                        </td>
                                        <td class="px-3 py-2">
                                            {{ optional(optional($asig->grupo)->grado)->nombre ?? '—' }}
                                        </td>
                                        <td class="px-3 py-2">
                                            <span class="badge {{ $asig->area === 'academica' ? 'text-bg-success' : 'text-bg-info' }}" style="font-size:.65rem;">
                                                {{ ucfirst($asig->area) }}
                                            </span>
                                        </td>
                                        <td class="px-3 py-2">
                                            <span class="badge text-bg-light" style="font-size:.65rem;">{{ ucfirst($asig->tipo_evaluacion) }}</span>
                                        </td>
                                        <td class="px-3 py-2 text-center">
                                            {{ optional($asig->grupo)->matriculas->where('estado','activa')->count() ?? '—' }}
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Tab Planificaciones --}}
            <div class="tab-pane fade" id="tab-planif">
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-header py-2 d-flex align-items-center gap-2" style="background:#f0f4ff;">
                        <i class="bi bi-journal-text text-primary"></i>
                        <strong style="font-size:.85rem;">Planificaciones Técnicas ({{ $planificaciones->count() }})</strong>
                    </div>
                    <div class="card-body p-0">
                        @if($planificaciones->isEmpty())
                        <div class="text-center py-3 text-muted small">Sin planificaciones técnicas registradas.</div>
                        @else
                        @foreach($planificaciones as $plan)
                        <div class="d-flex align-items-center gap-3 px-3 py-2 border-bottom" style="font-size:.83rem;">
                            <span class="badge {{ $plan->tipo === 'ra' ? 'text-bg-primary' : 'text-bg-success' }}" style="font-size:.7rem;">
                                {{ $plan->tipo === 'ra' ? 'RA' : 'Activ.' }}
                            </span>
                            <div class="flex-grow-1">
                                <span class="fw-semibold">{{ $plan->modulo_nombre ?? $plan->asignacion?->asignatura?->nombre ?? '—' }}</span>
                                <span class="text-muted ms-2" style="font-size:.75rem;">{{ $plan->asignacion?->grupo?->nombre_completo }}</span>
                            </div>
                            <span class="badge {{ $plan->publicado ? 'text-bg-success' : 'text-bg-secondary' }}" style="font-size:.68rem;">
                                {{ $plan->publicado ? 'Publicado' : 'Borrador' }}
                            </span>
                        </div>
                        @endforeach
                        @endif
                    </div>
                </div>
                <div class="card border-0 shadow-sm">
                    <div class="card-header py-2 d-flex align-items-center gap-2" style="background:#f0fff4;">
                        <i class="bi bi-journal-text text-success"></i>
                        <strong style="font-size:.85rem;">Planes de Clase ({{ $planesClase->count() }})</strong>
                    </div>
                    <div class="card-body p-0">
                        @if($planesClase->isEmpty())
                        <div class="text-center py-3 text-muted small">Sin planes de clase registrados.</div>
                        @else
                        @foreach($planesClase as $plan)
                        <div class="d-flex align-items-center gap-3 px-3 py-2 border-bottom" style="font-size:.83rem;">
                            <span class="badge text-bg-light text-capitalize" style="font-size:.7rem;">{{ $plan->tipo_plan }}</span>
                            <div class="flex-grow-1">
                                <span class="fw-semibold">{{ $plan->titulo }}</span>
                            </div>
                            <span class="badge {{ $plan->publicado ? 'text-bg-success' : 'text-bg-secondary' }}" style="font-size:.68rem;">
                                {{ $plan->publicado ? 'Publicado' : 'Borrador' }}
                            </span>
                        </div>
                        @endforeach
                        @endif
                    </div>
                </div>
            </div>

            {{-- Tab Observaciones emitidas --}}
            <div class="tab-pane fade" id="tab-obs-doc">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-0">
                        @if($observacionesEmitidas->isEmpty())
                        <div class="text-center py-4 text-muted small">
                            <i class="bi bi-chat-square" style="font-size:2rem;display:block;margin-bottom:.5rem;"></i>
                            Este docente no ha registrado observaciones este año.
                        </div>
                        @else
                        @foreach($observacionesEmitidas as $obs)
                        @php $ti = $obs->tipo_info; @endphp
                        <div class="d-flex gap-3 p-3 border-bottom align-items-start" style="font-size:.83rem;">
                            <div style="width:32px;height:32px;border-radius:8px;background:{{ $ti['color'] }}18;color:{{ $ti['color'] }};display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                <i class="bi {{ $ti['icon'] }}"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                                    <span class="badge" style="background:{{ $ti['color'] }}18;color:{{ $ti['color'] }};font-size:.7rem;">{{ $ti['label'] }}</span>
                                    <span class="fw-semibold">{{ $obs->estudiante?->nombre_completo ?? '—' }}</span>
                                    <span class="text-muted ms-auto" style="font-size:.72rem;">{{ $obs->created_at->format('d/m/Y') }}</span>
                                </div>
                                <div class="text-muted" style="font-size:.8rem;">{{ Str::limit($obs->texto, 150) }}</div>
                            </div>
                        </div>
                        @endforeach
                        @endif
                    </div>
                </div>
            </div>

            {{-- Tab Rendimiento --}}
            <div class="tab-pane fade" id="tab-rendimiento">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-0">
                        @if($docente->asignaciones->isEmpty())
                        <div class="text-center py-4 text-muted">
                            <i class="bi bi-bar-chart" style="font-size:2rem;display:block;margin-bottom:.5rem;"></i>
                            Sin asignaciones para mostrar rendimiento.
                        </div>
                        @else
                        <div class="table-responsive">
                            <table class="table table-hover mb-0" style="font-size:.84rem;">
                                <thead class="table-light">
                                    <tr>
                                        <th class="px-3 py-2">Asignatura / Grupo</th>
                                        <th class="px-3 py-2 text-center">Estudiantes</th>
                                        <th class="px-3 py-2 text-center">Con nota</th>
                                        <th class="px-3 py-2 text-center">Promedio</th>
                                        <th class="px-3 py-2 text-center">Aprobados</th>
                                        <th class="px-3 py-2 text-center">Reprobados</th>
                                        <th class="px-3 py-2" style="min-width:120px;">Progreso</th>
                                    </tr>
                                </thead>
                                <tbody>
                                @foreach($docente->asignaciones as $asig)
                                @php
                                    $r = $rendimiento[$asig->id] ?? ['total'=>0,'con_nota'=>0,'promedio'=>null,'aprobados'=>0,'reprobados'=>0];
                                    $prom = $r['promedio'];
                                    $promColor = $prom === null ? '#9ca3af' : ($prom >= 80 ? '#16a34a' : ($prom >= 70 ? '#d97706' : '#dc2626'));
                                    $pctApro = $r['total'] > 0 ? round($r['aprobados'] / $r['total'] * 100) : 0;
                                @endphp
                                <tr>
                                    <td class="px-3 py-2">
                                        <div class="fw-semibold">{{ $asig->asignatura->nombre ?? '—' }}</div>
                                        <div class="text-muted small">{{ optional($asig->grupo)->nombre_corto ?? '—' }}</div>
                                    </td>
                                    <td class="px-3 py-2 text-center">{{ $r['total'] }}</td>
                                    <td class="px-3 py-2 text-center text-muted">{{ $r['con_nota'] }}</td>
                                    <td class="px-3 py-2 text-center fw-bold" style="color:{{ $promColor }};font-size:.95rem;">
                                        {{ $prom !== null ? $prom : '—' }}
                                    </td>
                                    <td class="px-3 py-2 text-center text-success fw-semibold">{{ $r['aprobados'] }}</td>
                                    <td class="px-3 py-2 text-center text-danger fw-semibold">{{ $r['reprobados'] }}</td>
                                    <td class="px-3 py-2">
                                        @if($r['total'] > 0)
                                        <div class="d-flex align-items-center gap-1">
                                            <div class="progress flex-grow-1" style="height:8px;">
                                                <div class="progress-bar bg-success" style="width:{{ $pctApro }}%;"></div>
                                            </div>
                                            <span class="text-muted" style="font-size:.72rem;white-space:nowrap;">{{ $pctApro }}%</span>
                                        </div>
                                        @else
                                        <span class="text-muted small">—</span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
