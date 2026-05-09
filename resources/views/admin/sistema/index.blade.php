@extends('layouts.admin')
@section('page-title', 'Configuración del Sistema')

@push('styles')
<style>
.cfg-tabs { display:flex; gap:.35rem; border-bottom:2px solid #e5e7eb; margin-bottom:1.5rem; flex-wrap:wrap; }
.cfg-tab  { display:flex; align-items:center; gap:.45rem; padding:.55rem 1.1rem; border-radius:8px 8px 0 0; font-size:.82rem; font-weight:700; color:#64748b; cursor:pointer; border:2px solid transparent; border-bottom:none; background:transparent; transition:all .15s; }
.cfg-tab:hover { color:#1d4ed8; background:#eff6ff; }
.cfg-tab.active { color:#1d4ed8; background:#fff; border-color:#e5e7eb; border-bottom-color:#fff; margin-bottom:-2px; }
.cfg-tab i { font-size:.9rem; }

.cfg-panel { display:none; }
.cfg-panel.active { display:block; }

.card-panel { background:#fff; border-radius:12px; border:1px solid #e5e7eb; padding:1.5rem; margin-bottom:1.25rem; }
.section-title { font-size:.72rem; font-weight:700; text-transform:uppercase; letter-spacing:.07em; color:var(--primary); border-bottom:2px solid var(--primary); padding-bottom:.4rem; margin-bottom:1.1rem; }
.form-label-custom { font-size:.83rem; font-weight:600; color:#374151; margin-bottom:.35rem; }
.form-control-custom { border-radius:8px; border:1px solid #d1d5db; font-size:.875rem; padding:.5rem .75rem; transition:border-color .15s,box-shadow .15s; }
.form-control-custom:focus { border-color:var(--primary); box-shadow:0 0 0 3px rgba(30,58,110,.12); outline:none; }
.info-note { font-size:.78rem; color:#6b7280; display:flex; align-items:center; gap:.35rem; margin-top:.35rem; }
.info-note i { color:var(--primary); flex-shrink:0; }
.btn-primary-custom { background:var(--primary); color:#fff; border-radius:8px; border:none; padding:.6rem 1.5rem; font-size:.9rem; font-weight:600; cursor:pointer; display:inline-flex; align-items:center; gap:.45rem; transition:background .15s; }
.btn-primary-custom:hover { background:var(--primary-dark); color:#fff; }
.btn-danger-custom { background:var(--secondary); color:#fff; border-radius:8px; border:none; padding:.4rem .9rem; font-size:.82rem; font-weight:600; cursor:pointer; display:inline-flex; align-items:center; gap:.35rem; transition:background .15s; }
.btn-danger-custom:hover { background:#a93226; color:#fff; }
.btn-upload { background:var(--primary); color:#fff; border-radius:8px; border:none; padding:.5rem 1.1rem; font-size:.85rem; font-weight:600; cursor:pointer; display:inline-flex; align-items:center; gap:.4rem; transition:background .15s; width:100%; justify-content:center; }
.btn-upload:hover { background:var(--primary-dark); color:#fff; }
.logo-placeholder { background:#f3f4f6; border:2px dashed #d1d5db; border-radius:10px; display:flex; flex-direction:column; align-items:center; justify-content:center; padding:2rem; color:#9ca3af; gap:.5rem; margin-bottom:1rem; }
.logo-placeholder i { font-size:2.5rem; opacity:.5; }
.logo-placeholder span { font-size:.8rem; }
.current-logo-wrapper { display:flex; flex-direction:column; align-items:center; gap:.75rem; padding:1rem; background:#f9fafb; border-radius:10px; border:1px solid #e5e7eb; margin-bottom:1rem; }
.sysinfo-row { display:flex; justify-content:space-between; align-items:center; padding:.5rem 0; border-bottom:1px solid #f3f4f6; font-size:.84rem; }
.sysinfo-row:last-child { border-bottom:none; }
.sysinfo-label { color:#6b7280; font-weight:500; display:flex; align-items:center; gap:.4rem; }
.sysinfo-value { font-weight:600; color:#111827; font-family:'Courier New',monospace; font-size:.8rem; background:#f3f4f6; padding:.15rem .5rem; border-radius:5px; }

/* Módulos */
.modulo-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(240px,1fr)); gap:.75rem; }
.modulo-card { display:flex; align-items:center; justify-content:space-between; padding:.85rem 1rem; background:#f8faff; border:1.5px solid #e2e8f0; border-radius:10px; transition:border-color .15s; }
.modulo-card:hover { border-color:#93c5fd; }
.modulo-card.activo { background:#eff6ff; border-color:#bfdbfe; }
.modulo-info { display:flex; align-items:center; gap:.65rem; }
.modulo-icon { width:36px; height:36px; border-radius:9px; display:flex; align-items:center; justify-content:center; font-size:1rem; flex-shrink:0; }
.modulo-label { font-size:.84rem; font-weight:700; color:#1e293b; }
.form-check-input { width:2.4em; height:1.3em; cursor:pointer; }

[data-theme="dark"] .card-panel { background:#1e293b; border-color:#334155; }
[data-theme="dark"] .cfg-tab.active { background:#1e293b; border-color:#334155; border-bottom-color:#1e293b; }
[data-theme="dark"] .cfg-tabs { border-color:#334155; }
[data-theme="dark"] .modulo-card { background:#0f172a; border-color:#334155; }
[data-theme="dark"] .modulo-card.activo { background:#1e293b; border-color:#3b82f6; }
[data-theme="dark"] .modulo-label { color:#e2e8f0; }
</style>
@endpush

@section('content')

{{-- Flash --}}
@if(session('success'))
<div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2 mb-4">
    <i class="bi bi-check-circle-fill"></i> {{ session('success') }}
    <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
</div>
@endif
@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show d-flex align-items-center gap-2 mb-4">
    <i class="bi bi-exclamation-circle-fill"></i> {{ session('error') }}
    <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
    <div>
        <h4 class="fw-bold mb-0"><i class="bi bi-gear-fill text-primary me-1"></i> Configuración del Sistema</h4>
        <p class="text-muted small mb-0">Parámetros generales, datos institucionales y módulos activos</p>
    </div>
</div>

{{-- Pestañas --}}
@php $activeTab = session('tab', 'general'); @endphp
<div class="cfg-tabs">
    <button class="cfg-tab {{ $activeTab === 'general' ? 'active' : '' }}" onclick="cambiarTab('general')">
        <i class="bi bi-sliders"></i> General
    </button>
    <button class="cfg-tab {{ $activeTab === 'institucional' ? 'active' : '' }}" onclick="cambiarTab('institucional')">
        <i class="bi bi-building-fill"></i> Datos del Centro
    </button>
    <button class="cfg-tab {{ $activeTab === 'modulos' ? 'active' : '' }}" onclick="cambiarTab('modulos')">
        <i class="bi bi-grid-fill"></i> Módulos
    </button>
    <button class="cfg-tab {{ $activeTab === 'apariencia' ? 'active' : '' }}" onclick="cambiarTab('apariencia')">
        <i class="bi bi-palette-fill"></i> Logo y Favicon
    </button>
    <button class="cfg-tab {{ $activeTab === 'seguridad' ? 'active' : '' }}" onclick="cambiarTab('seguridad')">
        <i class="bi bi-shield-lock-fill"></i> Seguridad
    </button>
</div>

{{-- ══ TAB: GENERAL ══════════════════════════════════════════════════════════ --}}
<div id="tab-general" class="cfg-panel {{ $activeTab === 'general' ? 'active' : '' }}">
<form action="{{ route('admin.sistema.update') }}" method="POST">
@csrf
<div class="row g-4">
    <div class="col-lg-7">
        <div class="card-panel">
            <div class="section-title"><i class="bi bi-building me-1"></i>Identidad del Sistema</div>
            <div class="mb-3">
                <label class="form-label-custom">Nombre Completo del Centro</label>
                <input type="text" name="system_name" class="form-control form-control-custom"
                       value="{{ old('system_name', $settings['system_name'] ?? '') }}"
                       placeholder="Ej. Politécnico Salesiano Arquides Calderón">
                <div class="info-note"><i class="bi bi-info-circle"></i>Se muestra en el encabezado de reportes y PDFs del sistema.</div>
            </div>
            <div class="row g-3">
                <div class="col-sm-5">
                    <label class="form-label-custom">Abreviatura <span class="text-muted fw-normal">(sidebar)</span></label>
                    <input type="text" name="system_abbr" class="form-control form-control-custom"
                           maxlength="10" value="{{ old('system_abbr', $settings['system_abbr'] ?? '') }}"
                           placeholder="PSAC">
                </div>
                <div class="col-sm-7">
                    <label class="form-label-custom">Subtítulo del Sidebar</label>
                    <input type="text" name="system_sub" class="form-control form-control-custom"
                           maxlength="80" value="{{ old('system_sub', $settings['system_sub'] ?? '') }}"
                           placeholder="Gestión Escolar">
                </div>
            </div>
        </div>

        <div class="card-panel">
            <div class="section-title"><i class="bi bi-person-plus me-1"></i>Registro de Usuarios</div>
            <label class="form-label-custom">Código de Acceso del Centro</label>
            <input type="text" name="codigo_registro" class="form-control form-control-custom"
                   maxlength="50" style="max-width:280px;"
                   value="{{ old('codigo_registro', $settings['codigo_registro'] ?? '') }}"
                   placeholder="Ej. CENTRO2026">
            <div class="info-note mt-1"><i class="bi bi-info-circle"></i>Los nuevos usuarios deben ingresar este código al registrarse. Cámbialo periódicamente.</div>
        </div>

        <div class="mt-3">
            <button type="submit" class="btn-primary-custom">
                <i class="bi bi-floppy2-fill"></i> Guardar Cambios
            </button>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card-panel">
            <div class="section-title"><i class="bi bi-cpu me-1"></i>Información del Sistema</div>
            <div class="sysinfo-row">
                <span class="sysinfo-label"><i class="bi bi-box-seam"></i>Laravel</span>
                <span class="sysinfo-value">{{ app()->version() }}</span>
            </div>
            <div class="sysinfo-row">
                <span class="sysinfo-label"><i class="bi bi-filetype-php"></i>PHP</span>
                <span class="sysinfo-value">{{ PHP_VERSION }}</span>
            </div>
            <div class="sysinfo-row">
                <span class="sysinfo-label"><i class="bi bi-database-fill"></i>Base de Datos</span>
                <span class="sysinfo-value">MySQL</span>
            </div>
            <div class="sysinfo-row">
                <span class="sysinfo-label"><i class="bi bi-calendar3"></i>Entorno</span>
                <span class="sysinfo-value">{{ app()->environment() }}</span>
            </div>
            <div class="sysinfo-row">
                <span class="sysinfo-label"><i class="bi bi-hdd-network"></i>IP Servidor</span>
                <span class="sysinfo-value">{{ $_SERVER['SERVER_ADDR'] ?? 'localhost' }}</span>
            </div>
        </div>

        {{-- Links rápidos a otras secciones --}}
        <div class="card-panel">
            <div class="section-title"><i class="bi bi-link-45deg me-1"></i>Accesos Rápidos</div>
            <div class="d-flex flex-column gap-2">
                <a href="{{ route('admin.sistema.actividad') }}" class="btn btn-outline-secondary btn-sm text-start">
                    <i class="bi bi-clock-history me-1"></i> Log de Actividad
                </a>
                <a href="{{ route('admin.sistema.backup') }}" class="btn btn-outline-secondary btn-sm text-start">
                    <i class="bi bi-cloud-arrow-down-fill me-1"></i> Respaldo (Backup)
                </a>
                <a href="{{ route('admin.sistema.email-notif') }}" class="btn btn-outline-secondary btn-sm text-start">
                    <i class="bi bi-envelope-check me-1"></i> Notificaciones por Email
                </a>
                <a href="{{ route('admin.sistema.whatsapp') }}" class="btn btn-outline-secondary btn-sm text-start">
                    <i class="bi bi-whatsapp me-1"></i> Integración WhatsApp
                </a>
            </div>
        </div>
    </div>
</div>
</form>

{{-- Zona peligrosa --}}
<div class="card-panel mt-2" style="border:2px solid #fee2e2;background:#fff5f5;">
    <div class="section-title" style="color:#dc2626;border-color:#dc2626;">
        <i class="bi bi-exclamation-triangle-fill me-1"></i>Zona Peligrosa — Limpiar Datos
    </div>
    <p class="text-muted small mb-3">Estas acciones son <strong>irreversibles</strong>. Asegúrate de tener un backup antes de continuar.</p>
    <div class="d-flex flex-wrap gap-3">
        <button type="button" class="btn btn-outline-danger" onclick="abrirModalLimpiar('estudiantes')">
            <i class="bi bi-people-fill me-1"></i>Borrar todos los estudiantes
        </button>
        <button type="button" class="btn btn-danger" onclick="abrirModalLimpiar('todo')">
            <i class="bi bi-trash3-fill me-1"></i>Borrar todo (estudiantes + datos académicos)
        </button>
    </div>
</div>
</div>

{{-- ══ TAB: DATOS INSTITUCIONALES ══════════════════════════════════════════ --}}
<div id="tab-institucional" class="cfg-panel {{ $activeTab === 'institucional' ? 'active' : '' }}">
<form action="{{ route('admin.sistema.institucional.update') }}" method="POST">
@csrf
<div class="row g-4">
    <div class="col-lg-8">
        <div class="card-panel">
            <div class="section-title"><i class="bi bi-building-fill me-1"></i>Identificación del Centro</div>
            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label-custom">Nombre Oficial de la Institución</label>
                    <input type="text" name="nombre_institucion" class="form-control form-control-custom"
                           value="{{ old('nombre_institucion', $inst['nombre_institucion'] ?? '') }}"
                           placeholder="Nombre completo como aparece en documentos oficiales">
                    <div class="info-note"><i class="bi bi-info-circle"></i>Usado en PDFs de boletines, constancias y reportes MINERD.</div>
                </div>
                <div class="col-sm-6">
                    <label class="form-label-custom">Código del Centro (MINERD)</label>
                    <input type="text" name="codigo_centro" class="form-control form-control-custom"
                           value="{{ old('codigo_centro', $inst['codigo_centro'] ?? '') }}"
                           placeholder="Ej. 15-001-0001">
                </div>
                <div class="col-sm-6">
                    <label class="form-label-custom">Tipo de Institución</label>
                    <select name="tipo_institucion" class="form-select form-control-custom">
                        <option value="">— Seleccione —</option>
                        <option value="publico"      {{ ($inst['tipo_institucion'] ?? '') === 'publico'      ? 'selected' : '' }}>Público</option>
                        <option value="privado"      {{ ($inst['tipo_institucion'] ?? '') === 'privado'      ? 'selected' : '' }}>Privado</option>
                        <option value="semi-privado" {{ ($inst['tipo_institucion'] ?? '') === 'semi-privado' ? 'selected' : '' }}>Semi-privado</option>
                    </select>
                </div>
                <div class="col-sm-6">
                    <label class="form-label-custom">Nivel Educativo</label>
                    <input type="text" name="nivel_educativo" class="form-control form-control-custom"
                           value="{{ old('nivel_educativo', $inst['nivel_educativo'] ?? '') }}"
                           placeholder="Ej. Nivel Secundario Técnico">
                </div>
                <div class="col-sm-6">
                    <label class="form-label-custom">Regional Educativa</label>
                    <input type="text" name="regional" class="form-control form-control-custom"
                           value="{{ old('regional', $inst['regional'] ?? '') }}"
                           placeholder="Ej. 10 — Santo Domingo">
                </div>
                <div class="col-sm-6">
                    <label class="form-label-custom">Distrito Educativo</label>
                    <input type="text" name="distrito" class="form-control form-control-custom"
                           value="{{ old('distrito', $inst['distrito'] ?? '') }}"
                           placeholder="Ej. 10-05">
                </div>
            </div>
        </div>

        <div class="card-panel">
            <div class="section-title"><i class="bi bi-person-badge-fill me-1"></i>Dirección del Centro</div>
            <div class="row g-3">
                <div class="col-sm-8">
                    <label class="form-label-custom">Nombre del Director/a</label>
                    <input type="text" name="nombre_director" class="form-control form-control-custom"
                           value="{{ old('nombre_director', $inst['nombre_director'] ?? '') }}"
                           placeholder="Nombre completo del director/a">
                </div>
                <div class="col-sm-4">
                    <label class="form-label-custom">Cargo</label>
                    <input type="text" name="cargo_director" class="form-control form-control-custom"
                           value="{{ old('cargo_director', $inst['cargo_director'] ?? 'Director/a') }}"
                           placeholder="Director/a">
                </div>
            </div>
        </div>

        <div class="card-panel">
            <div class="section-title"><i class="bi bi-geo-alt-fill me-1"></i>Contacto y Ubicación</div>
            <div class="row g-3">
                <div class="col-sm-6">
                    <label class="form-label-custom">Teléfono</label>
                    <input type="text" name="telefono" class="form-control form-control-custom"
                           value="{{ old('telefono', $inst['telefono'] ?? '') }}"
                           placeholder="(809) 000-0000">
                </div>
                <div class="col-sm-6">
                    <label class="form-label-custom">Correo Institucional</label>
                    <input type="email" name="email_institucional" class="form-control form-control-custom"
                           value="{{ old('email_institucional', $inst['email_institucional'] ?? '') }}"
                           placeholder="info@centro.edu.do">
                </div>
                <div class="col-12">
                    <label class="form-label-custom">Dirección Física</label>
                    <input type="text" name="direccion" class="form-control form-control-custom"
                           value="{{ old('direccion', $inst['direccion'] ?? '') }}"
                           placeholder="Calle, sector, número...">
                </div>
                <div class="col-sm-6">
                    <label class="form-label-custom">Municipio</label>
                    <input type="text" name="municipio" class="form-control form-control-custom"
                           value="{{ old('municipio', $inst['municipio'] ?? '') }}"
                           placeholder="Ej. Santo Domingo Este">
                </div>
                <div class="col-sm-6">
                    <label class="form-label-custom">Provincia</label>
                    <input type="text" name="provincia" class="form-control form-control-custom"
                           value="{{ old('provincia', $inst['provincia'] ?? '') }}"
                           placeholder="Ej. Santo Domingo">
                </div>
            </div>
        </div>

        <button type="submit" class="btn-primary-custom">
            <i class="bi bi-floppy2-fill"></i> Guardar Datos Institucionales
        </button>
    </div>

    <div class="col-lg-4">
        <div class="card-panel" style="background:#eff6ff;border-color:#bfdbfe;">
            <div class="section-title" style="color:#1d4ed8;border-color:#1d4ed8;">
                <i class="bi bi-info-circle-fill me-1"></i>¿Dónde se usan?
            </div>
            <ul style="font-size:.82rem;color:#374151;padding-left:1.2rem;line-height:1.9;margin:0;">
                <li>Encabezado de <strong>boletines PDF</strong></li>
                <li>Constancias de <strong>matrícula y estudios</strong></li>
                <li>Informes del <strong>Registro MINERD</strong></li>
                <li>Reportes de <strong>asistencia y pagos</strong></li>
                <li>Firma del <strong>director/a</strong> en documentos</li>
                <li>Chat <strong>Zura</strong> (contexto institucional)</li>
                <li>Formulario de <strong>pre-matrícula pública</strong></li>
            </ul>
        </div>

        @php
            $camposCompletos = collect(['nombre_institucion','nombre_director','codigo_centro','telefono','direccion'])
                ->filter(fn($k) => !empty($inst[$k] ?? ''))
                ->count();
            $pct = round($camposCompletos / 5 * 100);
        @endphp
        <div class="card-panel">
            <div class="section-title"><i class="bi bi-bar-chart-fill me-1"></i>Completitud del Perfil</div>
            <div style="font-size:.82rem;color:#374151;margin-bottom:.5rem;">
                {{ $camposCompletos }} de 5 campos principales completados
            </div>
            <div style="height:8px;background:#e5e7eb;border-radius:99px;overflow:hidden;">
                <div style="height:100%;width:{{ $pct }}%;background:{{ $pct >= 80 ? '#15803d' : ($pct >= 40 ? '#d97706' : '#dc2626') }};border-radius:99px;transition:width .4s;"></div>
            </div>
            <div style="font-size:.75rem;color:#64748b;margin-top:.35rem;">{{ $pct }}% completado</div>
        </div>
    </div>
</div>
</form>
</div>

{{-- ══ TAB: MÓDULOS ═════════════════════════════════════════════════════════ --}}
<div id="tab-modulos" class="cfg-panel {{ $activeTab === 'modulos' ? 'active' : '' }}">
<form action="{{ route('admin.sistema.modulos.update') }}" method="POST">
@csrf
<div class="card-panel">
    <div class="section-title"><i class="bi bi-grid-fill me-1"></i>Módulos del Sistema</div>
    <p class="text-muted small mb-3">Activa o desactiva módulos según las necesidades de tu institución. Los módulos inactivos se ocultan del menú y sus rutas quedan bloqueadas.</p>

    <div class="modulo-grid">
        @foreach($modulos as $key => $modulo)
        @php
            $activo = \App\Models\ConfigInstitucional::withoutGlobalScopes()
                ->where('clave', "modulo_{$key}_activo")
                ->value('valor') === '1';
        @endphp
        <div class="modulo-card {{ $activo ? 'activo' : '' }}" id="card-{{ $key }}">
            <div class="modulo-info">
                <div class="modulo-icon" style="background:{{ $modulo['color'] }}18;color:{{ $modulo['color'] }};">
                    <i class="bi {{ $modulo['icon'] }}"></i>
                </div>
                <div>
                    <div class="modulo-label">{{ $modulo['label'] }}</div>
                </div>
            </div>
            <div class="form-check form-switch mb-0">
                <input class="form-check-input" type="checkbox"
                       name="modulo_{{ $key }}" id="mod_{{ $key }}"
                       value="1" {{ $activo ? 'checked' : '' }}
                       onchange="document.getElementById('card-{{ $key }}').classList.toggle('activo', this.checked)">
            </div>
        </div>
        @endforeach
    </div>

    <div class="mt-4 pt-2 border-top">
        <button type="submit" class="btn-primary-custom">
            <i class="bi bi-floppy2-fill"></i> Guardar Módulos
        </button>
        <span class="text-muted small ms-3">Los cambios se reflejan inmediatamente en el menú.</span>
    </div>
</div>
</form>
</div>

{{-- ══ TAB: LOGO Y FAVICON ══════════════════════════════════════════════════ --}}
<div id="tab-apariencia" class="cfg-panel {{ $activeTab === 'apariencia' ? 'active' : '' }}">
<div class="row g-4">
    <div class="col-md-6">
        <div class="card-panel">
            <div class="section-title"><i class="bi bi-image me-1"></i>Logotipo del Sistema</div>

            @if(!empty($settings['system_logo']))
            <div class="current-logo-wrapper mb-3">
                <img src="{{ asset('storage/' . $settings['system_logo']) }}"
                     alt="Logotipo" style="max-height:100px;max-width:220px;border-radius:8px;">
            </div>
            <form action="{{ route('admin.sistema.logo.delete') }}" method="POST" class="mb-3"
                  onsubmit="return confirm('¿Eliminar el logotipo actual?')">
                @csrf
                <button type="submit" class="btn-danger-custom w-100 justify-content-center">
                    <i class="bi bi-trash3"></i> Eliminar logo
                </button>
            </form>
            @else
            <div class="logo-placeholder mb-3"><i class="bi bi-image"></i><span>Sin logotipo cargado</span></div>
            @endif

            <form action="{{ route('admin.sistema.logo') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <label class="form-label-custom">Subir nuevo logotipo</label>
                <input type="file" name="logo" class="form-control form-control-custom mb-2 @error('logo') is-invalid @enderror"
                       accept=".png,.jpg,.jpeg,.svg" style="font-size:.82rem;">
                @error('logo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <div class="info-note mb-2"><i class="bi bi-info-circle"></i>PNG, JPG o SVG. Máx. 512 KB.</div>
                <button type="submit" class="btn-upload"><i class="bi bi-cloud-upload"></i> Subir Logotipo</button>
            </form>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card-panel">
            <div class="section-title"><i class="bi bi-lightning-charge me-1"></i>Favicon</div>
            <p style="font-size:.8rem;color:#6b7280;margin-bottom:.85rem;">
                Ícono que aparece en la pestaña del navegador. Recomendado: 32×32 px PNG o ICO.
            </p>

            @if(!empty($settings['system_favicon']))
            <div class="d-flex align-items-center gap-3 mb-3 p-2" style="background:#f8faff;border-radius:8px;border:1px solid #e5e7eb;">
                <img src="{{ asset('storage/' . $settings['system_favicon']) }}"
                     alt="Favicon" style="width:32px;height:32px;border-radius:4px;object-fit:contain;">
                <span style="font-size:.8rem;color:#374151;">Favicon actual</span>
                <form action="{{ route('admin.sistema.favicon.delete') }}" method="POST" class="ms-auto"
                      onsubmit="return confirm('¿Eliminar el favicon?')">
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

            <form action="{{ route('admin.sistema.favicon') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <label class="form-label-custom">Subir nuevo favicon</label>
                <input type="file" name="favicon" class="form-control form-control-custom mb-2 @error('favicon') is-invalid @enderror"
                       accept=".png,.jpg,.jpeg,.ico,.svg" style="font-size:.82rem;">
                @error('favicon')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <div class="info-note mb-2"><i class="bi bi-info-circle"></i>PNG, ICO, JPG o SVG. Máx. 256 KB.</div>
                <button type="submit" class="btn-upload"><i class="bi bi-cloud-upload"></i> Subir Favicon</button>
            </form>
        </div>
    </div>
</div>
</div>

{{-- ══ TAB: SEGURIDAD ═══════════════════════════════════════════════════════ --}}
<div id="tab-seguridad" class="cfg-panel {{ $activeTab === 'seguridad' ? 'active' : '' }}">
<form action="{{ route('admin.sistema.update') }}" method="POST">
@csrf
<div class="row g-4">
    <div class="col-lg-7">
        <div class="card-panel">
            <div class="section-title"><i class="bi bi-shield-lock me-1"></i>Parámetros de Seguridad</div>
            <div class="row g-3">
                <div class="col-sm-6">
                    <label class="form-label-custom">Máx. intentos de login</label>
                    <input type="number" name="max_login_attempts" class="form-control form-control-custom"
                           min="3" max="20" value="{{ old('max_login_attempts', $settings['max_login_attempts'] ?? 5) }}">
                    <div class="info-note"><i class="bi bi-info-circle"></i>Bloqueo temporal tras superar este límite.</div>
                </div>
                <div class="col-sm-6">
                    <label class="form-label-custom">Tiempo de sesión (minutos)</label>
                    <input type="number" name="session_timeout" class="form-control form-control-custom"
                           min="15" max="480" value="{{ old('session_timeout', $settings['session_timeout'] ?? 120) }}">
                    <div class="info-note"><i class="bi bi-info-circle"></i>Sesión inactiva se cierra automáticamente.</div>
                </div>
            </div>
            <div class="mt-4 pt-2 border-top">
                <button type="submit" class="btn-primary-custom">
                    <i class="bi bi-floppy2-fill"></i> Guardar Seguridad
                </button>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card-panel" style="background:#fef9c3;border-color:#fcd34d;">
            <div class="section-title" style="color:#92400e;border-color:#d97706;">
                <i class="bi bi-exclamation-triangle-fill me-1"></i>Recomendaciones
            </div>
            <ul style="font-size:.82rem;color:#78350f;padding-left:1.2rem;line-height:1.9;margin:0;">
                <li>Máximo <strong>5 intentos</strong> de login es lo estándar</li>
                <li>Sesión de <strong>120 minutos</strong> para uso en laboratorios</li>
                <li>Cambia el <strong>código de registro</strong> cada período</li>
                <li>Realiza <strong>backups semanales</strong> desde la sección de respaldo</li>
            </ul>
        </div>
    </div>
</div>
</form>
</div>

{{-- Modal confirmación zona peligrosa --}}
<div class="modal fade" id="modalLimpiar" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0" style="border-radius:16px;overflow:hidden;">
            <div class="modal-header" style="background:#dc2626;color:#fff;border:none;">
                <h5 class="modal-title fw-bold"><i class="bi bi-exclamation-triangle-fill me-2"></i>Confirmar eliminación</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div id="limpiarDescripcion" class="alert alert-danger border-0 rounded-3 mb-3" style="font-size:.875rem;"></div>
                <p class="fw-semibold mb-2" style="font-size:.875rem;">Escribe <strong style="color:#dc2626;">CONFIRMAR</strong> para continuar:</p>
                <input type="text" id="inputConfirmacion" class="form-control form-control-lg"
                       placeholder="Escribe CONFIRMAR" style="border:2px solid #fca5a5;border-radius:10px;font-weight:700;"
                       oninput="verificarConfirmacion(this.value)">
            </div>
            <div class="modal-footer border-0 px-4 pb-4">
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
const activeTab = '{{ $activeTab }}';

function cambiarTab(nombre) {
    document.querySelectorAll('.cfg-tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.cfg-panel').forEach(p => p.classList.remove('active'));
    document.querySelector(`[onclick="cambiarTab('${nombre}')"]`).classList.add('active');
    document.getElementById(`tab-${nombre}`).classList.add('active');
    history.replaceState(null, '', '#' + nombre);
}

// Restaurar pestaña desde hash o session
const hash = location.hash.replace('#', '');
if (['general','institucional','modulos','apariencia','seguridad'].includes(hash)) {
    cambiarTab(hash);
}

const descripciones = {
    estudiantes: '⚠️ Se eliminarán <strong>todos los estudiantes</strong> y sus datos: calificaciones, asistencias, matrículas y observaciones. Los docentes y configuraciones no serán afectados.',
    todo: '🚨 Se eliminarán <strong>TODOS los datos académicos</strong>: estudiantes, calificaciones, asistencias, matrículas, grupos, secciones y horarios. Solo se conservarán usuarios y configuración.',
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
