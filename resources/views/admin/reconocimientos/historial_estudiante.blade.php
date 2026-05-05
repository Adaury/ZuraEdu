@extends('layouts.admin')

@section('page-title', 'Reconocimientos — ' . $estudiante->nombre_completo)

@section('content')
<div class="space-y-6">

    {{-- Encabezado --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center gap-4">
            <a href="{{ route('admin.reconocimientos.index') }}"
               class="p-2 rounded-lg text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-full bg-indigo-100 dark:bg-indigo-900/40 flex items-center justify-center text-xl font-bold text-indigo-700 dark:text-indigo-300">
                    {{ strtoupper(substr($estudiante->nombres ?? '?', 0, 1)) }}
                </div>
                <div>
                    <h1 class="text-xl font-bold text-gray-900 dark:text-white">
                        🏆 Historial de Reconocimientos
                    </h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                        {{ $estudiante->nombre_completo }}
                        @if($estudiante->numero_matricula)
                        · Matrícula: {{ $estudiante->numero_matricula }}
                        @endif
                    </p>
                </div>
            </div>
        </div>
        <a href="{{ route('admin.reconocimientos.create') }}?estudiante_id={{ $estudiante->id }}"
           class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg shadow transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Nuevo Reconocimiento
        </a>
    </div>

    {{-- Resumen por tipo --}}
    @if($tipos->isNotEmpty())
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3">
        @foreach($tipos as $tipo)
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-3 text-center">
            <div class="text-2xl mb-1">{{ $tipo->icono }}</div>
            <p class="text-xs text-gray-500 dark:text-gray-400 font-medium leading-tight">{{ $tipo->nombre }}</p>
            <p class="text-lg font-bold text-gray-900 dark:text-white mt-1">{{ $tipo->total }}</p>
        </div>
        @endforeach
    </div>
    @endif

    {{-- Timeline de reconocimientos --}}
    @if($reconocimientos->isEmpty())
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-12 text-center">
        <div class="text-5xl mb-3">📋</div>
        <p class="text-base font-medium text-gray-500 dark:text-gray-400">
            Este estudiante aún no tiene reconocimientos registrados.
        </p>
    </div>
    @else

    {{-- Conteo total --}}
    <div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400 font-medium">
        <span class="w-2 h-2 rounded-full bg-indigo-500 inline-block"></span>
        {{ $reconocimientos->count() }} {{ $reconocimientos->count() === 1 ? 'reconocimiento' : 'reconocimientos' }} en total
    </div>

    <div class="relative">
        {{-- Línea vertical del timeline --}}
        <div class="absolute left-6 top-0 bottom-0 w-0.5 bg-gradient-to-b from-indigo-300 via-yellow-300 to-indigo-100 dark:from-indigo-700 dark:via-yellow-700 dark:to-indigo-900"></div>

        <div class="space-y-4">
            @php $anioActual = null; @endphp
            @foreach($reconocimientos as $r)

            {{-- Separador de año --}}
            @if($r->fecha->year !== $anioActual)
                @php $anioActual = $r->fecha->year; @endphp
                <div class="relative flex items-center gap-3 pl-16 mb-1">
                    <span class="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest bg-gray-50 dark:bg-gray-900 px-2">
                        {{ $anioActual }}
                    </span>
                    <div class="flex-1 h-px bg-gray-200 dark:bg-gray-700"></div>
                </div>
            @endif

            <div class="relative flex gap-4">
                {{-- Nodo del timeline --}}
                <div class="flex-shrink-0 w-12 h-12 rounded-full border-2 border-white dark:border-gray-800 shadow
                            bg-gradient-to-br from-yellow-300 to-amber-400 dark:from-yellow-600 dark:to-amber-700
                            flex items-center justify-center text-xl z-10">
                    {{ $r->tipo->icono }}
                </div>

                {{-- Tarjeta --}}
                <div class="flex-1 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-4 hover:shadow-md transition">
                    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-2">
                        <div class="flex-1">
                            <div class="flex flex-wrap items-center gap-2 mb-1">
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold ring-1 ring-inset {{ $r->tipo->badgeClasses() }}">
                                    {{ $r->tipo->nombre }}
                                </span>
                                @if($r->entregado)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400 ring-1 ring-inset ring-green-300 dark:ring-green-700">
                                        ✅ Entregado
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400 ring-1 ring-inset ring-amber-300">
                                        ⏳ Pendiente
                                    </span>
                                @endif
                            </div>
                            <h3 class="text-base font-semibold text-gray-900 dark:text-white">
                                {{ $r->titulo }}
                            </h3>
                            @if($r->descripcion)
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                {{ $r->descripcion }}
                            </p>
                            @endif
                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-2">
                                Emitido por {{ $r->emitidoPor->name }}
                                @if($r->entregado && $r->fecha_entrega)
                                · Entregado el {{ $r->fecha_entrega->format('d/m/Y') }}
                                @endif
                            </p>
                        </div>

                        <div class="flex flex-col items-end gap-2 flex-shrink-0">
                            <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                                {{ $r->fecha->format('d/m/Y') }}
                            </span>
                            <div class="flex items-center gap-1">
                                <a href="{{ route('admin.reconocimientos.diploma-pdf', $r) }}"
                                   target="_blank"
                                   title="Descargar Diploma PDF"
                                   class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-medium rounded-lg transition">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                    </svg>
                                    Diploma
                                </a>

                                @unless($r->entregado)
                                <form method="POST" action="{{ route('admin.reconocimientos.entregar', $r) }}">
                                    @csrf @method('PATCH')
                                    <button type="submit"
                                            title="Marcar como entregado"
                                            onclick="return confirm('¿Marcar como entregado?')"
                                            class="p-1.5 rounded-lg text-green-600 dark:text-green-400 hover:bg-green-50 dark:hover:bg-green-900/30 transition">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        </svg>
                                    </button>
                                </form>
                                @endunless

                                <a href="{{ route('admin.reconocimientos.edit', $r) }}"
                                   class="p-1.5 rounded-lg text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </a>

                                <form method="POST" action="{{ route('admin.reconocimientos.destroy', $r) }}">
                                    @csrf @method('DELETE')
                                    <button type="submit"
                                            title="Eliminar"
                                            onclick="return confirm('¿Eliminar este reconocimiento?')"
                                            class="p-1.5 rounded-lg text-red-500 hover:bg-red-50 dark:hover:bg-red-900/30 transition">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

</div>
@endsection
