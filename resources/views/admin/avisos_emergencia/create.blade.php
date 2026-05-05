@extends('layouts.admin')

@section('page-title', 'Nuevo Aviso de Emergencia')

@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-6">

    {{-- Cabecera --}}
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('admin.avisos-emergencia.index') }}"
           class="p-2 rounded-lg text-gray-500 hover:text-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 transition">
            <i class="bi bi-arrow-left text-lg"></i>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                <i class="bi bi-megaphone-fill text-red-500"></i>
                Nuevo Aviso de Emergencia
            </h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                Se enviará como notificación en la plataforma. Si es emergencia o suspensión, también se enviará por WhatsApp a los representantes.
            </p>
        </div>
    </div>

    {{-- Errores --}}
    @if($errors->any())
        <div class="mb-5 p-4 rounded-xl bg-red-50 border border-red-200 text-red-700 dark:bg-red-900/20 dark:border-red-700 dark:text-red-300">
            <p class="font-semibold text-sm mb-2 flex items-center gap-2">
                <i class="bi bi-exclamation-triangle-fill"></i> Corrige los errores antes de continuar:
            </p>
            <ul class="list-disc list-inside text-sm space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Formulario --}}
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-6"
         x-data="avisoForm()">

        <form action="{{ route('admin.avisos-emergencia.store') }}" method="POST" @submit="submitting = true">
            @csrf

            {{-- Tipo de aviso --}}
            <div class="mb-5">
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                    Tipo de aviso <span class="text-red-500">*</span>
                </label>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                    @foreach($tipos as $valor => $etiqueta)
                        @php
                            $colores = [
                                'emergencia'  => 'border-red-400 bg-red-50 text-red-700 dark:bg-red-900/20 dark:border-red-500 dark:text-red-300',
                                'suspension'  => 'border-orange-400 bg-orange-50 text-orange-700 dark:bg-orange-900/20 dark:border-orange-500 dark:text-orange-300',
                                'actividad'   => 'border-blue-400 bg-blue-50 text-blue-700 dark:bg-blue-900/20 dark:border-blue-500 dark:text-blue-300',
                                'informativo' => 'border-gray-400 bg-gray-50 text-gray-700 dark:bg-gray-700 dark:border-gray-500 dark:text-gray-300',
                            ];
                            $iconos = [
                                'emergencia'  => 'bi-exclamation-octagon-fill',
                                'suspension'  => 'bi-calendar-x-fill',
                                'actividad'   => 'bi-calendar-event-fill',
                                'informativo' => 'bi-info-circle-fill',
                            ];
                        @endphp
                        <label class="relative flex flex-col items-center gap-1.5 p-3 rounded-xl border-2 cursor-pointer transition select-none
                                      {{ old('tipo', 'informativo') === $valor ? $colores[$valor] : 'border-gray-200 dark:border-gray-600 hover:border-gray-300 dark:hover:border-gray-500' }}"
                               :class="tipo === '{{ $valor }}'
                                   ? '{{ $colores[$valor] }}'
                                   : 'border-gray-200 dark:border-gray-600 hover:border-gray-300 dark:hover:border-gray-500 text-gray-600 dark:text-gray-400'">
                            <input type="radio" name="tipo" value="{{ $valor }}"
                                   x-model="tipo"
                                   {{ old('tipo', 'informativo') === $valor ? 'checked' : '' }}
                                   class="sr-only">
                            <i class="bi {{ $iconos[$valor] }} text-xl"></i>
                            <span class="text-xs font-semibold">{{ $etiqueta }}</span>
                        </label>
                    @endforeach
                </div>
                @error('tipo')
                    <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Título --}}
            <div class="mb-5">
                <label for="titulo" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1.5">
                    Título <span class="text-red-500">*</span>
                </label>
                <input type="text" id="titulo" name="titulo"
                       value="{{ old('titulo') }}"
                       placeholder="Ej: Suspensión de clases por lluvia"
                       maxlength="200"
                       class="w-full px-4 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-red-400 focus:border-transparent transition text-sm
                              @error('titulo') border-red-400 @enderror">
                @error('titulo')
                    <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Mensaje --}}
            <div class="mb-5">
                <label for="mensaje" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1.5">
                    Mensaje <span class="text-red-500">*</span>
                </label>
                <textarea id="mensaje" name="mensaje" rows="5"
                          maxlength="2000"
                          placeholder="Escribe el mensaje completo que recibirán los destinatarios..."
                          x-model="mensaje"
                          class="w-full px-4 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-red-400 focus:border-transparent transition text-sm resize-none
                                 @error('mensaje') border-red-400 @enderror">{{ old('mensaje') }}</textarea>
                <div class="flex justify-between items-center mt-1">
                    @error('mensaje')
                        <p class="text-xs text-red-500">{{ $message }}</p>
                    @else
                        <span></span>
                    @enderror
                    <span class="text-xs text-gray-400" x-text="mensaje.length + '/2000'"></span>
                </div>
            </div>

            {{-- Destinatarios --}}
            <div class="mb-5">
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1.5">
                    Destinatarios <span class="text-red-500">*</span>
                </label>
                <select name="destinatarios" x-model="destinatarios"
                        class="w-full px-4 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-red-400 focus:border-transparent transition text-sm
                               @error('destinatarios') border-red-400 @enderror">
                    @foreach($destinatarios as $valor => $etiqueta)
                        <option value="{{ $valor }}" {{ old('destinatarios') === $valor ? 'selected' : '' }}>
                            {{ $etiqueta }}
                        </option>
                    @endforeach
                </select>
                @error('destinatarios')
                    <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Grupo (condicional) --}}
            <div class="mb-6" x-show="destinatarios === 'grupo'" x-transition>
                <label for="grupo_id" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1.5">
                    Seleccionar grupo <span class="text-red-500">*</span>
                </label>
                <select id="grupo_id" name="grupo_id"
                        class="w-full px-4 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-red-400 focus:border-transparent transition text-sm
                               @error('grupo_id') border-red-400 @enderror">
                    <option value="">— Elige un grupo —</option>
                    @foreach($grupos as $grupo)
                        <option value="{{ $grupo->id }}" {{ old('grupo_id') == $grupo->id ? 'selected' : '' }}>
                            {{ $grupo->nombre_completo }}
                        </option>
                    @endforeach
                </select>
                @error('grupo_id')
                    <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Banner informativo WhatsApp --}}
            <div x-show="tipo === 'emergencia' || tipo === 'suspension'" x-transition
                 class="mb-6 flex items-start gap-3 p-4 rounded-xl bg-amber-50 border border-amber-200 text-amber-800 dark:bg-amber-900/20 dark:border-amber-700 dark:text-amber-300">
                <i class="bi bi-whatsapp text-green-500 text-xl shrink-0 mt-0.5"></i>
                <p class="text-sm">
                    <strong>Envío por WhatsApp activado:</strong> Los avisos de tipo
                    <strong>Emergencia</strong> y <strong>Suspensión</strong> también se enviarán
                    por WhatsApp a los representantes con teléfono registrado
                    (requiere WhatsApp configurado en ajustes del sistema).
                </p>
            </div>

            {{-- Acciones --}}
            <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-100 dark:border-gray-700">
                <a href="{{ route('admin.avisos-emergencia.index') }}"
                   class="px-5 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-xl transition">
                    Cancelar
                </a>
                <button type="submit"
                        :disabled="submitting"
                        class="inline-flex items-center gap-2 px-6 py-2.5 bg-red-600 hover:bg-red-700 disabled:opacity-60 disabled:cursor-not-allowed text-white text-sm font-semibold rounded-xl shadow transition">
                    <template x-if="!submitting">
                        <span class="flex items-center gap-2"><i class="bi bi-send-fill"></i> Enviar Aviso</span>
                    </template>
                    <template x-if="submitting">
                        <span class="flex items-center gap-2">
                            <svg class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 22 6.477 22 12h-4z"></path>
                            </svg>
                            Enviando...
                        </span>
                    </template>
                </button>
            </div>

        </form>
    </div>

</div>
@endsection

@push('scripts')
<script>
function avisoForm() {
    return {
        tipo:          '{{ old('tipo', 'informativo') }}',
        destinatarios: '{{ old('destinatarios', 'todos') }}',
        mensaje:       '{{ old('mensaje') }}',
        submitting:    false,
    }
}
</script>
@endpush
