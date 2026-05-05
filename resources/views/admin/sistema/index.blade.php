@extends('layouts.admin')
@section('page-title', 'Configuración del Sistema')

@push('styles')
<style>
    /* ── Page header ─────────────────────────────── */
    .page-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 1.5rem;
        flex-wrap: wrap;
        gap: .75rem;
    }
    .page-header h1 {
        font-size: 1.45rem;
        font-weight: 800;
        color: var(--primary);
        margin: 0;
    }

    /* ── Cards ───────────────────────────────────── */
    .card-panel {
        background: #fff;
        border-radius: 12px;
        border: 1px solid #e5e7eb;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
    }

    /* ── Section title ───────────────────────────── */
    .section-title {
        font-size: .72rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .07em;
        color: var(--primary);
        border-bottom: 2px solid var(--primary);
        padding-bottom: .4rem;
        margin-bottom: 1.1rem;
    }

    /* ── Form controls ───────────────────────────── */
    .form-label-custom {
        font-size: .83rem;
        font-weight: 600;
        color: #374151;
        margin-bottom: .35rem;
    }
    .form-control-custom {
        border-radius: 8px;
        border: 1px solid #d1d5db;
        font-size: .875rem;
        padding: .5rem .75rem;
        transition: border-color .15s, box-shadow .15s;
    }
    .form-control-custom:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(30,58,110,.12);
        outline: none;
    }

    /* ── Info note ───────────────────────────────── */
    .info-note {
        font-size: .78rem;
        color: #6b7280;
        display: flex;
        align-items: center;
        gap: .35rem;
        margin-top: .35rem;
    }
    .info-note i { color: var(--primary); flex-shrink: 0; }

    /* ── Primary button ──────────────────────────── */
    .btn-primary-custom {
        background: var(--primary);
        color: #fff;
        border-radius: 8px;
        border: none;
        padding: .6rem 1.5rem;
        font-size: .9rem;
        font-weight: 600;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: .45rem;
        transition: background .15s;
    }
    .btn-primary-custom:hover { background: var(--primary-dark); color: #fff; }

    /* ── Danger button ───────────────────────────── */
    .btn-danger-custom {
        background: var(--secondary);
        color: #fff;
        border-radius: 8px;
        border: none;
        padding: .4rem .9rem;
        font-size: .82rem;
        font-weight: 600;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        transition: background .15s;
    }
    .btn-danger-custom:hover { background: #a93226; color: #fff; }

    /* ── Upload button ───────────────────────────── */
    .btn-upload {
        background: var(--primary);
        color: #fff;
        border-radius: 8px;
        border: none;
        padding: .5rem 1.1rem;
        font-size: .85rem;
        font-weight: 600;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: .4rem;
        transition: background .15s;
        width: 100%;
        justify-content: center;
    }
    .btn-upload:hover { background: var(--primary-dark); color: #fff; }

    /* ── Logo placeholder ────────────────────────── */
    .logo-placeholder {
        background: #f3f4f6;
        border: 2px dashed #d1d5db;
        border-radius: 10px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 2rem;
        color: #9ca3af;
        gap: .5rem;
        margin-bottom: 1rem;
    }
    .logo-placeholder i { font-size: 2.5rem; opacity: .5; }
    .logo-placeholder span { font-size: .8rem; }

    /* ── Current logo ────────────────────────────── */
    .current-logo-wrapper {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: .75rem;
        padding: 1rem;
        background: #f9fafb;
        border-radius: 10px;
        border: 1px solid #e5e7eb;
        margin-bottom: 1rem;
    }

    /* ── System info rows ────────────────────────── */
    .sysinfo-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: .5rem 0;
        border-bottom: 1px solid #f3f4f6;
        font-size: .84rem;
    }
    .sysinfo-row:last-child { border-bottom: none; }
    .sysinfo-label {
        color: #6b7280;
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: .4rem;
    }
    .sysinfo-value {
        font-weight: 600;
        color: #111827;
        font-family: 'Courier New', monospace;
        font-size: .8rem;
        background: #f3f4f6;
        padding: .15rem .5rem;
        border-radius: 5px;
    }

    /* ── Divider between form sections ───────────── */
    .form-section + .form-section { margin-top: 1.5rem; }

    [data-theme="dark"] .card-panel { background: #1e293b; border-color: #334155; }
</style>
@endpush

@section('content')

{{-- Flash messages --}}
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2 mb-4" role="alert">
        <i class="bi bi-check-circle-fill"></i>
        {{ session('success') }}
        <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
    </div>
@endif
@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center gap-2 mb-4" role="alert">
        <i class="bi bi-exclamation-circle-fill"></i>
        {{ session('error') }}
        <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
    </div>
@endif

{{-- Page header --}}
<div class="page-header">
    <div>
        <h1>
            <i class="bi bi-gear-fill me-2" style="color:var(--secondary);"></i>
            Configuración del Sistema
        </h1>
        <p class="text-muted mb-0" style="font-size:.85rem;">
            Parámetros generales, seguridad y logotipo del sistema
        </p>
    </div>
</div>

{{-- Two-column layout --}}
<div class="row g-4">

    {{-- LEFT: Main settings form --}}
    <div class="col-lg-8">
        <form action="{{ route('admin.sistema.update') }}" method="POST">
            @csrf

            <div class="card-panel">

                {{-- Section: Identidad del Sistema --}}
                <div class="form-section">
                    <div class="section-title">
                        <i class="bi bi-building me-1"></i>
                        Identidad del Sistema
                    </div>

                    <div class="mb-3">
                        <label for="system_name" class="form-label-custom">
                            Nombre Completo del Centro
                        </label>
                        <input type="text"
                               id="system_name"
                               name="system_name"
                               class="form-control form-control-custom @error('system_name') is-invalid @enderror"
                               value="{{ old('system_name', $settings['system_name'] ?? '') }}"
                               placeholder="Ej. Politécnico Salesiano Arquides Calderón">
                        @error('system_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-1">
                        <label for="system_abbr" class="form-label-custom">
                            Abreviatura
                            <span class="text-muted fw-normal">(aparece en el logo del sidebar)</span>
                        </label>
                        <input type="text"
                               id="system_abbr"
                               name="system_abbr"
                               class="form-control form-control-custom @error('system_abbr') is-invalid @enderror"
                               maxlength="10"
                               value="{{ old('system_abbr', $settings['system_abbr'] ?? '') }}"
                               placeholder="Ej. PSAC"
                               style="max-width:180px;">
                        @error('system_abbr')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-1">
                        <label for="system_sub" class="form-label-custom">
                            Subtítulo del Sidebar
                            <span class="text-muted fw-normal">(ej. Gestión Escolar)</span>
                        </label>
                        <input type="text"
                               id="system_sub"
                               name="system_sub"
                               class="form-control form-control-custom @error('system_sub') is-invalid @enderror"
                               maxlength="80"
                               value="{{ old('system_sub', $settings['system_sub'] ?? '') }}"
                               placeholder="Ej. Gestión Escolar">
                        @error('system_sub')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                {{-- Section: Registro de Usuarios --}}
                <div class="form-section mt-4">
                    <div class="section-title">
                        <i class="bi bi-person-plus me-1"></i>
                        Registro de Usuarios
                    </div>

                    <div class="mb-1">
                        <label for="codigo_registro" class="form-label-custom">
                            Código de Acceso del Centro
                        </label>
                        <input type="text"
                               id="codigo_registro"
                               name="codigo_registro"
                               class="form-control form-control-custom @error('codigo_registro') is-invalid @enderror"
                               maxlength="50"
                               value="{{ old('codigo_registro', $settings['codigo_registro'] ?? '') }}"
                               placeholder="Ej. PSAC2026"
                               style="max-width:280px;">
                        @error('codigo_registro')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="info-note mt-1">
                            <i class="bi bi-info-circle"></i>
                            <span>
                                Los nuevos usuarios deben ingresar este código en el formulario de registro.
                                Cámbialo periódicamente para controlar quién puede solicitar acceso.
                            </span>
                        </div>
                    </div>
                </div>

                {{-- Section: Seguridad --}}
                <div class="form-section mt-4">
                    <div class="section-title">
                        <i class="bi bi-shield-lock me-1"></i>
                        Seguridad
                    </div>

                    <div class="row g-3 mb-1">
                        <div class="col-sm-6">
                            <label for="max_login_attempts" class="form-label-custom">
                                Máx. intentos de login antes de bloqueo
                            </label>
                            <input type="number"
                                   id="max_login_attempts"
                                   name="max_login_attempts"
                                   class="form-control form-control-custom @error('max_login_attempts') is-invalid @enderror"
                                   min="3"
                                   max="20"
                                   value="{{ old('max_login_attempts', $settings['max_login_attempts'] ?? 5) }}">
                            @error('max_login_attempts')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-sm-6">
                            <label for="session_timeout" class="form-label-custom">
                                Tiempo de sesión (minutos)
                            </label>
                            <input type="number"
                                   id="session_timeout"
                                   name="session_timeout"
                                   class="form-control form-control-custom @error('session_timeout') is-invalid @enderror"
                                   min="15"
                                   max="480"
                                   value="{{ old('session_timeout', $settings['session_timeout'] ?? 120) }}">
                            @error('session_timeout')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="info-note">
                        <i class="bi bi-info-circle"></i>
                        <span>Cambios de seguridad aplican al próximo inicio de sesión.</span>
                    </div>
                </div>

                {{-- Save button --}}
                <div class="mt-4 pt-2 border-top">
                    <button type="submit" class="btn-primary-custom">
                        <i class="bi bi-floppy2-fill"></i>
                        Guardar Cambios
                    </button>
                </div>

            </div>
        </form>
    </div>

    {{-- RIGHT: Logo + System Info --}}
    <div class="col-lg-4">

        {{-- Logotipo del Sistema --}}
        <div class="card-panel">
            <div class="section-title">
                <i class="bi bi-image me-1"></i>
                Logotipo del Sistema
            </div>

            @if(!empty($settings['system_logo']))
                {{-- Current logo --}}
                <div class="current-logo-wrapper">
                    <img src="{{ asset('storage/' . $settings['system_logo']) }}"
                         alt="Logotipo del sistema"
                         style="max-height:100px; max-width:200px; border-radius:8px;">
                </div>

                {{-- Delete logo form --}}
                <form action="{{ route('admin.sistema.logo.delete') }}"
                      method="POST"
                      class="mb-3"
                      onsubmit="return confirm('¿Eliminar el logotipo actual?')">
                    @csrf
                    <button type="submit" class="btn-danger-custom w-100 justify-content-center">
                        <i class="bi bi-trash3"></i>
                        Eliminar logo
                    </button>
                </form>
            @else
                {{-- Placeholder --}}
                <div class="logo-placeholder mb-3">
                    <i class="bi bi-image"></i>
                    <span>Sin logotipo cargado</span>
                </div>
            @endif

            {{-- Upload form --}}
            <form action="{{ route('admin.sistema.logo') }}"
                  method="POST"
                  enctype="multipart/form-data">
                @csrf

                <div class="mb-2">
                    <label for="logo" class="form-label-custom">Subir nuevo logotipo</label>
                    <input type="file"
                           id="logo"
                           name="logo"
                           class="form-control form-control-custom @error('logo') is-invalid @enderror"
                           accept=".png,.jpg,.jpeg,.svg"
                           style="font-size:.82rem;">
                    @error('logo')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div class="info-note mt-1">
                        <i class="bi bi-info-circle"></i>
                        <span>PNG, JPG o SVG. Máx. 512&nbsp;KB.</span>
                    </div>
                </div>

                <button type="submit" class="btn-upload mt-1">
                    <i class="bi bi-cloud-upload"></i>
                    Subir Logotipo
                </button>
            </form>
        </div>

        {{-- Favicon del Sistema --}}
        <div class="card-panel">
            <div class="section-title">
                <i class="bi bi-lightning-charge me-1"></i>
                Favicon de la Página
            </div>

            <p style="font-size:.8rem;color:#6b7280;margin-bottom:.85rem;">
                El favicon es el ícono que aparece en la pestaña del navegador.
                Recomendado: 32×32 px formato <strong>PNG</strong> o <strong>ICO</strong>.
            </p>

            @if(!empty($settings['system_favicon']))
                <div class="d-flex align-items-center gap-3 mb-3 p-2" style="background:#f8faff;border-radius:8px;border:1px solid #e5e7eb;">
                    <img src="{{ asset('storage/' . $settings['system_favicon']) }}"
                         alt="Favicon actual"
                         style="width:32px;height:32px;border-radius:4px;object-fit:contain;">
                    <span style="font-size:.8rem;color:#374151;">Favicon actual</span>
                    <form action="{{ route('admin.sistema.favicon.delete') }}"
                          method="POST"
                          class="ms-auto"
                          onsubmit="return confirm('¿Eliminar el favicon actual?')">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-danger" style="font-size:.75rem;">
                            <i class="bi bi-trash3"></i>
                        </button>
                    </form>
                </div>
            @else
                <div class="logo-placeholder mb-3" style="padding:.75rem;">
                    <i class="bi bi-lightning-charge" style="font-size:1.5rem;"></i>
                    <span>Sin favicon cargado</span>
                </div>
            @endif

            <form action="{{ route('admin.sistema.favicon') }}"
                  method="POST"
                  enctype="multipart/form-data">
                @csrf
                <div class="mb-2">
                    <label for="favicon" class="form-label-custom">Subir nuevo favicon</label>
                    <input type="file"
                           id="favicon"
                           name="favicon"
                           class="form-control form-control-custom @error('favicon') is-invalid @enderror"
                           accept=".png,.jpg,.jpeg,.ico,.svg"
                           style="font-size:.82rem;">
                    @error('favicon')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div class="info-note mt-1">
                        <i class="bi bi-info-circle"></i>
                        <span>PNG, ICO, JPG o SVG. Máx. 256&nbsp;KB. Recomendado 32×32px.</span>
                    </div>
                </div>
                <button type="submit" class="btn-upload mt-1">
                    <i class="bi bi-cloud-upload"></i>
                    Subir Favicon
                </button>
            </form>
        </div>

        {{-- Información del Sistema --}}
        <div class="card-panel">
            <div class="section-title">
                <i class="bi bi-cpu me-1"></i>
                Información del Sistema
            </div>

            <div class="sysinfo-row">
                <span class="sysinfo-label">
                    <i class="bi bi-box-seam"></i>
                    Laravel
                </span>
                <span class="sysinfo-value">{{ app()->version() }}</span>
            </div>

            <div class="sysinfo-row">
                <span class="sysinfo-label">
                    <i class="bi bi-filetype-php"></i>
                    PHP
                </span>
                <span class="sysinfo-value">{{ PHP_VERSION }}</span>
            </div>

            <div class="sysinfo-row">
                <span class="sysinfo-label">
                    <i class="bi bi-hdd-network"></i>
                    IP del Servidor
                </span>
                <span class="sysinfo-value">{{ $_SERVER['SERVER_ADDR'] ?? 'localhost' }}</span>
            </div>
        </div>

    </div>
</div>

{{-- ── Zona Peligrosa ────────────────────────────────────────────── --}}
<div class="card-panel" style="border:2px solid #fee2e2;background:#fff5f5;">
    <div class="section-title" style="color:#dc2626;border-color:#dc2626;">
        <i class="bi bi-exclamation-triangle-fill me-1"></i>Zona Peligrosa — Limpiar Datos
    </div>
    <p class="text-muted small mb-3">
        Estas acciones son <strong>irreversibles</strong>. Se eliminarán permanentemente los datos seleccionados.
        Asegúrate de tener un backup antes de continuar.
    </p>
    <div class="d-flex flex-wrap gap-3">
        <button type="button" class="btn btn-outline-danger"
            onclick="abrirModalLimpiar('estudiantes')">
            <i class="bi bi-people-fill me-1"></i>Borrar todos los estudiantes
        </button>
        <button type="button" class="btn btn-danger"
            onclick="abrirModalLimpiar('todo')">
            <i class="bi bi-trash3-fill me-1"></i>Borrar todo (estudiantes + datos académicos)
        </button>
    </div>
</div>

{{-- Modal confirmación --}}
<div class="modal fade" id="modalLimpiar" tabindex="-1" aria-labelledby="modalLimpiarLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0" style="border-radius:16px;overflow:hidden;">
            <div class="modal-header" style="background:#dc2626;color:#fff;border:none;">
                <h5 class="modal-title fw-800" id="modalLimpiarLabel">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>Confirmar eliminación
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div id="limpiarDescripcion" class="alert alert-danger border-0 rounded-3 mb-3" style="font-size:.875rem;"></div>
                <p class="fw-600 mb-2" style="font-size:.875rem;">
                    Para confirmar, escribe <strong style="color:#dc2626;font-size:1rem;">CONFIRMAR</strong> en el campo de abajo:
                </p>
                <input type="text" id="inputConfirmacion" class="form-control form-control-lg"
                    placeholder="Escribe CONFIRMAR"
                    style="border:2px solid #fca5a5;border-radius:10px;font-weight:700;letter-spacing:.05em;"
                    oninput="verificarConfirmacion(this.value)">
                <p class="text-muted mt-2 mb-0" style="font-size:.75rem;">
                    <i class="bi bi-shield-lock me-1"></i>Esta acción quedará registrada en el log de actividad.
                </p>
            </div>
            <div class="modal-footer border-0 pt-0 px-4 pb-4">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <form id="formLimpiar" method="POST" action="{{ route('admin.sistema.limpiar-datos') }}">
                    @csrf
                    <input type="hidden" name="confirmacion" value="CONFIRMAR">
                    <input type="hidden" name="scope" id="limpiarScope" value="">
                    <button type="submit" id="btnConfirmarLimpiar" class="btn btn-danger px-4" disabled>
                        <i class="bi bi-trash3-fill me-1"></i>Sí, eliminar permanentemente
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
@if(session('success_danger'))
    document.addEventListener('DOMContentLoaded', function() {
        const toast = document.createElement('div');
        toast.innerHTML = `
            <div class="position-fixed top-0 start-50 translate-middle-x mt-3" style="z-index:9999;min-width:340px;">
                <div class="alert alert-warning alert-dismissible fade show border-0 shadow rounded-3" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i>{{ session('success_danger') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            </div>`;
        document.body.appendChild(toast);
        setTimeout(() => toast.remove(), 6000);
    });
@endif

const descripciones = {
    estudiantes: '⚠️ Se eliminarán <strong>todos los estudiantes</strong> y sus datos relacionados: calificaciones, asistencias, matrículas y observaciones. <br><br>Los docentes, grupos y configuraciones no serán afectados.',
    todo: '🚨 Se eliminarán <strong>TODOS los datos académicos</strong>: estudiantes, calificaciones, asistencias, matrículas, grupos, secciones y horarios. <br><br>Solo se conservarán los usuarios del sistema (docentes, admin) y la configuración.',
};

function abrirModalLimpiar(scope) {
    document.getElementById('limpiarScope').value = scope;
    document.getElementById('limpiarDescripcion').innerHTML = descripciones[scope];
    document.getElementById('inputConfirmacion').value = '';
    document.getElementById('btnConfirmarLimpiar').disabled = true;
    new bootstrap.Modal(document.getElementById('modalLimpiar')).show();
}

function verificarConfirmacion(val) {
    document.getElementById('btnConfirmarLimpiar').disabled = (val.trim() !== 'CONFIRMAR');
}
</script>
@endpush

@endsection
