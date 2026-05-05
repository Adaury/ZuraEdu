@extends('layouts.admin')
@section('page-title', 'Importar Docentes')

@push('styles')
<style>
    .import-card {
        background: #fff;
        border-radius: 12px;
        border: 1px solid #e5e7eb;
        padding: 1.75rem;
    }
    .card-section-title {
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
    .drop-zone:hover, .drop-zone.dragover { border-color: var(--primary); background: #f0f4fb; }
    .drop-zone i { font-size: 2.2rem; color: #9ca3af; }
    .drop-zone.has-file i { color: var(--primary); }
    .drop-zone.has-file .drop-label { color: var(--primary); font-weight: 600; }
    .fmt-badges span {
        display: inline-flex; align-items: center; gap: .3rem;
        font-size: .72rem; font-weight: 700; padding: .2rem .55rem;
        border-radius: 20px; border: 1px solid #d1d5db; color: #374151; background: #f9fafb;
    }

    /* Tabla de contraseñas temporales */
    .pass-table th { background:#166534;color:#fff;font-size:.76rem;padding:.45rem .6rem; }
    .pass-table td { font-size:.8rem;padding:.4rem .6rem;vertical-align:middle; }
    .pass-code { font-family:monospace;background:#f0fdf4;border:1px solid #86efac;padding:.15rem .4rem;border-radius:5px;font-weight:700;color:#166534;font-size:.85rem; }
    [data-theme="dark"] .import-card { background: #1e293b; border-color: #334155; }
    [data-theme="dark"] .pass-code { background: #052e16; border-color: #166534; color: #4ade80; }
</style>
@endpush

@section('content')

{{-- Encabezado --}}
<div class="d-flex align-items-center gap-3 mb-4">
    <a href="{{ route('admin.docentes.index') }}"
       class="btn btn-sm btn-outline-secondary" style="border-radius:8px;">
        <i class="bi bi-arrow-left me-1"></i>Volver
    </a>
    <div>
        <h1 class="mb-0" style="font-size:1.4rem;font-weight:800;color:var(--primary);">
            <i class="bi bi-file-earmark-arrow-up me-2" style="color:var(--secondary);"></i>Importar Docentes
        </h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0" style="font-size:.78rem;">
                <li class="breadcrumb-item">
                    <a href="{{ route('admin.docentes.index') }}" class="text-decoration-none">Docentes</a>
                </li>
                <li class="breadcrumb-item active">Importar</li>
            </ol>
        </nav>
    </div>
</div>

{{-- Alerta de éxito --}}
@if(session('success'))
    <div class="alert alert-success d-flex align-items-center gap-2 mb-3"
         role="alert" style="border-radius:10px;font-size:.875rem;">
        <i class="bi bi-check-circle-fill fs-5"></i>
        <div>{{ session('success') }}</div>
    </div>
@endif

{{-- Contraseñas temporales creadas --}}
@if(session('cuentas_creadas') && count(session('cuentas_creadas')) > 0)
    @php $cuentas = session('cuentas_creadas'); @endphp
    <div class="import-card mb-4" style="border-color:#86efac;background:#f0fdf4;">
        <div class="card-section-title" style="color:#166534;border-color:#86efac;">
            <i class="bi bi-key-fill"></i>Cuentas de acceso creadas — Contraseñas temporales
        </div>
        <p style="font-size:.82rem;color:#166534;" class="mb-3">
            <i class="bi bi-info-circle me-1"></i>
            Guarda estas contraseñas y entrégaselas a cada docente.
            Al iniciar sesión por primera vez, el sistema les pedirá que la cambien.
        </p>
        <div class="table-responsive">
            <table class="table table-sm table-bordered pass-table mb-2">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Docente</th>
                        <th>Email</th>
                        <th>Contraseña Temporal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($cuentas as $nombre => $info)
                        <tr>
                            <td class="text-muted" style="font-size:.75rem;">{{ $loop->iteration }}</td>
                            <td class="fw-semibold">{{ $nombre }}</td>
                            <td style="font-size:.8rem;">{{ $info['email'] }}</td>
                            <td>
                                <code class="pass-code">{{ $info['pass'] }}</code>
                                <button type="button" class="btn btn-sm ms-2"
                                        style="background:#dcfce7;border:none;border-radius:5px;padding:.1rem .4rem;font-size:.7rem;"
                                        onclick="copiar('{{ $info['pass'] }}', this)"
                                        title="Copiar contraseña">
                                    <i class="bi bi-clipboard"></i>
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <p class="text-muted mb-0" style="font-size:.74rem;">
            <i class="bi bi-exclamation-triangle me-1 text-warning"></i>
            Esta información solo se muestra una vez. Asegúrate de anotarla antes de salir de esta página.
        </p>
    </div>
@endif

{{-- Advertencias de importación --}}
@if(session('errores_import') && count(session('errores_import')) > 0)
    @php $erroresImport = session('errores_import'); @endphp
    <div class="alert alert-warning mb-3" style="border-radius:10px;font-size:.875rem;" role="alert">
        <div class="d-flex align-items-center gap-2 mb-1">
            <i class="bi bi-exclamation-triangle-fill fs-5"></i>
            <strong>{{ count($erroresImport) }} advertencia(s) durante la importación:</strong>
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

    {{-- Formulario de subida --}}
    <div class="col-lg-7">
        <div class="import-card h-100">
            <div class="card-section-title">
                <i class="bi bi-upload"></i>Subir Archivo
            </div>

            <form method="POST" enctype="multipart/form-data"
                  action="{{ route('admin.docentes.importPreview') }}"
                  id="importForm">
                @csrf

                <div class="fmt-badges mb-3 d-flex flex-wrap gap-1">
                    <span><i class="bi bi-filetype-xlsx text-success"></i> XLSX</span>
                    <span><i class="bi bi-filetype-xls text-success"></i> XLS</span>
                    <span><i class="bi bi-filetype-csv text-secondary"></i> CSV</span>
                </div>

                <div class="alert alert-info d-flex gap-2 align-items-start mb-4"
                     style="border-radius:10px;font-size:.84rem;">
                    <i class="bi bi-info-circle-fill flex-shrink-0 mt-1"></i>
                    <div>
                        Sube un archivo Excel con <strong>Apellidos, Nombres y Email</strong>.
                        El sistema creará automáticamente el perfil del docente y su cuenta de acceso
                        con una contraseña temporal que deberá cambiar al primer inicio de sesión.
                    </div>
                </div>

                <div class="drop-zone mb-3" id="dropZone"
                     onclick="document.getElementById('archivoInput').click()">
                    <i class="bi bi-file-earmark-person d-block mb-2"></i>
                    <p class="drop-label mb-1" style="font-size:.9rem;color:#6b7280;">
                        Haz clic o arrastra aquí tu archivo
                    </p>
                    <p class="text-muted mb-0" style="font-size:.76rem;">XLSX · XLS · CSV — máx. 10 MB</p>
                </div>
                <input type="file" id="archivoInput" name="archivo"
                       accept=".csv,.xlsx,.xls"
                       class="d-none @error('archivo') is-invalid @enderror"
                       onchange="handleFileSelect(this)">
                @error('archivo')
                    <div class="text-danger mb-2" style="font-size:.78rem;">
                        <i class="bi bi-x-circle me-1"></i>{{ $message }}
                    </div>
                @enderror

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

    {{-- Instrucciones --}}
    <div class="col-lg-5">
        {{-- Plantilla --}}
        <div class="import-card mb-4">
            <div class="card-section-title">
                <i class="bi bi-download"></i>Descargar Plantilla Excel
            </div>
            <p class="text-muted mb-3" style="font-size:.82rem;">
                Descarga la plantilla con el formato correcto.
                Las columnas en <strong>azul</strong> son obligatorias.
            </p>
            <a href="{{ route('admin.docentes.plantilla.descargar') }}"
               class="btn btn-outline-success fw-semibold w-100"
               style="border-radius:8px;font-size:.85rem;">
                <i class="bi bi-filetype-xlsx me-2"></i>Descargar Plantilla Excel
            </a>
        </div>

        {{-- Columnas --}}
        <div class="import-card mb-4">
            <div class="card-section-title">
                <i class="bi bi-table"></i>Columnas del archivo
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-bordered csv-table mb-0">
                    <thead>
                        <tr><th style="width:46%">Columna</th><th>Descripción</th></tr>
                    </thead>
                    <tbody>
                        <tr style="background:#eff6ff;">
                            <td><code>Apellidos</code></td>
                            <td><span class="badge-req">obligatorio</span></td>
                        </tr>
                        <tr style="background:#eff6ff;">
                            <td><code>Nombres</code></td>
                            <td><span class="badge-req">obligatorio</span></td>
                        </tr>
                        <tr style="background:#eff6ff;">
                            <td><code>Email</code></td>
                            <td>
                                <span class="badge-req">obligatorio</span>
                                <small class="d-block text-muted mt-1" style="font-size:.7rem;">
                                    <i class="bi bi-key me-1"></i>Crea cuenta de acceso automáticamente
                                </small>
                            </td>
                        </tr>
                        <tr><td><code>Cédula</code></td><td><span class="badge-opt">opcional</span></td></tr>
                        <tr><td><code>Teléfono</code></td><td><span class="badge-opt">opcional</span></td></tr>
                        <tr><td><code>Especialidad</code></td><td><span class="badge-opt">opcional</span></td></tr>
                        <tr><td><code>Título Académico</code></td><td><span class="badge-opt">opcional</span></td></tr>
                        <tr><td><code>Sexo</code></td><td><code>M</code> o <code>F</code></td></tr>
                        <tr><td><code>Área</code></td><td><code>tecnica</code> / <code>administrativa</code> / <code>otro</code></td></tr>
                        <tr><td><code>Cargo</code></td><td><span class="badge-opt">opcional</span></td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Nota sobre contraseñas --}}
        <div class="import-card" style="background:#fffbeb;border-color:#fcd34d;">
            <div class="card-section-title" style="color:#92400e;border-color:#fcd34d;">
                <i class="bi bi-shield-lock"></i>Contraseña temporal
            </div>
            <p style="font-size:.82rem;color:#374151;margin:0;">
                Al importar, el sistema genera una contraseña aleatoria segura para cada docente.
                Las contraseñas se muestran <strong>solo una vez</strong> al terminar la importación.<br><br>
                Al entrar por primera vez, el sistema le pedirá automáticamente que la cambie.
            </p>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
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

const fmtIcons = { xlsx:'bi-filetype-xlsx', xls:'bi-filetype-xls', csv:'bi-filetype-csv' };

function handleFileSelect(input) {
    if (input.files && input.files[0]) {
        const file = input.files[0];
        const ext  = file.name.split('.').pop().toLowerCase();
        const icon = fmtIcons[ext] || 'bi-file-earmark';
        const size = (file.size / 1024).toFixed(1) + ' KB';
        dropZone.classList.add('has-file');
        dropZone.innerHTML =
            `<i class="bi ${icon} d-block mb-2"></i>` +
            `<p class="drop-label mb-1" style="font-size:.9rem;">${file.name}</p>` +
            `<p class="text-muted mb-0" style="font-size:.76rem;">${size} — listo para previsualizar</p>`;
    }
}

document.getElementById('importForm').addEventListener('submit', function() {
    const btn  = document.getElementById('submitBtn');
    const spin = document.getElementById('submitSpinner');
    const icon = document.getElementById('submitIcon');
    btn.disabled = true;
    spin.classList.remove('d-none');
    if (icon) icon.classList.add('d-none');
});

function copiar(texto, btn) {
    navigator.clipboard.writeText(texto).then(() => {
        btn.innerHTML = '<i class="bi bi-check-lg"></i>';
        setTimeout(() => { btn.innerHTML = '<i class="bi bi-clipboard"></i>'; }, 2000);
    });
}
</script>
@endpush
