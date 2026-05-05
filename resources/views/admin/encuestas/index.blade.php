@extends('layouts.admin')

@section('page-title', 'Encuestas de Satisfacción')

@section('content')
<div class="space-y-6">

    {{-- Encabezado --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Encuestas de Satisfacción</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Crea y gestiona encuestas dirigidas a padres y/o estudiantes.</p>
        </div>
        <a href="{{ route('admin.encuestas.create') }}"
           class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Nueva Encuesta
        </a>
    </div>

    {{-- Flash --}}
    @if(session('success'))
        <div class="p-4 bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-700 rounded-lg text-green-800 dark:text-green-300 text-sm">
            {{ session('success') }}
        </div>
    @endif

    {{-- Tabla --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        @if($encuestas->isEmpty())
            <div class="py-16 text-center">
                <svg class="mx-auto w-12 h-12 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">No hay encuestas creadas aún.</p>
                <a href="{{ route('admin.encuestas.create') }}" class="mt-2 inline-block text-blue-600 hover:underline text-sm">Crear la primera</a>
            </div>
        @else
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-700/50 text-gray-600 dark:text-gray-300 uppercase text-xs tracking-wider">
                    <tr>
                        <th class="px-4 py-3 text-left">Título</th>
                        <th class="px-4 py-3 text-left">Dirigida a</th>
                        <th class="px-4 py-3 text-center">Preguntas</th>
                        <th class="px-4 py-3 text-center">Respuestas</th>
                        <th class="px-4 py-3 text-center">Cierre</th>
                        <th class="px-4 py-3 text-center">Estado</th>
                        <th class="px-4 py-3 text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach($encuestas as $encuesta)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition">
                        <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">
                            {{ $encuesta->titulo }}
                            @if($encuesta->descripcion)
                                <p class="text-xs text-gray-400 font-normal mt-0.5 line-clamp-1">{{ $encuesta->descripcion }}</p>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @php
                                $badge = match($encuesta->dirigida_a) {
                                    'padres'      => ['text' => 'Padres', 'class' => 'bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-300'],
                                    'estudiantes' => ['text' => 'Estudiantes', 'class' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300'],
                                    default       => ['text' => 'Todos', 'class' => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300'],
                                };
                            @endphp
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $badge['class'] }}">{{ $badge['text'] }}</span>
                        </td>
                        <td class="px-4 py-3 text-center text-gray-700 dark:text-gray-300">{{ $encuesta->preguntas_count }}</td>
                        <td class="px-4 py-3 text-center text-gray-700 dark:text-gray-300">{{ $encuesta->respuestas_count }}</td>
                        <td class="px-4 py-3 text-center text-gray-500 dark:text-gray-400 text-xs">
                            {{ $encuesta->fecha_cierre?->format('d/m/Y') ?? '—' }}
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($encuesta->activo)
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300">Activa</span>
                            @else
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300">Inactiva</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-center gap-2">
                                {{-- Ver resultados --}}
                                <a href="{{ route('admin.encuestas.show', $encuesta) }}"
                                   title="Ver resultados"
                                   class="p-1.5 rounded-lg text-gray-500 hover:bg-blue-50 hover:text-blue-600 dark:hover:bg-blue-900/30 dark:hover:text-blue-400 transition">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                                    </svg>
                                </a>
                                {{-- Toggle activo --}}
                                <form method="POST" action="{{ route('admin.encuestas.toggle-activo', $encuesta) }}">
                                    @csrf @method('PATCH')
                                    <button type="submit"
                                            title="{{ $encuesta->activo ? 'Desactivar' : 'Activar' }}"
                                            class="p-1.5 rounded-lg transition {{ $encuesta->activo ? 'text-green-600 hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-900/30' : 'text-gray-400 hover:bg-green-50 hover:text-green-600 dark:hover:bg-green-900/30' }}">
                                        @if($encuesta->activo)
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                        @else
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                        @endif
                                    </button>
                                </form>
                                {{-- Eliminar --}}
                                <form method="POST" action="{{ route('admin.encuestas.destroy', $encuesta) }}"
                                      onsubmit="return confirm('¿Eliminar esta encuesta y todas sus respuestas?')">
                                    @csrf @method('DELETE')
                                    <button type="submit"
                                            title="Eliminar"
                                            class="p-1.5 rounded-lg text-gray-400 hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-900/30 dark:hover:text-red-400 transition">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @if($encuestas->hasPages())
                <div class="px-4 py-3 border-t border-gray-100 dark:border-gray-700">
                    {{ $encuestas->links() }}
                </div>
            @endif
        @endif
    </div>
</div>
@endsection
