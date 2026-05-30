@extends('layouts.admin')
@section('page-title', 'Registro Académico')

@push('styles')
<style>
    .ciclo-badge { display:inline-flex; align-items:center; gap:.35rem;
        padding:.25rem .75rem; border-radius:20px; font-size:.72rem; font-weight:700; }
    .ciclo-primer  { background:#dbeafe; color:#1e40af; }
    .ciclo-segundo { background:#ede9fe; color:#5b21b6; }
    .grupo-card { background:#fff; border-radius:14px; border:1px solid #e5e7eb;
        padding:1.25rem 1.5rem; display:flex; align-items:center;
        justify-content:space-between; transition:box-shadow .2s;
        text-decoration:none; color:inherit; }
    .grupo-card:hover { box-shadow:0 4px 18px rgba(0,0,0,.1); border-color:var(--primary); color:inherit; }
    .grupo-nombre { font-size:1.15rem; font-weight:800; color:#111827; }
    .grupo-meta { font-size:.8rem; color:#6b7280; margin-top:.15rem; }
    .filter-tab { border:none; background:none; padding:.45rem 1rem; border-radius:8px;
        font-size:.85rem; font-weight:600; color:#6b7280; cursor:pointer; transition:.15s; }
    .filter-tab.active { background:var(--primary); color:#fff; }

    /* Panel de períodos */
    .per-panel { background:#fff; border:1px solid #e5e7eb; border-radius:14px;
        overflow:hidden; margin-bottom:1.5rem; box-shadow:0 1px 6px rgba(0,0,0,.04); }
    .per-panel-hd { padding:.65rem 1.1rem; border-bottom:1px solid #f1f5f9;
        background:linear-gradient(90deg,rgba(30,58,110,.05) 0%,transparent 100%);
        display:flex; align-items:center; gap:.5rem; }
    .per-row { display:flex; align-items:center; gap:.75rem; padding:.6rem 1.1rem;
        border-bottom:1px solid #f1f5f9; flex-wrap:wrap; }
    .per-row:last-child { border-bottom:none; }
    .per-nombre { font-weight:700; font-size:.88rem; color:#111827; min-width:140px; }
    .per-fechas { font-size:.75rem; color:#6b7280; flex:1; }
    .badge-activo  { background:#d1fae5; color:#065f46; border-radius:20px;
        padding:.2rem .65rem; font-size:.72rem; font-weight:700; white-space:nowrap; }
    .badge-cerrado { background:#fee2e2; color:#991b1b; border-radius:20px;
        padding:.2rem .65rem; font-size:.72rem; font-weight:700; white-space:nowrap; }
    .badge-pendiente { background:#f1f5f9; color:#64748b; border-radius:20px;
        padding:.2rem .65rem; font-size:.72rem; font-weight:700; white-space:nowrap; }

    [data-theme="dark"] .ciclo-primer { background: #0c1f3f; color: #93c5fd; }
    [data-theme="dark"] .ciclo-segundo { background: #2e1065; color: #c4b5fd; }
    [data-theme="dark"] .grupo-card { background: #1e293b; border-color: #334155; color: #e2e8f0; }
    [data-theme="dark"] .grupo-card:hover { border-color: var(--primary); }
    [data-theme="dark"] .grupo-nombre { color: #e2e8f0; }
    [data-theme="dark"] .grupo-meta { color: #94a3b8; }
    [data-theme="dark"] .filter-tab { color: #94a3b8; }
    [data-theme="dark"] .per-panel { background:#1e293b; border-color:#334155; }
    [data-theme="dark"] .per-panel-hd { border-color:#334155; }
    [data-theme="dark"] .per-row { border-color:#334155; }
    [data-theme="dark"] .per-nombre { color:#e2e8f0; }
    [data-theme="dark"] .per-fechas { color:#94a3b8; }
</style>
@endpush

@section('content')
<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
    <div>
        <h1 style="font-size:1.45rem;font-weight:800;color:var(--primary);margin:0;">
            <i class="bi bi-journal-bookmark-fill me-2"></i>Registro Académico MINERD
        </h1>
        <p class="text-muted small mb-0">{{ $schoolYear->nombre }} — Selecciona un grupo para ingresar o revisar el registro</p>
    </div>
    <a href="{{ route('admin.competencias.index') }}" class="btn btn-outline-primary btn-sm">
        <i class="bi bi-diagram-3 me-1"></i>Configurar CE / IL
    </a>
</div>

{{-- ── Flash messages ── --}}
@if(session('success'))
<div class="alert alert-success d-flex gap-2 align-items-center mb-3" style="border-radius:10px;font-size:.84rem;">
    <i class="bi bi-check-circle-fill flex-shrink-0"></i>{{ session('success') }}
</div>
@endif
@if(session('error'))
<div class="alert alert-danger d-flex gap-2 align-items-center mb-3" style="border-radius:10px;font-size:.84rem;">
    <i class="bi bi-exclamation-circle-fill flex-shrink-0"></i>{{ session('error') }}
</div>
@endif

{{-- ── Panel de Períodos ──────────────────────────────────────────── --}}
@if($periodos->isNotEmpty())
<div class="per-panel">
    <div class="per-panel-hd">
        <i class="bi bi-calendar-range-fill" style="color:var(--primary);"></i>
        <span style="font-weight:800;font-size:.82rem;color:var(--primary);">Estado de Períodos</span>
        <span style="font-size:.75rem;color:#6b7280;margin-left:.25rem;">— {{ $schoolYear->nombre }}</span>
    </div>

    @foreach($periodos as $p)
    <div class="per-row">
        {{-- Nombre --}}
        <div class="per-nombre">
            <i class="bi bi-circle-fill me-1"
               style="font-size:.45rem;color:{{ $p->activo ? '#10b981' : ($p->cerrado ? '#ef4444' : '#94a3b8') }};vertical-align:middle;"></i>
            {{ $p->nombre }}
        </div>

        {{-- Fechas --}}
        <div class="per-fechas">
            @if($p->fecha_inicio && $p->fecha_fin)
                {{ $p->fecha_inicio->format('d/m/Y') }} → {{ $p->fecha_fin->format('d/m/Y') }}
            @else
                <span style="color:#d1d5db;">Sin fechas</span>
            @endif
        </div>

        {{-- Badge de estado --}}
        @if($p->activo && !$p->cerrado)
            <span class="badge-activo"><i class="bi bi-play-circle-fill me-1"></i>Activo</span>
        @elseif($p->cerrado)
            <span class="badge-cerrado"><i class="bi bi-lock-fill me-1"></i>Cerrado</span>
        @else
            <span class="badge-pendiente"><i class="bi bi-clock me-1"></i>Pendiente</span>
        @endif

        {{-- Acciones --}}
        <div class="d-flex gap-2 ms-auto flex-wrap">
            @if($p->activo && !$p->cerrado)
                {{-- Va al checklist que tiene la validación y el botón de cierre --}}
                <a href="{{ route('admin.periodos.checklist', $p) }}"
                   class="btn btn-sm btn-outline-primary"
                   style="border-radius:7px;font-size:.75rem;padding:.25rem .65rem;">
                    <i class="bi bi-list-check me-1"></i>Checklist
                </a>
                <a href="{{ route('admin.periodos.checklist', $p) }}"
                   class="btn btn-sm btn-danger"
                   style="border-radius:7px;font-size:.75rem;padding:.25rem .65rem;">
                    <i class="bi bi-lock-fill me-1"></i>Cerrar período
                </a>
            @elseif($p->cerrado)
                {{-- Reabrir --}}
                <form method="POST" action="{{ route('admin.periodos.reabrir', $p) }}"
                      onsubmit="return confirm('¿Reabrir el {{ $p->nombre }}?\n\nEste período volverá a ser el activo y se podrán editar notas.')">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-outline-warning"
                            style="border-radius:7px;font-size:.75rem;padding:.25rem .65rem;">
                        <i class="bi bi-unlock-fill me-1"></i>Reabrir
                    </button>
                </form>
            @else
                {{-- Activar manualmente --}}
                <span style="font-size:.72rem;color:#94a3b8;">Esperando activación automática</span>
            @endif
        </div>
    </div>
    @endforeach
</div>
@endif

{{-- Filtros de ciclo --}}
<div class="mb-4 d-flex gap-2 flex-wrap">
    <button class="filter-tab {{ !$cicloFiltro ? 'active' : '' }}"
            onclick="location.href='{{ route('admin.registro.index') }}'">
        Todos los grados
    </button>
    <button class="filter-tab {{ $cicloFiltro === 'primer_ciclo' ? 'active' : '' }}"
            onclick="location.href='{{ route('admin.registro.index', ['ciclo'=>'primer_ciclo']) }}'">
        <i class="bi bi-1-circle me-1"></i>Primer Ciclo (1ro–3ro)
    </button>
    <button class="filter-tab {{ $cicloFiltro === 'segundo_ciclo' ? 'active' : '' }}"
            onclick="location.href='{{ route('admin.registro.index', ['ciclo'=>'segundo_ciclo']) }}'">
        <i class="bi bi-2-circle me-1"></i>Segundo Ciclo (4to–6to)
    </button>
</div>

<div class="row g-3">
    @forelse($grupos as $grupo)
        @php
            $ciclo     = $grupo->grado->ciclo ?? 'primer_ciclo';
            $matCount  = $grupo->asignaciones()->where('activo',true)->count();
            $estCount  = $grupo->matriculas()->where('estado','activa')->count();
        @endphp
        <div class="col-lg-4 col-md-6">
            <a href="{{ route('admin.registro.show', $grupo) }}" class="grupo-card d-block">
                <div class="d-flex align-items-center gap-3">
                    <div style="width:52px;height:52px;background:var(--primary-pale, #eef3fb);
                                border-radius:12px;display:flex;align-items:center;justify-content:center;">
                        <i class="bi bi-journal-text" style="color:var(--primary);font-size:1.4rem;"></i>
                    </div>
                    <div>
                        <div class="grupo-nombre">
                            {{ $grupo->grado->nombre }} — Sección {{ $grupo->seccion->nombre }}
                        </div>
                        <div class="grupo-meta">
                            <span class="ciclo-badge {{ $ciclo === 'primer_ciclo' ? 'ciclo-primer' : 'ciclo-segundo' }}">
                                {{ $ciclo === 'primer_ciclo' ? 'Primer Ciclo' : 'Segundo Ciclo' }}
                            </span>
                            &nbsp;·&nbsp; {{ $estCount }} estudiantes &nbsp;·&nbsp; {{ $matCount }} materias
                        </div>
                    </div>
                </div>
                <i class="bi bi-chevron-right text-muted"></i>
            </a>
        </div>
    @empty
        <div class="col-12">
            <div class="text-center py-5 text-muted">
                <i class="bi bi-folder2-open d-block mb-2" style="font-size:2rem;"></i>
                No hay grupos para el año escolar activo.
            </div>
        </div>
    @endforelse
</div>
@endsection
