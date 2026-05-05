@extends('layouts.admin')

@section('page-title', isset($reconocimiento) ? 'Editar Reconocimiento' : 'Nuevo Reconocimiento')

@section('content')
<div class="max-w-2xl mx-auto space-y-6">

    {{-- Encabezado --}}
    <div class="flex items-center gap-4">
        <a href="{{ route('admin.reconocimientos.index') }}"
           class="p-2 rounded-lg text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
        </a>
        <div>
            <h1 class="text-xl font-bold text-gray-900 dark:text-white">
                {{ isset($reconocimiento) ? '✏️ Editar Reconocimiento' : '🏆 Nuevo Reconocimiento' }}
            </h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                {{ isset($reconocimiento) ? 'Modifica los datos del reconocimiento.' : 'Registra un nuevo reconocimiento estudiantil.' }}
            </p>
        </div>
    </div>

    {{-- Formulario --}}
    <form method="POST"
          action="{{ isset($reconocimiento) ? route('admin.reconocimientos.update', $reconocimiento) : route('admin.reconocimientos.store') }}"
          class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-6 space-y-5">
        @csrf
        @isset($reconocimiento) @method('PUT') @endisset

        {{-- Errores globales --}}
        @if($errors->any())
        <div class="p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-700 rounded-lg text-sm text-red-700 dark:text-red-300 space-y-1">
            @foreach($errors->all() as $err)
            <p>• {{ $err }}</p>
            @endforeach
        </div>
        @endif

        {{-- Estudiante --}}
        <div>
            <label for="estudiante_id" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">
                Estudiante <span class="text-red-500">*</span>
            </label>
            <select id="estudiante_id" name="estudiante_id" required
                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:ring-indigo-500 focus:border-indigo-500 @error('estudiante_id') border-red-400 @enderror">
                <option value="">— Selecciona un estudiante —</option>
                @foreach($estudiantes as $e)
                    <option value="{{ $e->id }}" @selected(old('estudiante_id', $reconocimiento->estudiante_id ?? '') == $e->id)>
                        {{ $e->nombre_completo }} ({{ $e->numero_matricula }})
                    </option>
                @endforeach
            </select>
            @error('estudiante_id')
            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
            @enderror
        </div>

        {{-- Tipo de reconocimiento --}}
        <div>
            <label for="tipo_id" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">
                Tipo de Reconocimiento <span class="text-red-500">*</span>
            </label>
            <select id="tipo_id" name="tipo_id" required
                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:ring-indigo-500 focus:border-indigo-500 @error('tipo_id') border-red-400 @enderror">
                <option value="">— Selecciona un tipo —</option>
                @foreach($tipos as $t)
                    <option value="{{ $t->id }}" @selected(old('tipo_id', $reconocimiento->tipo_id ?? '') == $t->id)>
                        {{ $t->icono }} {{ $t->nombre }}
                    </option>
                @endforeach
            </select>
            @error('tipo_id')
            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
            @enderror
        </div>

        {{-- Título --}}
        <div>
            <label for="titulo" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">
                Título del Diploma <span class="text-red-500">*</span>
            </label>
            <input type="text" id="titulo" name="titulo" maxlength="160" required
                   value="{{ old('titulo', $reconocimiento->titulo ?? '') }}"
                   placeholder="Ej: Primer lugar en rendimiento académico del trimestre"
                   class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:ring-indigo-500 focus:border-indigo-500 @error('titulo') border-red-400 @enderror">
            @error('titulo')
            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
            @enderror
        </div>

        {{-- Descripción --}}
        <div>
            <label for="descripcion" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">
                Descripción / Motivo
            </label>
            <textarea id="descripcion" name="descripcion" rows="3" maxlength="1000"
                      placeholder="Describe brevemente el motivo del reconocimiento..."
                      class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:ring-indigo-500 focus:border-indigo-500 @error('descripcion') border-red-400 @enderror">{{ old('descripcion', $reconocimiento->descripcion ?? '') }}</textarea>
            @error('descripcion')
            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
            @enderror
        </div>

        {{-- Fecha --}}
        <div>
            <label for="fecha" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">
                Fecha de Emisión <span class="text-red-500">*</span>
            </label>
            <input type="date" id="fecha" name="fecha" required
                   value="{{ old('fecha', isset($reconocimiento) ? $reconocimiento->fecha->format('Y-m-d') : now()->format('Y-m-d')) }}"
                   class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:ring-indigo-500 focus:border-indigo-500 @error('fecha') border-red-400 @enderror">
            @error('fecha')
            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
            @enderror
        </div>

        {{-- Botones --}}
        <div class="flex items-center justify-end gap-3 pt-2 border-t border-gray-100 dark:border-gray-700">
            <a href="{{ route('admin.reconocimientos.index') }}"
               class="px-4 py-2 text-sm text-gray-600 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg font-medium transition">
                Cancelar
            </a>
            <button type="submit"
                    class="px-5 py-2 text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg shadow transition">
                {{ isset($reconocimiento) ? 'Guardar Cambios' : 'Registrar Reconocimiento' }}
            </button>
        </div>
    </form>

</div>
@endsection
