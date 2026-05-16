@extends('layouts.portal-estudiante')
@section('title', 'Mis Evaluaciones')
@section('activeKey', 'evaluaciones')

@push('styles')
<style>
.eva-card {
    background:#fff;border:1.5px solid #e2e8f0;border-radius:12px;
    padding:1rem 1.1rem;margin-bottom:.7rem;
    border-left:4px solid #6366f1;transition:box-shadow .15s;
}
.eva-card:hover { box-shadow:0 3px 14px rgba(99,102,241,.12); }
.badge-estado {
    display:inline-flex;align-items:center;gap:.3rem;
    padding:.2rem .65rem;border-radius:99px;
    font-size:.68rem;font-weight:700;color:#fff;
}
</style>
@endpush

@section('content')

<div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:.5rem;margin-bottom:1.25rem;">
    <h2 style="font-size:1rem;font-weight:800;margin:0;">
        <i class="bi bi-patch-question-fill me-2" style="color:#6366f1;"></i>Mis Evaluaciones
    </h2>
    <span style="font-size:.75rem;color:#64748b;">{{ $quizzes->count() }} evaluacion{{ $quizzes->count() !== 1 ? 'es' : '' }}</span>
</div>

@if(session('success'))
<div style="background:#dcfce7;border:1px solid #86efac;border-radius:8px;padding:.6rem 1rem;margin-bottom:1rem;font-size:.8rem;color:#166534;">
    <i class="bi bi-check-circle-fill me-1"></i>{{ session('success') }}
</div>
@endif
@if(session('error'))
<div style="background:#fee2e2;border:1px solid #fca5a5;border-radius:8px;padding:.6rem 1rem;margin-bottom:1rem;font-size:.8rem;color:#991b1b;">
    <i class="bi bi-exclamation-triangle-fill me-1"></i>{{ session('error') }}
</div>
@endif

@forelse($quizzes as $item)
@php
    $quiz = $item['quiz'];
    $intento = $item['intento'];
    $activo  = $item['activo'];
    $puede   = $item['puede'];
    $disp    = $item['disponible'];
@endphp
<div class="eva-card">
    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:.5rem;flex-wrap:wrap;">
        <div style="flex:1;min-width:0;">
            <div style="font-size:.72rem;color:#94a3b8;margin-bottom:.15rem;">
                {{ $quiz->asignacion?->asignatura?->nombre }}
            </div>
            <div style="font-weight:800;font-size:.9rem;margin-bottom:.3rem;">{{ $quiz->titulo }}</div>
            <div style="display:flex;gap:.7rem;flex-wrap:wrap;font-size:.72rem;color:#64748b;">
                <span><i class="bi bi-list-ul me-1"></i>{{ $quiz->preguntas_count }} preguntas</span>
                @if($quiz->duracion_minutos)
                    <span><i class="bi bi-clock me-1"></i>{{ $quiz->duracion_minutos }} min</span>
                @endif
                <span><i class="bi bi-arrow-repeat me-1"></i>Max {{ $quiz->intentos_max }} intento{{ $quiz->intentos_max > 1 ? 's' : '' }}</span>
                @if($quiz->disponible_hasta)
                    <span><i class="bi bi-calendar-x me-1"></i>Hasta {{ $quiz->disponible_hasta->format('d/m/Y H:i') }}</span>
                @endif
            </div>
            @if($intento)
            <div style="margin-top:.4rem;font-size:.75rem;">
                Mejor intento:
                <strong style="color:{{ $intento->porcentaje >= 60 ? '#10b981' : '#ef4444' }};">
                    {{ $intento->porcentaje }}%
                </strong>
                ({{ $intento->puntuacion }}/{{ $intento->puntuacion_max }} pts)
            </div>
            @endif
        </div>
        <div style="display:flex;flex-direction:column;align-items:flex-end;gap:.4rem;">
            @if($activo)
                <span class="badge-estado" style="background:#f59e0b;"><i class="bi bi-hourglass-split"></i>En curso</span>
                <a href="{{ route('portal.estudiante.evaluaciones.tomar', $activo) }}"
                   style="background:#f59e0b;color:#fff;border:none;border-radius:8px;padding:.4rem .9rem;font-size:.78rem;font-weight:700;text-decoration:none;display:inline-flex;align-items:center;gap:.35rem;">
                    <i class="bi bi-play-fill"></i>Continuar
                </a>
            @elseif(!$disp)
                <span class="badge-estado" style="background:#94a3b8;"><i class="bi bi-lock"></i>No disponible</span>
            @elseif(!$puede)
                <span class="badge-estado" style="background:#64748b;"><i class="bi bi-check2-all"></i>Completada</span>
                @if($intento)
                <a href="{{ route('portal.estudiante.evaluaciones.resultado', $intento) }}"
                   style="background:#6366f1;color:#fff;border:none;border-radius:8px;padding:.4rem .9rem;font-size:.78rem;font-weight:700;text-decoration:none;display:inline-flex;align-items:center;gap:.35rem;">
                    <i class="bi bi-eye"></i>Ver resultado
                </a>
                @endif
            @else
                <a href="{{ route('portal.estudiante.evaluaciones.show', $quiz) }}"
                   style="background:#6366f1;color:#fff;border:none;border-radius:8px;padding:.4rem .9rem;font-size:.78rem;font-weight:700;text-decoration:none;display:inline-flex;align-items:center;gap:.35rem;">
                    <i class="bi bi-play-circle-fill"></i>{{ $intento ? 'Reintentar' : 'Iniciar' }}
                </a>
            @endif
        </div>
    </div>
</div>
@empty
<div class="prt-card" style="text-align:center;padding:2.5rem;color:#94a3b8;">
    <i class="bi bi-patch-question" style="font-size:2.5rem;display:block;margin-bottom:.6rem;"></i>
    <p style="margin:0;font-size:.88rem;">No hay evaluaciones disponibles por el momento.</p>
</div>
@endforelse

@endsection
