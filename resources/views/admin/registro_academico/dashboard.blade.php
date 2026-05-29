@extends('layouts.admin')
@section('page-title', 'Registro Académico')

@push('styles')
<style>
/* ── KPI Cards ────────────────────────────────────────────── */
.kpi-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
    gap: 1rem;
    margin-bottom: 1.5rem;
}
.kpi-card {
    background: #fff;
    border-radius: 14px;
    border: 1px solid #e5e7eb;
    padding: 1.25rem 1.5rem;
    display: flex;
    flex-direction: column;
    gap: .35rem;
    transition: box-shadow .2s, transform .2s;
}
.kpi-card:hover { box-shadow: 0 6px 24px rgba(0,0,0,.08); transform: translateY(-2px); }
.kpi-icon {
    width: 42px; height: 42px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    margin-bottom: .35rem;
}
.kpi-value {
    font-size: 1.9rem;
    font-weight: 800;
    line-height: 1;
    color: var(--primary);
}
.kpi-label {
    font-size: .78rem;
    color: #6b7280;
    font-weight: 500;
}
.kpi-sub {
    font-size: .72rem;
    color: #10b981;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: .2rem;
}
.kpi-sub.warn { color: #f59e0b; }
.kpi-sub.danger { color: #ef4444; }

/* ── Panel cards ──────────────────────────────────────────── */
.panel-card {
    background: #fff;
    border-radius: 14px;
    border: 1px solid #e5e7eb;
    overflow: hidden;
}
.panel-header {
    padding: 1rem 1.25rem .75rem;
    border-bottom: 1px solid #f3f4f6;
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.panel-title {
    font-size: .82rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .08em;
    color: var(--primary);
    display: flex;
    align-items: center;
    gap: .45rem;
}
.panel-body { padding: 0; }

/* ── Quick actions ────────────────────────────────────────── */
.quick-actions {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
    gap: .75rem;
    padding: 1.25rem;
}
.qa-btn {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: .45rem;
    padding: 1rem .75rem;
    border-radius: 12px;
    border: 1.5px solid #e5e7eb;
    background: #f9fafb;
    color: #374151;
    font-size: .78rem;
    font-weight: 600;
    text-decoration: none;
    transition: all .18s;
    text-align: center;
    line-height: 1.3;
}
.qa-btn i { font-size: 1.5rem; }
.qa-btn:hover {
    border-color: var(--primary);
    background: #eff6ff;
    color: var(--primary);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(30,58,110,.1);
}
.qa-btn.featured {
    border-color: var(--secondary);
    background: #fff7ed;
    color: var(--secondary);
}
.qa-btn.featured:hover {
    background: var(--secondary);
    color: #fff;
}

/* ── Estudiantes recientes ────────────────────────────────── */
.est-row {
    display: flex;
    align-items: center;
    gap: .75rem;
    padding: .65rem 1.25rem;
    border-bottom: 1px solid #f3f4f6;
    font-size: .83rem;
    transition: background .12s;
}
.est-row:last-child { border-bottom: none; }
.est-row:hover { background: #f8faff; }
.est-avatar {
    width: 36px; height: 36px;
    border-radius: 50%;
    background: linear-gradient(135deg, #2a4f96, var(--primary));
    color: #fff;
    font-size: .7rem;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.est-avatar img { width: 36px; height: 36px; border-radius: 50%; object-fit: cover; }
.est-name { font-weight: 600; color: #1e293b; }
.est-meta { font-size: .74rem; color: #9ca3af; }
.est-grupo {
    margin-left: auto;
    font-size: .72rem;
    background: #eff6ff;
    color: #1d4ed8;
    border: 1px solid #bfdbfe;
    border-radius: 20px;
    padding: .15rem .55rem;
    white-space: nowrap;
}

/* ── Pre-matrículas ───────────────────────────────────────── */
.pre-row {
    display: flex;
    align-items: center;
    gap: .75rem;
    padding: .65rem 1.25rem;
    border-bottom: 1px solid #f3f4f6;
    font-size: .83rem;
    transition: background .12s;
}
.pre-row:last-child { border-bottom: none; }
.pre-row:hover { background: #fffbeb; }
.badge-pendiente { background: #fef3c7; color: #92400e; border-radius: 20px; padding: .15rem .55rem; font-size: .7rem; font-weight: 700; }
.badge-aprobada  { background: #d1fae5; color: #065f46; border-radius: 20px; padding: .15rem .55rem; font-size: .7rem; font-weight: 700; }
.badge-rechazada { background: #fee2e2; color: #991b1b; border-radius: 20px; padding: .15rem .55rem; font-size: .7rem; font-weight: 700; }

/* ── Distribución ─────────────────────────────────────────── */
.dist-row {
    display: flex;
    align-items: center;
    gap: .75rem;
    padding: .6rem 1.25rem;
    font-size: .83rem;
}
.dist-bar-wrap {
    flex: 1;
    height: 8px;
    background: #e5e7eb;
    border-radius: 4px;
    overflow: hidden;
}
.dist-bar {
    height: 8px;
    border-radius: 4px;
    background: linear-gradient(90deg, var(--primary), #6366f1);
}
.dist-count {
    font-weight: 700;
    color: var(--primary);
    min-width: 30px;
    text-align: right;
}

/* ── Dark mode ────────────────────────────────────────────── */
[data-theme="dark"] .kpi-card { background: #1e293b; border-color: #334155; }
[data-theme="dark"] .kpi-value { color: #93c5fd; }
[data-theme="dark"] .panel-card { background: #1e293b; border-color: #334155; }
[data-theme="dark"] .panel-header { border-color: #334155; }
[data-theme="dark"] .est-row:hover { background: #0f172a; }
[data-theme="dark"] .est-name { color: #e2e8f0; }
[data-theme="dark"] .qa-btn { background: #0f172a; border-color: #334155; color: #94a3b8; }
[data-theme="dark"] .qa-btn:hover { background: #1e3a6e; border-color: #3b82f6; color: #93c5fd; }
</style>
@endpush

@section('content')

{{-- ── Encabezado ──────────────────────────────────────────────────────── --}}
<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
    <div>
        <h1 class="mb-0" style="font-size:1.4rem;font-weight:800;color:var(--primary);">
            <i class="bi bi-journal-bookmark-fill me-2" style="color:var(--secondary);"></i>Departamento de Registro Académico
        </h1>
        <p class="text-muted mb-0" style="font-size:.82rem;">
            @if($schoolYear)
                Año escolar activo: <strong>{{ $schoolYear->nombre }}</strong>
            @else
                <span class="text-warning"><i class="bi bi-exclamation-triangle me-1"></i>No hay año escolar activo</span>
            @endif
        </p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('admin.estudiantes.wizard') }}"
           class="btn btn-sm px-3"
           style="background:var(--secondary);color:#fff;border-radius:8px;font-weight:600;">
            <i class="bi bi-magic me-1"></i>Nuevo Registro
        </a>
        <a href="{{ route('admin.pre-matriculas.index') }}"
           class="btn btn-sm btn-outline-warning px-3"
           style="border-radius:8px;font-weight:600;">
            <i class="bi bi-inbox me-1"></i>Pre-matrículas
            @if($prePendientes > 0)
            <span class="badge bg-warning text-dark ms-1" style="font-size:.65rem;">{{ $prePendientes }}</span>
            @endif
        </a>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success d-flex gap-2 align-items-center mb-3" style="border-radius:10px;font-size:.84rem;">
    <i class="bi bi-check-circle-fill flex-shrink-0"></i>{{ session('success') }}
</div>
@endif

{{-- ── KPIs ─────────────────────────────────────────────────────────────── --}}
<div class="kpi-grid">
    <div class="kpi-card">
        <div class="kpi-icon" style="background:#eff6ff;color:#1d4ed8;">
            <i class="bi bi-people-fill"></i>
        </div>
        <div class="kpi-value">{{ number_format($totalEstudiantes) }}</div>
        <div class="kpi-label">Estudiantes Activos</div>
        <div class="kpi-sub">
            <i class="bi bi-arrow-up-short"></i>+{{ $nuevosEstudiantesEsteMes }} este mes
        </div>
    </div>

    <div class="kpi-card">
        <div class="kpi-icon" style="background:#d1fae5;color:#065f46;">
            <i class="bi bi-card-list"></i>
        </div>
        <div class="kpi-value">{{ number_format($matriculasActivas) }}</div>
        <div class="kpi-label">Matrículas Activas</div>
        <div class="kpi-sub">
            <i class="bi bi-calendar-check"></i>+{{ $matriculasEsteMes }} este mes
        </div>
    </div>

    <div class="kpi-card">
        <div class="kpi-icon" style="background:#fef3c7;color:#92400e;">
            <i class="bi bi-inbox-fill"></i>
        </div>
        <div class="kpi-value {{ $prePendientes > 0 ? 'text-warning' : '' }}">{{ $prePendientes }}</div>
        <div class="kpi-label">Pre-matrículas Pendientes</div>
        @if($prePendientes > 0)
        <div class="kpi-sub warn">
            <i class="bi bi-exclamation-circle"></i>Requieren revisión
        </div>
        @else
        <div class="kpi-sub">
            <i class="bi bi-check-circle"></i>Al día
        </div>
        @endif
    </div>

    <div class="kpi-card">
        <div class="kpi-icon" style="background:#fee2e2;color:#991b1b;">
            <i class="bi bi-person-fill-x"></i>
        </div>
        <div class="kpi-value {{ $sinGrupo > 0 ? '' : '' }}" style="{{ $sinGrupo > 0 ? 'color:#ef4444' : '' }}">{{ $sinGrupo }}</div>
        <div class="kpi-label">Sin Grupo Asignado</div>
        @if($sinGrupo > 0)
        <div class="kpi-sub danger">
            <i class="bi bi-exclamation-triangle"></i>Pendientes de matricular
        </div>
        @else
        <div class="kpi-sub">
            <i class="bi bi-check-circle"></i>Todos asignados
        </div>
        @endif
    </div>
</div>

{{-- ── Acciones rápidas ─────────────────────────────────────────────────── --}}
<div class="panel-card mb-4">
    <div class="panel-header">
        <span class="panel-title"><i class="bi bi-lightning-fill"></i>Acciones Rápidas</span>
    </div>
    <div class="quick-actions">
        <a href="{{ route('admin.estudiantes.wizard') }}" class="qa-btn featured">
            <i class="bi bi-magic"></i>Registro Wizard
        </a>
        <a href="{{ route('admin.estudiantes.create') }}" class="qa-btn">
            <i class="bi bi-person-plus"></i>Nuevo Estudiante
        </a>
        <a href="{{ route('admin.estudiantes.import') }}" class="qa-btn">
            <i class="bi bi-upload"></i>Importar Estudiantes
        </a>
        <a href="{{ route('admin.pre-matriculas.index') }}" class="qa-btn">
            <i class="bi bi-inbox"></i>Pre-matrículas
        </a>
        <a href="{{ route('admin.matriculas.index') }}" class="qa-btn">
            <i class="bi bi-card-list"></i>Matrículas
        </a>
        <a href="{{ route('admin.inscripciones.index') }}" class="qa-btn">
            <i class="bi bi-clipboard-check"></i>Inscripciones
        </a>
        <a href="{{ route('admin.boletines.index') }}" class="qa-btn">
            <i class="bi bi-file-earmark-text"></i>Boletines
        </a>
        <a href="{{ route('admin.registro.index') }}" class="qa-btn">
            <i class="bi bi-table"></i>Registro MINERD
        </a>
        <a href="{{ route('admin.estudiantes.lista-excel') }}" class="qa-btn">
            <i class="bi bi-file-earmark-excel"></i>Excel Estudiantes
        </a>
        <a href="{{ route('admin.estudiantes.lista-pdf') }}" class="qa-btn">
            <i class="bi bi-file-earmark-pdf"></i>PDF Estudiantes
        </a>
    </div>
</div>

<div class="row g-4">

    {{-- ── Estudiantes recientes ────────────────────────────────────────── --}}
    <div class="col-lg-6">
        <div class="panel-card h-100">
            <div class="panel-header">
                <span class="panel-title"><i class="bi bi-person-plus-fill"></i>Últimos Registros</span>
                <a href="{{ route('admin.estudiantes.index') }}" class="btn btn-sm btn-outline-primary" style="border-radius:7px;font-size:.77rem;">
                    Ver todos
                </a>
            </div>
            <div class="panel-body">
                @forelse($estudiantesRecientes as $est)
                @php
                    $matricula = $est->matriculas->first();
                    $iniciales = mb_substr($est->nombres ?? '', 0, 1) . mb_substr($est->apellidos ?? '', 0, 1);
                @endphp
                <div class="est-row">
                    <div class="est-avatar">
                        @if($est->foto)
                            <img src="{{ asset('storage/' . $est->foto) }}" alt="">
                        @else
                            {{ strtoupper($iniciales) }}
                        @endif
                    </div>
                    <div>
                        <div class="est-name">{{ $est->apellidos }}, {{ $est->nombres }}</div>
                        <div class="est-meta">
                            {{ $est->numero_matricula }} · {{ $est->created_at->diffForHumans() }}
                        </div>
                    </div>
                    @if($matricula?->grupo)
                        <span class="est-grupo">
                            {{ $matricula->grupo->grado->nombre ?? '—' }} {{ $matricula->grupo->seccion->nombre ?? '' }}
                        </span>
                    @else
                        <span class="est-grupo" style="background:#fee2e2;color:#991b1b;border-color:#fca5a5;">
                            Sin grupo
                        </span>
                    @endif
                </div>
                @empty
                <div class="text-center py-4 text-muted" style="font-size:.84rem;">
                    <i class="bi bi-person-plus" style="font-size:2rem;opacity:.3;display:block;margin-bottom:.5rem;"></i>
                    No hay estudiantes registrados aún.
                </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- ── Pre-matrículas pendientes + Distribución ────────────────────── --}}
    <div class="col-lg-6 d-flex flex-column gap-4">

        {{-- Pre-matrículas --}}
        <div class="panel-card">
            <div class="panel-header">
                <span class="panel-title"><i class="bi bi-inbox-fill"></i>Pre-matrículas Pendientes</span>
                <a href="{{ route('admin.pre-matriculas.index') }}" class="btn btn-sm btn-outline-warning" style="border-radius:7px;font-size:.77rem;">
                    Ver todas
                </a>
            </div>
            <div class="panel-body">
                @forelse($preMatriculas as $pre)
                <div class="pre-row">
                    <div style="flex:1;">
                        <div style="font-weight:600;color:#1e293b;font-size:.83rem;">{{ $pre->apellidos }}, {{ $pre->nombres }}</div>
                        <div style="font-size:.74rem;color:#9ca3af;">{{ $pre->grado_solicitado }} · {{ $pre->created_at->diffForHumans() }}</div>
                    </div>
                    <span class="badge-{{ $pre->estado }}">{{ ucfirst($pre->estado) }}</span>
                    <a href="{{ route('admin.pre-matriculas.show', $pre) }}"
                       class="btn btn-sm btn-outline-secondary ms-1" style="border-radius:6px;font-size:.72rem;padding:.2rem .55rem;">
                        Ver
                    </a>
                </div>
                @empty
                <div class="text-center py-3 text-muted" style="font-size:.84rem;">
                    <i class="bi bi-check-circle" style="font-size:1.5rem;opacity:.35;display:block;margin-bottom:.35rem;color:#10b981;opacity:1;"></i>
                    No hay pre-matrículas pendientes.
                </div>
                @endforelse
            </div>
        </div>

        {{-- Distribución por grado --}}
        <div class="panel-card">
            <div class="panel-header">
                <span class="panel-title"><i class="bi bi-bar-chart-fill"></i>Distribución por Grado</span>
                @if($schoolYear)
                <span style="font-size:.72rem;color:#9ca3af;">{{ $schoolYear->nombre }}</span>
                @endif
            </div>
            <div class="panel-body" style="padding:.5rem 0;">
                @forelse($porGrado as $g)
                @php $pct = $matriculasActivas > 0 ? round(($g->total / $matriculasActivas) * 100) : 0; @endphp
                <div class="dist-row">
                    <span style="min-width:90px;font-size:.78rem;color:#374151;font-weight:500;">{{ $g->nombre }}</span>
                    <div class="dist-bar-wrap">
                        <div class="dist-bar" style="width:{{ $pct }}%"></div>
                    </div>
                    <span class="dist-count">{{ $g->total }}</span>
                </div>
                @empty
                <div class="text-center py-3 text-muted" style="font-size:.84rem;">Sin datos de matrícula.</div>
                @endforelse

                @if($sinGrupo > 0)
                <div class="dist-row">
                    <span style="min-width:90px;font-size:.78rem;color:#ef4444;font-weight:500;">Sin grupo</span>
                    <div class="dist-bar-wrap">
                        <div class="dist-bar" style="width:{{ $matriculasActivas > 0 ? round(($sinGrupo / ($matriculasActivas + $sinGrupo)) * 100) : 100 }}%;background:linear-gradient(90deg,#ef4444,#f87171);"></div>
                    </div>
                    <span class="dist-count" style="color:#ef4444;">{{ $sinGrupo }}</span>
                </div>
                @endif
            </div>
        </div>

    </div>
</div>

@endsection
