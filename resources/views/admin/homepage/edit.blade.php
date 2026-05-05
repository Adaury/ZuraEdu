@extends('layouts.admin')

@section('title', 'Editor de Página Principal')

@section('content')
<div class="container-fluid px-4">
    <div class="mb-4">
        <h1 class="h3 mb-0">Editor de Página Principal</h1>
        <nav aria-label="breadcrumb"><ol class="breadcrumb mb-0 small">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Inicio</a></li>
            <li class="breadcrumb-item active">Homepage</li>
        </ol></nav>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    <form method="POST" action="{{ route('admin.homepage.update') }}" enctype="multipart/form-data">
        @csrf

        <div class="row g-4">
            {{-- Left Column --}}
            <div class="col-lg-8">

                {{-- Hero --}}
                <div class="card shadow-sm mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span class="fw-semibold"><i class="bi bi-display me-1"></i>Sección Hero (Portada)</span>
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input" type="checkbox" name="hp_hero_visible" id="hero_vis" value="1"
                                @checked(($config['hp_hero_visible'] ?? '1') == '1')>
                            <label class="form-check-label small" for="hero_vis">Visible</label>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label small">Título Principal</label>
                            <input type="text" name="hp_hero_titulo" class="form-control"
                                value="{{ old('hp_hero_titulo', $config['hp_hero_titulo'] ?? '') }}" maxlength="200">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small">Subtítulo / Descripción</label>
                            <textarea name="hp_hero_subtitulo" class="form-control" rows="3" maxlength="500">{{ old('hp_hero_subtitulo', $config['hp_hero_subtitulo'] ?? '') }}</textarea>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small">Texto Botón 1</label>
                                <input type="text" name="hp_hero_btn_texto" class="form-control"
                                    value="{{ old('hp_hero_btn_texto', $config['hp_hero_btn_texto'] ?? '') }}" maxlength="80">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small">Texto Botón 2</label>
                                <input type="text" name="hp_hero_btn2_texto" class="form-control"
                                    value="{{ old('hp_hero_btn2_texto', $config['hp_hero_btn2_texto'] ?? '') }}" maxlength="80">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Sobre la Institución --}}
                <div class="card shadow-sm mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span class="fw-semibold"><i class="bi bi-building me-1"></i>Sobre la Institución</span>
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input" type="checkbox" name="hp_about_visible" id="about_vis" value="1"
                                @checked(($config['hp_about_visible'] ?? '1') == '1')>
                            <label class="form-check-label small" for="about_vis">Visible</label>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label small">Título</label>
                            <input type="text" name="hp_about_titulo" class="form-control"
                                value="{{ old('hp_about_titulo', $config['hp_about_titulo'] ?? '') }}" maxlength="200">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small">Texto / Descripción</label>
                            <textarea name="hp_about_texto" class="form-control" rows="5" maxlength="1000">{{ old('hp_about_texto', $config['hp_about_texto'] ?? '') }}</textarea>
                        </div>
                    </div>
                </div>

                {{-- Estadísticas --}}
                <div class="card shadow-sm mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span class="fw-semibold"><i class="bi bi-bar-chart me-1"></i>Estadísticas</span>
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input" type="checkbox" name="hp_stats_visible" id="stats_vis" value="1"
                                @checked(($config['hp_stats_visible'] ?? '1') == '1')>
                            <label class="form-check-label small" for="stats_vis">Visible</label>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            @for($s = 1; $s <= 4; $s++)
                            <div class="col-md-6">
                                <div class="border rounded p-2">
                                    <label class="form-label small fw-semibold">Estadística #{{ $s }}</label>
                                    <div class="row g-2">
                                        <div class="col-5">
                                            <input type="text" name="hp_stat{{ $s }}_numero" class="form-control form-control-sm"
                                                placeholder="Número (ej: 500+)"
                                                value="{{ old("hp_stat{$s}_numero", $config["hp_stat{$s}_numero"] ?? '') }}">
                                        </div>
                                        <div class="col-7">
                                            <input type="text" name="hp_stat{{ $s }}_label" class="form-control form-control-sm"
                                                placeholder="Etiqueta (ej: Estudiantes)"
                                                value="{{ old("hp_stat{$s}_label", $config["hp_stat{$s}_label"] ?? '') }}">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endfor
                        </div>
                    </div>
                </div>

                {{-- Características --}}
                <div class="card shadow-sm mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span class="fw-semibold"><i class="bi bi-stars me-1"></i>Características</span>
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input" type="checkbox" name="hp_features_visible" id="feat_vis" value="1"
                                @checked(($config['hp_features_visible'] ?? '1') == '1')>
                            <label class="form-check-label small" for="feat_vis">Visible</label>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="mb-0">
                            <label class="form-label small">Título de la Sección</label>
                            <input type="text" name="hp_features_titulo" class="form-control"
                                value="{{ old('hp_features_titulo', $config['hp_features_titulo'] ?? '') }}" maxlength="200">
                        </div>
                    </div>
                </div>

                {{-- Contacto --}}
                <div class="card shadow-sm mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span class="fw-semibold"><i class="bi bi-geo-alt me-1"></i>Contacto</span>
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input" type="checkbox" name="hp_contacto_visible" id="cont_vis" value="1"
                                @checked(($config['hp_contacto_visible'] ?? '1') == '1')>
                            <label class="form-check-label small" for="cont_vis">Visible</label>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="form-label small">Dirección</label>
                                <input type="text" name="hp_contacto_direccion" class="form-control"
                                    value="{{ old('hp_contacto_direccion', $config['hp_contacto_direccion'] ?? '') }}" maxlength="200">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small">Teléfono</label>
                                <input type="text" name="hp_contacto_telefono" class="form-control"
                                    value="{{ old('hp_contacto_telefono', $config['hp_contacto_telefono'] ?? '') }}" maxlength="50">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small">Correo Electrónico</label>
                                <input type="email" name="hp_contacto_email" class="form-control"
                                    value="{{ old('hp_contacto_email', $config['hp_contacto_email'] ?? '') }}" maxlength="100">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Redes Sociales --}}
                <div class="card shadow-sm mb-4">
                    <div class="card-header fw-semibold"><i class="bi bi-share me-1"></i>Redes Sociales</div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label small"><i class="bi bi-facebook me-1 text-primary"></i>Facebook</label>
                                <input type="url" name="hp_social_facebook" class="form-control"
                                    placeholder="https://facebook.com/..."
                                    value="{{ old('hp_social_facebook', $config['hp_social_facebook'] ?? '') }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small"><i class="bi bi-instagram me-1 text-danger"></i>Instagram</label>
                                <input type="url" name="hp_social_instagram" class="form-control"
                                    placeholder="https://instagram.com/..."
                                    value="{{ old('hp_social_instagram', $config['hp_social_instagram'] ?? '') }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small"><i class="bi bi-twitter-x me-1"></i>Twitter / X</label>
                                <input type="url" name="hp_social_twitter" class="form-control"
                                    placeholder="https://twitter.com/..."
                                    value="{{ old('hp_social_twitter', $config['hp_social_twitter'] ?? '') }}">
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
                            $ss = \Illuminate\Support\Facades\DB::table('system_settings')
                                ->whereIn('key', ['system_name','system_abbr','system_sub'])
                                ->pluck('value','key');
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
                                placeholder="PSAC" maxlength="200">
                            <div class="form-text">Nombre que aparece en la barra lateral del admin.</div>
                        </div>
                        <div class="row g-2">
                            <div class="col-5">
                                <label class="form-label small fw-semibold">Abreviatura</label>
                                <input type="text" name="system_abbr" class="form-control"
                                    value="{{ old('system_abbr', $ss['system_abbr'] ?? '') }}"
                                    placeholder="PSAC" maxlength="10">
                            </div>
                            <div class="col-7">
                                <label class="form-label small fw-semibold">Subtítulo sidebar</label>
                                <input type="text" name="system_sub" class="form-control"
                                    value="{{ old('system_sub', $ss['system_sub'] ?? '') }}"
                                    placeholder="Gestión Escolar" maxlength="80">
                            </div>
                        </div>
                        <div class="form-text mt-1">Logo y badge visibles en la parte superior del sidebar.</div>
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
                        <p class="small text-muted mb-3">Ver cómo luce la página principal en este momento.</p>
                        <a href="{{ route('landing') }}" target="_blank" class="btn btn-outline-info btn-sm w-100">
                            <i class="bi bi-box-arrow-up-right me-1"></i> Ver Página Principal
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
