@extends('layouts.admin')

@section('page-title', 'Editar Bloque — ' . $seccion->tipo_label)

@section('content')
<div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    <div class="flex items-center gap-3 mb-8">
        <a href="{{ route('admin.secciones.index') }}"
           class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-xl transition">
            <i class="bi bi-arrow-left text-lg"></i>
        </a>
        <div>
            <h1 class="text-2xl font-extrabold text-gray-900 dark:text-white tracking-tight flex items-center gap-2">
                <i class="bi {{ $seccion->icono }} text-indigo-500"></i>
                {{ $seccion->tipo_label }}
            </h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Este bloque se muestra en el sitio público del centro.</p>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-5 flex items-center gap-3 p-4 rounded-xl bg-green-50 border border-green-200 text-green-800 dark:bg-green-900/20 dark:border-green-700 dark:text-green-300">
            <i class="bi bi-check-circle-fill text-green-500 text-lg shrink-0"></i>
            <span class="text-sm font-medium">{{ session('success') }}</span>
        </div>
    @endif

    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-6">
        <form action="{{ route('admin.secciones.update', $seccion) }}" method="POST">
            @csrf
            @method('PUT')

            <label class="flex items-center gap-3 cursor-pointer mb-6 pb-5 border-b border-gray-100 dark:border-gray-700">
                <input type="checkbox" name="activo" value="1" @checked(old('activo', $seccion->activo))
                       class="w-5 h-5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                <span class="text-sm font-semibold text-gray-700 dark:text-gray-200">Bloque activo (visible en el sitio público)</span>
            </label>

            @include("admin.secciones.tipos.{$seccion->tipo}")

            <div class="flex items-center justify-end gap-3 pt-6 mt-2 border-t border-gray-100 dark:border-gray-700">
                <a href="{{ route('admin.secciones.index') }}"
                   class="px-4 py-2.5 text-sm font-semibold text-gray-600 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-xl transition">
                    Cancelar
                </a>
                <button type="submit"
                        class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-xl shadow transition">
                    <i class="bi bi-check-lg"></i>Guardar cambios
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
