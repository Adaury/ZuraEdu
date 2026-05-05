@extends('layouts.admin')

@section('page-title', 'Disponibilidad de Recursos')

@section('content')
<div class="space-y-6">

    {{-- Encabezado --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.recursos.index') }}"
               class="p-2 text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-gray-800 dark:text-white">Disponibilidad</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                    Estado de todos los recursos para
                    <strong class="text-gray-700 dark:text-gray-200">{{ $fecha->translatedFormat('l d \d\e F \d\e Y') }}</strong>
                </p>
            </div>
        </div>
    </div>

    {{-- Selector de fecha --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
        <form method="GET" action="{{ route('admin.recursos.disponibilidad') }}"
              class="flex flex-wrap items-end gap-3">
            <div>
                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Fecha</label>
                <input type="date" name="fecha" value="{{ $fecha->toDateString() }}"
                       class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:ring-blue-500 focus:border-blue-500">
            </div>
            <button type="submit"
                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium transition">
                Ver disponibilidad
            </button>
            @if(!$fecha->isToday())
            <a href="{{ route('admin.recursos.disponibilidad') }}"
               class="px-4 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 rounded-lg text-sm font-medium transition">
                Hoy
            </a>
            @endif
            {{-- Navegar día a día --}}
            <div class="flex gap-1 ml-auto">
                <a href="{{ route('admin.recursos.disponibilidad', ['fecha' => $fecha->copy()->subDay()->toDateString()]) }}"
                   class="p-2 text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition" title="Día anterior">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </a>
                <a href="{{ route('admin.recursos.disponibilidad', ['fecha' => $fecha->copy()->addDay()->toDateString()]) }}"
                   class="p-2 text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition" title="Día siguiente">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
            </div>
        </form>
    </div>

    {{-- Leyenda --}}
    <div class="flex flex-wrap gap-4 text-xs text-gray-600 dark:text-gray-400">
        <div class="flex items-center gap-1.5">
            <span class="w-4 h-4 rounded bg-green-100 dark:bg-green-900/40 border border-green-300 dark:border-green-700 inline-block"></span>
            Libre
        </div>
        <div class="flex items-center gap-1.5">
            <span class="w-4 h-4 rounded bg-red-100 dark:bg-red-900/40 border border-red-300 dark:border-red-700 inline-block"></span>
            Ocupado (aprobado)
        </div>
        <div class="flex items-center gap-1.5">
            <span class="w-4 h-4 rounded bg-yellow-100 dark:bg-yellow-900/40 border border-yellow-300 dark:border-yellow-700 inline-block"></span>
            Pendiente de aprobación
        </div>
    </div>

    {{-- Grid de recursos --}}
    @if($recursos->isEmpty())
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-16 text-center text-gray-400 dark:text-gray-500">
            <p>No hay recursos activos registrados.</p>
            <a href="{{ route('admin.recursos.create') }}" class="mt-2 text-blue-600 hover:underline text-sm inline-block">Agregar recurso</a>
        </div>
    @else
        @php
            $tiposPresentes = $recursos->groupBy('tipo');
        @endphp
        @foreach($tiposPresentes as $tipo => $grupoRecursos)
            @php $tipoInfo = \App\Models\RecursoFisico::TIPOS[$tipo] ?? ['label' => $tipo, 'color' => 'gray']; @endphp
            <div class="space-y-3">
                <h2 class="text-sm font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wide flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-{{ $tipoInfo['color'] }}-500 inline-block"></span>
                    {{ $tipoInfo['label'] }} ({{ $grupoRecursos->count() }})
                </h2>
                <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-4">
                    @foreach($grupoRecursos as $recurso)
                        @php
                            $reservasHoy = $reservasPorRecurso[$recurso->id] ?? collect();
                            $totalOcupadas = 0;
                        @endphp
                        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden shadow-sm hover:shadow-md transition">
                            {{-- Cabecera del recurso --}}
                            <div class="px-4 py-3 bg-gray-50 dark:bg-gray-700/50 flex items-center justify-between border-b border-gray-100 dark:border-gray-700">
                                <div class="min-w-0">
                                    <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100 truncate">
                                        {{ $recurso->nombre }}
                                    </h3>
                                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">
                                        @if($recurso->capacidad) Cap. {{ $recurso->capacidad }} &middot; @endif
                                        {{ $recurso->ubicacion ?? 'Sin ubicación definida' }}
                                    </p>
                                </div>
                                <a href="{{ route('admin.recursos.reservas.create', $recurso) }}"
                                   class="flex-shrink-0 ml-2 p-1.5 text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/30 rounded-lg transition" title="Nueva reserva">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                    </svg>
                                </a>
                            </div>

                            {{-- Grilla de horas --}}
                            <div class="p-3">
                                @if($reservasHoy->isEmpty())
                                    <div class="text-center py-4 text-xs text-gray-400 dark:text-gray-500">
                                        <div class="text-2xl mb-1">✓</div>
                                        Todo el día disponible
                                    </div>
                                @else
                                    <div class="grid grid-cols-7 gap-0.5 mb-3">
                                        @foreach($horas as $hora)
                                            @php
                                                $horaFin  = sprintf('%02d:00', (int)explode(':', $hora)[0] + 1);
                                                $ocupada  = $reservasHoy->first(function ($r) use ($hora, $horaFin) {
                                                    return $r->hora_inicio < $horaFin && $r->hora_fin > $hora;
                                                });
                                                if ($ocupada) $totalOcupadas++;
                                            @endphp
                                            <div title="{{ $hora }}{{ $ocupada ? ' — '.$ocupada->motivo.' ('.$ocupada->solicitante?->name.')' : ' — Libre' }}"
                                                 class="h-5 rounded-sm text-center text-[9px] flex items-center justify-center font-mono cursor-default
                                                        {{ $ocupada
                                                            ? ($ocupada->estado === 'aprobada'
                                                                ? 'bg-red-200 dark:bg-red-900/60 text-red-700 dark:text-red-300'
                                                                : 'bg-yellow-200 dark:bg-yellow-900/60 text-yellow-700 dark:text-yellow-300')
                                                            : 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400'
                                                        }}">
                                                {{ (int)explode(':', $hora)[0] }}
                                            </div>
                                        @endforeach
                                    </div>
                                @endif

                                {{-- Detalle de reservas del día --}}
                                @if($reservasHoy->isNotEmpty())
                                <div class="space-y-1.5 mt-2 border-t border-gray-100 dark:border-gray-700 pt-2">
                                    @foreach($reservasHoy as $reserva)
                                        @php
                                            $isAprobada = $reserva->estado === 'aprobada';
                                            $borderColor = $isAprobada ? 'border-red-300 dark:border-red-700' : 'border-yellow-300 dark:border-yellow-700';
                                            $bgColor     = $isAprobada ? 'bg-red-50 dark:bg-red-900/20' : 'bg-yellow-50 dark:bg-yellow-900/20';
                                            $textColor   = $isAprobada ? 'text-red-700 dark:text-red-300' : 'text-yellow-700 dark:text-yellow-300';
                                        @endphp
                                        <div class="flex items-start gap-2 p-2 rounded-lg border {{ $borderColor }} {{ $bgColor }} text-xs">
                                            <div class="flex-1 min-w-0">
                                                <div class="font-medium {{ $textColor }} truncate">{{ $reserva->motivo }}</div>
                                                <div class="text-gray-500 dark:text-gray-400 font-mono">
                                                    {{ substr($reserva->hora_inicio,0,5) }} – {{ substr($reserva->hora_fin,0,5) }}
                                                    &middot; {{ $reserva->duracion }}
                                                </div>
                                                <div class="text-gray-400 dark:text-gray-500 truncate">{{ $reserva->solicitante?->name }}</div>
                                            </div>
                                            @if(!$isAprobada)
                                                <span class="flex-shrink-0 px-1.5 py-0.5 rounded bg-yellow-200 dark:bg-yellow-800 text-yellow-800 dark:text-yellow-200 font-medium">
                                                    Pend.
                                                </span>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                                @endif

                                {{-- Enlace a reservas --}}
                                <div class="mt-2 text-right">
                                    <a href="{{ route('admin.recursos.reservas', [$recurso, 'semana' => $fecha->toDateString()]) }}"
                                       class="text-xs text-blue-600 dark:text-blue-400 hover:underline">
                                        Ver semana completa →
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    @endif

</div>
@endsection
