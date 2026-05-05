@extends('layouts.admin')
@section('page-title', 'Importar Calificaciones Académicas')

@push('styles')
<style>
.imp-card {
    background: #fff;
    border-radius: 12px;
    border: 1px solid #e5e7eb;
    padding: 1.75rem;
}
.imp-card .section-title {
    font-size: .78rem; font-weight: 700;
    letter-spacing: .1em; text-transform: uppercase;
    color: var(--primary);
    border-bottom: 2px solid var(--primary);
    padding-bottom: .5rem; margin-bottom: 1.25rem;
    display: flex; align-items: center; gap: .5rem;
}
.drop-zone {
    border: 2px dashed #d1d5db; border-radius: 10px;
    padding: 2rem 1rem; text-align: center; cursor: pointer;
    transition: border-color .2s, background .2s;
}
.drop-zone:hover, .drop-zone.dragover { border-color: var(--primary); background: #f0f4fb; }
.drop-zone i { font-size: 2.2rem; color: #9ca3af; }
.drop-zone.has-file i { color: var(--primary); }
.drop-zone.has-file .drop-label { color: var(--primary); font-weight: 600; }
.comp-legend span {
    display: inline-flex; align-items: center; gap: .3rem;
    font-size: .72rem; font-weight: 600; padding: .25rem .6rem;
    border-radius: 20px; white-space: nowrap;
}
.result-table th { font-size: .78rem; background: var(--primary); color: #fff; }
.result-table td { font-size: .8rem; vertical-align: middle; }
[data-theme="dark"] .imp-card { background: #1e293b; border-color: #334155; }
[data-theme="dark"] .drop-zone { border-color: #475569; }
[data-theme="dark"] .drop-zone:hover, [data-theme="dark"] .drop-zone.dragover { background: #162032; border-color: var(--primary); }
</style>
@endpush

@section('content')

{{-- Header --}}
<div class="d-flex align-items-center gap-3 mb-4">
    <a href="{{ route('admin.importaciones.index') }}" class="btn btn-sm btn-outline-secondary" style="border-radius:8px;">
        <i class="bi bi-arrow-left me-1"></i>Importaciones
    </a>
    <div>
        <h1 class="mb-0" style="font-size:1.4rem;font-weight:800;color:var(--primary);">
            <i class="bi bi-journal-arrow-up me-2" style="color:var(--secondary);"></i>Importar Calificaciones Académicas
        </h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0" style="font-size:.78rem;">
                <li class="breadcrumb-item"><a href="{{ route('admin.importaciones.index') }}" class="text-decoration-none">Importaciones</a></li>
                <li class="breadcrumb-item active">Calificaciones</li>
            </ol>
        </nav>
    </div>
</div>

{{-- Alertas de resultado --}}
@if(session('success'))
    <div class="alert alert-success d-flex align-items-center gap-2 mb-3"
         role="alert" style="border-radius:10px;font-size:.875rem;">
        <i class="bi bi-check-circle-fill fs-5"></i>
        <div>{{ session('success') }}</div>
    </div>
@endif

@if(session('stats_import'))
    @php $stats = session('stats_import'); @endphp
    <div class="row g-3 mb-4">
        <div class="col-4">
            <div class="text-center p-3 rounded-3" style="background:#f0fdf4;border:1px solid #bbf7d0;">
                <div style="font-size:1.6rem;font-weight:800;color:#16a34a;">{{ $stats['importados'] }}</div>
                <div style="font-size:.78rem;color:#166534;font-weight:600;">Importados</div>
            </div>
        </div>
        <div class="col-4">
            <div class="text-center p-3 rounded-3" style="background:#fef9c3;border:1px solid #fde047;">
                <div style="font-size:1.6rem;font-weight:800;color:#ca8a04;">{{ $stats['omitidos'] }}</div>
                <div style="font-size:.78rem;color:#92400e;font-weight:600;">Omitidos</div>
            </div>
        </div>
        <div class="col-4">
            <div class="text-center p-3 rounded-3" style="background:#eff6ff;border:1px solid #bfdbfe;">
                <div style="font-size:1.6rem;font-weight:800;color:var(--primary);">{{ $stats['total'] }}</div>
                <div style="font-size:.78rem;color:var(--primary);font-weight:600;">Total filas</div>
            </div>
        </div>
    </div>
@endif

@if(session('errores_import') && count(session('errores_import')) > 0)
    <div class="alert alert-warning mb-4" style="border-radius:10px;font-size:.875rem;" role="alert">
        <div class="d-flex align-items-center gap-2 mb-1">
            <i class="bi bi-exclamation-triangle-fill fs-5"></i>
            <strong>{{ count(session('errores_import')) }} advertencia(s):</strong>
            <button class="btn btn-sm btn-link ms-auto p-0 text-warning-emphasis"
                    type="button" data-bs-toggle="collapse" data-bs-target="#erroresList">
                Ver detalles <i class="bi bi-chevron-down"></i>
            </button>
        </div>
        <div class="collapse" id="erroresList">
            <ul class="mb-0 mt-2 ps-3" style="font-size:.82rem;">
                @foreach(session('errores_import') as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    </div>
@endif

{{-- Tabla de resultados detallada --}}
@if(session('resultados_import') && count(session('resultados_import')) > 0)
    @php $resultados = session('resultados_import'); @endphp
    <div class="imp-card mb-4">
        <div class="section-title">
            <i class="bi bi-table"></i>Resultado por fila
            <span class="badge ms-auto" style="background:#e0f2fe;color:#0369a1;font-size:.72rem;">{{ count($resultados) }} filas procesadas</span>
        </div>
        <div class="table-responsive" style="max-height:320px;overflow-y:auto;">
            <table class="table table-sm table-hover result-table mb-0">
                <thead style="position:sticky;top:0;z-index:1;">
                    <tr>
                        <th style="width:60px;">Fila</th>
                        <th>Estudiante</th>
                        <th style="width:110px;">Estado</th>
                        <th>Detalle</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($resultados as $r)
                        <tr>
                            <td class="text-muted">{{ $r['fila'] }}</td>
                            <td>{{ $r['nombre'] }}</td>
                            <td>
                                @if($r['estado'] === 'ok')
                                    <span class="badge" style="background:#dcfce7;color:#166534;border-radius:8px;">
                                        <i class="bi bi-check-circle me-1"></i>OK
                                    </span>
                                @elseif($r['estado'] === 'advertencia')
                                    <span class="badge" style="background:#fef9c3;color:#92400e;border-radius:8px;">
                                        <i class="bi bi-exclamation-circle me-1"></i>Parcial
                                    </span>
                                @else
                                    <span class="badge" style="background:#fee2e2;color:#991b1b;border-radius:8px;">
                                        <i class="bi bi-x-circle me-1"></i>Error
                                    </span>
                                @endif
                            </td>
                            <td class="text-muted" style="font-size:.78rem;">{{ $r['mensaje'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif

<div class="row g-4">

    {{-- LEFT: Formulario --}}
    <div class="col-lg-7">

        {{-- PASO 1: Selección de asignación --}}
        <div class="imp-card mb-4">
            <div class="section-title">
                <i class="bi bi-1-circle"></i>Paso 1 — Seleccionar Asignación
            </div>
            <label class="form-label fw-semibold mb-1" style="font-size:.83rem;color:var(--primary);">
                <i class="bi bi-journal-check me-1"></i>Asignación (Materia · Grupo)
                <span style="color:#991b1b;">*</span>
            </label>
            <select id="asignacion_sel" class="form-select" style="border-radius:8px;">
                <option value="">— Selecciona la asignación académica —</option>
                @foreach($asignaciones as $a)
                    <option value="{{ $a->id }}">
                        {{ $a->grupo->grado->nombre ?? '' }}
                        {{ $a->grupo->seccion->nombre ?? '' }}
                        · {{ $a->asignatura->nombre ?? '—' }}
                    </option>
                @endforeach
            </select>
            <div class="mt-3 alert alert-light border" style="border-radius:8px;font-size:.8rem;">
                <i class="bi bi-info-circle me-1 text-primary"></i>
                Solo se muestran asignaciones de <strong>área académica</strong> del año escolar activo
                (@isset($schoolYear){{ $schoolYear->nombre }}@endisset).
            </div>
        </div>

        {{-- PASO 2: Subir archivo --}}
        <div class="imp-card">
            <div class="section-title">
                <i class="bi bi-2-circle"></i>Paso 2 — Subir Archivo
            </div>

            <form method="POST" enctype="multipart/form-data"
                  action="{{ route('admin.importaciones.calificaciones.importar') }}"
                  id="importForm">
                @csrf
                <input type="hidden" name="asignacion_id" id="hdnAsignacion">

                {{-- Badges de formatos --}}
                <div class="d-flex flex-wrap gap-1 mb-3">
                    @foreach(['CSV','TXT','XLSX','XLS'] as $fmt)
                        <span class="badge" style="background:#f3f4f6;color:#374151;border:1px solid #d1d5db;font-size:.72rem;font-weight:600;border-radius:20px;padding:.25rem .6rem;">
                            <i class="bi bi-filetype-{{ strtolower($fmt) }} me-1"></i>{{ $fmt }}
                        </span>
                    @endforeach
                    <span class="badge" style="background:#fffbeb;border:1px solid #fcd34d;color:#92400e;font-size:.72rem;font-weight:600;border-radius:20px;padding:.25rem .6rem;">
                        <i class="bi bi-magic me-1"></i>delimitador auto-detectado
                    </span>
                </div>

                <div class="drop-zone mb-3" id="dropZone"
                     onclick="document.getElementById('archivoInp').click()">
                    <i class="bi bi-file-earmark-spreadsheet d-block mb-2"></i>
                    <p class="drop-label mb-1" style="font-size:.9rem;color:#6b7280;">
                        Haz clic o arrastra aquí tu archivo
                    </p>
                    <p class="text-muted mb-0" style="font-size:.76rem;">
                        CSV · TXT · XLSX · XLS — Máx. 10 MB
                    </p>
                </div>
                <input type="file" id="archivoInp" name="archivo"
                       accept=".csv,.txt,.xlsx,.xls" class="d-none"
                       onchange="onFileSelect(this)">

                <div class="d-flex justify-content-end mt-3">
                    <button type="submit" class="btn px-4 fw-semibold" id="submitBtn"
                            style="background:var(--primary);color:#fff;border-radius:8px;">
                        <span id="submitSpinner" class="spinner-border spinner-border-sm me-2 d-none" role="status"></span>
                        <i class="bi bi-cloud-arrow-up me-1" id="submitIcon"></i>
                        Importar Calificaciones
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- RIGHT: Plantilla + Columnas --}}
    <div class="col-lg-5">

        {{-- Plantilla --}}
        <div class="imp-card mb-4">
            <div class="section-title">
                <i class="bi bi-download"></i>Descargar Plantilla
            </div>
            <p class="text-muted mb-3" style="font-size:.82rem;">
                La plantilla se genera con las columnas exactas e incluye los estudiantes del grupo
                pre-cargados (si seleccionaste una asignación).
            </p>
            <div class="d-flex gap-2 flex-wrap mb-2">
                <a href="{{ route('admin.importaciones.calificaciones.plantilla', ['format'=>'csv']) }}"
                   id="btnCsv"
                   class="btn btn-outline-success fw-semibold flex-fill"
                   style="border-radius:8px;font-size:.85rem;">
                    <i class="bi bi-filetype-csv me-1"></i>Plantilla CSV
                </a>
                <a href="{{ route('admin.importaciones.calificaciones.plantilla', ['format'=>'xlsx']) }}"
                   id="btnXlsx"
                   class="btn btn-outline-success fw-semibold flex-fill"
                   style="border-radius:8px;font-size:.85rem;">
                    <i class="bi bi-filetype-xlsx me-1"></i>Plantilla Excel
                </a>
            </div>
            <div style="font-size:.74rem;color:#6b7280;">
                <i class="bi bi-lightbulb me-1"></i>
                Si seleccionas la asignación antes de descargar, la plantilla incluye las notas actuales.
            </div>
        </div>

        {{-- Columnas --}}
        <div class="imp-card mb-4">
            <div class="section-title">
                <i class="bi bi-table"></i>Columnas del archivo
            </div>
            <p style="font-size:.78rem;color:#6b7280;margin-bottom:.75rem;">
                El sistema acepta tanto <code>comp1_p1</code> como <code>p1_comp1</code> (ambas convenciones).
            </p>
            <div class="table-responsive">
                <table class="table table-sm table-bordered mb-0" style="font-size:.8rem;">
                    <thead>
                        <tr style="background:var(--primary);color:#fff;">
                            <th>Columna</th>
                            <th>Descripción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td><code>numero_matricula</code></td><td><span class="badge" style="background:#fee2e2;color:#991b1b;font-size:.68rem;">recomendado</span></td></tr>
                        <tr><td><code>cedula</code></td><td><span class="badge" style="background:#f3f4f6;color:#374151;font-size:.68rem;">alternativo</span></td></tr>
                        <tr><td><code>nombres</code></td><td><span class="badge" style="background:#f3f4f6;color:#374151;font-size:.68rem;">referencia</span></td></tr>
                        <tr><td><code>apellidos</code></td><td><span class="badge" style="background:#f3f4f6;color:#374151;font-size:.68rem;">referencia</span></td></tr>
                        <tr style="background:#e0f2fe;">
                            <td><code>comp1_p1 … comp4_p1</code></td>
                            <td>Período 1 — 4 competencias (0–100)</td>
                        </tr>
                        <tr style="background:#dcfce7;">
                            <td><code>comp1_p2 … comp4_p2</code></td>
                            <td>Período 2 — 4 competencias (0–100)</td>
                        </tr>
                        <tr style="background:#fef9c3;">
                            <td><code>comp1_p3 … comp4_p3</code></td>
                            <td>Período 3 — 4 competencias (0–100)</td>
                        </tr>
                        <tr style="background:#fce7f3;">
                            <td><code>comp1_p4 … comp4_p4</code></td>
                            <td>Período 4 — 4 competencias (0–100)</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="mt-2" style="font-size:.74rem;color:#6b7280;">
                <strong>Total:</strong> 4 columnas de ID + 16 columnas de notas = 20 columnas.
                Las notas fuera de rango [0–100] se omiten por fila.
            </div>
        </div>

        {{-- Competencias referencia --}}
        <div class="imp-card">
            <div class="section-title">
                <i class="bi bi-info-circle"></i>Referencia de Competencias
            </div>
            <div class="comp-legend d-flex flex-wrap gap-2">
                <span style="background:#dbeafe;color:#1e3a8a;">
                    <i class="bi bi-1-circle-fill"></i>Comp 1: Comunicativa
                </span>
                <span style="background:#ede9fe;color:#3b0764;">
                    <i class="bi bi-2-circle-fill"></i>Comp 2: Pensamiento Lógico
                </span>
                <span style="background:#d1fae5;color:#064e3b;">
                    <i class="bi bi-3-circle-fill"></i>Comp 3: Científica / Técnica
                </span>
                <span style="background:#fef3c7;color:#451a03;">
                    <i class="bi bi-4-circle-fill"></i>Comp 4: Ética / Ciudadana
                </span>
            </div>
            <div class="mt-3 alert alert-info d-flex gap-2 align-items-start"
                 style="border-radius:8px;font-size:.8rem;">
                <i class="bi bi-lightning-charge-fill flex-shrink-0 mt-1"></i>
                <div>
                    Los promedios de período (<code>avg_comp*_p*</code>), promedios de competencia
                    (<code>prom_comp*</code>) y la <strong>nota final</strong> se recalculan automáticamente
                    tras la importación.
                </div>
            </div>
        </div>

    </div>
</div>

@endsection

@push('scripts')
<script>
// ── Selector de asignación: actualiza inputs ocultos y links plantilla ──
const asignacionSel = document.getElementById('asignacion_sel');
const hdnAsignacion = document.getElementById('hdnAsignacion');
const btnCsv  = document.getElementById('btnCsv');
const btnXlsx = document.getElementById('btnXlsx');
const baseTemplate = '{{ route("admin.importaciones.calificaciones.plantilla") }}';

asignacionSel.addEventListener('change', function () {
    const id = this.value;
    hdnAsignacion.value = id;

    const qs = id ? `?asignacion_id=${id}` : '';
    btnCsv.href  = baseTemplate + qs + (id ? '&' : '?') + 'format=csv';
    btnXlsx.href = baseTemplate + qs + (id ? '&' : '?') + 'format=xlsx';
});

// ── Drop zone ──────────────────────────────────────────────────────────
const dropZone = document.getElementById('dropZone');
dropZone.addEventListener('dragover', e => { e.preventDefault(); dropZone.classList.add('dragover'); });
dropZone.addEventListener('dragleave', () => dropZone.classList.remove('dragover'));
dropZone.addEventListener('drop', e => {
    e.preventDefault();
    dropZone.classList.remove('dragover');
    if (e.dataTransfer.files.length) {
        document.getElementById('archivoInp').files = e.dataTransfer.files;
        onFileSelect(document.getElementById('archivoInp'));
    }
});

const fmtIcons = { csv: 'bi-filetype-csv', xlsx: 'bi-filetype-xlsx', xls: 'bi-filetype-xls', txt: 'bi-filetype-txt' };

function onFileSelect(input) {
    if (input.files && input.files[0]) {
        const file = input.files[0];
        const ext  = file.name.split('.').pop().toLowerCase();
        const size = (file.size / 1024).toFixed(1) + ' KB';
        dropZone.classList.add('has-file');
        dropZone.innerHTML =
            `<i class="bi ${fmtIcons[ext] || 'bi-file-earmark'} d-block mb-2"></i>` +
            `<p class="drop-label mb-1" style="font-size:.9rem;">${file.name}</p>` +
            `<p class="text-muted mb-0" style="font-size:.76rem;">${size} — listo para importar</p>`;
    }
}

// ── Validar antes de enviar ────────────────────────────────────────────
document.getElementById('importForm').addEventListener('submit', function (e) {
    if (! hdnAsignacion.value) {
        e.preventDefault();
        asignacionSel.focus();
        asignacionSel.classList.add('is-invalid');
        setTimeout(() => asignacionSel.classList.remove('is-invalid'), 2500);
        return;
    }
    const btn = document.getElementById('submitBtn');
    btn.disabled = true;
    document.getElementById('submitSpinner').classList.remove('d-none');
    document.getElementById('submitIcon').classList.add('d-none');
});
</script>
@endpush
