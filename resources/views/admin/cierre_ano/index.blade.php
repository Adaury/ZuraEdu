@extends('layouts.admin')

@section('page-title', 'Cierre de Año Escolar')

@push('styles')
<style>
    /* ── Tarjeta resumen superior ─── */
    .stat-card {
        border-radius: 12px;
        padding: 1.1rem 1.3rem;
        display: flex;
        align-items: center;
        gap: .85rem;
        border: 1px solid #e5e7eb;
        background: #fff;
        box-shadow: 0 1px 4px rgba(30,58,110,.05);
    }
    .stat-icon {
        width: 46px; height: 46px;
        border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.2rem; flex-shrink: 0;
    }
    .stat-label { font-size: .72rem; font-weight: 700; text-transform: uppercase;
                  letter-spacing: .07em; color: #6b7280; margin-bottom: 1px; }
    .stat-value { font-size: 1.55rem; font-weight: 900; line-height: 1; }

    /* ── Tabla de grupos ─── */
    .table-card {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 1px 6px rgba(30,58,110,.05);
    }
    .table thead th {
        background: #f8fafc;
        border-bottom: 1px solid #e5e7eb;
        font-size: .72rem;
        font-weight: 700;
        letter-spacing: .07em;
        text-transform: uppercase;
        color: #2563eb;
        padding: .75rem 1rem;
        white-space: nowrap;
    }
    .table tbody td {
        padding: .7rem 1rem;
        vertical-align: middle;
        border-bottom: 1px solid #f3f4f6;
        font-size: .84rem;
    }
    .table tbody tr:last-child td { border-bottom: none; }
    .table tbody tr:hover td { background: #fafbff; }

    /* ── Badges ─── */
    .badge-aprobado  { background: #d1fae5; color: #065f46; font-size: .68rem;
                       font-weight: 700; padding: .22rem .6rem; border-radius: 20px; }
    .badge-reprobado { background: #fee2e2; color: #991b1b; font-size: .68rem;
                       font-weight: 700; padding: .22rem .6rem; border-radius: 20px; }
    .badge-pendiente { background: #fef3c7; color: #92400e; font-size: .68rem;
                       font-weight: 700; padding: .22rem .6rem; border-radius: 20px; }

    /* ── Barra de progreso compacta ─── */
    .prog-bar { height: 6px; border-radius: 3px; background: #f3f4f6; overflow: hidden; min-width: 80px; }
    .prog-fill-a  { height: 100%; background: #10b981; float: left; }
    .prog-fill-r  { height: 100%; background: #ef4444; float: left; }
    .prog-fill-p  { height: 100%; background: #f59e0b; float: left; }

    /* ── Panel de cierre ─── */
    .cierre-panel {
        background: linear-gradient(135deg, #1e3a6e 0%, #2563eb 100%);
        border-radius: 14px;
        padding: 1.6rem 2rem;
        color: #fff;
    }
    .cierre-panel h5 { font-weight: 800; font-size: 1.05rem; }
    .cierre-panel p  { font-size: .85rem; opacity: .85; }

    /* ── Stepper ─── */
    .workflow-stepper {
        display: flex;
        align-items: flex-start;
        gap: 0;
        margin-bottom: 1.75rem;
    }
    .ws-step {
        flex: 1;
        display: flex;
        flex-direction: column;
        align-items: center;
        position: relative;
        text-align: center;
    }
    .ws-step:not(:last-child)::after {
        content: '';
        position: absolute;
        top: 18px;
        left: 50%;
        width: 100%;
        height: 2px;
        background: #e2e8f0;
        z-index: 0;
    }
    .ws-step.done::after { background: #10b981; }
    .ws-step.active::after { background: #e2e8f0; }
    .ws-dot {
        width: 36px; height: 36px;
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-size: .8rem; font-weight: 800;
        border: 2px solid #e2e8f0;
        background: #fff;
        color: #94a3b8;
        position: relative; z-index: 1;
        flex-shrink: 0;
    }
    .ws-step.done .ws-dot  { background: #10b981; border-color: #10b981; color: #fff; }
    .ws-step.active .ws-dot{ background: #2563eb; border-color: #2563eb; color: #fff; box-shadow: 0 0 0 4px #dbeafe; }
    .ws-label {
        font-size: .69rem; font-weight: 600; color: #94a3b8;
        margin-top: .4rem; line-height: 1.25; max-width: 80px;
    }
    .ws-step.done .ws-label   { color: #059669; }
    .ws-step.active .ws-label { color: #1d4ed8; }

    /* ── Panel preflight ─── */
    .preflight-item {
        display: flex; align-items: flex-start; gap: .6rem;
        padding: .5rem .75rem;
        border-radius: 8px;
        background: #fffbeb;
        border: 1px solid #fde68a;
        font-size: .81rem;
        color: #78350f;
    }
    .preflight-ok {
        background: #f0fdf4;
        border-color: #bbf7d0;
        color: #14532d;
    }

    /* Dark mode */
    [data-theme="dark"] .stat-card,
    [data-theme="dark"] .table-card { background: #1e293b; border-color: #334155; }
    [data-theme="dark"] .table thead th { background: #0f172a; color: #93c5fd; border-color: #334155; }
    [data-theme="dark"] .table tbody td { border-color: #1e293b; color: #e2e8f0; }
    [data-theme="dark"] .table tbody tr:hover td { background: #0f172a; }
    [data-theme="dark"] .stat-label { color: #94a3b8; }
    [data-theme="dark"] .stat-value { color: #f1f5f9; }
    [data-theme="dark"] .ws-dot { background: #1e293b; border-color: #334155; }
</style>
@endpush

@section('content')

<x-breadcrumb :items="[
    ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
    ['label' => 'Cierre de Año Escolar'],
]" />

{{-- Alertas de sesión ─────────────────────────────────────────────────── --}}
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif
@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@php
    $yearActivo   = $schoolYear && $schoolYear->activo;
    $yearCerrado  = $schoolYear && ! $schoolYear->activo;
    $tieneNuevoAno = isset($nuevoAno) && $nuevoAno;

    // Determinar paso actual del workflow
    if (! $schoolYear)       $pasoActual = 3; // sin año activo → crear nuevo
    elseif ($yearCerrado)    $pasoActual = 3;
    elseif ($tieneNuevoAno)  $pasoActual = 4;
    else                     $pasoActual = 1;
@endphp

{{-- Workflow Stepper ─────────────────────────────────────────────────── --}}
<div class="workflow-stepper">
    @php
    $pasos = [
        1 => ['label' => 'Verificar preflight', 'icon' => 'bi-clipboard-check-fill'],
        2 => ['label' => 'Ejecutar cierre',      'icon' => 'bi-lock-fill'],
        3 => ['label' => 'Crear nuevo año',      'icon' => 'bi-calendar-plus-fill'],
        4 => ['label' => 'Trasladar alumnos',    'icon' => 'bi-arrow-right-circle-fill'],
    ];
    @endphp
    @foreach($pasos as $n => $paso)
    @php
        $state = $n < $pasoActual ? 'done' : ($n === $pasoActual ? 'active' : '');
    @endphp
    <div class="ws-step {{ $state }}">
        <div class="ws-dot">
            @if($n < $pasoActual)
                <i class="bi bi-check-lg"></i>
            @else
                {{ $n }}
            @endif
        </div>
        <div class="ws-label">{{ $paso['label'] }}</div>
    </div>
    @endforeach
</div>

{{-- ══════════════════════════════════════════════════════════════════════
     CASO A: Hay año escolar ACTIVO → flujo de cierre
══════════════════════════════════════════════════════════════════════════ --}}
@if($yearActivo)

<div x-data="{ tab: '{{ session('tab', 'cierre') }}' }">

{{-- Nav tabs --}}
<ul class="nav nav-tabs mb-4 border-bottom-0">
    <li class="nav-item">
        <button class="nav-link fw-semibold" :class="tab==='cierre' ? 'active' : ''"
                @click="tab='cierre'" type="button">
            <i class="bi bi-mortarboard-fill me-1"></i> Cierre de Año
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link fw-semibold" :class="tab==='boletines' ? 'active' : ''"
                @click="tab='boletines'" type="button">
            <i class="bi bi-file-earmark-zip-fill me-1"></i> Boletines Masivos
        </button>
    </li>
</ul>

{{-- ─── TAB: CIERRE ─── --}}
<div x-show="tab==='cierre'" x-transition>

    {{-- Encabezado año activo --}}
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4">
        <div>
            <h4 class="fw-bold mb-0">
                <i class="bi bi-calendar2-check me-2 text-primary"></i>
                {{ $schoolYear->nombre }}
            </h4>
            <small class="text-muted">
                {{ $schoolYear->fecha_inicio?->format('d/m/Y') }} —
                {{ $schoolYear->fecha_fin?->format('d/m/Y') }}
            </small>
        </div>
        <span class="badge bg-success fs-6 px-3 py-2">
            <i class="bi bi-circle-fill me-1" style="font-size:.45rem;vertical-align:middle;"></i>
            Año Activo
        </span>
    </div>

    {{-- Panel Preflight ─────────────────────────────────────────────────── --}}
    @if($preflight)
    <div class="table-card mb-4">
        <div class="d-flex align-items-center gap-2 px-3 py-3 border-bottom" style="background:#f8fafc;">
            <i class="bi bi-clipboard-check-fill text-primary"></i>
            <span class="fw-bold" style="font-size:.9rem;">Verificación Previa al Cierre</span>
            @if(empty($preflight['advertencias']))
                <span class="badge bg-success ms-auto">Todo listo</span>
            @else
                <span class="badge bg-warning text-dark ms-auto">{{ count($preflight['advertencias']) }} advertencia(s)</span>
            @endif
        </div>
        <div class="p-3" style="display:flex;flex-direction:column;gap:.5rem;">
            <div class="preflight-item {{ $preflight['periodos_cerrados'] >= $preflight['periodos_total'] && $preflight['periodos_total'] > 0 ? 'preflight-ok' : '' }}">
                <i class="bi {{ $preflight['periodos_cerrados'] >= $preflight['periodos_total'] && $preflight['periodos_total'] > 0 ? 'bi-check-circle-fill text-success' : 'bi-exclamation-triangle-fill text-warning' }} flex-shrink-0 mt-1"></i>
                <span>
                    Períodos cerrados:
                    <strong>{{ $preflight['periodos_cerrados'] }} / {{ $preflight['periodos_total'] }}</strong>
                    @if($preflight['periodos_cerrados'] < $preflight['periodos_total'])
                        — Faltan {{ $preflight['periodos_total'] - $preflight['periodos_cerrados'] }} por cerrar
                    @endif
                </span>
            </div>
            @php $sinPromo = $preflight['total_estudiantes'] - $preflight['con_promocion']; @endphp
            <div class="preflight-item {{ $sinPromo === 0 ? 'preflight-ok' : '' }}">
                <i class="bi {{ $sinPromo === 0 ? 'bi-check-circle-fill text-success' : 'bi-exclamation-triangle-fill text-warning' }} flex-shrink-0 mt-1"></i>
                <span>
                    Promoción calculada:
                    <strong>{{ $preflight['con_promocion'] }} / {{ $preflight['total_estudiantes'] }}</strong> estudiantes
                    @if($sinPromo > 0)
                        — <em>{{ $sinPromo }} sin evaluar</em> (se marcarán como pendientes)
                    @endif
                </span>
            </div>
            @if($schoolYear->fecha_fin && $schoolYear->fecha_fin->isFuture())
            <div class="preflight-item">
                <i class="bi bi-exclamation-triangle-fill text-warning flex-shrink-0 mt-1"></i>
                <span>La fecha de fin del año escolar es <strong>{{ $schoolYear->fecha_fin->format('d/m/Y') }}</strong> (todavía no ha llegado).</span>
            </div>
            @else
            <div class="preflight-item preflight-ok">
                <i class="bi bi-check-circle-fill text-success flex-shrink-0 mt-1"></i>
                <span>Fecha de fin del año escolar: <strong>{{ $schoolYear->fecha_fin?->format('d/m/Y') ?? 'Sin fecha' }}</strong></span>
            </div>
            @endif
        </div>
    </div>
    @endif

    {{-- Tarjetas de resumen --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <div class="stat-icon" style="background:#eff6ff;">
                    <i class="bi bi-people-fill text-primary"></i>
                </div>
                <div>
                    <div class="stat-label">Total Estudiantes</div>
                    <div class="stat-value text-primary">{{ $totalAprobados + $totalReprobados + $totalPendientes }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <div class="stat-icon" style="background:#d1fae5;">
                    <i class="bi bi-check-circle-fill" style="color:#059669;"></i>
                </div>
                <div>
                    <div class="stat-label">Promovidos</div>
                    <div class="stat-value" style="color:#059669;">{{ $totalAprobados }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <div class="stat-icon" style="background:#fee2e2;">
                    <i class="bi bi-x-circle-fill text-danger"></i>
                </div>
                <div>
                    <div class="stat-label">No Promovidos</div>
                    <div class="stat-value text-danger">{{ $totalReprobados }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <div class="stat-icon" style="background:#fef3c7;">
                    <i class="bi bi-hourglass-split" style="color:#d97706;"></i>
                </div>
                <div>
                    <div class="stat-label">Pendientes</div>
                    <div class="stat-value" style="color:#d97706;">{{ $totalPendientes }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Tabla de grupos --}}
    <div class="table-card mb-4">
        <div class="d-flex align-items-center justify-content-between px-3 py-3 border-bottom">
            <span class="fw-bold" style="font-size:.9rem;">
                <i class="bi bi-grid-3x3-gap-fill me-1 text-primary"></i>
                Resumen por Grupo
            </span>
            <span class="text-muted" style="font-size:.78rem;">{{ count($resumen) }} grupos</span>
        </div>
        @if(count($resumen) > 0)
        <div class="table-responsive">
            <table class="table table-borderless mb-0">
                <thead>
                    <tr>
                        <th>Grupo</th>
                        <th>Ciclo</th>
                        <th class="text-center">Total</th>
                        <th class="text-center">Promovidos</th>
                        <th class="text-center">No Prom.</th>
                        <th class="text-center">Pendientes</th>
                        <th style="width:120px;">Distribución</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($resumen as $grupoId => $data)
                    <tr>
                        <td><span class="fw-semibold">{{ $data['grupo']->nombre_completo }}</span></td>
                        <td>
                            @php $ciclo = $data['grupo']->grado?->ciclo; @endphp
                            @if($ciclo === 'primer_ciclo')
                                <span class="badge" style="background:#eff6ff;color:#1d4ed8;font-size:.67rem;">1er Ciclo</span>
                            @elseif($ciclo === 'segundo_ciclo')
                                <span class="badge" style="background:#f0fdf4;color:#15803d;font-size:.67rem;">2do Ciclo</span>
                            @elseif($ciclo === 'bachillerato')
                                <span class="badge" style="background:#faf5ff;color:#7e22ce;font-size:.67rem;">Bachillerato</span>
                            @elseif($ciclo === 'inicial')
                                <span class="badge" style="background:#fefce8;color:#a16207;font-size:.67rem;">Inicial</span>
                            @else
                                <span class="badge bg-secondary" style="font-size:.67rem;">{{ $ciclo }}</span>
                            @endif
                        </td>
                        <td class="text-center fw-bold">{{ $data['total'] }}</td>
                        <td class="text-center"><span class="badge-aprobado">{{ $data['aprobados'] }}</span></td>
                        <td class="text-center"><span class="badge-reprobado">{{ $data['reprobados'] }}</span></td>
                        <td class="text-center"><span class="badge-pendiente">{{ $data['pendientes'] }}</span></td>
                        <td>
                            @if($data['total'] > 0)
                            <div class="prog-bar">
                                <div class="prog-fill-a" style="width:{{ round($data['aprobados']/$data['total']*100) }}%"></div>
                                <div class="prog-fill-r" style="width:{{ round($data['reprobados']/$data['total']*100) }}%"></div>
                                <div class="prog-fill-p" style="width:{{ round($data['pendientes']/$data['total']*100) }}%"></div>
                            </div>
                            @endif
                        </td>
                        <td class="text-end">
                            <a href="{{ route('admin.cierre-ano.acta-pdf', $data['grupo']) }}"
                               target="_blank"
                               class="btn btn-sm btn-outline-danger py-1 px-2"
                               title="Descargar Acta PDF">
                                <i class="bi bi-file-earmark-pdf-fill me-1"></i>Acta PDF
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
            <div class="text-center py-5 text-muted">
                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                No hay grupos registrados para este año escolar.
            </div>
        @endif
    </div>

    {{-- Panel de ejecución del cierre --}}
    <div x-data="{ confirmar: false }">
        <div class="cierre-panel">
            <div class="row align-items-center g-3">
                <div class="col-md-8">
                    <h5 class="mb-1"><i class="bi bi-lock-fill me-2"></i>Ejecutar Cierre de Año</h5>
                    <p class="mb-0">
                        Al ejecutar el cierre, el sistema marcará el año como <strong>cerrado</strong>,
                        registrará la <strong>promoción</strong> de cada estudiante y cambiará el estado
                        de las matrículas. Esta acción <strong>no se puede deshacer</strong>.
                    </p>
                    @if($totalPendientes > 0)
                    <div class="alert alert-warning mt-2 mb-0 py-2 px-3" style="font-size:.82rem;">
                        <i class="bi bi-exclamation-triangle-fill me-1"></i>
                        <strong>{{ $totalPendientes }}</strong> estudiante(s) sin calificación final serán marcados como <em>pendiente</em>.
                    </div>
                    @endif
                </div>
                <div class="col-md-4 text-md-end">
                    <button type="button" @click="confirmar = true"
                            class="btn btn-warning btn-lg fw-bold shadow-sm">
                        <i class="bi bi-play-fill me-2"></i>Ejecutar Cierre
                    </button>
                </div>
            </div>
        </div>

        {{-- Modal de confirmación --}}
        <div x-show="confirmar" x-transition.opacity style="display:none;"
             class="modal-backdrop-custom"
             style="position:fixed; inset:0; background:rgba(0,0,0,.55); z-index:1050;
                    display:flex; align-items:center; justify-content:center;">
            <div style="position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:1050;display:flex;align-items:center;justify-content:center;"
                 x-show="confirmar" @click.self="confirmar=false">
                <div class="bg-white rounded-3 shadow-lg p-4" style="max-width:480px;width:100%;" @click.stop>
                    <div class="text-center mb-3">
                        <div style="width:64px;height:64px;border-radius:50%;background:#fee2e2;
                                    display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;">
                            <i class="bi bi-exclamation-triangle-fill text-danger fs-2"></i>
                        </div>
                        <h5 class="fw-bold text-danger mb-1">¿Confirmar Cierre de Año?</h5>
                        <p class="text-muted mb-0" style="font-size:.88rem;">
                            Se procesarán <strong>{{ $totalAprobados + $totalReprobados + $totalPendientes }}</strong>
                            estudiantes del año <strong>{{ $schoolYear->nombre }}</strong>.
                            El año quedará <strong>cerrado e inactivo</strong>. Esta acción no se puede deshacer.
                        </p>
                    </div>
                    <form method="POST" action="{{ route('admin.cierre-ano.ejecutar') }}">
                        @csrf
                        <input type="hidden" name="school_year_id" value="{{ $schoolYear->id }}">
                        <div class="d-flex gap-2 justify-content-center mt-3">
                            <button type="button" @click="confirmar = false" class="btn btn-secondary px-4">
                                <i class="bi bi-x-circle me-1"></i>Cancelar
                            </button>
                            <button type="submit" class="btn btn-danger px-4 fw-bold">
                                <i class="bi bi-check-circle-fill me-1"></i>Sí, ejecutar cierre
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

</div>{{-- /tab cierre --}}

{{-- ─── TAB: BOLETINES MASIVOS ─── --}}
<div x-show="tab==='boletines'" x-transition style="display:none;">
    <div class="row justify-content-center">
        <div class="col-lg-7">
            <div class="table-card p-4">
                <h5 class="fw-bold mb-1">
                    <i class="bi bi-file-earmark-zip-fill me-2 text-primary"></i>
                    Generación Masiva de Boletines
                </h5>
                <p class="text-muted mb-4" style="font-size:.85rem;">
                    Selecciona un grupo y un período para descargar todos los boletines en un archivo ZIP.
                </p>
                <form method="POST" action="{{ route('admin.cierre-ano.boletines-masivos') }}" id="formBoletines">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Grupo</label>
                        <select name="grupo_id" class="form-select" required>
                            <option value="">— Seleccionar grupo —</option>
                            @foreach($grupos as $g)
                            <option value="{{ $g->id }}">{{ $g->nombre_completo }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Período</label>
                        <select name="periodo_id" class="form-select" required>
                            <option value="">— Seleccionar período —</option>
                            @foreach($periodos ?? [] as $p)
                            <option value="{{ $p->id }}">Período {{ $p->numero }}
                                @if($p->nombre) — {{ $p->nombre }} @endif
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary fw-bold w-100"
                            x-data="{ loading: false }"
                            @click="loading = true; $nextTick(() => { setTimeout(() => loading = false, 8000) })"
                            :disabled="loading">
                        <span x-show="!loading"><i class="bi bi-download me-1"></i>Descargar ZIP con Boletines</span>
                        <span x-show="loading"><span class="spinner-border spinner-border-sm me-1"></span>Generando...</span>
                    </button>
                </form>
            </div>
            <div class="alert alert-info d-flex gap-2 mt-3" style="font-size:.82rem;">
                <i class="bi bi-info-circle-fill flex-shrink-0 mt-1"></i>
                <div>El ZIP incluirá un PDF por cada estudiante activo del grupo y período seleccionados.</div>
            </div>
        </div>
    </div>
</div>

</div>{{-- /x-data tabs --}}

{{-- ══════════════════════════════════════════════════════════════════════
     CASO B: No hay año activo → Crear Nuevo Año
══════════════════════════════════════════════════════════════════════════ --}}
@else

@php
    $ultimoCerrado = App\Models\SchoolYear::where('activo', false)->orderByDesc('fecha_fin')->first();
    $sugNombre = $ultimoCerrado
        ? (string)(((int) filter_var($ultimoCerrado->nombre, FILTER_SANITIZE_NUMBER_INT)) + 1) . '-' . (((int) filter_var($ultimoCerrado->nombre, FILTER_SANITIZE_NUMBER_INT)) + 2)
        : '';
    // Suggest dates: 1 year after last
    $sugInicio = $ultimoCerrado?->fecha_fin?->addDay()->format('Y-m-d') ?? '';
    $sugFin    = $ultimoCerrado?->fecha_fin?->addYear()->format('Y-m-d') ?? '';
@endphp

<div class="row justify-content-center">
    <div class="col-lg-7">

        {{-- Panel informativo --}}
        @if($ultimoCerrado)
        <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:12px;padding:1.1rem 1.3rem;margin-bottom:1.5rem;display:flex;align-items:flex-start;gap:.9rem;">
            <div style="width:40px;height:40px;border-radius:10px;background:#d1fae5;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="bi bi-check-circle-fill text-success fs-5"></i>
            </div>
            <div>
                <div style="font-size:.9rem;font-weight:800;color:#14532d;">Año escolar cerrado correctamente</div>
                <div style="font-size:.82rem;color:#166534;margin-top:.15rem;">
                    El año <strong>{{ $ultimoCerrado->nombre }}</strong> fue cerrado.
                    Ahora puedes crear el nuevo año escolar y trasladar a los estudiantes.
                </div>
            </div>
        </div>
        @endif

        {{-- Formulario crear nuevo año --}}
        <div class="table-card">
            <div class="d-flex align-items-center gap-2 px-3 py-3 border-bottom" style="background:#f8fafc;">
                <i class="bi bi-calendar-plus-fill text-primary"></i>
                <span class="fw-bold" style="font-size:.9rem;">Crear Nuevo Año Escolar</span>
            </div>
            <div class="p-4">
                <form method="POST" action="{{ route('admin.cierre-ano.crear-nuevo-ano') }}">
                    @csrf
                    @if($ultimoCerrado)
                    <input type="hidden" name="ano_base_id" value="{{ $ultimoCerrado->id }}">
                    @endif

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nombre del año escolar <span class="text-danger">*</span></label>
                        <input type="text" name="nombre" class="form-control @error('nombre') is-invalid @enderror"
                               value="{{ old('nombre', $sugNombre) }}"
                               placeholder="Ej: 2026-2027" required maxlength="50">
                        @error('nombre') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-6">
                            <label class="form-label fw-semibold">Fecha de inicio <span class="text-danger">*</span></label>
                            <input type="date" name="fecha_inicio" class="form-control @error('fecha_inicio') is-invalid @enderror"
                                   value="{{ old('fecha_inicio', $sugInicio) }}" required>
                            @error('fecha_inicio') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold">Fecha de fin <span class="text-danger">*</span></label>
                            <input type="date" name="fecha_fin" class="form-control @error('fecha_fin') is-invalid @enderror"
                                   value="{{ old('fecha_fin', $sugFin) }}" required>
                            @error('fecha_fin') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    @if(! $ultimoCerrado)
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Año base (para clonar grupos) <span class="text-danger">*</span></label>
                        <select name="ano_base_id" class="form-select" required>
                            <option value="">— Seleccionar —</option>
                            @foreach(App\Models\SchoolYear::orderByDesc('fecha_inicio')->get() as $sy)
                            <option value="{{ $sy->id }}">{{ $sy->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endif

                    <div class="alert alert-info d-flex gap-2 mb-4" style="font-size:.81rem;">
                        <i class="bi bi-info-circle-fill flex-shrink-0 mt-1"></i>
                        <span>Los grupos del año base se clonarán automáticamente (mismos grados y secciones, sin estudiantes). A continuación podrás trasladar a los alumnos.</span>
                    </div>

                    <button type="submit" class="btn btn-primary fw-bold w-100 py-2">
                        <i class="bi bi-calendar-plus-fill me-2"></i>Crear Año Escolar y Continuar
                    </button>
                </form>
            </div>
        </div>

    </div>
</div>

@endif {{-- /yearActivo --}}

@endsection

@push('scripts')
<script>
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const el = document.querySelector('[x-data*="confirmar"]');
        if (el && el.__x) el.__x.$data.confirmar = false;
    }
});
</script>
@endpush
