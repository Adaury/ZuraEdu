@extends('layouts.admin')

@section('page-title', 'Grupos / Cursos')

@push('styles')
<style>
    .grupo-card {
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        background: #fff;
        transition: box-shadow .2s, transform .2s;
        overflow: hidden;
    }
    .grupo-card:hover {
        box-shadow: 0 6px 24px rgba(30,58,110,.12);
        transform: translateY(-2px);
    }
    .grupo-card-header {
        background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
        color: #fff;
        padding: 1rem 1.1rem .8rem;
        position: relative;
    }
    .grupo-card-header.inactive {
        background: linear-gradient(135deg, #6b7280 0%, #9ca3af 100%);
    }
    .grupo-nombre {
        font-size: 1.45rem;
        font-weight: 800;
        letter-spacing: .02em;
        line-height: 1;
    }
    .grupo-year {
        font-size: .7rem;
        opacity: .75;
        letter-spacing: .06em;
        text-transform: uppercase;
        margin-top: .2rem;
    }
    .grupo-status-badge {
        position: absolute;
        top: .75rem;
        right: .75rem;
        font-size: .65rem;
        font-weight: 700;
        padding: .2rem .55rem;
        border-radius: 20px;
        letter-spacing: .04em;
        text-transform: uppercase;
    }
    .grupo-card-body {
        padding: .85rem 1.1rem;
    }
    .capacity-bar {
        height: 6px;
        border-radius: 3px;
        background: #e5e7eb;
        overflow: hidden;
        margin-top: .3rem;
    }
    .capacity-fill {
        height: 100%;
        border-radius: 3px;
        background: linear-gradient(90deg, #10b981, #059669);
        transition: width .4s ease;
    }
    .capacity-fill.warning { background: linear-gradient(90deg, #f59e0b, #d97706); }
    .capacity-fill.danger  { background: linear-gradient(90deg, #ef4444, #dc2626); }
    .grado-section-title {
        font-size: .7rem;
        font-weight: 700;
        letter-spacing: .12em;
        text-transform: uppercase;
        color: var(--primary);
        border-bottom: 2px solid var(--primary);
        padding-bottom: .35rem;
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        gap: .5rem;
    }
    .grado-section-title .grado-badge {
        background: var(--primary);
        color: #fff;
        border-radius: 6px;
        padding: .15rem .55rem;
        font-size: .7rem;
    }
    .btn-action {
        padding: .25rem .5rem;
        font-size: .75rem;
        border-radius: 6px;
        line-height: 1.4;
    }
    .empty-state {
        text-align: center;
        padding: 4rem 2rem;
        color: #9ca3af;
    }
    .empty-state i { font-size: 3rem; margin-bottom: 1rem; display: block; }

    [data-theme="dark"] .grupo-card { background: #1e293b; border-color: #334155; }
</style>
@endpush

@section('content')

{{-- Page Header --}}
<div class="d-flex align-items-center justify-content-between mb-4 p-slide-up">
    <div>
        <h1 class="h4 fw-bold mb-0" style="color:var(--primary);">
            <i class="bi bi-grid-3x3-gap me-2"></i>Grupos / Cursos
        </h1>
        <p class="text-muted mb-0 mt-1" style="font-size:.82rem;">
            @if($schoolYear)
                Año escolar: <strong>{{ $schoolYear->nombre }}</strong> &mdash;
            @endif
            {{ $grupos->count() }} {{ Str::plural('grupo', $grupos->count()) }} registrado{{ $grupos->count() !== 1 ? 's' : '' }}
        </p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.grupos.lista-pdf-general') }}" target="_blank" class="btn btn-danger btn-sm">
            <i class="bi bi-file-earmark-pdf-fill me-1"></i>PDF
        </a>
        <a href="{{ route('admin.grupos.lista-excel-general') }}" class="btn btn-success btn-sm">
            <i class="bi bi-file-earmark-excel-fill me-1"></i>Excel
        </a>
        <button type="button" class="btn btn-sm fw-semibold" data-bs-toggle="modal" data-bs-target="#modalSecciones"
                style="background:#f0f4f8;color:var(--primary);border:1px solid #e5e7eb;border-radius:8px;padding:.45rem 1rem;">
            <i class="bi bi-tag me-1"></i>Secciones
            <span class="badge ms-1" style="background:var(--primary);font-size:.65rem;">{{ $secciones->count() }}</span>
        </button>
        <a href="{{ route('admin.grupos.create') }}" class="btn btn-sm fw-semibold" style="background:var(--primary);color:#fff;border-radius:8px;padding:.45rem 1rem;">
            <i class="bi bi-plus-lg me-1"></i>Nuevo Grupo
        </a>
    </div>
</div>

{{-- Session alerts --}}
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm" role="alert" style="border-radius:10px;">
        <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif
@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm" role="alert" style="border-radius:10px;">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if($grupos->isEmpty())
    <div class="card border-0 shadow-sm" style="border-radius:12px;">
        <div class="card-body empty-state">
            <i class="bi bi-grid-3x3-gap" style="color:#d1d5db;"></i>
            <h5 class="fw-semibold mb-1" style="color:#6b7280;">No hay grupos registrados</h5>
            <p class="mb-3" style="font-size:.85rem;">Crea el primer grupo para el año escolar actual.</p>
            <a href="{{ route('admin.grupos.create') }}" class="btn btn-sm fw-semibold" style="background:var(--primary);color:#fff;border-radius:8px;">
                <i class="bi bi-plus-lg me-1"></i>Crear primer grupo
            </a>
        </div>
    </div>
@else
    {{-- Group cards organized by grado --}}
    @php
        $gruposPorGrado = $grupos->groupBy(fn($g) => $g->grado_id);
    @endphp

    @foreach($gruposPorGrado as $gradoId => $gruposDelGrado)
        @php $grado = $gruposDelGrado->first()->grado; @endphp

        <div class="mb-4 p-slide-up p-delay-1">
            <div class="grado-section-title">
                <span class="grado-badge">{{ $grado->nivel }}°</span>
                {{ $grado->nombre }}
                <span class="text-muted fw-normal ms-1" style="font-size:.68rem;">
                    ({{ $gruposDelGrado->count() }} {{ Str::plural('sección', $gruposDelGrado->count()) }})
                </span>
            </div>

            <div class="row g-3">
                @foreach($gruposDelGrado as $grupo)
                    @php
                        $pct = $grupo->capacidad ? round(($grupo->matriculas_count / $grupo->capacidad) * 100) : 0;
                        $fillClass = $pct >= 90 ? 'danger' : ($pct >= 70 ? 'warning' : '');

                        $niveles = [1=>'1ro',2=>'2do',3=>'3ro',4=>'4to',5=>'5to',6=>'6to'];
                        $prefijo = $niveles[$grado->nivel] ?? $grado->nivel.'mo';
                        $nombreCorto = $prefijo . ' ' . $grupo->seccion->nombre;
                    @endphp

                    <div class="col-12 col-sm-6 col-md-4 col-xl-3">
                        <div class="grupo-card h-100">
                            <div class="grupo-card-header {{ $grupo->activo ? '' : 'inactive' }}">
                                <span class="grupo-status-badge {{ $grupo->activo ? 'bg-success bg-opacity-25 text-white border border-white border-opacity-25' : 'bg-white bg-opacity-20 text-white' }}">
                                    {{ $grupo->activo ? 'Activo' : 'Inactivo' }}
                                </span>
                                <div class="grupo-nombre">{{ $nombreCorto }}</div>
                                <div class="grupo-year">
                                    {{ $grupo->schoolYear->nombre ?? '—' }}
                                    @if($grupo->aula)
                                        &middot; Aula {{ $grupo->aula }}
                                    @endif
                                </div>
                            </div>

                            <div class="grupo-card-body">
                                {{-- Tutor --}}
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <div style="width:28px;height:28px;background:var(--primary);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.65rem;font-weight:800;color:#fff;flex-shrink:0;">
                                        {{ strtoupper(substr($grupo->tutor->name ?? 'S', 0, 1)) }}
                                    </div>
                                    <div>
                                        <div style="font-size:.75rem;font-weight:600;color:#1e293b;line-height:1.2;">
                                            {{ $grupo->tutor->name ?? 'Sin tutor asignado' }}
                                        </div>
                                        <div style="font-size:.65rem;color:#9ca3af;">Tutor</div>
                                    </div>
                                </div>

                                {{-- Capacity --}}
                                <div class="mt-2">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span style="font-size:.72rem;color:#6b7280;font-weight:500;">
                                            <i class="bi bi-people-fill me-1" style="color:var(--primary);"></i>Estudiantes
                                        </span>
                                        <span style="font-size:.78rem;font-weight:700;color:#1e293b;">
                                            {{ $grupo->matriculas_count }}
                                            @if($grupo->capacidad)
                                                <span style="color:#9ca3af;font-weight:400;">/ {{ $grupo->capacidad }}</span>
                                            @endif
                                        </span>
                                    </div>
                                    @if($grupo->capacidad)
                                        <div class="capacity-bar">
                                            <div class="capacity-fill {{ $fillClass }}" style="width:{{ min($pct, 100) }}%;"></div>
                                        </div>
                                        <div style="font-size:.64rem;color:#9ca3af;margin-top:.2rem;text-align:right;">
                                            {{ $pct }}% ocupado
                                        </div>
                                    @endif
                                </div>

                                {{-- Actions --}}
                                <div class="d-flex gap-1 mt-3 pt-2 border-top">
                                    <a href="{{ route('admin.grupos.show', $grupo) }}"
                                       class="btn btn-action flex-fill fw-semibold"
                                       style="background:var(--primary);color:#fff;border:1px solid var(--primary);">
                                        <i class="bi bi-eye-fill"></i> Ver
                                    </a>
                                    <a href="{{ route('admin.grupos.edit', $grupo) }}"
                                       class="btn btn-action flex-fill"
                                       style="background:#f0f4f8;color:var(--primary);font-weight:600;border:1px solid #e5e7eb;">
                                        <i class="bi bi-pencil-fill"></i> Editar
                                    </a>
                                    <form action="{{ route('admin.grupos.destroy', $grupo) }}" method="POST"
                                          onsubmit="return confirm('¿Eliminar el grupo {{ $nombreCorto }}? Esta acción no se puede deshacer.')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-action"
                                                style="background:#fff0f0;color:var(--secondary);border:1px solid #fecaca;"
                                                title="Eliminar">
                                            <i class="bi bi-trash3"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endforeach
@endif

{{-- ── Modal: Gestión de Secciones ──────────────────────────────────────── --}}
<div class="modal fade" id="modalSecciones" tabindex="-1" aria-labelledby="modalSeccionesLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-md">
        <div class="modal-content border-0 shadow" style="border-radius:14px;overflow:hidden;">
            <div class="modal-header border-0 pb-0" style="background:linear-gradient(135deg,var(--primary) 0%,var(--primary-light) 100%);padding:1.2rem 1.4rem;">
                <h5 class="modal-title fw-bold text-white mb-0" id="modalSeccionesLabel">
                    <i class="bi bi-tag me-2"></i>Gestionar Secciones
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body px-4 py-3">

                {{-- Add new section form --}}
                <form action="{{ route('admin.secciones.store') }}" method="POST" class="d-flex gap-2 mb-3">
                    @csrf
                    <input type="text" name="nombre" class="form-control form-control-sm"
                           placeholder="Ej: F" maxlength="10" required
                           style="border-radius:8px;font-weight:600;text-transform:uppercase;"
                           oninput="this.value=this.value.toUpperCase()">
                    <button type="submit" class="btn btn-sm fw-semibold flex-shrink-0"
                            style="background:var(--primary);color:#fff;border-radius:8px;white-space:nowrap;">
                        <i class="bi bi-plus-lg me-1"></i>Agregar
                    </button>
                </form>

                {{-- Section list --}}
                <div style="max-height:320px;overflow-y:auto;">
                    @forelse($secciones as $sec)
                        <div class="d-flex align-items-center gap-2 mb-2 p-2"
                             style="background:#f8fafc;border-radius:10px;border:1px solid #e5e7eb;">

                            {{-- Inline rename form --}}
                            <form action="{{ route('admin.secciones.update', $sec) }}" method="POST"
                                  class="d-flex gap-2 flex-grow-1 align-items-center">
                                @csrf @method('PUT')
                                <span style="width:32px;height:32px;background:var(--primary);border-radius:8px;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:800;font-size:.85rem;flex-shrink:0;">
                                    {{ $sec->nombre }}
                                </span>
                                <input type="text" name="nombre" value="{{ $sec->nombre }}"
                                       class="form-control form-control-sm"
                                       maxlength="10" required
                                       style="border-radius:6px;font-weight:600;text-transform:uppercase;max-width:90px;"
                                       oninput="this.value=this.value.toUpperCase()">
                                <span class="text-muted" style="font-size:.72rem;white-space:nowrap;">
                                    {{ $sec->grupos_count }} {{ Str::plural('grupo', $sec->grupos_count) }}
                                </span>
                                <button type="submit" class="btn btn-sm ms-auto"
                                        style="background:#e0edff;color:var(--primary);border-radius:6px;font-size:.75rem;padding:.2rem .55rem;"
                                        title="Guardar nombre">
                                    <i class="bi bi-check-lg"></i>
                                </button>
                            </form>

                            {{-- Delete button --}}
                            <form action="{{ route('admin.secciones.destroy', $sec) }}" method="POST"
                                  onsubmit="return confirm('¿Eliminar la sección {{ $sec->nombre }}?{{ $sec->grupos_count > 0 ? ' Tiene '.$sec->grupos_count.' grupo(s) asociado(s).' : '' }}')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm"
                                        style="background:#fff0f0;color:#ef4444;border-radius:6px;font-size:.75rem;padding:.2rem .5rem;"
                                        title="Eliminar sección"
                                        {{ $sec->grupos_count > 0 ? 'disabled' : '' }}>
                                    <i class="bi bi-trash3"></i>
                                </button>
                            </form>
                        </div>
                    @empty
                        <p class="text-muted text-center mb-0" style="font-size:.85rem;">
                            No hay secciones. Agrega la primera arriba.
                        </p>
                    @endforelse
                </div>

                @if($secciones->count() > 0)
                    <p class="text-muted mt-2 mb-0" style="font-size:.72rem;">
                        <i class="bi bi-info-circle me-1"></i>
                        Solo se pueden eliminar secciones sin grupos asociados.
                    </p>
                @endif
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
// Re-open modal after redirect if there was a seccion-related flash
@if(session('success') || session('error'))
    // Keep modal open if user was interacting with sections
    const hash = window.location.hash;
    if (document.referrer.includes('grupos') && !document.referrer.includes('grupos/')) {
        const modal = new bootstrap.Modal(document.getElementById('modalSecciones'));
        modal.show();
    }
@endif
</script>
@endpush

@endsection
