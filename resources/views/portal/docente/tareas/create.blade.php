@extends('layouts.portal')
@section('page-title', isset($tarea) ? 'Editar Tarea' : 'Nueva Tarea')
@section('portal-name', 'Portal Docente')

@section('sidebar')
    @include('portal.docente._sidebar_clase', ['activeKey' => 'tareas'])
@endsection

@section('bottom-nav')
    <a href="{{ route('portal.docente.dashboard') }}" class="prt-nav-item">
        <i class="bi bi-house-fill"></i>Inicio
    </a>
    <a href="{{ route('portal.docente.tareas.index', $asignacion) }}" class="prt-nav-item active">
        <i class="bi bi-check2-square"></i>Tareas
    </a>
@endsection

@section('content')

{{-- Encabezado --}}
<div class="d-flex align-items-center gap-2 mb-3">
    <a href="{{ route('portal.docente.tareas.index', $asignacion) }}"
       class="btn btn-outline-secondary btn-sm" style="padding:.25rem .6rem;">
        <i class="bi bi-arrow-left"></i>
    </a>
    <div>
        <h2 style="font-size:1rem;font-weight:800;margin:0;">
            {{ isset($tarea) ? 'Editar Tarea' : 'Nueva Tarea' }}
        </h2>
        <p style="font-size:.78rem;color:var(--prt-muted);margin:0;">
            {{ $asignacion->asignatura?->nombre }} &mdash;
            {{ $asignacion->grupo?->nombre_corto ?? $asignacion->grupo?->nombre ?? '' }}
        </p>
    </div>
</div>

{{-- Formulario --}}
@php
    $action = isset($tarea)
        ? route('portal.docente.tareas.update', [$asignacion, $tarea])
        : route('portal.docente.tareas.store', $asignacion);
    $method = isset($tarea) ? 'PUT' : 'POST';
@endphp

<div style="background:#fff;border:1.5px solid #e2e8f0;border-radius:12px;padding:1.25rem;">
    <form method="POST" action="{{ $action }}" x-data="{ tipo: '{{ old('tipo', $tarea->tipo ?? 'tarea') }}' }">
        @csrf
        @if($method === 'PUT') @method('PUT') @endif

        @if($errors->any())
        <div class="alert alert-danger py-2 mb-3" style="font-size:.82rem;">
            <ul class="mb-0 ps-3">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        {{-- Tipo --}}
        <div class="mb-3">
            <label class="form-label fw-semibold" style="font-size:.84rem;">
                Tipo <span class="text-danger">*</span>
            </label>
            <div class="d-flex gap-2 flex-wrap">
                @foreach($tipos as $valor => $label)
                @php
                    $colores = ['tarea'=>'#3b82f6','actividad'=>'#10b981','proyecto'=>'#8b5cf6','evaluacion'=>'#ef4444'];
                    $color   = $colores[$valor] ?? '#6b7280';
                @endphp
                <label style="cursor:pointer;">
                    <input type="radio" name="tipo" value="{{ $valor }}" x-model="tipo"
                           {{ old('tipo', $tarea->tipo ?? 'tarea') === $valor ? 'checked' : '' }}
                           class="d-none" required>
                    <span :style="tipo === '{{ $valor }}'
                        ? 'background:{{ $color }};color:#fff;border-color:{{ $color }};'
                        : 'background:#f8fafc;color:#475569;border-color:#cbd5e1;'"
                          style="display:inline-block;padding:.3rem .85rem;border-radius:99px;font-size:.78rem;font-weight:600;border:1.5px solid #cbd5e1;transition:all .15s;">
                        {{ $label }}
                    </span>
                </label>
                @endforeach
            </div>
        </div>

        {{-- Título --}}
        <div class="mb-3">
            <label class="form-label fw-semibold" style="font-size:.84rem;">
                Título <span class="text-danger">*</span>
            </label>
            <input type="text" name="titulo" value="{{ old('titulo', $tarea->titulo ?? '') }}"
                   class="form-control form-control-sm @error('titulo') is-invalid @enderror"
                   placeholder="Ej. Trabajo práctico Unidad 3" required maxlength="255">
            @error('titulo')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        {{-- Descripción --}}
        <div class="mb-3">
            <label class="form-label fw-semibold" style="font-size:.84rem;">
                Descripción / Instrucciones
            </label>
            <textarea name="descripcion" rows="4"
                      class="form-control form-control-sm @error('descripcion') is-invalid @enderror"
                      placeholder="Describe lo que deben hacer los estudiantes…" maxlength="5000">{{ old('descripcion', $tarea->descripcion ?? '') }}</textarea>
            @error('descripcion')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        {{-- Fecha límite + Puntos --}}
        <div class="row g-3 mb-3">
            <div class="col-sm-6">
                <label class="form-label fw-semibold" style="font-size:.84rem;">
                    Fecha límite <span class="text-danger">*</span>
                </label>
                <input type="date" name="fecha_limite"
                       value="{{ old('fecha_limite', isset($tarea) ? $tarea->fecha_limite->format('Y-m-d') : '') }}"
                       class="form-control form-control-sm @error('fecha_limite') is-invalid @enderror" required>
                @error('fecha_limite')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-sm-6">
                <label class="form-label fw-semibold" style="font-size:.84rem;">
                    Puntos / Valor <span style="font-weight:400;color:var(--prt-muted);">(opcional)</span>
                </label>
                <input type="number" name="puntos_valor" min="1" max="100"
                       value="{{ old('puntos_valor', $tarea->puntos_valor ?? '') }}"
                       class="form-control form-control-sm @error('puntos_valor') is-invalid @enderror"
                       placeholder="Ej. 10">
                @error('puntos_valor')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>

        {{-- Activo (solo edición) --}}
        @isset($tarea)
        <div class="mb-3 d-flex align-items-center gap-2">
            <input type="checkbox" name="activo" id="activo" value="1"
                   class="form-check-input" style="width:1.1rem;height:1.1rem;"
                   {{ old('activo', $tarea->activo) ? 'checked' : '' }}>
            <label for="activo" class="form-check-label" style="font-size:.84rem;font-weight:600;cursor:pointer;">
                Tarea activa (visible para estudiantes)
            </label>
        </div>
        @endisset

        {{-- Botones --}}
        <div class="d-flex gap-2 mt-4">
            <button type="submit" class="btn btn-primary btn-sm px-4">
                <i class="bi bi-floppy-fill me-1"></i>
                {{ isset($tarea) ? 'Guardar cambios' : 'Crear tarea' }}
            </button>
            <a href="{{ route('portal.docente.tareas.index', $asignacion) }}"
               class="btn btn-outline-secondary btn-sm">
                Cancelar
            </a>
        </div>
    </form>
</div>

@endsection
