@extends('layouts.portal')
@section('page-title', 'Boletines — ' . ($asignacion->asignatura?->nombre ?? ''))
@section('portal-name', 'Portal Docente')

@section('sidebar')
    @include('portal.docente._sidebar_clase', ['activeKey' => 'boletines'])
@endsection

@section('bottom-nav')
    <a href="{{ route('portal.docente.dashboard') }}" class="prt-nav-item">
        <i class="bi bi-house-fill"></i>Inicio
    </a>
    <a href="{{ route('portal.docente.asistencia', $asignacion) }}" class="prt-nav-item">
        <i class="bi bi-calendar-check"></i>Asistencia
    </a>
    <a href="{{ route('portal.docente.calificaciones', $asignacion) }}" class="prt-nav-item">
        <i class="bi bi-journal-check"></i>Notas
    </a>
    <a href="{{ route('portal.docente.boletines', $asignacion) }}" class="prt-nav-item active">
        <i class="bi bi-file-earmark-text"></i>Boletines
    </a>
@endsection

@push('styles')
<style>
.bol-row { border-bottom: 1px solid #f1f5f9; transition: background .1s; }
.bol-row:hover { background: #f8faff; }
.per-badge {
    display: inline-block; min-width: 40px; text-align: center;
    font-weight: 700; font-size: .82rem; border-radius: 7px; padding: .18rem .35rem;
}
.per-aprobado  { background: #dcfce7; color: #15803d; }
.per-reprobado { background: #fee2e2; color: #dc2626; }
.per-vacio     { background: #f1f5f9; color: #94a3b8; }
.final-aprobado  { background: #dcfce7; color: #15803d; font-weight: 800; }
.final-reprobado { background: #fee2e2; color: #dc2626; font-weight: 800; }
.final-vacio     { background: #f1f5f9; color: #94a3b8; }
.col-head {
    padding: .5rem .4rem; text-align: center;
    font-size: .7rem; font-weight: 700; letter-spacing: .06em;
    text-transform: uppercase; color: #2563eb; white-space: nowrap;
}
/* Dark mode */
[data-theme="dark"] .bol-row { border-bottom-color: #334155; }
[data-theme="dark"] .bol-row:hover { background: #1e3a5f; }
[data-theme="dark"] .col-head { color: #60a5fa; }
[data-theme="dark"] .per-aprobado,
[data-theme="dark"] .final-aprobado  { background: #052e16; color: #4ade80; }
[data-theme="dark"] .per-reprobado,
[data-theme="dark"] .final-reprobado { background: #1c0000; color: #f87171; }
[data-theme="dark"] .per-vacio,
[data-theme="dark"] .final-vacio { background: #1e293b; color: #475569; }
[data-theme="dark"] .bol-thead { background: #1a2640 !important; border-bottom-color: #334155 !important; }
[data-theme="dark"] .bol-info-banner { background: #1e1040 !important; color: #a78bfa !important; }
</style>
@endpush

@section('content')

{{-- Header --}}
<div style="display:flex;align-items:center;gap:.75rem;margin-bottom:1rem;flex-wrap:wrap;">
    <a href="{{ route('portal.docente.dashboard') }}" class="btn-back"
       style="background:#f1f5f9;color:#374151;border-radius:8px;padding:.4rem .85rem;font-size:.8rem;text-decoration:none;display:flex;align-items:center;gap:.4rem;">
        <i class="bi bi-arrow-left"></i>Volver
    </a>
    <div style="flex:1;">
        <h1 style="font-size:1rem;font-weight:800;margin:0;">
            <i class="bi bi-file-earmark-text-fill" style="color:#5b21b6;"></i>
            Boletines — {{ $asignacion->asignatura?->nombre }}
        </h1>
        <div class="dm-text-muted" style="font-size:.75rem;color:#64748b;">
            {{ $asignacion->grupo?->nombre_completo ?? '—' }}
            · {{ $matriculas->count() }} estudiante(s)
            @if($schoolYear) · {{ $schoolYear->nombre }} @endif
        </div>
    </div>
    <a href="{{ route('portal.docente.boletines.zip', $asignacion) }}"
       style="background:#0f766e;color:#fff;border-radius:8px;padding:.4rem .85rem;font-size:.78rem;font-weight:700;text-decoration:none;display:flex;align-items:center;gap:.4rem;white-space:nowrap;flex-shrink:0;">
        <i class="bi bi-file-zip"></i>ZIP
    </a>
    <a href="{{ route('portal.docente.acta.pdf', $asignacion) }}" target="_blank"
       style="background:#1e3a6e;color:#fff;border-radius:8px;padding:.4rem .85rem;font-size:.78rem;font-weight:700;text-decoration:none;display:flex;align-items:center;gap:.4rem;white-space:nowrap;flex-shrink:0;">
        <i class="bi bi-file-earmark-spreadsheet"></i>Acta PDF
    </a>
</div>

{{-- Estadísticas de la materia --}}
@if(!empty($statsMateria))
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(130px,1fr));gap:.7rem;margin-bottom:1rem;">
    <div style="background:#ede9fe;border-radius:10px;padding:.75rem;text-align:center;">
        <div style="font-size:1.4rem;font-weight:900;color:#5b21b6;">{{ $statsMateria['promedio'] }}</div>
        <div style="font-size:.68rem;font-weight:600;color:#6b21a8;text-transform:uppercase;letter-spacing:.05em;">Promedio</div>
    </div>
    <div style="background:#dcfce7;border-radius:10px;padding:.75rem;text-align:center;">
        <div style="font-size:1.4rem;font-weight:900;color:#15803d;">{{ $statsMateria['aprobados'] }}</div>
        <div style="font-size:.68rem;font-weight:600;color:#166534;text-transform:uppercase;letter-spacing:.05em;">Aprobados</div>
    </div>
    <div style="background:#fee2e2;border-radius:10px;padding:.75rem;text-align:center;">
        <div style="font-size:1.4rem;font-weight:900;color:#dc2626;">{{ $statsMateria['reprobados'] }}</div>
        <div style="font-size:.68rem;font-weight:600;color:#991b1b;text-transform:uppercase;letter-spacing:.05em;">Reprobados</div>
    </div>
    <div style="background:#f0fdf4;border-radius:10px;padding:.75rem;text-align:center;">
        <div style="font-size:1.4rem;font-weight:900;color:#065f46;">{{ $statsMateria['tasa'] }}%</div>
        <div style="font-size:.68rem;font-weight:600;color:#065f46;text-transform:uppercase;letter-spacing:.05em;">Tasa aprob.</div>
    </div>
    <div style="background:#f1f5f9;border-radius:10px;padding:.75rem;text-align:center;">
        <div style="font-size:1rem;font-weight:800;color:#374151;">{{ $statsMateria['min'] }} – {{ $statsMateria['max'] }}</div>
        <div style="font-size:.68rem;font-weight:600;color:#6b7280;text-transform:uppercase;letter-spacing:.05em;">Min – Max</div>
    </div>
</div>
@endif

{{-- Info --}}
<div class="bol-info-banner" style="background:#ede9fe;border-radius:10px;padding:.7rem 1rem;margin-bottom:1rem;font-size:.8rem;color:#5b21b6;display:flex;align-items:center;gap:.5rem;">
    <i class="bi bi-info-circle-fill"></i>
    Se muestran las calificaciones de <strong>{{ $asignacion->asignatura?->nombre }}</strong> por período.
    Haz clic en <strong>Ver Boletín</strong> para ver el resumen completo de todas tus materias para ese estudiante.
</div>

{{-- Tabla --}}
<div class="prt-card">
    <div class="prt-card-header">
        <i class="bi bi-list-ul" style="color:#5b21b6;font-size:1rem;"></i>
        <h3>Listado de Estudiantes</h3>
    </div>

    <div style="overflow-x:auto;">
        <table style="width:100%;border-collapse:collapse;font-size:.82rem;">
            <thead>
                <tr class="bol-thead" style="background:#f8faff;border-bottom:1.5px solid #e2e8f0;">
                    <th style="padding:.5rem .85rem;text-align:left;" class="col-head">#</th>
                    <th style="padding:.5rem .85rem;text-align:left;min-width:160px;" class="col-head">Estudiante</th>
                    @foreach($periodos as $p)
                    <th class="col-head">P{{ $p->numero }}</th>
                    @endforeach
                    <th class="col-head">Final</th>
                    <th class="col-head">Acción</th>
                </tr>
            </thead>
            <tbody>
            @foreach($matriculas as $i => $mat)
            @php
                if ($esTecnica) {
                    // $calificaciones[matricula_id][periodo_id] = Calificacion
                    $calPeriodos = $calificaciones->get($mat->id, collect());
                    $notas       = $calPeriodos->pluck('nota_final')->filter();
                    $nf          = $notas->isNotEmpty() ? round($notas->avg(), 0) : null;
                } else {
                    $cal = $calificaciones->get($mat->id);
                    $nf  = $cal !== null
                        ? (int)round($cal->nota_extraordinaria ?? $cal->nota_completiva ?? $cal->nota_final ?? 0)
                        : null;
                }
            @endphp
            <tr class="bol-row">
                <td style="padding:.55rem .85rem;color:#2563eb;font-size:.75rem;font-weight:700;">{{ $i + 1 }}</td>
                <td style="padding:.55rem .85rem;">
                    <div class="dm-text-primary" style="font-weight:700;font-size:.85rem;">
                        {{ $mat->estudiante?->apellidos }}, {{ $mat->estudiante?->nombres }}
                    </div>
                    <div style="font-size:.68rem;color:#60a5fa;font-family:monospace;">
                        {{ $mat->estudiante?->numero_matricula }}
                    </div>
                </td>
                @foreach($periodos as $p)
                @php
                    if ($esTecnica) {
                        $calP = ($calPeriodos ?? collect())->get($p->id);
                        $pv   = $calP?->nota_final !== null ? (int)round($calP->nota_final) : null;
                    } else {
                        $n = $p->numero;
                        // Usar avg_compC_pN (CF = P + min(R, 100-P)) actualizado por recalcularPromedios()
                        // Fallback dinámico si la caché aún no fue calculada
                        $vals = [];
                        for ($ci = 1; $ci <= 4; $ci++) {
                            $cv = $cal?->{"avg_comp{$ci}_p{$n}"};
                            if ($cv === null) {
                                $pb = $cal?->{"comp{$ci}_p{$n}"};
                                if ($pb !== null) {
                                    $rv = $cal?->{"comp{$ci}_r{$n}"};
                                    $pb = (float)$pb;
                                    $cv = ($rv !== null && $pb < 70)
                                        ? round($pb + min((float)$rv, max(0.0, 100.0 - $pb)), 2)
                                        : round($pb, 2);
                                }
                            }
                            if ($cv !== null) $vals[] = (float)$cv;
                        }
                        $pv = count($vals) > 0 ? (int)round(array_sum($vals) / count($vals)) : null;
                    }
                @endphp
                <td style="padding:.45rem .3rem;text-align:center;">
                    @if($pv !== null)
                        <span class="per-badge {{ $pv >= 70 ? 'per-aprobado' : 'per-reprobado' }}">{{ $pv }}</span>
                    @else
                        <span class="per-badge per-vacio">—</span>
                    @endif
                </td>
                @endforeach
                <td style="padding:.45rem .5rem;text-align:center;">
                    @if($nf !== null)
                        <span class="per-badge final-{{ $nf >= 70 ? 'aprobado' : 'reprobado' }}">{{ $nf }}</span>
                    @else
                        <span class="per-badge final-vacio">—</span>
                    @endif
                </td>
                <td style="padding:.45rem .5rem;text-align:center;">
                    <a href="{{ route('portal.docente.boletin.ver', [$asignacion, $mat]) }}"
                       style="background:#5b21b6;color:#fff;border-radius:7px;padding:.28rem .7rem;font-size:.73rem;font-weight:700;text-decoration:none;display:inline-flex;align-items:center;gap:.3rem;">
                        <i class="bi bi-eye-fill"></i>Ver
                    </a>
                </td>
            </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>

@endsection
