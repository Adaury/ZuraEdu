@extends('layouts.admin')

@section('page-title', 'Gestión de Recursos y Aulas')

@section('content')
<div class="space-y-6">

    {{-- Encabezado --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 dark:text-white">Recursos y Aulas</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Gestión de espacios físicos y equipos disponibles</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('admin.recursos.disponibilidad') }}"
               class="inline-flex items-center gap-2 px-4 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 rounded-lg text-sm font-medium hover:bg-gray-50 dark:hover:bg-gray-600 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                Disponibilidad
            </a>
            <a href="{{ route('admin.recursos.create') }}"
               class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Nuevo Recurso
            </a>
        </div>
    </div>

    {{-- Alertas --}}
    @if(session('success'))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
             class="flex items-center gap-3 p-4 bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-700 rounded-lg text-green-800 dark:text-green-200 text-sm">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="flex items-center gap-3 p-4 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-700 rounded-lg text-red-800 dark:text-red-200 text-sm">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12A9 9 0 113 12a9 9 0 0118 0z"/>
            </svg>
            {{ session('error') }}
        </div>
    @endif

    {{-- Tarjetas resumen por tipo --}}
    @if($totales->isNotEmpty())
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-7 gap-3">
        @foreach($tipos as $key => $info)
            @php $count = $totales[$key] ?? 0; @endphp
            @if($count > 0)
            <a href="{{ route('admin.recursos.index', ['tipo' => $key]) }}"
               class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-3 text-center hover:shadow-md transition group">
                <div class="text-2xl font-bold text-{{ $info['color'] }}-600 dark:text-{{ $info['color'] }}-400 group-hover:scale-110 transition-transform">
                    {{ $count }}
                </div>
                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $info['label'] }}</div>
            </a>
            @endif
        @endforeach
    </div>
    @endif

    {{-- Filtros --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
        <form method="GET" action="{{ route('admin.recursos.index') }}" class="flex flex-wrap gap-3 items-end">
            <div class="flex-1 min-w-[180px]">
                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Buscar</label>
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Nombre o ubicación…"
                       class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div class="min-w-[150px]">
                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Tipo</label>
                <select name="tipo" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:ring-blue-500 focus:border-blue-500">
                    <option value="">Todos</option>
                    @foreach($tipos as $key => $info)
                        <option value="{{ $key }}" @selected(request('tipo') === $key)>{{ $info['label'] }}</option>
                    @endforeach
                </select>
            </div>
            <div class="min-w-[120px]">
                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Estado</label>
                <select name="activo" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:ring-blue-500 focus:border-blue-500">
                    <option value="">Todos</option>
                    <option value="1" @selected(request('activo') === '1')>Activos</option>
                    <option value="0" @selected(request('activo') === '0')>Inactivos</option>
                </select>
            </div>
            <div class="flex gap-2">
                <button type="submit"
                        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium transition">
                    Filtrar
                </button>
                @if(request()->hasAny(['q','tipo','activo']))
                <a href="{{ route('admin.recursos.index') }}"
                   class="px-4 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 rounded-lg text-sm font-medium transition">
                    Limpiar
                </a>
                @endif
            </div>
        </form>
    </div>

    {{-- Tabla --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden shadow-sm">
        @if($recursos->isEmpty())
            <div class="flex flex-col items-center justify-center py-16 text-gray-400 dark:text-gray-500">
                <svg class="w-14 h-14 mb-4 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                </svg>
                <p class="text-lg font-medium">No hay recursos registrados</p>
                <a href="{{ route('admin.recursos.create') }}" class="mt-3 text-blue-600 hover:underline text-sm">Agregar el primer recurso</a>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 dark:bg-gray-700/50 text-left">
                            <th class="px-4 py-3 font-semibold text-gray-600 dark:text-gray-300">Nombre</th>
                            <th class="px-4 py-3 font-semibold text-gray-600 dark:text-gray-300">Tipo</th>
                            <th class="px-4 py-3 font-semibold text-gray-600 dark:text-gray-300 hidden md:table-cell">Capacidad</th>
                            <th class="px-4 py-3 font-semibold text-gray-600 dark:text-gray-300 hidden lg:table-cell">Ubicación</th>
                            <th class="px-4 py-3 font-semibold text-gray-600 dark:text-gray-300 text-center">Estado</th>
                            <th class="px-4 py-3 font-semibold text-gray-600 dark:text-gray-300 text-center">Reservas hoy</th>
                            <th class="px-4 py-3 font-semibold text-gray-600 dark:text-gray-300 text-center">Pendientes</th>
                            <th class="px-4 py-3 font-semibold text-gray-600 dark:text-gray-300 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach($recursos as $recurso)
                            @php $tipoInfo = $recurso->tipo_info; @endphp
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition">
                                <td class="px-4 py-3 font-medium text-gray-800 dark:text-gray-100">
                                    {{ $recurso->nombre }}
                                    @if($recurso->descripcion)
                                        <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5 truncate max-w-[220px]">{{ $recurso->descripcion }}</p>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    @php
                                        $colorMap = [
                                            'blue'   => 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300',
                                            'purple' => 'bg-purple-100 text-purple-800 dark:bg-purple-900/40 dark:text-purple-300',
                                            'indigo' => 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/40 dark:text-indigo-300',
                                            'green'  => 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300',
                                            'yellow' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/40 dark:text-yellow-300',
                                            'orange' => 'bg-orange-100 text-orange-800 dark:bg-orange-900/40 dark:text-orange-300',
                                            'gray'   => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
                                        ];
                                        $badgeClass = $colorMap[$tipoInfo['color']] ?? $colorMap['gray'];
                                    @endphp
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $badgeClass }}">
                                        {{ $tipoInfo['label'] }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-300 hidden md:table-cell">
                                    {{ $recurso->capacidad ? $recurso->capacidad . ' pers.' : '—' }}
                                </td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-300 hidden lg:table-cell text-sm">
                                    {{ $recurso->ubicacion ?? '—' }}
                                </td>
                                <td class="px-4 py-3 text-center">
                                    @if($recurso->activo)
                                        <span class="inline-flex items-center gap-1 text-xs font-medium text-green-700 dark:text-green-400">
                                            <span class="w-1.5 h-1.5 rounded-full bg-green-500 inline-block"></span> Activo
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 text-xs font-medium text-gray-500 dark:text-gray-400">
                                            <span class="w-1.5 h-1.5 rounded-full bg-gray-400 inline-block"></span> Inactivo
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-center">
                                    @if($recurso->reservas_hoy > 0)
                                        <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300 text-xs font-bold">
                                            {{ $recurso->reservas_hoy }}
                                        </span>
                                    @else
                                        <span class="text-gray-400 dark:text-gray-500 text-xs">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-center">
                                    @if($recurso->reservas_pendientes > 0)
                                        <span class="inline-flex items-center justify-center px-2 py-0.5 rounded-full bg-yellow-100 dark:bg-yellow-900/40 text-yellow-800 dark:text-yellow-300 text-xs font-bold">
                                            {{ $recurso->reservas_pendientes }}
                                        </span>
                                    @else
                                        <span class="text-gray-400 dark:text-gray-500 text-xs">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <a href="{{ route('admin.recursos.reservas', $recurso) }}"
                                           title="Ver reservas"
                                           class="p-1.5 text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/30 rounded-lg transition">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                      d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                            </svg>
                                        </a>
                                        <a href="{{ route('admin.recursos.edit', $recurso) }}"
                                           title="Editar"
                                           class="p-1.5 text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                      d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                        </a>
                                        <form method="POST" action="{{ route('admin.recursos.destroy', $recurso) }}"
                                              x-data
                                              @submit.prevent="if(confirm('¿Eliminar este recurso?')) $el.submit()">
                                            @csrf @method('DELETE')
                                            <button type="submit" title="Eliminar"
                                                    class="p-1.5 text-red-500 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/30 rounded-lg transition">
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
            @if($recursos->hasPages())
                <div class="px-4 py-3 border-t border-gray-100 dark:border-gray-700">
                    {{ $recursos->links() }}
                </div>
            @endif
        @endif
    </div>
</div>
@endsection
