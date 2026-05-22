@extends('layouts.admin')

@section('page-title', 'Biblioteca Escolar')

@section('content')
<div class="space-y-6">

    {{-- Encabezado --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Biblioteca Escolar</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Gestión de catálogo y préstamos de libros</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <form method="POST" action="{{ route('admin.biblioteca.verificar-vencidos') }}" class="inline">
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
            <a href="{{ route('admin.biblioteca.prestamos.create') }}"
               class="inline-flex items-center gap-1.5 text-sm font-semibold bg-blue-600 hover:bg-blue-700
                      text-white rounded-lg px-4 py-2 shadow transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Nuevo préstamo
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

    {{-- Tarjetas de estadísticas --}}
    <div class="grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4">

        <a href="{{ route('admin.biblioteca.index') }}"
           class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700
                  p-4 hover:shadow-md transition group">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Títulos</span>
                <div class="w-8 h-8 rounded-lg bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                    <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                    </svg>
                </div>
            </div>
            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $totalLibros }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">libros en catálogo</p>
        </a>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Ejemplares</span>
                <div class="w-8 h-8 rounded-lg bg-indigo-100 dark:bg-indigo-900/30 flex items-center justify-center">
                    <svg class="w-4 h-4 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                    </svg>
                </div>
            </div>
            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $totalEjemplares }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">total de copias</p>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Disponibles</span>
                <div class="w-8 h-8 rounded-lg bg-green-100 dark:bg-green-900/30 flex items-center justify-center">
                    <svg class="w-4 h-4 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $totalDisponibles }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">ejemplares libres</p>
        </div>

        <a href="{{ route('admin.biblioteca.prestamos.index', ['estado' => 'activo']) }}"
           class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700
                  p-4 hover:shadow-md transition">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Prestados</span>
                <div class="w-8 h-8 rounded-lg bg-sky-100 dark:bg-sky-900/30 flex items-center justify-center">
                    <svg class="w-4 h-4 text-sky-600 dark:text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                    </svg>
                </div>
            </div>
            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $prestamosActivos }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">préstamos activos</p>
        </a>

        <a href="{{ route('admin.biblioteca.prestamos.index', ['estado' => 'vencido']) }}"
           class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700
                  p-4 hover:shadow-md transition {{ $prestamosVencidos > 0 ? 'ring-1 ring-red-300' : '' }}">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Vencidos</span>
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
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">préstamos vencidos</p>
        </a>

        <a href="{{ route('admin.biblioteca.index', ['disponibilidad' => 'agotado']) }}"
           class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700
                  p-4 hover:shadow-md transition">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Agotados</span>
                <div class="w-8 h-8 rounded-lg bg-orange-100 dark:bg-orange-900/30 flex items-center justify-center">
                    <svg class="w-4 h-4 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                    </svg>
                </div>
            </div>
            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $librosAgotados }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">sin ejemplares</p>
        </a>

    </div>

    {{-- Cuerpo: categorías + últimos préstamos --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Libros por categoría --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-5">
            <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-4">Distribución por categoría</h2>
            @forelse($porCategoria as $cat)
                @php
                    $pct = $totalLibros > 0 ? round(($cat->total / $totalLibros) * 100) : 0;
                @endphp
                <div class="mb-3">
                    <div class="flex justify-between text-xs text-gray-600 dark:text-gray-400 mb-1">
                        <span>{{ $cat->categoria ?? 'Sin categoría' }}</span>
                        <span>{{ $cat->total }} ({{ $pct }}%)</span>
                    </div>
                    <div class="w-full bg-gray-100 dark:bg-gray-700 rounded-full h-1.5">
                        <div class="bg-blue-500 h-1.5 rounded-full" style="width: {{ $pct }}%"></div>
                    </div>
                </div>
            @empty
                <p class="text-sm text-gray-400 text-center py-4">Sin libros registrados</p>
            @endforelse
            <a href="{{ route('admin.biblioteca.index') }}"
               class="mt-2 block text-center text-xs text-blue-600 dark:text-blue-400 hover:underline font-medium">
                Ver catálogo completo →
            </a>
        </div>

        {{-- Últimos préstamos --}}
        <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between px-5 pt-5 pb-3 border-b border-gray-100 dark:border-gray-700">
                <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Últimos préstamos</h2>
                <a href="{{ route('admin.biblioteca.prestamos.index') }}"
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
                                {{ $prestamo->libro?->titulo ?? '—' }}
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $prestamo->estudiante?->nombres }} {{ $prestamo->estudiante?->apellidos }}
                                · {{ $prestamo->fecha_prestamo?->format('d/m/Y') }}
                            </p>
                        </div>
                        <span class="ml-3 shrink-0 text-xs font-medium px-2 py-0.5 rounded-full {{ $badge }}">
                            {{ $label }}
                        </span>
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
        <a href="{{ route('admin.biblioteca.libros.create') }}"
           class="flex flex-col items-center gap-2 bg-white dark:bg-gray-800 rounded-xl border border-gray-200
                  dark:border-gray-700 p-4 text-center hover:shadow-md hover:border-blue-300 transition group">
            <div class="w-10 h-10 rounded-xl bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center
                        group-hover:bg-blue-200 transition">
                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
            </div>
            <span class="text-xs font-semibold text-gray-700 dark:text-gray-300">Agregar libro</span>
        </a>

        <a href="{{ route('admin.biblioteca.prestamos.create') }}"
           class="flex flex-col items-center gap-2 bg-white dark:bg-gray-800 rounded-xl border border-gray-200
                  dark:border-gray-700 p-4 text-center hover:shadow-md hover:border-green-300 transition group">
            <div class="w-10 h-10 rounded-xl bg-green-100 dark:bg-green-900/30 flex items-center justify-center
                        group-hover:bg-green-200 transition">
                <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                </svg>
            </div>
            <span class="text-xs font-semibold text-gray-700 dark:text-gray-300">Nuevo préstamo</span>
        </a>

        <a href="{{ route('admin.biblioteca.catalogo.pdf') }}"
           class="flex flex-col items-center gap-2 bg-white dark:bg-gray-800 rounded-xl border border-gray-200
                  dark:border-gray-700 p-4 text-center hover:shadow-md hover:border-red-300 transition group">
            <div class="w-10 h-10 rounded-xl bg-red-100 dark:bg-red-900/30 flex items-center justify-center
                        group-hover:bg-red-200 transition">
                <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                </svg>
            </div>
            <span class="text-xs font-semibold text-gray-700 dark:text-gray-300">Catálogo PDF</span>
        </a>

        <a href="{{ route('admin.biblioteca.catalogo.excel') }}"
           class="flex flex-col items-center gap-2 bg-white dark:bg-gray-800 rounded-xl border border-gray-200
                  dark:border-gray-700 p-4 text-center hover:shadow-md hover:border-emerald-300 transition group">
            <div class="w-10 h-10 rounded-xl bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center
                        group-hover:bg-emerald-200 transition">
                <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
            </div>
            <span class="text-xs font-semibold text-gray-700 dark:text-gray-300">Catálogo Excel</span>
        </a>
    </div>

</div>
@endsection
