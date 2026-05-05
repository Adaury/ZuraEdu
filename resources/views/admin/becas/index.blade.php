@extends('layouts.admin')

@section('page-title', 'Becas y Descuentos')

@section('content')
<div class="space-y-6">

    {{-- Cabecera --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 dark:text-white flex items-center gap-2">
                <svg class="w-7 h-7 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Becas y Descuentos
            </h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Gestión de becas y descuentos aplicables a las cuotas escolares.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.becas.becados') }}"
               class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 text-sm font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                Lista de becados
            </a>
            <a href="{{ route('admin.becas.reporte-pdf') }}" target="_blank"
               class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg border border-red-300 text-sm font-medium text-red-700 bg-red-50 hover:bg-red-100 transition dark:bg-red-900/20 dark:text-red-400 dark:border-red-700">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                </svg>
                Reporte PDF
            </a>
            <a href="{{ route('admin.becas.create') }}"
               class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-indigo-600 text-sm font-semibold text-white hover:bg-indigo-700 transition shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Nueva beca
            </a>
        </div>
    </div>

    {{-- Alertas --}}
    @if(session('success'))
        <div class="flex items-center gap-2 p-3 rounded-lg bg-green-50 border border-green-200 text-green-800 dark:bg-green-900/20 dark:border-green-700 dark:text-green-300">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="flex items-center gap-2 p-3 rounded-lg bg-red-50 border border-red-200 text-red-800 dark:bg-red-900/20 dark:border-red-700 dark:text-red-300">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            {{ session('error') }}
        </div>
    @endif

    {{-- Stats --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 flex items-center gap-4">
            <div class="w-12 h-12 rounded-full bg-indigo-100 dark:bg-indigo-900/30 flex items-center justify-center flex-shrink-0">
                <svg class="w-6 h-6 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                </svg>
            </div>
            <div>
                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['total'] }}</p>
                <p class="text-sm text-gray-500 dark:text-gray-400">Becas registradas</p>
            </div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 flex items-center gap-4">
            <div class="w-12 h-12 rounded-full bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center flex-shrink-0">
                <svg class="w-6 h-6 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
            </div>
            <div>
                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['activas'] }}</p>
                <p class="text-sm text-gray-500 dark:text-gray-400">Becas activas</p>
            </div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 flex items-center gap-4">
            <div class="w-12 h-12 rounded-full bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center flex-shrink-0">
                <svg class="w-6 h-6 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                </svg>
            </div>
            <div>
                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['becados'] }}</p>
                <p class="text-sm text-gray-500 dark:text-gray-400">Becados activos</p>
            </div>
        </div>
    </div>

    {{-- Filtros --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
        <form method="GET" action="{{ route('admin.becas.index') }}" class="flex flex-wrap gap-3 items-end">
            <div class="flex-1 min-w-[180px]">
                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Buscar</label>
                <input type="text" name="buscar" value="{{ request('buscar') }}" placeholder="Nombre o criterio..."
                       class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm px-3 py-2 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Tipo</label>
                <select name="tipo"
                        class="rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm px-3 py-2 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500">
                    <option value="">Todos</option>
                    <option value="porcentaje" {{ request('tipo') === 'porcentaje' ? 'selected' : '' }}>Porcentaje</option>
                    <option value="monto_fijo" {{ request('tipo') === 'monto_fijo' ? 'selected' : '' }}>Monto fijo</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Estado</label>
                <select name="estado"
                        class="rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm px-3 py-2 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500">
                    <option value="">Todos</option>
                    <option value="activo" {{ request('estado') === 'activo' ? 'selected' : '' }}>Activas</option>
                    <option value="inactivo" {{ request('estado') === 'inactivo' ? 'selected' : '' }}>Inactivas</option>
                </select>
            </div>
            <button type="submit"
                    class="px-4 py-2 rounded-lg bg-indigo-600 text-sm font-medium text-white hover:bg-indigo-700 transition">
                Filtrar
            </button>
            @if(request()->hasAny(['buscar','tipo','estado']))
                <a href="{{ route('admin.becas.index') }}"
                   class="px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 text-sm text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                    Limpiar
                </a>
            @endif
        </form>
    </div>

    {{-- Tabla --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 dark:bg-gray-700/50 border-b border-gray-200 dark:border-gray-600">
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Nombre</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Tipo / Valor</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Criterio</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Becados</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Estado</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse($becas as $beca)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition">
                        <td class="px-4 py-3">
                            <p class="font-semibold text-gray-900 dark:text-white">{{ $beca->nombre }}</p>
                            @if($beca->descripcion)
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 line-clamp-1">{{ $beca->descripcion }}</p>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold
                                {{ $beca->tipo === 'porcentaje' ? 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-300' : 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300' }}">
                                {{ $beca->tipo === 'porcentaje' ? $beca->valor . '%' : 'RD$ ' . number_format($beca->valor, 2) }}
                            </span>
                            <p class="text-xs text-gray-400 mt-1">{{ $beca->tipo === 'porcentaje' ? 'Porcentaje' : 'Monto fijo' }}</p>
                        </td>
                        <td class="px-4 py-3 text-gray-600 dark:text-gray-300 text-xs">
                            {{ $beca->criterio ?? '—' }}
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="inline-flex items-center justify-center w-8 h-8 rounded-full
                                {{ $beca->becados_count > 0 ? 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300' : 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400' }}
                                text-sm font-bold">
                                {{ $beca->becados_count }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($beca->activo)
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300 text-xs font-semibold">
                                    <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span> Activa
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400 text-xs font-semibold">
                                    <span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span> Inactiva
                                </span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('admin.becas.edit', $beca) }}"
                                   class="p-1.5 rounded-lg text-gray-500 hover:bg-indigo-50 hover:text-indigo-600 dark:hover:bg-indigo-900/30 dark:hover:text-indigo-400 transition"
                                   title="Editar">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </a>
                                <form method="POST" action="{{ route('admin.becas.destroy', $beca) }}"
                                      onsubmit="return confirm('¿Eliminar esta beca?')">
                                    @csrf @method('DELETE')
                                    <button type="submit"
                                            class="p-1.5 rounded-lg text-gray-500 hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-900/30 dark:hover:text-red-400 transition"
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
                    @empty
                    <tr>
                        <td colspan="6" class="px-4 py-10 text-center text-gray-400 dark:text-gray-500">
                            <svg class="w-10 h-10 mx-auto mb-2 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                      d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            No hay becas registradas.
                            <a href="{{ route('admin.becas.create') }}" class="text-indigo-500 underline ml-1">Crear primera beca</a>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($becas->hasPages())
        <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700">
            {{ $becas->links() }}
        </div>
        @endif
    </div>

</div>
@endsection
