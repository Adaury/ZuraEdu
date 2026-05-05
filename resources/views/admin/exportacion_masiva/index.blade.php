@extends('layouts.admin')

@section('page-title', 'Exportación Masiva')

@section('content')
<div class="space-y-6" x-data="exportacionMasiva()">

    {{-- Encabezado --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Exportación Masiva</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                Genera y descarga múltiples reportes en un solo archivo ZIP.
                Año escolar: <span class="font-semibold">{{ $schoolYear->nombre }}</span>
            </p>
        </div>
        <div class="text-blue-600 dark:text-blue-400">
            <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                    d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
            </svg>
        </div>
    </div>

    {{-- Alertas --}}
    @if(session('error'))
    <div class="rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-700 p-4 text-red-700 dark:text-red-300 text-sm">
        {{ session('error') }}
    </div>
    @endif

    {{-- Formulario principal --}}
    <form method="POST" action="{{ route('admin.exportacion-masiva.exportar') }}" @submit="submitting = true">
        @csrf

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            {{-- ── Columna 1: Qué exportar ─────────────────────────── --}}
            <div class="lg:col-span-1 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <h2 class="text-base font-semibold text-gray-800 dark:text-gray-200 mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414A1 1 0 0119 9.414V19a2 2 0 01-2 2z"/>
                    </svg>
                    Tipo de reporte
                </h2>

                <div class="space-y-3">
                    {{-- Matrícula --}}
                    <label class="flex items-start gap-3 p-3 rounded-lg border border-gray-200 dark:border-gray-600 cursor-pointer
                                  hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors
                                  has-[:checked]:bg-blue-50 has-[:checked]:border-blue-400 dark:has-[:checked]:bg-blue-900/30">
                        <input type="checkbox" name="exportar[]" value="matricula"
                               x-model="seleccionados"
                               class="mt-0.5 h-4 w-4 text-blue-600 rounded border-gray-300 focus:ring-blue-500">
                        <div>
                            <p class="text-sm font-medium text-gray-800 dark:text-gray-200">Lista de matrícula (CSV)</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                Todos los estudiantes activos con datos del representante
                            </p>
                        </div>
                    </label>

                    {{-- Notas --}}
                    <label class="flex items-start gap-3 p-3 rounded-lg border border-gray-200 dark:border-gray-600 cursor-pointer
                                  hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors
                                  has-[:checked]:bg-blue-50 has-[:checked]:border-blue-400 dark:has-[:checked]:bg-blue-900/30">
                        <input type="checkbox" name="exportar[]" value="notas"
                               x-model="seleccionados"
                               class="mt-0.5 h-4 w-4 text-blue-600 rounded border-gray-300 focus:ring-blue-500">
                        <div>
                            <p class="text-sm font-medium text-gray-800 dark:text-gray-200">Notas por grupo (PDF)</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                Acta de calificaciones de cada grupo seleccionado
                            </p>
                        </div>
                    </label>

                    {{-- Asistencia --}}
                    <label class="flex items-start gap-3 p-3 rounded-lg border border-gray-200 dark:border-gray-600 cursor-pointer
                                  hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors
                                  has-[:checked]:bg-blue-50 has-[:checked]:border-blue-400 dark:has-[:checked]:bg-blue-900/30">
                        <input type="checkbox" name="exportar[]" value="asistencia"
                               x-model="seleccionados"
                               class="mt-0.5 h-4 w-4 text-blue-600 rounded border-gray-300 focus:ring-blue-500">
                        <div>
                            <p class="text-sm font-medium text-gray-800 dark:text-gray-200">Asistencia por grupo (PDF)</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                Reporte de asistencia consolidada por grupo
                            </p>
                        </div>
                    </label>
                </div>

                @error('exportar')
                <p class="text-xs text-red-500 mt-2">{{ $message }}</p>
                @enderror
            </div>

            {{-- ── Columna 2: Grupos y período ─────────────────────── --}}
            <div class="lg:col-span-2 space-y-4">

                {{-- Período --}}
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                    <h2 class="text-base font-semibold text-gray-800 dark:text-gray-200 mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        Período de referencia
                        <span class="text-xs font-normal text-gray-400">(para notas y asistencia)</span>
                    </h2>

                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                        @foreach($periodos as $p)
                        <label class="flex items-center gap-2 p-2 rounded-lg border border-gray-200 dark:border-gray-600
                                      cursor-pointer hover:bg-purple-50 dark:hover:bg-purple-900/20
                                      has-[:checked]:bg-purple-50 has-[:checked]:border-purple-400 dark:has-[:checked]:bg-purple-900/30">
                            <input type="radio" name="periodo_id" value="{{ $p->id }}"
                                   {{ $loop->first ? 'checked' : '' }}
                                   class="h-4 w-4 text-purple-600 border-gray-300 focus:ring-purple-500">
                            <span class="text-sm text-gray-700 dark:text-gray-300">Período {{ $p->numero }}</span>
                        </label>
                        @endforeach
                        <label class="flex items-center gap-2 p-2 rounded-lg border border-gray-200 dark:border-gray-600
                                      cursor-pointer hover:bg-purple-50 dark:hover:bg-purple-900/20
                                      has-[:checked]:bg-purple-50 has-[:checked]:border-purple-400 dark:has-[:checked]:bg-purple-900/30">
                            <input type="radio" name="periodo_id" value=""
                                   class="h-4 w-4 text-purple-600 border-gray-300 focus:ring-purple-500">
                            <span class="text-sm text-gray-700 dark:text-gray-300">Todo el año</span>
                        </label>
                    </div>
                </div>

                {{-- Grupos --}}
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-base font-semibold text-gray-800 dark:text-gray-200 flex items-center gap-2">
                            <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            Grupos
                        </h2>
                        <div class="flex gap-2">
                            <button type="button"
                                    @click="seleccionarTodosGrupos(true)"
                                    class="text-xs text-blue-600 hover:text-blue-800 dark:text-blue-400 font-medium">
                                Todos
                            </button>
                            <span class="text-gray-300">|</span>
                            <button type="button"
                                    @click="seleccionarTodosGrupos(false)"
                                    class="text-xs text-gray-500 hover:text-gray-700 dark:text-gray-400 font-medium">
                                Ninguno
                            </button>
                        </div>
                    </div>

                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">
                        Sin selección = todos los grupos del año escolar.
                    </p>

                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-2 max-h-64 overflow-y-auto pr-1">
                        @foreach($grupos as $grupo)
                        <label class="flex items-center gap-2 p-2 rounded-lg border border-gray-200 dark:border-gray-600
                                      cursor-pointer hover:bg-green-50 dark:hover:bg-green-900/20 text-sm
                                      has-[:checked]:bg-green-50 has-[:checked]:border-green-400 dark:has-[:checked]:bg-green-900/30">
                            <input type="checkbox" name="grupo_ids[]" value="{{ $grupo->id }}"
                                   x-ref="grupo_{{ $grupo->id }}"
                                   class="h-4 w-4 text-green-600 rounded border-gray-300 focus:ring-green-500 grupo-check">
                            <span class="text-gray-700 dark:text-gray-300 leading-tight">
                                {{ $grupo->grado->nombre ?? '' }}
                                {{ $grupo->seccion->nombre ?? '' }}
                            </span>
                        </label>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        {{-- Resumen y botón de exportar --}}
        <div class="mt-6 bg-gradient-to-r from-blue-600 to-blue-700 rounded-xl p-5 flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="text-white">
                <p class="font-semibold text-base">
                    <template x-if="seleccionados.length === 0">
                        <span>Selecciona al menos un tipo de reporte</span>
                    </template>
                    <template x-if="seleccionados.length > 0">
                        <span>
                            Se generará un ZIP con:
                            <span x-text="resumenSeleccion()"></span>
                        </span>
                    </template>
                </p>
                <p class="text-blue-200 text-sm mt-1">El tiempo de procesamiento depende de la cantidad de grupos.</p>
            </div>

            <button type="submit"
                    :disabled="seleccionados.length === 0 || submitting"
                    class="flex-shrink-0 inline-flex items-center gap-2 px-6 py-3 bg-white text-blue-700 font-semibold
                           rounded-lg shadow hover:bg-blue-50 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                <template x-if="!submitting">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                </template>
                <template x-if="submitting">
                    <svg class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
                    </svg>
                </template>
                <span x-text="submitting ? 'Generando ZIP...' : 'Descargar ZIP'"></span>
            </button>
        </div>
    </form>

</div>
@endsection

@push('scripts')
<script>
function exportacionMasiva() {
    return {
        seleccionados: [],
        submitting: false,

        seleccionarTodosGrupos(state) {
            document.querySelectorAll('.grupo-check').forEach(cb => {
                cb.checked = state;
            });
        },

        resumenSeleccion() {
            const etiquetas = {
                matricula:  'Lista de matrícula (CSV)',
                notas:      'Notas por grupo (PDF)',
                asistencia: 'Asistencia por grupo (PDF)',
            };
            return this.seleccionados.map(s => etiquetas[s] || s).join(', ');
        },
    };
}
</script>
@endpush
