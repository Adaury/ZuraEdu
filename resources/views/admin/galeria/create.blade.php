@extends('layouts.admin')

@section('page-title', isset($album) ? 'Editar álbum' : 'Nuevo álbum')

@section('content')
<div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    {{-- Encabezado --}}
    <div class="flex items-center gap-3 mb-8">
        <a href="{{ isset($album) ? route('admin.galeria.show', $album) : route('admin.galeria.index') }}"
           class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-xl transition">
            <i class="bi bi-arrow-left text-lg"></i>
        </a>
        <div>
            <h1 class="text-2xl font-extrabold text-gray-900 dark:text-white tracking-tight">
                {{ isset($album) ? 'Editar álbum' : 'Nuevo álbum' }}
            </h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                {{ isset($album) ? "Modificar «{$album->titulo}»" : 'Crea un nuevo álbum de fotos' }}
            </p>
        </div>
    </div>

    {{-- Formulario --}}
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-6"
         x-data="albumForm()">

        <form action="{{ isset($album) ? route('admin.galeria.update', $album) : route('admin.galeria.store') }}"
              method="POST" enctype="multipart/form-data">
            @csrf
            @if(isset($album)) @method('PUT') @endif

            {{-- Título --}}
            <div class="mb-5">
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200 mb-1.5" for="titulo">
                    Título <span class="text-red-500">*</span>
                </label>
                <input type="text" id="titulo" name="titulo"
                       value="{{ old('titulo', $album->titulo ?? '') }}"
                       required maxlength="255"
                       class="w-full border border-gray-300 dark:border-gray-600 rounded-xl px-4 py-2.5 text-sm
                              bg-white dark:bg-gray-700 text-gray-900 dark:text-white
                              focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition
                              @error('titulo') border-red-400 @enderror"
                       placeholder="Ej: Día del Maestro 2026">
                @error('titulo')
                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                @enderror
            </div>

            {{-- Descripción --}}
            <div class="mb-5">
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200 mb-1.5" for="descripcion">
                    Descripción <span class="text-gray-400 font-normal">(opcional)</span>
                </label>
                <textarea id="descripcion" name="descripcion" rows="3" maxlength="1000"
                          class="w-full border border-gray-300 dark:border-gray-600 rounded-xl px-4 py-2.5 text-sm
                                 bg-white dark:bg-gray-700 text-gray-900 dark:text-white resize-none
                                 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition
                                 @error('descripcion') border-red-400 @enderror"
                          placeholder="Breve descripción del álbum…">{{ old('descripcion', $album->descripcion ?? '') }}</textarea>
                @error('descripcion')
                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                @enderror
            </div>

            {{-- Portada --}}
            <div class="mb-5">
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200 mb-1.5">
                    Imagen de portada <span class="text-gray-400 font-normal">(opcional · máx. 2 MB)</span>
                </label>

                {{-- Preview portada actual --}}
                @if(isset($album) && $album->portada)
                <div class="mb-3">
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-1.5">Portada actual:</p>
                    <img src="{{ $album->portada_url }}" alt="Portada"
                         class="w-32 h-24 object-cover rounded-xl border border-gray-200 dark:border-gray-600">
                </div>
                @endif

                <div class="relative border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-xl p-5 text-center
                            hover:border-indigo-400 transition cursor-pointer"
                     @click="$refs.portadaInput.click()">
                    <template x-if="!portadaPreview">
                        <div class="text-gray-400">
                            <i class="bi bi-image text-3xl block mb-2"></i>
                            <span class="text-sm">Haz clic o arrastra una imagen aquí</span>
                            <span class="block text-xs mt-1">JPG, PNG, WEBP</span>
                        </div>
                    </template>
                    <template x-if="portadaPreview">
                        <div>
                            <img :src="portadaPreview" class="h-32 object-cover rounded-lg mx-auto">
                            <p class="text-xs text-gray-500 mt-2" x-text="portadaNombre"></p>
                        </div>
                    </template>
                    <input type="file" name="portada" accept="image/*" class="hidden"
                           x-ref="portadaInput"
                           @change="onPortadaChange($event)">
                </div>
                @error('portada')
                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                @enderror
            </div>

            {{-- Orden y estado en fila --}}
            <div class="grid grid-cols-2 gap-4 mb-6">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200 mb-1.5" for="orden">
                        Orden
                    </label>
                    <input type="number" id="orden" name="orden" min="0"
                           value="{{ old('orden', $album->orden ?? 0) }}"
                           class="w-full border border-gray-300 dark:border-gray-600 rounded-xl px-4 py-2.5 text-sm
                                  bg-white dark:bg-gray-700 text-gray-900 dark:text-white
                                  focus:ring-2 focus:ring-indigo-500 outline-none transition">
                </div>
                <div class="flex flex-col justify-center">
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200 mb-1.5">Estado</label>
                    <label class="flex items-center gap-3 cursor-pointer mt-1">
                        <div class="relative" x-data="{ on: {{ old('activo', ($album->activo ?? true) ? 'true' : 'false') ? 'true' : 'false' }} }">
                            <input type="hidden" name="activo" :value="on ? '1' : '0'">
                            <button type="button" @click="on = !on"
                                    :class="on ? 'bg-indigo-600' : 'bg-gray-300 dark:bg-gray-600'"
                                    class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-1">
                                <span :class="on ? 'translate-x-6' : 'translate-x-1'"
                                      class="inline-block h-4 w-4 transform rounded-full bg-white shadow transition-transform"></span>
                            </button>
                        </div>
                        <span class="text-sm text-gray-600 dark:text-gray-300">Álbum activo (visible en la web)</span>
                    </label>
                </div>
            </div>

            {{-- Botones --}}
            <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-100 dark:border-gray-700">
                <a href="{{ isset($album) ? route('admin.galeria.show', $album) : route('admin.galeria.index') }}"
                   class="px-4 py-2.5 text-sm font-semibold text-gray-600 dark:text-gray-300 bg-gray-100 dark:bg-gray-700
                          hover:bg-gray-200 dark:hover:bg-gray-600 rounded-xl transition">
                    Cancelar
                </a>
                <button type="submit"
                        class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-semibold text-white
                               bg-indigo-600 hover:bg-indigo-700 rounded-xl shadow transition">
                    <i class="bi bi-{{ isset($album) ? 'check-lg' : 'plus-lg' }}"></i>
                    {{ isset($album) ? 'Guardar cambios' : 'Crear álbum' }}
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function albumForm() {
    return {
        portadaPreview: null,
        portadaNombre: '',
        onPortadaChange(event) {
            const file = event.target.files[0];
            if (!file) return;
            this.portadaNombre = file.name;
            const reader = new FileReader();
            reader.onload = e => { this.portadaPreview = e.target.result; };
            reader.readAsDataURL(file);
        }
    };
}
</script>
@endpush
@endsection
