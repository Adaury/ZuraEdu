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

{{-- ── Avisar a padres, docentes y personal ─────────────────────────────── --}}
@php
    $gruposMarcados   = (array) old('notificar_grupos', []);
    $usuariosMarcados = array_map('intval', (array) old('notificar_usuarios', []));
    $yaAvisados       = $yaAvisados ?? [];
    $esEdicion        = isset($evento) && $evento->exists;
@endphp
<div class="border rounded-3 p-3 mb-3" style="background:#f8fafc;">
    <div class="form-check mb-2">
        <input class="form-check-input" type="checkbox" name="notificar" id="chkNotificar" value="1"
               {{ old('notificar') ? 'checked' : '' }}
               onchange="document.getElementById('bloqueAviso').style.display = this.checked ? 'block' : 'none'">
        <label class="form-check-label fw-semibold" for="chkNotificar" style="font-size:.85rem;">
            {{ $esEdicion ? 'Avisar de esta actualización' : 'Avisar a las personas que elija' }}
        </label>
        <div class="text-muted" style="font-size:.78rem;">
            Les llega a su bandeja de <strong>Mensajes</strong>, como notificación y por <strong>correo electrónico</strong> con el botón
            «Agregar a Google Calendar» (cada persona decide si lo agrega).
        </div>
    </div>

    <div id="bloqueAviso" style="display:{{ old('notificar') ? 'block' : 'none' }};">
        <div class="mb-3">
            <div class="fw-semibold mb-1" style="font-size:.82rem;">Grupos completos</div>
            @foreach($gruposAviso as $clave => $etiqueta)
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="checkbox" name="notificar_grupos[]" id="g_{{ $clave }}" value="{{ $clave }}"
                       {{ in_array($clave, $gruposMarcados, true) ? 'checked' : '' }}>
                <label class="form-check-label" for="g_{{ $clave }}" style="font-size:.82rem;">{{ $etiqueta }}</label>
            </div>
            @endforeach
        </div>

        <div class="mb-3">
            <label class="fw-semibold mb-1" for="selAulas" style="font-size:.82rem;">Padres de grupos específicos (opcional)</label>
            <select name="notificar_padres_grupos[]" id="selAulas" class="form-select form-select-sm" multiple size="6">
                @foreach($aulasAviso as $g)
                <option value="{{ $g->id }}" {{ in_array($g->id, array_map('intval', (array) old('notificar_padres_grupos', [])), true) ? 'selected' : '' }}>
                    {{ $g->nombre_completo }}
                </option>
                @endforeach
            </select>
            <div class="text-muted" style="font-size:.76rem;">Se avisa a los representantes de los estudiantes matriculados en esos grupos (una sola vez por representante, aunque tenga varios hijos).</div>
        </div>

        <div class="mb-1">
            <label class="fw-semibold mb-1" for="selPersonas" style="font-size:.82rem;">Docentes o personal en particular (opcional)</label>
            <select name="notificar_usuarios[]" id="selPersonas" class="form-select form-select-sm" multiple size="8">
                @foreach($personasAviso as $p)
                <option value="{{ $p->id }}" {{ in_array($p->id, $usuariosMarcados, true) ? 'selected' : '' }}>
                    {{ $p->name }}{{ in_array($p->id, $yaAvisados, true) ? ' — ya avisado' : '' }}
                </option>
                @endforeach
            </select>
            <div class="text-muted" style="font-size:.76rem;">Mantén Ctrl (o Cmd) para elegir varias personas.</div>
        </div>
        @if($esEdicion)
        <div class="text-muted mt-2" style="font-size:.76rem;">
            Al editar, se vuelve a avisar también a quienes ya habían recibido el aviso, indicando que el evento cambió.
        </div>
        @endif
    </div>
</div>
