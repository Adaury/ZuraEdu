@extends('layouts.admin')
@section('page-title', 'Planilla Académica')

@push('styles')
<style>
/* ── Wrapper ─────────────────────────────────────────────────────────── */
.planilla-outer {
    width:100%; overflow-x:auto; border-radius:10px;
    box-shadow:0 2px 20px rgba(0,0,0,.12);
    max-height:80vh; overflow-y:auto;
}
#tbl-ac {
    border-collapse:collapse;
    font-size:.75rem;
    white-space:nowrap;
    min-width:2800px;
}
#tbl-ac th, #tbl-ac td {
    border:1px solid #d1d5db;
    vertical-align:middle;
    padding:0;
}
/* ── Sticky cols ─────────────────────────────────────────────────────── */
#tbl-ac .s-num {
    position:sticky;left:0;z-index:5;min-width:38px;width:38px;text-align:center;
}
#tbl-ac .s-nom {
    position:sticky;left:38px;z-index:5;min-width:180px;max-width:180px;
}
/* ── Header ──────────────────────────────────────────────────────────── */
#tbl-ac thead th {
    color:#fff;font-weight:700;font-size:.72rem;text-align:center;
    position:sticky;top:0;z-index:4;border-color:rgba(255,255,255,.25);
    padding:.5rem .3rem;letter-spacing:.01em;
}
#tbl-ac thead tr:nth-child(2) th {
    top:34px;font-size:.68rem;padding:.35rem .25rem;
}
#tbl-ac thead tr:nth-child(3) th {
    top:66px;font-size:.67rem;padding:.3rem .25rem;
    background:#374151 !important;
}
/* ── Section colors ──────────────────────────────────────────────────── */
.h-idx    {background:#0f1f3d!important;z-index:7!important;}
.h-c1     {background:#1d4ed8!important;}
.h-c2     {background:#065f46!important;}
.h-c3     {background:#6d28d9!important;}
.h-c4     {background:#9f1239!important;}
.h-pc     {background:#1e3a6e!important;}
.h-final  {background:#0f766e!important;}
.h-compl  {background:#5b21b6!important;}
.h-extra  {background:#92400e!important;}
.h-eval   {background:#374151!important;}
.h-sit    {background:#166534!important;}
.h-asist  {background:#064e3b!important;}
/* sub-header rows */
.sh-c1  {background:#2563eb!important;}
.sh-c2  {background:#047857!important;}
.sh-c3  {background:#7c3aed!important;}
.sh-c4  {background:#be123c!important;}
.sh-pc  {background:#2a4f96!important;}
.sh-compl {background:#6d28d9!important;}
.sh-extra {background:#b45309!important;}
.sh-eval  {background:#4b5563!important;}
.sh-sit   {background:#15803d!important;}
.sh-asist {background:#065f46!important;}
/* ── Inputs ──────────────────────────────────────────────────────────── */
.ni {
    width:54px;border:1px solid #d1d5db;border-radius:4px;
    padding:.22rem .2rem;font-size:.8rem;font-weight:600;
    text-align:center;background:#fff;display:block;margin:0 auto;
    transition:border-color .12s,box-shadow .12s,background .12s;
}
.ni:focus {outline:none;border-color:#6d28d9;box-shadow:0 0 0 2px rgba(109,40,217,.2);}
.ni-sm {width:46px;}
.ni-eval {border-color:#9ca3af;}
.ni-rp   {width:42px;font-size:.76rem;}
.ni-rec  {border-color:#f59e0b!important;background:#fffbeb!important;color:#92400e!important;}
.ni-rp:disabled {opacity:.25;cursor:not-allowed;}
.mcell-rp.locked {background:#f3f4f6;}
.mcell-rp {background:#fefce8;}
/* ── Calculated cells ────────────────────────────────────────────────── */
.cc {
    font-weight:800;text-align:center;padding:.3rem .25rem;
    font-size:.82rem;min-width:56px;
}
.c-ok   {color:#15803d;background:#dcfce7;}
.c-mid  {color:#854d0e;background:#fef9c3;}
.c-bad  {color:#991b1b;background:#fee2e2;}
.c-nil  {color:#9ca3af;}
/* Situación */
.sit-a  {font-weight:800;color:#15803d;background:#dcfce7;text-align:center;padding:.3rem;}
.sit-r  {font-weight:800;color:#991b1b;background:#fee2e2;text-align:center;padding:.3rem;}
.sit-nil{color:#d1d5db;text-align:center;padding:.3rem;}
/* ── Rows ────────────────────────────────────────────────────────────── */
.fila-est td {padding:.3rem .28rem;}
.fila-est:nth-child(even) td {background:#fafafa;}
.fila-est:nth-child(even) .s-num,
.fila-est:nth-child(even) .s-nom {background:#f3f4f6;}
.fila-est:hover td {background:#f0f4ff!important;color:#1e293b!important;}
.fila-est:hover .s-num,
.fila-est:hover .s-nom {background:#e8eefe!important;color:#1e293b!important;}
[data-theme="dark"] .fila-est:hover td {background:#1e3a5f!important;color:#f1f5f9!important;}
[data-theme="dark"] .fila-est:hover .s-num,
[data-theme="dark"] .fila-est:hover .s-nom {background:#1e3a5f!important;color:#f1f5f9!important;}
.nom-cell {font-weight:600;font-size:.81rem;color:#1e293b;padding-left:.7rem;
           overflow:hidden;text-overflow:ellipsis;}
.num-cell {font-size:.72rem;color:#9ca3af;font-weight:600;}
/* ── Prom footer row ─────────────────────────────────────────────────── */
.avg-row td {
    background:#e0e7ff!important;font-weight:700;color:#1e3a6e;
    font-size:.72rem;text-align:center;padding:.35rem .25rem;
    border-top:2px solid #a5b4fc;
}
/* ── Actions ─────────────────────────────────────────────────────────── */
.action-bar {
    background:#fff;border-radius:10px;
    box-shadow:0 2px 8px rgba(0,0,0,.06);
    padding:.7rem 1rem;margin-bottom:.8rem;
}
#toast-ac {position:fixed;bottom:1.5rem;right:1.5rem;z-index:9999;min-width:280px;}
/* Unsaved indicator */
.unsaved-dot {display:none;align-items:center;gap:.3rem;
    font-size:.74rem;color:#f97316;font-weight:600;}
/* Auto-calc cells (non-editable) show slightly different bg */
.cc-auto {cursor:default;user-select:none;}
/* Readonly completivo/extraordinario percentage cells */
.pct-cell {font-size:.74rem;color:#6b7280;font-weight:500;
           text-align:center;padding:.3rem .2rem;min-width:52px;}
</style>
@endpush

@section('content')

{{-- Breadcrumb --}}
<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb mb-0" style="font-size:.82rem;">
        <li class="breadcrumb-item">
            <a href="{{ route('admin.calificaciones.index') }}" class="text-decoration-none">Calificaciones</a>
        </li>
        <li class="breadcrumb-item active">Planilla Académica</li>
    </ol>
</nav>

{{-- Header card --}}
<div class="card border-0 shadow-sm mb-3" style="background:linear-gradient(135deg,#1d4ed8,#2563eb);">
    <div class="card-body py-3 px-4 text-white">
        <div class="row align-items-center">
            <div class="col-md-8">
                <div class="d-flex align-items-center gap-2 mb-1">
                    <h5 class="fw-bold mb-0">{{ $asignacion->asignatura->nombre }}</h5>
                    <span class="badge" style="background:rgba(255,255,255,.2);font-size:.72rem;">Área Académica</span>
                </div>
                <div class="d-flex flex-wrap gap-3" style="font-size:.81rem;opacity:.9;">
                    <span><i class="bi bi-people me-1"></i>{{ $asignacion->grupo->nombre_completo }}</span>
                    <span><i class="bi bi-person-badge me-1"></i>{{ optional($asignacion->docente)->nombre_completo ?? 'Sin docente' }}</span>
                    <span><i class="bi bi-calendar3 me-1"></i>{{ $schoolYear->nombre }}</span>
                    <span><i class="bi bi-people-fill me-1"></i>{{ $matriculas->count() }} estudiantes</span>
                </div>
            </div>
            <div class="col-md-4 text-md-end mt-2 mt-md-0">
                <div class="d-flex gap-2 justify-content-md-end">
                    <button id="btn-guardar" class="btn btn-light fw-bold px-4" onclick="guardarTodo()">
                        <i class="bi bi-floppy me-2"></i>Guardar Todo
                    </button>
                    @php $primeraReg = collect($registros)->first(); @endphp
                    <button id="btn-publicar" class="btn {{ $primeraReg?->publicado ? 'btn-success' : 'btn-outline-light' }} px-3"
                            onclick="publicarPlanilla()">
                        <i class="bi bi-eye me-1"></i>
                        <span id="txt-publicar">{{ $primeraReg?->publicado ? 'Publicado ✓' : 'Publicar' }}</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Action bar / legend --}}
<div class="action-bar d-flex flex-wrap gap-2 align-items-center justify-content-between">
    <div class="d-flex gap-1 flex-wrap" style="font-size:.73rem;">
        <span class="badge" style="background:#dbeafe;color:#1d4ed8;border:1px solid #bfdbfe;">■ Competencia 1</span>
        <span class="badge" style="background:#d1fae5;color:#065f46;border:1px solid #a7f3d0;">■ Competencia 2</span>
        <span class="badge" style="background:#ede9fe;color:#6d28d9;border:1px solid #ddd6fe;">■ Competencia 3</span>
        <span class="badge" style="background:#ffe4e6;color:#9f1239;border:1px solid #fecdd3;">■ Competencia 4</span>
        <span class="badge" style="background:#fef3c7;color:#92400e;">Completivo = 50%CF + 50%CEC</span>
        <span class="badge" style="background:#fee2e2;color:#991b1b;">Extraordinario = 30%CF + 70%CEX</span>
        <span class="badge" style="background:#dcfce7;color:#15803d;">A = Aprobado (≥60)</span>
        <span class="badge" style="background:#fee2e2;color:#991b1b;">R = Reprobado (&lt;60)</span>
    </div>
    <div class="d-flex align-items-center gap-2">
        <span class="unsaved-dot" id="unsaved-dot">
            <i class="bi bi-circle-fill" style="font-size:.4rem;"></i>Sin guardar
        </span>
        <span id="save-st" style="font-size:.74rem;color:#6b7280;">
            <i class="bi bi-check-circle-fill text-success me-1"></i>Listo
        </span>
        <span style="font-size:.73rem;color:#9ca3af;"><kbd>Enter</kbd>/<kbd>↑↓</kbd> navegar · Auto-guarda al salir</span>
    </div>
</div>

{{-- ══════════════════ TABLA EXCEL ══════════════════ --}}
<div class="planilla-outer">
<table id="tbl-ac">
<thead>

{{-- ── FILA 1: Grupos de bloques ──────────────────────────────────────── --}}
<tr>
    <th class="s-num h-idx" rowspan="2" style="z-index:8;">#</th>
    <th class="s-nom h-idx" rowspan="2" style="z-index:8;text-align:left;padding-left:.6rem;">Estudiante</th>

    @foreach([1,2,3,4] as $ci)
    @php
        $compNames = [1=>'Comunicativa',2=>'Pensamiento Lógico',3=>'Científica/Tecnológica',4=>'Ética y Ciudadana'];
        $compCls   = [1=>'h-c1',2=>'h-c2',3=>'h-c3',4=>'h-c4'];
        $compIcons = [1=>'bi-chat-quote-fill',2=>'bi-lightbulb-fill',3=>'bi-gear-fill',4=>'bi-heart-fill'];
    @endphp
    <th class="{{ $compCls[$ci] }}" colspan="9" style="font-size:.67rem;white-space:normal;line-height:1.3;padding:.28rem .35rem;">
        <i class="bi {{ $compIcons[$ci] }}" style="opacity:.85;margin-right:.2rem;"></i>C{{ $ci }} — {{ $compNames[$ci] }}
    </th>
    @endforeach

    <th class="h-final" rowspan="2" style="min-width:66px;font-size:.67rem;">NOTA<br>FINAL</th>
    <th class="h-compl" rowspan="2" style="min-width:52px;font-size:.65rem;">CC<br><span style="font-size:.54rem;font-weight:500;opacity:.85;">Completivo</span></th>
    <th class="h-extra" rowspan="2" style="min-width:52px;font-size:.65rem;">CE<br><span style="font-size:.54rem;font-weight:500;opacity:.85;">Extraordinario</span></th>
    <th class="h-compl" rowspan="2" style="min-width:52px;font-size:.67rem;font-weight:800;">CCF</th>
    <th class="h-eval"  colspan="2">EVALUACIÓN<br>ESPECIAL</th>
    <th class="h-sit"   colspan="2">SITUACIÓN</th>
    <th class="h-asist" colspan="5">ASISTENCIA</th>
</tr>

{{-- ── FILA 2: Sub-columnas ──────────────────────────────────────────── --}}
<tr>
    {{-- Competencias: P1 RP1 P2 RP2 P3 RP3 P4 RP4 ★PROM × 4 --}}
    @foreach([1,2,3,4] as $c)
    @php $shCls = "sh-c{$c}"; @endphp
    @foreach([1,2,3,4] as $per)
    <th class="{{ $shCls }}" style="min-width:46px;">P{{ $per }}</th>
    <th class="{{ $shCls }}" style="min-width:44px;font-size:.64rem;opacity:.9;">RP{{ $per }}</th>
    @endforeach
    <th class="{{ $shCls }}" style="min-width:52px;font-weight:800;">★ PROM</th>
    @endforeach

    {{-- Eval especial --}}
    <th class="sh-eval" style="min-width:54px;">C.F</th>
    <th class="sh-eval" style="min-width:54px;">C/E</th>

    {{-- Situación --}}
    <th class="sh-sit" style="min-width:40px;">A</th>
    <th class="sh-sit" style="min-width:40px;">R</th>

    {{-- Asistencia --}}
    <th class="sh-asist" style="min-width:50px;">P1</th>
    <th class="sh-asist" style="min-width:50px;">P2</th>
    <th class="sh-asist" style="min-width:50px;">P3</th>
    <th class="sh-asist" style="min-width:50px;">P4</th>
    <th class="sh-asist" style="min-width:62px;">% Asist.</th>
</tr>

</thead>

{{-- ── BODY ─────────────────────────────────────────────────────────── --}}
<tbody id="tbody-est">
@php $rowNum = 1; @endphp
@foreach($matriculas as $m)
@php $reg = $registros[$m->id] ?? null; $fi = $loop->index; @endphp
<tr class="fila-est" data-mid="{{ $m->id }}" data-fila="{{ $fi }}">

    <td class="s-num num-cell">{{ $rowNum++ }}</td>
    <td class="s-nom nom-cell" title="{{ $m->estudiante->nombre_completo }}">
        {{ $m->estudiante->nombre_completo }}
    </td>

    {{-- ── Competencias 1-4 × (P1 RP1 P2 RP2 P3 RP3 P4 RP4 ★PROM) ──── --}}
    @foreach([1,2,3,4] as $c)
    @php $promComp = $reg?->{"prom_comp{$c}"}; @endphp
    @foreach([1,2,3,4] as $p)
    @php
        $pVal     = $reg?->{"comp{$c}_p{$p}"};
        $rVal     = $reg?->{"comp{$c}_r{$p}"};
        $showR    = $pVal !== null && $pVal < 70;
        $faltante = $pVal !== null ? max(0, round(100 - $pVal, 1)) : 100;
    @endphp
    <td>
        <input type="number" class="ni ni-comp"
               data-campo="comp{{ $c }}_p{{ $p }}" data-comp="{{ $c }}" data-per="{{ $p }}"
               data-mid="{{ $m->id }}" data-fila="{{ $fi }}"
               min="0" max="100" step="0.5"
               value="{{ $pVal !== null ? rtrim(rtrim(number_format($pVal,1,'.',''),'0'),'.') : '' }}"
               placeholder="—"
               oninput="colorNi(this);recalcComp({{ $m->id }},{{ $c }})"
               onblur="autoGuardar(this)">
    </td>
    <td class="mcell-rp {{ !$showR ? 'locked' : '' }}" id="rcell-ac-c{{ $c }}-p{{ $p }}-m{{ $m->id }}"
        style="padding:.12rem .08rem;">
        <input type="number" class="ni ni-rp {{ $showR ? 'ni-rec' : '' }}"
               data-campo="comp{{ $c }}_r{{ $p }}" data-comp="{{ $c }}" data-per="{{ $p }}"
               data-mid="{{ $m->id }}" data-fila="{{ $fi }}"
               min="0" max="{{ $faltante }}" step="0.5"
               value="{{ $rVal !== null ? rtrim(rtrim(number_format($rVal,1,'.',''),'0'),'.') : '' }}"
               placeholder="—"
               {{ !$showR ? 'disabled' : '' }}
               oninput="colorNi(this);recalcComp({{ $m->id }},{{ $c }})"
               onblur="autoGuardar(this)">
    </td>
    @endforeach
    {{-- ★ PROM competencia (auto) --}}
    <td class="cc c-nil cc-auto" id="pc{{ $c }}-{{ $m->id }}"
        style="background:{{ ['',  '#1d4ed820','#06564620','#6d28d920','#9f123920'][$c] }};">
        {{ $promComp !== null ? rtrim(rtrim(number_format($promComp,1,'.',''),'0'),'.') : '—' }}
    </td>
    @endforeach

    {{-- ── Nota Final del Área (auto) ─────────────────────────────────── --}}
    @php
        $nfReg      = $reg?->nota_final;
        $ncReg      = $reg?->nota_cc;
        $neReg      = $reg?->nota_ce;
        $ncvReg     = $reg?->nota_completiva;
        $nevReg     = $reg?->nota_extraordinaria;
        $showCC     = $nfReg !== null && $nfReg < 70;
        $showCE     = $ncvReg !== null && $ncvReg < 70;
        $ccfDisplay = $nevReg ?? $ncvReg ?? null;
    @endphp
    <td class="cc c-nil cc-auto" id="cf-{{ $m->id }}" style="font-size:.88rem;">
        {{ $nfReg !== null ? rtrim(rtrim(number_format($nfReg,1,'.',''),'0'),'.') : '—' }}
    </td>

    {{-- ── CC Completivo (editable si NF < 70) ───────────────────────── --}}
    <td class="mcell-rp {{ !$showCC ? 'locked' : '' }}" id="cc-cell-ac-m{{ $m->id }}" style="padding:.15rem .1rem;">
        <input type="number" class="ni ni-sm {{ $showCC ? 'ni-rec' : '' }}"
               data-campo="nota_cc" data-mid="{{ $m->id }}" data-fila="{{ $fi }}"
               min="0" max="100" step="0.5"
               value="{{ $ncReg !== null ? rtrim(rtrim(number_format($ncReg,1,'.',''),'0'),'.') : '' }}"
               placeholder="—"
               {{ !$showCC ? 'disabled' : '' }}
               oninput="colorNi(this);recalcCompletivo({{ $m->id }})"
               onblur="autoGuardar(this)">
    </td>

    {{-- ── CE Extraordinario (editable si CCF < 70) ──────────────────── --}}
    <td class="mcell-rp {{ !$showCE ? 'locked' : '' }}" id="ce-cell-ac-m{{ $m->id }}" style="padding:.15rem .1rem;">
        <input type="number" class="ni ni-sm {{ $showCE ? 'ni-rec' : '' }}"
               data-campo="nota_ce" data-mid="{{ $m->id }}" data-fila="{{ $fi }}"
               min="0" max="100" step="0.5"
               value="{{ $neReg !== null ? rtrim(rtrim(number_format($neReg,1,'.',''),'0'),'.') : '' }}"
               placeholder="—"
               {{ !$showCE ? 'disabled' : '' }}
               oninput="colorNi(this);recalcExtraordinario({{ $m->id }})"
               onblur="autoGuardar(this)">
    </td>

    {{-- ── CCF (Calificación Final Completiva — auto) ─────────────────── --}}
    <td class="cc cc-auto" id="ccf-{{ $m->id }}"
        style="{{ $ccfDisplay !== null ? ($ccfDisplay >= 70 ? 'color:#15803d;background:#dcfce7;' : 'color:#991b1b;background:#fee2e2;') : 'color:#9ca3af;' }};font-weight:800;">
        {{ $ccfDisplay !== null ? rtrim(rtrim(number_format($ccfDisplay,1,'.',''),'0'),'.') : '—' }}
    </td>

    {{-- ── Eval Especial: C.F ──────────────────────────────────────────── --}}
    <td>
        <input type="number" class="ni ni-sm ni-eval"
               data-campo="eval_cf" data-mid="{{ $m->id }}" data-fila="{{ $fi }}"
               min="0" max="100" step="0.5"
               value="{{ $reg?->eval_cf ?? '' }}" placeholder="—"
               oninput="colorNi(this)" onblur="autoGuardar(this)">
    </td>

    {{-- ── Eval Especial: C/E ──────────────────────────────────────────── --}}
    <td>
        <input type="number" class="ni ni-sm ni-eval"
               data-campo="eval_ce" data-mid="{{ $m->id }}" data-fila="{{ $fi }}"
               min="0" max="100" step="0.5"
               value="{{ $reg?->eval_ce ?? '' }}" placeholder="—"
               oninput="colorNi(this)" onblur="autoGuardar(this)">
    </td>

    {{-- ── Situación: A ─────────────────────────────────────────────────── --}}
    <td class="sit-nil cc-auto" id="sit-a-{{ $m->id }}">—</td>

    {{-- ── Situación: R ─────────────────────────────────────────────────── --}}
    <td class="sit-nil cc-auto" id="sit-r-{{ $m->id }}">—</td>

    {{-- ── Asistencia P1-P4 ────────────────────────────────────────────── --}}
    @foreach([1,2,3,4] as $p)
    <td>
        <input type="number" class="ni ni-sm"
               data-campo="asist_p{{ $p }}" data-mid="{{ $m->id }}" data-fila="{{ $fi }}"
               min="0" max="999" step="1"
               value="{{ $reg?->{'asist_p'.$p} ?? '' }}" placeholder="—"
               oninput="recalcAsistencia({{ $m->id }})"
               onblur="autoGuardar(this)">
    </td>
    @endforeach

    {{-- ── % Asistencia ─────────────────────────────────────────────────── --}}
    <td class="cc c-nil cc-auto" id="pct-{{ $m->id }}">—</td>

</tr>
@endforeach
</tbody>

{{-- ── FOOTER: promedios de clase ─────────────────────────────────────── --}}
<tfoot>
<tr class="avg-row">
    <td class="s-num" style="background:#c7d2fe!important;"><i class="bi bi-calculator"></i></td>
    <td class="s-nom" style="background:#c7d2fe!important;padding-left:.6rem;font-size:.74rem;">
        <i class="bi bi-bar-chart-fill me-1"></i>Prom. clase
    </td>
    @foreach([1,2,3,4] as $c)
        @foreach([1,2,3,4] as $p)
        <td id="avg-c{{$c}}-p{{$p}}">—</td>
        <td style="background:#f9fafb!important;"></td>{{-- RP col --}}
        @endforeach
        <td id="avg-pc{{$c}}" style="background:#ddd6fe!important;color:#4c1d95!important;font-weight:800;">—</td>
    @endforeach
    <td id="avg-cf" style="background:#ccfbf1!important;color:#0f766e!important;font-weight:800;">—</td>
    <td colspan="2" style="background:#ede9fe!important;"></td>
    <td id="avg-ccf" style="background:#ede9fe!important;color:#5b21b6!important;font-weight:800;">—</td>
    <td colspan="2" style="background:#f3f4f6!important;"></td>
    <td colspan="2" style="background:#dcfce7!important;"></td>
    @foreach([1,2,3,4] as $p)
    <td id="avg-asist-p{{$p}}" style="background:#d1fae5!important;color:#065f46!important;">—</td>
    @endforeach
    <td id="avg-pct" style="background:#d1fae5!important;color:#065f46!important;font-weight:800;">—</td>
</tr>
</tfoot>
</table>
</div>

{{-- ══════════════════ INDICADORES DE LOGRO ══════════════════ --}}
<div class="mt-4 mb-2">
    <div class="d-flex align-items-center gap-2 mb-3">
        <div style="width:36px;height:36px;background:#1d4ed8;border-radius:8px;display:flex;align-items:center;justify-content:center;">
            <i class="bi bi-list-check text-white" style="font-size:1.1rem;"></i>
        </div>
        <div>
            <h6 class="fw-bold mb-0" style="color:#1d4ed8;">Indicadores de Logro</h6>
            <p class="text-muted mb-0" style="font-size:.78rem;">MINERD · Registro por período y competencia</p>
        </div>
    </div>

    @php
        $totalIndicadores = $indicadoresPorPeriodo->flatten()->count();
    @endphp

    @if($totalIndicadores === 0)
    <div class="alert alert-info d-flex align-items-center gap-3 border-0" style="background:#eff6ff;border-left:4px solid #3b82f6!important;border-radius:10px;">
        <i class="bi bi-info-circle-fill" style="color:#3b82f6;font-size:1.3rem;flex-shrink:0;"></i>
        <div>
            <div class="fw-semibold" style="color:#1e40af;">No hay indicadores configurados aún</div>
            <div style="font-size:.82rem;color:#1e40af;">
                Ve a <a href="{{ route('admin.indicadores.index') }}" class="fw-bold">Gestión → Indicadores de Logro</a>
                para agregar los indicadores de esta asignatura y grado.
            </div>
        </div>
    </div>
    @else
    <div class="row g-3">
        @foreach([1,2,3,4] as $p)
        @php
            $indsP      = $indicadoresPorPeriodo->get($p, collect());
            $periodo    = $periodos->firstWhere('numero', $p);
            $labelP     = $periodo ? $periodo->nombre : "Período {$p}";
            $colores    = ['','#1d4ed8','#047857','#7c3aed','#be123c'];
            $bgs        = ['','#dbeafe','#d1fae5','#ede9fe','#ffe4e6'];
            $colorP     = $colores[$p];
            $bgP        = $bgs[$p];
        @endphp
        <div class="col-md-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100" style="border-top:3px solid {{ $colorP }}!important;border-radius:10px;">
                <div class="card-header py-2 px-3 border-0" style="background:{{ $bgP }};border-radius:10px 10px 0 0;">
                    <div class="d-flex align-items-center justify-content-between">
                        <span class="fw-bold" style="font-size:.85rem;color:{{ $colorP }};">
                            <i class="bi bi-{{ $p == 1 ? '1-circle' : ($p == 2 ? '2-circle' : ($p == 3 ? '3-circle' : '4-circle')) }}-fill me-1"></i>
                            {{ $labelP }}
                        </span>
                        <span class="badge" style="background:{{ $colorP }};color:#fff;font-size:.7rem;">
                            {{ $indsP->count() }} indicador{{ $indsP->count() != 1 ? 'es' : '' }}
                        </span>
                    </div>
                </div>
                <div class="card-body p-0">
                    @if($indsP->isEmpty())
                        <p class="text-muted text-center py-3" style="font-size:.78rem;">Sin indicadores configurados</p>
                    @else
                        <ul class="list-unstyled mb-0 px-3 py-2" style="font-size:.77rem;max-height:160px;overflow-y:auto;">
                            @foreach($indsP as $ind)
                            <li class="d-flex align-items-start gap-2 py-1 border-bottom border-light">
                                <span style="min-width:18px;height:18px;border-radius:50%;background:{{ $colorP }};color:#fff;font-size:.6rem;display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:1px;">{{ $ind->orden }}</span>
                                <span style="color:#374151;line-height:1.3;">{{ $ind->descripcion }}</span>
                            </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
                @if($periodo)
                <div class="card-footer bg-white border-0 px-3 pb-3 pt-1">
                    <a href="{{ route('admin.indicadores.evaluaciones', ['asignacion_id' => $asignacion->id, 'periodo_id' => $periodo->id]) }}"
                       class="btn btn-sm w-100 fw-semibold"
                       style="background:{{ $colorP }};color:#fff;border-radius:8px;font-size:.78rem;">
                        <i class="bi bi-pencil-square me-1"></i>
                        {{ $indsP->isEmpty() ? 'Ver evaluaciones' : 'Registrar evaluaciones' }}
                    </a>
                </div>
                @endif
            </div>
        </div>
        @endforeach
    </div>
    @endif
</div>

{{-- Bottom actions --}}
<div class="d-flex justify-content-between align-items-center mt-3">
    <div class="d-flex gap-2 align-items-center">
        <a href="{{ route('admin.calificaciones.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>Volver
        </a>
        <a href="{{ route('admin.calificaciones.planilla.excel', ['asignacion_id' => $asignacion->id]) }}"
           class="btn btn-sm btn-outline-success">
            <i class="bi bi-file-earmark-spreadsheet me-1"></i>Excel
        </a>
        <a href="{{ route('admin.calificaciones.planilla.pdf', ['asignacion_id' => $asignacion->id]) }}"
           class="btn btn-sm btn-outline-danger" target="_blank">
            <i class="bi bi-file-earmark-pdf me-1"></i>PDF
        </a>
        <button onclick="window.print()" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-printer me-1"></i>Imprimir
        </button>
    </div>
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
<div id="toast-ac" class="toast align-items-center border-0" role="alert" aria-live="assertive">
    <div class="d-flex">
        <div class="toast-body fw-semibold" id="toast-msg">Guardando...</div>
        <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast"></button>
    </div>
</div>

<script>
    const ROUTE_GUARDAR_AC  = "{{ route('admin.calificaciones.guardar-academica') }}";
    const ROUTE_PUBLICAR_AC = "{{ route('admin.calificaciones.publicar-academica') }}";
    const ASIGNACION_ID_AC  = {{ $asignacion->id }};
    const SCHOOL_YEAR_ID_AC = {{ $schoolYear->id }};
    const CSRF_AC           = document.querySelector('meta[name="csrf-token"]').content;
</script>

@endsection

@push('scripts')
<script>
/* ══════════════════════════════════════════════════════════════
   AGENTE UI + CÁLCULO + INTERACCIÓN + SEGURIDAD
══════════════════════════════════════════════════════════════ */

/* ── Estado ──────────────────────────────────────────────────── */
let _hayPendientes = false;

function marcarPendiente() {
    if (!_hayPendientes) {
        _hayPendientes = true;
        document.getElementById('unsaved-dot').style.display = 'inline-flex';
    }
}
function limpiarPendiente() {
    _hayPendientes = false;
    document.getElementById('unsaved-dot').style.display = 'none';
}
window.addEventListener('beforeunload', e => {
    if (_hayPendientes) { e.preventDefault(); return e.returnValue = '¿Deseas salir? Hay cambios sin guardar.'; }
});

/* ── Colores de input (Agente Seguridad) ─────────────────────── */
function colorNi(inp) {
    inp.parentNode.querySelectorAll('.ni-tip').forEach(e => e.remove());
    inp.style.background = '';
    inp.style.color = '';
    const raw = inp.value.trim();
    if (!raw) return;
    const v = parseFloat(raw);
    if (isNaN(v)) { _tip(inp,'Solo números'); return; }
    if (v < 0 || v > 100) { _tip(inp, v<0?'Mín 0':'Máx 100'); return; }
    if      (v >= 80) { inp.style.background='#dcfce7'; inp.style.color='#15803d'; }
    else if (v >= 60) { inp.style.background='#fef9c3'; inp.style.color='#854d0e'; }
    else              { inp.style.background='#fee2e2'; inp.style.color='#991b1b'; }
    marcarPendiente();
}
function _tip(inp, msg) {
    const s = document.createElement('span');
    s.className = 'ni-tip';
    s.textContent = msg;
    s.style.cssText = 'position:absolute;bottom:-14px;left:50%;transform:translateX(-50%);font-size:.58rem;color:#991b1b;background:#fff8f8;padding:0 3px;border-radius:3px;z-index:10;border:1px solid #fca5a5;white-space:nowrap;line-height:1.6;';
    if (getComputedStyle(inp.parentNode).position==='static') inp.parentNode.style.position='relative';
    inp.parentNode.appendChild(s);
    setTimeout(()=>s.remove(), 2500);
    inp.style.background='#fee2e2'; inp.style.color='#991b1b';
}

/* ── Colorear celda calculada ────────────────────────────────── */
function colorCc(el, val) {
    if (val === null || isNaN(val)) {
        el.className = el.className.replace(/\bc-(ok|mid|bad)\b/g,'') + ' c-nil';
        el.classList.remove('c-ok','c-mid','c-bad');
        el.classList.add('c-nil');
    } else {
        el.classList.remove('c-nil','c-ok','c-mid','c-bad');
        if      (val >= 80) el.classList.add('c-ok');
        else if (val >= 60) el.classList.add('c-mid');
        else                el.classList.add('c-bad');
    }
}

/* ── CÁLCULO: Competencia → PC (incluye RP) ──────────────────── */
function recalcComp(mid, c) {
    const UMBRAL = 70;
    const cfs = [];
    for (let p=1;p<=4;p++) {
        const pInp = document.querySelector(`input[data-campo="comp${c}_p${p}"][data-mid="${mid}"]`);
        const rInp = document.querySelector(`input[data-campo="comp${c}_r${p}"][data-mid="${mid}"]`);
        const pv = pInp && pInp.value.trim() ? parseFloat(pInp.value) : null;
        const rv = rInp && rInp.value.trim() ? parseFloat(rInp.value) : null;

        // Gestionar estado del input RP
        const showR = pv !== null && pv < UMBRAL;
        if (rInp) {
            rInp.disabled = !showR;
            rInp.classList.toggle('ni-rec', showR);
            if (!showR) rInp.value = '';
        }
        const rcell = document.getElementById(`rcell-ac-c${c}-p${p}-m${mid}`);
        if (rcell) rcell.classList.toggle('locked', !showR);

        if (pv !== null) {
            const falt = Math.max(0, 100 - pv);
            let cf = pv;
            if (rv !== null && pv < UMBRAL) cf = Math.min(pv + Math.min(rv, falt), 100);
            cfs.push(Math.round(cf * 100) / 100);
        }
    }
    const pc = cfs.length ? Math.round(cfs.reduce((a,b)=>a+b,0)/cfs.length*100)/100 : null;
    const el = document.getElementById(`pc${c}-${mid}`);
    el.textContent = pc !== null ? pc.toFixed(1) : '—';
    colorCc(el, pc);
    recalcFinal(mid);
}

/* ── CÁLCULO: Final = avg(PC1..PC4) ──────────────────────────── */
function recalcFinal(mid) {
    const pcs = [];
    for (let c=1;c<=4;c++) {
        const el = document.getElementById(`pc${c}-${mid}`);
        const v = parseFloat(el?.textContent);
        if (!isNaN(v)) pcs.push(v);
    }
    const cf = pcs.length ? Math.round(pcs.reduce((a,b)=>a+b,0)/pcs.length*100)/100 : null;
    const el = document.getElementById(`cf-${mid}`);
    el.textContent = cf !== null ? cf.toFixed(1) : '—';
    colorCc(el, cf);

    // Situación final A / R
    const sitA = document.getElementById(`sit-a-${mid}`);
    const sitR = document.getElementById(`sit-r-${mid}`);
    if (cf === null) {
        sitA.textContent='—'; sitA.className='sit-nil cc-auto';
        sitR.textContent='—'; sitR.className='sit-nil cc-auto';
    } else if (cf >= 70) {
        sitA.textContent='✓'; sitA.className='sit-a cc-auto';
        sitR.textContent='—'; sitR.className='sit-nil cc-auto';
    } else {
        sitA.textContent='—'; sitA.className='sit-nil cc-auto';
        sitR.textContent='✗'; sitR.className='sit-r cc-auto';
    }

    // Habilitar / bloquear CC según NF
    const UMBRAL = 70;
    const showCC = cf !== null && cf < UMBRAL;
    const ccCell = document.getElementById(`cc-cell-ac-m${mid}`);
    const ccInp2 = ccCell?.querySelector('input');
    if (ccInp2) {
        ccInp2.disabled = !showCC;
        ccInp2.classList.toggle('ni-rec', showCC);
        if (!showCC) ccInp2.value = '';
    }
    if (ccCell) ccCell.classList.toggle('locked', !showCC);

    recalcCompletivo(mid);
    recalcExtraordinario(mid);
    recalcPromediosClase();
}

/* ── CÁLCULO: Completivo — CCF = 0.5×NF + 0.5×CC ────────────── */
function recalcCompletivo(mid) {
    const UMBRAL = 70;
    const cfEl  = document.getElementById(`cf-${mid}`);
    const cf    = parseFloat(cfEl?.textContent);
    const ccInp = document.querySelector(`input[data-campo="nota_cc"][data-mid="${mid}"]`);
    const cc    = ccInp && ccInp.value.trim() ? parseFloat(ccInp.value) : NaN;

    const ccfEl = document.getElementById(`ccf-${mid}`);
    let ccf = null;
    if (!isNaN(cf) && !isNaN(cc)) {
        ccf = Math.round((0.5*cf + 0.5*cc)*100)/100;
        ccfEl.textContent = ccf.toFixed(1);
        colorCc(ccfEl, ccf);
    } else { ccfEl.textContent = '—'; colorCc(ccfEl, null); }

    // Habilitar / bloquear CE según CCF
    const showCE  = ccf !== null && ccf < UMBRAL;
    const ceCell  = document.getElementById(`ce-cell-ac-m${mid}`);
    const ceInp   = ceCell?.querySelector('input');
    if (ceInp) {
        ceInp.disabled = !showCE;
        ceInp.classList.toggle('ni-rec', showCE);
        if (!showCE) ceInp.value = '';
    }
    if (ceCell) ceCell.classList.toggle('locked', !showCE);

    recalcPromediosClase();
}

/* ── CÁLCULO: Extraordinario — CEF = 0.3×NF + 0.7×CE ────────── */
function recalcExtraordinario(mid) {
    const cfEl  = document.getElementById(`cf-${mid}`);
    const cf    = parseFloat(cfEl?.textContent);
    const ceInp = document.querySelector(`input[data-campo="nota_ce"][data-mid="${mid}"]`);
    const ce    = ceInp && ceInp.value.trim() ? parseFloat(ceInp.value) : NaN;

    const ccfEl = document.getElementById(`ccf-${mid}`);
    if (!isNaN(cf) && !isNaN(ce)) {
        const cef = Math.round((0.3*cf + 0.7*ce)*100)/100;
        ccfEl.textContent = cef.toFixed(1);
        colorCc(ccfEl, cef);
    }
    recalcPromediosClase();
}

/* ── CÁLCULO: Asistencia ─────────────────────────────────────── */
function recalcAsistencia(mid) {
    const asistVals = [];
    for (let p=1;p<=4;p++) {
        const inp = document.querySelector(`input[data-campo="asist_p${p}"][data-mid="${mid}"]`);
        const v = parseInt(inp?.value);
        if (!isNaN(v) && inp?.value.trim()) asistVals.push(v);
    }
    // Asistencia: suma clases asistidas vs total esperado (clases_p1 × 4 períodos)
    // Simplificado: usamos clases_p1 × nPeriodos si existe
    const clsInp = document.querySelector(`input[data-campo="clases_p1"][data-mid="${mid}"]`);
    const clsV   = parseInt(clsInp?.value);
    const totalAsist  = asistVals.reduce((a,b)=>a+b,0);
    const totalClases = (!isNaN(clsV) && clsV>0) ? clsV * 4 : 0;

    const pctEl = document.getElementById(`pct-${mid}`);
    if (totalClases > 0 && asistVals.length) {
        const pct = Math.round(totalAsist/totalClases*10000)/100;
        pctEl.textContent = pct.toFixed(1)+'%';
        pctEl.classList.remove('c-nil','c-ok','c-mid','c-bad');
        if      (pct>=75) pctEl.classList.add('c-ok');
        else if (pct>=50) pctEl.classList.add('c-mid');
        else              pctEl.classList.add('c-bad');
    } else {
        pctEl.textContent='—';
        pctEl.classList.remove('c-ok','c-mid','c-bad');
        pctEl.classList.add('c-nil');
    }
}

/* ── Promedios de clase (footer) ─────────────────────────────── */
function recalcPromediosClase() {
    // Columnas comp
    for (let c=1;c<=4;c++) {
        for (let p=1;p<=4;p++) {
            const inputs = document.querySelectorAll(`input[data-campo="comp${c}_p${p}"]`);
            let s=0,n=0;
            inputs.forEach(i=>{ const v=parseFloat(i.value); if(!isNaN(v)){s+=v;n++;} });
            const el = document.getElementById(`avg-c${c}-p${p}`);
            if (el) el.textContent = n ? (s/n).toFixed(1) : '—';
        }
        // PC promedio
        const pcs = document.querySelectorAll(`[id^="pc${c}-"]`);
        let s2=0,n2=0;
        pcs.forEach(e=>{ const v=parseFloat(e.textContent); if(!isNaN(v)){s2+=v;n2++;} });
        const elPc = document.getElementById(`avg-pc${c}`);
        if (elPc) elPc.textContent = n2 ? (s2/n2).toFixed(1) : '—';
    }
    // Cal Final promedio
    const cfs = document.querySelectorAll('[id^="cf-"]');
    let sc=0,nc=0;
    cfs.forEach(e=>{ const v=parseFloat(e.textContent); if(!isNaN(v)){sc+=v;nc++;} });
    const elCf = document.getElementById('avg-cf');
    if (elCf) elCf.textContent = nc ? (sc/nc).toFixed(1) : '—';
    // CCF
    const ccfs = document.querySelectorAll('[id^="ccf-"]');
    let sc2=0,nc2=0;
    ccfs.forEach(e=>{ const v=parseFloat(e.textContent); if(!isNaN(v)){sc2+=v;nc2++;} });
    const elCcf = document.getElementById('avg-ccf');
    if (elCcf) elCcf.textContent = nc2 ? (sc2/nc2).toFixed(1) : '—';
    // CEXF
    const cexfs = document.querySelectorAll('[id^="cexf-"]');
    let sc3=0,nc3=0;
    cexfs.forEach(e=>{ const v=parseFloat(e.textContent); if(!isNaN(v)){sc3+=v;nc3++;} });
    const elCexf = document.getElementById('avg-cexf');
    if (elCexf) elCexf.textContent = nc3 ? (sc3/nc3).toFixed(1) : '—';
    // Asistencia
    for (let p=1;p<=4;p++) {
        const inputs = document.querySelectorAll(`input[data-campo="asist_p${p}"]`);
        let s=0,n=0;
        inputs.forEach(i=>{ const v=parseFloat(i.value); if(!isNaN(v)){s+=v;n++;} });
        const el = document.getElementById(`avg-asist-p${p}`);
        if (el) el.textContent = n ? (s/n).toFixed(0) : '—';
    }
    // % asistencia
    const pcts = document.querySelectorAll('[id^="pct-"]');
    let sp=0,np=0;
    pcts.forEach(e=>{ const v=parseFloat(e.textContent); if(!isNaN(v)){sp+=v;np++;} });
    const elPct = document.getElementById('avg-pct');
    if (elPct) elPct.textContent = np ? (sp/np).toFixed(1)+'%' : '—';
}

/* ── Navegación teclado ──────────────────────────────────────── */
document.addEventListener('keydown', e => {
    const a = document.activeElement;
    if (!a?.classList.contains('ni')) return;
    const campo = a.dataset.campo;
    const fila  = parseInt(a.dataset.fila ?? 0);
    let next = null;
    if (e.key==='Enter'||e.key==='ArrowDown') {
        e.preventDefault();
        next = document.querySelector(`input[data-campo="${campo}"][data-fila="${fila+1}"]`);
    } else if (e.key==='ArrowUp') {
        e.preventDefault();
        next = document.querySelector(`input[data-campo="${campo}"][data-fila="${fila-1}"]`);
    } else if (e.key==='Escape') {
        e.preventDefault();
        a.value = a.dataset.orig ?? '';
        colorNi(a); a.blur(); return;
    }
    if (next) { next.focus(); requestAnimationFrame(()=>{ try{next.select();}catch(ex){} }); }
});
document.addEventListener('focusin', e => {
    if (e.target?.classList.contains('ni')) e.target.dataset.orig = e.target.value ?? '';
});

/* ── Save status ─────────────────────────────────────────────── */
function setSaveStatus(state, text) {
    const el = document.getElementById('save-st');
    if (!el) return;
    const icons = {saving:'bi-arrow-repeat',saved:'bi-check-circle-fill text-success',error:'bi-exclamation-circle-fill text-danger'};
    el.innerHTML = `<i class="bi ${icons[state]??'bi-check-circle-fill text-success'} me-1"></i>${text}`;
}

/* ── Toast ───────────────────────────────────────────────────── */
function toast(msg, tipo='success') {
    const el = document.getElementById('toast-ac');
    document.getElementById('toast-msg').textContent = msg;
    el.className = `toast align-items-center border-0 text-white bg-${tipo==='success'?'success':'danger'}`;
    new bootstrap.Toast(el,{delay:3500}).show();
}

/* ── Build POST body para un estudiante ─────────────────────── */
function buildBody(mid) {
    const body = new URLSearchParams({
        _token: CSRF_AC,
        asignacion_id: ASIGNACION_ID_AC,
        school_year_id: SCHOOL_YEAR_ID_AC,
    });
    const fila = document.querySelector(`tr[data-mid="${mid}"]`);
    fila?.querySelectorAll('input[data-campo]').forEach(inp => {
        body.append(`notas[${mid}][${inp.dataset.campo}]`, inp.value.trim());
    });
    return body;
}

/* ── Auto-guardar al salir de celda ─────────────────────────── */
function autoGuardar(inp) {
    const mid = inp.dataset.mid;
    if (!mid) return;
    setSaveStatus('saving','Guardando…');
    fetch(ROUTE_GUARDAR_AC,{
        method:'POST',
        headers:{'X-CSRF-TOKEN':CSRF_AC,'Accept':'application/json'},
        body: buildBody(mid),
    }).then(r=>r.json()).then(d=>{
        if (d.success) {
            inp.style.boxShadow='0 0 0 2px #22c55e66';
            setTimeout(()=>inp.style.boxShadow='',700);
            setSaveStatus('saved','Guardado '+new Date().toLocaleTimeString('es',{hour:'2-digit',minute:'2-digit'}));
            limpiarPendiente();
        } else setSaveStatus('error','Error al guardar');
    }).catch(()=>setSaveStatus('error','Error al guardar'));
}

/* ── Guardar todo ────────────────────────────────────────────── */
function guardarTodo() {
    const btn = document.getElementById('btn-guardar');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Guardando...';
    setSaveStatus('saving','Guardando…');

    const body = new URLSearchParams({
        _token:CSRF_AC, asignacion_id:ASIGNACION_ID_AC, school_year_id:SCHOOL_YEAR_ID_AC
    });
    document.querySelectorAll('.fila-est').forEach(fila => {
        const mid = fila.dataset.mid;
        fila.querySelectorAll('input[data-campo]').forEach(inp => {
            body.append(`notas[${mid}][${inp.dataset.campo}]`, inp.value.trim());
        });
    });

    fetch(ROUTE_GUARDAR_AC,{
        method:'POST',
        headers:{'X-CSRF-TOKEN':CSRF_AC,'Accept':'application/json'},
        body,
    }).then(r=>r.json()).then(d=>{
        toast(d.success ? d.message : (d.message??'Error.'), d.success?'success':'danger');
        if (d.success) {
            setSaveStatus('saved','Guardado '+new Date().toLocaleTimeString('es',{hour:'2-digit',minute:'2-digit'}));
            limpiarPendiente();
        } else setSaveStatus('error','Error al guardar');
    }).catch(()=>{ toast('Error de conexión.','danger'); setSaveStatus('error','Error al guardar'); })
    .finally(()=>{ btn.disabled=false; btn.innerHTML='<i class="bi bi-floppy me-2"></i>Guardar Todo'; });
}

/* ── Publicar ────────────────────────────────────────────────── */
function publicarPlanilla() {
    const btn = document.getElementById('btn-publicar');
    btn.disabled = true;
    fetch(ROUTE_PUBLICAR_AC,{
        method:'POST',
        headers:{'X-CSRF-TOKEN':CSRF_AC,'Accept':'application/json'},
        body: new URLSearchParams({_token:CSRF_AC,asignacion_id:ASIGNACION_ID_AC,school_year_id:SCHOOL_YEAR_ID_AC}),
    }).then(r=>r.json()).then(d=>{
        if (d.success) {
            const pub = d.publicado;
            document.getElementById('txt-publicar').textContent = pub ? 'Publicado ✓' : 'Publicar';
            btn.className = `btn ${pub?'btn-success':'btn-outline-light'} px-3`;
            toast(d.message,'success');
        } else toast(d.message??'Error.','danger');
    }).catch(()=>toast('Error de conexión.','danger'))
    .finally(()=>btn.disabled=false);
}

/* ── Inicialización ──────────────────────────────────────────── */
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.ni').forEach(inp => colorNi(inp));
    document.querySelectorAll('.fila-est').forEach(fila => {
        const mid = fila.dataset.mid;
        for (let c=1;c<=4;c++) recalcComp(mid,c);
        recalcCompletivo(mid);
        recalcExtraordinario(mid);
        recalcAsistencia(mid);
    });
    recalcPromediosClase();
    // Marcar pendiente al editar
    document.querySelectorAll('.ni').forEach(inp => {
        inp.addEventListener('input', marcarPendiente);
    });
});
</script>
@endpush
