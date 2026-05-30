@extends('layouts.portal')
@section('page-title', 'Boletín — ' . ($matricula->estudiante?->nombre_completo ?? ''))
@section('portal-name', 'Portal Docente')

@section('sidebar')
    @include('portal.docente._sidebar_clase', ['activeKey' => 'boletines'])
@endsection

@section('bottom-nav')
    <a href="{{ route('portal.docente.dashboard') }}" class="prt-nav-item">
        <i class="bi bi-house-fill"></i>Inicio
    </a>
    <a href="{{ route('portal.docente.calificaciones', $asignacion) }}" class="prt-nav-item">
        <i class="bi bi-journal-check"></i>Notas
    </a>
    <a href="{{ route('portal.docente.boletines', $asignacion) }}" class="prt-nav-item active">
        <i class="bi bi-file-earmark-text"></i>Boletines
    </a>
    <a href="{{ route('portal.docente.estudiantes', $asignacion) }}" class="prt-nav-item">
        <i class="bi bi-people-fill"></i>Estudiantes
    </a>
@endsection

@push('styles')
<style>
.bol-table { width:100%; border-collapse:collapse; font-size:.83rem; }
.bol-table thead tr { background:#f8faff; border-bottom:1.5px solid #e2e8f0; }
.bol-table th {
    padding:.5rem .6rem; text-align:center;
    font-size:.7rem; font-weight:700; letter-spacing:.06em;
    text-transform:uppercase; color:#2563eb; white-space:nowrap;
}
.bol-table th.th-asig { text-align:left; min-width:150px; }
.bol-table td { padding:.5rem .6rem; border-bottom:1px solid #f1f5f9; }
.bol-table tr:last-child td { border-bottom:none; }
.bol-table tbody tr:hover { background:#f8faff; }
.nbadge {
    display:inline-block; min-width:42px; text-align:center;
    font-weight:700; font-size:.82rem; border-radius:7px; padding:.2rem .35rem;
}
.nb-ok   { background:#dcfce7; color:#15803d; }
.nb-mal  { background:#fee2e2; color:#dc2626; }
.nb-nd   { background:#f1f5f9; color:#94a3b8; }
.nb-final-ok  { background:#dcfce7; color:#15803d; font-weight:800; font-size:.9rem; }
.nb-final-mal { background:#fee2e2; color:#dc2626; font-weight:800; font-size:.9rem; }
.nb-final-nd  { background:#f1f5f9; color:#94a3b8; }
.sit-badge {
    display:inline-block; border-radius:20px; padding:.2rem .65rem;
    font-size:.72rem; font-weight:700; letter-spacing:.05em;
}
.sit-a { background:#dcfce7; color:#15803d; }
.sit-r { background:#fee2e2; color:#dc2626; }

[data-theme="dark"] .bol-table th { color:#60a5fa; }
[data-theme="dark"] .bol-table thead tr { background:#1a2640; border-bottom-color:#334155; }
[data-theme="dark"] .bol-table tbody tr:hover { background:#1e3a5f; }
[data-theme="dark"] .bol-table td { border-bottom-color:#334155; color:var(--prt-text); }
[data-theme="dark"] .nb-nd,
[data-theme="dark"] .nb-final-nd  { background:#1e293b; color:#475569; }
[data-theme="dark"] .nb-ok,
[data-theme="dark"] .nb-final-ok  { background:#052e16; color:#4ade80; }
[data-theme="dark"] .nb-mal,
[data-theme="dark"] .nb-final-mal { background:#1c0000; color:#f87171; }
[data-theme="dark"] .sit-a { background:#052e16; color:#4ade80; }
[data-theme="dark"] .sit-r { background:#1c0000; color:#f87171; }
[data-theme="dark"] .bv-data-label { color:#60a5fa !important; }
[data-theme="dark"] .bv-data-value { color:#93c5fd !important; }
[data-theme="dark"] .bv-data-neutral { color:#94a3b8 !important; }
[data-theme="dark"] .bv-summary { border-top-color:#334155 !important; }

/* Grid datos estudiante: 1 col en móvil muy estrecho */
.bv-student-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: .5rem .75rem;
}
@media (max-width: 360px) {
    .bv-student-grid { grid-template-columns: 1fr; }
}
</style>
@endpush

@section('content')

{{-- Header --}}
<div style="display:flex;align-items:center;gap:.75rem;margin-bottom:1rem;flex-wrap:wrap;">
    <a href="{{ route('portal.docente.boletines', $asignacion) }}" class="btn-back"
       style="background:#f1f5f9;color:#374151;border-radius:8px;padding:.4rem .85rem;font-size:.8rem;text-decoration:none;display:flex;align-items:center;gap:.4rem;">
        <i class="bi bi-arrow-left"></i>Volver
    </a>
    <div style="flex:1;">
        <h1 style="font-size:1rem;font-weight:800;margin:0;">
            <i class="bi bi-file-earmark-text-fill" style="color:#5b21b6;"></i>
            Boletín de Calificaciones
        </h1>
        <div class="dm-text-muted" style="font-size:.75rem;color:#64748b;">
            Solo materias impartidas por ti · {{ $asignacion->grupo?->nombre_completo ?? '—' }}
            @if($schoolYear) · {{ $schoolYear->nombre }} @endif
        </div>
    </div>
    <a href="{{ route('portal.docente.boletin.pdf', [$asignacion, $matricula]) }}"
       target="_blank"
       style="background:#7c3aed;color:#fff;border-radius:8px;padding:.4rem .9rem;font-size:.8rem;text-decoration:none;display:flex;align-items:center;gap:.4rem;white-space:nowrap;">
        <i class="bi bi-file-earmark-pdf"></i>Descargar PDF
    </a>
</div>

{{-- Datos del estudiante --}}
<div class="prt-card" style="margin-bottom:1rem;">
    <div class="prt-card-header" style="background:linear-gradient(135deg,#5b21b6,#7c3aed);border-radius:10px 10px 0 0;padding:.7rem 1rem;">
        <i class="bi bi-person-fill" style="color:#fff;font-size:1rem;"></i>
        <h3 style="color:#fff;margin:0;font-size:.9rem;">Datos del Estudiante</h3>
    </div>
    <div class="bv-student-grid" style="padding:.85rem 1rem;">
        <div>
            <div class="bv-data-label" style="font-size:.68rem;font-weight:700;color:#2563eb;text-transform:uppercase;letter-spacing:.05em;">Nombre</div>
            <div class="bv-data-value" style="font-size:.88rem;font-weight:700;color:#1d4ed8;">
                {{ $matricula->estudiante?->apellidos }}, {{ $matricula->estudiante?->nombres }}
            </div>
        </div>
        <div>
            <div class="bv-data-label" style="font-size:.68rem;font-weight:700;color:#2563eb;text-transform:uppercase;letter-spacing:.05em;">Nº Matrícula</div>
            <div class="bv-data-value" style="font-size:.85rem;font-weight:700;color:#2563eb;font-family:monospace;">
                {{ $matricula->estudiante?->numero_matricula ?? '—' }}
            </div>
        </div>
        <div>
            <div class="bv-data-label" style="font-size:.68rem;font-weight:700;color:#2563eb;text-transform:uppercase;letter-spacing:.05em;">Grupo</div>
            <div class="bv-data-neutral" style="font-size:.85rem;color:#374151;">{{ $asignacion->grupo?->nombre_completo ?? '—' }}</div>
        </div>
        <div>
            <div class="bv-data-label" style="font-size:.68rem;font-weight:700;color:#2563eb;text-transform:uppercase;letter-spacing:.05em;">Año Escolar</div>
            <div class="bv-data-neutral" style="font-size:.85rem;color:#374151;">{{ $schoolYear?->nombre ?? '—' }}</div>
        </div>
    </div>
</div>

{{-- Tabla de calificaciones --}}
<div class="prt-card">
    <div class="prt-card-header">
        <i class="bi bi-journal-check" style="color:#5b21b6;font-size:1rem;"></i>
        <h3>Calificaciones por Materia</h3>
    </div>

    @if(count($tablaNotas) === 0)
        <div style="padding:2rem;text-align:center;color:#64748b;font-size:.85rem;">
            <i class="bi bi-inbox" style="font-size:2rem;display:block;margin-bottom:.5rem;color:#94a3b8;"></i>
            No hay calificaciones registradas para este estudiante en tus materias.
        </div>
    @else
    <div style="overflow-x:auto;">
        <table class="bol-table">
            <thead>
                <tr>
                    <th class="th-asig" style="text-align:left;">Materia</th>
                    @foreach($periodos as $p)
                        <th>P{{ $p->numero }}</th>
                    @endforeach
                    <th>Promedio</th>
                    <th>Situación</th>
                </tr>
            </thead>
            <tbody>
            @foreach($tablaNotas as $fila)
            <tr>
                <td style="font-weight:600;color:#1e293b;">{{ $fila['asignatura'] }}</td>
                @foreach($periodos as $p)
                @php $pv = $fila['periodos'][$p->id] ?? null; @endphp
                <td style="text-align:center;">
                    @if($pv !== null)
                        <span class="nbadge {{ $pv >= 70 ? 'nb-ok' : 'nb-mal' }}">{{ number_format($pv, 0) }}</span>
                    @else
                        <span class="nbadge nb-nd">—</span>
                    @endif
                </td>
                @endforeach
                <td style="text-align:center;">
                    @if($fila['promedio'] !== null)
                        <span class="nbadge {{ $fila['promedio'] >= 70 ? 'nb-final-ok' : 'nb-final-mal' }}">
                            {{ number_format($fila['promedio'], 1) }}
                        </span>
                    @else
                        <span class="nbadge nb-final-nd">—</span>
                    @endif
                </td>
                <td style="text-align:center;">
                    @if($fila['situacion'] === 'A')
                        <span class="sit-badge sit-a">Aprobado</span>
                    @elseif($fila['situacion'] === 'R')
                        <span class="sit-badge sit-r">Reprobado</span>
                    @else
                        <span style="color:#94a3b8;font-size:.78rem;">—</span>
                    @endif
                </td>
            </tr>
            @endforeach
            </tbody>
        </table>
    </div>

    {{-- Resumen --}}
    @php
        $promedioGeneral = collect($tablaNotas)->pluck('promedio')->filter()->avg();
        $aprobadas = collect($tablaNotas)->filter(fn($f) => ($f['promedio'] ?? 0) >= 70)->count();
        $reprobadas = collect($tablaNotas)->filter(fn($f) => $f['promedio'] !== null && $f['promedio'] < 70)->count();
    @endphp
    <div class="bv-summary" style="padding:.75rem 1rem;border-top:1px solid #f1f5f9;display:grid;grid-template-columns:repeat(3,1fr);gap:.5rem;">
        <div style="text-align:center;">
            <div style="font-size:1.1rem;font-weight:800;color:#15803d;">{{ $aprobadas }}</div>
            <div style="font-size:.68rem;color:#16a34a;">Materias aprobadas</div>
        </div>
        <div style="text-align:center;">
            <div style="font-size:1.1rem;font-weight:800;color:#dc2626;">{{ $reprobadas }}</div>
            <div style="font-size:.68rem;color:#dc2626;">Materias reprobadas</div>
        </div>
        <div style="text-align:center;">
            <div style="font-size:1.1rem;font-weight:800;color:#1d4ed8;">
                {{ $promedioGeneral ? number_format($promedioGeneral, 1) : '—' }}
            </div>
            <div style="font-size:.68rem;color:#1d4ed8;">Promedio general</div>
        </div>
    </div>
    @endif
</div>

{{-- ── Sección MINERD CE/IL ─────────────────────────────────────────── --}}
@if(!empty($minerdData) && $minerdData['tieneEvaluaciones'])
@php
    $mCiclo    = $minerdData['ciclo'];
    $mAsigs    = $minerdData['asignaciones'];
    $mEvalMap  = $minerdData['evalMap'];
    $mEsPrimer = $mCiclo === 'primer_ciclo';
    $mUmbral   = $mEsPrimer ? 2.5 : 65;

    $mPromPer = function(int $asigId, $ces, int $pId) use ($mEvalMap): ?float {
        $vals = [];
        foreach ($ces as $ce) {
            $ils = $ce->indicadoresActivos ?? collect();
            if ($ils->isNotEmpty()) {
                foreach ($ils as $il) {
                    $v = $mEvalMap[$asigId]["il_{$il->id}"][$pId] ?? null;
                    if ($v !== null) $vals[] = (float)$v;
                }
            } else {
                $v = $mEvalMap[$asigId]["ce_{$ce->id}"][$pId] ?? null;
                if ($v !== null) $vals[] = (float)$v;
            }
        }
        return count($vals) ? round(array_sum($vals)/count($vals), 2) : null;
    };

    $mPromAsig = function(int $asigId, $ces) use ($mEvalMap): ?float {
        $vals = [];
        foreach ($ces as $ce) {
            $ils = $ce->indicadoresActivos ?? collect();
            if ($ils->isNotEmpty()) {
                foreach ($ils as $il) {
                    foreach ($mEvalMap[$asigId]["il_{$il->id}"] ?? [] as $v) {
                        if ($v !== null) $vals[] = (float)$v;
                    }
                }
            } else {
                foreach ($mEvalMap[$asigId]["ce_{$ce->id}"] ?? [] as $v) {
                    if ($v !== null) $vals[] = (float)$v;
                }
            }
        }
        return count($vals) ? round(array_sum($vals)/count($vals), 2) : null;
    };

    $mNbCls = function($v) use ($mUmbral): string {
        if ($v === null) return 'nb-nd';
        return (float)$v >= $mUmbral ? 'nb-ok' : 'nb-mal';
    };

    $mFmt = function($v) use ($mEsPrimer): string {
        if ($v === null) return '—';
        return $mEsPrimer ? number_format((float)$v, 1) : number_format((float)$v, 0);
    };

    $mEscala = function($v) use ($mEsPrimer, $mUmbral): string {
        if ($v === null) return '—';
        $f = (float)$v;
        if ($mEsPrimer) return match(true) {
            $f >= 3.5 => 'Avanzado',
            $f >= 2.5 => 'Logrado',
            $f >= 1.5 => 'En proceso',
            default   => 'Inicial',
        };
        return $f >= $mUmbral ? 'Aprobado' : 'No aprobado';
    };

    $mAsigsCon = $mAsigs->filter(fn($a) => ($a->asignatura->competenciasActivas ?? collect())->isNotEmpty());
@endphp

<div class="prt-card" style="margin-top:1rem;">
    <div class="prt-card-header" style="background:linear-gradient(135deg,#4f46e5,#7c3aed);border-radius:10px 10px 0 0;padding:.7rem 1rem;display:flex;align-items:center;gap:.6rem;flex-wrap:wrap;">
        <i class="bi bi-table" style="color:#fff;font-size:1rem;"></i>
        <h3 style="color:#fff;margin:0;font-size:.9rem;">Registro MINERD — Competencias Específicas</h3>
        <span style="background:rgba(255,255,255,.18);color:#fff;font-size:.68rem;font-weight:700;
                     padding:.15rem .55rem;border-radius:20px;">
            {{ $mEsPrimer ? 'Primer Ciclo · Escala 1–4' : 'Segundo Ciclo · 0–100' }}
        </span>
    </div>

    <div style="padding:.4rem 1rem;font-size:.7rem;color:#6b7280;border-bottom:1px solid #f1f5f9;
                display:flex;flex-wrap:wrap;gap:.35rem;align-items:center;">
        <span style="font-weight:700;">Escala:</span>
        @if($mEsPrimer)
            <span style="background:#fee2e2;color:#991b1b;padding:.1rem .38rem;border-radius:4px;font-weight:700;">1 Inicial</span>
            <span style="background:#fef9c3;color:#92400e;padding:.1rem .38rem;border-radius:4px;font-weight:700;">2 En proceso</span>
            <span style="background:#dbeafe;color:#1e40af;padding:.1rem .38rem;border-radius:4px;font-weight:700;">3 Logrado</span>
            <span style="background:#dcfce7;color:#15803d;padding:.1rem .38rem;border-radius:4px;font-weight:700;">4 Avanzado</span>
            <span style="color:#94a3b8;">· Aprobatorio ≥ 2.5</span>
        @else
            <span class="nbadge nb-ok" style="font-size:.68rem;">≥ 65</span> Aprobado &nbsp;
            <span class="nbadge nb-mal" style="font-size:.68rem;">< 65</span> Reprobado
        @endif
    </div>

    @if($mAsigsCon->isEmpty())
        <div style="padding:1.5rem;text-align:center;color:#94a3b8;font-size:.83rem;">
            <i class="bi bi-info-circle me-1"></i>Las materias no tienen Competencias Específicas configuradas.
        </div>
    @else
    <div style="overflow-x:auto;">
        <table class="bol-table">
            <thead>
                <tr>
                    <th class="th-asig">Materia (CE/IL)</th>
                    @foreach($periodos as $p)
                        <th>P{{ $p->numero }}</th>
                    @endforeach
                    <th>Prom.</th>
                    <th>{{ $mEsPrimer ? 'Escala' : 'Indicador' }}</th>
                </tr>
            </thead>
            <tbody>
            @foreach($mAsigsCon as $asig)
                @php
                    $ces = $asig->asignatura->competenciasActivas ?? collect();
                    $pm  = $mPromAsig($asig->id, $ces);
                @endphp
                <tr>
                    <td style="font-weight:600;color:#1e293b;">{{ $asig->asignatura?->nombre }}</td>
                    @foreach($periodos as $p)
                        @php $vp = $mPromPer($asig->id, $ces, $p->id); @endphp
                        <td style="text-align:center;">
                            <span class="nbadge {{ $mNbCls($vp) }}">{{ $mFmt($vp) }}</span>
                        </td>
                    @endforeach
                    <td style="text-align:center;">
                        <span class="nbadge {{ $pm !== null ? 'nb-final-'.($mNbCls($pm) === 'nb-ok' ? 'ok' : ($mNbCls($pm) === 'nb-mal' ? 'mal' : 'nd')) : 'nb-final-nd' }}">
                            {{ $mFmt($pm) }}
                        </span>
                    </td>
                    <td style="text-align:center;">
                        @if($pm !== null)
                            <span class="sit-badge {{ (float)$pm >= $mUmbral ? 'sit-a' : 'sit-r' }}">
                                {{ $mEscala($pm) }}
                            </span>
                        @else
                            <span style="color:#94a3b8;font-size:.78rem;">—</span>
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>

    @php
        $mPromsGral = $mAsigsCon->map(fn($a) => $mPromAsig($a->id, $a->asignatura->competenciasActivas ?? collect()))->filter();
        $mTotalGral = $mPromsGral->count() ? round($mPromsGral->avg(), 2) : null;
    @endphp
    @if($mTotalGral !== null)
    <div style="padding:.65rem 1rem;border-top:1px solid #f1f5f9;display:flex;align-items:center;gap:.75rem;flex-wrap:wrap;">
        <span style="font-size:.78rem;font-weight:700;color:#64748b;">Promedio MINERD:</span>
        <span class="nbadge nb-final-{{ (float)$mTotalGral >= $mUmbral ? 'ok' : 'mal' }}" style="font-size:.9rem;">
            {{ $mFmt($mTotalGral) }}
        </span>
        <span class="sit-badge {{ (float)$mTotalGral >= $mUmbral ? 'sit-a' : 'sit-r' }}">
            {{ $mEscala($mTotalGral) }}
        </span>
        <span style="margin-left:auto;font-size:.7rem;color:#94a3b8;">
            {{ $mAsigsCon->count() }} materia(s)
        </span>
    </div>
    @endif
    @endif
</div>
@endif

@endsection
