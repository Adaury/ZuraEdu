@extends('layouts.admin')
@section('page-title', 'Registro — ' . $grupo->grado->nombre . ' ' . $grupo->seccion->nombre)

@push('styles')
<style>
/* ── Estructura base ─────────────────────────────────────────────── */
:root { --cell-w:54px; --cell-h:32px; }
.registro-wrap { overflow-x:auto; -webkit-overflow-scrolling:touch; border-radius:12px;
    border:1.5px solid #d1d5db; background:#fff; }
.tbl-registro { border-collapse:collapse; min-width:100%; font-size:.72rem; }
.tbl-registro th, .tbl-registro td { border:1px solid #e5e7eb; white-space:nowrap; }

/* ── Header capas ────────────────────────────────────────────────── */
.th-materia  { background:#1e3a6e; color:#fff; font-weight:800; font-size:.73rem;
    text-align:center; padding:.4rem .6rem; letter-spacing:.04em; }
.th-ce       { background:#2d5aa0; color:#fff; font-size:.68rem; font-weight:700;
    text-align:center; padding:.3rem .5rem; }
.th-il       { background:#e0e7ff; color:#1e3a6e; font-size:.65rem; font-weight:600;
    text-align:center; padding:.25rem .4rem; }
.th-periodo  { background:#f0f4ff; color:#374151; font-size:.64rem; font-weight:700;
    text-align:center; padding:.2rem; width:var(--cell-w); }
.th-prom     { background:#dcfce7; color:#065f46; font-size:.66rem; font-weight:700;
    text-align:center; padding:.25rem .4rem; }
.th-gen-prom { background:#111827; color:#fff; font-size:.7rem; font-weight:800;
    text-align:center; padding:.4rem .5rem; }

/* ── Celdas de datos ─────────────────────────────────────────────── */
.td-num    { background:#f9fafb; color:#374151; font-weight:700; text-align:center;
    padding:.3rem .4rem; min-width:32px; }
.td-nombre { background:#fff; font-weight:600; color:#111827; padding:.3rem .7rem;
    min-width:180px; position:sticky; left:0; z-index:2; border-right:2px solid #d1d5db; }
.td-valor  { text-align:center; padding:0; min-width:var(--cell-w); cursor:pointer; }
.td-prom   { text-align:center; font-weight:700; font-size:.75rem; padding:.25rem .4rem; }
.td-gen    { text-align:center; font-weight:800; font-size:.78rem; min-width:58px;
    padding:.25rem .4rem; }

/* ── Celda editable ─────────────────────────────────────────────── */
.cell-input { width:100%; height:var(--cell-h); border:none; text-align:center;
    font-size:.72rem; font-weight:700; background:transparent; outline:none;
    padding:0 .25rem; }
.cell-input:focus { background:#fffbeb; outline:2px solid #f59e0b; border-radius:3px; }
.td-valor:hover { background:#fef9ec !important; }

/* ── Sticky col nombre ──────────────────────────────────────────── */
.tbl-registro thead tr th:first-child,
.tbl-registro thead tr th:nth-child(2) { position:sticky; left:0; z-index:10; }
.tbl-registro thead tr th:nth-child(2) { left:32px; z-index:10; }

/* ── Escala cualitativa chips ───────────────────────────────────── */
.escala-chip { display:inline-block; border-radius:4px; padding:.1rem .35rem;
    font-size:.65rem; font-weight:700; }

/* ── Toolbar ────────────────────────────────────────────────────── */
.toolbar { display:flex; align-items:center; gap:.75rem; flex-wrap:wrap;
    background:#fff; border-radius:14px; border:1px solid #e5e7eb;
    padding:1rem 1.25rem; margin-bottom:1rem; }
.periodo-tab { border:1.5px solid #d1d5db; border-radius:8px; padding:.35rem .9rem;
    font-size:.8rem; font-weight:700; cursor:pointer; background:#fff; transition:.15s; }
.periodo-tab.active { background:var(--primary); color:#fff; border-color:var(--primary); }

/* ── Toast autosave ─────────────────────────────────────────────── */
.toast-save { position:fixed; bottom:1.5rem; right:1.5rem; background:#111827;
    color:#fff; border-radius:10px; padding:.6rem 1.25rem; font-size:.82rem;
    font-weight:600; z-index:9999; opacity:0; transform:translateY(8px);
    transition:all .25s; pointer-events:none; }
.toast-save.show { opacity:1; transform:translateY(0); }
.toast-save.error { background:#dc2626; }

/* ── Leyenda escala ─────────────────────────────────────────────── */
.leyenda { display:flex; gap:.5rem; flex-wrap:wrap; margin-bottom:.75rem; }
.leg-item { display:inline-flex; align-items:center; gap:.3rem;
    background:#f3f4f6; border-radius:6px; padding:.2rem .6rem; font-size:.72rem; }

[data-theme="dark"] .registro-wrap { background: #1e293b; border-color: #334155; }
[data-theme="dark"] .tbl-registro th, [data-theme="dark"] .tbl-registro td { border-color: #334155; }
[data-theme="dark"] .th-il { background: #0c1f3f; color: #93c5fd; }
[data-theme="dark"] .th-periodo { background: #162032; color: #94a3b8; }
[data-theme="dark"] .th-prom { background: #052e16; color: #4ade80; }
[data-theme="dark"] .td-num { background: #162032; color: #94a3b8; }
[data-theme="dark"] .td-nombre { background: #1e293b; color: #e2e8f0; border-right-color: #334155; }
[data-theme="dark"] .toolbar { background: #1e293b; border-color: #334155; }
[data-theme="dark"] .periodo-tab { background: #1e293b; border-color: #334155; color: #94a3b8; }
[data-theme="dark"] .leg-item { background: #334155; color: #cbd5e1; }
</style>
@endpush

@section('content')

{{-- ── Encabezado ──────────────────────────────────────────────────── --}}
<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
    <div>
        <nav aria-label="breadcrumb" style="font-size:.8rem;">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="{{ route('admin.registro.index') }}">Registro</a></li>
                <li class="breadcrumb-item active">{{ $grupo->grado->nombre }} — Sección {{ $grupo->seccion->nombre }}</li>
            </ol>
        </nav>
        <h1 style="font-size:1.35rem;font-weight:800;color:var(--primary);margin:0;">
            <i class="bi bi-journal-bookmark-fill me-2"></i>
            Registro Académico — {{ $grupo->grado->nombre }} {{ $grupo->seccion->nombre }}
        </h1>
        <p class="text-muted small mb-0">
            {{ $schoolYear->nombre }} &nbsp;·&nbsp;
            <span class="badge {{ $ciclo === 'primer_ciclo' ? 'text-bg-primary' : 'text-bg-purple' }}"
                  style="{{ $ciclo === 'segundo_ciclo' ? 'background:#7c3aed!important;' : '' }}">
                {{ $ciclo === 'primer_ciclo' ? 'Primer Ciclo' : 'Segundo Ciclo' }}
            </span>
        </p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.registro.exportarPdf', $grupo) }}"
           class="btn btn-outline-danger btn-sm" target="_blank">
            <i class="bi bi-file-earmark-pdf me-1"></i>Exportar PDF
        </a>
        <a href="{{ route('admin.registro.exportarExcel', $grupo) }}"
           class="btn btn-success btn-sm">
            <i class="bi bi-file-earmark-excel-fill me-1"></i>Excel
        </a>
        <button class="btn btn-success btn-sm" onclick="calcularPromociones({{ $grupo->id }})">
            <i class="bi bi-trophy me-1"></i>Calcular Promoción
        </button>
    </div>
</div>

{{-- ── Leyenda escala (solo primer ciclo) ────────────────────────────── --}}
@if($ciclo === 'primer_ciclo')
<div class="leyenda">
    <span class="leg-item">Escala de valoración:</span>
    <span class="leg-item"><span style="background:#fee2e2;border-radius:4px;padding:.1rem .4rem;font-weight:700;">1</span>&nbsp;Inicial</span>
    <span class="leg-item"><span style="background:#fef3c7;border-radius:4px;padding:.1rem .4rem;font-weight:700;">2</span>&nbsp;En proceso</span>
    <span class="leg-item"><span style="background:#d1fae5;border-radius:4px;padding:.1rem .4rem;font-weight:700;">3</span>&nbsp;Logrado</span>
    <span class="leg-item"><span style="background:#a7f3d0;border-radius:4px;padding:.1rem .4rem;font-weight:700;">4</span>&nbsp;Avanzado</span>
</div>
@endif

{{-- ── Toolbar períodos ────────────────────────────────────────────── --}}
<div class="toolbar">
    <span style="font-size:.8rem;font-weight:700;color:#374151;">Período activo:</span>
    @foreach($periodos as $p)
        <button class="periodo-tab {{ $periodoActivo?->id === $p->id ? 'active' : '' }}"
                onclick="location.href='{{ route('admin.registro.show', [$grupo, 'periodo' => $p->id]) }}'">
            {{ $p->nombre }}
            @if($p->cerrado)<i class="bi bi-lock-fill ms-1" style="font-size:.65rem;"></i>@endif
        </button>
    @endforeach
    <span class="ms-auto text-muted" style="font-size:.75rem;">
        <i class="bi bi-info-circle me-1"></i>Haz clic en una celda para editar
    </span>
</div>

{{-- ── TABLA REGISTRO MINERD ──────────────────────────────────────── --}}
<div class="registro-wrap">
<table class="tbl-registro" id="tablaRegistro">

{{-- ─── FILA 1: Materias ─── --}}
<thead>
<tr>
    <th class="th-gen-prom" rowspan="4" style="min-width:32px;">#</th>
    <th class="th-gen-prom" rowspan="4"
        style="min-width:180px;position:sticky;left:32px;z-index:11;text-align:left;padding-left:.75rem;">
        Estudiante
    </th>

    @foreach($asignaciones as $asig)
        @php
            $ces = $asig->asignatura->competenciasActivas ?? collect();
            // Calcular colspan total de la materia
            $colspan = 0;
            foreach($ces as $ce) {
                $ils = $ce->indicadoresActivos ?? collect();
                if($ils->isNotEmpty()) {
                    $colspan += $ils->count() * $periodos->count(); // períodos por IL
                    $colspan += 1; // prom IL no existe por IL, sino por CE
                } else {
                    $colspan += $periodos->count() + 1; // períodos + prom CE
                }
            }
            $colspan += 1; // prom materia
        @endphp
        <th class="th-materia" colspan="{{ max($colspan,1) }}">
            {{ $asig->asignatura->nombre }}
        </th>
    @endforeach
    <th class="th-gen-prom" rowspan="4">PROM<br>GRAL</th>
</tr>

{{-- ─── FILA 2: Competencias (CE) ─── --}}
<tr>
    @foreach($asignaciones as $asig)
        @php $ces = $asig->asignatura->competenciasActivas ?? collect(); @endphp
        @foreach($ces as $ce)
            @php
                $ils = $ce->indicadoresActivos ?? collect();
                $ceColspan = $ils->isNotEmpty()
                    ? $ils->count() * $periodos->count() + 1
                    : $periodos->count() + 1;
            @endphp
            <th class="th-ce" colspan="{{ $ceColspan }}">
                {{ $ce->codigo }}: {{ Str::limit($ce->nombre, 28) }}
            </th>
        @endforeach
        <th class="th-prom" rowspan="3" style="vertical-align:middle;">PROM<br>MAT.</th>
    @endforeach
</tr>

{{-- ─── FILA 3: Indicadores (IL) ─── --}}
<tr>
    @foreach($asignaciones as $asig)
        @php $ces = $asig->asignatura->competenciasActivas ?? collect(); @endphp
        @foreach($ces as $ce)
            @php $ils = $ce->indicadoresActivos ?? collect(); @endphp
            @if($ils->isNotEmpty())
                @foreach($ils as $il)
                    <th class="th-il" colspan="{{ $periodos->count() }}">
                        {{ $il->codigo }}
                        <span title="{{ $il->descripcion }}">
                            <i class="bi bi-info-circle" style="font-size:.6rem;"></i>
                        </span>
                    </th>
                @endforeach
                <th class="th-prom" rowspan="2" style="vertical-align:middle;font-size:.63rem;">PROM<br>CE</th>
            @else
                {{-- Sin ILs: eval directa por CE --}}
                <th class="th-il" colspan="{{ $periodos->count() }}">
                    {{ $ce->codigo }}
                </th>
                <th class="th-prom" rowspan="2" style="vertical-align:middle;font-size:.63rem;">PROM<br>CE</th>
            @endif
        @endforeach
    @endforeach
</tr>

{{-- ─── FILA 4: Períodos ─── --}}
<tr>
    @foreach($asignaciones as $asig)
        @php $ces = $asig->asignatura->competenciasActivas ?? collect(); @endphp
        @foreach($ces as $ce)
            @php $ils = $ce->indicadoresActivos ?? collect(); @endphp
            @php $grupos_iter = $ils->isNotEmpty() ? $ils : collect([$ce]); @endphp
            @foreach($grupos_iter as $ref)
                @foreach($periodos as $p)
                    <th class="th-periodo {{ $periodoActivo?->id === $p->id ? 'th-activo' : '' }}"
                        style="{{ $periodoActivo?->id === $p->id ? 'background:#fef3c7!important;' : '' }}">
                        P{{ $p->numero }}
                    </th>
                @endforeach
            @endforeach
        @endforeach
    @endforeach
</tr>
</thead>

{{-- ─── CUERPO: Estudiantes ─── --}}
<tbody>
@foreach($registro as $idx => $row)
    @php
        $m    = $row['matricula'];
        $pgen = $row['promedio_general'];
        $genColor = $pgen !== null
            ? \App\Services\RegistroAcademicoService::notaColor($pgen, $ciclo)
            : '#f9fafb';
    @endphp
    <tr>
        <td class="td-num">{{ $idx + 1 }}</td>
        <td class="td-nombre">
            <div style="font-size:.78rem;">
                {{ $m->estudiante?->apellidos ?? '—' }}, {{ $m->estudiante?->nombres ?? '' }}
            </div>
            <div style="font-size:.65rem;color:#2563eb;font-weight:700;font-family:monospace;">{{ $m->estudiante?->numero_matricula }}</div>
        </td>

        {{-- ── Materias ── --}}
        @foreach($row['materias'] as $matIdx => $matRow)
            @foreach($matRow['competencias'] as $ceRow)
                @if(count($ceRow['indicadores']) > 0)
                    {{-- Evaluación por IL --}}
                    @foreach($ceRow['indicadores'] as $ilRow)
                        @foreach($periodos as $p)
                            @php
                                $val   = $ilRow['periodos'][$p->id] ?? null;
                                $color = $val !== null
                                    ? \App\Services\RegistroAcademicoService::notaColor($val, $ciclo)
                                    : '#fff';
                                $esPeriodoActivo = $periodoActivo?->id === $p->id;
                                $esCerrado = $p->cerrado;
                                $tipo = 'indicador';
                                $refId = $ilRow['il']->id;
                                $asigId = $matRow['asignacion']->id;
                                $syId = $schoolYear->id;
                            @endphp
                            <td class="td-valor"
                                style="background:{{ $val !== null ? $color : '#fff' }};"
                                data-matricula="{{ $m->id }}"
                                data-asignacion="{{ $asigId }}"
                                data-periodo="{{ $p->id }}"
                                data-schoolyear="{{ $syId }}"
                                data-tipo="{{ $tipo }}"
                                data-ref="{{ $refId }}"
                                data-ciclo="{{ $ciclo }}"
                                onclick="{{ $esCerrado ? '' : 'activarCelda(this)' }}"
                                title="{{ $esCerrado ? 'Período cerrado' : 'Clic para editar' }}">
                                @if($ciclo === 'primer_ciclo' && $val !== null)
                                    <span class="escala-chip" style="background:{{ $color }};color:#111827;">{{ $val }}</span>
                                @else
                                    <span style="font-weight:700;color:{{ $val !== null ? '#111827' : '#d1d5db' }};">
                                        {{ $val ?? '—' }}
                                    </span>
                                @endif
                            </td>
                        @endforeach
                    @endforeach
                    {{-- Promedio CE --}}
                    @php
                        $pce = $ceRow['promedio'];
                        $pceC = $pce !== null ? \App\Services\RegistroAcademicoService::notaColor($pce, $ciclo) : '#f9fafb';
                    @endphp
                    <td class="td-prom" style="background:{{ $pceC }};">
                        {{ $pce !== null ? ($ciclo==='primer_ciclo' ? number_format($pce,1) : number_format($pce,1)) : '—' }}
                    </td>
                @else
                    {{-- Evaluación directa por CE --}}
                    @foreach($periodos as $p)
                        @php
                            $val = $ceRow['periodos'][$p->id] ?? null;
                            $color = $val !== null
                                ? \App\Services\RegistroAcademicoService::notaColor($val, $ciclo)
                                : '#fff';
                        @endphp
                        <td class="td-valor"
                            style="background:{{ $val !== null ? $color : '#fff' }};"
                            data-matricula="{{ $m->id }}"
                            data-asignacion="{{ $matRow['asignacion']->id }}"
                            data-periodo="{{ $p->id }}"
                            data-schoolyear="{{ $schoolYear->id }}"
                            data-tipo="competencia"
                            data-ref="{{ $ceRow['ce']->id }}"
                            data-ciclo="{{ $ciclo }}"
                            onclick="{{ $p->cerrado ? '' : 'activarCelda(this)' }}">
                            <span style="font-weight:700;color:{{ $val !== null ? '#111827' : '#d1d5db' }};">
                                {{ $val ?? '—' }}
                            </span>
                        </td>
                    @endforeach
                    @php
                        $pce = $ceRow['promedio'];
                        $pceC = $pce !== null ? \App\Services\RegistroAcademicoService::notaColor($pce, $ciclo) : '#f9fafb';
                    @endphp
                    <td class="td-prom" style="background:{{ $pceC }};">
                        {{ $pce !== null ? number_format($pce,1) : '—' }}
                    </td>
                @endif
            @endforeach

            {{-- Promedio materia --}}
            @php
                $pm  = $matRow['promedio'];
                $pmC = $pm !== null ? \App\Services\RegistroAcademicoService::notaColor($pm, $ciclo) : '#f9fafb';
            @endphp
            <td class="td-prom" style="background:{{ $pmC }};border-left:2px solid #9ca3af;">
                @if($pm !== null)
                    <strong>{{ number_format($pm,1) }}</strong>
                    @if($ciclo === 'segundo_ciclo')
                        <div style="font-size:.6rem;color:#6b7280;">
                            {{ \App\Services\RegistroAcademicoService::notaLetra($pm) }}
                        </div>
                    @endif
                @else
                    <span style="color:#d1d5db;">—</span>
                @endif
            </td>
        @endforeach

        {{-- Promedio general --}}
        <td class="td-gen" style="background:{{ $genColor }};border-left:2px solid #6b7280;">
            @if($pgen !== null)
                <strong>{{ number_format($pgen,1) }}</strong>
                @if($ciclo === 'segundo_ciclo')
                    <div style="font-size:.6rem;">{{ \App\Services\RegistroAcademicoService::notaLetra($pgen) }}</div>
                @endif
            @else
                <span style="color:#d1d5db;">—</span>
            @endif
        </td>
    </tr>
@endforeach

{{-- Fila de promedios del grupo --}}
<tr style="background:#f1f5f9;border-top:2px solid #374151;">
    <td colspan="2" class="td-nombre" style="font-weight:800;font-size:.78rem;color:#374151;">
        PROMEDIO DEL GRUPO
    </td>
    @foreach($asignaciones as $asig)
        @php
            $ces = $asig->asignatura->competenciasActivas ?? collect();
            $totalCols = 0;
            foreach($ces as $ce) {
                $ils = $ce->indicadoresActivos ?? collect();
                $totalCols += ($ils->isNotEmpty() ? $ils->count() : 1) * $periodos->count();
                $totalCols += 1; // prom CE
            }
        @endphp
        <td colspan="{{ $totalCols }}" style="background:#f8fafc;"></td>
        <td class="td-prom" style="background:#e2e8f0;font-weight:800;border-left:2px solid #9ca3af;">
            @php
                $promsMateria = collect($registro)->map(function($r) use ($asig) {
                    return collect($r['materias'])->first(fn($m) => $m['asignacion']->id === $asig->id);
                })->pluck('promedio')->filter();
            @endphp
            {{ $promsMateria->count() ? number_format($promsMateria->avg(),1) : '—' }}
        </td>
    @endforeach
    <td class="td-gen" style="background:#cbd5e1;font-weight:800;">
        @php
            $promsGen = collect($registro)->pluck('promedio_general')->filter();
        @endphp
        {{ $promsGen->count() ? number_format($promsGen->avg(),1) : '—' }}
    </td>
</tr>
</tbody>
</table>
</div>

{{-- ── Input flotante (edición inline) ──────────────────────────── --}}
<div id="cellEditor" style="display:none; position:fixed; z-index:500;">
    <input type="number" id="cellInput" min="0" max="100" step="0.5"
           style="width:70px;height:32px;border:2px solid #f59e0b;border-radius:6px;
                  text-align:center;font-weight:700;font-size:.85rem;box-shadow:0 4px 12px rgba(0,0,0,.15);"
           onblur="guardarCelda()"
           onkeydown="if(event.key==='Enter') guardarCelda(); if(event.key==='Escape') cerrarEditor();">
</div>

{{-- ── Toast ─────────────────────────────────────────────────────── --}}
<div id="toastSave" class="toast-save">
    <i class="bi bi-check-circle-fill me-1"></i><span id="toastMsg">Guardado</span>
</div>

{{-- ── Modal promociones ─────────────────────────────────────────── --}}
<div class="modal fade" id="modalPromocion" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content rounded-4">
            <div class="modal-header">
                <h5 class="modal-title fw-800"><i class="bi bi-trophy me-2 text-warning"></i>Resultado de Promoción</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="modalPromocionBody">
                <div class="text-center py-4"><div class="spinner-border text-primary"></div></div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// ── Estado ──────────────────────────────────────────────────────────
let celdaActiva  = null;
let timerSave    = null;
const csrfToken  = '{{ csrf_token() }}';
const guardarUrl = '{{ route('admin.registro.guardar') }}';

// ── Activar edición de celda ────────────────────────────────────────
function activarCelda(td) {
    cerrarEditor();
    celdaActiva = td;

    const rect  = td.getBoundingClientRect();
    const input = document.getElementById('cellInput');
    const editor= document.getElementById('cellEditor');

    // Configurar tipo de input según ciclo
    const ciclo = td.dataset.ciclo;
    if (ciclo === 'primer_ciclo') {
        input.min = 1; input.max = 4; input.step = 1;
        input.placeholder = '1–4';
    } else {
        input.min = 0; input.max = 100; input.step = 0.5;
        input.placeholder = '0–100';
    }

    // Valor actual
    const span = td.querySelector('span');
    const valActual = span ? span.textContent.trim() : '';
    input.value = (valActual === '—' || valActual === '') ? '' : valActual;

    // Posicionar
    editor.style.display = 'block';
    editor.style.top  = (rect.top  + window.scrollY + rect.height/2 - 16) + 'px';
    editor.style.left = (rect.left + window.scrollX + rect.width/2  - 35) + 'px';

    input.focus();
    input.select();
}

function cerrarEditor() {
    document.getElementById('cellEditor').style.display = 'none';
    celdaActiva = null;
}

// ── Guardar celda vía AJAX ──────────────────────────────────────────
async function guardarCelda() {
    if (!celdaActiva) return;
    const td    = celdaActiva;
    const input = document.getElementById('cellInput');
    const valor = input.value.trim();

    cerrarEditor();

    const body = {
        matricula_id  : parseInt(td.dataset.matricula),
        asignacion_id : parseInt(td.dataset.asignacion),
        periodo_id    : parseInt(td.dataset.periodo),
        school_year_id: parseInt(td.dataset.schoolyear),
        tipo          : td.dataset.tipo,
        referencia_id : parseInt(td.dataset.ref),
        valor         : valor !== '' ? parseFloat(valor) : null,
        _token        : csrfToken,
    };

    // Validación cliente
    const ciclo = td.dataset.ciclo;
    if (valor !== '' && ciclo === 'primer_ciclo') {
        const v = parseInt(valor);
        if (v < 1 || v > 4 || isNaN(v)) { mostrarToast('Valor debe ser 1, 2, 3 ó 4', true); return; }
    }
    if (valor !== '' && ciclo === 'segundo_ciclo') {
        const v = parseFloat(valor);
        if (v < 0 || v > 100 || isNaN(v)) { mostrarToast('Valor debe ser entre 0 y 100', true); return; }
    }

    try {
        const res  = await fetch(guardarUrl, {
            method : 'POST',
            headers: {'Content-Type':'application/json','X-CSRF-TOKEN':csrfToken,'Accept':'application/json'},
            body   : JSON.stringify(body),
        });
        const json = await res.json();
        if (!json.ok) throw new Error(json.message ?? 'Error');

        // Actualizar celda visualmente
        actualizarCeldaUI(td, valor, ciclo);
        mostrarToast('✓ Guardado');
    } catch(e) {
        mostrarToast('Error al guardar: ' + e.message, true);
    }
}

function actualizarCeldaUI(td, valor, ciclo) {
    const span = td.querySelector('span');
    if (!span) return;

    if (valor === '' || valor === null) {
        td.style.background = '#fff';
        span.textContent = '—';
        span.style.color = '#d1d5db';
        return;
    }

    const v = parseFloat(valor);
    let bg = '#fff';

    if (ciclo === 'primer_ciclo') {
        const colores = {1:'#fee2e2',2:'#fef3c7',3:'#d1fae5',4:'#a7f3d0'};
        bg = colores[Math.round(v)] || '#fff';
        span.textContent = Math.round(v);
        span.className = 'escala-chip';
        span.style.background = bg;
        span.style.color = '#111827';
    } else {
        bg = v >= 90 ? '#d1fae5' : v >= 65 ? '#dcfce7' : v >= 50 ? '#fef3c7' : '#fee2e2';
        span.textContent = v % 1 === 0 ? v : v.toFixed(1);
        span.style.color = '#111827';
    }

    td.style.background = bg;
}

// ── Toast ──────────────────────────────────────────────────────────
function mostrarToast(msg, error = false) {
    const t = document.getElementById('toastSave');
    document.getElementById('toastMsg').textContent = msg;
    t.classList.toggle('error', error);
    t.classList.add('show');
    clearTimeout(timerSave);
    timerSave = setTimeout(() => t.classList.remove('show'), 2200);
}

// ── Cerrar editor al clic fuera ────────────────────────────────────
document.addEventListener('click', e => {
    const editor = document.getElementById('cellEditor');
    if (celdaActiva && !editor.contains(e.target) && e.target !== celdaActiva) {
        guardarCelda();
    }
});

// ── Calcular promociones ───────────────────────────────────────────
async function calcularPromociones(grupoId) {
    const modal = new bootstrap.Modal(document.getElementById('modalPromocion'));
    document.getElementById('modalPromocionBody').innerHTML =
        '<div class="text-center py-4"><div class="spinner-border text-primary"></div><p class="mt-2 text-muted small">Calculando...</p></div>';
    modal.show();

    try {
        const res  = await fetch(`/admin/registro/${grupoId}/calcular-promociones`, {
            method:'POST', headers:{'X-CSRF-TOKEN':csrfToken,'Accept':'application/json','Content-Type':'application/json'}
        });
        const json = await res.json();

        if (!json.ok) throw new Error(json.message ?? 'Error');

        let html = '<div class="table-responsive"><table class="table table-sm mb-0">';
        html += '<thead><tr><th>Estudiante</th><th>Promedio</th><th>Estado</th></tr></thead><tbody>';

        json.resultados.forEach(r => {
            const color = r.estado === 'Promovido' ? 'success' : r.estado === 'Condicionado' ? 'warning' : 'danger';
            html += `<tr><td>${r.estudiante}</td>
                     <td class="text-center fw-bold">${r.promedio ?? '—'}</td>
                     <td><span class="badge text-bg-${color}">${r.estado}</span></td></tr>`;
        });

        html += '</tbody></table></div>';
        document.getElementById('modalPromocionBody').innerHTML = html;

    } catch(e) {
        document.getElementById('modalPromocionBody').innerHTML =
            `<div class="alert alert-danger mb-0">Error: ${e.message}</div>`;
    }
}

// ── Tooltips en ILs ────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[title]').forEach(el => {
        new bootstrap.Tooltip(el, {trigger:'hover', placement:'top'});
    });
});
</script>
@endpush
