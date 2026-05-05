@extends('layouts.admin')
@section('page-title', 'Asistencia')

@push('styles')
<style>
    .asig-row-card {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        padding: .9rem 1.1rem;
        margin-bottom: .6rem;
        transition: box-shadow .15s, border-color .15s;
    }
    .asig-row-card:hover {
        box-shadow: 0 3px 12px rgba(30,58,110,.10);
        border-color: #c7d6f0;
    }
    .materia-badge-pill {
        font-size: .75rem;
        font-weight: 700;
        padding: .3em .85em;
        border-radius: 20px;
        background: var(--primary);
        color: #fff;
        white-space: nowrap;
    }
    .stats-mini span {
        font-size: .75rem;
        font-weight: 600;
        padding: .2em .6em;
        border-radius: 12px;
        white-space: nowrap;
    }
    .stat-p { background: #dcfce7; color: #15803d; }
    .stat-a { background: #fee2e2; color: #991b1b; }
    .stat-t { background: #fef3c7; color: #92400e; }
    .stat-j { background: #dbeafe; color: #1d4ed8; }

    [data-theme="dark"] .asig-row-card { background: #1e293b; border-color: #334155; }
    [data-theme="dark"] .asig-row-card:hover { border-color: #4b6a9e; }
    [data-theme="dark"] .stat-p { background: #052e16; color: #4ade80; }
    [data-theme="dark"] .stat-a { background: #1c0000; color: #f87171; }
    [data-theme="dark"] .stat-t { background: #1c1000; color: #fcd34d; }
    [data-theme="dark"] .stat-j { background: #0c1f3f; color: #93c5fd; }
</style>
@endpush

@section('content')

<x-breadcrumb :items="[
    ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
    ['label' => 'Asistencia'],
]" />

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold mb-0" style="color:var(--primary)">
            <i class="bi bi-calendar-check me-2"></i>Registro de Asistencia
            @isset($contexto)
                <span class="badge ms-2 px-2 py-1" style="font-size:.7rem;background:var(--primary-light);color:var(--primary);border-radius:8px;font-weight:700;vertical-align:middle;">{{ $contexto }}</span>
            @endisset
        </h4>
        <p class="text-muted mb-0 mt-1" style="font-size:.85rem;">
            Selecciona una asignación para registrar o consultar la asistencia.
        </p>
    </div>
    @if($schoolYear)
    <span class="badge rounded-pill px-3 py-2" style="background:var(--accent-light);color:#92400e;font-size:.8rem;border:1px solid #fcd34d;">
        <i class="bi bi-calendar2-check me-1"></i>{{ $schoolYear->nombre }}
    </span>
    @endif
</div>

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show"><i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif

{{-- ── Filter bar ───────────────────────────────────────────────────── --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-2 px-3">
        <div class="row g-2 align-items-center">
            <div class="col-md-5">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" id="filtro-busqueda" class="form-control border-start-0 ps-0"
                           placeholder="Buscar asignatura, grupo o docente..."
                           oninput="filtrarAsignaciones(this.value)">
                </div>
            </div>
            <div class="col-auto ms-auto text-muted" style="font-size:.8rem;">
                <i class="bi bi-collection me-1"></i>
                <strong>{{ $asignaciones->count() }}</strong> asignaciones activas
            </div>
        </div>
    </div>
</div>

{{-- ── List grouped by ciclo ──────────────────────────────────────────── --}}
@php
    $porCiclo = $asignaciones->groupBy(function($a) {
        $nivel = optional(optional($a->grupo)->grado)->nivel ?? 0;
        return $nivel <= 3 ? 'Primer Ciclo' : 'Segundo Ciclo';
    });
@endphp

<div id="lista-asignaciones">
@foreach($asignaciones as $a)
    {{-- hidden items for JS search --}}
    <div class="asig-item d-none" data-ciclo="{{ optional(optional($a->grupo)->grado)->nivel <= 3 ? 'primer' : 'segundo' }}"
         data-texto="{{ strtolower(optional($a->asignatura)->nombre . ' ' . optional($a->grupo)->nombre_completo . ' ' . optional($a->docente)->nombre_completo . ' ' . ($a->area ?? '')) }}">
    </div>
@endforeach

@foreach($porCiclo as $cicloLabel => $asigsCiclo)
<div class="mb-4">
    <div class="d-flex align-items-center gap-2 mb-2">
        <span class="badge px-3 py-2" style="font-size:.75rem;border-radius:8px;
            background:{{ $cicloLabel === 'Primer Ciclo' ? '#dbeafe' : '#d1fae5' }};
            color:{{ $cicloLabel === 'Primer Ciclo' ? '#1e40af' : '#065f46' }};">
            <i class="bi bi-mortarboard me-1"></i>{{ $cicloLabel }}
        </span>
        <span class="text-muted" style="font-size:.78rem;">{{ $asigsCiclo->count() }} asignaciones</span>
    </div>

    @foreach($asigsCiclo->sortBy(fn($a) => optional(optional($a->grupo)->grado)->nivel . optional(optional($a->grupo)->seccion)->nombre . optional($a->asignatura)->nombre) as $a)
    <div class="asig-row-card asig-item"
         data-texto="{{ strtolower(optional($a->asignatura)->nombre . ' ' . optional($a->grupo)->nombre_completo . ' ' . optional($a->docente)->nombre_completo . ' ' . ($a->area ?? '')) }}">
        <div class="d-flex flex-wrap align-items-center gap-3">
            {{-- Subject badge --}}
            <div>
                <span class="materia-badge-pill" style="background:{{ $a->asignatura->color ?? 'var(--primary)' }};">
                    {{ optional($a->asignatura)->nombre ?? 'S/A' }}
                </span>
            </div>

            {{-- Info --}}
            <div class="flex-grow-1">
                <div class="d-flex flex-wrap gap-2 align-items-center mb-1">
                    @if($a->area === 'tecnica')
                        <span class="badge" style="font-size:.65rem;border-radius:6px;padding:.2rem .55rem;background:#fef3c7;color:#92400e;">
                            <i class="bi bi-tools me-1"></i>Área Técnica
                        </span>
                    @endif
                    <span class="fw-semibold" style="font-size:.88rem;color:#111827;">
                        <i class="bi bi-people me-1 text-muted"></i>{{ optional($a->grupo)->nombre_completo ?? '—' }}
                    </span>
                </div>
                <div class="d-flex flex-wrap gap-3" style="font-size:.79rem;color:#6b7280;">
                    <span><i class="bi bi-person-badge me-1"></i><strong>Docente:</strong> {{ optional($a->docente)->nombre_completo ?? 'Sin docente' }}</span>
                    @if(optional($a->grupo)->tutor)
                        <span><i class="bi bi-star-fill me-1" style="color:#f59e0b;"></i><strong>Guía:</strong> {{ $a->grupo->tutor->name }}</span>
                    @endif
                    <span><i class="bi bi-people-fill me-1"></i>{{ optional($a->grupo)->matriculas()->activas()->count() ?? 0 }} est.</span>
                </div>
            </div>

            {{-- Stats mini --}}
            @php
                $hoy      = now()->format('Y-m-d');
                $asistHoy = \App\Models\Asistencia::where('asignacion_id', $a->id)->where('fecha', $hoy)->get();
            @endphp
            @if($asistHoy->count() > 0)
            <div class="stats-mini d-flex gap-1 align-items-center">
                <span style="background:#d1fae5;color:#065f46;font-size:.72rem;font-weight:700;padding:.2em .6em;border-radius:12px;">
                    <i class="bi bi-check-circle-fill me-1"></i>Tomada hoy
                </span>
                <span class="stat-p"><i class="bi bi-check2"></i> {{ $asistHoy->where('estado','presente')->count() }}</span>
                <span class="stat-a"><i class="bi bi-x"></i> {{ $asistHoy->where('estado','ausente')->count() }}</span>
                <span class="stat-t"><i class="bi bi-clock"></i> {{ $asistHoy->where('estado','tardanza')->count() }}</span>
            </div>
            @else
            <div class="stats-mini d-flex gap-1 align-items-center">
                <span style="background:#fef3c7;color:#92400e;font-size:.72rem;font-weight:700;padding:.2em .6em;border-radius:12px;">
                    <i class="bi bi-hourglass-split me-1"></i>Pendiente hoy
                </span>
            </div>
            @endif

            {{-- Actions --}}
            <div class="d-flex gap-2">
                <a href="{{ route('admin.asistencia.registrar', $a->id) }}" class="btn btn-primary btn-sm px-3">
                    <i class="bi bi-clipboard-check me-1"></i>Registrar Hoy
                </a>
                <a href="{{ route('admin.asistencia.grilla', $a->id) }}" class="btn btn-outline-primary btn-sm px-3">
                    <i class="bi bi-calendar3-range me-1"></i>Hoja
                </a>
                <a href="{{ route('admin.asistencia.historial', $a->id) }}" class="btn btn-outline-secondary btn-sm px-3">
                    <i class="bi bi-calendar3 me-1"></i>Historial
                </a>
            </div>
        </div>
    </div>
    @endforeach
</div>
@endforeach

@if($asignaciones->isEmpty())
<div class="empty-state-enhanced">
    <div class="empty-illustration"><i class="bi bi-calendar-x"></i></div>
    <div class="empty-title">No hay asignaciones activas</div>
    <div class="empty-desc">No hay asignaciones activas para este año escolar. Verifica la configuración de asignaciones y año escolar.</div>
    <div class="empty-actions">
        <a href="{{ route('admin.asignaciones.index') }}" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-diagram-3 me-1"></i>Ver Asignaciones
        </a>
    </div>
</div>
@endif
</div>

<div id="sin-resultados" class="text-center py-4 text-muted d-none">
    <i class="bi bi-search" style="font-size:2rem;opacity:.3;"></i>
    <p class="mt-2 mb-0">No se encontraron resultados.</p>
</div>

@endsection

@push('scripts')
<script>
function filtrarAsignaciones(q) {
    q = q.toLowerCase().trim();
    let visible = 0;
    document.querySelectorAll('.asig-item').forEach(el => {
        const match = q === '' || (el.dataset.texto || '').includes(q);
        el.style.display = match ? '' : 'none';
        if (match) visible++;
    });
    document.getElementById('sin-resultados').classList.toggle('d-none', visible > 0 || q === '');
}
</script>
@endpush
