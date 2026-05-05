@extends('layouts.admin')
@section('page-title', 'Boletines — '.optional($grupo)->nombre_completo)

@push('styles')
<style>
/* ── Layout ─────────────────────────────────────────────────── */
.grupo-boletin-wrap { max-width: 1100px; margin: 0 auto; }

/* ── Filtro búsqueda ─────────────────────────────────────────── */
.search-filter-bar {
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    padding: .75rem 1rem;
    margin-bottom: 1.25rem;
    display: flex;
    gap: .75rem;
    align-items: center;
    flex-wrap: wrap;
}
.search-filter-bar input {
    flex: 1;
    min-width: 200px;
    border: 1px solid #e5e7eb;
    border-radius: 7px;
    padding: .4rem .75rem;
    font-size: .85rem;
    outline: none;
}
.search-filter-bar input:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(30,58,110,.08); }

/* ── Mini boletín card ───────────────────────────────────────── */
.boletin-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    overflow: hidden;
    margin-bottom: 1.25rem;
    box-shadow: 0 2px 8px rgba(30,58,110,.06);
    transition: box-shadow .15s;
    page-break-inside: avoid;
    break-inside: avoid;
}
.boletin-card:hover { box-shadow: 0 6px 20px rgba(30,58,110,.12); }

/* Header MINERD estilo */
.bc-header {
    background: #1e3a6e;
    color: #fff;
    display: table;
    width: 100%;
    border-collapse: collapse;
}
.bc-header-logo {
    display: table-cell;
    width: 56px;
    vertical-align: middle;
    text-align: center;
    padding: 8px 6px;
    border-right: 1px solid rgba(255,255,255,.15);
}
.bc-header-logo img  { max-width: 44px; max-height: 44px; object-fit: contain; }
.bc-header-logo .abbr { width:40px;height:40px;background:#c0392b;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:900;font-size:.75rem;margin:auto; }
.bc-header-center {
    display: table-cell;
    vertical-align: middle;
    padding: 7px 10px;
}
.bc-inst-name { font-weight: 800; font-size: .82rem; line-height: 1.2; }
.bc-inst-sub  { font-size: .68rem; opacity: .75; margin-top: 1px; }
.bc-header-student {
    display: table-cell;
    width: 200px;
    vertical-align: middle;
    padding: 7px 10px;
    border-left: 1px solid rgba(255,255,255,.15);
    text-align: right;
}
.bc-student-name { font-weight: 800; font-size: .82rem; line-height: 1.2; }
.bc-student-sub  { font-size: .68rem; opacity: .75; margin-top: 2px; }
.bc-periodo-bar {
    background: #c0392b;
    color: #fff;
    font-size: .65rem;
    font-weight: 800;
    text-align: center;
    letter-spacing: .12em;
    text-transform: uppercase;
    padding: 3px 0;
}

/* ── Tabla de notas ─────────────────────────────────────────── */
.notas-mini {
    width: 100%;
    border-collapse: collapse;
    font-size: .8rem;
}
.notas-mini thead th {
    background: #f0f4f8;
    color: #1e3a6e;
    font-size: .68rem;
    font-weight: 700;
    padding: .3rem .6rem;
    text-align: center;
    border-bottom: 1px solid #e5e7eb;
}
.notas-mini thead th:first-child { text-align: left; }
.notas-mini tbody td {
    padding: .35rem .6rem;
    text-align: center;
    border-bottom: 1px solid #f3f4f6;
    vertical-align: middle;
}
.notas-mini tbody td:first-child { text-align: left; font-weight: 600; }
.notas-mini tbody tr:last-child td { border-bottom: 0; }
.prom-row td {
    background: #eef3fb;
    font-weight: 800;
    color: #1e3a6e;
    font-size: .8rem;
    border-top: 1.5px solid #c7d6f0;
}

/* Indicadores */
.ind { font-size: .65rem; font-weight: 700; padding: .15em .5em; border-radius: 10px; white-space: nowrap; }
.ind-e { background:#dcfce7;color:#15803d; }
.ind-b { background:#dbeafe;color:#1d4ed8; }
.ind-p { background:#fef3c7;color:#92400e; }
.ind-i { background:#fee2e2;color:#991b1b; }
.ind-v { background:#f3f4f6;color:#6b7280; }

/* ── Footer card ────────────────────────────────────────────── */
.bc-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: .5rem .75rem;
    background: #f8fafc;
    border-top: 1px solid #e5e7eb;
    font-size: .75rem;
    flex-wrap: wrap;
    gap: .4rem;
}
.asist-chips { display: flex; gap: .35rem; flex-wrap: wrap; }
.asist-chip  { padding: .15em .55em; border-radius: 10px; font-size: .68rem; font-weight: 700; }

/* ── Print ──────────────────────────────────────────────────── */
@media print {
    * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; color-adjust: exact !important; }
    .sidebar, .topbar, .sidebar-overlay, .no-print,
    #sge-toast-container, #nprogress-bar { display: none !important; }
    body { background: #fff !important; margin: 0 !important; padding: 0 !important; }
    .main-content { margin-left: 0 !important; margin-top: 0 !important; padding: 0 !important; min-height: unset !important; }
    .grupo-boletin-wrap { max-width: 100% !important; padding: 0 !important; }
    .search-filter-bar, .action-bar { display: none !important; }
    .boletin-card { box-shadow: none !important; border: 1px solid #d1d5db !important; margin-bottom: .75rem !important; page-break-inside: avoid; break-inside: avoid; }
    .bc-footer .btn { display: none !important; }
    @page { size: letter portrait; margin: 1cm 1.2cm; }
}

/* Print solo un estudiante (clase aplicada via JS) */
@media print {
    body.print-one .boletin-card { display: none !important; }
    body.print-one .boletin-card.printing { display: block !important; }
}

[data-theme="dark"] .search-filter-bar { background: #1e293b; border-color: #334155; }
[data-theme="dark"] .search-filter-bar input { background: #0f172a; border-color: #334155; color: #e2e8f0; }
[data-theme="dark"] .boletin-card { background: #1e293b; border-color: #334155; }
[data-theme="dark"] .notas-mini thead th { background: #162032; color: #93c5fd; border-bottom-color: #334155; }
[data-theme="dark"] .notas-mini tbody td { border-bottom-color: #334155; color: #cbd5e1; }
[data-theme="dark"] .bc-footer { background: #162032; border-top-color: #334155; }
[data-theme="dark"] .ind-e { background: #052e16; color: #4ade80; }
[data-theme="dark"] .ind-b { background: #0c1f3f; color: #93c5fd; }
[data-theme="dark"] .ind-p { background: #1c1000; color: #fcd34d; }
[data-theme="dark"] .ind-i { background: #1c0000; color: #f87171; }
[data-theme="dark"] .ind-v { background: #1e293b; color: #64748b; }
</style>
@endpush

@section('content')

<div class="grupo-boletin-wrap">

{{-- Breadcrumb --}}
<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb mb-0" style="font-size:.82rem;">
        <li class="breadcrumb-item"><a href="{{ route('admin.boletines.index') }}" class="text-decoration-none">Boletines</a></li>
        <li class="breadcrumb-item active">{{ optional($grupo)->nombre_completo }}</li>
    </ol>
</nav>

{{-- Action bar --}}
<div class="action-bar d-flex align-items-center justify-content-between mb-3 gap-2 flex-wrap no-print">
    <div>
        <h5 class="fw-bold mb-0" style="color:var(--primary)">
            <i class="bi bi-people-fill me-2"></i>{{ optional($grupo)->nombre_completo }}
            <span class="badge rounded-pill ms-2" style="background:#e0e7ff;color:#1e3a6e;font-size:.72rem;">
                {{ $matriculas->count() }} estudiantes
            </span>
        </h5>
        <div class="text-muted" style="font-size:.78rem;">
            {{ optional($periodo)->nombre }} · {{ optional($schoolYear)->nombre }}
        </div>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <button onclick="imprimirTodo()" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-printer me-1"></i>Imprimir Todo
        </button>
        <a href="{{ route('admin.boletines.zip', ['grupo_id'=>$grupo->id,'periodo_id'=>$periodo->id]) }}"
           class="btn btn-danger btn-sm">
            <i class="bi bi-file-earmark-zip me-1"></i>Descargar ZIP PDF
        </a>
        <a href="{{ route('admin.boletines.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i>Volver
        </a>
    </div>
</div>

{{-- Filtro de búsqueda --}}
<div class="search-filter-bar no-print">
    <i class="bi bi-search text-muted"></i>
    <input type="text" id="buscarEstudiante" placeholder="Buscar estudiante por nombre o matrícula…" oninput="filtrarEstudiantes(this.value)">
    <span class="text-muted" style="font-size:.78rem;" id="contadorVisible">{{ $matriculas->count() }} mostrando</span>
</div>

@if($matriculas->isEmpty())
<div class="text-center py-5 text-muted">
    <i class="bi bi-people" style="font-size:3rem;opacity:.3;"></i>
    <p class="mt-3 mb-0">No hay estudiantes activos en este grupo para el año escolar actual.</p>
    <a href="{{ route('admin.boletines.index') }}" class="btn btn-outline-primary btn-sm mt-3">Seleccionar otro grupo</a>
</div>
@else

<div id="lista-boletines" class="row g-3">
@foreach($matriculas as $matricula)
@php
    $bd        = $boletines[$matricula->id] ?? null;
    $notas     = $bd ? $bd['notas'] : [];
    $promGen   = $bd ? $bd['promedioGeneral'] : null;
    $asist     = $bd ? $bd['asistencia'] : [];
    $pgInd     = null; $pgCls = 'ind-v';
    if ($promGen !== null) {
        $pgInd = $promGen >= 90 ? 'Excelente' : ($promGen >= 75 ? 'Bueno' : ($promGen >= 60 ? 'En proceso' : 'Insuficiente'));
        $pgCls = $promGen >= 90 ? 'ind-e' : ($promGen >= 75 ? 'ind-b' : ($promGen >= 60 ? 'ind-p' : 'ind-i'));
    }
    $pctAsist  = ($asist['total'] ?? 0) > 0
        ? round((($asist['presente'] + ($asist['tardanza'] ?? 0) + ($asist['justificado'] ?? 0)) / $asist['total']) * 100, 1)
        : null;
@endphp
<div class="col-xl-6 boletin-item"
     data-nombre="{{ strtolower(optional($matricula->estudiante)->nombre_completo ?? '') }}"
     data-matricula="{{ optional($matricula->estudiante)->numero_matricula ?? $matricula->id }}">
    <div class="boletin-card" id="card-{{ $matricula->id }}">

        {{-- Header MINERD --}}
        <div class="bc-header">
            <div class="bc-header-logo">
                @if($boletinConfig && $boletinConfig->logo)
                    <img src="{{ asset('storage/'.$boletinConfig->logo) }}" alt="Logo">
                @else
                    <div class="abbr">PSA</div>
                @endif
            </div>
            <div class="bc-header-center">
                <div class="bc-inst-name">
                    {{ $boletinConfig?->nombre_institucion ?? config('app.school_name', 'Politécnico Salesiano') }}
                </div>
                <div class="bc-inst-sub">
                    {{ $boletinConfig?->nivel_educativo ?? 'Nivel Secundario' }} · Rep. Dominicana
                </div>
            </div>
            <div class="bc-header-student">
                <div class="bc-student-name">{{ optional($matricula->estudiante)->nombre_completo ?? '—' }}</div>
                <div class="bc-student-sub">
                    Matr. #{{ optional($matricula->estudiante)->numero_matricula ?? $matricula->id }}
                    @if($promGen !== null)
                        · Prom: <strong>{{ number_format($promGen, 1) }}</strong>
                    @endif
                </div>
            </div>
        </div>

        {{-- Barra período --}}
        <div class="bc-periodo-bar">
            {{ optional($grupo)->nombre_completo }} &nbsp;·&nbsp; {{ optional($periodo)->nombre }}
            @if($pgInd) &nbsp;·&nbsp; <span class="ind {{ $pgCls }}" style="font-size:.6rem;">{{ $pgInd }}</span> @endif
        </div>

        {{-- Tabla de notas --}}
        @if(count($notas) > 0)
        <table class="notas-mini">
            <thead>
                <tr>
                    <th>Materia</th>
                    <th>Promedio</th>
                    <th>Indicador</th>
                </tr>
            </thead>
            <tbody>
                @foreach($notas as $nota)
                @php
                    $iCls = match($nota['indicador']) {
                        'Excelente'    => 'ind-e',
                        'Bueno'        => 'ind-b',
                        'En proceso'   => 'ind-p',
                        'Insuficiente' => 'ind-i',
                        default        => 'ind-v',
                    };
                @endphp
                <tr>
                    <td>{{ $nota['asignatura'] }}</td>
                    <td>
                        <strong>{{ $nota['nota_final'] !== null ? number_format($nota['nota_final'], 1) : '—' }}</strong>
                    </td>
                    <td>
                        <span class="ind {{ $iCls }}">{{ $nota['indicador'] ?? '—' }}</span>
                    </td>
                </tr>
                @endforeach
                <tr class="prom-row">
                    <td style="text-align:right;padding-right:.5rem;font-weight:700;">Promedio General</td>
                    <td><strong>{{ $promGen !== null ? number_format($promGen, 1) : '—' }}</strong></td>
                    <td>@if($pgInd)<span class="ind {{ $pgCls }}">{{ $pgInd }}</span>@endif</td>
                </tr>
            </tbody>
        </table>
        @else
        <div class="text-center text-muted py-3" style="font-size:.8rem;">
            <i class="bi bi-info-circle me-1"></i>Sin calificaciones registradas en este período.
        </div>
        @endif

        {{-- Footer: asistencia + acciones --}}
        <div class="bc-footer">
            <div class="asist-chips">
                @if(($asist['total'] ?? 0) > 0)
                <span class="asist-chip" style="background:#dcfce7;color:#15803d;">
                    <i class="bi bi-check-circle-fill"></i> {{ $asist['presente'] ?? 0 }} pres.
                </span>
                <span class="asist-chip" style="background:#fee2e2;color:#991b1b;">
                    <i class="bi bi-x-circle-fill"></i> {{ $asist['ausente'] ?? 0 }} aus.
                </span>
                @if(($asist['tardanza'] ?? 0) > 0)
                <span class="asist-chip" style="background:#fef3c7;color:#92400e;">
                    <i class="bi bi-clock-fill"></i> {{ $asist['tardanza'] }} tard.
                </span>
                @endif
                @if($pctAsist !== null)
                <span class="asist-chip" style="background:#e0e7ff;color:#1e3a6e;font-weight:800;">
                    {{ $pctAsist }}% asist.
                </span>
                @endif
                @else
                <span class="text-muted" style="font-size:.72rem;">Sin asistencia registrada</span>
                @endif
            </div>
            <div class="d-flex gap-1 no-print flex-wrap">
                <button onclick="imprimirUno({{ $matricula->id }})"
                        class="btn btn-sm btn-outline-secondary" title="Imprimir">
                    <i class="bi bi-printer"></i>
                </button>
                <a href="{{ route('admin.boletines.ver', [$matricula->id, $periodo->id]) }}"
                   class="btn btn-sm btn-outline-primary" title="Ver boletín completo">
                    <i class="bi bi-eye"></i>
                </a>
                <a href="{{ route('admin.boletines.pdf', [$matricula->id, $periodo->id]) }}"
                   class="btn btn-sm btn-danger" title="PDF Período" target="_blank">
                    <i class="bi bi-file-pdf"></i>
                </a>
                <a href="{{ route('admin.boletines.pdf-anual', $matricula->id) }}"
                   class="btn btn-sm btn-outline-info" title="PDF Anual" target="_blank">
                    <i class="bi bi-file-earmark-bar-graph"></i>
                </a>
                @php
                    $telRep = $matricula->representantes()->first()?->telefono
                        ?? $matricula->estudiante?->tutor_telefono ?? null;
                    if ($telRep) {
                        $msgWA2 = urlencode("📋 *Boletín de Calificaciones*\n\nEstudiante: " . ($matricula->estudiante?->nombres . ' ' . $matricula->estudiante?->apellidos) . "\nPeríodo: {$periodo->nombre}\nPromedio: " . ($promGen ? number_format($promGen,1) : '—') . "\n\n_" . ($boletinConfig?->nombre_institucion ?? 'Centro Educativo') . "_");
                        $waLink = 'https://wa.me/' . preg_replace('/\D+/', '', $telRep) . '?text=' . $msgWA2;
                    }
                @endphp
                @if($telRep ?? false)
                <a href="{{ $waLink }}" target="_blank"
                   class="btn btn-sm" style="background:#25D366;color:#fff;border:none;" title="Enviar por WhatsApp">
                    <i class="bi bi-whatsapp"></i>
                </a>
                @endif
            </div>
        </div>

    </div>{{-- .boletin-card --}}
</div>{{-- .col --}}
@endforeach
</div>{{-- #lista-boletines --}}
@endif

</div>{{-- .grupo-boletin-wrap --}}
@endsection

@push('scripts')
<script>
// ── Filtrar por nombre/matrícula ──────────────────────────────
function filtrarEstudiantes(q) {
    q = q.toLowerCase().trim();
    let visible = 0;
    document.querySelectorAll('.boletin-item').forEach(function (el) {
        const match = !q
            || el.dataset.nombre.includes(q)
            || el.dataset.matricula.includes(q);
        el.style.display = match ? '' : 'none';
        if (match) visible++;
    });
    document.getElementById('contadorVisible').textContent = visible + ' mostrando';
}

// ── Imprimir un solo estudiante ───────────────────────────────
function imprimirUno(id) {
    document.querySelectorAll('.boletin-card').forEach(function (c) {
        c.classList.remove('printing');
    });
    const card = document.getElementById('card-' + id);
    if (card) card.classList.add('printing');
    document.body.classList.add('print-one');
    window.print();
    document.body.classList.remove('print-one');
    document.querySelectorAll('.boletin-card').forEach(function (c) {
        c.classList.remove('printing');
    });
}

// ── Imprimir todo el grupo ────────────────────────────────────
function imprimirTodo() {
    document.body.classList.remove('print-one');
    window.print();
}
</script>
@endpush
