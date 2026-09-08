@php $in = 'w-full border border-gray-300 dark:border-gray-600 rounded-xl px-4 py-2.5 text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 outline-none'; @endphp

<div class="mb-5">
    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200 mb-1.5">Título</label>
    <input type="text" name="contenido[titulo]" maxlength="200" class="{{ $in }}"
           value="{{ old('contenido.titulo', $seccion->dato('titulo')) }}"
           placeholder="{{ $seccion->tipo === 'hero' ? config('tenant.nombre', 'Nombre de tu institución') : '' }}">
    <p class="text-xs text-gray-400 mt-1">Si lo dejas vacío, se usa el nombre de la institución.</p>
</div>

<div class="mb-5">
    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200 mb-1.5">Subtítulo</label>
    <textarea name="contenido[subtitulo]" rows="2" maxlength="500" class="{{ $in }}">{{ old('contenido.subtitulo', $seccion->dato('subtitulo')) }}</textarea>
</div>

<div class="grid grid-cols-2 gap-4 mb-5">
    <div>
        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200 mb-1.5">Botón 1 — texto</label>
        <input type="text" name="contenido[btn_texto]" maxlength="80" class="{{ $in }}"
               value="{{ old('contenido.btn_texto', $seccion->dato('btn_texto')) }}" placeholder="Ej: Iniciar sesión">
    </div>
    <div>
        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200 mb-1.5">Botón 1 — enlace</label>
        <input type="text" name="contenido[btn_url]" maxlength="255" class="{{ $in }}"
               value="{{ old('contenido.btn_url', $seccion->dato('btn_url')) }}" placeholder="Vacío = página de acceso">
    </div>
</div>

<div class="grid grid-cols-2 gap-4">
    <div>
        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200 mb-1.5">Botón 2 — texto</label>
        <input type="text" name="contenido[btn2_texto]" maxlength="80" class="{{ $in }}"
               value="{{ old('contenido.btn2_texto', $seccion->dato('btn2_texto')) }}" placeholder="Ej: Inscríbete">
    </div>
    <div>
        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200 mb-1.5">Botón 2 — enlace</label>
        <input type="text" name="contenido[btn2_url]" maxlength="255" class="{{ $in }}"
               value="{{ old('contenido.btn2_url', $seccion->dato('btn2_url')) }}" placeholder="Vacío = página de inscripción">
    </div>
</div>
