@extends('layouts.admin')
@section('page-title', 'Configuración del Boletín')

@push('styles')
<style>
    .cfg-card {
        background: #fff;
        border-radius: 12px;
        border: 1px solid #e5e7eb;
        box-shadow: 0 1px 4px rgba(0,0,0,.07);
        margin-bottom: 1.25rem;
    }
    .cfg-card-header {
        display: flex;
        align-items: center;
        gap: .65rem;
        padding: .9rem 1.25rem .75rem;
        border-bottom: 1px solid #e5e7eb;
    }
    .cfg-card-header .cfg-icon {
        width: 34px; height: 34px;
        border-radius: 8px;
        background: var(--primary);
        color: #fff;
        display: flex; align-items: center; justify-content: center;
        font-size: 1rem; flex-shrink: 0;
    }
    .cfg-card-header h6 { margin: 0; font-weight: 700; font-size: .9rem; color: #1e293b; }
    .cfg-card-header small { color: #9ca3af; font-size: .75rem; }
    .cfg-body { padding: 1.1rem 1.25rem; }

    .form-label {
        font-size: .83rem;
        font-weight: 600;
        color: #374151;
        margin-bottom: .3rem;
    }
    .form-label .req { color: #c0392b; margin-left: 2px; }
    .form-control, .form-select {
        border-radius: 8px;
        border: 1.5px solid #e5e7eb;
        font-size: .88rem;
        transition: border-color .15s, box-shadow .15s;
    }
    .form-control:focus, .form-select:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(30,58,110,.1);
    }
    .form-hint {
        font-size: .74rem;
        color: #9ca3af;
        margin-top: .25rem;
        line-height: 1.35;
    }
    .input-group-text {
        background: #f8faff;
        border-color: #e5e7eb;
        color: #6b7280;
        font-size: .83rem;
    }

    /* Toggle rows */
    .toggle-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: .7rem 1rem;
        border: 1.5px solid #e5e7eb;
        border-radius: 8px;
        margin-bottom: .6rem;
        background: #f9fafb;
        transition: border-color .15s, background .15s;
    }
    .toggle-row:hover { border-color: var(--primary); background: #f0f6ff; }
    .toggle-row:last-child { margin-bottom: 0; }
    .toggle-row .toggle-info h6 {
        margin: 0; font-size: .87rem; font-weight: 700; color: #374151;
    }
    .toggle-row .toggle-info small { color: #9ca3af; font-size: .74rem; }
    .form-switch .form-check-input {
        width: 2.4em; height: 1.3em; cursor: pointer;
    }
    .form-check-input:checked {
        background-color: var(--primary);
        border-color: var(--primary);
    }

    /* Sidebar logo */
    .logo-preview-wrap {
        background: #f3f4f6;
        border-radius: 8px;
        border: 2px dashed #d1d5db;
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 100px;
        padding: .75rem;
        margin-bottom: .85rem;
        transition: border-color .15s;
    }
    .logo-preview-wrap:hover { border-color: var(--primary); }
    .logo-preview-wrap img { max-height: 90px; max-width: 100%; object-fit: contain; border-radius: 4px; }

    /* Botones */
    .btn-save {
        background: var(--primary);
        color: #fff;
        border: none;
        border-radius: 8px;
        font-weight: 700;
        font-size: .9rem;
        padding: .65rem 1.4rem;
        transition: opacity .15s, transform .12s;
    }
    .btn-save:hover { opacity: .88; color: #fff; transform: translateY(-1px); }

    /* Preview box */
    .preview-box {
        border: 1.5px solid #e5e7eb;
        border-radius: 8px;
        overflow: hidden;
        font-size: .78rem;
    }
    .preview-box .pv-hdr {
        background: #1e3a6e;
        color: #fff;
        font-size: .65rem;
        font-weight: 700;
        letter-spacing: .12em;
        text-transform: uppercase;
        padding: 2px 8px;
    }
    .preview-box .pv-title {
        background: #c0392b;
        color: #fff;
        text-align: center;
        font-size: .65rem;
        font-weight: 800;
        letter-spacing: .1em;
        padding: 2px;
    }
    .preview-box .pv-body {
        padding: .5rem .7rem;
        background: #fff;
    }
    .preview-box .pv-inst { font-size: .85rem; font-weight: 800; color: #1e3a6e; }
    .preview-box .pv-sub  { font-size: .68rem; color: #6b7280; }
    .preview-box .pv-lema { font-size: .65rem; color: #9ca3af; font-style: italic; }

    /* Quick links */
    .qlink {
        display: flex; align-items: center; gap: .6rem;
        background: #f3f4f6; border: 1.5px solid #e5e7eb;
        border-radius: 8px; padding: .65rem .9rem;
        text-decoration: none; color: var(--primary);
        font-size: .86rem; font-weight: 600;
        transition: background .13s, border-color .13s;
    }
    .qlink:hover { background: #eef3fb; border-color: var(--primary); color: var(--primary); }

    /* Year pill */
    .year-pill {
        background: #fef3c7; color: #92400e;
        border: 1px solid #fcd34d;
        border-radius: 20px; font-size: .8rem;
        font-weight: 700; padding: .3rem .9rem;
    }

    [data-theme="dark"] .cfg-card { background: #1e293b; border-color: #334155; }
    [data-theme="dark"] .cfg-card-header { border-bottom-color: #334155; }
    [data-theme="dark"] .cfg-card-header h6 { color: #e2e8f0; }
    [data-theme="dark"] .qlink { background: #162032; border-color: #334155; color: #93c5fd; }
    [data-theme="dark"] .qlink:hover { background: #1e3a5f; border-color: var(--primary); }
    [data-theme="dark"] .year-pill { background: #1c1000; color: #fcd34d; border-color: #78350f; }
</style>
@endpush

@section('content')

<x-breadcrumb :items="[
    ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
    ['label' => 'Boletines',  'url' => route('admin.boletines.index')],
    ['label' => 'Configuración'],
]" />

{{-- ── Page header ── --}}
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold mb-0" style="color:var(--primary)">
            <i class="bi bi-gear-wide-connected me-2"></i>Configuración del Boletín
        </h4>
        <p class="text-muted mb-0 mt-1" style="font-size:.85rem;">
            Personaliza todos los datos institucionales que aparecen en los boletines PDF.
        </p>
    </div>
    @if($schoolYear)
    <span class="year-pill"><i class="bi bi-calendar2-check me-1"></i>{{ $schoolYear->nombre }}</span>
    @endif
</div>

{{-- Flash --}}
@if(session('success'))
<div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2">
    <i class="bi bi-check-circle-fill"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif
@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show d-flex align-items-center gap-2">
    <i class="bi bi-exclamation-triangle-fill"></i>{{ session('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="row g-4 align-items-start">

    {{-- ══════════════════════════════════════
         COLUMNA IZQUIERDA — FORMULARIO PRINCIPAL
    ══════════════════════════════════════ --}}
    <div class="col-lg-8">
    <form action="{{ route('admin.boletines.config.update') }}" method="POST" enctype="multipart/form-data" id="frmConfig">
    @csrf

    {{-- ─── BLOQUE 1: INSTITUCIÓN ─────────────────────── --}}
    <div class="cfg-card">
        <div class="cfg-card-header">
            <div class="cfg-icon"><i class="bi bi-building"></i></div>
            <div>
                <h6>Datos de la Institución</h6>
                <small>Información que aparece en el encabezado del boletín</small>
            </div>
        </div>
        <div class="cfg-body">
            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label">Nombre del Centro <span class="req">*</span></label>
                    <input type="text" name="nombre_institucion" class="form-control @error('nombre_institucion') is-invalid @enderror"
                           value="{{ old('nombre_institucion', $boletinConfig->nombre_institucion ?? '') }}"
                           placeholder="Ej. Politécnico Salesiano Arquides Calderón">
                    @error('nombre_institucion')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-4">
                    <label class="form-label">Código del Centro</label>
                    <input type="text" name="codigo" class="form-control"
                           value="{{ old('codigo', $boletinConfig->codigo ?? '') }}"
                           placeholder="Ej. 26-123">
                    <div class="form-hint">Aparece en la esquina del encabezado</div>
                </div>

                <div class="col-md-8">
                    <label class="form-label">Nivel Educativo</label>
                    <input type="text" name="nivel_educativo" class="form-control"
                           value="{{ old('nivel_educativo', $boletinConfig->nivel_educativo ?? 'Nivel Secundario') }}"
                           placeholder="Ej. Nivel Secundario / Nivel Primario">
                </div>

                <div class="col-md-6">
                    <label class="form-label">Regional</label>
                    <div class="input-group">
                        <span class="input-group-text">Regional</span>
                        <input type="text" name="regional" class="form-control"
                               value="{{ old('regional', $boletinConfig->regional ?? '') }}"
                               placeholder="Ej. 10-02">
                    </div>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Distrito</label>
                    <div class="input-group">
                        <span class="input-group-text">Distrito</span>
                        <input type="text" name="distrito" class="form-control"
                               value="{{ old('distrito', $boletinConfig->distrito ?? '') }}"
                               placeholder="Ej. 15-04">
                    </div>
                </div>

                <div class="col-md-5">
                    <label class="form-label">Municipio / Ciudad</label>
                    <input type="text" name="municipio" class="form-control"
                           value="{{ old('municipio', $boletinConfig->municipio ?? '') }}"
                           placeholder="Ej. Santiago de los Caballeros">
                </div>

                <div class="col-md-7">
                    <label class="form-label">Dirección</label>
                    <input type="text" name="direccion" class="form-control"
                           value="{{ old('direccion', $boletinConfig->direccion ?? '') }}"
                           placeholder="Ej. Calle Principal #45, Los Jardines">
                </div>

                <div class="col-md-4">
                    <label class="form-label">Teléfono</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-telephone"></i></span>
                        <input type="text" name="telefono" class="form-control"
                               value="{{ old('telefono', $boletinConfig->telefono ?? '') }}"
                               placeholder="(809) 000-0000">
                    </div>
                </div>

                <div class="col-md-8">
                    <label class="form-label">Lema / Subtítulo</label>
                    <input type="text" name="lema" class="form-control"
                           value="{{ old('lema', $boletinConfig->lema ?? '') }}"
                           placeholder="Ej. Educando con valores para la vida">
                    <div class="form-hint">Aparece en cursiva bajo el nombre del centro</div>
                </div>
            </div>
        </div>
    </div>

    {{-- ─── BLOQUE 2: AUTORIDADES / FIRMAS ────────────── --}}
    <div class="cfg-card">
        <div class="cfg-card-header">
            <div class="cfg-icon"><i class="bi bi-person-badge"></i></div>
            <div>
                <h6>Autoridades del Centro</h6>
                <small>Nombres que aparecerán en las líneas de firma del boletín</small>
            </div>
        </div>
        <div class="cfg-body">
            <div class="row g-3">
                {{-- Director/a --}}
                <div class="col-12">
                    <label class="form-label">Director(a)</label>
                    <div class="row g-2">
                        <div class="col-3">
                            <select name="titulo_director" class="form-select">
                                <option value="">Título</option>
                                @foreach(['Lic.','Prof.','Ing.','Dr.','Dra.','Mgtr.','Arq.'] as $t)
                                <option value="{{ $t }}"
                                    {{ old('titulo_director', $boletinConfig->titulo_director ?? 'Lic.') === $t ? 'selected' : '' }}>
                                    {{ $t }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-9">
                            <input type="text" name="director" class="form-control"
                                   value="{{ old('director', $boletinConfig->director ?? '') }}"
                                   placeholder="Nombre completo del Director(a)">
                        </div>
                    </div>
                    <div class="form-hint">Aparece en la primera firma del boletín</div>
                </div>

                {{-- Encargado Académico --}}
                <div class="col-12">
                    <label class="form-label">Encargado(a) Académico(a)</label>
                    <div class="row g-2">
                        <div class="col-3">
                            <select name="titulo_encargado" class="form-select">
                                <option value="">Título</option>
                                @foreach(['Lic.','Prof.','Ing.','Dr.','Dra.','Mgtr.','Arq.'] as $t)
                                <option value="{{ $t }}"
                                    {{ old('titulo_encargado', $boletinConfig->titulo_encargado ?? 'Lic.') === $t ? 'selected' : '' }}>
                                    {{ $t }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-9">
                            <input type="text" name="encargado_academico" class="form-control"
                                   value="{{ old('encargado_academico', $boletinConfig->encargado_academico ?? '') }}"
                                   placeholder="Nombre completo del Encargado(a) Académico(a)">
                        </div>
                    </div>
                    <div class="form-hint">Aparece en la segunda firma del boletín</div>
                </div>

                {{-- Info firmas adicionales --}}
                <div class="col-12">
                    <div class="alert alert-info py-2 px-3 mb-0" style="font-size:.81rem;border-radius:8px;">
                        <i class="bi bi-info-circle me-1"></i>
                        <strong>Docente Guía</strong> — se toma automáticamente del tutor asignado al grupo del estudiante.<br>
                        <strong>Padre/Madre/Tutor</strong> — espacio en blanco para firma manual al momento de la entrega.
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ─── BLOQUE 3: TEXTOS DEL BOLETÍN ──────────────── --}}
    <div class="cfg-card">
        <div class="cfg-card-header">
            <div class="cfg-icon"><i class="bi bi-card-text"></i></div>
            <div>
                <h6>Textos del Boletín</h6>
                <small>Mensajes que se imprimirán en todos los boletines</small>
            </div>
        </div>
        <div class="cfg-body">
            <div class="mb-3">
                <label class="form-label">Observaciones Generales predeterminadas</label>
                <textarea name="observaciones_generales" rows="3" class="form-control"
                          placeholder="Texto de observaciones que aparecerá en todos los boletines (opcional)"
                >{{ old('observaciones_generales', $boletinConfig->observaciones_generales ?? '') }}</textarea>
                <div class="form-hint">Se incluye al final de la sección de observaciones de cada boletín</div>
            </div>
            <div class="mb-0">
                <label class="form-label">Pie de Página</label>
                <textarea name="pie_pagina" rows="2" class="form-control"
                          placeholder="Texto adicional que aparece al pie de cada boletín (opcional)"
                >{{ old('pie_pagina', $boletinConfig->pie_pagina ?? '') }}</textarea>
            </div>
        </div>
    </div>

    {{-- ─── BLOQUE 4: SECCIONES VISIBLES ──────────────── --}}
    <div class="cfg-card">
        <div class="cfg-card-header">
            <div class="cfg-icon"><i class="bi bi-layout-text-window"></i></div>
            <div>
                <h6>Secciones del Boletín</h6>
                <small>Controla qué secciones se muestran en el PDF</small>
            </div>
        </div>
        <div class="cfg-body">
            <div class="toggle-row">
                <div class="toggle-info">
                    <h6><i class="bi bi-calendar-check text-muted me-2"></i>Resumen de Asistencia</h6>
                    <small>Tabla con días presentes, ausencias, tardanzas y % asistencia</small>
                </div>
                <div class="form-check form-switch mb-0">
                    <input class="form-check-input" type="checkbox" role="switch"
                           id="mostrar_asistencia" name="mostrar_asistencia" value="1"
                           {{ old('mostrar_asistencia', $boletinConfig->mostrar_asistencia ?? true) ? 'checked' : '' }}>
                </div>
            </div>
            <div class="toggle-row">
                <div class="toggle-info">
                    <h6><i class="bi bi-check2-square text-muted me-2"></i>Indicadores de Logro</h6>
                    <small>Sección de evaluaciones por indicador (IL) del módulo MINERD</small>
                </div>
                <div class="form-check form-switch mb-0">
                    <input class="form-check-input" type="checkbox" role="switch"
                           id="mostrar_indicadores" name="mostrar_indicadores" value="1"
                           {{ old('mostrar_indicadores', $boletinConfig->mostrar_indicadores ?? true) ? 'checked' : '' }}>
                </div>
            </div>
        </div>
    </div>

    {{-- ─── GUARDAR ─────────────────────────────────────── --}}
    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
        <a href="{{ route('admin.boletines.index') }}" class="btn btn-outline-secondary px-4">
            Cancelar
        </a>
        <button type="submit" class="btn btn-save px-5">
            <i class="bi bi-floppy me-2"></i>Guardar Configuración
        </button>
    </div>

    </form>
    </div>

    {{-- ══════════════════════════════════════
         COLUMNA DERECHA — SIDEBAR
    ══════════════════════════════════════ --}}
    <div class="col-lg-4">

        {{-- ─── Logo ───────────────────────────────────── --}}
        <div class="cfg-card">
            <div class="cfg-card-header">
                <div class="cfg-icon"><i class="bi bi-image"></i></div>
                <div>
                    <h6>Logo del Centro</h6>
                    <small>Aparece en el encabezado del boletín</small>
                </div>
            </div>
            <div class="cfg-body">
                <div class="logo-preview-wrap" id="logoPreview">
                    @if(!empty($boletinConfig->logo))
                        <img src="{{ asset('storage/' . $boletinConfig->logo) }}"
                             alt="Logo" id="logoImg">
                    @else
                        <div class="text-center text-muted" id="logoPlaceholder">
                            <i class="bi bi-image" style="font-size:2.5rem;opacity:.3;display:block;"></i>
                            <span style="font-size:.78rem;">Sin logo cargado</span>
                        </div>
                    @endif
                </div>

                <form action="{{ route('admin.boletines.config.update') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-2">
                        <input type="file" name="logo" id="logoInput"
                               class="form-control form-control-sm @error('logo') is-invalid @enderror"
                               accept="image/png,image/jpeg">
                        @error('logo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="form-hint mt-1">PNG o JPG · máx. 1 MB · recomendado 200×200 px</div>
                    </div>
                    <button type="submit" class="btn btn-save btn-sm w-100" style="font-size:.83rem;">
                        <i class="bi bi-cloud-upload me-1"></i>Subir Logo
                    </button>
                </form>
            </div>
        </div>

        {{-- ─── Vista previa del encabezado ─────────────── --}}
        <div class="cfg-card">
            <div class="cfg-card-header">
                <div class="cfg-icon"><i class="bi bi-eye"></i></div>
                <div>
                    <h6>Vista Previa</h6>
                    <small>Cómo se verá el encabezado del PDF</small>
                </div>
            </div>
            <div class="cfg-body p-3">
                <div class="preview-box">
                    <div class="pv-hdr">República Dominicana · Ministerio de Educación (MINERD)</div>
                    <div class="pv-body">
                        <div class="pv-inst" id="pv-inst">
                            {{ $boletinConfig->nombre_institucion ?? 'Nombre del Centro' }}
                        </div>
                        <div class="pv-sub" id="pv-nivel">
                            {{ $boletinConfig->nivel_educativo ?? 'Nivel Secundario' }}
                        </div>
                        <div class="pv-lema" id="pv-lema">
                            {{ $boletinConfig->lema ? '"'.$boletinConfig->lema.'"' : '' }}
                        </div>
                        <div class="pv-sub" id="pv-contacto" style="margin-top:2px;">
                            {{ implode(' · ', array_filter([$boletinConfig->municipio ?? '', $boletinConfig->telefono ? 'Tel. '.($boletinConfig->telefono ?? '') : ''])) }}
                        </div>
                    </div>
                    <div class="pv-title">&#9670; Boletín de Calificaciones &#9670;</div>
                </div>

                <div class="mt-3 mb-0">
                    <div style="font-size:.74rem;font-weight:700;color:#374151;margin-bottom:.4rem;">
                        <i class="bi bi-pen me-1 text-muted"></i>Firma del Director(a):
                    </div>
                    <div style="border-top:1.5px solid #374151;padding-top:3px;font-size:.78rem;font-weight:700;color:#1e293b;" id="pv-director">
                        {{ $boletinConfig->nombre_director_completo ?? 'Director(a)' }}
                    </div>
                    <div style="font-size:.7rem;color:#9ca3af;">Director(a) del Centro</div>
                </div>
            </div>
        </div>

        {{-- ─── Accesos rápidos ─────────────────────────── --}}
        <div class="cfg-card">
            <div class="cfg-card-header">
                <div class="cfg-icon" style="background:#6b7280;"><i class="bi bi-lightning"></i></div>
                <div><h6>Accesos Rápidos</h6></div>
            </div>
            <div class="cfg-body d-flex flex-column gap-2">
                <a href="{{ route('admin.boletines.index') }}" class="qlink">
                    <i class="bi bi-file-earmark-text"></i>
                    <span>Generar Boletines</span>
                    <i class="bi bi-arrow-right ms-auto" style="font-size:.8rem;opacity:.5;"></i>
                </a>
                @if($schoolYear)
                <div style="font-size:.76rem;color:#9ca3af;padding:.4rem .2rem;">
                    <i class="bi bi-info-circle me-1"></i>
                    Esta configuración aplica al año escolar <strong>{{ $schoolYear->nombre }}</strong>.
                </div>
                @endif
            </div>
        </div>

    </div>
</div>

@endsection

@push('scripts')
<script>
(function () {
    // Vista previa en tiempo real
    var fields = {
        'nombre_institucion' : 'pv-inst',
        'nivel_educativo'    : 'pv-nivel',
        'lema'               : 'pv-lema',
    };

    Object.entries(fields).forEach(function([name, id]) {
        var inp = document.querySelector('[name="' + name + '"]');
        var el  = document.getElementById(id);
        if (!inp || !el) return;
        inp.addEventListener('input', function () {
            var v = this.value.trim();
            if (name === 'lema') el.textContent = v ? '"' + v + '"' : '';
            else el.textContent = v || (name === 'nombre_institucion' ? 'Nombre del Centro' : '');
        });
    });

    // Preview contacto
    function refreshContacto() {
        var mun = (document.querySelector('[name="municipio"]')?.value || '').trim();
        var tel = (document.querySelector('[name="telefono"]')?.value || '').trim();
        var el  = document.getElementById('pv-contacto');
        if (!el) return;
        var parts = [];
        if (mun) parts.push(mun);
        if (tel) parts.push('Tel. ' + tel);
        el.textContent = parts.join(' · ');
    }
    ['municipio','telefono'].forEach(function(n) {
        var inp = document.querySelector('[name="' + n + '"]');
        if (inp) inp.addEventListener('input', refreshContacto);
    });

    // Preview director
    function refreshDirector() {
        var titulo = document.querySelector('[name="titulo_director"]')?.value || '';
        var nombre = (document.querySelector('[name="director"]')?.value || '').trim();
        var el     = document.getElementById('pv-director');
        if (!el) return;
        var full = [titulo, nombre].filter(Boolean).join(' ');
        el.textContent = full || 'Director(a)';
    }
    ['titulo_director','director'].forEach(function(n) {
        var inp = document.querySelector('[name="' + n + '"]');
        if (inp) inp.addEventListener(inp.tagName === 'SELECT' ? 'change' : 'input', refreshDirector);
    });

    // Logo preview
    var logoInput = document.getElementById('logoInput');
    if (logoInput) {
        logoInput.addEventListener('change', function () {
            var file = this.files[0];
            if (!file) return;
            var reader = new FileReader();
            reader.onload = function(e) {
                var wrap = document.getElementById('logoPreview');
                wrap.innerHTML = '<img src="' + e.target.result + '" alt="Logo" id="logoImg" style="max-height:90px;max-width:100%;object-fit:contain;border-radius:4px;">';
            };
            reader.readAsDataURL(file);
        });
    }
})();
</script>
@endpush
