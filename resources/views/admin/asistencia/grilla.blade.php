@extends('layouts.admin')
@section('page-title', 'Hoja de Asistencia')

@push('styles')
<style>
    /* Grid container */
    .grilla-asist-wrapper {
        overflow-x: auto;
        border-radius: 10px;
        box-shadow: 0 2px 12px rgba(0,0,0,.08);
        background: #fff;
    }

    #tabla-grilla {
        border-collapse: separate;
        border-spacing: 0;
        min-width: max-content;
        font-size: .82rem;
    }

    /* Sticky columns */
    #tabla-grilla th.col-num,
    #tabla-grilla td.col-num {
        position: sticky; left: 0; z-index: 4;
        background: #f8faff;
        width: 40px; min-width: 40px;
        text-align: center;
        border-right: 1px solid #dee2e6;
        color: #2563eb; font-weight: 700;
    }
    [data-theme="dark"] #tabla-grilla td.col-num {
        background: #1e293b !important;
        color: #93c5fd !important;
        border-right-color: #334155 !important;
    }
    #tabla-grilla th.col-nombre,
    #tabla-grilla td.col-nombre {
        position: sticky; left: 40px; z-index: 4;
        background: #fff;
        min-width: 180px; max-width: 220px;
        border-right: 2px solid #c7d6f0;
        padding: 6px 10px;
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        font-weight: 700; color: #1d4ed8;
    }
    [data-theme="dark"] #tabla-grilla td.col-nombre {
        background: #1e293b !important;
        color: #93c5fd !important;
        border-right-color: #334155 !important;
    }

    /* Day header cells */
    .th-dia {
        width: 36px; min-width: 36px;
        text-align: center;
        padding: 4px 2px;
        font-size: .75rem;
        border-right: 1px solid #e5e7eb;
        border-bottom: 1px solid #dee2e6;
        background: #1e3a6e;
        color: #fff;
    }
    .th-dia.domingo { background: #6c757d; }
    .th-dia.sabado  { background: #495057; }

    /* Attendance cell */
    .celda-asist {
        width: 36px; min-width: 36px;
        height: 34px;
        text-align: center;
        vertical-align: middle;
        cursor: pointer;
        border-right: 1px solid #e5e7eb;
        border-bottom: 1px solid #e5e7eb;
        font-weight: 700;
        font-size: .78rem;
        transition: opacity .1s;
        user-select: none;
    }
    .celda-asist:hover { opacity: .75; }

    /* State colors */
    .est-presente  { background: #dcfce7; color: #15803d; }
    .est-ausente   { background: #fee2e2; color: #991b1b; }
    .est-tarde     { background: #fef3c7; color: #92400e; }
    .est-excusa    { background: #dbeafe; color: #1d4ed8; }
    .est-retiro    { background: #f3e8ff; color: #6b21a8; }
    .est-vacio     { background: #f9fafb; color: #9ca3af; }

    .celda-asist.domingo,
    .celda-asist.sabado { background: #f1f5f9 !important; cursor: default; color: #94a3b8; }

    /* Stats column */
    .col-stats {
        min-width: 90px;
        padding: 4px 8px;
        font-size: .78rem;
        border-left: 2px solid #c7d6f0;
        white-space: nowrap;
    }

    /* Day bulk controls */
    .day-controls { cursor: pointer; font-size: .65rem; margin-top: 2px; }

    /* Legend */
    .legend-badge {
        display: inline-flex; align-items: center; gap: 5px;
        padding: 3px 10px; border-radius: 20px;
        font-size: .75rem; font-weight: 600;
    }

    /* Month nav */
    .mes-nav-btn {
        width: 36px; height: 36px;
        border-radius: 50%;
        border: 1px solid #e5e7eb;
        background: #fff;
        display: inline-flex; align-items: center; justify-content: center;
        cursor: pointer; transition: all .15s;
        text-decoration: none;
        color: inherit;
    }
    .mes-nav-btn:hover { background: var(--primary); color: #fff; border-color: var(--primary); }

    [data-theme="dark"] .grilla-asist-wrapper { background: #1e293b; }
    [data-theme="dark"] .mes-nav-btn { background: #1e293b; border-color: #334155; color: #e2e8f0; }
</style>
@endpush

@section('content')

{{-- Header --}}
<div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
    <div class="d-flex align-items-center gap-3">
        <a href="{{ route('admin.asistencia.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i>
        </a>
        <div>
            <h5 class="fw-bold mb-0" style="color:var(--primary)">
                <i class="bi bi-calendar3-range me-2"></i>Hoja de Asistencia
            </h5>
            <div class="text-muted" style="font-size:.8rem;">
                {{ $asignacion->asignatura->nombre }} — {{ $asignacion->grupo->nombre_completo }}
                @if($asignacion->docente)
                · <span>{{ $asignacion->docente->nombre_completo }}</span>
                @endif
            </div>
        </div>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.asistencia.grilla.pdf', [$asignacion, 'mes' => request('mes', now()->month), 'anio' => request('anio', now()->year)]) }}"
           target="_blank" class="btn btn-danger btn-sm">
            <i class="bi bi-file-earmark-pdf-fill me-1"></i>PDF
        </a>
        <a href="{{ route('admin.asistencia.reporte', $asignacion) }}" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-bar-chart me-1"></i>Reporte
        </a>
    </div>
</div>

{{-- Month navigation --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-2 px-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div class="d-flex align-items-center gap-3">
            @php
                $mesPrev = $mes == 1 ? 12 : $mes - 1;
                $anioPrev = $mes == 1 ? $anio - 1 : $anio;
                $mesNext = $mes == 12 ? 1 : $mes + 1;
                $anioNext = $mes == 12 ? $anio + 1 : $anio;
            @endphp
            <a href="{{ route('admin.asistencia.grilla', $asignacion) }}?mes={{ $mesPrev }}&anio={{ $anioPrev }}"
               class="mes-nav-btn"><i class="bi bi-chevron-left"></i></a>
            <div class="fw-bold" style="font-size:1.05rem;color:var(--primary);min-width:140px;text-align:center;">
                @php
                    $meses = ['','Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
                @endphp
                {{ $meses[$mes] }} {{ $anio }}
            </div>
            <a href="{{ route('admin.asistencia.grilla', $asignacion) }}?mes={{ $mesNext }}&anio={{ $anioNext }}"
               class="mes-nav-btn"><i class="bi bi-chevron-right"></i></a>
            <a href="{{ route('admin.asistencia.grilla', $asignacion) }}?mes={{ now()->month }}&anio={{ now()->year }}"
               class="btn btn-sm btn-outline-secondary">Hoy</a>
        </div>

        {{-- Legend --}}
        <div class="d-flex flex-wrap gap-2">
            @php
                $legendas = [
                    'presente' => ['#dcfce7','#15803d','P'],
                    'ausente'  => ['#fee2e2','#991b1b','A'],
                    'tarde'    => ['#fef3c7','#92400e','T'],
                    'excusa'   => ['#dbeafe','#1d4ed8','E'],
                    'retiro'   => ['#f3e8ff','#6b21a8','R'],
                ];
            @endphp
            @foreach($legendas as $est => $cfg)
            <span class="legend-badge" style="background:{{ $cfg[0] }};color:{{ $cfg[1] }};">
                <span style="font-weight:800;">{{ $cfg[2] }}</span>{{ ucfirst($est) }}
            </span>
            @endforeach
        </div>
    </div>
</div>

{{-- Grid --}}
<div class="grilla-asist-wrapper">
<table id="tabla-grilla">
    <thead>
        <tr>
            <th class="col-num" style="background:#1e3a6e;color:#fff;border-bottom:2px solid #0f1f3d;">#</th>
            <th class="col-nombre" style="background:#1e3a6e;color:#fff;border-bottom:2px solid #0f1f3d;padding:8px 10px;">Estudiante</th>
            @for($d = 1; $d <= $diasEnMes; $d++)
                @php
                    $fecha = \Carbon\Carbon::createFromDate($anio, $mes, $d);
                    $diaSem = $fecha->dayOfWeek; // 0=domingo,6=sabado
                    $esFds = $diaSem === 0 || $diaSem === 6;
                    $clsFds = $diaSem === 0 ? 'domingo' : ($diaSem === 6 ? 'sabado' : '');
                    $diaNombreCorto = strtoupper(substr($fecha->locale('es')->isoFormat('ddd'), 0, 2));
                @endphp
                <th class="th-dia {{ $clsFds }}" title="{{ $fecha->format('d/m/Y') }}">
                    <div>{{ str_pad($d,2,'0',STR_PAD_LEFT) }}</div>
                    <div style="font-size:.6rem;opacity:.75;">{{ $diaNombreCorto }}</div>
                    @if(!$esFds)
                    <div class="day-controls" onclick="marcarDia({{ $d }}, event)" title="Marcar todos Presente">
                        <i class="bi bi-check-all"></i>
                    </div>
                    @endif
                </th>
            @endfor
            <th class="col-stats" style="background:#1e3a6e;color:#fff;border-bottom:2px solid #0f1f3d;text-align:center;">Resumen</th>
        </tr>
    </thead>
    <tbody>
        @foreach($matriculas as $idx => $matricula)
        @php
            $studentAsist = $asistencias[$matricula->id] ?? collect();
            $totalP = $studentAsist->where('estado','presente')->count();
            $totalA = $studentAsist->where('estado','ausente')->count();
            $totalT = $studentAsist->where('estado','tarde')->count();
            $totalE = $studentAsist->where('estado','excusa')->count();
            $totalR = $studentAsist->where('estado','retiro')->count();
            $totalReg = $studentAsist->count();
            $pctAsist = $totalReg > 0 ? round((($totalP+$totalT)/$totalReg)*100) : null;
        @endphp
        <tr class="fila-estudiante" data-matricula="{{ $matricula->id }}">
            <td class="col-num" style="font-size:.75rem;color:#64748b;">{{ $idx+1 }}</td>
            <td class="col-nombre" title="{{ optional($matricula->estudiante)->nombre_completo }}">
                {{ optional($matricula->estudiante)->nombre_completo ?? '—' }}
            </td>
            @for($d = 1; $d <= $diasEnMes; $d++)
                @php
                    $fechaCell = \Carbon\Carbon::createFromDate($anio, $mes, $d);
                    $diaSemCell = $fechaCell->dayOfWeek;
                    $esFdsCell = $diaSemCell === 0 || $diaSemCell === 6;
                    $clsFdsCell = $diaSemCell === 0 ? 'domingo' : ($diaSemCell === 6 ? 'sabado' : '');
                    $registro = $studentAsist->get($d);
                    $estado = $registro ? $registro->estado : null;
                    $clsEst = $estado ? 'est-'.$estado : 'est-vacio';
                    $letra = $estado ? strtoupper(substr($estado,0,1)) : '·';
                    $fechaStr = $fechaCell->format('Y-m-d');
                @endphp
                @if($esFdsCell)
                <td class="celda-asist {{ $clsFdsCell }}" title="Fin de semana">—</td>
                @else
                <td class="celda-asist {{ $clsEst }}"
                    data-matricula="{{ $matricula->id }}"
                    data-fecha="{{ $fechaStr }}"
                    data-estado="{{ $estado ?? '' }}"
                    data-dia="{{ $d }}"
                    onclick="ciclarEstado(this)"
                    title="{{ $fechaStr }}">{{ $letra }}</td>
                @endif
            @endfor
            <td class="col-stats">
                @if($totalReg > 0)
                <div style="font-size:.72rem;">
                    <span style="color:#15803d;font-weight:700;">P{{ $totalP }}</span>
                    <span style="color:#991b1b;font-weight:700;margin-left:3px;">A{{ $totalA }}</span>
                    <span style="color:#92400e;font-weight:700;margin-left:3px;">T{{ $totalT }}</span>
                    @if($totalE > 0)<span style="color:#1d4ed8;font-weight:700;margin-left:3px;">E{{ $totalE }}</span>@endif
                    @if($totalR > 0)<span style="color:#6b21a8;font-weight:700;margin-left:3px;">R{{ $totalR }}</span>@endif
                </div>
                @if($pctAsist !== null)
                <div class="mt-1">
                    <span style="font-size:.7rem;font-weight:700;color:{{ $pctAsist < 75 ? '#991b1b' : ($pctAsist < 85 ? '#92400e' : '#15803d') }}">
                        {{ $pctAsist }}%
                        @if($pctAsist < 75)<i class="bi bi-exclamation-triangle-fill"></i>@endif
                    </span>
                </div>
                @endif
                @else
                <span style="color:#9ca3af;font-size:.72rem;">Sin datos</span>
                @endif
            </td>
        </tr>
        @endforeach
    </tbody>
</table>
</div>

{{-- Toast --}}
<div class="position-fixed bottom-0 end-0 p-3" style="z-index:9999">
    <div id="toast-asist" class="toast align-items-center border-0 text-white" role="alert">
        <div class="d-flex">
            <div class="toast-body fw-semibold" id="toast-asist-msg">Guardado</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
const ESTADOS       = ['presente','ausente','tarde','excusa','retiro'];
const LETRAS        = {presente:'P', ausente:'A', tarde:'T', excusa:'E', retiro:'R'};
const ASIGNACION_ID = {{ $asignacion->id }};
const CSRF          = document.querySelector('meta[name="csrf-token"]').content;
const ROUTE_TOGGLE  = "{{ route('admin.asistencia.toggle') }}";
const ROUTE_TODOS   = "{{ route('admin.asistencia.marcarTodos') }}";

function ciclarEstado(cell) {
    const actual = cell.dataset.estado || '';
    const idx = ESTADOS.indexOf(actual);
    const siguiente = ESTADOS[(idx + 1) % ESTADOS.length];

    cell.dataset.estado = siguiente;
    cell.className = 'celda-asist est-' + siguiente;
    cell.textContent = LETRAS[siguiente];

    const body = new URLSearchParams({
        _token: CSRF,
        asignacion_id: ASIGNACION_ID,
        matricula_id: cell.dataset.matricula,
        fecha: cell.dataset.fecha,
        estado: siguiente,
    });

    fetch(ROUTE_TOGGLE, {
        method: 'POST',
        headers: {'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF},
        body
    })
    .then(r => r.json())
    .then(data => { if (data.success) showToast('Guardado', 'success'); })
    .catch(() => showToast('Error al guardar', 'danger'));
}

function marcarDia(dia, e) {
    e.stopPropagation();

    const cells = document.querySelectorAll(`.celda-asist[data-dia="${dia}"]`);
    if (!cells.length) return;
    const fecha = cells[0].dataset.fecha;

    const body = new URLSearchParams({
        _token: CSRF,
        asignacion_id: ASIGNACION_ID,
        fecha: fecha,
        estado: 'presente',
    });

    fetch(ROUTE_TODOS, {
        method: 'POST',
        headers: {'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF},
        body
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            cells.forEach(c => {
                c.dataset.estado = 'presente';
                c.className = 'celda-asist est-presente';
                c.textContent = 'P';
            });
            showToast(`${data.count} estudiantes marcados como Presente`, 'success');
        }
    })
    .catch(() => showToast('Error', 'danger'));
}

function showToast(msg, tipo) {
    const el = document.getElementById('toast-asist');
    el.className = `toast align-items-center border-0 text-white bg-${tipo === 'success' ? 'success' : 'danger'}`;
    document.getElementById('toast-asist-msg').textContent = msg;
    new bootstrap.Toast(el, {delay: 2000}).show();
}
</script>
@endpush
