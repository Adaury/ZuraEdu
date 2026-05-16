@extends('layouts.portal')
@section('page-title', 'Conducta — ' . ($asignacion->asignatura?->nombre ?? ''))
@section('portal-name', 'Portal Docente')

@section('sidebar')
    @include('portal.docente._sidebar_clase', ['activeKey' => 'conducta', 'asignacion' => $asignacion])
@endsection

@section('bottom-nav')
<a href="{{ route('portal.docente.dashboard') }}" class="prt-nav-item"><i class="bi bi-house-fill"></i>Inicio</a>
<a href="{{ route('portal.docente.calificaciones', $asignacion) }}" class="prt-nav-item"><i class="bi bi-journal-check"></i>Notas</a>
<a href="{{ route('portal.docente.conducta.index', $asignacion) }}" class="prt-nav-item active"><i class="bi bi-person-check-fill"></i>Conducta</a>
<a href="{{ route('portal.docente.estudiantes', $asignacion) }}" class="prt-nav-item"><i class="bi bi-people-fill"></i>Estudiantes</a>
@endsection

@push('styles')
<style>
.per-tab {
    padding:.38rem .9rem;border-radius:8px;font-size:.78rem;font-weight:700;
    border:1.5px solid #e2e8f0;cursor:pointer;text-decoration:none;
    color:#374151;background:#f8fafc;transition:all .15s;white-space:nowrap;
}
.per-tab:hover { border-color:#7c3aed;color:#7c3aed;background:#f5f3ff; }
.per-tab.active { background:#7c3aed;color:#fff;border-color:#7c3aed; }

.ind-btn {
    display:inline-flex;align-items:center;justify-content:center;
    width:34px;height:26px;border-radius:6px;font-size:.67rem;font-weight:800;
    border:1.5px solid transparent;cursor:pointer;transition:.12s;
    background:#f1f5f9;color:#64748b;
}
.ind-btn:hover { transform:scale(1.08); }
.ind-btn.sel   { color:#fff;transform:scale(1.06); }

.cond-table { width:100%;border-collapse:collapse;font-size:.78rem; }
.cond-table th {
    background:#4c1d95;color:#fff;padding:.5rem .5rem;text-align:center;
    font-size:.68rem;font-weight:700;white-space:nowrap;
}
.cond-table th.left { text-align:left; }
.cond-table td { padding:.42rem .45rem;border-bottom:1px solid #f1f5f9;vertical-align:middle; }
.cond-table tbody tr:hover td { background:#faf5ff; }

.concepto-chip {
    display:inline-block;padding:.12rem .5rem;border-radius:99px;
    font-size:.68rem;font-weight:800;color:#fff;min-width:32px;text-align:center;
}
.save-indicator {
    width:8px;height:8px;border-radius:50%;display:inline-block;
    background:#e2e8f0;transition:.3s;
}
.save-indicator.saving  { background:#f59e0b; }
.save-indicator.saved   { background:#10b981; }
.save-indicator.error   { background:#ef4444; }

.obs-inp {
    width:100%;border:1.5px solid #e2e8f0;border-radius:7px;
    padding:.28rem .5rem;font-size:.72rem;font-family:inherit;
    resize:none;min-width:100px;
}
.obs-inp:focus { outline:none;border-color:#7c3aed; }
</style>
@endpush

@section('content')

{{-- Header --}}
<div style="display:flex;align-items:center;gap:.7rem;margin-bottom:1.1rem;flex-wrap:wrap;">
    <div style="flex:1;min-width:0;">
        <h1 style="font-size:1rem;font-weight:800;margin:0;color:#1e293b;">
            <i class="bi bi-person-check-fill" style="color:#7c3aed;margin-right:.35rem;"></i>
            Informe de Conducta — {{ $asignacion->asignatura?->nombre ?? '—' }}
        </h1>
        <div style="font-size:.72rem;color:#64748b;margin-top:.1rem;">
            {{ $asignacion->grupo?->grado?->nombre }} {{ $asignacion->grupo?->seccion?->nombre }}
            &nbsp;·&nbsp; {{ $matriculas->count() }} estudiante(s)
            @if($schoolYear) &nbsp;·&nbsp; {{ $schoolYear->nombre }} @endif
        </div>
    </div>
    <a href="{{ route('portal.docente.conducta.pdf', $asignacion) }}"
       style="background:#4c1d95;color:#fff;border-radius:8px;padding:.42rem .85rem;font-size:.78rem;font-weight:700;text-decoration:none;display:inline-flex;align-items:center;gap:.35rem;flex-shrink:0;">
        <i class="bi bi-file-earmark-pdf-fill"></i>PDF Informe
    </a>
</div>

{{-- Leyenda escala --}}
<div style="display:flex;gap:.45rem;flex-wrap:wrap;margin-bottom:.85rem;align-items:center;">
    <span style="font-size:.7rem;font-weight:600;color:#64748b;margin-right:.2rem;">Escala:</span>
    @foreach($escala as $val => $e)
    <span style="background:{{ $e['bg'] }};color:{{ $e['color'] }};border:1px solid {{ $e['color'] }}30;border-radius:99px;padding:.15rem .55rem;font-size:.7rem;font-weight:700;">
        {{ $e['label'] }} = {{ $e['nombre'] }}
    </span>
    @endforeach
</div>

{{-- Tabs de período --}}
<div style="display:flex;gap:.4rem;flex-wrap:wrap;margin-bottom:1rem;">
    @foreach($periodos as $p)
    <a href="{{ route('portal.docente.conducta.index', [$asignacion, 'periodo_id' => $p->id]) }}"
       class="per-tab {{ $periodoActual?->id == $p->id ? 'active' : '' }}">
        {{ $p->nombre }}
    </a>
    @endforeach
</div>

@if(!$periodoActual)
<div class="prt-card" style="text-align:center;padding:2.5rem;color:#94a3b8;">
    <i class="bi bi-calendar-x" style="font-size:2rem;display:block;margin-bottom:.5rem;"></i>
    <p style="margin:0;font-size:.85rem;">No hay períodos configurados.</p>
</div>
@else

{{-- Tabla --}}
<div class="prt-card" style="overflow-x:auto;padding:0;">
    <table class="cond-table">
        <thead>
            <tr>
                <th style="width:22px;background:#3b0764;">#</th>
                <th class="left" style="min-width:160px;background:#3b0764;">Estudiante</th>
                @foreach($indicadores as $campo => $ind)
                <th style="min-width:160px;">
                    <i class="bi {{ $ind['icon'] }}" style="margin-right:.2rem;"></i>{{ $ind['label'] }}
                </th>
                @endforeach
                <th style="min-width:120px;">Observaciones</th>
                <th style="min-width:72px;background:#3b0764;">Concepto</th>
                <th style="width:20px;background:#3b0764;"></th>
            </tr>
        </thead>
        <tbody>
            @foreach($matriculas as $i => $mat)
            @php $reg = $registros[$mat->id] ?? null; @endphp
            <tr id="row-{{ $mat->id }}">
                <td style="text-align:center;color:#94a3b8;font-size:.72rem;">{{ $i+1 }}</td>
                <td>
                    <span style="font-weight:700;font-size:.82rem;">
                        {{ $mat->estudiante?->apellidos ?? $mat->estudiante?->apellido ?? '' }},
                        {{ $mat->estudiante?->nombres ?? $mat->estudiante?->nombre ?? '' }}
                    </span>
                </td>
                @foreach($indicadores as $campo => $ind)
                <td>
                    <div style="display:flex;gap:.25rem;flex-wrap:wrap;">
                        @foreach($escala as $val => $e)
                        @php $selVal = $reg?->{$campo}; $sel = $selVal == $val; @endphp
                        <button class="ind-btn {{ $sel ? 'sel' : '' }}"
                            id="btn-{{ $mat->id }}-{{ $campo }}-{{ $val }}"
                            onclick="seleccionar({{ $mat->id }}, '{{ $campo }}', {{ $val }}, this)"
                            style="{{ $sel ? 'background:'.$e['color'].';border-color:'.$e['color'].';' : 'border-color:'.$e['color'].'50;color:'.$e['color'].';' }}"
                            title="{{ $e['nombre'] }}">
                            {{ $e['label'] }}
                        </button>
                        @endforeach
                    </div>
                </td>
                @endforeach
                <td>
                    <textarea class="obs-inp" id="obs-{{ $mat->id }}" rows="1"
                        placeholder="Observación..."
                        onchange="autoGuardar({{ $mat->id }})"
                        onblur="autoGuardar({{ $mat->id }})">{{ $reg?->observaciones }}</textarea>
                </td>
                <td style="text-align:center;" id="concepto-{{ $mat->id }}">
                    @if($reg && $reg->concepto)
                    @php $c = $reg->concepto; @endphp
                    <span class="concepto-chip" style="background:{{ $escala[$c]['color'] }};">
                        {{ $escala[$c]['label'] }}
                    </span>
                    @else
                    <span style="color:#cbd5e1;font-size:.75rem;">—</span>
                    @endif
                </td>
                <td style="text-align:center;">
                    <span class="save-indicator" id="ind-{{ $mat->id }}" title="Estado de guardado"></span>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

@endif

@push('scripts')
<script>
const CSRF_T    = '{{ csrf_token() }}';
const URL_GUARD = '{{ route("portal.docente.conducta.guardar", $asignacion) }}';
const PER_ID    = {{ $periodoActual?->id ?? 'null' }};
const ESCALA    = @json($escala);
const INDICADORES = @json(array_keys($indicadores));

// Estado local: matricula_id => {campo: valor}
const estado = {};

// Inicializar con valores existentes
@foreach($matriculas as $mat)
@php $reg = $registros[$mat->id] ?? null; @endphp
estado[{{ $mat->id }}] = {
    @foreach($indicadores as $campo => $ind)
    '{{ $campo }}': {{ $reg?->{$campo} ?? 'null' }},
    @endforeach
};
@endforeach

const timers = {};

function seleccionar(matId, campo, val, btn) {
    // Deselect all for this field
    Object.keys(ESCALA).forEach(v => {
        const b = document.getElementById(`btn-${matId}-${campo}-${v}`);
        if (!b) return;
        b.classList.remove('sel');
        b.style.background = '';
        b.style.borderColor = ESCALA[v].color + '50';
        b.style.color = ESCALA[v].color;
    });

    // Toggle: if already selected, deselect
    if (estado[matId][campo] == val) {
        estado[matId][campo] = null;
        actualizarConcepto(matId);
        autoGuardar(matId);
        return;
    }

    // Select
    btn.classList.add('sel');
    btn.style.background   = ESCALA[val].color;
    btn.style.borderColor  = ESCALA[val].color;
    btn.style.color        = '#fff';
    estado[matId][campo]   = val;
    actualizarConcepto(matId);
    autoGuardar(matId);
}

function actualizarConcepto(matId) {
    const vals = INDICADORES.map(k => estado[matId][k]).filter(v => v !== null && v !== undefined);
    const cell = document.getElementById(`concepto-${matId}`);
    if (!vals.length) { cell.innerHTML = '<span style="color:#cbd5e1;font-size:.75rem;">—</span>'; return; }
    const prom = vals.reduce((a,b) => a+b, 0) / vals.length;
    let c;
    if (prom >= 4.5) c = 5;
    else if (prom >= 3.5) c = 4;
    else if (prom >= 2.5) c = 3;
    else if (prom >= 1.5) c = 2;
    else c = 1;
    cell.innerHTML = `<span class="concepto-chip" style="background:${ESCALA[c].color};">${ESCALA[c].label}</span>`;
}

function autoGuardar(matId) {
    clearTimeout(timers[matId]);
    const ind = document.getElementById(`ind-${matId}`);
    if (ind) { ind.className = 'save-indicator saving'; }
    timers[matId] = setTimeout(() => guardar(matId), 600);
}

async function guardar(matId) {
    if (!PER_ID) return;
    const ind = document.getElementById(`ind-${matId}`);
    const obs = document.getElementById(`obs-${matId}`)?.value ?? '';

    const body = { matricula_id: matId, periodo_id: PER_ID, observaciones: obs, ...estado[matId] };

    try {
        const r = await fetch(URL_GUARD, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_T, 'Accept': 'application/json' },
            body: JSON.stringify(body)
        });
        const d = await r.json();
        if (!d.ok) throw new Error();
        if (ind) { ind.className = 'save-indicator saved'; setTimeout(() => { ind.className = 'save-indicator'; }, 2500); }
    } catch(e) {
        if (ind) { ind.className = 'save-indicator error'; }
    }
}
</script>
@endpush

@endsection
