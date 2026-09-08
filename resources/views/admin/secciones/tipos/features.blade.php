@php $in = 'w-full border border-gray-300 dark:border-gray-600 rounded-xl px-4 py-2.5 text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 outline-none'; @endphp

<div class="mb-5">
    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200 mb-1.5">Título de la sección</label>
    <input type="text" name="contenido[titulo]" maxlength="200" class="{{ $in }}"
           value="{{ old('contenido.titulo', $seccion->dato('titulo')) }}" placeholder="Ej: ¿Por qué elegirnos?">
</div>

<p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Características o beneficios destacados. Cada una con su ícono (nombre de <a href="https://icons.getbootstrap.com/" target="_blank" class="text-indigo-600 hover:underline">Bootstrap Icons</a>, ej. <code>bi-mortarboard</code>), título corto y texto.</p>

@include('admin.secciones.tipos._items', ['campos' => [
    ['key' => 'icono',  'label' => 'Ícono', 'placeholder' => 'bi-mortarboard'],
    ['key' => 'titulo', 'label' => 'Título', 'placeholder' => 'Ej: Educación de calidad'],
    ['key' => 'texto',  'label' => 'Texto', 'placeholder' => 'Descripción breve', 'type' => 'textarea'],
]])
