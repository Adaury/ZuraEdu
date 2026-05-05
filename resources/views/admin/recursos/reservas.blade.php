@extends('layouts.admin')

@section('page-title', 'Reservas — ' . $recurso->nombre)

@section('content')
<div class="space-y-6" x-data="reservasPage()">

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
                <h1 class="text-2xl font-bold text-gray-800 dark:text-white">Reservas</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                    {{ $recurso->nombre }}
                    @if($recurso->capacidad)
                        &middot; Cap. {{ $recurso->capacidad }} pers.
                    @endif
                    @if($recurso->ubicacion)
                        &middot; {{ $recurso->ubicacion }}
                    @endif
                </p>
            </div>
        </div>
        <button @click="modalNueva = true"
                class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Nueva Reserva
        </button>
    </div>

    {{-- Alertas --}}
    @if(session('success'))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
             class="flex items-center gap-3 p-4 bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-700 rounded-lg text-green-800 dark:text-green-200 text-sm">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="flex items-center gap-3 p-4 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-700 rounded-lg text-red-800 dark:text-red-200 text-sm">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12A9 9 0 113 12a9 9 0 0118 0z"/>
            </svg>
            {{ session('error') }}
        </div>
    @endif
    @if($errors->any())
        <div class="p-4 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-700 rounded-lg text-red-800 dark:text-red-200 text-sm">
            <ul class="list-disc list-inside space-y-1">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Selector de semana --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
        <form method="GET" action="{{ route('admin.recursos.reservas', $recurso) }}"
              class="flex flex-wrap items-center gap-3">
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.recursos.reservas', [$recurso, 'semana' => $fechaBase->copy()->subWeek()->toDateString()]) }}"
                   class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition text-gray-600 dark:text-gray-300">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </a>
                <span class="text-sm font-medium text-gray-700 dark:text-gray-200 min-w-[200px] text-center">
                    {{ $fechaBase->translatedFormat('d M') }} — {{ $semanaFin->translatedFormat('d M Y') }}
                </span>
                <a href="{{ route('admin.recursos.reservas', [$recurso, 'semana' => $fechaBase->copy()->addWeek()->toDateString()]) }}"
                   class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition text-gray-600 dark:text-gray-300">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
            </div>
            <input type="date" name="semana" value="{{ $fechaBase->toDateString() }}"
                   class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:ring-blue-500 focus:border-blue-500">
            <button type="submit"
                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium transition">
                Ir
            </button>
            @if($fechaBase->weekOfYear !== now()->weekOfYear)
            <a href="{{ route('admin.recursos.reservas', $recurso) }}"
               class="px-4 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 rounded-lg text-sm font-medium transition">
                Semana actual
            </a>
            @endif
        </form>
    </div>

    {{-- Calendario semanal (hora × día) --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden shadow-sm">
        <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-700">
            <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-200">Calendario semanal</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead>
                    <tr class="bg-gray-50 dark:bg-gray-700/50">
                        <th class="px-3 py-2.5 text-left text-gray-500 dark:text-gray-400 font-medium w-20 sticky left-0 bg-gray-50 dark:bg-gray-700/50">
                            Hora
                        </th>
                        @foreach($diasSemana as $dia)
                            @php $esHoy = $dia->isToday(); @endphp
                            <th class="px-2 py-2.5 text-center font-medium min-w-[110px] {{ $esHoy ? 'text-blue-600 dark:text-blue-400' : 'text-gray-600 dark:text-gray-300' }}">
                                <div>{{ $dia->translatedFormat('D') }}</div>
                                <div class="text-base {{ $esHoy ? 'bg-blue-600 text-white rounded-full w-7 h-7 flex items-center justify-center mx-auto' : '' }}">
                                    {{ $dia->format('d') }}
                                </div>
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50 dark:divide-gray-700/50">
                    @foreach($horas as $hora)
                        <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-700/20">
                            <td class="px-3 py-2 text-gray-400 dark:text-gray-500 font-mono sticky left-0 bg-white dark:bg-gray-800">
                                {{ $hora }}
                            </td>
                            @foreach($diasSemana as $dia)
                                @php
                                    $diaStr   = $dia->toDateString();
                                    $horaFin  = sprintf('%02d:00', (int)explode(':', $hora)[0] + 1);
                                    $bloque   = $reservasSemana[$diaStr] ?? collect();
                                    $ocupada  = $bloque->first(function ($r) use ($hora, $horaFin) {
                                        return $r->hora_inicio < $horaFin && $r->hora_fin > $hora;
                                    });
                                @endphp
                                <td class="px-1 py-1 text-center align-top min-w-[110px]">
                                    @if($ocupada)
                                        @php
                                            $badge = $ocupada->estado_badge;
                                            $bgMap = ['green' => 'bg-green-100 dark:bg-green-900/40 border-green-300 dark:border-green-700 text-green-800 dark:text-green-200',
                                                      'yellow'=> 'bg-yellow-100 dark:bg-yellow-900/40 border-yellow-300 dark:border-yellow-700 text-yellow-800 dark:text-yellow-200',
                                                      'red'   => 'bg-red-100 dark:bg-red-900/40 border-red-300 dark:border-red-700 text-red-700 dark:text-red-300'];
                                            $cls = $bgMap[$badge['color']] ?? $bgMap['yellow'];
                                        @endphp
                                        <div class="rounded p-1 border text-left {{ $cls }} leading-tight">
                                            <div class="font-semibold truncate max-w-[96px]">{{ Str::limit($ocupada->motivo, 20) }}</div>
                                            <div class="opacity-70">{{ substr($ocupada->hora_inicio,0,5) }}–{{ substr($ocupada->hora_fin,0,5) }}</div>
                                            <div class="opacity-60 truncate max-w-[96px]">{{ $ocupada->solicitante?->name }}</div>
                                        </div>
                                    @else
                                        <div class="h-8 rounded border border-dashed border-gray-200 dark:border-gray-600"></div>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Lista de reservas pendientes --}}
    @if($pendientes->isNotEmpty())
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden shadow-sm">
        <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
            <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-200">
                Solicitudes pendientes
            </h2>
            <span class="inline-flex items-center justify-center px-2 py-0.5 rounded-full bg-yellow-100 dark:bg-yellow-900/40 text-yellow-800 dark:text-yellow-300 text-xs font-bold">
                {{ $pendientes->count() }}
            </span>
        </div>
        <div class="divide-y divide-gray-100 dark:divide-gray-700">
            @foreach($pendientes as $reserva)
            <div class="px-4 py-3 flex flex-col sm:flex-row sm:items-center gap-3">
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="text-sm font-medium text-gray-800 dark:text-gray-100">
                            {{ $reserva->fecha->translatedFormat('D d M Y') }}
                        </span>
                        <span class="text-xs text-gray-500 dark:text-gray-400 font-mono">
                            {{ substr($reserva->hora_inicio,0,5) }} – {{ substr($reserva->hora_fin,0,5) }}
                        </span>
                        <span class="text-xs text-gray-500 dark:text-gray-400">
                            ({{ $reserva->duracion }})
                        </span>
                    </div>
                    <p class="text-sm text-gray-700 dark:text-gray-300 mt-0.5">{{ $reserva->motivo }}</p>
                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">
                        Solicitado por: {{ $reserva->solicitante?->name }}
                    </p>
                    @if($reserva->notas)
                        <p class="text-xs text-gray-400 dark:text-gray-500 italic mt-0.5">{{ $reserva->notas }}</p>
                    @endif
                </div>
                <div class="flex items-center gap-2 flex-shrink-0">
                    {{-- Aprobar --}}
                    <form method="POST" action="{{ route('admin.recursos.reserva.aprobar', $reserva) }}">
                        @csrf @method('PATCH')
                        <button type="submit"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-green-600 hover:bg-green-700 text-white rounded-lg text-xs font-medium transition">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Aprobar
                        </button>
                    </form>
                    {{-- Rechazar --}}
                    <form method="POST" action="{{ route('admin.recursos.reserva.rechazar', $reserva) }}"
                          x-data="{ notas: '' }"
                          @submit.prevent="
                              let n = prompt('Motivo del rechazo (opcional):','');
                              $el.querySelector('[name=notas]').value = n ?? '';
                              $el.submit();
                          ">
                        @csrf @method('PATCH')
                        <input type="hidden" name="notas">
                        <button type="submit"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-red-600 hover:bg-red-700 text-white rounded-lg text-xs font-medium transition">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                            Rechazar
                        </button>
                    </form>
                    {{-- Cancelar --}}
                    <form method="POST" action="{{ route('admin.recursos.reserva.cancelar', $reserva) }}"
                          x-data @submit.prevent="if(confirm('¿Cancelar esta reserva?')) $el.submit()">
                        @csrf @method('DELETE')
                        <button type="submit" title="Cancelar reserva"
                                class="p-1.5 text-gray-400 hover:text-red-500 dark:text-gray-500 dark:hover:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/30 rounded-lg transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                        </button>
                    </form>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Modal Nueva Reserva --}}
    <div x-show="modalNueva" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100">
        <div class="absolute inset-0 bg-black/50 dark:bg-black/70" @click="modalNueva = false"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-md p-6"
             @click.stop>
            <div class="flex items-center justify-between mb-5">
                <h3 class="text-lg font-bold text-gray-800 dark:text-white">Nueva Reserva</h3>
                <button @click="modalNueva = false" class="p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <form method="POST" action="{{ route('admin.recursos.reservas.store', $recurso) }}" class="space-y-4">
                @csrf

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                        Fecha <span class="text-red-500">*</span>
                    </label>
                    <input type="date" name="fecha" required
                           min="{{ today()->toDateString() }}"
                           value="{{ old('fecha', today()->toDateString()) }}"
                           class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-blue-500 focus:border-blue-500 text-sm">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                            Desde <span class="text-red-500">*</span>
                        </label>
                        <input type="time" name="hora_inicio" required value="{{ old('hora_inicio', '08:00') }}"
                               class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-blue-500 focus:border-blue-500 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                            Hasta <span class="text-red-500">*</span>
                        </label>
                        <input type="time" name="hora_fin" required value="{{ old('hora_fin', '10:00') }}"
                               class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-blue-500 focus:border-blue-500 text-sm">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                        Motivo <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="motivo" required maxlength="250"
                           placeholder="Ej: Clase de Matemáticas – 5to B"
                           value="{{ old('motivo') }}"
                           class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-blue-500 focus:border-blue-500 text-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                        Notas adicionales
                    </label>
                    <textarea name="notas" rows="2" maxlength="500"
                              placeholder="Información adicional opcional…"
                              class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-blue-500 focus:border-blue-500 text-sm resize-none">{{ old('notas') }}</textarea>
                </div>

                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" @click="modalNueva = false"
                            class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition">
                        Cancelar
                    </button>
                    <button type="submit"
                            class="px-5 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition">
                        Enviar solicitud
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>

@push('scripts')
<script>
function reservasPage() {
    return {
        modalNueva: {{ $errors->any() ? 'true' : 'false' }},
    }
}
</script>
@endpush
@endsection
