@extends('layouts.admin')
@section('page-title', 'Competencias e Indicadores')

@push('styles')
<style>
    .ce-card { background:#fff; border-radius:12px; border:1px solid #e5e7eb;
        margin-bottom:1rem; overflow:hidden; }
    .ce-header { background:#f8fafc; border-bottom:1px solid #e5e7eb;
        padding:.75rem 1rem; display:flex; align-items:center; gap:.75rem; }
    .ce-codigo { display:inline-flex; align-items:center; justify-content:center;
        width:36px; height:36px; background:var(--primary); color:#fff;
        border-radius:8px; font-weight:800; font-size:.8rem; flex-shrink:0; }
    .ce-nombre { font-weight:700; font-size:.9rem; color:#111827; }
    .ce-ciclo-badge { font-size:.65rem; font-weight:700; padding:.2rem .6rem;
        border-radius:20px; }
    .il-row { display:flex; align-items:center; gap:.75rem; padding:.55rem 1rem;
        border-bottom:1px solid #f3f4f6; }
    .il-row:last-child { border-bottom:none; }
    .il-chip { width:28px; height:28px; background:#ede9fe; color:#5b21b6;
        border-radius:6px; display:flex; align-items:center; justify-content:center;
        font-size:.65rem; font-weight:800; flex-shrink:0; }
    .il-desc { font-size:.82rem; color:#374151; flex:1; }
    .btn-icon { border:none; background:none; padding:.2rem .4rem;
        border-radius:6px; cursor:pointer; transition:.15s; }
    .btn-icon:hover { background:#f3f4f6; }
    .sidebar-filter { background:#fff; border-radius:12px; border:1px solid #e5e7eb;
        padding:1.25rem; position:sticky; top:1rem; }
    .asig-btn { display:block; width:100%; text-align:left; border:1.5px solid #e5e7eb;
        border-radius:8px; padding:.5rem .85rem; font-size:.82rem; font-weight:600;
        margin-bottom:.4rem; background:#fff; cursor:pointer; transition:.15s;
        text-decoration:none; color:#374151; }
    .asig-btn:hover { border-color:var(--primary); color:var(--primary); }
    .asig-btn.active { border-color:var(--primary); background:#eef3fb; color:var(--primary); }

    [data-theme="dark"] .ce-card { background: #1e293b; border-color: #334155; }
    [data-theme="dark"] .ce-header { background: #162032; border-bottom-color: #334155; }
    [data-theme="dark"] .ce-nombre { color: #e2e8f0; }
    [data-theme="dark"] .il-chip { background: #2e1065; color: #c4b5fd; }
    [data-theme="dark"] .il-desc { color: #cbd5e1; }
    [data-theme="dark"] .il-row { border-bottom-color: #334155; }
    [data-theme="dark"] .btn-icon:hover { background: #334155; }
    [data-theme="dark"] .sidebar-filter { background: #1e293b; border-color: #334155; }
    [data-theme="dark"] .asig-btn { background: #1e293b; border-color: #334155; color: #94a3b8; }
    [data-theme="dark"] .asig-btn:hover { border-color: var(--primary); color: #93c5fd; }
    [data-theme="dark"] .asig-btn.active { background: #1e3a5f; border-color: var(--primary); color: #93c5fd; }
</style>
@endpush

@section('content')
<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
    <div>
        <h1 style="font-size:1.45rem;font-weight:800;color:var(--primary);margin:0;">
            <i class="bi bi-diagram-3-fill me-2"></i>Competencias e Indicadores
        </h1>
        <p class="text-muted small mb-0">Configura las CE e IL del currículo MINERD por asignatura y ciclo</p>
    </div>
    <a href="{{ route('admin.registro.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Volver al Registro
    </a>
</div>

<div class="row g-4">

    {{-- ── Sidebar: filtros ─────────────────────────────────────── --}}
    <div class="col-lg-3">
        <div class="sidebar-filter">
            {{-- Ciclo --}}
            <div class="mb-3">
                <div style="font-size:.72rem;font-weight:700;text-transform:uppercase;
                             letter-spacing:.07em;color:#6b7280;margin-bottom:.5rem;">Ciclo</div>
                <a href="{{ route('admin.competencias.index', array_merge(request()->all(), ['ciclo'=>'primer_ciclo'])) }}"
                   class="asig-btn {{ $ciclo === 'primer_ciclo' ? 'active' : '' }}">
                    <i class="bi bi-1-circle me-1"></i>Primer Ciclo (1ro–3ro)
                </a>
                <a href="{{ route('admin.competencias.index', array_merge(request()->all(), ['ciclo'=>'segundo_ciclo'])) }}"
                   class="asig-btn {{ $ciclo === 'segundo_ciclo' ? 'active' : '' }}">
                    <i class="bi bi-2-circle me-1"></i>Segundo Ciclo (4to–6to)
                </a>
            </div>

            <hr class="my-2">

            {{-- Asignaturas --}}
            <div style="font-size:.72rem;font-weight:700;text-transform:uppercase;
                         letter-spacing:.07em;color:#6b7280;margin-bottom:.5rem;">Asignatura</div>
            @foreach($asignaturas as $a)
                <a href="{{ route('admin.competencias.index', ['ciclo'=>$ciclo, 'asignatura_id'=>$a->id]) }}"
                   class="asig-btn {{ $asignatura?->id === $a->id ? 'active' : '' }}">
                    <span style="display:inline-block;width:10px;height:10px;border-radius:50%;
                                 background:{{ $a->color ?? '#6b7280' }};margin-right:.4rem;"></span>
                    {{ $a->nombre }}
                </a>
            @endforeach
        </div>
    </div>

    {{-- ── Panel principal ──────────────────────────────────────── --}}
    <div class="col-lg-9">
        @if(!$asignatura)
            <div class="text-center py-5 text-muted">
                <i class="bi bi-cursor-fill d-block mb-2" style="font-size:2rem;"></i>
                Selecciona una asignatura para ver y configurar sus competencias
            </div>
        @else

            {{-- Encabezado con botón agregar CE --}}
            <div class="d-flex align-items-center justify-content-between mb-3">
                <h5 class="fw-800 mb-0" style="color:#111827;">
                    {{ $asignatura->nombre }}
                    <span class="badge ms-2" style="font-size:.65rem;background:{{ $ciclo==='primer_ciclo'?'#dbeafe':'#ede9fe' }};color:{{ $ciclo==='primer_ciclo'?'#1e40af':'#5b21b6' }}">
                        {{ $ciclo === 'primer_ciclo' ? 'Primer Ciclo' : 'Segundo Ciclo' }}
                    </span>
                </h5>
                <button class="btn btn-primary btn-sm" onclick="abrirModalCE()">
                    <i class="bi bi-plus-lg me-1"></i>Agregar Competencia
                </button>
            </div>

            @if($competencias->isEmpty())
                <div class="text-center py-5" style="background:#f9fafb;border-radius:12px;border:1.5px dashed #d1d5db;">
                    <i class="bi bi-diagram-3 d-block mb-2" style="font-size:2rem;color:#9ca3af;"></i>
                    <p class="text-muted mb-2">No hay competencias configuradas.</p>
                    <button class="btn btn-primary btn-sm" onclick="abrirModalCE()">
                        <i class="bi bi-plus-lg me-1"></i>Agregar primera competencia
                    </button>
                </div>
            @else
                @foreach($competencias as $ce)
                <div class="ce-card" id="ce-{{ $ce->id }}">
                    <div class="ce-header">
                        <div class="ce-codigo">{{ $ce->codigo }}</div>
                        <div class="flex-1">
                            <div class="ce-nombre">{{ $ce->nombre }}</div>
                            @if($ce->descripcion)
                                <div style="font-size:.73rem;color:#6b7280;">{{ $ce->descripcion }}</div>
                            @endif
                        </div>
                        <div class="d-flex gap-1 ms-auto">
                            <button class="btn-icon" onclick="abrirModalIL({{ $ce->id }}, '{{ addslashes($ce->nombre) }}')"
                                    title="Agregar indicador">
                                <i class="bi bi-plus-circle text-success"></i>
                            </button>
                            <button class="btn-icon" onclick="editarCE({{ $ce->id }}, '{{ addslashes($ce->nombre) }}', '{{ addslashes($ce->descripcion) }}')"
                                    title="Editar">
                                <i class="bi bi-pencil text-primary"></i>
                            </button>
                            <button class="btn-icon" onclick="eliminarCE({{ $ce->id }})" title="Eliminar">
                                <i class="bi bi-trash text-danger"></i>
                            </button>
                        </div>
                    </div>

                    {{-- ILs --}}
                    <div id="ils-{{ $ce->id }}">
                        @forelse($ce->indicadoresActivos as $il)
                        <div class="il-row" id="il-{{ $il->id }}">
                            <div class="il-chip">{{ $il->codigo }}</div>
                            <div class="il-desc">{{ $il->descripcion }}</div>
                            <div class="d-flex gap-1">
                                <button class="btn-icon" onclick="editarIL({{ $il->id }}, '{{ addslashes($il->descripcion) }}')">
                                    <i class="bi bi-pencil" style="font-size:.75rem;color:#3b82f6;"></i>
                                </button>
                                <button class="btn-icon" onclick="eliminarIL({{ $il->id }})">
                                    <i class="bi bi-trash" style="font-size:.75rem;color:#ef4444;"></i>
                                </button>
                            </div>
                        </div>
                        @empty
                        <div class="il-row text-muted" style="font-size:.8rem;justify-content:center;">
                            <i class="bi bi-info-circle me-1"></i>Sin indicadores de logro. Agrega el primero.
                        </div>
                        @endforelse
                    </div>
                </div>
                @endforeach
            @endif
        @endif
    </div>
</div>

{{-- ── Modal: Nueva / Editar Competencia ───────────────────────── --}}
<div class="modal fade" id="modalCE" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content rounded-4">
            <div class="modal-header border-0">
                <h5 class="modal-title fw-800" id="modalCETitle">
                    <i class="bi bi-plus-circle me-2 text-primary"></i>Agregar Competencia
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body pt-0">
                <input type="hidden" id="ceId">
                <div class="mb-3">
                    <label class="form-label fw-600" style="font-size:.83rem;">Código</label>
                    <input type="text" id="ceCodigo" class="form-control" placeholder="CE1, CE2..."
                           maxlength="10" style="width:120px;">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-600" style="font-size:.83rem;">Nombre de la competencia <span class="text-danger">*</span></label>
                    <input type="text" id="ceNombre" class="form-control"
                           placeholder="Ej: Comprensión lectora" maxlength="250">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-600" style="font-size:.83rem;">Descripción (opcional)</label>
                    <textarea id="ceDesc" class="form-control" rows="2" maxlength="1000"></textarea>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="guardarCE()">
                    <i class="bi bi-check-lg me-1"></i>Guardar
                </button>
            </div>
        </div>
    </div>
</div>

{{-- ── Modal: Nuevo / Editar Indicador ─────────────────────────── --}}
<div class="modal fade" id="modalIL" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content rounded-4">
            <div class="modal-header border-0">
                <h5 class="modal-title fw-800" id="modalILTitle">
                    <i class="bi bi-plus-circle me-2 text-success"></i>Agregar Indicador de Logro
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body pt-0">
                <input type="hidden" id="ilId">
                <input type="hidden" id="ilCEId">
                <div id="ilCENombre" class="mb-3 p-2 rounded-3"
                     style="background:#eef3fb;font-size:.83rem;font-weight:600;color:var(--primary);"></div>
                <div>
                    <label class="form-label fw-600" style="font-size:.83rem;">Descripción del indicador <span class="text-danger">*</span></label>
                    <textarea id="ilDesc" class="form-control" rows="3" maxlength="1000"
                              placeholder="Ej: Identifica el tema central y la idea principal..."></textarea>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success" onclick="guardarIL()">
                    <i class="bi bi-check-lg me-1"></i>Guardar
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
const csrf      = '{{ csrf_token() }}';
const asigId    = {{ $asignatura?->id ?? 'null' }};
const ciclo     = '{{ $ciclo }}';
let modalCE, modalIL;

document.addEventListener('DOMContentLoaded', () => {
    modalCE = new bootstrap.Modal(document.getElementById('modalCE'));
    modalIL = new bootstrap.Modal(document.getElementById('modalIL'));
});

// ── CE ─────────────────────────────────────────────────────────────
function abrirModalCE() {
    document.getElementById('ceId').value = '';
    document.getElementById('ceCodigo').value = '';
    document.getElementById('ceNombre').value = '';
    document.getElementById('ceDesc').value = '';
    document.getElementById('modalCETitle').innerHTML = '<i class="bi bi-plus-circle me-2 text-primary"></i>Agregar Competencia';
    modalCE.show();
}

function editarCE(id, nombre, desc) {
    document.getElementById('ceId').value = id;
    document.getElementById('ceNombre').value = nombre;
    document.getElementById('ceDesc').value = desc ?? '';
    document.getElementById('modalCETitle').innerHTML = '<i class="bi bi-pencil me-2 text-primary"></i>Editar Competencia';
    modalCE.show();
}

async function guardarCE() {
    const id     = document.getElementById('ceId').value;
    const nombre = document.getElementById('ceNombre').value.trim();
    const codigo = document.getElementById('ceCodigo').value.trim();
    const desc   = document.getElementById('ceDesc').value.trim();

    if (!nombre) { alert('El nombre es requerido.'); return; }

    let url, body;
    if (id) {
        url  = `/admin/competencias/ce/${id}`;
        body = {_method:'PUT', nombre, descripcion:desc, _token:csrf};
    } else {
        if (!codigo) { alert('El código es requerido.'); return; }
        url  = '/admin/competencias/ce';
        body = {asignatura_id:asigId, ciclo, codigo, nombre, descripcion:desc, _token:csrf};
    }

    const res  = await fetch(url, {method:'POST', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':csrf,'Accept':'application/json'}, body:JSON.stringify(body)});
    const json = await res.json();

    if (json.ok) { modalCE.hide(); location.reload(); }
    else          alert(json.error ?? 'Error al guardar');
}

async function eliminarCE(id) {
    if (!confirm('¿Eliminar esta competencia? Se borrarán también sus indicadores.')) return;
    const res  = await fetch(`/admin/competencias/ce/${id}`, {method:'DELETE', headers:{'X-CSRF-TOKEN':csrf,'Accept':'application/json'}});
    const json = await res.json();
    if (json.ok) document.getElementById(`ce-${id}`)?.remove();
    else          alert(json.error ?? 'No se puede eliminar (tiene evaluaciones registradas).');
}

// ── IL ─────────────────────────────────────────────────────────────
function abrirModalIL(ceId, ceNombre) {
    document.getElementById('ilId').value   = '';
    document.getElementById('ilCEId').value = ceId;
    document.getElementById('ilDesc').value = '';
    document.getElementById('ilCENombre').textContent = `CE: ${ceNombre}`;
    document.getElementById('modalILTitle').innerHTML = '<i class="bi bi-plus-circle me-2 text-success"></i>Agregar Indicador de Logro';
    modalIL.show();
}

function editarIL(id, desc) {
    document.getElementById('ilId').value   = id;
    document.getElementById('ilDesc').value = desc;
    document.getElementById('modalILTitle').innerHTML = '<i class="bi bi-pencil me-2 text-success"></i>Editar Indicador';
    modalIL.show();
}

async function guardarIL() {
    const id   = document.getElementById('ilId').value;
    const ceId = document.getElementById('ilCEId').value;
    const desc = document.getElementById('ilDesc').value.trim();

    if (!desc) { alert('La descripción es requerida.'); return; }

    let url, body;
    if (id) {
        url  = `/admin/competencias/il/${id}`;
        body = {_method:'PUT', descripcion:desc, _token:csrf};
    } else {
        url  = '/admin/competencias/il';
        body = {competencia_id:ceId, descripcion:desc, _token:csrf};
    }

    const res  = await fetch(url, {method:'POST', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':csrf,'Accept':'application/json'}, body:JSON.stringify(body)});
    const json = await res.json();

    if (json.ok) { modalIL.hide(); location.reload(); }
    else          alert(json.error ?? 'Error al guardar');
}

async function eliminarIL(id) {
    if (!confirm('¿Eliminar este indicador?')) return;
    const res  = await fetch(`/admin/competencias/il/${id}`, {method:'DELETE', headers:{'X-CSRF-TOKEN':csrf,'Accept':'application/json'}});
    const json = await res.json();
    if (json.ok) document.getElementById(`il-${id}`)?.remove();
    else          alert(json.error ?? 'No se puede eliminar (tiene evaluaciones registradas).');
}
</script>
@endpush
