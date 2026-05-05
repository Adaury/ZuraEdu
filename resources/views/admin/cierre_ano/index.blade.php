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

    /* Dark mode */
    [data-theme="dark"] .stat-card,
    [data-theme="dark"] .table-card { background: #1e293b; border-color: #334155; }
    [data-theme="dark"] .table thead th { background: #0f172a; color: #93c5fd; border-color: #334155; }
    [data-theme="dark"] .table tbody td { border-color: #1e293b; color: #e2e8f0; }
    [data-theme="dark"] .table tbody tr:hover td { background: #0f172a; }
    [data-theme="dark"] .stat-label { color: #94a3b8; }
    [data-theme="dark"] .stat-value { color: #f1f5f9; }
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

@if(! $schoolYear)
    <div class="alert alert-warning d-flex align-items-center gap-2">
        <i class="bi bi-exclamation-triangle-fill fs-5"></i>
        <div>No hay un <strong>año escolar activo</strong> configurado.
            <a href="{{ route('admin.school-years.index') }}" class="alert-link">Ir a Años Escolares</a>
        </div>
    </div>
@else

{{-- Tabs ─────────────────────────────────────────────────────────────── --}}
<div x-data="{ tab: '{{ session('tab', 'cierre') }}' }">

{{-- Nav tabs ─────────────────────────────────────────────────────────── --}}
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

{{-- ═══════════════════════════════ TAB: CIERRE ═══════════════════════ --}}
<div x-show="tab==='cierre'" x-transition>

    {{-- Encabezado año activo ──────────────────────────────────────────── --}}
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

    {{-- Tarjetas de resumen ─────────────────────────────────────────────── --}}
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
                    <div class="stat-label">Aprobados</div>
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
                    <div class="stat-label">Reprobados</div>
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

    {{-- Tabla de grupos ─────────────────────────────────────────────────── --}}
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
                        <th class="text-center">Aprobados</th>
                        <th class="text-center">Reprobados</th>
                        <th class="text-center">Pendientes</th>
                        <th style="width:120px;">Distribución</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($resumen as $grupoId => $data)
                    <tr>
                        <td>
                            <span class="fw-semibold">{{ $data['grupo']->nombre_completo }}</span>
                        </td>
                        <td>
                            @if($data['grupo']->grado?->ciclo === 'primer_ciclo')
                                <span class="badge" style="background:#eff6ff;color:#1d4ed8;font-size:.67rem;">1er Ciclo</span>
                            @else
                                <span class="badge" style="background:#f0fdf4;color:#15803d;font-size:.67rem;">2do Ciclo</span>
                            @endif
                        </td>
                        <td class="text-center fw-bold">{{ $data['total'] }}</td>
                        <td class="text-center">
                            <span class="badge-aprobado">{{ $data['aprobados'] }}</span>
                        </td>
                        <td class="text-center">
                            <span class="badge-reprobado">{{ $data['reprobados'] }}</span>
                        </td>
                        <td class="text-center">
                            <span class="badge-pendiente">{{ $data['pendientes'] }}</span>
                        </td>
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

    {{-- Panel de ejecución del cierre ──────────────────────────────────── --}}
    <div x-data="{ confirmar: false }">

        <div class="cierre-panel">
            <div class="row align-items-center g-3">
                <div class="col-md-8">
                    <h5 class="mb-1"><i class="bi bi-lock-fill me-2"></i>Ejecutar Cierre de Año</h5>
                    <p class="mb-0">
                        Al ejecutar el cierre, el sistema:
                        marcará el año como <strong>cerrado</strong>,
                        generará los registros de <strong>promoción</strong> de todos los estudiantes,
                        y cambiará el estado de las matrículas aprobadas a <em>promovida</em>.
                        Esta acción <strong>no se puede deshacer</strong>.
                    </p>

                    @if($totalPendientes > 0)
                    <div class="alert alert-warning mt-2 mb-0 py-2 px-3" style="font-size:.82rem;">
                        <i class="bi bi-exclamation-triangle-fill me-1"></i>
                        Hay <strong>{{ $totalPendientes }}</strong> estudiante(s) con situación pendiente.
                        Se intentará calcular su promedio automáticamente; si no hay notas se marcarán como <em>pendiente</em>.
                    </div>
                    @endif
                </div>
                <div class="col-md-4 text-md-end">
                    <button type="button"
                            @click="confirmar = true"
                            class="btn btn-warning btn-lg fw-bold shadow-sm">
                        <i class="bi bi-play-fill me-2"></i>Ejecutar Cierre
                    </button>
                </div>
            </div>
        </div>

        {{-- Modal de confirmación ──────────────────────────────────────── --}}
        <div x-show="confirmar"
             x-transition.opacity
             class="modal-backdrop-custom"
             style="display:none; position:fixed; inset:0; background:rgba(0,0,0,.55); z-index:1050;
                    display:flex; align-items:center; justify-content:center;">
            <div class="bg-white rounded-3 shadow-lg p-4" style="max-width:480px; width:100%;"
                 @click.stop>
                <div class="text-center mb-3">
                    <div style="width:64px;height:64px;border-radius:50%;background:#fee2e2;
                                display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;">
                        <i class="bi bi-exclamation-triangle-fill text-danger fs-2"></i>
                    </div>
                    <h5 class="fw-bold text-danger mb-1">¿Confirmar Cierre de Año?</h5>
                    <p class="text-muted mb-0" style="font-size:.88rem;">
                        Se procesarán <strong>{{ $totalAprobados + $totalReprobados + $totalPendientes }}</strong>
                        estudiantes del año escolar <strong>{{ $schoolYear->nombre }}</strong>.
                        El año quedará <strong>cerrado e inactivo</strong>.
                        Esta acción no se puede deshacer.
                    </p>
                </div>

                <form method="POST" action="{{ route('admin.cierre-ano.ejecutar') }}">
                    @csrf
                    <input type="hidden" name="school_year_id" value="{{ $schoolYear->id }}">
                    <div class="d-flex gap-2 justify-content-center mt-3">
                        <button type="button" @click="confirmar = false"
                                class="btn btn-secondary px-4">
                            <i class="bi bi-x-circle me-1"></i>Cancelar
                        </button>
                        <button type="submit" class="btn btn-danger px-4 fw-bold">
                            <i class="bi bi-check-circle-fill me-1"></i>Sí, ejecutar cierre
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>{{-- /x-data confirmar --}}

</div>{{-- /tab cierre --}}

{{-- ════════════════════════════ TAB: BOLETINES MASIVOS ════════════════ --}}
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

                @if($schoolYear)
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
                        <span x-show="!loading">
                            <i class="bi bi-download me-1"></i>Descargar ZIP con Boletines
                        </span>
                        <span x-show="loading">
                            <span class="spinner-border spinner-border-sm me-1"></span>
                            Generando, por favor espera...
                        </span>
                    </button>
                </form>
                @else
                    <div class="alert alert-warning">No hay año escolar activo.</div>
                @endif

            </div>

            {{-- Info card ─────────────────────────────────────────────── --}}
            <div class="alert alert-info d-flex gap-2 mt-3" style="font-size:.82rem;">
                <i class="bi bi-info-circle-fill flex-shrink-0 mt-1"></i>
                <div>
                    El ZIP incluirá un PDF por cada estudiante activo del grupo y período seleccionados.
                    Los boletines se generan con los mismos datos del módulo de <strong>Boletines</strong>.
                    Para grupos grandes el proceso puede tardar algunos segundos.
                </div>
            </div>
        </div>
    </div>

</div>{{-- /tab boletines --}}

</div>{{-- /x-data tabs --}}

@endif {{-- /schoolYear --}}

@endsection

@push('scripts')
<script>
// Cerrar modal con Escape
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const btn = document.querySelector('[\\@click="confirmar = false"]');
        if (btn) btn.click();
    }
});
</script>
@endpush
