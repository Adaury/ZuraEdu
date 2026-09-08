{{--
    Repetidor Alpine compartido por stats y features. El include debe pasar
    `campos` = [ [key, label, placeholder?], ... ] -- los inputs se nombran
    contenido[items][INDEX][key].
--}}
@php $in = 'w-full border border-gray-300 dark:border-gray-600 rounded-xl px-3 py-2 text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 outline-none'; @endphp

<div x-data="{ items: @js(old('contenido.items', $seccion->dato('items', []))) }">
    <div class="space-y-3 mb-3">
        <template x-for="(it, i) in items" :key="i">
            <div class="flex items-start gap-2 p-3 border border-gray-100 dark:border-gray-700 rounded-xl">
                <div class="flex-1 grid gap-2" style="grid-template-columns: repeat({{ count($campos) }}, minmax(0, 1fr));">
                    @foreach($campos as $campo)
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">{{ $campo['label'] }}</label>
                        @if(($campo['type'] ?? 'text') === 'textarea')
                        <textarea rows="2" class="{{ $in }}" :name="`contenido[items][${i}][{{ $campo['key'] }}]`" x-model="it.{{ $campo['key'] }}"></textarea>
                        @else
                        <input type="text" class="{{ $in }}" :name="`contenido[items][${i}][{{ $campo['key'] }}]`" x-model="it.{{ $campo['key'] }}" placeholder="{{ $campo['placeholder'] ?? '' }}">
                        @endif
                    </div>
                    @endforeach
                </div>
                <button type="button" @click="items.splice(i, 1)" class="text-red-400 hover:text-red-600 p-2 mt-5" title="Quitar">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
        </template>
        <p x-show="items.length === 0" class="text-sm text-gray-400 py-3">Todavía no hay elementos — agrega el primero.</p>
    </div>
    <button type="button"
            @click="items.push({ {{ collect($campos)->map(fn($c) => "{$c['key']}: ''")->implode(', ') }} })"
            class="inline-flex items-center gap-2 px-3 py-2 text-sm font-semibold text-indigo-600 bg-indigo-50 dark:bg-indigo-900/30 dark:text-indigo-300 rounded-xl hover:bg-indigo-100 transition">
        <i class="bi bi-plus-lg"></i>Agregar elemento
    </button>
</div>
