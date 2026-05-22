@extends('layouts.admin')

@section('page-title', 'Dashboard Financiero')

@section('content')
<div class="space-y-6">

    {{-- Encabezado --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Dashboard Financiero</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                @if($syActual)
                    Año escolar: <span class="font-medium text-gray-700 dark:text-gray-300">{{ $syActual->nombre }}</span>
                @else
                    <span class="text-amber-600">Sin año escolar activo</span>
                @endif
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.pagos.resumen-mensual-excel') }}"
               class="inline-flex items-center gap-1.5 text-sm font-medium bg-white dark:bg-gray-800
                      border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300
                      rounded-lg px-3 py-2 hover:shadow-sm transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Resumen Excel
            </a>
            <a href="{{ route('admin.pagos.deudores') }}"
               class="inline-flex items-center gap-1.5 text-sm font-medium bg-white dark:bg-gray-800
                      border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300
                      rounded-lg px-3 py-2 hover:shadow-sm transition {{ $countVencidos > 0 ? 'ring-1 ring-red-300' : '' }}">
                <svg class="w-4 h-4 {{ $countVencidos > 0 ? 'text-red-500' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Deudores
                @if($countVencidos > 0)
                    <span class="bg-red-500 text-white text-xs font-bold px-1.5 py-0.5 rounded-full">{{ $countVencidos }}</span>
                @endif
            </a>
            <a href="{{ route('admin.pagos.create') }}"
               class="inline-flex items-center gap-1.5 text-sm font-semibold bg-green-600 hover:bg-green-700
                      text-white rounded-lg px-4 py-2 shadow transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Registrar pago
            </a>
        </div>
    </div>

    {{-- Flash --}}
    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-700 rounded-lg px-4 py-3 text-sm">
            {{ session('success') }}
        </div>
    @endif

    {{-- Tarjetas financieras --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">

        {{-- Recaudado --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-5">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Total Recaudado</span>
                <div class="w-9 h-9 rounded-xl bg-green-100 dark:bg-green-900/30 flex items-center justify-center">
                    <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            <p class="text-3xl font-black text-green-700 dark:text-green-400">
                RD${{ number_format($totalPagado, 0, '.', ',') }}
            </p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                {{ number_format($countPagados) }} pago{{ $countPagados !== 1 ? 's' : '' }} confirmado{{ $countPagados !== 1 ? 's' : '' }}
            </p>
        </div>

        {{-- Pendiente --}}
        <a href="{{ route('admin.pagos.index', ['estado' => 'pendiente']) }}"
           class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700
                  p-5 hover:shadow-md transition">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Pendiente de Cobro</span>
                <div class="w-9 h-9 rounded-xl bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center">
                    <svg class="w-5 h-5 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            <p class="text-3xl font-black text-amber-700 dark:text-amber-400">
                RD${{ number_format($totalPendiente, 0, '.', ',') }}
            </p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                {{ number_format($countPendientes) }} cuota{{ $countPendientes !== 1 ? 's' : '' }} por cobrar
            </p>
        </a>

        {{-- Vencido --}}
        <a href="{{ route('admin.pagos.index', ['estado' => 'vencido']) }}"
           class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700
                  p-5 hover:shadow-md transition {{ $countVencidos > 0 ? 'ring-1 ring-red-300' : '' }}">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">En Mora</span>
                <div class="w-9 h-9 rounded-xl bg-red-100 dark:bg-red-900/30 flex items-center justify-center">
                    <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            <p class="text-3xl font-black {{ $countVencidos > 0 ? 'text-red-700 dark:text-red-400' : 'text-gray-600 dark:text-gray-400' }}">
                RD${{ number_format($totalVencido, 0, '.', ',') }}
            </p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                {{ number_format($countVencidos) }} cuota{{ $countVencidos !== 1 ? 's' : '' }} vencida{{ $countVencidos !== 1 ? 's' : '' }}
            </p>
        </a>

    </div>

    {{-- Gráfica de recaudación mensual + Top deudores --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Recaudación por mes --}}
        <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-5">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Recaudación mensual</h2>
                <a href="{{ route('admin.pagos.index', ['estado' => 'pagado']) }}"
                   class="text-xs text-green-600 dark:text-green-400 hover:underline font-medium">Ver todos</a>
            </div>

            @if($cobrosMes->isEmpty())
                <div class="flex items-center justify-center h-32 text-sm text-gray-400">
                    Sin pagos registrados aún
                </div>
            @else
                @php $maxVal = $cobrosMes->max() ?: 1; @endphp
                <div class="flex items-end gap-2 h-32">
                    @foreach($cobrosMes as $mes => $monto)
                        @php
                            $pct = round(($monto / $maxVal) * 100);
                            $label = \Carbon\Carbon::createFromFormat('Y-m', $mes)->locale('es')->isoFormat('MMM YY');
                        @endphp
                        <div class="flex-1 flex flex-col items-center gap-1 group">
                            <span class="text-xs text-gray-500 dark:text-gray-400 opacity-0 group-hover:opacity-100 transition
                                         whitespace-nowrap text-center bg-gray-800 text-white px-1.5 py-0.5 rounded text-[10px]">
                                RD${{ number_format($monto, 0, '.', ',') }}
                            </span>
                            <div class="w-full bg-green-500 dark:bg-green-600 rounded-t-md transition-all"
                                 style="height: {{ max(4, $pct) }}%"></div>
                            <span class="text-[10px] text-gray-400 truncate w-full text-center">{{ $label }}</span>
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- Últimos pagos --}}
            @if($ultimosPagos->isNotEmpty())
                <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700">
                    <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2">Últimos pagos</p>
                    <div class="space-y-1.5">
                        @foreach($ultimosPagos as $pago)
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-700 dark:text-gray-300 truncate">
                                    {{ $pago->matricula?->estudiante?->nombres }}
                                    {{ $pago->matricula?->estudiante?->apellidos }}
                                    <span class="text-gray-400 text-xs">— {{ $pago->concepto }}</span>
                                </span>
                                <span class="ml-3 shrink-0 font-semibold text-green-600 dark:text-green-400">
                                    RD${{ number_format($pago->monto, 0, '.', ',') }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        {{-- Top deudores + Acciones --}}
        <div class="space-y-5">

            {{-- Top deudores --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-5">
                <div class="flex items-center justify-between mb-3">
                    <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Mayor deuda</h2>
                    <a href="{{ route('admin.pagos.deudores') }}"
                       class="text-xs text-red-600 dark:text-red-400 hover:underline font-medium">Ver todos</a>
                </div>
                @forelse($topDeudores as $item)
                    @php $est = $item->matricula?->estudiante; @endphp
                    <div class="flex items-center justify-between py-1.5 text-sm border-b border-gray-50 dark:border-gray-700/50 last:border-0">
                        <div class="min-w-0">
                            <p class="font-medium text-gray-800 dark:text-gray-200 truncate text-xs">
                                {{ $est?->nombres }} {{ $est?->apellidos }}
                            </p>
                            <p class="text-[10px] text-gray-400">{{ $item->cuotas }} cuota{{ $item->cuotas != 1 ? 's' : '' }}</p>
                        </div>
                        <span class="ml-2 shrink-0 text-xs font-bold text-red-600 dark:text-red-400">
                            RD${{ number_format($item->deuda_total, 0, '.', ',') }}
                        </span>
                    </div>
                @empty
                    <p class="text-xs text-green-600 dark:text-green-400 text-center py-3 font-medium">
                        ✓ Sin deudores vencidos
                    </p>
                @endforelse
            </div>

            {{-- Acciones rápidas --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-5">
                <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Acciones rápidas</h2>
                <div class="space-y-2">
                    <a href="{{ route('admin.pagos.index') }}"
                       class="flex items-center gap-2.5 text-sm text-gray-700 dark:text-gray-300 hover:text-blue-600
                              dark:hover:text-blue-400 transition p-2 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M4 6h16M4 10h16M4 14h16M4 18h7"/>
                        </svg>
                        Lista de pagos
                    </a>
                    <a href="{{ route('admin.pagos.generar-cuotas') }}"
                       class="flex items-center gap-2.5 text-sm text-gray-700 dark:text-gray-300 hover:text-indigo-600
                              dark:hover:text-indigo-400 transition p-2 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        Generar cuotas
                    </a>
                    <a href="{{ route('admin.pagos.deudores.recordatorio') }}" onclick="return confirm('¿Enviar recordatorio a todos los deudores?')"
                       class="flex items-center gap-2.5 text-sm text-gray-700 dark:text-gray-300 hover:text-amber-600
                              dark:hover:text-amber-400 transition p-2 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                        </svg>
                        Enviar recordatorio
                    </a>
                    <a href="{{ route('admin.pagos.lista-excel') }}"
                       class="flex items-center gap-2.5 text-sm text-gray-700 dark:text-gray-300 hover:text-emerald-600
                              dark:hover:text-emerald-400 transition p-2 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        Exportar Excel
                    </a>
                    <a href="{{ route('admin.pagos.conceptos') }}"
                       class="flex items-center gap-2.5 text-sm text-gray-700 dark:text-gray-300 hover:text-gray-600
                              dark:hover:text-gray-200 transition p-2 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                        </svg>
                        Conceptos de pago
                    </a>
                </div>
            </div>

        </div>

    </div>

</div>
@endsection
