@extends('layouts.admin')

@section('page-title', $beca->exists ? 'Editar Beca' : 'Nueva Beca')

@section('content')
<div class="max-w-2xl mx-auto space-y-6">

    {{-- Cabecera --}}
    <div class="flex items-center gap-3">
        <a href="{{ route('admin.becas.index') }}"
           class="p-2 rounded-lg text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700 transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
        </a>
        <div>
            <h1 class="text-xl font-bold text-gray-800 dark:text-white">
                {{ $beca->exists ? 'Editar Beca' : 'Nueva Beca' }}
            </h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                {{ $beca->exists ? "Modificar: {$beca->nombre}" : 'Registrar una nueva beca o descuento' }}
            </p>
        </div>
    </div>

    {{-- Formulario --}}
    <form method="POST"
          action="{{ $beca->exists ? route('admin.becas.update', $beca) : route('admin.becas.store') }}"
          class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6 space-y-5"
          x-data="becaForm()">

        @csrf
        @if($beca->exists) @method('PUT') @endif

        @if($errors->any())
        <div class="p-3 rounded-lg bg-red-50 border border-red-200 dark:bg-red-900/20 dark:border-red-700">
            <ul class="text-sm text-red-700 dark:text-red-300 space-y-1 list-disc list-inside">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        {{-- Nombre --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                Nombre <span class="text-red-500">*</span>
            </label>
            <input type="text" name="nombre" value="{{ old('nombre', $beca->nombre) }}"
                   required maxlength="150"
                   placeholder="Ej: Beca de Excelencia Académica"
                   class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm px-3 py-2.5 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 @error('nombre') border-red-400 @enderror">
            @error('nombre') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
        </div>

        {{-- Descripción --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Descripción</label>
            <textarea name="descripcion" rows="3" maxlength="500"
                      placeholder="Descripción opcional de la beca..."
                      class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm px-3 py-2.5 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 resize-none">{{ old('descripcion', $beca->descripcion) }}</textarea>
        </div>

        {{-- Tipo y Valor --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Tipo de descuento <span class="text-red-500">*</span>
                </label>
                <select name="tipo" x-model="tipo" required
                        class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm px-3 py-2.5 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500">
                    <option value="">Seleccionar...</option>
                    <option value="porcentaje" {{ old('tipo', $beca->tipo) === 'porcentaje' ? 'selected' : '' }}>Porcentaje (%)</option>
                    <option value="monto_fijo" {{ old('tipo', $beca->tipo) === 'monto_fijo' ? 'selected' : '' }}>Monto fijo (RD$)</option>
                </select>
                @error('tipo') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Valor <span class="text-red-500">*</span>
                </label>
                <div class="relative">
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-sm font-semibold text-gray-500 dark:text-gray-400"
                          x-text="tipo === 'porcentaje' ? '%' : 'RD$'">
                        {{ $beca->tipo === 'porcentaje' ? '%' : 'RD$' }}
                    </span>
                    <input type="number" name="valor" value="{{ old('valor', $beca->valor) }}"
                           required min="0.01" step="0.01"
                           :max="tipo === 'porcentaje' ? 100 : 9999999"
                           class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm pl-10 pr-3 py-2.5 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 @error('valor') border-red-400 @enderror">
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1"
                   x-show="tipo === 'porcentaje'">Máximo 100%</p>
                @error('valor') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>
        </div>

        {{-- Criterio --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Criterio de elegibilidad</label>
            <input type="text" name="criterio" value="{{ old('criterio', $beca->criterio) }}"
                   maxlength="255"
                   placeholder="Ej: Promedio mayor a 90, Condición socioeconómica, Hijo de empleado..."
                   class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm px-3 py-2.5 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500">
        </div>

        {{-- Estado --}}
        <div class="flex items-center gap-3">
            <label class="relative inline-flex items-center cursor-pointer">
                <input type="checkbox" name="activo" value="1" class="sr-only peer"
                       {{ old('activo', $beca->activo ?? true) ? 'checked' : '' }}>
                <div class="w-10 h-5 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-indigo-400 rounded-full peer dark:bg-gray-700
                            peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5
                            after:left-0.5 after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all
                            peer-checked:bg-indigo-600"></div>
            </label>
            <span class="text-sm text-gray-700 dark:text-gray-300">Beca activa</span>
        </div>

        {{-- Botones --}}
        <div class="flex items-center justify-end gap-3 pt-2 border-t border-gray-100 dark:border-gray-700">
            <a href="{{ route('admin.becas.index') }}"
               class="px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                Cancelar
            </a>
            <button type="submit"
                    class="px-5 py-2 rounded-lg bg-indigo-600 text-sm font-semibold text-white hover:bg-indigo-700 transition shadow-sm">
                {{ $beca->exists ? 'Guardar cambios' : 'Crear beca' }}
            </button>
        </div>

    </form>

</div>

<script>
function becaForm() {
    return {
        tipo: '{{ old('tipo', $beca->tipo ?? '') }}',
    };
}
</script>
@endsection
