@extends('layouts.admin')
@section('page-title', 'Boletines')

@push('styles')
<style>
    .grupo-card-boletin {
        cursor: pointer;
        border: 2px solid #e5e7eb;
        border-radius: 10px;
        transition: border-color .15s, box-shadow .15s, transform .12s;
        text-decoration: none;
        color: inherit;
        display: flex;
        align-items: center;
        gap: .75rem;
    }
    .grupo-card-boletin:hover {
        border-color: var(--primary-light, #2a4f96);
        box-shadow: 0 4px 14px rgba(30,58,110,.12);
        transform: translateY(-1px);
        color: inherit;
        text-decoration: none;
    }
    .grupo-icon-box {
        width: 44px; height: 44px;
        background: var(--primary);
        border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        color: #fff; font-size: 1.1rem; flex-shrink: 0;
    }
    .periodo-selector .periodo-btn {
        border: 2px solid #e5e7eb;
        background: #fff;
        border-radius: 8px;
        padding: .45rem .9rem;
        font-size: .82rem;
        font-weight: 600;
        cursor: pointer;
        transition: all .15s;
        color: #4b5563;
    }
    .periodo-selector .periodo-btn:hover { border-color: var(--primary); color: var(--primary); }
    .periodo-selector .periodo-btn.active {
        border-color: var(--primary); background: var(--primary); color: #fff;
    }

    [data-theme="dark"] .grupo-card-boletin { border-color: #334155; color: var(--dm-text, #e2e8f0); }
    [data-theme="dark"] .grupo-card-boletin:hover { border-color: #4b6a9e; }
    [data-theme="dark"] .periodo-selector .periodo-btn { background: #1e293b; border-color: #334155; color: #94a3b8; }
    [data-theme="dark"] .periodo-selector .periodo-btn:hover { border-color: var(--primary); color: #93c5fd; }
</style>
@endpush

@section('content')

<x-breadcrumb :items="[
    ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
    ['label' => 'Boletines'],
]" />

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold mb-0" style="color:var(--primary)">
            <i class="bi bi-file-earmark-text me-2"></i>Boletines de Calificaciones
        </h4>
        <p class="text-muted mb-0 mt-1" style="font-size:.85rem;">
            {{ $cicloLabel ?? 'Todos los Ciclos' }} — selecciona un período y luego el grupo.
        </p>
    </div>
    <div class="d-flex flex-column align-items-end gap-2">
        @if($schoolYear)
        <span class="badge rounded-pill px-3 py-2" style="background:var(--accent-light);color:#92400e;font-size:.8rem;border:1px solid #fcd34d;">
            <i class="bi bi-calendar2-check me-1"></i>{{ $schoolYear->nombre }}
        </span>
        @endif
        @if(isset($esDocente) && $esDocente)
        <span class="badge rounded-pill px-3 py-2" style="background:#dbeafe;color:#1d4ed8;font-size:.78rem;border:1px solid #93c5fd;">
            <i class="bi bi-eye-slash me-1"></i>Vista Docente — solo tus grupos
        </span>
        @endif
    </div>
</div>

@if(isset($esDocente) && $esDocente)
<div class="alert alert-info alert-dismissible fade show mb-3" style="border-radius:10px;font-size:.85rem;">
    <i class="bi bi-info-circle me-2"></i>
    Como <strong>Docente</strong>, puedes ver los boletines de los grupos que tienes asignados.
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show"><i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif

{{-- ── Selector de período ────────────────────────────────────────────────── --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body py-3 px-4">
        <div class="d-flex align-items-center gap-3 flex-wrap">
            <span class="fw-semibold text-muted" style="font-size:.85rem;white-space:nowrap;">
                <i class="bi bi-calendar3 me-1"></i>Período:
            </span>
            <div class="periodo-selector d-flex flex-wrap gap-2" id="periodo-selector">
                @foreach($periodos as $p)
                <button class="periodo-btn {{ $loop->first ? 'active' : '' }}"
                        data-periodo-id="{{ $p->id }}">
                    {{ $p->nombre }}
                </button>
                @endforeach
            </div>
        </div>
    </div>
</div>

{{-- ── Grupos ─────────────────────────────────────────────────────────────── --}}
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between">
        <h6 class="mb-0 fw-bold">
            <i class="bi bi-grid-3x3-gap me-2 text-primary"></i>
            {{ $cicloLabel ?? 'Grupos' }}
            <span class="badge ms-2" style="background:#e0e7ff;color:#1e3a6e;font-size:.7rem;font-weight:700;">
                {{ $grupos->count() }} {{ $grupos->count() === 1 ? 'grupo' : 'grupos' }}
            </span>
        </h6>
        <div class="input-group input-group-sm" style="max-width:220px;">
            <span class="input-group-text bg-white border-end-0 pe-1">
                <i class="bi bi-search text-muted" style="font-size:.78rem;"></i>
            </span>
            <input type="text"
                   id="filtro-grupos-bol"
                   class="form-control border-start-0 ps-1"
                   placeholder="Buscar grupo..."
                   autocomplete="off"
                   style="font-size:.81rem;">
        </div>
    </div>
    <div class="card-body p-3">
        <div class="row g-3" id="lista-grupos">
            @forelse($grupos as $grupo)
            <div class="col-md-6 col-lg-4 grupo-item"
                 data-nombre="{{ strtolower($grupo->nombre_completo) }}">
                <a class="grupo-card-boletin p-3"
                   href="#"
                   data-grupo-id="{{ $grupo->id }}"
                   data-base-url="{{ route('admin.boletines.grupo') }}">
                    <div class="grupo-icon-box"><i class="bi bi-people-fill"></i></div>
                    <div class="flex-grow-1 min-w-0">
                        <div class="fw-semibold" style="font-size:.9rem;">{{ $grupo->nombre_completo }}</div>
                        <div class="text-muted" style="font-size:.77rem;">
                            <i class="bi bi-people me-1"></i>
                            {{ $grupo->matriculas()->activas()->where('school_year_id', $schoolYear->id)->count() }} estudiantes
                        </div>
                    </div>
                    <i class="bi bi-chevron-right text-muted" style="font-size:.8rem;"></i>
                </a>
            </div>
            @empty
            <div class="col-12">
                <div class="empty-state-enhanced" style="padding:2rem 1rem;">
                    <div class="empty-illustration"><i class="bi bi-grid-3x3-gap"></i></div>
                    <div class="empty-title">Sin grupos en este ciclo</div>
                    <div class="empty-desc">
                        No hay grupos de <strong>{{ $cicloLabel ?? 'este ciclo' }}</strong>
                        registrados para el año escolar {{ $schoolYear->nombre }}.
                    </div>
                    <div class="empty-actions">
                        <a href="{{ route('admin.grupos.index') }}" class="btn btn-outline-primary btn-sm">
                            <i class="bi bi-plus me-1"></i>Gestionar Grupos
                        </a>
                    </div>
                </div>
            </div>
            @endforelse
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
(function () {
    var periodoId = {{ optional($periodos->first())->id ?? 0 }};

    // ── Selector de período ───────────────────────────────────────────────────
    document.querySelectorAll('.periodo-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.querySelectorAll('.periodo-btn').forEach(function (b) {
                b.classList.remove('active');
            });
            btn.classList.add('active');
            periodoId = btn.dataset.periodoId;
        });
    });

    // ── Clic en tarjeta de grupo → navegar a boletines del grupo ─────────────
    document.querySelectorAll('.grupo-card-boletin').forEach(function (card) {
        card.addEventListener('click', function (e) {
            e.preventDefault();
            if (!periodoId) {
                alert('Selecciona un período primero.');
                return;
            }
            var url = card.dataset.baseUrl
                    + '?grupo_id=' + card.dataset.grupoId
                    + '&periodo_id=' + periodoId;
            window.location.href = url;
        });
    });

    // ── Filtro de búsqueda de grupos ──────────────────────────────────────────
    var inp = document.getElementById('filtro-grupos-bol');
    if (inp) {
        inp.addEventListener('input', function () {
            var q = this.value.toLowerCase().trim();
            document.querySelectorAll('.grupo-item').forEach(function (el) {
                el.style.display = (!q || el.dataset.nombre.includes(q)) ? '' : 'none';
            });
        });
    }
})();
</script>
@endpush
