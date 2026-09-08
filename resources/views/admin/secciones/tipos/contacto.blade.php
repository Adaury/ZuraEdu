@php $in = 'w-full border border-gray-300 dark:border-gray-600 rounded-xl px-4 py-2.5 text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 outline-none'; @endphp

<div class="mb-5">
    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200 mb-1.5">Título de la sección</label>
    <input type="text" name="contenido[titulo]" maxlength="200" class="{{ $in }}"
           value="{{ old('contenido.titulo', $seccion->dato('titulo')) }}" placeholder="Contacto">
</div>

<div class="mb-5">
    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200 mb-1.5">Dirección</label>
    <input type="text" name="contenido[direccion]" maxlength="200" class="{{ $in }}"
           value="{{ old('contenido.direccion', $seccion->dato('direccion')) }}">
</div>

<div class="grid grid-cols-2 gap-4 mb-5">
    <div>
        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200 mb-1.5">Teléfono</label>
        <input type="text" name="contenido[telefono]" maxlength="50" class="{{ $in }}"
               value="{{ old('contenido.telefono', $seccion->dato('telefono')) }}">
    </div>
    <div>
        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200 mb-1.5">Correo</label>
        <input type="email" name="contenido[email]" maxlength="100" class="{{ $in }}"
               value="{{ old('contenido.email', $seccion->dato('email')) }}">
    </div>
</div>

<div class="grid grid-cols-3 gap-4">
    <div>
        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200 mb-1.5">Facebook</label>
        <input type="text" name="contenido[facebook]" maxlength="255" class="{{ $in }}"
               value="{{ old('contenido.facebook', $seccion->dato('facebook')) }}" placeholder="URL">
    </div>
    <div>
        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200 mb-1.5">Instagram</label>
        <input type="text" name="contenido[instagram]" maxlength="255" class="{{ $in }}"
               value="{{ old('contenido.instagram', $seccion->dato('instagram')) }}" placeholder="URL">
    </div>
    <div>
        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200 mb-1.5">Twitter / X</label>
        <input type="text" name="contenido[twitter]" maxlength="255" class="{{ $in }}"
               value="{{ old('contenido.twitter', $seccion->dato('twitter')) }}" placeholder="URL">
    </div>
</div>
