@extends('layouts.admin')

@section('page-title', isset($recurso) ? 'Editar Recurso' : 'Nuevo Recurso')

@section('content')
<div class="max-w-2xl mx-auto space-y-6">

    {{-- Encabezado --}}
    <div class="flex items-center gap-3">
        <a href="{{ route('admin.recursos.index') }}"
           class="p-2 text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-gray-800 dark:text-white">
                {{ isset($recurso) ? 'Editar Recurso' : 'Nuevo Recurso' }}
            </h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                {{ isset($recurso) ? $recurso->nombre : 'Complete los datos del recurso físico' }}
            </p>
        </div>
    </div>

    {{-- Formulario --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm">
        <form method="POST"
              action="{{ isset($recurso) ? route('admin.recursos.update', $recurso) : route('admin.recursos.store') }}"
              class="p-6 space-y-5">
            @csrf
            @if(isset($recurso)) @method('PUT') @endif

            {{-- Nombre --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                    Nombre <span class="text-red-500">*</span>
                </label>
                <input type="text" name="nombre" value="{{ old('nombre', $recurso->nombre ?? '') }}"
                       required maxlength="120" placeholder="Ej: Aula 201, Lab. Química, Cancha Principal"
                       class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-blue-500 focus:border-blue-500 @error('nombre') border-red-400 @enderror">
                @error('nombre')
                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            {{-- Tipo --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                    Tipo <span class="text-red-500">*</span>
                </label>
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-2" x-data="{ sel: '{{ old('tipo', $recurso->tipo ?? 'aula') }}' }">
                    @foreach($tipos as $key => $info)
                    <label class="relative cursor-pointer"
                           :class="sel === '{{ $key }}' ? 'ring-2 ring-blue-500' : 'ring-1 ring-gray-200 dark:ring-gray-600'">
                        <input type="radio" name="tipo" value="{{ $key }}" class="sr-only"
                               @if(old('tipo', $recurso->tipo ?? 'aula') === $key) checked @endif
                               x-on:change="sel = '{{ $key }}'">
                        <div class="flex items-center gap-2 p-3 rounded-lg bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 transition">
                            <div class="w-2.5 h-2.5 rounded-full bg-{{ $info['color'] }}-500 flex-shrink-0"></div>
                            <span class="text-sm text-gray-700 dark:text-gray-200 font-medium">{{ $info['label'] }}</span>
                            <div class="ml-auto w-4 h-4 rounded-full border-2 flex items-center justify-center"
                                 :class="sel === '{{ $key }}'
                                     ? 'border-blue-500 bg-blue-500'
                                     : 'border-gray-300 dark:border-gray-500'">
                                <div class="w-1.5 h-1.5 rounded-full bg-white"
                                     x-show="sel === '{{ $key }}'"></div>
                            </div>
                        </div>
                    </label>
                    @endforeach
                </div>
                @error('tipo')
                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            {{-- Capacidad y Ubicación --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                        Capacidad (personas)
                    </label>
                    <input type="number" name="capacidad"
                           value="{{ old('capacidad', $recurso->capacidad ?? '') }}"
                           min="1" max="9999" placeholder="Ej: 35"
                           class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-blue-500 focus:border-blue-500 @error('capacidad') border-red-400 @enderror">
                    @error('capacidad')
                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                        Ubicación
                    </label>
                    <input type="text" name="ubicacion"
                           value="{{ old('ubicacion', $recurso->ubicacion ?? '') }}"
                           maxlength="150" placeholder="Ej: Planta 2, Ala Norte"
                           class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-blue-500 focus:border-blue-500 @error('ubicacion') border-red-400 @enderror">
                    @error('ubicacion')
                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- Descripción --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                    Descripción
                </label>
                <textarea name="descripcion" rows="3" maxlength="500"
                          placeholder="Características, equipamiento disponible, notas adicionales…"
                          class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-blue-500 focus:border-blue-500 @error('descripcion') border-red-400 @enderror resize-none">{{ old('descripcion', $recurso->descripcion ?? '') }}</textarea>
                @error('descripcion')
                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            {{-- Activo --}}
            <div class="flex items-center gap-3 p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                <div x-data="{ checked: {{ old('activo', isset($recurso) ? ($recurso->activo ? 'true' : 'false') : 'true') ? 'true' : 'false' }} }">
                    <input type="hidden" name="activo" :value="checked ? '1' : '0'">
                    <button type="button" @click="checked = !checked"
                            class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors"
                            :class="checked ? 'bg-blue-600' : 'bg-gray-300 dark:bg-gray-500'">
                        <span class="inline-block h-4 w-4 transform rounded-full bg-white shadow transition-transform"
                              :class="checked ? 'translate-x-6' : 'translate-x-1'"></span>
                    </button>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-700 dark:text-gray-200">Recurso activo</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Los recursos inactivos no aparecen disponibles para reservar</p>
                </div>
            </div>

            {{-- Acciones --}}
            <div class="flex justify-end gap-3 pt-2 border-t border-gray-100 dark:border-gray-700">
                <a href="{{ route('admin.recursos.index') }}"
                   class="px-5 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition">
                    Cancelar
                </a>
                <button type="submit"
                        class="px-6 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition">
                    {{ isset($recurso) ? 'Guardar cambios' : 'Crear recurso' }}
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
