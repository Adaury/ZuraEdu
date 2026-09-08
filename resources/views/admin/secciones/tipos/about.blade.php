@php $in = 'w-full border border-gray-300 dark:border-gray-600 rounded-xl px-4 py-2.5 text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 outline-none'; @endphp

<div class="mb-5">
    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200 mb-1.5">Título</label>
    <input type="text" name="contenido[titulo]" maxlength="200" class="{{ $in }}"
           value="{{ old('contenido.titulo', $seccion->dato('titulo')) }}" placeholder="Ej: Sobre nosotros">
</div>

<div class="mb-2">
    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200 mb-1.5">Texto</label>
    {{-- La clave 'texto' está en SanitizeInput::$allowedRichFields, por eso
         el HTML que produce Quill sobrevive aquí (no pasaría con 'titulo'). --}}
    <div id="contenido-editor" class="bg-white dark:bg-gray-700 rounded-xl"></div>
    <textarea id="contenido-input" name="contenido[texto]" class="hidden">{{ old('contenido.texto', $seccion->dato('texto')) }}</textarea>
</div>

@push('scripts')
    @vite('resources/js/publicaciones-editor.js')
@endpush
