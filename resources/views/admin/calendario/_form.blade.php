@if($errors->any())
<div class="alert alert-danger py-2 mb-3">
    <ul class="mb-0 ps-3">@foreach($errors->all() as $e)<li style="font-size:.85rem;">{{ $e }}</li>@endforeach</ul>
</div>
@endif

<div class="mb-3">
    <label class="form-label fw-semibold" style="font-size:.85rem;">Título *</label>
    <input type="text" name="titulo" class="form-control form-control-sm"
           value="{{ old('titulo', $evento->titulo ?? '') }}" required>
</div>

<div class="row g-3 mb-3">
    <div class="col-sm-6">
        <label class="form-label fw-semibold" style="font-size:.85rem;">Tipo *</label>
        <select name="tipo" class="form-select form-select-sm" required>
            @foreach($tipos as $value => $label)
            <option value="{{ $value }}" {{ old('tipo', $evento->tipo ?? '') === $value ? 'selected' : '' }}>
                {{ $label }}
            </option>
            @endforeach
        </select>
    </div>
    <div class="col-sm-6">
        <label class="form-label fw-semibold" style="font-size:.85rem;">Aplica a *</label>
        <select name="aplica_a" class="form-select form-select-sm" required>
            <option value="todos" {{ old('aplica_a', $evento->aplica_a ?? 'todos') === 'todos' ? 'selected' : '' }}>Todos</option>
            <option value="docentes" {{ old('aplica_a', $evento->aplica_a ?? '') === 'docentes' ? 'selected' : '' }}>Docentes</option>
            <option value="estudiantes" {{ old('aplica_a', $evento->aplica_a ?? '') === 'estudiantes' ? 'selected' : '' }}>Estudiantes</option>
            <option value="coordinadores" {{ old('aplica_a', $evento->aplica_a ?? '') === 'coordinadores' ? 'selected' : '' }}>Coordinadores</option>
            <option value="administrativos" {{ old('aplica_a', $evento->aplica_a ?? '') === 'administrativos' ? 'selected' : '' }}>Administrativos</option>
        </select>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-sm-4">
        <label class="form-label fw-semibold" style="font-size:.85rem;">Fecha Inicio *</label>
        <input type="date" name="fecha_inicio" class="form-control form-control-sm"
               value="{{ old('fecha_inicio', isset($evento) ? $evento->fecha_inicio?->format('Y-m-d') : '') }}" required>
    </div>
    <div class="col-sm-4">
        <label class="form-label fw-semibold" style="font-size:.85rem;">Fecha Fin</label>
        <input type="date" name="fecha_fin" class="form-control form-control-sm"
               value="{{ old('fecha_fin', isset($evento) ? $evento->fecha_fin?->format('Y-m-d') : '') }}">
    </div>
    <div class="col-sm-4">
        <label class="form-label fw-semibold" style="font-size:.85rem;">Hora</label>
        <input type="time" name="hora_inicio" class="form-control form-control-sm"
               value="{{ old('hora_inicio', $evento->hora_inicio ?? '') }}">
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-sm-4">
        <label class="form-label fw-semibold" style="font-size:.85rem;">Color</label>
        <input type="color" name="color" class="form-control form-control-sm form-control-color"
               value="{{ old('color', $evento->color ?? '#1e3a6e') }}" style="height:38px;padding:.25rem;">
    </div>
    <div class="col-sm-8">
        <label class="form-label fw-semibold" style="font-size:.85rem;">Período (opcional)</label>
        <select name="periodo_id" class="form-select form-select-sm">
            <option value="">— Sin período específico —</option>
            @foreach($periodos as $p)
            <option value="{{ $p->id }}" {{ old('periodo_id', $evento->periodo_id ?? '') == $p->id ? 'selected' : '' }}>
                {{ $p->nombre }}
            </option>
            @endforeach
        </select>
    </div>
</div>

<div class="mb-3">
    <label class="form-label fw-semibold" style="font-size:.85rem;">Descripción</label>
    <textarea name="descripcion" class="form-control form-control-sm" rows="3"
              placeholder="Detalles adicionales del evento...">{{ old('descripcion', $evento->descripcion ?? '') }}</textarea>
</div>
