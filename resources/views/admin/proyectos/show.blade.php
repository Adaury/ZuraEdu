@extends('layouts.admin')

@section('page-title', $proyecto->titulo)

@section('content')
<div class="space-y-6" x-data="proyectoShow()">

    {{-- Breadcrumb + acciones --}}
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <nav class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400 mb-1">
                <a href="{{ route('admin.proyectos.index') }}" class="hover:text-indigo-600 transition">Proyectos Escolares</a>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
                <span class="text-gray-900 dark:text-white font-medium">{{ $proyecto->titulo }}</span>
            </nav>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $proyecto->titulo }}</h1>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.proyectos.edit', $proyecto) }}"
               class="inline-flex items-center gap-1.5 px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white text-sm font-semibold rounded-lg shadow transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
                Editar
            </a>
            <form method="POST" action="{{ route('admin.proyectos.destroy', $proyecto) }}"
                  onsubmit="return confirm('¿Eliminar este proyecto definitivamente?')">
                @csrf @method('DELETE')
                <button type="submit"
                        class="inline-flex items-center gap-1.5 px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold rounded-lg shadow transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                    Eliminar
                </button>
            </form>
        </div>
    </div>

    {{-- Info general --}}
    @php
        $areaColor   = \App\Models\ProyectoEscolar::AREA_COLORS[$proyecto->area]   ?? 'gray';
        $estadoColor = \App\Models\ProyectoEscolar::ESTADO_COLORS[$proyecto->estado] ?? 'gray';
        $progreso    = $proyecto->progreso;
    @endphp
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
        <div class="flex flex-wrap items-center gap-3 mb-4">
            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold
                bg-{{ $areaColor }}-100 text-{{ $areaColor }}-800
                dark:bg-{{ $areaColor }}-900/30 dark:text-{{ $areaColor }}-300">
                {{ $proyecto->area_label }}
            </span>
            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold
                bg-{{ $estadoColor }}-100 text-{{ $estadoColor }}-800
                dark:bg-{{ $estadoColor }}-900/30 dark:text-{{ $estadoColor }}-300">
                {{ $proyecto->estado_label }}
            </span>
            @if($proyecto->schoolYear)
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                    {{ $proyecto->schoolYear->nombre }}
                </span>
            @endif
        </div>

        @if($proyecto->descripcion)
        <p class="text-gray-600 dark:text-gray-300 text-sm leading-relaxed mb-4">{{ $proyecto->descripcion }}</p>
        @endif

        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-sm">
            <div>
                <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Tutor</p>
                <p class="font-semibold text-gray-900 dark:text-white mt-0.5">{{ $proyecto->tutor->name }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Fecha Inicio</p>
                <p class="font-semibold text-gray-900 dark:text-white mt-0.5">{{ $proyecto->fecha_inicio->format('d/m/Y') }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Fecha Fin</p>
                <p class="font-semibold text-gray-900 dark:text-white mt-0.5">
                    {{ $proyecto->fecha_fin ? $proyecto->fecha_fin->format('d/m/Y') : '—' }}
                </p>
            </div>
            <div>
                <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Integrantes</p>
                <p class="font-semibold text-gray-900 dark:text-white mt-0.5">{{ $proyecto->integrantes->count() }}</p>
            </div>
        </div>

        {{-- Barra de progreso fases --}}
        @if($proyecto->fases->count() > 0)
        <div class="mt-5">
            <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400 mb-1.5">
                <span>Progreso de fases</span>
                <span class="font-semibold text-gray-700 dark:text-gray-300">{{ $progreso }}%</span>
            </div>
            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2.5">
                <div class="h-2.5 rounded-full bg-indigo-600 transition-all duration-500"
                     style="width: {{ $progreso }}%"></div>
            </div>
            <p class="text-xs text-gray-400 mt-1">
                {{ $proyecto->fases->where('completada', true)->count() }} de {{ $proyecto->fases->count() }} fases completadas
            </p>
        </div>
        @endif
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        {{-- ── Integrantes ── --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 flex flex-col">
            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-200 dark:border-gray-700">
                <h2 class="font-bold text-gray-900 dark:text-white text-base">Integrantes</h2>
                <button @click="showAddIntegrante = !showAddIntegrante"
                        class="text-sm text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 font-medium transition">
                    + Agregar
                </button>
            </div>

            {{-- Formulario agregar integrante --}}
            <div x-show="showAddIntegrante" x-collapse class="px-5 py-4 border-b border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/30">
                <form method="POST" action="{{ route('admin.proyectos.integrantes.agregar', $proyecto) }}" class="space-y-3">
                    @csrf
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Estudiante</label>
                            <select name="estudiante_id" required
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="">Seleccionar...</option>
                                @foreach($estudiantesDisponibles as $est)
                                    <option value="{{ $est->id }}">{{ $est->nombre_completo }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Rol</label>
                            <select name="rol" required
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="integrante">Integrante</option>
                                <option value="lider">Líder</option>
                            </select>
                        </div>
                    </div>
                    <button type="submit"
                            class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-lg transition">
                        Agregar al Proyecto
                    </button>
                </form>
            </div>

            {{-- Lista de integrantes --}}
            <div class="divide-y divide-gray-100 dark:divide-gray-700 flex-1">
                @forelse($proyecto->integrantes as $integrante)
                <div class="flex items-center justify-between px-5 py-3">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-full bg-indigo-100 dark:bg-indigo-900/40 flex items-center justify-center">
                            <span class="text-xs font-bold text-indigo-700 dark:text-indigo-300">
                                {{ strtoupper(substr($integrante->estudiante->nombres, 0, 1)) }}
                            </span>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-gray-900 dark:text-white">
                                {{ $integrante->estudiante->nombre_completo }}
                            </p>
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                                @if($integrante->rol === 'lider')
                                    bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300
                                @else
                                    bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300
                                @endif">
                                {{ $integrante->rol_label }}
                            </span>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        {{-- Botón Certificado PDF --}}
                        <a href="{{ route('admin.proyectos.certificado', [$proyecto, $integrante->estudiante]) }}"
                           target="_blank"
                           class="inline-flex items-center gap-1 px-2.5 py-1 bg-emerald-100 hover:bg-emerald-200 dark:bg-emerald-900/30 dark:hover:bg-emerald-900/50 text-emerald-800 dark:text-emerald-300 text-xs font-semibold rounded-lg transition"
                           title="Descargar Certificado PDF">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414A1 1 0 0119 9.414V19a2 2 0 01-2 2z"/>
                            </svg>
                            Cert.
                        </a>
                        {{-- Quitar integrante --}}
                        <form method="POST"
                              action="{{ route('admin.proyectos.integrantes.quitar', [$proyecto, $integrante]) }}"
                              onsubmit="return confirm('¿Quitar a este integrante?')">
                            @csrf @method('DELETE')
                            <button type="submit"
                                    class="p-1 rounded text-red-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/30 transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </form>
                    </div>
                </div>
                @empty
                <div class="px-5 py-8 text-center text-sm text-gray-400">
                    No hay integrantes aún. Agrega el primero.
                </div>
                @endforelse
            </div>
        </div>

        {{-- ── Fases / Timeline ── --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 flex flex-col">
            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-200 dark:border-gray-700">
                <h2 class="font-bold text-gray-900 dark:text-white text-base">Fases del Proyecto</h2>
                <button @click="showAddFase = !showAddFase"
                        class="text-sm text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 font-medium transition">
                    + Agregar fase
                </button>
            </div>

            {{-- Formulario agregar fase --}}
            <div x-show="showAddFase" x-collapse class="px-5 py-4 border-b border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/30">
                <form method="POST" action="{{ route('admin.proyectos.fases.store', $proyecto) }}" class="space-y-3">
                    @csrf
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Nombre de la fase</label>
                        <input type="text" name="nombre" required placeholder="Ej. Revisión bibliográfica"
                               class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Fecha límite</label>
                            <input type="date" name="fecha_limite" required
                                   class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:ring-indigo-500 focus:border-indigo-500">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Descripción (opcional)</label>
                            <input type="text" name="descripcion" placeholder="Notas adicionales"
                                   class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:ring-indigo-500 focus:border-indigo-500">
                        </div>
                    </div>
                    <button type="submit"
                            class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-lg transition">
                        Agregar Fase
                    </button>
                </form>
            </div>

            {{-- Timeline de fases --}}
            <div class="px-5 py-4 flex-1">
                @forelse($proyecto->fases as $i => $fase)
                @php $vencida = !$fase->completada && $fase->fecha_limite->isPast(); @endphp
                <div class="relative flex gap-4 {{ !$loop->last ? 'pb-6' : '' }}">
                    {{-- Línea vertical --}}
                    @if(!$loop->last)
                    <div class="absolute left-3.5 top-7 bottom-0 w-0.5 bg-gray-200 dark:bg-gray-700"></div>
                    @endif

                    {{-- Círculo indicador --}}
                    <div class="flex-shrink-0 mt-1">
                        <form method="POST" action="{{ route('admin.proyectos.fases.toggle', [$proyecto, $fase]) }}">
                            @csrf @method('PATCH')
                            <button type="submit"
                                    class="w-7 h-7 rounded-full border-2 flex items-center justify-center transition
                                    @if($fase->completada)
                                        bg-green-500 border-green-500 text-white
                                    @elseif($vencida)
                                        bg-red-100 border-red-400 text-red-600 dark:bg-red-900/30 dark:border-red-500
                                    @else
                                        bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-600 hover:border-indigo-400
                                    @endif"
                                    title="{{ $fase->completada ? 'Marcar como pendiente' : 'Marcar como completada' }}">
                                @if($fase->completada)
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                    </svg>
                                @elseif($vencida)
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                @endif
                            </button>
                        </form>
                    </div>

                    {{-- Contenido --}}
                    <div class="flex-1 min-w-0">
                        <div class="flex items-start justify-between gap-2">
                            <p class="text-sm font-semibold
                                @if($fase->completada) line-through text-gray-400 dark:text-gray-500
                                @else text-gray-900 dark:text-white @endif">
                                {{ $fase->nombre }}
                            </p>
                            <span class="text-xs flex-shrink-0 mt-0.5
                                @if($fase->completada) text-green-600 dark:text-green-400
                                @elseif($vencida) text-red-600 dark:text-red-400 font-semibold
                                @else text-gray-500 dark:text-gray-400 @endif">
                                {{ $fase->fecha_limite->format('d/m/Y') }}
                                @if($vencida) ⚠️ @endif
                            </span>
                        </div>
                        @if($fase->descripcion)
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ $fase->descripcion }}</p>
                        @endif
                    </div>
                </div>
                @empty
                <div class="text-center py-8 text-sm text-gray-400">
                    Sin fases definidas. Agrega la primera fase del proyecto.
                </div>
                @endforelse
            </div>
        </div>

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

@push('scripts')
<script>
function proyectoShow() {
    return {
        showAddIntegrante: {{ $errors->any() ? 'true' : 'false' }},
        showAddFase: false,
    }
}
</script>
@endpush
@endsection
