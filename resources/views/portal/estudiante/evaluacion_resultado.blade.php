@extends('layouts.portal-estudiante')
@section('title', 'Resultado — '.$quiz->titulo)
@section('activeKey', 'evaluaciones')

@push('styles')
<style>
.preg-res {
    background:#fff;border:1.5px solid #e2e8f0;border-radius:10px;
    padding:.85rem 1rem;margin-bottom:.6rem;
}
.opcion-r {
    display:flex;align-items:center;gap:.5rem;
    padding:.45rem .7rem;border-radius:7px;margin-bottom:.3rem;
    font-size:.82rem;
}
.opcion-r.correcta { background:#dcfce7;border:1px solid #86efac; }
.opcion-r.respondida { background:#ede9fe;border:1px solid #a5b4fc; }
.opcion-r.error     { background:#fee2e2;border:1px solid #fca5a5; }
</style>
@endpush

@section('content')

<div style="display:flex;align-items:center;gap:.7rem;margin-bottom:1.2rem;flex-wrap:wrap;">
    <a href="{{ route('portal.estudiante.evaluaciones.index') }}"
       style="color:#6366f1;text-decoration:none;font-size:.8rem;font-weight:600;display:flex;align-items:center;gap:.3rem;">
        <i class="bi bi-arrow-left"></i>Evaluaciones
    </a>
    <span style="color:#cbd5e1;">›</span>
    <h2 style="font-size:1rem;font-weight:800;margin:0;">{{ $quiz->titulo }}</h2>
</div>

{{-- Tarjeta resultado --}}
<div class="prt-card" style="text-align:center;padding:1.8rem;margin-bottom:1.2rem;">
    @php $pct = $intento->porcentaje; $aprobado = $pct >= 60; @endphp
    <div style="width:70px;height:70px;border-radius:50%;background:{{ $aprobado ? '#dcfce7' : '#fee2e2' }};display:flex;align-items:center;justify-content:center;margin:0 auto .8rem;">
        <i class="bi bi-{{ $aprobado ? 'trophy-fill' : 'x-circle-fill' }}" style="font-size:2rem;color:{{ $aprobado ? '#10b981' : '#ef4444' }};"></i>
    </div>
    <div style="font-size:2rem;font-weight:900;color:{{ $aprobado ? '#10b981' : '#ef4444' }};">{{ $pct }}%</div>
    <div style="font-size:.9rem;font-weight:700;margin:.2rem 0;">{{ $aprobado ? 'Aprobado' : 'No aprobado' }}</div>
    <div style="font-size:.8rem;color:#64748b;">
        {{ $intento->puntuacion }} / {{ $intento->puntuacion_max }} puntos
        @if($intento->duracion)· {{ $intento->duracion }}@endif
    </div>
</div>

{{-- Detalle por pregunta (solo si el quiz lo permite) --}}
@if($quiz->mostrar_resultados)
<div class="prt-card" style="padding:1rem;">
    <div class="prt-card-header" style="margin-bottom:.8rem;">
        <i class="bi bi-list-check me-2" style="color:#6366f1;"></i>Detalle de respuestas
    </div>

    @foreach($quiz->preguntas as $i => $p)
    @php
        $resp     = ($intento->respuestas ?? [])[$p->id] ?? null;
        $esCor    = $resp ? ($resp['correcta'] ?? false) : false;
        $idxResp  = $resp ? ($resp['valor'] ?? null) : null;
        $idxCorr  = $p->opcionCorrecta();
    @endphp
    <div class="preg-res">
        <div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:.4rem;gap:.5rem;">
            <span style="font-size:.78rem;font-weight:700;color:#475569;">Pregunta {{ $i+1 }}</span>
            @if($p->tipo !== 'abierta')
                @if($esCor)
                    <span style="font-size:.72rem;font-weight:700;color:#10b981;"><i class="bi bi-check-circle-fill me-1"></i>Correcta · +{{ $p->puntos }} pts</span>
                @elseif($idxResp !== null)
                    <span style="font-size:.72rem;font-weight:700;color:#ef4444;"><i class="bi bi-x-circle-fill me-1"></i>Incorrecta · 0 pts</span>
                @else
                    <span style="font-size:.72rem;font-weight:700;color:#94a3b8;"><i class="bi bi-dash-circle me-1"></i>Sin responder</span>
                @endif
            @else
                <span style="font-size:.72rem;font-weight:700;color:#f59e0b;"><i class="bi bi-pencil-fill me-1"></i>Revisión manual</span>
            @endif
        </div>
        <p style="font-size:.85rem;font-weight:600;margin:0 0 .5rem;">{{ $p->enunciado }}</p>

        @if($p->opciones)
            @foreach($p->opciones as $j => $op)
            @php
                $esCorrecta   = !empty($op['correcta']);
                $fuiYo        = (string)$j === (string)$idxResp;
            @endphp
            <div class="opcion-r {{ $esCorrecta ? 'correcta' : ($fuiYo && !$esCorrecta ? 'error' : '') }} {{ ($fuiYo && $esCorrecta) ? 'respondida' : '' }}">
                <i class="bi bi-{{ $esCorrecta ? 'check-circle-fill' : ($fuiYo ? 'x-circle-fill' : 'circle') }}"
                   style="color:{{ $esCorrecta ? '#10b981' : ($fuiYo ? '#ef4444' : '#cbd5e1') }};font-size:.8rem;flex-shrink:0;"></i>
                {{ $op['texto'] }}
                @if($fuiYo && !$esCorrecta) <span style="margin-left:auto;font-size:.7rem;color:#ef4444;font-weight:700;">Tu respuesta</span> @endif
                @if($esCorrecta) <span style="margin-left:auto;font-size:.7rem;color:#10b981;font-weight:700;">Correcta</span> @endif
            </div>
            @endforeach
        @elseif($p->tipo === 'abierta' && $idxResp)
            <div style="background:#f8fafc;border-radius:8px;padding:.6rem .8rem;font-size:.82rem;color:#475569;border-left:3px solid #6366f1;">
                {{ $idxResp }}
            </div>
        @endif

        @if($p->explicacion)
        <div style="margin-top:.4rem;font-size:.72rem;color:#64748b;font-style:italic;background:#fefce8;border-radius:6px;padding:.4rem .6rem;">
            <i class="bi bi-lightbulb me-1;color:#ca8a04;"></i>{{ $p->explicacion }}
        </div>
        @endif
    </div>
    @endforeach
</div>
@else
<div class="prt-card" style="text-align:center;padding:1.5rem;color:#64748b;">
    <i class="bi bi-eye-slash" style="font-size:1.5rem;display:block;margin-bottom:.4rem;"></i>
    <p style="margin:0;font-size:.85rem;">El docente no ha habilitado la visualización de respuestas.</p>
</div>
@endif

<div style="text-align:center;margin-top:1.2rem;">
    <a href="{{ route('portal.estudiante.evaluaciones.index') }}"
       style="background:#6366f1;color:#fff;border:none;border-radius:10px;padding:.65rem 1.8rem;font-size:.88rem;font-weight:700;text-decoration:none;display:inline-flex;align-items:center;gap:.5rem;">
        <i class="bi bi-arrow-left"></i>Volver a Evaluaciones
    </a>
</div>

@endsection
