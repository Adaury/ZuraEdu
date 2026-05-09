<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solicitud de Pre-matrícula — {{ $inst ?? config('app.name') }}</title>
    <meta name="description" content="Formulario de pre-matrícula en línea. Complete sus datos y recibirá un código de seguimiento.">
    <link href="/vendor/bootstrap-icons/bootstrap-icons.min.css" rel="stylesheet">
    <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    :root {
        --navy:   #0a0e27;
        --blue:   #1d4ed8;
        --blue-l: #3b82f6;
        --indigo: #4f46e5;
        --green:  #10b981;
        --white:  #ffffff;
        --g50:    #f8fafc;
        --g100:   #f1f5f9;
        --g200:   #e2e8f0;
        --g400:   #94a3b8;
        --g500:   #64748b;
        --g700:   #374151;
        --g900:   #0f172a;
    }
    html { scroll-behavior: smooth; }
    body { font-family: 'Inter', -apple-system, 'Segoe UI', sans-serif; background: var(--g50); color: var(--g900); line-height: 1.6; min-height: 100vh; }

    /* NAV */
    .nav { position: sticky; top: 0; z-index: 300; background: rgba(255,255,255,.96); backdrop-filter: blur(18px); border-bottom: 1px solid rgba(226,232,240,.8); box-shadow: 0 1px 20px rgba(15,23,42,.06); }
    .nav-inner { max-width: 1100px; margin: 0 auto; padding: 0 1.5rem; display: flex; align-items: center; height: 62px; gap: 1rem; }
    .nav-logo { display: flex; align-items: center; gap: .6rem; text-decoration: none; }
    .nav-logo-icon { width: 36px; height: 36px; border-radius: 10px; background: linear-gradient(135deg, #1e3a8a, #3b82f6); display: flex; align-items: center; justify-content: center; color: #fff; font-size: .85rem; box-shadow: 0 4px 12px rgba(30,64,175,.3); }
    .nav-logo-name { font-size: 1.05rem; font-weight: 900; color: var(--g900); letter-spacing: -.02em; }
    .nav-links { margin-left: auto; display: flex; align-items: center; gap: .5rem; }
    .nav-link { display: inline-flex; align-items: center; gap: .4rem; padding: .4rem .85rem; border-radius: 8px; font-size: .82rem; font-weight: 500; color: var(--g500); text-decoration: none; border: 1.5px solid var(--g200); transition: all .15s; }
    .nav-link:hover { color: var(--blue); border-color: var(--blue-l); background: #eff6ff; }
    .nav-link-primary { background: var(--blue); color: #fff; border-color: var(--blue); }
    .nav-link-primary:hover { background: #1e40af; color: #fff; border-color: #1e40af; }

    /* HERO */
    .hero { background: linear-gradient(145deg, #060b20 0%, #0e1f5e 50%, #1d4ed8 100%); padding: 3.5rem 1.5rem 4rem; text-align: center; position: relative; overflow: hidden; }
    .hero-glow { position: absolute; top: -80px; right: -60px; width: 480px; height: 480px; border-radius: 50%; background: radial-gradient(circle, rgba(99,102,241,.22) 0%, transparent 65%); pointer-events: none; }
    .hero-inner { max-width: 680px; margin: 0 auto; position: relative; z-index: 2; }
    .hero-badge { display: inline-flex; align-items: center; gap: .5rem; background: rgba(255,255,255,.1); border: 1px solid rgba(255,255,255,.18); color: rgba(255,255,255,.88); border-radius: 99px; padding: .32rem 1rem; font-size: .72rem; font-weight: 600; margin-bottom: 1.25rem; }
    .pulse { width: 7px; height: 7px; background: #34d399; border-radius: 50%; animation: pdot 2s infinite; }
    @keyframes pdot { 0%,100%{opacity:1;transform:scale(1)} 50%{opacity:.55;transform:scale(1.5)} }
    .hero h1 { font-size: clamp(1.8rem, 5vw, 2.8rem); font-weight: 900; color: #fff; line-height: 1.15; letter-spacing: -.03em; margin-bottom: 1rem; }
    .hero h1 span { background: linear-gradient(135deg, #6ee7b7, #34d399); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
    .hero-sub { font-size: .97rem; color: rgba(255,255,255,.62); max-width: 500px; margin: 0 auto 1.5rem; }
    .hero-code-hint { display: inline-flex; align-items: center; gap: .5rem; background: rgba(255,255,255,.08); border: 1px solid rgba(255,255,255,.15); border-radius: 10px; padding: .5rem 1rem; font-size: .8rem; color: rgba(255,255,255,.7); }

    /* FORM */
    .form-wrap { max-width: 780px; margin: -2rem auto 3rem; padding: 0 1.25rem; position: relative; z-index: 10; }
    .form-card { background: #fff; border-radius: 20px; box-shadow: 0 8px 40px rgba(15,23,42,.12), 0 2px 12px rgba(15,23,42,.06); overflow: hidden; }
    .form-section { padding: 2rem 2.25rem; border-bottom: 1px solid var(--g100); }
    .form-section:last-child { border-bottom: none; }
    .section-title { display: flex; align-items: center; gap: .6rem; font-size: .78rem; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; color: var(--blue); margin-bottom: 1.25rem; }
    .section-title i { font-size: 1rem; }

    .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
    .grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem; }
    .grid-1 { display: grid; grid-template-columns: 1fr; gap: 1rem; }

    .field { display: flex; flex-direction: column; gap: .35rem; }
    .field label { font-size: .8rem; font-weight: 600; color: var(--g700); }
    .field label span.req { color: #ef4444; }
    .field label span.opt { color: var(--g400); font-weight: 400; font-size: .73rem; }
    .field input, .field select, .field textarea {
        width: 100%; padding: .6rem .85rem; border: 1.5px solid var(--g200);
        border-radius: 9px; font-size: .9rem; color: var(--g900);
        background: #fff; transition: border-color .15s, box-shadow .15s;
        outline: none; font-family: inherit;
    }
    .field input:focus, .field select:focus, .field textarea:focus { border-color: var(--blue-l); box-shadow: 0 0 0 3px rgba(59,130,246,.15); }
    .field input.error, .field select.error, .field textarea.error { border-color: #ef4444; box-shadow: 0 0 0 3px rgba(239,68,68,.12); }
    .field-error { font-size: .78rem; color: #dc2626; margin-top: -.1rem; }
    .field-hint { font-size: .75rem; color: var(--g400); }

    /* File upload */
    .file-drop { border: 2px dashed var(--g200); border-radius: 10px; padding: 1rem; text-align: center; cursor: pointer; transition: all .15s; position: relative; }
    .file-drop:hover, .file-drop.has-file { border-color: var(--blue-l); background: #eff6ff; }
    .file-drop input[type="file"] { position: absolute; inset: 0; opacity: 0; cursor: pointer; width: 100%; height: 100%; }
    .file-drop-icon { font-size: 1.5rem; color: var(--g400); margin-bottom: .3rem; }
    .file-drop.has-file .file-drop-icon { color: var(--blue); }
    .file-drop-label { font-size: .78rem; color: var(--g500); }
    .file-drop.has-file .file-drop-label { color: var(--blue); font-weight: 600; }
    .file-drop-sub { font-size: .7rem; color: var(--g400); }

    /* SUBMIT */
    .form-footer { padding: 1.75rem 2.25rem; background: var(--g50); }
    .btn-submit { width: 100%; padding: .85rem; border-radius: 11px; background: linear-gradient(135deg, #1e3a8a, #2563eb); color: #fff; font-size: 1rem; font-weight: 700; border: none; cursor: pointer; transition: all .18s; box-shadow: 0 4px 16px rgba(30,64,175,.35); display: flex; align-items: center; justify-content: center; gap: .5rem; }
    .btn-submit:hover { transform: translateY(-1px); box-shadow: 0 8px 24px rgba(30,64,175,.42); }
    .btn-submit:active { transform: translateY(0); }
    .btn-submit:disabled { opacity: .7; cursor: not-allowed; transform: none; }
    .form-note { text-align: center; font-size: .8rem; color: var(--g400); margin-top: .9rem; }

    /* Alerts */
    .alert-error { background: #fef2f2; border: 1px solid #fecaca; border-radius: 10px; padding: 1rem 1.25rem; margin-bottom: 1.25rem; }
    .alert-error ul { list-style: none; padding: 0; margin: 0; }
    .alert-error li { font-size: .85rem; color: #dc2626; display: flex; align-items: center; gap: .4rem; padding: .15rem 0; }

    /* Docs section note */
    .docs-note { background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 10px; padding: .85rem 1rem; margin-bottom: 1.25rem; font-size: .82rem; color: #1e40af; display: flex; align-items: flex-start; gap: .5rem; }

    @media (max-width: 620px) {
        .grid-2, .grid-3 { grid-template-columns: 1fr; }
        .form-section { padding: 1.5rem 1.25rem; }
        .form-footer { padding: 1.5rem 1.25rem; }
        .nav-link-text { display: none; }
    }
    @keyframes spin { from{transform:rotate(0deg)} to{transform:rotate(360deg)} }
    </style>
</head>
<body>

{{-- Navbar --}}
<nav class="nav">
    <div class="nav-inner">
        <a href="{{ route('landing') }}" class="nav-logo">
            @if(!empty($logo))
                <img src="{{ $logo }}" alt="Logo" style="height:32px;width:auto;border-radius:6px;">
            @else
                <div class="nav-logo-icon"><i class="bi bi-mortarboard-fill"></i></div>
            @endif
            <span class="nav-logo-name">{{ $inst ?? config('app.name') }}</span>
        </a>
        <div class="nav-links">
            <a href="{{ route('inscripcion.consulta') }}" class="nav-link">
                <i class="bi bi-search"></i>
                <span class="nav-link-text">Consultar solicitud</span>
            </a>
            <a href="{{ route('login') }}" class="nav-link nav-link-primary">
                <i class="bi bi-box-arrow-in-right"></i>
                <span class="nav-link-text">Iniciar sesión</span>
            </a>
        </div>
    </div>
</nav>

{{-- Hero --}}
<section class="hero">
    <div class="hero-glow"></div>
    <div class="hero-inner">
        <div class="hero-badge">
            <div class="pulse"></div>
            Inscripciones abiertas
        </div>
        <h1>Solicitud de <span>Pre-matrícula</span></h1>
        <p class="hero-sub">
            Complete el formulario y recibirá un <strong style="color:#6ee7b7;">código de seguimiento</strong>
            para consultar el estado de su solicitud en cualquier momento.
        </p>
        <div class="hero-code-hint">
            <i class="bi bi-shield-check-fill" style="color:#34d399;"></i>
            Sus datos están protegidos · Confirmación por correo electrónico
        </div>
    </div>
</section>

{{-- Formulario --}}
<div class="form-wrap">
    <div class="form-card">
        <form action="{{ route('inscripcion.store') }}" method="POST" enctype="multipart/form-data"
              x-data="formPM()" @submit.prevent="submit">
            @csrf

            {{-- Errores globales --}}
            @if($errors->any())
            <div class="form-section">
                <div class="alert-error">
                    <ul>
                        @foreach($errors->all() as $error)
                            <li><i class="bi bi-exclamation-circle-fill"></i> {{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
            @endif

            {{-- SECCIÓN 1: Datos del Estudiante --}}
            <div class="form-section">
                <div class="section-title"><i class="bi bi-person-fill"></i> Datos del Estudiante</div>

                <div class="grid-2" style="margin-bottom:1rem;">
                    <div class="field">
                        <label for="nombres">Nombres <span class="req">*</span></label>
                        <input type="text" id="nombres" name="nombres"
                               value="{{ old('nombres') }}" placeholder="Ej: María Fernanda"
                               class="{{ $errors->has('nombres') ? 'error' : '' }}"
                               required maxlength="100">
                        @error('nombres')<span class="field-error">{{ $message }}</span>@enderror
                    </div>
                    <div class="field">
                        <label for="apellidos">Apellidos <span class="req">*</span></label>
                        <input type="text" id="apellidos" name="apellidos"
                               value="{{ old('apellidos') }}" placeholder="Ej: García López"
                               class="{{ $errors->has('apellidos') ? 'error' : '' }}"
                               required maxlength="100">
                        @error('apellidos')<span class="field-error">{{ $message }}</span>@enderror
                    </div>
                </div>

                <div class="grid-3" style="margin-bottom:1rem;">
                    <div class="field">
                        <label for="fecha_nacimiento">Fecha de Nacimiento <span class="req">*</span></label>
                        <input type="date" id="fecha_nacimiento" name="fecha_nacimiento"
                               value="{{ old('fecha_nacimiento') }}"
                               class="{{ $errors->has('fecha_nacimiento') ? 'error' : '' }}"
                               required>
                        @error('fecha_nacimiento')<span class="field-error">{{ $message }}</span>@enderror
                    </div>
                    <div class="field">
                        <label for="genero">Género <span class="req">*</span></label>
                        <select id="genero" name="genero"
                                class="{{ $errors->has('genero') ? 'error' : '' }}" required>
                            <option value="">-- Seleccione --</option>
                            <option value="Masculino" {{ old('genero') === 'Masculino' ? 'selected' : '' }}>Masculino</option>
                            <option value="Femenino"  {{ old('genero') === 'Femenino'  ? 'selected' : '' }}>Femenino</option>
                            <option value="Otro"      {{ old('genero') === 'Otro'      ? 'selected' : '' }}>Otro</option>
                        </select>
                        @error('genero')<span class="field-error">{{ $message }}</span>@enderror
                    </div>
                    <div class="field">
                        <label for="cedula_estudiante">Cédula del Estudiante <span class="opt">(opcional)</span></label>
                        <input type="text" id="cedula_estudiante" name="cedula_estudiante"
                               value="{{ old('cedula_estudiante') }}" placeholder="001-0000000-0" maxlength="20">
                    </div>
                </div>

                <div class="grid-2">
                    <div class="field">
                        <label for="lugar_nacimiento">Lugar de Nacimiento <span class="opt">(opcional)</span></label>
                        <input type="text" id="lugar_nacimiento" name="lugar_nacimiento"
                               value="{{ old('lugar_nacimiento') }}" placeholder="Ej: Santo Domingo, D.N." maxlength="150">
                    </div>
                    <div class="field">
                        <label for="grado_solicitado">Grado Solicitado <span class="req">*</span></label>
                        <select id="grado_solicitado" name="grado_solicitado"
                                class="{{ $errors->has('grado_solicitado') ? 'error' : '' }}" required>
                            <option value="">-- Seleccione un grado --</option>
                            @foreach($grados as $grado)
                                <option value="{{ $grado }}" {{ old('grado_solicitado') === $grado ? 'selected' : '' }}>{{ $grado }}</option>
                            @endforeach
                        </select>
                        @error('grado_solicitado')<span class="field-error">{{ $message }}</span>@enderror
                    </div>
                </div>
            </div>

            {{-- SECCIÓN 2: Datos del Representante --}}
            <div class="form-section">
                <div class="section-title"><i class="bi bi-people-fill"></i> Datos del Representante / Tutor</div>

                <div class="grid-2" style="margin-bottom:1rem;">
                    <div class="field">
                        <label for="nombre_representante">Nombre Completo <span class="req">*</span></label>
                        <input type="text" id="nombre_representante" name="nombre_representante"
                               value="{{ old('nombre_representante') }}" placeholder="Ej: Carlos Antonio García"
                               class="{{ $errors->has('nombre_representante') ? 'error' : '' }}"
                               required maxlength="150">
                        @error('nombre_representante')<span class="field-error">{{ $message }}</span>@enderror
                    </div>
                    <div class="field">
                        <label for="cedula_representante">Cédula de Identidad <span class="req">*</span></label>
                        <input type="text" id="cedula_representante" name="cedula_representante"
                               value="{{ old('cedula_representante') }}" placeholder="001-1234567-8"
                               class="{{ $errors->has('cedula_representante') ? 'error' : '' }}"
                               required maxlength="20">
                        @error('cedula_representante')<span class="field-error">{{ $message }}</span>@enderror
                    </div>
                </div>

                <div class="field" style="max-width:300px;">
                    <label for="relacion_representante">Relación con el Estudiante <span class="req">*</span></label>
                    <select id="relacion_representante" name="relacion_representante"
                            class="{{ $errors->has('relacion_representante') ? 'error' : '' }}" required>
                        <option value="">-- Seleccione --</option>
                        <option value="Padre"   {{ old('relacion_representante') === 'Padre'   ? 'selected' : '' }}>Padre</option>
                        <option value="Madre"   {{ old('relacion_representante') === 'Madre'   ? 'selected' : '' }}>Madre</option>
                        <option value="Tutor/a" {{ old('relacion_representante') === 'Tutor/a' ? 'selected' : '' }}>Tutor/a</option>
                        <option value="Otro"    {{ old('relacion_representante') === 'Otro'    ? 'selected' : '' }}>Otro</option>
                    </select>
                    @error('relacion_representante')<span class="field-error">{{ $message }}</span>@enderror
                </div>
            </div>

            {{-- SECCIÓN 3: Contacto --}}
            <div class="form-section">
                <div class="section-title"><i class="bi bi-telephone-fill"></i> Información de Contacto</div>
                <div class="grid-2" style="margin-bottom:1rem;">
                    <div class="field">
                        <label for="telefono">Teléfono <span class="req">*</span></label>
                        <input type="tel" id="telefono" name="telefono"
                               value="{{ old('telefono') }}" placeholder="809-555-0000"
                               class="{{ $errors->has('telefono') ? 'error' : '' }}"
                               required maxlength="30">
                        @error('telefono')<span class="field-error">{{ $message }}</span>@enderror
                    </div>
                    <div class="field">
                        <label for="email">Correo Electrónico <span class="req">*</span></label>
                        <input type="email" id="email" name="email"
                               value="{{ old('email') }}" placeholder="correo@ejemplo.com"
                               class="{{ $errors->has('email') ? 'error' : '' }}"
                               required maxlength="150">
                        @error('email')<span class="field-error">{{ $message }}</span>@enderror
                        <span class="field-hint">Aquí recibirá su código de seguimiento.</span>
                    </div>
                </div>
                <div class="field">
                    <label for="direccion">Dirección Residencial <span class="req">*</span></label>
                    <input type="text" id="direccion" name="direccion"
                           value="{{ old('direccion') }}" placeholder="Calle, sector, ciudad"
                           class="{{ $errors->has('direccion') ? 'error' : '' }}"
                           required maxlength="300">
                    @error('direccion')<span class="field-error">{{ $message }}</span>@enderror
                </div>
            </div>

            {{-- SECCIÓN 4: Documentos (opcional) --}}
            <div class="form-section">
                <div class="section-title"><i class="bi bi-paperclip"></i> Documentos <span style="font-size:.7rem;font-weight:400;text-transform:none;letter-spacing:0;color:var(--g400);">(opcional — todos son opcionales)</span></div>

                <div class="docs-note">
                    <i class="bi bi-info-circle-fill" style="flex-shrink:0;margin-top:.1rem;"></i>
                    <span>Puede adjuntar los documentos ahora o presentarlos posteriormente en la institución.
                    Formatos aceptados: JPG, PNG, PDF · Máximo 3 MB por archivo.</span>
                </div>

                <div class="grid-3">
                    {{-- Cédula del representante --}}
                    <div>
                        <div style="font-size:.78rem;font-weight:600;color:var(--g700);margin-bottom:.4rem;">
                            <i class="bi bi-person-vcard" style="color:var(--g400);margin-right:.2rem;"></i>
                            Cédula del Representante
                        </div>
                        <div class="file-drop" x-bind:class="files.cedula_rep ? 'has-file' : ''"
                             @dragover.prevent @drop.prevent="handleDrop($event, 'cedula_rep')">
                            <input type="file" name="cedula_rep_doc" accept=".jpg,.jpeg,.png,.pdf"
                                   @change="handleFile($event, 'cedula_rep')">
                            <div class="file-drop-icon"><i class="bi bi-cloud-upload-fill"></i></div>
                            <div class="file-drop-label" x-text="files.cedula_rep ? files.cedula_rep : 'Subir archivo'"></div>
                            <div class="file-drop-sub">Arrastra o haz clic</div>
                        </div>
                    </div>

                    {{-- Acta de nacimiento --}}
                    <div>
                        <div style="font-size:.78rem;font-weight:600;color:var(--g700);margin-bottom:.4rem;">
                            <i class="bi bi-file-earmark-text" style="color:var(--g400);margin-right:.2rem;"></i>
                            Acta de Nacimiento
                        </div>
                        <div class="file-drop" x-bind:class="files.acta ? 'has-file' : ''"
                             @dragover.prevent @drop.prevent="handleDrop($event, 'acta')">
                            <input type="file" name="acta_nacimiento_doc" accept=".jpg,.jpeg,.png,.pdf"
                                   @change="handleFile($event, 'acta')">
                            <div class="file-drop-icon"><i class="bi bi-cloud-upload-fill"></i></div>
                            <div class="file-drop-label" x-text="files.acta ? files.acta : 'Subir archivo'"></div>
                            <div class="file-drop-sub">Arrastra o haz clic</div>
                        </div>
                    </div>

                    {{-- Foto del estudiante --}}
                    <div>
                        <div style="font-size:.78rem;font-weight:600;color:var(--g700);margin-bottom:.4rem;">
                            <i class="bi bi-camera-fill" style="color:var(--g400);margin-right:.2rem;"></i>
                            Foto del Estudiante
                        </div>
                        <div class="file-drop" x-bind:class="files.foto ? 'has-file' : ''"
                             @dragover.prevent @drop.prevent="handleDrop($event, 'foto')">
                            <input type="file" name="foto_doc" accept=".jpg,.jpeg,.png"
                                   @change="handleFile($event, 'foto')">
                            <div class="file-drop-icon"><i class="bi bi-cloud-upload-fill"></i></div>
                            <div class="file-drop-label" x-text="files.foto ? files.foto : 'Subir foto'"></div>
                            <div class="file-drop-sub">JPG / PNG únicamente</div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Footer / Submit --}}
            <div class="form-footer">
                <button type="submit" class="btn-submit" :disabled="enviando">
                    <template x-if="!enviando">
                        <span style="display:flex;align-items:center;gap:.5rem;">
                            <i class="bi bi-send-fill"></i> Enviar Solicitud de Pre-matrícula
                        </span>
                    </template>
                    <template x-if="enviando">
                        <span style="display:flex;align-items:center;gap:.5rem;">
                            <i class="bi bi-arrow-repeat" style="animation:spin .7s linear infinite;"></i> Enviando...
                        </span>
                    </template>
                </button>
                <p class="form-note">
                    <i class="bi bi-shield-lock-fill" style="color:#94a3b8;"></i>
                    Sus datos están protegidos. Recibirá un <strong>código de seguimiento</strong> por correo electrónico.
                </p>
            </div>

        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
<script>
function formPM() {
    return {
        enviando: false,
        files: { cedula_rep: null, acta: null, foto: null },
        handleFile(e, key) {
            const f = e.target.files[0];
            this.files[key] = f ? f.name : null;
        },
        handleDrop(e, key) {
            const f = e.dataTransfer.files[0];
            if (f) {
                this.files[key] = f.name;
                // Assign to the input
                const inp = e.currentTarget.querySelector('input[type="file"]');
                const dt = new DataTransfer();
                dt.items.add(f);
                inp.files = dt.files;
            }
        },
        submit() {
            this.enviando = true;
            this.$el.submit();
        }
    }
}
</script>
</body>
</html>
