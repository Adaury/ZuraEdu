@extends('layouts.admin')

@section('page-title', 'Avisos de Emergencia')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">

    {{-- Cabecera --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                <i class="bi bi-megaphone-fill text-red-500"></i>
                Avisos de Emergencia
            </h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                Historial de avisos masivos enviados al plantel
            </p>
        </div>
        <a href="{{ route('admin.avisos-emergencia.create') }}"
           class="inline-flex items-center gap-2 px-5 py-2.5 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold rounded-xl shadow transition">
            <i class="bi bi-send-fill"></i>
            Nuevo Aviso
        </a>
    </div>

    {{-- Alertas de sesión --}}
    @if(session('success'))
        <div class="mb-5 flex items-center gap-3 p-4 rounded-xl bg-green-50 border border-green-200 text-green-800 dark:bg-green-900/20 dark:border-green-700 dark:text-green-300">
            <i class="bi bi-check-circle-fill text-green-500 text-lg shrink-0"></i>
            <span class="text-sm font-medium">{{ session('success') }}</span>
        </div>
    @endif
    @if(session('error'))
        <div class="mb-5 flex items-center gap-3 p-4 rounded-xl bg-red-50 border border-red-200 text-red-800 dark:bg-red-900/20 dark:border-red-700 dark:text-red-300">
            <i class="bi bi-exclamation-triangle-fill text-red-500 text-lg shrink-0"></i>
            <span class="text-sm font-medium">{{ session('error') }}</span>
        </div>
    @endif

    {{-- Tabla --}}
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">

        @if($avisos->isEmpty())
            <div class="flex flex-col items-center justify-center py-20 text-gray-400 dark:text-gray-500">
                <i class="bi bi-megaphone text-5xl mb-4 opacity-40"></i>
                <p class="text-base font-medium">No se han enviado avisos todavía</p>
                <p class="text-sm mt-1">Usa el botón <strong>Nuevo Aviso</strong> para enviar el primero</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-700/50 border-b border-gray-200 dark:border-gray-600">
                        <tr>
                            <th class="text-left px-5 py-3.5 font-semibold text-gray-600 dark:text-gray-300">Tipo</th>
                            <th class="text-left px-5 py-3.5 font-semibold text-gray-600 dark:text-gray-300">Título / Mensaje</th>
                            <th class="text-left px-5 py-3.5 font-semibold text-gray-600 dark:text-gray-300">Destinatarios</th>
                            <th class="text-center px-5 py-3.5 font-semibold text-gray-600 dark:text-gray-300">Enviados</th>
                            <th class="text-left px-5 py-3.5 font-semibold text-gray-600 dark:text-gray-300">Por</th>
                            <th class="text-left px-5 py-3.5 font-semibold text-gray-600 dark:text-gray-300">Fecha</th>
                            <th class="px-5 py-3.5"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach($avisos as $aviso)
                            <tr class="hover:bg-gray-50/60 dark:hover:bg-gray-700/30 transition">

                                {{-- Tipo --}}
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold {{ $aviso->badge_clase }}">
                                        <i class="bi {{ $aviso->icono }}"></i>
                                        {{ $aviso->tipo_label }}
                                    </span>
                                </td>

                                {{-- Título y extracto del mensaje --}}
                                <td class="px-5 py-4 max-w-xs">
                                    <p class="font-semibold text-gray-900 dark:text-white truncate">{{ $aviso->titulo }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 truncate mt-0.5">{{ Str::limit($aviso->mensaje, 80) }}</p>
                                </td>

                                {{-- Destinatarios --}}
                                <td class="px-5 py-4 text-gray-700 dark:text-gray-300 whitespace-nowrap">
                                    {{ $aviso->destinatarios_label }}
                                </td>

                                {{-- Total enviados --}}
                                <td class="px-5 py-4 text-center">
                                    <span class="inline-flex items-center justify-center w-9 h-9 rounded-full bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 font-bold text-sm">
                                        {{ $aviso->total_enviados }}
                                    </span>
                                </td>

                                {{-- Enviado por --}}
                                <td class="px-5 py-4 text-gray-700 dark:text-gray-300 whitespace-nowrap">
                                    {{ $aviso->enviadoPor?->nombre_completo ?? '—' }}
                                </td>

                                {{-- Fecha --}}
                                <td class="px-5 py-4 text-gray-500 dark:text-gray-400 whitespace-nowrap text-xs">
                                    {{ $aviso->created_at->format('d/m/Y') }}<br>
                                    <span class="text-gray-400">{{ $aviso->created_at->format('H:i') }}</span>
                                </td>

                                {{-- Acciones --}}
                                <td class="px-5 py-4 whitespace-nowrap text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <a href="{{ route('admin.avisos-emergencia.show', $aviso) }}"
                                           class="p-1.5 rounded-lg text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 transition"
                                           title="Ver detalle">
                                            <i class="bi bi-eye text-base"></i>
                                        </a>
                                        <form method="POST" action="{{ route('admin.avisos-emergencia.destroy', $aviso) }}"
                                              onsubmit="return confirm('¿Eliminar este aviso del historial?')">
                                            @csrf @method('DELETE')
                                            <button type="submit"
                                                    class="p-1.5 rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 transition"
                                                    title="Eliminar">
                                                <i class="bi bi-trash text-base"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>

                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Paginación --}}
            @if($avisos->hasPages())
                <div class="px-5 py-4 border-t border-gray-100 dark:border-gray-700">
                    {{ $avisos->links() }}
                </div>
            @endif
        @endif
    </div>

</div>
@endsection
