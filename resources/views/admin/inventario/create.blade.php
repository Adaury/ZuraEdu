@extends('layouts.admin')
@section('page-title', isset($articulo) ? 'Editar Artículo' : 'Nuevo Artículo')

@push('styles')
<style>
.form-card { background:#fff; border-radius:14px; border:1px solid #e5e7eb; padding:2rem; max-width:780px; }
.form-label-custom { font-size:.8rem; font-weight:700; color:#374151; margin-bottom:.3rem; }
.form-control-custom, .form-select-custom {
    border-radius:9px; border:1px solid #d1d5db; font-size:.875rem;
    padding:.55rem .9rem; background:#f9fafb; transition:border-color .15s,background .15s;
}
.form-control-custom:focus, .form-select-custom:focus {
    border-color:var(--primary); background:#fff; box-shadow:0 0 0 3px rgba(30,58,110,.08); outline:none;
}
.cat-option { display:inline-flex; align-items:center; gap:.4rem; padding:.35rem .85rem;
    border-radius:20px; font-size:.8rem; font-weight:700; cursor:pointer; border:2px solid transparent;
    transition:all .15s; user-select:none; }
.cat-option:hover { filter:brightness(.95); }
.cat-option.selected { border-color:var(--primary); box-shadow:0 0 0 3px rgba(30,58,110,.12); }
.est-option { display:inline-flex; align-items:center; gap:.4rem; padding:.35rem .85rem;
    border-radius:20px; font-size:.8rem; font-weight:700; cursor:pointer; border:2px solid transparent;
    transition:all .15s; user-select:none; }
.est-option:hover { filter:brightness(.95); }
.est-option.selected { border-color:var(--primary); box-shadow:0 0 0 3px rgba(30,58,110,.12); }
</style>
@endpush

@section('content')
<div class="d-flex align-items-center gap-2 mb-3">
    <a href="{{ route('admin.inventario.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i>
    </a>
    <h1 style="font-size:1.3rem;font-weight:800;color:var(--primary);margin:0;">
        <i class="bi bi-{{ isset($articulo) ? 'pencil-square' : 'plus-circle-fill' }} me-2"></i>
        {{ isset($articulo) ? 'Editar Artículo' : 'Nuevo Artículo' }}
    </h1>
</div>

@if($errors->any())
<div class="alert alert-danger mb-3" style="border-radius:10px;">
    <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
</div>
@endif

<div class="form-card" x-data="{
    categoria: '{{ old('categoria', $articulo->categoria ?? '') }}',
    estado: '{{ old('estado', $articulo->estado ?? 'bueno') }}'
}">
    <form method="POST"
          action="{{ isset($articulo) ? route('admin.inventario.update', $articulo) : route('admin.inventario.store') }}">
        @csrf
        @if(isset($articulo)) @method('PUT') @endif

        {{-- Nombre --}}
        <div class="mb-4">
            <label class="form-label-custom">Nombre del artículo <span class="text-danger">*</span></label>
            <input type="text" name="nombre" class="form-control form-control-custom"
                   value="{{ old('nombre', $articulo->nombre ?? '') }}"
                   placeholder="Ej: Silla plástica para aula" required>
        </div>

        {{-- Categoría --}}
        <div class="mb-4">
            <label class="form-label-custom">Categoría <span class="text-danger">*</span></label>
            <input type="hidden" name="categoria" :value="categoria">
            <div class="d-flex flex-wrap gap-2 mt-1">
                @foreach($categorias as $key => $cat)
                <div class="cat-option"
                     style="background:{{ $cat['color'] }}; color:{{ $cat['text'] }};"
                     :class="{ 'selected': categoria === '{{ $key }}' }"
                     @click="categoria = '{{ $key }}'">
                    <i class="{{ $cat['icon'] }}"></i>{{ $cat['label'] }}
                </div>
                @endforeach
            </div>
            @error('categoria')<div class="text-danger" style="font-size:.8rem; margin-top:.3rem;">{{ $message }}</div>@enderror
        </div>

        {{-- Estado --}}
        <div class="mb-4">
            <label class="form-label-custom">Estado <span class="text-danger">*</span></label>
            <input type="hidden" name="estado" :value="estado">
            <div class="d-flex flex-wrap gap-2 mt-1">
                @foreach($estados as $key => $est)
                <div class="est-option"
                     style="background:{{ $est['color'] }}; color:{{ $est['text'] }};"
                     :class="{ 'selected': estado === '{{ $key }}' }"
                     @click="estado = '{{ $key }}'">
                    <span style="width:8px;height:8px;border-radius:50%;background:{{ $est['dot'] }};display:inline-block;"></span>
                    {{ $est['label'] }}
                </div>
                @endforeach
            </div>
            @error('estado')<div class="text-danger" style="font-size:.8rem; margin-top:.3rem;">{{ $message }}</div>@enderror
        </div>

        {{-- Cantidades --}}
        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <label class="form-label-custom">Cantidad Total <span class="text-danger">*</span></label>
                <input type="number" name="cantidad_total" class="form-control form-control-custom"
                       value="{{ old('cantidad_total', $articulo->cantidad_total ?? 0) }}"
                       min="0" required>
                <div style="font-size:.74rem; color:#9ca3af; margin-top:.2rem;">Cantidad total incluyendo los en uso.</div>
            </div>
            <div class="col-md-6">
                <label class="form-label-custom">Cantidad Disponible <span class="text-danger">*</span></label>
                <input type="number" name="cantidad_disponible" class="form-control form-control-custom"
                       value="{{ old('cantidad_disponible', $articulo->cantidad_disponible ?? 0) }}"
                       min="0" required>
                <div style="font-size:.74rem; color:#9ca3af; margin-top:.2rem;">Cantidad actualmente sin asignar.</div>
            </div>
        </div>

        {{-- Ubicación --}}
        <div class="mb-4">
            <label class="form-label-custom">Ubicación</label>
            <input type="text" name="ubicacion" class="form-control form-control-custom"
                   value="{{ old('ubicacion', $articulo->ubicacion ?? '') }}"
                   placeholder="Ej: Aula 3B, Depósito central…">
        </div>

        {{-- Descripción --}}
        <div class="mb-4">
            <label class="form-label-custom">Descripción</label>
            <textarea name="descripcion" class="form-control form-control-custom" rows="3"
                      placeholder="Detalles adicionales, marca, código, observaciones…">{{ old('descripcion', $articulo->descripcion ?? '') }}</textarea>
        </div>

        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-{{ isset($articulo) ? 'check-lg' : 'plus-lg' }} me-1"></i>
                {{ isset($articulo) ? 'Guardar Cambios' : 'Registrar Artículo' }}
            </button>
            <a href="{{ route('admin.inventario.index') }}" class="btn btn-outline-secondary">Cancelar</a>
        </div>
    </form>
</div>
@endsection
