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

    [data-theme="dark"] .ciclo-primer { background: #0c1f3f; color: #93c5fd; }
    [data-theme="dark"] .ciclo-segundo { background: #2e1065; color: #c4b5fd; }
    [data-theme="dark"] .grupo-card { background: #1e293b; border-color: #334155; color: #e2e8f0; }
    [data-theme="dark"] .grupo-card:hover { border-color: var(--primary); }
    [data-theme="dark"] .grupo-nombre { color: #e2e8f0; }
    [data-theme="dark"] .grupo-meta { color: #94a3b8; }
    [data-theme="dark"] .filter-tab { color: #94a3b8; }
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
