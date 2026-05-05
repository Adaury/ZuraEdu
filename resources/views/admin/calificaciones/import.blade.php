@extends('layouts.admin')
@section('page-title', 'Importar Calificaciones')

@push('styles')
<style>
    .import-card {
        background: #fff;
        border-radius: 12px;
        border: 1px solid #e5e7eb;
        padding: 1.75rem;
    }
    .import-card .card-section-title {
        font-size: .8rem;
        font-weight: 700;
        letter-spacing: .1em;
        text-transform: uppercase;
        color: var(--primary);
        border-bottom: 2px solid var(--primary);
        padding-bottom: .5rem;
        margin-bottom: 1.25rem;
        display: flex;
        align-items: center;
        gap: .5rem;
    }
    .csv-table th { background:var(--primary);color:#fff;font-size:.78rem;font-weight:600;white-space:nowrap; }
    .csv-table td { font-size:.82rem;vertical-align:middle; }
    .badge-req { font-size:.68rem;padding:.2rem .5rem;border-radius:20px;background:#fee2e2;color:#991b1b;font-weight:600; }
    .badge-opt { font-size:.68rem;padding:.2rem .5rem;border-radius:20px;background:#f3f4f6;color:#374151;font-weight:600; }
    .drop-zone {
        border: 2px dashed #d1d5db;
        border-radius: 10px;
        padding: 2rem 1rem;
        text-align: center;
        cursor: pointer;
        transition: border-color .2s, background .2s;
    }
    .drop-zone:hover, .drop-zone.dragover { border-color:var(--primary); background:#f0f4fb; }
    .drop-zone i { font-size:2.2rem; color:#9ca3af; }
    .drop-zone.has-file i { color:var(--primary); }
    .drop-zone.has-file .drop-label { color:var(--primary); font-weight:600; }
    .fmt-badges span {
        display:inline-flex; align-items:center; gap:.3rem;
        font-size:.72rem; font-weight:700; padding:.2rem .55rem;
        border-radius:20px; border:1px solid #d1d5db; color:#374151; background:#f9fafb;
    }

    /* ── Modo selector ── */
    .modo-selector { display:flex; gap:.75rem; margin-bottom:1.5rem; }
    .modo-btn {
        flex:1; border:2px solid #e5e7eb; border-radius:12px;
        padding:1.25rem 1rem; text-align:center; cursor:pointer;
        transition:all .2s; background:#fff;
    }
    .modo-btn:hover { border-color:var(--primary); background:#f0f4fb; }
    .modo-btn.active { border-color:var(--primary); background:#f0f4fb; }
    .modo-btn i { font-size:1.75rem; display:block; margin-bottom:.5rem; color:#9ca3af; }
    .modo-btn.active i { color:var(--primary); }
    .modo-btn .modo-title { font-size:.9rem; font-weight:700; color:#374151; }
    .modo-btn.active .modo-title { color:var(--primary); }
    .modo-btn .modo-desc { font-size:.76rem; color:#6b7280; margin-top:.2rem; }

    .sug-card { background:#f0fdf4; border:1px solid #bbf7d0; border-radius:10px; padding:1rem 1.1rem; margin-bottom:.75rem; }
    .sug-card .sug-title { font-size:.82rem; font-weight:700; color:#166534; margin-bottom:.3rem; }
    .sug-card p { font-size:.8rem; color:#374151; margin:0; }

    [data-theme="dark"] .import-card { background: #1e293b; border-color: #334155; }
    [data-theme="dark"] .modo-btn { background: #1e293b; border-color: #334155; }
    [data-theme="dark"] .modo-btn:hover, [data-theme="dark"] .modo-btn.active { background: #162032; border-color: var(--primary); }
    [data-theme="dark"] .modo-btn .modo-title { color: #e2e8f0; }
    [data-theme="dark"] .modo-btn.active .modo-title { color: #93c5fd; }
    [data-theme="dark"] .modo-btn .modo-desc { color: #64748b; }
    [data-theme="dark"] .sug-card { background: #052e16; border-color: #166534; }
    [data-theme="dark"] .sug-card p { color: #94a3b8; }
</style>
@endpush

@section('content')

{{-- Header --}}
<div class="d-flex align-items-center gap-3 mb-4">
    <a href="{{ route('admin.calificaciones.index') }}" class="btn btn-sm btn-outline-secondary" style="border-radius:8px;">
        <i class="bi bi-arrow-left me-1"></i>Volver
    </a>
    <div>
        <h1 class="mb-0" style="font-size:1.4rem;font-weight:800;color:var(--primary);">
            <i class="bi bi-journal-arrow-up me-2" style="color:var(--secondary);"></i>Ingresar Calificaciones
        </h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0" style="font-size:.78rem;">
                <li class="breadcrumb-item"><a href="{{ route('admin.calificaciones.index') }}" class="text-decoration-none">Calificaciones</a></li>
                <li class="breadcrumb-item active">Importar / Ingresar</li>
            </ol>
        </nav>
    </div>
</div>

{{-- Alerts --}}
@if(session('success'))
    <div class="alert alert-success d-flex align-items-center gap-2 mb-3" role="alert" style="border-radius:10px;font-size:.875rem;">
        <i class="bi bi-check-circle-fill fs-5"></i>
        <div>{{ session('success') }}</div>
    </div>
@endif

@if(session('errores_import') && count(session('errores_import')) > 0)
    <div class="alert alert-warning mb-3" style="border-radius:10px;font-size:.875rem;" role="alert">
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

<div class="row g-4">

    {{-- LEFT: Selección + Modo --}}
    <div class="col-lg-7">

        {{-- PASO 1: Selección de asignación y período --}}
        <div class="import-card mb-4">
            <div class="card-section-title">
                <i class="bi bi-1-circle"></i>Paso 1 — Seleccionar Asignación y Período
            </div>

            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label fw-semibold mb-1" style="font-size:.83rem;color:var(--primary);">
                        <i class="bi bi-journal-check me-1"></i>Asignación (Materia · Grupo) <span style="color:#991b1b;">*</span>
                    </label>
                    <select id="asignacion_id" class="form-select" style="border-radius:8px;" required>
                        <option value="">— Selecciona la materia y grupo —</option>
                        @foreach($asignaciones as $a)
                            <option value="{{ $a->id }}"
                                    data-area="{{ $a->area }}"
                                    data-grupo="{{ $a->grupo_id }}">
                                {{ $a->grupo->grado->nombre ?? '' }} {{ $a->grupo->seccion->nombre ?? '' }}
                                · {{ $a->asignatura->nombre ?? '—' }}
                                ({{ $a->area === 'academica' ? 'Académica' : 'Técnica' }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-6" id="periodoWrapper">
                    <label class="form-label fw-semibold mb-1" style="font-size:.83rem;color:var(--primary);">
                        <i class="bi bi-calendar3 me-1"></i>Período <span style="color:#6b7280;font-weight:400;">(para área técnica)</span>
                    </label>
                    <select id="periodo_id" class="form-select" style="border-radius:8px;">
                        <option value="">— Todos los períodos (académica) —</option>
                        @foreach($periodos as $p)
                            <option value="{{ $p->id }}" data-numero="{{ $p->numero }}">
                                Período {{ $p->numero }} {{ $p->nombre ? '– ' . $p->nombre : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-6 d-flex align-items-end">
                    <div class="alert alert-light border w-100 mb-0 py-2 px-3" style="border-radius:8px;font-size:.78rem;">
                        <strong>Año Escolar:</strong> {{ $schoolYear->nombre }}<br>
                        <span id="areaInfo" class="text-muted">Selecciona una asignación para ver el tipo de área</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- PASO 2: Modo de ingreso --}}
        <div class="import-card mb-4">
            <div class="card-section-title">
                <i class="bi bi-2-circle"></i>Paso 2 — Elige cómo ingresar las notas
            </div>

            <div class="modo-selector">
                <div class="modo-btn active" id="modoSistema" onclick="setModo('sistema')">
                    <i class="bi bi-grid-3x3-gap"></i>
                    <div class="modo-title">Ingresar en el sistema</div>
                    <div class="modo-desc">Usa la grilla interactiva del sistema</div>
                </div>
                <div class="modo-btn" id="modoArchivo" onclick="setModo('archivo')">
                    <i class="bi bi-file-earmark-arrow-up"></i>
                    <div class="modo-title">Subir archivo</div>
                    <div class="modo-desc">Importa notas desde CSV o Excel</div>
                </div>
            </div>

            {{-- Panel: modo sistema --}}
            <div id="panelSistema">
                <div class="alert alert-primary d-flex gap-2 align-items-start mb-3" style="border-radius:10px;font-size:.84rem;">
                    <i class="bi bi-grid-3x3-gap-fill flex-shrink-0 mt-1"></i>
                    <div>
                        La <strong>grilla interactiva</strong> del sistema permite ingresar notas directamente con validación
                        en tiempo real, cálculo automático de promedios y publicación controlada.
                        Es el método <strong>recomendado</strong> para el registro diario.
                    </div>
                </div>

                <a id="btnIrGrilla" href="{{ route('admin.calificaciones.index') }}"
                   class="btn fw-semibold w-100 py-2"
                   style="background:var(--primary);color:#fff;border-radius:8px;font-size:.95rem;">
                    <i class="bi bi-grid-3x3-gap me-2"></i>Ir a la Grilla de Calificaciones
                </a>

                <div class="mt-3 text-center text-muted" style="font-size:.78rem;">
                    La grilla calculará automáticamente la nota final según los pesos configurados.
                </div>
            </div>

            {{-- Panel: modo archivo --}}
            <div id="panelArchivo" style="display:none;">
                <form method="POST" enctype="multipart/form-data"
                      action="{{ route('admin.calificaciones.importStore') }}"
                      id="importForm">
                    @csrf
                    <input type="hidden" name="asignacion_id" id="hiddenAsignacion">
                    <input type="hidden" name="periodo_id" id="hiddenPeriodo">

                    <div class="fmt-badges mb-3 d-flex flex-wrap gap-1">
                        <span><i class="bi bi-filetype-csv text-success"></i> CSV</span>
                        <span><i class="bi bi-filetype-txt text-secondary"></i> TXT</span>
                        <span><i class="bi bi-filetype-xlsx text-success"></i> XLSX</span>
                        <span><i class="bi bi-filetype-xls text-success"></i> XLS</span>
                        <span style="background:#fffbeb;border-color:#fcd34d;color:#92400e;">
                            <i class="bi bi-info-circle"></i> delimitador auto-detectado
                        </span>
                    </div>

                    <div class="drop-zone mb-3" id="dropZone"
                         onclick="document.getElementById('archivoInput').click()">
                        <i class="bi bi-file-earmark-spreadsheet d-block mb-2"></i>
                        <p class="drop-label mb-1" style="font-size:.9rem;color:#6b7280;">
                            Haz clic o arrastra aquí tu archivo de notas
                        </p>
                        <p class="text-muted mb-0" style="font-size:.76rem;">
                            CSV · TXT · XLSX · XLS — Máx. 10 MB
                        </p>
                    </div>
                    <input type="file" id="archivoInput" name="archivo"
                           accept=".csv,.txt,.xlsx,.xls" class="d-none"
                           onchange="handleFileSelect(this)">

                    <div id="alertAreaAcademica"
                         class="alert alert-info d-flex gap-2 align-items-start mb-3 d-none"
                         style="border-radius:10px;font-size:.83rem;">
                        <i class="bi bi-info-circle-fill flex-shrink-0 mt-1"></i>
                        <div>
                            Para <strong>área académica</strong> el archivo debe incluir las 4 competencias por período:
                            <code>p1_comp1</code>, <code>p1_comp2</code>, <code>p1_comp3</code>, <code>p1_comp4</code> …
                            hasta <code>p4_comp4</code>. Descarga la plantilla Excel para ver el formato exacto.
                        </div>
                    </div>

                    <div id="alertAreaTecnica"
                         class="alert alert-info d-flex gap-2 align-items-start mb-3 d-none"
                         style="border-radius:10px;font-size:.83rem;">
                        <i class="bi bi-info-circle-fill flex-shrink-0 mt-1"></i>
                        <div>
                            Para <strong>área técnica</strong> el archivo incluye las columnas <code>ra1</code> a <code>ra<em>N</em></code>
                            (según los Resultados de Aprendizaje configurados) y <code>nota_final</code>. Descarga la plantilla para ver el formato exacto.
                        </div>
                    </div>

                    <div class="d-flex justify-content-end mt-3">
                        <button type="submit" class="btn px-4 fw-semibold"
                                style="background:var(--primary);color:#fff;border-radius:8px;"
                                id="submitBtn">
                            <span id="submitSpinner" class="spinner-border spinner-border-sm me-2 d-none" role="status"></span>
                            <i class="bi bi-cloud-arrow-up me-1" id="submitIcon"></i>
                            Subir e Importar Notas
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- RIGHT: Plantilla + Columnas + Protocolo --}}
    <div class="col-lg-5">

        {{-- Plantilla --}}
        <div class="import-card mb-4">
            <div class="card-section-title">
                <i class="bi bi-download"></i>Descargar Plantilla
            </div>
            <p class="text-muted mb-3" style="font-size:.82rem;">
                La plantilla se genera según la asignación seleccionada (técnica o académica) e incluye los estudiantes del grupo pre-cargados.
            </p>
            <div class="d-flex gap-2 flex-wrap mb-2">
                <a href="{{ route('admin.calificaciones.plantilla.descargar', ['format' => 'csv']) }}"
                   id="btnCsv"
                   class="btn btn-outline-success fw-semibold flex-fill" style="border-radius:8px;font-size:.85rem;">
                    <i class="bi bi-filetype-csv me-1"></i>Plantilla CSV
                </a>
                <a href="{{ route('admin.calificaciones.plantilla.descargar', ['format' => 'xlsx']) }}"
                   id="btnXlsx"
                   class="btn btn-outline-success fw-semibold flex-fill" style="border-radius:8px;font-size:.85rem;">
                    <i class="bi bi-filetype-xlsx me-1"></i>Plantilla Excel
                </a>
            </div>
            <div style="font-size:.74rem;color:#6b7280;">
                <i class="bi bi-lightbulb me-1"></i>
                Las columnas de <code>nombres</code> y <code>apellidos</code> son solo referencia — no se importan.
            </div>
        </div>

        {{-- Columnas según área --}}
        <div class="import-card mb-4">
            <div class="card-section-title">
                <i class="bi bi-table"></i>Columnas según el área
            </div>

            {{-- Común --}}
            <p style="font-size:.78rem;color:#6b7280;margin-bottom:.5rem;font-weight:600;">COLUMNAS COMUNES (todas las áreas)</p>
            <div class="table-responsive mb-3">
                <table class="table table-sm table-bordered csv-table mb-0">
                    <thead><tr><th style="width:42%">Columna</th><th>Descripción</th></tr></thead>
                    <tbody>
                        <tr><td><code>numero_matricula</code></td><td><span class="badge-req">recomendado</span></td></tr>
                        <tr><td><code>cedula</code></td><td><span class="badge-opt">alternativo</span></td></tr>
                        <tr><td><code>nombres</code></td><td><span class="badge-opt">referencia</span></td></tr>
                        <tr><td><code>apellidos</code></td><td><span class="badge-opt">referencia</span></td></tr>
                    </tbody>
                </table>
            </div>

            {{-- Técnica --}}
            <p style="font-size:.78rem;color:#6b7280;margin-bottom:.5rem;font-weight:600;">ÁREA TÉCNICA</p>
            <div class="table-responsive mb-3">
                <table class="table table-sm table-bordered csv-table mb-0">
                    <thead><tr><th>Columna</th><th>Descripción</th></tr></thead>
                    <tbody>
                        <tr><td><code>periodo</code></td><td><span class="badge-req">req.</span> — número 1 a 4</td></tr>
                        <tr><td><code>ra1</code> … <code>raN</code></td><td><span class="badge-opt">opcional</span> — notas por RA</td></tr>
                        <tr><td><code>nota_final</code></td><td><span class="badge-req">req.</span> — 0 a 100</td></tr>
                    </tbody>
                </table>
            </div>

            {{-- Académica --}}
            <p style="font-size:.78rem;color:#6b7280;margin-bottom:.5rem;font-weight:600;">ÁREA ACADÉMICA</p>
            <div class="table-responsive">
                <table class="table table-sm table-bordered csv-table mb-0">
                    <thead><tr><th>Columna</th><th>Descripción</th></tr></thead>
                    <tbody>
                        <tr><td><code>p1_comp1</code> … <code>p4_comp4</code></td><td>4 competencias × 4 períodos (16 columnas) — 0 a 100</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Protocolo --}}
        <div class="import-card">
            <div class="card-section-title">
                <i class="bi bi-shield-check"></i>Protocolo de Importación
            </div>

            <div class="sug-card">
                <div class="sug-title"><i class="bi bi-1-circle me-1"></i>Elige tu método</div>
                <p>Para ingreso regular, usa la <strong>grilla del sistema</strong>. El archivo es ideal para carga masiva desde registros externos o al inicio del año.</p>
            </div>
            <div class="sug-card" style="background:#eff6ff;border-color:#bfdbfe;">
                <div class="sug-title" style="color:#1e40af;"><i class="bi bi-2-circle me-1"></i>Descarga la plantilla primero</div>
                <p>La plantilla tiene el formato exacto esperado y los estudiantes del grupo pre-cargados. Evita errores de columnas.</p>
            </div>
            <div class="sug-card" style="background:#fffbeb;border-color:#fde68a;">
                <div class="sug-title" style="color:#92400e;"><i class="bi bi-3-circle me-1"></i>Las notas existentes se actualizan</div>
                <p>Si ya existe una nota para ese estudiante, asignación y período, se actualizará. No se crean duplicados.</p>
            </div>
            <div class="sug-card" style="background:#fdf4ff;border-color:#e9d5ff;">
                <div class="sug-title" style="color:#6b21a8;"><i class="bi bi-4-circle me-1"></i>Publica desde la grilla</div>
                <p>La importación guarda las notas como <strong>no publicadas</strong>. Ve a la grilla para revisar y publicar cuando estén listas.</p>
            </div>
        </div>

    </div>

</div>
@endsection

@push('scripts')
<script>
// ── Modo selector ──────────────────────────────────────
let modoActual = 'sistema';

function setModo(modo) {
    modoActual = modo;
    document.getElementById('modoSistema').classList.toggle('active', modo === 'sistema');
    document.getElementById('modoArchivo').classList.toggle('active', modo === 'archivo');
    document.getElementById('panelSistema').style.display = modo === 'sistema' ? '' : 'none';
    document.getElementById('panelArchivo').style.display = modo === 'archivo'  ? '' : 'none';
}

// ── Asignación change ──────────────────────────────────
document.getElementById('asignacion_id').addEventListener('change', function() {
    const opt  = this.options[this.selectedIndex];
    const id   = this.value;
    const area = opt.getAttribute('data-area') || '';

    // Update hidden inputs
    document.getElementById('hiddenAsignacion').value = id;

    // Update grilla link
    const grupoId = opt.getAttribute('data-grupo') || '';
    document.getElementById('btnIrGrilla').href =
        '{{ route("admin.calificaciones.grilla") }}' + (id ? `?asignacion_id=${id}` : '');

    // Update template links
    const periodoId = document.getElementById('periodo_id').value;
    const baseT = '{{ route("admin.calificaciones.plantilla.descargar") }}';
    const paramsBase = id ? `?asignacion_id=${id}` : '?';
    const paramsPer  = periodoId ? `&periodo_id=${periodoId}` : '';
    document.getElementById('btnCsv').href  = baseT + paramsBase + paramsPer + '&format=csv';
    document.getElementById('btnXlsx').href = baseT + paramsBase + paramsPer + '&format=xlsx';

    // Area info
    const areaInfo = document.getElementById('areaInfo');
    if (area === 'academica') {
        areaInfo.innerHTML = '<strong style="color:#047857;">Área Académica</strong> — 4 competencias × 4 períodos';
        document.getElementById('alertAreaAcademica').classList.remove('d-none');
        document.getElementById('alertAreaTecnica').classList.add('d-none');
    } else if (area === 'tecnica') {
        areaInfo.innerHTML = '<strong style="color:#1e3a6e;">Área Técnica</strong> — Resultados de Aprendizaje por período';
        document.getElementById('alertAreaTecnica').classList.remove('d-none');
        document.getElementById('alertAreaAcademica').classList.add('d-none');
    } else {
        areaInfo.textContent = 'Selecciona una asignación para ver el tipo de área';
        document.getElementById('alertAreaAcademica').classList.add('d-none');
        document.getElementById('alertAreaTecnica').classList.add('d-none');
    }
});

// ── Período change ──────────────────────────────────────
document.getElementById('periodo_id').addEventListener('change', function() {
    document.getElementById('hiddenPeriodo').value = this.value;
    // Trigger asignacion change to refresh template links
    document.getElementById('asignacion_id').dispatchEvent(new Event('change'));
});

// ── Drop zone ──────────────────────────────────────────
const dropZone = document.getElementById('dropZone');
if (dropZone) {
    dropZone.addEventListener('dragover', e => { e.preventDefault(); dropZone.classList.add('dragover'); });
    dropZone.addEventListener('dragleave', () => dropZone.classList.remove('dragover'));
    dropZone.addEventListener('drop', e => {
        e.preventDefault();
        dropZone.classList.remove('dragover');
        const files = e.dataTransfer.files;
        if (files.length) {
            document.getElementById('archivoInput').files = files;
            handleFileSelect(document.getElementById('archivoInput'));
        }
    });
}

const fmtIcons = { csv:'bi-filetype-csv', xlsx:'bi-filetype-xlsx', xls:'bi-filetype-xls', txt:'bi-filetype-txt' };

function handleFileSelect(input) {
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

// ── Spinner on submit ──────────────────────────────────
document.getElementById('importForm')?.addEventListener('submit', function(e) {
    const id = document.getElementById('asignacion_id').value;
    if (!id) {
        e.preventDefault();
        alert('Debes seleccionar una asignación antes de importar.');
        return;
    }
    document.getElementById('submitBtn').disabled = true;
    document.getElementById('submitSpinner').classList.remove('d-none');
    document.getElementById('submitIcon')?.classList.add('d-none');
});
</script>
@endpush
