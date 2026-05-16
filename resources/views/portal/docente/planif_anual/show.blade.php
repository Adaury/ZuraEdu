@extends('layouts.portal')
@section('page-title', $plan->titulo)
@section('portal-name', 'Portal Docente')

@section('sidebar')
    @include('portal.docente._sidebar_clase', ['activeKey' => 'planif-anual', 'asignacion' => $asignacion])
@endsection

@section('bottom-nav')
<a href="{{ route('portal.docente.dashboard') }}" class="prt-nav-item"><i class="bi bi-house-fill"></i>Inicio</a>
<a href="{{ route('portal.docente.planif-anual.index', $asignacion) }}" class="prt-nav-item"><i class="bi bi-map-fill"></i>Planes</a>
<a href="{{ route('portal.docente.planif-anual.pdf', [$asignacion, $plan]) }}" class="prt-nav-item"><i class="bi bi-file-earmark-pdf-fill"></i>PDF</a>
@endsection

@push('styles')
<style>
.unidad-card {
    background:#fff;border:1.5px solid #e2e8f0;border-radius:12px;
    margin-bottom:.75rem;overflow:hidden;transition:border-color .15s;
}
.unidad-card.open { border-color:#0ea5e9; }

.unidad-header {
    display:flex;align-items:center;gap:.7rem;padding:.75rem 1rem;cursor:pointer;
    user-select:none;flex-wrap:wrap;
}
.unidad-header:hover { background:#f0f9ff; }

.unidad-num {
    width:32px;height:32px;border-radius:8px;background:#0ea5e9;color:#fff;
    font-size:.8rem;font-weight:900;display:flex;align-items:center;justify-content:center;
    flex-shrink:0;
}
.per-badge {
    padding:.2rem .55rem;border-radius:99px;font-size:.67rem;font-weight:700;
    background:#e0f2fe;color:#0284c7;
}
.save-dot {
    width:8px;height:8px;border-radius:50%;background:#e2e8f0;
    transition:.3s;flex-shrink:0;
}
.save-dot.saving { background:#f59e0b; }
.save-dot.saved  { background:#10b981; }
.save-dot.error  { background:#ef4444; }

.unidad-body { padding:1rem;border-top:1px solid #f1f5f9;display:none; }
.unidad-body.open { display:block; }

.field-group { margin-bottom:.85rem; }
.field-label { font-size:.72rem;font-weight:700;color:#475569;display:block;margin-bottom:.3rem; }
.field-ta {
    width:100%;border:1.5px solid #e2e8f0;border-radius:8px;
    padding:.5rem .65rem;font-size:.82rem;font-family:inherit;
    resize:vertical;transition:border-color .15s;
}
.field-ta:focus { outline:none;border-color:#0ea5e9; }
.field-inp {
    border:1.5px solid #e2e8f0;border-radius:8px;
    padding:.42rem .65rem;font-size:.82rem;font-family:inherit;
    transition:border-color .15s;background:#fff;
}
.field-inp:focus { outline:none;border-color:#0ea5e9; }

.comp-chip {
    display:inline-flex;align-items:center;gap:.3rem;padding:.25rem .6rem;
    border-radius:99px;font-size:.67rem;font-weight:700;cursor:pointer;
    border:1.5px solid #e2e8f0;color:#64748b;background:#f8fafc;
    transition:.12s;user-select:none;
}
.comp-chip.sel { background:#0ea5e9;color:#fff;border-color:#0ea5e9; }
</style>
@endpush

@section('content')

{{-- Header --}}
<div style="display:flex;align-items:flex-start;gap:.7rem;margin-bottom:1.2rem;flex-wrap:wrap;">
    <a href="{{ route('portal.docente.planif-anual.index', $asignacion) }}"
       style="color:#0ea5e9;text-decoration:none;font-size:.8rem;font-weight:600;display:flex;align-items:center;gap:.3rem;margin-top:.15rem;">
        <i class="bi bi-arrow-left"></i>Planes
    </a>
    <span style="color:#cbd5e1;margin-top:.1rem;">›</span>
    <div style="flex:1;min-width:0;">
        <input type="text" id="plan-titulo" value="{{ $plan->titulo }}"
            style="font-size:1rem;font-weight:800;border:none;outline:none;width:100%;color:#1e293b;background:transparent;padding:0;"
            onblur="guardarPlan()" onchange="guardarPlan()">
        <div style="font-size:.72rem;color:#64748b;">
            {{ $asignacion->asignatura?->nombre }} ·
            {{ $asignacion->grupo?->grado?->nombre }} {{ $asignacion->grupo?->seccion?->nombre }}
        </div>
    </div>
    <div style="display:flex;gap:.5rem;flex-shrink:0;">
        <a href="{{ route('portal.docente.planif-anual.pdf', [$asignacion, $plan]) }}"
           style="background:#0f172a;color:#fff;border-radius:8px;padding:.42rem .85rem;font-size:.78rem;font-weight:700;text-decoration:none;display:inline-flex;align-items:center;gap:.35rem;">
            <i class="bi bi-file-earmark-pdf-fill"></i>PDF
        </a>
        <button onclick="agregarUnidad()"
            style="background:#0ea5e9;color:#fff;border:none;border-radius:8px;padding:.42rem .85rem;font-size:.78rem;font-weight:700;cursor:pointer;display:inline-flex;align-items:center;gap:.35rem;">
            <i class="bi bi-plus-lg"></i>Agregar Unidad
        </button>
    </div>
</div>

{{-- Descripción del plan --}}
<div style="margin-bottom:1rem;">
    <textarea id="plan-desc" rows="1" placeholder="Descripción del plan (opcional)..."
        style="width:100%;border:1.5px solid #e2e8f0;border-radius:9px;padding:.55rem .8rem;font-size:.82rem;font-family:inherit;resize:vertical;color:#475569;"
        onblur="guardarPlan()">{{ $plan->descripcion }}</textarea>
</div>

{{-- Lista de unidades --}}
<div id="unidades-container">
    @foreach($plan->unidades as $u)
    @include('portal.docente.planif_anual._unidad', ['u' => $u, 'competencias' => $competencias, 'open' => false])
    @endforeach
</div>

@if($plan->unidades->isEmpty())
<div id="empty-state" class="prt-card" style="text-align:center;padding:2.5rem;color:#94a3b8;">
    <i class="bi bi-layout-text-sidebar-reverse" style="font-size:2rem;display:block;margin-bottom:.6rem;color:#bae6fd;"></i>
    <p style="margin:0 0 .35rem;font-weight:600;color:#475569;font-size:.88rem;">Sin unidades curriculares</p>
    <p style="margin:0;font-size:.78rem;">Agrega la primera unidad temática de tu plan.</p>
</div>
@else
<div id="empty-state" style="display:none;"></div>
@endif

@push('scripts')
<script>
const CSRF_PL   = '{{ csrf_token() }}';
const URL_PLAN  = '{{ route("portal.docente.planif-anual.update", [$asignacion, $plan]) }}';
const URL_USTORE= '{{ route("portal.docente.planif-anual.unidades.store", [$asignacion, $plan]) }}';
const COMPS     = @json($competencias);
let planTimer   = null;
const unitTimers= {};

// ── Plan header ────────────────────────────────────────────────────────────
function guardarPlan() {
    clearTimeout(planTimer);
    planTimer = setTimeout(async () => {
        await fetch(URL_PLAN, {
            method: 'PATCH',
            headers: { 'Content-Type':'application/json','X-CSRF-TOKEN':CSRF_PL,'Accept':'application/json' },
            body: JSON.stringify({
                titulo: document.getElementById('plan-titulo').value,
                descripcion: document.getElementById('plan-desc').value,
            })
        });
    }, 800);
}

// ── Unidad: toggle expand ──────────────────────────────────────────────────
function toggleUnidad(id) {
    const card = document.getElementById(`unidad-${id}`);
    const body = document.getElementById(`body-${id}`);
    card.classList.toggle('open');
    body.classList.toggle('open');
}

// ── Agregar unidad ─────────────────────────────────────────────────────────
async function agregarUnidad() {
    document.getElementById('empty-state').style.display = 'none';
    const r = await fetch(URL_USTORE, {
        method: 'POST',
        headers: { 'Content-Type':'application/json','X-CSRF-TOKEN':CSRF_PL,'Accept':'application/json' },
        body: JSON.stringify({ titulo: 'Nueva Unidad', periodo: '' })
    });
    const d = await r.json();
    if (!d.ok) return;
    const u = d.unidad;
    const container = document.getElementById('unidades-container');
    container.insertAdjacentHTML('beforeend', buildUnidadHTML(u));
    // open it
    const card = document.getElementById(`unidad-${u.id}`);
    const body = document.getElementById(`body-${u.id}`);
    card?.classList.add('open');
    body?.classList.add('open');
    card?.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

// ── Eliminar unidad ────────────────────────────────────────────────────────
async function eliminarUnidad(id) {
    if (!confirm('¿Eliminar esta unidad?')) return;
    const url = URL_USTORE.replace('/unidades', `/unidades/${id}`);
    const r = await fetch(url, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': CSRF_PL, 'Accept': 'application/json' }
    });
    const d = await r.json();
    if (d.ok) {
        document.getElementById(`unidad-${id}`)?.remove();
        renumerarDOM();
        const container = document.getElementById('unidades-container');
        if (!container.children.length) {
            document.getElementById('empty-state').style.display = '';
        }
    }
}

// ── Mover unidad ───────────────────────────────────────────────────────────
async function moverUnidad(id, dir) {
    const url = URL_USTORE.replace('/unidades', `/unidades/${id}/mover`);
    await fetch(url, {
        method: 'PATCH',
        headers: { 'Content-Type':'application/json','X-CSRF-TOKEN':CSRF_PL,'Accept':'application/json' },
        body: JSON.stringify({ dir })
    });
    // Reload to reflect reorder
    window.location.reload();
}

// ── Auto-guardar unidad ────────────────────────────────────────────────────
function autoGuardarUnidad(id) {
    clearTimeout(unitTimers[id]);
    const dot = document.getElementById(`dot-${id}`);
    if (dot) dot.className = 'save-dot saving';
    unitTimers[id] = setTimeout(() => guardarUnidad(id), 700);
}

async function guardarUnidad(id) {
    const dot = document.getElementById(`dot-${id}`);
    const url = URL_USTORE.replace('/unidades', `/unidades/${id}`);

    // Collect competencias
    const compEls = document.querySelectorAll(`#body-${id} .comp-chip.sel`);
    const comps   = Array.from(compEls).map(el => el.dataset.comp);

    const body = {
        titulo:        document.getElementById(`u-titulo-${id}`)?.value ?? '',
        periodo:       document.getElementById(`u-periodo-${id}`)?.value ?? '',
        semanas:       document.getElementById(`u-semanas-${id}`)?.value ?? null,
        objetivos:     document.getElementById(`u-objetivos-${id}`)?.value ?? '',
        competencias:  comps,
        indicadores:   document.getElementById(`u-indicadores-${id}`)?.value ?? '',
        contenidos:    document.getElementById(`u-contenidos-${id}`)?.value ?? '',
        estrategias:   document.getElementById(`u-estrategias-${id}`)?.value ?? '',
        recursos:      document.getElementById(`u-recursos-${id}`)?.value ?? '',
        evaluacion:    document.getElementById(`u-evaluacion-${id}`)?.value ?? '',
        fecha_inicio:  document.getElementById(`u-fi-${id}`)?.value || null,
        fecha_fin:     document.getElementById(`u-ff-${id}`)?.value || null,
    };

    try {
        const r = await fetch(url, {
            method: 'PUT',
            headers: { 'Content-Type':'application/json','X-CSRF-TOKEN':CSRF_PL,'Accept':'application/json' },
            body: JSON.stringify(body)
        });
        const d = await r.json();
        if (d.ok) {
            if (dot) { dot.className = 'save-dot saved'; setTimeout(()=>{ dot.className='save-dot'; }, 2500); }
            // Update header title preview
            const hTitle = document.getElementById(`htitle-${id}`);
            if (hTitle) hTitle.textContent = body.titulo || 'Sin título';
            const hPer = document.getElementById(`hper-${id}`);
            if (hPer) { hPer.textContent = body.periodo || ''; hPer.style.display = body.periodo ? '' : 'none'; }
        } else throw new Error();
    } catch(e) {
        if (dot) dot.className = 'save-dot error';
    }
}

// ── Competencia chip toggle ────────────────────────────────────────────────
function toggleComp(el, id) {
    el.classList.toggle('sel');
    autoGuardarUnidad(id);
}

// ── Renumerar números en DOM ───────────────────────────────────────────────
function renumerarDOM() {
    document.querySelectorAll('.unidad-num').forEach((el, i) => { el.textContent = i + 1; });
}

// ── Build HTML para nueva unidad (sin reload) ──────────────────────────────
function buildUnidadHTML(u) {
    const compChips = COMPS.map(c =>
        `<span class="comp-chip" data-comp="${c}" onclick="toggleComp(this,${u.id})" title="${c}">${c}</span>`
    ).join('');

    return `
<div class="unidad-card" id="unidad-${u.id}">
  <div class="unidad-header" onclick="toggleUnidad(${u.id})">
    <div class="unidad-num">${u.numero}</div>
    <input type="text" id="u-titulo-${u.id}" value="${u.titulo ?? ''}"
        onclick="event.stopPropagation()"
        oninput="autoGuardarUnidad(${u.id})"
        style="flex:1;font-weight:700;font-size:.88rem;border:none;outline:none;background:transparent;color:#1e293b;min-width:80px;"
        placeholder="Título de la unidad">
    <span class="per-badge" id="hper-${u.id}" style="${u.periodo ? '' : 'display:none;'}">${u.periodo ?? ''}</span>
    <div style="display:flex;gap:.3rem;align-items:center;margin-left:auto;">
        <span class="save-dot" id="dot-${u.id}"></span>
        <button onclick="event.stopPropagation();moverUnidad(${u.id},'up')" title="Subir"
            style="background:none;border:none;cursor:pointer;color:#94a3b8;padding:.2rem .3rem;font-size:.85rem;"><i class="bi bi-chevron-up"></i></button>
        <button onclick="event.stopPropagation();moverUnidad(${u.id},'down')" title="Bajar"
            style="background:none;border:none;cursor:pointer;color:#94a3b8;padding:.2rem .3rem;font-size:.85rem;"><i class="bi bi-chevron-down"></i></button>
        <button onclick="event.stopPropagation();eliminarUnidad(${u.id})" title="Eliminar"
            style="background:none;border:none;cursor:pointer;color:#ef4444;padding:.2rem .3rem;font-size:.85rem;"><i class="bi bi-trash3-fill"></i></button>
        <i class="bi bi-chevron-down" style="color:#94a3b8;font-size:.8rem;"></i>
    </div>
  </div>
  <div class="unidad-body" id="body-${u.id}">
    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:.65rem;margin-bottom:.85rem;">
        <div>
            <label class="field-label">Período</label>
            <select id="u-periodo-${u.id}" class="field-inp" style="width:100%;" onchange="autoGuardarUnidad(${u.id})">
                <option value="">— Todos —</option>
                <option value="P1">Período 1</option>
                <option value="P2">Período 2</option>
                <option value="P3">Período 3</option>
                <option value="P4">Período 4</option>
            </select>
        </div>
        <div>
            <label class="field-label">Semanas estimadas</label>
            <input type="number" id="u-semanas-${u.id}" class="field-inp" style="width:100%;" min="1" max="20" value=""
                onchange="autoGuardarUnidad(${u.id})" placeholder="e.g. 4">
        </div>
        <div>
            <label class="field-label">Fechas</label>
            <div style="display:flex;gap:.3rem;align-items:center;">
                <input type="date" id="u-fi-${u.id}" class="field-inp" style="flex:1;" onchange="autoGuardarUnidad(${u.id})">
                <span style="color:#94a3b8;font-size:.7rem;">→</span>
                <input type="date" id="u-ff-${u.id}" class="field-inp" style="flex:1;" onchange="autoGuardarUnidad(${u.id})">
            </div>
        </div>
    </div>
    <div class="field-group">
        <label class="field-label"><i class="bi bi-bullseye"></i> Objetivos generales</label>
        <textarea class="field-ta" id="u-objetivos-${u.id}" rows="2" placeholder="Objetivos de la unidad..." oninput="autoGuardarUnidad(${u.id})"></textarea>
    </div>
    <div class="field-group">
        <label class="field-label"><i class="bi bi-diagram-3-fill"></i> Competencias</label>
        <div style="display:flex;gap:.35rem;flex-wrap:wrap;">${compChips}</div>
    </div>
    <div class="field-group">
        <label class="field-label"><i class="bi bi-check2-all"></i> Indicadores de logro</label>
        <textarea class="field-ta" id="u-indicadores-${u.id}" rows="2" placeholder="Indicadores de logro esperados..." oninput="autoGuardarUnidad(${u.id})"></textarea>
    </div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:.65rem;">
        <div class="field-group">
            <label class="field-label"><i class="bi bi-list-ul"></i> Contenidos / Temas</label>
            <textarea class="field-ta" id="u-contenidos-${u.id}" rows="3" placeholder="Temas y contenidos..." oninput="autoGuardarUnidad(${u.id})"></textarea>
        </div>
        <div class="field-group">
            <label class="field-label"><i class="bi bi-lightbulb-fill"></i> Estrategias / Actividades</label>
            <textarea class="field-ta" id="u-estrategias-${u.id}" rows="3" placeholder="Métodos y actividades..." oninput="autoGuardarUnidad(${u.id})"></textarea>
        </div>
        <div class="field-group">
            <label class="field-label"><i class="bi bi-folder-fill"></i> Recursos</label>
            <textarea class="field-ta" id="u-recursos-${u.id}" rows="2" placeholder="Materiales, libros, TICs..." oninput="autoGuardarUnidad(${u.id})"></textarea>
        </div>
        <div class="field-group">
            <label class="field-label"><i class="bi bi-clipboard-check-fill"></i> Evaluación / Instrumentos</label>
            <textarea class="field-ta" id="u-evaluacion-${u.id}" rows="2" placeholder="Pruebas, rúbricas, portafolio..." oninput="autoGuardarUnidad(${u.id})"></textarea>
        </div>
    </div>
  </div>
</div>`;
}
</script>
@endpush

@endsection
