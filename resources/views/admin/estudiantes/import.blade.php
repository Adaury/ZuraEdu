@extends('layouts.admin')
@section('page-title', 'Importar Estudiantes')

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
    .csv-table th {
        background: var(--primary);
        color: #fff;
        font-size: .78rem;
        font-weight: 600;
        white-space: nowrap;
    }
    .csv-table td {
        font-size: .82rem;
        vertical-align: middle;
    }
    .csv-table .badge-req {
        font-size: .68rem; padding: .2rem .5rem; border-radius: 20px;
        background: #fee2e2; color: #991b1b; font-weight: 600;
    }
    .csv-table .badge-opt {
        font-size: .68rem; padding: .2rem .5rem; border-radius: 20px;
        background: #f3f4f6; color: #374151; font-weight: 600;
    }
    .drop-zone {
        border: 2px dashed #d1d5db;
        border-radius: 10px;
        padding: 2rem 1rem;
        text-align: center;
        cursor: pointer;
        transition: border-color .2s, background .2s;
    }
    .drop-zone:hover, .drop-zone.dragover { border-color: var(--primary); background: #f0f4fb; }
    .drop-zone i { font-size: 2.2rem; color: #9ca3af; }
    .drop-zone.has-file i { color: var(--primary); }
    .drop-zone.has-file .drop-label { color: var(--primary); font-weight: 600; }
    .fmt-badges span {
        display: inline-flex; align-items: center; gap: .3rem;
        font-size: .72rem; font-weight: 700; padding: .2rem .55rem;
        border-radius: 20px; border: 1px solid #d1d5db; color: #374151;
        background: #f9fafb;
    }
    /* Sugerencias */
    .sug-card {
        background: #f0fdf4;
        border: 1px solid #bbf7d0;
        border-radius: 10px;
        padding: 1rem 1.1rem;
        margin-bottom: .75rem;
    }
    .sug-card .sug-title { font-size: .82rem; font-weight: 700; color: #166534; margin-bottom: .3rem; }
    .sug-card p { font-size: .8rem; color: #374151; margin: 0; }
    [data-theme="dark"] .import-card { background: #1e293b; border-color: #334155; }
    [data-theme="dark"] .sug-card { background: #052e16; border-color: #166534; }
    [data-theme="dark"] .sug-card p { color: #94a3b8; }
</style>
@endpush

@section('content')

@php
    $backParams = array_filter(['ciclo' => $ciclo ?? null, 'area' => $area ?? null]);
@endphp

{{-- Page header --}}
<div class="d-flex align-items-center gap-3 mb-4">
    <a href="{{ route('admin.estudiantes.index', $backParams) }}"
       class="btn btn-sm btn-outline-secondary" style="border-radius:8px;">
        <i class="bi bi-arrow-left me-1"></i>Volver
    </a>
    <div>
        <h1 class="mb-0" style="font-size:1.4rem;font-weight:800;color:var(--primary);">
            <i class="bi bi-file-earmark-arrow-up me-2" style="color:var(--secondary);"></i>Importar Estudiantes
            @isset($contexto)
                <span class="badge ms-2 px-2 py-1" style="font-size:.7rem;background:var(--primary-light,#dbeafe);color:var(--primary);border-radius:8px;font-weight:700;vertical-align:middle;">{{ $contexto }}</span>
            @endisset
        </h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0" style="font-size:.78rem;">
                <li class="breadcrumb-item">
                    <a href="{{ route('admin.estudiantes.index', $backParams) }}" class="text-decoration-none">Estudiantes</a>
                </li>
                <li class="breadcrumb-item active">Importar</li>
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
    @php $erroresImport = session('errores_import'); @endphp
    <div class="alert alert-warning mb-3" style="border-radius:10px;font-size:.875rem;" role="alert">
        <div class="d-flex align-items-center gap-2 mb-1">
            <i class="bi bi-exclamation-triangle-fill fs-5"></i>
            <strong>Se encontraron {{ count($erroresImport) }} advertencia(s) durante la importación:</strong>
            <button class="btn btn-sm btn-link ms-auto p-0 text-warning-emphasis"
                    type="button" data-bs-toggle="collapse" data-bs-target="#erroresImportList">
                Ver detalles <i class="bi bi-chevron-down"></i>
            </button>
        </div>
        <div class="collapse" id="erroresImportList">
            <ul class="mb-0 mt-2 ps-3" style="font-size:.82rem;">
                @foreach($erroresImport as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    </div>
@endif

<div class="row g-4">

    {{-- ── LEFT: Upload form ──────────────────────── --}}
    <div class="col-lg-7">
        <div class="import-card h-100">
            <div class="card-section-title">
                <i class="bi bi-upload"></i>Subir Archivo
            </div>

            <form method="POST" enctype="multipart/form-data"
                  action="{{ route('admin.estudiantes.importPreview') }}"
                  id="importForm">
                @csrf
                @if(!empty($ciclo))
                    <input type="hidden" name="ciclo" value="{{ $ciclo }}">
                @endif
                @if(!empty($area))
                    <input type="hidden" name="area" value="{{ $area }}">
                @endif

                {{-- Formatos aceptados --}}
                <div class="fmt-badges mb-3 d-flex flex-wrap gap-1">
                    <span><i class="bi bi-filetype-csv text-success"></i> CSV</span>
                    <span><i class="bi bi-filetype-txt text-secondary"></i> TXT</span>
                    <span><i class="bi bi-filetype-xlsx text-success"></i> XLSX</span>
                    <span><i class="bi bi-filetype-xls text-success"></i> XLS</span>
                    <span style="background:#fffbeb;border-color:#fcd34d;color:#92400e;">
                        <i class="bi bi-magic"></i> separador auto-detectado:
                        <code style="font-size:.68rem;">,</code>
                        <code style="font-size:.68rem;">;</code>
                        <code style="font-size:.68rem;">Tab</code>
                        <code style="font-size:.68rem;">|</code>
                    </span>
                </div>

                {{-- Info alert --}}
                <div class="alert alert-info d-flex gap-2 align-items-start mb-4" style="border-radius:10px;font-size:.84rem;">
                    <i class="bi bi-info-circle-fill flex-shrink-0 mt-1"></i>
                    <div>
                        El archivo puede ser <strong>CSV, TXT, TSV o Excel (.xlsx/.xls)</strong>.
                        La primera fila debe contener los nombres de las columnas.
                        El número de matrícula se genera automáticamente si no se incluye.
                    </div>
                </div>

                {{-- Drop zone --}}
                <div class="drop-zone mb-3" id="dropZone"
                     onclick="document.getElementById('archivoInput').click()">
                    <i class="bi bi-file-earmark-spreadsheet d-block mb-2"></i>
                    <p class="drop-label mb-1" style="font-size:.9rem;color:#6b7280;">
                        Haz clic o arrastra aquí tu archivo
                    </p>
                    <p class="text-muted mb-0" style="font-size:.76rem;">
                        CSV · TXT · XLSX · XLS · ODS — Tamaño máximo: 10 MB
                    </p>
                </div>
                <input type="file"
                       id="archivoInput"
                       name="archivo"
                       accept=".csv,.txt,.xlsx,.xls,.ods"
                       class="d-none @error('archivo') is-invalid @enderror"
                       onchange="handleFileSelect(this)">
                @error('archivo')
                    <div class="text-danger mb-2" style="font-size:.78rem;">
                        <i class="bi bi-x-circle me-1"></i>{{ $message }}
                    </div>
                @enderror

                {{-- Submit --}}
                <div class="d-flex justify-content-end mt-4">
                    <button type="submit"
                            class="btn px-4 fw-semibold"
                            style="background:var(--primary);color:#fff;border-radius:8px;"
                            id="submitBtn">
                        <span id="submitSpinner" class="spinner-border spinner-border-sm me-2 d-none"
                              role="status" aria-hidden="true"></span>
                        <i class="bi bi-eye me-1" id="submitIcon"></i>
                        Previsualizar archivo
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ── RIGHT: Template + Instructions ──────────── --}}
    <div class="col-lg-5">

        {{-- Plantilla descargable --}}
        <div class="import-card mb-4">
            <div class="card-section-title">
                <i class="bi bi-download"></i>Descargar Plantilla
            </div>
            <p class="text-muted mb-3" style="font-size:.82rem;">
                Descarga la plantilla con el mismo formato de la lista escolar: <strong>No., Sección, Apellidos, Nombres</strong> + columnas opcionales.
                Elige el formato que prefieras:
            </p>
            <div class="d-flex gap-2 flex-wrap">
                <a href="{{ route('admin.estudiantes.plantilla.descargar', ['format' => 'csv']) }}"
                   class="btn btn-outline-success fw-semibold flex-fill"
                   style="border-radius:8px;font-size:.85rem;">
                    <i class="bi bi-filetype-csv me-1"></i>Plantilla CSV
                </a>
                <a href="{{ route('admin.estudiantes.plantilla.descargar', ['format' => 'xlsx']) }}"
                   class="btn btn-outline-success fw-semibold flex-fill"
                   style="border-radius:8px;font-size:.85rem;">
                    <i class="bi bi-filetype-xlsx me-1"></i>Plantilla Excel
                </a>
            </div>
            <div class="mt-2" style="font-size:.74rem;color:#6b7280;">
                <i class="bi bi-lightbulb me-1"></i>
                La plantilla CSV es compatible con Excel, Google Sheets y LibreOffice.
            </div>
        </div>

        {{-- Columnas --}}
        <div class="import-card mb-4">
            <div class="card-section-title">
                <i class="bi bi-table"></i>Columnas del archivo
            </div>
            <p class="text-muted mb-2" style="font-size:.78rem;">
                Las columnas con fondo azul son <strong>básicas</strong> (igual que la lista escolar).
                Las grises son opcionales. Los nombres con tilde también son aceptados.
            </p>
            <div class="table-responsive">
                <table class="table table-sm table-bordered csv-table mb-0">
                    <thead>
                        <tr>
                            <th style="width:46%">Columna</th>
                            <th>Descripción / Valores</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr style="background:#eff6ff;">
                            <td><code>No.</code></td>
                            <td><span class="badge-opt">informativo</span> — se ignora</td>
                        </tr>
                        <tr style="background:#eff6ff;">
                            <td><code>Sección</code></td>
                            <td><span class="badge-opt">informativo</span> — se ignora</td>
                        </tr>
                        <tr style="background:#eff6ff;">
                            <td><code>Apellidos</code></td>
                            <td><span class="badge-req">obligatorio</span></td>
                        </tr>
                        <tr style="background:#eff6ff;">
                            <td><code>Nombres</code></td>
                            <td><span class="badge-req">obligatorio</span></td>
                        </tr>
                        <tr>
                            <td><code>Fecha de Nacimiento</code></td>
                            <td>
                                <span class="badge-opt">opcional</span>
                                <code>AAAA-MM-DD</code> · <code>DD/MM/AAAA</code> · serial Excel
                            </td>
                        </tr>
                        <tr><td><code>Cédula</code></td><td><span class="badge-opt">opcional</span> — omite duplicados</td></tr>
                        <tr><td><code>Sexo</code></td><td><code>M</code> o <code>F</code></td></tr>
                        <tr><td><code>Nacionalidad</code></td><td><span class="badge-opt">opcional</span></td></tr>
                        <tr><td><code>Teléfono</code></td><td><span class="badge-opt">opcional</span></td></tr>
                        <tr><td><code>Email</code></td><td><span class="badge-opt">opcional</span></td></tr>
                        <tr><td><code>Dirección</code></td><td><span class="badge-opt">opcional</span></td></tr>
                        <tr><td><code>Sector</code></td><td><span class="badge-opt">opcional</span></td></tr>
                        <tr><td><code>Municipio</code></td><td><span class="badge-opt">opcional</span></td></tr>
                        <tr><td><code>Provincia</code></td><td><span class="badge-opt">opcional</span></td></tr>
                        <tr><td><code>Nombre del Tutor</code></td><td><span class="badge-opt">opcional</span></td></tr>
                        <tr><td><code>Tel. Tutor</code></td><td><span class="badge-opt">opcional</span></td></tr>
                        <tr><td><code>Estado</code></td><td><code>activo</code> / <code>inactivo</code> / <code>egresado</code> / <code>transferido</code></td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Sugerencias de mejora --}}
        <div class="import-card">
            <div class="card-section-title">
                <i class="bi bi-lightbulb"></i>Sugerencias para una mejor importación
            </div>

            <div class="sug-card">
                <div class="sug-title"><i class="bi bi-check2-circle me-1"></i>Verifica la cédula antes de importar</div>
                <p>Si el estudiante ya existe con la misma cédula, la fila será omitida automáticamente. Revisa duplicados primero con el listado actual.</p>
            </div>

            <div class="sug-card" style="background:#eff6ff;border-color:#bfdbfe;">
                <div class="sug-title" style="color:#1e40af;"><i class="bi bi-file-earmark-excel me-1"></i>Usa Excel para preparar el archivo</div>
                <p>Prepara tus datos en Excel o Google Sheets y expórtalos como CSV (UTF-8). Evita fórmulas, formatos especiales o celdas combinadas.</p>
            </div>

            <div class="sug-card" style="background:#fffbeb;border-color:#fde68a;">
                <div class="sug-title" style="color:#92400e;"><i class="bi bi-calendar-date me-1"></i>Fecha de nacimiento (opcional)</div>
                <p>
                    La fecha de nacimiento es opcional. Si se incluye, el sistema la detecta automáticamente:
                    <code>AAAA-MM-DD</code> (ej: <code>2010-05-15</code>) o
                    <code>DD/MM/AAAA</code> (ej: <code>15/05/2010</code>).
                    Si no se incluye, el campo quedará vacío y podrás completarlo luego desde el perfil del estudiante.
                </p>
            </div>

            <div class="sug-card" style="background:#fdf4ff;border-color:#e9d5ff;">
                <div class="sug-title" style="color:#6b21a8;"><i class="bi bi-people me-1"></i>Importa por grupos</div>
                <p>Puedes importar todos los estudiantes del plantel en un solo archivo. Luego matricúlalos en sus grupos desde la sección de <strong>Matrículas</strong>.</p>
            </div>

            <div class="sug-card" style="background:#fff1f2;border-color:#fecaca;">
                <div class="sug-title" style="color:#991b1b;"><i class="bi bi-shield-check me-1"></i>Datos del tutor</div>
                <p>Las columnas <code>Nombre del Tutor</code> y <code>Tel. Tutor</code> son opcionales, pero se recomienda incluirlos para facilitar las comunicaciones.</p>
            </div>
        </div>

    </div>

</div>
@endsection

@push('scripts')
<script>
// ── Drop zone UX ──────────────────────────────────
const dropZone = document.getElementById('dropZone');

dropZone.addEventListener('dragover', e => {
    e.preventDefault();
    dropZone.classList.add('dragover');
});
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

const formatIcons = {
    csv: 'bi-filetype-csv',
    xlsx: 'bi-filetype-xlsx',
    xls: 'bi-filetype-xls',
    ods: 'bi-filetype-raw',
    txt: 'bi-filetype-txt',
};

function handleFileSelect(input) {
    if (input.files && input.files[0]) {
        const file = input.files[0];
        const ext  = file.name.split('.').pop().toLowerCase();
        const icon = formatIcons[ext] || 'bi-file-earmark';
        const size = (file.size / 1024).toFixed(1) + ' KB';
        dropZone.classList.add('has-file');
        dropZone.innerHTML =
            `<i class="bi ${icon} d-block mb-2"></i>` +
            `<p class="drop-label mb-1" style="font-size:.9rem;">${file.name}</p>` +
            `<p class="text-muted mb-0" style="font-size:.76rem;">${size} — listo para importar</p>`;
    }
}

// ── Spinner on submit ─────────────────────────────
document.getElementById('importForm').addEventListener('submit', function() {
    const btn     = document.getElementById('submitBtn');
    const spinner = document.getElementById('submitSpinner');
    const icon    = document.getElementById('submitIcon');
    btn.disabled = true;
    spinner.classList.remove('d-none');
    if (icon) icon.classList.add('d-none');
});
</script>
@endpush
