@extends('layouts.admin')
@section('page-title', 'Importar Asistencia')

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
    .csv-table td { font-size: .82rem; vertical-align: middle; }
    .badge-req { font-size:.68rem; padding:.2rem .5rem; border-radius:20px; background:#fee2e2; color:#991b1b; font-weight:600; }
    .badge-opt { font-size:.68rem; padding:.2rem .5rem; border-radius:20px; background:#f3f4f6; color:#374151; font-weight:600; }
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
        border-radius: 20px; border: 1px solid #d1d5db; color: #374151; background: #f9fafb;
    }
    .estado-badge {
        display: inline-block; font-size: .7rem; font-weight: 600; padding: .15rem .5rem;
        border-radius: 20px; margin: .1rem;
    }
    .sug-card {
        background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 10px;
        padding: 1rem 1.1rem; margin-bottom: .75rem;
    }
    .sug-card .sug-title { font-size:.82rem; font-weight:700; color:#166534; margin-bottom:.3rem; }
    .sug-card p { font-size:.8rem; color:#374151; margin:0; }
    [data-theme="dark"] .import-card { background: #1e293b; border-color: #334155; }
    [data-theme="dark"] .sug-card { background: #052e16; border-color: #166534; }
    [data-theme="dark"] .sug-card p { color: #94a3b8; }
</style>
@endpush

@section('content')

{{-- Header --}}
<div class="d-flex align-items-center gap-3 mb-4">
    <a href="{{ route('admin.asistencia.index') }}" class="btn btn-sm btn-outline-secondary" style="border-radius:8px;">
        <i class="bi bi-arrow-left me-1"></i>Volver
    </a>
    <div>
        <h1 class="mb-0" style="font-size:1.4rem;font-weight:800;color:var(--primary);">
            <i class="bi bi-file-earmark-arrow-up me-2" style="color:var(--secondary);"></i>Importar Asistencia
        </h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0" style="font-size:.78rem;">
                <li class="breadcrumb-item"><a href="{{ route('admin.asistencia.index') }}" class="text-decoration-none">Asistencia</a></li>
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
    <div class="alert alert-warning mb-3" style="border-radius:10px;font-size:.875rem;" role="alert">
        <div class="d-flex align-items-center gap-2 mb-1">
            <i class="bi bi-exclamation-triangle-fill fs-5"></i>
            <strong>{{ count(session('errores_import')) }} advertencia(s) durante la importación:</strong>
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

    {{-- LEFT: Form --}}
    <div class="col-lg-7">
        <div class="import-card h-100">
            <div class="card-section-title">
                <i class="bi bi-upload"></i>Subir Archivo de Asistencia
            </div>

            <form method="POST" enctype="multipart/form-data"
                  action="{{ route('admin.asistencia.importStore') }}"
                  id="importForm">
                @csrf

                {{-- Selección de asignación --}}
                <div class="mb-4 p-3 rounded-3" style="background:#f0f4fb;border:1px solid #c7d2fe;">
                    <label class="form-label fw-semibold mb-1" style="font-size:.85rem;color:var(--primary);">
                        <i class="bi bi-journal-check me-1"></i>Asignación (Materia · Grupo)
                        <span style="color:#991b1b;">*</span>
                    </label>
                    <select name="asignacion_id" id="asignacion_id"
                            class="form-select @error('asignacion_id') is-invalid @enderror"
                            style="border-radius:8px;" required>
                        <option value="">— Selecciona la materia y grupo —</option>
                        @foreach($asignaciones as $a)
                            <option value="{{ $a->id }}" {{ old('asignacion_id') == $a->id ? 'selected' : '' }}>
                                {{ $a->grupo->grado->nombre ?? '' }} {{ $a->grupo->seccion->nombre ?? '' }}
                                · {{ $a->asignatura->nombre ?? '—' }}
                                @if($a->docente) ({{ $a->docente->nombres }}) @endif
                            </option>
                        @endforeach
                    </select>
                    @error('asignacion_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Formatos --}}
                <div class="fmt-badges mb-3 d-flex flex-wrap gap-1">
                    <span><i class="bi bi-filetype-csv text-success"></i> CSV</span>
                    <span><i class="bi bi-filetype-txt text-secondary"></i> TXT</span>
                    <span><i class="bi bi-filetype-xlsx text-success"></i> XLSX</span>
                    <span><i class="bi bi-filetype-xls text-success"></i> XLS</span>
                    <span style="background:#fffbeb;border-color:#fcd34d;color:#92400e;">
                        <i class="bi bi-info-circle"></i> delimitador auto-detectado
                    </span>
                </div>

                {{-- Drop zone --}}
                <div class="drop-zone mb-3" id="dropZone"
                     onclick="document.getElementById('archivoInput').click()">
                    <i class="bi bi-file-earmark-spreadsheet d-block mb-2"></i>
                    <p class="drop-label mb-1" style="font-size:.9rem;color:#6b7280;">
                        Haz clic o arrastra aquí tu archivo
                    </p>
                    <p class="text-muted mb-0" style="font-size:.76rem;">
                        CSV · TXT · XLSX · XLS — Tamaño máximo: 5 MB
                    </p>
                </div>
                <input type="file" id="archivoInput" name="archivo"
                       accept=".csv,.txt,.xlsx,.xls"
                       class="d-none @error('archivo') is-invalid @enderror"
                       onchange="handleFileSelect(this)">
                @error('archivo')
                    <div class="text-danger mb-2" style="font-size:.78rem;">
                        <i class="bi bi-x-circle me-1"></i>{{ $message }}
                    </div>
                @enderror

                {{-- Estados de asistencia info --}}
                <div class="alert alert-info d-flex gap-2 align-items-start mb-4" style="border-radius:10px;font-size:.83rem;">
                    <i class="bi bi-info-circle-fill flex-shrink-0 mt-1"></i>
                    <div>
                        <strong>Valores válidos para la columna <code>estado</code>:</strong><br>
                        <span class="estado-badge" style="background:#dcfce7;color:#166534;">presente</span>
                        <span class="estado-badge" style="background:#fee2e2;color:#991b1b;">ausente</span>
                        <span class="estado-badge" style="background:#fef9c3;color:#854d0e;">tardanza</span>
                        <span class="estado-badge" style="background:#e0f2fe;color:#075985;">excusa</span>
                        <span class="estado-badge" style="background:#f3e8ff;color:#6b21a8;">retiro</span>
                        <span class="ms-1" style="color:#4b5563;">Si se omite, se registra como <code>presente</code>.</span>
                    </div>
                </div>

                {{-- Submit --}}
                <div class="d-flex justify-content-end mt-4">
                    <button type="submit" class="btn px-4 fw-semibold"
                            style="background:var(--primary);color:#fff;border-radius:8px;"
                            id="submitBtn">
                        <span id="submitSpinner" class="spinner-border spinner-border-sm me-2 d-none" role="status"></span>
                        <i class="bi bi-cloud-arrow-up me-1" id="submitIcon"></i>
                        Subir e Importar Asistencia
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- RIGHT: Plantilla + Columnas --}}
    <div class="col-lg-5">

        {{-- Plantilla descargable --}}
        <div class="import-card mb-4">
            <div class="card-section-title">
                <i class="bi bi-download"></i>Descargar Plantilla
            </div>
            <p class="text-muted mb-3" style="font-size:.82rem;">
                Descarga la plantilla para la asignación seleccionada. Si has elegido una asignación arriba, la plantilla
                incluirá los estudiantes del grupo pre-cargados.
            </p>
            <div class="d-flex gap-2 flex-wrap mb-2" id="templateBtns">
                <a href="{{ route('admin.asistencia.plantilla.descargar', ['format' => 'csv']) }}"
                   id="btnCsv"
                   class="btn btn-outline-success fw-semibold flex-fill" style="border-radius:8px;font-size:.85rem;">
                    <i class="bi bi-filetype-csv me-1"></i>Plantilla CSV
                </a>
                <a href="{{ route('admin.asistencia.plantilla.descargar', ['format' => 'xlsx']) }}"
                   id="btnXlsx"
                   class="btn btn-outline-success fw-semibold flex-fill" style="border-radius:8px;font-size:.85rem;">
                    <i class="bi bi-filetype-xlsx me-1"></i>Plantilla Excel
                </a>
            </div>
            <div style="font-size:.74rem;color:#6b7280;">
                <i class="bi bi-lightbulb me-1"></i>
                La plantilla con el grupo seleccionado incluye los estudiantes activos pre-cargados.
            </div>
        </div>

        {{-- Columnas --}}
        <div class="import-card mb-4">
            <div class="card-section-title">
                <i class="bi bi-table"></i>Columnas del archivo
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-bordered csv-table mb-0">
                    <thead>
                        <tr>
                            <th style="width:42%">Columna</th>
                            <th>Descripción / Valores</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><code>numero_matricula</code></td>
                            <td><span class="badge-req">recomendado</span> — identifica al estudiante</td>
                        </tr>
                        <tr>
                            <td><code>cedula</code></td>
                            <td><span class="badge-opt">alternativo</span> — si no hay número de matrícula</td>
                        </tr>
                        <tr>
                            <td><code>nombres</code></td>
                            <td><span class="badge-opt">referencia</span> — no se importa</td>
                        </tr>
                        <tr>
                            <td><code>apellidos</code></td>
                            <td><span class="badge-opt">referencia</span> — no se importa</td>
                        </tr>
                        <tr>
                            <td><code>fecha</code></td>
                            <td><span class="badge-req">obligatorio</span> — formato <code>AAAA-MM-DD</code></td>
                        </tr>
                        <tr>
                            <td><code>estado</code></td>
                            <td><span class="badge-req">obligatorio</span> — <code>presente</code> / <code>ausente</code> / <code>tardanza</code> / <code>excusa</code> / <code>retiro</code></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Sugerencias --}}
        <div class="import-card">
            <div class="card-section-title">
                <i class="bi bi-lightbulb"></i>Protocolo de Importación
            </div>

            <div class="sug-card">
                <div class="sug-title"><i class="bi bi-1-circle me-1"></i>Descarga la plantilla del grupo</div>
                <p>Selecciona la asignación primero, luego descarga la plantilla. Vendrá con los estudiantes del grupo pre-cargados y sólo necesitas llenar <code>fecha</code> y <code>estado</code>.</p>
            </div>

            <div class="sug-card" style="background:#eff6ff;border-color:#bfdbfe;">
                <div class="sug-title" style="color:#1e40af;"><i class="bi bi-2-circle me-1"></i>Una fila por estudiante por fecha</div>
                <p>Cada fila representa un registro de asistencia (un estudiante en una fecha). Puedes mezclar varias fechas en el mismo archivo.</p>
            </div>

            <div class="sug-card" style="background:#fffbeb;border-color:#fde68a;">
                <div class="sug-title" style="color:#92400e;"><i class="bi bi-3-circle me-1"></i>Los registros existentes se actualizan</div>
                <p>Si ya existe un registro para esa combinación de estudiante + fecha + asignación, se actualizará con el nuevo estado. No se crean duplicados.</p>
            </div>

            <div class="sug-card" style="background:#fdf4ff;border-color:#e9d5ff;">
                <div class="sug-title" style="color:#6b21a8;"><i class="bi bi-4-circle me-1"></i>Formato de fecha obligatorio</div>
                <p>Usa siempre <code>AAAA-MM-DD</code> (ej: <code>{{ now()->format('Y-m-d') }}</code>). Excel puede cambiar el formato — verifica antes de guardar.</p>
            </div>
        </div>

    </div>

</div>
@endsection

@push('scripts')
<script>
// ── Drop zone ──────────────────────────────────
const dropZone = document.getElementById('dropZone');

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

const formatIcons = { csv:'bi-filetype-csv', xlsx:'bi-filetype-xlsx', xls:'bi-filetype-xls', txt:'bi-filetype-txt' };

function handleFileSelect(input) {
    if (input.files && input.files[0]) {
        const file = input.files[0];
        const ext  = file.name.split('.').pop().toLowerCase();
        const size = (file.size / 1024).toFixed(1) + ' KB';
        dropZone.classList.add('has-file');
        dropZone.innerHTML =
            `<i class="bi ${formatIcons[ext] || 'bi-file-earmark'} d-block mb-2"></i>` +
            `<p class="drop-label mb-1" style="font-size:.9rem;">${file.name}</p>` +
            `<p class="text-muted mb-0" style="font-size:.76rem;">${size} — listo para importar</p>`;
    }
}

// ── Update template links when asignación changes ──
document.getElementById('asignacion_id').addEventListener('change', function() {
    const id = this.value;
    const base = '{{ route("admin.asistencia.plantilla.descargar") }}';
    document.getElementById('btnCsv').href  = base + (id ? `?asignacion_id=${id}&format=csv`  : '?format=csv');
    document.getElementById('btnXlsx').href = base + (id ? `?asignacion_id=${id}&format=xlsx` : '?format=xlsx');
});

// ── Spinner on submit ──
document.getElementById('importForm').addEventListener('submit', function() {
    const btn = document.getElementById('submitBtn');
    btn.disabled = true;
    document.getElementById('submitSpinner').classList.remove('d-none');
    document.getElementById('submitIcon')?.classList.add('d-none');
});
</script>
@endpush
