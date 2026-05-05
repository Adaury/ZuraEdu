@extends('layouts.admin')

@section('page-title', 'Diplomas y Reconocimientos')

@section('content')
<div class="space-y-6" x-data="{ showFilters: false }">

    {{-- Encabezado --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                🏆 Diplomas y Reconocimientos
            </h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                Registro y gestión de reconocimientos estudiantiles
            </p>
        </div>
        <div class="flex items-center gap-2">
            <button @click="showFilters = !showFilters"
                class="inline-flex items-center gap-2 px-3 py-2 text-sm rounded-lg border border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2a1 1 0 01-.293.707L13 13.414V19a1 1 0 01-.553.894l-4 2A1 1 0 017 21v-7.586L3.293 6.707A1 1 0 013 6V4z"/>
                </svg>
                Filtros
            </button>
            <a href="{{ route('admin.reconocimientos.create') }}"
               class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg shadow transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Nuevo Reconocimiento
            </a>
        </div>
    </div>

    {{-- Tarjetas resumen --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 flex items-center gap-4">
            <div class="w-12 h-12 rounded-full bg-indigo-100 dark:bg-indigo-900/40 flex items-center justify-center text-2xl">🏅</div>
            <div>
                <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide font-semibold">Total</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $totalGeneral }}</p>
            </div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 flex items-center gap-4">
            <div class="w-12 h-12 rounded-full bg-green-100 dark:bg-green-900/40 flex items-center justify-center text-2xl">✅</div>
            <div>
                <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide font-semibold">Entregados</p>
                <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $totalEntregados }}</p>
            </div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 flex items-center gap-4">
            <div class="w-12 h-12 rounded-full bg-amber-100 dark:bg-amber-900/40 flex items-center justify-center text-2xl">⏳</div>
            <div>
                <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide font-semibold">Pendientes</p>
                <p class="text-2xl font-bold text-amber-600 dark:text-amber-400">{{ $totalPendientes }}</p>
            </div>
        </div>
    </div>

    {{-- Alertas --}}
    @if(session('success'))
    <div class="flex items-center gap-3 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-700 rounded-xl text-green-700 dark:text-green-300 text-sm">
        <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
        </svg>
        {{ session('success') }}
    </div>
    @endif

    {{-- Panel de filtros (colapsable) --}}
    <div x-show="showFilters" x-transition class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
        <form method="GET" action="{{ route('admin.reconocimientos.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div>
                <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1 uppercase tracking-wide">Buscar</label>
                <input type="text" name="q" value="{{ request('q') }}"
                       placeholder="Nombre, título..."
                       class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:ring-indigo-500 focus:border-indigo-500">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1 uppercase tracking-wide">Tipo</label>
                <select name="tipo_id" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">— Todos —</option>
                    @foreach($tipos as $t)
                        <option value="{{ $t->id }}" @selected(request('tipo_id') == $t->id)>{{ $t->icono }} {{ $t->nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1 uppercase tracking-wide">Estudiante</label>
                <select name="estudiante_id" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">— Todos —</option>
                    @foreach($estudiantes as $e)
                        <option value="{{ $e->id }}" @selected(request('estudiante_id') == $e->id)>{{ $e->nombre_completo }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1 uppercase tracking-wide">Estado</label>
                <select name="entregado" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">— Todos —</option>
                    <option value="0" @selected(request('entregado') === '0')>Pendiente de entrega</option>
                    <option value="1" @selected(request('entregado') === '1')>Entregado</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1 uppercase tracking-wide">Desde</label>
                <input type="date" name="fecha_desde" value="{{ request('fecha_desde') }}"
                       class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:ring-indigo-500 focus:border-indigo-500">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1 uppercase tracking-wide">Hasta</label>
                <input type="date" name="fecha_hasta" value="{{ request('fecha_hasta') }}"
                       class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:ring-indigo-500 focus:border-indigo-500">
            </div>
            <div class="flex items-end gap-2 sm:col-span-2">
                <button type="submit"
                        class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition">
                    Filtrar
                </button>
                <a href="{{ route('admin.reconocimientos.index') }}"
                   class="px-4 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-lg transition">
                    Limpiar
                </a>
            </div>
        </form>
    </div>

    {{-- Tabla --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden shadow-sm">
        @if($reconocimientos->isEmpty())
        <div class="text-center py-16 text-gray-400 dark:text-gray-500">
            <div class="text-5xl mb-3">🏆</div>
            <p class="text-base font-medium">No se encontraron reconocimientos.</p>
            <p class="text-sm mt-1">Crea el primero usando el botón "Nuevo Reconocimiento".</p>
        </div>
        @else
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                <thead class="bg-gray-50 dark:bg-gray-700/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Estudiante</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Tipo</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Título</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Fecha</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Estado</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Emitido por</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach($reconocimientos as $r)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/40 transition">
                        {{-- Estudiante --}}
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-indigo-100 dark:bg-indigo-900/40 flex items-center justify-center text-xs font-bold text-indigo-700 dark:text-indigo-300 flex-shrink-0">
                                    {{ strtoupper(substr($r->estudiante->nombres ?? '?', 0, 1)) }}
                                </div>
                                <div>
                                    <p class="font-semibold text-gray-900 dark:text-white leading-tight">{{ $r->estudiante->nombre_completo }}</p>
                                    <p class="text-xs text-gray-400">{{ $r->estudiante->numero_matricula ?? '' }}</p>
                                </div>
                            </div>
                        </td>

                        {{-- Tipo --}}
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold ring-1 ring-inset {{ $r->tipo->badgeClasses() }}">
                                {{ $r->tipo->icono }} {{ $r->tipo->nombre }}
                            </span>
                        </td>

                        {{-- Título --}}
                        <td class="px-4 py-3">
                            <p class="text-gray-800 dark:text-gray-200 font-medium max-w-xs truncate" title="{{ $r->titulo }}">{{ $r->titulo }}</p>
                            @if($r->descripcion)
                            <p class="text-xs text-gray-400 truncate max-w-xs" title="{{ $r->descripcion }}">{{ $r->descripcion }}</p>
                            @endif
                        </td>

                        {{-- Fecha --}}
                        <td class="px-4 py-3 whitespace-nowrap text-gray-600 dark:text-gray-300">
                            {{ $r->fecha->format('d/m/Y') }}
                        </td>

                        {{-- Estado --}}
                        <td class="px-4 py-3">
                            @if($r->entregado)
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400 ring-1 ring-inset ring-green-300 dark:ring-green-700">
                                    ✅ Entregado
                                </span>
                                @if($r->fecha_entrega)
                                <p class="text-xs text-gray-400 mt-0.5">{{ $r->fecha_entrega->format('d/m/Y') }}</p>
                                @endif
                            @else
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400 ring-1 ring-inset ring-amber-300 dark:ring-amber-700">
                                    ⏳ Pendiente
                                </span>
                            @endif
                        </td>

                        {{-- Emitido por --}}
                        <td class="px-4 py-3 text-xs text-gray-500 dark:text-gray-400 whitespace-nowrap">
                            {{ $r->emitidoPor->name }}
                        </td>

                        {{-- Acciones --}}
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-1">
                                {{-- Diploma PDF --}}
                                <a href="{{ route('admin.reconocimientos.diploma-pdf', $r) }}"
                                   target="_blank"
                                   title="Descargar Diploma PDF"
                                   class="p-1.5 rounded-lg text-indigo-600 dark:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 transition">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                    </svg>
                                </a>

                                {{-- Historial estudiante --}}
                                <a href="{{ route('admin.reconocimientos.historial-estudiante', $r->estudiante) }}"
                                   title="Ver historial del estudiante"
                                   class="p-1.5 rounded-lg text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/30 transition">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </a>

                                {{-- Marcar entregado --}}
                                @unless($r->entregado)
                                <form method="POST" action="{{ route('admin.reconocimientos.entregar', $r) }}">
                                    @csrf @method('PATCH')
                                    <button type="submit"
                                            title="Marcar como entregado"
                                            onclick="return confirm('¿Marcar este reconocimiento como entregado?')"
                                            class="p-1.5 rounded-lg text-green-600 dark:text-green-400 hover:bg-green-50 dark:hover:bg-green-900/30 transition">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        </svg>
                                    </button>
                                </form>
                                @endunless

                                {{-- Editar --}}
                                <a href="{{ route('admin.reconocimientos.edit', $r) }}"
                                   title="Editar"
                                   class="p-1.5 rounded-lg text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </a>

                                {{-- Eliminar --}}
                                <form method="POST" action="{{ route('admin.reconocimientos.destroy', $r) }}">
                                    @csrf @method('DELETE')
                                    <button type="submit"
                                            title="Eliminar"
                                            onclick="return confirm('¿Eliminar este reconocimiento? Esta acción no se puede deshacer.')"
                                            class="p-1.5 rounded-lg text-red-500 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/30 transition">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Paginación --}}
        @if($reconocimientos->hasPages())
        <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700">
            {{ $reconocimientos->links() }}
        </div>
        @endif
        @endif
    </div>

</div>
@endsection
