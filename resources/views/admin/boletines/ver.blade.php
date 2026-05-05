@extends('layouts.admin')
@section('page-title', 'Boletín — '.optional($matricula->estudiante)->nombre_completo)

@push('styles')
<style>
/* ── Container ──────────────────────────────────────────────── */
.boletin-container {
    max-width: 960px;
    margin: 0 auto;
    background: #fff;
    border-radius: 14px;
    box-shadow: 0 4px 32px rgba(30,58,110,.13);
    overflow: visible;
}

/* ── MINERD Header (table-based, print-safe) ─────────────────── */
.minerd-header-table {
    border: 2px solid #1e3a6e;
    background: #fff;
    color: #1e293b;
}
.minerd-top-stripe {
    background: #1e3a6e;
    color: #fff;
    text-align: center;
    font-size: .68rem;
    font-weight: 700;
    letter-spacing: .15em;
    text-transform: uppercase;
    padding: 4px 0;
}
.minerd-logo-cell {
    border-right: 1px solid #c7d6f0;
}
.minerd-school-name {
    font-size: 1.05rem;
    font-weight: 900;
    color: #1e3a6e;
    line-height: 1.2;
}
.minerd-school-sub {
    font-size: .73rem;
    color: #475569;
    margin-top: 2px;
}
.minerd-school-lema {
    font-size: .68rem;
    color: #64748b;
    font-style: italic;
    margin-top: 2px;
}
.minerd-school-contact {
    font-size: .65rem;
    color: #64748b;
    margin-top: 3px;
}
.minerd-doc-info {
    border-left: 1px solid #c7d6f0;
    font-size: .72rem;
    color: #1e293b;
    line-height: 1.4;
}
.minerd-doc-label {
    font-size: .6rem;
    text-transform: uppercase;
    letter-spacing: .08em;
    color: #6b7280;
}

/* Title bar */
.minerd-title-bar {
    background: #c0392b;
    color: #fff;
    text-align: center;
    padding: .55rem 0;
    font-size: .88rem;
    font-weight: 800;
    letter-spacing: .12em;
    text-transform: uppercase;
}

/* ── Student info ────────────────────────────────────────────── */
.student-section {
    padding: 1.25rem 1.75rem .75rem;
    border-bottom: 1px solid #e5e7eb;
    display: flex;
    gap: 1.25rem;
    align-items: flex-start;
}
.student-photo {
    flex-shrink: 0;
    width: 80px; height: 95px;
    border-radius: 8px;
    border: 2px solid #c7d6f0;
    object-fit: cover;
    background: #f0f4f8;
    display: flex; align-items: center; justify-content: center;
    overflow: hidden;
}
.student-photo img { width: 100%; height: 100%; object-fit: cover; }
.student-photo .no-photo { font-size: .72rem; color: #9ca3af; text-align: center; padding: .5rem; }
.student-fields { flex: 1; display: grid; grid-template-columns: 1fr 1fr 1fr; gap: .5rem 1.5rem; }
.sfield-label { font-size: .68rem; font-weight: 700; color: #6b7280; text-transform: uppercase; letter-spacing: .07em; display: block; }
.sfield-value { font-size: .9rem; font-weight: 700; color: #1e293b; display: block; margin-top: 1px; }

/* ── Section title ───────────────────────────────────────────── */
.section-heading {
    font-size: .72rem; font-weight: 800;
    letter-spacing: .13em; text-transform: uppercase;
    color: #1e3a6e;
    border-bottom: 2px solid #1e3a6e;
    padding: 1rem 1.75rem .35rem;
    margin: 0;
    display: flex; align-items: center; gap: .5rem;
}

/* ── Grades table ────────────────────────────────────────────── */
.notas-wrapper { overflow-x: auto; padding: 0 1.75rem 1rem; }
.notas-table {
    width: 100%;
    border-collapse: collapse;
    font-size: .83rem;
    min-width: 550px;
}
.notas-table thead th {
    background: #1e3a6e;
    color: #fff;
    padding: .55rem .6rem;
    font-size: .73rem;
    font-weight: 700;
    text-align: center;
    border: 1px solid rgba(255,255,255,.15);
    white-space: nowrap;
}
.notas-table thead th:first-child { text-align: left; min-width: 160px; }
.notas-table tbody td {
    padding: .45rem .6rem;
    border: 1px solid #e5e7eb;
    text-align: center;
    vertical-align: middle;
}
.notas-table tbody td:first-child { text-align: left; font-weight: 600; }
.notas-table tbody tr:nth-child(even) td { background: #f9fafb; }
.notas-table tbody tr:hover td { background: #f0f4ff; }

/* Grade cell colors */
.nc-exc { background: #dcfce7 !important; color: #15803d; font-weight: 700; }
.nc-bue { background: #dbeafe !important; color: #1d4ed8; font-weight: 700; }
.nc-pro { background: #fef3c7 !important; color: #92400e; font-weight: 700; }
.nc-ins { background: #fee2e2 !important; color: #991b1b; font-weight: 700; }
.nc-vac { color: #9ca3af; }

/* Indicator badge */
.ind-badge {
    display: inline-block;
    padding: .22em .65em;
    border-radius: 20px;
    font-size: .72rem;
    font-weight: 700;
    white-space: nowrap;
}
.ind-e { background: #dcfce7; color: #15803d; }
.ind-b { background: #dbeafe; color: #1d4ed8; }
.ind-p { background: #fef3c7; color: #92400e; }
.ind-i { background: #fee2e2; color: #991b1b; }
.ind-v { background: #f3f4f6; color: #9ca3af; }

/* Promedio row */
.prom-row td {
    background: #eef3fb !important;
    font-weight: 800;
    color: #1e3a6e;
    border-top: 2px solid #1e3a6e !important;
    font-size: .9rem;
}
.prom-box {
    display: inline-block;
    background: #1e3a6e;
    color: #fff;
    border-radius: 6px;
    padding: .2em .75em;
    font-size: .95rem;
    font-weight: 800;
}

/* ── Attendance ──────────────────────────────────────────────── */
.asist-wrapper { padding: 0 1.75rem 1rem; }
.asist-table {
    width: 100%; border-collapse: collapse; font-size: .82rem;
}
.asist-table th, .asist-table td {
    padding: .45rem .65rem;
    border: 1px solid #e5e7eb;
    text-align: center;
}
.asist-table thead th { background: #1e3a6e; color: #fff; font-size: .73rem; }
.asist-table thead th:first-child { text-align: left; }
.asist-table tbody td:first-child { text-align: left; font-weight: 600; background: #f8faff; }
.pct-good { color: #15803d; font-weight: 800; }
.pct-warn { color: #92400e; font-weight: 800; }
.pct-bad  { color: #991b1b; font-weight: 800; }
.asist-total { background: #eef3fb !important; font-weight: 700; border-top: 2px solid #1e3a6e !important; }

/* ── Observations ────────────────────────────────────────────── */
.obs-section { padding: 0 1.75rem 1rem; }
.obs-box {
    border: 1.5px dashed #d1d5db;
    border-radius: 8px;
    padding: .85rem 1rem;
    min-height: 60px;
    font-size: .85rem;
    color: #4b5563;
    line-height: 1.6;
    background: #fafafa;
}
.obs-item { margin-bottom: .4rem; }
.obs-materia { font-weight: 700; color: #1e3a6e; }

/* ── Indicators ─────────────────────────────────────────────── */
.eval-section { padding: 0 1.75rem 1rem; }
.eval-grid { display: grid; grid-template-columns: 1fr 1fr; gap: .4rem; }
.eval-item {
    display: flex; align-items: center; gap: .6rem;
    background: #f9fafb; border: 1px solid #e5e7eb;
    border-radius: 6px; padding: .4rem .7rem; font-size: .8rem;
}

/* ── Signatures ──────────────────────────────────────────────── */
.firma-section {
    padding: 1.5rem 1.75rem 1.25rem;
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 1.5rem;
    border-top: 1px solid #e5e7eb;
}
.firma-card { text-align: center; }
.firma-space { height: 50px; }
.firma-sello {
    width: 65px; height: 65px;
    border: 2px dashed #9ca3af;
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    margin: 0 auto;
    font-size: .65rem; color: #9ca3af; text-align: center;
    line-height: 1.3;
}
.firma-line { border-top: 1.5px solid #374151; padding-top: .3rem; font-weight: 700; font-size: .82rem; color: #1e293b; }
.firma-rol  { font-size: .72rem; color: #6b7280; margin-top: 2px; }

/* ── Footer ─────────────────────────────────────────────────── */
.boletin-footer {
    background: #f8faff;
    border-top: 1px solid #e5e7eb;
    text-align: center;
    padding: .6rem 1.75rem;
    font-size: .73rem;
    color: #9ca3af;
}

/* ── Action bar (no-print) ───────────────────────────────────── */
.action-bar {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,.06);
    padding: .85rem 1.25rem;
    margin-bottom: 1.25rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: .5rem;
}

@media print {
    /* ── Forzar colores e imágenes ── */
    * {
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
        color-adjust: exact !important;
    }

    /* ── Ocultar chrome del admin ── */
    .sidebar,
    .topbar,
    .sidebar-overlay,
    .no-print,
    #sge-toast-container,
    #nprogress-bar { display: none !important; }

    /* ── Resetear layout ── */
    body {
        background: #fff !important;
        margin: 0 !important;
        padding: 0 !important;
    }
    .main-content {
        margin-left: 0 !important;
        margin-top: 0 !important;
        padding: 0.5rem !important;
        min-height: unset !important;
    }

    /* ── Boletín limpio ── */
    .boletin-container {
        box-shadow: none !important;
        border-radius: 0 !important;
        max-width: 100% !important;
        margin: 0 !important;
    }

    /* ── Evitar cortes de página en medio de secciones ── */
    .section-heading,
    .asist-wrapper,
    .obs-section,
    .eval-section,
    .firma-section { page-break-inside: avoid; }

    /* ── Imágenes y fondos ── */
    img { max-width: 100% !important; }

    /* ── Página ── */
    @page {
        size: letter portrait;
        margin: 1.2cm 1.5cm;
    }
}
@media (max-width: 600px) {
    .student-fields { grid-template-columns: 1fr 1fr; }
    .firma-section  { grid-template-columns: 1fr; }
    .eval-grid      { grid-template-columns: 1fr; }
}
</style>
@endpush

@section('content')

{{-- ── Action bar ──────────────────────────────────────────────────────── --}}
@php
    $est = $matricula->estudiante;
    $ranking = $rankingGrupo ?? [];
    $telRepresentante = $matricula->representantes()->first()?->telefono
        ?? $matricula->estudiante?->tutor_telefono ?? null;
    $nombreEst = $est?->nombres . ' ' . $est?->apellidos;
    $msgWA = urlencode("📋 *Boletín de Calificaciones*\n\nEstudiante: {$nombreEst}\nPeríodo: {$periodo->nombre}\nGrupo: {$matricula->grupo?->nombre_completo}\nPromedio: " . ($promedioGeneral ? number_format($promedioGeneral,1) : '—') . "\n\n_" . ($boletinConfig?->nombre_institucion ?? 'Centro Educativo') . "_");
    $waUrl = $telRepresentante
        ? 'https://wa.me/' . preg_replace('/\D+/', '', $telRepresentante) . '?text=' . $msgWA
        : null;
@endphp
<div class="action-bar no-print">
    <div class="d-flex align-items-center gap-3 flex-wrap">
        <a href="{{ route('admin.boletines.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Volver
        </a>
        <div>
            <span class="fw-bold" style="color:var(--primary);">{{ $nombreEst }}</span>
            <span class="text-muted ms-2" style="font-size:.82rem;">{{ optional($matricula->grupo)->nombre_completo }}</span>
        </div>
        {{-- Badge ranking --}}
        @if(!empty($ranking['puesto']))
        <span class="badge rounded-pill" style="background:#1e3a6e;color:#fff;font-size:.78rem;padding:.4em .9em;">
            <i class="bi bi-trophy me-1" style="color:#fbbf24;"></i>
            Puesto {{ $ranking['puesto'] }} de {{ $ranking['total'] }}
        </span>
        @endif
    </div>
    <div class="d-flex gap-2 flex-wrap">
        @if(!$vistaDocente)
        <button class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#modalObservacion">
            <i class="bi bi-chat-left-text me-1"></i>Observación
        </button>
        @endif
        @if($waUrl)
        <a href="{{ $waUrl }}" target="_blank" class="btn btn-sm" style="background:#25D366;color:#fff;border:none;">
            <i class="bi bi-whatsapp me-1"></i>WhatsApp
        </a>
        @endif
        <a href="{{ route('admin.boletines.pdf-anual', $matricula) }}" class="btn btn-sm btn-outline-primary">
            <i class="bi bi-file-earmark-bar-graph me-1"></i>PDF Anual
        </a>
        <button class="btn btn-sm btn-outline-secondary" onclick="window.print()">
            <i class="bi bi-printer me-1"></i>Imprimir
        </button>
        <a href="{{ route('admin.boletines.pdf', [$matricula, $periodo]) }}" class="btn btn-sm btn-danger">
            <i class="bi bi-file-earmark-pdf me-1"></i>PDF Período
        </a>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════════════════
     BOLETÍN
════════════════════════════════════════════════════════════════════════ --}}
<div class="boletin-container">

    {{-- ── 1. ENCABEZADO (tabla – compatible con impresión) ────────────── --}}
    <table class="minerd-header-table" style="width:100%;border-collapse:collapse;margin-bottom:0;">
        <tr>
            <td colspan="3" class="minerd-top-stripe">
                República Dominicana &nbsp;·&nbsp; Ministerio de Educación (MINERD)
            </td>
        </tr>
        <tr>
            {{-- Logo --}}
            <td class="minerd-logo-cell" style="width:90px;text-align:center;vertical-align:middle;padding:8px;">
                @if($boletinConfig && $boletinConfig->logo)
                    <img src="{{ asset('storage/'.$boletinConfig->logo) }}"
                         alt="Logo" style="max-width:75px;max-height:75px;object-fit:contain;">
                @else
                    <div style="width:70px;height:70px;background:#e2e8f0;border-radius:50%;
                                display:flex;align-items:center;justify-content:center;
                                margin:auto;font-weight:700;font-size:1.1rem;color:#475569;">
                        PSA
                    </div>
                @endif
            </td>
            {{-- Centro --}}
            <td style="text-align:center;vertical-align:middle;padding:6px 10px;">
                <div class="minerd-school-name">
                    {{ ($boletinConfig && $boletinConfig->nombre_institucion)
                        ? $boletinConfig->nombre_institucion
                        : config('app.school_name', env('SCHOOL_NAME', 'Politécnico Salesiano')) }}
                </div>
                @if($boletinConfig && $boletinConfig->nivel_educativo)
                    <div class="minerd-school-sub">{{ $boletinConfig->nivel_educativo }}</div>
                @endif
                @if($boletinConfig && $boletinConfig->lema)
                    <div class="minerd-school-lema">"{{ $boletinConfig->lema }}"</div>
                @endif
                @php
                    $contactParts = array_filter([
                        $boletinConfig?->regional ? 'Regional '.$boletinConfig->regional : null,
                        $boletinConfig?->distrito  ? 'Distrito '.$boletinConfig->distrito  : null,
                        $boletinConfig?->municipio,
                        $boletinConfig?->telefono  ? 'Tel: '.$boletinConfig->telefono      : null,
                    ]);
                @endphp
                @if(count($contactParts))
                    <div class="minerd-school-contact">{{ implode(' · ', $contactParts) }}</div>
                @endif
            </td>
            {{-- Datos doc --}}
            <td class="minerd-doc-info" style="width:120px;text-align:right;vertical-align:middle;padding:8px 10px;">
                @if($boletinConfig && $boletinConfig->codigo)
                    <div><span class="minerd-doc-label">Código</span><br>
                    <strong>{{ $boletinConfig->codigo }}</strong></div>
                @endif
                @if($schoolYear)
                    <div style="margin-top:4px;"><span class="minerd-doc-label">Año Escolar</span><br>
                    <strong>{{ $schoolYear->nombre }}</strong></div>
                @endif
            </td>
        </tr>
    </table>

    <div class="minerd-title-bar">
        ✦ &nbsp; Boletín de Calificaciones &nbsp;·&nbsp; {{ $periodo->nombre }} &nbsp; ✦
    </div>

    {{-- ── 2. DATOS DEL ESTUDIANTE ─────────────────────────────────────── --}}
    <div class="student-section">
        <div class="student-photo">
            @if($matricula->estudiante && $matricula->estudiante->foto)
                <img src="{{ asset('storage/'.$matricula->estudiante->foto) }}" alt="foto">
            @else
                <div class="no-photo"><i class="bi bi-person" style="font-size:2rem;color:#cbd5e1;"></i></div>
            @endif
        </div>
        <div class="student-fields">
            <div>
                <span class="sfield-label">Nombre Completo</span>
                <span class="sfield-value">{{ optional($matricula->estudiante)->nombre_completo ?? '—' }}</span>
            </div>
            <div>
                <span class="sfield-label">No. Matrícula</span>
                <span class="sfield-value" style="font-family:monospace;">
                    {{ optional($matricula->estudiante)->numero_matricula ?? '#'.$matricula->id }}
                </span>
            </div>
            <div>
                <span class="sfield-label">Período Evaluado</span>
                <span class="sfield-value">{{ $periodo->nombre }} (P{{ $periodo->numero }})</span>
            </div>
            <div>
                <span class="sfield-label">Grado / Sección</span>
                <span class="sfield-value">{{ optional($matricula->grupo)->nombre_completo ?? '—' }}</span>
            </div>
            <div>
                <span class="sfield-label">Cédula</span>
                <span class="sfield-value" style="font-family:monospace;">{{ optional($matricula->estudiante)->cedula ?? '—' }}</span>
            </div>
            <div>
                <span class="sfield-label">Fecha de Nacimiento</span>
                <span class="sfield-value">
                    {{ optional($matricula->estudiante)->fecha_nacimiento
                        ? $matricula->estudiante->fecha_nacimiento->format('d/m/Y')
                        : '—' }}
                </span>
            </div>
        </div>
    </div>

    {{-- ── 3. TABLA DE CALIFICACIONES (MULTI-PERÍODO) ──────────────────── --}}
    <div class="section-heading">
        <i class="bi bi-journal-check"></i> Calificaciones por Período
        @if(isset($vistaDocente) && $vistaDocente)
        <span style="margin-left:.75rem;font-size:.7rem;font-weight:700;background:#dbeafe;color:#1d4ed8;padding:.2em .7em;border-radius:20px;text-transform:none;letter-spacing:0;">
            <i class="bi bi-eye-slash me-1"></i>Mostrando solo tus asignaturas publicadas
        </span>
        @endif
    </div>

    @if(empty($tablaNotas))
        <div style="padding:1rem 1.75rem;color:#9ca3af;font-size:.85rem;">
            No hay calificaciones registradas.
        </div>
    @else
    <div class="notas-wrapper">
        <table class="notas-table">
            <thead>
                <tr>
                    <th style="text-align:left;">Materia</th>
                    @foreach($periodos as $p)
                        <th>{{ $p->nombre_corto ?? 'P'.$p->numero }}</th>
                    @endforeach
                    <th style="background:#0f4c81;white-space:nowrap;">Progreso</th>
                    <th style="background:#c0392b;">Prom. Anual</th>
                    <th style="background:#c0392b;">Indicador</th>
                </tr>
            </thead>
            <tbody>
                @foreach($tablaNotas as $row)
                @php
                    $indCls = match($row['indicador'] ?? null) {
                        'Excelente'    => 'ind-e',
                        'Bueno'        => 'ind-b',
                        'En proceso'   => 'ind-p',
                        'Insuficiente' => 'ind-i',
                        default        => 'ind-v',
                    };
                    $pg = $progreso[$row['asignacion']->id] ?? null;
                @endphp
                <tr>
                    <td>
                        {{ $row['asignatura'] }}
                        @if($row['docente'])
                            <div style="font-size:.7rem;color:#9ca3af;font-weight:400;">{{ $row['docente'] }}</div>
                        @endif
                        @if(!isset($vistaDocente) || !$vistaDocente)
                            @if($row['publicado'] ?? false)
                                <span style="font-size:.62rem;font-weight:700;background:#dcfce7;color:#15803d;padding:.15em .55em;border-radius:20px;display:inline-block;margin-top:2px;">
                                    <i class="bi bi-check-circle"></i> Publicado
                                </span>
                            @else
                                <span style="font-size:.62rem;font-weight:700;background:#fef3c7;color:#92400e;padding:.15em .55em;border-radius:20px;display:inline-block;margin-top:2px;">
                                    <i class="bi bi-clock"></i> Sin publicar
                                </span>
                            @endif
                        @endif
                    </td>
                    @foreach($periodos as $p)
                    @php
                        $cal  = $row['periodos'][$p->id] ?? null;
                        $nota = $cal?->nota_final;
                        $ncls = match(true) {
                            $nota === null   => 'nc-vac',
                            $nota >= 90      => 'nc-exc',
                            $nota >= 75      => 'nc-bue',
                            $nota >= 60      => 'nc-pro',
                            default          => 'nc-ins',
                        };
                    @endphp
                    <td class="{{ $ncls }}">
                        {{ $nota !== null ? number_format($nota, 1) : '—' }}
                    </td>
                    @endforeach
                    {{-- Columna progreso --}}
                    <td style="text-align:center;font-size:.85rem;font-weight:700;">
                        @if($pg)
                            @if($pg['direccion'] === 'sube')
                                <span style="color:#15803d;" title="Mejoró {{ abs($pg['diff']) }} puntos">
                                    <i class="bi bi-arrow-up-circle-fill"></i> +{{ abs($pg['diff']) }}
                                </span>
                            @elseif($pg['direccion'] === 'baja')
                                <span style="color:#dc2626;" title="Bajó {{ abs($pg['diff']) }} puntos">
                                    <i class="bi bi-arrow-down-circle-fill"></i> -{{ abs($pg['diff']) }}
                                </span>
                            @else
                                <span style="color:#9ca3af;" title="Sin cambio"><i class="bi bi-dash-circle"></i></span>
                            @endif
                        @else
                            <span style="color:#d1d5db;">—</span>
                        @endif
                    </td>
                    @php
                        $prom = $row['promedio'];
                        $pcls = match(true) {
                            $prom === null => 'nc-vac',
                            $prom >= 90   => 'nc-exc',
                            $prom >= 75   => 'nc-bue',
                            $prom >= 60   => 'nc-pro',
                            default       => 'nc-ins',
                        };
                    @endphp
                    <td class="{{ $pcls }}" style="font-weight:800;">
                        {{ $prom !== null ? number_format($prom, 1) : '—' }}
                    </td>
                    <td>
                        @if($row['indicador'])
                            <span class="ind-badge {{ $indCls }}">{{ $row['indicador'] }}</span>
                        @else
                            <span class="ind-badge ind-v">—</span>
                        @endif
                    </td>
                </tr>
                @endforeach

                {{-- Promedio General --}}
                <tr class="prom-row">
                    <td colspan="{{ $periodos->count() + 2 }}" style="text-align:right;padding-right:1rem;letter-spacing:.05em;">
                        PROMEDIO GENERAL ANUAL
                    </td>
                    <td>
                        <span class="prom-box">
                            {{ $promedioGeneral !== null ? number_format($promedioGeneral, 1) : '—' }}
                        </span>
                    </td>
                    <td>
                        @if($promedioGeneral !== null)
                        @php
                            $pgCls   = $promedioGeneral >= 90 ? 'ind-e' : ($promedioGeneral >= 75 ? 'ind-b' : ($promedioGeneral >= 60 ? 'ind-p' : 'ind-i'));
                            $pgLabel = $promedioGeneral >= 90 ? 'Excelente' : ($promedioGeneral >= 75 ? 'Bueno' : ($promedioGeneral >= 60 ? 'En proceso' : 'Insuficiente'));
                        @endphp
                        <span class="ind-badge {{ $pgCls }}">{{ $pgLabel }}</span>
                        @endif
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    {{-- ── Gráfica de barras por materia (no se imprime) ─────────────────── --}}
    @if(!empty($tablaNotas) && count($tablaNotas) > 0)
    <div style="padding:1rem 1.75rem 1.25rem;" class="no-print">
        <div style="font-size:.72rem;font-weight:800;letter-spacing:.13em;text-transform:uppercase;color:#1e3a6e;border-bottom:2px solid #1e3a6e;padding-bottom:.35rem;margin-bottom:.75rem;display:flex;align-items:center;gap:.5rem;">
            <i class="bi bi-bar-chart-fill"></i> Rendimiento Visual por Materia
        </div>
        <canvas id="chartNotas" style="max-height:220px;"></canvas>
    </div>
    @endif
    @endif

    {{-- ── 4. INDICADORES DE LOGRO ──────────────────────────────────────── --}}
    @if(isset($evaluaciones) && $evaluaciones->isNotEmpty())
    <div class="section-heading">
        <i class="bi bi-patch-check"></i> Indicadores de Logro
    </div>
    <div class="eval-section">
        <div class="eval-grid">
            @foreach($evaluaciones as $eval)
            @php
                $eCls = match($eval->nivel ?? null) {
                    'Excelente'    => 'ind-e',
                    'Bueno'        => 'ind-b',
                    'En proceso'   => 'ind-p',
                    'Insuficiente' => 'ind-i',
                    default        => 'ind-v',
                };
            @endphp
            <div class="eval-item">
                <span class="ind-badge {{ $eCls }}">{{ $eval->nivel }}</span>
                <span style="color:#374151;">{{ optional($eval->indicador)->descripcion ?? '—' }}</span>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- ── 5. ASISTENCIA ───────────────────────────────────────────────── --}}
    <div class="section-heading">
        <i class="bi bi-calendar-check"></i> Resumen de Asistencia
    </div>
    <div class="asist-wrapper">
        @if($asistenciaTotales['total'] > 0)
        <table class="asist-table">
            <thead>
                <tr>
                    <th style="text-align:left;">Concepto</th>
                    @foreach($periodos as $p)
                        <th>{{ $p->nombre_corto ?? 'P'.$p->numero }}</th>
                    @endforeach
                    <th style="background:#c0392b;">Total</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $filas = [
                        'total'       => ['label' => 'Días de Clase',   'icon' => '📅'],
                        'presente'    => ['label' => 'Asistencias',     'icon' => '✅'],
                        'ausente'     => ['label' => 'Ausencias',       'icon' => '❌'],
                        'tardanza'    => ['label' => 'Tardanzas',       'icon' => '⏰'],
                        'justificado' => ['label' => 'Justificados',    'icon' => '📋'],
                    ];
                @endphp
                @foreach($filas as $key => $meta)
                <tr>
                    <td>{{ $meta['label'] }}</td>
                    @foreach($periodos as $p)
                        <td>{{ $asistenciaPorPeriodo[$p->id][$key] ?? 0 }}</td>
                    @endforeach
                    <td class="asist-total">{{ $asistenciaTotales[$key] ?? 0 }}</td>
                </tr>
                @endforeach
                <tr>
                    <td><strong>% Asistencia</strong></td>
                    @foreach($periodos as $p)
                    @php
                        $pct = $asistenciaPorPeriodo[$p->id]['pct'] ?? null;
                        $pctCls = $pct === null ? '' : ($pct >= 90 ? 'pct-good' : ($pct >= 75 ? 'pct-warn' : 'pct-bad'));
                    @endphp
                    <td class="{{ $pctCls }}">{{ $pct !== null ? $pct.'%' : '—' }}</td>
                    @endforeach
                    @php
                        $pctT = $asistenciaTotales['pct'];
                        $pctTCls = $pctT === null ? '' : ($pctT >= 90 ? 'pct-good' : ($pctT >= 75 ? 'pct-warn' : 'pct-bad'));
                    @endphp
                    <td class="asist-total {{ $pctTCls }}">{{ $pctT !== null ? $pctT.'%' : '—' }}</td>
                </tr>
            </tbody>
        </table>
        @else
        <p class="text-muted" style="font-size:.83rem;">No hay registros de asistencia para este período.</p>
        @endif
    </div>

    {{-- ── 6. OBSERVACIONES ────────────────────────────────────────────── --}}
    <div class="section-heading">
        <i class="bi bi-chat-square-text"></i> Observaciones
    </div>
    <div class="obs-section">
        @php
            $hayObsEst = isset($boletinObservaciones) && $boletinObservaciones->isNotEmpty();
            $hayObsLeg = $observacionesList->isNotEmpty();
            $hayObsCfg = $boletinConfig && $boletinConfig->observaciones_generales;
        @endphp
        <div class="obs-box">
            {{-- Observaciones estructuradas por tipo --}}
            @if($hayObsEst)
                @foreach($boletinObservaciones as $tipo => $items)
                <div style="margin-bottom:.65rem;">
                    <div style="font-size:.68rem;font-weight:800;text-transform:uppercase;letter-spacing:.08em;color:#1e3a6e;margin-bottom:.2rem;">
                        @php echo match($tipo) { 'academica'=>'Académica','conducta'=>'Conducta','sugerencia'=>'Sugerencia',default=>'General' }; @endphp
                    </div>
                    @foreach($items as $obs)
                    <div class="obs-item">
                        @if($obs->docente)
                            <span style="font-size:.72rem;color:#9ca3af;">({{ optional($obs->docente)->nombre_completo }}):</span>
                        @endif
                        {{ $obs->contenido }}
                    </div>
                    @endforeach
                </div>
                @endforeach
            @endif
            {{-- Observaciones por materia (legacy) --}}
            @if($hayObsLeg)
                @foreach($observacionesList as $obs)
                @if($obs->observaciones)
                <div class="obs-item">
                    <span class="obs-materia">{{ optional($obs->asignacion->asignatura)->nombre ?? 'Materia' }}:</span>
                    {{ $obs->observaciones }}
                </div>
                @endif
                @endforeach
            @endif
            {{-- Observaciones generales del config --}}
            @if($hayObsCfg)
                <div class="obs-item" style="border-top:1px dashed #e5e7eb;padding-top:.4rem;font-style:italic;">
                    {{ $boletinConfig->observaciones_generales }}
                </div>
            @endif
            @if(!$hayObsEst && !$hayObsLeg && !$hayObsCfg)
                <span style="color:#d1d5db;font-style:italic;">Sin observaciones registradas para este período.</span>
            @endif
        </div>
    </div>

    {{-- ── 7. ESTADO ACADÉMICO FINAL ───────────────────────────────────── --}}
    <div class="section-heading">
        <i class="bi bi-award"></i> Estado Académico Final
    </div>
    <div style="padding:0 1.75rem 1.25rem;">
        @if(isset($promocion) && $promocion)
        @php
            $bgColor   = $promocion->estado_color;
            $textColor = match($promocion->estado) {
                'promovido'    => '#15803d',
                'no_promovido' => '#991b1b',
                'condicionado' => '#92400e',
                default        => '#4b5563',
            };
            $borderColor = $textColor;
            $iconClass   = match($promocion->estado) {
                'promovido'    => 'bi-check-circle-fill',
                'no_promovido' => 'bi-x-circle-fill',
                'condicionado' => 'bi-exclamation-circle-fill',
                default        => 'bi-hourglass-split',
            };
        @endphp
        <div style="background:{{ $bgColor }};border:2px solid {{ $borderColor }};border-radius:10px;padding:1rem 1.25rem;display:flex;align-items:center;gap:1.25rem;">
            <div style="flex-shrink:0;">
                <i class="bi {{ $iconClass }}" style="font-size:2.5rem;color:{{ $textColor }};"></i>
            </div>
            <div style="flex:1;">
                <div style="font-size:1.25rem;font-weight:900;color:{{ $textColor }};line-height:1.1;">
                    {{ strtoupper($promocion->estado_label) }}
                </div>
                <div style="display:flex;flex-wrap:wrap;gap:1.25rem;margin-top:.35rem;font-size:.83rem;color:{{ $textColor }};">
                    @if($promocion->promedio_final !== null)
                    <span><strong>Promedio final:</strong> {{ number_format($promocion->promedio_final, 2) }}</span>
                    @endif
                    @if($promocion->materias_reprobadas)
                    <span><strong>Materias reprobadas:</strong> {{ $promocion->materias_reprobadas }}</span>
                    @endif
                    @if($promocion->pct_asistencia !== null)
                    <span><strong>Asistencia anual:</strong> {{ number_format($promocion->pct_asistencia, 1) }}%</span>
                    @endif
                </div>
                @if($promocion->observacion)
                <div style="margin-top:.35rem;font-size:.82rem;font-style:italic;color:{{ $textColor }};opacity:.8;">
                    {{ $promocion->observacion }}
                </div>
                @endif
            </div>
        </div>
        @else
        <div style="border:1.5px dashed #d1d5db;border-radius:8px;padding:.85rem 1rem;color:#9ca3af;font-size:.84rem;font-style:italic;">
            <i class="bi bi-hourglass-split me-2"></i>
            Estado de promoción pendiente de evaluación por la dirección académica.
        </div>
        @endif
    </div>

    {{-- ── 8. FIRMAS ────────────────────────────────────────────────────── --}}
    <div class="firma-section">
        <div class="firma-card">
            <div class="firma-space"></div>
            <div class="firma-line">
                {{ ($boletinConfig && $boletinConfig->director) ? $boletinConfig->director : 'Director(a)' }}
            </div>
            <div class="firma-rol">Director(a) del Centro</div>
        </div>
        <div class="firma-card" style="display:flex;flex-direction:column;align-items:center;">
            <div class="firma-sello">SELLO<br>OFICIAL</div>
            <div style="margin-top:.5rem;font-size:.7rem;color:#9ca3af;">Sello del Centro</div>
        </div>
        <div class="firma-card">
            <div class="firma-space"></div>
            <div class="firma-line">
                {{ optional($matricula->grupo->tutor)->nombre_completo ?? 'Docente Guía' }}
            </div>
            <div class="firma-rol">Orientador(a) del Grado</div>
        </div>
    </div>

    {{-- ── FOOTER ───────────────────────────────────────────────────────── --}}
    <div class="boletin-footer">
        Generado el {{ now()->format('d/m/Y \a \l\a\s H:i') }}
        &nbsp;·&nbsp; {{ env('SCHOOL_NAME', 'Politécnico Salesiano Arquides Calderón') }}
        @if($boletinConfig && $boletinConfig->pie_pagina)
            &nbsp;·&nbsp; {{ $boletinConfig->pie_pagina }}
        @endif
        @if(!empty($rankingGrupo['puesto']))
        &nbsp;·&nbsp; <strong>Puesto {{ $rankingGrupo['puesto'] }} de {{ $rankingGrupo['total'] }}</strong> en el grupo
        @endif
    </div>

</div>{{-- .boletin-container --}}

{{-- ══ MODAL: Agregar Observación ══ --}}
@if(!$vistaDocente)
<div class="modal fade" id="modalObservacion" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow" style="border-radius:14px;">
            <div class="modal-header border-0" style="background:#1e3a6e;border-radius:14px 14px 0 0;">
                <h6 class="modal-title text-white fw-bold"><i class="bi bi-chat-left-text me-2"></i>Agregar Observación</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('admin.boletines.obs.guardar', [$matricula, $periodo]) }}">
                @csrf
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Tipo de observación</label>
                        <select name="tipo" class="form-select form-select-sm">
                            <option value="academica">Académica</option>
                            <option value="conducta">Conducta</option>
                            <option value="sugerencia">Sugerencia</option>
                            <option value="general">General</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Observación</label>
                        <textarea name="contenido" class="form-control" rows="4" required
                            placeholder="Escriba la observación para este período..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-sm btn-primary" style="border-radius:8px;">
                        <i class="bi bi-check-lg me-1"></i>Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

@push('scripts')
@if(!empty($tablaNotas) && count($tablaNotas) > 0)
<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const canvas = document.getElementById('chartNotas');
    if (!canvas) return;

    const labels  = @json(collect($tablaNotas)->pluck('asignatura')->toArray());
    const notas   = @json(collect($tablaNotas)->map(fn($r) => $r['promedio'])->toArray());
    const colors  = notas.map(n => n === null ? '#E5E7EB' : (n >= 90 ? '#16A34A' : (n >= 75 ? '#2563EB' : (n >= 60 ? '#D97706' : '#DC2626'))));

    new Chart(canvas, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Promedio Anual',
                data: notas,
                backgroundColor: colors,
                borderRadius: 6,
                borderSkipped: false,
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: ctx => ' Promedio: ' + (ctx.raw ?? '—')
                    }
                }
            },
            scales: {
                y: {
                    min: 0, max: 100,
                    grid: { color: '#F3F4F6' },
                    ticks: { font: { size: 11 } }
                },
                x: {
                    ticks: {
                        font: { size: 10 },
                        maxRotation: 35
                    },
                    grid: { display: false }
                }
            }
        }
    });
});
</script>
@endif
@endpush

@endsection
