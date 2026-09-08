@php $in = 'w-full border border-gray-300 dark:border-gray-600 rounded-xl px-4 py-2.5 text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 outline-none'; @endphp

<div class="mb-2">
    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200 mb-1.5">Álbum</label>
    <select name="contenido[album_id]" class="{{ $in }}">
        <option value="">-- Elige un álbum --</option>
        @foreach($albumes as $album)
            <option value="{{ $album->id }}" @selected(old('contenido.album_id', $seccion->dato('album_id')) == $album->id)>
                {{ $album->titulo }} ({{ $album->fotos->count() }} fotos)
            </option>
        @endforeach
    </select>
    <p class="text-xs text-gray-400 mt-1">
        Necesita al menos {{ \App\Models\Album::MIN_FOTOS_CARRUSEL }} fotos marcadas para mostrarse.
        Las fotos se administran en <a href="{{ route('admin.galeria.index') }}" class="text-indigo-600 hover:underline">Galería</a>.
    </p>
</div>
