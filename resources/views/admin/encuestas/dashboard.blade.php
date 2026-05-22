@extends('layouts.admin')

@section('page-title', 'Encuestas')

@section('content')
<div class="space-y-6">

    {{-- Encabezado --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Encuestas de Satisfacción</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Gestión y análisis de resultados</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.encuestas.lista-pdf') }}"
               class="inline-flex items-center gap-1.5 text-sm font-medium bg-white dark:bg-gray-800
                      border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300
                      rounded-lg px-3 py-2 hover:shadow-sm transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                </svg>
                PDF
            </a>
            <a href="{{ route('admin.encuestas.lista-excel') }}"
               class="inline-flex items-center gap-1.5 text-sm font-medium bg-white dark:bg-gray-800
                      border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300
                      rounded-lg px-3 py-2 hover:shadow-sm transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Excel
            </a>
            <a href="{{ route('admin.encuestas.create') }}"
               class="inline-flex items-center gap-1.5 text-sm font-semibold bg-indigo-600 hover:bg-indigo-700
                      text-white rounded-lg px-4 py-2 shadow transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Nueva encuesta
            </a>
        </div>
    </div>

    {{-- Flash --}}
    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-700 rounded-lg px-4 py-3 text-sm">
            {{ session('success') }}
        </div>
    @endif

    {{-- Tarjetas de estadísticas --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Total</span>
                <div class="w-8 h-8 rounded-lg bg-indigo-100 dark:bg-indigo-900/30 flex items-center justify-center">
                    <svg class="w-4 h-4 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                    </svg>
                </div>
            </div>
            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $totalEncuestas }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">encuestas creadas</p>
        </div>

        <a href="{{ route('admin.encuestas.index') }}"
           class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700
                  p-4 hover:shadow-md transition">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Activas</span>
                <div class="w-8 h-8 rounded-lg bg-green-100 dark:bg-green-900/30 flex items-center justify-center">
                    <svg class="w-4 h-4 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            <p class="text-2xl font-bold text-green-700 dark:text-green-400">{{ $encuestasActivas }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">en curso</p>
        </a>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Cerradas</span>
                <div class="w-8 h-8 rounded-lg bg-gray-100 dark:bg-gray-700 flex items-center justify-center">
                    <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                    </svg>
                </div>
            </div>
            <p class="text-2xl font-bold text-gray-700 dark:text-gray-300">{{ $encuestasCerradas }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">inactivas / vencidas</p>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Participantes</span>
                <div class="w-8 h-8 rounded-lg bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                    <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
            </div>
            <p class="text-2xl font-bold text-blue-700 dark:text-blue-400">{{ $totalRespuestas }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">usuarios únicos</p>
        </div>

    </div>

    {{-- Cuerpo principal --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Audiencia + más respondidas --}}
        <div class="space-y-5">

            {{-- Por audiencia --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-5">
                <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-4">Por audiencia</h2>
                @php
                    $audiencias = [
                        'todos'       => ['label' => 'Todos', 'color' => 'bg-indigo-500'],
                        'padres'      => ['label' => 'Padres / Representantes', 'color' => 'bg-blue-500'],
                        'estudiantes' => ['label' => 'Estudiantes', 'color' => 'bg-emerald-500'],
                    ];
                @endphp
                @foreach($audiencias as $key => $info)
                    @php $total = $porAudiencia->get($key)?->total ?? 0; @endphp
                    <div class="flex items-center gap-3 mb-2">
                        <div class="w-2 h-2 rounded-full {{ $info['color'] }} shrink-0"></div>
                        <span class="text-xs text-gray-600 dark:text-gray-400 flex-1">{{ $info['label'] }}</span>
                        <span class="text-xs font-bold text-gray-800 dark:text-gray-200">{{ $total }}</span>
                    </div>
                @endforeach
            </div>

            {{-- Más respondidas --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-5">
                <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-4">Más respondidas</h2>
                @forelse($masRespondidas as $enc)
                    <a href="{{ route('admin.encuestas.show', $enc) }}"
                       class="flex items-center justify-between py-2 hover:bg-gray-50 dark:hover:bg-gray-700/40
                              rounded-lg px-1 transition group">
                        <span class="text-xs text-gray-700 dark:text-gray-300 truncate group-hover:text-indigo-600 dark:group-hover:text-indigo-400">
                            {{ $enc->titulo }}
                        </span>
                        <span class="ml-2 shrink-0 text-xs font-bold text-indigo-600 dark:text-indigo-400">
                            {{ $enc->respuestas_count }}
                        </span>
                    </a>
                @empty
                    <p class="text-sm text-gray-400 text-center py-3">Sin respuestas aún</p>
                @endforelse
            </div>

        </div>

        {{-- Listado de encuestas recientes --}}
        <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between px-5 pt-5 pb-3 border-b border-gray-100 dark:border-gray-700">
                <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Encuestas recientes</h2>
                <a href="{{ route('admin.encuestas.index') }}"
                   class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline font-medium">Ver todas</a>
            </div>

            <div class="divide-y divide-gray-100 dark:divide-gray-700">
                @forelse($ultimasEncuestas as $enc)
                    @php
                        $estaActiva = $enc->activo && (! $enc->fecha_cierre || $enc->fecha_cierre >= today());
                        $audienciaLabel = match($enc->dirigida_a) {
                            'padres'      => 'Padres',
                            'estudiantes' => 'Estudiantes',
                            default       => 'Todos',
                        };
                    @endphp
                    <div class="flex items-center justify-between px-5 py-3 hover:bg-gray-50 dark:hover:bg-gray-700/40 transition">
                        <div class="min-w-0 flex-1">
                            <a href="{{ route('admin.encuestas.show', $enc) }}"
                               class="font-medium text-sm text-gray-800 dark:text-gray-200 hover:text-indigo-600
                                      dark:hover:text-indigo-400 truncate block">
                                {{ $enc->titulo }}
                            </a>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                {{ $audienciaLabel }}
                                · {{ $enc->preguntas_count }} pregunta{{ $enc->preguntas_count !== 1 ? 's' : '' }}
                                · {{ $enc->totalParticipantes() }} participante{{ $enc->totalParticipantes() !== 1 ? 's' : '' }}
                                @if($enc->fecha_cierre)
                                    · cierra {{ $enc->fecha_cierre->format('d/m/Y') }}
                                @endif
                            </p>
                        </div>
                        <div class="ml-3 flex items-center gap-2 shrink-0">
                            <a href="{{ route('admin.encuestas.resultados-excel', $enc) }}"
                               class="text-gray-400 hover:text-emerald-600 transition" title="Exportar resultados Excel">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                            </a>
                            <form method="POST" action="{{ route('admin.encuestas.toggle-activo', $enc) }}" class="inline">
                                @csrf @method('PATCH')
                                <button type="submit"
                                        class="text-xs font-medium px-2 py-0.5 rounded-full transition
                                               {{ $estaActiva
                                                  ? 'bg-green-100 text-green-700 hover:bg-green-200 dark:bg-green-900/30 dark:text-green-300'
                                                  : 'bg-gray-100 text-gray-500 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-400' }}"
                                        title="Cambiar estado">
                                    {{ $estaActiva ? 'Activa' : 'Inactiva' }}
                                </button>
                            </form>
                        </div>
                    </div>
                @empty
                    <div class="px-5 py-8 text-center text-sm text-gray-400">
                        No hay encuestas creadas aún.
                        <a href="{{ route('admin.encuestas.create') }}"
                           class="text-indigo-600 dark:text-indigo-400 hover:underline ml-1">Crear la primera</a>
                    </div>
                @endforelse
            </div>
        </div>

    </div>

</div>
@endsection
