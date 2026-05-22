@extends('layouts.admin')

@section('page-title', 'Inventario TI')

@section('content')
<div class="space-y-6">

    {{-- Encabezado --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Inventario TI</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Equipos tecnológicos y préstamos</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <form method="POST" action="{{ route('admin.equipos.verificar-vencidos') }}" class="inline">
                @csrf
                <button type="submit"
                        class="inline-flex items-center gap-1.5 text-sm font-medium bg-amber-50 hover:bg-amber-100
                               text-amber-700 border border-amber-200 rounded-lg px-3 py-2 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Verificar vencidos
                </button>
            </form>
            <a href="{{ route('admin.equipos.prestamos.create') }}"
               class="inline-flex items-center gap-1.5 text-sm font-medium bg-white dark:bg-gray-800
                      border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300
                      rounded-lg px-3 py-2 hover:shadow-sm transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                </svg>
                Nuevo préstamo
            </a>
            <a href="{{ route('admin.equipos.create') }}"
               class="inline-flex items-center gap-1.5 text-sm font-semibold bg-blue-600 hover:bg-blue-700
                      text-white rounded-lg px-4 py-2 shadow transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Nuevo equipo
            </a>
        </div>
    </div>

    {{-- Flash --}}
    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-700 rounded-lg px-4 py-3 text-sm">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="bg-red-50 border border-red-200 text-red-700 rounded-lg px-4 py-3 text-sm">
            {{ session('error') }}
        </div>
    @endif

    {{-- Estadísticas --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-7 gap-4">

        <a href="{{ route('admin.equipos.index') }}"
           class="sm:col-span-2 lg:col-span-1 bg-white dark:bg-gray-800 rounded-xl shadow-sm
                  border border-gray-200 dark:border-gray-700 p-4 hover:shadow-md transition">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Total</span>
                <div class="w-8 h-8 rounded-lg bg-gray-100 dark:bg-gray-700 flex items-center justify-center">
                    <svg class="w-4 h-4 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M9 3H5a2 2 0 00-2 2v4m6-6h10a2 2 0 012 2v4M9 3v18m0 0h10a2 2 0 002-2V9M9 21H5a2 2 0 01-2-2V9m0 0h18"/>
                    </svg>
                </div>
            </div>
            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $totalEquipos }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">equipos</p>
        </a>

        <a href="{{ route('admin.equipos.index', ['estado' => 'disponible']) }}"
           class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700
                  p-4 hover:shadow-md transition">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Disponibles</span>
                <div class="w-8 h-8 rounded-lg bg-green-100 dark:bg-green-900/30 flex items-center justify-center">
                    <svg class="w-4 h-4 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            <p class="text-2xl font-bold text-green-700 dark:text-green-400">{{ $totalDisponibles }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">para préstamo</p>
        </a>

        <a href="{{ route('admin.equipos.index', ['estado' => 'prestado']) }}"
           class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700
                  p-4 hover:shadow-md transition">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Prestados</span>
                <div class="w-8 h-8 rounded-lg bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                    <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                    </svg>
                </div>
            </div>
            <p class="text-2xl font-bold text-blue-700 dark:text-blue-400">{{ $totalPrestados }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">en uso</p>
        </a>

        <a href="{{ route('admin.equipos.index', ['estado' => 'mantenimiento']) }}"
           class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700
                  p-4 hover:shadow-md transition">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Mant.</span>
                <div class="w-8 h-8 rounded-lg bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center">
                    <svg class="w-4 h-4 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
            </div>
            <p class="text-2xl font-bold text-amber-700 dark:text-amber-400">{{ $totalMantenimiento }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">mantenimiento</p>
        </a>

        <a href="{{ route('admin.equipos.index', ['estado' => 'baja']) }}"
           class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700
                  p-4 hover:shadow-md transition">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Baja</span>
                <div class="w-8 h-8 rounded-lg bg-gray-100 dark:bg-gray-700 flex items-center justify-center">
                    <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                    </svg>
                </div>
            </div>
            <p class="text-2xl font-bold text-gray-600 dark:text-gray-400">{{ $totalBaja }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">de baja</p>
        </a>

        <a href="{{ route('admin.equipos.prestamos.index', ['estado' => 'activo']) }}"
           class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700
                  p-4 hover:shadow-md transition">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">P. activos</span>
                <div class="w-8 h-8 rounded-lg bg-indigo-100 dark:bg-indigo-900/30 flex items-center justify-center">
                    <svg class="w-4 h-4 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                </div>
            </div>
            <p class="text-2xl font-bold text-indigo-700 dark:text-indigo-400">{{ $prestamosActivos }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">préstamos</p>
        </a>

        <a href="{{ route('admin.equipos.prestamos.index', ['estado' => 'vencido']) }}"
           class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700
                  p-4 hover:shadow-md transition {{ $prestamosVencidos > 0 ? 'ring-1 ring-red-300' : '' }}">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">P. vencidos</span>
                <div class="w-8 h-8 rounded-lg bg-red-100 dark:bg-red-900/30 flex items-center justify-center">
                    <svg class="w-4 h-4 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            <p class="text-2xl font-bold {{ $prestamosVencidos > 0 ? 'text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-white' }}">
                {{ $prestamosVencidos }}
            </p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">vencidos</p>
        </a>

    </div>

    {{-- Contenido principal --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Por tipo --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-5">
            <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-4">Equipos por tipo</h2>
            @forelse($porTipo as $item)
                @php
                    $pct = $totalEquipos > 0 ? round(($item->total / $totalEquipos) * 100) : 0;
                    $label = \App\Models\Equipo::TIPOS[$item->tipo] ?? ucfirst($item->tipo);
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
                <p class="text-sm text-gray-400 text-center py-4">Sin equipos registrados</p>
            @endforelse
            <a href="{{ route('admin.equipos.index') }}"
               class="mt-2 block text-center text-xs text-blue-600 dark:text-blue-400 hover:underline font-medium">
                Ver inventario completo →
            </a>
        </div>

        {{-- Últimos préstamos --}}
        <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between px-5 pt-5 pb-3 border-b border-gray-100 dark:border-gray-700">
                <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Últimos préstamos</h2>
                <a href="{{ route('admin.equipos.prestamos.index') }}"
                   class="text-xs text-blue-600 dark:text-blue-400 hover:underline font-medium">Ver todos</a>
            </div>
            <div class="divide-y divide-gray-100 dark:divide-gray-700">
                @forelse($ultimosPrestamos as $prestamo)
                    @php
                        $badge = match($prestamo->estado) {
                            'activo'   => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300',
                            'devuelto' => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300',
                            'vencido'  => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300',
                            default    => 'bg-gray-100 text-gray-600',
                        };
                        $label = match($prestamo->estado) {
                            'activo'   => 'Activo',
                            'devuelto' => 'Devuelto',
                            'vencido'  => 'Vencido',
                            default    => $prestamo->estado,
                        };
                    @endphp
                    <div class="flex items-center justify-between px-5 py-3 text-sm hover:bg-gray-50 dark:hover:bg-gray-700/40 transition">
                        <div class="min-w-0">
                            <p class="font-medium text-gray-800 dark:text-gray-200 truncate">
                                {{ $prestamo->equipo?->nombre ?? '—' }}
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $prestamo->usuario?->name ?? '—' }}
                                · {{ $prestamo->fecha_prestamo?->format('d/m/Y') }}
                                @if($prestamo->fecha_vencimiento)
                                    → {{ $prestamo->fecha_vencimiento->format('d/m/Y') }}
                                @endif
                            </p>
                        </div>
                        <div class="ml-3 flex items-center gap-2 shrink-0">
                            @if($prestamo->estado === 'activo')
                                <a href="{{ route('admin.equipos.prestamos.comprobante', $prestamo) }}"
                                   class="text-xs text-gray-400 hover:text-gray-600 transition" title="Comprobante PDF">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                    </svg>
                                </a>
                            @endif
                            <span class="text-xs font-medium px-2 py-0.5 rounded-full {{ $badge }}">
                                {{ $label }}
                            </span>
                        </div>
                    </div>
                @empty
                    <div class="px-5 py-8 text-center text-sm text-gray-400">
                        No hay préstamos registrados aún.
                    </div>
                @endforelse
            </div>
        </div>

    </div>

    {{-- Acciones rápidas --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <a href="{{ route('admin.equipos.create') }}"
           class="flex flex-col items-center gap-2 bg-white dark:bg-gray-800 rounded-xl border border-gray-200
                  dark:border-gray-700 p-4 text-center hover:shadow-md hover:border-blue-300 transition group">
            <div class="w-10 h-10 rounded-xl bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center
                        group-hover:bg-blue-200 transition">
                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
            </div>
            <span class="text-xs font-semibold text-gray-700 dark:text-gray-300">Agregar equipo</span>
        </a>

        <a href="{{ route('admin.equipos.prestamos.create') }}"
           class="flex flex-col items-center gap-2 bg-white dark:bg-gray-800 rounded-xl border border-gray-200
                  dark:border-gray-700 p-4 text-center hover:shadow-md hover:border-green-300 transition group">
            <div class="w-10 h-10 rounded-xl bg-green-100 dark:bg-green-900/30 flex items-center justify-center
                        group-hover:bg-green-200 transition">
                <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                </svg>
            </div>
            <span class="text-xs font-semibold text-gray-700 dark:text-gray-300">Registrar préstamo</span>
        </a>

        <a href="{{ route('admin.equipos.lista-pdf') }}"
           class="flex flex-col items-center gap-2 bg-white dark:bg-gray-800 rounded-xl border border-gray-200
                  dark:border-gray-700 p-4 text-center hover:shadow-md hover:border-red-300 transition group">
            <div class="w-10 h-10 rounded-xl bg-red-100 dark:bg-red-900/30 flex items-center justify-center
                        group-hover:bg-red-200 transition">
                <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                </svg>
            </div>
            <span class="text-xs font-semibold text-gray-700 dark:text-gray-300">Inventario PDF</span>
        </a>

        <a href="{{ route('admin.equipos.lista-excel') }}"
           class="flex flex-col items-center gap-2 bg-white dark:bg-gray-800 rounded-xl border border-gray-200
                  dark:border-gray-700 p-4 text-center hover:shadow-md hover:border-emerald-300 transition group">
            <div class="w-10 h-10 rounded-xl bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center
                        group-hover:bg-emerald-200 transition">
                <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
            </div>
            <span class="text-xs font-semibold text-gray-700 dark:text-gray-300">Inventario Excel</span>
        </a>
    </div>

</div>
@endsection
