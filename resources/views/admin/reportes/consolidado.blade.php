@extends('layouts.admin')
@section('page-title', 'Consolidado de Calificaciones')

@section('content')

<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb mb-0" style="font-size:.82rem;">
        <li class="breadcrumb-item"><a href="{{ route('admin.reportes.index') }}" class="text-decoration-none">Reportes</a></li>
        <li class="breadcrumb-item active">Consolidado</li>
    </ol>
</nav>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold mb-1" style="color:var(--primary);">
            <i class="bi bi-table me-2"></i>Consolidado de Calificaciones
        </h4>
        <p class="text-muted mb-0" style="font-size:.85rem;">
            {{ $ciclo === 'primer' ? 'Primer Ciclo — 1ro, 2do, 3ro de Bachillerato' : 'Segundo Ciclo — 4to, 5to, 6to de Bachillerato' }}
            · {{ $schoolYear?->nombre }}
        </p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.reportes.consolidado', ['ciclo'=>'primer']) }}"
           class="btn btn-sm {{ $ciclo==='primer' ? 'btn-primary' : 'btn-outline-primary' }}">
            <i class="bi bi-1-circle me-1"></i>Primer Ciclo
        </a>
        <a href="{{ route('admin.reportes.consolidado', ['ciclo'=>'segundo']) }}"
           class="btn btn-sm {{ $ciclo==='segundo' ? 'btn-primary' : 'btn-outline-primary' }}">
            <i class="bi bi-2-circle me-1"></i>Segundo Ciclo
        </a>
        @if(request('grupo_id'))
        <a href="{{ route('admin.reportes.consolidado.pdf', ['grupo_id'=>request('grupo_id'), 'ciclo'=>$ciclo]) }}"
           target="_blank"
           class="btn btn-sm btn-danger">
            <i class="bi bi-file-earmark-pdf-fill me-1"></i>PDF
        </a>
        <a href="{{ route('admin.reportes.consolidado.excel', ['grupo_id'=>request('grupo_id'), 'ciclo'=>$ciclo]) }}"
           class="btn btn-sm btn-success">
            <i class="bi bi-file-earmark-excel-fill me-1"></i>Excel
        </a>
        @endif
    </div>
</div>

{{-- Group selector --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-3">
        <form method="GET" action="{{ route('admin.reportes.consolidado') }}" class="d-flex gap-2 align-items-end flex-wrap">
            <input type="hidden" name="ciclo" value="{{ $ciclo }}">
            <div>
                <label class="form-label mb-1" style="font-size:.8rem;font-weight:600;">Grupo / Sección</label>
                <select name="grupo_id" class="form-select form-select-sm" style="min-width:220px;" onchange="this.form.submit()">
                    <option value="">— Selecciona un grupo —</option>
                    @foreach($grupos as $g)
                    <option value="{{ $g->id }}" {{ request('grupo_id') == $g->id ? 'selected' : '' }}>
                        {{ $g->nombre_completo ?? $g->nombre }}
                    </option>
                    @endforeach
                </select>
            </div>
        </form>
    </div>
</div>

@if($grupo)

{{-- Read-only notice --}}
<div class="alert alert-info py-2 px-3 mb-3 d-flex align-items-center gap-2" style="font-size:.82rem;border-radius:8px;">
    <i class="bi bi-eye-fill text-primary"></i>
    <span><strong>Vista de supervisión:</strong> Esta pantalla es de solo lectura. Para modificar calificaciones, el docente responsable debe acceder al módulo de Calificaciones.</span>
</div>

{{-- Asignaciones table --}}
@if($asignaciones->count())
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header border-0 py-3 px-4" style="background:#f8faff;">
        <h6 class="fw-bold mb-0" style="color:var(--primary);">
            {{ $grupo->nombre_completo ?? $grupo->nombre }} — Asignaturas
        </h6>
    </div>
    <div class="table-responsive">
        <table class="table table-sm mb-0" style="font-size:.82rem;">
            <thead style="background:#1e3a6e;color:#fff;">
                <tr>
                    <th style="padding:.55rem 1rem;">Asignatura</th>
                    <th style="padding:.55rem .5rem;">Área</th>
                    <th style="padding:.55rem .5rem;">Docente</th>
                    <th class="text-center" style="padding:.55rem .5rem;">Registros</th>
                    <th class="text-center" style="padding:.55rem .5rem;">Aprobados</th>
                    <th class="text-center" style="padding:.55rem .5rem;">Reprobados</th>
                    <th class="text-center" style="padding:.55rem .5rem;">Prom. Final</th>
                    <th class="text-center" style="padding:.55rem .5rem;">Estado</th>
                </tr>
            </thead>
            <tbody>
            @foreach($asignaciones as $asig)
            @php
                $calAsig = collect($registros)->flatMap(fn($r) => $r['academicas'])
                    ->where('asignacion_id', $asig->id);
                $aprobados  = $calAsig->where('situacion','A')->count();
                $reprobados = $calAsig->where('situacion','R')->count();
                $promFinal  = $calAsig->whereNotNull('nota_final')->avg('nota_final');
                $total      = $calAsig->count();
            @endphp
            <tr>
                <td style="padding:.5rem 1rem;font-weight:600;color:#1e293b;">{{ $asig->asignatura->nombre }}</td>
                <td style="padding:.5rem .5rem;">
                    <span class="badge" style="background:{{ ($asig->asignatura->area ?? 'Técnica')==='Académica' ? '#dbeafe' : '#ede9fe' }};color:{{ ($asig->asignatura->area ?? 'Técnica')==='Académica' ? '#1d4ed8' : '#6d28d9' }};font-size:.71rem;">
                        {{ $asig->asignatura->area ?? 'Técnica' }}
                    </span>
                </td>
                <td style="padding:.5rem .5rem;color:#6b7280;font-size:.8rem;">
                    {{ optional($asig->docente)->nombre_completo ?? 'Sin docente' }}
                </td>
                <td class="text-center" style="padding:.5rem;">{{ $total ?: '—' }}</td>
                <td class="text-center" style="padding:.5rem;font-weight:700;color:#15803d;">{{ $aprobados ?: '—' }}</td>
                <td class="text-center" style="padding:.5rem;font-weight:700;color:{{ $reprobados > 0 ? '#991b1b' : '#9ca3af' }};">{{ $reprobados ?: '—' }}</td>
                <td class="text-center" style="padding:.5rem;font-weight:700;
                    color:{{ $promFinal >= 80 ? '#15803d' : ($promFinal >= 60 ? '#854d0e' : '#991b1b') }};">
                    {{ $promFinal ? number_format($promFinal,1) : '—' }}
                </td>
                <td class="text-center" style="padding:.5rem .5rem;">
                    @if($total === 0)
                        <span class="badge" style="background:#f3f4f6;color:#6b7280;font-size:.72rem;">Sin datos</span>
                    @elseif($reprobados === 0)
                        <span class="badge" style="background:#dcfce7;color:#15803d;font-size:.72rem;">✓ OK</span>
                    @else
                        <span class="badge" style="background:#fee2e2;color:#991b1b;font-size:.72rem;">⚠ Revisar</span>
                    @endif
                </td>
            </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@else
<div class="text-center py-5 text-muted">
    <i class="bi bi-journal-x" style="font-size:3rem;opacity:.3;"></i>
    <p class="mt-2">No hay asignaciones registradas para este grupo.</p>
</div>
@endif

@endif

@endsection
