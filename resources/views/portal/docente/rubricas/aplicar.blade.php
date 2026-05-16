@extends('layouts.portal')
@section('page-title', 'Aplicar — '.$rubrica->titulo)
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
.nivel-btn {
    border:2px solid transparent;border-radius:8px;padding:.35rem .6rem;
    font-size:.72rem;font-weight:700;cursor:pointer;transition:.15s;
    background:#f8fafc;color:#475569;text-align:center;line-height:1.2;
}
.nivel-btn.selected { color:#fff;transform:scale(1.04); }
.est-row {
    background:#fff;border:1.5px solid #e2e8f0;border-radius:10px;
    padding:.85rem 1rem;margin-bottom:.65rem;
}
.est-row.guardado { border-left:4px solid #10b981; }
.score-display {
    font-size:1.1rem;font-weight:900;min-width:48px;text-align:center;
}
</style>
@endpush

@section('content')

<div style="display:flex;align-items:center;gap:.7rem;margin-bottom:1rem;flex-wrap:wrap;">
    <a href="{{ route('portal.docente.rubricas.index') }}"
       style="color:#ec4899;text-decoration:none;font-size:.8rem;font-weight:600;display:flex;align-items:center;gap:.3rem;">
        <i class="bi bi-arrow-left"></i>Rúbricas
    </a>
    <span style="color:#cbd5e1;">›</span>
    <h1 style="font-size:1rem;font-weight:800;margin:0;flex:1;">Aplicar: {{ $rubrica->titulo }}</h1>
    @if($asignacion && $aplicaciones->isNotEmpty())
    <a href="{{ route('portal.docente.rubricas.resultados', [$rubrica, 'asignacion_id' => $asignacion->id]) }}"
       style="background:#0ea5e9;color:#fff;border:none;border-radius:8px;padding:.42rem .85rem;font-size:.78rem;font-weight:700;text-decoration:none;display:inline-flex;align-items:center;gap:.35rem;">
        <i class="bi bi-bar-chart-fill"></i>Ver resultados
    </a>
    @endif
</div>

{{-- Selector de asignación --}}
<div class="prt-card" style="padding:.8rem 1rem;margin-bottom:1rem;">
    <form method="GET" style="display:flex;gap:.5rem;align-items:flex-end;flex-wrap:wrap;">
        <div style="flex:1;min-width:200px;">
            <label style="font-size:.72rem;font-weight:600;display:block;margin-bottom:.25rem;color:#64748b;">Clase / Grupo</label>
            <select name="asignacion_id" onchange="this.form.submit()"
                style="width:100%;border:1.5px solid #e2e8f0;border-radius:8px;padding:.48rem .7rem;font-size:.83rem;">
                <option value="">— Selecciona una clase —</option>
                @foreach($asignaciones as $asg)
                <option value="{{ $asg->id }}" {{ $asignacion?->id == $asg->id ? 'selected':'' }}>
                    {{ $asg->asignatura?->nombre }} — {{ $asg->grupo?->nombre_completo }}
                </option>
                @endforeach
            </select>
        </div>
    </form>
</div>

@if($asignacion)

{{-- Niveles leyenda --}}
<div style="display:flex;gap:.5rem;flex-wrap:wrap;margin-bottom:1rem;">
    @foreach($rubrica->niveles as $ni => $nivel)
    <span style="background:{{ $nivel['color'] }}20;color:{{ $nivel['color'] }};border:1px solid {{ $nivel['color'] }}40;border-radius:99px;padding:.2rem .65rem;font-size:.72rem;font-weight:700;">
        {{ $nivel['nombre'] }} ({{ $nivel['pct'] }}%)
    </span>
    @endforeach
    <span style="margin-left:auto;font-size:.75rem;color:#64748b;font-style:italic;display:flex;align-items:center;">
        Puntaje máx: <strong style="color:#ec4899;margin-left:.3rem;">{{ $rubrica->puntaje_max }} pts</strong>
    </span>
</div>

@foreach($matriculas as $mat)
@php $aplic = $aplicaciones->get($mat->id); @endphp
<div class="est-row {{ $aplic ? 'guardado' : '' }}" id="row-{{ $mat->id }}">
    <div style="display:flex;align-items:center;gap:.6rem;margin-bottom:.65rem;flex-wrap:wrap;">
        <div style="width:34px;height:34px;border-radius:50%;background:#fce7f3;display:flex;align-items:center;justify-content:center;font-size:.8rem;font-weight:800;color:#be185d;flex-shrink:0;">
            {{ strtoupper(substr($mat->estudiante?->nombre ?? '?', 0, 1)) }}
        </div>
        <div style="flex:1;min-width:0;">
            <div style="font-weight:700;font-size:.88rem;">{{ $mat->estudiante?->nombre_completo ?? '—' }}</div>
        </div>
        <div style="display:flex;align-items:center;gap:.5rem;">
            <span class="score-display" id="score-{{ $mat->id }}" style="color:{{ $aplic ? '#10b981' : '#94a3b8' }};">
                {{ $aplic ? $aplic->puntaje : '—' }}
            </span>
            <span style="font-size:.72rem;color:#64748b;">/ {{ $rubrica->puntaje_max }}</span>
            <span style="font-size:.72rem;font-weight:700;" id="pct-{{ $mat->id }}" style="color:{{ $aplic ? ($aplic->porcentaje >= 60 ? '#10b981' : '#ef4444') : '#94a3b8' }};">
                {{ $aplic ? '('.$aplic->porcentaje.'%)' : '' }}
            </span>
            <button onclick="guardarEstudiante({{ $mat->id }})"
                style="background:#ec4899;color:#fff;border:none;border-radius:7px;padding:.32rem .75rem;font-size:.75rem;font-weight:700;cursor:pointer;display:flex;align-items:center;gap:.3rem;" id="btn-{{ $mat->id }}">
                <i class="bi bi-floppy"></i>Guardar
            </button>
        </div>
    </div>

    {{-- Criterios --}}
    @foreach($rubrica->criterios as $ci => $crit)
    <div style="margin-bottom:.5rem;">
        <div style="display:flex;align-items:center;gap:.5rem;margin-bottom:.3rem;flex-wrap:wrap;">
            <span style="font-size:.75rem;font-weight:700;color:#475569;flex:1;">
                {{ $crit['nombre'] }} <span style="font-weight:500;color:#94a3b8;">({{ $crit['puntos'] }} pts)</span>
            </span>
        </div>
        <div style="display:flex;gap:.4rem;flex-wrap:wrap;">
            @foreach($rubrica->niveles as $ni => $nivel)
            @php $selected = $aplic && isset($aplic->resultados[$ci]) && $aplic->resultados[$ci] == $ni; @endphp
            <button class="nivel-btn {{ $selected ? 'selected' : '' }}"
                id="btn-{{ $mat->id }}-{{ $ci }}-{{ $ni }}"
                onclick="seleccionarNivel({{ $mat->id }}, {{ $ci }}, {{ $ni }}, this)"
                style="{{ $selected ? 'background:'.$nivel['color'].';border-color:'.$nivel['color'].';' : 'border-color:'.$nivel['color'].'60;' }}">
                {{ $nivel['nombre'] }}<br>
                <span style="font-size:.65rem;opacity:.8;">{{ round($crit['puntos'] * $nivel['pct'] / 100, 1) }} pts</span>
            </button>
            @endforeach
        </div>
    </div>
    @endforeach

    {{-- Observaciones --}}
    <div style="margin-top:.5rem;">
        <textarea id="obs-{{ $mat->id }}" rows="1" placeholder="Observaciones (opcional)..."
            style="width:100%;border:1.5px solid #e2e8f0;border-radius:7px;padding:.42rem .65rem;font-size:.78rem;resize:vertical;font-family:inherit;">{{ $aplic?->observaciones }}</textarea>
    </div>
</div>
@endforeach

@else
<div class="prt-card" style="text-align:center;padding:2.5rem;color:#94a3b8;">
    <i class="bi bi-people" style="font-size:2rem;display:block;margin-bottom:.5rem;"></i>
    <p style="margin:0;font-size:.85rem;">Selecciona una clase para ver los estudiantes.</p>
</div>
@endif

@push('scripts')
<script>
const CSRF       = '{{ csrf_token() }}';
const URL_GUARD  = '{{ route("portal.docente.rubricas.guardar", $rubrica) }}';
const ASG_ID     = {{ $asignacion?->id ?? 'null' }};
const CRITERIOS  = @json($rubrica->criterios);
const NIVELES    = @json($rubrica->niveles);
const resultados = {}; // matricula_id -> {ci: ni}

// Inicializar con aplicaciones existentes
@foreach($matriculas as $mat)
@php $aplic = $aplicaciones->get($mat->id); @endphp
@if($aplic)
resultados[{{ $mat->id }}] = @json($aplic->resultados ?? []);
@else
resultados[{{ $mat->id }}] = {};
@endif
@endforeach

function seleccionarNivel(matId, ci, ni, btn) {
    // Deselect others for this criterio
    NIVELES.forEach((_, nj) => {
        const b = document.getElementById(`btn-${matId}-${ci}-${nj}`);
        if (b) {
            b.classList.remove('selected');
            b.style.background = '';
            b.style.borderColor = NIVELES[nj].color + '60';
        }
    });
    // Select this
    btn.classList.add('selected');
    btn.style.background   = NIVELES[ni].color;
    btn.style.borderColor  = NIVELES[ni].color;

    if (!resultados[matId]) resultados[matId] = {};
    resultados[matId][ci] = ni;

    // Recalcular score en tiempo real
    recalcScore(matId);
}

function recalcScore(matId) {
    let score    = 0;
    const res    = resultados[matId] ?? {};
    const maxPct = Math.max(...NIVELES.map(n => n.pct));

    CRITERIOS.forEach((crit, ci) => {
        const ni = res[ci];
        if (ni !== undefined && ni !== null) {
            score += crit.puntos * (NIVELES[ni].pct / 100);
        }
    });

    const puntajeMax = CRITERIOS.reduce((s, c) => s + c.puntos, 0);
    const pct        = puntajeMax > 0 ? Math.round(score / puntajeMax * 100) : 0;

    document.getElementById(`score-${matId}`).textContent = score.toFixed(1);
    document.getElementById(`score-${matId}`).style.color = '#f59e0b';
    document.getElementById(`pct-${matId}`).textContent   = `(${pct}%)`;
}

async function guardarEstudiante(matId) {
    const btn = document.getElementById(`btn-${matId}`);
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-arrow-clockwise"></i>';

    const res = resultados[matId] ?? {};
    const obs = document.getElementById(`obs-${matId}`)?.value ?? '';

    try {
        const r = await fetch(URL_GUARD, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
            body: JSON.stringify({
                asignacion_id: ASG_ID,
                matricula_id:  matId,
                resultados:    res,
                observaciones: obs,
            })
        });
        const data = await r.json();
        if (!data.ok) throw new Error('Error al guardar');

        document.getElementById(`score-${matId}`).textContent = data.puntaje;
        document.getElementById(`score-${matId}`).style.color = data.porcentaje >= 60 ? '#10b981' : '#ef4444';
        document.getElementById(`pct-${matId}`).textContent   = `(${data.porcentaje}%)`;
        document.getElementById(`row-${matId}`).classList.add('guardado');
        btn.innerHTML = '<i class="bi bi-check-lg"></i> Guardado';
        btn.style.background = '#10b981';
        setTimeout(() => {
            btn.innerHTML = '<i class="bi bi-floppy"></i> Guardar';
            btn.style.background = '#ec4899';
            btn.disabled = false;
        }, 2000);
    } catch(e) {
        btn.innerHTML = '<i class="bi bi-floppy"></i> Guardar';
        btn.disabled  = false;
    }
}
</script>
@endpush

@endsection
