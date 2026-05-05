@extends('layouts.admin')
@section('page-title', 'Nueva Aula Virtual')
@section('content')

<div class="mb-4 d-flex align-items-center gap-3">
    <a href="{{ route('admin.classroom.index') }}" class="btn btn-outline-secondary btn-sm">← Volver</a>
    <h5 class="fw-bold mb-0">Nueva Aula Virtual</h5>
</div>

<div class="card border-0 shadow-sm" style="border-radius:16px;max-width:600px;">
<div class="card-body p-4">
<form method="POST" action="{{ route('admin.classroom.store') }}">
@csrf
<div class="mb-3">
    <label class="form-label fw-semibold">Asignación <span class="text-danger">*</span></label>
    <select name="asignacion_id" class="form-select" required>
        <option value="">Seleccionar asignación...</option>
        @foreach($asignaciones as $asig)
        <option value="{{ $asig->id }}" {{ old('asignacion_id')==$asig->id?'selected':'' }}>
            {{ $asig->asignatura?->nombre }} — {{ $asig->grupo?->nombre }} ({{ $asig->docente?->user?->name }})
        </option>
        @endforeach
    </select>
</div>
<div class="mb-3">
    <label class="form-label fw-semibold">Nombre del Aula <span class="text-danger">*</span></label>
    <input type="text" name="nombre" class="form-control" value="{{ old('nombre') }}" required placeholder="Ej: Matemáticas 4to A">
</div>
<div class="mb-3">
    <label class="form-label fw-semibold">Descripción</label>
    <textarea name="descripcion" class="form-control" rows="3">{{ old('descripcion') }}</textarea>
</div>
<div class="mb-4">
    <label class="form-label fw-semibold">Color de portada</label>
    <div class="d-flex gap-2 flex-wrap">
        @foreach(['#3B82F6','#10B981','#F59E0B','#EF4444','#8B5CF6','#EC4899','#06B6D4','#F97316'] as $color)
        <label style="cursor:pointer;">
            <input type="radio" name="portada_color" value="{{ $color }}" {{ old('portada_color','#3B82F6')===$color?'checked':'' }} style="display:none;">
            <div style="width:32px;height:32px;background:{{ $color }};border-radius:50%;border:3px solid {{ old('portada_color','#3B82F6')===$color?'#111':'transparent' }};"></div>
        </label>
        @endforeach
    </div>
</div>
<div class="d-flex gap-2">
    <button type="submit" class="btn btn-primary px-4"><i class="bi bi-check-lg me-1"></i>Crear Aula</button>
    <a href="{{ route('admin.classroom.index') }}" class="btn btn-outline-secondary">Cancelar</a>
</div>
</form>
</div>
</div>
@endsection
