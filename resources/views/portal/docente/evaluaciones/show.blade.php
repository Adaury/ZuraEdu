@extends('layouts.portal-docente')
@section('title', $quiz->titulo)
@section('activeKey', 'evaluaciones')

@push('styles')
<style>
.preg-card {
    background:#fff;border:1.5px solid #e2e8f0;border-radius:10px;
    padding:.85rem 1rem;margin-bottom:.6rem;
    border-left:3px solid #6366f1;
}
.opcion-row {
    display:flex;align-items:center;gap:.5rem;
    margin-bottom:.4rem;font-size:.8rem;
}
.opcion-correcta { color:#10b981;font-weight:700; }
.modal-overlay {
    display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);
    z-index:1000;align-items:center;justify-content:center;
}
.modal-overlay.active { display:flex; }
.modal-box {
    background:#fff;border-radius:14px;padding:1.5rem;
    width:100%;max-width:540px;max-height:92vh;overflow-y:auto;
    box-shadow:0 20px 60px rgba(0,0,0,.2);
}
.tab-btn {
    padding:.45rem 1rem;border:none;border-radius:8px;
    font-size:.8rem;font-weight:700;cursor:pointer;
    background:#f1f5f9;color:#64748b;transition:.15s;
}
.tab-btn.active { background:#6366f1;color:#fff; }
.spinner { display:none; }
.spinner.show { display:inline-block; }
</style>
@endpush

@section('content')

{{-- Header --}}
<div style="display:flex;align-items:center;gap:.7rem;margin-bottom:1rem;flex-wrap:wrap;">
    <a href="{{ route('portal.docente.evaluaciones.index', $asignacion) }}"
       style="color:#6366f1;text-decoration:none;font-size:.8rem;font-weight:600;display:flex;align-items:center;gap:.3rem;">
        <i class="bi bi-arrow-left"></i>Evaluaciones
    </a>
    <span style="color:#cbd5e1;">›</span>
    <h2 style="font-size:1rem;font-weight:800;margin:0;flex:1;">{{ $quiz->titulo }}</h2>
    @if($quiz->publicado)
        <span style="background:#10b981;color:#fff;border-radius:99px;padding:.2rem .65rem;font-size:.68rem;font-weight:700;display:inline-flex;align-items:center;gap:.3rem;">
            <i class="bi bi-broadcast"></i>Publicada
        </span>
    @else
        <span style="background:#94a3b8;color:#fff;border-radius:99px;padding:.2rem .65rem;font-size:.68rem;font-weight:700;display:inline-flex;align-items:center;gap:.3rem;">
            <i class="bi bi-eye-slash"></i>Borrador
        </span>
    @endif
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

{{-- Tabs --}}
<div style="display:flex;gap:.4rem;margin-bottom:1rem;">
    <button class="tab-btn active" onclick="switchTab('preguntas',this)">
        <i class="bi bi-list-ul me-1"></i>Preguntas (<span id="totalPreg">{{ $quiz->preguntas->count() }}</span>)
    </button>
    <button class="tab-btn" onclick="switchTab('config',this)">
        <i class="bi bi-gear me-1"></i>Configuración
    </button>
    @if($intentosCount > 0)
    <a href="{{ route('portal.docente.evaluaciones.resultados', [$asignacion, $quiz]) }}"
       style="padding:.45rem 1rem;border:none;border-radius:8px;font-size:.8rem;font-weight:700;cursor:pointer;background:#0ea5e9;color:#fff;text-decoration:none;display:inline-flex;align-items:center;gap:.3rem;">
        <i class="bi bi-bar-chart-fill"></i>Resultados ({{ $intentosCount }})
    </a>
    @endif
    <form method="POST" action="{{ route('portal.docente.evaluaciones.toggle-publicado', [$asignacion, $quiz]) }}" style="margin:0;margin-left:auto;">
        @csrf @method('PATCH')
        <button type="submit"
            style="padding:.45rem 1rem;border:none;border-radius:8px;font-size:.8rem;font-weight:700;cursor:pointer;background:{{ $quiz->publicado ? '#f59e0b' : '#10b981' }};color:#fff;display:inline-flex;align-items:center;gap:.3rem;">
            <i class="bi bi-{{ $quiz->publicado ? 'eye-slash' : 'broadcast' }}"></i>
            {{ $quiz->publicado ? 'Despublicar' : 'Publicar' }}
        </button>
    </form>
</div>

{{-- TAB: Preguntas --}}
<div id="tab-preguntas">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:.75rem;">
        <div style="font-size:.78rem;color:#64748b;">
            Puntaje total: <strong style="color:#6366f1;">{{ $quiz->puntaje_total }}</strong> pts
        </div>
        <button onclick="document.getElementById('modalPregunta').classList.add('active')"
            style="background:#6366f1;color:#fff;border:none;border-radius:8px;padding:.45rem 1rem;font-size:.8rem;font-weight:700;cursor:pointer;display:flex;align-items:center;gap:.4rem;">
            <i class="bi bi-plus-lg"></i>Agregar Pregunta
        </button>
    </div>

    <div id="listaPreguntas">
        @forelse($quiz->preguntas as $i => $p)
        <div class="preg-card" id="preg-{{ $p->id }}">
            <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:.5rem;">
                <div style="flex:1;">
                    <div style="font-size:.78rem;color:#94a3b8;margin-bottom:.2rem;">
                        #{{ $i+1 }} · {{ ['multiple'=>'Opción Múltiple','verdadero_falso'=>'V/F','abierta'=>'Abierta'][$p->tipo] ?? $p->tipo }}
                        · <strong>{{ $p->puntos }} pts</strong>
                    </div>
                    <p style="margin:0 0 .4rem;font-size:.85rem;font-weight:600;">{{ $p->enunciado }}</p>
                    @if($p->opciones)
                    <div>
                        @foreach($p->opciones as $j => $op)
                        <div class="opcion-row {{ !empty($op['correcta']) ? 'opcion-correcta' : '' }}">
                            <i class="bi bi-{{ !empty($op['correcta']) ? 'check-circle-fill' : 'circle' }}" style="font-size:.75rem;"></i>
                            {{ $op['texto'] }}
                        </div>
                        @endforeach
                    </div>
                    @endif
                    @if($p->explicacion)
                    <div style="font-size:.72rem;color:#64748b;margin-top:.3rem;font-style:italic;">
                        <i class="bi bi-lightbulb me-1"></i>{{ $p->explicacion }}
                    </div>
                    @endif
                </div>
                <button onclick="eliminarPregunta({{ $p->id }}, this)"
                    style="background:#fee2e2;color:#ef4444;border:none;border-radius:7px;padding:.3rem .55rem;font-size:.8rem;cursor:pointer;flex-shrink:0;">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
        </div>
        @empty
        <div id="sinPreguntas" class="prt-card" style="text-align:center;padding:2rem;color:#94a3b8;">
            <i class="bi bi-list-ul" style="font-size:2rem;display:block;margin-bottom:.5rem;"></i>
            <p style="margin:0;font-size:.85rem;">Agrega la primera pregunta.</p>
        </div>
        @endforelse
    </div>
</div>

{{-- TAB: Configuración --}}
<div id="tab-config" style="display:none;">
    <div class="prt-card" style="padding:1.2rem;">
        <form method="POST" action="{{ route('portal.docente.evaluaciones.update', [$asignacion, $quiz]) }}">
            @csrf @method('PUT')
            <div style="margin-bottom:.85rem;">
                <label style="font-size:.75rem;font-weight:600;display:block;margin-bottom:.3rem;">Título *</label>
                <input name="titulo" required maxlength="200" value="{{ $quiz->titulo }}"
                    style="width:100%;border:1.5px solid #e2e8f0;border-radius:8px;padding:.55rem .75rem;font-size:.85rem;">
            </div>
            <div style="margin-bottom:.85rem;">
                <label style="font-size:.75rem;font-weight:600;display:block;margin-bottom:.3rem;">Instrucciones</label>
                <textarea name="instrucciones" rows="2"
                    style="width:100%;border:1.5px solid #e2e8f0;border-radius:8px;padding:.55rem .75rem;font-size:.85rem;resize:vertical;">{{ $quiz->instrucciones }}</textarea>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:.7rem;margin-bottom:.85rem;">
                <div>
                    <label style="font-size:.75rem;font-weight:600;display:block;margin-bottom:.3rem;">Duración (min)</label>
                    <input name="duracion_minutos" type="number" min="1" max="300" value="{{ $quiz->duracion_minutos }}"
                        placeholder="Sin límite"
                        style="width:100%;border:1.5px solid #e2e8f0;border-radius:8px;padding:.55rem .75rem;font-size:.85rem;">
                </div>
                <div>
                    <label style="font-size:.75rem;font-weight:600;display:block;margin-bottom:.3rem;">Intentos máx. *</label>
                    <input name="intentos_max" type="number" min="1" max="10" value="{{ $quiz->intentos_max }}" required
                        style="width:100%;border:1.5px solid #e2e8f0;border-radius:8px;padding:.55rem .75rem;font-size:.85rem;">
                </div>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:.7rem;margin-bottom:.85rem;">
                <div>
                    <label style="font-size:.75rem;font-weight:600;display:block;margin-bottom:.3rem;">Disponible desde</label>
                    <input name="disponible_desde" type="datetime-local"
                        value="{{ $quiz->disponible_desde?->format('Y-m-d\TH:i') }}"
                        style="width:100%;border:1.5px solid #e2e8f0;border-radius:8px;padding:.55rem .75rem;font-size:.82rem;">
                </div>
                <div>
                    <label style="font-size:.75rem;font-weight:600;display:block;margin-bottom:.3rem;">Disponible hasta</label>
                    <input name="disponible_hasta" type="datetime-local"
                        value="{{ $quiz->disponible_hasta?->format('Y-m-d\TH:i') }}"
                        style="width:100%;border:1.5px solid #e2e8f0;border-radius:8px;padding:.55rem .75rem;font-size:.82rem;">
                </div>
            </div>
            <div style="display:flex;gap:1.5rem;margin-bottom:1rem;">
                <label style="display:flex;align-items:center;gap:.4rem;font-size:.78rem;font-weight:600;cursor:pointer;">
                    <input name="mostrar_resultados" type="checkbox" value="1" {{ $quiz->mostrar_resultados ? 'checked' : '' }}>
                    Mostrar resultados al terminar
                </label>
                <label style="display:flex;align-items:center;gap:.4rem;font-size:.78rem;font-weight:600;cursor:pointer;">
                    <input name="aleatorizar" type="checkbox" value="1" {{ $quiz->aleatorizar ? 'checked' : '' }}>
                    Aleatorizar preguntas
                </label>
            </div>
            <div style="text-align:right;">
                <button type="submit"
                    style="background:#6366f1;color:#fff;border:none;border-radius:8px;padding:.55rem 1.4rem;font-size:.82rem;font-weight:700;cursor:pointer;">
                    <i class="bi bi-floppy me-1"></i>Guardar Cambios
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Modal: Agregar Pregunta --}}
<div id="modalPregunta" class="modal-overlay" onclick="if(event.target===this)this.classList.remove('active')">
    <div class="modal-box">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem;">
            <h3 style="margin:0;font-size:.95rem;font-weight:800;">
                <i class="bi bi-plus-circle-fill me-2" style="color:#6366f1;"></i>Agregar Pregunta
            </h3>
            <button onclick="document.getElementById('modalPregunta').classList.remove('active')"
                style="background:none;border:none;font-size:1.2rem;cursor:pointer;color:#64748b;">&times;</button>
        </div>

        <div style="margin-bottom:.85rem;">
            <label style="font-size:.75rem;font-weight:600;display:block;margin-bottom:.3rem;">Tipo de pregunta</label>
            <select id="tipoPregunta" onchange="cambiarTipo(this.value)"
                style="width:100%;border:1.5px solid #e2e8f0;border-radius:8px;padding:.55rem .75rem;font-size:.85rem;">
                <option value="multiple">Opción Múltiple</option>
                <option value="verdadero_falso">Verdadero / Falso</option>
                <option value="abierta">Abierta (manual)</option>
            </select>
        </div>

        <div style="margin-bottom:.85rem;">
            <label style="font-size:.75rem;font-weight:600;display:block;margin-bottom:.3rem;">Enunciado *</label>
            <textarea id="enunciadoInput" rows="3" placeholder="Escribe la pregunta..."
                style="width:100%;border:1.5px solid #e2e8f0;border-radius:8px;padding:.55rem .75rem;font-size:.85rem;resize:vertical;"></textarea>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:.7rem;margin-bottom:.85rem;">
            <div>
                <label style="font-size:.75rem;font-weight:600;display:block;margin-bottom:.3rem;">Puntos *</label>
                <input id="puntosInput" type="number" min="0.5" max="100" step="0.5" value="1"
                    style="width:100%;border:1.5px solid #e2e8f0;border-radius:8px;padding:.55rem .75rem;font-size:.85rem;">
            </div>
            <div>
                <label style="font-size:.75rem;font-weight:600;display:block;margin-bottom:.3rem;">Explicación (opcional)</label>
                <input id="explicacionInput" type="text" placeholder="Retroalimentación..."
                    style="width:100%;border:1.5px solid #e2e8f0;border-radius:8px;padding:.55rem .75rem;font-size:.85rem;">
            </div>
        </div>

        {{-- Opciones múltiple --}}
        <div id="seccionMultiple">
            <label style="font-size:.75rem;font-weight:600;display:block;margin-bottom:.4rem;">Opciones (marca la correcta)</label>
            <div id="opcionesContainer"></div>
            <button type="button" onclick="agregarOpcion()"
                style="background:#f1f5f9;color:#475569;border:1px dashed #cbd5e1;border-radius:7px;padding:.35rem .75rem;font-size:.75rem;font-weight:600;cursor:pointer;margin-top:.3rem;">
                <i class="bi bi-plus me-1"></i>Agregar opción
            </button>
        </div>

        {{-- VF --}}
        <div id="seccionVF" style="display:none;">
            <label style="font-size:.75rem;font-weight:600;display:block;margin-bottom:.4rem;">Respuesta correcta</label>
            <div style="display:flex;gap:1rem;">
                <label style="display:flex;align-items:center;gap:.4rem;font-size:.85rem;font-weight:700;cursor:pointer;">
                    <input type="radio" name="correcta_vf_ui" id="vfV" value="V" checked> Verdadero
                </label>
                <label style="display:flex;align-items:center;gap:.4rem;font-size:.85rem;font-weight:700;cursor:pointer;">
                    <input type="radio" name="correcta_vf_ui" id="vfF" value="F"> Falso
                </label>
            </div>
        </div>

        {{-- Abierta --}}
        <div id="seccionAbierta" style="display:none;">
            <div style="background:#fef9c3;border:1px solid #fde047;border-radius:8px;padding:.6rem .8rem;font-size:.78rem;color:#854d0e;">
                <i class="bi bi-info-circle me-1"></i>Las preguntas abiertas requieren revisión manual.
            </div>
        </div>

        <div id="errorPregunta" style="display:none;background:#fee2e2;border:1px solid #fca5a5;border-radius:8px;padding:.5rem .8rem;font-size:.78rem;color:#991b1b;margin-top:.8rem;"></div>

        <div style="display:flex;gap:.5rem;justify-content:flex-end;margin-top:1rem;">
            <button type="button" onclick="document.getElementById('modalPregunta').classList.remove('active')"
                style="background:#f1f5f9;color:#475569;border:none;border-radius:8px;padding:.55rem 1.1rem;font-size:.82rem;font-weight:600;cursor:pointer;">
                Cancelar
            </button>
            <button type="button" onclick="guardarPregunta()"
                style="background:#6366f1;color:#fff;border:none;border-radius:8px;padding:.55rem 1.2rem;font-size:.82rem;font-weight:700;cursor:pointer;display:flex;align-items:center;gap:.4rem;">
                <span class="spinner show" id="spinnerPregunta" style="display:none;">
                    <i class="bi bi-arrow-clockwise"></i>
                </span>
                <i class="bi bi-plus-lg"></i>Agregar
            </button>
        </div>
    </div>
</div>

@push('scripts')
<script>
const CSRF   = '{{ csrf_token() }}';
const QUIZ   = {{ $quiz->id }};
const ASG    = {{ $asignacion->id }};
const URL_PREG = '{{ route("portal.docente.evaluaciones.preguntas.store", [$asignacion, $quiz]) }}';
const URL_DEL  = '/portal/docente/asignacion/' + ASG + '/evaluaciones/' + QUIZ + '/preguntas/';

function switchTab(tab, btn) {
    document.getElementById('tab-preguntas').style.display = tab === 'preguntas' ? '' : 'none';
    document.getElementById('tab-config').style.display    = tab === 'config' ? '' : 'none';
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    if (btn) btn.classList.add('active');
}

// Opciones iniciales para "multiple"
window.addEventListener('DOMContentLoaded', () => {
    for (let i = 0; i < 4; i++) agregarOpcion();
});

function cambiarTipo(tipo) {
    document.getElementById('seccionMultiple').style.display = tipo === 'multiple' ? '' : 'none';
    document.getElementById('seccionVF').style.display       = tipo === 'verdadero_falso' ? '' : 'none';
    document.getElementById('seccionAbierta').style.display  = tipo === 'abierta' ? '' : 'none';
}

function agregarOpcion() {
    const c  = document.getElementById('opcionesContainer');
    const i  = c.children.length;
    const d  = document.createElement('div');
    d.className = 'opcion-row';
    d.innerHTML = `
        <input type="radio" name="opcCorrecta" value="${i}" style="flex-shrink:0;">
        <input type="text" placeholder="Opción ${i+1}" maxlength="300"
            style="flex:1;border:1.5px solid #e2e8f0;border-radius:7px;padding:.38rem .6rem;font-size:.82rem;">
        <button type="button" onclick="this.parentElement.remove()"
            style="background:#fee2e2;color:#ef4444;border:none;border-radius:6px;padding:.25rem .45rem;font-size:.75rem;cursor:pointer;">
            <i class="bi bi-x"></i>
        </button>`;
    c.appendChild(d);
}

async function guardarPregunta() {
    const tipo      = document.getElementById('tipoPregunta').value;
    const enunciado = document.getElementById('enunciadoInput').value.trim();
    const puntos    = parseFloat(document.getElementById('puntosInput').value);
    const explic    = document.getElementById('explicacionInput').value.trim();
    const errDiv    = document.getElementById('errorPregunta');

    errDiv.style.display = 'none';
    if (!enunciado) { errDiv.textContent = 'El enunciado es obligatorio.'; errDiv.style.display=''; return; }
    if (!puntos || puntos < 0.5) { errDiv.textContent = 'Los puntos deben ser ≥ 0.5.'; errDiv.style.display=''; return; }

    const body = { enunciado, tipo, puntos, explicacion: explic };

    if (tipo === 'multiple') {
        const filas = document.querySelectorAll('#opcionesContainer .opcion-row');
        const opts  = [];
        let   idx   = 0;
        let   corr  = null;
        const radio = document.querySelector('input[name="opcCorrecta"]:checked');
        if (radio) corr = parseInt(radio.value);

        filas.forEach((f, fi) => {
            const txt = f.querySelector('input[type="text"]').value.trim();
            if (txt) {
                opts.push({ texto: txt, correcta: fi === corr });
                idx++;
            }
        });
        if (opts.length < 2) { errDiv.textContent = 'Agrega al menos 2 opciones.'; errDiv.style.display=''; return; }
        body.opciones = opts;
    } else if (tipo === 'verdadero_falso') {
        const vf = document.querySelector('input[name="correcta_vf_ui"]:checked');
        body.correcta_vf = vf ? vf.value : 'V';
    }

    const spinner = document.getElementById('spinnerPregunta');
    spinner.style.display = 'inline-block';

    try {
        const r = await fetch(URL_PREG, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
            body: JSON.stringify(body)
        });
        const data = await r.json();
        if (!data.ok) throw new Error(data.message ?? 'Error');

        // Recargar la página para mostrar la nueva pregunta
        window.location.reload();
    } catch(e) {
        errDiv.textContent = e.message;
        errDiv.style.display = '';
    } finally {
        spinner.style.display = 'none';
    }
}

async function eliminarPregunta(id, btn) {
    if (!confirm('¿Eliminar esta pregunta?')) return;
    const r = await fetch(URL_DEL + id, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' }
    });
    const data = await r.json();
    if (data.ok) {
        document.getElementById('preg-' + id)?.remove();
        document.getElementById('totalPreg').textContent = data.total;
        if (data.total === 0) {
            document.getElementById('listaPreguntas').innerHTML = `
                <div id="sinPreguntas" class="prt-card" style="text-align:center;padding:2rem;color:#94a3b8;">
                    <i class="bi bi-list-ul" style="font-size:2rem;display:block;margin-bottom:.5rem;"></i>
                    <p style="margin:0;font-size:.85rem;">Agrega la primera pregunta.</p>
                </div>`;
        }
    }
}
</script>
@endpush

@endsection
