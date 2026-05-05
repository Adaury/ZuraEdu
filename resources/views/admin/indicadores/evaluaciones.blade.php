@extends('layouts.admin')
@section('page-title', 'Indicadores de Logro')

@push('styles')
<style>
/* Tabla Excel-style */
#tbl-ind {
    border-collapse: collapse;
    font-size: .78rem;
    min-width: max-content;
}
#tbl-ind th, #tbl-ind td {
    border: 1px solid #d1d5db;
    padding: 0;
    white-space: nowrap;
}
#tbl-ind thead th {
    background: #1e3a8a;
    color: #fff;
    font-weight: 700;
    text-align: center;
    font-size: .72rem;
    padding: .3rem .4rem;
    position: sticky;
    top: 0;
    z-index: 3;
}
#tbl-ind thead th.h-ind {
    background: #1e40af;
    max-width: 200px;
    white-space: normal;
    font-size: .68rem;
    padding: .3rem .5rem;
}
.s-num { width: 32px; min-width: 32px; text-align: center; background: #f8faff; font-weight: 600; }
.s-nom { min-width: 200px; max-width: 240px; text-align: left; padding: .25rem .5rem !important; }
.nom-cell { padding: .25rem .5rem !important; font-size: .8rem; }

/* Celdas de nivel */
.nivel-cell {
    min-width: 90px;
    text-align: center;
    padding: 2px !important;
}
.nivel-btn-group {
    display: flex;
    gap: 1px;
    justify-content: center;
    flex-wrap: nowrap;
}
.nivel-btn {
    border: none;
    font-size: .62rem;
    font-weight: 700;
    padding: .18rem .3rem;
    border-radius: 3px;
    cursor: pointer;
    opacity: .35;
    transition: opacity .15s, transform .1s;
    min-width: 22px;
}
.nivel-btn.activo { opacity: 1; transform: scale(1.08); }
.nivel-btn.e-btn  { background: #16a34a; color: #fff; }
.nivel-btn.b-btn  { background: #2563eb; color: #fff; }
.nivel-btn.ep-btn { background: #d97706; color: #fff; }
.nivel-btn.i-btn  { background: #dc2626; color: #fff; }
.nivel-btn:hover  { opacity: .85; }

/* Footer promedio */
.tfoot-row td {
    background: #f0f4ff;
    font-weight: 700;
    font-size: .75rem;
    text-align: center;
    border-top: 2px solid #1e3a8a;
}

/* Leyenda nivel */
.leyenda-item {
    display: inline-flex;
    align-items: center;
    gap: .3rem;
    font-size: .75rem;
    margin-right: .75rem;
}
.leyenda-dot {
    width: 12px; height: 12px;
    border-radius: 3px;
    flex-shrink: 0;
}

/* sticky first 2 cols */
#tbl-ind td:first-child,
#tbl-ind th:first-child {
    position: sticky; left: 0; z-index: 2;
    background: inherit;
}
#tbl-ind td:nth-child(2),
#tbl-ind th:nth-child(2) {
    position: sticky; left: 32px; z-index: 2;
    background: inherit;
}

/* Row highlight */
#tbl-ind tbody tr:hover td { background: #f0f9ff; }

/* Saving indicator */
.saving-flash { animation: flash .4s; }
@keyframes flash { 0%,100%{opacity:1} 50%{opacity:.4} }

[data-theme="dark"] #tbl-ind th, [data-theme="dark"] #tbl-ind td { border-color: #334155; }
[data-theme="dark"] .s-num { background: #162032; color: #93c5fd; }
[data-theme="dark"] #tbl-ind tbody tr:hover td { background: #1a2640; }
</style>
@endpush

@section('content')

{{-- Header --}}
<div class="d-flex align-items-start justify-content-between mb-3">
    <div>
        <h4 class="fw-bold mb-0" style="color:var(--primary)">
            <i class="bi bi-check2-all me-2"></i>Indicadores de Logro
        </h4>
        <p class="text-muted mb-0" style="font-size:.82rem;">
            {{ $asignacion->asignatura->nombre }}
            &nbsp;·&nbsp; {{ $asignacion->grupo->nombre_completo }}
            &nbsp;·&nbsp; {{ $periodo->nombre }}
        </p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.indicadores.evaluaciones.pdf', ['asignacion_id' => $asignacion->id, 'periodo_id' => $periodo->id]) }}"
           target="_blank" class="btn btn-sm btn-danger">
            <i class="bi bi-file-earmark-pdf-fill me-1"></i>PDF
        </a>
        <a href="{{ route('admin.indicadores.evaluaciones.excel', ['asignacion_id' => $asignacion->id, 'periodo_id' => $periodo->id]) }}"
           class="btn btn-sm btn-success">
            <i class="bi bi-file-earmark-excel-fill me-1"></i>Excel
        </a>
        <a href="{{ route('admin.calificaciones.planilla-academica', ['asignacion_id' => $asignacion->id]) }}"
           class="btn btn-sm btn-outline-primary">
            <i class="bi bi-table me-1"></i>Planilla de Notas
        </a>
        <a href="{{ route('admin.calificaciones.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Volver
        </a>
    </div>
</div>

{{-- Selector de período --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-2 px-3 d-flex align-items-center gap-3 flex-wrap">
        <span class="fw-semibold text-muted" style="font-size:.82rem;">Período:</span>
        @foreach($periodos as $p)
        <a href="{{ route('admin.indicadores.evaluaciones', ['asignacion_id' => $asignacion->id, 'periodo_id' => $p->id]) }}"
           class="btn btn-sm {{ $p->id == $periodo->id ? 'btn-primary' : 'btn-outline-primary' }}"
           style="font-size:.78rem;">
            {{ $p->nombre }}
        </a>
        @endforeach
        <span class="ms-auto text-muted" style="font-size:.75rem;">
            <i class="bi bi-info-circle me-1"></i>
            Haz clic en los botones E/B/EP/I para registrar el nivel de logro de cada estudiante
        </span>
    </div>
</div>

{{-- Leyenda --}}
<div class="mb-2">
    <span class="leyenda-item"><span class="leyenda-dot" style="background:#16a34a;"></span> E = Excelente (90-100)</span>
    <span class="leyenda-item"><span class="leyenda-dot" style="background:#2563eb;"></span> B = Bueno (75-89)</span>
    <span class="leyenda-item"><span class="leyenda-dot" style="background:#d97706;"></span> EP = En proceso (60-74)</span>
    <span class="leyenda-item"><span class="leyenda-dot" style="background:#dc2626;"></span> I = Insuficiente (0-59)</span>
    <span class="ms-2 text-muted" style="font-size:.72rem;" id="save-status"></span>
</div>

@if($indicadores->isEmpty())
<div class="alert alert-warning">
    <i class="bi bi-exclamation-triangle me-2"></i>
    No hay indicadores de logro configurados para esta asignatura en el período {{ $periodo->numero }}.
    <a href="{{ route('admin.indicadores.index') }}" class="alert-link">Ir a configurar indicadores</a>
</div>
@else

{{-- Tabla principal --}}
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div style="overflow-x:auto;overflow-y:auto;max-height:70vh;">
            <table id="tbl-ind">
                <thead>
                <tr>
                    <th rowspan="1" class="s-num" style="z-index:5;">#</th>
                    <th rowspan="1" style="min-width:200px;text-align:left;padding:.3rem .5rem !important;z-index:5;">
                        Estudiante
                    </th>
                    @foreach($indicadores as $ind)
                    <th class="h-ind nivel-cell" style="max-width:120px;white-space:normal;vertical-align:top;">
                        <div style="font-size:.65rem;opacity:.8;margin-bottom:.2rem;">IL-{{ $loop->iteration }}</div>
                        <div style="max-width:115px;word-wrap:break-word;font-size:.68rem;font-weight:500;line-height:1.2;">
                            {{ Str::limit($ind->descripcion, 60) }}
                        </div>
                    </th>
                    @endforeach
                </tr>
                </thead>
                <tbody>
                @php $rowNum = 1; @endphp
                @foreach($matriculas as $m)
                @php
                    $evRow = $evaluaciones[$m->id] ?? collect();
                @endphp
                <tr data-mid="{{ $m->id }}">
                    <td class="s-num">{{ $rowNum++ }}</td>
                    <td class="nom-cell s-nom">{{ $m->estudiante->nombre_completo }}</td>
                    @foreach($indicadores as $ind)
                    @php
                        $ev       = $evRow[$ind->id] ?? null;
                        $nivelAct = $ev?->nivel ?? '';
                    @endphp
                    <td class="nivel-cell" data-mid="{{ $m->id }}" data-ind="{{ $ind->id }}">
                        <div class="nivel-btn-group">
                            <button type="button"
                                    class="nivel-btn e-btn {{ $nivelAct === 'Excelente' ? 'activo' : '' }}"
                                    onclick="registrar({{ $m->id }}, {{ $ind->id }}, 'Excelente', this)"
                                    title="Excelente (90-100)">E</button>
                            <button type="button"
                                    class="nivel-btn b-btn {{ $nivelAct === 'Bueno' ? 'activo' : '' }}"
                                    onclick="registrar({{ $m->id }}, {{ $ind->id }}, 'Bueno', this)"
                                    title="Bueno (75-89)">B</button>
                            <button type="button"
                                    class="nivel-btn ep-btn {{ $nivelAct === 'En proceso' ? 'activo' : '' }}"
                                    onclick="registrar({{ $m->id }}, {{ $ind->id }}, 'En proceso', this)"
                                    title="En proceso (60-74)">EP</button>
                            <button type="button"
                                    class="nivel-btn i-btn {{ $nivelAct === 'Insuficiente' ? 'activo' : '' }}"
                                    onclick="registrar({{ $m->id }}, {{ $ind->id }}, 'Insuficiente', this)"
                                    title="Insuficiente (0-59)">I</button>
                        </div>
                    </td>
                    @endforeach
                </tr>
                @endforeach
                </tbody>
                {{-- Footer: resumen por indicador --}}
                <tfoot>
                <tr class="tfoot-row">
                    <td colspan="2" style="text-align:left;padding:.3rem .5rem !important;font-size:.75rem;">
                        Resumen
                    </td>
                    @foreach($indicadores as $ind)
                    @php
                        $totE  = $evaluaciones->filter(fn($r) => isset($r[$ind->id]) && $r[$ind->id]->nivel === 'Excelente')->count();
                        $totB  = $evaluaciones->filter(fn($r) => isset($r[$ind->id]) && $r[$ind->id]->nivel === 'Bueno')->count();
                        $totEP = $evaluaciones->filter(fn($r) => isset($r[$ind->id]) && $r[$ind->id]->nivel === 'En proceso')->count();
                        $totI  = $evaluaciones->filter(fn($r) => isset($r[$ind->id]) && $r[$ind->id]->nivel === 'Insuficiente')->count();
                    @endphp
                    <td style="padding:.3rem .4rem !important;font-size:.7rem;text-align:center;">
                        <span style="color:#16a34a;">{{ $totE }}E</span>
                        <span style="color:#2563eb;"> {{ $totB }}B</span>
                        <span style="color:#d97706;"> {{ $totEP }}EP</span>
                        <span style="color:#dc2626;"> {{ $totI }}I</span>
                    </td>
                    @endforeach
                </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
@endif

<script>
const ROUTE_GUARDAR = "{{ route('admin.indicadores.evaluaciones.guardar') }}";
const CSRF          = "{{ csrf_token() }}";
const PERIODO_ID    = {{ $periodo->id }};

function registrar(matriculaId, indicadorId, nivel, btnEl) {
    // Deactivate siblings
    const cell = btnEl.closest('td');
    cell.querySelectorAll('.nivel-btn').forEach(b => b.classList.remove('activo'));
    btnEl.classList.add('activo');

    // Flash saving
    const status = document.getElementById('save-status');
    status.textContent = 'Guardando...';
    btnEl.classList.add('saving-flash');

    fetch(ROUTE_GUARDAR, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': CSRF,
            'Accept': 'application/json',
        },
        body: JSON.stringify({
            matricula_id: matriculaId,
            indicador_id: indicadorId,
            periodo_id:   PERIODO_ID,
            nivel:        nivel,
        })
    })
    .then(r => r.json())
    .then(data => {
        status.textContent = data.ok ? '✓ Guardado' : '✗ Error';
        setTimeout(() => { status.textContent = ''; }, 1500);
    })
    .catch(() => {
        status.textContent = '✗ Error de conexión';
    })
    .finally(() => {
        btnEl.classList.remove('saving-flash');
    });
}
</script>

@endsection
