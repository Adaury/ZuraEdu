@extends('layouts.admin')

@section('page-title', 'Editar Aula Virtual')

@section('content')
<div class="max-w-2xl mx-auto space-y-6">

    <div class="flex items-center gap-3">
        <a href="{{ route('admin.classroom.show', $claseVirtual) }}" class="text-gray-400 hover:text-gray-600 transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
        </a>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Editar Aula Virtual</h1>
    </div>

    @if($errors->any())
        <div class="bg-red-50 border border-red-200 rounded-lg p-4">
            <ul class="text-sm text-red-700 space-y-1 list-disc list-inside">
                @foreach($errors->all() as $error) <li>{{ $error }}</li> @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.classroom.update', $claseVirtual) }}"
          class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-6 space-y-5">
        @csrf @method('PUT')

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                Asignación <span class="text-red-500">*</span>
            </label>
            <select name="asignacion_id" required
                    class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 text-sm dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-blue-500">
                @foreach($asignaciones as $asig)
                    <option value="{{ $asig->id }}" @selected(old('asignacion_id', $claseVirtual->asignacion_id) == $asig->id)>
                        {{ $asig->asignatura?->nombre }} — {{ $asig->grupo?->nombre_corto }}
                        ({{ $asig->docente?->nombre_completo }})
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                Nombre <span class="text-red-500">*</span>
            </label>
            <input type="text" name="nombre" value="{{ old('nombre', $claseVirtual->nombre) }}" required maxlength="150"
                   class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 text-sm dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-blue-500">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Descripción</label>
            <textarea name="descripcion" rows="3" maxlength="500"
                      class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 text-sm dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-blue-500">{{ old('descripcion', $claseVirtual->descripcion) }}</textarea>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Color de Portada</label>
            <div class="flex items-center gap-3">
                <input type="color" name="portada_color" value="{{ old('portada_color', $claseVirtual->portada_color) }}"
                       class="w-12 h-10 border border-gray-300 rounded-lg cursor-pointer">
                <div class="flex gap-2 flex-wrap">
                    @foreach(['#3B82F6','#10B981','#F59E0B','#EF4444','#8B5CF6','#EC4899','#14B8A6','#F97316'] as $color)
                        <button type="button"
                                onclick="document.querySelector('[name=portada_color]').value='{{ $color }}'"
                                class="w-8 h-8 rounded-full border-2 border-white shadow-sm hover:scale-110 transition"
                                style="background-color: {{ $color }}"></button>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="flex flex-wrap gap-6">
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" name="activo" value="1" @checked(old('activo', $claseVirtual->activo))
                       class="w-4 h-4 rounded text-blue-600">
                <span class="text-sm text-gray-700 dark:text-gray-300">Aula activa</span>
            </label>
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" name="permite_comentarios" value="1"
                       @checked(old('permite_comentarios', $claseVirtual->permite_comentarios))
                       class="w-4 h-4 rounded text-blue-600">
                <span class="text-sm text-gray-700 dark:text-gray-300">Permitir comentarios</span>
            </label>
        </div>

        <div class="flex justify-end gap-3 pt-2">
            <a href="{{ route('admin.classroom.show', $claseVirtual) }}"
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
