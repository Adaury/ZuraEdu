@extends('layouts.admin')

@section('page-title', isset($proyecto) ? 'Editar Proyecto' : 'Nuevo Proyecto')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">

    {{-- Breadcrumb --}}
    <nav class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
        <a href="{{ route('admin.proyectos.index') }}" class="hover:text-indigo-600 transition">Proyectos Escolares</a>
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
        </svg>
        <span class="text-gray-900 dark:text-white font-medium">
            {{ isset($proyecto) ? 'Editar: '.$proyecto->titulo : 'Nuevo Proyecto' }}
        </span>
    </nav>

    {{-- Formulario --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
        <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-6">
            {{ isset($proyecto) ? 'Editar Proyecto' : 'Crear Nuevo Proyecto' }}
        </h2>

        <form method="POST"
              action="{{ isset($proyecto) ? route('admin.proyectos.update', $proyecto) : route('admin.proyectos.store') }}">
            @csrf
            @if(isset($proyecto)) @method('PUT') @endif

            <div class="space-y-5">

                {{-- Título --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Título del Proyecto <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="titulo"
                           value="{{ old('titulo', $proyecto->titulo ?? '') }}"
                           required
                           class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-indigo-500 focus:border-indigo-500 text-sm"
                           placeholder="Ej. Investigación sobre energías renovables">
                    @error('titulo')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Descripción --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Descripción
                    </label>
                    <textarea name="descripcion" rows="3"
                              class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-indigo-500 focus:border-indigo-500 text-sm"
                              placeholder="Describe brevemente el objetivo y alcance del proyecto...">{{ old('descripcion', $proyecto->descripcion ?? '') }}</textarea>
                    @error('descripcion')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Área y Estado --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Área <span class="text-red-500">*</span>
                        </label>
                        <select name="area" required
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                            <option value="">Seleccionar área...</option>
                            @foreach($areas as $key => $label)
                                <option value="{{ $key }}" @selected(old('area', $proyecto->area ?? '') === $key)>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                        @error('area')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Estado <span class="text-red-500">*</span>
                        </label>
                        <select name="estado" required
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                            @foreach($estados as $key => $label)
                                <option value="{{ $key }}" @selected(old('estado', $proyecto->estado ?? 'planificacion') === $key)>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                        @error('estado')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Tutor y Año escolar --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Tutor/Docente Responsable <span class="text-red-500">*</span>
                        </label>
                        <select name="tutor_id" required
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                            <option value="">Seleccionar tutor...</option>
                            @foreach($tutores as $tutor)
                                <option value="{{ $tutor->id }}"
                                    @selected(old('tutor_id', $proyecto->tutor_id ?? '') == $tutor->id)>
                                    {{ $tutor->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('tutor_id')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Año Escolar <span class="text-red-500">*</span>
                        </label>
                        <select name="school_year_id" required
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                            <option value="">Seleccionar año...</option>
                            @foreach($schoolYears as $sy)
                                <option value="{{ $sy->id }}"
                                    @selected(old('school_year_id', $proyecto->school_year_id ?? $schoolYear?->id) == $sy->id)>
                                    {{ $sy->nombre }}
                                </option>
                            @endforeach
                        </select>
                        @error('school_year_id')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Fechas --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Fecha de Inicio <span class="text-red-500">*</span>
                        </label>
                        <input type="date" name="fecha_inicio"
                               value="{{ old('fecha_inicio', isset($proyecto) ? $proyecto->fecha_inicio->format('Y-m-d') : '') }}"
                               required
                               class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                        @error('fecha_inicio')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Fecha de Fin <span class="text-xs text-gray-400">(opcional)</span>
                        </label>
                        <input type="date" name="fecha_fin"
                               value="{{ old('fecha_fin', isset($proyecto) && $proyecto->fecha_fin ? $proyecto->fecha_fin->format('Y-m-d') : '') }}"
                               class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                        @error('fecha_fin')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

            </div>

            {{-- Botones --}}
            <div class="flex items-center gap-3 mt-8 pt-5 border-t border-gray-200 dark:border-gray-700">
                <button type="submit"
                        class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-lg shadow transition">
                    {{ isset($proyecto) ? 'Guardar Cambios' : 'Crear Proyecto' }}
                </button>
                <a href="{{ isset($proyecto) ? route('admin.proyectos.show', $proyecto) : route('admin.proyectos.index') }}"
                   class="px-6 py-2.5 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 text-sm font-semibold rounded-lg transition">
                    Cancelar
                </a>
            </div>
        </form>
    </div>

</div>
@endsection
