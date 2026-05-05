@extends('layouts.admin')
@section('page-title', 'Resultados Quiz — '.$material->titulo)
@section('content')

@php $color = $claseVirtual->portada_color ?? '#4f46e5'; @endphp

<div class="mb-4 d-flex align-items-center gap-3">
    <a href="{{ route('portal.docente.classroom.entregas', [$claseVirtual, $material]) }}" class="btn btn-outline-secondary btn-sm" style="border-radius:8px;">
        <i class="bi bi-arrow-left me-1"></i>Volver
    </a>
    <div>
        <h5 class="fw-bold mb-0">Resultados del Quiz</h5>
        <small class="text-muted">{{ $material->titulo }} &bull; {{ $claseVirtual->nombre }}</small>
    </div>
</div>

{{-- Stats --}}
<div class="row g-3 mb-4">
    @foreach([
        ['Completaron', $stats['completaron'].'/'.$matriculas->count(), '#4f46e5', 'bi-people-fill'],
        ['Pendientes', $stats['pendientes'], '#f59e0b', 'bi-hourglass-split'],
        ['Aprobados', $stats['aprobados'], '#16a34a', 'bi-check-circle-fill'],
        ['Promedio', $stats['promedio'] ? number_format($stats['promedio'],1).' pts' : '—', '#0284c7', 'bi-bar-chart-fill'],
    ] as [$lbl,$val,$clr,$icn])
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100" style="border-radius:14px;border-left:4px solid {{ $clr }} !important;">
            <div class="card-body d-flex align-items-center gap-3 py-3">
                <i class="bi {{ $icn }}" style="color:{{ $clr }};font-size:1.5rem;"></i>
                <div>
                    <div style="font-size:1.4rem;font-weight:800;color:{{ $clr }};">{{ $val }}</div>
                    <div class="text-muted small">{{ $lbl }}</div>
                </div>
            </div>
        </div>
    </div>
    @endforeach
</div>

{{-- Tabla de resultados --}}
<div class="card border-0 shadow-sm" style="border-radius:16px;">
<div class="card-body p-0">
    <div class="px-4 py-3 border-bottom d-flex align-items-center justify-content-between">
        <h6 class="fw-bold mb-0">Resultados por Estudiante</h6>
        <span class="text-muted small">{{ $quiz->preguntas->count() }} preguntas &bull; {{ $quiz->puntaje_total }} pts máx.</span>
    </div>
    <div class="table-responsive">
    <table class="table table-hover mb-0 align-middle" style="font-size:.875rem;">
    <thead style="background:#F8FAFC;">
        <tr>
            <th class="px-4 py-3 fw-semibold text-muted" style="font-size:.8rem;text-transform:uppercase;">#</th>
            <th class="py-3 fw-semibold text-muted" style="font-size:.8rem;text-transform:uppercase;">Estudiante</th>
            <th class="py-3 fw-semibold text-muted text-center" style="font-size:.8rem;text-transform:uppercase;">Puntaje</th>
            <th class="py-3 fw-semibold text-muted text-center" style="font-size:.8rem;text-transform:uppercase;">%</th>
            <th class="py-3 fw-semibold text-muted text-center" style="font-size:.8rem;text-transform:uppercase;">Estado</th>
            <th class="py-3 fw-semibold text-muted text-center" style="font-size:.8rem;text-transform:uppercase;">Intentos</th>
            <th class="py-3 fw-semibold text-muted" style="font-size:.8rem;text-transform:uppercase;">Fecha</th>
        </tr>
    </thead>
    <tbody>
    @foreach($matriculas->sortBy(fn($m) => $m->estudiante->apellidos) as $i => $mat)
    @php
        $intento = $mejoresIntentos->get($mat->id);
        $pct     = $intento?->porcentaje;
        $aprobado = $pct !== null && $pct >= 60;
        $allIntentos = \App\Models\ZcIntento::where('quiz_id',$quiz->id)->where('matricula_id',$mat->id)->count();
    @endphp
    <tr>
        <td class="px-4">{{ $i + 1 }}</td>
        <td class="py-3">
            <div class="fw-semibold">{{ $mat->estudiante?->nombres }} {{ $mat->estudiante?->apellidos }}</div>
        </td>
        <td class="py-3 text-center">
            @if($intento)
            <strong style="color:{{ $aprobado?'#16a34a':'#dc2626' }};">
                {{ number_format($intento->puntuacion,1) }}/{{ $quiz->puntaje_total }}
            </strong>
            @else
            <span class="text-muted">—</span>
            @endif
        </td>
        <td class="py-3 text-center">
            @if($pct !== null)
            <span class="badge rounded-pill {{ $aprobado?'bg-success':'bg-danger' }}">{{ $pct }}%</span>
            @else
            <span class="text-muted small">Sin intentos</span>
            @endif
        </td>
        <td class="py-3 text-center">
            @if($intento)
            <span class="badge {{ $aprobado?'bg-success':'bg-danger' }}">{{ $aprobado?'Aprobado':'No aprobado' }}</span>
            @else
            <span class="badge bg-warning text-dark">Pendiente</span>
            @endif
        </td>
        <td class="py-3 text-center">
            <span class="text-muted small">{{ $allIntentos }}/{{ $quiz->intentos_max }}</span>
        </td>
        <td class="py-3">
            <span class="text-muted small">{{ $intento?->finalizado_en?->format('d/m/Y H:i') ?? '—' }}</span>
        </td>
    </tr>
    @endforeach
    </tbody>
    </table>
    </div>
</div>
</div>

@endsection
