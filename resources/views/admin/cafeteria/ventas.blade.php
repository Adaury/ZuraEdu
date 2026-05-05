@extends('layouts.admin')

@section('page-title', 'Ventas y Recargas — Cafetería')

@section('content')
<div class="px-4 py-6 max-w-7xl mx-auto" x-data="{ modalVenta: false, modalRecarga: false }">

    {{-- Encabezado --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Ventas y Recargas</h1>
            <p class="text-sm text-slate-500 mt-0.5">Registro de movimientos de cafetería</p>
        </div>
        <div class="flex gap-2 flex-wrap">
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
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 4v16m8-8H4"/>
                </svg>
                Recargar Saldo
            </button>
        </div>
    </div>

    {{-- Alertas --}}
    @if(session('success'))
        <div class="mb-4 bg-green-50 border border-green-200 text-green-800 text-sm rounded-lg px-4 py-3 flex items-center gap-2">
            <svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
            </svg>
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="mb-4 bg-red-50 border border-red-200 text-red-800 text-sm rounded-lg px-4 py-3 flex items-center gap-2">
            <svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm-1-5a1 1 0 112 0v-4a1 1 0 11-2 0v4zm1-8a1 1 0 100 2 1 1 0 000-2z" clip-rule="evenodd"/>
            </svg>
            {{ session('error') }}
        </div>
    @endif

    {{-- Tarjetas resumen del día --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-4">
            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-1">Ventas Hoy</p>
            <p class="text-2xl font-bold text-slate-800">RD$ {{ number_format($totalVentasHoy, 2) }}</p>
        </div>
        <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-4">
            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-1">Recargas Hoy</p>
            <p class="text-2xl font-bold text-emerald-600">RD$ {{ number_format($totalRecargasHoy, 2) }}</p>
        </div>
        <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-4 flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-1">Reporte Diario</p>
                <p class="text-sm text-slate-500">{{ now()->format('d/m/Y') }}</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('admin.cafeteria.reporte-pdf', ['fecha' => now()->toDateString()]) }}"
                   class="inline-flex items-center gap-1 bg-red-100 hover:bg-red-200 text-red-700
                          text-xs font-medium px-3 py-1.5 rounded-lg transition"
                   target="_blank">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                    </svg>
                    PDF
                </a>
                <a href="{{ route('admin.cafeteria.reporte-csv', ['fecha' => now()->toDateString()]) }}"
                   class="inline-flex items-center gap-1 bg-green-100 hover:bg-green-200 text-green-700
                          text-xs font-medium px-3 py-1.5 rounded-lg transition">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    CSV
                </a>
            </div>
        </div>
    </div>

    {{-- Filtros --}}
    <form method="GET" class="bg-white rounded-xl border border-slate-100 shadow-sm p-4 mb-5">
        <div class="flex flex-wrap gap-3 items-end">
            <div class="flex-1 min-w-[160px]">
                <label class="block text-xs font-medium text-slate-500 mb-1">Buscar</label>
                <input type="text" name="q" value="{{ request('q') }}"
                       placeholder="Estudiante o producto..."
                       class="w-full text-sm border border-slate-200 rounded-lg px-3 py-2
                              focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="min-w-[130px]">
                <label class="block text-xs font-medium text-slate-500 mb-1">Tipo</label>
                <select name="tipo" class="w-full text-sm border border-slate-200 rounded-lg px-3 py-2
                                           focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Todos</option>
                    <option value="venta"   {{ request('tipo') === 'venta'   ? 'selected' : '' }}>Ventas</option>
                    <option value="recarga" {{ request('tipo') === 'recarga' ? 'selected' : '' }}>Recargas</option>
                    <option value="ajuste"  {{ request('tipo') === 'ajuste'  ? 'selected' : '' }}>Ajustes</option>
                </select>
            </div>
            <div class="min-w-[130px]">
                <label class="block text-xs font-medium text-slate-500 mb-1">Desde</label>
                <input type="date" name="fecha_desde" value="{{ request('fecha_desde') }}"
                       class="w-full text-sm border border-slate-200 rounded-lg px-3 py-2
                              focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="min-w-[130px]">
                <label class="block text-xs font-medium text-slate-500 mb-1">Hasta</label>
                <input type="date" name="fecha_hasta" value="{{ request('fecha_hasta') }}"
                       class="w-full text-sm border border-slate-200 rounded-lg px-3 py-2
                              focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="flex gap-2">
                <button type="submit"
                        class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium
                               px-4 py-2 rounded-lg transition">Filtrar</button>
                <a href="{{ route('admin.cafeteria.ventas') }}"
                   class="bg-slate-100 hover:bg-slate-200 text-slate-600 text-sm font-medium
                          px-4 py-2 rounded-lg transition">Limpiar</a>

                {{-- Reporte con filtros --}}
                <a href="{{ route('admin.cafeteria.reporte-pdf', request()->all()) }}"
                   class="inline-flex items-center gap-1 bg-red-50 hover:bg-red-100 text-red-700
                          text-sm font-medium px-4 py-2 rounded-lg transition"
                   target="_blank" title="Exportar resultado actual a PDF">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                    </svg>
                    PDF
                </a>
                <a href="{{ route('admin.cafeteria.reporte-csv', request()->all()) }}"
                   class="inline-flex items-center gap-1 bg-green-50 hover:bg-green-100 text-green-700
                          text-sm font-medium px-4 py-2 rounded-lg transition"
                   title="Exportar resultado actual a CSV">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    CSV
                </a>
            </div>
        </div>
    </form>

    {{-- Tabla de movimientos --}}
    <div class="bg-white rounded-xl border border-slate-100 shadow-sm overflow-hidden">
        @if($movimientos->isEmpty())
            <div class="py-16 text-center text-slate-400">
                <svg class="mx-auto w-10 h-10 mb-3 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                <p class="text-sm">Sin movimientos en este período</p>
            </div>
        @else
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-100 text-left">
                        <th class="px-4 py-3 font-semibold text-slate-600">Fecha / Hora</th>
                        <th class="px-4 py-3 font-semibold text-slate-600">Estudiante</th>
                        <th class="px-4 py-3 font-semibold text-slate-600">Tipo</th>
                        <th class="px-4 py-3 font-semibold text-slate-600">Producto / Descripción</th>
                        <th class="px-4 py-3 font-semibold text-slate-600 text-right">Monto</th>
                        <th class="px-4 py-3 font-semibold text-slate-600 text-right">Saldo Anterior</th>
                        <th class="px-4 py-3 font-semibold text-slate-600 text-right">Saldo Nuevo</th>
                        <th class="px-4 py-3 font-semibold text-slate-600 text-center">Registrado por</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @foreach($movimientos as $mov)
                    <tr class="hover:bg-slate-50/50 transition">
                        <td class="px-4 py-3 text-slate-500 whitespace-nowrap">
                            {{ $mov->created_at->format('d/m/Y') }}<br>
                            <span class="text-xs text-slate-400">{{ $mov->created_at->format('H:i') }}</span>
                        </td>
                        <td class="px-4 py-3">
                            <a href="{{ route('admin.cafeteria.balance', $mov->estudiante_id) }}"
                               class="font-medium text-blue-700 hover:underline">
                                {{ $mov->estudiante?->nombre_completo ?? '—' }}
                            </a>
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
                        <td class="px-4 py-3 text-right text-slate-500">
                            RD$ {{ number_format($mov->saldo_anterior, 2) }}
                        </td>
                        <td class="px-4 py-3 text-right font-semibold text-slate-800">
                            RD$ {{ number_format($mov->saldo_nuevo, 2) }}
                        </td>
                        <td class="px-4 py-3 text-center text-slate-500 text-xs">
                            {{ $mov->creadoPor?->name ?? '—' }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            @if($movimientos->hasPages())
                <div class="px-4 py-3 border-t border-slate-100">
                    {{ $movimientos->links() }}
                </div>
            @endif
        @endif
    </div>

    {{-- Acceso a productos --}}
    <div class="mt-4">
        <a href="{{ route('admin.cafeteria.productos.index') }}"
           class="inline-flex items-center gap-2 text-sm text-slate-500 hover:text-blue-600 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
            </svg>
            Gestionar Productos
        </a>
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

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">
                    Estudiante <span class="text-red-500">*</span>
                </label>
                <select name="estudiante_id" required
                        class="w-full text-sm border border-slate-200 rounded-lg px-3 py-2
                               focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">-- Seleccionar --</option>
                    @foreach($estudiantes as $est)
                        <option value="{{ $est->id }}">{{ $est->nombre_completo }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">
                    Producto (opcional)
                </label>
                <select name="producto_id" id="selectProductoVenta"
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
                <label class="block text-sm font-medium text-slate-700 mb-1">
                    Descripción (opcional)
                </label>
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

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">
                    Estudiante <span class="text-red-500">*</span>
                </label>
                <select name="estudiante_id" required
                        class="w-full text-sm border border-slate-200 rounded-lg px-3 py-2
                               focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">-- Seleccionar --</option>
                    @foreach($estudiantes as $est)
                        <option value="{{ $est->id }}">{{ $est->nombre_completo }}</option>
                    @endforeach
                </select>
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
                <label class="block text-sm font-medium text-slate-700 mb-1">
                    Nota (opcional)
                </label>
                <input type="text" name="descripcion" maxlength="200"
                       placeholder="Ej. Pago en efectivo, transferencia..."
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
