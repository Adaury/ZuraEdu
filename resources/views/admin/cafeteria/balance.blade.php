@extends('layouts.admin')

@section('page-title', 'Balance — ' . $estudiante->nombre_completo)

@section('content')
<div class="px-4 py-6 max-w-5xl mx-auto" x-data="{ modalVenta: false, modalRecarga: false }">

    {{-- Encabezado --}}
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('admin.cafeteria.ventas') }}"
           class="text-slate-400 hover:text-slate-700 transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Balance de Cafetería</h1>
            <p class="text-sm text-slate-500 mt-0.5">{{ $estudiante->nombre_completo }}</p>
        </div>
    </div>

    {{-- Alertas --}}
    @if(session('success'))
        <div class="mb-4 bg-green-50 border border-green-200 text-green-800 text-sm rounded-lg px-4 py-3">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="mb-4 bg-red-50 border border-red-200 text-red-800 text-sm rounded-lg px-4 py-3">
            {{ session('error') }}
        </div>
    @endif

    {{-- Tarjetas de resumen --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        {{-- Saldo actual --}}
        <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-5 sm:col-span-1">
            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-2">Saldo Actual</p>
            <p class="text-3xl font-extrabold
                      {{ $saldo >= 0 ? 'text-emerald-600' : 'text-red-600' }}">
                RD$ {{ number_format($saldo, 2) }}
            </p>
            @if($saldo < 0)
                <p class="text-xs text-red-500 mt-1">Saldo negativo — requiere recarga</p>
            @elseif($saldo < 50)
                <p class="text-xs text-amber-500 mt-1">Saldo bajo — considera recargar</p>
            @else
                <p class="text-xs text-slate-400 mt-1">Saldo disponible</p>
            @endif
        </div>

        <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-5">
            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-2">Total Gastado</p>
            <p class="text-2xl font-bold text-red-600">RD$ {{ number_format($totalGastado, 2) }}</p>
            <p class="text-xs text-slate-400 mt-1">Todas las ventas</p>
        </div>

        <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-5">
            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-2">Total Recargado</p>
            <p class="text-2xl font-bold text-emerald-600">RD$ {{ number_format($totalRecargado, 2) }}</p>
            <p class="text-xs text-slate-400 mt-1">Todas las recargas</p>
        </div>
    </div>

    {{-- Acciones rápidas --}}
    <div class="flex gap-3 mb-6">
        <button @click="modalVenta = true"
                class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white
                       text-sm font-medium px-4 py-2 rounded-lg transition shadow-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-1.5 6M17 13l1.5 6M9 19h6"/>
            </svg>
            Registrar Venta
        </button>
        <button @click="modalRecarga = true"
                class="inline-flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white
                       text-sm font-medium px-4 py-2 rounded-lg transition shadow-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Recargar Saldo
        </button>
    </div>

    {{-- Historial de movimientos --}}
    <div class="bg-white rounded-xl border border-slate-100 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100">
            <h2 class="font-semibold text-slate-700">Historial de Movimientos</h2>
        </div>

        @if($historial->isEmpty())
            <div class="py-12 text-center text-slate-400">
                <svg class="mx-auto w-10 h-10 mb-3 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                <p class="text-sm">Sin movimientos registrados para este estudiante</p>
            </div>
        @else
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-100 text-left">
                        <th class="px-4 py-3 font-semibold text-slate-600">Fecha / Hora</th>
                        <th class="px-4 py-3 font-semibold text-slate-600">Tipo</th>
                        <th class="px-4 py-3 font-semibold text-slate-600">Descripción</th>
                        <th class="px-4 py-3 font-semibold text-slate-600 text-right">Monto</th>
                        <th class="px-4 py-3 font-semibold text-slate-600 text-right">Saldo Resultante</th>
                        <th class="px-4 py-3 font-semibold text-slate-600 text-center">Operador</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @foreach($historial as $mov)
                    <tr class="hover:bg-slate-50/50 transition">
                        <td class="px-4 py-3 text-slate-500 whitespace-nowrap">
                            {{ $mov->created_at->format('d/m/Y') }}<br>
                            <span class="text-xs text-slate-400">{{ $mov->created_at->format('H:i') }}</span>
                        </td>
                        <td class="px-4 py-3">
                            @if($mov->tipo === 'venta')
                                <span class="px-2 py-0.5 bg-red-100 text-red-700 rounded-full text-xs font-medium">Venta</span>
                            @elseif($mov->tipo === 'recarga')
                                <span class="px-2 py-0.5 bg-green-100 text-green-700 rounded-full text-xs font-medium">Recarga</span>
                            @else
                                <span class="px-2 py-0.5 bg-yellow-100 text-yellow-700 rounded-full text-xs font-medium">Ajuste</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-slate-700">
                            {{ $mov->producto?->nombre ?? $mov->descripcion ?? '—' }}
                        </td>
                        <td class="px-4 py-3 text-right font-semibold
                                   {{ $mov->tipo === 'venta' ? 'text-red-600' : 'text-green-600' }}">
                            {{ $mov->tipo === 'venta' ? '-' : '+' }}RD$ {{ number_format($mov->monto, 2) }}
                        </td>
                        <td class="px-4 py-3 text-right font-bold text-slate-800">
                            RD$ {{ number_format($mov->saldo_nuevo, 2) }}
                        </td>
                        <td class="px-4 py-3 text-center text-xs text-slate-500">
                            {{ $mov->creadoPor?->name ?? '—' }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            @if($historial->hasPages())
                <div class="px-4 py-3 border-t border-slate-100">
                    {{ $historial->links() }}
                </div>
            @endif
        @endif
    </div>

</div>

{{-- ═══ Modal: Registrar Venta ══════════════════════════════════════════════ --}}
<div x-show="modalVenta" x-cloak
     class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm p-4"
     @keydown.escape.window="modalVenta = false">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md"
         @click.outside="modalVenta = false">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
            <h2 class="font-bold text-slate-800 text-lg">Registrar Venta</h2>
            <button @click="modalVenta = false" class="text-slate-400 hover:text-slate-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <form method="POST" action="{{ route('admin.cafeteria.ventas.store') }}" class="px-6 py-5 space-y-4">
            @csrf
            <input type="hidden" name="estudiante_id" value="{{ $estudiante->id }}">

            <div class="bg-slate-50 rounded-lg p-3 text-sm text-slate-600">
                Estudiante: <strong>{{ $estudiante->nombre_completo }}</strong><br>
                Saldo actual: <strong class="{{ $saldo >= 50 ? 'text-emerald-600' : 'text-amber-600' }}">
                    RD$ {{ number_format($saldo, 2) }}
                </strong>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">
                    Producto (opcional)
                </label>
                <select name="producto_id"
                        class="w-full text-sm border border-slate-200 rounded-lg px-3 py-2
                               focus:outline-none focus:ring-2 focus:ring-blue-500"
                        x-on:change="
                            let opt = $event.target.selectedOptions[0];
                            let precio = opt.dataset.precio;
                            if (precio) $root.querySelector('[name=monto]').value = precio;
                        ">
                    <option value="">-- Libre / Sin producto --</option>
                    @foreach($productos as $prod)
                        <option value="{{ $prod->id }}" data-precio="{{ $prod->precio }}">
                            {{ $prod->nombre }} — RD$ {{ number_format($prod->precio, 2) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Descripción (opcional)</label>
                <input type="text" name="descripcion" maxlength="200"
                       placeholder="Ej. Almuerzo especial..."
                       class="w-full text-sm border border-slate-200 rounded-lg px-3 py-2
                              focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">
                    Monto (RD$) <span class="text-red-500">*</span>
                </label>
                <input type="number" name="monto" step="0.01" min="0.01" required
                       placeholder="0.00"
                       class="w-full text-sm border border-slate-200 rounded-lg px-3 py-2
                              focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit"
                        class="flex-1 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium
                               py-2.5 rounded-lg transition">
                    Confirmar Venta
                </button>
                <button type="button" @click="modalVenta = false"
                        class="flex-1 bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-medium
                               py-2.5 rounded-lg transition">
                    Cancelar
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ═══ Modal: Recargar Saldo ═══════════════════════════════════════════════ --}}
<div x-show="modalRecarga" x-cloak
     class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm p-4"
     @keydown.escape.window="modalRecarga = false">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md"
         @click.outside="modalRecarga = false">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
            <h2 class="font-bold text-slate-800 text-lg">Recargar Saldo</h2>
            <button @click="modalRecarga = false" class="text-slate-400 hover:text-slate-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <form method="POST" action="{{ route('admin.cafeteria.recargas.store') }}" class="px-6 py-5 space-y-4">
            @csrf
            <input type="hidden" name="estudiante_id" value="{{ $estudiante->id }}">

            <div class="bg-slate-50 rounded-lg p-3 text-sm text-slate-600">
                Estudiante: <strong>{{ $estudiante->nombre_completo }}</strong><br>
                Saldo actual: <strong class="{{ $saldo >= 50 ? 'text-emerald-600' : 'text-amber-600' }}">
                    RD$ {{ number_format($saldo, 2) }}
                </strong>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">
                    Monto a Recargar (RD$) <span class="text-red-500">*</span>
                </label>
                <input type="number" name="monto" step="0.01" min="0.01" required
                       placeholder="0.00"
                       class="w-full text-sm border border-slate-200 rounded-lg px-3 py-2
                              focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Nota (opcional)</label>
                <input type="text" name="descripcion" maxlength="200"
                       placeholder="Ej. Pago en efectivo..."
                       class="w-full text-sm border border-slate-200 rounded-lg px-3 py-2
                              focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit"
                        class="flex-1 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium
                               py-2.5 rounded-lg transition">
                    Aplicar Recarga
                </button>
                <button type="button" @click="modalRecarga = false"
                        class="flex-1 bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-medium
                               py-2.5 rounded-lg transition">
                    Cancelar
                </button>
            </div>
        </form>
    </div>
</div>

@endsection
