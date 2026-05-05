@extends('layouts.admin')

@section('page-title', 'Productos — Cafetería')

@section('content')
<div class="px-4 py-6 max-w-6xl mx-auto">

    {{-- Encabezado --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Productos de Cafetería</h1>
            <p class="text-sm text-slate-500 mt-0.5">Gestión del menú y precios</p>
        </div>
        <a href="{{ route('admin.cafeteria.productos.create') }}"
           class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white
                  text-sm font-medium px-4 py-2 rounded-lg transition shadow-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Nuevo Producto
        </a>
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

    {{-- Filtros --}}
    <form method="GET" class="bg-white rounded-xl border border-slate-100 shadow-sm p-4 mb-5">
        <div class="flex flex-wrap gap-3 items-end">
            <div class="flex-1 min-w-[160px]">
                <label class="block text-xs font-medium text-slate-500 mb-1">Buscar</label>
                <input type="text" name="q" value="{{ request('q') }}"
                       placeholder="Nombre del producto..."
                       class="w-full text-sm border border-slate-200 rounded-lg px-3 py-2
                              focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="min-w-[130px]">
                <label class="block text-xs font-medium text-slate-500 mb-1">Categoría</label>
                <select name="categoria"
                        class="w-full text-sm border border-slate-200 rounded-lg px-3 py-2
                               focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Todas</option>
                    @foreach($categorias as $key => $label)
                        <option value="{{ $key }}" {{ request('categoria') === $key ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="min-w-[110px]">
                <label class="block text-xs font-medium text-slate-500 mb-1">Estado</label>
                <select name="activo"
                        class="w-full text-sm border border-slate-200 rounded-lg px-3 py-2
                               focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Todos</option>
                    <option value="1" {{ request('activo') === '1' ? 'selected' : '' }}>Activos</option>
                    <option value="0" {{ request('activo') === '0' ? 'selected' : '' }}>Inactivos</option>
                </select>
            </div>
            <div class="flex gap-2">
                <button type="submit"
                        class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium
                               px-4 py-2 rounded-lg transition">
                    Filtrar
                </button>
                <a href="{{ route('admin.cafeteria.productos.index') }}"
                   class="bg-slate-100 hover:bg-slate-200 text-slate-600 text-sm font-medium
                          px-4 py-2 rounded-lg transition">
                    Limpiar
                </a>
            </div>
        </div>
    </form>

    {{-- Tabla --}}
    <div class="bg-white rounded-xl border border-slate-100 shadow-sm overflow-hidden">
        @if($productos->isEmpty())
            <div class="py-16 text-center text-slate-400">
                <svg class="mx-auto w-10 h-10 mb-3 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-1.5 6M17 13l1.5 6M9 19h6"/>
                </svg>
                <p class="text-sm">No hay productos registrados</p>
                <a href="{{ route('admin.cafeteria.productos.create') }}"
                   class="mt-2 inline-block text-blue-600 text-sm hover:underline">
                    Crear el primero
                </a>
            </div>
        @else
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-100 text-left">
                        <th class="px-4 py-3 font-semibold text-slate-600">Producto</th>
                        <th class="px-4 py-3 font-semibold text-slate-600">Categoría</th>
                        <th class="px-4 py-3 font-semibold text-slate-600 text-right">Precio</th>
                        <th class="px-4 py-3 font-semibold text-slate-600 text-center">Estado</th>
                        <th class="px-4 py-3 font-semibold text-slate-600 text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @foreach($productos as $producto)
                    <tr class="hover:bg-slate-50/50 transition">
                        <td class="px-4 py-3 font-medium text-slate-800">
                            {{ $producto->nombre }}
                        </td>
                        <td class="px-4 py-3">
                            @php
                                $catColors = [
                                    'comida' => 'bg-orange-100 text-orange-700',
                                    'bebida' => 'bg-blue-100 text-blue-700',
                                    'snack'  => 'bg-yellow-100 text-yellow-700',
                                    'otro'   => 'bg-slate-100 text-slate-600',
                                ];
                            @endphp
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium
                                         {{ $catColors[$producto->categoria] ?? 'bg-slate-100 text-slate-600' }}">
                                {{ \App\Models\ProductoCafeteria::CATEGORIAS[$producto->categoria] ?? $producto->categoria }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right font-semibold text-slate-800">
                            RD$ {{ number_format($producto->precio, 2) }}
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($producto->activo)
                                <span class="px-2 py-0.5 bg-green-100 text-green-700 rounded-full text-xs font-medium">
                                    Activo
                                </span>
                            @else
                                <span class="px-2 py-0.5 bg-slate-100 text-slate-500 rounded-full text-xs font-medium">
                                    Inactivo
                                </span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            <div class="flex items-center justify-center gap-2">
                                <a href="{{ route('admin.cafeteria.productos.edit', $producto) }}"
                                   class="text-blue-600 hover:text-blue-800 transition"
                                   title="Editar">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </a>
                                <form method="POST"
                                      action="{{ route('admin.cafeteria.productos.destroy', $producto) }}"
                                      onsubmit="return confirm('¿Eliminar este producto?')"
                                      class="inline">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-red-500 hover:text-red-700 transition" title="Eliminar">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            @if($productos->hasPages())
                <div class="px-4 py-3 border-t border-slate-100">
                    {{ $productos->links() }}
                </div>
            @endif
        @endif
    </div>

    {{-- Accesos rápidos --}}
    <div class="mt-4 flex gap-3 flex-wrap">
        <a href="{{ route('admin.cafeteria.ventas') }}"
           class="inline-flex items-center gap-2 text-sm text-slate-600 hover:text-blue-600 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
            </svg>
            Ver Ventas
        </a>
    </div>

</div>
@endsection
