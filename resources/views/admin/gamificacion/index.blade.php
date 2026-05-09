@extends('layouts.admin')

@section('page-title', 'Gamificación — Ranking y Puntos')

@section('content')
<div class="space-y-6" x-data="{
    showModalPuntos: false,
    matriculaId: '',
    estudianteNombre: '',
    abrirModal(id, nombre) {
        this.matriculaId = id;
        this.estudianteNombre = nombre;
        this.showModalPuntos = true;
    }
}">

    {{-- Encabezado --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                <i class="bi bi-controller text-indigo-500"></i>
                Gamificación
            </h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                Ranking de puntos, insignias y motivación académica por grupo
            </p>
        </div>
        {{-- Selector de grupo --}}
        <form method="GET" action="{{ route('admin.gamificacion.index') }}" class="flex items-center gap-2">
            <select name="grupo_id" onchange="this.form.submit()"
                class="text-sm border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-100 focus:ring-2 focus:ring-indigo-500 outline-none">
                @foreach($grupos as $grupo)
                    <option value="{{ $grupo->id }}" @selected($grupo->id == $grupoId)>
                        {{ $grupo->grado?->nombre ?? '' }} {{ $grupo->seccion?->nombre ?? '' }} — {{ $grupo->nombre }}
                    </option>
                @endforeach
            </select>
        </form>
    </div>

    {{-- Flash --}}
    @if(session('success'))
        <div class="bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-700 text-green-800 dark:text-green-200 rounded-xl px-4 py-3 text-sm flex items-center gap-2">
            <i class="bi bi-check-circle-fill"></i> {{ session('success') }}
        </div>
    @endif

    {{-- Tarjetas estadísticas --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 flex items-center gap-4">
            <div class="w-12 h-12 rounded-full bg-indigo-100 dark:bg-indigo-900/40 flex items-center justify-center">
                <i class="bi bi-star-fill text-indigo-500 text-xl"></i>
            </div>
            <div>
                <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Total Puntos</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($totalPuntos) }}</p>
            </div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 flex items-center gap-4">
            <div class="w-12 h-12 rounded-full bg-yellow-100 dark:bg-yellow-900/40 flex items-center justify-center">
                <i class="bi bi-award-fill text-yellow-500 text-xl"></i>
            </div>
            <div>
                <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Insignias Otorgadas</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($totalInsignias) }}</p>
            </div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 flex items-center gap-4">
            <div class="w-12 h-12 rounded-full bg-green-100 dark:bg-green-900/40 flex items-center justify-center">
                <i class="bi bi-people-fill text-green-500 text-xl"></i>
            </div>
            <div>
                <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Estudiantes con Puntos</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($matriculasConPuntos) }}</p>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Ranking del grupo --}}
        <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between gap-2 flex-wrap">
                <h2 class="font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                    <i class="bi bi-trophy-fill text-yellow-500"></i>
                    Ranking del Grupo
                </h2>
                <div class="flex items-center gap-2">
                    @if($grupoId)
                    {{-- PDF ranking --}}
                    <a href="{{ route('admin.gamificacion.ranking-pdf', ['grupo_id' => $grupoId]) }}" target="_blank"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs bg-red-600 hover:bg-red-700 text-white rounded-lg transition font-medium">
                        <i class="bi bi-file-earmark-pdf-fill"></i> PDF
                    </a>
                    {{-- Excel ranking --}}
                    <a href="{{ route('admin.gamificacion.ranking-excel', ['grupo_id' => $grupoId]) }}"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs bg-green-600 hover:bg-green-700 text-white rounded-lg transition font-medium">
                        <i class="bi bi-file-earmark-excel-fill"></i> Excel
                    </a>
                    {{-- Generar puntos automáticos --}}
                    <form method="POST" action="{{ route('admin.gamificacion.generar-puntos') }}"
                          onsubmit="return confirm('¿Generar puntos automáticos para este grupo?')">
                        @csrf
                        <input type="hidden" name="grupo_id" value="{{ $grupoId }}">
                        <button type="submit"
                            class="inline-flex items-center gap-2 px-3 py-1.5 text-xs bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg transition font-medium">
                            <i class="bi bi-lightning-charge-fill"></i>
                            Generar Automático
                        </button>
                    </form>
                    @endif
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-700/50 text-xs text-gray-500 dark:text-gray-400 uppercase">
                        <tr>
                            <th class="px-4 py-3 text-center w-10">#</th>
                            <th class="px-4 py-3 text-left">Estudiante</th>
                            <th class="px-4 py-3 text-center">Puntos</th>
                            <th class="px-4 py-3 text-center">Insignias</th>
                            <th class="px-4 py-3 text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse($ranking as $pos => $item)
                            @php
                                $medallas = ['🥇','🥈','🥉'];
                                $m = $medallas[$pos] ?? ($pos + 1);
                            @endphp
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition">
                                <td class="px-4 py-3 text-center font-bold text-lg">{{ $m }}</td>
                                <td class="px-4 py-3">
                                    <div class="font-medium text-gray-900 dark:text-white">
                                        {{ $item['matricula']->estudiante?->nombre_completo ?? '—' }}
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        Mat. #{{ $item['matricula']->numero_orden ?? $item['matricula']->id }}
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold
                                        {{ $item['total'] >= 500 ? 'bg-pink-100 text-pink-700 dark:bg-pink-900/40 dark:text-pink-300' :
                                           ($item['total'] >= 100 ? 'bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-300' :
                                           ($item['total'] > 0 ? 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300'
                                                               : 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400')) }}">
                                        <i class="bi bi-star-fill text-yellow-400"></i>
                                        {{ number_format($item['total']) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    @if($item['insignias'] > 0)
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-yellow-100 dark:bg-yellow-900/40 text-yellow-700 dark:text-yellow-300 text-xs font-semibold">
                                            <i class="bi bi-award-fill"></i> {{ $item['insignias'] }}
                                        </span>
                                    @else
                                        <span class="text-gray-400 dark:text-gray-600 text-xs">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <button
                                            @click="abrirModal({{ $item['matricula']->id }}, '{{ addslashes($item['matricula']->estudiante?->nombre_completo ?? '') }}')"
                                            class="inline-flex items-center gap-1 px-2.5 py-1 text-xs bg-green-600 hover:bg-green-700 text-white rounded-lg transition font-medium">
                                            <i class="bi bi-plus-circle-fill"></i> Puntos
                                        </button>
                                        <a href="{{ route('admin.gamificacion.detalle', $item['matricula']) }}"
                                            class="inline-flex items-center gap-1 px-2.5 py-1 text-xs bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg transition font-medium">
                                            <i class="bi bi-eye-fill"></i> Ver
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-10 text-center text-gray-400 dark:text-gray-600">
                                    <i class="bi bi-inbox text-3xl block mb-2"></i>
                                    No hay estudiantes en este grupo.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Panel lateral: insignias posibles --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 space-y-3">
            <h2 class="font-semibold text-gray-900 dark:text-white flex items-center gap-2 mb-4">
                <i class="bi bi-award-fill text-yellow-500"></i>
                Insignias del Sistema
            </h2>
            @foreach(\App\Models\InsigniaEstudiante::TIPOS as $tipo => $info)
            <div class="flex items-start gap-3 p-3 rounded-xl border border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/40 transition">
                <div class="w-9 h-9 rounded-full flex items-center justify-center flex-shrink-0"
                     style="background-color: {{ $info['bg'] }}">
                    <i class="bi {{ $info['icono'] }} text-sm" style="color: {{ $info['color'] }}"></i>
                </div>
                <div>
                    <p class="text-sm font-semibold text-gray-800 dark:text-gray-200">{{ $info['label'] }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ $info['descripcion'] }}</p>
                </div>
            </div>
            @endforeach

            {{-- Leyenda de puntos --}}
            <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700">
                <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2">
                    Puntos automáticos
                </p>
                <ul class="text-xs text-gray-600 dark:text-gray-400 space-y-1">
                    <li class="flex justify-between"><span>Promedio ≥ 90</span><span class="font-bold text-indigo-500">+50 pts</span></li>
                    <li class="flex justify-between"><span>Promedio ≥ 80</span><span class="font-bold text-blue-500">+30 pts</span></li>
                    <li class="flex justify-between"><span>Asistencia ≥ 95%</span><span class="font-bold text-green-500">+40 pts</span></li>
                    <li class="flex justify-between"><span>Sin faltas disciplinarias</span><span class="font-bold text-purple-500">+20 pts</span></li>
                </ul>
            </div>
        </div>
    </div>

    {{-- Modal: asignar puntos manualmente --}}
    <div x-show="showModalPuntos" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
         @keydown.escape.window="showModalPuntos = false">
        <div @click.outside="showModalPuntos = false"
             class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl w-full max-w-md p-6 space-y-4">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    <i class="bi bi-plus-circle-fill text-green-500"></i>
                    Asignar Puntos
                </h3>
                <button @click="showModalPuntos = false"
                    class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition">
                    <i class="bi bi-x-lg text-lg"></i>
                </button>
            </div>

            <p class="text-sm text-gray-600 dark:text-gray-300">
                Estudiante: <span class="font-semibold" x-text="estudianteNombre"></span>
            </p>

            <form method="POST" action="{{ route('admin.gamificacion.asignar-puntos') }}" class="space-y-3">
                @csrf
                <input type="hidden" name="matricula_id" :value="matriculaId">

                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Concepto</label>
                    <input type="text" name="concepto" required maxlength="255"
                        placeholder="Ej: Participación activa en clase"
                        class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 outline-none">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Categoría</label>
                        <select name="categoria" required
                            class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 outline-none">
                            <option value="academico">Académico</option>
                            <option value="asistencia">Asistencia</option>
                            <option value="conducta">Conducta</option>
                            <option value="participacion" selected>Participación</option>
                            <option value="extra">Extra</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Puntos</label>
                        <input type="number" name="puntos" required min="1" max="500" value="10"
                            class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 outline-none">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Fecha</label>
                    <input type="date" name="fecha" required value="{{ today()->format('Y-m-d') }}"
                        class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 outline-none">
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" @click="showModalPuntos = false"
                        class="px-4 py-2 text-sm border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                        Cancelar
                    </button>
                    <button type="submit"
                        class="px-4 py-2 text-sm bg-green-600 hover:bg-green-700 text-white rounded-lg font-medium shadow transition">
                        <i class="bi bi-check-lg"></i> Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
