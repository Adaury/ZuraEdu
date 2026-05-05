@extends('layouts.admin')

@section('page-title', $album->titulo . ' — Galería')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    {{-- Encabezado --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
        <div class="flex items-start gap-3">
            <a href="{{ route('admin.galeria.index') }}"
               class="mt-1 p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-xl transition">
                <i class="bi bi-arrow-left text-lg"></i>
            </a>
            <div>
                <div class="flex items-center gap-2 flex-wrap">
                    <h1 class="text-2xl font-extrabold text-gray-900 dark:text-white tracking-tight">
                        {{ $album->titulo }}
                    </h1>
                    @if($album->activo)
                        <span class="bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300 text-xs font-bold px-2.5 py-0.5 rounded-full">Activo</span>
                    @else
                        <span class="bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400 text-xs font-bold px-2.5 py-0.5 rounded-full">Inactivo</span>
                    @endif
                </div>
                @if($album->descripcion)
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">{{ $album->descripcion }}</p>
                @endif
                <p class="text-xs text-gray-400 mt-1">
                    <i class="bi bi-image me-1"></i>{{ $album->fotos->count() }} foto{{ $album->fotos->count() !== 1 ? 's' : '' }} en este álbum
                </p>
            </div>
        </div>
        <div class="flex items-center gap-2 flex-shrink-0">
            <a href="{{ route('admin.galeria.edit', $album) }}"
               class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-semibold text-amber-700 dark:text-amber-400
                      bg-amber-50 dark:bg-amber-900/30 hover:bg-amber-100 dark:hover:bg-amber-900/50 rounded-xl transition border border-amber-200 dark:border-amber-700">
                <i class="bi bi-pencil"></i>Editar álbum
            </a>
            <form action="{{ route('admin.galeria.destroy', $album) }}" method="POST"
                  onsubmit="return confirm('¿Eliminar el álbum y todas sus fotos? Esta acción no se puede deshacer.')">
                @csrf @method('DELETE')
                <button type="submit"
                        class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-semibold text-red-700 dark:text-red-400
                               bg-red-50 dark:bg-red-900/30 hover:bg-red-100 dark:hover:bg-red-900/50 rounded-xl transition border border-red-200 dark:border-red-700">
                    <i class="bi bi-trash"></i>Eliminar
                </button>
            </form>
        </div>
    </div>

    {{-- Mensajes --}}
    @if(session('success'))
    <div class="mb-6 flex items-center gap-3 bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-700
                text-green-800 dark:text-green-300 rounded-xl px-4 py-3 text-sm font-medium">
        <i class="bi bi-check-circle-fill text-green-500 flex-shrink-0"></i>
        {{ session('success') }}
    </div>
    @endif

    {{-- Panel subir fotos --}}
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-6 mb-8"
         x-data="subirFotos()">

        <h2 class="text-base font-bold text-gray-800 dark:text-white mb-4 flex items-center gap-2">
            <i class="bi bi-cloud-upload text-indigo-500"></i>
            Subir nuevas fotos
        </h2>

        <form action="{{ route('admin.galeria.fotos.subir', $album) }}" method="POST"
              enctype="multipart/form-data" @submit="submitting = true">
            @csrf

            {{-- Drop zone --}}
            <div class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-xl p-6 text-center
                        hover:border-indigo-400 transition cursor-pointer mb-4"
                 @click="$refs.fotosInput.click()"
                 @dragover.prevent
                 @drop.prevent="onDrop($event)">
                <template x-if="previews.length === 0">
                    <div class="text-gray-400">
                        <i class="bi bi-images text-4xl block mb-2"></i>
                        <p class="text-sm font-medium">Haz clic o arrastra fotos aquí</p>
                        <p class="text-xs mt-1">JPG, PNG, WEBP · Máx. 2 MB por foto · Selección múltiple</p>
                    </div>
                </template>
                <template x-if="previews.length > 0">
                    <div>
                        <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-6 gap-3 mb-3">
                            <template x-for="(p, i) in previews" :key="i">
                                <div class="relative group">
                                    <img :src="p.url" class="w-full aspect-square object-cover rounded-lg border border-gray-200 dark:border-gray-600">
                                    <button type="button" @click.stop="removePreview(i)"
                                            class="absolute top-1 right-1 bg-red-500 text-white rounded-full w-5 h-5 text-xs
                                                   flex items-center justify-center opacity-0 group-hover:opacity-100 transition shadow">
                                        <i class="bi bi-x"></i>
                                    </button>
                                    <div class="absolute bottom-0 left-0 right-0 bg-black/50 rounded-b-lg px-1 py-0.5" @click.stop>
                                        <input type="text" :name="'titulos['+i+']'"
                                               class="w-full bg-transparent text-white text-xs placeholder-gray-300 outline-none"
                                               placeholder="Título…">
                                    </div>
                                </div>
                            </template>
                        </div>
                        <p class="text-xs text-indigo-600 dark:text-indigo-400 font-semibold" x-text="previews.length + ' foto(s) seleccionada(s)'"></p>
                    </div>
                </template>
                <input type="file" name="fotos[]" multiple accept="image/*" class="hidden"
                       x-ref="fotosInput"
                       @change="onFileChange($event)">
            </div>

            @error('fotos')
                <p class="mb-3 text-xs text-red-500">{{ $message }}</p>
            @enderror
            @error('fotos.*')
                <p class="mb-3 text-xs text-red-500">{{ $message }}</p>
            @enderror

            <div class="flex items-center justify-between">
                <button type="button" x-show="previews.length > 0" @click="clearAll()"
                        class="text-sm text-gray-500 hover:text-red-600 transition">
                    <i class="bi bi-x-circle me-1"></i>Limpiar selección
                </button>
                <button type="submit"
                        x-bind:disabled="previews.length === 0 || submitting"
                        class="ml-auto inline-flex items-center gap-2 px-5 py-2.5 text-sm font-semibold text-white
                               bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed
                               rounded-xl shadow transition">
                    <template x-if="!submitting">
                        <span class="flex items-center gap-2"><i class="bi bi-cloud-upload"></i>Subir fotos</span>
                    </template>
                    <template x-if="submitting">
                        <span class="flex items-center gap-2">
                            <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                            </svg>
                            Subiendo…
                        </span>
                    </template>
                </button>
            </div>
        </form>
    </div>

    {{-- Grid de fotos --}}
    @if($album->fotos->isEmpty())
    <div class="text-center py-16 bg-white dark:bg-gray-800 rounded-2xl border border-dashed border-gray-200 dark:border-gray-700">
        <i class="bi bi-card-image text-4xl text-gray-300 dark:text-gray-600 block mb-3"></i>
        <p class="text-sm text-gray-500 dark:text-gray-400">Este álbum aún no tiene fotos. ¡Sube las primeras!</p>
    </div>
    @else
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-6">
        <h2 class="text-base font-bold text-gray-800 dark:text-white mb-5 flex items-center gap-2">
            <i class="bi bi-grid text-gray-500"></i>
            Fotos del álbum
            <span class="text-xs font-medium text-gray-400 ml-1">({{ $album->fotos->count() }})</span>
        </h2>

        {{-- Lightbox simple --}}
        <div x-data="lightbox()" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-3">

            @foreach($album->fotos as $foto)
            <div class="group relative aspect-square rounded-xl overflow-hidden bg-gray-100 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 hover:shadow-lg transition-shadow">
                <img src="{{ $foto->url }}"
                     alt="{{ $foto->titulo ?? 'Foto' }}"
                     class="w-full h-full object-cover cursor-pointer group-hover:scale-105 transition-transform duration-200"
                     @click="open('{{ $foto->url }}', '{{ addslashes($foto->titulo ?? '') }}')">

                {{-- Overlay con acciones --}}
                <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex flex-col justify-between p-2">
                    <div class="flex justify-end">
                        <form action="{{ route('admin.galeria.fotos.eliminar', [$album, $foto]) }}"
                              method="POST"
                              onsubmit="return confirm('¿Eliminar esta foto?')">
                            @csrf @method('DELETE')
                            <button type="submit"
                                    class="bg-red-500 hover:bg-red-600 text-white rounded-full w-7 h-7 flex items-center justify-center shadow transition text-xs">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                    </div>
                    @if($foto->titulo)
                    <div class="text-white text-xs font-medium truncate bg-black/50 rounded px-1.5 py-0.5">
                        {{ $foto->titulo }}
                    </div>
                    @endif
                </div>
            </div>
            @endforeach

            {{-- Lightbox modal --}}
            <div x-show="visible" x-cloak
                 class="fixed inset-0 z-50 bg-black/90 flex items-center justify-center p-4"
                 @click.self="close()" @keydown.escape.window="close()">
                <div class="relative max-w-5xl max-h-full">
                    <img :src="currentSrc" :alt="currentAlt"
                         class="max-w-full max-h-[85vh] object-contain rounded-xl shadow-2xl">
                    <p x-show="currentAlt" x-text="currentAlt"
                       class="text-white text-sm text-center mt-3 font-medium"></p>
                    <button @click="close()"
                            class="absolute -top-3 -right-3 bg-white text-gray-900 rounded-full w-8 h-8
                                   flex items-center justify-center font-bold shadow-lg hover:bg-gray-100 transition">
                        <i class="bi bi-x text-lg"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

@push('scripts')
<script>
function subirFotos() {
    return {
        previews: [],
        files: [],
        submitting: false,
        onFileChange(event) {
            this.addFiles(event.target.files);
        },
        onDrop(event) {
            this.addFiles(event.dataTransfer.files);
        },
        addFiles(fileList) {
            Array.from(fileList).forEach(file => {
                if (!file.type.startsWith('image/')) return;
                const reader = new FileReader();
                reader.onload = e => {
                    this.previews.push({ url: e.target.result, name: file.name });
                };
                reader.readAsDataURL(file);
                this.files.push(file);
            });
            // Sincronizar el input nativo con un DataTransfer
            this.syncInput();
        },
        removePreview(index) {
            this.previews.splice(index, 1);
            this.files.splice(index, 1);
            this.syncInput();
        },
        clearAll() {
            this.previews = [];
            this.files = [];
            this.$refs.fotosInput.value = '';
        },
        syncInput() {
            const dt = new DataTransfer();
            this.files.forEach(f => dt.items.add(f));
            this.$refs.fotosInput.files = dt.files;
        }
    };
}

function lightbox() {
    return {
        visible: false,
        currentSrc: '',
        currentAlt: '',
        open(src, alt) {
            this.currentSrc = src;
            this.currentAlt = alt;
            this.visible = true;
            document.body.style.overflow = 'hidden';
        },
        close() {
            this.visible = false;
            document.body.style.overflow = '';
        }
    };
}
</script>
@endpush
@endsection
