@extends('layouts.superadmin')
@section('title', 'Editar — '.$tenant->nombre_institucion)
@section('content')

<div class="mb-4">
    <h4 class="fw-bold mb-1"><i class="bi bi-building-gear me-2" style="color:#6366f1;"></i>Editar Institución</h4>
    <p class="text-muted small mb-0">{{ $tenant->nombre_institucion }}</p>
</div>

<form method="POST" action="{{ route('superadmin.tenants.update', $tenant) }}">
@csrf @method('PUT')
<div class="row g-4">

    <div class="col-lg-8">
        <div class="card border-0 shadow-sm mb-3" style="border-radius:16px;">
            <div class="card-body p-4">
                <h6 class="fw-bold mb-3" style="color:#6366f1;"><i class="bi bi-info-circle me-2"></i>Información General</h6>
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label fw-semibold small">Nombre de la Institución <span class="text-danger">*</span></label>
                        <input type="text" name="nombre_institucion" class="form-control" value="{{ old('nombre_institucion', $tenant->nombre_institucion) }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold small">Subdominio <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="text" name="dominio" class="form-control" value="{{ old('dominio', $tenant->dominio) }}" required pattern="[a-z0-9\-]+">
                            <span class="input-group-text text-muted" style="font-size:.8rem;">.zuraedu.com</span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold small">Dominio Personalizado</label>
                        <input type="text" name="dominio_personalizado" class="form-control" value="{{ old('dominio_personalizado', $tenant->dominio_personalizado) }}" placeholder="miescuela.edu.do">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold small">Tipo</label>
                        <select name="tipo" class="form-select">
                            @foreach(['publico'=>'Público','privado'=>'Privado','instituto'=>'Instituto','tecnico'=>'Técnico'] as $v=>$l)
                            <option value="{{ $v }}" @selected(old('tipo',$tenant->tipo)===$v)>{{ $l }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold small">Estado</label>
                        <select name="estado" class="form-select">
                            @foreach(['activo'=>'Activo','prueba'=>'En Prueba','suspendido'=>'Suspendido','cancelado'=>'Cancelado'] as $v=>$l)
                            <option value="{{ $v }}" @selected(old('estado',$tenant->estado)===$v)>{{ $l }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold small">Plan</label>
                        <select name="plan" class="form-select">
                            @foreach(['free'=>'Free','pro'=>'Pro','premium'=>'Premium'] as $v=>$l)
                            <option value="{{ $v }}" @selected(old('plan',$tenant->plan)===$v)>{{ $l }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold small">Email de Contacto</label>
                        <input type="email" name="email_contacto" class="form-control" value="{{ old('email_contacto', $tenant->email_contacto) }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold small">Ciudad</label>
                        <input type="text" name="ciudad" class="form-control" value="{{ old('ciudad', $tenant->ciudad) }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold small">Teléfono</label>
                        <input type="text" name="telefono_contacto" class="form-control" value="{{ old('telefono_contacto', $tenant->telefono_contacto) }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold small">Fecha Registro</label>
                        <input type="date" name="fecha_registro" class="form-control" value="{{ old('fecha_registro', $tenant->fecha_registro?->toDateString()) }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold small">Fecha Vencimiento</label>
                        <input type="date" name="fecha_vencimiento" class="form-control" value="{{ old('fecha_vencimiento', $tenant->fecha_vencimiento?->toDateString()) }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold small">Máx. Estudiantes</label>
                        <input type="number" name="max_estudiantes" class="form-control" value="{{ old('max_estudiantes', $tenant->max_estudiantes) }}" min="1">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold small">Máx. Docentes</label>
                        <input type="number" name="max_docentes" class="form-control" value="{{ old('max_docentes', $tenant->max_docentes) }}" min="1">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold small">Color Primario</label>
                        <input type="color" name="color_primario" class="form-control form-control-color w-100" value="{{ old('color_primario', $tenant->color_primario) }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold small">Color Secundario</label>
                        <input type="color" name="color_secundario" class="form-control form-control-color w-100" value="{{ old('color_secundario', $tenant->color_secundario) }}">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card border-0 shadow-sm" style="border-radius:16px;">
            <div class="card-body p-4">
                <h6 class="fw-bold mb-3" style="color:#6366f1;"><i class="bi bi-toggles me-2"></i>Módulos</h6>
                <div class="d-flex gap-2 mb-3">
                    <button type="button" class="btn btn-xs btn-outline-primary" onclick="toggleAll(true)">Todos</button>
                    <button type="button" class="btn btn-xs btn-outline-secondary" onclick="toggleAll(false)">Ninguno</button>
                </div>
                @php $oldFeatures = old('features'); @endphp
                @foreach($features as $key => $label)
                @php
                    $activo = $featureMap[$key]?->activo ?? false;
                    $checked = $oldFeatures !== null ? in_array($key, (array)$oldFeatures) : $activo;
                @endphp
                <div class="form-check form-switch mb-2">
                    <input class="form-check-input feature-check" type="checkbox" name="features[]"
                           id="f_{{ $key }}" value="{{ $key }}" @checked($checked)>
                    <label class="form-check-label small" for="f_{{ $key }}">{{ $label }}</label>
                </div>
                @endforeach
            </div>
        </div>
    </div>

</div>

<div class="mt-3 d-flex gap-2">
    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Guardar Cambios</button>
    <a href="{{ route('superadmin.tenants.show', $tenant) }}" class="btn btn-outline-secondary">Cancelar</a>
</div>
</form>

<script>
function toggleAll(state) {
    document.querySelectorAll('.feature-check').forEach(c => c.checked = state);
}
</script>
@endsection
