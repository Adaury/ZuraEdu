@extends('layouts.admin')
@section('page-title', 'Calificaciones — '.$claseVirtual->nombre)
@section('content')

@php $color = $claseVirtual->portada_color ?? '#3B82F6'; @endphp

<div class="mb-4 d-flex align-items-center gap-3">
    <a href="{{ route('portal.docente.classroom.show', $claseVirtual) }}" class="btn btn-outline-secondary btn-sm" style="border-radius:8px;">
        <i class="bi bi-arrow-left me-1"></i>Volver al Aula
    </a>
    <div>
        <h5 class="fw-bold mb-0">Calificaciones</h5>
        <small class="text-muted">{{ $claseVirtual->nombre }} &bull; {{ $materiales->count() }} actividad{{ $materiales->count() !== 1 ? 'es' : '' }} evaluadas</small>
    </div>
</div>

@if($materiales->isEmpty())
<div class="card border-0 shadow-sm" style="border-radius:16px;">
    <div class="card-body text-center py-5">
        <div style="width:70px;height:70px;background:#F1F5F9;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;">
            <i class="bi bi-bar-chart" style="font-size:1.8rem;color:#94A3B8;"></i>
        </div>
        <h6 class="fw-semibold text-muted mb-1">Sin actividades evaluadas</h6>
        <p class="text-muted small mb-3">Crea tareas o evaluaciones para ver el resumen de calificaciones.</p>
        <a href="{{ route('portal.docente.classroom.crear_material', $claseVirtual) }}?tipo=tarea" class="btn btn-primary btn-sm" style="border-radius:8px;">
            <i class="bi bi-plus-lg me-1"></i>Crear tarea
        </a>
    </div>
</div>
@else

{{-- Tabla principal --}}
<div class="card border-0 shadow-sm" style="border-radius:14px;overflow:hidden;">
<div class="table-responsive">
<table class="table table-hover mb-0" style="font-size:.85rem;">
<thead style="background:#F8FAFC;position:sticky;top:0;z-index:10;">
    <tr>
        <th class="px-4 py-3 fw-semibold text-muted" style="font-size:.75rem;text-transform:uppercase;min-width:180px;">Estudiante</th>
        @foreach($materiales as $mat)
        @php
            $tipoColor = ['tarea'=>'#F59E0B','evaluacion'=>'#EF4444'][$mat->tipo] ?? '#6B7280';
        @endphp
        <th class="py-3 text-center fw-semibold" style="min-width:110px;">
            <div class="d-flex flex-column align-items-center gap-1">
                <span class="badge rounded-pill" style="background:{{ $tipoColor }}18;color:{{ $tipoColor }};font-size:.65rem;">
                    {{ ucfirst($mat->tipo) }}
                </span>
                <span class="text-muted" style="font-size:.75rem;max-width:100px;text-align:center;line-height:1.2;">
                    {{ Str::limit($mat->titulo, 22) }}
                </span>
                @if($mat->puntos)
                <span style="font-size:.7rem;color:#94A3B8;">/ {{ $mat->puntos }} pts</span>
                @endif
            </div>
        </th>
        @endforeach
        <th class="py-3 text-center fw-semibold text-muted" style="font-size:.75rem;text-transform:uppercase;min-width:90px;">Promedio</th>
    </tr>
</thead>
<tbody>
@foreach($matriculas as $matricula)
@php
    $nombre = $matricula->estudiante->nombre_completo
        ?? ($matricula->estudiante->nombres.' '.$matricula->estudiante->apellidos);
    $sumaNotas = 0;
    $cntNotas  = 0;
@endphp
<tr>
    <td class="px-4 py-3 fw-semibold" style="font-size:.875rem;">{{ $nombre }}</td>
    @foreach($materiales as $mat)
    @php
        $entrega = $mat->entregas->where('matricula_id', $matricula->id)->first();
        $nota    = $entrega?->calificacion;
        $max     = $mat->puntos ?? 100;
        if ($nota !== null) { $sumaNotas += ($nota / $max * 100); $cntNotas++; }
        $pct     = $max > 0 && $nota !== null ? round($nota / $max * 100) : null;
        $badgeBg = $pct === null ? '#F1F5F9' : ($pct >= 70 ? '#F0FDF4' : '#FEF2F2');
        $badgeCl = $pct === null ? '#94A3B8'  : ($pct >= 70 ? '#16A34A' : '#DC2626');
    @endphp
    <td class="py-3 text-center">
        @if($entrega && $nota !== null)
            <span class="badge rounded-pill" style="background:{{ $badgeBg }};color:{{ $badgeCl }};font-size:.8rem;padding:.35em .65em;">
                {{ $nota }}<span style="font-size:.65rem;opacity:.7;">/{{ $max }}</span>
            </span>
        @elseif($entrega)
            <span class="badge rounded-pill" style="background:#EFF6FF;color:#3B82F6;font-size:.75rem;">Entregado</span>
        @else
            <span class="text-muted" style="font-size:.8rem;">—</span>
        @endif
    </td>
    @endforeach
    <td class="py-3 text-center">
        @if($cntNotas > 0)
        @php $prom = round($sumaNotas / $cntNotas, 1); @endphp
        <span class="fw-bold" style="font-size:.875rem;color:{{ $prom >= 70 ? '#16A34A' : '#DC2626' }};">
            {{ $prom }}%
        </span>
        @else
        <span class="text-muted small">—</span>
        @endif
    </td>
</tr>
@endforeach
</tbody>
</table>
</div>
</div>

{{-- Resumen por actividad --}}
<div class="row g-3 mt-1">
@foreach($materiales as $mat)
@php
    $total     = $matriculas->count();
    $entregados = $mat->entregas->where('matricula_id', '!=', null)->count();
    $calificados = $mat->entregas->whereNotNull('calificacion')->count();
    $tipoColor  = ['tarea'=>'#F59E0B','evaluacion'=>'#EF4444'][$mat->tipo] ?? '#6B7280';
@endphp
<div class="col-md-4 col-lg-3">
    <div class="card border-0 shadow-sm h-100" style="border-radius:12px;border-left:3px solid {{ $tipoColor }} !important;">
        <div class="card-body p-3">
            <div class="fw-semibold mb-1" style="font-size:.85rem;">{{ Str::limit($mat->titulo, 30) }}</div>
            <div class="d-flex gap-3 mt-2" style="font-size:.8rem;">
                <div class="text-center">
                    <div class="fw-bold" style="font-size:1rem;">{{ $entregados }}</div>
                    <div class="text-muted" style="font-size:.7rem;">Entregas</div>
                </div>
                <div class="text-center">
                    <div class="fw-bold text-success" style="font-size:1rem;">{{ $calificados }}</div>
                    <div class="text-muted" style="font-size:.7rem;">Calificados</div>
                </div>
                <div class="text-center">
                    <div class="fw-bold text-warning" style="font-size:1rem;">{{ $total - $entregados }}</div>
                    <div class="text-muted" style="font-size:.7rem;">Pendientes</div>
                </div>
            </div>
            <a href="{{ route('portal.docente.classroom.entregas', [$claseVirtual, $mat]) }}" class="btn btn-sm w-100 mt-3" style="border-radius:8px;background:{{ $tipoColor }}15;color:{{ $tipoColor }};border:none;font-size:.78rem;">
                <i class="bi bi-arrow-right me-1"></i>Ver entregas
            </a>
        </div>
    </div>
</div>
@endforeach
</div>
@endif

@endsection
