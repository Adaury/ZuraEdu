@extends('layouts.admin')

@section('page-title', 'Editar Encuesta')

@section('content')
<div class="max-w-3xl mx-auto space-y-6"
     x-data="encuestaBuilder()"
     x-init="init()">

    {{-- Encabezado --}}
    <div class="flex items-center gap-3">
        <a href="{{ route('admin.encuestas.show', $encuesta) }}"
           class="p-2 rounded-lg text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-700 dark:hover:text-gray-200 transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Editar Encuesta</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $encuesta->titulo }}</p>
        </div>
    </div>

    @if($errors->any())
        <div class="p-4 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-700 rounded-lg text-red-700 dark:text-red-300 text-sm space-y-1">
            @foreach($errors->all() as $error)
                <p>• {{ $error }}</p>
            @endforeach
        </div>
    @endif

    @if($encuesta->respuestas()->count() > 0)
        <div class="flex items-start gap-3 p-4 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-700 rounded-lg text-amber-800 dark:text-amber-300 text-sm">
            <svg class="w-5 h-5 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
            </svg>
            <div>
                <p class="font-semibold">Esta encuesta ya tiene {{ $encuesta->respuestas()->count() }} respuesta(s) registradas.</p>
                <p class="mt-0.5">Si modificas o eliminas preguntas, las respuestas asociadas se perderán. Solo edita preguntas si es estrictamente necesario.</p>
            </div>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.encuestas.update', $encuesta) }}" @submit.prevent="submitForm">
        @csrf @method('PUT')

        {{-- Datos generales --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 space-y-5">
            <h2 class="text-base font-semibold text-gray-800 dark:text-white">Datos generales</h2>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Título <span class="text-red-500">*</span></label>
                <input type="text" name="titulo" value="{{ old('titulo', $encuesta->titulo) }}" required
                       class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 text-sm bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Descripción</label>
                <textarea name="descripcion" rows="2"
                          class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 text-sm bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-none"
                          placeholder="Descripción breve (opcional)">{{ old('descripcion', $encuesta->descripcion) }}</textarea>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Dirigida a <span class="text-red-500">*</span></label>
                    <select name="dirigida_a" required
                            class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 text-sm bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                        <option value="todos"       {{ old('dirigida_a', $encuesta->dirigida_a) === 'todos'       ? 'selected' : '' }}>Todos</option>
                        <option value="padres"      {{ old('dirigida_a', $encuesta->dirigida_a) === 'padres'      ? 'selected' : '' }}>Padres / Representantes</option>
                        <option value="estudiantes" {{ old('dirigida_a', $encuesta->dirigida_a) === 'estudiantes' ? 'selected' : '' }}>Estudiantes</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Fecha de cierre</label>
                    <input type="date" name="fecha_cierre"
                           value="{{ old('fecha_cierre', $encuesta->fecha_cierre?->format('Y-m-d')) }}"
                           class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 text-sm bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="flex items-end pb-1">
                    <label class="flex items-center gap-2 cursor-pointer select-none">
                        <input type="hidden" name="activo" value="0">
                        <input type="checkbox" name="activo" value="1"
                               {{ old('activo', $encuesta->activo ? '1' : '0') == '1' ? 'checked' : '' }}
                               class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        <span class="text-sm text-gray-700 dark:text-gray-300">Encuesta activa</span>
                    </label>
                </div>
            </div>
        </div>

        {{-- Preguntas --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 space-y-4 mt-4">
            <div class="flex items-center justify-between">
                <h2 class="text-base font-semibold text-gray-800 dark:text-white">Preguntas</h2>
                <button type="button" @click="addPregunta()"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Agregar pregunta
                </button>
            </div>

            <template x-if="preguntas.length === 0">
                <p class="text-sm text-gray-400 dark:text-gray-500 text-center py-6 border-2 border-dashed border-gray-200 dark:border-gray-600 rounded-lg">
                    Haz clic en "Agregar pregunta" para comenzar.
                </p>
            </template>

            <div class="space-y-4">
                <template x-for="(preg, idx) in preguntas" :key="preg.id">
                    <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 space-y-3">
                        <div class="flex items-start gap-3">
                            <span class="mt-2 flex-shrink-0 w-6 h-6 rounded-full bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300 text-xs font-bold flex items-center justify-center"
                                  x-text="idx + 1"></span>
                            <div class="flex-1 space-y-2">
                                <input type="text" :name="`preguntas[${idx}][texto]`" x-model="preg.texto"
                                       required placeholder="Texto de la pregunta..."
                                       class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 text-sm bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                                <select :name="`preguntas[${idx}][tipo]`" x-model="preg.tipo"
                                        class="border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 text-sm bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                                    <option value="opcion_multiple">Opción múltiple</option>
                                    <option value="texto_libre">Texto libre</option>
                                    <option value="escala_1_5">Escala 1–5</option>
                                </select>
                            </div>
                            <button type="button" @click="removePregunta(idx)"
                                    class="mt-1 p-1.5 rounded-lg text-gray-400 hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-900/30 transition flex-shrink-0">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>

                        <template x-if="preg.tipo === 'opcion_multiple'">
                            <div class="ml-9 space-y-2">
                                <template x-for="(op, opIdx) in preg.opciones" :key="opIdx">
                                    <div class="flex items-center gap-2">
                                        <svg class="w-3 h-3 text-gray-400 flex-shrink-0" fill="currentColor" viewBox="0 0 8 8">
                                            <circle cx="4" cy="4" r="4"/>
                                        </svg>
                                        <input type="text"
                                               :name="`preguntas[${idx}][opciones][${opIdx}]`"
                                               x-model="preg.opciones[opIdx]"
                                               placeholder="Opción..."
                                               class="flex-1 border border-gray-200 dark:border-gray-600 rounded-lg px-3 py-1.5 text-sm bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                                        <button type="button" @click="preg.opciones.splice(opIdx, 1)"
                                                class="p-1 text-gray-300 hover:text-red-500 transition">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                        </button>
                                    </div>
                                </template>
                                <button type="button" @click="preg.opciones.push('')"
                                        class="text-xs text-blue-600 hover:text-blue-700 dark:text-blue-400 flex items-center gap-1">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                    </svg>
                                    Agregar opción
                                </button>
                            </div>
                        </template>

                        <template x-if="preg.tipo === 'escala_1_5'">
                            <p class="ml-9 text-xs text-gray-400 dark:text-gray-500">
                                Los encuestados elegirán un valor del 1 (muy malo) al 5 (excelente).
                            </p>
                        </template>

                        <template x-if="preg.tipo === 'texto_libre'">
                            <p class="ml-9 text-xs text-gray-400 dark:text-gray-500">
                                Los encuestados escribirán una respuesta libre.
                            </p>
                        </template>
                    </div>
                </template>
            </div>
        </div>

        {{-- Acciones --}}
        <div class="flex items-center justify-end gap-3 mt-4">
            <a href="{{ route('admin.encuestas.show', $encuesta) }}"
               class="px-4 py-2 text-sm text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                Cancelar
            </a>
            <button type="submit"
                    class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition">
                Guardar cambios
            </button>
        </div>
    </form>
</div>

@push('scripts')
<script>
function encuestaBuilder() {
    return {
        preguntas: [],
        counter: 0,

        init() {
            // Precargar preguntas existentes desde el servidor
            const existentes = @json($encuesta->preguntas->map(fn($p) => [
                'texto'   => $p->texto,
                'tipo'    => $p->tipo,
                'opciones'=> $p->opciones->pluck('texto')->toArray(),
            ]));

            existentes.forEach(p => {
                this.preguntas.push({
                    id:      ++this.counter,
                    texto:   p.texto,
                    tipo:    p.tipo,
                    opciones: p.opciones.length ? p.opciones : ['', ''],
                });
            });

            // Si hubo error de validación, sobreescribir con old()
            @if(old('preguntas'))
            this.preguntas = [];
            this.counter   = 0;
            const old = @json(old('preguntas', []));
            old.forEach(p => {
                this.preguntas.push({
                    id:      ++this.counter,
                    texto:   p.texto ?? '',
                    tipo:    p.tipo  ?? 'opcion_multiple',
                    opciones: p.opciones ? Object.values(p.opciones) : ['', ''],
                });
            });
            @endif
        },

        addPregunta() {
            this.preguntas.push({
                id:       ++this.counter,
                texto:    '',
                tipo:     'opcion_multiple',
                opciones: ['', ''],
            });
        },

        removePregunta(idx) {
            this.preguntas.splice(idx, 1);
        },

        submitForm(e) {
            if (this.preguntas.length === 0) {
                alert('Debes tener al menos una pregunta.');
                return;
            }
            e.target.submit();
        },
    };
}
</script>
@endpush
@endsection
