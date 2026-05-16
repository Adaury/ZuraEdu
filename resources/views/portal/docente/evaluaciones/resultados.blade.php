@extends('layouts.portal')
@section('page-title', 'Resultados — '.$quiz->titulo)
@section('portal-name', 'Portal Docente')

@section('sidebar')
    @include('portal.docente._sidebar_clase', ['activeKey' => 'evaluaciones', 'asignacion' => $asignacion])
@endsection

@section('bottom-nav')
<a href="{{ route('portal.docente.dashboard') }}" class="prt-nav-item"><i class="bi bi-house-fill"></i>Inicio</a>
<a href="{{ route('portal.docente.evaluaciones.index', $asignacion) }}" class="prt-nav-item active"><i class="bi bi-patch-question-fill"></i>Evaluaciones</a>
<a href="{{ route('portal.docente.calificaciones', $asignacion) }}" class="prt-nav-item"><i class="bi bi-journal-check"></i>Notas</a>
@endsection

@push('styles')
<style>
.res-table th, .res-table td {
    padding:.5rem .75rem;font-size:.78rem;vertical-align:middle;
}
.res-table th { background:#f8fafc;font-weight:700;color:#475569; }
.pct-bar-bg { background:#e2e8f0;border-radius:99px;height:6px;width:80px;display:inline-block;vertical-align:middle; }
.pct-bar-fill { height:6px;border-radius:99px;transition:.3s; }
.badge-mini {
    display:inline-block;padding:.15rem .45rem;border-radius:99px;
    font-size:.68rem;font-weight:700;color:#fff;
}
</style>
@endpush

@section('content')

<div style="display:flex;align-items:center;gap:.7rem;margin-bottom:1rem;flex-wrap:wrap;">
    <a href="{{ route('portal.docente.evaluaciones.show', [$asignacion, $quiz]) }}"
       style="color:#6366f1;text-decoration:none;font-size:.8rem;font-weight:600;display:flex;align-items:center;gap:.3rem;">
        <i class="bi bi-arrow-left"></i>Evaluación
    </a>
    <span style="color:#cbd5e1;">›</span>
    <h2 style="font-size:1rem;font-weight:800;margin:0;">Resultados: {{ $quiz->titulo }}</h2>
</div>

{{-- KPIs --}}
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(130px,1fr));gap:.7rem;margin-bottom:1.2rem;">
    <div class="prt-card" style="text-align:center;padding:.8rem;">
        <div style="font-size:1.4rem;font-weight:800;color:#6366f1;">{{ $stats['completaron'] }}</div>
        <div style="font-size:.68rem;color:#64748b;">Completaron</div>
    </div>
    <div class="prt-card" style="text-align:center;padding:.8rem;">
        <div style="font-size:1.4rem;font-weight:800;color:#94a3b8;">{{ $stats['pendientes'] }}</div>
        <div style="font-size:.68rem;color:#64748b;">Pendientes</div>
    </div>
    <div class="prt-card" style="text-align:center;padding:.8rem;">
        <div style="font-size:1.4rem;font-weight:800;color:#10b981;">{{ $stats['promedio'] ?? '—' }}%</div>
        <div style="font-size:.68rem;color:#64748b;">Promedio</div>
    </div>
    <div class="prt-card" style="text-align:center;padding:.8rem;">
        <div style="font-size:1.4rem;font-weight:800;color:#f59e0b;">{{ $stats['aprobados'] }}</div>
        <div style="font-size:.68rem;color:#64748b;">Aprobados (≥60%)</div>
    </div>
    <div class="prt-card" style="text-align:center;padding:.8rem;">
        <div style="font-size:1.4rem;font-weight:800;color:#475569;">{{ $puntajeTotal }}</div>
        <div style="font-size:.68rem;color:#64748b;">Pts. total</div>
    </div>
</div>

{{-- Análisis por pregunta --}}
@if($analisisPregunta->isNotEmpty())
<div class="prt-card" style="margin-bottom:1.2rem;">
    <div class="prt-card-header" style="margin-bottom:.8rem;">
        <i class="bi bi-bar-chart-steps me-2" style="color:#6366f1;"></i>Análisis por Pregunta
    </div>
    @foreach($analisisPregunta as $ap)
    <div style="margin-bottom:.7rem;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.2rem;">
            <span style="font-size:.78rem;font-weight:600;flex:1;margin-right:.5rem;">
                P{{ $loop->iteration }}: {{ Str::limit($ap['pregunta']->enunciado, 60) }}
            </span>
            <span style="font-size:.72rem;color:#64748b;white-space:nowrap;">
                {{ $ap['correctas'] }}/{{ $ap['total'] }}
                @if($ap['pct'] !== null)
                    · <strong style="color:{{ $ap['pct'] >= 60 ? '#10b981' : '#ef4444' }};">{{ $ap['pct'] }}%</strong>
                @endif
            </span>
        </div>
        <div style="background:#e2e8f0;border-radius:99px;height:7px;">
            <div style="height:7px;border-radius:99px;width:{{ $ap['pct'] ?? 0 }}%;background:{{ ($ap['pct'] ?? 0) >= 60 ? '#10b981' : '#ef4444' }};transition:.4s;"></div>
        </div>
    </div>
    @endforeach
</div>
@endif

{{-- Tabla de resultados por estudiante --}}
<div class="prt-card">
    <div class="prt-card-header" style="margin-bottom:.8rem;">
        <i class="bi bi-people me-2" style="color:#6366f1;"></i>Resultados por Estudiante
    </div>
    <div style="overflow-x:auto;">
        <table class="res-table" style="width:100%;border-collapse:collapse;">
            <thead>
                <tr style="border-bottom:2px solid #e2e8f0;">
                    <th>Estudiante</th>
                    <th>Puntaje</th>
                    <th>Porcentaje</th>
                    <th>Estado</th>
                    <th>Duración</th>
                </tr>
            </thead>
            <tbody>
                @foreach($matriculas as $m)
                @php $intento = $mejores->get($m->id); @endphp
                <tr style="border-bottom:1px solid #f1f5f9;">
                    <td>
                        <div style="font-weight:600;font-size:.82rem;">
                            {{ $m->estudiante?->nombre_completo ?? '—' }}
                        </div>
                    </td>
                    @if($intento)
                    <td>
                        <span style="font-weight:700;color:#6366f1;">{{ $intento->puntuacion }}</span>
                        <span style="color:#94a3b8;font-size:.72rem;">/ {{ $puntajeTotal }}</span>
                    </td>
                    <td>
                        <div style="display:flex;align-items:center;gap:.4rem;">
                            <div class="pct-bar-bg">
                                <div class="pct-bar-fill" style="width:{{ $intento->porcentaje }}%;background:{{ $intento->porcentaje >= 60 ? '#10b981' : '#ef4444' }};"></div>
                            </div>
                            <span style="font-size:.75rem;font-weight:700;color:{{ $intento->porcentaje >= 60 ? '#10b981' : '#ef4444' }};">
                                {{ $intento->porcentaje }}%
                            </span>
                        </div>
                    </td>
                    <td>
                        @if($intento->porcentaje >= 60)
                            <span class="badge-mini" style="background:#10b981;">Aprobado</span>
                        @else
                            <span class="badge-mini" style="background:#ef4444;">No aprobado</span>
                        @endif
                    </td>
                    <td style="color:#64748b;font-size:.75rem;">{{ $intento->duracion ?? '—' }}</td>
                    @else
                    <td colspan="4" style="color:#94a3b8;font-size:.75rem;font-style:italic;">Sin responder</td>
                    @endif
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

@endsection
