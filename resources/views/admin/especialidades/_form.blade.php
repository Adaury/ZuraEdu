@if($errors->any())
<div class="alert alert-danger py-2 mb-3">
    <ul class="mb-0 ps-3">@foreach($errors->all() as $e)<li style="font-size:.85rem;">{{ $e }}</li>@endforeach</ul>
</div>
@endif

{{-- Preview de color e icono --}}
<div class="mb-3 p-3 rounded-2 d-flex align-items-center gap-3"
     style="background:#f8fafc;border:1px solid #e5e7eb;" id="esp-preview">
    <div id="preview-icon-box" style="width:50px;height:50px;border-radius:12px;
         background:{{ old('color', $especialidad->color ?? '#1e3a6e') }};
         display:flex;align-items:center;justify-content:center;font-size:1.4rem;color:#fff;flex-shrink:0;">
        <i class="bi {{ old('icono', $especialidad->icono ?? 'bi-mortarboard') }}" id="preview-icon"></i>
    </div>
    <div>
        <div class="fw-bold" id="preview-nombre" style="color:#1e293b;">
            {{ old('nombre', $especialidad->nombre ?? 'Nueva Especialidad') }}
        </div>
        <div class="text-muted" style="font-size:.78rem;" id="preview-codigo">
            {{ old('codigo', $especialidad->codigo ?? 'COD') }}
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-sm-8">
        <label class="form-label fw-semibold" style="font-size:.85rem;">Nombre *</label>
        <input type="text" name="nombre" class="form-control form-control-sm" id="inp-nombre"
               value="{{ old('nombre', $especialidad->nombre ?? '') }}" required maxlength="100">
    </div>
    <div class="col-sm-4">
        <label class="form-label fw-semibold" style="font-size:.85rem;">Código *</label>
        <input type="text" name="codigo" class="form-control form-control-sm" id="inp-codigo"
               value="{{ old('codigo', $especialidad->codigo ?? '') }}" required maxlength="20"
               style="text-transform:uppercase;" placeholder="Ej: INF">
    </div>
</div>

<div class="mb-3">
    <label class="form-label fw-semibold" style="font-size:.85rem;">Descripción</label>
    <textarea name="descripcion" class="form-control form-control-sm" rows="2"
              placeholder="Descripción breve de la especialidad...">{{ old('descripcion', $especialidad->descripcion ?? '') }}</textarea>
</div>

<div class="row g-3 mb-3">
    <div class="col-sm-4">
        <label class="form-label fw-semibold" style="font-size:.85rem;">Color</label>
        <input type="color" name="color" id="inp-color"
               class="form-control form-control-sm form-control-color"
               value="{{ old('color', $especialidad->color ?? '#1e3a6e') }}"
               style="height:38px;padding:.25rem;">
    </div>
    <div class="col-sm-8">
        <label class="form-label fw-semibold" style="font-size:.85rem;">Ícono (Bootstrap Icons)</label>
        <div class="input-group input-group-sm">
            <span class="input-group-text"><i class="bi bi-grid" id="preview-icono-small"></i></span>
            <input type="text" name="icono" id="inp-icono"
                   class="form-control form-control-sm"
                   value="{{ old('icono', $especialidad->icono ?? 'bi-mortarboard') }}"
                   placeholder="Ej: bi-laptop">
        </div>
        <div class="mt-1 d-flex gap-1 flex-wrap">
            @foreach(['bi-laptop','bi-airplane','bi-graph-up','bi-heart-pulse','bi-truck','bi-book','bi-tools','bi-mortarboard','bi-building','bi-people'] as $ic)
            <button type="button" class="btn btn-sm btn-outline-secondary icon-btn"
                    data-icon="{{ $ic }}" style="font-size:.75rem;padding:.2rem .45rem;" title="{{ $ic }}">
                <i class="bi {{ $ic }}"></i>
            </button>
            @endforeach
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-sm-6">
        <label class="form-label fw-semibold" style="font-size:.85rem;">Coordinador</label>
        <select name="coordinador_id" class="form-select form-select-sm">
            <option value="">— Sin coordinador —</option>
            @foreach($docentes as $doc)
            <option value="{{ $doc->id }}"
                    {{ old('coordinador_id', $especialidad->coordinador_id ?? '') == $doc->id ? 'selected' : '' }}>
                {{ $doc->nombre_completo }}
            </option>
            @endforeach
        </select>
    </div>
    <div class="col-sm-3">
        <label class="form-label fw-semibold" style="font-size:.85rem;">Orden</label>
        <input type="number" name="orden" class="form-control form-control-sm"
               value="{{ old('orden', $especialidad->orden ?? 0) }}" min="0" max="99">
    </div>
    <div class="col-sm-3">
        <label class="form-label fw-semibold" style="font-size:.85rem;">Estado</label>
        <div class="form-check form-switch mt-2">
            <input class="form-check-input" type="checkbox" name="activo" value="1" id="activo"
                   {{ old('activo', $especialidad->activo ?? true) ? 'checked' : '' }}>
            <label class="form-check-label" for="activo" style="font-size:.82rem;">Activa</label>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function() {
    const inpNombre = document.getElementById('inp-nombre');
    const inpCodigo = document.getElementById('inp-codigo');
    const inpColor  = document.getElementById('inp-color');
    const inpIcono  = document.getElementById('inp-icono');
    const prevNombre = document.getElementById('preview-nombre');
    const prevCodigo = document.getElementById('preview-codigo');
    const prevIconBox = document.getElementById('preview-icon-box');
    const prevIcon   = document.getElementById('preview-icon');
    const prevIconoSmall = document.getElementById('preview-icono-small');

    inpNombre?.addEventListener('input', () => { if(prevNombre) prevNombre.textContent = inpNombre.value || 'Nueva Especialidad'; });
    inpCodigo?.addEventListener('input', () => { if(prevCodigo) prevCodigo.textContent = inpCodigo.value.toUpperCase() || 'COD'; });
    inpColor?.addEventListener('input', () => { if(prevIconBox) prevIconBox.style.background = inpColor.value; });

    function setIcono(ic) {
        if(!ic.startsWith('bi-')) ic = 'bi-' + ic;
        if(inpIcono) inpIcono.value = ic;
        if(prevIcon) { prevIcon.className = 'bi ' + ic; }
        if(prevIconoSmall) { prevIconoSmall.className = 'bi ' + ic; }
    }

    inpIcono?.addEventListener('input', () => setIcono(inpIcono.value));

    document.querySelectorAll('.icon-btn').forEach(btn => {
        btn.addEventListener('click', () => setIcono(btn.dataset.icon));
    });
})();
</script>
@endpush
