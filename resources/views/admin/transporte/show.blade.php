@extends('layouts.admin')

@section('page-title', 'Ruta: ' . $ruta->nombre)

@section('content')
<div class="space-y-6" x-data="{ tabActivo: 'paradas' }">

    {{-- Encabezado --}}
    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
        <div class="flex items-start gap-3">
            <a href="{{ route('admin.transporte.index') }}"
               class="mt-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <div>
                <div class="flex items-center gap-2 flex-wrap">
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $ruta->nombre }}</h1>
                    @if($ruta->activo)
                        <span class="inline-flex items-center gap-1 bg-green-100 text-green-700 text-xs font-semibold px-2 py-0.5 rounded-full">
                            <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span> Activa
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1 bg-gray-100 text-gray-600 text-xs font-semibold px-2 py-0.5 rounded-full">
                            <span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span> Inactiva
                        </span>
                    @endif
                </div>
                @if($ruta->descripcion)
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">{{ $ruta->descripcion }}</p>
                @endif
            </div>
        </div>
        <div class="flex items-center gap-2 flex-shrink-0">
            <a href="{{ route('admin.transporte.pasajeros.pdf', $ruta) }}" target="_blank"
               class="inline-flex items-center gap-1.5 bg-red-50 hover:bg-red-100 text-red-600 border border-red-200
                      text-sm font-semibold px-3 py-2 rounded-lg transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                </svg>
                PDF Pasajeros
            </a>
            <a href="{{ route('admin.transporte.edit', $ruta) }}"
               class="inline-flex items-center gap-1.5 bg-yellow-50 hover:bg-yellow-100 text-yellow-700 border border-yellow-200
                      text-sm font-semibold px-3 py-2 rounded-lg transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M15.232 5.232l3.536 3.536M9 13l6.5-6.5a2.121 2.121 0 013 3L12 16H9v-3z"/>
                </svg>
                Editar
            </a>
        </div>
    </div>

    {{-- Alertas --}}
    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-800 rounded-lg px-4 py-3 text-sm">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="bg-red-50 border border-red-200 text-red-800 rounded-lg px-4 py-3 text-sm">
            {{ session('error') }}
        </div>
    @endif

    {{-- Estadísticas --}}
    @php
        $ocupacion = $ruta->estudiantesRuta->count();
        $pct       = $ruta->capacidad > 0 ? round(($ocupacion / $ruta->capacidad) * 100) : 0;
        $barColor  = $pct >= 90 ? 'bg-red-500' : ($pct >= 70 ? 'bg-yellow-500' : 'bg-green-500');
        $txtColor  = $pct >= 90 ? 'text-red-600' : ($pct >= 70 ? 'text-yellow-600' : 'text-green-600');
    @endphp
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Pasajeros</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ $ocupacion }}</p>
            <p class="text-xs text-gray-400 mt-0.5">de {{ $ruta->capacidad }} lugares</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Disponibles</p>
            <p class="text-2xl font-bold {{ $txtColor }} mt-1">{{ max(0, $ruta->capacidad - $ocupacion) }}</p>
            <div class="mt-2 bg-gray-200 dark:bg-gray-600 rounded-full h-1.5">
                <div class="{{ $barColor }} h-1.5 rounded-full" style="width:{{ min($pct,100) }}%"></div>
            </div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Paradas</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ $ruta->paradas->count() }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Conductor</p>
            <p class="text-sm font-semibold text-gray-800 dark:text-gray-200 mt-1 truncate">
                {{ $ruta->conductor ?? '—' }}
            </p>
            <p class="text-xs text-gray-400 truncate">{{ $ruta->vehiculo ?? 'Sin vehículo' }}</p>
        </div>
    </div>

    {{-- Pestañas --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">

        {{-- Tab nav --}}
        <div class="border-b border-gray-200 dark:border-gray-700 px-4">
            <nav class="flex gap-1 -mb-px">
                <button @click="tabActivo = 'paradas'"
                        :class="tabActivo === 'paradas'
                            ? 'border-blue-600 text-blue-600 dark:text-blue-400 dark:border-blue-400'
                            : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'"
                        class="flex items-center gap-1.5 px-4 py-3 text-sm font-medium border-b-2 transition whitespace-nowrap">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    Paradas
                    <span class="bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300 text-xs rounded-full px-1.5 py-0.5">
                        {{ $ruta->paradas->count() }}
                    </span>
                </button>
                <button @click="tabActivo = 'estudiantes'"
                        :class="tabActivo === 'estudiantes'
                            ? 'border-blue-600 text-blue-600 dark:text-blue-400 dark:border-blue-400'
                            : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'"
                        class="flex items-center gap-1.5 px-4 py-3 text-sm font-medium border-b-2 transition whitespace-nowrap">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197"/>
                    </svg>
                    Estudiantes
                    <span class="bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300 text-xs rounded-full px-1.5 py-0.5">
                        {{ $ruta->estudiantesRuta->count() }}
                    </span>
                </button>
            </nav>
        </div>

        {{-- TAB: PARADAS ─────────────────────────────────────────────────────── --}}
        <div x-show="tabActivo === 'paradas'" x-cloak class="p-5 space-y-4">

            {{-- Formulario nueva parada --}}
            <div class="bg-gray-50 dark:bg-gray-700/40 rounded-lg p-4 border border-gray-200 dark:border-gray-600"
                 x-data="{ open: false }">
                <button @click="open = !open"
                        class="w-full flex items-center justify-between text-sm font-semibold text-blue-600 dark:text-blue-400">
                    <span class="flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Agregar parada
                    </span>
                    <svg class="w-4 h-4 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <form x-show="open" x-cloak
                      method="POST" action="{{ route('admin.transporte.paradas.store', $ruta) }}"
                      class="mt-3 grid grid-cols-1 sm:grid-cols-3 gap-3">
                    @csrf
                    <div class="sm:col-span-2">
                        <input type="text" name="nombre" required maxlength="120"
                               placeholder="Nombre de la parada"
                               class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700
                                      text-sm px-3 py-2 focus:ring-2 focus:ring-blue-500 dark:text-white placeholder-gray-400">
                    </div>
                    <div class="flex gap-2">
                        <input type="time" name="hora_estimada"
                               class="flex-1 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700
                                      text-sm px-3 py-2 focus:ring-2 focus:ring-blue-500 dark:text-white">
                        <button type="submit"
                                class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-4 py-2 rounded-lg transition">
                            Agregar
                        </button>
                    </div>
                </form>
            </div>

            {{-- Lista de paradas --}}
            @forelse($ruta->paradas as $parada)
            <div class="flex items-center gap-3 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700
                        rounded-lg px-4 py-3" x-data="{ editing: false }">
                <span class="flex-shrink-0 w-7 h-7 rounded-full bg-blue-100 dark:bg-blue-900/40 text-blue-700
                             dark:text-blue-300 text-xs font-bold flex items-center justify-center">
                    {{ $parada->orden }}
                </span>

                {{-- Vista normal --}}
                <div x-show="!editing" class="flex-1 min-w-0">
                    <p class="font-medium text-gray-800 dark:text-gray-200 text-sm">{{ $parada->nombre }}</p>
                    @if($parada->hora_estimada)
                        <p class="text-xs text-gray-400 mt-0.5">
                            <svg class="w-3 h-3 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            {{ \Carbon\Carbon::parse($parada->hora_estimada)->format('g:i A') }}
                        </p>
                    @endif
                </div>

                {{-- Formulario edición inline --}}
                <form x-show="editing" x-cloak
                      method="POST" action="{{ route('admin.transporte.paradas.update', [$ruta, $parada]) }}"
                      class="flex-1 flex flex-wrap gap-2">
                    @csrf @method('PUT')
                    <input type="text" name="nombre" value="{{ $parada->nombre }}" required
                           class="flex-1 min-w-[140px] rounded-lg border border-gray-300 dark:border-gray-600 bg-white
                                  dark:bg-gray-700 text-sm px-2 py-1.5 focus:ring-2 focus:ring-blue-500 dark:text-white">
                    <input type="time" name="hora_estimada" value="{{ $parada->hora_estimada }}"
                           class="rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700
                                  text-sm px-2 py-1.5 focus:ring-2 focus:ring-blue-500 dark:text-white">
                    <input type="number" name="orden" value="{{ $parada->orden }}" min="1"
                           class="w-16 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700
                                  text-sm px-2 py-1.5 focus:ring-2 focus:ring-blue-500 dark:text-white">
                    <button type="submit" class="bg-green-600 text-white text-xs font-semibold px-3 py-1.5 rounded-lg transition hover:bg-green-700">
                        Guardar
                    </button>
                    <button type="button" @click="editing = false"
                            class="text-gray-500 hover:text-gray-700 text-xs px-2 py-1.5 rounded-lg">
                        Cancelar
                    </button>
                </form>

                {{-- Acciones --}}
                <div x-show="!editing" class="flex items-center gap-1 flex-shrink-0">
                    <button @click="editing = true"
                            class="text-gray-400 hover:text-yellow-600 dark:hover:text-yellow-400 transition p-1" title="Editar">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M15.232 5.232l3.536 3.536M9 13l6.5-6.5a2.121 2.121 0 013 3L12 16H9v-3z"/>
                        </svg>
                    </button>
                    <form method="POST" action="{{ route('admin.transporte.paradas.destroy', [$ruta, $parada]) }}"
                          onsubmit="return confirm('¿Eliminar parada {{ addslashes($parada->nombre) }}?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="text-gray-400 hover:text-red-600 dark:hover:text-red-400 transition p-1" title="Eliminar">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M1 7h22M8 7V5a1 1 0 011-1h6a1 1 0 011 1v2"/>
                            </svg>
                        </button>
                    </form>
                </div>
            </div>
            @empty
            <div class="text-center py-8 text-gray-400 dark:text-gray-500">
                <svg class="w-10 h-10 mx-auto mb-2 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                <p class="text-sm">Sin paradas. Agrega la primera arriba.</p>
            </div>
            @endforelse

        </div>

        {{-- TAB: ESTUDIANTES ─────────────────────────────────────────────────── --}}
        <div x-show="tabActivo === 'estudiantes'" x-cloak class="p-5 space-y-4">

            {{-- Buscador para asignar --}}
            <div class="bg-gray-50 dark:bg-gray-700/40 rounded-lg p-4 border border-gray-200 dark:border-gray-600">
                <p class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Asignar estudiante a esta ruta</p>
                <form method="GET" action="{{ route('admin.transporte.show', $ruta) }}"
                      class="flex gap-2">
                    @foreach(request()->except('buscar_estudiante') as $k => $v)
                        <input type="hidden" name="{{ $k }}" value="{{ $v }}">
                    @endforeach
                    <input type="text" name="buscar_estudiante" value="{{ $busqueda }}"
                           placeholder="Buscar por nombre o matrícula…"
                           class="flex-1 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700
                                  text-sm px-3 py-2 focus:ring-2 focus:ring-blue-500 dark:text-white placeholder-gray-400">
                    <button type="submit"
                            class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-4 py-2 rounded-lg transition">
                        Buscar
                    </button>
                </form>

                {{-- Resultados búsqueda --}}
                @if($busqueda)
                    @if($candidatos->isEmpty())
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-3">
                            Sin resultados para "{{ $busqueda }}" (o ya están asignados a esta ruta).
                        </p>
                    @else
                        <div class="mt-3 space-y-2">
                            @foreach($candidatos as $est)
                            <form method="POST" action="{{ route('admin.transporte.estudiantes.store', $ruta) }}"
                                  class="flex flex-wrap items-center gap-2 bg-white dark:bg-gray-800 rounded-lg border
                                         border-gray-200 dark:border-gray-600 px-3 py-2">
                                @csrf
                                <input type="hidden" name="estudiante_id" value="{{ $est->id }}">
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-800 dark:text-gray-200 truncate">
                                        {{ $est->nombre_completo }}
                                    </p>
                                    <p class="text-xs text-gray-400">Mat: {{ $est->numero_matricula ?? '—' }}</p>
                                </div>
                                <select name="tipo"
                                        class="rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700
                                               text-xs px-2 py-1.5 focus:ring-2 focus:ring-blue-500 dark:text-white">
                                    <option value="ambos">Ida y vuelta</option>
                                    <option value="ida">Solo ida</option>
                                    <option value="vuelta">Solo vuelta</option>
                                </select>
                                @if($ruta->paradas->count())
                                <select name="parada_id"
                                        class="rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700
                                               text-xs px-2 py-1.5 focus:ring-2 focus:ring-blue-500 dark:text-white">
                                    <option value="">Sin parada</option>
                                    @foreach($ruta->paradas as $p)
                                        <option value="{{ $p->id }}">{{ $p->nombre }}</option>
                                    @endforeach
                                </select>
                                @endif
                                <button type="submit"
                                        class="bg-green-600 hover:bg-green-700 text-white text-xs font-semibold px-3 py-1.5 rounded-lg transition">
                                    Asignar
                                </button>
                            </form>
                            @endforeach
                        </div>
                    @endif
                @endif
            </div>

            {{-- Lista de estudiantes asignados --}}
            @if($ruta->estudiantesRuta->isEmpty())
            <div class="text-center py-8 text-gray-400 dark:text-gray-500">
                <svg class="w-10 h-10 mx-auto mb-2 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197"/>
                </svg>
                <p class="text-sm">Sin estudiantes asignados. Usa el buscador para agregar.</p>
            </div>
            @else
            <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 dark:bg-gray-700/50 border-b border-gray-200 dark:border-gray-700">
                            <th class="text-left px-4 py-3 font-semibold text-gray-600 dark:text-gray-300">#</th>
                            <th class="text-left px-4 py-3 font-semibold text-gray-600 dark:text-gray-300">Estudiante</th>
                            <th class="text-left px-4 py-3 font-semibold text-gray-600 dark:text-gray-300">Parada</th>
                            <th class="text-left px-4 py-3 font-semibold text-gray-600 dark:text-gray-300">Servicio</th>
                            <th class="text-right px-4 py-3 font-semibold text-gray-600 dark:text-gray-300">Acción</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach($ruta->estudiantesRuta as $i => $er)
                        @php $est = $er->estudiante; @endphp
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition">
                            <td class="px-4 py-3 text-gray-400 text-xs">{{ $i + 1 }}</td>
                            <td class="px-4 py-3">
                                <p class="font-medium text-gray-800 dark:text-gray-200">
                                    {{ $est?->nombre_completo ?? '—' }}
                                </p>
                                <p class="text-xs text-gray-400">Mat: {{ $est?->numero_matricula ?? '—' }}</p>
                            </td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-400 text-xs">
                                {{ $er->parada?->nombre ?? '—' }}
                            </td>
                            <td class="px-4 py-3">
                                @php
                                    $tipoBadge = match($er->tipo) {
                                        'ida'    => 'bg-blue-100 text-blue-700',
                                        'vuelta' => 'bg-purple-100 text-purple-700',
                                        default  => 'bg-gray-100 text-gray-600',
                                    };
                                @endphp
                                <span class="inline-block {{ $tipoBadge }} text-xs font-semibold px-2 py-0.5 rounded-full">
                                    {{ $er->tipo_label }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <form method="POST"
                                      action="{{ route('admin.transporte.estudiantes.destroy', [$ruta, $er]) }}"
                                      onsubmit="return confirm('¿Quitar a {{ addslashes($est?->nombre_completo ?? '') }} de esta ruta?')">
                                    @csrf @method('DELETE')
                                    <button type="submit"
                                            class="text-gray-400 hover:text-red-600 dark:hover:text-red-400 transition" title="Desasignar">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M13 7a4 4 0 11-8 0 4 4 0 018 0zM9 14a6 6 0 00-6 6v1h12v-1a6 6 0 00-6-6zm7-3h6m-3-3v6"/>
                                        </svg>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif

        </div>
    </div>

</div>
@endsection
