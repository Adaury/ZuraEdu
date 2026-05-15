@extends('layouts.portal-estudiante')

@section('title', 'Mi Boletín — ' . ($estudiante->nombre_completo ?? ''))

@section('activeKey', 'boletin')

@push('styles')
<style>
/* ── Hero card estudiante ── */
.bol-hero {
    background: linear-gradient(135deg,#1e40af 0%,#4f46e5 100%);
    border-radius: 16px;
    padding: 1.25rem 1.4rem;
    margin-bottom: 1.25rem;
    color: #fff;
    display: flex; align-items: center; gap: 1rem; flex-wrap: wrap;
}
.bol-hero-avatar {
    width: 52px; height: 52px; border-radius: 50%;
    background: rgba(255,255,255,.18);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.5rem; flex-shrink: 0;
}
.bol-hero h2 { font-size: 1rem; font-weight: 800; margin: 0 0 .2rem; }
.bol-hero .sub { font-size: .78rem; opacity: .85; }
.bol-hero-stats { margin-left: auto; display: flex; gap: 1.25rem; flex-wrap: wrap; }
.bol-hero-stat { text-align: center; }
.bol-hero-stat .val { font-size: 1.2rem; font-weight: 800; line-height: 1; }
.bol-hero-stat .lbl { font-size: .68rem; opacity: .8; margin-top: .15rem; }

/* ── Sección card ── */
.bol-section {
    background: var(--prt-card);
    border: 1px solid var(--prt-border);
    border-radius: 14px;
    overflow: hidden;
    margin-bottom: 1.25rem;
    box-shadow: 0 1px 6px rgba(0,0,0,.04);
}
.bol-section-hd {
    padding: .75rem 1.1rem;
    border-bottom: 1px solid var(--prt-border);
    display: flex; align-items: center; gap: .6rem;
    background: linear-gradient(90deg,rgba(37,99,235,.06) 0%,transparent 100%);
}
.bol-section-hd.purple { background: linear-gradient(90deg,rgba(124,58,237,.07) 0%,transparent 100%); }
.bol-section-hd .title { font-weight: 800; font-size: .88rem; color: var(--prt-text); }
.bol-section-hd .count { margin-left: auto; font-size: .72rem; color: var(--prt-muted); }

/* ── Fila materia ── */
.mat-row {
    padding: .8rem 1.1rem;
    border-bottom: 1px solid var(--prt-border);
    display: flex; align-items: center; gap: .75rem; flex-wrap: wrap;
}
.mat-row:last-child { border-bottom: none; }
.mat-name {
    font-size: .84rem; font-weight: 700; color: var(--prt-text);
    min-width: 120px; flex: 1;
}
.mat-name .mat-sub { font-size: .7rem; font-weight: 400; color: var(--prt-muted); margin-top: .1rem; }
.per-badges { display: flex; gap: .35rem; flex-wrap: wrap; align-items: center; }

/* ── Badges período ── */
.pb {
    display: inline-flex; flex-direction: column; align-items: center;
    min-width: 44px; border-radius: 9px; padding: .25rem .4rem;
    font-size: .78rem; font-weight: 800; line-height: 1.2;
}
.pb .pb-lbl { font-size: .6rem; font-weight: 700; opacity: .75; text-transform: uppercase; }
.pb-ok   { background: #dcfce7; color: #15803d; }
.pb-mal  { background: #fee2e2; color: #dc2626; }
.pb-nd   { background: #f1f5f9; color: #94a3b8; }
.pb-rec  { background: #fef9c3; color: #92400e; position: relative; }
.pb-rec::after { content: '↑'; font-size: .55rem; position: absolute; top: 2px; right: 3px; }

/* ── Badge final ── */
.final-wrap { margin-left: auto; flex-shrink: 0; display: flex; flex-direction: column; align-items: center; gap: .25rem; }
.final-num {
    display: inline-flex; align-items: center; justify-content: center;
    width: 52px; height: 52px; border-radius: 12px;
    font-size: 1.05rem; font-weight: 900; line-height: 1;
}
.fn-ok  { background: #dcfce7; color: #15803d; border: 2px solid #86efac; }
.fn-mal { background: #fee2e2; color: #dc2626; border: 2px solid #fca5a5; }
.fn-nd  { background: #f1f5f9; color: #94a3b8; border: 2px solid #e2e8f0; }
.sit-pill {
    font-size: .65rem; font-weight: 800; border-radius: 99px;
    padding: .15rem .5rem; letter-spacing: .04em;
}
.sp-ok  { background: #dcfce7; color: #15803d; }
.sp-mal { background: #fee2e2; color: #dc2626; }

/* ── Progress bar ── */
.nota-bar-wrap { width: 100%; height: 4px; background: #f1f5f9; border-radius: 99px; margin-top: .35rem; overflow: hidden; }
.nota-bar { height: 100%; border-radius: 99px; transition: width .4s; }
.nota-bar-ok  { background: linear-gradient(90deg,#10b981,#34d399); }
.nota-bar-mal { background: linear-gradient(90deg,#ef4444,#f87171); }

/* ── Separador período ── */
.per-sep { font-size: .62rem; color: var(--prt-muted); padding: 0 .1rem; }

/* ── Bloques de competencia — boletín ── */
.comp-block-grid {
    display: grid; grid-template-columns: repeat(2,1fr); gap: .55rem; margin-bottom: .55rem;
}
@media (max-width: 500px) { .comp-block-grid { grid-template-columns: 1fr; } }

.comp-block { border: 1px solid #e5e7eb; border-radius: 8px; overflow: hidden; }
.comp-block-hd {
    padding: .32rem .6rem; color: #fff; font-size: .65rem; font-weight: 800;
    letter-spacing: .04em; display: flex; align-items: center; gap: .3rem;
}
.comp-block-tbl { width: 100%; border-collapse: collapse; font-size: .72rem; }
.comp-block-tbl tr { border-top: 1px solid #f1f5f9; }
.comp-block-tbl tr:first-child { border-top: none; }
.comp-block-tbl td { padding: .22rem .45rem; vertical-align: middle; }
.cbt-per  { color: #6b7280; font-size: .63rem; font-weight: 700; white-space: nowrap; }
.cbt-p    { font-weight: 700; color: #374151; }
.cbt-rp   { display:inline-block; background:#fef9c3; color:#92400e; border-radius:3px;
             padding:.03rem .28rem; font-size:.63rem; font-weight:800; }
.cbt-cf   { font-weight: 800; font-size: .8rem; text-align: right; }
.cbt-ok   { color: #15803d; }
.cbt-mal  { color: #dc2626; }
.cbt-warn { color: #d97706; }
.cbt-nd   { color: #94a3b8; }
.comp-block-ft {
    padding: .28rem .55rem; background: #f8fafc; border-top: 2px solid #e5e7eb;
    display: flex; align-items: center; justify-content: space-between; font-size: .69rem;
}
.comp-block-ft .cft-lbl { color: #6b7280; font-weight: 700; text-transform: uppercase; font-size: .6rem; letter-spacing:.04em; }
.comp-block-ft .cft-val { font-weight: 900; font-size: .88rem; }
/* nota final materia */
.bol-nota-final-row {
    display: flex; align-items: center; justify-content: space-between;
    padding: .5rem .75rem; background: #f8fafc; border: 1px solid #e5e7eb;
    border-radius: 8px; margin-top: .1rem;
}

/* ── colores reutilizables ── */
.bok-ok   { color: #15803d; }
.bok-mal  { color: #dc2626; }
.bok-warn { color: #d97706; }
.bok-nd   { color: #94a3b8; }

/* ── DARK MODE ── */
[data-theme="dark"] .pb-nd   { background: #1e293b; color: #64748b; }
[data-theme="dark"] .pb-ok   { background: #052e16; color: #4ade80; }
[data-theme="dark"] .pb-mal  { background: #1c0000; color: #f87171; }
[data-theme="dark"] .pb-rec  { background: #1a1500; color: #fbbf24; }
[data-theme="dark"] .fn-ok   { background: #052e16; color: #4ade80; border-color: #166534; }
[data-theme="dark"] .fn-mal  { background: #1c0000; color: #f87171; border-color: #7f1d1d; }
[data-theme="dark"] .fn-nd   { background: #1e293b; color: #64748b; border-color: #334155; }
[data-theme="dark"] .sp-ok   { background: #052e16; color: #4ade80; }
[data-theme="dark"] .sp-mal  { background: #1c0000; color: #f87171; }
[data-theme="dark"] .nota-bar-wrap { background: #1e293b; }
[data-theme="dark"] .bol-section-hd { background: linear-gradient(90deg,rgba(59,130,246,.08) 0%,transparent 100%); }
[data-theme="dark"] .bol-section-hd.purple { background: linear-gradient(90deg,rgba(139,92,246,.08) 0%,transparent 100%); }
[data-theme="dark"] .bol-acad-tbl th, [data-theme="dark"] .bol-acad-tbl td { border-color: #374151; }
[data-theme="dark"] .bat-comp-hd  { background: #1e293b; color: #cbd5e1; }
[data-theme="dark"] .bat-p    { background: #1e3a5f; color: #93c5fd; }
[data-theme="dark"] .bat-rp   { background: #2d1b00; color: #fde68a; }
[data-theme="dark"] .bat-cf   { background: #052e16; color: #4ade80; }
[data-theme="dark"] .bat-prom-hd { background: #2e1b4e; color: #c4b5fd; }
[data-theme="dark"] .bat-comp-cell { background: #1e293b; color: #e2e8f0; }
[data-theme="dark"] .bat-p-cell   { background: #0f172a; }
[data-theme="dark"] .bat-rp-cell  { background: #1c1000; }
[data-theme="dark"] .bat-prom-cell{ background: #1a1040; }
[data-theme="dark"] .bat-foot-row td { background: #172032 !important; }
[data-theme="dark"] .bok-rec  { background: #1a1500 !important; }
</style>
@endpush

@section('content')

{{-- ── Hero card ─────────────────────────────────────────────────── --}}
<div class="bol-hero">
    <div class="bol-hero-avatar"><i class="bi bi-mortarboard-fill"></i></div>
    <div>
        <h2>{{ $estudiante->nombre_completo }}</h2>
        <div class="sub">
            {{ $schoolYear->nombre ?? '—' }} &nbsp;·&nbsp;
            {{ $matricula->grupo->nombre_completo ?? '—' }}
        </div>
    </div>
    <div class="bol-hero-stats">
        <div class="bol-hero-stat">
            <div class="val">{{ $resumenAsistencia['porcentaje'] !== null ? $resumenAsistencia['porcentaje'].'%' : '—' }}</div>
            <div class="lbl">Asistencia</div>
        </div>
        @php
            $totalMats = $calificaciones->count() + $calificacionesAcademicas->count();
            $aprobadas = 0;
            foreach ($calificacionesAcademicas as $c) {
                if ($c->nota_final !== null && $c->nota_final >= 70) $aprobadas++;
            }
        @endphp
        <div class="bol-hero-stat">
            <div class="val">{{ $totalMats }}</div>
            <div class="lbl">Materias</div>
        </div>
        @if($calificacionesAcademicas->count())
        <div class="bol-hero-stat">
            <div class="val">{{ $aprobadas }}/{{ $calificacionesAcademicas->count() }}</div>
            <div class="lbl">Aprobadas</div>
        </div>
        @endif
    </div>
    <div style="margin-left:auto;">
        <div style="display:flex;gap:.5rem;">
            <button onclick="window.print()" style="background:rgba(255,255,255,.15);color:#fff;border:1.5px solid rgba(255,255,255,.35);border-radius:9px;padding:.4rem .9rem;font-size:.78rem;font-weight:700;cursor:pointer;display:flex;align-items:center;gap:.4rem;">
                <i class="bi bi-printer"></i>Imprimir
            </button>
            <a href="{{ route('portal.estudiante.boletin.pdf') }}" target="_blank"
               style="background:rgba(255,255,255,.22);color:#fff;border:1.5px solid rgba(255,255,255,.45);border-radius:9px;padding:.4rem .9rem;font-size:.78rem;font-weight:700;text-decoration:none;display:flex;align-items:center;gap:.4rem;">
                <i class="bi bi-file-earmark-pdf"></i>PDF
            </a>
            <a href="{{ route('portal.estudiante.constancia') }}"
               style="background:rgba(255,255,255,.15);color:#fff;border:1.5px solid rgba(255,255,255,.35);border-radius:9px;padding:.4rem .9rem;font-size:.78rem;font-weight:700;text-decoration:none;display:flex;align-items:center;gap:.4rem;">
                <i class="bi bi-award"></i>Constancia
            </a>
        </div>
    </div>
</div>

{{-- ── Área Técnica ─────────────────────────────────────────────────── --}}
@if($calificaciones->count())
<div class="bol-section">
    <div class="bol-section-hd">
        <i class="bi bi-journal-check" style="color:#2563eb;font-size:1rem;"></i>
        <span class="title">Área Técnica</span>
        <span class="count">{{ $calificaciones->count() }} materia(s)</span>
    </div>
    @foreach($calificaciones as $asignacionId => $calsGrupo)
        @php
            $asignatura   = $calsGrupo->first()?->asignacion?->asignatura;
            $calsXPeriodo = $calsGrupo->keyBy('periodo_id');
            $notas        = $calsGrupo->pluck('nota_final')->filter()->values();
            $notaFinal    = $notas->count() ? round($notas->avg(), 2) : null;
        @endphp
        <div class="mat-row">
            <div style="flex:1;min-width:110px;">
                <div class="mat-name">{{ $asignatura->nombre ?? '—' }}</div>
                <div class="nota-bar-wrap">
                    <div class="nota-bar {{ $notaFinal !== null ? ($notaFinal >= 65 ? 'nota-bar-ok' : 'nota-bar-mal') : '' }}"
                         style="width:{{ $notaFinal !== null ? min($notaFinal,100) : 0 }}%;"></div>
                </div>
            </div>
            <div class="per-badges">
                @foreach($periodos as $per)
                    @php $cal = $calsXPeriodo->get($per->id); $n = $cal?->nota_final; @endphp
                    <div class="pb {{ $n !== null ? ($n >= 65 ? 'pb-ok' : 'pb-mal') : 'pb-nd' }}">
                        <span class="pb-lbl">P{{ $per->numero }}</span>
                        {{ $n !== null ? $n : '—' }}
                    </div>
                @endforeach
            </div>
            <div class="final-wrap">
                <div class="final-num {{ $notaFinal !== null ? ($notaFinal >= 65 ? 'fn-ok' : 'fn-mal') : 'fn-nd' }}">
                    {{ $notaFinal !== null ? $notaFinal : '—' }}
                </div>
                @if($notaFinal !== null)
                <span class="sit-pill {{ $notaFinal >= 65 ? 'sp-ok' : 'sp-mal' }}">
                    {{ $notaFinal >= 65 ? 'APROBADO' : 'REPROBADO' }}
                </span>
                @endif
            </div>
        </div>
    @endforeach
</div>
@endif

{{-- ── Área Académica — 4 Competencias MINERD ──────────────────────────── --}}
@if($calificacionesAcademicas->count())
@php
use App\Models\CalificacionAcademica;
$comps = CalificacionAcademica::COMPETENCIAS;
$fmtB  = fn($v) => $v !== null
    ? rtrim(rtrim(number_format((float)$v, 1, '.', ''), '0'), '.')
    : '—';
@endphp
<div class="bol-section">
    <div class="bol-section-hd purple">
        <i class="bi bi-mortarboard" style="color:#7c3aed;font-size:1rem;"></i>
        <span class="title">Área Académica — Competencias MINERD</span>
        <span class="count">{{ $calificacionesAcademicas->count() }} materia(s)</span>
    </div>

    @foreach($calificacionesAcademicas as $cal)
    @php
        $asignatura = $cal->asignacion?->asignatura;

        // ── Calcular CF dinámicamente desde P y R (CF = P + min(R, 100-P)) ──
        // No depende de avg_comp{c}_p{p} cacheado en BD — siempre correcto.
        $cfDyn   = [];   // [comp][per] → CF calculado
        $promDyn = [];   // [comp]      → promedio de la competencia
        for ($c = 1; $c <= 4; $c++) {
            $cfDyn[$c] = [];
            for ($p = 1; $p <= 4; $p++) {
                $pb = $cal->{"comp{$c}_p{$p}"};
                $rv = $cal->{"comp{$c}_r{$p}"};
                if ($pb === null) { $cfDyn[$c][$p] = null; continue; }
                if ($rv !== null && (float)$pb < 70) {
                    $mr = max(0.0, 100.0 - (float)$pb);
                    $cfDyn[$c][$p] = round((float)$pb + min((float)$rv, $mr), 2);
                } else {
                    $cfDyn[$c][$p] = round((float)$pb, 2);
                }
            }
            $pVals = array_values(array_filter($cfDyn[$c], fn($v) => $v !== null));
            $promDyn[$c] = $pVals ? round(array_sum($pVals) / count($pVals), 2) : null;
        }
        $nVals = array_values(array_filter($promDyn, fn($v) => $v !== null));
        $nota  = $nVals ? round(array_sum($nVals) / count($nVals), 2) : null;

        $hayRec = false;
        for ($ci = 1; $ci <= 4; $ci++)
            for ($p = 1; $p <= 4; $p++) {
                $pb = $cal->{"comp{$ci}_p{$p}"}; $rv = $cal->{"comp{$ci}_r{$p}"};
                if ($pb !== null && $pb < 70 && $rv !== null && $rv > 0) { $hayRec = true; break 2; }
            }
    @endphp

    <div style="padding:.75rem 1rem;border-bottom:1px solid var(--prt-border);">

        {{-- Nombre de la materia --}}
        <div class="mat-name" style="margin-bottom:.55rem;">
            <i class="bi bi-journal-text" style="color:#7c3aed;margin-right:.3rem;"></i>
            {{ $asignatura->nombre ?? '—' }}
        </div>

        {{-- 4 bloques de competencias --}}
        <div class="comp-block-grid">
        @foreach($comps as $ci => $comp)
        @php $prom = $promDyn[$ci]; @endphp
        <div class="comp-block">
            {{-- Cabecera bloque --}}
            <div class="comp-block-hd" style="background:{{ $comp['color'] }};">
                <i class="bi {{ $comp['icon'] }}"></i>
                C{{ $ci }} — {{ $comp['nombre'] }}
            </div>
            {{-- Filas P1–P4 --}}
            <table class="comp-block-tbl">
            @foreach([1,2,3,4] as $p)
            @php
                $pBase  = $cal->{"comp{$ci}_p{$p}"};
                $rVal   = $cal->{"comp{$ci}_r{$p}"};
                $cfVal  = $cfDyn[$ci][$p];   // CF calculado dinámicamente (P + min(R, 100-P))
                $hasRec = $pBase !== null && $pBase < 70 && $rVal !== null && $rVal > 0;
                $cfCls  = $cfVal !== null ? ($cfVal >= 70 ? 'cbt-ok' : 'cbt-mal') : 'cbt-nd';
            @endphp
            <tr>
                <td class="cbt-per">P{{ $p }}</td>
                <td class="cbt-p {{ $pBase !== null && $pBase < 70 ? 'cbt-warn' : '' }}">
                    {{ $fmtB($pBase) }}
                </td>
                <td>
                    @if($hasRec)
                        <span class="cbt-rp">+{{ $fmtB($rVal) }}</span>
                    @endif
                </td>
                <td class="cbt-cf {{ $cfCls }}">{{ $fmtB($cfVal) }}</td>
            </tr>
            @endforeach
            </table>
            {{-- Calificación final del bloque --}}
            <div class="comp-block-ft">
                <span class="cft-lbl">Calificación Final</span>
                <span class="cft-val {{ $prom !== null ? ($prom >= 70 ? 'cbt-ok' : 'cbt-mal') : 'cbt-nd' }}">
                    {{ $fmtB($prom) }}
                </span>
            </div>
        </div>
        @endforeach
        </div>

        {{-- Nota final de la materia --}}
        <div class="bol-nota-final-row">
            <span style="font-size:.78rem;font-weight:700;color:#374151;">
                <i class="bi bi-award-fill" style="color:#7c3aed;"></i>
                Nota Final — {{ $asignatura->nombre ?? '—' }}
            </span>
            <div style="display:flex;align-items:center;gap:.6rem;">
                <span style="font-size:1.3rem;font-weight:900;{{ $nota !== null ? ($nota >= 70 ? 'color:#15803d;' : 'color:#dc2626;') : 'color:#94a3b8;' }}">
                    {{ $fmtB($nota) }}
                </span>
                @if($nota !== null)
                @php $sitDyn = $nota >= 70 ? 'A' : 'R'; @endphp
                <span class="sit-pill {{ $sitDyn === 'A' ? 'sp-ok' : 'sp-mal' }}">
                    {{ $sitDyn === 'A' ? 'APROBADO' : 'REPROBADO' }}
                </span>
                @endif
            </div>
        </div>

        @if($hayRec)
        <div style="margin-top:.38rem;font-size:.62rem;color:#92400e;display:flex;align-items:center;gap:.3rem;">
            <span style="background:#fef9c3;border-radius:3px;padding:.05rem .28rem;font-weight:700;">RP</span>
            Recuperación pedagógica &mdash; CF = P + RP
        </div>
        @endif
    </div>
    @endforeach
</div>
@endif

@if($calificaciones->isEmpty() && $calificacionesAcademicas->isEmpty())
<div style="text-align:center;padding:3.5rem 1rem;color:#64748b;">
    <i class="bi bi-file-earmark-x" style="font-size:3rem;display:block;margin-bottom:.85rem;color:#cbd5e1;"></i>
    <div style="font-weight:700;font-size:.9rem;margin-bottom:.3rem;">Sin calificaciones aún</div>
    <div style="font-size:.8rem;">Aún no hay calificaciones publicadas para este año escolar.</div>
</div>
@endif

@endsection
