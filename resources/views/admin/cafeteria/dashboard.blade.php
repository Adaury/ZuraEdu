@extends('layouts.admin')
@section('page-title', 'Cafetería — Dashboard')

@section('content')
<div class="px-4 py-6 max-w-7xl mx-auto"
     x-data="{ modalVenta: false, modalRecarga: false, modalAjuste: false }">

    {{-- Encabezado ──────────────────────────────────────────────────────── --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 flex items-center gap-2">
                <i class="bi bi-shop text-emerald-600"></i>Cafetería
            </h1>
            <p class="text-sm text-slate-500 mt-0.5">Control de ventas, recargas y saldos</p>
        </div>
        <div class="flex gap-2 flex-wrap">
            <a href="{{ route('admin.cafeteria.productos.index') }}"
               class="inline-flex items-center gap-2 bg-slate-100 hover:bg-slate-200 text-slate-700
                      text-sm font-medium px-4 py-2 rounded-lg transition">
                <i class="bi bi-grid"></i>Productos
            </a>
            <a href="{{ route('admin.cafeteria.ventas') }}"
               class="inline-flex items-center gap-2 bg-slate-100 hover:bg-slate-200 text-slate-700
                      text-sm font-medium px-4 py-2 rounded-lg transition">
                <i class="bi bi-list-ul"></i>Todos los movimientos
            </a>
            <button @click="modalVenta = true"
                    class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white
                           text-sm font-medium px-4 py-2 rounded-lg transition shadow-sm">
                <i class="bi bi-cart-plus"></i>Registrar Venta
            </button>
            <button @click="modalRecarga = true"
                    class="inline-flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white
                           text-sm font-medium px-4 py-2 rounded-lg transition shadow-sm">
                <i class="bi bi-plus-circle"></i>Recargar Saldo
            </button>
        </div>
    </div>

    {{-- Alertas ──────────────────────────────────────────────────────────── --}}
    @if(session('success'))
    <div class="mb-4 bg-green-50 border border-green-200 text-green-800 text-sm rounded-lg px-4 py-3 flex items-center gap-2">
        <i class="bi bi-check-circle-fill flex-shrink-0"></i>{{ session('success') }}
    </div>
    @endif
    @if(session('error'))
    <div class="mb-4 bg-red-50 border border-red-200 text-red-800 text-sm rounded-lg px-4 py-3 flex items-center gap-2">
        <i class="bi bi-exclamation-circle-fill flex-shrink-0"></i>{{ session('error') }}
    </div>
    @endif

    {{-- Tarjetas resumen ─────────────────────────────────────────────────── --}}
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-4">
            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-1">Ventas Hoy</p>
            <p class="text-2xl font-bold text-red-600">RD${{ number_format($totalVentasHoy, 2) }}</p>
        </div>
        <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-4">
            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-1">Recargas Hoy</p>
            <p class="text-2xl font-bold text-emerald-600">RD${{ number_format($totalRecargasHoy, 2) }}</p>
        </div>
        <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-4">
            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-1">Ventas del Mes</p>
            <p class="text-2xl font-bold text-blue-600">RD${{ number_format($totalVentasMes, 2) }}</p>
        </div>
        <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-4">
            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-1">Productos Activos</p>
            <p class="text-2xl font-bold text-slate-800">{{ $productosActivos }}</p>
        </div>
        <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-4"
             style="border-left:4px solid #f59e0b;">
            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-1">Saldo Bajo</p>
            <p class="text-2xl font-bold text-amber-600">{{ $saldosBajos }}</p>
            <p class="text-xs text-slate-400">estudiantes &lt; RD$50</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

        {{-- Acciones rápidas ───────────────────────────────────────────────── --}}
        <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-5">
            <h2 class="font-semibold text-slate-700 mb-4 flex items-center gap-2">
                <i class="bi bi-lightning-charge text-amber-500"></i>Acciones Rápidas
            </h2>
            <div class="space-y-3">
                <button @click="modalVenta = true"
                        class="w-full flex items-center gap-3 bg-blue-50 hover:bg-blue-100 text-blue-700
                               rounded-lg px-4 py-3 text-sm font-semibold transition">
                    <i class="bi bi-cart-plus text-lg"></i>
                    <div class="text-left">
                        <div>Registrar Venta</div>
                        <div class="font-normal text-xs text-blue-500">Descuenta saldo del estudiante</div>
                    </div>
                </button>
                <button @click="modalRecarga = true"
                        class="w-full flex items-center gap-3 bg-emerald-50 hover:bg-emerald-100 text-emerald-700
                               rounded-lg px-4 py-3 text-sm font-semibold transition">
                    <i class="bi bi-plus-circle text-lg"></i>
                    <div class="text-left">
                        <div>Recargar Saldo</div>
                        <div class="font-normal text-xs text-emerald-500">Agrega saldo al estudiante</div>
                    </div>
                </button>
                <button @click="modalAjuste = true"
                        class="w-full flex items-center gap-3 bg-amber-50 hover:bg-amber-100 text-amber-700
                               rounded-lg px-4 py-3 text-sm font-semibold transition">
                    <i class="bi bi-sliders text-lg"></i>
                    <div class="text-left">
                        <div>Ajuste de Saldo</div>
                        <div class="font-normal text-xs text-amber-500">Corrección manual (+ o −)</div>
                    </div>
                </button>
                <hr class="border-slate-100">
                <a href="{{ route('admin.cafeteria.productos.create') }}"
                   class="w-full flex items-center gap-3 bg-slate-50 hover:bg-slate-100 text-slate-700
                          rounded-lg px-4 py-3 text-sm font-semibold transition">
                    <i class="bi bi-plus-square text-lg"></i>
                    <div class="text-left">
                        <div>Nuevo Producto</div>
                        <div class="font-normal text-xs text-slate-400">Agregar al menú</div>
                    </div>
                </a>
                <a href="{{ route('admin.cafeteria.reporte-pdf', ['fecha' => now()->toDateString()]) }}"
                   target="_blank"
                   class="w-full flex items-center gap-3 bg-red-50 hover:bg-red-100 text-red-700
                          rounded-lg px-4 py-3 text-sm font-semibold transition">
                    <i class="bi bi-file-earmark-pdf text-lg"></i>
                    <div class="text-left">
                        <div>Reporte del Día</div>
                        <div class="font-normal text-xs text-red-400">PDF de hoy</div>
                    </div>
                </a>
            </div>
        </div>

        {{-- Últimos movimientos ─────────────────────────────────────────────── --}}
        <div class="lg:col-span-2 bg-white rounded-xl border border-slate-100 shadow-sm overflow-hidden">
            <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100">
                <h2 class="font-semibold text-slate-700 flex items-center gap-2">
                    <i class="bi bi-clock-history text-blue-500"></i>Últimos Movimientos
                </h2>
                <a href="{{ route('admin.cafeteria.ventas') }}"
                   class="text-xs text-blue-600 hover:underline">
                    Ver todos <i class="bi bi-arrow-right"></i>
                </a>
            </div>

            @if($ultimosMovimientos->isEmpty())
            <div class="py-12 text-center text-slate-400">
                <i class="bi bi-inbox fs-1 d-block mb-2 opacity-50"></i>
                <p class="text-sm">Sin movimientos registrados</p>
            </div>
            @else
            <div class="divide-y divide-slate-50">
                @foreach($ultimosMovimientos as $mov)
                <div class="flex items-center gap-3 px-5 py-3 hover:bg-slate-50/50 transition">
                    {{-- Ícono tipo --}}
                    @if($mov->tipo === 'venta')
                    <div class="w-8 h-8 rounded-full bg-red-100 flex items-center justify-center flex-shrink-0">
                        <i class="bi bi-cart text-red-600 text-sm"></i>
                    </div>
                    @elseif($mov->tipo === 'recarga')
                    <div class="w-8 h-8 rounded-full bg-emerald-100 flex items-center justify-center flex-shrink-0">
                        <i class="bi bi-plus-circle text-emerald-600 text-sm"></i>
                    </div>
                    @else
                    <div class="w-8 h-8 rounded-full bg-amber-100 flex items-center justify-center flex-shrink-0">
                        <i class="bi bi-sliders text-amber-600 text-sm"></i>
                    </div>
                    @endif

                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-1.5">
                            <span class="font-semibold text-sm text-slate-800 truncate">
                                {{ $mov->estudiante?->nombre_completo ?? '—' }}
                            </span>
                            @if($mov->tipo === 'venta')
                            <span class="text-xs bg-red-100 text-red-700 px-1.5 py-0.5 rounded-full shrink-0">Venta</span>
                            @elseif($mov->tipo === 'recarga')
                            <span class="text-xs bg-emerald-100 text-emerald-700 px-1.5 py-0.5 rounded-full shrink-0">Recarga</span>
                            @else
                            <span class="text-xs bg-amber-100 text-amber-700 px-1.5 py-0.5 rounded-full shrink-0">Ajuste</span>
                            @endif
                        </div>
                        <div class="text-xs text-slate-400">
                            {{ $mov->producto?->nombre ?? $mov->descripcion ?? '—' }}
                            · {{ $mov->created_at->format('d/m H:i') }}
                        </div>
                    </div>

                    <div class="text-right shrink-0">
                        <div class="font-bold text-sm {{ $mov->tipo === 'venta' ? 'text-red-600' : 'text-emerald-600' }}">
                            {{ $mov->tipo === 'venta' ? '−' : '+' }}RD${{ number_format($mov->monto, 2) }}
                        </div>
                        <div class="text-xs text-slate-400">
                            → RD${{ number_format($mov->saldo_nuevo, 2) }}
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
            @endif
        </div>
    </div>

</div>

{{-- ═══ Modal: Registrar Venta ══════════════════════════════════════════════ --}}
<div x-show="modalVenta" x-cloak
     class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm p-4"
     @keydown.escape.window="modalVenta = false">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md" @click.outside="modalVenta = false">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
            <h2 class="font-bold text-slate-800 text-lg flex items-center gap-2">
                <i class="bi bi-cart-plus text-blue-600"></i>Registrar Venta
            </h2>
            <button @click="modalVenta = false" class="text-slate-400 hover:text-slate-600">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <form method="POST" action="{{ route('admin.cafeteria.ventas.store') }}" class="px-6 py-5 space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Estudiante <span class="text-red-500">*</span></label>
                <select name="estudiante_id" required class="w-full text-sm border border-slate-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">— Seleccionar —</option>
                    @foreach(\App\Models\Estudiante::activos()->orderBy('apellidos')->get(['id','nombres','apellidos']) as $est)
                    <option value="{{ $est->id }}">{{ $est->nombre_completo }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Producto (opcional)</label>
                <select name="producto_id" class="w-full text-sm border border-slate-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                        x-on:change="let p=$event.target.selectedOptions[0].dataset.precio; if(p) $root.querySelector('[name=monto]').value=p;">
                    <option value="">— Libre / Sin producto —</option>
                    @foreach(\App\Models\ProductoCafeteria::activos()->orderBy('nombre')->get() as $prod)
                    <option value="{{ $prod->id }}" data-precio="{{ $prod->precio }}">
                        {{ $prod->nombre }} — RD${{ number_format($prod->precio, 2) }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Descripción (opcional)</label>
                <input type="text" name="descripcion" maxlength="200" placeholder="Ej. Almuerzo especial..."
                       class="w-full text-sm border border-slate-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Monto (RD$) <span class="text-red-500">*</span></label>
                <input type="number" name="monto" step="0.01" min="0.01" required placeholder="0.00"
                       class="w-full text-sm border border-slate-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="flex gap-3 pt-2">
                <button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium py-2.5 rounded-lg transition">
                    Confirmar Venta
                </button>
                <button type="button" @click="modalVenta = false" class="flex-1 bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-medium py-2.5 rounded-lg transition">
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
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md" @click.outside="modalRecarga = false">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
            <h2 class="font-bold text-slate-800 text-lg flex items-center gap-2">
                <i class="bi bi-plus-circle text-emerald-600"></i>Recargar Saldo
            </h2>
            <button @click="modalRecarga = false" class="text-slate-400 hover:text-slate-600">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <form method="POST" action="{{ route('admin.cafeteria.recargas.store') }}" class="px-6 py-5 space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Estudiante <span class="text-red-500">*</span></label>
                <select name="estudiante_id" required class="w-full text-sm border border-slate-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                    <option value="">— Seleccionar —</option>
                    @foreach(\App\Models\Estudiante::activos()->orderBy('apellidos')->get(['id','nombres','apellidos']) as $est)
                    <option value="{{ $est->id }}">{{ $est->nombre_completo }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Monto a Recargar (RD$) <span class="text-red-500">*</span></label>
                <input type="number" name="monto" step="0.01" min="0.01" required placeholder="0.00"
                       class="w-full text-sm border border-slate-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-emerald-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Nota (opcional)</label>
                <input type="text" name="descripcion" maxlength="200" placeholder="Ej. Pago en efectivo..."
                       class="w-full text-sm border border-slate-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-emerald-500">
            </div>
            <div class="flex gap-3 pt-2">
                <button type="submit" class="flex-1 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium py-2.5 rounded-lg transition">
                    Aplicar Recarga
                </button>
                <button type="button" @click="modalRecarga = false" class="flex-1 bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-medium py-2.5 rounded-lg transition">
                    Cancelar
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ═══ Modal: Ajuste de Saldo ══════════════════════════════════════════════ --}}
<div x-show="modalAjuste" x-cloak
     class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm p-4"
     @keydown.escape.window="modalAjuste = false">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md" @click.outside="modalAjuste = false">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
            <h2 class="font-bold text-slate-800 text-lg flex items-center gap-2">
                <i class="bi bi-sliders text-amber-600"></i>Ajuste de Saldo
            </h2>
            <button @click="modalAjuste = false" class="text-slate-400 hover:text-slate-600">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <form method="POST" action="{{ route('admin.cafeteria.ajustes.store') }}" class="px-6 py-5 space-y-4">
            @csrf
            <div class="bg-amber-50 border border-amber-200 rounded-lg px-4 py-3 text-sm text-amber-800">
                <i class="bi bi-info-circle me-1"></i>
                Use monto <strong>positivo</strong> para aumentar saldo y <strong>negativo</strong> para reducirlo.
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Estudiante <span class="text-red-500">*</span></label>
                <select name="estudiante_id" required class="w-full text-sm border border-slate-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-amber-500">
                    <option value="">— Seleccionar —</option>
                    @foreach(\App\Models\Estudiante::activos()->orderBy('apellidos')->get(['id','nombres','apellidos']) as $est)
                    <option value="{{ $est->id }}">{{ $est->nombre_completo }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Monto (RD$) — puede ser negativo <span class="text-red-500">*</span></label>
                <input type="number" name="monto" step="0.01" required placeholder="Ej: 50 o -20"
                       class="w-full text-sm border border-slate-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-amber-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Motivo <span class="text-red-500">*</span></label>
                <input type="text" name="descripcion" maxlength="200" required placeholder="Ej. Error en cobro del 20/05..."
                       class="w-full text-sm border border-slate-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-amber-500">
            </div>
            <div class="flex gap-3 pt-2">
                <button type="submit" class="flex-1 bg-amber-600 hover:bg-amber-700 text-white text-sm font-medium py-2.5 rounded-lg transition">
                    Aplicar Ajuste
                </button>
                <button type="button" @click="modalAjuste = false" class="flex-1 bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-medium py-2.5 rounded-lg transition">
                    Cancelar
                </button>
            </div>
        </form>
    </div>
</div>

@endsection
