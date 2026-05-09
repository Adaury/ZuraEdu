@extends('layouts.admin')

@section('page-title', 'Transporte Escolar')

@section('content')
<div class="space-y-6">

    {{-- Encabezado --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                <svg class="w-7 h-7 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M8 17H5a2 2 0 01-2-2V7a2 2 0 012-2h3m8 0h3a2 2 0 012 2v8a2 2 0 01-2 2h-3m-8 0h8m-8 0v-4m8 4v-4M8 7V5m8 2V5M3 12h18"/>
                </svg>
                Transporte Escolar
            </h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Gestión de rutas, paradas y pasajeros</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.transporte.lista-pdf', request()->query()) }}"
               class="inline-flex items-center gap-2 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold px-4 py-2 rounded-lg shadow transition">
                <i class="bi bi-file-earmark-pdf"></i>PDF
            </a>
            <a href="{{ route('admin.transporte.lista-excel', request()->query()) }}"
               class="inline-flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold px-4 py-2 rounded-lg shadow transition">
                <i class="bi bi-file-earmark-excel"></i>Excel
            </a>
            <a href="{{ route('admin.transporte.create') }}"
               class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-4 py-2 rounded-lg shadow transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Nueva Ruta
            </a>
        </div>
    </div>

    {{-- Alertas --}}
    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-800 rounded-lg px-4 py-3 text-sm">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="bg-red-50 border border-red-200 text-red-800 rounded-lg px-4 py-3 text-sm">
            {{ session('error') }}
        </div>
    @endif

    {{-- Filtros --}}
    <form method="GET" class="flex flex-wrap gap-3 items-end">
        <div class="flex-1 min-w-[220px]">
            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Buscar</label>
            <input type="text" name="q" value="{{ request('q') }}"
                   placeholder="Nombre, conductor, vehículo…"
                   class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800
                          text-sm px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:text-white">
        </div>
        <div class="min-w-[140px]">
            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Estado</label>
            <select name="activo"
                    class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800
                           text-sm px-3 py-2 focus:ring-2 focus:ring-blue-500 dark:text-white">
                <option value="">Todos</option>
                <option value="1" {{ request('activo') === '1' ? 'selected' : '' }}>Activas</option>
                <option value="0" {{ request('activo') === '0' ? 'selected' : '' }}>Inactivas</option>
            </select>
        </div>
        <button type="submit"
                class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-4 py-2 rounded-lg transition">
            Filtrar
        </button>
        @if(request()->hasAny(['q','activo']))
            <a href="{{ route('admin.transporte.index') }}"
               class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 px-3 py-2 rounded-lg
                      border border-gray-200 dark:border-gray-600 transition">
                Limpiar
            </a>
        @endif
    </form>

    {{-- Tabla --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 dark:bg-gray-700/50 border-b border-gray-200 dark:border-gray-700">
                        <th class="text-left px-5 py-3 font-semibold text-gray-600 dark:text-gray-300">Ruta</th>
                        <th class="text-left px-4 py-3 font-semibold text-gray-600 dark:text-gray-300">Conductor</th>
                        <th class="text-left px-4 py-3 font-semibold text-gray-600 dark:text-gray-300">Vehículo</th>
                        <th class="text-center px-4 py-3 font-semibold text-gray-600 dark:text-gray-300">Ocupación</th>
                        <th class="text-center px-4 py-3 font-semibold text-gray-600 dark:text-gray-300">Estado</th>
                        <th class="text-right px-5 py-3 font-semibold text-gray-600 dark:text-gray-300">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse($rutas as $ruta)
                    @php
                        $pct    = $ruta->capacidad > 0 ? round(($ruta->estudiantes_ruta_count / $ruta->capacidad) * 100) : 0;
                        $color  = $pct >= 90 ? 'red' : ($pct >= 70 ? 'yellow' : 'green');
                        $barClr = $pct >= 90 ? 'bg-red-500' : ($pct >= 70 ? 'bg-yellow-500' : 'bg-green-500');
                    @endphp
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition">
                        <td class="px-5 py-3">
                            <a href="{{ route('admin.transporte.show', $ruta) }}"
                               class="font-semibold text-blue-600 dark:text-blue-400 hover:underline">
                                {{ $ruta->nombre }}
                            </a>
                            @if($ruta->descripcion)
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 line-clamp-1">{{ $ruta->descripcion }}</p>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                            {{ $ruta->conductor ?? '—' }}
                        </td>
                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                            {{ $ruta->vehiculo ?? '—' }}
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex flex-col items-center gap-1 min-w-[100px]">
                                <span class="text-xs font-semibold text-{{ $color }}-600">
                                    {{ $ruta->estudiantes_ruta_count }} / {{ $ruta->capacidad }}
                                </span>
                                <div class="w-full bg-gray-200 dark:bg-gray-600 rounded-full h-1.5">
                                    <div class="{{ $barClr }} h-1.5 rounded-full transition-all"
                                         style="width: {{ min($pct, 100) }}%"></div>
                                </div>
                                <span class="text-xs text-gray-400">{{ $pct }}%</span>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($ruta->activo)
                                <span class="inline-flex items-center gap-1 bg-green-100 text-green-700 text-xs font-semibold px-2 py-1 rounded-full">
                                    <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span> Activa
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 bg-gray-100 text-gray-600 text-xs font-semibold px-2 py-1 rounded-full">
                                    <span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span> Inactiva
                                </span>
                            @endif
                        </td>
                        <td class="px-5 py-3">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('admin.transporte.show', $ruta) }}"
                                   class="text-blue-600 hover:text-blue-800 dark:text-blue-400 transition" title="Ver detalle">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                </a>
                                <a href="{{ route('admin.transporte.edit', $ruta) }}"
                                   class="text-yellow-600 hover:text-yellow-800 dark:text-yellow-400 transition" title="Editar">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M15.232 5.232l3.536 3.536M9 13l6.5-6.5a2.121 2.121 0 013 3L12 16H9v-3z"/>
                                    </svg>
                                </a>
                                <a href="{{ route('admin.transporte.pasajeros.pdf', $ruta) }}" target="_blank"
                                   class="text-red-500 hover:text-red-700 dark:text-red-400 transition" title="PDF Pasajeros">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                    </svg>
                                </a>
                                <form method="POST" action="{{ route('admin.transporte.destroy', $ruta) }}"
                                      onsubmit="return confirm('¿Eliminar esta ruta y todos sus datos?')">
                                    @csrf @method('DELETE')
                                    <button type="submit"
                                            class="text-gray-400 hover:text-red-600 dark:hover:text-red-400 transition" title="Eliminar">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M1 7h22M8 7V5a1 1 0 011-1h6a1 1 0 011 1v2"/>
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-5 py-10 text-center text-gray-400 dark:text-gray-500">
                            <svg class="w-10 h-10 mx-auto mb-2 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                      d="M8 17H5a2 2 0 01-2-2V7a2 2 0 012-2h3m8 0h3a2 2 0 012 2v8a2 2 0 01-2 2h-3m-8 0h8"/>
                            </svg>
                            No se encontraron rutas. <a href="{{ route('admin.transporte.create') }}" class="text-blue-600 hover:underline">Crear la primera</a>.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($rutas->hasPages())
            <div class="px-5 py-3 border-t border-gray-100 dark:border-gray-700">
                {{ $rutas->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
