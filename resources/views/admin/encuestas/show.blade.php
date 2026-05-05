@extends('layouts.admin')

@section('page-title', 'Resultados: ' . $encuesta->titulo)

@section('content')
<div class="space-y-6">

    {{-- Encabezado --}}
    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
        <div class="flex items-start gap-3">
            <a href="{{ route('admin.encuestas.index') }}"
               class="mt-1 p-2 rounded-lg text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-700 dark:hover:text-gray-200 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $encuesta->titulo }}</h1>
                <div class="flex flex-wrap items-center gap-2 mt-1">
                    <span class="text-sm text-gray-500 dark:text-gray-400">{{ $encuesta->dirigidaALabel }}</span>
                    @if($encuesta->activo)
                        <span class="px-2 py-0.5 rounded-full text-xs bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300">Activa</span>
                    @else
                        <span class="px-2 py-0.5 rounded-full text-xs bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300">Inactiva</span>
                    @endif
                    @if($encuesta->fecha_cierre)
                        <span class="text-xs text-gray-400">Cierre: {{ $encuesta->fecha_cierre->format('d/m/Y') }}</span>
                    @endif
                </div>
            </div>
        </div>
        <div class="flex items-center gap-2 flex-shrink-0">
            <a href="{{ route('admin.encuestas.edit', $encuesta) }}"
               class="px-3 py-2 text-sm border border-indigo-300 text-indigo-600 hover:bg-indigo-50 dark:border-indigo-700 dark:text-indigo-400 dark:hover:bg-indigo-900/20 rounded-lg transition">
                Editar
            </a>
            <form method="POST" action="{{ route('admin.encuestas.toggle-activo', $encuesta) }}">
                @csrf @method('PATCH')
                <button type="submit"
                        class="px-3 py-2 text-sm border rounded-lg transition {{ $encuesta->activo ? 'border-red-300 text-red-600 hover:bg-red-50 dark:border-red-700 dark:text-red-400 dark:hover:bg-red-900/20' : 'border-green-300 text-green-600 hover:bg-green-50 dark:border-green-700 dark:text-green-400 dark:hover:bg-green-900/20' }}">
                    {{ $encuesta->activo ? 'Desactivar' : 'Activar' }}
                </button>
            </form>
        </div>
    </div>

    {{-- Flash --}}
    @if(session('success'))
        <div class="p-4 bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-700 rounded-lg text-green-800 dark:text-green-300 text-sm">
            {{ session('success') }}
        </div>
    @endif

    {{-- Resumen --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 text-center shadow-sm">
            <p class="text-3xl font-bold text-blue-600 dark:text-blue-400">{{ $totalParticipantes }}</p>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Participantes únicos</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 text-center shadow-sm">
            <p class="text-3xl font-bold text-indigo-600 dark:text-indigo-400">{{ $encuesta->preguntas->count() }}</p>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Preguntas</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 text-center shadow-sm">
            <p class="text-3xl font-bold text-purple-600 dark:text-purple-400">{{ $encuesta->respuestas->count() }}</p>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Respuestas totales</p>
        </div>
    </div>

    {{-- Resultados por pregunta --}}
    @forelse($estadisticas as $item)
        @php
            $pregunta = $item['pregunta'];
            $stats    = $item['estadisticas'];
        @endphp
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-start gap-3 mb-4">
                <span class="flex-shrink-0 w-7 h-7 rounded-full bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300 text-sm font-bold flex items-center justify-center">
                    {{ $loop->iteration }}
                </span>
                <div class="flex-1">
                    <p class="font-semibold text-gray-900 dark:text-white">{{ $pregunta->texto }}</p>
                    <span class="text-xs text-gray-400">{{ $pregunta->tipoLabel }} &bull; {{ $stats['total'] }} respuesta(s)</span>
                </div>
            </div>

            @if($stats['tipo'] === 'opcion_multiple')
                @if($stats['total'] > 0)
                    <div class="flex flex-col lg:flex-row gap-6 items-center">
                        {{-- Gráfica donut --}}
                        <div class="w-48 h-48 flex-shrink-0">
                            <canvas id="chart-{{ $pregunta->id }}" width="192" height="192"></canvas>
                        </div>
                        {{-- Tabla de resultados --}}
                        <div class="flex-1 w-full space-y-2">
                            @foreach($stats['data'] as $fila)
                                <div>
                                    <div class="flex justify-between text-sm mb-1">
                                        <span class="text-gray-700 dark:text-gray-300">{{ $fila['label'] }}</span>
                                        <span class="font-medium text-gray-900 dark:text-white">{{ $fila['count'] }} ({{ $fila['porcentaje'] }}%)</span>
                                    </div>
                                    <div class="w-full bg-gray-100 dark:bg-gray-700 rounded-full h-2">
                                        <div class="bg-blue-500 h-2 rounded-full transition-all"
                                             style="width: {{ $fila['porcentaje'] }}%"></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    @push('scripts')
                    <script>
                    (function() {
                        const ctx = document.getElementById('chart-{{ $pregunta->id }}');
                        if (!ctx) return;
                        new Chart(ctx.getContext('2d'), {
                            type: 'doughnut',
                            data: {
                                labels: @json($stats['data']->pluck('label')),
                                datasets: [{
                                    data:  @json($stats['data']->pluck('count')),
                                    backgroundColor: [
                                        '#3b82f6','#8b5cf6','#10b981','#f59e0b','#ef4444',
                                        '#06b6d4','#ec4899','#84cc16','#f97316','#6366f1'
                                    ],
                                    borderWidth: 2,
                                    borderColor: '#fff',
                                }]
                            },
                            options: {
                                responsive: false,
                                plugins: {
                                    legend: { display: false },
                                    tooltip: {
                                        callbacks: {
                                            label: (ctx) => ` ${ctx.label}: ${ctx.raw} respuestas`
                                        }
                                    }
                                },
                                cutout: '60%',
                            }
                        });
                    })();
                    </script>
                    @endpush
                @else
                    <p class="text-sm text-gray-400 dark:text-gray-500 text-center py-6">Sin respuestas aún.</p>
                @endif

            @elseif($stats['tipo'] === 'escala_1_5')
                @if($stats['total'] > 0)
                    <div class="flex flex-col lg:flex-row gap-6 items-center">
                        <div class="w-48 h-48 flex-shrink-0">
                            <canvas id="chart-{{ $pregunta->id }}" width="192" height="192"></canvas>
                        </div>
                        <div class="flex-1 w-full space-y-2">
                            @php
                                $colores = ['1' => 'bg-red-500', '2' => 'bg-orange-400', '3' => 'bg-yellow-400', '4' => 'bg-lime-500', '5' => 'bg-green-500'];
                                $etiquetas = ['1' => '1 - Muy malo', '2' => '2 - Malo', '3' => '3 - Regular', '4' => '4 - Bueno', '5' => '5 - Excelente'];
                            @endphp
                            @foreach($stats['data'] as $fila)
                                <div>
                                    <div class="flex justify-between text-sm mb-1">
                                        <span class="text-gray-700 dark:text-gray-300">{{ $etiquetas[$fila['label']] ?? $fila['label'] }}</span>
                                        <span class="font-medium text-gray-900 dark:text-white">{{ $fila['count'] }} ({{ $fila['porcentaje'] }}%)</span>
                                    </div>
                                    <div class="w-full bg-gray-100 dark:bg-gray-700 rounded-full h-2">
                                        <div class="{{ $colores[$fila['label']] ?? 'bg-blue-500' }} h-2 rounded-full"
                                             style="width: {{ $fila['porcentaje'] }}%"></div>
                                    </div>
                                </div>
                            @endforeach
                            <p class="text-sm text-gray-600 dark:text-gray-400 pt-2">
                                Promedio: <strong>{{ number_format($stats['promedio'], 2) }}</strong> / 5
                            </p>
                        </div>
                    </div>
                    @push('scripts')
                    <script>
                    (function() {
                        const ctx = document.getElementById('chart-{{ $pregunta->id }}');
                        if (!ctx) return;
                        new Chart(ctx.getContext('2d'), {
                            type: 'doughnut',
                            data: {
                                labels: ['1','2','3','4','5'],
                                datasets: [{
                                    data:  @json($stats['data']->pluck('count')),
                                    backgroundColor: ['#ef4444','#f97316','#eab308','#84cc16','#22c55e'],
                                    borderWidth: 2,
                                    borderColor: '#fff',
                                }]
                            },
                            options: {
                                responsive: false,
                                plugins: {
                                    legend: { display: false },
                                    tooltip: {
                                        callbacks: {
                                            label: (ctx) => ` ${ctx.label}: ${ctx.raw} respuestas`
                                        }
                                    }
                                },
                                cutout: '60%',
                            }
                        });
                    })();
                    </script>
                    @endpush
                @else
                    <p class="text-sm text-gray-400 dark:text-gray-500 text-center py-6">Sin respuestas aún.</p>
                @endif

            @else
                {{-- Texto libre --}}
                @if($stats['total'] > 0)
                    <div class="space-y-2 max-h-60 overflow-y-auto">
                        @foreach($stats['textos'] as $texto)
                            <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg px-4 py-2.5 text-sm text-gray-700 dark:text-gray-300">
                                {{ $texto }}
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-gray-400 dark:text-gray-500 text-center py-6">Sin respuestas aún.</p>
                @endif
            @endif
        </div>
    @empty
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-10 text-center">
            <p class="text-gray-400 dark:text-gray-500">Esta encuesta no tiene preguntas.</p>
        </div>
    @endforelse

</div>
@endsection
