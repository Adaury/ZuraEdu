@extends('layouts.portal-estudiante')
@section('title', $quiz->titulo)
@section('activeKey', 'evaluaciones')

@section('content')

<div style="display:flex;align-items:center;gap:.7rem;margin-bottom:1.2rem;flex-wrap:wrap;">
    <a href="{{ route('portal.estudiante.evaluaciones.index') }}"
       style="color:#6366f1;text-decoration:none;font-size:.8rem;font-weight:600;display:flex;align-items:center;gap:.3rem;">
        <i class="bi bi-arrow-left"></i>Evaluaciones
    </a>
    <span style="color:#cbd5e1;">›</span>
    <h2 style="font-size:1rem;font-weight:800;margin:0;">{{ $quiz->titulo }}</h2>
</div>

<div class="prt-card" style="max-width:600px;margin:0 auto;padding:1.5rem;">
    <div style="text-align:center;margin-bottom:1.5rem;">
        <div style="width:56px;height:56px;background:#ede9fe;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto .8rem;">
            <i class="bi bi-patch-question-fill" style="font-size:1.6rem;color:#6366f1;"></i>
        </div>
        <h3 style="font-size:1.05rem;font-weight:800;margin:0 0 .3rem;">{{ $quiz->titulo }}</h3>
        <p style="font-size:.78rem;color:#64748b;margin:0;">{{ $quiz->asignacion?->asignatura?->nombre }}</p>
    </div>

    @if($quiz->instrucciones)
    <div style="background:#f8fafc;border-radius:8px;padding:.8rem 1rem;margin-bottom:1.2rem;font-size:.83rem;color:#475569;border-left:3px solid #6366f1;">
        {{ $quiz->instrucciones }}
    </div>
    @endif

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:.7rem;margin-bottom:1.2rem;">
        <div style="background:#f8fafc;border-radius:8px;padding:.7rem;text-align:center;">
            <div style="font-size:1.2rem;font-weight:800;color:#6366f1;">{{ $quiz->preguntas_count }}</div>
            <div style="font-size:.7rem;color:#64748b;">Preguntas</div>
        </div>
        <div style="background:#f8fafc;border-radius:8px;padding:.7rem;text-align:center;">
            @if($quiz->duracion_minutos)
            <div style="font-size:1.2rem;font-weight:800;color:#f59e0b;">{{ $quiz->duracion_minutos }} min</div>
            <div style="font-size:.7rem;color:#64748b;">Límite de tiempo</div>
            @else
            <div style="font-size:1.2rem;font-weight:800;color:#10b981;">∞</div>
            <div style="font-size:.7rem;color:#64748b;">Sin límite</div>
            @endif
        </div>
        <div style="background:#f8fafc;border-radius:8px;padding:.7rem;text-align:center;">
            <div style="font-size:1.2rem;font-weight:800;color:#475569;">{{ $quiz->intentos_max }}</div>
            <div style="font-size:.7rem;color:#64748b;">Intentos permitidos</div>
        </div>
        <div style="background:#f8fafc;border-radius:8px;padding:.7rem;text-align:center;">
            <div style="font-size:1.2rem;font-weight:800;color:{{ $quiz->mostrar_resultados ? '#10b981' : '#94a3b8' }};">
                {{ $quiz->mostrar_resultados ? 'Sí' : 'No' }}
            </div>
            <div style="font-size:.7rem;color:#64748b;">Muestra resultados</div>
        </div>
    </div>

    @if($intentoPrevio)
    <div style="background:#f0fdf4;border:1px solid #86efac;border-radius:8px;padding:.7rem 1rem;margin-bottom:1rem;font-size:.82rem;color:#166534;">
        <i class="bi bi-trophy-fill me-1"></i>
        Mejor resultado previo: <strong>{{ $intentoPrevio->porcentaje }}%</strong>
        ({{ $intentoPrevio->puntuacion }}/{{ $intentoPrevio->puntuacion_max }} pts)
        — <a href="{{ route('portal.estudiante.evaluaciones.resultado', $intentoPrevio) }}" style="color:#166534;font-weight:700;">Ver detalles</a>
    </div>
    @endif

    <div style="text-align:center;">
        @if($intentoActivo)
        <a href="{{ route('portal.estudiante.evaluaciones.tomar', $intentoActivo) }}"
           style="background:#f59e0b;color:#fff;border:none;border-radius:10px;padding:.7rem 2rem;font-size:.9rem;font-weight:800;text-decoration:none;display:inline-flex;align-items:center;gap:.5rem;">
            <i class="bi bi-play-fill"></i>Continuar Evaluación
        </a>
        @elseif(!$disponible)
        <div style="background:#f1f5f9;border-radius:8px;padding:1rem;color:#64748b;font-size:.85rem;">
            <i class="bi bi-lock me-1"></i>Esta evaluación no está disponible en este momento.
        </div>
        @elseif(!$puede)
        <div style="background:#f1f5f9;border-radius:8px;padding:1rem;color:#64748b;font-size:.85rem;">
            <i class="bi bi-check2-all me-1"></i>Has agotado todos tus intentos.
        </div>
        @else
        <form method="POST" action="{{ route('portal.estudiante.evaluaciones.iniciar', $quiz) }}">
            @csrf
            <button type="submit"
                style="background:#6366f1;color:#fff;border:none;border-radius:10px;padding:.7rem 2rem;font-size:.9rem;font-weight:800;cursor:pointer;display:inline-flex;align-items:center;gap:.5rem;">
                <i class="bi bi-play-circle-fill"></i>Comenzar Evaluación
            </button>
        </form>
        @endif
    </div>
</div>

@endsection
