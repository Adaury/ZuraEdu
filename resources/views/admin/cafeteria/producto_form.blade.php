@extends('layouts.admin')

@section('page-title', isset($producto) ? 'Editar Producto' : 'Nuevo Producto')

@section('content')
<div class="max-w-xl mx-auto px-4 py-6">

    {{-- Encabezado --}}
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('admin.cafeteria.productos.index') }}"
           class="text-slate-400 hover:text-slate-700 transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
        </a>
        <h1 class="text-xl font-bold text-slate-800">
            {{ isset($producto) ? 'Editar Producto' : 'Nuevo Producto' }}
        </h1>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-6">
        <form method="POST"
              action="{{ isset($producto)
                ? route('admin.cafeteria.productos.update', $producto)
                : route('admin.cafeteria.productos.store') }}">
            @csrf
            @if(isset($producto)) @method('PUT') @endif

            {{-- Nombre --}}
            <div class="mb-4">
                <label class="block text-sm font-medium text-slate-700 mb-1">
                    Nombre <span class="text-red-500">*</span>
                </label>
                <input type="text" name="nombre"
                       value="{{ old('nombre', $producto->nombre ?? '') }}"
                       class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm
                              focus:outline-none focus:ring-2 focus:ring-blue-500
                              @error('nombre') border-red-400 @enderror"
                       placeholder="Ej. Arroz con pollo" required>
                @error('nombre')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Precio --}}
            <div class="mb-4">
                <label class="block text-sm font-medium text-slate-700 mb-1">
                    Precio (RD$) <span class="text-red-500">*</span>
                </label>
                <input type="number" name="precio" step="0.01" min="0"
                       value="{{ old('precio', $producto->precio ?? '') }}"
                       class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm
                              focus:outline-none focus:ring-2 focus:ring-blue-500
                              @error('precio') border-red-400 @enderror"
                       placeholder="0.00" required>
                @error('precio')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Categoría --}}
            <div class="mb-4">
                <label class="block text-sm font-medium text-slate-700 mb-1">
                    Categoría <span class="text-red-500">*</span>
                </label>
                <select name="categoria"
                        class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm
                               focus:outline-none focus:ring-2 focus:ring-blue-500
                               @error('categoria') border-red-400 @enderror">
                    @foreach($categorias as $key => $label)
                        <option value="{{ $key }}"
                            {{ old('categoria', $producto->categoria ?? 'comida') === $key ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
                @error('categoria')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Activo --}}
            <div class="mb-6 flex items-center gap-3">
                <input type="hidden" name="activo" value="0">
                <input type="checkbox" name="activo" value="1" id="activo"
                       {{ old('activo', $producto->activo ?? true) ? 'checked' : '' }}
                       class="w-4 h-4 rounded text-blue-600 border-slate-300 focus:ring-blue-500">
                <label for="activo" class="text-sm font-medium text-slate-700">
                    Producto activo (disponible para venta)
                </label>
            </div>

            {{-- Botones --}}
            <div class="flex gap-3">
                <button type="submit"
                        class="flex-1 bg-blue-600 hover:bg-blue-700 text-white text-sm
                               font-medium py-2 px-4 rounded-lg transition">
                    {{ isset($producto) ? 'Actualizar Producto' : 'Crear Producto' }}
                </button>
                <a href="{{ route('admin.cafeteria.productos.index') }}"
                   class="flex-1 text-center bg-slate-100 hover:bg-slate-200 text-slate-700
                          text-sm font-medium py-2 px-4 rounded-lg transition">
                    Cancelar
                </a>
            </div>
        </form>
    </div>

</div>
@endsection
