@extends('layouts.portal')
@section('page-title', 'Intento — '.$quiz->titulo)
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
.preg-card {
    border:1px solid #e2e8f0;border-radius:10px;padding:1rem;margin-bottom:.9rem;
}
.preg-card.correcta  { border-left:4px solid #10b981; }
.preg-card.incorrecta{ border-left:4px solid #ef4444; }
.preg-card.abierta   { border-left:4px solid #f59e0b; }
.preg-card.sin-resp  { border-left:4px solid #cbd5e1; }
.opcion-row {
    display:flex;align-items:center;gap:.5rem;padding:.3rem .5rem;
    border-radius:6px;font-size:.8rem;margin-bottom:.2rem;
}
.opcion-row.sel-correcta  { background:#d1fae5;color:#065f46; }
.opcion-row.sel-incorrecta{ background:#fee2e2;color:#991b1b; }
.opcion-row.correcta-no-sel{ background:#f0fdf4;color:#166534;font-weight:600; }
.opcion-row.normal        { color:#475569; }
.score-input {
    width:70px;border:2px solid #e2e8f0;border-radius:6px;padding:.2rem .4rem;
    font-size:.85rem;font-weight:700;text-align:center;
    transition:border-color .2s;
}
.score-input:focus { outline:none;border-color:#6366f1; }
.score-saved { color:#10b981;font-size:.75rem;font-weight:700;display:none; }
</style>
@endpush

@section('content')

{{-- Header --}}
<div style="display:flex;align-items:center;gap:.7rem;margin-bottom:1rem;flex-wrap:wrap;">
    <a href="{{ route('portal.docente.evaluaciones.resultados', [$asignacion, $quiz]) }}"
       style="color:#6366f1;text-decoration:none;font-size:.8rem;font-weight:600;display:flex;align-items:center;gap:.3rem;">
        <i class="bi bi-arrow-left"></i>Resultados
    </a>
    <span style="color:#cbd5e1;">›</span>
    <h2 style="font-size:.95rem;font-weight:800;margin:0;flex:1;">
        {{ $intento->matricula?->estudiante?->nombre_completo ?? '—' }}
    </h2>
    @if($pendientesRevision > 0)
    <span style="background:#f59e0b;color:#fff;border-radius:99px;font-size:.68rem;font-weight:700;padding:.2rem .6rem;">
        <i class="bi bi-pencil-fill"></i> {{ $pendientesRevision }} por calificar
    </span>
    @endif
</div>

{{-- Info del intento --}}
<div class="prt-card" style="margin-bottom:1rem;padding:.8rem 1rem;">
    <div style="display:flex;flex-wrap:wrap;gap:1.2rem;align-items:center;">
        <div>
            <div style="font-size:.68rem;color:#64748b;text-transform:uppercase;letter-spacing:.04em;">Evaluación</div>
            <div style="font-size:.85rem;font-weight:700;">{{ $quiz->titulo }}</div>
        </div>
        <div>
            <div style="font-size:.68rem;color:#64748b;text-transform:uppercase;letter-spacing:.04em;">Enviado</div>
            <div style="font-size:.82rem;">{{ $intento->finalizado_en?->format('d/m/Y H:i') ?? '—' }}</div>
        </div>
        <div>
            <div style="font-size:.68rem;color:#64748b;text-transform:uppercase;letter-spacing:.04em;">Duración</div>
            <div style="font-size:.82rem;">{{ $intento->duracion ?? '—' }}</div>
        </div>
        <div style="margin-left:auto;text-align:right;">
            <div style="font-size:.68rem;color:#64748b;text-transform:uppercase;letter-spacing:.04em;">Puntaje</div>
            <div id="total-score" style="font-size:1.4rem;font-weight:800;color:{{ $intento->porcentaje >= 60 ? '#10b981' : '#ef4444' }};">
                {{ $intento->puntuacion }} / {{ $quiz->puntaje_total }}
            </div>
            <div id="total-pct" style="font-size:.8rem;font-weight:700;color:{{ $intento->porcentaje >= 60 ? '#10b981' : '#ef4444' }};">
                {{ $intento->porcentaje }}%
                — @if($intento->porcentaje >= 60) Aprobado @else No aprobado @endif
            </div>
        </div>
    </div>
</div>

{{-- Preguntas --}}
@php
    $respuestas = $intento->respuestas ?? [];
    $puntajeMax = $quiz->puntaje_total;
@endphp

@foreach($quiz->preguntas as $idx => $pregunta)
@php
    $resp  = $respuestas[$pregunta->id] ?? null;
    $valor = $resp['valor'] ?? null;
    $esCor = $resp['correcta'] ?? false;
    $pts   = $resp['puntos'] ?? 0;
    $sinResp = ($valor === null || $valor === '');

    if ($pregunta->tipo === 'abierta') {
        $cardClass = 'abierta';
    } elseif ($sinResp) {
        $cardClass = 'sin-resp';
    } elseif ($esCor) {
        $cardClass = 'correcta';
    } else {
        $cardClass = 'incorrecta';
    }
@endphp
<div class="preg-card {{ $cardClass }}">
    {{-- Cabecera de la pregunta --}}
    <div style="display:flex;align-items:flex-start;gap:.6rem;margin-bottom:.6rem;">
        <span style="background:#e2e8f0;color:#475569;border-radius:99px;font-size:.68rem;font-weight:700;padding:.15rem .5rem;white-space:nowrap;margin-top:.1rem;">
            P{{ $idx + 1 }}
        </span>
        <div style="flex:1;">
            <span style="font-size:.82rem;font-weight:600;color:#1e293b;">{{ $pregunta->enunciado }}</span>
        </div>
        <div style="text-align:right;white-space:nowrap;">
            @if($pregunta->tipo === 'multiple')
                <span style="background:#e0e7ff;color:#4338ca;border-radius:4px;font-size:.65rem;font-weight:700;padding:.1rem .35rem;">Múltiple</span>
            @elseif($pregunta->tipo === 'verdadero_falso')
                <span style="background:#dcfce7;color:#166534;border-radius:4px;font-size:.65rem;font-weight:700;padding:.1rem .35rem;">V/F</span>
            @else
                <span style="background:#fef3c7;color:#92400e;border-radius:4px;font-size:.65rem;font-weight:700;padding:.1rem .35rem;">Abierta</span>
            @endif
            <div style="font-size:.7rem;color:#64748b;margin-top:.15rem;">{{ $pregunta->puntos }} pt{{ $pregunta->puntos != 1 ? 's' : '' }}</div>
        </div>
    </div>

    {{-- Respuesta según tipo --}}
    @if($pregunta->tipo === 'abierta')
        {{-- Respuesta de texto libre --}}
        <div style="background:#fffbeb;border:1px solid #fde68a;border-radius:7px;padding:.6rem .8rem;margin-bottom:.7rem;font-size:.82rem;color:#1e293b;min-height:40px;">
            @if($sinResp)
                <em style="color:#94a3b8;">Sin respuesta</em>
            @else
                {{ $valor }}
            @endif
        </div>

        {{-- Calificación manual --}}
        <div style="display:flex;align-items:center;gap:.7rem;">
            <label style="font-size:.75rem;font-weight:600;color:#475569;">Puntaje asignado:</label>
            <input
                type="number"
                class="score-input"
                id="score-{{ $pregunta->id }}"
                value="{{ $pts }}"
                min="0"
                max="{{ $pregunta->puntos }}"
                step="0.5"
                @if($sinResp) disabled @endif
                data-pregunta="{{ $pregunta->id }}"
                data-max="{{ $pregunta->puntos }}"
                onchange="guardarPuntos(this)"
            >
            <span style="font-size:.75rem;color:#94a3b8;">/ {{ $pregunta->puntos }}</span>
            <span class="score-saved" id="saved-{{ $pregunta->id }}">
                <i class="bi bi-check-circle-fill"></i> Guardado
            </span>
            @if($sinResp)
            <span style="font-size:.72rem;color:#94a3b8;font-style:italic;">Sin respuesta — no calificable</span>
            @endif
        </div>

    @elseif($pregunta->tipo === 'verdadero_falso')
        @foreach($pregunta->opciones ?? [] as $oi => $opcion)
        @php
            $esLaRespuesta = ((string)$oi === (string)$valor || (string)$oi === (string)($valor ?? ''));
            $esCorrecta    = $opcion['correcta'] ?? false;
            if ($esLaRespuesta && $esCorrecta)       $cls = 'sel-correcta';
            elseif ($esLaRespuesta && ! $esCorrecta) $cls = 'sel-incorrecta';
            elseif (! $esLaRespuesta && $esCorrecta) $cls = 'correcta-no-sel';
            else                                     $cls = 'normal';
        @endphp
        <div class="opcion-row {{ $cls }}">
            @if($esLaRespuesta && $esCorrecta)    <i class="bi bi-check-circle-fill" style="color:#10b981;"></i>
            @elseif($esLaRespuesta)               <i class="bi bi-x-circle-fill"     style="color:#ef4444;"></i>
            @elseif($esCorrecta)                  <i class="bi bi-check-circle"      style="color:#10b981;"></i>
            @else                                 <i class="bi bi-circle"            style="color:#cbd5e1;"></i>
            @endif
            {{ $opcion['texto'] }}
            @if($esLaRespuesta) <span style="font-size:.68rem;margin-left:.3rem;">(respuesta del estudiante)</span> @endif
        </div>
        @endforeach
        <div style="margin-top:.4rem;font-size:.75rem;font-weight:700;color:{{ $esCor ? '#10b981' : ($sinResp ? '#94a3b8' : '#ef4444') }};">
            @if($sinResp)   Sin responder — 0 pts
            @elseif($esCor) Correcto — {{ $pts }} pts
            @else           Incorrecto — 0 pts
            @endif
        </div>

    @else
        {{-- Opción múltiple --}}
        @foreach($pregunta->opciones ?? [] as $oi => $opcion)
        @php
            $esLaRespuesta = ((string)$oi === (string)$valor);
            $esCorrecta    = $opcion['correcta'] ?? false;
            if ($esLaRespuesta && $esCorrecta)       $cls = 'sel-correcta';
            elseif ($esLaRespuesta && ! $esCorrecta) $cls = 'sel-incorrecta';
            elseif (! $esLaRespuesta && $esCorrecta) $cls = 'correcta-no-sel';
            else                                     $cls = 'normal';
        @endphp
        <div class="opcion-row {{ $cls }}">
            @if($esLaRespuesta && $esCorrecta)    <i class="bi bi-check-circle-fill" style="color:#10b981;"></i>
            @elseif($esLaRespuesta)               <i class="bi bi-x-circle-fill"     style="color:#ef4444;"></i>
            @elseif($esCorrecta)                  <i class="bi bi-check-circle"      style="color:#10b981;"></i>
            @else                                 <i class="bi bi-circle"            style="color:#cbd5e1;"></i>
            @endif
            {{ $opcion['texto'] }}
            @if($esLaRespuesta) <span style="font-size:.68rem;margin-left:.3rem;">(respuesta del estudiante)</span> @endif
        </div>
        @endforeach
        <div style="margin-top:.4rem;font-size:.75rem;font-weight:700;color:{{ $esCor ? '#10b981' : ($sinResp ? '#94a3b8' : '#ef4444') }};">
            @if($sinResp)   Sin responder — 0 pts
            @elseif($esCor) Correcto — {{ $pts }} pts
            @else           Incorrecto — 0 pts
            @endif
        </div>
    @endif

    {{-- Explicación --}}
    @if($pregunta->explicacion)
    <div style="margin-top:.6rem;background:#f8fafc;border-left:3px solid #6366f1;padding:.4rem .7rem;border-radius:0 5px 5px 0;font-size:.76rem;color:#475569;">
        <strong>Explicación:</strong> {{ $pregunta->explicacion }}
    </div>
    @endif
</div>
@endforeach

@endsection

@push('scripts')
<script>
const CALIFICAR_URL = @json(route('portal.docente.evaluaciones.intento.calificar', [$asignacion, $quiz, $intento]));
const CSRF = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

function guardarPuntos(input) {
    const pregId = input.dataset.pregunta;
    const max    = parseFloat(input.dataset.max);
    let val      = parseFloat(input.value);
    if (isNaN(val) || val < 0) val = 0;
    if (val > max)             val = max;
    input.value = val;

    fetch(CALIFICAR_URL, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': CSRF,
            'Accept': 'application/json',
        },
        body: JSON.stringify({ pregunta_id: parseInt(pregId), puntos: val }),
    })
    .then(r => r.json())
    .then(data => {
        if (data.ok) {
            // Actualizar totales en el header
            document.getElementById('total-score').textContent =
                data.puntuacion + ' / ' + document.getElementById('total-score').textContent.split('/')[1].trim();
            document.getElementById('total-pct').textContent =
                data.porcentaje + '% — ' + (data.porcentaje >= 60 ? 'Aprobado' : 'No aprobado');

            const saved = document.getElementById('saved-' + pregId);
            if (saved) {
                saved.style.display = 'inline';
                setTimeout(() => { saved.style.display = 'none'; }, 2500);
            }
        }
    })
    .catch(() => {});
}
</script>
@endpush
