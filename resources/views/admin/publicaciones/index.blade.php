@extends('layouts.admin')

@section('page-title', 'Noticias y Publicaciones')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                <i class="bi bi-newspaper text-indigo-500"></i>
                Noticias y Publicaciones
            </h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                Contenido del sitio público — noticias, avisos, actividades, logros y convocatorias.
            </p>
        </div>
        <a href="{{ route('admin.publicaciones.create') }}"
           class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-xl shadow transition">
            <i class="bi bi-plus-lg"></i>Nueva Publicación
        </a>
    </div>

    @if(session('success'))
        <div class="mb-5 flex items-center gap-3 p-4 rounded-xl bg-green-50 border border-green-200 text-green-800 dark:bg-green-900/20 dark:border-green-700 dark:text-green-300">
            <i class="bi bi-check-circle-fill text-green-500 text-lg shrink-0"></i>
            <span class="text-sm font-medium">{{ session('success') }}</span>
        </div>
    @endif

    <div class="flex flex-wrap gap-2 mb-5">
        <a href="{{ route('admin.publicaciones.index') }}"
           class="px-3 py-1.5 rounded-full text-xs font-semibold {{ !request('tipo') ? 'bg-indigo-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300' }}">
            Todas
        </a>
        @foreach(\App\Models\Publicacion::TIPOS as $key => $label)
        <a href="{{ route('admin.publicaciones.index', ['tipo' => $key]) }}"
           class="px-3 py-1.5 rounded-full text-xs font-semibold {{ request('tipo') === $key ? 'bg-indigo-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300' }}">
            {{ $label }}
        </a>
        @endforeach
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        @if($publicaciones->isEmpty())
            <div class="flex flex-col items-center justify-center py-20 text-gray-400 dark:text-gray-500">
                <i class="bi bi-newspaper text-5xl mb-4 opacity-40"></i>
                <p class="text-base font-medium">No hay publicaciones todavía</p>
                <p class="text-sm mt-1">Usa el botón <strong>Nueva Publicación</strong> para crear la primera</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-700/50 border-b border-gray-200 dark:border-gray-600">
                        <tr>
                            <th class="text-left px-5 py-3.5 font-semibold text-gray-600 dark:text-gray-300">Tipo</th>
                            <th class="text-left px-5 py-3.5 font-semibold text-gray-600 dark:text-gray-300">Título</th>
                            <th class="text-left px-5 py-3.5 font-semibold text-gray-600 dark:text-gray-300">Fecha</th>
                            <th class="text-center px-5 py-3.5 font-semibold text-gray-600 dark:text-gray-300">Estado</th>
                            <th class="text-center px-5 py-3.5 font-semibold text-gray-600 dark:text-gray-300">Visible</th>
                            <th class="px-5 py-3.5"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach($publicaciones as $publicacion)
                        <tr class="hover:bg-gray-50/60 dark:hover:bg-gray-700/30 transition">
                            <td class="px-5 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300">
                                    {{ $publicacion->tipo_label }}
                                </span>
                            </td>
                            <td class="px-5 py-4 max-w-sm">
                                <p class="font-semibold text-gray-900 dark:text-white truncate">{{ $publicacion->titulo }}</p>
                            </td>
                            <td class="px-5 py-4 text-gray-500 dark:text-gray-400 whitespace-nowrap text-xs">
                                {{ $publicacion->fecha->format('d/m/Y') }}
                            </td>
                            <td class="px-5 py-4 text-center">
                                @if($publicacion->estado === 'publicado')
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-green-50 dark:bg-green-900/30 text-green-700 dark:text-green-300">Publicado</span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300">Borrador</span>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-center">
                                <i class="bi {{ $publicacion->visible ? 'bi-eye-fill text-green-500' : 'bi-eye-slash text-gray-400' }}"></i>
                            </td>
                            <td class="px-5 py-4 text-right whitespace-nowrap">
                                <a href="{{ route('admin.publicaciones.edit', $publicacion) }}"
                                   class="text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 mr-3"><i class="bi bi-pencil"></i></a>
                                <form action="{{ route('admin.publicaciones.destroy', $publicacion) }}" method="POST" class="inline"
                                      onsubmit="return confirm('¿Eliminar esta publicación?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-red-500 hover:text-red-700"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="px-5 py-4 border-t border-gray-100 dark:border-gray-700">{{ $publicaciones->links() }}</div>
        @endif
    </div>
</div>
@endsection
