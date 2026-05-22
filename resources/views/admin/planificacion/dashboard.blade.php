@extends('layouts.admin')

@section('page-title', 'Planificaciones Técnicas')

@section('content')
<div class="space-y-6">

    {{-- Encabezado --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Planificaciones Técnicas</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                @if($schoolYear)
                    Año escolar: <span class="font-medium text-gray-700 dark:text-gray-300">{{ $schoolYear->nombre }}</span>
                @else
                    <span class="text-amber-600">Sin año escolar activo</span>
                @endif
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            @if($schoolYear)
            <a href="{{ route('admin.planificacion.cumplimiento-excel') }}"
               class="inline-flex items-center gap-1.5 text-sm font-medium bg-white dark:bg-gray-800
                      border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300
                      rounded-lg px-3 py-2 hover:shadow-sm transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Cumplimiento Excel
            </a>
            <a href="{{ route('admin.planificacion.create-ra') }}"
               class="inline-flex items-center gap-1.5 text-sm font-medium bg-white dark:bg-gray-800
                      border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300
                      rounded-lg px-3 py-2 hover:shadow-sm transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Nueva por RA
            </a>
            <a href="{{ route('admin.planificacion.create-actividad') }}"
               class="inline-flex items-center gap-1.5 text-sm font-semibold bg-blue-600 hover:bg-blue-700
                      text-white rounded-lg px-4 py-2 shadow transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Nueva por Actividad
            </a>
            @endif
        </div>
    </div>

    {{-- Flash --}}
    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-700 rounded-lg px-4 py-3 text-sm">
            {{ session('success') }}
        </div>
    @endif

    {{-- Tarjetas de estadísticas --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4">

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Total</span>
                <div class="w-8 h-8 rounded-lg bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                    <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
            </div>
            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $total }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">planificaciones</p>
        </div>

        <a href="{{ route('admin.planificacion.index') }}"
           class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700
                  p-4 hover:shadow-md transition">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Publicadas</span>
                <div class="w-8 h-8 rounded-lg bg-green-100 dark:bg-green-900/30 flex items-center justify-center">
                    <svg class="w-4 h-4 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            <p class="text-2xl font-bold text-green-700 dark:text-green-400">{{ $publicadas }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">visibles a estudiantes</p>
        </a>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Borradores</span>
                <div class="w-8 h-8 rounded-lg bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center">
                    <svg class="w-4 h-4 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                    </svg>
                </div>
            </div>
            <p class="text-2xl font-bold text-amber-700 dark:text-amber-400">{{ $borradores }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">pendientes de publicar</p>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Por RA</span>
                <div class="w-8 h-8 rounded-lg bg-indigo-100 dark:bg-indigo-900/30 flex items-center justify-center">
                    <svg class="w-4 h-4 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                    </svg>
                </div>
            </div>
            <p class="text-2xl font-bold text-indigo-700 dark:text-indigo-400">{{ $porRa }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">resultados de aprendizaje</p>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Por Actividad</span>
                <div class="w-8 h-8 rounded-lg bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center">
                    <svg class="w-4 h-4 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                    </svg>
                </div>
            </div>
            <p class="text-2xl font-bold text-purple-700 dark:text-purple-400">{{ $porActividad }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">actividades de aprendizaje</p>
        </div>

    </div>

    {{-- Cuerpo --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Cumplimiento por asignaciones --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-5">
            <div class="flex items-center justify-between mb-1">
                <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Cumplimiento</h2>
                <span class="text-xs font-bold {{ $pctCumplimiento >= 80 ? 'text-green-600' : ($pctCumplimiento >= 50 ? 'text-amber-600' : 'text-red-600') }}">
                    {{ $pctCumplimiento }}%
                </span>
            </div>
            <p class="text-xs text-gray-400 mb-3">{{ $conPlan->count() }} de {{ $asignaciones->count() }} asignaciones técnicas</p>

            {{-- Barra de progreso --}}
            <div class="w-full bg-gray-100 dark:bg-gray-700 rounded-full h-2 mb-4">
                <div class="h-2 rounded-full {{ $pctCumplimiento >= 80 ? 'bg-green-500' : ($pctCumplimiento >= 50 ? 'bg-amber-500' : 'bg-red-500') }}"
                     style="width: {{ $pctCumplimiento }}%"></div>
            </div>

            @if($sinPlan->isNotEmpty())
                <p class="text-xs font-semibold text-red-600 dark:text-red-400 mb-2 flex items-center gap-1">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Sin planificación ({{ $sinPlan->count() }})
                </p>
                <div class="space-y-1 max-h-40 overflow-y-auto pr-1">
                    @foreach($sinPlan->take(10) as $asig)
                        <div class="text-xs text-gray-600 dark:text-gray-400 bg-red-50 dark:bg-red-900/10 rounded px-2 py-1">
                            {{ $asig->docente?->nombre_completo ?? 'Sin docente' }}
                            <span class="text-gray-400">·</span>
                            {{ $asig->asignatura?->nombre ?? '—' }}
                        </div>
                    @endforeach
                    @if($sinPlan->count() > 10)
                        <p class="text-xs text-gray-400 text-center pt-1">y {{ $sinPlan->count() - 10 }} más…</p>
                    @endif
                </div>
            @else
                <p class="text-xs text-green-600 dark:text-green-400 text-center py-3 font-medium">
                    ✓ Todas las asignaciones tienen planificación
                </p>
            @endif

            @if($schoolYear)
            <div class="mt-4 flex gap-2">
                <a href="{{ route('admin.planificacion.cumplimiento-pdf') }}"
                   class="flex-1 text-center text-xs font-medium bg-gray-100 hover:bg-gray-200 dark:bg-gray-700
                          dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg py-1.5 transition">
                    PDF
                </a>
                <a href="{{ route('admin.planificacion.cumplimiento-excel') }}"
                   class="flex-1 text-center text-xs font-medium bg-gray-100 hover:bg-gray-200 dark:bg-gray-700
                          dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg py-1.5 transition">
                    Excel
                </a>
            </div>
            @endif
        </div>

        {{-- Últimas planificaciones --}}
        <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between px-5 pt-5 pb-3 border-b border-gray-100 dark:border-gray-700">
                <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Últimas planificaciones</h2>
                <a href="{{ route('admin.planificacion.index') }}"
                   class="text-xs text-blue-600 dark:text-blue-400 hover:underline font-medium">Ver todas</a>
            </div>
            <div class="divide-y divide-gray-100 dark:divide-gray-700">
                @forelse($ultimas as $plan)
                    @php
                        $tipoLabel = $plan->tipo === 'ra' ? 'RA' : 'Actividad';
                        $tipoBadge = $plan->tipo === 'ra'
                            ? 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-300'
                            : 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-300';
                    @endphp
                    <div class="flex items-center justify-between px-5 py-3 hover:bg-gray-50 dark:hover:bg-gray-700/40 transition">
                        <div class="min-w-0">
                            <a href="{{ route('admin.planificacion.show', $plan) }}"
                               class="font-medium text-sm text-gray-800 dark:text-gray-200 hover:text-blue-600
                                      dark:hover:text-blue-400 truncate block">
                                {{ $plan->modulo_nombre ?? $plan->denominacion ?? 'Sin título' }}
                            </a>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                {{ $plan->asignacion?->docente?->nombre_completo ?? '—' }}
                                @if($plan->asignacion?->asignatura)
                                    · {{ $plan->asignacion->asignatura->nombre }}
                                @endif
                                @if($plan->asignacion?->grupo)
                                    · {{ $plan->asignacion->grupo->nombre_completo ?? $plan->asignacion->grupo->nombre ?? '' }}
                                @endif
                            </p>
                        </div>
                        <div class="ml-3 flex items-center gap-2 shrink-0">
                            <span class="text-xs font-medium px-2 py-0.5 rounded-full {{ $tipoBadge }}">
                                {{ $tipoLabel }}
                            </span>
                            @if($plan->publicado)
                                <span class="text-xs font-medium px-2 py-0.5 rounded-full
                                             bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300">
                                    Publicada
                                </span>
                            @else
                                <span class="text-xs font-medium px-2 py-0.5 rounded-full
                                             bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300">
                                    Borrador
                                </span>
                            @endif
                            <a href="{{ route('admin.planificacion.pdf', $plan) }}"
                               class="text-gray-400 hover:text-red-600 dark:hover:text-red-400 transition" title="Descargar PDF">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                </svg>
                            </a>
                        </div>
                    </div>
                @empty
                    <div class="px-5 py-8 text-center text-sm text-gray-400">
                        No hay planificaciones creadas aún.
                        @if($schoolYear)
                            <br>
                            <a href="{{ route('admin.planificacion.create-ra') }}"
                               class="text-blue-600 dark:text-blue-400 hover:underline mt-1 inline-block">
                                Crear la primera
                            </a>
                        @endif
                    </div>
                @endforelse
            </div>
        </div>

    </div>

</div>
@endsection
