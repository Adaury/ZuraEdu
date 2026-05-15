@extends('admin.onboarding._layout')
@php $pasoActual = 1; @endphp

@section('wizard-content')

<div class="wizard-card-header">
    <h2>🏫 Cuéntanos sobre tu institución</h2>
    <p>Esta información aparecerá en boletines, comunicados y reportes. Puedes modificarla después en Configuración.</p>
</div>

<form method="POST" action="{{ route('admin.onboarding.store', 1) }}" enctype="multipart/form-data">
@csrf

<div class="wizard-card-body">

    @if($errors->any())
    <div class="alert-error">
        <ul>@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
    @endif

    {{-- Logo --}}
    <div class="form-group">
        <label class="form-label">Logo de la institución</label>
        <div class="logo-upload-area" id="logoArea">
            <input type="file" name="logo" accept="image/*" id="logoInput" onchange="previewLogo(this)">
            <img id="logoPreview" class="logo-preview" src="" alt="Logo">
            <div id="logoPlaceholder">
                <i class="bi bi-image" style="font-size:1.5rem;color:#94a3b8;display:block;margin-bottom:.4rem;"></i>
                <span style="font-size:.83rem;color:#64748b;">Haz clic para subir tu logo</span>
                <div style="font-size:.75rem;color:#94a3b8;margin-top:.25rem;">PNG, JPG — máx. 2 MB</div>
            </div>
        </div>
    </div>

    {{-- Nombre --}}
    <div class="form-group">
        <label class="form-label">Nombre de la institución <span style="color:#ef4444;">*</span></label>
        <input type="text" name="nombre_institucion" class="form-control"
               value="{{ old('nombre_institucion', $tenant->nombre_institucion) }}"
               placeholder="Ej: Centro Educativo San Pablo" required maxlength="120">
    </div>

    {{-- Tipo --}}
    <div class="form-group">
        <label class="form-label">Tipo de institución</label>
        <div class="tipo-grid">
            @foreach(['publico' => ['Pública', 'bi-building'], 'privado' => ['Privada', 'bi-buildings-fill'], 'instituto' => ['Instituto', 'bi-book'], 'tecnico' => ['Técnico', 'bi-gear-fill']] as $val => [$lbl, $ico])
            <label class="tipo-option">
                <input type="radio" name="tipo" value="{{ $val }}" {{ old('tipo', $tenant->tipo) === $val ? 'checked' : '' }}>
                <div class="tipo-card">
                    <i class="bi {{ $ico }}"></i>
                    <span>{{ $lbl }}</span>
                </div>
            </label>
            @endforeach
        </div>
    </div>

    {{-- Contacto --}}
    <div class="form-row">
        <div class="form-group">
            <label class="form-label">Teléfono</label>
            <input type="tel" name="telefono_contacto" class="form-control"
                   value="{{ old('telefono_contacto', $tenant->telefono_contacto) }}"
                   placeholder="809-555-0000" maxlength="30">
        </div>
        <div class="form-group">
            <label class="form-label">Email de contacto</label>
            <input type="email" name="email_contacto" class="form-control"
                   value="{{ old('email_contacto', $tenant->email_contacto) }}"
                   placeholder="director@escuela.edu" maxlength="150">
        </div>
    </div>

    <div class="form-row">
        <div class="form-group">
            <label class="form-label">Ciudad</label>
            <input type="text" name="ciudad" class="form-control"
                   value="{{ old('ciudad', $tenant->ciudad) }}"
                   placeholder="Santo Domingo" maxlength="80">
        </div>
        <div class="form-group">
            <label class="form-label">Color principal</label>
            <div class="color-picker-wrap">
                <input type="color" name="color_primario" id="colorPick"
                       value="{{ old('color_primario', $tenant->color_primario ?? '#1d4ed8') }}"
                       oninput="document.getElementById('colorHex').textContent = this.value">
                <span class="color-hex" id="colorHex">{{ old('color_primario', $tenant->color_primario ?? '#1d4ed8') }}</span>
            </div>
        </div>
    </div>

    <div class="form-group">
        <label class="form-label">Dirección</label>
        <input type="text" name="direccion" class="form-control"
               value="{{ old('direccion', $tenant->direccion) }}"
               placeholder="Calle Principal #123, Sector..." maxlength="250">
    </div>

</div>

<div class="wizard-card-footer">
    <span style="font-size:.8rem;color:#94a3b8;">Paso 1 de 4</span>
    <button type="submit" class="btn btn-primary">
        Continuar <i class="bi bi-arrow-right"></i>
    </button>
</div>

</form>

@endsection

@push('scripts')
<script>
function previewLogo(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => {
            const img = document.getElementById('logoPreview');
            img.src = e.target.result;
            img.style.display = 'block';
            document.getElementById('logoPlaceholder').style.display = 'none';
        };
        reader.readAsDataURL(input.files[0]);
    }
}
@if($tenant->logo)
document.getElementById('logoPreview').src = '/storage/{{ $tenant->logo }}';
document.getElementById('logoPreview').style.display = 'block';
document.getElementById('logoPlaceholder').style.display = 'none';
@endif
</script>
@endpush
