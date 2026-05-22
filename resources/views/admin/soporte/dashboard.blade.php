@extends('layouts.admin')

@section('page-title', 'Tickets de Soporte')

@section('content')
<div class="space-y-6">

    {{-- Encabezado --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Tickets de Soporte</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                {{ $esAdmin ? 'Vista de administración — todos los tickets' : 'Mis tickets enviados' }}
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            @if($esAdmin)
            <a href="{{ route('admin.soporte.lista-excel') }}"
               class="inline-flex items-center gap-1.5 text-sm font-medium bg-white dark:bg-gray-800
                      border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300
                      rounded-lg px-3 py-2 hover:shadow-sm transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Excel
            </a>
            @endif
            <a href="{{ route('admin.soporte.index') }}"
               class="inline-flex items-center gap-1.5 text-sm font-medium bg-white dark:bg-gray-800
                      border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300
                      rounded-lg px-3 py-2 hover:shadow-sm transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M4 6h16M4 10h16M4 14h16M4 18h7"/>
                </svg>
                Lista completa
            </a>
            <a href="{{ route('admin.soporte.create') }}"
               class="inline-flex items-center gap-1.5 text-sm font-semibold bg-blue-600 hover:bg-blue-700
                      text-white rounded-lg px-4 py-2 shadow transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Nuevo ticket
            </a>
        </div>
    </div>

    {{-- Flash --}}
    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-700 rounded-lg px-4 py-3 text-sm">
            {{ session('success') }}
        </div>
    @endif

    {{-- Sin asignar (solo admins) --}}
    @if($esAdmin && $sinAsignar > 0)
        <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-700
                    text-amber-800 dark:text-amber-300 rounded-lg px-4 py-3 text-sm flex items-center gap-2">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span><strong>{{ $sinAsignar }}</strong> ticket{{ $sinAsignar !== 1 ? 's' : '' }} abierto{{ $sinAsignar !== 1 ? 's' : '' }} sin asignar.</span>
            <a href="{{ route('admin.soporte.index', ['estado' => 'abierto']) }}"
               class="ml-1 underline font-semibold hover:no-underline">Revisar</a>
        </div>
    @endif

    {{-- Tarjetas de estado --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">

        <a href="{{ route('admin.soporte.index', ['estado' => 'abierto']) }}"
           class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700
                  p-4 hover:shadow-md transition {{ $totalAbiertos > 0 ? 'ring-1 ring-green-300' : '' }}">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Abiertos</span>
                <div class="w-8 h-8 rounded-lg bg-green-100 dark:bg-green-900/30 flex items-center justify-center">
                    <svg class="w-4 h-4 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                </div>
            </div>
            <p class="text-2xl font-bold text-green-700 dark:text-green-400">{{ $totalAbiertos }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">sin respuesta</p>
        </a>

        <a href="{{ route('admin.soporte.index', ['estado' => 'en_proceso']) }}"
           class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700
                  p-4 hover:shadow-md transition">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">En proceso</span>
                <div class="w-8 h-8 rounded-lg bg-yellow-100 dark:bg-yellow-900/30 flex items-center justify-center">
                    <svg class="w-4 h-4 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                </div>
            </div>
            <p class="text-2xl font-bold text-yellow-700 dark:text-yellow-400">{{ $totalEnProceso }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">atendiendo</p>
        </a>

        <a href="{{ route('admin.soporte.index', ['estado' => 'resuelto']) }}"
           class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700
                  p-4 hover:shadow-md transition">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Resueltos</span>
                <div class="w-8 h-8 rounded-lg bg-indigo-100 dark:bg-indigo-900/30 flex items-center justify-center">
                    <svg class="w-4 h-4 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            <p class="text-2xl font-bold text-indigo-700 dark:text-indigo-400">{{ $totalResueltos }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">solucionados</p>
        </a>

        <a href="{{ route('admin.soporte.index') }}"
           class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700
                  p-4 hover:shadow-md transition">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Total</span>
                <div class="w-8 h-8 rounded-lg bg-gray-100 dark:bg-gray-700 flex items-center justify-center">
                    <svg class="w-4 h-4 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                </div>
            </div>
            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $total }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">tickets histórico</p>
        </a>

    </div>

    {{-- Cuerpo --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Columna lateral: categoría + prioridad --}}
        <div class="space-y-5">

            {{-- Por categoría --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-5">
                <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-4">Por categoría</h2>
                @forelse($porCategoria as $item)
                    @php
                        $pct = $total > 0 ? round(($item->total / $total) * 100) : 0;
                        $label = \App\Models\TicketSoporte::CATEGORIAS[$item->categoria] ?? ucfirst($item->categoria);
                    @endphp
                    <div class="mb-3">
                        <div class="flex justify-between text-xs text-gray-600 dark:text-gray-400 mb-1">
                            <span>{{ $label }}</span>
                            <span>{{ $item->total }} ({{ $pct }}%)</span>
                        </div>
                        <div class="w-full bg-gray-100 dark:bg-gray-700 rounded-full h-1.5">
                            <div class="bg-blue-500 h-1.5 rounded-full" style="width: {{ $pct }}%"></div>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-400 text-center py-3">Sin tickets aún</p>
                @endforelse
            </div>

            {{-- Abiertos por prioridad --}}
            @if($porPrioridad->isNotEmpty())
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-5">
                <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Activos por prioridad</h2>
                @php
                    $prioColores = [
                        'urgente' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300',
                        'alta'    => 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-300',
                        'media'   => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300',
                        'baja'    => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400',
                    ];
                @endphp
                @foreach($porPrioridad as $item)
                    @php $label = \App\Models\TicketSoporte::PRIORIDADES[$item->prioridad] ?? ucfirst($item->prioridad); @endphp
                    <div class="flex items-center justify-between py-1.5 text-sm">
                        <span class="text-xs font-medium px-2 py-0.5 rounded-full {{ $prioColores[$item->prioridad] ?? 'bg-gray-100 text-gray-600' }}">
                            {{ $label }}
                        </span>
                        <span class="font-bold text-gray-800 dark:text-gray-200">{{ $item->total }}</span>
                    </div>
                @endforeach
            </div>
            @endif

        </div>

        {{-- Tickets recientes --}}
        <div class="lg:col-span-2 space-y-5">

            {{-- Urgentes activos --}}
            @if($urgentes->isNotEmpty())
            <div class="bg-red-50 dark:bg-red-900/10 rounded-xl border border-red-200 dark:border-red-800">
                <div class="flex items-center gap-2 px-5 pt-4 pb-3 border-b border-red-100 dark:border-red-800">
                    <svg class="w-4 h-4 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <h2 class="text-sm font-semibold text-red-700 dark:text-red-400">Urgentes sin resolver</h2>
                </div>
                <div class="divide-y divide-red-100 dark:divide-red-800/50">
                    @foreach($urgentes as $ticket)
                        <div class="flex items-center justify-between px-5 py-2.5">
                            <div class="min-w-0">
                                <a href="{{ route('admin.soporte.show', $ticket) }}"
                                   class="text-sm font-medium text-red-800 dark:text-red-300 hover:underline truncate block">
                                    {{ $ticket->titulo }}
                                </a>
                                <p class="text-xs text-red-500 dark:text-red-400">
                                    {{ $ticket->solicitante?->name ?? '—' }}
                                    · {{ $ticket->created_at->diffForHumans() }}
                                </p>
                            </div>
                            <span class="ml-3 shrink-0 text-xs font-medium px-2 py-0.5 rounded-full
                                         {{ \App\Models\TicketSoporte::COLORES_ESTADO[$ticket->estado] ?? '' }}">
                                {{ \App\Models\TicketSoporte::ESTADOS[$ticket->estado] ?? $ticket->estado }}
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Tickets recientes --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between px-5 pt-5 pb-3 border-b border-gray-100 dark:border-gray-700">
                    <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Tickets recientes</h2>
                    <a href="{{ route('admin.soporte.index') }}"
                       class="text-xs text-blue-600 dark:text-blue-400 hover:underline font-medium">Ver todos</a>
                </div>
                <div class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse($recientes as $ticket)
                        <div class="flex items-center justify-between px-5 py-3 hover:bg-gray-50
                                    dark:hover:bg-gray-700/40 transition">
                            <div class="min-w-0 flex-1">
                                <a href="{{ route('admin.soporte.show', $ticket) }}"
                                   class="font-medium text-sm text-gray-800 dark:text-gray-200 hover:text-blue-600
                                          dark:hover:text-blue-400 truncate block">
                                    {{ $ticket->titulo }}
                                </a>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                    {{ $ticket->solicitante?->name ?? '—' }}
                                    @if($ticket->asignadoA)
                                        → <span class="text-blue-500">{{ $ticket->asignadoA->name }}</span>
                                    @endif
                                    · {{ $ticket->created_at->diffForHumans() }}
                                </p>
                            </div>
                            <div class="ml-3 flex items-center gap-1.5 shrink-0">
                                @php
                                    $prioBadge = \App\Models\TicketSoporte::COLORES_PRIORIDAD[$ticket->prioridad] ?? 'bg-gray-100 text-gray-600';
                                    $estBadge  = \App\Models\TicketSoporte::COLORES_ESTADO[$ticket->estado] ?? 'bg-gray-100 text-gray-500';
                                @endphp
                                <span class="text-xs px-1.5 py-0.5 rounded-full {{ $prioBadge }}">
                                    {{ \App\Models\TicketSoporte::PRIORIDADES[$ticket->prioridad] ?? '' }}
                                </span>
                                <span class="text-xs px-1.5 py-0.5 rounded-full {{ $estBadge }}">
                                    {{ \App\Models\TicketSoporte::ESTADOS[$ticket->estado] ?? '' }}
                                </span>
                            </div>
                        </div>
                    @empty
                        <div class="px-5 py-8 text-center text-sm text-gray-400">
                            No hay tickets aún.
                            <a href="{{ route('admin.soporte.create') }}"
                               class="text-blue-600 dark:text-blue-400 hover:underline ml-1">Crear el primero</a>
                        </div>
                    @endforelse
                </div>
            </div>

        </div>

    </div>

</div>
@endsection
