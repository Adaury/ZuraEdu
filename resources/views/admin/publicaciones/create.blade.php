@extends('layouts.admin')

@section('page-title', isset($publicacion) ? 'Editar Publicación' : 'Nueva Publicación')

@section('content')
<div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    <div class="flex items-center gap-3 mb-8">
        <a href="{{ route('admin.publicaciones.index') }}"
           class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-xl transition">
            <i class="bi bi-arrow-left text-lg"></i>
        </a>
        <div>
            <h1 class="text-2xl font-extrabold text-gray-900 dark:text-white tracking-tight">
                {{ isset($publicacion) ? 'Editar Publicación' : 'Nueva Publicación' }}
            </h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Se mostrará en el sitio público del centro.</p>
        </div>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-6">
        <form action="{{ isset($publicacion) ? route('admin.publicaciones.update', $publicacion) : route('admin.publicaciones.store') }}"
              method="POST" enctype="multipart/form-data">
            @csrf
            @if(isset($publicacion)) @method('PUT') @endif

            <div class="grid grid-cols-2 gap-4 mb-5">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200 mb-1.5">Tipo <span class="text-red-500">*</span></label>
                    <select name="tipo" required
                            class="w-full border border-gray-300 dark:border-gray-600 rounded-xl px-4 py-2.5 text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 outline-none">
                        @foreach(\App\Models\Publicacion::TIPOS as $key => $label)
                            <option value="{{ $key }}" @selected(old('tipo', $publicacion->tipo ?? '') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('tipo')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200 mb-1.5">Fecha <span class="text-red-500">*</span></label>
                    <input type="date" name="fecha" required
                           value="{{ old('fecha', isset($publicacion) ? $publicacion->fecha->format('Y-m-d') : now()->format('Y-m-d')) }}"
                           class="w-full border border-gray-300 dark:border-gray-600 rounded-xl px-4 py-2.5 text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 outline-none">
                    @error('fecha')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="mb-5">
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200 mb-1.5">Título <span class="text-red-500">*</span></label>
                <input type="text" name="titulo" required maxlength="200"
                       value="{{ old('titulo', $publicacion->titulo ?? '') }}"
                       class="w-full border border-gray-300 dark:border-gray-600 rounded-xl px-4 py-2.5 text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 outline-none @error('titulo') border-red-400 @enderror"
                       placeholder="Ej: Feria de Ciencias 2026">
                @error('titulo')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
            </div>

            <div class="mb-5">
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200 mb-1.5">Contenido <span class="text-red-500">*</span></label>
                <div id="contenido-editor" class="bg-white dark:bg-gray-700 rounded-xl @error('contenido') ring-2 ring-red-400 @enderror"></div>
                <textarea id="contenido-input" name="contenido" required
                          class="hidden">{{ old('contenido', $publicacion->contenido ?? '') }}</textarea>
                @error('contenido')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
            </div>

            <div class="mb-5">
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200 mb-1.5">
                    Imagen destacada <span class="text-gray-400 font-normal">(opcional · máx. 2 MB)</span>
                </label>
                @if(isset($publicacion) && $publicacion->imagen_destacada)
                <div class="mb-3">
                    <img src="{{ $publicacion->imagen_url }}" alt="" class="w-40 h-24 object-cover rounded-xl border border-gray-200 dark:border-gray-600">
                </div>
                @endif
                <input type="file" name="imagen_destacada" accept="image/*"
                       class="w-full text-sm text-gray-600 dark:text-gray-300 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 dark:file:bg-indigo-900/30 dark:file:text-indigo-300">
                @error('imagen_destacada')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror

                <div class="flex items-center gap-4 mt-3">
                    @foreach(['izquierda' => 'Izquierda', 'centro' => 'Centro', 'derecha' => 'Derecha'] as $valor => $etiqueta)
                    <label class="flex items-center gap-1.5 text-sm text-gray-600 dark:text-gray-300 cursor-pointer">
                        <input type="radio" name="imagen_alineacion" value="{{ $valor }}"
                               @checked(old('imagen_alineacion', $publicacion->imagen_alineacion ?? 'centro') === $valor)
                               class="text-indigo-600 focus:ring-indigo-500">
                        {{ $etiqueta }}
                    </label>
                    @endforeach
                </div>
                @error('imagen_alineacion')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
            </div>

            <div class="grid grid-cols-2 gap-4 mb-6">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200 mb-1.5">Estado</label>
                    <select name="estado"
                            class="w-full border border-gray-300 dark:border-gray-600 rounded-xl px-4 py-2.5 text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 outline-none">
                        <option value="borrador" @selected(old('estado', $publicacion->estado ?? 'borrador') === 'borrador')>Borrador</option>
                        <option value="publicado" @selected(old('estado', $publicacion->estado ?? '') === 'publicado')>Publicado</option>
                    </select>
                    <p class="text-xs text-gray-400 mt-1">Solo "Publicado" se muestra en el sitio público.</p>
                </div>
                <div class="flex flex-col justify-center">
                    <label class="flex items-center gap-3 cursor-pointer mt-6">
                        <input type="checkbox" name="visible" value="1"
                               @checked(old('visible', $publicacion->visible ?? true))
                               class="w-5 h-5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                        <span class="text-sm text-gray-600 dark:text-gray-300">Visible en el sitio público</span>
                    </label>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-100 dark:border-gray-700">
                <a href="{{ route('admin.publicaciones.index') }}"
                   class="px-4 py-2.5 text-sm font-semibold text-gray-600 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-xl transition">
                    Cancelar
                </a>
                <button type="submit"
                        class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-xl shadow transition">
                    <i class="bi bi-{{ isset($publicacion) ? 'check-lg' : 'plus-lg' }}"></i>
                    {{ isset($publicacion) ? 'Guardar cambios' : 'Crear publicación' }}
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
    @vite('resources/js/publicaciones-editor.js')
@endpush
@endsection
