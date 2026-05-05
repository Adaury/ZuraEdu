@extends('layouts.portal')
@section('title', 'Resultado — '.$material->titulo)
@section('content')

@php
$color   = $claseVirtual->portada_color ?? '#4f46e5';
$pct     = $intento->porcentaje;
$aprobado = $pct >= 60;
$puntMax = $intento->puntuacion_max ?? $quiz->puntaje_total;
@endphp

{{-- Hero resultado --}}
<div class="text-center p-5 mb-4 rounded-4" style="background:{{ $aprobado ? 'linear-gradient(135deg,#16a34a,#22c55e)' : 'linear-gradient(135deg,#dc2626,#ef4444)' }};color:#fff;">
    <div style="font-size:3rem;">{{ $aprobado ? '🎉' : '📚' }}</div>
    <h3 class="fw-bold mt-2 mb-1">{{ $aprobado ? '¡Felicitaciones!' : 'Sigue practicando' }}</h3>
    <div style="font-size:2.5rem;font-weight:900;margin:.5rem 0;">{{ $intento->puntuacion ?? 0 }} / {{ $puntMax }}</div>
    <div style="font-size:1.1rem;opacity:.9;">{{ $pct }}% — {{ $aprobado ? 'Aprobado' : 'No aprobado' }}</div>
    <div style="opacity:.7;font-size:.85rem;margin-top:.5rem;">
        Completado {{ $intento->finalizado_en?->format('d/m/Y H:i') }}
        @if($intento->duracion) &bull; Duración: {{ $intento->duracion }} @endif
    </div>
</div>

{{-- Revisión de respuestas (si autocorrección activa) --}}
@if($quiz->mostrar_respuestas)
<h6 class="fw-bold mb-3">Revisión de respuestas</h6>
@foreach($quiz->preguntas as $i => $pregunta)
@php
$resp     = $respuestas->get($pregunta->id);
$correcta = $pregunta->tipo !== 'abierta' ? $pregunta->opcionCorrecta() : null;
$esOk     = $resp?->es_correcta;
$esAbierta = $pregunta->tipo === 'abierta';
@endphp
<div class="card border-0 shadow-sm mb-3" style="border-radius:14px;border-left:4px solid {{ $esAbierta ? '#6366f1' : ($esOk ? '#16a34a' : '#dc2626') }} !important;">
    <div class="card-body p-4">
        <div class="d-flex align-items-start gap-2 mb-3">
            <span class="badge {{ $esAbierta ? 'bg-info' : ($esOk ? 'bg-success' : 'bg-danger') }}" style="flex-shrink:0;margin-top:2px;">
                {{ $i+1 }}
            </span>
            <div>
                <p class="fw-semibold mb-1">{{ $pregunta->enunciado }}</p>
                <small class="text-muted">{{ $pregunta->puntos }} pts</small>
            </div>
            <div class="ms-auto">
                @if(!$esAbierta)
                <span class="badge {{ $esOk ? 'bg-success' : 'bg-danger' }}">
                    {{ $esOk ? '+'.number_format($resp->puntos_obtenidos,1) : '0' }} pts
                </span>
                @else
                <span class="badge bg-secondary">Revisión manual</span>
                @endif
            </div>
        </div>

        @if($pregunta->tipo !== 'abierta')
        <div class="d-flex flex-column gap-2">
            @foreach($pregunta->opciones as $opcion)
            @php
            $seleccionada = $resp && $resp->opcion_id == $opcion->id;
            $esLaCorrecta = $opcion->es_correcta;
            @endphp
            <div class="d-flex align-items-center gap-2 p-2 rounded-3" style="background:{{ $esLaCorrecta ? '#dcfce7' : ($seleccionada && !$esLaCorrecta ? '#fee2e2' : '#f8fafc') }};border:1px solid {{ $esLaCorrecta ? '#86efac' : ($seleccionada && !$esLaCorrecta ? '#fca5a5' : '#e5e7eb') }};">
                @if($esLaCorrecta)
                    <i class="bi bi-check-circle-fill text-success"></i>
                @elseif($seleccionada)
                    <i class="bi bi-x-circle-fill text-danger"></i>
                @else
                    <i class="bi bi-circle text-muted"></i>
                @endif
                <span style="font-size:.88rem;">{{ $opcion->texto }}</span>
                @if($seleccionada && !$esLaCorrecta)
                <span class="ms-auto text-danger" style="font-size:.75rem;font-weight:600;">Tu respuesta</span>
                @elseif($esLaCorrecta && $seleccionada)
                <span class="ms-auto text-success" style="font-size:.75rem;font-weight:600;">✓ Correcta</span>
                @elseif($esLaCorrecta)
                <span class="ms-auto text-success" style="font-size:.75rem;font-weight:600;">Respuesta correcta</span>
                @endif
            </div>
            @endforeach
        </div>
        @else
        <div class="p-3 rounded-3" style="background:#f8fafc;border:1px solid #e5e7eb;">
            <div class="text-muted small mb-1">Tu respuesta:</div>
            <p style="font-size:.9rem;">{{ $resp?->texto_respuesta ?? '(Sin respuesta)' }}</p>
            <div class="text-muted small mt-2"><i class="bi bi-info-circle me-1"></i>Será revisada por el docente</div>
        </div>
        @endif
    </div>
</div>
@endforeach
@endif

<div class="d-flex gap-3 justify-content-center mt-4">
    <a href="{{ route('portal.estudiante.classroom.show', $claseVirtual) }}" class="btn btn-outline-secondary" style="border-radius:10px;">
        <i class="bi bi-arrow-left me-1"></i>Volver al Aula
    </a>
    @if($quiz->puedeIntentar($matricula->id))
    <a href="{{ route('portal.estudiante.quiz.iniciar', [$claseVirtual, $material]) }}" class="btn btn-primary" style="border-radius:10px;">
        <i class="bi bi-arrow-repeat me-1"></i>Reintentar
    </a>
    @endif
</div>

@endsection
