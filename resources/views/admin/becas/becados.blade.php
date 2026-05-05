@extends('layouts.admin')

@section('page-title', 'Lista de Becados')

@section('content')
<div class="space-y-6" x-data="{ modalAsignar: false }">

    {{-- Cabecera --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.becas.index') }}"
               class="p-2 rounded-lg text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-gray-800 dark:text-white">Lista de Becados</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                    Año escolar: <strong>{{ $syActual?->nombre ?? '—' }}</strong>
                </p>
            </div>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('admin.becas.reporte-pdf') }}" target="_blank"
               class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg border border-red-300 text-sm font-medium text-red-700 bg-red-50 hover:bg-red-100 transition dark:bg-red-900/20 dark:text-red-400 dark:border-red-700">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                </svg>
                Reporte PDF
            </a>
            <button @click="modalAsignar = true"
                    class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-indigo-600 text-sm font-semibold text-white hover:bg-indigo-700 transition shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Asignar beca
            </button>
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

    {{-- Filtros --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
        <form method="GET" action="{{ route('admin.becas.becados') }}" class="flex flex-wrap gap-3 items-end">
            <div class="flex-1 min-w-[180px]">
                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Buscar estudiante</label>
                <input type="text" name="buscar" value="{{ request('buscar') }}" placeholder="Nombre o apellido..."
                       class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm px-3 py-2 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Beca</label>
                <select name="beca_id"
                        class="rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm px-3 py-2 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500">
                    <option value="">Todas</option>
                    @foreach($becas as $b)
                        <option value="{{ $b->id }}" {{ request('beca_id') == $b->id ? 'selected' : '' }}>{{ $b->nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Estado</label>
                <select name="activo"
                        class="rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm px-3 py-2 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500">
                    <option value="">Todos</option>
                    <option value="1" {{ request('activo') === '1' ? 'selected' : '' }}>Activos</option>
                    <option value="0" {{ request('activo') === '0' ? 'selected' : '' }}>Revocados</option>
                </select>
            </div>
            <button type="submit"
                    class="px-4 py-2 rounded-lg bg-indigo-600 text-sm font-medium text-white hover:bg-indigo-700 transition">
                Filtrar
            </button>
            @if(request()->hasAny(['buscar','beca_id','activo']))
                <a href="{{ route('admin.becas.becados') }}"
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
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Estudiante</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Grupo</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Beca</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Descuento</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Vigencia</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Estado</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse($becados as $be)
                    @php
                        $est = $be->matricula?->estudiante;
                        $grp = $be->matricula?->grupo;
                    @endphp
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition {{ !$be->activo ? 'opacity-60' : '' }}">
                        <td class="px-4 py-3">
                            <p class="font-semibold text-gray-900 dark:text-white">
                                {{ $est?->apellidos ?? $est?->apellido ?? '—' }}, {{ $est?->nombres ?? $est?->nombre ?? '—' }}
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $est?->matricula ?? '' }}</p>
                        </td>
                        <td class="px-4 py-3 text-gray-600 dark:text-gray-300">
                            {{ $grp?->grado?->nombre ?? '—' }} {{ $grp?->seccion?->nombre ?? '' }}
                        </td>
                        <td class="px-4 py-3">
                            <p class="font-medium text-gray-900 dark:text-white">{{ $be->beca?->nombre ?? '—' }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $be->beca?->tipo === 'porcentaje' ? 'Porcentaje' : 'Monto fijo' }}</p>
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($be->beca)
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold
                                    {{ $be->beca->tipo === 'porcentaje' ? 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-300' : 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300' }}">
                                    {{ $be->beca->tipo === 'porcentaje' ? $be->beca->valor . '%' : $mon . ' ' . number_format($be->beca->valor, 2) }}
                                </span>
                            @else
                                —
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center text-xs text-gray-600 dark:text-gray-300">
                            <span>{{ $be->fecha_inicio?->format('d/m/Y') ?? '—' }}</span>
                            @if($be->fecha_fin)
                                <br><span class="text-gray-400">al {{ $be->fecha_fin->format('d/m/Y') }}</span>
                            @else
                                <br><span class="text-gray-400 italic">Sin fin</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($be->activo)
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300 text-xs font-semibold">
                                    <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span> Activa
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400 text-xs font-semibold">
                                    <span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span> Revocada
                                </span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            @if($be->activo)
                                <form method="POST" action="{{ route('admin.becas.revocar', $be) }}"
                                      onsubmit="return confirm('¿Revocar esta beca al estudiante?')">
                                    @csrf @method('DELETE')
                                    <button type="submit"
                                            class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-medium text-red-600 border border-red-300 hover:bg-red-50 dark:hover:bg-red-900/20 transition">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                                        </svg>
                                        Revocar
                                    </button>
                                </form>
                            @else
                                <span class="text-xs text-gray-400">—</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-4 py-10 text-center text-gray-400 dark:text-gray-500">
                            <svg class="w-10 h-10 mx-auto mb-2 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                      d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                            </svg>
                            No hay becados registrados.
                            <button @click="modalAsignar = true" class="text-indigo-500 underline ml-1">Asignar primera beca</button>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($becados->hasPages())
        <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700">
            {{ $becados->links() }}
        </div>
        @endif
    </div>

    {{-- Modal Asignar Beca --}}
    <div x-show="modalAsignar"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
         style="display:none">

        <div @click.outside="modalAsignar = false"
             class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl w-full max-w-lg">

            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Asignar Beca a Estudiante</h2>
                <button @click="modalAsignar = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <form method="POST" action="{{ route('admin.becas.asignar') }}" class="p-6 space-y-4">
                @csrf

                @if($errors->any())
                <div class="p-3 rounded-lg bg-red-50 border border-red-200 dark:bg-red-900/20 dark:border-red-700">
                    <ul class="text-xs text-red-700 dark:text-red-300 space-y-1 list-disc list-inside">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif

                {{-- Beca --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Beca <span class="text-red-500">*</span>
                    </label>
                    <select name="beca_id" required
                            class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm px-3 py-2.5 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500">
                        <option value="">Seleccionar beca...</option>
                        @foreach($becas->where('activo', true) as $b)
                            <option value="{{ $b->id }}" {{ old('beca_id') == $b->id ? 'selected' : '' }}>
                                {{ $b->nombre }} — {{ $b->tipo === 'porcentaje' ? $b->valor . '%' : 'RD$ ' . number_format($b->valor, 2) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Matrícula --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Estudiante (matrícula) <span class="text-red-500">*</span>
                    </label>
                    <select name="matricula_id" required
                            class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm px-3 py-2.5 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500">
                        <option value="">Seleccionar estudiante...</option>
                        @foreach(\App\Models\Matricula::with(['estudiante', 'grupo.grado', 'grupo.seccion'])
                            ->where('school_year_id', $syActual?->id)
                            ->where('estado', 'activo')
                            ->get()
                            ->sortBy('estudiante.apellidos') as $mat)
                            <option value="{{ $mat->id }}" {{ old('matricula_id') == $mat->id ? 'selected' : '' }}>
                                {{ $mat->estudiante?->apellidos }}, {{ $mat->estudiante?->nombres }}
                                — {{ $mat->grupo?->grado?->nombre ?? '' }} {{ $mat->grupo?->seccion?->nombre ?? '' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Fecha inicio <span class="text-red-500">*</span>
                        </label>
                        <input type="date" name="fecha_inicio" value="{{ old('fecha_inicio', today()->toDateString()) }}"
                               required
                               class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm px-3 py-2.5 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Fecha fin</label>
                        <input type="date" name="fecha_fin" value="{{ old('fecha_fin') }}"
                               class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm px-3 py-2.5 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Notas</label>
                    <textarea name="notas" rows="2" maxlength="500"
                              placeholder="Observaciones opcionales..."
                              class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm px-3 py-2.5 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 resize-none">{{ old('notas') }}</textarea>
                </div>

                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" @click="modalAsignar = false"
                            class="px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                        Cancelar
                    </button>
                    <button type="submit"
                            class="px-5 py-2 rounded-lg bg-indigo-600 text-sm font-semibold text-white hover:bg-indigo-700 transition">
                        Asignar beca
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Abrir modal automáticamente si hay errores de validación --}}
    @if($errors->any())
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const comp = document.querySelector('[x-data]').__x;
            if (comp) comp.$data.modalAsignar = true;
        });
    </script>
    @endif

</div>
@endsection
