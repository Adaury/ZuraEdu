@extends('layouts.portal')
@section('page-title', $rubrica->titulo)
@section('portal-name', 'Portal Docente')

@section('sidebar')
    @include('portal.docente._sidebar_clase', ['activeKey' => 'rubricas'])
@endsection

@section('bottom-nav')
<a href="{{ route('portal.docente.dashboard') }}" class="prt-nav-item"><i class="bi bi-house-fill"></i>Inicio</a>
<a href="{{ route('portal.docente.rubricas.index') }}" class="prt-nav-item"><i class="bi bi-table"></i>Rúbricas</a>
<a href="{{ route('portal.docente.rubricas.aplicar', $rubrica) }}" class="prt-nav-item active"><i class="bi bi-play-fill"></i>Aplicar</a>
@endsection

@push('styles')
<style>
.rub-table { width:100%;border-collapse:collapse;font-size:.8rem; }
.rub-table th, .rub-table td { border:1.5px solid #e2e8f0;padding:.55rem .65rem;vertical-align:top; }
.rub-table thead th { background:#f8fafc;font-weight:700;text-align:center; }
.rub-table .criterio-cell { font-weight:700;background:#fdf2f8;min-width:130px;color:#be185d; }
.nivel-header { text-align:center;min-width:110px; }
.nivel-color-dot { width:12px;height:12px;border-radius:50%;display:inline-block;vertical-align:middle;margin-right:4px; }
.desc-input {
    width:100%;border:none;background:transparent;font-size:.78rem;
    resize:vertical;min-height:56px;font-family:inherit;color:#475569;
    outline:none;
}
.desc-input:focus { background:#f0f9ff; }
.crit-name-input {
    border:none;background:transparent;font-weight:700;font-size:.82rem;
    color:#be185d;width:100%;outline:none;font-family:inherit;
}
.crit-name-input:focus { background:#fdf2f8;border-radius:4px; }
.pts-input {
    border:1.5px solid #e2e8f0;border-radius:6px;width:58px;
    text-align:center;font-size:.8rem;padding:.25rem .4rem;
}
.nivel-name-input {
    border:none;background:transparent;font-weight:700;text-align:center;
    font-size:.78rem;width:100%;outline:none;font-family:inherit;
}
.nivel-name-input:focus { background:rgba(255,255,255,.5);border-radius:4px; }
</style>
@endpush

@section('content')

<div style="display:flex;align-items:center;gap:.7rem;margin-bottom:1rem;flex-wrap:wrap;">
    <a href="{{ route('portal.docente.rubricas.index') }}"
       style="color:#ec4899;text-decoration:none;font-size:.8rem;font-weight:600;display:flex;align-items:center;gap:.3rem;">
        <i class="bi bi-arrow-left"></i>Mis Rúbricas
    </a>
    <span style="color:#cbd5e1;">›</span>
    <h1 style="font-size:1rem;font-weight:800;margin:0;flex:1;" id="tituloRub">{{ $rubrica->titulo }}</h1>
    <a href="{{ route('portal.docente.rubricas.aplicar', $rubrica) }}"
       style="background:#ec4899;color:#fff;border:none;border-radius:8px;padding:.42rem .9rem;font-size:.78rem;font-weight:700;text-decoration:none;display:inline-flex;align-items:center;gap:.35rem;">
        <i class="bi bi-play-fill"></i>Aplicar
    </a>
    <button onclick="guardarTodo()"
        style="background:#10b981;color:#fff;border:none;border-radius:8px;padding:.42rem .9rem;font-size:.78rem;font-weight:700;cursor:pointer;display:flex;align-items:center;gap:.35rem;" id="btnGuardar">
        <i class="bi bi-floppy"></i>Guardar
    </button>
</div>

@if(session('success'))
<div style="background:#dcfce7;border:1px solid #86efac;border-radius:8px;padding:.6rem 1rem;margin-bottom:1rem;font-size:.8rem;color:#166534;">
    <i class="bi bi-check-circle-fill me-1"></i>{{ session('success') }}
</div>
@endif

<div id="msgGuardado" style="display:none;background:#dcfce7;border:1px solid #86efac;border-radius:8px;padding:.6rem 1rem;margin-bottom:1rem;font-size:.8rem;color:#166534;">
    <i class="bi bi-check-circle-fill me-1"></i>Rúbrica guardada correctamente.
</div>
<div id="msgError" style="display:none;background:#fee2e2;border:1px solid #fca5a5;border-radius:8px;padding:.6rem 1rem;margin-bottom:1rem;font-size:.8rem;color:#991b1b;"></div>

{{-- Info general --}}
<div class="prt-card" style="padding:1rem;margin-bottom:1rem;">
    <div style="display:grid;grid-template-columns:1fr 1fr auto;gap:.7rem;align-items:end;">
        <div>
            <label style="font-size:.72rem;font-weight:600;display:block;margin-bottom:.25rem;color:#64748b;">Título</label>
            <input id="fTitulo" value="{{ $rubrica->titulo }}" maxlength="200"
                style="width:100%;border:1.5px solid #e2e8f0;border-radius:8px;padding:.45rem .7rem;font-size:.85rem;">
        </div>
        <div>
            <label style="font-size:.72rem;font-weight:600;display:block;margin-bottom:.25rem;color:#64748b;">Asignatura</label>
            <select id="fAsignatura"
                style="width:100%;border:1.5px solid #e2e8f0;border-radius:8px;padding:.45rem .7rem;font-size:.82rem;">
                <option value="">General</option>
                @foreach($asignaturas as $a)
                <option value="{{ $a->id }}" {{ $rubrica->asignatura_id == $a->id ? 'selected':'' }}>{{ $a->nombre }}</option>
                @endforeach
            </select>
        </div>
        <div style="text-align:right;">
            <label style="font-size:.72rem;font-weight:600;display:block;margin-bottom:.25rem;color:#64748b;">Puntaje máx.</label>
            <span id="puntajeMax" style="font-size:1.1rem;font-weight:800;color:#ec4899;">{{ $rubrica->puntaje_max }}</span> pts
        </div>
    </div>
    <div style="margin-top:.7rem;">
        <label style="font-size:.72rem;font-weight:600;display:block;margin-bottom:.25rem;color:#64748b;">Descripción</label>
        <textarea id="fDesc" rows="1" style="width:100%;border:1.5px solid #e2e8f0;border-radius:8px;padding:.45rem .7rem;font-size:.82rem;resize:vertical;">{{ $rubrica->descripcion }}</textarea>
    </div>
</div>

{{-- Tabla rúbrica editable --}}
<div style="overflow-x:auto;margin-bottom:1rem;">
    <table class="rub-table" id="tablaRubrica">
        <thead>
            <tr>
                <th style="min-width:140px;">Criterio</th>
                <th style="min-width:65px;text-align:center;">Pts</th>
                @foreach($rubrica->niveles as $ni => $nivel)
                <th class="nivel-header" style="background:{{ $nivel['color'] }}20;">
                    <div style="display:flex;align-items:center;justify-content:center;gap:.3rem;flex-wrap:wrap;">
                        <input type="color" class="nivel-color" data-ni="{{ $ni }}"
                            value="{{ $nivel['color'] }}"
                            style="width:22px;height:22px;border:none;border-radius:4px;cursor:pointer;padding:0;background:none;" title="Color del nivel">
                        <input class="nivel-name-input nivel-name" data-ni="{{ $ni }}"
                            value="{{ $nivel['nombre'] }}" maxlength="60"
                            style="color:{{ $nivel['color'] }};background:transparent;">
                        <input type="hidden" class="nivel-pct" data-ni="{{ $ni }}" value="{{ $nivel['pct'] }}">
                    </div>
                    <div style="font-size:.65rem;color:{{ $nivel['color'] }};font-weight:700;margin-top:.2rem;">
                        <input type="number" class="nivel-pct-input" data-ni="{{ $ni }}"
                            value="{{ $nivel['pct'] }}" min="0" max="100" step="5"
                            style="width:42px;border:1px solid #e2e8f0;border-radius:4px;text-align:center;font-size:.68rem;padding:.1rem .2rem;">%
                    </div>
                </th>
                @endforeach
                <th style="width:36px;"></th>
            </tr>
        </thead>
        <tbody id="tbodyCriterios">
            @foreach($rubrica->criterios as $ci => $crit)
            <tr data-ci="{{ $ci }}">
                <td class="criterio-cell">
                    <input class="crit-name-input crit-nombre" data-ci="{{ $ci }}"
                        value="{{ $crit['nombre'] }}" maxlength="200">
                </td>
                <td style="text-align:center;vertical-align:middle;">
                    <input type="number" class="pts-input crit-puntos" data-ci="{{ $ci }}"
                        value="{{ $crit['puntos'] }}" min="1" max="1000" oninput="recalcMax()">
                </td>
                @foreach($rubrica->niveles as $ni => $nivel)
                <td style="background:{{ $nivel['color'] }}08;">
                    <textarea class="desc-input crit-desc" data-ci="{{ $ci }}" data-ni="{{ $ni }}"
                        placeholder="Descripción del desempeño...">{{ $crit['descriptores'][$ni] ?? '' }}</textarea>
                </td>
                @endforeach
                <td style="text-align:center;vertical-align:middle;">
                    <button onclick="eliminarCriterio(this)" title="Eliminar criterio"
                        style="background:#fee2e2;color:#ef4444;border:none;border-radius:6px;padding:.25rem .42rem;font-size:.78rem;cursor:pointer;">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

<div style="display:flex;gap:.5rem;flex-wrap:wrap;margin-bottom:1.5rem;">
    <button onclick="agregarCriterio()"
        style="background:#fce7f3;color:#be185d;border:1px dashed #f9a8d4;border-radius:8px;padding:.42rem .9rem;font-size:.8rem;font-weight:600;cursor:pointer;display:flex;align-items:center;gap:.35rem;">
        <i class="bi bi-plus-lg"></i>Agregar criterio
    </button>
    <button onclick="agregarNivel()"
        style="background:#f0fdf4;color:#166534;border:1px dashed #86efac;border-radius:8px;padding:.42rem .9rem;font-size:.8rem;font-weight:600;cursor:pointer;display:flex;align-items:center;gap:.35rem;">
        <i class="bi bi-plus-lg"></i>Agregar nivel
    </button>
    <div style="margin-left:auto;font-size:.78rem;color:#64748b;display:flex;align-items:center;gap:.3rem;">
        <i class="bi bi-info-circle"></i>Los cambios no se guardan automáticamente — pulsa <strong>Guardar</strong>.
    </div>
</div>

@push('scripts')
<script>
const CSRF     = '{{ csrf_token() }}';
const URL_UPD  = '{{ route("portal.docente.rubricas.update", $rubrica) }}';
let   numNiveles = {{ count($rubrica->niveles) }};

function recalcMax() {
    let total = 0;
    document.querySelectorAll('.crit-puntos').forEach(i => total += parseFloat(i.value)||0);
    document.getElementById('puntajeMax').textContent = total.toFixed(0);
}

function reindexar() {
    document.querySelectorAll('#tbodyCriterios tr').forEach((tr, ci) => {
        tr.dataset.ci = ci;
        tr.querySelector('.crit-nombre')?.setAttribute('data-ci', ci);
        tr.querySelector('.crit-puntos')?.setAttribute('data-ci', ci);
        tr.querySelectorAll('.crit-desc').forEach((d, ni) => {
            d.setAttribute('data-ci', ci);
            d.setAttribute('data-ni', ni);
        });
    });
}

function agregarCriterio() {
    const tbody  = document.getElementById('tbodyCriterios');
    const ci     = tbody.children.length;
    const celdas = Array.from({length: numNiveles}, (_, ni) => `
        <td style="background:transparent;">
            <textarea class="desc-input crit-desc" data-ci="${ci}" data-ni="${ni}"
                placeholder="Descripción del desempeño..."></textarea>
        </td>`).join('');

    const tr = document.createElement('tr');
    tr.dataset.ci = ci;
    tr.innerHTML = `
        <td class="criterio-cell">
            <input class="crit-name-input crit-nombre" data-ci="${ci}" value="Nuevo criterio" maxlength="200">
        </td>
        <td style="text-align:center;vertical-align:middle;">
            <input type="number" class="pts-input crit-puntos" data-ci="${ci}" value="10" min="1" max="1000" oninput="recalcMax()">
        </td>
        ${celdas}
        <td style="text-align:center;vertical-align:middle;">
            <button onclick="eliminarCriterio(this)" style="background:#fee2e2;color:#ef4444;border:none;border-radius:6px;padding:.25rem .42rem;font-size:.78rem;cursor:pointer;">
                <i class="bi bi-x-lg"></i>
            </button>
        </td>`;
    tbody.appendChild(tr);
    recalcMax();
}

function eliminarCriterio(btn) {
    if (!confirm('¿Eliminar este criterio?')) return;
    btn.closest('tr').remove();
    reindexar();
    recalcMax();
}

function agregarNivel() {
    numNiveles++;
    const ni    = numNiveles - 1;
    const color = '#6366f1';

    // Header
    const thead = document.querySelector('#tablaRubrica thead tr');
    const thBtn = thead.lastElementChild;
    const th    = document.createElement('th');
    th.className = 'nivel-header';
    th.style.background = color + '20';
    th.innerHTML = `
        <div style="display:flex;align-items:center;justify-content:center;gap:.3rem;flex-wrap:wrap;">
            <input type="color" class="nivel-color" data-ni="${ni}" value="${color}"
                style="width:22px;height:22px;border:none;border-radius:4px;cursor:pointer;padding:0;" title="Color">
            <input class="nivel-name-input nivel-name" data-ni="${ni}" value="Nivel ${ni+1}" maxlength="60"
                style="color:${color};background:transparent;">
            <input type="hidden" class="nivel-pct" data-ni="${ni}" value="50">
        </div>
        <div style="font-size:.65rem;color:${color};font-weight:700;margin-top:.2rem;">
            <input type="number" class="nivel-pct-input" data-ni="${ni}" value="50" min="0" max="100" step="5"
                style="width:42px;border:1px solid #e2e8f0;border-radius:4px;text-align:center;font-size:.68rem;padding:.1rem .2rem;">%
        </div>`;
    thead.insertBefore(th, thBtn);

    // Celdas en cada fila
    document.querySelectorAll('#tbodyCriterios tr').forEach(tr => {
        const ci  = tr.dataset.ci;
        const tdBtn = tr.lastElementChild;
        const td    = document.createElement('td');
        td.innerHTML = `<textarea class="desc-input crit-desc" data-ci="${ci}" data-ni="${ni}"
            placeholder="Descripción del desempeño..."></textarea>`;
        tr.insertBefore(td, tdBtn);
    });
}

async function guardarTodo() {
    const btn  = document.getElementById('btnGuardar');
    const msgOk  = document.getElementById('msgGuardado');
    const msgErr = document.getElementById('msgError');
    msgOk.style.display  = 'none';
    msgErr.style.display = 'none';
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-arrow-clockwise"></i> Guardando...';

    // Recopilar niveles
    const niveles = [];
    document.querySelectorAll('#tablaRubrica thead .nivel-name').forEach((inp, ni) => {
        const color = document.querySelector(`.nivel-color[data-ni="${ni}"]`)?.value ?? '#6366f1';
        const pct   = parseInt(document.querySelector(`.nivel-pct-input[data-ni="${ni}"]`)?.value ?? 50);
        niveles.push({ nombre: inp.value, pct, color });
    });

    // Recopilar criterios
    const criterios = [];
    document.querySelectorAll('#tbodyCriterios tr').forEach(tr => {
        const ci          = parseInt(tr.dataset.ci);
        const nombre      = tr.querySelector('.crit-nombre')?.value ?? '';
        const puntos      = parseFloat(tr.querySelector('.crit-puntos')?.value ?? 10);
        const descriptores= [];
        tr.querySelectorAll('.crit-desc').forEach(ta => descriptores.push(ta.value));
        criterios.push({ nombre, puntos, descriptores });
    });

    try {
        const r = await fetch(URL_UPD, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
            body: JSON.stringify({
                titulo:        document.getElementById('fTitulo').value,
                descripcion:   document.getElementById('fDesc').value,
                asignatura_id: document.getElementById('fAsignatura').value || null,
                niveles,
                criterios,
            })
        });
        const data = await r.json();
        if (!data.ok) throw new Error(data.message ?? 'Error');
        document.getElementById('puntajeMax').textContent = data.puntaje_max;
        msgOk.style.display = '';
        setTimeout(() => msgOk.style.display = 'none', 3000);
    } catch(e) {
        msgErr.textContent = e.message;
        msgErr.style.display = '';
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-floppy"></i> Guardar';
    }
}
</script>
@endpush

@endsection
