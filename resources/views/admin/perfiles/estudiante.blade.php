@extends('layouts.admin')

@section('page-title', 'Perfil Estudiante')

@push('styles')
<style>
    .perfil-avatar {
        width: 90px; height: 90px; border-radius: 50%;
        background: linear-gradient(135deg, #0f766e, #14b8a6);
        color: #fff; font-weight: 800; font-size: 2rem;
        display: flex; align-items: center; justify-content: center;
        margin: 0 auto;
        box-shadow: 0 4px 16px rgba(15,118,110,.25);
    }
    .estado-badge-activo  { background:#dcfce7;color:#16a34a;border:1px solid #86efac; }
    .estado-badge-riesgo  { background:#fef3c7;color:#b45309;border:1px solid #fde68a; }
    .estado-badge-baja    { background:#fee2e2;color:#dc2626;border:1px solid #fca5a5; }
    .info-label { font-size:.75rem;font-weight:600;color:#9ca3af;text-transform:uppercase;letter-spacing:.04em; }
    .info-value { font-size:.88rem;color:#1e293b; }

    [data-theme="dark"] .estado-badge-activo { background: #052e16; color: #4ade80; border-color: #166534; }
    [data-theme="dark"] .estado-badge-riesgo { background: #1c1000; color: #fcd34d; border-color: #78350f; }
    [data-theme="dark"] .estado-badge-baja { background: #1c0000; color: #f87171; border-color: #7f1d1d; }
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

    {{-- Columna izquierda --}}
    <div class="col-lg-3">
        <div class="card border-0 shadow-sm text-center p-3 mb-3">
            <div class="perfil-avatar mb-2">
                {{ strtoupper(substr($estudiante->user->name ?? 'E', 0, 1) . substr($estudiante->user->apellidos ?? '', 0, 1)) }}
            </div>
            <h6 class="fw-bold mb-0" style="color:#1e293b;">
                {{ $estudiante->user->name ?? '' }} {{ $estudiante->user->apellidos ?? '' }}
            </h6>
            <div class="text-muted mb-2" style="font-size:.82rem;">Estudiante</div>

            {{-- Estado académico --}}
            @php
                $estadoClases = [
                    'activo' => 'estado-badge-activo',
                    'riesgo' => 'estado-badge-riesgo',
                    'baja'   => 'estado-badge-baja',
                ];
                $estadoLabels = [
                    'activo' => 'Activo',
                    'riesgo' => 'En Riesgo',
                    'baja'   => 'Baja Académica',
                ];
            @endphp
            <span class="badge d-inline-block px-3 py-2 rounded-pill mb-3 {{ $estadoClases[$estado] ?? '' }}"
                  style="font-size:.78rem;font-weight:700;">
                {{ $estadoLabels[$estado] ?? $estado }}
            </span>

            {{-- Promedio grande --}}
            @if($promedio !== null)
            <div class="mb-3">
                <div class="fw-black" style="font-size:2.5rem;line-height:1;color:{{ $estado === 'activo' ? '#16a34a' : ($estado === 'riesgo' ? '#b45309' : '#dc2626') }};">
                    {{ number_format($promedio, 1) }}
                </div>
                <div style="font-size:.72rem;color:#9ca3af;">Promedio General</div>
            </div>
            @endif

            @if($matriculaActual)
            <div class="text-muted" style="font-size:.78rem;">
                <i class="bi bi-people me-1"></i>
                {{ optional($matriculaActual->grupo)->nombre_corto ?? '—' }}
                · {{ $schoolYear->nombre ?? '' }}
            </div>
            @endif
        </div>

        {{-- Info personal --}}
        <div class="card border-0 shadow-sm p-3">
            <h6 class="fw-bold mb-3" style="font-size:.85rem;">Datos Personales</h6>
            @if($estudiante->user->email ?? false)
            <div class="mb-2">
                <div class="info-label">Correo</div>
                <div class="info-value">{{ $estudiante->user->email }}</div>
            </div>
            @endif
            @if($estudiante->user->cedula ?? false)
            <div class="mb-2">
                <div class="info-label">Cédula</div>
                <div class="info-value">{{ $estudiante->user->cedula }}</div>
            </div>
            @endif
            <div class="mb-2">
                <div class="info-label">Matrículas</div>
                <div class="info-value">{{ $estudiante->matriculas->count() }} año(s)</div>
            </div>

            {{-- Accesos rápidos a portales --}}
            <div class="mt-3 d-flex flex-column gap-2">
                <a href="{{ route('admin.perfiles.estudiante.historial-academico', $estudiante) }}"
                   class="btn btn-sm" style="background:#1d4ed8;color:#fff;border-radius:8px;">
                    <i class="bi bi-clock-history me-1"></i>Historial Multi-año
                </a>
                <a href="{{ route('admin.perfiles.estudiante.informe-pdf', $estudiante) }}" target="_blank"
                   class="btn btn-sm" style="background:#1e3a6e;color:#fff;border-radius:8px;">
                    <i class="bi bi-file-earmark-person me-1"></i>Informe PDF
                </a>
                <a href="{{ route('admin.perfiles.estudiante.informe-excel', $estudiante) }}"
                   class="btn btn-sm btn-success">
                    <i class="bi bi-file-earmark-excel-fill me-1"></i>Historial Excel
                </a>
                <a href="{{ route('admin.perfiles.estudiante.certificado-notas', $estudiante) }}" target="_blank"
                   class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-patch-check me-1"></i>Certificado de Notas
                </a>
                <a href="{{ route('admin.perfiles.estudiante.certificado-conducta', $estudiante) }}" target="_blank"
                   class="btn btn-sm btn-outline-success">
                    <i class="bi bi-award me-1"></i>Cert. Conducta
                </a>
                <a href="{{ route('admin.reconocimientos.historial-estudiante', $estudiante) }}"
                   class="btn btn-sm" style="background:#7c3aed;color:#fff;border-radius:8px;">
                    <i class="bi bi-trophy me-1"></i>Ver Reconocimientos
                </a>
                <a href="{{ route('portal.representante', $estudiante) }}" target="_blank"
                   class="btn btn-sm" style="background:#1d4ed8;color:#fff;border-radius:8px;">
                    <i class="bi bi-box-arrow-up-right me-1"></i>Ver Portal Representante
                </a>
                @if($matriculaActual)
                <a href="{{ route('admin.matriculas.constancia', $matriculaActual) }}" target="_blank"
                   class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-file-earmark-text me-1"></i>Descargar Constancia
                </a>
                @endif
                @if($estudiante->user_id)
                <a href="{{ route('admin.usuarios.edit', $estudiante->user_id) }}"
                   class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-person-lock me-1"></i>Gestionar Usuario
                </a>
                @endif
            </div>
        </div>
    </div>

    {{-- Columna central --}}
    <div class="col-lg-9">
        <ul class="nav nav-tabs mb-3">
            <li class="nav-item">
                <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-calif">
                    <i class="bi bi-journal-check me-1"></i>Calificaciones
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-asistencia">
                    <i class="bi bi-calendar-check me-1"></i>Asistencia
                    @if($resumenAsistencia['ausentes'] > 0)
                    <span class="badge text-bg-danger ms-1" style="font-size:.65rem;">{{ $resumenAsistencia['ausentes'] }}</span>
                    @endif
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-observaciones">
                    <i class="bi bi-chat-square-text me-1"></i>Observaciones
                    @if($observaciones->isNotEmpty())
                    <span class="badge text-bg-secondary ms-1" style="font-size:.65rem;">{{ $observaciones->count() }}</span>
                    @endif
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-historial">
                    <i class="bi bi-clock-history me-1"></i>Historial
                </button>
            </li>
            @if(\App\Models\ConfigInstitucional::moduloActivo('pagos') && auth()->user()->hasAnyRole(['Administrador','Director']))
            <li class="nav-item">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-pagos">
                    <i class="bi bi-cash-coin me-1"></i>Pagos
                </button>
            </li>
            @endif
        </ul>

        <div class="tab-content">
            {{-- Calificaciones --}}
            <div class="tab-pane fade show active" id="tab-calif">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-0">
                        @if($calificaciones->isEmpty())
                        <div class="empty-state-enhanced py-4">
                            <div class="empty-illustration"><i class="bi bi-journal-x"></i></div>
                            <div class="empty-title">Sin calificaciones registradas</div>
                        </div>
                        @else
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead style="font-size:.76rem;background:#f8fafc;color:#6b7280;text-transform:uppercase;">
                                    <tr>
                                        <th class="px-3 py-2">Asignatura</th>
                                        <th class="px-3 py-2 text-center">Nota Final</th>
                                        <th class="px-3 py-2 text-center">Situación</th>
                                        <th class="px-3 py-2 text-center">Indicador</th>
                                    </tr>
                                </thead>
                                <tbody style="font-size:.84rem;">
                                    @foreach($calificaciones as $cal)
                                    @php
                                        $nota = $cal->nota_final;
                                        $color = is_null($nota) ? '#9ca3af' : ($nota >= 80 ? '#16a34a' : ($nota >= 70 ? '#b45309' : '#dc2626'));
                                    @endphp
                                    <tr>
                                        <td class="px-3 py-2 fw-semibold">
                                            {{ optional(optional($cal->asignacion)->asignatura)->nombre ?? '—' }}
                                        </td>
                                        <td class="px-3 py-2 text-center fw-bold" style="color:{{ $color }};">
                                            {{ $nota ? number_format($nota, 1) : '—' }}
                                        </td>
                                        <td class="px-3 py-2 text-center">
                                            @if($cal->situacion === 'A')
                                                <span class="badge text-bg-success" style="font-size:.7rem;">Aprobado</span>
                                            @elseif($cal->situacion === 'R')
                                                <span class="badge text-bg-danger" style="font-size:.7rem;">Reprobado</span>
                                            @else
                                                <span class="badge text-bg-light" style="font-size:.7rem;">Pendiente</span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-2 text-center">
                                            <span style="font-size:.78rem;color:#4b5563;">{{ $cal->indicador ?? '—' }}</span>
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

            {{-- Asistencia --}}
            <div class="tab-pane fade" id="tab-asistencia">
                <div class="card border-0 shadow-sm">
                    <div class="card-header d-flex justify-content-between align-items-center py-2 px-3 border-0">
                        <span class="fw-semibold" style="font-size:.85rem;">Historial de Asistencia</span>
                        <div class="d-flex gap-1">
                            <a href="{{ route('admin.perfiles.estudiante.asistencia-pdf', $estudiante) }}" target="_blank"
                               class="btn btn-danger btn-sm" style="font-size:.75rem;">
                                <i class="bi bi-file-earmark-pdf-fill me-1"></i>PDF
                            </a>
                            <a href="{{ route('admin.perfiles.estudiante.asistencia-excel', $estudiante) }}"
                               class="btn btn-success btn-sm" style="font-size:.75rem;">
                                <i class="bi bi-file-earmark-excel-fill me-1"></i>Excel
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        @if($resumenAsistencia['total'] === 0)
                        <div class="text-center py-4 text-muted">
                            <i class="bi bi-calendar3" style="font-size:2rem;display:block;margin-bottom:.5rem;"></i>
                            Sin registros de asistencia este año.
                        </div>
                        @else
                        {{-- Resumen global --}}
                        <div class="row g-3 mb-3">
                            <div class="col-3 text-center">
                                <div class="fw-bold fs-4">{{ $resumenAsistencia['porcentaje'] }}%</div>
                                <div class="small text-muted">Asistencia</div>
                                <div class="progress mt-1" style="height:6px;">
                                    @php $pct = $resumenAsistencia['porcentaje']; $barColor = $pct >= 80 ? '#22c55e' : ($pct >= 60 ? '#f59e0b' : '#ef4444'); @endphp
                                    <div class="progress-bar" style="width:{{ $pct }}%;background:{{ $barColor }};"></div>
                                </div>
                            </div>
                            <div class="col-3 text-center">
                                <div class="fw-bold fs-4 text-success">{{ $resumenAsistencia['presentes'] }}</div>
                                <div class="small text-muted">Presentes</div>
                            </div>
                            <div class="col-3 text-center">
                                <div class="fw-bold fs-4 text-danger">{{ $resumenAsistencia['ausentes'] }}</div>
                                <div class="small text-muted">Ausencias</div>
                            </div>
                            <div class="col-3 text-center">
                                <div class="fw-bold fs-4 text-warning">{{ $resumenAsistencia['tardanzas'] }}</div>
                                <div class="small text-muted">Tardanzas</div>
                            </div>
                        </div>
                        {{-- Por materia --}}
                        @if($resumenAsistencia['por_materia']->isNotEmpty())
                        <div class="table-responsive">
                            <table class="table table-sm table-hover mb-0" style="font-size:.82rem;">
                                <thead class="table-light">
                                    <tr>
                                        <th>Materia</th>
                                        <th class="text-center">Total</th>
                                        <th class="text-center">Presentes</th>
                                        <th class="text-center">Ausencias</th>
                                        <th class="text-center">Tardanzas</th>
                                        <th class="text-center">%</th>
                                    </tr>
                                </thead>
                                <tbody>
                                @foreach($resumenAsistencia['por_materia'] as $pm)
                                @php $pctM = $pm['porcentaje']; $cM = $pctM >= 80 ? 'text-success' : ($pctM >= 60 ? 'text-warning' : 'text-danger'); @endphp
                                <tr>
                                    <td class="fw-semibold">{{ $pm['asignatura'] }}</td>
                                    <td class="text-center">{{ $pm['total'] }}</td>
                                    <td class="text-center text-success">{{ $pm['presentes'] }}</td>
                                    <td class="text-center text-danger">{{ $pm['ausentes'] }}</td>
                                    <td class="text-center text-warning">{{ $pm['tardanzas'] }}</td>
                                    <td class="text-center fw-bold {{ $cM }}">{{ $pctM }}%</td>
                                </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                        @endif
                        @endif
                    </div>
                </div>
            </div>

            {{-- Observaciones --}}
            <div class="tab-pane fade" id="tab-observaciones">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-0">
                        @if($observaciones->isEmpty())
                        <div class="text-center py-4 text-muted">
                            <i class="bi bi-chat-square" style="font-size:2rem;display:block;margin-bottom:.5rem;"></i>
                            Sin observaciones registradas.
                        </div>
                        @else
                        @foreach($observaciones as $obs)
                        @php $ti = $obs->tipo_info; @endphp
                        <div class="d-flex gap-3 p-3 border-bottom align-items-start">
                            <div style="width:36px;height:36px;border-radius:9px;background:{{ $ti['color'] }}18;color:{{ $ti['color'] }};display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                <i class="bi {{ $ti['icon'] }}"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                                    <span class="badge" style="background:{{ $ti['color'] }}18;color:{{ $ti['color'] }};font-size:.72rem;font-weight:700;">
                                        {{ $ti['label'] }}
                                    </span>
                                    @if($obs->asignacion?->asignatura)
                                    <span class="badge text-bg-light text-secondary" style="font-size:.7rem;">
                                        {{ $obs->asignacion->asignatura->nombre }}
                                    </span>
                                    @endif
                                    @if($obs->privada)
                                    <span class="badge text-bg-warning" style="font-size:.68rem;"><i class="bi bi-eye-slash me-1"></i>Privada</span>
                                    @endif
                                    <span class="ms-auto text-muted" style="font-size:.72rem;">{{ $obs->created_at->format('d/m/Y H:i') }}</span>
                                </div>
                                <div style="font-size:.84rem;color:#374151;">{{ $obs->texto }}</div>
                                <div class="text-muted mt-1" style="font-size:.72rem;">
                                    <i class="bi bi-person me-1"></i>{{ $obs->docente?->nombre_completo ?? '—' }}
                                </div>
                            </div>
                        </div>
                        @endforeach
                        @endif
                    </div>
                </div>
            </div>

            {{-- Pagos --}}
            @if(\App\Models\ConfigInstitucional::moduloActivo('pagos') && auth()->user()->hasAnyRole(['Administrador','Director']))
            @php
                $matriculaActual = $estudiante->matriculas->where('school_year_id', \App\Models\SchoolYear::actual()?->id)->first();
                $pagosEstudiante = $matriculaActual ? $matriculaActual->pagos()->latest('fecha_vencimiento')->get() : collect();
            @endphp
            <div class="tab-pane fade" id="tab-pagos">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        @if($matriculaActual)
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <span style="font-size:.83rem;font-weight:600;">Cobrado: </span>
                                <span class="text-success fw-bold">RD$ {{ number_format($pagosEstudiante->where('estado','pagado')->sum('monto'),2) }}</span>
                                &nbsp;·&nbsp;
                                <span style="font-size:.83rem;font-weight:600;">Pendiente: </span>
                                <span class="text-warning fw-bold">RD$ {{ number_format($pagosEstudiante->whereIn('estado',['pendiente','vencido'])->sum('monto'),2) }}</span>
                            </div>
                            <div class="d-flex gap-2">
                                <a href="{{ route('admin.pagos.por-estudiante', $matriculaActual) }}" class="btn btn-sm btn-outline-primary" style="font-size:.78rem;">
                                    <i class="bi bi-box-arrow-up-right me-1"></i>Ver cuenta completa
                                </a>
                                <a href="{{ route('admin.pagos.estado-cuenta-pdf', $matriculaActual) }}" target="_blank" class="btn btn-sm btn-outline-danger" style="font-size:.78rem;">
                                    <i class="bi bi-file-earmark-pdf me-1"></i>PDF
                                </a>
                            </div>
                        </div>
                        @if($pagosEstudiante->isNotEmpty())
                        <table class="table table-sm mb-0" style="font-size:.82rem;">
                            <thead><tr><th>Concepto</th><th>Monto</th><th>Vencimiento</th><th>Estado</th></tr></thead>
                            <tbody>
                                @foreach($pagosEstudiante->take(8) as $pg)
                                <tr>
                                    <td>{{ $pg->concepto }}</td>
                                    <td>RD$ {{ number_format($pg->monto,2) }}</td>
                                    <td>{{ $pg->fecha_vencimiento->format('d/m/Y') }}</td>
                                    <td>
                                        <span class="badge"
                                              style="font-size:.68rem;background:{{ match($pg->estado) { 'pagado'=>'#d1fae5', 'vencido'=>'#fee2e2', default=>'#fef3c7' } }};color:{{ match($pg->estado) { 'pagado'=>'#065f46', 'vencido'=>'#991b1b', default=>'#92400e' } }};">
                                            {{ $pg->estado_label }}
                                        </span>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                        @else
                        <p class="text-muted text-center py-3 mb-0">Sin registros de pagos para este año.</p>
                        @endif
                        @else
                        <p class="text-muted text-center py-3 mb-0">No hay matrícula activa para el año en curso.</p>
                        @endif
                    </div>
                </div>
            </div>
            @endif

            {{-- Historial --}}
            <div class="tab-pane fade" id="tab-historial">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        @forelse($estudiante->matriculas as $mat)
                        <div class="d-flex align-items-center gap-3 p-2 rounded-2 mb-2" style="background:#f8fafc;">
                            <i class="bi bi-calendar3 text-muted"></i>
                            <div class="flex-grow-1">
                                <div class="fw-semibold" style="font-size:.85rem;">{{ $mat->schoolYear->nombre ?? '—' }}</div>
                                <div class="text-muted" style="font-size:.76rem;">
                                    {{ optional($mat->grupo)->nombre_corto ?? '—' }}
                                    · {{ optional(optional($mat->grupo)->grado)->nombre ?? '' }}
                                </div>
                            </div>
                            <span class="badge {{ $mat->estado === 'activa' ? 'text-bg-success' : 'text-bg-secondary' }}" style="font-size:.65rem;">
                                {{ ucfirst($mat->estado) }}
                            </span>
                            <a href="{{ route('admin.matriculas.constancia', $mat) }}" target="_blank"
                               class="btn btn-sm" style="font-size:.7rem;background:#1e3a6e;color:#fff;border-radius:6px;padding:.2rem .55rem;"
                               title="Descargar constancia PDF">
                                <i class="bi bi-file-earmark-text"></i>
                            </a>
                        </div>
                        @empty
                        <p class="text-muted text-center py-3">Sin historial de matrículas.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ── Historial Académico completo ───────────────────────────── --}}
@if(isset($historialAnios) && $historialAnios->count() > 0)
<div class="card border-0 shadow-sm mt-4">
    <div class="card-header bg-white border-bottom py-3 d-flex align-items-center gap-2">
        <i class="bi bi-clock-history" style="color:var(--primary);font-size:1rem;"></i>
        <strong style="color:var(--primary);">Historial Académico — Todos los Años</strong>
    </div>
    <div class="card-body p-0">
        <div class="accordion accordion-flush" id="historialAccordion">
            @foreach($historialAnios as $idx => $h)
            @if($h['califs']->isNotEmpty())
            <div class="accordion-item border-0 border-bottom">
                <h2 class="accordion-header">
                    <button class="accordion-button {{ $idx > 0 ? 'collapsed' : '' }} py-2 px-3"
                            type="button" data-bs-toggle="collapse"
                            data-bs-target="#hist{{ $idx }}" style="font-size:.88rem;font-weight:700;">
                        <i class="bi bi-calendar3 me-2" style="color:var(--primary);"></i>
                        {{ $h['schoolYear']?->nombre ?? 'Año '.$idx }}
                        <span class="badge ms-2" style="background:var(--primary);color:#fff;font-size:.68rem;">
                            {{ $h['califs']->count() }} asignaturas
                        </span>
                        @if($h['promedio'] !== null)
                        <span class="badge ms-1" style="background:{{ $h['promedio'] >= 80 ? '#16a34a' : ($h['promedio'] >= 70 ? '#d97706' : '#dc2626') }};color:#fff;font-size:.68rem;">
                            Prom: {{ number_format($h['promedio'],1) }}
                        </span>
                        @endif
                    </button>
                </h2>
                <div id="hist{{ $idx }}" class="accordion-collapse collapse {{ $idx === 0 ? 'show' : '' }}"
                     data-bs-parent="#historialAccordion">
                    <div class="table-responsive">
                        <table class="table table-sm mb-0" style="font-size:.8rem;">
                            <thead style="background:#f8faff;">
                                <tr>
                                    <th class="ps-3">Asignatura</th>
                                    <th class="text-center">Nota Final</th>
                                    <th class="text-center">Situación</th>
                                </tr>
                            </thead>
                            <tbody>
                            @foreach($h['califs'] as $cal)
                            <tr>
                                <td class="ps-3">{{ $cal->asignacion?->asignatura?->nombre ?? '—' }}</td>
                                <td class="text-center">
                                    @if($cal->nota_final !== null)
                                    <span style="font-weight:700;color:{{ $cal->nota_final >= 80 ? '#16a34a' : ($cal->nota_final >= 70 ? '#d97706' : '#dc2626') }};">
                                        {{ number_format($cal->nota_final,1) }}
                                    </span>
                                    @else —
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($cal->situacion)
                                    <span class="badge" style="font-size:.68rem;background:{{ $cal->situacion === 'Aprobado' ? '#dcfce7' : '#fee2e2' }};color:{{ $cal->situacion === 'Aprobado' ? '#166534' : '#991b1b' }};">
                                        {{ $cal->situacion }}
                                    </span>
                                    @else —
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endif
            @endforeach
        </div>
    </div>
</div>
@endif
@endsection
