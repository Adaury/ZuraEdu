@extends('layouts.portal-estudiante')
@section('title', $quiz->titulo)
@section('activeKey', 'evaluaciones')

@push('styles')
<style>
.pregunta-block {
    background:#fff;border:2px solid #e2e8f0;border-radius:12px;
    padding:1.1rem 1.2rem;margin-bottom:.8rem;
    scroll-margin-top:80px;transition:border-color .2s;
}
.pregunta-block.respondida { border-color:#10b981; }
.opcion-label {
    display:flex;align-items:center;gap:.65rem;
    padding:.55rem .8rem;border-radius:8px;cursor:pointer;
    border:1.5px solid #e2e8f0;margin-bottom:.4rem;
    transition:.15s;font-size:.85rem;
}
.opcion-label:hover { background:#ede9fe;border-color:#a5b4fc; }
.opcion-label.seleccionada { background:#ede9fe;border-color:#6366f1;font-weight:700; }
#cronometro {
    font-size:1rem;font-weight:800;padding:.3rem .75rem;
    border-radius:8px;background:#f1f5f9;
}
#cronometro.urgente { background:#fee2e2;color:#dc2626;animation: pulso .8s infinite; }
@keyframes pulso { 0%,100% { opacity:1; } 50% { opacity:.5; } }
.nav-pregunta {
    width:32px;height:32px;border-radius:7px;border:1.5px solid #e2e8f0;
    display:inline-flex;align-items:center;justify-content:center;
    font-size:.75rem;font-weight:700;cursor:pointer;background:#fff;
    transition:.15s;color:#475569;
}
.nav-pregunta.ok { background:#dcfce7;border-color:#86efac;color:#166534; }
.nav-pregunta.actual { background:#6366f1;border-color:#6366f1;color:#fff; }
</style>
@endpush

@section('content')

{{-- Barra superior fija --}}
<div style="background:#fff;border-bottom:2px solid #e2e8f0;padding:.6rem 1rem;margin:-1rem -1rem 1rem;display:flex;align-items:center;justify-content:space-between;gap:.5rem;flex-wrap:wrap;position:sticky;top:0;z-index:100;">
    <div style="font-weight:800;font-size:.9rem;max-width:50%;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
        <i class="bi bi-patch-question-fill me-1" style="color:#6366f1;"></i>{{ $quiz->titulo }}
    </div>
    <div style="display:flex;align-items:center;gap:.7rem;">
        @if($segundosRestantes !== null)
        <div id="cronometro" data-seconds="{{ (int) $segundosRestantes }}">
            <i class="bi bi-clock me-1"></i><span id="tiempoTexto">--:--</span>
        </div>
        @endif
        <span style="font-size:.78rem;color:#64748b;"><span id="respondidas">0</span>/{{ $quiz->preguntas->count() }} respondidas</span>
        <button type="button" onclick="confirmarEnviar()"
            style="background:#6366f1;color:#fff;border:none;border-radius:8px;padding:.4rem .9rem;font-size:.8rem;font-weight:700;cursor:pointer;display:flex;align-items:center;gap:.35rem;">
            <i class="bi bi-send-fill"></i>Entregar
        </button>
    </div>
</div>

{{-- Navegación de preguntas --}}
<div style="display:flex;gap:.35rem;flex-wrap:wrap;margin-bottom:1rem;" id="navPregs">
    @foreach($quiz->preguntas as $i => $p)
    <button class="nav-pregunta" id="nav-{{ $p->id }}" onclick="irA({{ $p->id }})">{{ $i+1 }}</button>
    @endforeach
</div>

{{-- Preguntas --}}
<form id="formQuiz">
    @csrf
    @foreach($quiz->preguntas as $i => $p)
    <div class="pregunta-block" id="bloque-{{ $p->id }}">
        <div style="font-size:.72rem;font-weight:700;color:#94a3b8;margin-bottom:.3rem;">
            Pregunta {{ $i+1 }} · {{ $p->puntos }} {{ $p->puntos == 1 ? 'punto' : 'puntos' }}
        </div>
        <p style="font-size:.9rem;font-weight:700;margin:0 0 .75rem;">{{ $p->enunciado }}</p>

        @if($p->tipo === 'multiple' || $p->tipo === 'verdadero_falso')
            @foreach($p->opciones ?? [] as $j => $op)
            <label class="opcion-label" id="lbl-{{ $p->id }}-{{ $j }}" onclick="seleccionarOpcion({{ $p->id }}, {{ $j }}, this)">
                <input type="radio" name="resp_{{ $p->id }}" value="{{ $j }}"
                    style="display:none;" id="radio-{{ $p->id }}-{{ $j }}">
                <span style="width:22px;height:22px;border-radius:50%;border:2px solid #cbd5e1;display:inline-flex;align-items:center;justify-content:center;flex-shrink:0;font-size:.7rem;font-weight:800;" id="circulo-{{ $p->id }}-{{ $j }}">
                    {{ chr(65+$j) }}
                </span>
                {{ $op['texto'] }}
            </label>
            @endforeach
        @else
            <textarea name="resp_{{ $p->id }}" rows="4" placeholder="Escribe tu respuesta..."
                onchange="guardarAbierta({{ $p->id }}, this.value)"
                style="width:100%;border:1.5px solid #e2e8f0;border-radius:8px;padding:.55rem .75rem;font-size:.85rem;resize:vertical;"></textarea>
        @endif
    </div>
    @endforeach
</form>

<div style="display:flex;justify-content:flex-end;margin-top:1rem;">
    <button type="button" onclick="confirmarEnviar()"
        style="background:#6366f1;color:#fff;border:none;border-radius:10px;padding:.65rem 1.8rem;font-size:.88rem;font-weight:800;cursor:pointer;display:flex;align-items:center;gap:.45rem;">
        <i class="bi bi-send-fill"></i>Entregar Evaluación
    </button>
</div>

{{-- Modal confirmación --}}
<div id="modalConfirmar" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:9999;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:14px;padding:1.5rem;max-width:380px;width:90%;box-shadow:0 20px 60px rgba(0,0,0,.2);text-align:center;">
        <i class="bi bi-send-fill" style="font-size:2rem;color:#6366f1;display:block;margin-bottom:.75rem;"></i>
        <h3 style="margin:0 0 .5rem;font-size:.95rem;font-weight:800;">¿Entregar evaluación?</h3>
        <p style="font-size:.82rem;color:#64748b;margin:0 0 1.2rem;">
            Has respondido <strong id="modalRespondidas">0</strong>/{{ $quiz->preguntas->count() }} preguntas. Esta acción no se puede deshacer.
        </p>
        <div style="display:flex;gap:.5rem;justify-content:center;">
            <button onclick="document.getElementById('modalConfirmar').style.display='none'"
                style="background:#f1f5f9;color:#475569;border:none;border-radius:8px;padding:.55rem 1.1rem;font-size:.82rem;font-weight:600;cursor:pointer;">
                Cancelar
            </button>
            <button onclick="enviarDefinitivo()"
                style="background:#6366f1;color:#fff;border:none;border-radius:8px;padding:.55rem 1.4rem;font-size:.82rem;font-weight:700;cursor:pointer;">
                <i class="bi bi-send-fill me-1"></i>Sí, entregar
            </button>
        </div>
    </div>
</div>

@push('scripts')
<script>
const CSRF     = '{{ csrf_token() }}';
const INT_ID   = {{ $intento->id }};
const URL_GUARD= '{{ route("portal.estudiante.evaluaciones.guardar", $intento) }}';
const URL_ENV  = '{{ route("portal.estudiante.evaluaciones.enviar", $intento) }}';
const respuestas = {};

function seleccionarOpcion(pregId, idx, lbl) {
    // Desmarcar anteriores
    document.querySelectorAll(`[id^="lbl-${pregId}-"]`).forEach(l => {
        l.classList.remove('seleccionada');
        const circulo = l.querySelector(`[id^="circulo-${pregId}-"]`);
        if (circulo) { circulo.style.background=''; circulo.style.color=''; circulo.style.borderColor='#cbd5e1'; }
    });
    // Marcar nueva
    lbl.classList.add('seleccionada');
    const circulo = document.getElementById(`circulo-${pregId}-${idx}`);
    if (circulo) { circulo.style.background='#6366f1'; circulo.style.color='#fff'; circulo.style.borderColor='#6366f1'; }
    document.getElementById(`radio-${pregId}-${idx}`).checked = true;

    respuestas[pregId] = idx;
    guardarServidor(pregId, idx);
    marcarBloqueOk(pregId);
}

function guardarAbierta(pregId, val) {
    respuestas[pregId] = val;
    guardarServidor(pregId, val);
    marcarBloqueOk(pregId);
}

async function guardarServidor(pregId, val) {
    try {
        await fetch(URL_GUARD, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
            body: JSON.stringify({ pregunta_id: pregId, respuesta: val })
        });
    } catch(e) { /* silent */ }
}

function marcarBloqueOk(pregId) {
    document.getElementById('bloque-' + pregId)?.classList.add('respondida');
    document.getElementById('nav-' + pregId)?.classList.add('ok');
    actualizarContador();
}

function actualizarContador() {
    const n = Object.keys(respuestas).length;
    document.getElementById('respondidas').textContent = n;
    document.getElementById('modalRespondidas').textContent = n;
}

function irA(pregId) {
    document.getElementById('bloque-' + pregId)?.scrollIntoView({ behavior: 'smooth' });
    document.querySelectorAll('.nav-pregunta').forEach(b => b.classList.remove('actual'));
    document.getElementById('nav-' + pregId)?.classList.add('actual');
}

function confirmarEnviar() {
    actualizarContador();
    document.getElementById('modalConfirmar').style.display = 'flex';
}

function enviarDefinitivo() {
    document.getElementById('modalConfirmar').style.display = 'none';
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = URL_ENV;
    const csrf = document.createElement('input');
    csrf.type  = 'hidden'; csrf.name = '_token'; csrf.value = CSRF;
    form.appendChild(csrf);
    document.body.appendChild(form);
    form.submit();
}

// Cronómetro
@if($segundosRestantes !== null)
(function() {
    let secs = {{ (int) $segundosRestantes }};
    const el = document.getElementById('tiempoTexto');
    const box = document.getElementById('cronometro');
    function tick() {
        if (secs <= 0) { enviarDefinitivo(); return; }
        const m = Math.floor(secs / 60).toString().padStart(2,'0');
        const s = (secs % 60).toString().padStart(2,'0');
        el.textContent = m + ':' + s;
        if (secs <= 60) box.classList.add('urgente');
        secs--;
        setTimeout(tick, 1000);
    }
    tick();
})();
@endif
</script>
@endpush

@endsection
