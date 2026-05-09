@extends('layouts.admin')

@section('page-title', 'Gamificación — ' . ($matricula->estudiante?->nombre_completo ?? 'Estudiante'))

@section('content')
<div class="space-y-6">

    {{-- Encabezado --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <a href="{{ route('admin.gamificacion.index', ['grupo_id' => $matricula->grupo_id]) }}"
               class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 mb-1 transition">
                <i class="bi bi-arrow-left"></i> Volver al ranking
            </a>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                <i class="bi bi-person-badge-fill text-indigo-500"></i>
                {{ $matricula->estudiante?->nombre_completo ?? '—' }}
            </h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                {{ $matricula->grupo?->grado?->nombre }} {{ $matricula->grupo?->seccion?->nombre }}
                — Mat. #{{ $matricula->numero_orden ?? $matricula->id }}
                @if($posicion)
                    — <span class="font-semibold text-indigo-600 dark:text-indigo-400">Posición #{{ $posicion }} en el grupo</span>
                @endif
            </p>
        </div>
        {{-- Botón asignar puntos --}}
        <button onclick="document.getElementById('modalPuntos').classList.remove('hidden')"
            class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-xl text-sm font-medium shadow transition">
            <i class="bi bi-plus-circle-fill"></i> Asignar Puntos
        </button>
    </div>

    {{-- Flash --}}
    @if(session('success'))
        <div class="bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-700 text-green-800 dark:text-green-200 rounded-xl px-4 py-3 text-sm flex items-center gap-2">
            <i class="bi bi-check-circle-fill"></i> {{ session('success') }}
        </div>
    @endif

    {{-- Tarjetas resumen --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 text-center">
            <i class="bi bi-star-fill text-indigo-500 text-2xl"></i>
            <p class="text-3xl font-bold text-gray-900 dark:text-white mt-1">{{ number_format($totalPuntos) }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Puntos totales</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 text-center">
            <i class="bi bi-award-fill text-yellow-500 text-2xl"></i>
            <p class="text-3xl font-bold text-gray-900 dark:text-white mt-1">{{ $insigniasObtenidas->count() }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Insignias</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 text-center">
            <i class="bi bi-list-check text-blue-500 text-2xl"></i>
            <p class="text-3xl font-bold text-gray-900 dark:text-white mt-1">{{ $historial->count() }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Registros</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 text-center">
            <i class="bi bi-trophy-fill text-amber-500 text-2xl"></i>
            <p class="text-3xl font-bold text-gray-900 dark:text-white mt-1">{{ $posicion ? '#'.$posicion : '—' }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Pos. en grupo</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Insignias --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
            <h2 class="font-semibold text-gray-900 dark:text-white flex items-center gap-2 mb-4">
                <i class="bi bi-award-fill text-yellow-500"></i> Insignias
            </h2>
            <div class="space-y-2">
                @foreach(\App\Models\InsigniaEstudiante::TIPOS as $tipo => $info)
                @php $obtenida = isset($insigniasObtenidas[$tipo]); @endphp
                <div class="flex items-center gap-3 p-2.5 rounded-xl {{ $obtenida ? 'bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-700' : 'opacity-40' }}">
                    <div class="w-9 h-9 rounded-full flex items-center justify-center flex-shrink-0"
                         style="background-color: {{ $info['bg'] }}">
                        <i class="bi {{ $info['icono'] }} text-sm" style="color: {{ $info['color'] }}"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-gray-800 dark:text-gray-200">{{ $info['label'] }}</p>
                        @if($obtenida)
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $insigniasObtenidas[$tipo]->fecha_obtencion?->format('d/m/Y') }}</p>
                        @else
                        <p class="text-xs text-gray-400">Bloqueada</p>
                        @endif
                    </div>
                    @if($obtenida)
                    <i class="bi bi-check-circle-fill text-green-500 flex-shrink-0"></i>
                    @endif
                </div>
                @endforeach
            </div>

            {{-- Puntos por categoría --}}
            <h2 class="font-semibold text-gray-900 dark:text-white flex items-center gap-2 mt-6 mb-4">
                <i class="bi bi-bar-chart-fill text-indigo-500"></i> Por Categoría
            </h2>
            @php $maxCat = max(array_values($puntosCategoria) ?: [1]); @endphp
            @foreach(\App\Models\PuntoEstudiante::CATEGORIAS as $cat => $info)
            @php
                $pts = $puntosCategoria[$cat] ?? 0;
                $pct = $maxCat > 0 ? round($pts / $maxCat * 100) : 0;
                $colores = ['blue' => '#3b82f6', 'green' => '#10b981', 'purple' => '#8b5cf6', 'orange' => '#f59e0b', 'gray' => '#6b7280'];
                $color = $colores[$info['color']] ?? '#6b7280';
            @endphp
            <div class="mb-3">
                <div class="flex justify-between items-center mb-1">
                    <span class="text-xs font-medium text-gray-700 dark:text-gray-300 flex items-center gap-1">
                        <i class="bi {{ $info['icon'] }}"></i> {{ $info['label'] }}
                    </span>
                    <span class="text-xs font-bold text-gray-900 dark:text-white">{{ $pts }} pts</span>
                </div>
                <div class="w-full bg-gray-100 dark:bg-gray-700 rounded-full h-2">
                    <div class="h-2 rounded-full transition-all" style="width:{{ $pct }}%;background:{{ $color }};"></div>
                </div>
            </div>
            @endforeach
        </div>

        {{-- Historial de puntos --}}
        <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700">
                <h2 class="font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                    <i class="bi bi-clock-history text-indigo-500"></i>
                    Historial de Puntos
                    <span class="text-xs font-normal text-gray-500 dark:text-gray-400">({{ $historial->count() }} registros)</span>
                </h2>
            </div>

            @if($historial->isEmpty())
            <div class="py-12 text-center text-gray-400 dark:text-gray-600">
                <i class="bi bi-inbox text-4xl block mb-2"></i>
                Sin puntos registrados aún.
            </div>
            @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-700/50 text-xs text-gray-500 dark:text-gray-400 uppercase">
                        <tr>
                            <th class="px-4 py-3 text-left">Concepto</th>
                            <th class="px-4 py-3 text-center">Categoría</th>
                            <th class="px-4 py-3 text-center">Puntos</th>
                            <th class="px-4 py-3 text-center">Fecha</th>
                            <th class="px-4 py-3 text-center">Acción</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach($historial as $punto)
                    @php
                        $catInfo = \App\Models\PuntoEstudiante::CATEGORIAS[$punto->categoria] ?? ['label' => ucfirst($punto->categoria), 'icon' => 'bi-star', 'color' => 'gray'];
                        $bgMap   = ['blue'=>'bg-blue-100 text-blue-700','green'=>'bg-green-100 text-green-700','purple'=>'bg-purple-100 text-purple-700','orange'=>'bg-yellow-100 text-yellow-700','gray'=>'bg-gray-100 text-gray-600'];
                        $badge   = $bgMap[$catInfo['color']] ?? 'bg-gray-100 text-gray-600';
                    @endphp
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition">
                        <td class="px-4 py-3 text-gray-800 dark:text-gray-200">{{ $punto->concepto }}</td>
                        <td class="px-4 py-3 text-center">
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium {{ $badge }}">
                                <i class="bi {{ $catInfo['icon'] }}"></i> {{ $catInfo['label'] }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center font-bold text-indigo-600 dark:text-indigo-400">+{{ $punto->puntos }}</td>
                        <td class="px-4 py-3 text-center text-gray-500 dark:text-gray-400 text-xs">
                            {{ \Carbon\Carbon::parse($punto->fecha)->format('d/m/Y') }}
                        </td>
                        <td class="px-4 py-3 text-center">
                            <form method="POST"
                                  action="{{ route('admin.gamificacion.puntos.destroy', $punto) }}"
                                  onsubmit="return confirm('¿Eliminar este punto?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                    class="inline-flex items-center gap-1 px-2 py-1 text-xs bg-red-50 hover:bg-red-100 text-red-600 dark:bg-red-900/20 dark:hover:bg-red-900/40 dark:text-red-400 rounded-lg transition">
                                    <i class="bi bi-trash-fill"></i>
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

{{-- Modal asignar puntos --}}
<div id="modalPuntos" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl w-full max-w-md p-6 space-y-4">
        <div class="flex items-center justify-between">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                <i class="bi bi-plus-circle-fill text-green-500"></i> Asignar Puntos
            </h3>
            <button onclick="document.getElementById('modalPuntos').classList.add('hidden')"
                class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                <i class="bi bi-x-lg text-lg"></i>
            </button>
        </div>
        <p class="text-sm text-gray-600 dark:text-gray-300">
            Estudiante: <span class="font-semibold">{{ $matricula->estudiante?->nombre_completo }}</span>
        </p>
        <form method="POST" action="{{ route('admin.gamificacion.asignar-puntos') }}" class="space-y-3">
            @csrf
            <input type="hidden" name="matricula_id" value="{{ $matricula->id }}">
            <input type="hidden" name="_redirect" value="{{ route('admin.gamificacion.detalle', $matricula) }}">
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
                <button type="button" onclick="document.getElementById('modalPuntos').classList.add('hidden')"
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
@endsection
