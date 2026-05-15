@extends('layouts.portal')

@section('title', 'Editar Material')

@section('sidebar')
    @include('portal.docente._sidebar_clase', ['activeKey' => 'classroom', 'asignacion' => $claseVirtual->asignacion])
@endsection

@section('content')
<div class="max-w-2xl mx-auto space-y-6" x-data="{ tipo: '{{ old('tipo', $material->tipo) }}' }">

    <div class="flex items-center gap-3">
        <a href="{{ route('portal.docente.classroom.show', $claseVirtual) }}"
           class="text-gray-400 hover:text-gray-600 transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
        </a>
        <div>
            <h1 class="text-xl font-bold text-gray-900 dark:text-white">Editar Material</h1>
            <p class="text-sm text-gray-500">{{ $claseVirtual->nombre }}</p>
        </div>
    </div>

    @if($errors->any())
        <div class="bg-red-50 border border-red-200 rounded-lg p-4">
            <ul class="text-sm text-red-700 space-y-1 list-disc list-inside">
                @foreach($errors->all() as $error) <li>{{ $error }}</li> @endforeach
            </ul>
        </div>
    @endif

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-800 rounded-lg px-4 py-3 text-sm">{{ session('success') }}</div>
    @endif

    <form method="POST"
          action="{{ route('portal.docente.classroom.actualizar_material', [$claseVirtual, $material]) }}"
          enctype="multipart/form-data"
          class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-6 space-y-5">
        @csrf @method('PUT')

        {{-- Tipo --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Tipo</label>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                @foreach($tipos as $key => $info)
                    <label class="cursor-pointer">
                        <input type="radio" name="tipo" value="{{ $key }}" x-model="tipo"
                               @checked(old('tipo', $material->tipo) === $key) class="sr-only">
                        <div :class="tipo === '{{ $key }}'
                                ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300'
                                : 'border-gray-200 dark:border-gray-600 text-gray-600 dark:text-gray-400'"
                             class="border-2 rounded-lg p-3 text-center text-sm font-medium transition cursor-pointer">
                            {{ $info['label'] }}
                        </div>
                    </label>
                @endforeach
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Título <span class="text-red-500">*</span></label>
            <input type="text" name="titulo" value="{{ old('titulo', $material->titulo) }}" required maxlength="200"
                   class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 text-sm dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-blue-500">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Contenido</label>
            <textarea name="contenido" rows="5"
                      class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 text-sm dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-blue-500">{{ old('contenido', $material->contenido) }}</textarea>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">URL externa</label>
            <input type="url" name="url_externo" value="{{ old('url_externo', $material->url_externo) }}"
                   class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 text-sm dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-blue-500">
        </div>

        <div x-show="tipo === 'tarea' || tipo === 'evaluacion'" class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Fecha límite</label>
                <input type="datetime-local" name="fecha_limite"
                       value="{{ old('fecha_limite', $material->fecha_limite?->format('Y-m-d\TH:i')) }}"
                       class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 text-sm dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Puntos</label>
                <input type="number" name="puntos" value="{{ old('puntos', $material->puntos) }}" min="0" max="100"
                       class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 text-sm dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-blue-500">
            </div>
        </div>

        {{-- Archivos existentes --}}
        @if($material->archivos->isNotEmpty())
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Archivos actuales</label>
                <div class="space-y-2">
                    @foreach($material->archivos as $archivo)
                        <div class="flex items-center justify-between bg-gray-50 dark:bg-gray-700 rounded-lg px-3 py-2">
                            <a href="{{ $archivo->url }}" target="_blank"
                               class="text-sm text-blue-600 hover:text-blue-800 truncate">
                                {{ $archivo->nombre_original }}
                            </a>
                            <form method="POST"
                                  action="{{ route('portal.docente.classroom.eliminar_archivo', [$claseVirtual, $archivo]) }}"
                                  onsubmit="return confirm('¿Eliminar este archivo?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-red-500 hover:text-red-700 text-xs ml-3">Eliminar</button>
                            </form>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Agregar archivos</label>
            <input type="file" name="archivos[]" multiple
                   class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 text-sm dark:bg-gray-700 dark:text-white file:mr-3 file:py-1 file:px-3 file:rounded-md file:border-0 file:bg-blue-50 file:text-blue-700 file:text-xs">
        </div>

        <label class="flex items-center gap-2 cursor-pointer">
            <input type="checkbox" name="publicado" value="1" @checked(old('publicado', $material->publicado))
                   class="w-4 h-4 rounded text-blue-600">
            <span class="text-sm text-gray-700 dark:text-gray-300">Publicado</span>
        </label>

        <div class="flex justify-end gap-3 pt-2">
            <a href="{{ route('portal.docente.classroom.show', $claseVirtual) }}"
               class="px-5 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                Cancelar
            </a>
            <button type="submit"
                    class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium transition">
                Guardar Cambios
            </button>
        </div>
    </form>
</div>
@endsection
