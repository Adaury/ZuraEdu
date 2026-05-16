@extends('layouts.portal')
@section('page-title', 'Banco de Preguntas')
@section('portal-name', 'Portal Docente')

@section('sidebar')
    @include('portal.docente._sidebar_clase', ['activeKey' => 'banco-preguntas'])
@endsection

@section('bottom-nav')
<a href="{{ route('portal.docente.dashboard') }}" class="prt-nav-item"><i class="bi bi-house-fill"></i>Inicio</a>
<a href="{{ route('portal.docente.banco-preguntas.index') }}" class="prt-nav-item active"><i class="bi bi-collection-fill"></i>Banco</a>
@endsection

@push('styles')
<style>
.bp-card {
    background:#fff;border:1.5px solid #e2e8f0;border-radius:10px;
    padding:.85rem 1rem;margin-bottom:.6rem;
    border-left:3px solid #8b5cf6;transition:box-shadow .15s;
}
.bp-card:hover { box-shadow:0 3px 12px rgba(139,92,246,.1); }
.tipo-badge {
    display:inline-block;padding:.15rem .5rem;border-radius:99px;
    font-size:.67rem;font-weight:700;color:#fff;
}
.cat-chip {
    display:inline-block;padding:.12rem .45rem;border-radius:99px;
    font-size:.65rem;font-weight:600;background:#ede9fe;color:#6d28d9;
}
.filter-btn {
    padding:.35rem .7rem;border:1.5px solid #e2e8f0;border-radius:7px;
    font-size:.75rem;font-weight:600;cursor:pointer;background:#fff;
    color:#475569;transition:.15s;
}
.filter-btn.active { background:#8b5cf6;color:#fff;border-color:#8b5cf6; }
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
.opcion-row { display:flex;align-items:center;gap:.5rem;margin-bottom:.4rem; }
</style>
@endpush

@section('content')

<div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:.5rem;margin-bottom:1.25rem;">
    <div>
        <h2 style="font-size:1rem;font-weight:800;margin:0;">
            <i class="bi bi-collection-fill me-2" style="color:#8b5cf6;"></i>Banco de Preguntas
        </h2>
        <p style="font-size:.75rem;color:#64748b;margin:.2rem 0 0;">
            {{ $totalBanco }} pregunta{{ $totalBanco !== 1 ? 's' : '' }} en tu banco
        </p>
    </div>
    <button onclick="abrirModal()"
        style="background:#8b5cf6;color:#fff;border:none;border-radius:8px;padding:.5rem 1rem;font-size:.8rem;font-weight:700;cursor:pointer;display:flex;align-items:center;gap:.4rem;">
        <i class="bi bi-plus-lg"></i>Nueva Pregunta
    </button>
</div>

@if(session('success'))
<div style="background:#dcfce7;border:1px solid #86efac;border-radius:8px;padding:.6rem 1rem;margin-bottom:1rem;font-size:.8rem;color:#166534;">
    <i class="bi bi-check-circle-fill me-1"></i>{{ session('success') }}
</div>
@endif

{{-- Filtros --}}
<div class="prt-card" style="padding:.85rem 1rem;margin-bottom:1rem;">
    <form method="GET" style="display:flex;gap:.5rem;flex-wrap:wrap;align-items:flex-end;">
        <div style="flex:2;min-width:160px;">
            <label style="font-size:.72rem;font-weight:600;display:block;margin-bottom:.2rem;color:#64748b;">Buscar</label>
            <input name="q" value="{{ request('q') }}" placeholder="Texto de la pregunta..."
                style="width:100%;border:1.5px solid #e2e8f0;border-radius:7px;padding:.42rem .7rem;font-size:.82rem;">
        </div>
        <div style="flex:1;min-width:130px;">
            <label style="font-size:.72rem;font-weight:600;display:block;margin-bottom:.2rem;color:#64748b;">Asignatura</label>
            <select name="asignatura_id"
                style="width:100%;border:1.5px solid #e2e8f0;border-radius:7px;padding:.42rem .7rem;font-size:.82rem;">
                <option value="">Todas</option>
                @foreach($asignaturas as $a)
                <option value="{{ $a->id }}" {{ request('asignatura_id') == $a->id ? 'selected' : '' }}>{{ $a->nombre }}</option>
                @endforeach
            </select>
        </div>
        <div style="flex:1;min-width:110px;">
            <label style="font-size:.72rem;font-weight:600;display:block;margin-bottom:.2rem;color:#64748b;">Tipo</label>
            <select name="tipo"
                style="width:100%;border:1.5px solid #e2e8f0;border-radius:7px;padding:.42rem .7rem;font-size:.82rem;">
                <option value="">Todos</option>
                <option value="multiple" {{ request('tipo')==='multiple' ? 'selected':'' }}>Opción Múltiple</option>
                <option value="verdadero_falso" {{ request('tipo')==='verdadero_falso' ? 'selected':'' }}>V/F</option>
                <option value="abierta" {{ request('tipo')==='abierta' ? 'selected':'' }}>Abierta</option>
            </select>
        </div>
        @if($categorias->isNotEmpty())
        <div style="flex:1;min-width:110px;">
            <label style="font-size:.72rem;font-weight:600;display:block;margin-bottom:.2rem;color:#64748b;">Categoría</label>
            <select name="categoria"
                style="width:100%;border:1.5px solid #e2e8f0;border-radius:7px;padding:.42rem .7rem;font-size:.82rem;">
                <option value="">Todas</option>
                @foreach($categorias as $cat)
                <option value="{{ $cat }}" {{ request('categoria')===$cat ? 'selected':'' }}>{{ $cat }}</option>
                @endforeach
            </select>
        </div>
        @endif
        <div style="display:flex;gap:.35rem;">
            <button type="submit"
                style="background:#8b5cf6;color:#fff;border:none;border-radius:7px;padding:.42rem .85rem;font-size:.8rem;font-weight:700;cursor:pointer;">
                <i class="bi bi-search"></i>
            </button>
            @if(request()->hasAny(['q','asignatura_id','tipo','categoria']))
            <a href="{{ route('portal.docente.banco-preguntas.index') }}"
               style="background:#f1f5f9;color:#64748b;border:none;border-radius:7px;padding:.42rem .75rem;font-size:.8rem;font-weight:600;text-decoration:none;display:inline-flex;align-items:center;">
                <i class="bi bi-x"></i>
            </a>
            @endif
        </div>
    </form>
</div>

{{-- Lista --}}
@forelse($preguntas as $p)
<div class="bp-card" id="bp-{{ $p->id }}">
    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:.5rem;">
        <div style="flex:1;min-width:0;">
            <div style="display:flex;align-items:center;gap:.4rem;flex-wrap:wrap;margin-bottom:.3rem;">
                @php
                    $tipoBadge = ['multiple'=>['Múltiple','#6366f1'],'verdadero_falso'=>['V/F','#10b981'],'abierta'=>['Abierta','#f59e0b']];
                    [$tLabel, $tColor] = $tipoBadge[$p->tipo] ?? ['?','#94a3b8'];
                @endphp
                <span class="tipo-badge" style="background:{{ $tColor }};">{{ $tLabel }}</span>
                @if($p->asignatura)
                    <span style="font-size:.7rem;color:#64748b;font-weight:600;">{{ $p->asignatura->nombre }}</span>
                @endif
                @if($p->categoria)
                    <span class="cat-chip">{{ $p->categoria }}</span>
                @endif
                <span style="font-size:.68rem;color:#94a3b8;margin-left:auto;">
                    <i class="bi bi-arrow-repeat me-1"></i>{{ $p->usos }} uso{{ $p->usos !== 1 ? 's' : '' }}
                </span>
            </div>
            <p style="margin:0 0 .35rem;font-size:.85rem;font-weight:600;">{{ Str::limit($p->enunciado, 120) }}</p>
            @if($p->opciones)
            <div style="display:flex;gap:.5rem;flex-wrap:wrap;">
                @foreach($p->opciones as $j => $op)
                <span style="font-size:.72rem;padding:.1rem .4rem;border-radius:6px;background:{{ !empty($op['correcta']) ? '#dcfce7' : '#f1f5f9' }};color:{{ !empty($op['correcta']) ? '#166534' : '#64748b' }};font-weight:{{ !empty($op['correcta']) ? '700' : '500' }};">
                    {{ !empty($op['correcta']) ? '✓' : '' }} {{ $op['texto'] }}
                </span>
                @endforeach
            </div>
            @endif
            @if($p->explicacion)
            <div style="font-size:.7rem;color:#94a3b8;margin-top:.3rem;font-style:italic;">
                <i class="bi bi-lightbulb me-1"></i>{{ Str::limit($p->explicacion, 80) }}
            </div>
            @endif
        </div>
        <div style="display:flex;gap:.3rem;flex-shrink:0;">
            <button onclick='abrirEditar({{ $p->id }}, @json($p))' title="Editar"
                style="background:#ede9fe;color:#7c3aed;border:none;border-radius:7px;padding:.32rem .55rem;font-size:.8rem;cursor:pointer;">
                <i class="bi bi-pencil-fill"></i>
            </button>
            <button onclick="eliminar({{ $p->id }})" title="Eliminar"
                style="background:#fee2e2;color:#ef4444;border:none;border-radius:7px;padding:.32rem .55rem;font-size:.8rem;cursor:pointer;">
                <i class="bi bi-trash"></i>
            </button>
        </div>
    </div>
</div>
@empty
<div class="prt-card" style="text-align:center;padding:2.5rem;color:#94a3b8;">
    <i class="bi bi-collection" style="font-size:2.5rem;display:block;margin-bottom:.6rem;"></i>
    <p style="margin:0;font-size:.88rem;">Tu banco de preguntas está vacío. ¡Empieza agregando la primera!</p>
</div>
@endforelse

{{ $preguntas->links() }}

{{-- ── MODAL: Crear / Editar ────────────────────────────────────────────── --}}
<div id="modalBP" class="modal-overlay" onclick="if(event.target===this)cerrarModal()">
    <div class="modal-box">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem;">
            <h3 id="modalTitulo" style="margin:0;font-size:.95rem;font-weight:800;">
                <i class="bi bi-collection-fill me-2" style="color:#8b5cf6;"></i>Nueva Pregunta
            </h3>
            <button onclick="cerrarModal()"
                style="background:none;border:none;font-size:1.2rem;cursor:pointer;color:#64748b;">&times;</button>
        </div>

        <div id="errModal" style="display:none;background:#fee2e2;border:1px solid #fca5a5;border-radius:8px;padding:.5rem .8rem;font-size:.78rem;color:#991b1b;margin-bottom:.8rem;"></div>

        <div style="margin-bottom:.8rem;">
            <label style="font-size:.75rem;font-weight:600;display:block;margin-bottom:.3rem;">Tipo *</label>
            <select id="mTipo" onchange="cambiarTipoModal(this.value)"
                style="width:100%;border:1.5px solid #e2e8f0;border-radius:8px;padding:.55rem .75rem;font-size:.85rem;">
                <option value="multiple">Opción Múltiple</option>
                <option value="verdadero_falso">Verdadero / Falso</option>
                <option value="abierta">Abierta (revisión manual)</option>
            </select>
        </div>
        <div style="margin-bottom:.8rem;">
            <label style="font-size:.75rem;font-weight:600;display:block;margin-bottom:.3rem;">Enunciado *</label>
            <textarea id="mEnunciado" rows="3" placeholder="Escribe la pregunta..."
                style="width:100%;border:1.5px solid #e2e8f0;border-radius:8px;padding:.55rem .75rem;font-size:.85rem;resize:vertical;"></textarea>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:.7rem;margin-bottom:.8rem;">
            <div>
                <label style="font-size:.75rem;font-weight:600;display:block;margin-bottom:.3rem;">Asignatura</label>
                <select id="mAsignatura"
                    style="width:100%;border:1.5px solid #e2e8f0;border-radius:8px;padding:.55rem .75rem;font-size:.82rem;">
                    <option value="">General</option>
                    @foreach($asignaturas as $a)
                    <option value="{{ $a->id }}">{{ $a->nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label style="font-size:.75rem;font-weight:600;display:block;margin-bottom:.3rem;">Categoría</label>
                <input id="mCategoria" list="listCats" placeholder="Ej: Tema 1, Fracciones..."
                    style="width:100%;border:1.5px solid #e2e8f0;border-radius:8px;padding:.55rem .75rem;font-size:.82rem;">
                <datalist id="listCats">
                    @foreach($categorias as $cat)
                    <option value="{{ $cat }}">
                    @endforeach
                </datalist>
            </div>
        </div>
        <div style="margin-bottom:.8rem;">
            <label style="font-size:.75rem;font-weight:600;display:block;margin-bottom:.3rem;">Puntos por defecto *</label>
            <input id="mPuntos" type="number" min="0.5" max="100" step="0.5" value="1"
                style="width:120px;border:1.5px solid #e2e8f0;border-radius:8px;padding:.55rem .75rem;font-size:.85rem;">
        </div>

        {{-- Opciones múltiple --}}
        <div id="mSecMultiple">
            <label style="font-size:.75rem;font-weight:600;display:block;margin-bottom:.4rem;">Opciones (marca la correcta)</label>
            <div id="mOpcionesContainer"></div>
            <button type="button" onclick="mAgregarOpcion()"
                style="background:#f1f5f9;color:#475569;border:1px dashed #cbd5e1;border-radius:7px;padding:.35rem .75rem;font-size:.75rem;font-weight:600;cursor:pointer;margin-top:.3rem;">
                <i class="bi bi-plus me-1"></i>Agregar opción
            </button>
        </div>

        {{-- VF --}}
        <div id="mSecVF" style="display:none;">
            <label style="font-size:.75rem;font-weight:600;display:block;margin-bottom:.4rem;">Respuesta correcta</label>
            <div style="display:flex;gap:1.5rem;">
                <label style="display:flex;align-items:center;gap:.4rem;font-size:.85rem;font-weight:700;cursor:pointer;">
                    <input type="radio" name="mVF" id="mVFV" value="V" checked> Verdadero
                </label>
                <label style="display:flex;align-items:center;gap:.4rem;font-size:.85rem;font-weight:700;cursor:pointer;">
                    <input type="radio" name="mVF" id="mVFF" value="F"> Falso
                </label>
            </div>
        </div>

        {{-- Abierta --}}
        <div id="mSecAbierta" style="display:none;">
            <div style="background:#fef9c3;border:1px solid #fde047;border-radius:8px;padding:.6rem .8rem;font-size:.78rem;color:#854d0e;">
                <i class="bi bi-info-circle me-1"></i>Las preguntas abiertas requieren corrección manual.
            </div>
        </div>

        <div style="margin-top:.85rem;">
            <label style="font-size:.75rem;font-weight:600;display:block;margin-bottom:.3rem;">Explicación / Retroalimentación</label>
            <input id="mExplicacion" type="text" placeholder="Ayuda o explicación de la respuesta correcta..."
                style="width:100%;border:1.5px solid #e2e8f0;border-radius:8px;padding:.55rem .75rem;font-size:.82rem;">
        </div>

        <div style="display:flex;gap:.5rem;justify-content:flex-end;margin-top:1.1rem;">
            <button type="button" onclick="cerrarModal()"
                style="background:#f1f5f9;color:#475569;border:none;border-radius:8px;padding:.55rem 1.1rem;font-size:.82rem;font-weight:600;cursor:pointer;">
                Cancelar
            </button>
            <button type="button" id="btnGuardarBP" onclick="guardarBP()"
                style="background:#8b5cf6;color:#fff;border:none;border-radius:8px;padding:.55rem 1.3rem;font-size:.82rem;font-weight:700;cursor:pointer;display:flex;align-items:center;gap:.4rem;">
                <i class="bi bi-floppy"></i>Guardar
            </button>
        </div>
    </div>
</div>

@push('scripts')
<script>
const CSRF        = '{{ csrf_token() }}';
const URL_STORE   = '{{ route("portal.docente.banco-preguntas.store") }}';
const URL_BASE    = '/portal/docente/banco-preguntas/';
let editandoId    = null;

function abrirModal() {
    editandoId = null;
    document.getElementById('modalTitulo').innerHTML = '<i class="bi bi-collection-fill me-2" style="color:#8b5cf6;"></i>Nueva Pregunta';
    limpiarModal();
    document.getElementById('modalBP').classList.add('active');
    // Opciones iniciales
    const c = document.getElementById('mOpcionesContainer');
    c.innerHTML = '';
    for (let i = 0; i < 4; i++) mAgregarOpcion();
}

function abrirEditar(id, data) {
    editandoId = id;
    document.getElementById('modalTitulo').innerHTML = '<i class="bi bi-pencil-fill me-2" style="color:#8b5cf6;"></i>Editar Pregunta';
    limpiarModal();

    document.getElementById('mTipo').value      = data.tipo;
    document.getElementById('mEnunciado').value = data.enunciado;
    document.getElementById('mPuntos').value    = data.puntos_default;
    document.getElementById('mCategoria').value = data.categoria ?? '';
    document.getElementById('mExplicacion').value = data.explicacion ?? '';

    const selAs = document.getElementById('mAsignatura');
    selAs.value = data.asignatura_id ?? '';

    cambiarTipoModal(data.tipo);

    if (data.tipo === 'multiple' && data.opciones) {
        const c = document.getElementById('mOpcionesContainer');
        c.innerHTML = '';
        data.opciones.forEach((op, i) => {
            mAgregarOpcion();
            const fila = c.children[c.children.length - 1];
            fila.querySelector('input[type="text"]').value = op.texto;
            if (op.correcta) fila.querySelector('input[type="radio"]').checked = true;
        });
    } else if (data.tipo === 'verdadero_falso' && data.opciones) {
        const correctaVF = data.opciones[0]?.correcta ? 'V' : 'F';
        document.getElementById('mVFV').checked = correctaVF === 'V';
        document.getElementById('mVFF').checked = correctaVF === 'F';
    }

    document.getElementById('modalBP').classList.add('active');
}

function cerrarModal() {
    document.getElementById('modalBP').classList.remove('active');
    editandoId = null;
}

function limpiarModal() {
    ['mEnunciado','mCategoria','mExplicacion'].forEach(id => document.getElementById(id).value = '');
    document.getElementById('mTipo').value    = 'multiple';
    document.getElementById('mPuntos').value  = '1';
    document.getElementById('mAsignatura').value = '';
    document.getElementById('errModal').style.display = 'none';
    cambiarTipoModal('multiple');
    const c = document.getElementById('mOpcionesContainer');
    c.innerHTML = '';
    for (let i = 0; i < 4; i++) mAgregarOpcion();
}

function cambiarTipoModal(tipo) {
    document.getElementById('mSecMultiple').style.display = tipo === 'multiple' ? '' : 'none';
    document.getElementById('mSecVF').style.display       = tipo === 'verdadero_falso' ? '' : 'none';
    document.getElementById('mSecAbierta').style.display  = tipo === 'abierta' ? '' : 'none';
}

function mAgregarOpcion() {
    const c = document.getElementById('mOpcionesContainer');
    const i = c.children.length;
    const d = document.createElement('div');
    d.className = 'opcion-row';
    d.innerHTML = `
        <input type="radio" name="mOpcCorrecta" value="${i}" style="flex-shrink:0;">
        <input type="text" placeholder="Opción ${i+1}" maxlength="300"
            style="flex:1;border:1.5px solid #e2e8f0;border-radius:7px;padding:.38rem .6rem;font-size:.82rem;">
        <button type="button" onclick="this.parentElement.remove()"
            style="background:#fee2e2;color:#ef4444;border:none;border-radius:6px;padding:.25rem .45rem;font-size:.75rem;cursor:pointer;">
            <i class="bi bi-x"></i>
        </button>`;
    c.appendChild(d);
}

async function guardarBP() {
    const tipo      = document.getElementById('mTipo').value;
    const enunciado = document.getElementById('mEnunciado').value.trim();
    const puntos    = parseFloat(document.getElementById('mPuntos').value);
    const errDiv    = document.getElementById('errModal');
    errDiv.style.display = 'none';

    if (!enunciado) { errDiv.textContent = 'El enunciado es obligatorio.'; errDiv.style.display=''; return; }
    if (!puntos || puntos < 0.5) { errDiv.textContent = 'Puntos deben ser ≥ 0.5.'; errDiv.style.display=''; return; }

    const body = {
        enunciado,
        tipo,
        puntos_default: puntos,
        asignatura_id:  document.getElementById('mAsignatura').value || null,
        categoria:      document.getElementById('mCategoria').value.trim() || null,
        explicacion:    document.getElementById('mExplicacion').value.trim() || null,
    };

    if (tipo === 'multiple') {
        const filas  = document.querySelectorAll('#mOpcionesContainer .opcion-row');
        const radio  = document.querySelector('input[name="mOpcCorrecta"]:checked');
        const corrIdx = radio ? parseInt(radio.value) : null;
        const opts   = [];
        filas.forEach((f, fi) => {
            const txt = f.querySelector('input[type="text"]').value.trim();
            if (txt) opts.push({ texto: txt, correcta: fi === corrIdx });
        });
        if (opts.length < 2) { errDiv.textContent = 'Agrega al menos 2 opciones.'; errDiv.style.display=''; return; }
        body.opciones = opts;
    } else if (tipo === 'verdadero_falso') {
        body.correcta_vf = document.querySelector('input[name="mVF"]:checked')?.value ?? 'V';
    }

    const url    = editandoId ? URL_BASE + editandoId : URL_STORE;
    const method = editandoId ? 'PUT' : 'POST';

    try {
        document.getElementById('btnGuardarBP').disabled = true;
        const r = await fetch(url, {
            method,
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
            body: JSON.stringify(body)
        });
        if (!r.ok) {
            const d = await r.json();
            throw new Error(Object.values(d.errors ?? {}).flat().join(' ') || d.message || 'Error');
        }
        window.location.reload();
    } catch(e) {
        errDiv.textContent = e.message;
        errDiv.style.display = '';
    } finally {
        document.getElementById('btnGuardarBP').disabled = false;
    }
}

async function eliminar(id) {
    if (!confirm('¿Eliminar esta pregunta del banco?')) return;
    const r = await fetch(URL_BASE + id, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' }
    });
    const data = await r.json();
    if (data.ok) {
        document.getElementById('bp-' + id)?.remove();
    }
}
</script>
@endpush

@endsection
