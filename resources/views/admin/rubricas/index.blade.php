@extends('layouts.admin')
@section('page-title', 'Rúbricas de Evaluación')

@section('content')
<div class="container-fluid px-4">

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Rúbricas de Evaluación</h1>
            <nav aria-label="breadcrumb"><ol class="breadcrumb mb-0 small">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Inicio</a></li>
                <li class="breadcrumb-item active">Rúbricas</li>
            </ol></nav>
        </div>
    </div>

    {{-- KPIs --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div style="width:44px;height:44px;background:linear-gradient(135deg,#1e40af,#3b82f6);border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i class="bi bi-grid-3x3-gap-fill text-white" style="font-size:1.1rem;"></i>
                    </div>
                    <div>
                        <div style="font-size:1.6rem;font-weight:900;color:#1e3a6e;line-height:1.1;">{{ $totalRubricas }}</div>
                        <div class="text-muted" style="font-size:.75rem;">Rúbricas creadas</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div style="width:44px;height:44px;background:linear-gradient(135deg,#059669,#10b981);border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i class="bi bi-check2-all text-white" style="font-size:1.1rem;"></i>
                    </div>
                    <div>
                        <div style="font-size:1.6rem;font-weight:900;color:#065f46;line-height:1.1;">{{ $totalAplicaciones }}</div>
                        <div class="text-muted" style="font-size:.75rem;">Aplicaciones totales</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div style="width:44px;height:44px;background:linear-gradient(135deg,#d97706,#f59e0b);border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i class="bi bi-bar-chart-fill text-white" style="font-size:1.1rem;"></i>
                    </div>
                    <div>
                        <div style="font-size:1.6rem;font-weight:900;color:#92400e;line-height:1.1;">
                            {{ $promedioGlobal ? number_format($promedioGlobal, 1) . '%' : '—' }}
                        </div>
                        <div class="text-muted" style="font-size:.75rem;">Promedio global</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div style="width:44px;height:44px;background:linear-gradient(135deg,#7c3aed,#8b5cf6);border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i class="bi bi-person-workspace text-white" style="font-size:1.1rem;"></i>
                    </div>
                    <div>
                        <div style="font-size:1.6rem;font-weight:900;color:#4c1d95;line-height:1.1;">{{ $docentesActivos }}</div>
                        <div class="text-muted" style="font-size:.75rem;">Docentes activos</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filtros --}}
    <div class="card shadow-sm mb-4">
        <div class="card-body py-2">
            <form method="GET" class="row g-2 align-items-center">
                <div class="col-md-4">
                    <input type="text" name="search" class="form-control form-control-sm"
                        placeholder="Buscar por título..." value="{{ request('search') }}">
                </div>
                <div class="col-md-3">
                    <select name="docente_id" class="form-select form-select-sm">
                        <option value="">-- Docente --</option>
                        @foreach($docentes as $doc)
                            <option value="{{ $doc->id }}" @selected(request('docente_id') == $doc->id)>
                                {{ $doc->nombre_completo }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="asignatura_id" class="form-select form-select-sm">
                        <option value="">-- Asignatura --</option>
                        @foreach($asignaturas as $asig)
                            <option value="{{ $asig->id }}" @selected(request('asignatura_id') == $asig->id)>
                                {{ $asig->nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-sm btn-secondary">
                        <i class="bi bi-search"></i> Filtrar
                    </button>
                    <a href="{{ route('admin.rubricas.index') }}" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-x"></i> Limpiar
                    </a>
                </div>
            </form>
        </div>
    </div>

    {{-- Tabla --}}
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle" style="font-size:.88rem;">
                    <thead class="table-light">
                        <tr>
                            <th>Título</th>
                            <th>Docente</th>
                            <th>Asignatura</th>
                            <th style="text-align:center;">Criterios</th>
                            <th style="text-align:center;">Aplicaciones</th>
                            <th style="text-align:center;">Niveles</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($rubricas as $rub)
                    @php
                        $niveles  = $rub->niveles  ?? [];
                        $criterios = $rub->criterios ?? [];
                        $ptsMax   = collect($criterios)->sum('puntos');
                    @endphp
                    <tr>
                        <td>
                            <a href="{{ route('admin.rubricas.show', $rub) }}" class="fw-semibold text-decoration-none" style="color:#1e3a6e;">
                                {{ $rub->titulo }}
                            </a>
                            @if($ptsMax)
                            <div class="text-muted" style="font-size:.75rem;">{{ $ptsMax }} pts máx.</div>
                            @endif
                        </td>
                        <td>
                            <div>{{ $rub->docente?->nombre_completo ?? '—' }}</div>
                        </td>
                        <td class="text-muted">{{ $rub->asignatura?->nombre ?? '—' }}</td>
                        <td style="text-align:center;">
                            <span class="badge bg-secondary">{{ count($criterios) }}</span>
                        </td>
                        <td style="text-align:center;">
                            @if($rub->aplicaciones_count > 0)
                            <span class="badge" style="background:#dbeafe;color:#1e40af;">
                                {{ $rub->aplicaciones_count }}
                            </span>
                            @else
                            <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td style="text-align:center;">
                            <div class="d-flex gap-1 justify-content-center flex-wrap">
                                @foreach($niveles as $niv)
                                <span style="background:{{ $niv['color'] ?? '#94a3b8' }};color:#fff;border-radius:99px;font-size:.65rem;padding:1px 7px;font-weight:700;">
                                    {{ $niv['nombre'] }}
                                </span>
                                @endforeach
                            </div>
                        </td>
                        <td class="text-end">
                            <a href="{{ route('admin.rubricas.show', $rub) }}"
                               class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-eye me-1"></i>Ver resultados
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <i class="bi bi-grid-3x3-gap" style="font-size:2rem;opacity:.3;display:block;margin-bottom:.5rem;"></i>
                            No se encontraron rúbricas con los filtros seleccionados.
                        </td>
                    </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($rubricas->hasPages())
        <div class="card-footer bg-white border-top-0 pt-0 pb-2">
            {{ $rubricas->links() }}
        </div>
        @endif
    </div>

</div>
@endsection
