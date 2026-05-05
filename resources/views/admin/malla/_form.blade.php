@if($errors->any())
<div class="alert alert-danger py-2 mb-3">
    <ul class="mb-0 ps-3">@foreach($errors->all() as $e)<li style="font-size:.85rem;">{{ $e }}</li>@endforeach</ul>
</div>
@endif

<div class="row g-3 mb-3">
    <div class="col-sm-6">
        <label class="form-label fw-semibold" style="font-size:.85rem;">Grado *</label>
        <select name="grado_id" class="form-select form-select-sm" required>
            <option value="">— Seleccionar grado —</option>
            @foreach($grados as $g)
            <option value="{{ $g->id }}"
                    {{ old('grado_id', $malla->grado_id ?? '') == $g->id ? 'selected' : '' }}>
                {{ $g->nombre }}
            </option>
            @endforeach
        </select>
    </div>
    <div class="col-sm-6">
        <label class="form-label fw-semibold" style="font-size:.85rem;">Asignatura *</label>
        <select name="asignatura_id" class="form-select form-select-sm" required>
            <option value="">— Seleccionar asignatura —</option>
            @foreach($asignaturas as $a)
            <option value="{{ $a->id }}"
                    {{ old('asignatura_id', $malla->asignatura_id ?? '') == $a->id ? 'selected' : '' }}>
                {{ $a->nombre }}
            </option>
            @endforeach
        </select>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-sm-6">
        <label class="form-label fw-semibold" style="font-size:.85rem;">Área *</label>
        <select name="area" class="form-select form-select-sm" id="sel-area" required>
            <option value="academica" {{ old('area', $malla->area ?? 'academica') === 'academica' ? 'selected' : '' }}>Académica</option>
            <option value="tecnica"   {{ old('area', $malla->area ?? '') === 'tecnica' ? 'selected' : '' }}>Técnica</option>
        </select>
    </div>
    <div class="col-sm-6" id="campo-especialidad">
        <label class="form-label fw-semibold" style="font-size:.85rem;">Especialidad Técnica</label>
        <select name="especialidad_id" class="form-select form-select-sm">
            <option value="">— Área general (sin especialidad) —</option>
            @foreach($especialidades as $esp)
            <option value="{{ $esp->id }}"
                    {{ old('especialidad_id', $malla->especialidad_id ?? '') == $esp->id ? 'selected' : '' }}>
                {{ $esp->nombre }}
            </option>
            @endforeach
        </select>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-sm-4">
        <label class="form-label fw-semibold" style="font-size:.85rem;">Horas Semanales *</label>
        <div class="input-group input-group-sm">
            <input type="number" name="horas_semanales" class="form-control form-control-sm"
                   value="{{ old('horas_semanales', $malla->horas_semanales ?? 4) }}"
                   min="0" max="40" required>
            <span class="input-group-text">h/sem</span>
        </div>
    </div>
    <div class="col-sm-4">
        <label class="form-label fw-semibold" style="font-size:.85rem;">Horas Anuales</label>
        <div class="input-group input-group-sm">
            <input type="number" name="horas_anuales" class="form-control form-control-sm"
                   value="{{ old('horas_anuales', $malla->horas_anuales ?? '') }}"
                   min="0">
            <span class="input-group-text">h/año</span>
        </div>
    </div>
    <div class="col-sm-4">
        <label class="form-label fw-semibold" style="font-size:.85rem;">Orden Display</label>
        <input type="number" name="orden_display" class="form-control form-control-sm"
               value="{{ old('orden_display', $malla->orden_display ?? 0) }}" min="0">
    </div>
</div>

<div class="mb-3">
    <div class="form-check">
        <input class="form-check-input" type="checkbox" name="es_obligatoria" value="1" id="obligatoria"
               {{ old('es_obligatoria', $malla->es_obligatoria ?? true) ? 'checked' : '' }}>
        <label class="form-check-label" for="obligatoria" style="font-size:.85rem;">
            Es asignatura obligatoria en este grado
        </label>
    </div>
</div>

<div class="mb-3">
    <label class="form-label fw-semibold" style="font-size:.85rem;">Notas / Referencia MINERD</label>
    <textarea name="notas_curriculo" class="form-control form-control-sm" rows="2"
              placeholder="Referencias al currículo oficial, competencias, notas adicionales...">{{ old('notas_curriculo', $malla->notas_curriculo ?? '') }}</textarea>
</div>

@push('scripts')
<script>
(function() {
    const selArea = document.getElementById('sel-area');
    const campoEsp = document.getElementById('campo-especialidad');
    function toggleEsp() {
        campoEsp.style.display = selArea.value === 'tecnica' ? '' : 'none';
    }
    toggleEsp();
    selArea?.addEventListener('change', toggleEsp);
})();
</script>
@endpush
