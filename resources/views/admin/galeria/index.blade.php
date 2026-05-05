@extends('layouts.admin')

@section('page-title', 'Galería Institucional')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    {{-- Encabezado --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
        <div>
            <h1 class="text-2xl font-extrabold text-gray-900 dark:text-white tracking-tight flex items-center gap-2">
                <i class="bi bi-images text-indigo-600 dark:text-indigo-400"></i>
                Galería Institucional
            </h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Administra los álbumes y fotos del centro educativo</p>
        </div>
        <a href="{{ route('admin.galeria.create') }}"
           class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold px-4 py-2.5 rounded-xl shadow transition">
            <i class="bi bi-plus-lg"></i>Nuevo álbum
        </a>
    </div>

    {{-- Mensajes --}}
    @if(session('success'))
    <div class="mb-6 flex items-center gap-3 bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-700 text-green-800 dark:text-green-300 rounded-xl px-4 py-3 text-sm font-medium">
        <i class="bi bi-check-circle-fill text-green-500 flex-shrink-0"></i>
        {{ session('success') }}
    </div>
    @endif

    @if($albumes->isEmpty())
    {{-- Estado vacío --}}
    <div class="text-center py-20 bg-white dark:bg-gray-800 rounded-2xl border border-dashed border-gray-300 dark:border-gray-600 shadow-sm">
        <div class="w-16 h-16 bg-indigo-50 dark:bg-indigo-900/30 rounded-2xl flex items-center justify-center mx-auto mb-4">
            <i class="bi bi-images text-indigo-400 text-3xl"></i>
        </div>
        <h3 class="text-lg font-bold text-gray-700 dark:text-gray-200 mb-1">Sin álbumes todavía</h3>
        <p class="text-sm text-gray-400 mb-6">Crea el primer álbum para comenzar a subir fotos.</p>
        <a href="{{ route('admin.galeria.create') }}"
           class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold px-5 py-2.5 rounded-xl transition">
            <i class="bi bi-plus-lg"></i>Crear álbum
        </a>
    </div>
    @else
    {{-- Grid de álbumes --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
        @foreach($albumes as $album)
        <div class="group bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden hover:shadow-lg transition-shadow duration-200">

            {{-- Portada --}}
            <a href="{{ route('admin.galeria.show', $album) }}" class="block relative aspect-[4/3] overflow-hidden bg-gray-100 dark:bg-gray-700">
                @if($album->portada_url)
                    <img src="{{ $album->portada_url }}"
                         alt="{{ $album->titulo }}"
                         class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                @else
                    <div class="w-full h-full flex flex-col items-center justify-center text-gray-400 dark:text-gray-500">
                        <i class="bi bi-card-image text-4xl mb-2"></i>
                        <span class="text-xs">Sin portada</span>
                    </div>
                @endif

                {{-- Badge activo/inactivo --}}
                <div class="absolute top-2 right-2">
                    @if($album->activo)
                        <span class="bg-green-500 text-white text-xs font-bold px-2 py-0.5 rounded-full shadow">Activo</span>
                    @else
                        <span class="bg-gray-500 text-white text-xs font-bold px-2 py-0.5 rounded-full shadow">Inactivo</span>
                    @endif
                </div>
            </a>

            {{-- Info --}}
            <div class="p-4">
                <h3 class="font-bold text-gray-900 dark:text-white truncate text-sm">{{ $album->titulo }}</h3>
                @if($album->descripcion)
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 line-clamp-2">{{ $album->descripcion }}</p>
                @endif
                <div class="flex items-center justify-between mt-3">
                    <span class="text-xs text-indigo-600 dark:text-indigo-400 font-semibold flex items-center gap-1">
                        <i class="bi bi-image"></i>
                        {{ $album->fotos_count }} foto{{ $album->fotos_count !== 1 ? 's' : '' }}
                    </span>
                    <div class="flex items-center gap-1">
                        <a href="{{ route('admin.galeria.show', $album) }}"
                           class="p-1.5 text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 rounded-lg transition"
                           title="Ver fotos">
                            <i class="bi bi-eye text-sm"></i>
                        </a>
                        <a href="{{ route('admin.galeria.edit', $album) }}"
                           class="p-1.5 text-gray-400 hover:text-amber-600 hover:bg-amber-50 dark:hover:bg-amber-900/30 rounded-lg transition"
                           title="Editar">
                            <i class="bi bi-pencil text-sm"></i>
                        </a>
                        <form action="{{ route('admin.galeria.destroy', $album) }}" method="POST"
                              onsubmit="return confirm('¿Eliminar el álbum «{{ addslashes($album->titulo) }}» y todas sus fotos?')">
                            @csrf @method('DELETE')
                            <button type="submit"
                                    class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/30 rounded-lg transition"
                                    title="Eliminar">
                                <i class="bi bi-trash text-sm"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    {{-- Paginación --}}
    @if($albumes->hasPages())
    <div class="mt-8">
        {{ $albumes->links() }}
    </div>
    @endif
    @endif
</div>
@endsection
