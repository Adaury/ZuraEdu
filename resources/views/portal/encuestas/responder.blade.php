{{--
    Vista compartida para responder encuestas.
    Variables esperadas:
      $encuesta   — modelo Encuesta con preguntas.opciones cargadas
      $backRoute  — ruta de vuelta (portal padre o estudiante)
      $postRoute  — ruta POST para enviar respuestas
--}}
@extends('layouts.portal')

@section('title', $encuesta->titulo)

@section('content')
<div class="max-w-2xl mx-auto py-8 px-4 space-y-6">

    {{-- Encabezado --}}
    <div class="flex items-start gap-3">
        <a href="{{ $backRoute }}"
           class="mt-1 p-2 rounded-lg text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-600 dark:hover:text-gray-300 transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
        </a>
        <div>
            <h1 class="text-xl font-bold text-gray-900 dark:text-white">{{ $encuesta->titulo }}</h1>
            @if($encuesta->descripcion)
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $encuesta->descripcion }}</p>
            @endif
            @if($encuesta->fecha_cierre)
                <p class="mt-1 text-xs text-amber-600 dark:text-amber-400">
                    Fecha de cierre: {{ $encuesta->fecha_cierre->format('d/m/Y') }}
                </p>
            @endif
        </div>
    </div>

    @if($errors->any())
        <div class="p-4 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-700 rounded-lg text-red-700 dark:text-red-300 text-sm space-y-1">
            @foreach($errors->all() as $error)
                <p>• {{ $error }}</p>
            @endforeach
        </div>
    @endif

    @if(session('success'))
        <div class="p-4 bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-700 rounded-lg text-green-800 dark:text-green-300 text-sm">
            {{ session('success') }}
        </div>
    @endif

    {{-- Formulario --}}
    <form method="POST" action="{{ $postRoute }}" class="space-y-5">
        @csrf

        @foreach($encuesta->preguntas as $pregunta)
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 shadow-sm">
                <div class="flex items-start gap-3 mb-4">
                    <span class="flex-shrink-0 w-6 h-6 rounded-full bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300 text-xs font-bold flex items-center justify-center">
                        {{ $loop->iteration }}
                    </span>
                    <p class="font-medium text-gray-800 dark:text-white leading-snug">{{ $pregunta->texto }}</p>
                </div>

                @if($pregunta->tipo === 'opcion_multiple')
                    <div class="ml-9 space-y-2">
                        @foreach($pregunta->opciones as $opcion)
                            <label class="flex items-center gap-3 cursor-pointer group">
                                <input type="radio"
                                       name="respuestas[{{ $pregunta->id }}][opcion_id]"
                                       value="{{ $opcion->id }}"
                                       {{ old("respuestas.{$pregunta->id}.opcion_id") == $opcion->id ? 'checked' : '' }}
                                       class="w-4 h-4 border-gray-300 text-blue-600 focus:ring-blue-500">
                                <span class="text-sm text-gray-700 dark:text-gray-300 group-hover:text-gray-900 dark:group-hover:text-white transition">
                                    {{ $opcion->texto }}
                                </span>
                            </label>
                        @endforeach
                        @error("respuestas.{$pregunta->id}.opcion_id")
                            <p class="text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                @elseif($pregunta->tipo === 'escala_1_5')
                    <div class="ml-9">
                        <div class="flex items-center gap-1 flex-wrap">
                            @foreach([1,2,3,4,5] as $valor)
                                <label class="flex flex-col items-center gap-1 cursor-pointer group">
                                    <input type="radio"
                                           name="respuestas[{{ $pregunta->id }}][escala_valor]"
                                           value="{{ $valor }}"
                                           {{ old("respuestas.{$pregunta->id}.escala_valor") == $valor ? 'checked' : '' }}
                                           class="sr-only peer">
                                    <span class="w-10 h-10 rounded-full border-2 border-gray-200 dark:border-gray-600
                                                 peer-checked:border-blue-500 peer-checked:bg-blue-500 peer-checked:text-white
                                                 group-hover:border-blue-400 flex items-center justify-center
                                                 text-sm font-bold text-gray-600 dark:text-gray-400 peer-checked:text-white
                                                 transition select-none">
                                        {{ $valor }}
                                    </span>
                                </label>
                            @endforeach
                        </div>
                        <p class="mt-1.5 text-xs text-gray-400 dark:text-gray-500">1 = Muy malo &nbsp;&bull;&nbsp; 5 = Excelente</p>
                        @error("respuestas.{$pregunta->id}.escala_valor")
                            <p class="text-xs text-red-600 dark:text-red-400 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                @else
                    {{-- texto_libre --}}
                    <div class="ml-9">
                        <textarea name="respuestas[{{ $pregunta->id }}][respuesta_texto]"
                                  rows="3"
                                  placeholder="Escribe tu respuesta aquí..."
                                  class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 text-sm bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 resize-none">{{ old("respuestas.{$pregunta->id}.respuesta_texto") }}</textarea>
                        @error("respuestas.{$pregunta->id}.respuesta_texto")
                            <p class="text-xs text-red-600 dark:text-red-400 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                @endif
            </div>
        @endforeach

        <div class="flex items-center justify-end gap-3 pt-2">
            <a href="{{ $backRoute }}"
               class="px-4 py-2 text-sm text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                Cancelar
            </a>
            <button type="submit"
                    class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg transition shadow-sm">
                Enviar respuestas
            </button>
        </div>
    </form>
</div>
@endsection
