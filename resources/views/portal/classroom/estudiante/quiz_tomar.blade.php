@extends('layouts.portal')
@section('title', 'Quiz — '.$material->titulo)
@section('content')

@php $color = $claseVirtual->portada_color ?? '#4f46e5'; @endphp

{{-- Barra superior con tiempo --}}
<div class="d-flex align-items-center justify-content-between mb-4 p-3 rounded-3" style="background:#1e3a6e;color:#fff;position:sticky;top:0;z-index:100;">
    <div>
        <div class="fw-bold">{{ $material->titulo }}</div>
        <small class="opacity-75">{{ $quiz->preguntas->count() }} preguntas</small>
    </div>
    @if($tiempoRestante !== null)
    <div class="text-center">
        <div id="temporizador" style="font-size:1.5rem;font-weight:900;font-family:monospace;color:#fbbf24;" data-segundos="{{ $tiempoRestante }}">
            {{ floor($tiempoRestante/60) }}:{{ str_pad($tiempoRestante%60,2,'0',STR_PAD_LEFT) }}
        </div>
        <small class="opacity-75">Tiempo restante</small>
    </div>
    @endif
    <div>
        <span class="badge bg-light text-dark" id="respondidas-badge">0/{{ $quiz->preguntas->count() }}</span>
    </div>
</div>

<form method="POST" action="{{ route('portal.estudiante.quiz.enviar', [$claseVirtual, $material, $intento]) }}" id="formQuiz">
@csrf

@foreach($quiz->preguntas as $i => $pregunta)
<div class="card border-0 shadow-sm mb-4 pregunta-card" style="border-radius:14px;" id="pregunta-{{ $pregunta->id }}">
    <div class="card-body p-4">
        <div class="d-flex align-items-start gap-3 mb-3">
            <div style="width:32px;height:32px;background:{{ $color }};border-radius:8px;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:800;font-size:.85rem;flex-shrink:0;">
                {{ $i + 1 }}
            </div>
            <div class="flex-grow-1">
                <p class="fw-semibold mb-1" style="font-size:.95rem;">{{ $pregunta->enunciado }}</p>
                <small class="text-muted">{{ $pregunta->puntos }} punto(s)</small>
            </div>
        </div>

        @if($pregunta->tipo === 'multiple')
        <div class="d-flex flex-column gap-2">
            @foreach($pregunta->opciones as $opcion)
            @php $prev = $respuestasGuardadas->get($pregunta->id); @endphp
            <label class="opcion-label d-flex align-items-center gap-3 p-3 rounded-3" style="cursor:pointer;border:2px solid #E5E7EB;transition:.15s;" onmouseover="this.style.borderColor='{{ $color }}'" onmouseout="if(!this.querySelector('input').checked) this.style.borderColor='#E5E7EB'">
                <input type="radio" name="pregunta_{{ $pregunta->id }}" value="{{ $opcion->id }}"
                       class="form-check-input opcion-radio" style="flex-shrink:0;cursor:pointer;"
                       data-pregunta="{{ $pregunta->id }}"
                       {{ ($prev && $prev->opcion_id == $opcion->id) ? 'checked' : '' }}
                       onchange="seleccionarOpcion(this)">
                <span style="font-size:.9rem;">{{ $opcion->texto }}</span>
            </label>
            @endforeach
        </div>

        @elseif($pregunta->tipo === 'verdadero_falso')
        @php $prev = $respuestasGuardadas->get($pregunta->id); @endphp
        <div class="d-flex gap-3">
            @foreach($pregunta->opciones as $opcion)
            <label class="opcion-label flex-fill d-flex align-items-center justify-content-center gap-2 p-3 rounded-3 text-center" style="cursor:pointer;border:2px solid #E5E7EB;transition:.15s;">
                <input type="radio" name="pregunta_{{ $pregunta->id }}" value="{{ $opcion->id }}"
                       class="form-check-input opcion-radio" data-pregunta="{{ $pregunta->id }}"
                       {{ ($prev && $prev->opcion_id == $opcion->id) ? 'checked' : '' }}
                       onchange="seleccionarOpcion(this)">
                <span class="fw-semibold">{{ $opcion->texto }}</span>
            </label>
            @endforeach
        </div>

        @elseif($pregunta->tipo === 'abierta')
        @php $prev = $respuestasGuardadas->get($pregunta->id); @endphp
        <textarea name="pregunta_{{ $pregunta->id }}" class="form-control" rows="4"
                  placeholder="Escribe tu respuesta aquí..."
                  data-pregunta="{{ $pregunta->id }}"
                  onchange="marcarRespondida({{ $pregunta->id }})"
                  oninput="guardarTexto(this)">{{ $prev?->texto_respuesta }}</textarea>
        @endif
    </div>
</div>
@endforeach

<div class="card border-0 shadow-sm" style="border-radius:14px;position:sticky;bottom:16px;">
    <div class="card-body d-flex align-items-center justify-content-between gap-3">
        <div class="text-muted small">
            <span id="msg-respondidas">Responde todas las preguntas antes de enviar</span>
        </div>
        <button type="submit" class="btn btn-success fw-bold px-4 py-2" style="border-radius:10px;"
                onclick="return confirm('¿Confirmas que deseas enviar el quiz? No podrás modificar tus respuestas.')">
            <i class="bi bi-send-fill me-2"></i>Enviar Quiz
        </button>
    </div>
</div>

</form>

@push('scripts')
<script>
const totalPreguntas = {{ $quiz->preguntas->count() }};
const respondidas = new Set();

// Pre-cargar respondidas
document.querySelectorAll('input[type=radio]:checked, textarea').forEach(el => {
    const pid = el.dataset.pregunta;
    if (pid && (el.checked || el.value.trim())) respondidas.add(pid);
});
actualizarContador();

function seleccionarOpcion(input) {
    const pid = input.dataset.pregunta;
    respondidas.add(String(pid));
    actualizarContador();
    // Guardar via AJAX
    fetch('{{ route('portal.estudiante.quiz.guardar', $intento) }}', {
        method: 'POST',
        headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}','Content-Type':'application/json'},
        body: JSON.stringify({pregunta_id: pid, opcion_id: input.value})
    });
    // Estilo visual
    document.querySelectorAll(`[data-pregunta="${pid}"]`).forEach(r => {
        r.closest('.opcion-label').style.borderColor = '#E5E7EB';
        r.closest('.opcion-label').style.background = '';
    });
    input.closest('.opcion-label').style.borderColor = '{{ $color }}';
    input.closest('.opcion-label').style.background = '{{ $color }}18';
}

function guardarTexto(textarea) {
    const pid = textarea.dataset.pregunta;
    if (textarea.value.trim()) respondidas.add(String(pid));
    else respondidas.delete(String(pid));
    actualizarContador();
}

function marcarRespondida(pid) {
    respondidas.add(String(pid));
    actualizarContador();
}

function actualizarContador() {
    document.getElementById('respondidas-badge').textContent = respondidas.size + '/' + totalPreguntas;
    const todas = respondidas.size === totalPreguntas;
    document.getElementById('msg-respondidas').textContent = todas
        ? '✓ Todas las preguntas respondidas'
        : `Faltan ${totalPreguntas - respondidas.size} por responder`;
}

// Inicializar estados visuales de opciones pre-seleccionadas
document.querySelectorAll('input[type=radio]:checked').forEach(input => {
    if (input.closest('.opcion-label')) {
        input.closest('.opcion-label').style.borderColor = '{{ $color }}';
        input.closest('.opcion-label').style.background = '{{ $color }}18';
    }
});

@if($tiempoRestante !== null)
// Temporizador
let segundos = {{ $tiempoRestante }};
const el = document.getElementById('temporizador');
const timer = setInterval(() => {
    segundos--;
    if (segundos <= 0) {
        clearInterval(timer);
        el.textContent = '0:00';
        el.style.color = '#EF4444';
        document.getElementById('formQuiz').submit();
        return;
    }
    const m = Math.floor(segundos / 60);
    const s = String(segundos % 60).padStart(2, '0');
    el.textContent = m + ':' + s;
    if (segundos <= 60) el.style.color = '#EF4444';
    else if (segundos <= 180) el.style.color = '#F59E0B';
}, 1000);
@endif
</script>
@endpush

@endsection
