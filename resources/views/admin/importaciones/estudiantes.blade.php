@extends('layouts.admin')
@section('page-title', 'Importar Lista de Estudiantes')

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
.drop-zone:hover, .drop-zone.dragover { border-color: #16a34a; background: #f0fdf4; }
.drop-zone i { font-size: 2.2rem; color: #9ca3af; }
.drop-zone.has-file i { color: #16a34a; }
.drop-zone.has-file .drop-label { color: #16a34a; font-weight: 600; }
.badge-req { font-size:.68rem;padding:.2rem .5rem;border-radius:20px;background:#fee2e2;color:#991b1b;font-weight:600; }
.badge-opt { font-size:.68rem;padding:.2rem .5rem;border-radius:20px;background:#f3f4f6;color:#374151;font-weight:600; }
.result-table th { font-size: .78rem; background: var(--primary); color: #fff; }
.result-table td { font-size: .8rem; vertical-align: middle; }
[data-theme="dark"] .imp-card { background: #1e293b; border-color: #334155; }
[data-theme="dark"] .drop-zone { border-color: #475569; }
[data-theme="dark"] .drop-zone:hover { background: #052e16; border-color: #16a34a; }
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
            <i class="bi bi-people-fill me-2" style="color:#16a34a;"></i>Importar Lista de Estudiantes
        </h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0" style="font-size:.78rem;">
                <li class="breadcrumb-item"><a href="{{ route('admin.importaciones.index') }}" class="text-decoration-none">Importaciones</a></li>
                <li class="breadcrumb-item active">Estudiantes</li>
            </ol>
        </nav>
    </div>
</div>

{{-- Alertas --}}
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

{{-- Tabla de resultados --}}
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
        <div class="mt-2 d-flex gap-2">
            <a href="{{ route('admin.estudiantes.index') }}"
               class="btn btn-sm btn-outline-primary" style="border-radius:8px;font-size:.82rem;">
                <i class="bi bi-people me-1"></i>Ver estudiantes importados
            </a>
        </div>
    </div>
@endif

<div class="row g-4">

    {{-- LEFT: Formulario --}}
    <div class="col-lg-7">

        {{-- PASO 1: Configuración --}}
        <div class="imp-card mb-4">
            <div class="section-title">
                <i class="bi bi-1-circle"></i>Paso 1 — Configuración (Opcional)
            </div>

            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label fw-semibold mb-1" style="font-size:.83rem;color:var(--primary);">
                        <i class="bi bi-collection me-1"></i>Grupo para matrícula automática
                        <span class="badge ms-1" style="background:#f3f4f6;color:#6b7280;font-size:.65rem;font-weight:600;">opcional</span>
                    </label>
                    <select name="grupo_id" id="grupoSel" form="importForm" class="form-select" style="border-radius:8px;">
                        <option value="">— Sin grupo (solo registrar estudiantes) —</option>
                        @foreach($grupos as $g)
                            <option value="{{ $g->id }}">
                                {{ $g->grado->nombre ?? '' }} {{ $g->seccion->nombre ?? '' }}
                            </option>
                        @endforeach
                    </select>
                    <div class="form-text" style="font-size:.76rem;">
                        Si seleccionas un grupo, los estudiantes se matriculan automáticamente en él.
                    </div>
                </div>

                @if($schoolYear)
                    <div class="col-12">
                        <div class="alert alert-light border py-2 px-3 mb-0" style="border-radius:8px;font-size:.8rem;">
                            <i class="bi bi-calendar3 me-1 text-primary"></i>
                            Año escolar activo: <strong>{{ $schoolYear->nombre }}</strong>
                        </div>
                        <input type="hidden" name="school_year_id" form="importForm" value="{{ $schoolYear->id }}">
                    </div>
                @endif
            </div>
        </div>

        {{-- PASO 2: Archivo --}}
        <div class="imp-card">
            <div class="section-title">
                <i class="bi bi-2-circle"></i>Paso 2 — Subir Archivo
            </div>

            <form method="POST" enctype="multipart/form-data"
                  action="{{ route('admin.importaciones.estudiantes.importar') }}"
                  id="importForm">
                @csrf

                <div class="d-flex flex-wrap gap-1 mb-3">
                    @foreach(['CSV','TXT','XLSX','XLS'] as $fmt)
                        <span class="badge" style="background:#f3f4f6;color:#374151;border:1px solid #d1d5db;font-size:.72rem;font-weight:600;border-radius:20px;padding:.25rem .6rem;">
                            <i class="bi bi-filetype-{{ strtolower($fmt) }} me-1"></i>{{ $fmt }}
                        </span>
                    @endforeach
                    <span class="badge" style="background:#fffbeb;border:1px solid #fcd34d;color:#92400e;font-size:.72rem;font-weight:600;border-radius:20px;padding:.25rem .6rem;">
                        <i class="bi bi-magic me-1"></i>separador auto-detectado
                    </span>
                </div>

                <div class="drop-zone mb-3" id="dropZone"
                     onclick="document.getElementById('archivoInp').click()">
                    <i class="bi bi-file-earmark-person d-block mb-2"></i>
                    <p class="drop-label mb-1" style="font-size:.9rem;color:#6b7280;">
                        Haz clic o arrastra aquí tu archivo de estudiantes
                    </p>
                    <p class="text-muted mb-0" style="font-size:.76rem;">
                        CSV · TXT · XLSX · XLS — Máx. 10 MB
                    </p>
                </div>
                <input type="file" id="archivoInp" name="archivo"
                       accept=".csv,.txt,.xlsx,.xls" class="d-none"
                       onchange="onFileSelect(this)">
                @error('archivo')
                    <div class="text-danger mt-1" style="font-size:.78rem;">
                        <i class="bi bi-x-circle me-1"></i>{{ $message }}
                    </div>
                @enderror

                <div class="d-flex justify-content-end mt-3">
                    <button type="submit" class="btn px-4 fw-semibold" id="submitBtn"
                            style="background:#16a34a;color:#fff;border-radius:8px;">
                        <span id="submitSpinner" class="spinner-border spinner-border-sm me-2 d-none" role="status"></span>
                        <i class="bi bi-cloud-arrow-up me-1" id="submitIcon"></i>
                        Importar Estudiantes
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
                Descarga la plantilla con el formato exacto. Incluye filas de ejemplo y una nota con los valores aceptados.
            </p>
            <div class="d-flex gap-2 flex-wrap mb-2">
                <a href="{{ route('admin.importaciones.estudiantes.plantilla', ['format'=>'csv']) }}"
                   class="btn btn-outline-success fw-semibold flex-fill"
                   style="border-radius:8px;font-size:.85rem;">
                    <i class="bi bi-filetype-csv me-1"></i>Plantilla CSV
                </a>
                <a href="{{ route('admin.importaciones.estudiantes.plantilla', ['format'=>'xlsx']) }}"
                   class="btn btn-outline-success fw-semibold flex-fill"
                   style="border-radius:8px;font-size:.85rem;">
                    <i class="bi bi-filetype-xlsx me-1"></i>Plantilla Excel
                </a>
            </div>
            <div style="font-size:.74rem;color:#6b7280;">
                <i class="bi bi-lightbulb me-1"></i>
                Compatible con Excel, Google Sheets y LibreOffice.
            </div>
        </div>

        {{-- Columnas --}}
        <div class="imp-card mb-4">
            <div class="section-title">
                <i class="bi bi-table"></i>Columnas del archivo
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-bordered mb-0" style="font-size:.8rem;">
                    <thead>
                        <tr style="background:var(--primary);color:#fff;">
                            <th style="width:46%">Columna</th>
                            <th>Descripción / Valores</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><code>nombres</code></td>
                            <td><span class="badge-req">obligatorio</span></td>
                        </tr>
                        <tr>
                            <td><code>apellidos</code></td>
                            <td><span class="badge-req">obligatorio</span></td>
                        </tr>
                        <tr>
                            <td><code>cedula</code></td>
                            <td><span class="badge-opt">opcional</span> — omite fila si ya existe</td>
                        </tr>
                        <tr>
                            <td><code>fecha_nacimiento</code></td>
                            <td>
                                <span class="badge-opt">opcional</span>
                                <code>AAAA-MM-DD</code> · <code>DD/MM/AAAA</code> · serial Excel
                            </td>
                        </tr>
                        <tr>
                            <td><code>sexo</code></td>
                            <td><code>M</code> o <code>F</code></td>
                        </tr>
                        <tr>
                            <td><code>direccion</code></td>
                            <td><span class="badge-opt">opcional</span></td>
                        </tr>
                        <tr style="background:#f0fdf4;">
                            <td><code>nombre_representante</code></td>
                            <td><span class="badge-opt">opcional</span></td>
                        </tr>
                        <tr style="background:#f0fdf4;">
                            <td><code>telefono_representante</code></td>
                            <td><span class="badge-opt">opcional</span></td>
                        </tr>
                        <tr style="background:#f0fdf4;">
                            <td><code>email_representante</code></td>
                            <td>
                                <span class="badge-opt">opcional</span>
                                <span class="badge" style="background:#e0f2fe;color:#0369a1;font-size:.65rem;border-radius:8px;">
                                    <i class="bi bi-person-badge me-1"></i>crea cuenta portal
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Notas de comportamiento --}}
        <div class="imp-card">
            <div class="section-title">
                <i class="bi bi-shield-check"></i>Comportamiento del sistema
            </div>
            <div style="font-size:.82rem;">
                <div class="d-flex gap-2 mb-2 align-items-start">
                    <i class="bi bi-check-circle-fill text-success flex-shrink-0 mt-1"></i>
                    <div>Las filas con <strong>cédula duplicada</strong> se omiten automáticamente.</div>
                </div>
                <div class="d-flex gap-2 mb-2 align-items-start">
                    <i class="bi bi-check-circle-fill text-success flex-shrink-0 mt-1"></i>
                    <div>El <strong>número de matrícula</strong> se genera automáticamente si no se incluye.</div>
                </div>
                <div class="d-flex gap-2 mb-2 align-items-start">
                    <i class="bi bi-check-circle-fill text-success flex-shrink-0 mt-1"></i>
                    <div>Si el <code>email_representante</code> no existe aún, se crea una cuenta de usuario
                         con rol <strong>Representante</strong> y contraseña aleatoria.</div>
                </div>
                <div class="d-flex gap-2 mb-2 align-items-start">
                    <i class="bi bi-check-circle-fill text-success flex-shrink-0 mt-1"></i>
                    <div>Si seleccionas un grupo, la <strong>matrícula se crea automáticamente</strong>
                         con estado "activa".</div>
                </div>
                <div class="d-flex gap-2 align-items-start">
                    <i class="bi bi-info-circle-fill text-primary flex-shrink-0 mt-1"></i>
                    <div>Cada operación se ejecuta en una transacción: si falla una fila, las demás no se ven afectadas.</div>
                </div>
            </div>
        </div>

    </div>
</div>

@endsection

@push('scripts')
<script>
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
            `<i class="bi ${fmtIcons[ext] || 'bi-file-earmark-person'} d-block mb-2"></i>` +
            `<p class="drop-label mb-1" style="font-size:.9rem;">${file.name}</p>` +
            `<p class="text-muted mb-0" style="font-size:.76rem;">${size} — listo para importar</p>`;
    }
}

// ── Spinner --
document.getElementById('importForm').addEventListener('submit', function () {
    const btn = document.getElementById('submitBtn');
    btn.disabled = true;
    document.getElementById('submitSpinner').classList.remove('d-none');
    document.getElementById('submitIcon').classList.add('d-none');
});
</script>
@endpush
