@php $in = 'w-full border border-gray-300 dark:border-gray-600 rounded-xl px-4 py-2.5 text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 outline-none'; @endphp

<div class="mb-5">
    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200 mb-1.5">Título de la sección</label>
    <input type="text" name="contenido[titulo]" maxlength="200" class="{{ $in }}"
           value="{{ old('contenido.titulo', $seccion->dato('titulo')) }}" placeholder="Noticias y Publicaciones">
</div>

<div>
    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200 mb-1.5">Cantidad a mostrar</label>
    <input type="number" name="contenido[limite]" min="1" max="{{ \App\Models\PaginaSeccion::LIMITE_NOTICIAS_MAX }}" class="{{ $in }}"
           value="{{ old('contenido.limite', $seccion->dato('limite', 6)) }}">
    <p class="text-xs text-gray-400 mt-1">
        Muestra las publicaciones más recientes marcadas como "Publicado" y "Visible". Se administran en
        <a href="{{ route('admin.publicaciones.index') }}" class="text-indigo-600 hover:underline">Noticias y Publicaciones</a>.
    </p>
</div>
