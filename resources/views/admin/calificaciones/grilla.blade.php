@extends('layouts.admin')
@section('page-title', 'Grilla de Calificaciones')

@push('styles')
<style>
/* ── Wrapper ────────────────────────────────────────────────────────────── */
.grilla-wrapper {
    overflow-x: auto;
    border-radius: 10px;
    box-shadow: 0 2px 12px rgba(0,0,0,.08);
}
#tabla-calificaciones {
    border-collapse: separate;
    border-spacing: 0;
    min-width: 1100px;
    width: 100%;
    font-size: .83rem;
}
#tabla-calificaciones th,
#tabla-calificaciones td {
    white-space: nowrap;
    vertical-align: middle;
    border-bottom: 1px solid #e5e7eb;
    border-right: 1px solid #e5e7eb;
    padding: 0;
}
/* Sticky columns */
#tabla-calificaciones th:nth-child(1), #tabla-calificaciones td:nth-child(1) {
    position: sticky; left: 0; z-index: 3; background: #fff;
    min-width: 44px; width: 44px; text-align: center;
}
#tabla-calificaciones th:nth-child(2), #tabla-calificaciones td:nth-child(2) {
    position: sticky; left: 44px; z-index: 3; background: #fff; min-width: 190px;
}
/* Header row */
#tabla-calificaciones thead th {
    background: var(--primary) !important;
    color: #fff; font-weight: 600; font-size: .78rem;
    letter-spacing: .02em; padding: .65rem .45rem;
    text-align: center; border-color: rgba(255,255,255,.18);
}
#tabla-calificaciones thead th:nth-child(1),
#tabla-calificaciones thead th:nth-child(2) {
    background: #0f1f3d !important; z-index: 5;
}
#tabla-calificaciones thead th:nth-child(2) { text-align: left; padding-left: 1rem; }
/* Section colors */
.th-ra     { background: #1e3a8a !important; }
.th-final  { background: #1e3a6e !important; }
.th-cc     { background: #6d28d9 !important; }
.th-comp   { background: #5b21b6 !important; }
.th-ce     { background: #92400e !important; }
.th-extra  { background: #78350f !important; }
.th-asist  { background: #065f46 !important; }
/* Sub-header */
#fila-promedios-cabecera th {
    background: #1e3a6e14 !important; color: var(--primary) !important;
    font-size: .75rem; font-weight: 700; padding: .4rem .45rem;
    border-top: 2px solid #c7d6f0; border-bottom: 2px solid #c7d6f0;
}
#fila-promedios-cabecera th:nth-child(1),
#fila-promedios-cabecera th:nth-child(2) { background: #eef3fb !important; }
/* Rows */
.fila-estudiante td { padding: .38rem .45rem; }
.fila-estudiante:nth-child(even) td { background: #fafafa; }
.fila-estudiante:nth-child(even) td:nth-child(1),
.fila-estudiante:nth-child(even) td:nth-child(2) { background: #f9fafb; }
.fila-estudiante:hover td { background: #f0f4ff !important; color: #1e293b !important; }
.fila-estudiante:hover td:nth-child(1),
.fila-estudiante:hover td:nth-child(2) { background: #e8eefe !important; color: #1e293b !important; }
/* Dark mode: fondo oscuro + texto claro en hover */
[data-theme="dark"] .fila-estudiante:hover td { background: #1e3a5f !important; color: #f1f5f9 !important; }
[data-theme="dark"] .fila-estudiante:hover td:nth-child(1),
[data-theme="dark"] .fila-estudiante:hover td:nth-child(2) { background: #1e3a5f !important; color: #f1f5f9 !important; }
.num-orden { font-size: .75rem; color: #2563eb; font-weight: 700; }
[data-theme="dark"] .num-orden { color: #93c5fd !important; }
.nombre-estudiante {
    font-weight: 700; font-size: .85rem; color: #1d4ed8;
    padding-left: 1rem; max-width: 190px;
    overflow: hidden; text-overflow: ellipsis;
}
[data-theme="dark"] .nombre-estudiante { color: #93c5fd !important; }
[data-theme="dark"] .fila-estudiante:nth-child(even) td { background: #162032 !important; }
[data-theme="dark"] .ind-excelente { background: #052e16; color: #4ade80; }
[data-theme="dark"] .ind-bueno { background: #0c1f3f; color: #93c5fd; }
[data-theme="dark"] .ind-proceso { background: #1c1000; color: #fcd34d; }
[data-theme="dark"] .ind-insuficiente { background: #1c0000; color: #f87171; }
[data-theme="dark"] .ind-vacio { background: #1e293b; color: #64748b; }
[data-theme="dark"] .avg-footer-row td { background: #162032 !important; border-top-color: #334155; }
[data-theme="dark"] .avg-footer-row td:nth-child(1),
[data-theme="dark"] .avg-footer-row td:nth-child(2) { background: #1e3a5f !important; }
[data-theme="dark"] .action-bar { background: #1e293b; box-shadow: none; }
/* Inputs */
.nota-input {
    width: 64px; border: 1px solid #d1d5db; border-radius: 6px;
    padding: .28rem .35rem; font-size: .84rem; font-weight: 600;
    text-align: center; color: #1e293b; background: #fff;
    transition: border-color .12s, box-shadow .12s, background .12s;
    display: block; margin: 0 auto;
}
.nota-input:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(30,58,110,.15); }
.nota-input:hover { border-color: #9ca3af; }
.nota-input-sm { width: 54px; }
/* Calculated cells */
.calc-cell {
    font-weight: 800; font-size: .9rem; text-align: center;
    min-width: 72px; padding: .38rem .5rem;
}
.color-excelente  { color: #15803d; background: #dcfce7; }
.color-bueno      { color: #1d4ed8; background: #dbeafe; }
.color-proceso    { color: #92400e; background: #fef3c7; }
.color-insuf      { color: #991b1b; background: #fee2e2; }
.color-vacio      { color: #9ca3af; }
/* Asistencia cells */
.asist-cell { text-align: center; min-width: 60px; padding: .38rem .4rem; }
/* Obs */
.obs-campo {
    min-width: 130px; max-width: 170px; resize: none;
    font-size: .78rem; border-radius: 6px; border: 1px solid #d1d5db;
    padding: .28rem .5rem; transition: border-color .12s;
}
.obs-campo:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(30,58,110,.12); }
/* Badge */
.badge-indicador {
    font-size: .7rem; font-weight: 700;
    padding: .28em .6em; border-radius: 20px; white-space: nowrap;
}
.ind-excelente  { background: #dcfce7; color: #15803d; }
.ind-bueno      { background: #dbeafe; color: #1d4ed8; }
.ind-proceso    { background: #fef3c7; color: #92400e; }
.ind-insuficiente { background: #fee2e2; color: #991b1b; }
.ind-vacio      { background: #f3f4f6; color: #6b7280; }
/* Footer */
.avg-footer-row td {
    background: #f0f4ff !important; font-weight: 700;
    font-size: .78rem; color: var(--primary); padding: .45rem;
    border-top: 2px solid #c7d6f0; text-align: center;
}
.avg-footer-row td:nth-child(1),
.avg-footer-row td:nth-child(2) { position: sticky; z-index: 2; background: #eef3fb !important; text-align: left; }
.avg-footer-row td:nth-child(1) { left: 0; }
.avg-footer-row td:nth-child(2) { left: 44px; padding-left: 1rem; }
/* Toast */
#toast-guardado { position: fixed; bottom: 1.5rem; right: 1.5rem; z-index: 9999; min-width: 280px; }
/* Action bar */
.action-bar {
    background: #fff; border-radius: 10px;
    box-shadow: 0 2px 8px rgba(0,0,0,.06);
    padding: .85rem 1.25rem; margin-bottom: 1rem;
}
.badge-ra-mode { background: #7c3aed; color: #fff; font-size: .75rem; padding: .28em .7em; border-radius: 20px; font-weight: 700; }
/* ── RA Criterios (matching portal docente) ─────────────────────────── */
.ra-cell-wrap { display: flex; flex-direction: column; align-items: center; gap: 2px; padding: 3px 2px; min-width: 110px; }
.crit-grid-adm {
    display: grid; grid-template-columns: 1fr 1fr; gap: 2px;
    background: #f5f3ff; border-radius: 5px; padding: 3px;
    border: 1px solid #ede9fe; width: 100%;
}
.crit-blk { display: flex; flex-direction: column; align-items: center; gap: 1px; }
.crit-lbl-adm { font-size: .46rem; font-weight: 800; color: #7c3aed; line-height: 1; text-transform: uppercase; }
.crit-inp-adm {
    width: 36px; text-align: center;
    border: 1px solid #c4b5fd; border-radius: 3px;
    padding: .14rem .08rem; font-size: .7rem; font-weight: 700;
    background: #fff; color: #1e293b;
    -moz-appearance: textfield;
}
.crit-inp-adm::-webkit-inner-spin-button, .crit-inp-adm::-webkit-outer-spin-button { -webkit-appearance: none; }
.crit-inp-adm:focus { outline: none; border-color: #7c3aed; }
/* Nota RA total */
.ra-total-row { display: flex; align-items: center; gap: 3px; margin-top: 2px; }
.ra-total-lbl { font-size: .48rem; font-weight: 700; color: #5b21b6; white-space: nowrap; }
/* Recovery section */
.rec-adm { display: none; flex-direction: column; align-items: center; gap: 2px; margin-top: 3px; width: 100%; }
.rec-adm.show { display: flex; }
.rec-adm-header { font-size: .46rem; font-weight: 800; color: #dc2626; background: #fef2f2; border: 1px solid #fecaca; border-radius: 3px; padding: .1rem .25rem; width: 100%; text-align: center; }
.rec-adm-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 2px; width: 100%; }
.rec-adm-blk { display: flex; flex-direction: column; align-items: center; gap: 1px; }
.rec-adm-lbl { font-size: .44rem; font-weight: 700; color: #b91c1c; line-height: 1; }
.rec-adm-inp {
    width: 36px; text-align: center;
    border: 1px solid #fca5a5; border-radius: 3px;
    padding: .14rem .08rem; font-size: .7rem; font-weight: 700;
    background: #fff7f7; color: #991b1b;
    -moz-appearance: textfield;
}
.rec-adm-inp::-webkit-inner-spin-button, .rec-adm-inp::-webkit-outer-spin-button { -webkit-appearance: none; }
.rec-adm-cf { font-size: .52rem; font-weight: 800; border-radius: 3px; padding: .1rem .3rem; margin-top: 1px; }
[data-theme="dark"] .crit-grid-adm { background: #2e1b4e; border-color: #6d28d9; }
[data-theme="dark"] .crit-inp-adm { background: #0f172a; border-color: #7c3aed; color: #e2e8f0; }
[data-theme="dark"] .rec-adm-header { background: #3f0b0b; border-color: #991b1b; color: #fca5a5; }
[data-theme="dark"] .rec-adm-inp { background: #1c0000; border-color: #991b1b; color: #fca5a5; }
</style>
@endpush

@section('content')

@php
    $esRA      = $esRA      ?? false;
    $rasDB     = $rasDB     ?? collect();
    $numRA     = $numRA     ?? 0;
    $raPesosJs = $raPesosJs ?? [];
    $raList    = $esRA
        ? ($rasDB->count() > 0 ? $rasDB : collect(range(1, max($numRA, 1)))->map(fn($n) => (object)['numero'=>$n,'descripcion'=>"RA {$n}",'peso'=>null,'id'=>null]))
        : collect();
    $compLabels = ['tareas'=>'Tareas','practicas'=>'Prácticas','participacion'=>'Participación','proyecto'=>'Proyecto','examen'=>'Examen'];
    $activeComps = [];
    if (!$esRA) {
        foreach(array_keys($compLabels) as $comp) {
            if(isset($pesos[$comp])) $activeComps[] = $comp;
        }
    }
    // Peso default por RA si no está configurado
    $numRaTotal = $raList->count() ?: 1;
    $canConfigRaPesos = $esRA && $asignacion->area === 'tecnica' && $rasDB->count() > 0;
@endphp

{{-- Breadcrumb --}}
<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb mb-0" style="font-size:.82rem;">
        <li class="breadcrumb-item"><a href="{{ route('admin.calificaciones.index') }}" class="text-decoration-none">Calificaciones</a></li>
        <li class="breadcrumb-item active">Grilla</li>
    </ol>
</nav>

{{-- Header card --}}
<div class="card border-0 shadow-sm mb-3" style="background:linear-gradient(135deg,var(--primary),#2a4f96);">
    <div class="card-body py-3 px-4 text-white">
        <div class="row align-items-center">
            <div class="col-md-8">
                <div class="d-flex align-items-center gap-2 mb-1">
                    <h5 class="fw-bold mb-0">{{ $asignacion->asignatura->nombre }}</h5>
                    @if($esRA)<span class="badge-ra-mode"><i class="bi bi-star me-1"></i>Modo RA</span>@endif
                    <span class="badge" style="background:rgba(255,255,255,.2);font-size:.72rem;">Área Técnica</span>
                </div>
                <div class="d-flex flex-wrap gap-3" style="font-size:.82rem;opacity:.88;">
                    <span><i class="bi bi-people me-1"></i>{{ $asignacion->grupo->nombre_completo }}</span>
                    <span><i class="bi bi-person-badge me-1"></i>{{ optional($asignacion->docente)->nombre_completo ?? 'Sin docente' }}</span>
                    <span><i class="bi bi-calendar3 me-1"></i>{{ $periodo->nombre }} — Período {{ $periodo->numero }}</span>
                    <span><i class="bi bi-people-fill me-1"></i>{{ $matriculas->count() }} estudiantes</span>
                </div>
            </div>
            <div class="col-md-4 text-md-end mt-2 mt-md-0">
                <div class="d-flex gap-2 justify-content-md-end flex-wrap">
                    @if($canConfigRaPesos)
                    <button class="btn btn-warning btn-sm fw-semibold" data-bs-toggle="modal" data-bs-target="#modal-ra-pesos">
                        <i class="bi bi-sliders me-1"></i>Configurar pesos RA
                    </button>
                    @endif
                    <button id="btn-guardar" class="btn btn-light fw-bold px-4 btn-save-ripple" onclick="guardarTodo()">
                        <i class="bi bi-floppy me-2"></i>Guardar Todo
                    </button>
                    @php $primeraCalif = collect($calificaciones)->first(); @endphp
                    <button id="btn-publicar" class="btn btn-outline-light px-3"
                            onclick="publicarCalificaciones()"
                            data-publicado="{{ ($primeraCalif?->publicado) ? '1' : '0' }}">
                        <i class="bi bi-eye me-1"></i>
                        <span id="btn-publicar-text">{{ ($primeraCalif?->publicado) ? 'Publicado' : 'Publicar' }}</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Info bar --}}
<div class="action-bar d-flex flex-wrap gap-3 align-items-center justify-content-between">
    <div class="d-flex flex-wrap gap-2 align-items-center" style="flex:1;">
        @if($esRA)
            <span class="text-muted fw-semibold" style="font-size:.78rem;">Pesos RA:</span>
            @foreach($raList as $ra)
            @php $pesoRaInfo = $ra->peso ?? round(100 / $numRaTotal, 1); @endphp
            <span class="badge rounded-pill px-2 py-1" style="background:#f3e8ff;color:#7c3aed;font-size:.74rem;border:1px solid #ddd6fe;">
                <strong>RA{{ $ra->numero }}:</strong> {{ $pesoRaInfo }}%
                {{ $ra->descripcion ? '· '.Str::limit($ra->descripcion, 18) : '' }}
            </span>
            @endforeach
        @else
            <span class="text-muted fw-semibold" style="font-size:.78rem;">Pesos:</span>
            @foreach($activeComps as $comp)
            <span class="badge rounded-pill px-2 py-1" style="background:#eef3fb;color:var(--primary);font-size:.74rem;border:1px solid #c7d6f0;">
                {{ ucfirst($comp) }}: <strong>{{ $pesos[$comp]->peso }}%</strong>
            </span>
            @endforeach
        @endif
    </div>
    <div class="d-flex gap-2 align-items-center flex-wrap" style="font-size:.78rem;color:#6b7280;">
        <a href="{{ route('admin.calificaciones.acta-pdf', $asignacion) }}" target="_blank"
           class="btn btn-danger btn-sm" style="font-size:.76rem;">
            <i class="bi bi-file-earmark-pdf-fill me-1"></i>Acta PDF
        </a>
        <a href="{{ route('admin.calificaciones.acta-excel', $asignacion) }}"
           class="btn btn-success btn-sm" style="font-size:.76rem;">
            <i class="bi bi-file-earmark-excel-fill me-1"></i>Acta Excel
        </a>
        <span><i class="bi bi-keyboard me-1"></i>Enter = siguiente</span>
        <span><i class="bi bi-save me-1"></i>Auto-guarda al salir de celda</span>
        <span id="save-status-gr" class="save-status save-status--idle ms-2">
            <i class="bi bi-check-circle-fill"></i>
            <span id="save-status-gr-text">Listo</span>
        </span>
        <span id="unsaved-gr" class="unsaved-indicator" style="display:none;">
            <i class="bi bi-circle-fill" style="font-size:.45rem;"></i>Sin guardar
        </span>
        <button class="btn btn-outline-secondary btn-sm"
                type="button"
                data-bs-toggle="collapse"
                data-bs-target="#shortcuts-help-gr"
                style="font-size:.76rem;padding:.28rem .65rem;">
            <i class="bi bi-keyboard me-1"></i>Atajos
        </button>
    </div>
</div>
<div class="collapse mt-2" id="shortcuts-help-gr">
    <div class="card card-body py-2 px-3 shadow-sm" style="font-size:.75rem;background:#f8fafc;border-radius:8px;">
        <div class="row g-1 g-md-2">
            <div class="col-6"><kbd>↑ ↓</kbd> Mover entre filas</div>
            <div class="col-6"><kbd>Enter</kbd> Confirmar y bajar</div>
            <div class="col-6"><kbd>Esc</kbd> Cancelar cambio</div>
            <div class="col-6"><kbd>Tab</kbd> Siguiente campo</div>
        </div>
    </div>
</div>

{{-- ═══════════════════ TABLE ═══════════════════ --}}
<div class="grilla-wrapper">
<table id="tabla-calificaciones">
    <thead>
        <tr>
            <th>#</th>
            <th>Estudiante</th>

            {{-- RA / Componentes --}}
            @if($esRA)
                @foreach($raList as $ra)
                @php
                    $pesoRa = $ra->peso ?? round(100 / $numRaTotal, 1);
                @endphp
                <th class="th-ra">
                    RA{{ $ra->numero }}
                    <br><small style="font-weight:700;opacity:.9;font-size:.72rem;color:#fde68a;">{{ $pesoRa }}%</small>
                    @if($ra->descripcion)<br><small style="font-weight:400;opacity:.7;font-size:.64rem;">{{ Str::limit($ra->descripcion, 16) }}</small>@endif
                </th>
                @endforeach
            @else
                @foreach($activeComps as $comp)
                <th class="th-ra">{{ $compLabels[$comp] }}<br><small style="font-weight:400;opacity:.75;font-size:.7rem;">({{ $pesos[$comp]->peso }}%)</small></th>
                @endforeach
            @endif

            {{-- Final --}}
            <th class="th-final" style="min-width:72px;">PROM.<br><small style="font-weight:400;font-size:.68rem;opacity:.8;">FINAL</small></th>

            {{-- Completivo --}}
            <th class="th-cc" style="min-width:66px;">C.C<br><small style="font-weight:400;font-size:.66rem;opacity:.85;">Complet.</small><i class="bi bi-question-circle ms-1" data-bs-toggle="tooltip" data-bs-placement="top" title="Calificación Completiva: nota de la prueba de completivo (50% Promedio Final + 50% C.C = Completivo)" style="font-size:.65rem;cursor:help;opacity:.75;vertical-align:middle;"></i></th>
            <th class="th-comp" style="min-width:78px;">COMPLETIVO<br><small style="font-weight:400;font-size:.64rem;opacity:.85;">50%F+50%CC</small><i class="bi bi-question-circle ms-1" data-bs-toggle="tooltip" data-bs-placement="top" title="Fórmula: 50% Promedio Final + 50% Calificación Completiva" style="font-size:.65rem;cursor:help;opacity:.75;vertical-align:middle;"></i></th>

            {{-- Extraordinario --}}
            <th class="th-ce" style="min-width:66px;">C.E<br><small style="font-weight:400;font-size:.66rem;opacity:.85;">Extraord.</small><i class="bi bi-question-circle ms-1" data-bs-toggle="tooltip" data-bs-placement="top" title="Calificación Extraordinaria: nota de la prueba extraordinaria (30% Promedio Final + 70% C.E = Extraordinario)" style="font-size:.65rem;cursor:help;opacity:.75;vertical-align:middle;"></i></th>
            <th class="th-extra" style="min-width:78px;">EXTRAORDIN.<br><small style="font-weight:400;font-size:.64rem;opacity:.85;">30%F+70%CE</small><i class="bi bi-question-circle ms-1" data-bs-toggle="tooltip" data-bs-placement="top" title="Fórmula: 30% Promedio Final + 70% Calificación Extraordinaria" style="font-size:.65rem;cursor:help;opacity:.75;vertical-align:middle;"></i></th>

            {{-- Asistencia --}}
            <th class="th-asist" style="min-width:58px;">ASIST.<br><small style="font-weight:400;font-size:.66rem;opacity:.85;">Clases</small></th>
            <th class="th-asist" style="min-width:58px;">TOTAL<br><small style="font-weight:400;font-size:.66rem;opacity:.85;">Clases</small></th>
            <th class="th-asist" style="min-width:62px;">%ASIST.<i class="bi bi-question-circle ms-1" data-bs-toggle="tooltip" data-bs-placement="top" title="Porcentaje de asistencia: (clases asistidas / total de clases) × 100" style="font-size:.65rem;cursor:help;opacity:.75;vertical-align:middle;"></i></th>

            {{-- Meta --}}
            <th style="min-width:100px;">INDICADOR</th>
            <th style="min-width:150px;">OBSERVACIONES</th>
        </tr>

        {{-- Promedios de clase --}}
        <tr id="fila-promedios-cabecera">
            <th colspan="2" style="text-align:left;padding-left:1rem;">
                <i class="bi bi-bar-chart-fill me-1"></i>Prom. clase
            </th>
            @if($esRA)
                @foreach($raList as $ra)<th id="avg-ra{{ $ra->numero }}">—</th>@endforeach
            @else
                @foreach($activeComps as $comp)<th id="avg-{{ $comp }}">—</th>@endforeach
            @endif
            <th id="avg-general">—</th>
            <th></th><th></th><th></th><th></th>
            <th></th><th></th><th></th><th></th><th></th>
        </tr>
    </thead>

    <tbody id="tbody-estudiantes">
        @php $num = 1; @endphp
        @foreach($matriculas as $m)
        @php $cal = $calificaciones[$m->id] ?? null; @endphp
        <tr class="fila-estudiante" data-matricula-id="{{ $m->id }}" data-fila="{{ $loop->index }}">
            <td class="num-orden">{{ $num++ }}</td>
            <td class="nombre-estudiante" title="{{ $m->estudiante->nombre_completo }}">
                {{ $m->estudiante->nombre_completo }}
            </td>

            {{-- Notas RA o componentes --}}
            @if($esRA)
                @foreach($raList as $ra)
                @php
                    $raN   = $ra->numero;
                    $raKey = "ra{$raN}";
                    $critSaved = $cal?->criterios_ra[$raN] ?? null;
                    $recSaved  = $cal?->recuperaciones_ra[$raN] ?? null;
                    $notaRaVal = $cal?->{$raKey} ?? '';
                    $pMax  = $ra->peso ?? round(100 / max($raList->count(),1), 2);
                @endphp
                <td style="padding:2px;">
                    <div class="ra-cell-wrap">
                        {{-- Criterios grid (T.P./EX./C.C./O.H./P.D./E.C.) --}}
                        <div class="crit-grid-adm">
                            @foreach([
                                ['tp','T.P.',30],['ex','EX.',15],
                                ['cc','C.C.',10],['oh','O.H.',20],
                                ['pd','P.D.',15],['ec','E.C.',10]
                            ] as [$ck,$cl,$cm])
                            <div class="crit-blk">
                                <span class="crit-lbl-adm">{{ $cl }}</span>
                                <input type="number" class="crit-inp-adm nota-campo"
                                       data-componente="criterios[{{ $raN }}][{{ $ck }}]"
                                       data-matricula="{{ $m->id }}"
                                       data-ra="{{ $raN }}"
                                       data-crit="{{ $ck }}"
                                       data-max="{{ $cm }}"
                                       min="0" max="{{ $cm }}" step="0.5"
                                       value="{{ $critSaved[$ck] ?? '' }}"
                                       placeholder="{{ $cm }}"
                                       oninput="recalcCritAdm(this)"
                                       onblur="autoGuardarCelda(this)">
                            </div>
                            @endforeach
                        </div>
                        {{-- Nota RA (calculada o manual) --}}
                        <div class="ra-total-row">
                            <span class="ra-total-lbl">RA:</span>
                            <input type="number" class="nota-input nota-campo nota-input-sm"
                                   id="ra-val-{{ $m->id }}-{{ $raN }}"
                                   data-componente="{{ $raKey }}"
                                   data-fila="{{ $loop->parent->index }}"
                                   data-matricula="{{ $m->id }}"
                                   data-ra="{{ $raN }}"
                                   data-pmax="{{ $pMax }}"
                                   min="0" max="{{ $pMax }}" step="any"
                                   value="{{ $notaRaVal }}"
                                   placeholder="—"
                                   oninput="colorInput(this); actualizarFila(this); toggleRecAdm(this)"
                                   onblur="autoGuardarCelda(this)"
                                   style="width:44px;font-size:.72rem;">
                        </div>
                        {{-- Recuperación (visible cuando nota < umbral) --}}
                        <div class="rec-adm {{ ($notaRaVal !== '' && (float)$notaRaVal < 70 && $notaRaVal !== null) ? 'show' : '' }}"
                             id="rec-adm-{{ $m->id }}-{{ $raN }}">
                            <div class="rec-adm-header">🔴 Recuperación</div>
                            <div class="rec-adm-grid">
                                @foreach([
                                    ['practica','Prác.',25],
                                    ['exposicion','Exp.',25],
                                    ['practica_eval','P.Ev.',50]
                                ] as [$rk,$rl,$rm])
                                <div class="rec-adm-blk">
                                    <span class="rec-adm-lbl">{{ $rl }}/{{ $rm }}</span>
                                    <input type="number" class="rec-adm-inp"
                                           data-rk="{{ $rk }}"
                                           data-matricula="{{ $m->id }}"
                                           data-ra="{{ $raN }}"
                                           min="0" max="{{ $rm }}" step="0.5"
                                           value="{{ $recSaved[$rk] ?? '' }}"
                                           placeholder="{{ $rm }}"
                                           oninput="recalcRecAdm(this)"
                                           onblur="autoGuardarCelda(this)">
                                </div>
                                @endforeach
                                {{-- CF recovery span (full width last item) --}}
                                <div class="rec-adm-blk" style="grid-column:1/-1;">
                                    <span class="rec-adm-lbl">CF Rec.</span>
                                    <span class="rec-adm-cf" id="rec-cf-{{ $m->id }}-{{ $raN }}"
                                          style="background:#fee2e2;color:#dc2626;">
                                        {{ $recSaved ? number_format($recSaved['cf'] ?? 0, 1) : '—' }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </td>
                @endforeach
            @else
                @foreach($activeComps as $comp)
                <td>
                    <input type="number" class="nota-input nota-campo"
                           data-componente="{{ $comp }}"
                           data-fila="{{ $loop->parent->index }}"
                           data-matricula="{{ $m->id }}"
                           min="0" max="100" step="0.5"
                           value="{{ $cal?->{$comp} ?? '' }}"
                           placeholder="—"
                           oninput="colorInput(this); actualizarFila(this)"
                           onblur="autoGuardarCelda(this)">
                </td>
                @endforeach
            @endif

            {{-- Promedio final --}}
            <td class="calc-cell color-vacio" id="promedio-{{ $m->id }}">—</td>

            {{-- C.C --}}
            <td>
                <input type="number" class="nota-input nota-campo nota-input-sm"
                       data-componente="nota_cc"
                       data-fila="{{ $loop->index }}"
                       data-matricula="{{ $m->id }}"
                       min="0" max="100" step="0.5"
                       value="{{ $cal?->nota_cc ?? '' }}"
                       placeholder="—"
                       oninput="colorInput(this); calcularCompletivo({{ $m->id }})"
                       onblur="autoGuardarCelda(this)">
            </td>
            {{-- COMPLETIVO auto --}}
            <td class="calc-cell color-vacio" id="completivo-{{ $m->id }}" title="50% Promedio + 50% C.C">—</td>

            {{-- C.E --}}
            <td>
                <input type="number" class="nota-input nota-campo nota-input-sm"
                       data-componente="nota_ce"
                       data-fila="{{ $loop->index }}"
                       data-matricula="{{ $m->id }}"
                       min="0" max="100" step="0.5"
                       value="{{ $cal?->nota_ce ?? '' }}"
                       placeholder="—"
                       oninput="colorInput(this); calcularExtraordinario({{ $m->id }})"
                       onblur="autoGuardarCelda(this)">
            </td>
            {{-- EXTRAORDINARIO auto --}}
            <td class="calc-cell color-vacio" id="extraordinario-{{ $m->id }}" title="30% Promedio Final + 70% C.E">—</td>

            {{-- Asistencia --}}
            <td>
                <input type="number" class="nota-input nota-campo nota-input-sm"
                       data-componente="asistencia_clases"
                       data-fila="{{ $loop->index }}"
                       data-matricula="{{ $m->id }}"
                       min="0" max="999" step="1"
                       value="{{ $cal?->asistencia_clases ?? '' }}"
                       placeholder="—"
                       oninput="calcularAsistencia({{ $m->id }})"
                       onblur="autoGuardarCelda(this)">
            </td>
            <td>
                <input type="number" class="nota-input nota-campo nota-input-sm"
                       data-componente="asistencia_total"
                       data-fila="{{ $loop->index }}"
                       data-matricula="{{ $m->id }}"
                       min="0" max="999" step="1"
                       value="{{ $cal?->asistencia_total ?? '' }}"
                       placeholder="—"
                       oninput="calcularAsistencia({{ $m->id }})"
                       onblur="autoGuardarCelda(this)">
            </td>
            <td class="asist-cell color-vacio" id="pct-asist-{{ $m->id }}">—</td>

            {{-- Indicador --}}
            <td style="text-align:center;padding:.38rem .45rem;">
                <span class="badge-indicador ind-vacio" id="indicador-{{ $m->id }}">—</span>
            </td>

            {{-- Observaciones --}}
            <td style="padding:.28rem .45rem;">
                <textarea class="obs-campo form-control form-control-sm" rows="1"
                          data-matricula="{{ $m->id }}"
                          onblur="autoGuardarObs(this)">{{ $cal?->observaciones ?? '' }}</textarea>
            </td>
        </tr>
        @endforeach
    </tbody>

    <tfoot>
        <tr class="avg-footer-row">
            <td><i class="bi bi-calculator"></i></td>
            <td><i class="bi bi-bar-chart-fill me-1"></i>Promedio clase</td>
            @if($esRA)
                @foreach($raList as $ra)<td id="foot-avg-ra{{ $ra->numero }}">—</td>@endforeach
            @else
                @foreach($activeComps as $comp)<td id="foot-avg-{{ $comp }}">—</td>@endforeach
            @endif
            <td id="foot-avg-general">—</td>
            <td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td>
        </tr>
    </tfoot>
</table>
</div>

{{-- Bottom bar --}}
<div class="d-flex justify-content-between align-items-center mt-3">
    <a href="{{ route('admin.calificaciones.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-2"></i>Volver
    </a>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.calificaciones.resumen', ['grupo_id' => $asignacion->grupo_id]) }}"
           class="btn btn-outline-primary">
            <i class="bi bi-grid-3x3 me-2"></i>Ver Resumen
        </a>
        <button class="btn btn-primary px-5 fw-bold" onclick="guardarTodo()">
            <i class="bi bi-floppy me-2"></i>Guardar Todo
        </button>
    </div>
</div>

{{-- Toast --}}
<div id="toast-guardado" class="toast align-items-center border-0" role="alert">
    <div class="d-flex">
        <div class="toast-body fw-semibold" id="toast-msg">Guardando...</div>
        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
    </div>
</div>

@if($canConfigRaPesos)
{{-- Modal configuración de pesos RA --}}
<div class="modal fade" id="modal-ra-pesos" tabindex="-1" aria-labelledby="modalRaPesosLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background:linear-gradient(135deg,#6d28d9,#7c3aed);color:#fff;">
                <h5 class="modal-title fw-bold" id="modalRaPesosLabel">
                    <i class="bi bi-sliders me-2"></i>Configurar pesos de Resultados de Aprendizaje
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">

                <div class="alert alert-info border-0 py-2 mb-3" style="font-size:.84rem;background:#ede9fe;">
                    <i class="bi bi-info-circle me-1 text-primary"></i>
                    Asigna un porcentaje a cada RA. La suma total <strong>debe ser exactamente 100%</strong>.
                    El sistema calculará el promedio ponderado automáticamente.
                </div>

                {{-- Tabla de pesos --}}
                <table class="table table-sm align-middle mb-3" style="font-size:.88rem;">
                    <thead>
                        <tr style="background:#f3e8ff;">
                            <th style="width:70px;">RA</th>
                            <th>Descripción</th>
                            <th style="width:120px;text-align:center;">Peso (%)</th>
                            <th style="width:70px;text-align:center;">Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($raList as $ra)
                        @php
                            $pesoActual = $ra->peso ?? round(100 / $numRaTotal, 1);
                        @endphp
                        <tr>
                            <td>
                                <span class="badge" style="background:#7c3aed;color:#fff;font-size:.82rem;">RA{{ $ra->numero }}</span>
                            </td>
                            <td style="color:#374151;">{{ $ra->descripcion ?: '—' }}</td>
                            <td>
                                <div class="input-group input-group-sm">
                                    <input type="number"
                                           class="form-control text-center fw-bold ra-peso-input"
                                           data-ra-id="{{ $ra->id }}"
                                           data-ra-numero="{{ $ra->numero }}"
                                           min="0" max="100" step="0.5"
                                           value="{{ $pesoActual }}"
                                           id="th-ra-peso-{{ $ra->numero }}"
                                           oninput="actualizarTotalPesos()"
                                           style="border-color:#7c3aed;">
                                    <span class="input-group-text" style="background:#f3e8ff;color:#7c3aed;font-weight:700;">%</span>
                                </div>
                            </td>
                            <td class="text-center">
                                <span class="badge {{ $ra->activo ? 'bg-success' : 'bg-secondary' }}" style="font-size:.7rem;">
                                    {{ $ra->activo ? 'Activo' : 'Inactivo' }}
                                </span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr style="background:#f9fafb;">
                            <td colspan="2" class="text-end fw-bold" style="font-size:.88rem;">
                                Total:
                            </td>
                            <td class="text-center">
                                <span id="total-pesos-display" class="fw-bold fs-5">—%</span>
                            </td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>

                {{-- Barra de distribución visual --}}
                <div class="mb-2" style="font-size:.8rem;color:#6b7280;">
                    <i class="bi bi-bar-chart-fill me-1 text-primary"></i>
                    Distribución visual de pesos:
                </div>
                <div class="d-flex w-100 rounded overflow-hidden mb-3" style="height:20px;">
                    @foreach($raList as $idx => $ra)
                    @php
                        $pesoBar  = $ra->peso ?? round(100 / $numRaTotal, 1);
                        $colores  = ['#7c3aed','#1d4ed8','#065f46','#be123c','#b45309','#0f766e','#9333ea','#dc2626','#2563eb','#16a34a'];
                        $colorBar = $colores[$idx % count($colores)];
                    @endphp
                    <div style="width:{{ $pesoBar }}%;background:{{ $colorBar }};transition:width .3s;"
                         title="RA{{ $ra->numero }}: {{ $pesoBar }}%"></div>
                    @endforeach
                </div>

            </div>
            <div class="modal-footer d-flex justify-content-between">
                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="distribuirEquitativamente()">
                    <i class="bi bi-distribute-vertical me-1"></i>Distribuir equitativamente
                </button>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn fw-bold px-4" id="btn-guardar-pesos"
                            onclick="guardarRaPesos()"
                            style="background:#7c3aed;color:#fff;border:none;">
                        <i class="bi bi-check-lg me-1"></i>Guardar pesos
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endif

{{-- JS Config --}}
@php
    $pesosJs = [];
    if (!$esRA) {
        foreach($activeComps as $comp) {
            $pesosJs[$comp] = (float)$pesos[$comp]->peso;
        }
    }
    $raColsJs = $esRA ? $raList->pluck('numero')->map(fn($n) => "ra{$n}")->values()->toArray() : [];
@endphp
<script>
    const PESOS              = @json($pesosJs);
    const ES_RA              = {{ $esRA ? 'true' : 'false' }};
    const RA_COLS            = @json($raColsJs);
    let   RA_PESOS           = @json($raPesosJs);   // mutable: updated after saving config
    const ROUTE_GUARDAR      = "{{ route('admin.calificaciones.guardar') }}";
    const ROUTE_PUBLICAR     = "{{ route('admin.calificaciones.publicar') }}";
    const ROUTE_RA_PESOS     = "{{ route('admin.calificaciones.guardar-ra-pesos') }}";
    const ASIGNACION_ID      = {{ $asignacion->id }};
    const PERIODO_ID         = {{ $periodo->id }};
    const NUM_RA_TOTAL       = {{ $numRaTotal }};
    const CSRF_TOKEN         = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
</script>
@endsection

@push('scripts')
<script>
/* ── Save status indicator ───────────────────────────────────────────── */
function setSaveStatusGr(state, text) {
    const el  = document.getElementById('save-status-gr');
    const txt = document.getElementById('save-status-gr-text');
    if (!el || !txt) return;
    el.className = 'save-status save-status--' + state + ' ms-2';
    txt.textContent = text;
    const icon = el.querySelector('i');
    if (icon) {
        if (state === 'saving')     icon.className = 'bi bi-arrow-repeat';
        else if (state === 'saved') icon.className = 'bi bi-check-circle-fill';
        else if (state === 'error') icon.className = 'bi bi-exclamation-circle-fill';
        else                        icon.className = 'bi bi-check-circle-fill';
    }
}

/* ── Cambios sin guardar ─────────────────────────────────────────────── */
let _hayPendientesGr = false;
function marcarPendienteGr() {
    if (!_hayPendientesGr) {
        _hayPendientesGr = true;
        const el = document.getElementById('unsaved-gr');
        if (el) el.style.display = 'inline-flex';
    }
}
function limpiarPendienteGr() {
    _hayPendientesGr = false;
    const el = document.getElementById('unsaved-gr');
    if (el) el.style.display = 'none';
}
window.addEventListener('beforeunload', function(e) {
    if (_hayPendientesGr) {
        e.preventDefault();
        return e.returnValue = '¿Deseas salir? Tienes cambios sin guardar.';
    }
});

/* ── Toggle sección recuperación por nota RA ────────────────────────── */
function toggleRecAdm(raInp) {
    const raN   = raInp.dataset.ra;
    const matId = raInp.dataset.matricula;
    const v     = parseFloat(raInp.value);
    const recDiv = document.getElementById(`rec-adm-${matId}-${raN}`);
    if (recDiv && raInp.value !== '') recDiv.classList.toggle('show', v < 70);
}

/* ── Criterios RA → calcula Nota RA automáticamente ─────────────────── */
function recalcCritAdm(inp) {
    marcarPendienteGr();
    const cell    = inp.closest('.ra-cell-wrap');
    if (!cell) return;
    const raN     = inp.dataset.ra;
    const matId   = inp.dataset.matricula;
    const raInp   = cell.querySelector(`[id="ra-val-${matId}-${raN}"]`);
    if (!raInp) return;
    const pMax    = parseFloat(raInp.dataset.pmax || 100);

    // Weights: tp=30, ex=15, cc=10, oh=20, pd=15, ec=10 (sum=100)
    const weights = { tp:30, ex:15, cc:10, oh:20, pd:15, ec:10 };
    let suma = 0, totalMax = 0;
    let allEmpty = true;
    cell.querySelectorAll('.crit-inp-adm').forEach(ci => {
        const ck = ci.dataset.crit;
        const v  = ci.value.trim();
        if (v !== '') { allEmpty = false; }
        suma     += (v !== '' && !isNaN(parseFloat(v))) ? Math.min(parseFloat(v), weights[ck]||0) : 0;
        totalMax += weights[ck] || 0;
    });
    if (!allEmpty) {
        const notaRA = Math.round(suma / 100 * pMax * 100) / 100;
        raInp.value = notaRA;
        colorInput(raInp);
        actualizarFila(raInp);
        // Show/hide recovery section
        const recDiv = document.getElementById(`rec-adm-${matId}-${raN}`);
        if (recDiv) {
            recDiv.classList.toggle('show', notaRA < 70);
        }
    }
}

/* ── Recuperación RA → calcula CF ────────────────────────────────────── */
function recalcRecAdm(inp) {
    marcarPendienteGr();
    const cell  = inp.closest('.ra-cell-wrap');
    if (!cell) return;
    const raN   = inp.dataset.ra;
    const matId = inp.dataset.matricula;
    const raInp = cell.querySelector(`[id="ra-val-${matId}-${raN}"]`);
    const pMax  = parseFloat(raInp?.dataset.pmax || 100);
    const notaAcum = raInp && raInp.value !== '' ? parseFloat(raInp.value) / pMax * 100 : 0;

    // pesos: practica=25, exposicion=25, practica_eval=50
    let notaRec = 0;
    cell.querySelectorAll('.rec-adm-inp').forEach(ri => {
        const v = ri.value.trim();
        notaRec += (v !== '' && !isNaN(parseFloat(v))) ? parseFloat(v) : 0;
    });
    const cf = Math.round((0.5 * notaAcum + 0.5 * notaRec) * 10) / 10;
    const cfSpan = document.getElementById(`rec-cf-${matId}-${raN}`);
    if (cfSpan) {
        cfSpan.textContent = cf.toFixed(1);
        cfSpan.style.background = cf >= 70 ? '#dcfce7' : '#fee2e2';
        cfSpan.style.color      = cf >= 70 ? '#15803d' : '#dc2626';
    }
}

/* ── Colores input con validación inline (Task 3.7) ──────────────────── */
function colorInput(inp) {
    inp.parentNode.querySelectorAll('.ni-error-tip').forEach(e => e.remove());
    inp.classList.remove('ni-invalid');

    const raw = inp.value.trim();
    if (raw === '' || raw === '—' || raw === null) {
        inp.style.background = '';
        inp.style.color      = '';
        return;
    }
    const v = parseFloat(raw);
    if (isNaN(v)) {
        inp.classList.add('ni-invalid');
        inp.style.background = '#fee2e2';
        inp.style.color      = '#991b1b';
        _showNiTipGr(inp, 'Solo números');
        return;
    }
    if (v < 0 || v > 100) {
        inp.classList.add('ni-invalid');
        inp.style.background = '#fee2e2';
        inp.style.color      = '#991b1b';
        _showNiTipGr(inp, v < 0 ? 'Mín: 0' : 'Máx: 100');
        return;
    }
    if      (v >= 80) { inp.style.background = '#dcfce7'; inp.style.color = '#15803d'; }
    else if (v >= 60) { inp.style.background = '#fef9c3'; inp.style.color = '#854d0e'; }
    else              { inp.style.background = '#fee2e2'; inp.style.color = '#991b1b'; }
}
function _showNiTipGr(inp, msg) {
    const span = document.createElement('span');
    span.className = 'ni-error-tip';
    span.textContent = msg;
    span.style.cssText = 'position:absolute;bottom:-15px;left:50%;transform:translateX(-50%);font-size:.6rem;color:#991b1b;white-space:nowrap;background:#fff8f8;padding:0 4px;border-radius:3px;z-index:10;border:1px solid #fca5a5;line-height:1.6;';
    if (getComputedStyle(inp.parentNode).position === 'static') inp.parentNode.style.position = 'relative';
    inp.parentNode.appendChild(span);
    setTimeout(() => span.remove(), 2500);
}

function colorCalcCell(cell, val) {
    const cls = val === null ? 'color-vacio'
        : val >= 80 ? 'color-excelente'
        : val >= 60 ? 'color-proceso'
        : 'color-insuf';
    cell.className = 'calc-cell ' + cls;
}

/* ── Cálculo de promedio ponderado (RA o componentes) ────────────────── */
function calcularPromedio(fila) {
    const inputs = fila.querySelectorAll('.nota-campo[data-componente]');
    if (ES_RA) {
        // Promedio ponderado: suma(nota × peso) / suma(pesos activos)
        let sumaPonderada = 0, pesoActivo = 0;
        inputs.forEach(inp => {
            const col = inp.dataset.componente;
            if (!RA_COLS.includes(col)) return;
            const v    = parseFloat(inp.value);
            const peso = RA_PESOS[col] ?? (100 / NUM_RA_TOTAL);
            if (!isNaN(v) && inp.value.trim() !== '') {
                sumaPonderada += v * peso;
                pesoActivo    += peso;
            }
        });
        return pesoActivo > 0 ? Math.round(sumaPonderada / pesoActivo * 100) / 100 : null;
    } else {
        let sw = 0, pw = 0;
        inputs.forEach(inp => {
            const comp = inp.dataset.componente;
            if (!(comp in PESOS)) return;
            const val = inp.value.trim();
            const peso = PESOS[comp] ?? 0;
            if (val !== '' && !isNaN(parseFloat(val)) && peso > 0) {
                sw += parseFloat(val) * peso; pw += peso;
            }
        });
        return pw > 0 ? Math.round(sw / pw * 100) / 100 : null;
    }
}

function resolverIndicador(nota) {
    if (nota === null) return { label: '—', cls: 'ind-vacio' };
    if (nota >= 90)   return { label: 'Excelente',   cls: 'ind-excelente' };
    if (nota >= 75)   return { label: 'Bueno',        cls: 'ind-bueno' };
    if (nota >= 60)   return { label: 'En proceso',   cls: 'ind-proceso' };
    return { label: 'Insuficiente', cls: 'ind-insuficiente' };
}

/* ── Actualizar fila ────────────────────────────────────────────────── */
function actualizarFila(input) {
    const fila        = input.closest('tr');
    const matriculaId = fila.dataset.matriculaId;
    const prom        = calcularPromedio(fila);
    const { label, cls } = resolverIndicador(prom);

    const promCell = document.getElementById('promedio-' + matriculaId);
    promCell.textContent = prom !== null ? prom.toFixed(1) : '—';
    colorCalcCell(promCell, prom);

    document.getElementById('indicador-' + matriculaId).textContent = label;
    document.getElementById('indicador-' + matriculaId).className   = 'badge-indicador ' + cls;

    // Recalcular completivo y extraordinario porque cambia el final
    calcularCompletivo(matriculaId);
    calcularExtraordinario(matriculaId);
    recalcularPromediosClase();
}

/* ── Completivo: 50% Final + 50% C.C ───────────────────────────────── */
function calcularCompletivo(matriculaId) {
    const final = parseFloat(document.getElementById('promedio-' + matriculaId).textContent);
    const ccInp = document.querySelector(`input[data-componente="nota_cc"][data-matricula="${matriculaId}"]`);
    const cc    = ccInp ? parseFloat(ccInp.value) : NaN;
    const cell  = document.getElementById('completivo-' + matriculaId);
    if (!isNaN(final) && !isNaN(cc) && ccInp?.value.trim() !== '') {
        const val = Math.round((0.5 * final + 0.5 * cc) * 100) / 100;
        cell.textContent = val.toFixed(1);
        colorCalcCell(cell, val);
    } else {
        cell.textContent = '—'; colorCalcCell(cell, null);
    }
}

/* ── Extraordinario: 30% Final + 70% C.E ───────────────────────────── */
function calcularExtraordinario(matriculaId) {
    const final = parseFloat(document.getElementById('promedio-' + matriculaId).textContent);
    const ceInp = document.querySelector(`input[data-componente="nota_ce"][data-matricula="${matriculaId}"]`);
    const ce    = ceInp ? parseFloat(ceInp.value) : NaN;
    const cell  = document.getElementById('extraordinario-' + matriculaId);
    if (!isNaN(final) && !isNaN(ce) && ceInp?.value.trim() !== '') {
        const val = Math.round((0.3 * final + 0.7 * ce) * 100) / 100;
        cell.textContent = val.toFixed(1);
        colorCalcCell(cell, val);
    } else {
        cell.textContent = '—'; colorCalcCell(cell, null);
    }
}

/* ── Asistencia: clases / total ─────────────────────────────────────── */
function calcularAsistencia(matriculaId) {
    const aInp = document.querySelector(`input[data-componente="asistencia_clases"][data-matricula="${matriculaId}"]`);
    const tInp = document.querySelector(`input[data-componente="asistencia_total"][data-matricula="${matriculaId}"]`);
    const cell = document.getElementById('pct-asist-' + matriculaId);
    const a    = parseFloat(aInp?.value);
    const t    = parseFloat(tInp?.value);
    if (!isNaN(a) && !isNaN(t) && t > 0) {
        const pct = Math.round(a / t * 10000) / 100;
        cell.textContent = pct.toFixed(1) + '%';
        if (pct >= 75) { cell.style.color = '#15803d'; cell.style.background = '#dcfce7'; }
        else if (pct >= 50) { cell.style.color = '#92400e'; cell.style.background = '#fef3c7'; }
        else { cell.style.color = '#991b1b'; cell.style.background = '#fee2e2'; }
        cell.className = 'asist-cell';
    } else {
        cell.textContent = '—'; cell.style.color = ''; cell.style.background = '';
        cell.className = 'asist-cell color-vacio';
    }
}

/* ── Promedios de clase por columna ─────────────────────────────────── */
function recalcularPromediosClase() {
    const cols = ES_RA ? RA_COLS : Object.keys(PESOS);
    cols.forEach(col => {
        const inputs = document.querySelectorAll(`.nota-campo[data-componente="${col}"]`);
        let s = 0, n = 0;
        inputs.forEach(inp => { const v = parseFloat(inp.value); if (!isNaN(v)) { s += v; n++; } });
        const val = n > 0 ? (s / n).toFixed(1) : '—';
        const idKey = ES_RA ? col : col;
        const h = document.getElementById('avg-' + idKey);
        if (h) h.textContent = val;
        const f = document.getElementById('foot-avg-' + idKey);
        if (f) f.textContent = val;
    });

    const cells = document.querySelectorAll('.calc-cell:not(.color-vacio):not([id^="completivo"]):not([id^="extraordinario"])');
    let s2 = 0, n2 = 0;
    document.querySelectorAll('[id^="promedio-"]').forEach(c => {
        const v = parseFloat(c.textContent); if (!isNaN(v)) { s2 += v; n2++; }
    });
    const genVal = n2 > 0 ? (s2 / n2).toFixed(1) : '—';
    const ag = document.getElementById('avg-general');
    if (ag) ag.textContent = genVal;
    const fg = document.getElementById('foot-avg-general');
    if (fg) fg.textContent = genVal;
}

/* ── Navegación con teclado (mejorada) ─────────────────────────────── */
document.addEventListener('keydown', function(e) {
    const active = document.activeElement;
    if (!active || !active.classList.contains('nota-campo')) return;

    const col  = active.dataset.componente;
    const fila = parseInt(active.dataset.fila ?? 0);
    let next   = null;

    if (e.key === 'Enter' || e.key === 'ArrowDown') {
        e.preventDefault();
        next = document.querySelector(`.nota-campo[data-componente="${col}"][data-fila="${fila + 1}"]`);
    } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        next = document.querySelector(`.nota-campo[data-componente="${col}"][data-fila="${fila - 1}"]`);
    } else if (e.key === 'Escape') {
        e.preventDefault();
        const orig = active.getAttribute('data-original') ?? '';
        active.value = orig;
        colorInput && colorInput(active);
        active.blur();
        return;
    } else if (e.key === 'Tab') {
        return; // allow default tab
    }

    if (next) {
        next.focus();
        requestAnimationFrame(() => { try { next.select(); } catch(ex) {} });
    }
});

// Guardar valor original al enfocar
document.addEventListener('focusin', function(e) {
    if (e.target?.classList.contains('nota-campo')) {
        e.target.setAttribute('data-original', e.target.value ?? '');
    }
});

/* ── Inicialización ─────────────────────────────────────────────────── */
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.nota-campo').forEach(inp => colorInput(inp));
    document.querySelectorAll('.crit-inp-adm').forEach(inp => colorInput(inp));
    document.querySelectorAll('.fila-estudiante').forEach(fila => {
        const firstInput = fila.querySelector('.nota-campo');
        if (firstInput) actualizarFila(firstInput);
        const mid = fila.dataset.matriculaId;
        calcularCompletivo(mid);
        calcularExtraordinario(mid);
        calcularAsistencia(mid);
        // Init recovery CF displays for each RA
        if (ES_RA) {
            fila.querySelectorAll('.rec-adm-inp[data-ra]').forEach(inp => recalcRecAdm(inp));
            // Show recovery section if RA score < 70
            fila.querySelectorAll('[id^="ra-val-"]').forEach(raInp => {
                const parts = raInp.id.split('-');
                const raN   = parts[parts.length - 1];
                const matId = raInp.dataset.matricula;
                const v     = parseFloat(raInp.value);
                if (!isNaN(v) && raInp.value !== '') {
                    const recDiv = document.getElementById(`rec-adm-${matId}-${raN}`);
                    if (recDiv) recDiv.classList.toggle('show', v < 70);
                }
            });
        }
    });
    recalcularPromediosClase();

    // Inicializar total del modal de pesos al abrirlo
    const modalPesos = document.getElementById('modal-ra-pesos');
    if (modalPesos) {
        modalPesos.addEventListener('shown.bs.modal', () => actualizarTotalPesos());
    }

    // Inicializar tooltips
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
        new bootstrap.Tooltip(el, { trigger: 'hover focus' });
    });
    // Marcar pendiente al editar
    document.querySelectorAll('.nota-campo').forEach(inp => {
        inp.addEventListener('input', marcarPendienteGr);
    });
});

/* ── Toast ──────────────────────────────────────────────────────────── */
function mostrarToast(msg, tipo = 'success') {
    const toastEl = document.getElementById('toast-guardado');
    const msgEl   = document.getElementById('toast-msg');
    msgEl.textContent = msg;
    toastEl.className = `toast align-items-center border-0 text-white bg-${tipo === 'success' ? 'success' : 'danger'}`;
    new bootstrap.Toast(toastEl, { delay: 3500 }).show();
}

/* ── Build request body ─────────────────────────────────────────────── */
function buildBody(matriculaId, fila) {
    const body = new URLSearchParams();
    body.append('_token', CSRF_TOKEN);
    body.append('asignacion_id', ASIGNACION_ID);
    body.append('periodo_id',    PERIODO_ID);
    // Main RA/component inputs
    fila.querySelectorAll('.nota-campo[data-componente]').forEach(inp => {
        const comp = inp.dataset.componente;
        // Skip criterios/recuperaciones fields (handled separately)
        if (comp && !comp.startsWith('criterios') && !comp.startsWith('recuperaciones')) {
            body.append(`notas[${matriculaId}][${comp}]`, inp.value.trim());
        }
    });
    // Criteria inputs per RA
    fila.querySelectorAll('.crit-inp-adm').forEach(inp => {
        const raN = inp.dataset.ra;
        const ck  = inp.dataset.crit;
        if (raN && ck) {
            body.append(`criterios[${matriculaId}][ra${raN}][${ck}]`, inp.value.trim());
        }
    });
    // Recovery inputs per RA
    fila.querySelectorAll('.rec-adm-inp').forEach(inp => {
        const raN  = inp.dataset.ra;
        const rk   = inp.dataset.componente?.split('[')[2]?.replace(']','');
        const rkAlt = inp.getAttribute('data-rk') || rk;
        if (raN && rkAlt) {
            body.append(`recuperaciones[${matriculaId}][ra${raN}][${rkAlt}]`, inp.value.trim());
        }
    });
    const obs = fila.querySelector('.obs-campo');
    if (obs) body.append(`notas[${matriculaId}][observaciones]`, obs.value);
    return body;
}

/* ── Auto-guardar celda ─────────────────────────────────────────────── */
function autoGuardarCelda(input) {
    const fila        = input.closest('tr');
    const matriculaId = input.dataset.matricula;
    setSaveStatusGr('saving', 'Guardando…');
    fetch(ROUTE_GUARDAR, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json' },
        body: buildBody(matriculaId, fila),
    }).then(r => r.json()).then(data => {
        if (data.success) {
            input.style.transition = 'box-shadow .4s';
            input.style.boxShadow  = '0 0 0 2px #22c55e55';
            setTimeout(() => { input.style.boxShadow = ''; }, 800);
            setSaveStatusGr('saved', 'Guardado ' + new Date().toLocaleTimeString('es', {hour:'2-digit',minute:'2-digit'}));
            limpiarPendienteGr();
        } else {
            setSaveStatusGr('error', 'Error al guardar');
        }
    }).catch(() => { setSaveStatusGr('error', 'Error al guardar'); });
}

function autoGuardarObs(textarea) {
    const fila = textarea.closest('tr');
    fetch(ROUTE_GUARDAR, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json' },
        body: buildBody(textarea.dataset.matricula, fila),
    }).catch(() => {});
}

/* ── Ripple helper ───────────────────────────────────────────────────── */
function triggerRipple(btn) {
    if (!btn) return;
    btn.classList.remove('ripple-active');
    void btn.offsetWidth;
    btn.classList.add('ripple-active');
    setTimeout(() => btn.classList.remove('ripple-active'), 500);
}
/* ── Guardar todo ───────────────────────────────────────────────────── */
function guardarTodo() {
    const btn = document.getElementById('btn-guardar');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Guardando...';
    setSaveStatusGr('saving', 'Guardando…');

    const body = new URLSearchParams();
    body.append('_token', CSRF_TOKEN);
    body.append('asignacion_id', ASIGNACION_ID);
    body.append('periodo_id',    PERIODO_ID);

    document.querySelectorAll('.fila-estudiante').forEach(fila => {
        const mid = fila.dataset.matriculaId;
        fila.querySelectorAll('.nota-campo[data-componente]').forEach(inp => {
            const comp = inp.dataset.componente;
            if (comp && !comp.startsWith('criterios') && !comp.startsWith('recuperaciones')) {
                body.append(`notas[${mid}][${comp}]`, inp.value.trim());
            }
        });
        // Criteria per RA
        fila.querySelectorAll('.crit-inp-adm').forEach(inp => {
            const raN = inp.dataset.ra; const ck = inp.dataset.crit;
            if (raN && ck) body.append(`criterios[${mid}][ra${raN}][${ck}]`, inp.value.trim());
        });
        // Recovery per RA
        fila.querySelectorAll('.rec-adm-inp').forEach(inp => {
            const raN = inp.dataset.ra; const rk = inp.dataset.rk;
            if (raN && rk) body.append(`recuperaciones[${mid}][ra${raN}][${rk}]`, inp.value.trim());
        });
        const obs = fila.querySelector('.obs-campo');
        if (obs) body.append(`notas[${mid}][observaciones]`, obs.value);
    });

    fetch(ROUTE_GUARDAR, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json' },
        body: body,
    })
    .then(r => r.json())
    .then(data => {
        mostrarToast(data.success ? data.message : (data.message ?? 'Error al guardar.'), data.success ? 'success' : 'danger');
        if (data.success) {
            setSaveStatusGr('saved', 'Guardado ' + new Date().toLocaleTimeString('es', {hour:'2-digit',minute:'2-digit'}));
            limpiarPendienteGr();
            triggerRipple(btn);
        } else {
            setSaveStatusGr('error', 'Error al guardar');
        }
    })
    .catch(() => { mostrarToast('Error de conexión.', 'danger'); setSaveStatusGr('error', 'Error al guardar'); })
    .finally(() => { btn.disabled = false; btn.innerHTML = '<i class="bi bi-floppy me-2"></i>Guardar Todo'; });
}

/* ── Publicar ───────────────────────────────────────────────────────── */
/* ── Configurar pesos RA ────────────────────────────────────────────── */
function actualizarTotalPesos() {
    let total = 0;
    document.querySelectorAll('.ra-peso-input').forEach(inp => {
        const v = parseFloat(inp.value);
        if (!isNaN(v)) total += v;
    });
    total = Math.round(total * 100) / 100;
    const display = document.getElementById('total-pesos-display');
    const btnSave = document.getElementById('btn-guardar-pesos');
    const ok = Math.abs(total - 100) <= 0.5;
    display.textContent = total.toFixed(1) + '%';
    display.style.color = ok ? '#15803d' : '#991b1b';
    if (btnSave) btnSave.disabled = !ok;
}

function distribuirEquitativamente() {
    const inputs = document.querySelectorAll('.ra-peso-input');
    const pesoIgual = Math.round(100 / inputs.length * 100) / 100;
    let acum = 0;
    inputs.forEach((inp, idx) => {
        if (idx < inputs.length - 1) {
            inp.value = pesoIgual;
            acum += pesoIgual;
        } else {
            // Último RA absorbe el residuo para sumar exactamente 100
            inp.value = Math.round((100 - acum) * 100) / 100;
        }
    });
    actualizarTotalPesos();
}

function guardarRaPesos() {
    const btn = document.getElementById('btn-guardar-pesos');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Guardando...';

    const body = new URLSearchParams();
    body.append('_token', CSRF_TOKEN);
    body.append('asignacion_id', ASIGNACION_ID);
    document.querySelectorAll('.ra-peso-input').forEach(inp => {
        body.append(`pesos[${inp.dataset.raId}]`, inp.value);
    });

    fetch(ROUTE_RA_PESOS, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json' },
        body: body,
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            // Update RA_PESOS in memory so recalculation uses new weights
            if (data.pesos_js) RA_PESOS = data.pesos_js;

            // Refresh header labels
            document.querySelectorAll('.ra-peso-input').forEach(inp => {
                const num    = inp.dataset.raNumero;
                const header = document.querySelector(`#th-ra-${num}`);
                if (header) header.textContent = inp.value + '%';
            });

            // Recalculate all rows with new weights
            document.querySelectorAll('.fila-estudiante').forEach(fila => {
                const firstInput = fila.querySelector('.nota-campo');
                if (firstInput) actualizarFila(firstInput);
            });

            bootstrap.Modal.getInstance(document.getElementById('modal-ra-pesos'))?.hide();
            mostrarToast(data.message, 'success');
        } else {
            mostrarToast(data.message, 'danger');
        }
    })
    .catch(() => mostrarToast('Error de conexión.', 'danger'))
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-check-lg me-1"></i>Guardar pesos';
    });
}

function publicarCalificaciones() {
    const btn = document.getElementById('btn-publicar');
    btn.disabled = true;
    fetch(ROUTE_PUBLICAR, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json' },
        body: new URLSearchParams({ _token: CSRF_TOKEN, asignacion_id: ASIGNACION_ID, periodo_id: PERIODO_ID }),
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            document.getElementById('btn-publicar-text').textContent = data.publicado ? 'Publicado' : 'Publicar';
            btn.className = data.publicado ? 'btn btn-success px-3' : 'btn btn-outline-light px-3';
            mostrarToast(data.message, 'success');
        } else {
            mostrarToast(data.message ?? 'Error al publicar.', 'danger');
        }
    })
    .catch(() => mostrarToast('Error de conexión.', 'danger'))
    .finally(() => { btn.disabled = false; });
}
</script>
@endpush
