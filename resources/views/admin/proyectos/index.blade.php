@extends('layouts.admin')

@section('page-title', 'Proyectos Escolares')

@section('content')
<div class="space-y-6">

    {{-- Encabezado --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Proyectos Escolares</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                Gestión de proyectos académicos por área
                @if($schoolYear) — {{ $schoolYear->nombre }} @endif
            </p>
        </div>
        <a href="{{ route('admin.proyectos.create') }}"
           class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-lg shadow transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Nuevo Proyecto
        </a>
    </div>

    {{-- Tarjetas de estado --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        @foreach(['planificacion' => ['Planificación','yellow','📋'], 'desarrollo' => ['Desarrollo','blue','⚙️'], 'finalizado' => ['Finalizado','green','✅'], 'presentado' => ['Presentado','indigo','🎤']] as $key => [$label, $color, $icon])
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 flex items-center gap-3">
            <span class="text-2xl">{{ $icon }}</span>
            <div>
                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $label }}</p>
                <p class="text-2xl font-bold text-{{ $color }}-600 dark:text-{{ $color }}-400">
                    {{ $totalesEstado[$key] ?? 0 }}
                </p>
            </div>
        </div>
        @endforeach
    </div>

    {{-- Filtros --}}
    <form method="GET" action="{{ route('admin.proyectos.index') }}"
          class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">

            {{-- Búsqueda --}}
            <div class="lg:col-span-2">
                <input type="text" name="q" value="{{ request('q') }}"
                       placeholder="Buscar por título o descripción..."
                       class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:ring-indigo-500 focus:border-indigo-500">
            </div>

            {{-- Área --}}
            <div>
                <select name="area"
                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">Todas las áreas</option>
                    @foreach($areas as $key => $label)
                        <option value="{{ $key }}" @selected(request('area') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Estado --}}
            <div>
                <select name="estado"
                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">Todos los estados</option>
                    @foreach($estados as $key => $label)
                        <option value="{{ $key }}" @selected(request('estado') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Año escolar --}}
            <div>
                <select name="school_year_id"
                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">Todos los años</option>
                    @foreach($schoolYears as $sy)
                        <option value="{{ $sy->id }}" @selected((string)$yearId === (string)$sy->id)>{{ $sy->nombre }}</option>
                    @endforeach
                </select>
            </div>

        </div>
        <div class="flex gap-2 mt-3">
            <button type="submit"
                    class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition">
                Filtrar
            </button>
            <a href="{{ route('admin.proyectos.index') }}"
               class="px-4 py-2 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 text-sm font-medium rounded-lg transition">
                Limpiar
            </a>
        </div>
    </form>

    {{-- Tabla --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
        @if($proyectos->isEmpty())
            <div class="flex flex-col items-center justify-center py-16 text-gray-400">
                <svg class="w-16 h-16 mb-3 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414A1 1 0 0119 9.414V19a2 2 0 01-2 2z"/>
                </svg>
                <p class="text-sm font-medium">No se encontraron proyectos</p>
                <a href="{{ route('admin.proyectos.create') }}" class="mt-2 text-indigo-600 hover:underline text-sm">Crear el primero</a>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700/50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Proyecto</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Área</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Estado</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Tutor</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Integrantes</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Fechas</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach($proyectos as $proyecto)
                        @php
                            $areaColor   = \App\Models\ProyectoEscolar::AREA_COLORS[$proyecto->area]   ?? 'gray';
                            $estadoColor = \App\Models\ProyectoEscolar::ESTADO_COLORS[$proyecto->estado] ?? 'gray';
                        @endphp
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition">
                            <td class="px-4 py-3">
                                <p class="font-semibold text-gray-900 dark:text-white text-sm">{{ $proyecto->titulo }}</p>
                                @if($proyecto->descripcion)
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 line-clamp-1">
                                        {{ Str::limit($proyecto->descripcion, 60) }}
                                    </p>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold
                                    bg-{{ $areaColor }}-100 text-{{ $areaColor }}-800
                                    dark:bg-{{ $areaColor }}-900/30 dark:text-{{ $areaColor }}-300">
                                    {{ $proyecto->area_label }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold
                                    bg-{{ $estadoColor }}-100 text-{{ $estadoColor }}-800
                                    dark:bg-{{ $estadoColor }}-900/30 dark:text-{{ $estadoColor }}-300">
                                    {{ $proyecto->estado_label }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                                {{ $proyecto->tutor->name }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                                    {{ $proyecto->integrantes_count }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-xs text-gray-500 dark:text-gray-400">
                                <div>Inicio: {{ $proyecto->fecha_inicio->format('d/m/Y') }}</div>
                                @if($proyecto->fecha_fin)
                                    <div>Fin: {{ $proyecto->fecha_fin->format('d/m/Y') }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-end gap-1">
                                    <a href="{{ route('admin.proyectos.show', $proyecto) }}"
                                       class="p-1.5 rounded-lg text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 transition"
                                       title="Ver detalle">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M15 12a3 3 0 11-6 0 3 3 0 016 0zM2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                    </a>
                                    <a href="{{ route('admin.proyectos.edit', $proyecto) }}"
                                       class="p-1.5 rounded-lg text-amber-600 hover:bg-amber-50 dark:hover:bg-amber-900/30 transition"
                                       title="Editar">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </a>
                                    <form method="POST" action="{{ route('admin.proyectos.destroy', $proyecto) }}"
                                          onsubmit="return confirm('¿Eliminar este proyecto?')">
                                        @csrf @method('DELETE')
                                        <button type="submit"
                                                class="p-1.5 rounded-lg text-red-600 hover:bg-red-50 dark:hover:bg-red-900/30 transition"
                                                title="Eliminar">
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
            @if($proyectos->hasPages())
            <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700">
                {{ $proyectos->links() }}
            </div>
            @endif
        @endif
    </div>

</div>

{{-- Flash messages --}}
@if(session('success'))
<div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3500)"
     x-transition
     class="fixed bottom-5 right-5 bg-green-600 text-white px-5 py-3 rounded-xl shadow-lg text-sm font-medium z-50">
    {{ session('success') }}
</div>
@endif
@endsection
