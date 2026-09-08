@extends('layouts.admin')

@section('page-title', 'Branding e Identidad del Sitio')

@section('content')
<div class="container-fluid px-4">
    <div class="mb-4 d-flex justify-content-between align-items-start flex-wrap gap-2">
        <div>
            <h1 class="h3 mb-0">Branding e Identidad del Sitio</h1>
            <nav aria-label="breadcrumb"><ol class="breadcrumb mb-0 small">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Inicio</a></li>
                <li class="breadcrumb-item active">Branding</li>
            </ol></nav>
        </div>
        <a href="{{ route('admin.secciones.index') }}" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-grid-1x2 me-1"></i>Ir al constructor de Secciones del Sitio
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    <form method="POST" action="{{ route('admin.homepage.update') }}" enctype="multipart/form-data">
        @csrf

        <div class="row g-4">
            {{-- Left Column --}}
            <div class="col-lg-8">

                {{-- Anuncios --}}
                <div class="card shadow-sm mb-4">
                    <div class="card-header">
                        <span class="fw-semibold"><i class="bi bi-megaphone me-1"></i>Anuncios (Google Ads u otro)</span>
                    </div>
                    <div class="card-body">
                        <p class="text-muted small mb-3">
                            Pega aquí <strong>solo</strong> el código que te da Google AdSense (u otra red publicitaria) para cada lado del sitio —
                            no pegues plantillas de página completas ni bloques de estilo (<code>&lt;style&gt;</code>), pueden romper visualmente el resto del sitio.
                            Se muestra tal cual, sin modificarlo — <strong>ustedes son responsables del código que peguen aquí</strong>,
                            solo se ve en pantallas anchas (no en celular) y solo si dejan algo escrito; si el campo queda vacío, no se muestra nada.
                        </p>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small">Código para el lado izquierdo</label>
                                <textarea name="hp_ads_izquierda" rows="4" class="form-control font-monospace" style="font-size:.8rem;"
                                    placeholder="&lt;script&gt;...&lt;/script&gt;">{{ old('hp_ads_izquierda', $config['hp_ads_izquierda'] ?? '') }}</textarea>
                                @error('hp_ads_izquierda')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small">Código para el lado derecho</label>
                                <textarea name="hp_ads_derecha" rows="4" class="form-control font-monospace" style="font-size:.8rem;"
                                    placeholder="&lt;script&gt;...&lt;/script&gt;">{{ old('hp_ads_derecha', $config['hp_ads_derecha'] ?? '') }}</textarea>
                                @error('hp_ads_derecha')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            {{-- Right Column --}}
            <div class="col-lg-4">

                {{-- Institución --}}
                <div class="card shadow-sm mb-4">
                    <div class="card-header fw-semibold"><i class="bi bi-mortarboard me-1"></i>Institución</div>
                    <div class="card-body">
                        @php
                            $ss = collect(\App\Helpers\Setting::all())->only(['system_name','system_abbr','system_sub']);
                        @endphp
                        <div class="mb-3">
                            <label class="form-label small">Nombre de la Institución <span class="text-muted">(landing)</span></label>
                            <input type="text" name="nombre_institucion" class="form-control"
                                value="{{ old('nombre_institucion', $config['nombre_institucion'] ?? '') }}" maxlength="200">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Nombre en el Panel Admin</label>
                            <input type="text" name="system_name" class="form-control"
                                value="{{ old('system_name', $ss['system_name'] ?? '') }}"
                                placeholder="Ej: Mi Institución" maxlength="200">
                            <div class="form-text">Nombre que aparece en la barra lateral del admin.</div>
                        </div>
                        <div class="row g-2">
                            <div class="col-5">
                                <label class="form-label small fw-semibold">Abreviatura</label>
                                <input type="text" name="system_abbr" class="form-control"
                                    value="{{ old('system_abbr', $ss['system_abbr'] ?? '') }}"
                                    placeholder="Ej: ZE" maxlength="10">
                            </div>
                            <div class="col-7">
                                <label class="form-label small fw-semibold">Subtítulo sidebar</label>
                                <input type="text" name="system_sub" class="form-control"
                                    value="{{ old('system_sub', $ss['system_sub'] ?? '') }}"
                                    placeholder="Gestión Escolar" maxlength="80">
                            </div>
                        </div>
                        <div class="form-text mt-1">Logo y badge visibles en la parte superior del sidebar.</div>
                        <div class="form-text">
                            Estos 3 campos también se editan desde
                            <a href="{{ route('admin.sistema.index') }}">Configuración del Sistema</a> —
                            es el mismo dato en ambos lugares.
                        </div>
                    </div>
                </div>

                {{-- Branding --}}
                <div class="card shadow-sm mb-4">
                    <div class="card-header fw-semibold"><i class="bi bi-palette me-1"></i>Logo y Colores</div>
                    <div class="card-body">
                        {{-- Logo --}}
                        @if(!empty($config['hp_logo_path']))
                        <div class="mb-3 text-center">
                            <img src="{{ Storage::url($config['hp_logo_path']) }}" alt="Logo"
                                class="img-thumbnail" style="max-height:80px;">
                            <div class="small text-muted mt-1">Logo actual</div>
                        </div>
                        @endif
                        <div class="mb-3">
                            <label class="form-label small">Subir Nuevo Logo</label>
                            <input type="file" name="logo" class="form-control @error('logo') is-invalid @enderror"
                                accept="image/*">
                            <div class="form-text">PNG/JPG — máx. 2 MB</div>
                            @error('logo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <hr class="my-2">
                        <div class="row g-3">
                            <div class="col-6">
                                <label class="form-label small">Color Primario</label>
                                <div class="input-group input-group-sm">
                                    <input type="color" name="hp_color_primario" class="form-control form-control-color p-1"
                                        value="{{ old('hp_color_primario', $config['hp_color_primario'] ?? '#0d6efd') }}">
                                    <input type="text" class="form-control font-monospace"
                                        value="{{ $config['hp_color_primario'] ?? '#0d6efd' }}"
                                        oninput="document.querySelector('[name=hp_color_primario]').value=this.value">
                                </div>
                            </div>
                            <div class="col-6">
                                <label class="form-label small">Color Secundario</label>
                                <div class="input-group input-group-sm">
                                    <input type="color" name="hp_color_secundario" class="form-control form-control-color p-1"
                                        value="{{ old('hp_color_secundario', $config['hp_color_secundario'] ?? '#6c757d') }}">
                                    <input type="text" class="form-control font-monospace"
                                        value="{{ $config['hp_color_secundario'] ?? '#6c757d' }}"
                                        oninput="document.querySelector('[name=hp_color_secundario]').value=this.value">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Preview link --}}
                <div class="card shadow-sm mb-4 border-info">
                    <div class="card-body text-center">
                        <i class="bi bi-eye display-6 text-info d-block mb-2"></i>
                        <p class="small text-muted mb-3">Ver cómo luce el sitio público de tu institución en este momento.</p>
                        <a href="{{ route('sitio.show') }}" target="_blank" class="btn btn-outline-info btn-sm w-100">
                            <i class="bi bi-box-arrow-up-right me-1"></i> Ver Sitio Público
                        </a>
                    </div>
                </div>

                <div class="d-grid">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-1"></i> Guardar Cambios
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
// Sync color text inputs with color pickers
document.querySelectorAll('input[type=color]').forEach(picker => {
    picker.addEventListener('input', function () {
        const textInput = this.nextElementSibling;
        if (textInput) textInput.value = this.value;
    });
});
</script>
@endpush
@endsection
