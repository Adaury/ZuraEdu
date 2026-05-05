@extends('layouts.admin')
@section('page-title', 'Configurar % Equivalente RA')

@push('styles')
<style>
    .ra-card {
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0,0,0,.08);
        border: none;
    }
    .ra-row {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: .85rem 1.25rem;
        border-bottom: 1px solid #f1f5f9;
        transition: background .15s;
    }
    .ra-row:last-child { border-bottom: none; }
    .ra-row:hover { background: #f8faff; }
    .ra-badge {
        width: 38px; height: 38px;
        border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        font-size: .85rem; font-weight: 800;
        flex-shrink: 0;
        color: #fff;
    }
    .ra-desc {
        flex: 1;
        font-size: .88rem;
        color: #1e293b;
        font-weight: 500;
        line-height: 1.4;
    }
    .ra-num-label {
        font-size: .7rem;
        color: #9ca3af;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: .06em;
    }
    .peso-input-wrap {
        display: flex;
        align-items: center;
        gap: .35rem;
    }
    .peso-input {
        width: 90px;
        text-align: center;
        font-weight: 700;
        font-size: .95rem;
        border-radius: 8px;
        border: 2px solid #e2e8f0;
        padding: .42rem .6rem;
        transition: border-color .15s, box-shadow .15s;
    }
    .peso-input:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(30,58,110,.12);
    }
    .peso-pct {
        font-weight: 700;
        color: #64748b;
        font-size: .9rem;
    }
    .total-bar {
        border-radius: 10px;
        padding: 1rem 1.5rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        transition: background .2s;
        margin-top: 1.25rem;
    }
    .total-bar.ok   { background: #dcfce7; border: 2px solid #86efac; }
    .total-bar.bad  { background: #fee2e2; border: 2px solid #fca5a5; }
    .total-bar.warn { background: #fef3c7; border: 2px solid #fde68a; }
    .total-label { font-weight: 700; font-size: .88rem; }
    .total-value { font-size: 1.35rem; font-weight: 900; }
    .bar-visual {
        height: 8px;
        border-radius: 4px;
        background: #e2e8f0;
        overflow: hidden;
        margin-top: .5rem;
    }
    .bar-fill {
        height: 100%;
        border-radius: 4px;
        transition: width .3s, background .3s;
    }
    /* Distribution bar */
    .dist-bar {
        display: flex;
        height: 14px;
        border-radius: 7px;
        overflow: hidden;
        margin-top: .5rem;
        gap: 1px;
    }
    .dist-segment {
        transition: width .35s ease;
        height: 100%;
    }
    /* Asignatura selector */
    .asign-card {
        cursor: pointer;
        border: 2px solid #e5e7eb;
        border-radius: 10px;
        padding: .75rem 1rem;
        transition: border-color .15s, background .15s;
        margin-bottom: .4rem;
        display: flex;
        align-items: center;
        gap: .75rem;
    }
    .asign-card:hover { border-color: var(--primary-light,#2a4f96); background: #f8faff; }
    .asign-card.selected { border-color: var(--primary); background: #eef3fb; }
    .asign-icon {
        width: 38px; height: 38px;
        background: var(--primary);
        border-radius: 9px;
        display: flex; align-items: center; justify-content: center;
        color: #fff; font-size: .9rem; flex-shrink: 0;
    }
    .asign-card.selected .asign-icon { background: var(--secondary,#c0392b); }
    .no-ra-msg {
        text-align: center;
        padding: 3rem 1rem;
        color: #9ca3af;
    }
    .no-ra-msg i { font-size: 2.5rem; opacity: .3; }
    #save-feedback {
        transition: opacity .3s;
    }

    [data-theme="dark"] .ra-row { border-bottom-color: #334155; }
    [data-theme="dark"] .ra-row:hover { background: #162032; }
    [data-theme="dark"] .asign-card { background: #1e293b; border-color: #334155; }
    [data-theme="dark"] .asign-card.selected { background: #1e3a5f; }
</style>
@endpush

@section('content')

{{-- Breadcrumb --}}
<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb mb-0" style="font-size:.82rem;">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="text-decoration-none">Inicio</a></li>
        <li class="breadcrumb-item active">Config. % Equiv. RA</li>
    </ol>
</nav>

{{-- Header --}}
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold mb-1" style="color:var(--primary);">
            <i class="bi bi-bar-chart-steps me-2"></i>Distribución Equivalente — Resultados de Aprendizaje
        </h4>
        <p class="text-muted mb-0" style="font-size:.86rem;">
            Define el % equivalente de cada RA por asignatura. La distribución debe sumar exactamente 100%.
        </p>
    </div>
    @if($schoolYear)
    <span class="badge rounded-pill px-3 py-2" style="background:var(--accent-light,#fef3c7);color:#92400e;font-size:.8rem;border:1px solid #fcd34d;">
        <i class="bi bi-calendar2-check me-1"></i>{{ $schoolYear->nombre }}
    </span>
    @endif
</div>

<div class="row g-4">

    {{-- Left: Asignatura selector --}}
    <div class="col-lg-4">
        <div class="card ra-card">
            <div class="card-header border-0 pb-0 pt-4 px-4" style="background:transparent;">
                <h6 class="fw-bold mb-1" style="color:var(--primary);">
                    <i class="bi bi-book me-2"></i>Asignaturas con RA
                </h6>
                <p class="text-muted mb-2" style="font-size:.77rem;">Selecciona una asignatura para configurar su distribución equivalente.</p>
                <div class="input-group input-group-sm mb-2">
                    <span class="input-group-text bg-white border-end-0">
                        <i class="bi bi-search text-muted" style="font-size:.75rem;"></i>
                    </span>
                    <input type="text" id="filtro-asign" class="form-control border-start-0 ps-1"
                           placeholder="Buscar asignatura..." autocomplete="off" style="font-size:.81rem;">
                </div>
            </div>
            <div class="card-body pt-2 px-3 pb-3" style="max-height:520px;overflow-y:auto;">
                @forelse($asignaturas as $asign)
                <div class="asign-card {{ $asignaturaSelected?->id === $asign->id ? 'selected' : '' }}"
                     data-id="{{ $asign->id }}"
                     data-nombre="{{ $asign->nombre }}"
                     onclick="seleccionarAsignatura(this)">
                    <div class="asign-icon">
                        <i class="bi bi-journal-bookmark"></i>
                    </div>
                    <div class="flex-grow-1 min-w-0">
                        <div class="fw-semibold" style="font-size:.88rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                            {{ $asign->nombre }}
                        </div>
                        <div class="text-muted" style="font-size:.74rem;">
                            {{ $asign->num_ra }} RA{{ $asign->num_ra !== 1 ? 's' : '' }}
                            · {{ $asign->area ?? 'técnica' }}
                        </div>
                    </div>
                    <i class="bi bi-chevron-right text-muted" style="font-size:.75rem;"></i>
                </div>
                @empty
                <div class="no-ra-msg">
                    <i class="bi bi-journal-x d-block mb-2"></i>
                    <p class="mb-0" style="font-size:.85rem;">
                        No tienes asignaturas con Resultados de Aprendizaje configurados.
                    </p>
                </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Right: RA peso editor --}}
    <div class="col-lg-8">
        <div class="card ra-card" id="ra-editor-card">
            <div class="card-header border-0 pb-0 pt-4 px-4" style="background:transparent;">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="fw-bold mb-0" id="editor-title" style="color:var(--primary);">
                            <i class="bi bi-bar-chart-steps me-2"></i>
                            {{ $asignaturaSelected ? $asignaturaSelected->nombre : 'Selecciona una asignatura' }}
                        </h6>
                        <p class="text-muted mb-0" style="font-size:.77rem;">
                            Ajusta el % equivalente de cada Resultado de Aprendizaje.
                        </p>
                    </div>
                    <button type="button" id="btn-equitativo" class="btn btn-outline-secondary btn-sm"
                            title="Distribución equitativa: 100% ÷ cantidad de RAs">
                        <i class="bi bi-distribute-vertical me-1"></i>Dist. Equitativa
                    </button>
                </div>
            </div>

            {{-- Feedback messages --}}
            <div id="save-feedback" class="mx-4 mt-3" style="display:none;"></div>

            {{-- Loading state --}}
            <div id="ra-loading" class="text-center py-4" style="display:none;">
                <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>
                <span style="font-size:.85rem;color:#6b7280;">Cargando RAs...</span>
            </div>

            {{-- Empty / placeholder state --}}
            <div id="ra-placeholder" class="card-body">
                @if(!$asignaturaSelected)
                <div class="no-ra-msg">
                    <i class="bi bi-arrow-left-circle d-block mb-2"></i>
                    <p class="mb-0">Selecciona una asignatura para editar su distribución equivalente.</p>
                </div>
                @elseif($ras->isEmpty())
                <div class="no-ra-msg">
                    <i class="bi bi-exclamation-circle d-block mb-2"></i>
                    <p class="mb-0">Esta asignatura no tiene Resultados de Aprendizaje registrados.</p>
                    @if(auth()->user()->hasRole('Administrador'))
                    <a href="{{ route('admin.asignaturas.edit', $asignaturaSelected) }}" class="btn btn-sm btn-outline-primary mt-2">
                        <i class="bi bi-pencil me-1"></i>Configurar RAs
                    </a>
                    @endif
                </div>
                @endif
            </div>

            {{-- RA list form --}}
            <div id="ra-form-wrap" style="{{ ($asignaturaSelected && $ras->isNotEmpty()) ? '' : 'display:none;' }}">
                <form id="form-ra" method="POST" action="{{ route('admin.config.ra.update') }}">
                    @csrf
                    <input type="hidden" name="asignatura_id" id="input-asignatura-id"
                           value="{{ $asignaturaSelected?->id }}">

                    <div id="ra-list" class="card-body pt-3 px-0 pb-0">
                        @foreach($ras as $ra)
                        @php
                            $colores = ['#3b82f6','#10b981','#f59e0b','#ef4444','#8b5cf6','#06b6d4','#f97316','#ec4899','#14b8a6','#64748b'];
                            $color   = $colores[($ra->numero - 1) % count($colores)];
                        @endphp
                        <div class="ra-row" data-ra-id="{{ $ra->id }}">
                            <div class="ra-badge" style="background:{{ $color }};">
                                RA{{ $ra->numero }}
                            </div>
                            <div class="ra-desc">
                                <div class="ra-num-label">Resultado de Aprendizaje {{ $ra->numero }}</div>
                                {{ $ra->descripcion ?: 'Sin descripción' }}
                            </div>
                            <div class="peso-input-wrap">
                                <input type="number"
                                       name="pesos[{{ $ra->id }}]"
                                       class="form-control peso-input ra-peso-field"
                                       data-ra-num="{{ $ra->numero }}"
                                       data-color="{{ $color }}"
                                       min="0" max="100" step="0.5"
                                       value="{{ $ra->peso !== null ? $ra->peso : round(100 / $ras->count(), 2) }}"
                                       oninput="recalcRaTotal()">
                                <span class="peso-pct">%</span>
                            </div>
                        </div>
                        @endforeach
                    </div>

                    {{-- Distribution bar --}}
                    <div class="px-4 pt-2">
                        <div class="dist-bar" id="dist-bar"></div>
                    </div>

                    {{-- Total bar --}}
                    <div class="px-4 pb-4">
                        <div id="total-bar-ra" class="total-bar warn">
                            <div>
                                <div class="total-label">Total % distribución</div>
                                <div class="bar-visual mt-2" style="width:200px;">
                                    <div class="bar-fill" id="bar-fill-ra" style="width:0%;background:#f59e0b;"></div>
                                </div>
                            </div>
                            <div class="total-value" id="total-display-ra">0%</div>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-4">
                            <a href="{{ route('admin.config.calificacion') }}" class="btn btn-outline-secondary">
                                <i class="bi bi-sliders me-2"></i>Config. Notas
                            </a>
                            <button type="button" onclick="guardarRa()" id="btn-save-ra"
                                    class="btn btn-primary px-5 fw-bold" disabled>
                                <i class="bi bi-floppy me-2"></i>Guardar Distribución
                            </button>
                        </div>
                    </div>
                </form>
            </div>

        </div>

        {{-- Info card --}}
        <div class="card ra-card mt-4">
            <div class="card-body p-4">
                <h6 class="fw-bold mb-3" style="color:var(--primary);">
                    <i class="bi bi-info-circle me-2"></i>¿Cómo funciona la distribución equivalente?
                </h6>
                <ul class="list-unstyled" style="font-size:.85rem;color:#475569;line-height:1.7;">
                    <li class="mb-2"><i class="bi bi-check2 me-2 text-success"></i>Cada RA tiene un <strong>% equivalente</strong> que representa su parte proporcional del 100% total de la asignatura.</li>
                    <li class="mb-2"><i class="bi bi-check2 me-2 text-success"></i><strong>Distribución equitativa:</strong> 100% ÷ N RAs. Por ejemplo, 4 RAs → cada uno vale 25%.</li>
                    <li class="mb-2"><i class="bi bi-check2 me-2 text-success"></i>La suma de todos los % equivalentes debe ser exactamente <strong>100%</strong> (±0.5 de tolerancia).</li>
                    <li class="mb-2"><i class="bi bi-check2 me-2 text-success"></i>El botón <strong>"Dist. Equitativa"</strong> aplica automáticamente 100% ÷ cantidad de RAs.</li>
                    <li class="mb-2"><i class="bi bi-check2 me-2 text-success"></i>Los cambios afectan el cálculo de notas finales del área técnica al guardar nuevas calificaciones.</li>
                </ul>
            </div>
        </div>

    </div>
</div>

@endsection

@push('scripts')
<script>
const ROUTE_RA_DATOS  = '{{ route('admin.config.ra.datos') }}';
const ROUTE_RA_UPDATE = '{{ route('admin.config.ra.update') }}';
const CSRF_TOKEN      = document.querySelector('meta[name="csrf-token"]').content;

const RA_COLORS = ['#3b82f6','#10b981','#f59e0b','#ef4444','#8b5cf6','#06b6d4','#f97316','#ec4899','#14b8a6','#64748b'];

// ── Filtro de asignaturas ───────────────────────────────────────────────────
(function() {
    const inp = document.getElementById('filtro-asign');
    if (!inp) return;
    inp.addEventListener('input', function() {
        const q = this.value.toLowerCase().trim();
        document.querySelectorAll('[data-id]').forEach(el => {
            const txt = el.textContent.toLowerCase();
            el.style.display = (q === '' || txt.includes(q)) ? '' : 'none';
        });
    });
})();

// ── Seleccionar asignatura via AJAX ─────────────────────────────────────────
function seleccionarAsignatura(el) {
    document.querySelectorAll('.asign-card').forEach(c => c.classList.remove('selected'));
    el.classList.add('selected');

    const asignaturaId   = el.dataset.id;
    const asignaturaNombre = el.dataset.nombre;

    document.getElementById('editor-title').innerHTML =
        '<i class="bi bi-bar-chart-steps me-2"></i>' + asignaturaNombre;
    document.getElementById('input-asignatura-id').value = asignaturaId;
    document.getElementById('ra-loading').style.display   = 'block';
    document.getElementById('ra-placeholder').style.display = 'none';
    document.getElementById('ra-form-wrap').style.display   = 'none';
    document.getElementById('save-feedback').style.display  = 'none';

    fetch(ROUTE_RA_DATOS + '?asignatura_id=' + asignaturaId, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(data => {
        document.getElementById('ra-loading').style.display = 'none';
        if (!data.ras || data.ras.length === 0) {
            document.getElementById('ra-placeholder').style.display = 'block';
            document.getElementById('ra-placeholder').innerHTML = `
                <div class="no-ra-msg">
                    <i class="bi bi-exclamation-circle d-block mb-2"></i>
                    <p class="mb-0">Esta asignatura no tiene Resultados de Aprendizaje registrados.</p>
                </div>`;
            return;
        }
        renderRaList(data.ras, asignaturaId);
        document.getElementById('ra-form-wrap').style.display = 'block';
        recalcRaTotal();
    })
    .catch(() => {
        document.getElementById('ra-loading').style.display = 'none';
        mostrarFeedback('danger', 'Error al cargar los RAs. Intenta de nuevo.');
    });
}

// ── Render lista de RAs ─────────────────────────────────────────────────────
function renderRaList(ras, asignaturaId) {
    const container = document.getElementById('ra-list');
    const n = ras.length;
    container.innerHTML = ras.map((ra, idx) => {
        const color = RA_COLORS[idx % RA_COLORS.length];
        const peso  = ra.peso !== null ? ra.peso : Math.round(100 / n * 100) / 100;
        return `
        <div class="ra-row" data-ra-id="${ra.id}">
            <div class="ra-badge" style="background:${color};">RA${ra.numero}</div>
            <div class="ra-desc">
                <div class="ra-num-label">Resultado de Aprendizaje ${ra.numero}</div>
                ${ra.descripcion || 'Sin descripción'}
            </div>
            <div class="peso-input-wrap">
                <input type="number"
                       name="pesos[${ra.id}]"
                       class="form-control peso-input ra-peso-field"
                       data-ra-num="${ra.numero}"
                       data-color="${color}"
                       min="0" max="100" step="0.5"
                       value="${peso}"
                       oninput="recalcRaTotal()">
                <span class="peso-pct">%</span>
            </div>
        </div>`;
    }).join('');
    document.getElementById('input-asignatura-id').value = asignaturaId;
}

// ── Recalc total en tiempo real ─────────────────────────────────────────────
function recalcRaTotal() {
    let total = 0;
    const campos = document.querySelectorAll('.ra-peso-field');
    campos.forEach(inp => { total += parseFloat(inp.value) || 0; });
    total = Math.round(total * 100) / 100;

    const displayEl = document.getElementById('total-display-ra');
    const barEl     = document.getElementById('total-bar-ra');
    const fillEl    = document.getElementById('bar-fill-ra');
    const btnSave   = document.getElementById('btn-save-ra');

    if (!displayEl) return;

    displayEl.textContent = total + '%';
    fillEl.style.width = Math.min(total, 100) + '%';

    const diff = Math.abs(total - 100);
    if (diff <= 0.5) {
        barEl.className = 'total-bar ok';
        fillEl.style.background = '#22c55e';
        displayEl.style.color   = '#15803d';
        btnSave.disabled = false;
    } else if (total > 100) {
        barEl.className = 'total-bar bad';
        fillEl.style.background = '#ef4444';
        displayEl.style.color   = '#991b1b';
        btnSave.disabled = true;
    } else {
        barEl.className = 'total-bar warn';
        fillEl.style.background = '#f59e0b';
        displayEl.style.color   = '#92400e';
        btnSave.disabled = true;
    }

    // Update distribution bar
    updateDistBar(campos, total);
}

// ── Barra de distribución proporcional ─────────────────────────────────────
function updateDistBar(campos, total) {
    const distBar = document.getElementById('dist-bar');
    if (!distBar) return;
    if (total <= 0) { distBar.innerHTML = ''; return; }
    distBar.innerHTML = Array.from(campos).map(inp => {
        const v     = parseFloat(inp.value) || 0;
        const pct   = (v / total * 100).toFixed(2);
        const color = inp.dataset.color || '#6b7280';
        return `<div class="dist-segment" style="width:${pct}%;background:${color};"></div>`;
    }).join('');
}

// ── Distribución equitativa ─────────────────────────────────────────────────
document.getElementById('btn-equitativo').addEventListener('click', function() {
    const campos = document.querySelectorAll('.ra-peso-field');
    if (campos.length === 0) return;
    const n    = campos.length;
    const base = Math.floor(100 / n * 100) / 100;  // 2 decimales
    const last = Math.round((100 - base * (n - 1)) * 100) / 100; // residuo al último
    campos.forEach((inp, i) => {
        inp.value = (i === n - 1) ? last : base;
    });
    recalcRaTotal();
});

// ── Guardar via AJAX ────────────────────────────────────────────────────────
function guardarRa() {
    const btn   = document.getElementById('btn-save-ra');
    const form  = document.getElementById('form-ra');
    const asignId = document.getElementById('input-asignatura-id').value;

    if (!asignId) { mostrarFeedback('warning', 'Selecciona una asignatura primero.'); return; }

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span>Guardando...';

    const pesos = {};
    document.querySelectorAll('.ra-peso-field').forEach(inp => {
        const raId = inp.name.match(/\[(\d+)\]/)?.[1];
        if (raId) pesos[raId] = inp.value;
    });

    fetch(ROUTE_RA_UPDATE, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': CSRF_TOKEN,
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify({ asignatura_id: asignId, pesos }),
    })
    .then(r => r.json())
    .then(data => {
        btn.innerHTML = '<i class="bi bi-floppy me-2"></i>Guardar Distribución';
        if (data.success) {
            mostrarFeedback('success', data.message ?? 'Distribución guardada correctamente.');
            recalcRaTotal(); // re-enable button if still 100%
        } else {
            mostrarFeedback('danger', data.message ?? 'Error al guardar.');
            btn.disabled = false;
        }
    })
    .catch(() => {
        btn.innerHTML = '<i class="bi bi-floppy me-2"></i>Guardar Distribución';
        btn.disabled = false;
        mostrarFeedback('danger', 'Error de conexión. Intenta de nuevo.');
    });
}

// ── Mostrar feedback ────────────────────────────────────────────────────────
function mostrarFeedback(type, msg) {
    const el = document.getElementById('save-feedback');
    const icons = { success: 'bi-check-circle', danger: 'bi-exclamation-triangle', warning: 'bi-exclamation-circle' };
    el.innerHTML = `
        <div class="alert alert-${type} alert-dismissible mb-0" style="border-radius:8px;">
            <i class="bi ${icons[type] ?? 'bi-info-circle'} me-2"></i>${msg}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>`;
    el.style.display = 'block';
    if (type === 'success') {
        setTimeout(() => { el.style.opacity = '0'; setTimeout(() => { el.style.display = 'none'; el.style.opacity = '1'; }, 400); }, 3000);
    }
}

// ── Init ────────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function() {
    if (document.querySelectorAll('.ra-peso-field').length > 0) {
        recalcRaTotal();
    }
});
</script>
@endpush
